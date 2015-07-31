<?php

//
// Ce fichier ne sera execute qu'une fois   
if (defined("_ECRIRE_INC_CHARSETS")) return;
define("_ECRIRE_INC_CHARSETS", "1");

function filtrer_entites($texte) {	// html -> texte, a completer

	// NB en php4 il suffirait d'utiliser get_html_translation_table/array_flip
	// HTML_ENTITIES
	$trans_iso = array(
		'&iexcl;' => "\xa1",
		'&cent;' => "\xa2",
		'&pound;' => "\xa3",
		'&curren;' => "\xa4",
		'&yen;' => "\xa5",
		'&brvbar;' => "\xa6",
		'&sect;' => "\xa7",
		'&uml;' => "\xa8",
		'&ordf;' => "\xaa",
		'&laquo;' => "\xab",
		'&not;' => "\xac",
		'&shy;' => "\xad",
		'&macr;' => "\xaf",
		'&deg;' => "\xb0",
		'&plusmn;' => "\xb1",
		'&sup2;' => "\xb2",
		'&sup3;' => "\xb3",
		'&acute;' => "\xb4",
		'&micro;' => "\xb5",
		'&para;' => "\xb6",
		'&middot;' => "\xb7",
		'&cedil;' => "\xb8",
		'&sup1;' => "\xb9",
		'&ordm;' => "\xba",
		'&raquo;' => "\xbb",
		'&iquest;' => "\xbf",
		'&Agrave;' => "\xc0",
		'&Aacute;' => "\xc1",
		'&Acirc;' => "\xc2",
		'&Atilde;' => "\xc3",
		'&Auml;' => "\xc4",
		'&Aring;' => "\xc5",
		'&AElig;' => "\xc6",
		'&Ccedil;' => "\xc7",
		'&Egrave;' => "\xc8",
		'&Eacute;' => "\xc9",
		'&Ecirc;' => "\xca",
		'&Euml;' => "\xcb",
		'&Igrave;' => "\xcc",
		'&Iacute;' => "\xcd",
		'&Icirc;' => "\xce",
		'&Iuml;' => "\xcf",
		'&ETH;' => "\xd0",
		'&Ntilde;' => "\xd1",
		'&Ograve;' => "\xd2",
		'&Oacute;' => "\xd3",
		'&Ocirc;' => "\xd4",
		'&Otilde;' => "\xd5",
		'&Ouml;' => "\xd6",
		'&times;' => "\xd7",
		'&Oslash;' => "\xd8",
		'&Ugrave;' => "\xd9",
		'&Uacute;' => "\xda",
		'&Ucirc;' => "\xdb",
		'&Uuml;' => "\xdc",
		'&Yacute;' => "\xdd",
		'&THORN;' => "\xde",
		'&szlig;' => "\xdf",
		'&agrave;' => "\xe0",
		'&aacute;' => "\xe1",
		'&acirc;' => "\xe2",
		'&atilde;' => "\xe3",
		'&auml;' => "\xe4",
		'&aring;' => "\xe5",
		'&aelig;' => "\xe6",
		'&ccedil;' => "\xe7",
		'&egrave;' => "\xe8",
		'&eacute;' => "\xe9",
		'&ecirc;' => "\xea",
		'&euml;' => "\xeb",
		'&igrave;' => "\xec",
		'&iacute;' => "\xed",
		'&icirc;' => "\xee",
		'&iuml;' => "\xef",
		'&eth;' => "\xf0",
		'&ntilde;' => "\xf1",
		'&ograve;' => "\xf2",
		'&oacute;' => "\xf3",
		'&ocirc;' => "\xf4",
		'&otilde;' => "\xf5",
		'&ouml;' => "\xf6",
		'&divide;' => "\xf7",
		'&oslash;' => "\xf8",
		'&ugrave;' => "\xf9",
		'&uacute;' => "\xfa",
		'&ucirc;' => "\xfb",
		'&uuml;' => "\xfc",
		'&yacute;' => "\xfd",
		'&thorn;' => "\xfe"
	);

	$trans = array (
		'&nbsp;' => " ",
		'&copy;' => "(c)",
		'&reg;' => "(r)",
		'&frac14;' => "1/4",
		'&frac12;' => "1/2",
		'&frac34;' => "3/4",
		'&amp;' => '&',
		'&quot;' => '"',
		'&apos;' => "'",
		'&lt;' => '<',
		'&gt;' => '>'
	);

	$texte = strtr2 ($texte, $trans);

	if (lire_meta('charset') == 'iso-8859-1')	// recuperer les caracteres iso-latin
		$texte = strtr2 ($texte, $trans_iso);
	else if (lire_meta('charset') == 'utf-8') {
		// 1. recuperer les caracteres binaires en &#1234;
		$texte = entites_unicode($texte);
		// 2. les &eacute; en iso-8859-1
		$texte = strtr2 ($texte, $trans_iso);
		// 3. les iso en &#233;
		$texte = iso_8859_1_to_unicode($texte);
		// 4. le tout dans le charset cible
		$texte = unicode2charset($texte);
	}

	return $texte;
}

// strtr (string $texte, array $trans) = emuler le php4
function strtr2 ($texte, $trans) {
	global $flag_strtr2;

	if ($flag_strtr2)
		return strtr($texte,$trans);
	else {
		reset ($trans);
		while (list($entite, $remplace) = each ($trans))
			$texte = ereg_replace($entite, $remplace, $texte);
		return $texte;
	}
}



// transforme une chaine en entites unicode &#129;
function entites_unicode($chaine, $charset='AUTO') {
	if ($charset == 'AUTO')
		$charset=lire_meta('charset');

	switch($charset) {
		case 'iso-8859-1':
		// On commente cet appel tant qu'il reste des spip v<1.5 dans la nature
		//	$chaine = iso_8859_1_to_unicode($chaine);
			break;
		// FORCE-iso-8859-1 passe le message suivant : on VEUT la conversion, meme
		// si elle est desactivee dans entites_unicode pour maintenir (temporairement)
		// la lisibilite de notre backend sur des SPIP v<1.5
		case 'FORCE-iso-8859-1':
			$chaine = iso_8859_1_to_unicode($chaine);
			break;

		case 'utf-8':
			$chaine = utf_8_to_unicode($chaine);
			break;

		case 'windows-1251':
			$chaine = windows_1251_to_unicode($chaine);
			break;
			
		default:
			break;

	}
	return $chaine;
}

// transforme les entites unicode &#129; dans le charset courant
function unicode2charset($chaine, $charset='AUTO') {
	if ($charset == 'AUTO')
		$charset=lire_meta('charset');

	switch($charset) {

		case 'iso-8859-1':
			$chaine = unicode_to_iso_8859_1($chaine);
			break;

		case 'utf-8':
			$chaine = unicode_to_utf_8($chaine);
			break;
		
		case 'windows-1251':
			$chaine = unicode_to_windows_1251($chaine);
			break;

		default:
			break;
	}
	return $chaine;
}

//
// Il faut deux fonctions par charset : charset->unicode et unicode->charset
//

// ISO-8859-1
function iso_8859_1_to_unicode($chaine) {
	while ($i = ord(substr($chaine,$p++)))
		if ($i>127)
			$s .= "&#$i;";
		else
			$s .= chr($i);
	return $s;
}
function unicode_to_iso_8859_1($chaine) {
	while (ereg('&#([0-9]+);', $chaine, $regs) AND !$vu[$regs[1]]) {
		$vu[$regs[1]] = true;
		if ($regs[1] < 256)
			$chaine = ereg_replace($regs[0], chr($regs[1]), $chaine);
	}
	return $chaine;
}

// UTF-8
function utf_8_to_unicode($source) {
/*
 * Ce code provient de php.net, son auteur est Ronen. Adapte pour compatibilite php3
 */
	// array used to figure what number to decrement from character order value
	// according to number of characters used to map unicode to ascii by utf-8
	$decrement[4] = 240;
	$decrement[3] = 224;
	$decrement[2] = 192;
	$decrement[1] = 0;

	// the number of bits to shift each charNum by
	$shift[1][0] = 0;
	$shift[2][0] = 6;
	$shift[2][1] = 0;
	$shift[3][0] = 12;
	$shift[3][1] = 6;
	$shift[3][2] = 0;
	$shift[4][0] = 18;
	$shift[4][1] = 12;
	$shift[4][2] = 6;
	$shift[4][3] = 0;

	$pos = 0;
	$len = strlen ($source);
	$encodedString = '';
	while ($pos < $len) {
		$char = '';
		$asciiPos = ord (substr ($source, $pos, 1));
		if (($asciiPos >= 240) && ($asciiPos <= 255)) {
			// 4 chars representing one unicode character
			$thisLetter = substr ($source, $pos, 4);
			$pos += 4;
		}
		else if (($asciiPos >= 224) && ($asciiPos <= 239)) {
			// 3 chars representing one unicode character
			$thisLetter = substr ($source, $pos, 3);
			$pos += 3;
		}
		else if (($asciiPos >= 192) && ($asciiPos <= 223)) {
			// 2 chars representing one unicode character
			$thisLetter = substr ($source, $pos, 2);
			$pos += 2;
		}
		else {
			// 1 char (lower ascii)
			$thisLetter = substr ($source, $pos, 1);
			$pos += 1;
			$char = $thisLetter;
		}

		if ($char)
			$encodedString .= $char;
		else {	// process the string representing the letter to a unicode entity
			$thisLen = strlen ($thisLetter);
			$thisPos = 0;
			$decimalCode = 0;
			while ($thisPos < $thisLen) {
				$thisCharOrd = ord (substr ($thisLetter, $thisPos, 1));
				if ($thisPos == 0) {
					$charNum = intval ($thisCharOrd - $decrement[$thisLen]);
					$decimalCode += ($charNum << $shift[$thisLen][$thisPos]);
				} else {
					$charNum = intval ($thisCharOrd - 128);
					$decimalCode += ($charNum << $shift[$thisLen][$thisPos]);
				}
				$thisPos++;
			}
			$encodedLetter = "&#". ereg_replace('^0+', '', $decimalCode) . ';';
			$encodedString .= $encodedLetter;
		}
	}
	return $encodedString;
}

function unicode_to_utf_8($chaine) {
	while (ereg('&#([0-9]+);', $chaine, $regs) AND !$vu[$regs[1]]) {
		$num = $regs[1];
		$vu[$num] = true;
		if($num<128) $s = chr($num);	// Ce bloc provient de php.net, auteur Ronen
		else if($num<2048) $s = chr(($num>>6)+192).chr(($num&63)+128);
		else if($num<32768) $s = chr(($num>>12)+224).chr((($num>>6)&63)+128).chr(($num&63)+128);
		else if($num<2097152) $s = chr($num>>18+240).chr((($num>>12)&63)+128).chr(($num>>6)&63+128). chr($num&63+128);
		else $s = '';
		$chaine = ereg_replace($regs[0], $s, $chaine);
	}
	return $chaine;
}


// WINDOWS-1251 (CYRILLIQUE)
function load_windows_1251() {
	// extrait de la table
	// http://www.slav.helsinki.fi/atk/codepages/win1251.html
	static $table;
	if(is_array($table))
		return $table;
	else
		return $table = array(
		chr(129)=>'&#1027;',chr(131)=>'&#1107;',chr(138)=>'&#1033;',chr(140)=>'&#1034;',chr(141)=>'&#1036;',
		chr(142)=>'&#1035;',chr(143)=>'&#1039;',chr(144)=>'&#1106;',chr(154)=>'&#1113;',chr(156)=>'&#1114;',
		chr(157)=>'&#1116;',chr(158)=>'&#1115;',chr(159)=>'&#1119;',chr(161)=>'&#1038;',chr(162)=>'&#1118;',
		chr(163)=>'&#1032;',chr(165)=>'&#1168;',chr(168)=>'&#1025;',chr(170)=>'&#1028;',chr(175)=>'&#1031;',
		chr(178)=>'&#1030;',chr(179)=>'&#1110;',chr(180)=>'&#1169;',chr(184)=>'&#1105;',chr(186)=>'&#1108;',
		chr(188)=>'&#1112;',chr(189)=>'&#1029;',chr(190)=>'&#1109;',chr(191)=>'&#1111;',chr(192)=>'&#1040;',
		chr(193)=>'&#1041;',chr(194)=>'&#1042;',chr(195)=>'&#1043;',chr(196)=>'&#1044;',chr(197)=>'&#1045;',
		chr(198)=>'&#1046;',chr(199)=>'&#1047;',chr(200)=>'&#1048;',chr(201)=>'&#1049;',chr(202)=>'&#1050;',
		chr(203)=>'&#1051;',chr(204)=>'&#1052;',chr(205)=>'&#1053;',chr(206)=>'&#1054;',chr(207)=>'&#1055;',
		chr(208)=>'&#1056;',chr(209)=>'&#1057;',chr(210)=>'&#1058;',chr(211)=>'&#1059;',chr(212)=>'&#1060;',
		chr(213)=>'&#1061;',chr(214)=>'&#1062;',chr(215)=>'&#1063;',chr(216)=>'&#1064;',chr(217)=>'&#1065;',
		chr(218)=>'&#1066;',chr(219)=>'&#1067;',chr(220)=>'&#1068;',chr(221)=>'&#1069;',chr(222)=>'&#1070;',
		chr(223)=>'&#1071;',chr(224)=>'&#1072;',chr(225)=>'&#1073;',chr(226)=>'&#1074;',chr(227)=>'&#1075;',
		chr(228)=>'&#1076;',chr(229)=>'&#1077;',chr(230)=>'&#1078;',chr(231)=>'&#1079;',chr(232)=>'&#1080;',
		chr(233)=>'&#1081;',chr(234)=>'&#1082;',chr(235)=>'&#1083;',chr(236)=>'&#1084;',chr(237)=>'&#1085;',
		chr(238)=>'&#1086;',chr(239)=>'&#1087;',chr(240)=>'&#1088;',chr(241)=>'&#1089;',chr(242)=>'&#1090;',
		chr(243)=>'&#1091;',chr(244)=>'&#1092;',chr(245)=>'&#1093;',chr(246)=>'&#1094;',chr(247)=>'&#1095;',
		chr(248)=>'&#1096;',chr(249)=>'&#1097;',chr(250)=>'&#1098;',chr(251)=>'&#1099;',chr(252)=>'&#1100;',
		chr(253)=>'&#1101;',chr(254)=>'&#1102;',chr(255)=>'&#1103;'
	);
}
function windows_1251_to_unicode($chaine) {
	$trans = load_windows_1251();
	while ($i = substr($chaine,$p++,1))
		if ($t = $trans[$i])
			$s .= $t;
		else
			$s .= $i;
	return $s;
}
function unicode_to_windows_1251($chaine) {
	$trans = load_windows_1251();
	while (list($chr,$uni) = each($trans))	// array_flip
		$ttrans[$uni] = $chr;
	while (ereg('&#([0-9]+);', $chaine, $regs) AND !$vu[$regs[1]]) {
		$vu[$regs[1]] = true;
		if ($ttrans[$regs[0]])
			$chaine = ereg_replace($regs[0], $ttrans[$regs[0]], $chaine);
	}
	return $chaine;
}


//
// Translitteration charset => ascii (pour l'indexation)
//
function translitteration ($texte, $charset='AUTO') {
	if ($charset == 'AUTO')
		$charset = lire_meta('charset');

	if ($charset == 'iso-8859-1') {
		$texte = translit_iso8859_1($texte);
	} else if ($charset == 'windows-1251') {
		$texte = translit_windows_1251($texte);
	} else if ($GLOBALS['flag_iconv']
		AND ($iconv = @iconv(strtoupper($charset), 'ASCII//TRANSLIT', $texte))
		AND !ereg('^\?+$',$iconv))
			$texte = $iconv;
	}
	return $texte;
}

function translit_iso8859_1($texte) {
	// Merci a phpDig (Antoine Bajolet) pour la fonction originale
	$accents =
		/* A */ chr(192).chr(193).chr(194).chr(195).chr(196).chr(197).
		/* a */ chr(224).chr(225).chr(226).chr(227).chr(228).chr(229).
		/* O */ chr(210).chr(211).chr(212).chr(213).chr(214).chr(216).
		/* o */ chr(242).chr(243).chr(244).chr(245).chr(246).chr(248).
		/* E */ chr(200).chr(201).chr(202).chr(203).
		/* e */ chr(232).chr(233).chr(234).chr(235).
		/* Cc */ chr(199).chr(231).
		/* I */ chr(204).chr(205).chr(206).chr(207).
		/* i */ chr(236).chr(237).chr(238).chr(239).
		/* U */ chr(217).chr(218).chr(219).chr(220).
		/* u */ chr(249).chr(250).chr(251).chr(252).
		/* yNn */ chr(255).chr(209).chr(241);
	return strtr($texte,
		$accents,
		"AAAAAAaaaaaaOOOOOOooooooEEEEeeeeCcIIIIiiiiUUUUuuuuyNn");
}

function translit_windows_1251($texte) {
	$code = array (
		128=>'D',129=>'G',136=>'euro',138=>'LJ',140=>'NJ',141=>'KJ',142=>'Ts',143=>'DZ',144=>'d',149=>'o',
		154=>'lj',156=>'nj',157=>'kj',158=>'ts',159=>'dz',161=>'V',162=>'v',163=>'J',165=>'G',168=>'IO',
		170=>'IE',175=>'YI',178=>'II',179=>'ii',180=>'g',181=>'mu',184=>'io',186=>'ie',188=>'j',189=>'DS',
		190=>'ds',191=>'yi',192=>'A',193=>'B',194=>'V',195=>'G',196=>'D',197=>'E',198=>'ZH',199=>'Z',
		200=>'I',201=>'J',202=>'K',203=>'L',204=>'M',205=>'N',206=>'O',207=>'P',208=>'R',209=>'S',210=>'T',
		211=>'U',212=>'F',213=>'H',214=>'C',215=>'CH',216=>'SH',217=>'SCH',218=>'"',219=>'Y',220=>'_',
		221=>'e',222=>'YU',223=>'YA',224=>'a',225=>'b',226=>'v',227=>'g',228=>'d',229=>'e',230=>'zh',
		231=>'z',232=>'i',233=>'j',234=>'k',235=>'l',236=>'m',237=>'n',238=>'o',239=>'p',240=>'r',241=>'s',
		242=>'t',243=>'u',244=>'f',245=>'h',246=>'c',247=>'ch',248=>'sh',249=>'sch',250=>' ',251=>'y',
		252=>'_',253=>'E',254=>'yu',255=>'ya');

	for ($i=0; $i<strlen($texte);$i++) {
		$d = substr($texte,$i,1);
		if ($c = $code[ord($d)])
			$ret .= $c;
		else
			$ret .= $d;
	}
	return $ret;
}

?>