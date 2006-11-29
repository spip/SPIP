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

include_spip('inc/filtres');
include_spip('base/abstract_sql');

// Modifier le reglage des forums publics de l'article x
// http://doc.spip.org/@action_instituer_mot_dist
function action_instituer_mot_dist()
{
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$securiser_action();

	$arg = _request('arg');
	if (!preg_match(",^(\d+)$,", $arg, $r)) {
		 spip_log("action_instituer_mot_dist $arg pas compris");
	} else action_instituer_mot_post($r);
}

// http://doc.spip.org/@action_instituer_mot_post
function action_instituer_mot_post($r)
{
	$id_mot = $r[1];
	global $new, $table, $table_id, $ajouter_id_article;

	$id_groupe = intval(_request('id_groupe'));

	if ($new == 'oui') {
		$id_mot = spip_abstract_insert("spip_mots",
			'(id_groupe)', "($id_groupe)");

		if ($ajouter_id_article = intval($ajouter_id_article))
		// heureusement que c'est pour les admin complet,
		// sinon bonjour le XSS
			ajouter_nouveau_mot($id_groupe, $table, $table_id, $id_mot, $ajouter_id_article);

	}

	// modifier le contenu via l'API
	include_spip('inc/modifier');
	revision_mot($id_mot);

}

// http://doc.spip.org/@ajouter_nouveau_mot
function ajouter_nouveau_mot($id_groupe, $table, $table_id, $id_mot, $id)
{
	if (un_seul_mot_dans_groupe($id_groupe)) {
		$mots = spip_query("SELECT id_mot FROM spip_mots WHERE id_groupe = $id_groupe");
		while ($r = spip_fetch_array($mots))
			spip_query("DELETE FROM spip_mots_$table WHERE id_mot=" . $r['id_mot'] ." AND $table_id=$id");
	}
	spip_abstract_insert("spip_mots_$table", "(id_mot, $table_id)", "($id_mot, $id)");
}


// http://doc.spip.org/@un_seul_mot_dans_groupe
function un_seul_mot_dans_groupe($id_groupe)
{
	$u = spip_fetch_array(spip_query("SELECT unseul FROM spip_groupes_mots WHERE id_groupe = $id_groupe"));
	return ($u['unseul'] == 'oui');
}

?>
