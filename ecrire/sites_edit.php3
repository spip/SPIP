<?php

include ("inc.php3");

$proposer_sites = lire_meta("proposer_sites");

function mySel($varaut,$variable) {
	$retour= " VALUE=\"$varaut\"";

	if ($variable==$varaut){
		$retour.= " SELECTED";
	}

	return $retour;
}

function premiere_rubrique(){
 	$query="SELECT * FROM spip_rubriques WHERE id_parent='0' ORDER BY titre LIMIT 0,1";
 	$result=spip_query($query);

	while($row=mysql_fetch_array($result)){
		$my_rubrique=$row['id_rubrique'];
	}
	return $my_rubrique;

}

function enfant($leparent){
	global $id_parent;
	global $id_rubrique;
	global $i;
	global $statut;
	global $connect_toutes_rubriques;
	global $connect_id_rubriques;
	global $couleur_claire;
	
	$i++;
 	$query="SELECT * FROM spip_rubriques WHERE id_parent='$leparent' ORDER BY titre";
 	$result=spip_query($query);

	while($row=mysql_fetch_array($result)){
		$my_rubrique=$row['id_rubrique'];
		$titre=typo($row['titre']);
		$statut_rubrique=$row['statut'];
		$style = "";

		// si l'article est publie il faut etre admin pour avoir le menu
		// sinon le menu est present en entier (proposer un article)
		if ($statut != "publie" OR acces_rubrique($my_rubrique)) {
			$rubrique_acceptable = true;
		} else {
			$rubrique_acceptable = false;
		}

		$espace="";
		for ($count=1;$count<$i;$count++){
			$espace.="&nbsp;&nbsp;&nbsp; ";
		}
		if ($i > 3) $style .= "color: #666666;";
		if ($i > 4) $style .= "font-style: italic;";
		if ($i < 3) $style .= "font-weight:bold; ";
		if ($i==1) {
			$espace= "";
			$style .= "background-color: $couleur_claire;";
		}
		if ($statut_rubrique!='publie') $titre = "($titre)";

		if ($rubrique_acceptable) {
			echo "<OPTION".mySel($my_rubrique,$id_rubrique)." style=\"$style\">$espace$titre\n";
		}
		enfant($my_rubrique);
	}
	$i=$i-1;
}


$proposer_sites = lire_meta("proposer_sites");

$query = "SELECT * FROM spip_syndic WHERE id_syndic='$id_syndic'";
$result = spip_query($query);
if ($row = mysql_fetch_array($result)) {
	$id_syndic = $row["id_syndic"];
	$id_rubrique = $row["id_rubrique"];
	$nom_site = stripslashes($row["nom_site"]);
	$url_site = stripslashes($row["url_site"]);
	$url_syndic = stripslashes($row["url_syndic"]);
	$descriptif = stripslashes($row["descriptif"]);
	$syndication = $row["syndication"];
}
else {
	$syndication = 'non';
	$new = 'oui';
}
if (!$id_rubrique > 0) $id_rubrique = premiere_rubrique();



debut_page("Site r&eacute;f&eacute;renc&eacute;", "documents", "sites");


debut_grand_cadre();

afficher_parents($id_rubrique);
$parents="~ <img src='img_pack/racine-site-24.gif' width=24 height=24 align='middle'> <A HREF='naviguer.php3?coll=0'><B>RACINE DU SITE</B></A> ".aide ("rubhier")."<BR>".$parents;

$parents=ereg_replace("~","&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$parents);
$parents=ereg_replace("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ","",$parents);

echo "$parents";

fin_grand_cadre();


debut_gauche();
debut_droite();
debut_cadre_formulaire();


echo "\n<table cellpadding=0 cellspacing=0 border=0 width='100%'>";
echo "<tr width='100%'>";

if ($new != 'oui') {
	echo "<td>";
	icone("Retour", "sites.php3?id_syndic=$id_syndic", 'site-24.gif', "rien.gif");
	echo "</td>";
	echo "<td><img src='img_pack/rien.gif' width=10></td>\n";
}
echo "<td width='100%'>";
echo "R&eacute;f&eacute;rencer le site :";
gros_titre($nom_site);
echo "</td></tr></table>";
echo "<p>";



if ($new == 'oui'){

	$proposer_sites = lire_meta("proposer_sites");
	if ($connect_statut == '0minirezo' OR $proposer_sites > 0) {
		debut_cadre_relief("site-24.gif");
		
		$link = new Link('sites.php3');
		$link->addVar('id_rubrique', $id_rubrique);
		$link->addVar('new', 'oui');
		$link->addVar('redirect', $this_link->getUrl());
		$link->addVar('analyser_site', 'oui');
		echo $link->getForm();
		
		echo "<font face='verdana,arial,helvetica, sans-serif' size=2><b>R&eacute;f&eacute;rencement automatis&eacute; d'un site</b><br>Vous pouvez r&eacute;f&eacute;rencer rapidement un site Web en indiquant ci-dessous l'adresse URL d&eacute;sir&eacute;e, ou l'adresse de son fichier backend. SPIP va r&eacute;cup&eacute;rer automatiquement les informations concernant ce site (titre, description...).</font>";
		echo "<div align='right'><input type=\"text\" name=\"url\" class='fondl' value=\"http://\">";
		echo "<input type=\"submit\" name=\"submit\" value=\"Ajouter\" class='fondo'>";
		
		fin_cadre_relief();
		echo "</form>";
		
		echo "<p><b>Vous pouvez pr&eacute;f&eacute;rer ne pas utiliser cette fonction automatique, et indiquer vous-m&ecirc;me les &eacute;l&eacute;ments concernant ce site...</b>";
		$cadre_ouvert = true;
		debut_cadre_enfonce("site-24.gif");
		
	}

}


$link = new Link($target);
$link->addVar('new');
$link->addVar('modifier_site', 'oui');
$link->addVar('syndication_old', $syndication);
echo $link->getForm('POST');

$nom_site = htmlspecialchars($nom_site);
$url_site = htmlspecialchars($url_site);
$url_syndic = htmlspecialchars($url_syndic);

echo "<b>Nom du site</b> [Obligatoire]<br>";
echo "<input type='text' class='formo' name='nom_site' value=\"$nom_site\" size='40'><p>";
if (strlen($url_site)<8) $url_site="http://";
echo "<b>Adresse du site</b> [Obligatoire]<br>";
echo "<input type='text' class='formo' name='url_site' value=\"$url_site\" size='40'><p>";



	if ($id_rubrique == 0) $logo_parent = "racine-site-24.gif";
	else {
		$query = "SELECT id_parent FROM spip_rubriques WHERE id_rubrique='$id_rubrique'";
		$result=spip_query($query);
		while($row=mysql_fetch_array($result)){
			$parent_parent=$row['id_parent'];
		}
		if ($parent_parent == 0) $logo_parent = "secteur-24.gif";
		else $logo_parent = "rubrique-24.gif";
	}

	debut_cadre_relief("$logo_parent");
	echo "<b>&Agrave; l'int&eacute;rieur de la rubrique&nbsp;:</b><br>\n";
	echo "<select name='id_rubrique' style='background-color:#ffffff; font-size:10px; width:100%; font-face:verdana,arial,helvetica,sans-serif;' size=1>\n";
	enfant(0);
	echo "</select><p>\n";
	fin_cadre_relief();

echo "<b>Description du site</b><br>";
echo "<textarea name='descriptif' rows='8' class='forml' cols='40' wrap=soft>";
echo $descriptif;
echo "</textarea>\n";

$activer_syndic = lire_meta("activer_syndic");

echo "<input type='hidden' name='syndication_old' value=\"$syndication\">";

if ($activer_syndic != "non") {
	debut_cadre_enfonce();
	if ($syndication == "non") {
		echo "<INPUT TYPE='radio' NAME='syndication' VALUE='non' id='syndication_non' CHECKED>";
	}
	else {
		echo "<INPUT TYPE='radio' NAME='syndication' VALUE='non' id='syndication_non'>";
	}
	echo " <b><label for='syndication_non'>Pas de syndication</label></b><p>";

	if ($syndication == "non") {
		echo "<INPUT TYPE='radio' NAME='syndication' VALUE='oui' id='syndication_oui'>";
	}
	else {
		echo "<INPUT TYPE='radio' NAME='syndication' VALUE='oui' id='syndication_oui' CHECKED>";
	}
	echo " <b><label for='syndication_oui'>Syndication :</label></b>";
	echo aide("rubsyn");


	echo "<table cellpadding=0 cellspacing=0 border=0 width=100%><tr><td width=50>&nbsp;</td><td>";

	if (strlen($url_syndic) < 8) $url_syndic = "http://";
	echo "Adresse du fichier &laquo;&nbsp;backend&nbsp;&raquo; pour la syndication&nbsp;:";
	echo "<br>";
	echo "<INPUT TYPE='text' CLASS='formo' NAME='url_syndic' VALUE=\"$url_syndic\" SIZE='40'><P>";
	echo "<INPUT TYPE='hidden' NAME='old_syndic' VALUE=\"$url_syndic\"";
	echo "</td></tr></table>";

	fin_cadre_enfonce();
} 
else {
	echo "<INPUT TYPE='Hidden' NAME='syndication' VALUE=\"$syndication\">";
	echo "<INPUT TYPE='hidden' NAME='url_syndic' VALUE=\"$url_syndic\"";
}

echo "<div ALIGN='right'><INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'></div>";
echo "</FORM>";

if ($cadre_ouvert) fin_cadre_enfonce();

fin_cadre_formulaire();

fin_page();

?>