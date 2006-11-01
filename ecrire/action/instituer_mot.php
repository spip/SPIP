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

include_spip('inc/actions');
include_spip('inc/filtres');
include_spip('base/abstract_sql');

// Modifier le reglage des forums publics de l'article x
// http://doc.spip.org/@action_instituer_mot_dist
function action_instituer_mot_dist()
{
	$var_f = charger_fonction('controler_action_auteur', 'inc');
	$var_f();

	$arg = _request('arg');

	if (!preg_match(",^(\d+)$,", $arg, $r)) {
		 spip_log("action_instituer_mot_dist $arg pas compris");
	} else action_instituer_mot_post($r);
}

// http://doc.spip.org/@action_instituer_mot_post
function action_instituer_mot_post($r)
{
	$id_mot = $r[1];
	global $titre_mot, $id_groupe, $descriptif, $texte, $new, $table, $table_id, $ajouter_id_article;

	if (strval($titre_mot)!='') {
		if ($new == 'oui' && $id_groupe) {
			$id_mot = spip_abstract_insert("spip_mots", '(id_groupe)', "($id_groupe)");

			if($ajouter_id_article = intval($ajouter_id_article))
			// heureusement que c'est pour les admin complet,
			// sinon bonjour le XSS
				ajouter_nouveau_mot($id_groupe, $table, $table_id, $id_mot, $ajouter_id_article);

		}

		$result = spip_query("SELECT titre FROM spip_groupes_mots WHERE id_groupe=$id_groupe");
		if ($row = spip_fetch_array($result))
			$type = (corriger_caracteres($row['titre']));
		else $type = (corriger_caracteres($type));
		// recoller les champs du extra
		if ($champs_extra) {
			include_spip('inc/extra');
			$add_extra = extra_recup_saisie("mots");
		} else
			$add_extra = '';

		spip_query("UPDATE spip_mots SET titre=" . _q($titre_mot) . ", texte=" . _q($texte) . ", descriptif=" . _q($descriptif) . ", type=" . _q($type) . ", id_groupe=$id_groupe" . (!$add_extra ? '' : (", extra = " . _q($add_extra))) . " WHERE id_mot=$id_mot");

		if ($GLOBALS['meta']['activer_moteur'] == 'oui') {
			include_spip("inc/indexation");
			marquer_indexer('spip_mots', $id_mot);
		}
	}
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
