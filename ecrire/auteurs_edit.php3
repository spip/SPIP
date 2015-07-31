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


debut_droite();

function mySel($varaut,$variable) {
	$retour = " VALUE=\"$varaut\"";
	if ($variable==$varaut){
		$retour.= " SELECTED";
	}
	return $retour;
}

	debut_cadre_relief("redacteurs-24.gif");
	
	
	echo "<table width='100%' cellpadding='0' border='0' cellspacing='0'>";
	
	echo "<tr>";

	echo "<td valign='top' width='100%'>";	


	gros_titre($nom);

	echo "<div>&nbsp;</div>";

	if (strlen($email) > 2) echo "<div>"._T('email_2')." <B><A HREF='mailto:$email'>$email</A></B></div>";
	if (strlen($nom_site_auteur) > 2) echo "<div>"._T('info_site_2')." <B><A HREF='$url_site'>$nom_site_auteur</A></B></div>";

		
	echo "</td>";
	
	echo "<td>";
	
	if (($connect_statut == "0minirezo") OR $connect_id_auteur == $id_auteur) {
		icone (_T("admin_modifier_auteur"), "auteurs_infos.php3?id_auteur=$id_auteur", "redacteurs-24.gif", "edit.gif");
	}
	echo "</td></tr></table>";

	if (strlen($bio) > 0) { echo "<div>".propre("<quote>".$bio."</quote>")."</div>"; }
	if (strlen($pgp) > 0) { echo "<div>".propre("PGP:<cadre>".$pgp."</cadre>")."</div>"; }

	if ($champs_extra AND $extra) {
		include_ecrire("inc_extra.php3");
		extra_affichage($extra, "auteurs");
	}


	fin_cadre_relief();


echo "<P>";
if ($connect_statut == "0minirezo") $aff_art = "'prepa','prop','publie','refuse'";
else if ($connect_id_auteur == $id_auteur) $aff_art = "'prepa','prop','publie'";
else $aff_art = "'prop','publie'";

afficher_articles(_T('info_articles_auteur'),
	", spip_auteurs_articles AS lien WHERE lien.id_auteur='$id_auteur' ".
	"AND lien.id_article=articles.id_article AND articles.statut IN ($aff_art) ".
	"ORDER BY articles.date DESC", true);
}



$query_message = "SELECT * FROM spip_messages AS messages, spip_auteurs_messages AS lien, spip_auteurs_messages AS lien2 ".
	"WHERE lien.id_auteur=$connect_id_auteur AND lien2.id_auteur = $id_auteur AND statut='publie' AND type='normal' AND rv!='oui' AND lien.id_message=messages.id_message AND lien2.id_message=messages.id_message";
afficher_messages(_T('info_discussion_cours'), $query_message, false, false);

$query_message = "SELECT * FROM spip_messages AS messages, spip_auteurs_messages AS lien, spip_auteurs_messages AS lien2 ".
	"WHERE lien.id_auteur=$connect_id_auteur AND lien2.id_auteur = $id_auteur AND statut='publie' AND type='normal' AND rv='oui' AND lien.id_message=messages.id_message AND lien2.id_message=messages.id_message  AND messages.date_heure > DATE_SUB(NOW(), INTERVAL 1 DAY)";
afficher_messages(_T('info_vos_rendez_vous'), $query_message, false, false);



fin_page();

?>
