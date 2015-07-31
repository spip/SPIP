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


//
if (!defined("_ECRIRE_INC_VERSION")) return;


function fichier_admin($action) {
	global $connect_login;
	return "admin_".substr(md5($action.(time() & ~2047).$connect_login), 0, 10);
}

function debut_admin($action, $commentaire='') {
	global $clean_link;
	global $connect_statut;

	if ((!$action) || ($connect_statut != "0minirezo")) {
		include_ecrire ("inc_minipre.php3");
		install_debut_html(_T('info_acces_refuse'));install_fin_html();
		exit;
	}
	$fichier = fichier_admin($action);
	if (@file_exists(_DIR_SESSIONS . $fichier)) {
		spip_log ("Action admin: $action");
		return true;
	}

	include_ecrire ("inc_minipres.php");
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


function _action_auteur($action, $id_auteur, $nom_alea) {
	if (!$id_auteur) {
		global $connect_id_auteur, $connect_pass;
		$id_auteur = $connect_id_auteur;
		$pass = $connect_pass;
	}
	else {
		$result = spip_query("SELECT pass FROM spip_auteurs WHERE id_auteur=$id_auteur");
		if ($result) if ($row = spip_fetch_array($result)) $pass = $row['pass'];
	}
	$alea = lire_meta($nom_alea);
	return md5($action.$id_auteur.$pass.$alea);
}


function calculer_action_auteur($action, $id_auteur = 0) {
	renouvelle_alea();
	return _action_auteur($action, $id_auteur, 'alea_ephemere');
}

function verifier_action_auteur($action, $valeur, $id_auteur = 0) {
	if ($valeur == _action_auteur($action, $id_auteur, 'alea_ephemere'))
		return true;
	if ($valeur == _action_auteur($action, $id_auteur, 'alea_ephemere_ancien'))
		return true;
	spip_log("inc_admin: verifier action $action $id_auteur : echec");
	return false;
}

function demande_maj_version()
{
	global $spip_version;
	$version_installee = (double) str_replace(',','.',lire_meta('version_installee'));
	if ($version_installee == $spip_version) return false;
	include_ecrire("inc_presentation.php3");
	debut_page();
	if (!$version_installee) $version_installee = _T('info_anterieur');
	echo "<blockquote><blockquote><h4><font color='red'>"._T('info_message_technique')."</font><br> "._T('info_procedure_maj_version')."</h4>
	"._T('info_administrateur_site_01')." <a href='upgrade.php3'>"._T('info_administrateur_site_02')."</a></blockquote></blockquote><p>";
	fin_page();
	return true;
}

?>
