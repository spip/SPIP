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

function fichier_admin($action) {
	global $connect_login;
	return "admin_".substr(md5($action.(time() & ~2047).$connect_login), 0, 10);
}

function debut_admin($action, $commentaire='') {
	global $clean_link;
	global $connect_statut;

	if ((!$action) || ($connect_statut != "0minirezo")) {
		include_ecrire ("inc_minipres");
		minipres(_T('info_acces_refuse'));
	}
	$fichier = fichier_admin($action);
	if (@file_exists(_DIR_SESSIONS . $fichier)) {
		spip_log ("Action admin: $action");
		return true;
	}
	if ($commentaire) {
		include_ecrire ("inc_texte");
		$commentaire = ("\n<p>".propre($commentaire)."</p>\n");
	}
	include_ecrire ("inc_minipres");
	minipres(_T('info_action', array('action' => $action)),
		  $commentaire
		. $clean_link->getForm('POST')
		. "\n<p><b>"._T('info_authentification_ftp')."</b>"
		. aide("ftp_auth")
		. "\n<p>"
		. _T('info_creer_repertoire')
		. "\n<p align='center'>\n<INPUT TYPE='text' NAME='fichier' CLASS='fondl' VALUE=\"".
		 $fichier
		. "\" size='30'>"
		. "\n<p>"
		. _T('info_creer_repertoire_2', array('repertoire' => _DIR_SESSIONS))
		. "\n<p align='right'><INPUT TYPE='submit' VALUE='"
		. _T('bouton_recharger_page')
		. "' CLASS='fondo'>"
		. "</form>");
}

function fin_admin($action) {
	$fichier = fichier_admin($action);
	@unlink(_DIR_SESSIONS . $fichier);
	@rmdir(_DIR_SESSIONS . $fichier);
}


function demande_maj_version()
{
	include_ecrire("inc_presentation");
	debut_page();
	echo "<blockquote><blockquote><h4><font color='red'>",
	_T('info_message_technique'),
	"</font><br> ",
	_T('info_procedure_maj_version'),
	"</h4>",
	_T('info_administrateur_site_01'),
	" <a href='" . generer_url_ecrire("upgrade","reinstall=non") . "'>",
	_T('info_administrateur_site_02'),
	"</a></blockquote></blockquote><p>";
	fin_page();
	exit;
}
?>
