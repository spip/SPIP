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


//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_MOTS")) return;
define("_ECRIRE_INC_MOTS", "1");

include_ecrire("inc_filtres.php3"); # pour http_script (normalement déjà fait)


// ne pas faire d'erreur si les chaines sont > 254 caracteres
function levenshtein255 ($a, $b) {
	$a = substr($a, 0, 254);
	$b = substr($b, 0, 254);
	return @levenshtein($a,$b);
}

// reduit un mot a sa valeur translitteree et en minuscules
function reduire_mot($mot) {
	return strtr(
		translitteration(trim($mot)),
		'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
		'abcdefghijklmnopqrstuvwxyz'
		);
}

function mots_ressemblants($mot, $table_mots, $table_ids='') {
	$lim = 2;
	$nb = 0;
	$opt = 1000000;
	$mot_opt = '';
	$mot = reduire_mot($mot);
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
					$val2 = reduire_mot($val2);
					$len2 = strlen($val2);
					if ($val2 == $mot)
						$m = -2; # resultat exact
					else if (substr($val2, 0, $len) == $mot)
						$m = -1; # sous-chaine
					else {
						# distance
						$m = levenshtein255($val2, $mot);
						# ne pas compter la distance due a la longueur
						$m -= max(0, $len2 - $len); 
					}
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
	if ($opt > -1) {
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
	global $connect_statut, $options;
	global $spip_lang_rtl, $spip_lang_right;

	$select_groupe = $GLOBALS['select_groupe'];

	if ($table == 'articles') {
		$id_table = 'id_article';
		$objet = 'article';
		$url_base = "articles.php3?id_article=$id_objet";
	}
	else if ($table == 'breves') {
		$id_table = 'id_breve';
		$objet = 'breve';
		$url_base = "breves_voir.php3?id_breve=$id_objet";
	}
	else if ($table == 'rubriques') {
		$id_table = 'id_rubrique';
		$objet = 'rubrique';
		$url_base = "naviguer.php3?id_rubrique=$id_objet";
	}

	else if ($table == 'syndic') {
		$id_table = 'id_syndic';
		$objet = 'syndic';
		$url_base = "sites.php3?id_syndic=$id_objet";
	}

	list($nombre_mots) = spip_fetch_array(spip_query("SELECT COUNT(*) FROM spip_mots AS mots, spip_mots_$table AS lien WHERE lien.$id_table=$id_objet AND mots.id_mot=lien.id_mot"));

	if (!$nombre_mots) {
		if (!$flag_editable) return;
		list($nombre_groupes) = spip_fetch_array(spip_query("SELECT COUNT(*) FROM spip_groupes_mots WHERE $table = 'oui'
		AND ".substr($connect_statut,1)." = 'oui'"));

		if (!$nombre_groupes) return;
	}

	echo "<a name='mots'></a>";
	if ($flag_editable){
		if ($nouv_mot.$cherche_mot.$supp_mot)
			$bouton = bouton_block_visible("lesmots");
		else
			$bouton =  bouton_block_invisible("lesmots");
	}
	debut_cadre_enfonce("mot-cle-24.gif", false, "", $bouton._T('titre_mots_cles').aide ("artmots"));

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
				$type_mot = typo($row['type']);
				$descriptif_mot = $row['descriptif'];

				echo "<LI><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=2><B><FONT SIZE=3>".typo($titre_mot)."</FONT></B>";
				echo "</FONT>\n";
			}
			echo "</UL>";
		}
		else {
			reset($resultat);
			unset($les_mots);
			while (list(, $id_mot) = each($resultat)
			AND $nombre ++ < 17)
				$les_mots[] = $id_mot;
			if ($les_mots) {
				if (count($resultat) > 17) {
					echo "<br /><b>"._T('info_trop_resultat', array('cherche_mot' => $cherche_mot))."</b><p />\n";
				}
				$les_mots = join(',', $les_mots);
				echo "<B>"._T('info_plusieurs_mots_trouves', array('cherche_mot' => $cherche_mot))."</B><BR>";
				$query = "SELECT * FROM spip_mots WHERE id_mot IN ($les_mots) ORDER BY titre";
				$result = spip_query($query);
				echo "<UL>";
				while ($row = spip_fetch_array($result)) {
					$id_mot = $row['id_mot'];
					$titre_mot = $row['titre'];
					$type_mot = typo($row['type']);
					$descriptif_mot = $row['descriptif'];

					echo "<LI><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=2><B><FONT SIZE=3>".typo($titre_mot)."</FONT></B>";

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

		if ($GLOBALS['connect_statut'] == '0minirezo') {
			echo "<div style='width: 200px;'>";
			$retour = urlencode($GLOBALS['clean_link']->getUrl());
			$titre = urlencode($cherche_mot);
			icone_horizontale(_T('icone_creer_mot_cle'), "mots_edit.php3?new=oui&ajouter_id_article=$id_objet&table=$table&id_table=$id_table&titre=$titre&redirect=$retour", "mot-cle-24.gif", "creer.gif");
			echo "</div> ";
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
		$reindexer = true;
	}

	if ($supp_mot && $flag_editable) {
		if ($supp_mot == -1)
			$mots_supp = "";
		else
			$mots_supp = " AND id_mot=$supp_mot";
		$query = "DELETE FROM spip_mots_$table WHERE $id_table=$id_objet $mots_supp";
		$result = spip_query($query);
		$reindexer = true;
	}


	if ($reindexer AND lire_meta('activer_moteur') == 'oui') {
		include_ecrire ("inc_index.php3");
		marquer_indexer($objet, $id_objet);
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

	if (spip_num_rows($result) > 0) {
		echo "<div class='liste'>";
		echo "<table width='100%' cellpadding='3' cellspacing='0' border='0' background=''>";
	
		$ifond=0;
			
		$tableau= '';
		while ($row = spip_fetch_array($result)) {
			$vals = '';
		
			$id_mot = $row['id_mot'];
			$titre_mot = $row['titre'];
			$descriptif_mot = $row['descriptif'];
			$id_groupe = $row['id_groupe'];
			$query_groupe = "SELECT * FROM spip_groupes_mots WHERE id_groupe = $id_groupe";
			$result_groupe = spip_query($query_groupe);
			while($row_groupe = spip_fetch_array($result_groupe)) {
				$id_groupe = $row_groupe['id_groupe'];
				$titre_groupe = entites_html($row_groupe['titre']);
				// On recupere le typo_mot ici, et non dans le mot-cle lui-meme; sinon bug avec arabe
				$type_mot = typo($row_groupe['titre']);
				$unseul = $row_groupe['unseul'];
				$obligatoire = $row_groupe['obligatoire'];
				$acces_admin =  $row_groupe['minirezo'];
				$acces_redacteur = $row_groupe['comite'];
	
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
	
			$vals[] = "<A HREF='$url'>" . http_img_pack('petite-cle.gif', "", "width='23' height='12' border='0'") ."</A>";
			
	
			// Changer
			if ($unseul == "oui" AND $flag_groupe) {
				$s = "<form action='$url_base#mots' method='post' style='margin:0px; padding: 0px'>";
				$s .= "<INPUT TYPE='Hidden' NAME='$id_table' VALUE='$id_objet'>";
				if ($table == 'rubriques') $s .= "<INPUT TYPE='Hidden' NAME='id_rubrique' VALUE='$id_objet'>";
				$s .= "<select name='nouv_mot' onChange=\"setvisibility('valider_groupe_$id_groupe', 'visible');\" CLASS='fondl' STYLE='font-size:10px; width:90px;'>";
	
				$query_autres_mots = "SELECT * FROM spip_mots WHERE id_groupe = $id_groupe ORDER by titre";
				$result_autres_mots = spip_query($query_autres_mots);
				while ($row_autres = spip_fetch_array($result_autres_mots)) {
					$le_mot = $row_autres['id_mot'];
					$le_titre_mot = supprimer_tags(typo($row_autres['titre']));
	
					if ($le_mot == $id_mot) $selected = "SELECTED";
					else $selected = "";
					$s .= "<option value='$le_mot' $selected> $le_titre_mot";
				}
				$s .= "</select>";
				$s .= "<INPUT TYPE='Hidden' NAME='supp_mot' VALUE='$id_mot'>";
				$s .= "<span class='visible_au_chargement' id='valider_groupe_$id_groupe'>";
				$s .= " &nbsp; <INPUT TYPE='submit' NAME='Choisir' VALUE='"._T('bouton_changer')."' CLASS='fondo' style='font-size: 10px';>";
				$s .= "</span>";
				$s .= "</form>";
	
			} else {
				$s = "<A HREF='$url'>".typo($titre_mot)."</A>";
			}
			$vals[] = $s;
	
			$vals[] = "$type_mot";
	
			if ($flag_editable){
				$s = "";
				if ($flag_groupe)
				  $s .= "<A HREF=\"$url_base&supp_mot=$id_mot#mots\">"._T('info_retirer_mot')."&nbsp;" . http_img_pack('croix-rouge.gif', "X", "width='7' height='7' border='0' align='middle'") ."</A>";
				else $s .= "&nbsp;";
			}
			$vals[] = $s;
			
			$tableau[] = $vals;
	
			$les_mots[] = $id_mot;
		}
	
		$largeurs = array('25', '', '', '');
		$styles = array('arial11', 'arial2', 'arial2', 'arial1');
		afficher_liste($largeurs, $tableau, $styles);
	
	
		echo "</table></div>";
	}

	if ($les_mots) {
		$nombre_mots_associes = count($les_mots);
		$les_mots = join($les_mots, ",");
	} else {
		$les_mots = "0";
	}
	if ($id_groupes_vus) $id_groupes_vus = join($id_groupes_vus, ",");
	else $id_groupes_vus = "0";

	$query_groupes = "SELECT * FROM spip_groupes_mots WHERE $table = 'oui'
	AND ".substr($connect_statut,1)." = 'oui' AND obligatoire = 'oui'
	AND id_groupe NOT IN ($id_groupes_vus)";
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
			if ($GLOBALS['browser_layer']) echo http_script("
triangle = findObj('triangle' + $couche_a_redresser);
if (triangle) triangle.src = '" . _DIR_IMG_PACK . "deplierbas$spip_lang_rtl.gif';");
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

		if ($table == 'rubriques') $form_mot .= "<INPUT TYPE='Hidden' NAME='id_rubrique' VALUE='$id_objet'>";

		$message_ajouter_mot = "<span class='verdana1'><B>"._T('titre_ajouter_mot_cle')."</B></span> &nbsp;\n";

		echo "<table border='0' width='100%' style='text-align: $spip_lang_right'>";

		$query_groupes = "SELECT *, ".creer_objet_multi ("titre", "$spip_lang")." FROM spip_groupes_mots WHERE $table = 'oui'
		AND ".substr($connect_statut,1)." = 'oui' AND (unseul != 'oui'  OR
		(unseul = 'oui' AND id_groupe NOT IN ($id_groupes_vus)))
		ORDER BY multi";
		$result_groupes = spip_query($query_groupes);

		// Afficher un menu par groupe de mots


		while ($row_groupes = spip_fetch_array($result_groupes)) {
			$id_groupe = $row_groupes['id_groupe'];
			$titre_groupe = entites_html(textebrut(typo($row_groupes['titre'])));
			$unseul = $row_groupes['unseul'];
			$obligatoire = $row_groupes['obligatoire'];
			$articles = $row_groupes['articles'];
			$breves = $row_groupes['breves'];
			$rubriques = $row_groupes['rubriques'];
			$syndic = $row_groupes['syndic'];
			$acces_minirezo = $row_groupes['minirezo'];
			$acces_comite = $row_groupes['comite'];
			$acces_forum = $row_groupes['forum'];
			
			$query = "SELECT * FROM spip_mots WHERE id_groupe = '$id_groupe' ";
			if ($les_mots) $query .= "AND id_mot NOT IN ($les_mots) ";
			$query .= "ORDER BY type, titre";
			$result = spip_query($query);
			if (spip_num_rows($result) > 0) {
				if ((spip_num_rows($result) > 50)) {
					echo "\n<tr>";
					echo $form_mot;
					echo "\n<td>";
					echo $message_ajouter_mot;
					$message_ajouter_mot = "";
					echo "</td>\n<td>";
					$jscript = "onfocus=\"setvisibility('valider_groupe_$id_groupe', 'visible'); if(!antifocus_mots[$id_groupe]){this.value='';antifocus_mots[$id_groupe]=true;}\"";

					if ($obligatoire == "oui" AND !$groupes_vus[$id_groupe])
						echo "<INPUT TYPE='text' NAME='cherche_mot' CLASS='fondl' STYLE='width: 180px; background-color:#E86519;' VALUE=\"$titre_groupe\" SIZE='20' $jscript>";
					else if ($unseul == "oui")
						echo "<INPUT TYPE='text' NAME='cherche_mot' CLASS='fondl' STYLE='width: 180px; background-color:#cccccc;' VALUE=\"$titre_groupe\" SIZE='20' $jscript>";
					else
						echo "<INPUT TYPE='text' NAME='cherche_mot'  CLASS='fondl' STYLE='width: 180px; ' VALUE=\"$titre_groupe\" SIZE='20' $jscript>";

					echo "</td>\n<td>";
					echo "<INPUT TYPE='hidden' NAME='select_groupe'  VALUE='$id_groupe'>";

					echo "<span class='visible_au_chargement' id='valider_groupe_$id_groupe'>";
					echo " <INPUT TYPE='submit' NAME='Chercher' VALUE='"._T('bouton_chercher')."' CLASS='fondo' STYLE='font-size:10px'>";
					echo "</span>";
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
						echo "<SELECT NAME='nouv_mot' SIZE='1' onChange=\"setvisibility('valider_groupe_$id_groupe', 'visible');\" STYLE='width: 180px; background-color:#E86519;' CLASS='fondl'>";
					else if ($unseul == "oui")
						echo "<SELECT NAME='nouv_mot' SIZE='1' onChange=\"setvisibility('valider_groupe_$id_groupe', 'visible');\" STYLE='width: 180px; background-color:#cccccc;' CLASS='fondl'>";
					else
						echo "<SELECT NAME='nouv_mot' SIZE='1' onChange=\"setvisibility('valider_groupe_$id_groupe', 'visible');\" STYLE='width: 180px; ' CLASS='fondl'>";

					$ifond == 0;
					echo "\n<option value='x' style='font-variant: small-caps;'>$titre_groupe</option>";
					while($row = spip_fetch_array($result)) {
						$id_mot = $row['id_mot'];
						$titre_mot = $row['titre'];
						$texte_option = entites_html(textebrut(typo($titre_mot)));
						echo "\n<OPTION VALUE=\"$id_mot\">";
						echo "&nbsp;&nbsp;&nbsp;";
						echo "$texte_option</option>";
					}
					echo "</SELECT>";
					echo "</td>\n<td>";
					echo "<span class='visible_au_chargement' id='valider_groupe_$id_groupe'>";
					echo " &nbsp; <INPUT TYPE='submit' NAME='Choisir' VALUE='"._T('bouton_choisir')."' CLASS='fondo'>";
					echo "</span>";
					echo "</td></FORM>";
					echo "</tr>";
				}
			}
		}
		
		if ($connect_statut == '0minirezo' AND $flag_editable AND $options == "avancees") {
			echo "<tr><td></td><td colspan='2'>";
			echo "<div style='width: 200px;'>";
			$retour = urlencode($GLOBALS['clean_link']->getUrl());
			icone_horizontale(_T('icone_creer_mot_cle'), "mots_edit.php3?new=oui&ajouter_id_article=$id_objet&table=$table&id_table=$id_table&redirect=$retour", "mot-cle-24.gif", "creer.gif");
			echo "</div> ";
			echo "</td></tr>";
		}
		
		
		
		echo "</table>";
		echo fin_block();
	}


	fin_cadre_enfonce();
}


?>
