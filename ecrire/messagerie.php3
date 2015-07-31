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


debut_page(_T('titre_page_messagerie'), "asuivre", "calendrier");

barre_onglets("calendrier", "messagerie");


debut_gauche("messagerie");


debut_boite_info();

echo _T('info_gauche_messagerie');

echo "<p>"."<IMG SRC='img_pack/m_envoi$spip_lang_rtl.gif' WIDTH='14' HEIGHT='7' BORDER='0'> "._T('info_symbole_vert');

echo aide ("messut");

echo "<p>"."<IMG SRC='img_pack/m_envoi_bleu$spip_lang_rtl.gif' WIDTH='14' HEIGHT='7' BORDER='0'> "._T('info_symbole_bleu');

echo aide ("messpense");

echo "<p>"."<IMG SRC='img_pack/m_envoi_jaune$spip_lang_rtl.gif' WIDTH='14' HEIGHT='7' BORDER='0'> "._T('info_symbole_jaune');


fin_boite_info();

debut_droite("messagerie");


function afficher_messages($titre_table, $query_message, $afficher_auteurs = true, $important = false, $boite_importante = true, $obligatoire = false) {
	global $messages_vus;
	global $connect_id_auteur;
	global $couleur_claire;
	global $spip_lang_rtl;

	// Interdire l'affichage de message en double
	if ($messages_vus) {
		$query_message .= ' AND messages.id_message NOT IN ('.join(',', $messages_vus).')';
	}


	if ($afficher_auteurs) $cols = 3;
	else $cols = 2;
	$query_message .= ' ORDER BY date_heure DESC';
	$tranches = afficher_tranches_requete($query_message, $cols);

	if ($tranches OR $obligatoire) {
		if ($important) debut_cadre_relief();

		echo "<TABLE WIDTH=100% CELLPADDING=0 CELLSPACING=0 BORDER=0><TR><TD WIDTH=100% BACKGROUND=''>";
		echo "<TABLE WIDTH=100% CELLPADDING=3 CELLSPACING=0 BORDER=0>";

		bandeau_titre_boite($titre_table, $afficher_auteurs, $boite_importante);

		echo $tranches;

		$result_message = spip_query($query_message);
		$num_rows = spip_num_rows($result_message);

		while($row = spip_fetch_array($result_message)) {
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
				$puce = "m_envoi_bleu$spip_lang_rtl.gif";
				break;
			case 'memo' :
				$puce = "m_envoi_jaune$spip_lang_rtl.gif";
				break;
			case 'affich' :
				$puce = "m_envoi_jaune$spip_lang_rtl.gif";
				break;
			case 'normal':
			default:
				$puce = "m_envoi$spip_lang_rtl.gif";
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
				while ($row_auteurs = spip_fetch_array($result_auteurs)) {
					$auteurs[] = typo($row_auteurs['nom']);
				}

				if ($auteurs AND $type == 'normal') {
					$s = "<FONT FACE='Arial,Sans,sans-serif' SIZE=1>";
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
			if ($rv == 'oui') {
				$jour=journum($date);
				$mois=mois($date);
				$annee=annee($date);

				$s = "<a href='calendrier_jour.php3?jour=$jour&mois=$mois&annee=$annee'>$s</a>";
			} else {
				$s = "<font color='#999999'>$s</font>";
			}
			
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
		spip_free_result($result_message);
		if ($important) fin_cadre_relief();
	}
}




$messages_vus = '';

$query_message = "SELECT messages.* FROM spip_messages AS messages, spip_auteurs_messages AS lien ".
	"WHERE ((lien.id_auteur=$connect_id_auteur AND lien.id_message=messages.id_message)) AND messages.rv='oui' AND messages.date_heure > DATE_SUB(NOW(), INTERVAL 1 DAY) ".
	"AND messages.statut='publie'";
afficher_messages(_T('info_vos_rendez_vous'), $query_message, true, true);

$query_message = "SELECT * FROM spip_messages AS messages WHERE statut='publie' AND rv='oui' AND type='affich'";
afficher_messages(_T('info_tous_redacteurs'), $query_message, false, true, false);


$query_message = "SELECT * FROM spip_messages AS messages WHERE id_auteur=$connect_id_auteur AND statut='publie' AND type='pb' AND (date_heure > DATE_SUB(NOW(), INTERVAL 1 DAY) OR rv != 'oui')";
afficher_messages(_T('infos_vos_pense_bete'), $query_message, false, true);

$query_message = "SELECT * FROM spip_messages AS messages, spip_auteurs_messages AS lien ".
	"WHERE lien.id_auteur=$connect_id_auteur AND vu='non' ".
	"AND statut='publie' AND lien.id_message=messages.id_message";
afficher_messages(_T('info_nouveaux_message'), $query_message, true, true);

$query_message = "SELECT * FROM spip_messages AS messages, spip_auteurs_messages AS lien ".
	"WHERE lien.id_auteur=$connect_id_auteur AND statut='publie' AND type='normal' AND lien.id_message=messages.id_message";
afficher_messages(_T('info_discussion_cours'), $query_message, true, false);

$query_message = "SELECT * FROM spip_messages AS messages WHERE id_auteur=$connect_id_auteur AND statut='redac'";
afficher_messages(_T('info_message_en_redaction'), $query_message, true, false, false);

$query_message = "SELECT * FROM spip_messages AS messages WHERE id_auteur=$connect_id_auteur AND statut='publie' AND type='pb'";
afficher_messages(_T('info_pense_bete_ancien'), $query_message, false, false, false);

$query_message = "SELECT * FROM spip_messages AS messages WHERE statut='publie' AND type='affich'";
afficher_messages(_T('info_tous_redacteurs'), $query_message, false, false, false);

fin_page();

?>
