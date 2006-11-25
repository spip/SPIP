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

// http://doc.spip.org/@import_init_tables
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
	global $import_ok, $abs_pos, $my_pos;
	global $affiche_progression_pourcent;
	static $time_javascript;

	$my_pos = $GLOBALS['meta']["status_restauration"];
	$archive = $dir . ($request['archive'] ? $request['archive'] : $request['archive_perso']);
	$size = @filesize($archive);

	// attention : si $request['archive']=="", alors archive='data/' 
	// le test is_readable n'est donc pas suffisant
	if (!@is_readable($archive)||is_dir($archive) || !$size) {
		$texte_boite = _T('info_erreur_restauration');
		echo debut_boite_alerte();
		echo "<font face='Verdana,Arial,Sans,sans-serif' size='4' color='black'><b>$texte_boite</b></font>";
		echo fin_boite_alerte();
// renvoyer True pour effacer les meta sans refaire calculer_rubriques:
// la restauration n'a pas encore commence, on peut eviter de tout casser
		return $texte_boite;
	}

	if (ereg("\.gz$", $archive)) {
			$size = false;
			$taille = taille_en_octets($my_pos);
			$_fopen = 'gzopen';
			$gz = true;
	} else {
			$taille = floor(100 * $my_pos / $size)." %";
			$_fopen = 'fopen';
			$gz = false;
	}

	import_affiche_javascript($taille);

	if ($GLOBALS['flag_ob_flush']) ob_flush();
	flush();

	list($my_date) = spip_fetch_array(spip_query("SELECT UNIX_TIMESTAMP(maj) AS d FROM spip_meta WHERE nom='debut_restauration'"), SPIP_NUM);

	if (!$my_date) return false;

	if ($request['insertion']=='on') {
		$request['init'] = 'insere_1_init';
		$request['boucle'] = 'import_insere';
	} elseif ($request['insertion']=='passe2') {
		$request['init'] = 'insere_2_init';
		$request['boucle'] = 'import_translate';
	} else {
		$request['init'] = 'import_init';
		$request['boucle'] = 'import_replace';
	}

	$f = $_fopen($archive, "rb");
	$my_pos = (!isset($GLOBALS['meta']["status_restauration"])) ? 0 :
		$GLOBALS['meta']["status_restauration"];

	if ($my_pos==0) {
// par defaut pour les anciens sites
// il est contenu dans le xml d'import et sera reecrit dans import_debut
		ecrire_meta('charset_restauration', 'iso-8859-1'); 
		if ($r = import_debut($f, $gz)) {
// tag ouvrant :
// 'SPIP' pour un dump xml spip, nom de la base source pour un dump phpmyadmin
			$tag_archive = $r[0];
			$version_archive = $r[1]['version_archive'];
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
		// a completer pour les insertions
		$request['init'] = 'import_table_choix';
	}

	$fimport = import_charge_version($version_archive);

	// Normalement c'est controle par import_all auparavant
	// c'est pour se proteger des reprises avortees
	if (!$fimport) return _T('avis_archive_incorrect');

	while ($table = $fimport($f, $request, $gz, $trans)) {
	// Pas d'ecriture SQL car sinon le temps double.
	// Il faut juste faire attention a bien lire_metas()
	// au debut de la restauration
		ecrire_meta("status_restauration", "$abs_pos");

		if (time() - $time_javascript > 3) {	// 3 secondes
			affiche_progression_javascript($abs_pos,$size,$table);
			$time_javascript = time();
		}

		$my_pos = true;
	}

	if (!$import_ok) return  _T('avis_archive_invalide');

	affiche_progression_javascript('100 %', $size);

	return false;
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


?>
