<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// $messages_vus en reference pour interdire l'affichage de message en double


function afficher_ses_messages($titre, $join, $where, &$messages_vus, $afficher_auteurs = true, $important = false, $type='messagerie') {

	$requete = array('SELECT' => 'M.id_message, M.date_heure, M.date_fin, M.titre, M.type, M.rv', 'FROM' => "spip_messages AS M$join", 'WHERE' => $where .(!$messages_vus ? '' : ' AND M.id_message NOT IN ('.join(',', $messages_vus).')'), 'ORDER BY'=> 'date_heure DESC');

	if ($afficher_auteurs) {
		$styles = array(array('arial2'), array('arial1', 130), array('arial1', 20), array('arial1', 120));
	} else {
		$styles = array(array('arial2'), array('arial1', 20), array('arial1', 120));
	}

	$presenter_liste = charger_fonction('presenter_liste', 'inc');
	$tmp_var = 't_' . substr(md5(join('', $requete)), 0, 4);

	// cette variable est passe par reference et recevra les valeurs du champ indique 
	$les_messages = 'id_message'; 
	$res = 	$presenter_liste($requete, 'presenter_message_boucles', $les_messages, $afficher_auteur, $important, $styles, $tmp_var, $titre,  "$type-24.gif");
	$messages_vus =  array_merge($messages_vus, $les_messages);

	if (!$res) return '';
	else
	  return 
	    (debut_cadre_couleur('',true)
			. $res
	     . fin_cadre_couleur(true));
}

function presenter_message_boucles($row, $afficher_auteurs)
{
	global $connect_id_auteur, $spip_lang_left, $spip_lang_rtl;

	$vals = array();

	$id_message = $row['id_message'];
	$date = $row["date_heure"];
	$date_fin = $row["date_fin"];
	$titre = sinon($row['titre'], _T('ecrire:info_sans_titre'));
	$type = $row["type"];
	$rv = $row["rv"];

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
		$auteurs = sql_allfetsel("A.id_auteur, A.nom", "spip_auteurs AS A, spip_auteurs_messages AS L", "L.id_message=$id_message AND L.id_auteur!=$connect_id_auteur AND L.id_auteur=A.id_auteur");

		foreach ($auteurs as $k => $row_auteurs) {
			$id_auteur = $row_auteurs['id_auteur'];
			$auteurs[$k] = "<a href='" . generer_url_ecrire("auteur_infos","id_auteur=$id_auteur") . "'>".typo($row_auteurs['nom'])."</a>";
		}

		if ($auteurs AND $type == 'normal') {
			$s = "<span class='arial1 spip_x-small'>" . join(', ', $auteurs) . "</span>";
		} else $s = "&nbsp;";
		$vals[] = $s;
	}
			
	//
	// Messages de forums
	if (test_plugin_actif('forum')
	  AND	$total_forum = sql_countsel('spip_forum', "id_message=$id_message")>0)
		$vals[] = "($total_forum)";
	else
		$vals[] = "";
			
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
