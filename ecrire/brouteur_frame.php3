<?php

include ("inc.php3");

debut_html();

	if ($spip_ecran == "large") {
		$nb_col = 4;
	} else {
		$nb_col = 3;
	}

	if ($effacer_suivant == "oui") {
		for ($i = $frame+1; $i < $nb_col; $i++) {
			echo "<script>parent.iframe$i.location.href='brouteur_frame.php3?frame=$i'</script>";
		}
	}
	echo "<div class='arial2'>";


	if ($special == "redac") {
		$query = "SELECT articles.id_article, articles.titre, articles.statut FROM spip_articles AS articles, spip_auteurs_articles AS lien WHERE articles.statut = 'prepa' AND articles.id_article = lien.id_article AND lien.id_auteur = $connect_id_auteur GROUP BY id_article ORDER BY articles.date DESC";
		$result=spip_query($query);
		if (spip_num_rows($result)>0) {
			echo "<div style='padding-top: 6px; padding-bottom: 3px;'><b class='verdana2'>"._T("info_cours_edition")."</b></div>";
			echo "<div class='plan-articles'>";
			while($row=spip_fetch_array($result)){
				$id_article=$row['id_article'];
				$titre = typo($row['titre']);
				$statut = $row['statut'];
				echo "<a class='$statut' href='javascript:window.parent.location=\"articles.php3?id_article=$id_article\"'>$titre</a>";
			}
			echo "</div>";
		}
	
	}
	else if ($special == "valider") {
		$query = "SELECT articles.id_article, articles.titre, articles.statut FROM spip_articles AS articles WHERE articles.statut = 'prop' ORDER BY articles.date DESC";
		$result=spip_query($query);
		if (spip_num_rows($result)>0) {
			echo "<div style='padding-top: 6px; padding-bottom: 3px;'><b class='verdana2'>"._T("info_articles_proposes")."</b></div>";
			echo "<div class='plan-articles'>";
			while($row=spip_fetch_array($result)){
				$id_article=$row['id_article'];
				$titre = typo($row['titre']);
				$statut = $row['statut'];
				echo "<a class='$statut' href='javascript:window.parent.location=\"articles.php3?id_article=$id_article\"'>$titre</a>";
			}
			echo "</div>";
		}
	
		$query = "SELECT * FROM spip_breves WHERE statut = 'prop' ORDER BY date_heure DESC LIMIT 0, 20";
		$result=spip_query($query);
		if (spip_num_rows($result)>0) {
			echo "<div style='padding-top: 6px;'><b class='verdana2'>"._T("info_breves_valider")."</b></div>";
			while($row=spip_fetch_array($result)){
				$id_breve=$row['id_breve'];
				$titre = typo($row['titre']);
				$statut = $row['statut'];
				$puce = "puce-orange-breve.gif";
				echo "<div style='margin:3px; padding-top: 3px; padding-bottom= 3px; padding-$spip_lang_left: 12px;  background: url(img_pack/$puce) $spip_lang_left center no-repeat;'><a href='javascript:window.parent.location=\"breves_voir.php3?id_breve=$id_breve\"' class='arial1'>$titre</a></div>";
			}
		}

	}
	else {
		$query = "SELECT * FROM spip_rubriques WHERE id_rubrique=$id_rubrique ORDER BY titre";
		$result=spip_query($query);
		if ($row=spip_fetch_array($result)){
			$ze_rubrique=$row['id_rubrique'];
			$titre = typo($row['titre']);
			$id_parent=$row['id_parent'];
			
			if ($id_parent == 0) $icone = "secteur-24.gif";
			else $icone = "rubrique-24.gif";
			
			echo "<div style='background-color: #cccccc; margin-bottom: 3px;'>";
			icone_horizontale("$titre", "javascript:window.parent.location=\"naviguer.php3?coll=$id_rubrique\"", "$icone","");
			echo "</div>";
		}  else if ($frame == 0) {
			echo "<div style='background-color: #cccccc; margin-bottom: 3px;'>";
			icone_horizontale(_T('info_racine_site'), "javascript:window.parent.location=\"naviguer.php3\"", "racine-site-24.gif","");
			echo "</div>";
		}
	
	
	
	
		$query = "SELECT * FROM spip_rubriques WHERE id_parent=$id_rubrique ORDER BY titre";
		$result=spip_query($query);
		while($row=spip_fetch_array($result)){
			$ze_rubrique=$row['id_rubrique'];
			$titre = typo($row['titre']);
			$id_parent=$row['id_parent'];
			
			if ($id_parent == '0') 	{
				echo "<div style='margin:3px; padding-top: 5px; padding-bottom: 5px; padding-$spip_lang_left: 28px; background: url(img_pack/secteur-24.gif) $spip_lang_left center no-repeat;'><b class='verdana2'><a href='brouteur_frame.php3?id_rubrique=$ze_rubrique&frame=".($frame+1)."&effacer_suivant=oui' target='iframe".($frame+1)."'>$titre</a></b></div>";
			}
			else {
				if ($frame+1 < $nb_col) echo "<div style='margin:3px; padding-top: 5px; padding-bottom: 5px; padding-$spip_lang_left: 28px; background: url(img_pack/rubrique-24.gif) $spip_lang_left center no-repeat;'><b><a href='brouteur_frame.php3?id_rubrique=$ze_rubrique&frame=".($frame+1)."&effacer_suivant=oui' target='iframe".($frame+1)."'>$titre</a></b></div>";
				else  echo "<div style='margin:3px; padding-top: 5px; padding-bottom: 5px; padding-$spip_lang_left: 28px; background: url(img_pack/rubrique-24.gif) $spip_lang_left center no-repeat;'><b><a href='javascript:window.parent.location=\"brouteur.php3?id_rubrique=$ze_rubrique\"'>$titre</a></b></div>";
			}
		}
	
	
		if ($id_rubrique > 0) {
			if ($connect_statut == "0minirezo") $query = "SELECT articles.id_article, articles.titre, articles.statut FROM spip_articles AS articles WHERE id_rubrique=$id_rubrique ORDER BY date DESC";
			else $query = "SELECT articles.id_article, articles.titre, articles.statut FROM spip_articles AS articles, spip_auteurs_articles AS lien WHERE articles.id_rubrique=$id_rubrique AND (articles.statut = 'publie' OR articles.statut = 'prop' OR (articles.statut = 'prepa' AND articles.id_article = lien.id_article AND lien.id_auteur = $connect_id_auteur)) GROUP BY id_article ORDER BY articles.date DESC";
			$result=spip_query($query);
			if (spip_num_rows($result)>0) {
				echo "<div style='padding-top: 6px; padding-bottom: 3px;'><b class='verdana2'>"._T('info_articles')."</b></div>";
				echo "<div class='plan-articles'>";
				while($row=spip_fetch_array($result)){
					$id_article=$row['id_article'];
					$titre = typo($row['titre']);
					$statut = $row['statut'];
					echo "<a class='$statut' href='javascript:window.parent.location=\"articles.php3?id_article=$id_article\"'>$titre</a>";
				}
				echo "</div>";
			}
	
			$query = "SELECT * FROM spip_breves WHERE id_rubrique=$id_rubrique ORDER BY date_heure DESC LIMIT 0, 20";
			$result=spip_query($query);
			if (spip_num_rows($result)>0) {
				echo "<div style='padding-top: 6px;'><b class='verdana2'>"._T('info_breves_02')."</b></div>";
				while($row=spip_fetch_array($result)){
					$id_breve=$row['id_breve'];
					$titre = typo($row['titre']);
					$statut = $row['statut'];
					switch ($statut) {
						case 'publie':
							$puce = 'verte';
								break;
						case 'prepa':
							$puce = 'blanche';
							break;
						case 'prop':
							$puce = 'orange';
							break;
						case 'refuse':
							$puce = 'rouge';
							break;
						case 'poubelle':
							$puce = 'poubelle';
							break;
					}
					$puce = "puce-$puce-breve.gif";
					echo "<div style='margin:3px; padding-top: 3px; padding-bottom= 3px; padding-$spip_lang_left: 12px;  background: url(img_pack/$puce) $spip_lang_left center no-repeat;'><a href='javascript:window.parent.location=\"breves_voir.php3?id_breve=$id_breve\"' class='arial1'>$titre</a></div>";
				}
			}
	
			$query = "SELECT * FROM spip_syndic WHERE id_rubrique='$id_rubrique' AND statut!='refuse' ORDER BY nom_site";
			$result=spip_query($query);
			if (spip_num_rows($result)>0) {
				echo "<div style='padding-top: 6px;'><b class='verdana2'>"._T('icone_sites_references')."</b></div>";
				while($row=spip_fetch_array($result)){
					$id_syndic=$row['id_syndic'];
					$titre = typo($row['nom_site']);
					$statut = $row['statut'];
					switch ($statut) {
						case 'publie':
							$puce = 'verte';
								break;
						case 'prepa':
							$puce = 'blanche';
							break;
						case 'prop':
							$puce = 'orange';
							break;
						case 'refuse':
							$puce = 'rouge';
							break;
						case 'poubelle':
							$puce = 'poubelle';
							break;
					}
					echo "<div style='margin:3px; padding-top: 5px; padding-bottom: 5px; padding-$spip_lang_left: 28px; background: url(img_pack/site-24.gif) $spip_lang_left center no-repeat;'><b><a href='javascript:window.parent.location=\"sites.php3?id_syndic=$id_syndic\"'>$titre</a></b></div>";
				}
			}
		}

	
		if ($frame == 0 AND $id_rubrique==0) {
			echo "<hr style='border: 0px; color: #999999; background-color: #999999; height: 2px;'>";
	
			$query = "SELECT articles.id_article, articles.titre, articles.statut FROM spip_articles AS articles, spip_auteurs_articles AS lien WHERE articles.statut = 'prepa' AND articles.id_article = lien.id_article AND lien.id_auteur = $connect_id_auteur GROUP BY id_article ORDER BY articles.date DESC";
			$result=spip_query($query);
			if (spip_num_rows($result)>0) {
				echo "<div style='margin:3px; padding-top: 5px; padding-bottom: 5px; padding-$spip_lang_left: 28px; background: url(img_pack/article-24.gif) $spip_lang_left center no-repeat;'><b class='verdana2'><a href='brouteur_frame.php3?special=redac&frame=".($frame+1)."&effacer_suivant=oui' target='iframe".($frame+1)."'>"._T("info_cours_edition")."</a></b></div>";
			}
			
			$query = "SELECT articles.id_article, articles.titre, articles.statut FROM spip_articles AS articles WHERE articles.statut = 'prop' ORDER BY articles.date DESC";
			$result = spip_query($query);
			$total_articles = spip_num_rows($result);
			
			$query = "SELECT * FROM spip_breves WHERE statut = 'prop' ORDER BY date_heure DESC LIMIT 0, 20";
			$result=spip_query($query);
			$total_breves = spip_num_rows($result);
			
			if ($total_articles + $total_breves > 0)
				echo "<div style='margin:3px; padding-top: 5px; padding-bottom: 5px; padding-$spip_lang_left: 28px; background: url(img_pack/article-24.gif) $spip_lang_left center no-repeat;'><b class='verdana2'><a href='brouteur_frame.php3?special=valider&frame=".($frame+1)."&effacer_suivant=oui' target='iframe".($frame+1)."'>"._T("info_articles_proposes")." / "._T("info_breves_valider")."</a></b></div>";
			


		}

	}

	echo "</div>";

echo "</body></html>";


?>