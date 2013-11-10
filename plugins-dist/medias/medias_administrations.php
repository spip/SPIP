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
 * verifier et maj le statut des documents
 * @param bool $affiche
 * @return
 */
function medias_check_statuts($affiche = false){
	$trouver_table = charger_fonction('trouver_table','base');
	$desc = $trouver_table('documents');
	# securite, si jamais on arrive ici avant un upgrade de base
	if (!isset($desc['field']['statut']))
		return;

	// utiliser Sql::allfetsel pour clore la requete avant la mise a jour en base sur chaque doc (sqlite)
	// iterer par groupe de 100 pour ne pas exploser sur les grosses bases
	$docs = array_map('reset',Sql::allfetsel('id_document','spip_documents',"statut='0'",'','',"0,100"));
	while (count($docs)){
		include_spip('action/editer_document');
		foreach($docs as $id_document)
			// mettre a jour le statut si necessaire
			instituer_document($id_document);
		if ($affiche) echo " .";
	  $docs = array_map('reset',Sql::allfetsel('id_document','spip_documents',"statut='0'",'','',"0,100"));
	}
}

/**
 * Mise a jour de la BDD
 * @param string $nom_meta_base_version
 * @param string $version_cible
 */
function medias_upgrade($nom_meta_base_version,$version_cible){
	if (!isset($GLOBALS['meta'][$nom_meta_base_version])){
		$trouver_table = charger_fonction('trouver_table','base');
		if ($desc = $trouver_table('spip_documents')
		  AND !isset($desc['field']['statut']))
			ecrire_meta($nom_meta_base_version,'0.1.0');
	}

	$maj = array();
	$maj['create'] = array(
		array('maj_tables',array('spip_documents','spip_documents_liens','spip_types_documents')),
		array('creer_base_types_doc')
	);
	$maj['0.2.0'] = array(
		array('Sql::alter',"TABLE spip_documents ADD statut varchar(10) DEFAULT '0' NOT NULL"),
	);
	$maj['0.3.0'] = array(
		array('Sql::alter',"TABLE spip_documents ADD date_publication datetime DEFAULT '0000-00-00 00:00:00' NOT NULL"),
	);
	$maj['0.4.0'] = array(
		// recalculer tous les statuts en tenant compte de la date de publi des articles...
		array('medias_check_statuts',true),
	);
	$maj['0.5.0'] = array(
		array('Sql::alter',"TABLE spip_documents ADD brise tinyint DEFAULT 0"),
	);
	$maj['0.6.0'] = array(
		array('Sql::alter',"TABLE spip_types_documents ADD media varchar(10) DEFAULT 'file' NOT NULL"),
		array('creer_base_types_doc','','media'),
	);
	$maj['0.7.0'] = array(
		array('Sql::alter',"TABLE spip_documents ADD credits varchar(255) DEFAULT '' NOT NULL"),
	);
	$maj['0.10.0'] = array(
		array('Sql::alter',"TABLE spip_documents CHANGE fichier fichier TEXT NOT NULL DEFAULT ''"),
	);
	$maj['0.11.0'] = array(
		array('Sql::alter',"TABLE spip_documents CHANGE mode mode varchar(10) DEFAULT 'document' NOT NULL"),
	);
	$maj['0.11.0'] = array(
		array('Sql::alter',"TABLE spip_documents CHANGE mode mode varchar(10) DEFAULT 'document' NOT NULL"),
	);
	$maj['0.12.0'] = array(
		array('medias_maj_meta_documents'),
	);
	$maj['0.14.0'] = array(
		array('creer_base_types_doc','','media'),
	);
	$maj['0.15.0'] = array(
		array('creer_base_types_doc','','media'),
	);
	$maj['0.15.1'] = array(
		array('Sql::alter',"TABLE spip_documents CHANGE taille taille bigint"),
	);
	$maj['0.16.0'] = array(
		array('creer_base_types_doc','','media'),
	);

	$maj['1.0.0'] = array(
		// on cree le champ en defaut '?' pour reperer les nouveaux a peupler
		array('Sql::alter',"TABLE spip_documents ADD media varchar(10) DEFAULT '?' NOT NULL"),
		array('medias_peuple_media_document', 'media'),
		// puis on retablit le bon defaut
		array('Sql::alter',"TABLE spip_documents CHANGE media media varchar(10) DEFAULT 'file' NOT NULL"),
	);
	$maj['1.0.1'] = array(
		// puis on retablit le bon defaut
		array('Sql::alter',"TABLE spip_types_documents CHANGE media media_defaut varchar(10) DEFAULT 'file' NOT NULL"),
	);

	$maj['1.1.0'] = array(
		array('Sql::alter',"TABLE spip_documents_liens ADD INDEX id_objet (id_objet)"),
		array('Sql::alter',"TABLE spip_documents_liens ADD INDEX objet (objet)"),
	);
	$maj['1.1.1'] = array(
		array('creer_base_types_doc'),
	);
	// reparer les media sur les file suite a upgrade rate depuis SPIP 2.x
	$maj['1.2.0'] = array(
		// on remet en ? tous les media=file
		array('Sql::updateq',"spip_documents",array('media'=>'?'),"media='file'"),
		// et on repeuple
		array('medias_peuple_media_document'),
	);
	$maj['1.2.1'] = array(
		array('creer_base_types_doc'),
	);
	$maj['1.2.3'] = array(
		// ajout de mbtiles
		array('creer_base_types_doc'),
		// reparer les clauses DEFAULT manquantes de maniere reccurente sur cette table
		array('Sql::alter',"TABLE spip_documents CHANGE extension extension VARCHAR(10) DEFAULT '' NOT NULL"),
		array('Sql::alter',"TABLE spip_documents CHANGE credits credits varchar(255) DEFAULT '' NOT NULL"),
		array('Sql::alter',"TABLE spip_documents CHANGE statut statut varchar(10) DEFAULT '0' NOT NULL"),
	);
	$maj['1.2.4'] = array(
		// ajout de tar
		array('creer_base_types_doc')
	);
	
	include_spip('base/upgrade');
	include_spip('base/medias');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);

	medias_check_statuts();
}

/**
 * Maj des meta documents
 */
function medias_maj_meta_documents(){
	$config = array();
	if (isset($GLOBALS['meta']['documents_article']) AND $GLOBALS['meta']['documents_article']!=='non')
		$config[] = 'spip_articles';
	if (isset($GLOBALS['meta']['documents_rubrique']) AND $GLOBALS['meta']['documents_rubrique']!=='non')
		$config[] = 'spip_rubriques';
	ecrire_meta('documents_objets',implode(',',$config));
}

function medias_peuple_media_document($champ_media="media_defaut"){
	$res = Sql::select("DISTINCT extension","spip_documents","media=".Sql::quote('?'));
	while($row = Sql::fetch($res)){
		// attention ici c'est encore le champ media, car on le renomme juste apres
		$media = Sql::getfetsel($champ_media,'spip_types_documents','extension='.Sql::quote($row['extension']));
		Sql::updateq('spip_documents',array('media'=>$media),"media=".Sql::quote('?').' AND extension='.Sql::quote($row['extension']));
		if (time() >= _TIME_OUT)
			return;
	}
}

/*
function medias_install($action,$prefix,$version_cible){
	$version_base = $GLOBALS[$prefix."_base_version"];
	switch ($action){
		case 'test':
			# plus necessaire si pas de bug :p
			# medias_check_statuts();
			return (isset($GLOBALS['meta'][$prefix."_base_version"])
				AND version_compare($GLOBALS['meta'][$prefix."_base_version"],$version_cible,">="));
			break;
		case 'install':
			medias_upgrade('medias_base_version',$version_cible);
			break;
		case 'uninstall':
			# pas de deinstallation sur les documents pour le moment, trop dangereux
			# medias_vider_tables();
			break;
	}
}
*/
