<?php

include ("inc.php3");
include_local ("inc_acces.php3");


//
// Action : supprimer un auteur
//
if ($supp && ($connect_statut == '0minirezo'))
	spip_query("UPDATE spip_auteurs SET statut='5poubelle' WHERE id_auteur=$supp");


debut_page("Auteurs","redacteurs","redacteurs");

debut_gauche();

if ($connect_statut == '0minirezo') {
	debut_raccourcis();
	icone_horizontale ("Cr&eacute;er un nouvel auteur", "auteur_infos.php3?new=oui&redirect=$retour", "redacteurs-24.gif", "creer.gif");
	fin_raccourcis();
}

debut_boite_info();
	echo "<p class='arial1'>".propre("Vous trouverez ici tous les auteurs du site.
	Leur statut (r&eacute;dacteur ou administrateur) est indiqu&eacute; par la couleur de l'icone. ");

	if ($connect_statut == '0minirezo')
	echo '<br>'. propre ("Les auteurs sans acc&egrave;s au site sont indiqu&eacute;s par un icone rouge;
		les auteurs effac&eacute;s par une poubelle.");
fin_boite_info();

debut_droite();


//
// Construire la requete
//

// statuts auteurs affiches
if ($connect_statut != '0minirezo')
	$sql_statut_auteurs = " AND FIND_IN_SET(auteurs.statut,'0minirezo,1comite,5poubelle')";
else
	$sql_statut_auteurs = " AND FIND_IN_SET(auteurs.statut,'0minirezo,1comite')";

// statuts articles affiches
unset($sql_statut_articles);
if ($connect_statut<>"0minirezo")
	$sql_statut_articles = " AND FIND_IN_SET(articles.statut,'prop,publie')";

// tri
switch ($tri) {
	case 'nombre':
		$sql_order = ' ORDER BY compteur DESC, UPPER(nom)';
		$type_requete = 'nombre';
		break;

	case 'statut':
		$sql_order = ' ORDER BY auteurs.statut, UPPER(nom)';
		$type_requete = 'auteur';
		break;

	case 'nom':
	default:
		$sql_order = ' ORDER BY UPPER(nom)';
		$type_requete = 'auteur';
}


// si on doit afficher les auteurs par statut ou par nom, 
// la requete principale est simple, et une autre requete
// vient calculer les nombres d'articles publies ;
// si en revanche on doit classer par nombre, la bonne requete
// est la concatenation de $query_nombres et de $query_auteurs

unset($nombre_auteurs);

if ($type_requete == 'auteur') {
	$result_auteurs = spip_query("SELECT *
		FROM spip_auteurs AS auteurs
		WHERE 1 $sql_statut_auteurs
		$sql_order");
	while ($row = mysql_fetch_array($result_auteurs)) {
		$auteurs[$row['id_auteur']] = $row;
		$nombre_auteurs ++;
	}

	$query = "SELECT auteurs.id_auteur, COUNT(articles.id_article) AS compteur
		FROM spip_auteurs AS auteurs, spip_auteurs_articles AS lien, spip_articles AS articles
		WHERE auteurs.id_auteur=lien.id_auteur AND lien.id_article=articles.id_article
		$sql_statut_auteurs $sql_statut_articles
		GROUP BY auteurs.id_auteur
		$sql_order";
	$result_nombres = spip_query($query);
	while ($row = mysql_fetch_array($result_nombres))
		$auteurs[$row['id_auteur']]['compteur'] = $row['compteur'];

	// si on n'est pas minirezo, supprimer les auteurs sans article publie
	if ($connect_statut <> '0minirezo') {
		reset($auteurs);
		while (list(,$auteur) = each ($auteurs)) {
			if (! $auteurs[$row['id_auteur']]['compteur']) {
				unset($auteurs[$auteur['id_auteur']]);
				$nombre_auteurs --;
			}
		}
	}

} else {
	$result_nombres = spip_query("SELECT auteurs.*, COUNT(articles.id_article) AS compteur
		FROM spip_auteurs AS auteurs, spip_auteurs_articles AS lien, spip_articles AS articles
		WHERE auteurs.id_auteur=lien.id_auteur AND lien.id_article=articles.id_article
		$sql_statut_auteurs $sql_statut_articles
		GROUP BY auteurs.id_auteur
		$sql_order");
	unset($vus);
	while ($row = mysql_fetch_array($result_nombres)) {
		$auteurs[$row['id_auteur']] = $row;
		$vus .= ','.$row['id_auteur'];
		$nombre_auteurs ++;
	}
	if ($connect_statut == '0minirezo') {
		$result_auteurs = spip_query("SELECT auteurs.*, 0 as compteur
			FROM spip_auteurs AS auteurs
			WHERE id_auteur NOT IN (0$vus) $sql_statut_auteurs
			$sql_order");
		while ($row = mysql_fetch_array($result_auteurs)) {
			$auteurs[$row['id_auteur']] = $row;
			$nombre_auteurs ++;
		}
	}
}


unset ($rub_restreinte);
if ($connect_statut == '0minirezo') { // recuperer les admins restreints
	$restreint = spip_query("SELECT * FROM spip_auteurs_rubriques");
	while ($row = mysql_fetch_array($restreint))
		$rub_restreinte[$row['id_auteur']] .= ','.$row['id_rubrique'];
}

//
// Affichage
//

echo "<p>";
gros_titre("Les auteurs");
echo "<p>";

$myretour = "auteurs.php3?";
if ($tri)
	$myretour .= "&tri=$tri";
if ($debut)
	$retour = $myretour."&debut=$debut";
else
	$retour = $myretour;
$retour = urlencode($retour);


// reglage du debut
$max_par_page = 50;
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
echo "<td width='50'>";
	$img = "<img src='img_pack/bonhomme-noir.gif' alt='Statut' border='0'>";
	if ($tri=='statut')
		echo $img;
	else
		echo "<a href='auteurs.php3?tri=statut' title='Trier par statut'>$img</a>";
	
echo "</td><td>";
	if ($tri == '' OR $tri=='nom')
		echo '<b>Nom</b>';
	else
		echo "<a href='auteurs.php3?tri=nom' title='Trier par nom'>Nom</a>";

echo "</td><td colspan=2>Contact";
echo "</td><td>";
	if ($tri=='nombre')
		echo '<b>Articles</b>';
	else
		echo "<a href='auteurs.php3?tri=nombre' title=\"Trier par nombre d'articles\">Articles</a>";

echo "</tr>\n";

if ($nombre_auteurs > 50) {
	echo "<tr bgcolor='#DBE1C5'><td colspan=5>";
	for ($j=0; $j < $nombre_auteurs; $j+=$max_par_page) {
		if ($j > 0) echo " | ";

		if ($j == $debut)
			echo "<b>$j</b>";
		else if ($j > 0)
			echo "<a href=$myretour&debut=$j>$j</a>";
		else
			echo " <a href=$myretour>0</a>";
	}
	echo "&nbsp; &nbsp; <i>($nombre_auteurs auteurs au total)</i></td></tr>\n";
}

while ($i++ <= $fin && (list(,$row) = each ($auteurs))) {

	// couleur de ligne
	$couleur = ($i % 2) ? '#FFFFFF' : $couleur_claire;
	echo "<tr bgcolor='$couleur'>";

	// statut auteur
	echo "<td width='50'>";
	switch($row['statut']){
		case "0minirezo":
			$image = "<img src='img_pack/bonhomme-noir.gif' alt='Admin' border='0'>";
			break;
		case "1comite":
			if ($connect_statut == '0minirezo' AND ! $row['pass'])
				$image = "<img src='img_pack/bonhomme-rouge.gif' alt='Sans acc&egrave;s' border='0'>";
			else
				$image = "<img src='img_pack/bonhomme-bleu.gif' alt='R&eacute;dacteur' border='0'>";
			break;
		case "5poubelle":
			$image = "<img src='img_pack/supprimer.gif' alt='Effac&eacute;' border='0'>";
			break;
		case "nouveau":
		default:
			$image = '';
			break;
	}
	if ($image && $connect_statut=="0minirezo")
		$image = "<A HREF='auteurs_edit.php3?id_auteur=".$row['id_auteur']."&redirect=$retour'>$image</a>";

	echo $image;

	// nom
	echo '</td><td>';
	if ($row['nom'] && $connect_statut=="0minirezo")
		echo "<A HREF='auteurs_edit.php3?id_auteur=".$row['id_auteur']."&redirect=$retour'>".typo($row['nom']).'</a>';
	else
		echo typo($nom);

	if ($connect_statut == '0minirezo' AND $rub_restreinte[$row['id_auteur']])
		echo " &nbsp;<small>(admin restreint)</small>";


	// contact
	echo '</td><td>';
	if ($row['messagerie'] == 'oui' AND $row['login']
	AND $activer_messagerie<>"non" AND $connect_activer_messagerie<>"non" AND $messagerie<>"non")
		echo bouton_imessage($id_auteur,"force")."&nbsp;";
	if ($connect_statut=="0minirezo")
		if (strlen($row['email'])>3)
			echo "<A HREF='mailto:".$row['email']."'>email</A>";
		else
			echo "&nbsp;";

	if (strlen($row['url_site'])>3)
		echo "</td><td><A HREF='".$row['url_site']."'>site</A>";
	else
		echo "</td><td>&nbsp;";

	// nombre d'articles
	echo '</td><td>';
	if ($row['compteur'] > 1)
		echo $row['compteur']."&nbsp;articles";
	else if($row['compteur'] == 1)
		echo "1&nbsp;article";
	else
		echo "&nbsp;";

	echo "</td></tr>\n";
}

echo "</TABLE>\n";
fin_cadre_relief();


fin_page();

?>
