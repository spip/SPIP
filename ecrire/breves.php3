<?php

include ("inc.php3");

debut_page("Br&egrave;ves", "documents", "breves");
debut_gauche();

echo "<P align=left>";
	
debut_droite();


if ($statut) {
	$query="UPDATE spip_breves SET date_heure=NOW(), statut=\"$statut\" WHERE id_breve=$id_breve";
	$result=spip_query($query);
	calculer_rubriques();
}




function enfant($leparent){
 	$query="SELECT * FROM spip_rubriques WHERE id_parent='$leparent' ORDER BY titre";
 	$result=spip_query($query);

 	while($row=spip_fetch_array($result)){
		$id_rubrique=$row['id_rubrique'];
		$id_parent=$row['id_parent'];
		$titre=$row['titre'];
		$descriptif=$row['descriptif'];
		$texte=$row['texte'];

		debut_cadre_enfonce();

		echo "<a href='naviguer.php3?coll=$id_rubrique'>";
		echo "<IMG SRC='img_pack/secteur-24.gif' WIDTH=24 HEIGHT=24 BORDER=0 align='middle'>";
		echo "</a>";
		if (acces_restreint_rubrique($id_rubrique))
			echo " <img src='img_pack/admin-12.gif' alt='' width='12' height='12' title='Vous pouvez administrer cette rubrique et ses sous-rubriques' border='0' align='middle'>";

		echo " <FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>";
		echo "<B>$titre</B></FONT>\n";
		echo aide ("breves");

		echo "<P ALIGN='left'>";

		if ($GLOBALS['connect_statut'] == "0minirezo") $statuts = "'prop', 'refuse', 'publie'";
		else $statuts = "'prop', 'publie'";

		$query = "SELECT id_breve, date_heure, titre, statut FROM spip_breves ".
			"WHERE id_rubrique='$id_rubrique' AND statut IN ($statuts) ORDER BY date_heure DESC";
		afficher_breves('', $query);
		echo "<div align='right'>";
		icone("&Eacute;crire une nouvelle br&egrave;ve", "breves_edit.php3?new=oui&id_rubrique=$id_rubrique", "breve-24.gif", "creer.gif");
		echo "</div>";

		fin_cadre_enfonce();

	}
}



enfant(0);


fin_page();

?>

