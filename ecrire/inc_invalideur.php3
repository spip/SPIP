<?php

// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INVALIDEUR")) return;
define("_ECRIRE_INVALIDEUR", "1");

include_ecrire('inc_serialbase.php3');

/*
function supprime_invalideurs_inclus ($cond='') {
	spip_query("DELETE FROM spip_caches_inclus" .
	($cond ? " WHERE $cond" :''));
}
*/

function supprime_invalideurs() {
	spip_query("DELETE FROM spip_caches");
#	supprime_invalideurs_inclus();
}

//
// Noter dans la base les liens d'invalidation
//
function maj_invalideurs ($hache, $infosurpage) {
	$hache = addslashes($hache); #parano
	spip_query("DELETE FROM $table_caches WHERE hache='$hache'");

	// invalidation des forums
	insere_invalideur($infosurpage['id_forum'],'id_forum', $hache);

	// invalidation du reste - on peut desactiver dans ecrire/mes_options.php3
	if ($GLOBALS['invalider_caches']) {
		insere_invalideur($infosurpage['id_article'],'id_article', $hache);

### a activer quand les suivre_invalideurs() seront ajoutes dans l'espace prive
#		insere_invalideur($infosurpage['id_breve'], 'id_breve', $hache);
#		insere_invalideur($infosurpage['id_rubrique'],'id_rubrique', $hache);
#		insere_invalideur($infosurpage['id_syndic'],'id_syndic', $hache);

		# et eventuellement les inclure
	}
}

function insere_invalideur($a, $type, $hache) {
	if ($type == 'inclure') {
		$prefix_id = '';
		$table_caches = 'spip_caches_inclus';
	} else {
		$prefix_id = $type.'/';
		$table_caches = 'spip_caches';
	}

	if (is_array($a)) {
		$values = array();
		foreach($a as $k => $v)
			$values[] = "('$hache', '$prefix_id$k')";
			$query = "INSERT IGNORE INTO $table_caches" .
		" (hache, id) VALUES " . join(", ", $values);
		spip_query($query);
		# spip_log("Dependances $type: " . join(", ", $values));
	}
}

// Regarde dans une table de nom de caches ceux verifiant une condition donnee
// Les retire de cette table et de la table generale des caches
// Si la condition est vide, c'est une simple purge generale


//
// Destruction des fichiers caches invalides
//
// NE PAS appeler ces fonctions depuis l'espace prive ! Elles ont besoin d'avoir
// un acces direct au repertoire CACHE/

// Securite : est sur que c'est un cache
function retire_cache($cache) {
	if ($GLOBALS['flag_ecrire']) return;
	# spip_log("kill $cache ?");
	if (preg_match("|^CACHE(/[0-9a-f])?(/[0-9]+)?/[^.][\-_\%0-9a-z]+\.[0-9a-f]+$|i", $cache))
		@unlink($cache);
}

// Supprimer les caches marques "suppr"
function retire_caches() {
	if ($GLOBALS['flag_ecrire']) return;

	// gerer la concurrence et prendre le travail
	include_ecrire('inc_meta.php3');
	lire_metas();
	if (!lire_meta('invalider')) return;
	effacer_meta('invalider');
	ecrire_metas();

	// faire le boulot de suppression
	foreach (array('spip_caches' /*, 'spip_caches_inclus'*/) as $table_cache) {
		$q = spip_query("SELECT DISTINCT hache FROM $table_cache WHERE suppr='x'");
		if ($n = @spip_num_rows($q)) {
			spip_log ("Retire $n caches");
			while (list($cache) = spip_fetch_array($q)) {
				retire_cache($cache);
				$supprimes[] = "'$cache'";
			}
			spip_query("DELETE FROM $table_cache WHERE "
			.calcul_mysql_in('hache', join(',',$supprimes)) );
		}
	}
}

//
// Supprimer les vieux caches
//

# Teste l'obsolescence d'un cache. 
# Celle de son include le + bref (indiquee ligne 1) serait + juste
# mais lors d'un balayage de repertoire, 
# ouvrir chaque fichier serait couteux, et de gain faible
function retire_cond_cache($arg,$path) {
	return (@filemtime($path) < $arg);
}

function trouve_caches($cond, $arg, $dir) {
	if ($handle = @opendir($dir))
		while (($fichier = readdir($handle)) !== false) {
			if (substr($fichier,0,1)<>'.') {
				$path = "$dir$fichier";
				if ($cond($arg, $path))
					$tous[] = $path;
			}
		}
	return $tous;
}


function retire_vieux_caches($dir) {
	include_ecrire('inc_meta.php3');
	include_ecrire('inc_connect.php3');
	if ($GLOBALS['db_ok']) {
		ecrire_meta('date_purge_cache', time());
		ecrire_metas();

		// Inferer la date de peremption du nom du dir
		// (et si on est a plat, disons 24h)
		// NB: On ajoute 10 minutes pour eviter tout probleme de lock
		if (ereg('CACHE/./([0-9]+)', $dir, $regs))
			$delais = $regs[1] + 600;
		else
			$delais = 24*60*60;

		$tous = trouve_caches('retire_cond_cache', time()-$delais, $dir);
		spip_log("nettoyage de $dir (" . count($tous) . " obsolete(s)");
		if ($tous) {
			applique_invalideur($tous);
		}
	}
}


//
// Invalider les caches lies a telle condition
//
function suivre_invalideur($cond, $table) {
	$result = spip_query("SELECT DISTINCT hache FROM $table WHERE $cond");
	$tous = array();
	while ($row = spip_fetch_array($result))
		$tous[] = $row['hache'];

	spip_log("suivre $cond");
	applique_invalideur($tous);
}

//
// Marquer les fichiers caches invalides comme etant a supprimer,
// et suivre leurs inclusions de maniere recursive
//
function applique_invalideur($depart) {

	if ($depart) {
		$tous = "'".join("', '", $depart)."'";
		spip_log("applique $tous");

/*
		// Invalider les fichiers incluants, de maniere recursive
		$niveau = $tous;
		while ($niveau) {
			// le NOT est theoriquement superflu, mais
			// protege des tables endommagees
			$result = spip_query("SELECT DISTINCT hache FROM spip_caches_inclus" 
			. ' WHERE ' .
			calcul_mysql_in('id', $niveau) . ' AND ' .
			calcul_mysql_in('hache', $tous, 'NOT')
			);
			$niveau = array();
			while ($row = spip_fetch_array($result)) {
				$niveau[] = "'" . $row['hache'] . "'"; 
				$tous .= ", '" . $row['hache'] . "'";
			}
			$niveau = join(', ', $niveau);
		}

		spip_query("UPDATE spip_caches_inclus SET suppr='x'" .
		' WHERE ' . calcul_mysql_in('hache', $tous));
*/

		spip_query("UPDATE spip_caches SET suppr='x'"
		. ' WHERE ' . calcul_mysql_in('hache', $tous));

		// Demander a inc-public.php3 de retirer les caches invalide's
		ecrire_meta('invalider', 'oui');
		ecrire_metas();
	}
}

?>
