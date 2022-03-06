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

use Spip\Admin\Bouton;

/**
 * Ce fichier gère le bandeau supérieur de l'espace privé
 *
 * @package SPIP\Core\Bandeau
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/boutons');

/**
 * Calcule le contexte pour le menu du bandeau
 *
 * La fonction tente de retrouver la rubrique et le secteur d'appartenance
 * à partir du nom du fichier exec, si celui ci correspond à un objet
 * éditorial de SPIP (et qu'il possède ces champs), et dans ce cas,
 * l'ajoute au contexte.
 *
 * @param null|array $contexte
 *     contexte connu.
 *     S'il n'est pas transmis, on prend `$_GET`
 * @return array
 *     contexte
 **/
function definir_barre_contexte($contexte = null) {
	if (is_null($contexte)) {
		$contexte = $_GET;
	} elseif (is_string($contexte)) {
		$contexte = unserialize($contexte);
	}
	if (!isset($contexte['id_rubrique']) and isset($contexte['exec'])) {
		if (!function_exists('trouver_objet_exec')) {
			include_spip('inc/pipelines_ecrire');
		}
		if ($e = trouver_objet_exec($contexte['exec'])) {
			$_id = $e['id_table_objet'];
			if (isset($contexte[$_id]) and $id = intval($contexte[$_id])) {
				$table = $e['table_objet_sql'];
				$row = sql_fetsel('*', $table, "$_id=" . intval($id));
				if (isset($row['id_rubrique'])) {
					$contexte['id_rubrique'] = $row['id_rubrique'];
					if (isset($row['id_secteur'])) {
						$contexte['id_secteur'] = $row['id_secteur'];
					}
				} elseif (isset($row['id_groupe'])) {
					// TODO supprimer ce bloc quand https://core.spip.net/issues/3844 sera réalisé
					$contexte['id_groupe'] = $row['id_groupe'];
				}
			}
		}
	}
	return $contexte;
}

/**
 * Définir la liste des boutons du haut et de ses sous-menus
 *
 * On defini les boutons à mettre selon les droits de l'utilisateur
 * puis on balance le tout au pipeline "ajouter_menus" pour que des plugins
 * puissent y mettre leur grain de sel
 *
 * @param array $contexte
 * @param bool $icones Rechercher les icones
 * @param bool $autorise Ne renvoyer que les boutons autorisés
 * @return array
 */
function definir_barre_boutons($contexte = [], $icones = true, $autorise = true) {
	include_spip('inc/autoriser');
	$boutons_admin = [];

	// les boutons du core, issus de ecrire/paquet.xml
	$liste_boutons = [];

	// ajouter les boutons issus des plugin via paquet.xml
	if (
		function_exists('boutons_plugins')
		and is_array($liste_boutons_plugins = boutons_plugins())
	) {
		$liste_boutons = &$liste_boutons_plugins;
	}

	foreach ($liste_boutons as $id => $infos) {
		$parent = '';
		// les boutons principaux ne sont pas soumis a autorisation
		if (
			!isset($infos['parent'])
			or !($parent = $infos['parent'])
			or !$autorise
			or autoriser('menu', "_$id", 0, null, ['contexte' => $contexte])
		) {
			if (
				$parent
				and $parent = preg_replace(',^bando_,', 'menu_', $parent)
				and isset($boutons_admin[$parent])
			) {
				$position = (isset($infos['position']) and strlen($infos['position'])) ? intval($infos['position']) : count($boutons_admin[$parent]->sousmenu);
				if ($position < 0) {
					$position = count($boutons_admin[$parent]->sousmenu) + 1 + $position;
				}
				$boutons_admin[$parent]->sousmenu = array_slice($boutons_admin[$parent]->sousmenu, 0, $position)
					+ [
						$id => new Bouton(
							($icones and !empty($infos['icone'])) ? find_in_theme($infos['icone']) : '',  // icone
							$infos['titre'],  // titre
							(isset($infos['action']) and $infos['action']) ? $infos['action'] : null,
							(isset($infos['parametres']) and $infos['parametres']) ? $infos['parametres'] : null
						)
					]
					+ array_slice($boutons_admin[$parent]->sousmenu, $position, 100);
			}
			if (
				!$parent
				// provisoire, eviter les vieux boutons
				and (!in_array($id, ['forum', 'statistiques_visites']))
				and (!$autorise or autoriser('menugrandeentree', "_$id", 0, null, ['contexte' => $contexte]))
			) {
				$position = (isset($infos['position']) and $infos['position']) ? $infos['position'] : count($boutons_admin);
				$boutons_admin = array_slice($boutons_admin, 0, $position)
					+ [
						$id => new Bouton(
							($icones and isset($infos['icone']) and $infos['icone']) ? find_in_theme($infos['icone']) : '',  // icone
							$infos['titre'],  // titre
							(isset($infos['action']) and $infos['action']) ? $infos['action'] : null,
							(isset($infos['parametres']) and $infos['parametres']) ? $infos['parametres'] : null
						)
					]
					+ array_slice($boutons_admin, $position, 100);
			}
		}
	}
	$boutons_admin = pipeline('ajouter_menus', $boutons_admin);

	// définir les favoris et positions d’origine
	if ($boutons_admin) {
		$menus_favoris = obtenir_menus_favoris();
		$i = 1;
		foreach ($boutons_admin as $key => $menu) {
			$menu->favori = (int) table_valeur($menus_favoris, $key, false);
			$menu->position = $i++;
			if ($menu->sousmenu) {
				$j = 1;
				foreach ($menu->sousmenu as $key => $bouton) {
					$bouton->favori = (int) table_valeur($menus_favoris, $key, false);
					$bouton->position = $j++;
				}
			}
		}
	}

	return $boutons_admin;
}

/**
 * Trie les entrées des sous menus par ordre alhabétique
 *
 * @param Bouton[] $menus
 * @param bool $avec_favoris
 *     Si true, tri en premier les sous menus favoris, puis l'ordre alphabétique
 * @return Bouton[]
 */
function trier_boutons_enfants_par_alpha($menus, $avec_favoris = false) {
	foreach ($menus as $menu) {
		if ($menu->sousmenu) {
			$libelles = $isfavoris = $favoris = [];
			foreach ($menu->sousmenu as $key => $item) {
				$libelles[$key] = strtolower(translitteration(_T($item->libelle)));
				$isfavoris[$key] = (bool) $item->favori;
				$favoris[$key] = $item->favori;
			}
			if ($avec_favoris) {
				array_multisort($isfavoris, SORT_DESC, $favoris, SORT_ASC, $libelles, SORT_ASC, $menu->sousmenu);
			} else {
				array_multisort($libelles, SORT_ASC, $menu->sousmenu);
			}
		}
	}
	return $menus;
}

/**
 * Trie les entrées des sous menus par favoris (selon leur ordre) puis les autres par ordre alhabétique
 *
 * @uses trier_boutons_enfants_par_alpha()
 * @param Bouton[] $menus
 * @return Bouton[]
 */
function trier_boutons_enfants_par_favoris_alpha($menus) {
	return trier_boutons_enfants_par_alpha($menus, true);
}


/**
 * Créer l'URL à partir de exec et args, sauf si c'est déjà une url formatée
 *
 * @param string $url
 * @param string $args
 * @param array|null $contexte
 * @return string
 */
function bandeau_creer_url($url, $args = '', $contexte = null) {
	if (!preg_match(',[\/\?],', $url)) {
		$url = generer_url_ecrire($url, $args, true);
		// recuperer les parametres du contexte demande par l'url sous la forme
		// &truc=@machin@
		// @machin@ etant remplace par _request('machin')
		$url = str_replace('&amp;', '&', $url);
		while (preg_match(',[&?]([a-z_]+)=@([a-z_]+)@,i', $url, $matches)) {
			if ($matches[2] == 'id_secteur' and !isset($contexte['id_secteur']) and isset($contexte['id_rubrique'])) {
				$contexte['id_secteur'] = sql_getfetsel('id_secteur', 'spip_rubriques', 'id_rubrique=' . intval($contexte['id_rubrique']));
			}
			$val = _request($matches[2], $contexte);
			$url = parametre_url($url, $matches[1], $val ?: '', '&');
		}
		$url = str_replace('&', '&amp;', $url);
	}

	return $url;
}

/**
 * Construire tout le bandeau supérieur de l'espace privé
 *
 * @return string
 *     Code HTML du bandeau
 */
function inc_bandeau_dist() {
	return recuperer_fond('prive/squelettes/inclure/barre-nav', $_GET);
}


/**
 * Retourne la liste des noms d'entrées de menus favoris de l'auteur connecté
 * @return array
 */
function obtenir_menus_favoris() {
	if (
		isset($GLOBALS['visiteur_session']['prefs']['menus_favoris'])
		and is_array($GLOBALS['visiteur_session']['prefs']['menus_favoris'])
		and $GLOBALS['visiteur_session']['prefs']['menus_favoris']
	) {
		return $GLOBALS['visiteur_session']['prefs']['menus_favoris'];
	}
	$definir_menus_favoris = charger_fonction('definir_menus_favoris', 'inc');
	return $definir_menus_favoris();
}
