<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

//
// Charger un fichier langue
//
// http://doc.spip.org/@chercher_module_lang
function chercher_module_lang($module, $lang = '') {
	if ($lang)
		$lang = '_'.$lang;

	// 1) dans un repertoire nomme lang/ se trouvant sur le chemin
	if ($f = find_in_path($module.$lang.'.php', 'lang/'))
		return $f;

	// 2) directement dans le chemin (old style, uniquement pour local)
	return ($module == 'local')
		? find_in_path('local'.$lang. '.php')
		: '';
}

// http://doc.spip.org/@charger_langue
function charger_langue($lang, $module = 'spip') {
	if ($lang AND $fichier_lang = chercher_module_lang($module, $lang)) {
		$GLOBALS['idx_lang']='i18n_'.$module.'_'.$lang;
		include($fichier_lang);
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
	static $surcharges = array();
	if (!isset($GLOBALS['idx_lang'])) return;
	if (!isset($surcharges[$fichier])) {
		$idx_lang_normal = $GLOBALS['idx_lang'];
		$GLOBALS['idx_lang'] = $GLOBALS['idx_lang'].'@temporaire';
		include($fichier);
		$surcharges[$fichier] = $GLOBALS[$GLOBALS['idx_lang']];
		unset ($GLOBALS[$GLOBALS['idx_lang']]);
		$GLOBALS['idx_lang'] = $idx_lang_normal;
	}
	if (is_array($surcharges[$fichier])) {
		$GLOBALS[$GLOBALS['idx_lang']] = array_merge(
			$GLOBALS[$GLOBALS['idx_lang']],
			$surcharges[$fichier]
		);
	}
}

//
// Traduire une chaine internationalisee
//
// http://doc.spip.org/@inc_traduire_dist
function inc_traduire_dist($ori, $lang) {

	static $deja_vu = array();
  
	if (isset($deja_vu[$lang][$ori]))
		return $deja_vu[$lang][$ori];

	// modules demandes explicitement <xxx/yyy/zzz:code>
	if (strpos($ori,':')) {
		list($modules,$code) = explode(':',$ori,2);
		$modules = explode('/', $modules);
	} else {
		$modules = array('spip', 'ecrire');
		$code = $ori;
	}

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

	// Retour aux sources si la chaine est absente dans la langue cible ;
	// on essaie d'abord la langue du site, puis a defaut la langue fr
	if (!strlen($text)
	AND $lang !== 'fr') {
		if ($lang !== $GLOBALS['meta']['langue_site'])
			$text = inc_traduire_dist($ori, $GLOBALS['meta']['langue_site']);
		else 
			$text = inc_traduire_dist($ori, 'fr');
	}

	// Supprimer la mention <NEW> ou <MODIF>
	if (substr($text,0,1) === '<')
		$text = str_replace(array('<NEW>', '<MODIF>'), array(), $text);

	// Si on n'est pas en utf-8, la chaine peut l'etre...
	// le cas echeant on la convertit en entites html &#xxx;
	if ($GLOBALS['meta']['charset'] !== 'utf-8'
	AND preg_match(',[\x7f-\xff],S', $text)) {
		include_spip('inc/charsets');
		$text = charset2unicode($text,'utf-8');
	}

	$deja_vu[$lang][$ori] = $text;

	return $text;
}
?>
