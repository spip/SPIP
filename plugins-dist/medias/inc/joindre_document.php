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
 * Recuperer le nom du fichier selon le mode d'upload choisi
 * et mettre cela au format $_FILES
 * 
 * Renvoie une liste de fichier ou un message en cas d'erreur
 *
 * @return string/array
 */
function joindre_trouver_fichier_envoye(){
	static $files = array();
	// on est appele deux fois dans un hit, resservir ce qu'on a trouve a la verif
	// lorsqu'on est appelle au traitement
	
	if (count($files))
		return $files;
	
	if (_request('joindre_upload')){
		$post = isset($_FILES) ? $_FILES : $GLOBALS['HTTP_POST_FILES'];
		$files = array();
		if (is_array($post)){
			include_spip('action/ajouter_documents');
		  foreach ($post as $file) {
		  	if (is_array($file['name'])){
		  		while (count($file['name'])){
						$test=array(
							'error'=>array_shift($file['error']),
							'name'=>array_shift($file['name']),
							'tmp_name'=>array_shift($file['tmp_name']),
							'type'=>array_shift($file['type']),
							);
						if (!($test['error'] == 4)){
							if (is_string($err = joindre_upload_error($test['error'])))
								return $err; // un erreur upload
							if (!is_array(verifier_upload_autorise($test['name'])))
								return _T('medias:erreur_upload_type_interdit',array('nom'=>$test['name']));
							$files[]=$test;
						}
		  		}
		  	}
		  	else {
			  	//UPLOAD_ERR_NO_FILE
					if (!($file['error'] == 4)){
						if (is_string($err = joindre_upload_error($file['error'])))
							return $err; // un erreur upload
						if (!is_array(verifier_upload_autorise($file['name'])))
							return _T('medias:erreur_upload_type_interdit',array('nom'=>$file['name']));
						$files[]=$file;
					}
		  	}
			}
			if (!count($files))
				return _T('medias:erreur_indiquez_un_fichier');
		}
		return $files;
	}
	elseif (_request('joindre_distant')){
		$path = _request('url');
		if (!strlen($path) OR $path=='http://')
			return _T('medias:erreur_indiquez_un_fichier');
		include_spip('action/ajouter_documents');
		$infos = renseigner_source_distante($path);
		if (!is_array($infos))
			return $infos; // message d'erreur
		else
			return array(
				array(
					'name' => basename($path),
					'tmp_name' => $path,
					'distant' => true,
				)
			);
	}
	elseif (_request('joindre_ftp')){
		$path = _request('cheminftp');
		if (!$path || strstr($path, '..')) return _T('medias:erreur_indiquez_un_fichier');
		
		include_spip('inc/documents');	
		include_spip('inc/actions');
		$upload = determine_upload();
		if ($path != '/' AND $path != './') $upload .= $path;
	
		if (!is_dir($upload))
		  // seul un fichier est demande
		  return array(
		  	array (
		  		'name' => basename($upload),
					'tmp_name' => $upload
				)
			);
		else {
		  // on upload tout un repertoire
		  $files = array();
		  foreach (preg_files($upload) as $fichier) {
				$files[]= array (
					'name' => basename($fichier),
					'tmp_name' => $fichier
				);
		  }
		  return $files;
		}
	}
	elseif (_request('joindre_zip') AND $path = _request('chemin_zip')){
		include_spip('inc/documents'); //pour creer_repertoire_documents
		define('_tmp_zip', $path);
		define('_tmp_dir', creer_repertoire_documents(md5($path.$GLOBALS['visiteur_session']['id_auteur'])));
		if (_tmp_dir == _DIR_IMG)
			return _T('avis_operation_impossible');
		
		$files = array();
		if (_request('options_upload_zip')=='deballe')
			$files = joindre_deballer_lister_zip($path,_tmp_dir);
	  
		// si le zip doit aussi etre conserve, l'ajouter
		if (_request('options_upload_zip')=='upload' OR _request('options_deballe_zip_conserver')){
	  	$files[] = array(
				'name' => basename($path),
				'tmp_name' => $path,
	  	);
	  }

	  return $files;
		
	}

	return array();
}


// Erreurs d'upload
// renvoie false si pas d'erreur
// et true si erreur = pas de fichier
// pour les autres erreurs renvoie le message d'erreur
function joindre_upload_error($error) {

	if (!$error) return false;
	spip_log("Erreur upload $error -- cf. http://php.net/manual/fr/features.file-upload.errors.php");
	switch ($error) {
			
		case 4: /* UPLOAD_ERR_NO_FILE */
			return true;

		# on peut affiner les differents messages d'erreur
		case 1: /* UPLOAD_ERR_INI_SIZE */
			$msg = _T('medias:upload_limit',
			array('max' => ini_get('upload_max_filesize')));
			break;
		case 2: /* UPLOAD_ERR_FORM_SIZE */
			$msg = _T('medias:upload_limit',
			array('max' => ini_get('upload_max_filesize')));
			break;
		case 3: /* UPLOAD_ERR_PARTIAL  */
			$msg = _T('medias:upload_limit',
			array('max' => ini_get('upload_max_filesize')));
			break;
		case 6: /* UPLOAD_ERR_NO_TMP_DIR  */
			$msg = _T('medias:erreur_dossier_tmp_manquant');
			break;
		case 7: /* UPLOAD_ERR_CANT_WRITE */
			$msg = _T('medias:erreur_ecriture_fichier');

		default: /* autre */
			if (!$msg)
			$msg = _T('pass_erreur').' '. $error
			. '<br />' . propre("[->http://php.net/manual/fr/features.file-upload.errors.php]");
			break;
	}

	spip_log ("erreur upload $error");
	return $msg;
	
}

/**
 * Verifier si le fichier poste est un zip
 * Si on sait le deballer, proposer les options necessaires
 *
 * @param array $files
 * @return string
 */
function joindre_verifier_zip($files){
	if (function_exists('gzopen')
	 AND (count($files) == 1)
	 AND !isset($files[0]['distant'])
	 AND 
	  (preg_match('/\.zip$/i', $files[0]['name']) 
	   OR (isset($files[0]['type']) AND $files[0]['type'] == 'application/zip'))
	  ){
	
	  // on pose le fichier dans le repertoire zip 
	  // (nota : copier_document n'ecrase pas un fichier avec lui-meme
	  // ca autorise a boucler)
	  include_spip('inc/getdocument');
		$desc = $files[0];
		$zip = copier_document("zip",
					$desc['name'],
					$desc['tmp_name']
				);
		
		// Est-ce qu'on sait le lire ?
		include_spip('inc/pclzip');
		if ($zip
		 AND $archive = new PclZip($zip)
		 AND $contenu = joindre_decrire_contenu_zip($archive)
		 AND rename($zip, $tmp = _DIR_TMP.basename($zip))
		 ){
		 	$contenu[] = $tmp;
		 	return $contenu;
		 }
	}
	
	// ce n'est pas un zip sur lequel il faut demander plus de precisions
	return false;
}

/**
 * Verifier et decrire les fichiers de l'archive, en deux listes :
 * - une liste des noms de fichiers ajoutables
 * - une liste des erreurs (fichiers refuses)
 *
 * @param object $zip
 * @return array
 */
function joindre_decrire_contenu_zip($zip) {
	include_spip('action/ajouter_documents');
	// si pas possible de decompacter: installer comme fichier zip joint
	if (!$list = $zip->listContent()) return false;

	// Verifier si le contenu peut etre uploade (verif extension)
	$fichiers = array();
	$erreurs = array();
	foreach ($list as $file) {
		if (accepte_fichier_upload($f = $file['stored_filename']))
			$fichiers[$f] = $file;
		else
			// pas de message pour les dossiers et fichiers caches
			if (substr($f,-1)!=='/' AND substr(basename($f),0,1)!=='.')
				$erreurs[] = _T('medias:erreur_upload_type_interdit',array('nom'=>$f));
	}

	// si aucun fichier uploadable : installer comme fichier zip joint
	if (!count($fichiers))
		return false;

	ksort($fichiers);
	return array($fichiers,$erreurs);
}



// http://doc.spip.org/@joindre_deballes
function joindre_deballer_lister_zip($path,$tmp_dir) {
  include_spip('inc/pclzip');
	$archive = new PclZip($path);
	$archive->extract(
		PCLZIP_OPT_PATH, _tmp_dir,
		PCLZIP_CB_PRE_EXTRACT, 'callback_deballe_fichier'
	);
	if ($contenu = joindre_decrire_contenu_zip($archive)){
		$files = array();
		$fichiers = reset($contenu);		
		foreach($fichiers as $fichier){
			$f = basename($fichier['filename']);
			$files[] = array('tmp_name'=>$tmp_dir. $f,'name'=>$f,'titrer'=>_request('options_deballe_zip_titrer'),'mode'=>_request('options_deballe_zip_mode_document')?'document':null);
		}
		return $files;
	}
 	return _T('avis_operation_impossible');
}

if (!function_exists('fixer_extension_document')){
/**
 * Cherche dans la base le type-mime du tableau representant le document
 * et corrige le nom du fichier ; retourne array(extension, nom corrige)
 * s'il ne trouve pas, retourne '' et le nom inchange
 *
 * @param unknown_type $doc
 * @return unknown
 */
// http://doc.spip.org/@fixer_extension_document
function fixer_extension_document($doc) {
	$extension = '';
	$name = $doc['name'];
	if (preg_match(',\.([^.]+)$,', $name, $r)
	 AND $t = Sql::fetsel("extension", "spip_types_documents",	"extension=" . Sql::quote(corriger_extension($r[1])))
	 ) {
		$extension = $t['extension'];
		$name = preg_replace(',\.[^.]*$,', '', $doc['name']).'.'.$extension;
	}
	else if ($t = Sql::fetsel("extension", "spip_types_documents",	"mime_type=" . Sql::quote($doc['type']))) {
		$extension = $t['extension'];
		$name = preg_replace(',\.[^.]*$,', '', $doc['name']).'.'.$extension;
	}

	return array($extension,$name);
}
}

//
// Gestion des fichiers ZIP
//
// http://doc.spip.org/@accepte_fichier_upload

function accepte_fichier_upload ($f) {
	if (!preg_match(",.*__MACOSX/,", $f)
	AND !preg_match(",^\.,", basename($f))) {
	include_spip('action/ajouter_documents');
		$ext = corriger_extension((strtolower(substr(strrchr($f, "."), 1))));
		return Sql::countsel('spip_types_documents', "extension=" . Sql::quote($ext) . " AND upload='oui'");
	}
}

# callback pour le deballage d'un zip telecharge
# http://www.phpconcept.net/pclzip/man/en/?options-pclzip_cb_pre_extractfunction
// http://doc.spip.org/@callback_deballe_fichier

function callback_deballe_fichier($p_event, &$p_header) {
	if (accepte_fichier_upload($p_header['filename'])) {
		$p_header['filename'] = _tmp_dir . basename($p_header['filename']);
		return 1;
	} else {
		return 0;
	}
}

?>
