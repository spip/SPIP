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
 * Gestion de l'action editer_article et de l'API d'édition d'un article
 *
 * @package SPIP\Core\Articles\Edition
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Action d'édition d'un article dans la base de données dont
 * l'identifiant est donné en paramètre de cette fonction ou
 * en argument de l'action sécurisée
 *
 * Si aucun identifiant n'est donné, on crée alors un nouvel article,
 * à condition que la rubrique parente (id_rubrique) puisse être obtenue
 * (avec _request())
 *
 * @link https://code.spip.net/@action_editer_article_dist
 * @uses article_inserer()
 * @uses article_modifier()
 *
 * @param null|int $arg
 *     Identifiant de l'article. En absence utilise l'argument
 *     de l'action sécurisée.
 * @return array
 *     Liste (identifiant de l'article, Texte d'erreur éventuel)
 */
function action_editer_article_dist($arg = null) {
	include_spip('inc/autoriser');
	$err = "";
	if (is_null($arg)) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}

	// si id_article n'est pas un nombre, c'est une creation 
	// mais on verifie qu'on a toutes les donnees qu'il faut.
	if (!$id_article = intval($arg)) {
		$id_parent = _request('id_parent');
		if (!$id_parent) {
			$err = _L("creation interdite d'un article sans rubrique");
		} elseif (!autoriser('creerarticledans', 'rubrique', $id_parent)) {
			$err = _T("info_creerdansrubrique_non_autorise");
		} else {
			$id_article = article_inserer($id_parent);
		}
	}

	// Enregistre l'envoi dans la BD
	if ($id_article > 0) {
		$err = article_modifier($id_article);
	}

	if ($err) {
		spip_log("echec editeur article: $err", _LOG_ERREUR);
	}

	return array($id_article, $err);
}

/**
 * Modifier un article
 *
 * Appelle toutes les fonctions de modification d'un article
 *
 * @param int $id_article
 *     Identifiant de l'article à modifier
 * @param array|null $set
 *     Couples (colonne => valeur) de données à modifier.
 *     En leur absence, on cherche les données dans les champs éditables
 *     qui ont été postés (via collecter_requests())
 * @return string|null
 *
 *     - Chaîne vide si aucune erreur,
 *     - Null si aucun champ à modifier,
 *     - Chaîne contenant un texte d'erreur sinon.
 */
function article_modifier($id_article, $set = null) {

	// unifier $texte en cas de texte trop long
	trop_longs_articles();

	include_spip('inc/modifier');
	include_spip('inc/filtres');
	$c = collecter_requests(
	// white list
		objet_info('article', 'champs_editables'),
		// black list
		array('date', 'statut', 'id_parent'),
		// donnees eventuellement fournies
		$set
	);

	// Si l'article est publie, invalider les caches et demander sa reindexation
	$t = sql_getfetsel("statut", "spip_articles", "id_article=" . intval($id_article));
	$invalideur = $indexation = false;
	if ($t == 'publie') {
		$invalideur = "id='article/$id_article'";
		$indexation = true;
	}

	if ($err = objet_modifier_champs('article', $id_article,
		array(
			'data' => $set,
			'nonvide' => array('titre' => _T('info_nouvel_article') . " " . _T('info_numero_abbreviation') . $id_article),
			'invalideur' => $invalideur,
			'indexation' => $indexation,
			'date_modif' => 'date_modif' // champ a mettre a date('Y-m-d H:i:s') s'il y a modif
		),
		$c)
	) {
		return $err;
	}

	// Modification de statut, changement de rubrique ?
	$c = collecter_requests(array('date', 'statut', 'id_parent'), array(), $set);
	$err = article_instituer($id_article, $c);

	return $err;
}

/**
 * Insérer un nouvel article en base de données
 *
 * En plus des données enregistrées par défaut, la fonction :
 *
 * - retrouve un identifiant de rubrique pour stocker l'article (la
 *   première rubrique racine) si l'identifiant de rubrique transmis est
 *   nul.
 * - calcule la langue de l'article, soit
 *   - d'après la langue de la rubrique si les articles ne sont pas
 *     configurés comme pouvant être traduits,
 *   - d'après la langue de l'auteur en cours si les articles peuvent être traduits et
 *     si la langue de l'auteur est acceptée en tant que langue de traduction
 * - crée une liaison automatiquement entre l'auteur connecté et l'article
 *   créé, de sorte que la personne devient par défaut auteur de l'article
 *   qu'elle crée.
 *
 * @pipeline_appel pre_insertion
 * @pipeline_appel post_insertion
 *
 * @global array meta
 * @global array visiteur_session
 * @global string spip_lang
 *
 * @param int $id_rubrique
 *     Identifiant de la rubrique parente
 * @param array|null $set
 * @return int
 *     Identifiant du nouvel article
 *
 */
function article_inserer($id_rubrique, $set = null) {

	// Si id_rubrique vaut 0 ou n'est pas definie, creer l'article
	// dans la premiere rubrique racine
	if (!$id_rubrique = intval($id_rubrique)) {
		$row = sql_fetsel("id_rubrique, id_secteur, lang", "spip_rubriques", "id_parent=0", '', '0+titre,titre', "1");
		$id_rubrique = $row['id_rubrique'];
	} else {
		$row = sql_fetsel("lang, id_secteur", "spip_rubriques", "id_rubrique=$id_rubrique");
	}

	// eviter $id_secteur = NULL (erreur sqlite) si la requete precedente echoue 
	// cas de id_rubrique = -1 par exemple avec plugin "pages"
	$id_secteur = isset($row['id_secteur']) ? $row['id_secteur'] : 0;
	$lang_rub = isset($row['lang']) ? $row['lang'] : '';

	$lang = "";
	$choisie = 'non';
	// La langue a la creation : si les liens de traduction sont autorises
	// dans les rubriques, on essaie avec la langue de l'auteur,
	// ou a defaut celle de la rubrique
	// Sinon c'est la langue de la rubrique qui est choisie + heritee
	if (!empty($GLOBALS['meta']['multi_objets']) and in_array('spip_articles',
			explode(',', $GLOBALS['meta']['multi_objets']))
	) {
		lang_select($GLOBALS['visiteur_session']['lang']);
		if (in_array($GLOBALS['spip_lang'],
			explode(',', $GLOBALS['meta']['langues_multilingue']))) {
			$lang = $GLOBALS['spip_lang'];
			$choisie = 'oui';
		}
	}

	if (!$lang) {
		$choisie = 'non';
		$lang = $lang_rub ? $lang_rub : $GLOBALS['meta']['langue_site'];
	}

	$champs = array(
		'id_rubrique' => $id_rubrique,
		'id_secteur' => $id_secteur,
		'statut' => 'prepa',
		'date' => date('Y-m-d H:i:s'),
		'lang' => $lang,
		'langue_choisie' => $choisie
	);

	if ($set) {
		$champs = array_merge($champs, $set);
	}

	// Envoyer aux plugins
	$champs = pipeline('pre_insertion',
		array(
			'args' => array(
				'table' => 'spip_articles',
			),
			'data' => $champs
		)
	);

	$id_article = sql_insertq("spip_articles", $champs);

	// controler si le serveur n'a pas renvoye une erreur
	if ($id_article > 0) {
		$id_auteur = ((is_null(_request('id_auteur')) and isset($GLOBALS['visiteur_session']['id_auteur'])) ?
			$GLOBALS['visiteur_session']['id_auteur']
			: _request('id_auteur'));
		if ($id_auteur) {
			include_spip('action/editer_auteur');
			auteur_associer($id_auteur, array('article' => $id_article));
		}
	}

	pipeline('post_insertion',
		array(
			'args' => array(
				'table' => 'spip_articles',
				'id_objet' => $id_article
			),
			'data' => $champs
		)
	);

	return $id_article;
}


/**
 * Modification des statuts d'un article
 *
 * Modifie la langue, la rubrique ou les statuts d'un article.
 *
 * @global array $GLOBALS ['meta']
 *
 * @pipeline_appel pre_edition
 * @pipeline_appel post_edition
 *
 * @param int $id_article
 *     Identifiant de l'article
 * @param array $c
 *     Couples (colonne => valeur) des données à instituer
 *     Les colonnes 'statut' et 'id_parent' sont liées, car un admin restreint
 *     peut deplacer un article publié vers une rubrique qu'il n'administre pas
 * @param bool $calcul_rub
 *     True pour changer le statut des rubriques concernées si un article
 *     change de statut ou est déplacé dans une autre rubrique
 * @return string
 *     Chaîne vide
 */
function article_instituer($id_article, $c, $calcul_rub = true) {

	include_spip('inc/autoriser');
	include_spip('inc/rubriques');
	include_spip('inc/modifier');

	$row = sql_fetsel("statut, date, id_rubrique", "spip_articles", "id_article=$id_article");
	$id_rubrique = $row['id_rubrique'];
	$statut_ancien = $statut = $row['statut'];
	$date_ancienne = $date = $row['date'];
	$champs = array();

	$d = isset($c['date']) ? $c['date'] : null;
	$s = isset($c['statut']) ? $c['statut'] : $statut;

	// cf autorisations dans inc/instituer_article
	if ($s != $statut or ($d and $d != $date)) {
		if (autoriser('publierdans', 'rubrique', $id_rubrique)) {
			$statut = $champs['statut'] = $s;
		} else {
			if (autoriser('modifier', 'article', $id_article) and $s != 'publie') {
				$statut = $champs['statut'] = $s;
			} else {
				spip_log("editer_article $id_article refus " . join(' ', $c));
			}
		}

		// En cas de publication, fixer la date a "maintenant"
		// sauf si $c commande autre chose
		// ou si l'article est deja date dans le futur
		// En cas de proposition d'un article (mais pas depublication), idem
		if ($champs['statut'] == 'publie'
			or ($champs['statut'] == 'prop' and ($d or !in_array($statut_ancien, array('publie', 'prop'))))
		) {
			if ($d or strtotime($d = $date) > time()) {
				$champs['date'] = $date = $d;
			} else {
				$champs['date'] = $date = date('Y-m-d H:i:s');
			}
		}
	}

	// Verifier que la rubrique demandee existe et est differente
	// de la rubrique actuelle
	if (isset($c['id_parent'])
		and $id_parent = $c['id_parent']
		and $id_parent != $id_rubrique
		and (sql_fetsel('1', "spip_rubriques", "id_rubrique=" . intval($id_parent)))
	) {
		$champs['id_rubrique'] = $id_parent;

		// si l'article etait publie
		// et que le demandeur n'est pas admin de la rubrique de destination
		// repasser l'article en statut 'propose'.
		if ($statut == 'publie'
			and !autoriser('publierdans', 'rubrique', $id_parent)
		) {
			$champs['statut'] = 'prop';
		}
	}

	// Envoyer aux plugins
	$champs = pipeline('pre_edition',
		array(
			'args' => array(
				'table' => 'spip_articles',
				'id_objet' => $id_article,
				'action' => 'instituer',
				'statut_ancien' => $statut_ancien,
				'date_ancienne' => $date_ancienne,
			),
			'data' => $champs
		)
	);

	if (!count($champs)) {
		return '';
	}

	// Envoyer les modifs.
	editer_article_heritage($id_article, $id_rubrique, $statut_ancien, $champs, $calcul_rub);

	// Invalider les caches
	include_spip('inc/invalideur');
	suivre_invalideur("id='article/$id_article'");

	if ($date) {
		$t = strtotime($date);
		$p = @$GLOBALS['meta']['date_prochain_postdate'];
		if ($t > time() and (!$p or ($t < $p))) {
			ecrire_meta('date_prochain_postdate', $t);
		}
	}

	// Pipeline
	pipeline('post_edition',
		array(
			'args' => array(
				'table' => 'spip_articles',
				'id_objet' => $id_article,
				'action' => 'instituer',
				'statut_ancien' => $statut_ancien,
				'date_ancienne' => $date_ancienne,
			),
			'data' => $champs
		)
	);

	// Notifications
	if ($notifications = charger_fonction('notifications', 'inc')) {
		$notifications('instituerarticle', $id_article,
			array('statut' => $statut, 'statut_ancien' => $statut_ancien, 'date' => $date, 'date_ancienne' => $date_ancienne)
		);
	}

	return ''; // pas d'erreur
}

/**
 * Fabrique la requête de modification de l'article, avec champs hérités
 *
 * @global array $GLOBALS ['meta']
 *
 * @param int $id_article
 *     Identifiant de l'article
 * @param int $id_rubrique
 *     Identifiant de la rubrique parente
 * @param string $statut
 *     Statut de l'article (prop, publie, ...)
 * @param array $champs
 *     Couples (colonne => valeur) des champs qui ont été modifiés
 * @param bool $cond
 *     True pour actualiser le statut et date de publication de la rubrique
 *     parente si nécessaire
 * @return void|null
 *     null si aucune action à faire
 *     void sinon
 */
function editer_article_heritage($id_article, $id_rubrique, $statut, $champs, $cond = true) {

	// Si on deplace l'article
	//  changer aussi son secteur et sa langue (si heritee)
	if (isset($champs['id_rubrique'])) {

		$row_rub = sql_fetsel("id_secteur, lang", "spip_rubriques", "id_rubrique=" . sql_quote($champs['id_rubrique']));

		$langue = $row_rub['lang'];
		$champs['id_secteur'] = $row_rub['id_secteur'];
		if (sql_fetsel('1', 'spip_articles',
			"id_article=" . intval($id_article) . " AND langue_choisie<>'oui' AND lang<>" . sql_quote($langue))) {
			$champs['lang'] = $langue;
		}
	}

	if (!$champs) {
		return;
	}

	sql_updateq('spip_articles', $champs, "id_article=" . intval($id_article));

	// Changer le statut des rubriques concernees 

	if ($cond) {
		include_spip('inc/rubriques');
		$postdate = ($GLOBALS['meta']["post_dates"] == "non" and isset($champs['date']) and (strtotime($champs['date']) < time())) ? $champs['date'] : false;
		calculer_rubriques_if($id_rubrique, $champs, $statut, $postdate);
	}
}

/**
 * Réunit les textes decoupés parce que trop longs
 *
 * @return void
 */
function trop_longs_articles() {
	if (is_array($plus = _request('texte_plus'))) {
		foreach ($plus as $n => $t) {
			$plus[$n] = preg_replace(",<!--SPIP-->[\n\r]*,", "", $t);
		}
		set_request('texte', join('', $plus) . _request('texte'));
	}
}
