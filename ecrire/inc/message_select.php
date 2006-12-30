<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// $messages_vus en reference pour interdire l'affichage de message en double

// http://doc.spip.org/@afficher_messages
function afficher_messages($titre, $from, $where, &$messages_vus, $afficher_auteurs = true, $important = false) {
	global $connect_id_auteur, $couleur_foncee, $spip_lang_rtl, $spip_lang_left;

	$tmp_var = 't_' . substr(md5($where.$from), 0, 4);

	$requete = array('FROM' => "spip_messages AS messages$from", 'WHERE' => $where .(!$messages_vus ? '' : ' AND messages.id_message NOT IN ('.join(',', $messages_vus).')'), 'ORDER BY'=> 'date_heure');

	if ($afficher_auteurs) {
			$largeurs = array('', 130, 20, 120);
			$styles = array('arial2', 'arial1', 'arial1', 'arial1');
	} else {
			$largeurs = array('', 20, 120);
			$styles = array('arial2', 'arial1', 'arial1');
	}


	$res =  affiche_tranche_bandeau($requete, "messagerie-24.gif", $couleur_foncee, "white", $tmp_var, $titre, false, $largeurs, $styles, 'afficher_message_boucles', $afficher_auteurs);

	$result = spip_query("SELECT messages.id_message FROM " . $requete['FROM'] . ' WHERE ' . $requete['WHERE']);

	while ($r = spip_fetch_array($result)) {
		$r = $r['id_message'];
		$messages_vus[$r]= $r;
	}
	
	if (!$important) return $res;
	else return "<div class='cadre-couleur'><div class='cadre-padding'>$res</div></div>";
}

// http://doc.spip.org/@afficher_message_boucles
function afficher_message_boucles($row, &$messages_vus, $voir_logo, $afficher_auteurs)
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
	$s .= "&nbsp;&nbsp;".typo($titre)."</a>";
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
			$s = "<span style='font-size: 12px;' class='arial1'>" . join(', ', $auteurs) . "</span>";
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
		$s = "<span style='color: #999999'>$s</span>";
	}
			
	$vals[] = $s;

	return $vals;
}

?>
