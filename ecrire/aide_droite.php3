<?php

include ("inc_version.php3");

$flag_ecrire = true;	// hack : on veut pouvoir eventuellement proposer
						// l'aide en ligne depuis l'espace public via un
						// RewriteRule (c'est le cas sur uZine)

include_ecrire ("inc_texte.php3");
include_ecrire ("inc_filtres.php3");
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
<body bgcolor="#FFFFFF" text="#000000" TOPMARGIN="24" LEFTMARGIN="24" MARGINWIDTH="24" MARGINHEIGHT="24">


<FONT FACE='Georgia,Garamond,Times,serif' SIZE=3>

<?php

if (strlen($aide) < 2) $aide = "spip";


// selection de la langue
$ln = '_en';

if (!file_exists($fichier_aide = "AIDE$ln/aide")) {
	$fichier_aide = "AIDE/aide";
	$ln='';
}

$html = join('', file($fichier_aide));

$html = substr($html, strpos($html,"<$aide>") + strlen("<$aide>"));
$html = substr($html, 0, strpos($html, "</$aide>"));

echo ereg_replace("AIDE(/[^[:space:]]+\.(gif|jpg))", "AIDE$ln\\1",
justifier(propre($html)."<p>"));
echo "<font size=2>$les_notes</font><p>";

?>

</FONT>


</BODY>
</HTML>
