<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


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


if (!$id_auteur = intval($id_auteur)) {
	die ('erreur');
}

$query = "SELECT * FROM spip_auteurs WHERE id_auteur=$id_auteur";
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


// Appliquer des modifications de statut
modifier_statut_auteur($row);


if ($connect_id_auteur == $id_auteur) debut_page($nom, "auteurs", "perso");
else debut_page($nom,"auteurs","redacteurs");


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

if ($id_auteur
AND (($connect_statut == '0minirezo')
OR ($connect_id_auteur == $id_auteur)))
	afficher_boite_logo('aut', 'id_auteur', $id_auteur,
	_T('logo_auteur').aide ("logoart"), _T('logo_survol'));


debut_droite();

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
		icone (_T("admin_modifier_auteur"), "auteur_infos.php3?id_auteur=$id_auteur", "redacteurs-24.gif", "edit.gif");
	}
	echo "</td></tr></table>";

	if (strlen($bio) > 0) { echo "<div>".propre("<quote>".$bio."</quote>")."</div>"; }
	if (strlen($pgp) > 0) { echo "<div>".propre("PGP:<cadre>".$pgp."</cadre>")."</div>"; }

	if ($champs_extra AND $extra) {
		include_ecrire("inc_extra.php3");
		extra_affichage($extra, "auteurs");
	}

	// Afficher le formulaire de changement de statut (cf. inc_acces.php3)
	if ($options == 'avancees')
		afficher_formulaire_statut_auteur ($id_auteur,
			"auteurs_edit.php3?id_auteur=$id_auteur");

	fin_cadre_relief();


echo "<div>&nbsp;</div>";
if ($connect_statut == "0minirezo") $aff_art = "'prepa','prop','publie','refuse'";
else if ($connect_id_auteur == $id_auteur) $aff_art = "'prepa','prop','publie'";
else $aff_art = "'prop','publie'";

afficher_articles(_T('info_articles_auteur'),
	", spip_auteurs_articles AS lien WHERE lien.id_auteur='$id_auteur' ".
	"AND lien.id_article=articles.id_article AND articles.statut IN ($aff_art) ".
	"ORDER BY articles.date DESC", true);
}


if ($id_auteur != $connect_id_auteur
AND ($statut == '0minirezo' OR $statut == '1comite')
) {
	echo "<div>&nbsp;</div>";
	debut_cadre_couleur();
	
	$query_message = "SELECT * FROM spip_messages AS messages, spip_auteurs_messages AS lien, spip_auteurs_messages AS lien2 ".
		"WHERE lien.id_auteur=$connect_id_auteur AND lien2.id_auteur = $id_auteur AND statut='publie' AND type='normal' AND rv!='oui' AND lien.id_message=messages.id_message AND lien2.id_message=messages.id_message";
	afficher_messages(_T('info_discussion_cours'), $query_message, false, false);
	
	$query_message = "SELECT * FROM spip_messages AS messages, spip_auteurs_messages AS lien, spip_auteurs_messages AS lien2 ".
		"WHERE lien.id_auteur=$connect_id_auteur AND lien2.id_auteur = $id_auteur AND statut='publie' AND type='normal' AND rv='oui' AND date_fin > NOW() AND lien.id_message=messages.id_message AND lien2.id_message=messages.id_message";
	afficher_messages(_T('info_vos_rendez_vous'), $query_message, false, false);
	
	icone_horizontale(_T('info_envoyer_message_prive'),
		"message_edit.php3?new=oui&type=normal&dest=$id_auteur", "message.gif");
	fin_cadre_couleur();
}

fin_page();

?>
