<?php

include ("inc_version.php3");
include_ecrire ("inc_layer.php3");

// Eviter les calculs evitables (surtout en client/serveur sans cache !)
#$lastmodified = filemtime("aide_index.php3");
#$headers_only = http_last_modified($lastmodified, time() + 24 * 3600);
#if ($headers_only) exit;

// Recuperer les infos de langue (preferences auteur), si possible
if (@file_exists("inc_connect.php3")) {
	include_ecrire ("inc_auth.php3");
}
include_ecrire ("inc_lang.php3");
utiliser_langue_visiteur();
if ($var_lang) changer_langue($var_lang);

// Debut page
function entetes_html() {
	header('Content-Type: text/html; charset=utf-8');
	echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">';
	echo "<html dir=\"".($GLOBALS['spip_lang_rtl'] ? 'rtl' : 'ltr')."\">";
	echo "<head>";
	echo "<title>"._T('info_aide_en_ligne')."</title>";
}


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

	if (!$lang_aide) $lang_aide = $GLOBALS['spip_lang'];

	if (@file_exists($fichier_aide = "AIDE/$lang_aide/$lang_aide-aide.html")) 
		return array(file($fichier_aide), $lang_aide);
	else	// reduction ISO du code langue oc_prv_ni => oc_prv => oc
		if (ereg("(.*)_", $lang_aide, $regs)
		AND $r = fichier_aide($regs[1]))
			return $r;

	else {
		// Aide internet, en cache ?
		include_ecrire('inc_sites.php3');
		if (@file_exists($fichier_aide = "data/aide-$lang_aide-aide.html"))
			return array(file($fichier_aide), $lang_aide, "http://www.spip.net/aide_tmp/$lang_aide/", true);
		else {
			// sinon aller la chercher sur le site d'aide
			if ($contenu = recuperer_page("http://www.spip.net/aide_tmp/$lang_aide-aide.html")) {
				$ecrire_cache = ecrire_fichier ($fichier_aide, $contenu);
				return array($contenu, $lang_aide, "http://www.spip.net/aide_tmp/$lang_aide/", $ecrire_cache);
			} else
				define ('erreur_langue', _L('Impossible de t&eacute;l&eacute;charger l\'aide en ligne pour cette langue. Erreur de r&eacute;seau, ou aide non traduite. Si vous utilisez ce site en-dehors d\'une connexion Internet, vous pouvez installer l\'aide en local.'));
		}
	}

	return false;
}


function help_body($aide) {

	if (!$aide) $aide = 'spip';

	// Recuperation du contenu de l'aide demandee
	list($html, $l, $url_aide, $ecrire_cache) = fichier_aide();
	$html = analyse_aide($html, $aide);

	// Recherche des images de l'aide
	$suite = $html;
	$html = "";
	while (ereg("AIDE/([-_a-zA-Z0-9]+\.(gif|jpg))", $suite, $r)) {
		$f = $r[1];
		
		# Image installee a l'ancienne
		if (@file_exists("AIDE/$l/$f"))
			$f = "AIDE/$l/$f";
		else
		# Image telechargee
		if (@file_exists("data/aide-${l}-$f")) $f = "aide_index.php3?img=aide/${l}-$f";
		else
		# Image a telecharger
		if ($url_aide) {
			if ($ecrire_cache AND $contenu =
			recuperer_page("http://www.spip.net/aide_tmp/$l/$f")
			AND ecrire_fichier ("data/aide-${l}-$f", $contenu))
				$f = "aide_index.php3?img=aide-${l}-$f";
			else
				$f = "http://www.spip.net/aide_tmp/$l/$f"; # erreur
		}

		$p = strpos($suite, $r[0]);
		$html .= substr($suite, 0, $p) . $f;
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

	include_ecrire ("inc_texte.php3");
	include_ecrire ("inc_filtres.php3");

	echo '<body bgcolor="#FFFFFF" text="#000000" TOPMARGIN="24" LEFTMARGIN="24" MARGINWIDTH="24" MARGINHEIGHT="24"';
	if ($spip_lang_rtl)
		echo " dir='rtl'";
	echo " lang='$lang_aide'>";

	if ($aide == 'spip') {
		echo '<TABLE BORDER=0 WIDTH=100% HEIGHT=60%>
<TR WIDTH=100% HEIGHT=60%>
<TD WIDTH=100% HEIGHT=60% ALIGN="center" VALIGN="middle">

<CENTER>
<img src="aide_index.php3?img=aide/-logo-spip.gif" alt="SPIP" width="300" height="170" border="0">
</CENTER>
</TD></TR></TABLE>';
	}

	// Il faut que la langue de typo() soit celle de l'aide en ligne
	changer_typo($lang_aide);

	$html = justifier(propre($html)."<p>");
	// Remplacer les liens externes par des liens ouvrants (a cause des frames)
	$html = ereg_replace('<a href="(http://[^"]+)"([^>]*)>', '<a href="\\1"\\2 target="_blank">', $html);

	echo $html;

	if (defined('erreur_langue'))
		echo "<div>".erreur_langue."</div>";

	echo "<font size=2>$les_notes</font><p>";
	echo "</body></html>";

}


/////////////////////////////////////
// Recuperer une image dans le cache
//
function help_img($regs) {
	list ($cache, $lang, $file, $ext) = $regs;
	header("Content-Type: image/$ext");
	if (file_exists('data/'.$cache)) {
		readfile('data/'.$cache);
	} else {
		include_ecrire('inc_sites.php3');
		if ($contenu =
		recuperer_page("http://www.spip.net/aide_tmp/$lang/$file")) {
			echo $contenu;
			ecrire_fichier ('data/'.$cache, $contenu);
		} else
			header ("Location: http://www.spip.net/aide_tmp/$lang/$file");
	}
	exit;
}

///////////////////////////////////////
// Le menu de gauche
//
function help_menu($aide) {
	global $spip_lang_left, $spip_lang_rtl, $spip_lang_right;

echo '<style type="text/css">
<!--
	a {text-decoration: none; }
	A:Hover {text-decoration: underline;}

	.article-inactif {
		float: '.$spip_lang_left.';
		text-align: '.$spip_lang_left.';
		width: 80%;
		background: url(img_pack/triangle'.$spip_lang_rtl.'.gif) '
	. $spip_lang_left.' center no-repeat;
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
		background: url(img_pack/triangle'.$spip_lang_rtl.'.gif) '.$spip_lang_right.' center no-repeat;
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

afficher_script_layer();

echo '
</head>
<body bgcolor="#FFFFFF" text="#000000" link="#E86519" vlink="#6E003A" alink="#FF9900" TOPMARGIN="5" LEFTMARGIN="5" MARGINWIDTH="5" MARGINHEIGHT="5"';

	if ($spip_lang_rtl)
		echo " dir='rtl'";
	echo " lang='$lang_aide'>";


	// Recuperation et analyse de la structure de l'aide demandee
	list($html, $l, $url_aide, $ecrire_cache) = fichier_aide();
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
if (preg_match(',^aide/([^-.]*)-([^\.]*\.(gif|jpg|png))$,', $img, $regs))
	help_img($regs);
else {
	entetes_html();
	if ($frame == 'menu')
		help_menu($aide);
	else if ($frame == 'body')
		help_body($aide);
	else
		help_frame($aide);
}

?>
