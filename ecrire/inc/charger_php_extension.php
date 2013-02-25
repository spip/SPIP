<?php

/**
 * Chargement d'une extension PHP
 *
 * @package SPIP\Core\Outils
**/

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Permet de charger un module PHP dont le nom est donné en argument
 *
 * Fonction adaptée de phpMyAdmin.
 *
 * Trois étapes :
 *
 * 1) si le module est deja charge, on sort vainqueur
 * 2) on teste si l'on a la possibilité de charger un module
 *    via la meta `dl_allowed`. Si elle n'est pas renseignée,
 *    elle sera crée en fonction des paramètres de php
 * 3) si l'on peut, on charge le module par la fonction `dl()`
 *
 * @note
 *     La fonction `dl()` n'est plus présente à partir de PHP 5.3.
 * 
 * @param string $module
 *     Nom du module à charger (tel que 'mysql')
 * @return bool
 *     true en cas de succes
**/
function inc_charger_php_extension_dist($module){
	if (extension_loaded($module)) {
		return true;
	}

	// A-t-on le droit de faire un dl() ; si on peut, on memorise la reponse,
	// lourde a calculer, dans les meta
	if (!isset($GLOBALS['meta']['dl_allowed'])) {
		if (!@ini_get('safe_mode') 
		  && @ini_get('enable_dl')
		  && @function_exists('dl')) {
			ob_start();
			phpinfo(INFO_GENERAL); /* Only general info */
			$a = strip_tags(ob_get_contents());
			ob_end_clean();
			if (preg_match('@Thread Safety[[:space:]]*enabled@', $a)) {
				if (preg_match('@Server API[[:space:]]*\(CGI\|CLI\)@', $a)) {
					$GLOBALS['meta']['dl_allowed'] = true;
				} else {
					$GLOBALS['meta']['dl_allowed'] = false;
				}
			} else {
				$GLOBALS['meta']['dl_allowed'] = true;
			}
		} else {
			$GLOBALS['meta']['dl_allowed'] = false;
		}

		// Attention, le ecrire_meta() echouera si on le tente ici ;
		// donc on ne fait rien, et on attend qu'un prochain ecrire_meta()
		// se produisant apres cette sequence enregistre sa valeur.
		#include_spip('inc/meta');
		#ecrire_meta('dl_allowed', $GLOBALS['meta']['dl_allowed'], 'non');
	}

	if (!$GLOBALS['meta']['dl_allowed']) {
		return false;
	}

	/* Once we require PHP >= 4.3, we might use PHP_SHLIB_SUFFIX here */
	if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
		$module_file = 'php_' . $module . '.dll';
	} elseif (PHP_OS=='HP-UX') {
		$module_file = $module . '.sl';
	} else {
		$module_file = $module . '.so';
	}

	return @dl($module_file);
}
?>
