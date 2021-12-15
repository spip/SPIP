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
 * Gestion des URLS
 *
 * @package SPIP\Core\URLs
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}
include_spip('base/objets');

/**
 * Décoder une URL en utilisant les fonctions inverses
 *
 * Gère les URLs transformées par le htaccess.
 *
 * @note
 *   `$renommer = 'urls_propres_dist';`
 *   renvoie `array($contexte, $type, $url_redirect, $nfond)`
 *
 *   `$nfond` n'est retourné que si l'URL est définie apres le `?`
 *   et risque d'être effacée par un form en get.
 *   Elle est utilisée par form_hidden exclusivement.
 *
 *   Compat ascendante si le retour est NULL en gérant une sauvegarde/restauration
 *   des globales modifiées par les anciennes fonctions
 *
 * @param string $url
 *   URL à décoder
 * @param string $fond
 *   Fond initial par défaut
 * @param array $contexte
 *   Contexte initial à prendre en compte
 * @param bool $assembler
 *   `true` si l'URL correspond à l'URL principale de la page qu'on est en train d'assembler
 *   dans ce cas la fonction redirigera automatiquement si besoin
 *   et utilisera les eventuelles globales `$_SERVER['REDIRECT_url_propre']` et `$_ENV['url_propre']`
 *   provenant du htaccess
 * @return array
 *   Liste `$fond, $contexte, $url_redirect`.
 *
 *   Si l'url n'est pas valide, $fond restera à la valeur initiale passée.
 *   Il suffit d'appeler la fonction sans $fond et de vérifier qu'à son retour celui-ci
 *   est non vide pour vérifier une URL
 *
 */
function urls_decoder_url($url, $fond = '', $contexte = [], $assembler = false) {
	static $current_base = null;

	// les anciennes fonctions modifient directement les globales
	// on les sauve avant l'appel, et on les retablit apres !
	$save = [
		isset($GLOBALS['fond']) ? $GLOBALS['fond'] : null,
		isset($GLOBALS['contexte']) ? $GLOBALS['contexte'] : null,
		isset($_SERVER['REDIRECT_url_propre']) ? $_SERVER['REDIRECT_url_propre'] : null,
		isset($_ENV['url_propre']) ? $_ENV['url_propre'] : null,
		$GLOBALS['profondeur_url']
	];

	if (is_null($current_base)) {
		include_spip('inc/filtres_mini');
		// le decodage des urls se fait toujours par rapport au site public
		$current_base = url_absolue(_DIR_RACINE ? _DIR_RACINE : './');
	}
	if (strncmp($url, $current_base, strlen($current_base)) == 0) {
		$url = substr($url, strlen($current_base));
	}

	// si on est en train d'assembler la page principale,
	// recuperer l'url depuis les globales url propres si fournies
	// sinon extraire la bonne portion d'url
	if ($assembler) {
		if (isset($_SERVER['REDIRECT_url_propre'])) {
			$url = $_SERVER['REDIRECT_url_propre'];
		} elseif (isset($_ENV['url_propre'])) {
			$url = $_ENV['url_propre'];
		} else {
			$qs = explode('?', $url);
			// ne prendre que le segment d'url qui correspond, en fonction de la profondeur calculee
			$url = ltrim($qs[0], '/');
			$url = explode('/', $url);
			while (count($url) > $GLOBALS['profondeur_url'] + 1) {
				array_shift($url);
			}
			$qs[0] = implode('/', $url);
			$url = implode('?', $qs);
		}
	}

	unset($_SERVER['REDIRECT_url_propre']);
	unset($_ENV['url_propre']);
	include_spip('inc/filtres_mini');
	if (strpos($url, '://') === false) {
		$GLOBALS['profondeur_url'] = substr_count(ltrim(resolve_path("/$url"), '/'), '/');
	} else {
		$GLOBALS['profondeur_url'] = max(0, substr_count($url, '/') - substr_count($current_base, '/'));
	}

	$url_redirect = '';
	$renommer = generer_url_entite('', '', '', '', true);
	if (!$renommer and !function_exists('recuperer_parametres_url')) {
		$renommer = charger_fonction('page', 'urls');
	} // fallback pour decoder l'url
	if ($renommer) {
		$a = $renommer($url, $fond, $contexte);
		if (is_array($a)) {
			list($ncontexte, $type, $url_redirect, $nfond) = array_pad($a, 4, null);
			if ($url_redirect == $url) {
				$url_redirect = '';
			} // securite pour eviter une redirection infinie
			if ($assembler and strlen($url_redirect)) {
				spip_log("Redirige $url vers $url_redirect");
				include_spip('inc/headers');
				redirige_par_entete($url_redirect, '', 301);
			}
			if (isset($nfond)) {
				$fond = $nfond;
			} else {
				if (
					$fond == ''
					or $fond == 'type_urls' /* compat avec htaccess 2.0.0 */
				) {
					$fond = $type;
				}
			}
			if (isset($ncontexte)) {
				$contexte = $ncontexte;
			}
			if (defined('_DEFINIR_CONTEXTE_TYPE') and _DEFINIR_CONTEXTE_TYPE) {
				$contexte['type'] = $type;
			}
			if (!defined('_DEFINIR_CONTEXTE_TYPE_PAGE') or _DEFINIR_CONTEXTE_TYPE_PAGE) {
				$contexte['type-page'] = $type;
			}
		}
	} // compatibilite <= 1.9.2
	elseif (function_exists('recuperer_parametres_url')) {
		$GLOBALS['fond'] = $fond;
		$GLOBALS['contexte'] = $contexte;
		recuperer_parametres_url($fond, nettoyer_uri());
		// fond est en principe modifiee directement
		$contexte = $GLOBALS['contexte'];
	}

	// retablir les globales
	list($GLOBALS['fond'], $GLOBALS['contexte'], $_SERVER['REDIRECT_url_propre'], $_ENV['url_propre'], $GLOBALS['profondeur_url']) = $save;

	// vider les globales url propres qui ne doivent plus etre utilisees en cas
	// d'inversion url => objet
	// maintenir pour compat ?
	#if ($assembler) {
	#	unset($_SERVER['REDIRECT_url_propre']);
	#	unset($_ENV['url_propre']);
	#}

	return [$fond, $contexte, $url_redirect];
}


/**
 * Lister les objets pris en compte dans les URLs
 * c'est à dire suceptibles d'avoir une URL propre
 *
 * @param bool $preg
 *  Permet de définir si la fonction retourne une chaine avec `|` comme séparateur
 *  pour utiliser en preg, ou un array()
 * @return string|array
 */
function urls_liste_objets($preg = true) {
	static $url_objets = null;
	if (is_null($url_objets)) {
		$url_objets = [];
		// recuperer les tables_objets_sql declarees
		$tables_objets = lister_tables_objets_sql();
		foreach ($tables_objets as $t => $infos) {
			if ($infos['page']) {
				$url_objets[] = $infos['type'];
				$url_objets = array_merge($url_objets, $infos['type_surnoms']);
			}
		}
		$url_objets = pipeline('declarer_url_objets', $url_objets);
	}
	if (!$preg) {
		return $url_objets;
	}

	return implode('|', array_map('preg_quote', $url_objets));
}

/**
 * Nettoyer une URL, en repérant notamment les raccourcis d'objets
 *
 * Repère les entités comme `?article13`, `?rubrique21` ...
 * les traduisant pour compléter le contexte fourni en entrée
 *
 * @param string $url
 * @param array $contexte
 * @return array
 */
function nettoyer_url_page($url, $contexte = []) {
	$url_objets = urls_liste_objets();
	$raccourci_url_page_html = ',^(?:[^?]*/)?(' . $url_objets . ')([0-9]+)(?:\.html)?([?&].*)?$,';
	$raccourci_url_page_id = ',^(?:[^?]*/)?(' . $url_objets . ')\.php3?[?]id_\1=([0-9]+)([?&].*)?$,';
	$raccourci_url_page_spip = ',^(?:[^?]*/)?(?:spip[.]php)?[?](' . $url_objets . ')([0-9]+)=?(&.*)?$,';

	if (
		preg_match($raccourci_url_page_html, $url, $regs)
		or preg_match($raccourci_url_page_id, $url, $regs)
		or preg_match($raccourci_url_page_spip, $url, $regs)
	) {
		$regs = array_pad($regs, 4, null);
		$type = objet_type($regs[1]);
		$_id = id_table_objet($type);
		$contexte[$_id] = $regs[2];
		$suite = $regs[3];

		return [$contexte, $type, null, $type, $suite];
	}

	return [];
}

/**
 * Générer l'URL d'un objet dans l'espace privé
 *
 * L'URL est calculée en fonction de son état publié ou non,
 * calculé à partir de la déclaration de statut.
 *
 * @param string $objet Type d'objet
 * @param int $id Identifiant de l'objet
 * @param string $args
 * @param string $ancre
 * @param bool|null $public
 * @param string $connect
 * @return string
 *
 */
function generer_url_ecrire_objet($objet, $id, $args = '', $ancre = '', $public = null, string $connect = '') {
	static $furls = [];
	if (!isset($furls[$objet])) {
		if (
			function_exists($f = 'generer_url_ecrire_' . $objet)
			// ou definie par un plugin
			or $f = charger_fonction($f, 'urls', true)
		) {
			$furls[$objet] = $f;
		} else {
			$furls[$objet] = '';
		}
	}
	if ($furls[$objet]) {
		return $furls[$objet]($id, $args, $ancre, $public, $connect);
	}
	// si pas de flag public fourni
	// le calculer en fonction de la declaration de statut
	if (is_null($public) and !$connect) {
		$public = objet_test_si_publie($objet, $id, $connect);
	}
	if ($public or $connect) {
		return generer_url_entite_absolue($id, $objet, $args, $ancre, $connect);
	}
	$a = id_table_objet($objet) . '=' . intval($id);
	if (!function_exists('objet_info')) {
		include_spip('inc/filtres');
	}

	return generer_url_ecrire(objet_info($objet, 'url_voir'), $a . ($args ? "&$args" : '')) . ($ancre ? "#$ancre" : '');
}
