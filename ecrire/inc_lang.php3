<?php

// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_LANG")) return;
define("_ECRIRE_INC_LANG", "1");



//
// Charger un fichier langue
//
function charger_langue($lang) {
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
// Regler la langue courante selon les infos envoyees par le brouteur
//
function regler_langue_navigateur() {
	global $HTTP_SERVER_VARS;
	$accept_langs = explode(',', $HTTP_SERVER_VARS['HTTP_ACCEPT_LANGUAGE']);
	if (is_array($accept_langs)) {
		while(list(, $s) = each($accept_langs)) {
			if (eregi('^([a-z]{2,3})(-[a-z]{2,3})?(;q=[0-9.]+)?$', trim($s), $r)) {
				$lang = strtolower($r[1]);
				if (changer_langue($lang)) return $lang;
			}
		}
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


function traduire_nom_langue($lang) {
	$retour = _T("langue_".$lang);
	
	if (strlen($retour) == 0) {
		include_ecrire("inc_liste_lang.php3");
		$retour = $GLOBALS['codes_langues'][$lang];
	}
	
	if (strlen($retour) == 0) {
		$retour = $lang;
	}
	
	return $retour;
}


//
// Initialisation
//
$GLOBALS['langues_ok'] = 'fr,en';

$GLOBALS['all_langs'] = lire_meta('sauver_langues_ok');
if (strlen($GLOBALS['all_langs']) == 0) $GLOBALS['all_langs'] = $GLOBALS['langues_ok'];

$GLOBALS['spip_lang'] = 'fr';
if (!regler_langue_navigateur())
	changer_langue(lire_meta('langue_site'));
if ($GLOBALS['prefs']['spip_lang'])
	changer_langue($GLOBALS['prefs']['spip_lang']);




?>
