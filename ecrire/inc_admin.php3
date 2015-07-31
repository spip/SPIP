<?

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_ADMIN")) return;
define("_ECRIRE_INC_ADMIN", "1");


function aff_aide2($aide) {
	return " <FONT SIZE=1>[<B><A HREF='#' onMouseDown=\"window.open('aide_index.php3?aide=$aide','myWindow','scrollbars=yes,resizable=yes,width=550')\">AIDE</A></B>]</FONT>";
}

function fichier_admin($action) {
	global $connect_login;
	return "admin_".substr(md5($action.(time() & ~2047).$connect_login), 0, 10);
}

function debut_admin($action) {
	global $REQUEST_URI;
	global $connect_statut;

	if (!$requete_fichier) {
		$requete_fichier = substr($REQUEST_URI, strrpos($REQUEST_URI, '/') + 1);
	}
	$lien = $requete_fichier;

	if ((!$action) || ($connect_statut != "0minirezo")) {
		echo "<H3>Acc&egrave;s refus&eacute;.</H3>";
		exit;
	}
	$fichier = fichier_admin($action);
	if (file_exists("data/$fichier")) return true;

	include_local ("inc_presentation.php3");
	install_debut_html("Action : $action");
	
		echo "<FORM ACTION='$lien' METHOD='post'>";
		echo "<P><B>Authentification (par FTP).</B>";
		echo aff_aide2("ftp_auth");
		echo "<P>Veuillez cr&eacute;er un fichier ou un r&eacute;pertoire nomm&eacute;&nbsp;:";
		echo "<P align='center'><INPUT TYPE='text' NAME='fichier' CLASS='fondl' VALUE=\"$fichier\" SIZE='30'>";
		echo "<P> &agrave; l'int&eacute;rieur du sous-r&eacute;pertoire <b>ecrire/data/</b>, puis&nbsp;:";
		echo "<P align='right'><INPUT TYPE='submit' NAME='Valider' VALUE='recharger cette page' CLASS='fondo'>";
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
		$result = mysql_query("SELECT pass FROM spip_auteurs WHERE id_auteur=$id_auteur");
		if ($result) if ($row = mysql_fetch_array($result)) $pass = $row[0];
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