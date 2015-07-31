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
function ramasse_parties($archive, $partfile, $nb, $fin=''){
	// a ameliorer par un preg_file
	// si le rammassage est interrompu par un timeout, on perd des morceaux
	$files = array();
	$ok = true;
	if (!ecrire_fichier($archive,$fin ? export_entete() : '',false,false))
	  $ok = false;
	else {
		for($cpt =1; $cpt <= $nb; $cpt++) {
			if (file_exists($f = $partfile.".$cpt")) {
			  $contenu = "";
			  if (lire_fichier ($f, $contenu)) {
			    if (!ecrire_fichier($archive,$contenu,false,false))
			      { $ok = false; break;}
			  }
			  unlink($f);
			  $files[]=$f;
			}
		}
	}

	if ($fin AND $ok)
		$ok = ecrire_fichier($archive, export_enpied(),false,false);

	return $ok ? $files : false;
}

define('_EXPORT_TRANCHES_LIMITE', 400);

//
// Exportation de table SQL au format xml
// La constante ci-dessus determine la taille des tranches,
// chaque tranche etant copiee immediatement dans un fichier 
// et son numero memorisee dans le serveur SQL.
// En cas d'abandon sur Time-out, le travail pourra ainsi avancer
// charge a l'appelant de coller tous les morceaux de 1 a N

// http://doc.spip.org/@export_objets
function export_objets($table, $liens, $etape, $cpt, $dir, $archive, $gz, $total) {
	static $etape_affichee=array();

	$debut = $cpt * _EXPORT_TRANCHES_LIMITE;
	$filetable = $dir . $archive . '_' . $etape . '.';

	while (1){ // on ne connait pas le nb de paquets d'avance

		if ($GLOBALS['flag_ob_flush']) ob_flush();
		flush();

		$string = build_while($debut, $table);
		// attention $string vide ne suffit pas a sortir
		// car les admins restreints peuvent parcourir
		// une portion de table vide pour eux.
		if ($string) { 
// on ecrit dans un fichier generique
// puis on le renomme pour avoir une operation atomique 
			$cpt++;
			ecrire_fichier ($filetable, $string);
			rename($filetable,$filetable . $cpt);
		}

		$debut += _EXPORT_TRANCHES_LIMITE;
		if ($debut >= $total) {break;}
		echo " $debut";
	// on se contente d'une ecriture en base pour aller plus vite
	// a la relecture on en profitera pour mettre le cache a jour
		$status_dump = "$gz::$archive::$etape::$cpt";
		ecrire_meta("status_dump", $status_dump,'non');
	}
	echo " $total."; 
	return $cpt;
}

// Construit la version xml  des champs d'une table

// http://doc.spip.org/@build_while
function build_while($debut, $table) {
	global $connect_toutes_rubriques ;
	global $tables_principales;
	static $table_fields=array();

	$result = spip_query("SELECT * FROM $table LIMIT $debut," . _EXPORT_TRANCHES_LIMITE);
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
			$attributs = "";
			$string .= "<$table$attributs>\n";
			for ($i = 0; $i < $nfields; ++$i) {
				$k = $table_fields[$table][$i];
				$string .= "<$k>" . text_to_xml($row[$k]) . "</$k>\n";
			}
			$string .= "</$table>\n\n";
		}
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
