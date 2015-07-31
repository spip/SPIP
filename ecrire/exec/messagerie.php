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

include_spip('inc/presentation');
include_spip('inc/message_select');

// http://doc.spip.org/@exec_messagerie_dist
function exec_messagerie_dist()
{

  global $connect_id_auteur, $connect_statut, $couleur_claire, $spip_lang_rtl;

  $id_message = intval(_request('id_message'));
  $detruire_message = intval(_request('detruire_message'));
  $supp_dest = intval(_request('supp_dest'));

  if ($supp_dest) {
	spip_query("DELETE FROM spip_auteurs_messages WHERE id_message=$id_message AND id_auteur=$supp_dest");
}

if ($detruire_message) {
	spip_query("DELETE FROM spip_messages WHERE id_message=$detruire_message");
	spip_query("DELETE FROM spip_auteurs_messages WHERE id_message=$detruire_message");
	spip_query("DELETE FROM spip_forum WHERE id_message=$detruire_message");
}

debut_page(_T('titre_page_messagerie'), "accueil", "messagerie");

debut_gauche("messagerie");


debut_boite_info();

echo _T('info_gauche_messagerie');

echo "<p>".http_img_pack("m_envoi$spip_lang_rtl.gif", 'V', "WIDTH='14' HEIGHT='7' BORDER='0'") .' ' . _T('info_symbole_vert');

echo aide ("messut");

echo "<p>".http_img_pack("m_envoi_bleu$spip_lang_rtl.gif", 'B', "WIDTH='14' HEIGHT='7' BORDER='0'") .' ' . _T('info_symbole_bleu');

echo aide ("messpense");

echo "<p>".http_img_pack("m_envoi_jaune$spip_lang_rtl.gif", 'J', "WIDTH='14' HEIGHT='7' BORDER='0'") .' ' . _T('info_symbole_jaune');

fin_boite_info();

creer_colonne_droite();

debut_cadre_relief("messagerie-24.gif");
 icone_horizontale(_T('lien_nouvea_pense_bete'),generer_url_ecrire("message_edit","new=oui&type=pb"), "pense-bete.gif");
 icone_horizontale(_T('lien_nouveau_message'),generer_url_ecrire("message_edit","new=oui&type=normal"), "message.gif");
		
		if ($connect_statut == "0minirezo") {
		  icone_horizontale(_T('lien_nouvelle_annonce'),generer_url_ecrire("message_edit","new=oui&type=affich"), "annonce.gif");
		}
fin_cadre_relief();


# Affiche l'encadre "lien iCal"

 echo
    debut_cadre_enfonce('',true) .
    "<div class='verdana1'>"._T("calendrier_synchro") .
    "<table  class='cellule-h-table' cellpadding='0' valign='middle'><tr>\n" .
    "<td><a href='" . generer_url_ecrire("synchro","") . "'><div class='cell-i'>"
    . http_img_pack("rien.gif", ' ', http_style_background('synchro-24.gif', "; background-repeat: no-repeat; background-position: center center;"))
    . "</div></a></td>\n"
    . "<td class='cellule-h-lien'><a href='" . generer_url_ecrire("synchro","") . "' class='cellule-h'>" 
    . _T("icone_suivi_activite")
    . "</a></td>\n</tr></table>\n" ."</div>" .
    fin_cadre_enfonce(true);


 debut_droite("messagerie");

 $messages_vus = array();

 afficher_messages(_T('infos_vos_pense_bete'), '', "id_auteur=$connect_id_auteur AND statut='publie' AND type='pb' AND (date_fin > DATE_SUB(NOW(), INTERVAL 1 DAY) OR rv != 'oui')", $messages_vus, false, true);


 afficher_messages(_T('info_nouveaux_message'), ", spip_auteurs_messages AS lien", "lien.id_auteur=$connect_id_auteur AND vu='non' AND statut='publie' AND lien.id_message=messages.id_message", $messages_vus,  true, true);


 afficher_messages(_T('info_discussion_cours'), ", spip_auteurs_messages AS lien", "lien.id_auteur=$connect_id_auteur AND statut='publie' AND type='normal' AND lien.id_message=messages.id_message AND (date_fin > DATE_SUB(NOW(), INTERVAL 1 DAY) OR rv != 'oui')",  $messages_vus, true, false);


// Afficher le lien RSS

$op = 'messagerie';
$args = array(
	'id_auteur' => $connect_id_auteur
);
echo "<div style='text-align: "
	. $GLOBALS['spip_lang_right']
	. ";'>"
	. bouton_spip_rss($op, $args)
	."</div>";



 afficher_messages(_T('info_message_en_redaction'), '', "id_auteur=$connect_id_auteur AND statut='redac'",  $messages_vus, true, false);


$result = spip_query("SELECT auteurs.id_auteur, auteurs.nom, COUNT(*) AS total FROM spip_auteurs AS auteurs,  spip_auteurs_messages AS lien2, spip_messages AS messages, spip_auteurs_messages AS lien WHERE (lien.id_auteur = $connect_id_auteur AND lien.id_message = messages.id_message AND messages.statut = 'publie' AND (messages.rv != 'oui' OR messages.date_fin > NOW() )) AND (lien2.id_auteur = lien2.id_auteur AND lien2.id_message = messages.id_message AND lien2.id_auteur != $connect_id_auteur AND auteurs.id_auteur = lien2.id_auteur) GROUP BY auteurs.id_auteur ORDER BY total DESC LIMIT 10");

if (spip_num_rows($result) > 0) {

	echo "<div style='height: 12px;'></div>";
	echo "<div class='liste'>";

	bandeau_titre_boite2('<b>' . _T('info_principaux_correspondants') . '</b>', "redacteurs-24.gif", "#333333", "white");

	echo "<table width='100%' cellpadding='0' cellspacing='0'>";
	echo "<tr><td valign='top' width='50%'>";
	$count = $i = 0;
	while($row = spip_fetch_array($result)) {
		$count ++;
		if ($i == 1) {
			$bgcolor = "white";
			$i = 0;
		} else {
			$bgcolor = $couleur_claire;
			$i = 1;
		}
		$id_auteur = $row['id_auteur'];
		$nom = typo($row["nom"]);
		$total = $row["total"];
		echo "<div class='tr_liste' onMouseOver=\"changeclass(this,'tr_liste_over');\" onMouseOut=\"changeclass(this,'tr_liste');\" style=' padding: 2px; padding-left: 10px; border-bottom: 1px solid #cccccc;'><div class='verdana1'><img src='" . _DIR_IMG_PACK . "redac-12.gif' border='0'> <a href='" . generer_url_ecrire("auteurs_edit","id_auteur=$id_auteur") . "'>$nom</a> ($total)</div></div>";
		if ($count == ceil(spip_num_rows($result)/2)) echo "</td><td valign='top' width='50%' style='background-color: #eeeeee;'>";
	}
	echo "</td></tr></table>";
	echo "</div>";
}

 afficher_messages(_T('info_pense_bete_ancien'), '', "id_auteur=$connect_id_auteur AND statut='publie' AND type='pb' AND rv!='oui'",  $messages_vus, false, false);

 afficher_messages(_T('info_tous_redacteurs'), '', "statut='publie' AND type='affich' AND (date_fin > DATE_SUB(NOW(), INTERVAL 1 DAY) OR rv != 'oui')",  $messages_vus, false, false);

echo fin_page();

}
?>
