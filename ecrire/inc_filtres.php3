<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_FILTRES")) return;
define("_ECRIRE_INC_FILTRES", "1");

//
// divers
//

// pour les titres numerotes ("1. Titre" -> "Titre")
function supprimer_numero($texte) {
	$texte = ereg_replace("^[[:space:]]*[0-9]+[.)".chr(176)."][[:space:]]+", "", $texte);
	return $texte;
}

// suppression basique et brutale de tous les <...>
function supprimer_tags($texte, $rempl = "") {
	// super gavant : la regexp ci-dessous plante sous php3, genre boucle infinie !
	// $texte = ereg_replace("<([^>\"']*|\"[^\"]*\"|'[^']*')*>", $rempl, $texte);
	$texte = ereg_replace("<[^>]*>", $rempl, $texte);
	return $texte;
}

// texte au kilometre
function textebrut($texte) {
	$texte = ereg_replace("[\n\r]+", " ", $texte);
	$texte = eregi_replace("<(p|br)([[:space:]][^>]*)?".">", "\n\n", $texte);
	$texte = ereg_replace("^\n+", "", $texte);
	$texte = ereg_replace("\n+$", "", $texte);
	$texte = ereg_replace("\n +", "\n", $texte);
	$texte = supprimer_tags($texte);
	$texte = ereg_replace("(&nbsp;| )+", " ", $texte);
	return $texte;
}

// pour ceux qui aiment les liens qui ouvrent une nouvelle fenetre
function liens_ouvrants ($texte) {
	return ereg_replace("<a ([^>]*class=\"spip_out\")>",
		"<a \\1 target=\"_blank\">", $texte);
}

// corrige les caracteres degoutants
function corriger_caracteres($texte) {
	// 145,146,180 = simple quote ; 147,148 = double quote ; 150 = tiret long
	return strtr($texte, chr(145).chr(146).chr(180).chr(147).chr(148).chr(150), "'''".'""-');
}

// resserrer les paragraphes pour l'intro
function PtoBR($texte){
	$texte = eregi_replace("</p>", "\n", $texte);
	$texte = eregi_replace("<p([[:space:]][^>]*)?".">", "<br>", $texte);
	return $texte;
}

// majuscules y compris accents
function majuscules($texte) {
	$suite = htmlentities($texte);
	$suite = ereg_replace('&amp;', '&', $suite);
	$suite = ereg_replace('&lt;', '<', $suite); 
	$suite = ereg_replace('&gt;', '>', $suite); 
	$texte = '';
	if (ereg('^(.*)&([A-Za-z])([a-zA-Z]*);(.*)$', $suite, $regs)) {
		$texte .= majuscules($regs[1]);
		$suite = $regs[4];
		$carspe = $regs[2];
		$accent = $regs[3];
		if (ereg('^(acute|grave|circ|uml|cedil|slash|caron|ring|tilde|elig)$', $accent))
			$carspe = strtoupper($carspe); 
		if ($accent == 'elig') $accent = 'Elig';
		$texte .= '&'.$carspe.$accent.';';
	}
	$texte .= strtoupper($suite);
	return $texte;
}

// "127.4 ko" ou "3.1 Mo"
function taille_en_octets ($taille) {
	if ($taille < 1024) {$taille .= "&nbsp;octets";}
	else if ($taille < 1024*1024) {
		$taille = ((floor($taille / 102.4))/10)."&nbsp;ko";
	} else {
		$taille = ((floor(($taille / 1024) / 102.4))/10)."&nbsp;Mo";
	}
	return $taille;
}


//
// date, heure, saisons
//

function vider_date($letexte) {
	if (ereg("^0000-00-00", $letexte)) return '';
	return $letexte;
}

function recup_heure($numdate){
	if (!$numdate) return '';

	if (ereg('([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})', $numdate, $regs)) {
		$heures = $regs[1];
		$minutes = $regs[2];
		$secondes = $regs[3];
	}
	return array($heures, $minutes, $secondes);
}

function heures($numdate) {
	$date_array = recup_heure($numdate);
	if ($date_array)
		list($heures, $minutes, $secondes) = $date_array;
	return $heures;
}

function minutes($numdate) {
	$date_array = recup_heure($numdate);
	if ($date_array)
		list($heures, $minutes, $secondes) = $date_array;
	return $minutes;
}

function secondes($numdate) {
	$date_array = recup_heure($numdate);
	if ($date_array)
		list($heures,$minutes,$secondes) = $date_array;
	return $secondes;
}

function recup_date($numdate){
	if (!$numdate) return '';
	if (ereg('([0-9]{1,2})/([0-9]{1,2})/([0-9]{1,2})', $numdate, $regs)) {
		$jour = $regs[1];
		$mois = $regs[2];
		$annee = $regs[3];
		if ($annee < 90){
			$annee = 2000 + $annee;
		} else {
			$annee = 1900 + $annee ;
		}
	}
	elseif (ereg('([0-9]{4})-([0-9]{2})-([0-9]{2})',$numdate, $regs)) {
		$annee = $regs[1];
		$mois = $regs[2];
		$jour = $regs[3];
	}
	elseif (ereg('([0-9]{4})-([0-9]{2})', $numdate, $regs)){
		$annee = $regs[1];
		$mois = $regs[2];
	}
	if ($annee > 4000) $annee -= 9000;
	if (substr($jour, 0, 1) == '0') $jour = substr($jour, 1);

	return array($annee, $mois, $jour);
}


function affdate_base($numdate, $vue) {
	global $lang;
	$date_array = recup_date($numdate);
	if ($date_array)
		list($annee, $mois, $jour) = $date_array;
	else
		return '';

	if ($mois > 0){
		$saison = "hiver";
		if (($mois == 3 AND $jour >= 21) OR $mois > 3) $saison = "printemps";
		if (($mois == 6 AND $jour >= 21) OR $mois > 6) $saison = chr(233)."t".chr(233);
		if (($mois == 9 AND $jour >= 21) OR $mois > 9) $saison = "automne";
		if (($mois == 12 AND $jour >= 21) OR $mois > 12) $saison = "hiver";
	}
	
	if ($lang == "fr") {
		if ($jour == '1') $jour = '1er';
		$tab_mois = array('',
			'janvier', 'f'.chr(233).'vrier', 'mars', 'avril', 'mai', 'juin',
			'juillet', 'ao'.chr(251).'t', 'septembre', 'octobre', 'novembre', 'd'.chr(233).'cembre');
		$avjc = ' av. J.C.';
	}
	elseif ($lang == "en"){
		switch($jour) {
		case '1':
			$jour = '1st';
			break;
		case '2':
			$jour = '2nd';
			break;
		case '3':
			$jour = '3rd';
			break;
		case '21':
			$jour = '21st';
			break;
		case '22':
			$jour = '22nd';
			break;
		case '23':
			$jour = '23rd';
			break;
		case '31':
			$jour = '31st';
			break;
		}
		$tab_mois = array('',
			'January', 'February', 'March', 'April', 'May', 'June',
			'July', 'August', 'September', 'October', 'November', 'December');
		$avjc = ' B.C.';
	}
	if ($jour == 0) $jour = "";
	if ($jour) $jour .= ' ';
	$mois = $tab_mois[(int) $mois];
	if ($annee < 0) {
		$annee = -$annee.$avjc;
		$avjc = true;
	}
	else $avjc = false;

	switch ($vue) {
	case 'saison':
		return $saison;

	case 'court':
		if ($avjc) return $annee;
		$a = date('Y');
		if ($annee < ($a - 100) OR $annee > ($a + 100)) return $annee;
		if ($annee != $a) return ucfirst($mois)." $annee";
		return $jour.$mois;

	case 'entier':
		if ($avjc) return $annee;
		return "$jour$mois $annee";

	case 'mois':
		return "$mois";

	case 'mois_annee':
		if ($avjc) return $annee;
		return "$mois $annee";
	}

	return '<blink>format non d&eacute;fini</blink>';
}

function nom_jour($numdate) {
	global $lang;
	$date_array = recup_date($numdate);
	if ($date_array)
		list($annee,$mois,$jour) = $date_array;
	else
		return '';

	if (!$mois OR !$jour) return;
	
	$nom = mktime(1,1,1,$mois,$jour,$annee);
	$nom = date("D",$nom);

	if ($lang == "fr") {
		switch($nom) {
			case 'Sun': $nom='dimanche'; break;
			case 'Mon': $nom='lundi'; break;
			case 'Tue': $nom='mardi'; break;
			case 'Wed': $nom='mercredi'; break;
			case 'Thu': $nom='jeudi'; break;
			case 'Fri': $nom='vendredi'; break;
			case 'Sat': $nom='samedi'; break;
		}
	}
	elseif ($lang == "en") {
		switch($nom) {
			case 'Sun': $nom='Sunday'; break;
			case 'Mon': $nom='Monday'; break;
			case 'Tue': $nom='Tuesday'; break;
			case 'Wed': $nom='Wednesday'; break;
			case 'Thu': $nom='Thursday'; break;
			case 'Fri': $nom='Friday'; break;
			case 'Sat': $nom='Saturday'; break;
		}
	}
	return $nom;
}

function jour($numdate) {
	$date_array = recup_date($numdate);
	if ($date_array)
		list($annee,$mois,$jour) = $date_array;
	else
		return '';
	if ($jour=="1") $jour="1er";
	return $jour;
}

function mois($numdate) {
	$date_array = recup_date($numdate);
	if ($date_array)
		list($annee,$mois,$jour) = $date_array;
	else
		return '';
	return $mois;
}

function annee($numdate) {
	$date_array = recup_date($numdate);
	if ($date_array)
		list($annee,$mois,$jour) = $date_array;
	else
		return '';
	return $annee;
}

function saison($numdate) {
	return affdate_base($numdate, 'saison');
}

function affdate($numdate) {
	return affdate_base($numdate, 'entier');
}

function affdate_court($numdate) {
	return affdate_base($numdate, 'court');
}

function affdate_mois_annee($numdate) {
	return affdate_base($numdate, 'mois_annee');
}

function nom_mois($numdate) {
	return affdate_base($numdate, 'mois');
}

//
// alignements
//

function aligner($letexte,$justif) {
	$letexte = eregi_replace("^<p([[:space:]][^>]*)?".">", "", trim($letexte));
	if ($letexte) {
		$letexte = eregi_replace("<p([[:space:]][^>]*)?".">", "<p\\1 align='$justif'>", $letexte);
		return "<p class='spip' align='$justif'>".$letexte;
	}
}

function justifier($letexte) {
	return aligner($letexte,'justify');
}

function aligner_droite($letexte) {
	return aligner($letexte,'right');
}

function aligner_gauche($letexte) {
	return aligner($letexte,'left');
}

function centrer($letexte) {
	return aligner($letexte,'center');
}

// utilise avec ob_start() et ob_get_contents() pour
// mettre en rouge les mots passes dans $var_s
function surligner_mots($page, $mots) {
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
		if (strlen($mot) > 3) {
			$mot = strtr($mot, $accents, "AAAAAAaaaaaaOOOOOOooooooEEEEeeeeCcIIIIiiiiUUUUuuuuyNn");
			$mot = strtr(strtolower($mot), $accents_regexp);
			$mots_surligne[] = $mot;
		}
	}

	// Remplacer une occurence de mot maxi par espace inter-tag (donc au max par paragraphe)
	if ($mots_surligne) {
		$regexp = '(>[^<]*[^[:alnum:]<])(('.join('|', $mots_surligne).')[[:alnum:]]*)';
		$page = eregi_replace($regexp, '\1<span class="spip_surligne">\2</span>', $page);
	}

	return $page;
}

?>
