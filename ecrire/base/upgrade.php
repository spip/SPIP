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

// http://doc.spip.org/@base_upgrade_dist
function base_upgrade_dist($titre='', $reprise='')
{
	if (!$titre) return; // anti-testeur automatique
	if ($GLOBALS['spip_version']!=$GLOBALS['meta']['version_installee']) {
		if (!is_numeric(_request('reinstall'))) {
			include_spip('base/create');
			spip_log("recree les tables eventuellement disparues");
			creer_base();
		}
		maj_base();
	}
	spip_log("Fin de mise a jour SQL. Debut m-a-j acces et config");
	
	// supprimer quelques fichiers temporaires qui peuvent se retrouver invalides
	spip_unlink(_DIR_TMP.'menu-rubriques-cache.txt');
	spip_unlink(_DIR_TMP.'plugin_xml.cache');
	spip_unlink(_DIR_SESSIONS.'ajax_fonctions.txt');
	spip_unlink(_DIR_TMP.'charger_pipelines.php');
	spip_unlink(_DIR_TMP.'charger_plugins_fonctions.php');
	spip_unlink(_DIR_TMP.'charger_plugins_options.php');
	spip_unlink(_DIR_TMP.'verifier_plugins.txt');

	include_spip('inc/acces');
	ecrire_acces();
	$config = charger_fonction('config', 'inc');
	$config();
}

// http://doc.spip.org/@maj_base
function maj_base($version_cible = 0) {
	global $spip_version_base;

	$version_installee = @$GLOBALS['meta']['version_installee'];
	//
	// Si version nulle ou inexistante, c'est une nouvelle installation
	//   => ne pas passer par le processus de mise a jour.
	// De meme en cas de version superieure: ca devait etre un test,
	// il y a eu le message d'avertissement il doit savoir ce qu'il fait
	//
	// version_installee = 1.702; quand on a besoin de forcer une MAJ
	
	spip_log("Version anterieure: $version_installee. Courante: $spip_version_base");
	if (!$version_installee OR ($spip_version_base < $version_installee)) {
		sql_replace('spip_meta', 
		      array('nom' => 'version_installee',
			    'valeur' => $spip_version_base,
			    'impt' => 'non'));
		return;
	}
	if (!upgrade_test()) return;
	
	$cible = ($version_cible ? $version_cible : $spip_version_base);

	if ($version_installee <= 1.926) {
		$n = floor($version_installee * 10);
		while ($n < 19) {
			$nom  = sprintf("v%03d",$n);
			$f = charger_fonction($nom, 'maj', true);
			if ($f) {
				spip_log("$f repercute les modifications de la version " . ($n/10));
				$f($version_installee, $spip_version_base);
			} else spip_log("pas de fonction pour la maj $n $nom");
			$n++;
		}
		include_spip('maj/v019_pre193');
		v019_pre193($version_installee, $version_cible);
	}
	if ($version_installee < 2000) {
		if ($version_installee < 2)
			$version_installee = $version_installee*1000;
		include_spip('maj/v019');
	}
	if ($cible < 2)
		$cible = $cible*1000;

	maj_while($version_installee, $cible);
}


// A partir des > 1.926 (i.e SPIP > 1.9.2), cette fonction gere les MAJ.
// Se relancer soi-meme pour eviter l'interruption pendant une operation SQL
// (qu'on espere pas trop longue chacune)
// evidemment en ecrivant dans la meta a quel numero on en est.

define('_UPGRADE_TIME_OUT', 20);

// http://doc.spip.org/@maj_while
function maj_while($installee, $cible)
{
	include_spip('maj/svn10000');

	$n = 0;
	$time = time();

	while ($installee < $cible) {
		$installee++;
		if (isset($GLOBALS['maj'][$installee])) {
			serie_alter($installee, $GLOBALS['maj'][$installee]);
			$n = time() - $time;
			spip_log("MAJ vers $installee en $n secondes",'maj');
			ecrire_meta('version_installee', $installee,'non');
		} // rien pour SQL
		if ($n >= _UPGRADE_TIME_OUT) {
			redirige_url_ecrire('upgrade', "reinstall=$installee");
		}
	}
	// indispensable pour les chgt de versions qui n'ecrivent pas en base
	// tant pis pour la redondance eventuelle avec ci-dessus
	ecrire_meta('version_installee', $installee,'non');
}

// Appliquer une serie de chgt qui risquent de partir en timeout
// (Alter cree une copie temporaire d'une table, c'est lourd)

// http://doc.spip.org/@serie_alter
function serie_alter($serie, $q = array()) {
	$etape = intval(@$GLOBALS['meta']['upgrade_etape_'.$serie]);
	foreach ($q as $i => $r) {
		if ($i >= $etape) {
			if (is_array($r)
			AND function_exists($f = array_shift($r))) {
				spip_log("$serie/$i: $f " . join(',',$r),'maj');
				ecrire_meta('upgrade_etape_'.$serie, $i+1); // attention on enregistre le meta avant de lancer la fonction, de maniere a eviter de boucler sur timeout
				call_user_func_array($f, $r);
				spip_log("$serie/$i: ok", 'maj');
			} else {
			  echo "maj $serie etape $i incorrecte";
			  exit;
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
			$default = $row['Default']?(" DEFAULT ".sql_quote($row['Default'])):"";
			$notnull = ($row['Null']=='YES')?"":" NOT NULL";
			sql_alter("TABLE $table CHANGE $champ $champ $type $default $notnull");
		}
	}
}

// La fonction a appeler dans le tableau global $maj 
// quand on rajoute des types MIME. cf par exemple la 1.953

// http://doc.spip.org/@upgrade_types_documents
function upgrade_types_documents() {
	include_spip('base/create');
	creer_base_types_doc();
}

// http://doc.spip.org/@upgrade_test
function upgrade_test() {
	sql_drop_table("spip_test", true);
	sql_create("spip_test", array('a' => 'int'));
	sql_alter("TABLE spip_test ADD b INT");
	sql_insertq('spip_test', array('b' => 1), array('field'=>array('b' => 'int')));
	$result = sql_select('b', "spip_test");
	// ne pas garder le resultat de la requete sinon sqlite3 
	// ne peut pas supprimer la table spip_test lors du sql_alter qui suit
	// car cette table serait alors 'verouillee'
	$result = $result?true:false; 
	sql_alter("TABLE spip_test DROP b");
	return $result;
}

// pour versions <= 1.926
// http://doc.spip.org/@maj_version
function maj_version ($version, $test = true) {
	if ($test) {
		if ($version>=1.922)
			ecrire_meta('version_installee', $version, 'non');
		else {
			// on le fait manuellement, car ecrire_meta utilise le champs impt qui est absent sur les vieilles versions
			$GLOBALS['meta']['version_installee'] = $version;
			sql_updateq('spip_meta',  array('valeur' => $version), "nom=" . sql_quote('version_installee') );
		}
		spip_log("mise a jour de la base en $version");
	} else {
		echo _T('alerte_maj_impossible', array('version' => $version));
		exit;
	}
}

// pour versions <= 1.926
// http://doc.spip.org/@upgrade_vers
function upgrade_vers($version, $version_installee, $version_cible = 0){
	return ($version_installee<$version
		AND (($version_cible>=$version) OR ($version_cible==0))
	);
}
?>
