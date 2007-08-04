<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return; // securiser
if (!function_exists('generer_url_article')) { // si la place n'est pas prise

/*

- Comment utiliser ce jeu d'URLs ?

Recopiez le fichier "htaccess.txt" du repertoire de base du site SPIP sous
le sous le nom ".htaccess" (attention a ne pas ecraser d'autres reglages
que vous pourriez avoir mis dans ce fichier) ; si votre site est en
"sous-repertoire", vous devrez aussi editer la ligne "RewriteBase" ce fichier.
Les URLs definies seront alors redirigees vers les fichiers de SPIP.

Definissez ensuite dans ecrire/mes_options.php :
	< ?php $type_urls = 'propres'; ? >
SPIP calculera alors ses liens sous la forme "Mon-titre-d-article".

Variante 'propres2' :
	< ?php $type_urls = 'propres2'; ? >
ajoutera '.html' aux adresses generees : "Mon-titre-d-article.html"

Variante 'qs' (experimentale) : ce systeme fonctionne en "Query-String",
c'est-a-dire sans utilisation de .htaccess ; les adresses sont de la forme
"/?Mon-titre-d-article"
	< ?php $type_urls = 'qs'; ? >

*/

define ('_terminaison_urls_propres', '');
define ('_debut_urls_propres', '');

// http://doc.spip.org/@_generer_url_propre
function _generer_url_propre($type, $id_objet) {
	$table = "spip_".table_objet($type);
	$col_id = id_table_objet($type);

	// Auteurs : on prend le nom
	if ($type == 'auteur')
		$champ_titre = 'nom AS titre';
	else if ($type == 'site' OR $type=='syndic')
		$champ_titre = 'nom_site AS titre';
	else
		$champ_titre = 'titre';

	// Mots-cles : pas de champ statut
	if ($type == 'mot')
		$statut = "'publie' as statut";
	else
		$statut = 'statut';

	// D'abord, essayer de recuperer l'URL existante si possible
	$result = spip_query("SELECT url_propre, $statut, $champ_titre FROM $table WHERE $col_id=$id_objet");
	if (!($row = spip_abstract_fetch($result))) return ""; # objet inexistant

	// Si l'on n'est pas dans spip_redirect.php3 sur un objet non publie
	// ou en preview (astuce pour corriger un url-propre) + admin connecte
	// Ne pas recalculer l'url-propre,
	// sauf si :
	// 1) il n'existe pas, ou
	// 2) l'objet n'est pas 'publie' et on est admin connecte, ou
	// 3) on le demande explicitement (preview) et on est admin connecte
	$modif_url_propre = false;
	if (_request('action') == 'redirect'
	AND (
		(_request('var_mode') == 'preview' OR $row['statut'] <> 'publie')
		OR
		(defined('_URL_PROPRES_REGENERER') AND _URL_PROPRES_REGENERER)
	)
	AND $GLOBALS['auteur_session']['statut'] == '0minirezo')
		$modif_url_propre = true;

	if ($row['url_propre'] AND !$modif_url_propre)
		return $row['url_propre'];

	// Sinon, creer l'URL
	include_spip('inc/filtres');
	$url = translitteration(corriger_caracteres(
		supprimer_tags(supprimer_numero(extraire_multi($row['titre'])))
		));

	$url = @preg_replace(',[[:punct:][:space:]]+,u', ' ', $url);
	// S'il reste trop de caracteres non latins, ou trop peu
	// de caracteres latins, utiliser l'id a la place
	if (preg_match(",([^a-zA-Z0-9 ].*){5},", $url, $r)
	OR strlen($url)<3) {
		$url = $type.$id_objet;
	}
	else {
		$mots = preg_split(",[^a-zA-Z0-9]+,", $url);
		$url = '';
		foreach ($mots as $mot) {
			if (!$mot) continue;
			$url2 = $url.'-'.$mot;
			if (strlen($url2) > 35) {
				break;
			}
			$url = $url2;
		}
		$url = substr($url, 1);
		//echo "$url<br>";
		if (strlen($url) < 2) $url = $type.$id_objet;
	}

	// Verifier les eventuels doublons et mettre a jour
	$lock = "url $type $id_objet";
	spip_get_lock($lock, 10);

	$n = spip_num_rows(spip_query("SELECT $col_id FROM $table WHERE url_propre=" . _q($url) . " AND $col_id != $id_objet LIMIT 1"));
	if ($n > 0) {
		$url = $url.','.$id_objet;
	}

	// Eviter de tamponner les URLs a l'ancienne (cas d'un article
	// intitule "auteur2")
	if ($type == 'article'
	AND preg_match(',^(article|breve|rubrique|mot|auteur)[0-9]+$,', $url))
		$url = $url.','.$id_objet;

	// Mettre a jour dans la base
	spip_query("UPDATE $table SET url_propre=" . _q($url) . " WHERE $col_id=$id_objet");

	spip_release_lock($lock);

	spip_log("Creation de l'url propre '$url' pour $col_id=$id_objet");

	return $url;
}

// http://doc.spip.org/@generer_url_article
function generer_url_article($id_article, $args='', $ancre='') {
	$url = _generer_url_propre('article', $id_article);
	if ($url)
		$url = _debut_urls_propres . $url . _terminaison_urls_propres 
		. (!$args ? ''
		: (((strpos(_debut_urls_propres, '?')===false) ? '?' : '&') . $args));
	else
		$url = get_spip_script('./')."?page=article&id_article=$id_article" . ($args ? "&$args" : '');
	if ($ancre) $url .= "#$ancre";
	return $url;
}

// http://doc.spip.org/@generer_url_rubrique
function generer_url_rubrique($id_rubrique, $args='', $ancre='') {
	$url = _generer_url_propre('rubrique', $id_rubrique);
	if ($url)
		$url = _debut_urls_propres . '-'.$url.'-'._terminaison_urls_propres
		. (!$args ? ''
		: (((strpos(_debut_urls_propres, '?')===false) ? '?' : '&') . $args));
	else
		$url = get_spip_script('./')."?page=rubrique&id_rubrique=$id_rubrique" . ($args ? "&$args" : '');
	if ($ancre) $url .= "#$ancre";
	return $url;
}

// http://doc.spip.org/@generer_url_breve
function generer_url_breve($id_breve, $args='', $ancre='') {
	$url = _generer_url_propre('breve', $id_breve);
	if ($url)
		$url = _debut_urls_propres . '+'.$url.'+'._terminaison_urls_propres
		. (!$args ? ''
		: (((strpos(_debut_urls_propres, '?')===false) ? '?' : '&') . $args));
	else
		$url = get_spip_script('./')."?page=breve&id_breve=$id_breve"  . ($args ? "&$args" : '');
	if ($ancre) $url .= "#$ancre";
	return $url;
}

// http://doc.spip.org/@generer_url_forum
function generer_url_forum($id_forum, $args='', $ancre='') {
	include_spip('inc/forum');
	return generer_url_forum_dist($id_forum, $args, $ancre);
}

// http://doc.spip.org/@generer_url_mot
function generer_url_mot($id_mot, $args='', $ancre='') {
	$url = _generer_url_propre('mot', $id_mot);
	if ($url)
		$url = _debut_urls_propres . '+-'.$url.'-+'._terminaison_urls_propres
		. (!$args ? ''
		: (((strpos(_debut_urls_propres, '?')===false) ? '?' : '&') . $args));
	else
		$url = get_spip_script('./')."?page=mot&id_mot=$id_mot" . ($args ? "&$args" : '');
	if ($ancre) $url .= "#$ancre";
	return $url;
}

// http://doc.spip.org/@generer_url_auteur
function generer_url_auteur($id_auteur, $args='', $ancre='') {
	$url = _generer_url_propre('auteur', $id_auteur);
	if ($url)
		$url = _debut_urls_propres . '_'.$url.'_'._terminaison_urls_propres
		. (!$args ? ''
		: (((strpos(_debut_urls_propres, '?')===false) ? '?' : '&') . $args));
	else
		$url = get_spip_script('./')."?page=auteur&id_auteur=$id_auteur" . ($args ? "&$args" : '');
	if ($ancre) $url .= "#$ancre";
	return $url;
}

// http://doc.spip.org/@generer_url_site
function generer_url_site($id_syndic, $args='', $ancre='') {
	$url = _generer_url_propre('site', $id_syndic);
	if ($url)
		$url = _debut_urls_propres . '@'.$url.'@'._terminaison_urls_propres
		. (!$args ? ''
		: (((strpos(_debut_urls_propres, '?')===false) ? '?' : '&') . $args));
	else
		$url = get_spip_script('./')."?page=site&id_syndic=$id_syndic" . ($args ? "&$args" : '');
	if ($ancre) $url .= "#$ancre";
	return $url;
}

// http://doc.spip.org/@generer_url_document
function generer_url_document($id_document, $args='', $ancre='') {
	include_spip('inc/documents');
	return generer_url_document_dist($id_document, $args, $ancre);
}

// http://doc.spip.org/@recuperer_parametres_url
function recuperer_parametres_url(&$fond, $url) {
	global $contexte;
	$id_objet = 0;

	// Migration depuis anciennes URLs ?
	if ($_SERVER['REQUEST_METHOD'] != 'POST') {
		if (preg_match(
		',(^|/)(article|breve|rubrique|mot|auteur|site)(\.php3?|[0-9]+\.html)'
		.'([?&].*)?$,', $url, $regs)
		) {
			$type = $regs[3];
			$id_table_objet = id_table_objet($type);
			$id_objet = intval($GLOBALS[$id_table_objet]);
		}

		/* Compatibilite urls-page */
		else if (preg_match(
		',[?/&](article|breve|rubrique|mot|auteur|site)[=]?([0-9]+),',
		$url, $regs)) {
			$type = $regs[1];
			$id_objet = $regs[2];
		}
	}
	if ($id_objet) {
		$func = "generer_url_$type";
		$url_propre = $func($id_objet);
		if (strlen($url_propre)
		AND !strstr($url,$url_propre)) {
			include_spip('inc/headers');
			http_status(301);
			// recuperer les arguments supplementaires (&debut_xxx=...)
			$reste = preg_replace('/^&/','?',
				preg_replace("/[?&]$id_table_objet=$id_objet/",'',$regs[5]));
			$reste .= preg_replace('/&/','?',
				preg_replace('/[?&]'.$type.'[=]?'.$id_objet.'/','',
				substr($url, strpos($url,'?'))));
			redirige_par_entete("$url_propre$reste");
		}
	}
	/* Fin compatibilite anciennes urls */

	// Chercher les valeurs d'environnement qui indiquent l'url-propre
	if (isset($_SERVER['REDIRECT_url_propre']))
		$url_propre = $_SERVER['REDIRECT_url_propre'];
	elseif (isset($_ENV['url_propre']))
		$url_propre = $_ENV['url_propre'];
	else {
		$url = substr($url, strrpos($url, '/') + 1);
		$url_propre = preg_replace(',[?].*,', '', $url);
	}
	// Mode Query-String ?
	$adapter_le_fond = false;
	if (!$url_propre
	AND preg_match(',([?])([^=/?&]+)(&.*)?$,', $GLOBALS['REQUEST_URI'], $r)) {
		$url_propre = $r[2];
		$adapter_le_fond = true;
	}
	if (!$url_propre) return;

	// Compatilibite avec propres2
	$url_propre = preg_replace(',\.html$,i', '', $url_propre);

	// Detecter les differents types d'objets demandes
	if (preg_match(',^\+-(.*?)-?\+?$,', $url_propre, $regs)) {
		$type = 'mot';
		$url_propre = $regs[1];
	}
	else if (preg_match(',^-(.*?)-?$,', $url_propre, $regs)) {
		$type = 'rubrique';
		$url_propre = $regs[1];
	}
	else if (preg_match(',^\+(.*?)\+?$,', $url_propre, $regs)) {
		$type = 'breve';
		$url_propre = $regs[1];
	}
	else if (preg_match(',^_(.*?)_?$,', $url_propre, $regs)) {
		$type = 'auteur';
		$url_propre = $regs[1];
	}
	else if (preg_match(',^@(.*?)@?$,', $url_propre, $regs)) {
		$type = 'syndic';
		$url_propre = $regs[1];
	}
	else {
		$type = 'article';
		preg_match(',^(.*)$,', $url_propre, $regs);
		$url_propre = $regs[1];
	}

	$table = "spip_".table_objet($type);
	$col_id = id_table_objet($type);
	$result = spip_query("SELECT $col_id FROM $table WHERE url_propre=" . _q($url_propre));

	if ($row = spip_abstract_fetch($result)) {
		$contexte[$col_id] = $row[$col_id];
	}

	// En mode Query-String, on fixe ici le $fond utilise
	if ($adapter_le_fond) {
		$fond = $type;
		if ($type == 'syndic') $fond = 'site';
	}

	return;
}
 }

?>
