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

// http://doc.spip.org/@base_upgrade_dist
function base_upgrade_dist($titre)
{
	if ($GLOBALS['spip_version']!=$GLOBALS['meta']['version_installee']) {
		if (!is_numeric(_request('reinstall'))) {
			include_spip('base/create');
			spip_log("recree les tables eventuellement disparues");
			creer_base();
		}
		maj_base();
	}
	spip_log("Fin de mise a jour SQL. Debut m-a-j acces et config");
	include_spip('inc/acces');
	ecrire_acces();
	$config = charger_fonction('config', 'inc');
	$config();
}

// http://doc.spip.org/@maj_base
function maj_base($version_cible = 0) {
	global $spip_version;

	$version_installee = @$GLOBALS['meta']['version_installee'];
	//
	// Si version nulle ou inexistante, c'est une nouvelle installation
	//   => ne pas passer par le processus de mise a jour.
	// De meme en cas de version superieure: ca devait etre un test,
	// il y a eu le message d'avertissement il doit savoir ce qu'il fait
	//
	// version_installee = 1.702; quand on a besoin de forcer une MAJ
	
	spip_log("Version anterieure: $version_installee. Courante: $spip_version");
	if (!$version_installee OR ($spip_version < $version_installee)) {
		sql_replace('spip_meta', 
		      array('nom' => 'version_installee',
			    'valeur' => $spip_version,
			    'impt' => 'non'));
		return;
	}
	if (!upgrade_test()) return;
	
	$n = floor($version_installee * 10);
	$cible = ($version_cible ? $version_cible : $spip_version) * 10;
	while ($n < $cible) {
		$nom  = sprintf("v%03d",$n);
		$f = charger_fonction($nom, 'maj', true);
		if ($f) {
			spip_log("$f repercute les modifications de la version " . ($n/10));
			$f($version_installee, $spip_version);
		} else spip_log("pas de fonction pour la maj $n $nom");
		$n++;
	}
}

// A partir des > 1.926 (i.e SPIP > 1.9.2), le while ci-dessus aboutit ici.
// Se relancer soi-meme pour eviter l'interruption pendant une operation SQL
// (qu'on espere pas trop longue chacune)
// evidemment en ecrivant dans la meta a quel numero on en est.

define('_UPGRADE_TIME_OUT', 20);

// http://doc.spip.org/@maj_while
function maj_while($version_installee, $version_cible)
{
	$pref = floor($version_installee);
	$cible = substr($version_cible*1000,-3);
	$installee = substr($version_installee*1000,-3);
	$time = time();
	$n = 0;

	$chgt = $GLOBALS['maj'][$pref];
	while ($installee < $cible) {
		$installee++;
		$version = ($pref . '.' . $installee);
		if (isset($chgt[$installee])) {
			serie_alter($installee, $chgt[$installee]);
			$n = time() - $time;
			spip_log("MAJ de $version_installee a $version en $n secondes",'maj');
		} else spip_log("MAJ $version: rien pour SQL", 'maj');
		ecrire_meta('version_installee', $version,'non');
		if ($n >= _UPGRADE_TIME_OUT) {
			redirige_par_entete(generer_url_ecrire('upgrade', "reinstall=$installee", true));
		}
	}
}

// Appliquer une serie de chgt qui risquent de partir en timeout
// (Alter cree une copie temporaire d'une table, c'est lourd)

// http://doc.spip.org/@serie_alter
function serie_alter($serie, $q = array()) {
	$etape = intval(@$GLOBALS['meta']['upgrade_etape_'.$serie]);
	foreach ($q as $i => $req) {
		if ($i >= $etape) {
			$f = array_shift($req);
			spip_log("maj $serie etape $i: $f ".join(',',$req),'maj');
			if (function_exists($f)) {
			  call_user_func_array($f, $req);
			  ecrire_meta('upgrade_etape_'.$serie, $i+1);
			}
		}
	}
	effacer_meta('upgrade_etape_'.$serie);
}


// A refaire pour PG
// http://doc.spip.org/@convertir_un_champ_blob_en_text
function convertir_un_champ_blob_en_text($table,$champ,$type){
	$res = spip_query("SHOW FULL COLUMNS FROM $table LIKE '$champ'");
	if ($row = sql_fetch($res)){
		if (strtolower($row['Type'])!=strtolower($type)) {
			$default = $row['Default']?(" DEFAULT "._q($row['Default'])):"";
			$notnull = ($row['Null']=='YES')?"":" NOT NULL";
			sql_alter("TABLE $table CHANGE $champ $champ $type $default $notnull");
		}
	}
}

// http://doc.spip.org/@upgrade_test
function upgrade_test() {
	sql_drop_table("spip_test", true);
	sql_create("spip_test", array('a' => 'int'));
	sql_alter("TABLE spip_test ADD b INT");
	sql_insertq('spip_test', array('b' => 1), array('b' => 'int'));
	$result = sql_select('b', "spip_test");
	sql_alter("TABLE spip_test DROP b");
	return $result;
}

// pour versions anterieures a 1.922
// http://doc.spip.org/@maj_version
function maj_version ($version, $test = true) {
	if ($test) {
		if ($version>=1.922)
			ecrire_meta('version_installee', $version, 'non');
		else {
			// on le fait manuellement, car ecrire_meta utilise le champs impt qui est absent sur les vieilles versions
			$GLOBALS['meta']['version_installee'] = $version;
			sql_updateq('spip_meta',  array('valeur' => $version), "nom=" . _q('version_installee') );
		}
		spip_log("mise a jour de la base en $version");
	} else {
		echo _T('alerte_maj_impossible', array('version' => $version));
		exit;
	}
}

// pour versions anterieures a 1.945
// http://doc.spip.org/@upgrade_vers
function upgrade_vers($version, $version_installee, $version_cible = 0){
	return ($version_installee<$version
		AND (($version_cible>=$version) OR ($version_cible==0))
	);
}
?>
