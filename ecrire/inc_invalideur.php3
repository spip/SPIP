<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined("_ECRIRE_INC_VERSION")) return;

include_ecrire('inc_serialbase.php3');

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
function maj_invalideurs ($fichier, $invalideurs, $delais) {
	$fichier = addslashes($fichier); #parano
	if ($fichier == '') return;	// ne pas noter les POST et les delais=0
	spip_query("DELETE FROM spip_caches WHERE fichier='$fichier'");

	// invalidation des forums (l'invalideur est : 'id_forum/a23')
	insere_invalideur($invalideurs['id_forum'], 'id_forum', $fichier);

	// invalidation des petitions et autres
	// (l'invalideur est par exemple : 'varia/petition')
	insere_invalideur($invalideurs['varia'], 'varia', $fichier);

	// invalidation du reste - experimental a activer dans mes_options
	if ($GLOBALS['invalider_caches']) {
		insere_invalideur($invalideurs['id_article'],'id_article', $fichier);
		insere_invalideur($invalideurs['id_breve'], 'id_breve', $fichier);
		insere_invalideur($invalideurs['id_rubrique'],'id_rubrique', $fichier);
		insere_invalideur($invalideurs['id_syndic'],'id_syndic', $fichier);
	}
}

function insere_invalideur($a, $type, $fichier) {
	if (is_array($a)) {
		$values = array();
		foreach($a as $k => $v)
			$values[] = "('$fichier', '$type/$k')";
		spip_query ("INSERT IGNORE INTO spip_caches
			(fichier, id) VALUES " . join(", ", $values));
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

		// Demander a inc-public.php3 de retirer les caches
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
		if (_DIR_RESTREINT) {
			include_local('inc-cache.php3');
			retire_caches();
		}
	}
}

?>
