<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_SURLIGNE")) return;
define("_ECRIRE_INC_SURLIGNE", "1");

// utilise avec ob_start() et ob_get_contents() pour
// mettre en rouge les mots passes dans $var_recherche
function surligner_mots($page, $mots) {
	global $nombre_surligne;
	include_ecrire("inc_texte.php3"); // pour le reglage de $nombre_surligne

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

	// Remplacer les caracteres potentiellement accentues dans la chaine
	// de recherche par les choix correspondants en syntaxe regexp (!)
	$mots = split("[[:space:]]+", $mots);
	while (list(, $mot) = each ($mots)) {
		if (strlen($mot) >= 2) {
			$mot = strtr($mot, $accents, "AAAAAAaaaaaaOOOOOOooooooEEEEeeeeCcIIIIiiiiUUUUuuuuyNn");
			$mot = strtr(strtolower($mot), $accents_regexp);
			$mots_surligne[] = $mot;
		}
	}

	// ne pas traiter tout ce qui est avant </head> ou <body>
	$regexp = '/<\/head>|<body[^>]*>/i';
	if (preg_match($regexp, $page, $exp)) {
		$debut = substr($page, 0, strpos($page, $exp[0])+strlen($exp[0]));
		$page = substr($page, strlen($debut));
	} else
		 $debut = '';

	// Remplacer une occurence de mot maxi par espace inter-tag (max 1 par paragraphe, sauf italiques etc.)
	// se limiter a 4 remplacements pour ne pas bouffer le CPU
	if ($mots_surligne) {
		$regexp = '/((^|>)([^<]*[^[:alnum:]_<])?)(('.join('|', $mots_surligne).')[[:alnum:]_]*?)/Uis';
		$page = preg_replace($regexp, '\1<span class="spip_surligne">\4</span>', $page, $nombre_surligne);
	}

	return $debut.$page;
}


// debut et fin, appeles depuis les squelettes
function debut_surligne($mots, $mode_surligne) {

	switch ($mode_surligne) {
		case 'auto' :	// on arrive du debut de la page, on ne touche pas au buffer
			ob_end_flush();
			ob_start("");
			$mode_surligne = 'actif';
			break;

		case 'actif' :	// il y a un buffer a traiter
			$la_page = surligner_mots(ob_get_contents(), $mots);
			ob_end_clean();
			echo $la_page;
			ob_start("");
			$mode_surligne = 'actif';
			break;

		case 'inactif' :	// il n'y a pas de buffer
			ob_start("");
			$mode_surligne = 'actif';
			break;

		case false :	// conditions pas reunies (flag_preserver, etc.)
			break;
	}

	return $mode_surligne;
}

function fin_surligne($mots, $mode_surligne) {

	switch ($mode_surligne) {
		case 'auto' :	// on arrive du debut de la page, on s'occupe du buffer
			$la_page = surligner_mots(ob_get_contents(), $mots);
			ob_end_clean();
			echo $la_page;
			ob_start("");
			$mode_surligne = 'inactif';
			break;

		case 'actif' :	// il y a un buffer a traiter
			$la_page = surligner_mots(ob_get_contents(), $mots);
			ob_end_clean();
			echo $la_page;
			$mode_surligne = 'inactif';
			break;

		case 'inactif' :	// il n'y a pas de buffer
			break;

		case false :	// conditions pas reunies (flag_preserver, etc.)
			break;
	}

	return $mode_surligne;
}

?>
