<?

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

function enfant($leparent){
	global $spip_lang_left, $spip_lang_right;

 	$query="SELECT * FROM spip_rubriques WHERE id_parent='$leparent' ORDER BY 0+titre, titre";
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


function changer_statut_breves($id_breve, $statut)
{
 	$cond = "WHERE id_breve=" . intval($id_breve);
	list($statut_ancien) = spip_fetch_array(spip_query("SELECT statut FROM spip_breves $cond"));
				
	spip_log("$statut != $statut_ancien");
	if ($statut != $statut_ancien) {
		spip_query("UPDATE spip_breves SET date_heure=NOW(), statut='$statut'" . $cond);
		include_ecrire("inc_rubriques.php3");
		calculer_rubriques();
	}
}

?>
