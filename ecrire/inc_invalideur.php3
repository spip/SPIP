<?php

// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INVALIDEUR")) return;
define("_ECRIRE_INVALIDEUR", "1");

include_ecrire('inc_serialbase.php3');

function supprime_invalideurs() {
	spip_query("DELETE FROM spip_caches");
}

//
// Noter dans la base les liens d'invalidation
//
function maj_invalideurs ($fichier, $infosurpage, $delais) {
	$fichier = addslashes($fichier); #parano
	if ($fichier == '') return;	// ne pas noter les POST et les delais=0
	spip_query("DELETE FROM spip_caches WHERE fichier='$fichier'");

	// invalidation des forums
	insere_invalideur($infosurpage['id_forum'],'id_forum', $fichier);

	// invalidation du reste - on peut desactiver dans ecrire/mes_options.php3
	if ($GLOBALS['invalider_caches']) {
		insere_invalideur($infosurpage['id_article'],'id_article', $fichier);

### a activer quand les suivre_invalideurs() seront ajoutes dans l'espace prive
#		insere_invalideur($infosurpage['id_breve'], 'id_breve', $fichier);
#		insere_invalideur($infosurpage['id_rubrique'],'id_rubrique', $fichier);
#		insere_invalideur($infosurpage['id_syndic'],'id_syndic', $fichier);
	}
}

function insere_invalideur($a, $type, $fichier) {
	if (is_array($a)) {
		$values = array();
		foreach($a as $k => $v)
			$values[] = "('$fichier', '$type/$k')";
			$query = "INSERT IGNORE INTO spip_caches" .
		" (fichier, id) VALUES " . join(", ", $values);
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
	if (preg_match(
	"|^CACHE(/[0-9a-f])?(/[0-9]+)?/[^.][\-_\%0-9a-z]+\.[0-9a-f]+(\.gz)?$|i",
	$cache)) {
		@unlink($cache);		// supprimer le fichier
		@unlink($cache.'.NEW');	// et le fichier compagnon s'il existe
	} else
		spip_log("Impossible de retirer $cache");
}

// Supprimer les caches marques "x"
function retire_caches($forcer = false) {
	if ($GLOBALS['flag_ecrire']) return;

	// marque comme fait
	effacer_meta('invalider');
	ecrire_metas();

	// essayer d'eviter de faire le meme travail qu'un autre processus
	// attendre maxi 3 secondes
	spip_get_lock("invalidation", 4);

	// faire le boulot de suppression
	$q = spip_query("SELECT DISTINCT fichier FROM spip_caches WHERE type='x'");
	if ($n = @spip_num_rows($q)) {
		spip_log ("Retire $n caches");
		while (list($cache) = spip_fetch_array($q)) {
			retire_cache($cache);
			$supprimes[] = "'$cache'";
		}
		spip_query("DELETE FROM spip_caches WHERE "
		.calcul_mysql_in('fichier', join(',',$supprimes)) );
	}
}

//
// Invalider les caches lies a telle condition
//
function suivre_invalideur($cond) {
	$result = spip_query("SELECT DISTINCT fichier FROM spip_caches WHERE $cond");
	$tous = array();
	while ($row = spip_fetch_array($result))
		$tous[] = $row['fichier'];

	spip_log("suivre $cond");
	applique_invalideur($tous);
}


//
// Supprimer les vieux caches
//
function retire_vieux_caches() {
	$condition = "type='t' AND id<".time();
	suivre_invalideur($condition);
}


//
// Marquer les fichiers caches invalides comme etant a supprimer
//
function applique_invalideur($depart) {

	if ($depart) {
		$tous = "'".join("', '", $depart)."'";
		spip_log("applique $tous");

		spip_query("UPDATE spip_caches SET type='x'"
		. ' WHERE ' . calcul_mysql_in('fichier', $tous));

		// Si on est dans ecrire/, demander a inc-public.php3
		// de retirer les caches invalide's ; sinon le faire soi-meme
		// ce qui evite des chevauchements dans la validation des forums
		// [ A valide un forum, B obtient de purger les invalides, et A
		//   trouve son cache avant que B n'ait eu le temps de le purger ]
		if ($GLOBALS['flag_ecrire']) {
			ecrire_meta('invalider', 'oui');
			ecrire_metas();
		} else {
			retire_caches();
		}
	}
}

?>
