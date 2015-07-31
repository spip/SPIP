<?

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_INSTALL")) return;
define("_ECRIRE_INC_INSTALL", "1");



function debut_html($titre="Installation du syst&egrave;me de publication...") {

?>
<HTML>
<HEAD>
<TITLE><? echo $titre; ?></TITLE>
<META HTTP-EQUIV="Expires" CONTENT="0">
<META HTTP-EQUIV="cache-control" CONTENT="no-cache,no-store">
<META HTTP-EQUIV="pragma" CONTENT="no-cache">
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=iso-8859-1">

<style>
<!--
	a {text-decoration: none; }
	A:Hover {color:#FF9900; text-decoration: underline;}
	.forml {width: 100%; background-color: #FFCC66; background-position: center bottom; float: none; color: #000000}
	.formo {width: 100%; background-color: #FFF0E0; background-position: center bottom; weight: bold; float: none; color: #000000}
	.fondl {background-color: #FFCC66; background-position: center bottom; float: none; color: #000000}
	.fondo {background-color: #FFF0E0; background-position: center bottom; float: none; color: #000000}
	.fondf {background-color: #FFFFFF; border-style: solid ; border-width: 1; border-color: #E86519; color: #E86519}
-->
</style>
</HEAD>

<body bgcolor="#FFFFFF" text="#000000" link="#E86519" vlink="#6E003A" alink="#FF9900" TOPMARGIN="0" LEFTMARGIN="0" MARGINWIDTH="0" MARGINHEIGHT="0">

<BR><BR><BR>
<CENTER>
<TABLE WIDTH=450>
<TR><TD WIDTH=450>
<FONT FACE="Verdana,Arial,Helvetica,sans-serif" SIZE=4 COLOR="#970038"><B><? 
	echo $titre; 
?></B></FONT>
<FONT FACE="Georgia,Garamond,Times,serif" SIZE=3>
<?

}


function fin_html() {

	echo '
	</FONT>
	</TD></TR></TABLE>
	</CENTER>
	</BODY>
	</HTML>
	';
}


function bad_dirs($bad_dirs) {
		echo "
<BR><FONT FACE=\"Verdana,Arial,Helvetica,sans-serif\" SIZE=3>Pr&eacute;liminaire : <B>R&eacute;gler les droits d'acc&egrave;s</B></FONT>

<P><B>Les r&eacute;pertoires suivants ne sont pas accessibles en &eacute;criture&nbsp;: <UL>$bad_dirs.</UL> </B>

<P>Pour y rem&eacute;dier, utilisez votre client FTP afin de r&eacute;gler les droits d'acc&egrave;s de chacun
de ces r&eacute;pertoires. La proc&eacute;dure est expliqu&eacute;e en d&eacute;tail dans le guide d'installation.

<P>Une fois cette manipulation effectu&eacute;e, vous pourrez <B><A HREF='spip_test_dirs.php3'>recharger
cette page</A> afin de commencer r&eacute;ellement l'installation.";

}

?>
