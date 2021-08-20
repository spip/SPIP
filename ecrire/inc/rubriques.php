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
 * Fichier gérant l'actualisation et le suivi des rubriques, et de leurs branches
 *
 * @package SPIP\Core\Rubriques
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Recalcule les statuts d'une rubrique
 *
 * Fonction à appeler lorsque le statut d'un objet change dans une rubrique
 * ou que la rubrique est deplacée.
 *
 * Si le statut passe a "publie", la rubrique et ses parents y passent aussi
 * et les langues utilisees sont recalculées.
 * Conséquences symétriques s'il est depublié.
 *
 * S'il est deplacé alors qu'il était publiée, double conséquence.
 *
 * Tout cela devrait passer en SQL, sous forme de Cascade SQL.
 *
 * @uses depublier_branche_rubrique_if()
 * @uses calculer_prochain_postdate()
 * @uses publier_branche_rubrique()
 *
 * @param int $id_rubrique
 *     Identifiant de la rubrique
 * @param array $modifs
 *     Tableau de description des modifications.
 *     Peut avoir 2 index, 'statut' étant obligatoire :
 *     - statut : indique le nouveau statut de la rubrique
 *     - id_rubrique : indiquer la rubrique dans laquelle on déplace la rubrique (son nouveau parent donc)
 * @param array $infos
 *     Infos sur l'objet modifié : statut_ancien, objet, id_objet…
 * @param bool $postdate
 *     true pour recalculer aussi la date du prochain article post-daté
 * @return bool
 *     true si le statut change effectivement
 **/
function calculer_rubriques_if($id_rubrique, $modifs, $infos = [], $postdate = false) {
	$neuf = false;

	// Compat avec l'ancienne signature
	if (is_string($infos)) {
		$infos = ['statut_ancien' => $infos];
	}
	if (!isset($infos['statut_ancien'])) {
		$infos['statut_ancien'] = '';
	}

	// On recherche quels statuts tester
	if (
		isset($infos['objet'])
		and include_spip('inc/filtres')
		and $declaration_statut = objet_info($infos['objet'], 'statut')
		and is_array($declaration_statut)
	) {
		foreach ($declaration_statut as $champ_statut) {
			if ($champ_statut['champ'] == 'statut') {
				$statuts_publies = array_map('trim', explode(',', $champ_statut['publie']));
				break; // stop on a trouvé le bon champ
			}
		}
	}
	else {
		$statuts_publies = ['publie'];
	}

	if (in_array($infos['statut_ancien'], $statuts_publies)) {
		if (
			isset($modifs['statut'])
			or isset($modifs['id_rubrique'])
			or ($postdate and strtotime($postdate) > time())
		) {
			$neuf |= depublier_branche_rubrique_if($id_rubrique);
		}
		// ne publier que si c'est pas un postdate, ou si la date n'est pas dans le futur
		if ($postdate) {
			calculer_prochain_postdate(true);
			$neuf |= (strtotime($postdate) <= time()); // par securite
		} elseif (isset($modifs['id_rubrique'])) {
			$neuf |= publier_branche_rubrique($modifs['id_rubrique']);
		}
	} elseif (isset($modifs['statut']) and in_array($modifs['statut'], $statuts_publies)) {
		if ($postdate) {
			calculer_prochain_postdate(true);
			$neuf |= (strtotime($postdate) <= time()); // par securite
		} else {
			$neuf |= publier_branche_rubrique($id_rubrique);
		}
	}

	if ($neuf) { // Sauver la date de la derniere mise a jour (pour menu_rubriques)
	ecrire_meta('date_calcul_rubriques', date('U'));
	}

	$langues = calculer_langues_utilisees();
	ecrire_meta('langues_utilisees', $langues);
}


/**
 * Publie une rubrique et sa hiérarchie de rubriques
 *
 * Fonction à appeler lorsqu'on dépublie ou supprime quelque chose
 * dans une rubrique.
 *
 * @todo Le nom de la fonction est trompeur, vu que la fonction remonte dans la hierarchie !
 *
 * @param int $id_rubrique
 *     Identifiant de la rubrique
 * @return bool
 *     true si le statut change effectivement
 */
function publier_branche_rubrique($id_rubrique) {
	$id_pred = $id_rubrique;
	while (true) {
		sql_updateq(
			'spip_rubriques',
			['statut' => 'publie', 'date' => date('Y-m-d H:i:s')],
			'id_rubrique=' . intval($id_rubrique)
		);
		$id_parent = sql_getfetsel('id_parent', 'spip_rubriques AS R', 'R.id_rubrique=' . intval($id_rubrique));
		if (!$id_parent) {
			break;
		}
		$id_rubrique = $id_parent;
	}

#	spip_log(" publier_branche_rubrique($id_rubrique $id_pred");
	return $id_pred != $id_rubrique;
}

/**
 * Dépublie si nécessaire des éléments d'une hiérarchie de rubriques
 *
 * Fonction à appeler lorsqu'on dépublie ou supprime quelque chose
 * dans une rubrique.
 *
 * @uses depublier_rubrique_if()
 * @todo Le nom de la fonction est trompeur, vu que la fonction remonte dans la hierarchie !
 *
 * @param int $id_rubrique
 *     Identifiant de la rubrique
 * @return bool
 *     true si le statut change effectivement
 */
function depublier_branche_rubrique_if($id_rubrique) {
	$date = date('Y-m-d H:i:s'); // figer la date

	#	spip_log("depublier_branche_rubrique($id_rubrique ?");
	$id_pred = $id_rubrique;
	while ($id_pred) {
		if (!depublier_rubrique_if($id_pred, $date)) {
			return $id_pred != $id_rubrique;
		}
		// passer au parent si on a depublie
		$r = sql_fetsel('id_parent', 'spip_rubriques', 'id_rubrique=' . intval($id_pred));
		$id_pred = $r['id_parent'];
	}

	return $id_pred != $id_rubrique;
}

/**
 * Dépublier une rubrique si aucun contenu publié connu n'est trouvé dedans
 *
 * @pipeline_appel objet_compte_enfants
 *
 * @param int $id_rubrique
 *     Identifiant de la rubrique à tester
 * @param string|null $date
 *     Date pour le calcul des éléments post-datés.
 *     null = date actuelle.
 * @return bool
 *    true si la rubrique a été dépubliée
 */
function depublier_rubrique_if($id_rubrique, $date = null) {
	if (is_null($date)) {
		$date = date('Y-m-d H:i:s');
	}
	$postdates = ($GLOBALS['meta']['post_dates'] == 'non') ?
		' AND date <= ' . sql_quote($date) : '';

	if (!$id_rubrique = intval($id_rubrique)) {
		return false;
	}

	// verifier qu'elle existe et est bien publiee
	$r = sql_fetsel('id_rubrique,statut', 'spip_rubriques', 'id_rubrique=' . intval($id_rubrique));
	if (!$r or $r['statut'] !== 'publie') {
		return false;
	}

	// On met le nombre de chaque type d'enfants dans un tableau
	// Le type de l'objet est au pluriel
	$compte = [
		'articles' => sql_countsel(
			'spip_articles',
			'id_rubrique=' . intval($id_rubrique) . " AND statut='publie'$postdates"
		),
		'rubriques' => sql_countsel('spip_rubriques', 'id_parent=' . intval($id_rubrique) . " AND statut='publie'"),
		'documents' => sql_countsel(
			'spip_documents AS D JOIN spip_documents_liens AS L ON D.id_document=L.id_document',
			'L.id_objet=' . intval($id_rubrique) . " AND L.objet='rubrique' and D.mode NOT IN('logoon', 'logooff') "
		)
	];

	// On passe le tableau des comptes dans un pipeline pour que les plugins puissent ajouter (ou retirer) des enfants
	$compte = pipeline(
		'objet_compte_enfants',
		[
			'args' => [
				'objet' => 'rubrique',
				'id_objet' => $id_rubrique,
				'statut' => 'publie',
				'date' => $date
			],
			'data' => $compte
		]
	);

	// S'il y a au moins un enfant de n'importe quoi, on ne dépublie pas
	foreach ($compte as $objet => $n) {
		if ($n) {
			return false;
		}
	}

	sql_updateq('spip_rubriques', ['statut' => 'prepa'], 'id_rubrique=' . intval($id_rubrique));

#		spip_log("depublier_rubrique $id_pred");
	return true;
}


/**
 * Recalcule des héritages de rubriques
 *
 * Recalcule le statut des rubriques, les langues héritées et la date
 * du prochain article post-daté
 *
 * Cette fonction est appelée après importation: elle calcule les meta-donnes
 * resultantes et remet de la coherence au cas où la base importée en manquait
 *
 * Cette fonction doit etre invoquée sans processus concurrent potentiel.
 *
 * @uses calculer_rubriques_publiees()
 * @uses calculer_langues_utilisees()
 * @uses calculer_prochain_postdate()
 *
 * @return void
 **/
function calculer_rubriques() {

	calculer_rubriques_publiees();

	// Apres chaque (de)publication
	// recalculer les langues utilisees sur le site
	$langues = calculer_langues_utilisees();
	ecrire_meta('langues_utilisees', $langues);

	// Sauver la date de la derniere mise a jour (pour menu_rubriques)
	ecrire_meta('date_calcul_rubriques', date('U'));

	// on calcule la date du prochain article post-date
	calculer_prochain_postdate();
}


/**
 * Recalcule l'ensemble des données associées à l'arborescence des rubriques
 *
 * Attention, faute de SQL transactionnel on travaille sur
 * des champs temporaires afin de ne pas casser la base
 * pendant la demi seconde de recalculs
 *
 * @pipeline_appel calculer_rubriques
 *
 * @return void
 **/
function calculer_rubriques_publiees() {

	// Mettre les compteurs a zero
	sql_updateq('spip_rubriques', ['date_tmp' => '0000-00-00 00:00:00', 'statut_tmp' => 'prepa']);

	//
	// Publier et dater les rubriques qui ont un article publie
	//

	// Afficher les articles post-dates ?
	$postdates = ($GLOBALS['meta']['post_dates'] == 'non') ?
		'AND A.date <= ' . sql_quote(date('Y-m-d H:i:s')) : '';

	$r = sql_select(
		'R.id_rubrique AS id, max(A.date) AS date_h',
		'spip_rubriques AS R JOIN spip_articles AS A ON R.id_rubrique = A.id_rubrique',
		"A.date>R.date_tmp AND A.statut='publie' $postdates ",
		'R.id_rubrique'
	);
	while ($row = sql_fetch($r)) {
		sql_updateq(
			'spip_rubriques',
			['statut_tmp' => 'publie', 'date_tmp' => $row['date_h']],
			'id_rubrique=' . intval($row['id'])
		);
	}

	// point d'entree pour permettre a des plugins de gerer le statut
	// autrement (par ex: toute rubrique est publiee des sa creation)
	// Ce pipeline fait ce qu'il veut, mais s'il touche aux statuts/dates
	// c'est statut_tmp/date_tmp qu'il doit modifier
	// [C'est un trigger... a renommer en trig_calculer_rubriques ?]
	pipeline('calculer_rubriques', null);


	// Les rubriques qui ont une rubrique fille plus recente
	// on tourne tant que les donnees remontent vers la racine.
	do {
		$continuer = false;
		$r = sql_select(
			'R.id_rubrique AS id, max(SR.date_tmp) AS date_h',
			'spip_rubriques AS R JOIN spip_rubriques AS SR ON R.id_rubrique = SR.id_parent',
			"(SR.date_tmp>R.date_tmp OR R.statut_tmp<>'publie') AND SR.statut_tmp='publie' ",
			'R.id_rubrique'
		);
		while ($row = sql_fetch($r)) {
			sql_updateq(
				'spip_rubriques',
				['statut_tmp' => 'publie', 'date_tmp' => $row['date_h']],
				'id_rubrique=' . intval($row['id'])
			);
			$continuer = true;
		}
	} while ($continuer);

	// Enregistrement des modifs
	sql_update('spip_rubriques', ['date' => 'date_tmp', 'statut' => 'statut_tmp']);
}

/**
 * Recalcule les secteurs et les profondeurs des rubriques (et articles)
 *
 * Cherche les rubriques ayant des id_secteur ou profondeurs ne correspondant pas
 * avec leur parent, et les met à jour. De même avec les articles et leur id_secteur
 * On procede en iterant la profondeur de 1 en 1 pour ne pas risquer une boucle infinie sur reference circulaire
 *
 * @pipeline_appel trig_propager_les_secteurs
 *
 * @return void
 **/
function propager_les_secteurs() {
	// Profondeur 0
	// Toutes les rubriques racines sont de profondeur 0
	// et fixer les id_secteur des rubriques racines
	sql_update('spip_rubriques', ['id_secteur' => 'id_rubrique', 'profondeur' => 0], 'id_parent=0');
	// Toute rubrique non racine est de profondeur >0
	sql_updateq('spip_rubriques', ['profondeur' => 1], 'id_parent<>0 AND profondeur=0');

	// securite : pas plus d'iteration que de rubriques dans la base
	$maxiter = sql_countsel('spip_rubriques');

	// reparer les rubriques qui n'ont pas l'id_secteur de leur parent
	// on fait profondeur par profondeur

	$prof = 0;
	do {
		$continuer = false;

		// Par recursivite : si toutes les rubriques de profondeur $prof sont bonnes
		// on fixe le profondeur $prof+1

		// Toutes les rubriques dont le parent est de profondeur $prof ont une profondeur $prof+1
		// on teste A.profondeur > $prof+1 car :
		// - toutes les rubriques de profondeur 0 à $prof sont bonnes
		// - si A.profondeur = $prof+1 c'est bon
		// - cela nous protege de la boucle infinie en cas de reference circulaire dans les rubriques
		$maxiter2 = $maxiter;
		while (
			$maxiter2--
			and $rows = sql_allfetsel(
				'A.id_rubrique AS id, R.id_secteur AS id_secteur, R.profondeur+1 as profondeur',
				'spip_rubriques AS A JOIN spip_rubriques AS R ON A.id_parent = R.id_rubrique',
				'R.profondeur=' . intval($prof) . ' AND (A.id_secteur <> R.id_secteur OR A.profondeur > R.profondeur+1)',
				'',
				'R.id_secteur',
				'0,100'
			)
		) {
			$id_secteur = null;
			$ids = [];
			while ($row = array_shift($rows)) {
				if ($row['id_secteur'] !== $id_secteur) {
					if (count($ids)) {
						sql_updateq(
							'spip_rubriques',
							['id_secteur' => $id_secteur, 'profondeur' => $prof + 1],
							sql_in('id_rubrique', $ids)
						);
					}
					$id_secteur = $row['id_secteur'];
					$ids = [];
				}
				$ids[] = $row['id'];
			}
			if (count($ids)) {
				sql_updateq(
					'spip_rubriques',
					['id_secteur' => $id_secteur, 'profondeur' => $prof + 1],
					sql_in('id_rubrique', $ids)
				);
			}
		}


		// Toutes les rubriques de profondeur $prof+1 qui n'ont pas un parent de profondeur $prof sont decalees
		$maxiter2 = $maxiter;
		while (
			$maxiter2--
			and $rows = sql_allfetsel(
				'id_rubrique as id',
				'spip_rubriques',
				'profondeur=' . intval($prof + 1) . ' AND id_parent NOT IN (' . sql_get_select(
					'zzz.id_rubrique',
					'spip_rubriques AS zzz',
					'zzz.profondeur=' . intval($prof)
				) . ')',
				'',
				'',
				'0,100'
			)
		) {
			$rows = array_column($rows, 'id');
			sql_updateq('spip_rubriques', ['profondeur' => $prof + 2], sql_in('id_rubrique', $rows));
		}

		// ici on a fini de valider $prof+1, toutes les rubriques de prondeur 0 a $prof+1 sont OK
		// si pas de rubrique a profondeur $prof+1 pas la peine de continuer
		// si il reste des rubriques non vues, c'est une branche morte ou reference circulaire (base foireuse)
		// on arrete les frais
		if (sql_countsel('spip_rubriques', 'profondeur=' . intval($prof + 1))) {
			$prof++;
			$continuer = true;
		}
	} while ($continuer and $maxiter--);

	// loger si la table des rubriques semble foireuse
	// et mettre un id_secteur=0 sur ces rubriques pour eviter toute selection par les boucles
	if (sql_countsel('spip_rubriques', 'profondeur>' . intval($prof + 1))) {
		spip_log(
			'Les rubriques de profondeur>' . ($prof + 1) . ' semblent suspectes (branches morte ou reference circulaire dans les parents)',
			_LOG_CRITIQUE
		);
		sql_update('spip_rubriques', ['id_secteur' => 0], 'profondeur>' . intval($prof + 1));
	}

	// reparer les articles
	$r = sql_select(
		'A.id_article AS id, R.id_secteur AS secteur',
		'spip_articles AS A, spip_rubriques AS R',
		'A.id_rubrique = R.id_rubrique AND A.id_secteur <> R.id_secteur'
	);

	while ($row = sql_fetch($r)) {
		sql_update('spip_articles', ['id_secteur' => $row['secteur']], 'id_article=' . intval($row['id']));
	}

	// avertir les plugins qui peuvent faire leur mises a jour egalement
	pipeline('trig_propager_les_secteurs', '');
}


/**
 * Recalcule les langues héritées des sous-rubriques
 *
 * Cherche les langues incorrectes de sous rubriques, qui doivent hériter
 * de la rubrique parente lorsque langue_choisie est différent de oui,
 * et les corrige.
 *
 * @return bool
 *     true si un changement a eu lieu
 **/
function calculer_langues_rubriques_etape() {
	$s = sql_select(
		'A.id_rubrique AS id_rubrique, R.lang AS lang',
		'spip_rubriques AS A, spip_rubriques AS R',
		"A.id_parent = R.id_rubrique AND A.langue_choisie != 'oui' AND R.lang<>'' AND R.lang<>A.lang"
	);

	$t = false;
	while ($row = sql_fetch($s)) {
		$id_rubrique = $row['id_rubrique'];
		$t = sql_updateq(
			'spip_rubriques',
			['lang' => $row['lang'], 'langue_choisie' => 'non'],
			'id_rubrique=' . intval($id_rubrique)
		);
	}

	return $t;
}

/**
 * Recalcule les langues des rubriques et articles
 *
 * Redéfinit la langue du site sur les rubriques sans langue spécifiée
 * (langue_choisie différent de 'oui')
 *
 * Redéfinit les langues des articles sans langue spécifiée
 * (langue_choisie différent de 'oui') en les rebasant sur la langue
 * de la rubrique parente lorsque ce n'est pas le cas.
 *
 * @uses calculer_langues_rubriques_etape()
 * @pipeline_appel trig_calculer_langues_rubriques
 *
 * @return void
 **/
function calculer_langues_rubriques() {

	// rubriques (recursivite)
	sql_updateq(
		'spip_rubriques',
		['lang' => $GLOBALS['meta']['langue_site'], 'langue_choisie' => 'non'],
		"id_parent=0 AND langue_choisie != 'oui'"
	);
	while (calculer_langues_rubriques_etape()) {
		;
	}

	// articles
	$s = sql_select(
		'A.id_article AS id_article, R.lang AS lang',
		'spip_articles AS A, spip_rubriques AS R',
		"A.id_rubrique = R.id_rubrique AND A.langue_choisie != 'oui' AND (length(A.lang)=0 OR length(R.lang)>0) AND R.lang<>A.lang"
	);
	while ($row = sql_fetch($s)) {
		$id_article = $row['id_article'];
		sql_updateq(
			'spip_articles',
			['lang' => $row['lang'], 'langue_choisie' => 'non'],
			'id_article=' . intval($id_article)
		);
	}

	if ($GLOBALS['meta']['multi_rubriques'] == 'oui') {
		$langues = calculer_langues_utilisees();
		ecrire_meta('langues_utilisees', $langues);
	}

	// avertir les plugins qui peuvent faire leur mises a jour egalement
	pipeline('trig_calculer_langues_rubriques', '');
}


/**
 * Calcule la liste des langues réellement utilisées dans le site public
 *
 * La recherche de langue est effectuée en recréant une boucle pour chaque
 * objet éditorial gérant des langues de sorte que les éléments non publiés
 * ne sont pas pris en compte.
 *
 * @param string $serveur
 *    Nom du connecteur à la base de données
 * @return string
 *    Liste des langues utilisées séparées par des virgules
 **/
function calculer_langues_utilisees($serveur = '') {
	include_spip('public/interfaces');
	include_spip('public/compiler');
	include_spip('public/composer');
	include_spip('public/phraser_html');
	$langues = [];

	$langues[$GLOBALS['meta']['langue_site']] = 1;

	include_spip('base/objets');
	$tables = lister_tables_objets_sql();
	$trouver_table = charger_fonction('trouver_table', 'base');

	foreach (array_keys($tables) as $t) {
		$desc = $trouver_table($t, $serveur);
		// c'est une table avec des langues
		if (
			$desc['exist']
			and isset($desc['field']['lang'])
			and isset($desc['field']['langue_choisie'])
		) {
			$boucle = new Boucle();
			$boucle->show = $desc;
			$boucle->nom = 'calculer_langues_utilisees';
			$boucle->id_boucle = $desc['table_objet'];
			$boucle->id_table = $desc['table_objet'];
			$boucle->sql_serveur = $serveur;
			$boucle->select[] = 'DISTINCT lang';
			$boucle->from[$desc['table_objet']] = $t;
			$boucle->separateur[] = ',';
			$boucle->return = '$Pile[$SP][\'lang\']';
			$boucle->iterateur = 'sql';

			$boucle->descr['nom'] = 'calculer_langues_utilisees'; // eviter notice php
			$boucle->descr['sourcefile'] = 'internal';
			$boucle->descr['gram'] = 'html';

			$boucle = pipeline('pre_boucle', $boucle);

			if (
				isset($desc['statut'])
				and $desc['statut']
			) {
				$boucles = [
					'calculer_langues_utilisees' => $boucle,
				];
				// generer un nom de fonction "anonyme" unique
				do {
					$functionname = 'f_calculer_langues_utilisees_' . $boucle->id_table . '_' . time() . '_' . rand();
				} while (function_exists($functionname));
				$code = calculer_boucle('calculer_langues_utilisees', $boucles);
				$code = '$SP=0; $command=array();$command["connect"] = $connect = "' . $serveur . '"; $Pile=array(0=>array());' . "\n" . $code;
				$code = 'function ' . $functionname . '(){' . $code . '};$res = ' . $functionname . '();';
				$res = '';
				eval($code);
				$res = explode(',', $res);
				foreach ($res as $lang) {
					$langues[$lang] = 1;
				}
			} else {
				$res = sql_select(implode(',', $boucle->select), $boucle->from);
				while ($row = sql_fetch($res)) {
					$langues[$row['lang']] = 1;
				}
			}
		}
	}

	$langues = array_filter(array_keys($langues));
	sort($langues);
	$langues = join(',', $langues);
	spip_log("langues utilisees: $langues");

	return $langues;
}

/**
 * Calcul d'une branche de rubrique
 *
 * Liste des id_rubrique contenues dans une rubrique donnée
 *
 * @see inc_calcul_branche_in_dist()
 *
 * @param string|int|array $id
 *     Identifiant de la, ou des rubriques noeuds
 * @return string
 *     Liste des identifiants séparés par des virgules,
 *     incluant les rubriques noeuds et toutes leurs descendances
 */
function calcul_branche_in($id) {
	$calcul_branche_in = charger_fonction('calcul_branche_in', 'inc');

	return $calcul_branche_in($id);
}

/**
 * Calcul d'une hiérarchie
 *
 * Liste des id_rubrique contenant une rubrique donnée
 *
 * @see inc_calcul_hierarchie_in_dist()
 * @param string|int|array $id
 *     Identifiant de la, ou des rubriques dont on veut obtenir les hierarchies
 * @param bool $tout
 *     inclure la rubrique de depart dans la hierarchie ou non
 * @return string
 *     Liste des identifiants séparés par des virgules,
 *     incluant les rubriques transmises et toutes leurs parentées
 */
function calcul_hierarchie_in($id, $tout = true) {
	$calcul_hierarchie_in = charger_fonction('calcul_hierarchie_in', 'inc');

	return $calcul_hierarchie_in($id, $tout);
}


/**
 * Calcul d'une branche de rubriques
 *
 * Liste des id_rubrique contenues dans une rubrique donnée
 * pour le critere {branche}
 *
 * Fonction surchargeable pour optimisation
 *
 * @see inc_calcul_hierarchie_in_dist() pour la hierarchie
 *
 * @param string|int|array $id
 *     Identifiant de la, ou des rubriques noeuds
 * @return string
 *     Liste des identifiants séparés par des virgules,
 *     incluant les rubriques noeuds et toutes leurs descendances
 */
function inc_calcul_branche_in_dist($id) {
	static $b = [];

	// normaliser $id qui a pu arriver comme un array, comme un entier, ou comme une chaine NN,NN,NN
	if (!is_array($id)) {
		$id = explode(',', $id);
	}
	$id = join(',', array_map('intval', $id));
	if (isset($b[$id])) {
		return $b[$id];
	}

	// Notre branche commence par la rubrique de depart
	$branche = $r = $id;

	// On ajoute une generation (les filles de la generation precedente)
	// jusqu'a epuisement, en se protegeant des references circulaires
	$maxiter = 10000;
	while (
		$maxiter-- and $filles = sql_allfetsel(
			'id_rubrique',
			'spip_rubriques',
			sql_in('id_parent', $r) . ' AND ' . sql_in('id_rubrique', $r, 'NOT')
		)
	) {
		$r = join(',', array_column($filles, 'id_rubrique'));
		$branche .= ',' . $r;
	}

	# securite pour ne pas plomber la conso memoire sur les sites prolifiques
	if (strlen($branche) < 10000) {
		$b[$id] = $branche;
	}

	return $branche;
}


/**
 * Calcul d'une hiérarchie
 *
 * Liste des id_rubrique contenant une rubrique donnée,
 * contrairement à la fonction calcul_branche_in() qui calcule les
 * rubriques contenues
 *
 * @see inc_calcul_branche_in_dist() pour la descendence
 *
 * @param string|int|array $id
 *     Identifiant de la, ou des rubriques dont on veut obtenir les hierarchies
 * @param bool $tout
 *     inclure la rubrique de depart dans la hierarchie ou non
 * @return string
 *     Liste des identifiants séparés par des virgules,
 *     incluant les rubriques transmises et toutes leurs parentées
 */
function inc_calcul_hierarchie_in_dist($id, $tout = true) {
	static $b = [];

	// normaliser $id qui a pu arriver comme un array, comme un entier, ou comme une chaine NN,NN,NN
	if (!is_array($id)) {
		$id = explode(',', $id);
	}
	$id = join(',', array_map('intval', $id));

	if (isset($b[$id])) {
		// Notre branche commence par la rubrique de depart si $tout=true
		return $tout ? (strlen($b[$id]) ? $b[$id] . ",$id" : $id) : $b[$id];
	}

	$hier = '';

	// On ajoute une generation (les filles de la generation precedente)
	// jusqu'a epuisement, en se protegeant des references circulaires
	$ids_nouveaux_parents = $id;
	$maxiter = 10000;
	while (
		$maxiter-- and $parents = sql_allfetsel(
			'id_parent',
			'spip_rubriques',
			sql_in('id_rubrique', $ids_nouveaux_parents) . ' AND ' . sql_in('id_parent', $hier, 'NOT')
		)
	) {
		$ids_nouveaux_parents = join(',', array_column($parents, 'id_parent'));
		$hier = $ids_nouveaux_parents . (strlen($hier) ? ',' . $hier : '');
	}

	# securite pour ne pas plomber la conso memoire sur les sites prolifiques
	if (strlen($hier) < 10000) {
		$b[$id] = $hier;
	}

	// Notre branche commence par la rubrique de depart si $tout=true
	$hier = $tout ? (strlen($hier) ? "$hier,$id" : $id) : $hier;

	return $hier;
}


/**
 * Calcule la date du prochain article post-daté
 *
 * Appelée lorsqu'un (ou plusieurs) article post-daté arrive à terme
 * ou est redaté
 *
 * @uses publier_branche_rubrique()
 * @pipeline_appel trig_calculer_prochain_postdate
 *
 * @param bool $check
 *     true pour affecter le statut des rubriques concernées.
 * @return void
 **/
function calculer_prochain_postdate($check = false) {
	include_spip('base/abstract_sql');
	if ($check) {
		$postdates = ($GLOBALS['meta']['post_dates'] == 'non') ?
			'AND A.date <= ' . sql_quote(date('Y-m-d H:i:s')) : '';

		$r = sql_select(
			'DISTINCT A.id_rubrique AS id',
			'spip_articles AS A LEFT JOIN spip_rubriques AS R ON A.id_rubrique=R.id_rubrique',
			"R.statut != 'publie' AND A.statut='publie'$postdates"
		);
		while ($row = sql_fetch($r)) {
			publier_branche_rubrique($row['id']);
		}

		pipeline('trig_calculer_prochain_postdate', '');
	}

	$t = sql_fetsel(
		'date',
		'spip_articles',
		"statut='publie' AND date > " . sql_quote(date('Y-m-d H:i:s')),
		'',
		'date',
		'1'
	);

	if ($t) {
		$t = $t['date'];
		if (
			!isset($GLOBALS['meta']['date_prochain_postdate'])
			or $t <> $GLOBALS['meta']['date_prochain_postdate']
		) {
			ecrire_meta('date_prochain_postdate', strtotime($t));
			ecrire_meta('derniere_modif', time());
		}
	} else {
		effacer_meta('date_prochain_postdate');
		ecrire_meta('derniere_modif', time());
	}

	spip_log("prochain postdate: $t");
}

/**
 * Crée une arborescence de rubrique
 *
 * creer_rubrique_nommee('truc/machin/chose') va créer
 * une rubrique truc, une sous-rubrique machin, et une sous-sous-rubrique
 * chose, sans créer de rubrique si elle existe déjà
 * à partir de $id_parent (par défaut, à partir de la racine)
 *
 * NB: cette fonction est très pratique, mais pas utilisée dans le core
 * pour rester légère elle n'appelle pas calculer_rubriques()
 *
 * @param string $titre
 *     Titre des rubriques, séparés par des /
 * @param int $id_parent
 *     Identifiant de la rubrique parente
 * @param string $serveur
 *     Nom du connecteur à la base de données
 * @return int
 *     Identifiant de la rubrique la plus profonde.
 */
function creer_rubrique_nommee($titre, $id_parent = 0, $serveur = '') {

	// eclater l'arborescence demandee
	// echapper les </multi> et autres balises fermantes html
	$titre = preg_replace(',</([a-z][^>]*)>,ims', "<@\\1>", $titre);
	$arbo = explode('/', preg_replace(',^/,', '', $titre));
	include_spip('base/abstract_sql');
	foreach ($arbo as $titre) {
		// retablir les </multi> et autres balises fermantes html
		$titre = preg_replace(',<@([a-z][^>]*)>,ims', "</\\1>", $titre);
		$r = sql_getfetsel(
			'id_rubrique',
			'spip_rubriques',
			'titre = ' . sql_quote($titre) . ' AND id_parent=' . intval($id_parent),
			$groupby = [],
			$orderby = [],
			$limit = '',
			$having = [],
			$serveur
		);
		if ($r !== null) {
			$id_parent = $r;
		} else {
			$id_rubrique = sql_insertq('spip_rubriques', [
					'titre' => $titre,
					'id_parent' => $id_parent,
					'statut' => 'prepa'
				], $desc = [], $serveur);
			if ($id_parent > 0) {
				$data = sql_fetsel(
					'id_secteur,lang',
					'spip_rubriques',
					"id_rubrique=$id_parent",
					$groupby = [],
					$orderby = [],
					$limit = '',
					$having = [],
					$serveur
				);
				$id_secteur = $data['id_secteur'];
				$lang = $data['lang'];
			} else {
				$id_secteur = $id_rubrique;
				$lang = $GLOBALS['meta']['langue_site'];
			}

			sql_updateq(
				'spip_rubriques',
				['id_secteur' => $id_secteur, 'lang' => $lang],
				'id_rubrique=' . intval($id_rubrique),
				$desc = '',
				$serveur
			);

			// pour la recursion
			$id_parent = $id_rubrique;
		}
	}

	return intval($id_parent);
}
