<?php

include ("inc_version.php3");
include_ecrire ("inc_lang.php3");
utiliser_langue_visiteur();

// Recuperer les infos de langue (preferences auteur), si possible
if (file_exists("inc_connect.php3")) {
	include_ecrire ("inc_auth.php3");
}

include_ecrire ("inc_texte.php3");
include_ecrire ("inc_filtres.php3");

// Selection du fichier d'aide correspondant a la langue
$lang_aide = $GLOBALS['spip_lang'];
if (!file_exists($fichier_aide = "AIDE/$lang_aide/aide")) {
	$fichier_aide = "AIDE/fr/aide";
	$lang_aide = 'fr';
	changer_langue('fr');
}

?>
<HTML>
<head>
<style><!--
	.forml {width: 100%; background-color: #E4E4E4; background-position: center bottom; float: none; color: #000000}
	.formo {width: 100%; background-color: #EDF3FE; background-position: center bottom; float: none;}
	.fondl {background-color: #EDF3FE; background-position: center bottom; float: none; color: #000000}
	.fondo {background-color: #044476; background-position: center bottom; float: none; color: #FFFFFF}
	.fondf {background-color: #FFFFFF; border-style: solid ; border-width: 1; border-color: #E86519; color: #E86519}
	.profondeur {border-right-color:white; border-top-color:#666666; border-left-color:#666666; border-bottom-color:white; border-style:solid}
	.hauteur {border-right-color:#666666; border-top-color:white; border-left-color:white; border-bottom-color:#666666; border-style:solid}
	label {cursor: pointer;}
	.arial1 {font-family: Arial, Helvetica, sans-serif; font-size: 10px;}
	.arial2 {font-family: Arial, Helvetica, sans-serif; font-size: 12px;}

	a {text-decoration: none;}
	a:hover {color:#FF9900; text-decoration: underline;}

h3.spip {
	font-family: Verdana,Arial,Helvetica,sans-serif;
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
<body bgcolor="#FFFFFF" text="#000000" TOPMARGIN="24" LEFTMARGIN="24" MARGINWIDTH="24" MARGINHEIGHT="24"<?php
if ($spip_lang_rtl)
	echo " dir='rtl'";
echo ">";
echo "<FONT FACE='Georgia,Garamond,Times,serif' SIZE=3>";

if (!$aide) {
	$aide = 'spip';
	echo '<TABLE BORDER=0 WIDTH=100% HEIGHT=60%>
<TR WIDTH=100% HEIGHT=60%>
<TD WIDTH=100% HEIGHT=60% ALIGN="center" VALIGN="middle">

<CENTER>
<img src="img_pack/logo-spip.gif" alt="SPIP" width="300" height="170" border="0">
</CENTER>

</TD></TR></TABLE>';
}


// Analyser le fichier d'aide
$html = join('', file($fichier_aide));
$html = substr($html, strpos($html,"<$aide>") + strlen("<$aide>"));
$html = substr($html, 0, strpos($html, "</$aide>"));

// Localisation des images de l'aide (si disponibles)
$suite = $html;
$html = "";
while (ereg("AIDE/([-_a-zA-Z0-9]+\.(gif|jpg))", $suite, $r)) {
	$f = $r[1];
	if (file_exists("AIDE/$lang_aide/$f")) $f = "$lang_aide/$f";
	else if (file_exists("AIDE/fr/$f")) $f = "fr/$f";
	$p = strpos($suite, $r[0]);
	$html .= substr($suite, 0, $p) . "AIDE/$f";
	$suite = substr($suite, $p + strlen($r[0]));
}
$html .= $suite;

// hack pour que la langue de typo() soit celle de l'aide en ligne
$langue_site = $lang_aide;

echo justifier(propre($html)."<p>");
echo "<font size=2>$les_notes</font><p>";

?>

</FONT>


</BODY>
</HTML>
