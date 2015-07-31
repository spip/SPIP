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

include_spip('inc/filtres');

// Modifier le reglage des forums publics de l'article x
// http://doc.spip.org/@action_editer_mot_dist
function action_editer_mot_dist()
{
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$id_mot = intval($securiser_action());

	$id_groupe = intval(_request('id_groupe'));
	if (!$id_mot AND $id_groupe) {
		$id_mot = sql_insertq("spip_mots", array('id_groupe' => $id_groupe));
	}

	// modifier le contenu via l'API
	include_spip('inc/modifier');

	$c = array();
	foreach (array(
		'titre', 'descriptif', 'texte', 'id_groupe'
	) as $champ)
		$c[$champ] = _request($champ);

	revision_mot($id_mot, $c);
	if ($redirect = _request('redirect')) {
		include_spip('inc/headers');
		redirige_par_entete(parametre_url(urldecode($redirect),
			'id_mot', $id_mot, '&'));
	} else
		return array($id_mot,'');
}

// http://doc.spip.org/@ajouter_nouveau_mot
function ajouter_nouveau_mot($id_groupe, $table, $table_id, $id_mot, $id)
{
	if (un_seul_mot_dans_groupe($id_groupe)) {
		$mots = sql_select("id_mot", "spip_mots", "id_groupe = $id_groupe");
		$a = array();
		while ($r = sql_fetch($mots)) $a[]=  $r['id_mot'];
		sql_delete("spip_mots_$table", "id_mot IN (" . join(',',$a) .") AND $table_id=$id");
	}
	sql_insertq("spip_mots_$table", array("id_mot" => $id_mot, $table_id => $id));
}


// http://doc.spip.org/@un_seul_mot_dans_groupe
function un_seul_mot_dans_groupe($id_groupe)
{
	return sql_countsel('spip_groupes_mots', "id_groupe=$id_groupe AND unseul='oui'");
}

?>
