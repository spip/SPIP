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

include_spip('inc/minipres');
include_spip('inc/layer');
include_spip('inc/texte');

/////////////////////////////
// La frame de base
//
function help_frame ($aide) {
	global $spip_lang;

	echo "<head><title>", _T('info_aide_en_ligne'),	"</title></head>\n";

	$frame_menu = "<frame src='" . generer_url_ecrire('aide_index', "aide=$aide&var_lang=$spip_lang&frame=menu", false, true) . "' name=\"gauche\" scrolling=\"auto\" noresize>\n";
	$frame_body = "<frame src='" . generer_url_ecrire('aide_index', "aide=$aide&var_lang=$spip_lang&frame=body", false, true) . "' name=\"droite\" scrolling=\"auto\" noresize>\n";

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
function erreur_aide_indisponible() {
	global $help_server;
	minipres(_T('forum_titre_erreur'),
		 "<div>$help_server: "._T('aide_non_disponible')."</div><div align='right'>".menu_langues('var_lang_ecrire')."</div>");
}

// Selection de l'aide correspondant a la langue demandee
function fichier_aide($lang_aide = '') {
	global $help_server;

	if (!$lang_aide) $lang_aide = $GLOBALS['spip_lang'];
	$fichier_aide = _DIR_CACHE . "aide-$lang_aide-aide.html";
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
				ecrire_fichier ($fichier_aide, $contenu);
				$lastm = time();
			}
			
		} else $contenu = '';
	}

	if (strlen($contenu) > 500) return array($contenu, $lastm);

	// c'est cuit
	erreur_aide_indisponible();
}

define(_STYLE_AIDE_BODY, '
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

a {text-decoration: none;}
a:hover {color:#FF9900; text-decoration: underline;
}

body {
	font-family: Georgia, Garamond, Times New Roman, serif;
}
h3.spip {
	font-family: Verdana,Arial,Sans,sans-serif;
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

function help_body($aide, $html) {
	global $help_server;

	// Recuperation du contenu de l'aide demandee

	if ($aide) {
		$html = analyse_aide($html, $aide);
		if (!$html) {
			erreur_aide_indisponible();
		}
	} else {
		// panneau d'accueil
		$html = '<center>
			<img src="'._DIR_IMG_PACK.
			'logo-spip.gif" alt="SPIP" width="267" height="170" border="0">
			<p />
			<div align="center" style="font-variant: small-caps;">
			Syst&egrave;me de publication pour l\'Internet
			</div></center>
			<div style="position:absolute; bottom: 10px; right:20px;
			font-size: 12px; ">'.
		preg_replace(",<a ,i", "<a target='_new' ",_T('info_copyright_doc')).
			'</div>';
	}

	// Recherche des images de l'aide
	$suite = $html;
	$html = "";
	while (preg_match("@(<img([^<>]* +)? src=['\"])"
		. "((AIDE|IMG)/([-_a-zA-Z0-9]*/?)([^'\"<>]*))@i",
	$suite, $r)) {
		$p = strpos($suite, $r[0]);
		$img = str_replace('/', '-', $r[3]);
		$html .= substr($suite, 0, $p) .
		  $r[1] . 
		  generer_url_ecrire('aide_index', "img=$img", false, true);
		$suite = substr($suite, $p + strlen($r[0]));
	}

	echo "<head><title>",_T('info_aide_en_ligne'), "</title>\n", _STYLE_AIDE_BODY, "</head>\n";

	echo '<body bgcolor="#FFFFFF" text="#000000" TOPMARGIN="24" LEFTMARGIN="24" MARGINWIDTH="24" MARGINHEIGHT="24"';
	if ($spip_lang_rtl)
		echo " dir='rtl'";
	echo " lang='$lang_aide'>";

	if ($aide == 'spip') {
		echo '<TABLE BORDER=0 WIDTH=100% HEIGHT=60%>
<TR WIDTH=100% HEIGHT=60%>
<TD WIDTH=100% HEIGHT=60% ALIGN="center" VALIGN="middle">
<CENTER>
<img src="', generer_url_ecrire("aide_index", "img=AIDE--logo-spip.gif", false, true),
		  '" alt="SPIP" width="300" height="170" border="0">
</CENTER>
</TD></TR></TABLE>';
	}

	// Il faut que la langue de typo() soit celle de l'aide en ligne
	changer_typo($lang_aide);

	$html = justifier($html . $suite );
	// Remplacer les liens externes par des liens ouvrants (a cause des frames)
	$html = ereg_replace('<a href="(http://[^"]+)"([^>]*)>', '<a href="\\1"\\2 target="_blank">', $html);

	echo $html;
}


/////////////////////////////////////
// Recuperer une image dans le cache
//
function help_img($regs) {
	global $help_server;

	list ($cache, $rep, $lang, $file, $ext) = $regs;

	header("Content-Type: image/$ext");
	header("Expires: ".gmdate("D, d M Y H:i:s", time()+24*3600) .' GMT');

	if ($rep=="IMG" AND $lang=="cache"
	AND @file_exists($img_tex = _DIR_IMG.'cache-TeX/'.preg_replace(',^TeX-,', '', $file))) {
          readfile($img_tex);
	} else if (@file_exists($img = _DIR_CACHE . 'aide-'.$cache)) {
		readfile($img);
	} else if (@file_exists($img = _DIR_RACINE . 'AIDE/aide-'.$cache)) {
		readfile($img);
	} else if ($help_server) {
		include_spip('inc/distant');
		if (ecrire_fichier(_DIR_CACHE . 'aide-test', "test")
		AND ($contenu =
		recuperer_page("$help_server/$rep/$lang/$file"))) {
			echo $contenu;
			ecrire_fichier (_DIR_CACHE . 'aide-'.$cache, $contenu);
		} else
			redirige_par_entete("$help_server/$rep/$lang/$file");
	}
	exit;
}


define('AIDE_STYLE_MENU', '<style type="text/css">
<!--
	a {text-decoration: none; }
	A:Hover {text-decoration: underline;}

	.article-inactif {
		float: '.$GLOBALS['spip_lang_left'].';
		text-align: '.$GLOBALS['spip_lang_left'].';
		width: 80%;
		background: ' . "url(" . _DIR_IMG_PACK . 'triangle'.$GLOBALS['spip_lang_rtl'].'.gif) ' . $GLOBALS['spip_lang_left'].' center no-repeat;
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
		background: ' . "url(" . _DIR_IMG_PACK . 'triangle'.$GLOBALS['spip_lang_rtl'].'.gif) ' . $GLOBALS['spip_lang_right'].' center no-repeat;
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
		font-size: 13px;
		font-weight: bold;
		color: black;
		background-color: #EEEECC;
		-moz-border-radius: 4px;
	}
-->
</style>');

///////////////////////////////////////
// Le menu de gauche
//
function help_menu($aide, $html) {
	global $spip_lang_rtl; 

	echo "<head><title>",_T('info_aide_en_ligne'), "</title>\n",AIDE_STYLE_MENU, '<script type="text/javascript"><!--
var curr_article;
function activer_article(id) {
	if (curr_article)
		document.getElementById(curr_article).className = "article-inactif";
	if (id) {
		document.getElementById(id).className = "article-actif";
		curr_article = id;
	}
}
//--></script>
';

	afficher_script_layer();
	echo '
</head>
<body bgcolor="#FFFFFF" text="#000000" link="#E86519" vlink="#6E003A" alink="#FF9900" TOPMARGIN="5" LEFTMARGIN="5" MARGINWIDTH="5" MARGINHEIGHT="5"';

	if ($spip_lang_rtl)
		echo " dir='rtl'";
	echo " lang='$lang_aide'>";


	// Recuperation et analyse de la structure de l'aide demandee
	$sections = analyse_aide($html);
	foreach ($sections as $section) {
		if ($section[1] == '1') {
			if ($rubrique_vue)
				fin_rubrique();
			rubrique($section[3].$section[5]);
			$rubrique_vue = true;
		} else
			article($section[5], $section[3]);
	}
	fin_rubrique();
}


function rubrique($titre, $statut = "redac") {
	global $ligne_rubrique;
	global $block_rubrique;
	global $titre_rubrique;
	global $afficher_rubrique, $ouvrir_rubrique;
	global $larubrique;

	global $aide_statut;

	$afficher_rubrique = 0;

	if (($statut == "admin" AND $aide_statut == "admin") OR ($statut == "redac")) {
		$larubrique++;
		$titre_rubrique = $titre;
		$ligne_rubrique = array();
		$block_rubrique = "block$larubrique";
		$afficher_rubrique = 1;
		$ouvrir_rubrique = 0;
	}
}

function fin_rubrique() {
	global $ligne_rubrique;
	global $block_rubrique;
	global $titre_rubrique;
	global $afficher_rubrique, $ouvrir_rubrique;
	global $texte;

	if ($afficher_rubrique && count($ligne_rubrique)) {
		echo "<div class='rubrique'>";
		if ($ouvrir_rubrique)
			echo bouton_block_visible($block_rubrique);
		else 
			echo bouton_block_invisible($block_rubrique);
		echo $titre_rubrique;
		echo "</div>\n";
		if ($ouvrir_rubrique)
			echo debut_block_visible($block_rubrique);
		else
			echo debut_block_invisible($block_rubrique);
		echo "\n";
		reset($ligne_rubrique);
		while (list(, $ligne) = each($ligne_rubrique)) {
			echo $texte[$ligne];
		}
		echo fin_block();
		echo "\n\n";
	}
}

function article($titre, $lien, $statut = "redac") {
	global $aide;
	global $ligne;
	global $ligne_rubrique;
	global $rubrique;
	global $texte;
	global $afficher_rubrique, $ouvrir_rubrique;
	global $aide_statut;
	global $spip_lang;

	if ($afficher_rubrique AND (($statut == "admin" AND $aide_statut == "admin") OR ($statut == "redac"))) {
		$ligne_rubrique[] = ++$ligne;
		
		$texte[$ligne] = '';
		$id = "ligne$ligne";

		if ($aide == $lien) {
			$ouvrir_rubrique = 1;
			$class = "article-actif";
			$texte[$ligne] .= "<script type='text/javascript'><!--\ncurr_article = '$id';\n// --></script>\n";
		}
		else {
			$class = "article-inactif";
		}
		$texte[$ligne] .= "<a class='$class' id='$id'
 href='" . generer_url_ecrire("aide_index", "aide=$lien&frame=body&var_lang=$spip_lang", false, true) .
		  "' target='droite' onClick=\"activer_article('$id');return true;\">$titre</a><br style='clear:both;'>\n";
	}
}


function analyse_aide($html, $aide=false) {

	preg_match_all(',<h([12])( class="spip")?'. '>([^/]+?)(/(.+?))?</h\1>,ism',
	$html, $regs, PREG_SET_ORDER);

	// pas de sujet precis: retourner le tableau des sujets
	if (!$aide) 	return $regs;

	unset ($regs);
	$preg = ',<h2( class="spip")?'	. ">$aide/(.+?)</h2>(.*)$,ism";
	preg_match($preg, $html, $regs);
	return preg_replace(',<h[12].*,ism', '', $regs[3]);
}

//
// Distribuer le travail
//
function exec_aide_index_dist()
{
global $img, $frame, $aide, $var_lang, $lang, $help_server;

// Recuperer les infos de langue (preferences auteur), si possible
utiliser_langue_visiteur();
if ($var_lang) changer_langue($var_lang);
if ($lang) changer_langue($lang); # pour le cas ou on a fait appel au menu de changement de langue (aide absente dans la langue x)

if (preg_match(',^([^-.]*)-([^-.]*)-([^\.]*\.(gif|jpg|png))$,', $img, $regs))
	help_img($regs);
else {
	list($html, $lastmodified) = fichier_aide();

	// si on a la doc dans un fichier, controler if_modified_since
	if ($lastmodified) {
		$gmoddate = gmdate("D, d M Y H:i:s", $lastmodified);
		header("Last-Modified: ".$gmoddate." GMT");
		if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
			# MSoft IIS is dumb
		AND !preg_match(',IIS/,', $_SERVER['SERVER_SOFTWARE'])) {

			$if_modified_since = preg_replace('/;.*/', '',
				$_SERVER['HTTP_IF_MODIFIED_SINCE']);
			$if_modified_since = trim(str_replace('GMT', '', $if_modified_since));
			if ($if_modified_since == $gmoddate) {
				include_spip('inc/headers');
				http_status(304);
				exit;
			}
		}
	} 

	header("Content-Type: text/html; charset=utf-8");
	echo _DOCTYPE_ECRIRE, "\n",
		"<html lang='",
		$GLOBALS['spip_lang'],
		"' dir='",
		($GLOBALS['spip_lang_rtl'] ? 'rtl' : 'ltr'),
		"'>\n";

	if ($frame == 'menu')
			help_menu($aide, $html);
	else if ($frame == 'body')
			help_body($aide, $html);
	else
			help_frame($aide);
	echo "\n</html>";
 }
}
?>
