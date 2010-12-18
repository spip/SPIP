<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;


// Fonction a appeler lorsque le statut d'un objet change dans une rubrique
// ou que la rubrique est deplacee.
// Le 2e arg est un tableau ayant un index "statut" (indiquant le nouveau)
// et eventuellement un index "id_rubrique" (indiquant le deplacement)

// Si le statut passe a "publie", la rubrique et ses parents y passent aussi
// et les langues utilisees sont recalculees.
// Consequences symetriques s'il est depublie'.
// S'il est deplace' alors qu'il etait publiee, double consequence.
// Tout cela devrait passer en SQL, sous forme de Cascade SQL.

// http://doc.spip.org/@calculer_rubriques_if
function calculer_rubriques_if ($id_rubrique, $modifs, $statut_ancien='', $postdate = false)
{
	$neuf = false;
	if ($statut_ancien == 'publie') {
		if (isset($modifs['statut'])
			OR isset($modifs['id_rubrique'])
			OR ($postdate AND strtotime($postdate)>time()))
			$neuf |= depublier_branche_rubrique_if($id_rubrique);
		// ne publier que si c'est pas un postdate, ou si la date n'est pas dans le futur
		if ($postdate){
			calculer_prochain_postdate(true);
			$neuf |= (strtotime($postdate)<=time()); // par securite
		}
		elseif (isset($modifs['id_rubrique']))
			$neuf |= publier_branche_rubrique($modifs['id_rubrique']);
	}
	elseif ($modifs['statut']=='publie'){
		if ($postdate){
			calculer_prochain_postdate(true);
			$neuf |= (strtotime($postdate)<=time()); // par securite
		}
		else
			$neuf |= publier_branche_rubrique($id_rubrique);
	}

	if ($neuf)
	// Sauver la date de la derniere mise a jour (pour menu_rubriques)
	  ecrire_meta("date_calcul_rubriques", date("U"));

	$langues = calculer_langues_utilisees();
	ecrire_meta('langues_utilisees', $langues);
}

// Si premiere publication dans une rubrique, la passer en statut "publie"
// ainsi que ses parentes, sans oublier les dates
// Retourne Vrai si le statut a change

// http://doc.spip.org/@publier_branche_rubrique
function publier_branche_rubrique($id_rubrique)
{
	$id_pred = $id_rubrique;
	while (true) {
		sql_updateq('spip_rubriques', array('statut'=>'publie', 'date'=>date('Y-m-d H:i:s')), "id_rubrique=$id_rubrique");
		$id_parent = sql_getfetsel('id_parent', 'spip_rubriques AS R', "R.id_rubrique=$id_rubrique");
		if (!$id_parent) break;
		$id_rubrique = $id_parent;
	} 
#	spip_log(" publier_branche_rubrique($id_rubrique $id_pred");
	return $id_pred != $id_rubrique;
}

/**
 * Fonction a appeler lorsqu'on depublie ou supprime qqch dans une rubrique
 * retourne Vrai si le statut change effectivement
 *
 * http://doc.spip.org/@depublier_branche_rubrique_if
 *
 * @param int $id_rubrique
 * @return bool
 */
function depublier_branche_rubrique_if($id_rubrique){
	$date = date('Y-m-d H:i:s'); // figer la date

	#	spip_log("depublier_branche_rubrique($id_rubrique ?");
	$id_pred = $id_rubrique;
	while ($id_pred) {

		if (!depublier_rubrique_if($id_pred,$date))
			return $id_pred != $id_rubrique;
		// passer au parent si on a depublie
		$r = sql_fetsel("id_parent", "spip_rubriques", "id_rubrique=$id_pred");
		$id_pred = $r['id_parent'];
	}

	return $id_pred != $id_rubrique;
}

/**
 * Depublier une rubrique si aucun contenu publie connu
 * retourne true si la rubrique a ete depubliee
 *
 * @param int $id_rubrique
 * @param string $date
 * @return bool
 */
function depublier_rubrique_if($id_rubrique,$date=null){
	if (is_null($date))
		$date = date('Y-m-d H:i:s');
	$postdates = ($GLOBALS['meta']["post_dates"] == "non") ?
		" AND date <= ".sql_quote($date) : '';

	if (!$id_rubrique=intval($id_rubrique))
		return false;

	// verifier qu'elle existe et est bien publiee
	$r = sql_fetsel('id_rubrique,statut','spip_rubriques',"id_rubrique=$id_rubrique");
	if (!$r OR $r['statut']!=='publie')
		return false;

	// On met le nombre de chaque type d'enfants dans un tableau
	// Le type de l'objet est au pluriel
	$compte = array(
		'articles' => sql_countsel("spip_articles",  "id_rubrique=$id_rubrique AND statut='publie'$postdates"),
		'breves' => sql_countsel("spip_breves",  "id_rubrique=$id_rubrique AND statut='publie'"),
		'sites' => sql_countsel("spip_syndic",  "id_rubrique=$id_rubrique AND statut='publie'"),
		'rubriques' => sql_countsel("spip_rubriques",  "id_parent=$id_rubrique AND statut='publie'"),
		'documents' => sql_countsel("spip_documents_liens",  "id_objet=$id_rubrique AND objet='rubrique'")
	);
	
	// On passe le tableau des comptes dans un pipeline pour que les plugins puissent ajouter (ou retirer) des enfants
	$compte = pipeline('objet_compte_enfants',
		array(
			'args' => array(
				'objet' => 'rubrique',
				'id_objet' => $id_rubrique,
				'statut' => 'publie',
				'date' => $date
			),
			'data' => $compte
		)
	);
	
	// S'il y a au moins un enfant de n'importe quoi, on ne dépublie pas
	foreach($compte as $objet => $n)
		if ($n)
			return false;

	sql_updateq("spip_rubriques", array("statut" => '0'), "id_rubrique=$id_rubrique");
#		spip_log("depublier_rubrique $id_pred");
	return true;
}

//
// Fonction appelee apres importation:
// calculer les meta-donnes resultantes,
// remettre de la coherence au cas ou la base importee en manquait
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
	calculer_prochain_postdate();
}

// Recalcule l'ensemble des donnees associees a l'arborescence des rubriques
// Attention, faute de SQL transactionnel on travaille sur
// des champs temporaires afin de ne pas casser la base
// pendant la demi seconde de recalculs

// http://doc.spip.org/@calculer_rubriques_publiees
function calculer_rubriques_publiees() {

	// Mettre les compteurs a zero
	sql_updateq('spip_rubriques', array('date_tmp' => '0000-00-00 00:00:00', 'statut_tmp' => 'prive'));

	//
	// Publier et dater les rubriques qui ont un article publie
	//

	// Afficher les articles post-dates ?
	$postdates = ($GLOBALS['meta']["post_dates"] == "non") ?
		"AND fille.date <= ".sql_quote(date('Y-m-d H:i:s')) : '';

	$r = sql_select("rub.id_rubrique AS id, max(fille.date) AS date_h", "spip_rubriques AS rub, spip_articles AS fille", "rub.id_rubrique = fille.id_rubrique AND fille.statut='publie' $postdates ", "rub.id_rubrique");
	while ($row = sql_fetch($r))
		sql_updateq("spip_rubriques", array("statut_tmp" => 'publie', "date_tmp" => $row['date_h']), "id_rubrique=".$row['id']);
	
	// Publier et dater les rubriques qui ont une breve publie
	$r = sql_select("rub.id_rubrique AS id, max(fille.date_heure) AS date_h", "spip_rubriques AS rub, spip_breves AS fille", "rub.id_rubrique = fille.id_rubrique AND rub.date_tmp <= fille.date_heure AND fille.statut='publie' ", "rub.id_rubrique");
	while ($row = sql_fetch($r))
	  sql_updateq('spip_rubriques', array('statut_tmp'=>'publie', 'date_tmp'=>$row['date_h']), "id_rubrique=".$row['id']);
	
	// Publier et dater les rubriques qui ont un site publie
	$r = sql_select("rub.id_rubrique AS id, max(fille.date) AS date_h", "spip_rubriques AS rub, spip_syndic AS fille", "rub.id_rubrique = fille.id_rubrique AND rub.date_tmp <= fille.date AND fille.statut='publie' ", "rub.id_rubrique");
	while ($row = sql_fetch($r))
		sql_updateq('spip_rubriques', array('statut_tmp'=>'publie', 'date_tmp'=>$row['date_h']),"id_rubrique=".$row['id']);
	
	// Publier et dater les rubriques qui ont un *document* publie
	$r = sql_select("rub.id_rubrique AS id, max(fille.date) AS date_h", "spip_rubriques AS rub, spip_documents AS fille, spip_documents_liens AS lien", "rub.id_rubrique = lien.id_objet AND lien.objet='rubrique' AND lien.id_document=fille.id_document AND rub.date_tmp <= fille.date AND fille.mode='document'", "rub.id_rubrique");
	while ($row = sql_fetch($r))
	  sql_updateq('spip_rubriques', array('statut_tmp'=>'publie', 'date_tmp'=>$row['date_h']),"id_rubrique=".$row['id']);
	
	
	// Les rubriques qui ont une rubrique fille plus recente
	// on tourne tant que les donnees remontent vers la racine.
	do {
		$continuer = false;
		$r = sql_select("rub.id_rubrique AS id, max(fille.date_tmp) AS date_h", "spip_rubriques AS rub, spip_rubriques AS fille", "rub.id_rubrique = fille.id_parent AND (rub.date_tmp < fille.date_tmp OR rub.statut_tmp<>'publie') AND fille.statut_tmp='publie' ", "rub.id_rubrique");
		while ($row = sql_fetch($r)) {
		  sql_updateq('spip_rubriques', array('statut_tmp'=>'publie', 'date_tmp'=>$row['date_h']),"id_rubrique=".$row['id']);
			$continuer = true;
		}
	} while ($continuer);
	// point d'entree pour permettre a des plugins de gerer le statut
	// autrement (par ex: toute rubrique est publiee des sa creation)
	// Ce pipeline fait ce qu'il veut, mais s'il touche aux statuts/dates
	// c'est statut_tmp/date_tmp qu'il doit modifier
	pipeline('calculer_rubriques', null);

	// Enregistrement des modifs
	sql_update('spip_rubriques', array('date'=>'date_tmp', 'statut'=>'statut_tmp'));
}

// http://doc.spip.org/@propager_les_secteurs
function propager_les_secteurs()
{
	// fixer les id_secteur des rubriques racines
	sql_update('spip_rubriques', array('id_secteur'=>'id_rubrique'), "id_parent=0");

	// reparer les rubriques qui n'ont pas l'id_secteur de leur parent
	do {
		$continuer = false;
		$r = sql_select("fille.id_rubrique AS id, maman.id_secteur AS secteur", "spip_rubriques AS fille, spip_rubriques AS maman", "fille.id_parent = maman.id_rubrique AND fille.id_secteur <> maman.id_secteur");
		while ($row = sql_fetch($r)) {
			sql_update("spip_rubriques", array("id_secteur" => $row['secteur']), "id_rubrique=".$row['id']);
			$continuer = true;
		}
	} while ($continuer);
	
	// reparer les articles
	$r = sql_select("fille.id_article AS id, maman.id_secteur AS secteur", "spip_articles AS fille, spip_rubriques AS maman", "fille.id_rubrique = maman.id_rubrique AND fille.id_secteur <> maman.id_secteur");

	while ($row = sql_fetch($r)) {
		sql_update("spip_articles", array("id_secteur" => $row['secteur']), "id_article=".$row['id']);
	}
	// reparer les sites
	$r = sql_select("fille.id_syndic AS id, maman.id_secteur AS secteur", "spip_syndic AS fille, spip_rubriques AS maman", "fille.id_rubrique = maman.id_rubrique AND fille.id_secteur <> maman.id_secteur");
	while ($row = sql_fetch($r))
		sql_update("spip_syndic", array("id_secteur" => $row['secteur']), "id_syndic=".$row['id']);

	// avertir les plugins qui peuvent faire leur mises a jour egalement
	pipeline('trig_propager_les_secteurs','');
}

//
// Calculer la langue des sous-rubriques et des articles
//
// http://doc.spip.org/@calculer_langues_rubriques_etape
function calculer_langues_rubriques_etape() {
	$s = sql_select("fille.id_rubrique AS id_rubrique, mere.lang AS lang", "spip_rubriques AS fille, spip_rubriques AS mere", "fille.id_parent = mere.id_rubrique AND fille.langue_choisie != 'oui' AND mere.lang<>'' AND mere.lang<>fille.lang");

	while ($row = sql_fetch($s)) {
		$id_rubrique = $row['id_rubrique'];
		$t = sql_updateq('spip_rubriques', array('lang' => $row['lang'], 'langue_choisie'=>'non'), "id_rubrique=$id_rubrique");
	}

	return $t;
}

// http://doc.spip.org/@calculer_langues_rubriques
function calculer_langues_rubriques() {

	// rubriques (recursivite)
	sql_updateq("spip_rubriques", array("lang" => $GLOBALS['meta']['langue_site'], "langue_choisie" => 'non'), "id_parent=0 AND langue_choisie != 'oui'");
	while (calculer_langues_rubriques_etape());

	// articles
	$s = sql_select("fils.id_article AS id_article, mere.lang AS lang", "spip_articles AS fils, spip_rubriques AS mere", "fils.id_rubrique = mere.id_rubrique AND fils.langue_choisie != 'oui' AND (fils.lang='' OR mere.lang<>'') AND mere.lang<>fils.lang");
	while ($row = sql_fetch($s)) {
		$id_article = $row['id_article'];
		sql_updateq('spip_articles', array("lang"=> $row['lang'], 'langue_choisie'=>'non'), "id_article=$id_article");
	}

	// breves
	$s = sql_select("fils.id_breve AS id_breve, mere.lang AS lang", "spip_breves AS fils, spip_rubriques AS mere", "fils.id_rubrique = mere.id_rubrique AND fils.langue_choisie != 'oui' AND (fils.lang='' OR mere.lang<>'') AND mere.lang<>fils.lang");
	while ($row = sql_fetch($s)) {
		$id_breve = $row['id_breve'];
		sql_updateq('spip_breves', array("lang"=>$row['lang'], 'langue_choisie'=>'non'), "id_breve=$id_breve");
	}

	if ($GLOBALS['meta']['multi_rubriques'] == 'oui') {

		$langues = calculer_langues_utilisees();
		ecrire_meta('langues_utilisees', $langues);
	}
	
	// avertir les plugins qui peuvent faire leur mises a jour egalement
	pipeline('trig_calculer_langues_rubriques','');
}

// Cette fonction calcule la liste des langues reellement utilisees dans le
// site public
// http://doc.spip.org/@calculer_langues_utilisees
function calculer_langues_utilisees () {
	$langues = array();

	$langues[$GLOBALS['meta']['langue_site']] = 1;

	$result = sql_select("DISTINCT lang", "spip_articles", "statut='publie'");
	while ($row = sql_fetch($result)) {
		$langues[$row['lang']] = 1;
	}

	$result = sql_select("DISTINCT lang", "spip_breves", "statut='publie'");
	while ($row = sql_fetch($result)) {
		$langues[$row['lang']] = 1;
	}

	$result = sql_select("DISTINCT lang", "spip_rubriques", "statut='publie'");
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
	$lesfils = array();
	$result = sql_select('id_rubrique',
			     'spip_rubriques',
			     sql_in('id_parent', $generation));
	while ($row = sql_fetch($result))
		$lesfils[] = $row['id_rubrique'];
	return join(",", $lesfils);
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

// Calcul d'une branche
// (liste des id_rubrique contenues dans une rubrique donnee)
// pour le critere {branche}
// http://doc.spip.org/@calcul_branche_in
function calcul_branche_in($id) {

	// normaliser $id qui a pu arriver comme un array, comme un entier, ou comme une chaine NN,NN,NN
	if (!is_array($id)) $id = explode(',',$id);
	$id = join(',', array_map('intval', $id));

	// Notre branche commence par la rubrique de depart
	$branche = $id;

	// On ajoute une generation (les filles de la generation precedente)
	// jusqu'a epuisement
	while ($filles = sql_allfetsel('id_rubrique', 'spip_rubriques',
	sql_in('id_parent', $id))) {
		$id = join(',', array_map('array_shift', $filles));
		$branche .= ',' . $id;
	}

	return $branche;
}

// Appelee lorsqu'un (ou plusieurs) article post-date arrive a terme 
// ou est redate'
// Si $check, affecte le statut des rubriques concernees.

// http://doc.spip.org/@calculer_prochain_postdate
function calculer_prochain_postdate($check= false) {
	include_spip('base/abstract_sql');
	if ($check) {
		$postdates = ($GLOBALS['meta']["post_dates"] == "non") ?
			"AND A.date <= ".sql_quote(date('Y-m-d H:i:s')) : '';

		$r = sql_select("DISTINCT A.id_rubrique AS id",
			"spip_articles AS A LEFT JOIN spip_rubriques AS R ON A.id_rubrique=R.id_rubrique", "R.statut != 'publie' AND A.statut='publie'$postdates");
		while ($row = sql_fetch($r))
			publier_branche_rubrique($row['id']);

		pipeline('trig_calculer_prochain_postdate','');
	}

	$t = sql_fetsel("date", "spip_articles", "statut='publie' AND date > ".sql_quote(date('Y-m-d H:i:s')), "", "date", "1");
	
	if ($t) {
		$t =  $t['date'];
		ecrire_meta('date_prochain_postdate', strtotime($t));
	} else
		effacer_meta('date_prochain_postdate');

	spip_log("prochain postdate: $t");
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
	// echapper les </multi> et autres balises fermantes html
	$titre = preg_replace(",</([a-z][^>]*)>,ims","<@\\1>",$titre);
	$arbo = explode('/', preg_replace(',^/,', '', $titre));
	include_spip('base/abstract_sql');
	foreach ($arbo as $titre) {
		// retablir les </multi> et autres balises fermantes html
		$titre = preg_replace(",<@([a-z][^>]*)>,ims","</\\1>",$titre);
		$r = sql_getfetsel("id_rubrique", "spip_rubriques", "titre = ".sql_quote($titre)." AND id_parent=".intval($id_parent));
		if ($r !== NULL) {
			$id_parent = $r;
		} else {
			$id_rubrique = sql_insertq('spip_rubriques', array(
				'titre' => $titre,
				'id_parent' => $id_parent,
				'statut' => 'prive'));
			if ($id_parent > 0) {
				$data = sql_fetsel("id_secteur,lang", "spip_rubriques", "id_rubrique=$id_parent");
				$id_secteur = $data['id_secteur'];
				$lang = $data['lang'];
			} else {
				$id_secteur = $id_rubrique;
				$lang = $GLOBALS['meta']['langue_site'];
			}

			sql_updateq('spip_rubriques', array('id_secteur'=>$id_secteur, "lang"=>$lang), "id_rubrique=$id_rubrique");

			// pour la recursion
			$id_parent = $id_rubrique;
		}
	}

	return intval($id_parent);
}

?>
