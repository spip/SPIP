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

include_spip('inc/presentation');
include_spip ("inc/acces");
include_spip('inc/indexation'); // pour la fonction primary_index_table 
include_spip('base/serial');
include_spip('base/auxiliaires');

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

global $IMPORT_tables_noerase;
$IMPORT_tables_noerase[]='spip_ajax_fonc';
$IMPORT_tables_noerase[]='spip_meta';
$GLOBALS['flag_ob_flush'] = function_exists('ob_flush');

// http://doc.spip.org/@xml_fetch_tag
function xml_fetch_tag($f, &$before, $gz=false, $skip_comment=true) {
	global $buf, $abs_pos;
	static $buf_len = 500;
	static $_fread,$_feof,$_ftell;
	if (!$_fread){
		$_fread = ($gz) ? 'gzread' : 'fread';
		$_feof = ($gz) ? 'gzeof' : 'feof';
		$_ftell = ($gz) ? 'gztell' : 'ftell';
	}
	
	while (preg_match("{<([^>]*?)>}s",$buf)==FALSE)
		$buf .= $_fread($f, $buf_len);
	$chars = preg_split("{<([^>]*?)>}s",$buf,2,PREG_SPLIT_DELIM_CAPTURE);

	$before .= str_replace(array('&amp;','&lt;'),array('&','<'),$chars[0]);
	$tag = $chars[1];
	$buf = $chars[2];

	$abs_pos = $_ftell($f) - strlen($buf);

	if (($skip_comment==true)&&(substr($tag,0,3)=='!--')){
	  return xml_fetch_tag($f,$before,$gz,$skip_comment);
	}
	else
		return $tag;
}

// http://doc.spip.org/@xml_parse_tag
function xml_parse_tag($texte) {
	list($tag, $atts) = split('[[:space:]]+', $texte, 2);
	$result[0] = $tag;
	$result[1] = '';
	if (!$atts) return $result;
	if ($tag=='!--'){
	  $result[1]=preg_replace(",(.*?)--$,s",'\\1',$atts);
	}
	else {
		while (ereg('^([^[:space:]]+)[[:space:]]*=[[:space:]]*"([^"]*)"([[:space:]]+(.*))?', $atts, $regs)) {
			$result[1][$regs[1]] = $regs[2];
			$atts = $regs[4];
		}
	}
	return $result;
}


// http://doc.spip.org/@import_debut
function import_debut($f, $gz=false) {
	$b = "";
//  Pour les anciennes archives, indiquer le charset par defaut:
	$charset = 'iso-8859-1'; 
//  les + recentes l'ont en debut de ce fichier 
	$flag_phpmyadmin = false;
	while ($t = xml_fetch_tag($f, $b, $gz, false)) {
		$r = xml_parse_tag($t);
		if ($r[0] == '?xml' AND $r[1]['encoding'])
			$charset = strtolower($r[1]['encoding']);
		elseif ($r[0] == "SPIP") {$r[2] = $charset; return $r;}
		if (($r[0] == "!--") && (preg_match(",phpmyadmin\sxml\sdump,is",$r[1]))){
			// c'est un dump xml phpmyadmin
			// on interprete le commentaire pour recuperer la version de phpmydadmin
			$version = preg_replace(",(.*?)version\s*([0-9a-z\.\-]*)\s(.*),is","\\2",$r[1]);
			$flag_phpmyadmin = true;
		}
		if (($r[0] != "!--") && ($flag_phpmyadmin == true)){
			$r[1] = array('version_archive'=>"phpmyadmin::$version");
			$r[2] = $charset;
			return $r;
		}
		$b = "";
	}
	// improbable: fichier correct avant debut_admin et plus apres
	import_all_fin();
	die(_T('info_erreur_restauration'));
}

// on conserve ce tableau pour faire des translations
// de table eventuelles
$tables_trans = array(
);

// http://doc.spip.org/@import_init_tables
function import_init_tables($request)
{
  global $IMPORT_tables_noerase, $connect_id_auteur;
	// grand menage
	// on vide toutes les tables dont la restauration est demandee
	$tables = import_table_choix($request);
	foreach($tables as $table){

		if (($table!='spip_auteurs')&&(!in_array($table,$IMPORT_tables_noerase)))
			spip_query("DELETE FROM $table");
	}

	// Bidouille pour garder l'acces admin actuel pendant toute la restauration
	spip_query("UPDATE spip_auteurs SET id_auteur=0, extra=$connect_id_auteur WHERE id_auteur=$connect_id_auteur");
	spip_query("DELETE FROM spip_auteurs WHERE id_auteur!=0");

	return $tables;
}

// Effacement de la bidouille ci-dessus
// Toutefois si la table des auteurs ne contient plus qu'elle
// c'est que la sauvegarde etait incomplete et on restaure le compte
// pour garder la connection au site (mais il doit pas etre bien beau)

// http://doc.spip.org/@detruit_restaurateur
function detruit_restaurateur()
{
	$r = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM spip_auteurs"));
	if ($r['n'] > 1)
		spip_query("DELETE FROM spip_auteurs WHERE id_auteur=0");
	else {
	  	spip_query("UPDATE spip_auteurs SET id_auteur=extra WHERE id_auteur=0");
	}
}

// http://doc.spip.org/@import_tables
function import_tables($request, $dir, $trans=array()) {
	global $import_ok, $abs_pos,  $affiche_progression_pourcent;
	static $time_javascript;

	$my_pos = (!isset($GLOBALS['meta']["status_restauration"])) ? 0 :
		$GLOBALS['meta']["status_restauration"];

	// au premier appel destruction des tables a restaurer
	// ou initialisation de la table des translations,
	// mais pas lors d'une reprise.

	if ($request['insertion']=='on') {
		include_spip('inc/import_insere');
		$request['init'] = (!$my_pos) ? 'insere_1_init' : 'insere_2_init';		$request['boucle'] = 'import_insere';
	} elseif ($request['insertion']=='passe2') {
		$request['init'] = 'insere_2_init';
		$request['boucle'] = 'import_translate';
	} else {
		$request['init'] = (!$my_pos) ? 'import_init_tables' : 'import_table_choix';
		$request['boucle'] = 'import_replace';
	}

	$archive = $dir . ($request['archive'] ? $request['archive'] : $request['archive_perso']);

	if (ereg("\.gz$", $archive)) {
			$size = false;
			$taille = taille_en_octets($my_pos);
			$file = gzopen($archive, 'rb');
			$gz = true;
	} else {
			$size = @filesize($archive);
			$taille = floor(100 * $my_pos / $size)." %";
			$file = fopen($archive, 'rb');
			$gz = false;
	}

	if ($my_pos==0) {
		list($tag, $r, $charset) = import_debut($file, $gz);
// tag ouvrant du Dump:
// 'SPIP' si fait par spip, nom de la base source si fait par  phpmyadmin
		$version_archive = $r['version_archive'];
		ecrire_meta('version_archive_restauration', $version_archive);
		ecrire_meta('tag_archive_restauration', $tag);
		if ( $i = $request['insertion'])
			ecrire_meta('charset_restauration', $charset);
		else	ecrire_meta('charset_insertion', $charset);
		ecrire_metas();
		spip_log("Debut de l'importation (charset: $charset, archive: $version_archive)" . ($i ? " insertion $i" : ''));
	} else {
		spip_log("Reprise de l'importation interrompue en $my_pos");
		$_fseek = ($gz) ? gzseek : fseek;
		$_fseek($file, $my_pos);
		$version_archive = $GLOBALS['meta']['version_archive_restauration'];
	}

	$fimport = import_charge_version($version_archive);

	import_affiche_javascript($taille);

	if ($GLOBALS['flag_ob_flush']) ob_flush();
	flush();

	$oldtable ='';
	while ($table = $fimport($file, $request, $gz, $trans)) {
	// Pas d'ecriture SQL car sinon le temps double.
	// Il faut juste faire attention a bien lire_metas()
	// au debut de la restauration
		ecrire_meta("status_restauration", "$abs_pos");
		if ($oldtable != $table) {
			spip_log("Restauration de $table");
			affiche_progression_javascript($abs_pos,$size,$table);

			$time_javascript = time();
			$oldtable = $table;
		}
	}
	if (!$import_ok) 
		$res =  _T('avis_archive_invalide');
	else {
		$res = '';
		affiche_progression_javascript('100 %', $size);
	}

	return $res ;
}

// http://doc.spip.org/@import_affiche_javascript
function import_affiche_javascript($taille)
{
	$max_time = ini_get('max_execution_time')*1000;
	echo debut_boite_alerte(),
	  "<font face='Verdana,Arial,Sans,sans-serif' size='4' color='black'><b>",
	  _T('info_base_restauration'),
	  "</b></font>",
	  "<form name='progression'><center><input type='text' size='10' style='text-align:center;' name='taille' value='",
	  $taille,
	  "'><br /><input type='text' class='forml' name='recharge' value='"._T('info_recharger_page')."'></center></form>",
	  fin_boite_alerte(),
	  "<script language=\"JavaScript\" type=\"text/javascript\">window.setTimeout('location.href=\"",
	  self(),
	  "\";',",
	  $max_time,
	  ");</script>\n";
}



// http://doc.spip.org/@affiche_progression_javascript
function affiche_progression_javascript($abs_pos,$size, $table="") {
	include_spip('inc/charsets');
	if ($GLOBALS['flag_ob_flush']) ob_flush();
	flush();
	echo "\n<script type='text/javascript'><!--\n";

	if ($abs_pos == '100 %') {

		if ($x = $GLOBALS['erreur_restauration'])
			echo "document.progression.recharge.value='".str_replace("'", "\\'", unicode_to_javascript(_T('avis_erreur'))).": $x ';\n";
		else
			echo "document.progression.recharge.value='".str_replace("'", "\\'", unicode_to_javascript(_T('info_fini')))."';\n";
		echo "document.progression.taille.value='$abs_pos';\n";
		echo "//--></script>\n";
		echo ("<script language=\"JavaScript\" type=\"text/javascript\">window.setTimeout('location.href=\"".self()."\";',0);</script>\n");
	}
	else {
		if (trim($table))
			echo "document.progression.recharge.value='$table';\n";
		if (!$size)
			$taille = ereg_replace("&nbsp;", " ", taille_en_octets($abs_pos));
		else
			$taille = floor(100 * $abs_pos / $size)." %";
		echo "document.progression.taille.value='$taille';\n";
		echo "//--></script>\n<!--\n";
	}

	if ($GLOBALS['flag_ob_flush']) ob_flush();
	flush();
}


// http://doc.spip.org/@import_table_choix
function import_table_choix($request)
{
	// construction de la liste des tables pour le dump :
	// toutes les tables principales
	// + toutes les tables auxiliaires hors relations
	// + les tables relations dont les deux tables liees sont dans la liste
	$tables_for_dump = array();
	$tables_pointees = array();
	global $IMPORT_tables_noimport;
	global $tables_principales;
	global $tables_auxiliaires;
	global $table_des_tables;
	global $tables_jointures;

	// on construit un index des tables de liens
	// pour les ajouter SI les deux tables qu'ils connectent sont sauvegardees
	$tables_for_link = array();
	foreach($tables_jointures as $table=>$liste_relations)
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
		$name = preg_replace("{^spip_}","",$table);
		if (		!isset($tables_pointees[$table]) 
				&&	!in_array($table,$IMPORT_tables_noimport)
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
			# si une restauration est interrompue, cela se verra mieux si il manque des objets
			# que des liens
			array_unshift($tables_for_dump,$link_table);
	}
	return $tables_for_dump;
}	
?>
