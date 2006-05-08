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

global $IMPORT_tables_noerase;
$IMPORT_tables_noerase[]='spip_ajax_fonc';
$IMPORT_tables_noerase[]='spip_meta';

function xml_fetch_tag($f, &$before, $gz=false, $skip_comment=true) {
	global $buf, $pos, $abs_pos;
	static $buf_len = 500;
	static $_fread,$_feof,$_ftell;
	if (!$_fread){
		$_fread = ($gz) ? gzread : fread;
		$_feof = ($gz) ? gzeof : feof;
		$_ftell = ($gz) ? gztell : ftell;
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

function import_objet_1_3($f, $gz=false, $tag_fermant='SPIP', $tables, $phpmyadmin=false) {
	global $import_ok, $pos, $abs_pos;
	static $time_javascript;

	global $tables_trans;
	static $primary_table;
	static $relation_liste;
	global $tables_principales;
	global $tables_auxiliaires;

	$import_ok = false;
	$b = '';
	// Lire le type d'objet
	if (!($table = xml_fetch_tag($f, $b, $gz))) return false;
	if ($table == ('/'.$tag_fermant)) return !($import_ok = true);
	#spip_log("import_objet_1_3 : table $table");
	if (!isset($primary_table[$table]))
		$primary_table[$table]=primary_index_table($table);

	$primary = $primary_table[$table];
	$id_objet = 0;
	$liens = array();

	// Lire les champs de l'objet
	for (;;) {
		$b = '';
		if (!($col = xml_fetch_tag($f, $b, $gz))) return false;
		if ($col == '/'.$table) break;
		if (substr($col,0,1) == '/')
		{ // tag fermant ici : probleme erreur de format
			spip_log('restauration : table $table tag fermanr $col innatendu');
		  break;
		}
		$value = '';
		if (!xml_fetch_tag($f, $value, $gz)) return false;

		if ($col != 'maj') {
			if ($phpmyadmin)
				$value = str_replace(array('&quot;','&gt;'),array('"','>'),$value);
			$cols[] = $col;
			$values[] = "'".addslashes($value)."'";
			if ($col == $primary) $id_objet = $value;
		}
	}
	
	if (isset($tables_trans[$table])) $table = $tables_trans[$table];
	if (in_array($table,$tables)){

		#spip_log("import_objet_1_3 : query $query");
		if (!spip_query("REPLACE $table (" . join(',', $cols) . ') VALUES (' . join(',', $values) . ')')) {
			echo "--><br><font color='red'><b>"._T('avis_erreur_mysql')."</b></font>\n<font color='black'><tt>".spip_sql_error()."</tt></font>\n<!--";
			$GLOBALS['erreur_restauration'] = true;
		}
	}

	$p = $pos + $abs_pos;
	// on se contente d'une ecriture en bdd car sinon le temps de backup
	// est double. Il faut juste faire attention a bien lire_metas()
	// au debut de la restauration
	ecrire_meta("status_restauration", "$p");
	#ecrire_metas(); 

	if (time() - $time_javascript > 3) {	// 3 secondes
		affiche_progression_javascript($abs_pos,$table);
		$time_javascript = time();
	}

	return $import_ok = true;
}

// pour le support des vieux dump
function import_objet_1_2($f, $gz=false) {
	global $import_ok, $pos, $abs_pos;
	static $time_javascript;

	if (time() - $time_javascript > 3) {	// 3 secondes
		affiche_progression_javascript($abs_pos);
		$time_javascript = time();
	}

	static $tables;
	if (!$tables) $tables = array(
		'article' => 'spip_articles',
		'auteur' => 'spip_auteurs',
		'breve' => 'spip_breves',
		'document' => 'spip_documents',
		'forum' => 'spip_forum',
		'groupe_mots' => 'spip_groupes_mots',
		'message' => 'spip_messages',
		'mot' => 'spip_mots',
		'petition' => 'spip_petitions',
		'rubrique' => 'spip_rubriques',
		'signature' => 'spip_signatures',
		'syndic' => 'spip_syndic',
		'syndic_article' => 'spip_syndic_articles',
		'type_document' => 'spip_types_documents'
	);

	$import_ok = false;
	$b = '';
	// Lire le type d'objet
	if (!($type = xml_fetch_tag($f, $b, $gz))) return false;
	if ($type == '/SPIP') return !($import_ok = true);
	$id = "id_$type";
	$id_objet = 0;

	// Lire les champs de l'objet
	for (;;) {
		$b = '';
		if (!($col = xml_fetch_tag($f, $b, $gz))) return false;
		if ($col == '/'.$type) break;
		$value = '';
		if (!xml_fetch_tag($f, $value, $gz)) return false;
		if (substr($col, 0, 5) == 'lien:') {
			$type_lien = substr($col, 5);
			$liens[$type_lien][] = '('.$id_objet.','.$value.')';
		}
		else if ($col != 'maj') {
			// tentative de restauration d'une base sauvegardee avec le champ 'images' ; d'experience, ca arrive...
			// mieux vaut accepter que canner silencieusement...
			if (($type == 'article') && ($col == 'images'))
			{
				if ($value) {		// ne pas afficher de message si on a un champ suppl mais vide
					echo "--><br><font color='red'><b>"._T('avis_erreur_sauvegarde', array('type' => $type, 'id_objet' => $id_objet))."</b></font>\n<font color='black'>"._T('avis_colonne_inexistante', array('col' => $col));
					if ($col == 'images') echo _T('info_verifier_image');
					echo "</font>\n<!--";
					$GLOBALS['erreur_restauration'] = true;
				}
			}
			else {
				$cols[] = $col;
				$values[] = '"'.addslashes($value).'"';
				if ($col == $id) $id_objet = $value;
			}
		}
	}

	$table = $tables[$type];
	if (!spip_query("REPLACE $table (" . join(',', $cols) . ') VALUES (' . join(',', $values) . ')')) {
		echo "--><br><font color='red'><b>"._T('avis_erreur_mysql')."</b></font>\n<font color='black'><tt>".spip_sql_error()."</tt></font>\n<!--";
		$GLOBALS['erreur_restauration'] = true;
	}

	if ($type == 'article') {
		spip_query("DELETE FROM spip_auteurs_articles WHERE id_article=$id_objet");
		spip_query("DELETE FROM spip_documents_articles WHERE id_article=$id_objet");
	}
	else if ($type == 'rubrique') {
		spip_query("DELETE FROM spip_auteurs_rubriques WHERE id_rubrique=$id_objet");
		spip_query("DELETE FROM spip_documents_rubriques WHERE id_rubrique=$id_objet");
	}
	else if ($type == 'breve') {
		spip_query("DELETE FROM spip_documents_breves WHERE id_breve=$id_objet");
	}
	else if ($type == 'mot') {
		spip_query("DELETE FROM spip_mots_articles WHERE id_mot=$id_objet");
		spip_query("DELETE FROM spip_mots_breves WHERE id_mot=$id_objet");
		spip_query("DELETE FROM spip_mots_forum WHERE id_mot=$id_objet");
		spip_query("DELETE FROM spip_mots_rubriques WHERE id_mot=$id_objet");
		spip_query("DELETE FROM spip_mots_syndic WHERE id_mot=$id_objet");
	}
	else if ($type == 'auteur') {
		spip_query("DELETE FROM spip_auteurs_rubriques WHERE id_auteur=$id_objet");
	}
	else if ($type == 'message') {
		spip_query("DELETE FROM spip_auteurs_messages WHERE id_message=$id_objet");
	}
	if ($liens) {
		reset($liens);
		while (list($type_lien, $t) = each($liens)) {
			if ($type == 'auteur' OR $type == 'mot' OR $type == 'document')
				if ($type_lien == 'syndic' OR $type_lien == 'forum') $table_lien = 'spip_'.$type.'s_'.$type_lien;
				else $table_lien = 'spip_'.$type.'s_'.$type_lien.'s';
			else
				$table_lien = 'spip_'.$type_lien.'s_'.$type.'s';
			spip_abstract_insert($table_lien, "($id, id_$type_lien)", join(',', $t));
		}
	}

	$p = $pos + $abs_pos;
	ecrire_meta("status_restauration", "$p");

	return $import_ok = true;
}


// pour le support des vieux dump
// pff ou vous l'avez trouve ce dump ?
function import_objet_0_0($f, $gz=false) {
	global $import_ok, $pos, $abs_pos;

	$import_ok = false;
	$b = '';
	if (!($type = xml_fetch_tag($f, $b, $gz))) return false;
	if ($type == '/SPIP') return !($import_ok = true);
	$is_art = ($type == 'article');
	$is_mot = ($type == 'mot');
	for (;;) {
		$b = '';
		if (!($col = xml_fetch_tag($f, $b, $gz))) return false;
		if ($col == ("/$type")) break;
		$value = '';
		if (!xml_fetch_tag($f, $value, $gz)) return false;
		if ($is_art AND $col == 'id_auteur') {
			$auteurs[] = $value;
		}
		else if ($is_mot AND $col == 'id_article') {
			$articles[] = $value;
		}
		else if ($is_mot AND $col == 'id_breve') {
			$breves[] = $value;
		}
		else if ($is_mot AND $col == 'id_forum') {
			$forums[] = $value;
		}
		else if ($is_mot AND $col == 'id_rubrique') {
			$rubriques[] = $value;
		}
		else if ($is_mot AND $col == 'id_syndic') {
			$syndics[] = $value;
		}
		else if ($col != 'maj') {
			$cols[] = $col;
			$values[] = '"'.addslashes($value).'"';
			if ($is_art && ($col == 'id_article')) $id_article = $value;
			if ($is_mot && ($col == 'id_mot')) $id_mot = $value;
		}
	}

	$table = "spip_$type";
	if ($type != 'forum' AND $type != 'syndic') $table .= 's';
	spip_query("REPLACE $table (" . join(",", $cols) . ") VALUES (" . join(",", $values) . ")");

	if ($is_art && $id_article) {
		spip_query("DELETE FROM spip_auteurs_articles WHERE id_article=$id_article");
		if ($auteurs) {
			reset ($auteurs);
			while (list(, $auteur) = each($auteurs)) {
			  spip_abstract_insert("spip_auteurs_articles", "(id_auteur, id_article)", "($auteur, $id_article)");
			}
		}
	}
	if ($is_mot && $id_mot) {
		spip_query("DELETE FROM spip_mots_articles WHERE id_mot=$id_mot");
		spip_query("DELETE FROM spip_mots_breves WHERE id_mot=$id_mot");
		spip_query("DELETE FROM spip_mots_forum WHERE id_mot=$id_mot");
		spip_query("DELETE FROM spip_mots_rubriques WHERE id_mot=$id_mot");
		spip_query("DELETE FROM spip_mots_syndic WHERE id_mot=$id_mot");
		if ($articles) {
			reset ($articles);
			while (list(, $article) = each($articles)) {

				spip_abstract_insert("spip_mots_articles", "(id_mot, id_article)", "($id_mot, $article)");
			}
		}
		if ($breves) {
			reset ($breves);
			while (list(, $breve) = each($breves)) {

				spip_abstract_insert("spip_mots_breves", "(id_mot, id_breve)", "($id_mot, $breve)");
			}
		}
		if ($forums) {
			reset ($forums);
			while (list(, $forum) = each($forums)) {

				spip_abstract_insert("spip_mots_forum", "(id_mot, id_forum)", "($id_mot, $forum)");
			}
		}
		if ($rubriques) {
			reset ($rubriques);
			while (list(, $rubrique) = each($rubriques)) {

				spip_abstract_insert("spip_mots_rubriques", "(id_mot, id_rubrique)", "($id_mot, $id_rubrique)");
			}
		}
		if ($syndics) {
			reset ($syndics);
			while (list(, $syndic) = each($syndics)) {

				spip_abstract_insert("spip_mots_syndic", "(id_mot, id_syndic)", "($id_mot, $syndic)");
			}
		}
	}

	$p = $pos + $abs_pos;
	ecrire_meta("status_restauration", "$p");

	return $import_ok = true;
}

function import_objet($f, $gz = false) {
	return import_objet_1_2($f, $gz);
}

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

function import_tables($f, $tables, $gz=false) {
	global $IMPORT_tables_noerase;
	global $import_ok;
	global $auth_htaccess;
	global $connect_id_auteur;
	$_fseek = ($gz) ? gzseek : fseek;

	$s = spip_query("SELECT UNIX_TIMESTAMP(maj) AS d FROM spip_meta WHERE nom='debut_restauration'");
	list($my_date) = spip_fetch_array($s);
	if (!$my_date) return false;

	$my_pos = 0;
	if (isset($GLOBALS['meta']["status_restauration"]))
		$my_pos = $GLOBALS['meta']["status_restauration"];

	if ($my_pos==0) {
		// Debut de l'importation
		ecrire_meta('charset_restauration', 'iso-8859-1');
		if (!($r = import_debut($f, $gz))) {
			ecrire_meta("erreur", _T('avis_archive_incorrect'));
			return false;
		}

		// grand menage
		// on vide toutes les tables dont la restauration est demandee
		foreach($tables as $table){
			$name = preg_replace("{^spip_}","",$table);
			if (($table!='spip_auteurs')&&(!in_array($table,$IMPORT_tables_noerase))){
				spip_query("DELETE FROM $table");
			}
		}

		if (in_array("spip_auteurs",$tables)){
			// Bidouille pour garder l'acces admin actuel pendant toute la restauration
			spip_query("UPDATE spip_auteurs SET id_auteur=0 WHERE id_auteur=$connect_id_auteur");
			spip_query("DELETE FROM spip_auteurs WHERE id_auteur!=0");
		}
		// tag ouvrant :
		// 'SPIP' pour un dump xml spip, nom de la base source pour un dump phpmyadmin
		$tag_archive = $r[0];
		$version_archive = $r[1]['version_archive'];
		ecrire_meta('version_archive_restauration', $version_archive);
		ecrire_meta('tag_archive_restauration', $tag_archive);
		#ecrire_metas();

	}
	else {
		// Reprise de l'importation
		$_fseek($f, $my_pos);
		$version_archive = $GLOBALS['meta']['version_archive_restauration'];
		$tag_archive = $GLOBALS['meta']['tag_archive_restauration'];
	}

	// Restauration des entrees du fichier
	if (preg_match("{^phpmyadmin::}is",$version_archive)){
		#spip_log("restauration phpmyadmin : version $version_archive tag $tag_archive");
		while (import_objet_1_3($f, $gz, $tag_archive, $tables, true));
	}
	else{
		switch ($version_archive) {
			case '1.3':
				while (import_objet_1_3($f, $gz, $tag_archive, $tables));
				break;
			case '1.2':
				while (import_objet_1_2($f, $gz));
				break;
			default:
				while (import_objet_0_0($f, $gz));
				break;
		}
	}
	if (!$import_ok) {
		ecrire_meta("erreur", _T('avis_archive_invalide'));
		return false;
	}

	// Mise a jour du fichier htpasswd

	ecrire_acces();

	// Destruction des entrees non restaurees

	detruit_non_restaurees($mydate, $tables);

	import_fin();

	affiche_progression_javascript('100 %');

	return true;
}

// Destruction des entrees non restaurees

function detruit_non_restaurees($mydate, $tables)
{
	spip_query("DELETE FROM spip_auteurs WHERE id_auteur=0");
	//foreach ($tables as $v) 
	//  spip_query("DELETE FROM $v WHERE UNIX_TIMESTAMP(maj) < $my_date");
}


function affiche_progression_javascript($abs_pos,$table="") {
	global $affiche_progression_pourcent;
	include_ecrire('inc_charsets');
	ob_flush();flush();
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
		if ($table!="")
			echo "document.progression.recharge.value='$table';\n";
		if (! $affiche_progression_pourcent)
			$taille = ereg_replace("&nbsp;", " ", taille_en_octets($abs_pos));
		else
			$taille = floor(100 * $abs_pos / $affiche_progression_pourcent)." %";
		echo "document.progression.taille.value='$taille';\n";
		echo "//--></script>\n<!--\n";
	}

	ob_flush();flush();
}

function import_all_continue($tables)
{
	global $meta, $flag_gz, $buf, $pos, $abs_pos;
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
	ob_flush();flush();

	echo "<font color='white'>\n<!--";

	$_fopen = ($gz) ? gzopen : fopen;
	$f = $_fopen($archive, "rb");
	$pos = 0;
	$buf = "";
	if (!import_tables($f, $tables, $gz))
		import_abandon();
	else	import_fin();
}

?>