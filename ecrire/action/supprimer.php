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
include_spip('base/abstract_sql');

// Effacer un doc (et sa vignette)
// http://doc.spip.org/@action_supprimer_dist
function action_supprimer_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	preg_match('/^(\w+)\W(.*)$/', $arg, $r);
	$var_nom = 'action_supprimer_' . $r[1];
	if (function_exists($var_nom)) {
		spip_log("$var_nom $r[2]");
		$var_nom($r[2]);
	}
	else
		spip_log("action supprimer $arg incompris");
}

// Ne pas confondre cette fonction avec celle au pluriel ci-dessous

// http://doc.spip.org/@action_supprimer_document
function action_supprimer_document($arg) {
	supprimer_document_et_vignette(intval($arg));
	if (strpos(_request('redirect'), 'id_rubrique=')) {
		include_spip('inc/rubriques');
		calculer_rubriques();
	}
}

// http://doc.spip.org/@action_supprimer_rubrique
function action_supprimer_rubrique($id_rubrique)
{
	spip_query("DELETE FROM spip_rubriques WHERE id_rubrique=$id_rubrique");
	// Les admin restreints qui n'administraient que cette rubrique
	// deviennent redacteurs
	// (il y a sans doute moyen de faire ca avec un having)

	$q = spip_query("SELECT id_auteur FROM spip_auteurs_rubriques WHERE id_rubrique=$id_rubrique");

	while ($r = spip_abstract_fetch($q)) {
		$id_auteur = $r['id_auteur'];
		spip_query("DELETE FROM spip_auteurs_rubriques WHERE id_rubrique=$id_rubrique AND id_auteur=$id_auteur");
		$n = spip_num_rows(spip_query("SELECT id_auteur FROM spip_auteurs_rubriques WHERE id_auteur=$id_auteur LIMIT 1"));
		if (!$n)
			spip_query("UPDATE spip_auteurs SET statut='1comite' WHERE id_auteur=$id_auteur");
	}

	include_spip('inc/rubriques');
	calculer_rubriques();
	calculer_langues_rubriques();

	// invalider les caches marques de cette rubrique
	include_spip('inc/invalideur');
	suivre_invalideur("id='id_rubrique/$id_rubrique'");
}

// http://doc.spip.org/@supprimer_document_et_vignette
function supprimer_document_et_vignette($arg)
{
	$result = spip_query("SELECT id_vignette, fichier FROM spip_documents WHERE id_document=$arg");
	if ($row = spip_abstract_fetch($result)) {
		@unlink(get_spip_doc($row['fichier']));
		spip_query("DELETE FROM spip_documents WHERE id_document=$arg");
		spip_query("UPDATE spip_documents SET id_vignette=0 WHERE id_vignette=$arg");
		spip_query("DELETE FROM spip_documents_articles WHERE id_document=$arg");
		spip_query("DELETE FROM spip_documents_rubriques WHERE id_document=$arg");
		spip_query("DELETE FROM spip_documents_breves WHERE id_document=$arg");
		$id_vignette = $row['id_vignette'];
		if ($id_vignette > 0) {
			$result = spip_query("SELECT fichier FROM spip_documents	WHERE id_document=$id_vignette");

			if ($row = spip_abstract_fetch($result)) {
				@unlink(get_spip_doc($row['fichier']));
			}
			spip_query("DELETE FROM spip_documents	WHERE id_document=$id_vignette");
			spip_query("DELETE FROM spip_documents_articles	WHERE id_document=$id_vignette");
			spip_query("DELETE FROM spip_documents_rubriques WHERE id_document=$id_vignette");
			spip_query("DELETE FROM spip_documents_breves WHERE id_document=$id_vignette");
		}
	}
}
?>
