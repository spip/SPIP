<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_FILTRES")) return;
define("_ECRIRE_INC_FILTRES", "1");

//
// Divers
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
		'&lt;' => '<',
		'&gt;' => '>'
	);

	$texte = strtr2 ($texte, $trans);

	if (lire_meta('charset') == 'iso-8859-1')	// recuperer les caracteres iso-latin
		$texte = strtr2 ($texte, $trans_iso);

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

//
// Conversions de charset
//

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

		default:
			break;
	}
	return $chaine;
}


// Il faut deux fonctions par charset : charset->unicode et unicode->charset

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
		else if($num<1024) $s = chr(($num>>6)+192).chr(($num&63)+128);
		else if($num<32768) $s = chr(($num>>12)+224).chr((($num>>6)&63)+128).chr(($num&63)+128);
		else if($num<2097152) $s = chr($num>>18+240).chr((($num>>12)&63)+128).chr(($num>>6)&63+128). chr($num&63+128);
		else $s = '';
		$chaine = ereg_replace($regs[0], $s, $chaine);
	}
	return $chaine;
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
	return ereg_replace("<a ([^>]*http://[^>]*class=\"spip_(out|url)\")>",
		"<a \\1 target=\"_blank\">", $texte);
}

// Corrige les caracteres degoutants utilises par les Windozeries
function corriger_caracteres($texte) {
	if (lire_meta('charset') != 'iso-8859-1')
		return $texte;
	// 145,146,180 = simple quote ; 147,148 = double quote ; 150,151 = tiret long
	return strtr($texte, chr(145).chr(146).chr(180).chr(147).chr(148).chr(150).chr(151), "'''".'""--');
}

// Transformer les sauts de paragraphe en simples passages a la ligne
function PtoBR($texte){
	$texte = eregi_replace("</p>", "\n", $texte);
	$texte = eregi_replace("<p([[:space:]][^>]*)?".">", "<br>", $texte);
	$texte = ereg_replace("^[[:space:]]*<br>", "", $texte);
	return $texte;
}

// Majuscules y compris accents, en HTML
function majuscules($texte) {
	if (lire_meta('charset') != 'iso-8859-1')
		return $texte;
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

// Vider les url nulles comme 'http://' ou 'mailto:'
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

// Maquiller une adresse e-mail
function antispam($texte) {
	include_ecrire ("inc_acces.php3");
	$masque = creer_pass_aleatoire(3);
	return ereg_replace("@", " $masque ", $texte);
}


//
// Date, heure, saisons
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
		if (($mois == 6 AND $jour >= 21) OR $mois > 6) $saison = unicode2charset("&#233;t&#233;");
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
// Alignements en HTML
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
