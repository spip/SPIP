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
 * Dissocier un document
 * $arg fournit les arguments de la fonction dissocier_document
 * sous la forme
 * $id_objet-$objet-$document-suppr-safe
 *
 * 4eme arg : suppr = true, false sinon
 * 5eme arg : safe = true, false sinon
 * 
 * @return void
 */
function action_dissocier_document_dist($arg=null){
	if(is_null($arg)){
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}

	// attention au cas ou id_objet est negatif !
	if (strncmp($arg,'-',1)==0){
		$arg = explode('-',substr($arg,1));
		list($id_objet, $objet, $document) = $arg;
		$id_objet = -$id_objet;
	}
	else {
		$arg = explode('-',$arg);
		list($id_objet, $objet, $document) = $arg;
	}

	$suppr=false;
	if (count($arg)>3 AND $arg[3]=='suppr')
		$suppr = true;
	if (count($arg)>4 AND $arg[4]=='safe')
		$check = true;
	if ($id_objet=intval($id_objet)
		AND (
			($id_objet<0 AND $id_objet==-$GLOBALS['visiteur_session']['id_auteur'])
			OR autoriser('modifier',$objet,$id_objet)
		))
		dissocier_document($document, $objet, $id_objet, $suppr, $check);
	else
		spip_log("Interdit de modifier $objet $id_objet","spip");
}

/**
 * Supprimer un lien entre un document et un objet
 *
 * @param int $id_document
 * @param string $objet
 * @param int $id_objet
 * @param bool $supprime
 *   si true, le document est supprime si plus lie a aucun objet
 * @param bool $check
 *   si true, on verifie les documents references dans le texte de l'objet
 *   et on les associe si pas deja fait
 * @return bool
 */
function supprimer_lien_document($id_document, $objet, $id_objet, $supprime = false, $check = false) {
	if (!$id_document = intval($id_document))
		return false;

	// D'abord on ne supprime pas, on dissocie
	include_spip('action/editer_liens');
	objet_dissocier(array('document'=>$id_document),array($objet=>$id_objet));

	// Si c'est une vignette, l'eliminer du document auquel elle appartient
	// cas tordu peu probable
	Sql::updateq("spip_documents", array('id_vignette' => 0), "id_vignette=".$id_document);

	// verifier son statut apres une suppression de lien
	include_spip('action/editer_document');
	document_instituer($id_document);

	pipeline('post_edition',
		array(
			'args' => array(
				'operation' => 'delier_document', // compat v<=2
				'action' => 'delier_document',
				'table' => 'spip_documents',
				'id_objet' => $id_document,
				'objet' => $objet,
				'id' => $id_objet
			),
			'data' => null
		)
	);

	if ($check) {
		// si demande, on verifie que ses documents vus sont bien lies !
		$spip_table_objet = table_objet_sql($objet);
		$table_objet = table_objet($objet);
		$id_table_objet = id_table_objet($objet,$serveur);
		$champs = Sql::fetsel('*',$spip_table_objet,addslashes($id_table_objet)."=".intval($id_objet));

		$marquer_doublons_doc = charger_fonction('marquer_doublons_doc','inc');
		$marquer_doublons_doc($champs,$id_objet,$objet,$id_table_objet,$table_objet,$spip_table_objet, '', $serveur);
	}

	// On supprime ensuite s'il est orphelin
	// et si demande
	// ici on ne bloque pas la suppression d'un document rattache a un autre
	if ($supprime AND !Sql::countsel('spip_documents_liens', "objet!='document' AND id_document=".$id_document)){
		$supprimer_document = charger_fonction('supprimer_document','action');
		return $supprimer_document($id_document);
	}
}

/**
 * Dissocier un ou des documents
 *
 * @param int|string $document
 *   id_document a dissocier
 *   I/image pour dissocier les images en mode Image
 *   I/document pour dissocier les images en mode document
 *   D/document pour dissocier les documents non image en mode document
 * @param  $objet
 *   objet duquel dissocier
 * @param  $id_objet
 *   id_objet duquel dissocier
 * @param bool $supprime
 *   supprimer les documents orphelins apres dissociation
 * @param bool $check
 *   verifier le texte des documents et relier les documents references dans l'objet
 * @return void
 */
function dissocier_document($document, $objet, $id_objet, $supprime = false, $check = false){
	if ($id_document=intval($document)) {
		supprimer_lien_document($id_document, $objet, $id_objet, $supprime, $check);
	}
	else {
		list($image,$mode) = explode('/',$document);
		$image = ($image=='I');
		$typdoc = Sql::in('docs.extension', array('gif', 'jpg', 'png'), $image  ? '' : 'NOT');

		$obj = "id_objet=".intval($id_objet)." AND objet=".Sql::quote($objet);

		$s = Sql::select('docs.id_document',
			"spip_documents AS docs LEFT JOIN spip_documents_liens AS l ON l.id_document=docs.id_document",
			"$obj AND vu='non' AND docs.mode=".Sql::quote($mode)." AND $typdoc");
		while ($t = SQl::fetch($s)) {
			supprimer_lien_document($t['id_document'], $objet, $id_objet, $supprime, $check);
		}
	}

	// pas tres generique ca ...
	if ($objet == 'rubrique') {
		include_spip('inc/rubriques');
		depublier_branche_rubrique_if($id_objet);
	}
}
?>
