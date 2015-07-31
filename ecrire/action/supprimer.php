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

include_spip('inc/charsets');	# pour le nom de fichier
include_spip('base/abstract_sql');
include_spip('inc/actions');

// Effacer un doc (et sa vignette)
// http://doc.spip.org/@action_supprimer_dist
function action_supprimer_dist() {

	$var_f = charger_fonction('controler_action_auteur', 'inc');
	$var_f();

	$arg = _request('arg');

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
	global $redirect;
	supprimer_document_et_vignette(intval($arg));
	$redirect = rawurldecode($redirect);
	if (strpos($redirect, 'id_rubrique=')) {
		include_spip('inc/rubriques');
		calculer_rubriques();
	}
	redirige_par_entete($redirect);
}


// http://doc.spip.org/@action_supprimer_rubrique
function action_supprimer_rubrique($id_rubrique)
{
	spip_query("DELETE FROM spip_rubriques WHERE id_rubrique=$id_rubrique");
	include_spip('inc/rubriques');
	calculer_rubriques();
	calculer_langues_rubriques();

	// invalider les caches marques de cette rubrique
	include_spip('inc/invalideur');
	suivre_invalideur("id='id_rubrique/$id_rubrique'");

}

// http://doc.spip.org/@action_supprimer_auteur_rubrique
function action_supprimer_auteur_rubrique($arg)
{
	if (preg_match(",^\W*(\d+)\W+(\d+)$,", $arg, $r))
		spip_query("DELETE FROM spip_auteurs_rubriques WHERE id_auteur=".$r[1]." AND id_rubrique=" . $r[2]);
	else spip_log("action_supprimer_auteur_rubrique $arg pas compris");
}

function supprimer_document_et_vignette($arg)
{
	$result = spip_query("SELECT id_vignette, fichier FROM spip_documents WHERE id_document=$arg");
	if ($row = spip_fetch_array($result)) {
		$fichier = $row['fichier'];
		$id_vignette = $row['id_vignette'];
		spip_query("DELETE FROM spip_documents WHERE id_document=$arg");
		spip_query("UPDATE spip_documents SET id_vignette=0 WHERE id_vignette=$arg");
		spip_query("DELETE FROM spip_documents_articles WHERE id_document=$arg");
		spip_query("DELETE FROM spip_documents_rubriques WHERE id_document=$arg");
		spip_query("DELETE FROM spip_documents_breves WHERE id_document=$arg");
		@unlink($fichier);

		if ($id_vignette > 0) {
			$result = spip_query("SELECT id_vignette, fichier FROM spip_documents	WHERE id_document=$id_vignette");

			if ($row = spip_fetch_array($result)) {
				$fichier = $row['fichier'];
				@unlink($fichier);
			}
			spip_query("DELETE FROM spip_documents	WHERE id_document=$id_vignette");
			spip_query("DELETE FROM spip_documents_articles	WHERE id_document=$id_vignette");
			spip_query("DELETE FROM spip_documents_rubriques WHERE id_document=$id_vignette");
			spip_query("DELETE FROM spip_documents_breves WHERE id_document=$id_vignette");
		}
	}
}
?>
