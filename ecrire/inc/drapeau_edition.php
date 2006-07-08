<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// Drapeau d'edition : on regarde qui a ouvert quel article en edition,
// et on le signale aux autres redacteurs pour eviter de se marcher sur
// les pieds

// Le format est une meta drapeau_edition qui contient un tableau
// serialise id_article => (id_auteur_modif, date_modif)

// a chaque mise a jour de ce tableau on oublie les enregistrements datant
// de plus d'une heure

// Attention ce n'est pas un verrou "bloquant", juste un drapeau qui signale
// que l'on bosse sur un article ; les autres peuvent passer outre
// (en cas de communication orale c'est plus pratique)


function lire_tableau_edition () {
	$edition = @unserialize($GLOBALS['meta']['drapeau_edition']);
	if (!$edition) $edition = array();

	// parcourir le tableau et virer les vieux
	foreach ($edition as $objet => $data) {
		if ($data[1] < time()-3600) {
			unset ($edition[$objet]);
			$changed = true;
		}
	}

	if ($changed)
		ecrire_tableau_edition($edition);

	return $edition;
}

function ecrire_tableau_edition($edition) {
	include_spip('inc/meta');
	ecrire_meta('drapeau_edition', serialize($edition));
	ecrire_metas();
}

// J'edite tel objet
function signale_edition ($id, $id_auteur, $type='article') {
	$edition = lire_tableau_edition();
	$edition[$type.$id] = array ($id_auteur, time());
	ecrire_tableau_edition($edition);
}

// Qui edite mon objet ?
function qui_edite ($id, $type='article') {
	$edition = lire_tableau_edition();
	if (list($id_auteur, $date) = $edition[$type.$id]
	AND $date > time() - 3600) {

		$date_diff = floor( (time()-$date) / 60);
		$row_auteur = spip_fetch_array(spip_query(
			"SELECT nom FROM spip_auteurs WHERE id_auteur='$id_auteur'"
		));
		$nom_auteur_modif = typo($row_auteur["nom"]);

		// attention ce format est lie a la chaine de langue
		return array(
			'id_auteur_modif' => $id_auteur,
			'nom_auteur_modif' => $nom_auteur_modif,
			'date_diff' => $date_diff
		);
	}
}

// Quels sont les articles en cours d'edition par X ?
function liste_drapeau_edition ($id_auteur, $type = 'article') {
	$edition = lire_tableau_edition();

	$articles_ouverts = array();

	foreach ($edition as $objet => $data) {
		if ($data[0] == $id_auteur
		AND ($data[1] > time()-3600)
		AND preg_match(",$type([0-9]+),", $objet, $regs)) {
			$row = spip_fetch_array(spip_query(
			"SELECT titre, statut FROM spip_articles WHERE id_article=".$regs[1]
			));
			$articles_ouverts[] = array(
				'id_article' => $regs[1],
				'titre' => typo($row['titre']),
				'statut' => typo($row['statut'])
			);
		}
	}
	return $articles_ouverts;
}

// Quand l'auteur veut liberer tous ses articles
function debloquer_tous($id_auteur) {
	$edition = lire_tableau_edition();
	foreach ($edition as $objet => $data)
		if ($data[0] == $id_auteur) {
			unset ($edition[$objet]);
			include_spip('inc/meta');
			ecrire_meta('drapeau_edition', serialize($edition));
			ecrire_metas();
		}
}

// quand l'auteur libere un article precis
function debloquer_edition($id_auteur, $debloquer_article, $type='article') {
	$edition = lire_tableau_edition();
	foreach ($edition as $objet => $data)
		if ($data[0] == $id_auteur
		AND $objet == $type.$debloquer_article) {
			unset ($edition[$objet]);
			ecrire_tableau_edition($edition);
		}
}


?>