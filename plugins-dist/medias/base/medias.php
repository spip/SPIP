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

/**
 * Interfaces des tables breves pour le compilateur
 *
 * @param array $interfaces
 * @return array
 */
function medias_declarer_tables_interfaces($interfaces) {
	$interfaces['table_des_tables']['documents']='documents';
	$interfaces['table_des_tables']['types_documents']='types_documents';

	$interfaces['exceptions_des_tables']['documents']['type_document']=array('types_documents', 'titre');
	$interfaces['exceptions_des_tables']['documents']['extension_document']=array('types_documents', 'extension');
	$interfaces['exceptions_des_tables']['documents']['mime_type']=array('types_documents', 'mime_type');
	$interfaces['exceptions_des_tables']['documents']['media_document']=array('types_documents', 'media');

	$interfaces['exceptions_des_jointures']['spip_documents']['id_forum']=array('spip_documents_liens','id_forum');
	$interfaces['exceptions_des_jointures']['spip_documents']['vu']=array('spip_documents_liens', 'vu');
	$interfaces['table_date']['types_documents']='date';

	$interfaces['table_des_traitements']['FICHIER']['documents']= 'get_spip_doc(%s)';

	return $interfaces;
}


/**
 * Table principale spip_documents et spip_types_documents
 *
 * @param array $tables_principales
 * @return array
 */
function medias_declarer_tables_principales($tables_principales) {

	$spip_types_documents = array(
			"extension"	=> "varchar(10) DEFAULT '' NOT NULL",
			"titre"	=> "text DEFAULT '' NOT NULL",
			"descriptif"	=> "text DEFAULT '' NOT NULL",
			"mime_type"	=> "varchar(100) DEFAULT '' NOT NULL",
			"inclus"	=> "ENUM('non', 'image', 'embed') DEFAULT 'non'  NOT NULL",
			"upload"	=> "ENUM('oui', 'non') DEFAULT 'oui'  NOT NULL",
			"media_defaut" => "varchar(10) DEFAULT 'file' NOT NULL",
			"maj"	=> "TIMESTAMP");

	$spip_types_documents_key = array(
			"PRIMARY KEY"	=> "extension",
			"KEY inclus"	=> "inclus");

	$tables_principales['spip_types_documents']	=
		array('field' => &$spip_types_documents, 'key' => &$spip_types_documents_key);

	return $tables_principales;
}

/**
 * Table des liens documents-objets spip_documents_liens
 * @param array $tables_auxiliaires
 * @return array
 */
function medias_declarer_tables_auxiliaires($tables_auxiliaires) {

	$spip_documents_liens = array(
			"id_document"	=> "bigint(21) DEFAULT '0' NOT NULL",
			"id_objet"	=> "bigint(21) DEFAULT '0' NOT NULL",
			"objet"	=> "VARCHAR (25) DEFAULT '' NOT NULL",
			"vu"	=> "ENUM('non', 'oui') DEFAULT 'non' NOT NULL");

	$spip_documents_liens_key = array(
			"PRIMARY KEY"		=> "id_document,id_objet,objet",
			"KEY id_document"	=> "id_document",
			"KEY id_objet"	=> "id_objet",
			"KEY objet"	=> "objet",
	);

	$tables_auxiliaires['spip_documents_liens'] = array(
		'field' => &$spip_documents_liens,
		'key' => &$spip_documents_liens_key);

	return $tables_auxiliaires;
}

/**
 * Declarer le surnom des breves
 *
 * @param array $surnoms
 * @return array
 */
function medias_declarer_tables_objets_surnoms($surnoms) {
	$surnoms['type_document'] = "types_documents"; # hum
	#$surnoms['extension'] = "types_documents"; # hum
	#$surnoms['type'] = "types_documents"; # a ajouter pour id_table_objet('type')=='extension' ?
	return $surnoms;
}

function medias_declarer_tables_objets_sql($tables){
	$tables['spip_articles']['champs_versionnes'][] = 'jointure_documents';
	$tables['spip_documents'] = array(
		'table_objet_surnoms'=>array('doc','img','emb'),
		'type_surnoms' => array(),
		'url_voir' => 'document_edit',
		'url_edit' => 'document_edit',
		'page'=>'',
		'texte_retour' => 'icone_retour',
		'texte_objets' => 'medias:objet_documents',
		'texte_objet' => 'medias:objet_document',
		'texte_modifier' => 'medias:info_modifier_document',
		'info_aucun_objet'=> 'medias:aucun_document',
		'info_1_objet' => 'medias:un_document',
		'info_nb_objets' => 'medias:des_documents',
		'titre' => "CASE WHEN length(titre)>0 THEN titre ELSE fichier END as titre, '' AS lang",
		'date' => 'date',
		'principale'=>'oui',
		'field' => array(
			"id_document"	=> "bigint(21) NOT NULL",
			"id_vignette"	=> "bigint(21) DEFAULT '0' NOT NULL",
			"extension"	=> "VARCHAR(10) DEFAULT '' NOT NULL",
			"titre"	=> "text DEFAULT '' NOT NULL",
			"date"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
			"descriptif"	=> "text DEFAULT '' NOT NULL",
			"fichier"	=> "text NOT NULL DEFAULT ''",
			"taille"	=> "bigint",
			"largeur"	=> "integer",
			"hauteur"	=> "integer",
			"media" => "varchar(10) DEFAULT 'file' NOT NULL",
			"mode"	=> "varchar(10) DEFAULT 'document' NOT NULL",
			"distant"	=> "VARCHAR(3) DEFAULT 'non'",
			"statut" => "varchar(10) DEFAULT '0' NOT NULL",
			"credits" => "varchar(255) DEFAULT '' NOT NULL",
			"date_publication" => "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
			"brise" => "tinyint DEFAULT 0",
			"maj"	=> "TIMESTAMP"
		),
		'key' => array(
			"PRIMARY KEY"	=> "id_document",
			"KEY id_vignette"	=> "id_vignette",
			"KEY mode"	=> "mode",
			"KEY extension"	=> "extension"
		),
		'join' => array(
			"id_document"=>"id_document",
			"extension"=>"extension"
		),
		'statut'=> array(
			array(
				'champ' => 'statut',
				'publie' => 'publie',
				'previsu' => 'publie,prop,prepa',
				'post_date' => 'date_publication',
				'exception' => array('statut','tout')
			)
		),
		'tables_jointures' => array('types_documents'),
		'rechercher_champs' => array(
			'titre' => 3, 'descriptif' => 1, 'fichier' => 1, 'credits' => 1,
		),
		'champs_editables' => array('titre', 'descriptif', 'date', 'taille', 'largeur','hauteur','mode','credits','fichier','distant','extension', 'id_vignette', 'media'),
		'champs_versionnes' => array('id_vignette', 'titre', 'descriptif', 'hauteur', 'largeur', 'mode','credits','distant'),
		'modeles' => array('document','doc','img','emb','image','video','text','audio','application'),
	);

	// jointures sur les forum pour tous les objets
	$tables[]['tables_jointures'][]= 'documents_liens';

	// recherche jointe sur les documents pour les articles et rubriques
	$tables['spip_articles']['rechercher_jointures']['document'] = array('titre' => 2, 'descriptif' => 1);
	$tables['spip_rubriques']['rechercher_jointures']['document'] = array('titre' => 2, 'descriptif' => 1);
	return $tables;
}

/**
 * Creer la table des types de document
 *
 * http://doc.spip.org/@creer_base_types_doc
 *
 * @param string $serveur
 * @return void
 */
function creer_base_types_doc($serveur='', $champ_media='media_defaut')
{
	global $tables_images, $tables_sequences, $tables_documents, $tables_mime;
	include_spip('base/typedoc');

	// charger en memoire tous les types deja definis pour limiter les requettes
	$rows = Sql::allfetsel('mime_type,titre,inclus,extension,'.$champ_media.',upload,descriptif','spip_types_documents','','','','','',$serveur);
	$deja = array();
	foreach ($rows as $k=>$row){
		$deja[$row['extension']] = &$rows[$k];
	}

	$insertions = array();
	$updates = array();

	foreach ($tables_mime as $extension => $type_mime) {
		if (isset($tables_images[$extension])) {
			$titre = $tables_images[$extension];
			$inclus='image';
		}
		else if (isset($tables_sequences[$extension])) {
			$titre = $tables_sequences[$extension];
			$inclus='embed';
		}
		else {
			$inclus='non';
			if (isset($tables_documents[$extension]))
				$titre = $tables_documents[$extension];
			else
				$titre = '';
		}

		// type de media
	  $media = "file";
	  if (preg_match(",^image/,",$type_mime) OR in_array($type_mime,array('application/illustrator')))
		  $media = "image";
	  elseif (preg_match(",^audio/,",$type_mime))
		  $media = "audio";
	  elseif (preg_match(",^video/,",$type_mime) OR in_array($type_mime,array('application/ogg','application/x-shockwave-flash','application/mp4')))
		  $media = "video";

		$set = array(
		        'mime_type' => $type_mime,
		        'titre' => $titre,
		        'inclus' => $inclus,
		        'extension' => $extension,
		        $champ_media => $media,
		        'upload' => 'oui',
		        'descriptif' => '',
					);
		if (!isset($deja[$extension])){
			$insertions[] = $set;
		}
		elseif (array_diff($deja[$extension],$set)){
			$updates[$extension] = $set;
		}
	}

	if (count($updates)){
		foreach ($updates as $extension=>$set){
			Sql::updateq('spip_types_documents',$set,'extension='.Sql::quote($extension));
		}
	}

	if ($insertions)
		Sql::insertq_multi('spip_types_documents', $insertions, '', $serveur);

}



/**
 * Optimiser la base de donnee en supprimant les liens orphelins
 *
 * @param int $n
 * @return int
 */
function medias_optimiser_base_disparus($flux){

	include_spip('action/editer_liens');
	// optimiser les liens morts :
	// entre documents vers des objets effaces
	// depuis des documents effaces
	$flux['data'] += objet_optimiser_liens(array('document'=>'*'),'*');
	// on ne nettoie volontairement pas automatiquement les documents orphelins

	return $flux;
}

?>