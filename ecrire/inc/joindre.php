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

include_spip('inc/actions'); // *action_auteur et determine_upload
include_spip('base/abstract_sql');

//
// Construire un formulaire pour telecharger un fichier
//

// http://doc.spip.org/@inc_joindre_dist
function inc_joindre_dist($script, $args, $id=0, $intitule='', $mode='', $type='', $ancre='', $id_document=0,$iframe_script='') {
	global $spip_lang_right;
	$vignette_de_doc = ($mode == 'vignette' AND $id_document>0);
	$distant = ($mode == 'document' AND $type);
	if ($intitule) $intitule = "<span>$intitule</span><br />";

	if (!_DIR_RESTREINT AND !$vignette_de_doc AND $GLOBALS['flag_upload']) {
		if($dir_ftp = determine_upload()) {
			// quels sont les docs accessibles en ftp ?
			$l = texte_upload_manuel($dir_ftp, '', $mode);
			// s'il n'y en a pas, on affiche un message d'aide
			// en mode document, mais pas en mode vignette
			if ($l OR ($mode == 'document'))
				$dir_ftp = afficher_transferer_upload($l, $dir_ftp);
			else
				$dir_ftp = '';
		}
	}
  
  // Add the redirect url when uploading via iframe

  $iframe = "";
  if($iframe_script)
    $iframe = "<input type='hidden' name='iframe_redirect' value='".rawurlencode($iframe_script)."' />\n";

	// Un menu depliant si on a une possibilite supplementaire

	if ($dir_ftp OR $distant OR $vignette_de_doc) {
		$bloc = "ftp_$mode" .'_'. intval($id_document);
		$debut = "\n\t<div style='float:".$GLOBALS['spip_lang_left'].";position:relative'>"
			. bouton_block_invisible($bloc) ."</div>\n";
		$milieu = debut_block_invisible($bloc);
		$fin = "\n\t" . fin_block();

	} else $debut = $milieu = $fin = '';

	// Lien document distant, jamais en mode image
	if ($distant) {
		$distant = "<p />\n<div style='border: 1px #303030 solid; padding: 4px; color: #505050;'>" .
			"\n\t<img src='"._DIR_IMG_PACK.'attachment.gif' .
			"' style='float: $spip_lang_right;' alt=\"\" />\n" .
			_T('info_referencer_doc_distant') .
			"<br />\n\t<input name='url' class='fondo' value='http://' />" .
			"\n\t<div align='$spip_lang_right'><input name='sousaction2' type='submit' value='".
			_T('bouton_choisir').
			"' class='fondo' /></div>" .
			"\n</div>";
	}

	$res = "<input name='fichier' type='file' style='font-size: 10px;' class='forml' size='15' />"
	. "\n\t\t<input type='hidden' name='ancre' value='$ancre' />"
	. "\n\t\t<div align='$spip_lang_right'><input name='sousaction1' type='submit' value='"
	. _T('bouton_telecharger')
	. "' class='fondo' /></div>";

	if ($vignette_de_doc)
		$res = $milieu . $res;
	else
		$res = $res . $milieu;

	return generer_action_auteur('joindre',
		(intval($id) .'/' .intval($id_document) . "/$mode/$type"),
		generer_url_ecrire($script, $args, true),
		"$iframe$debut$intitule$res$dir_ftp$distant$fin",
		" method='post' enctype='multipart/form-data' style='border: 0px; margin: 0px;' class='form_upload'");
}


//
// Retourner le code HTML d'utilisation de fichiers envoyes
//

// http://doc.spip.org/@texte_upload_manuel
function texte_upload_manuel($dir, $inclus = '', $mode = 'document') {
	$fichiers = preg_files($dir);
	$exts = array();
	$dirs = array(); 
	$texte_upload = array();
	foreach ($fichiers as $f) {
		$f = preg_replace(",^$dir,",'',$f);
		if (ereg("\.([^.]+)$", $f, $match)) {
			$ext = strtolower($match[1]);
			if (!isset($exts[$ext])) {
				if ($ext == 'jpeg') $ext = 'jpg'; # cf. corriger_extension dans inc/getdocument
				if (spip_abstract_fetsel('extension', 'spip_types_documents', "extension='$ext'" . (!$inclus ? '':  " AND inclus='$inclus'")))
					$exts[$ext] = 'oui';
				else $exts[$ext] = 'non';
			}
			
			$k = 2*substr_count($f,'/');
			$n = strrpos($f, "/");
			if ($n === false)
			  $lefichier = $f;
			else {
			  $lefichier = substr($f, $n+1, strlen($f));
			  $ledossier = substr($f, 0, $n);
			  if (!in_array($ledossier, $dirs)) {
				$texte_upload[] = "\n<option value=\"$ledossier\">"
				. str_repeat("&nbsp;",$k) 
				._T('tout_dossier_upload', array('upload' => $ledossier))
				."</option>";
				$dirs[]= $ledossier;
			  }
			}

			if ($exts[$ext] == 'oui')
			  $texte_upload[] = "\n<option value=\"$f\">" .
			    str_repeat("&nbsp;",$k+2) .
			    $lefichier .
			    "</option>";
		}
	} 

	$texte = join('', $texte_upload);

	if ($mode == "document" AND count($texte_upload)>1) {
		$texte = "\n<option value=\"/\" style='font-weight: bold;'>"
				._T('info_installer_tous_documents')
				."</option>" . $texte;
	}

	return $texte;
}


// http://doc.spip.org/@afficher_transferer_upload
function afficher_transferer_upload($texte_upload, $dir)
{
	$doc = array('upload' => '<b>' . joli_repertoire($dir) . '</b>');
	if (!$texte_upload) {
		return "\n<div style='border: 1px #303030 solid; padding: 4px; color: #505050;'>" .
			_T('info_installer_ftp', $doc) .
			aide("ins_upload") .
			"</div>";
		}
	else {  return
		"\n<div style='color: #505050;'>"
		._T('info_selectionner_fichier', $doc)
		."&nbsp;:<br />\n" .
		"\n<select name='chemin' size='1' class='fondl'>" .
		$texte_upload .
		"\n</select>" .
		"\n<div align='".
		$GLOBALS['spip_lang_right'] .
		"'><input name='sousaction3' type='submit' value='" .
		_T('bouton_choisir').
		"' class='fondo' /></div>" .
		"</div>\n";
	}
}
?>
