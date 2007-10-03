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

include_spip('inc/charsets');	# pour le nom de fichier
include_spip('inc/documents');

// Effacer un doc et sa vignette, ou une rubrique
// http://doc.spip.org/@action_supprimer_dist
function action_supprimer_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	preg_match('/^(\w+)\W(\d+)(\W(\w+)\W(\d+))?$/', $arg, $r);
	$var_nom = 'action_supprimer_' . $r[1];
	if (function_exists($var_nom)) {
		$var_nom($r);
	}
	else
		spip_log("action supprimer $arg incompris");
}

// Ne pas confondre cette fonction avec celle au pluriel ci-dessous

// http://doc.spip.org/@action_supprimer_document
function action_supprimer_document($arg) {
	list(,,$id_document,, $type, $id) = $arg;
	supprimer_document_et_vignette($id_document);
	if (strpos($type,'rubrique') !== 'false') {
		include_spip('inc/rubriques');
		depublier_branche_rubrique_if($id);
	}
}

// http://doc.spip.org/@action_supprimer_rubrique
function action_supprimer_rubrique($r)
{
	list(,,$id_rubrique) = $r;
	sql_delete("spip_rubriques", "id_rubrique=$id_rubrique");
	// Les admin restreints qui n'administraient que cette rubrique
	// deviennent redacteurs
	// (il y a sans doute moyen de faire ca avec un having)

	$q = sql_select("id_auteur", "spip_auteurs_rubriques", "id_rubrique=$id_rubrique");

	while ($r = sql_fetch($q)) {
		$id_auteur = $r['id_auteur'];
		sql_delete("spip_auteurs_rubriques", "id_rubrique=$id_rubrique AND id_auteur=$id_auteur");
		$n = sql_countsel("spip_auteurs_rubriques", "id_auteur=$id_auteur");
		if (!$n)
			spip_query("UPDATE spip_auteurs SET statut='1comite' WHERE id_auteur=$id_auteur");
	}

	// Une rubrique supprimable n'avait pas le statut "publie"
	// donc rien de neuf pour la rubrique parente
	include_spip('inc/rubriques');
	calculer_langues_rubriques();

	// invalider les caches marques de cette rubrique
	include_spip('inc/invalideur');
	suivre_invalideur("id='id_rubrique/$id_rubrique'");
}

// http://doc.spip.org/@supprimer_document_et_vignette
function supprimer_document_et_vignette($arg)
{
	$result = sql_select("id_vignette, fichier", "spip_documents", "id_document=$arg");
	if ($row = sql_fetch($result)) {
		spip_unlink(get_spip_doc($row['fichier']));
		sql_delete("spip_documents", "id_document=$arg");
		spip_query("UPDATE spip_documents SET id_vignette=0 WHERE id_vignette=$arg");
		sql_delete("spip_documents_articles", "id_document=$arg");
		sql_delete("spip_documents_rubriques", "id_document=$arg");
		sql_delete("spip_documents_breves", "id_document=$arg");
		$id_vignette = $row['id_vignette'];
		if ($id_vignette > 0) {
			$result = sql_select("fichier", "spip_documents	", "id_document=$id_vignette");

			if ($row = sql_fetch($result)) {
				spip_unlink(get_spip_doc($row['fichier']));
			}
			sql_delete("spip_documents", "id_document=$id_vignette");
			sql_delete("spip_documents_articles", "id_document=$id_vignette");
			sql_delete("spip_documents_rubriques", "id_document=$id_vignette");
			sql_delete("spip_documents_breves", "id_document=$id_vignette");
		}
	}
}
?>
