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

// Ces commentaires vont etre substitue's en mode recherche
// voir les champs SURLIGNE dans inc-index-squel

define("MARQUEUR_SURLIGNE",  '!-- debut_surligneconditionnel -->');
define("MARQUEUR_FSURLIGNE", '!-- finde_surligneconditionnel -->');

// http://doc.spip.org/@surligner_sans_accents
function surligner_sans_accents ($mot) {
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

	if ($GLOBALS['meta']['charset'] == 'utf-8') {
		include_spip('inc/charsets');
		$mot = unicode2charset(utf_8_to_unicode($mot), 'iso-8859-1');
	}

	return strtr($mot, $accents, "AAAAAAaaaaaaOOOOOOooooooEEEEeeeeCcIIIIiiiiUUUUuuuuyNn");
}

// tres sale
// http://doc.spip.org/@split_by_char
function split_by_char($str) {
$len = strlen($str);
$streturn = array();
for ($i=0; $i<$len; $i++) {
$streturn[$i] = substr($str, $i, 1);
}
return $streturn;
}

// http://doc.spip.org/@surligner_regexp_accents
function surligner_regexp_accents ($mot) {
	$accents_regexp = array(
		"a" => "[a".chr(224).chr(225).chr(226).chr(227).chr(228).chr(229). chr(192).chr(193).chr(194).chr(195).chr(196).chr(197)."]",
		"o" => "[o".chr(242).chr(243).chr(244).chr(245).chr(246).chr(248). chr(210).chr(211).chr(212).chr(213).chr(214).chr(216)."]",
		"e" => "[e".chr(232).chr(233).chr(234).chr(235). chr(200).chr(201).chr(202).chr(203)."]",
		"c" => "[c".chr(199).chr(231)."]",
		"i" => "[i".chr(236).chr(237).chr(238).chr(239). chr(204).chr(205).chr(206).chr(207)."]",
		"u" => "[u".chr(249).chr(250).chr(251).chr(252). chr(217).chr(218).chr(219).chr(220)."]",
		"y" => "[y".chr(255)."]",
		"n" => "[n".chr(209).chr(241)."]"
	);

	$mot = surligner_sans_accents ($mot);
	if ($GLOBALS['meta']['charset'] == 'utf-8') {
		while(list($k,$s) = each ($accents_regexp)) {
			$accents_regexp_utf8[$k] = "(".join("|", split_by_char(preg_replace(',[\]\[],','',$accents_regexp[$k]))).")";
		}
		$mot = strtr(strtolower($mot), $accents_regexp_utf8);
		$mot = importer_charset($mot, 'iso-8859-1');
	} else
		$mot = strtr(strtolower($mot), $accents_regexp);

	return $mot;
}


// mettre en rouge les mots passes dans $var_recherche
// http://doc.spip.org/@surligner_mots
function surligner_mots($page, $mots) {
	global $nombre_surligne;
	include_spip('inc/texte'); // pour le reglage de $nombre_surligne
	tester_variable('nombre_surligne', 4);

	// Remplacer les caracteres potentiellement accentues dans la chaine
	// de recherche par les choix correspondants en syntaxe regexp (!)
	$mots = preg_split(',\s+,ms', $mots);

	foreach ($mots as $mot) {
		if (strlen($mot) >= 2) {
			$mot = surligner_regexp_accents(preg_quote(str_replace('/', '', $mot)));
			$mots_surligne[] = $mot;
		}
	}

	if (!$mots_surligne) return $page;

	$regexp = '/((^|>)([^<]*[^[:alnum:]_<\x80-\xFF])?)(('
	. join('|', $mots_surligne)
	. ')[[:alnum:]_\x80-\xFF]*?)/Uis';

	// en cas de surlignement limite' (champs #SURLIGNE), 
	// le compilateur a inse're' les balises de surlignement
	// sur toute la zone; reste a` raffiner.
	// On boucle pour le cas ou` il y a plusieurs zones

	$p = strpos($page, MARQUEUR_SURLIGNE);
	if ($p !== false) {
		$debut = '';
		while ($p) {
			$debut .= substr($page, 0, $p-1);
			$page = substr($page, $p+strlen(MARQUEUR_SURLIGNE));
			if (!$q = strpos($page,MARQUEUR_FSURLIGNE))
				$q = 1+strlen($page);
			$debut .= trouve_surligne(substr($page, 0, $q-1), $regexp);
			$page = substr($page, $q+strlen(MARQUEUR_FSURLIGNE));
			$p = strpos($page,MARQUEUR_SURLIGNE);
		}
		return $debut . $page;
	} else {
		// pour toute la page: ignorer ce qui est avant </head> ou <body>
		$re = '/<\/head>|<body[^>]*>/i';
		if (preg_match($re, $page, $exp)) {
			$debut = substr($page, 0, strpos($page, $exp[0])+strlen($exp[0]));
			$page = substr($page, strlen($debut));
		} else
			$debut = '';
		return $debut . trouve_surligne($page, $regexp);
	}
}

// http://doc.spip.org/@trouve_surligne
function trouve_surligne($page, $regexp) {
	// Remplacer une occurrence de mot maxi par espace inter-tag
	// (max 1 par paragraphe, sauf italiques etc.)
	// se limiter a 4 remplacements pour ne pas bouffer le CPU ;
	// traiter <textarea...>....</textarea> comme un tag.
	global $nombre_surligne;
	$page = preg_replace('/(<textarea[^>]*>)([^<>]*)(<\/textarea>)/Uis', '\1<<SPIP\2>>\3', $page);
	$page = preg_replace($regexp, '\1<span class="spip_surligne">\4</span>', $page, $nombre_surligne);
	$page = preg_replace('/(<textarea[^>]*>)<<SPIP([^<>]*)>>(<\/textarea>)/Uis', '\1\2\3', $page);
	return $page ;
}

?>
