<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


//
if (!defined("_ECRIRE_INC_VERSION")) return;

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

function formulaire_mots($table, $id_objet, $nouv_mot, $supp_mot, $cherche_mot, $flag_editable, $retour) {
  global $connect_statut, $connect_toutes_rubriques, $options;
	global $spip_lang_rtl, $spip_lang_right;

	$retour = urlencode($retour);
	$select_groupe = $GLOBALS['select_groupe'];

	if ($table == 'articles') {
		$table_id = 'id_article';
		$objet = 'article';
		$url_base = "articles";
	}
	else if ($table == 'breves') {
		$table_id = 'id_breve';
		$objet = 'breve';
		$url_base = "breves_voir";
	}
	else if ($table == 'rubriques') {
		$table_id = 'id_rubrique';
		$objet = 'rubrique';
		$url_base = "naviguer";
	}

	else if ($table == 'syndic') {
		$table_id = 'id_syndic';
		$objet = 'syndic';
		$url_base = "sites";
	}

	list($nombre_mots) = spip_fetch_array(spip_query("SELECT COUNT(*) FROM spip_mots AS mots, spip_mots_$table AS lien WHERE lien.$table_id=$id_objet AND mots.id_mot=lien.id_mot"));

	if (!$nombre_mots) {
		if (!$flag_editable) return;
		list($nombre_groupes) = spip_fetch_array(spip_query("SELECT COUNT(*) FROM spip_groupes_mots WHERE $table = 'oui'
		AND ".substr($connect_statut,1)." = 'oui'"));

		if (!$nombre_groupes) return;
	}

	echo "<a name='mots'></a>";
	if ($flag_editable){
		if ($nouv_mot||$cherche_mot||$supp_mot)
			$bouton = bouton_block_visible("lesmots");
		else
			$bouton =  bouton_block_invisible("lesmots");
	}
	debut_cadre_enfonce("mot-cle-24.gif", false, "", $bouton._T('titre_mots_cles').aide ("artmots"));

	//////////////////////////////////////////////////////
	// Recherche de mot-cle
	//

	if ($nouv_mot)
		$nouveaux_mots = array($nouv_mot);

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
					echo " | <A href='", generer_url_ecrire($url_base, "$table_id=$id_objet&nouv_mot=$id_mot#mots"), "'>",_T('info_ajouter_mot'),"</A>";

					if (strlen($descriptif_mot) > 1) {
						echo "<BR><FONT SIZE=1>".propre(couper($descriptif_mot, 100))."</FONT>\n";
					}
					echo "</FONT><p>\n";
				}
				echo "</UL>";
			}
		}

		if ($GLOBALS['connect_statut'] == '0minirezo'
		     AND $connect_toutes_rubriques ) {
			echo "<div style='width: 200px;'>";
			$titre = urlencode($cherche_mot);
			icone_horizontale(_T('icone_creer_mot_cle'), generer_url_ecrire("mots_edit","new=oui&ajouter_id_article=$id_objet&table=$table&table_id=$table_id&titre=$titre&redirect=$retour"), "mot-cle-24.gif", "creer.gif");
			echo "</div> ";
		}

		fin_boite_info();
		echo "<P>";

	} // fin de la boucle sur la recherche de mots


	//////////////////////////////////////////////////////
	// Appliquer les modifications sur les mots-cles
	//

	if ($nouveaux_mots && $flag_editable) {
		while ((list(,$nouv_mot) = each($nouveaux_mots)) AND $nouv_mot!='x') {
			$query = "SELECT * FROM spip_mots_$table WHERE id_mot=$nouv_mot AND $table_id=$id_objet";
			$result = spip_query($query);
			if (!spip_num_rows($result)) {
				$query = "INSERT INTO spip_mots_$table (id_mot,$table_id) VALUES ($nouv_mot, $id_objet)";
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
		$query = "DELETE FROM spip_mots_$table WHERE $table_id=$id_objet $mots_supp";
		$result = spip_query($query);
		$reindexer = true;
	}


	if ($reindexer AND $GLOBALS['meta']['activer_moteur'] == 'oui') {
		include_spip("inc/indexation");
		marquer_indexer($objet, $id_objet);
	}

	//
	// Afficher les mots-cles
	//

	unset($les_mots);

	$query = "SELECT mots.* FROM spip_mots AS mots, spip_mots_$table AS lien WHERE lien.$table_id=$id_objet AND mots.id_mot=lien.id_mot ORDER BY mots.type, mots.titre";
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
	
			$url = "href='" . generer_url_ecrire('mots_edit', "id_mot=$id_mot&redirect=".rawurlencode(generer_url_ecrire($url_base, "$table_id=$id_objet#mots"))) . "'";

			$vals[] = "<A $url>" . http_img_pack('petite-cle.gif', "", "width='23' height='12' border='0'") ."</A>";
			
	
			// Changer
			if ($unseul == "oui" AND $flag_groupe) {
				$s =  generer_url_post_ecrire($url_base,"$table_id=$id_objet", '', "#mots") . 
					"<select name='nouv_mot' onChange=\"setvisibility('valider_groupe_$id_groupe', 'visible');\" CLASS='fondl' STYLE='font-size:10px; width:90px;'>";
				$query_autres_mots = "SELECT * FROM spip_mots WHERE id_groupe = $id_groupe ORDER by titre";
				$result_autres_mots = spip_query($query_autres_mots);
				while ($row_autres = spip_fetch_array($result_autres_mots)) {
					$le_mot = $row_autres['id_mot'];
					$le_titre_mot = supprimer_tags(typo($row_autres['titre']));
	
					if ($le_mot == $id_mot) $selected = "SELECTED";
					else $selected = "";
					$s .= "<option value='$le_mot' $selected> $le_titre_mot</option>";
				}
				$s .= "</select>";
				$s .= "<input type='hidden' name='supp_mot' VALUE='$id_mot' />";
				$s .= "<span class='visible_au_chargement' id='valider_groupe_$id_groupe'>";
				$s .= " &nbsp; <INPUT TYPE='submit' NAME='Choisir' VALUE='"._T('bouton_changer')."' CLASS='fondo' style='font-size: 10px';>";
				$s .= "</span>";
				$s .= "</form>";
	
			} else {
				$s = "<A $url>".typo($titre_mot)."</A>";
			}
			$vals[] = $s;
	
			$vals[] = "$type_mot";
	
			if ($flag_editable){
				if ($flag_groupe)
				  $s = "<A href='" . generer_url_ecrire($url_base, "$table_id=$id_objet&supp_mot=$id_mot#mots") . "'>"._T('info_retirer_mot')."&nbsp;" . http_img_pack('croix-rouge.gif', "X", "width='7' height='7' border='0' align='middle'") ."</A>";
				else $s = "&nbsp;";
				$vals[] = $s;
			} else $vals[]= "";

			
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
			echo "<a href='", generer_url_ecrire($url_base, "$table_id=$id_objet&supp_mot=-1#mots"), "'>",_T('info_retirer_mots'),"</a>";
			echo "</div><br />\n";
		}

		// il faudrait rajouter STYLE='margin:1px;' qq part

		$form_mot = generer_url_post_ecrire($url_base,"$table_id=$id_objet", '', "#mots");

		if ($table == 'rubriques') $form_mot .= "<INPUT TYPE='Hidden' NAME='id_rubrique' VALUE='$id_objet' />";

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
		
		if ($connect_statut == '0minirezo' AND $flag_editable AND $options == "avancees" AND $connect_toutes_rubriques) {
			echo "<tr><td></td><td colspan='2'>";
			echo "<div style='width: 200px;'>";
			icone_horizontale(_T('icone_creer_mot_cle'), generer_url_ecrire("mots_edit","new=oui&ajouter_id_article=$id_objet&table=$table&table_id=$table_id&redirect=$retour"), "mot-cle-24.gif", "creer.gif");
			echo "</div> ";
			echo "</td></tr>";
		}
		
		
		
		echo "</table>";
		echo fin_block();
	}


	fin_cadre_enfonce();
}


//
// Calculer les nombres d'elements (articles, etc.) lies a chaque mot
//

function calculer_liens_mots()
{

if ($GLOBALS['connect_statut'] =="0minirezo") $aff_articles = "'prepa','prop','publie'";
else $aff_articles = "'prop','publie'";

 $articles = array();
 $result_articles = spip_query(
	"SELECT COUNT(*) as cnt, lien.id_mot FROM spip_mots_articles AS lien, spip_articles AS article
	WHERE article.id_article=lien.id_article AND article.statut IN ($aff_articles) GROUP BY lien.id_mot"
);
 while ($row =  spip_fetch_array($result_articles)){
	$articles[$row['id_mot']] = $row['cnt'];
}


 $rubriques = array();
 $result_rubriques = spip_query(
	"SELECT COUNT(*) AS cnt, lien.id_mot FROM spip_mots_rubriques AS lien, spip_rubriques AS rubrique
	WHERE rubrique.id_rubrique=lien.id_rubrique GROUP BY lien.id_mot"
	);

 while ($row = spip_fetch_array($result_rubriques)){
	$rubriques[$row['id_mot']] = $row['cnt'];
}

 $breves = array();
 $result_breves = spip_query(
	"SELECT COUNT(*) AS cnt, lien.id_mot FROM spip_mots_breves AS lien, spip_breves AS breve
	WHERE breve.id_breve=lien.id_breve AND breve.statut IN ($aff_articles) GROUP BY lien.id_mot"
	);

 while ($row = spip_fetch_array($result_breves)){
	$breves[$row['id_mot']] = $row['cnt'];
}

 $syndic = array(); 
 $result_syndic = spip_query(
	"SELECT COUNT(*) AS cnt, lien.id_mot FROM spip_mots_syndic AS lien, spip_syndic AS syndic
	WHERE syndic.id_syndic=lien.id_syndic AND syndic.statut IN ($aff_articles) GROUP BY lien.id_mot"
	);
 while ($row = spip_fetch_array($result_syndic)){
	$sites[$row['id_mot']] = $row['cnt'];

 }

 return array('articles' => $articles, 
	      'breves' => $breves, 
	      'rubriques' => $rubriques, 
	      'syndic' => $syndic);
}

   

function afficher_groupe_mots($id_groupe) {
	global $connect_id_auteur, $connect_statut, $connect_toutes_rubriques;
	global $spip_lang_right, $couleur_claire;

	$query = "SELECT id_mot, titre, ".creer_objet_multi ("titre", "$spip_lang")." FROM spip_mots WHERE id_groupe = '$id_groupe' ORDER BY multi";

	$jjscript["fonction"] = "afficher_groupe_mots";
	$jjscript["id_groupe"] = $id_groupe;
	$jjscript = addslashes(serialize($jjscript));
	$hash = "0x".substr(md5($connect_id_auteur.$jjscript), 0, 16);
	$tmp_var = substr(md5($jjscript), 0, 4);
	
			
	$javascript = "charger_id_url('" . generer_url_ecrire("ajax_page", "fonction=sql&amp;id_ajax_fonc=::id_ajax_fonc::::deb::", true) . "','$tmp_var')";
	$tranches = afficher_tranches_requete($query, 3, $tmp_var, $javascript);


	$occurrences = calculer_liens_mots();

	$table = '';

	if (strlen($tranches)) {

		$res_proch = spip_query("SELECT id_ajax_fonc FROM spip_ajax_fonc WHERE hash=$hash AND id_auteur=$connect_id_auteur ORDER BY id_ajax_fonc DESC LIMIT 1");
		if ($row = spip_fetch_array($res_proch)) {
			$id_ajax_fonc = $row["id_ajax_fonc"];
		} else  {
			include_ecrire ("inc_abstract_sql");
			$id_ajax_fonc = spip_abstract_insert("spip_ajax_fonc", "(id_auteur, variables, hash, date)", "($connect_id_auteur, '$jjscript', $hash, NOW())");
		}
		$tranches = ereg_replace("\:\:id\_ajax\_fonc\:\:", $id_ajax_fonc, $tranches);

		if (!$GLOBALS["t_$tmp_var"]) echo "<div id='$tmp_var' style='position: relative;'>";

		echo http_img_pack("searching.gif", "*", "style='border: 0px; visibility: hidden; position: absolute; $spip_lang_right: 0px; top: -20px;' id = 'img_$tmp_var'");

		
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

				if ($connect_statut == "0minirezo" OR $occurrences['articles'][$id_mot] > 0)
				  $s = "<a href='" .
				    generer_url_ecrire('mots_edit', "id_mot=$id_mot&redirect=" . urlencode(generer_url_ecrire('mots_tous'))) .
				    "' class='liste-mot'>".typo($titre_mot)."</a>";
				else
					$s = typo($titre_mot);

				$vals[] = $s;

				$texte_lie = array();

				if ($occurrences['articles'][$id_mot] == 1)
					$texte_lie[] = _T('info_1_article');
				else if ($occurrences['articles'][$id_mot] > 1)
					$texte_lie[] = $occurrences['articles'][$id_mot]." "._T('info_articles_02');

				if ($occurrences['breves'][$id_mot] == 1)
					$texte_lie[] = _T('info_1_breve');
				else if ($occurrences['breves'][$id_mot] > 1)
					$texte_lie[] = $occurrences['breves'][$id_mot]." "._T('info_breves_03');

				if ($occurrences['sites'][$id_mot] == 1)
					$texte_lie[] = _T('info_1_site');
				else if ($occurrences['sites'][$id_mot] > 1)
					$texte_lie[] = $occurrences['sites'][$id_mot]." "._T('info_sites');

				if ($occurrences['rubriques'][$id_mot] == 1)
					$texte_lie[] = _T('info_une_rubrique_02');
				else if ($occurrences['rubriques'][$id_mot] > 1)
					$texte_lie[] = $occurrences['rubriques'][$id_mot]." "._T('info_rubriques_02');

				$texte_lie = join($texte_lie,", ");
				
				$vals[] = $texte_lie;


				if ($connect_statut=="0minirezo"  AND $connect_toutes_rubriques) {
					$vals[] = "<div style='text-align:right;'><a href='" . generer_url_ecrire("mots_tous","conf_mot=$id_mot") . "'>"._T('info_supprimer_mot')."&nbsp;<img src='" . _DIR_IMG_PACK . "croix-rouge.gif' alt='X' width='7' height='7' border='0' align='bottom' /></a></div>";
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
		
		if (!$GLOBALS["t_$tmp_var"]) echo "</div>";

		$supprimer_groupe = false;
	} 
	else
		if ($connect_statut =="0minirezo")
			$supprimer_groupe = true;

	return $supprimer_groupe;
}

?>
