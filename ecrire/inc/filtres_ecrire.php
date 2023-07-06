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
 * Fonctions utilisées au calcul des squelette du privé.
 *
 * @package SPIP\Core\Filtres
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/filtres_boites');
include_spip('inc/filtres_alertes');
include_spip('inc/boutons');
include_spip('inc/pipelines_ecrire');


/**
 * Retourne les paramètres de personnalisation css de l'espace privé
 *
 * Ces paramètres sont (ltr et couleurs) ce qui permet une écriture comme :
 * generer_url_public('style_prive', parametres_css_prive())
 * qu'il est alors possible de récuperer dans le squelette style_prive.html avec
 *
 * #SET{claire,##ENV{couleur_claire,edf3fe}}
 * #SET{foncee,##ENV{couleur_foncee,3874b0}}
 * #SET{left,#ENV{ltr}|choixsiegal{left,left,right}}
 * #SET{right,#ENV{ltr}|choixsiegal{left,right,left}}
 *
 * @return string
 */
function parametres_css_prive() {

	$args = [];
	$args['v'] = $GLOBALS['spip_version_code'];
	$args['p'] = substr(md5((string) $GLOBALS['meta']['plugin']), 0, 4);
	$args['themes'] = implode(',', lister_themes_prives());
	$args['ltr'] = $GLOBALS['spip_lang_left'];
	// un md5 des menus : si un menu change il faut maj la css
	$args['md5b'] = (function_exists('md5_boutons_plugins') ? md5_boutons_plugins() : '');

	$c = $GLOBALS['visiteur_session']['prefs']['couleur'] ?? 2;

	$couleurs = charger_fonction('couleurs', 'inc');
	parse_str((string) $couleurs($c), $c);
	$args = array_merge($args, $c);

	if (_request('var_mode') == 'recalcul' || defined('_VAR_MODE') && _VAR_MODE == 'recalcul') {
		$args['var_mode'] = 'recalcul';
	}

	return http_build_query($args);
}


/**
 * Afficher le sélecteur de rubrique
 *
 * Il permet de placer un objet dans la hiérarchie des rubriques de SPIP
 *
 * @uses inc_chercher_rubrique_dist()
 *
 * @param string $titre
 * @param int $id_objet
 * @param int $id_parent
 * @param string $objet
 * @param int $id_secteur
 * @param bool $restreint
 * @param bool $actionable
 *   true : fournit le selecteur dans un form directement postable
 * @param bool $retour_sans_cadre
 * @return string
 */
function chercher_rubrique(
	$titre,
	$id_objet,
	$id_parent,
	$objet,
	$id_secteur,
	$restreint,
	$actionable = false,
	$retour_sans_cadre = false
) {

	include_spip('inc/autoriser');
	if ((int) $id_objet && !autoriser('modifier', $objet, $id_objet)) {
		return '';
	}
	if (!sql_countsel('spip_rubriques')) {
		return '';
	}
	$chercher_rubrique = charger_fonction('chercher_rubrique', 'inc');
	$form = $chercher_rubrique($id_parent, $objet, $restreint, ($objet == 'rubrique') ? $id_objet : 0);

	if ($id_parent == 0) {
		$logo = 'racine-24.png';
	} elseif ($id_secteur == $id_parent) {
		$logo = 'secteur-24.png';
	} else {
		$logo = 'rubrique-24.png';
	}

	$confirm = '';
	if ($objet == 'rubrique') {
		// FIXME: Migration plus adapté vers le plugin Brèves ?
		// si c'est une rubrique-secteur contenant des breves, demander la
		// confirmation du deplacement
		if (
			sql_table_exists('spip_breves')
			&& ($contient_breves = sql_countsel('spip_breves', 'id_rubrique=' . (int) $id_objet))
			&& $contient_breves > 0
		) {
			// FIXME: utiliser singulier_ou_pluriel, migrer dans plugin Brèves
			$scb = ($contient_breves > 1 ? 's' : '');
			$scb = _T(
				'avis_deplacement_rubrique',
				[
					'contient_breves' => $contient_breves,
					'scb' => $scb
				]
			);
			$confirm .= "\n<div class='confirmer_deplacement verdana2'>"
				. "<div class='choix'><input type='checkbox' name='confirme_deplace' value='oui' id='confirme-deplace'><label for='confirme-deplace'>"
				. $scb .
				"</label></div></div>\n";
		} else {
			$confirm .= "<input type='hidden' name='confirme_deplace' value='oui'>\n";
		}
	}
	$form .= $confirm;
	if ($actionable) {
		if (str_contains($form, '<select')) {
			$form .= "<div style='text-align: " . $GLOBALS['spip_lang_right'] . ";'>"
				. '<input class="fondo submit btn" type="submit" value="' . _T('bouton_choisir') . '"/>'
				. '</div>';
		}
		$form = "<input type='hidden' name='editer_$objet' value='oui'>\n" . $form;
		if ($action = charger_fonction("editer_$objet", 'action', true)) {
			$form = generer_action_auteur(
				"editer_$objet",
				$id_objet,
				self(),
				$form,
				" method='post' class='submit_plongeur'"
			);
		} else {
			$form = generer_action_auteur(
				'editer_objet',
				"$objet/$id_objet",
				self(),
				$form,
				" method='post' class='submit_plongeur'"
			);
		}
	}

	if ($retour_sans_cadre) {
		return $form;
	}

	include_spip('inc/presentation');

	return debut_cadre_couleur($logo, true, '', $titre) . $form . fin_cadre_couleur();
}


/**
 * Tester si le site peut avoir des visiteurs
 *
 * @param bool $past
 *   si true, prendre en compte le fait que le site a *deja* des visiteurs
 *   comme le droit d'en avoir de nouveaux
 * @param bool $accepter
 * @return bool
 */
function avoir_visiteurs($past = false, $accepter = true) {
	if ($GLOBALS['meta']['forums_publics'] == 'abo') {
		return true;
	}
	if ($accepter && $GLOBALS['meta']['accepter_visiteurs'] != 'non') {
		return true;
	}
	if (sql_countsel('spip_articles', "accepter_forum='abo'")) {
		return true;
	}
	if (!$past) {
		return false;
	}

	return sql_countsel(
		'spip_auteurs',
		"statut NOT IN ('0minirezo','1comite', '5poubelle')
	                    AND (statut<>'nouveau' OR prefs NOT IN ('0minirezo','1comite', '5poubelle'))"
	);
}

/**
 * Lister les status d'article visibles dans l'espace prive
 * en fonction du statut de l'auteur
 *
 * Pour l'extensibilie de SPIP, on se repose sur autoriser('voir','article')
 * en testant un à un les status présents en base
 *
 * On mémorise en static pour éviter de refaire plusieurs fois.
 *
 * @param string $statut_auteur
 * @return array
 */
function statuts_articles_visibles($statut_auteur) {
	static $auth = [];
	if (!isset($auth[$statut_auteur])) {
		$auth[$statut_auteur] = [];
		$statuts = array_column(sql_allfetsel('distinct statut', 'spip_articles'), 'statut');
		foreach ($statuts as $s) {
			if (autoriser('voir', 'article', 0, ['statut' => $statut_auteur], ['statut' => $s])) {
				$auth[$statut_auteur][] = $s;
			}
		}
	}

	return $auth[$statut_auteur];
}

/**
 * Traduire le statut technique de l'auteur en langage compréhensible
 *
 * Si $statut=='nouveau' et que le statut en attente est fourni,
 * le prendre en compte en affichant que l'auteur est en attente
 *
 * @param string $statut
 * @param string $attente
 * @return string
 */
function traduire_statut_auteur($statut, $attente = '') {
	$plus = '';
	if ($statut == 'nouveau') {
		if ($attente) {
			$statut = $attente;
			$plus = ' (' . _T('info_statut_auteur_a_confirmer') . ')';
		} else {
			return _T('info_statut_auteur_a_confirmer');
		}
	}

	$recom = [
		'info_administrateurs' => _T('item_administrateur_2'),
		'info_redacteurs' => _T('intem_redacteur'),
		'info_visiteurs' => _T('item_visiteur'),
		'5poubelle' => _T('texte_statut_poubelle'), // bouh
	];
	if (isset($recom[$statut])) {
		return $recom[$statut] . $plus;
	}

	// retrouver directement par le statut sinon
	if ($t = array_search($statut, $GLOBALS['liste_des_statuts'])) {
		if (isset($recom[$t])) {
			return $recom[$t] . $plus;
		}

		return _T($t) . $plus;
	}

	// si on a pas reussi a le traduire, retournons la chaine telle quelle
	// c'est toujours plus informatif que rien du tout
	return $statut;
}

/**
 * Afficher la mention des autres auteurs ayant modifié un objet
 *
 * @param int $id_objet
 * @param string $objet
 * @return string
 */
function afficher_qui_edite($id_objet, $objet): string {
	static $qui = [];
	if (isset($qui[$objet][$id_objet])) {
		return $qui[$objet][$id_objet];
	}

	include_spip('inc/config');
	if (lire_config('articles_modif', 'non') === 'non') {
		return $qui[$objet][$id_objet] = '';
	}

	include_spip('inc/drapeau_edition');
	$modif = mention_qui_edite($id_objet, $objet);
	if (!$modif) {
		return $qui[$objet][$id_objet] = '';
	}

	include_spip('base/objets');
	$infos = lister_tables_objets_sql(table_objet_sql($objet));
	if (isset($infos['texte_signale_edition'])) {
		return $qui[$objet][$id_objet] = _T($infos['texte_signale_edition'], $modif);
	}

	return $qui[$objet][$id_objet] = _T('info_qui_edite', $modif);
}

/**
 * Lister les statuts des auteurs
 *
 * @param string $quoi
 *   - redacteurs : retourne les statuts des auteurs au moins redacteur,
 *     tels que defini par AUTEURS_MIN_REDAC
 *   - visiteurs : retourne les statuts des autres auteurs, cad les visiteurs
 *     et autres statuts perso
 *   - tous : retourne tous les statuts connus
 * @param bool $en_base
 *   si true, ne retourne strictement que les status existants en base
 *   dans tous les cas, les statuts existants en base sont inclus
 * @return array
 */
function auteurs_lister_statuts($quoi = 'tous', $en_base = true): array {
	if (!defined('AUTEURS_MIN_REDAC')) {
		define('AUTEURS_MIN_REDAC', '0minirezo,1comite,5poubelle');
	}

	switch ($quoi) {
		case 'redacteurs':
			$statut = AUTEURS_MIN_REDAC;
			$statut = explode(',', (string) $statut);
			if ($en_base) {
				$check = array_column(sql_allfetsel('DISTINCT statut', 'spip_auteurs', sql_in('statut', $statut)), 'statut');
				$retire = array_diff($statut, $check);
				$statut = array_diff($statut, $retire);
			}

			return array_unique($statut);

		case 'visiteurs':
			$statut = [];
			$exclus = AUTEURS_MIN_REDAC;
			$exclus = explode(',', (string) $exclus);
			if (!$en_base) {
				// prendre aussi les statuts de la table des status qui ne sont pas dans le define
				$statut = array_diff(array_values($GLOBALS['liste_des_statuts']), $exclus);
			}
			$s_complement = array_column(
				sql_allfetsel('DISTINCT statut', 'spip_auteurs', sql_in('statut', $exclus, 'NOT')),
				'statut'
			);

			return array_unique([...$statut, ...$s_complement]);

		default:
		case 'tous':
			$statut = array_values($GLOBALS['liste_des_statuts']);
			$s_complement = array_column(
				sql_allfetsel('DISTINCT statut', 'spip_auteurs', sql_in('statut', $statut, 'NOT')),
				'statut'
			);
			$statut = [...$statut, ...$s_complement];
			if ($en_base) {
				$check = array_column(sql_allfetsel('DISTINCT statut', 'spip_auteurs', sql_in('statut', $statut)), 'statut');
				$retire = array_diff($statut, $check);
				$statut = array_diff($statut, $retire);
			}

			return array_unique($statut);
	}
}

/**
 * Déterminer la rubrique pour la création d'un objet heuristique
 *
 * Rubrique courante si possible,
 * - sinon rubrique administrée pour les admin restreint
 * - sinon première rubrique de premier niveau autorisée que l'on trouve
 *
 * @param int $id_rubrique Identifiant de rubrique (si connu)
 * @param string $objet Objet en cours de création
 * @return int             Identifiant de la rubrique dans laquelle créer l'objet
 */
function trouver_rubrique_creer_objet($id_rubrique, $objet) {

	if (!$id_rubrique && defined('_CHOIX_RUBRIQUE_PAR_DEFAUT') && _CHOIX_RUBRIQUE_PAR_DEFAUT) {
		$in = (is_countable($GLOBALS['connect_id_rubrique']) ? count($GLOBALS['connect_id_rubrique']) : 0)
			? ' AND ' . sql_in('id_rubrique', $GLOBALS['connect_id_rubrique'])
			: '';

		// on tente d'abord l'ecriture a la racine dans le cas des rubriques uniquement
		if ($objet == 'rubrique') {
			$id_rubrique = 0;
		} else {
			$id_rubrique = sql_getfetsel('id_rubrique', 'spip_rubriques', "id_parent=0$in", '', 'id_rubrique DESC', 1);
		}

		if (!autoriser("creer{$objet}dans", 'rubrique', $id_rubrique)) {
			// manque de chance, la rubrique n'est pas autorisee, on cherche un des secteurs autorises
			$res = sql_select('id_rubrique', 'spip_rubriques', 'id_parent=0');
			while (!autoriser("creer{$objet}dans", 'rubrique', $id_rubrique) && $row_rub = sql_fetch($res)) {
				$id_rubrique = $row_rub['id_rubrique'];
			}
		}
	}

	return $id_rubrique;
}

/**
 * Afficher le lien de redirection d'un article virtuel si il y a lieu
 * (rien si l'article n'est pas redirige)
 *
 * @param string $virtuel
 * @return string
 */
function lien_article_virtuel($virtuel) {
	include_spip('inc/lien');
	if (!$virtuel = virtuel_redirige($virtuel)) {
		return '';
	}

	$joli = $virtuel;
	$parts = parse_url($virtuel);
	if (!empty($parts['query']) && str_contains($parts['query'], ']')) {
		$query = str_replace(['[', ']'], [urlencode('['), urlencode(']')], $parts['query']);
		$virtuel = str_replace('?' . $parts['query'], "?$query", $virtuel);
	}
	if ($virtuel !== $joli) {
		$joli = propre('[' . $joli . ' -> ' . $virtuel . ']');
	}
	else {
		$joli = propre('[->' . $virtuel . ']');
	}

	return $joli;
}


/**
 * Filtre pour générer un lien vers un flux RSS privé
 *
 * Le RSS est protegé par un hash de faible sécurité
 *
 * @param string $op
 * @param array $args
 * @param string $lang
 * @param string $title
 * @return string
 *     Code HTML du lien
 *@uses generer_url_api_low_sec()
 * @example
 *     - `[(#VAL{a_suivre}|bouton_spip_rss)]`
 *     - `[(#VAL{signatures}|bouton_spip_rss{#ARRAY{id_article,#ID_ARTICLE}})]`
 *
 * @filtre
 */
function bouton_spip_rss($op, $args = [], $lang = '', $title = 'RSS') {
	include_spip('inc/acces');
	$clic = http_img_pack('rss-16.png', 'RSS', '', $title);

	$url = generer_url_api_low_sec('transmettre', 'rss', $op, '', http_build_query($args), false, true);
	return "<a style='float: " . $GLOBALS['spip_lang_right'] . ";' href='$url'>$clic</a>";
}


/**
 * Vérifier la présence d'alertes pour les auteur
 *
 * @param int $id_auteur
 * @return string
 */
function alertes_auteur($id_auteur): string {

	$alertes = [];

	if (
		isset($GLOBALS['meta']['message_crash_tables'])
		&& autoriser('detruire', null, null, $id_auteur)
	) {
		include_spip('genie/maintenance');
		if ($msg = message_crash_tables()) {
			$alertes[] = $msg;
		}
	}

	if (
		isset($GLOBALS['meta']['message_crash_plugins'])
		&& $GLOBALS['meta']['message_crash_plugins']
		&& autoriser('configurer', '_plugins', null, $id_auteur)
		&& is_array($msg = unserialize($GLOBALS['meta']['message_crash_plugins']))
	) {
		$msg = implode(', ', array_map('joli_repertoire', array_keys($msg)));
		$alertes[] = _T('plugins_erreur', ['plugins' => $msg]);
	}

	$a = $GLOBALS['meta']['message_alertes_auteurs'] ?? '';
	if ($a && is_array($a = unserialize($a)) && count($a)) {
		$update = false;
		if (isset($a[$GLOBALS['visiteur_session']['statut']])) {
			$alertes = array_merge($alertes, $a[$GLOBALS['visiteur_session']['statut']]);
			unset($a[$GLOBALS['visiteur_session']['statut']]);
			$update = true;
		}
		if (isset($a[''])) {
			$alertes = array_merge($alertes, $a['']);
			unset($a['']);
			$update = true;
		}
		if ($update) {
			ecrire_meta('message_alertes_auteurs', serialize($a));
		}
	}

	if (
		isset($GLOBALS['meta']['plugin_erreur_activation'])
		&& autoriser('configurer', '_plugins', null, $id_auteur)
	) {
		include_spip('inc/plugin');
		$alertes[] = plugin_donne_erreurs();
	}

	$alertes = pipeline(
		'alertes_auteur',
		[
			'args' => [
				'id_auteur' => $id_auteur,
				'exec' => _request('exec'),
			],
			'data' => $alertes
		]
	);

	if ($alertes = array_filter($alertes)) {
		return "<div class='wrap-messages-alertes'><div class='messages-alertes'>" .
		implode(' | ', $alertes)
		. '</div></div>';
	}

	return '';
}

/**
 * Filtre pour afficher les rubriques enfants d'une rubrique
 *
 * @param int $id_rubrique
 * @return string
 */
function filtre_afficher_enfant_rub_dist($id_rubrique) {
	include_spip('inc/presenter_enfants');

	return afficher_enfant_rub((int) $id_rubrique);
}

/**
 * Afficher un petit "i" pour lien vers autre page
 *
 * @param string $lien
 *    URL du lien desire
 * @param string $titre
 *    Titre au survol de l'icone pointant le lien
 * @param string $titre_lien
 *    Si present, ajoutera en plus apres l'icone
 *    un lien simple, vers la meme URL,
 *    avec le titre indique
 *
 * @return string
 */
function afficher_plus_info($lien, $titre = '+', $titre_lien = '') {
	$titre = attribut_html($titre);
	$icone = "\n<a href='$lien' title='$titre' class='plus_info'>" .
		http_img_pack('information-16.png', $titre) . '</a>';

	if (!$titre_lien) {
		return $icone;
	} else {
		return $icone . "\n<a href='$lien'>$titre_lien</a>";
	}
}




/**
 * Lister les id objet_source associés à l'objet id_objet
 * via la table de lien objet_lien
 *
 * Utilisé pour les listes de #FORMULAIRE_EDITER_LIENS
 *
 * @param string $objet_source
 * @param string $objet
 * @param int $id_objet
 * @param string $objet_lien
 * @return array
 */
function lister_objets_lies($objet_source, $objet, $id_objet, $objet_lien) {
	$res = lister_objets_liens($objet_source, $objet, $id_objet, $objet_lien);
	if (!(is_countable($res) ? count($res) : 0)) {
		return [];
	}
	$r = reset($res);
	$colonne_id = ($objet_source == $objet_lien ? id_table_objet($objet_source) : 'id_objet');
	if (isset($r['rang_lien'])) {
		$l = array_column($res, 'rang_lien', $colonne_id);
		asort($l);
		$l = array_keys($l);
	} else {
		// Si les liens qu'on cherche sont ceux de la table de lien, l'info est dans la clé de l'objet
		// Sinon c'est dans "id_objet"
		$l = array_column($res, $colonne_id);
	}
	return $l;
}
