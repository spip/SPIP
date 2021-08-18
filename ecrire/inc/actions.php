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
 * Gestion des actions sécurisées
 *
 * @package SPIP\Core\Actions
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Retourne une URL ou un formulaire securisé
 *
 * @uses inc_securiser_action_dist()
 *
 * @param string $action
 *     Nom du fichier/action appelé (dans le répertoire action)
 * @param string $arg
 *     Arguments pour l'action sécurisée, peut etre vide
 * @param string $redirect
 *     Adresse de redirection souhaitée à la fin du bon déroulement de l’action
 * @param bool|int|string $mode
 *     - -1 : renvoyer action, arg et hash sous forme de array()
 *     - true ou false : renvoyer une url, avec `&amp;` (false) ou `&` (true)
 *     - string : renvoyer un formulaire
 * @param string|int $att
 *     - id_auteur pour lequel générer l'action en mode url ou array()
 *     - attributs du formulaire en mode formulaire
 * @param bool $public
 * @return array|string
 *     URL, code HTML du formulaire ou tableau (action, arg, hash)
 */
function generer_action_auteur($action, $arg = '', $redirect = '', $mode = false, $att = '', $public = false) {
	$securiser_action = charger_fonction('securiser_action', 'inc');

	return $securiser_action($action, $arg, $redirect, $mode, $att, $public);
}

/**
 * Génère une URL ou un formulaire dirigé vers un fichier action (action/xx.php)
 *
 * Le génère à condition que $mode="texte".
 *
 * @uses generer_action_auteur()
 *
 * @api
 * @param string $action
 *     Nom du fichier action/xx.php
 * @param string $arg
 *     Argument passé à l'action, qui sera récupéré par la fonction
 *     `securiser_action()`
 * @param string $ret
 *     Nom du script exec sur lequel on revient après l'action (redirection),
 *     que l'on peut récupérer dans une fonction d'action par `_request('redirect')`
 * @param string $gra
 *     Arguments transmis au script exec de retour `arg1=yy&arg2=zz`
 * @param bool|string|int $mode
 *     - -1 : renvoyer action, arg et hash sous forme de array()
 *     - true ou false : renvoyer une url, avec `&amp;` (false) ou `&` (true)
 *     - string : renvoyer un formulaire
 * @param string $atts ?
 * @param bool $public
 *     true produit une URL d'espace public
 *     false (par défaut) produit une URL d'espace privé
 * @return string
 *     Code HTML du formulaire
 */
function redirige_action_auteur($action, $arg, $ret, $gra = '', $mode = false, $atts = '', $public = false) {
	$r = ($public ? _DIR_RESTREINT_ABS : _DIR_RESTREINT) . generer_url_ecrire($ret, $gra, true, true);

	return generer_action_auteur($action, $arg, $r, $mode, $atts, $public);
}

/**
 * Retourne une URL ou un formulaire sécurisé en méthode POST
 *
 * @param string $action
 *     Nom du fichier/action appelé (dans le répertoire action)
 * @param string $arg
 *     Arguments pour l'action sécurisée
 * @param string $ret
 *     Adresse de redirection souhaitée à la fin du bon déroulement de l’action
 * @param string $gra
 *     Arguments à transmettre, tel que `arg1=yy&arg2=zz`
 * @param bool|int|string $corps
 *     - -1 : renvoyer action, arg et hash sous forme de array()
 *     - true ou false : renvoyer une url, avec `&amp;` (false) ou `&` (true)
 *     - string : renvoyer un formulaire
 * @param string|int $att
 *     - id_auteur pour lequel générer l'action en mode url ou array()
 *     - attributs du formulaire en mode formulaire
 * @return array|string
 *     URL, code HTML du formulaire ou tableau (action, arg, hash)
 */
function redirige_action_post($action, $arg, $ret, $gra, $corps, $att = '') {
	$r = _DIR_RESTREINT . generer_url_ecrire($ret, $gra, false, true);

	return generer_action_auteur($action, $arg, $r, $corps, $att . " method='post'");
}


/**
 * Fonction de formatage du contenu renvoyé en ajax
 *
 * @param string $corps
 * @param string $content_type
 *   permet de definir le type de contenu renvoye.
 *   Si rien de précisé, ou si true c'est "text/html" avec un entete xml en plus.
 *   La valeur speciale false fournit text/html sans entete xml. Elle equivaut a
 *   passer "text/html" comme $content_type
 */
function ajax_retour($corps, $content_type = null) {
	$xml = false;
	if (is_null($content_type) or $content_type === true) {
		$xml = true;
		$content_type = 'text/html';
	} elseif (!$content_type or !is_string($content_type) or strpos($content_type, '/') === false) {
		$content_type = 'text/html';
	}

	$e = '';
	if (isset($_COOKIE['spip_admin'])
		and ((_request('var_mode') == 'debug') or !empty($GLOBALS['tableau_des_temps']))
	) {
		$e = erreur_squelette();
	}

	$c = $GLOBALS['meta']['charset'];
	header('Content-Type: ' . $content_type . '; charset=' . $c);
	$debut = (($xml and strlen(trim($corps))) ? '<' . "?xml version='1.0' encoding='" . $c . "'?" . ">\n" : '');
	$fin = '';

	echo $debut, $corps, $fin, $e;
}
