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

include_spip('inc/presentation');
include_spip('inc/acces');
include_spip('base/abstract_sql');

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

/**
 * Lire l'entete du fichier importe
 * Balise ouvrante:
 * 'SPIP' si fait par spip, nom de la base source si fait par  phpmyadmin
 *
 * @param resource $f
 * @param string $gz
 * @return array
 */
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
function import_init_tables($request){
  global $connect_id_auteur;

	// commencer par verifier les meta et le champ impt=non
	$config = charger_fonction('config','inc');
	$config();


	// grand menage
	// on vide toutes les tables dont la restauration est demandee
	list($tables,) = base_liste_table_for_dump(lister_tables_noerase());
	spip_log(count($tables) . " tables effacees " . join(', ', $tables),'import');

	foreach($tables as $table){
		// regarder si il y a au moins un champ impt='non'
		if (($table!='spip_auteurs')){
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

	// retourner la liste des tables a importer, pas celle des tables videes !
	return import_table_choix($request);
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
function import_tables($request, $archive) {
	global $import_ok, $abs_pos,  $affiche_progression_pourcent;

	// regarder si on est pas en train d'importer dans une copie des tables
	if (isset($GLOBALS['meta']['restauration_table_prefix'])) {
		$charger = charger_fonction('charger','maj/vieille_base');
		$charger($GLOBALS['meta']['vieille_version_installee']);
		$GLOBALS['serveur_vieille_base'] = 0;
		$prefix = $GLOBALS['connexions'][$GLOBALS['serveur_vieille_base']]['prefixe'];
		$GLOBALS['connexions'][$GLOBALS['serveur_vieille_base']]['prefixe'] = $GLOBALS['meta']['restauration_table_prefix'];
		// verifier qu'une table meta existe bien
		// sinon c'est une restauration anterieure echouee
		if (!sql_getfetsel('valeur','spip_meta','','','','0,1')){
				$GLOBALS['connexions'][$GLOBALS['serveur_vieille_base']]['prefixe'] = $prefix;
				return;
		}
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

	if (!defined('_DEBUG_IMPORT')) define('_DEBUG_IMPORT', false);
	if (_DEBUG_IMPORT)
		ecrire_fichier(_DIR_TMP."debug_import.log","#####".date('Y-m-d H:i:s')."\n",false,false);
	$fimport = import_charge_version($version_archive);

	if ($request['insertion'] !== 'passe2')
		import_affiche_javascript($taille);

	if (function_exists('ob_flush')) @ob_flush();
	flush();
	$oldtable ='';
	$cpt = 0;
	$pos = $abs_pos;

	// BOUCLE principale qui tourne en rond jusqu'a le fin du fichier
	while ($table = $fimport($file, $request, $gz, $atts)) {
	  // memoriser pour pouvoir reprendre en cas d'interrupt,
	  // mais pas d'ecriture sur fichier, ca ralentit trop
		ecrire_meta("restauration_status", "$abs_pos",'non');
		if ($oldtable != $table) {
			if (_DEBUG_IMPORT){
				ecrire_fichier(_DIR_TMP."debug_import.log","----\n".$GLOBALS['debug_import_avant']."\n<<<<\n$table\n>>>>\n".$GLOBALS['debug_import_apres']."\n----\n",false,false);
			}
			if ($oldtable) spip_log("$cpt entrees","import");
			spip_log("Analyse de $table (commence en $pos)","import");
			affiche_progression_javascript($abs_pos,$size,$table);
			$oldtable = $table;
			$cpt = 0;
			$pos = $abs_pos;
		}
		$cpt++;
	}
	spip_log("$cpt entrees","import");
	spip_log("fin de l'archive, statut: " .($import_ok ? 'ok' : 'alert'),"import");

	if (!$import_ok) 
	  return  _T('avis_archive_invalide') . ' ' .
	    _T('taille_octets', array('taille' => $pos)) ;

	if ($GLOBALS['spip_version_base'] != (str_replace(',','.',$GLOBALS['meta']['version_installee']))){
		// il FAUT recharger les bonnes desc serial/aux avant ...
		include_spip('base/serial');
		$GLOBALS['tables_principales']=array();
		base_serial($GLOBALS['tables_principales']);
		include_spip('base/auxiliaires');
		$GLOBALS['tables_auxiliaires']=array();
		base_auxiliaires($GLOBALS['tables_auxiliaires']);
		$GLOBALS['tables_jointures']=array();
		include_spip('public/interfaces');
		declarer_interfaces();
		include_spip('base/upgrade');
		maj_base(); // upgrade jusqu'a la version courante
	}
	// regarder si on est pas en train d'importer dans une copie des tables
	if (isset($GLOBALS['meta']['restauration_table_prefix_source'])){
		$prefixe_source = $GLOBALS['meta']['restauration_table_prefix_source'];
		
		$GLOBALS['connexions']['-1'] = $GLOBALS['connexions'][0];
		// rebasculer le serveur sur les bonnes tables pour finir proprement
		$GLOBALS['connexions'][0]['prefixe'] = $prefixe_source;
		// et relire les meta de la bonne base
		lire_metas();


		$tables_recopiees = isset($GLOBALS['meta']['restauration_recopie_tables'])?unserialize($GLOBALS['meta']['restauration_recopie_tables']):array();
		spip_log("charge tables_recopiees ".serialize($tables_recopiees),'dbdump');

		// recopier les tables l'une sur l'autre
		// il FAUT recharger les bonnes desc serial/aux avant ...
		include_spip('base/serial');
		$GLOBALS['tables_principales']=array();
		base_serial($GLOBALS['tables_principales']);
		include_spip('base/auxiliaires');
		$GLOBALS['tables_auxiliaires']=array();
		base_auxiliaires($GLOBALS['tables_auxiliaires']);
		$GLOBALS['tables_jointures']=array();
		include_spip('public/interfaces');
		declarer_interfaces();
		
		// puis relister les tables a importer
		// et les vider si besoin, au moment du premier passage ici
		// (et seulement si ce n'est pas une fusion, comment le dit-on ?)
		$initialisation_copie = (!isset($GLOBALS['meta']["restauration_status_copie"])) ? 0 :
			$GLOBALS['meta']["restauration_status_copie"];

		if (!$initialisation_copie) {
			// vide les tables qui le necessitent
			$tables = import_init_tables($request);
			ecrire_meta("restauration_status_copie", "ok",'non');
		}
		else
			// la liste des tables a recopier
			$tables = import_table_choix($request);
		#		var_dump($tables);die();
		spip_log("tables a copier :".implode(", ",$tables),'dbdump');
		if (in_array('spip_auteurs',$tables)){
			$tables = array_diff($tables,array('spip_auteurs'));
			$tables[] = 'spip_auteurs';
		}
		if (in_array('spip_meta',$tables)){
			$tables = array_diff($tables,array('spip_meta'));
			$tables[] = 'spip_meta';
		}
		sql_drop_table('spip_test','','-1');
		foreach ($tables as $table){
			if (sql_showtable($table,true,-1)){
				if (!isset($tables_recopiees[$table])) $tables_recopiees[$table] = 0;
				if ($tables_recopiees[$table]!==-1){
					affiche_progression_javascript(0,0,$table);
					while (true) {
						$n = intval($tables_recopiees[$table]);
						$res = sql_select('*',$table,'','','',"$n,400",'','-1');
						while ($row = sql_fetch($res,'-1')){
							array_walk($row,'sql_quote');
							sql_replace($table,$row);
							$tables_recopiees[$table]++;
						}
						if ($n == $tables_recopiees[$table])
							break;
						spip_log("recopie $table ".$tables_recopiees[$table],'dbdump');
						affiche_progression_javascript($tables_recopiees[$table],0,$table);
						ecrire_meta('restauration_recopie_tables',serialize($tables_recopiees));
					}
					sql_drop_table($table,'','-1');
					spip_log("drop $table",'dbdump');
					$tables_recopiees[$table]=-1;
					ecrire_meta('restauration_recopie_tables',serialize($tables_recopiees));
					spip_log("tables_recopiees ".serialize($tables_recopiees),'dbdump');
				}
			}
		}
	}

	// recharger les metas
	lire_metas();
	#die();
	return '' ;
}

// http://doc.spip.org/@import_init_meta
function import_init_meta($tag, $atts, $charset, $request)
{
	$version_archive = $atts['version_archive'];
	$version_base = $atts['version_base'];
	$insert = $request['insertion'] ;

	$old = (!$insert 
		&& version_compare($version_base,$GLOBALS['spip_version_base'],'<')
		&& !isset($GLOBALS['meta']['restauration_table_prefix']));

	if ($old) {
		// creer une base avec les tables dans l'ancienne version
		// et changer de contexte
		$creer_base_anterieure = charger_fonction('create','maj/vieille_base');
		$creer_base_anterieure($version_base);
	}
	if ($old OR $insert) {
		$init = $request['init'];
		spip_log("import_init_meta lance $init","import");
		$init($request);
	}

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
	  fin_boite_alerte();
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
function import_table_choix($request){
	spip_log("noimport:".implode(',',lister_tables_noimport()),'noimport');
	list($tables,) = base_liste_table_for_dump(lister_tables_noimport());
	spip_log("liste:".implode(',',$tables),'noimport');
	return $tables;
}	
?>
