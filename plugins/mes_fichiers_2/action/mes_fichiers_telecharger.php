<?php

if (!defined("_ECRIRE_INC_VERSION")) return;

function action_mes_fichiers_telecharger() {

	// SŽcurisation: aucun argument attendu
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();
	if (!@is_readable($arg)) {
		spip_log("action_telecharger_mes_fichiers $arg pas accessible en lecture");
		redirige_par_entete(generer_url_ecrire('mes_fichiers', 'etat=nok_tele', true));
	}

	// Autorisation
	if(!autoriser('sauvegarder','mes_fichiers')) {
		include_spip('inc/minipres');
		echo minipres();
		exit;
	}

	// Telechargement du fichier
	header("Content-type: application/force-download;");
	header("Content-Transfer-Encoding: application/zip");
	header("Content-Length: ".filesize($arg));
	header("Content-Disposition: attachment; filename=\"".basename($arg)."\"");
	header("Pragma: no-cache");
	header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0, public");
	header("Expires: 0");
	readfile($arg);
	redirige_par_entete(generer_url_ecrire('mes_fichiers', 'etat=ok_tele', true));
}

?>