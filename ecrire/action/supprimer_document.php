<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

// http://doc.spip.org/@supprimer_document
function action_supprimer_document_dist($id_document) {
	include_spip('inc/autoriser');
	if (!autoriser('supprimer','document',$id_document))
		return false;

	include_spip('inc/documents');
	if (!$doc = sql_fetsel('*', 'spip_documents', 'id_document='.$id_document))
		return false;

	spip_log("Suppression du document $id_document (".$doc['fichier'].")");

	// Si c'est un document ayant une vignette, supprimer aussi la vignette
	if ($doc['id_vignette']) {
		action_supprimer_document_dist($doc['id_vignette']);
		sql_delete('spip_documents_liens', 'id_document='.$doc['id_vignette']);
	}

	// Supprimer le fichier si le doc est local,
	// et la copie locale si le doc est distant
	if ($doc['distant'] == 'oui') {
		include_spip('inc/distant');
		if ($local = copie_locale($doc['fichier'],'test'))
			spip_unlink($local);
	}
	else spip_unlink(get_spip_doc($doc['fichier']));

	sql_delete('spip_documents', 'id_document='.$id_document);

	pipeline('post_edition',
		array(
			'args' => array(
				'operation' => 'supprimer_document',
				'table' => 'spip_documents',
				'id_objet' => $id_document
			),
			'data' => null
		)
	);
}

?>
