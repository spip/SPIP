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
 *   contexte initial à prendre en compte
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
		$GLOBALS['fond'] ?? null,
		$GLOBALS['contexte'] ?? null,
		$_SERVER['REDIRECT_url_propre'] ?? null,
		$_ENV['url_propre'] ?? null,
		$GLOBALS['profondeur_url']
	];

	if (is_null($current_base)) {
		include_spip('inc/filtres_mini');
		// le decodage des urls se fait toujours par rapport au site public
		$current_base = url_absolue(_DIR_RACINE ?: './');
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
	$decoder = charger_fonction_url('decoder');
	if ($decoder) {
		$a = $decoder($url, $fond, $contexte);
		if (is_array($a)) {
			[$ncontexte, $type, $url_redirect, $nfond] = array_pad($a, 4, null);
			$url_redirect ??= '';
			if ($url_redirect === $url) {
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
	}

	// retablir les globales
	[$GLOBALS['fond'], $GLOBALS['contexte'], $_SERVER['REDIRECT_url_propre'], $_ENV['url_propre'], $GLOBALS['profondeur_url']] = $save;

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
 * Le bloc qui suit sert a faciliter les transitions depuis
 * le mode 'urls-propres' vers les modes 'urls-standard' et 'url-html'
 *
 * @param string $url_propre
 * @param string $entite
 * @param array $contexte
 * @return array|false|string
 */
function urls_transition_retrouver_anciennes_url_propres(string $url_propre, string $entite, array $contexte = []): array {
	if ($url_propre) {
		if ($GLOBALS['profondeur_url'] <= 0) {
			$urls_anciennes = charger_fonction_url('decoder', 'propres');
		} else {
			$urls_anciennes = charger_fonction_url('decoder', 'arbo');
		}

		if ($urls_anciennes) {
			$urls_anciennes = $urls_anciennes($url_propre, $entite, $contexte);
		}
		return $urls_anciennes ?: [];
	}

	return [];
}

/**
 * Le bloc qui suit sert a faciliter les transitions depuis
 * le mode 'urls-html/standard' vers les modes 'urls propres|arbos'
 *
 * @param string $url_propre
 * @param string $entite
 * @param array $contexte
 * @return array|false|string
 */
function urls_transition_retrouver_anciennes_url_html(string $url, string $entite, array $contexte = []): array {
	// Migration depuis anciennes URLs ?
	// traiter les injections domain.tld/spip.php/n/importe/quoi/rubrique23
	if (
		$url
		and $GLOBALS['profondeur_url'] <= 0
	) {
		$r = nettoyer_url_page($url, $contexte);
		if ($r) {
			[$contexte, $type, , , $suite] = $r;
			$_id = id_table_objet($type);
			$id_objet = $contexte[$_id];
			$url_propre = generer_objet_url($id_objet, $type);
			if (
				strlen($url_propre)
				and !strstr($url, (string) $url_propre)
				and (
					objet_test_si_publie($type, $id_objet)
					or (defined('_VAR_PREVIEW') and _VAR_PREVIEW and autoriser('voir', $type, $id_objet))
				)
			) {
				[, $hash] = array_pad(explode('#', $url_propre), 2, '');
				$args = [];
				foreach (array_filter(explode('&', $suite ?? '')) as $fragment) {
					if ($fragment != "$_id=$id_objet") {
						$args[] = $fragment;
					}
				}
				$url_redirect = generer_objet_url($id_objet, $type, join('&', array_filter($args)), $hash);

				return [$contexte, $type, $url_redirect, $type];
			}
		}
	}
	/* Fin compatibilite anciennes urls */
	return [];
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
 * @param int|string|null $id Identifiant de l'objet
 * @param string $objet Type d'objet
 * @param string $args
 * @param string $ancre
 * @param bool|null $public
 * @param string $connect
 * @return string
 */
function generer_objet_url_ecrire($id, string $objet, string $args = '', string $ancre = '', ?bool $public = null, string $connect = ''): string {
	static $furls = [];
	$id = intval($id);
	if (!isset($furls[$objet])) {
		if (
			function_exists($f = 'generer_' . $objet . '_url_ecrire')
			// ou definie par un plugin
			or $f = charger_fonction($f, 'urls', true)
			// deprecated
			or function_exists($f = 'generer_url_ecrire_' . $objet) or $f = charger_fonction($f, 'urls', true)
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
		return generer_objet_url_absolue($id, $objet, $args, $ancre, $public, '', $connect);
	}
	$a = id_table_objet($objet) . '=' . intval($id);
	if (!function_exists('objet_info')) {
		include_spip('inc/filtres');
	}

	return generer_url_ecrire(objet_info($objet, 'url_voir'), $a . ($args ? "&$args" : '')) . ($ancre ? "#$ancre" : '');
}

/**
 * @deprecated 4.1
 * @see generer_objet_url_ecrire
 */
function generer_url_ecrire_objet($objet, $id, $args = '', $ancre = '', $public = null, string $connect = '') {
	trigger_deprecation('spip', '4.1', 'Using "%s" is deprecated, use "%s" instead', __FUNCTION__, 'generer_objet_url_ecrire');
	return generer_objet_url_ecrire($id, $objet, $args, $ancre, $public, $connect);
}
