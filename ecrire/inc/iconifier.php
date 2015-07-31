<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/actions');
global $logo_libelles;
$logo_libelles['id_article'] = _T('logo_article').aide ("logoart");
$logo_libelles['id_auteur'] = _T('logo_auteur').aide ("logoart");
$logo_libelles['id_breve'] = _T('logo_breve').aide ("breveslogo");
$logo_libelles['id_syndic'] = _T('logo_site')." ".aide ("rublogo");
$logo_libelles['id_mot'] = _T('logo_mot_cle').aide("breveslogo");
$logo_libelles['id_rubrique'] = _T('logo_rubrique')." ".aide ("rublogo");
$logo_libelles['id_racine'] = _T('logo_standard_rubrique')." ".aide ("rublogo");


// http://doc.spip.org/@inc_iconifier_dist
function inc_iconifier_dist($id_objet, $id,  $script, $visible=false, $flag_modif=true) {
	if ($GLOBALS['spip_display'] == 4) return "";
	$texteon = $GLOBALS['logo_libelles'][($id OR $id_objet != 'id_rubrique') ? $id_objet : 'id_racine'];

	$chercher_logo = charger_fonction('chercher_logo', 'inc');
	
	// Add the redirect url when uploading via iframe
	$iframe_script = generer_url_ecrire('iconifier',"type=$id_objet&$id_objet=$id&script=$script",true);
	$iframe = "<input type='hidden' name='iframe_redirect' value='".rawurlencode($iframe_script)."' />\n";

	$logo = $chercher_logo($id, $id_objet, 'on');
	$logo_s = $chercher_logo($id, $id_objet, 'off');
	if (!$logo) {
		if ($flag_modif AND $GLOBALS['meta']['activer_logos'] != 'non') {
			$masque = indiquer_logo($texteon, $id_objet, 'on', $id, $script, $iframe);
			$masque = "<div class='cadre_padding'>$masque</div>";
			$bouton = bouton_block_depliable($texteon, $visible, "on-$id_objet-$id");
			$res = debut_block_depliable($visible,"on-$id_objet-$id") . $masque . fin_block();
		}
	} else {
		list($img, $clic) = decrire_logo($id_objet,'on',$id, 170, 170, $logo, $texteon, $script, $flag_modif AND !$logo_s);

		$bouton = bouton_block_depliable($texteon, $visible, "on-$id_objet-$id");

		$survol = '';
		$texteoff = _T('logo_survol');
		if (!$logo = $logo_s) {
			if ($flag_modif AND $GLOBALS['meta']['activer_logos_survol'] == 'oui') {
				$masque = "<br />".indiquer_logo($texteoff, $id_objet, 'off', $id, $script, $iframe);
				$survol .= "<br />".block_parfois_visible("off-$id_objet-$id", $texteoff, $masque, null, $visible);
			}
			$masque = debut_block_depliable($visible,"on-$id_objet-$id") 
				. "<div class='cadre_padding'>"
				. $clic . $survol . "</div>" . fin_block();
		} else {
			list($imgoff, $clicoff) = decrire_logo($id_objet, 'off', $id, 170, 170, $logo, $texteoff, $script, $flag_modif);
			$masque = debut_block_depliable($visible, "off-$id_objet-$id") .  $clicoff . fin_block();
			$survol .= "<br />".bouton_block_depliable($texteoff, $visible, "off-$id_objet-$id")
			. "<div class='cadre_padding'>".$imgoff.$masque."</div>";
			$masque = debut_block_depliable($visible,"on-$id_objet-$id") . $clic . fin_block() . $survol;
		}

		$res = "$img$masque";
	}

	if ($res) {
		$res = debut_cadre('r', 'image-24.gif', '', $bouton, '', '', false)
			. $res
			. fin_cadre_relief(true);

		if(_request("exec")!="iconifier") {
		  $js = http_script('',  'async_upload.js')
		    . http_script('$("form.form_upload_icon").async_upload(async_upload_icon)');

		} else $js = "";
		return ajax_action_greffe("iconifier", $id, $res).$js;
	}
	else return '';

}


// http://doc.spip.org/@indiquer_logo
function indiquer_logo($titre, $id_objet, $mode, $id, $script, $iframe_script) {

	global $formats_logos;
	$afficher = "";
	$reg = '[.](' . join('|', $formats_logos) . ')$';


/*
	# CODE MORT SI ON DECIDE DE NE PAS LAISSER UPLOADER DES LOGOS PAR FTP

	if ($GLOBALS['flag_upload']
	AND $dir_ftp = determine_upload('logos')
	AND $fichiers = preg_files($dir_ftp, $reg)) {
		foreach ($fichiers as $f) {
			$f = substr($f, strlen($dir_ftp));
			$afficher .= "\n<option value='$f'>$f</option>";
		}
	}
	if (!$afficher) {
		if ($dir_ftp) {
			$afficher = _T('info_installer_images_dossier',
				array('upload' => '<b>' . joli_repertoire($dir_ftp) . '</b>'));
		}
	} else {
		$afficher = "\n<div style='text-align: left'>" .
			_T('info_selectionner_fichier',
				array('upload' => '<b>' . joli_repertoire($dir_ftp) . '</b>')) .
			":</div>" .
			"\n<select name='source' class='forml' size='1'>$afficher\n</select>" .
			"\n<div style='text-align:" .
			$GLOBALS['spip_lang_right'] .
			"'><input name='sousaction2' type='submit' value='".
			_T('bouton_choisir') .
			"'  /></div>";
	}
*/

	$afficher = "\n<label for='image'>" .
		_T('info_telecharger_nouveau_logo') .
		"</label><br />" .
		"\n<input name='image' id='image' type='file' class='forml spip_xx-small' size='15' />" .
		"<div style='text-align: " .  $GLOBALS['spip_lang_right'] . "'>" .
		"\n<input name='sousaction1' type='submit' value='" .
		_T('bouton_telecharger') .
		"'  /></div>" .
		$afficher;

	$type = type_du_logo($id_objet);
	return redirige_action_post('iconifier',
		"$id+$type$mode$id",
		$script,
		"$id_objet=$id",
		$iframe_script.$afficher,
		" enctype='multipart/form-data' class='form_upload_icon'");
}

// http://doc.spip.org/@decrire_logo
function decrire_logo($id_objet, $mode, $id, $width, $height, $img, $titre="", $script="", $flag_modif=true) {

	list($fid, $dir, $nom, $format, $timestamp) = $img;
	include_spip('inc/filtres_images_mini');

	$res = image_reduire("<img src='$fid' alt='' class='miniature_logo' />", $width, $height);

	if ($res){
			$src = extraire_attribut($res,'src');
			$res = inserer_attribut($res, 'src', "$src?$timestamp");
	    $res = "<div><a href='" .	$fid . "'>$res</a></div>";
	}
	else
	    $res = "<img src='$fid?$timestamp' width='$width' height='$height' alt=\"" . htmlentities($titre) . '" />';
	if ($taille = @getimagesize($fid))
		$taille = _T('info_largeur_vignette', array('largeur_vignette' => $taille[0], 'hauteur_vignette' => $taille[1]));

	return array($res,
		"<div class='spip_xx-small'>" . $taille
		. ($flag_modif
			? "\n<br />["
				. ajax_action_auteur("iconifier", "$id-$nom.$format",
				$script, "$id_objet=$id&type=$id_objet",
				array(_T('lien_supprimer')),
				'',"function(r,status) {this.innerHTML = r; \$('form.form_upload_icon',this).async_upload(async_upload_icon);}") ."]"
			: '')
			. "</div>");
}
?>
