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

	// invalidation du reste - on peut desactiver dans _FILE_OPTIONS
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

		// Si on est dans ecrire, demander a inc-public.php3
		// de retirer les caches invalide's ; sinon le faire soi-meme
		// ce qui evite des chevauchements dans la validation des forums
		// [ A valide un forum, B obtient de purger les invalides, et A
		//   trouve son cache avant que B n'ait eu le temps de le purger ]
		if (!_DIR_RESTREINT) {
			ecrire_meta('invalider', 'oui');
			ecrire_metas();
		} else {
			include_local('inc-cache.php3');
			retire_caches();
		}
	}
}

?>
