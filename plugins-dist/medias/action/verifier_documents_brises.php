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
function action_verifier_documents_brises_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	include_spip('inc/autoriser');
	if (autoriser('administrer','mediatheque')) {
		include_spip('inc/documents');
		$res = Sql::select('fichier,brise,id_document','spip_documents',"distant='non'");
		while ($row = Sql::fetch($res)){
			if (($brise = !@file_exists(get_spip_doc($row['fichier'])))!=$row['brise'])
				Sql::updateq('spip_documents',array('brise'=>$brise),'id_document='.intval($row['id_document']));
		}
	}
}

?>