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


####### modifications possibles dans ecrire/mes_options
# on peut indiquer '.html' pour faire joli
define ('_terminaison_urls_page', '');
# ci-dessous, ce qu'on veut ou presque (de preference pas de '/')
# attention toutefois seuls '' et '=' figurent dans les modes de compatibilite
define ('_separateur_urls_page', '');
# on peut indiquer '' si on a installe le .htaccess
define ('_debut_urls_page', get_spip_script('./').'?');
#######


// http://doc.spip.org/@composer_url_page
function composer_url_page($page,$id, $args='', $ancre='') {
	$url = _debut_urls_page . $page . _separateur_urls_page
	  . $id . _terminaison_urls_page;
	if ($args) $args = strpos($url,'?') ? "&$args" : "?$args";
	return $url . $args . ($ancre ? "#$ancre" : '');
}

// http://doc.spip.org/@generer_url_article
function generer_url_article($id_article, $args='', $ancre='') {
	return composer_url_page('article', $id_article, $args, $ancre);
}

// http://doc.spip.org/@generer_url_rubrique
function generer_url_rubrique($id_rubrique, $args='', $ancre='') {
	return composer_url_page('rubrique', $id_rubrique, $args, $ancre);
}

// http://doc.spip.org/@generer_url_breve
function generer_url_breve($id_breve, $args='', $ancre='') {
	return composer_url_page('breve', $id_breve, $args, $ancre);
}

// http://doc.spip.org/@generer_url_mot
function generer_url_mot($id_mot, $args='', $ancre='') {
	return composer_url_page('mot', $id_mot, $args, $ancre);
}

// http://doc.spip.org/@generer_url_site
function generer_url_site($id_syndic, $args='', $ancre='') {
	return composer_url_page('site', $id_syndic, $args, $ancre);
}

// http://doc.spip.org/@generer_url_auteur
function generer_url_auteur($id_auteur, $args='', $ancre='') {
	return composer_url_page('auteur', $id_auteur, $args, $ancre);
}

// http://doc.spip.org/@generer_url_document
function generer_url_document($id_document, $args='', $ancre='') {
	include_spip('inc/documents');
	return generer_url_document_dist($id_document);
}

// http://doc.spip.org/@recuperer_parametres_url
function recuperer_parametres_url(&$fond, $url) {
	global $contexte;

	// Ce bloc gere les urls page et la compatibilite avec les "urls standard"
	if ($fond=='sommaire'){
		if (preg_match(
		',^[^?]*[?/](article|rubrique|breve|mot|site|auteur)(?:\.php3?)?.*?([0-9]+),',
		$url, $regs)) {
			$fond = $regs[1];
			if ($regs[1] == 'site') {
				if (!isset($contexte['id_syndic']))
					$contexte['id_syndic'] = $regs[2];
			} else {
				if (!isset($contexte['id_'.$fond]))
					$contexte['id_'.$fond] = $regs[2];
			}
	
			return;
		}
		/* Compatibilite urls-page avec formulaire en get !!! */
		else if (preg_match(
			',[?/&](article|breve|rubrique|mot|auteur|site)[=]?([0-9]+),',
			$url, $regs)) {
			$fond = $regs[1];
			if ($regs[1] == 'site') {
				if (!isset($contexte['id_syndic']))
					$contexte['id_syndic'] = $regs[2];
			} else {
				if (!isset($contexte['id_'.$fond]))
					$contexte['id_'.$fond] = $regs[2];
			}
			return;
		}
		/* Fin compatibilite urls-page */
	}


	/*
	 * Le bloc qui suit sert a faciliter les transitions depuis
	 * le mode 'urls-propres' vers les modes 'urls-standard/page' et 'url-html'
	 * Il est inutile de le recopier si vous personnalisez vos URLs
	 * et votre .htaccess
	 */
	// Si on est revenu en mode page, mais c'est une ancienne url_propre
	// on ne redirige pas, on assume le nouveau contexte (si possible)
	if (
		 (isset($_SERVER['REDIRECT_url_propre']) AND $url_propre = $_SERVER['REDIRECT_url_propre'])
	OR (isset($_ENV['url_propre']) AND $url_propre = $_ENV['url_propre'])
	AND preg_match(',^(article|breve|rubrique|mot|auteur|site)$,', $fond)) {
	  $url_propre = (preg_replace('/^[_+-]{0,2}(.*?)[_+-]{0,2}(\.html)?$/',
			'$1', $url_propre));
		$r = "spip_" . table_objet($fond);
		$id = id_table_objet($fond);
		$r = spip_query("SELECT $id AS id FROM $r WHERE url_propre = " . _q($url_propre));
		if ($r AND $r = sql_fetch($r))
			$contexte[$id] = $r['id'];
	}

	/* Fin du bloc compatibilite url-propres */
}

//
// URLs des forums
//

// http://doc.spip.org/@generer_url_forum
function generer_url_forum($id_forum, $args='', $ancre='') {
	include_spip('inc/forum');
	return generer_url_forum_dist($id_forum, $args, $ancre);
}
 }
?>
