<?php

// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_LANG")) return;
define("_ECRIRE_INC_LANG", "1");



//
// Charger un fichier langue
//
function charger_langue($lang, $module='spip') {
	global $dir_ecrire;

	$fichier_lang = 'lang/'.$module.'_'.$lang.'.php3';
	if (file_exists($dir_ecrire.$fichier_lang)) {
		include_ecrire ($fichier_lang);
	} else {
		// si le fichier de langue du module n'existe pas, on se rabat sur
		// le francais, qui *par definition* doit exister, et on copie le
		// tableau 'fr' dans la var liee a la langue
		include_ecrire ('lang/'.$module.'_fr.php3');
		$GLOBALS['i18n_'.$module.'_'.$lang] = $GLOBALS['i18n_'.$module.'_fr'];
	}

	// surcharge perso
	if (file_exists($dir_ecrire.'lang/perso.php3')) {
		include_ecrire('lang/perso.php3');
	}
}

//
// Changer la langue courante
//
function changer_langue($lang) {
	global $all_langs, $spip_lang_rtl, $spip_lang_right, $spip_lang_left;
 	if ($lang && ereg(",$lang,", ",$all_langs,")) {
		$GLOBALS['spip_lang'] = $lang;
		if ($lang == 'ar' OR $lang == 'fa')	// arabic, farsi
			$spip_lang_rtl = '_rtl';
		else
			$spip_lang_rtl = '';

		if ($spip_lang_rtl) {
			$spip_lang_left = 'right';
			$spip_lang_right = 'left';
		} else {
			$spip_lang_left = 'left';
			$spip_lang_right = 'right';
		}
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
function traduire_chaine($code, $args) {
	global $spip_lang;

	if (ereg("^([a-z]+):(.*)$", $code, $regs)) {
		$module = $regs[1];
		$code = $regs[2];
	} else
		$module = 'spip';

	$var = "i18n_".$module."_".$spip_lang;
	if (!$GLOBALS[$var]) charger_langue($spip_lang, $module);

	$text = $GLOBALS[$var][$code];

	if (!is_array($args)) return $text;

	while (list($name, $value) = each($args))
		$text = str_replace ("@$name@", $value, $text);
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
	'cpf' => "Kr&eacute;ol r&eacute;yon&eacute;",
	'cpf_dom' => "Krey&ograve;l",
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
	'gl' => "galego",
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
	'oci_lnc' => "lengadocian",
	'oci_prv_ni' => "ni&ccedil;ard",
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
	'vi' => "Ti&#7871;ng Vi&#7879;t",
	'vo' => "Volapuk",
	'wo' => "Wolof",
	'xh' => "Xhosa",
	'yi' => "Yiddish",
	'yor' => "Yoruba",
	'za' => "Zhuang",
	'zh' => "&#20013;&#25991;",
	'zu' => "Zulu");
	$GLOBALS['codes_langues'] = $codes_langues;

	$r = $codes_langues[$lang];
	if (!$r) $r = $lang;
	return $r;
}

//
// Afficher un menu de selection de langue
//
function menu_langues($nom_select = 'var_lang', $default = '', $texte = '') {
	global $couleur_foncee;

	if ($default == '')
		$default = $GLOBALS['spip_lang'];
	else if ($default == '--') {
		$parenthese_o = '(';
		$parenthese_f = ')';
		$default = lire_meta('langue_site');
	} else if (substr($default,0,1) == '.') {
		$parenthese_o = '(';
		$parenthese_f = ')';
		$default = substr($default,1);
	}

	if ($nom_select == 'var_lang') {
		$langues = explode(',', $GLOBALS['all_langs']);
	}
	else {
		$langues = explode(',', lire_meta('multi_auth'));
	}
	
	if (count($langues) <= 1) return;

	if (!$couleur_foncee) $couleur_foncee = '#044476';

	$lien = $GLOBALS['clean_link'];
	$lien->delVar($nom_select);
	$lien = $lien->getUrl();

	$amp = (strpos(' '.$lien,'?') ? '&' : '?');

	$ret = "<form action='$lien' method='get' style='margin:0px; padding:0px;'>";
	$ret .= $texte;
	if ($nom_select == 'var_lang') $ret .= "\n<select name='$nom_select' class='verdana1' style='background-color: $couleur_foncee; color: white;' onChange=\"document.location.href='". $lien . $amp."$nom_select='+this.options[this.selectedIndex].value\">\n";
	else $ret .= "\n<select name='$nom_select' class='fondl'>\n";
	$ret .= $premier_option;
	while (list(,$l) = each ($langues)) {
		if ($l == $default)
			$ret .= "<option value='$l' selected>$parenthese_o".traduire_nom_langue($l)."$parenthese_f</option>\n";
		else
			$ret .= "<option value='$l'>".traduire_nom_langue($l)."</option>\n";
	}
	$ret .= "</select>\n";
	if ($nom_select == 'var_lang') $ret .= "<noscript><INPUT TYPE='submit' NAME='Valider' VALUE='>>' class='verdana1' style='background-color: $couleur_foncee; color: white; height: 19px;'></noscript>";
	else $ret .= "<INPUT TYPE='submit' NAME='Modifier' VALUE='"._T('bouton_modifier')."' CLASS='fondo'>";
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
function utiliser_langue_site() {
	changer_langue($GLOBALS['langue_site']);
}

function utiliser_langue_visiteur() {
	if (!regler_langue_navigateur())
		utiliser_langue_site();
	if ($GLOBALS['auteur_session']['lang'])
		changer_langue($GLOBALS['auteur_session']['lang']);
}

//
// Initialisation
//
function init_langues() {
	global $all_langs, $dir_ecrire, $langue_site;

	$all_langs = lire_meta('langues_proposees');
	$langue_site = lire_meta('langue_site');

	if (!$all_langs || !$langue_site || !$dir_ecrire) {
		if (!$d = @opendir($dir_ecrire.'lang')) return;
		while ($f = readdir($d)) {
			if (ereg('^spip_([a-z_]+)\.php3?$', $f, $regs))
				$toutes_langs[] = $regs[1];
		}
		closedir($d);
		sort($toutes_langs);
		$all_langs = join(',', $toutes_langs);

		if (defined("_ECRIRE_INC_META"))
			ecrire_meta('langues_proposees', $all_langs);

		if (!$langue_site) {
			// Initialisation : le francais par defaut, sinon la premiere langue trouvee
			if (ereg(',fr,', ",$all_langs,")) $langue_site = 'fr';
			else list(, $langue_site) = each($toutes_langs);
			if (defined("_ECRIRE_INC_META"))
				ecrire_meta('langue_site', $langue_site);
		}
	}
}

init_langues();
utiliser_langue_site();

?>
