<?php

include ("inc.php3");

include_ecrire ("inc_admin.php3");

debut_page(_T('onglet_vider_cache'), "administration", "cache");


echo "<br><br><br>";
gros_titre(_T('titre_admin_vider'));
// barre_onglets("administration", "vider");


debut_gauche();

debut_boite_info();

echo _T('info_gauche_admin_vider');

fin_boite_info();

debut_droite();

if ($connect_statut != '0minirezo' OR !$connect_toutes_rubriques) {
	echo _T('avis_non_acces_page');
	fin_page();
	exit;
}

if ($purger_index == "oui") {
	if (verifier_action_auteur("purger_index", $hash)) {
		include_ecrire('inc_index.php3');
		purger_index();
		creer_liste_indexation();
	}
}




//
// Purger le cache
//

debut_cadre_relief();

echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=8 WIDTH=\"100%\">";
echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND=''><B>";
echo "<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3 COLOR='#FFFFFF'>";
echo _T('texte_vider_cache')."</FONT></B></TD></TR>";

echo "<TR><TD class='serif'>";

echo "\n<p align='justify'>"._T('texte_suppression_fichiers')."<p align='justify'>"._T('texte_recalcul_page');

echo "\n<FORM ACTION='../spip_cache.php3' METHOD='post'>";

$hash = calculer_action_auteur("purger_cache");

echo "\n<INPUT TYPE='hidden' NAME='id_auteur' VALUE='$connect_id_auteur'>";
echo "\n<INPUT TYPE='hidden' NAME='hash' VALUE='$hash'>";
echo "\n<INPUT TYPE='hidden' NAME='purger_cache' VALUE='oui'>";
echo "\n<INPUT TYPE='hidden' NAME='redirect' VALUE='admin_vider.php3'>";
echo "\n<p><DIV align='right'><INPUT CLASS='fondo' TYPE='submit' NAME='valider' VALUE='"._T('bouton_vider_cache')."'></FORM></DIV>";
echo "</TD></TR>";
echo "</TABLE>";


//
// Quota et taille du cache
//
echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=8 WIDTH=\"100%\">";
echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND=''><B>";
echo "<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3 COLOR='#000000'>";
echo _L('Taille du r&eacute;pertoire cache')."</FONT></B></TD></TR>";


echo "<TR><TD class='serif'>";

list ($taille) = spip_fetch_array(spip_query(
"SELECT SUM(taille) FROM spip_caches WHERE type='t'"));

if ($taille>0) {
	$info = _L("La taille du cache est actuellement de "
	.taille_en_octets($taille).".");
} else
	$info = _L('Le cache est vide.');

echo "<p align='justify'><b>$info</b></p>\n";

echo "\n<p align='justify'>";
if ($quota_cache) {
	echo _L('SPIP essaie de limiter la taille du r&eacute;pertoire
	<code>CACHE/</code> de ce site &agrave; environ')
	.' <b>' .taille_en_octets($quota_cache*1024*1024).'</b> '._L('de donn&eacute;es.');
} else {
	echo _L('Ce site ne pr&eacute;voit pas de limitation de taille du r&eacute;pertoire <code>CACHE/</code>.');
}

	echo "\n";
	echo _L('Les fichiers du cache sont enregistr&eacute;s en mode '
	.($compresser_cache?'':'non ').'compress&eacute;.');
	echo ' '._L('(Ces param&egrave;tres sont modifiables par l\'administrateur du site.)').'</p>';

echo "</TD></TR>";
echo "</TABLE>";



//
// Purger la base d'indexation
//

echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=8 WIDTH=\"100%\">";
echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND=''><B>";
echo "<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3 COLOR='#FFFFFF'>";
echo _T('texte_effacer_donnees_indexation')."</FONT></B></TD></TR>";

echo "<TR><TD class='serif'>";

echo "\n<p align='justify'>";
if (lire_meta('activer_moteur') == 'oui')
	echo _T('texte_moteur_recherche_active');
else {
	echo "<b>"._T('texte_moteur_recherche_non_active')."</b> ";
	$row = spip_fetch_array(spip_query("SELECT COUNT(*) AS cnt FROM spip_index_articles"));
	if ($row['cnt'])
		echo _T('texte_commande_vider_tables_indexation');
	else
		echo _T('texte_tables_indexation_vides');

}

echo "\n<FORM ACTION='admin_vider.php3' METHOD='post'>";

$hash = calculer_action_auteur("purger_index");

echo "\n<INPUT TYPE='hidden' NAME='hash' VALUE='$hash'>";
echo "\n<INPUT TYPE='hidden' NAME='purger_index' VALUE='oui'>";
echo "\n<p><DIV align='right'><INPUT CLASS='fondo' TYPE='submit' NAME='valider' VALUE=\""._T('bouton_effacer_index')."\"></FORM></DIV>";

echo "</TD></TR>";
echo "</TABLE>";


fin_cadre_relief();

echo "<BR>";

fin_page();


?>

