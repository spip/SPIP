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
 * Gestion des drapeaux d'édition
 *
 * Drapeau d'edition : on regarde qui a ouvert quel objet éditorial en
 * édition, et on le signale aux autres redacteurs pour éviter de se marcher
 * sur les pieds
 *
 * Le format est une meta drapeau_edition qui contient un tableau sérialisé
 * `type_objet => (id_objet => (id_auteur => (nom_auteur => (date_modif))))`
 *
 * À chaque mise à jour de ce tableau on oublie les enregistrements datant
 * de plus d'une heure
 *
 * Attention ce n'est pas un verrou "bloquant", juste un drapeau qui signale
 * que l'on bosse sur cet objet editorial ; les autres peuvent passer outre
 * (en cas de communication orale c'est plus pratique)
 *
 * @package SPIP\Core\Drapeaux\Edition
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Retourne le tableau des éléments édités en cours après avoir supprimé
 * les éléments trop vieux (de plus d'une heure) du tableau.
 *
 * @uses ecrire_tableau_edition()
 *
 * @return array
 *     Tableau des éléments édités actuellement, par objet et auteur, du type :
 *     `[ type d'objet ][id_objet][id_auteur][nom de l'auteur] = time()`
 **/
function lire_tableau_edition() {
	$edition = @unserialize($GLOBALS['meta']['drapeau_edition']);
	if (!$edition) {
		return [];
	}
	$changed = false;

	$bon_pour_le_service = time() - 3600;
	// parcourir le tableau et virer les vieux
	foreach ($edition as $objet => $data) {
		if (!is_array($data)) {
			unset($edition[$objet]);
		} // vieille version
		else {
			foreach ($data as $id => $tab) {
				if (!is_array($tab)) {
					unset($edition[$objet][$tab]);
				} // vieille version
				else {
					foreach ($tab as $n => $duo) {
						if (current($duo) < $bon_pour_le_service) {
							unset($edition[$objet][$id][$n]);
							$changed = true;
						}
					}
				}
				if (!$edition[$objet][$id]) {
					unset($edition[$objet][$id]);
				}
			}
		}
		if (!$edition[$objet]) {
			unset($edition[$objet]);
		}
	}

	if ($changed) {
		ecrire_tableau_edition($edition);
	}

	return $edition;
}

/**
 * Enregistre la liste des éléments édités
 *
 * @uses ecrire_meta()
 *
 * @param array $edition
 *     Tableau des éléments édités actuellement, par objet et auteur, du type :
 *     `[ type d'objet ][id_objet][id_auteur][nom de l'auteur] = time()`
 **/
function ecrire_tableau_edition($edition) {
	ecrire_meta('drapeau_edition', serialize($edition));
}

/**
 * Signale qu'un auteur édite tel objet
 *
 * Si l'objet est non éditable dans l'espace privé, ne pas retenir le signalement
 * qui correspond à un process unique.
 *
 * @see lire_tableau_edition()
 * @see ecrire_tableau_edition()
 *
 * @param int $id
 *     Identifiant de l'objet
 * @param array $auteur
 *     Session de l'auteur
 * @param string $type
 *     Type d'objet édité
 */
function signale_edition($id, $auteur, $type = 'article') {
	include_spip('base/objets');
	include_spip('inc/filtres');
	if (objet_info($type, 'editable') !== 'oui') {
		return;
	}

	$edition = lire_tableau_edition();
	if (isset($auteur['id_auteur']) and ($id_a = $auteur['id_auteur']) and isset($auteur['nom'])) {
		$nom = $auteur['nom'];
	} else {
		$nom = $id_a = $GLOBALS['ip'];
	}

	if (!isset($edition[$type][$id]) or !is_array($edition[$type][$id])) {
		$edition[$type][$id] = [];
	}
	$edition[$type][$id][$id_a][$nom] = time();
	ecrire_tableau_edition($edition);
}

/**
 * Qui édite mon objet ?
 *
 * @see lire_tableau_edition()
 *
 * @param integer $id
 *     Identifiant de l'objet
 * @param string $type
 *     Type de l'objet
 * @return array
 *     Tableau sous la forme `["id_auteur"]["nom de l'auteur"] = time()`
 */
function qui_edite($id, $type = 'article') {

	$edition = lire_tableau_edition();

	return empty($edition[$type][$id]) ? [] : $edition[$type][$id];
}

/**
 * Afficher les auteurs ayant édités récemment l'objet.
 *
 * @param integer $id
 *     Identifiant de l'objet
 * @param string $type
 *     Type de l'objet
 * @return array<string, string>
 *     Liste de tableaux `['nom_auteur_modif' => x|y|z, 'date_diff' => n]`
 */
function mention_qui_edite($id, $type = 'article'): array {
	$modif = qui_edite($id, $type);
	unset($modif[$GLOBALS['visiteur_session']['id_auteur']]);

	if ($modif) {
		$quand = 0;
		foreach ($modif as $duo) {
			$auteurs[] = typo(key($duo));
			$quand = max($quand, current($duo));
		}

		// format lie a la chaine de langue 'avis_article_modifie'
		return [
			'nom_auteur_modif' => join(' | ', $auteurs),
			'date_diff' => ceil((time() - $quand) / 60)
		];
	}
	return [];
}

/**
 * Quels sont les objets en cours d'édition par `$id_auteur` ?
 *
 * @uses lire_tableau_edition()
 *
 * @param int $id_auteur
 *     Identifiant de l'auteur
 * @return array
 *     Liste de tableaux `['objet' => x, 'id_objet' => n]`
 */
function liste_drapeau_edition($id_auteur) {
	$edition = lire_tableau_edition();
	$objets_ouverts = [];

	foreach ($edition as $objet => $data) {
		foreach ($data as $id => $auteurs) {
			if (
				isset($auteurs[$id_auteur])
				and is_array($auteurs[$id_auteur]) // precaution
				and (array_pop($auteurs[$id_auteur]) > time() - 3600)
			) {
				$objets_ouverts[] = [
					'objet' => $objet,
					'id_objet' => $id,
				];
			}
		}
	}

	return $objets_ouverts;
}

/**
 * Quand l'auteur veut libérer tous ses objets (tous types)
 *
 * @uses lire_tableau_edition()
 * @uses ecrire_tableau_edition()
 *
 * @param integer $id_auteur
 * @return void
 */
function debloquer_tous($id_auteur) {
	$edition = lire_tableau_edition();
	foreach ($edition as $objet => $data) {
		foreach ($data as $id => $auteurs) {
			if (isset($auteurs[$id_auteur])) {
				unset($edition[$objet][$id][$id_auteur]);
				ecrire_tableau_edition($edition);
			}
		}
	}
}

/**
 * Quand l'auteur libère un objet précis
 *
 * @uses lire_tableau_edition()
 * @uses ecrire_tableau_edition()
 *
 * @param integer $id_auteur
 *     Identifiant de l'auteur
 * @param integer $id_objet
 *     Identifiant de l'objet édité
 * @param string $type
 *     Type de l'objet
 * @return void
 */
function debloquer_edition($id_auteur, $id_objet, $type = 'article') {
	$edition = lire_tableau_edition();

	foreach ($edition as $objet => $data) {
		if ($objet == $type) {
			foreach ($data as $id => $auteurs) {
				if (
					$id == $id_objet
					and isset($auteurs[$id_auteur])
				) {
					unset($edition[$objet][$id][$id_auteur]);
					ecrire_tableau_edition($edition);
				}
			}
		}
	}
}
