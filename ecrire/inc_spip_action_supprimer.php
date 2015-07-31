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

include_ecrire("inc_charsets");	# pour le nom de fichier
include_ecrire("inc_abstract_sql");# spip_insert / spip_fetch...

// Effacer un doc (et sa vignette)
function spip_action_supprimer_dist() {

  global  $arg, $ancre, $redirect;

	$arg = intval($arg);
	$result = spip_query("SELECT id_vignette, fichier FROM spip_documents WHERE id_document=$arg");
	if ($row = spip_fetch_array($result)) {
		$fichier = $row['fichier'];
		$id_vignette = $row['id_vignette'];
		spip_query("DELETE FROM spip_documents
			WHERE id_document=$arg");
		spip_query("UPDATE spip_documents SET id_vignette=0
			WHERE id_vignette=$arg");
		spip_query("DELETE FROM spip_documents_articles
			WHERE id_document=$arg");
		spip_query("DELETE FROM spip_documents_rubriques
			WHERE id_document=$arg");
		spip_query("DELETE FROM spip_documents_breves
			WHERE id_document=$arg");
		@unlink($fichier);

		if ($id_vignette > 0) {
			$query = "SELECT id_vignette, fichier FROM spip_documents
				WHERE id_document=$id_vignette";
			$result = spip_query($query);
			if ($row = spip_fetch_array($result)) {
				$fichier = $row['fichier'];
				@unlink($fichier);
			}
			spip_query("DELETE FROM spip_documents
				WHERE id_document=$id_vignette");
			spip_query("DELETE FROM spip_documents_articles
				WHERE id_document=$id_vignette");
			spip_query("DELETE FROM spip_documents_rubriques
				WHERE id_document=$id_vignette");
			spip_query("DELETE FROM spip_documents_breves
				WHERE id_document=$id_vignette");
		}
	}

	redirige_par_entete(urldecode($redirect), $ancre ? "#$ancre" : '');
}
?>
