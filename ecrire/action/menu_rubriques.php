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
 * Gestion de l'action d'affichage du navigateur de rubrique du bandeau
 *
 * @package SPIP\Core\Rubriques
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/autoriser');
include_spip('inc/texte');
include_spip('inc/filtres');

/**
 * Action d'affichage en ajax du navigateur de rubrique du bandeau
 *
 * @uses gen_liste_rubriques()
 * @uses menu_rubriques()
 *
 * @return string
 *     Code HTML présentant la liste des rubriques
 **/
function action_menu_rubriques_dist() {

	// si pas acces a ecrire, pas acces au menu
	// on renvoi un 401 qui fait echouer la requete ajax silencieusement
	if (!autoriser('ecrire')) {
		$retour =
		'<ul class="deroulant__sous-menu" data-profondeur="1">' .
			'<li class="deroulant__item deroulant__item_plan plan_site" data-profondeur="1">' .
				'<a class="deroulant__lien" href="' . generer_url_ecrire('accueil') . '" data-profondeur="1">' .
					'<span class="libelle">' . _T('public:lien_connecter') . '</span>' .
				'</a>' .
			'</li>' .
		'</ul>';
		include_spip('inc/actions');
		ajax_retour($retour);
		exit;
	}

	if ($date = intval(_request('date'))) {
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $date) . ' GMT');
	}

	$r = gen_liste_rubriques();
	if (!$r
		and isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
		and !strstr($_SERVER['SERVER_SOFTWARE'], 'IIS/')
	) {
		include_spip('inc/headers');
		header('Content-Type: text/html; charset=' . $GLOBALS['meta']['charset']);
		http_status(304);
		exit;
	} else {
		include_spip('inc/actions');
		$ret = menu_rubriques();
		ajax_retour($ret);
		exit;
	}
}

/**
 * Retourne une liste HTML des rubriques et rubriques enfants
 *
 * @param bool $complet
 *     - false pour n'avoir que le bouton racine «plan du site»
 *     - true pour avoir l'ensemble des rubriques en plus
 *
 * @return string
 *     Code HTML présentant la liste des rubriques
 **/
function menu_rubriques($complet = true) {
	$ret = '<li class="deroulant__item deroulant__item_tout toutsite" data-profondeur="1">'
		. '<a class="deroulant__lien" href="' . generer_url_ecrire('plan') . '" data-profondeur="1">'
		. '<span class="libelle">' . _T('info_tout_site') . '</span>'
		. '</a>'
		. '</li>';

	if (!$complet) {
		return "<ul class=\"deroulant__sous-menu\" data-profondeur=\"1\">$ret\n</ul>\n";
	}

	if (!isset($GLOBALS['db_art_cache'])) {
		gen_liste_rubriques();
	}
	$arr_low = extraire_article(0, $GLOBALS['db_art_cache']);

	$total_lignes = $i = sizeof($arr_low);

	if ($i > 0) {
		$nb_col = min(8, ceil($total_lignes / 30));
		if ($nb_col <= 1) {
			$nb_col = ceil($total_lignes / 10);
		}
		foreach ($arr_low as $id_rubrique => $titre_rubrique) {
			if (autoriser('voir', 'rubrique', $id_rubrique)) {
				$ret .= bandeau_rubrique($id_rubrique, $titre_rubrique, $i);
				$i++;
			}
		}

		$class_cols = ($nb_col > 1 ? "cols-$nb_col" : '');
		$ret = "<ul class=\"deroulant__sous-menu $class_cols\" data-profondeur=\"1\">"
			. $ret
			. "\n</ul>\n";
	} else {
		$ret = "<ul class=\"deroulant__sous-menu\" data-profondeur=\"1\">$ret\n</ul>\n";
	}

	return $ret;
}

/**
 * Retourne une liste HTML des rubriques enfants d'une rubrique
 *
 * @uses extraire_article()
 *
 * @param int $id_rubrique
 *     Identifiant de la rubrique parente
 * @param string $titre_rubrique
 *     Titre de cette rubrique
 * @param int $zdecal
 *     Décalage vertical, en nombre d'élément
 * @param int $profondeur
 *     Profondeur du parent
 *
 * @return string
 *     Code HTML présentant la liste des rubriques
 **/
function bandeau_rubrique($id_rubrique, $titre_rubrique, $zdecal, $profondeur = 1) {
	static $zmax = 6;
	$profondeur_next = $profondeur + 1;

	$nav = '<a class="deroulant__lien" href="' . generer_url_entite($id_rubrique, 'rubrique', '', '', false) . "\" data-profondeur=\"$profondeur\">"
		. '<span class="libelle">' . supprimer_tags(preg_replace(',[\x00-\x1f]+,', ' ', $titre_rubrique)) . '</span>'
		. "</a>\n";

	// Limiter volontairement le nombre de sous-menus
	if (!(--$zmax)) {
		$zmax++;

		return "\n<li class=\"deroulant__item\" data-profondeur=\"$profondeur\">$nav</li>";
	}

	$arr_rub = extraire_article($id_rubrique, $GLOBALS['db_art_cache']);
	$i = sizeof($arr_rub);
	if (!$i) {
		$zmax++;

		return "\n<li class=\"deroulant__item\" data-profondeur=\"$profondeur\">$nav</li>";
	}


	$nb_col = 1;
	if ($nb_rub = count($arr_rub)) {
		$nb_col = min(10, max(1, ceil($nb_rub / 10)));
	}
	$class_cols = ($nb_col > 1 ? "cols-$nb_col" : '');
	$ret = "<li class=\"deroulant__item deroulant__item_parent\" data-profondeur=\"$profondeur\">"
	 . $nav
	 . "<ul class=\"deroulant__sous-menu $class_cols\" data-profondeur=\"$profondeur_next\">";
	foreach ($arr_rub as $id_rub => $titre_rub) {
		if (autoriser('voir', 'rubrique', $id_rub)) {
			$titre = supprimer_numero(typo($titre_rub));
			$ret .= bandeau_rubrique($id_rub, $titre, $zdecal + $i, $profondeur_next);
			$i++;
		}
	}
	$ret .= "</ul></li>\n";
	$zmax++;

	return $ret;
}


/**
 * Obtient la liste des rubriques enfants d'une rubrique, prise dans le cache
 * du navigateur de rubrique
 *
 * @see gen_liste_rubriques() pour le calcul du cache
 *
 * @param int $id_p
 *     Identifiant de la rubrique parente des articles
 * @param array $t
 *     Cache des rubriques
 * @return array
 *     Liste des rubriques enfants de la rubrique (et leur titre)
 **/
function extraire_article($id_p, $t) {
	return array_key_exists($id_p, $t) ? $t[$id_p] : array();
}

/**
 * Génère le cache de la liste des rubriques pour la navigation du bandeau
 *
 * Le cache, qui comprend pour chaque rubrique ses rubriques enfants et leur titre, est :
 *
 * - réactualisé en fonction de la meta `date_calcul_rubriques`
 * - mis en cache dans le fichier défini par la constante `_CACHE_RUBRIQUES`
 * - stocké également dans la globale `db_art_cache`
 *
 * @return bool true.
 **/
function gen_liste_rubriques() {

	include_spip('inc/config');
	// ici, un petit fichier cache ne fait pas de mal
	$last = lire_config('date_calcul_rubriques', 0);
	if (lire_fichier(_CACHE_RUBRIQUES, $cache)) {
		list($date, $GLOBALS['db_art_cache']) = @unserialize($cache);
		if ($date == $last) {
			return false;
		} // c'etait en cache :-)
	}
	// se restreindre aux rubriques utilisees recemment +secteurs

	$where = sql_in_select(
		'id_rubrique',
		'id_rubrique',
		'spip_rubriques',
		'',
		'',
		'id_parent=0 DESC, date DESC',
		_CACHE_RUBRIQUES_MAX
	);

	// puis refaire la requete pour avoir l'ordre alphabetique

	$res = sql_select('id_rubrique, titre, id_parent', 'spip_rubriques', $where, '', 'id_parent, 0+titre, titre');

	// il ne faut pas filtrer le autoriser voir ici
	// car on met le resultat en cache, commun a tout le monde
	$GLOBALS['db_art_cache'] = array();
	while ($r = sql_fetch($res)) {
		$t = sinon($r['titre'], _T('ecrire:info_sans_titre'));
		$GLOBALS['db_art_cache'][$r['id_parent']][$r['id_rubrique']] = supprimer_numero(typo($t));
	}

	$t = array($last ? $last : time(), $GLOBALS['db_art_cache']);
	ecrire_fichier(_CACHE_RUBRIQUES, serialize($t));

	return true;
}
