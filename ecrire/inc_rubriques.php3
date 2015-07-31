<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_RUBRIQUES")) return;
define("_ECRIRE_INC_RUBRIQUES", "1");


//
// Recalculer l'ensemble des donnees associees a l'arborescence des rubriques
// (cette fonction est a appeler a chaque modification sur les rubriques)
//
function calculer_rubriques() {
	if (!spip_get_lock("calcul_rubriques")) return;

	// Mettre les compteurs a zero
	// Attention, faute de SQL transactionnel on travaille sur
	// des champs temporaires afin de ne pas  casser la base
	// pendant la demi seconde de recalculs
	spip_query("UPDATE spip_rubriques
	SET date_tmp='0000-00-00 00:00:00', statut_tmp='prive'");


	//
	// Publier et dater les rubriques qui ont un article publie
	//

	// Afficher les articles post-dates ?
	include_ecrire('inc_meta.php3');
	$postdates = (lire_meta("post_dates") == "non") ?
		"AND fille.date <= NOW()" : '';

	$r = spip_query("SELECT rub.id_rubrique AS id, max(fille.date) AS date_h
	FROM spip_rubriques AS rub, spip_articles AS fille
	WHERE rub.id_rubrique = fille.id_rubrique AND fille.statut='publie'
	$postdates GROUP BY rub.id_rubrique");
	while ($row = spip_fetch_array($r))
		spip_query("UPDATE spip_rubriques
		SET statut_tmp='publie', date_tmp='".$row['date_h']."'
		WHERE id_rubrique=".$row['id']);
	
	// Publier et dater les rubriques qui ont une breve publie
	$r = spip_query("SELECT rub.id_rubrique AS id,
	max(fille.date_heure) AS date_h
	FROM spip_rubriques AS rub, spip_breves AS fille
	WHERE rub.id_rubrique = fille.id_rubrique
	AND rub.date_tmp <= fille.date_heure AND fille.statut='publie'
	GROUP BY rub.id_rubrique");
	while ($row = spip_fetch_array($r))
		spip_query("UPDATE spip_rubriques
		SET statut_tmp='publie', date_tmp='".$row['date']."'
		WHERE id_rubrique=".$row['id']);
	
	// Publier et dater les rubriques qui ont un site publie
	$r = spip_query("SELECT rub.id_rubrique AS id, max(fille.date) AS date_h
	FROM spip_rubriques AS rub, spip_syndic AS fille
	WHERE rub.id_rubrique = fille.id_rubrique AND rub.date_tmp <= fille.date
	AND fille.statut='publie'
	GROUP BY rub.id_rubrique");
	while ($row = spip_fetch_array($r))
		spip_query("UPDATE spip_rubriques
		SET statut_tmp='publie', date_tmp='".$row['date_h']."'
		WHERE id_rubrique=".$row['id']);
	
	// Publier et dater les rubriques qui ont un document publie
	$r = spip_query("SELECT rub.id_rubrique AS id, max(fille.date) AS date_h
	FROM spip_rubriques AS rub, spip_documents AS fille,
	spip_documents_rubriques AS lien
	WHERE rub.id_rubrique = lien.id_rubrique
	AND lien.id_document=fille.id_document AND rub.date_tmp <= fille.date
	GROUP BY rub.id_rubrique");
	while ($row = spip_fetch_array($r))
		spip_query("UPDATE spip_rubriques
		SET statut_tmp='publie', date_tmp='".$row['date_h']."'
		WHERE id_rubrique=".$row['id']);
	
	
	// Les rubriques qui ont une rubrique fille plus recente
	// on tourne tant que les donnees remontent vers la racine.
	do {
		$continuer = false;
		$r = spip_query("SELECT rub.id_rubrique AS id,
		max(fille.date_tmp) AS date_h
		FROM spip_rubriques AS rub, spip_rubriques AS fille
		WHERE rub.id_rubrique = fille.id_parent
		AND (rub.date_tmp < fille.date_tmp OR rub.statut_tmp<>'publie')
		AND fille.statut_tmp='publie'
		GROUP BY rub.id_rubrique");
		while ($row = spip_fetch_array($r)) {
			spip_query("UPDATE spip_rubriques
			SET statut_tmp='publie', date_tmp='".$row['date_h']."'
			WHERE id_rubrique=".$row['id']);
			$continuer = true;
		}
	} while ($continuer);

	// "Commit" des modifs
	spip_query("UPDATE spip_rubriques SET date=date_tmp, statut=statut_tmp");


	//
	// Propager les secteurs
	//

	// fixer les id_secteur des rubriques racines
	spip_query("UPDATE spip_rubriques SET id_secteur=id_rubrique
	WHERE id_parent=0");

	// reparer les rubriques qui n'ont pas l'id_secteur de leur parent
	do {
		$continuer = false;
		$r = spip_query("SELECT fille.id_rubrique AS id,
		maman.id_secteur AS secteur
		FROM spip_rubriques AS fille, spip_rubriques AS maman
		WHERE fille.id_parent = maman.id_rubrique
		AND fille.id_secteur <> maman.id_secteur");
		while ($row = spip_fetch_array($r)) {
			spip_query("UPDATE spip_rubriques
			SET id_secteur=".$row['secteur']." WHERE id_rubrique=".$row['id']);
			$continuer = true;
		}
	} while ($continuer);
	
	// reparer les articles
	$r = spip_query("SELECT fille.id_article AS id, maman.id_secteur AS secteur
	FROM spip_articles AS fille, spip_rubriques AS maman
	WHERE fille.id_rubrique = maman.id_rubrique
	AND fille.id_secteur <> maman.id_secteur");
	while ($row = spip_fetch_array($r))
		spip_query("UPDATE spip_articles
		SET id_secteur=".$row['secteur']." WHERE id_article=".$row['id']);
	
	// reparer les sites
	$r = spip_query("SELECT fille.id_syndic AS id, maman.id_secteur AS secteur
	FROM spip_syndic AS fille, spip_rubriques AS maman
	WHERE fille.id_rubrique = maman.id_rubrique
	AND fille.id_secteur <> maman.id_secteur");
	while ($row = spip_fetch_array($r))
		spip_query("UPDATE spip_syndic SET id_secteur=".$row['secteur']."
		WHERE id_syndic=".$row['id']);
	
	// Sauver la date de la derniere mise a jour (pour menu_rubriques)
	ecrire_meta("date_calcul_rubriques", date("U"));

}

//
// Calculer la langue des sous-rubriques et des articles
//
function calculer_langues_rubriques_etape() {
	$s = spip_query ("SELECT fille.id_rubrique AS id_rubrique, mere.lang AS lang
		FROM spip_rubriques AS fille, spip_rubriques AS mere
		WHERE fille.id_parent = mere.id_rubrique
		AND fille.langue_choisie != 'oui' AND mere.lang<>''
		AND mere.lang<>fille.lang");

	while ($row = spip_fetch_array($s)) {
		$lang = addslashes($row['lang']);
		$id_rubrique = $row['id_rubrique'];
		$t = spip_query ("UPDATE spip_rubriques
		SET lang='$lang', langue_choisie='non' WHERE id_rubrique=$id_rubrique");
	}

	return $t;
}

function calculer_langues_rubriques() {

	// rubriques (recursivite)
	$langue_site = addslashes(lire_meta('langue_site'));
	spip_query ("UPDATE spip_rubriques
	SET lang='$langue_site', langue_choisie='non'
	WHERE id_parent=0 AND langue_choisie != 'oui'");
	while (calculer_langues_rubriques_etape());

	// articles
	$s = spip_query ("SELECT fils.id_article AS id_article, mere.lang AS lang
		FROM spip_articles AS fils, spip_rubriques AS mere
		WHERE fils.id_rubrique = mere.id_rubrique
		AND fils.langue_choisie != 'oui' AND (fils.lang='' OR mere.lang<>'')
		AND mere.lang<>fils.lang");
	while ($row = spip_fetch_array($s)) {
		$lang = addslashes($row['lang']);
		$id_article = $row['id_article'];
		spip_query ("UPDATE spip_articles
		SET lang='$lang', langue_choisie='non' WHERE id_article=$id_article");
	}

	// breves
	$s = spip_query ("SELECT fils.id_breve AS id_breve, mere.lang AS lang
		FROM spip_breves AS fils, spip_rubriques AS mere
		WHERE fils.id_rubrique = mere.id_rubrique
		AND fils.langue_choisie != 'oui' AND (fils.lang='' OR mere.lang<>'')
		AND mere.lang<>fils.lang");
	while ($row = spip_fetch_array($s)) {
		$lang = addslashes($row['lang']);
		$id_breve = $row['id_breve'];
		spip_query ("UPDATE spip_breves
		SET lang='$lang', langue_choisie='non' WHERE id_breve=$id_breve");
	}

	if (lire_meta('multi_rubriques') == 'oui') {
		// Ecrire meta liste langues utilisees dans rubriques
		include_ecrire('inc_meta.php3');
		$s = spip_query ("SELECT lang FROM spip_rubriques
		WHERE lang != '' GROUP BY lang");
		while ($row = spip_fetch_array($s)) {
			$lang_utilisees[] = $row['lang'];
		}
		if ($lang_utilisees) {
			$lang_utilisees = join (',', $lang_utilisees);
			ecrire_meta('langues_utilisees', $lang_utilisees);
		} else {
			ecrire_meta('langues_utilisees', "");
		}
	}
}

?>
