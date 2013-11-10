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

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('engine/textwheel');
// si une regle change et rend son cache non valide
// incrementer ce define au numero de commit concerne
// (inconsistence entre la wheel et l'inclusion php)
if (!defined('_WHEELS_VERSION')) define('_WHEELS_VERSION',68672);

//
// Definition des principales wheels de SPIP
//
if (!isset($GLOBALS['spip_wheels'])) {
	$GLOBALS['spip_wheels'] = array();
}

// Si le tableau des raccourcis existe déjà
if (!isset($GLOBALS['spip_wheels']['raccourcis']) OR !is_array($GLOBALS['spip_wheels']['raccourcis']))
	$GLOBALS['spip_wheels']['raccourcis'] = array(
		'spip/spip.yaml',
		'spip/spip-paragrapher.yaml'
	);
else
	$GLOBALS['spip_wheels']['raccourcis'] = array_merge(
		array(
			'spip/spip.yaml',
			'spip/spip-paragrapher.yaml'
		),
		$GLOBALS['spip_wheels']['raccourcis']
	);

if (test_espace_prive ())
	$GLOBALS['spip_wheels']['raccourcis'][] = 'spip/ecrire.yaml';

$GLOBALS['spip_wheels']['interdire_scripts'] = array(
	'spip/interdire-scripts.yaml'
);

$GLOBALS['spip_wheels']['echappe_js'] = array(
	'spip/echappe-js.yaml'
);

$GLOBALS['spip_wheels']['paragrapher'] = array(
	'spip/spip-paragrapher.yaml'
);

$GLOBALS['spip_wheels']['listes'] = array(
	'spip/spip-listes.yaml'
);

//
// Methode de chargement d'une wheel SPIP
//

class SPIPTextWheelRuleset extends TextWheelRuleSet {
	protected function findFile(&$file, $path=''){
		static $default_path;

		// absolute file path?
		if (file_exists($file))
			return $file;

		// file include with texwheels, relative to calling ruleset
		if ($path AND file_exists($f = $path.$file))
			return $f;

		return find_in_path($file,'wheels/');
	}

	public static function &loader($ruleset, $callback = '', $class = 'SPIPTextWheelRuleset') {

		# memoization
		# attention : le ruleset peut contenir apres loading des chemins relatifs
		# il faut donc que le cache depende du chemin courant vers la racine de SPIP
		$key = 'tw-'.md5(_WHEELS_VERSION."-".serialize($ruleset).$callback.$class._DIR_RACINE);

		# lecture du cache
		include_spip('inc/memoization');
		if (!function_exists('cache_get')) include_spip('inc/memoization-mini');
		if ((!defined('_VAR_MODE') OR _VAR_MODE!='recalcul')
		  AND $cacheruleset = cache_get($key))
			return $cacheruleset;

		# calcul de la wheel
		$ruleset = parent::loader($ruleset, $callback, $class);

		# ecriture du cache
		cache_set($key, $ruleset);

		return $ruleset;
	}
}


function tw_trig_purger($quoi){
	if ($quoi=='cache')
		purger_repertoire(_DIR_CACHE."wheels");
	return $quoi;
}

?>
