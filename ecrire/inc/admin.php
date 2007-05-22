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

include_spip('inc/headers');
include_spip('inc/meta');

// demande/verifie le droit de creation de repertoire par le demandeur;
// memorise dans les meta que ce script est en cours d'execution
// si elle y est deja c'est qu'il y a eu suspension du script, on reprend.

// http://doc.spip.org/@inc_admin_dist
function inc_admin_dist($script, $titre, $comment='', $retour='')
{
	$reprise = true;
	if (!isset($GLOBALS['meta'][$script])) {
		$reprise = false;
		debut_admin($script, $titre, $comment); 
		spip_log("meta: $script " . join(',', $_POST));
		ecrire_meta($script, serialize($_POST));
		ecrire_metas();
	} else spip_log("reprise de $script");
	$base = charger_fonction($script, 'base');
	$base($titre,$reprise);
	effacer_meta($script);
	ecrire_metas();
	@unlink(_FILE_META);
	fin_admin($script);
	spip_log("efface meta: $script " . ($retour ? $retour : ''));
	if ($retour) redirige_par_entete($retour);
}


// http://doc.spip.org/@fichier_admin
function fichier_admin($action) {
	global $connect_login;
	return "admin_".substr(md5($action.(time() & ~2047).$connect_login), 0, 10);
}

// demande la creation d'un repertoire et sort
// ou retourne sans rien faire si repertoire deja la.

// http://doc.spip.org/@debut_admin
function debut_admin($script, $action='', $commentaire='') {
	global $connect_login, $connect_statut, $connect_toutes_rubriques;

	if ((!$action) || ($connect_statut != "0minirezo")) {
		include_spip('inc/minipres');
		echo minipres();
		exit;
	}
	if ($connect_toutes_rubriques) {
		$dir = _DIR_TMP;
	} else {
		$dir = _DIR_TRANSFERT . $connect_login . '/';
	}

	$signal = fichier_admin($script);
	if (@file_exists($dir . $signal)) {
		spip_log ("Action admin: $action");
		return true;
	}
	include_spip('inc/minipres');

	if ($commentaire) {
		$commentaire = ("\n<p>".propre($commentaire)."</p>\n");
	}

	// Si on est un super-admin, un bouton de validation suffit
	// sauf dans les cas destroy
	if (autoriser('webmestre')
	AND $script != 'delete_all') {
		if (_request('validation_admin') == $signal) {
			spip_log ("Action super-admin: $action");
			return;
		}
		$form = '<input type="hidden" name="validation_admin" value="'.$signal.'" />';
		$suivant = _T('bouton_valider');

		$js = '';
	} else {
		$form = "<fieldset><legend>"
		. _T('info_authentification_ftp')
		. aide("ftp_auth")
		. "</legend>\n<label for='fichier'>"
		. _T('info_creer_repertoire')
		. "</label>\n"
		. "<input class='formo' size='40' id='fichier' name='fichier' value='"
		. $signal
		. "' /><br />"
		. _T('info_creer_repertoire_2', array('repertoire' => joli_repertoire($dir)))
		. "</fieldset>";


		$suivant = _T('bouton_recharger_page');

	// code volontairement tordu:
	// provoquer la copie dans le presse papier du nom du repertoire
	// en remettant a vide le champ pour que ca marche aussi en cas
	// de JavaScript inactif.

		$js = " onload='document.forms[0].fichier.value=\"\";barre_inserer(\"$signal\", document.forms[0].fichier)'";
	}

	$form = $commentaire . copy_request($script, $form, $suivant);
	echo minipres(_T('info_action', array('action' => $action)), $form, $js);
	exit;
}

// http://doc.spip.org/@fin_admin
function fin_admin($action) {
	global $connect_login, $connect_toutes_rubriques;
	if ($connect_toutes_rubriques) {
		$dir = _DIR_TMP;
	} else {
		$dir = _DIR_TRANSFERT . $connect_login . '/';
	}
	$signal = fichier_admin($action);
	@unlink($dir . $signal);
	@rmdir($dir . $signal);
}


// http://doc.spip.org/@copy_request
function copy_request($script, $suite, $submit='')
{
        include_spip('inc/filtres');
	foreach($_POST as $n => $c) {
	  if ($n != 'fichier')
		$suite .= "\n<input type='hidden' name='$n' value='" .
		  entites_html($c) .
		  "'  />";
	}
	return  generer_form_ecrire($script, $suite, '', $submit);
}
?>
