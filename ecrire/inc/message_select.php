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

// $messages_vus en reference pour interdire l'affichage de message en double

function afficher_messages($titre_table, $from, $where, &$messages_vus, $afficher_auteurs = true, $important = false) {
	global $connect_id_auteur, $couleur_foncee, $spip_lang_rtl, $spip_lang_left;

	$tmp_var = substr(md5($where.$from), 0, 4);
	$from =  "spip_messages AS messages$from";
	$where .= (!$messages_vus ? '' : ' AND messages.id_message NOT IN ('.join(',', $messages_vus).')');

	$cpt = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM $from WHERE $where"));
	if (!($cpt = $cpt['n'])) return ;

	$nb_aff = 1.5 * _TRANCHES;
	$deb_aff = intval(_request('t_' .$tmp_var));

	if ($cpt > $nb_aff) {
		$nb_aff = (_TRANCHES); 
		$tranches = afficher_tranches_requete($cpt, ($afficher_auteurs ? 4 : 3), $tmp_var, '', $nb_aff);
	}
	if ($important)  debut_cadre_couleur();
	// ie: echo "<div class='cadre-couleur'><div class='cadre-padding'>";

	echo "<div style='height: 12px;'></div>";
	echo "<div class='liste'>";
	bandeau_titre_boite2($titre_table, "messagerie-24.gif", $couleur_foncee, "white");
	echo "<TABLE WIDTH='100%' CELLPADDING='2' CELLSPACING='0' BORDER='0'>";
	echo $tranches;

	$result_message = spip_query("SELECT messages.* FROM $from WHERE $where ORDER BY date_heure DESC LIMIT $deb_aff, $nb_aff");
;
	while($row = spip_fetch_array($result_message)) {
		$table[]= afficher_message_boucles($row, $messages_vus, $afficher_auteurs);
	}

	if ($afficher_auteurs) {
			$largeurs = array('', 130, 20, 120);
			$styles = array('arial2', 'arial1', 'arial1', 'arial1');
	} else {
			$largeurs = array('', 20, 120);
			$styles = array('arial2', 'arial1', 'arial1');
	}
	spip_free_result($result_message);
	echo afficher_liste($largeurs, $table, $styles);

	echo "</TABLE>";
	echo "</div>\n\n";
	if ($important) fin_cadre_couleur();
}

function afficher_message_boucles($row, &$messages_vus, $afficher_auteurs)
{
	global $connect_id_auteur, $spip_lang_left, $spip_lang_rtl;

	$vals = array();

	$id_message = $row['id_message'];
	$date = $row["date_heure"];
	$date_fin = $row["date_fin"];
	$titre = sinon($row['titre'], _T('ecrire:info_sans_titre'));
	$type = $row["type"];
	$rv = $row["rv"];
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
				
	$s .= http_img_pack("$puce", "", "width='14' height='7'");
	$s .= "&nbsp;&nbsp;".typo($titre)."</A>";
	$vals[] = $s;

			//
			// Auteurs

	if ($afficher_auteurs) {
		$result_auteurs = spip_query("SELECT auteurs.id_auteur, auteurs.nom FROM spip_auteurs AS auteurs, spip_auteurs_messages AS lien WHERE lien.id_message=$id_message AND lien.id_auteur!=$connect_id_auteur AND lien.id_auteur=auteurs.id_auteur");

		$auteurs = '';
		while ($row_auteurs = spip_fetch_array($result_auteurs)) {
			$id_auteur = $row_auteurs['id_auteur'];
			$auteurs[] = "<a href='" . generer_url_ecrire("auteurs_edit","id_auteur=$id_auteur") . "'>".typo($row_auteurs['nom'])."</a>";
		}

		if ($auteurs AND $type == 'normal') {
			$s = "<FONT FACE='Arial,Sans,sans-serif' SIZE=1>";
			$s .= join(', ', $auteurs);
			$s .= "</FONT>";
		} else $s = "&nbsp;";
		$vals[] = $s;
	}
			
			//
			// Messages de forums
			
	$total_forum = spip_num_rows(spip_query("SELECT id_message FROM spip_forum WHERE id_message = $id_message"));
			
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

	return $vals;
}

?>
