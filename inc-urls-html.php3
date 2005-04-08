<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/*

- Comment utiliser ce jeu d'URLs ?

Il faut recopier le fichier htaccess-propres.txt sous le nom .htaccess
dans le repertoire de base du site SPIP (attention a ne pas ecraser
d'autres reglages que vous pourriez avoir mis dans ce fichier) ; si votre site
est en "sous-repertoire", vous devrez editer la ligne "RewriteBase" ce fichier.

definissez ensuite dans ecrire/mes_options.php3 :
	type_urls = 'html';

Note : le fichier htaccess-propres.txt est compatible avec les URLS 'html' ;
toutefois si htaccess-propres.txt se revele trop "puissant", car trop generique,
et conduit a des problemes (en lien par exemple avec d'autres applications
installees dans votre repertoire, a cote de SPIP), vous pouvez utiliser a la
place le fichier htaccess-html.txt, qui est suffisant pour ce type d'urls.

*/

// executer une seule fois
if (defined("_INC_URLS2")) return;
define("_INC_URLS2", "1");

function generer_url_article($id_article) {
	return "article$id_article.html";
}

function generer_url_rubrique($id_rubrique) {
	return "rubrique$id_rubrique.html";
}

function generer_url_breve($id_breve) {
	return "breve$id_breve.html";
}

function generer_url_mot($id_mot) {
	return "mot$id_mot.html";
}

function generer_url_auteur($id_auteur) {
	return "auteur$id_auteur.html";
}

function generer_url_document($id_document) {
	if (intval($id_document) <= 0)
		return '';
	if ((lire_meta("creer_htaccess")) == 'oui')
		return "spip_acces_doc.php3?id_document=$id_document";
	if ($row = @spip_fetch_array(spip_query("SELECT fichier FROM spip_documents WHERE id_document = $id_document")))
		return ($row['fichier']);
	return '';
}

function recuperer_parametres_url($fond, $url) {
	global $contexte;


	/*
	 * Le bloc qui suit sert a faciliter les transitions depuis
	 * le mode 'urls-propres' vers les modes 'urls-standard' et 'url-html'
	 * Il est inutile de le recopier si vous personnalisez vos URLs
	 * et votre .htaccess
	 */
	// Si on est revenu en mode html, mais c'est une ancienne url_propre
	// on ne redirige pas, on assume le nouveau contexte (si possible)
	if ($url_propre = $GLOBALS['_SERVER']['REDIRECT_url_propre']
	OR $url_propre = $GLOBALS['HTTP_ENV_VARS']['url_propre']
	AND preg_match(',^(article|breve|rubrique|mot|auteur)$,', $fond)) {
		$url_propre = preg_replace('/^[_+-]{0,2}(.*?)[_+-]{0,2}(\.html)?$/',
			'$1', $url_propre);
		if ($r = spip_query("SELECT id_$fond AS id FROM spip_".$fond."s
		WHERE url_propre = '".addslashes($url_propre)."'")
		AND $t = spip_fetch_array($r))
			$contexte["id_$fond"] = $t['id'];
	}
	/* Fin du bloc compatibilite url-propres */


	return;
}


//
// URLs des forums
//

function generer_url_forum($id_forum, $show_thread=false) {
	include_ecrire('inc_forum.php3');
	return generer_url_forum_dist($id_forum, $show_thread);
}

?>
