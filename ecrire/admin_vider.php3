<?php

include ("inc.php3");

include_ecrire ("inc_admin.php3");

debut_page("Maintenance technique : gestion du cache", "administration", "base");


echo "<br><br><br>";
gros_titre("Maintenance technique");
barre_onglets("administration", "vider");


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

if ($purger_index == "oui") {
	if (verifier_action_auteur("purger_index", $hash)) {
		spip_query("DELETE FROM spip_index_articles");
		spip_query("DELETE FROM spip_index_auteurs");
		spip_query("DELETE FROM spip_index_breves");
		spip_query("DELETE FROM spip_index_mots");
		spip_query("DELETE FROM spip_index_rubriques");
		spip_query("DELETE FROM spip_index_syndic");

		spip_query("DELETE FROM spip_index_dico");

		include_ecrire('inc_index.php3');
		creer_liste_indexation();
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
echo "\n<INPUT TYPE='hidden' NAME='redirect' VALUE='admin_vider.php3'>";
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

echo "\n<p align='justify'>";
if (lire_meta('activer_moteur') == 'oui')
	echo "<b>Le moteur de recherche est activ&eacute;.</b> Utilisez cette commande
		si vous souhaitez proc&eacute;der &agrave; une r&eacute;indexation rapide (apr&egrave;s restauration
		d'une sauvegarde par exemple). Notez que les documents modifi&eacute;s de
		fa&ccedil;on normale (depuis l'interface SPIP) sont automatiquement
		r&eacute;index&eacute;s&nbsp;: cette commande n'est donc utile que de fa&ccedil;on exceptionnelle.";
else {
	echo "<b>Le moteur de recherche n'est pas activ&eacute;.</b>";
	$row = spip_fetch_array(spip_query("SELECT COUNT(*) AS cnt FROM spip_index_articles"));
	if ($row['cnt'])
		echo " Utilisez cette commande afin de vider les tables d'indexation utilis&eacute;es
			par le moteur de recherche int&eacute;gr&eacute; &agrave; SPIP. Cela vous permettra
			de gagner de l'espace disque.";
	else
		echo " Les tables d'indexation du moteur sont vides.";

}

echo "\n<FORM ACTION='admin_vider.php3' METHOD='post'>";

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

