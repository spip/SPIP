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

/**
 * Verifier tous les fichiers brises
 *
 */
function action_verifier_documents_liens_dist($id_document=null) {

	if (is_null($id_document)){
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$id_document = $securiser_action();
	}

	$id_document = ($id_document=='*')?'*':intval($id_document);
	include_spip('action/editer_liens');
	objet_optimiser_liens(array('document'=>$id_document),'*');

}

?>