<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Decoder une url en utilisant les fonctions inverse
 * gestion des URLs transformee par le htaccess
 * $renommer = 'urls_propres_dist';
 * renvoie array($contexte, $type, $url_redirect, $nfond)
 * $nfond n'est retourne que si l'url est definie apres le ?
 * et risque d'etre effacee par un form en get
 * elle est utilisee par form_hidden exclusivement
 * Compat ascendante si le retour est null en gerant une sauvegarde/restauration
 * des globales modifiees par les anciennes fonctions
 *
 * @param string $url
 *  url a decoder
 * @param string $fond
 *  fond initial par defaut
 * @param array $contexte
 *  contexte initial a prendre en compte
 * @param bool $assembler
 *	true si l'url correspond a l'url principale de la page qu'on est en train d'assembler
 *  dans ce cas la fonction redirigera automatiquement si besoin
 *  et utilisera les eventuelles globales $_SERVER['REDIRECT_url_propre'] et $_ENV['url_propre']
 *  provenant du htaccess
 * @return array
 *  ($fond,$contexte,$url_redirect)
 *  si l'url n'est pas valide, $fond restera a la valeur initiale passee
 *  il suffit d'appeler la fonction sans $fond et de verifier qu'a son retour celui-ci
 *  est non vide pour verifier une url
 *
 */
function urls_decoder_url($url, $fond='', $contexte=array(), $assembler=false){
	// les anciennes fonctions modifient directement les globales
	// on les sauve avant l'appel, et on les retablit apres !
	$save = array(@$GLOBALS['fond'],@$GLOBALS['contexte'],@$_SERVER['REDIRECT_url_propre'],@$_ENV['url_propre']);

	// si on est pas en train d'assembler la page principale,
	// vider les globales url propres qui ne doivent pas etre utilisees en cas
	// d'inversion url => objet
	if (!$assembler) {
		unset($_SERVER['REDIRECT_url_propre']);
		unset($_ENV['url_propre']);
	}

	
	$url_redirect = "";
	$renommer = generer_url_entite();
	if (!$renommer AND !function_exists('recuperer_parametres_url'))
		$renommer = charger_fonction('page','urls'); // fallback pour decoder l'url
	if ($renommer) {
		$a = $renommer($url, $fond, $contexte);
		if (is_array($a)) {
			list($ncontexte, $type, $url_redirect, $nfond) = $a;
			if ($url_redirect == $url)
				$url_redirect = ""; // securite pour eviter une redirection infinie
			if ($assembler AND strlen($url_redirect)) {
				spip_log("Redirige $url vers $url_redirect");
				include_spip('inc/headers');
				http_status(301);
				redirige_par_entete($url_redirect);
			}
			if (isset($nfond))
				$fond = $nfond;
			else if ($fond == ''
			OR $fond == 'type_urls' /* compat avec htaccess 2.0.0 */
			)
				$fond = $type;
			if (isset($ncontexte))
				$contexte = $ncontexte;
		}
	}
	// compatibilite <= 1.9.2
	elseif (function_exists('recuperer_parametres_url')) {
		$GLOBALS['fond'] = $fond;
		$GLOBALS['contexte'] = $contexte;
		recuperer_parametres_url($fond, nettoyer_uri());
		// fond est en principe modifiee directement
		$contexte = $GLOBALS['contexte'];
	}

	// retablir les globales
	list($GLOBALS['fond'],$GLOBALS['contexte'],$_SERVER['REDIRECT_url_propre'],$_ENV['url_propre']) = $save;
	
	// vider les globales url propres qui ne doivent plus etre utilisees en cas
	// d'inversion url => objet
	// maintenir pour compat ?
	#if ($assembler) {
	#	unset($_SERVER['REDIRECT_url_propre']);
	#	unset($_ENV['url_propre']);
	#}

	return array($fond,$contexte,$url_redirect);
}


/**
 * Lister les objets pris en compte dans les urls
 * c'est a dire suceptibles d'avoir une url propre
 *
 * @param bool $preg
 *  permet de definir si la fonction retourne une chaine avec | comme separateur
 *  pour utiliser en preg, ou un array()
 * @return string/array
 */
function urls_liste_objets($preg = true){
	static $url_objets = null;
	if (is_null($url_objets)){
		$url_objets = array();
		// recuperer les tables_objets_sql declarees
		include_spip('base/objets');
		$tables_objets = lister_tables_objets_sql();
		foreach($tables_objets as $t=>$infos){
			if ($infos['page']) {
				$url_objets[] = $infos['type'];
				$url_objets = array_merge($url_objets,$infos['type_surnoms']);
			}
		}
		$url_objets = pipeline('declarer_url_objets',$url_objets);
	}
	if (!$preg) return $url_objets;
	return implode('|',array_map('preg_quote',$url_objets));
}

/**
 * Nettoyer une url, en reperant notamment les raccourcis d'entites
 * comme ?article13, ?rubrique21 ...
 * et en les traduisant pour completer le contexte fourni en entree
 *
 * @param string $url
 * @param array $contexte
 * @return array
 */
function nettoyer_url_page($url, $contexte=array())
{
	$url_objets = urls_liste_objets();
	$raccourci_url_page_html = ',^(?:[^?]*/)?('. $url_objets . ')([0-9]+)(?:\.html)?([?&].*)?$,';
	$raccourci_url_page_id = ',^(?:[^?]*/)?('. $url_objets .')\.php3?[?]id_\1=([0-9]+)([?&].*)?$,';
	$raccourci_url_page_spip = ',^(?:[^?]*/)?(?:spip[.]php)?[?]('. $url_objets .')([0-9]+)(&.*)?$,';

	if (preg_match($raccourci_url_page_html, $url, $regs)
	OR preg_match($raccourci_url_page_id, $url, $regs)
	OR preg_match($raccourci_url_page_spip, $url, $regs)) {
		$type = objet_type($regs[1]);
		$_id = id_table_objet($type);
		$contexte[$_id] = $regs[2];
		$suite = $regs[3];
		return array($contexte, $type, null, $type, $suite);
	}
	return array();
}


/**
 * Generer l'url d'un article dans l'espace prive,
 * fonction du statut de l'article
 *
 * @param int $id
 * @param string $args
 * @param string $ancre
 * @param string $statut
 * @param string $connect
 * @return string
 *
 * http://doc.spip.org/@generer_url_ecrire_article
 */
function generer_url_ecrire_article($id, $args='', $ancre='', $statut='', $connect='') {
	$a = "id_article=" . intval($id);
	if (!$statut) {
		$statut = sql_getfetsel('statut', 'spip_articles', $a,'','','','',$connect);
	}
	$h = ($statut == 'publie' OR $connect)
	? generer_url_entite_absolue($id, 'article', $args, $ancre, $connect)
	: (generer_url_ecrire('article', $a . ($args ? "&$args" : ''))
		. ($ancre ? "#$ancre" : ''));
	return $h;
}

/**
 * Generer l'url d'une rubrique dans l'espace prive,
 * fonction du statut de la rubrique
 *
 * @param int $id
 * @param string $args
 * @param string $ancre
 * @param string $statut
 * @param string $connect
 * @return string
 *
 * http://doc.spip.org/@generer_url_ecrire_rubrique
 */
function generer_url_ecrire_rubrique($id, $args='', $ancre='', $statut='', $connect='') {
	$a = "id_rubrique=" . intval($id);
	if (!$statut) {
		$statut = sql_getfetsel('statut', 'spip_rubriques', $a,'','','','',$connect);
	}
	$h = ($statut == 'publie' OR $connect)
	? generer_url_entite_absolue($id, 'rubrique', $args, $ancre, $connect)
	: (generer_url_ecrire('naviguer',$a . ($args ? "&$args" : ''))
		. ($ancre ? "#$ancre" : ''));
	return $h;
}


/**
 * Generer l'url d'un auteur dans l'espace prive,
 * fonction du statut de l'auteur
 *
 * @param int $id
 * @param string $args
 * @param string $ancre
 * @param string $statut
 * @param string $connect
 * @return string
 *
 * http://doc.spip.org/@generer_url_ecrire_auteur
 */
function generer_url_ecrire_auteur($id, $args='', $ancre='', $statut='', $connect='') {
	$a = (intval($id)?"id_auteur=" . intval($id):'');
	$h = (!$statut OR $connect)
	?  generer_url_entite_absolue($id, 'auteur', $args, $ancre, $connect)
	: (generer_url_ecrire('auteur',$a . ($args ? ($a?"&":"").$args : ''))
		. ($ancre ? "#$ancre" : ''));
	return $h;
}

/**
 * Generer l'url d'un document dans l'espace prive,
 * fonction du statut du document
 * 
 * @param int $id
 * @param string $args
 * @param string $ancre
 * @param string $statut
 * @param string $connect
 * @return string
 *
 * http://doc.spip.org/@generer_url_ecrire_document
 */
function generer_url_ecrire_document($id, $args='', $ancre='', $statut='', $connect='') {
	include_spip('inc/documents');
	return generer_url_document_dist($id);
}

?>
