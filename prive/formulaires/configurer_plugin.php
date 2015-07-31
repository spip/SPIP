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

	$infos = formulaires_configurer_plugin_infos($plugin, $form);
	if (!is_array($infos)) return $infos;
	return $GLOBALS[$infos['meta']];
}

function formulaires_configurer_plugin_verifier_dist($plugin, $form='') {
	$infos = formulaires_configurer_plugin_infos($plugin, $form);
	if (!is_array($infos)) return $infos;
	$formulaire = $infos['configurer_plugin'];
	$f = charger_fonction('verifier', "formulaires/$formulaire", true);
	return $f ? $f($plugin, $form) : array();
}

function formulaires_configurer_plugin_traiter_dist($plugin, $form=''){

	$infos = formulaires_configurer_plugin_infos($plugin, $form);
	if (!is_array($infos)) return $infos;
	$form = $infos['configurer_plugin'];
	$meta = $infos['meta'];
	foreach (formulaires_configurer_plugin_recense($form) as $regs) {
		$k = $regs[3];
		ecrire_meta($k, _request($k), 'oui', $meta);
	}
	return array('redirect' => generer_url_ecrire($infos['prefix']));
}

// version amelioree de la RegExp de cfg_formulaire.
define('_EXTRAIRE_SAISIES', 
	'#<(?:(select|textarea)|input type=["\'](text|password|checkbox|radio|hidden|file)["\']) name=["\'](\w+)(\[\w*\])?["\'](?: class=["\']([^\'"]*)["\'])?( multiple=)?[^>]*?>#ims');

// determiner la liste des noms des saisies d'un formulaire
// (a refaire avec SAX)
function formulaires_configurer_plugin_recense($form)
{
	$f = find_in_path($form.'.' . _EXTENSION_SQUELETTES, 'formulaires/');
	$f = $f ? file_get_contents($f) : '';
	if (preg_match_all(_EXTRAIRE_SAISIES, $f, $r, PREG_SET_ORDER))
		return $r;
	return array();

}

// Recuperer la version compilee de plugin.xml et normaliser
function formulaires_configurer_plugin_infos($plugin, $form=''){

	$get_infos = charger_fonction('get_infos','plugins');
	$infos = $get_infos($plugin);
	if (!is_array($infos) OR !isset($infos['prefix']))
		return _T('erreur_plugin_nom_manquant');
	$prefix = $infos['prefix'];
	$infos['configurer_plugin'] = $form ? $form : "configurer_$prefix";
	if (!isset($infos['meta'])) $infos['meta'] = ($prefix . '_metas');
	return $infos;
}
?>
