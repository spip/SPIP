<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

/**
 * Gestion des charsets et des conversions
 *
 * Ce fichier contient les fonctions relatives à la gestion de charsets,
 * à la conversion de textes dans différents charsets et
 * propose des fonctions émulant la librairie mb si elle est absente
 *
 * @package SPIP\Core\Texte\Charsets
 **/

// securité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// se faciliter la lecture du charset
include_spip('inc/config');

/**
 * Initialisation
 */
function init_charset(): void {
	// Initialisation
	$GLOBALS['CHARSET'] = [];

	// noter a l'occasion dans la meta pcre_u notre capacite a utiliser le flag /u
	// dans les preg_replace pour ne pas casser certaines lettres accentuees :
	// en utf-8 chr(195).chr(160) = a` alors qu'en iso-latin chr(160) = nbsp
	if (
		!isset($GLOBALS['meta']['pcre_u'])
		|| isset($_GET['var_mode']) && !isset($_GET['var_profile'])
	) {
		include_spip('inc/meta');
		ecrire_meta('pcre_u', (lire_config('charset', _DEFAULT_CHARSET) === 'utf-8') ? 'u' : '');
	}
}

// TODO: code d’exécution en dehors du fichier.
init_charset();


/**
 * Charge en mémoire la liste des caractères d'un charset
 *
 * Charsets supportés en natif : voir les tables dans ecrire/charsets/
 * Les autres charsets sont supportés via mbstring()
 *
 * @param string $charset
 *     Charset à charger.
 *     Par défaut (AUTO), utilise le charset du site
 * @return string|bool
 *     - Nom du charset
 *     - false si le charset n'est pas décrit dans le répertoire charsets/
 **/
function load_charset($charset = 'AUTO') {
	if ($charset == 'AUTO') {
		$charset = $GLOBALS['meta']['charset'];
	}
	$charset = trim(strtolower((string) $charset));
	if (isset($GLOBALS['CHARSET'][$charset])) {
		return $charset;
	}

	if ($charset == 'utf-8') {
		$GLOBALS['CHARSET'][$charset] = [];

		return $charset;
	}

	// Quelques synonymes
	if ($charset === '') {
		$charset = 'iso-8859-1';
	} elseif ($charset === 'windows-1250') {
		$charset = 'cp1250';
	} elseif ($charset === 'windows-1251') {
		$charset = 'cp1251';
	} elseif ($charset === 'windows-1256') {
		$charset = 'cp1256';
	}

	if (find_in_path($charset . '.php', 'charsets/', true)) {
		return $charset;
	} else {
		spip_log("Erreur: pas de fichier de conversion 'charsets/$charset'");
		$GLOBALS['CHARSET'][$charset] = [];

		return false;
	}
}


/**
 * Vérifier qu'on peut utiliser mb_string avec notre charset
 *
 * Les fonctions mb_* sont tout le temps présentes avec symfony/polyfill-mbstring
 *
 * @return bool
 *     true si notre charset est utilisable par mb_strsing
 **/
function init_mb_string(): bool {
	static $mb;

	// verifier que le charset interne est connu de mb_string
	if (!$mb) {
		if (mb_detect_order(lire_config('charset', _DEFAULT_CHARSET))) {
			mb_internal_encoding('utf-8');
			$mb = 1;
		} else {
			$mb = -1;
		}
	}

	return ($mb === 1);
}

/**
 * Test le fonctionnement correct d'iconv
 *
 * Celui-ci coupe sur certaines versions la chaine
 * quand un caractère n'appartient pas au charset
 *
 * @link http://php.net/manual/fr/function.iconv.php
 *
 * @return bool
 *     true si iconv fonctionne correctement
 **/
function test_iconv(): bool {
	static $iconv_ok;

	if (!$iconv_ok) {
		if (!function_exists('iconv')) {
			$iconv_ok = -1;
		} else {
			$iconv_ok = utf_32_to_unicode(@iconv('utf-8', 'utf-32', 'chaine de test')) === 'chaine de test' ? 1 : -1;
		}
	}

	return ($iconv_ok === 1);
}

/**
 * Corriger des caractères non-conformes : 128-159
 *
 * Cf. charsets/iso-8859-1.php (qu'on recopie ici pour aller plus vite)
 * On peut passer un charset cible en parametre pour accelerer le passage iso-8859-1 -> autre charset
 *
 * @param string|array $texte
 *     Le texte à corriger
 * @param string $charset
 *     Charset d'origine du texte
 *     Par défaut (AUTO) utilise le charset du site
 * @param string $charset_cible
 *     Charset de destination (unicode par défaut)
 * @return string|array
 *     texte corrigé
 **/
function corriger_caracteres_windows($texte, $charset = 'AUTO', $charset_cible = 'unicode') {
	static $trans;

	if (is_array($texte)) {
		return array_map('corriger_caracteres_windows', $texte);
	}

	if ($charset == 'AUTO') {
		$charset = lire_config('charset', _DEFAULT_CHARSET);
	}
	if ($charset == 'utf-8') {
		$p = chr(194);
		if (!str_contains($texte, $p)) {
			return $texte;
		}
	} else {
		if ($charset == 'iso-8859-1') {
			$p = '';
		} else {
			return $texte;
		}
	}

	if (!isset($trans[$charset][$charset_cible])) {
		$trans[$charset][$charset_cible] = [
			$p . chr(128) => '&#8364;',
			$p . chr(129) => ' ', # pas affecte
			$p . chr(130) => '&#8218;',
			$p . chr(131) => '&#402;',
			$p . chr(132) => '&#8222;',
			$p . chr(133) => '&#8230;',
			$p . chr(134) => '&#8224;',
			$p . chr(135) => '&#8225;',
			$p . chr(136) => '&#710;',
			$p . chr(137) => '&#8240;',
			$p . chr(138) => '&#352;',
			$p . chr(139) => '&#8249;',
			$p . chr(140) => '&#338;',
			$p . chr(141) => ' ', # pas affecte
			$p . chr(142) => '&#381;',
			$p . chr(143) => ' ', # pas affecte
			$p . chr(144) => ' ', # pas affecte
			$p . chr(145) => '&#8216;',
			$p . chr(146) => '&#8217;',
			$p . chr(147) => '&#8220;',
			$p . chr(148) => '&#8221;',
			$p . chr(149) => '&#8226;',
			$p . chr(150) => '&#8211;',
			$p . chr(151) => '&#8212;',
			$p . chr(152) => '&#732;',
			$p . chr(153) => '&#8482;',
			$p . chr(154) => '&#353;',
			$p . chr(155) => '&#8250;',
			$p . chr(156) => '&#339;',
			$p . chr(157) => ' ', # pas affecte
			$p . chr(158) => '&#382;',
			$p . chr(159) => '&#376;',
		];
		if ($charset_cible != 'unicode') {
			foreach ($trans[$charset][$charset_cible] as $k => $c) {
				$trans[$charset][$charset_cible][$k] = unicode2charset($c, $charset_cible);
			}
		}
	}

	return @str_replace(
		array_keys($trans[$charset][$charset_cible]),
		array_values($trans[$charset][$charset_cible]),
		$texte
	);
}


/**
 * Transforme les entités HTML en unicode
 *
 * Transforme les &eacute; en &#123;
 *
 * @param string $texte
 *     texte à convertir
 * @param bool $secure
 *     true pour *ne pas convertir* les caracteres malins &lt; &amp; etc.
 * @return string
 *     texte converti
 **/
function html2unicode($texte, $secure = false) {
	if (!str_contains($texte, '&')) {
		return $texte;
	}
	static $trans = [];
	if (!$trans) {
		load_charset('html');
		foreach ($GLOBALS['CHARSET']['html'] as $key => $val) {
			$trans["&$key;"] = $val;
		}
	}

	if ($secure) {
		return str_replace(array_keys($trans), array_values($trans), $texte);
	} else {
		return str_replace(
			['&amp;', '&quot;', '&lt;', '&gt;'],
			['&', '"', '<', '>'],
			str_replace(array_keys($trans), array_values($trans), $texte)
		);
	}
}


/**
 * Transforme les entités mathématiques (MathML) en unicode
 *
 * Transforme &angle; en &#x2220; ainsi que toutes autres entités mathématiques
 *
 * @param string $texte
 *     texte à convertir
 * @return string
 *     texte converti
 **/
function mathml2unicode($texte) {
	static $trans;
	if (!$trans) {
		load_charset('mathml');

		foreach ($GLOBALS['CHARSET']['mathml'] as $key => $val) {
			$trans["&$key;"] = $val;
		}
	}

	return str_replace(array_keys($trans), array_values($trans), $texte);
}


/**
 * Transforme une chaine en entites unicode &#129;
 *
 * Utilise la librairie mb si elle est présente.
 *
 * @internal
 *     Note: l'argument $forcer est obsolete : il visait a ne pas
 *     convertir les accents iso-8859-1
 *
 * @param string $texte
 *     texte à convertir
 * @param string $charset
 *     Charset actuel du texte
 *     Par défaut (AUTO), le charset est celui du site.
 * @return string
 *     texte converti en unicode
 **/
function charset2unicode($texte, $charset = 'AUTO' /* $forcer: obsolete*/) {
	static $trans;

	if ($charset === 'AUTO') {
		$charset = lire_config('charset', _DEFAULT_CHARSET);
	}

	if ($charset === '') {
		$charset = 'iso-8859-1';
	}
	$charset = strtolower((string) $charset);

	switch ($charset) {
		case 'utf-8':
		case 'utf8':
			return utf_8_to_unicode($texte);

		case 'iso-8859-1':
			$texte = corriger_caracteres_windows($texte, 'iso-8859-1');
		// pas de break; ici, on suit sur default:

		default:
			// mbstring presente ?
			if (init_mb_string()) {
				$order = mb_detect_order();
				try {
					# mb_string connait-il $charset?
					if ($order && mb_detect_order($charset)) {
						$s = mb_convert_encoding($texte, 'utf-8', $charset);
						if ($s && $s != $texte) {
							return utf_8_to_unicode($s);
						}
					}
				} catch (\Error) {
					// Le charset n'existe probablement pas
				} finally {
					mb_detect_order($order); # remettre comme precedemment
				}
			}

			// Sinon, peut-etre connaissons-nous ce charset ?
			if (!isset($trans[$charset]) && (($cset = load_charset($charset)) && is_array($GLOBALS['CHARSET'][$cset]))) {
				foreach ($GLOBALS['CHARSET'][$cset] as $key => $val) {
						$trans[$charset][chr($key)] = '&#' . $val . ';';
					}
			}
			if (isset($trans[$charset]) && (is_countable($trans[$charset]) ? count($trans[$charset]) : 0)) {
				return str_replace(array_keys($trans[$charset]), array_values($trans[$charset]), $texte);
			}

			// Sinon demander a iconv (malgre le fait qu'il coupe quand un
			// caractere n'appartient pas au charset, mais c'est un probleme
			// surtout en utf-8, gere ci-dessus)
			if (test_iconv()) {
				$s = iconv($charset, 'utf-32le', $texte);
				if ($s) {
					return utf_32_to_unicode($s);
				}
			}

			// Au pire ne rien faire
			spip_log("erreur charset '$charset' non supporte");

			return $texte;
	}
}


/**
 * Transforme les entites unicode &#129; dans le charset specifie
 *
 * Attention on ne transforme pas les entites < &#128; car si elles
 * ont ete encodees ainsi c'est a dessein
 *
 * @param string $texte
 *     texte unicode à transformer
 * @param string $charset
 *     Charset à appliquer au texte
 *     Par défaut (AUTO), le charset sera celui du site.
 * @return string
 *     texte transformé dans le charset souhaité
 **/
function unicode2charset($texte, $charset = 'AUTO') {
	static $CHARSET_REVERSE = [];
	static $trans = [];

	if ($charset == 'AUTO') {
		$charset = lire_config('charset', _DEFAULT_CHARSET);
	}

	switch ($charset) {
		case 'utf-8':
			return unicode_to_utf_8($texte);

		default:
			$charset = load_charset($charset);

			if (empty($CHARSET_REVERSE[$charset])) {
				$CHARSET_REVERSE[$charset] = array_flip($GLOBALS['CHARSET'][$charset]);
			}

			if (!isset($trans[$charset])) {
				$trans[$charset] = [];
				$t = &$trans[$charset];
				for ($e = 128; $e < 255; $e++) {
					$h = dechex($e);
					if ($s = isset($CHARSET_REVERSE[$charset][$e])) {
						$s = $CHARSET_REVERSE[$charset][$e];
						$t['&#' . $e . ';'] = $t['&#0' . $e . ';'] = $t['&#00' . $e . ';'] = chr($s);
						$t['&#x' . $h . ';'] = $t['&#x0' . $h . ';'] = $t['&#x00' . $h . ';'] = chr($s);
					} else {
						$t['&#' . $e . ';'] = $t['&#0' . $e . ';'] = $t['&#00' . $e . ';'] = chr($e);
						$t['&#x' . $h . ';'] = $t['&#x0' . $h . ';'] = $t['&#x00' . $h . ';'] = chr($e);
					}
				}
			}

			return str_replace(array_keys($trans[$charset]), array_values($trans[$charset]), $texte);
	}
}


/**
 * Importer un texte depuis un charset externe vers le charset du site
 *
 * Les caractères non resolus sont transformés en `&#123`;
 *
 * @param string $texte
 *     texte unicode à importer
 * @param string $charset
 *     Charset d'origine du texte
 *     Par défaut (AUTO), le charset d'origine est celui du site.
 * @return string
 *     texte transformé dans le charset site
 **/
function importer_charset($texte, $charset = 'AUTO') {
	$s = null;
	static $trans = [];
	// on traite le cas le plus frequent iso-8859-1 vers utf directement pour aller plus vite !
	if (($charset == 'iso-8859-1') && ($GLOBALS['meta']['charset'] == 'utf-8')) {
		$texte = corriger_caracteres_windows($texte, 'iso-8859-1', $GLOBALS['meta']['charset']);
		if (init_mb_string()) {
			if (
				($order = mb_detect_order())
				&& mb_detect_order($charset)
			) {
				$s = mb_convert_encoding($texte, 'utf-8', $charset);
			}
			mb_detect_order($order); # remettre comme precedemment
			return $s;
		}
		// Sinon, peut-etre connaissons-nous ce charset ?
		if (
			!isset($trans[$charset])
			&& (($cset = load_charset($charset))
			&& is_array($GLOBALS['CHARSET'][$cset]))
		) {
			foreach ($GLOBALS['CHARSET'][$cset] as $key => $val) {
				$trans[$charset][chr($key)] = unicode2charset('&#' . $val . ';');
			}
		}
		if (is_countable($trans[$charset]) ? count($trans[$charset]) : 0) {
			return str_replace(array_keys($trans[$charset]), array_values($trans[$charset]), $texte);
		}

		return $texte;
	}

	return unicode2charset(charset2unicode($texte, $charset));
}


/**
 * Transforme un texte UTF-8 en unicode
 *
 * Utilise la librairie mb si présente
 *
 * @param string $source
 *    texte UTF-8 à transformer
 * @return string
 *    texte transformé en unicode
 **/
function utf_8_to_unicode($source) {

	// mb_string : methode rapide
	if (init_mb_string()) {
		$convmap = [0x7F, 0xFFFFFF, 0x0, 0xFFFFFF];

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
	$len = strlen($source);
	$encodedString = '';
	while ($pos < $len) {
		$char = '';
		$ischar = false;
		$asciiPos = ord(substr($source, $pos, 1));
		if (($asciiPos >= 240) && ($asciiPos <= 255)) {
			// 4 chars representing one unicode character
			$thisLetter = substr($source, $pos, 4);
			$pos += 4;
		} else {
			if (($asciiPos >= 224) && ($asciiPos <= 239)) {
				// 3 chars representing one unicode character
				$thisLetter = substr($source, $pos, 3);
				$pos += 3;
			} else {
				if (($asciiPos >= 192) && ($asciiPos <= 223)) {
					// 2 chars representing one unicode character
					$thisLetter = substr($source, $pos, 2);
					$pos += 2;
				} else {
					// 1 char (lower ascii)
					$thisLetter = substr($source, $pos, 1);
					$pos += 1;
					$char = $thisLetter;
					$ischar = true;
				}
			}
		}

		if ($ischar) {
			$encodedString .= $char;
		} else {  // process the string representing the letter to a unicode entity
			$thisLen = strlen($thisLetter);
			$thisPos = 0;
			$decimalCode = 0;
			while ($thisPos < $thisLen) {
				$thisCharOrd = ord(substr($thisLetter, $thisPos, 1));
				if ($thisPos == 0) {
					$charNum = (int) ($thisCharOrd - $decrement[$thisLen]);
					$decimalCode += ($charNum << $shift[$thisLen][$thisPos]);
				} else {
					$charNum = (int) ($thisCharOrd - 128);
					$decimalCode += ($charNum << $shift[$thisLen][$thisPos]);
				}
				$thisPos++;
			}
			$encodedLetter = '&#' . preg_replace('/^0+/', '', $decimalCode) . ';';
			$encodedString .= $encodedLetter;
		}
	}

	return $encodedString;
}

/**
 * Transforme un texte UTF-32 en unicode
 *
 * UTF-32 ne sert plus que si on passe par iconv, c'est-a-dire quand
 * mb_string est absente ou ne connait pas notre charset.
 *
 * Mais on l'optimise quand meme par mb_string
 * => tout ca sera osolete quand on sera surs d'avoir mb_string
 *
 * @param string $source
 *    texte UTF-8 à transformer
 * @return string
 *    texte transformé en unicode
 **/
function utf_32_to_unicode($source) {

	// mb_string : methode rapide
	if (init_mb_string()) {
		$convmap = [0x7F, 0xFFFFFF, 0x0, 0xFFFFFF];
		$source = mb_encode_numericentity($source, $convmap, 'UTF-32LE');

		return str_replace(chr(0), '', $source);
	}

	// Sinon methode lente
	$texte = '';
	while ($source) {
		$words = unpack('V*', substr($source, 0, 1024));
		$source = substr($source, 1024);
		foreach ($words as $word) {
			if ($word < 128) {
				$texte .= chr($word);
			} // ignorer le BOM - http://www.unicode.org/faq/utf_bom.html
			else {
				if ($word != 65279) {
					$texte .= '&#' . $word . ';';
				}
			}
		}
	}

	return $texte;
}


/**
 * Transforme un numéro unicode en caractère utf-8
 *
 * Ce bloc provient de php.net
 *
 * @author Ronen
 *
 * @param int $num
 *    Numéro de l'entité unicode
 * @return string
 *    Caractère utf8 si trouvé, '' sinon
 **/
function caractere_utf_8($num) {
	$num = (int) $num;
	if ($num < 128) {
		return chr($num);
	}
	if ($num < 2048) {
		return chr(($num >> 6) + 192) . chr(($num & 63) + 128);
	}
	if ($num < 65536) {
		return chr(($num >> 12) + 224) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
	}
	if ($num < 1_114_112) {
		return chr(($num >> 18) + 240) . chr((($num >> 12) & 63) + 128) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
	}

	return '';
}

/**
 * Convertit un texte unicode en utf-8
 *
 * @param string $texte
 *     texte à convertir
 * @return string
 *     texte converti
 **/
function unicode_to_utf_8($texte) {

	// 1. Entites &#128; et suivantes
	$vu = [];
	if (
		preg_match_all(
			',&#0*([1-9]\d\d+);,S',
			$texte,
			$regs,
			PREG_SET_ORDER
		)
	) {
		foreach ($regs as $reg) {
			if ($reg[1] > 127 && !isset($vu[$reg[0]])) {
				$vu[$reg[0]] = caractere_utf_8($reg[1]);
			}
		}
	}
	//$texte = str_replace(array_keys($vu), array_values($vu), $texte);

	// 2. Entites > &#xFF;
	//$vu = array();
	if (
		preg_match_all(
			',&#x0*([1-9a-f][0-9a-f][0-9a-f]+);,iS',
			$texte,
			$regs,
			PREG_SET_ORDER
		)
	) {
		foreach ($regs as $reg) {
			if (!isset($vu[$reg[0]])) {
				$vu[$reg[0]] = caractere_utf_8(hexdec($reg[1]));
			}
		}
	}

	return str_replace(array_keys($vu), array_values($vu), $texte);
}

/**
 * Convertit les unicode &#264; en javascript \u0108
 *
 * @param string $texte
 *     texte à convertir
 * @return string
 *     texte converti
 **/
function unicode_to_javascript($texte) {
	$vu = [];
	while (preg_match(',&#0*(\d+);,S', $texte, $regs) && !isset($vu[$regs[1]])) {
		$num = $regs[1];
		$vu[$num] = true;
		$s = '\u' . sprintf('%04x', $num);
		$texte = str_replace($regs[0], $s, $texte);
	}

	return $texte;
}

/**
 * Convertit les %uxxxx (envoyés par javascript) en &#yyy unicode
 *
 * @param string $texte
 *     texte à convertir
 * @return string
 *     texte converti
 **/
function javascript_to_unicode($texte) {
	while (preg_match(',%u([0-9A-F][0-9A-F][0-9A-F][0-9A-F]),', $texte, $regs)) {
		$texte = str_replace($regs[0], '&#' . hexdec($regs[1]) . ';', $texte);
	}

	return $texte;
}

/**
 * Convertit les %E9 (envoyés par le browser) en chaîne du charset du site (binaire)
 *
 * @param string $texte
 *     texte à convertir
 * @return string
 *     texte converti
 **/
function javascript_to_binary($texte) {
	while (preg_match(',%([0-9A-F][0-9A-F]),', $texte, $regs)) {
		$texte = str_replace($regs[0], chr(hexdec($regs[1])), $texte);
	}

	return $texte;
}


/**
 * Substition rapide de chaque graphème selon le charset sélectionné.
 *
 * @uses caractere_utf_8()
 *
 * @global array $CHARSET
 * @staticvar array $trans
 *
 * @param string $texte
 * @param string $charset
 * @param string $complexe
 * @return string
 */
function translitteration_rapide($texte, $charset = 'AUTO', $complexe = ''): string {
	static $trans = [];
	if ($charset == 'AUTO') {
		$charset = $GLOBALS['meta']['charset'];
	}
	if (!strlen($texte)) {
		return $texte;
	}

	$table_translit = 'translit' . $complexe;

	// 2. Translitterer grace a la table predefinie
	if (!isset($trans[$complexe])) {
		$trans[$complexe] = [];
		load_charset($table_translit);
		foreach ($GLOBALS['CHARSET'][$table_translit] as $key => $val) {
			$trans[$complexe][caractere_utf_8($key)] = $val;
		}
	}

	return str_replace(array_keys($trans[$complexe]), array_values($trans[$complexe]), $texte);
}

/**
 * Translittération charset => ascii (pour l'indexation)
 *
 * Permet, entre autres, d’enlever les accents,
 * car la table ASCII non étendue ne les comporte pas.
 *
 * Attention les caractères non reconnus sont renvoyés en utf-8
 *
 * @uses corriger_caracteres()
 * @uses unicode_to_utf_8()
 * @uses html2unicode()
 * @uses charset2unicode()
 * @uses translitteration_rapide()
 *
 * @param string $texte
 * @param string $charset
 * @param string $complexe
 * @return string
 */
function translitteration($texte, $charset = 'AUTO', $complexe = ''): string {
	// 0. Supprimer les caracteres illegaux
	include_spip('inc/filtres');
	$texte = corriger_caracteres($texte);

	// 1. Passer le charset et les &eacute en utf-8
	$texte = unicode_to_utf_8(html2unicode(charset2unicode($texte, $charset)));

	return translitteration_rapide($texte, $charset, $complexe);
}

/**
 * Translittération complexe
 *
 * `&agrave;` est retourné sous la forme ``a` `` et pas `à`
 * mais si `$chiffre=true`, on retourne `a8` (vietnamien)
 *
 * @uses translitteration()
 * @param string $texte
 * @param bool $chiffres
 * @return string
 */
function translitteration_complexe($texte, $chiffres = false): string {
	$texte = translitteration($texte, 'AUTO', 'complexe');

	if ($chiffres) {
		$texte = preg_replace_callback(
			"/[aeiuoyd]['`?~.^+(-]{1,2}/S",
			fn($m) => translitteration_chiffree($m[0]),
			$texte
		);
	}

	return $texte;
}

/**
 * Translittération chiffrée
 *
 * Remplace des caractères dans une chaîne par des chiffres
 *
 * @param string $car
 * @return string
 */
function translitteration_chiffree($car): string {
	return strtr($car, "'`?~.^+(-", '123456789');
}


/**
 * Reconnaitre le BOM utf-8 (0xEFBBBF)
 *
 * @param string $texte
 *    texte dont on vérifie la présence du BOM
 * @return bool
 *    true s'il a un BOM
 **/
function bom_utf8($texte): bool {
	return (substr($texte, 0, 3) === chr(0xEF) . chr(0xBB) . chr(0xBF));
}

/**
 * Vérifie qu'une chaîne est en utf-8 valide
 *
 * Note: preg_replace permet de contourner un "stack overflow" sur PCRE
 *
 * @link http://us2.php.net/manual/fr/function.mb-detect-encoding.php#50087
 * @link http://w3.org/International/questions/qa-forms-utf-8.html
 *
 * @param string $string
 *     texte dont on vérifie qu'il est de l'utf-8
 * @return bool
 *     true si c'est le cas
 **/
function is_utf8($string): bool {
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
			'',
			$string
		)
	);
}

/**
 * Vérifie qu'une chaîne est en ascii valide
 *
 * @param string $string
 *     texte dont on vérifie qu'il est de l'ascii
 * @return bool
 *     true si c'est le cas
 **/
function is_ascii($string): bool {
	return !strlen(
		preg_replace(
			',[\x09\x0A\x0D\x20-\x7E],sS',
			'',
			$string
		)
	);
}

/**
 * Transcode une page vers le charset du site
 *
 * Transcode une page (attrapée sur le web, ou un squelette) vers le
 * charset du site en essayant par tous les moyens de deviner son charset
 * (y compris dans les headers HTTP)
 *
 * @param string $texte
 *     Page à transcoder, dont on souhaite découvrir son charset
 * @param string $headers
 *     Éventuels headers HTTP liés à cette page
 * @return string
 *     texte transcodé dans le charset du site
 **/
function transcoder_page($texte, $headers = ''): string {

	// Si tout est < 128 pas la peine d'aller plus loin
	if (is_ascii($texte)) {
		#spip_log('charset: ascii');
		return $texte;
	}

	if (bom_utf8($texte)) {
		// Reconnaitre le BOM utf-8 (0xEFBBBF)
		$charset = 'utf-8';
		$texte = substr($texte, 3);
	} elseif (preg_match(',<[?]xml[^>]*encoding[^>]*=[^>]*([-_a-z0-9]+?),UimsS', $texte, $regs)) {
		// charset precise par le contenu (xml)
		$charset = trim(strtolower($regs[1]));
	} elseif (
		// charset precise par le contenu (html)
		preg_match(',<(meta|html|body)[^>]*charset[^>]*=[^>]*([#-_a-z0-9]+?),UimsS', $texte, $regs)
		# eviter toute balise SPIP tel que #CHARSET ou #CONFIG d'un squelette

		&& !str_contains($regs[2], '#')
		&& ($tmp = trim(strtolower($regs[2])))
	) {
		$charset = $tmp;
	} elseif (preg_match(',charset=([-_a-z0-9]+),i', $headers, $regs)) {
		// charset de la reponse http
		$charset = trim(strtolower($regs[1]));
	} else {
		$charset = '';
	}


	// normaliser les noms du shif-jis japonais
	if (preg_match(',^(x|shift)[_-]s?jis$,i', $charset)) {
		$charset = 'shift-jis';
	}

	if ($charset) {
		spip_log("charset: $charset");
	} else {
		// valeur par defaut
		$charset = is_utf8($texte) ? 'utf-8' : 'iso-8859-1';
		spip_log("charset probable: $charset");
	}

	return importer_charset($texte, $charset);
}


//
// Gerer les outils mb_string
//

/**
 * Coupe un texte selon substr()
 *
 * Coupe une chaîne en utilisant les outils mb* lorsque le site est en utf8
 *
 * @link http://fr.php.net/manual/fr/function.mb-substr.php
 * @link http://www.php.net/manual/fr/function.substr.php
 * @uses spip_substr_manuelle() si les fonctions php mb sont absentes
 *
 * @param string $c Le texte
 * @param int $start Début
 * @param null|int $length Longueur ou fin
 * @return string
 *     Le texte coupé
 **/
function spip_substr($c, $start = 0, $length = null) {
	if ($GLOBALS['meta']['charset'] !== 'utf-8') {
		if ($length) {
			return substr($c, $start, $length);
		} else {
			return substr($c, $start);
		}
	}

	if ($length) {
		return mb_substr($c, $start, $length);
	} else {
		return mb_substr($c, $start);
	}
}

/**
 * Rend majuscule le premier caractère d'une chaîne utf-8
 *
 * Version utf-8 d'ucfirst
 *
 * @param string $c
 *     La chaîne à transformer
 * @return string
 *     La chaîne avec une majuscule sur le premier mot
 */
function spip_ucfirst($c) {
	if ($GLOBALS['meta']['charset'] !== 'utf-8') {
		return ucfirst($c);
	}

	$lettre1 = mb_strtoupper(spip_substr($c, 0, 1));

	return $lettre1 . spip_substr($c, 1);
}

/**
 * Passe une chaîne utf-8 en minuscules
 *
 * Version utf-8 de strtolower
 *
 * @param string $c
 *     La chaîne à transformer
 * @return string
 *     La chaîne en minuscules
 */
function spip_strtolower($c) {
	if ($GLOBALS['meta']['charset'] !== 'utf-8') {
		return strtolower($c);
	}

	return mb_strtolower($c);
}

/**
 * Retourne la longueur d'une chaîne utf-8
 *
 * Version utf-8 de strlen
 *
 * @param string $c
 *     La chaîne à compter
 * @return int
 *     Longueur de la chaîne
 */
function spip_strlen($c) {
	// On transforme les sauts de ligne pour ne pas compter deux caractères
	$c = str_replace("\r\n", "\n", $c);

	// Si ce n'est pas utf-8, utiliser strlen
	if ($GLOBALS['meta']['charset'] !== 'utf-8') {
		return strlen($c);
	}

	return mb_strlen($c);
}

/**
 * Transforme une chaîne utf-8 en utf-8 sans "planes"
 * ce qui permet de la donner à MySQL "utf8", qui n'est pas un utf-8 complet
 * L'alternative serait d'utiliser utf8mb4
 *
 * @param string $x
 *     La chaîne à transformer
 * @return string
 *     La chaîne avec les caractères utf8 des hauts "planes" échappée
 *     en unicode : &#128169;
 */
function utf8_noplanes($x): string {
	$regexp_utf8_4bytes = '/(
      \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
   | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
   |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
)/xS';
	if (preg_match_all($regexp_utf8_4bytes, $x, $z, PREG_PATTERN_ORDER)) {
		foreach ($z[0] as $k) {
			$ku = utf_8_to_unicode($k);
			$x = str_replace($k, $ku, $x);
		}
	}

	return $x;
}
