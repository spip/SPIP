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

$GLOBALS['version_archive'] = '1.3';

// http://doc.spip.org/@export_nom_fichier_dump
function export_nom_fichier_dump($dir,$gz=true){
	$archive = _SPIP_DUMP;
	if ($gz) $archive .= '.gz';
	$cpt=0;
	$stamp = date('Ymd');
	while ((file_exists($dir.($nom = str_replace('@stamp@',"_{$stamp}_".substr("00$cpt",-3),$archive))))&&($cpt<999))
		$cpt++;
	return $nom;
}


// http://doc.spip.org/@ramasse_parties
function ramasse_parties($archive, $gz, $partfile){
	// a ameliorer par un preg_file
	// si le rammassage est interrompu par un timeout, on perd des morceaux
	$cpt=0;
	$files = array();
	while(file_exists($f = $partfile.".$cpt")){
		$contenu = "";
		if (lire_fichier ($f, $contenu))
			if (!ecrire_fichier($archive,$contenu,false,false))
			{
				echo "<p>"._T('avis_erreur_sauvegarde', array('type'=>'.', 'id_objet'=>'. .'))."</p>\n";
				exit;
			}
		unlink($f);
		$files[]=$f;
		$cpt++;
	}
	return $files;
}

//
// Exportation generique d'objets (fichier ou retour de fonction)
//
// http://doc.spip.org/@export_objets
function export_objets($table, $liens, $file = 0, $gz = false, $etape_actuelle="", $nom_etape="",$limit=0) {
	static $etape_affichee=array();

	$string='';
	$status_dump = explode("::",$GLOBALS['meta']["status_dump"]);
	$etape_en_cours = $status_dump[2];
	$pos_in_table = $status_dump[3];

	if ($etape_en_cours < 1 OR $etape_en_cours == $etape_actuelle){

	  // on calcule ca autant de fois qu'il y a de paquets!
	  // on devrait plutot le faire dans l'appelant
	  // et le memoriser dans status_dump
		$result = spip_query("SELECT COUNT(*) FROM $table");
		$row = spip_fetch_array($result,SPIP_NUM);
		$total = $row[0];
		if (!isset($etape_affichee[$etape_actuelle]) AND $total){
			echo "\n<br /><strong>$etape_actuelle-$nom_etape</strong>";
			$etape_affichee[$etape_actuelle] = 1;
		}
		if ($pos_in_table!=0 AND $total)
			echo " ", $pos_in_table;
		if ($GLOBALS['flag_ob_flush']) ob_flush();
		flush();
		if ($limit == 0) $limit = $total;

		$string = build_while($pos_in_table, $limit, $table);

		if ($pos_in_table>=$total){
			if ($total) echo " ok";
			$status_dump[2] = $status_dump[2]+1;
			$status_dump[3] = 0;
		} else  $status_dump[3] = $pos_in_table;

		if ($file) {
			$_fputs = ($gz) ? gzputs : fputs;
			$_fputs($file, $string);
			fflush($file);
			ecrire_meta("status_dump", implode("::",$status_dump),'non');
			$string='';
		}
	} else { // ca ne sert plus il me semble
	  if ($etape_actuelle < $etape_en_cours) {
		if (!isset($etape_affichee[$etape_actuelle]))
			echo "\n<li>", $etape_actuelle,'-',$nom_etape,"</li>";
	  } else {
		if (!isset($etape_affichee[$etape_actuelle]))
			echo "\n<li> <span style='color: #999999'>",$etape_actuelle,'-',$nom_etape,'</span></li>';
	  }
	  if ($GLOBALS['flag_ob_flush']) ob_flush();
	  flush();
	}
	return array($string,$status_dump);
}

// Exporter les champs de la table

// http://doc.spip.org/@build_while
function build_while(&$pos_in_table, $limit, $table) {
	global $connect_toutes_rubriques ;
	global $tables_principales;
	static $table_fields=array();

	$result = spip_query("SELECT * FROM $table LIMIT " . intval($pos_in_table) .',' . intval($limit));
	// Recuperer les noms des champs
	// Ces infos sont donnees par le abstract_showtable
	// les instructions natives mysql ne devraient pas apparaitre ici
	if (!isset($table_fields[$table])){
		$nfields = mysql_num_fields($result);
		for ($i = 0; $i < $nfields; ++$i)
		  $table_fields[$table][$i] = mysql_field_name($result, $i);
	} else	$nfields = count($table_fields[$table]);

	$string = '';
	$all = $connect_toutes_rubriques
	  ||(!in_array('id_rubrique',$table_fields[$table]));

	while ($row = spip_fetch_array($result,SPIP_ASSOC)) {
		if ((!isset($row['impt']) OR $row['impt']=='oui')
		AND ($all OR autoriser('publierdans','rubrique',$row['id_rubrique']))) {
			$string .= "<$table>\n";
			for ($i = 0; $i < $nfields; ++$i) {
				$k = $table_fields[$table][$i];
				$string .= "<$k>" . text_to_xml($row[$k]) . "</$k>\n";
			}
			$string .= "</$table>\n\n";
		}
		$pos_in_table++;
	}
	spip_free_result($result);
	return $string;
}

// Conversion texte -> xml (ajout d'entites)
// http://doc.spip.org/@text_to_xml
function text_to_xml($string) {
	return str_replace('<', '&lt;', str_replace('&', '&amp;', $string));
}

// production de l'entete du fichier d'archive

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

function export_enpied () { return  "</SPIP>\n";}

?>
