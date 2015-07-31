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
if (defined("_ECRIRE_INC_FILTRES")) return;
define("_ECRIRE_INC_FILTRES", "1");


// Echappement des entites HTML avec correction des entites "brutes"
// (generees par les butineurs lorsqu'on rentre des caracteres n'appartenant
// pas au charset de la page [iso-8859-1 par defaut])
function corriger_entites_html($texte) {
	return preg_replace(',&amp;(#[0-9]+;),i', '&\1', $texte);
}
// idem mais corriger aussi les &amp;eacute; en &eacute;
function corriger_toutes_entites_html($texte) {
	return preg_replace(',&amp;(#?[a-z0-9]+;),', '&\1', $texte);
}

function entites_html($texte) {
	return corriger_entites_html(htmlspecialchars($texte));
}

// Transformer les &eacute; dans le charset local
function filtrer_entites($texte) {
	include_ecrire('inc_charsets.php3');
	// filtrer
	$texte = html2unicode($texte);
	// remettre le tout dans le charset cible
	return unicode2charset($texte);
}

// Tout mettre en entites pour l'export backend (sauf iso-8859-1)
function entites_unicode($texte) {
	include_ecrire('inc_charsets.php3');
	return charset2unicode($texte);
}

// caracteres de controle - http://www.w3.org/TR/REC-xml/#charsets
function supprimer_caracteres_illegaux($texte) {
	$from = "\x0\x1\x2\x3\x4\x5\x6\x7\x8\xB\xC\xE\xF\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F";
	$to = str_repeat('-', strlen($from));
	return strtr($texte, $from, $to);
}

// Corrige les caracteres degoutants utilises par les Windozeries
function corriger_caracteres_windows($texte) {
	static $trans;
	if (!$trans) {
		// 145,146,180 = simple quote ; 147,148 = double quote ; 150,151 = tiret long
		$trans['iso-8859-1'] = array(
			chr(146) => "'",
			chr(180) => "'",
			chr(147) => '&#8220;',
			chr(148) => '&#8221;',
			chr(150) => '-',
			chr(151) => '-',
			chr(133) => '...'
		);
		$trans['utf-8'] = array(
			chr(194).chr(146) => "'",
			chr(194).chr(180) => "'",
			chr(194).chr(147) => '&#8220;',
			chr(194).chr(148) => '&#8221;',
			chr(194).chr(150) => '-',
			chr(194).chr(151) => '-',
			chr(194).chr(133) => '...'
		);
	}
	$charset = lire_meta('charset');
	if (!$trans[$charset]) return $texte;
	return strtr($texte, $trans[$charset]);
}

// Supprimer caracteres windows et les caracteres de controle ILLEGAUX
function corriger_caracteres ($texte) {
	$texte = corriger_caracteres_windows($texte);
	$texte = supprimer_caracteres_illegaux($texte);
	return $texte;
}


// Nettoyer les backend
function texte_backend($texte) {

	// importer les &eacute;
	$texte = filtrer_entites($texte);

	// " -> &quot; et tout ce genre de choses
	// contourner bug windows ou char(160) fait partie de la regexp \s
	$u = (lire_meta('charset')=='utf-8') ? 'u':'';
	$texte = str_replace("&nbsp;", " ", $texte);
	$texte = preg_replace("/\s\s+/$u", " ", $texte);
	$texte = entites_html($texte);

	// verifier le charset
	$texte = entites_unicode($texte);

	// Caracteres problematiques en iso-latin 1
	if (lire_meta('charset') == 'iso-8859-1') {
		$texte = str_replace(chr(156), '&#156;', $texte);
		$texte = str_replace(chr(140), '&#140;', $texte);
		$texte = str_replace(chr(159), '&#159;', $texte);
	}

	// nettoyer l'apostrophe curly qui semble poser probleme a certains rss-readers
	$texte = str_replace("&#8217;","'",$texte);

	return $texte;
}

// Enleve le numero des titres numerotes ("1. Titre" -> "Titre")
function supprimer_numero($texte) {
	$texte = preg_replace(",^[[:space:]]*[0-9]+[.)".chr(176)."][[:space:]]+,", "", $texte);
	return $texte;
}

// Suppression basique et brutale de tous les <...>
function supprimer_tags($texte, $rempl = "") {
	$texte = preg_replace(",<[^>]*>,U", $rempl, $texte);
	// ne pas oublier un < final non ferme
	$texte = str_replace('<', ' ', $texte);
	return $texte;
}

// Convertit les <...> en la version lisible en HTML
function echapper_tags($texte, $rempl = "") {
	$texte = ereg_replace("<([^>]*)>", "&lt;\\1&gt;", $texte);
	return $texte;
}

// Convertit un texte HTML en texte brut
function textebrut($texte) {
	$u = (lire_meta('charset')=='utf-8') ? 'u':'';
	$texte = preg_replace("/\s+/$u", " ", $texte);
	$texte = preg_replace("/<(p|br)( [^>]*)?".">/i", "\n\n", $texte);
	$texte = preg_replace("/^\n+/", "", $texte);
	$texte = preg_replace("/\n+$/", "", $texte);
	$texte = preg_replace("/\n +/", "\n", $texte);
	$texte = supprimer_tags($texte);
	$texte = preg_replace("/(&nbsp;| )+/", " ", $texte);
	// nettoyer l'apostrophe curly qui pose probleme a certains rss-readers, lecteurs de mail...
	$texte = str_replace("&#8217;","'",$texte);
	return $texte;
}

// Remplace les liens SPIP en liens ouvrant dans une nouvelle fenetre (target=blank)
function liens_ouvrants ($texte) {
	return ereg_replace("<a ([^>]*https?://[^>]*class=\"spip_(out|url)\")>",
		"<a \\1 target=\"_blank\">", $texte);
}

// Fabrique une balise A, avec un href conforme au validateur W3C
// attention au cas ou la href est du Javascript avec des "'"

function http_href($href, $clic, $title='', $style='', $class='', $evt='') {
	return '<a href="' .
		str_replace('&', '&amp;', $href) .
		'"' .
		(!$title ? '' : ("\ntitle=\"" . supprimer_tags($title)."\"")) .
		(!$style ? '' : ("\nstyle=\"" . $style . "\"")) .
		(!$class ? '' : ("\nclass=\"" . $class . "\"")) .
		($evt ? "\n$evt" : '') .
		'>' .
		$clic .
		'</a>';
}

// produit une balise img avec un champ alt d'office si vide
// attention le htmlentities et la traduction doivent etre appliques avant.

function http_img_pack($img, $alt, $att, $title='') {
	return "<img src='" . _DIR_IMG_PACK . $img
	  . ("'\nalt=\"" .
	     ($alt ? $alt : ($title ? $title : ereg_replace('\..*$','',$img)))
	     . '" ')
	  . ($title ? " title=\"$title\"" : '')
	  . $att . " />";
}

// variante avec un label et un checkbox

function http_label_img($statut, $etat, $var, $img, $texte) {
  return "<label for='$statut'>". 
    "<input type='checkbox' " .
    (($etat !== false) ? ' checked="checked"' : '') .
    " name='$var" .
    "[]' value='$statut' id='$statut'>&nbsp;" .
    http_img_pack($img, $texte, "width='8' height='9' border='0'", $texte) .
    " " .
    $texte .
    "</label><br />";
}

function http_href_img($href, $img, $att, $title='', $style='', $class='', $evt='') {
	return  http_href($href, http_img_pack($img, $title, $att), $title, $style, $class, $evt);
}


// Transformer les sauts de paragraphe en simples passages a la ligne
function PtoBR($texte){
	$texte = eregi_replace("</p>", "\n", $texte);
	$texte = eregi_replace("<p([[:space:]][^>]*)?".">", "<br />", $texte);
	$texte = ereg_replace("^[[:space:]]*<br />", "", $texte);
	return $texte;
}

// Couper les "mots" de plus de $l caracteres (souvent des URLs)
function lignes_longues($texte, $l = 70) {
	// Passer en utf-8 pour ne pas avoir de coupes trop courtes avec les &#xxxx;
	// qui prennent 7 caracteres
	include_ecrire('inc_charsets.php3');
	$texte = unicode_to_utf_8(charset2unicode(
		$texte, lire_meta('charset'), true));

	// echapper les tags (on ne veut pas casser les a href=...)
	$tags = array();
	if (preg_match_all('/<.*>/Uums', $texte, $t, PREG_SET_ORDER)) {
		foreach ($t as $n => $tag) {
			$tags[$n] = $tag[0];
			$texte = str_replace($tag[0], " @@SPIPTAG$n@@ ", $texte);
		}
	}
	// casser les mots longs qui restent
	if (preg_match_all("/\S{".$l."}/Ums", $texte, $longs, PREG_SET_ORDER)) {
		foreach ($longs as $long) {
			$texte = str_replace($long[0], $long[0].' ', $texte);
		}
	}

	// retablir les tags
	foreach ($tags as $n=>$tag) {
		$texte = str_replace(" @@SPIPTAG$n@@ ", $tag, $texte);
	}

	return importer_charset($texte, 'utf-8');
}

// Majuscules y compris accents, en HTML
function majuscules($texte) {
	// Cas du turc
	if ($GLOBALS['spip_lang'] == 'tr') {
		# remplacer hors des tags et des entites
		if (preg_match_all(',<[^<>]+>|&[^;]+;,', $texte, $regs, PREG_SET_ORDER))
			foreach ($regs as $n => $match)
				$texte = str_replace($match[0], "@@SPIP_TURC$n@@", $texte);

		$texte = str_replace('i', '&#304;', $texte);

		if ($regs)
			foreach ($regs as $n => $match)
				$texte = str_replace("@@SPIP_TURC$n@@", $match[0], $texte);
	}

	// Cas general
	if (strlen($texte))
		return "<span style='text-transform: uppercase'>$texte</span>";
}

// "127.4 ko" ou "3.1 Mo"
function taille_en_octets ($taille) {
	if ($taille < 1024) {$taille = _T('taille_octets', array('taille' => $taille));}
	else if ($taille < 1024*1024) {
		$taille = _T('taille_ko', array('taille' => ((floor($taille / 102.4))/10)));
	} else {
		$taille = _T('taille_mo', array('taille' => ((floor(($taille / 1024) / 102.4))/10)));
	}
	return $taille;
}


// Rend une chaine utilisable sans dommage comme attribut HTML
function attribut_html($texte) {
	$texte = ereg_replace('"', '&quot;', supprimer_tags($texte));
	return $texte;
}

// Vider les url nulles comme 'http://' ou 'mailto:'
function vider_url($url) {
	# un message pour abs_url
	$GLOBALS['mode_abs_url'] = 'url';

	$url = trim($url);
	if (eregi("^(http:?/?/?|mailto:?)$", $url))
		return '';

	return $url;
}

function parametre_url($url, $parametre, $valeur = '__global__') {
	$link = new Link(str_replace('&amp;', '&', $url));
	if($valeur == '__global__')
		$valeur = $GLOBALS[$parametre];
	if(empty($valeur)) $link->DelVar($parametre);
	else $link->AddVar($parametre, $valeur);
	return quote_amp($link->getUrl());
}

//
// Ajouter le &var_recherche=toto dans les boucles de recherche
//
function url_var_recherche($url) {
	if (_request('recherche')
	AND !ereg("var_recherche", $url)) {

		list ($url,$ancre) = preg_split(',#,', $url, 2);
		if ($ancre) $ancre='#'.$ancre;

		$x = "var_recherche=".urlencode(_request('recherche'));

		if (!strpos($url, '?'))
			return "$url?$x$ancre";
		else
			return "$url&$x$ancre";
	}
	else return $url;
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

// |sinon{rien} : affiche "rien" si la chaine est vide, affiche la chaine si non vide
function sinon ($texte, $sinon='') {
	if ($texte)
		return $texte;
	else
		return $sinon;
}

// |choixsivide{vide,pasvide} affiche pasvide si la chaine n'est pas vide...
function choixsivide($a, $vide, $pasvide) {
	return $a ? $pasvide : $vide;
}

// |choixsiegal{aquoi,oui,non} affiche oui si la chaine est egal a aquoi ...
function choixsiegal($a1,$a2,$v,$f) {
	return ($a1 == $a2) ? $v : $f;
}


//
// Date, heure, saisons
//

function normaliser_date($date) {
	if ($date) {
		$date = vider_date($date);
		if (ereg("^[0-9]{8,10}$", $date))
			$date = date("Y-m-d H:i:s", $date);
		if (ereg("^([12][0-9]{3})([-/]00)?( [-0-9:]+)?$", $date, $regs))
			$date = $regs[1]."-01-01".$regs[3];
		else if (ereg("^([12][0-9]{3}[-/][01]?[0-9])([-/]00)?( [-0-9:]+)?$", $date, $regs))
			$date = ereg_replace("/","-",$regs[1])."-01".$regs[3];
		else
			$date = date("Y-m-d H:i:s", strtotime($date));
	}
	return $date;
}

function vider_date($letexte) {
	if (ereg("^0000-00-00", $letexte)) return;
	if (ereg("^1970-01-01", $date)) return;	// eviter le bug GMT-1
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

function heures_minutes($numdate) {
	return _T('date_fmt_heures_minutes', array('h'=> heures($numdate), 'm'=> minutes($numdate)));
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
	elseif (ereg('([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})',$numdate, $regs)) {
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


function date_relative($date) {
	
	if (!$date) return;
	$decal = date("U") - date("U", strtotime($date));
	
	if ($decal < 0) {
		$il_y_a = "date_dans";
		$decal = -1 * $decal;
	} else {
		$il_y_a = "date_il_y_a";
	}
	
	if ($decal < 3600) {
		$minutes = ceil($decal / 60);
		$retour = _T($il_y_a, array("delai"=>"$minutes "._T("date_minutes"))); 
	}
	else if ($decal < (3600 * 24) ) {
		$heures = ceil ($decal / 3600);
		$retour = _T($il_y_a, array("delai"=>"$heures "._T("date_heures"))); 
	}
	else if ($decal < (3600 * 24 * 7)) {
		$jours = ceil ($decal / (3600 * 24));
		$retour = _T($il_y_a, array("delai"=>"$jours "._T("date_jours"))); 
	}
	else if ($decal < (3600 * 24 * 7 * 4)) {
		$semaines = ceil ($decal / (3600 * 24 * 7));
		$retour = _T($il_y_a, array("delai"=>"$semaines "._T("date_semaines"))); 
	}
	else if ($decal < (3600 * 24 * 30 * 6)) {
		$mois = ceil ($decal / (3600 * 24 * 30));
		$retour = _T($il_y_a, array("delai"=>"$mois "._T("date_mois"))); 
	}
	else {
		$retour = affdate_court($date);
	}



	return $retour;
}


function affdate_base($numdate, $vue) { 
	global $spip_lang;
	$date_array = recup_date($numdate);
	if ($date_array)
		list($annee, $mois, $jour) = $date_array;
	else
		return '';

	// 1er, 21st, etc.
	$journum = $jour;

	if ($jour == 0)
		$jour = '';
	else if ($jourth = _T('date_jnum'.$jour))
			$jour = $jourth;

	$mois = intval($mois);
	if ($mois > 0 AND $mois < 13) {
		$nommois = _T('date_mois_'.$mois);
		if ($jour)
			$jourmois = _T('date_de_mois_'.$mois, array('j'=>$jour, 'nommois'=>$nommois));
	}

	if ($annee < 0) {
		$annee = -$annee." "._T('date_avant_jc');
		$avjc = true;
	}
	else $avjc = false;

	switch ($vue) {
	case 'saison':
		if ($mois > 0){
			$saison = 1;
			if (($mois == 3 AND $jour >= 21) OR $mois > 3) $saison = 2;
			if (($mois == 6 AND $jour >= 21) OR $mois > 6) $saison = 3;
			if (($mois == 9 AND $jour >= 21) OR $mois > 9) $saison = 4;
			if (($mois == 12 AND $jour >= 21) OR $mois > 12) $saison = 1;
		}
		return _T('date_saison_'.$saison);

	case 'court':
		if ($avjc) return $annee;
		$a = date('Y');
		if ($annee < ($a - 100) OR $annee > ($a + 100)) return $annee;
		if ($annee != $a) return _T('date_fmt_mois_annee', array ('mois'=>$mois, 'nommois'=>ucfirst($nommois), 'annee'=>$annee));
		return _T('date_fmt_jour_mois', array ('jourmois'=>$jourmois, 'jour'=>$jour, 'mois'=>$mois, 'nommois'=>$nommois, 'annee'=>$annee));

	case 'jourcourt':
		if ($avjc) return $annee;
		$a = date('Y');
		if ($annee < ($a - 100) OR $annee > ($a + 100)) return $annee;
		if ($annee != $a) return _T('date_fmt_jour_mois_annee', array ('jourmois'=>$jourmois, 'jour'=>$jour, 'mois'=>$mois, 'nommois'=>$nommois, 'annee'=>$annee));
		return _T('date_fmt_jour_mois', array ('jourmois'=>$jourmois, 'jour'=>$jour, 'mois'=>$mois, 'nommois'=>$nommois, 'annee'=>$annee));

	case 'entier':
		if ($avjc) return $annee;
		if ($jour)
			return _T('date_fmt_jour_mois_annee', array ('jourmois'=>$jourmois, 'jour'=>$jour, 'mois'=>$mois, 'nommois'=>$nommois, 'annee'=>$annee));
		else
			return trim(_T('date_fmt_mois_annee', array ('mois'=>$mois, 'nommois'=>$nommois, 'annee'=>$annee)));

	case 'nom_mois':
		return $nommois;

	case 'mois':
		return sprintf("%02s",$mois);

	case 'jour':
		return $jour;

	case 'journum':
		return $journum;

	case 'nom_jour':
		if (!$mois OR !$jour) return '';
		$nom = mktime(1,1,1,$mois,$jour,$annee);
		$nom = 1+date('w',$nom);
		return _T('date_jour_'.$nom);

	case 'mois_annee':
		if ($avjc) return $annee;
		return trim(_T('date_fmt_mois_annee', array('mois'=>$mois, 'nommois'=>$nommois, 'annee'=>$annee)));

	case 'annee':
		return $annee;

	// Cas d'une vue non definie : retomber sur le format
	// de date propose par http://www.php.net/date
	default:
		return date($vue, strtotime($numdate));
	}
}

function nom_jour($numdate) {
	return affdate_base($numdate, 'nom_jour');
}

function jour($numdate) {
	return affdate_base($numdate, 'jour');
}

function journum($numdate) {
	return affdate_base($numdate, 'journum');
}

function mois($numdate) {
	return affdate_base($numdate, 'mois');
}

function nom_mois($numdate) {
	return affdate_base($numdate, 'nom_mois');
}

function annee($numdate) {
	return affdate_base($numdate, 'annee');
}

function saison($numdate) {
	return affdate_base($numdate, 'saison');
}

function affdate($numdate, $format='entier') {
	return affdate_base($numdate, $format);
}

function affdate_court($numdate) {
	return affdate_base($numdate, 'court');
}

function affdate_jourcourt($numdate) {
	return affdate_base($numdate, 'jourcourt');
}

function affdate_mois_annee($numdate) {
	return affdate_base($numdate, 'mois_annee');
}

function affdate_heure($numdate) {
	return _T('date_fmt_jour_heure', array('jour' => affdate($numdate), 'heure' => heures_minutes($numdate)));
}


//
// Alignements en HTML
//

function aligner($letexte,$justif) {
	$letexte = eregi_replace("<p([^>]*)", "<p\\1 align='$justif'", trim($letexte));
	if ($letexte AND !ereg("^[[:space:]]*<p", $letexte)) {
		$letexte = "<p class='spip' align='$justif'>" . $letexte . "</p>";
	}
	return $letexte;
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

function style_align($bof) {
	global $spip_lang_left;
	return "text-align: $spip_lang_left";
}

//
// Export iCal
//

function filtrer_ical($texte) {
	include_ecrire('inc_charsets.php3');
	$texte = html2unicode($texte);
	$texte = unicode2charset(charset2unicode($texte, lire_meta('charset'), 1), 'utf-8');
	$texte = ereg_replace("\n", " ", $texte);
	$texte = ereg_replace(",", "\,", $texte);

	return $texte;
}

function date_ical($date, $addminutes = 0) {
	list($heures, $minutes, $secondes) = recup_heure($date);
	list($annee, $mois, $jour) = recup_date($date);
	return date("Ymd\THis", 
		    mktime($heures, $minutes+$addminutes,$secondes,$mois,$jour,$annee));
}

function date_iso($date_heure) {
	list($annee, $mois, $jour) = recup_date($date_heure);
	list($heures, $minutes, $secondes) = recup_heure($date_heure);
	$time = mktime($heures, $minutes, $secondes, $mois, $jour, $annee);
	return gmdate("Y-m-d\TH:i:s\Z", $time);
}


function date_anneemoisjour($d)  {
	if (!$d) $d = date("Y-m-d");
	return  substr($d, 0, 4) . substr($d, 5, 2) .substr($d, 8, 2);
}

function date_anneemois($d)  {
	if (!$d) $d = date("Y-m-d");
	return  substr($d, 0, 4) . substr($d, 5, 2);
}

function date_debut_semaine($annee, $mois, $jour) {
  $w_day = date("w", mktime(0,0,0,$mois, $jour, $annee));
  if ($w_day == 0) $w_day = 7; // Gaffe: le dimanche est zero
  $debut = $jour-$w_day;
  return date("Ymd", mktime(0,0,0,$mois,$debut,$annee));
}

function date_fin_semaine($annee, $mois, $jour) {
  $w_day = date("w", mktime(0,0,0,$mois, $jour, $annee));
  if ($w_day == 0) $w_day = 7; // Gaffe: le dimanche est zero
  $debut = $jour-$w_day+1;
  return date("Ymd", mktime(0,0,0,$mois,$debut+6,$annee));
}

function agenda_connu($type)
{
  return in_array($type, array('jour','mois','semaine','periode')) ? ' ' : '';
}


// Cette fonction memorise dans un tableau indexe par son 5e arg
// un evenement decrit par les 4 autres (date, descriptif, titre, URL). 
// Appellee avec une date nulle, elle renvoie le tableau construit.
// l'indexation par le 5e arg autorise plusieurs calendriers dans une page

function agenda_memo($date=0 , $descriptif='', $titre='', $url='', $cal='')
{
  static $agenda = array();
  if (!$date) return $agenda;
  $idate = date_ical($date);
  $cal = trim($cal); // func_get_args (filtre alterner) rajoute \n !!!!
  $agenda[$cal][(date_anneemoisjour($date))][] =  array(
			'CATEGORIES' => $cal,
			'DTSTART' => $idate,
			'DTEND' => $idate,
                        'DESCRIPTION' => texte_script($descriptif),
                        'SUMMARY' => texte_script($titre),
                        'URL' => $url);
  // toujours retourner vide pour qu'il ne se passe rien
  return "";
}

// Cette fonction recoit un nombre d'evenements, un type de calendriers
// et une suite de noms N.
// Elle demande a la fonction la precedente son tableau
// et affiche selon le type les elements indexes par N dans ce tableau.
// Si le suite de noms est vide, tout le tableau est pris
// Ces noms N sont aussi des classes CSS utilisees par http_calendrier_init

function agenda_affiche($i)
{
  $args = func_get_args();
  $nb = array_shift($args); // nombre d'evenements (on pourrait l'afficher)
  $sinon = array_shift($args);
  if (!$nb) return $sinon;
  $type = array_shift($args);
  $agenda = agenda_memo(0);
  $evt = array();
  foreach (($args ? $args : array_keys($agenda)) as $k) {  
      if (is_array($agenda[$k]))
	foreach($agenda[$k] as $d => $v) { 
	  $evt[$d] = $evt[$d] ? (array_merge($evt[$d], $v)) : $v;
	}
    }
  if ($type != 'periode')
      $evt = array('', $evt);
  else
      {
	$d = array_keys($evt);
	$mindate = min($d);
	$min = substr($mindate,6,2);
	$max = $min + ((strtotime(max($d)) - strtotime($mindate)) / (3600 * 24));
	if ($max < 31) $max = 0;
	$evt = array('', $evt, $min, $max);
	$type = 'mois';
      }
    include('ecrire/inc_calendrier.php');
    return http_calendrier_init('', $type, '', '', '', $evt);
}

//
// Fonctions graphiques
//

// Accepte en entree un tag <img ...>
function reduire_une_image($img, $taille, $taille_y) {
	include_ecrire('inc_logos.php3');

	// Cas du mouseover genere par les logos de survol de #LOGO_ARTICLE
	if (eregi("onmouseover=\"this\.src=\'([^']+)\'\"", $img, $match)) {
		$mouseover = extraire_attribut(
			reduire_image_logo($match[1], $taille, $taille_y),
			'src');
	}

	$image = reduire_image_logo($img, $taille, $taille_y);

	if ($mouseover) {
		$mouseout = extraire_attribut($image, 'src');
		$js_mouseover = "onmouseover=\"this.src='$mouseover'\""
			." onmouseout=\"this.src='$mouseout'\" />";
		$image = preg_replace(",( /)?".">,", $js_mouseover, $image);
	}

	return $image;
}

// accepte en entree un texte complet, un img-log (produit par #LOGO_XX),
// un tag <img ...> complet, ou encore un nom de fichier *local* (passer
// le filtre |copie_locale si on veut l'appliquer a un document)
function reduire_image($texte, $taille = -1, $taille_y = -1) {
	if (!$texte) return;

	// Cas du nom de fichier local
	if (preg_match(',^'._DIR_IMG.',', $texte)) {
		if (!@file_exists($texte)) {
			spip_log("Image absente : $texte");
			return '';
		} else {
			return reduire_une_image("<img src='$texte' />", $taille, $taille_y);
		}
	}

	// Cas general : trier toutes les images, avec eventuellement leur <span>
	if (preg_match_all(
	',(<(span|div) [^<>]*spip_documents[^<>]*>)?(<img\s.*>),Uims',
	$texte, $tags, PREG_SET_ORDER)) {
		foreach ($tags as $tag) {
			if ($reduit = reduire_une_image($tag[3], $taille, $taille_y))

				// En cas de span spip_documents, modifier le style=...width:
				if($tag[1]
				AND $w = extraire_attribut($reduit, 'width')) {
					$texte = str_replace($tag[1],
						inserer_attribut($tag[1], 'style', 
						preg_replace(", width: *\d+px,", " width: ${w}px",
						extraire_attribut($tag[1], 'style'))), $texte);
				}

				$texte = str_replace($tag[3], $reduit, $texte);
		}
	}
	
	return $texte;
}

function largeur($img) {
	if (!$img) return;
	include_ecrire('inc_logos.php3');
	list ($h,$l) = taille_image($img);
	return $l;
}
function hauteur($img) {
	if (!$img) return;
	include_ecrire('inc_logos.php3');
	list ($h,$l) = taille_image($img);
	return $h;
}

//
// Cree au besoin la copie locale d'un fichier distant
// mode = 'test' - ne faire que tester
// mode = 'auto' - charger au besoin
// mode = 'force' - charger toujours (mettre a jour)
//
function copie_locale($source, $mode='auto') {
	include_ecrire('inc_getdocument.php3');

	// Si copie_locale() est appele depuis l'espace prive
	if (!_DIR_RESTREINT
	AND strpos('../'.$source, _DIR_IMG) === 0)
		return '../'.$source;

	$local = fichier_copie_locale($source);

	if ($source != $local) {
		if (($mode=='auto' AND !@file_exists($local))
		OR $mode=='force') {
			include_ecrire('inc_sites.php3');
			$contenu = recuperer_page($source);
			if ($contenu) {
				ecrire_fichier($local, $contenu);

				// signaler au moteur de recherche qu'il peut reindexer ce doc
				$a = spip_query("SELECT id_document FROM spip_documents
					WHERE fichier='".addslashes($source)."'");
				list($id_document) = spip_fetch_array($a);
				if ($id_document) {
					include_ecrire('inc_index.php3');
					marquer_indexer('document', $id_document);
				}
			}
			else
				return false;
		}
	}

	return $local;
}


//
// Recuperation de donnees dans le champ extra
// Ce filtre n'a de sens qu'avec la balise #EXTRA
//
function extra($letexte, $champ) {
	$champs = unserialize($letexte);
	return $champs[$champ];
}

// postautobr : transforme les sauts de ligne en _
function post_autobr($texte, $delim="\n_ ") {
	$texte = str_replace("\r\n", "\r", $texte);
	$texte = str_replace("\r", "\n", $texte);
	list($texte, $les_echap) = echappe_html($texte, "POSTAUTOBR", true);

	$debut = '';
	$suite = $texte;
	while ($t = strpos('-'.$suite, "\n", 1)) {
		$debut .= substr($suite, 0, $t-1);
		$suite = substr($suite, $t);
		$car = substr($suite, 0, 1);
		if (($car<>'-') AND ($car<>'_') AND ($car<>"\n") AND ($car<>"|"))
			$debut .= $delim;
		else
			$debut .= "\n";
		if (ereg("^\n+", $suite, $regs)) {
			$debut.=$regs[0];
			$suite = substr($suite, strlen($regs[0]));
		}
	}
	$texte = $debut.$suite;

	$texte = echappe_retour($texte, $les_echap, "POSTAUTOBR");
	return $texte;
}


//
// Gestion des blocs multilingues
//

//
// Selection dans un tableau dont les index sont des noms de langues
// de la valeur associee a la langue en cours
//

function multi_trad ($trads) {
	global  $spip_lang; 

	if (isset($trads[$spip_lang])) {
		return $trads[$spip_lang];

	}	// cas des langues xx_yy
	else if (ereg('^([a-z]+)_', $spip_lang, $regs) AND isset($trads[$regs[1]])) {
		return $trads[$regs[1]];
	}	
	// sinon, renvoyer la premiere du tableau
	// remarque : on pourrait aussi appeler un service de traduction externe
	// ou permettre de choisir une langue "plus proche",
	// par exemple le francais pour l'espagnol, l'anglais pour l'allemand, etc.
	else  return array_shift($trads);
}

// analyse un bloc multi
function extraire_trad ($bloc) {
	$lang = '';
// ce reg fait planter l'analyse multi s'il y a de l'{italique} dans le champ
//	while (preg_match("/^(.*?)[{\[]([a-z_]+)[}\]]/si", $bloc, $regs)) {
	while (preg_match("/^(.*?)[\[]([a-z_]+)[\]]/si", $bloc, $regs)) {
		$texte = trim($regs[1]);
		if ($texte OR $lang)
			$trads[$lang] = $texte;
		$bloc = substr($bloc, strlen($regs[0]));
		$lang = $regs[2];
	}
	$trads[$lang] = $bloc;

	// faire la traduction avec ces donnees
	return multi_trad($trads);
}

// repere les blocs multi dans un texte et extrait le bon
function extraire_multi ($letexte) {
	if (strpos($letexte, '<multi>') === false) return $letexte; // perf
	if (preg_match_all("@<multi>(.*?)</multi>@s", $letexte, $regs, PREG_SET_ORDER))
		foreach ($regs as $reg)
			$letexte = str_replace($reg[0], extraire_trad($reg[1]), $letexte);
	return $letexte;
}


// Raccourci ancre [#ancre<-]
function avant_propre_ancres($texte) {
	$regexp = "|\[#?([^][]*)<-\]|";
	if (preg_match_all($regexp, $texte, $matches, PREG_SET_ORDER))
	foreach ($matches as $regs)
		$texte = str_replace($regs[0],
		'<a name="'.entites_html($regs[1]).'"></a>', $texte);
	return $texte;
}

// Raccourci typographique <sc></sc>
function avant_typo_smallcaps($texte) {
	$texte = str_replace("<sc>", "<span style=\"font-variant: small-caps\">", $texte);
	$texte = str_replace("</sc>", "</span>", $texte);
	
	return $texte;
}

//
// Ce filtre retourne la donnee si c'est la premiere fois qu'il la voit ;
// possibilite de gerer differentes "familles" de donnees |unique{famille}
# ameliorations possibles :
# 1) si la donnee est grosse, mettre son md5 comme cle
# 2) purger $mem quand on change de squelette (sinon bug inclusions)
//
// http://www.spip.net/@unique
function unique($donnee, $famille='') {
	static $mem;
	if (!($mem[$famille][$donnee]++))
		return $donnee;
}

//
// Filtre |alterner
//
// Exemple [(#COMPTEUR_BOUCLE|alterner{'bleu','vert','rouge'})]
//
function alterner($i) {
	// recuperer les arguments (attention fonctions un peu space)
	$num = func_num_args();
	$args = func_get_args();

	// renvoyer le i-ieme argument, modulo le nombre d'arguments
	return $args[(intval($i)-1)%($num-1)+1];
}

// recuperer un attribut html d'une balise
// ($complet demande de retourner $r)
function extraire_attribut($balise, $attribut, $complet = false) {
	if (preg_match(",(.*<[^>]*)([[:space:]]+$attribut=[[:space:]]*(['\"])?(.*?)\\3)([^>]*>.*),ims", $balise, $r)) {
		$att = $r[4];
	}
	else
		$att = NULL;

	if ($complet)
		return array($att, $r);
	else
		return $att;
}

// modifier (ou inserer) un attribut html dans une balise
function inserer_attribut($balise, $attribut, $val, $texte_backend=true) {
	// preparer l'attribut
	if ($texte_backend) $val = texte_backend($val); # supprimer les &nbsp; etc

	// echapper les ' pour eviter tout bug
	$val = str_replace("'", "&#39;", $val);
	$insert = " $attribut='$val' ";

	list($old,$r) = extraire_attribut($balise, $attribut, true);

	if ($old !== NULL) {
		// Remplacer l'ancien attribut du meme nom
		$balise = $r[1].$insert.$r[5];
	}
	else {
		// preferer une balise " />" (comme <img />)
		if (preg_match(',[[:space:]]/>,', $balise))
			$balise = preg_replace(",[[:space:]]/>,", $insert."/>", $balise, 1);
		// sinon une balise <a ...> ... </a>
		else
			$balise = preg_replace(",>,", $insert.">", $balise, 1);
	}

	return $balise;
}


// fabrique un bouton de type $t de Name $n, de Value $v et autres attributs $a
# a placer ailleurs que dans inc_filtres
function boutonne($t, $n, $v, $a='') {
  return "\n<input type='$t'" .
    (!$n ? '' : " name='$n'") .
    " value=\"$v\" $a />";
}

function http_script($script, $src='', $noscript='') {
	return '<script type="text/javascript"'
		. ($src ? " src=\"$src\"" : '')
		. ">"
		. ($script ? "<!--\n$script\n//-->" : '')
		. "</script>\n"
		. (!$noscript ? '' : "<noscript>\n\t$noscript\n</noscript>\n");
}


// Un filtre ad hoc, qui retourne ce qu'il faut pour les tests de config
// dans les squelettes : [(#URL_SITE_SPIP|tester_config{quoi})]
function tester_config($ignore, $quoi) {
	switch ($quoi) {
		case 'mode_inscription':
			if (lire_meta("accepter_inscriptions") == "oui")
				return 'redac';
			else if (lire_meta("accepter_visiteurs") == "oui"
			OR lire_meta('forums_publics') == 'abo')
				return 'forum';
			else
				return '';

		default:
			return '';
	}
}

//
// Un filtre qui, etant donne un #PARAMETRES_FORUM, retourne un URL de suivi rss
// dudit forum
// Attention applique a un #PARAMETRES_FORUM complexe (id_article=x&id_forum=y)
// ca retourne un url de suivi du thread y (que le thread existe ou non)
function url_rss_forum($param) {
	if (preg_match(',.*(id_.*?)=([0-9]+),', $param, $regs)) {
		include_ecrire('inc_acces.php3');
		$regs[1] = str_replace('id_forum', 'id_thread', $regs[1]);
		$arg = $regs[1].'-'.$regs[2];
		$cle = afficher_low_sec(0, "rss forum $arg");
		return "spip_rss.php?op=forum&amp;args=$arg&amp;cle=$cle";
	}
}


// filtre pour visualiser dans l'espace public le calendrier de l'espace de redac
// Tres ad hoc, faudra ameliorer.

function calendrier($date='', $type='mois', $echelle='', $partie_cal='', $script='')
{
   include_ecrire("inc_calendrier.php");
   include_ecrire("inc_layer.php3");
   if (!isset($GLOBALS['spip_ecran'])) $GLOBALS['spip_ecran'] = 'large';
   if (isset($GLOBALS['mois'])) $date ='';
   return
     $GLOBALS['browser_layer'] .
     http_script('',_DIR_RESTREINT . 'presentation.js') .
     http_calendrier_init($date, $type, $echelle, $partie_cal, $script);
}


//
// Filtres d'URLs
//

// Nettoyer une URL contenant des ../
//
// echo resolve_url('/.././/truc/chose/machin/./.././.././hopla/..');
// inspire (de loin) par PEAR:NetURL:resolvePath
//
function resolve_path($url) {
	while (preg_match(',/\.?/,', $url, $regs)		# supprime // et /./
	OR preg_match(',/[^/]*/\.\./,', $url, $regs)	# supprime /toto/../
	OR preg_match(',^/\.\./,', $url, $regs))		# supprime les /../ du haut
		$url = str_replace($regs[0], '/', $url);

	return '/'.preg_replace(',^/,', '', $url);
}

// 
// Suivre un lien depuis une adresse donnee -> nouvelle adresse
//
// echo suivre_lien('http://rezo.net/sous/dir/../ect/ory/fi.html..s#toto',
// 'a/../../titi.coco.html/tata#titi');
function suivre_lien($url, $lien) {
	# lien absolu ? ok
	if (preg_match(',^([a-z0-9]+://|mailto:),i', $lien))
		return $lien;

	# lien relatif, il faut verifier l'url de base
	if (preg_match(',^(.*?://[^/]+)(/.*?/?)?[^/]*$,', $url, $regs)) {
		$debut = $regs[1];
		$dir = $regs[2];
	}
	if (substr($lien,0,1) == '/')
		return $debut . resolve_path($lien);
	else
		return $debut . resolve_path($dir.$lien);
}

// un filtre pour transformer les URLs relatives en URLs absolues ;
// ne s'applique qu'aux #URL_XXXX
function url_absolue($url, $base='') {
	if (strlen($url = trim($url)) == 0)
		return '';
	if (!$base) $base=lire_meta('adresse_site').'/';
	return suivre_lien($base, $url);
}

// un filtre pour transformer les URLs relatives en URLs absolues ;
// ne s'applique qu'aux textes contenant des liens
function liens_absolus($texte, $base='') {
	if (preg_match_all(',(<a[[:space:]]+[^<>]*href=["\']?)([^"\' ><[:space:]]+)([^<>]*>),ims', 
	$texte, $liens, PREG_SET_ORDER)) {
		foreach ($liens as $lien) {
			$abs = url_absolue($lien[2], $base);
			if ($abs <> $lien[2])
				$texte = str_replace($lien[0], $lien[1].$abs.$lien[3], $texte);
		}
	}
	if (preg_match_all(',(<img[[:space:]]+[^<>]*src=["\']?)([^"\' ><[:space:]]+)([^<>]*>),ims', 
	$texte, $liens, PREG_SET_ORDER)) {
		foreach ($liens as $lien) {
			$abs = url_absolue($lien[2], $base);
			if ($abs <> $lien[2])
				$texte = str_replace($lien[0], $lien[1].$abs.$lien[3], $texte);
		}
	}
	return $texte;
}

//
// Ce filtre public va traiter les URL ou les <a href>
//
function abs_url($texte, $base='') {
	if ($GLOBALS['mode_abs_url'] == 'url')
		return url_absolue($texte, $base);
	else
		return liens_absolus($texte, $base);
}


// Image typographique

function printWordWrapped($image, $top, $left, $maxWidth, $font, $color, $text, $textSize, $align="left") {
	$words = explode(' ', strip_tags($text)); // split the text into an array of single words
	$line = '';
	while (count($words) > 0) {
		$dimensions = imageftbbox($textSize, 0, $font, $line.' '.$words[0]);
		$lineWidth = $dimensions[2] - $dimensions[0]; // get the length of this line, if the word is to be included
		if ($lineWidth > $maxWidth) { // if this makes the text wider that anticipated
			$lines[] = $line; // add the line to the others
			$line = ''; // empty it (the word will be added outside the loop)
		}
		$line .= ' '.$words[0]; // add the word to the current sentence
		$words = array_slice($words, 1); // remove the word from the array
	}
	if ($line != '') { $lines[] = $line; } // add the last line to the others, if it isn't empty
	$lineHeight = floor($textSize * 1.3);
	$height = count($lines) * $lineHeight; // the height of all the lines total
	// do the actual printing
	$i = 0;
	foreach ($lines as $line) {
		$line = ereg_replace("~", " ", $line);
		$dimensions = imageftbbox($textSize, 0, $font, $line);
		$largeur_ligne = $dimensions[2] - $dimensions[0];
		if ($largeur_ligne > $largeur_max) $largeur_max = $largeur_ligne;
		if ($align == "right") $left_pos = $maxWidth - $largeur_ligne;
		else if ($align == "center") $left_pos = floor(($maxWidth - $largeur_ligne)/2);
		else $left_pos = 0;
		imagefttext($image, $textSize, 0, $left + $left_pos, $top + $lineHeight * $i, $color, $font, trim($line));
		$i++;
	}
	$retour["height"] = $height;
	$retour["width"] = $largeur_max;
                 
	$dimensions_espace = imageftbbox($textSize, 0, $font, ' ');
	$largeur_espace = $dimensions_espace[2] - $dimensions_espace[0];
	$retour["espace"] = $largeur_espace;
	return $retour;
}



function image_typo() {
	/*
	arguments autorises:
	
	$texte : le texte a transformer; attention: c'est toujours le premier argument, et c'est automatique dans les filtres
	$couleur : la couleur du texte dans l'image - pas de dieze
	$fond: ne pas utiliser, puisque transparence du PNG
	$ombre: la couleur de l'ombre
	$ombrex, $ombrey: les decalages en pixels de l'ombre
	$police: nom du fichier Truetype de la police
	$largeur: la largeur maximale de l'image ; attention, l'image retournee a une largeur inferieure, selon les limites reelles du texte
	
	$alt: pour forcer l'affichage d'un alt; attention, comme utilisation d'un span invisible pour affiche le texte, generalement inutile
	
	
	*/



	// Recuperer les differents arguments
	$numargs = func_num_args();
	$arg_list = func_get_args();
	$texte = $arg_list[0];
	for ($i = 1; $i < $numargs; $i++) {
		if (ereg("\=", $arg_list[$i])) {
			$nom_variable = substr($arg_list[$i], 0, strpos($arg_list[$i], "="));
			$val_variable = substr($arg_list[$i], strpos($arg_list[$i], "=")+1, strlen($arg_list[$i]));
		
			$variable["$nom_variable"] = $val_variable;
		}
		
	}

	// Construire requete et nom fichier
	$text = ereg_replace("\&nbsp;", "~", $texte);	

	$taille = $variable["taille"];
	if ($taille < 1) $taille = 16;

	$couleur = $variable["couleur"];
	if (strlen($couleur) < 6) $couleur = "000000";

	$fond = $variable["fond"];
	if (strlen($fond) < 6) $fond = "ffffff";
	
	$alt = $texte;
	
	$ombre = $variable["ombre"];
	$ombrex = $variable["ombrex"];
	$ombrey = $variable["ombrey"];
	if (!$variable["ombrex"]) $ombrex = 1;
	if (!$variable["ombrey"]) $ombrey = $ombrex;
	
	$align = $variable["align"];
	if (!$variable["align"]) $align="left";
	 
	$police = $variable["police"];
	if (strlen($police) < 2) $police = "dustismo.ttf";

	$largeur = $variable["largeur"];
	if ($largeur < 5) $largeur = 600;


	$string = "$text-$taille-$couleur-$fond-$ombre-$ombrex-$ombrey-$align-$police-$largeur";
	$query = md5($string);
	$dossier = _DIR_IMG. creer_repertoire(_DIR_IMG, 'cache-texte');
	$fichier = "$dossier$query.png";

	$flag_gd_typo = function_exists("imageftbbox")
		&& function_exists('imageCreateTrueColor');

	
	if (!file_exists($fichier) AND $flag_gd_typo) {
		// Il faut completer avec un vrai _SPIP_PATH, de facon a pouvoir livrer des /polices dans les dossiers de squelettes
		$font = find_in_path("polices/$police", "ecrire");
	
		$imgbidon = imageCreateTrueColor($largeur, 45);
		$retour = printWordWrapped($imgbidon, $taille+5, 0, $largeur, $font, $black, $text, $taille);
		$hauteur = $retour["height"];
		$largeur_reelle = $retour["width"];
		$espace = $retour["espace"];
		imagedestroy($imgbidon);
		
		$im = imageCreateTrueColor($largeur_reelle+$ombrex-$espace, $hauteur+5+$ombrey);
		imagealphablending ($im, FALSE );
		imagesavealpha ( $im, TRUE );
		
		// CrŽation de quelques couleurs
		if (strlen($ombre) == 6) $grey = imagecolorallocatealpha($im, hexdec("0x{".substr($ombre, 0,2)."}"), hexdec("0x{".substr($ombre, 2,2)."}"), hexdec("0x{".substr($ombre, 4,2)."}"), 50);
		$black = imagecolorallocatealpha($im, hexdec("0x{".substr($couleur, 0,2)."}"), hexdec("0x{".substr($couleur, 2,2)."}"), hexdec("0x{".substr($couleur, 4,2)."}"), 0);
		$grey2 = imagecolorallocatealpha($im, hexdec("0x{".substr($fond, 0,2)."}"), hexdec("0x{".substr($fond, 2,2)."}"), hexdec("0x{".substr($fond, 4,2)."}"), 127);
		
		ImageFilledRectangle ($im,0,0,$largeur+$ombrex,$hauteur+5+$ombrey,$grey2);
		
		// Le texte ˆ dessiner
		// Remplacez le chemin par votre propre chemin de police
		//global $text;
				
		if (strlen($ombre) == 6) printWordWrapped($im, $taille+$ombrey+5, $ombrex, $largeur, $font, $grey, $text, $taille, $align);
		printWordWrapped($im, $taille+5, 0, $largeur, $font, $black, $text, $taille, $align);
		
		
		// Utiliser imagepng() donnera un texte plus claire,
		// comparŽ ˆ l'utilisation de la fonction imagejpeg()
		imagepng($im, $fichier);
		imagedestroy($im);
		
		$image = $fichier;
		
	} else if ($flag_gd_typo) {
		$image = $fichier;
	}

	if ($image) {
		$dimensions = getimagesize($image);
		$largeur = $dimensions[0];
		$hauteur = $dimensions[1];
		return inserer_attribut("<img src='$image' style='border: 0px; width: ".$largeur."px; height: ".$hauteur.px."' class='image_typo'>", 'alt', $alt);
	} else {
		return $texte;
	}

}

function modulo($nb, $mod, $add=0)
{
  return ($nb%$mod)+$add;
}
?>
