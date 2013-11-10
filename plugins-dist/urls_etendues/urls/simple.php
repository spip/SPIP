<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

# donner un exemple d'url pour le formulaire de choix
define('URLS_SIMPLE_EXEMPLE', 'spip.php?page=article&id_article=12');

####### modifications possibles dans ecrire/mes_options
# on peut indiquer '.html' pour faire joli
define ('_terminaison_urls_simple', '');
define ('_debut_urls_simple', get_spip_script('./').'?'._SPIP_PAGE.'=');
#######


function _generer_url_simple($type,$id, $args='', $ancre='') {

	if ($generer_url_externe = charger_fonction("generer_url_$type",'urls',true)) {
		$url = $generer_url_externe($id, $args, $ancre);
		if (NULL != $url) return $url;
	}

	$url = _debut_urls_simple . $type
	  . "&" . id_table_objet($type) . "="
	  . $id . _terminaison_urls_page;

	if ($args) $args = strpos($url,'?') ? "&$args" : "?$args";
	return _DIR_RACINE . $url . $args . ($ancre ? "#$ancre" : '');
}

// retrouve le fond et les parametres d'une URL abregee
// le contexte deja existant est fourni dans args sous forme de tableau ou query string
// http://doc.spip.org/@urls_page_dist
function urls_simple_dist($i, &$entite, $args='', $ancre='')
{
	if (is_numeric($i)){
		include_spip('urls/page');
		return _generer_url_simple($entite, $i, $args, $ancre);
	}
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

	if ($type = _request(_SPIP_PAGE)
	  AND $_id = id_table_objet($type)
	  AND $id = _request($_id)){
		$contexte[$_id] = $id;
		return array($contexte, $type, null, $type);
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
