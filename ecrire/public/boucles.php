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
 * Ce fichier definit les boucles standard de SPIP
 *
 * @package SPIP\Core\Compilateur\Boucles
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Compile une boucle standard, sans condition rajoutée
 *
 * @param string $id_boucle
 *     Identifiant de la boucle
 * @param array $boucles
 *     AST du squelette
 * @return string
 *     Code PHP compilé de la boucle
 **/
function boucle_DEFAUT_dist($id_boucle, &$boucles) {
	return calculer_boucle($id_boucle, $boucles);
}


/**
 * Compile une boucle récursive
 *
 * `<BOUCLE(BOUCLE)>`
 *
 * @link https://www.spip.net/914
 *
 * @param string $id_boucle
 *     Identifiant de la boucle
 * @param array $boucles
 *     AST du squelette
 * @return string
 *     Code PHP compilé de la boucle
 **/
function boucle_BOUCLE_dist($id_boucle, &$boucles) {
	return calculer_boucle($id_boucle, $boucles);
}


/**
 * Compile une boucle HIERARCHIE
 *
 * La boucle `<BOUCLE(HIERARCHIE)>` retourne la liste des RUBRIQUES
 * qui mènent de la racine du site à la rubrique ou à l’article en cours.
 *
 * Cette boucle (aliasée sur la table RUBRIQUES)
 *
 * - recherche un id_rubrique dans les boucles parentes,
 * - extrait sa hiérarchie, en prenant ou non la rubrique en cours en fonction du critère {tout}
 * - crée une condition WHERE avec ces identifiants ansi qu'une clause ORDER
 * - compile la boucle.
 *
 * Le code compilé calculant la hierarchie est ajouté au tout début de la
 * fonction de boucle et quitte la boucle si aucune rubrique n'est trouvée.
 *
 * @link https://www.spip.net/913
 *
 * @param string $id_boucle
 *     Identifiant de la boucle
 * @param array $boucles
 *     AST du squelette
 * @return string
 *     Code PHP compilé de la boucle
 **/
function boucle_HIERARCHIE_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table . '.id_rubrique';

	// Si la boucle mere est une boucle RUBRIQUES il faut ignorer la feuille
	// sauf en presence du critere {tout} (vu par phraser_html)
	// ou {id_article} qui positionne aussi le {tout}

	$boucle->hierarchie = 'if (!($id_rubrique = intval('
		. calculer_argument_precedent($boucle->id_boucle, 'id_rubrique', $boucles)
		. ")))\n\t\treturn '';\n\t"
		. "include_spip('inc/rubriques');\n\t"
		. '$hierarchie = calcul_hierarchie_in($id_rubrique,'
		. (isset($boucle->modificateur['tout']) ? 'true' : 'false')
		. ");\n\t"
		. 'if (!$hierarchie) return "";' . "\n\t";

	$boucle->where[] = ["'IN'", "'$id_table'", '"($hierarchie)"'];

	$order = "FIELD($id_table, \$hierarchie)";
	if (!isset($boucle->default_order[0]) || $boucle->default_order[0] != ' DESC') {
		$boucle->default_order[] = "\"$order\"";
	} else {
		$boucle->default_order[0] = "\"$order DESC\"";
	}

	return calculer_boucle($id_boucle, $boucles);
}
