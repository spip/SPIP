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
			echo "<div style='margin:3px; padding-top: 5px; padding-bottom: 5px; padding-left: 28px; background: url(img_pack/secteur-24.gif) left center no-repeat;'><b class='verdana2'><a href='brouteur_frame.php3?id_rubrique=$ze_rubrique&frame=".($frame+1)."&effacer_suivant=oui' target='iframe".($frame+1)."'>$titre</a></b></div>";
		}
		else {
			if ($frame+1 < $nb_col) echo "<div style='margin:3px; padding-top: 5px; padding-bottom: 5px; padding-left: 28px; background: url(img_pack/rubrique-24.gif) left center no-repeat;'><b><a href='brouteur_frame.php3?id_rubrique=$ze_rubrique&frame=".($frame+1)."&effacer_suivant=oui' target='iframe".($frame+1)."'>$titre</a></b></div>";
			else  echo "<div style='margin:3px; padding-top: 5px; padding-bottom: 5px; padding-left: 28px; background: url(img_pack/rubrique-24.gif) left center no-repeat;'><b><a href='javascript:window.parent.location=\"brouteur.php3?id_rubrique=$ze_rubrique\"'>$titre</a></b></div>";
		}
	}


	if ($id_rubrique > 0) {
		//if ($connect_statut == "0minirezo") $query = "SELECT articles.id_article, articles.titre, articles.statut FROM spip_articles AS articles WHERE id_rubrique=$id_rubrique ORDER BY date DESC";
		$query = "SELECT articles.id_article, articles.titre, articles.statut FROM spip_articles AS articles, spip_auteurs_articles AS lien WHERE articles.id_rubrique=$id_rubrique AND (articles.statut = 'publie' OR articles.statut = 'prop' OR (articles.statut = 'prepa' AND articles.id_article = lien.id_article AND lien.id_auteur = $connect_id_auteur)) GROUP BY id_article ORDER BY articles.date DESC";
		$result=spip_query($query);
		if (spip_fetch_row($result)>0) {
			echo "<div style='padding-top: 6px;'><b class='verdana2'>"._T('info_articles')."</b></div>";
			while($row=spip_fetch_array($result)){
				$id_article=$row['id_article'];
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
				$puce = "puce-$puce.gif";
				echo "<div style='margin:3px; padding-left: 20px; background: url(img_pack/$puce) left center no-repeat;'><a href='javascript:window.parent.location=\"articles.php3?id_article=$id_article\"'>$titre</a></div>";
			}
		}

		$query = "SELECT * FROM spip_breves WHERE id_rubrique=$id_rubrique ORDER BY date_heure DESC LIMIT 0, 20";
		$result=spip_query($query);
		if (spip_fetch_row($result)>0) {
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
				$puce = "puce-$puce.gif";
				echo "<div style='margin:3px; padding-left: 20px; background: url(img_pack/$puce) left center no-repeat;'><a href='javascript:window.parent.location=\"breves_voir.php3?id_breve=$id_breve\"'>$titre</a></div>";
			}
		}
	}

	echo "</div>";

echo "</body></html>";


?>