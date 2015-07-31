<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;


function formulaires_configurer_plugin_charger_dist($plugin, $form='') {

	$get_infos = charger_fonction('get_infos','plugins');
	$infos = $get_infos($plugin);
	if (!isset($infos['prefix'])) return array('erreur' => "$plugin ?");
	$meta = isset($infos['meta']) ? $infos['meta']
	  : ($infos['prefix'] . '_metas');
	return $GLOBALS[$meta];
}

#function formulaires_configurer_plugin_verifier_dist(){
#  spip_log("formulaires_configurer_plugin_verifier_dist $plugin, $form");
#  return array();
#}

// version amelioree de la RegExp de cfg_formulaire.
define('_EXTRAIRE_SAISIES', 
	'#<(?:(select|textarea)|input type=["\'](text|password|checkbox|radio|hidden|file)["\']) name=["\'](\w+)(\[\w*\])?["\'](?: class=["\']([^\'"]*)["\'])?( multiple=)?[^>]*?>#ims');


function formulaires_configurer_plugin_traiter_dist($plugin, $form=''){

	$get_infos = charger_fonction('get_infos','plugins');
	$infos = $get_infos($plugin);
	if (!isset($infos['prefix'])) return array('erreur' => "$plugin ?");
	$prefix = $infos['prefix'];
	$meta = isset($infos['meta']) ? $infos['meta'] : ($prefix . '_metas');

	if (!$form) $form = 'configurer_' . $prefix;
	$f = find_in_path($form.'.' . _EXTENSION_SQUELETTES, 'formulaires/');
	$formulaire = $f ? file_get_contents($f) : '';
	if (preg_match_all(_EXTRAIRE_SAISIES, $formulaire, $r, PREG_SET_ORDER)) {
		foreach($r as $regs) {
			$k = $regs[3];
			$v = _request($k);
			ecrire_meta($k, $v, 'oui', $meta);
		}
	}
	return array('redirect' => generer_url_ecrire($prefix));
}
?>
