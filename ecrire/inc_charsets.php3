<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_CHARSETS")) return;
define("_ECRIRE_INC_CHARSETS", "1");


/* charsets supportes en natif (les autres via mb_string) :
	utf-8 ;
	iso-8859-1 ; iso-8859-9 ; iso-8859-15 ;
	windows-1251  = CP1251 ;
*/
function load_charset ($charset = 'AUTO', $langue_site = 'AUTO') {
	if ($charset == 'AUTO')
		$charset = lire_meta('charset');
	if (is_array($GLOBALS['CHARSET'][$charset]))
		return $charset;

	if ($langue_site == 'AUTO')
		$langue_site = lire_meta('langue_site');

	switch (strtolower($charset)) {
	case 'utf-8':
		$GLOBALS['CHARSET'][$charset] = array();
		return $charset;

	// iso latin 1
	case 'iso-8859-1':
	case '':
		$GLOBALS['CHARSET'][$charset] = array (
		128=>128, 129=>129, 130=>130, 131=>131, 132=>132, 133=>133, 134=>134, 135=>135,
		136=>136, 137=>137, 138=>138, 139=>139, 140=>140, 141=>141, 142=>142, 143=>143,
		144=>144, 145=>145, 146=>146, 147=>147, 148=>148, 149=>149, 150=>150, 151=>151,
		152=>152, 153=>153, 154=>154, 155=>155, 156=>156, 157=>157, 158=>158, 159=>159,
		160=>160, 161=>161, 162=>162, 163=>163, 164=>164, 165=>165, 166=>166, 167=>167,
		168=>168, 169=>169, 170=>170, 171=>171, 172=>172, 173=>173, 174=>174, 175=>175,
		176=>176, 177=>177, 178=>178, 179=>179, 180=>180, 181=>181, 182=>182, 183=>183,
		184=>184, 185=>185, 186=>186, 187=>187, 188=>188, 189=>189, 190=>190, 191=>191,
		192=>192, 193=>193, 194=>194, 195=>195, 196=>196, 197=>197, 198=>198, 199=>199,
		200=>200, 201=>201, 202=>202, 203=>203, 204=>204, 205=>205, 206=>206, 207=>207,
		208=>208, 209=>209, 210=>210, 211=>211, 212=>212, 213=>213, 214=>214, 215=>215,
		216=>216, 217=>217, 218=>218, 219=>219, 220=>220, 221=>221, 222=>222, 223=>223,
		224=>224, 225=>225, 226=>226, 227=>227, 228=>228, 229=>229, 230=>230, 231=>231,
		232=>232, 233=>233, 234=>234, 235=>235, 236=>236, 237=>237, 238=>238, 239=>239,
		240=>240, 241=>241, 242=>242, 243=>243, 244=>244, 245=>245, 246=>246, 247=>247,
		248=>248, 249=>249, 250=>250, 251=>251, 252=>252, 253=>253, 254=>254, 255=>255
		);
		return $charset;


	// iso latin 9 - Turc <alexis@nds.k12.tr>
	case 'iso-8859-9':
		load_charset('iso-8859-1');
		$trans = $GLOBALS['CHARSET']['iso-8859-1'];
		$trans[240]=287; //gbreve
		$trans[208]=286; //Gbreve
		$trans[221]=304; //Idot
		$trans[253]=305; //inodot
		$trans[254]=351; //scedil
		$trans[222]=350; //Scedil
		$GLOBALS['CHARSET'][$charset] = $trans;
		return $charset;


	// iso latin 15 - Gaetan Ryckeboer <gryckeboer@virtual-net.fr>
	case 'iso-8859-15':
		load_charset('iso-8859-1');
		$trans = $GLOBALS['CHARSET']['iso-8859-1'];
		$trans[164]=8364;
		$trans[166]=352;
		$trans[168]=353;
		$trans[180]=381;
		$trans[184]=382;
		$trans[188]=338;
		$trans[189]=339;
		$trans[190]=376;
		$GLOBALS['CHARSET'][$charset] = $trans;
		return $charset;


	// cyrillic - ref. http://czyborra.com/charsets/cyrillic.html
	case 'windows-1251':
	case 'cp1251':
		$GLOBALS['CHARSET'][$charset] = array (
		0x80=>0x0402, 0x81=>0x0403, 0x82=>0x201A, 0x83=>0x0453, 0x84=>0x201E,
		0x85=>0x2026, 0x86=>0x2020, 0x87=>0x2021, 0x88=>0x20AC, 0x89=>0x2030,
		0x8A=>0x0409, 0x8B=>0x2039, 0x8C=>0x040A, 0x8D=>0x040C, 0x8E=>0x040B,
		0x8F=>0x040F, 0x90=>0x0452, 0x91=>0x2018, 0x92=>0x2019, 0x93=>0x201C,
		0x94=>0x201D, 0x95=>0x2022, 0x96=>0x2013, 0x97=>0x2014, 0x99=>0x2122,
		0x9A=>0x0459, 0x9B=>0x203A, 0x9C=>0x045A, 0x9D=>0x045C, 0x9E=>0x045B,
		0x9F=>0x045F, 0xA0=>0x00A0, 0xA1=>0x040E, 0xA2=>0x045E, 0xA3=>0x0408,
		0xA4=>0x00A4, 0xA5=>0x0490, 0xA6=>0x00A6, 0xA7=>0x00A7, 0xA8=>0x0401,
		0xA9=>0x00A9, 0xAA=>0x0404, 0xAB=>0x00AB, 0xAC=>0x00AC, 0xAD=>0x00AD,
		0xAE=>0x00AE, 0xAF=>0x0407, 0xB0=>0x00B0, 0xB1=>0x00B1, 0xB2=>0x0406,
		0xB3=>0x0456, 0xB4=>0x0491, 0xB5=>0x00B5, 0xB6=>0x00B6, 0xB7=>0x00B7,
		0xB8=>0x0451, 0xB9=>0x2116, 0xBA=>0x0454, 0xBB=>0x00BB, 0xBC=>0x0458,
		0xBD=>0x0405, 0xBE=>0x0455, 0xBF=>0x0457, 0xC0=>0x0410, 0xC1=>0x0411,
		0xC2=>0x0412, 0xC3=>0x0413, 0xC4=>0x0414, 0xC5=>0x0415, 0xC6=>0x0416,
		0xC7=>0x0417, 0xC8=>0x0418, 0xC9=>0x0419, 0xCA=>0x041A, 0xCB=>0x041B,
		0xCC=>0x041C, 0xCD=>0x041D, 0xCE=>0x041E, 0xCF=>0x041F, 0xD0=>0x0420,
		0xD1=>0x0421, 0xD2=>0x0422, 0xD3=>0x0423, 0xD4=>0x0424, 0xD5=>0x0425,
		0xD6=>0x0426, 0xD7=>0x0427, 0xD8=>0x0428, 0xD9=>0x0429, 0xDA=>0x042A,
		0xDB=>0x042B, 0xDC=>0x042C, 0xDD=>0x042D, 0xDE=>0x042E, 0xDF=>0x042F,
		0xE0=>0x0430, 0xE1=>0x0431, 0xE2=>0x0432, 0xE3=>0x0433, 0xE4=>0x0434,
		0xE5=>0x0435, 0xE6=>0x0436, 0xE7=>0x0437, 0xE8=>0x0438, 0xE9=>0x0439,
		0xEA=>0x043A, 0xEB=>0x043B, 0xEC=>0x043C, 0xED=>0x043D, 0xEE=>0x043E,
		0xEF=>0x043F, 0xF0=>0x0440, 0xF1=>0x0441, 0xF2=>0x0442, 0xF3=>0x0443,
		0xF4=>0x0444, 0xF5=>0x0445, 0xF6=>0x0446, 0xF7=>0x0447, 0xF8=>0x0448,
		0xF9=>0x0449, 0xFA=>0x044A, 0xFB=>0x044B, 0xFC=>0x044C, 0xFD=>0x044D,
		0xFE=>0x044E, 0xFF=>0x044F); // fin windows-1251
		return $charset;
	
	// arabic - george kandalaft - http://www.microsoft.com/typography/unicode/1256.htm
	case 'windows-1256':
	case 'cp1256':
		$GLOBALS['CHARSET'][$charset] = array (
		0x80=>0x20AC, 0x81=>0x067E, 0x82=>0x201A, 0x83=>0x0192, 0x84=>0x201E,
		0x85=>0x2026, 0x86=>0x2020, 0x87=>0x2021, 0x88=>0x02C6, 0x89=>0x2030,
		0x8A=>0x0679, 0x8B=>0x2039, 0x8C=>0x0152, 0x8D=>0x0686, 0x8E=>0x0698,
		0x8F=>0x0688, 0x90=>0x06AF, 0x91=>0x2018, 0x92=>0x2019, 0x93=>0x201C,
		0x94=>0x201D, 0x95=>0x2022, 0x96=>0x2013, 0x97=>0x2014, 0x98=>0x06A9,
		0x99=>0x2122, 0x9A=>0x0691, 0x9B=>0x203A, 0x9C=>0x0153, 0x9D=>0x200C,
		0x9E=>0x200D, 0x9F=>0x06BA, 0xA0=>0x00A0, 0xA1=>0x060C, 0xA2=>0x00A2,
		0xA3=>0x00A3, 0xA4=>0x00A4, 0xA5=>0x00A5, 0xA6=>0x00A6, 0xA7=>0x00A7,
		0xA8=>0x00A8, 0xA9=>0x00A9, 0xAA=>0x06BE, 0xAB=>0x00AB, 0xAC=>0x00AC,
		0xAD=>0x00AD, 0xAE=>0x00AE, 0xAF=>0x00AF, 0xB0=>0x00B0, 0xB1=>0x00B1,
		0xB2=>0x00B2, 0xB3=>0x00B3, 0xB4=>0x00B4, 0xB5=>0x00B5, 0xB6=>0x00B6,
		0xB7=>0x00B7, 0xB8=>0x00B8, 0xB9=>0x00B9, 0xBA=>0x061B, 0xBB=>0x00BB,
		0xBC=>0x00BC, 0xBD=>0x00BD, 0xBE=>0x00BE, 0xBF=>0x061F, 0xC0=>0x06C1,
		0xC1=>0x0621, 0xC2=>0x0622, 0xC3=>0x0623, 0xC4=>0x0624, 0xC5=>0x0625,
		0xC6=>0x0626, 0xC7=>0x0627, 0xC8=>0x0628, 0xC9=>0x0629, 0xCA=>0x062A,
		0xCB=>0x062B, 0xCC=>0x062C, 0xCD=>0x062D, 0xCE=>0x062E, 0xCF=>0x062F,
		0xD0=>0x0630, 0xD1=>0x0631, 0xD2=>0x0632, 0xD3=>0x0633, 0xD4=>0x0634,
		0xD5=>0x0635, 0xD6=>0x0636, 0xD7=>0x00D7, 0xD8=>0x0637, 0xD9=>0x0638,
		0xDA=>0x0639, 0xDB=>0x063A, 0xDC=>0x0640, 0xDD=>0x0641, 0xDE=>0x0642,
		0xDF=>0x0643, 0xE0=>0x00E0, 0xE1=>0x0644, 0xE2=>0x00E2, 0xE3=>0x0645,
		0xE4=>0x0646, 0xE5=>0x0647, 0xE6=>0x0648, 0xE7=>0x00E7, 0xE8=>0x00E8,
		0xE9=>0x00E9, 0xEA=>0x00EA, 0xEB=>0x00EB, 0xEC=>0x0649, 0xED=>0x064A,
		0xEE=>0x00EE, 0xEF=>0x00EF, 0xF0=>0x064B, 0xF1=>0x064C, 0xF2=>0x064D,
		0xF3=>0x064E, 0xF4=>0x00F4, 0xF5=>0x064F, 0xF6=>0x0650, 0xF7=>0x00F7,
		0xF8=>0x0651, 0xF9=>0x00F9, 0xFA=>0x0652, 0xFB=>0x00FB, 0xFC=>0x00FC,
		0xFD=>0x200E, 0xFE=>0x200F, 0xFF=>0x06D2); // fin windows-1256
		return $charset;
	// arabic iso-8859-6 - http://czyborra.com/charsets/iso8859.html#ISO-8859-6
	case 'iso-8859-6':
		load_charset('iso-8859-1');
		$trans = $GLOBALS['CHARSET']['iso-8859-1'];
		$mod = Array(
		0xA0=>0x00A0, 0xA4=>0x00A4, 0xAC=>0x060C, 0xAD=>0x00AD, 0xBB=>0x061B,
		0xBF=>0x061F, 0xC1=>0x0621, 0xC2=>0x0622, 0xC3=>0x0623, 0xC4=>0x0624,
		0xC5=>0x0625, 0xC6=>0x0626, 0xC7=>0x0627, 0xC8=>0x0628, 0xC9=>0x0629,
		0xCA=>0x062A, 0xCB=>0x062B, 0xCC=>0x062C, 0xCD=>0x062D, 0xCE=>0x062E,
		0xCF=>0x062F, 0xD0=>0x0630, 0xD1=>0x0631, 0xD2=>0x0632, 0xD3=>0x0633,
		0xD4=>0x0634, 0xD5=>0x0635, 0xD6=>0x0636, 0xD7=>0x0637, 0xD8=>0x0638,
		0xD9=>0x0639, 0xDA=>0x063A, 0xE0=>0x0640, 0xE1=>0x0641, 0xE2=>0x0642,
		0xE3=>0x0643, 0xE4=>0x0644, 0xE5=>0x0645, 0xE6=>0x0646, 0xE7=>0x0647,
		0xE8=>0x0648, 0xE9=>0x0649, 0xEA=>0x064A, 0xEB=>0x064B, 0xEC=>0x064C,
		0xED=>0x064D, 0xEE=>0x064E, 0xEF=>0x064F, 0xF0=>0x0650, 0xF1=>0x0651,
		0xF2=>0x0652
		);
		foreach ($mod as $num=>$val)
			$trans[$num]=$val;
		$GLOBALS['CHARSET'][$charset] = $trans;
		return $charset;

	// ------------------------------------------------------------------

	// cas particulier pour les entites html (a completer eventuellement)
	case 'html':
		$GLOBALS['CHARSET'][$charset] = array (
		'ldquo'=>'&#147;', 'rdquo'=>'&#148;',
		'cent'=>'&#162;', 'pound'=>'&#163;', 'curren'=>'&#164;', 'yen'=>'&#165;', 'brvbar'=>'&#166;',
		'sect'=>'&#167;', 'uml'=>'&#168;', 'ordf'=>'&#170;', 'laquo'=>'&#171;', 'not'=>'&#172;',
		'shy'=>'&#173;', 'macr'=>'&#175;', 'deg'=>'&#176;', 'plusmn'=>'&#177;', 'sup2'=>'&#178;',
		'sup3'=>'&#179;', 'acute'=>'&#180;', 'micro'=>'&#181;', 'para'=>'&#182;', 'middot'=>'&#183;',
		'cedil'=>'&#184;', 'sup1'=>'&#185;', 'ordm'=>'&#186;', 'raquo'=>'&#187;', 'iquest'=>'&#191;',
		'Agrave'=>'&#192;', 'Aacute'=>'&#193;', 'Acirc'=>'&#194;', 'Atilde'=>'&#195;', 'Auml'=>'&#196;',
		'Aring'=>'&#197;', 'AElig'=>'&#198;', 'Ccedil'=>'&#199;', 'Egrave'=>'&#200;', 'Eacute'=>'&#201;',
		'Ecirc'=>'&#202;', 'Euml'=>'&#203;', 'Igrave'=>'&#204;', 'Iacute'=>'&#205;', 'Icirc'=>'&#206;',
		'Iuml'=>'&#207;', 'ETH'=>'&#208;', 'Ntilde'=>'&#209;', 'Ograve'=>'&#210;', 'Oacute'=>'&#211;',
		'Ocirc'=>'&#212;', 'Otilde'=>'&#213;', 'Ouml'=>'&#214;', 'times'=>'&#215;', 'Oslash'=>'&#216;',
		'Ugrave'=>'&#217;', 'Uacute'=>'&#218;', 'Ucirc'=>'&#219;', 'Uuml'=>'&#220;', 'Yacute'=>'&#221;',
		'THORN'=>'&#222;', 'szlig'=>'&#223;', 'agrave'=>'&#224;', 'aacute'=>'&#225;', 'acirc'=>'&#226;',
		'atilde'=>'&#227;', 'auml'=>'&#228;', 'aring'=>'&#229;', 'aelig'=>'&#230;', 'ccedil'=>'&#231;',
		'egrave'=>'&#232;', 'eacute'=>'&#233;', 'ecirc'=>'&#234;', 'euml'=>'&#235;', 'igrave'=>'&#236;',
		'iacute'=>'&#237;', 'icirc'=>'&#238;', 'iuml'=>'&#239;', 'eth'=>'&#240;', 'ntilde'=>'&#241;',
		'ograve'=>'&#242;', 'oacute'=>'&#243;', 'ocirc'=>'&#244;', 'otilde'=>'&#245;', 'ouml'=>'&#246;',
		'divide'=>'&#247;', 'oslash'=>'&#248;', 'ugrave'=>'&#249;', 'uacute'=>'&#250;',
		'ucirc'=>'&#251;', 'uuml'=>'&#252;', 'yacute'=>'&#253;', 'thorn'=>'&#254;',
		'nbsp' => " ", 'copy' => "(c)", 'reg' => "(r)", 'frac14' => "1/4",
		'frac12' => "1/2", 'frac34' => "3/4", 'amp' => '&', 'quot' => '"',
		'apos' => "'", 'lt' => '<', 'gt' => '>',
		'mdash' => '&#8212;', 'ndash' => '&#8211;'
		);
		return $charset;
		
	case 'mathml':
		$GLOBALS['CHARSET'][$charset] = array (
		'ac' => '&#xE207;',
		'acd' => '&#xE3A6;',
		'acE' => '&E#290;',
		'acute' => '&#x0301;',
		'Afr' => '&#xE47C;',
		'afr' => '&#xE495;',
		'aleph' => '&#x2135;',
		'alpha' => '&#x03B1;',
		'amalg' => '&#xE251;',
		'amp' => '&#x0026;',
		'And' => '&#x2227;',
		'and' => '&#x2227;',
		'andand' => '&#xE36E;',
		'andd' => '&#xE394;',
		'andslope' => '&#xE50A;',
		'andv' => '&#xE391;',
		'ang' => '&#x2220;',
		'ange' => '&#xE2D6;',
		'angle' => '&#x2220;',
		'angmsd' => '&#x2221;',
		'angmsdaa' => '&#xE2D9;',
		'angmsdab' => '&#xE2DA;',
		'angmsdac' => '&#xE2DB;',
		'angmsdad' => '&#xE2DC;',
		'angmsdae' => '&#xE2DD;',
		'angmsdaf' => '&#xE2DE;',
		'angmsdag' => '&#xE2DF;',
		'angmsdah' => '&#xE2E0;',
		'angrt' => '&#x221F;',
		'angrtvb' => '&#xE418;',
		'angrtvbd' => '&#xE2E1;',
		'angsph' => '&#x2222;',
		'angst' => '&#x212B;',
		'angzarr' => '&#xE248;',
		'Aopf' => '&#xE4AF;',
		'ap' => '&#x2248;',
		'apacir' => '&#xE38C;',
		'apE' => '&#xE315;',
		'ape' => '&#x224A;',
		'apid' => '&#x224B;',
		'apos' => '&#x0027;',
		'approx' => '&#x2248;',
		'approxeq' => '&#x224A;',
		'Ascr' => '&#xE4C5;',
		'ascr' => '&#xE4DF;',
		'ast' => '&#x2217;',
		'asymp' => '&#x224D;',
		'awconint' => '&#x2233;',
		'awint' => '&#xE39B;',
		'backcong' => '&#x224C;',
		'backepsilon' => '&#xE420;',
		'backprime' => '&#x2035;',
		'backsim' => '&#x223D;',
		'backsimeq' => '&#x22CD;',
		'Backslash' => '&#x2216;',
		'Barv' => '&#xE311;',
		'barvee' => '&#x22BD;',
		'Barwed' => '&#x2306;',
		'barwed' => '&#x22BC;',
		'barwedge' => '&#x22BC;',
		'bbrk' => '&#xE2EE;',
		'bbrktbrk' => '&#xE419;',
		'bcong' => '&#x224C;',
		'becaus' => '&#x2235;',
		'Because' => '&#x2235;',
		'because' => '&#x2235;',
		'bemptyv' => '&#xE41A;',
		'benzen' => '&#xE43C;',
		'benzena' => '&#xE42A;',
		'benzenb' => '&#xE42B;',
		'benzenc' => '&#xE42C;',
		'benzend' => '&#xE42D;',
		'benzene' => '&#xE42E;',
		'benzenf' => '&#xE42F;',
		'benzeng' => '&#xE430;',
		'benzenh' => '&#xE431;',
		'benzeni' => '&#xE432;',
		'benzenj' => '&#xE433;',
		'benzenk' => '&#xE434;',
		'benzenl' => '&#xE435;',
		'benzenm' => '&#xE436;',
		'benzenn' => '&#xE437;',
		'benzeno' => '&#xE438;',
		'benzenp' => '&#xE439;',
		'benzenq' => '&#xE43A;',
		'benzenr' => '&#xE43B;',
		'bepsi' => '&#xE420;',
		'bernou' => '&#x212C;',
		'beta' => '&#x03B2;',
		'beth' => '&#x2136;',
		'between' => '&#x226C;',
		'Bfr' => '&#xE47D;',
		'bfr' => '&#xE496;',
		'bigcap' => '&#x22C2;',
		'bigcirc' => '&#x25CB;',
		'bigcup' => '&#x22C3;',
		'bigodot' => '&#x2299;',
		'bigoplus' => '&#x2295;',
		'bigotimes' => '&#x2297;',
		'bigsqcup' => '&#x2294;',
		'bigstar' => '&#x2605;',
		'bigtriangledown' => '&#x25BD;',
		'bigtriangleup' => '&#x25B3;',
		'biguplus' => '&#x228E;',
		'bigvee' => '&#x22C1;',
		'bigwedge' => '&#x22C0;',
		'bkarow' => '&#xE405;',
		'blacklozenge' => '&#xE501;',
		'blacksquare' => '&#x25A0;',
		'blacktriangle' => '&#x25B4;',
		'blacktriangledown' => '&#x25BE;',
		'blacktriangleleft' => '&#x25C2;',
		'blacktriangleright' => '&#x25B8;',
		'blank' => '&#xE4F9;',
		'blk12' => '&#x2592;',
		'blk14' => '&#x2591;',
		'blk34' => '&#x2593;',
		'block' => '&#x2588;',
		'bne' => '&#xE388;',
		'bnequiv' => '&#xE387;',
		'bNot' => '&#xE3AD;',
		'bnot' => '&#x2310;',
		'Bopf' => '&#xE4B0;',
		'bot' => '&#x22A5;',
		'bottom' => '&#x22A5;',
		'bowtie' => '&#x22C8;',
		'boxbox' => '&#xE2E6;',
		'boxminus' => '&#x229F;',
		'boxplus' => '&#x229E;',
		'boxtimes' => '&#x22A0;',
		'bprime' => '&#x2035;',
		'Breve' => '&#x0306;',
		'breve' => '&#x0306;',
		'brvbar' => '&#x00A6;',
		'Bscr' => '&#xE4C6;',
		'bscr' => '&#xE4E0;',
		'bsemi' => '&#xE2ED;',
		'bsim' => '&#x223D;',
		'bsime' => '&#x22CD;',
		'bsol' => '&#x005C;',
		'bsolb' => '&#xE280;',
		'bsolhsub' => '&#xE34D;',
		'bull' => '&#x2022;',
		'bullet' => '&#x2022;',
		'bump' => '&#x224E;',
		'bumpe' => '&#x224F;',
		'Bumpeq' => '&#x224E;',
		'bumpeq' => '&#x224F;',
		'Cap' => '&#x22D2;',
		'cap' => '&#x2229;',
		'capand' => '&#xE281;',
		'capbrcup' => '&#xE271;',
		'capcap' => '&#xE273;',
		'capcup' => '&#xE26F;',
		'capdot' => '&#xE261;',
		'caps' => '&#xE275;',
		'caret' => '&#x2038;',
		'caron' => '&#x030C;',
		'ccaps' => '&#xE279;',
		'Cconint' => '&#x2230;',
		'ccups' => '&#xE278;',
		'ccupssm' => '&#xE27A;',
		'cdot' => '&#x22C5;',
		'cedil' => '&#x0327;',
		'Cedilla' => '&#x0327;',
		'cemptyv' => '&#xE2E8;',
		'cent' => '&#x00A2;',
		'CenterDot' => '&#x00B7;',
		'centerdot' => '&#x00B7;',
		'Cfr' => '&#xE47E;',
		'cfr' => '&#xE497;',
		'check' => '&#x2713;',
		'checkmark' => '&#x2713;',
		'chi' => '&#x03C7;',
		'cir' => '&#x2218;',
		'circ' => '&#x2218;',
		'circeq' => '&#x2257;',
		'circle' => '&#xE4FA;',
		'circlearrowleft' => '&#x21BA;',
		'circlearrowright' => '&#x21BB;',
		'circledast' => '&#x229B;',
		'circledcirc' => '&#x229A;',
		'circleddash' => '&#x229D;',
		'CircleDot' => '&#x2299;',
		'circledR' => '&#x00AF;',
		'circledS' => '&#xE41D;',
		'circlef' => '&#x25CF;',
		'circlefb' => '&#x25D2;',
		'circlefl' => '&#x25D0;',
		'circlefr' => '&#x25D1;',
		'circleft' => '&#x25D3;',
		'CircleMinus' => '&#x2296;',
		'CirclePlus' => '&#x2295;',
		'CircleTimes' => '&#x2297;',
		'cirE' => '&#xE41B;',
		'cire' => '&#x2257;',
		'cirfnint' => '&#xE395;',
		'cirmid' => '&#xE250;',
		'cirscir' => '&#xE41C;',
		'ClockwiseContourIntegral' => '&#x2232;',
		'CloseCurlyDoubleQuote' => '&#x201D;',
		'CloseCurlyQuote' => '&#x2019;',
		'clubs' => '&#x2663;',
		'clubsuit' => '&#x2663;',
		'Colon' => '&#x2237;',
		'colon' => '&#x003A;',
		'Colone' => '&#xE30E;',
		'colone' => '&#x2254;',
		'coloneq' => '&#x2254;',
		'comma' => '&#x002C;',
		'commat' => '&#x0040;',
		'comp' => '&#x2201;',
		'compfn' => '&#x2218;',
		'complement' => '&#x2201;',
		'cong' => '&#x2245;',
		'congdot' => '&#xE314;',
		'Congruent' => '&#x2261;',
		'Conint' => '&#x222F;',
		'conint' => '&#x222E;',
		'ContourIntegral' => '&#x222E;',
		'Copf' => '&#x2102;',
		'coprod' => '&#x2210;',
		'Coproduct' => '&#x2210;',
		'copy' => '&#x00A9;',
		'copysr' => '&#x2117;',
		'CounterClockwiseContourIntegral' => '&#x2233;',
		'cross' => '&#x2612;',
		'Cscr' => '&#xE4C7;',
		'cscr' => '&#xE4E1;',
		'csub' => '&#xE351;',
		'csube' => '&#xE353;',
		'csup' => '&#xE352;',
		'csupe' => '&#xE354;',
		'ctdot' => '&#x22EF;',
		'cudarrl' => '&#xE23E;',
		'cudarrr' => '&#xE400;',
		'cuepr' => '&#x22DE;',
		'cuesc' => '&#x22DF;',
		'cularr' => '&#x21B6;',
		'cularrp' => '&#xE24A;',
		'Cup' => '&#x2323;',
		'cup' => '&#x222A;',
		'cupbrcap' => '&#xE270;',
		'CupCap' => '&#x224D;',
		'cupcap' => '&#xE26E;',
		'cupcup' => '&#xE272;',
		'cupdot' => '&#x228D;',
		'cupor' => '&#xE282;',
		'cups' => '&#xE274;',
		'curarr' => '&#x21B7;',
		'curarrm' => '&#xE249;',
		'curlyeqprec' => '&#x22DE;',
		'curlyeqsucc' => '&#x22DF;',
		'curlyvee' => '&#x22CE;',
		'curlywedge' => '&#x22CF;',
		'curren' => '&#x00A4;',
		'curvearrowleft' => '&#x21B6;',
		'curvearrowright' => '&#x21B7;',
		'cuvee' => '&#x22CE;',
		'cuwed' => '&#x22CF;',
		'cwconint' => '&#x2232;',
		'cwint' => '&#x2231;',
		'cylcty' => '&#x232D;',
		'Dagger' => '&#x2021;',
		'dagger' => '&#x2020;',
		'daleth' => '&#x2138;',
		'Darr' => '&#x21A1;',
		'dArr' => '&#x21D3;',
		'darr' => '&#x2193;',
		'dash' => '&#x2010;',
		'Dashv' => '&#xE30F;',
		'dashv' => '&#x22A3;',
		'dbkarow' => '&#xE207;',
		'dblac' => '&#x030B;',
		'ddagger' => '&#x2021;',
		'ddarr' => '&#x21CA;',
		'DDotrahd' => '&#xE238;',
		'ddotseq' => '&#xE309;',
		'deg' => '&#x00B0;',
		'Del' => '&#x2207;',
		'Delta' => '&#x0394;',
		'delta' => '&#x03B4;',
		'demptyv' => '&#xE2E7;',
		'dfisht' => '&#xE24C;',
		'Dfr' => '&#xE47F;',
		'dfr' => '&#xE498;',
		'dHar' => '&#xE227;',
		'dharl' => '&#x21C3;',
		'dharr' => '&#x21C2;',
		'DiacriticalAcute' => '&#x0301;',
		'DiacriticalDot' => '&#x0307;',
		'DiacriticalDoubleAcute' => '&#x030B;',
		'DiacriticalGrave' => '&#x0300;',
		'DiacriticalLeftArrow' => '&#x20D6;',
		'DiacriticalLeftRightArrow' => '&#x20E1;',
		'DiacriticalLeftRightVector' => '&#xF505;',
		'DiacriticalLeftVector' => '&#x20D0;',
		'DiacriticalRightArrow' => '&#x20D7;',
		'DiacriticalRightVector' => '&#x20D1;',
		'DiacriticalTilde' => '&#x0303;',
		'diam' => '&#x22C4;',
		'diamond' => '&#x22C4;',
		'diamondf' => '&#xE4FB;',
		'diamondsuit' => '&#x2662;',
		'diamonfb' => '&#xE4FC;',
		'diamonfl' => '&#xE4FD;',
		'diamonfr' => '&#xE4FE;',
		'diamonft' => '&#xE4FF;',
		'diams' => '&#x2662;',
		'die' => '&#x0308;',
		'digamma' => '&#x03DC;',
		'disin' => '&#xE3A0;',
		'div' => '&#x00F7;',
		'divide' => '&#x00F7;',
		'divideontimes' => '&#x22C7;',
		'divonx' => '&#x22C7;',
		'dlcorn' => '&#x231E;',
		'dlcrop' => '&#x230D;',
		'dollar' => '&#x0024;',
		'Dopf' => '&#xE4B1;',
		'Dot' => '&#x0308;',
		'dot' => '&#x0307;',
		'DotDot' => '&#x20DC;',
		'doteq' => '&#x2250;',
		'doteqdot' => '&#x2251;',
		'DotEqual' => '&#x2250;',
		'dotminus' => '&#x2238;',
		'dotplus' => '&#x2214;',
		'dotsquare' => '&#x22A1;',
		'doublebarwedge' => '&#x2306;',
		'DoubleContourIntegral' => '&#x222F;',
		'DoubleDot' => '&#x0308;',
		'DoubleDownArrow' => '&#x21D3;',
		'DoubleLeftArrow' => '&#x21D0;',
		'DoubleLeftRightArrow' => '&#x21D4;',
		'DoubleLongLeftArrow' => '&#xE200;',
		'DoubleLongLeftRightArrow' => '&#xE202;',
		'DoubleLongRightArrow' => '&#xE204;',
		'DoubleRightArrow' => '&#x21D2;',
		'DoubleRightTee' => '&#x22A8;',
		'DoubleUpArrow' => '&#x21D1;',
		'DoubleUpDownArrow' => '&#x21D5;',
		'DoubleVerticalBar' => '&#x2225;',
		'DownArrow' => '&#x2193;',
		'Downarrow' => '&#x21D3;',
		'downarrow' => '&#x2193;',
		'DownArrowUpArrow' => '&#xE216;',
		'downdownarrows' => '&#x21CA;',
		'downharpoonleft' => '&#x21C3;',
		'downharpoonright' => '&#x21C2;',
		'DownLeftVector' => '&#x21BD;',
		'DownRightVector' => '&#x21C1;',
		'DownTee' => '&#x22A4;',
		'drbkarow' => '&#xE209;',
		'drcorn' => '&#x231F;',
		'drcrop' => '&#x230C;',
		'Dscr' => '&#xE4C8;',
		'dscr' => '&#xE4E2;',
		'dsol' => '&#xE3A9;',
		'dtdot' => '&#x22F1;',
		'dtri' => '&#x25BF;',
		'dtrif' => '&#x25BE;',
		'duarr' => '&#xE216;',
		'duhar' => '&#xE217;',
		'dwangle' => '&#xE3AA;',
		'dzigrarr' => '&#x21DD;',
		'easter' => '&#x225B;',
		'ecir' => '&#x2256;',
		'ecolon' => '&#x2255;',
		'eDDot' => '&#xE309;',
		'eDot' => '&#x2251;',
		'efDot' => '&#x2252;',
		'Efr' => '&#xE480;',
		'efr' => '&#xE499;',
		'eg' => '&#xE328;',
		'egs' => '&#x22DD;',
		'egsdot' => '&#xE324;',
		'el' => '&#xE327;',
		'Element' => '&#x2208;',
		'elinters' => '&#xE3A7;',
		'ell' => '&#x2113;',
		'els' => '&#x22DC;',
		'elsdot' => '&#xE323;',
		'empty' => '&#xE2D3;',
		'emptyset' => '&#xE2D3;',
		'emptyv' => '&#x2205;',
		'emsp' => '&#x2003;',
		'emsp13' => '&#x2004;',
		'emsp14' => '&#x2005;',
		'ensp' => '&#x2002;',
		'Eopf' => '&#xE4B2;',
		'epar' => '&#x22D5;',
		'eparsl' => '&#xE384;',
		'eplus' => '&#xE268;',
		'epsi' => '&#x220A;',
		'epsiv' => '&#x03B5;',
		'eqcirc' => '&#x2256;',
		'eqcolon' => '&#x2255;',
		'eqsim' => '&#x2242;',
		'eqslantgtr' => '&#x22DD;',
		'eqslantless' => '&#x22DC;',
		'equals' => '&#x003D;',
		'EqualTilde' => '&#x2242;',
		'equest' => '&#x225F;',
		'Equilibrium' => '&#x21CC;',
		'equiv' => '&#x2261;',
		'equivDD' => '&#xE318;',
		'eqvparsl' => '&#xE386;',
		'erarr' => '&#xE236;',
		'erDot' => '&#x2253;',
		'Escr' => '&#xE4C9;',
		'escr' => '&#xE4E3;',
		'esdot' => '&#x2250;',
		'Esim' => '&#xE317;',
		'esim' => '&#x2242;',
		'eta' => '&#x03B7;',
		'excl' => '&#x0021;',
		'exist' => '&#x2203;',
		'Exists' => '&#x2203;',
		'fallingdotseq' => '&#x2252;',
		'female' => '&#x2640;',
		'ffilig' => '&#xFB03;',
		'fflig' => '&#xFB00;',
		'ffllig' => '&#xFB04;',
		'Ffr' => '&#xE481;',
		'ffr' => '&#xE49A;',
		'filig' => '&#xFB01;',
		'fjlig' => '&#xE500;',
		'flat' => '&#x266D;',
		'fllig' => '&#xFB02;',
		'fltns' => '&#xE381;',
		'Fopf' => '&#xE4B3;',
		'ForAll' => '&#x2200;',
		'forall' => '&#x2200;',
		'fork' => '&#x22D4;',
		'forkv' => '&#xE31B;',
		'fpartint' => '&#xE396;',
		'frac12' => '&#x00BD;',
		'frac13' => '&#x2153;',
		'frac14' => '&#x00BC;',
		'frac15' => '&#x2155;',
		'frac16' => '&#x2159;',
		'frac18' => '&#x215B;',
		'frac23' => '&#x2254;',
		'frac25' => '&#x2156;',
		'frac34' => '&#x00BE;',
		'frac35' => '&#x2157;',
		'frac38' => '&#x215C;',
		'frac45' => '&#x2158;',
		'frac56' => '&#x215A;',
		'frac58' => '&#x215D;',
		'frac78' => '&#x215E;',
		'frown' => '&#x2322;',
		'Fscr' => '&#xE4CA;',
		'fscr' => '&#xE4E4;',
		'Gamma' => '&#x0393;',
		'gamma' => '&#x03B3;',
		'Gammad' => '&#x03DC;',
		'gammad' => '&#x03DC;',
		'gap' => '&#x2273;',
		'gE' => '&#x2267;',
		'ge' => '&#x2265;',
		'gEl' => '&#x22DB;',
		'gel' => '&#x22DB;',
		'geq' => '&#x2265;',
		'geqq' => '&#x2267;',
		'geqslant' => '&#xE421;',
		'ges' => '&#xE421;',
		'gescc' => '&#xE358;',
		'gesdot' => '&#xE31E;',
		'gesdoto' => '&#xE320;',
		'gesdotol' => '&#xE322;',
		'gesl' => '&#xE32C;',
		'gesles' => '&#xE332;',
		'Gfr' => '&#xE482;',
		'gfr' => '&#xE49B;',
		'Gg' => '&#x22D9;',
		'gg' => '&#x226B;',
		'ggg' => '&#x22D9;',
		'gimel' => '&#x2137;',
		'gl' => '&#x2277;',
		'gla' => '&#xE330;',
		'glE' => '&#xE32E;',
		'glj' => '&#xE32F;',
		'gnap' => '&#xE411;',
		'gnapprox' => '&#xE411;',
		'gnE' => '&#x2269;',
		'gne' => '&#x2269;',
		'gneq' => '&#x2269;',
		'gneqq' => '&#x2269;',
		'gnsim' => '&#x22E7;',
		'Gopf' => '&#xE4B4;',
		'grave' => '&#x0300;',
		'GreaterEqual' => '&#x2265;',
		'GreaterEqualLess' => '&#x22DB;',
		'GreaterFullEqual' => '&#x2267;',
		'GreaterLess' => '&#x2277;',
		'GreaterSlantEqual' => '&#xE421;',
		'GreaterTilde' => '&#x2273;',
		'Gscr' => '&#xE4CB;',
		'gscr' => '&#xE4E5;',
		'gsim' => '&#x2273;',
		'gsime' => '&#xE334;',
		'gsiml' => '&#xE336;',
		'Gt' => '&#x226B;',
		'gt' => '&#x003E;',
		'gtcc' => '&#xE356;',
		'gtcir' => '&#xE326;',
		'gtdot' => '&#x22D7;',
		'gtlPar' => '&#xE296;',
		'gtquest' => '&#xE32A;',
		'gtrapprox' => '&#x2273;',
		'gtrarr' => '&#xE35F;',
		'gtrdot' => '&#x22D7;',
		'gtreqless' => '&#x22DB;',
		'gtreqqless' => '&#x22DB;',
		'gtrless' => '&#x2277;',
		'gtrsim' => '&#x2273;',
		'gvertneqq' => '&#xE2A1;',
		'gvnE' => '&#xE2A1;',
		'Hacek' => '&#x030C;',
		'hairsp' => '&#x200A;',
		'half' => '&#x00BD;',
		'hamilt' => '&#x210B;',
		'hArr' => '&#x21D4;',
		'harr' => '&#x2194;',
		'harrcir' => '&#xE240;',
		'harrw' => '&#x21AD;',
		'Hat' => '&#x0302;',
		'hbar' => '&#xE2D5;',
		'hbenzen' => '&#xE44F;',
		'hbenzena' => '&#xE43D;',
		'hbenzenb' => '&#xE43E;',
		'hbenzenc' => '&#xE43F;',
		'hbenzend' => '&#xE440;',
		'hbenzene' => '&#xE441;',
		'hbenzenf' => '&#xE442;',
		'hbenzeng' => '&#xE443;',
		'hbenzenh' => '&#xE444;',
		'hbenzeni' => '&#xE445;',
		'hbenzenj' => '&#xE446;',
		'hbenzenk' => '&#xE447;',
		'hbenzenl' => '&#xE448;',
		'hbenzenm' => '&#xE449;',
		'hbenzenn' => '&#xE44A;',
		'hbenzeno' => '&#xE44B;',
		'hbenzenp' => '&#xE44C;',
		'hbenzenq' => '&#xE44D;',
		'hbenzenr' => '&#xE44E;',
		'hearts' => '&#x2661;',
		'heartsuit' => '&#x2661;',
		'hellip' => '&#x2026;',
		'hercon' => '&#x22B9;',
		'Hfr' => '&#xE483;',
		'hfr' => '&#xE49C;',
		'hksearow' => '&#xE20B;',
		'hkswarow' => '&#xE20A;',
		'hoarr' => '&#xE243;',
		'homtht' => '&#x223B;',
		'hookleftarrow' => '&#x21A9;',
		'hookrightarrow' => '&#x21AA;',
		'Hopf' => '&#xE4B5;',
		'horbar' => '&#x2015;',
		'Hscr' => '&#xE4CC;',
		'hscr' => '&#xE4E6;',
		'hslash' => '&#x210F;',
		'HumpDownHump' => '&#x224E;',
		'HumpEqual' => '&#x224F;',
		'hybull' => '&#x2043;',
		'hyphen' => '&#xE4F8;',
		'iexcl' => '&#x00A1;',
		'iff' => '&#xE365;',
		'Ifr' => '&#xE484;',
		'ifr' => '&#xE49D;',
		'iiiint' => '&#xE378;',
		'iiint' => '&#x222D;',
		'iinfin' => '&#xE372;',
		'iiota' => '&#x2129;',
		'Im' => '&#x2111;',
		'image' => '&#x2111;',
		'imath' => '&#x0131;',
		'imof' => '&#x22B7;',
		'imped' => '&#xE50B;',
		'Implies' => '&#x21D2;',
		'in' => '&#x220A;',
		'incare' => '&#x2105;',
		'infin' => '&#x221E;',
		'infintie' => '&#xE50C;',
		'Int' => '&#x222C;',
		'int' => '&#x222B;',
		'intcal' => '&#x22BA;',
		'Integral' => '&#x222B;',
		'intercal' => '&#x22BA;',
		'Intersection' => '&#x22C2;',
		'intlarhk' => '&#xE39A;',
		'intprod' => '&#xE259;',
		'Iopf' => '&#xE4B6;',
		'iota' => '&#x03B9;',
		'iprod' => '&#xE259;',
		'iquest' => '&#x00BF;',
		'Iscr' => '&#xE4CD;',
		'iscr' => '&#xE4E7;',
		'isin' => '&#x220A;',
		'isindot' => '&#xE39C;',
		'isinE' => '&#xE39E;',
		'isins' => '&#xE3A4;',
		'isinsv' => '&#xE3A2;',
		'isinv' => '&#x2208;',
		'Jfr' => '&#xE485;',
		'jfr' => '&#xE49E;',
		'jmath' => '&#xE2D4;',
		'Jopf' => '&#xE4B7;',
		'Jscr' => '&#xE4CE;',
		'jscr' => '&#xE4E8;',
		'kappa' => '&#x03BA;',
		'kappav' => '&#x03F0;',
		'Kfr' => '&#xE486;',
		'kfr' => '&#xE49F;',
		'Kopf' => '&#xE4B8;',
		'Kscr' => '&#xE4CF;',
		'kscr' => '&#xE4E9;',
		'lAarr' => '&#x21DA;',
		'laemptyv' => '&#xE2EA;',
		'lagran' => '&#x2112;',
		'Lambda' => '&#x039B;',
		'lambda' => '&#x03BB;',
		'Lang' => '&#x300A;',
		'lang' => '&#x3008;',
		'langd' => '&#xE297;',
		'langle' => '&#x3008;',
		'lap' => '&#x2272;',
		'laquo' => '&#x00AB;',
		'Larr' => '&#x219E;',
		'lArr' => '&#x21D0;',
		'larr' => '&#x2190;',
		'larrbfs' => '&#xE220;',
		'larrfs' => '&#xE222;',
		'larrhk' => '&#x21A9;',
		'larrlp' => '&#x21AB;',
		'larrpl' => '&#xE23F;',
		'larrsim' => '&#xE24E;',
		'larrtl' => '&#x21A2;',
		'lat' => '&#xE33A;',
		'lAtail' => '&#xE23D;',
		'latail' => '&#xE23C;',
		'late' => '&#xE33C;',
		'lates' => '&#xE33E;',
		'lBarr' => '&#xE206;',
		'lbarr' => '&#xE402;',
		'lbbrk' => '&#x3014;',
		'lbrace' => '&#x007B;',
		'lbrack' => '&#x005B;',
		'lbrke' => '&#xE299;',
		'lbrksld' => '&#xE29D;',
		'lbrkslu' => '&#xE29B;',
		'lceil' => '&#x2308;',
		'lcub' => '&#x007B;',
		'ldca' => '&#xE21A;',
		'ldquo' => '&#x201C;',
		'ldquor' => '&#x201E;',
		'ldrdhar' => '&#xE22C;',
		'ldrushar' => '&#xE228;',
		'ldsh' => '&#x21B2;',
		'lE' => '&#x2266;',
		'le' => '&#x2264;',
		'LeftAngleBracket' => '&#x3008;',
		'LeftArrow' => '&#x2190;',
		'Leftarrow' => '&#x21D0;',
		'leftarrow' => '&#x2190;',
		'LeftArrowRightArrow' => '&#x21C6;',
		'leftarrowtail' => '&#x21A2;',
		'LeftCeiling' => '&#x2308;',
		'LeftDownVector' => '&#x21C3;',
		'LeftFloor' => '&#x230A;',
		'leftharpoondown' => '&#x21BD;',
		'leftharpoonup' => '&#x21BC;',
		'leftleftarrows' => '&#x21C7;',
		'LeftRightArrow' => '&#x2194;',
		'Leftrightarrow' => '&#x21D4;',
		'leftrightarrow' => '&#x2194;',
		'leftrightarrows' => '&#x21C6;',
		'leftrightharpoons' => '&#x21CB;',
		'leftrightsquigarrow' => '&#x21AD;',
		'LeftTee' => '&#x22A3;',
		'leftthreetimes' => '&#x22CB;',
		'LeftTriangle' => '&#x22B2;',
		'LeftTriangleEqual' => '&#x22B4;',
		'LeftUpVector' => '&#x21BF;',
		'LeftVector' => '&#x21BC;',
		'lEg' => '&#x22DA;',
		'leg' => '&#x22DA;',
		'leq' => '&#x2264;',
		'leqq' => '&#x2266;',
		'leqslant' => '&#xE425;',
		'les' => '&#xE425;',
		'lescc' => '&#xE357;',
		'lesdot' => '&#xE31D;',
		'lesdoto' => '&#xE31F;',
		'lesdotor' => '&#xE321;',
		'lesg' => '&#xE32B;',
		'lesges' => '&#xE331;',
		'lessapprox' => '&#x2272;',
		'lessdot' => '&#x22D6;',
		'lesseqgtr' => '&#x22DA;',
		'lesseqqgtr' => '&#x22DA;',
		'LessEqualGreater' => '&#x22DA;',
		'LessFullEqual' => '&#x2266;',
		'LessGreater' => '&#x2276;',
		'lessgtr' => '&#x2276;',
		'lesssim' => '&#x2272;',
		'LessSlantEqual' => '&#xE425;',
		'LessTilde' => '&#x2272;',
		'lfisht' => '&#xE214;',
		'lfloor' => '&#x230A;',
		'Lfr' => '&#xE487;',
		'lfr' => '&#xE4A0;',
		'lg' => '&#x2276;',
		'lgE' => '&#xE32D;',
		'lHar' => '&#xE225;',
		'lhard' => '&#x21BD;',
		'lharu' => '&#x21BC;',
		'lharul' => '&#xE22E;',
		'lhblk' => '&#x2584;',
		'Ll' => '&#x22D8;',
		'll' => '&#x226A;',
		'llarr' => '&#x21C7;',
		'llcorner' => '&#x231E;',
		'Lleftarrow' => '&#x21DA;',
		'llhard' => '&#xE231;',
		'lltri' => '&#xE2E5;',
		'lmoust' => '&#xE294;',
		'lmoustache' => '&#xE294;',
		'lnap' => '&#xE2A2;',
		'lnapprox' => '&#xE2A2;',
		'lnE' => '&#x2268;',
		'lne' => '&#x2268;',
		'lneq' => '&#x2268;',
		'lneqq' => '&#x2268;',
		'lnsim' => '&#x22E6;',
		'loang' => '&#x3018;',
		'loarr' => '&#xE242;',
		'lobrk' => '&#x301A;',
		'LongLeftArrow' => '&#xE201;',
		'Longleftarrow' => '&#xE200;',
		'longleftarrow' => '&#xE201;',
		'LongLeftRightArrow' => '&#xE203;',
		'Longleftrightarrow' => '&#xE202;',
		'longleftrightarrow' => '&#xE203;',
		'longmapsto' => '&#xE208;',
		'LongRightArrow' => '&#xE205;',
		'Longrightarrow' => '&#xE204;',
		'longrightarrow' => '&#xE205;',
		'looparrowleft' => '&#x21AB;',
		'looparrowright' => '&#x21AC;',
		'lopar' => '&#xE379;',
		'Lopf' => '&#xE4B9;',
		'loplus' => '&#xE25C;',
		'lotimes' => '&#xE25E;',
		'lowast' => '&#x2217;',
		'lowbar' => '&#x005F;',
		'LowerLeftArrow' => '&#x2199;',
		'LowerRightArrow' => '&#x2198;',
		'loz' => '&#x25CA;',
		'lozenge' => '&#x25CA;',
		'lozf' => '&#xE501;',
		'lpar' => '&#x0028;',
		'lparlt' => '&#xE292;',
		'lrarr' => '&#x21C6;',
		'lrcorner' => '&#x231F;',
		'lrhar' => '&#x21CB;',
		'lrhard' => '&#xE22F;',
		'lrtri' => '&#xE2E3;',
		'Lscr' => '&#xE4D0;',
		'lscr' => '&#xE4EA;',
		'Lsh' => '&#x21B0;',
		'lsh' => '&#x21B0;',
		'lsim' => '&#x2272;',
		'lsime' => '&#xE333;',
		'lsimg' => '&#xE335;',
		'lsqb' => '&#x005B;',
		'lsquo' => '&#x2018;',
		'lsquor' => '&#x201A;',
		'Lt' => '&#x226A;',
		'lt' => '&#x003C;',
		'ltcc' => '&#xE355;',
		'ltcir' => '&#xE325;',
		'ltdot' => '&#x22D6;',
		'lthree' => '&#x22CB;',
		'ltimes' => '&#x22C9;',
		'ltlarr' => '&#xE35E;',
		'ltquest' => '&#xE329;',
		'ltri' => '&#x25C3;',
		'ltrie' => '&#x22B4;',
		'ltrif' => '&#x25C2;',
		'ltrPar' => '&#xE295;',
		'lurdshar' => '&#xE229;',
		'luruhar' => '&#xE22B;',
		'lvertneqq' => '&#xE2A4;',
		'lvnE' => '&#xE2A4;',
		'macr' => '&#x0304;',
		'male' => '&#x2642;',
		'malt' => '&#x2720;',
		'maltese' => '&#x2720;',
		'Map' => '&#xE212;',
		'map' => '&#x21A6;',
		'mapsto' => '&#x21A6;',
		'marker' => '&#xE502;',
		'mcomma' => '&#xE31A;',
		'mdash' => '&#x2014;',
		'mDDot' => '&#x223A;',
		'measuredangle' => '&#x2221;',
		'Mfr' => '&#xE488;',
		'mfr' => '&#xE4A1;',
		'mho' => '&#x2127;',
		'micro' => '&#x00B5;',
		'mid' => '&#x2223;',
		'midast' => '&#x2217;',
		'midcir' => '&#xE20F;',
		'middot' => '&#x00B7;',
		'minus' => '&#x2212;',
		'minusb' => '&#x229F;',
		'minusd' => '&#x2238;',
		'minusdu' => '&#xE25B;',
		'MinusPlus' => '&#x2213;',
		'mlcp' => '&#xE30A;',
		'mldr' => '&#xE503;',
		'mnplus' => '&#x2213;',
		'models' => '&#x22A7;',
		'Mopf' => '&#xE4BA;',
		'mp' => '&#x2213;',
		'Mscr' => '&#xE4D1;',
		'mscr' => '&#xE4EB;',
		'mstpos' => '&#x223E;',
		'mu' => '&#x03BC;',
		'multimap' => '&#x22B8;',
		'mumap' => '&#x22B8;',
		'nabla' => '&#x2207;',
		'nang' => '&#xE2D8;',
		'nap' => '&#x2249;',
		'napE' => '&#xE2C7;',
		'napid' => '&#xE2BC;',
		'napprox' => '&#x2249;',
		'natur' => '&#x266E;',
		'natural' => '&#x266E;',
		'nbsp' => '&#x00A0;',
		'ncap' => '&#xE284;',
		'ncong' => '&#x2247;',
		'ncongdot' => '&#xE2C5;',
		'ncup' => '&#xE283;',
		'ndash' => '&#x2013;',
		'ne' => '&#x2260;',
		'nearhk' => '&#xE20D;',
		'neArr' => '&#x21D7;',
		'nearr' => '&#x2197;',
		'nearrow' => '&#x2197;',
		'nedot' => '&#xE38A;',
		'nequiv' => '&#x2262;',
		'nesear' => '&#xE20E;',
		'NestedGreaterGreater' => '&#x226B;',
		'NestedLessLess' => '&#x226A;',
		'nexist' => '&#x2204;',
		'nexists' => '&#x2204;',
		'Nfr' => '&#xE489;',
		'nfr' => '&#xE4A2;',
		'ngE' => '&#x2271;',
		'nge' => '&#xE2A6;',
		'ngeq' => '&#xE2A6;',
		'ngeqq' => '&#x2271;',
		'ngeqslant' => '&#x2271;',
		'nges' => '&#x2271;',
		'nGg' => '&#xE2CE;',
		'ngsim' => '&#x2275;',
		'nGt' => '&#xE2CA;',
		'ngt' => '&#x226F;',
		'ngtr' => '&#x226F;',
		'nGtv' => '&#xE2CC;',
		'nhArr' => '&#x21CE;',
		'nharr' => '&#x21AE;',
		'nhpar' => '&#xE38D;',
		'ni' => '&#x220D;',
		'nis' => '&#xE3A5;',
		'nisd' => '&#xE3A1;',
		'niv' => '&#x220B;',
		'nlArr' => '&#x21CD;',
		'nlarr' => '&#x219A;',
		'nldr' => '&#x2025;',
		'nlE' => '&#x2270;',
		'nle' => '&#xE2A7;',
		'nLeftarrow' => '&#x21CD;',
		'nleftarrow' => '&#x219A;',
		'nLeftrightarrow' => '&#x21CE;',
		'nleftrightarrow' => '&#x21AE;',
		'nleq' => '&#xE2A7;',
		'nleqq' => '&#x2270;',
		'nleqslant' => '&#x2270;',
		'nles' => '&#x2270;',
		'nless' => '&#x226E;',
		'nLl' => '&#xE2CD;',
		'nlsim' => '&#x2274;',
		'nLt' => '&#xE2C9;',
		'nlt' => '&#x226E;',
		'nltri' => '&#x22EA;',
		'nltrie' => '&#x22EC;',
		'nLtv' => '&#xE2CB;',
		'nmid' => '&#x2224;',
		'Nopf' => '&#x2115;',
		'Not' => '&#xE3AC;',
		'not' => '&#x00AC;',
		'NotCongruent' => '&#x2262;',
		'NotDoubleVerticalBar' => '&#x2226;',
		'NotElement' => '&#x2209;',
		'NotEqual' => '&#x2260;',
		'NotExists' => '&#x2204;',
		'NotGreater' => '&#x226F;',
		'NotGreaterEqual' => '&#xE2A6;',
		'NotGreaterFullEqual' => '&#x2270;',
		'NotGreaterGreater' => '&#xE2CC;',
		'NotGreaterLess' => '&#x2279;',
		'NotGreaterSlantEqual' => '&#x2271;',
		'NotGreaterTilde' => '&#x2275;',
		'notin' => '&#x2209;',
		'notindot' => '&#xE39D;',
		'notinE' => '&#xE50D;',
		'notinva' => '&#xE370;',
		'notinvb' => '&#xE37B;',
		'notinvc' => '&#xE37C;',
		'NotLeftTriangle' => '&#x22EA;',
		'NotLeftTriangleEqual' => '&#x22EC;',
		'NotLess' => '&#x226E;',
		'NotLessEqual' => '&#xE2A7;',
		'NotLessFullEqual' => '&#x2270;',
		'NotLessGreater' => '&#x2278;',
		'NotLessLess' => '&#xE2CB;',
		'NotLessSlantEqual' => '&#x2270;',
		'NotLessTilde' => '&#x2274;',
		'notni' => '&#x220C;',
		'notniva' => '&#x220C;',
		'notnivb' => '&#xE37D;',
		'notnivc' => '&#xE37E;',
		'NotPrecedes' => '&#x2280;',
		'NotPrecedesEqual' => '&#xE412;',
		'NotPrecedesSlantEqual' => '&#x22E0;',
		'NotReverseElement' => '&#x220C;',
		'NotRightTriangle' => '&#x22EB;',
		'NotRightTriangleEqual' => '&#x22ED;',
		'NotSquareSubsetEqual' => '&#x22E2;',
		'NotSquareSupersetEqual' => '&#x22E3;',
		'NotSubset' => '&#x2284;',
		'NotSucceeds' => '&#x2281;',
		'NotSucceedsEqual' => '&#xE413;',
		'NotSucceedsSlantEqual' => '&#x22E1;',
		'NotSuperset' => '&#x2285;',
		'NotTilde' => '&#x2241;',
		'NotTildeEqual' => '&#x2244;',
		'NotTildeFullEqual' => '&#x2247;',
		'NotTildeTilde' => '&#x2249;',
		'NotVerticalBar' => '&#x2224;',
		'npar' => '&#x2226;',
		'nparallel' => '&#x2226;',
		'nparsl' => '&#xE389;',
		'npart' => '&#xE390;',
		'npolint' => '&#xE399;',
		'npr' => '&#x2280;',
		'nprcue' => '&#x22E0;',
		'npre' => '&#xE412;',
		'nprec' => '&#x2280;',
		'npreceq' => '&#xE412;',
		'nrArr' => '&#x21CF;',
		'nrarr' => '&#x219B;',
		'nrarrc' => '&#xE21D;',
		'nrarrw' => '&#xE21B;',
		'nRightarrow' => '&#x21CF;',
		'nrightarrow' => '&#x219B;',
		'nrtri' => '&#x22EB;',
		'nrtrie' => '&#x22ED;',
		'nsc' => '&#x2281;',
		'nsccue' => '&#x22E1;',
		'nsce' => '&#xE413;',
		'Nscr' => '&#xE4D2;',
		'nscr' => '&#xE4EC;',
		'nshortmid' => '&#xE2AA;',
		'nshortparallel' => '&#xE2AB;',
		'nsim' => '&#x2241;',
		'nsime' => '&#x2244;',
		'nsimeq' => '&#x2244;',
		'nsmid' => '&#xE2AA;',
		'nspar' => '&#xE2AB;',
		'nsqsube' => '&#x22E2;',
		'nsqsupe' => '&#x22E3;',
		'nsub' => '&#x2284;',
		'nsubE' => '&#x2288;',
		'nsube' => '&#x2288;',
		'nsubset' => '&#x2284;',
		'nsubseteq' => '&#x2288;',
		'nsubseteqq' => '&#x2288;',
		'nsucc' => '&#x2281;',
		'nsucceq' => '&#xE413;',
		'nsup' => '&#x2285;',
		'nsupE' => '&#x2289;',
		'nsupe' => '&#x2289;',
		'nsupset' => '&#x2285;',
		'nsupseteq' => '&#x2289;',
		'nsupseteqq' => '&#x2289;',
		'ntgl' => '&#x2279;',
		'ntlg' => '&#x2278;',
		'ntriangleleft' => '&#x22EA;',
		'ntrianglelefteq' => '&#x22EC;',
		'ntriangleright' => '&#x22EB;',
		'ntrianglerighteq' => '&#x22ED;',
		'ntvgl' => '&#x2279;',
		'ntvlg' => '&#x2278;',
		'nu' => '&#x03BD;',
		'num' => '&#x0023;',
		'numsp' => '&#x2007;',
		'nvap' => '&#xE2C6;',
		'nVDash' => '&#x22AF;',
		'nVdash' => '&#x22AE;',
		'nvDash' => '&#x22AD;',
		'nvdash' => '&#x22AC;',
		'nvge' => '&#x2271;',
		'nvgt' => '&#x226F;',
		'nvhArr*' => '&#x21CE;',
		'nvinfin' => '&#xE38E;',
		'nvlArr' => '&#x21CD;',
		'nvle' => '&#x2270;',
		'nvlt' => '&#x226E;',
		'nvltrie' => '&#xE2D0;',
		'nvrArr' => '&#x21CF;',
		'nvrtrie' => '&#xE2CF;',
		'nvsim' => '&#xE415;',
		'nwarhk' => '&#xE20C;',
		'nwArr' => '&#x21D6;',
		'nwarr' => '&#x2196;',
		'nwarrow' => '&#x2196;',
		'nwnear' => '&#xE211;',
		'oast' => '&#x229B;',
		'ocir' => '&#x229A;',
		'odash' => '&#x229D;',
		'odiv' => '&#xE285;',
		'odot' => '&#x2299;',
		'odsold' => '&#xE286;',
		'ofcir' => '&#xE287;',
		'Ofr' => '&#xE48A;',
		'ofr' => '&#xE4A3;',
		'ogon' => '&#x0328;',
		'ogt' => '&#xE289;',
		'ohbar' => '&#xE260;',
		'ohm' => '&#x2126;',
		'oint' => '&#x222E;',
		'olarr' => '&#x21BA;',
		'olcir' => '&#xE409;',
		'olcross' => '&#xE3A8;',
		'olt' => '&#xE288;',
		'Omega' => '&#x03A9;',
		'omega' => '&#x03C9;',
		'omicron' => '&#x03BE;',
		'omid' => '&#xE40A;',
		'ominus' => '&#x2296;',
		'Oopf' => '&#xE4BC;',
		'opar' => '&#xE28A;',
		'OpenCurlyDoubleQuote' => '&#x201C;',
		'OpenCurlyQuote' => '&#x2018;',
		'operp' => '&#xE28B;',
		'oplus' => '&#x2295;',
		'Or' => '&#xE375;',
		'or' => '&#x2228;',
		'orarr' => '&#x21BB;',
		'ord' => '&#xE393;',
		'order' => '&#x2134;',
		'ordf' => '&#x00AA;',
		'ordm' => '&#x00BA;',
		'origof' => '&#x22B6;',
		'oror' => '&#xE50E;',
		'orslope' => '&#xE3AE;',
		'orv' => '&#xE392;',
		'oS' => '&#xE41D;',
		'Oscr' => '&#xE4D3;',
		'oscr' => '&#xE4ED;',
		'oslash' => '&#x2298;',
		'osol' => '&#x2298;',
		'Otimes' => '&#xE28C;',
		'otimes' => '&#x2297;',
		'otimesas' => '&#xE28D;',
		'ovbar' => '&#xE40B;',
		'OverLine' => '&#x0305;',
		'par' => '&#x2225;',
		'para' => '&#x00B6;',
		'parallel' => '&#x2225;',
		'parsim' => '&#xE2C8;',
		'parsl' => '&#xE382;',
		'part' => '&#x2202;',
		'PartialD' => '&#x2202;',
		'percnt' => '&#x0025;',
		'period' => '&#x002E;',
		'permil' => '&#x2030;',
		'perp' => '&#x22A5;',
		'pertenk' => '&#x2031;',
		'Pfr' => '&#xE48B;',
		'pfr' => '&#xE4A4;',
		'Phi' => '&#x03A6;',
		'phi' => '&#x03C6;',
		'phiv' => '&#x03D5;',
		'phmmat' => '&#x2133;',
		'phone' => '&#x260E;',
		'Pi' => '&#x03A0;',
		'pi' => '&#x03C0;',
		'pitchfork' => '&#x22D4;',
		'piv' => '&#x03D6;',
		'plank' => '&#xE2D5;',
		'plankv' => '&#x210F;',
		'plus' => '&#x002B;',
		'plusacir' => '&#xE26A;',
		'plusb' => '&#x229E;',
		'pluscir' => '&#xE266;',
		'plusdo' => '&#x2214;',
		'plusdu' => '&#xE25A;',
		'pluse' => '&#xE267;',
		'PlusMinus' => '&#x00B1;',
		'plusmn' => '&#x00B1;',
		'plussim' => '&#xE26C;',
		'plustwo' => '&#xE269;',
		'pm' => '&#x00B1;',
		'pointint' => '&#xE376;',
		'Popf' => '&#x2119;',
		'pound' => '&#x00A3;',
		'Pr' => '&#xE35C;',
		'pr' => '&#x227A;',
		'prap' => '&#x227E;',
		'prcue' => '&#x227C;',
		'prE' => '&#x227C;',
		'pre' => '&#x227C;',
		'prec' => '&#x227A;',
		'precapprox' => '&#x227E;',
		'preccurlyeq' => '&#x227C;',
		'Precedes' => '&#x227A;',
		'PrecedesEqual' => '&#x227C;',
		'PrecedesSlantEqual' => '&#x227C;',
		'PrecedesTilde' => '&#x227E;',
		'preceq' => '&#x227C;',
		'precnapprox' => '&#x22E8;',
		'precneqq' => '&#xE2B3;',
		'precnsim' => '&#x22E8;',
		'precsim' => '&#x227E;',
		'Prime' => '&#x2033;',
		'prime' => '&#x2032;',
		'prnap' => '&#x22E8;',
		'prnE' => '&#xE2B3;',
		'prnsim' => '&#x22E8;',
		'profalar' => '&#x232E;',
		'profline' => '&#x2312;',
		'profsurf' => '&#x2313;',
		'prop' => '&#x221D;',
		'Proportion' => '&#x2237;',
		'Proportional' => '&#x221D;',
		'propto' => '&#x221D;',
		'prsim' => '&#x227E;',
		'prurel' => '&#x22B0;',
		'Pscr' => '&#xE4D4;',
		'pscr' => '&#xE4EE;',
		'Psi' => '&#x03A8;',
		'psi' => '&#x03C8;',
		'puncsp' => '&#x2008;',
		'Qfr' => '&#xE48C;',
		'qfr' => '&#xE4A5;',
		'qint' => '&#xE378;',
		'Qopf' => '&#x211A;',
		'qprime' => '&#xE371;',
		'Qscr' => '&#xE4D5;',
		'qscr' => '&#xE4EF;',
		'quatint' => '&#xE377;',
		'quest' => '&#x003F;',
		'questeq' => '&#x225F;',
		'quot' => '&#x0022;',
		'rAarr' => '&#x21DB;',
		'race' => '&#xE40C;',
		'radic' => '&#x221A;',
		'raemptyv' => '&#xE2E9;',
		'Rang' => '&#x300B;',
		'rang' => '&#x3009;',
		'rangd' => '&#xE298;',
		'range' => '&#xE2D7;',
		'rangle' => '&#x3009;',
		'raquo' => '&#x00BB;',
		'Rarr' => '&#x21A0;',
		'rArr' => '&#x21D2;',
		'rarr' => '&#x2192;',
		'rarrap' => '&#xE235;',
		'rarrbfs' => '&#xE221;',
		'rarrc' => '&#xE21C;',
		'rarrfs' => '&#xE223;',
		'rarrhk' => '&#x21AA;',
		'rarrlp' => '&#x21AC;',
		'rarrpl' => '&#xE21E;',
		'rarrsim' => '&#xE24D;',
		'Rarrtl' => '&#xE239;',
		'rarrtl' => '&#x21A3;',
		'rarrw' => '&#x219D;',
		'rAtail' => '&#xE23B;',
		'ratail' => '&#x21A3;',
		'ratio' => '&#x2236;',
		'RBarr' => '&#xE209;',
		'rBarr' => '&#xE207;',
		'rbarr' => '&#xE405;',
		'rbbrk' => '&#x3015;',
		'rbrace' => '&#x007D;',
		'rbrack' => '&#x005D;',
		'rbrke' => '&#xE29A;',
		'rbrksld' => '&#xE29C;',
		'rbrkslu' => '&#xE29E;',
		'rceil' => '&#x2309;',
		'rcub' => '&#x007D;',
		'rdca' => '&#xE219;',
		'rdldhar' => '&#xE22D;',
		'rdquo' => '&#x201D;',
		'rdquor' => '&#x201B;',
		'rdsh' => '&#x21B3;',
		'Re' => '&#x211C;',
		'real' => '&#x211C;',
		'rect' => '&#xE504;',
		'reg' => '&#x00AF;',
		'ReverseElement' => '&#x220B;',
		'ReverseEquilibrium' => '&#x21CB;',
		'ReverseUpEquilibrium' => '&#xE217;',
		'rfisht' => '&#xE215;',
		'rfloor' => '&#x230B;',
		'Rfr' => '&#xE48D;',
		'rfr' => '&#xE4A6;',
		'rHar' => '&#xE224;',
		'rhard' => '&#x21C1;',
		'rharu' => '&#x21C0;',
		'rharul' => '&#xE230;',
		'rho' => '&#x03C1;',
		'rhov' => '&#x03F1;',
		'RightAngleBracket' => '&#x3009;',
		'RightArrow' => '&#x2192;',
		'Rightarrow' => '&#x21D2;',
		'rightarrow' => '&#x2192;',
		'RightArrowLeftArrow' => '&#x21C4;',
		'rightarrowtail' => '&#x21A3;',
		'RightCeiling' => '&#x2309;',
		'RightDownVector' => '&#x21C2;',
		'RightFloor' => '&#x230B;',
		'rightharpoondown' => '&#x21C1;',
		'rightharpoonup' => '&#x21C0;',
		'rightleftarrows' => '&#x21C4;',
		'rightleftharpoons' => '&#x21CC;',
		'rightrightarrows' => '&#x21C9;',
		'rightsquigarrow' => '&#x219D;',
		'RightTee' => '&#x22A2;',
		'RightTeeArrow' => '&#x21A6;',
		'rightthreetimes' => '&#x22CC;',
		'RightTriangle' => '&#x22B3;',
		'RightTriangleEqual' => '&#x22B5;',
		'RightUpVector' => '&#x21BE;',
		'RightVector' => '&#x21C0;',
		'ring' => '&#x030A;',
		'risingdotseq' => '&#x2253;',
		'rlarr' => '&#x21C4;',
		'rlhar' => '&#x21CC;',
		'rmoust' => '&#xE293;',
		'rmoustache' => '&#xE293;',
		'rnmid' => '&#xE2D1;',
		'roang' => '&#x3019;',
		'roarr' => '&#xE241;',
		'robrk' => '&#x301B;',
		'ropar' => '&#xE37A;',
		'Ropf' => '&#x211D;',
		'roplus' => '&#xE25D;',
		'rotimes' => '&#xE40D;',
		'rpar' => '&#x0029;',
		'rpargt' => '&#xE291;',
		'rppolint' => '&#xE397;',
		'rrarr' => '&#x21C9;',
		'Rrightarrow' => '&#x21DB;',
		'Rscr' => '&#xE4D6;',
		'rscr' => '&#x211B;',
		'Rsh' => '&#x21B1;',
		'rsh' => '&#x21B1;',
		'rsqb' => '&#x005D;',
		'rsquo' => '&#x2019;',
		'rsquor' => '&#x201F;',
		'rthree' => '&#x22CC;',
		'rtimes' => '&#x22CA;',
		'rtri' => '&#x25B9;',
		'rtrie' => '&#x22B5;',
		'rtrif' => '&#x25B8;',
		'rtriltri' => '&#xE359;',
		'ruluhar' => '&#xE22A;',
		'rx' => '&#x211E;',
		'Sc' => '&#xE35D;',
		'sc' => '&#x227B;',
		'scap' => '&#x227F;',
		'sccue' => '&#x227D;',
		'scE' => '&#x227E;',
		'sce' => '&#x227D;',
		'scnap' => '&#x22E9;',
		'scnE' => '&#xE2B5;',
		'scnsim' => '&#x22E9;',
		'scpolint' => '&#xE398;',
		'scsim' => '&#x227F;',
		'sdot' => '&#x22C5;',
		'sdotb' => '&#x22A1;',
		'sdote' => '&#xE319;',
		'searhk' => '&#xE20B;',
		'seArr' => '&#x21D8;',
		'searr' => '&#x2198;',
		'searrow' => '&#x2198;',
		'sect' => '&#x00A7;',
		'semi' => '&#x003B;',
		'seswar' => '&#xE406;',
		'setminus' => '&#x2216;',
		'setmn' => '&#x2216;',
		'sext' => '&#xE505;',
		'Sfr' => '&#xE48E;',
		'sfr' => '&#xE4A7;',
		'sfrown' => '&#xE426;',
		'sharp' => '&#x266F;',
		'ShortLeftArrow' => '&#xE233;',
		'shortmid' => '&#xE301;',
		'shortparallel' => '&#xE302;',
		'ShortRightArrow' => '&#xE232;',
		'shy' => '&#x00AD;',
		'Sigma' => '&#x03A3;',
		'sigma' => '&#x03C3;',
		'sigmav' => '&#x03C2;',
		'sim' => '&#x223C;',
		'simdot' => '&#xE38B;',
		'sime' => '&#x2243;',
		'simeq' => '&#x2243;',
		'simg' => '&#xE30C;',
		'simgE' => '&#xE338;',
		'siml' => '&#xE30B;',
		'simlE' => '&#xE337;',
		'simne' => '&#x2246;',
		'simplus' => '&#xE26B;',
		'simrarr' => '&#xE234;',
		'slarr' => '&#xE233;',
		'SmallCircle' => '&#x2218;',
		'smallfrown' => '&#xE426;',
		'smallsetminus' => '&#xE844;',
		'smallsmile' => '&#xE303;',
		'smashp' => '&#xE264;',
		'smeparsl' => '&#xE385;',
		'smid' => '&#xE301;',
		'smile' => '&#x2323;',
		'smt' => '&#xE339;',
		'smte' => '&#xE33B;',
		'smtes' => '&#xE33D;',
		'sol' => '&#x002F;',
		'solb' => '&#xE27F;',
		'solbar' => '&#xE416;',
		'Sopf' => '&#xE4BD;',
		'spades' => '&#x2660;',
		'spadesuit' => '&#x2660;',
		'spar' => '&#xE302;',
		'sqcap' => '&#x2293;',
		'sqcaps' => '&#xE277;',
		'sqcup' => '&#x2294;',
		'sqcups' => '&#xE276;',
		'Sqrt' => '&#x221A;',
		'sqsub' => '&#x228F;',
		'sqsube' => '&#x2291;',
		'sqsubset' => '&#x228F;',
		'sqsubseteq' => '&#x2291;',
		'sqsup' => '&#x2290;',
		'sqsupe' => '&#x2292;',
		'sqsupset' => '&#x2290;',
		'sqsupseteq' => '&#x2292;',
		'squ' => '&#x25A1;',
		'square' => '&#x25A1;',
		'SquareIntersection' => '&#x2293;',
		'SquareSubset' => '&#x228F;',
		'SquareSubsetEqual' => '&#x2291;',
		'SquareSuperset' => '&#x2290;',
		'SquareSupersetEqual' => '&#x2292;',
		'SquareUnion' => '&#x2294;',
		'squarf' => '&#x25A0;',
		'squarfb' => '&#xE507;',
		'squarfbl' => '&#xE506;',
		'squarfbr' => '&#x25EA;',
		'squarfl' => '&#x25E7;',
		'squarfr' => '&#x25E8;',
		'squarft' => '&#xE509;',
		'squarftl' => '&#x25E9;',
		'squarftr' => '&#xE508;',
		'squf' => '&#x25AA;',
		'srarr' => '&#xE232;',
		'Sscr' => '&#xE4D7;',
		'sscr' => '&#xE4F0;',
		'ssetmn' => '&#xE844;',
		'ssmile' => '&#xE303;',
		'sstarf' => '&#x22C6;',
		'Star' => '&#x22C6;',
		'star' => '&#x22C6;',
		'starf' => '&#x2605;',
		'straightepsilon' => '&#x220A;',
		'straightphi' => '&#x03C6;',
		'strns' => '&#xE380;',
		'Sub' => '&#x22D0;',
		'sub' => '&#x2282;',
		'subdot' => '&#xE262;',
		'subE' => '&#x2286;',
		'sube' => '&#x2286;',
		'subedot' => '&#xE34F;',
		'submult' => '&#xE343;',
		'subnE' => '&#x228A;',
		'subne' => '&#x228A;',
		'subplus' => '&#xE341;',
		'subrarr' => '&#xE33F;',
		'Subset' => '&#x22D0;',
		'subset' => '&#x2282;',
		'subseteq' => '&#x2286;',
		'subseteqq' => '&#x2286;',
		'SubsetEqual' => '&#x2286;',
		'subsetneq' => '&#x228A;',
		'subsetneqq' => '&#x228A;',
		'subsim' => '&#xE345;',
		'subsub' => '&#xE349;',
		'subsup' => '&#xE347;',
		'succ' => '&#x227B;',
		'succapprox' => '&#x227F;',
		'succcurlyeq' => '&#x227D;',
		'Succeeds' => '&#x227B;',
		'SucceedsEqual' => '&#x227D;',
		'SucceedsSlantEqual' => '&#x227D;',
		'SucceedsTilde' => '&#x227F;',
		'succeq' => '&#x227D;',
		'succnapprox' => '&#x22E9;',
		'succneqq' => '&#xE2B5;',
		'succnsim' => '&#x22E9;',
		'succsim' => '&#x227F;',
		'SuchThat' => '&#x220D;',
		'Sum' => '&#x2211;',
		'sum' => '&#x2211;',
		'sung' => '&#x2669;',
		'Sup' => '&#x22D1;',
		'sup' => '&#x2283;',
		'sup1' => '&#x00B9;',
		'sup2' => '&#x00B2;',
		'sup3' => '&#x00B3;',
		'supdot' => '&#xE263;',
		'supdsub' => '&#xE34C;',
		'supE' => '&#x2287;',
		'supe' => '&#x2287;',
		'supedot' => '&#xE350;',
		'Superset' => '&#x2283;',
		'SupersetEqual' => '&#x2287;',
		'suphsol' => '&#xE34E;',
		'suphsub' => '&#xE34B;',
		'suplarr' => '&#xE340;',
		'supmult' => '&#xE344;',
		'supnE' => '&#x228B;',
		'supne' => '&#x228B;',
		'supplus' => '&#xE342;',
		'Supset' => '&#x22D1;',
		'supset' => '&#x2283;',
		'supseteq' => '&#x2287;',
		'supseteqq' => '&#x2287;',
		'supsetneq' => '&#x228B;',
		'supsetneqq' => '&#x228B;',
		'supsim' => '&#xE346;',
		'supsub' => '&#xE348;',
		'supsup' => '&#xE34A;',
		'swarhk' => '&#xE20A;',
		'swArr' => '&#x21D9;',
		'swarr' => '&#x2199;',
		'swarrow' => '&#x2199;',
		'swnwar' => '&#xE210;',
		'target' => '&#x2316;',
		'tau' => '&#x03C4;',
		'tbrk' => '&#xE2EF;',
		'tdot' => '&#x20DB;',
		'telrec' => '&#x2315;',
		'Tfr' => '&#xE48F;',
		'tfr' => '&#xE4A8;',
		'there4' => '&#x2234;',
		'Therefore' => '&#x2234;',
		'therefore' => '&#x2234;',
		'Theta' => '&#x0398;',
		'theta' => '&#x03B8;',
		'thetav' => '&#x03D1;',
		'thickapprox' => '&#xE306;',
		'thicksim' => '&#xE429;',
		'thinsp' => '&#x2009;',
		'thkap' => '&#xE306;',
		'thksim' => '&#xE429;',
		'Tilde' => '&#x223C;',
		'tilde' => '&#x0303;',
		'TildeEqual' => '&#x2243;',
		'TildeFullEqual' => '&#x2245;',
		'TildeTilde' => '&#x2248;',
		'times' => '&#x00D7;',
		'timesb' => '&#x22A0;',
		'timesbar' => '&#xE28E;',
		'timesd' => '&#xE26D;',
		'tint' => '&#x222D;',
		'toea' => '&#xE20E;',
		'top' => '&#x22A4;',
		'topbot' => '&#x2336;',
		'topcir' => '&#xE383;',
		'Topf' => '&#xE4BE;',
		'topfork' => '&#xE31C;',
		'tosa' => '&#xE20F;',
		'tprime' => '&#x2034;',
		'trade' => '&#x2122;',
		'triangle' => '&#x25B5;',
		'triangledown' => '&#x25BF;',
		'triangleleft' => '&#x25C3;',
		'trianglelefteq' => '&#x22B4;',
		'triangleq' => '&#x225C;',
		'triangleright' => '&#x25B9;',
		'trianglerighteq' => '&#x22B5;',
		'tridot' => '&#x25EC;',
		'trie' => '&#x225C;',
		'triminus' => '&#xE27C;',
		'TripleDot' => '&#x20DB;',
		'triplus' => '&#xE27B;',
		'trisb' => '&#xE27E;',
		'tritime' => '&#xE27D;',
		'trpezium' => '&#xE2EC;',
		'Tscr' => '&#xE4D8;',
		'tscr' => '&#xE4F1;',
		'twixt' => '&#x226C;',
		'twoheadleftarrow' => '&#x219E;',
		'twoheadrightarrow' => '&#x21A0;',
		'Uarr' => '&#x219F;',
		'uArr' => '&#x21D1;',
		'uarr' => '&#x2191;',
		'Uarrocir' => '&#xE237;',
		'udarr' => '&#x21C5;',
		'udhar' => '&#xE218;',
		'ufisht' => '&#xE24B;',
		'Ufr' => '&#xE490;',
		'ufr' => '&#xE4A9;',
		'uHar' => '&#xE226;',
		'uharl' => '&#x21BF;',
		'uharr' => '&#x21BE;',
		'uhblk' => '&#x2580;',
		'ulcorn' => '&#x231C;',
		'ulcorner' => '&#x231C;',
		'ulcrop' => '&#x230F;',
		'ultri' => '&#xE2E4;',
		'uml' => '&#x0308;',
		'UnderLine' => '&#x0332;',
		'Union' => '&#x22C3;',
		'UnionPlus' => '&#x228E;',
		'Uopf' => '&#xE4BF;',
		'UpArrow' => '&#x2191;',
		'Uparrow' => '&#x21D1;',
		'uparrow' => '&#x2191;',
		'UpArrowDownArrow' => '&#x21C5;',
		'UpDownArrow' => '&#x2195;',
		'Updownarrow' => '&#x21D5;',
		'updownarrow' => '&#x2195;',
		'UpEquilibrium' => '&#xE218;',
		'upharpoonleft' => '&#x21BF;',
		'upharpoonright' => '&#x21BE;',
		'uplus' => '&#x228E;',
		'UpperLeftArrow' => '&#x2196;',
		'UpperRightArrow' => '&#x2197;',
		'Upsi' => '&#x03D2;',
		'upsi' => '&#x03C5;',
		'Upsilon' => '&#x03D2;',
		'upsilon' => '&#x03C5;',
		'UpTee' => '&#x22A5;',
		'upuparrows' => '&#x21C8;',
		'urcorn' => '&#x231D;',
		'urcorner' => '&#x231D;',
		'urcrop' => '&#x230E;',
		'urtri' => '&#xE2E2;',
		'Uscr' => '&#xE4D9;',
		'uscr' => '&#xE4F2;',
		'utdot' => '&#x22F0;',
		'utri' => '&#x25B5;',
		'utrif' => '&#x25B4;',
		'uuarr' => '&#x21C8;',
		'uwangle' => '&#xE3AB;',
		'vangrt' => '&#x22BE;',
		'varepsilon' => '&#x03B5;',
		'varkappa' => '&#x03F0;',
		'varnothing' => '&#x2205;',
		'varphi' => '&#x03D5;',
		'varpi' => '&#x03D6;',
		'varpropto' => '&#x221D;',
		'vArr' => '&#x21D5;',
		'varr' => '&#x2195;',
		'varrho' => '&#x03F1;',
		'varsigma' => '&#x03C2;',
		'varsubsetneq' => '&#xE2B9;',
		'varsubsetneqq' => '&#xE2B8;',
		'varsupsetneq' => '&#xE2BA;',
		'varsupsetneqq' => '&#xE2BB;',
		'vartheta' => '&#x03D1;',
		'vartriangleleft' => '&#x22B2;',
		'vartriangleright' => '&#x22B3;',
		'Vbar' => '&#xE30D;',
		'vBar' => '&#xE310;',
		'vBarv' => '&#xE312;',
		'VDash' => '&#x22AB;',
		'Vdash' => '&#x22A9;',
		'vDash' => '&#x22A8;',
		'vdash' => '&#x22A2;',
		'Vdashl' => '&#xE313;',
		'Vee' => '&#x22C1;',
		'vee' => '&#x2228;',
		'veebar' => '&#x22BB;',
		'veeeq' => '&#x225A;',
		'vellip' => '&#x22EE;',
		'Verbar' => '&#x2016;',
		'verbar' => '&#x007C;',
		'Vert' => '&#x2016;',
		'vert' => '&#x007C;',
		'VerticalBar' => '&#x2223;',
		'VerticalTilde' => '&#x2240;',
		'Vfr' => '&#xE491;',
		'vfr' => '&#xE4AA;',
		'vltri' => '&#x22B2;',
		'vnsub' => '&#x2284;',
		'vnsup' => '&#x2285;',
		'Vopf' => '&#xE4C0;',
		'vprop' => '&#x221D;',
		'vrtri' => '&#x22B3;',
		'Vscr' => '&#xE4DA;',
		'vscr' => '&#xE4F3;',
		'vsubnE' => '&#xE2B8;',
		'vsubne' => '&#xE2B9;',
		'vsupnE' => '&#xE2BB;',
		'vsupne' => '&#xE2BA;',
		'Vvdash' => '&#x22AA;',
		'vzigzag' => '&#xE2EB;',
		'wedbar' => '&#xE265;',
		'Wedge' => '&#x22C0;',
		'wedge' => '&#x2227;',
		'wedgeq' => '&#x2259;',
		'weierp' => '&#x2118;',
		'Wfr' => '&#xE492;',
		'wfr' => '&#xE4AB;',
		'Wopf' => '&#xE4C1;',
		'wp' => '&#x2118;',
		'wr' => '&#x2240;',
		'wreath' => '&#x2240;',
		'Wscr' => '&#xE4DB;',
		'wscr' => '&#xE4F4;',
		'xcap' => '&#x22C2;',
		'xcirc' => '&#x25CB;',
		'xcup' => '&#x22C3;',
		'xdtri' => '&#x25BD;',
		'Xfr' => '&#xE493;',
		'xfr' => '&#xE4AC;',
		'xhArr' => '&#xE202;',
		'xharr' => '&#xE203;',
		'Xi' => '&#x039E;',
		'xi' => '&#x03BE;',
		'xlArr' => '&#xE200;',
		'xlarr' => '&#xE201;',
		'xmap' => '&#xE208;',
		'xnis' => '&#xE3A3;',
		'xodot' => '&#x2299;',
		'Xopf' => '&#xE4C2;',
		'xoplus' => '&#x2295;',
		'xotime' => '&#x2297;',
		'xrArr' => '&#xE204;',
		'xrarr' => '&#xE205;',
		'Xscr' => '&#xE4DC;',
		'xscr' => '&#xE4F5;',
		'xsqcup' => '&#x2294;',
		'xuplus' => '&#x228E;',
		'xutri' => '&#x25B3;',
		'xvee' => '&#x22C1;',
		'xwedge' => '&#x22C0;',
		'yen' => '&#x00A5;',
		'Yfr' => '&#xE494;',
		'yfr' => '&#xE4AD;',
		'Yopf' => '&#xE4C3;',
		'Yscr' => '&#xE4DD;',
		'yscr' => '&#xE4F6;',
		'zeta' => '&#x03B6;',
		'Zfr' => '&#x2124;',
		'zfr' => '&#xE4AE;',
		'zigrarr' => '&#xE244;',
		'Zopf' => '&#xE4C4;',
		'Zscr' => '&#xE4DE;',
		'zscr' => '&#xE4F7;'
		);
		return $charset;
			
		

	// cas particuliers pour la translitteration
	case 'translit':
		$GLOBALS['CHARSET'][$charset] = array (
		// latin
		128=>'euro', 131=>'f', 140=>'OE', 147=>'\'\'', 148=>'\'\'', 153=>'TM', 156=>'oe', 159=>'Y', 160=>' ',
		161=>'!', 162=>'c', 163=>'L', 164=>'O', 165=>'yen',166=>'|',
		167=>'p',169=>'(c)', 171=>'<<',172=>'-',173=>'-',174=>'(R)',
		176=>'o',177=>'+-',181=>'mu',182=>'p',183=>'.',187=>'>>', 192=>'A',
		193=>'A', 194=>'A', 195=>'A', 196=>'A', 197=>'A', 198=>'AE', 199=>'C',
		200=>'E', 201=>'E', 202=>'E', 203=>'E', 204=>'I', 205=>'I', 206=>'I',
		207=>'I', 209=>'N', 210=>'O', 211=>'O', 212=>'O', 213=>'O', 214=>'O',
		216=>'O', 217=>'U', 218=>'U', 219=>'U', 220=>'U', 223=>'ss', 224=>'a',
		225=>'a', 226=>'a', 227=>'a', 228=>'a', 229=>'a', 230=>'ae', 231=>'c',
		232=>'e', 233=>'e', 234=>'e', 235=>'e', 236=>'i', 237=>'i', 238=>'i',
		239=>'i', 241=>'n', 242=>'o', 243=>'o', 244=>'o', 245=>'o', 246=>'o',
		248=>'o', 249=>'u', 250=>'u', 251=>'u', 252=>'u', 255=>'y',

		// turc
		286=>'G', 287=>'g', 304=>'I', 305=>'i', 350=>'S', 351=>'s',

		// esperanto
		264 => 'Cx',265 => 'cx',
		284 => 'Gx',285 => 'gx',
		292 => 'Hx',293 => 'hx',
		308 => 'Jx',309 => 'jx',
		348 => 'Sx',349 => 'sx',
		364 => 'Ux',365 => 'ux',

		// cyrillique
		1026=>'D%', 1027=>'G%', 8218=>'\'', 1107=>'g%', 8222=>'"', 8230=>'...',
		8224=>'/-', 8225=>'/=',  8364=>'EUR', 8240=>'0/00', 1033=>'LJ',
		8249=>'<', 1034=>'NJ', 1036=>'KJ', 1035=>'Ts', 1039=>'DZ',  1106=>'d%',
		8216=>'`', 8217=>'\'', 8220=>'"', 8221=>'"', 8226=>' o ', 8211=>'-',
		8212=>'--', 8212=>'~',  8482=>'(TM)', 1113=>'lj', 8250=>'>', 1114=>'nj',
		1116=>'kj', 1115=>'ts', 1119=>'dz',  1038=>'V%', 1118=>'v%', 1032=>'J%',
		1168=>'G3', 1025=>'IO',  1028=>'IE', 1031=>'YI', 1030=>'II',
		1110=>'ii', 1169=>'g3', 1105=>'io', 8470=>'No.', 1108=>'ie',
		1112=>'j%', 1029=>'DS', 1109=>'ds', 1111=>'yi', 1040=>'A', 1041=>'B',
		1042=>'V', 1043=>'G', 1044=>'D',  1045=>'E', 1046=>'ZH', 1047=>'Z',
		1048=>'I', 1049=>'J', 1050=>'K', 1051=>'L', 1052=>'M', 1053=>'N',
		1054=>'O', 1055=>'P', 1056=>'R', 1057=>'S', 1058=>'T', 1059=>'U',
		1060=>'F', 1061=>'H', 1062=>'C',  1063=>'CH', 1064=>'SH', 1065=>'SCH',
		1066=>'"', 1067=>'Y', 1068=>'\'', 1069=>'`E', 1070=>'YU',  1071=>'YA',
		1072=>'a', 1073=>'b', 1074=>'v', 1075=>'g', 1076=>'d', 1077=>'e',
		1078=>'zh', 1079=>'z',  1080=>'i', 1081=>'j', 1082=>'k', 1083=>'l',
		1084=>'m', 1085=>'n', 1086=>'o', 1087=>'p', 1088=>'r',  1089=>'s',
		1090=>'t', 1091=>'u', 1092=>'f', 1093=>'h', 1094=>'c', 1095=>'ch',
		1096=>'sh', 1097=>'sch',  1098=>'"', 1099=>'y', 1100=>'\'', 1101=>'`e',
		1102=>'yu', 1103=>'ya',
		
		// vietnamien en translitteration de base
		7843=>"a",7841=>"a",7845=>"a",7847=>"a",7849=>"a",7851=>"a",7853=>"a",
		7855=>"a",7857=>"a",7859=>"a",7861=>"a",7863=>"a",
		7842=>"A",7840=>"A",7844=>"A",7846=>"A",7848=>"A",
		7850=>"A",7852=>"A",7854=>"A",7856=>"A",7858=>"A",7860=>"A",
		7862=>"A",7867=>"e",7869=>"e",7865=>"e",
		7871=>"e",7873=>"e",7875=>"e",7877=>"e",7879=>"e",
		7866=>"E",7868=>"E",7864=>"E",7870=>"E",7872=>"E",7874=>"E",
		7876=>"E",7878=>"E",7881=>"i",7883=>"i",
		7880=>"I",7882=>"I",
		7887=>"o",7885=>"o",7889=>"o",7891=>"o",7893=>"o",
		7895=>"o",7897=>"o",417=>"o",7899=>"o",7901=>"o",7903=>"o",7905=>"o",
		7907=>"o",7886=>"O",7884=>"O",
		7888=>"O",7890=>"O",7892=>"O",7894=>"O",7896=>"O",416=>"O",7898=>"O",
		7900=>"O",7902=>"O",7904=>"O",7906=>"O",7911=>"u",
		361=>"u",7909=>"u",432=>"u",7913=>"u",7915=>"u",7917=>"u",7919=>"u",
		7921=>"u",7910=>"U",360=>"U",7908=>"U",431=>"U",
		7912=>"U",7914=>"U",7916=>"U",7918=>"U",7920=>"U",253=>"y",7923=>"y",
		7927=>"y",7929=>"y",7925=>"y",221=>"Y",7922=>"Y",7926=>"Y",7928=>"Y",
		7924=>"Y",273=>"d"
		
		);
		return $charset;

	// translitteration complexe
	case 'translitcomplexe':
		load_charset('translit');
		$trans = $GLOBALS['CHARSET']['translit'];

		$translit_c = array (
		// vietnamien
		225=>"a'", 224=>"a`",7843=>"a?",227=>"a~",7841=>"a.",
		226=>"a^",7845=>"a^'",7847=>"a^`",7849=>"a^?",7851=>"a^~",7853=>"a^.",259=>"a(",
		7855=>"a('",7857=>"a(`",7859=>"a(?",7861=>"a(~",7863=>"a(.",193=>"A'",192=>"A`",
		7842=>"A?",195=>"A~",7840=>"A.",194=>"A^",7844=>"A^'",7846=>"A^`",7848=>"A^?",
		7850=>"A^~",7852=>"A^.",258=>"A(",7854=>"A('",7856=>"A(`",7858=>"A(?",7860=>"A(~",
		7862=>"A(.",233=>"e'",232=>"e`",7867=>"e?",7869=>"e~",7865=>"e.",234=>"e^",
		7871=>"e^'",7873=>"e^`",7875=>"e^?",7877=>"e^~",7879=>"e^.",201=>"E'",200=>"E`",
		7866=>"E?",7868=>"E~",7864=>"E.",202=>"E^",7870=>"E^'",7872=>"E^`",7874=>"E^?",
		7876=>"E^~",7878=>"E^.",237=>"i'",236=>"i`",7881=>"i?",297=>"i~",7883=>"i.",
		205=>"I'",204=>"I`",7880=>"I?",296=>"I~",7882=>"I.",243=>"o'",242=>"o`",
		7887=>"o?",245=>"o~",7885=>"o.",244=>"o^",7889=>"o^'",7891=>"o^`",7893=>"o^?",
		7895=>"o^~",7897=>"o^.",417=>"o+",7899=>"o+'",7901=>"o+`",7903=>"o+?",7905=>"o+~",
		7907=>"o+.",211=>"O'",210=>"O`",7886=>"O?",213=>"O~",7884=>"O.",212=>"O^",
		7888=>"O^'",7890=>"O^`",7892=>"O^?",7894=>"O^~",7896=>"O^.",416=>"O+",7898=>"O+'",
		7900=>"O+`",7902=>"O+?",7904=>"O+~",7906=>"O+.",250=>"u'",249=>"u`",7911=>"u?",
		361=>"u~",7909=>"u.",432=>"u+",7913=>"u+'",7915=>"u+`",7917=>"u+?",7919=>"u+~",
		7921=>"u+.",218=>"U'",217=>"U`",7910=>"U?",360=>"U~",7908=>"U.",431=>"U+",
		7912=>"U+'",7914=>"U+`",7916=>"U+?",7918=>"U+~",7920=>"U+.",253=>"y'",7923=>"y`",
		7927=>"y?",7929=>"y~",7925=>"y.",221=>"Y'",7922=>"Y`",7926=>"Y?",7928=>"Y~",
		7924=>"Y.",273=>"d-",208=>"D-",

		// allemand
		228=>'ae',246=>'oe',252=>'ue',196=>'Ae',214=>'Oe',220=>'Ue'
		);
		foreach($translit_c as $u=>$t)
			$trans[$u] = $t;
		$GLOBALS['CHARSET'][$charset] = $trans;
		return $charset;


	default:
		$GLOBALS['CHARSET'][$charset] = array();
		return $charset;
	}
}

//
// Verifier qu'on peut utiliser mb_string
//
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
		AND mb_detect_order(lire_meta('charset'))
		) {
			mb_internal_encoding('utf-8');
			$mb = 1;
		} else
			$mb = -1;
	}

	return ($mb == 1);
}

// Detecter les versions buggees d'iconv
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
function test_pcre_unicode() {
	static $pcre_ok = 0;

	if (!$pcre_ok) {
		$s = " ".chr(195).chr(169)."t".chr(195).chr(169)." ";
		if (preg_match(',\W\w\w\w\W,u', $s)) $pcre_ok = 1;
		else $pcre_ok = -1;
	}
	return $pcre_ok == 1;
}

// Plages alphanumeriques (incomplet...)
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
function plage_punct_unicode() {
	return '\xE2(\x80[\x80-\xBF]|\x81[\x80-\xAF])';
}

//
// Transformer les &eacute; en &#123;
//
function html2unicode($texte) {
	static $trans;
	if (!$trans) {
		global $CHARSET;
		load_charset('html');
		foreach ($CHARSET['html'] as $key => $val) {
			$trans["&$key;"] = $val;
		}
	}

	return strtr($texte, $trans);
}

//
// Transformer les &eacute; en &#123;
//
function mathml2unicode($texte) {
	static $trans;
	if (!$trans) {
		global $CHARSET;
		load_charset('mathml');
		
		foreach ($CHARSET['mathml'] as $key => $val)
			$trans["&$key;"] = $val;
	}

	return strtr($texte, $trans);
}


//
// Transforme une chaine en entites unicode &#129;
//
function charset2unicode($texte, $charset='AUTO', $forcer = false) {
	static $trans;

	if ($charset == 'AUTO')
		$charset = lire_meta('charset');

	$charset = strtolower($charset);

	switch ($charset) {
	case 'utf-8':
		return utf_8_to_unicode($texte);

	case 'iso-8859-1':
		// corriger caracteres non-conformes issus de Windows (CP-1252)
		$faux_latin = array(
			chr(138) => "&#352;", // Scaron
			chr(140) => "&#338;", // OElig
			chr(142) => "&#381;", // Zcaron
			chr(154) => "&#353;", // scaron
			chr(156) => "&#339;", // oelig
			chr(158) => "&#382;" // zcaron
		);
		$texte = strtr($texte, $faux_latin);

		// On commente cet appel tant qu'il reste des spip v<1.5 dans la nature
		// pour que le filtre |entites_unicode donne des backends lisibles sur ces spips.
		if (!$forcer) return $texte;

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
			load_charset($charset);
			foreach ($CHARSET[$charset] as $key => $val) {
				$trans[$charset][chr($key)] = '&#'.$val.';';
			}
		}
		if (count($trans[$charset]))
			return strtr($texte, $trans[$charset]);

		// Sinon demander a iconv (malgre le fait qu'il coupe quand un
		// caractere n'appartient pas au charset, mais c'est un probleme
		// surtout en utf-8, gere ci-dessus)
		if (test_iconv()) {
			$s = iconv($charset, 'utf-32le', $texte);
			if ($s) return utf_32_to_unicode($s);
		}

		// Au pire ne rien faire
		spip_log("erreur charset $charset non supporte");
		return $texte;
	}
}

//
// Transforme les entites unicode &#129; dans le charset specifie
//
function unicode2charset($texte, $charset='AUTO') {
	static $CHARSET_REVERSE;
	if ($charset == 'AUTO')
		$charset = lire_meta('charset');

	switch($charset) {
	case 'utf-8':
		return unicode_to_utf_8($texte);
		break;

	default:
		$charset = load_charset($charset);

		if (!is_array($CHARSET_REVERSE[$charset])) {
			$CHARSET_REVERSE[$charset] = array_flip($GLOBALS['CHARSET'][$charset]);
		}

		$trans = array();
		// Construire la table de remplacements
		// 1. Entites decimales (type "&#123;")
		if (preg_match_all(',&#([0-9]+);,', $texte, $regs, PREG_PATTERN_ORDER)) {
			$entites = array_flip($regs[1]);
			foreach ($entites as $e => $v) {
				if ($s = ($e < 128) ? $e : $CHARSET_REVERSE[$charset][intval($e)])
					$trans['&#'.$e.';'] = chr($s);
			}
		}
		// 2. Entites hexadecimales (type "&#xD;")
		if (preg_match_all(',&#x([0-9a-zA-Z]+);,', $texte, $regs, PREG_PATTERN_ORDER)) {
			$entites = array_flip($regs[1]);
			foreach ($entites as $e => $v) {
				$h = hexdec($e);
				if ($s = ($h < 128) ? $h : $CHARSET_REVERSE[$charset][$h])
					$trans['&#x'.$e.';'] = chr($s);
			}
		}
		$texte = strtr($texte, $trans);
		return $texte;
	}
}


// Importer un texte depuis un charset externe vers le charset du site
// (les caracteres non resolus sont transformes en &#123;)
function importer_charset($texte, $charset = 'AUTO') {
	return unicode2charset(charset2unicode($texte, $charset, true));
}

// UTF-8
function utf_8_to_unicode($source) {

	// mb_string : methode rapide
	if (init_mb_string()) {
		$convmap = array(0x7F, 0xFFFFFF, 0x0, 0xFFFFFF);
		return mb_encode_numericentity($source, $convmap, 'UTF-8');
	}

	// Sinon methode pas a pas
	static $decrement;
	static $shift;

	// Cf. php.net, par Ronen. Adapte pour compatibilite php3
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
			$encodedLetter = "&#". ereg_replace('^0+', '', $decimalCode) . ';';
			$encodedString .= $encodedLetter;
		}
	}
	return $encodedString;
}

// UTF-32 ne sert plus que si on passe par iconv, c'est-a-dire quand
// mb_string est absente ou ne connait pas notre charset
// mais on l'optimise quand meme par mb_string
// => tout ca sera osolete quand on sera surs d'avoir mb_string
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

function unicode_to_utf_8($texte) {
	while (preg_match(',&#0*([0-9]+);,', $texte, $regs)) {
		$s = caractere_utf_8($regs[1]);
		$texte = str_replace($regs[0], $s, $texte);
	}
	return $texte;
}

// convertit les &#264; en \u0108
function unicode_to_javascript($texte) {
	while (preg_match(',&#0*([0-9]+);,', $texte, $regs) AND !$vu[$regs[1]]) {
		$num = $regs[1];
		$vu[$num] = true;
		$s = '\u'.sprintf("%04x", $num);
		$texte = str_replace($regs[0], $s, $texte);
	}
	return $texte;
}

// convertit les %uxxxx (envoyes par javascript)
function javascript_to_unicode ($texte) {
	while (ereg("%u([0-9A-F][0-9A-F][0-9A-F][0-9A-F])", $texte, $regs))
		$texte = str_replace($regs[0],"&#".hexdec($regs[1]).";", $texte);
	return $texte;
}
// convertit les %E9 (envoyes par le browser) en chaine du charset du site (binaire)
function javascript_to_binary ($texte) {
	while (ereg("%([0-9A-F][0-9A-F])", $texte, $regs))
		$texte = str_replace($regs[0],chr(hexdec($regs[1])), $texte);
	return $texte;
}


//
// Translitteration charset => ascii (pour l'indexation)
// Attention les caracteres non reconnus sont renvoyes en utf-8
//
function translitteration($texte, $charset='AUTO', $complexe='') {
	static $trans;
	if ($charset == 'AUTO')
		$charset = lire_meta('charset');

	$table_translit ='translit'.$complexe;

	// 1. Passer le charset et les &eacute en utf-8
	$texte = unicode_to_utf_8(html2unicode(charset2unicode($texte, $charset, true)));

	// 2. Translitterer grace a la table predefinie
	if (!$trans[$complexe]) {
		global $CHARSET;
		load_charset($table_translit);
		foreach ($CHARSET[$table_translit] as $key => $val)
			$trans[$complexe][caractere_utf_8($key)] = $val;
	}

	return strtr($texte, $trans[$complexe]);
}

function translitteration_complexe($texte) {
	return translitteration($texte,'AUTO','complexe');
}

// Reconnaitre le BOM utf-8 (0xEFBBBF)
function bom_utf8($texte) {
	return (substr($texte, 0,3) == chr(0xEF).chr(0xBB).chr(0xBF));
}

// Transcode une page (probablement attrapee sur le web) en essayant
// par tous les moyens de deviner son charset (y compris headers HTTP)
function transcoder_page($texte, $headers='') {

	// Reconnaitre le BOM utf-8 (0xEFBBBF)
	if (bom_utf8($texte))
		$charset = 'utf-8';
	// charset precise par le contenu (xml)
	else if (preg_match(',<[?]xml[^>]*encoding[^>]*=[^>]*([-_a-z0-9]+?),Uims', $texte, $regs))
		$charset = trim(strtolower($regs[1]));
	// charset precise par le contenu (html)
	else if (preg_match(',<(meta|html|body)[^>]*charset[^>]*=[^>]*([-_a-z0-9]+?),Uims', $texte, $regs))
		$charset = trim(strtolower($regs[2]));
	// charset de la reponse http
	else if (preg_match(',charset=([-_a-z0-9]+),i', $headers, $regs))
		$charset = trim(strtolower($regs[1]));

	// normaliser les noms du shif-jis japonais
	if (preg_match(',^(x|shift)[_-]s?jis$,i', $charset))
		$charset = 'shift-jis';

	if ($charset) {
		spip_log("charset source detecte : $charset");
	} else {
		// valeur par defaut
		$charset = 'iso-8859-1';
		spip_log("pas de charset detecte, on suppose : $charset");
	}

	return importer_charset($texte, $charset);
}

// Initialisation
$GLOBALS['CHARSET'] = Array();

?>
