<?php

include ("inc.php3");


debut_page("Maintenance technique", "administration", "base");


echo "<br><br><br>";
gros_titre("Maintenance technique");
barre_onglets("administration", "vider");


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
		spip_query("DELETE FROM spip_index_articles");
		spip_query("DELETE FROM spip_index_auteurs");
		spip_query("DELETE FROM spip_index_breves");
		spip_query("DELETE FROM spip_index_mots");
		spip_query("DELETE FROM spip_index_rubriques");
		spip_query("DELETE FROM spip_index_syndic");

		spip_query("DELETE FROM spip_index_dico");
	}
}




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






fin_page();


?>

