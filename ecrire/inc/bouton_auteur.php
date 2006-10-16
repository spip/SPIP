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

//
// affiche un bouton appelant le script de messagerie interne
//

function inc_bouton_auteur_dist($id_auteur) {

	global $connect_id_auteur, $spip_lang_rtl;

	if ($id_auteur == $connect_id_auteur)
		return '';

	$login_req = spip_query("SELECT login, messagerie FROM spip_auteurs where id_auteur=" . intval($id_auteur) ." AND en_ligne>DATE_SUB(NOW(),INTERVAL 15 DAY)");
	$row = spip_fetch_array($login_req);

	if (($row['login'] == "") OR ($row['messagerie'] == "non")) {
			return '';
	}

	$title = _T('info_envoyer_message_prive');
		
	return "<a href='"
	. generer_url_ecrire("message_edit","new=oui&dest=$id_auteur&type=normal")
	. "' title=\""
	. $title
	. "\">"
	. http_img_pack("m_envoi$spip_lang_rtl.gif", "m&gt;", "width='14' height='7'", $title)
	. "</a>";
}
?>
