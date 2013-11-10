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
 * Action editer_document
 *
 * @param int $arg
 * @return array
 */
function action_editer_document_dist($arg=null) {

	if (is_null($arg)){
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}

	// Envoi depuis le formulaire de creation d'un document
	if (!$id_document = intval($arg)) {
		$id_document = document_inserer();
	}

	if (!$id_document)
		return array(0,''); // erreur

	$err = document_modifier($id_document);

	return array($id_document,$err);
}

/**
 * Creer un nouveau document
 *
 * @return int
 */
function document_inserer() {

	$champs = array(
		'statut' => 'prop',
		'date' => 'NOW()',
	);

	// Envoyer aux plugins
	$champs = pipeline('pre_insertion',
		array(
			'args' => array(
				'table' => 'spip_documents',
			),
			'data' => $champs
		)
	);
	$id_document = Sql::insertq("spip_documents", $champs);
	pipeline('post_insertion',
		array(
			'args' => array(
				'table' => 'spip_documents',
				'id_objet' => $id_document
			),
			'data' => $champs
		)
	);

	return $id_document;
}


/**
 * Enregistre une revision de document.
 * $set est un contenu (par defaut on prend le contenu via _request())
 *
 * @param int $id_document
 * @param array|bool $set
 */
function document_modifier($id_document, $set=false) {

	include_spip('inc/modifier');
	include_spip('inc/filtres');
	
	// champs normaux
	$champs = collecter_requests(
		// white list
		objet_info('document','champs_editables'),
		// black list
		array('parents', 'ajout_parents'),
		// donnees eventuellement fournies
		$set
	);


	$invalideur = "";
	$indexation = false;

	// Si le document est publie, invalider les caches et demander sa reindexation
	$t = Sql::getfetsel("statut", "spip_documents", 'id_document='.intval($id_document));
	if ($t == 'publie') {
		$invalideur = "id='id_document/$id_document'";
		$indexation = true;
	}
	
	$ancien_fichier = "";
	// si le fichier est modifie, noter le nom de l'ancien pour faire le menage
	if (isset($champs['fichier'])){
		$ancien_fichier = Sql::getfetsel('fichier','spip_documents','id_document='.intval($id_document));
	}

	if ($err = objet_modifier_champs('document', $id_document,
		array(
			'invalideur' => $invalideur,
			'indexation' => $indexation
		),
		$champs))
		return $err;

	// nettoyer l'ancien fichier si necessaire
	if ($champs['fichier'] // un plugin a pu interdire la modif du fichier en virant le champ
	 AND $ancien_fichier // on avait bien note le nom du fichier avant la modif
	 AND $ancien_fichier!==$champs['fichier'] // et il a ete modifie
	 AND @file_exists($f = get_spip_doc($ancien_fichier)))
	 	spip_unlink($f);

	// Changer le statut du document ?
	// le statut n'est jamais fixe manuellement mais decoule de celui des objets lies
	$champs = collecter_requests(array('parents','ajouts_parents'),array(),$set);
	if(document_instituer($id_document,$champs)) {

		//
		// Post-modifications
		//
	
		// Invalider les caches
		include_spip('inc/invalideur');
		suivre_invalideur("id='id_document/$id_document'");	
	}

}


/**
 * determiner le statut d'un document : prepa/publie
 * si on trouve un element joint sans champ statut ou avec un statut='publie' alors le doc est publie aussi
 *
 * @param int $id_document
 * @param array $champs
 * @return bool
 */
function document_instituer($id_document,$champs=array()){
	
	$statut=isset($champs['statut'])?$champs['statut']:null;
	$date_publication = isset($champs['date_publication'])?$champs['date_publication']:null;
	if (isset($champs['parents']))
		medias_revision_document_parents($id_document,$champs['parents']);
	if (isset($champs['ajout_parents']))
		medias_revision_document_parents($id_document,$champs['ajout_parents'],true);
	
	$row = Sql::fetsel("statut,date_publication", "spip_documents", "id_document=$id_document");
	$statut_ancien = $row['statut'];
	$date_publication_ancienne = $row['date_publication'];

	/* Autodetermination du statut si non fourni */
	if (is_null($statut)){
		$statut = 'prepa';

		$trouver_table = charger_fonction('trouver_table','base');
		$res = Sql::select('id_objet,objet','spip_documents_liens',"objet!='document' AND id_document=".intval($id_document));
		// dans 10 ans, ca nous fera un bug a corriger vers 2018
		// penser a ouvrir un ticket d'ici la :p
		$date_publication=time()+10*365*24*3600;
		include_spip('base/objets');
		while($row = Sql::fetch($res)){
			if (
				// cas particulier des rubriques qui sont publiees des qu'elles contiennent un document !
			  $row['objet']=='rubrique'
				// ou si objet publie selon sa declaration
			  OR objet_test_si_publie($row['objet'],$row['id_objet'])){
				$statut = 'publie';
				$date_publication=0;
				continue;
			}
			// si pas publie, et article, il faut checker la date de post-publi eventuelle
			elseif ($row['objet']=='article'
			  AND $row2 = Sql::fetsel('date','spip_articles','id_article='.intval($row['id_objet'])." AND statut='publie'")){
				$statut = 'publie';
				$date_publication = min($date_publication,strtotime($row2['date']));
			}
		}
		$date_publication = date('Y-m-d H:i:s',$date_publication);
		if ($statut=='publie' AND $statut_ancien=='publie' AND $date_publication==$date_publication_ancienne)
			return false;
		if ($statut!='publie' AND $statut_ancien!='publie' AND $statut_ancien!='0')
			return false;
	}
	if ($statut!==$statut_ancien
	  OR $date_publication!=$date_publication_ancienne){
		Sql::updateq('spip_documents',array('statut'=>$statut,'date_publication'=>$date_publication),'id_document='.intval($id_document));
		if ($statut!==$statut_ancien){
			$publier_rubriques = Sql::allfetsel('id_objet','spip_documents_liens',"objet='rubrique' AND id_document=".intval($id_document));
			if (count($publier_rubriques)){
				include_spip('inc/rubriques');
				foreach($publier_rubriques as $r)
					calculer_rubriques_if($r['id_objet'],array('statut'=>$statut),$statut_ancien,false);
			}
		}
		return true;
	}
	return false;
}


/**
 * Revision des parents d'un document
 * chaque parent est liste au format objet|id_objet
 *
 * @param int $id_document
 * @param array $parents
 * @param bool $ajout
 */
function medias_revision_document_parents($id_document, $parents=null, $ajout=false){
	if (!is_array($parents))
		return;
	
	$insertions = array();
	$objets_parents = array(); // array('article'=>array(12,23))
	
	// au format objet|id_objet
	foreach($parents as $p){
		$p = explode('|',$p);
		if (preg_match('/^[a-z0-9_]+$/i', $objet=$p[0])
		  AND $p[1]=intval($p[1])){ // securite
			$objets_parents[$p[0]][] = $p[1];
		}
	}
	
	include_spip('action/editer_liens');
	// les liens actuels
	$liens = objet_trouver_liens(array('document'=>$id_document),'*');
	$deja_parents = array();
	// si ce n'est pas un ajout, il faut supprimer les liens actuels qui ne sont pas dans $objets_parents
	if (!$ajout){
		foreach($liens as $k=>$lien)
			if (!isset($objets_parents[$lien['objet']]) OR !in_array($lien['id_objet'],$objets_parents[$lien['objet']])) {
				objet_dissocier(array('document'=>$id_document),array($lien['objet']=>$lien['id_objet']));
				unset($liens[$k]);
			}
			else $deja_parents[$lien['objet']][] = $lien['id_objet'];
	}

	objet_associer(array('document'=>$id_document),$objets_parents);

}


// obsoletes
function insert_document() {
	return document_inserer();
}
function document_set($id_document, $set=false) {
	return document_modifier($id_document, $set);
}
function instituer_document($id_document,$champs=array()){
	return document_instituer($id_document,$champs);
}
function revision_document($id_document, $c=false) {
	return document_modifier($id_document,$c);
}

?>
