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

// Selection de l'aide correspondant a la langue demandee
// avec un cache fonde sur la date du fichier indiquant la version
// (approximatif, mais c'est deja qqch)
// http://doc.spip.org/@fichier_aide
function fichier_aide($lang_aide = '', $aide='') {
	global $help_server;

	if (!$lang_aide) $lang_aide = $GLOBALS['spip_lang'];
	$fichier_aide = _DIR_AIDE . "$lang_aide-aide.html";
	$lastm = @filemtime($fichier_aide);
	$lastversion = @filemtime(_DIR_RESTREINT . 'inc_version.php');
	$here = @(is_readable($fichier_aide) AND ($lastm >= $lastversion));

	if ($here) {
		lire_fichier($fichier_aide, $contenu);
	} elseif (isset($help_server)) {
			include_spip('inc/distant');
			$contenu = recuperer_page("$help_server/$lang_aide-aide.html");
	} else return array(-1, false);

	if (strlen($contenu) <= 500) return array(-1, false);

	if ($aide) {
		$preg = ',<h2( class="spip")?'. ">$aide/(.+?)</h2>(.*)$,ism";
		preg_match($preg, $contenu, $r);
		$html = preg_replace(',<h[12].*,ism', '', $r[3]);
		if (!$html) return array(-1, false);
	} else $html = $contenu;
	if (!$here) {
		// mettre en cache (tant pis si on peut pas)
		sous_repertoire(_DIR_AIDE,'','',true);
		ecrire_fichier ($fichier_aide, $contenu);
		$lastm = time();
	}

	return array($html, $lastm);
}

// http://doc.spip.org/@help_panneau
function help_panneau() {

	$copyleft = _T('info_copyright_doc',
				array('spipnet' => $GLOBALS['home_server']
					. '/' .    $GLOBALS['spip_lang']
					. '_'));

	return "<div align='center'>
			<img src='" . chemin_image('logo-spip.gif') .
		  "' alt='SPIP' style='width: 267px; height: 170px; border: 0px' />
			<br />
			<div align='center' style='font-variant: small-caps;'>
			Syst&egrave;me de publication pour l'Internet
			</div></div>
			<div style='position:absolute; bottom: 10px; right:20px;
			font-size: 12px; '>" .
			preg_replace(",<a ,i", "<a class='target_blank", $copyleft) .
			'</div>';
}

// http://doc.spip.org/@help_body
function help_body($aide, $suite, $lang_aide='') {
	global $help_server, $spip_lang_rtl;

	if (!$aide) 
		$html = help_panneau();
	elseif ($aide == 'spip') {
		$aide = "<table border='0' width='100%' height='60%'>
<tr style='width: 100%' height='60%'>
<td style='width: 100%' height='60%' align='center' valign='middle'>
<img src='" . generer_url_ecrire('aide_index', 'img=AIDE--logo-spip.gif', false, true).
		  "' alt='SPIP' style='width: 300px; height: 170px; border: 0px;' />
</td></tr></table>";
	} else $aide = '';

	// Recherche des images de l'aide
	$html = "";
	while (preg_match("@(<img([^<>]* +)?\s*src=['\"])"
		. "((AIDE|IMG|local)/([-_a-zA-Z0-9]*/?)([^'\"<>]*))@ims",
	$suite, $r)) {
		$p = strpos($suite, $r[0]);
		$img = str_replace('/', '-', $r[3]);
		$html .= substr($suite, 0, $p) .
		  $r[1] . 
		  generer_url_ecrire('aide_index', "img=$img", false, true);
		$suite = substr($suite, $p + strlen($r[0]));
	}
	$html .= $suite;

	// relocaliser img_pack au bon endroit ...
	$html = preg_replace(_REPLACE_IMG_PACK,"\\1"._DIR_IMG_PACK, $html);

	// Il faut que la langue de typo() soit celle de l'aide en ligne
	changer_typo($lang_aide);

	$html = justifier($html);
	// Remplacer les liens externes par des liens ouvrants (a cause des frames)
	$html = preg_replace('@<a href="(http://[^"]+)"([^>]*)>@', '<a href="\\1"\\2 target="_blank">', $html);

	return $aide . $html;
}


/////////////////////////////////////
// filtre les chemins des images referencant le repertoire de www.spip.net
// et les remplacer par les copies locales, qu'on cree si ce n'est fait
//
// http://doc.spip.org/@help_img
function help_img($regs) {
	global $help_server;

	list ($cache, $rep, $lang, $file, $ext) = $regs;

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
		if (ecrire_fichier(_DIR_AIDE . "test")
		AND ($contenu =
		recuperer_page("$help_server/$rep/$lang/$file"))) {
			ecrire_fichier ($img = _DIR_AIDE . $cache, $contenu);
			// Bug de certains OS: 
			// le contenu n'est pas compris au premier envoi
			// Donc ne pas mettre en 
			header("Content-Type: image/$ext");
			echo $contenu;
		} else
			redirige_par_entete("$help_server/$rep/$lang/$file");
	}
}

// http://doc.spip.org/@help_img_cache
function help_img_cache($img, $ext)
{
	header("Content-Type: image/$ext");
	header("Expires: ".gmdate("D, d M Y H:i:s", time()+24*3600) .' GMT');
	readfile($img);
}

// Affichage du menu de gauche avec analyse de l'aide demandee
// afin d'ouvrir le sous-menu correspondant a l'affichage a droite
// http://doc.spip.org/@help_menu_rubrique
function help_menu_rubrique($aide, $html)
{
	global $spip_lang;

	preg_match_all(',<h([12])( class="spip")?'. '>([^/]+?)(/(.+?))?</h\1>,ism', $html, $sections, PREG_SET_ORDER);
	$afficher_rubrique = false;
	$ligne = 0;
	foreach ($sections as $section) {
		if ($section[1] == '1') {
			if ($afficher_rubrique && $texte)
				echo fin_rubrique($titre, $texte, $numrub, $ouvrir);
			$afficher_rubrique = $section[5] ? ($section[5] == 'redac') : true;
			$texte = '';
			if ($afficher_rubrique) {
				$numrub++;
				$ouvrir = 0;
				$titre = preg_replace(_REPLACE_IMG_PACK,"\\1"._DIR_IMG_PACK, $section[3]);
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

			$h = generer_url_ecrire("aide_index", "aide=$sujet&frame=body&var_lang=$spip_lang", false, true);
			$texte .= "<a class='$class' target='droite' id='$id' href='$h' onclick=\"activer_article('$id');return true;\">"
			  . preg_replace(_REPLACE_IMG_PACK,"\\1"._DIR_IMG_PACK, $section[5])
			  . "</a><br style='clear:both;' />\n";
		}
	}
	if ($afficher_rubrique && $texte)
		echo fin_rubrique($titre, $texte, $numrub, $ouvrir);
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

//
// Distribuer le travail
//
// http://doc.spip.org/@exec_aide_index_dist
function exec_aide_index_dist()
{
	global $spip_lang;

	$img = _request('img');
	if (preg_match(',^([^-.]*)-([^-.]*)-([^\.]*\.(gif|jpg|png))$,', $img, $r))
		help_img($r);
	else {
		if (_request('var_lang'))
			changer_langue($lang = _request('var_lang'));
		if (_request('lang'))
			changer_langue($lang = _request('lang')); # pour le cas ou on a fait appel au menu de changement de langue (aide absente dans la langue x)
		else $lang = $spip_lang;

		$frame = _request('frame');
		$aide = _request('aide');
		list($html, $lastm) = 
		  fichier_aide($spip_lang, ($frame === 'menu') ? '' : $aide);
		if ($html === -1) {
			include_spip('inc/minipres');
			echo  minipres(_T('forum_titre_erreur'),
				       "<div><a href='" .
				       $GLOBALS['home_server'] .
				       "'>" .
				       $GLOBALS['help_server'] .
				       "</a>&nbsp;: ".
				       _T('aide_non_disponible').
				       "</div><div align='right'>".
				       menu_langues('var_lang_ecrire').
				       "</div>");
		// Si pas de not-modified-since, envoyer tout
		} elseif ($lastm AND !help_lastmodified($lastm)) {
			help_frame($frame, $aide, $html, $lang);
		}
	}
}
		  
function help_frame($frame, $aide, $html, $lang)
{
	global $spip_lang_rtl;
	header("Content-Type: text/html; charset=utf-8");
	echo _DOCTYPE_AIDE, html_lang_attributes();
	$titre = _T('info_aide_en_ligne');
	if ($frame === 'menu') {
		echo "<head>\n<title>",$titre,"</title>\n";
		echo '<link rel="stylesheet" type="text/css" href="';
		echo url_absolue(find_in_path('aide_menu.css'));
		echo "\"/>\n", $GLOBALS['browser_layer'],
		  http_script('', 'jquery.js'),
		  http_script('var curr_article;
function activer_article(id) {
	if (curr_article)
		jQuery("#"+curr_article).removeClass("article-actif").addClass("article-inactif");
	if (id) {
		jQuery("#"+id).removeClass("article-inactif").addClass("article-actif");
		curr_article = id;
	}
}
'), '
</head>
<body bgcolor="#FFFFFF" text="#000000" link="#E86519" vlink="#6E003A" alink="#FF9900" topmargin="5" leftmargin="5" marginwidth="5" marginheight="5"' .
		  ($spip_lang_rtl ? " dir='rtl'" : '') .
		  " lang='$lang'" . '>';
		help_menu_rubrique($aide, $html);
		echo '</body>';
	} elseif ($frame === 'body') {
		echo "<head>\n<title>",$titre,"</title>\n";
		echo '<link rel="stylesheet" type="text/css" href="';
		echo url_absolue(find_in_path('aide_body.css'));
		echo "\"/>\n";
		echo "</head>\n";
		echo '<body bgcolor="#FFFFFF" text="#000000" topmargin="24" leftmargin="24" marginwidth="24" marginheight="24"';
		if ($spip_lang_rtl)
			echo " dir='rtl'";
		echo " lang='$lang'>";
		echo help_body($aide, $html, $lang);
		echo '</body>';
	} else {
		echo "<head>\n<title>",$titre,"</title>\n</head>\n";
		$menu = "<frame src='" . generer_url_ecrire('aide_index', "aide=$aide&var_lang=$lang&frame=menu", false, true) . "' name=\"gauche\" id=\"gauche\" scrolling=\"auto\" />\n";
		$body = "<frame src='" . generer_url_ecrire('aide_index', "aide=$aide&var_lang=$lang&frame=body", false, true) . "' name=\"droite\" id=\"droite\" scrolling=\"auto\" />\n";

		$seq = $spip_lang_rtl ? "$body$menu" : "$menu$body";
		$dim = $spip_lang_rtl ? '*,160' : '160,*';
		echo "<frameset cols='$dim' border='0' frameborder='0' framespacing='0'>$seq</frameset>";
	}
	echo "\n</html>";
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
?>
