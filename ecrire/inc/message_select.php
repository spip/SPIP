<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// $messages_vus en reference pour interdire l'affichage de message en double

// http://doc.spip.org/@afficher_messages
function afficher_messages($titre, $from, $where, &$messages_vus, $afficher_auteurs = true, $important = false) {

	$tmp_var = 't_' . substr(md5($where.$from), 0, 4);

	$requete = array('SELECT' => 'messages.id_message, messages.date_heure, messages.date_fin, messages.titre, messages.type, messages.rv', 'FROM' => "spip_messages AS messages$from", 'WHERE' => $where .(!$messages_vus ? '' : ' AND messages.id_message NOT IN ('.join(',', $messages_vus).')'), 'ORDER BY'=> 'date_heure DESC');

	if ($afficher_auteurs) {
			$largeurs = array('', 130, 20, 120);
			$styles = array('arial2', 'arial1', 'arial1', 'arial1');
	} else {
			$largeurs = array('', 20, 120);
			$styles = array('arial2', 'arial1', 'arial1');
	}


	$tranches =  affiche_tranche_bandeau($requete, $tmp_var, false, 'afficher_message_boucles', $afficher_auteurs);

	$result = sql_select((isset($requete["SELECT"]) ? $requete["SELECT"] : "*"), $requete['FROM'], $requete['WHERE'], $requete['GROUP BY'], $requete['ORDER BY'], ($deb_aff > 0 ? "$deb_aff, $nb_aff" : ($requete['LIMIT'] ? $requete['LIMIT'] : "99999")));

	$table = array();
	while ($row = sql_fetch($result)) {
		$table[]= afficher_message_boucles($row, $messages_vus, $afficher_auteurs);
	}
	sql_free($result);

	$res = xhtml_table_id_type($table, $largeurs, $styles, $tranches, $titre,  "messagerie-24.gif");

	if (!$important AND !$table) return '';
	else
	  return 
	    (debut_cadre_couleur('',true)
			. $res
	     . fin_cadre_couleur(true));
}

// http://doc.spip.org/@afficher_message_boucles
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
	$s .= "&nbsp;&nbsp;".typo($titre)."</a>";
	$vals[] = $s;

			//
			// Auteurs

	if ($afficher_auteurs) {
		$result_auteurs = sql_select("auteurs.id_auteur, auteurs.nom", "spip_auteurs AS auteurs, spip_auteurs_messages AS lien", "lien.id_message=$id_message AND lien.id_auteur!=$connect_id_auteur AND lien.id_auteur=auteurs.id_auteur");

		$auteurs = '';
		while ($row_auteurs = sql_fetch($result_auteurs)) {
			$id_auteur = $row_auteurs['id_auteur'];
			$auteurs[] = "<a href='" . generer_url_ecrire("auteur_infos","id_auteur=$id_auteur") . "'>".typo($row_auteurs['nom'])."</a>";
		}

		if ($auteurs AND $type == 'normal') {
			$s = "<span class='arial1 spip_x-small'>" . join(', ', $auteurs) . "</span>";
		} else $s = "&nbsp;";
		$vals[] = $s;
	}
			
			//
			// Messages de forums
			
	$total_forum = sql_countsel('spip_forum', "id_message=$id_message");
			
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
