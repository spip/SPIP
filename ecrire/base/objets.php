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
 * Fonctions relatives aux objets éditoriaux et SQL
 *
 * @package SPIP\Core\SQL\Tables
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Merge dans un tableau une de ses clés avec une valeur
 *
 * @param array $table
 *     Tableau dont on veut compléter une clé
 * @param string $index
 *     Clé du tableau que l'on souhaite compléter
 * @param array $valeur
 *     Sous tableau à merger dans la clé.
 * @return void
 **/
function array_set_merge(&$table, $index, $valeur) {
	if (!isset($table[$index])) {
		$table[$index] = $valeur;
	} else {
		$table[$index] = array_merge($table[$index], $valeur);
	}
}

/**
 * Lister les infos de toutes les tables sql declarées
 *
 * Si un argument est fourni, on ne renvoie que les infos de cette table.
 * Elle est auto-declarée si inconnue jusqu'alors.
 *
 * @api
 * @param string $table_sql
 *   table_sql demandee explicitement
 * @param array $desc
 *   description connue de la table sql demandee
 * @return array|bool
 */
function lister_tables_objets_sql($table_sql = null, $desc = []) {
	static $deja_la = false;
	static $infos_tables = null;
	static $md5 = null;
	static $plugin_hash = null;

	// plugins hash connu ? non si _CACHE_PLUGINS_OPT est pas encore chargé.
	$_PLUGINS_HASH = defined('_PLUGINS_HASH') ? _PLUGINS_HASH : '!_CACHE_PLUGINS_OPT';

	// prealablement recuperer les tables_principales
	if (is_null($infos_tables) or $plugin_hash !== $_PLUGINS_HASH) {
		// pas de reentrance (cas base/serial)
		if ($deja_la) {
			spip_log('Re-entrance anormale sur lister_tables_objets_sql : '
				. var_export(debug_backtrace(), true), _LOG_CRITIQUE);

			return ($table_sql === '::md5' ? $md5 : []);
		}
		$deja_la = true;
		$plugin_hash = $_PLUGINS_HASH; // avant de lancer les pipelines

		// recuperer les declarations explicites ancienne mode
		// qui servent a completer declarer_tables_objets_sql
		base_serial($GLOBALS['tables_principales']);
		base_auxiliaires($GLOBALS['tables_auxiliaires']);
		$infos_tables = [
			'spip_articles' => [
				'page' => 'article',
				'texte_retour' => 'icone_retour_article',
				'texte_modifier' => 'icone_modifier_article',
				'texte_creer' => 'icone_ecrire_article',
				'texte_objets' => 'public:articles',
				'texte_objet' => 'public:article',
				'texte_signale_edition' => 'texte_travail_article',
				'info_aucun_objet' => 'info_aucun_article',
				'info_1_objet' => 'info_1_article',
				'info_nb_objets' => 'info_nb_articles',
				'texte_logo_objet' => 'logo_article',
				'texte_langue_objet' => 'titre_langue_article',
				'texte_definir_comme_traduction_objet' => 'trad_lier',
				'titre' => 'titre, lang',
				'date' => 'date',
				'principale' => 'oui',
				'introduction_longueur' => '500',
				'champs_editables' => [
					'surtitre',
					'titre',
					'soustitre',
					'descriptif',
					'nom_site',
					'url_site',
					'chapo',
					'texte',
					'ps',
					'virtuel'
				],
				'champs_versionnes' => [
					'id_rubrique',
					'surtitre',
					'titre',
					'soustitre',
					'jointure_auteurs',
					'descriptif',
					'nom_site',
					'url_site',
					'chapo',
					'texte',
					'ps'
				],
				'field' => [
					'id_article' => 'bigint(21) NOT NULL',
					'surtitre' => "text DEFAULT '' NOT NULL",
					'titre' => "text DEFAULT '' NOT NULL",
					'soustitre' => "text DEFAULT '' NOT NULL",
					'id_rubrique' => "bigint(21) DEFAULT '0' NOT NULL",
					'descriptif' => "text DEFAULT '' NOT NULL",
					'chapo' => "mediumtext DEFAULT '' NOT NULL",
					'texte' => "longtext DEFAULT '' NOT NULL",
					'ps' => "mediumtext DEFAULT '' NOT NULL",
					'date' => "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
					'statut' => "varchar(10) DEFAULT '0' NOT NULL",
					'id_secteur' => "bigint(21) DEFAULT '0' NOT NULL",
					'maj' => 'TIMESTAMP',
					'export' => "VARCHAR(10) DEFAULT 'oui'",
					'date_redac' => "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
					'visites' => "integer DEFAULT '0' NOT NULL",
					'referers' => "integer DEFAULT '0' NOT NULL",
					'popularite' => "DOUBLE DEFAULT '0' NOT NULL",
					'accepter_forum' => "CHAR(3) DEFAULT '' NOT NULL",
					'date_modif' => "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
					'lang' => "VARCHAR(10) DEFAULT '' NOT NULL",
					'langue_choisie' => "VARCHAR(3) DEFAULT 'non'",
					'id_trad' => "bigint(21) DEFAULT '0' NOT NULL",
					'nom_site' => "tinytext DEFAULT '' NOT NULL",
					'url_site' => "text DEFAULT '' NOT NULL",
					'virtuel' => "text DEFAULT '' NOT NULL",
				],
				'key' => [
					'PRIMARY KEY' => 'id_article',
					'KEY id_rubrique' => 'id_rubrique',
					'KEY id_secteur' => 'id_secteur',
					'KEY id_trad' => 'id_trad',
					'KEY lang' => 'lang',
					'KEY statut' => 'statut, date',
				],
				'join' => [
					'id_article' => 'id_article',
					'id_rubrique' => 'id_rubrique'
				],
				'parent' => [
					['type' => 'rubrique', 'champ' => 'id_rubrique']
				],
				'rechercher_champs' => [
					'surtitre' => 5,
					'titre' => 8,
					'soustitre' => 5,
					'chapo' => 3,
					'texte' => 1,
					'ps' => 1,
					'nom_site' => 1,
					'url_site' => 1,
					'descriptif' => 4
				],
				'rechercher_jointures' => [
					'auteur' => ['nom' => 10],
				],
				'statut' => [
					[
						'champ' => 'statut',
						'publie' => 'publie',
						'previsu' => 'publie,prop,prepa/auteur',
						'post_date' => 'date',
						'exception' => ['statut', 'tout']
					]
				],
				'statut_titres' => [
					'prepa' => 'info_article_redaction',
					'prop' => 'info_article_propose',
					'publie' => 'info_article_publie',
					'refuse' => 'info_article_refuse',
					'poubelle' => 'info_article_supprime'
				],
				'statut_textes_instituer' => [
					'prepa' => 'texte_statut_en_cours_redaction',
					'prop' => 'texte_statut_propose_evaluation',
					'publie' => 'texte_statut_publie',
					'refuse' => 'texte_statut_refuse',
					'poubelle' => 'texte_statut_poubelle',
				],
				'texte_changer_statut' => 'texte_article_statut',
				'aide_changer_statut' => 'artstatut',
				'tables_jointures' => [
					'profondeur' => 'rubriques',
					#'id_auteur' => 'auteurs_liens' // declaration generique plus bas
				],
			],
			'spip_auteurs' => [
				'page' => 'auteur',
				'texte_retour' => 'icone_retour',
				'texte_ajouter' => 'titre_ajouter_un_auteur',
				'texte_modifier' => 'admin_modifier_auteur',
				'texte_objets' => 'icone_auteurs',
				'texte_objet' => 'public:auteur',
				'info_aucun_objet' => 'info_aucun_auteur',
				'info_1_objet' => 'info_1_auteur',
				'info_nb_objets' => 'info_nb_auteurs',
				'texte_logo_objet' => 'logo_auteur',
				'texte_creer_associer' => 'creer_et_associer_un_auteur',
				'titre' => "nom AS titre, '' AS lang",
				'date' => 'date',
				'principale' => 'oui',
				'champs_editables' => ['nom', 'email', 'bio', 'nom_site', 'url_site', 'imessage', 'pgp'],
				'champs_versionnes' => ['nom', 'bio', 'email', 'nom_site', 'url_site', 'login'],
				'field' => [
					'id_auteur' => 'bigint(21) NOT NULL',
					'nom' => "text DEFAULT '' NOT NULL",
					'bio' => "text DEFAULT '' NOT NULL",
					'email' => "tinytext DEFAULT '' NOT NULL",
					'nom_site' => "tinytext DEFAULT '' NOT NULL",
					'url_site' => "text DEFAULT '' NOT NULL",
					'login' => 'VARCHAR(255) BINARY',
					'pass' => "tinytext DEFAULT '' NOT NULL",
					'low_sec' => "tinytext DEFAULT '' NOT NULL",
					'statut' => "varchar(255)  DEFAULT '0' NOT NULL",
					'webmestre' => "varchar(3)  DEFAULT 'non' NOT NULL",
					'maj' => 'TIMESTAMP',
					'pgp' => "TEXT DEFAULT '' NOT NULL",
					'htpass' => "tinytext DEFAULT '' NOT NULL",
					'en_ligne' => "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
					'alea_actuel' => 'tinytext',
					'alea_futur' => 'tinytext',
					'prefs' => 'text',
					'cookie_oubli' => 'tinytext',
					'source' => "VARCHAR(10) DEFAULT 'spip' NOT NULL",
					'lang' => "VARCHAR(10) DEFAULT '' NOT NULL",
					'imessage' => "VARCHAR(3) DEFAULT '' NOT NULL"
				],
				'key' => [
					'PRIMARY KEY' => 'id_auteur',
					'KEY login' => 'login',
					'KEY statut' => 'statut',
					'KEY en_ligne' => 'en_ligne',
				],
				'join' => [
					'id_auteur' => 'id_auteur',
					'login' => 'login'
				],
				'rechercher_champs' => [
					'nom' => 5,
					'bio' => 1,
					'email' => 1,
					'nom_site' => 1,
					'url_site' => 1,
					'login' => 1
				],
				// 2 conditions pour les auteurs : statut!=poubelle,
				// et avoir des articles publies
				'statut' => [
					[
						'champ' => 'statut',
						'publie' => '!5poubelle',
						'previsu' => '!5poubelle',
						'exception' => 'statut'
					],
					[
						'champ' => [
							['spip_auteurs_liens', 'id_auteur'],
							[
								'spip_articles',
								['id_objet', 'id_article', 'objet', 'article']
							],
							'statut'
						],
						'publie' => 'publie',
						'previsu' => '!',
						'post_date' => 'date',
						'exception' => ['statut', 'lien', 'tout']
					],
				],
				'statut_images' => [
					'auteur-6forum-16.png',
					'0minirezo' => 'auteur-0minirezo-16.png',
					'1comite' => 'auteur-1comite-16.png',
					'6forum' => 'auteur-6forum-16.png',
					'5poubelle' => 'auteur-5poubelle-16.png',
					'nouveau' => ''
				],
				'statut_titres' => [
					'titre_image_visiteur',
					'0minirezo' => 'titre_image_administrateur',
					'1comite' => 'titre_image_redacteur_02',
					'6forum' => 'titre_image_visiteur',
					'5poubelle' => 'titre_image_auteur_supprime',
				],
				'tables_jointures' => [#'auteurs_liens' // declaration generique plus bas
				],
			],
			'spip_rubriques' => [
				'page' => 'rubrique',
				'url_voir' => 'rubrique',
				'url_edit' => 'rubrique_edit',
				'texte_retour' => 'icone_retour',
				'texte_objets' => 'public:rubriques',
				'texte_objet' => 'public:rubrique',
				'texte_modifier' => 'icone_modifier_rubrique',
				'texte_creer' => 'icone_creer_rubrique',
				'texte_ajouter' => 'titre_ajouter_une_rubrique',
				'texte_creer_associer' => 'creer_et_associer_une_rubrique',
				'info_aucun_objet' => 'info_aucun_rubrique',
				'info_1_objet' => 'info_1_rubrique',
				'info_nb_objets' => 'info_nb_rubriques',
				'texte_logo_objet' => 'logo_rubrique',
				'texte_langue_objet' => 'titre_langue_rubrique',
				'texte_definir_comme_traduction_objet' => 'texte_definir_comme_traduction_rubrique',
				'titre' => 'titre, lang',
				'date' => 'date',
				'principale' => 'oui',
				'introduction_longueur' => '600',
				'champs_editables' => ['titre', 'texte', 'descriptif', 'extra'],
				'champs_versionnes' => ['titre', 'descriptif', 'texte'],
				'field' => [
					'id_rubrique' => 'bigint(21) NOT NULL',
					'id_parent' => "bigint(21) DEFAULT '0' NOT NULL",
					'titre' => "text DEFAULT '' NOT NULL",
					'descriptif' => "text DEFAULT '' NOT NULL",
					'texte' => "longtext DEFAULT '' NOT NULL",
					'id_secteur' => "bigint(21) DEFAULT '0' NOT NULL",
					'maj' => 'TIMESTAMP',
					'statut' => "varchar(10) DEFAULT '0' NOT NULL",
					'date' => "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
					'lang' => "VARCHAR(10) DEFAULT '' NOT NULL",
					'langue_choisie' => "VARCHAR(3) DEFAULT 'non'",
					'statut_tmp' => "varchar(10) DEFAULT '0' NOT NULL",
					'date_tmp' => "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
					'profondeur' => "smallint(5) DEFAULT '0' NOT NULL"
				],
				'key' => [
					'PRIMARY KEY' => 'id_rubrique',
					'KEY lang' => 'lang',
					'KEY id_parent' => 'id_parent',
				],
				'parent' => [
					['type' => 'rubrique', 'champ' => 'id_parent']
				],
				'rechercher_champs' => [
					'titre' => 8,
					'descriptif' => 5,
					'texte' => 1
				],
				'statut' => [
					[
						'champ' => 'statut',
						'publie' => 'publie',
						'previsu' => '!',
						'exception' => ['statut', 'tout']
					],
				],
				'tables_jointures' => [#'id_auteur' => 'auteurs_liens' // declaration generique plus bas
				],
			],
			// toutes les tables ont le droit a une jointure sur les auteurs
			['tables_jointures' => ['id_auteur' => 'auteurs_liens']]
		];

		// avant d'appeller les pipeline qui peuvent generer une reentrance a l'install
		// initialiser la signature
		$md5 = md5(serialize($infos_tables));

		$GLOBALS['tables_principales'] = pipeline('declarer_tables_principales', $GLOBALS['tables_principales']);
		$GLOBALS['tables_auxiliaires'] = pipeline('declarer_tables_auxiliaires', $GLOBALS['tables_auxiliaires']);
		$infos_tables = pipeline('declarer_tables_objets_sql', $infos_tables);

		// completer les informations manquantes ou implicites
		$all = [];
		foreach (array_keys($infos_tables) as $t) {
			// les cles numeriques servent a declarer
			// les proprietes applicables a tous les objets
			// on les mets de cote
			if (is_numeric($t)) {
				$all = array_merge_recursive($all, $infos_tables[$t]);
				unset($infos_tables[$t]);
			} else {
				$infos_tables[$t] = renseigner_table_objet_sql($t, $infos_tables[$t]);
			}
		}

		// repercuter les proprietes generales communes a tous les objets
		foreach (array_keys($infos_tables) as $t) {
			foreach ($all as $i => $v) {
				if (in_array($i, ['tables_jointures', 'champs_versionnes'])) {
					$add = $all[$i];
					// eviter les doublons de declaration de table jointure (ex des mots sur auteurs)
					// pour les declarations generiques avec cles numeriques
					if ($i == 'tables_jointures' and isset($infos_tables[$t][$i]) and count($infos_tables[$t][$i])) {
						$doublons = array_intersect($infos_tables[$t][$i], $add);
						foreach ($doublons as $d) {
							if (
								is_numeric(array_search($d, $infos_tables[$t][$i]))
								and is_numeric($k = array_search($d, $add))
							) {
								unset($add[$k]);
							}
						}
					}
					$infos_tables[$t][$i] = array_merge(isset($infos_tables[$t][$i]) ? $infos_tables[$t][$i] : [], $add);
				} else {
					$infos_tables[$t][$i] = array_merge_recursive(
						isset($infos_tables[$t][$i]) ? $infos_tables[$t][$i] : [],
						$all[$i]
					);
				}
			}
		}

		// completer les tables principales et auxiliaires
		// avec celles declarees uniquement dans declarer_table_objets_sql
		// pour assurer la compat en transition
		foreach ($infos_tables as $table => $infos) {
			$principale_ou_auxiliaire = ($infos['principale'] ? 'tables_principales' : 'tables_auxiliaires');
			// memoriser des champs eventuels declares par des plugins dans le pipeline tables_xxx
			// qui a ete appelle avant
			$mem = (isset($GLOBALS[$principale_ou_auxiliaire][$table]) ? $GLOBALS[$principale_ou_auxiliaire][$table] : []);
			// l'ajouter au tableau
			$GLOBALS[$principale_ou_auxiliaire][$table] = [];
			if (isset($infos['field']) and isset($infos['key'])) {
				foreach (['field', 'key', 'join'] as $k) {
					if (isset($infos_tables[$table][$k])) {
						$GLOBALS[$principale_ou_auxiliaire][$table][$k] = &$infos_tables[$table][$k];
					}
				}
			} else {
				// ici on ne renvoie que les declarations, donc RIEN
				// pour avoir la vrai description en base, il faut passer par trouver_table
				$GLOBALS[$principale_ou_auxiliaire][$table] = [];
			}
			if (count($mem)) {
				foreach (array_keys($mem) as $k) {
					if (isset($GLOBALS[$principale_ou_auxiliaire][$table][$k])) {
						$GLOBALS[$principale_ou_auxiliaire][$table][$k] = array_merge(
							$GLOBALS[$principale_ou_auxiliaire][$table][$k],
							$mem[$k]
						);
					} else {
						$GLOBALS[$principale_ou_auxiliaire][$table][$k] = $mem[$k];
					}
				}
			}
		}

		// recuperer les interfaces (table_titre, table_date)
		// on ne le fait que dans un second temps pour que table_objet soit fonctionnel
		// dans le pipeline de declarer_tables_interfaces
		include_spip('public/interfaces');
		foreach (array_keys($infos_tables) as $t) {
			$infos_tables[$t] = renseigner_table_objet_interfaces($t, $infos_tables[$t]);
		}

		$deja_la = false;
		// signature
		$md5 = md5(serialize($infos_tables));
	}
	if ($table_sql === '::md5') {
		return $md5;
	}
	if ($table_sql and !isset($infos_tables[$table_sql])) {
		#$desc = renseigner_table_objet_sql($table_sql,$desc);
		$desc = renseigner_table_objet_interfaces($table_sql, $desc);

		return $desc;
	}
	if ($table_sql) {
		return isset($infos_tables[$table_sql]) ? $infos_tables[$table_sql] : [];
	}

	return $infos_tables;
}


/**
 * Déclare les tables principales du Core
 *
 * Tables principales, hors objets éditoriaux.
 *
 * @param array $tables_principales
 *     Description des tables principales déjà déclarées
 * @return void
 **/
function base_serial(&$tables_principales) {

	$spip_jobs = [
		'id_job' => 'bigint(21) NOT NULL',
		'descriptif' => "text DEFAULT '' NOT NULL",
		'fonction' => 'varchar(255) NOT NULL', //nom de la fonction
		'args' => "longblob DEFAULT '' NOT NULL", // arguments
		'md5args' => "char(32) NOT NULL default ''", // signature des arguments
		'inclure' => 'varchar(255) NOT NULL', // fichier a inclure ou path/ pour charger_fonction
		'priorite' => 'smallint(6) NOT NULL default 0',
		'date' => "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL", // date au plus tot
		'status' => 'tinyint NOT NULL default 1',
	];

	$spip_jobs_key = [
		'PRIMARY KEY' => 'id_job',
		'KEY date' => 'date',
		'KEY status' => 'status',
	];

	/// Attention: mes_fonctions peut avoir deja defini cette variable
	/// il faut donc rajouter, mais pas reinitialiser
	$tables_principales['spip_jobs'] = ['field' => &$spip_jobs, 'key' => &$spip_jobs_key];
}


/**
 * Déclare les tables auxiliaires du Core
 *
 * @param array $tables_auxiliaires
 *     Description des tables auxiliaires déjà déclarées
 * @return void
 **/
function base_auxiliaires(&$tables_auxiliaires) {
	$spip_resultats = [
		'recherche' => "char(16) DEFAULT '' NOT NULL",
		'id' => 'INT UNSIGNED NOT NULL',
		'points' => "INT UNSIGNED DEFAULT '0' NOT NULL",
		'table_objet' => "varchar(30) DEFAULT '' NOT NULL",
		'serveur' => "char(16) DEFAULT '' NOT NULL", // hash md5 partiel du serveur de base ('' pour le serveur principal)
		'maj' => 'TIMESTAMP'
	];

	$spip_resultats_key = [// pas de cle ni index, ca fait des insertions plus rapides et les requetes jointes utilisees en recheche ne sont pas plus lentes ...
	];

	$spip_auteurs_liens = [
		'id_auteur' => "bigint(21) DEFAULT '0' NOT NULL",
		'id_objet' => "bigint(21) DEFAULT '0' NOT NULL",
		'objet' => "VARCHAR (25) DEFAULT '' NOT NULL",
		'vu' => "VARCHAR(6) DEFAULT 'non' NOT NULL"
	];

	$spip_auteurs_liens_key = [
		'PRIMARY KEY' => 'id_auteur,id_objet,objet',
		'KEY id_auteur' => 'id_auteur',
		'KEY id_objet' => 'id_objet',
		'KEY objet' => 'objet',
	];

	$spip_meta = [
		'nom' => 'VARCHAR (255) NOT NULL',
		'valeur' => "text DEFAULT ''",
		'impt' => "ENUM('non', 'oui') DEFAULT 'oui' NOT NULL",
		'maj' => 'TIMESTAMP'
	];

	$spip_meta_key = [
		'PRIMARY KEY' => 'nom'
	];

	$spip_jobs_liens = [
		'id_job' => "bigint(21) DEFAULT '0' NOT NULL",
		'id_objet' => "bigint(21) DEFAULT '0' NOT NULL",
		'objet' => "VARCHAR (25) DEFAULT '' NOT NULL",
	];

	$spip_jobs_liens_key = [
		'PRIMARY KEY' => 'id_job,id_objet,objet',
		'KEY id_job' => 'id_job'
	];

	$tables_auxiliaires['spip_auteurs_liens'] = [
		'field' => &$spip_auteurs_liens,
		'key' => &$spip_auteurs_liens_key
	];

	$tables_auxiliaires['spip_meta'] = [
		'field' => &$spip_meta,
		'key' => &$spip_meta_key
	];
	$tables_auxiliaires['spip_resultats'] = [
		'field' => &$spip_resultats,
		'key' => &$spip_resultats_key
	];
	$tables_auxiliaires['spip_jobs_liens'] = [
		'field' => &$spip_jobs_liens,
		'key' => &$spip_jobs_liens_key
	];
}


/**
 * Auto remplissage des informations non explicites
 * sur un objet d'une table sql
 *
 * - table_objet
 * - table_objet_surnoms
 * - type
 * - type_surnoms
 * - url_voir
 * - url_edit
 * - icone_objet
 *
 * - texte_retour
 * - texte_modifier
 * - texte_creer
 * - texte_creer_associer
 * - texte_ajouter
 * - texte_objets
 * - texte_objet
 *
 * - info_aucun_objet
 * - info_1_objet
 * - info_nb_objets
 *
 * - texte_logo_objet
 * - texte_langue_objet
 * - texte_definir_comme_traduction_objet
 *
 * - principale
 * - champs_contenu : utlisé pour générer l'affichage par défaut du contenu
 * - editable
 * - champs_editables : utilisé pour prendre en compte le post lors de l'édition
 *
 * - champs_versionnes
 *
 * L'objet doit définir de lui même ces champs pour gérer des statuts :
 *     - statut
 *     - statut_images
 *     - statut_titres
 *     - statut_textes_instituer
 *     - texte_changer_statut
 *     - aide_changer_statut
 *
 * - modeles : permet de declarer les modeles associes a cet objet
 *
 * Les infos non renseignées sont auto-déduites par conventions
 * ou laissées vides
 *
 * @param string $table_sql
 * @param array $infos
 * @return array
 */
function renseigner_table_objet_sql($table_sql, &$infos) {
	if (!isset($infos['type'])) {
		// si on arrive de base/trouver_table, on a la cle primaire :
		// s'en servir pour extrapoler le type
		if (isset($infos['key']['PRIMARY KEY'])) {
			$primary = $infos['key']['PRIMARY KEY'];
			$primary = explode(',', $primary);
			$primary = reset($primary);
			$infos['type'] = preg_replace(',^spip_|^id_|s$,', '', $primary);
		} else {
			$infos['type'] = preg_replace(',^spip_|s$,', '', $table_sql);
		}
	}
	if (!isset($infos['type_surnoms'])) {
		$infos['type_surnoms'] = [];
	}

	if (!isset($infos['table_objet'])) {
		$infos['table_objet'] = preg_replace(',^spip_,', '', $table_sql);
	}
	if (!isset($infos['table_objet_surnoms'])) {
		$infos['table_objet_surnoms'] = [];
	}

	if (!isset($infos['principale'])) {
		$infos['principale'] = (isset($GLOBALS['tables_principales'][$table_sql]) ? 'oui' : false);
	}

	// normaliser pour pouvoir tester en php $infos['principale']?
	// et dans une boucle {principale=oui}
	$infos['principale'] = (($infos['principale'] and $infos['principale'] != 'non') ? 'oui' : false);

	// declarer et normaliser pour pouvoir tester en php $infos['editable']?
	// et dans une boucle {editable=oui}
	if (!isset($infos['editable'])) {
		$infos['editable'] = 'oui';
	}

	$infos['editable'] = (($infos['editable'] and $infos['editable'] != 'non') ? 'oui' : false);

	// les urls publiques sont par defaut page=type pour les tables principales, et rien pour les autres
	// seules les exceptions sont donc a declarer
	if (!isset($infos['page'])) {
		$infos['page'] = ($infos['principale'] ? $infos['type'] : '');
	}

	if (!isset($infos['url_voir'])) {
		$infos['url_voir'] = $infos['type'];
	}
	if (!isset($infos['url_edit'])) {
		$infos['url_edit'] = $infos['url_voir'] . ($infos['editable'] ? '_edit' : '');
	}
	if (!isset($infos['icone_objet'])) {
		$infos['icone_objet'] = $infos['type'];
	}

	// chaines de langue
	// par defaut : objet:icone_xxx_objet
	if (!isset($infos['texte_retour'])) {
		$infos['texte_retour'] = 'icone_retour';
	}
	if (!isset($infos['texte_modifier'])) {
		$infos['texte_modifier'] = $infos['type'] . ':' . 'icone_modifier_' . $infos['type'];
	}
	if (!isset($infos['texte_creer'])) {
		$infos['texte_creer'] = $infos['type'] . ':' . 'icone_creer_' . $infos['type'];
	}
	if (!isset($infos['texte_creer_associer'])) {
		$infos['texte_creer_associer'] = $infos['type'] . ':' . 'texte_creer_associer_' . $infos['type'];
	}
	if (!isset($infos['texte_ajouter'])) {
		// Ajouter un X
		$infos['texte_ajouter'] = $infos['type'] . ':' . 'texte_ajouter_' . $infos['type'];
	}
	if (!isset($infos['texte_objets'])) {
		$infos['texte_objets'] = $infos['type'] . ':' . 'titre_' . $infos['table_objet'];
	}
	if (!isset($infos['texte_objet'])) {
		$infos['texte_objet'] = $infos['type'] . ':' . 'titre_' . $infos['type'];
	}
	if (!isset($infos['texte_logo_objet'])) {
		// objet:titre_logo_objet "Logo de ce X"
		$infos['texte_logo_objet'] = $infos['type'] . ':' . 'titre_logo_' . $infos['type'];
	}
	if (!isset($infos['texte_langue_objet'])) {
		// objet:texte_langue_objet "Langue de ce X"
		$infos['texte_langue_objet'] = $infos['type'] . ':' . 'titre_langue_' . $infos['type'];
	}
	if (!isset($infos['texte_definir_comme_traduction_objet'])) {
		// "Ce X est une traduction du X numéro :"
		$infos['texte_definir_comme_traduction_objet'] = $infos['type'] . ':' . 'texte_definir_comme_traduction_' . $infos['type'];
	}

	// objet:info_aucun_objet
	if (!isset($infos['info_aucun_objet'])) {
		$infos['info_aucun_objet'] = $infos['type'] . ':' . 'info_aucun_' . $infos['type'];
	}
	// objet:info_1_objet
	if (!isset($infos['info_1_objet'])) {
		$infos['info_1_objet'] = $infos['type'] . ':' . 'info_1_' . $infos['type'];
	}
	// objet:info_nb_objets
	if (!isset($infos['info_nb_objets'])) {
		$infos['info_nb_objets'] = $infos['type'] . ':' . 'info_nb_' . $infos['table_objet'];
	}

	if (!isset($infos['champs_editables'])) {
		$infos['champs_editables'] = [];
	}
	if (!isset($infos['champs_versionnes'])) {
		$infos['champs_versionnes'] = [];
	}
	if (!isset($infos['rechercher_champs'])) {
		$infos['rechercher_champs'] = [];
	}
	if (!isset($infos['rechercher_jointures'])) {
		$infos['rechercher_jointures'] = [];
	}

	if (!isset($infos['modeles'])) {
		$infos['modeles'] = [$infos['type']];
	}

	return $infos;
}

/**
 * Renseigner les infos d'interface compilateur pour les tables objets
 * complete la declaration precedente
 *
 * titre
 * date
 * statut
 * tables_jointures
 *
 * @param $table_sql
 * @param $infos
 * @return array
 */
function renseigner_table_objet_interfaces($table_sql, &$infos) {
	if (!isset($infos['titre'])) {
		if (isset($infos['table_objet']) and isset($GLOBALS['table_titre'][$infos['table_objet']])) {
			$infos['titre'] = $GLOBALS['table_titre'][$infos['table_objet']];
		} else {
			$infos['titre'] = ((isset($infos['field']['titre'])) ? 'titre,' : "'' as titre,");
			$infos['titre'] .= ((isset($infos['field']['lang'])) ? 'lang' : "'' as lang");
		}
	}
	if (!isset($infos['date'])) {
		if (isset($infos['table_objet']) and isset($GLOBALS['table_date'][$infos['table_objet']])) {
			$infos['date'] = $GLOBALS['table_date'][$infos['table_objet']];
		} else {
			$infos['date'] = ((isset($infos['field']['date'])) ? 'date' : '');
		}
	}
	if (!isset($infos['statut'])) {
		$infos['statut'] = isset($GLOBALS['table_statut'][$table_sql]) ? $GLOBALS['table_statut'][$table_sql] : '';
	}
	if (!isset($infos['tables_jointures'])) {
		$infos['tables_jointures'] = [];
	}
	if (isset($GLOBALS['tables_jointures'][$table_sql])) {
		$infos['tables_jointures'] = array_merge($infos['tables_jointures'], $GLOBALS['tables_jointures'][$table_sql]);
	}

	return $infos;
}

/**
 * Retourne la liste des tables principales et leurs descriptions
 *
 * @api
 * @return array
 *     Liste et descriptions des tables principales
 **/
function lister_tables_principales() {
	static $done = false;
	if (!$done or !count($GLOBALS['tables_principales'])) {
		lister_tables_objets_sql();
		$done = true;
	}

	return $GLOBALS['tables_principales'];
}

/**
 * Retourne la liste des tables auxiliaires et leurs descriptions
 *
 * @api
 * @return array
 *     Liste et descriptions des tables auxiliaires
 **/
function lister_tables_auxiliaires() {
	static $done = false;
	if (!$done or !count($GLOBALS['tables_auxiliaires'])) {
		lister_tables_objets_sql();
		$done = true;
	}

	return $GLOBALS['tables_auxiliaires'];
}

/**
 * Recenser les surnoms de table_objet
 *
 * @return array
 */
function lister_tables_objets_surnoms() {
	static $surnoms = null;
	static $md5 = null;
	if (
		!$surnoms
		or $md5 != lister_tables_objets_sql('::md5')
	) {
		// passer dans un pipeline qui permet aux plugins de declarer leurs exceptions
		// pour compatibilite, car il faut dorenavent utiliser
		// declarer_table_objets_sql
		$surnoms = pipeline(
			'declarer_tables_objets_surnoms',
			[
				# pour les modeles
				# a enlever ?
				'doc' => 'documents',
				'img' => 'documents',
				'emb' => 'documents',
			]
		);
		$infos_tables = lister_tables_objets_sql();
		foreach ($infos_tables as $t => $infos) {
			// cas de base type=>table
			// et preg_replace(',^spip_|^id_|s$,',table)=>table
			if ($infos['table_objet']) { // securite, si la fonction est appelee trop tot, c'est vide
				// optimisations pour table_objet
				//$surnoms[$infos['type']] = $infos['table_objet'];
				$surnoms[preg_replace(',^spip_|^id_|s$,', '', $infos['table_objet'])] = $infos['table_objet'];
				$surnoms[preg_replace(',^spip_|^id_|s$,', '', $infos['type'])] = $infos['table_objet'];
				if (is_array($infos['table_objet_surnoms']) and count($infos['table_objet_surnoms'])) {
					foreach ($infos['table_objet_surnoms'] as $surnom) {
						$surnoms[$surnom] = $infos['table_objet'];
					}
				}
			}
		}
		$md5 = lister_tables_objets_sql('::md5');
	}

	return $surnoms;
}

/**
 * Recenser les surnoms de table_objet
 *
 * @return array
 */
function lister_types_surnoms() {
	static $surnoms = null;
	static $md5 = null;
	if (
		!$surnoms
		or $md5 != lister_tables_objets_sql('::md5')
	) {
		// passer dans un pipeline qui permet aux plugins de declarer leurs exceptions
		// pour compatibilite, car il faut dorenavent utiliser
		// declarer_table_objets_sql
		$surnoms = pipeline('declarer_type_surnoms', ['racine-site' => 'site']);
		$infos_tables = lister_tables_objets_sql();
		foreach ($infos_tables as $t => $infos) {
			if ($infos['type']) { // securite, si la fonction est appelee trop tot, c'est vide
				// optimisations pour objet_type
				//$surnoms[$infos['type']] = $infos['type'];
				$surnoms[preg_replace(',^spip_|^id_|s$,', '', $infos['table_objet'])] = $infos['type'];
				$surnoms[preg_replace(',^spip_|^id_|s$,', '', $infos['type'])] = $infos['type'];
				// surnoms declares
				if (is_array($infos['type_surnoms']) and count($infos['type_surnoms'])) {
					foreach ($infos['type_surnoms'] as $surnom) {
						$surnoms[$surnom] = $infos['type'];
					}
				}
			}
		}
		$md5 = lister_tables_objets_sql('::md5');
	}

	return $surnoms;
}

/**
 * Retourne la liste des tables SQL qui concernent SPIP
 *
 * Cette liste n'est calculée qu'une fois par serveur pour l'ensemble du hit
 *
 * @param string $serveur
 *     Nom du fichier de connexion à la base de données
 * @return array
 *     Couples (nom de la table SQL => même nom, sans 'spip_' devant)
 **/
function lister_tables_spip($serveur = '') {
	static $tables = [];
	if (!isset($tables[$serveur])) {
		$tables[$serveur] = [];
		if (!function_exists('sql_alltable')) {
			include_spip('base/abstract_sql');
		}
		$ts = sql_alltable(null, $serveur); // toutes les tables "spip_" (ou prefixe perso)
		$connexion = $GLOBALS['connexions'][$serveur ? $serveur : 0];
		$spip = $connexion['prefixe'] . '_';
		foreach ($ts as $t) {
			$t = substr($t, strlen($spip));
			$tables[$serveur]["spip_$t"] = $t;
		}
	}

	return $tables[$serveur];
}


/**
 * Retourne la liste des tables SQL, Spip ou autres
 *
 * Cette liste n'est calculée qu'une fois par serveur pour l'ensemble du hit
 *
 * @param string $serveur
 *     Nom du fichier de connexion à la base de données
 * @return array
 *     Couples (nom de la table SQL => même nom)
 **/
function lister_toutes_tables($serveur) {
	static $tables = [];
	if (!isset($tables[$serveur])) {
		$tables[$serveur] = [];
		if (!function_exists('sql_alltable')) {
			include_spip('base/abstract_sql');
		}
		$ts = sql_alltable('%', $serveur); // toutes les tables
		foreach ($ts as $t) {
			$tables[$serveur][$t] = $t;
		}
	}
	return $tables[$serveur];
}

/**
 * Retrouve le nom d'objet à partir de la table
 *
 * - spip_articles -> articles
 * - id_article    -> articles
 * - article       -> articles
 *
 * @api
 * @param string $type
 *     Nom de la table SQL (le plus souvent)
 *     Tolère un nom de clé primaire.
 * @param string $serveur
 *     Nom du connecteur
 * @return string
 *     Nom de l'objet
 **/
function table_objet($type, $serveur = '') {
	$surnoms = lister_tables_objets_surnoms();
	$type = preg_replace(',^spip_|^id_|s$,', '', $type);
	if (!$type) {
		return;
	}
	if (isset($surnoms[$type])) {
		return $surnoms[$type];
	}

	if ($serveur !== false) {
		$t = lister_tables_spip($serveur);
		$trouver_table = charger_fonction('trouver_table', 'base');
		$typetrim = rtrim($type, 's') . 's';
		if (
			(isset($t[$typetrim]) or in_array($typetrim, $t))
			and ($desc = $trouver_table(rtrim($type, 's') . 's', $serveur))
		) {
			return $desc['id_table'];
		} elseif (
			(isset($t[$type]) or in_array($type, $t))
			and ($desc = $trouver_table($type, $serveur))
		) {
			return $desc['id_table'];
		}

		spip_log('table_objet(' . $type . ') calculee sans verification');
		#spip_log(debug_backtrace(),'db');
	}

	return rtrim($type, 's') . 's'; # cas historique ne devant plus servir, sauf si $serveur=false
}

/**
 * Retrouve la table sql à partir de l'objet ou du type
 *
 * - articles    -> spip_articles
 * - article     -> spip_articles
 * - id_article  -> spip_articles
 *
 * @api
 * @param string $type
 *     Nom ou type de l'objet
 *     Tolère un nom de clé primaire.
 * @param string $serveur
 *     Nom du connecteur
 * @return string
 *     Nom de la table SQL
 **/
function table_objet_sql($type, $serveur = '') {

	$nom = table_objet($type, $serveur);
	if (!isset($GLOBALS['table_des_tables']['articles'])) {
		// eviter de multiples inclusions
		include_spip('public/interfaces');
	}
	if (isset($GLOBALS['table_des_tables'][$nom])) {
		$nom = $GLOBALS['table_des_tables'][$nom];
		$nom = "spip_$nom";
	} else {
		$infos_tables = lister_tables_objets_sql();
		if (isset($infos_tables["spip_$nom"])) {
			$nom = "spip_$nom";
		} elseif ($serveur !== false) {
			$t = lister_tables_spip($serveur);
			if (isset($t[$nom]) or in_array($nom, $t)) {
				$trouver_table = charger_fonction('trouver_table', 'base');
				if ($desc = $trouver_table($nom, $serveur)) {
					return $desc['table_sql'];
				}
			}
		}
	}

	return $nom;
}

/**
 * Retrouve la clé primaire à partir du nom d'objet ou de table
 *
 * - articles      -> id_article
 * - article       -> id_article
 * - spip_articles -> id_article
 *
 * @api
 * @param string $type
 *     Nom de la table SQL ou de l'objet
 * @param string $serveur
 *     Nom du connecteur
 * @return string
 *     Nom de la clé primaire
 **/
function id_table_objet($type, $serveur = '') {
	static $trouver_table = null;
	$type = objet_type($type, $serveur);
	if (!$type) {
		return;
	}
	$t = table_objet($type);
	if (!$trouver_table) {
		$trouver_table = charger_fonction('trouver_table', 'base');
	}

	$ts = lister_tables_spip($serveur);
	if (
		in_array($t, $ts)
		or in_array($t, lister_toutes_tables($serveur))
	) {
		$desc = $trouver_table($t, $serveur);
		if (isset($desc['key']['PRIMARY KEY'])) {
			return $desc['key']['PRIMARY KEY'];
		}
		if (!$desc or isset($desc['field']["id_$type"])) {
			return "id_$type";
		}
		// sinon renvoyer le premier champ de la table...
		$keys = array_keys($desc['field']);

		return array_shift($keys);
	}

	return "id_$type";
}

/**
 * Retrouve le type d'objet à partir du nom d'objet ou de table
 *
 * - articles      -> article
 * - spip_articles -> article
 * - id_article    -> article
 *
 * @api
 * @param string $table_objet
 *     Nom de l'objet ou de la table SQL
 * @param string $serveur
 *     Nom du connecteur
 * @return string|null
 *     Type de l'objet
 **/
function objet_type(string $table_objet, string $serveur = '') : ?string {
	if (!$table_objet) {
		return null;
	}
	$surnoms = lister_types_surnoms();

	// scenario de base
	// le type est decline a partir du nom de la table en enlevant le prefixe eventuel
	// et la marque du pluriel
	// on accepte id_xx en entree aussi
	$type = preg_replace(',^spip_|^id_|s$,', '', $table_objet);
	if (isset($surnoms[$type])) {
		return $surnoms[$type];
	}

	// securite : eliminer les caracteres non \w
	$type = preg_replace(',[^\w-],', '', $type);

	// si le type redonne bien la table c'est bon
	// oui si table_objet ressemblait deja a un type
	if (
		$type == $table_objet
		or (table_objet($type, $serveur) == $table_objet)
		or (table_objet_sql($type, $serveur) == $table_objet)
	) {
		return $type;
	}

	// si on ne veut pas chercher en base
	if ($serveur === false) {
		return $type;
	}

	// sinon on passe par la cle primaire id_xx pour trouver le type
	// car le s a la fin est incertain
	// notamment en cas de pluriel derogatoire
	// id_jeu/spip_jeux id_journal/spip_journaux qui necessitent tout deux
	// une declaration jeu => jeux, journal => journaux
	// dans le pipeline declarer_tables_objets_surnoms
	$trouver_table = charger_fonction('trouver_table', 'base');
	$ts = lister_tables_spip($serveur);
	$desc = false;
	if (in_array($table_objet, $ts)) {
		$desc = $trouver_table($table_objet);
	}
	if (!$desc and in_array($table_objet = table_objet($type, $serveur), $ts)) {
		$desc = $trouver_table($table_objet, $serveur);
	}
	// si le type est declare : bingo !
	if ($desc and isset($desc['type'])) {
		return $desc['type'];
	}

	// on a fait ce qu'on a pu
	return $type;
}

/**
 * Determininer si un objet est publie ou non
 *
 * On se base pour cela sur sa declaration de statut
 * pour des cas particuliers non declarables, on permet de fournir une fonction
 * base_xxxx_test_si_publie qui sera appele par la fonction
 *
 * @param string $objet
 * @param int $id_objet
 * @param string $serveur
 * @return bool
 */
function objet_test_si_publie($objet, $id_objet, $serveur = '') {
	// voir si une fonction est definie pour faire le boulot
	// elle a la priorite dans ce cas
	if ($f = charger_fonction($objet . '_test_si_publie', 'base', true)) {
		return $f($objet, $id_objet, $serveur);
	}

	// sinon on se fie a la declaration de l'objet si presente
	$id_table = $table_objet = table_objet($objet);
	$id_table_objet = id_table_objet($objet, $serveur);
	$trouver_table = charger_fonction('trouver_table', 'base');
	if (
		$desc = $trouver_table($table_objet, $serveur)
		and isset($desc['statut'])
		and $desc['statut']
	) {
		$boucle = new Boucle();
		$boucle->show = $desc;
		$boucle->nom = 'objet_test_si_publie';
		$boucle->id_boucle = $id_table;
		$boucle->id_table = $id_table;
		$boucle->primary = $desc['key']['PRIMARY KEY'] ?? '';
		$boucle->sql_serveur = $serveur;
		$boucle->select[] = $id_table_objet;
		$boucle->from[$table_objet] = table_objet_sql($objet, $serveur);
		$boucle->where[] = $id_table . '.' . $id_table_objet . '=' . intval($id_objet);

		$boucle->descr['nom'] = 'objet_test_si_publie'; // eviter notice php
		$boucle->descr['sourcefile'] = 'internal';
		$boucle->descr['gram'] = 'html';

		include_spip('public/compiler');
		include_spip('public/composer');
		instituer_boucle($boucle, false, true);
		$res = calculer_select(
			$boucle->select,
			$boucle->from,
			$boucle->from_type,
			$boucle->where,
			$boucle->join,
			$boucle->group,
			$boucle->order,
			$boucle->limit,
			$boucle->having,
			$table_objet,
			$id_table,
			$serveur
		);
		if (sql_fetch($res)) {
			return true;
		}

		return false;
	}

	// si pas d'info statut ni de fonction : l'objet est publie
	return true;
}


/**
 * Cherche les contenus parent d'un contenu précis.
 *
 * comme :
 * ```
 * $tables['spip_auteurs']['parent']  = array(
 *     'type' => 'organisation',
 *     'champ' => 'id_organisation',
 *     'table' => 'spip_organisations_liens',
 *     'table_condition' => 'role="parent"',
 *     'source_champ' => 'id_objet',
 *     'champ_type' => 'objet'
 * );
 * ```
 *
 * La fonction retourne un tableau de parents, chacun de la forme
 * ['objet' => '...', 'id_objet' => X, 'table' => '...', 'champ' => '...']
 * Si la table utilisée pour trouver le parent est une table de liens (finissant par _liens),
 * le tableau contient en plus en entrée 'lien' qui contient les informations complètes du lien (rang, role...)
 *
 * @api
 * @param string $objet
 *     Type de l'objet dont on cherche les parent
 * @param int $id_objet
 *     Identifiant de l'objet dont on cherche les parent
 * @param bool $parent_direct_seulement
 *     ne considerer que les relations directes et non via table de liens
 * @return array
 *     Retourne un tableau décrivant les parents trouvés
 */
function objet_lister_parents($objet, $id_objet, $parent_direct_seulement = false) {
	$parents = [];

	// Si on trouve une ou des méthodes de parent
	if ($parent_methodes = objet_type_decrire_infos_parents($objet)) {
		// On identifie les informations sur l'objet source dont on cherche le parent.
		include_spip('base/abstract_sql');
		$table_objet = table_objet_sql($objet);
		$cle_objet = id_table_objet($objet);
		$id_objet = intval($id_objet);

		// On teste chacun méthode dans l'ordre, et dès qu'on a trouvé un parent on s'arrête
		foreach ($parent_methodes as $parent_methode) {
			// Champ identifiant le parent (id et éventuellement le type)
			// -- cette identification ne dépend pas du fait que le parent soit stocké dans une table de différente
			//    de celle de l'objet source
			$select = [];
			if (isset($parent_methode['champ'])) {
				$select[] = $parent_methode['champ'];
			}
			if (isset($parent_methode['champ_type'])) {
				$select[] = $parent_methode['champ_type'];
			}

			// Détermination de la table du parent et des conditions sur l'objet source et le parent.
			$condition_objet_invalide = false;
			$where = [];
			if (!isset($parent_methode['table'])) {
				// Le parent est stocké dans la même table que l'objet source :
				// -- toutes les conditions s'appliquent à la table source.
				$table = $table_objet;
				$where = ["$cle_objet = $id_objet"];
				// -- Condition supplémentaire sur la détection du parent
				if (isset($parent_methode['condition'])) {
					$where[] = $parent_methode['condition'];
				}
			} elseif (!$parent_direct_seulement) {
				// Le parent est stocké dans une table différente de l'objet source.
				// -- on vérifie d'emblée si il y a une condition sur l'objet source et si celle-ci est vérifiée
				//    Si non, on peut arrêter le traitement.
				if (isset($parent_methode['condition'])) {
					$where = [
						"$cle_objet = $id_objet",
						$parent_methode['condition']
					];
					if (!sql_countsel($table_objet, $where)) {
						$condition_objet_invalide = true;
					}
				}

				// Si pas de condition sur l'objet source ou que la condition est vérifiée, on peut construire
				// la requête sur la table qui accueille le parent.
				if (!$condition_objet_invalide) {
					$table = $parent_methode['table'];
					// On construit les conditions en fonction de l'identification de l'objet source
					$where = [];
					// -- si le champ_source de l'id n'est pas précisé c'est qu'il est déjà connu et donc que c'est
					//    le même que celui de l'objet source.
					$where[] = isset($parent_methode['source_champ'])
						? "{$parent_methode['source_champ']} = $id_objet"
						: "${cle_objet} = $id_objet";
					if (isset($parent_methode['source_champ_type'])) {
						$where[] = "{$parent_methode['source_champ_type']} = " . sql_quote($objet);
					}
					// -- Condition supplémentaire sur la détection du parent
					if (isset($parent_methode['table_condition'])) {
						$where[] = $parent_methode['table_condition'];
					}
				}
			}

			// On lance la requête de récupération du parent
			$is_table_lien = (strpos($table, '_liens') !== false and substr($table, -6) === '_liens');
			if (
				!$condition_objet_invalide
				and $where
				and ($lignes = sql_allfetsel($is_table_lien ? '*' : $select, $table, $where))
			) {
				foreach ($lignes as $ligne) {
					// Si le type est fixe
					if (isset($parent_methode['type'])) {
						$parent = [
							'objet' 	=> $parent_methode['type'],
							'id_objet'	=> intval($ligne[$parent_methode['champ']]),
							'champ' 	=> $parent_methode['champ'],
							'table'    => $table,
						];
					}
					elseif (isset($parent_methode['champ_type'])) {
						$parent = [
							'objet' 	 => $ligne[$parent_methode['champ_type']],
							'id_objet' 	 => intval($ligne[$parent_methode['champ']]),
							'champ' 	 => $parent_methode['champ'],
							'champ_type' => $parent_methode['champ_type'],
							'table'    => $table,
						];
					}
					if ($is_table_lien) {
						$parent['lien'] = $ligne;
					}
					$parents[] = $parent;
				}
			}
		}
	}

	// On passe par un pipeline avant de retourner
	$parents = pipeline(
		'objet_lister_parents',
		[
			'args' => [
				'objet' => $objet,
				'id_objet' => $id_objet,
			],
			'data' => $parents,
		]
	);

	return $parents;
}

/**
 * Fonction helper qui permet de récupérer une liste simplifiée des parents, regroupés par objet
 * [ 'objet1' => [ids...], 'objet2' => [ids...] ]
 *
 * @param $objet
 * @param $id_objet
 * @return array
 */
function objet_lister_parents_par_type($objet, $id_objet) {
	$parents = objet_lister_parents($objet, $id_objet);

	$parents_par_type = [];
	foreach ($parents as $parent) {
		if (!isset($parents_par_type[$parent['objet']])) {
			$parents_par_type[$parent['objet']] = [];
		}
		$parents_par_type[$parent['objet']][] = $parent['id_objet'];
	}

	return $parents_par_type;
}


/**
 * Cherche tous les contenus enfants d'un contenu précis
 *
 * comme :
 * ```
 * $tables['spip_auteurs']['parent']  = array(
 *     'type' => 'organisation',
 *     'champ' => 'id_organisation',
 *     'table' => 'spip_organisations_liens',
 *     'table_condition' => 'role="parent"',
 *     'source_champ' => 'id_objet',
 *     'champ_type' => 'objet'
 * );
 * ```
 *
 * La fonction retourne un tableau des enfants, chacun de la forme
 * ['objet' => '...', 'id_objet' => X, 'table' => '...', 'champ' => '...']
 * Si la table utilisée pour trouver l'eenfant est une table de liens (finissant par _liens),
 * le tableau contient en plus en entrée 'lien' qui contient les informations complètes du lien (rang, role...)
 *
 * @api
 * @param $objet
 *     Type de l'objet dont on cherche les enfants
 * @param $id_objet
 *     Identifiant de l'objet dont on cherche les enfants
 * @return array
 *     Retourne un tableau de tableaux, avec comme clés les types des objets, et dans chacun un tableau des identifiants trouvés
 */
function objet_lister_enfants($objet, $id_objet) {
	$enfants = [];

	// Si on trouve des types d'enfants et leurs méthodes
	if ($enfants_methodes = objet_type_decrire_infos_enfants($objet)) {
		include_spip('base/abstract_sql');
		$id_objet = intval($id_objet);

		// On parcourt tous les types d'enfants trouvés
		foreach ($enfants_methodes as $objet_enfant => $_methode_parent) {
			// On construit les conditions d'identification du parent
			$where = [];
			// -- L'identifiant du parent
			if (isset($_methode_parent['champ'])) {
				$where[] = $_methode_parent['champ'] . ' = ' . $id_objet;
			}
			// -- Si le parent est variable
			if (isset($_methode_parent['champ_type'])) {
				$where[] = $_methode_parent['champ_type'] . ' = ' . sql_quote($objet);
			}

			// On détermine la table, le champ id des enfants et on complète éventuellement les conditions
			if (!isset($_methode_parent['table'])) {
				// Les enfants sont stockés dans la même table que l'objet parent :
				$table_enfant = table_objet_sql($objet_enfant);
				$cle_objet_enfant = id_table_objet($objet_enfant);

				// S'il y a une condition supplémentaire
				if (isset($_methode_parent['condition'])) {
					$where[] = $_methode_parent['condition'];
				}
			} else {
				// Les enfants sont stockés dans une table différente de l'objet parent.
				$table_enfant = $_methode_parent['table'];
				$cle_objet_enfant = isset($_methode_parent['source_champ'])
					? $_methode_parent['source_champ']
					: id_table_objet($objet_enfant);

				// S'il y a une condition supplémentaire
				if (isset($_methode_parent['table_condition'])) {
					$where[] = $_methode_parent['table_condition'];
				}
			}

			// On lance la requête
			$is_table_lien = (strpos($table_enfant, '_liens') !== false and substr($table_enfant, -6) === '_liens');
			if ($rows = sql_allfetsel($is_table_lien ? '*' : $cle_objet_enfant, $table_enfant, $where)) {
				$enfant = [
					'objet' => $objet_enfant,
					'id_objet' => 0,
					'table' => $table_enfant
				];
				if (isset($_methode_parent['champ'])) {
					$enfant['champ'] = $_methode_parent['champ'];
				}
				if (isset($_methode_parent['champ_type'])) {
					$enfant['champ_type'] = $_methode_parent['champ_type'];
				}
				foreach ($rows as $row) {
					$enfant['id_objet'] = intval($row[$cle_objet_enfant]);
					if ($is_table_lien) {
						$enfant['lien'] = $row;
					}
					$enfants[] = $enfant;
				}
			}
		}
	}

	// On passe par un pipeline avant de retourner
	$enfants = pipeline(
		'objet_lister_enfants',
		[
			'args' => [
				'objet' => $objet,
				'id_objet' => $id_objet,
			],
			'data' => $enfants,
		]
	);

	return $enfants;
}

/**
 * Fonction helper qui permet de récupérer une liste simplifiée des enfants, regroupés par objet
 * [ 'objet1' => [ids...], 'objet2' => [ids...] ]
 *
 * @param $objet
 * @param $id_objet
 * @return array
 */
function objet_lister_enfants_par_type($objet, $id_objet) {
	$enfants = objet_lister_enfants($objet, $id_objet);

	$enfants_par_type = [];
	foreach ($enfants as $enfant) {
		if (!isset($enfants_par_type[$enfant['objet']])) {
			$enfants_par_type[$enfant['objet']] = [];
		}
		$enfants_par_type[$enfant['objet']][] = $enfant['id_objet'];
	}

	return $enfants_par_type;
}

/**
 * Donne les informations de parenté directe d'un type d'objet si on en trouve
 *
 * @param $objet
 *     Type de l'objet dont on cherche les informations de parent
 * @return array|false
 *     Retourne un tableau de tableau contenant les informations de type et de champ pour trouver le parent ou false sinon
 */
function objet_type_decrire_infos_parents($objet) {
	static $parents = [];

	// Si on ne l'a pas encore cherché pour cet objet
	if (!isset($parents[$objet])) {
		$parents[$objet] = false;
		$table = table_objet_sql($objet);

		// Si on trouve bien la description de cet objet
		if ($infos = lister_tables_objets_sql($table)) {
			if (isset($infos['parent']) and is_array($infos['parent'])) {
				// S'il y a une description explicite de parent, c'est prioritaire
				// -- on traite les cas où il y a une ou plusieurs description mais on renvoie toujours un tableau
				//    de description
				if (!isset($infos['parent'][0])) {
					$parents[$objet] = [$infos['parent']];
				} else {
					$parents[$objet] = $infos['parent'];
				}
			} elseif (isset($infos['field']['id_rubrique'])) {
				// Sinon on cherche des cas courants connus magiquement, à commencer par id_rubrique
				$parents[$objet] = [['type' => 'rubrique', 'champ' => 'id_rubrique']];
			} elseif (isset($infos['field']['id_parent'])) {
				// Sinon on cherche un champ id_parent, ce qui signifie que l'objet est parent de lui-même
				$parents[$objet] = [['type' => $objet, 'champ' => 'id_parent']];
			}
		}
	}

	return $parents[$objet];
}

/**
 * Donne les informations des enfants directs d'un type d'objet si on en trouve
 *
 * @param $objet
 *     Type de l'objet dont on cherche les informations des enfants
 * @return array
 *     Retourne un tableau de tableaux contenant chacun les informations d'un type d'enfant
 */
function objet_type_decrire_infos_enfants($objet) {
	static $enfants = [];

	// Si on a déjà fait la recherche pour ce type d'objet
	if (!isset($enfants[$objet])) {
		$enfants[$objet] = [];
		$tables = lister_tables_objets_sql();

		// On parcourt toutes les tables d'objet, et on cherche si chacune peut être enfant
		foreach ($tables as $table => $infos) {
			$objet_enfant = objet_type($table);

			// On ne va pas refaire les tests des différents cas, on réutilise
			if ($parent_methodes = objet_type_decrire_infos_parents($objet_enfant)) {
				// On parcourt les différents cas possible, si certains peuvent concerner l'objet demandé
				foreach ($parent_methodes as $parent_methode) {
					// Si la méthode qu'on teste n'exclut pas le parent demandé
					if (!isset($parent_methode['exclus']) or !in_array($objet, $parent_methode['exclus'])) {
						// Si le type du parent est fixe et directement l'objet demandé
						if (isset($parent_methode['type']) and isset($parent_methode['champ']) and $parent_methode['type'] == $objet) {
							$enfants[$objet][$objet_enfant] = $parent_methode;
						}
						// Si le type est variable, alors l'objet demandé peut forcément être parent
						elseif (isset($parent_methode['champ_type']) and isset($parent_methode['champ'])) {
							$enfants[$objet][$objet_enfant] = $parent_methode;
						}
					}
				}
			}
		}
	}

	return $enfants[$objet];
}
