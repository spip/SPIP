<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/headers');
include_spip('inc/texte');
include_spip('inc/layer');

// Pour relocaliser les imgages au bon endroit ...
define('_REPLACE_IMG_PACK', "@(<img([^<>]* +)?\s*src=['\"])img_pack\/@ims");

// Les appels a soi-meme (notamment les images)
// doivent etre en relatif pour pouvoir creer un cache local

function generer_url_aide($args)
{
	return generer_url_ecrire('aide_index', $args, false, true);
}

// Trouver l'aide correspondant a la langue demandee. 
// On gere un cache fonde sur la date du fichier indiquant la version
// (approximatif, mais c'est deja qqch)
// 

function help_fichier($lang_aide, $path, $help_server) {

	$fichier_aide = _DIR_AIDE . $path;
	$lastm = @filemtime($fichier_aide);
	$lastversion = @filemtime(_DIR_RESTREINT . 'inc_version.php');
	$here = @(is_readable($fichier_aide) AND ($lastm >= $lastversion));
	$contenu = '';

	if ($here) {
		lire_fichier($fichier_aide, $contenu);
	} elseif ($help_server) {
		include_spip('inc/distant');
		$contenu = recuperer_page("$help_server/$path");
	}

	if (strlen($contenu) <= 500) return array(false, false);

	// Il faut que la langue de typo() soit celle de l'aide en ligne
	changer_typo($lang_aide);

	if (!$here) {
		// mettre en cache (tant pis si on peut pas)
		sous_repertoire(_DIR_AIDE,'','',true);
		$contenu = help_replace_img(justifier($contenu));
		ecrire_fichier ($fichier_aide, $contenu);
		$lastm = time();
	}

	return array($contenu, help_lastmodified($lastm));
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

// Recherche des images de l'aide

function help_replace_img($contenu)
{
	$html = "";
	$re = "@(<img([^<>]* +)?\s*src=['\"])((AIDE|IMG|local)/([-_a-zA-Z0-9]*/?)([^'\"<>]*))@imsS";
	while (preg_match($re, $contenu, $r)) {
		$p = strpos($contenu, $r[0]);
		$h = generer_url_aide("img=" . str_replace('/', '-', $r[3]));
		$html .= substr($contenu, 0, $p) .  $r[1] . $h;
		$contenu = substr($contenu, $p + strlen($r[0]));
	}
	$html .= $contenu;

	// relocaliser img_pack au bon endroit ...
	$html = preg_replace(_REPLACE_IMG_PACK,"\\1"._DIR_IMG_PACK, $html);

	// Remplacer les liens externes par des liens ouvrants (a cause des frames)
	return preg_replace('@<a href="(http://[^"]+)"([^>]*)>@', '<a href="\\1"\\2 target="_blank">', $html);
}

define('_HELP_PANNEAU', "<img src='" .
       chemin_image('logo-spip.gif') .
       "' alt='SPIP' style='width: 267px; height: 170px; border: 0px' />
	<br />
	<div align='center' style='font-variant: small-caps;'>
	Syst&egrave;me de publication pour l'Internet
	</div></div>
	<div style='position:absolute; bottom: 10px; right:20px; font-size: 12px; '>");

// http://doc.spip.org/@help_body
function help_body($aide) {

	if (!$aide) {
		$c = _T('info_copyright_doc',
				array('spipnet' => $GLOBALS['home_server']
					. '/' .    $GLOBALS['spip_lang']
					. '_'));
		return "<div align='center'>" .
			_HELP_PANNEAU .
			preg_replace(",<a ,i", "<a class='target_blank", $c) .
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
// et se termine par la prochaine balise h2 ou h1

function help_section($aide, $contenu)
{
	$r = ',<h2( class="spip")?' . '>' . $aide ."/(.+?)</h2>(.*?)<h[12],ism";
	preg_match($r, $contenu, $m);
	return $m[3];
}

define('_SECTIONS_AIDE', ',<h([12])( class="spip")?'. '>([^/]+?)(/(.+?))?</h\1>,ism');

// Affichage du menu de gauche avec analyse de la section demandee
// afin d'ouvrir le sous-menu correspondant a l'affichage a droite
// http://doc.spip.org/@help_menu_rubrique
function help_menu_rubrique($aide, $html)
{
	global $spip_lang;

	preg_match_all(_SECTIONS_AIDE, $html, $sections, PREG_SET_ORDER);
	$afficher = false;
	$ligne = 0;
	$res = '';
	foreach ($sections as $section) {
		if ($section[1] == '1') {
			if ($afficher && $texte)
				$res .= fin_rubrique($titre, $texte, $numrub, $ouvrir);
			$afficher = $section[5] ? ($section[5] == 'redac') : true;
			$texte = '';
			if ($afficher) {
				$numrub++;
				$ouvrir = 0;
				$titre = $section[3];
			}
		} else {
			++$ligne;
			$sujet = $section[3];
			$id = "ligne$ligne";

			if ($aide == $sujet) {
				$ouvrir = 1;
				$class = "article-actif";
				$texte .= http_script("curr_article = '$id';");
			} else $class = "article-inactif";

			$h = generer_url_aide("aide=$sujet&frame=body&var_lang=$spip_lang");
			$texte .= "<a class='$class' target='droite' id='$id' href='$h' onclick=\"activer_article('$id');return true;\">"
			  . $section[5]
			  . "</a><br style='clear:both;' />\n";
		}
	}
	if ($afficher && $texte)
		$res .= fin_rubrique($titre, $texte, $numrub, $ouvrir);
	return $res;
}

// http://doc.spip.org/@fin_rubrique
function fin_rubrique($titre, $texte, $numrub, $ouvrir) {

	$block_rubrique = "block$numrub";
	return  "<div class='rubrique'>"
		. bouton_block_depliable($titre, $ouvrir, $block_rubrique)
		. "</div>\n"
		. debut_block_depliable($ouvrir, $block_rubrique)
		. "\n"
		.  $texte
		. fin_block(). "\n\n";
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
	  	$html .
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

//
// Tester d'abord si c'est un image qui est demandee, et sinon deleguer.
// Pour les images du repertoire de www.spip.net,
// on les remplace par les copies locales, qu'on cree si ce n'est fait
//
//
// http://doc.spip.org/@exec_aide_index_dist
function exec_aide_index_dist()
{
	global $help_server;
	if (!preg_match(',^([^-.]*)-([^-.]*)-([^\.]*\.(gif|jpg|png))$,', 
			_request('img'),
			$r)) {
		aide_index_frame(_request('var_lang_r'),
				 _request('lang_r'),
				 _request('frame'),
				 _request('aide'),
				 $help_server);
	} else {
		list ($cache, $rep, $lang, $file, $ext) = $r;
		if ($rep=="IMG" AND $lang=="cache"
		AND @file_exists($img = _DIR_VAR.'cache-TeX/'.preg_replace(',^TeX-,', '', $file))) {
			help_img_cache($img, $ext);
		} else if (@file_exists($img = _DIR_AIDE . $cache)) {
			help_img_cache($img, $ext);
		} else if (@file_exists($img = _DIR_RACINE . 'AIDE/aide-'.$cache)) {
			help_img_cache($img, $ext);
		} else if ($help_server) {
			include_spip('inc/distant');
			sous_repertoire(_DIR_AIDE,'','',true);
			$img = "$help_server/$rep/$lang/$file";
			if (ecrire_fichier(_DIR_AIDE . "test")
			AND ($contenu = recuperer_page($img))) {
			  ecrire_fichier ($img = _DIR_AIDE . $cache, $contenu);
			// Bug de certains OS: 
			// le contenu n'est pas compris au premier envoi
			// Donc ne pas mettre d'Expire
			  header("Content-Type: image/$ext");
			  echo $contenu;
			} else redirige_par_entete($img);
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
			$help_server .
			"</a>&nbsp;: ".
			_T('aide_non_disponible').
			"</div><div align='right'>".
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
