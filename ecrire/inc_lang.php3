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
	global $all_langs, $spip_lang_rtl;
 	if ($lang && ereg(",$lang,", ",$all_langs,")) {
		$GLOBALS['spip_lang'] = $lang;
		if ($lang == 'ar')
			$spip_lang_rtl = '_rtl';
		else
			$spip_lang_rtl = '';
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
	$codes_langues = array(
	'aa' => "Afar",
	'ab' => "Abkhazian",
	'af' => "Afrikaans",
	'am' => "Amharic",
	'ar' => "&#1575;&#1604;&#1593;&#1585;&#1576;&#1610;&#1577;",
	'as' => "Assamese",
	'ay' => "Aymara",
	'az' => "&#1040;&#1079;&#1241;&#1088;&#1073;&#1072;&#1112;&#1209;&#1072;&#1085;",
	'ba' => "Bashkir",
	'be' => "&#1041;&#1077;&#1083;&#1072;&#1088;&#1091;&#1089;&#1082;&#1110;",
	'bg' => "&#1073;&#1098;&#1083;&#1075;&#1072;&#1088;&#1089;&#1082;&#1080;",
	'bh' => "Bihari",
	'bi' => "Bislama",
	'bn' => "Bengali; Bangla",
	'bo' => "Tibetan",
	'br' => "Breton",
	'ca' => "catal&#224;",
	'co' => "Corsican",
	'cs' => "&#269;e&#353;tina",
	'cy' => "Welsh",
	'da' => "dansk",
	'de' => "Deutsch",
	'dz' => "Bhutani",
	'el' => "&#949;&#955;&#955;&#951;&#957;&#953;&#954;&#940;",
	'en' => "English",
	'eo' => "Esperanto",
	'es' => "Espa&#241;ol",
	'et' => "eesti",
	'eu' => "euskara",
	'fa' => "&#1601;&#1575;&#1585;&#1587;&#1609;",
	'fi' => "suomi",
	'fj' => "Fiji",
	'fo' => "f&#248;royskt",
	'fr' => "fran&#231;ais",
	'fy' => "Frisian",
	'ga' => "Irish",
	'gd' => "Scots Gaelic",
	'gl' => "Galician",
	'gn' => "Guarani",
	'gu' => "Gujarati",
	'ha' => "Hausa",
	'he' => "&#1506;&#1489;&#1512;&#1497;&#1514;",
	'hi' => "&#2361;&#2367;&#2306;&#2342;&#2368;",
	'hr' => "hrvatski",
	'hu' => "magyar",
	'hy' => "Armenian",
	'ia' => "Interlingua",
	'id' => "Bahasa Indonesia",
	'ie' => "Interlingue",
	'ik' => "Inupiak",
	'is' => "&#237;slenska",
	'it' => "italiano",
	'iu' => "Inuktitut",
	'ja' => "&#26085;&#26412;&#35486;",
	'jw' => "Javanese",
	'ka' => "&#4325;&#4304;&#4320;&#4311;&#4323;&#4314;&#4312;",
	'kk' => "&#1178;&#1072;&#1079;&#1072;&#1097;b",
	'kl' => "Greenlandic",
	'km' => "Cambodian",
	'kn' => "Kannada",
	'ko' => "&#54620;&#44397;&#50612;",
	'ks' => "Kashmiri",
	'ku' => "Kurdish",
	'ky' => "Kirghiz",
	'la' => "Latin",
	'ln' => "Lingala",
	'lo' => "Laothian",
	'lt' => "lietuvi&#371;",
	'lv' => "latvie&#353;u",
	'mg' => "Malagasy",
	'mi' => "Maori",
	'mk' => "&#1084;&#1072;&#1082;&#1077;&#1076;&#1086;&#1085;&#1089;&#1082;&#1080; &#1112;&#1072;&#1079;&#1080;&#1082;",
	'ml' => "Malayalam",
	'mn' => "Mongolian",
	'mo' => "Moldavian",
	'mr' => "&#2350;&#2352;&#2366;&#2336;&#2368;",
	'ms' => "Bahasa Malaysia",
	'mt' => "Maltese",
	'my' => "Burmese",
	'na' => "Nauru",
	'ne' => "Nepali",
	'nl' => "Nederlands",
	'no' => "norsk",
	'oc' => "Occitan",
	'om' => "(Afan) Oromo",
	'or' => "Oriya",
	'pa' => "Punjabi",
	'pl' => "polski",
	'ps' => "Pashto, Pushto",
	'pt' => "Portugu&#234;s",
	'qu' => "Quechua",
	'rm' => "Rhaeto-Romance",
	'rn' => "Kirundi",
	'ro' => "rom&#226;n&#259;",
	'ru' => "&#1088;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081;",
	'rw' => "Kinyarwanda",
	'sa' => "&#2360;&#2306;&#2360;&#2381;&#2325;&#2371;&#2340;",
	'sd' => "Sindhi",
	'sg' => "Sangho",
	'sh' => "Serbo-Croatian",
	'si' => "Sinhalese",
	'sk' => "sloven&#269;ina",
	'sl' => "slovenski",
	'sm' => "Samoan",
	'sn' => "Shona",
	'so' => "Somali",
	'sq' => "shqipe",
	'sr' => "&#1089;&#1088;&#1087;&#1089;&#1082;&#1080;",
	'ss' => "Siswati",
	'st' => "Sesotho",
	'su' => "Sundanese",
	'sv' => "svenska",
	'sw' => "Kiswahili",
	'ta' => "&#2980;&#2990;&#3007;&#2996;&#3021;",
	'te' => "Telugu",
	'tg' => "Tajik",
	'th' => "&#3652;&#3607;&#3618;",
	'ti' => "Tigrinya",
	'tk' => "Turkmen",
	'tl' => "Tagalog",
	'tn' => "Setswana",
	'to' => "Tonga",
	'tr' => "T&#252;rk&#231;e",
	'ts' => "Tsonga",
	'tt' => "&#1058;&#1072;&#1090;&#1072;&#1088;",
	'tw' => "Twi",
	'ug' => "Uighur",
	'uk' => "&#1091;&#1082;&#1088;&#1072;&#1111;&#1085;&#1100;&#1089;&#1082;&#1072;",
	'ur' => "&#1649;&#1585;&#1583;&#1608;",
	'uz' => "U'zbek",
	'vi' => "Ti&#234;&#769;ng Vi&#234;&#803;t Nam",
	'vo' => "Volapuk",
	'wo' => "Wolof",
	'xh' => "Xhosa",
	'yi' => "Yiddish",
	'yo' => "Yoruba",
	'za' => "Zhuang",
	'zh' => "&#20013;&#25991;",
	'zu' => "Zulu");
	
	$r = $codes_langues[$lang];
	if (!$r) $r = $lang;
	return $r;
}

//
// Afficher un menu de selection de langue
//
function menu_langues() {
		$lien = $GLOBALS['clean_link'];
		$lien->delVar('var_lang');
		$lien = $lien->getUrl();

		$amp = (strpos(' '.$lien,'?') ? '&' : '?');

		$ret = "<form action='$lien' method='get' style='margin:0px; padding:0px;'>";
		$ret .= "\n<select name='var_lang' class='verdana1' style='background-color: $couleur_foncee; color: white;' onChange=\"document.location.href='". $lien . $amp."var_lang='+this.options[this.selectedIndex].value\">\n";
		$langues = explode(',', $GLOBALS['all_langs']);
		while (list(,$l) = each ($langues)) {
			if ($l == $GLOBALS['spip_lang']) $selected = " selected";
			else $selected = "";

			$ret .= "<option value='$l'$selected>".traduire_nom_langue($l)."</option>\n";
		}
		$ret .= "</select>\n";
		$ret .= "<noscript><INPUT TYPE='submit' NAME='Valider' VALUE='>>' class='verdana1' style='background-color: $couleur_foncee; color: white; height: 19px;'></noscript>";
		$ret .= "</form>";
		return $ret;
}

// menu dans l'espace public
function gerer_menu_langues() {
	global $var_lang;
	if ($var_lang) {
		if (changer_langue($var_lang)) {
			spip_setcookie('spip_lang', $var_lang, time() + 365 * 24 * 3600);

		}
	}
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
	if (defined("_ECRIRE_INC_META") && ($GLOBALS['flag_ecrire'] || !lire_meta('langues_proposees'))) {
		$d = opendir($GLOBALS['flag_ecrire'] ? "lang" : "ecrire/lang");
		while ($f = readdir($d)) {
			if (ereg('^spip_([a-z]{2,3})\.php3?$', $f, $regs))
				$all_langs[] = $regs[1];
		}
		closedir($d);
		sort($all_langs);
		$GLOBALS['all_langs'] = join(',', $all_langs);
		ecrire_meta('langues_proposees', $GLOBALS['all_langs']);
	} else
		$GLOBALS['all_langs'] = lire_meta('langues_proposees');
}

init_langues();
utiliser_langue_site();

?>
