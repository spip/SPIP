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
 * Gestion de redirection publique à la volée d'un objet éditorial en
 * recalculant au passage son URL
 *
 * @package SPIP\Core\Redirections
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Script utile pour recalculer une URL symbolique dès son changement
 *
 * Cette action est appelé par les boutons 'Voir en ligne' ou par
 * le fichier `.htaccess` activé lors d'une URL du genre : http://site/1234
 *
 * @example
 *   ```
 *   [(#VAL{redirect}
 *      |generer_url_action{type=article&id=#ID_ARTICLE}
 *      |parametre_url{var_mode,calcul}
 *      |icone_horizontale{<:icone_voir_en_ligne:>,racine})]
 *   ```
 **/
function action_redirect_dist() {
	$type = _request('type');
	$id = intval(_request('id'));
	$page = false;

	// verifier le type ou page transmis
	if (!preg_match('/^\w+$/', $type)) {
		$page = _request('page');
		if (!preg_match('/^\w+$/', $page)) {
			return;
		}
	}

	if ($var_mode = _request('var_mode')) {
		// forcer la mise a jour de l'url de cet objet !
		if (!defined('_VAR_URLS')) {
			define('_VAR_URLS', true);
		}
	}

	if ($page) {
		$url = generer_url_public($page, '', true);
	} else {
		$url = calculer_url_redirect_entite($type, $id, $var_mode);
	}

	$status = '302';
	if ($url) {
		if ($var_mode) {
			$url = parametre_url($url, 'var_mode', $var_mode, '&');
		}

		if (
			$var_mode == 'preview'
			and defined('_PREVIEW_TOKEN')
			and _PREVIEW_TOKEN
			and autoriser('previsualiser')
		) {
			include_spip('inc/securiser_action');
			$token = calculer_token_previsu($url);
			$url = parametre_url($url, 'var_previewtoken', $token);
		}

		if (_request('status') and _request('status') == '301') {
			$status = '301';
		}
	} else {
		$url = generer_url_public('404', '', true);
	}

	redirige_par_entete(str_replace('&amp;', '&', $url), '', $status);
}

/**
 * Retourne l’URL de l’objet sur lequel on doit rediriger
 *
 * On met en cache les calculs (si memoization),
 * et on ne donne pas l’URL si la personne n’y a pas accès
 *
 * @param string $type
 * @param int $id
 * @param string $var_mode
 * @return string|null
 */
function calculer_url_redirect_entite($type, $id, $var_mode) {
	// invalider le cache à chaque modif en bdd
	$date = 0;
	if (isset($GLOBALS['meta']['derniere_modif'])) {
		$date = $GLOBALS['meta']['derniere_modif'];
	}
	$key = "url-$date-$type-$id";

	// Obtenir l’url et si elle est publié du cache memoization
	if (function_exists('cache_get') and $desc = cache_get($key)) {
		list($url, $publie) = $desc;
	}
	// Si on ne l’a pas trouvé, ou si var mode, on calcule l’url et son état publie
	if (empty($desc) or $var_mode) {
		$publie = objet_test_si_publie($type, $id);
		$url = generer_url_entite_absolue($id, $type, '', '', true);
		if (function_exists('cache_set')) {
			cache_set($key, [$url, $publie], 3600);
		}
	}

	// On valide l’url si elle est publiee ; sinon si preview on teste l’autorisation
	if ($publie) {
		return $url;
	} elseif (defined('_VAR_PREVIEW') and _VAR_PREVIEW and autoriser('voir', $type, $id)) {
		return $url;
	}

	return;
}
