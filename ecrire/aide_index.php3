<?php

include ("inc_version.php3");
include_ecrire ("inc_presentation.php3");

// Eviter les calculs evitables (surtout en client/serveur sans cache !)
$lastmodified = filemtime("aide_index.php3");
$headers_only = http_last_modified($lastmodified, time() + 24 * 3600);
if ($headers_only) exit;

include_ecrire ("inc_filtres.php3");
include_ecrire ("inc_layer.php3");
include_ecrire ("inc_texte.php3");

// Recuperer les infos de langue (preferences auteur), si possible
if (_FILE_CONNECT) {
	include_ecrire ("inc_auth.php3");
}
include_ecrire ("inc_lang.php3");
utiliser_langue_visiteur();
if ($var_lang) changer_langue($var_lang);

/////////////////////////////
// La frame de base
//
function help_frame ($aide) {
	global $spip_lang;

	echo "</head>\n";

	$frame_menu = "<frame src=\"aide_index.php3?aide=$aide&var_lang=$spip_lang&frame=menu\" name=\"gauche\" scrolling=\"auto\" noresize>\n";
	$frame_body = "<frame src=\"aide_index.php3?aide=$aide&var_lang=$spip_lang&frame=body\" name=\"droite\" scrolling=\"auto\" noresize>\n";

	if ($GLOBALS['spip_lang_rtl']) {
		echo '<frameset cols="*,160" border="0" frameborder="0" framespacing="0">';
		echo $frame_body.$frame_menu;
	}
	else {
		echo '<frameset cols="160,*" border="0" frameborder="0" framespacing="0">';
		echo $frame_menu.$frame_body;
	}
	echo '</frameset>';
	echo "\n</html>";
}



/////////////////////////////
// Le contenu demande
//

// Selection de l'aide correspondant a la langue demandee
function fichier_aide($lang_aide = '') {
	global $help_server;

	if (!$lang_aide) $lang_aide = $GLOBALS['spip_lang'];

	if (@file_exists($fichier_aide = "AIDE/$lang_aide/aide.html")) 
		return array(file($fichier_aide), $lang_aide);
	else	// reduction ISO du code langue oc_prv_ni => oc_prv => oc
		if (ereg("(.*)_", $lang_aide, $regs)
		AND $r = fichier_aide($regs[1]))
			return $r;

	else if ($help_server) {
		// Aide internet, en cache ?
		include_ecrire('inc_sites.php3');
		if (@file_exists($fichier_aide = _DIR_SESSIONS . "aide-$lang_aide-aide.html")) {
			return array(file($fichier_aide), $lang_aide);
		}
		else {
			// sinon aller la chercher sur le site d'aide
			if (ecrire_fichier(_DIR_SESSIONS . 'aide-test', "test")
			AND ($contenu = recuperer_page("$help_server/$lang_aide-aide.html"))) {
				ecrire_fichier ($fichier_aide, $contenu);
				return array($contenu, $lang_aide);
			}
		}
	}

	return false;
}


function help_body($aide, $html) {
	global $help_server;

	if (!$aide) $aide = 'spip';

	// Recuperation du contenu de l'aide demandee
	$html = analyse_aide($html, $aide);

	// Recherche des images de l'aide
	$suite = $html;
	$html = "";
	while (preg_match("@(<img([^<>]* +)? src=['\"])"
		. "((AIDE|IMG)/([-_a-zA-Z0-9]*/?)([^'\"<>]*))@i",
	$suite, $r)) {

		$image = $r[3];
		$image_plat = str_replace('/', '-', $image);

		# Image installee a l'ancienne
		if (@file_exists($image))
			$f = $image;
		else
		# Image telechargee ou a telecharger
			$f = "aide_index.php3?img=$image_plat";

		$p = strpos($suite, $r[0]);
		$html .= substr($suite, 0, $p) . $r[1].$f;
		$suite = substr($suite, $p + strlen($r[0]));
	}

	$html .= $suite;

?>
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
a:hover {color:#FF9900; text-decoration: underline;}

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

--></style>
</head>
<?php

	echo '<body bgcolor="#FFFFFF" text="#000000" TOPMARGIN="24" LEFTMARGIN="24" MARGINWIDTH="24" MARGINHEIGHT="24"';
	if ($spip_lang_rtl)
		echo " dir='rtl'";
	echo " lang='$lang_aide'>";

	if ($aide == 'spip') {
		echo '<TABLE BORDER=0 WIDTH=100% HEIGHT=60%>
<TR WIDTH=100% HEIGHT=60%>
<TD WIDTH=100% HEIGHT=60% ALIGN="center" VALIGN="middle">

<CENTER>
<img src="aide_index.php3?img=AIDE--logo-spip.gif" alt="SPIP" width="300" height="170" border="0">
</CENTER>
</TD></TR></TABLE>';
	}

	// Il faut que la langue de typo() soit celle de l'aide en ligne
	changer_typo($lang_aide);

	$html = justifier($html."<p>");
	// Remplacer les liens externes par des liens ouvrants (a cause des frames)
	$html = ereg_replace('<a href="(http://[^"]+)"([^>]*)>', '<a href="\\1"\\2 target="_blank">', $html);

	echo $html;

	if (defined('erreur_langue')) {
		include_ecrire('inc_presentation.php3');
		install_debut_html(_T('forum_titre_erreur'));
		echo "<div>".erreur_langue."</div>";
	}

}


/////////////////////////////////////
// Recuperer une image dans le cache
//
function help_img($regs) {
	global $help_server;

	list ($cache, $rep, $lang, $file, $ext) = $regs;

	header("Content-Type: image/$ext");
	if (@file_exists(_DIR_SESSIONS . 'aide-'.$cache)) {
		readfile(_DIR_SESSIONS . 'aide-'.$cache);
	} else {
		include_ecrire('inc_sites.php3');
		if (ecrire_fichier(_DIR_SESSIONS . 'aide-test', "test")
		AND ($contenu =
		recuperer_page("$help_server/$rep/$lang/$file"))) {
			echo $contenu;
			ecrire_fichier (_DIR_SESSIONS . 'aide-'.$cache, $contenu);
		} else
			header ("Location: $help_server/$rep/$lang/$file");
	}
	exit;
}

///////////////////////////////////////
// Le menu de gauche
//
function help_menu($aide, $html) {
	global $spip_lang_left, $spip_lang_rtl, $spip_lang_right;

$triangle = "url(" . _DIR_IMG_PACK . 'triangle'.$spip_lang_rtl.'.gif) ';

echo '<style type="text/css">
<!--
	a {text-decoration: none; }
	A:Hover {text-decoration: underline;}

	.article-inactif {
		float: '.$spip_lang_left.';
		text-align: '.$spip_lang_left.';
		width: 80%;
		background: ' . $triangle . $spip_lang_left.' center no-repeat;
		margin: 2px;
		padding: 0px;
		padding-'.$spip_lang_left.': 20px;
		font-family: Arial, Sans, sans-serif;
		font-size: 12px;
	}
	.article-actif {
		float: '.$spip_lang_right.';
		text-align: '.$spip_lang_right.';
		width: 80%;
		background: ' . $triangle . $spip_lang_right.' center no-repeat;
		margin: 4px;
		padding: 0px;
		padding-'.$spip_lang_right.': 20px;
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
</style>
<script type="text/javascript"><!--
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

  echo $browser_layer,'
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
		$url = "aide_index.php3?aide=$lien&frame=body&var_lang=$spip_lang";
		if ($aide == $lien) {
			$ouvrir_rubrique = 1;
			$class = "article-actif";
			$texte[$ligne] .= "<script type='text/javascript'><!--\ncurr_article = '$id';\n// --></script>\n";
		}
		else {
			$class = "article-inactif";
		}
		$texte[$ligne] .= "<a class='$class' id='$id' href='$url' target='droite' ".
			"onClick=\"activer_article('$id');return true;\">$titre</a><br style='clear:both;'>\n";
	}
}


function analyse_aide($html, $aide=false) {
	if (is_array($html))
		$html = join('', $html);

	if (!$html)
		define ('erreur_langue', _T('aide_non_disponible'));

	preg_match_all(',<h([12])( class="spip")?'. '>([^/]+?)(/(.+?))?</h\1>,ism',
	$html, $regs, PREG_SET_ORDER);

	if ($aide) {
		unset ($regs);
		$preg = ',<h2( class="spip")?'
		. ">$aide/(.+?)</h2>(.*)$,ism";
		preg_match($preg, $html, $regs);
		$regs = preg_replace(',<h[12].*,ism', '', $regs[3]);
	}

	return $regs;
}


//
// Distribuer le travail
//
if (preg_match(',^([^-.]*)-([^-.]*)-([^\.]*\.(gif|jpg|png))$,', $img, $regs))
	help_img($regs);
else {
	list($html, $l, $url_aide) = fichier_aide();

	// On n'a pas d'aide du tout
	if (!$html) {
		// Renvoyer sur l'aide en ligne du serveur externe
		if ($help_server)
			@Header("Location: $help_server/" . _DIR_RESTREINT_ABS . "aide_index.php3?var_lang=$spip_lang");
		// Ou alors message d'erreur
		else {
			include_ecrire('inc_presentation.php3');
			install_debut_html(_L('Erreur : documentation non disponible'));
			echo "<p>"._L('Votre site a &eacute;t&eacute; install&eacute; sans aide en ligne, et n\'est pas connect&eacute; &agrave; un serveur ext&eacute;rieur d\'aide en ligne. Sorry.');
			install_fin_html();
		}
	} else {
		echo debut_entete(_T('info_aide_en_ligne'),
				  "Content-Type: text/html; charset=utf-8");
		if ($frame == 'menu')
			help_menu($aide, $html);
		else if ($frame == 'body')
			help_body($aide, $html);
		else
			help_frame($aide);
	}
}

?>
