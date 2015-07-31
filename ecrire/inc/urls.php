<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;
include_spip('base/objets');

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
	static $current_base = null;
	// les anciennes fonctions modifient directement les globales
	// on les sauve avant l'appel, et on les retablit apres !
	$save = array(@$GLOBALS['fond'],@$GLOBALS['contexte'],@$_SERVER['REDIRECT_url_propre'],@$_ENV['url_propre'],$GLOBALS['profondeur_url']);
	if (is_null($current_base)){
		include_spip('inc/filtres_mini');
		// le decodage des urls se fait toujours par rapport au site public
		$current_base = url_absolue(_DIR_RACINE?_DIR_RACINE:'./');
	}
	if (strncmp($url,$current_base,strlen($current_base))==0)
		$url = substr($url,strlen($current_base));

	// si on est pas en train d'assembler la page principale,
	// vider les globales url propres qui ne doivent pas etre utilisees en cas
	// d'inversion url => objet
	if (!$assembler) {
		unset($_SERVER['REDIRECT_url_propre']);
		unset($_ENV['url_propre']);
		include_spip('inc/filtres_mini');
		if (strpos($url,"://")===false){
            $GLOBALS['profondeur_url'] = substr_count(ltrim(resolve_path("/$url"),'/'),'/');
    }
    else {
            $GLOBALS['profondeur_url'] = max(0,substr_count($url,"/")-substr_count($current_base,"/"));
    }
	}

	
	$url_redirect = "";
	$renommer = generer_url_entite('','','','',true);
	if (!$renommer AND !function_exists('recuperer_parametres_url'))
		$renommer = charger_fonction('page','urls'); // fallback pour decoder l'url
	if ($renommer) {
		$a = $renommer($url, $fond, $contexte);
		if (is_array($a)) {
			list($ncontexte, $type, $url_redirect, $nfond) = array_pad($a, 4, null);
			if ($url_redirect == $url)
				$url_redirect = ""; // securite pour eviter une redirection infinie
			if ($assembler AND strlen($url_redirect)) {
				spip_log("Redirige $url vers $url_redirect");
				include_spip('inc/headers');
				redirige_par_entete($url_redirect, '', 301);
			}
			if (isset($nfond))
				$fond = $nfond;
			else if ($fond == ''
			OR $fond == 'type_urls' /* compat avec htaccess 2.0.0 */
			)
				$fond = $type;
			if (isset($ncontexte))
				$contexte = $ncontexte;
			if (defined('_DEFINIR_CONTEXTE_TYPE') AND _DEFINIR_CONTEXTE_TYPE)
				$contexte['type'] = $type;
			if (defined('_DEFINIR_CONTEXTE_TYPE_PAGE') AND _DEFINIR_CONTEXTE_TYPE_PAGE)
				$contexte['type-page'] = $type;
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
	list($GLOBALS['fond'],$GLOBALS['contexte'],$_SERVER['REDIRECT_url_propre'],$_ENV['url_propre'],$GLOBALS['profondeur_url']) = $save;
	
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
 * Generer l'url d'un objet dans l'espace prive,
 * fonction de son etat publie ou non
 * calcule a partir de la declaration de statut
 *
 * @param int $id
 * @param string $args
 * @param string $ancre
 * @param string $statut
 * @param string $connect
 * @return string
 *
 */
function generer_url_ecrire_objet($objet,$id, $args='', $ancre='', $public=null, $connect=''){
	static $furls = array();
	if (!isset($furls[$objet])){
		if (function_exists($f = 'generer_url_ecrire_' . $objet)
			// ou definie par un plugin
			OR $f = charger_fonction($f,'urls',true))
			$furls[$objet] = $f;
		else
			$furls[$objet] = '';
	}
	if ($furls[$objet])
		return $furls[$objet]($id, $args, $ancre, $public, $connect);
	// si pas de flag public fourni
	// le calculer en fonction de la declaration de statut
	if (is_null($public) AND !$connect)
		$public = objet_test_si_publie($objet, $id, $connect);
	if ($public OR $connect){
		return generer_url_entite_absolue($id, $objet, $args, $ancre, $connect);
	}
	$a = id_table_objet($objet) . "=" . intval($id);
	if (!function_exists('objet_info'))
		include_spip('inc/filtres');
	return generer_url_ecrire(objet_info($objet,'url_voir'), $a . ($args ? "&$args" : '')). ($ancre ? "#$ancre" : '');
}

?>
