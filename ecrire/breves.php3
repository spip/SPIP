<?php

include ("inc.php3");

if ($statut AND $connect_statut == "0minirezo") {
	$query="UPDATE spip_breves SET date_heure=NOW(), statut=\"$statut\" WHERE id_breve=$id_breve";
	$result=spip_query($query);
	calculer_rubriques();
	
	@header("Location:breves.php3");
}



debut_page(_T('titre_page_breves'), "documents", "breves");
debut_gauche();

echo "<P align=left>";
	
debut_droite();



function enfant($leparent){
	global $spip_lang_left, $spip_lang_right;

 	$query="SELECT * FROM spip_rubriques WHERE id_parent='$leparent' ORDER BY titre";
 	$result=spip_query($query);

 	while($row=spip_fetch_array($result)){
		$id_rubrique=$row['id_rubrique'];
		$id_parent=$row['id_parent'];
		$titre=typo($row['titre']);
		$descriptif=$row['descriptif'];
		$texte=$row['texte'];

		debut_cadre_enfonce("secteur-24.gif", false, '', $titre.aide ("breves"));

		if ($GLOBALS['connect_statut'] == "0minirezo") $statuts = "'prop', 'refuse', 'publie'";
		else $statuts = "'prop', 'publie'";

		$query = "SELECT id_breve, date_heure, titre, statut FROM spip_breves ".
			"WHERE id_rubrique='$id_rubrique' AND statut IN ($statuts) ORDER BY date_heure DESC";
		afficher_breves('', $query);
		echo "<div align='$spip_lang_right'>";
		icone(_T('icone_nouvelle_breve'), "breves_edit.php3?new=oui&id_rubrique=$id_rubrique", "breve-24.gif", "creer.gif");
		echo "</div>";

		fin_cadre_enfonce();

	}
}



enfant(0);


fin_page();

?>

