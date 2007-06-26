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

include_spip('inc/actions'); // *action_auteur et determine_upload
include_spip('base/abstract_sql');

//
// Construire un formulaire pour telecharger un fichier
//

// http://doc.spip.org/@inc_joindre_dist
function inc_joindre_dist($v) {
	global $spip_lang_right;

	$vignette_de_doc = ($v['mode'] == 'vignette' AND $v['id_document']>0);
	$distant = ($v['mode'] == 'document' AND $v['type']);

	# indiquer un choix d'upload FTP
	$dir_ftp = '';
	if (test_espace_prive()
	AND !($v['mode'] == 'vignette')	# si c'est pour un document
	AND !$vignette_de_doc		# pas pour une vignette (NB: la ligne precedente suffit, mais si on la supprime il faut conserver ce test-ci)
	AND $GLOBALS['flag_upload']) {
		if($dir = determine_upload('documents')) {
			// quels sont les docs accessibles en ftp ?
			$l = texte_upload_manuel($dir, '', $v['mode']);
			// s'il n'y en a pas, on affiche un message d'aide
			// en mode document, mais pas en mode vignette
			if ($l OR ($v['mode'] == 'document'))
				$dir_ftp = afficher_transferer_upload($l, $dir);
		}
	}
  
  // Add the redirect url when uploading via iframe

  $iframe = "";
  if($v['iframe_script'])
    $iframe = "<input type='hidden' name='iframe_redirect' value='".rawurlencode($v['iframe_script'])."' />\n";

	// Un menu depliant si on a une possibilite supplementaire

	if ($dir_ftp OR $distant OR $vignette_de_doc) {
		$bloc = "ftp_". $v['mode'] .'_'. intval($v['id_document']);

		if ($vignette_de_doc)
			$debut = bouton_block_depliable($v['intitule'],false,$bloc);
		else
			$debut = $v['intitule'];

		$milieu = debut_block_depliable(false,$bloc);
		$fin = "\n\t" . fin_block();
		$depliable = true;

	} else
		$debut = $milieu = $fin = '';

	// Lien document distant, jamais en mode image
	if ($distant) {
		$distant = "<br />\n<div style='border: 1px #303030 solid; padding: 4px; color: #505050;'>" .
			"\n\t<img src='"._DIR_IMG_PACK.'attachment.gif' .
			"' style='float: $spip_lang_right;' alt=\"\" />\n" .
			_T('info_referencer_doc_distant') .
			"<br />\n\t<input name='url' class='fondo' value='http://' />" .
			"\n\t<div style='text-align: $spip_lang_right'><input name='sousaction2' type='submit' value='".
			_T('bouton_choisir').
			"' class='fondo' /></div>" .
			"\n</div>";
	}

	$res = "<input name='fichier' type='file' class='forml spip_xx-small' size='15' />"
	. ($v['ancre']
		? "\n\t\t<input type='hidden' name='ancre' value='".$v['ancre']."' />"
		: ''
	)
	. "\n\t\t<div style='text-align: $spip_lang_right'><input name='sousaction1' type='submit' value='"
	. _T('bouton_telecharger')
	. "' class='fondo' /></div>";

	if ($vignette_de_doc)
		$res = $milieu . $res;
	else
		$res = $res . $milieu;


	$res = generer_action_auteur('joindre',
		(intval($v['id']) .'/' .intval($v['id_document']) . "/".$v['mode'].'/'.$v['type']),
		(!test_espace_prive())?$v['script']:generer_url_ecrire($v['script'], $v['args'], true),
		"$iframe$debut$res$dir_ftp$distant$fin",
		" method='post' enctype='multipart/form-data' class='form_upload'");

	if ($v['cadre']) {
		if ($depliable) {
			$v['titre'] = bouton_block_depliable($v['titre'],false,$bloc);
		}
		$debut_cadre = 'debut_cadre_'.$v['cadre'];
		$fin_cadre = 'fin_cadre_'.$v['cadre'];
		$res = $debut_cadre($v['icone'], true, $v['fonction'], $v['titre'])
			. $res
			. $fin_cadre(true);
	}

	return "\n<div class='joindre'>".$res."</div>\n";
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
		if (preg_match(",\.([^.]+)$,", $f, $match)) {
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
		"\n<div style='text-align: ".
		$GLOBALS['spip_lang_right'] .
		"'><input name='sousaction3' type='submit' value='" .
		_T('bouton_choisir').
		"' class='fondo' /></div>" .
		"</div>\n";
	}
}
?>
