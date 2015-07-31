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

include_spip('inc/filtres');
include_spip('base/abstract_sql');

// http://doc.spip.org/@action_editer_message_dist
function action_editer_message_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	if (preg_match(',^(\d+)$,', $arg, $r))
	  action_editer_message_post_vieux($arg); // pas encore fait.
	elseif (preg_match(',^(\w+)$,', $arg, $r))
		action_editer_message_post_nouveau($arg);
	elseif (preg_match(',^(\w+)\W(\d+)$,', $arg, $r))
		action_editer_message_post_nouveau($r[1], $r[2]);
	elseif (preg_match(',^(\w+)\W(\d+-\d+-\d+)$,', $arg, $r))
		action_editer_message_post_nouveau($r[1], '', $r[2]);
	else 	spip_log("action_editer_message_dist $arg pas compris");
}

// http://doc.spip.org/@action_editer_message_post_nouveau
function action_editer_message_post_nouveau($type, $dest='', $rv='')
{

	$id_auteur = $GLOBALS['auteur_session']['id_auteur'];

	$mydate = date("YmdHis", time() - 2 * 24 * 3600);
	spip_query("DELETE FROM spip_messages WHERE (statut = 'redac') AND (date_heure < $mydate)");

	if ($type == 'pb') $statut = 'publie';
	else $statut = 'redac';

	$titre = filtrer_entites(_T('texte_nouveau_message'));

	$id_message = spip_abstract_insert("spip_messages", "(titre, date_heure, statut, type, id_auteur)", "(" . _q($titre) . ", NOW(), '$statut', '$type', $id_auteur)");
	
	if ($rv) {
		spip_query("UPDATE spip_messages SET rv='oui', date_heure=" . _q($rv . ' 12:00:00') . ", date_fin= " . _q($rv . ' 13:00:00') . " WHERE id_message = $id_message");
	}

	if ($type != "affich"){
		spip_abstract_insert('spip_auteurs_messages',
			"(id_auteur,id_message,vu)",
			"('$id_auteur','$id_message','oui')");
		if ($dest) {
			spip_abstract_insert('spip_auteurs_messages',
				"(id_auteur,id_message,vu)",
				"('$dest','$id_message','non')");
		}
	}
	redirige_par_entete(generer_url_ecrire('message_edit', "id_message=$id_message&new=oui&dest=$dest",true));
}
?>
