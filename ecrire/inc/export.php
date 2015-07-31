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
include_spip('public/interfaces'); // pour table_des_tables

$GLOBALS['version_archive'] = '1.3';
// NB: Ce fichier peut ajouter des tables (old-style)
// donc il faut l'inclure "en globals"
if ($f = include_spip('mes_fonctions', false)) {
	global $dossier_squelettes;
	@include_once ($f); 
}
if (@is_readable(_DIR_TMP."charger_plugins_fonctions.php")){
	// chargement optimise precompile
	include_once(_DIR_TMP."charger_plugins_fonctions.php");
}

// par defaut tout est exporte sauf les tables ci-dessous

global $EXPORT_tables_noexport;

if (!isset($EXPORT_tables_noexport)){
	$EXPORT_tables_noexport= array(
		'spip_caches', // plugin invalideur
		'spip_index',
		'spip_index_dico',
		'spip_referers',
		'spip_referers_articles',
		'spip_visites',
		'spip_visites_articles',
		'spip_ortho_cache',
		'spip_ortho_dico',
		'spip_versions', // le dump des fragments n'est pas robuste
		'spip_versions_fragments' // le dump des fragments n'est pas robuste
		);
	if (!$GLOBALS['connect_toutes_rubriques']){
		$EXPORT_tables_noexport[]='spip_messages';
		$EXPORT_tables_noexport[]='spip_auteurs_messages';
	}
}

// construction de la liste des tables pour le dump :
// toutes les tables principales
// + toutes les tables auxiliaires hors relations
// + les tables relations dont les deux tables liees sont dans la liste

// http://doc.spip.org/@export_all_list_tables
function export_all_list_tables()
{
	$tables_for_dump = array();
	$tables_pointees = array();
	global $EXPORT_tables_noexport;
	global $tables_principales;
	global $tables_auxiliaires;
	global $table_des_tables;
	global $tables_jointures;

// on construit un index des tables de liens
// pour les ajouter SI les deux tables qu'ils connectent sont sauvegardees
	$tables_for_link = array();
	foreach($tables_jointures as $table => $liste_relations)
		if (is_array($liste_relations))
		{
			$nom = $table;
			if (!isset($tables_auxiliaires[$nom])&&!isset($tables_principales[$nom]))
				$nom = "spip_$table";
			if (isset($tables_auxiliaires[$nom])||isset($tables_principales[$nom])){
				foreach($liste_relations as $link_table){
					if (isset($tables_auxiliaires[$link_table])/*||isset($tables_principales[$link_table])*/){
						$tables_for_link[$link_table][] = $nom;
					}
					else if (isset($tables_auxiliaires["spip_$link_table"])/*||isset($tables_principales["spip_$link_table"])*/){
						$tables_for_link["spip_$link_table"][] = $nom;
					}
				}
			}
		}
	
	$liste_tables = array_merge(array_keys($tables_principales),array_keys($tables_auxiliaires));
	foreach($liste_tables as $table){
	  //		$name = preg_replace("{^spip_}","",$table);
	  if (		!isset($tables_pointees[$table]) 
	  		&&	!in_array($table,$EXPORT_tables_noexport) 
	  		&&	!isset($tables_for_link[$table])){
			$tables_for_dump[] = $table;
			$tables_pointees[$table] = 1;
		}
	}
	foreach ($tables_for_link as $link_table =>$liste){
		$connecte = true;
		foreach($liste as $connect_table)
			if (!in_array($connect_table,$tables_for_dump))
				$connecte = false;
		if ($connecte)
			# on ajoute les liaisons en premier
			# si une restauration est interrompue,
			# cela se verra mieux si il manque des objets
			# que des liens
			array_unshift($tables_for_dump,$link_table);
	}
	return array($tables_for_dump, $tables_for_link);
}

// Concatenation des tranches
// Il faudrait ouvrir une seule fois le fichier, et d'abord sous un autre nom
// et sans detruire les tranches: au final renommage+destruction massive pour
// prevenir autant que possible un Time-out.

// http://doc.spip.org/@ramasse_parties
function ramasse_parties($dir, $archive)
{
	$files = preg_files($dir . $archive . ".part_[0-9]+_[0-9]+[.gz]?");

	$ok = true;
	$files_o = array();
	$but = $dir . $archive;
	foreach($files as $f) {
	  $contenu = "";
	  if (lire_fichier ($f, $contenu)) {
	    if (!ecrire_fichier($but,$contenu,false,false))
	      { $ok = false; break;}
	  }
	  @unlink($f);
	  $files_o[]=$f;
	}
	return $ok ? $files_o : false;
}

define('_EXPORT_TRANCHES_LIMITE', 400);
define('_EXTENSION_PARTIES', '.gz');

//
// Exportation de table SQL au format xml
// La constante ci-dessus determine la taille des tranches,
// chaque tranche etant copiee immediatement dans un fichier 
// et son numero memorisee dans le serveur SQL.
// En cas d'abandon sur Time-out, le travail pourra ainsi avancer.
// Au final, on regroupe les tranches en un seul fichier
// et on memorise dans le serveur qu'on va passer a la table suivante.

// http://doc.spip.org/@export_objets
function export_objets($table, $etape, $cpt, $dir, $archive, $gz, $total, $les_rubriques) {
	global $tables_principales;

	$filetable = $dir . $archive . '.part_' . sprintf('%03d',$etape);
	$prim = isset($tables_principales[$table])
	  ? $tables_principales[$table]['key']["PRIMARY KEY"]
	  : '';
	$debut = $cpt * _EXPORT_TRANCHES_LIMITE;

	include_spip('inc/meta');
	while (1){ // on ne connait pas le nb de paquets d'avance

		$string = build_while($debut, $table, $prim, $les_rubriques);
		$cpt++;
		$debut +=  _EXPORT_TRANCHES_LIMITE;
		$status_dump = "$gz::$archive::$etape::$cpt";

		// attention $string vide ne suffit pas a sortir
		// car les admins restreints peuvent parcourir
		// une portion de table vide pour eux.
		if ($string) { 
			// on ecrit dans un fichier generique
			// puis on le renomme pour avoir une operation atomique 
			ecrire_fichier ($temp = $filetable . '.temp' . _EXTENSION_PARTIES, $string);
	// le fichier destination peut deja exister
	// si on sort d'un timeout entre le rename et le ecrire_meta
			if (file_exists($f = $filetable . sprintf('_%04d',$cpt) . _EXTENSION_PARTIES)) @unlink($f);
			rename($temp, $f);
		}
		// on se contente d'une ecriture en base pour aller plus vite
		// a la relecture on en profitera pour mettre le cache a jour
		ecrire_meta("status_dump", $status_dump,'non');
		if ($debut >= $total) {break;}
		echo " $debut";
	}
	echo " $total."; 
	
	# on prefere ne pas faire le ramassage ici de peur d'etre interrompu par le timeout au mauvais moment
	# le ramassage aura lieu en debut de hit suivant, et ne sera normalement pas interrompu car le temps pour ramasser
	# est plus court que le temps pour creer les parties
	// ramasse_parties($dir.$archive, $dir.$archive);
}

// Construit la version xml  des champs d'une table

// http://doc.spip.org/@build_while
function build_while($debut, $table, $prim, $les_rubriques) {
	global  $chercher_logo ;

	$result = spip_query("SELECT * FROM $table LIMIT $debut," . _EXPORT_TRANCHES_LIMITE);

	$string = '';
	while ($row = spip_abstract_fetch($result)) {
		if (export_select($row, $les_rubriques)) {
			$attributs = "";
			if ($chercher_logo) {
				if ($logo = $chercher_logo($row[$prim], $prim, 'on'))
					$attributs .= ' on="' . $logo[3] . '"';
				if ($logo = $chercher_logo($row[$prim], $prim, 'off'))
					$attributs .= ' off="' . $logo[3] . '"';
			}

			$string .= "<$table$attributs>\n";
			foreach ($row as $k => $v) {
				$string .= "<$k>" . text_to_xml($v) . "</$k>\n";
			}
			$string .= "</$table>\n\n";
		}
	}
	spip_abstract_free($result);
	return $string;
}

// dit si Row est exportable, 
// en particulier quand on se restreint a un tableau non vide de rubriques

// http://doc.spip.org/@export_select
function export_select($row, $les_rubriques) {

	if (isset($row['impt']) AND $row['impt'] !='oui') return false;
	if (!$les_rubriques) return true;
	// if (isset($row['id_auteur'])) return false; # pour les ignorer
	if (!isset($row['id_rubrique'])) {
		if (isset($row['id_article']))
			return autoriser('modifier','article',$row['id_article']);
		if (isset($row['id_breve']))
			return autoriser('modifier','breve',$row['id_breve']);
	}
	if (isset($row['id_article']) OR isset($row['id_breve']))
		return autoriser('publierdans','rubrique',$row['id_rubrique']);
	return isset($les_rubriques[$row['id_rubrique']]);
}

// Conversion texte -> xml (ajout d'entites)
// http://doc.spip.org/@text_to_xml
function text_to_xml($string) {
	return str_replace(array('&','<','>'), array('&amp;','&lt;','&gt;'), $string);
}

// production de l'entete du fichier d'archive

// http://doc.spip.org/@export_entete
function export_entete()
{
	return
"<" . "?xml version=\"1.0\" encoding=\"".
$GLOBALS['meta']['charset']."\"?".">\n" .
"<SPIP 
	version=\"" . $GLOBALS['spip_version_affichee'] . "\" 
	version_base=\"" . $GLOBALS['spip_version'] . "\" 
	version_archive=\"" . $GLOBALS['version_archive'] . "\"
	adresse_site=\"" .  $GLOBALS['meta']["adresse_site"] . "\"
	dir_img=\"" . _DIR_IMG . "\"
	dir_logos=\"" . _DIR_LOGOS . "\"
>\n";
}

// http://doc.spip.org/@export_enpied
function export_enpied () { return  "</SPIP>\n";}

?>
