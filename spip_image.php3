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


// Charger un document, une image, un logo, un repertoire
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

$documents_actifs = array();
//
// Le switch principal : quelle est l'action demandee
//

// appel de config-fonction
if ($test_vignette)
	redirige_par_entete(tester_vignette($test_vignette));

 else if ($ajout_doc == "oui")
   {

// Autorisation ?
     if (!verifier_action_auteur("ajout_doc", $hash, $hash_id_auteur))
	die ('Interdit');

// Cas d'un document distant reference sur internet
     if (preg_match(',^https?://....+,i', $_POST['image_url'])) {
	ajouter_les_fichiers(array(
			array('name' => basename($_POST['image_url']),
			'tmp_name' => $_POST['image_url'])
			), 'distant', $type, $id_article, $id_document, $documents_actifs);
 } else {

  $image2 = $_POST['image2'];
  if ($image2 AND !strstr($image2, '..') AND $_POST['ok_ftp']) {
  //
  // Cas d'un fichier ou d'un repertoire installe dans ecrire/upload/
  //
	$upload = _DIR_TRANSFERT .$image2;
	if (!is_dir($upload))
	  // seul un fichier est demande
	  $_FILES = array(
				array ('name' => basename($upload),
				'tmp_name' => $upload)
				);
	else $_FILES = ajouter_par_upload($upload, $_POST['identifier'], $id_article, $hash_id_auteur);
  } else {
	if (!$_FILES)
		$_FILES = &$HTTP_POST_FILES;
	if (!is_array($_FILES))
		$_FILES = array();
	foreach ($_FILES as $id => $file) {
		if ($file['error'] == 4 /* UPLOAD_ERR_NO_FILE */)
			unset ($_FILES[$id]);
	}
  }
  if (function_exists('gzopen') AND !($mode == 'distant'))
	$_FILES = deballer_upload($_FILES, $_POST['source_zip'],$action_zip, $hash, $hash_id_auteur, $id_article, $id_document, $mode, $redirect, $type);
  
  ajouter_les_fichiers($_FILES, $mode, $type, $id_article, $id_document, $documents_actifs);
     }
   }
// Ajout d'un logo
else if ($ajout_logo == "oui" and $logo) {

  // Recuperer les variables d'upload
  if (!$_FILES)
    $_FILES = &$HTTP_POST_FILES;
  if (!is_array($_FILES))
    $_FILES = array();
  foreach ($_FILES as $id => $file) {
    if ($file['error'] == 4 /* UPLOAD_ERR_NO_FILE */)
      unset ($_FILES[$id]);
  }
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


$link = new Link(_DIR_RESTREINT_ABS . $redirect);
if ($documents_actifs) {
	$link->addVar('show_docs',join('-',$documents_actifs));
 }
redirige_par_entete($link->getUrl($ancre));
?>
