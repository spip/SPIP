<?php

include ("inc.php3");

// cette page gere les deux types de forums ; forum_admin.php3 n'est qu'une coquille vide
if ($admin=='oui') {
	debut_page(_T('titre_page_forum'), "redacteurs", "forum-admin");
	$statutforum = 'privadm';
	$urlforum = 'forum_admin.php3';
} else {
	debut_page(_T('titre_forum'), "redacteurs", "forum-interne");
	$statutforum = 'privrac';
	$urlforum = 'forum.php3';
}

debut_gauche();


//
// Raccourcis
//
/*
	debut_raccourcis();
	// rien
	fin_raccourcis();
*/


debut_droite();

if ($admin=='oui')
	gros_titre(_T('titre_cadre_forum_administrateur'));
else
	gros_titre(_T('titre_cadre_forum_interne'));

if ($admin == 'oui' AND $connect_statut != "0minirezo") {
	echo _T('avis_non_acces_page');
	exit;
}

echo "<div class='serif2'>";

if (!$debut) $debut = 0;

$query_forum = "SELECT COUNT(*) AS cnt FROM spip_forum WHERE statut='$statutforum' AND id_parent=0";
$result_forum = spip_query($query_forum);
$total = 0;
if ($row = spip_fetch_array($result_forum)) $total = $row['cnt'];

if ($total > 10) {
	echo "<p>";
	for ($i = 0; $i < $total; $i = $i + 10){
		if ($i > 0) echo " | ";
		if ($i == $debut)
			echo "<FONT SIZE=3><B>$i</B></FONT>";
		else
			echo "<A HREF='$urlforum?debut=$i'>$i</A>";
	}
}



echo "<p><div align='center'>";
icone (_T('icone_poster_message'), "forum_envoi.php3?statut=$statutforum&adresse_retour=$urlforum&titre_message=".urlencode(filtrer_entites(_T('texte_nouveau_message'))), "forum-interne-24.gif", "creer.gif");
echo "</div>";


echo "<P align='left'>";

$query_forum="SELECT * FROM spip_forum WHERE statut='$statutforum' AND id_parent=0 ORDER BY date_heure DESC LIMIT $debut,10";
$result_forum=spip_query($query_forum);

afficher_forum($result_forum,$urlforum);

echo "</div>";


fin_page();

?>

