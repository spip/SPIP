<?php
//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_SUIVI_REVISIONS")) return;
define("_ECRIRE_INC_SUIVI_REVISIONS", "1");

include_ecrire("inc_lab.php");
include_spip("ecrire.php");
include_spip("revisions.php");
include_spip("diff.php");


function afficher_para_modifies ($texte, $court = false) {
	// Limiter la taille de l'affichage
	if ($court) $max = 200;
	else $max = 2000;
	
	$paras = explode ("\n",$texte);
	for ($i = 0; $i < count($paras) AND strlen($texte_ret) < $max; $i++) {
		if (ereg("diff-", $paras[$i])) $texte_ret .= $paras[$i]."\n\n";
	}
	$texte = $texte_ret;
	return $texte;
}

function afficher_suivi_versions ($debut = 0, $id_secteur = 0, $uniq_auteur = false, $lang = "", $court = false) {
	global $connect_id_auteur, $connect_statut, $dir_lang;
	
	$nb_aff = 10;
	$champs = array('surtitre', 'titre', 'soustitre', 'descriptif', 'nom_site', 'url_site', 'chapo', 'texte', 'ps');

	if ($uniq_auteur) $req_where = " AND articles.statut IN ('prepa','prop','publie')"; 
	else $req_where = " AND articles.statut IN ('prop','publie')"; 
	
	if ($uniq_auteur) $req_where = " AND versions.id_auteur = $connect_id_auteur";

	if (strlen($lang) > 0) $req_where .= " AND articles.lang='$lang'";

	if ($id_secteur > 0) $req_where .= " AND articles.id_secteur = $id_secteur";

	$query = "
		SELECT versions.*, articles.statut, articles.titre 
		FROM spip_versions AS versions, spip_articles AS articles 
		WHERE versions.id_article = articles.id_article AND versions.id_version > 1 $req_where ";
	
	$result = spip_query($query . " ORDER BY versions.date DESC LIMIT $debut, $nb_aff");
	if (spip_num_rows($result) > 0) {	
		$titre_table = _T('icone_suivi_revisions');
		if ($court) $titre_table = afficher_plus("suivi_revisions.php3").$titre_table;
		echo "<div style='height: 12px;'></div>";
		echo "<div class='liste'>";
		bandeau_titre_boite2($titre_table, "historique-24.gif");
	
		$total = spip_num_rows(spip_query($query . "LIMIT 0, 149"));
		
		if ($total > $nb_aff) {
			$nb_tranches = ceil($total / $nb_aff);
			
			echo "<div class='arial2' style='background-color: #dddddd; padding: 5px;'>";
		
			for ($i = 0; $i < $nb_tranches; $i++) {
				if ($i > 0) echo " | ";
				if ($i*$nb_aff == $debut) echo "<b>";
				else echo "<a href='suivi_revisions.php3?debut=".($i * $nb_aff)."&id_secteur=$id_secteur&uniq_auteur=$uniq_auteur&lang_choisie=$lang'>";
				echo (($i * $nb_aff) + 1);
				if ($i*$nb_aff == $debut) echo "</b>";
				else echo "</a>";
			}
			echo "</div>";
		}
	
		while ($row = mysql_fetch_array($result)) {
			$id_version = $row['id_version'];
			$id_auteur = $row['id_auteur'];
			$date = date_relative($row['date']);
			$id_article = $row['id_article'];
			$statut = $row['statut'];
			$titre = propre($row['titre']);	
			$nom = "";
			$query_auteur = "
				SELECT nom
				FROM spip_auteurs
				WHERE id_auteur = $id_auteur";
			$result_auteur = spip_query($query_auteur);
			if ($row_auteur = spip_fetch_array($result_auteur)) {
				$nom = propre($row_auteur["nom"]);
				if (strlen($nom) > 0) $nom = "($nom)";
			}
	
	
			$logo_statut = "puce-".puce_statut($statut).".gif";
			
			echo "<div class='tr_liste' style='padding: 5px; border-top: 1px solid #aaaaaa;'>";
	
			echo "<span class='arial2'>";
			echo bouton_block_invisible("$id_version-$id_article-$id_auteur");
			echo "<img src='img_pack/$logo_statut' border='0'>&nbsp;";
			echo "<a class='$statut' style='font-weight: bold;' href='articles_versions.php3?id_article=$id_article'>$titre</a>";
			echo "</span>";
			echo "<span class='arial1'>";
			echo " $date $nom";
			echo "</span>";
		
			$query_diff = "
				SELECT id_version
				FROM spip_versions
				WHERE id_article=$id_article AND id_version<$id_version 
				ORDER BY id_version DESC LIMIT 0,1";
				if ($result_diff = spip_query($query_diff)) {
					$row_diff = mysql_fetch_array($result_diff);
					$id_diff = $row_diff['id_version'];
			}
	
	
			$query_art = "
				SELECT *
				FROM spip_articles
				WHERE id_article='$id_article'";
			$result_art = spip_query($query_art);
			
			if ($row_art = spip_fetch_array($result_art)) {
				$id_article = $row_art["id_article"];
				$id_rubrique = $row_art["id_rubrique"];
				$date = $row_art["date"];
				$statut_article = $row_art["statut"];
				$maj = $row_art["maj"];
				$date_redac = $row_art["date_redac"];
				$visites = $row_art["visites"];
				$referers = $row_art["referers"];
				$extra = $row_art["extra"];
				$id_trad = $row_art["id_trad"];
			}
			
			$textes = recuperer_version($id_article, $id_version);		
					
			if ($id_version && $id_diff) {		
				if ($id_diff > $id_version) {
					$t = $id_version;
					$id_version = $id_diff;
					$id_diff = $t;
					$old = $textes;
					$new = $textes = recuperer_version($id_article, $id_version);
				}
				else {
					$old = recuperer_version($id_article, $id_diff);
					$new = $textes;
				}		
				$textes = array();			
				foreach ($champs as $champ) {
					if (!$new[$champ] && !$old[$champ]) continue;			
					$diff = new Diff(new DiffTexte);
					$textes[$champ] = afficher_para_modifies(afficher_diff($diff->comparer(preparer_diff($new[$champ]), preparer_diff($old[$champ]))), $court);
				}
			}		
			
			echo debut_block_invisible("$id_version-$id_article-$id_auteur");
			if (is_array($textes))
			foreach ($textes as $var => $t) {
				if (strlen($t) > 0) {
					echo "<blockquote class='spip serif1'>";
					echo propre($t)."";
					echo "</blockquote>";
				}		
			}		
			echo fin_block();
			echo "</div>";
		}		
		echo "</div>";
	}
}

?>