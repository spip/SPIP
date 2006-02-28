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

include_ecrire('inc_serialbase');

function supprime_invalideurs() {
	spip_query("DELETE FROM spip_caches");
}


// Compilateur : ajouter un invalideur "$type/$valeur" a un code donne
// Attention le type est compile, pas forcement la valeur
function ajouter_invalideur($type, $valeur, $code) {
	return '
	// invalideur '.$type.'
	(!($Cache[\''.$type.'\']['.$valeur."]=1) ? '':\n\t" . $code .")\n";
}


//
// Calcul des pages : noter dans la base les liens d'invalidation
//
function maj_invalideurs ($fichier, &$page, $duree) {
	// ne pas noter les POST et les delais=0
	if ($fichier == '') return;

	// Supprimer les anciens invalideurs
	$f = str_replace('.gz', '', $fichier);
	spip_query("DELETE FROM spip_caches WHERE fichier='$f' OR fichier='$f.gz'");

	// Creer un invalideur 't' nous informant de la date d'expiration
	// et de la taille du fichier cache
	# Note : on ajoute 3600s pour eviter toute concurrence
	# entre un invalideur et un appel public de page
	$bedtime = time() + $duree + 3600;
	$taille = @filesize(_DIR_CACHE . $fichier);
	spip_query("INSERT IGNORE INTO spip_caches (fichier,id,type,taille)
	VALUES ('".addslashes($fichier)."','$bedtime','t','$taille')");

	// invalidations
	insere_invalideur($page['invalideurs'], $fichier);
}

// pour les forums l'invalideur est : 'id_forum/a23'
// pour les petitions et autres, l'invalideur est par exemple :
// 'varia/pet60'
function insere_invalideur($inval, $fichier) {
	foreach ($inval as $type => $a) {
		if (is_array($a)) {
			$values = array();
			foreach($a as $k => $v)
				$values[] = "('$fichier', '$type/$k')";
			spip_query ("INSERT IGNORE INTO spip_caches
				(fichier, id) VALUES " . join(", ", $values));
		}
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

	spip_log("suivre $cond dans " . count($tous) . " caches");
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

		// Demander a inc-public de retirer les caches
		// invalides ;
		// - le signal (meta='invalider') indique
		// qu'il faut faire attention ;
		// - le signal (meta='invalider_caches') indique qu'on
		// peut effacer 100 caches invalides
		// (Signaux differents pour eviter de la concurrence entre
		// les processus d'invalidation)
		ecrire_meta('invalider', 'oui'); // se verifier soi-meme
		ecrire_meta('invalider_caches', 'oui'); // supprimer les autres
		ecrire_metas();
	}
}


// Utilisee pour vider le cache depuis l'espace prive
// (ou juste les squelettes si un changement de config le necessite)
function purger_repertoire($dir, $age='ignore', $regexp = '') {
	$handle = @opendir($dir);
	if (!$handle) return;

	while (($fichier = @readdir($handle)) !== false) {
		// Eviter ".", "..", ".htaccess", etc.
		if ($fichier[0] == '.') continue;
		if ($regexp AND !ereg($regexp, $fichier)) continue;
		$chemin = "$dir/$fichier";
		if (is_file($chemin))
			@unlink($chemin);
		else if (is_dir($chemin))
			if ($fichier != 'CVS')
				purger_repertoire($chemin);
	}
	closedir($handle);
}

// Fonctions pour le cache des images (vues reduites)


function calculer_taille_dossier ($dir) {
	$handle = @opendir($dir);
	if (!$handle) return;

	while (($fichier = @readdir($handle)) !== false) {
		// Eviter ".", "..", ".htaccess", etc.
		if ($fichier[0] == '.') continue;
		if ($regexp AND !ereg($regexp, $fichier)) continue;
		if (is_file("$dir/$fichier")) {
			$taille += filesize("$dir/$fichier");
		}
	}
	closedir($handle);
	return $taille;
}

?>
