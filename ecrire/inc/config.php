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
 * Fonctions utilitaires pour le stockage et lecture de configuration
 *
 * @package SPIP\Core\Config
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Appliquer les valeurs par défaut pour les options non initialisées
 * (pour les langues c'est fait)
 *
 * @return void
 */
function inc_config_dist() {
	actualise_metas(liste_metas());
}

/**
 * Expliquer une clé de configuration
 *
 * Analyser la clé de configuration pour déterminer
 * - la table (ex: spip_metas),
 * - le casier, la clé principale (ex: dada)
 * - et le sous-casier éventuel, chemin dans la clé principale (ex: truc/muche)
 *
 * @param string $cfg
 *     Clé de configuration, tel que 'dada/truc/muche'
 * @return array
 *     Liste (table, casier, sous_casier)
 */
function expliquer_config($cfg) {
	// par defaut, sur la table des meta
	$table = 'meta';
	$casier = null;
	$sous_casier = [];
	$cfg = explode('/', $cfg);

	// si le premier argument est vide, c'est une syntaxe /table/ ou un appel vide ''
	if (!reset($cfg) and count($cfg) > 1) {
		array_shift($cfg);
		$table = array_shift($cfg);
		if (!isset($GLOBALS[$table])) {
			lire_metas($table);
		}
	}

	// si on a demande #CONFIG{/meta,'',0}
	if (count($cfg)) {
		// pas sur un appel vide ''
		if ('' !== ($c = array_shift($cfg))) {
			$casier = $c;
		}
	}

	if (count($cfg)) {
		$sous_casier = $cfg;
	}

	return [$table, $casier, $sous_casier];
}

/**
 * Lecture de la configuration
 *
 * lire_config() permet de recuperer une config depuis le php<br>
 * memes arguments que la balise (forcement)<br>
 * $cfg: la config, lire_config('montruc') est un tableau<br>
 * lire_config('/table/champ') lit le valeur de champ dans la table des meta 'spip_table'<br>
 * lire_config('montruc/sub') est l'element "sub" de cette config equivalent a lire_config('/meta/montruc/sub')<br>
 *
 * lire_config('methode::montruc/sub') delegue la lecture a methode_lire_config_dist via un charger_fonction
 * permet de brancher CFG ou autre outil externe qui etend les methodes de stockage de config
 *
 * $unserialize est mis par l'histoire
 *
 * @param string $cfg
 *    Clé de configuration
 * @param mixed $def
 *    Valeur par défaut
 * @param boolean $unserialize
 *    N'affecte que le dépôt 'meta' :
 *    True pour désérialiser automatiquement la valeur
 * @return mixed
 *    Contenu de la configuration obtenue
 */
function lire_config($cfg = '', $def = null, $unserialize = true) {
	// lire le stockage sous la forme /table/valeur
	// ou valeur qui est en fait implicitement /meta/valeur
	// ou casier/valeur qui est en fait implicitement /meta/casier/valeur

	// traiter en priorite le cas simple et frequent
	// de lecture direct $GLOBALS['meta']['truc'], si $cfg ne contient ni / ni :
	if ($cfg and strpbrk($cfg, '/:') === false) {
		$r = isset($GLOBALS['meta'][$cfg]) ?
			((!$unserialize
				// ne pas essayer de deserialiser autre chose qu'une chaine
				or !is_string($GLOBALS['meta'][$cfg])
				// ne pas essayer de deserialiser si ce n'est visiblement pas une chaine serializee
				or strpos($GLOBALS['meta'][$cfg], ':') === false
				or ($t = @unserialize($GLOBALS['meta'][$cfg])) === false) ? $GLOBALS['meta'][$cfg] : $t)
			: $def;

		return $r;
	}

	// Brancher sur methodes externes si besoin
	if ($cfg and $p = strpos($cfg, '::')) {
		$methode = substr($cfg, 0, $p);
		$lire_config = charger_fonction($methode, 'lire_config');

		return $lire_config(substr($cfg, $p + 2), $def, $unserialize);
	}

	[$table, $casier, $sous_casier] = expliquer_config($cfg);

	if (!isset($GLOBALS[$table])) {
		return $def;
	}

	$r = $GLOBALS[$table];

	// si on a demande #CONFIG{/meta,'',0}
	if (!$casier) {
		return $unserialize ? $r : serialize($r);
	}

	// casier principal :
	// le deserializer si demande
	// ou si on a besoin
	// d'un sous casier
	$r = $r[$casier] ?? null;
	if (($unserialize or is_countable($sous_casier) ? count($sous_casier) : 0) and $r and is_string($r)) {
		$r = (($t = @unserialize($r)) === false ? $r : $t);
	}

	// aller chercher le sous_casier
	while (!is_null($r) and $casier = array_shift($sous_casier)) {
		$r = $r[$casier] ?? null;
	}

	if (is_null($r)) {
		return $def;
	}

	return $r;
}

/**
 * metapack est inclue dans lire_config, mais on traite le cas ou il est explicite
 * metapack:: dans le $cfg de lire_config.
 * On renvoie simplement sur lire_config
 *
 * @param string $cfg
 * @param mixed $def
 * @param bool $unserialize
 * @return mixed
 */
function lire_config_metapack_dist($cfg = '', $def = null, $unserialize = true) {
	return lire_config($cfg, $def, $unserialize);
}


/**
 * Ecrire une configuration
 *
 * @param string $cfg
 * @param mixed $store
 * @return bool
 */
function ecrire_config($cfg, $store) {
	// Brancher sur methodes externes si besoin
	if ($cfg and $p = strpos($cfg, '::')) {
		$methode = substr($cfg, 0, $p);
		$ecrire_config = charger_fonction($methode, 'ecrire_config');

		return $ecrire_config(substr($cfg, $p + 2), $store);
	}

	[$table, $casier, $sous_casier] = expliquer_config($cfg);
	// il faut au moins un casier pour ecrire
	if (!$casier) {
		return false;
	}

	// trouvons ou creons le pointeur sur le casier
	$st = $GLOBALS[$table][$casier] ?? null;
	if (!is_array($st) and ($sous_casier or is_array($store))) {
		if ($st === null) {
			// ne rien creer si c'est une demande d'effacement
			if ($store === null) {
				return false;
			}
			$st = [];
		} else {
			$st = unserialize($st);
			if ($st === false) {
				// ne rien creer si c'est une demande d'effacement
				if ($store === null) {
					return false;
				}
				$st = [];
			}
		}
	}

	$has_planes = false;
	// si on a affaire a un sous caiser
	// il faut ecrire au bon endroit sans perdre les autres sous casier freres
	if ($c = $sous_casier) {
		$sc = &$st;
		$pointeurs = [];
		while (is_countable($c) ? count($c) : 0 and $cc = array_shift($c)) {
			// creer l'entree si elle n'existe pas
			if (!isset($sc[$cc])) {
				// si on essaye d'effacer une config qui n'existe pas
				// ne rien creer mais sortir
				if (is_null($store)) {
					return false;
				}
				$sc[$cc] = [];
			}
			$pointeurs[$cc] = &$sc;
			$sc = &$sc[$cc];
		}

		// si c'est une demande d'effacement
		if (is_null($store)) {
			$c = $sous_casier;
			$sous = array_pop($c);
			// effacer, et remonter pour effacer les parents vides
			do {
				unset($pointeurs[$sous][$sous]);
			} while ($sous = array_pop($c) and !(is_countable($pointeurs[$sous][$sous]) ? count($pointeurs[$sous][$sous]) : 0));

			// si on a vide tous les sous casiers,
			// et que le casier est vide
			// vider aussi la meta
			if (!$sous and !(is_countable($st) ? count($st) : 0)) {
				$st = null;
			}
		} // dans tous les autres cas, on ecrase
		else {

			if (
				    defined('_MYSQL_NOPLANES')
				and _MYSQL_NOPLANES
				and !empty($GLOBALS['meta']['charset_sql_connexion'])
				and $GLOBALS['meta']['charset_sql_connexion'] == 'utf8'
			) {

				// detecter si la valeur qu'on veut ecrire a des planes
				// @see utf8_noplanes
				$serialized_store = (is_string($store) ? $store : serialize($store));
				// un preg_match rapide pour voir si ca vaut le coup de lancer utf8_noplanes
				if (preg_match(',[\xF0-\xF4],ms', $serialized_store)) {
					if (!function_exists('utf8_noplanes')) {
						include_spip('inc/charsets');
					}
					if ($serialized_store !== utf8_noplanes($serialized_store)) {
						$has_planes = true;
					}
				}
			}

			$sc = $store;
		}

		// Maintenant que $st est modifiee
		// reprenons la comme valeur a stocker dans le casier principal
		$store = $st;
	}

	if (is_null($store)) {
		if (is_null($st) and !$sous_casier) {
			return false;
		} // la config n'existait deja pas !
		effacer_meta($casier, $table);
		supprimer_table_meta($table); // supprimons la table (si elle est bien vide)
	} // les meta ne peuvent etre que des chaines : il faut serializer le reste
	else {
		if (!isset($GLOBALS[$table])) {
			installer_table_meta($table);
		}
		// si ce n'est pas une chaine
		// il faut serializer
		if (!is_string($store)) {
			$serialized_store = serialize($store);
			ecrire_meta($casier, $serialized_store, null, $table);
			// et dans ce cas il faut verifier que l'ecriture en base a bien eu lieu a l'identique si il y a des planes dans la chaine
			// car sinon ca casse le serialize PHP - par exemple si on est en mysql utf8 (non mb4)
			if ($has_planes) {
				$check_store = sql_getfetsel('valeur', 'spip_'.$table, 'nom='.sql_quote($casier));
				if ($check_store !== $serialized_store) {
					array_walk_recursive($store, function (&$value, $key) {if (is_string($value)) {$value = utf8_noplanes($value);}});
					$serialized_store = serialize($store);
					ecrire_meta($casier, $serialized_store, null, $table);
				}
			}
		}
		else {
			ecrire_meta($casier, $store, null, $table);
		}
	}

	// verifier que lire_config($cfg)==$store ?
	return true;
}


/**
 * metapack est inclue dans ecrire_config, mais on traite le cas ou il est explicite
 * metapack:: dans le $cfg de ecrire_config.
 * On renvoie simplement sur ecrire_config
 *
 * @param string $cfg
 * @param mixed $store
 * @return bool
 */
function ecrire_config_metapack_dist($cfg, $store) {
	// cas particulier en metapack::
	// si on ecrit une chaine deja serializee, il faut la reserializer pour la rendre
	// intacte en sortie ...
	if (is_string($store) and strpos($store, ':') and unserialize($store)) {
		$store = serialize($store);
	}

	return ecrire_config($cfg, $store);
}

/**
 * Effacer une configuration : revient a ecrire une valeur null
 *
 * @param string $cfg
 * @return bool
 */
function effacer_config($cfg) {
	ecrire_config($cfg, null);

	return true;
}

/**
 * Définir les `meta` de configuration
 *
 * @pipeline_appel configurer_liste_metas
 *
 * @uses url_de_base()
 * @uses _DEFAULT_CHARSET
 * @uses _DIR_IMG
 * @uses _DIR_RACINE
 *
 * @return array
 *    Couples nom de la `meta` => valeur par défaut
 */
function liste_metas() {
	return pipeline('configurer_liste_metas', [
		'nom_site' => _T('info_mon_site_spip'),
		'slogan_site' => '',
		'adresse_site' => preg_replace(',/$,', '', url_de_base()),
		'descriptif_site' => '',
		'activer_logos' => 'oui',
		'activer_logos_survol' => 'non',
		'articles_surtitre' => 'non',
		'articles_soustitre' => 'non',
		'articles_descriptif' => 'non',
		'articles_chapeau' => 'non',
		'articles_texte' => 'oui',
		'articles_ps' => 'non',
		'articles_redac' => 'non',
		'post_dates' => 'non',
		'articles_urlref' => 'non',
		'articles_redirection' => 'non',
		'creer_preview' => 'non',
		'taille_preview' => 150,
		'articles_modif' => 'non',

		'rubriques_descriptif' => 'non',
		'rubriques_texte' => 'oui',

		'accepter_inscriptions' => 'non',
		'accepter_visiteurs' => 'non',
		'prevenir_auteurs' => 'non',
		'suivi_edito' => 'non',
		'adresse_suivi' => '',
		'adresse_suivi_inscription' => '',
		'adresse_neuf' => '',
		'jours_neuf' => '',
		'quoi_de_neuf' => 'non',
		'preview' => ',0minirezo,1comite,',

		'syndication_integrale' => 'oui',
		'charset' => _DEFAULT_CHARSET,
		'dir_img' => substr(_DIR_IMG, strlen(_DIR_RACINE)),

		'multi_rubriques' => 'non',
		'multi_secteurs' => 'non',
		'gerer_trad' => 'non',
		'langues_multilingue' => '',

		'version_html_max' => 'html4',

		'type_urls' => 'page',

		'email_envoi' => '',
		'email_webmaster' => '',
		'auto_compress_http' => 'non',
	]);
}

/**
 * Mets les `meta` à des valeurs conventionnelles quand elles sont vides
 * et recalcule les langues
 *
 * @param array $liste_meta
 * @return void
 */
function actualise_metas($liste_meta) {
	$meta_serveur =
		[
			'version_installee',
			'adresse_site',
			'alea_ephemere_ancien',
			'alea_ephemere',
			'alea_ephemere_date',
			'langue_site',
			'langues_proposees',
			'date_calcul_rubriques',
			'derniere_modif',
			'optimiser_table',
			'drapeau_edition',
			'creer_preview',
			'taille_preview',
			'creer_htpasswd',
			'creer_htaccess',
			'gd_formats_read',
			'gd_formats',
			'netpbm_formats',
			'formats_graphiques',
			'image_process',
			'plugin_header',
			'plugin'
		];
	// verifier le impt=non
	sql_updateq('spip_meta', ['impt' => 'non'], sql_in('nom', $meta_serveur));

	foreach ($liste_meta as $nom => $valeur) {
		if (empty($GLOBALS['meta'][$nom])) {
			ecrire_meta($nom, $valeur);
		}
	}

	include_spip('inc/rubriques');
	$langues = calculer_langues_utilisees();
	ecrire_meta('langues_utilisees', $langues);
}


//
// Gestion des modifs
//

/**
 * Appliquer les modifications apportées aux `metas`
 *
 * Si `$purger_skel` est à `true`, on purge le répertoire de cache des squelettes
 *
 * @uses liste_metas()
 * @uses ecrire_meta()
 * @uses purger_repertoire()
 *
 * @param bool $purger_skel
 * @return void
 */
function appliquer_modifs_config($purger_skel = false) {

	foreach (liste_metas() as $i => $v) {
		if (($x = _request($i)) !== null) {
			ecrire_meta($i, $x);
		} elseif (!isset($GLOBALS['meta'][$i])) {
			ecrire_meta($i, $v);
		}
	}

	if ($purger_skel) {
		include_spip('inc/invalideur');
		purger_repertoire(_DIR_SKELS);
	}
}

/**
 * Mettre à jour l'adresse du site à partir d'une valeur saisie
 * (ou auto détection si vide)
 *
 * @param string $adresse_site
 * @return string
 */
function appliquer_adresse_site($adresse_site) {
	if ($adresse_site !== null) {
		if (!strlen($adresse_site)) {
			$GLOBALS['profondeur_url'] = _DIR_RESTREINT ? 0 : 1;
			$adresse_site = url_de_base();
		}
		$adresse_site = preg_replace(',/?\s*$,', '', $adresse_site);

		if (!tester_url_absolue($adresse_site)) {
			$adresse_site = "http://$adresse_site";
		}

		$adresse_site = entites_html($adresse_site);

		ecrire_meta('adresse_site', $adresse_site);
	}

	return $adresse_site;
}
