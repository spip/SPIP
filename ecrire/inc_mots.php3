<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_MOTS")) return;
define("_ECRIRE_INC_MOTS", "1");


$GLOBALS['flag_mots_ressemblants'] = $GLOBALS['flag_levenshtein'];


function mots_ressemblants($mot, $table_mots, $table_ids='') {
	$lim = 2;
	$nb = 0;
	$opt = 1000000;
	$mot_opt = '';
	$mot = strtolower(trim($mot));
	$len = strlen($mot);

	if (!$table_mots) return '';

	while (!$nb AND $lim < 10) {
		reset($table_mots);
		if ($table_ids) reset($table_ids);
		while (list(, $val) = each($table_mots)) {
			if ($table_ids) list(, $id) = each($table_ids);
			else $id = $val;
			$val2 = trim($val);
			if ($val2) {
				if (!($m = $distance[$id])) {
					$val2 = strtolower($val2);
					$len2 = strlen($val2);
					if (substr($val2, 0, $len) == $mot) $m = -1;
					else if ($len2 > $len) $m = levenshtein($val2, $mot) + $len - $len2;
					else $m = levenshtein($val2, $mot);
					$distance[$id] = $m;
				}
				if ($m <= $lim) {
					$selection[$id] = $m;
					if ($m < $opt) {
						$opt = $m;
						$mot_opt = $val;
					}
					$nb++;
				}
			}
		}
		$lim += 2;
	}

	if (!$nb) return '';
	reset($selection);
	if ($opt != -1) {
		$moy = 1;
		while(list(, $val) = each($selection)) $moy *= $val;
		if($moy) $moy = pow($moy, 1.0/$nb);
		$lim = ($opt + $moy) / 2;
	}
	else $lim = -1;

	reset($selection);
	while (list($key, $val) = each($selection)) {
		if ($val <= $lim) {
			$result[] = $key;
		}
	}
	return $result;
}


/*
 * Affiche la liste des mots-cles associes a l'objet
 * specifie, plus le formulaire d'ajout de mot-cle
 */

function formulaire_mots($table, $id_objet, $nouv_mot, $supp_mot, $cherche_mot, $flag_editable) {
	global $flag_mots_ressemblants;
	global $connect_statut;
	global $spip_lang_rtl;
	$select_groupe = $GLOBALS['select_groupe'];

	if ($table == 'articles') {
		$id_table = 'id_article';
		$url_base = "articles.php3?id_article=$id_objet";
	}
	else if ($table == 'breves') {
		$id_table = 'id_breve';
		$url_base = "breves_voir.php3?id_breve=$id_objet";
	}
	else if ($table == 'rubriques') {
		$id_table = 'id_rubrique';
		$url_base = "naviguer.php3?coll=$id_objet";
	}

	else if ($table == 'syndic') {
		$id_table = 'id_syndic';
		$url_base = "sites.php3?id_syndic=$id_objet";
	}

	$query = "SELECT mots.* FROM spip_mots AS mots, spip_mots_$table AS lien WHERE lien.$id_table=$id_objet AND mots.id_mot=lien.id_mot";
	$nombre_mots = spip_num_rows(spip_query($query));

	$query_groupes = "SELECT * FROM spip_groupes_mots WHERE $table = 'oui' AND $connect_statut = 'oui'";
	$nombre_groupes = spip_num_rows(spip_query($query_groupes));

	if (!$nombre_mots AND (!$nombre_groupes OR !$flag_editable)) return;

	echo "<a name='mots'></a>";
	debut_cadre_enfonce("mot-cle-24.gif");

	echo "<TABLE BORDER=0 CELLSPACING=0 CELLPADDING=3 WIDTH=100% BACKGROUND=''><TR><TD BGCOLOR='#EEEECC' class='serif2'>";
	if ($flag_editable){
		if ($nouv_mot.$cherche_mot.$supp_mot)
			echo bouton_block_visible("lesmots");
		else
			echo bouton_block_invisible("lesmots");
	}
	echo "<B>"._T('titre_mots_cles')."</B>";
	echo aide ("artmots");
	echo "</td></tr></TABLE>";

	//////////////////////////////////////////////////////
	// Recherche de mot-cle
	//

	if ($nouv_mot)
		$nouveaux_mots[] = $nouv_mot;

	$tous_les_mots = split(" *[,;] *", $cherche_mot);
	while ((list(,$cherche_mot) = each ($tous_les_mots)) AND $cherche_mot) {
		echo "<P ALIGN='left'>";
		$query = "SELECT id_mot, titre FROM spip_mots WHERE id_groupe='$select_groupe'";
		$result = spip_query($query);
		unset($table_mots);
		unset($table_ids);
		while ($row = spip_fetch_array($result)) {
			$table_ids[] = $row['id_mot'];
			$table_mots[] = $row['titre'];
		}
		$resultat = mots_ressemblants($cherche_mot, $table_mots, $table_ids);
		debut_boite_info();
		if (!$resultat) {
			echo "<B>"._T('info_non_resultat', array('cherche_mot' => $cherche_mot))."</B><BR>";
		}
		else if (count($resultat) == 1) {
			list(, $nouv_mot) = each($resultat);
			$nouveaux_mots[] = $nouv_mot;
			echo "<B>"._T('info_mot_cle_ajoute')." ";
			if ($table == 'articles') echo _T('info_l_article');
			else if ($table == 'breves') echo _T('info_la_breve');
			else if ($table == 'rubriques') echo _T('info_la_rubrique');
			echo " : </B><BR>";
			$query = "SELECT * FROM spip_mots WHERE id_mot=$nouv_mot";
			$result = spip_query($query);
			echo "<UL>";
			while ($row = spip_fetch_array($result)) {
				$id_mot = $row['id_mot'];
				$titre_mot = $row['titre'];
				$type_mot = $row['type'];
				$descriptif_mot = $row['descriptif'];

				echo "<LI><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=2><B><FONT SIZE=3>$titre_mot</FONT></B>";
				echo "</FONT>\n";
			}
			echo "</UL>";
		}
		else if (count($resultat) < 16) {
			reset($resultat);
			unset($les_mots);
			while (list(, $id_mot) = each($resultat)) $les_mots[] = $id_mot;
			if ($les_mots) {
				$les_mots = join(',', $les_mots);
				echo "<B>"._T('info_plusieurs_mots_trouves', array('cherche_mot' => $cherche_mot))."</B><BR>";
				$query = "SELECT * FROM spip_mots WHERE id_mot IN ($les_mots) ORDER BY titre";
				$result = spip_query($query);
				echo "<UL>";
				while ($row = spip_fetch_array($result)) {
					$id_mot = $row['id_mot'];
					$titre_mot = $row['titre'];
					$type_mot = $row['type'];
					$descriptif_mot = $row['descriptif'];

					echo "<LI><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=2><B><FONT SIZE=3>$titre_mot</FONT></B>";

					if ($type_mot) echo " ($type_mot)";
					echo " | <A HREF=\"$url_base&nouv_mot=$id_mot#mots\">"._T('info_ajouter_mot')."</A>";

					if (strlen($descriptif_mot) > 1) {
						echo "<BR><FONT SIZE=1>".propre(couper($descriptif_mot, 100))."</FONT>\n";
					}
					echo "</FONT><p>\n";
				}
				echo "</UL>";
			}
		}
		else {
			echo "<B>"._T('info_trop_resultat', array('cherche_mot' => $cherche_mot))."<BR>";
		}
		fin_boite_info();
		echo "<P>";

	}


	//////////////////////////////////////////////////////
	// Appliquer les modifications sur les mots-cles
	//

	if ($nouveaux_mots && $flag_editable) {
		while ((list(,$nouv_mot) = each($nouveaux_mots)) AND $nouv_mot!='x') {
			$query = "SELECT * FROM spip_mots_$table WHERE id_mot=$nouv_mot AND $id_table=$id_objet";
			$result = spip_query($query);
			if (!spip_num_rows($result)) {
				$query = "INSERT INTO spip_mots_$table (id_mot,$id_table) VALUES ($nouv_mot, $id_objet)";
				$result = spip_query($query);
			}
		}
	}

	if ($supp_mot && $flag_editable) {
		if ($supp_mot == -1)
			$mots_supp = "";
		else
			$mots_supp = " AND id_mot=$supp_mot";
		$query = "DELETE FROM spip_mots_$table WHERE $id_table=$id_objet $mots_supp";
		$result = spip_query($query);
	}


	//
	// Afficher les mots-cles
	//

	$query = "SELECT DISTINCT type FROM spip_mots";
	$result = spip_query($query);
	$plusieurs_types = (spip_num_rows($result) > 1);

	unset($les_mots);

	$query = "SELECT mots.* FROM spip_mots AS mots, spip_mots_$table AS lien WHERE lien.$id_table=$id_objet AND mots.id_mot=lien.id_mot ORDER BY mots.type, mots.titre";
	$result = spip_query($query);

	echo "<table border=0 cellspacing=0 cellpadding=2 width=100% background=''>";

	$ifond=0;

	while ($row = spip_fetch_array($result)) {
		$id_mot = $row['id_mot'];
		$titre_mot = $row['titre'];
		$type_mot = $row['type'];
		$descriptif_mot = $row['descriptif'];
		$id_groupe = $row['id_groupe'];

		$query_groupe = "SELECT * FROM spip_groupes_mots WHERE id_groupe = $id_groupe";
		$result_groupe = spip_query($query_groupe);
		while($row_groupe = spip_fetch_array($result_groupe)) {
			$id_groupe = $row_groupe['id_groupe'];
			$titre_groupe = entites_html($row_groupe['titre']);
			$unseul = $row_groupe['unseul'];
			$obligatoire = $row_groupe['obligatoire'];
			$acces_admin =  $row_groupe['0minirezo'];
			$acces_redacteur = $row_groupe['1comite'];

			$flag_groupe = ($flag_editable AND (($connect_statut == '1comite' AND $acces_redacteur == 'oui') OR ($connect_statut == '0minirezo' AND $acces_admin == 'oui')));
		}

		$groupes_vus[$id_groupe] = true;
		$id_groupes_vus[] = $id_groupe;

		if ($ifond==0){
			$ifond=1;
			$couleur="#FFFFFF";
		}else{
			$ifond=0;
			$couleur="#EDF3FE";
		}

		$url = "mots_edit.php3?id_mot=$id_mot&redirect=".rawurlencode($url_base.'#mots');

		echo "<TR WIDTH=\"100%\">";
		echo "<TD BGCOLOR='$couleur'>";
		echo "<A HREF='$url'><img src='img_pack/petite-cle.gif' alt='' width='23' height='12' border='0'></A>";
		echo "</TD>";
		echo "<TD BGCOLOR='$couleur' width='100%' CLASS='arial2'>";


		// Changer
		if ($unseul == "oui" AND $flag_groupe) {
			echo "<form action='$url_base#mots' method='post' style='margin:0px; padding: 0px'>";
			echo "<INPUT TYPE='Hidden' NAME='$id_table' VALUE='$id_objet'>";
			if ($table == 'rubriques') echo "<INPUT TYPE='Hidden' NAME='coll' VALUE='$id_objet'>";
			echo "<select name='nouv_mot' CLASS='fondl' STYLE='font-size:10px; width:90px;'>";

			$query_autres_mots = "SELECT * FROM spip_mots WHERE id_groupe = $id_groupe";
			$result_autres_mots = spip_query($query_autres_mots);
			while ($row_autres = spip_fetch_array($result_autres_mots)) {
				$le_mot = $row_autres['id_mot'];
				$le_titre_mot = supprimer_tags($row_autres['titre']);

				if ($le_mot == $id_mot) $selected = "SELECTED";
				else $selected = "";
				echo "<option value='$le_mot' $selected> $le_titre_mot";
			}
			echo "</select>";
			echo "<INPUT TYPE='Hidden' NAME='supp_mot' VALUE='$id_mot'>";
			echo " &nbsp; <INPUT TYPE='submit' NAME='Choisir' VALUE='"._T('bouton_changer')."' CLASS='fondo' style='font-size: 10px';>";
			echo "</form>";

		} else {
			echo "<A HREF='$url'>$titre_mot</A>";
		}
		echo "</TD>";

		echo "<TD ALIGN='right' BGCOLOR='$couleur' ALIGN='right' CLASS='arial2'>";
		echo "$type_mot";
		echo "</TD>";

		if ($flag_editable){
			echo "<TD BGCOLOR='$couleur' ALIGN='right' CLASS='arial1'>";
			if ($flag_groupe)
				echo "<A HREF=\"$url_base&supp_mot=$id_mot#mots\">"._T('info_retirer_mot')."&nbsp;<img src='img_pack/croix-rouge.gif' alt='X' width='7' height='7' border='0' align='middle'></A>";
			else echo "&nbsp;";
			echo "</TD>";
		}
		echo "</TR>\n";

		$les_mots[] = $id_mot;
	}
	echo "<tr><td></td><td></td><td><img src='img_pack/rien.gif' width=100 height=1></td><td><img src='img_pack/rien.gif' width=90 height=1></td></tr>";
	echo "</TABLE>";

	if ($les_mots) {
		$nombre_mots_associes = count($les_mots);
		$les_mots = join($les_mots, ",");
	}
	if ($id_groupes_vus) $id_groupes_vus = join($id_groupes_vus, ",");
	else $id_groupes_vus = "0";

	$query_groupes = "SELECT * FROM spip_groupes_mots WHERE $table = 'oui' AND $connect_statut = 'oui' AND obligatoire = 'oui' AND id_groupe NOT IN ($id_groupes_vus)";
	$nb_groupes = spip_num_rows(spip_query($query_groupes));

	//
	// Afficher le formulaire d'ajout de mots-cles
	//

	if ($flag_editable) {
		if ($nouveaux_mots.$cherche_mot.$supp_mot)
			echo debut_block_visible("lesmots");
		else if ($nb_groupes > 0) {
			echo debut_block_visible("lesmots");
			// vilain hack pour redresser un triangle
			$couche_a_redresser = $GLOBALS['numero_block']['lesmots'];
			if (test_layer()) echo "<script type='text/javascript'><!--
			triangle = MM_findObj('triangle' + $couche_a_redresser);
			if (triangle) triangle.src = 'img_pack/deplierbas$spip_lang_rtl.gif';
			//--></script>";
		}
		else
			echo debut_block_invisible("lesmots");

		if ($nombre_mots_associes > 3) {
			echo "<div align='right' class='arial1'>";
			echo "<a href=\"$url_base&supp_mot=-1#mots\">"._T('info_retirer_mots')."</a>";
			echo "</div><br />\n";
		}

		$form_mot = "<FORM ACTION='$url_base#mots' METHOD='post' STYLE='margin:1px;'>"
			."<INPUT TYPE='Hidden' NAME='$id_table' VALUE='$id_objet'>";

		if ($table == 'rubriques') $form_mot .= "<INPUT TYPE='Hidden' NAME='coll' VALUE='$id_objet'>";

		$message_ajouter_mot = "<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=2><B>"._T('titre_ajouter_mot_cle')."</B></FONT> &nbsp;\n";
		echo "<table border='0' align='right' style='white-space: nowrap'>";

		$query_groupes = "SELECT * FROM spip_groupes_mots WHERE $table = 'oui' AND $connect_statut = 'oui' AND (unseul != 'oui'  OR (unseul = 'oui' AND id_groupe NOT IN ($id_groupes_vus))) ORDER BY titre";
		$result_groupes = spip_query($query_groupes);

		// Afficher un menu par groupe de mots

		while ($row_groupes = spip_fetch_array($result_groupes)) {
			$id_groupe = $row_groupes['id_groupe'];
			$titre_groupe = entites_html($row_groupes['titre']);
			$unseul = $row_groupes['unseul'];
			$obligatoire = $row_groupes['obligatoire'];
			$articles = $row_groupes['articles'];
			$breves = $row_groupes['breves'];
			$rubriques = $row_groupes['rubriques'];
			$syndic = $row_groupes['syndic'];
			$acces_minirezo = $row_groupes['0minirezo'];
			$acces_comite = $row_groupes['1comite'];
			$acces_forum = $row_groupes['6forum'];

			$query = "SELECT * FROM spip_mots WHERE id_groupe = '$id_groupe' ";
			if ($les_mots) $query .= "AND id_mot NOT IN ($les_mots) ";
			$query .= "ORDER BY type, titre";
			$result = spip_query($query);
			if (spip_num_rows($result) > 0) {
				if ((spip_num_rows($result) > 50 AND $flag_mots_ressemblants)) {
					echo "\n<tr>";
					echo $form_mot;
					echo "\n<td>";
					echo $message_ajouter_mot;
					$message_ajouter_mot = "";
					echo "</td>\n<td>";

					if ($obligatoire == "oui" AND !$groupes_vus[$id_groupe])
						echo "<INPUT TYPE='text' NAME='cherche_mot' CLASS='fondl' STYLE='width: 180px; background-color:#E86519;' VALUE=\"$titre_groupe\" SIZE='20'>";
					else if ($unseul == "oui")
						echo "<INPUT TYPE='text' NAME='cherche_mot' CLASS='fondl' STYLE='width: 180px; background-color:#cccccc;' VALUE=\"$titre_groupe\" SIZE='20'>";
					else
						echo "<INPUT TYPE='text' NAME='cherche_mot' CLASS='fondl' STYLE='width: 180px; ' VALUE=\"$titre_groupe\" SIZE='20'>";

					echo "</td>\n<td>";
					echo "<INPUT TYPE='hidden' NAME='select_groupe'  VALUE='$id_groupe'>";

					echo " <INPUT TYPE='submit' NAME='Chercher' VALUE='"._T('bouton_chercher')."' CLASS='fondo' STYLE='font-size:10px'>";
					echo "</td></FORM>";
					echo "</tr>";
				}
				else {
					echo "\n<tr>";
					echo $form_mot;
					echo "\n<td>";
					echo $message_ajouter_mot;
					$message_ajouter_mot = "";
					echo "</td>\n<td>";

					if ($obligatoire == "oui" AND !$groupes_vus[$id_groupe])
						echo "<SELECT NAME='nouv_mot' SIZE='1' STYLE='width: 180px; background-color:#E86519;' CLASS='fondl'>";
					else if ($unseul == "oui")
						echo "<SELECT NAME='nouv_mot' SIZE='1' STYLE='width: 180px; background-color:#cccccc;' CLASS='fondl'>";
					else
						echo "<SELECT NAME='nouv_mot' SIZE='1' STYLE='width: 180px; ' CLASS='fondl'>";

					$ifond == 0;
					echo "<OPTION VALUE='x' style='font-variant: small-caps;'>$titre_groupe";
					while($row = spip_fetch_array($result)) {
						$id_mot = $row['id_mot'];
						$titre_mot = $row['titre'];
						$texte_option = entites_html($titre_mot);
						echo "\n<OPTION VALUE=\"$id_mot\">";
						echo "&nbsp;&nbsp;&nbsp;";
						echo $texte_option;
					}
					echo "</SELECT>";
					echo "</td>\n<td>";
					echo " &nbsp; <INPUT TYPE='submit' NAME='Choisir' VALUE='"._T('bouton_choisir')."' CLASS='fondo'>";
					echo "</td></FORM>";
					echo "</tr>";
				}
			}
		}
		echo "</table>";
		echo fin_block();
	}


	fin_cadre_enfonce();
}


?>
