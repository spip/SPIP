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


//
if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/filtres');
include_spip('inc/charsets');
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
	#include_spip('inc/lang');
	$dir = _DIR_RESTREINT ?
		lang_dir($GLOBALS['spip_lang']) : $GLOBALS['lang_dir'];
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

	// Convertir en base64
	$base64 = base64_encode($rempl);

	// Tester si on echappe en span ou en div
	$mode = preg_match(',</?('._BALISES_BLOCS.')[>[:space:]],iS', $rempl) ?
		'div' : 'span';
	$nn = ($mode == 'div') ? "\n\n" : '';

	return
		inserer_attribut("<$mode class=\"base64$source\">", 'title', $base64)
		."</$mode>$nn";
}

// Echapper les <html>...</ html>
// http://doc.spip.org/@traiter_echap_html_dist
function traiter_echap_html_dist($regs) {
	return $regs[3];
}

// Echapper les <code>...</ code>
// http://doc.spip.org/@traiter_echap_code_dist
function traiter_echap_code_dist($regs) {
	$echap = entites_html($regs[3]);
	// supprimer les sauts de ligne debut/fin
	// (mais pas les espaces => ascii art).
	$echap = ereg_replace("^\n+|\n+$", "", $echap);

	// ne pas mettre le <div...> s'il n'y a qu'une ligne
	if (is_int(strpos($echap,"\n"))) {
		$echap = nl2br("<div style='text-align: left;' "
		. "class='spip_code' dir='ltr'><code>"
		.$echap."</code></div>");
	} else
		$echap = "<code class='spip_code' "
		."dir='ltr'>".$echap."</code>";

	$echap = str_replace("\t",
		"&nbsp; &nbsp; &nbsp; &nbsp; ", $echap);
	$echap = str_replace("  ", " &nbsp;", $echap);
	return $echap;
}

// Echapper les <cadre>...</ cadre> aka <frame>...</ frame>
// http://doc.spip.org/@traiter_echap_cadre_dist
function traiter_echap_cadre_dist($regs) {
	$echap = trim(entites_html($regs[3]));
	$total_lignes = substr_count($echap, "\n") + 1;
	$echap = "<form action=\"/\" method=\"get\"><div>"
	."<textarea readonly='readonly' cols='40' rows='$total_lignes' "
	."class='spip_cadre' dir='ltr'>"
	.$echap
	."</textarea></div></form>";
	return $echap;
}
// http://doc.spip.org/@traiter_echap_frame_dist
function traiter_echap_frame_dist($regs) {
	return traiter_echap_cadre_dist($regs);
}

// http://doc.spip.org/@traiter_echap_script_dist
function traiter_echap_script_dist($regs) {
	return $regs[0];
}


// - pour $source voir commentaire infra (echappe_retour)
// - pour $no_transform voir le filtre post_autobr dans inc_filtres.php3
// http://doc.spip.org/@echappe_html
function echappe_html($letexte, $source='', $no_transform=false,
$preg='') {
	if (!strlen($letexte)) return '';

	if (!$preg)
		$preg = ',<(html|code|cadre|frame|script)'
			.'(\s[^>]*)?'
			.'>(.*)</\1>,UimsS';
	while (preg_match_all(
	$preg,
	$letexte, $matches, PREG_SET_ORDER))
	foreach ($matches as $regs) {

		// Bloc gerant les imbrications complexes
		// balise d'ouverture == imbrication non finie
		if (strpos($regs[$endr = count($regs) - 1], '<' . $regs[1]) !== false
		 && (!isset($lastreg) || $lastreg != $regs[$endr])) {
			if (isset($pmid) || (($posmid = strpos($preg, '(.*)'))
						&& ($pmid = '.') && ($psav = $preg))) {
				$pmid = '(?:<\1[^>]*>(' . $pmid . '*)</\1>|.)';
				$preg = substr_replace($psav, $pmid, $posmid + 1, 1);
				$lastreg = $regs[$endr];
				continue 2; # reprendre preg_match_all avec un $preg augmente
			}
		}

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
function echappe_retour($letexte, $source='') {
	if (strpos($letexte,"base64$source")) {
		# spip_log(htmlspecialchars($letexte));  ## pour les curieux
		if (preg_match_all(
		',<(span|div) class=[\'"]base64'.$source.'[\'"]\s.*></\1>,UmsS',
		$letexte, $regs, PREG_SET_ORDER)) {
			foreach ($regs as $reg) {
				$rempl = base64_decode(extraire_attribut($reg[0], 'title'));
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
	$texte = preg_replace(",\[\[([^]]|\][^]])*\]\],sS", "", $texte);

	// supprimer les codes typos
	$texte = str_replace(array('}','{'), '', $texte);

	// supprimer les tableaux
	$texte = preg_replace(",(^|\r)\|.*\|\r,s", "\r", $texte);
	return $texte;
}

// http://doc.spip.org/@couper
function couper($texte, $taille=50) {
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
	$texte = ereg_replace("\n\n+", "\r", $texte);
	$texte = ereg_replace("<(p|br)( [^>]*)?".">", "\r", $texte);

	// supprimer les traits, lignes etc
	$texte = ereg_replace("(^|\r|\n)(-[-#\*]*|_ )", "\r", $texte);

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
	$court = ereg_replace("([^[:space:]][[:space:]]+)[^[:space:]]*\n?$", "\\1", $long);
	$points = '&nbsp;(...)';

	// trop court ? ne pas faire de (...)
	if (spip_strlen($court) < max(0.75 * $taille,2)) {
		$points = '';
		$long = spip_substr($texte, 0, $taille);
		$texte = ereg_replace("([^[:space:]][[:space:]]+)[^[:space:]]*$", "\\1", $long);
		// encore trop court ? couper au caractere
		if (spip_strlen($texte) < 0.75 * $taille)
			$texte = $long;
	} else
		$texte = $court;

	if (strpos($texte, "\n"))	// la fin est encore la : c'est qu'on n'a pas de texte de suite
		$points = '';

	// remettre les paragraphes
	$texte = ereg_replace("\r+", "\n\n", $texte);

	// supprimer l'eventuelle entite finale mal coupee
	$texte = preg_replace('/&#?[a-z0-9]*$/S', '', $texte);

	return quote_amp(trim($texte)).$points;
}

// prendre <intro>...</intro> sinon couper a la longueur demandee
// http://doc.spip.org/@couper_intro
function couper_intro($texte, $long) {
	$texte = extraire_multi(eregi_replace("(</?)intro>", "\\1intro>", $texte)); // minuscules
	$intro = '';
	while ($fin = strpos($texte, "</intro>")) {
		$zone = substr($texte, 0, $fin);
		$texte = substr($texte, $fin + strlen("</intro>"));
		if ($deb = strpos($zone, "<intro>") OR substr($zone, 0, 7) == "<intro>")
			$zone = substr($zone, $deb + 7);
		$intro .= $zone;
	}

	if ($intro)
		$intro = $intro.'&nbsp;(...)';
	else {
		$intro = preg_replace(',([|]\s*)+,S', '; ', couper($texte, $long));
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

// Securite : empecher l'execution de code PHP, en le transformant en joli code
// http://doc.spip.org/@interdire_scripts
function interdire_scripts($t) {

	// rien ?
	if (!$t OR !strstr($t, '<')) return $t;

	// echapper les tags asp
	$t = str_replace('<'.'%', '&lt;%', $t);

	// echapper le php
	$t = str_replace('<'.'?', '&lt;?', $t);

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
	static $process, $test;

	# attention safehtml nettoie deux ou trois caracteres de plus. A voir
	if (strpos($t,'<')===false)
		return str_replace("\x00", '', $t);

	$t = interdire_scripts($t);
	$t = echappe_js($t);

	if (!$test) {
		if ($f = include_spip('safehtml/classes/safehtml', false)) {
			define('XML_HTMLSAX3', dirname($f).'/');
			include($f);
			$process = new safehtml();
			$process->deleteTags[] = 'param'; // sinon bug Firefox
		} else die('pas de safe');
		if ($process)
			$test = 1; # ok
		else
			$test = -1; # se rabattre sur interdire_scripts
	}

	if ($test > 0) {
		# reset ($process->clear() ne vide que _xhtml...),
		# on doit pouvoir programmer ca plus propremement
		$process->_counter = array();
		$process->_stack = array();
		$process->_dcCounter = array();
		$process->_dcStack = array();
		$process->_listScope = 0;
		$process->_liStack = array();
#		$process->parse(''); # cas particulier ?
		$process->clear();
		$t = $process->parse($t);
	}

	return interdire_scripts($t); # gere le < ?php > en plus
}

// Correction typographique francaise
// http://doc.spip.org/@typo_fr
function typo_fr($letexte) {
	static $trans;

	// Nettoyer 160 = nbsp ; 187 = raquo ; 171 = laquo ; 176 = deg ; 147 = ldquo; 148 = rdquo
	if (!$trans) {
		$trans = array(
			"&nbsp;" => "~",
			"&raquo;" => "&#187;",
			"&laquo;" => "&#171;",
			"&rdquo;" => "&#8221;",
			"&ldquo;" => "&#8220;",
			"&deg;" => "&#176;"
		);
		$chars = array(160 => '~', 187 => '&#187;', 171 => '&#171;', 148 => '&#8221;', 147 => '&#8220;', 176 => '&#176;');
		$chars_trans = array_keys($chars);
		$chars = array_values($chars);
		$chars_trans = implode(' ',array_map('chr',$chars_trans));
		$chars_trans = unicode2charset(charset2unicode($chars_trans, 'iso-8859-1', 'forcer'));
		$chars_trans = explode(" ",$chars_trans);
		foreach($chars as $k=>$r)
			$trans[$chars_trans[$k]] = $r;
	}

	$letexte = strtr($letexte, $trans);

	$cherche1 = array(
		/* 1 */ 	'/((^|[^\#0-9a-zA-Z\&])[\#0-9a-zA-Z]*)\;/S',
		/* 2 */		'/&#187;| --?,|:([^0-9]|$)/S',
		/* 3 */		'/([^[<!?])([!?])/S',
		/* 4 */		'/&#171;|(M(M?\.|mes?|r\.?)|[MnN]&#176;) /S'
	);
	$remplace1 = array(
		/* 1 */		'\1~;',
		/* 2 */		'~\0',
		/* 3 */		'\1~\2',
		/* 4 */		'\0~'
	);
	$letexte = preg_replace($cherche1, $remplace1, $letexte);
	$letexte = preg_replace("/ *~+ */S", "~", $letexte);

	$cherche2 = array(
		'/([^-\n]|^)--([^-]|$)/S',
		'/(http|https|ftp|mailto)~:/S',
		'/~/'
	);
	$remplace2 = array(
		'\1&mdash;\2',
		'\1:',
		'&nbsp;'
	);
	$letexte = preg_replace($cherche2, $remplace2, $letexte);

	return $letexte;
}

// rien sauf les "~" et "-,"
// http://doc.spip.org/@typo_en
function typo_en($letexte) {

	$cherche1 = array(
		'/ --?,/S'
	);
	$remplace1 = array(
		'~\0'
	);
	$letexte = preg_replace($cherche1, $remplace1, $letexte);

	$letexte = str_replace("&nbsp;", "~", $letexte);
	$letexte = ereg_replace(" *~+ *", "~", $letexte);

	$cherche2 = array(
		'/([^-\n]|^)--([^-]|$)/',
		'/~/'
	);
	$remplace2 = array(
		'\1&mdash;\2',
		'&nbsp;'
	);

	$letexte = preg_replace($cherche2, $remplace2, $letexte);

	return $letexte;
}

//
// Typographie generale
// note: $echapper = false lorsqu'on appelle depuis propre() [pour accelerer]
//
// http://doc.spip.org/@typo
function typo($letexte, $echapper=true) {

	// Plus vite !
	if (!$letexte) return $letexte;

	// Echapper les codes <html> etc
	if ($echapper)
		$letexte = echappe_html($letexte, 'TYPO');

	// Appeler les fonctions de pre-traitement
	$letexte = pipeline('pre_typo', $letexte);
	// old style
	if (function_exists('avant_typo'))
		$letexte = avant_typo($letexte);

	// Caracteres de controle "illegaux"
	$letexte = corriger_caracteres($letexte);

	// Proteger les caracteres typographiques a l'interieur des tags html
	$protege = "!':;?~";
	$illegal = "\x1\x2\x3\x4\x5\x6";
	if (preg_match_all(",</?[a-z!][^<>]*[!':;\?~][^<>]*>,imsS",
	$letexte, $regs, PREG_SET_ORDER)) {
		foreach ($regs as $reg) {
			$insert = $reg[0];
			// hack: on transforme les caracteres a proteger en les remplacant
			// par des caracteres "illegaux". (cf corriger_caracteres())
			$insert = strtr($insert, $protege, $illegal);
			$letexte = str_replace($reg[0], $insert, $letexte);
		}
	}

	// zouli apostrophe
	$letexte = str_replace("'", "&#8217;", $letexte);

	// typo francaise ou anglaise ?
	// $lang_objet est fixee dans l'interface privee pour editer
	// un texte anglais en interface francaise (ou l'inverse) ;
	// sinon determiner la typo en fonction de la langue
	if (!$lang = $GLOBALS['lang_objet'])
		$lang = $GLOBALS['spip_lang'];
	lang_select($lang);
	switch (lang_typo($lang)) {
		case 'fr':
			$letexte = typo_fr($letexte);
			break;
		default:
			$letexte = typo_en($letexte);
			break;
	}

	// Retablir les caracteres proteges
	$letexte = strtr($letexte, $illegal, $protege);

	//
	// Installer les modeles, notamment images et documents ;
	//
	// NOTE : dans propre() ceci s'execute avant les tableaux a cause du "|",
	// et apres les liens a cause du traitement de [<imgXX|right>->URL]
	$letexte = traiter_modeles($mem = $letexte, false, $echapper ? 'TYPO' : '');
	if ($letexte != $mem) $echapper = true;
	unset($mem);

	// Appeler les fonctions de post-traitement
	$letexte = pipeline('post_typo', $letexte);
	// old style
	if (function_exists('apres_typo'))
		$letexte = apres_typo($letexte);

	// remettre la langue precedente
	lang_dselect();

	// reintegrer les echappements
	if ($echapper)
		$letexte = echappe_retour($letexte, 'TYPO');

	# un message pour abs_url - on est passe en mode texte
	$GLOBALS['mode_abs_url'] = 'texte';

	// Dans l'espace prive, securiser ici
	if (!_DIR_RESTREINT)
		$letexte = interdire_scripts($letexte);

	return $letexte;
}

// obsolete, utiliser calculer_url

// http://doc.spip.org/@extraire_lien
function extraire_lien ($regs) {
	list($lien, $class, $texte) = calculer_url($regs[3], $regs[1],'tout');
	// Preparer le texte du lien ; attention s'il contient un <div>
	// (ex: [<docXX|right>->lien]), il faut etre smart
	$ref = "<a href=\"$lien\" class=\"$class\">$texte</a>";
	return array($ref, $lien, $texte);
}

// traitement des raccourcis issus de [TITRE->RACCOURCInnn] et connexes
//
// Valeur retournee selon le parametre $pour:
// 'tout' : <a href="L">T</a>
// 'titre': seulement T ci-dessus (i.e. le TITRE ci-dessus ou dans table SQL)
// 'url':   seulement L (i.e. generer_url_RACCOURCI)

// http://doc.spip.org/@calculer_url
function calculer_url ($lien, $texte='', $pour='url') {
	$lien = vider_url($lien); # supprimer 'http://' ou 'mailto:'

	// Cherche un lien du type [->raccourci 123]
	// associe a une fonction generer_url_raccourci()
	if (preg_match(',^(\S*?)\s*(\d+)(\?.*?)?(#[^\s]*)?$,S', trim($lien), $match)) {
		list(,$objet,$id,$params,$ancre) = $match;

		// valeur par defaut
		if (!$f = $objet) $f = 'article';

		// aliases (historique)
		if ($f == 'art') $f = 'article';
		else if ($f == 'br') $f = 'breve';
		else if ($f == 'rub') $f = 'rubrique';
		else if ($f == 'aut') $f = 'auteur';
		else if ($f == 'doc' OR $f == 'im' OR $f == 'img' OR $f == 'image' OR $f == 'emb')
			$f = 'document';
		else if (preg_match(',^br..?ve$,S', $f)) $f = 'breve'; # accents :(

		// chercher la fonction nommee generer_url_$raccourci
		// ou calculer_url_raccourci si on n'a besoin que du lien
		$f=(($pour == 'url') ? 'generer' : 'calculer') . '_url_' . $f;
		charger_generer_url();
		if (function_exists($f)) {
			if ($pour == 'url') {
				$url = $f($id);
				if ($params)
					$url .= (strstr($url, '?') ? '&amp;' : '?')
						. substr($params,1);
				return $url . $ancre;
			}

			$res = $f($id, $texte, $ancre);
			$res[2] = $res[2];
			if ($pour == 'titre')
				return $res[2];
			if ($params)
				$res[0] .= (strstr($res[0], '?') ? '&amp;' : '?')
					. substr($params,1);
			$res[0] .= $ancre;
			return $res;
		}
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

	return ($pour == 'url') ? $lien : array($lien, $class, $texte, $lang);
}

// http://doc.spip.org/@calculer_url_article
function calculer_url_article($id, $texte='') {
	$lien = generer_url_article($id);
	$row = @spip_fetch_array(spip_query("SELECT titre,lang FROM spip_articles WHERE id_article=$id"));
	if ($texte=='')
		$texte = supprimer_numero($row['titre']);
	return array($lien, 'spip_in', $texte, $row['lang']);
}

// http://doc.spip.org/@calculer_url_rubrique
function calculer_url_rubrique($id, $texte='')
{
	$lien = generer_url_rubrique($id);
	$row = @spip_fetch_array(spip_query("SELECT titre,lang FROM spip_rubriques WHERE id_rubrique=$id"));
	if ($texte=='')
		$texte = supprimer_numero($row['titre']);
	return array($lien, 'spip_in', $texte, $row['lang']);
}

// http://doc.spip.org/@calculer_url_mot
function calculer_url_mot($id, $texte='')
{
	$lien = generer_url_mot($id);
	$row = @spip_fetch_array(spip_query("SELECT titre FROM spip_mots WHERE id_mot=$id"));
	if ($texte=='')
		$texte = supprimer_numero($row['titre']);
	return array($lien, 'spip_in', $texte);
}

// http://doc.spip.org/@calculer_url_breve
function calculer_url_breve($id, $texte='')
{
	$lien = generer_url_breve($id);
	$row = @spip_fetch_array(spip_query("SELECT titre,lang FROM spip_breves WHERE id_breve=$id"));
	if ($texte=='')
		$texte = supprimer_numero($row['titre']);
	return array($lien, 'spip_in', $texte, $row['lang']);
}

// http://doc.spip.org/@calculer_url_auteur
function calculer_url_auteur($id, $texte='')
{
	$lien = generer_url_auteur($id);
	if ($texte=='') {
		$row = @spip_fetch_array(spip_query("SELECT nom FROM spip_auteurs WHERE id_auteur=$id"));
		$texte = $row['nom'];
	}
	return array($lien, 'spip_in', $texte); # pas de hreflang
}

// http://doc.spip.org/@calculer_url_document
function calculer_url_document($id, $texte='')
{
	$lien = generer_url_document($id);
	if ($texte=='') {
		$row = @spip_fetch_array(spip_query("SELECT titre,fichier FROM spip_documents WHERE id_document=$id"));
		$texte = $row['titre'];
		if ($texte=='')
			$texte = preg_replace(",^.*/,","",$row['fichier']);
	}
	return array($lien, 'spip_in', $texte); # pas de hreflang
}

// http://doc.spip.org/@calculer_url_site
function calculer_url_site($id, $texte='')
{
	# attention dans le cas des sites le lien pointe non pas sur
	# la page locale du site, mais directement sur le site lui-meme
	$row = @spip_fetch_array(spip_query("SELECT nom_site,url_site,lang FROM spip_syndic WHERE id_syndic=$id"));
	if ($row) {
		$lien = $row['url_site'];
		if ($texte=='')
			$texte = supprimer_numero($row['nom_site']);
	}
	return array($lien, 'spip_out', $texte, $row['lang']);
}

// http://doc.spip.org/@calculer_url_site
function calculer_url_forum($id, $texte='')
{
	$lien = generer_url_forum($id);
	if ($texte=='') {
		$row = @spip_fetch_array(spip_query("SELECT titre FROM spip_forum WHERE id_forum=$id AND statut='publie'"));
		if ($texte=='')
			$texte = $row['titre'];
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
			else if (preg_match(',^(\|([[:space:]]*{{[^}]+}}[[:space:]]*|<))+$,sS',
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
			if (ereg("\n-[*#]", $ligne))
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

	return "\n\n<table class=\"spip\"$summary>\n"
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
					$ajout .= "<$type class=\"spip\">";
					$pile_type[$niveau] = "</$type>";
				}

				$ajout .= "<li class=\"spip\">";
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
	return preg_replace(',<(img|doc|emb)([0-9]+)(\|([^>]*))?'.'>,iS',
		"($message)", $letexte);
}

// traite les modeles (dans la fonction typo), en remplacant
// le raccourci <modeleN|parametres> par la page calculee a
// partir du squelette modeles/modele.html
// Le nom du modele doit faire au moins trois caracteres (evite <h2>)
// Si $doublons==true, on repere les documents sans calculer les modeles
// mais on renvoie les params (pour l'indexation par le moteur de recherche)
// http://doc.spip.org/@traiter_modeles
function traiter_modeles($texte, $doublons=false, $echap='') {
	// detecter les modeles (rapide)
	if (preg_match_all('/<[a-z_-]{3,}\s*[0-9|]+/iS',
	$texte, $matches, PREG_SET_ORDER)) {
		include_spip('public/assembler');
		foreach ($matches as $match) {
			// Recuperer l'appel complet (y compris un eventuel lien)
			// $regs : 1 => modele, 2 => type, 3 => id, 4 => params, 5 => a
			$a = strpos($texte,$match[0]);
			preg_match(
			'/^' #debut
			.'(<([a-z_-]{3,})' # <modele
			.'\s*([0-9]*)\s*' # id
			.'([|](?:<[^<>]*>|[^>])*)?' # |arguments (y compris des tags <...>)
			.'>)' # fin du modele >
			.'\s*(<\/a>)?' # eventuel </a>
			.'/isS',
			substr($texte, $a), $regs);

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
				$modele = inclure_modele($regs[2], $regs[3], $regs[4], $lien);

				// le remplacer dans le texte
				if ($modele !== false) {
					$rempl = code_echappement($modele, $echap);
					$texte = substr($texte, 0, $a)
						. $rempl
						. substr($texte, $a+strlen($cherche));
				}
			}

			// hack pour tout l'espace prive
			if (!_DIR_RESTREINT)
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
		$letexte = preg_replace(
		',(<p(>|\s[^>]*)>)\s*|\s*(</p[>[:space:]]),iS', '\1\3',
			$letexte);

		// Supprimer les <p xx></p> vides
		$letexte = preg_replace(',<p\s[^>]*></p>\s*,iS', '',
			$letexte);

		// Renommer les paragraphes normaux avec class="spip"
		$letexte = str_replace('<p >', '<p class="spip">',
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

	// title et hreflang donnes par le raccourci ?
	if (preg_match(',^(.*?)([|]([^<>]*?))?([{]([a-z_]+)[}])?$,', $regs[1], $m)) {
		// |infobulle ?
		if ($m[2])
			$bulle = ' title="'.texte_backend($m[3]).'"';
		// {hreflang} ?
		if ($m[4])
			$hlang = $m[5];
		// S'il n'y a pas de hreflang sous la forme {}, ce qui suit le |
		// est peut-etre une langue
		else if (preg_match(',^[a-z_]+$,', $m[3])) {
			// si c'est un code de langue connu, on met un hreflang
			include_spip('inc/lang');
			if (traduire_nom_langue($m[3]) <> $m[3]) {
				$hlang = $m[3];
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


// Nettoie un texte, traite les raccourcis spip, la typo, etc.
// http://doc.spip.org/@traiter_raccourcis
function traiter_raccourcis($letexte) {
	global $debut_intertitre, $fin_intertitre, $ligne_horizontale, $url_glossaire_externe;
	global $compt_note;
	global $marqueur_notes;
	global $ouvre_ref;
	global $ferme_ref;
	global $ouvre_note;
	global $ferme_note;
	global $lang_dir;
	static $notes_vues;
	static $tester_variables=0;

	// Appeler les fonctions de pre_traitement
	$letexte = pipeline('pre_propre', $letexte);
	// old style
	if (function_exists('avant_propre'))
		$letexte = avant_propre($letexte);

	// Verifier les variables de personnalisation
	if (!$tester_variables++) {
		tester_variable('debut_intertitre', "\n<h3 class=\"spip\">");
		tester_variable('fin_intertitre', "</h3>\n");
		tester_variable('ligne_horizontale', "\n<hr class=\"spip\" />\n");
		tester_variable('ouvre_ref', '&nbsp;[');
		tester_variable('ferme_ref', ']');
		tester_variable('ouvre_note', '[');
		tester_variable('ferme_note', '] ');
		tester_variable('url_glossaire_externe',
			"http://@lang@.wikipedia.org/wiki/");
		tester_variable('les_notes', '');
		tester_variable('compt_note', 0);
		tester_variable('toujours_paragrapher', false);
	}

	// Gestion de la <poesie>
	if (preg_match_all(",<(poesie|poetry)>(.*)<\/(poesie|poetry)>,UimsS",
	$letexte, $regs, PREG_SET_ORDER)) {
		foreach ($regs as $reg) {
			$lecode = preg_replace(",\r\n?,S", "\n", $reg[2]);
			$lecode = ereg_replace("\n[[:space:]]*\n", "\n&nbsp;\n",$lecode);
			$lecode = "<div class=\"spip_poesie\">\n<div>".ereg_replace("\n+", "</div>\n<div>", trim($lecode))."</div>\n</div>\n\n";
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

			$lien = "<a href=\"#nb$ancre\"$name_id class=\"spip_note\">";

			// creer le popup 'title' sur l'appel de note
			if ($title = supprimer_tags(propre($note_texte))) {
				$title = $ouvre_note.$num_note.$ferme_note.$title;
				$title = couper($title,80);
				$lien = inserer_attribut($lien, 'title', $title);
			}

			$insert = "$ouvre_ref$lien$num_note</a>$ferme_ref";

			// on l'echappe
			$insert = code_echappement($insert);

			$appel = "$ouvre_note<a href=\"#nh$ancre\" name=\"nb$ancre\" class=\"spip_note\" title=\"" . _T('info_notes') . " $ancre\">$num_note</a>$ferme_note";
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
	$regexp = ",\[([^][]*)->(>?)([^]]*)\],msS";
	$inserts = array();
	if (preg_match_all($regexp, $letexte, $matches, PREG_SET_ORDER)) {
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
	if (ereg("\n-[*#]", $letexte))
		$letexte = traiter_listes($letexte);

	// Puce
	if (strpos($letexte, "\n- ") !== false)
		$puce = definir_puce();
	else $puce = '';


	// Proteger les caracteres actifs a l'interieur des tags html
	$protege = "{}-";
	$illegal = "\x1\x2\x3";
	if (preg_match_all(",</?[a-z!][^<>]*[!':;\?~][^<>]*>,imsS",
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
		/* 6 */ 	"/(( *)\n){2,}(<br[[:space:]]*\/?".">)?/S",
		/* 7 */ 	"/[{][{]/S",
		/* 8 */ 	"/[}][}]/S",
		/* 9 */ 	"/[{]/S",
		/* 10 */	"/[}]/S",
		/* 11 */	"/(<br[[:space:]]*\/?".">){2,}/S",
		/* 12 */	"/<p>([\n]*(<br[[:space:]]*\/?".">)*)*/S",
		/* 13 */	"/<quote>/S",
		/* 14 */	"/<\/quote>/S"
	);
	$remplace1 = array(
		/* 0 */ 	"\n\n$ligne_horizontale\n\n",
		/* 1 */ 	"\n<br />&mdash;&nbsp;",
		/* 2 */ 	"\n<br />$puce&nbsp;",
		/* 3 */ 	"\n<br />",
		/* 4 */ 	"\$1\n\n$debut_intertitre",
		/* 5 */ 	"$fin_intertitre\n\n\$1",
		/* 6 */ 	"<p>",
		/* 7 */ 	"<strong class=\"spip\">",
		/* 8 */ 	"</strong>",
		/* 9 */ 	"<i class=\"spip\">",
		/* 10 */	"</i>",
		/* 11 */	"<p>",
		/* 12 */	"<p>",
		/* 13 */	"<blockquote class=\"spip\"><p>",
		/* 14 */	"</blockquote><p>"
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
	$mes_notes = str_replace(
		'<p class="spip">', '<p class="spip_note">', $mes_notes);
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

	return $letexte;
}

?>
