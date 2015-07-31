<?php

include ("inc.php3");

if ($conf_mot>0) {
	$query = "SELECT * FROM spip_mots WHERE id_mot='$conf_mot'";
	$result = spip_query($query);
	if ($row = spip_fetch_array($result)) {
		$id_mot = $row['id_mot'];
		$titre_mot = typo($row['titre']);
		$type_mot = typo($row['type']);
		$descriptif_mot = $row['descriptif'];

		if ($connect_statut=="0minirezo") $aff_articles="prepa,prop,publie,refuse";
		else $aff_articles="prop,publie";

		$nb_articles = spip_fetch_row(spip_query(
			"SELECT COUNT(*) FROM spip_mots_articles AS lien, spip_articles AS article
			WHERE lien.id_mot=$conf_mot AND article.id_article=lien.id_article
			AND FIND_IN_SET(article.statut,'$aff_articles')>0 AND article.statut!='refuse'"
			));
		$nb_articles = $nb_articles[0];
		$nb_rubriques = spip_fetch_row(spip_query(
			"SELECT COUNT(*) FROM spip_mots_rubriques AS lien, spip_rubriques AS rubrique
			WHERE lien.id_mot=$conf_mot AND rubrique.id_rubrique=lien.id_rubrique"
			));
		$nb_rubriques = $nb_rubriques[0];
		$nb_breves = spip_fetch_row(spip_query(
			"SELECT COUNT(*) FROM spip_mots_breves AS lien, spip_breves AS breve
			WHERE lien.id_mot=$conf_mot AND breve.id_breve=lien.id_breve
			AND FIND_IN_SET(breve.statut,'$aff_articles')>0 AND breve.statut!='refuse'"));
		$nb_breves = $nb_breves[0];
		$nb_sites = spip_fetch_row(spip_query(
			"SELECT COUNT(*) FROM spip_mots_syndic AS lien, spip_syndic AS syndic
			WHERE lien.id_mot=$conf_mot AND syndic.id_syndic=lien.id_syndic
			AND FIND_IN_SET(syndic.statut,'$aff_articles')>0 AND syndic.statut!='refuse'"));
		$nb_sites = $nb_sites[0];

		if ($nb_articles + $nb_breves + $nb_sites == 0) {
			header("Location: mots_edit.php3?supp_mot=$id_mot&redirect_ok=oui&redirect=mots_tous.php3");
			exit();
		} // else traite plus loin
	}
}


if ($connect_statut == '0minirezo') {
	if ($modifier_groupe == "oui") {
		$change_type = addslashes(corriger_caracteres($change_type));
		$ancien_type = addslashes(corriger_caracteres($ancien_type));

		if ($ancien_type) {	// modif groupe
			$query = "UPDATE spip_mots SET type=\"$change_type\" WHERE id_groupe='$id_groupe'";
			spip_query($query);

			$query = "UPDATE spip_groupes_mots SET titre=\"$change_type\", unseul='$unseul', obligatoire='$obligatoire',
				articles='$articles', breves='$breves', rubriques='$rubriques', syndic='$syndic',
				0minirezo='$acces_minirezo', 1comite='$acces_comite', 6forum='$acces_forum'
				WHERE id_groupe='$id_groupe'";
			spip_query($query);
		} else {	// creation groupe
			$query = "INSERT INTO spip_groupes_mots SET titre=\"$change_type\", unseul='$unseul', obligatoire='$obligatoire',
				articles='$articles', breves='$breves', rubriques='$rubriques', syndic='$syndic',
				0minirezo='$acces_minirezo', 1comite='$acces_comite', 6forum='$acces_forum'";
			spip_query($query);
		}
	}

	if ($supp_group){
		$type=addslashes($supp_group);
		$query="DELETE FROM spip_groupes_mots WHERE id_groupe='$supp_group'";
		$result=spip_query($query);
	}
}


debut_page(_T('titre_page_mots_tous'), "documents", "mots");
debut_gauche();

debut_droite();

gros_titre(_T('titre_mots_tous'));
echo typo(_T('info_creation_mots_cles')) . aide ("mots") . "<br><br>";

/////

if ($conf_mot>0) {
	if ($nb_articles == 1) {
		$texte_lie = _T('info_un_article')." ";
	} else if ($nb_articles > 1) {
		$texte_lie = _T('info_nombre_articles', array('nb_articles' => $nb_articles)) ." ";
	}
	if ($nb_breves == 1) {
		$texte_lie .= _T('info_une_breve')." ";
	} else if ($nb_breves > 1) {
		$texte_lie .= _T('info_nombre_breves', array('nb_breves' => $nb_breves))." ";
	}
	if ($nb_sites == 1) {
		$texte_lie .= _T('info_un_site')." ";
	} else if ($nb_sites > 1) {
		$texte_lie .= _T('info_nombre_sites', array('nb_sites' => $nb_sites))." ";
	}
	if ($nb_rubriques == 1) {
		$texte_lie .= _T('info_une_rubrique')." ";
	} else if ($nb_rubriques > 1) {
		$texte_lie .= _T('info_nombre_rubriques', array('nb_rubriques' => $nb_rubriques))." ";
	}

	debut_boite_info();
	echo "<FONT FACE='Georgia,Garamond,Times,serif' SIZE=3>";
	echo _T('info_delet_mots_cles', array('titre_mot' => $titre_mot, 'type_mot' => $type_mot, 'texte_lie' => $texte_lie));

	echo "<UL>";
	echo "<LI><B><A HREF='mots_edit.php3?supp_mot=$id_mot&redirect_ok=oui&redirect=mots_tous.php3'>"._T('item_oui')."</A>,</B> "._T('info_oui_suppression_mot_cle');
	echo "<LI><B><A HREF='mots_tous.php3'>"._T('item_non')."</A>,</B> "._T('info_non_suppression_mot_cle');
	echo "</UL>";
	echo "</FONT>";
	fin_boite_info();
	echo "<P>";
}


//
// Calculer les nombres d'elements (articles, etc.) lies a chaque mot
//

if ($connect_statut=="0minirezo") $aff_articles = "'prepa','prop','publie'";
else $aff_articles = "'prop','publie'";

$result_articles = spip_query(
	"SELECT COUNT(*) as cnt, lien.id_mot FROM spip_mots_articles AS lien, spip_articles AS article
	WHERE article.id_article=lien.id_article AND article.statut IN ($aff_articles) GROUP BY lien.id_mot"
);
while ($row_articles =  spip_fetch_array($result_articles)){
	$id_mot = $row_articles['id_mot'];
	$total_articles = $row_articles['cnt'];
	$nb_articles[$id_mot] = $total_articles;
}


$result_rubriques = spip_query(
	"SELECT COUNT(*) AS cnt, lien.id_mot FROM spip_mots_rubriques AS lien, spip_rubriques AS rubrique
	WHERE rubrique.id_rubrique=lien.id_rubrique GROUP BY lien.id_mot"
	);
while ($row_rubriques = spip_fetch_array($result_rubriques)){
	$id_mot = $row_rubriques['id_mot'];
	$total_rubriques = $row_rubriques['cnt'];
	$nb_rubriques[$id_mot] = $total_rubriques;
}

$result_breves = spip_query(
	"SELECT COUNT(*) AS cnt, lien.id_mot FROM spip_mots_breves AS lien, spip_breves AS breve
	WHERE breve.id_breve=lien.id_breve AND breve.statut IN ($aff_articles) GROUP BY lien.id_mot"
	);
while ($row_breves = spip_fetch_array($result_breves)){
	$id_mot = $row_breves['id_mot'];
	$total_breves = $row_breves['cnt'];
	$nb_breves[$id_mot] = $total_breves;
}

$result_syndic = spip_query(
	"SELECT COUNT(*) AS cnt, lien.id_mot FROM spip_mots_syndic AS lien, spip_syndic AS syndic
	WHERE syndic.id_syndic=lien.id_syndic AND syndic.statut IN ($aff_articles) GROUP BY lien.id_mot"
	);
while ($row_syndic = spip_fetch_array($result_syndic)){
	$id_mot = $row_syndic['id_mot'];
	$total_sites = $row_syndic['cnt'];
	$nb_sites[$id_mot] = $total_sites;
}


//
// On boucle d'abord sur les groupes de mots
//

$query_groupes = "SELECT * FROM spip_groupes_mots ORDER BY titre";
$result_groupes = spip_query($query_groupes);

while ($row_groupes = spip_fetch_array($result_groupes)) {
	$id_groupe = $row_groupes['id_groupe'];
	$titre_groupe = $row_groupes['titre'];
	$unseul = $row_groupes['unseul'];
	$obligatoire = $row_groupes['obligatoire'];
	$articles = $row_groupes['articles'];
	$breves = $row_groupes['breves'];
	$rubriques = $row_groupes['rubriques'];
	$syndic = $row_groupes['syndic'];
	$acces_minirezo = $row_groupes['0minirezo'];
	$acces_comite = $row_groupes['1comite'];
	$acces_forum = $row_groupes['6forum'];

	// Afficher le titre du groupe
	debut_cadre_enfonce("groupe-mot-24.gif");
	echo "<p><table border=0 cellspacing=0 cellpadding=3 width=\"100%\">";
	echo "<tr><td bgcolor='$couleur_foncee' colspan=2><font face='Verdana,Arial,Helvetica,sans-serif' size=3 color='#ffffff'><b>$titre_groupe</b>";
	echo "</font></td>";
	echo "<td bgcolor='$couleur_foncee' align='right'><font face='Verdana,Arial,Helvetica,sans-serif' size=1>";
	echo "</font></td></tr>";

	// Affichage des options du groupe (types d'éléments, permissions...)
	echo "<tr><td colspan=3><font face='Verdana,Arial,Helvetica,sans-serif' size=1>";
	if ($articles == "oui") echo "> "._T('info_articles_2')." &nbsp;&nbsp;";
	if ($breves == "oui") echo "> "._T('info_breves_02')." &nbsp;&nbsp;";
	if ($rubriques == "oui") echo "> "._T('info_rubriques')." &nbsp;&nbsp;";
	if ($syndic == "oui") echo "> "._T('icone_sites_references')." &nbsp;&nbsp;";

	if ($unseul == "oui" OR $obligatoire == "oui") echo "<br>";
	if ($unseul == "oui") echo "> "._T('info_un_mot')." &nbsp;&nbsp;";
	if ($obligatoire == "oui") echo "> "._T('info_groupe_important')." &nbsp;&nbsp;";

	echo "<br>";
	if ($acces_minirezo == "oui") echo "> "._T('info_administrateurs')." &nbsp;&nbsp;";
	if ($acces_comite == "oui") echo "> "._T('info_redacteurs')." &nbsp;&nbsp;";
	if ($acces_forum == "oui") echo "> "._T('info_visiteurs_02')." &nbsp;&nbsp;";

	echo "</font></td></tr></table>";

	//
	// Afficher les mots-cles du groupe
	//
	$query = "SELECT * FROM spip_mots WHERE id_groupe = '$id_groupe' ORDER BY titre";
	$result = spip_query($query);

	if (spip_num_rows($result) > 0) {
		debut_cadre_relief("mot-cle-24.gif");
		echo "<table border=0 cellspacing=0 cellpadding=3 width=\"100%\">";
		while ($row = spip_fetch_array($result)) {
			$id_mot = $row['id_mot'];
			$titre_mot = $row['titre'];
			$type_mot = $row['type'];
			$descriptif_mot = $row['descriptif'];

			if ($connect_statut == "0minirezo")
				$aff_articles="prepa,prop,publie,refuse";
			else
				$aff_articles="prop,publie";

			if ($id_mot!=$conf_mot) {
				$couleur = $ifond ? "#FFFFFF" : $couleur_claire;
				$ifond = $ifond ^ 1;

				echo "<TR BGCOLOR='$couleur'>";
				echo "<TD>";
				if ($connect_statut == "0minirezo" OR $nb_articles[$id_mot] > 0)
					echo "<A HREF='mots_edit.php3?id_mot=$id_mot&redirect=mots_tous.php3'><img src='img_pack/petite-cle.gif' alt='' width='23' height='12' border='0'></A>";
				else
					echo "<img src='img_pack/petite-cle.gif' alt='' width='23' height='12' border='0'>";
				echo "</TD>";
				echo "<TD>";
				echo "<FONT FACE='Georgia,Garamond,Times,serif' SIZE=3>";
				if ($connect_statut == "0minirezo" OR $nb_articles[$id_mot] > 0)
					echo "<A HREF='mots_edit.php3?id_mot=$id_mot&redirect=mots_tous.php3'>$titre_mot</A>";
				else
					echo "$titre_mot";
				echo "</FONT></TD>";
				echo "<TD ALIGN='right'>";
				echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2>";

				$texte_lie = array();

				if ($nb_articles[$id_mot] == 1)
					$texte_lie[] = _T('info_1_article');
				else if ($nb_articles[$id_mot] > 1)
					$texte_lie[] = $nb_articles[$id_mot]." "._T('info_articles_02');

				if ($nb_breves[$id_mot] == 1)
					$texte_lie[] = _T('info_1_breve');
				else if ($nb_breves[$id_mot] > 1)
					$texte_lie[] = $nb_breves[$id_mot]." "._T('info_breves_03');

				if ($nb_sites[$id_mot] == 1)
					$texte_lie[] = _T('info_1_site');
				else if ($nb_sites[$id_mot] > 1)
					$texte_lie[] = $nb_sites[$id_mot]." "._T('info_sites');

				if ($nb_rubriques[$id_mot] == 1)
					$texte_lie[] = _T('info_une_rubrique_02');
				else if ($nb_rubriques[$id_mot] > 1)
					$texte_lie[] = $nb_rubriques[$id_mot]." "._T('info_rubriques_02');

				echo $texte_lie = join($texte_lie,", ");

				if ($connect_statut=="0minirezo") {
					echo " &nbsp;&nbsp;&nbsp;&nbsp; ";
					echo "<FONT SIZE=1>[<A HREF='mots_tous.php3?conf_mot=$id_mot'>"._T('info_supprimer_mot')."</A>]</FONT>";
				} else
					echo "&nbsp;";

				echo "</FONT>";
				echo "</TD>";
				echo "</TR>\n";
			}
		}

		echo "</table>";
		fin_cadre_relief();
	
		$supprimer_groupe = false;
	} 
	else
		if ($connect_statut =="0minirezo")
			$supprimer_groupe = true;

	if ($connect_statut =="0minirezo" AND !$conf_mot){
		echo "\n<table cellpadding=0 cellspacing=0 border=0 width=100%>";
		echo "<tr>";
		echo "<td>";
		icone(_T('icone_modif_groupe_mots'), "mots_type.php3?id_groupe=$id_groupe", "groupe-mot-24.gif", "edit.gif");
		echo "</td>";
		if ($supprimer_groupe) {
			echo "<td>";
			icone(_T('icone_supprimer_groupe_mots'), "mots_tous.php3?supp_group=$id_groupe", "groupe-mot-24.gif", "supprimer.gif");
			echo "</td>";
			echo "<td> &nbsp; </td>"; // Histoire de forcer "supprimer" un peu plus vers la gauche
		}
		echo "<td align='right'>";
		icone(_T('icone_creation_mots_cles'), "mots_edit.php3?new=oui&redirect=mots_tous.php3&id_groupe=$id_groupe", "mot-cle-24.gif", "creer.gif");
		echo "</td></tr></table>";
	}	

	fin_cadre_enfonce();
}

if ($connect_statut =="0minirezo" AND !$conf_mot){
	echo "<p>&nbsp;</p><div align='right'>";
	icone(_T('icone_creation_groupe_mots'), "mots_type.php3?new=oui", "groupe-mot-24.gif", "creer.gif");
	echo "</div>";
}

fin_page();

?>
