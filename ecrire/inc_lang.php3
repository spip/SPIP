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
 	if ($lang && ereg(",$lang,", ",$all_langs,")) {
		$GLOBALS['spip_lang'] = $lang;
		return true;
	}
	return false;
}

//
// Regler la langue courante selon les infos envoyees par le brouteur
//
function regler_langue_navigateur() {
	global $HTTP_SERVER_VARS, $HTTP_COOKIE_VARS;

	if ($cookie_lang = $HTTP_COOKIE_VARS['spip_lang']) {
		if (changer_langue($cookie_lang)) return $cookie_lang;
	}

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
	//$retour = _T("langue_".$lang);
	include_ecrire("inc_liste_lang.php3");
	$retour = $GLOBALS['codes_langues'][$lang];
	if (!$retour) $retour = $lang;
	return $retour;
}

//
// Selection de langue haut niveau
//
function utiliser_langue_visiteur() {
	changer_langue('fr');
	if (!regler_langue_navigateur())
		changer_langue(lire_meta('langue_site'));
	if ($GLOBALS['prefs']['spip_lang'])
		changer_langue($GLOBALS['prefs']['spip_lang']);
}

function utiliser_langue_site() {
	changer_langue('fr');
	changer_langue(lire_meta('langue_site'));
}

//
// Initialisation
//
function init_langues() {
	if ($GLOBALS['flag_ecrire'] || !lire_meta('langues_proposees')) {
		$d = opendir($GLOBALS['flag_ecrire'] ? "lang" : "ecrire/lang");
		while ($f = readdir($d)) {
			if (ereg('^spip_([a-z]{2,3})\.php3?$', $f, $regs))
				$all_langs[] = $regs[1];
		}
		closedir($d);
		$GLOBALS['all_langs'] = join(',', $all_langs);
		ecrire_meta('langues_proposees', $GLOBALS['all_langs']);
	} else
		$GLOBALS['all_langs'] = lire_meta('langues_proposees');
}

init_langues();
utiliser_langue_site();

?>
