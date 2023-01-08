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
 * Ce fichier gère la balise dynamique `#FORMULAIRE_ADMIN`
 *
 * @package SPIP\Core\Formulaires
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Compile la balise dynamique `#FORMULAIRE_ADMIN` qui des boutons
 * d'administration dans l'espace public
 *
 * Cette balise permet de placer les boutons d'administrations dans un
 * endroit spécifique du site. Si cette balise n'est pas présente, les boutons
 * seront automatiquement ajoutés par SPIP si l'auteur a activé le
 * cookie de correspondance.
 *
 * @balise
 * @see f_admin()
 * @example
 *     ```
 *     #FORMULAIRE_ADMIN
 *     ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée du code compilé
 **/
function balise_FORMULAIRE_ADMIN($p) {
	return calculer_balise_dynamique($p, 'FORMULAIRE_ADMIN', []);
}

/**
 * Calculs de paramètres de contexte automatiques pour la balise FORMULAIRE_ADMIN
 *
 * On ne peut rien dire au moment de l'execution du squelette
 *
 * @param array $args
 *   - Classe CSS éventuelle
 * @param array $context_compil
 *   Tableau d'informations sur la compilation
 * @return array|string
 *   - Liste (statut, id) si un mode d'inscription est possible
 *   - chaîne vide sinon.
 */
function balise_FORMULAIRE_ADMIN_stat($args, $context_compil) {
	return $args;
}


/**
 * Retourne le squelette d'affichage et le contexte de la balise FORMULAIRE_ADMIN
 *
 * @note
 *   Les boutons admin sont mis d'autorité si absents
 *   donc une variable statique contrôle si FORMULAIRE_ADMIN a été vu.
 *
 *   Toutefois, si c'est le debuger qui appelle, il peut avoir recopié
 *   le code dans ses données et il faut le lui refournir.
 *   Pas question de recompiler: ca fait boucler !
 *   Le debuger transmet donc ses données, et cette balise y retrouve son petit.
 *
 * @param string $float
 *     Classe CSS éventuelle
 * @param string|array $debug
 *     Informations sur la page contenant une erreur de compilation
 * @return array|string
 *     Liste : Chemin du squelette, durée du cache, contexte
 **/
function balise_FORMULAIRE_ADMIN_dyn($float = '', $debug = '') {

	static $dejafait = false;

	if (empty($_COOKIE['spip_admin'])) {
		return '';
	}

	if (!is_array($debug)) {
		if ($dejafait) {
			return '';
		}
	} else {
		if ($dejafait) {
			if (empty($debug['sourcefile'])) {
				return '';
			}
			foreach ($debug['sourcefile'] as $k => $v) {
				if (strpos($v, 'administration.') !== false) {
					if (isset($debug['resultat'][$k . 'tout'])) {
						return $debug['resultat'][$k . 'tout'];
					}
				}
			}

			return '';
		}
	}

	include_spip('inc/autoriser');
	include_spip('base/abstract_sql');


	$dejafait = true;

	// Preparer le #ENV des boutons

	$env = admin_objet();

	// Pas de "modifier ce..." ? -> donner "acces a l'espace prive"
	if (!$env) {
		$env['ecrire'] = _DIR_RESTREINT_ABS;
	}

	$env['divclass'] = $float;
	$env['lang'] = admin_lang();
	$env['calcul'] = (_request('var_mode') ? 'recalcul' : 'calcul');
	$env['debug'] = ((defined('_VAR_PREVIEW') and _VAR_PREVIEW) ? '' : admin_debug());
	$env['analyser'] = (!$env['debug'] and !$GLOBALS['xhtml']) ? '' : admin_valider();
	$env['inclure'] = ((defined('_VAR_INCLURE') and _VAR_INCLURE) ? 'inclure' : '');

	if (empty($GLOBALS['use_cache'])) {
		$env['use_cache'] = ' *';
	}

	if (isset($debug['validation'])) {
		$env['xhtml_error'] = $debug['validation'];
	}

	$env['_pipelines']['formulaire_admin'] = [];

	return ['formulaires/administration', 0, $env];
}


/**
 * Préparer le contexte d'environnement pour les boutons
 *
 * Permettra d'afficher le bouton 'Modifier ce...' s'il y a un
 * `$id_XXX` défini dans le contexte de la page
 *
 * @note
 *   Attention à l'ordre dans la boucle:
 *   on ne veut pas la rubrique si un autre bouton est possible
 *
 * @return array
 *     Tableau de l'environnement calculé
 **/
function admin_objet() {
	include_spip('inc/urls');
	$env = [];

	$trouver_table = charger_fonction('trouver_table', 'base');
	$objets = urls_liste_objets(false);
	$objets = array_diff($objets, ['rubrique']);
	$objets = array_reverse($objets);
	array_unshift($objets, 'rubrique');
	foreach ($objets as $obj) {
		$type = $obj;
		if (
			$type == objet_type($type, false)
			and $_id_type = id_table_objet($type)
			and isset($GLOBALS['contexte'][$_id_type])
			and $id = $GLOBALS['contexte'][$_id_type]
			and !is_array($id)
			and $id = intval($id)
			and $desc = $trouver_table(table_objet_sql($type))
		) {
			$id = sql_getfetsel($_id_type, table_objet_sql($type), "$_id_type=" . intval($id));
			if ($id) {
				$env[$_id_type] = $id;
				$env['objet'] = $type;
				$env['id_objet'] = $id;
				$env['voir_' . $obj] =
					str_replace('&amp;', '&', generer_objet_url($id, $obj, '', '', false));
				if (
					isset($desc['field']['id_rubrique'])
					and $type != 'rubrique'
				) {
					unset($env['id_rubrique']);
					unset($env['voir_rubrique']);
					if (admin_preview($type, $id, $desc)) {
						$env['preview'] = parametre_url(self(), 'var_mode', 'preview', '&');
					}
				}
			}
		}
	}

	return $env;
}


/**
 * Détermine si l'élément est previsualisable
 *
 * @param string $type
 *     Type d'objet
 * @param int $id
 *     Identifinant de l'objet
 * @param array|null $desc
 *     Description de la table
 * @return string|array
 *     - Chaine vide si on est déjà en prévisu ou si pas de previsualisation possible
 *     - Tableau d'un élément sinon.
 **/
function admin_preview($type, $id, $desc = null) {
	if (defined('_VAR_PREVIEW') and _VAR_PREVIEW) {
		return '';
	}

	if (!$desc) {
		$trouver_table = charger_fonction('trouver_table', 'base');
		$desc = $trouver_table(table_objet_sql($type));
	}
	if (!$desc or !isset($desc['field']['statut'])) {
		return '';
	}

	include_spip('inc/autoriser');
	if (!autoriser('previsualiser')) {
		return '';
	}

	$notpub = sql_in('statut', ['prop', 'prive']);

	if ($type == 'article' and $GLOBALS['meta']['post_dates'] != 'oui') {
		$notpub .= " OR (statut='publie' AND date>" . sql_quote(date('Y-m-d H:i:s')) . ')';
	}

	return sql_fetsel('1', table_objet_sql($type), id_table_objet($type) . '=' . $id . " AND ($notpub)");
}


/**
 * Régler les boutons dans la langue de l'admin (sinon tant pis)
 *
 * @return string
 *     Code de langue
 **/
function admin_lang() {
	$alang = '';
	if (!empty($_COOKIE['spip_admin'])) {
		$email_or_login = preg_replace(',^@,', '', $_COOKIE['spip_admin']);
		$alang = sql_getfetsel('lang', 'spip_auteurs', 'email=' . sql_quote($email_or_login));
		if (!$alang) {
			$alang = sql_getfetsel('lang', 'spip_auteurs', 'login=' . sql_quote($email_or_login));
		}
	}
	if (!$alang) {
		return '';
	}

	$l = lang_select($alang);
	$alang = $GLOBALS['spip_lang'];
	if ($l) {
		lang_select();
	}

	return $alang;
}

/**
 * Retourne une URL vers un validateur
 *
 * @return string
 **/
function admin_valider() {

	return ((!isset($GLOBALS['xhtml']) or $GLOBALS['xhtml'] !== 'true') ?
		(parametre_url(self(), 'var_mode', 'debug', '&')
			. '&var_mode_affiche=validation') :
		('http://validator.w3.org/check?uri='
			. rawurlencode('http://' . $_SERVER['HTTP_HOST'] . nettoyer_uri())));
}

/**
 * Retourne une URL vers le mode debug, si l'utilisateur a le droit, et si c'est utile
 *
 * @return string
 **/
function admin_debug() {
	return ((
			(isset($GLOBALS['forcer_debug']) and $GLOBALS['forcer_debug'])
			or (isset($GLOBALS['bouton_admin_debug']) and $GLOBALS['bouton_admin_debug'])
			or (
				defined('_VAR_MODE') and _VAR_MODE == 'debug'
				and isset($_COOKIE['spip_debug']) and $_COOKIE['spip_debug']
			)
		) and autoriser('debug')
	)
		? parametre_url(self(), 'var_mode', 'debug', '&') : '';
}
