<?

include ("inc.php3");
include_local ("inc_index.php3");
include_local ("inc_logos.php3");


function mySel($varaut, $variable) {
	$retour = " VALUE=\"$varaut\"";

	if ($variable==$varaut){
		$retour.= " SELECTED";
	}
	return $retour;
}


//
// modifications mot
//
if ($connect_statut == '0minirezo') {
	if ($new == 'oui') {
		$query = "INSERT INTO spip_mots (titre) VALUES ('')";
		$result = mysql_query($query);
		$id_mot = mysql_insert_id();
	}

	if ($supp_mot) {
		$query = "DELETE FROM spip_mots WHERE id_mot=$supp_mot";
		$result = mysql_query($query);
		$query = "DELETE FROM spip_mots_articles WHERE id_mot=$supp_mot";
		$result = mysql_query($query);
	}

	if ($titre) {
		$titre = addslashes($titre);
		$texte = addslashes($texte);
		$descriptif = addslashes($descriptif);
		$type = addslashes(corriger_caracteres($type));
		$result = mysql_query("SELECT * FROM spip_groupes_mots WHERE id_groupe='$id_type'");
		while($row = mysql_fetch_array($result)) {
				$type = addslashes(corriger_caracteres($row['titre']));
		}
				
		$query = "UPDATE spip_mots SET titre=\"$titre\", texte=\"$texte\", descriptif=\"$descriptif\", type=\"$type\", id_groupe=\"$id_type\" WHERE id_mot=$id_mot";
		$result = mysql_query($query);
		
		
		
		if (lire_meta('activer_moteur') == 'oui') {
			indexer_mot($id_mot);
		}
	}
}

//
// redirection ou affichage
//
if ($redirect_ok == 'oui' && $redirect) {
	@header("Location: ".rawurldecode($redirect));
	exit;
}

//
// Recupere les donnees
//
$query = "SELECT * FROM spip_mots WHERE id_mot='$id_mot'";
$result = mysql_query($query);

if ($row = mysql_fetch_array($result)) {
	$id_mot = $row['id_mot'];
	$titre = typo($row['titre']);
	$descriptif = propre($row['descriptif']);
	$texte = propre($row['texte']);
	$type = $row['type'];
}

debut_page("&laquo; $titre &raquo;");
debut_gauche();


//////////////////////////////////////////////////////
// Boite "voir en ligne"
//

debut_boite_info();
echo "<CENTER>";
echo "<A HREF='../spip_redirect.php3?id_mot=$id_mot&recalcul=oui'><img src='IMG2/voirenligne.gif' alt='voir en ligne' width='48' height='48' border='0' align='right'></A>";

echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=1><B>MOT NUM&Eacute;RO :</B></FONT>";
echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=6><B>$id_mot</B></FONT>";
echo "</CENTER>";
fin_boite_info();


//////////////////////////////////////////////////////
// Logos du mot-clef
//

$arton = "moton$id_mot";
$artoff = "motoff$id_mot";
$arton_ok = get_image($arton);
if ($arton_ok) $artoff_ok = get_image($artoff);

if ($connect_statut == '0minirezo' AND ($options == 'avancees' OR $arton_ok)) {

	debut_boite_info();
	afficher_boite_logo($arton, "LOGO DU MOT-CL&Eacute;".aide ("breveslogo"));
	if (($options == 'avancees' AND $arton_ok) OR $artoff_ok) {
		echo "<P>";
		afficher_boite_logo($artoff, "LOGO POUR SURVOL");
	}
	fin_boite_info();
}

debut_droite();

if ($new == 'oui') {
	echo "<A HREF='mots_edit.php3?redirect=$redirect&redirect_ok=oui&supp_mot=$id_mot' onMouseOver=\"retour.src='IMG2/retour-on.gif'\" onMouseOut=\"retour.src='IMG2/retour-off.gif'\"><img src='IMG2/retour-off.gif' alt='Annuler le nouveau mot-cl&eacute;' width='49' height='46' border='0' name='retour' align='middle'></A>";
}else {
	echo "<A HREF='mots_edit.php3?redirect=$redirect&redirect_ok=oui' onMouseOver=\"retour.src='IMG2/retour-on.gif'\" onMouseOut=\"retour.src='IMG2/retour-off.gif'\"><img src='IMG2/retour-off.gif' alt='Retour' width='49' height='46' border='0' name='retour' align='middle'></A>";
	
}

echo "<FONT SIZE=5 FACE='Verdana,Arial,Helvetica,sans-serif'><B>$titre</B></FONT>";

if (strlen($descriptif)>0 OR strlen($texte)>0){
	debut_boite_info();

	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif'>";
	
	echo "<P><B>$descriptif</B>";
	echo "<P>$texte";
	
	echo "</FONT>";

	fin_boite_info();
}

echo "<P>";

if ($connect_statut == "0minirezo") $aff_articles = "prepa,prop,publie,refuse";
else $aff_articles = "prop,publie";


afficher_rubriques("Les rubriques li&eacute;es &agrave; ce mot-cl&eacute;",
"SELECT rubrique.* FROM spip_rubriques AS rubrique, spip_mots_rubriques AS lien WHERE lien.id_mot='$id_mot' AND lien.id_rubrique=rubrique.id_rubrique ORDER BY rubrique.titre");

afficher_articles("Les articles li&eacute;s &agrave; ce mot-cl&eacute;",
"SELECT article.* FROM spip_articles AS article, spip_mots_articles AS lien WHERE lien.id_mot='$id_mot' AND lien.id_article=article.id_article AND FIND_IN_SET(article.statut,'$aff_articles')>0 ORDER BY article.date DESC");



afficher_breves("Les br&egrave;ves li&eacute;es &agrave; ce mot-cl&eacute;",
"SELECT breves.* FROM spip_breves AS breves, spip_mots_breves AS lien WHERE lien.id_mot='$id_mot' AND lien.id_breve=breves.id_breve ORDER BY breves.date_heure DESC LIMIT 0,10");



afficher_sites("Les sites r&eacute;f&eacute;renc&eacute;s li&eacute;es &agrave; ce mot-cl&eacute;",
"SELECT syndic.* FROM spip_syndic AS syndic, spip_mots_syndic AS lien WHERE lien.id_mot='$id_mot' AND lien.id_syndic=syndic.id_syndic ORDER BY syndic.nom_site DESC LIMIT 0,10");


if ($connect_statut =="0minirezo"){
	echo "<P>";
	debut_cadre_relief();



	$query = "SELECT * FROM spip_mots WHERE id_mot='$id_mot'";
	$result = mysql_query($query);


	while ($row = mysql_fetch_array($result)) {

		$id_mot = $row['id_mot'];
		$titre = $row['titre'];
		$descriptif = $row['descriptif'];
		$texte = $row['texte'];
		$groupe = $row['id_groupe'];

		echo "<FORM ACTION='mots_edit.php3' METHOD='post'>";
		echo "<FONT FACE='Georgia,Garamond,Times,serif' SIZE=3>";
		echo "<INPUT TYPE='Hidden' NAME='id_mot' VALUE=\"$id_mot\">";
		echo "<INPUT TYPE='Hidden' NAME='redirect' VALUE=\"$redirect\">";
		echo "<INPUT TYPE='Hidden' NAME='redirect_ok' VALUE='oui'>";

		$titre = htmlspecialchars($titre);
		$descriptif = htmlspecialchars($descriptif);
		$texte = htmlspecialchars($texte);

		echo "<B>Nom ou titre du mot-cl&eacute;</B> [Obligatoire]";
		echo aide ("mots");

		echo "<BR><INPUT TYPE='text' NAME='titre' CLASS='formo' VALUE=\"$titre\" SIZE='40'><P>";

		echo  "Dans le groupe :</label>\n";
		echo aide ("motsgroupes");
		echo  "<UL><SELECT NAME='id_type'>\n";

		$query_groupes = "SELECT * FROM spip_groupes_mots ORDER BY titre";
		$result = mysql_query($query_groupes);
		while ($row_groupes = mysql_fetch_array($result)){
			$id_groupe = $row_groupes['id_groupe'];
			$titre_groupe = htmlspecialchars($row_groupes['titre']);
			echo  "<OPTION".mySel($id_groupe, $groupe).">$titre_groupe</OPTION>\n";
		}			


		echo  "</SELECT></UL>";

		
	
		echo $texte_types;

		if ($options == 'avancees' OR $descriptif) {
			echo "<B>Descriptif rapide</B><BR>";
			echo "<TEXTAREA NAME='descriptif' CLASS='forml' ROWS='2' COLS='40' wrap=soft>";
			echo $descriptif;
			echo "</TEXTAREA><P>\n";
		}
		else {
			echo "<INPUT TYPE='hidden' NAME='descriptif' VALUE=\"$descriptif\">";
		}

		if ($options == 'avancees' OR $texte) {
			echo "<B>Texte explicatif</B><BR>";
			echo "<TEXTAREA NAME='texte' ROWS='5' CLASS='forml' COLS='40' wrap=soft>";
			echo $texte;
			echo "</TEXTAREA><P>\n";
		}
		else {
			echo "<INPUT TYPE='hidden' NAME='texte' VALUE=\"$texte\">";
		}

		echo "<DIV align='right'><INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
		echo "</FORM>";
	}

fin_cadre_relief();
}


fin_page();

?>
