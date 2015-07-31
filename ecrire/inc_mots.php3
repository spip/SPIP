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
	$nombre_mots = mysql_num_rows(spip_query($query));

	$query_groupes = "SELECT * FROM spip_groupes_mots WHERE $table = 'oui' AND $connect_statut = 'oui'";
	$nombre_groupes = mysql_num_rows(spip_query($query_groupes));



	if ($nombre_mots > 0 OR ($nombre_groupes > 0 AND $flag_editable)) {

		debut_cadre_enfonce("mot-cle-24.gif");
	
		echo "<TABLE BORDER=0 CELLSPACING=0 CELLPADDING=3 WIDTH=100% BACKGROUND=''><TR><TD BGCOLOR='#EEEECC'>";
		if ($flag_editable){
			if ($nouv_mot.$cherche_mot.$supp_mot)
				echo bouton_block_visible("lesmots");
			else
				echo bouton_block_invisible("lesmots");
		}
		echo "<FONT SIZE=2 FACE='Georgia,Garamond,Times,serif'><B>MOTS-CL&Eacute;S</B></FONT>";
		echo aide ("artmots");
		echo "</TABLE>";
	
		//////////////////////////////////////////////////////
		// Recherche de mot-cle
		//
	
		if ($cherche_mot) {
			echo "<P ALIGN='left'>";
			$query = "SELECT id_mot, titre FROM spip_mots WHERE id_groupe='$select_groupe'";
			$result = spip_query($query);
			unset($table_mots);
			unset($table_ids);
			while ($row = mysql_fetch_array($result)) {
				$table_mots[] = $row[1];
				$table_ids[] = $row[0];
			}
			$resultat = mots_ressemblants($cherche_mot, $table_mots, $table_ids);
			debut_boite_info();
			if (!$resultat) {
				echo "<B>Aucun r&eacute;sultat pour \"$cherche_mot\".</B><BR>";
			}
			else if (count($resultat) == 1) {
//				$ajout_mot = 'oui';
				list(, $nouv_mot) = each($resultat);
				echo "<B>Le mot-cl&eacute; suivant a &eacute;t&eacute; ajout&eacute; &agrave; ";
				if ($table == 'articles') echo "l'article";
				else if ($table == 'breves') echo "la br&egrave;ve";
				else if ($table == 'rubriques') echo "la rubrique";
				echo " : </B><BR>";
				$query = "SELECT * FROM spip_mots WHERE id_mot=$nouv_mot";
				$result = spip_query($query);
				echo "<UL>";
				while ($row = mysql_fetch_array($result)) {
					$id_mot = $row['id_mot'];
					$titre_mot = $row['titre'];
					$type_mot = $row['type'];
					$descriptif_mot = $row['descriptif'];
				
					echo "<LI><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2><B><FONT SIZE=3>$titre_mot</FONT></B>";
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
					echo "<B>Plusieurs mots-cl&eacute;s trouv&eacute;s pour \"$cherche_mot\":</B><BR>";
					$query = "SELECT * FROM spip_mots WHERE id_mot IN ($les_mots) ORDER BY titre";
					$result = spip_query($query);
					echo "<UL>";
					while ($row = mysql_fetch_array($result)) {
						$id_mot = $row['id_mot'];
						$titre_mot = $row['titre'];
						$type_mot = $row['type'];
						$descriptif_mot = $row['descriptif'];
					
						echo "<LI><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2><B><FONT SIZE=3>$titre_mot</FONT></B>";
					
						if ($type_mot) echo " ($type_mot)";
						echo " | <A HREF=\"$url_base&nouv_mot=$id_mot\">Ajouter ce mot</A>";
					
						if (strlen($descriptif_mot) > 1) {
							echo "<BR><FONT SIZE=1>".propre(couper($descriptif_mot, 100))."</FONT>\n";
						}
						echo "</FONT><p>\n";
					}
					echo "</UL>";
				}
			}
			else {
				echo "<B>Trop de r&eacute;sultats pour \"$cherche_mot\" ; veuillez affiner la recherche.<BR>";
			}
			fin_boite_info();
			echo "<P>";
	
		}
	
	
		//////////////////////////////////////////////////////
		// Appliquer les modifications sur les mots-cles
		//
	
		if ($nouv_mot && $flag_editable && $nouv_mot!='x') {
			$query = "SELECT * FROM spip_mots_$table WHERE id_mot=$nouv_mot AND $id_table=$id_objet";
			$result = spip_query($query);
			if (!mysql_num_rows($result)) {
				$query = "INSERT INTO spip_mots_$table (id_mot,$id_table) VALUES ($nouv_mot, $id_objet)";
				$result = spip_query($query);
			}
		}

		if ($supp_mot && $flag_editable) {
			$query = "DELETE FROM spip_mots_$table WHERE id_mot=$supp_mot AND $id_table=$id_objet";
			$result = spip_query($query);
		}
	
	
		$query = "SELECT DISTINCT type FROM spip_mots";
		$result = spip_query($query);
		$plusieurs_types = (mysql_num_rows($result) > 1);
	
		unset($les_mots);
	
		$query = "SELECT mots.* FROM spip_mots AS mots, spip_mots_$table AS lien WHERE lien.$id_table=$id_objet AND mots.id_mot=lien.id_mot ORDER BY mots.type, mots.titre";
		$result = spip_query($query);
		
		echo "<table border=0 cellspacing=0 cellpadding=2 width=100% background=''>";
	
		$ifond=0;
		
		while ($row = mysql_fetch_array($result)) {
			$id_mot = $row['id_mot'];
			$titre_mot = $row['titre'];
			$type_mot = $row['type'];
			$descriptif_mot = $row['descriptif'];
			$id_groupe = $row['id_groupe'];

			$query_groupe = "SELECT * FROM spip_groupes_mots WHERE id_groupe = $id_groupe";
			$result_groupe = spip_query($query_groupe);
			while($row_groupe = mysql_fetch_array($result_groupe)) {
				$id_groupe = $row_groupe['id_groupe'];
				$titre_groupe = htmlspecialchars($row_groupe['titre']);
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


			$url = "mots_edit.php3?id_mot=$id_mot&redirect=".rawurlencode($url_base);

			echo "<TR WIDTH=\"100%\">";
			echo "<TD BGCOLOR='$couleur'>";
			echo "<A HREF='$url'><img src='img_pack/petite-cle.gif' alt='X' width='23' height='12' border='0'></A>";
			echo "</TD>";
			echo "<TD BGCOLOR='$couleur' width='100%'><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2>";
			
			
			// Changer 
			if ($unseul == "oui" AND $flag_groupe){
				echo "<form action='$url_base' method='get' style='margin:0px; padding: 0px'>";
				echo "<INPUT TYPE='Hidden' NAME='$id_table' VALUE='$id_objet'>";
				if ($table == 'rubriques') echo "<INPUT TYPE='Hidden' NAME='coll' VALUE='$id_objet'>";				
				echo "<select name='nouv_mot' CLASS='fondl' STYLE='font-size:10px; width:90px'>";
				
				$query_autres_mots = "SELECT * FROM spip_mots WHERE id_groupe = $id_groupe";
				$result_autres_mots = spip_query($query_autres_mots);
				while ($row_autres = mysql_fetch_array($result_autres_mots)) {
					$le_mot = $row_autres['id_mot'];
					$le_titre_mot = $row_autres['titre'];
					
					if ($le_mot == $id_mot) $selected = "SELECTED";
					else $selected = "";
					echo "<option value='$le_mot' $selected> $le_titre_mot";


				}
				echo "</select>";
				echo "<INPUT TYPE='Hidden' NAME='supp_mot' VALUE='$id_mot'>";
				echo "<INPUT TYPE='submit' NAME='Choisir' VALUE='Changer' CLASS='fondo' STYLE='font-size:10px'>";
				echo "</form>";

			}else {
				echo "<A HREF='$url'>$titre_mot</A>";
			}
			echo "</FONT></TD>";
	
			echo "<TD ALIGN='right' BGCOLOR='$couleur' ALIGN='right'>";
			echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2>";
			echo "$type_mot";
			echo "</FONT>";
			echo "</TD>";
	
			if ($flag_editable){
				echo "<TD BGCOLOR='$couleur' ALIGN='right'>";
				$url = $url_base."&supp_mot=$id_mot";
				
				if ($flag_groupe)				
					echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=1><A HREF=\"$url\">Retirer ce mot</A></FONT>";
				else echo "&nbsp;";
				echo "</TD>";
			}
			echo "</TR>\n";
	
			$les_mots[] = $id_mot;
		}
		echo "<tr><td></td><td></td><td><img src='img_pack/rien.gif' width=100 height=1></td><td><img src='img_pack/rien.gif' width=90 height=1></td></tr>";
		echo "</TABLE>";
		
		if ($les_mots) $les_mots = join($les_mots, ",");
		if ($id_groupes_vus) $id_groupes_vus = join($id_groupes_vus, ",");
		else $id_groupes_vus = "0";
		
		$query_groupes = "SELECT * FROM spip_groupes_mots WHERE $table = 'oui' AND $connect_statut = 'oui' AND obligatoire = 'oui' AND id_groupe NOT IN ($id_groupes_vus)";
		$nb_groupes = mysql_num_rows(spip_query($query_groupes));

		if ($flag_editable) {
			if ($nouv_mot.$cherche_mot.$supp_mot OR $nb_groupes > 0)
				echo debut_block_visible("lesmots");
			else
				echo debut_block_invisible("lesmots");

			// un menu par type de mot : mais une seule case "recherche"
			if ($table == 'articles') $url = 'articles.php3';
			else if ($table == 'breves') $url = 'breves_voir.php3';
			else if ($table == 'rubriques') $url = 'naviguer.php3';
			$case_recherche = false;
			$form_mot = "<FORM ACTION='$url' METHOD='get' STYLE='margin:1px;'>"
				."<INPUT TYPE='Hidden' NAME='$id_table' VALUE='$id_objet'>";
				
			if ($table == 'rubriques') $form_mot .= "<INPUT TYPE='Hidden' NAME='coll' VALUE='$id_objet'>";				
				
			$message_ajouter_mot = "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2><B>AJOUTER UN MOT-CL&Eacute; : &nbsp; </B></FONT>\n";
			echo "<DIV align='right'>";
			
		//////
		$query_groupes = "SELECT * FROM spip_groupes_mots WHERE $table = 'oui' AND $connect_statut = 'oui' AND (unseul != 'oui'  OR (unseul = 'oui' AND id_groupe NOT IN ($id_groupes_vus))) ORDER BY titre";
		$result_groupes = spip_query($query_groupes);



		while($row_groupes = mysql_fetch_array($result_groupes)) {
			$id_groupe = $row_groupes['id_groupe'];
			$titre_groupe = htmlspecialchars($row_groupes['titre']);
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
				if (mysql_num_rows($result) > 0) {
					if (mysql_num_rows($result) > 50 AND $flag_mots_ressemblants) {
						if (! $case_recherche) {
							echo $form_mot;
							echo $message_ajouter_mot;
							$message_ajouter_mot = "";

							if ($obligatoire == "oui" AND ! $groupes_vus[$id_groupe])
								echo "<INPUT TYPE='text' NAME='cherche_mot' CLASS='fondl' STYLE='width:150px; font-size:11px; background-color:#E86519;' VALUE=\"$titre_groupe\" SIZE='20'>";
							else if ($unseul == "oui")
								echo "<INPUT TYPE='text' NAME='cherche_mot' CLASS='fondl' STYLE='width:150px; font-size:11px; background-color:#cccccc;' VALUE=\"$titre_groupe\" SIZE='20'>";
							else
								echo "<INPUT TYPE='text' NAME='cherche_mot' CLASS='fondl' STYLE='width:150px; font-size:11px' VALUE=\"$titre_groupe\" SIZE='20'>";
					
							echo "<INPUT TYPE='hidden' NAME='select_groupe'  VALUE='$id_groupe'>";

							echo " <INPUT TYPE='submit' NAME='Chercher' VALUE='Chercher' CLASS='fondo' STYLE='font-size:10px'>";
							echo "</FORM>";
							//$case_recherche = true; // on n'en veut pas d'autre
						}
					}
					else {
						echo $form_mot;
						echo $message_ajouter_mot;
						$message_ajouter_mot = "";
						
						
						if ($obligatoire == "oui" AND ! $groupes_vus[$id_groupe])
							echo "<SELECT NAME='nouv_mot' SIZE='1' STYLE='width:150px; font-size:10px; background-color:#E86519;' CLASS='fondl'>";
						else if ($unseul == "oui")
							echo "<SELECT NAME='nouv_mot' SIZE='1' STYLE='width:150px; font-size:10px; background-color:#cccccc;' CLASS='fondl'>";
						else
							echo "<SELECT NAME='nouv_mot' SIZE='1' STYLE='width:150px; font-size:10px' CLASS='fondl'>";
							
						$ifond == 0;	
						$label = htmlspecialchars(strtoupper($titre_groupe));
						echo "<OPTION VALUE='x'>$label";
						while($row = mysql_fetch_array($result)) {
							$id_mot = $row['id_mot'];
							$titre_mot = $row['titre'];		
							$texte_option = htmlspecialchars(couper($titre_mot, 50));
							echo "\n<OPTION VALUE=\"$id_mot\">";
							echo "&nbsp;&nbsp;&nbsp;";
							echo $texte_option;
						}
						echo "</SELECT>";
						echo " &nbsp; <INPUT TYPE='submit' NAME='Choisir' VALUE='Choisir' CLASS='fondo' STYLE='font-size:10px'>";
						echo "</FORM>";
					}
				}
			
			
			}
			

			
			
			echo "</DIV>";
			echo fin_block();
		}
		fin_cadre_enfonce();
		echo "&nbsp;<P>";
	}

}


?>
