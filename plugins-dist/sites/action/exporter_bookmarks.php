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

function action_exporter_bookmarks_dist($arg=null){

	if (is_null($arg)){
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}

	if (autoriser('exporter','_bookmarks')){
		list($id_parent,$exporter_publie_seulement,$exporter_avec_mots_cles) = explode("-",$arg);
		$statut = ($exporter_publie_seulement?array('publie'):array('prop','publie'));

		$f = "bookmarks-".date('Y-m-d').".html";
		header('Content-Type: text/html');
		header("Content-Disposition: attachment; filename=\"$f\";");
		header("Content-Transfer-Encoding: 8bit");

		// fix for IE catching or PHP bug issue
		header("Pragma: public");
		header("Expires: 0"); // set expiration time
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		echo recuperer_fond("prive/transmettre/bookmarks",array('statut'=>$statut,'id_parent'=>intval($id_parent),'tags'=>$exporter_avec_mots_cles));
	}
}