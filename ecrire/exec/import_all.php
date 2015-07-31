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

include_spip('inc/admin');
include_spip('base/serial');
include_spip('base/auxiliaires');

// par defaut tout est importe sauf les tables ci-dessous
// possibiliter de definir cela tables via la meta
global $IMPORT_tables_noimport;
if (isset($GLOBALS['meta']['IMPORT_tables_noimport']))
	$IMPORT_tables_noimport = unserialize($GLOBALS['meta']['IMPORT_tables_noimport']);
else{
	include_spip('inc/meta');
	$IMPORT_tables_noimport[]='spip_ajax_fonc';
	$IMPORT_tables_noimport[]='spip_caches';
	$IMPORT_tables_noimport[]='spip_meta';
	ecrire_meta('IMPORT_tables_noimport',serialize($IMPORT_tables_noimport));
	ecrire_metas();
}

// NB: Ce fichier peut ajouter des tables (old-style)
// donc il faut l'inclure "en globals"
if ($f = include_spip('mes_fonctions', false)) {
	global $dossier_squelettes;
	@include_once ($f); 
}

function verifier_version_sauvegarde ($archive) {
	global $spip_version;
	global $flag_gz;

	$_fopen = ($flag_gz) ? gzopen : fopen;
	$_fread = ($flag_gz) ? gzread : fread;
	$buf_len = 1024; // la version doit etre dans le premier ko

	if (@file_exists(_DIR_SESSIONS . $archive)) {
		$f = $_fopen(_DIR_SESSIONS . $archive, "rb");
		$buf = $_fread($f, $buf_len);

		if (ereg('<SPIP [^>]* version_base="([0-9.]+)".*version_archive="([^"]+)"', $buf, $regs)
		AND $regs[1] == $spip_version
		AND import_charge_version($regs[2], 'inc', true))
			return false; // c'est bon
		else
			return _T('avis_erreur_version_archive', array('archive' => $archive));
	} else
		return _T('avis_probleme_archive', array('archive' => $archive));
}


function import_charge_version($version_archive)
{
	if (preg_match("{^phpmyadmin::}is",$version_archive)){
	#spip_log("restauration phpmyadmin : version $version_archive tag $tag_archive");
		$fimport = 'import_1_3'; 
	} else 	$fimport = 'import_' . str_replace('.','_',$version_archive);

	return  charger_fonction($fimport, 'inc', true);
}

function exec_import_all_dist()
{
	global $archive;

	// si l'appel est explicite, 
	// passer par l'authentification ftp et attendre d'etre rappele
	if (!$GLOBALS['meta']["debut_restauration"]) {
	// cas de l'appel apres demande de confirmation
		if ($archive) {
			$action = _T('info_restauration_sauvegarde', array('archive' => $archive));
			$commentaire = verifier_version_sauvegarde ($archive);
		}

		// au tout premier appel, on ne revient pas de debut_admin
		debut_admin(generer_url_post_ecrire("import_all","archive=$archive"), $action, $commentaire);

		// on est revenu: l'authentification ftp est ok
		fin_admin($action);
		// dire qu'on commence
		ecrire_meta("request_restauration", serialize($_REQUEST));
		ecrire_meta("debut_restauration", "debut");
		ecrire_meta("status_restauration", "0");
		ecrire_metas();
		// se rappeler pour montrer illico ce qu'on fait 
		header('Location: ./');
		exit();
	}

	// au rappel, on commence (voire on continue)
	include_spip('inc/import');
	import_all_continue();
}
?>
