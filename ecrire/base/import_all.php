<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('base/serial');
include_spip('base/auxiliaires');

// par defaut tout est importe sauf les tables ci-dessous
// possibiliter de definir cela tables via la meta
global $IMPORT_tables_noimport;
if (isset($GLOBALS['meta']['IMPORT_tables_noimport']))
	$IMPORT_tables_noimport = unserialize($GLOBALS['meta']['IMPORT_tables_noimport']);
else{
	include_spip('inc/meta');
	$IMPORT_tables_noimport[]='spip_caches';
	ecrire_meta('IMPORT_tables_noimport',serialize($IMPORT_tables_noimport),'non');
	ecrire_metas();
}

// NB: Ce fichier peut ajouter des tables (old-style)
// donc il faut l'inclure "en globals"
if ($f = include_spip('mes_fonctions', false)) {
	global $dossier_squelettes;
	@include_once ($f); 
}

// http://doc.spip.org/@import_charge_version
function import_charge_version($version_archive)
{
	if (preg_match("{^phpmyadmin::}is",$version_archive)){
		$fimport = 'import_1_3'; 
	} else 	$fimport = 'import_' . str_replace('.','_',$version_archive);

	return  charger_fonction($fimport, 'inc', true);
}

// http://doc.spip.org/@base_import_all_dist
function base_import_all_dist($titre, $reprise=false)
{
	if (!$reprise) import_all_debut();

	$request = unserialize($GLOBALS['meta']['import_all']);
	// au rappel, on commence (voire on continue)
	@ini_set("zlib.output_compression","0"); // pour permettre l'affichage au fur et a mesure
	// utiliser une version fraiche des metas (ie pas le cache)
	include_spip('inc/meta');
	lire_metas();
	include_spip('inc/import');
	@ignore_user_abort(1);

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page($titre, "accueil", "accueil");

	debut_gauche();

	debut_droite();
	
	// precaution inutile I think (esj)
	list($my_date) = spip_fetch_array(spip_query("SELECT UNIX_TIMESTAMP(maj) AS d FROM spip_meta WHERE nom='import_all'"), SPIP_NUM);

	$res = $my_date ? import_all_milieu($request) : '';

	echo $res, "</body></html>\n";

	if ($request['insertion'] == 'on' AND !$res) {
			$request['insertion'] = 'passe2';
			if ($request['url_site']
			AND substr($request['url_site'],-1) != '/')
				$request['url_site'] .= '/';
			ecrire_meta("import_all", serialize($request),'non');
			import_all_debut();
			$res = import_all_milieu($request);
	}
 
	if ($charset = $GLOBALS['meta']['charset_restauration']) {
			ecrire_meta('charset', $charset);
			ecrire_metas();
	}

	detruit_restaurateur();
	import_all_fin($request);
	include_spip('inc/rubriques');
	calculer_rubriques();

	if (!$res) ecrire_acces();	// Mise a jour du fichier htpasswd
}

// http://doc.spip.org/@import_all_milieu
function import_all_milieu($request)
{
	global $trans;
	if ($request['insertion'] == 'passe2') {
		include_spip('inc/import_insere');
		$trans = translate_init($request);
	} else $trans = array();

	return import_tables($request, $request['dir']);
}

// http://doc.spip.org/@import_all_debut
function import_all_debut() {
	ecrire_meta("status_restauration", "0",'non');
	ecrire_metas();
}

// http://doc.spip.org/@import_all_fin
function import_all_fin($request) {

	effacer_meta("charset_restauration");
	effacer_meta("charset_insertion");
	effacer_meta("status_restauration");
	effacer_meta("date_optimisation");
	effacer_meta('version_archive_restauration');
	effacer_meta('tag_archive_restauration');
	ecrire_metas();
	if ($request['insertion'] == 'passe2') 
		spip_query("DROP TABLE spip_translate");
	 
}
?>
