<?php

include ("inc.php3");


debut_page("Maintenance technique");
debut_gauche();

debut_boite_info();

echo propre("{{Cette page est uniquement accessible aux administrateurs.}}<P> Elle donne acc&egrave;s aux diff&eacute;rentes
fonctions de maintenance technique. Certaines d'entre elles donnent lieu &agrave; un processus d'authentification sp&eacute;cifique, qui
exige d'avoir un acc&egrave;s FTP au site Web.");

fin_boite_info();

debut_droite();

if ($connect_statut != '0minirezo' OR !$connect_toutes_rubriques) {
	echo "Vous n'avez pas acc&egrave;s &agrave; cette page.";
	fin_page();
	exit;
}

if ($purger_index == "oui") {
	if (verifier_action_auteur("purger_index", $hash)) {
		mysql_query("DELETE FROM spip_index_articles");
		mysql_query("DELETE FROM spip_index_auteurs");
		mysql_query("DELETE FROM spip_index_breves");
		mysql_query("DELETE FROM spip_index_mots");
		mysql_query("DELETE FROM spip_index_rubriques");
		mysql_query("DELETE FROM spip_index_dico");
	}
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

echo "\n<p align='justify'>Cette option vous permet de sauvegarder
le contenu de la base dans un fichier qui sera stock&eacute; dans le r&eacute;pertoire <i>ecrire/data/</i>.
N'oubliez pas &eacute;galement de r&eacute;cup&eacute;rer l'int&eacute;gralit&eacute; du r&eacute;pertoire <i>IMG/</i>, qui contient
les images utilis&eacute;es dans les articles et les rubriques.";

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

fin_cadre_relief();

echo "<BR>";




//
// Purger le cache
//

debut_cadre_relief();

echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=8 WIDTH=\"100%\">";
echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND=''><B>";
echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>";
echo "Vider le cache</FONT></B></TD></TR>";

echo "<TR><TD BACKGROUND=''>";

echo "<FONT FACE='Georgia,Garamond,Times,serif' SIZE=3>";

echo "\n<p align='justify'>Utilisez cette commande afin de supprimer tous les fichiers pr&eacute;sents
dans le cache SPIP. Cela permet par exemple de forcer un recalcul de toutes les pages si vous
avez fait des modifications importantes de graphisme ou de structure du site. <p align='justifty'>Si vous voulez
recalculer une seule page, passez plut&ocirc;t par l'espace public et utilisez-y le bouton &laquo; recalculer &raquo;.";

echo "\n<FORM ACTION='../spip_cache.php3' METHOD='post'>";

$hash = calculer_action_auteur("purger_cache");

echo "\n<INPUT TYPE='hidden' NAME='id_auteur' VALUE='$connect_id_auteur'>";
echo "\n<INPUT TYPE='hidden' NAME='hash' VALUE='$hash'>";
echo "\n<INPUT TYPE='hidden' NAME='purger_cache' VALUE='oui'>";
echo "\n<INPUT TYPE='hidden' NAME='redirect' VALUE='admin_tech.php3'>";
echo "\n<p><DIV align='right'><INPUT CLASS='fondo' TYPE='submit' NAME='valider' VALUE='Vider le cache'></FORM></DIV>";

echo "</FONT>";
echo "</TD></TR>";
echo "</TABLE>";



//
// Purger la base d'indexation
//

echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=8 WIDTH=\"100%\">";
echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND=''><B>";
echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#000000'>";
echo "Effacer les donn&eacute;es d'indexation</FONT></B></TD></TR>";

echo "<TR><TD BACKGROUND=''>";

echo "<FONT FACE='Georgia,Garamond,Times,serif' SIZE=3>";

echo "\n<p align='justify'>Utilisez cette commande afin de vider les tables utilis&eacute;es
par le moteur de recherche int&eacute;gr&eacute; &agrave; SPIP. Cela permet de gagner de
l'espace disque si vous avez d&eacute;sactiv&eacute; le moteur de recherche. Cela peut servir
&eacute;galement &agrave; r&eacute;indexer les documents au cas o&ugrave; vous avez restaur&eacute; une sauvegarde.

<p align='justify'>Notez que les documents modifi&eacute;s de fa&ccedil;on normale (depuis l'interface
SPIP) sont automatiquement r&eacute;index&eacute;s, cette commande n'est donc utile que de fa&ccedil;on exceptionnelle.";


echo "\n<FORM ACTION='admin_tech.php3' METHOD='post'>";

$hash = calculer_action_auteur("purger_index");

echo "\n<INPUT TYPE='hidden' NAME='hash' VALUE='$hash'>";
echo "\n<INPUT TYPE='hidden' NAME='purger_index' VALUE='oui'>";
echo "\n<p><DIV align='right'><INPUT CLASS='fondo' TYPE='submit' NAME='valider' VALUE=\"Effacer les index\"></FORM></DIV>";

echo "</FONT>";
echo "</TD></TR>";
echo "</TABLE>";


fin_cadre_relief();

echo "<BR>";




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

echo "\n<p align='justify'>Cette commande efface <i>tout</i> le contenu de la base de donn&eacute;es,
y compris <i>tous</i> les acc&egrave;s r&eacute;dacteurs et administrateurs. Apr&egrave;s l'avoir ex&eacute;cut&eacute;e, vous devrez lancer la
r&eacute;installation de SPIP pour recr&eacute;er une nouvelle base ainsi qu'un premier acc&egrave;s administrateur.";

echo "<CENTER>";

debut_boite_alerte();

echo "\n<FONT FACE='Georgia,Garamond,Times,serif' SIZE=3>";
echo "\n<p align='justify'><b>ATTENTION, la suppression des donn&eacute;es est irr&eacute;versible !</b>";

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

