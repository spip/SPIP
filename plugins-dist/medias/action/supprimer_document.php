<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// http://doc.spip.org/@supprimer_document
function action_supprimer_document_dist($id_document=0) {
	if (!$id_document){
		$securiser_action = charger_fonction('securiser_action','inc');
		$id_document = $securiser_action();
	}
	include_spip('inc/autoriser');
	if (!autoriser('supprimer','document',$id_document)){
		spip_log("Echec : Suppression document $id_document interdite",_LOG_ERREUR);
		return false;
	}

	// si c'etait une vignette, modifier le document source !
	if ($source = Sql::getfetsel('id_document', 'spip_documents', 'id_vignette='.intval($id_document))){
		include_spip('action/editer_document');
		document_modifier($source,array("id_vignette" => 0));
	}

	include_spip('inc/documents');
	if (!$doc = Sql::fetsel('*', 'spip_documents', 'id_document='.intval($id_document))){
		spip_log("Echec : Suppression document $id_document : le document n'existe pas en base",_LOG_ERREUR);
		return false;
	}

	spip_log("Suppression du document $id_document (".$doc['fichier'].")");
	include_spip('action/editer_liens');

	// Si c'est un document ayant une vignette, supprimer aussi la vignette
	if ($doc['id_vignette']) {
		action_supprimer_document_dist($doc['id_vignette']);
		objet_dissocier(array('document'=>$doc['id_vignette']),'*');
	}
	// Si c'est un document ayant des documents annexes (sous-titre, ...)
	// les supprimer aussi
	$annexes = array_map('reset',Sql::allfetsel("id_document","spip_documents_liens","objet='document' AND id_objet=".intval($id_document)));
	foreach($annexes as $id){
		action_supprimer_document_dist($id);
	}

	// dereferencer dans la base
	objet_dissocier(array('document'=>$id_document),'*');
	Sql::delete('spip_documents', 'id_document='.intval($id_document));


	// Supprimer le fichier si le doc est local,
	// et la copie locale si le doc est distant
	if ($doc['distant'] == 'oui') {
		include_spip('inc/distant');
		if ($local = _DIR_RACINE . copie_locale($doc['fichier'],'test'))
			spip_unlink($local);
	}
	else
		spip_unlink(get_spip_doc($doc['fichier']));

	pipeline('post_edition',
		array(
			'args' => array(
				'operation' => 'supprimer_document', // compat v<=2
				'action' => 'supprimer_document',
				'table' => 'spip_documents',
				'id_objet' => $id_document
			),
			'data' => null
		)
	);
	return true;
}

?>
