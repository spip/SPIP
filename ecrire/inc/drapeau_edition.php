<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

// Drapeau d'edition : on regarde qui a ouvert quel objet editorial en 
// edition, et on le signale aux autres redacteurs pour eviter de se marcher 
// sur les pieds

// Le format est une meta drapeau_edition qui contient un tableau
// serialise
// type_objet => (id_objet => (id_auteur => (nom_auteur => (date_modif))))

// a chaque mise a jour de ce tableau on oublie les enregistrements datant
// de plus d'une heure

// Attention ce n'est pas un verrou "bloquant", juste un drapeau qui signale
// que l'on bosse sur cet objet editorial ; les autres peuvent passer outre
// (en cas de communication orale c'est plus pratique)


// http://doc.spip.org/@lire_tableau_edition
function lire_tableau_edition () {
	$edition = @unserialize($GLOBALS['meta']['drapeau_edition']);
	if (!$edition) return array();
	$changed = false;

	$bon_pour_le_service = time()-3600;
	// parcourir le tableau et virer les vieux
	foreach ($edition as $objet => $data) {
		if (!is_array($data))
			unset ($edition[$objet]); // vieille version
		else foreach ($data as $id => $tab) {
			if (!is_array($tab))
			  unset ($edition[$objet][$tab]); // vieille version
			else foreach ($tab as $n => $duo) {
				if (current($duo) < $bon_pour_le_service) {
					unset($edition[$objet][$id][$n]);
					$changed = true;
				}
			}
			if (!$edition[$objet][$id])
				unset($edition[$objet][$id]);
		}
		if (!$edition[$objet])
			unset($edition[$objet]);
	}

	if ($changed)
		ecrire_tableau_edition($edition);

	return $edition;
}

// http://doc.spip.org/@ecrire_tableau_edition
function ecrire_tableau_edition($edition) {
	ecrire_meta('drapeau_edition', serialize($edition));
}

/**
 * J'edite tel objet
 * si l'objet est non editable dans l'espace prive, ne pas retenir le signalement
 * qui correspond a un process unique
 *
 * http://doc.spip.org/@signale_edition
 *
 * @param int $id
 * @param $auteur
 * @param string $type
 * @return mixed
 */
function signale_edition ($id, $auteur, $type='article') {
	include_spip('base/objets');
	include_spip('inc/filtres');
	if (objet_info($type,'editable')!=='oui')
		return;

	$edition = lire_tableau_edition();
	if (isset($auteur['id_auteur']) and $id_a = $auteur['id_auteur'])
		$nom = $auteur['nom'];
	else
		$nom = $id_a = $GLOBALS['ip'];
	if (!is_array($edition[$type][$id]))
		$edition[$type][$id] = array();
	$edition[$type][$id][$id_a][$nom] = time();
	ecrire_tableau_edition($edition);
}

// Qui edite mon objet ?
// http://doc.spip.org/@qui_edite
function qui_edite ($id, $type='article') {

	$edition = lire_tableau_edition();

	return $edition ? $edition[$type][$id] : array();
}

// http://doc.spip.org/@mention_qui_edite
function mention_qui_edite ($id, $type='article') {
	$modif = qui_edite($id, $type);
	unset($modif[$GLOBALS['visiteur_session']['id_auteur']]);

	if ($modif) {
		$quand = 0;
		foreach ($modif as $duo) {
			$auteurs[] = typo(key($duo));
			$quand = max($quand, current($duo));
		}
		// format lie a la chaine de langue 'avis_article_modifie'
		return array(
			'nom_auteur_modif' => join(' | ', $auteurs),
			'date_diff' => ceil((time()-$quand) / 60)
		);
	}
}

/**
 * Quels sont les objets en cours d'edition par X ?
 *
 * http://doc.spip.org/@liste_drapeau_edition
 *
 * @param $id_auteur
 * @return array
 */
function liste_drapeau_edition ($id_auteur) {
	$edition = lire_tableau_edition();
	$objets_ouverts = array();

	foreach ($edition as $objet => $data)
		foreach ($data as $id => $auteurs)
		{
			if (isset($auteurs[$id_auteur])
			AND is_array($auteurs[$id_auteur]) // precaution
			AND (array_pop($auteurs[$id_auteur]) > time()-3600)) {
				$objets_ouverts[] = array(
					'objet'=>$objet,
					'id_objet' => $id,
				);
			}
		}
	return $objets_ouverts;
}

// Quand l'auteur veut liberer tous ses objets (tous types)
// http://doc.spip.org/@debloquer_tous
function debloquer_tous($id_auteur) {
	$edition = lire_tableau_edition();
	foreach ($edition as $objet => $data) {
		foreach ($data as $id => $auteurs)
		{
			if (isset($auteurs[$id_auteur])) {
				unset ($edition[$objet][$id][$id_auteur]);
				ecrire_tableau_edition($edition);
			}
		}
	}
}

// quand l'auteur libere un objet precis
// http://doc.spip.org/@debloquer_edition
function debloquer_edition($id_auteur, $id_objet, $type='article') {
	$edition = lire_tableau_edition();

	foreach ($edition as $objet => $data){
		if ($objet == $type) {
			foreach ($data as $id => $auteurs)
			{
				if ($id == $id_objet
				AND isset($auteurs[$id_auteur])) {
					unset ($edition[$objet][$id][$id_auteur]);
					ecrire_tableau_edition($edition);
				}
			}
		}
	}
}
?>
