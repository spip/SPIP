<?php

include ("inc.php3");
include_ecrire ("inc_acces.php3");
include_ecrire ("inc_index.php3");
include_ecrire ("inc_logos.php3");

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

	while($row=spip_fetch_array($result)){
		$my_rubrique=$row["id_rubrique"];
		$titre=typo($row["titre"]);
	
		if (!ereg(",$my_rubrique,","$toutes_rubriques")){
			$espace="";
			for ($count=0;$count<$i;$count++){$espace.="&nbsp;&nbsp;";}
			$espace .= "|";
			if ($i==1)
				$espace = "*";

			echo "<OPTION VALUE='$my_rubrique'>$espace ".supprimer_tags($titre)."\n";
			afficher_auteur_rubriques($my_rubrique);
		}
	}
	$i=$i-1;
}


$query = "SELECT * FROM spip_auteurs WHERE id_auteur='$id_auteur'";
$result = spip_query($query);


if ($row = spip_fetch_array($result)) {
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
	$extra = $row["extra"];
	$low_sec = $row["low_sec"];


if ($connect_id_auteur == $id_auteur) debut_page($nom, "asuivre", "perso");
else debut_page($nom,"documents","redacteurs");


echo "<br><br><br>";
gros_titre($nom);

if (($connect_statut == "0minirezo") OR $connect_id_auteur == $id_auteur) {
	$statut_auteur=$statut;
	barre_onglets("auteur", "auteur");
}


debut_gauche();



debut_boite_info();

echo "<CENTER>";

echo "<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=1><B>"._T('info_gauche_numero_auteur')."&nbsp;:</B></FONT>";
echo "<BR><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=6><B>$id_auteur</B></FONT>";
echo "</CENTER>";

fin_boite_info();




//////////////////////////////////////////////////////
// Logos de l'auteur
//

$arton = "auton$id_auteur";
$artoff = "autoff$id_auteur";

if ($id_auteur>0 AND (($connect_statut == '0minirezo') OR ($connect_id_auteur == $id_auteur)))
	afficher_boite_logo($arton, $artoff, _T('logo_auteur').aide ("logoart"), _T('logo_survol'));

// raccourcis
if ($connect_id_auteur == $id_auteur) {
	debut_raccourcis();
	icone_horizontale(_T('icone_tous_auteur'), "auteurs.php3", "redacteurs-24.gif","rien.gif");
	fin_raccourcis();
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


if (strlen($email) > 2 OR strlen($bio) > 0 OR strlen($nom_site_auteur) > 0 OR ($champs_extra AND $extra)) {
	debut_cadre_relief("$logo");
	echo "<FONT FACE='Verdana,Arial,Sans,sans-serif'>";
	if (strlen($email) > 2) echo _T('email_2')." <B><A HREF='mailto:$email'>$email</A></B><BR> ";
	if (strlen($nom_site_auteur) > 2) echo _T('info_site_2')." <B><A HREF='$url_site'>$nom_site_auteur</A></B>";
	echo "<P>".propre($bio)."</P>";

	if ($champs_extra AND $extra) {
		include_ecrire("inc_extra.php3");
		extra_affichage($extra, "auteurs");
	}

	echo "</FONT>";
	fin_cadre_relief();
}


echo "<P>";
if ($connect_statut == "0minirezo") $aff_art = "'prepa','prop','publie','refuse'";
else if ($connect_id_auteur == $id_auteur) $aff_art = "'prepa','prop','publie'";
else $aff_art = "'prop','publie'";

afficher_articles(_T('info_articles_auteur'),
	", spip_auteurs_articles AS lien WHERE lien.id_auteur='$id_auteur' ".
	"AND lien.id_article=articles.id_article AND articles.statut IN ($aff_art) ".
	"ORDER BY articles.date DESC");
}


fin_page();

?>
