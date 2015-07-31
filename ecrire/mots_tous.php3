<?php

include ("inc.php3");


	// C'est pas comme ca qu'on fait proprement...
	$query = "DELETE FROM spip_mots WHERE titre=''";
	$result = spip_query($query);


if ($conf_mot>0) {
	$query = "SELECT * FROM spip_mots WHERE id_mot='$conf_mot'";
	$result = spip_query($query);
	if ($row = mysql_fetch_array($result)) {
		$id_mot = $row['id_mot'];
		$titre_mot = typo($row['titre']);
		$type_mot = typo($row['type']);
		$descriptif_mot = $row['descriptif'];

		if ($connect_statut=="0minirezo") $aff_articles="prepa,prop,publie,refuse";
		else $aff_articles="prop,publie";

		$nb_articles = mysql_fetch_row(spip_query(
			"SELECT COUNT(*) FROM spip_mots_articles AS lien, spip_articles AS article
			WHERE lien.id_mot=$conf_mot AND article.id_article=lien.id_article
			AND FIND_IN_SET(article.statut,'$aff_articles')>0 AND article.statut!='refuse'"
			));
		$nb_articles = $nb_articles[0];
		$nb_rubriques = mysql_fetch_row(spip_query(
			"SELECT COUNT(*) FROM spip_mots_rubriques AS lien, spip_rubriques AS rubrique
			WHERE lien.id_mot=$conf_mot AND rubrique.id_rubrique=lien.id_rubrique"
			));
		$nb_rubriques = $nb_rubriques[0];
		$nb_breves = mysql_fetch_row(spip_query(
			"SELECT COUNT(*) FROM spip_mots_breves AS lien, spip_breves AS breve
			WHERE lien.id_mot=$conf_mot AND breve.id_breve=lien.id_breve
			AND FIND_IN_SET(breve.statut,'$aff_articles')>0 AND breve.statut!='refuse'"));
		$nb_breves = $nb_breves[0];
		$nb_sites = mysql_fetch_row(spip_query(
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


debut_page("Mots-cl&eacute;s", "documents", "mots");
debut_gauche();

echo aide ("mots");

debut_droite();



/////

if ($conf_mot>0) {
	if ($nb_articles == 1) {
		$texte_lie = "un article, ";
	} else if ($nb_articles > 1) {
		$texte_lie = "$nb_articles articles, ";
	} 
	if ($nb_breves == 1) {
		$texte_lie .= "une br&egrave;ve, ";
	} else if ($nb_breves > 1) {
		$texte_lie .= "$nb_breves br&egrave;ves, ";
	} 
	if ($nb_sites == 1) {
		$texte_lie .= "un site, ";
	} else if ($nb_sites > 1) {
		$texte_lie .= "$nb_sites sites, ";
	}
	if ($nb_rubriques == 1) {
		$texte_lie .= "une rubrique, ";
	} else if ($nb_rubriques > 1) {
		$texte_lie .= "$nb_rubriques rubriques, ";
	}

	debut_boite_info();
	echo "<FONT FACE='Georgia,Garamond,Times,serif' SIZE=3>";
	echo "Vous avez	demand&eacute; &agrave; supprimer le mot-cl&eacute;
<B>$titre_mot</B> ($type_mot). Ce mot-cl&eacute; &eacute;tant li&eacute; &agrave;
<b>$texte_lie</b> vous devez confirmer cette d&eacute;cision&nbsp;:";
	
	echo "<UL>";
	echo "<LI> <B><A HREF='mots_edit.php3?supp_mot=$id_mot&redirect_ok=oui&redirect=mots_tous.php3'>Oui</A>,</B> je veux supprimer d&eacute;finitivement ce mot-cl&eacute;.";
	echo "<LI> <B><A HREF='mots_tous.php3'>Non</A>,</B> je ne veux pas supprimer ce mot-cl&eacute;.";
	echo "</UL>";
	echo "</FONT>";
	fin_boite_info();
	echo "<P>";
}



	/// Calculer les nombre d'elements lies a chaque mot
		if ($connect_statut=="0minirezo") $aff_articles="prepa,prop,publie,refuse";
		else $aff_articles="prop,publie";

		$result_articles = spip_query(
			"SELECT COUNT(*) as cnt, mots.id_mot FROM spip_mots_articles AS lien, spip_articles AS article, spip_mots AS mots
			WHERE lien.id_mot=mots.id_mot AND article.id_article=lien.id_article
			AND FIND_IN_SET(article.statut,'$aff_articles')>0 AND article.statut!='refuse' GROUP BY mots.id_mot"
			);
		while ($row_articles =  mysql_fetch_array($result_articles)){
			$id_mot = $row_articles['id_mot'];
			$total_articles = $row_articles['cnt'];
			$nb_articles[$id_mot] = $total_articles;
		}
			

		$result_rubriques = spip_query(
			"SELECT COUNT(*) AS cnt, mots.id_mot FROM spip_mots_rubriques AS lien, spip_rubriques AS rubrique, spip_mots AS mots
			WHERE lien.id_mot=mots.id_mot AND rubrique.id_rubrique=lien.id_rubrique GROUP BY mots.id_mot"
			);
		while ($row_rubriques = mysql_fetch_array($result_rubriques)){
			$id_mot = $row_rubriques['id_mot'];
			$total_rubriques = $row_rubriques['cnt'];
			$nb_rubriques[$id_mot] = $total_rubriques;
		}
			
		$result_breves = spip_query(
			"SELECT COUNT(*) AS cnt, mots.id_mot FROM spip_mots_breves AS lien, spip_breves AS breve, spip_mots AS mots
			WHERE lien.id_mot=mots.id_mot AND breve.id_breve=lien.id_breve
			AND FIND_IN_SET(breve.statut,'$aff_articles')>0 AND breve.statut!='refuse' GROUP BY mots.id_mot"
			);
		while ($row_breves = mysql_fetch_array($result_breves)){
			$id_mot = $row_breves['id_mot'];
			$total_breves = $row_breves['cnt'];
			$nb_breves[$id_mot] = $total_breves;
		}
			
		$result_syndic = spip_query(
			"SELECT COUNT(*) AS cnt, mots.id_mot FROM spip_mots_syndic AS lien, spip_syndic AS syndic, spip_mots AS mots
			WHERE lien.id_mot=mots.id_mot AND syndic.id_syndic=lien.id_syndic
			AND FIND_IN_SET(syndic.statut,'$aff_articles')>0 AND syndic.statut!='refuse' GROUP BY mots.id_mot"
			);
		while ($row_syndic = mysql_fetch_array($result_syndic)){
			$id_mot = $row_syndic['id_mot'];
			$total_sites = $row_syndic['cnt'];
			$nb_sites[$id_mot] = $total_sites;
		}




//////
$query_groupes = "SELECT * FROM spip_groupes_mots ORDER BY titre";
$result_groupes = spip_query($query_groupes);



while($row_groupes = mysql_fetch_array($result_groupes)) {
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

	$ifond=0;
	
	// Afficher le titre du groupe
	debut_cadre_enfonce("groupe-mot-24.gif");
	echo "<p><table border=0 cellspacing=0 cellpadding=3 width=\"100%\">";
	echo "<tr><td bgcolor='$couleur_foncee' colspan=2><font face='Verdana,Arial,Helvetica,sans-serif' size=3 color='#ffffff'><b>$titre_groupe</b>";
	echo "</font></td>";
	echo "<td bgcolor='$couleur_foncee' align='right'><font face='Verdana,Arial,Helvetica,sans-serif' size=1>";
	/*if ($connect_statut == "0minirezo"){
		echo " [<a href=\"mots_type.php3?id_groupe=$id_groupe\">modifier</a>]";
	}else echo "&nbsp;";
	*/
	echo "</font></td></tr>";


	
	echo "<tr><td colspan=3><font face='Verdana,Arial,Helvetica,sans-serif' size=1>";
		if ($articles == "oui") echo "> Articles &nbsp;&nbsp;";
		if ($breves == "oui") echo "> Br&egrave;ves &nbsp;&nbsp;";
		if ($rubriques == "oui") echo "> Rubriques &nbsp;&nbsp;";
		if ($syndic == "oui") echo "> Sites r&eacute;f&eacute;renc&eacute;s &nbsp;&nbsp;";
		
	
	
	if ($unseul == "oui" OR $obligatoire == "oui") echo "<br>";
			if ($unseul == "oui") echo "> Un seul mot &agrave; la fois &nbsp;&nbsp;";
			if ($obligatoire == "oui") echo "> Groupe important &nbsp;&nbsp;";
			


	echo "<br>";
		if ($acces_minirezo == "oui") echo "> Administrateurs &nbsp;&nbsp;";
		if ($acces_comite == "oui") echo "> R&eacute;dacteurs &nbsp;&nbsp;";
		if ($acces_forum == "oui") echo "> Visiteurs du site public &nbsp;&nbsp;";
		
	echo "</font></td></tr></table>";	
	
	
	


	$query = "SELECT * FROM spip_mots WHERE id_groupe = '$id_groupe' ORDER BY titre";
	$result = spip_query($query);

	if (mysql_num_rows($result) > 0) {
		debut_cadre_relief("mot-cle-24.gif");
		echo "<table border=0 cellspacing=0 cellpadding=3 width=\"100%\">";
		// Afficher les mots-cles
		while($row = mysql_fetch_array($result)) {
			$id_mot = $row['id_mot'];
			$titre_mot = $row['titre'];
			$type_mot = $row['type'];
			$descriptif_mot = $row['descriptif'];

			if ($connect_statut=="0minirezo") $aff_articles="prepa,prop,publie,refuse";
			else $aff_articles="prop,publie";

			$query2 = "SELECT COUNT(*) FROM spip_mots_articles AS lien, spip_articles AS article WHERE lien.id_mot=$id_mot AND article.id_article=lien.id_article AND FIND_IN_SET(article.statut,'$aff_articles')>0 AND article.statut!='refuse'";
			$result2 = spip_query($query2);
			list($nombre_mots) = mysql_fetch_array($result2);


			if ($nombre_articles>0) $nombre_articles++;
			
			if($id_mot!=$conf_mot){
				if ($ifond==0){
					$ifond=1;
					$couleur="#FFFFFF";
				}else{
					$ifond=0;
					$couleur="$couleur_claire";
				}				
				echo "<TR BGCOLOR='$couleur'>";
				echo "<TD>";
				if ($connect_statut == "0minirezo" OR $nombre_mots>0){
					echo "<A HREF='mots_edit.php3?id_mot=$id_mot&redirect=mots_tous.php3'><img src='img_pack/petite-cle.gif' alt='X' width='23' height='12' border='0'></A>";
				}else{
					echo "<img src='img_pack/petite-cle.gif' alt='X' width='23' height='12' border='0'>";
				}
				echo "</TD>";
				echo "<TD WIDTH=\"50%\">";
				echo "<FONT FACE='Georgia,Garamond,Times,serif' SIZE=3>";
				if ($connect_statut == "0minirezo" OR $nombre_mots>0){
					echo "<A HREF='mots_edit.php3?id_mot=$id_mot&redirect=mots_tous.php3'>$titre_mot</A>";
				}else{
					echo "$titre_mot";
				}
				echo "</FONT></TD>";
				echo "<TD WIDTH=\"50%\" ALIGN='right'>";
				echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2>";


		
		$texte_lie="";
		if ($nb_articles[$id_mot] == 1) {
			$texte_lie[] = "1 article";
		} else if ($nb_articles[$id_mot] > 1) {
			$texte_lie[] = $nb_articles[$id_mot]." articles";
		} 
		if ($nb_breves[$id_mot] == 1) {
			$texte_lie[] = "1 br&egrave;ve";
		} else if ($nb_breves[$id_mot] > 1) {
			$texte_lie[] = $nb_breves[$id_mot]." br&egrave;ves";
		} 
		if ($nb_sites[$id_mot] == 1) {
			$texte_lie[] = "1 site";
		} else if ($nb_sites[$id_mot] > 1) {
			$texte_lie[] = $nb_sites[$id_mot]." sites";
		}
		if ($nb_rubriques[$id_mot] == 1) {
			$texte_lie[] = "1 rubrique";
		} else if ($nb_rubriques[$id_mot] > 1) {
			$texte_lie[] = $nb_rubriques[$id_mot]." rubriques";
		}
		if (is_array($texte_lie)) $texte_lie = join($texte_lie,", ");


		echo $texte_lie;
				
				/*
				if ($nombre_mots=="1") {
					echo "$nombre_mots article  ";
				}
				else if ($nombre_mots) {
					echo "$nombre_mots articles  ";
				}
				*/

				if ($connect_statut=="0minirezo"){
					echo " &nbsp;&nbsp;&nbsp;&nbsp; ";
					echo "<FONT SIZE=1>[<A HREF='mots_tous.php3?conf_mot=$id_mot'>supprimer&nbsp;ce&nbsp;mot</A>]</FONT>";
				} else echo "&nbsp;";

				//echo "<IMG SRC='img_pack/rien.gif' WIDTH=100 HEIGHT=1 BORDER=0>";
				echo "</FONT>";
				echo "</TD>";
				echo "</TR>\n";
			}
		}

	echo "</table>";
	fin_cadre_relief();
	
	$supprimer_groupe = false;


	} 
	else {
		
		if ($connect_statut =="0minirezo"){
			$supprimer_groupe = true;
			//echo "<A HREF='mots_tous.php3?supp_group=$id_groupe'>Supprimer ce groupe</A>";
		}
	}
	
	
	
	
	
	
	if ($connect_statut =="0minirezo" AND !$conf_mot){
		echo "\n<table cellpadding=0 cellspacing=0 border=0 width=100%>";
		echo "<tr>";
		echo "<td>";
		icone("Modifier ce groupe de mots", "mots_type.php3?id_groupe=$id_groupe", "groupe-mot-24.gif", "edit.gif");
		echo "</td>";
		if ($supprimer_groupe){
			echo "<td>";
			icone("Supprimer ce groupe", "mots_tous.php3?supp_group=$id_groupe", "groupe-mot-24.gif", "supprimer.gif");
			echo "</td>";
			echo "<td> &nbsp; </td>"; // Histoire de forcer "supprimer" un peu plus vers la gauche
		}
		echo "<td align='right'>";
		icone("Cr&eacute;er un nouveau mot-cl&eacute;", "mots_edit.php3?new=oui&redirect=mots_tous.php3&id_groupe=$id_groupe", "mot-cle-24.gif", "creer.gif");
		echo "</td></tr></table>";
	}	
	fin_cadre_enfonce();

}



if ($connect_statut =="0minirezo" AND !$conf_mot){
	echo "<p>&nbsp;</p><div align='right'>";
	icone("Cr&eacute;er un nouveau groupe de mots", "mots_type.php3?new=oui", "groupe-mot-24.gif", "creer.gif");
	echo "</div>";
}



fin_page();

?>
