<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_CHARSETS")) return;
define("_ECRIRE_INC_CHARSETS", "1");


/* charsets supportes :
	utf-8 ;
	iso-8859-1 ; iso-8859-15 ;
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
		while (list($num,$val) = each($mod))
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
		'apos' => "'", 'lt' => '<', 'gt' => '>'
		);
		return $charset;

	// cas particulier pour la translitteration
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
		216=>'O', 217=>'U', 218=>'U', 219=>'U', 220=>'U', 223=>'B', 224=>'a',
		225=>'a', 226=>'a', 227=>'a', 228=>'a', 229=>'a', 230=>'ae', 231=>'c',
		232=>'e', 233=>'e', 234=>'e', 235=>'e', 236=>'i', 237=>'i', 238=>'i',
		239=>'i', 241=>'n', 242=>'o', 243=>'o', 244=>'o', 245=>'o', 246=>'o',
		248=>'o', 249=>'u', 250=>'u', 251=>'u', 252=>'u', 255=>'y',

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
		1102=>'yu', 1103=>'ya'
		);

		// translitteration specifique du vietnamien
		// (necessaire pour le moteur de recherche car les mots sont tous tres courts)
		if ($langue_site == 'vi') {
			$translit_vi = array (225=>"a'", 224=>"a`",7843=>"a?",227=>"a~",7841=>"a.",
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
			7924=>"Y.",273=>"d-",208=>"D-");
			while (list($u,$t) = each($translit_vi))
				$GLOBALS['CHARSET'][$charset][$u] = $t;
		}
		return $charset;

	default:
		spip_log("erreur charset $charset non supporte");
		$GLOBALS['CHARSET'][$charset] = array();
		return $charset;
	}
}


// Detecter les versions buggees d'iconv
function test_iconv() {
	static $iconv_ok;

	if (!$iconv_ok) {
		if (!$GLOBALS['flag_iconv']) $iconv_ok = -1;
		else {
			if (utf_32_to_unicode(@iconv('utf-8', 'utf-32', 'chaine de test')) == 'chaine de test')
				$iconv_ok = 1;
			else
				$iconv_ok = -1;
		}
	}
	return $iconv_ok == 1;
}


//
// Transformer les &eacute; en &#123;
//
function html2unicode($texte) {
	static $trans;
	if (!$trans) {
		global $CHARSET;
		load_charset('html');
		reset($CHARSET['html']);
		while (list($key, $val) = each($CHARSET['html'])) {
			$trans["&$key;"] = $val;
		}
	}

	if ($GLOBALS['flag_strtr2']) return strtr($texte, $trans);

	reset($trans);
	while (list($from, $to) = each($trans)) {
		$texte = str_replace($from, $to, $texte);
	}
	return $texte;
}


//
// Transforme une chaine en entites unicode &#129;
//
function charset2unicode($texte, $charset='AUTO', $forcer = false) {
	static $trans;

	if ($charset == 'AUTO')
		$charset = lire_meta('charset');

	switch ($charset) {
	case 'utf-8':
		// Le passage par utf-32 devrait etre plus rapide
		// (traitements PHP reduits au minimum)
		if (test_iconv()) {
			$s = iconv('utf-8', 'utf-32', $texte);
			if ($s) return utf_32_to_unicode($s);
		}
		return utf_8_to_unicode($texte);

	case 'iso-8859-1':
		// On commente cet appel tant qu'il reste des spip v<1.5 dans la nature
		// pour que le filtre |entites_unicode donne des backends lisibles sur ces spips.
		if (!$forcer) return $texte;

	default:
		if (test_iconv()) {
			$s = iconv($charset, 'utf-32', $texte);
			if ($s) return utf_32_to_unicode($s);
		}

		if (!$trans[$charset]) {
			global $CHARSET;
			load_charset($charset);
			reset($CHARSET[$charset]);
			while (list($key, $val) = each($CHARSET[$charset])) {
				$trans[$charset][chr($key)] = '&#'.$val.';';
			}
		}
		if ($trans[$charset]) {
			if ($GLOBALS['flag_strtr2'])
				$texte = strtr($texte, $trans[$charset]);
			else {
				reset($trans[$charset]);
				while (list($from, $to) = each($trans[$charset])) {
					$texte = str_replace($from, $to, $texte);
				}
			}
		}
		return $texte;
	}
}

//
// Transforme les entites unicode &#129; dans le charset specifie
//
function unicode2charset($texte, $charset='AUTO') {
	static $CHARSET_REVERSE;
	if ($charset == 'AUTO')
		$charset=lire_meta('charset');

	switch($charset) {
	case 'utf-8':
		return unicode_to_utf_8($texte);
		break;

	default:
		$charset = load_charset($charset);

		// array_flip
		if (!is_array($CHARSET_REVERSE[$charset])) {
			$trans = $GLOBALS['CHARSET'][$charset];
			while (list($chr,$uni) = each($trans))
				$CHARSET_REVERSE[$charset][$uni] = $chr;
		}

		while ($a = strpos(' '.$texte, '&')) {
			$traduit .= substr($texte,0,$a-1);
			$texte = substr($texte,$a-1);
			if (eregi('^&#0*([0-9]+);',$texte,$match) AND ($s = $CHARSET_REVERSE[$charset][$match[1]]))
				$texte = str_replace($match[0], chr($s), $texte);
			// avancer d'un cran
			$traduit .= $texte[0];
			$texte = substr($texte,1);
		}
		return $traduit.$texte;
	}
}


// Importer un texte depuis un charset externe vers le charset du site
// (les caracteres non resolus sont transformes en &#123;)
function importer_charset($texte, $charset = 'AUTO') {
	return unicode2charset(charset2unicode($texte, $charset, true));
}

// UTF-8
function utf_8_to_unicode($source) {
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

// UTF-32 : utilise en interne car plus rapide qu'UTF-8
function utf_32_to_unicode($source) {
	$texte = "";
	// Plusieurs iterations pour eviter l'explosion memoire
	while ($source) {
		$words = unpack("V*", substr($source, 0, 1024));
		$source = substr($source, 1024);
		if (is_array($words)) {
			reset($words);
			while (list(, $word) = each($words)) {
				if ($word < 128) $texte .= chr($word);
				else if ($word != 65279) $texte .= '&#'.$word.';';
			}
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
	if($num<32768)
		return chr(($num>>12)+224).chr((($num>>6)&63)+128).chr(($num&63)+128);
	if($num<2097152)
		return chr($num>>18+240).chr((($num>>12)&63)+128).chr(($num>>6)&63+128). chr($num&63+128);
	return '';
}

function unicode_to_utf_8($texte) {
	while (ereg('&#0*([0-9]+);', $texte, $regs) AND !$vu[$regs[1]]) {
		$num = $regs[1];
		$vu[$num] = true;
		$s = caractere_utf_8($num);
		$texte = str_replace($regs[0], $s, $texte);
	}
	return $texte;
}

// convertit les &#264; en \u0108
function unicode_to_javascript($texte) {
	while (ereg('&#0*([0-9]+);', $texte, $regs) AND !$vu[$regs[1]]) {
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
function translitteration($texte, $charset='AUTO') {
	static $trans;
	if ($charset == 'AUTO')
		$charset = lire_meta('charset');

	// 1. Passer le charset et les &eacute en utf-8
	$texte = unicode_to_utf_8(html2unicode(charset2unicode($texte, $charset, true)));

	// 2. Translitterer grace a la table predefinie
	if (!$trans) {
		global $CHARSET;
		load_charset('translit');
		reset($CHARSET['translit']);
		while (list($key, $val) = each($CHARSET['translit'])) {
			$trans[caractere_utf_8($key)] = $val;
		}
	}
	if ($GLOBALS['flag_strtr2'])
		$texte = strtr($texte, $trans);
	else {
		reset($trans);
		while (list($from, $to) = each($trans)) {
			$texte = str_replace($from, $to, $texte);
		}
	}

/*
	// Le probleme d'iconv c'est qu'il risque de nous renvoyer des ? alors qu'on
	// prefere garder l'utf-8 pour que la chaine soit indexable.
	// 3. Translitterer grace a iconv
	if ($GLOBALS['flag_iconv'] && ereg('&#0*([0-9]+);', $texte)) {
		$texte = iconv('utf-8', 'ascii//translit', $texte);
	}
*/

	return $texte;
}

// Initialisation
$GLOBALS['CHARSET'] = Array();

?>
