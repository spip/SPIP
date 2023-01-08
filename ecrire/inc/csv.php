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
 * Analyse de fichiers CSV
 *
 * @package SPIP\Core\CSV
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Retourne les données d'un texte au format CSV
 *
 * @param string $t
 *     Contenu du CSV
 * @return array
 *     Tableau des données en 3 index :
 *     - Liste des noms des colonnes
 *     - Liste des valeurs de chaque ligne et chaque colonne
 *     - Titre du tableau (si une seule colonne)
 **/
function analyse_csv($t) {

	// Quel est le séparateur ?
	$virg = substr_count($t, ',');
	$pvirg = substr_count($t, ';');
	$tab = substr_count($t, "\t");
	if ($virg > $pvirg) {
		$sep = ',';
		$hs = '&#44;';
	} else {
		$sep = ';';
		$hs = '&#59;';
		$virg = $pvirg;
	}
	// un certain nombre de tab => le séparateur est tab
	if ($tab > $virg / 10) {
		$sep = "\t";
		$hs = "\t";
	}

	// un separateur suivi de 3 guillemets attention !
	// attention au ; ou , suceptible d'etre confondu avec un separateur
	// on substitue un # et on remplacera a la fin
	$t = preg_replace("/([\n$sep])\"\"\"/", '\\1"&#34#', $t);
	$t = str_replace('""', '&#34#', $t);
	preg_match_all('/"[^"]*"/', $t, $r);
	foreach ($r[0] as $cell) {
		$t = str_replace(
			$cell,
			str_replace(
				$sep,
				$hs,
				str_replace(
					"\n",
					'``**``', // échapper les saut de lignes, on les remettra après.
					substr($cell, 1, -1)
				)
			),
			$t
		);
	}

	$t = preg_replace(
		'/\r?\n/',
		"\n",
		preg_replace('/[\r\n]+/', "\n", $t)
	);

	[$entete, $corps] = explode("\n", $t, 2);
	$caption = '';
	// sauter la ligne de tete formee seulement de separateurs
	if (substr_count($entete, $sep) == strlen($entete)) {
		[$entete, $corps] = explode("\n", $corps, 2);
	}
	// si une seule colonne, en faire le titre
	if (preg_match("/^([^$sep]+)$sep+\$/", $entete, $l)) {
		$caption = "\n||" . $l[1] . '|';
		[$entete, $corps] = explode("\n", $corps, 2);
	}
	// si premiere colonne vide, le raccourci doit quand meme produire <th...
	if ($entete[0] == $sep) {
		$entete = ' ' . $entete;
	}

	$lignes = explode("\n", $corps);

	// retrait des lignes vides finales
	while (
		count($lignes) > 0
		and preg_match("/^$sep*$/", $lignes[count($lignes) - 1])
	) {
		unset($lignes[count($lignes) - 1]);
	}
	//  calcul du  nombre de colonne a chaque ligne
	$nbcols = [];
	$max = $mil = substr_count($entete, $sep);
	foreach ($lignes as $k => $v) {
		if ($max <> ($nbcols[$k] = substr_count($v, $sep))) {
			if ($max > $nbcols[$k]) {
				$mil = $nbcols[$k];
			} else {
				$mil = $max;
				$max = $nbcols[$k];
			}
		}
	}
	// Si pas le meme nombre, cadrer au nombre max
	if ($mil <> $max) {
		foreach ($nbcols as $k => $v) {
			if ($v < $max) {
				$lignes[$k] .= str_repeat($sep, $max - $v);
			}
		}
	}
	// et retirer les colonnes integralement vides
	while (true) {
		$nbcols = ($entete[strlen($entete) - 1] === $sep);
		foreach ($lignes as $v) {
			$nbcols &= ($v[strlen($v) - 1] === $sep);
		}
		if (!$nbcols) {
			break;
		}
		$entete = substr($entete, 0, -1);
		foreach ($lignes as $k => $v) {
			$lignes[$k] = substr($v, 0, -1);
		}
	}

	foreach ($lignes as &$l) {
		$l = str_replace('&#34#', '"', $l);
		$l = str_replace('``**``', "\n", $l);
		$l = explode($sep, $l);
	}

	return [explode($sep, $entete), $lignes, $caption];
}
