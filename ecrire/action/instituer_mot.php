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

// Modifier le reglage des forums publics de l'article x
// http://doc.spip.org/@action_instituer_mot_dist
function action_instituer_mot_dist()
{
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();
	if (!preg_match(",^(\d+)(\W(\d+)\W(\w+)\W(\w+))?$,", $arg, $r)) {
		 spip_log("action_instituer_mot_dist '$arg' pas compris");
	} else action_instituer_mot_post($r);
}

// http://doc.spip.org/@action_instituer_mot_post
function action_instituer_mot_post($r)
{
	$id_mot = $r[1];
	$id_groupe = intval(_request('id_groupe'));

	if (!$id_mot AND $id_groupe) {
		$id_mot = sql_insertq("spip_mots", array('id_groupe' => $id_groupe));
		if ($id_mot AND $r[2]) {
			list(,,,$ajouter_id_article, $table, $table_id) = $r;
			ajouter_nouveau_mot($id_groupe, $table, $table_id, $id_mot, $ajouter_id_article);
		}
	}

	// modifier le contenu via l'API
	include_spip('inc/modifier');
	revision_mot($id_mot);
	if ($redirect = _request('redirect'))
		redirige_par_entete(parametre_url(urldecode($redirect),
						  'id_mot', $id_mot, '&'));

}

// http://doc.spip.org/@ajouter_nouveau_mot
function ajouter_nouveau_mot($id_groupe, $table, $table_id, $id_mot, $id)
{
	if (un_seul_mot_dans_groupe($id_groupe)) {
		$mots = spip_query("SELECT id_mot FROM spip_mots WHERE id_groupe = $id_groupe");
		$a = array();
		while ($r = sql_fetch($mots)) $a[]=  $r['id_mot'];
		spip_query("DELETE FROM spip_mots_$table WHERE id_mot IN (" . join(',',$a) .") AND $table_id=$id");
	}
	sql_insertq("spip_mots_$table", array("id_mot" => $id_mot, $table_id => $id));
}


// http://doc.spip.org/@un_seul_mot_dans_groupe
function un_seul_mot_dans_groupe($id_groupe)
{
	return sql_countsel('spip_groupes_mots', "id_groupe=$id_groupe AND unseul='oui'");
}

?>
