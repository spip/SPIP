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

include_spip('inc/filtres');

// http://doc.spip.org/@action_instituer_groupe_mots_dist
function action_instituer_groupe_mots_dist()
{
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	if (preg_match(",^([a-zA-Z_]\w+)$,", $arg, $r)) 
	  action_instituer_groupe_mots_get($arg);
	elseif (!preg_match(",^(-?\d+)$,", $arg, $r)) {
		 spip_log("action_instituer_groupe_mots_dist $arg pas compris");
	} else action_instituer_groupe_mots_post($r[1]);
}

// http://doc.spip.org/@action_instituer_groupe_mots_post
function action_instituer_groupe_mots_post($id_groupe)
{
	if ($id_groupe < 0){
		sql_delete("spip_groupes_mots", "id_groupe=" . (0- $id_groupe));
	}
	else
		spip_log('appel deprecie, rien a faire ici (voir action/edite_groupe_mot)');
}

// http://doc.spip.org/@action_instituer_groupe_mots_get
function action_instituer_groupe_mots_get($table)
{
	include_spip('action/editer_groupe_mot');
	$id_groupe = insert_groupe_mot($table);

  redirige_par_entete(parametre_url(urldecode(_request('redirect')),
		  'id_groupe', $id_groupe, '&'));
}

?>
