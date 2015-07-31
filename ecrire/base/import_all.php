<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('base/dump');

/**
 * On arrive ici depuis inc/admin
 * - au lancement
 * - puis apres chaque timeout avec dans ce cas $reprise=true
 *
 * import_all_debut() est appele la premiere fois et initialise le dump
 * import_all_milieu() est appele a chaque retour ici
 * quand on en sort,
 *	- soit on relance pour la seconde etape de fusion
 *  - soit on finit
 *
 * @param string $titre
 * @param bool $reprise
 */
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

	// Cas particulier de la fusion/insertion :
	// a la fin de la premiere passe on relance pour l'etape suivante
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
	} 
	else
		$trans = array();

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
