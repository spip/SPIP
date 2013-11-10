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

function formulaires_importer_sites_charger_dist(){

	if (!autoriser('importer', '_sites'))
		return false;

	return array(
		'fichier_import' => 0,
		'id_parent' => 0,
		'importer_statut_publie' => 0,
		'importer_les_tags' => 1,
	);
}

function formulaires_importer_sites_verifier_dist(){
	$erreurs = array();

	if (!_request('id_parent'))
		$erreurs['id_parent'] = _T('info_obligatoire');

	$fichier_ok = info_fichiers_import('fichier_import');
	if (!$fichier_ok){
		$erreurs['fichier_import'] = _T('sites:erreur_fichier_incorrect');
	}
	elseif (!charger_fonction('importer_bookmarks_'.$fichier_ok['format'],'action',true)){
		$erreurs['fichier_import'] = _T('sites:erreur_fichier_format_inconnu',array('fichier'=>"<tt>".$fichier_ok['name']."</tt>"));
	}

	return $erreurs;
}

function formulaires_importer_sites_traiter_dist(){
	$id_parent = intval(_request('id_parent'));
	$importer_statut_publie = _request('importer_statut_publie') ? true : false;
	$importer_tags = _request('importer_les_tags') ? true : false;
	$fichier_ok = info_fichiers_import('fichier_import');


	$importer_bookmarks = charger_fonction('importer_bookmarks_'.$fichier_ok['format'],'action');
	$nb = $importer_bookmarks($fichier_ok,$id_parent,$importer_statut_publie,$importer_tags);

	if (!$nb)
		$res = array('message_erreur'=>_T('sites:info_aucun_site_importe'));
	else
		$res = array('message_ok'=>singulier_ou_pluriel($nb,'sites:info_1_site_importe','sites:info_nb_sites_importes'));

	return $res;
}

function info_fichiers_import($name){
	static $fichier_ok = array();

	if (!isset($fichier_ok[$name])){
		if (sizeof($_FILES)<0
		  OR !isset($_FILES[$name])
		  OR !$_FILES[$name]['size']>0)
			return false;

		if ($_FILES[$name]['error']!=0)
			return false;

		$fichier_ok[$name] = array();
		$fichier_ok[$name]['name'] = $_FILES[$name]['name'];
		$fichier_ok[$name]['chemin'] = $_FILES[$name]['tmp_name'];

		// On récupère le contenu du fichier
		$fichier_ok[$name]['format'] = '';
		lire_fichier($fichier_ok[$name]['chemin'],$fichier_ok[$name]['contenu']);
		if (stripos($fichier_ok[$name]['contenu'], 'NETSCAPE-Bookmark-file')!==false)
			$fichier_ok[$name]['format'] = 'netscape';
	}

	return $fichier_ok[$name];
}