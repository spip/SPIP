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

// Verifier les variables de personnalisation
tester_variable('debut_intertitre', "\n<h3 class=\"spip\">");
tester_variable('fin_intertitre', "</h3>\n");
tester_variable('ligne_horizontale', "\n<hr class=\"spip\" />\n");
tester_variable('ouvre_ref', '&nbsp;[');
tester_variable('ferme_ref', ']');
tester_variable('ouvre_note', '[');
tester_variable('ferme_note', '] ');
tester_variable('les_notes', '');
tester_variable('compt_note', 0);
tester_variable('nombre_surligne', 4);
tester_variable('url_glossaire_externe', "http://@lang@.wikipedia.org/wiki/");

// on initialise la puce ici car il serait couteux de faire le find_in_path()
// a chaque hit, alors qu'on n'a besoin de cette valeur que lors du calcul
function definir_puce() {
	static $les_puces = array();

	// Attention au sens, qui n'est pas defini de la meme facon dans
	// l'espace prive (spip_lang est la langue de l'interface, lang_dir
	// celle du texte) et public (spip_lang est la langue du texte)
	include_spip('inc/lang');
	$dir = _DIR_RESTREINT ?
		lang_dir($GLOBALS['spip_lang']) : $GLOBALS['lang_dir'];
	$p = ($dir == 'rtl') ? 'puce_rtl' : 'puce';

	if (!isset($les_puces[$p])) {
		tester_variable($p, 'AUTO');
		if ($GLOBALS[$p] == 'AUTO') {
			$img = find_in_path($p.'.gif', _DIR_IMG_PACK);
			list(,,,$size) = @getimagesize($img);
			$img = '<img src="'.$img.'" '
				.$size.' alt="-" border="0" />';
		} else
			$img = $GLOBALS[$p];

		$les_puces[$p] = $img;
	}

	return $les_puces[$p];
}

//
// Diverses fonctions essentielles
//


// XHTML - Preserver les balises-bloc
define('_BALISES_BLOCS',
	'div|pre|ul|li|blockquote|h[1-5r]|'
	.'t(able|[rdh]|body|foot|extarea)|'
	.'form|object|center|marquee|address|'
	.'d[ltd]|script|noscript|map|del|ins|button|fieldset');


// Ne pas afficher le chapo si article virtuel
function nettoyer_chapo($chapo){
	if (substr($chapo,0,1) == "="){
		$chapo = "";
	}
	return $chapo;
}


//
// Echapper les les elements perilleux en les passant en base64
//

// Creer un bloc base64 correspondant a $rempl ; au besoin en marquant
// une $source differente ; le script detecte automagiquement si ce qu'on
// echappe est un div ou un span
function code_echappement($rempl, $source='') {
	// Convertir en base64
	$base64 = base64_encode($rempl);

	// Tester si on echappe en span ou en div
	$mode = preg_match(',</?('._BALISES_BLOCS.')[>[:space:]],i', $rempl) ?
		'div' : 'span';
	$nn = ($mode == 'div') ? "\n\n" : '';

	return
		inserer_attribut("<$mode class=\"base64$source\">", 'title', $base64)
		."</$mode>$nn";
}

// - pour $source voir commentaire infra (echappe_retour)
// - pour $no_transform voir le filtre post_autobr dans inc_filtres.php3
function echappe_html($letexte, $source='', $no_transform=false) {
	if (preg_match_all(
	',<(html|code|cadre|frame)>(.*)</\1>,Uims',
	$letexte, $matches, PREG_SET_ORDER))
	foreach ($matches as $regs) {

		// mode d'echappement :
		//    <span class='base64'> . base64_encode(contenu) . </span>
		// ou 'div' selon les cas, pour refermer correctement les paragraphes
		$mode = 'span';

		// echappements tels quels ?
		if ($no_transform) {
			$echap = $regs[0];
		}

		// sinon les traiter selon le cas
		else switch(strtolower($regs[1])) {

			// Echapper les <html>...</ html>
			case 'html':
				$echap = $regs[2];
				break;

			// Echapper les <code>...</ code>
			case 'code':
				$echap = entites_html($regs[2]);
				// supprimer les sauts de ligne debut/fin
				// (mais pas les espaces => ascii art).
				$echap = ereg_replace("^\n+|\n+$", "", $echap);

				// ne pas mettre le <div...> s'il n'y a qu'une ligne
				if (is_int(strpos($echap,"\n"))) {
					$echap = nl2br("<div style='text-align: left;' "
					. "class='spip_code' dir='ltr'><code>"
					.$echap."</code></div>");
					$mode = 'div';
				} else
					$echap = "<span class='spip_code' "
					."dir='ltr'><code>".$echap."</code></span>";

				$echap = str_replace("\t",
					"&nbsp; &nbsp; &nbsp; &nbsp; ", $echap);
				$echap = str_replace("  ", " &nbsp;", $echap);
				break;

			// Echapper les <cadre>...</ cadre>
			case 'cadre':
			case 'frame':
				$echap = trim(entites_html($regs[2]));
				$total_lignes = substr_count($echap, "\n") + 1;
				$echap = "<form action=\"/\" method=\"get\"><div>"
				."<textarea readonly='readonly' cols='40' rows='$total_lignes' "
				."class='spip_cadre' dir='ltr'>"
				.$echap
				."</textarea></div></form>";
				break;

		}

		$letexte = str_replace($regs[0],
			code_echappement($echap, $source),
			$letexte);
	}

	// Gestion du TeX
	if (strpos($letexte, "<math>") !== false) {
		include_spip('inc/math');
		$letexte = traiter_math($letexte, $source);
	}

	return $letexte;
}


//
// Traitement final des echappements
// Rq: $source sert a faire des echappements "a soi" qui ne sont pas nettoyes
// par propre() : exemple dans ecrire/inc_articles_ortho.php, $source='ORTHO'
// ou encore dans typo()
function echappe_retour($letexte, $source='') {
	if (strpos($letexte,"base64$source")) {
		# echo htmlspecialchars($letexte);  ## pour les curieux
		if (preg_match_all(
		',<(span|div) class=[\'"]base64'.$source.'[\'"]\s.*></\1>,Ums',
		$letexte, $regs, PREG_SET_ORDER)) {
			foreach ($regs as $reg) {
				$rempl = base64_decode(extraire_attribut($reg[0], 'title'));
				$letexte = str_replace($reg[0], $rempl, $letexte);
			}
		}
	}
	return $letexte;
}


//
// Gerer les outils mb_string
//
function spip_substr($c, $start=0, $end='') {
	include_spip('inc/charsets');
	if (init_mb_string()) {
		if ($end)
			return mb_substr($c, $start, $end);
		else
			return mb_substr($c, $start);
	}

	// methode substr normale
	else {
		if ($end)
			return substr($c, $start, $end);
		else
			return substr($c, $start);
	}
}

function spip_strlen($c) {
	include_spip('inc/charsets');
	if (init_mb_string())
		return mb_strlen($c);
	else
		return strlen($c);
}
// fin mb_string


function couper($texte, $taille=50) {
	$texte = substr($texte, 0, 400 + 2*$taille); /* eviter de travailler sur 10ko pour extraire 150 caracteres */

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
	$texte = filtrer_entites($texte);

	// remplacer les liens
	if (preg_match_all(',[[]([^][]*)->(>?)([^][]*)[]],', $texte, $regs, PREG_SET_ORDER))
		foreach ($regs as $reg) {
			if (strlen($reg[1]))
				$titre = $reg[1];
			else
				list(,,$titre) = extraire_lien($reg);
			$texte = str_replace($reg[0], $titre, $texte);
		}

	// supprimer les notes
	$texte = ereg_replace("\[\[([^]]|\][^]])*\]\]", "", $texte);

	// supprimer les codes typos
	$texte = ereg_replace("[}{]", "", $texte);

	// supprimer les tableaux
	$texte = ereg_replace("(^|\r)\|.*\|\r", "\r", $texte);

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
	$texte = preg_replace('/&#?[a-z0-9]*$/', '', $texte);

	return trim($texte).$points;
}

// prendre <intro>...</intro> sinon couper a la longueur demandee
function couper_intro($texte, $long) {
	$texte = extraire_multi(eregi_replace("(</?)intro>", "\\1intro>", $texte)); // minuscules
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
		$intro = preg_replace(',([|]\s*)+,', '; ', couper($texte, $long));
	}

	// supprimer un eventuel chapo redirecteur =http:/.....
	$intro = preg_replace(',^=[^[:space:]]+,','',$intro);

	return $intro;
}


//
// Les elements de propre()
//

// Securite : empecher l'execution de code PHP ou javascript ou autre malice
function interdire_scripts($source) {
	$source = preg_replace(",<(\%|\?|/?[[:space:]]*(script|base)),ims", "&lt;\\1", $source);
	return $source;
}

// Securite : utiliser SafeHTML s'il est present dans ecrire/safehtml/
function safehtml($t) {
	static $process, $test;

	# attention safehtml nettoie deux ou trois caracteres de plus. A voir
	if (strpos($t,'<')===false)
		return str_replace("\x00", '', $t);

	if (!$test) {
		if ($f = include_spip('safehtml/classes/safehtml.php')) {
			define('XML_HTMLSAX3', dirname($f).'/');
			$process = new safehtml();
		}
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
function typo_fr($letexte) {
	static $trans;

	// Nettoyer 160 = nbsp ; 187 = raquo ; 171 = laquo ; 176 = deg ; 147 = ldquo; 148 = rdquo
	if (!$trans) {
		$trans = array(
			"&nbsp;" => "~",
			"&raquo;" => "&#187;",
			"&laquo;" => "&#171;",
			"&rdquo;" => "&#148;",
			"&ldquo;" => "&#147;",
			"&deg;" => "&#176;"
		);
		$chars = array(160 => '~', 187 => '&#187;', 171 => '&#171;', 148 => '&#148;', 147 => '&#147;', 176 => '&#176;');

		include_spip('inc/charsets');
		while (list($c, $r) = each($chars)) {
			$c = unicode2charset(charset2unicode(chr($c), 'iso-8859-1', 'forcer'));
			$trans[$c] = $r;
		}
	}

	$letexte = strtr($letexte, $trans);

	$cherche1 = array(
		/* 1 */ 	'/((^|[^\#0-9a-zA-Z\&])[\#0-9a-zA-Z]*)\;/',
		/* 2 */		'/&#187;| --?,|:([^0-9]|$)/',
		/* 3 */		'/([^[<!?])([!?])/',
		/* 4 */		'/&#171;|(M(M?\.|mes?|r\.?)|[MnN]&#176;) /'
	);
	$remplace1 = array(
		/* 1 */		'\1~;',
		/* 2 */		'~\0',
		/* 3 */		'\1~\2',
		/* 4 */		'\0~'
	);
	$letexte = preg_replace($cherche1, $remplace1, $letexte);
	$letexte = ereg_replace(" *~+ *", "~", $letexte);

	$cherche2 = array(
		'/([^-\n]|^)--([^-]|$)/',
		'/(http|https|ftp|mailto)~:/',
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
function typo_en($letexte) {

	$cherche1 = array(
		'/ --?,/'
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
function typo($letexte, $echapper=true) {

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
	if (preg_match_all(",</?[a-z!][^<>]*[!':;\?~][^<>]*>,ims",
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
	// $lang_typo est fixee dans l'interface privee pour editer
	// un texte anglais en interface francaise (ou l'inverse) ;
	// sinon determiner la typo en fonction de la langue
	if (!$lang = $GLOBALS['lang_typo']) {
		include_spip('inc/lang');
		$lang = lang_typo($GLOBALS['spip_lang']);
	}
	if ($lang == 'fr')
		$letexte = typo_fr($letexte);
	else
		$letexte = typo_en($letexte);

	// Retablir les caracteres proteges
	$letexte = strtr($letexte, $illegal, $protege);

	//
	// Installer les images et documents ;
	//
	// NOTE : dans propre() ceci s'execute avant les tableaux a cause du "|",
	// et apres les liens a cause du traitement de [<imgXX|right>->URL]
	if (preg_match(__preg_img, $letexte)) {
		include_spip('inc/documents');
		$letexte = inserer_documents($letexte);
	}

	// Appeler les fonctions de post-traitement
	$letexte = pipeline('post_typo', $letexte);
	// old style
	if (function_exists('apres_typo'))
		$letexte = apres_typo($letexte);

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


// cette fonction est tordue : on lui passe un tableau correspondant au match
// de la regexp ci-dessous, et elle retourne le texte a inserer a la place
// et le lien "brut" a usage eventuel de redirection...
function extraire_lien ($regs) {
	$lien_texte = $regs[1];

	$lien_url = entites_html(trim($regs[3]));
	$compt_liens++;
	$lien_interne = false;
	if (ereg('^[[:space:]]*(art(icle)?|rub(rique)?|br(.ve)?|aut(eur)?|mot|site|doc(ument)?|im(age|g))?[[:space:]]*([[:digit:]]+)(#.*)?[[:space:]]*$', $lien_url, $match)) {
		$id_lien = $match[8];
		$ancre = $match[9];
		$type_lien = substr($match[1], 0, 2);
		$lien_interne=true;
		$class_lien = "in";
		charger_generer_url();
		switch ($type_lien) {
			case 'ru':
				$lien_url = generer_url_rubrique($id_lien);
				if (!$lien_texte) {
					$req = "select titre from spip_rubriques where id_rubrique=$id_lien";
					$row = @spip_fetch_array(@spip_query($req));
					$lien_texte = $row['titre'];
				}
				break;
			case 'br':
				$lien_url = generer_url_breve($id_lien);
				if (!$lien_texte) {
					$req = "select titre from spip_breves where id_breve=$id_lien";
					$row = @spip_fetch_array(@spip_query($req));
					$lien_texte = $row['titre'];
				}
				break;
			case 'au':
				$lien_url = generer_url_auteur($id_lien);
				if (!$lien_texte) {
					$req = "select nom from spip_auteurs where id_auteur = $id_lien";
					$row = @spip_fetch_array(@spip_query($req));
					$lien_texte = $row['nom'];
				}
				break;
			case 'mo':
				$lien_url = generer_url_mot($id_lien);
				if (!$lien_texte) {
					$req = "select titre from spip_mots where id_mot=$id_lien";
					$row = @spip_fetch_array(@spip_query($req));
					$lien_texte = $row['titre'];
				}
				break;
			case 'im':
			case 'do':
				$lien_url = generer_url_document($id_lien);
				if (!$lien_texte) {
					$req = "select titre,fichier from spip_documents
					WHERE id_document=$id_lien";
					$row = @spip_fetch_array(@spip_query($req));
					$lien_texte = $row['titre'];
					if (!$lien_texte)
						$lien_texte = ereg_replace("^.*/","",$row['fichier']);
				}
				break;
			case 'si':
				# attention dans le cas des sites le lien pointe non pas sur
				# la page locale du site, mais directement sur le site lui-meme
				$row = @spip_fetch_array(@spip_query("SELECT nom_site,url_site
				FROM spip_syndic WHERE id_syndic=$id_lien"));
				if ($row) {
					$lien_url = $row['url_site'];
					if (!$lien_texte)
						$lien_texte = typo($row['nom_site']);
				}
				break;
			default:
				$lien_url = generer_url_article($id_lien);
				if (!$lien_texte) {
					$req = "select titre from spip_articles where id_article=$id_lien";
					$row = @spip_fetch_array(@spip_query($req));
					$lien_texte = $row['titre'];

				}
				break;
		}

		$lien_url .= $ancre;

		// supprimer les numeros des titres
		$lien_texte = supprimer_numero($lien_texte);
	}
	else if (preg_match(',^\?(.*)$,s', $lien_url, $regs)) {
		// Liens glossaire
		$lien_url = substr($lien_url, 1);
		$class_lien = "glossaire";
	}
	else {
		// Liens non automatiques
		$class_lien = "out";
		// texte vide ?
		if ((!$lien_texte) and (!$lien_interne)) {
			$lien_texte = str_replace('"', '', $lien_url);
			if (strlen($lien_texte)>40)
				$lien_texte = substr($lien_texte,0,35).'...';
			$class_lien = "url";
			$lien_texte = "<html>$lien_texte</html>";
		}
		// petites corrections d'URL
		if (preg_match(",^www\.[^@]+$,",$lien_url))
			$lien_url = "http://".$lien_url;
		else if (strpos($lien_url, "@") && email_valide($lien_url))
			$lien_url = "mailto:".$lien_url;
	}

	// Preparer le texte du lien ; attention s'il contient un <div>
	// (ex: [<docXX|right>->lien]), il faut etre smart
	$insert = typo("<a href=\"$lien_url\" class=\"spip_$class_lien\""
		.">$lien_texte</a>");

	return array($insert, $lien_url, $lien_texte);
}

//
// Tableaux
//
function traiter_tableau($bloc) {

	// Decouper le tableau en lignes
	preg_match_all(',([|].*)[|]\n,Ums', $bloc, $regs, PREG_PATTERN_ORDER);
	$lignes = array();

	// Traiter chaque ligne
	foreach ($regs[1] as $ligne) {
		$l ++;

		// Gestion de la premiere ligne :
		if ($l == 1) {
		// - <caption> et summary dans la premiere ligne :
		//   || caption | summary || (|summary est optionnel)
			if (preg_match(',^\|\|([^|]*)(\|(.*))?\|$,s', $ligne, $cap)) {
				$l = 0;
				if ($caption = trim($cap[1]))
					$debut_table .= "<caption>".$caption."</caption>\n";
				$summary = ' summary="'.entites_html(trim($cap[3])).'"';
			}
		// - <thead> sous la forme |{{titre}}|{{titre}}|
		//   Attention thead oblige a avoir tbody
			else if (preg_match(',^(\|([[:space:]]*{{[^}]+}}[[:space:]]*|<))+$,s',
				$ligne, $thead)) {
			  	preg_match_all("/\|([^|]*)/", $ligne, $cols);
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
			$ligne = preg_replace(",\n\n+,", "<br />\n", $ligne);

			// tout mettre dans un tableau 2d
			preg_match_all("/\|([^|]*)/", $ligne, $cols);
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
function traiter_listes ($texte) {
	$parags = preg_split(",\n[[:space:]]*\n,", $texte);
	unset($texte);

	// chaque paragraphe est traite a part
	while (list(,$para) = each($parags)) {
		$niveau = 0;
		$lignes = explode("\n-", "\n" . $para);

		// ne pas toucher a la premiere ligne
		list(,$debut) = each($lignes);
		$texte .= $debut;

		// chaque item a sa profondeur = nb d'etoiles
		unset ($type);
		while (list(,$item) = each($lignes)) {
			preg_match(",^([*]*|[#]*)([^*#].*)$,s", $item, $regs);
			$profond = strlen($regs[1]);

			if ($profond > 0) {
				unset ($ajout);

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
		unset ($ajout);
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

// Definition de la regexp des images/documents
define('__preg_img', ',<(img|doc|emb)([0-9]+)(\|([^>]*))?'.'>,i');

// fonction en cas de texte extrait d'un serveur distant:
// on ne sait pas (encore) rapatrier les documents joints

function supprime_img($letexte) {
	$message = _T('img_indisponible');
	preg_replace(__preg_img, "($message)", $letexte);
	return $letexte;
}


//
// Une fonction pour fermer les paragraphes ; on essaie de preserver
// des paragraphes indiques a la main dans le texte
// (par ex: on ne modifie pas un <p align='center'>)
//
function paragrapher($letexte) {

	if (preg_match(',<p[>[:space:]],i',$letexte)) {

		// Ajouter un espace aux <p> et un "STOP P"
		// transformer aussi les </p> existants en <p>, nettoyes ensuite
		$letexte = preg_replace(',</?p(\s([^>]*))?'.'>,i', '<STOP P><p \2>',
			'<p>'.$letexte.'<STOP P>');

		// Fermer les paragraphes (y compris sur "STOP P")
		$letexte = preg_replace(
			',(<p\s.*)(</?(STOP P|'._BALISES_BLOCS.')[>[:space:]]),Uims',
			"\n\\1</p>\n\\2", $letexte);

		// Supprimer les marqueurs "STOP P"
		$letexte = str_replace('<STOP P>', '', $letexte);

		// Reduire les blancs dans les <p>
		$letexte = preg_replace(
		',(<p(>|\s[^>]*)>)\s*|\s*(</p[>[:space:]]),i', '\1\3',
			$letexte);

		// Supprimer les <p xx></p> vides
		$letexte = preg_replace(',<p\s[^>]*></p>\s*,i', '',
			$letexte);

		// Renommer les paragraphes normaux avec class="spip"
		$letexte = str_replace('<p >', '<p class="spip">',
			$letexte);

	}

	return $letexte;
}

// Nettoie un texte, traite les raccourcis spip, la typo, etc.
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

	// Appeler les fonctions de pre_traitement
	$letexte = pipeline('pre_propre', $letexte);
	// old style
	if (function_exists('avant_propre'))
		$letexte = avant_propre($letexte);


	// Gestion de la <poesie>
	if (preg_match_all(",<(poesie|poetry)>(.*)<\/(poesie|poetry)>,Uims",
	$letexte, $regs, PREG_SET_ORDER)) {
		foreach ($regs as $reg) {
			$lecode = preg_replace(",\r\n?,", "\n", $reg[2]);
			$lecode = ereg_replace("\n[[:space:]]*\n", "\n&nbsp;\n",$lecode);
			$lecode = "<div class=\"spip_poesie\">\n<div>".ereg_replace("\n+", "</div>\n<div>", trim($lecode))."</div>\n</div>\n\n";
			$letexte = str_replace($reg[0], $lecode, $letexte);
		}
	}

	// Harmoniser les retours chariot
	$letexte = preg_replace(",\r\n?,", "\n", $letexte);

	// Recuperer les para HTML
	$letexte = preg_replace(",<p[>[:space:]],i", "\n\n\\0", $letexte);
	$letexte = preg_replace(",</p[>[:space:]],i", "\\0\n\n", $letexte);

	//
	// Notes de bas de page
	//
	$regexp = ', *\[\[(.*?)\]\],ms';
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
			$ancre = $mn.urlencode($num_note);

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

			$appel = "$ouvre_note<a href=\"#nh$ancre\" name=\"nb$ancre\" class=\"spip_note\">$num_note</a>$ferme_note";
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
		$regexp = "|\[\?+([^][<>]+)\]|";
		if (preg_match_all($regexp, $letexte, $matches, PREG_SET_ORDER))
		foreach ($matches as $regs) {
			$terme = trim($regs[1]);
			$terme_underscore = urlencode(preg_replace(',\s+,', '_', $terme));
			if (strstr($url_glossaire_externe,"%s"))
				$url = str_replace("%s", $terme_underscore, $url_glossaire_externe);
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
	// Raccourcis liens [xxx->url] (cf. fonction extraire_lien ci-dessus)
	// Note : complique car c'est ici qu'on applique typo() !
	//
	$regexp = "|\[([^][]*)->(>?)([^]]*)\]|ms";
	$inserts = array();
	if (preg_match_all($regexp, $letexte, $matches, PREG_SET_ORDER)) {
		$i = 0;
		foreach ($matches as $regs) {
			list($insert) = extraire_lien($regs);
			$inserts[++$i] = $insert;
			$letexte = str_replace($regs[0], "@@SPIP_ECHAPPE_LIEN_$i@@", $letexte);
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
	$letexte = preg_replace(",^\n?[|],", "\n\n|", $letexte);
	$letexte = preg_replace(",\n\n+[|],", "\n\n\n\n|", $letexte);
	$letexte = preg_replace(",[|](\n\n+|\n?$),", "|\n\n\n\n", $letexte);

	// traiter chaque tableau
	if (preg_match_all(',[^|](\n[|].*[|]\n)[^|],Ums', $letexte,
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

	// autres raccourcis
	$cherche1 = array(
		/* 0 */ 	"/\n(----+|____+)/",
		/* 1 */ 	"/\n-- */",
		/* 2 */ 	"/\n- */",
		/* 3 */ 	"/\n_ +/",
		/* 4 */ 	"/[{][{][{]/",
		/* 5 */ 	"/[}][}][}]/",
		/* 6 */ 	"/(( *)\n){2,}(<br[[:space:]]*\/?".">)?/",
		/* 7 */ 	"/[{][{]/",
		/* 8 */ 	"/[}][}]/",
		/* 9 */ 	"/[{]/",
		/* 10 */	"/[}]/",
		/* 11 */	"/(<br[[:space:]]*\/?".">){2,}/",
		/* 12 */	"/<p>([\n]*(<br[[:space:]]*\/?".">)*)*/",
		/* 13 */	"/<quote>/",
		/* 14 */	"/<\/quote>/"
	);
	$remplace1 = array(
		/* 0 */ 	"\n\n$ligne_horizontale\n\n",
		/* 1 */ 	"\n<br />&mdash;&nbsp;",
		/* 2 */ 	"\n<br />$puce&nbsp;",
		/* 3 */ 	"\n<br />",
		/* 4 */ 	"\n\n$debut_intertitre",
		/* 5 */ 	"$fin_intertitre\n\n",
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
	$letexte = preg_replace("@^ <br />@", "", $letexte);


	// Fermer les paragraphes
	$letexte = paragrapher($letexte);

	// Appeler les fonctions de post-traitement
	$letexte = pipeline('post_propre', $letexte);
	// old style
	if (function_exists('apres_propre'))
		$letexte = apres_propre($letexte);

	if ($mes_notes) traiter_les_notes($mes_notes);

	return $letexte;
}

function traiter_les_notes($mes_notes) {
	$mes_notes = propre('<p>'.$mes_notes);
	$mes_notes = str_replace(
		'<p class="spip">', '<p class="spip_note">', $mes_notes);
	$GLOBALS['les_notes'] .= $mes_notes;
}


// Filtre a appliquer aux champs du type #TEXTE*
function propre($letexte) {
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
