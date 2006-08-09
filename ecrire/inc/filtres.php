<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/charsets');
// on definit la matrice pour les filtres images : le compilateur fera passer l'appel par filtrer
// on ne definit pas de fichier a inclure : l'inclusion sera faite dans image_filtrer
// par un include_spip unique en cas d'appel multiple

$GLOBALS['spip_matrice']['image_valeurs_trans'] = '';
$GLOBALS['spip_matrice']['image_reduire'] = '';
$GLOBALS['spip_matrice']['image_reduire_par'] = '';
$GLOBALS['spip_matrice']['image_alpha'] = '';
$GLOBALS['spip_matrice']['image_flip_vertical'] = '';
$GLOBALS['spip_matrice']['image_flip_horizontal'] = '';
$GLOBALS['spip_matrice']['image_masque'] = '';
$GLOBALS['spip_matrice']['image_nb'] = '';
$GLOBALS['spip_matrice']['image_flou'] = '';
$GLOBALS['spip_matrice']['image_RotateBicubic'] = '';
$GLOBALS['spip_matrice']['image_rotation'] = '';
$GLOBALS['spip_matrice']['image_distance_pixel'] = '';
$GLOBALS['spip_matrice']['image_decal_couleur'] = '';
$GLOBALS['spip_matrice']['image_gamma'] = '';
$GLOBALS['spip_matrice']['image_decal_couleur_127'] = '';
$GLOBALS['spip_matrice']['image_sepia'] = '';
$GLOBALS['spip_matrice']['image_aplatir'] = '';
$GLOBALS['spip_matrice']['image_couleur_extraire'] = '';


$inc_filtres_images = _DIR_RESTREINT."inc/filtres_images"; # find_in_path('inc/filtres_images');
$GLOBALS['spip_matrice']['couleur_dec_to_hex'] = $inc_filtres_images;
$GLOBALS['spip_matrice']['couleur_hex_to_dec'] = $inc_filtres_images;
$GLOBALS['spip_matrice']['couleur_extreme'] = $inc_filtres_images;
$GLOBALS['spip_matrice']['couleur_inverser'] = $inc_filtres_images;
$GLOBALS['spip_matrice']['couleur_eclaircir'] = $inc_filtres_images;
$GLOBALS['spip_matrice']['couleur_foncer'] = $inc_filtres_images;
$GLOBALS['spip_matrice']['couleur_foncer_si_claire'] = $inc_filtres_images;
$GLOBALS['spip_matrice']['couleur_eclaircir_si_foncee'] = $inc_filtres_images;

// Appliquer un filtre (eventuellement defini dans la matrice) aux donnees
// et arguments
function filtrer($filtre) {
	if ($f = $GLOBALS['spip_matrice'][$filtre])
		include_once($f);

	$tous = func_get_args();
	if (substr($filtre,0,6)=='image_')
		return image_filtrer($tous);
	else{
		array_shift($tous); # enlever $filtre
		return call_user_func_array($filtre, $tous);
	}
}

//
// Fonctions graphiques
//

// fonction generique d'entree des filtres images
// accepte en entree un texte complet, un img-log (produit par #LOGO_XX),
// un tag <img ...> complet, ou encore un nom de fichier *local* (passer
// le filtre |copie_locale si on veut l'appliquer a un document)
// applique le filtre demande a chacune des occurences

function image_filtrer($args){
	static $inclure = true;
	$filtre = array_shift($args); # enlever $filtre
	$texte = array_shift($args);
	if (!$texte) return;
	// Cas du nom de fichier local
	if (preg_match(',^'._DIR_IMG.',', $texte)) {
		if (!@file_exists($texte)) {
			spip_log("Image absente : $texte");
			return '';
		} else {
			if ($inclure){
				include_spip('inc/filtres_images');
				$inclure = false;
			}
			array_unshift($args,"<img src='$texte' />");
			return call_user_func_array($filtre, $args);
		}
	}

	// Cas general : trier toutes les images, avec eventuellement leur <span>
	if (preg_match_all(
	',(<(span|div) [^<>]*spip_documents[^<>]*>)?(<img\s.*>),Uims',
	$texte, $tags, PREG_SET_ORDER)) {
		if ($inclure){
			include_spip('inc/filtres_images');
			$inclure = false;
		}
		foreach ($tags as $tag) {
			array_unshift($args,$tag[3]);
			if ($reduit = call_user_func_array($filtre, $args)) {
				// En cas de span spip_documents, modifier le style=...width:
				if($tag[1]
				AND $w = extraire_attribut($reduit, 'width')) {
					$style = preg_replace(", width: *\d+px,", " width: ${w}px",
						extraire_attribut($tag[1], 'style'));
					$replace = inserer_attribut($tag[1], 'style', $style);
					$replace = str_replace(" style=''", '', $replace);
					$texte = str_replace($tag[1], $replace, $texte);
				}

				$texte = str_replace($tag[3], $reduit, $texte);
			}
			array_shift($args);
		}
	}

	return $texte;
}

// Pour assurer la compatibilite avec les anciens nom des filtres image_xxx
// commencent par "image_"
function reduire_image($texte, $taille = -1, $taille_y = -1) {
	return filtrer('image_reduire',$texte, $taille, $taille_y);
}
function valeurs_image_trans($img, $effet, $forcer_format = false) {
	return filtrer('image_valeurs_trans',$img, $effet, $forcer_format);
}
function couleur_extraire($img, $x=10, $y=6) {
	return filtrer('image_couleur_extraire',$img, $x, $y);
}
function image_typo() {
	include_spip('inc/filtres_images');
	$tous = func_get_args();
	return call_user_func_array('produire_image_typo', $tous);
}

function largeur($img) {
	if (!$img) return;
	include_spip('inc/logos');
	list ($h,$l) = taille_image($img);
	return $l;
}
function hauteur($img) {
	if (!$img) return;
	include_spip('inc/logos');
	list ($h,$l) = taille_image($img);
	return $h;
}


// Echappement des entites HTML avec correction des entites "brutes"
// (generees par les butineurs lorsqu'on rentre des caracteres n'appartenant
// pas au charset de la page [iso-8859-1 par defaut])
//
// Attention on limite cette correction aux caracteres "hauts" (en fait > 99
// pour aller plus vite que le > 127 qui serait logique), de maniere a
// preserver des echappements de caracteres "bas" (par exemple [ ou ")
// et au cas particulier de &amp; qui devient &amp;amp; dans les url
function corriger_entites_html($texte) {
	return preg_replace(',&amp;(#[0-9][0-9][0-9]+;|amp;),i', '&\1', $texte);
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
#	include_spip('inc/charsets');
	// filtrer
	$texte = html2unicode($texte);
	// remettre le tout dans le charset cible
	return unicode2charset($texte);
}

// caracteres de controle - http://www.w3.org/TR/REC-xml/#charsets
function supprimer_caracteres_illegaux($texte) {
	$from = "\x0\x1\x2\x3\x4\x5\x6\x7\x8\xB\xC\xE\xF\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F";
	$to = str_repeat('-', strlen($from));
	return strtr($texte, $from, $to);
}

// Supprimer caracteres windows et les caracteres de controle ILLEGAUX
function corriger_caracteres ($texte) {
	include_spip('inc/charsets');
	$texte = corriger_caracteres_windows($texte);
	$texte = supprimer_caracteres_illegaux($texte);
	return $texte;
}

// Encode du HTML pour transmission XML
function texte_backend($texte) {

	// si on a des liens ou des images, les passer en absolu
	$texte = liens_absolus($texte);

	// echapper les tags &gt; &lt;
	$texte = preg_replace(',&(gt|lt);,', '&amp;\1;', $texte);

	// importer les &eacute;
	$texte = filtrer_entites($texte);

	// " -> &quot; et tout ce genre de choses
	// contourner bug windows ou char(160) fait partie de la regexp \s
	$u = ($GLOBALS['meta']['charset']=='utf-8') ? 'u':'';
	$texte = str_replace("&nbsp;", " ", $texte);
	$texte = preg_replace("/\s\s+/$u", " ", $texte);
	$texte = entites_html($texte);

	// verifier le charset
	$texte = charset2unicode($texte);

	// Caracteres problematiques en iso-latin 1
	if ($GLOBALS['meta']['charset'] == 'iso-8859-1') {
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
	return preg_replace(
	",^[[:space:]]*([0-9]+)([.)]|".chr(194).'?'.chr(176).")[[:space:]]+,",
	"", $texte);
}

// et la fonction inverse
function recuperer_numero($texte) {
	if (preg_match(
	",^[[:space:]]*([0-9]+)([.)]|".chr(194).'?'.chr(176).")[[:space:]]+,",
	$texte, $regs))
		return intval($regs[1]);
	else
		return '';
}

// Suppression basique et brutale de tous les <...>
function supprimer_tags($texte, $rempl = "") {
	$texte = preg_replace(",<[^>]*>,U", $rempl, $texte);
	// ne pas oublier un < final non ferme
	// mais qui peut aussi etre un simple signe plus petit que
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
	$u = ($GLOBALS['meta']['charset']=='utf-8') ? 'u':'';
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
	#include_spip('inc/charsets');
	$texte = unicode_to_utf_8(charset2unicode(
		$texte, $GLOBALS['meta']['charset'], true));

	// echapper les tags (on ne veut pas casser les a href=...)
	$tags = array();
	if (preg_match_all('/<.*>/Uums', $texte, $t, PREG_SET_ORDER)) {
		foreach ($t as $n => $tag) {
			$tags[$n] = $tag[0];
			$texte = str_replace($tag[0], " @@SPIPTAG$n@@ ", $texte);
		}
	}
	// casser les mots longs qui restent
	// note : on pourrait preferer couper sur les / , etc.
	if (preg_match_all("/[\w,\/.]{".$l."}/Ums", $texte, $longs, PREG_SET_ORDER)) {
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
	if (!strlen($texte)) return '';

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
	return "<span style='text-transform: uppercase;'>$texte</span>";
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

//
// Ajouter le &var_recherche=toto dans les boucles de recherche
//
function url_var_recherche($url) {
	if (_request('recherche')
	AND !ereg("var_recherche", $url)) {

		list ($url,$ancre) = preg_split(',#,', $url, 2);
		if ($ancre) $ancre='#'.$ancre;

		$x = "var_recherche=".rawurlencode(_request('recherche'));

		if (strpos($url, '?') === false)
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
		return $regs[1]."-".sprintf("%02d", $regs[2])."-01";
}

// Maquiller une adresse e-mail
function antispam($texte) {
	include_spip('inc/acces');
	$masque = creer_pass_aleatoire(3);
	return ereg_replace("@", " $masque ", $texte);
}

// |sinon{rien} : affiche "rien" si la chaine est vide, affiche la chaine si non vide
function sinon ($texte, $sinon='') {
	if (strlen($texte))
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
	if (ereg("^1970-01-01", $letexte)) return;	// eviter le bug GMT-1
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
	if (ereg('([0-9]{1,2})/([0-9]{1,2})/([0-9]{1,2}|[0-9]{4})', $numdate, $regs)) {
		$jour = $regs[1];
		$mois = $regs[2];
		$annee = $regs[3];
		if ($annee < 90){
			$annee = 2000 + $annee;
		} elseif ($annee<100) {
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
// Alignements en HTML (Old-style, preferer CSS)
//

// Cette fonction cree le paragraphe s'il n'existe pas (texte sur un seul para)
function aligner($letexte, $justif='') {
	$letexte = trim($letexte);
	if (!strlen($letexte)) return '';

	// Ajouter un paragraphe au debut, et reparagrapher proprement
	$letexte = paragrapher(
		str_replace('</p>', '', '<p>'.$letexte));

	// Inserer les alignements
	return str_replace(
		'<p class="spip">', '<p class="spip" align="'.$justif.'">',
		$letexte);
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
	#include_spip('inc/charsets');
	$texte = html2unicode($texte);
	$texte = unicode2charset(charset2unicode($texte, $GLOBALS['meta']['charset'], 1), 'utf-8');
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

// date_iso retourne la date au format "RFC 3339" / "ISO 8601"
// voir http://www.php.net/manual/fr/ref.datetime.php#datetime.constants
function date_iso($date_heure) {
	list($annee, $mois, $jour) = recup_date($date_heure);
	list($heures, $minutes, $secondes) = recup_heure($date_heure);
	$time = mktime($heures, $minutes, $secondes, $mois, $jour, $annee);
	return gmdate('Y-m-d\TH:i:s\Z', $time);
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
  $debut = $jour-$w_day+1;
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

// Cette fonction recoit:
// - un nombre d'evenements, 
// - une chaine à afficher si ce nombre est nul, 
// - un type de calendrier
// -- et une suite de noms N.
// Elle demande a la fonction precedente son tableau
// et affiche selon le type les elements indexes par N dans ce tableau.
// Si le suite de noms est vide, tout le tableau est pris
// Ces noms N sont aussi des classes CSS utilisees par http_calendrier_init

function agenda_affiche($i)
{
  include_spip('inc/agenda');
  include_spip('inc/minipres');
  $args = func_get_args();
  $nb = array_shift($args); // nombre d'evenements (on pourrait l'afficher)
  $sinon = array_shift($args);
  $type = array_shift($args);
  if (!$nb) 
    return http_calendrier_init('', $type, '', '', str_replace('&amp;', '&', self()), $sinon);
  $agenda = agenda_memo(0);
  $evt = array();
  foreach (($args ? $args : array_keys($agenda)) as $k) {  
      if (is_array($agenda[$k]))
	foreach($agenda[$k] as $d => $v) { 
	  $evt[$d] = $evt[$d] ? (array_merge($evt[$d], $v)) : $v;
	}
    }
  $d = array_keys($evt);
  $mindate = min($d);
  $start = strtotime($mindate);
  if ($type != 'periode')
      $evt = array('', $evt);
  else
      {
	$min = substr($mindate,6,2);
	$max = $min + ((strtotime(max($d)) - $start) / (3600 * 24));
	if ($max < 31) $max = 0;
	$evt = array('', $evt, $min, $max);
	$type = 'mois';
      }
  return http_calendrier_init($start, $type, '', '', str_replace('&amp;', '&', self()), $evt);
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
	$texte = echappe_html($texte, '', true);

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

	$texte = echappe_retour($texte);
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

// recuperer une balise HTML de type "xxx"
// exemple : [(#DESCRIPTIF|extraire_tag{img})] (pour flux RSS-photo)
function extraire_tag($texte, $tag) {
	if (preg_match(",<$tag(\\s.*)?".">,Uims", $texte, $regs))
		return $regs[0];
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
function inserer_attribut($balise, $attribut, $val, $texte_backend=true, $vider=false) {
	// preparer l'attribut
	if ($texte_backend) $val = texte_backend($val); # supprimer les &nbsp; etc

	// echapper les ' pour eviter tout bug
	$val = str_replace("'", "&#39;", $val);
	if ($vider AND strlen($val)==0)
		$insert = '';
	else
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

function vider_attribut ($balise, $attribut) {
	return inserer_attribut($balise, $attribut, '', false, true);
}


// Un filtre ad hoc, qui retourne ce qu'il faut pour les tests de config
// dans les squelettes : [(#URL_SITE_SPIP|tester_config{quoi})]
function tester_config($ignore, $quoi) {
	switch ($quoi) {
		case 'mode_inscription':
			if ($GLOBALS['meta']["accepter_inscriptions"] == "oui")
				return 'redac';
			else if ($GLOBALS['meta']["accepter_visiteurs"] == "oui"
			OR $GLOBALS['meta']['forums_publics'] == 'abo')
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
		include_spip('inc/acces');
		$regs[1] = str_replace('id_forum', 'id_thread', $regs[1]);
		$arg = $regs[1].'-'.$regs[2];
		$cle = afficher_low_sec(0, "rss forum $arg");
		return generer_url_action('rss', "op=forum&args=$arg&cle=$cle");
	}
}

//
// Un filtre applique a #PARAMETRES_FORUM, qui donne l'adresse de la page
// de reponse
//
function url_reponse_forum($parametres) {
	if (!$parametres) return '';
	return generer_url_public('forum', $parametres);
}

//
// Filtres d'URLs
//

// Nettoyer une URL contenant des ../
//
// resolve_url('/.././/truc/chose/machin/./.././.././hopla/..');
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
// suivre_lien('http://rezo.net/sous/dir/../ect/ory/fi.html..s#toto',
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
	if (!$base)
		$base=$GLOBALS['meta']['adresse_site'].'/'
		. (_DIR_RACINE ? _DIR_RESTREINT_ABS : '');
	return suivre_lien($base, $url);
}

// un filtre pour transformer les URLs relatives en URLs absolues ;
// ne s'applique qu'aux textes contenant des liens
function liens_absolus($texte, $base='') {
	if (preg_match_all(',(<(a|link)[[:space:]]+[^<>]*href=["\']?)([^"\' ><[:space:]]+)([^<>]*>),ims', 
	$texte, $liens, PREG_SET_ORDER)) {
		foreach ($liens as $lien) {
			$abs = url_absolue($lien[3], $base);
			if ($abs <> $lien[3])
				$texte = str_replace($lien[0], $lien[1].$abs.$lien[4], $texte);
		}
	}
	if (preg_match_all(',(<(img|script)[[:space:]]+[^<>]*src=["\']?)([^"\' ><[:space:]]+)([^<>]*>),ims', 
	$texte, $liens, PREG_SET_ORDER)) {
		foreach ($liens as $lien) {
			$abs = url_absolue($lien[3], $base);
			if ($abs <> $lien[3])
				$texte = str_replace($lien[0], $lien[1].$abs.$lien[4], $texte);
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

//
// Quelques fonctions de calcul arithmetique
//
function plus($a,$b) {
	return $a+$b;
}
function moins($a,$b) {
	return $a-$b;
}
function mult($a,$b) {
	return $a*$b;
}
function div($a,$b) {
	return $b?$a/$b:0;
}
function modulo($nb, $mod, $add=0) {
	return ($nb%$mod)+$add;
}


// Verifier la conformite d'une ou plusieurs adresses email
//  retourne false ou la  normalisation de la derniere adresse donnee
function email_valide($adresses) {
	// Si c'est un spammeur autant arreter tout de suite
	if (preg_match(",[\n\r].*(MIME|multipart|Content-),i", $adresses)) {
		spip_log("Tentative d'injection de mail : $adresses");
		return false;
	}

	foreach (explode(',', $adresses) as $v) {
		// nettoyer certains formats
		// "Marie Toto <Marie@toto.com>"
		$adresse = trim(eregi_replace("^[^<>\"]*<([^<>\"]+)>$", "\\1", $v));
		// RFC 822
		if (!eregi('^[^()<>@,;:\\"/[:space:]]+(@([-_0-9a-z]+\.)*[-_0-9a-z]+)$', $adresse))
			return false;
	}
	return $adresse;
}

// Pour un champ de microformats :
// afficher les tags
// ou afficher les enclosures
function extraire_tags($tags) {
	if (preg_match_all(',<a([[:space:]][^>]*)?[[:space:]][^>]*>.*</a>,Uims',
	$tags, $regs, PREG_PATTERN_ORDER))
		return $regs[0];
	else
		return array();
}
function afficher_enclosures($tags) {
	$s = array();
	foreach (extraire_tags($tags) as $tag) {
		if (extraire_attribut($tag, 'rel') == 'enclosure'
		AND $t = extraire_attribut($tag, 'href')) {
			include_spip('inc/minipres'); #pour http_img_pack (quel bazar)
			$s[] = preg_replace(',>[^<]+</a>,', 
				'>'
				.http_img_pack('attachment.gif', $t,
					'height="15" width="15" title="'.attribut_html($t).'"')
				.'</a>', $tag);
		}
	}
	return join('&nbsp;', $s);
}
function afficher_tags($tags, $rels='tag,directory') {
	$s = array();
	foreach (extraire_tags($tags) as $tag) {
		$rel = extraire_attribut($tag, 'rel');
		if (strstr(",$rels,", ",$rel,"))
			$s[] = $tag;
	}
	return join(', ', $s);
}

// Passe un <enclosure url="fichier" length="5588242" type="audio/mpeg"/>
// au format microformat <a rel="enclosure" href="fichier" ...>fichier</a>
function enclosure2microformat($e) {
	$url = extraire_attribut($e, 'url');
	$fichier = basename($url) OR $fichier;
	$e = preg_replace(',<enclosure[[:space:]],i','<a rel="enclosure" ', $e)
		. $fichier.'</a>';
	$e = vider_attribut($e, 'url');
	$e = inserer_attribut($e, 'href', filtrer_entites($url));
	$e = str_replace('/>', '>', $e);
	return $e;
}
// La fonction inverse
function microformat2enclosure($tags) {
	$enclosures = array();
	foreach (extraire_tags($tags) as $e)
	if (extraire_attribut($e, rel) == 'enclosure') {
		$url = extraire_attribut($e, 'href');
		$fichier = basename($url) OR $fichier;
		$e = preg_replace(',<a[[:space:]],i','<enclosure ', $e);
		$e = preg_replace(',( ?/?)>.*,',' />', $e);
		$e = vider_attribut($e, 'href');
		$e = vider_attribut($e, 'rel');
		$e = inserer_attribut($e, 'url', filtrer_entites($url));
		$enclosures[] = $e;
	}
	return join("\n", $enclosures);
}
// Creer les elements ATOM <dc:subject> a partir des tags
function tags2dcsubject($tags) {
	$subjects = '';
	foreach (extraire_tags($tags) as $e) {
		if (extraire_attribut($e, rel) == 'tag') {
			$subjects .= '<dc:subject>'
				. texte_backend(textebrut($e))
				. '</dc:subject>'."\n";
		}
	}
	return $subjects;
}
// fabrique un bouton de type $t de Name $n, de Value $v et autres attributs $a
function boutonne($t, $n, $v, $a='') {
	return "\n<input type='$t'"
	. (!$n ? '' : " name='$n'")
	. " value=\"$v\" $a />";
}

// retourne la premiere balise du type demande
// ex: [(#DESCRIPTIF|extraire_balise{img})]
function extraire_balise($texte, $tag) {
	if (preg_match(",<$tag\\s.*>,Uims", $texte, $regs))
		return $regs[0];
}

// construit une balise textarea avec la barre de raccourcis std de Spip.
// ATTENTION: cette barre injecte un script JS que le squelette doit accepter
// donc ce filtre doit IMPERATIVEMENT assurer la securite a sa place

function barre_textarea($texte, $rows, $cols, $lang='') {
	static $num_textarea = 0;
	include_spip('inc/layer'); // definit browser_barre

	$texte = entites_html($texte);
	if (!$GLOBALS['browser_barre'])
		return "<textarea name='texte' rows='$rows' class='forml' cols='$cols'>$texte</textarea>";

	$num_textarea++;
	include_spip ('inc/barre');
	return afficher_barre("document.getElementById('textarea_$num_textarea')", true, $lang) .
	  "
<textarea name='texte' rows='$rows' class='forml' cols='$cols'
id='textarea_$num_textarea'
onselect='storeCaret(this);'
onclick='storeCaret(this);'
onkeyup='storeCaret(this);'
ondblclick='storeCaret(this);'>$texte</textarea>";
}

// comme in_array mais renvoie son 3e arg si le 2er arg n'est pas un tableau
// prend ' ' comme representant de vrai et '' de faux

function in_any($val, $vals, $def) {
  return (!is_array($vals) ? $def : (in_array($val, $vals) ? ' ' : ''));
}

// valeur_numerique("3*2") => 6
// n'accepte que les *, + et - (a ameliorer si on l'utilise vraiment)
function valeur_numerique($expr) {
	if (preg_match(',^[0-9]+(\s*[+*-]\s*[0-9]+)*$,', trim($expr)))
		eval("\$a = $expr;");
	return intval($a);
}

// Si on fait un formulaire qui GET ou POST des donnees sur un lien
// comprenant des arguments, il faut remettre ces valeurs dans des champs
// hidden ; cette fonction calcule les hidden en question
function form_hidden($action) {
	$hidden = '';
	if (false !== ($p = strpos($action, '?')))
		foreach(preg_split('/&(amp;)?/',substr($action,$p+1)) as $c) {
			$hidden .= "\n<input name='" .
				entites_html(rawurldecode(str_replace('=', "' value='", $c))) .
				"' type='hidden' />";
	}
	return $hidden;
}

function calcul_bornes_pagination($max, $nombre, $courante) {
	if (function_exists("bornes_pagination"))
		return bornes_pagination($max, $nombre, $courante);

	if($max<=0 OR $max>=$nombre)
		return array(1, $nombre);

	$premiere = max(1, $courante-floor(($max-1)/2));
	$derniere = min($nombre, $premiere+$max-2);
	$premiere = $derniere == $nombre ? $derniere-$max+1 : $premiere;
	return array($premiere, $derniere);
}

function pagination_item($num, $txt, $pattern, $lien_base, $debut, $ancre) {
	$url = parametre_url($lien_base, $debut, $num);
	return str_replace('@url@', $url.'#'.$ancre,
		str_replace('@item@', $txt,
		$pattern));
}

//
// fonction standard de calcul de la balise #PAGINATION
// on peut la surcharger en definissant dans mes_fonctions :
// function pagination($total, $nom, $pas, $liste) {...}
//

define('PAGINATION_MAX', 10);

function calcul_pagination($total, $nom, $pas, $liste = true) {
	static $ancres = array();
	$bloc_ancre = "";
	
	if ($pas<1) return;

	if (function_exists("pagination"))
		return pagination($total, $nom, $pas, $liste);

	$separateur = '&nbsp;| ';

	$debut = 'debut'.$nom;

	$pagination = array(
		'lien_base' => parametre_url(self(),'fragment',''), // nettoyer l'id ahah eventuel
		'total' => $total,
		'position' => intval(_request($debut)),
		'pas' => $pas,
		'nombre_pages' => floor(($total-1)/$pas)+1,
		'page_courante' => floor(intval(_request($debut))/$pas)+1,
		'lien_pagination' => '<a href="@url@" class="lien_pagination">@item@</a>',
		'lien_item_courant' => '<span class="on">@item@</span>'
	);

	$ancre='pagination'.$nom;

	// n'afficher l'ancre qu'une fois
	if (!isset($ancres[$ancre]))
		$bloc_ancre = $ancres[$ancre] = "<a name='$ancre' id='$ancre'></a>";

	// Pas de pagination
	if ($pagination['nombre_pages']<=1)
		return '';

	// liste = false : on ne veut que l'ancre
	if (!$liste)
		return $bloc_ancre;

	// liste  = true : on retourne tout (ancre + bloc de navigation)

	list ($premiere, $derniere) = calcul_bornes_pagination(
		PAGINATION_MAX,
		$pagination['nombre_pages'],
		$pagination['page_courante']);

	$texte = '';

	if ($premiere > 2)
		$texte .= pagination_item('',
			'...',
			$pagination[
				($i != $pagination['page_courante']) ?
				'lien_pagination' : 'lien_item_courant'
			],
			$pagination['lien_base'], $debut, $ancre)
			. $separateur;

	if ($premiere == 2) $premiere = 1; # '...' inutile quand on peut mettre 0

	for ($i = $premiere; $i<=$derniere; $i++) {
		$num = strval(($i-1)*$pas);
		$texte .= pagination_item($num,
			$num,
			$pagination[
				($i != $pagination['page_courante']) ?
				'lien_pagination' : 'lien_item_courant'
			],
			$pagination['lien_base'], $debut, $ancre);
		if ($i<$derniere) $texte .= $separateur;
	}

	if ($derniere < $pagination['nombre_pages'])
		$texte .= $separateur.
		pagination_item(strval(($pagination['nombre_pages']-1)*$pas),
			'...',
			$pagination[
				($i != $pagination['page_courante']) ?
				'lien_pagination' : 'lien_item_courant'
			],
			$pagination['lien_base'], $debut, $ancre);

	return $bloc_ancre.$texte;
}

// recuperere le chemin d'une css existante et :
// 1. regarde si une css inversee droite-gauche existe dans le meme repertoire
// 2. sinon la cree (ou la recree) dans IMG/cache_css/
// SI on lui donne a manger une feuille nommee _rtl.css il va faire l'inverse
function direction_css ($css) {
	if (!preg_match(',(_rtl)?\.css$,i', $css, $r)) return $css;

	$sens = $r[1] ? 'left' : 'right'; // sens de la css lue en entree
	$dir = $r[1] ? 'ltr' : 'rtl'; // direction voulu en sortie

	if ($GLOBALS['spip_lang_right'] == $sens)
		return $css;

	// 1.
	$f = preg_replace(',(_rtl)?\.css$,i', '_'.$dir.'.css', $css);
	if (@file_exists($f))
		return $f;

	// 2.
	$f = sous_repertoire (_DIR_IMG, 'cache-css')
		. preg_replace(',.*/(.*?)(_rtl)?\.css,', '\1', $css)
		. '.' . substr(md5($css), 0,4) . '_' . $dir . '.css';

	if ((@filemtime($f) > @filemtime($css))
	AND ($GLOBALS['var_mode'] != 'recalcul'))
		return $f;

	if (!lire_fichier($css, $contenu))
		return $css;

	$contenu = str_replace(
		array('right', 'left', '@@@@L E F T@@@@'),
		array('@@@@L E F T@@@@', 'right', 'left'),
		$contenu);

	if (!ecrire_fichier($f, $contenu))
		return $css;

	return $f;
}

### fonction depreciee, laissee ici pour compat ascendante 1.9
function entites_unicode($texte) { return charset2unicode($texte); }

// filtre table_valeur
// permet de recuperer la valeur d'un tableau pour une cle donnee
// prend en entree un tableau serialise ou non (ce qui permet d'enchainer le filtre)
function table_valeur($table,$cle,$defaut=''){
	$table= is_string($table)?unserialize($table):$table;
	$table= is_array($table)?$table:array();
	return isset($table[$cle])?$table[$cle]:$defaut;
}

// filtre match pour faire des tests avec expression reguliere
// [(#TEXTE|match{^ceci$,Uims})]
function match($texte,$expression,$modif="Uims"){
	$expression=str_replace("\/","/",$expression);
	$expression=str_replace("/","\/",$expression);
  return preg_match("/$expression/$modif",$texte);
}

?>