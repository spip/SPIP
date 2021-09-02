<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Gestion de la sélection d'un squelette depuis son nom parmi les
 * chemins connus de SPIP
 *
 * Recherche par exemple `contenu\xx` et en absence utilisera `contenu\dist`
 *
 * @package SPIP\Core\Public\Styliser
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// Ce fichier doit imperativement definir la fonction ci-dessous:

/**
 * Déterminer le squelette qui sera utilisé pour rendre la page ou le bloc
 * à partir de `$fond` et du `$contetxe`
 *
 * Actuellement tous les squelettes se terminent par `.html`
 * pour des raisons historiques, ce qui est trompeur
 *
 * @param string $fond
 * @param array $contexte
 * @param string $lang
 * @param string $connect
 * @return array
 */
function public_styliser_dist($fond, $contexte, $lang = '', $connect = '') {
	static $styliser_par_z;

	// s'assurer que le fond est licite
	// car il peut etre construit a partir d'une variable d'environnement
	if (strpos($fond, '../') !== false or strncmp($fond, '/', 1) == 0) {
		$fond = '404';
	}

	if (strncmp($fond, 'modeles/', 8) == 0) {
		$modele = substr($fond, 8);
		$modele = styliser_modele($modele, null, $contexte);
		$fond = "modeles/$modele";
	}

	// Choisir entre $fond-dist.html, $fond=7.html, etc?
	$id_rubrique = 0;
	// Chercher le fond qui va servir de squelette
	if ($r = quete_rubrique_fond($contexte)) {
		list($id_rubrique, $lang) = $r;
	}

	// trouver un squelette du nom demande
	// ne rien dire si on ne trouve pas,
	// c'est l'appelant qui sait comment gerer la situation
	// ou les plugins qui feront mieux dans le pipeline
	$squelette = trouver_fond($fond, '', true);
	$ext = $squelette['extension'];

	$flux = [
		'args' => [
			'id_rubrique' => $id_rubrique,
			'ext' => $ext,
			'fond' => $fond,
			'lang' => $lang,
			'contexte' => $contexte, // le style d'un objet peut dependre de lui meme
			'connect' => $connect
		],
		'data' => $squelette['fond'],
	];

	if (test_espace_prive() or defined('_ZPIP')) {
		if (!$styliser_par_z) {
			$styliser_par_z = charger_fonction('styliser_par_z', 'public');
		}
		$flux = $styliser_par_z($flux);
	}

	$flux = styliser_par_objets($flux);

	// pipeline styliser
	$squelette = pipeline('styliser', $flux);

	return [$squelette, $ext, $ext, "$squelette.$ext"];
}

/**
 * Cherche à échafauder un squelette générique pour un objet éditorial si
 * aucun squelette approprié n'a été trouvé
 *
 * Échaffaude seulement pour des appels à `prive/objets/liste/` ou
 * `prive/objets/contenu/` pour lesquels aucun squelette n'a été trouvé,
 * et uniquement si l'on est dans l'espace privé.
 *
 * @see prive_echafauder_dist()
 *
 * @param array $flux
 *     Données du pipeline styliser
 * @return array
 *     Données du pipeline styliser
 **/
function styliser_par_objets($flux) {
	if (
		test_espace_prive()
		and !$squelette = $flux['data']
		and strncmp($flux['args']['fond'], 'prive/objets/', 13) == 0
		and $echafauder = charger_fonction('echafauder', 'prive', true)
	) {
		if (strncmp($flux['args']['fond'], 'prive/objets/liste/', 19) == 0) {
			$table = table_objet(substr($flux['args']['fond'], 19));
			$table_sql = table_objet_sql($table);
			$objets = lister_tables_objets_sql();
			if (isset($objets[$table_sql])) {
				$flux['data'] = $echafauder($table, $table, $table_sql, 'prive/objets/liste/objets', $flux['args']['ext']);
			}
		}
		if (strncmp($flux['args']['fond'], 'prive/objets/contenu/', 21) == 0) {
			$type = substr($flux['args']['fond'], 21);
			$table = table_objet($type);
			$table_sql = table_objet_sql($table);
			$objets = lister_tables_objets_sql();
			if (isset($objets[$table_sql])) {
				$flux['data'] = $echafauder($type, $table, $table_sql, 'prive/objets/contenu/objet', $flux['args']['ext']);
			}
		}
	}

	return $flux;
}

/**
 * Calcul de la rubrique associée à la requête
 * (sélection de squelette spécifique par id_rubrique & lang)
 *
 * Êttention, on repète cela à chaque inclusion,
 * on optimise donc pour ne faire la recherche qu'une fois
 * par contexte semblable du point de vue des id_xx
 *
 * @staticvar array $liste_objets
 * @param array $contexte
 * @return array
 */
function quete_rubrique_fond($contexte) {
	static $liste_objets = null;
	static $quete = [];
	if (is_null($liste_objets)) {
		$liste_objets = [];
		include_spip('inc/urls');
		include_spip('public/quete');
		$l = urls_liste_objets(false);
		// placer la rubrique en tete des objets
		$l = array_diff($l, ['rubrique']);
		array_unshift($l, 'rubrique');
		foreach ($l as $objet) {
			$id = id_table_objet($objet);
			if (!isset($liste_objets[$id])) {
				$liste_objets[$id] = objet_type($objet, false);
			}
		}
	}
	$c = array_intersect_key($contexte, $liste_objets);
	if (!count($c)) {
		return false;
	}

	$c = array_map('intval', $c);
	$s = serialize($c);
	if (isset($quete[$s])) {
		return $quete[$s];
	}

	if (isset($c['id_rubrique']) and $r = $c['id_rubrique']) {
		unset($c['id_rubrique']);
		$c = ['id_rubrique' => $r] + $c;
	}

	foreach ($c as $_id => $id) {
		if (
			$id
			and $row = quete_parent_lang(table_objet_sql($liste_objets[$_id]), $id)
		) {
			$lang = isset($row['lang']) ? $row['lang'] : '';
			if ($_id == 'id_rubrique' or (isset($row['id_rubrique']) and $id = $row['id_rubrique'])) {
				return $quete[$s] = [$id, $lang];
			}
		}
	}

	return $quete[$s] = false;
}
