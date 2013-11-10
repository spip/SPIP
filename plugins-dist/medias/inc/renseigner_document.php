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
 * recuperer les infos distantes d'une url,
 * et renseigner pour une insertion en base
 * utilise une variable static car appellee plusieurs fois au cours du meme hit
 * (verification puis traitement)
 *
 * @param string $source
 * @return string
 */
function renseigner_source_distante($source){
	static $infos = array();
	if (isset($infos[$source]))
		return $infos[$source];
	
	include_spip('inc/distant');
	// on passe la source dans le pipeline, le premier plugin
	// qui est capable de renseigner complete
	// fichier et mode + tous les autres champs a son gout
	// ex : oembed
	$a = pipeline('renseigner_document_distant',array('source'=>$source));

	// si la source est encore la, en revenir a la
	// methode traditionnelle : chargement de l'url puis analyse
	if (!isset($a['fichier']) OR !isset($a['mode'])){
		if (!$a = recuperer_infos_distantes($a['source'])) {
			return _T('medias:erreur_chemin_distant',array('nom'=>$source));
		}
		# NB: dans les bonnes conditions (fichier autorise et pas trop gros)
		# $a['fichier'] est une copie locale du fichier
		unset($a['body']);
		$a['distant'] = 'oui';
		$a['mode'] = 'document';
		$a['fichier'] = set_spip_doc($source);
	}

	// stocker pour la seconde demande
	return $infos[$source] = $a;
}

/**
 * Renseigner les informations de taille et dimension d'un document
 * 
 * Récupère les informations de taille (largeur / hauteur / type_image / taille) d'un document
 * Utilise pour cela les fonctions du répertoire metadatas/*
 * 
 * Ces fonctions de récupérations peuvent retourner d'autres champs si ces champs sont définis
 * comme editable dans la déclaration de la table spip_documents
 * 
 * TODO Renommer cette fonction sans "_image"
 *
 * @param string $fichier 
 * 		Le fichier à examiner 
 * @param string $ext
 * 		L'extension du fichier à examiner
 * @return array|string $infos
 * 		Si c'est une chaine, c'est une erreur
 * 		Si c'est un tableau, l'ensemble des informations récupérées du fichier
 */
function renseigner_taille_dimension_image($fichier,$ext){

	$infos = array(
		'largeur'=>0,
		'hauteur'=>0,
		'type_image'=>'',
		'taille'=>0
	);
	
	// Quelques infos sur le fichier
	if (
		!$fichier
		OR !@file_exists($fichier)
		OR !$infos['taille'] = @intval(filesize($fichier))) {
			spip_log ("Echec copie du fichier $fichier");
			return _T('medias:erreur_copie_fichier',array('nom'=> $fichier));
	}

	// chercher une fonction de description
	$meta = array();
	if ($metadata = charger_fonction($ext,"metadata",true)){
		$meta = $metadata($fichier);
	}
	else {
		$media = Sql::getfetsel('media_defaut','spip_types_documents','extension='.Sql::quote($ext));
		if ($metadata = charger_fonction($media,"metadata",true)){
			$meta = $metadata($fichier);
		}
	}

	$meta = pipeline('renseigner_document',array('args'=>array('extension'=>$ext,'fichier'=>$fichier),'data' => $meta));

	include_spip('inc/filtres'); # pour objet_info()
	$editables = objet_info('document','champs_editables');
	foreach($meta as $m=>$v)
		if (isset($infos[$m]) OR in_array($m,$editables))
			$infos[$m] = $v;

	return $infos;
}

?>