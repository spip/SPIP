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

include_spip('inc/presentation');
include_spip('inc/message_select');

// http://doc.spip.org/@exec_messagerie_dist
function exec_messagerie_dist()
{

global $connect_id_auteur, $connect_statut, $spip_lang_rtl;


$commencer_page = charger_fonction('commencer_page', 'inc');
echo $commencer_page(_T('titre_page_messagerie'), "accueil", "messagerie");

debut_gauche("messagerie");


echo debut_boite_info(true);

echo _T('info_gauche_messagerie');

echo "<p>".http_img_pack("m_envoi$spip_lang_rtl.gif", 'V', "style='width: 14px; height: 7px; border: 0px'") .' ' . _T('info_symbole_vert'), '</p>';

 echo aide ("messut");

echo "<p>".http_img_pack("m_envoi_bleu$spip_lang_rtl.gif", 'B', "style='width: 14px; height: 7px; border: 0px'") .' ' . _T('info_symbole_bleu'), '</p>';

echo aide ("messpense");

echo "<p>".http_img_pack("m_envoi_jaune$spip_lang_rtl.gif", 'J', "style='width: 14px; height: 7px; border: 0px'") .' ' . _T('info_symbole_jaune'), '</p>';

echo fin_boite_info(true);

echo creer_colonne_droite(true);

echo debut_cadre_relief("messagerie-24.gif", true);
echo icone_horizontale(_T('lien_nouvea_pense_bete'),generer_action_auteur("editer_message","pb"), "pense-bete.gif", "", false);
echo icone_horizontale(_T('lien_nouveau_message'),generer_action_auteur("editer_message","normal"), "message.gif", "", false);
		
		if ($connect_statut == "0minirezo") {
			echo icone_horizontale(_T('lien_nouvelle_annonce'),generer_action_auteur("editer_message","affich"), "annonce.gif", "", false);
		}
echo fin_cadre_relief(true);


# Affiche l'encadre "lien iCal"

 echo
    debut_cadre_enfonce('',true) .
    "<div class='verdana1'>"._T("calendrier_synchro") .
    "<table  class='cellule-h-table' cellpadding='0'><tr>\n" .
    "<td><a href='" . generer_url_ecrire("synchro") . "' class='ical'>&nbsp;</a></td>\n"
    . "<td class='cellule-h-lien'><br /><a href='" . generer_url_ecrire("synchro") . "' class='cellule-h'>" 
    . _T("icone_suivi_activite")
    . "</a></td>\n</tr></table>\n" ."</div>" .
    fin_cadre_enfonce(true);


 debut_droite("messagerie");

 $messages_vus = array();

 echo afficher_messages('<b>' . _T('infos_vos_pense_bete') . '</b>', '', "id_auteur=$connect_id_auteur AND statut='publie' AND type='pb' AND (date_fin > DATE_SUB(NOW(), INTERVAL 1 DAY) OR rv != 'oui')", $messages_vus, false, true);


 echo afficher_messages('<b>' . _T('info_nouveaux_message') . '</b>', ", spip_auteurs_messages AS lien", "lien.id_auteur=$connect_id_auteur AND vu='non' AND statut='publie' AND lien.id_message=messages.id_message", $messages_vus,  true, true);


 echo afficher_messages('<b>' . _T('info_discussion_cours') . '</b>', ", spip_auteurs_messages AS lien", "lien.id_auteur=$connect_id_auteur AND statut='publie' AND type='normal' AND lien.id_message=messages.id_message AND (date_fin > DATE_SUB(NOW(), INTERVAL 1 DAY) OR rv != 'oui')",  $messages_vus, true, false);


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



 echo afficher_messages('<b>' . _T('info_message_en_redaction') . '</b>', '', "id_auteur=$connect_id_auteur AND statut='redac'",  $messages_vus, true, false);


$result = spip_query("SELECT auteurs.id_auteur, auteurs.nom, COUNT(*) AS total FROM spip_auteurs AS auteurs,  spip_auteurs_messages AS lien2, spip_messages AS messages, spip_auteurs_messages AS lien WHERE (lien.id_auteur = $connect_id_auteur AND lien.id_message = messages.id_message AND messages.statut = 'publie' AND (messages.rv != 'oui' OR messages.date_fin > NOW() )) AND (lien2.id_auteur = lien2.id_auteur AND lien2.id_message = messages.id_message AND lien2.id_auteur != $connect_id_auteur AND auteurs.id_auteur = lien2.id_auteur) GROUP BY auteurs.id_auteur ORDER BY total DESC LIMIT 10");

if (spip_num_rows($result) > 0) {

	echo "<div style='height: 12px;'></div>";
	$bouton = bouton_block_depliable(_T('info_principaux_correspondants'),true,'principaux');
	echo debut_cadre('liste',"redacteurs-24.gif",'',$bouton);
	echo debut_block_depliable(true,'principaux');
	echo "<table width='100%' cellpadding='0' cellspacing='0'>";
	echo "<tr><td valign='top' style='width: 50%'>";
	$count = 0;
	while($row = sql_fetch($result)) {
		$count ++;
		$id_auteur = $row['id_auteur'];
		$nom = typo($row["nom"]);
		$total = $row["total"];
		echo "<div class='tr_liste'\nonmouseover=\"changeclass(this,'tr_liste_over');\"\nonmouseout=\"changeclass(this,'tr_liste');\"\nstyle='padding: 2px; padding-left: 10px; border-bottom: 1px solid #cccccc;'><div class='verdana1'><img src='" . _DIR_IMG_PACK . "redac-12.gif'\nstyle='border: 0px' alt=' ' /> <a href='" . generer_url_ecrire("auteur_infos","id_auteur=$id_auteur"), "'>",
		  $nom,
		  "</a> ($total)</div></div>";
		if ($count == ceil(spip_num_rows($result)/2)) echo "</td><td valign='top' style='width: 50%'>";
	}
	echo "</td></tr></table>";
	echo fin_block();
	echo fin_cadre('liste');
}

 echo afficher_messages('<b>' . _T('info_pense_bete_ancien') . '</b>', '', "id_auteur=$connect_id_auteur AND statut='publie' AND type='pb' AND rv!='oui'",  $messages_vus, false, false);

 echo afficher_messages('<b>' . _T('info_tous_redacteurs') . '</b>', '', "statut='publie' AND type='affich' AND (date_fin > DATE_SUB(NOW(), INTERVAL 1 DAY) OR rv != 'oui')",  $messages_vus, false, false);

echo fin_gauche(), fin_page();

}
?>
