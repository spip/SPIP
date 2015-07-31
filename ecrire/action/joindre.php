<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/charsets');	# pour le nom de fichier
include_spip('base/abstract_sql');
include_spip('inc/actions');

// http://doc.spip.org/@action_joindre_dist
function action_joindre_dist()
{
	global $hash, $url, $chemin, $ancre,
	  $sousaction1,
	  $sousaction2,
	  $sousaction3,
	  $sousaction4,
	  $sousaction5,
	  $_FILES,  $HTTP_POST_FILES;

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	$redirect = _request('redirect');
	$iframe_redirect = _request('iframe_redirect');
	if (!preg_match(',^(-?\d+)\D(\d+)\D(\w+)/(\w+)$,',$arg,$r)) {
	  spip_log("action_joindre_dist incompris: " . $arg);
	  redirige_par_entete(urldecode($redirect));
	}
	list($arg, $id, $id_document, $mode, $type) = $r;

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
       $type_image = $sousaction($path, $mode, $type, $id, $id_document, 
				 $hash, $redirect, $documents_actifs, $iframe_redirect);

     else spip_log("spip_action: sousaction inconnue $sousaction");

     $redirect = urldecode($redirect);
     if ($documents_actifs) {
	$redirect = parametre_url($redirect,'show_docs',join(',',$documents_actifs));
     }
     
    if (!$ancre) {

		if ($mode=='vignette')
			$ancre = 'images';
		else if ($type_image)
			$ancre = 'portfolio';
		else
			$ancre = 'documents';
     }

    $redirect .= '#' . $ancre;
    if ($type == 'rubrique') {
	include_spip('inc/rubriques');
	calculer_rubriques();
     }

	if(_request("iframe") == 'iframe') {
		$redirect = parametre_url(urldecode($iframe_redirect),"show_docs",join(',',$documents_actifs),'&')."&iframe=iframe";
	}

	redirige_par_entete($redirect);
     ## redirection a supprimer si on veut poster dans l'espace prive directement (UPLOAD_DIRECT)
}


// Cas d'un document distant reference sur internet

// http://doc.spip.org/@spip_action_joindre2
function spip_action_joindre2($path, $mode, $type, $id, $id_document,$hash, $redirect, &$actifs, $iframe_redirect)
{
	return joindre_documents(array(
				   array('name' => basename($path),
					 'tmp_name' => $path)
				   ), 'distant', $type, $id, $id_document,
			     $hash, $redirect, $actifs, $iframe_redirect);
}

// Cas d'un fichier transmis

// http://doc.spip.org/@spip_action_joindre1
function spip_action_joindre1($path, $mode, $type, $id, $id_document,$hash, $redirect, &$actifs, $iframe_redirect)
{
	$files = array();
	if (is_array($path))
	  foreach ($path as $file) {
		if (!($file['error'] == 4) /* UPLOAD_ERR_NO_FILE */)
			$files[]=$file;
	}

	return joindre_documents($files, $mode, $type, $id, $id_document,
			     $hash, $redirect, $actifs, $iframe_redirect);
} 

// copie de tout ou partie du repertoire upload

// http://doc.spip.org/@spip_action_joindre3
function spip_action_joindre3($path, $mode, $type, $id, $id_document,$hash, $redirect, &$actifs, $iframe_redirect)
{
	if (!$path || strstr($path, '..')) return;
	    
	$upload = determine_upload();
	if ($path != '/' AND $path != './') $upload .= $path;

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

	return joindre_documents($files, $mode, $type, $id, $id_document, $hash, $redirect, $actifs, $iframe_redirect);
}

//
// Charger la fonction surchargeable receptionnant un fichier
// et l'appliquer sur celui ou ceux indiques.

// http://doc.spip.org/@joindre_documents
function joindre_documents($files, $mode, $type, $id, $id_document, $hash, $redirect, &$actifs, $iframe_redirect)
{
	$ajouter_documents = charger_fonction('ajouter_documents', 'inc');

	if (function_exists('gzopen') 
	AND !($mode == 'distant')
	AND (count($files) == 1)) {

		$desc = $files[0];
		if (preg_match('/\.zip$/i', $desc['name'])
		    OR ($desc['type'] == 'application/zip')) {
	
	  // on pose le fichier dans le repertoire zip 
	  // (nota : copier_document n'ecrase pas un fichier avec lui-meme
	  // ca autorise a boucler)
			$zip = copier_document("zip",
					$desc['name'],
					$desc['tmp_name']
				);
			if (!$zip)
			  {include_spip('minipres'); echo minipres('Erreur upload zip'); exit;} # pathologique
			// Est-ce qu'on sait le lire ?
			include_spip('inc/pclzip');
			$archive = new PclZip($zip);
			if ($archive) {
			  $valables = verifier_compactes($archive);
			  if ($valables) {
			    echo liste_archive_jointe($valables, $mode, $type, $id, $id_document, $hash, $redirect, $zip, $iframe_redirect);
	// a tout de suite en joindre4, joindre5, ou joindre6
			    exit;
			  }
			}
		}
	}
	foreach ($files as $arg) {
		check_upload_error($arg['error']);
		$x = $ajouter_documents($arg['tmp_name'], $arg['name'], 
				    $type, $id, $mode, $id_document, $actifs);
	}
	// un invalideur a la hussarde qui doit marcher au moins pour article, breve, rubrique
	include_spip('inc/invalideur');
	suivre_invalideur("id='id_$type/$id'");
	return $x;
}

#-----------------------------------------------------------------------

// sous-actions suite a l'envoi d'un Zip:
// la fonction joindre_documents ci-dessus a construit un formulaire 
// qui renvoie sur une des 3 sous-actions qui suivent. 

//  Zip avec confirmation "tel quel"

// http://doc.spip.org/@spip_action_joindre5
function spip_action_joindre5($path, $mode, $type, $id, $id_document,$hash, $redirect, &$actifs)
{
	$ajouter_documents = charger_fonction('ajouter_documents', 'inc');
	$pos = strpos($path, '/zip/');
	if (!$pos) {
		$pos = strpos($path, '/zip_');
	}
	return $ajouter_documents($path, substr($path, $pos+5), $type, $id, $mode, $id_document, $actifs);
}

// Zip a deballer. 

// http://doc.spip.org/@spip_action_joindre6
function spip_action_joindre6($path, $mode, $type, $id, $id_document,$hash, $redirect, &$actifs, $iframe_redirect)
{
	$x = joindre_deballes($path, $mode, $type, $id, $id_document,$hash, $redirect, $actifs);
	//  suppression de l'archive en zip
	@unlink($path);
	return $x;
}

// Zip avec les 2 options a la fois

// http://doc.spip.org/@spip_action_joindre4
function spip_action_joindre4($path, $mode, $type, $id, $id_document,$hash, $redirect, &$actifs, $iframe_redirect)
{
	joindre_deballes($path, $mode, $type, $id, $id_document,$hash, $redirect, $actifs);
	return spip_action_joindre5($path, $mode, $type, $id, $id_document,$hash, $redirect, $actifs);
}

// http://doc.spip.org/@joindre_deballes
function joindre_deballes($path, $mode, $type, $id, $id_document,$hash, $redirect, &$actifs)
{
	    $ajouter_documents = charger_fonction('ajouter_documents', 'inc');
	    define('_tmp_dir', creer_repertoire_documents($hash));

	    if (_tmp_dir == _DIR_DOC)
	      {include_spip('minipres');
		echo minipres(_T('avis_operation_impossible'));
		exit;
	      }
	    include_spip('inc/pclzip');
	    $archive = new PclZip($path);
	    $archive->extract(
			      PCLZIP_OPT_PATH, _tmp_dir,
			      PCLZIP_CB_PRE_EXTRACT, 'callback_deballe_fichier'
			      );
	    $contenu = verifier_compactes($archive);

	    foreach ($contenu as $fichier) {
		$f = basename($fichier);
		$x = $ajouter_documents(_tmp_dir. $f, $f,
				    $type, $id, $mode, $id_document, $actifs);
	    }
	    effacer_repertoire_temporaire(_tmp_dir);
	    return $x;
}


?>
