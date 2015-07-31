<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/headers');
include_spip('inc/texte');
include_spip('inc/layer');

// Pour relocaliser img_pack au bon endroit ...
define('_REPLACE_IMG_PACK', "@(<img([^<>]* +)?\s*src=['\"])img_pack\/@ims");


/////////////////////////////
// La frame de base
//
// http://doc.spip.org/@help_frame
function help_frame ($aide, $lang) {

	$frame_menu = "<frame src='" . generer_url_ecrire('aide_index', "aide=$aide&var_lang=$lang&frame=menu", false, true) . "' name=\"gauche\" id=\"gauche\" scrolling=\"auto\" />\n";
	$frame_body = "<frame src='" . generer_url_ecrire('aide_index', "aide=$aide&var_lang=$lang&frame=body", false, true) . "' name=\"droite\" id=\"droite\" scrolling=\"auto\" />\n";

	if ($GLOBALS['spip_lang_rtl']) {
	  echo '<frameset cols="*,160" border="0" frameborder="0" framespacing="0">', $frame_body,$frame_menu, '</frameset>';
	}
	else {
	  echo '<frameset cols="160,*" border="0" frameborder="0" framespacing="0">', $frame_menu,$frame_body, '</frameset>';
	}
}



/////////////////////////////
// Le contenu demande
//

// Erreur aide non disponible
// http://doc.spip.org/@erreur_aide_indisponible
function erreur_aide_indisponible() {
	global $help_server;
	include_spip('inc/minipres');
	return  minipres(_T('forum_titre_erreur'),
		 "<div>$help_server: "._T('aide_non_disponible')."</div><div align='right'>".menu_langues('var_lang_ecrire')."</div>");
}

// Selection de l'aide correspondant a la langue demandee
// http://doc.spip.org/@fichier_aide
function fichier_aide($lang_aide = '') {
	global $help_server;

	if (!$lang_aide) $lang_aide = $GLOBALS['spip_lang'];
	$fichier_aide = _DIR_AIDE . "$lang_aide-aide.html";
	$lastm = @filemtime($fichier_aide);
	$lastversion = @filemtime(_DIR_RESTREINT . 'inc_version.php');

	// en cache et a jour ?
	if (@is_readable($fichier_aide) AND ($lastm >= $lastversion)) {
		lire_fichier($fichier_aide, $contenu);
	} else {
		
	  // Non, chercher les tables de la loi 
		if (isset($help_server)) {
			include_spip('inc/distant');
			if ($contenu = recuperer_page("$help_server/$lang_aide-aide.html")) {
			  // mettre en cache (tant pis si on peut pas)
			  sous_repertoire(_DIR_AIDE,'','',true);
				ecrire_fichier ($fichier_aide, $contenu);
				$lastm = time();
			}
			
		} else $contenu = '';
	}

	if (strlen($contenu) > 500) return array($contenu, $lastm);

	// c'est cuit
	return array(-1, false);
}

define('_STYLE_AIDE_BODY', '
<style type="text/css"><!--
.spip_cadre {
	width : 100%;
	background-color: #FFFFFF;
	padding: 5px;
}
.spip_quote {
	margin-left : 40px;
	margin-top : 10px;
	margin-bottom : 10px;
	border : solid 1px #aaaaaa;
	background-color: #dddddd;
	padding: 5px;
}

a { text-decoration: none; }
a:focus,a:hover { color: #FF9900; text-decoration: underline; }

body {
	font-family: Georgia, Garamond, Times New Roman, serif;
}
h3.spip {
	font-family: Verdana, Geneva, Sans, sans-serif;
	font-weight: bold;
	font-size: 115%;
	text-align: center;
}

table.spip {
}

table.spip tr.row_first {
	background-color: #FCF4D0;
}

table.spip tr.row_odd {
	background-color: #C0C0C0;
}

table.spip tr.row_even {
	background-color: #F0F0F0;
}

table.spip td {
	padding: 1px;
	text-align: left;
	vertical-align: center;
}

--></style>');

// http://doc.spip.org/@help_panneau
function help_panneau() {

	  return "<div align='center'>
			<img src='" . chemin_image('logo-spip.gif') .
		  "' alt='SPIP' style='width: 267px; height: 170px; border: 0px' />
			<br />
			<div align='center' style='font-variant: small-caps;'>
			Syst&egrave;me de publication pour l'Internet
			</div></div>
			<div style='position:absolute; bottom: 10px; right:20px;
			font-size: 12px; '>" .
		preg_replace(",<a ,i", "<a class='target_blank' ",_T('info_copyright_doc')).
			'</div>';
}

// http://doc.spip.org/@help_body
function help_body($aide, $suite, $lang_aide='') {
	global $help_server, $spip_lang_rtl;

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
	
	echo _STYLE_AIDE_BODY, "</head>\n";

	echo '<body bgcolor="#FFFFFF" text="#000000" topmargin="24" leftmargin="24" marginwidth="24" marginheight="24"';
	if ($spip_lang_rtl)
		echo " dir='rtl'";
	echo " lang='$lang_aide'>";

	if ($aide == 'spip') {
		echo "<table border='0' width='100%' height='60%'>
<tr style='width: 100%' height='60%'>
<td style='width: 100%' height='60%' align='center' valign='middle'>
<img src='", generer_url_ecrire('aide_index', 'img=AIDE--logo-spip.gif', false, true),
		  "' alt='SPIP' style='width: 300px; height: 170px; border: 0px;' />
</td></tr></table>";
	}

	// Il faut que la langue de typo() soit celle de l'aide en ligne
	changer_typo($lang_aide);

	$html = justifier($html);
	// Remplacer les liens externes par des liens ouvrants (a cause des frames)
	$html = preg_replace('@<a href="(http://[^"]+)"([^>]*)>@', '<a href="\\1"\\2 target="_blank">', $html);

	echo $html, '</body>';
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

define('AIDE_STYLE_MENU', '<style type="text/css">
<!--
	a {text-decoration: none; }
	A:Hover {text-decoration: underline;}

	.article-inactif {
		float: '.$GLOBALS['spip_lang_left'].';
		text-align: '.$GLOBALS['spip_lang_left'].';
		width: 80%;
		background: ' . "url(" . chemin_image('triangle'.$GLOBALS['spip_lang_rtl'].'.gif') . ') ' . $GLOBALS['spip_lang_left'].' center no-repeat;
		margin: 2px;
		padding: 0px;
		padding-'.$GLOBALS['spip_lang_left'].': 20px;
		font-family: Arial, Sans, sans-serif;
		font-size: 12px;
	}
	.article-actif {
		float: '.$GLOBALS['spip_lang_right'].';
		text-align: '.$GLOBALS['spip_lang_right'].';
		width: 80%;
		background: ' . "url(" .  chemin_image('triangle'.$GLOBALS['spip_lang_rtl'].'.gif') . ') ' . $GLOBALS['spip_lang_right'].' center no-repeat;
		margin: 4px;
		padding: 0px;
		padding-'.$GLOBALS['spip_lang_right'].': 20px;
		font-family: Arial, Sans, sans-serif;
		font-size: 12px;
		font-weight: bold;
		color: black;
	}
	.article-actif:hover {
		text-decoration: none;
	}
	.rubrique {
		width: 90%;
		margin: 0px;
		margin-top: 6px;
		margin-bottom: 4px;
		padding: 4px;
		font-family: Trebuchet MS, Arial, Sans, sans-serif;
		font-size: 14px;
		font-weight: bold;
		color: black;
		background-color: #EEEECC;
	}
-->
</style>');

///////////////////////////////////////
// Le menu de gauche
//
// http://doc.spip.org/@help_menu
function help_menu($aide, $html, $lang_aide='') {
	global $spip_lang_rtl;

	echo AIDE_STYLE_MENU, http_script('var curr_article;
// http://doc.spip.org/@activer_article
function activer_article(id) {
	if (curr_article)
		jQuery("#"+curr_article).removeClass("article-actif").addClass("article-inactif");
	if (id) {
		jQuery("#"+id).removeClass("article-inactif").addClass("article-actif");
		curr_article = id;
	}
}
'),
	$GLOBALS['browser_layer'],
	'
</head>
<body bgcolor="#FFFFFF" text="#000000" link="#E86519" vlink="#6E003A" alink="#FF9900" topmargin="5" leftmargin="5" marginwidth="5" marginheight="5"';
	if ($spip_lang_rtl)
		echo " dir='rtl'";
	echo " lang='$lang_aide'>";

	help_menu_rubrique($html);
}

// Analyse de la structure de l'aide demandee

// http://doc.spip.org/@help_menu_rubrique
function help_menu_rubrique($html)
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

			if (_request('aide') == $sujet) {
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
	global $help_server, $spip_lang;

	if (_request('var_lang')) changer_langue($lang = _request('var_lang'));
	if (_request('lang'))
	  changer_langue($lang = _request('lang')); # pour le cas ou on a fait appel au menu de changement de langue (aide absente dans la langue x)
	else $lang = $spip_lang;

	if (preg_match(',^([^-.]*)-([^-.]*)-([^\.]*\.(gif|jpg|png))$,', _request('img'), $regs))
	  help_img($regs);
	else {

	list($html, $lastmodified) = fichier_aide();

	if ($html === -1)
	  echo erreur_aide_indisponible();
	// si on a la doc dans un fichier, controler if_modified_since
	elseif ($lastmodified AND !help_lastmodified($lastmodified)) {
		header("Content-Type: text/html; charset=utf-8");
		echo _DOCTYPE_AIDE, html_lang_attributes();
		echo "<head><title>", _T('info_aide_en_ligne'),	"</title>\n";
		echo http_script('', 'jquery.js');

		if (_request('frame') == 'menu'){
			help_menu(_request('aide'), $html, $lang);
		}
		else if (_request('frame') == 'body') {
			$aide = _request('aide');
			if ($aide) {
				$preg = ',<h2( class="spip")?'. ">$aide/(.+?)</h2>(.*)$,ism";
				preg_match($preg, $html, $r);
				$html = preg_replace(',<h[12].*,ism', '', $r[3]);
				if (!$html) echo erreur_aide_indisponible();
			} else 	$html = help_panneau();
			help_body($aide, $html, $lang);
		} else {
			echo '</head>';
			help_frame(_request('aide'), $lang);
		}
		echo "\n</html>";
	}
	}
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
