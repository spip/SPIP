<?php

include ("inc_version.php3");
include_local ("inc_connect.php3");
include_local ("inc_auth.php3");
include_local ("inc_import.php3");
include_local ("inc_admin.php3");
include_local ("inc_meta.php3");

function verifier_version_sauvegarde ($archive) {
	global $spip_version;
	global $flag_gz;

	$ok = file_exists("data/$archive");
	$gz = $flag_gz;
	$_fopen = ($gz) ? gzopen : fopen;
	$_fread = ($gz) ? gzread : fread;
	$buf_len = 1024; // la version doit etre dans le premier ko

	if ($ok) {
		$f = $_fopen("data/$archive", "rb");
		$buf = $_fread($f, $buf_len);

		if (ereg("<SPIP [^>]* version_base=\"([0-9\.]+)\" ", $buf, $regs)
		AND $regs[1] == $spip_version)
			return false; // c'est bon
		else
			return "{{Attention! Le fichier $archive correspond &agrave;
				une autre version de SPIP que celle que vous avez
				install&eacute;e.}} Vous allez au-devant de grosses
				difficult&eacute;s: risque de destruction de votre base de
				donn&eacute;es, dysfonctionnements divers du site, etc. Ne
				validez pas cette demande d'importation.<p>Pour plus
				d'informations, voyez [la documentation de
				SPIP->.net/article1489.html].";
    } else
		return "Probl&eagrave; de lecture du fichier $archive";
	
}

if ($archive) {
	$action = "restauration de la sauvegarde $archive";
	$commentaire = verifier_version_sauvegarde ($archive);
}

debut_admin($action, $commentaire);


$archive = "data/$archive";

ecrire_meta("debut_restauration", "debut");
ecrire_meta("fichier_restauration", $archive);
ecrire_meta("status_restauration", "0");
ecrire_metas();

fin_admin($action);

@header("Location: index.php3");

exit;

?>
