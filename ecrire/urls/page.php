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

if (!defined("_ECRIRE_INC_VERSION")) return; // securiser

####### modifications possibles dans ecrire/mes_options
# on peut indiquer '.html' pour faire joli
define ('_terminaison_urls_page', '');
# ci-dessous, ce qu'on veut ou presque (de preference pas de '/')
# attention toutefois seuls '' et '=' figurent dans les modes de compatibilite
define ('_separateur_urls_page', '');
# on peut indiquer '' si on a installe le .htaccess
define ('_debut_urls_page', get_spip_script('./').'?');
#######


// http://doc.spip.org/@_generer_url_page
function _generer_url_page($type,$id, $args='', $ancre='') {

	if ($type == 'forum') {
		include_spip('inc/forum');
		return generer_url_forum_dist($id, $args, $ancre);
	}

	if ($type == 'document') {
		include_spip('inc/documents');
		return generer_url_document_dist($id, $args, $ancre);
	}

	$url = _debut_urls_page . $type . _separateur_urls_page
	  . $id . _terminaison_urls_page;

	if ($args) $args = strpos($url,'?') ? "&$args" : "?$args";
	return $url . $args . ($ancre ? "#$ancre" : '');
}

// retrouve le fond et les parametres d'une URL abregee
// http://doc.spip.org/@urls_page_dist
function urls_page_dist($i, &$entite, $args='', $ancre='')
{
	global $contexte;

	if (is_numeric($i))
		return _generer_url_page($entite, $i, $args, $ancre);

	$url = $i;

	// Ce bloc gere les urls page et la compatibilite avec les "urls standard"
	if ($entite=='sommaire'){
		if (preg_match(
		',^[^?]*[?/](article|rubrique|breve|mot|site|auteur)(?:\.php3?)?.*?([0-9]+),',
		$url, $regs)) {
			$entite = $regs[1];
			if ($regs[1] == 'site') {
				if (!isset($contexte['id_syndic']))
					$contexte['id_syndic'] = $regs[2];
			} else {
				if (!isset($contexte['id_'.$entite]))
					$contexte['id_'.$entite] = $regs[2];
			}
	
			return;
		}
		/* Compatibilite urls-page avec formulaire en get !!! */
		else if (preg_match(
			',[?/&](article|breve|rubrique|mot|auteur|site)[=]?([0-9]+),',
			$url, $regs)) {
			$entite = $regs[1];
			if ($regs[1] == 'site') {
				if (!isset($contexte['id_syndic']))
					$contexte['id_syndic'] = $regs[2];
			} else {
				if (!isset($contexte['id_'.$entite]))
					$contexte['id_'.$entite] = $regs[2];
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
	AND preg_match(',^(article|breve|rubrique|mot|auteur|site|type_urls)$,', $entite)) {
		$url_propre = (preg_replace('/^[_+-]{0,2}(.*?)[_+-]{0,2}(\.html)?$/',
			'$1', $url_propre));
		$r = sql_fetsel("id_objet,type", "spip_urls", "url=" . _q($url_propre));
		if ($r) {
			$entite = ($r['type'] == 'syndic') ?  'site' : $r['type'];
			$contexte[id_table_objet($entite)] = $r['id_objet'];
		}
	}

	/* Fin du bloc compatibilite url-propres */
}
?>
