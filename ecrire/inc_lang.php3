<?php

// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_LANG")) return;
define("_ECRIRE_INC_LANG", "1");



//
// Charger un fichier langue
//
function charger_langue($lang) {
	global $dir_ecrire;
	include_ecrire ("lang/spip_$lang.php3");
}

//
// Changer la langue courante
//
function changer_langue($lang) {
	global $all_langs;
 	if (ereg(",$lang,", ",$all_langs,")) {
		$GLOBALS['spip_lang'] = $lang;
		return true;
	}
	return false;
}


//
// Traduire une chaine internationalisee
//
function traduire_chaine($text, $args) {
	global $spip_lang;
	$var = "i18n_$spip_lang";
	if (!$GLOBALS[$var]) charger_langue($spip_lang);
	$text = $GLOBALS[$var][$text];
	if (!is_array($args)) return $text;

	if ($GLOBALS['flag_str_replace']) {
		while (list($name, $value) = each($args))
			$text = str_replace ("@$name@", $value, $text);
	}
	else {
		while (list($name, $value) = each($args))
			$text = ereg_replace ("@$name@", $value, $text);
	}
	return $text;
}


//
// Initialisation
//
$GLOBALS['all_langs'] = 'fr,zg';
$GLOBALS['spip_lang'] = 'fr';
changer_langue(lire_meta('langue_site'));

?>
