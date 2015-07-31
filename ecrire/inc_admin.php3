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

if (!defined("_ECRIRE_INC_VERSION")) return;

function fichier_admin($action) {
	global $connect_login;
	return "admin_".substr(md5($action.(time() & ~2047).$connect_login), 0, 10);
}

function debut_admin($action, $commentaire='') {
	global $clean_link;
	global $connect_statut;

	if ((!$action) || ($connect_statut != "0minirezo")) {
		include_ecrire ("inc_minipres.php");
		install_debut_html(_T('info_acces_refuse'));install_fin_html();
		exit;
	}
	$fichier = fichier_admin($action);
	if (@file_exists(_DIR_SESSIONS . $fichier)) {
		spip_log ("Action admin: $action");
		return true;
	}

	include_ecrire ("inc_minipres.php");
	include_ecrire ("inc_texte.php3");
	install_debut_html(_T('info_action', array('action' => $action)));

	if ($commentaire) {
		echo "<p>".propre($commentaire)."</p>";
	}

	echo $clean_link->getForm('POST');
	echo "<P><B>"._T('info_authentification_ftp')."</B>";
	echo aide("ftp_auth");
	echo "<P>"._T('info_creer_repertoire');
	echo "<P align='center'><INPUT TYPE='text' NAME='fichier' CLASS='fondl' VALUE=\"$fichier\" SIZE='30'>";
	echo "<P> "._T('info_creer_repertoire_2');
	echo "<P align='right'><INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_recharger_page')."' CLASS='fondo'>";
	echo "</FORM>";

	install_fin_html();
	exit;
}

function fin_admin($action) {
	$fichier = fichier_admin($action);
	@unlink(_DIR_SESSIONS . $fichier);
	@rmdir(_DIR_SESSIONS . $fichier);
}

?>
