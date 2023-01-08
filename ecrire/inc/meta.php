<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

/**
 * Gestion des meta de configuration
 *
 * @package SPIP\Core\Configuration
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// Les parametres generaux du site sont dans une table SQL;
// Recopie dans le tableau PHP global meta, car on en a souvent besoin

// duree maximale du cache. Le double pour l'antidater
define('_META_CACHE_TIME', 1 << 24);

function inc_meta_dist($table = 'meta') {
	$new = null;
	// Lire les meta, en cache si present, valide et lisible
	// en cas d'install ne pas faire confiance au meta_cache eventuel
	$cache = cache_meta($table);

	if (
		(!$exec = _request('exec') or !autoriser_sans_cookie($exec))
		and $new = jeune_fichier($cache, _META_CACHE_TIME)
		and lire_fichier_securise($cache, $meta)
		and $meta = @unserialize($meta)
	) {
		$GLOBALS[$table] = $meta;
	}

	if (
		isset($GLOBALS[$table]['touch'])
		and ($GLOBALS[$table]['touch'] < time() - _META_CACHE_TIME)
	) {
		$GLOBALS[$table] = [];
	}
	// sinon lire en base
	if (!$GLOBALS[$table]) {
		$new = !lire_metas($table);
	}

	// renouveller l'alea general si trop vieux ou sur demande explicite
	if (
		(test_espace_prive() || isset($_GET['renouvelle_alea']))
		and $GLOBALS[$table]
		and (time() > _RENOUVELLE_ALEA + ($GLOBALS['meta']['alea_ephemere_date'] ?? 0))
	) {
		// si on n'a pas l'acces en ecriture sur le cache,
		// ne pas renouveller l'alea sinon le cache devient faux
		if (supprimer_fichier($cache)) {
			include_spip('inc/acces');
			renouvelle_alea();
			$new = false;
		} else {
			spip_log("impossible d'ecrire dans " . $cache);
		}
	}
	// et refaire le cache si on a du lire en base
	if (!$new) {
		touch_meta(false, $table);
	}
}

// fonctions aussi appelees a l'install ==> spip_query en premiere requete
// pour eviter l'erreur fatale (serveur non encore configure)

function lire_metas($table = 'meta') {

	if ($result = spip_query("SELECT nom,valeur FROM spip_$table")) {
		include_spip('base/abstract_sql');
		$GLOBALS[$table] = [];
		while ($row = sql_fetch($result)) {
			$GLOBALS[$table][$row['nom']] = $row['valeur'];
		}
		sql_free($result);

		if (
			!isset($GLOBALS[$table]['charset'])
			or !$GLOBALS[$table]['charset']
			or $GLOBALS[$table]['charset'] == '_DEFAULT_CHARSET' // hum, correction d'un bug ayant abime quelques install
		) {
			ecrire_meta('charset', _DEFAULT_CHARSET, null, $table);
		}

		// noter cette table de configuration dans les meta de SPIP
		if ($table !== 'meta') {
			$liste = [];
			if (isset($GLOBALS['meta']['tables_config'])) {
				$liste = unserialize($GLOBALS['meta']['tables_config']);
			}
			if (!$liste) {
				$liste = [];
			}
			if (!in_array($table, $liste)) {
				$liste[] = $table;
				ecrire_meta('tables_config', serialize($liste));
			}
		}
	}

	return $GLOBALS[$table] ?? null;
}


/**
 * Mettre en cache la liste des meta, sauf les valeurs sensibles
 * pour qu'elles ne soient pas visibiles dans un fichier (souvent en 777)
 *
 * @param bool|int $antidate
 *      Date de modification du fichier à appliquer si indiqué (timestamp)
 * @param string $table
 *      Table SQL d'enregistrement des meta.
 **/
function touch_meta($antidate = false, $table = 'meta') {
	$file = cache_meta($table);
	if (!$antidate or !@touch($file, $antidate)) {
		$r = $GLOBALS[$table] ?? [];
		if ($table == 'meta') {
			unset($r['alea_ephemere']);
			unset($r['alea_ephemere_ancien']);
			// le secret du site est utilise pour encoder les contextes ajax que l'on considere fiables
			// mais le sortir deu cache meta implique une requete sql des qu'on a un form dynamique
			// meme si son squelette est en cache
			//unset($r['secret_du_site']);
			if ($antidate) {
				$r['touch'] = $antidate;
			}
		}
		ecrire_fichier_securise($file, serialize($r));
	}
}

/**
 * Supprime une meta
 *
 * @see ecrire_config()
 * @see effacer_config()
 * @see lire_config()
 *
 * @param string $nom
 *     Nom de la meta
 * @param string $table
 *     Table SQL d'enregistrement de la meta.
 **/
function effacer_meta($nom, $table = 'meta') {
	// section critique sur le cache:
	// l'invalider avant et apres la MAJ de la BD
	// c'est un peu moins bien qu'un vrai verrou mais ca suffira
	// et utiliser une statique pour eviter des acces disques a repetition
	static $touch = [];
	$antidate = time() - (_META_CACHE_TIME << 4);
	if (!isset($touch[$table])) {
		touch_meta($antidate, $table);
	}
	sql_delete('spip_' . $table, "nom='$nom'", '', 'continue');
	unset($GLOBALS[$table][$nom]);
	if (!isset($touch[$table])) {
		touch_meta($antidate, $table);
		$touch[$table] = false;
	}
}

/**
 * Met à jour ou crée une meta avec la clé et la valeur indiquée
 *
 * @see ecrire_config()
 * @see effacer_config()
 * @see lire_config()
 *
 * @param string $nom
 *     Nom de la meta
 * @param string $valeur
 *     Valeur à enregistrer
 * @param string|null $importable
 *     Cette meta s'importe-elle avec une restauration de sauvegarde ?
 *     'oui' ou 'non'
 * @param string $table
 *     Table SQL d'enregistrement de la meta.
 **/
function ecrire_meta($nom, $valeur, $importable = null, $table = 'meta') {

	static $touch = [];
	if (!$nom) {
		return;
	}
	include_spip('base/abstract_sql');
	$res = sql_select('*', 'spip_' . $table, 'nom=' . sql_quote($nom), '', '', '', '', '', 'continue');
	// table pas encore installee, travailler en php seulement
	if (!$res) {
		$GLOBALS[$table][$nom] = $valeur;

		return;
	}
	$row = sql_fetch($res);
	sql_free($res);

	// ne pas invalider le cache si affectation a l'identique
	// (tant pis si impt aurait du changer)
	if (
		$row and $valeur == $row['valeur']
		and isset($GLOBALS[$table][$nom])
		and $GLOBALS[$table][$nom] == $valeur
	) {
		return;
	}

	$GLOBALS[$table][$nom] = $valeur;
	// cf effacer pour comprendre le double touch
	$antidate = time() - (_META_CACHE_TIME << 1);
	if (!isset($touch[$table])) {
		touch_meta($antidate, $table);
	}
	$r = ['nom' => sql_quote($nom, '', 'text'), 'valeur' => sql_quote($valeur, '', 'text')];
	// Gaffe aux tables sans impt (vieilles versions de SPIP notamment)
	// ici on utilise pas sql_updateq et sql_insertq pour ne pas provoquer trop tot
	// de lecture des descriptions des tables
	if ($importable and isset($row['impt'])) {
		$r['impt'] = sql_quote($importable, '', 'text');
	}
	if ($row) {
		sql_update('spip_' . $table, $r, 'nom=' . sql_quote($nom));
	} else {
		sql_insert('spip_' . $table, '(' . join(',', array_keys($r)) . ')', '(' . join(',', array_values($r)) . ')');
	}
	if (!isset($touch[$table])) {
		touch_meta($antidate, $table);
		$touch[$table] = false;
	}
}

/**
 * Retourne le nom du fichier cache d'une table SQL de meta
 *
 * @param string $table
 *     Table SQL d'enregistrement des meta.
 * @return string
 *     Nom du fichier cache
 **/
function cache_meta($table = 'meta') {
	return ($table == 'meta') ? _FILE_META : (_DIR_CACHE . $table . '.php');
}

/**
 * Installer une table de configuration supplementaire
 *
 * @param string $table
 */
function installer_table_meta($table) {
	$trouver_table = charger_fonction('trouver_table', 'base');
	if (!$trouver_table("spip_$table")) {
		include_spip('base/auxiliaires');
		include_spip('base/create');
		creer_ou_upgrader_table("spip_$table", $GLOBALS['tables_auxiliaires']['spip_meta'], false, false);
		$trouver_table('');
	}
	lire_metas($table);
}

/**
 * Supprimer une table de configuration supplémentaire
 *
 * Si $force=true, on ne verifie pas qu'elle est bien vide
 *
 * @param string $table
 * @param bool $force
 */
function supprimer_table_meta($table, $force = false) {
	if ($table !== 'meta') {
		// Vérifier le contenu restant de la table
		$nb_variables = sql_countsel("spip_$table");

		// Supprimer si :
		// - la table est vide
		// - ou limitée à la variable charset
		// - ou qu'on force la suppression
		if (
			$force
			or !$nb_variables
			or (
				($nb_variables == 1)
				and isset($GLOBALS[$table]['charset'])
			)
		) {
			// Supprimer la table des globaleset de la base
			unset($GLOBALS[$table]);
			sql_drop_table("spip_$table");
			// Supprimer le fichier cache
			include_spip('inc/flock');
			$cache = cache_meta($table);
			supprimer_fichier($cache);

			// vider le cache des tables
			$trouver_table = charger_fonction('trouver_table', 'base');
			$trouver_table('');

			// Supprimer la table de la liste des tables de configuration autres que spip_meta
			if (isset($GLOBALS['meta']['tables_config'])) {
				$liste = unserialize($GLOBALS['meta']['tables_config']);
				$cle = array_search($table, $liste);
				if ($cle !== false) {
					unset($liste[$cle]);
					if ($liste) {
						ecrire_meta('tables_config', serialize($liste));
					} else {
						effacer_meta('tables_config');
					}
				}
			}
		}
	}
}
