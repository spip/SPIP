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

// Concatenation des tranches
// Il faudrait ouvrir une seule fois le fichier, et d'abord sous un autre nom
// et sans detruire les tranches: au final renommage+destruction massive pour
// prevenir autant que possible un Time-out.

// http://doc.spip.org/@ramasse_parties
function ramasse_parties($archive, $partfile, $files = array()){

	$files_o = array();
	if (!count($files))
		$files = preg_files(dirname($archive)."/",basename($partfile).".part_[0-9]+_[0-9]+");
	$ok = true;
	foreach($files as $f) {
	  $contenu = "";
	  if (lire_fichier ($f, $contenu)) {
	    if (!ecrire_fichier($archive,$contenu,false,false))
	      { $ok = false; break;}
	  }
	  unlink($f);
	  $files_o[]=$f;
	}

	if ($fin AND $ok)
		$ok = ecrire_fichier($archive, export_enpied(),false,false);

	return $ok ? $files_o : false;
}

define('_EXPORT_TRANCHES_LIMITE', 400);

//
// Exportation de table SQL au format xml
// La constante ci-dessus determine la taille des tranches,
// chaque tranche etant copiee immediatement dans un fichier 
// et son numero memorisee dans le serveur SQL.
// En cas d'abandon sur Time-out, le travail pourra ainsi avancer.
// Au final, on regroupe les tranches en un seul fichier
// et on memorise dans le serveur qu'on va passer a la table suivante.

// http://doc.spip.org/@export_objets
function export_objets($table, $liens, $etape, $cpt, $dir, $archive, $gz, $total) {
	global $tables_principales;

	$filetable = $dir . $archive . '.part_' . sprintf('%3d',$etape);
	$prim = isset($tables_principales[$table])
	  ? $tables_principales[$table]['key']["PRIMARY KEY"]
	  : '';
	$debut = $cpt * _EXPORT_TRANCHES_LIMITE;

	while (1){ // on ne connait pas le nb de paquets d'avance

		$string = build_while($debut, $table, $prim);
		// attention $string vide ne suffit pas a sortir
		// car les admins restreints peuvent parcourir
		// une portion de table vide pour eux.
		if ($string) { 
			// on ecrit dans un fichier generique
			// puis on le renomme pour avoir une operation atomique 
			ecrire_fichier ($filetable . '.temp', $string);
			// le fichier destination peut deja exister si on sort d'un timeout entre le rename et le ecrire_meta
			if (file_exists($f = $filetable . sprintf('_%4d',$cpt))) @unlink($f);
			rename($filetable . '.temp', $f);
		}
		$cpt++;
		$status_dump = "$gz::$archive::$etape::$cpt";
		// on se contente d'une ecriture en base pour aller plus vite
		// a la relecture on en profitera pour mettre le cache a jour
		ecrire_meta("status_dump", $status_dump,'non');
//die();
		$debut = $cpt * _EXPORT_TRANCHES_LIMITE;
		if ($debut >= $total) {break;}
		echo " $debut";

	}
	echo " $total."; 
	ramasse_parties($dir.$archive, $dir.$archive);
	$status_dump = "$gz::$archive::" . ($etape+1) . "::0";
	// on se contente d'une ecriture en base pour aller plus vite
	// a la relecture on en profitera pour mettre le cache a jour
	ecrire_meta("status_dump", $status_dump,'non');

}

// Construit la version xml  des champs d'une table

// http://doc.spip.org/@build_while
function build_while($debut, $table, $prim) {
	global $connect_toutes_rubriques, $chercher_logo ;

	$result = spip_query("SELECT * FROM $table LIMIT $debut," . _EXPORT_TRANCHES_LIMITE);

	$string = '';
	while ($row = spip_fetch_array($result,SPIP_ASSOC)) {
	  if ((!isset($row['impt'])) OR $row['impt']=='oui') {
			if (!($ok = $connect_toutes_rubriques)) {
				if (isset($row['id_rubrique']))
				  $ok = autoriser('publierdans','rubrique',$row['id_rubrique']);
				elseif (isset($row['id_article']))
				  $ok = autoriser('modifier','article',$row['id_article']);
				else $ok = true;
			}
			if ($ok) {
			  $attributs = "";
				if ($chercher_logo) {
					if ($logo = $chercher_logo($row[$prim], $prim, 'on'))
					  $attributs .= ' on="' . $logo[3] . '"';
					if ($logo = $chercher_logo($row[$prim], $prim, 'off'))
					  $attributs .= ' off="' . $logo[3] . '"';
				}

				$string .= "<$table$attributs>\n";
				foreach ($row as $k => $v) {
					$string .= "<$k>" . text_to_xml($row[$k]) . "</$k>\n";
			  }
				$string .= "</$table>\n\n";
			}
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

// http://doc.spip.org/@export_entete
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

// http://doc.spip.org/@export_enpied
function export_enpied () { return  "</SPIP>\n";}

?>
