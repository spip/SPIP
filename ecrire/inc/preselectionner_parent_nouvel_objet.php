<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}
if (!defined('_AUTO_SELECTION_RUBRIQUE')) {
	define('_AUTO_SELECTION_RUBRIQUE', false);
}


/**
 * Preselectionner la rubrique lors de la creation
 * desactive par defaut suite a remontee utilisateur mais activable par define
 * ou surchargeable
 *
 * @param string $objet
 * @param array $row
 * @return string
 */
function inc_preselectionner_parent_nouvel_objet_dist($objet, $row) {
	if (!_AUTO_SELECTION_RUBRIQUE) {
		return '';
	}

	if (!isset($row['id_rubrique'])) {
		return '';
	}

	$id_rubrique = '';
	if ($GLOBALS['connect_id_rubrique']) {
		// si admin restreint : sa rubrique
		$id_rubrique = $GLOBALS['connect_id_rubrique'][0];
	} elseif (
		is_int(_AUTO_SELECTION_RUBRIQUE)
		&& sql_fetsel('id_rubrique', 'spip_rubriques', 'id_rubrique=' . (int) _AUTO_SELECTION_RUBRIQUE)
	) {
		$id_rubrique = _AUTO_SELECTION_RUBRIQUE;
	} else {
		// sinon la derniere rubrique cree
		$row_rub = sql_fetsel('id_rubrique', 'spip_rubriques', '', '', 'id_rubrique DESC', '0,1');
		$id_rubrique = $row_rub['id_rubrique'];
	}
	// si le choix ne convient pas, on cherche dans un secteur
	if (!autoriser('creer' . $objet . 'dans', 'rubrique', $id_rubrique)) {
		$id_rubrique = '';
		// manque de chance, la rubrique n'est pas autorisee, on cherche un des secteurs autorises
		$res = sql_select('id_rubrique', 'spip_rubriques', 'id_parent=0');
		while (!$id_rubrique && ($row_rub = sql_fetch($res))) {
			if (autoriser('creer' . $objet . 'dans', 'rubrique', $row_rub['id_rubrique'])) {
				$id_rubrique = $row_rub['id_rubrique'];
			}
		}
	}

	return $id_rubrique;
}
