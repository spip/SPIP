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


## type d'URLs obsolete

if (!defined('_ECRIRE_INC_VERSION')) return;

define('URLS_STANDARD_EXEMPLE', 'article.php3?id_article=12');

if (!function_exists('generer_url_article')) { // si la place n'est pas prise

// http://doc.spip.org/@generer_url_article
function generer_url_article($id_article) {
	return _DIR_RACINE . "article.php3?id_article=$id_article";
}

// http://doc.spip.org/@generer_url_rubrique
function generer_url_rubrique($id_rubrique) {
	return _DIR_RACINE . "rubrique.php3?id_rubrique=$id_rubrique";
}

// http://doc.spip.org/@generer_url_breve
function generer_url_breve($id_breve) {
	return _DIR_RACINE . "breve.php3?id_breve=$id_breve";
}

// http://doc.spip.org/@generer_url_mot
function generer_url_mot($id_mot) {
	return _DIR_RACINE . "mot.php3?id_mot=$id_mot";
}

// http://doc.spip.org/@generer_url_site
function generer_url_site($id_syndic) {
	return _DIR_RACINE . "site.php3?id_syndic=$id_syndic";
}

// http://doc.spip.org/@generer_url_auteur
function generer_url_auteur($id_auteur) {
	return _DIR_RACINE . "auteur.php3?id_auteur=$id_auteur";
}

// http://doc.spip.org/@generer_url_document
function generer_url_document($id_document) {
	include_spip('inc/documents');
	return generer_url_document_dist($id_document);
}

// http://doc.spip.org/@recuperer_parametres_url
function recuperer_parametres_url(&$fond, $url) {
	global $contexte;

	// traiter les injections du type domaine.org/spip.php/cestnimportequoi/ou/encore/plus/rubrique23
	if ($GLOBALS['profondeur_url']>0 AND $fond=='sommaire'){
		$fond = '404';
	}

	/*
	 * Le bloc qui suit sert a faciliter les transitions depuis
	 * le mode 'urls-propres' vers les modes 'urls-standard' et 'url-html'
	 * Il est inutile de le recopier si vous personnalisez vos URLs
	 * et votre .htaccess
	 */
	// Si on est revenu en mode html, mais c'est une ancienne url_propre
	// on ne redirige pas, on assume le nouveau contexte (si possible)
	$url_propre = isset($_SERVER['REDIRECT_url_propre']) ?
		$_SERVER['REDIRECT_url_propre'] :
		(isset($_ENV['url_propre']) ?
			$_ENV['url_propre'] :
			'');
	
	include_spip('inc/urls');
	$objets = urls_liste_objets();
	if ($url_propre
	AND preg_match(",^($objets|type_urls|404)$,",$fond)) {
		if ($GLOBALS['profondeur_url']<=0)
			$urls_anciennes = charger_fonction('propres','urls');
		else
			$urls_anciennes = charger_fonction('arbo','urls');
		$p = $urls_anciennes($url_propre,$fond,$contexte);
		$contexte = $p[0];
	}
	/* Fin du bloc compatibilite url-propres */

	/* Compatibilite urls-page */
	else if ($GLOBALS['profondeur_url']<=0
	AND preg_match(
	',[?/&]('.$objets.')[=]?([0-9]+),',
	$url, $r)) {
		$fond = $r[1];
		$contexte[id_table_objet($r[1])] = $r[2];
	}
	/* Fin compatibilite urls-page */

	return;
}

//
// le format de definition obsolete oblige a referencer explicitement les forums
// on prevoit leur inexistence possible par un test sur charger_fonction
// http://doc.spip.org/@generer_url_forum
function generer_url_forum($id, $show_thread=false) {
	if ($generer_url_externe = charger_fonction("generer_url_forum",'urls',true))
		return $generer_url_externe($id, $args, $ancre);
	return '';
}
 }
?>
