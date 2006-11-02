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

// http://doc.spip.org/@verifier_version_sauvegarde
function verifier_version_sauvegarde ($archive) {
	global $spip_version;
	global $flag_gz, $connect_toutes_rubriques;

	if ($connect_toutes_rubriques) {
		$dir = _DIR_DUMP;
	} else {
		$dir = _DIR_TRANSFERT . $connect_login . '/';
	}
	$_fopen = ($flag_gz) ? gzopen : fopen;
	$_fread = ($flag_gz) ? gzread : fread;
	$buf_len = 1024; // la version doit etre dans le premier ko

	if (@file_exists($f = $dir . $archive)) {
		$f = $_fopen($f, "rb");
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
	// si l'appel est explicite, 
	// passer par l'authentification ftp et attendre d'etre rappele
	if (!$GLOBALS['meta']["debut_restauration"]) {
	// cas de l'appel apres demande de confirmation
		$archive=_request('archive');
		if (!strlen($archive)) $archive=_request('archive_perso');
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
	import_all_continue();
	include_spip('inc/rubriques');
	calculer_rubriques();
}


// http://doc.spip.org/@import_all_continue
function import_all_continue()
{
	global $meta, $flag_gz, $buf, $abs_pos, $my_pos, $connect_toutes_rubriques;
	global $affiche_progression_pourcent;
	@ini_set("zlib.output_compression","0"); // pour permettre l'affichage au fur et a mesure
	// utiliser une version fraiche des metas (ie pas le cache)
	include_spip('inc/meta');
	lire_metas();
	include_spip('inc/import');
	@ignore_user_abort(1);

	$request = unserialize($meta['request_restauration']);
	if ($connect_toutes_rubriques) {
		$dir = _DIR_DUMP;
	} else {
		$dir = _DIR_TRANSFERT . $connect_login . '/';
	}
	$archive = $dir . $request['archive'];
	$affiche_progression_pourcent = @filesize($archive);

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_index'), "accueil", "accueil");

	debut_gauche();

	debut_droite();

	// attention : si $request['archive']=="", alors archive='data/' 
	// le test is_readable n'est donc pas suffisant
	if (!@is_readable($archive)||is_dir($archive) || !$affiche_progression_pourcent) {
		$texte_boite = _T('info_erreur_restauration');
		debut_boite_alerte();
		echo "<font face='Verdana,Arial,Sans,sans-serif' size='4' color='black'><b>$texte_boite</b></font>";
		fin_boite_alerte();

		// faut faire quelque chose, sinon le site est mort :-)
		// a priori on reset les meta de restauration car rien n'a encore commence
		effacer_meta('request_restauration');
		effacer_meta('fichier_restauration');
		effacer_meta('version_archive_restauration');
		effacer_meta('tag_archive_restauration');
		effacer_meta('status_restauration');
		effacer_meta('debut_restauration');
		effacer_meta('charset_restauration');
		ecrire_metas();
		exit;
	}

	$my_pos = $meta["status_restauration"];

	if (ereg("\.gz$", $archive)) {
			$affiche_progression_pourcent = false;
			$taille = taille_en_octets($my_pos);
			$gz = true;
	} else {
			$taille = floor(100 * $my_pos / $affiche_progression_pourcent)." %";
			$gz = false;
		}
	$texte_boite = _T('info_base_restauration')."<p>
		<form name='progression'><center><input type='text' size=10 style='text-align:center;' name='taille' value='$taille'><br>
		<input type='text' class='forml' name='recharge' value='"._T('info_recharger_page')."'></center></form>";

	debut_boite_alerte();
	echo "<font FACE='Verdana,Arial,Sans,sans-serif' SIZE=4 color='black'><B>$texte_boite</B></font>";
	fin_boite_alerte();
	$max_time = ini_get('max_execution_time')*1000;
	echo ("<script language=\"JavaScript\" type=\"text/javascript\">window.setTimeout('location.href=\"".self()."\";',$max_time);</script>\n");

	if ($GLOBALS['flag_ob_flush']) ob_flush();
	flush();

	$_fopen = ($gz) ? 'gzopen' : 'fopen';
	$f = $_fopen($archive, "rb");
	$buf = "";
	$r = import_tables($f, $gz);
	if ($r) {
		spip_log("Erreur: $r");
	}
	else {
		if ($charset = $GLOBALS['meta']['charset_restauration'])
			ecrire_meta('charset', $charset);
	}

	import_fin();
	echo "</body></html>\n";
}
?>
