<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


/*
 Ce fichier contient les codes de langue de base de SPIP.
 La plupart sont des codes ISO 639-1, dont vous pouvez lire
 les definitions/descriptions en francais et en anglais dans
 http://www.loc.gov/standards/iso639-2/langcodes.html
*/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

$GLOBALS['codes_langues'] = array(
	'aa' => 'Afar',
	'ab' => 'Abkhazian',
	'af' => 'Afrikaans',
	'am' => 'Amharic',
	'an' => 'Aragon&#233;s',
	'ar' => '&#1593;&#1585;&#1576;&#1610;',
	'as' => 'Assamese',
	'ast' => 'asturianu',
	'ay' => 'Aymara',
	'az' => 'Az&#601;rbaycan dili',
	'ba' => 'Bashkir',
	'be' => '&#1041;&#1077;&#1083;&#1072;&#1088;&#1091;&#1089;&#1082;&#1072;&#1103;',
	'ber_tam' => 'Tamazigh',
	'ber_tam_tfng' => 'Tamazigh tifinagh',
	'bg' => '&#1073;&#1098;&#1083;&#1075;&#1072;&#1088;&#1089;&#1082;&#1080;',
	'bh' => 'Bihari',
	'bi' => 'Bislama',
	'bm' => 'Bambara',
	'bn' => 'Bengali; Bangla',
	'bo' => 'Tibetan',
	'br' => 'brezhoneg',
	'bs' => 'bosanski',
	'ca' => 'catal&#224;',
	'co' => 'corsu',
	'cpf' => 'Kr&eacute;ol r&eacute;yon&eacute;',
	'cpf_dom' => 'Krey&ograve;l',
	'cpf_hat' => 'Krey&ograve;l (Peyi Dayiti)',
	'cs' => '&#269;e&#353;tina',
	'cy' => 'Cymraeg',  # welsh, gallois
	'da' => 'dansk',
	'de' => 'Deutsch',
	'dz' => 'Bhutani',
	'el' => '&#949;&#955;&#955;&#951;&#957;&#953;&#954;&#940;',
	'en' => 'English',
	'en_hx' => 'H4ck3R',
	'en_sm' => 'Smurf',
	'eo' => 'Esperanto',
	'es' => 'Espa&#241;ol',
	'es_co' => 'Colombiano',
	'es_mx_pop' => 'Mexicano a lo g&#252;ey',
	'et' => 'eesti',
	'eu' => 'euskara',
	'fa' => '&#1601;&#1575;&#1585;&#1587;&#1609;',
	'ff' => 'Fulah', // peul
	'fi' => 'suomi',
	'fj' => 'Fiji',
	'fo' => 'f&#248;royskt',
	'fon' => 'fongb&egrave;',
	'fr' => 'fran&#231;ais',
	'fr_fem' => 'fran&#231;ais f&#233;minin',
	'fr_sc' => 'schtroumpf',
	'fr_lpc' => 'langue parl&#233;e compl&#233;t&#233;e',
	'fr_lsf' => 'langue des signes fran&#231;aise',
	'fr_spl' => 'fran&#231;ais simplifi&#233;',
	'fr_tu' => 'fran&#231;ais copain',
	'fy' => 'Frisian',
	'ga' => 'Irish',
	'gd' => 'Scots Gaelic',
	'gl' => 'galego',
	'gn' => 'Guarani',
	'grc' => '&#7944;&#961;&#967;&#945;&#943;&#945; &#7961;&#955;&#955;&#951;&#957;&#953;&#954;&#942;', // grec ancien
	'gu' => 'Gujarati',
	'ha' => 'Hausa',
	'hac' => '&#1705;-&#1607;&#1734;&#1585;&#1575;&#1605;&#1740;', //"Kurdish-Horami"
	'hbo' => '&#1506;&#1489;&#1512;&#1497;&#1514;&#1470;&#1492;&#1514;&#1504;&#1498;', // hebreu classique ou biblique
	'haz' => '&#x647;&#x632;&#x627;&#x631;&#x647; &#x6AF;&#x6CC;',
	'he' => '&#1506;&#1489;&#1512;&#1497;&#1514;',
	'hi' => '&#2361;&#2367;&#2306;&#2342;&#2368;',
	'hr' => 'hrvatski',
	'hu' => 'magyar',
	'hy' => '&#1344;&#1377;&#1397;&#1381;&#1408;&#1381;&#1398;',// Arménien
	'ia' => 'Interlingua',
	'id' => 'Indonesia',
	'ie' => 'Interlingue',
	'ik' => 'Inupiak',
	'is' => '&#237;slenska',
	'it' => 'italiano',
	'it_fem' => 'italiana',
	'iu' => 'Inuktitut',
	'ja' => '&#26085;&#26412;&#35486;',
	'jv' => 'Javanese',
	'ka' => '&#4325;&#4304;&#4320;&#4311;&#4323;&#4314;&#4312;',
	'kk' => '&#1179;&#1072;&#1079;&#1072;&#1179;&#32;&#1090;&#1110;&#1083;&#1110;', // Kazakh
	'kl' => 'kalaallisut',
	'km' => '&#6039;&#6070;&#6047;&#6070;&#6017;&#6098;&#6040;&#6082;&#6042;',// Khmer
	'kn' => 'Kannada',
	'ko' => '&#54620;&#44397;&#50612;',
	'kok' => '&#2325;&#2379;&#2306;&#2325;&#2339;&#2368;',
	'ks' => 'Kashmiri',
	'ku' => '&#1705;&#1608;&#1585;&#1583;&#1740;',
	'ky' => 'Kirghiz',
	'la' => 'lingua latina',
	'lb' => 'L&euml;tzebuergesch',
	'ln' => 'Lingala',
	'lo' => '&#3742;&#3762;&#3754;&#3762;&#3749;&#3762;&#3751;', # lao
	'lt' => 'lietuvi&#371;',
	'lu' => 'luba-katanga',
	'lv' => 'latvie&#353;u',
	'man' => 'mandingue', # a traduire en mandingue
	'mfv' => 'manjak', # ISO-639-3
	'mg' => 'Malagasy',
	'mi' => 'Maori',
	'mk' => '&#1084;&#1072;&#1082;&#1077;&#1076;&#1086;&#1085;&#1089;&#1082;&#1080; &#1112;&#1072;&#1079;&#1080;&#1082;',
	'ml' => 'Malayalam',
	'mn' => 'Монгол хэл',
	'mo' => 'Moldavian',
	'mos' => 'Mor&eacute;',
	'mr' => '&#2350;&#2352;&#2366;&#2336;&#2368;',
	'ms' => 'Bahasa Malaysia',
	'mt' => 'Maltese',
	'my' => 'Burmese',
	'na' => 'Nauru',
	'nap' => 'napulitano',
	'ne' => 'Nepali',
	'nqo' => "N'ko", // www.manden.org
	'nl' => 'Nederlands',
	'no' => 'norsk',
	'nb' => 'norsk bokm&aring;l',
	'nn' => 'norsk nynorsk',
	'oc' => '&ograve;c',
	'oc_lnc' => '&ograve;c lengadocian',
	'oc_ni' => '&ograve;c ni&ccedil;ard',
	'oc_ni_la' => '&ograve;c ni&ccedil;ard (larg)',
	'oc_ni_mis' => '&ograve;c nissart (mistralenc)',
	'oc_prv' => '&ograve;c proven&ccedil;au',
	'oc_gsc' => '&ograve;c gascon',
	'oc_lms' => '&ograve;c lemosin',
	'oc_auv' => '&ograve;c auvernhat',
	'oc_va' => '&ograve;c vivaroaupenc',
	'om' => '(Afan) Oromo',
	'or' => 'Oriya',
	'pa' => 'Punjabi',
	'pbb' => 'Nasa Yuwe',
	'pl' => 'polski',
	'prs' => '&#1583;&#1585;&#1740;', // ISO-639-3 Dari (Afghanistan)
	'ps' => '&#1662;&#1690;&#1578;&#1608;',
	'pt' => 'Portugu&#234;s',
	'pt_br' => 'Portugu&#234;s do Brasil',
	'qu' => 'Quechua',
	'rm' => 'Rhaeto-Romance',
	'rn' => 'Kirundi',
	'ro' => 'rom&#226;n&#259;',
	'roa' => "ch'ti",
	'ru' => '&#1088;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081;',
	'rw' => 'Kinyarwanda',
	'sa' => '&#2360;&#2306;&#2360;&#2381;&#2325;&#2371;&#2340;',
	'sc' => 'sardu',
	'scn' => 'sicilianu',
	'sd' => 'Sindhi',
	'sg' => 'Sangho',
	'sh' => 'srpskohrvastski',
	'sh_latn' => 'srpskohrvastski',
	'sh_cyrl' => '&#1057;&#1088;&#1087;&#1089;&#1082;&#1086;&#1093;&#1088;&#1074;&#1072;&#1090;&#1089;&#1082;&#1080;',
	'si' => 'Sinhalese',
	'sk' => 'sloven&#269;ina',  // (Slovakia)
	'sl' => 'sloven&#353;&#269;ina',  // (Slovenia)
	'sm' => 'Samoan',
	'sn' => 'Shona',
	'so' => 'Somali',
	'sq' => 'shqip',
	'sr' => '&#1089;&#1088;&#1087;&#1089;&#1082;&#1080;',
	'src' => 'sardu logudor&#233;su', // sarde cf 'sc'
	'sro' => 'sardu campidan&#233;su',
	'ss' => 'Siswati',
	'st' => 'Sesotho',
	'su' => 'Sundanese',
	'sv' => 'svenska',
	'sw' => 'Kiswahili',
	'ta' => '&#2980;&#2990;&#3007;&#2996;&#3021;', // Tamil
	'te' => 'Telugu',
	'tg' => 'Tajik',
	'th' => '&#3652;&#3607;&#3618;',
	'ti' => 'Tigrinya',
	'tk' => 'Turkmen',
	'tl' => 'Tagalog',
	'tn' => 'Setswana',
	'to' => 'Tonga',
	'tr' => 'T&#252;rk&#231;e',
	'ts' => 'Tsonga',
	'tt' => '&#1058;&#1072;&#1090;&#1072;&#1088;',
	'tw' => 'Twi',
	'ty' => 'reo m&#257;`ohi', // tahitien
	'ug' => 'Uighur',
	'uk' => '&#1091;&#1082;&#1088;&#1072;&#1111;&#1085;&#1089;&#1100;&#1082;&#1072;',
	'ur' => '&#1649;&#1585;&#1583;&#1608;',
	'uz' => "O'zbekcha",
	'vi' => 'Ti&#7871;ng Vi&#7879;t',
	'vo' => 'Volapuk',
	'wa' => 'walon',
	'wo' => 'Wolof',
	'xh' => 'Xhosa',
	'yi' => 'Yiddish',
	'yo' => 'Yoruba',
	'za' => 'Zhuang',
	'zh' => '&#20013;&#25991;', // chinois (ecriture simplifiee)
	'zh_tw' => '&#21488;&#28771;&#20013;&#25991;', // chinois taiwan (ecr. traditionnelle)
	'zu' => 'Zulu'

);
