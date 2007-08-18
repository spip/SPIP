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


//
if (!defined("_ECRIRE_INC_VERSION")) return;


/*
 * charsets supportes en natif : voir les tables dans ecrire/charsets/
 * les autres charsets sont supportes via mbstring()
 */

// http://doc.spip.org/@load_charset
function load_charset ($charset = 'AUTO', $langue_site = 'AUTO') {
	if ($charset == 'AUTO')
		$charset = $GLOBALS['meta']['charset'];
	$charset = trim(strtolower($charset));
	if (isset($GLOBALS['CHARSET'][$charset]))
		return $charset;

	if ($langue_site == 'AUTO')
		$langue_site = $GLOBALS['meta']['langue_site'];

	if ($charset == 'utf-8') {
		$GLOBALS['CHARSET'][$charset] = array();
		return $charset;
	}
	
	// Quelques synonymes
	if ($charset == '') $charset = 'iso-8859-1';
	else if ($charset == 'windows-1250') $charset = 'cp1250';
	else if ($charset == 'windows-1251') $charset = 'cp1251';
	else if ($charset == 'windows-1256') $charset = 'cp1256';

	if (include_spip('charsets/'.$charset)) {
		return $charset;
	} else {
		spip_log("Erreur: pas de fichier de conversion 'charsets/$charset'");
		$GLOBALS['CHARSET'][$charset] = array();
		return false;
	}
}

//
// Verifier qu'on peut utiliser mb_string
//
// http://doc.spip.org/@init_mb_string
function init_mb_string() {
	static $mb;

	// verifier que tout est present (fonctions mb_string pour php >= 4.0.6)
	// et que le charset interne est connu de mb_string
	if (!$mb) {
		if (function_exists('mb_internal_encoding')
		AND function_exists('mb_detect_order')
		AND function_exists('mb_substr')
		AND function_exists('mb_strlen')
		AND function_exists('mb_encode_mimeheader')
		AND function_exists('mb_encode_numericentity')
		AND function_exists('mb_decode_numericentity')
		AND mb_detect_order($GLOBALS['meta']['charset'])
		) {
			mb_internal_encoding('utf-8');
			$mb = 1;
		} else
			$mb = -1;
	}

	return ($mb == 1);
}

// Detecter les versions buggees d'iconv
// http://doc.spip.org/@test_iconv
function test_iconv() {
	static $iconv_ok;

	if (!$iconv_ok) {
		if (!function_exists('iconv'))
			$iconv_ok = -1;
		else {
			if (utf_32_to_unicode(@iconv('utf-8', 'utf-32', 'chaine de test')) == 'chaine de test')
				$iconv_ok = 1;
			else
				$iconv_ok = -1;
		}
	}
	return ($iconv_ok == 1);
}

// Test de fonctionnement du support UTF-8 dans PCRE
// (contournement bug Debian Woody)
// http://doc.spip.org/@test_pcre_unicode
function test_pcre_unicode() {
	static $pcre_ok = 0;

	if (!$pcre_ok) {
		$s = " ".chr(195).chr(169)."t".chr(195).chr(169)." ";
		if (preg_match(',\W\w\w\w\W,uS', $s)) $pcre_ok = 1;
		else $pcre_ok = -1;
	}
	return $pcre_ok == 1;
}

// Plages alphanumeriques (incomplet...)
// http://doc.spip.org/@pcre_lettres_unicode
function pcre_lettres_unicode() {
	static $plage_unicode;

	if (!$plage_unicode) {
		if (test_pcre_unicode()) {
			// cf. http://www.unicode.org/charts/
			$plage_unicode = '\w' // iso-latin
				. '\x{100}-\x{24f}' // europeen etendu
				. '\x{300}-\x{1cff}' // des tas de trucs
			;
		}
		else {
			// fallback a trois sous
			$plage_unicode = '\w';
		}
	}
	return $plage_unicode;
}

// Plage ponctuation de 0x2000 a 0x206F
// (i.e. de 226-128-128 a 226-129-176)
// http://doc.spip.org/@plage_punct_unicode
function plage_punct_unicode() {
	return '\xE2(\x80[\x80-\xBF]|\x81[\x80-\xAF])';
}

// corriger caracteres non-conformes : 128-159
// cf. charsets/iso-8859-1.php (qu'on recopie ici pour aller plus vite)
// on peut passer un charset cible en parametre pour accelerer le passage iso-8859-1 -> autre charset
// http://doc.spip.org/@corriger_caracteres_windows
function corriger_caracteres_windows($texte, $charset='AUTO', $charset_cible='unicode') {
	static $trans;

	if ($charset=='AUTO') $charset = $GLOBALS['meta']['charset'];
	if ($charset == 'utf-8') {
		$p = chr(194);
	} else if ($charset == 'iso-8859-1') {
		$p = '';
	} else
		return $texte;

	if (!isset($trans[$charset][$charset_cible])) {
		$trans[$charset][$charset_cible] = array(
			$p.chr(128) => "&#8364;",
			$p.chr(129) => ' ', # pas affecte
			$p.chr(130) => "&#8218;",
			$p.chr(131) => "&#402;",
			$p.chr(132) => "&#8222;",
			$p.chr(133) => "&#8230;",
			$p.chr(134) => "&#8224;",
			$p.chr(135) => "&#8225;",
			$p.chr(136) => "&#710;",
			$p.chr(137) => "&#8240;",
			$p.chr(138) => "&#352;",
			$p.chr(139) => "&#8249;",
			$p.chr(140) => "&#338;",
			$p.chr(141) => ' ', # pas affecte
			$p.chr(142) => "&#381;",
			$p.chr(143) => ' ', # pas affecte
			$p.chr(144) => ' ', # pas affecte
			$p.chr(145) => "&#8216;",
			$p.chr(146) => "&#8217;",
			$p.chr(147) => "&#8220;",
			$p.chr(148) => "&#8221;",
			$p.chr(149) => "&#8226;",
			$p.chr(150) => "&#8211;",
			$p.chr(151) => "&#8212;",
			$p.chr(152) => "&#732;",
			$p.chr(153) => "&#8482;", 
			$p.chr(154) => "&#353;",
			$p.chr(155) => "&#8250;",
			$p.chr(156) => "&#339;",
			$p.chr(157) => ' ', # pas affecte
			$p.chr(158) => "&#382;",
			$p.chr(159) => "&#376;",
		);
		if ($charset_cible!='unicode'){
			foreach($trans[$charset][$charset_cible] as $k=>$c)
				$trans[$charset][$charset_cible][$k] = unicode2charset($c, $charset_cible);
		}
	}

	return @str_replace(array_keys($trans[$charset][$charset_cible]),
			   array_values($trans[$charset][$charset_cible]),$texte);
}


//
// Transformer les &eacute; en &#123;
// $secure = true pour *ne pas convertir* les caracteres malins &lt; &amp; etc.
//
// http://doc.spip.org/@html2unicode
function html2unicode($texte, $secure=false) {
	if (strpos($texte,'&') === false) return $texte;
	static $trans = array();
	if (!$trans) {
		global $CHARSET;
		load_charset('html');
		foreach ($CHARSET['html'] as $key => $val) {
			$trans["&$key;"] = $val;
		}
	}

	if ($secure)
		return str_replace(array_keys($trans),array_values($trans),$texte);
	else
		return str_replace(array('&amp;', '&quot;', '&lt;', '&gt;'),array('&', '"', '<', '>'),
		  str_replace(array_keys($trans),array_values($trans),$texte)			
		);
}

//
// Transformer les &eacute; en &#123;
//
// http://doc.spip.org/@mathml2unicode
function mathml2unicode($texte) {
	static $trans;
	if (!$trans) {
		global $CHARSET;
		load_charset('mathml');
		
		foreach ($CHARSET['mathml'] as $key => $val)
			$trans["&$key;"] = $val;
	}

	return str_replace(array_keys($trans),array_values($trans),$texte);
}


//
// Transforme une chaine en entites unicode &#129;
//
// Note: l'argument $forcer est obsolete : il visait a ne pas
// convertir les accents iso-8859-1
// http://doc.spip.org/@charset2unicode
function charset2unicode($texte, $charset='AUTO' /* $forcer: obsolete*/) {
	static $trans;

	if ($charset == 'AUTO')
		$charset = $GLOBALS['meta']['charset'];

	if ($charset == '') $charset = 'iso-8859-1';
	$charset = strtolower($charset);

	switch ($charset) {
	case 'utf-8':
		return utf_8_to_unicode($texte);

	case 'iso-8859-1':
		$texte = corriger_caracteres_windows($texte, 'iso-8859-1');
		// pas de break; ici, on suit sur default:

	default:
		// mbstring presente ?
		if (init_mb_string()) {
			if ($order = mb_detect_order() # mb_string connait-il $charset?
			AND mb_detect_order($charset)) {
				$s = mb_convert_encoding($texte, 'utf-8', $charset);
				if ($s && $s != $texte) return utf_8_to_unicode($s);
			}
			mb_detect_order($order); # remettre comme precedemment
		}

		// Sinon, peut-etre connaissons-nous ce charset ?
		if (!isset($trans[$charset])) {
			global $CHARSET;
			if ($cset = load_charset($charset)
			AND is_array($CHARSET[$cset]))
				foreach ($CHARSET[$cset] as $key => $val) {
					$trans[$charset][chr($key)] = '&#'.$val.';';
			}
		}
		if (count($trans[$charset]))
			return str_replace(array_keys($trans[$charset]),array_values($trans[$charset]),$texte);

		// Sinon demander a iconv (malgre le fait qu'il coupe quand un
		// caractere n'appartient pas au charset, mais c'est un probleme
		// surtout en utf-8, gere ci-dessus)
		if (test_iconv()) {
			$s = iconv($charset, 'utf-32le', $texte);
			if ($s) return utf_32_to_unicode($s);
		}

		// Au pire ne rien faire
		spip_log("erreur charset '$charset' non supporte");
		return $texte;
	}
}

//
// Transforme les entites unicode &#129; dans le charset specifie
// Attention on ne transforme pas les entites < &#128; car si elles
// ont ete encodees ainsi c'est a dessein
// http://doc.spip.org/@unicode2charset
function unicode2charset($texte, $charset='AUTO') {
	static $CHARSET_REVERSE;
	static $trans = array();
	
	if ($charset == 'AUTO')
		$charset = $GLOBALS['meta']['charset'];

	switch($charset) {
	case 'utf-8':
		return unicode_to_utf_8($texte);
		break;

	default:
		$charset = load_charset($charset);

		if (!is_array($CHARSET_REVERSE[$charset])) {
			$CHARSET_REVERSE[$charset] = array_flip($GLOBALS['CHARSET'][$charset]);
		}

		if (!isset($trans[$charset])){
			$trans[$charset]=array();
			$t = &$trans[$charset];
			for($e=128;$e<255;$e++){
				$h = dechex($e);
				if ($s = isset($CHARSET_REVERSE[$charset][$e])){
					$s = $CHARSET_REVERSE[$charset][$e];
					$t['&#'.$e.';'] = $t['&#0'.$e.';'] = $t['&#00'.$e.';'] = chr($s);
					$t['&#x'.$h.';'] = $t['&#x0'.$h.';'] = $t['&#x00'.$h.';'] = chr($s);
				}
				else{
					$t['&#'.$e.';'] = $t['&#0'.$e.';'] = $t['&#00'.$e.';'] = chr($e);
					$t['&#x'.$h.';'] = $t['&#x0'.$h.';'] = $t['&#x00'.$h.';'] = chr($e);
				}
			}
		}
		$texte = str_replace(array_keys($trans[$charset]),array_values($trans[$charset]),$texte);
		return $texte;
	}
}


// Importer un texte depuis un charset externe vers le charset du site
// (les caracteres non resolus sont transformes en &#123;)
// http://doc.spip.org/@importer_charset
function importer_charset($texte, $charset = 'AUTO') {
	static $trans = array();
	// on traite le cas le plus frequent iso-8859-1 vers utf directement pour aller plus vite !
	if (($charset == 'iso-8859-1') && ($GLOBALS['meta']['charset']=='utf-8')){
		$texte = corriger_caracteres_windows($texte, 'iso-8859-1',$GLOBALS['meta']['charset']);
		if (init_mb_string()) {
			if ($order = mb_detect_order() # mb_string connait-il $charset?
			AND mb_detect_order($charset)) {
				$s = mb_convert_encoding($texte, 'utf-8', $charset);
			}
			mb_detect_order($order); # remettre comme precedemment
			return $s;
		}
		// Sinon, peut-etre connaissons-nous ce charset ?
		if (!isset($trans[$charset])) {
			global $CHARSET;
			if ($cset = load_charset($charset)
			AND is_array($CHARSET[$cset]))
				foreach ($CHARSET[$cset] as $key => $val) {
					$trans[$charset][chr($key)] = unicode2charset('&#'.$val.';');
			}
		}
		if (count($trans[$charset]))
			return str_replace(array_keys($trans[$charset]),array_values($trans[$charset]),$texte);
		return $texte;
	}
	return unicode2charset(charset2unicode($texte, $charset));
}

// UTF-8
// http://doc.spip.org/@utf_8_to_unicode
function utf_8_to_unicode($source) {

	// mb_string : methode rapide
	if (init_mb_string()) {
		$convmap = array(0x7F, 0xFFFFFF, 0x0, 0xFFFFFF);
		return mb_encode_numericentity($source, $convmap, 'UTF-8');
	}

	// Sinon methode pas a pas
	static $decrement;
	static $shift;

	// Cf. php.net, par Ronen. Adapte pour compatibilite < php4
	if (!is_array($decrement)) {
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
	}

	$pos = 0;
	$len = strlen ($source);
	$encodedString = '';
	while ($pos < $len) {
		$char = '';
		$ischar = false;
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
			$ischar = true;
		}

		if ($ischar)
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
			$encodedLetter = "&#". preg_replace('/^0+/', '', $decimalCode) . ';';
			$encodedString .= $encodedLetter;
		}
	}
	return $encodedString;
}

// UTF-32 ne sert plus que si on passe par iconv, c'est-a-dire quand
// mb_string est absente ou ne connait pas notre charset
// mais on l'optimise quand meme par mb_string
// => tout ca sera osolete quand on sera surs d'avoir mb_string
// http://doc.spip.org/@utf_32_to_unicode
function utf_32_to_unicode($source) {

	// mb_string : methode rapide
	if (init_mb_string()) {
		$convmap = array(0x7F, 0xFFFFFF, 0x0, 0xFFFFFF);
		$source = mb_encode_numericentity($source, $convmap, 'UTF-32LE');
		return str_replace(chr(0), '', $source);
	}

	// Sinon methode lente
	$texte = '';
	while ($source) {
		$words = unpack("V*", substr($source, 0, 1024));
		$source = substr($source, 1024);
		foreach ($words as $word) {
			if ($word < 128)
				$texte .= chr($word);
			// ignorer le BOM - http://www.unicode.org/faq/utf_bom.html
			else if ($word != 65279)
				$texte .= '&#'.$word.';';
		}
	}
	return $texte;

}

// Ce bloc provient de php.net, auteur Ronen
// http://doc.spip.org/@caractere_utf_8
function caractere_utf_8($num) {
	if($num<128)
		return chr($num);
	if($num<2048)
		return chr(($num>>6)+192).chr(($num&63)+128);
	if($num<65536)
		return chr(($num>>12)+224).chr((($num>>6)&63)+128).chr(($num&63)+128);
	if($num<1114112)
		return chr($num>>18+240).chr((($num>>12)&63)+128).chr(($num>>6)&63+128). chr($num&63+128);
	return '';
}

// http://doc.spip.org/@unicode_to_utf_8
function unicode_to_utf_8($texte) {

	// 1. Entites &#128; et suivantes
	$vu = array();
	if (preg_match_all(',&#0*([1-9][0-9][0-9]+);,S',
	$texte, $regs, PREG_SET_ORDER))
	foreach ($regs as $reg) {
		if ($reg[1]>127 AND !isset($vu[$reg[0]]))
			$vu[$reg[0]] = caractere_utf_8($reg[1]);
	}
	//$texte = str_replace(array_keys($vu), array_values($vu), $texte);

	// 2. Entites > &#xFF;
	//$vu = array();
	if (preg_match_all(',&#x0*([1-9a-f][0-9a-f][0-9a-f]+);,iS',
	$texte, $regs, PREG_SET_ORDER))
	foreach ($regs as $reg) {
		if (!isset($vu[$reg[0]]))
			$vu[$reg[0]] = caractere_utf_8(hexdec($reg[1]));
	}
	return str_replace(array_keys($vu), array_values($vu), $texte);

}

// convertit les &#264; en \u0108
// http://doc.spip.org/@unicode_to_javascript
function unicode_to_javascript($texte) {
	$vu = array();
	while (preg_match(',&#0*([0-9]+);,S', $texte, $regs) AND !isset($vu[$regs[1]])) {
		$num = $regs[1];
		$vu[$num] = true;
		$s = '\u'.sprintf("%04x", $num);
		$texte = str_replace($regs[0], $s, $texte);
	}
	return $texte;
}

// convertit les %uxxxx (envoyes par javascript)
// http://doc.spip.org/@javascript_to_unicode
function javascript_to_unicode ($texte) {
	while (preg_match(",%u([0-9A-F][0-9A-F][0-9A-F][0-9A-F]),", $texte, $regs))
		$texte = str_replace($regs[0],"&#".hexdec($regs[1]).";", $texte);
	return $texte;
}
// convertit les %E9 (envoyes par le browser) en chaine du charset du site (binaire)
// http://doc.spip.org/@javascript_to_binary
function javascript_to_binary ($texte) {
	while (preg_match(",%([0-9A-F][0-9A-F]),", $texte, $regs))
		$texte = str_replace($regs[0],chr(hexdec($regs[1])), $texte);
	return $texte;
}


//
// Translitteration charset => ascii (pour l'indexation)
// Attention les caracteres non reconnus sont renvoyes en utf-8
//
// http://doc.spip.org/@translitteration
function translitteration($texte, $charset='AUTO', $complexe='') {
	static $trans;
	if ($charset == 'AUTO')
		$charset = $GLOBALS['meta']['charset'];

	$table_translit ='translit'.$complexe;

	// 0. Supprimer les caracteres illegaux
	include_spip('inc/filtres');
	$texte = corriger_caracteres($texte);

	// 1. Passer le charset et les &eacute en utf-8
	$texte = unicode_to_utf_8(html2unicode(charset2unicode($texte, $charset, true)));

	// 2. Translitterer grace a la table predefinie
	if (!$trans[$complexe]) {
		global $CHARSET;
		load_charset($table_translit);
		foreach ($CHARSET[$table_translit] as $key => $val)
			$trans[$complexe][caractere_utf_8($key)] = $val;
	}

	return str_replace(array_keys($trans[$complexe]),array_values($trans[$complexe]),$texte);
}

// &agrave; est retourne sous la forme "a`" et pas "a"
// mais si $chiffre=true, on retourne "a8" (vietnamien)
// http://doc.spip.org/@translitteration_complexe
function translitteration_complexe($texte, $chiffres=false) {
	$texte = translitteration($texte,'AUTO','complexe');

	if ($chiffres) {
		$texte = preg_replace("/[aeiuoyd]['`?~.^+(-]{1,2}/eS",
			"translitteration_chiffree('\\0')", $texte);
	}
	
	return $texte;
}
// http://doc.spip.org/@translitteration_chiffree
function translitteration_chiffree($car) {
	return strtr($car, "'`?~.^+(-", "123456789");
}


// Reconnaitre le BOM utf-8 (0xEFBBBF)
// http://doc.spip.org/@bom_utf8
function bom_utf8($texte) {
	return (substr($texte, 0,3) == chr(0xEF).chr(0xBB).chr(0xBF));
}
// Verifie qu'un document est en utf-8 valide
// http://us2.php.net/manual/fr/function.mb-detect-encoding.php#50087
// http://w3.org/International/questions/qa-forms-utf-8.html
// note: preg_replace permet de contourner un "stack overflow" sur PCRE
// http://doc.spip.org/@is_utf8
function is_utf8($string) {
	return !strlen(
	preg_replace(
	  ',[\x09\x0A\x0D\x20-\x7E]'            # ASCII
	. '|[\xC2-\xDF][\x80-\xBF]'             # non-overlong 2-byte
	. '|\xE0[\xA0-\xBF][\x80-\xBF]'         # excluding overlongs
	. '|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}'  # straight 3-byte
	. '|\xED[\x80-\x9F][\x80-\xBF]'         # excluding surrogates
	. '|\xF0[\x90-\xBF][\x80-\xBF]{2}'      # planes 1-3
	. '|[\xF1-\xF3][\x80-\xBF]{3}'          # planes 4-15
	. '|\xF4[\x80-\x8F][\x80-\xBF]{2}'      # plane 16
	. ',sS',
	'', $string));
}
// http://doc.spip.org/@is_ascii
function is_ascii($string) {
	return !strlen(
	preg_replace(
	',[\x09\x0A\x0D\x20-\x7E],sS',
	'', $string));
}

// Transcode une page (attrapee sur le web, ou un squelette) en essayant
// par tous les moyens de deviner son charset (y compris headers HTTP)
// http://doc.spip.org/@transcoder_page
function transcoder_page($texte, $headers='') {

	// Si tout est < 128 pas la peine d'aller plus loin
	if (is_ascii($texte)) {
		#spip_log('charset: ascii');
		return $texte;
	}

	// Reconnaitre le BOM utf-8 (0xEFBBBF)
	if (bom_utf8($texte)) {
		$charset = 'utf-8';
		$texte = substr($texte,3);
	}

	// charset precise par le contenu (xml)
	else if (preg_match(
	',<[?]xml[^>]*encoding[^>]*=[^>]*([-_a-z0-9]+?),UimsS', $texte, $regs))
		$charset = trim(strtolower($regs[1]));
	// charset precise par le contenu (html)
	else if (preg_match(
	',<(meta|html|body)[^>]*charset[^>]*=[^>]*([-_a-z0-9]+?),UimsS',
	$texte, $regs)
	# eviter #CHARSET des squelettes
	AND (($tmp = trim(strtolower($regs[2]))) != 'charset'))
		$charset = $tmp;
	// charset de la reponse http
	else if (preg_match(',charset=([-_a-z0-9]+),i', $headers, $regs))
		$charset = trim(strtolower($regs[1]));
	else $charset = '';
	// normaliser les noms du shif-jis japonais
	if (preg_match(',^(x|shift)[_-]s?jis$,i', $charset))
		$charset = 'shift-jis';

	if ($charset) {
		spip_log("charset: $charset");
	} else {
		// valeur par defaut
		if (is_utf8($texte))
			$charset = 'utf-8';
		else
			$charset = 'iso-8859-1';
		spip_log("charset probable: $charset");
	}

	return importer_charset($texte, $charset);
}


//
// Gerer les outils mb_string
//
// http://doc.spip.org/@spip_substr
function spip_substr($c, $start=0, $length = NULL) {
	// Si ce n'est pas utf-8, utiliser substr
	if ($GLOBALS['meta']['charset'] != 'utf-8') {
		if ($length)
			return substr($c, $start, $length);
		else
			substr($c, $start);
	}

	// Si utf-8, voir si on dispose de mb_string
	if (init_mb_string()) {
		if ($length)
			return mb_substr($c, $start, $length);
		else
			return mb_substr($c, $start);
	}

	// Version manuelle (cf. ci-dessous)
	return spip_substr_manuelle($c, $start, $length);
}

// version manuelle de substr utf8, pour php vieux et/ou mal installe
// http://doc.spip.org/@spip_substr_manuelle
function spip_substr_manuelle($c, $start, $length = NULL) {

	// Cas pathologique
	if ($length === 0)
		return '';

	// S'il y a un demarrage, on se positionne
	if ($start > 0)
		$c = substr($c, strlen(spip_substr_manuelle($c, 0, $start)));
	elseif ($start < 0)
		return spip_substr_manuelle($c, spip_strlen($c)+$start, $length);

	if (!$length)
		return $c;

	if ($length > 0) {
		// on prend n fois la longueur desiree, pour etre surs d'avoir tout
		// (un caractere utf-8 prenant au maximum n bytes)
		$n = 0; while (preg_match(',[\x80-\xBF]{'.(++$n).'},', $c));
		$c = substr($c, 0, $n*$length);
		// puis, tant qu'on est trop long, on coupe...
		while (($l = spip_strlen($c)) > $length)
			$c = substr($c, 0, $length - $l);
		return $c;
	}

	// $length < 0
	return spip_substr_manuelle($c, 0, spip_strlen($c)+$length);
}

// http://doc.spip.org/@spip_strlen
function spip_strlen($c) {
	// Si ce n'est pas utf-8, utiliser strlen
	if ($GLOBALS['meta']['charset'] != 'utf-8')
		return strlen($c);

	// Sinon, utiliser mb_strlen() si disponible
	if (init_mb_string())
		return mb_strlen($c);

	// Methode manuelle : on supprime les bytes 10......,
	// on compte donc les ascii (0.......) et les demarrages
	// de caracteres utf-8 (11......)
	return strlen(preg_replace(',[\x80-\xBF],S', '', $c));
}

// Initialisation
$GLOBALS['CHARSET'] = Array();

?>
