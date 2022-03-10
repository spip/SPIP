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
 * Fonctions spécifiques au squelette
 *
 * @package SPIP\Core\Fonctions
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Compter les articles publies lies a un auteur, dans une boucle auteurs
 * pour la vue prive/liste/auteurs.html
 *
 * @param string $idb
 * @param array $boucles
 * @param Critere $crit
 * @param bool $left
 */
function critere_compteur_articles_filtres_dist($idb, &$boucles, $crit, $left = false) {
	$boucle = &$boucles[$idb];

	$_statut = calculer_liste($crit->param[0], [], $boucles, $boucle->id_parent);

	$not = '';
	if ($crit->not) {
		$not = ", 'NOT'";
	}
	$boucle->from['LAA'] = 'spip_auteurs_liens';
	$boucle->from_type['LAA'] = 'left';
	$boucle->join['LAA'] = ["'auteurs'", "'id_auteur'", "'id_auteur'", "'LAA.objet=\'article\''"];

	$boucle->from['articles'] = 'spip_articles';
	$boucle->from_type['articles'] = 'left';
	$boucle->join['articles'] = [
		"'LAA'",
		"'id_article'",
		"'id_objet'",
		"'(articles.statut IS NULL OR '.sql_in('articles.statut',[_q($_statut)]$not).')'"
	];

	$boucle->select[] = 'COUNT(articles.id_article) AS compteur_articles';
	$boucle->group[] = 'auteurs.id_auteur';
}

/**
 * Compter les articles publiés liés à un auteur, dans une boucle auteurs
 * pour la vue `prive/liste/auteurs.html`
 *
 * Nécessite le critère `compteur_articles_filtres`
 *
 * @balise
 * @see critere_compteur_articles_filtres_dist()
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 */
function balise_COMPTEUR_ARTICLES_dist($p) {
	return rindex_pile($p, 'compteur_articles', 'compteur_articles_filtres');
}


/**
 * Afficher l'initiale pour la navigation par lettres
 *
 * @staticvar string $memo
 * @param string $url
 * @param string $initiale
 * @param int $compteur
 * @param int $debut
 * @param int $pas
 * @return string
 */
function afficher_initiale($url, $initiale, $compteur, $debut, $pas) {
	static $memo = null;
	static $res = [];
	$out = '';
	if (
		!$memo
		or (!$initiale and !$url)
		or ($initiale !== $memo['initiale'])
	) {
		$newcompt = intval(floor(($compteur - 1) / $pas) * $pas);
		// si fin de la pagination et une seule entree, ne pas l'afficher, ca ne sert a rien
		if (!$initiale and !$url and !$memo['compteur']) {
			$memo = null;
		}
		if ($memo) {
			$on = (($memo['compteur'] <= $debut)
				and (
					$newcompt > $debut or ($newcompt == $debut and $newcompt == $memo['compteur'])
				));
			$res[] = lien_ou_expose($memo['url'], $memo['initiale'], $on, 'lien_pagination');
		}
		if ($initiale) {
			$memo = [
				'entree' => isset($memo['entree']) ? $memo['entree'] + 1 : 0,
				'initiale' => $initiale,
				'url' => parametre_url($url, 'i', $initiale),
				'compteur' => $newcompt
			];
		}
	}
	if (!$initiale and !$url) {
		if ((is_countable($res) ? count($res) : 0) > 1) {
			$out = implode(' ', $res);
		}
		$memo = null;
		$res = [];
	}

	return $out;
}

/**
 * Calculer l'url vers la messagerie :
 * - si l'auteur accepte les messages internes et que la messagerie est activee
 * et qu'il est en ligne, on propose le lien vers la messagerie interne
 * - sinon on propose un lien vers un email si possible
 * - sinon rien
 *
 * @staticvar string $time
 * @param int $id_auteur
 * @param string $en_ligne Date
 * @param string $statut
 * @param string $imessage
 * @param string $email
 * @return string
 */
function auteur_lien_messagerie($id_auteur, $en_ligne, $statut, $imessage, $email = '') {
	static $time = null;
	if (!in_array($statut, ['0minirezo', '1comite'])) {
		return '';
	}

	if (is_null($time)) {
		$time = time();
	}
	$parti = (($time - strtotime($en_ligne)) > 15 * 60);

	if (
		$imessage != 'non' and !$parti // historique : est-ce que ca a encore un sens de limiter vu qu'on a la notification par email ?
		and $GLOBALS['meta']['messagerie_agenda'] != 'non'
	) {
		return parametre_url(parametre_url(generer_url_ecrire('message_edit', 'new=oui'), 'to', $id_auteur), 'redirect', self());
	} elseif (strlen($email) and autoriser('voir', 'auteur', $id_auteur)) {
		return 'mailto:' . $email;
	} else {
		return '';
	}
}
