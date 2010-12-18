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

/**
 * 
 * On arrive ici depuis exec=admin_tech
 * - le premier coup on initialise par exec_export_all_args puis export_all_start
 * - ensuite on enchaine sur inc/export, qui remplit le dump et renvoie ici a chaque timeout
 * - a chaque coup on relance inc/export
 * - lorsque inc/export a fini, il retourne $arg
 * - on l'utilise pour clore le fichier
 * - on renvoie
 *   vers action=export_all pour afficher le resume
 * 
 */

include_spip('inc/presentation');
include_spip('base/dump');

// http://doc.spip.org/@exec_export_all_dist
function exec_export_all_dist(){
	$rub = intval(_request('id_parent'));
	$meta = base_dump_meta_name($rub);

	if (!isset($GLOBALS['meta'][$meta])){
		// c'est un demarrage en arrivee directe depuis exec=admin_tech
		// on initialise  (mais si c'est le validateur, ne rien faire)
		if ($GLOBALS['exec'] == 'valider_xml') return;
		exec_export_all_args($meta, $rub, _request('gz'));
	}

	$export = charger_fonction('export', 'inc');
	$arg = $export($meta);
	@list(, $gz, $archive, $rub, $version) = explode(',', $arg);

	// quand on sort de $export c'est qu'on a fini
	export_all_end($meta,$archive);

	include_spip('inc/headers');
	redirige_par_entete(generer_action_auteur("export_all",$arg,'',true, true));

}

// L'en tete du fichier doit etre cree a partir de l'espace public
// Ici on construit la liste des tables pour confirmation.
// Envoi automatique en cas d'inaction (sauf si appel incorrect $nom=NULL)

function exec_export_all_args($meta, $rub, $gz){

	$gz = $gz ? '.gz' : '';
	$nom = $gz 
	?  _request('znom_sauvegarde') 
	:  _request('nom_sauvegarde');

	if (!preg_match(',^[\w_][\w_.]*$,', $nom)) $nom = 'dump';
	$archive = $nom . '.xml' . $gz;

	// si pas de tables listees en post, utiliser la liste par defaut
	if (!$tables = _request('export'))
		list($tables,) = base_liste_table_for_dump(lister_tables_noexport());

	export_all_start($meta, $gz, $archive, $rub, _VERSION_ARCHIVE, $tables);
	
}


function export_all_start($meta, $gz, $archive, $rub, $version, $tables){

	// determine upload va aussi initialiser l'index "restreint"
	$maindir = determine_upload();
	if (!$GLOBALS['visiteur_session']['restreint'])
		$maindir = _DIR_DUMP;
	$dir = sous_repertoire($maindir, $meta);
	$file = $dir . $archive;

	utiliser_langue_visiteur();

	// en mode partiel, commencer par les articles et les rubriques
	// pour savoir quelles parties des autres tables sont a sauver
	if ($rub) {
		if ($t = array_search('spip_rubriques', $tables)) {
			unset($tables[$t]);
			array_unshift($tables, 'spip_rubriques');
		}
		if ($t = array_search('spip_articles', $tables)) {
			unset($tables[$t]);
			array_unshift($tables, 'spip_articles');
		}
	}
	// creer l'en tete du fichier et retourner dans l'espace prive
	ecrire_fichier($file, export_entete($version),false);
	$v = serialize(array($gz, $archive, $rub, $tables, 1, 0));
	ecrire_meta($meta, $v, 'non');
	include_spip('inc/headers');
		// rub=$rub sert AUSSI a distinguer cette redirection
		// d'avec l'appel initial sinon FireFox croit malin
		// d'optimiser la redirection
	redirige_url_ecrire('export_all',"rub=$rub");


}

function export_all_end($meta, $archive){
	$dir = base_dump_dir($meta);
	$file = $dir . $archive;
	ecrire_fichier($file, export_enpied(),false,false);
}

// http://doc.spip.org/@export_entete
function export_entete($version_archive)
{
	return
"<" . "?xml version=\"1.0\" encoding=\"".
$GLOBALS['meta']['charset']."\"?".">\n" .
"<SPIP
	version=\"" . $GLOBALS['spip_version_affichee'] . "\"
	version_base=\"" . $GLOBALS['spip_version_base'] . "\"
	version_archive=\"" . $version_archive . "\"
	adresse_site=\"" .  $GLOBALS['meta']["adresse_site"] . "\"
	dir_img=\"" . _DIR_IMG . "\"
	dir_logos=\"" . _DIR_LOGOS . "\"
>\n";
}


// production de l'entete du fichier d'archive
// http://doc.spip.org/@export_enpied
function export_enpied () { return  "</SPIP>\n";}

?>
