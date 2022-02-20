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
 * Gestion de l'activation des plugins
 *
 * @package SPIP\Core\Plugins
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/** l'adresse du repertoire de telechargement et de decompactage des plugins */
if (!defined('_DIR_PLUGINS_AUTO')) {
	define('_DIR_PLUGINS_AUTO', _DIR_PLUGINS . 'auto/');
}

#include_spip('inc/texte'); // ????? Appelle public/parametrer trop tot avant la reconstruction du chemin des plugins.
include_spip('plugins/installer');

/**
 * Retourne la description de chaque plugin présent dans un répertoire
 *
 * Lecture des sous repertoire plugin existants
 *
 * @example
 *     - `liste_plugin_files()`
 *     - `liste_plugin_files(_DIR_PLUGINS_DIST)`
 *     - `liste_plugin_files(_DIR_PLUGINS_SUPPL)`
 *
 * @uses fast_find_plugin_dirs()
 * @uses plugins_get_infos_dist()
 *
 * @param string|null $dir_plugins
 *     - string : Chemin (relatif à la racine du site) du répertoire à analyser.
 *     - null : utilise le chemin `_DIR_PLUGINS`.
 * @return array
**/
function liste_plugin_files($dir_plugins = null) {
	static $plugin_files = [];
	if (is_null($dir_plugins)) {
		$dir_plugins = _DIR_PLUGINS;
	}
	if (
		!isset($plugin_files[$dir_plugins])
		or (is_countable($plugin_files[$dir_plugins]) ? count($plugin_files[$dir_plugins]) : 0) == 0
	) {
		$plugin_files[$dir_plugins] = [];
		foreach (fast_find_plugin_dirs($dir_plugins) as $plugin) {
			$plugin_files[$dir_plugins][] = substr($plugin, strlen($dir_plugins));
		}

		sort($plugin_files[$dir_plugins]);
		// et on lit le XML de tous les plugins pour le mettre en cache
		// et en profiter pour nettoyer ceux qui n'existent plus du cache
		$get_infos = charger_fonction('get_infos', 'plugins');
		$get_infos($plugin_files[$dir_plugins], false, $dir_plugins, true);
	}

	return $plugin_files[$dir_plugins];
}

/**
 * Recherche rapide des répertoires de plugins contenus dans un répertoire
 *
 * @uses is_plugin_dir()
 *
 * @param string $dir
 *     Chemin du répertoire dont on souhaite retourner les sous répertoires
 * @param int $max_prof
 *     Profondeur maximale des sous répertoires
 * @return array
 *     Liste complète des répeertoires
**/
function fast_find_plugin_dirs($dir, $max_prof = 100) {
	$fichiers = [];
	// revenir au repertoire racine si on a recu dossier/truc
	// pour regarder dossier/truc/ ne pas oublier le / final
	$dir = preg_replace(',/[^/]*$,', '', $dir);
	if ($dir == '') {
		$dir = '.';
	}

	if (!is_dir($dir)) {
		return $fichiers;
	}
	if (is_plugin_dir($dir, '')) {
		$fichiers[] = $dir;

		return $fichiers;
	}
	if ($max_prof <= 0) {
		return $fichiers;
	}

	$subdirs = [];
	if (@is_dir($dir) and is_readable($dir) and $d = opendir($dir)) {
		while (($f = readdir($d)) !== false) {
			if (
				$f[0] != '.' # ignorer . .. .svn etc
				and $f != 'CVS'
				and is_dir($f = "$dir/$f")
			) {
				$subdirs[] = $f;
			}
		}
		closedir($d);
	}

	foreach ($subdirs as $d) {
		$fichiers = array_merge($fichiers, fast_find_plugin_dirs("$d/", $max_prof - 1));
	}

	return $fichiers;
}

/**
 * Indique si un répertoire (ou plusieurs) est la racine d'un plugin SPIP
 *
 * Vérifie le ou les chemins relatifs transmis pour vérifier qu'ils contiennent
 * un `paquet.xml`. Les chemins valides sont retournés.
 *
 * @param string|string[] $dir
 *     Chemin (relatif à `$dir_plugins`), ou liste de chemins à tester
 * @param string|null $dir_plugins
 *     - string : Chemin de répertoire (relatif à la `_DIR_RACINE`), départ des chemin(s) à tester
 *     - null (par défaut) : utilise le chemin `_DIR_PLUGINS`
 * @return string|string[]
 *     - string : Le chemin accepté (c'était un plugin)
 *     - '' : ce n'était pas un chemin valide
 *     - array : Ensemble des chemins acceptés (si `$dir` était array)
**/
function is_plugin_dir($dir, $dir_plugins = null) {

	if (is_array($dir)) {
		foreach ($dir as $k => $d) {
			if (!is_plugin_dir($d, $dir_plugins)) {
				unset($dir[$k]);
			}
		}

		return $dir;
	}
	if (is_null($dir_plugins)) {
		$dir_plugins = _DIR_PLUGINS;
	}
	$search = ["$dir_plugins$dir/paquet.xml"];

	foreach ($search as $s) {
		if (file_exists($s)) {
			return $dir;
		}
	}

	return '';
}

/** Regexp d'extraction des informations d'un intervalle de compatibilité */
define('_EXTRAIRE_INTERVALLE', ',^[\[\(\]]([0-9.a-zRC\s\-]*)[;]([0-9.a-zRC\s\-\*]*)[\]\)\[]$,');

/**
 * Teste si le numéro de version d'un plugin est dans un intervalle donné.
 *
 * Cette fonction peut être volontairement trompée (phase de développement) :
 * voir commentaire infra sur l'utilisation de la constante _DEV_VERSION_SPIP_COMPAT
 *
 * @uses spip_version_compare()
 *
 * @param string $intervalle
 *    Un intervalle entre 2 versions. ex: [2.0.0-dev;2.1.*]
 * @param string $version
 *    Un numéro de version. ex: 3.1.99]
 * @param string $avec_quoi
 *    Ce avec quoi est testée la compatibilité. par défaut ('')
 *    avec un plugin (cas des 'necessite'), parfois ('spip')
 *    avec SPIP.
 * @return bool
 *    True si dans l'intervalle, false sinon.
 **/
function plugin_version_compatible($intervalle, $version, $avec_quoi = '') {

	if (!strlen($intervalle)) {
		return true;
	}
	if (!preg_match(_EXTRAIRE_INTERVALLE, $intervalle, $regs)) {
		return false;
	}
	// Extraction des bornes et traitement de * pour la borne sup :
	// -- on autorise uniquement les ecritures 3.0.*, 3.*
	$minimum = $regs[1];
	$maximum = $regs[2];

	//  si une version SPIP de compatibilité a été définie (dans
	//  mes_options.php, sous la forme : define('_DEV_VERSION_SPIP_COMPAT', '3.1.0');
	//  on l'utilise (phase de dev, de test...) mais *que* en cas de comparaison
	//  avec la version de SPIP (ne nuit donc pas aux tests de necessite
	//  entre plugins)
	if (defined('_DEV_VERSION_SPIP_COMPAT') and $avec_quoi == 'spip' and $version !== _DEV_VERSION_SPIP_COMPAT) {
		if (plugin_version_compatible($intervalle, _DEV_VERSION_SPIP_COMPAT, $avec_quoi)) {
			return true;
		}
		// si pas de compatibilite avec _DEV_VERSION_SPIP_COMPAT, on essaye quand meme avec la vrai version
		// cas du plugin qui n'est compatible qu'avec cette nouvelle version
	}

	$minimum_inc = $intervalle[0] == '[';
	$maximum_inc = substr($intervalle, -1) == ']';

	if (strlen($minimum)) {
		if ($minimum_inc and spip_version_compare($version, $minimum, '<')) {
			return false;
		}
		if (!$minimum_inc and spip_version_compare($version, $minimum, '<=')) {
			return false;
		}
	}
	if (strlen($maximum)) {
		if ($maximum_inc and spip_version_compare($version, $maximum, '>')) {
			return false;
		}
		if (!$maximum_inc and spip_version_compare($version, $maximum, '>=')) {
			return false;
		}
	}

	return true;
}

/**
 * Construire la liste des infos strictement necessaires aux plugins à activer
 * afin de les mémoriser dans une meta pas trop grosse
 *
 * @uses liste_plugin_files()
 * @uses plugins_get_infos_dist()
 * @uses plugin_valide_resume()
 * @uses plugin_fixer_procure()
 *
 * @param array $liste_plug
 * @param bool $force
 * @return array
 */
function liste_plugin_valides($liste_plug, $force = false) {
	$liste_ext = liste_plugin_files(_DIR_PLUGINS_DIST);
	$get_infos = charger_fonction('get_infos', 'plugins');
	$infos = [
		// lister les extensions qui sont automatiquement actives
		'_DIR_PLUGINS_DIST' => $get_infos($liste_ext, $force, _DIR_PLUGINS_DIST),
		'_DIR_PLUGINS' => $get_infos($liste_plug, $force, _DIR_PLUGINS)
	];

	// creer une premiere liste non ordonnee mais qui ne retient
	// que les plugins valides, et dans leur derniere version en cas de doublon
	$infos['_DIR_RESTREINT'][''] = $get_infos('./', $force, _DIR_RESTREINT);
	$infos['_DIR_RESTREINT']['SPIP']['version'] = $GLOBALS['spip_version_branche'];
	$infos['_DIR_RESTREINT']['SPIP']['chemin'] = [];
	$liste_non_classee = [
		'SPIP' => [
			'nom' => 'SPIP',
			'etat' => 'stable',
			'version' => $GLOBALS['spip_version_branche'],
			'dir_type' => '_DIR_RESTREINT',
			'dir' => '',
		]
	];

	$invalides = [];
	foreach ($liste_ext as $plug) {
		if (isset($infos['_DIR_PLUGINS_DIST'][$plug])) {
			plugin_valide_resume($liste_non_classee, $plug, $infos, '_DIR_PLUGINS_DIST');
		}
	}
	foreach ($liste_plug as $plug) {
		if (isset($infos['_DIR_PLUGINS'][$plug])) {
			$r = plugin_valide_resume($liste_non_classee, $plug, $infos, '_DIR_PLUGINS');
			if (is_array($r)) {
				$invalides = array_merge($invalides, $r);
			}
		}
	}

	if (defined('_DIR_PLUGINS_SUPPL') and _DIR_PLUGINS_SUPPL) {
		$infos['_DIR_PLUGINS_SUPPL'] = $get_infos($liste_plug, false, _DIR_PLUGINS_SUPPL);
		foreach ($liste_plug as $plug) {
			if (isset($infos['_DIR_PLUGINS_SUPPL'][$plug])) {
				$r = plugin_valide_resume($liste_non_classee, $plug, $infos, '_DIR_PLUGINS_SUPPL');
				if (is_array($r)) {
					$invalides = array_merge($invalides, $r);
				}
			}
		}
	}

	plugin_fixer_procure($liste_non_classee, $infos);

	// les plugins qui sont dans $liste_non_classee ne sont pas invalides (on a trouve un autre version valide)
	$invalides = array_diff_key($invalides, $liste_non_classee);

	return [$infos, $liste_non_classee, $invalides];
}

/**
 * Ne retenir un plugin que s'il est valide
 * et dans leur plus recente version compatible
 * avec la version presente de SPIP
 *
 * @uses plugin_version_compatible()
 * @uses spip_version_compare()
 *
 * @param array $liste
 * @param string $plug
 * @param array $infos
 * @param string $dir_type
 * @return string|array
 *   string prefixe dans $liste si on a accepte le plugin
 *   array description short si on ne le retient pas (pour memorisation dans une table des erreurs)
 */
function plugin_valide_resume(&$liste, $plug, $infos, $dir_type) {
	$i = $infos[$dir_type][$plug];
	// minimum syndical pour afficher si le xml avait des erreurs éventuelles
	$short_desc = [
		'dir' => $plug,
		'dir_type' => $dir_type
	];
	if (empty($i['prefix'])) {
		// erreur xml ? mais sans connaissance du prefix, on retourne le chemin…
		$short_desc['erreur'] = $i['erreur'] ?? ['?'];
		return [$plug => $short_desc];
	}

	$p = strtoupper($i['prefix']);
	$short_desc['nom'] = $i['nom'];
	$short_desc['etat'] = $i['etat'];
	$short_desc['version'] = $i['version'];

	if (isset($i['erreur']) and $i['erreur']) {
		$short_desc['erreur'] = $i['erreur'];
		return [$p => $short_desc];
	}
	if (!plugin_version_compatible($i['compatibilite'], $GLOBALS['spip_version_branche'], 'spip')) {
		return [$p => $short_desc];
	}
	if (
		!isset($liste[$p])
		or spip_version_compare($i['version'], $liste[$p]['version'], '>')
	) {
		$liste[$p] = $short_desc;
	}
	// ok le plugin etait deja dans la liste ou on a choisi une version plus recente
	return $p;
}

/**
 * Compléter la liste des plugins avec les éventuels procure
 *
 * les balises `<procure>` sont considerées comme des plugins proposés,
 * mais surchargeables (on peut activer un plugin qui procure ça pour l'améliorer,
 * donc avec le même prefixe, qui sera pris en compte si il a une version plus grande)
 *
 * @uses spip_version_compare()
 *
 * @param array $liste
 * @param array $infos
 */
function plugin_fixer_procure(&$liste, &$infos) {
	foreach ($liste as $p => $resume) {
		$i = $infos[$resume['dir_type']][$resume['dir']];
		if (isset($i['procure']) and $i['procure']) {
			foreach ($i['procure'] as $procure) {
				$p = strtoupper($procure['nom']);
				$dir = $resume['dir'];
				if ($dir) {
					$dir .= '/';
				}
				$dir .= 'procure:' . $procure['nom'];

				$procure['etat'] = '?';
				$procure['dir_type'] = $resume['dir_type'];
				$procure['dir'] = $dir;

				// si ce plugin n'est pas deja procure, ou dans une version plus ancienne
				// on ajoute cette version a la liste
				if (
					!isset($liste[$p])
					or spip_version_compare($procure['version'], $liste[$p]['version'], '>')
				) {
					$liste[$p] = $procure;

					// on fournit une information minimale pour ne pas perturber la compilation
					$infos[$resume['dir_type']][$dir] = [
						'prefix' => $procure['nom'],
						'nom' => $procure['nom'],
						'etat' => $procure['etat'],
						'version' => $procure['version'],
						'chemin' => [],
						'necessite' => [],
						'utilise' => [],
						'lib' => [],
						'menu' => [],
						'onglet' => [],
						'procure' => [],
					];
				}
			}
		}
	}
}

/**
 * Extrait les chemins d'une liste de plugin
 *
 * Sélectionne au passage ceux qui sont dans `$dir_plugins` uniquement
 * si valeur non vide
 *
 * @param array $liste
 * @param string $dir_plugins
 * @return array
 */
function liste_chemin_plugin($liste, $dir_plugins = _DIR_PLUGINS) {
	foreach ($liste as $prefix => $infos) {
		if (
			!$dir_plugins
			or (
				defined($infos['dir_type'])
				and constant($infos['dir_type']) == $dir_plugins)
		) {
			$liste[$prefix] = $infos['dir'];
		} else {
			unset($liste[$prefix]);
		}
	}

	return $liste;
}

/**
 * Liste les chemins vers les plugins actifs du dossier fourni en argument
 * a partir d'une liste d'elelements construits par plugin_valide_resume
 *
 * @uses liste_plugin_actifs()
 * @uses liste_chemin_plugin()
 *
 * @param string $dir_plugins
 *     Chemin du répertoire de plugins
 * @return array
 */
function liste_chemin_plugin_actifs($dir_plugins = _DIR_PLUGINS) {
	include_spip('plugins/installer');

	return liste_chemin_plugin(liste_plugin_actifs(), $dir_plugins);
}

/**
 * Trier les plugins en vériant leur dépendances (qui doivent être présentes)
 *
 * Pour tester "utilise", il faut connaître tous les plugins
 * qui seront forcément absents à la fin,
 * car absent de la liste des plugins actifs.
 *
 * Il faut donc construire une liste ordonnée.
 *
 * Cette fonction détecte des dépendances circulaires,
 * avec un doute sur un "utilise" qu'on peut ignorer.
 * Mais ne pas insérer silencieusement et risquer un bug sournois latent
 *
 * @uses plugin_version_compatible()
 *
 * @param array $infos
 *     Répertoire (plugins, plugins-dist, ...) => Couples (prefixes => infos completes) des plugins qu'ils contiennent
 * @param array $liste_non_classee
 *     Couples (prefixe => description) des plugins qu'on souhaite utiliser
 * @return array
 *     Tableau de 3 éléments :
 *     - $liste : couples (prefixes => description) des plugins valides
 *     - $ordre : couples (prefixes => infos completes) des plugins triés
 *                (les plugins nécessités avant les plugins qui les utilisent)
 *     - $liste_non_classee : couples (prefixes => description) des plugins
 *                qui n'ont pas satisfait leurs dépendances
**/
function plugin_trier($infos, $liste_non_classee) {
	$toute_la_liste = $liste_non_classee;
	$liste = $ordre = [];
	$count = 0;

	while ($c = count($liste_non_classee) and $c != $count) { // tant qu'il reste des plugins a classer, et qu'on ne stagne pas
		#echo "tour::";var_dump($liste_non_classee);
		$count = $c;
		foreach ($liste_non_classee as $p => $resume) {
			$plug = $resume['dir'];
			$dir_type = $resume['dir_type'];
			$info1 = $infos[$dir_type][$plug];
			// si des plugins sont necessaires,
			// on ne peut inserer qu'apres eux
			foreach ($info1['necessite'] as $need) {
				$nom = strtoupper($need['nom']);
				$compat = $need['compatibilite'] ?? '';
				if (!isset($liste[$nom]) or !plugin_version_compatible($compat, $liste[$nom]['version'])) {
					$info1 = false;
					break;
				}
			}
			if (!$info1) {
				continue;
			}
			// idem si des plugins sont utiles,
			// sauf si ils sont de toute facon absents de la liste
			foreach ($info1['utilise'] as $need) {
				$nom = strtoupper($need['nom']);
				$compat = $need['compatibilite'] ?? '';
				if (isset($toute_la_liste[$nom])) {
					if (
						!isset($liste[$nom]) or
						!plugin_version_compatible($compat, $liste[$nom]['version'])
					) {
						$info1 = false;
						break;
					}
				}
			}
			if ($info1) {
				$ordre[$p] = $info1;
				$liste[$p] = $liste_non_classee[$p];
				unset($liste_non_classee[$p]);
			}
		}
	}

	return [$liste, $ordre, $liste_non_classee];
}

/**
 * Collecte les erreurs de dépendances des plugins dans la meta `plugin_erreur_activation`
 *
 * @uses plugin_necessite()
 * @uses plugin_controler_lib()
 *
 * @param array $liste_non_classee
 *     Couples (prefixe => description) des plugins en erreur
 * @param array $liste
 *     Couples (prefixe => description) des plugins qu'on souhaite utiliser
 * @param array $infos
 *     Répertoire (plugins, plugins-dist, ...) => Couples (prefixes => infos completes) des plugins qu'ils contiennent
**/
function plugins_erreurs($liste_non_classee, $liste, $infos, $msg = []) {
	static $erreurs = [];

	if (!is_array($liste)) {
		$liste = [];
	}

	// les plugins en erreur ne sont pas actifs ; ils ne doivent pas être dans la liste
	$liste = array_diff_key($liste, $liste_non_classee);

	foreach ($liste_non_classee as $p => $resume) {
		$dir_type = $resume['dir_type'];
		$plug = $resume['dir'];
		$k = $infos[$dir_type][$plug];

		$plug = constant($dir_type) . $plug;
		if (!isset($msg[$p])) {
			if (isset($resume['erreur']) and $resume['erreur']) {
				$msg[$p] = [$resume['erreur']];
			}
			elseif (!plugin_version_compatible($k['compatibilite'], $GLOBALS['spip_version_branche'], 'spip')) {
				$msg[$p] = [plugin_message_incompatibilite($k['compatibilite'], $GLOBALS['spip_version_branche'], 'SPIP', 'necessite')];
			}
			elseif (!$msg[$p] = plugin_necessite($k['necessite'], $liste, 'necessite')) {
				$msg[$p] = plugin_necessite($k['utilise'], $liste, 'utilise');
			}
		} else {
			foreach ($msg[$p] as $c => $l) {
				$msg[$p][$c] = plugin_controler_lib($l['nom'], $l['lien']);
			}
		}
		$erreurs[$plug] = $msg[$p];
	}

	ecrire_meta('plugin_erreur_activation', serialize($erreurs));
}

/**
 * Retourne les erreurs d'activation des plugins, au format html ou brut
 *
 * @param bool $raw
 *     - true : pour obtenir le tableau brut des erreurs
 *     - false : Code HTML
 * @param bool $raz
 *     - true pour effacer la meta qui stocke les erreurs.
 * @return string|array
 *     - Liste des erreurs ou code HTML des erreurs
**/
function plugin_donne_erreurs($raw = false, $raz = true) {
	if (!isset($GLOBALS['meta']['plugin_erreur_activation'])) {
		return $raw ? [] : '';
	}
	$list = @unserialize($GLOBALS['meta']['plugin_erreur_activation']);
	// Compat ancienne version
	if (!$list) {
		$list = $raw ? [] : $GLOBALS['meta']['plugin_erreur_activation'];
	} elseif (!$raw) {
		foreach ($list as $plug => $msg) {
			$list[$plug] = '<li>' . _T('plugin_impossible_activer', ['plugin' => $plug])
				. '<ul><li>' . implode('</li><li>', $msg) . '</li></ul></li>';
		}
		$list = '<ul>' . join("\n", $list) . '</ul>';
	}
	if ($raz) {
		effacer_meta('plugin_erreur_activation');
	}

	return $list;
}

/**
 * Teste des dépendances
 *
 * Et vérifie que chaque dépendance est présente
 * dans la liste de plugins donnée
 *
 * @uses plugin_controler_necessite()
 *
 * @param array $n
 *    Tableau de dépendances dont on souhaite vérifier leur présence
 * @param array $liste
 *    Tableau des plugins présents
 * @return array
 *    Tableau des messages d'erreurs reçus. Il sera vide si tout va bien.
 *
 **/
function plugin_necessite($n, $liste, $balise = 'necessite') {
	$msg = [];
	foreach ($n as $need) {
		$id = strtoupper($need['nom']);
		$r = plugin_controler_necessite(
			$liste,
			$id,
			$need['compatibilite'] ?? '',
			$balise
		);
		if ($r) {
			$msg[] = $r;
		}
	}

	return $msg;
}

/**
 * Vérifie qu'une dépendance (plugin) est bien présente.
 *
 * @uses plugin_version_compatible()
 * @uses plugin_message_incompatibilite()
 *
 * @param $liste
 *    Liste de description des plugins
 * @param $nom
 *    Le plugin donc on cherche la presence
 * @param $intervalle
 *    L'éventuelle intervalle de compatibilité de la dépendance. ex: [1.1.0;]
 * @param $balise
 *    Permet de définir si on teste un utilise ou un nécessite
 * @return string.
 *    Vide si ok,
 *    Message d'erreur lorsque la dépendance est absente.
 **/
function plugin_controler_necessite($liste, $nom, $intervalle, $balise) {
	if (isset($liste[$nom]) and plugin_version_compatible($intervalle, $liste[$nom]['version'])) {
		return '';
	}
	// Si l'on a un <utilise="plugin non actif" />, ne pas renvoyer d'erreur
	if ($balise === 'utilise' and !isset($liste[$nom])) {
		return '';
	}
	return plugin_message_incompatibilite(
		$intervalle,
		(isset($liste[$nom]) ? $liste[$nom]['version'] : ''),
		$nom,
		$balise
	);
}

/**
 * @param string $intervalle
 *     L'éventuelle intervalle de compatibilité de la dépendance. ex: [1.1.0;]
 * @param string $version
 *     La version en cours active pour le plugin demandé (ou php ou extension php demandée)
 * @param string $nom
 *     Le plugin (ou php ou extension php) qui est absent
 * @param string $balise
 *     Le type de balise utilisé (necessite ou utilise)
 * @return string
 *     Le message d'erreur.
 */
function plugin_message_incompatibilite($intervalle, $version, $nom, $balise) {

	// prendre en compte les erreurs de dépendances à PHP
	// ou à une extension PHP avec des messages d'erreurs dédiés.
	$type = 'plugin';
	if ($nom === 'SPIP') {
		$type = 'spip';
	} elseif ($nom === 'PHP') {
		$type = 'php';
	} elseif (strncmp($nom, 'PHP:', 4) === 0) {
		$type = 'extension_php';
		[, $nom] = explode(':', $nom, 2);
	}

	if (preg_match(_EXTRAIRE_INTERVALLE, $intervalle, $regs)) {
		$minimum = $regs[1];
		$maximum = $regs[2];

		$minimum_inclus = $intervalle[0] == '[';
		$maximum_inclus = substr($intervalle, -1) == ']';

		if (strlen($minimum)) {
			if ($minimum_inclus and spip_version_compare($version, $minimum, '<')) {
				return _T("plugin_{$balise}_{$type}", [
					'plugin' => $nom,
					'version' => ' &ge; ' . $minimum
				]);
			}
			if (!$minimum_inclus and spip_version_compare($version, $minimum, '<=')) {
				return _T("plugin_{$balise}_{$type}", [
					'plugin' => $nom,
					'version' => ' &gt; ' . $minimum
				]);
			}
		}

		if (strlen($maximum)) {
			if ($maximum_inclus and spip_version_compare($version, $maximum, '>')) {
				return _T("plugin_{$balise}_{$type}", [
					'plugin' => $nom,
					'version' => ' &le; ' . $maximum
				]);
			}
			if (!$maximum_inclus and spip_version_compare($version, $maximum, '>=')) {
				return _T("plugin_{$balise}_plugin", [
					'plugin' => $nom,
					'version' => ' &lt; ' . $maximum
				]);
			}
		}
	}

	// note : il ne peut pas y avoir d'erreur sur
	// - un 'utilise' sans version.
	// - un 'php' sans version.
	return _T("plugin_necessite_{$type}_sans_version", ['plugin' => $nom]);
}


function plugin_controler_lib($lib, $url) {
	/* Feature sortie du core, voir STP
	 * if ($url) {
		include_spip('inc/charger_plugin');
		$url = '<br />'	. bouton_telechargement_plugin($url, 'lib');
	}*/
	return _T('plugin_necessite_lib', ['lib' => $lib]) . " <a href='$url'>$url</a>";
}


/**
 * Calcule la liste des plugins actifs et recompile les fichiers caches
 * qui leurs sont relatifs
 *
 * @uses ecrire_plugin_actifs()
 *
 * @param bool $pipe_recherche ?
 * @return bool
 *     true si il y a eu des modifications sur la liste des plugins actifs, false sinon
 **/
function actualise_plugins_actifs($pipe_recherche = false) {
	return ecrire_plugin_actifs('', $pipe_recherche, 'force');
}


/**
 * Calcule ou modifie la liste des plugins actifs et recompile les fichiers caches
 * qui leurs sont relatifs
 *
 * @note
 *   Les  ecrire_meta() doivent en principe aussi initialiser la valeur a vide
 *   si elle n'existe pas risque de pb en php5 a cause du typage ou de null
 *   (verifier dans la doc php)
 *
 * @param string|string[] $plugin
 *     Plugin ou plugins concernés (leur chemin depuis le répertoire plugins)
 * @param bool $pipe_recherche
 *     ?
 * @param string $operation
 *     - raz : recalcule tout
 *     - ajoute : ajoute le plugin indiqué à la liste des plugins actifs
 *     - enleve : enleve le plugin indiqué de la liste des plugins actifs
 *     - force  : ?
 * @return bool
 *     true si il y a eu des modifications sur la liste des plugins actifs, false sinon
 **/
function ecrire_plugin_actifs($plugin, $pipe_recherche = false, $operation = 'raz') {

	// creer le repertoire cache/ si necessaire ! (installation notamment)
	$cache = sous_repertoire(_DIR_CACHE, '', false, true);

	// Si on n'a ni cache accessible, ni connexion SQL, on ne peut pas faire grand chose encore.
	if (!$cache and !spip_connect()) {
		return false;
	}

	if ($operation != 'raz') {
		$plugin_valides = liste_chemin_plugin_actifs();
		$plugin_valides = is_plugin_dir($plugin_valides);
		if (defined('_DIR_PLUGINS_SUPPL') && _DIR_PLUGINS_SUPPL) {
			$plugin_valides_supp = liste_chemin_plugin_actifs(_DIR_PLUGINS_SUPPL);
			$plugin_valides_supp = is_plugin_dir($plugin_valides_supp, _DIR_PLUGINS_SUPPL);
			$plugin_valides = array_merge($plugin_valides, $plugin_valides_supp);
		}
		// si des plugins sont en attentes (coches mais impossible a activer)
		// on les reinjecte ici
		if (
			isset($GLOBALS['meta']['plugin_attente'])
			and $a = unserialize($GLOBALS['meta']['plugin_attente'])
		) {
			$plugin_valides = $plugin_valides + liste_chemin_plugin($a);
		}

		if ($operation == 'ajoute') {
			$plugin = array_merge($plugin_valides, $plugin);
		} elseif ($operation == 'enleve') {
			$plugin = array_diff($plugin_valides, $plugin);
		} else {
			$plugin = $plugin_valides;
		}
	}
	$actifs_avant = $GLOBALS['meta']['plugin'] ?? '';

	// si une fonction de gestion de dependances existe, l'appeler ici
	if ($ajouter_dependances = charger_fonction('ajouter_dependances', 'plugins', true)) {
		$plugin = $ajouter_dependances($plugin);
	}

	// recharger le xml des plugins a activer
	// on force le reload ici, meme si le fichier xml n'a pas change
	// pour ne pas rater l'ajout ou la suppression d'un fichier fonctions/options/administrations
	// pourra etre evite quand on ne supportera plus les plugin.xml
	// en deplacant la detection de ces fichiers dans la compilation ci dessous
	[$infos, $liste, $invalides] = liste_plugin_valides($plugin, true);
	// trouver l'ordre d'activation
	[$plugin_valides, $ordre, $reste] = plugin_trier($infos, $liste);
	if ($invalides or $reste) {
		plugins_erreurs(array_merge($invalides, $reste), $liste, $infos);
	}

	// Ignorer les plugins necessitant une lib absente
	// et preparer la meta d'entete Http
	$err = $msg = $header = [];
	foreach ($plugin_valides as $p => $resume) {
		// Les headers ne doivent pas indiquer les versions des extensions PHP, ni la version PHP
		if (!str_starts_with($p, 'PHP:') and $p !== 'PHP') {
			$header[] = $p . ($resume['version'] ? '(' . $resume['version'] . ')' : '');
		}
		if ($resume['dir']) {
			foreach ($infos[$resume['dir_type']][$resume['dir']]['lib'] as $l) {
				if (!find_in_path($l['nom'], 'lib/')) {
					$err[$p] = $resume;
					$msg[$p][] = $l;
					unset($plugin_valides[$p]);
				}
			}
		}
	}
	if ($err) {
		plugins_erreurs($err, '', $infos, $msg);
	}

	if (isset($GLOBALS['meta']['message_crash_plugins'])) {
		effacer_meta('message_crash_plugins');
	}
	ecrire_meta('plugin', serialize($plugin_valides));
	$liste = array_diff_key($liste, $plugin_valides);
	ecrire_meta('plugin_attente', serialize($liste));
	$header = strtolower(implode(',', $header));
	if (!isset($GLOBALS['spip_header_silencieux']) or !$GLOBALS['spip_header_silencieux']) {
		ecrire_fichier(
			_DIR_VAR . 'config.txt',
			(defined('_HEADER_COMPOSED_BY') ? _HEADER_COMPOSED_BY : 'Composed-By: SPIP') . ' ' . $GLOBALS['spip_version_affichee'] . ' @ www.spip.net + ' . $header
		);
	} else {
		@unlink(_DIR_VAR . 'config.txt');
	}
	// generer charger_plugins_chemin.php
	plugins_precompile_chemin($plugin_valides, $ordre);
	// generer les fichiers
	// - charger_plugins_options.php
	// - charger_plugins_fonctions.php
	plugins_precompile_xxxtions($plugin_valides, $ordre);
	// charger les chemins des plugins et les fichiers d'options
	// (qui peuvent déclarer / utiliser des pipelines, ajouter d'autres chemins)
	plugins_amorcer_plugins_actifs();
	// mise a jour de la matrice des pipelines
	$prepend_code = pipeline_matrice_precompile($plugin_valides, $ordre, $pipe_recherche);
	// generer le fichier _CACHE_PIPELINE
	pipeline_precompile($prepend_code);

	if (spip_connect()) {
		// lancer et initialiser les nouveaux crons !
		include_spip('inc/genie');
		genie_queue_watch_dist();
	}

	return ($GLOBALS['meta']['plugin'] != $actifs_avant);
}

/**
 * Écrit le fichier de déclaration des chemins (path) des plugins actifs
 *
 * Le fichier créé, une fois exécuté permet à SPIP de rechercher
 * des fichiers dans les répertoires des plugins concernés.
 *
 * @see _chemin() Utilisé pour déclarer les chemins.
 * @uses plugin_version_compatible()
 * @uses ecrire_fichier_php()
 *
 * @param array $plugin_valides
 *     Couples (prefixe => description) des plugins qui seront actifs
 * @param array $ordre
 *     Couples (prefixe => infos complètes) des plugins qui seront actifs, dans l'ordre de leurs dépendances
**/
function plugins_precompile_chemin($plugin_valides, $ordre) {
	$chemins = [
		'public' => [],
		'prive' => []
	];
	$contenu = '';
	foreach ($ordre as $p => $info) {
		// $ordre peur contenir des plugins en attente et non valides pour ce hit
		if (isset($plugin_valides[$p])) {
			$dir_type = $plugin_valides[$p]['dir_type'];
			$plug = $plugin_valides[$p]['dir'];
			// definir le plugin, donc le path avant l'include du fichier options
			// permet de faire des include_spip pour attraper un inc_ du plugin

			$dir = $dir_type . ".'" . $plug . "/'";

			$prefix = strtoupper(preg_replace(',\W,', '_', $info['prefix']));
			if (
				$prefix !== 'SPIP'
				and !str_contains($dir, ':') // exclure le cas des procure:
			) {
				$contenu .= "define('_DIR_PLUGIN_$prefix',$dir);\n";
				if (!$info['chemin']) {
					$chemins['public'][] = "_DIR_PLUGIN_$prefix";
					$chemins['prive'][] = "_DIR_PLUGIN_$prefix";
					if (is_dir(constant($dir_type) . $plug . '/squelettes/')) {
						$chemins['public'][] = "_DIR_PLUGIN_{$prefix}.'squelettes/'";
					}
				}
				else {
					foreach ($info['chemin'] as $chemin) {
						if (
							!isset($chemin['version']) or plugin_version_compatible(
								$chemin['version'],
								$GLOBALS['spip_version_branche'],
								'spip'
							)
						) {
							$dir = $chemin['path'];
							if (strlen($dir) and $dir[0] == '/') {
								$dir = substr($dir, 1);
							}
							if (strlen($dir) and $dir == './') {
								$dir = '';
							}
							if (strlen($dir)) {
								$dir = rtrim($dir, '/') . '/';
							}
							if (!isset($chemin['type']) or $chemin['type'] == 'public') {
								$chemins['public'][] = "_DIR_PLUGIN_$prefix" . (strlen($dir) ? ".'$dir'" : '');
							}
							if (!isset($chemin['type']) or $chemin['type'] == 'prive') {
								$chemins['prive'][] = "_DIR_PLUGIN_$prefix" . (strlen($dir) ? ".'$dir'" : '');
							}
						}
					}
				}
			}
		}
	}
	if (count($chemins['public']) or count($chemins['prive'])) {
		$contenu .= 'if (_DIR_RESTREINT) _chemin([' . implode(
			',',
			array_reverse($chemins['public'])
		) . "]);\n"
			. 'else _chemin([' . implode(',', array_reverse($chemins['prive'])) . "]);\n";
	}

	ecrire_fichier_php(_CACHE_PLUGINS_PATH, $contenu);
}

/**
 * Écrit les fichiers de chargement des fichiers d'options et de fonctions des plugins
 *
 * Les onglets et menus déclarés dans le fichier paquet.xml des plugins sont également
 * ajoutés au fichier de fonctions créé.
 *
 * @uses plugin_ongletbouton()
 * @uses ecrire_fichier_php()
 *
 * @param array $plugin_valides
 *     Couples (prefixe => description) des plugins qui seront actifs
 * @param array $ordre
 *     Couples (prefixe => infos complètes) des plugins qui seront actifs, dans l'ordre de leurs dépendances
**/
function plugins_precompile_xxxtions($plugin_valides, $ordre) {
	$contenu = ['options' => '', 'fonctions' => ''];
	$boutons = [];
	$onglets = [];
	$sign = '';

	foreach ($ordre as $p => $info) {
		// $ordre peur contenir des plugins en attente et non valides pour ce hit
		if (isset($plugin_valides[$p])) {
			$dir_type = $plugin_valides[$p]['dir_type'];
			$plug = $plugin_valides[$p]['dir'];
			$dir = constant($dir_type);
			$root_dir_type = str_replace('_DIR_', '_ROOT_', $dir_type);
			if ($info['menu']) {
				$boutons = array_merge($boutons, $info['menu']);
			}
			if ($info['onglet']) {
				$onglets = array_merge($onglets, $info['onglet']);
			}
			foreach ($contenu as $charge => $v) {
				// si pas declare/detecte a la lecture du paquet.xml,
				// detecer a nouveau ici puisque son ajout ne provoque pas une modif du paquet.xml
				// donc ni sa relecture, ni sa detection
				if (
					!isset($info[$charge])
					and $dir // exclure le cas du plugin "SPIP"
					and !str_contains($dir, ':') // exclure le cas des procure:
					and file_exists("$dir$plug/paquet.xml") // uniquement pour les paquet.xml
				) {
					if (is_readable("$dir$plug/" . ($file = $info['prefix'] . '_' . $charge . '.php'))) {
						$info[$charge] = [$file];
					}
				}
				if (isset($info[$charge])) {
					$files = $info[$charge];
					foreach ($files as $k => $file) {
						// on genere un if file_exists devant chaque include
						// pour pouvoir garder le meme niveau d'erreur general
						$file = trim($file);
						if (
							!is_readable("$dir$plug/$file")
							// uniquement pour les paquet.xml
							and file_exists("$dir$plug/paquet.xml")
						) {
							unset($info[$charge][$k]);
						} else {
							$_file = $root_dir_type . ".'$plug/$file'";
							$contenu[$charge] .= "include_once_check($_file);\n";
						}
					}
				}
			}
			$sign .= md5(serialize($info));
		}
	}

	$contenu['options'] = "define('_PLUGINS_HASH','" . md5($sign) . "');\n" . $contenu['options'];
	$contenu['fonctions'] .= plugin_ongletbouton('boutons_plugins', $boutons)
		. plugin_ongletbouton('onglets_plugins', $onglets);

	ecrire_fichier_php(_CACHE_PLUGINS_OPT, $contenu['options']);
	ecrire_fichier_php(_CACHE_PLUGINS_FCT, $contenu['fonctions']);
}

/**
 * Compile les entrées d'un menu et retourne le code php d'exécution
 *
 * Génère et retourne un code php (pour enregistrement dans un fichier de cache)
 * permettant d'obtenir la liste des entrées de menus, ou des onglets
 * de l'espace privé.
 *
 * Définit également une constante (_UPDATED_$nom et _UPDATED_md5_$nom),
 * signalant une modification de ces menus
 *
 * @param string $nom Nom du type de menu
 *     Exemple: boutons_plugins, onglets_plugins
 * @param array $val Liste des entrées de ce menu
 * @return string Code php
 */
function plugin_ongletbouton($nom, $val) {
	if (!$val) {
		$val = [];
	}

	$val = serialize($val);
	$md5 = md5($val);

	if (!defined("_UPDATED_$nom")) {
		define("_UPDATED_$nom", $val);
		define("_UPDATED_md5_$nom", $md5);
	}
	$val = "unserialize('" . str_replace("'", "\'", $val) . "')";

	return
		"if (!function_exists('$nom')) {\n"
		. "function $nom(){return defined('_UPDATED_$nom')?unserialize(_UPDATED_$nom):$val;}\n"
		. "function md5_$nom(){return defined('_UPDATED_md5_$nom')?_UPDATED_md5_$nom:'" . $md5 . "';}\n"
		. "}\n";
}

/**
 * Chargement des plugins actifs dans le path de SPIP
 * et exécution de fichiers d'options des plugins
 *
 * Les fichiers d'options peuvent déclarer des pipelines ou de
 * nouveaux chemins.
 *
 * La connaissance chemins peut être nécessaire pour la construction
 * du fichier d'exécution des pipelines.
**/
function plugins_amorcer_plugins_actifs() {

	if (@is_readable(_CACHE_PLUGINS_PATH)) {
		include_once(_CACHE_PLUGINS_PATH);
	}

	if (@is_readable(_CACHE_PLUGINS_OPT)) {
		include_once(_CACHE_PLUGINS_OPT);
	} else {
		spip_log('pipelines desactives: impossible de produire ' . _CACHE_PLUGINS_OPT);
	}
}

/**
 * Crée la liste des filtres à traverser pour chaque pipeline
 *
 * Complète la globale `spip_pipeline` des fonctions que doit traverser un pipeline,
 * et la globale `spip_matrice` des fichiers à charger qui contiennent ces fonctions.
 *
 * Retourne aussi pour certaines balises présentes dans les paquet.xml (script, style, genie),
 * un code PHP à insérer au début de la chaîne du ou des pipelines associés à cette balise
 * (insert_head, insert_head_css, taches_generales_cron, ...). Ce sont des écritures
 * raccourcies pour des usages fréquents de ces pipelines.
 *
 * @param array $plugin_valides
 *     Couples (prefixe => description) des plugins qui seront actifs
 * @param array $ordre
 *     Couples (prefixe => infos complètes) des plugins qui seront actifs, dans l'ordre de leurs dépendances
 * @param string $pipe_recherche
 * @return array
 *     Couples (nom du pipeline => Code PHP à insérer au début du pipeline)
**/
function pipeline_matrice_precompile($plugin_valides, $ordre, $pipe_recherche) {
	static $liste_pipe_manquants = [];
	if (($pipe_recherche) && (!in_array($pipe_recherche, $liste_pipe_manquants))) {
		$liste_pipe_manquants[] = $pipe_recherche;
	}

	$prepend_code = [];

	foreach ($ordre as $p => $info) {
		// $ordre peur contenir des plugins en attente et non valides pour ce hit
		if (isset($plugin_valides[$p])) {
			$dir_type = $plugin_valides[$p]['dir_type'];
			$root_dir_type = str_replace('_DIR_', '_ROOT_', $dir_type);
			$plug = $plugin_valides[$p]['dir'];
			$prefix = (($info['prefix'] == 'spip') ? '' : $info['prefix'] . '_');
			if (isset($info['pipeline']) and is_array($info['pipeline'])) {
				foreach ($info['pipeline'] as $pipe) {
					$nom = $pipe['nom'];
					if (isset($pipe['action'])) {
						$action = $pipe['action'];
					} else {
						$action = $nom;
					}
					$nomlower = strtolower($nom);
					if (
						$nomlower != $nom
						and isset($GLOBALS['spip_pipeline'][$nom])
						and !isset($GLOBALS['spip_pipeline'][$nomlower])
					) {
						$GLOBALS['spip_pipeline'][$nomlower] = $GLOBALS['spip_pipeline'][$nom];
						unset($GLOBALS['spip_pipeline'][$nom]);
					}
					$nom = $nomlower;
					// une action vide est une declaration qui ne doit pas etre compilee !
					if (!isset($GLOBALS['spip_pipeline'][$nom])) { // creer le pipeline eventuel
					$GLOBALS['spip_pipeline'][$nom] = '';
					}
					if ($action) {
						if (strpos($GLOBALS['spip_pipeline'][$nom], (string) "|$prefix$action") === false) {
							$GLOBALS['spip_pipeline'][$nom] = preg_replace(
								',(\|\||$),',
								"|$prefix$action\\1",
								$GLOBALS['spip_pipeline'][$nom],
								1
							);
						}
						if (isset($pipe['inclure'])) {
							$GLOBALS['spip_matrice']["$prefix$action"] =
								"$root_dir_type:$plug/" . $pipe['inclure'];
						}
					}
				}
			}
			if (isset($info['genie']) and is_countable($info['genie']) ? count($info['genie']) : 0) {
				if (!isset($prepend_code['taches_generales_cron'])) {
					$prepend_code['taches_generales_cron'] = '';
				}
				foreach ($info['genie'] as $genie) {
					$nom = $prefix . $genie['nom'];
					$periode = max(60, intval($genie['periode']));
					if (charger_fonction($nom, 'genie', true)) {
						$prepend_code['taches_generales_cron'] .= "\$val['$nom'] = $periode;\n";
					} else {
						spip_log("Fonction genie_$nom introuvable", _LOG_ERREUR);
					}
				}
			}
			if (isset($info['style']) and is_countable($info['style']) ? count($info['style']) : 0) {
				if (!isset($prepend_code['insert_head_css'])) {
					$prepend_code['insert_head_css'] = '';
				}
				if (!isset($prepend_code['header_prive_css'])) {
					$prepend_code['header_prive_css'] = '';
				}
				foreach ($info['style'] as $style) {
					if (isset($style['path']) and $style['path']) {
						$code = "if (\$f=timestamp(direction_css(find_in_path('" . addslashes($style['path']) . "')))) ";
					} else {
						$code = "if (\$f='" . addslashes($style['url']) . "') ";
					}
					$code .= "\$val .= '<link rel=\"stylesheet\" href=\"'.\$f.'\" type=\"text/css\"";
					if (isset($style['media']) and strlen($style['media'])) {
						$code .= ' media="' . addslashes($style['media']) . '"';
					}
					$code .= "/>';\n";
					if ($style['type'] != 'prive') {
						$prepend_code['insert_head_css'] .= $code;
					}
					if ($style['type'] != 'public') {
						$prepend_code['header_prive_css'] .= $code;
					}
				}
			}
			if (!isset($prepend_code['insert_head'])) {
				$prepend_code['insert_head'] = '';
			}
			if (!isset($prepend_code['header_prive'])) {
				$prepend_code['header_prive'] = '';
			}
			if (isset($info['script']) and is_countable($info['script']) ? count($info['script']) : 0) {
				foreach ($info['script'] as $script) {
					if (isset($script['path']) and $script['path']) {
						$code = "if (\$f=timestamp(find_in_path('" . addslashes($script['path']) . "'))) ";
					} else {
						$code = "if (\$f='" . addslashes($script['url']) . "') ";
					}
					$code .= "\$val .= '<script src=\"'.\$f.'\" type=\"text/javascript\"></script>';\n";
					if ($script['type'] != 'prive') {
						$prepend_code['insert_head'] .= $code;
					}
					if ($script['type'] != 'public') {
						$prepend_code['header_prive'] .= $code;
					}
				}
			}
		}
	}

	$prepend_code['insert_head'] =
		"include_once_check(_DIR_RESTREINT . 'inc/pipelines.php');\n"
		. "\$val = minipipe('f_jQuery', \$val);\n"
		. $prepend_code['insert_head'];
	$prepend_code['header_prive'] =
		"include_once_check(_DIR_RESTREINT . 'inc/pipelines_ecrire.php');\n"
		. "\$val = minipipe('f_jQuery_prive', \$val);\n"
		. $prepend_code['header_prive'];

	// on ajoute les pipe qui ont ete recenses manquants
	foreach ($liste_pipe_manquants as $add_pipe) {
		if (!isset($GLOBALS['spip_pipeline'][$add_pipe])) {
			$GLOBALS['spip_pipeline'][$add_pipe] = '';
		}
	}

	return $prepend_code;
}

/**
 * Précompilation des pipelines
 *
 * Crée le fichier d'exécution des pipelines
 * dont le chemin est défini par `_CACHE_PIPELINES`
 *
 * La liste des pipelines est définie par la globale `spip_pipeline`
 * qui a été remplie soit avec les fichiers d'options, soit avec
 * des descriptions de plugins (paquet.xml) dont celui de SPIP lui-même.
 *
 * Les fichiers à charger pour accéder aux fonctions qui doivent traverser
 * un pipeline se trouve dans la globale `spip_matrice`.
 *
 * @see pipeline_matrice_precompile()
 *
 * @uses ecrire_fichier_php()
 * @uses clear_path_cache()
 *
 * @param array $prepend_code
 *     Code PHP à insérer avant le passage dans la chaîne des fonctions d'un pipeline
 *     Couples 'Nom du pipeline' => Code PHP à insérer
**/
function pipeline_precompile($prepend_code = []) {

	$all_pipes = $all_pipes_end = '';
	if (!empty($GLOBALS['spip_pipeline']['all'])) {
		$a = explode('||', $GLOBALS['spip_pipeline']['all'], 2);
		unset($GLOBALS['spip_pipeline']['all']);
		$all_pipes = trim(array_shift($a));
		if ($all_pipes) {
			$all_pipes = '|' . ltrim($all_pipes, '|');
		}
		if (count($a)) {
			$all_pipes_end = '||' . array_shift($a);
		}
	}
	$content = '';
	foreach ($GLOBALS['spip_pipeline'] as $action => $pipeline) {
		$s_inc = '';
		$s_call = '';
		if ($all_pipes) {
			$pipeline = preg_replace(',(\|\||$),', "$all_pipes\\1", $pipeline, 1);
		}
		if ($all_pipes_end) {
			$pipeline .= $all_pipes_end;
		}
		$pipe = array_filter(explode('|', $pipeline));
		// Eclater le pipeline en filtres et appliquer chaque filtre
		foreach ($pipe as $fonc) {
			$fonc = trim($fonc);
			$s_call .= '$val = minipipe(\'' . $fonc . '\', $val);' . "\n";
			if (isset($GLOBALS['spip_matrice'][$fonc])) {
				$file = $GLOBALS['spip_matrice'][$fonc];
				$file = "'$file'";
				// si un _DIR_XXX: est dans la chaine, on extrait la constante
				if (preg_match(',(_(DIR|ROOT)_[A-Z_]+):,Ums', $file, $regs)) {
					$dir = $regs[1];
					$root_dir = str_replace('_DIR_', '_ROOT_', $dir);
					if (defined($root_dir)) {
						$dir = $root_dir;
					}
					$file = str_replace($regs[0], "'." . $dir . ".'", $file);
					$file = str_replace("''.", '', $file);
					$file = str_replace(constant($dir), '', $file);
				}
				$s_inc .= "include_once_check($file);\n";
			}
		}
		if (strlen($s_inc)) {
			$s_inc = "static \$inc=null;\nif (!\$inc){\n$s_inc\$inc=true;\n}\n";
		}
		$content .= "// Pipeline $action \n"
			. "function execute_pipeline_$action(&\$val){\n"
			. $s_inc
			. ((isset($prepend_code[$action]) and strlen($prepend_code[$action])) ? trim($prepend_code[$action]) . "\n" : '')
			. $s_call
			. "return \$val;\n}\n";
	}
	ecrire_fichier_php(_CACHE_PIPELINES, $content);
	clear_path_cache();
}


/**
 * Indique si un chemin de plugin fait parti des plugins activés sur le site
 *
 * @param string $plug_path
 *     Chemin du plugin
 * @return bool
 *     true si le plugin est actif, false sinon
**/
function plugin_est_installe($plug_path) {
	$plugin_installes = isset($GLOBALS['meta']['plugin_installes']) ? unserialize($GLOBALS['meta']['plugin_installes']) : [];
	if (!$plugin_installes) {
		return false;
	}

	return in_array($plug_path, $plugin_installes);
}


/**
 * Parcours les plugins activés et appelle leurs fonctions d'installation si elles existent.
 *
 * Elle ajoute ensuite les plugins qui ont été installés dans la valeur "plugin_installes"
 * de la table meta. Cette meta ne contient que les noms des plugins qui ont une version_base.
 *
 * @uses plugins_installer_dist()
 **/
function plugin_installes_meta() {
	if (isset($GLOBALS['fichier_php_compile_recent'])) {
		// attendre eventuellement l'invalidation du cache opcode
		spip_attend_invalidation_opcode_cache($GLOBALS['fichier_php_compile_recent']);
	}

	$installer_plugins = charger_fonction('installer', 'plugins');
	$meta_plug_installes = [];
	foreach (unserialize($GLOBALS['meta']['plugin']) as $prefix => $resume) {
		if ($plug = $resume['dir']) {
			$infos = $installer_plugins($plug, 'install', $resume['dir_type']);
			if ($infos) {
				if (!is_array($infos) or $infos['install_test'][0]) {
					$meta_plug_installes[] = $plug;
				}
				if (is_array($infos)) {
					[$ok, $trace] = $infos['install_test'];
					$titre = _T('plugin_titre_installation', ['plugin' => typo($infos['nom'])]);
					$result = ($ok ? ((isset($infos['upgrade']) && $infos['upgrade']) ? _T('plugin_info_upgrade_ok') : _T('plugin_info_install_ok')) : _T('avis_operation_echec'));
					if (_IS_CLI) {
						include_spip('inc/filtres');
						$trace = ltrim(textebrut($trace) . "\n" . $result);
						$trace = '    ' . str_replace("\n", "\n    ", $trace);
						echo "\n" . ($ok ? 'OK  ' : '/!\ ') . textebrut($titre) . "\n",
						  $trace,
						  "\n";
					}
					else {
						include_spip('inc/filtres_boites');
						echo "<div class='install-plugins svp_retour'>"
							. boite_ouvrir($titre, ($ok ? 'success' : 'error'))
							. $trace
							. "<div class='result'>$result</div>"
							. boite_fermer()
							. '</div>';
					}
				}
			}
		}
	}
	ecrire_meta('plugin_installes', serialize($meta_plug_installes), 'non');
}

/**
 * Écrit un fichier PHP
 *
 * @param string $nom
 *     Chemin du fichier
 * @param string $contenu
 *     Contenu du fichier (sans les balises ouvrantes et fermantes de PHP)
 * @param string $comment
 *     Commentaire : code écrit en tout début de fichier, après la balise PHP ouvrante
**/
function ecrire_fichier_php($nom, $contenu, $comment = '') {
	if (!isset($GLOBALS['fichier_php_compile_recent'])) {
		$GLOBALS['fichier_php_compile_recent'] = 0;
	}

	$contenu = '<' . '?php' . "\n" . $comment . "\nif (defined('_ECRIRE_INC_VERSION')) {\n" . $contenu . "}\n?" . '>';
	// si un fichier existe deja on verifie que son contenu change avant de l'ecraser
	// si pas de modif on ne touche pas au fichier initial
	if (file_exists($nom)) {
		if (substr($nom, -4) == '.php') {
			$fichier_tmp = substr($nom, 0, -4) . '.tmp.php';
		}
		else {
			$fichier_tmp = $nom . '.tmp';
		}
		file_put_contents($fichier_tmp, $contenu);
		if (md5_file($nom) == md5_file($fichier_tmp)) {
			$GLOBALS['fichier_php_compile_recent'] = max($GLOBALS['fichier_php_compile_recent'], filemtime($nom));
			@unlink($fichier_tmp);
			return;
		}
		@unlink($fichier_tmp);
	}
	ecrire_fichier($nom, $contenu);
	$GLOBALS['fichier_php_compile_recent'] = max($GLOBALS['fichier_php_compile_recent'], filemtime($nom));
	spip_clear_opcode_cache(realpath($nom));
}
