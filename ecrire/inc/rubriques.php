<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/meta');

// Fonction a appeler lorsque le statut d'un objet change dans une rubrique
// ou que la rubrique est déplacee.
// Le 2e arg est un tableau ayant un index "statut" (indiquant le nouveau)
// et eventuellement un index "id_rubrique" (indiquant le deplacement)

// Si le statut passe a "publie", la rubrique et ses parents y passent aussi
// et les langues utilisées sont recalculées. 
// Conséquences symétriques s'il est depublie'.
// S'il est deplace' alors qu'il etait publieé, double consequence.
// Tout cela devrait passer en SQL, sous forme de Cascade SQL.

// http://doc.spip.org/@calculer_rubriques_if
function calculer_rubriques_if ($id_rubrique, $modifs, $statut_ancien='')
{
	$neuf = false;
	if ($statut_ancien == 'publie') {
		if (isset($modifs['statut']) OR isset($modifs['id_rubrique']))
			$neuf |= depublier_branche_rubrique_if($id_rubrique);
		if (isset($modifs['id_rubrique']))
			$neuf |= publier_branche_rubrique($modifs['id_rubrique']);
	} elseif ($modifs['statut']=='publie')
		$neuf |= publier_branche_rubrique($id_rubrique);

	if ($neuf)
	// Sauver la date de la derniere mise a jour (pour menu_rubriques)
	  ecrire_meta("date_calcul_rubriques", date("U"));

	$langues = calculer_langues_utilisees();
	ecrire_meta('langues_utilisees', $langues);
	ecrire_metas();
}

// Si premiere publication dans une rubrique, la passer en statut "publie"
// avec consequence sur ses parentes.
// Retourne Vrai si le statut a change

// http://doc.spip.org/@publier_branche_rubrique
function publier_branche_rubrique($id_rubrique)
{
	$id_pred = $id_rubrique;

	while ($r = sql_fetch(spip_query("SELECT id_parent FROM spip_rubriques AS R WHERE R.id_rubrique=$id_pred AND  R.statut != 'publie'"))) {

		spip_query("UPDATE spip_rubriques SET statut='publie', date=NOW() WHERE id_rubrique=$id_pred");
		if (!($id_pred = $r['id_parent'])) break;
	}
#	spip_log(" publier_branche_rubrique($id_rubrique $id_pred");
	return $id_pred != $id_rubrique;
}

// Fonction a appeler lorsqu'on depublie ou supprime qqch dans une rubrique
// retourne Vrai si le statut change effectivement

// http://doc.spip.org/@depublier_branche_rubrique_if
function depublier_branche_rubrique_if($id_rubrique)
{
	$postdates = ($GLOBALS['meta']["post_dates"] == "non") ?
		" AND date <= NOW()" : '';

#	spip_log("depublier_branche_rubrique($id_rubrique ?");
	$id_pred = $id_rubrique;
	while ($id_pred) {

		if (sql_countsel("spip_articles",  "id_rubrique=$id_pred AND statut='publie'$postdates", '', "1"))
			return $id_pred != $id_rubrique;;
	
		if (sql_countsel("spip_breves",  "id_rubrique=$id_pred AND statut='publie'", '', "1"))
			return $id_pred != $id_rubrique;;

		if (sql_countsel("spip_syndic",  "id_rubrique=$id_pred AND statut='publie'", '', "1"))
			return $id_pred != $id_rubrique;;
	
		if (sql_countsel("spip_rubriques",  "id_parent=$id_pred AND statut='publie'", '', "1"))
			return $id_pred != $id_rubrique;;
	
		if (sql_countsel("spip_documents_rubriques",  "id_rubrique=$id_pred", '', "1"))
			return $id_pred != $id_rubrique;;

		spip_query("UPDATE spip_rubriques SET statut='0' WHERE id_rubrique=$id_pred");
#		spip_log("depublier_rubrique $id_pred");

		$r = sql_fetch(spip_query("SELECT id_parent FROM spip_rubriques WHERE id_rubrique=$id_pred"));

		$id_pred = $r['id_parent'];
	}

	return $id_pred != $id_rubrique;;
}

//
// Fonction appelee apres importation:
// calculer les meta-donnes resultantes,
// remettre de la cohérence au cas où la base importee en manquait
// Cette fonction doit etre invoque sans processus concurrent potentiel.
// http://doc.spip.org/@calculer_rubriques
function calculer_rubriques() {

	calculer_rubriques_publiees();

	// Apres chaque (de)publication 
	// recalculer les langues utilisees sur le site
	$langues = calculer_langues_utilisees();
	ecrire_meta('langues_utilisees', $langues);

	// Sauver la date de la derniere mise a jour (pour menu_rubriques)
	ecrire_meta("date_calcul_rubriques", date("U"));

	// on calcule la date du prochain article post-date
	calculer_prochain_postdate(); // fera le ecrire_metas();
}

// Recalcule l'ensemble des donnees associees a l'arborescence des rubriques
// Attention, faute de SQL transactionnel on travaille sur
// des champs temporaires afin de ne pas casser la base
// pendant la demi seconde de recalculs

// http://doc.spip.org/@calculer_rubriques_publiees
function calculer_rubriques_publiees() {

	// Mettre les compteurs a zero
	spip_query("UPDATE spip_rubriques
	SET date_tmp='0000-00-00 00:00:00', statut_tmp='prive'");

	//
	// Publier et dater les rubriques qui ont un article publie
	//

	// Afficher les articles post-dates ?
	$postdates = ($GLOBALS['meta']["post_dates"] == "non") ?
		"AND fille.date <= NOW()" : '';

	$r = spip_query("SELECT rub.id_rubrique AS id, max(fille.date) AS date_h
	FROM spip_rubriques AS rub, spip_articles AS fille
	WHERE rub.id_rubrique = fille.id_rubrique AND fille.statut='publie'
	$postdates GROUP BY rub.id_rubrique");
	while ($row = sql_fetch($r))
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
	while ($row = sql_fetch($r))
		spip_query("UPDATE spip_rubriques
		SET statut_tmp='publie', date_tmp='".$row['date_h']."'
		WHERE id_rubrique=".$row['id']);
	
	// Publier et dater les rubriques qui ont un site publie
	$r = spip_query("SELECT rub.id_rubrique AS id, max(fille.date) AS date_h
	FROM spip_rubriques AS rub, spip_syndic AS fille
	WHERE rub.id_rubrique = fille.id_rubrique AND rub.date_tmp <= fille.date
	AND fille.statut='publie'
	GROUP BY rub.id_rubrique");
	while ($row = sql_fetch($r))
		spip_query("UPDATE spip_rubriques
		SET statut_tmp='publie', date_tmp='".$row['date_h']."'
		WHERE id_rubrique=".$row['id']);
	
	// Publier et dater les rubriques qui ont un document publie
	$r = spip_query("SELECT rub.id_rubrique AS id, max(fille.date) AS date_h
	FROM spip_rubriques AS rub, spip_documents AS fille,
	spip_documents_rubriques AS lien
	WHERE rub.id_rubrique = lien.id_rubrique
	AND lien.id_document=fille.id_document AND rub.date_tmp <= fille.date
	$postdates GROUP BY rub.id_rubrique");
	while ($row = sql_fetch($r))
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
		while ($row = sql_fetch($r)) {
			spip_query("UPDATE spip_rubriques
			SET statut_tmp='publie', date_tmp='".$row['date_h']."'
			WHERE id_rubrique=".$row['id']);
			$continuer = true;
		}
	} while ($continuer);
	// point d'entree pour permettre a des plugins de gerer le statut
	// autrement (par ex: toute rubrique est publiee des sa creation)
	// Ce pipeline fait ce qu'il veut, mais s'il touche aux statuts/dates
	// c'est statut_tmp/date_tmp qu'il doit modifier
	pipeline('calculer_rubriques', null);

	// Enregistrement des modifs
	spip_query("UPDATE spip_rubriques SET date=date_tmp, statut=statut_tmp");
}

// http://doc.spip.org/@propager_les_secteurs
function propager_les_secteurs()
{
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
		while ($row = sql_fetch($r)) {
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

	while ($row = sql_fetch($r)) {
		spip_query("UPDATE spip_articles
		SET id_secteur=".$row['secteur']." WHERE id_article=".$row['id']);
	}
	// reparer les sites
	$r = spip_query("SELECT fille.id_syndic AS id, maman.id_secteur AS secteur
	FROM spip_syndic AS fille, spip_rubriques AS maman
	WHERE fille.id_rubrique = maman.id_rubrique
	AND fille.id_secteur <> maman.id_secteur");
	while ($row = sql_fetch($r))
		spip_query("UPDATE spip_syndic SET id_secteur=".$row['secteur']."
		WHERE id_syndic=".$row['id']);
}

//
// Calculer la langue des sous-rubriques et des articles
//
// http://doc.spip.org/@calculer_langues_rubriques_etape
function calculer_langues_rubriques_etape() {
	$s = spip_query("SELECT fille.id_rubrique AS id_rubrique, mere.lang AS lang
		FROM spip_rubriques AS fille, spip_rubriques AS mere
		WHERE fille.id_parent = mere.id_rubrique
		AND fille.langue_choisie != 'oui' AND mere.lang<>''
		AND mere.lang<>fille.lang");

	while ($row = sql_fetch($s)) {
		$id_rubrique = $row['id_rubrique'];
		$t = spip_query("UPDATE spip_rubriques	SET lang=" . _q($row['lang']) . ", langue_choisie='non' WHERE id_rubrique=$id_rubrique");
	}

	return $t;
}

// http://doc.spip.org/@calculer_langues_rubriques
function calculer_langues_rubriques() {

	// rubriques (recursivite)
	spip_query("UPDATE spip_rubriques SET lang=" . _q($GLOBALS['meta']['langue_site']) . ", langue_choisie='non'	WHERE id_parent=0 AND langue_choisie != 'oui'");
	while (calculer_langues_rubriques_etape());

	// articles
	$s = spip_query("SELECT fils.id_article AS id_article, mere.lang AS lang
		FROM spip_articles AS fils, spip_rubriques AS mere
		WHERE fils.id_rubrique = mere.id_rubrique
		AND fils.langue_choisie != 'oui' AND (fils.lang='' OR mere.lang<>'')
		AND mere.lang<>fils.lang");
	while ($row = sql_fetch($s)) {
		$id_article = $row['id_article'];
		spip_query("UPDATE spip_articles SET lang=" . _q($row['lang']) . ", langue_choisie='non' WHERE id_article=$id_article");
	}

	// breves
	$s = spip_query("SELECT fils.id_breve AS id_breve, mere.lang AS lang
		FROM spip_breves AS fils, spip_rubriques AS mere
		WHERE fils.id_rubrique = mere.id_rubrique
		AND fils.langue_choisie != 'oui' AND (fils.lang='' OR mere.lang<>'')
		AND mere.lang<>fils.lang");
	while ($row = sql_fetch($s)) {
		$id_breve = $row['id_breve'];
		spip_query("UPDATE spip_breves SET lang=" . _q($row['lang']) . ", langue_choisie='non' WHERE id_breve=$id_breve");
	}

	if ($GLOBALS['meta']['multi_rubriques'] == 'oui') {

		$langues = calculer_langues_utilisees();
		ecrire_meta('langues_utilisees', $langues);
		ecrire_metas();
	}
}

// Cette fonction calcule la liste des langues reellement utilisees dans le
// site public
// http://doc.spip.org/@calculer_langues_utilisees
function calculer_langues_utilisees () {
	$langues = array();

	$langues[$GLOBALS['meta']['langue_site']] = 1;

	$result = spip_query("SELECT DISTINCT lang FROM spip_articles WHERE statut='publie'");
	while ($row = sql_fetch($result)) {
		$langues[$row['lang']] = 1;
	}

	$result = spip_query("SELECT DISTINCT lang FROM spip_breves WHERE statut='publie'");
	while ($row = sql_fetch($result)) {
		$langues[$row['lang']] = 1;
	}

	$result = spip_query("SELECT DISTINCT lang FROM spip_rubriques WHERE statut='publie'");
	while ($row = sql_fetch($result)) {
		$langues[$row['lang']] = 1;
	}

	$langues = array_filter(array_keys($langues));
	sort($langues);
	$langues = join(',',$langues);
	spip_log("langues utilisees: $langues");
	return $langues;
}

// http://doc.spip.org/@calcul_generation
function calcul_generation ($generation) {
	include_spip('base/abstract_sql');
	$lesfils = array();
	$result = sql_select(array('id_rubrique'),
				array('spip_rubriques AS rubriques'),
				array(calcul_mysql_in('id_parent', $generation)));
	while ($row = sql_fetch($result))
		$lesfils[] = $row['id_rubrique'];
	return join(",",$lesfils);
}

// http://doc.spip.org/@calcul_branche
function calcul_branche ($generation) {
	if (!$generation) 
		return '0';
	else {
		$branche[] = $generation;
		while ($generation = calcul_generation ($generation))
			$branche[] = $generation;
		return join(",",$branche);
	}
}

// Appelee lorsqu'un (ou plusieurs) article post-date arrive a terme 
// ou est redate'
// Si $check, affecte le statut des rubriques concernees.

// http://doc.spip.org/@calculer_prochain_postdate
function calculer_prochain_postdate($check= false) {

	if ($check) {
		$postdates = ($GLOBALS['meta']["post_dates"] == "non") ?
			"AND A.date <= NOW()" : '';

		$r = spip_query("SELECT DISTINCT A.id_rubrique AS id FROM spip_articles AS A LEFT JOIN spip_rubriques AS R ON A.id_rubrique=R.id_rubrique WHERE R.statut != 'publie' AND A.statut='publie'$postdates");
		while ($row = sql_fetch($r))
			publier_branche_rubrique($row['id']);
	}
	include_spip('inc/meta');
	$t = sql_fetch(spip_query("SELECT date FROM spip_articles WHERE statut='publie' AND date > NOW() ORDER BY date LIMIT 1"));
	
	if ($t) {
		$t =  $t['date'];
		ecrire_meta('date_prochain_postdate', strtotime($t));
	} else
		effacer_meta('date_prochain_postdate');

	spip_log("prochain postdate: $t");
	ecrire_metas(); // attention, sert aussi aux appelants
}


// creer_rubrique_nommee('truc/machin/chose') va creer
// une rubrique truc, une sous-rubrique machin, et une sous-sous-rubrique
// chose, sans creer de rubrique si elle existe deja
// a partir de id_rubrique (par defaut, a partir de la racine)
// NB: cette fonction est tres pratique, mais pas utilisee dans le core
// pour rester legere elle n'appelle pas calculer_rubriques()
// http://doc.spip.org/@creer_rubrique_nommee
function creer_rubrique_nommee($titre, $id_parent=0) {

	// eclater l'arborescence demandee
	$arbo = explode('/', preg_replace(',^/,', '', $titre));

	foreach ($arbo as $titre) {
		$s = spip_query("SELECT id_rubrique, id_parent, id_secteur, titre FROM spip_rubriques
		WHERE titre = "._q($titre)."
		AND id_parent=".intval($id_parent));
		if (!$t = sql_fetch($s)) {
			include_spip('base/abstract_sql');
			$id_rubrique = sql_insert('spip_rubriques',
				'(titre, id_parent, statut)',
				'('._q($titre).", $id_parent, 'prive')"
			);
			if ($id_parent > 0) {
				$data = sql_fetch(spip_query(
					"SELECT id_secteur,lang FROM spip_rubriques
					WHERE id_rubrique=$id_parent"));
				$id_secteur = $data['id_secteur'];
				$lang = $data['lang'];
			} else {
				$id_secteur = $id_rubrique;
				$lang = $GLOBALS['meta']['langue_site'];
			}

			spip_query("UPDATE spip_rubriques SET id_secteur=$id_secteur, lang="._q($lang)."
			WHERE id_rubrique=$id_rubrique");
		} else {
			$id_rubrique = $t['id_rubrique'];
		}

		// pour la recursion
		$id_parent = $id_rubrique;
	}

	return intval($id_rubrique);
}

?>
