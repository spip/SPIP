<?php

include ("inc.php3");


debut_page("Maintenance technique : effacer la base", "administration", "base");


echo "<br><br><br>";
gros_titre("Maintenance technique");
barre_onglets("administration", "effacer");


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
// Effacement total
//

debut_cadre_relief();

echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=8 WIDTH=\"100%\">";
echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND=''><B>";
echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>";
echo "Effacer la base de donn&eacute;es SPIP</FONT></B></TD></TR>";

echo "<TR><TD BACKGROUND=''>";

echo "<FONT FACE='Georgia,Garamond,Times,serif' SIZE=3>";

echo "\n<p align='justify'>";
echo '<img src="img_pack/warning.gif" alt="warning.gif" width="48" height="48" align="right">';
echo "Cette commande efface <i>tout</i> le contenu de la base de donn&eacute;es,
y compris <i>tous</i> les acc&egrave;s r&eacute;dacteurs et administrateurs. Apr&egrave;s l'avoir ex&eacute;cut&eacute;e, vous devrez lancer la
r&eacute;installation de SPIP pour recr&eacute;er une nouvelle base ainsi qu'un premier acc&egrave;s administrateur.";

echo "<CENTER>";

debut_boite_alerte();

echo "\n<FONT FACE='Georgia,Garamond,Times,serif' SIZE=3>";
echo "\n<p align='justify'><b>ATTENTION, la suppression des donn&eacute;es est irr&eacute;versible&nbsp;!</b>";

echo "\n<FORM ACTION='delete_all.php3' METHOD='get'>";
echo "\n<p><DIV align='right'><INPUT CLASS='fondo' TYPE='submit' NAME='valider' VALUE='Effacer TOUT'></FORM></DIV>";

echo "\n</FONT>";

fin_boite_alerte();

echo "</CENTER>";

echo "</FONT>";
echo "</TD></TR>";
echo "</TABLE>";

fin_cadre_relief();

echo "<BR>";




fin_page();


?>

