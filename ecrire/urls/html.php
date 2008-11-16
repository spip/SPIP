<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/*

- Comment utiliser ce jeu d'URLs ?

Recopiez le fichier "htaccess.txt" du repertoire de base du site SPIP sous
le sous le nom ".htaccess" (attention a ne pas ecraser d'autres reglages
que vous pourriez avoir mis dans ce fichier) ; si votre site est en
"sous-repertoire", vous devrez aussi editer la ligne "RewriteBase" ce fichier.
Les URLs definies seront alors redirigees vers les fichiers de SPIP.

Dans les pages de configuration, choisissez 'html' comme type d'url

SPIP calculera alors ses liens sous la forme "article123.html".

Note : si le fichier htaccess.txt se revele trop "puissant", car trop
generique, et conduit a des problemes (en lien par exemple avec d'autres
applications installees dans votre repertoire, a cote de SPIP), vous
pouvez l'editer pour ne conserver que la partie concernant les URLS 'html'.

*/

if (!defined("_ECRIRE_INC_VERSION")) return; // securiser

define('URLS_HTML_EXEMPLE', 'article12.html');

// http://doc.spip.org/@_generer_url_html
function _generer_url_html($type, $id, $args='', $ancre='') {

	if ($type == 'forum') {
		include_spip('inc/forum');
		return generer_url_forum_dist($id, $args, $ancre);
	}

	if ($type == 'document') {
		include_spip('inc/documents');
		return generer_url_document_dist($id, $args, $ancre);
	}

	return $type . $id . '.html' . ($args ? "?$args" : '') .($ancre ? "#$ancre" : '');
}

// retrouver les parametres d'une URL dite "html"
// http://doc.spip.org/@urls_html_dist
function urls_html_dist($i, &$entite, $args='', $ancre='') {
	global $contexte;

	if (is_numeric($i))
		return _generer_url_html($entite, $i, $args, $ancre);

	// traiter les injections du type domaine.org/spip.php/cestnimportequoi/ou/encore/plus/rubrique23
	if ($GLOBALS['profondeur_url']>0){
		$entite = '404';
	}
	$url = $i;

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
	if ($url_propre AND preg_match(',^(article|breve|rubrique|mot|auteur|site|type_urls|404)$,', $entite)) {
		if ($GLOBALS['profondeur_url']<=0)
			$urls_anciennes = charger_fonction('propres','urls');
		else
			$urls_anciennes = charger_fonction('arbo','urls');
		$urls_anciennes($url_propre,$entite);
	}
	/* Fin du bloc compatibilite url-propres */
}

?>
