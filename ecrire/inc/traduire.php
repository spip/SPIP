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

if (!defined("_ECRIRE_INC_VERSION")) return;

//
// Charger un fichier langue
//
// http://doc.spip.org/@chercher_module_lang
function chercher_module_lang($module, $lang = '') {
	if ($lang)
		$lang = '_'.$lang;

	// 1) dans un repertoire nomme lang/ se trouvant sur le chemin
	if ($f = include_spip('lang/'.$module.$lang, false))
		return $f;

	// 2) directement dans le chemin (old style)
	return include_spip($module.$lang, false);
}

// http://doc.spip.org/@charger_langue
function charger_langue($lang, $module = 'spip') {
	if ($lang AND $fichier_lang = chercher_module_lang($module, $lang)) {
		$GLOBALS['idx_lang']='i18n_'.$module.'_'.$lang;
		include_once($fichier_lang);
	} else {
		// si le fichier de langue du module n'existe pas, on se rabat sur
		// la langue par defaut du site -- et au pire sur le francais, qui
		// *par definition* doit exister, et on copie le tableau dans la
		// var liee a la langue
		$l = $GLOBALS['meta']['langue_site'];
		if (!$fichier_lang = chercher_module_lang($module, $l))
			$fichier_lang = chercher_module_lang($module, 'fr');

		if ($fichier_lang) {
			$GLOBALS['idx_lang']='i18n_'.$module.'_' .$l;
			include($fichier_lang);
			$GLOBALS['i18n_'.$module.'_'.$lang]
				= &$GLOBALS['i18n_'.$module.'_'.$l];
			#spip_log("module de langue : ${module}_$l.php");
		}
	}
}

//
// Surcharger le fichier de langue courant avec un autre (tordu, hein...)
//
// http://doc.spip.org/@surcharger_langue
function surcharger_langue($fichier) {

	$idx_lang_normal = $GLOBALS['idx_lang'];
	$idx_lang_surcharge = $GLOBALS['idx_lang'].'_temporaire';
	$GLOBALS['idx_lang'] = $idx_lang_surcharge;
	include($fichier);
	if (is_array($GLOBALS[$idx_lang_surcharge])) {
		$GLOBALS[$idx_lang_normal] = array_merge(
			$GLOBALS[$idx_lang_normal],
			$GLOBALS[$idx_lang_surcharge]
		);
	}
	unset ($GLOBALS[$idx_lang_surcharge]);
	$GLOBALS['idx_lang'] = $idx_lang_normal;
}

//
// Traduire une chaine internationalisee
//
// http://doc.spip.org/@inc_traduire_dist
function inc_traduire_dist($ori, $lang) {

	static $deja_vu = array();
  
	if (isset($deja_vu[$lang][$ori])) {
	      return $deja_vu[$lang][$ori];
	}

	// modules demandes explicitement

	if (preg_match(",^([a-z0-9/]+):(.*)$,", $ori, $regs)) {
			$modules = explode("/",$regs[1]);
			$code = $regs[2];
	} else 	{$modules = array('spip', 'ecrire'); $code = $ori;}

	$text = '';
	// parcourir tous les modules jusqu'a ce qu'on trouve
	foreach ($modules as $module) {
		$var = "i18n_".$module."_".$lang;
		if (empty($GLOBALS[$var])) {
			charger_langue($lang, $module);

			// surcharge perso -- on cherche (lang/)local_xx.php ...
			if ($f = chercher_module_lang('local', $lang))
				surcharger_langue($f);
			// ... puis (lang/)local.php
			if ($f = chercher_module_lang('local'))
				surcharger_langue($f);
		}
		if (isset($GLOBALS[$var][$code])) {
			$text = $GLOBALS[$var][$code];
			break;
		}
	}

	// filet pour traduction pas finies 
	if (($lang<>'fr') AND ereg("^<(NEW|MODIF)>", $text))
		  $text = inc_traduire_dist($ori, 'fr');
	$deja_vu[$lang][$code] = $text;

	return $text;
}
?>
