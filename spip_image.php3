<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


// Uploader un document, une image ou un logo,
// supprimer cet element, creer les vignettes, etc.

include ("ecrire/inc_version.php3");
include_ecrire('inc_presentation.php3');	# regler la langue en cas d'erreur
include_ecrire('inc_getdocument.php3');		# diverses fonctions de ce fichier
include_ecrire("inc_charsets.php3");		# pour le nom de fichier
include_ecrire("inc_meta.php3");			# ne pas faire confiance au cache
											# (alea_ephemere a peut-etre change)
include_ecrire("inc_admin.php3");			# verifier_action_auteur
include_ecrire("inc_abstract_sql.php3");	# spip_insert
include_ecrire('inc_documents.php3');		# fichiers_upload()


// Recuperer les variables d'upload
if (!$_FILES)
	$_FILES = &$HTTP_POST_FILES;
if (!is_array($_FILES))
	$_FILES = array();
foreach ($_FILES as $id => $file) {
	if ($file['error'] == 4 /* UPLOAD_ERR_NO_FILE */)
		unset ($_FILES[$id]);
}

// Si on est en mode 'document', les images doivent etre installees
// comme documents dans le portfolio
if ($forcer_document) $mode = 'document';


//
// Le switch principal : quelle est l'action demandee
//


// appel de config-fonction
if ($test_vignette)
	$retour_image = tester_vignette($test_vignette);

/**
	OBSOLETE

// Creation de vignette depuis le portfolio (ou autre)
else if ($vignette) {
	if ($creer_vignette == 'oui' AND
	verifier_action_auteur("vign $vignette",
	$hash, $hash_id_auteur))
		creer_fichier_vignette($vignette);
	else
		$retour_image = creer_fichier_vignette($vignette, true); # obsolete
}

**/


//
// Ajout d'un document ou d'une image
//
else if ($ajout_doc == 'oui') {

	// Autorisation ?
	if (!verifier_action_auteur("ajout_doc", $hash, $hash_id_auteur))
		die ('Interdit');

	//
	// Cas d'un fichier ou d'un repertoire installe dans ecrire/upload/
	//
	if ($_POST['image2']
	AND !strstr($_POST['image2'], '..')
	AND $_POST['ok_ftp']
	) {
		$upload = _DIR_TRANSFERT.$_POST['image2'];

		// lire le repertoire upload et remplir $_FILES
		if (is_dir($upload)) {
			$fichiers = fichiers_upload($upload);

			$_FILES = array();
			foreach ($fichiers as $fichier) {
				$_FILES[] = array (
					'name' => basename($fichier),
					'tmp_name' => $fichier
				);
			}
		}

		// seul un fichier est demande
		else
			$_FILES = array(
				array ('name' => basename($upload),
				'tmp_name' => $upload)
			);
	}
	
	// Cas d'un document distant reference sur internet
	else if (preg_match(',^https?://....+,i', $_POST['image_url'])) {
		$_FILES = array(
			array('name' => basename($_POST['image_url']),
			'tmp_name' => $_POST['image_url'])
		);
		$mode = 'distant';
	}

	//
	// Upload d'un ZIP
	//
	if (function_exists('gzopen') AND !($mode == 'distant')) {

		// traiter la reponse de l'utilisateur ('telquel' ou 'decompacter')
		if ($_POST['source_zip']
		AND !strstr($_POST['source_zip'], '..')) # securite
		{
			$_FILES = array(
				array('name' => basename($_POST['source_zip']),
					'tmp_name' => $_POST['source_zip'])
			);
		}
	
		// traiter le zip si c'en est un tout seul
		if (count($_FILES) == 1
		AND $action_zip!='telquel') {
			$desc = array_pop($_FILES); # recuperer la description
			$_FILES = array($desc);
	
			if (preg_match('/\.zip$/i', $desc['name'])
			OR ($desc['type'] == 'application/zip')) {
	
				// on pose le fichier dans le repertoire zip et on met
				// a jour $_FILES (nota : copier_document n'ecrase pas
				// un fichier avec lui-meme : ca autorise a boucler)
				$zip = copier_document("zip",
					$desc['name'],
					$desc['tmp_name']
				);
				if (!$zip) die ('Erreur upload zip'); # pathologique
				$desc['tmp_name'] = $zip;	# nouvel emplacement du fichier
				$_FILES = array($desc);
	
				// Est-ce qu'on sait le lire ?
				require_once(_DIR_RESTREINT . 'pclzip.lib.php');
				$archive = new PclZip($zip);
				$contenu = verifier_compactes($archive);
	
				// si non, on le force comme document
				if (!$contenu) {
					$forcer_document = 'oui';
				}
	
				// si le deballage est demande
				else if ($action_zip == 'decompacter') {
					// 1. on deballe
					define('_tmp_dir', creer_repertoire_documents($hash));
					if (!_tmp_dir) redirige_par_entete("spip_test_dirs.php3");
					$archive->extract(
						PCLZIP_OPT_PATH, _tmp_dir,
						PCLZIP_CB_PRE_EXTRACT, 'callback_deballe_fichier'
						);
					$contenu = verifier_compactes($archive);
					// 2. on supprime le fichier temporaire
					@unlink($zip);
	
					$_FILES = array();
					foreach ($contenu as $fichier) {
						$_FILES[] = array(
							'name' => basename($fichier),
							'tmp_name' => _tmp_dir.basename($fichier));
					}
				}
	
				// sinon on demande une reponse
				else {
					$link = new Link('spip_image.php3');
					$link->addVar('ajout_doc', 'oui');
					$link->addVar('redirect', $redirect);
					$link->addVar('id_article', $id_article);
					$link->addVar('mode', $mode);
					$link->addVar('type', $type);
					$link->addVar('hash', $hash);
					$link->addVar('hash_id_auteur', $hash_id_auteur);
					$link->addVar('source_zip', $zip);
					afficher_compactes($desc, $contenu, $link);
					exit;
				}
			}
		}
	}
	// Fin du bloc ZIP


	//
	// Traiter la liste des fichiers
	//
	$documents_actifs = array();

	foreach ($_FILES as $file) {

		// afficher l'erreur 'fichier trop gros' ou autre
		check_upload_error($file['error']);

		spip_log ("ajout du document ".$file['name'].", $mode ($type $id_article $id_document)");
		ajouter_un_document (
			$file['tmp_name'],	# le fichier sur le serveur (/var/tmp/xyz34)
			$file['name'],		# son nom chez le client (portequoi.pdf)
			$type,				# lie a un article, une breve ou une rubrique ?
			$id_article,		# identifiant de l'article (ou rubrique) lie
			$mode,				# 'vignette' => image en mode image
								# ou vignette personnalisee liee a un document
								# 'document' => doc ou image en mode document
								# 'distant' => lien internet
			$id_document,		# pour une vignette, l'id_document de maman
			$documents_actifs	# tableau des id_document "actifs" (par ref)
		);
	}	// foreach $_FILES

	// Nettoyer le repertoire temporaire d'extraction des fichiers
	if (defined('_tmp_dir'))
		effacer_repertoire_temporaire(_tmp_dir);
}

// Ajout d'un logo
else if ($ajout_logo == "oui" and $logo) {
	if ($desc = array_pop($_FILES)
	AND verifier_action_auteur("ajout_logo $logo",
	$hash, $hash_id_auteur))
		ajout_logo($desc, $logo);
}

// Suppression d'un logo
else if ($image_supp) {
	if (verifier_action_auteur("supp_logo $image_supp",
	$hash, $hash_id_auteur))
		effacer_logo($image_supp);
}

// Suppression d'un document et de sa vignette
else if ($doc_supp) {
	if (verifier_action_auteur("supp_doc $doc_supp",
	$hash, $hash_id_auteur))
		supprime_document_et_vignette($doc_supp);
}

// Rotation d'une image
else if ($doc_rotate) {
	if (verifier_action_auteur("rotate $doc_rotate",
	$hash, $hash_id_auteur))
		tourner_document($var_rot, $doc_rotate, $convert_command);
}


//
// Retour a l'envoyeur
//

// si nous sommes diriges vers une vignette
if ($retour_image) {
	redirige_par_entete($retour_image);

} else {
	$link = new Link(_DIR_RESTREINT_ABS . $redirect);
	if ($documents_actifs) {
		$show_docs = join('-',$documents_actifs);
		$link->addVar('show_docs',$show_docs);
	}

	redirige_par_entete($link->getUrl($ancre));
}

?>
