<?php

include ("inc.php3");
include_ecrire ("inc_acces.php3");


//
// Action : supprimer un auteur
//
if ($supp && ($connect_statut == '0minirezo'))
	spip_query("UPDATE spip_auteurs SET statut='5poubelle' WHERE id_auteur=$supp");

$retour = "auteurs.php3?";
if ($tri) {
	$retour .= "tri=$tri";
	if ($tri=='nom' OR $tri=='statut')
		$partri = " "._T('info_par_tri', array('tri' => $tri));
	else if ($tri=='nombre')
		$partri = " "._T('info_par_nombre_article');
}

if ($visiteurs == "oui") {
	debut_page(_T('titre_page_auteurs'),"redacteurs","redacteurs");
	$retour .= '&visiteurs=oui';
} else
	debut_page(_T('info_auteurs_par_tri', array('partri' => $partri)),"redacteurs","redacteurs");

debut_gauche();



debut_boite_info();
if ($visiteurs == "oui")
	echo "<p class='arial1'>"._T('info_gauche_visiteurs_enregistres');
else {
	echo "<p class='arial1'>"._T('info_gauche_auteurs');

	if ($connect_statut == '0minirezo')
		echo '<br>'. _T('info_gauche_auteurs_exterieurs');
}
fin_boite_info();


if ($connect_statut == '0minirezo') {
	$query = "SELECT id_auteur FROM spip_auteurs WHERE statut='6forum' LIMIT 0,1";
	$result = spip_query($query);
	$flag_visiteurs = spip_num_rows($result) > 0;

	debut_raccourcis();
	icone_horizontale (_T('icone_creer_nouvel_auteur'), "auteur_infos.php3?new=oui", "redacteurs-24.gif", "creer.gif");
	if ($flag_visiteurs) {
		if ($visiteurs == "oui")
			icone_horizontale (_T('icone_afficher_auteurs'), "auteurs.php3", "redacteurs-24.gif", "");
		else
			icone_horizontale (_T('icone_afficher_visiteurs'), "auteurs.php3?visiteurs=oui", "redacteurs-24.gif", "");
	}
	fin_raccourcis();
}
debut_droite();


//
// Construire la requete
//

// limiter les statuts affiches
if (($visiteurs == "oui") AND ($connect_statut == '0minirezo')) {
	$sql_statut_auteurs = " AND auteurs.statut IN ('6forum', '5poubelle')";
	$sql_statut_articles = '';
	$tri = 'nom';
} else if ($connect_statut != '0minirezo') {
	$sql_statut_auteurs = " AND auteurs.statut IN ('0minirezo', '1comite')";
	$sql_statut_articles = " AND articles.statut IN ('prop', 'publie')";
} else {
	$sql_statut_auteurs = " AND auteurs.statut IN ('0minirezo', '1comite', '5poubelle')";
	$sql_statut_articles = "";
}

// tri
switch ($tri) {
case 'nombre':
	$sql_order = ' ORDER BY compteur DESC, unom';
	$type_requete = 'nombre';
	break;

case 'statut':
	$sql_order = ' ORDER BY auteurs.statut, unom';
	$type_requete = 'auteur';
	break;

case 'nom':
default:
	$sql_order = ' ORDER BY unom';
	$type_requete = 'auteur';
}


// si on doit afficher les auteurs par statut ou par nom,
// la requete principale est simple, et une autre requete
// vient calculer les nombres d'articles publies ;
// si en revanche on doit classer par nombre, la bonne requete
// est la concatenation de $query_nombres et de $query_auteurs

unset($nombre_auteurs);
$auteurs = Array();

if ($type_requete == 'auteur') {
	$result_auteurs = spip_query("SELECT *, UPPER(nom) AS unom
		FROM spip_auteurs AS auteurs
		WHERE 1 $sql_statut_auteurs
		$sql_order");
	while ($row = spip_fetch_array($result_auteurs)) {
		$auteurs[$row['id_auteur']] = $row;
		$nombre_auteurs ++;

		$nom_auteur = $row['nom'];
		$premiere_lettre = addslashes(strtoupper(substr($nom_auteur,0,1)));
		if ($premiere_lettre != $lettre_prec) {
			$lettre[$premiere_lettre] = $nombre_auteurs;
		}
		$lettre_prec = $premiere_lettre;
	}

	$result_nombres = spip_query("SELECT auteurs.id_auteur, UPPER(auteurs.nom) AS unom, COUNT(articles.id_article) AS compteur
		FROM spip_auteurs AS auteurs, spip_auteurs_articles AS lien, spip_articles AS articles
		WHERE auteurs.id_auteur=lien.id_auteur AND lien.id_article=articles.id_article
		$sql_statut_auteurs $sql_statut_articles
		GROUP BY auteurs.id_auteur
		$sql_order");
	while ($row = spip_fetch_array($result_nombres))
		$auteurs[$row['id_auteur']]['compteur'] = $row['compteur'];

	// si on n'est pas minirezo, supprimer les auteurs sans article publie
	// sauf les admins, toujours visibles.
	if ($connect_statut != '0minirezo') {
		reset($auteurs);
		while (list(,$auteur) = each ($auteurs)) {
			if (! $auteur['compteur'] AND ($auteur['statut'] != '0minirezo')) {
				unset($auteurs[$auteur['id_auteur']]);
				$nombre_auteurs --;
			}
		}
	}

} else { // tri par nombre
	$result_nombres = spip_query("SELECT auteurs.*, UPPER(nom) AS unom, COUNT(articles.id_article) AS compteur
		FROM spip_auteurs AS auteurs, spip_auteurs_articles AS lien, spip_articles AS articles
		WHERE auteurs.id_auteur=lien.id_auteur AND lien.id_article=articles.id_article
		$sql_statut_auteurs $sql_statut_articles
		GROUP BY auteurs.id_auteur
		$sql_order");
	unset($vus);
	while ($row = spip_fetch_array($result_nombres)) {
		$auteurs[$row['id_auteur']] = $row;
		$vus .= ','.$row['id_auteur'];
		$nombre_auteurs ++;
	}

	// si on est admin, ajouter tous les auteurs sans articles
	// sinon ajouter seulement les admins sans articles
	if ($connect_statut == '0minirezo')
		$sql_statut_auteurs_ajout = $sql_statut_auteurs;
	else
		$sql_statut_auteurs_ajout = " AND auteurs.statut = '0minirezo'";

	$result_auteurs = spip_query("SELECT auteurs.*, UPPER(nom) AS unom, 0 as compteur
		FROM spip_auteurs AS auteurs
		WHERE id_auteur NOT IN (0$vus)
		$sql_statut_auteurs_ajout
		$sql_order");
	while ($row = spip_fetch_array($result_auteurs)) {
		$auteurs[$row['id_auteur']] = $row;
		$nombre_auteurs ++;
	}
}


unset ($rub_restreinte);
if ($connect_statut == '0minirezo') { // recuperer les admins restreints
	$restreint = spip_query("SELECT * FROM spip_auteurs_rubriques");
	while ($row = spip_fetch_array($restreint))
		$rub_restreinte[$row['id_auteur']] .= ','.$row['id_rubrique'];
}

//
// Affichage
//

echo "<br>";
if ($visiteurs=='oui')
	gros_titre(_T('info_visiteurs'));
else
	gros_titre(_T('info_auteurs'));
echo "<p>";

// reglage du debut
$max_par_page = 30;
if ($debut > $nombre_auteurs - $max_par_page)
	$debut = max(0,$nombre_auteurs - $max_par_page);
$fin = min($nombre_auteurs, $debut + $max_par_page);

// ignorer les $debut premiers
unset ($i);
reset ($auteurs);
while ($i++ < $debut AND each($auteurs));

// ici commence la vraie boucle
debut_cadre_relief('redacteurs-24.gif');
echo "<TABLE BORDER=0 CELLPADDING=3 CELLSPACING=0 WIDTH='100%' class='arial2'>\n";
echo "<tr bgcolor='#DBE1C5'>";
echo "<td width='20'>";
	$img = "<img src='img_pack/admin-12.gif' alt='' border='0'>";
	if ($tri=='statut')
		echo $img;
	else
		echo "<a href='auteurs.php3?tri=statut' title='"._T('lien_trier_statut')."'>$img</a>";

echo "</td><td>";
	if ($tri == '' OR $tri=='nom')
		echo '<b>'._T('info_nom').'</b>';
	else
		echo "<a href='auteurs.php3?tri=nom' title='"._T('lien_trier_nom')."'>"._T('info_nom')."</a>";

if ($options == 'avancees') echo "</td><td colspan='2'>"._T('info_contact');
echo "</td><td>";
	if ($visiteurs != 'oui') {
		if ($tri=='nombre')
			echo '<b>'._T('info_articles').'</b>';
		else
			echo "<a href='auteurs.php3?tri=nombre' title=\""._T('lien_trier_nombre_articles')."\">"._T('info_articles_2')."</a>"; //'
	}
echo "</td></tr>\n";

if ($nombre_auteurs > $max_par_page) {
	echo "<tr bgcolor='white'><td colspan='".($options == 'avancees' ? 5 : 3)."'>";
	echo "<font face='Verdana,Arial,Helvetica,sans-serif' size='2'>";
	for ($j=0; $j < $nombre_auteurs; $j+=$max_par_page) {
		if ($j > 0) echo " | ";

		if ($j == $debut)
			echo "<b>$j</b>";
		else if ($j > 0)
			echo "<a href=$retour&debut=$j>$j</a>";
		else
			echo " <a href=$retour>0</a>";

		if ($debut > $j  AND $debut < $j+$max_par_page){
			echo " | <b>$debut</b>";
		}

	}
	echo "</font>";
	echo "</td></tr>\n";

	if (($tri == 'nom' OR !$tri) AND $options == 'avancees') {
		// affichage des lettres
		echo "<tr bgcolor='white'><td colspan='5'>";
		echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=2>";
		while (list($key,$val) = each($lettre)) {
			if ($val == $debut)
				echo "<b>$key</b> ";
			else
				echo "<a href=$retour&debut=$val>$key</a> ";
		}
		echo "</font>";
		echo "</td></tr>\n";
	}
	echo "<tr height='5'></tr>";
}



while ($i++ <= $fin && (list(,$row) = each ($auteurs))) {
	// couleur de ligne
	$couleur = ($i % 2) ? '#FFFFFF' : $couleur_claire;
	echo "<tr bgcolor='$couleur'>";

	// statut auteur
	echo "<td>";
	echo bonhomme_statut($row);

	// nom
	echo '</td><td>';
	echo "<a href='auteurs_edit.php3?id_auteur=".$row['id_auteur']."'>".typo($row['nom']).'</a>';

	if ($connect_statut == '0minirezo' AND $row['statut']=='0minirezo' AND $rub_restreinte[$row['id_auteur']])
		echo " &nbsp;<small>"._T('statut_admin_restreint')."</small>";


	// contact
	if ($options == 'avancees') {
		echo '</td><td>';
		if ($row['messagerie'] == 'oui' AND $row['login']
		AND $activer_messagerie != "non" AND $connect_activer_messagerie != "non" AND $messagerie != "non")
			echo bouton_imessage($row['id_auteur'],"force")."&nbsp;";
		if ($connect_statut=="0minirezo")
			if (strlen($row['email'])>3)
				echo "<A HREF='mailto:".$row['email']."'>"._T('lien_email')."</A>";
			else
				echo "&nbsp;";

		if (strlen($row['url_site'])>3)
			echo "</td><td><A HREF='".$row['url_site']."'>"._T('lien_site')."</A>";
		else
			echo "</td><td>&nbsp;";
	}

	// nombre d'articles
	echo '</td><td>';
	if ($row['compteur'] > 1)
		echo $row['compteur']."&nbsp;"._T('info_article_2');
	else if($row['compteur'] == 1)
		echo "1&nbsp;"._T('info_article');
	else
		echo "&nbsp;";

	echo "</td></tr>\n";
}

echo "</table>\n";


echo "<a name='bas'>";
echo "<table width='100%' border='0'>";

$debut_suivant = $debut + $max_par_page;
if ($debut_suivant < $nombre_auteurs OR $debut > 0) {
	echo "<tr height='10'></tr>";
	echo "<tr bgcolor='white'><td align='left'>";
	if ($debut > 0) {
		$debut_prec = strval(max($debut - $max_par_page, 0));
		$link = new Link;
		$link->addVar('debut', $debut_prec);
		echo $link->getForm('GET');
		echo "<input type='submit' name='submit' value='&lt;&lt;&lt;' class='fondo'>";
		echo "</form>";
		//echo "<a href='$retour&debut=$debut_prec'>&lt;&lt;&lt;</a>";
	}
	echo "</td><td align='right'>";
	if ($debut_suivant < $nombre_auteurs) {
		$link = new Link;
		$link->addVar('debut', $debut_suivant);
		echo $link->getForm('GET');
		echo "<input type='submit' name='submit' value='&gt;&gt;&gt;' class='fondo'>";
		echo "</form>";
		//echo "<a href='$retour&debut=$debut_suivant'>&gt;&gt;&gt;</a>";
	}
	echo "</td></tr>\n";
}

echo "</table>\n";



fin_cadre_relief();


fin_page();

?>
