<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
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
if (isset($GLOBALS['meta']['IMPORT_tables_noimport'])){
	$IMPORT_tables_noimport = unserialize($GLOBALS['meta']['IMPORT_tables_noimport']);
	if (!is_array($IMPORT_tables_noimport)){
		ecrire_meta('IMPORT_tables_noimport',serialize(array()),'non');
		$IMPORT_tables_noimport = unserialize($GLOBALS['meta']['IMPORT_tables_noimport']);
	}
}
else{
	ecrire_meta('IMPORT_tables_noimport',
		serialize($IMPORT_tables_noimport),'non');
}

// NB: Ce fichier peut ajouter des tables (old-style)
// donc il faut l'inclure "en globals"
if ($f = find_in_path('mes_fonctions.php')) {
	global $dossier_squelettes;
	@include_once ($f); 
}

// http://doc.spip.org/@base_import_all_dist
function base_import_all_dist($titre='', $reprise=false)
{
	if (!$titre) return; // anti-testeur automatique
	if (!$reprise) import_all_debut();

	$request = unserialize($GLOBALS['meta']['import_all']);

	$archive = $request['dir'] . 
	($request['archive'] ? $request['archive'] : $request['archive_perso']);
	// au rappel, on commence (voire on continue)
	@ini_set("zlib.output_compression","0"); // pour permettre l'affichage au fur et a mesure

	include_spip('inc/import');
	@ignore_user_abort(1);

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page($titre, "accueil", "accueil");

	echo debut_gauche('', true);

	echo debut_droite('', true);
	
	$res = import_all_milieu($request, $archive);

	if (!$res AND $request['insertion'] == 'on') {
			$request['insertion'] = 'passe2';
			if ($request['url_site']
			AND substr($request['url_site'],-1) != '/')
				$request['url_site'] .= '/';
			ecrire_meta("import_all", serialize($request),'non');
			import_all_debut();
			$res = import_all_milieu($request, $archive);
	}

	echo $res, "</body></html>\n";

	if ($charset = $GLOBALS['meta']['charset_restauration']) {
			ecrire_meta('charset', $charset);
	}

	detruit_restaurateur();
	import_all_fin($request);
	include_spip('inc/rubriques');
	calculer_rubriques();
	if (!$res) ecrire_acces();	// Mise a jour du fichier htpasswd
	// revenir a l'accueil pour finir
	affiche_progression_javascript('100 %', 0);
}

// http://doc.spip.org/@import_all_milieu
function import_all_milieu($request, $archive)
{
	global $trans;
	if ($request['insertion'] == 'passe2') {
		include_spip('inc/import_insere');
		$trans = translate_init($request);
	} else $trans = array();

	return import_tables($request, $archive);
}

// http://doc.spip.org/@import_all_debut
function import_all_debut() {
	ecrire_meta("restauration_status", "0",'non');
	ecrire_meta("restauration_status_copie", "0",'non');
}

// http://doc.spip.org/@import_all_fin
function import_all_fin($request) {

	effacer_meta("charset_restauration");
	effacer_meta("charset_insertion");
	effacer_meta("restauration_status");
	effacer_meta("date_optimisation");
	effacer_meta('restauration_version_archive');
	effacer_meta('restauration_tag_archive');
	effacer_meta('restauration_charset_sql_connexion');
	effacer_meta('restauration_attributs_archive');
	effacer_meta('restauration_table_prefix');
	effacer_meta('restauration_table_prefix_source');
	effacer_meta('vieille_version_installee');
	effacer_meta('restauration_status_tables');
	effacer_meta('restauration_recopie_tables');
	if ($request['insertion'] == 'passe2') 
		sql_drop_table("spip_translate");
	 
}
?>
