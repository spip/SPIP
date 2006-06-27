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
include_spip('inc/serialbase');
include_spip('inc/auxbase');

// NB: Ce fichier peut ajouter des tables (old-style)
// donc il faut l'inclure "en globals"
if ($f = include_spip('mes_fonctions', false)) {
	global $dossier_squelettes;
	@include_once ($f); 
}
if (@is_readable(_DIR_SESSIONS."charger_plugins_fonctions.php")){
	// chargement optimise precompile
	include_once(_DIR_SESSIONS."charger_plugins_fonctions.php");
}

global $IMPORT_tables_noerase;
$IMPORT_tables_noerase[]='spip_ajax_fonc';
$IMPORT_tables_noerase[]='spip_meta';
$GLOBALS['flag_ob_flush'] = function_exists('ob_flush');

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
	$chars = preg_split("{<([^>]*?)>}s",$buf,2,PREG_SPLIT_OFFSET_CAPTURE|PREG_SPLIT_DELIM_CAPTURE);

	$before .= str_replace(array('&amp;','&lt;'),array('&','<'),$chars[0][0]);
	$tag = $chars[1][0];
	$buf = $chars[2][0];

	$abs_pos = $_ftell($f) - strlen($buf);

	if (($skip_comment==true)&&(substr($tag,0,3)=='!--')){
	  return xml_fetch_tag($f,$before,$gz,$skip_comment);
	}
	else
		return $tag;
}

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


function import_debut($f, $gz=false) {
	$b = "";
	$flag_phpmyadmin = false;
	while ($t = xml_fetch_tag($f, $b, $gz, false)) {
		$r = xml_parse_tag($t);
		if ($r[0] == '?xml' AND $r[1]['encoding'])
			ecrire_meta('charset_restauration', strtolower($r[1]['encoding']));
		if ($r[0] == "SPIP") return $r;
		if (($r[0] == "!--") && (preg_match(",phpmyadmin\sxml\sdump,is",$r[1]))){
			// c'est un dump xml phpmyadmin
			// on interprete le commentaire pour recuperer la version de phpmydadmin
			$version = preg_replace(",(.*?)version\s*([0-9a-z\.\-]*)\s(.*),is","\\2",$r[1]);
			$flag_phpmyadmin = true;
		}
		if (($r[0] != "!--") && ($flag_phpmyadmin == true)){
		  $r[1] = array('version_archive'=>"phpmyadmin::$version");
			return $r;
		}
		$b = "";
	}
	return false;
}

//
// $f = handle fichier
// $gz = flag utilisation zlib
//
// importe un objet depuis le fichier, retourne true si ok, false si erreur ou fin de fichier
//

// on conserve ce tableau pour faire des translations
// de table eventuelles
$tables_trans = array(
);

function import_fin() {
	// Effacer l'ancien acces admin
	spip_query("DELETE FROM spip_auteurs WHERE id_auteur=0");

	if ($charset = $GLOBALS['meta']['charset_restauration'])
		ecrire_meta('charset', $charset);
	effacer_meta("charset_restauration");
	effacer_meta("status_restauration");
	effacer_meta("debut_restauration");
	effacer_meta("date_optimisation");
	effacer_meta('request_restauration');
	effacer_meta('fichier_restauration');
	effacer_meta('version_archive_restauration');
	effacer_meta('tag_archive_restauration');
	ecrire_meta('calculer_rubriques', 'oui');
	ecrire_metas();
}

function import_abandon() {
	// Probleme pour restaurer l'ancien acces admin : il conserve un id_auteur = 0

	effacer_meta("charset_restauration");
	effacer_meta("status_restauration");
	effacer_meta("debut_restauration");
	effacer_meta("date_optimisation");
	effacer_meta('request_restauration');
	effacer_meta('fichier_restauration');
	effacer_meta('version_archive_restauration');
	effacer_meta('tag_archive_restauration');
	ecrire_metas();
}

function import_init_tables()
{
  global $IMPORT_tables_noerase, $connect_id_auteur;
	// grand menage
	// on vide toutes les tables dont la restauration est demandee
	$tables = import_table_choix();
	foreach($tables as $table){

		if (($table!='spip_auteurs')&&(!in_array($table,$IMPORT_tables_noerase)))
			spip_query("DELETE FROM $table");
	}

	// Bidouille pour garder l'acces admin actuel pendant toute la restauration
	spip_query("UPDATE spip_auteurs SET id_auteur=0 WHERE id_auteur=$connect_id_auteur");
	spip_query("DELETE FROM spip_auteurs WHERE id_auteur!=0");

	return $tables;
}

function import_tables($f, $gz=false) {
	global $import_ok, $abs_pos, $my_pos;
	static $time_javascript;

	list($my_date) = spip_fetch_array(spip_query("SELECT UNIX_TIMESTAMP(maj) AS d FROM spip_meta WHERE nom='debut_restauration'"));

	if (!$my_date) return false;

	$my_pos = (!isset($GLOBALS['meta']["status_restauration"])) ? 0 :
		$GLOBALS['meta']["status_restauration"];
	if ($my_pos==0) {
		// par defaut pour les anciens sites
		// il est contenu dans le xml d'import et sera reecrit dans import_debut
		ecrire_meta('charset_restauration', 'iso-8859-1'); 
		// Debut de l'importation
		$fimport = false;
		if ($r = import_debut($f, $gz)) {
// tag ouvrant :
// 'SPIP' pour un dump xml spip, nom de la base source pour un dump phpmyadmin
			$tag_archive = $r[0];
			$version_archive = $r[1]['version_archive'];
			$fimport = import_charge_version($version_archive);
		}
		// Normalement c'est controle par import_all auparavant
		if (!$fimport) {
			return _T('avis_archive_incorrect');
		}

		ecrire_meta('version_archive_restauration', $version_archive);
		ecrire_meta('tag_archive_restauration', $tag_archive);
		ecrire_metas();
	} else {
		// Reprise de l'importation
		$_fseek = ($gz) ? gzseek : fseek;
		$_fseek($f, $my_pos);
		$version_archive = $GLOBALS['meta']['version_archive_restauration'];
		$tag_archive = $GLOBALS['meta']['tag_archive_restauration'];
		$fimport = import_charge_version($version_archive);
		$tables = import_table_choix();
	}

	while ($table = $fimport($f, $gz)) {
	// Pas d'ecriture SQL car sinon le temps double.
	// Il faut juste faire attention a bien lire_metas()
	// au debut de la restauration
		ecrire_meta("status_restauration", "$abs_pos");

		if (time() - $time_javascript > 3) {	// 3 secondes
			affiche_progression_javascript($abs_pos,$table);
			$time_javascript = time();
		}

		$my_pos = true;
	}

	if (!$import_ok) return  _T('avis_archive_invalide');

	// Mise a jour du fichier htpasswd

	ecrire_acces();

	detruit_restaurateur();

	import_fin();

	affiche_progression_javascript('100 %');

	return false;
}

// Destruction des entrees non restaurees

function detruit_restaurateur()
{
	spip_query("DELETE FROM spip_auteurs WHERE id_auteur=0");
}


function affiche_progression_javascript($abs_pos,$table="") {
	global $affiche_progression_pourcent;
	include_spip('inc/charsets');
	if ($GLOBALS['flag_ob_flush']) ob_flush();
	flush();
	echo " -->\n<script type='text/javascript'><!--\n";

	if ($abs_pos == '100 %') {
		$taille = $abs_pos;
		if ($GLOBALS['erreur_restauration'])
			echo "document.progression.recharge.value='".str_replace("'", "\\'", unicode_to_javascript(_T('avis_erreur')))."';\n";
		else
			echo "document.progression.recharge.value='".str_replace("'", "\\'", unicode_to_javascript(_T('info_fini')))."';\n";
		echo "document.progression.taille.value='$taille';\n";
		echo "//--></script>\n";
		echo ("<script language=\"JavaScript\" type=\"text/javascript\">window.setTimeout('location.href=\"".self()."\";',0);</script>\n");
	}
	else {
		if (trim($table))
			echo "document.progression.recharge.value='$table';\n";
		if (! $affiche_progression_pourcent)
			$taille = ereg_replace("&nbsp;", " ", taille_en_octets($abs_pos));
		else
			$taille = floor(100 * $abs_pos / $affiche_progression_pourcent)." %";
		echo "document.progression.taille.value='$taille';\n";
		echo "//--></script>\n<!--\n";
	}

	if ($GLOBALS['flag_ob_flush']) ob_flush();
	flush();
}


function import_table_choix()
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


function import_all_continue()
{
  global $meta, $flag_gz, $buf, $abs_pos, $my_pos;
	global $affiche_progression_pourcent;
	ini_set("zlib.output_compression","0"); // pour permettre l'affichage au fur et a mesure
	// utiliser une version fraiche des metas (ie pas le cache)
	include_spip('inc/meta');
	lire_metas();

	@ignore_user_abort(1);

	$request = unserialize($meta['request_restauration']);
	$archive = _DIR_SESSIONS . $request['archive'];

	debut_page(_T('titre_page_index'), "asuivre", "asuivre");

	debut_gauche();

	debut_droite();

	// attention : si $request['archive']=="", alors archive='data/' 
	// le test is_readable n'est donc pas suffisant
	if (!@is_readable($archive)||is_dir($archive)) {
		$texte_boite = _T('info_erreur_restauration');
		debut_boite_alerte();
		echo "<font FACE='Verdana,Arial,Sans,sans-serif' SIZE=4 color='black'><B>$texte_boite</B></font>";
		fin_boite_alerte();
		fin_html();
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
			$affiche_progression_pourcent = filesize($archive);
			#echo $affiche_progression_pourcent;
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

	fin_page();
	if ($GLOBALS['flag_ob_flush']) ob_flush();
	flush();

	echo "<font color='white'>\n<!--";

	$_fopen = ($gz) ? 'gzopen' : 'fopen';
	$f = $_fopen($archive, "rb");
	$buf = "";
	$r = import_tables($f, $gz);
	if ($r) {
		spip_log("Erreur: $r");
		import_abandon();
	}
	else	import_fin();
}

?>
