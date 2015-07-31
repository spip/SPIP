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
if (defined("_ECRIRE_INC_TEXTE")) return;
define("_ECRIRE_INC_TEXTE", "1");

include_ecrire("inc_filtres.php3");

//
// Initialisation de quelques variables globales
// (on peut les modifier globalement dans mes_fonctions.php3,
//  OU individuellement pour chaque type de page dans article.php3,
//  rubrique.php3, etc. cf doc...)
//
function tester_variable($nom_var, $val){
	if (!isset($GLOBALS[$nom_var])) {
		$GLOBALS[$nom_var] = $val;
		return false;
	}
	return true;
}

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


// On ne prend la $puce_rtl par defaut que si $puce n'a pas ete redefinie

//if (!tester_variable('puce', "<li class='spip_puce' style='list-style-image: url(puce.gif)'>")) {
if (!tester_variable('puce', "<img class='spip_puce' src='puce.gif' alt='-' />&nbsp;")) {
	tester_variable('puce_rtl', "<img class='spip_puce' src='puce_rtl.gif' alt='-' />&nbsp;");
}


//
// Diverses fonctions essentielles
//

// Ne pas afficher le chapo si article virtuel
function nettoyer_chapo($chapo){
	if (substr($chapo,0,1) == "="){
		$chapo = "";
	}
	return $chapo;
}

// points d'entree de pre- et post-traitement pour propre() et typo()
function spip_avant_propre ($letexte) {
	$letexte = avant_propre_ancres($letexte);
	$letexte = extraire_multi($letexte);

	if (function_exists('avant_propre'))
		$letexte = avant_propre($letexte);

	return $letexte;
}

function spip_apres_propre ($letexte) {
	if (function_exists('apres_propre'))
		$letexte = apres_propre($letexte);

	return $letexte;
}

function spip_avant_typo ($letexte) {
	$letexte = extraire_multi($letexte);
	$letexte = avant_typo_smallcaps($letexte);

	if (function_exists('avant_typo'))
		$letexte = avant_typo($letexte);

	return $letexte;
}

function spip_apres_typo ($letexte) {

	// relecture des &nbsp;
	if (!_DIR_RESTREINT AND $GLOBALS['revision_nbsp'])
		$letexte = str_replace('&nbsp;',
			'<span class="spip-nbsp">&nbsp;</span>', $letexte);

	if (function_exists('apres_typo'))
		$letexte = apres_typo($letexte);

	return $letexte;
}


//
// Mise de cote des echappements
//

// Definition de la regexp de echappe_html
define ('__regexp_echappe',
		"/(" . "<html>((.*?))<\/html>" . ")|("	#html
		. "<code>((.*?))<\/code>" . ")|("	#code
		. "<(cadre|frame)>((.*?))<\/(cadre|frame)>" #cadre
		. ")|("
		. "<(poesie|poetry)>((.*?))<\/(poesie|poetry)>" #poesie
		. ")/si");
define('__preg_img', ',<(img|doc|emb)([0-9]+)(\|([^>]*))?'.'>,i');

function echappe_html($letexte, $source='SOURCEPROPRE', $no_transform=false) {
	if (preg_match_all(__regexp_echappe, $letexte, $matches, PREG_SET_ORDER))
	foreach ($matches as $regs) {
		$num_echap++;
		$marqueur_echap = "@@SPIP_$source$num_echap@@";

		if ($no_transform) {	// echappements bruts
			$les_echap[$num_echap] = $regs[0];
		}
		else
		if ($regs[1]) {
			// Echapper les <html>...</ html>
			$les_echap[$num_echap] = $regs[2];
		}
		else
		if ($regs[4]) {
			// Echapper les <code>...</ code>
			$lecode = entites_html($regs[5]);

			// supprimer les sauts de ligne debut/fin (mais pas les espaces => ascii art).
			$lecode = ereg_replace("^\n+|\n+$", "", $lecode);

			// ne pas mettre le <div...> s'il n'y a qu'une ligne
			if (is_int(strpos($lecode,"\n"))) {
				$lecode = nl2br("<div style='text-align: left;' class='spip_code' dir='ltr'><tt>".$lecode."</tt></div>");
				$marqueur_echap = "</no p>$marqueur_echap<no p>";
			} else
				$lecode = "<span class='spip_code' dir='ltr'><tt>".$lecode."</tt></span>";

			$lecode = str_replace("\t", "&nbsp; &nbsp; &nbsp; &nbsp; ", $lecode);
			$lecode = str_replace("  ", " &nbsp;", $lecode);
			$les_echap[$num_echap] = $lecode;
		}
		else
		if ($regs[7]) {
			// Echapper les <cadre>...</cadre>
			$lecode = trim(entites_html($regs[9]));
			$total_lignes = substr_count($lecode, "\n");

			$les_echap[$num_echap] = "<form action=\"/\" method=\"get\"><div><textarea readonly='readonly' cols='40' rows='$total_lignes' class='spip_cadre' dir='ltr'>".$lecode."</textarea></div></form>";
			// Les marques ci-dessous indiquent qu'on ne veut pas paragrapher
			$marqueur_echap = "\n\n</no p>$marqueur_echap<no p>\n\n";
		}
		else
		if ($regs[12]) {
			$lecode = $regs[14];
			$lecode = ereg_replace("\n[[:space:]]*\n", "\n&nbsp;\n",$lecode);
			$lecode = str_replace("\r", "\n", $lecode); # gestion des \r a revoir !
			$lecode = "<div class=\"spip_poesie\"><div>".ereg_replace("\n+", "</div>\n<div>", $lecode)."</div></div>";
			$marqueur_echap = "\n\n</no p>$marqueur_echap<no p>\n\n";
			$les_echap[$num_echap] = propre($lecode);
		} 

		$letexte = str_replace($regs[0], $marqueur_echap, $letexte);
	}

	// Gestion du TeX
	if (!(strpos($letexte, "<math>") === false)) {
		include_ecrire("inc_math.php3");
		$letexte = traiter_math($letexte, $les_echap, $num_echap, $source);
	}

	// Traitement des images et documents <IMGxx|right>
	// (insertion dans echappe_retour pour faciliter les doublons)
	if (preg_match_all(__preg_img, $letexte, $matches, PREG_SET_ORDER)) {
		foreach ($matches as $match) {
			$num_echap++;

			// Si le resultat contient des <div>, forcer la sortie du paragraphe
			if ( $match[1] == "doc" OR $match[1] == "emb" OR eregi("^\|center", $match[3]) )  {
				$letexte = str_replace($match[0],
					"\n\n</no p>@@SPIP_$source$num_echap@@<no p>\n\n", $letexte);
			} else {
			$letexte = str_replace($match[0],
				"@@SPIP_$source$num_echap@@", $letexte);
			}
			$les_echap[$num_echap] = $match;
		}
	}

	return array($letexte, $les_echap);
}

// Traitement final des echappements
function echappe_retour($letexte, $les_echap, $source='') {
	$expr = ",@@SPIP_$source([0-9]+)@@,";
	if (preg_match_all($expr, $letexte, $regs, PREG_SET_ORDER)) {
		foreach ($regs as $reg) {
			$rempl = $les_echap[$reg[1]];
# si $rempl est un tableau, c'est le resultat (cf echappe_html) de eregi sur :
# <(IMG|DOC|EMB)([0-9]+)(\|([^\>]*))?
			if (is_array($rempl)) {
				include_ecrire("inc_documents.php3");
				$type = strtoupper($rempl[1]);
				if ($type == 'EMB')
					$rempl = embed_document($rempl[2], $rempl[4]);
				else
					$rempl = integre_image($rempl[2], $rempl[4], $type);
					
			}
			$letexte = str_replace($reg[0], $rempl, $letexte);
		}
	}
	return $letexte;
}


// fonction en cas de texte extrait d'un serveur distant:
// on ne sait pas (encore) rapatrier les documents joints

function supprime_img($letexte) {
	$message = _T('img_indisponible');
	preg_replace(__preg_img, "($message)", $letexte);
	return $letexte;
}

# il y a 3 couples de fonctions homonymes au prefixe _doublons pres
# pour eviter a tous les appels de se trimbaler ce qui concerne les squelettes

function echappe_retour_doublon($letexte, $les_echap, $source, &$doublons)
{
  if (!$les_echap)
    return $letexte;
  foreach($les_echap as $rempl) {
    if (is_array($rempl)) # cf commentaires dans la fonction echappe_retour
      $doublons['documents'] .= "," . $rempl[2];
  }
  return echappe_retour($letexte, $les_echap, $source);
}

//
// Gerer les outils mb_string
//
function spip_substr($c, $start=0, $end='') {
	include_ecrire('inc_charsets.php3');
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
	include_ecrire('inc_charsets.php3');
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
	else
		$intro = couper($texte, $long);

	// supprimer un eventuel chapo redirecteur =http:/.....
	$intro = ereg_replace("^=[^[:space:]]+","",$intro);

	return $intro;
}


//
// Les elements de propre()
//

// Securite : empecher l'execution de code PHP
function interdire_scripts($source) {
	$source = preg_replace(",<(\%|\?|[[:space:]]*(script|base)),ims", "&lt;\\1", $source);
	return $source;
}

// Securite : utiliser SafeHTML s'il est present dans ecrire/safehtml/
function safehtml($t) {
	static $process, $test;

	# attention safehtml nettoie deux ou trois caracteres de plus. A voir
	if (strpos($t,'<')===false)
		return str_replace("\x00", '', $t);

	if (!$test) {
		define('XML_HTMLSAX3', _DIR_INCLUDE."safehtml/classes/");
		if (@file_exists(XML_HTMLSAX3.'safehtml.php')) {
			include_local(XML_HTMLSAX3.'safehtml.php');
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

		include_ecrire('inc_charsets.php3');
		while (list($c, $r) = each($chars)) {
			$c = unicode2charset(charset2unicode(chr($c), 'iso-8859-1', 'forcer'));
			$trans[$c] = $r;
		}
	}

	$letexte = strtr($letexte, $trans);

	$cherche1 = array(
		/* 1		'/{([^}]+)}/',  */
		/* 2 */ 	'/((^|[^\#0-9a-zA-Z\&])[\#0-9a-zA-Z]*)\;/',
		/* 3 */		'/&#187;| --?,|:([^0-9]|$)/',
		/* 4 */		'/([^<!?])([!?])/',
		/* 5 */		'/&#171;|(M(M?\.|mes?|r\.?)|[MnN]&#176;) /'
	);
	$remplace1 = array(
		/* 1		'<i class="spip">\1</i>', */
		/* 2 */		'\1~;',
		/* 3 */		'~\0',
		/* 4 */		'\1~\2',
		/* 5 */		'\0~'
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
//
function typo_generale($letexte) {
	global $spip_lang;

	// Appeler la fonction de pre-traitement
	$letexte = spip_avant_typo ($letexte);

	// Caracteres de controle "illegaux"
	$letexte = corriger_caracteres($letexte);

	// Proteger les caracteres typographiques a l'interieur des tags html
	$protege = "!':;?";
	$illegal = "\x1\x2\x3\x4\x5";
	if (preg_match_all("/<[a-z!][^<>!':;\?]*[!':;\?][^<>]*>/ims",
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
		include_ecrire('inc_lang.php3');
		$lang = lang_typo($spip_lang);
	}
	if ($lang == 'fr')
		$letexte = typo_fr($letexte);
	else
		$letexte = typo_en($letexte);

	// Retablir les caracteres proteges
	$letexte = strtr($letexte, $illegal, $protege);

	// Appeler la fonction de post-traitement
	$letexte = spip_apres_typo ($letexte);

	# un message pour abs_url - on est passe en mode texte
	$GLOBALS['mode_abs_url'] = 'texte';

	// et retour
	return $letexte;
}

function typo($letexte) {
	// echapper les codes <html>...</html> etc.
	list($letexte, $les_echap) = echappe_html($letexte, "SOURCETYPO");
	
	$letexte = typo_generale($letexte);
	// reintegrer les echappements
	return echappe_retour($letexte, $les_echap, "SOURCETYPO");
}

function typo_doublon(&$doublons, $letexte)
{
	// echapper les codes <html>...</html> etc.
	list($letexte, $les_echap) = echappe_html($letexte, "SOURCETYPO");
	
	$letexte = typo_generale($letexte);
	// reintegrer les echappements
	return echappe_retour_doublon($letexte, $les_echap, "SOURCETYPO", $doublons);
}

function charger_generer_url()
{
		// Traitement des liens internes
	if (!_DIR_RESTREINT)
		include_ecrire('inc_urls.php3');
	else if (@file_exists("inc-urls.php3"))
		include_local("inc-urls.php3");
	else	include_local("inc-urls-".$GLOBALS['type_urls'].".php3");
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
		include_ecrire("inc_filtres.php3");
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

	$insert = "<a href=\"$lien_url\" class=\"spip_$class_lien\""
		.">".typo($lien_texte)."</a>";

	return array($insert, $lien_url, $lien_texte);
}

//
// Tableaux
//
function traiter_tableau($bloc) {

	// Decouper le tableau en lignes
	preg_match_all(',([|].*)[|]\n,Ums', $bloc, $regs, PREG_PATTERN_ORDER);

	// Traiter chaque ligne
	foreach ($regs[1] as $ligne) {
		$l ++;

		// Gestion de la premiere ligne :
		if ($l == 1) {
		// - <caption> et summary dans la premiere ligne :
		//   || caption | summary || (|summary est optionnel)
			if (preg_match(',^\|\|([^|]*)(\|(.*))?\|$,ms', $ligne, $cap)) {
				$l = 0;
				if ($caption = trim($cap[1]))
					$debut_table .= "<caption>".$caption."</caption>\n";
				$summary = ' summary="'.entites_html(trim($cap[3])).'"';
			}
		// - <thead> sous la forme |{{titre}}|{{titre}}|
		//   Attention thead oblige a avoir tbody
			else if (preg_match(',^(\|[[:space:]]*{{[^}]+}}[[:space:]]*)+$,ms',
				$ligne, $thead)) {
				$debut_table .= "<thead>
					<tr class='row_first'>".
					preg_replace(",[|]([^|]*),",
						"<th scope='col'>\\1</th>", $ligne)
					."</tr></thead>\n";
				$l = 0;
			}
		}

		// Sinon ligne normale
		if ($l) {
			// definition du tr
			if ($l == 1
			AND ereg("^(\|[[:space:]]*[{][{][^}]+[}][}][[:space:]]*)+$", $ligne)) {
				$class = 'row_first';
			} else {
				$class = 'row_'.alterner($l, 'even', 'odd');
			}

			// Gerer les listes a puce dans les cellules
			if (ereg("\n-[*#]", $ligne))
				$ligne = traiter_listes($ligne);

			// Pas de paragraphes dans les cellules
			$ligne = preg_replace(",\n\n+,", "<br />\n", $ligne);

			// definition du <td>
			$ligne = preg_replace(",[|]([^|]*),",
				"<td>\\1</td>", $ligne);

			// ligne complete
			$html[$l] = "<tr class=\"$class\">" . $ligne . "</tr>\n";
		}
	}

	return "\n\n</no p><table class=\"spip\"$summary>\n"
		. $debut_table
		. "<tbody>\n"
		. join ('', $html)
		. "</tbody>\n"
		. "</table><no p>\n\n";
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
					$ajout .= "</no p>"."<$type class=\"spip\">";
					$pile_type[$niveau] = "</$type>"."<no p>";
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

// Nettoie un texte, traite les raccourcis spip, la typo, etc.
function traiter_raccourcis_generale($letexte) {
	global $debut_intertitre, $fin_intertitre, $ligne_horizontale, $url_glossaire_externe;
	global $compt_note;
	global $marqueur_notes;
	global $ouvre_ref;
	global $ferme_ref;
	global $ouvre_note;
	global $ferme_note;
	global $lang_dir;

	// Appeler la fonction de pre_traitement
	$letexte = spip_avant_propre ($letexte);

	// Puce
	if (!$lang_dir) {
		include_ecrire('inc_lang.php3');
		$lang_dir = lang_dir($GLOBALS['spip_lang']);
	}
	if ($lang_dir == 'rtl' AND $GLOBALS['puce_rtl'])
		$puce = $GLOBALS['puce_rtl'];
	else
		$puce = $GLOBALS['puce'];

	// Harmoniser les retours chariot
	$letexte = preg_replace(",\r\n?,", "\n", $letexte);

	// Corriger HTML
	$letexte = preg_replace(",</?p>,i", "\n\n\n", $letexte);

	//
	// Notes de bas de page
	//
	$regexp = ',\[\[(.*?)\]\],ms';
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

			// creer le popup 'title' sur l'appel de note
			if ($title = supprimer_tags(propre($note_texte))) {
				$title = $ouvre_note.$ancre.$ferme_note.$title;
				$title = ' title="<html>'
				. texte_backend(couper($title,80)).'</html>"';
			}

			$insert = "$ouvre_ref<a href=\"#nb$ancre\" name=\"nh$ancre\" class=\"spip_note\"$title>$num_note</a>$ferme_ref";
			$appel = "<html>$ouvre_note<a href=\"#nh$ancre\" name=\"nb$ancre\" class=\"spip_note\">$num_note</a>$ferme_note</html>";
		} else {
			$insert = '';
			$appel = '';
		}

		// l'ajouter "brut" dans les notes
		if ($note_texte) {
			if ($mes_notes)
				$mes_notes .= "\n\n";
			$mes_notes .= $appel . $note_texte;
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
			$letexte = str_replace($regs[0], $code, $letexte);
		}
	}


	//
	// Raccourcis liens [xxx->url] (cf. fonction extraire_lien ci-dessus)
	// Note : complique car c'est ici qu'on applique la typo() !
	//
	$regexp = "|\[([^][]*)->(>?)([^]]*)\]|ms";
	$inserts = array();
	if (preg_match_all($regexp, $letexte, $matches, PREG_SET_ORDER)) {
		$i = 0;
		foreach ($matches as $regs) {
			list($insert) = extraire_lien($regs);
			$inserts[++$i] = $insert;
			$letexte = str_replace($regs[0], "@@SPIP_ECHAPPE$i@@", $letexte);
		}
	}
	$letexte = typo($letexte);
	foreach ($inserts as $i => $insert) {
		$letexte = str_replace("@@SPIP_ECHAPPE$i@@", $insert, $letexte);
	}

	//
	// Tableaux
	//

	// traiter le cas particulier des echappements (<doc...> par exemple)
	// qui auraient provoque des \n\n</no p> devant les | des tableaux
	$letexte = preg_replace(',[|]\n\n</no p>@@,','|@@', $letexte);
	$letexte = preg_replace(',@@<no p>\n\n[|],','@@|', $letexte);

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
		/* 12 */	"/<p>([\n]*)(<br[[:space:]]*\/?".">)+/",
		/* 13 */	"/<p>/",
		/* 14 		"/\n/", */
		/* 15 */	"/<quote>/",
		/* 16 */	"/<\/quote>/"
	);
	$remplace1 = array(
		/* 0 */ 	"\n\n@@SPIP_ligne_horizontale@@\n\n",
		/* 1 */ 	"\n<br />&mdash;&nbsp;",
		/* 2 */ 	"\n<br />$puce&nbsp;",
		/* 3 */ 	"\n<br />",
		/* 4 */ 	"\n\n@@SPIP_debut_intertitre@@",
		/* 5 */ 	"@@SPIP_fin_intertitre@@\n\n",
		/* 6 */ 	"<p>",
		/* 7 */ 	"<strong class=\"spip\">",
		/* 8 */ 	"</strong>",
		/* 9 */ 	"<i class=\"spip\">",
		/* 10 */	"</i>",
		/* 11 */	"<p class=\"spip\">",
		/* 12 */	"<p class=\"spip\">",
		/* 13 */	"<p class=\"spip\">",
		/* 14 		" ", */
		/* 15 */	"\n\n<blockquote class=\"spip\"><p class=\"spip\">",
		/* 16 */	"</p></blockquote>\n\n"
	);
	$letexte = preg_replace($cherche1, $remplace1, $letexte);
	$letexte = preg_replace("@^ <br />@", "", $letexte);

	// paragrapher
	if (strpos(' '.$letexte, '<p class="spip">')) # ce test est destine a disparaitre, avec un impact sur les textes a un seul paragraphe
	{
		$letexte = '<p class="spip">'.str_replace('<p class="spip">', "</p>\n".'<p class="spip">', $letexte).'</p>';
	}
	$letexte = preg_replace(',(<p class="spip">)?\s*(<center>)?\s*</no p>,ims', '\2', $letexte);
	$letexte = preg_replace(',<no p>\s*(</center>)?\s*(</p>)?,ims', '\1', $letexte);

	// intertitres / hr / blockquote / table / ul compliants
	$letexte = preg_replace(',(<p class="spip">)?[[:space:]]*@@SPIP_debut_intertitre@@,ms', $debut_intertitre, $letexte);
	$letexte = preg_replace(',@@SPIP_fin_intertitre@@[[:space:]]*(</p>)?,ms', $fin_intertitre, $letexte);
	$letexte = preg_replace(',(<p class="spip">)?[[:space:]]*@@SPIP_ligne_horizontale@@[[:space:]]*(</p>)?,ms', $ligne_horizontale, $letexte);
	$letexte = preg_replace(',(<p class="spip">)?[[:space:]]*<blockquote class=\"spip\"></p>,ms', "\n<blockquote class=\"spip\">", $letexte);
	$letexte = preg_replace(',</blockquote>[[:space:]]*(</p>)?,ms', "</blockquote>\n", $letexte);
	$letexte = preg_replace(',(<p class="spip">)?[[:space:]]*<table([>[:space:]]),ms', "\n<table\\2", $letexte);
	$letexte = preg_replace(',</table>[[:space:]]*(</p>)?,ms', "</table>\n", $letexte);
	$letexte = preg_replace(',(<p class="spip">)?[[:space:]]*<ul([>[:space:]]),ms', "<ul\\2", $letexte);
	$letexte = preg_replace(',</ul>[[:space:]]*(</p>)?,ms', '</ul>', $letexte);
	$letexte = preg_replace(',<p class="spip">[[:space:]]*</p>,ms', "\n", $letexte);

	// Appeler la fonction de post-traitement
	$letexte = spip_apres_propre ($letexte);

	return array($letexte,$mes_notes);
}

function traiter_les_notes($mes_notes, $les_echap) {
	list($mes_notes,) = traiter_raccourcis_generale($mes_notes);
	if (ereg('<p class="spip">',$mes_notes))
		$mes_notes = str_replace('<p class="spip">', '<p class="spip_note">', $mes_notes);
	else
		$mes_notes = '<p class="spip_note">'.$mes_notes."</p>\n";
	$mes_notes = echappe_retour($mes_notes, $les_echap, "SOURCEPROPRE");
	$GLOBALS['les_notes'] .= interdire_scripts($mes_notes);
}

function traiter_raccourcis($letexte, $les_echap=false) {
	// echapper les <a href>, <html>...< /html>, <code>...< /code>

	if (!$les_echap)
		list($letexte, $les_echap) = echappe_html($letexte, "SOURCEPROPRE");
	list($letexte, $mes_notes) = traiter_raccourcis_generale($letexte);
	if ($mes_notes) traiter_les_notes($mes_notes, $les_echap);
	// Reinserer les echappements
	return trim(echappe_retour($letexte, $les_echap, "SOURCEPROPRE"));
}

function traiter_raccourcis_doublon(&$doublons, $letexte) {
	// echapper les <a href>, <html>...< /html>, <code>...< /code>
	list($letexte, $les_echap) = echappe_html($letexte, "SOURCEPROPRE");
	list($letexte, $mes_notes) = traiter_raccourcis_generale($letexte);
	if ($mes_notes) traiter_les_notes($mes_notes, $les_echap);
	// Reinserer les echappements
	return trim(echappe_retour_doublon($letexte, $les_echap, "SOURCEPROPRE", $doublons));
}


// Filtre a appliquer aux champs du type #TEXTE*
function propre($letexte, $echap=false) {
	return interdire_scripts(traiter_raccourcis(trim($letexte), $echap));
}

?>
