<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_ADMIN")) return;
define("_ECRIRE_INC_ADMIN", "1");


function fichier_admin($action) {
	global $connect_login;
	return "admin_".substr(md5($action.(time() & ~2047).$connect_login), 0, 10);
}

function debut_admin($action, $commentaire='') {
	global $this_link;
	global $connect_statut;

	if ((!$action) || ($connect_statut != "0minirezo")) {
		include_ecrire ("inc_presentation.php3");
		install_debut_html(_T('info_acces_refuse'));
		install_fin_html();
		exit;
	}
	$fichier = fichier_admin($action);
	if (file_exists("data/$fichier")) {
		spip_log ("Action admin: $action");
		return true;
	}

	include_ecrire ("inc_presentation.php3");
	install_debut_html(_T('info_action', array('action' => $action)));

		if ($commentaire) {
			echo "<p>".propre($commentaire)."</p>";
		}	

		echo $this_link->getForm('POST');
		echo "<P><B>"._T('info_authentification_ftp')."</B>";
		echo aide("ftp_auth");
		echo "<P>"._T('info_creer_repertoire')."&nbsp;:";
		echo "<P align='center'><INPUT TYPE='text' NAME='fichier' CLASS='fondl' VALUE=\"$fichier\" SIZE='30'>";
		echo "<P> "._T('info_creer_repertoire_2')."&nbsp;:";
		echo "<P align='right'><INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_recharger_page')."' CLASS='fondo'>";
		echo "</FORM>";

	install_fin_html();
	exit;
}

function fin_admin($action) {
	$fichier = fichier_admin($action);
	@unlink("data/$fichier");
	@rmdir("data/$fichier");
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
	return _action_auteur($action, $id_auteur, 'alea_ephemere');
}

function verifier_action_auteur($action, $valeur, $id_auteur = 0) {
	if ($valeur == _action_auteur($action, $id_auteur, 'alea_ephemere')) return true;
	if ($valeur == _action_auteur($action, $id_auteur, 'alea_ephemere_ancien')) return true;
	return false;
}


?>