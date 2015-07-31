<?php

include ("inc.php3");
include_local ("inc_acces.php3");
include_local ("inc_index.php3");
include_local ("inc_logos.php3");


function supp_auteur($id_auteur) {
	$query="UPDATE spip_auteurs SET statut='5poubelle' WHERE id_auteur=$id_auteur";
	$result=spip_query($query);
}


function afficher_auteur_rubriques($leparent){
	global $id_parent;
	global $id_rubrique;
	global $toutes_rubriques;
	global $i;
	
	$i++;
 	$query="SELECT * FROM spip_rubriques WHERE id_parent='$leparent' ORDER BY titre";
 	$result=spip_query($query);

	while($row=mysql_fetch_array($result)){
		$my_rubrique=$row["id_rubrique"];
		$titre=typo($row["titre"]);
	
		if (!ereg(",$my_rubrique,","$toutes_rubriques")){
			$espace="";
			for ($count=0;$count<$i;$count++){$espace.="&nbsp;&nbsp;";}
			$espace .= "|";
			if ($i==1)
				$espace = "*";

			echo "<OPTION VALUE='$my_rubrique'>$espace $titre\n";
			afficher_auteur_rubriques($my_rubrique);
		}
	}
	$i=$i-1;
}


$query = "SELECT * FROM spip_auteurs WHERE id_auteur='$id_auteur'";
$result = spip_query($query);


if ($row = mysql_fetch_array($result)) {
	$id_auteur=$row['id_auteur'];
	$nom=$row['nom'];
	$bio=$row['bio'];
	$email=$row['email'];
	$nom_site_auteur=$row['nom_site'];
	$url_site=$row['url_site'];
	$login=$row['login'];
	$pass=$row['pass'];
	$statut=$row['statut'];
	$pgp=$row["pgp"];
	$messagerie=$row["messagerie"];
	$imessage=$row["imessage"];


if ($connect_id_auteur == $id_auteur) debut_page($nom_auteur, "redacteurs", "perso");
else if (ereg("5poubelle",$statut)) debut_page("$nom_auteur","redacteurs","redac-poubelle");
else if (ereg("0minirezo",$statut)) debut_page("$nom_auteur","redacteurs","administrateurs");
else debut_page("$nom_auteur","redacteurs","redacteurs");



echo "<br><br><br>";
gros_titre("$nom");

if (($connect_statut == "0minirezo") OR $connect_id_auteur == $id_auteur) {
	barre_onglets("auteur", "auteur");
}


debut_gauche();



debut_boite_info();

echo "<CENTER>";

echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=1><B>AUTEUR NUM&Eacute;RO&nbsp;:</B></FONT>";
echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=6><B>$id_auteur</B></FONT>";
echo "</CENTER>";

fin_boite_info();




//////////////////////////////////////////////////////
// Logos de l'auteur
//

$arton = "auton$id_auteur";
$artoff = "autoff$id_auteur";
$arton_ok = get_image($arton);
if ($arton_ok) $artoff_ok = get_image($artoff);

if ($connect_statut == '0minirezo' AND ($options == 'avancees' OR $arton_ok)) {

	debut_boite_info();
	afficher_boite_logo($arton, "LOGO DE L'AUTEUR".aide ("logoart"));
	if (($options == 'avancees' AND $arton_ok) OR $artoff_ok) {
		echo "<P>";
		afficher_boite_logo($artoff, "LOGO POUR SURVOL");
	}
	fin_boite_info();
}


debut_droite();

function mySel($varaut,$variable) {
	$retour = " VALUE=\"$varaut\"";
	if ($variable==$varaut){
		$retour.= " SELECTED";
	}
	return $retour;
}

if ($statut == "0minirezo") $logo = "redacteurs-admin-24.gif";
else if ($statut == "5poubelle") $logo = "redacteurs-poubelle-24.gif";
else $logo = "redacteurs-24.gif";


	if (strlen($email) > 2 OR strlen($bio) > 0 OR strlen($nom_site_auteur) > 0) {
		debut_cadre_relief("$logo");
		echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif'>";
		if (strlen($email) > 2) echo "email : <B><A HREF='mailto:$email'>$email</A></B><BR> ";
		if (strlen($nom_site_auteur) > 2) echo "site : <B><A HREF='$url_site'>$nom_site_auteur</A></B>";
		echo "<P>".propre($bio);
		echo "</FONT>";
		fin_cadre_relief();
	}


	echo "<P>";
	if ($connect_statut == "0minirezo") $aff_art = "prepa,prop,publie,refuse";
	else if($connect_id_auteur == $id_auteur) $aff_art = "prepa,prop,publie";
	else $aff_art = "prop,publie";
	
	afficher_articles("Les articles de cet auteur",
	"SELECT article.id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, id_rubrique, statut ".
	"FROM spip_articles AS article, spip_auteurs_articles AS lien WHERE lien.id_auteur='$id_auteur' AND lien.id_article=article.id_article AND FIND_IN_SET(article.statut,'$aff_art')>0 ORDER BY article.date DESC");


}


fin_page();

?>