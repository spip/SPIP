<?php

include ("inc_version.php3");

include_ecrire ("inc_auth.php3");
include_ecrire ("inc_import.php3");
include_ecrire ("inc_admin.php3");
include_ecrire ("inc_meta.php3");
include_ecrire("inc_texte.php3");
include_ecrire("inc_filtres.php3");

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
			return _T('avis_erreur_version_archive', array('archive' => $archive));
    } else
		return _T('avis_probleme_archive', array('archive' => $archive));
	
}

if ($archive) {
	$action = _T('info_restauration_sauvegarde', array('archive' => $archive));
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
