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


// Comme son nom ne l'indique pas cette action consiste a SUPPRIMER un document

// http://doc.spip.org/@action_documenter_dist
function action_documenter_dist()
{
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	if (!preg_match(",^(-?)(\d+)\W(\w+)(\W(\d+))?$,", $arg, $r))
		spip_log("action_documenter $arg pas compris");
	else action_documenter_post($r);
}

// http://doc.spip.org/@supprimer_lien_document
function supprimer_lien_document($id_document, $objet, $id_objet) {
	if (!$id_document = intval($id_document))
		return false;

	// D'abord on ne supprime pas, on dissocie
	sql_delete("spip_documents_liens",
		$z = "id_objet=".intval($id_objet)." AND objet=".sql_quote($objet)." AND id_document=".$id_document);

	// Si c'est une vignette, l'eliminer du document auquel elle appartient
	sql_updateq("spip_documents", array('id_vignette' => 0), "id_vignette=".$id_document);

	pipeline('post_edition',
		array(
			'args' => array(
				'operation' => 'delier_document',
				'table' => 'spip_documents',
				'id_objet' => $id_document,
				'objet' => $objet,
				'id' => $id_objet
			),
			'data' => null
		)
	);

	// On supprime ensuite s'il est orphelin
	// (autorisation verifiee dans l'action)
	$supprimer_document = charger_fonction('supprimer_document','action');
	return $supprimer_document($id_document);
}

// http://doc.spip.org/@action_documenter_post
function action_documenter_post($r)
{
	// - sign indique le portfolio image ou document, dans le cas de
	// la page exec=articles
	// - id est l'id_objet (id_article ou id_rubrique etc)
	// - type est 'article' (ou 'rubrique')
	// - id_document le doc a supprimer ou a delier de l'objet
	//   SI VIDE, on supprime tous les documents du type SIGN
	//   (bouton "supprimer tous les documents")
	list(, $sign, $id, $type, ,$id_document) = $r;

	if ($id_document) {
		supprimer_lien_document($id_document, $type, $id);
	}
	else {
		$obj = "id_objet=".intval($id)." AND objet=".sql_quote($type);
		$typdoc = sql_in('docs.extension', array('gif', 'jpg', 'png'), $sign  ? '' : 'NOT');

		$s = sql_select('docs.id_document AS id_doc', "spip_documents AS docs LEFT JOIN spip_documents_liens AS l ON l.id_document=docs.id_document", "$obj AND docs.mode='document' AND $typdoc");
		while ($t = sql_fetch($s)) {
			supprimer_lien_document($t['id_doc'], $type, $id);
		}
	}

	if ($type == 'rubrique') {
		include_spip('inc/rubriques');
		depublier_branche_rubrique_if($id);
	}
}
?>
