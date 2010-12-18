<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

// Les parametres generaux du site sont dans une table SQL;
// Recopie dans le tableau PHP global meta, car on en a souvent besoin

// duree maximale du cache. Le double pour l'antidater
define('_META_CACHE_TIME', 1<<24);

// http://doc.spip.org/@inc_meta_dist
function inc_meta_dist($table='meta')
{
	// Lire les meta, en cache si present, valide et lisible
	// en cas d'install ne pas faire confiance au meta_cache eventuel
	$cache = cache_meta($table);

	if ((_request('exec')!=='install' OR !test_espace_prive())
	AND $new = jeune_fichier($cache, _META_CACHE_TIME)
	AND lire_fichier_securise($cache, $meta)
	AND $meta = @unserialize($meta))
		$GLOBALS[$table] = $meta;

	if (isset($GLOBALS[$table]['touch']) 
	AND ($GLOBALS[$table]['touch']<time()-_META_CACHE_TIME))
		$GLOBALS[$table] = array();
	// sinon lire en base
	if (!$GLOBALS[$table]) $new = !lire_metas($table);

	// renouveller l'alea general si trop vieux ou sur demande explicite
	if ((test_espace_prive() || isset($_GET['renouvelle_alea']))
	AND $GLOBALS[$table]
	AND (time() > _RENOUVELLE_ALEA + $GLOBALS['meta']['alea_ephemere_date'])) {
		// si on n'a pas l'acces en ecriture sur le cache,
		// ne pas renouveller l'alea sinon le cache devient faux
		if (supprimer_fichier($cache)) {
			include_spip('inc/acces');
			renouvelle_alea();
			$new = false; 
		} else spip_log("impossible d'ecrire dans " . $cache);
	}
	// et refaire le cache si on a du lire en base
	if (!$new) touch_meta(false, $table);
}

// fonctions aussi appelees a l'install ==> spip_query en premiere requete 
// pour eviter l'erreur fatale (serveur non encore configure)

// http://doc.spip.org/@lire_metas
function lire_metas($table='meta') {

	if ($result = spip_query("SELECT nom,valeur FROM spip_$table")) {
		include_spip('base/abstract_sql');
		$GLOBALS[$table] = array();
		while ($row = sql_fetch($result))
			$GLOBALS[$table][$row['nom']] = $row['valeur'];
        sql_free($result);

		if (!$GLOBALS[$table]['charset']
		  OR $GLOBALS[$table]['charset']=='_DEFAULT_CHARSET' // hum, correction d'un bug ayant abime quelques install
		)
			ecrire_meta('charset', _DEFAULT_CHARSET, NULL, $table);
	}
	return $GLOBALS[$table];
}

// Mettre en cache la liste des meta, sauf les valeurs sensibles 
// pour qu'elles ne soient pas visibiles dans un fichier.souvent en 777
// http://doc.spip.org/@touch_meta
function touch_meta($antidate= false, $table='meta'){
	$file = cache_meta($table);
	if (!$antidate OR !@touch($file, $antidate)) {
		$r = $GLOBALS[$table];
		unset($r['alea_ephemere']);
		unset($r['alea_ephemere_ancien']);
		// le secret du site est utilise pour encoder les contextes ajax que l'on considere fiables
		// mais le sortir deu cache meta implique une requete sql des qu'on a un form dynamique
		// meme si son squelette est en cache
		//unset($r['secret_du_site']);
		if ($antidate) $r['touch']= $antidate;
		ecrire_fichier_securise($file, serialize($r));
	}
}

// http://doc.spip.org/@effacer_meta
function effacer_meta($nom, $table='meta') {
	// section critique sur le cache:
	// l'invalider avant et apres la MAJ de la BD
	// c'est un peu moins bien qu'un vrai verrou mais ca suffira
	// et utiliser une statique pour eviter des acces disques a repetition
	static $touch = array();
	$antidate = time() - (_META_CACHE_TIME<<4);
	if (!isset($touch[$table])) {touch_meta($antidate, $table);}
	sql_delete('spip_' . $table, "nom='$nom'");
	unset($GLOBALS[$table][$nom]);
	if (!isset($touch[$table])) {touch_meta($antidate, $table); $touch[$table] = false;}
}

// http://doc.spip.org/@ecrire_meta
function ecrire_meta($nom, $valeur, $importable = NULL, $table='meta') {

	static $touch = array();
	if (!$nom) return;
	include_spip('base/abstract_sql');
	$res = sql_select("*",'spip_' . $table,"nom=" . sql_quote($nom),'','','','','','continue');
	// table pas encore installee, travailler en php seulement
	if (!$res) {
		$GLOBALS[$table][$nom] = $valeur;
		return;
	}
	$row = sql_fetch($res);
    sql_free($res);

	// ne pas invalider le cache si affectation a l'identique
	// (tant pis si impt aurait du changer)
	if ($row AND $valeur == $row['valeur'] AND $GLOBALS[$table][$nom] == $valeur) return;

	$GLOBALS[$table][$nom] = $valeur;
	// cf effacer pour comprendre le double touch
	$antidate = time() - (_META_CACHE_TIME<<1);
	if (!isset($touch[$table])) {touch_meta($antidate, $table);}
	$r = array('nom' => $nom, 'valeur' => $valeur);
	// Gaffe aux tables sans impt (vieilles versions de SPIP notamment)
	if ($importable AND isset($row['impt'])) $r['impt'] = $importable;
	if ($row) {
		sql_updateq('spip_' . $table, $r,"nom=" . sql_quote($nom));
	} else {
		sql_insertq('spip_' . $table, $r);
	}
	if (!isset($touch[$table])) {touch_meta($antidate, $table); $touch[$table] = false;}
}

function cache_meta($table='meta')
{
	return ($table=='meta') ? _FILE_META : (_DIR_CACHE . $table . '.php');
}
?>
