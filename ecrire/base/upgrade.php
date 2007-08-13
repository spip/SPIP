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

include_spip('inc/meta');

// http://doc.spip.org/@base_upgrade_dist
function base_upgrade_dist($titre)
{
	include_spip('base/create');
	creer_base();
	maj_base();

	include_spip('inc/acces');
	ecrire_acces();
	$config = charger_fonction('config', 'inc');
	$config();
}

// http://doc.spip.org/@maj_version
function maj_version ($version, $test = true) {
	if ($test) {
		if ($version>=1.922)
			ecrire_meta('version_installee', $version,'non');
		else {
			// on le fait manuellement, car ecrire_meta utilise le champs impt qui est absent sur les vieilles versions
			$GLOBALS['meta'][$nom] = $valeur;
			spip_query("UPDATE spip_meta SET valeur=" . _q($valeur) ."$r WHERE nom=" . _q($nom) );
		}
		ecrire_metas();
		spip_log("mise a jour de la base en $version");
	} else {
		echo _T('alerte_maj_impossible', array('version' => $version));
		exit;
	}
}

// http://doc.spip.org/@upgrade_vers
function upgrade_vers($version, $version_installee, $version_cible = 0){
	return ($version_installee<$version
		AND (($version_cible>=$version) OR ($version_cible==0))
	);
}

// http://doc.spip.org/@convertir_un_champ_blob_en_text
function convertir_un_champ_blob_en_text($table,$champ,$type){
	$res = spip_query("SHOW FULL COLUMNS FROM $table LIKE '$champ'");
	if ($row = sql_fetch($res)){
		if (strtolower($row['Type'])!=strtolower($type)) {
			$default = $row2['Default']?(" DEFAULT "._q($row2['Default'])):"";
			$notnull = ($row2['Null']=='YES')?"":" NOT NULL";
			$q = "ALTER TABLE $table CHANGE $champ $champ $type $default $notnull";
			spip_log($q);
			spip_query($q);
		}
	}
}

// http://doc.spip.org/@maj_base
function maj_base($version_cible = 0) {
	global $spip_version;

	//
	// Lecture de la version installee
	//

	$version_installee = 0.0;
	$result = spip_query("SELECT valeur FROM spip_meta WHERE nom='version_installee'");
	if ($result) if ($row = sql_fetch($result)) $version_installee = (double) $row['valeur'];

	//
	// Si pas de version mentionnee dans spip_meta, c'est qu'il s'agit
	// d'une nouvelle installation
	//   => ne pas passer par le processus de mise a jour
	// De meme en cas de version superieure: ca devait etre un test,
	// il y a eu le message d'avertissement il doit savoir ce qu'il fait
	//
	// $version_installee = 1.702; quand on a besoin de forcer une MAJ
	
	spip_log("version anterieure: $version_installee");
	if (!$version_installee OR ($spip_version < $version_installee)) {
		spip_query("REPLACE spip_meta (nom, valeur,impt)
			VALUES ('version_installee', '$spip_version','non')");
		return true;
	}

	//
	// Verification des droits de modification sur la base
	//

	spip_query("DROP TABLE IF EXISTS spip_test");
	spip_query("CREATE TABLE spip_test (a INT)");
	spip_query("ALTER TABLE spip_test ADD b INT");
	spip_query("INSERT INTO spip_test (b) VALUES (1)");
	$result = spip_query("SELECT b FROM spip_test");
	spip_query("ALTER TABLE spip_test DROP b");
	if (!$result) return false;
	
	$n = floor($version_installee * 10);
	$cible = ($version_cible ? $version_cible : $spip_version) * 10;
	while ($n < $cible) {
		$nom  = sprintf("maj%03d",$n);
		$f = charger_fonction($nom, 'base', true);
		spip_log("$f repercute les modifications de la version " . ($n/10));
		if ($f) $f($version_installee, $version_cible);
		$n++;
	}
}

?>
