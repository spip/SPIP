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
	include_spip('inc/meta');
	ecrire_meta('drapeau_edition', serialize($edition));
	ecrire_metas();
}

// J'edite tel objet
// http://doc.spip.org/@signale_edition
function signale_edition ($id, $auteur, $type='article') {
	$edition = lire_tableau_edition();
	if ($id_a = $auteur['id_auteur'])
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

	return $edition[$type][$id];
}

// Quels sont les articles en cours d'edition par X ?
// http://doc.spip.org/@liste_drapeau_edition
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
// http://doc.spip.org/@debloquer_tous
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
// http://doc.spip.org/@debloquer_edition
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