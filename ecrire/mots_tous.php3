<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


include ("inc.php3");

// suppression d'un mot ?
if ($conf_mot = intval($conf_mot)) {
	$query = "SELECT * FROM spip_mots WHERE id_mot='$conf_mot'";
	$result = spip_query($query);
	if ($row = spip_fetch_array($result)) {
		$id_mot = $row['id_mot'];
		$titre_mot = typo($row['titre']);
		$type_mot = typo($row['type']);

		if ($connect_statut=="0minirezo") $aff_articles="prepa,prop,publie,refuse";
		else $aff_articles="prop,publie";

		list($nb_articles) = spip_fetch_array(spip_query(
			"SELECT COUNT(*) FROM spip_mots_articles AS lien, spip_articles AS article
			WHERE lien.id_mot=$conf_mot AND article.id_article=lien.id_article
			AND FIND_IN_SET(article.statut,'$aff_articles')>0 AND article.statut!='refuse'"
			));
		list($nb_rubriques) = spip_fetch_array(spip_query(
			"SELECT COUNT(*) FROM spip_mots_rubriques AS lien, spip_rubriques AS rubrique
			WHERE lien.id_mot=$conf_mot AND rubrique.id_rubrique=lien.id_rubrique"
			));
		list($nb_breves) = spip_fetch_array(spip_query(
			"SELECT COUNT(*) FROM spip_mots_breves AS lien, spip_breves AS breve
			WHERE lien.id_mot=$conf_mot AND breve.id_breve=lien.id_breve
			AND FIND_IN_SET(breve.statut,'$aff_articles')>0 AND breve.statut!='refuse'"));
		list($nb_sites) = spip_fetch_array(spip_query(
			"SELECT COUNT(*) FROM spip_mots_syndic AS lien, spip_syndic AS syndic
			WHERE lien.id_mot=$conf_mot AND syndic.id_syndic=lien.id_syndic
			AND FIND_IN_SET(syndic.statut,'$aff_articles')>0 AND syndic.statut!='refuse'"));
		list($nb_forum) = spip_fetch_array(spip_query(
			"SELECT COUNT(*) FROM spip_mots_forum AS lien, spip_forum AS forum
			WHERE lien.id_mot=$conf_mot AND forum.id_forum=lien.id_forum
			AND forum.statut='publie'"));

		// si le mot n'est pas lie, on le supprime sans etats d'ames
		if ($nb_articles + $nb_breves + $nb_sites + $nb_forum == 0) {
			redirige_par_entete("mots_edit.php3?supp_mot=$id_mot&redirect_ok=oui&redirect=mots_tous.php3");
		} // else traite plus loin (confirmation de suppression)
	}
}


if ($connect_statut == '0minirezo') {
	if ($modifier_groupe == "oui") {
		$change_type = addslashes(corriger_caracteres($change_type));
		$ancien_type = addslashes(corriger_caracteres($ancien_type));
		$texte = addslashes(corriger_caracteres($texte));
		$descriptif = addslashes(corriger_caracteres($descriptif));

		if ($ancien_type) {	// modif groupe
			$query = "UPDATE spip_mots SET type='$change_type' WHERE id_groupe='$id_groupe'";
			spip_query($query);

			$query = "UPDATE spip_groupes_mots SET titre='$change_type', texte='$texte', descriptif='$descriptif', unseul='$unseul', obligatoire='$obligatoire',
				articles='$articles', breves='$breves', rubriques='$rubriques', syndic='$syndic',
				minirezo='$acces_minirezo', comite='$acces_comite', forum='$acces_forum'
				WHERE id_groupe='$id_groupe'";
			spip_query($query);
		} else {	// creation groupe
			spip_query("INSERT INTO spip_groupes_mots (titre, unseul,  obligatoire, articles, breves, rubriques, syndic, minirezo, comite, forum) VALUES ('$change_type', '$unseul', '$obligatoire', '$articles','$breves', '$rubriques', '$syndic', '$acces_minirezo',  '$acces_comite', '$acces_forum')");
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
	echo "<div class='serif'>";
	echo _T('info_delet_mots_cles', array('titre_mot' => $titre_mot, 'type_mot' => $type_mot, 'texte_lie' => $texte_lie));

	echo "<UL>";
	echo "<LI><B><A HREF='mots_edit.php3?supp_mot=$id_mot&redirect_ok=oui&redirect=mots_tous.php3'>"._T('item_oui')."</A>,</B> "._T('info_oui_suppression_mot_cle');
	echo "<LI><B><A HREF='mots_tous.php3'>"._T('item_non')."</A>,</B> "._T('info_non_suppression_mot_cle');
	echo "</UL>";
	echo "</div>";
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

$query_groupes = "SELECT *, ".creer_objet_multi ("titre", "$spip_lang")." FROM spip_groupes_mots ORDER BY multi";
$result_groupes = spip_query($query_groupes);

while ($row_groupes = spip_fetch_array($result_groupes)) {
	$id_groupe = $row_groupes['id_groupe'];
	$titre_groupe = typo($row_groupes['titre']);
	$descriptif = $row_groupes['descriptif'];
	$texte = $row_groupes['texte'];
	$unseul = $row_groupes['unseul'];
	$obligatoire = $row_groupes['obligatoire'];
	$articles = $row_groupes['articles'];
	$breves = $row_groupes['breves'];
	$rubriques = $row_groupes['rubriques'];
	$syndic = $row_groupes['syndic'];
	$acces_minirezo = $row_groupes['minirezo'];
	$acces_comite = $row_groupes['comite'];
	$acces_forum = $row_groupes['forum'];

	// Afficher le titre du groupe
	debut_cadre_enfonce("groupe-mot-24.gif", false, '', $titre_groupe);
	// Affichage des options du groupe (types d'elements, permissions...)
	echo "<font face='Verdana,Arial,Sans,sans-serif' size=1>";
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

	echo "</font>";
	if ($descriptif) {
		echo "<p><div align='left' border: 1px dashed #aaaaaa;'>";
		echo "<font size=2 face='Verdana,Arial,Sans,sans-serif'>";
		echo "<b>"._T('info_descriptif')."</b> ";
		echo propre($descriptif);
		echo "&nbsp; ";
		echo "</font>";
		echo "</div>";
	}

	if (strlen($texte)>0){
		echo "<FONT FACE='Verdana,Arial,Sans,sans-serif'>";
		echo "<P>".propre($texte);
		echo "</FONT>";
	}

	//
	// Afficher les mots-cles du groupe
	//
	$query = "SELECT id_mot, titre, ".creer_objet_multi ("titre", "$spip_lang")." FROM spip_mots WHERE id_groupe = '$id_groupe' ORDER BY multi";

	$tranches = afficher_tranches_requete($query, 3);

	$table = '';

	if (strlen($tranches)) {
		echo "<div class='liste'>";
		echo "<table border=0 cellspacing=0 cellpadding=3 width=\"100%\">";

		echo $tranches;

		$result = spip_query($query);
		while ($row = spip_fetch_array($result)) {
		
			$vals = '';
			
			$id_mot = $row['id_mot'];
			$titre_mot = $row['titre'];
			
			if ($connect_statut == "0minirezo")
				$aff_articles="prepa,prop,publie,refuse";
			else
				$aff_articles="prop,publie";

			if ($id_mot!=$conf_mot) {
				$couleur = $ifond ? "#FFFFFF" : $couleur_claire;
				$ifond = $ifond ^ 1;

				if ($connect_statut == "0minirezo" OR $nb_articles[$id_mot] > 0)
					$s = "<a href='mots_edit.php3?id_mot=$id_mot&redirect=mots_tous.php3' class='liste-mot'>".typo($titre_mot)."</a>";
				else
					$s = typo($titre_mot);

				$vals[] = $s;

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

				$texte_lie = join($texte_lie,", ");
				
				$vals[] = $texte_lie;


				if ($connect_statut=="0minirezo") {
					$vals[] = "<div style='text-align:right;'><a href='mots_tous.php3?conf_mot=$id_mot'>"._T('info_supprimer_mot')."&nbsp;<img src='" . _DIR_IMG_PACK . "croix-rouge.gif' alt='X' width='7' height='7' border='0' align='bottom' /></a></div>";
				} 

				$table[] = $vals;

				
			}
				
		}
		if ($connect_statut=="0minirezo") {
			$largeurs = array('', 100, 130);
			$styles = array('arial11', 'arial1', 'arial1');
		}
		else {
			$largeurs = array('', 100);
			$styles = array('arial11', 'arial1');
		}
		afficher_liste($largeurs, $table, $styles);

		echo "</table>";
//		fin_cadre_relief();
		echo "</div>";
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
		echo "<td>";
		echo "<div align='$spip_lang_right'>";
		icone(_T('icone_creation_mots_cles'), "mots_edit.php3?new=oui&redirect=mots_tous.php3&id_groupe=$id_groupe", "mot-cle-24.gif", "creer.gif");
		echo "</div>";
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
