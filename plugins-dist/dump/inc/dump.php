<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;


/**
 * Repertoire de sauvegarde
 *
 * @return string
 */
function dump_repertoire() {
	$repertoire = _DIR_DUMP;
	if (!@file_exists($repertoire)
		AND !$repertoire = sous_repertoire(_DIR_DUMP,'',false,true)
	) {
		$repertoire = preg_replace(','._DIR_TMP.',', '', _DIR_DUMP);
		$repertoire = sous_repertoire(_DIR_TMP, $repertoire);
	}
	return $repertoire;
}


/**
 * Nom du fichier de sauvegarde
 * la fourniture de l'extension permet de verifier que le nom n'existe pas deja
 *
 * @param string $dir
 * @param string $extension
 * @return string
 */
function dump_nom_fichier($dir,$extension='sqlite'){
	include_spip('inc/texte');
	$site = isset($GLOBALS['meta']['nom_site'])
	  ? preg_replace(array(",\W,is",",_(?=_),",",_$,"),array("_","",""), couper(translitteration(trim($GLOBALS['meta']['nom_site'])),30,""))
	  : 'spip';

	$site .= '_' . date('Ymd');

	$nom = $site;
	$cpt=0;
	while (file_exists($dir. $nom . ".$extension")) {
		$nom = $site . sprintf('_%03d', ++$cpt);
	}
	return $nom.".$extension";
}

/**
 * Determine le type de serveur de sauvegarde
 * sqlite2 ou sqlite3
 * 
 * @return string
 */
function dump_type_serveur() {

	// chercher si sqlite2 ou 3 est disponible
	include_spip('req/sqlite3');
	if (spip_versions_sqlite3())
		return 'sqlite3';

	include_spip('req/sqlite2');
	if (spip_versions_sqlite2())
		return 'sqlite2';

	return '';
}

/**
 * Conteneur pour les arguments de la connexion
 * si on passe $args, les arguments de la connexion sont memorises
 * renvoie toujours les derniers arguments memorises
 *
 * @staticvar array $connect_args
 * @param array $connect
 * @return array
 */
function dump_serveur($args=null) {
	static $connect_args = null;
	if ($args)
		$connect_args = $args;

	return $connect_args;
}

function dump_connect_args($archive) {
	if (!$type_serveur = dump_type_serveur())
		return null;
	return array(dirname($archive), '', '', '', basename($archive,".sqlite"), $type_serveur, 'spip');
}

/**
 * Initialiser un dump
 * @param string $status_file
 * @param string $archive
 * @param array $tables
 * @param array $where
 * @return bool/string
 */
function dump_init($status_file, $archive, $tables=null, $where=array(),$action='sauvegarde'){
	$status_file = _DIR_TMP.basename($status_file).".txt";

	if (lire_fichier($status_file, $status)
		AND $status = unserialize($status)
		AND $status['etape']!=='fini'
		AND filemtime($status_file)>=time()-120) // si le fichier status est trop vieux c'est un abandon
		return _T("dump:erreur_".$action."_deja_en_cours");

	if (!$type_serveur = dump_type_serveur())
		return _T('dump:erreur_sqlite_indisponible');

	if (!$tables)
		list($tables,) = base_liste_table_for_dump(lister_tables_noexport());
	$status = array('tables'=>$tables,'where'=>$where,'archive'=>$archive);

	$status['connect'] = dump_connect_args($archive);
	dump_serveur($status['connect']);
	if (!spip_connect('dump'))
		return _T('dump:erreur_creation_base_sqlite');

	// la constante sert a verifier qu'on utilise bien le connect/dump du plugin,
	// et pas une base externe homonyme
	if (!defined('_DUMP_SERVEUR_OK'))
		return _T('erreur_connect_dump', array('dump' => 'dump'));

	$status['etape'] = 'init';

	if (!ecrire_fichier($status_file, serialize($status)))
		return _T('dump:avis_probleme_ecriture_fichier',array('fichier'=>$status_file));

	return true;
}

/**
 * Afficher l'avancement de la copie
 * @staticvar int $etape
 * @param <type> $courant
 * @param <type> $total
 * @param <type> $table
 */
function dump_afficher_progres($courant,$total,$table) {
	static $etape = 1;
	if (unique($table)) {
		if ($total<0 OR !is_numeric($total))
			echo "<br /><strong>".$etape. '. '."</strong>$table ";
		else
			echo "<br /><strong>".$etape. '. '."$table</strong> ".($courant?" <i>($courant)</i> ":"");
		$etape++;
	}
	if (is_numeric($total) AND $total>=0)
		echo ". ";
	else
		echo "(". (-intval($total)).")";
	flush();
}

/**
 * Ecrire le js pour relancer la procedure de dump
 * @param string $redirect
 * @return string
 */
function dump_relance($redirect){
	// si Javascript est dispo, anticiper le Time-out
	return "<script language=\"JavaScript\" type=\"text/javascript\">window.setTimeout('location.href=\"$redirect\";',300);</script>\n";
}


/**
 * Marquer la procedure de dump comme finie
 * @param string $status_file
 * @return <type>
 */
function dump_end($status_file, $action=''){
	$status_file = _DIR_TMP.basename($status_file).".txt";
	if (!lire_fichier($status_file, $status)
		OR !$status = unserialize($status))
		return;
	
	switch($action) {
		case 'restaurer':
			// supprimer la structure qui etait stockee dans le dump
			Sql::delete('spip_meta',"nom='dump_structure_temp'");
			break;
		case 'sauvegarder':
			// stocker dans le dump la structure de la base source
			$structure = array();
			foreach($status['tables_copiees'] as $t=>$n)
				$structure[$t] = Sql::showtable($t,true);
			dump_serveur($status['connect']);
			spip_connect('dump');
			Sql::delete('spip_meta',"nom='dump_structure_temp'",'dump'); #enlever une vieille structure deja la, au cas ou
			Sql::insertq('spip_meta',array('nom'=>'dump_structure_temp','valeur'=>serialize($structure),'impt'=>'non'),array(),'dump');
			break;
	}
	
	$status['etape'] = 'fini';
	ecrire_fichier($status_file, serialize($status));
}

/**
 * Lister les fichiers de sauvegarde existant dans un repertoire
 * trie par nom, date ou taille
 * 
 * @param string $dir
 * @param string $tri
 * @param string $extension
 * @param int $limit
 * @return array
 */
function dump_lister_sauvegardes($dir,$tri='nom',$extension="sqlite",$limit = 100) {
	$liste_dump = preg_files($dir,'\.'.$extension.'$',$limit,false);

	$n = strlen($dir);
	$tn = $tl = $tt = $td = array();
	foreach($liste_dump as $fichier){
		$d = filemtime($fichier);
		$t = filesize($fichier);
		$fichier = substr($fichier, $n);
		$tl[]= array('fichier'=>$fichier,'taille'=>$t,'date'=>$d);
		$td[] = $d;
		$tt[] = $t;
		$tn[] = $fichier;
	}
	if ($tri == 'taille')
		array_multisort($tt, SORT_ASC, $tl);
	elseif ($tri == 'date')
		array_multisort($td, SORT_ASC, $tl);
	else
		array_multisort($tn, SORT_ASC, $tl);
	return $tl;
}


function dump_lire_status($status_file) {
	$status_file = _DIR_TMP.basename($status_file).".txt";
	if (!lire_fichier($status_file, $status)
		OR !$status = unserialize($status))
		return '';

	return $status;
}

function dump_verifie_sauvegarde_finie($status_file) {
	if (!$status=dump_lire_status($status_file)
	 OR $status['etape']!=='fini')
	 return '';
	return ' ';
}

function dump_nom_sauvegarde($status_file) {
	if (!$status=dump_lire_status($status_file)
	  OR !file_exists($f=$status['archive'].".sqlite"))
		return '';

	return $f;
}

function dump_taille_sauvegarde($status_file) {
	if (!$f=dump_nom_sauvegarde($status_file)
		OR !$s = filesize($f))
		return '';

	return $s;
}

function dump_date_sauvegarde($status_file) {
	if (!$f=dump_nom_sauvegarde($status_file)
		OR !$d = filemtime($f))
		return '';

	return date('Y-m-d',$d);
}

?>