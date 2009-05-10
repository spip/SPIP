<?php
if (!defined("_ECRIRE_INC_VERSION")) return;

function action_mes_fichiers_sauver() {

	// SŽcurisation: aucun argument attendu
 
	// Autorisation
	if(!autoriser('sauvegarder','mes_fichiers')) {
		include_spip('inc/minipres');
		echo minipres();
		exit;
	}

	// Contenu de la sauvegarde	
	include_spip('inc/pclzip');
	include_spip('inc/mes_fichiers_utils');
	$liste = _request('a_sauver');
	spip_log('*** mes_fichiers ***');
	spip_log($liste);

	// Archivage du contenu
	$mes_fichiers = new PclZip(_DIR_TMP . 'mes_fichiers_'.date("Ymd_Hi").'.zip');
	$erreur = $mes_fichiers->create($liste, PCLZIP_OPT_ADD_PATH, "spip");
	if ($erreur == 0) {
		redirige_par_entete(generer_url_ecrire('mes_fichiers', 'etat=nok_sauve', true));
	}

	// Redirection vers la page mes_fichiers avec l'Žtat ok
	redirige_par_entete(generer_url_ecrire('mes_fichiers', 'etat=ok_sauve', true));
}
?>