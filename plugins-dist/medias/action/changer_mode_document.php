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
 * Cette action permet de basculer du mode image au mode document et vice versa
 *
 * http://doc.spip.org/@action_changer_mode_document_dist
 *
 * @param int $id_document
 * @param string $mode
 * @return void
 */
function action_changer_mode_document_dist($id_document=null, $mode=null){
	if (is_null($id_document) OR is_null($mode)){
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();

		if (!preg_match(",^(\d+)\W(\w+)$,", $arg, $r))
			spip_log("action_changer_mode_document $arg pas compris");
		else {
			array_shift($r);
	    list($id_document, $mode) = $r;
		}
	}

	if ($id_document
		AND include_spip('inc/autoriser')
	  AND autoriser('modifier','document',$id_document))
		action_changer_mode_document_post($id_document, $mode);
}

// http://doc.spip.org/@action_changer_mode_document_post
function action_changer_mode_document_post($id_document, $mode){
	// - id_document le doc a modifier
	// - mode le mode a lui donner
	if ($id_document = intval($id_document)
	AND in_array($mode, array('vignette', 'image', 'document'))) {
		include_spip('action/editer_document');
		document_modifier($id_document,array('mode'=>$mode));
	}
}
?>
