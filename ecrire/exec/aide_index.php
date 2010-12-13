<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/headers');
include_spip('inc/texte');
include_spip('inc/layer');

// Les sections d'un fichier aide sont reperees ainsi:

define('_SECTIONS_AIDE', ',<h([12])(?:\s+class="spip")?'. '>([^/]+?)(?:/(.+?))?</h\1>,ism');

// Les appels a soi-meme (notamment les images)
// doivent etre en relatif pour pouvoir creer un cache local

function generer_url_aide($args)
{
	return generer_url_ecrire('aide_index', $args, false, true);
}

// Trouver l'aide correspondant a la langue demandee. 
// On gere un cache fonde sur la date du fichier indiquant la version
// (approximatif, mais c'est deja qqch)

function help_fichier($lang_aide, $path, $help_server) {

	$fichier_aide = _DIR_AIDE . $path;
	$lastm = @filemtime($fichier_aide);
	$lastversion = @filemtime(_DIR_RESTREINT . 'inc_version.php');
	$here = @(is_readable($fichier_aide) AND ($lastm >= $lastversion));
	$contenu = '';

	if ($here) {
		lire_fichier($fichier_aide, $contenu);
		return array($contenu, help_lastmodified($lastm));
	}

	$contenu = array();
	include_spip('inc/distant');
	foreach ($help_server as $k => $server) {
		// Remplacer les liens aux images par leur gestionnaire de cache
		$url = "$server/$path";
		$page = help_replace_img(recuperer_page($url),$k);
		// les liens internes ne doivent pas etre deguises en externes
		$url = parse_url($url);
		$re = '@(<a\b[^>]*\s+href=["\'])' .
		  '(?:' . $url['scheme'] . '://' . $url['host'] . ')?' .
		  $url['path'] . '([^"\']*)@ims';
		$page = preg_replace($re,'\\1\\2', $page);

		preg_match_all(_SECTIONS_AIDE, $page, $sections, PREG_SET_ORDER);
		// Fusionner les aides ayant meme nom de section
		$vus = array();
		foreach ($sections as $section) {
			list($tout,$prof, $sujet,) = $section;
			if (in_array($sujet, $vus)) continue;
			$corps = help_section($sujet, $page, $prof);
			foreach ($contenu as $k => $s) {
			  if ($sujet == $k) {
			    // Section deja vue qu'il faut completer
			    // Si le complement a des sous-sections,
			    // ne pas en tenir compte quand on les rencontrera
			    // lors des prochains passages dans la boucle
			    preg_match_all(_SECTIONS_AIDE, $corps, $s, PREG_PATTERN_ORDER);
			    if ($s) {$vus = array_merge($vus, $s[2]);}
			    $contenu[$k] .= $corps;
			    $corps = '';
			    break;
			  }
			}
			// Si totalement nouveau, inserer le titre
			// mais pas le corps s'il contient des sous-sections:
			// elles vont venir dans les passages suivants
			if ($corps) {
			  $corps = help_section($sujet, $page);
			  $contenu[$sujet] = $tout . "\n" . $corps;
			}
		}
	}

	$contenu = '<div>' . join('',$contenu) . '</div>';

	// Renvoyer les liens vraiment externes dans une autre fenetre
	$contenu = preg_replace('@<a href="(http://[^"]+)"([^>]*)>@',
				'<a href="\\1"\\2 target="_blank">',
				$contenu);

	// Correction typo dans la langue demandee
	changer_typo($lang_aide);
	$contenu = '<body>' . justifier($contenu) . '</body>';

	if (strlen($contenu) <= 100) return array(false, false);
	// mettre en cache (tant pis si echec)
	sous_repertoire(_DIR_AIDE,'','',true);
	ecrire_fichier ($fichier_aide, $contenu);
	return array($contenu, help_lastmodified(time()));
}

// http://doc.spip.org/@help_lastmodified
function help_lastmodified($lastmodified)
{
	$gmoddate = gmdate("D, d M Y H:i:s", $lastmodified);
	header("Last-Modified: ".$gmoddate." GMT");
	if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
			# MSoft IIS is dumb
	AND !preg_match(',IIS/,', $_SERVER['SERVER_SOFTWARE'])) {

		$ims = preg_replace('/;.*/', '',
				$_SERVER['HTTP_IF_MODIFIED_SINCE']);
		$ims = trim(str_replace('GMT', '', $ims));
		if ($ims == $gmoddate) {
			http_status(304);
			return true;
		}
	}
	return false;
}

// Les aides non mises a jour ont un vieux Path a remplacer
// (mais ce serait bien de le faire en SQL une bonne fois)
define('_REPLACE_IMG_PACK', "@(<img([^<>]* +)?\s*src=['\"])img_pack\/@ims");

// Remplacer les URL des images par l'URL du gestionnaire de cache local

function help_replace_img($contenu, $server=0)
{
	$html = "";
	$re = "@(<img([^<>]* +)?\s*src=['\"])((AIDE|IMG|local)/([-_a-zA-Z0-9]*/?)([^'\"<>]*))@imsS";
	while (preg_match($re, $contenu, $r)) {
		$p = strpos($contenu, $r[0]);
		$i = $server . ':' . str_replace('/', '-', $r[3]);
		$h = generer_url_aide("img=" . $i);
		$html .= substr($contenu, 0, $p) .  $r[1] . $h;
		$contenu = substr($contenu, $p + strlen($r[0]));
	}
	$html .= $contenu;

	// traiter les vieilles doc
	return  preg_replace(_REPLACE_IMG_PACK,"\\1"._DIR_IMG_PACK, $html);
}

// un bout de squelette qu'il serait bon d'evacuer un jour.

define('_HELP_PANNEAU', "<img src='" .
       chemin_image('logo-spip.gif') .
       "' alt='SPIP' style='width: 267px; height: 170px; border: 0px' />
	<br />
	<div align='center' style='font-variant: small-caps;'>
	Syst&egrave;me de publication pour l'Internet
	</div></div>
	<div style='position:absolute; bottom: 10px; right:20px; font-size: 12px; '>");

// Autre squelette qui ne s'avoue pas comme tel

// http://doc.spip.org/@help_body
function help_body($aide) {

	if (!$aide) {
		$c = _T('info_copyright_doc',
				array('spipnet' => $GLOBALS['home_server']
					. '/' .    $GLOBALS['spip_lang']
					. '_'));
		return "<div align='center'>" .
			_HELP_PANNEAU .
			preg_replace(",<a ,i", "<a class='target_blank' ", $c) .
			'</div>';
	} elseif ($aide == 'spip') {
		return "<table border='0' width='100%' height='60%'>
<tr style='width: 100%' height='60%'>
<td style='width: 100%' height='60%' align='center' valign='middle'>
<img src='" . generer_url_aide('img=AIDE--logo-spip.gif').
		  "' alt='SPIP' style='width: 300px; height: 170px; border: 0px;' />
</td></tr></table>";
	} return '';
}


// Extraire la seule section demandee,
// qui commence par son nom entouree d'une balise h2
// et se termine par la prochaine balise h2 ou h1 ou le /body final.

function help_section($aide, $contenu, $prof=2)
{
	$maxprof = ($prof >=2) ? "12" : "1";
	$r = "@<h$prof" . '(?: class="spip")?' . '>\s*' . $aide 
	  ."\s*(?:/.+?)?</h$prof>(.*?)<(?:(?:h[$maxprof])|/body)@ism";

	if (preg_match($r, $contenu, $m))
	  return $m[1];
#	spip_log("aide inconnue $r dans " . substr($contenu, 0, 150));
	return '';
}



// Affichage du menu de gauche avec analyse de la section demandee
// afin d'ouvrir le sous-menu correspondant a l'affichage a droite
// http://doc.spip.org/@help_menu_rubrique
function help_menu_rubrique($aide, $contenu)
{
	global $spip_lang;

	$afficher = false;
	$ligne = $numrub = 0;
	$texte = $res = '';
	preg_match_all(_SECTIONS_AIDE, $contenu, $sections, PREG_SET_ORDER);
	foreach ($sections as $section) {
		list(,$prof, $sujet, $bloc) = $section;
		if ($prof == '1') {
			if ($afficher && $texte)
				$res .= block_parfois_visible("block$numrub", "<div class='rubrique'>$titre</div>", "\n$texte",'', $ouvrir);
			$afficher = $bloc ? ($bloc == 'redac') : true;
			$texte = '';
			if ($afficher) {
				$numrub++;
				$ouvrir = 0;
				$titre = $sujet;
			}
		} else {
			++$ligne;
			$id = "ligne$ligne";

			if ($aide == $sujet) {
				$ouvrir = 1;
				$class = "article-actif";
				$texte .= http_script("curr_article = '$id';");
			} else $class = "article-inactif";

			$h = generer_url_aide("aide=$sujet&frame=body&var_lang=$spip_lang");
			$texte .= "<a class='$class' target='droite' id='$id' href='$h' onclick=\"activer_article('$id');return true;\">"
			  . $bloc
			  . "</a><br style='clear:both;' />\n";
		}
	}
	if ($afficher && $texte)
		$res .= block_parfois_visible("block$numrub", "<div class='rubrique'>$titre</div>", "\n$texte",'', $ouvrir);
	return $res;
}

function help_frame_menu($titre, $contenu, $lang)
{
	global $spip_lang_rtl;

	return "<head>\n<title>" .$titre ."</title>\n" .
	 '<link rel="stylesheet" type="text/css" href="' .
	 generer_url_public('aide_menu', "ltr=". $GLOBALS['spip_lang_left']) .
	  "\"/>\n" .
		http_script('', 'jquery.js') .
		"\n" .
		$GLOBALS['browser_layer'] .
		http_script('var curr_article;
function activer_article(id) {
	if (curr_article)
		jQuery("#"+curr_article).removeClass("article-actif").addClass("article-inactif");
	if (id) {
		jQuery("#"+id).removeClass("article-inactif").addClass("article-actif");
		curr_article = id;
	}
}
') . '
</head>
<body bgcolor="#FFFFFF" text="#000000" link="#E86519" vlink="#6E003A" alink="#FF9900" topmargin="5" leftmargin="5" marginwidth="5" marginheight="5"' .
		  ($spip_lang_rtl ? " dir='rtl'" : '') .
		  " lang='$lang'" . '>' .
		    $contenu .
		    '</body>';
}

function help_frame_body($titre, $aide, $html, $lang_aide='')
{
	global $spip_lang_rtl;
	$dir = $spip_lang_rtl ?  " dir='rtl'" : '';

	return "<head>\n<title>$titre</title>\n".
		'<link rel="stylesheet" type="text/css" href="'.
		url_absolue(find_in_path('aide_body.css')).
		"\"/>\n".
		"</head>\n".
		'<body bgcolor="#FFFFFF" text="#000000" topmargin="24" leftmargin="24" marginwidth="24" marginheight="24"' .
		$dir .
		" lang='$lang'>".
		help_body($aide) .
		($aide ? $html : '').
		'</body>';
}

function help_frame_frame($titre, $aide, $lang)
{
	global $spip_lang_rtl;
	$menu = "<frame src='" . generer_url_aide("aide=$aide&var_lang=$lang&frame=menu") . "' name=\"gauche\" id=\"gauche\" scrolling=\"auto\" />\n";
	$body = "<frame src='" . generer_url_aide("aide=$aide&var_lang=$lang&frame=body") . "' name=\"droite\" id=\"droite\" scrolling=\"auto\" />\n";

	$seq = $spip_lang_rtl ? "$body$menu" : "$menu$body";
	$dim = $spip_lang_rtl ? '*,160' : '160,*';
	return "<head>\n<title>$titre</title>\n</head>\n<frameset cols='$dim' border='0' frameborder='0' framespacing='0'>$seq</frameset>";
}

// http://doc.spip.org/@help_img_cache
function help_img_cache($img, $ext)
{
	header("Content-Type: image/$ext");
	header("Expires: ".gmdate("D, d M Y H:i:s", time()+24*3600) .' GMT');
	readfile($img);
}

// Regexp reperant le travail fait par help_replace_img
define('_HELP_PLACE_IMG',',^(\d+:)?(([^-.]*)-([^-.]*)-([^\.]*\.(gif|jpg|png)))$,');

// Distinguer la demande d'une image et la demande d'un texte.
// Si c'est une URL d'image deguisee, on la cherche dans le cache ou on l'y met.
// Voir les differentes localisations possibles dans help_replace_img
//
// http://doc.spip.org/@exec_aide_index_dist
function exec_aide_index_dist()
{
	global $help_server;
	if (!is_array($help_server)) $help_server = array($help_server);
	if (!preg_match(_HELP_PLACE_IMG,  _request('img'), $r)) {
		aide_index_frame(_request('var_lang_r'),
				 _request('lang_r'),
				 _request('frame'),
				 _request('aide'),
				 $help_server);
	} else {
		list (,$server, $cache, $rep, $lang, $file, $ext) = $r;
		if ($rep=="IMG" AND $lang=="cache"
		AND @file_exists($img = _DIR_VAR.'cache-TeX/'.preg_replace(',^TeX-,', '', $file))) {
			help_img_cache($img, $ext);
		} else if (@file_exists($img = _DIR_AIDE . $cache)) {
			help_img_cache($img, $ext);
		} else if (@file_exists($img = _DIR_RACINE . 'AIDE/aide-'.$cache)) {
			help_img_cache($img, $ext);
		} else { 
			$server = intval(substr($server, 0, -1));
			if ($server = $help_server[$server]) {
				include_spip('inc/distant');
				sous_repertoire(_DIR_AIDE,'','',true);
				$img = "$server/$rep/$lang/$file";
				$contenu = recuperer_page($img);
				if ($contenu) {
				  ecrire_fichier (_DIR_AIDE . $cache, $contenu);
				  // Bug de certains OS:
				  // le contenu est incompris au premier envoi
				  // Donc ne pas mettre d'Expire
				  header("Content-Type: image/$ext");
				  echo $contenu;
				} else redirige_par_entete($img);
			} else redirige_par_entete(generer_url_public('404'));
		}
	}
}

// Determiner la langue L, et en deduire le Path du fichier d'aide.
// Sur le site www.spip.net/, ca donne l'URL www.spip.net/L-aide.html
// reecrit par le htacces suivant:
// http://zone.spip.org/trac/spip-zone/browser/_galaxie_/www.spip.net/squelettes/htaccess.txt

function aide_index_frame($var_lang_r, $lang_r, $frame, $aide, $help_server)
{
	global $spip_lang;

	if ($var_lang_r)
		changer_langue($lang = $var_lang_r);
	if ($lang_r)
	  # pour le cas ou on a fait appel au menu de changement de langue
	  # (aide absente dans la langue x)
		changer_langue($lang = $lang_r);
	else $lang = $spip_lang;

	$titre = _T('info_aide_en_ligne');
	if (!$frame) {
		echo _DOCTYPE_AIDE, html_lang_attributes();
		echo help_frame_frame($titre, $aide, $lang);
		echo "\n</html>";
	} else {
		$path = $spip_lang . "-aide.html";
		list($contenu, $lastm) = 
			help_fichier($spip_lang, $path, $help_server);
		header("Content-Type: text/html; charset=utf-8");
		if (!$contenu) {
			include_spip('inc/minipres');
			echo  minipres(_T('forum_titre_erreur'),
			"<div><a href='" .
			$GLOBALS['home_server'] .
			"'>" .
			$help_server[0] . 
			"</a> $aide&nbsp;: ".
			_T('aide_non_disponible').
			"</div><br /><div align='right'>".
			menu_langues('var_lang_ecrire').
			"</div>");
		// Si pas de not-modified-since, envoyer tout
		} elseif (!$lastm) {
			echo _DOCTYPE_AIDE, html_lang_attributes();
			if ($frame === 'menu') {
			  $contenu = help_menu_rubrique($aide, $contenu);
			  echo help_frame_menu($titre, $contenu, $lang);
			} else  {
			  if ($aide) 
				  $contenu = help_section($aide, $contenu);
			  echo help_frame_body($titre, $aide, $contenu, $lang);
			}
			echo "\n</html>";
		}
	}
}

?>
