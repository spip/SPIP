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

include_spip('inc/presentation');
include_spip('inc/message_select');

// http://doc.spip.org/@exec_messagerie_dist
function exec_messagerie_dist()
{

global $connect_id_auteur, $connect_statut, $spip_lang_rtl;


$commencer_page = charger_fonction('commencer_page', 'inc');
echo $commencer_page(_T('titre_page_messagerie'), "accueil", "messagerie");

echo debut_gauche("messagerie",true);


echo debut_boite_info(true);

echo _T('info_gauche_messagerie');

echo "<p>".http_img_pack("m_envoi$spip_lang_rtl.gif", 'V', "style='width: 14px; height: 7px; border: 0px'") .' ' . _T('info_symbole_vert'), '</p>';

 echo aide ("messut");

echo "<p>".http_img_pack("m_envoi_bleu$spip_lang_rtl.gif", 'B', "style='width: 14px; height: 7px; border: 0px'") .' ' . _T('info_symbole_bleu'), '</p>';

echo aide ("messpense");

echo "<p>".http_img_pack("m_envoi_jaune$spip_lang_rtl.gif", 'J', "style='width: 14px; height: 7px; border: 0px'") .' ' . _T('info_symbole_jaune'), '</p>';

echo fin_boite_info(true);

echo creer_colonne_droite('', true);

echo debut_cadre_relief("messagerie-24.png", true);
echo icone_horizontale(_T('lien_nouvea_pense_bete'),generer_action_auteur("editer_message","pb"), "pense-bete-24.png", "", false);
echo icone_horizontale(_T('lien_nouveau_message'),generer_action_auteur("editer_message","normal"), "message-24.png", "", false);
		
		if ($connect_statut == "0minirezo") {
			echo icone_horizontale(_T('lien_nouvelle_annonce'),generer_action_auteur("editer_message","affich"), "annonce-24.png", "", false);
		}
echo fin_cadre_relief(true);


# Affiche l'encadre "lien iCal"
 echo
    debut_cadre_enfonce('',true) .
		icone_horizontale(_T('icone_suivi_activite'),generer_url_ecrire("synchro"), "synchro-24.png", "", false) .
    fin_cadre_enfonce(true);


 echo debut_droite("messagerie", true);

 $messages_vus = array();

 echo afficher_ses_messages('<b>' . _T('infos_vos_pense_bete') . '</b>', '', "id_auteur=$connect_id_auteur AND statut='publie' AND type='pb' AND (date_fin > DATE_SUB(".sql_quote(date('Y-m-d H:i:s')).", INTERVAL 1 DAY) OR rv != 'oui')", $messages_vus, false, true,'pense-bete');


 echo afficher_ses_messages('<b>' . _T('info_nouveaux_message') . '</b>', ", spip_auteurs_messages AS A", "A.id_auteur=$connect_id_auteur AND vu='non' AND statut='publie' AND A.id_message=M.id_message", $messages_vus,  true, true,'message');


 echo afficher_ses_messages('<b>' . _T('info_discussion_cours') . '</b>', ", spip_auteurs_messages AS A", "A.id_auteur=$connect_id_auteur AND statut='publie' AND type='normal' AND A.id_message=M.id_message AND (date_fin > DATE_SUB(".sql_quote(date('Y-m-d H:i:s')).", INTERVAL 1 DAY) OR rv != 'oui')",  $messages_vus, true, false,'message');


// Afficher le lien RSS

echo bouton_spip_rss('messagerie', array('id_auteur' => $connect_id_auteur));



 echo afficher_ses_messages('<b>' . _T('info_message_en_redaction') . '</b>', '', "id_auteur=$connect_id_auteur AND statut='redac'",  $messages_vus, true, false,'message');


 $result = sql_select('A.id_auteur, A.nom, COUNT(*) AS total', 'spip_auteurs AS A LEFT JOIN spip_auteurs_messages AS D ON A.id_auteur=D.id_auteur LEFT JOIN spip_messages AS M ON D.id_message=M.id_message LEFT JOIN spip_auteurs_messages AS S ON S.id_message=M.id_message', "(S.id_auteur = $connect_id_auteur AND M.statut = 'publie' AND (M.rv != 'oui' OR M.date_fin > ".sql_quote(date('Y-m-d H:i:s'))." ))  AND D.id_auteur != $connect_id_auteur", "A.id_auteur", 'total DESC', 10);

 $cor = array();
 while($row = sql_fetch($result)) {
		$id_auteur = $row['id_auteur'];
		$nom = typo($row["nom"]);
		$total = $row["total"];
		$cor[]= "<div class='tr_liste'\nonmouseover=\"changeclass(this,'tr_liste_over');\"\nonmouseout=\"changeclass(this,'tr_liste');\"\nstyle='padding: 2px; padding-left: 10px; border-bottom: 1px solid #cccccc;'><div class='verdana1'><img src='" . chemin_image('auteur-16.png') . "'\nstyle='border: 0px' alt=' ' /> <a href='" . generer_url_ecrire("auteur_infos","id_auteur=$id_auteur") . "'>$nom,</a> ($total)</div></div>";
 }

 if ($cor) {

	echo "<div style='height: 12px;'></div>";
	$bouton = bouton_block_depliable(_T('info_principaux_correspondants'),true,'principaux');
	echo debut_cadre('liste',"auteur-24.png",'',$bouton);
	echo debut_block_depliable(true,'principaux');
	echo "<table width='100%' cellpadding='0' cellspacing='0'>";
	echo "<tr><td valign='top' style='width: 50%'>";
	$count = ceil(count($cor)/2);
	echo join("\n",array_slice($cor, 0, $count));
	echo "</td><td valign='top' style='width: 50%'>";
	echo join("\n",array_slice($cor, $count));
	echo "</td></tr></table>";
	echo fin_block();
	echo fin_cadre('liste');
 }

 echo afficher_ses_messages('<b>' . _T('info_pense_bete_ancien') . '</b>', '', "id_auteur=$connect_id_auteur AND statut='publie' AND type='pb' AND rv!='oui'",  $messages_vus, false, false);

 echo afficher_ses_messages('<b>' . _T('info_tous_redacteurs') . '</b>', '', "statut='publie' AND type='affich' AND (date_fin > DATE_SUB(".sql_quote(date('Y-m-d H:i:s')).", INTERVAL 1 DAY) OR rv != 'oui')",  $messages_vus, false, false);

echo fin_gauche(), fin_page();

}
?>
