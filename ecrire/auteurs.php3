<?php

include ("inc.php3");
include_ecrire ("inc_acces.php3");


//
// Action : supprimer un auteur
//
if ($supp && ($connect_statut == '0minirezo'))
	spip_query("UPDATE spip_auteurs SET statut='5poubelle' WHERE id_auteur=$supp");

$myretour = "auteurs.php3?";
if ($tri) {
	$myretour .= "&tri=$tri";
	if ($tri=='nom' OR $tri=='statut')
		$partri = " (par $tri)";
	else if ($tri=='nombre')
		$partri = " (par nombre d'articles)";
}
if ($debut)
	$retour = $myretour."&debut=$debut";
else
	$retour = $myretour;
$retour = urlencode($retour);

debut_page("Auteurs$partri","redacteurs","redacteurs");

debut_gauche();



debut_boite_info();
	echo "<p class='arial1'>".propre("Vous trouverez ici tous les auteurs du site.
	Leur statut est indiqu&eacute; par la couleur de leur icone  (r&eacute;dacteur = bleu; administrateur=noir). ");

	if ($connect_statut == '0minirezo')
	echo '<br>'. propre ("Les auteurs ext&eacute;rieurs, sans acc&egrave;s au site, sont indiqu&eacute;s par un icone rouge;
		les auteurs effac&eacute;s par une poubelle.");
fin_boite_info();

if ($connect_statut == '0minirezo') {
	debut_raccourcis();
	icone_horizontale ("Cr&eacute;er un nouvel auteur", "auteur_infos.php3?new=oui", "redacteurs-24.gif", "creer.gif");
	fin_raccourcis();
}
debut_droite();


//
// Construire la requete
//

// limiter les statuts affiches
if ($connect_statut != '0minirezo') {
	$sql_statut_auteurs = " AND FIND_IN_SET(auteurs.statut,'0minirezo,1comite')";
	$sql_statut_articles = " AND FIND_IN_SET(articles.statut,'prop,publie')";
} else {
	$sql_statut_auteurs = " AND FIND_IN_SET(auteurs.statut,'0minirezo,1comite,5poubelle')";
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

if ($type_requete == 'auteur') {
	$result_auteurs = spip_query("SELECT *, UPPER(nom) AS unom
		FROM spip_auteurs AS auteurs
		WHERE 1 $sql_statut_auteurs
		$sql_order");
	while ($row = mysql_fetch_array($result_auteurs)) {
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
	while ($row = mysql_fetch_array($result_nombres))
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
	while ($row = mysql_fetch_array($result_nombres)) {
		$auteurs[$row['id_auteur']] = $row;
		$vus .= ','.$row['id_auteur'];
		$nombre_auteurs ++;
	}

	// si on est admin, ajouter tous les auteurs sans articles
	// sinon ajouter seulement les admins sans articles
	if ($connect_statut == '0minirezo')
		$sql_statut_auteurs_ajout = $sql_statut_auteurs;
	else
		$sql_statut_auteurs_ajout = " AND FIND_IN_SET(auteurs.statut,'0minirezo')";

	$result_auteurs = spip_query("SELECT auteurs.*, UPPER(nom) AS unom, 0 as compteur
		FROM spip_auteurs AS auteurs
		WHERE id_auteur NOT IN (0$vus)
		$sql_statut_auteurs_ajout
		$sql_order");
	while ($row = mysql_fetch_array($result_auteurs)) {
		$auteurs[$row['id_auteur']] = $row;
		$nombre_auteurs ++;
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

echo "<br><br><br>";
gros_titre("Les auteurs");
echo "<p>";

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

if ($nombre_auteurs > $max_par_page) {
	echo "<tr bgcolor='white'><td colspan=5>";
	echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=1>";
	for ($j=0; $j < $nombre_auteurs; $j+=$max_par_page) {
		if ($j > 0) echo " | ";

		if ($j == $debut)
			echo "<b>$j</b>";
		else if ($j > 0)
			echo "<a href=$myretour&debut=$j>$j</a>";
		else
			echo " <a href=$myretour>0</a>";
			
		if ($debut > $j  AND $debut < $j+$max_par_page){
			echo " | <b>$debut</b>";
		}	
			
	}
	echo "</font>";
	echo "</td></tr>\n";

	if ($tri == 'nom' OR !$tri) {
		// affichage des lettres
		echo "<tr bgcolor='white'><td colspan=5>";
		echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=2>";
		while (list($key,$val) = each($lettre)) {
			if ($val == $debut)
				echo "<b>$key</b> ";
			else 
				echo "<a href=$myretour&debut=$val>$key</a> ";
		}
		echo "</font>";
		echo "</td></tr>\n";
	}

	
	$debut_prec = max($debut - $max_par_page,0);
	if ($debut > 0) {
		echo "<tr bgcolor='white'><td colspan=5>";
		echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=2>";
		echo "<a href='$myretour&debut=$debut_prec'><<<</a>";
		echo "</font>";
		echo "</td></tr>\n";
	}
}




while ($i++ <= $fin && (list(,$row) = each ($auteurs))) {

	// couleur de ligne
	$couleur = ($i % 2) ? '#FFFFFF' : $couleur_claire;
	echo "<tr bgcolor='$couleur'>";

	// statut auteur
	echo "<td width='50'>";
	echo bonhomme_statut($row);

	// nom
	echo '</td><td>';
	echo "<a href='auteurs_edit.php3?id_auteur=".$row['id_auteur']."&redirect=$retour'>".typo($row['nom']).'</a>';

	if ($connect_statut == '0minirezo' AND $rub_restreinte[$row['id_auteur']])
		echo " &nbsp;<small>(admin restreint)</small>";


	// contact
	echo '</td><td>';
	if ($row['messagerie'] == 'oui' AND $row['login']
	AND $activer_messagerie != "non" AND $connect_activer_messagerie != "non" AND $messagerie != "non")
		echo bouton_imessage($row['id_auteur'],"force")."&nbsp;";
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

$debut_suivant = $debut + $max_par_page;
if ($debut_suivant < $nombre_auteurs) {
		echo "<tr bgcolor='white'><td colspan=5>";
		echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=2>";
	echo "<div align='right'><a href='$myretour&debut=$debut_suivant'>>>></a></div>";
		echo "</font>";
		echo "</td></tr>\n";
}

echo "</TABLE>\n";



fin_cadre_relief();


fin_page();

?>
