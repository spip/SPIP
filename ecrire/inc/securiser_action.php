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
 * Gestion des actions sécurisées
 *
 * @package SPIP\Core\Actions
 **/

 use Spip\Chiffrer\SpipCles;

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Génère ou vérifie une action sécurisée
 *
 * Interface d'appel:
 *
 * - au moins un argument: retourne une URL ou un formulaire securisés
 * - sans argument : vérifie la sécurité et retourne `_request('arg')`, ou exit.
 *
 * @uses securiser_action_auteur() Pour produire l'URL ou le formulaire
 * @example
 *     Tester une action reçue et obtenir son argument :
 *     ```
 *     $securiser_action = charger_fonction('securiser_action');
 *     $arg = $securiser_action();
 *     ```
 *
 * @param string $action
 * @param string $arg
 * @param string $redirect
 * @param bool|int|string $mode
 *   - -1 : renvoyer action, arg et hash sous forme de array()
 *   - true ou false : renvoyer une url, avec &amp; (false) ou & (true)
 *   - string : renvoyer un formulaire
 * @param string|int $att
 *   id_auteur pour lequel generer l'action en mode url ou array()
 *   atributs du formulaire en mode formulaire
 * @param bool $public
 * @return array|string
 */
function inc_securiser_action_dist($action = '', $arg = '', $redirect = '', $mode = false, $att = '', $public = false) {
	if ($action) {
		return securiser_action_auteur($action, $arg, $redirect, $mode, $att, $public);
	} else {
		$arg = _request('arg');
		$hash = _request('hash');
		$action = _request('action') ?: _request('formulaire_action');
		if ($a = verifier_action_auteur("$action-$arg", $hash)) {
			return $arg;
		}
		include_spip('inc/minipres');
		echo minipres();
		exit;
	}
}

/**
 * Confirmer avant suppression si on arrive par un bouton action
 * a appeler dans la fonction action avant toute action destructrice
 *
 * demander_confirmation_avant_action("Supprimer l'article xxxx", "Oui je veux le supprimer");
 *
 * L'action affiche le formulaire de demande de confirmation sans rendre la main au premier appel,
 * si l'utilisateur clique, cela relance l'action avec un confirm et quand on repasse ici, la fonction ne fera rien et l'action se finira normalement
 *
 * @param string $titre
 * @param string $titre_bouton
 * @param string|null $url_action
 * @return bool
 */
function demander_confirmation_avant_action($titre, $titre_bouton, $url_action = null) {

	if (!$url_action) {
		$url_action = self();
		$action = _request('action');
		$url_action = parametre_url($url_action, 'action', $action, '&');
	}
	else {
		$action = parametre_url($url_action, 'action');
	}

	$arg = parametre_url($url_action, 'arg');
	$confirm = md5("$action:$arg:" . realpath(__FILE__));
	if (_request('confirm_action') === $confirm) {
		return true;
	}

	$url_confirm = parametre_url($url_action, 'confirm_action', $confirm, '&');
	include_spip('inc/filtres');
	$bouton_action = bouton_action($titre_bouton, $url_confirm);
	$corps = "<div style='text-align:center;'>$bouton_action</div>";

	include_spip('inc/minipres');
	echo minipres($titre, $corps);
	exit;
}

/**
 * Retourne une URL ou un formulaire sécurisés
 *
 * @note
 *   Attention: PHP applique urldecode sur $_GET mais pas sur $_POST
 *   cf http://fr.php.net/urldecode#48481
 *
 * @uses calculer_action_auteur()
 * @uses generer_form_action()
 *
 * @param string $action
 * @param string $arg
 * @param string $redirect
 * @param bool|int|string $mode
 *   - -1 : renvoyer action, arg et hash sous forme de array()
 *   - true ou false : renvoyer une url, avec &amp; (false) ou & (true)
 *   - string : renvoyer un formulaire
 * @param string|int $att
 *   - id_auteur pour lequel générer l'action en mode URL ou array()
 *   - atributs du formulaire en mode formulaire
 * @param bool $public
 * @return array|string
 *    - string URL, si $mode = true ou false,
 *    - string code HTML du formulaire, si $mode texte,
 *    - array Tableau (action=>x, arg=>x, hash=>x) si $mode=-1.
 */
function securiser_action_auteur($action, $arg, $redirect = '', $mode = false, $att = '', $public = false) {

	// mode URL ou array
	if (!is_string($mode)) {
		$hash = calculer_action_auteur("$action-$arg", is_numeric($att) ? $att : null);

		$r = rawurlencode($redirect);
		if ($mode === -1) {
			return ['action' => $action, 'arg' => $arg, 'hash' => $hash];
		} else {
			return generer_url_action(
				$action,
				'arg=' . rawurlencode($arg) . "&hash=$hash" . ($r ? "&redirect=$r" : ''),
				$mode,
				$public
			);
		}
	}

	// mode formulaire
	$hash = calculer_action_auteur("$action-$arg");
	$att .= " style='margin: 0px; border: 0px'";
	if ($redirect) {
		$redirect = "\n\t\t<input name='redirect' type='hidden' value='" . str_replace("'", '&#39;', $redirect) . "' />";
	}
	$mode .= $redirect . "
<input name='hash' type='hidden' value='$hash' />
<input name='arg' type='hidden' value='$arg' />";

	return generer_form_action($action, $mode, $att, $public);
}

/**
 * Caracteriser un auteur : l'auteur loge si $id_auteur=null
 *
 * @param int|null $id_auteur
 * @return array
 */
function caracteriser_auteur($id_auteur = null) {
	static $caracterisation = [];

	if (is_null($id_auteur) && !isset($GLOBALS['visiteur_session']['id_auteur'])) {
		// si l'auteur courant n'est pas connu alors qu'il peut demander une action
		// c'est une connexion par php_auth ou 1 instal, on se rabat sur le cookie.
		// S'il n'avait pas le droit de realiser cette action, le hash sera faux.
		if (
			isset($_COOKIE['spip_session'])
			&& preg_match('/^(\d+)/', $_COOKIE['spip_session'], $r)
		) {
			return [$r[1], ''];
			// Necessaire aux forums anonymes.
			// Pour le reste, ca echouera.
		} else {
			return ['0', ''];
		}
	}
	// Eviter l'acces SQL si le pass est connu de PHP
	if (is_null($id_auteur)) {
		$id_auteur = $GLOBALS['visiteur_session']['id_auteur'] ?? 0;
		if (isset($GLOBALS['visiteur_session']['pass']) && $GLOBALS['visiteur_session']['pass']) {
			return $caracterisation[$id_auteur] = [$id_auteur, $GLOBALS['visiteur_session']['pass']];
		}
	}

	if (isset($caracterisation[$id_auteur])) {
		return $caracterisation[$id_auteur];
	}

	if ($id_auteur) {
		include_spip('base/abstract_sql');
		$t = sql_fetsel('id_auteur, pass', 'spip_auteurs', "id_auteur=$id_auteur");
		if ($t) {
			return $caracterisation[$id_auteur] = [$t['id_auteur'], $t['pass']];
		}
		include_spip('inc/minipres');
		echo minipres();
		exit;
	} // Visiteur anonyme, pour ls forums par exemple
	else {
		return ['0', ''];
	}
}

/**
 * Calcule une cle securisee pour une action et un auteur donnes
 * utilisee pour generer des urls personelles pour executer une action qui modifie la base
 * et verifier la legitimite de l'appel a l'action
 */
function _action_auteur(string $action, int $id_auteur, #[\SensitiveParameter] ?string $pass, string $alea): string {
	static $sha = [];
	$pass ??= '';
	$entry = "$action:$id_auteur:$pass:$alea";
	if (!isset($sha[$entry])) {
		$sha[$entry] = hash_hmac('sha256', "$action::$id_auteur", "$pass::" . _action_get_alea($alea));
	}

	return $sha[$entry];
}

function _action_get_alea(string $alea): string {
	if (!isset($GLOBALS['meta'][$alea])) {
		$exec = _request('exec');
		if (!$exec || !autoriser_sans_cookie($exec)) {
			include_spip('inc/acces');
			charger_aleas();
			if (empty($GLOBALS['meta'][$alea])) {
				include_spip('inc/minipres');
				echo minipres();
				spip_log("$alea indisponible");
				exit;
			}
		}
	}
	return $GLOBALS['meta'][$alea] ?? '';
}

/**
 * Calculer le hash qui signe une action pour un auteur
 *
 * @param string $action
 * @param int|null $id_auteur
 * @return string
 */
function calculer_action_auteur($action, $id_auteur = null) {
	[$id_auteur, $pass] = caracteriser_auteur($id_auteur);

	return _action_auteur($action, $id_auteur, $pass, 'alea_ephemere');
}


/**
 * Verifier le hash de signature d'une action
 * toujours exclusivement pour l'auteur en cours
 *
 * @param $action
 * @param $hash
 * @return bool
 */
function verifier_action_auteur($action, $hash) {
	[$id_auteur, $pass] = caracteriser_auteur();
 	return hash_equals($hash, _action_auteur($action, $id_auteur, $pass, 'alea_ephemere'))
		|| hash_equals($hash, _action_auteur($action, $id_auteur, $pass, 'alea_ephemere_ancien'));
}

//
// Des fonctions independantes du visiteur, qui permettent de controler
// par exemple que l'URL d'un document a la bonne cle de lecture
//

/**
 * Renvoyer le secret du site (le generer si il n'existe pas encore)
 *
 * @uses SpipCles::secret_du_site()
 * @return string
 */
function secret_du_site() {
	return SpipCles::secret_du_site();
}

/**
 * Calculer une signature valable pour une action et pour le site
 *
 * @param string $action
 * @return string
 */
function calculer_cle_action($action) {
	return hash_hmac('sha256', $action, secret_du_site());
}

/**
 * Verifier la cle de signature d'une action valable pour le site
 *
 * @param string $action
 * @param string $cle
 * @return bool
 */
function verifier_cle_action($action, #[\SensitiveParameter] $cle) {
	return hash_equals($cle, calculer_cle_action($action));
}


/**
 * Calculer le token de prévisu
 *
 * Il permettra de transmettre une URL publique d’un élément non encore publié,
 * pour qu’une personne tierce le relise. Valable quelques temps.
 *
 * @see verifier_token_previsu()
 * @param string $url Url à autoriser en prévisu
 * @param int|null id_auteur qui génère le token de prévisu. Null utilisera auteur courant.
 * @param string $alea Nom de l’alea à utiliser
 * @return string Token, de la forme "{id}*{hash}"
 */
function calculer_token_previsu($url, $id_auteur = null, $alea = 'alea_ephemere') {
	if (is_null($id_auteur) && !empty($GLOBALS['visiteur_session']['id_auteur'])) {
		$id_auteur = $GLOBALS['visiteur_session']['id_auteur'];
	}
	if (!$id_auteur = (int) $id_auteur) {
		return '';
	}
	// On nettoie l’URL de tous les var_.
	$url = nettoyer_uri_var($url);

	$token = _action_auteur('previsualiser-' . $url, $id_auteur, secret_du_site(), $alea);
	return "$id_auteur-$token";
}


/**
 * Vérifie un token de prévisu
 *
 * Découpe le token pour avoir l’id_auteur,
 * Retrouve à partir de l’url un objet/id_objet en cours de parcours
 * Recrée un token pour l’auteur et l’objet trouvé et le compare au token.
 *
 * @see calculer_token_previsu()
 * @param string $token Token, de la forme '{id}*{hash}'
 * @return false|array
 *     - `False` si echec,
 *     + Tableau (id auteur, type d’objet, id_objet) sinon.
 */
function verifier_token_previsu(#[\SensitiveParameter] $token) {
	// retrouver auteur / hash
	$e = explode('-', $token, 2);
	if (count($e) == 2 && is_numeric(reset($e))) {
		$id_auteur = (int) reset($e);
	} else {
		return false;
	}

	// calculer le type et id de l’url actuelle
	include_spip('inc/urls');
	include_spip('inc/filtres_mini');
	$url = url_absolue(self());

	// verifier le token
	$_token = calculer_token_previsu($url, $id_auteur, 'alea_ephemere');
	if (!$_token || !hash_equals($token, $_token)) {
		$_token = calculer_token_previsu($url, $id_auteur, 'alea_ephemere_ancien');
		if (!$_token || !hash_equals($token, $_token)) {
			return false;
		}
	}

	return [
		'id_auteur' => $id_auteur,
	];
}

/**
 * Décrire un token de prévisu en session
 * @uses verifier_token_previsu()
 * @return bool|array
 */
function decrire_token_previsu() {
	static $desc = null;
	if (is_null($desc)) {
		$desc = ($token = _request('var_previewtoken')) ? verifier_token_previsu($token) : false;
	}
	return $desc;
}
