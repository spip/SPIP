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
	switch($lang) {
	case 'aa':$r="Afar";break;
	case 'ab':$r="Abkhazian";break;
	case 'af':$r="Afrikaans";break;
	case 'am':$r="Amharic";break;
	case 'ar':$r="&#1575;&#1604;&#1593;&#1585;&#1576;&#1610;&#1577;";break;
	case 'as':$r="Assamese";break;
	case 'ay':$r="Aymara";break;
	case 'az':$r="&#1040;&#1079;&#1241;&#1088;&#1073;&#1072;&#1112;&#1209;&#1072;&#1085;";break;
	case 'ba':$r="Bashkir";break;
	case 'be':$r="&#1041;&#1077;&#1083;&#1072;&#1088;&#1091;&#1089;&#1082;&#1110;";break;
	case 'bg':$r="&#1073;&#1098;&#1083;&#1075;&#1072;&#1088;&#1089;&#1082;&#1080;";break;
	case 'bh':$r="Bihari";break;
	case 'bi':$r="Bislama";break;
	case 'bn':$r="Bengali; Bangla";break;
	case 'bo':$r="Tibetan";break;
	case 'br':$r="Breton";break;
	case 'ca':$r="catal&#224;";break;
	case 'co':$r="Corsican";break;
	case 'cs':$r="&#269;e&#353;tina";break;
	case 'cy':$r="Welsh";break;
	case 'da':$r="dansk";break;
	case 'de':$r="Deutsch";break;
	case 'dz':$r="Bhutani";break;
	case 'el':$r="&#949;&#955;&#955;&#951;&#957;&#953;&#954;&#940;";break;
	case 'en':$r="English";break;
	case 'eo':$r="Esperanto";break;
	case 'es':$r="Espa&#241;ol";break;
	case 'et':$r="eesti";break;
	case 'eu':$r="euskara";break;
	case 'fa':$r="&#1601;&#1575;&#1585;&#1587;&#1609;";break;
	case 'fi':$r="suomi";break;
	case 'fj':$r="Fiji";break;
	case 'fo':$r="f&#248;royskt";break;
	case 'fr':$r="fran&#231;ais";break;
	case 'fy':$r="Frisian";break;
	case 'ga':$r="Irish";break;
	case 'gd':$r="Scots Gaelic";break;
	case 'gl':$r="Galician";break;
	case 'gn':$r="Guarani";break;
	case 'gu':$r="Gujarati";break;
	case 'ha':$r="Hausa";break;
	case 'he':$r="&#1506;&#1489;&#1512;&#1497;&#1514;";break;
	case 'hi':$r="&#2361;&#2367;&#2306;&#2342;&#2368;";break;
	case 'hr':$r="hrvatski";break;
	case 'hu':$r="magyar";break;
	case 'hy':$r="Armenian";break;
	case 'ia':$r="Interlingua";break;
	case 'id':$r="Bahasa Indonesia";break;
	case 'ie':$r="Interlingue";break;
	case 'ik':$r="Inupiak";break;
	case 'is':$r="&#237;slenska";break;
	case 'it':$r="italiano";break;
	case 'iu':$r="Inuktitut";break;
	case 'ja':$r="&#26085;&#26412;&#35486;";break;
	case 'jw':$r="Javanese";break;
	case 'ka':$r="&#4325;&#4304;&#4320;&#4311;&#4323;&#4314;&#4312;";break;
	case 'kk':$r="&#1178;&#1072;&#1079;&#1072;&#1097;b";break;
	case 'kl':$r="Greenlandic";break;
	case 'km':$r="Cambodian";break;
	case 'kn':$r="Kannada";break;
	case 'ko':$r="&#54620;&#44397;&#50612;";break;
	case 'ks':$r="Kashmiri";break;
	case 'ku':$r="Kurdish";break;
	case 'ky':$r="Kirghiz";break;
	case 'la':$r="Latin";break;
	case 'ln':$r="Lingala";break;
	case 'lo':$r="Laothian";break;
	case 'lt':$r="lietuvi&#371;";break;
	case 'lv':$r="latvie&#353;u";break;
	case 'mg':$r="Malagasy";break;
	case 'mi':$r="Maori";break;
	case 'mk':$r="&#1084;&#1072;&#1082;&#1077;&#1076;&#1086;&#1085;&#1089;&#1082;&#1080; &#1112;&#1072;&#1079;&#1080;&#1082;";break;
	case 'ml':$r="Malayalam";break;
	case 'mn':$r="Mongolian";break;
	case 'mo':$r="Moldavian";break;
	case 'mr':$r="&#2350;&#2352;&#2366;&#2336;&#2368;";break;
	case 'ms':$r="Bahasa Malaysia";break;
	case 'mt':$r="Maltese";break;
	case 'my':$r="Burmese";break;
	case 'na':$r="Nauru";break;
	case 'ne':$r="Nepali";break;
	case 'nl':$r="Nederlands";break;
	case 'no':$r="norsk";break;
	case 'oc':$r="Occitan";break;
	case 'om':$r="(Afan) Oromo";break;
	case 'or':$r="Oriya";break;
	case 'pa':$r="Punjabi";break;
	case 'pl':$r="polski";break;
	case 'ps':$r="Pashto, Pushto";break;
	case 'pt':$r="Portugu&#234;s";break;
	case 'qu':$r="Quechua";break;
	case 'rm':$r="Rhaeto-Romance";break;
	case 'rn':$r="Kirundi";break;
	case 'ro':$r="rom&#226;n&#259;";break;
	case 'ru':$r="&#1088;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081;";break;
	case 'rw':$r="Kinyarwanda";break;
	case 'sa':$r="&#2360;&#2306;&#2360;&#2381;&#2325;&#2371;&#2340;";break;
	case 'sd':$r="Sindhi";break;
	case 'sg':$r="Sangho";break;
	case 'sh':$r="Serbo-Croatian";break;
	case 'si':$r="Sinhalese";break;
	case 'sk':$r="sloven&#269;ina";break;
	case 'sl':$r="slovenski";break;
	case 'sm':$r="Samoan";break;
	case 'sn':$r="Shona";break;
	case 'so':$r="Somali";break;
	case 'sq':$r="shqipe";break;
	case 'sr':$r="&#1089;&#1088;&#1087;&#1089;&#1082;&#1080;";break;
	case 'ss':$r="Siswati";break;
	case 'st':$r="Sesotho";break;
	case 'su':$r="Sundanese";break;
	case 'sv':$r="svenska";break;
	case 'sw':$r="Kiswahili";break;
	case 'ta':$r="&#2980;&#2990;&#3007;&#2996;&#3021;";break;
	case 'te':$r="Telugu";break;
	case 'tg':$r="Tajik";break;
	case 'th':$r="&#3652;&#3607;&#3618;";break;
	case 'ti':$r="Tigrinya";break;
	case 'tk':$r="Turkmen";break;
	case 'tl':$r="Tagalog";break;
	case 'tn':$r="Setswana";break;
	case 'to':$r="Tonga";break;
	case 'tr':$r="T&#252;rk&#231;e";break;
	case 'ts':$r="Tsonga";break;
	case 'tt':$r="&#1058;&#1072;&#1090;&#1072;&#1088;";break;
	case 'tw':$r="Twi";break;
	case 'ug':$r="Uighur";break;
	case 'uk':$r="&#1091;&#1082;&#1088;&#1072;&#1111;&#1085;&#1100;&#1089;&#1082;&#1072;";break;
	case 'ur':$r="&#1649;&#1585;&#1583;&#1608;";break;
	case 'uz':$r="U'zbek";break;
	case 'vi':$r="Ti&#234;&#769;ng Vi&#234;&#803;t Nam";break;
	case 'vo':$r="Volapuk";break;
	case 'wo':$r="Wolof";break;
	case 'xh':$r="Xhosa";break;
	case 'yi':$r="Yiddish";break;
	case 'yo':$r="Yoruba";break;
	case 'za':$r="Zhuang";break;
	case 'zh':$r="&#20013;&#25991;";break;
	case 'zu':$r="Zulu";break;
	default:$r=$lang;
	}
	return $r;
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
		$GLOBALS['all_langs'] = join(',', $all_langs);
		ecrire_meta('langues_proposees', $GLOBALS['all_langs']);
	} else
		$GLOBALS['all_langs'] = lire_meta('langues_proposees');
}

init_langues();
utiliser_langue_site();

?>
