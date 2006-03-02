<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/charsets');	# pour le nom de fichier
include_spip('inc/getdocument');
include_spip('base/abstract_sql');

function action_joindre_dist()
{
  global $action, $arg, $hash, $id_auteur,  $redirect,
    $sousaction1,
    $sousaction2,
    $sousaction3,
#    $sousaction4,  # sousaction4 = code mort a supprimer
    $sousaction5,
    $url, $chemin, $ancre, $type, $id, $id_document,
    $_FILES,  $HTTP_POST_FILES;

	include_spip('inc/session');
	if (!verifier_action_auteur("$action $arg", $hash, $id_auteur)) {
		include_spip('inc/minipres');
		minipres(_T('info_acces_interdit'));
	}
     // pas terrible, mais c'est le pb du bouton Submit qui retourne son texte,
     // et son transcodage est couteux et perilleux
     $sousaction = 'spip_action_joindre' .
       ($sousaction1 ? 1 :
	($sousaction2 ? 2 :
	 ($sousaction3 ? 3 : 
	  ($sousaction4 ? 4 :
	   $sousaction5 ))));

     $path = ($sousaction1 ? ($_FILES ? $_FILES : $HTTP_POST_FILES) :
	     ($sousaction2 ? $url : $chemin));

     $documents_actifs = array();

     if (function_exists($sousaction))
       $sousaction($path, $arg, $type, intval($id), $id_document, 
	       $hash, $id_auteur, $redirect, $documents_actifs);

     else spip_log("spip_action: sousaction inconnue $sousaction");

     if ($documents_actifs) {
	$redirect .= '&show_docs=' . join('-',$documents_actifs);
     }
     
     if ($ancre) {
	$redirect .= '#' . $ancre;
     }

     redirige_par_entete($redirect);
     ## redirection a supprimer si on veut poster dans l'espace prive directement (UPLOAD_DIRECT)
}


// Cas d'un document distant reference sur internet

function spip_action_joindre2($arg, $mode, $type, $id, $id_document,$hash, $id_auteur, $redirect, &$actifs)
{
	examiner_les_fichiers(array(
				   array('name' => basename($arg),
					 'tmp_name' => $arg)
				   ), 'distant', $type, $id, $id_document,
			     $hash, $id_auteur, $redirect, $actifs);
}

// Cas d'un fichier transmis

function spip_action_joindre1($arg, $mode, $type, $id, $id_document,$hash, $id_auteur, $redirect, &$actifs)
{
	$files = array();
	if (is_array($arg))
	  foreach ($arg as $file) {
		if (!($file['error'] == 4) /* UPLOAD_ERR_NO_FILE */)
			$files[]=$file;
	}

	examiner_les_fichiers($files, $mode, $type, $id, $id_document,
			     $hash, $id_auteur, $redirect, $actifs);
} 

// copie de tout ou partie du repertoire upload

function spip_action_joindre3($arg, $mode, $type, $id, $id_document,$hash, $id_auteur, $redirect, &$actifs)
{
	if (!$arg || strstr($arg, '..')) return;
	    
	$upload = (_DIR_TRANSFERT .$arg);

	if (!is_dir($upload))
	  // seul un fichier est demande
	  $files = array(array ('name' => basename($upload),
				'tmp_name' => $upload)
			 );
	else {
	  include_spip('inc/documents');
	  $files = array();
	  foreach (preg_files($upload) as $fichier) {
			$files[]= array (
					'name' => basename($fichier),
					'tmp_name' => $fichier
					);
	  }
	}

	examiner_les_fichiers($files, $mode, $type, $id, $id_document,
			     $hash, $id_auteur, $redirect, $actifs);
}

//  Zip avec confirmation "tel quel"

function spip_action_joindre5($arg, $mode, $type, $id, $id_document,$hash, $id_auteur, $redirect, &$actifs)
{
  	ajouter_un_document($arg, basename($arg), $type, $id, $mode, $id_document, $actifs);
}

// cas du zip a deballer. On ressort la bibli 

function spip_action_joindre6($arg, $mode, $type, $id, $id_document,$hash, $id_auteur, $redirect, &$actifs)
{
	    define('_tmp_dir', creer_repertoire_documents($hash));
	    if (_tmp_dir == _DIR_DOC) die(_L('Op&eacute;ration impossible'));
	    include_spip('inc/pclzip');
	    $archive = new PclZip($arg);
	    $archive->extract(
			      PCLZIP_OPT_PATH, _tmp_dir,
			      PCLZIP_CB_PRE_EXTRACT, 'callback_deballe_fichier'
			      );
	    $contenu = verifier_compactes($archive);
	    //  on supprime la copie temporaire
	    @unlink($arg);
	    
	    foreach ($contenu as $fichier)
		ajouter_un_document(_tmp_dir.basename($fichier),
				    basename($fichier),
				    $type, $id, $mode, $id_document, $actifs);
	    effacer_repertoire_temporaire(_tmp_dir);
}

?>
