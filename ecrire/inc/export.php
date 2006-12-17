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
	while(file_exists($f = $partfile.".$cpt")){
		$contenu = "";
		if (lire_fichier ($f, $contenu))
			if (!ecrire_fichier($archive,$contenu,false,false))
			{
				echo "<p>"._T('avis_erreur_sauvegarde', array('type'=>'.', 'id_objet'=>'. .'))."</p>\n";
				exit;
			}
		unlink($f);
		$cpt++;
	}
}

//
// Exportation generique d'objets (fichier ou retour de fonction)
//
// http://doc.spip.org/@export_objets
function export_objets($table, $primary, $liens, $file = 0, $gz = false, $etape_actuelle="", $nom_etape="",$limit=0) {
	static $etape_affichee=array();
	static $table_fields=array();
	$string='';

	$status_dump = explode("::",$GLOBALS['meta']["status_dump"]);
	$etape_en_cours = $status_dump[2];
	$pos_in_table = $status_dump[3];
	
	if ($etape_en_cours < 1 OR $etape_en_cours == $etape_actuelle){

		$result = spip_query("SELECT COUNT(*) FROM $table");
		$row = spip_fetch_array($result,SPIP_NUM);
		$total = $row[0];
		$debut = $pos_in_table;
		if (!isset($etape_affichee[$etape_actuelle])){
			echo "<li><strong>$etape_actuelle-$nom_etape</strong>";
			echo " : $total";
			$etape_affichee[$etape_actuelle] = 1;
			if ($limit<$total) echo "</li>";
		}
		if ($pos_in_table!=0)
			echo "| ", $pos_in_table;
		if ($GLOBALS['flag_ob_flush']) ob_flush();
		flush();

		if ($limit == 0) $limit=$total;
		$result = spip_query("SELECT * FROM $table LIMIT $debut,$limit");
#" LIMIT  $limit OFFSET $debut" # PG

		if (!isset($table_fields[$table])){
			$nfields = mysql_num_fields($result);
			// Recuperer les noms des champs
			for ($i = 0; $i < $nfields; ++$i) $table_fields[$table][$i] = mysql_field_name($result, $i);
		}
		else
			$nfields = count($table_fields[$table]);

		$string = build_while($file,$gz, $nfields, $pos_in_table, $result, $status_dump, $table, $table_fields[$table]);

		if ($pos_in_table>=$total){
			// etape suivante : 
			echo " ok";
			$status_dump[2] = $status_dump[2]+1;
			$status_dump[3] = 0;
		}
		if ($file) {
			// on se contente d'une ecriture en base pour aller plus vite
			// a la relecture on en profitera pour mettre le cache a jour
			ecrire_meta("status_dump", implode("::",$status_dump),'non');
			#lire_metas();
			#ecrire_metas();
		}
		spip_free_result($result);
		return array($string,$status_dump);
	}
	else if ($etape_actuelle < $etape_en_cours) {
		if (!isset($etape_affichee[$etape_actuelle]))
			echo "<li>", $etape_actuelle,'-',$nom_etape,"</li>";
		if ($GLOBALS['flag_ob_flush']) ob_flush();
		flush();
	} else {
		if (!isset($etape_affichee[$etape_actuelle]))
			echo "<li> <font color='#999999'>",$etape_actuelle,'-',$nom_etape,'</font></li>';
		if ($GLOBALS['flag_ob_flush']) ob_flush();
		flush();
	}
	return array($string,$status_dump);
}

// Exporter les champs de la table

// http://doc.spip.org/@build_while
function build_while($file,$gz, $nfields, &$pos_in_table, $result, &$status_dump, $table, $fields) {
	global $connect_toutes_rubriques ;
	$string = '';
	$begin = build_begin_tag($table);
	$end = build_end_tag($table);
	$all = $connect_toutes_rubriques || (!in_array('id_rubrique',$fields));
	while ($row = spip_fetch_array($result,SPIP_ASSOC)) {
		$item = '';
		if (!isset($row['impt']) OR $row['impt']=='oui'){
			for ($i = 0; $i < $nfields; ++$i) {
				$k = $fields[$i];
				$item .= "<$k>" . text_to_xml($row[$k]) . "</$k>\n";
			}
			if ($all OR acces_rubrique($row['id_rubrique']))
				$string .= "$begin$item$end";
		}
		$status_dump[3] = $pos_in_table = $pos_in_table +1;
	}

	if ($file) {
		$_fputs = ($gz) ? gzputs : fputs;
		$_fputs($file, $string);
		fflush($file);
		// on se contente d'une ecriture en base pour aller plus vite
		// a la relecture on en profitera pour mettre le cache a jour
		ecrire_meta("status_dump", implode("::",$status_dump),'non');
		$string = '';
	}
	return $string;
}

// http://doc.spip.org/@build_begin_tag
function build_begin_tag($tag) {
	return "<$tag>\n";
}

// http://doc.spip.org/@build_end_tag
function build_end_tag($tag) {
	return "</$tag>\n\n";
}

// Conversion texte -> xml (ajout d'entites)
// http://doc.spip.org/@text_to_xml
function text_to_xml($string) {
	return str_replace('<', '&lt;', str_replace('&', '&amp;', $string));
}

?>
