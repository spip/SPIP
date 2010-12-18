<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/filtres');
include_spip('inc/lang');
include_spip('inc/lien');

// init du tableau principal des raccourcis

global $spip_raccourcis_typo, $class_spip_plus, $debut_intertitre, $fin_intertitre, $debut_gras, $fin_gras, $debut_italique, $fin_italique;

$spip_raccourcis_typo = array(
			      array(
		/* 4 */		"/(^|[^{])[{][{][{]/S",
		/* 5 */		"/[}][}][}]($|[^}])/S",
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
				),
			      array(
		/* 4 */ 	"\$1\n\n" . $debut_intertitre,
		/* 5 */ 	$fin_intertitre ."\n\n\$1",
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
				)
);

// Raccourcis dependant du sens de la langue

function definir_raccourcis_alineas()
{
	global $ligne_horizontale;
	static $alineas = array();
	$x = _DIR_RESTREINT ? lang_dir() : lang_dir($GLOBALS['spip_lang']);
	if (!isset($alineas[$x])) {

		$alineas[$x] = array(
		array(
		/* 0 */ 	"/\n(----+|____+)/S",
		/* 1 */ 	"/\n-- */S",
		/* 2 */ 	"/\n- */S", /* DOIT rester a cette position */
		/* 3 */ 	"/\n_ +/S"
				),
		array(
		/* 0 */ 	"\n\n" . $ligne_horizontale . "\n\n",
		/* 1 */ 	"\n<br />&mdash;&nbsp;",
		/* 2 */ 	"\n<br />".definir_puce()."&nbsp;",
		/* 3 */ 	"\n<br />"
				)
		);
	}
	return $alineas[$x];
}

// On initialise la puce pour eviter find_in_path() a chaque rencontre de \n-
// Mais attention elle depend de la direction et de X_fonctions.php, ainsi que
// de l'espace choisi (public/prive)
// http://doc.spip.org/@definir_puce
function definir_puce() {

	// Attention au sens, qui n'est pas defini de la meme facon dans
	// l'espace prive (spip_lang est la langue de l'interface, lang_dir
	// celle du texte) et public (spip_lang est la langue du texte)
	$dir = _DIR_RESTREINT ? lang_dir() : lang_dir($GLOBALS['spip_lang']);

	$p = 'puce' . (test_espace_prive() ? '_prive' : '');
	if ($dir == 'rtl') $p .= '_rtl';

	if (!isset($GLOBALS[$p])) {
		$img = find_in_path($p.'.gif');
		list(,,,$size) = @getimagesize($img);
		$GLOBALS[$p] = '<img src="'.$img.'" '.$size.' class="puce" alt="-" />';
	}
	return $GLOBALS[$p];
}

// XHTML - Preserver les balises-bloc : on liste ici tous les elements
// dont on souhaite qu'ils provoquent un saut de paragraphe
if (!defined('_BALISES_BLOCS')) define('_BALISES_BLOCS',
	'div|pre|ul|ol|li|blockquote|h[1-6r]|'
	.'t(able|[rdh]|body|foot|extarea)|'
	.'form|object|center|marquee|address|'
	.'d[ltd]|script|noscript|map|button|fieldset|style');

//
// Echapper les elements perilleux en les passant en base64
//

// Creer un bloc base64 correspondant a $rempl ; au besoin en marquant
// une $source differente ; le script detecte automagiquement si ce qu'on
// echappe est un div ou un span
// http://doc.spip.org/@code_echappement
function code_echappement($rempl, $source='', $no_transform=false) {
	if (!strlen($rempl)) return '';

	// Tester si on echappe en span ou en div
	$mode = preg_match(',</?('._BALISES_BLOCS.')[>[:space:]],iS', $rempl) ?
		'div' : 'span';
	$return = '';

	// Decouper en morceaux, base64 a des probleme selon la taille de la pile
	$taille = 30000;
	for($i = 0; $i < strlen($rempl); $i += $taille) {
		// Convertir en base64 et cacher dans un attribut
		// utiliser les " pour eviter le re-encodage de ' et &#8217
		$base64 = base64_encode(substr($rempl, $i, $taille));
		$return .= "<$mode class=\"base64$source\" title=\"$base64\"></$mode>";
	}

	return $return
		. ((!$no_transform AND $mode == 'div')
			? "\n\n"
			: ''
		);
;
}

// Echapper les <html>...</ html>
// http://doc.spip.org/@traiter_echap_html_dist
function traiter_echap_html_dist($regs) {
	return $regs[3];
}

// Echapper les <code>...</ code>
// http://doc.spip.org/@traiter_echap_code_dist
function traiter_echap_code_dist($regs) {
	list(,,$att,$corps) = $regs;
	$echap = htmlspecialchars($corps); // il ne faut pas passer dans entites_html, ne pas transformer les &#xxx; du code !

	// ne pas mettre le <div...> s'il n'y a qu'une ligne
	if (is_int(strpos($echap,"\n"))) {
		// supprimer les sauts de ligne debut/fin
		// (mais pas les espaces => ascii art).
		$echap = preg_replace("/^[\n\r]+|[\n\r]+$/s", "", $echap);
		$echap = nl2br($echap);
		$echap = "<div style='text-align: left;' "
		. "class='spip_code' dir='ltr'><code$att>"
		.$echap."</code></div>";
	} else {
		$echap = "<code$att class='spip_code' dir='ltr'>".$echap."</code>";
	}

	$echap = str_replace("\t", "&nbsp; &nbsp; &nbsp; &nbsp; ", $echap);
	$echap = str_replace("  ", " &nbsp;", $echap);
	return $echap;
}

// Echapper les <cadre>...</ cadre> aka <frame>...</ frame>
// http://doc.spip.org/@traiter_echap_cadre_dist
function traiter_echap_cadre_dist($regs) {
	$echap = trim(entites_html($regs[3]));
	// compter les lignes un peu plus finement qu'avec les \n
	$lignes = explode("\n",trim($echap));
	$n = 0;
	foreach($lignes as $l)
		$n+=floor(strlen($l)/60)+1;
	$n = max($n,2);
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
	if (preg_match(',<script\b[^>]+php,ims', $regs[0]))
		return highlight_string($regs[0],true);

	// Cas normal : le script passe tel quel
	return $regs[0];
}

define('_PROTEGE_BLOCS', ',<(html|code|cadre|frame|script)(\s[^>]*)?>(.*)</\1>,UimsS');

// - pour $source voir commentaire infra (echappe_retour)
// - pour $no_transform voir le filtre post_autobr dans inc/filtres
// http://doc.spip.org/@echappe_html
function echappe_html($letexte, $source='', $no_transform=false,
$preg='') {
	if (!is_string($letexte) or !strlen($letexte))
		return $letexte;

	if (($preg OR strpos($letexte,"<")!==false) 
	  AND preg_match_all($preg ? $preg : _PROTEGE_BLOCS, $letexte, $matches, PREG_SET_ORDER))
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
				code_echappement($echap, $source, $no_transform),
				$letexte);
		}

	if ($no_transform)
		return $letexte;

	// Gestion du TeX
	if (strpos($letexte, "<math>") !== false) {
		include_spip('inc/math');
		$letexte = traiter_math($letexte, $source);
	}

	// Echapper le php pour faire joli (ici, c'est pas pour la securite)
	if (strpos($letexte,"<"."?")!==false AND preg_match_all(',<[?].*($|[?]>),UisS',
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
// par propre() : exemple dans multi et dans typo()
// http://doc.spip.org/@echappe_retour
function echappe_retour($letexte, $source='', $filtre = "") {
	if (strpos($letexte,"base64$source")) {
		# spip_log(htmlspecialchars($letexte));  ## pour les curieux
		if (strpos($letexte,"<")!==false AND
		  preg_match_all(',<(span|div) class=[\'"]base64'.$source.'[\'"]\s(.*)>\s*</\1>,UmsS',
		$letexte, $regs, PREG_SET_ORDER)) {
			foreach ($regs as $reg) {
				$rempl = base64_decode(extraire_attribut($reg[0], 'title'));
				// recherche d'attributs supplementaires
				$at = array();
				foreach(array('lang', 'dir') as $attr) {
					if ($a = extraire_attribut($reg[0], $attr))
						$at[$attr] = $a;
				}
				if ($at) {
					$rempl = '<'.$reg[1].'>'.$rempl.'</'.$reg[1].'>';
					foreach($at as $attr => $a)
						$rempl = inserer_attribut($rempl, $attr, $a);
				}
				if ($filtre) $rempl = $filtre($rempl);
				$letexte = str_replace($reg[0], $rempl, $letexte);
			}
		}
	}
	return $letexte;
}

// Reinserer le javascript de confiance (venant des modeles)

// http://doc.spip.org/@echappe_retour_modeles
function echappe_retour_modeles($letexte, $interdire_scripts=false)
{
	$letexte = echappe_retour($letexte);

	// Dans les appels directs hors squelette, securiser aussi ici
	if ($interdire_scripts)
		$letexte = interdire_scripts($letexte,true);

	return trim($letexte);
}


// http://doc.spip.org/@couper
function couper($texte, $taille=50, $suite = '&nbsp;(...)') {
	if (!($length=strlen($texte)) OR $taille <= 0) return '';
	$offset = 400 + 2*$taille;
	while ($offset<$length
		AND strlen(preg_replace(",<[^>]+>,Uims","",substr($texte,0,$offset)))<$taille)
		$offset = 2*$offset;
	if (	$offset<$length
			&& ($p_tag_ouvrant = strpos($texte,'<',$offset))!==NULL){
		$p_tag_fermant = strpos($texte,'>',$offset);
		if ($p_tag_fermant<$p_tag_ouvrant)
			$offset = $p_tag_fermant+1; // prolonger la coupe jusqu'au tag fermant suivant eventuel
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
		$nbcharutf = preg_match_all('/(&#[0-9]{3,5};)/S', $long, $matches);
		$taille += $nbcharutf;
	}


	// couper au mot precedent
	$long = spip_substr($texte, 0, max($taille-4,1));
	$u = $GLOBALS['meta']['pcre_u'];
	$court = preg_replace("/([^\s][\s]+)[^\s]*\n?$/".$u, "\\1", $long);
	$points = $suite;

	// trop court ? ne pas faire de (...)
	if (spip_strlen($court) < max(0.75 * $taille,2)) {
		$points = '';
		$long = spip_substr($texte, 0, $taille);
		$texte = preg_replace("/([^\s][\s]+)[^\s]*\n?$/".$u, "\\1", $long);
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

//
// Les elements de propre()
//

// afficher joliment les <script>
// http://doc.spip.org/@echappe_js
function echappe_js($t,$class=' class="echappe-js"') {
	if (preg_match_all(',<script.*?($|</script.),isS', $t, $r, PREG_SET_ORDER))
	foreach ($r as $regs)
		$t = str_replace($regs[0],
			"<code$class>".nl2br(htmlspecialchars($regs[0])).'</code>',
			$t);
	return $t;
}
// http://doc.spip.org/@protege_js_modeles
function protege_js_modeles($t) {
	if (isset($GLOBALS['visiteur_session'])){
		if (preg_match_all(',<script.*?($|</script.),isS', $t, $r, PREG_SET_ORDER)){
			if (!defined('_PROTEGE_JS_MODELES')){
				include_spip('inc/acces');
				define('_PROTEGE_JS_MODELES',creer_uniqid());
			}
			foreach ($r as $regs)
				$t = str_replace($regs[0],code_echappement($regs[0],'javascript'._PROTEGE_JS_MODELES),$t);
		}
		if (preg_match_all(',<\?php.*?($|\?'.'>),isS', $t, $r, PREG_SET_ORDER)){
			if (!defined('_PROTEGE_PHP_MODELES')){
				include_spip('inc/acces');
				define('_PROTEGE_PHP_MODELES',creer_uniqid());
			}
			foreach ($r as $regs)
				$t = str_replace($regs[0],code_echappement($regs[0],'php'._PROTEGE_PHP_MODELES),$t);
		}
	}
	return $t;
}

// Securite : empecher l'execution de code PHP, en le transformant en joli code
// dans l'espace prive, cette fonction est aussi appelee par propre et typo
// si elles sont appelees en direct
// il ne faut pas desactiver globalement la fonction dans l'espace prive car elle protege
// aussi les balises des squelettes qui ne passent pas forcement par propre ou typo apres
// http://doc.spip.org/@interdire_scripts
function interdire_scripts($arg) {
	// on memorise le resultat sur les arguments non triviaux
	static $dejavu = array();

	// Attention, si ce n'est pas une chaine, laisser intact
	if (!$arg OR !is_string($arg) OR !strstr($arg, '<')) return $arg;

	if (isset($dejavu[$GLOBALS['filtrer_javascript']][$arg])) return $dejavu[$GLOBALS['filtrer_javascript']][$arg];

	// echapper les tags asp/php
	$t = str_replace('<'.'%', '&lt;%', $arg);

	// echapper le php
	$t = str_replace('<'.'?', '&lt;?', $t);

	// echapper le < script language=php >
	$t = preg_replace(',<(script\b[^>]+\blanguage\b[^\w>]+php\b),UimsS', '&lt;\1', $t);

	// Pour le js, trois modes : parano (-1), prive (0), ok (1)
	switch($GLOBALS['filtrer_javascript']) {
		case 0:
			if (!_DIR_RESTREINT)
				$t = echappe_js($t);
			break;
		case -1:
			$t = echappe_js($t);
			break;
	}

	// pas de <base href /> svp !
	$t = preg_replace(',<(base\b),iS', '&lt;\1', $t);

	// Reinserer les echappements des modeles
	if (defined('_PROTEGE_JS_MODELES'))
		$t = echappe_retour($t,"javascript"._PROTEGE_JS_MODELES);
	if (defined('_PROTEGE_PHP_MODELES'))
		$t = echappe_retour($t,"php"._PROTEGE_PHP_MODELES);

	return $dejavu[$GLOBALS['filtrer_javascript']][$arg] = $t;
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
		$safehtml = charger_fonction('safehtml', 'inc', true);
	if ($safehtml)
		$t = $safehtml($t);

	return interdire_scripts($t); // interdire le php (2 precautions)
}

// Typographie generale
// avec protection prealable des balises HTML et SPIP

// http://doc.spip.org/@typo
function typo($letexte, $echapper=true, $connect=null) {
	// Plus vite !
	if (!$letexte) return $letexte;

	// les appels directs a cette fonction depuis le php de l'espace
	// prive etant historiquement ecrit sans argment $connect
	// on utilise la presence de celui-ci pour distinguer les cas
	// ou il faut passer interdire_script explicitement
	// les appels dans les squelettes (de l'espace prive) fournissant un $connect
	// ne seront pas perturbes
	$interdire_script = false;
	if (is_null($connect)){
		$connect = '';
		$interdire_script = true;
	}

	// Echapper les codes <html> etc
	if ($echapper)
		$letexte = echappe_html($letexte, 'TYPO');

	//
	// Installer les modeles, notamment images et documents ;
	//
	// NOTE : propre() ne passe pas par ici mais directement par corriger_typo
	// cf. inc/lien
	$letexte = traiter_modeles($mem = $letexte, false, $echapper ? 'TYPO' : '', $connect);
	if ($letexte != $mem) $echapper = true;
	unset($mem);

	$letexte = corriger_typo($letexte);

	// reintegrer les echappements
	if ($echapper)
		$letexte = echappe_retour($letexte, 'TYPO');

	// Dans les appels directs hors squelette, securiser ici aussi
	if ($interdire_script)
		$letexte = interdire_scripts($letexte);

	return $letexte;
}

// Correcteur typographique

define('_TYPO_PROTEGER', "!':;?~%-");
define('_TYPO_PROTECTEUR', "\x1\x2\x3\x4\x5\x6\x7\x8");

define('_TYPO_BALISE', ",</?[a-z!][^<>]*[".preg_quote(_TYPO_PROTEGER)."][^<>]*>,imsS");

// http://doc.spip.org/@corriger_typo
function corriger_typo($letexte, $lang='') {
	// Plus vite !
	if (!$letexte) return $letexte;

	$letexte = pipeline('pre_typo', $letexte);

	// Caracteres de controle "illegaux"
	$letexte = corriger_caracteres($letexte);

	// Proteger les caracteres typographiques a l'interieur des tags html
	if (preg_match_all(_TYPO_BALISE, $letexte, $regs, PREG_SET_ORDER)) {
		foreach ($regs as $reg) {
			$insert = $reg[0];
			// hack: on transforme les caracteres a proteger en les remplacant
			// par des caracteres "illegaux". (cf corriger_caracteres())
			$insert = strtr($insert, _TYPO_PROTEGER, _TYPO_PROTECTEUR);
			$letexte = str_replace($reg[0], $insert, $letexte);
		}
	}

	// trouver les blocs multi et les traiter a part
	$letexte = extraire_multi($e = $letexte, $lang, true);
	$e = ($e === $letexte);

	// Charger & appliquer les fonctions de typographie
	$typographie = charger_fonction(lang_typo($lang), 'typographie');
	$letexte = $typographie($letexte);

	// Les citations en une autre langue, s'il y a lieu
	if (!$e) $letexte = echappe_retour($letexte, 'multi');

	// Retablir les caracteres proteges
	$letexte = strtr($letexte, _TYPO_PROTECTEUR, _TYPO_PROTEGER);

	// pipeline
	$letexte = pipeline('post_typo', $letexte);

	# un message pour abs_url - on est passe en mode texte
	$GLOBALS['mode_abs_url'] = 'texte';

	return $letexte;
}


//
// Tableaux
//

define('_RACCOURCI_TH_SPAN', '\s*(?:{{[^{}]+}}\s*)?|<');

// http://doc.spip.org/@traiter_tableau
function traiter_tableau($bloc) {

	// Decouper le tableau en lignes
	preg_match_all(',([|].*)[|]\n,UmsS', $bloc, $regs, PREG_PATTERN_ORDER);
	$lignes = array();
	$debut_table = $summary = '';
	$l = 0;
	$numeric = true;

	// Traiter chaque ligne
	$reg_line1 = ',^(\|(' . _RACCOURCI_TH_SPAN . '))+$,sS';
	$reg_line_all = ',^'  . _RACCOURCI_TH_SPAN . '$,sS';
	foreach ($regs[1] as $ligne) {
		$l ++;

		// Gestion de la premiere ligne :
		if ($l == 1) {
		// - <caption> et summary dans la premiere ligne :
		//   || caption | summary || (|summary est optionnel)
			if (preg_match(',^\|\|([^|]*)(\|(.*))?$,sS', rtrim($ligne,'|'), $cap)) {
				$l = 0;
				if ($caption = trim($cap[1]))
					$debut_table .= "<caption>".$caption."</caption>\n";
				$summary = ' summary="'.entites_html(trim($cap[3])).'"';
			}
		// - <thead> sous la forme |{{titre}}|{{titre}}|
		//   Attention thead oblige a avoir tbody
			else if (preg_match($reg_line1,	$ligne)) {
			  	preg_match_all('/\|([^|]*)/S', $ligne, $cols);
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
					  // inutile de garder le strong qui n'a servi que de marqueur 
					  $cols[$c] = str_replace(array('{','}'), '', $cols[$c]);
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
			$ligne = preg_replace("/\n{2,}/", "<br /><br />\n", $ligne);

			// tout mettre dans un tableau 2d
			preg_match_all('/\|([^|]*)/S', $ligne, $cols);
			$lignes[]= $cols[1];
		}
	}

	// maintenant qu'on a toutes les cellules
	// on prepare une liste de rowspan par defaut, a partir
	// du nombre de colonnes dans la premiere ligne.
	// Reperer egalement les colonnes numeriques pour les cadrer a droite
	$rowspans = $numeric = array();
	$n = count($lignes[0]);
	$k = count($lignes);
	// distinguer les colonnes numeriques a point ou a virgule,
	// pour les alignements eventuels sur "," ou "."
	$numeric_class = array('.'=>'point',','=>'virgule');
	for($i=0;$i<$n;$i++) {
	  $align = true;
	  for ($j=0;$j<$k;$j++) $rowspans[$j][$i] = 1;
	  for ($j=0;$j<$k;$j++) {
	    $cell = trim($lignes[$j][$i]);
	    if (preg_match($reg_line_all, $cell)) {
		if (!preg_match('/^\d+([.,]?)\d*$/', $cell, $r))
		  { $align = ''; break;}
		else if ($r[1]) $align = $r[1];
	      }
	  }
	  $numeric[$i] = !$align ? '' : (" class='numeric ".$numeric_class[$align]."'");
	}

	// et on parcourt le tableau a l'envers pour ramasser les
	// colspan et rowspan en passant
	$html = '';

	for($l=count($lignes)-1; $l>=0; $l--) {
		$cols= $lignes[$l];
		$colspan=1;
		$ligne='';

		for($c=count($cols)-1; $c>=0; $c--) {
			$attr= $numeric[$c];
			$cell = trim($cols[$c]);
			if($cell=='<') {
			  $colspan++;

			} elseif($cell=='^') {
			  $rowspans[$l-1][$c]+=$rowspans[$l][$c];

			} else {
			  if($colspan>1) {
				$attr .= " colspan='$colspan'";
				$colspan=1;
			  }
			  if(($x=$rowspans[$l][$c])>1) {
				$attr.= " rowspan='$x'";
			  }
			  $ligne= "\n<td".$attr.'>'.$cols[$c].'</td>'.$ligne;
			}
		}

		// ligne complete
		$class = alterner($l+1, 'even', 'odd');
		$html = "<tr class='row_$class'>$ligne</tr>\n$html";
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
		$pile_li = $pile_type = array();
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
					elseif (!isset($pile_li[$niveau])) {
						$ajout .= "<li$class_spip>";
						$pile_li[$niveau] = "</li>";
					}
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
// Sert aussi a nettoyer un texte qu'on veut mettre dans un <a> etc.
// TODO: gerer les modeles ?
// http://doc.spip.org/@supprime_img
function supprime_img($letexte, $message=NULL) {
	if ($message===NULL) $message = '(' . _T('img_indisponible') . ')';
	return preg_replace(',<(img|doc|emb)([0-9]+)(\|([^>]*))?'.'\s*/?'.'>,i',
		$message, $letexte);
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
		$letexte = preg_replace(',</?p\b\s?(.*?)>,iS', '<STOP P><p \1>',
			'<p>'.$letexte.'<STOP P>');

		// Fermer les paragraphes (y compris sur "STOP P")
		$letexte = preg_replace(',(<p\s.*)(</?(STOP P|'._BALISES_BLOCS.')[>[:space:]]),UimsS',
			"\n\\1</p>\n\\2", $letexte);

		// Supprimer les marqueurs "STOP P"
		$letexte = str_replace('<STOP P>', '', $letexte);

		// Reduire les blancs dans les <p>
		$u = @$GLOBALS['meta']['pcre_u'];
		$letexte = preg_replace(',(<p\b.*>)\s*,UiS'.$u, '\1',$letexte);
		$letexte = preg_replace(',\s*(</p\b.*>),UiS'.$u, '\1',$letexte);

		// Supprimer les <p xx></p> vides
		$letexte = preg_replace(',<p\b[^<>]*></p>\s*,iS'.$u, '',
			$letexte);

		// Renommer les paragraphes normaux
		$letexte = str_replace('<p >', "<p$class_spip>",
			$letexte);

	}

	return $letexte;
}

// http://doc.spip.org/@traiter_poesie
function traiter_poesie($letexte)
{
	if (preg_match_all(",<(poesie|poetry)>(.*)<\/(poesie|poetry)>,UimsS",
	$letexte, $regs, PREG_SET_ORDER)) {
		$u = "/\n[\s]*\n/S" . $GLOBALS['meta']['pcre_u'];
		foreach ($regs as $reg) {
			$lecode = preg_replace(",\r\n?,S", "\n", $reg[2]);
			$lecode = preg_replace($u, "\n&nbsp;\n",$lecode);
			$lecode = "<blockquote class=\"spip_poesie\">\n<div>"
				.preg_replace("/\n+/", "</div>\n<div>", trim($lecode))
				."</div>\n</blockquote>\n\n";
			$letexte = str_replace($reg[0], $lecode, $letexte);
		}
	}
	return $letexte;
}

// Harmonise les retours chariots et mange les paragraphes html
// http://doc.spip.org/@traiter_retours_chariots
function traiter_retours_chariots($letexte) {
	$letexte = preg_replace(",\r\n?,S", "\n", $letexte);
	$letexte = preg_replace(",<p[>[:space:]],iS", "\n\n\\0", $letexte);
	$letexte = preg_replace(",</p[>[:space:]],iS", "\\0\n\n", $letexte);
	return $letexte;
}

// Ces deux constantes permettent de proteger certains caracteres
// en les remplacanat par des caracteres "illegaux". (cf corriger_caracteres)

define('_RACCOURCI_PROTEGER', "{}_-");
define('_RACCOURCI_PROTECTEUR', "\x1\x2\x3\x4");

define('_RACCOURCI_BALISE', ",</?[a-z!][^<>]*[".preg_quote(_RACCOURCI_PROTEGER)."][^<>]*>,imsS");

// Nettoie un texte, traite les raccourcis autre qu'URL, la typo, etc.
// http://doc.spip.org/@traiter_raccourcis
function traiter_raccourcis($letexte) {

	// Appeler les fonctions de pre_traitement
	$letexte = pipeline('pre_propre', $letexte);

	// Gerer les notes (ne passe pas dans le pipeline)
	$notes = charger_fonction('notes', 'inc');
	list($letexte, $mes_notes) = $notes($letexte);

	//
	// Tableaux
	//

	// ne pas oublier les tableaux au debut ou a la fin du texte
	$letexte = preg_replace(",^\n?[|],S", "\n\n|", $letexte);
	$letexte = preg_replace(",\n\n+[|],S", "\n\n\n\n|", $letexte);
	$letexte = preg_replace(",[|](\n\n+|\n?$),S", "|\n\n\n\n", $letexte);

	if (preg_match_all(',[^|](\n[|].*[|]\n)[^|],UmsS', $letexte,
	$regs, PREG_SET_ORDER))
	foreach ($regs as $t) {
		$letexte = str_replace($t[1], traiter_tableau($t[1]), $letexte);
	}

	$letexte = "\n".trim($letexte);

	// les listes
	if (strpos($letexte,"\n-*")!==false OR strpos($letexte,"\n-#")!==false)
		$letexte = traiter_listes($letexte);

	// Proteger les caracteres actifs a l'interieur des tags html

	if (preg_match_all(_RACCOURCI_BALISE, $letexte, $regs, PREG_SET_ORDER)) {
		foreach ($regs as $reg) {
			$insert = strtr($reg[0], _RACCOURCI_PROTEGER, _RACCOURCI_PROTECTEUR);
			$letexte = str_replace($reg[0], $insert, $letexte);
		}
	}

	// Traitement des alineas
	list($a,$b) = definir_raccourcis_alineas();
	$letexte = preg_replace($a, $b,	$letexte);
	//  Introduction des attributs class_spip* et autres raccourcis
	list($a,$b) = $GLOBALS['spip_raccourcis_typo'];
	$letexte = preg_replace($a, $b,	$letexte);
	$letexte = preg_replace('@^\n<br />@S', '', $letexte);

	// Retablir les caracteres proteges
	$letexte = strtr($letexte, _RACCOURCI_PROTECTEUR, _RACCOURCI_PROTEGER);

	// Fermer les paragraphes ; mais ne pas forcement en creer si un seul
	$letexte = paragrapher($letexte, $GLOBALS['toujours_paragrapher']);

	// Appeler les fonctions de post-traitement
	$letexte = pipeline('post_propre', $letexte);

	if ($mes_notes) $notes($mes_notes);

	return $letexte;
}



// Filtre a appliquer aux champs du type #TEXTE*
// http://doc.spip.org/@propre
function propre($t, $connect=null) {
	// les appels directs a cette fonction depuis le php de l'espace
	// prive etant historiquement ecrits sans argment $connect
	// on utilise la presence de celui-ci pour distinguer les cas
	// ou il faut passer interdire_script explicitement
	// les appels dans les squelettes (de l'espace prive) fournissant un $connect
	// ne seront pas perturbes
	$interdire_script = false;
	if (is_null($connect)){
		$connect = '';
		$interdire_script = true;
	}

	return !$t ? strval($t) :
		echappe_retour_modeles(
			traiter_raccourcis(
				expanser_liens(echappe_html($t),$connect)),$interdire_script);
}
?>
