<?php

include ("inc.php3");


debut_page("Maintenance technique", "administration", "base");


echo "<br><br><br>";
gros_titre("Maintenance technique");
barre_onglets("administration", "sauver");


debut_gauche();

debut_boite_info();

echo propre("{{Cette page est uniquement accessible aux responsables du site.}}<P> Elle donne acc&egrave;s aux diff&eacute;rentes
fonctions de maintenance technique. Certaines d'entre elles donnent lieu &agrave; un processus d'authentification sp&eacute;cifique, qui
exige d'avoir un acc&egrave;s FTP au site Web.");

fin_boite_info();

debut_droite();

if ($connect_statut != '0minirezo' OR !$connect_toutes_rubriques) {
	echo "Vous n'avez pas acc&egrave;s &agrave; cette page.";
	fin_page();
	exit;
}



//
// Sauvegarde de la base
//

debut_cadre_relief();

echo "<TABLE BORDER=0 CELLSPACING=0 CELLPADDING=5 WIDTH=\"100%\">";
echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND=''><B>";
echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>";
echo "Sauvegarder le contenu de la base</FONT></B></TD></TR>";

echo "<TR><TD BACKGROUND=''>";

echo "<FONT FACE='Georgia,Garamond,Times,serif' SIZE=3>";
echo "\n<FORM ACTION='export_all.php3' METHOD='get'>";

echo "\n<p align='justify'>";

echo '<img src="img_pack/warning.gif" alt="warning.gif" width="48" height="48" align="right">';
echo "Cette option vous permet de sauvegarder
le contenu de la base dans un fichier qui sera stock&eacute; dans le r&eacute;pertoire <i>ecrire/data/</i>.
N'oubliez pas &eacute;galement de r&eacute;cup&eacute;rer l'int&eacute;gralit&eacute; du r&eacute;pertoire <i>IMG/</i>, qui contient
les images utilis&eacute;es dans les articles et les rubriques.";

echo "<p>" . propre("Attention: cette sauvegarde ne pourra &ecirc;tre restaur&eacute;e
	QUE dans un site install&eacute; sous la m&ecirc;me version de SPIP. L'erreur
	consistant &agrave; faire une sauvegarde avant de faire une mise &agrave; jour
	de SPIP est courante... Pour plus de d&eacute;tails consultez [la documentation de SPIP->http://www.uzine.net/article1489.html].");

if ($flag_gz) {
	echo "\n<p align='justify'>Vous pouvez choisir de sauvegarder le fichier sous forme compress&eacute;e, afin
	d'&eacute;courter son transfert chez vous ou sur un serveur de sauvegardes, et d'&eacute;conomiser de l'espace disque.<p>";
	echo "\n<INPUT TYPE='radio' NAME='gz' VALUE='1' id='gz_on' CHECKED><label for='gz_on'> sauvegarde compress&eacute;e sous <b>ecrire/data/dump.xml.gz</b> </label><BR>\n";
	echo "\n<INPUT TYPE='radio' NAME='gz' VALUE='0' id='gz_off'><label for='gz_off'> sauvegarde non compress&eacute;e sous <b>ecrire/data/dump.xml</b> </label><BR>\n";
}
else {
	echo "\n<p align='justify'>La sauvegarde sera faite dans le fichier non compress&eacute; <b>ecrire/data/dump.xml</b>.";
	echo "\n<INPUT TYPE='hidden' NAME='gz' VALUE='0'>";
}

echo "\n<p><DIV align='right'><INPUT CLASS='fondo' TYPE='submit' NAME='valider' VALUE='Sauvegarder la base'></FORM></DIV>";

echo "</FONT>";
echo "</TD></TR>";
echo "</TABLE>";


//
// Restauration de la base
//

echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=8 WIDTH=\"100%\">";
echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND=''><B>";
echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#000000'>";
echo "Restaurer le contenu d'une sauvegarde de la base</FONT></B></TD></TR>";

echo "<TR><TD BACKGROUND=''>";

echo "<FONT FACE='Georgia,Garamond,Times,serif' SIZE=3>";
echo "\n<FORM ACTION='import_all.php3' METHOD='get'>";

echo "\n<p align='justify'> Cette option vous permet de restaurer une sauvegarde pr&eacute;c&eacute;demment
effectu&eacute;e de la base. A cet effet, le fichier contenant la sauvegarde doit avoir &eacute;t&eacute;
plac&eacute; dans le r&eacute;pertoire <i>ecrire/data/</i>.
Soyez prudent avec cette fonctionnalit&eacute;&nbsp;: <b>les modifications, pertes &eacute;ventuelles, sont
irr&eacute;versibles.</b>";


if ($flag_gz) {
	$fichier_defaut = 'dump.xml.gz';
	$texte_compresse = "(celui-ci peut &ecirc;tre compress&eacute; ou non)&nbsp;";
}
else {
	$fichier_defaut = 'dump.xml';
	$texte_compresse = "<i>non compress&eacute;</i> (votre serveur ne supportant pas cette fonctionnalit&eacute;)&nbsp;";
}

echo "\n<p>Veuillez entrer le nom du fichier $texte_compresse:";
echo "\n<p><FONT SIZE=3><ul><INPUT TYPE='text' NAME='archive' VALUE='$fichier_defaut' SIZE='30'></ul></FONT>";

echo "\n<p><DIV align='right'><INPUT CLASS='fondo' TYPE='submit' NAME='valider' VALUE='Restaurer la base'></DIV></FORM>";

echo "</FONT>";
echo "</TD></TR>";
echo "</TABLE>";


//
// Lien vers la reparation
//

$res = spip_query("SELECT version()");
if (($row = mysql_fetch_array($res)) AND ($row[0] >= '3.23.14')) {

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=8 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND=''><B>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#000000'>";
	echo "R&eacute;parer la base de donn&eacute;es</FONT></B></TD></TR>";

	echo "<TR><TD BACKGROUND=''>";

	echo "<FONT FACE='Georgia,Garamond,Times,serif' SIZE=3>";
	echo "\n<FORM ACTION='admin_repair.php3' METHOD='get'>";

	echo "\n<p align='justify'>Si votre base de donn&eacute;es a
		crash&eacute;, vous pouvez tenter une r&eacute;paration
		automatique.";

	echo "\n<p><DIV align='right'><INPUT CLASS='fondo' TYPE='submit' NAME='valider' VALUE='Tenter une r&eacute;paration'></DIV></FORM>";

	echo "</FONT>";
	echo "</TD></TR>";
	echo "</TABLE>";

}


fin_cadre_relief();

echo "<BR>";




fin_page();


?>
