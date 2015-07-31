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

define('URLS_PAGE_EXEMPLE', 'spip.php?article12');

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

	if ($generer_url_externe = charger_fonction("generer_url_$type",'urls',true)) {
		$url = $generer_url_externe($id, $args, $ancre);
		if (NULL != $url) return $url;
	}

	$url = _debut_urls_page . $type . _separateur_urls_page
	  . $id . _terminaison_urls_page;

	if ($args) $args = strpos($url,'?') ? "&$args" : "?$args";
	return _DIR_RACINE . $url . $args . ($ancre ? "#$ancre" : '');
}

// retrouve le fond et les parametres d'une URL abregee
// le contexte deja existant est fourni dans args sous forme de tableau ou query string
// http://doc.spip.org/@urls_page_dist
function urls_page_dist($i, &$entite, $args='', $ancre='')
{
	if (is_numeric($i))
		return _generer_url_page($entite, $i, $args, $ancre);

	// traiter les injections du type domaine.org/spip.php/cestnimportequoi/ou/encore/plus/rubrique23
	if ($GLOBALS['profondeur_url']>0 AND $entite=='sommaire'){
		return array(array(),'404');
	}

	// voir s'il faut recuperer le id_* implicite et les &debut_xx;
	if (is_array($args))
		$contexte = $args;
	else
		parse_str($args,$contexte);
	include_spip('inc/urls');
	$r = nettoyer_url_page($i, $contexte);
	if ($r) {
		array_pop($r); // nettoyer_url_page renvoie un argument de plus inutile ici
		return $r;
	}

	/*
	 * Le bloc qui suit sert a faciliter les transitions depuis
	 * le mode 'urls-propres' vers les modes 'urls-standard' et 'url-html'
	 * Il est inutile de le recopier si vous personnalisez vos URLs
	 * et votre .htaccess
	 */
	// Si on est revenu en mode html, mais c'est une ancienne url_propre
	// on ne redirige pas, on assume le nouveau contexte (si possible)
	$url = $i;
	$url_propre = isset($url)
		? $url
		: (isset($_SERVER['REDIRECT_url_propre'])
			? $_SERVER['REDIRECT_url_propre']
			: (isset($_ENV['url_propre'])
				? $_ENV['url_propre']
				: ''
				));
	if ($url_propre) {
		if ($GLOBALS['profondeur_url']<=0)
			$urls_anciennes = charger_fonction('propres','urls',true);
		else
			$urls_anciennes = charger_fonction('arbo','urls',true);
		return $urls_anciennes?$urls_anciennes($url_propre, $entite, $contexte):'';
	}
	/* Fin du bloc compatibilite url-propres */
}

?>
