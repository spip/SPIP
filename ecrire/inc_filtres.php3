<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_FILTRES")) return;
define("_ECRIRE_INC_FILTRES", "1");

//
// divers
//


// Echappement des entites HTML avec correction des entites "brutes"
// (generees par les butineurs lorsqu'on rentre des caracteres n'appartenant
// pas au charset de la page [iso-8859-1 par defaut])
function corriger_entites_html($texte) {
	return ereg_replace('&amp;(#[0-9]+;)', '&\1', $texte);
}

function entites_html($texte) {
	return corriger_entites_html(htmlspecialchars($texte));
}


// Enleve le numero des titres numerotes ("1. Titre" -> "Titre")
function supprimer_numero($texte) {
	$texte = ereg_replace("^[[:space:]]*[0-9]+[.)".chr(176)."][[:space:]]+", "", $texte);
	return $texte;
}

// Suppression basique et brutale de tous les <...>
function supprimer_tags($texte, $rempl = "") {
	// super gavant : la regexp ci-dessous plante sous php3, genre boucle infinie !
	// $texte = ereg_replace("<([^>\"']*|\"[^\"]*\"|'[^']*')*>", $rempl, $texte);
	$texte = ereg_replace("<[^>]*>", $rempl, $texte);
	return $texte;
}

// Convertit les <...> en la version lisible en HTML
function echapper_tags($texte, $rempl = "") {
	$texte = ereg_replace("<([^>]*)>", "&lt;\\1&gt;", $texte);
	return $texte;
}

// Convertit un texte HTML en texte brut
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

// Remplace les liens SPIP en liens ouvrant dans une nouvelle fenetre (target=blank)
function liens_ouvrants ($texte) {
	return ereg_replace("<a ([^>]*class=\"spip_(out|url)\")>",
		"<a \\1 target=\"_blank\">", $texte);
}

// Corrige les caracteres degoutants utilises par les Windozeries
function corriger_caracteres($texte) {
	// 145,146,180 = simple quote ; 147,148 = double quote ; 150,151 = tiret long
	return strtr($texte, chr(145).chr(146).chr(180).chr(147).chr(148).chr(150).chr(151), "'''".'""--');
}

// Transformer les sauts de paragraphe en simples passages a la ligne
function PtoBR($texte){
	$texte = eregi_replace("</p>", "\n", $texte);
	$texte = eregi_replace("<p([[:space:]][^>]*)?".">", "<br>", $texte);
	return $texte;
}

// Majuscules y compris accents, en HTML
function majuscules($texte) {
	$suite = htmlentities($texte);
	$suite = ereg_replace('&amp;', '&', $suite);
	$suite = ereg_replace('&lt;', '<', $suite); 
	$suite = ereg_replace('&gt;', '>', $suite); 
	$texte = '';
	if (ereg('^(.*)&([A-Za-z])([a-zA-Z]*);(.*)$', $suite, $regs)) {
		$texte .= majuscules($regs[1]); // quelle horrible recursion
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


// Transforme n'importe quel champ en une chaine utilisable
// en PHP ou Javascript en toute securite
// < ? php $x = '[(#TEXTE|texte_script)]'; ? >
function texte_script($texte) {
	$texte = str_replace('\\', '\\\\', $texte);
	$texte = str_replace('\'', '\\\'', $texte);
	return $texte;
}


// Rend une chaine utilisable sans dommage comme attribut HTML
function attribut_html($texte) {
	$texte = ereg_replace('"', '&quot;', supprimer_tags($texte));
	return $texte;
}

// vider les url nulles comme 'http://' ou 'mailto:'
function vider_url($url) {
	if (eregi("^(http:?/?/?|mailto:?)$", trim($url)))
		return false;
	else
		return $url;
}

// Extraire une date de n'importe quel champ (a completer...)
function extraire_date($texte) {
	// format = 2001-08
	if (ereg("([1-2][0-9]{3})[^0-9]*(0?[1-9]|1[0-2])",$texte,$regs))
		return $regs[1]."-".$regs[2]."01";
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
		if (($mois == 6 AND $jour >= 21) OR $mois > 6) $saison = "\xe9t\xe9";
		if (($mois == 9 AND $jour >= 21) OR $mois > 9) $saison = "automne";
		if (($mois == 12 AND $jour >= 21) OR $mois > 12) $saison = "hiver";
	}
	
	if ($lang == "fr") {
		if ($jour == '1') $jour = '1er';
		$tab_mois = array('',
			'janvier', "f&eacute;vrier", 'mars', 'avril', 'mai', 'juin',
			'juillet', "ao&ucirc;t", 'septembre', 'octobre', 'novembre', "d&eacute;cembre");
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
	global $lang;
	$date_array = recup_date($numdate);
	if ($date_array)
		list($annee,$mois,$jour) = $date_array;
	if ($jour=="1") switch($lang) {
		case 'en':
			$jour = "1st";
			break;
		
		case 'fr':
		default:
			$jour = "1er";
	}
	return $jour;
}

// la meme... mais avec '1' au lieu de '1er'
function journum($numdate) {
	$date_array = recup_date($numdate);
	if ($date_array)
		list($annee,$mois,$jour) = $date_array;
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

function affdate($numdate, $corriger_entites = false) {
	$date = affdate_base($numdate, 'entier');
	if ($corriger_entites) {	// special pour le mail...
		$date = ereg_replace("&ucirc;", "\xfb", $date);
		$date = ereg_replace("&eacute;", "\xe9", $date);
	}
	return $date;
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

?>
