<?php

include ("inc.php3");

if ($supp_dest) {
	spip_query("DELETE FROM spip_auteurs_messages WHERE id_message=$id_message AND id_auteur=$supp_dest");
}

if ($detruire_message) {
	spip_query("DELETE FROM spip_messages WHERE id_message=$detruire_message");
	spip_query("DELETE FROM spip_auteurs_messages WHERE id_message=$detruire_message");
	spip_query("DELETE FROM spip_forum WHERE id_message=$detruire_message");
}


debut_page("Votre messagerie", "asuivre", "messagerie");
debut_gauche("messagerie");


debut_boite_info();

echo propre("La messagerie vous permet d'&eacute;changer des messages entre r&eacute;dacteurs, de conserver des pense-b&ecirc;tes (pour votre usage personnel) ou d'afficher des annonces sur la page d'accueil de l'espace priv&eacute; (si vous &ecirc;tes administrateur).");

echo "<p>".propre("<IMG SRC='img_pack/m_envoi.gif' WIDTH='14' HEIGHT='7' BORDER='0'> Le symbole {{vert}} indique les {{messages &eacute;chang&eacute;s avec d'autres utilisateurs}} du site.");

echo aide ("messut");

echo "<p>".propre("<IMG SRC='img_pack/m_envoi_bleu.gif' WIDTH='14' HEIGHT='7' BORDER='0'> Le symbole {{bleu}} indique un {{pense-b&ecirc;te}}: c'est-&agrave;-dire un message &agrave; votre usage personnel.");

echo aide ("messpense");

echo "<p>".propre("<IMG SRC='img_pack/m_envoi_jaune.gif' WIDTH='14' HEIGHT='7' BORDER='0'> Le symbole {{jaune}} indique une {{annonces &agrave; tous les r&eacute;dacteurs}}&nbsp;: modifiable par tous les administrateurs, et visible par tous les r&eacute;dacteurs.");


fin_boite_info();

debut_droite("messagerie");


function afficher_messages($titre_table, $query_message, $afficher_auteurs = true, $important = false, $boite_importante = true, $obligatoire = false) {
	global $messages_vus;
	global $connect_id_auteur;
	global $couleur_claire;

	// Interdire l'affichage de message en double
	if ($messages_vus) {
		$query_message .= ' AND messages.id_message NOT IN ('.join(',', $messages_vus).')';
	}

	$query_message .= ' ORDER BY date_heure DESC';
	$tranches = afficher_tranches_requete($query_message, 3);

	if ($tranches OR $obligatoire) {
		if ($important) debut_cadre_relief();

		echo "<P><TABLE WIDTH=100% CELLPADDING=0 CELLSPACING=0 BORDER=0><TR><TD WIDTH=100% BACKGROUND=''>";
		echo "<TABLE WIDTH=100% CELLPADDING=3 CELLSPACING=0 BORDER=0>";

		bandeau_titre_boite($titre_table, $afficher_auteurs, $boite_importante);

		echo $tranches;

		$result_message = spip_query($query_message);
		$num_rows = mysql_num_rows($result_message);

		while($row = mysql_fetch_array($result_message)) {
			$vals = '';

			$id_message = $row['id_message'];
			$date = $row["date_heure"];
			$titre = $row["titre"];
			$type = $row["type"];
			$statut = $row["statut"];
			$page = $row["page"];
			$rv = $row["rv"];
			$vu = $row["vu"];
			$messages_vus[$id_message] = $id_message;

			//
			// Titre
			//

			$s = "<A HREF='message.php3?id_message=$id_message'>";

			switch ($type) {
			case 'pb' :
				$puce = 'm_envoi_bleu.gif';
				break;
			case 'memo' :
				$puce = 'm_envoi_jaune.gif';
				break;
			case 'affich' :
				$puce = 'm_envoi_jaune.gif';
				break;
			case 'normal':
			default:
				$puce = 'm_envoi.gif';
				break;
			}
				
			$s .= "<img src='img_pack/$puce' width='14' height='7' border='0'>";
			$s .= "&nbsp;&nbsp;".typo($titre)."</A>";
			$vals[] = $s;

			//
			// Auteurs

			if ($afficher_auteurs) {
				$query_auteurs = "SELECT auteurs.nom FROM spip_auteurs AS auteurs, spip_auteurs_messages AS lien WHERE lien.id_message=$id_message AND lien.id_auteur!=$connect_id_auteur AND lien.id_auteur=auteurs.id_auteur";
				$result_auteurs = spip_query($query_auteurs);
				$auteurs = '';
				while ($row_auteurs = mysql_fetch_array($result_auteurs)) {
					$auteurs[] = typo($row_auteurs['nom']);
				}

				if ($auteurs AND $type == 'normal') {
					$s = "<FONT FACE='Arial,Helvetica,sans-serif' SIZE=1>";
					$s .= join(', ', $auteurs);
					$s .= "</FONT>";
				}
				else $s = "&nbsp;";
				$vals[] = $s;
			}
			
			//
			// Date
			//

			$s = affdate($date);
			$vals[] = $s;

			$table[] = $vals;
		}

		if ($afficher_auteurs) {
			$largeurs = array('', 130, 90);
			$styles = array('arial2', 'arial1', 'arial1');
		}
		else {
			$largeurs = array('', 90);
			$styles = array('arial2', 'arial1');
		}
		afficher_liste($largeurs, $table, $styles);

		echo "</TABLE></TD></TR></TABLE>";
		mysql_free_result($result_message);
		if ($important) fin_cadre_relief();
	}
}




$messages_vus = '';

$query_message = "SELECT * FROM spip_messages AS messages, spip_auteurs_messages AS lien ".
	"WHERE lien.id_auteur=$connect_id_auteur AND rv='oui' AND date_heure > DATE_SUB(NOW(), INTERVAL 1 DAY) ".
	"AND statut='publie' AND lien.id_message=messages.id_message";
afficher_messages("Vos rendez-vous &agrave; venir", $query_message, true, true);

$query_message = "SELECT * FROM spip_messages AS messages WHERE id_auteur=$connect_id_auteur AND statut='publie' AND type='pb' AND (date_heure > DATE_SUB(NOW(), INTERVAL 1 DAY) OR rv != 'oui')";
afficher_messages("Vos pense-b&ecirc;te", $query_message, false, true);

$query_message = "SELECT * FROM spip_messages AS messages, spip_auteurs_messages AS lien ".
	"WHERE lien.id_auteur=$connect_id_auteur AND vu='non' ".
	"AND statut='publie' AND lien.id_message=messages.id_message";
afficher_messages("Nouveaux messages", $query_message, true, true);

$query_message = "SELECT * FROM spip_messages AS messages, spip_auteurs_messages AS lien ".
	"WHERE lien.id_auteur=$connect_id_auteur AND statut='publie' AND type='normal' AND lien.id_message=messages.id_message";
afficher_messages("Discussions en cours", $query_message, true, false);

$query_message = "SELECT * FROM spip_messages AS messages WHERE id_auteur=$connect_id_auteur AND statut='redac'";
afficher_messages("Vos messages en cours de r&eacute;daction", $query_message, true, false, false);

$query_message = "SELECT * FROM spip_messages AS messages WHERE id_auteur=$connect_id_auteur AND statut='publie' AND type='pb'";
afficher_messages("Vos anciens pense-b&ecirc;te", $query_message, false, false, false);

if ($connect_statut == '0minirezo') {
	$query_message = "SELECT * FROM spip_messages AS messages WHERE statut='publie' AND type='affich'";
	afficher_messages("Annonces &agrave; tous les r&eacute;dacteurs <font size=1>&nbsp;&nbsp;&nbsp;<a href='message_edit.php3?new=oui&type=affich'><img src='img_pack/m_envoi_jaune.gif' width='14' height='7' border='0'> Ajouter</a></font>", $query_message, false, false, false, true);
}
else {
	$query_message = "SELECT * FROM spip_messages AS messages WHERE statut='publie' AND type='affich'";
	afficher_messages("Annonces &agrave; tous les r&eacute;dacteurs", $query_message, false, false, false, true);
}

fin_page();

?>