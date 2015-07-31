<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

function afficher_messages($titre_table, $query_message, &$messages_vus, $afficher_auteurs = true, $important = false, $boite_importante = true, $obligatoire = false) {
	global $connect_id_auteur, $couleur_foncee, $spip_lang_rtl, $spip_lang_left;

	// Interdire l'affichage de message en double
	if ($messages_vus) {
		$query_message .= ' AND messages.id_message NOT IN ('.join(',', $messages_vus).')';
	}


	if ($afficher_auteurs) $cols = 4;
	else $cols = 2;
	$query_message .= ' ORDER BY date_heure DESC';
	$tranches = afficher_tranches_requete($query_message, $cols);

	if ($tranches OR $obligatoire) {
		if ($important) debut_cadre_couleur();

		echo "<div style='height: 12px;'></div>";
		echo "<div class='liste'>";
	//	bandeau_titre_boite($titre_table, $afficher_auteurs, $boite_importante);
		bandeau_titre_boite2($titre_table, "messagerie-24.gif", $couleur_foncee, "white");
		echo "<TABLE WIDTH='100%' CELLPADDING='2' CELLSPACING='0' BORDER='0'>";


		echo $tranches;

		$result_message = spip_query($query_message);
		$num_rows = spip_num_rows($result_message);

		while($row = spip_fetch_array($result_message)) {
			$vals = '';

			$id_message = $row['id_message'];
			$date = $row["date_heure"];
			$date_fin = $row["date_fin"];
			$titre = sinon($row['titre'], _T('ecrire:info_sans_titre'));
			$type = $row["type"];
			$statut = $row["statut"];
			$page = $row["page"];
			$rv = $row["rv"];
			$vu = $row["vu"];
			$messages_vus[$id_message] = $id_message;

			//
			// Titre
			//

			$s = "<a href='" . generer_url_ecrire("message","id_message=$id_message") . "' style='display: block;'>";

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
				
			$s .= http_img_pack("$puce", "", "width='14' height='7' border='0'");
			$s .= "&nbsp;&nbsp;".typo($titre)."</A>";
			$vals[] = $s;

			//
			// Auteurs

			if ($afficher_auteurs) {
				$query_auteurs = "SELECT auteurs.id_auteur, auteurs.nom FROM spip_auteurs AS auteurs, spip_auteurs_messages AS lien WHERE lien.id_message=$id_message AND lien.id_auteur!=$connect_id_auteur AND lien.id_auteur=auteurs.id_auteur";
				$result_auteurs = spip_query($query_auteurs);
				$auteurs = '';
				while ($row_auteurs = spip_fetch_array($result_auteurs)) {
					$id_auteur = $row_auteurs['id_auteur'];
					$auteurs[] = "<a href='" . generer_url_ecrire("auteurs_edit","id_auteur=$id_auteur") . "'>".typo($row_auteurs['nom'])."</a>";
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
			// Messages de forums
			
			$query_forum = "SELECT * FROM spip_forum WHERE id_message = $id_message";
			$total_forum = spip_num_rows(spip_query($query_forum));
			
			if ($total_forum > 0) $vals[] = "($total_forum)";
			else $vals[] = "";
			
		
			
			//
			// Date
			//
			
			$s = affdate($date);
			if ($rv == 'oui') {
				$jour=journum($date);
				$mois=mois($date);
				$annee=annee($date);
				
				$heure = heures($date).":".minutes($date);
				if (affdate($date) == affdate($date_fin))
					$heure_fin = heures($date_fin).":".minutes($date_fin);
				else 
					$heure_fin = "...";

				$s = "<div " . 
				  http_style_background('rv-12.gif', "$spip_lang_left center no-repeat; padding-$spip_lang_left: 15px") .
				  "><a href='" . generer_url_ecrire("calendrier","type=jour&jour=$jour&mois=$mois&annee=$annee") . "'><b style='color: black;'>$s</b><br />$heure-$heure_fin</a></div>";
			} else {
				$s = "<font color='#999999'>$s</font>";
			}
			
			$vals[] = $s;

			$table[] = $vals;
		}

		if ($afficher_auteurs) {
			$largeurs = array('', 130, 20, 120);
			$styles = array('arial2', 'arial1', 'arial1', 'arial1');
		}
		else {
			$largeurs = array('', 20, 120);
			$styles = array('arial2', 'arial1', 'arial1');
		}
		afficher_liste($largeurs, $table, $styles);

		echo "</TABLE>";
		echo "</div>\n\n";
		spip_free_result($result_message);
		if ($important) fin_cadre_couleur();
	}
}

?>
