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
include_ecrire('inc_presentation.php3');# regler la langue en cas d'erreur
include_ecrire('inc_getdocument.php3');	# diverses fonctions de ce fichier
include_ecrire("inc_charsets.php3");	# pour le nom de fichier
include_ecrire("inc_meta.php3");	# ne pas faire confiance au cache
					# (alea_ephemere a peut-etre change)
include_ecrire("inc_admin.php3");	# verifier_action_auteur
include_ecrire("inc_abstract_sql.php3");# spip_insert
include_ecrire('inc_documents.php3');	# fichiers_upload()

if ($test_vignette)
	redirige_par_entete(tester_vignette($test_vignette));

else {
	if (!verifier_action_auteur("$action $doc", $hash, $hash_id_auteur))
		die ('Interdit');

	$documents_actifs = array();

	if ($action == 'joindre') {

     // pas terrible, mais c'est le pb du bouton Submit qui retourne son texte,
     // et son transcodage est couteux et perilleux
     $action .=
       ($sousaction1 ? 1 :
	($sousaction2 ? 2 :
	 ($sousaction3 ? 3 : 
	  ($sousaction4 ? 4 :
	   $sousaction5 ))));

     $arg = ($sousaction1 ? ($_FILES ? $_FILES : $HTTP_POST_FILES) :
	     ($sousaction2 ? $url : $chemin));

     if (function_exists($action))
       $action($arg, $doc, $type, $id_article, $id_document, 
	       $hash, $hash_id_auteur, $redirect, $documents_actifs);

     else spip_log("spip_image: action inconnue $action");
	}
	else if ($action == 'ajout_logo') {

		if (!$_FILES) $_FILES = $HTTP_POST_FILES;
		$desc = $sousaction2 ? $chemin : (is_array($_FILES) ? array_pop($_FILES) : "");
		if ($desc) ajout_logo($desc, $doc);
	}

	else if ($action == "effacer_logo") {
		effacer_logo($doc);
	}

	else if ($action == 'supprime_document_et_vignette') {
		supprime_document_et_vignette($doc);
	}

	else if ($action = 'tourner_document') {
		tourner_document($var_rot, $doc, $convert_command);
	}

	else spip_log("spip_image: action inconnue $action");
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
