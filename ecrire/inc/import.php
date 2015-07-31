<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/presentation');
include_spip('inc/acces');
include_spip('base/abstract_sql');

// NB: Ce fichier peut ajouter des tables (old-style)
// donc il faut l'inclure "en globals"
if ($f = find_in_path('mes_fonctions.php')) {
	global $dossier_squelettes;
	@include_once ($f); 
}

if (@is_readable(_DIR_TMP."charger_plugins_fonctions.php")){
	// chargement optimise precompile
	include_once(_DIR_TMP."charger_plugins_fonctions.php");
}

global $IMPORT_tables_noerase;
$IMPORT_tables_noerase[]='spip_meta';

// Retourne la premiere balise XML figurant dans le buffet de la sauvegarde 
// et avance dans ce buffet jusqu'au '>' de cette balise.
// Si le 2e argument (passe par reference) est non vide
// ce qui precede cette balise y est mis.
// Les balises commencant par <! sont ignorees
// $abs_pos est globale pour pouvoir etre reinitialisee a la meta
// restauration_status en cas d'interruption sur TimeOut.
// Evite au maximum les recopies

// http://doc.spip.org/@xml_fetch_tag
function xml_fetch_tag($f, &$before, $_fread='fread', $skip='!') {
	global $abs_pos;
	static $buf='';
	static $ent = array('&gt;','&lt;','&amp;');
	static $brut = array('>','<','&');

	while (($b=strpos($buf,'<'))===false) {
		if (!($x = $_fread($f, 1024))) return '';
		if ($before)
			$buf .= $x;
		else {
			if (_DEBUG_IMPORT)
				$GLOBALS['debug_import_avant'] .= $buf;
			$abs_pos += strlen($buf);
			$buf = $x;
		}
	}
	if ($before) $before = str_replace($ent,$brut,substr($buf,0,$b));
#	else { spip_log("position: $abs_pos" . substr($buf,0,12));flush();}

	// $b pour ignorer un > de raccourci Spip avant un < de balise XML

	while (($e=strpos($buf,'>', $b))===false) {
		if (!($x = $_fread($f, 1024))) return '';
		$buf .= $x;
	}

	if ($buf[++$b]!=$skip) {
		if (_DEBUG_IMPORT){
			$GLOBALS['debug_import_avant'] .= substr($buf,0,$e+1);
			$GLOBALS['debug_import_avant'] = substr($GLOBALS['debug_import_avant'],-1024);
		}
		$tag = substr($buf, $b, $e-$b);
		$buf = substr($buf,++$e);
		if (_DEBUG_IMPORT)
			$GLOBALS['debug_import_apres'] = $buf;
		$abs_pos += $e;
		return $tag;
	}
	if (_DEBUG_IMPORT)
		$GLOBALS['debug_import_avant'] .= substr($buf,0,$e+1);
	$buf = substr($buf,++$e);
	if (_DEBUG_IMPORT)
		$GLOBALS['debug_import_apres'] = $buf;
	$abs_pos += $e;
	return xml_fetch_tag($f,$before,$_fread,$skip);
}

// http://doc.spip.org/@xml_parse_tag
function xml_parse_tag($t) {

	preg_match(',^([\w[?!%.;:-]*),s', $t, $res);
	$t = substr($t,strlen($res[0]));
	$res[1] = array();

	// pourquoi on ne peut pas mettre \3 entre crochets ?
	if (preg_match_all(',\s*(--.*?--)?\s*([^=]*)\s*=\s*([\'"])([^"]*)\3,sS', $t, $m, PREG_SET_ORDER)) {
		foreach($m as $r) $res[1][$r[2]] = $r[4];
	}
	return $res;
}

// Balise ouvrante:
// 'SPIP' si fait par spip, nom de la base source si fait par  phpmyadmin

// http://doc.spip.org/@import_debut
function import_debut($f, $gz='fread') {

//  Pour les anciennes archives, indiquer le charset par defaut:
	$charset = 'iso-8859-1'; 
//  les + recentes l'ont en debut de ce fichier 
	$flag_phpmyadmin = false;
	$b = false;
	while ($t = xml_fetch_tag($f, $b, $gz, '')) {
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
	}
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
		// regarder si il y a au moins un champ impt='non'
		if (($table!='spip_auteurs')&&(!in_array($table,$IMPORT_tables_noerase))){
			$desc = description_table($table);
			if (isset($desc['field']['impt']))
				sql_delete($table, "impt='oui'");
			else
				sql_delete($table);
		}
	}

	// Bidouille pour garder l'acces admin actuel pendant toute la restauration
	sql_delete("spip_auteurs", "id_auteur=0");
	sql_updateq('spip_auteurs', array('id_auteur'=>0, 'extra'=>$connect_id_auteur), "id_auteur=$connect_id_auteur");
	sql_delete("spip_auteurs", "id_auteur!=0");

	return $tables;
}

// Effacement de la bidouille ci-dessus
// Toutefois si la table des auteurs ne contient plus qu'elle
// c'est que la sauvegarde etait incomplete et on restaure le compte
// pour garder la connection au site (mais il doit pas etre bien beau)

// http://doc.spip.org/@detruit_restaurateur
function detruit_restaurateur()
{
	if (sql_countsel("spip_auteurs", "id_auteur<>0"))
		sql_delete("spip_auteurs", "id_auteur=0");
	else {
		sql_update('spip_auteurs', array('id_auteur'=>'extra'), "id_auteur=0");
	}
}

// http://doc.spip.org/@import_tables
function import_tables($request, $dir) {
	global $import_ok, $abs_pos,  $affiche_progression_pourcent;

	// regarder si on est pas en train d'importer dans une copie des tables
	if (isset($GLOBALS['meta']['restauration_table_prefix'])) {
		$charger = charger_fonction('charger','maj/vieille_base');
		$charger($GLOBALS['meta']['vieille_version_installee']);
		$GLOBALS['serveur_vieille_base'] = 0;
		$GLOBALS['connexions'][$GLOBALS['serveur_vieille_base']]['prefixe'] = $GLOBALS['meta']['restauration_table_prefix'];
		// recharger les metas
		lire_metas();
	}


	$abs_pos = (!isset($GLOBALS['meta']["restauration_status"])) ? 0 :
		$GLOBALS['meta']["restauration_status"];

	// au premier appel destruction des tables a restaurer
	// ou initialisation de la table des translations,
	// mais pas lors d'une reprise.

	if ($request['insertion']=='on') {
		include_spip('inc/import_insere');
		$request['init'] = (!$abs_pos) ? 'insere_1_init' : 'insere_1bis_init';
		$request['boucle'] = 'import_insere';
	} elseif ($request['insertion']=='passe2') {
		$request['init'] = 'insere_2_init';
		$request['boucle'] = 'import_translate';
	} else {
		$request['init'] = (!$abs_pos) ? 'import_init_tables' : 'import_table_choix';
		$request['boucle'] = 'import_replace';
	}

	$archive = $dir . ($request['archive'] ? $request['archive'] : $request['archive_perso']);

	if (strncmp(".gz", substr($archive,-3),3)==0) {
			$size = false;
			$taille = taille_en_octets($abs_pos);
			$file = gzopen($archive, 'rb');
			$gz = 'gzread';
	} else {
			$size = @filesize($archive);
			$taille = @floor(100 * $abs_pos / $size)." %";
			$file = fopen($archive, 'rb');
			$gz = 'fread';
	}

	
	if ($abs_pos==0) {
		list($tag, $atts, $charset) = import_debut($file, $gz);
	// improbable: fichier correct avant debut_admin et plus apres
		if (!$tag) return !($import_ok = true);
		$version_archive = import_init_meta($tag, $atts, $charset, $request);
	} else {
		$version_archive = $GLOBALS['meta']['restauration_version_archive'];
		$atts = unserialize($GLOBALS['meta']['restauration_attributs_archive']);
		spip_log("Reprise de l'importation interrompue en $abs_pos");
		$_fseek = ($gz=='gzread') ? 'gzseek' : 'fseek';
		$_fseek($file, $abs_pos);
	}
	
	// placer la connexion sql dans le bon charset

	if (isset($GLOBALS['meta']['restauration_charset_sql_connexion']))
		sql_set_charset($GLOBALS['meta']['restauration_charset_sql_connexion']);

	@define('_DEBUG_IMPORT',false);
	if (_DEBUG_IMPORT)
		ecrire_fichier(_DIR_TMP."debug_import.log","#####".date('Y-m-d H:i:s')."\n",false,false);
	$fimport = import_charge_version($version_archive);

	import_affiche_javascript($taille);

	if (function_exists('ob_flush')) @ob_flush();
	flush();
	$oldtable ='';
	$cpt = 0;
	$pos = $abs_pos;
	while ($table = $fimport($file, $request, $gz, $atts)) {
	  // memoriser pour pouvoir reprendre en cas d'interrupt,
	  // mais pas d'ecriture sur fichier, ca ralentit trop
		ecrire_meta("restauration_status", "$abs_pos",'non');
		if ($oldtable != $table) {
			if (_DEBUG_IMPORT){
				ecrire_fichier(_DIR_TMP."debug_import.log","----\n".$GLOBALS['debug_import_avant']."\n<<<<\n$table\n>>>>\n".$GLOBALS['debug_import_apres']."\n----\n",false,false);
			}
			if ($oldtable) spip_log("$cpt entrees");
			spip_log("Analyse de $table (commence en $pos)");
			affiche_progression_javascript($abs_pos,$size,$table);
			$oldtable = $table;
			$cpt = 0;
			$pos = $abs_pos;
		} 
		$cpt++;
	}
	spip_log("$cpt entrees");

	if (!$import_ok) 
	  $res =  _T('avis_archive_invalide') . ' ' .
	    _T('taille_octets', array('taille' => $pos)) ;
	else {
		$res = '';
		affiche_progression_javascript('100 %', $size);
	}
	
	if ($GLOBALS['spip_version_base'] != (str_replace(',','.',$GLOBALS['meta']['version_installee']))){
		include_spip('base/upgrade');
		maj_base(); // upgrade jusqu'a la version courante
	}
	// regarder si on est pas en train d'importer dans une copie des tables
	if (isset($GLOBALS['meta']['restauration_table_prefix_source'])){
		$prefixe_source = $GLOBALS['meta']['restauration_table_prefix_source'];
		
		$GLOBALS['connexions']['-1'] = $GLOBALS['connexions'][0];
		// rebasculer le serveur sur les bonnes tables pour finir proprement
		$GLOBALS['connexions'][0]['prefixe'] = $prefixe_source;

		$tables = import_table_choix($request);
		$tables_recopiees = isset($GLOBALS['meta']['restauration_recopie_tables'])?unserialize($GLOBALS['meta']['restauration_recopie_tables']):array();
		
		// recopier les tables l'une sur l'autre
		// il FAUT recharger les bonnes desc serial/aux avant ...
		$GLOBALS['tables_principales'] = $GLOBALS['nouvelle_base']['tables_principales'];
		$GLOBALS['tables_auxiliaires'] = $GLOBALS['nouvelle_base']['tables_auxiliaires'];
		if (in_array('spip_auteurs',$tables)){
			$tables = array_diff($tables,array('spip_auteurs'));
			$tables[] = 'spip_auteurs';
		}
		sql_drop_table('spip_test','','-1');
		foreach ($tables as $table){
			if (!isset($tables_recopiees[$table])) $tables_recopiees[$table] = 0;
			if ($tables_recopiees[$table]!==-1){
				affiche_progression_javascript(0,0,$table);
				while (true) {
					$n = intval($tables_recopiees[$table]);
					$res = sql_select('*',$table,'','','',"$n,400",'','-1');
					while ($row = sql_fetch($res,'-1')){
						sql_insertq($table,$row);
						$tables_recopiees[$table]++;
					}
					if ($n == $tables_recopiees[$table])
						break;
					affiche_progression_javascript($tables_recopiees[$table],0,$table);
					ecrire_meta('restauration_recopie_tables',serialize($tables_recopiees));
				}
				sql_drop_table($table,'','-1');
				$tables_recopiees[$table]=-1;
				ecrire_meta('restauration_recopie_tables',serialize($tables_recopiees));
			}
		}
	}
	

	return $res ;
}

// http://doc.spip.org/@import_init_meta
function import_init_meta($tag, $atts, $charset, $request)
{
	$version_base = $atts['version_base'];
	if (version_compare($version_base,$GLOBALS['spip_version_base'],'<')
	 && !isset($GLOBALS['meta']['restauration_table_prefix'])
	 && ($request['insertion']!='on')
	 && ($request['insertion']!='passe2')){
		// effacer les tables ici
		$init = $request['init'];
		$init($request);

		// creer une base avec les tables dans l'ancienne version
		// et changer de contexte
		$creer_base_anterieure = charger_fonction('create','maj/vieille_base');
		$creer_base_anterieure($version_base);
	}
	
	$version_archive = $atts['version_archive'];
	$insert = $request['insertion'] ;
	ecrire_meta('restauration_attributs_archive', serialize($atts),'non');
	ecrire_meta('restauration_version_archive', $version_archive,'non');
	ecrire_meta('restauration_tag_archive', $tag,'non');

	// trouver le charset de la connexion sql qu'il faut utiliser pour la restauration
	// ou si le charset de la base est iso-xx
	// (on ne peut garder une connexion utf dans ce cas)
	// on laisse sql gerer la conversion de charset !

	if (isset($GLOBALS['meta']['charset_sql_connexion'])
		OR (strncmp($charset,'iso-',4)==0)
		){
		include_spip('base/abstract_sql');
		if ($sql_char = sql_get_charset($charset)){
			$sql_char = $sql_char['charset'];
			ecrire_meta('restauration_charset_sql_connexion',$sql_char);
		}
		else {
			// faire la conversion de charset en php :(
			effacer_meta('restauration_charset_sql_connexion'); # precaution
			spip_log("charset de restauration inconnu de sql : $charset");
			if ($insert)
				ecrire_meta('charset_insertion', $charset,'non');
			else	ecrire_meta('charset_restauration', $charset,'non');
		}
	}

	$i = $insert ? ("insertion  $insert") : '';
	spip_log("Debut de l'importation (charset: $charset, format: $version_archive) $i");
	return $version_archive;
}

// http://doc.spip.org/@import_affiche_javascript
function import_affiche_javascript($taille)
{
	$max_time = ini_get('max_execution_time')*1000;
	$t = _T('info_recharger_page');
	$t = "
<input type='text' size='10' name='taille' id='taille' value='$taille' />
<input type='text' class='forml' name='recharge' id='recharge' value='$t' />";
	echo debut_boite_alerte(),
	  "<span style='color: black;' class='verdana1 spip_large'><b>",  _T('info_base_restauration'),  "</b></span>",
	  generer_form_ecrire('', $t, " style='text-align: center' name='progression' id='progression' method='get' "),
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
	echo "\n<script type='text/javascript'><!--\n";

	if ($abs_pos == '100 %') {

		echo "document.progression.taille.value='$abs_pos';\n";
		if ($x = $GLOBALS['erreur_restauration']) {
			echo "document.progression.recharge.value='".str_replace("'", "\\'", unicode_to_javascript(html2unicode(_T('avis_erreur').": $x")))." ';\n";
		}
		else {
			echo "document.progression.recharge.value='".str_replace("'", "\\'", unicode_to_javascript(html2unicode(_T('info_fini'))))."';\n";
			echo "window.setTimeout('location.href=\"".self()."\";',0);";
		}
	}
	else {
		if (trim($table))
			echo "document.progression.recharge.value='$table';\n";
		if (!$size)
			$taille = preg_replace("/&nbsp;/", " ", taille_en_octets($abs_pos));
		else
			$taille = floor(100 * $abs_pos / $size)." %";
		echo "document.progression.taille.value='$taille';\n";
	}
	echo "\n--></script>\n";
	if (function_exists('ob_flush')) @ob_flush();
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
	global $tables_jointures;

	if (!isset($IMPORT_tables_noimport)) $IMPORT_tables_noimport=array();

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
