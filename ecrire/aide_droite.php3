<?php

include ("inc_version.php3");

// Recuperer les infos de langue (preferences auteur), si possible
if (@file_exists("inc_connect.php3")) {
	include_ecrire ("inc_auth.php3");
}

include_ecrire ("inc_lang.php3");
utiliser_langue_visiteur();
if ($var_lang) changer_langue($var_lang);

// Selection du fichier d'aide correspondant a la langue
function fichier_aide($lang_aide) {
	if (@file_exists($fichier_aide = "AIDE/$lang_aide/aide")) 
		return array($fichier_aide, $lang_aide);
	else	// reduction ISO du code langue oci_prv_ni => oci_prv => oci
		if (ereg("(.*)_", $lang_aide, $regs))
			return fichier_aide($regs[1]);

	return false;
}

if (!$aide) $aide = 'spip';
$lang_aide = $spip_lang;

// Recuperation du contenu de l'aide demandee
list($fichier_aide, $l) = fichier_aide($lang_aide);
if (!$fichier_aide)
	$html = _T('aide_non_disponible');
else {
	if ($var_lang) {
		$lastmodified = filemtime($fichier_aide);
		$headers_only = http_last_modified($lastmodified);
		if ($headers_only) exit;
	}
	
	$html = join('', file($fichier_aide));
	$html = substr($html, strpos($html,"<$aide>") + strlen("<$aide>"));
	$html = substr($html, 0, strpos($html, "</$aide>"));

	// Localisation des images de l'aide (si disponibles)
	$suite = $html;
	$html = "";
	while (ereg("AIDE/([-_a-zA-Z0-9]+\.(gif|jpg))", $suite, $r)) {
		$f = $r[1];
		if (@file_exists("AIDE/$l/$f")) $f = "$l/$f";
		else if (@file_exists("AIDE/fr/$f")) $f = "fr/$f";
		$p = strpos($suite, $r[0]);
		$html .= substr($suite, 0, $p) . "AIDE/$f";
		$suite = substr($suite, $p + strlen($r[0]));
	}
	$html .= $suite;
}

?>
<html>
<head>
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
echo ">";

if ($aide == 'spip') {
	echo '<TABLE BORDER=0 WIDTH=100% HEIGHT=60%>
<TR WIDTH=100% HEIGHT=60%>
<TD WIDTH=100% HEIGHT=60% ALIGN="center" VALIGN="middle">

<CENTER>
<img src="img_pack/logo-spip.gif" alt="SPIP" width="300" height="170" border="0">
</CENTER>

</TD></TR></TABLE>';
}

// hack pour que la langue de typo() soit celle de l'aide en ligne
$spip_lang = $lang_aide;

$html = justifier(propre($html)."<p>");
// Remplacer les liens externes par des liens ouvrants (a cause des frames)
$html = ereg_replace('<a href="(http://[^"]+)"([^>]*)>', '<a href="\\1"\\2 target="_blank">', $html);

echo $html;
echo "<font size=2>$les_notes</font><p>";

?>


</body>
</html>
