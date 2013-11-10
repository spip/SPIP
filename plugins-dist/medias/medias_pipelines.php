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


if (!defined('_ECRIRE_INC_VERSION')) return;

function medias_detecter_fond_par_defaut($fond){
	// traiter le cas pathologique d'un upload de document ayant echoue
	// car trop gros
	if (empty($_GET) AND empty($_POST) AND empty($_FILES)
	AND isset($_SERVER["CONTENT_LENGTH"])
	AND strstr($_SERVER["CONTENT_TYPE"], "multipart/form-data;")) {
		include_spip('inc/getdocument');
		erreur_upload_trop_gros();
	}
  return $fond;
}


/**
 * A chaque insertion d'un nouvel objet editorial
 * auquel on a attache des documents, restituer l'identifiant
 * du nouvel objet cree sur les liaisons documents/objet,
 * qui ont ponctuellement un identifiant id_objet negatif.
 * cf. medias_affiche_gauche()
**/
function medias_post_insertion($flux){

	$objet    = objet_type($flux['args']['table']);
	$id_objet = $flux['args']['id_objet'];
	
	include_spip('inc/autoriser');
	
	if (autoriser('joindredocument', $objet, $id_objet)
	  AND $id_auteur = intval($GLOBALS['visiteur_session']['id_auteur'])){

		# cf. HACK medias_affiche_gauche()
		# rattrapper les documents associes a cet objet nouveau
		# ils ont un id = 0-id_auteur

		# utiliser l'api editer_lien pour les appels aux pipeline edition_lien
		include_spip('action/editer_liens');
		$liens = objet_trouver_liens(array('document'=>'*'),array($objet=>0-$id_auteur));
		foreach($liens as $lien){
			objet_associer(array('document'=>$lien['document']),array($objet=>$id_objet),$lien);
		}
		// un simple delete pour supprimer les liens temporaires
		Sql::delete("spip_documents_liens", array("id_objet = ".(0-$id_auteur),"objet=".Sql::quote($objet)));
	}

  return $flux;
}

/**
 * Configuration des contenus
 * @param array $flux
 * @return array
 */
function medias_affiche_milieu($flux){
	if ($flux["args"]["exec"] == "configurer_contenu") {
		$flux["data"] .=  recuperer_fond('prive/squelettes/inclure/configurer',array('configurer'=>'configurer_documents'));
	}
	return $flux;
}

function medias_configurer_liste_metas($config){
	$config['documents_objets'] = '';
	$config['documents_date'] = 'non';
	return $config;
}


function medias_post_edition($flux){
	// le serveur n'est pas toujours la
	$serveur = (isset($flux['args']['serveur']) ? $flux['args']['serveur'] : '');
	// si on ajoute un document, mettre son statut a jour
	if($flux['args']['action']=='ajouter_document'){
		include_spip('action/editer_document');
		// mettre a jour le statut si necessaire
		document_instituer($flux['args']['id_objet']);
	}
	// si on institue un objet, mettre ses documents lies a jour
	elseif ($flux['args']['table']!=='spip_documents'){
		$type = isset($flux['args']['type'])?$flux['args']['type']:objet_type($flux['args']['table']);
		// verifier d'abord les doublons !
		include_spip('inc/autoriser');
		if (autoriser('autoassocierdocument',$type,$flux['args']['id_objet'])){
			$table_objet = isset($flux['args']['table_objet'])?$flux['args']['table_objet']:table_objet($flux['args']['table'],$serveur);
			$marquer_doublons_doc = charger_fonction('marquer_doublons_doc','inc');
			$marquer_doublons_doc($flux['data'],$flux['args']['id_objet'],$type,id_table_objet($type, $serveur),$table_objet,$flux['args']['table'], '', $serveur);
		}

		if ($flux['args']['action']=='instituer' OR isset($flux['data']['statut'])) {
			$id = $flux['args']['id_objet'];
			$docs = array_map('reset',Sql::allfetsel('id_document','spip_documents_liens','id_objet='.intval($id).' AND objet='.Sql::quote($type)));
			include_spip('action/editer_document');
			foreach ($docs as $id_document)
				// mettre a jour le statut si necessaire
				document_instituer($id_document);
		}
	}
	else {
		if ($flux['args']['table']!=='spip_documents') {
			// verifier les doublons !
			$marquer_doublons_doc = charger_fonction('marquer_doublons_doc','inc');
			$marquer_doublons_doc($flux['data'],$flux['args']['id_objet'],$flux['args']['type'],id_table_objet($flux['args']['type'], $serveur),$flux['args']['table_objet'],$flux['args']['spip_table_objet'], '', $serveur);
		}
	}
	return $flux;
}

/**
 * Pipeline afficher_complement_objet
 * afficher le portfolio et ajout de document sur les fiches objet
 * sur lesquelles les medias ont ete activees
 * Pour les articles, on ajoute toujours !
 * 
 * @param  $flux
 * @return
 */
function medias_afficher_complement_objet($flux){
	if ($type=$flux['args']['type']
		AND $id=intval($flux['args']['id'])
	  AND (autoriser('joindredocument',$type,$id))) {
		$documenter_objet = charger_fonction('documenter_objet','inc');
		$flux['data'] .= $documenter_objet($id,$type);
	}
	return $flux;
}

/**
 * Pipeline affiche_gauche
 * Affiche le formulaire d'ajout de document sur le formulaire d'edition
 * d'un objet (lorsque cet objet peut recevoir des documents).
 *
 * HACK : Lors d'une premiere creation de l'objet, celui-ci n'ayant pas
 * encore d'identifiant tant que le formulaire d'edition n'est pas enregistre,
 * les liaisions entre les documents lies et l'objet a creer sauvegardent
 * un identifiant d'objet negatif de la valeur de id_auteur (l'auteur
 * connecte). Ces liaisons seront corrigees apres validation dans
 * medias_post_insertion()
 */
function medias_affiche_gauche($flux){
	if ($en_cours = trouver_objet_exec($flux['args']['exec'])
		AND $en_cours['edition']!==false // page edition uniquement
		AND $type = $en_cours['type']
		AND $id_table_objet = $en_cours['id_table_objet']
		// id non defini sur les formulaires de nouveaux objets
		AND (isset($flux['args'][$id_table_objet]) and $id = intval($flux['args'][$id_table_objet])
			// et justement dans ce cas, on met un identifiant negatif
		    OR $id = 0-$GLOBALS['visiteur_session']['id_auteur'])
	  AND autoriser('joindredocument',$type,$id)){
		$flux['data'] .= recuperer_fond('prive/objets/editer/colonne_document',array('objet'=>$type,'id_objet'=>$id));
	}

	return $flux;
}

function medias_document_desc_actions($flux){
	return $flux;
}

function medias_editer_document_actions($flux){
	return $flux;
}

function medias_renseigner_document_distant($flux){
	return $flux;
}

/**
 * Compter les documents dans un objet
 *
 * @param array $flux
 * @return array
 */
function medias_objet_compte_enfants($flux){
	if ($objet = $flux['args']['objet']
	  AND $id=intval($flux['args']['id_objet'])) {
		// juste les publies ?
		if (array_key_exists('statut', $flux['args']) and ($flux['args']['statut'] == 'publie')) {
			$flux['data']['document'] = Sql::countsel('spip_documents AS D JOIN spip_documents_liens AS L ON D.id_document=L.id_document', "L.objet=".Sql::quote($objet)."AND L.id_objet=".intval($id)." AND (D.statut='publie')");
		} else {
			$flux['data']['document'] = Sql::countsel('spip_documents AS D JOIN spip_documents_liens AS L ON D.id_document=L.id_document', "L.objet=".Sql::quote($objet)."AND L.id_objet=".intval($id)." AND (D.statut='publie' OR D.statut='prepa')");
		}
	}
	return $flux;
}

/**
 * Afficher le nombre de documents dans chaque rubrique
 *
 * @param array $flux
 * @return array
 */
function medias_boite_infos($flux){
	if ($flux['args']['type']=='rubrique'
	  AND $id_rubrique = $flux['args']['id']){
		if ($nb = Sql::countsel('spip_documents_liens',"objet='rubrique' AND id_objet=".intval($id_rubrique))){
			$nb = "<div>". singulier_ou_pluriel($nb, "medias:un_document", "medias:des_documents") . "</div>";
			if ($p = strpos($flux['data'],"<!--nb_elements-->"))
				$flux['data'] = substr_replace($flux['data'],$nb,$p,0);
		}
	}
	return $flux;
}
