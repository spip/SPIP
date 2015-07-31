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

// http://doc.spip.org/@verifier_sauvegarde
function verifier_sauvegarde ($archive, $dir) {
	global $spip_version;

	$g = ereg("\.gz$", $archive);
	$_fopen = ($g) ? gzopen : fopen;
	$_fread = ($g) ? gzread : fread;
	$buf_len = 1024; // la version doit etre dans le premier ko
	$g = $dir . $archive;

	if (@file_exists($g) AND $f = $_fopen($g, "rb")) {
		$buf = $_fread($f, $buf_len);

		if (ereg('<SPIP [^>]* version_base="([0-9.]+)".*version_archive="([^"]+)"', $buf, $regs)
		AND $regs[1] == $spip_version
		AND import_charge_version($regs[2], 'inc', true))
			return false; // c'est bon
		else
			return _T('avis_erreur_version_archive', array('archive' => $archive));
	} else
		return _T('avis_probleme_archive', array('archive' => $g));
}


// http://doc.spip.org/@import_charge_version
function import_charge_version($version_archive)
{
	if (preg_match("{^phpmyadmin::}is",$version_archive)){
	#spip_log("restauration phpmyadmin : version $version_archive tag $tag_archive");
		$fimport = 'import_1_3'; 
	} else 	$fimport = 'import_' . str_replace('.','_',$version_archive);

	return  charger_fonction($fimport, 'inc', true);
}

// http://doc.spip.org/@exec_import_all_dist
function exec_import_all_dist()
{
	$dir = import_queldir();

	// si l'appel est explicite, 
	// passer par l'authentification ftp et attendre d'etre rappele
	if (!$GLOBALS['meta']["debut_restauration"]) {
		$archive=_request('archive');
		$insertion=_request('insertion');
		if (!strlen($archive)) $archive=_request('archive_perso');
		if ($archive) {
			$action = _T('info_restauration_sauvegarde', array('archive' => $archive));
			$commentaire = verifier_sauvegarde($archive, $dir);
		}

		// au tout premier appel, on ne revient pas de debut_admin

		debut_admin("import_all", $action, $commentaire);

		// si on est revenu c'est que l'authentification ftp est ok
		// sinon il reste le meta request_restau; a ameliorer.
		fin_admin($action);
		import_all_debut($_REQUEST);
		$request = $_REQUEST;
	} else $request = unserialize($GLOBALS['meta']['request_restauration']);
	// au rappel, on commence (voire on continue)
	@ini_set("zlib.output_compression","0"); // pour permettre l'affichage au fur et a mesure
	// utiliser une version fraiche des metas (ie pas le cache)
	include_spip('inc/meta');
	lire_metas();
	include_spip('inc/import');
	@ignore_user_abort(1);

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_index'), "accueil", "accueil");

	debut_gauche();

	debut_droite();
	
	// precaution inutile I think (esj)
	list($my_date) = spip_fetch_array(spip_query("SELECT UNIX_TIMESTAMP(maj) AS d FROM spip_meta WHERE nom='debut_restauration'"), SPIP_NUM);

	if ($my_date) echo import_tables($request, $dir);

	import_all_fin();
}

// http://doc.spip.org/@import_all_debut
function import_all_debut($request) {
	ecrire_meta("request_restauration", serialize($request));
	ecrire_meta("debut_restauration", "debut");
	ecrire_meta("status_restauration", "0");
	ecrire_metas();
}

// http://doc.spip.org/@import_all_fin
function import_all_fin() {

	effacer_meta("charset_restauration");
	effacer_meta("status_restauration");
	effacer_meta("debut_restauration");
	effacer_meta("date_optimisation");
	effacer_meta('request_restauration');
	effacer_meta('version_archive_restauration');
	effacer_meta('tag_archive_restauration');
	ecrire_metas();
}

// http://doc.spip.org/@import_queldir
function import_queldir()
{
	global $connect_toutes_rubriques;

	if ($connect_toutes_rubriques) {
		$repertoire = _DIR_DUMP;
		if(!@file_exists($repertoire)) {
			$repertoire = preg_replace(','._DIR_TMP.',', '', $repertoire);
			$repertoire = sous_repertoire(_DIR_TMP, $repertoire);
		}
		return $repertoire;
	} else {
		$repertoire = _DIR_TRANSFERT;
		if(!@file_exists($repertoire)) {
			$repertoire = preg_replace(','._DIR_TMP.',', '', $repertoire);
			$repertoire = sous_repertoire(_DIR_TMP, $repertoire);
		}
		if(!@file_exists($repertoire.$connect_login)) {
			$sous_rep = sous_repertoire($repertoire, $connect_login);
		}
		return $sous_rep . '/';
	}

}
?>
