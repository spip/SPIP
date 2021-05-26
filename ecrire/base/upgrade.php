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
 * Mise à jour de la base de données
 *
 * @package SPIP\Core\SQL\Upgrade
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

if (!defined('_UPGRADE_TIME_OUT')) {
	/**
	 * Durée en secondes pour relancer les scripts de mises à jour, x secondes
	 * avant que la durée d'exécution du script provoque un timeout
	 *
	 * @var int
	 **/
	define('_UPGRADE_TIME_OUT', 20);
}

/**
 * Programme de mise à jour des tables SQL lors d'un changement de version.
 *
 * L'entrée dans cette fonction est reservée aux mises à jour de SPIP coeur.
 *
 * Marche aussi pour les plugins en appelant directement la fonction `maj_plugin`
 * Pour que ceux-ci profitent aussi de la reprise sur interruption,
 * ils doivent simplement indiquer leur numero de version installée dans une meta
 * et fournir le tableau `$maj` à la fonction `maj_plugin`.
 * La reprise sur timeout se fait alors par la page admin_plugin et jamais par ici.
 *
 * @uses creer_base()
 * @uses maj_base()
 * @uses auth_synchroniser_distant()
 *
 * @param string $titre
 * @param string $reprise Inutilisé
 * @return void
 */
function base_upgrade_dist($titre = '', $reprise = '') {
	if (!$titre) {
		return;
	} // anti-testeur automatique
	if ($GLOBALS['spip_version_base'] != $GLOBALS['meta']['version_installee']) {
		if (!is_numeric(_request('reinstall'))) {
			include_spip('base/create');
			spip_log('recree les tables eventuellement disparues', 'maj.' . _LOG_INFO_IMPORTANTE);
			creer_base();
		}

		// quand on rentre par ici, c'est toujours une mise a jour de SPIP
		// lancement de l'upgrade SPIP
		$res = maj_base();

		if ($res) {
			// on arrete tout ici !
			exit;
		}
	}
	spip_log('Fin de mise a jour SQL. Debut m-a-j acces et config', 'maj.' . _LOG_INFO_IMPORTANTE);

	// supprimer quelques fichiers temporaires qui peuvent se retrouver invalides
	@spip_unlink(_CACHE_RUBRIQUES);
	@spip_unlink(_CACHE_PIPELINES);
	@spip_unlink(_CACHE_PLUGINS_PATH);
	@spip_unlink(_CACHE_PLUGINS_OPT);
	@spip_unlink(_CACHE_PLUGINS_FCT);
	@spip_unlink(_CACHE_CHEMIN);
	@spip_unlink(_DIR_TMP . 'plugin_xml_cache.gz');

	include_spip('inc/auth');
	auth_synchroniser_distant();
	$config = charger_fonction('config', 'inc');
	$config();
}

/**
 * Mise à jour de base de SPIP
 *
 * Exécute toutes les fonctions de mises à jour de SPIP nécessaires,
 * en fonction de la meta `version_installee` indiquant le numéro de
 * schéma actuel de la base de données.
 *
 * Les fonctions de mises à jour se trouvent dans `ecrire/maj/`
 * 
 * @note
 *     Si version nulle ou inexistante, c'est une nouvelle installation,
 *     on ne passe pas par le processus de mise à jour.
 * 
 *     De même en cas de version supérieure: ca devait être un test,
 *     il y a eu le message d'avertissement il doit savoir ce qu'il fait
 * 
 *     version_installee = YYYYMMDDNN; quand on a besoin de forcer une MAJ
 *     tel que 2021021800 où 00 est un incrément.
 *
 * @uses upgrade_test()
 * @uses maj_while()
 *
 * @param int $version_cible
 * @param string $redirect
 * @return array|bool
 */
function maj_base($version_cible = 0, $redirect = '', $debut_page = true) {

	$version_installee = $GLOBALS['meta']['version_installee'] ?? null;

	spip_log(
		"Version anterieure: $version_installee. Courante: " . $GLOBALS['spip_version_base'],
		'maj.' . _LOG_INFO_IMPORTANTE
	);
	if (!$version_installee or ($GLOBALS['spip_version_base'] < $version_installee)) {
		sql_replace(
			'spip_meta',
			array(
				'nom' => 'version_installee',
				'valeur' => $GLOBALS['spip_version_base'],
				'impt' => 'non'
			)
		);
		return false;
	}
	if (!upgrade_test()) {
		return true;
	}

	$cible = ($version_cible ? $version_cible : $GLOBALS['spip_version_base']);

	if ($version_installee < 2021010100) {
		include_spip('maj/legacy/v21');
		include_spip('maj/legacy/v30');
		include_spip('maj/legacy/v31');
		include_spip('maj/legacy/v32');
		include_spip('maj/legacy/v40');
	}

	include_spip('maj/2021');

	ksort($GLOBALS['maj']);
	$res = maj_while($version_installee, $cible, $GLOBALS['maj'], 'version_installee', 'meta', $redirect, $debut_page);
	if ($res) {
		if (!is_array($res)) {
			spip_log("Pb d'acces SQL a la mise a jour", 'maj.' . _LOG_INFO_ERREUR);
		} else {
			echo _T('avis_operation_echec') . ' ' . join(' ', $res);
			echo install_fin_html();
		}
	}

	return $res;
}

/**
 * Mise à jour d'un plugin de SPIP
 *
 * Fonction appelée par la fonction de mise à jour d'un plugin.
 * On lui fournit un tableau de fonctions élementaires
 * dont l'indice est la version.
 *
 * @uses maj_while()
 *
 * @param string $nom_meta_base_version
 *     Nom de la meta informant de la version du schéma de données du plugin installé dans SPIP
 * @param string $version_cible
 *     Version du schéma de données dans le plugin (déclaré dans paquet.xml)
 * @param array $maj
 *     Tableau d'actions à faire à l'installation (clé `create`) et pour chaque
 *     version intermédiaire entre la version actuelle du schéma du plugin dans SPIP
 *     et la version du schéma déclaré dans le plugin (ex. clé `1.1.0`).
 *
 *     Chaque valeur est un tableau contenant une liste de fonctions à exécuter,
 *     cette liste étant elle-même un tableau avec premier paramètre le nom de la fonction
 *     et les suivant les paramètres à lui passer
 *
 *     Exemple :
 *
 *         ```
 *         array(
 *             'create' => array(
 *                 array('maj_tables', array('spip_rubriques', 'spip_articles')),
 *                 array('creer_base)),
 *             '1.1.0' => array(
 *                 array('sql_alter', 'TABLE spip_articles ADD INDEX truc (truc)'))
 *         )
 *         ```
 * @param string $table_meta
 *     Nom de la table meta (sans le prefixe spip_) dans laquelle trouver la meta $nom_meta_base_version
 * @return void
 */
function maj_plugin($nom_meta_base_version, $version_cible, $maj, $table_meta = 'meta') {

	if ($table_meta !== 'meta') {
		installer_table_meta($table_meta);
	}

	$current_version = null;

	if ((!isset($GLOBALS[$table_meta][$nom_meta_base_version]))
		|| (!spip_version_compare($current_version = $GLOBALS[$table_meta][$nom_meta_base_version], $version_cible, '='))
	) {
		// $maj['create'] contient les directives propres a la premiere creation de base
		// c'est une operation derogatoire qui fait aboutir directement dans la version_cible
		if (isset($maj['create'])) {
			if (!isset($GLOBALS[$table_meta][$nom_meta_base_version])) {
				// installation : on ne fait que l'operation create
				$maj = array('init' => $maj['create']);
				// et on lui ajoute un appel a inc/config
				// pour creer les metas par defaut
				$config = charger_fonction('config', 'inc');
				$maj[$version_cible] = array(array($config));
			}
			// dans tous les cas enlever cet index du tableau
			unset($maj['create']);
		}
		// si init, deja dans le bon ordre
		if (!isset($maj['init'])) {
			include_spip('inc/plugin'); // pour spip_version_compare
			uksort($maj, 'spip_version_compare');
		}

		// la redirection se fait par defaut sur la page d'administration des plugins
		// sauf lorsque nous sommes sur l'installation de SPIP
		// ou define _REDIRECT_MAJ_PLUGIN
		$redirect = (defined('_REDIRECT_MAJ_PLUGIN') ? _REDIRECT_MAJ_PLUGIN : generer_url_ecrire('admin_plugin'));
		if (defined('_ECRIRE_INSTALL')) {
			$redirect = parametre_url(generer_url_ecrire('install'), 'etape', _request('etape'));
		}

		$res = maj_while($current_version, $version_cible, $maj, $nom_meta_base_version, $table_meta, $redirect);
		if ($res) {
			if (!is_array($res)) {
				spip_log("Pb d'acces SQL a la mise a jour", 'maj.' . _LOG_INFO_ERREUR);
			} else {
				echo '<p>' . _T('avis_operation_echec') . ' ' . join(' ', $res) . '</p>';
			}
		}
	}
}

/**
 * Relancer le hit de mise à jour avant timeout
 *
 * si pas de redirect fourni, on redirige vers `exec=upgrade` pour finir
 * ce qui doit être une mise à jour SPIP
 *
 * @uses redirige_formulaire()
 *
 * @param string $meta
 * @param string $table
 * @param string $redirect
 * @return void
 */
function relance_maj($meta, $table, $redirect = '') {
	include_spip('inc/headers');
	if (!$redirect) {
		// recuperer la valeur installee en cours
		// on la tronque numeriquement, elle ne sert pas reellement
		// sauf pour verifier que ce n'est pas oui ou non
		// sinon is_numeric va echouer sur un numero de version 1.2.3
		$installee = intval($GLOBALS[$table][$meta]);
		$redirect = generer_url_ecrire('upgrade', "reinstall=$installee&meta=$meta&table=$table", true);
	}
	echo redirige_formulaire($redirect);
	exit();
}

/**
 * Initialiser la page pour l'affichage des progrès de l'upgrade
 * uniquement si la page n'a pas déjà été initilalisée
 *
 * @param string $installee
 * @param string $meta
 * @param string $table
 * @return void
 */
function maj_debut_page($installee, $meta, $table) {
	static $done = false;
	if ($done) {
		return;
	}
	include_spip('inc/minipres');
	@ini_set('zlib.output_compression', '0'); // pour permettre l'affichage au fur et a mesure
	$timeout = _UPGRADE_TIME_OUT * 2;
	$titre = _T('titre_page_upgrade');
	$balise_img = charger_filtre('balise_img');
	$titre .= $balise_img(chemin_image('loader.svg'),'','loader');
	echo(install_debut_html($titre));
	// script de rechargement auto sur timeout
	$redirect = generer_url_ecrire('upgrade', "reinstall=$installee&meta=$meta&table=$table", true);
	echo http_script("window.setTimeout('location.href=\"" . $redirect . "\";'," . ($timeout * 1000) . ')');
	echo "<div style='text-align: left'>\n";
	if (ob_get_level()) {
		ob_flush();
	}
	flush();
	$done = true;
}


/**
 * Gestion des mises à jour de SPIP et des plugins
 *
 * À partir des versions > 1.926 (i.e SPIP > 1.9.2), cette fonction gere les MAJ.
 *
 * Se relancer soi-même pour éviter l'interruption pendant une operation SQL
 * (qu'on espère pas trop longue chacune) évidemment en ecrivant dans la meta
 * à quel numero on en est.
 *
 * Cette fonction peut servir aux plugins qui doivent donner comme arguments :
 *
 * 1. le numero de version courant (numéro de version 1.2.3 ou entier)
 * 2. le numero de version à atteindre (numéro de version 1.2.3 ou entier)
 * 3. le tableau des instructions de mise à jour à exécuter
 *    Pour profiter du mécanisme de reprise sur interruption il faut de plus
 * 4. le nom de la meta permettant de retrouver tout ca
 * 5. la table des meta ou elle se trouve (`$table_prefix . '_meta'` par défaut)
 *    (cf début de fichier)
 *
 * les fonctions `sql_xx` appelées lors des mises à jour sont supposées
 * atomiques et ne sont pas relancées en cas de timeout, mais les fonctions
 * spécifiques sont relancées jusqu'à ce qu'elles finissent.
 * Elles doivent donc s'assurer de progresser à chaque reprise.
 *
 * @uses maj_debut_page()
 * @uses serie_alter()
 * @uses relance_maj()
 *
 * @param string $installee
 * @param string $cible
 * @param array $maj
 * @param string $meta
 * @param string $table
 * @param string $redirect
 * @param bool $debut_page
 * @return array
 *    - tableau (étape, sous-étape) en cas d'échec,
 *    - tableau vide sinon.
 */
function maj_while($installee, $cible, $maj, $meta = '', $table = 'meta', $redirect = '', $debut_page = false) {
	# inclusions pour que les procedures d'upgrade disposent des fonctions de base
	include_spip('base/create');
	include_spip('base/abstract_sql');
	$trouver_table = charger_fonction('trouver_table', 'base');
	include_spip('inc/plugin'); // pour spip_version_compare
	$n = 0;
	$time = time();

	if (!defined('_TIME_OUT')) {
		/**
		 * Définir le timeout qui peut-être utilisé dans les fonctions
		 * de mises à jour qui durent trop longtemps
		 *
		 * À utiliser tel que : `if (time() >= _TIME_OUT)`
		 *
		 * @var int
		 */
		define('_TIME_OUT', $time + _UPGRADE_TIME_OUT);
	}

	foreach ($maj as $v => $operations) {
		// si une maj pour cette version
		if ($v == 'init' or
			(spip_version_compare($v, $installee, '>')
				and spip_version_compare($v, $cible, '<='))
		) {
			if ($debut_page) {
				maj_debut_page($v, $meta, $table);
			}
			echo "MAJ $v";
			$etape = serie_alter($v, $operations, $meta, $table, $redirect);
			$trouver_table(''); // vider le cache des descriptions de table
			# echec sur une etape en cours ?
			# on sort
			if ($etape) {
				return array($v, $etape);
			}
			$n = time() - $time;
			spip_log("$table $meta: $v en $n secondes", 'maj.' . _LOG_INFO_IMPORTANTE);
			if ($meta) {
				ecrire_meta($meta, $installee = $v, 'oui', $table);
			}
			echo '<br />';
		}
		if (time() >= _TIME_OUT) {
			relance_maj($meta, $table, $redirect);
		}
	}
	$trouver_table(''); // vider le cache des descriptions de table
	// indispensable pour les chgt de versions qui n'ecrivent pas en base
	// tant pis pour la redondance eventuelle avec ci-dessus
	if ($meta) {
		ecrire_meta($meta, $cible, 'oui', $table);
	}
	spip_log("MAJ terminee. $meta: $installee", 'maj.' . _LOG_INFO_IMPORTANTE);

	return array();
}

/**
 * Appliquer une serie de changements qui risquent de partir en timeout
 *
 * Alter crée une copie temporaire d'une table, c'est lourd.
 *
 * @uses relance_maj()
 *
 * @param string $serie
 *   numero de version upgrade
 * @param array $q
 *   tableau des operations pour cette version
 * @param string $meta
 *   nom de la meta qui contient le numero de version
 * @param string $table
 *   nom de la table meta
 * @param string $redirect
 *   url de redirection en cas d'interruption
 * @return int
 */
function serie_alter($serie, $q = array(), $meta = '', $table = 'meta', $redirect = '') {
	$meta2 = $meta . '_maj_' . $serie;
	$etape = 0;
	if (isset($GLOBALS[$table][$meta2])) {
		$etape = intval($GLOBALS[$table][$meta2]);
	}
	foreach ($q as $i => $r) {
		if ($i >= $etape) {
			$msg = "maj $table $meta2 etape $i";
			if (is_array($r)
				and function_exists($f = array_shift($r))
			) {
				// note: $r (arguments de la fonction $f) peut avoir des données tabulaires
				spip_log("$msg: $f " . @join(',', $r), 'maj.' . _LOG_INFO_IMPORTANTE);
				// pour les fonctions atomiques sql_xx
				// on enregistre le meta avant de lancer la fonction,
				// de maniere a eviter de boucler sur timeout
				// mais pour les fonctions complexes,
				// il faut les rejouer jusqu'a achevement.
				// C'est a elle d'assurer qu'elles progressent a chaque rappel
				if (strncmp($f, 'sql_', 4) == 0) {
					ecrire_meta($meta2, $i + 1, 'non', $table);
				}
				echo " <span title='$i'>.</span>";
				call_user_func_array($f, $r);
				// si temps imparti depasse, on relance sans ecrire en meta
				// car on est peut etre sorti sur timeout si c'est une fonction longue
				if (time() >= _TIME_OUT) {
					relance_maj($meta, $table, $redirect);
				}
				ecrire_meta($meta2, $i + 1, 'non', $table);
				spip_log("$meta2: ok", 'maj.' . _LOG_INFO_IMPORTANTE);
			} else {
				if (!is_array($r)) {
					spip_log("maj $i format incorrect", 'maj.' . _LOG_ERREUR);
				} else {
					spip_log("maj $i fonction $f non definie", 'maj.' . _LOG_ERREUR);
				}
				// en cas d'erreur serieuse, on s'arrete
				// mais on permet de passer par dessus en rechargeant la page.
				return $i + 1;
			}
		}
	}
	effacer_meta($meta2, $table);

	return 0;
}


/**
 * Mise à jour des types MIME de documents
 *
 * Fonction utilisé par les vieilles mises à jour de SPIP, à appeler dans
 * le tableau `$maj` quand on rajoute des types MIME. Remplacé actuellement
 * par le plugin Medias.
 *
 * @deprecated 3.1
 * @see Utiliser directement `creer_base_types_doc()` du plugin Medias
 * @example
 *     ```
 *     $GLOBALS['maj'][1953] = array(array('upgrade_types_documents'));
 *
 *     ```
 * @uses creer_base_types_doc()
 *
 **/
function upgrade_types_documents() {
	if (include_spip('base/medias')
		and function_exists('creer_base_types_doc')
	) {
		creer_base_types_doc();
	}
}

/**
 * Vérifie qu'il est possible d'ajouter une colonne à une table SQL
 *
 * @return bool True si possible.
 **/
function upgrade_test() {
	sql_drop_table('spip_test', true);
	sql_create('spip_test', array('a' => 'int'));
	sql_alter('TABLE spip_test ADD b INT');
	sql_insertq('spip_test', array('b' => 1), array('field' => array('b' => 'int')));
	$result = sql_select('b', 'spip_test');
	// ne pas garder le resultat de la requete sinon sqlite3
	// ne peut pas supprimer la table spip_test lors du sql_alter qui suit
	// car cette table serait alors 'verouillee'
	$result = $result ? true : false;
	sql_alter('TABLE spip_test DROP b');

	return $result;
}
