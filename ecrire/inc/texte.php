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

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/filtres');
include_spip('inc/lang');

//
// Gerer les variables de personnalisation, qui peuvent provenir
// des fichiers d'appel, en verifiant qu'elles n'ont pas ete passees
// par le visiteur (sinon, pas de cache)
//
// http://doc.spip.org/@tester_variable
function tester_variable($var, $val){
	if (!isset($GLOBALS[$var]))
		$GLOBALS[$var] = $val;

	if (
		isset($_REQUEST[$var])
		AND $GLOBALS[$var] == $_REQUEST[$var]
	)
		die ("tester_variable: $var interdite");
}

// on initialise la puce ici car il serait couteux de faire le find_in_path()
// a chaque hit, alors qu'on n'a besoin de cette valeur que lors du calcul
// http://doc.spip.org/@definir_puce
function definir_puce() {
	static $les_puces = array();

	// Attention au sens, qui n'est pas defini de la meme facon dans
	// l'espace prive (spip_lang est la langue de l'interface, lang_dir
	// celle du texte) et public (spip_lang est la langue du texte)

	$dir = _DIR_RESTREINT ? lang_dir() : lang_dir($GLOBALS['spip_lang']);
	$p = ($dir == 'rtl') ? 'puce_rtl' : 'puce';

	if (!isset($les_puces[$p])) {
		tester_variable($p, 'AUTO');
		if ($GLOBALS[$p] == 'AUTO') {
			$img = find_in_path($p.'.gif');
			list(,,,$size) = @getimagesize($img);
			$img = '<img src="'.$img.'" '
				.$size.' alt="-" />';
		} else
			$img = $GLOBALS[$p];

		$les_puces[$p] = $img;
	}

	return $les_puces[$p];
}

//
// Diverses fonctions essentielles
//


// XHTML - Preserver les balises-bloc : on liste ici tous les elements
// dont on souhaite qu'ils provoquent un saut de paragraphe
define('_BALISES_BLOCS',
	'div|pre|ul|ol|li|blockquote|h[1-6r]|'
	.'t(able|[rdh]|body|foot|extarea)|'
	.'form|object|center|marquee|address|'
	.'d[ltd]|script|noscript|map|button|fieldset');


// Ne pas afficher le chapo si article virtuel
// http://doc.spip.org/@nettoyer_chapo
function nettoyer_chapo($chapo){
	return (substr($chapo,0,1) == "=") ? '' : $chapo;
}


//
// Echapper les les elements perilleux en les passant en base64
//

// Creer un bloc base64 correspondant a $rempl ; au besoin en marquant
// une $source differente ; le script detecte automagiquement si ce qu'on
// echappe est un div ou un span
// http://doc.spip.org/@code_echappement
function code_echappement($rempl, $source='') {
	if (!strlen($rempl)) return '';

	// Tester si on echappe en span ou en div
	$mode = preg_match(',</?('._BALISES_BLOCS.')[>[:space:]],iS', $rempl) ?
		'div' : 'span';
	$nn = ($mode == 'div') ? "\n\n" : '';
	$return = '';

	// Decouper en morceaux, base64 a des probleme selon la taille de la pile
	$taille = 30000;
	for($i = 0; $i < strlen($rempl); $i += $taille) {
		// Convertir en base64
		$base64 = base64_encode(substr($rempl, $i, $taille));
		$return .=
			inserer_attribut("<$mode class=\"base64$source\">", 'title', $base64)
					                ."</$mode>";
	}

	return $return . $nn;
}

// Echapper les <html>...</ html>
// http://doc.spip.org/@traiter_echap_html_dist
function traiter_echap_html_dist($regs) {
	return $regs[3];
}

// Echapper les <code>...</ code>
// http://doc.spip.org/@traiter_echap_code_dist
function traiter_echap_code_dist($regs) {
	$echap = htmlspecialchars($regs[3]); // il ne faut pas passer dans entites_html, ne pas transformer les &#xxx; du code ! 

	// ne pas mettre le <div...> s'il n'y a qu'une ligne
	if (is_int(strpos($echap,"\n"))) {
		// supprimer les sauts de ligne debut/fin
		// (mais pas les espaces => ascii art).
		$echap = preg_replace("/^[\n\r]+|[\n\r]+$/s", "", $echap);
		$echap = nl2br($echap);
		$echap = "<div style='text-align: left;' "
		. "class='spip_code' dir='ltr'><code>"
		.$echap."</code></div>";
	} else {
		$echap = "<code class='spip_code' "
		."dir='ltr'>".$echap."</code>";
	}

	$echap = str_replace("\t",
		"&nbsp; &nbsp; &nbsp; &nbsp; ", $echap);
	$echap = str_replace("  ", " &nbsp;", $echap);
	return $echap;
}

// Echapper les <cadre>...</ cadre> aka <frame>...</ frame>
// http://doc.spip.org/@traiter_echap_cadre_dist
function traiter_echap_cadre_dist($regs) {
	$echap = trim(entites_html($regs[3]));
	$n = substr_count($echap, "\n") + 1;
	$echap = "\n<textarea readonly='readonly' cols='40' rows='$n' class='spip_cadre' dir='ltr'>$echap</textarea>";
	return generer_form_ecrire('', $echap, " method='get'");
}
// http://doc.spip.org/@traiter_echap_frame_dist
function traiter_echap_frame_dist($regs) {
	return traiter_echap_cadre_dist($regs);
}

// http://doc.spip.org/@traiter_echap_script_dist
function traiter_echap_script_dist($regs) {
	// rendre joli (et inactif) si c'est un script language=php
	if (preg_match(',<script\b[^>]+php,ims',
	$regs[0]))
		return highlight_string($regs[0],true);

	// Cas normal : le script passe tel quel
	return $regs[0];
}


// - pour $source voir commentaire infra (echappe_retour)
// - pour $no_transform voir le filtre post_autobr dans inc_filtres.php3
// http://doc.spip.org/@echappe_html
function echappe_html($letexte, $source='', $no_transform=false,
$preg='') {
	if (!strlen($letexte)) return '';

	if (!$preg) $preg = ',<(html|code|cadre|frame|script)'
			.'(\s[^>]*)?'
			.'>(.*)</\1>,UimsS';
	if (preg_match_all(
	$preg,
	$letexte, $matches, PREG_SET_ORDER))
	foreach ($matches as $regs) {
		// echappements tels quels ?
		if ($no_transform) {
			$echap = $regs[0];
		}

		// sinon les traiter selon le cas
		else if (function_exists($f = 'traiter_echap_'.strtolower($regs[1])))
			$echap = $f($regs);
		else if (function_exists($f = $f.'_dist'))
			$echap = $f($regs);

		$letexte = str_replace($regs[0],
			code_echappement($echap, $source),
			$letexte);
	}

	// Gestion du TeX
	if (strpos($letexte, "<math>") !== false) {
		include_spip('inc/math');
		$letexte = traiter_math($letexte, $source);
	}

	// Echapper le php pour faire joli (ici, c'est pas pour la securite)
	if (preg_match_all(
	',<[?].*($|[?]>),UisS',
	$letexte, $matches, PREG_SET_ORDER))
	foreach ($matches as $regs) {
		$letexte = str_replace($regs[0],
			code_echappement(highlight_string($regs[0],true), $source),
			$letexte);
	}

	return $letexte;
}

//
// Traitement final des echappements
// Rq: $source sert a faire des echappements "a soi" qui ne sont pas nettoyes
// par propre() : exemple dans ecrire/inc_articles_ortho.php, $source='ORTHO'
// ou encore dans typo()
// http://doc.spip.org/@echappe_retour
function echappe_retour($letexte, $source='', $filtre = "") {
	if (strpos($letexte,"base64$source")) {
		# spip_log(htmlspecialchars($letexte));  ## pour les curieux
		if (preg_match_all(
		',<(span|div) class=[\'"]base64'.$source.'[\'"]\s.*>\s*</\1>,UmsS',
		$letexte, $regs, PREG_SET_ORDER)) {
			foreach ($regs as $reg) {
				$rempl = base64_decode(extraire_attribut($reg[0], 'title'));
				if ($filtre) $rempl = $filtre($rempl);
				$letexte = str_replace($reg[0], $rempl, $letexte);
			}
		}
	}
	return $letexte;
}

// http://doc.spip.org/@nettoyer_raccourcis_typo
function nettoyer_raccourcis_typo($texte){
	$texte = pipeline('nettoyer_raccourcis_typo',$texte);
	// remplacer les liens
	if (preg_match_all(',[[]([^][]*)->(>?)([^][]*)[]],S', $texte, $regs, PREG_SET_ORDER))
		foreach ($regs as $reg) {
			$titre = supprimer_tags(traiter_raccourci_lien($reg));
			$texte = str_replace($reg[0], $titre, $texte);
		}

	// supprimer les notes
	$texte = preg_replace(",[[][[]([^]]|[]][^]])*[]][]],UimsS", "", $texte);

	// supprimer les codes typos
	$texte = str_replace(array('}','{'), '', $texte);

	// supprimer les tableaux
	$texte = preg_replace(",(^|\r)\|.*\|\r,s", "\r", $texte);
	return $texte;
}

// http://doc.spip.org/@couper
function couper($texte, $taille=50, $suite = '&nbsp;(...)') {
	if (!strlen($texte)) return '';
	$offset = 400 + 2*$taille;
	if (	$offset<strlen($texte)
			&& ($p_tag_ouvrant = strpos($texte,'<',$offset))!==NULL){
		$p_tag_fermant = strpos($texte,'>',$offset);
		if ($p_tag_fermant<$p_tag_ouvrant)
			$offset += $p_tag_fermant; // prolonger la coupe jusqu'au tag fermant suivant eventuel
	}
	$texte = substr($texte, 0, $offset); /* eviter de travailler sur 10ko pour extraire 150 caracteres */

	// on utilise les \r pour passer entre les gouttes
	$texte = str_replace("\r\n", "\n", $texte);
	$texte = str_replace("\r", "\n", $texte);

	// sauts de ligne et paragraphes
	$texte = preg_replace("/\n\n+/", "\r", $texte);
	$texte = preg_replace("/<(p|br)( [^>]*)?".">/", "\r", $texte);

	// supprimer les traits, lignes etc
	$texte = preg_replace("/(^|\r|\n)(-[-#\*]*|_ )/", "\r", $texte);

	// supprimer les tags
	$texte = supprimer_tags($texte);
	$texte = trim(str_replace("\n"," ", $texte));
	$texte .= "\n";	// marquer la fin

	// travailler en accents charset
	$texte = unicode2charset(html2unicode($texte, /* secure */ true));

	$texte = nettoyer_raccourcis_typo($texte);

	// corriger la longueur de coupe 
	// en fonction de la presence de caracteres utf
	if ($GLOBALS['meta']['charset']=='utf-8'){
		$long = charset2unicode($texte);
		$long = spip_substr($long, 0, max($taille,1));
		$nbcharutf = preg_match_all("/(&#[0-9]{3,5};)/S",$long,$matches);
		$taille += $nbcharutf;
	}


	// couper au mot precedent
	$long = spip_substr($texte, 0, max($taille-4,1));
	$court = preg_replace("/([^\s][\s]+)[^\s]*\n?$/", "\\1", $long);
	$points = $suite;

	// trop court ? ne pas faire de (...)
	if (spip_strlen($court) < max(0.75 * $taille,2)) {
		$points = '';
		$long = spip_substr($texte, 0, $taille);
		$texte = preg_replace("/([^\s][\s]+)[^\s]*\n?$/", "\\1", $long);
		// encore trop court ? couper au caractere
		if (spip_strlen($texte) < 0.75 * $taille)
			$texte = $long;
	} else
		$texte = $court;

	if (strpos($texte, "\n"))	// la fin est encore la : c'est qu'on n'a pas de texte de suite
		$points = '';

	// remettre les paragraphes
	$texte = preg_replace("/\r+/", "\n\n", $texte);

	// supprimer l'eventuelle entite finale mal coupee
	$texte = preg_replace('/&#?[a-z0-9]*$/S', '', $texte);

	return quote_amp(trim($texte)).$points;
}

// prendre <intro>...</intro> sinon couper a la longueur demandee
// http://doc.spip.org/@couper_intro
function couper_intro($texte, $long, $suite = '&nbsp;(...)') {
	$texte = extraire_multi(preg_replace(",(</?)intro>,i", "\\1intro>", $texte)); // minuscules
	$intro = '';
	while ($fin = strpos($texte, "</intro>")) {
		$zone = substr($texte, 0, $fin);
		$texte = substr($texte, $fin + strlen("</intro>"));
		if ($deb = strpos($zone, "<intro>") OR substr($zone, 0, 7) == "<intro>")
			$zone = substr($zone, $deb + 7);
		$intro .= $zone;
	}

	if ($intro)
		$intro .= $suite;
	else {
		$intro = preg_replace(',([|]\s*)+,S', '; ', couper($texte, $long, $suite));
	}

	// supprimer un eventuel chapo redirecteur =http:/.....
	return $intro;
}


//
// Les elements de propre()
//

/*
// Securite : empecher l'execution de code PHP ou javascript ou autre malice
// http://doc.spip.org/@interdire_scripts
function interdire_scripts($source) {
	$source = preg_replace(",<(\%|\?|/?[[:space:]]*(script|base)),imsS", "&lt;\\1", $source);
	return $source;
}
*/

// afficher joliment les <script>
// http://doc.spip.org/@echappe_js
function echappe_js($t,$class='') {
	if (preg_match_all(',<script.*?($|</script.),isS', $t, $r, PREG_SET_ORDER))
	foreach ($r as $regs)
		$t = str_replace($regs[0],
			"<code$class>".nl2br(htmlspecialchars($regs[0])).'</code>',
			$t);
	return $t;
}
// http://doc.spip.org/@protege_js_modeles
function protege_js_modeles($t) {
	if (isset($GLOBALS['auteur_session'])){
		if (!defined('_PROTEGE_JS_MODELES')){
			include_spip('inc/acces');
			define('_PROTEGE_JS_MODELES',creer_uniqid());
		}
		if (preg_match_all(',<script.*?($|</script.),isS', $t, $r, PREG_SET_ORDER))
			foreach ($r as $regs)
				$t = str_replace($regs[0],code_echappement($regs[0],'javascript'._PROTEGE_JS_MODELES),$t);
	}
	return $t;
}

// Securite : empecher l'execution de code PHP, en le transformant en joli code
// http://doc.spip.org/@interdire_scripts
function interdire_scripts($t) {

	// rien ?
	if (!$t OR !strstr($t, '<')) return $t;

	// echapper les tags asp/php
	$t = str_replace('<'.'%', '&lt;%', $t);

	// echapper le php
	$t = str_replace('<'.'?', '&lt;?', $t);

	// echapper le < script language=php >
	$t = preg_replace(',<(script\b[^>]+\blanguage\b[^\w>]+php\b),UimsS', '&lt;\1', $t);

	// Pour le js, trois modes : parano (-1), prive (0), ok (1)
	switch($GLOBALS['filtrer_javascript']) {
		case 0:
			if (!_DIR_RESTREINT)
				$t = echappe_js($t,' style="color:red"');
			break;
		case -1:
			$t = echappe_js($t);
			break;
	}

	// pas de <base href /> svp !
	$t = preg_replace(',<(base\s),iS', '&lt;\1', $t);

	return $t;
}

// Securite : utiliser SafeHTML s'il est present dans ecrire/safehtml/
// http://doc.spip.org/@safehtml
function safehtml($t) {
	static $safehtml;

	# attention safehtml nettoie deux ou trois caracteres de plus. A voir
	if (strpos($t,'<')===false)
		return str_replace("\x00", '', $t);

	$t = interdire_scripts($t); // jolifier le php
	$t = echappe_js($t);

	if (!isset($safehtml))
		$safehtml = charger_fonction('safehtml', 'inc');
	if ($safehtml)
		$t = $safehtml($t);

	return interdire_scripts($t); // interdire le php (2 precautions)
}

// Typographie generale
// avec protection prealable des balises HTML et SPIP

function typo($letexte, $echapper=true) {

	// Plus vite !
	if (!$letexte) return $letexte;

	// Echapper les codes <html> etc
	if ($echapper)
		$letexte = echappe_html($letexte, 'TYPO');

	//
	// Installer les modeles, notamment images et documents ;
	//
	// NOTE : dans propre() ceci s'execute avant les tableaux a cause du "|",
	// et apres les liens a cause du traitement de [<imgXX|right>->URL]
	$letexte = traiter_modeles($mem = $letexte, false, $echapper ? 'TYPO' : '');
	if ($letexte != $mem) $echapper = true;
	unset($mem);

	$letexte = corriger_typo($letexte);

	// reintegrer les echappements
	if ($echapper)
		$letexte = echappe_retour($letexte, 'TYPO');

	// l'espace prive securise ici
	$letexte = interdire_scripts($letexte);

	return $letexte;
}

// Correcteur typographique

function corriger_typo($letexte) {

	// Plus vite !
	if (!$letexte) return $letexte;

	$letexte = pipeline('pre_typo', $letexte);

	// Caracteres de controle "illegaux"
	$letexte = corriger_caracteres($letexte);

	// Charger & appliquer la fonction de typographie
	if ($typographie = charger_fonction(lang_typo(), 'typographie')) {

		// Proteger les caracteres typographiques a l'interieur des tags html
		$protege = "!':;?~%";
		$illegal = "\x1\x2\x3\x4\x5\x6\x7";
		if (preg_match_all(",</?[a-z!][^<>]*[!':;\?~%][^<>]*>,imsS",
		$letexte, $regs, PREG_SET_ORDER)) {
			foreach ($regs as $reg) {
				$insert = $reg[0];
				// hack: on transforme les caracteres a proteger en les remplacant
				// par des caracteres "illegaux". (cf corriger_caracteres())
				$insert = strtr($insert, $protege, $illegal);
				$letexte = str_replace($reg[0], $insert, $letexte);
			}
		}


		$letexte = $typographie($letexte);

		// Retablir les caracteres proteges
		$letexte = strtr($letexte, $illegal, $protege);

	}

	$letexte = pipeline('post_typo', $letexte);

	# un message pour abs_url - on est passe en mode texte
	$GLOBALS['mode_abs_url'] = 'texte';

	return $letexte;
}

//

// note: $echapper = false lorsqu'on appelle depuis propre() [pour accelerer]
//
// http://doc.spip.org/@typo

// analyse des raccourcis issus de [TITRE->RACCOURCInnn] et connexes

define('_RACCOURCI_URL', ',^\s*(\w*?)\s*(\d+)(\?(.*?))?(#([^\s]*))?\s*$,S');

// http://doc.spip.org/@typer_raccourci
function typer_raccourci ($lien) {
	if (!preg_match(_RACCOURCI_URL, $lien, $match)) return array();
	$f = $match[1];
	// valeur par defaut et alias historiques
	if (!$f) $f = 'article';
	else if ($f == 'art') $f = 'article';
	else if ($f == 'br') $f = 'breve';
	else if ($f == 'rub') $f = 'rubrique';
	else if ($f == 'aut') $f = 'auteur';
	else if ($f == 'doc' OR $f == 'im' OR $f == 'img' OR $f == 'image' OR $f == 'emb')
		$f = 'document';
	else if (preg_match(',^br..?ve$,S', $f)) $f = 'breve'; # accents :(
	$match[0] = $f;
	return $match;
}

// Cherche un lien du type [->raccourci 123]
// associe a une fonction generer_url_raccourci()
//
// Valeur retournee selon le parametre $pour:
// 'tout' : tableau [U,C,T,L] (vise <a href="U" class='C' hreflang='L'>T</a>)
// 'titre': seulement T ci-dessus (i.e. le TITRE ci-dessus ou dans table SQL)
// 'url':   seulement U  (i.e. generer_url_RACCOURCI)

// http://doc.spip.org/@calculer_url
function calculer_url ($lien, $texte='', $pour='url') {
	$lien = vider_url($lien); # 'http://' ou 'mailto:' seuls == ""
	if ($match = typer_raccourci ($lien)) {
		@list($f,,$id,,$param,,$ancre) = $match;
		charger_generer_url();
		$g = 'generer_url_' . $f;
		if (function_exists($g) OR function_exists($g .= '_dist')) {
			$res = $g($id, $param, $ancre);
			if ($pour == 'url') return $res;
			$g = 'calculer_url_' . $f;
			if (function_exists($g) OR function_exists($g .= '_dist')) { 
				if ($pour == 'tout')
					return $g($id, $texte, $res);
				$res = $g($id, $texte, $res);
				return $res[2];
			}
		} else spip_log("raccourci indefini $f");
	}
  
	$lien = ltrim($lien);
	if ($lien[0] == '?') {
		if ($pour == 'titre') return $texte;
		$lien = entites_html(substr($lien, 1));
		return ($pour == 'url') ? $lien :
			array($lien, 'spip_glossaire', $texte);
	}
	// Liens explicites
	if (!$texte) {
		$texte = str_replace('"', '', $lien);
		if (strlen($texte)>40)
				$texte = substr($texte,0,35).'...';
		$texte = "<html>$texte</html>";
		$class = "spip_url spip_out";
	} else 	$class = "spip_out";

	if ($pour == 'titre') return $texte;

	$lien = vider_url($lien);

	// petites corrections d'URL
	if (preg_match(",^www\.[^@]+$,S",$lien))
		$lien = "http://".$lien;
	else if (strpos($lien, "@") && email_valide($lien))
		$lien = "mailto:".$lien;

	// class spip_ancre sur les ancres pures (internes a la page)
	if (substr($lien,0,1) == '#')
		$class = 'spip_ancre';

	return ($pour == 'url') ? $lien : array($lien, $class, $texte, '');
}

// http://doc.spip.org/@calculer_url_article_dist
function calculer_url_article_dist($id, $texte='', $lien='') {
	$s = spip_query("SELECT titre,lang FROM spip_articles WHERE id_article=$id");
	$row = sql_fetch($s);
	if (!trim($texte))
		$texte = supprimer_numero($row['titre']);
	if (!trim($texte))
	  $texte = _T('article') . $id;
	return array($lien, 'spip_in', $texte, $row['lang']);
}

// http://doc.spip.org/@calculer_url_rubrique_dist
function calculer_url_rubrique_dist($id, $texte='', $lien='')
{
	$s = spip_query("SELECT titre,lang FROM spip_rubriques WHERE id_rubrique=$id");
	$row = sql_fetch($s);
	if (!trim($texte)) {
		$texte = supprimer_numero($row['titre']);
		if (!trim($texte))
		  $texte = $id;
	}
	return array($lien, 'spip_in', $texte, $row['lang']);
}

// http://doc.spip.org/@calculer_url_breve_dist
 function calculer_url_breve_dist($id, $texte='', $lien='')
{
	$s = spip_query("SELECT titre,lang FROM spip_breves WHERE id_breve=$id");
	$row = sql_fetch($s);
	if (!trim($texte)) {
		$texte = supprimer_numero($row['titre']);
		if (!trim($texte))
		  $texte = $id;
	}
	return array($lien, 'spip_in', $texte, $row['lang']);
}

// http://doc.spip.org/@calculer_url_auteur_dist
 function calculer_url_auteur_dist($id, $texte='', $lien='')
{
	if ($texte=='') {
		$s = spip_query("SELECT nom FROM spip_auteurs WHERE id_auteur=$id");
		$row = sql_fetch($s);
		$texte = $row['nom'];
	}
	return array($lien, 'spip_in', $texte); # pas de hreflang
}

// http://doc.spip.org/@calculer_url_mot_dist
function calculer_url_mot_dist($id, $texte='', $lien='')
{
	if (!trim($texte)) {
		$s = spip_query("SELECT titre FROM spip_mots WHERE id_mot=$id");
		$row = sql_fetch($s);
		$texte = supprimer_numero($row['titre']);
		if (!trim($texte))
		  $texte = $id;
	}
	return array($lien, 'spip_in', $texte);
}

// http://doc.spip.org/@calculer_url_document_dist
 function calculer_url_document_dist($id, $texte='', $lien='')
{
	if ($texte=='') {
		$s = spip_query("SELECT titre,fichier FROM spip_documents WHERE id_document=$id");
		$row = sql_fetch($s);
		$texte = $row['titre'];
		if (!trim($texte))
			$texte = preg_replace(",^.*/,","",$row['fichier']);
		if (!trim($texte))
		    $texte = $id;
	}
	return array($lien, 'spip_in', $texte); # pas de hreflang
}

// http://doc.spip.org/@calculer_url_site_dist
function calculer_url_site_dist($id, $texte='', $lien='')
{
	# attention dans le cas des sites le lien pointe non pas sur
	# la page locale du site, mais directement sur le site lui-meme
	$s = spip_query("SELECT nom_site,url_site FROM spip_syndic WHERE id_syndic=$id");
	$row = sql_fetch($s);
	if ($row) {
		$lien = $row['url_site'];
		if (!trim($texte))
			$texte = supprimer_numero($row['nom_site']);
		if (!trim($texte))
		    $texte = $id;
	}
	return array($lien, 'spip_out', $texte, $row['lang']);
}

// http://doc.spip.org/@calculer_url_forum_dist
function calculer_url_forum_dist($id, $texte='', $lien='')
{
	if (!trim($texte)) {
		$s = spip_query("SELECT titre FROM spip_forum WHERE id_forum=$id AND statut='publie'");
		$row = sql_fetch($s);
		$texte = $row['titre'];
		if (!trim($texte))
		    $texte = $id;
	}
	return array($lien, 'spip_in', $texte); # pas de hreflang
}

//
// Tableaux
//
// http://doc.spip.org/@traiter_tableau
function traiter_tableau($bloc) {

	// Decouper le tableau en lignes
	preg_match_all(',([|].*)[|]\n,UmsS', $bloc, $regs, PREG_PATTERN_ORDER);
	$lignes = array();
	$debut_table = $summary = '';
	$l = 0;

	// Traiter chaque ligne
	foreach ($regs[1] as $ligne) {
		$l ++;

		// Gestion de la premiere ligne :
		if ($l == 1) {
		// - <caption> et summary dans la premiere ligne :
		//   || caption | summary || (|summary est optionnel)
			if (preg_match(',^\|\|([^|]*)(\|(.*))?\|$,sS', $ligne, $cap)) {
				$l = 0;
				if ($caption = trim($cap[1]))
					$debut_table .= "<caption>".$caption."</caption>\n";
				$summary = ' summary="'.entites_html(trim($cap[3])).'"';
			}
		// - <thead> sous la forme |{{titre}}|{{titre}}|
		//   Attention thead oblige a avoir tbody
			else if (preg_match(',^(\|([[:space:]]*(:?{{[^}]+}}[[:space:]]*)?|<))+$,sS',
				$ligne, $thead)) {
			  	preg_match_all("/\|([^|]*)/S", $ligne, $cols);
				$ligne='';$cols= $cols[1];
				$colspan=1;
				for($c=count($cols)-1; $c>=0; $c--) {
					$attr='';
					if($cols[$c]=='<') {
					  $colspan++;
					} else {
					  if($colspan>1) {
						$attr= " colspan='$colspan'";
						$colspan=1;
					  }
					  $ligne= "<th scope='col'$attr>$cols[$c]</th>$ligne";
					}
				}

				$debut_table .= "<thead><tr class='row_first'>".
					$ligne."</tr></thead>\n";
				$l = 0;
			}
		}

		// Sinon ligne normale
		if ($l) {
			// Gerer les listes a puce dans les cellules
			if (strpos($ligne,"\n-*")!==false OR strpos($ligne,"\n-#")!==false)
				$ligne = traiter_listes($ligne);

			// Pas de paragraphes dans les cellules
			$ligne = preg_replace("/\n{2,}/", "<br />\n", $ligne);

			// tout mettre dans un tableau 2d
			preg_match_all("/\|([^|]*)/S", $ligne, $cols);
			$lignes[]= $cols[1];
		}
	}

	// maintenant qu'on a toutes les cellules
	// on prepare une liste de rowspan par defaut, a partir
	// du nombre de colonnes dans la premiere ligne
	$rowspans = array();
	for ($i=0; $i<count($lignes[0]); $i++)
		$rowspans[] = 1;

	// et on parcourt le tableau a l'envers pour ramasser les
	// colspan et rowspan en passant
	$html = '';
	for($l=count($lignes)-1; $l>=0; $l--) {
		$cols= $lignes[$l];
		$colspan=1;
		$ligne='';

		for($c=count($cols)-1; $c>=0; $c--) {
			$attr='';
			if($cols[$c]=='<') {
			  $colspan++;

			} elseif($cols[$c]=='^') {
			  $rowspans[$c]++;

			} else {
			  if($colspan>1) {
				$attr.= " colspan='$colspan'";
				$colspan=1;
			  }
			  if($rowspans[$c]>1) {
				$attr.= " rowspan='$rowspans[$c]'";
				$rowspans[$c]=1;
			  }
			  $ligne= '<td'.$attr.'>'.$cols[$c].'</td>'.$ligne;
			}
		}

		// ligne complete
		$class = 'row_'.alterner($l+1, 'even', 'odd');
		$html = "<tr class=\"$class\">" . $ligne . "</tr>\n".$html;
	}

	return "\n\n<table".$GLOBALS['class_spip_plus'].$summary.">\n"
		. $debut_table
		. "<tbody>\n"
		. $html
		. "</tbody>\n"
		. "</table>\n\n";
}


//
// Traitement des listes (merci a Michael Parienti)
//
// http://doc.spip.org/@traiter_listes
function traiter_listes ($texte) {
	global $class_spip, $class_spip_plus;
	$parags = preg_split(",\n[[:space:]]*\n,S", $texte);
	$texte ='';

	// chaque paragraphe est traite a part
	while (list(,$para) = each($parags)) {
		$niveau = 0;
		$lignes = explode("\n-", "\n" . $para);

		// ne pas toucher a la premiere ligne
		list(,$debut) = each($lignes);
		$texte .= $debut;

		// chaque item a sa profondeur = nb d'etoiles
		$type ='';
		while (list(,$item) = each($lignes)) {
			preg_match(",^([*]*|[#]*)([^*#].*)$,sS", $item, $regs);
			$profond = strlen($regs[1]);

			if ($profond > 0) {
				$ajout='';

				// changement de type de liste au meme niveau : il faut
				// descendre un niveau plus bas, fermer ce niveau, et
				// remonter
				$nouv_type = (substr($item,0,1) == '*') ? 'ul' : 'ol';
				$change_type = ($type AND ($type <> $nouv_type) AND ($profond == $niveau)) ? 1 : 0;
				$type = $nouv_type;

				// d'abord traiter les descentes
				while ($niveau > $profond - $change_type) {
					$ajout .= $pile_li[$niveau];
					$ajout .= $pile_type[$niveau];
					if (!$change_type)
						unset ($pile_li[$niveau]);
					$niveau --;
				}

				// puis les identites (y compris en fin de descente)
				if ($niveau == $profond && !$change_type) {
					$ajout .= $pile_li[$niveau];
				}

				// puis les montees (y compris apres une descente un cran trop bas)
				while ($niveau < $profond) {
					if ($niveau == 0) $ajout .= "\n\n";
					$niveau ++;
					$ajout .= "<$type$class_spip_plus>";
					$pile_type[$niveau] = "</$type>";
				}

				$ajout .= "<li$class_spip>";
				$pile_li[$profond] = "</li>";
			}
			else {
				$ajout = "\n-";	// puce normale ou <hr>
			}

			$texte .= $ajout . $regs[2];
		}

		// retour sur terre
		$ajout = '';
		while ($niveau > 0) {
			$ajout .= $pile_li[$niveau];
			$ajout .= $pile_type[$niveau];
			$niveau --;
		}
		$texte .= $ajout;

		// paragraphe
		$texte .= "\n\n";
	}

	// sucrer les deux derniers \n
	return substr($texte, 0, -2);
}


// fonction en cas de texte extrait d'un serveur distant:
// on ne sait pas (encore) rapatrier les documents joints
// TODO: gerer les modeles ?
// http://doc.spip.org/@supprime_img
function supprime_img($letexte) {
	$message = _T('img_indisponible');
	return preg_replace(',<(img|doc|emb)([0-9]+)(\|([^>]*))?'.'\s*/?'.'>,i',
		"($message)", $letexte);
}

// traite les modeles (dans la fonction typo), en remplacant
// le raccourci <modeleN|parametres> par la page calculee a
// partir du squelette modeles/modele.html
// Le nom du modele doit faire au moins trois caracteres (evite <h2>)
// Si $doublons==true, on repere les documents sans calculer les modeles
// mais on renvoie les params (pour l'indexation par le moteur de recherche)
// http://doc.spip.org/@traiter_modeles

define('_RACCOURCI_MODELE', 
	 '(<([a-z_-]{3,})' # <modele
	.'\s*([0-9]*)\s*' # id
	.'([|](?:<[^<>]*>|[^>])*?)?' # |arguments (y compris des tags <...>)
	.'\s*/?'.'>)' # fin du modele >
	.'\s*(<\/a>)?' # eventuel </a>
       );

define('_RACCOURCI_MODELE_DEBUT', '@^' . _RACCOURCI_MODELE .'@is');

// http://doc.spip.org/@traiter_modeles
function traiter_modeles($texte, $doublons=false, $echap='', $connect='') {
	// detecter les modeles (rapide)
	if (preg_match_all('/<[a-z_-]{3,}\s*[0-9|]+/iS',
	$texte, $matches, PREG_SET_ORDER)) {
		include_spip('public/assembler');
		foreach ($matches as $match) {
			// Recuperer l'appel complet (y compris un eventuel lien)
			// $regs : 1 => modele, 2 => type, 3 => id, 4 => params, 5 => a
			$a = strpos($texte,$match[0]);
			preg_match(_RACCOURCI_MODELE_DEBUT, substr($texte, $a), $regs);

			if ($regs[5] AND preg_match(
			',<a\s[^<>]*>\s*$,i', substr($texte, 0, $a), $r)) {
				$lien = array(
					extraire_attribut($r[0],'href'),
					extraire_attribut($r[0],'class')
				);
				$a -= strlen($r[0]);
				$cherche = $r[0].$regs[0];
			} else {
				$lien = false;
				$cherche = $regs[1];
			}

			// calculer le modele
			# hack articles_edit, breves_edit, indexation
			if ($doublons)
				$texte .= preg_replace(',[|][^|=]*,s',' ',$regs[4]);
			# version normale
			else {
			  $modele = inclure_modele($regs[2], $regs[3], $regs[4], $lien, $connect);

				// le remplacer dans le texte
				if ($modele !== false) {
					$modele = protege_js_modeles($modele);
					$rempl = code_echappement($modele, $echap);
					$texte = substr($texte, 0, $a)
						. $rempl
						. substr($texte, $a+strlen($cherche));
				}
			}

			// hack pour tout l'espace prive
			if (((!_DIR_RESTREINT) OR ($doublons)) AND ($regs[3]) AND (in_array($regs[2],array('doc','emb','img'))))
				$GLOBALS['doublons_documents_inclus'][] = $regs[3];
		}
	}

	return $texte;
}


//
// Une fonction pour fermer les paragraphes ; on essaie de preserver
// des paragraphes indiques a la main dans le texte
// (par ex: on ne modifie pas un <p align='center'>)
//
// deuxieme argument : forcer les <p> meme pour un seul paragraphe
//
// http://doc.spip.org/@paragrapher
function paragrapher($letexte, $forcer=true) {
	global $class_spip;

	$letexte = trim($letexte);
	if (!strlen($letexte))
		return '';

	if ($forcer OR (
	strstr($letexte,'<') AND preg_match(',<p\b,iS',$letexte)
	)) {

		// Ajouter un espace aux <p> et un "STOP P"
		// transformer aussi les </p> existants en <p>, nettoyes ensuite
		$letexte = preg_replace(',</?p(\s([^>]*))?'.'>,iS', '<STOP P><p \2>',
			'<p>'.$letexte.'<STOP P>');

		// Fermer les paragraphes (y compris sur "STOP P")
		$letexte = preg_replace(
			',(<p\s.*)(</?(STOP P|'._BALISES_BLOCS.')[>[:space:]]),UimsS',
			"\n\\1</p>\n\\2", $letexte);

		// Supprimer les marqueurs "STOP P"
		$letexte = str_replace('<STOP P>', '', $letexte);

		// Reduire les blancs dans les <p>
		// Do not delete multibyte utf character just before </p> having last byte equal to whitespace  
		$u = ($GLOBALS['meta']['charset']=='utf-8' && test_pcre_unicode()) ? 'u':'S';
		$letexte = preg_replace(
		',(<p(>|\s[^>]*)>)\s*|\s*(</p[>[:space:]]),'.$u.'i', '\1\3',
			$letexte);

		// Supprimer les <p xx></p> vides
		$letexte = preg_replace(',<p\s[^>]*></p>\s*,iS', '',
			$letexte);

		// Renommer les paragraphes normaux
		$letexte = str_replace('<p >', "<p$class_spip>",
			$letexte);

	}

	return $letexte;
}

//
// Inserer un lien a partir du preg_match du raccourci [xx->url]
// $regs:
// 0=>tout le raccourci
// 1=>texte (ou texte|hreflang ou texte|bulle ou texte|bulle{hreflang})
// 2=>double fleche (historiquement, liens ouvrants)
// 3=>url
//
// http://doc.spip.org/@traiter_raccourci_lien
function traiter_raccourci_lien($regs) {
	$bulle = $hlang = '';
	// title et hreflang donnes par le raccourci ?
	if (preg_match(',^(.*?)([|]([^<>]*?))?([{]([a-z_]+)[}])?$,', $regs[1], $m)) {
		$n =count($m);
		// |infobulle ?
		if ($n > 2) {
			$bulle = ' title="'.texte_backend($m[3]).'"';
			// {hreflang} ?
			if ($n > 4) {
			// si c'est un code de langue connu, on met un hreflang
				if (traduire_nom_langue($m[5]) <> $m[5]) {
					$hlang = $m[5];
				}
			// sinon c'est un italique
				else {
					$m[1] .= $m[4];
				}
			
	// S'il n'y a pas de hreflang sous la forme {}, ce qui suit le |
	// est peut-etre une langue
			} else if (preg_match(',^[a-z_]+$,', $m[3])) {
			// si c'est un code de langue connu, on met un hreflang
			// mais on laisse le title (c'est arbitraire tout ca...)
				if (traduire_nom_langue($m[3]) <> $m[3]) {
				  $hlang = $m[3];
				}
			}
		}
		$regs[1] = $m[1];
	}

	list ($lien, $class, $texte, $lang) = calculer_url($regs[3], $regs[1], 'tout');

	// Si l'objet n'est pas de la langue courante, on ajoute hreflang
	if (!$hlang AND $lang AND ($lang!=$GLOBALS['spip_lang'])) $hlang=$lang;
	$hreflang = $hlang ? ' hreflang="'.$hlang.'"' : '';

	# ici bien passer le lien pour traiter [<doc3>->url]
	return typo("<a href=\"$lien\" class=\"$class\"$hreflang$bulle>"
		. $texte
		. "</a>");
}

// Fonction pour les champs chapo commencant par =,  redirection qui peut etre:
// 1. un raccourci Spip habituel (premier If) [texte->TYPEnnn]
// 2. un ultra raccourci TYPEnnn voire nnn (article) (deuxieme If)
// 3. une URL std
// renvoie une tableau structure comme ci-dessus mais sans calcul d'URL
// (cf fusion de sauvegardes)

define('_RACCOURCI_CHAPO', ',^(\W*)(\W*)(\w*\d+([?#].*)?)$,');

// http://doc.spip.org/@chapo_redirige
function chapo_redirige($chapo)
{
	if (!preg_match(_RACCOURCI_LIEN, $chapo, $m))
		if (!preg_match(_RACCOURCI_CHAPO, $chapo, $m))
			$m = array('','','',$chapo);
	return $m;
}

// Regexp des raccouris, aussi utilisee pour la fusion de sauvegarde Spip
define('_RACCOURCI_LIEN', ",\[([^][]*)->(>?)([^]]*)\],msS");

// Nettoie un texte, traite les raccourcis spip, la typo, etc.
// http://doc.spip.org/@traiter_raccourcis
function traiter_raccourcis($letexte) {
	global $debut_intertitre, $fin_intertitre, $ligne_horizontale, $url_glossaire_externe;
	global $debut_italique, $fin_italique;
	global $debut_gras, $fin_gras;
	global $class_spip, $class_spip_plus;
	global $compt_note;
	global $marqueur_notes;
	global $ouvre_ref;
	global $ferme_ref;
	global $ouvre_note;
	global $ferme_note;
	static $notes_vues;
	static $tester_variables=0;

	// Appeler les fonctions de pre_traitement
	$letexte = pipeline('pre_propre', $letexte);
	// old style
	if (function_exists('avant_propre'))
		$letexte = avant_propre($letexte);

	// Verifier les variables de personnalisation
	if (!$tester_variables++) {
		// class_spip : savoir si on veut class="spip" sur p i strong & li
		// class_spip_plus : class="spip" sur les ul ol h3 hr quote table...
		// la difference c'est que des css specifiques existent pour les seconds
		tester_variable('class_spip', '');  /*' class="spip"'*/
		tester_variable('class_spip_plus', ' class="spip"');
		tester_variable('debut_italique', "<i$class_spip>");
		tester_variable('fin_italique', '</i>');
		tester_variable('debut_gras', "<strong$class_spip>");
		tester_variable('fin_gras', '</strong>');
		tester_variable('debut_intertitre', "\n<h3$class_spip_plus>");
		tester_variable('fin_intertitre', "</h3>\n");
		tester_variable('ligne_horizontale', "\n<hr$class_spip_plus />\n");
		tester_variable('ouvre_ref', '&nbsp;[');
		tester_variable('ferme_ref', ']');
		tester_variable('ouvre_note', '[');
		tester_variable('ferme_note', '] ');
		tester_variable('url_glossaire_externe',
			"http://@lang@.wikipedia.org/wiki/");
		tester_variable('les_notes', '');
		tester_variable('compt_note', 0);
		tester_variable('toujours_paragrapher', true);
	}

	// Gestion de la <poesie>
	if (preg_match_all(",<(poesie|poetry)>(.*)<\/(poesie|poetry)>,UimsS",
	$letexte, $regs, PREG_SET_ORDER)) {
		foreach ($regs as $reg) {
			$lecode = preg_replace(",\r\n?,S", "\n", $reg[2]);
			$lecode = preg_replace("/\n[\s]*\n/", "\n&nbsp;\n",$lecode);
			$lecode = "<blockquote class=\"spip_poesie\">\n<div>".preg_replace("/\n+/", "</div>\n<div>", trim($lecode))."</div>\n</blockquote>\n\n";
			$letexte = str_replace($reg[0], $lecode, $letexte);
		}
	}

	// Harmoniser les retours chariot
	$letexte = preg_replace(",\r\n?,S", "\n", $letexte);

	// Recuperer les para HTML
	$letexte = preg_replace(",<p[>[:space:]],iS", "\n\n\\0", $letexte);
	$letexte = preg_replace(",</p[>[:space:]],iS", "\\0\n\n", $letexte);

	//
	// Notes de bas de page
	//
	$mes_notes = '';
	$regexp = ', *\[\[(.*?)\]\],msS';
	if (preg_match_all($regexp, $letexte, $matches, PREG_SET_ORDER))
	foreach ($matches as $regs) {
		$note_source = $regs[0];
		$note_texte = $regs[1];
		$num_note = false;

		// note auto ou pas ?
		if (preg_match(",^ *<([^>]*)>,", $note_texte, $regs)){
			$num_note = $regs[1];
			$note_texte = str_replace($regs[0], "", $note_texte);
		} else {
			$compt_note++;
			$num_note = $compt_note;
		}

		// preparer la note
		if ($num_note) {
			if ($marqueur_notes) // quand il y a plusieurs series
								 // de notes sur une meme page
				$mn = $marqueur_notes.'-';
			$ancre = $mn.rawurlencode($num_note);

			// ne mettre qu'une ancre par appel de note (XHTML)
			if (!$notes_vues[$ancre]++)
				$name_id = " name=\"nh$ancre\" id=\"nh$ancre\"";
			else
				$name_id = "";

			$lien = "<a href=\"#nb$ancre\"$name_id class=\"spip_note\" rel=\"footnote\">";

			// creer le popup 'title' sur l'appel de note
			if ($title = supprimer_tags(propre($note_texte))) {
				$title = $ouvre_note.$num_note.$ferme_note.$title;
				$title = couper($title,80);
				$lien = inserer_attribut($lien, 'title', $title);
			}

			$insert = "$ouvre_ref$lien$num_note</a>$ferme_ref";

			// on l'echappe
			$insert = code_echappement($insert);

			$appel = "$ouvre_note<a href=\"#nh$ancre\" name=\"nb$ancre\" class=\"spip_note\" title=\"" . _T('info_notes') . " $ancre\" rev=\"footnote\">$num_note</a>$ferme_note";
		} else {
			$insert = '';
			$appel = '';
		}

		// l'ajouter "tel quel" (echappe) dans les notes
		if ($note_texte) {
			if ($mes_notes)
				$mes_notes .= "\n\n";
			$mes_notes .= code_echappement($appel) . $note_texte;
		}

		// dans le texte, mettre l'appel de note a la place de la note
		$pos = strpos($letexte, $note_source);
		$letexte = substr($letexte, 0, $pos) . $insert
			. substr($letexte, $pos + strlen($note_source));
	}

	//
	// Raccourcis automatiques [?SPIP] vers un glossaire
	// (on traite ce raccourci en deux temps afin de ne pas appliquer
	//  la typo sur les URLs, voir raccourcis liens ci-dessous)
	//
	if ($url_glossaire_externe) {
		$regexp = "|\[\?+([^][<>]+)\]|S";
		if (preg_match_all($regexp, $letexte, $matches, PREG_SET_ORDER))
		foreach ($matches as $regs) {
			$terme = trim($regs[1]);
			$terme_underscore = preg_replace(',\s+,', '_', $terme);
			// faire sauter l'eventuelle partie "|bulle d'aide" du lien
			// cf. http://fr.wikipedia.org/wiki/Wikip%C3%A9dia:Conventions_sur_les_titres
			$terme_underscore = preg_replace(',[|].*,', '', $terme_underscore);
			if (strstr($url_glossaire_externe,"%s"))
				$url = str_replace("%s", rawurlencode($terme_underscore),
					$url_glossaire_externe);
			else
				$url = $url_glossaire_externe.$terme_underscore;
			$url = str_replace("@lang@", $GLOBALS['spip_lang'], $url);
			$code = '['.$terme.'->?'.$url.']';

			// Eviter les cas particulier genre "[?!?]"
			if (preg_match(',[a-z],i', $terme))
				$letexte = str_replace($regs[0], $code, $letexte);
		}
	}


	//
	// Raccourcis ancre [#ancre<-]
	//
	$regexp = "|\[#?([^][]*)<-\]|S";
	if (preg_match_all($regexp, $letexte, $matches, PREG_SET_ORDER))
	foreach ($matches as $regs)
		$letexte = str_replace($regs[0],
		'<a name="'.entites_html($regs[1]).'"></a>', $letexte);

	//
	// Raccourcis liens [xxx->url]
	// Note : complique car c'est ici qu'on applique typo(),
	// et en plus on veut pouvoir les passer en pipeline
	//

	$inserts = array();
	if (preg_match_all(_RACCOURCI_LIEN, $letexte, $matches, PREG_SET_ORDER)) {
		$i = 0;
		foreach ($matches as $regs) {
			$inserts[++$i] = traiter_raccourci_lien($regs);
			$letexte = str_replace($regs[0], "@@SPIP_ECHAPPE_LIEN_$i@@",
				$letexte);
		}
	}

	$letexte = typo($letexte, /* echap deja fait, accelerer */ false);

	foreach ($inserts as $i => $insert) {
		$letexte = str_replace("@@SPIP_ECHAPPE_LIEN_$i@@", $insert, $letexte);
	}


	//
	// Tableaux
	//

	// ne pas oublier les tableaux au debut ou a la fin du texte
	$letexte = preg_replace(",^\n?[|],S", "\n\n|", $letexte);
	$letexte = preg_replace(",\n\n+[|],S", "\n\n\n\n|", $letexte);
	$letexte = preg_replace(",[|](\n\n+|\n?$),S", "|\n\n\n\n", $letexte);

	// traiter chaque tableau
	if (preg_match_all(',[^|](\n[|].*[|]\n)[^|],UmsS', $letexte,
	$regs, PREG_SET_ORDER))
	foreach ($regs as $tab) {
		$letexte = str_replace($tab[1], traiter_tableau($tab[1]), $letexte);
	}

	//
	// Ensemble de remplacements implementant le systeme de mise
	// en forme (paragraphes, raccourcis...)
	//

	$letexte = "\n".trim($letexte);

	// les listes
	if (strpos($letexte,"\n-*")!==false OR strpos($letexte,"\n-#")!==false)
		$letexte = traiter_listes($letexte);

	// Puce
	if (strpos($letexte, "\n- ") !== false)
		$puce = definir_puce();
	else $puce = '';

	// Proteger les caracteres actifs a l'interieur des tags html
	$protege = "{}_-";
	$illegal = "\x1\x2\x3\x4";
	if (preg_match_all(",</?[a-z!][^<>]*[".preg_quote($protege)."][^<>]*>,imsS",
	$letexte, $regs, PREG_SET_ORDER)) {
		foreach ($regs as $reg) {
			$insert = $reg[0];
			// hack: on transforme les caracteres a proteger en les remplacant
			// par des caracteres "illegaux". (cf corriger_caracteres())
			$insert = strtr($insert, $protege, $illegal);
			$letexte = str_replace($reg[0], $insert, $letexte);
		}
	}

	// autres raccourcis
	$cherche1 = array(
		/* 0 */ 	"/\n(----+|____+)/S",
		/* 1 */ 	"/\n-- */S",
		/* 2 */ 	"/\n- */S",
		/* 3 */ 	"/\n_ +/S",
		/* 4 */   "/(^|[^{])[{][{][{]/S",
		/* 5 */   "/[}][}][}]($|[^}])/S",
		/* 6 */ 	"/(( *)\n){2,}(<br\s*\/?".">)?/S",
		/* 7 */ 	"/[{][{]/S",
		/* 8 */ 	"/[}][}]/S",
		/* 9 */ 	"/[{]/S",
		/* 10 */	"/[}]/S",
		/* 11 */	"/(?:<br\s*\/?".">){2,}/S",
		/* 12 */	"/<p>\n*(?:<br\s*\/?".">\n*)*/S",
		/* 13 */	"/<quote>/S",
		/* 14 */	"/<\/quote>/S",
		/* 15 */	"/<\/?intro>/S"
	);
	$remplace1 = array(
		/* 0 */ 	"\n\n$ligne_horizontale\n\n",
		/* 1 */ 	"\n<br />&mdash;&nbsp;",
		/* 2 */ 	"\n<br />$puce&nbsp;",
		/* 3 */ 	"\n<br />",
		/* 4 */ 	"\$1\n\n$debut_intertitre",
		/* 5 */ 	"$fin_intertitre\n\n\$1",
		/* 6 */ 	"<p>",
		/* 7 */ 	$debut_gras,
		/* 8 */ 	$fin_gras,
		/* 9 */ 	$debut_italique,
		/* 10 */	$fin_italique,
		/* 11 */	"<p>",
		/* 12 */	"<p>",
		/* 13 */	"<blockquote$class_spip_plus><p>",
		/* 14 */	"</blockquote><p>",
		/* 15 */	""
	);
	$letexte = preg_replace($cherche1, $remplace1, $letexte);
	$letexte = preg_replace("@^ <br />@S", "", $letexte);

	// Retablir les caracteres proteges
	$letexte = strtr($letexte, $illegal, $protege);

	// Fermer les paragraphes ; mais ne pas en creer si un seul
	$letexte = paragrapher($letexte, $GLOBALS['toujours_paragrapher']);

	// Appeler les fonctions de post-traitement
	$letexte = pipeline('post_propre', $letexte);
	// old style
	if (function_exists('apres_propre'))
		$letexte = apres_propre($letexte);

	if ($mes_notes) traiter_les_notes($mes_notes);

	return $letexte;
}

// http://doc.spip.org/@traiter_les_notes
function traiter_les_notes($mes_notes) {
	$mes_notes = propre('<p>'.$mes_notes);

	if ($GLOBALS['class_spip'])
		$mes_notes = str_replace('<p class="spip">', '<p class="spip_note">', $mes_notes);

	$GLOBALS['les_notes'] .= $mes_notes;
}


// Filtre a appliquer aux champs du type #TEXTE*
// http://doc.spip.org/@propre
function propre($letexte) {
	if (!$letexte) return $letexte;

	// Echapper les <a href>, <html>...< /html>, <code>...< /code>
	$letexte = echappe_html($letexte);

	// Traiter le texte
	$letexte = traiter_raccourcis($letexte);

	// Reinserer les echappements
	$letexte = echappe_retour($letexte);

	// Vider les espaces superflus
	$letexte = trim($letexte);

	// Dans l'espace prive, securiser ici
	if (!_DIR_RESTREINT)
		$letexte = interdire_scripts($letexte);

	// Reinserer le javascript de confiance (venant des modeles)
	if (defined('_PROTEGE_JS_MODELES'))
		$letexte = echappe_retour($letexte,"javascript"._PROTEGE_JS_MODELES);

	return $letexte;
}

?>
