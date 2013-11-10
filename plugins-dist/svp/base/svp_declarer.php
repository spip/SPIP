<?php
/**
 * Déclarations relatives à la base de données
 * 
 * @plugin SVP pour SPIP
 * @license GPL
 * @package SPIP\SVP\Pipelines
**/

/**
 * Déclarer les objets éditoriaux de SVP
 *
 * - Dépot  : table spip_depots
 * - Plugin : table spip_plugins
 * - Paquet : table spip_paquets
 *
 * @pipeline declarer_tables_objets_sql
 * @param array $tables
 *     Description des tables
 * @return array
 *     Description complétée des tables
 */
function svp_declarer_tables_objets_sql($tables) {
	include_spip('inc/config');
	
	// Table des depots
	$tables['spip_depots'] = array(
		// Base de donnees
		'table_objet'			=> 'depots',
		'type'					=> 'depot',
		'field'					=> array(
			"id_depot"		=> "bigint(21) NOT NULL",
			"titre"			=> "text DEFAULT '' NOT NULL",
			"descriptif"	=> "text DEFAULT '' NOT NULL",
			"type" 			=> "varchar(10) DEFAULT '' NOT NULL",
			"url_serveur"	=> "varchar(255) DEFAULT '' NOT NULL", // url du serveur svn ou git
			"url_brouteur"	=> "varchar(255) DEFAULT '' NOT NULL", // url de l'interface de gestion du repository (trac, redmine...)
			"url_archives"	=> "varchar(255) DEFAULT '' NOT NULL", // url de base des zips
			"url_commits"	=> "varchar(255) DEFAULT '' NOT NULL", // url du flux rss des commits du serveur svn ou git
			"xml_paquets"	=> "varchar(255) DEFAULT '' NOT NULL", // chemin complet du fichier xml du depot
			"sha_paquets"	=> "varchar(40) DEFAULT '' NOT NULL",
			"nbr_paquets" 	=> "integer DEFAULT 0 NOT NULL",
			"nbr_plugins" 	=> "integer DEFAULT 0 NOT NULL",
			"nbr_autres" 	=> "integer DEFAULT 0 NOT NULL", // autres contributions, non plugin
			"maj"			=> "timestamp"),
		'key'					=> array(
			"PRIMARY KEY"	=> "id_depot"),
		'tables_jointures'		=> array('id_plugin' => 'depots_plugins'),
		'principale'			=> 'oui',

		// Titre, date et gestion du statut
		'titre'					=> "titre, '' AS lang",
		
		// Edition, affichage et recherche
		'page'					=> 'depot',
		'url_voir'				=> 'depot',
		'url_edit'				=> 'depot_edit',
		'editable'				=> lire_config('svp/depot_editable', 'non'),
		'champs_editables'		=> array('titre', 'descriptif'),
		'icone_objet'			=> 'depot',
		
		// Textes standard
		'texte_retour' 			=> 'icone_retour',
		'texte_modifier' 		=> 'svp:bouton_modifier_depot',
		'texte_creer' 			=> '',
		'texte_creer_associer' 	=> '',
		'texte_signale_edition' => '',
		'texte_objet' 			=> 'svp:titre_depot',
		'texte_objets' 			=> 'svp:titre_depots',
		'info_aucun_objet'		=> 'svp:info_aucun_depot',
		'info_1_objet' 			=> 'svp:info_1_depot',
		'info_nb_objets' 		=> 'svp:info_nb_depots',
		'texte_logo_objet' 		=> 'svp:titre_logo_depot',
	);

	// Table des plugins
	$tables['spip_plugins'] = array(
		// Base de donnees
		'table_objet'			=> 'plugins',
		'type'					=> 'plugin',
		'field'					=> array(
			"id_plugin"		=> "bigint(21) NOT NULL",
			"prefixe"		=> "varchar(30) DEFAULT '' NOT NULL",
			"nom"			=> "text DEFAULT '' NOT NULL",
			"slogan"		=> "text DEFAULT '' NOT NULL",
			"categorie"		=> "varchar(100) DEFAULT '' NOT NULL",
			"tags"			=> "text DEFAULT '' NOT NULL",
			"vmax"			=> "varchar(24) DEFAULT '' NOT NULL", // version la plus elevee des paquets du plugin
			"date_crea"		=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL", // la plus ancienne des paquets du plugin
			"date_modif"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL", // la plus recente des paquets du plugin
			"compatibilite_spip"	=> "varchar(24) DEFAULT '' NOT NULL", // union des intervalles des paquets du plugin
			"branches_spip"	=> "varchar(255) DEFAULT '' NOT NULL"), // union des branches spip supportees par les paquets du plugin
		'key'					=> array(
			"PRIMARY KEY"	=> "id_plugin",
			"KEY prefixe"		=> "prefixe"),
		'tables_jointures'		=> array('id_depot' => 'depots_plugins'),
		'principale'			=> 'oui',

		// Titre, date et gestion du statut
		'titre'					=> "prefixe AS titre, '' AS lang",
		
		// Edition, affichage et recherche
		'page'					=> 'plugin',
		'url_voir'				=> 'plugin',
		'editable'				=> 'non',
		'champs_editables'		=> array(),
		'rechercher_champs'		=> array('prefixe' => 8, 'nom' => 8, 'slogan' => 4),
		'rechercher_jointures'	=> array('paquet' => array('auteur' => 8, 'description' => 2)),
		'icone_objet'			=> 'plugin',
		
		// Textes standard
		'texte_retour' 			=> 'icone_retour',
		'texte_modifier' 		=> '',
		'texte_creer' 			=> '',
		'texte_creer_associer' 	=> '',
		'texte_signale_edition' => '',
		'texte_objet' 			=> 'svp:titre_plugin',
		'texte_objets' 			=> 'svp:titre_plugins',
		'info_aucun_objet'		=> 'svp:info_aucun_plugin',
		'info_1_objet' 			=> 'svp:info_1_plugin',
		'info_nb_objets' 		=> 'svp:info_nb_plugins',
		'texte_logo_objet' 		=> 'svp:titre_logo_plugin',
	);

	$tables['spip_paquets'] = array(
		// Base de donnees
		'table_objet'			=> 'paquets',
		'type'					=> 'paquet',
		'field'					=> array(
			"id_paquet"		=> "bigint(21) NOT NULL",
			"id_plugin"		=> "bigint(21) NOT NULL",
			"prefixe"		=> "varchar(30) DEFAULT '' NOT NULL",
			"logo"			=> "varchar(255) DEFAULT '' NOT NULL", // chemin du logo depuis la racine du plugin
			"version"		=> "varchar(24) DEFAULT '' NOT NULL",
			"version_base"	=> "varchar(24) DEFAULT '' NOT NULL",
			"compatibilite_spip"	=> "varchar(24) DEFAULT '' NOT NULL",
			"branches_spip"	=> "varchar(255) DEFAULT '' NOT NULL", // branches spip supportees (cf meta)
			"description"	=> "text DEFAULT '' NOT NULL",
			"auteur"		=> "text DEFAULT '' NOT NULL",
			"credit"		=> "text DEFAULT '' NOT NULL",
			"licence"		=> "text DEFAULT '' NOT NULL",
			"copyright"		=> "text DEFAULT '' NOT NULL",
			"lien_doc"		=> "text DEFAULT '' NOT NULL", // lien vers la documentation
			"lien_demo"		=> "text DEFAULT '' NOT NULL", // lien vers le site de demo
			"lien_dev"		=> "text DEFAULT '' NOT NULL", // lien vers le site de dev
			"etat"			=> "varchar(16) DEFAULT '' NOT NULL",
			"etatnum"		=> "int(1) DEFAULT 0 NOT NULL", // 0 aucune indication - 1 exp - 2 dev - 3 test - 4 stable
			"dependances"	=> "text DEFAULT '' NOT NULL",
			"date_crea"		=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
			"date_modif"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
			"id_depot"		=> "bigint(21) DEFAULT 0 NOT NULL", // 0 pour un paquet local
			"nom_archive"	=> "VARCHAR(255) DEFAULT '' NOT NULL", // nom du zip du paquet, depuis l'adresse de la zone
			"nbo_archive"	=> "integer DEFAULT 0 NOT NULL", // taille de l'archive en octets
			"maj_archive"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL", // date de mise a jour de l'archive
			"src_archive"	=> "VARCHAR(255) DEFAULT '' NOT NULL", // source de l'archive sur le depot
			"traductions"	=> "text DEFAULT '' NOT NULL", // tableau serialise par module des langues traduites et de leurs traducteurs
			"actif"			=> "varchar(3) DEFAULT 'non' NOT NULL", // est actif ? oui / non
			"installe"		=> "varchar(3) DEFAULT 'non' NOT NULL", // est desinstallable ? oui / non
			"recent"		=> "int(2) DEFAULT 0 NOT NULL", // a ete utilise recemment ? > 0 : oui
			"maj_version"	=> "VARCHAR(255) DEFAULT '' NOT NULL", // version superieure existante (mise a jour possible)
			"superieur"		=> "varchar(3) DEFAULT 'non' NOT NULL", // superieur : version plus recente disponible (distant) d'un plugin (actif?) existant
			"obsolete"		=> "varchar(3) DEFAULT 'non' NOT NULL", // obsolete : version plus ancienne (locale) disponible d'un plugin local existant
			"attente"		=> "varchar(3) DEFAULT 'non' NOT NULL", // attente : plugin semi actif (il etait actif, mais il lui manque maintenant une dependance : il reste coche actif jusqu'a resolution ou desactivation manuelle)
			"constante"		=> "VARCHAR(30) DEFAULT '' NOT NULL", // nom de la constante _DIR_(PLUGINS|EXTENSIONS|PLUGINS_SUPP)
			"signature"		=> "VARCHAR(32) DEFAULT '' NOT NULL"), // hash MD5 d'un paquet
		'key'					=> array(
			"PRIMARY KEY"	=> "id_paquet",
			"KEY id_plugin"	=> "id_plugin"),
		'join' 					=> array(
			"id_paquet"		=> "id_paquet",
			"id_plugin"		=> "id_plugin"),
		'principale'			=> 'oui',

		// Titre, date et gestion du statut
		'titre'					=> "nom_archive AS titre, '' AS lang",
		
		// Edition, affichage et recherche
		'page'					=> 'paquet',
		'url_voir'				=> '',
		'editable'				=> 'non',
		'champs_editables'		=> array(),
		'rechercher_champs'		=> array(),
		'rechercher_jointures'	=> array(),
		'icone_objet'			=> 'paquet',
		
		// Textes standard
		'texte_retour' 			=> '',
		'texte_modifier' 		=> '',
		'texte_creer' 			=> '',
		'texte_creer_associer' 	=> '',
		'texte_signale_edition' => '',
		'texte_objet' 			=> 'svp:titre_paquet',
		'texte_objets' 			=> 'svp:titre_paquets',
		'info_aucun_objet'		=> 'svp:info_aucun_paquet',
		'info_1_objet' 			=> 'svp:info_1_paquet',
		'info_nb_objets' 		=> 'svp:info_nb_paquets',
		'texte_logo_objet' 		=> '',
	);

	return $tables;
}


/**
 * Déclarer les tables de liaisons de SVP
 *
 * Déclare la table spip_depots_plugins
 *
 * @pipeline declarer_tables_auxiliaires
 * @param array $tables_auxiliaires
 *     Description des tables auxiliaires
 * @return array
 *     Description complétée des tables auxiliaires
 */
function svp_declarer_tables_auxiliaires($tables_auxiliaires) {
	// Tables de liens entre plugins et depots : spip_depots_plugins
	$spip_depots_plugins = array(
		"id_depot"	=> "bigint(21) NOT NULL",
		"id_plugin"	=> "bigint(21) NOT NULL"
	);

	$spip_depots_plugins_key = array(
		"PRIMARY KEY" 	=> "id_depot, id_plugin"
	);

	$tables_auxiliaires['spip_depots_plugins'] = 
		array('field' => &$spip_depots_plugins, 'key' => &$spip_depots_plugins_key);

	return $tables_auxiliaires;
}

/**
 * Déclare les alias de boucle et traitements automatiques de certaines balises
 *
 * @pipeline declarer_tables_interfaces
 * @param array $interface
 *     Déclarations d'interface pour le compilateur
 * @return array
 *     Déclarations d'interface pour le compilateur
 */
function svp_declarer_tables_interfaces($interface) {
	// Les tables : permet d'appeler une boucle avec le *type* de la table uniquement
 	$interface['table_des_tables']['depots'] = 'depots';
	$interface['table_des_tables']['plugins'] = 'plugins';
	$interface['table_des_tables']['paquets'] = 'paquets';
	$interface['table_des_tables']['depots_plugins'] = 'depots_plugins';

	// Les traitements
	// - table spip_plugins
	$interface['table_des_traitements']['SLOGAN']['plugins'] = _TRAITEMENT_RACCOURCIS;
	$interface['table_des_traitements']['VMAX']['plugins'] = 'denormaliser_version(%s)';
	// - table spip_paquets
	$interface['table_des_traitements']['DESCRIPTION']['paquets'] = _TRAITEMENT_RACCOURCIS;
	$interface['table_des_traitements']['VERSION']['paquets'] = 'denormaliser_version(%s)';
	$interface['table_des_traitements']['MAJ_VERSION']['paquets'] = 'denormaliser_version(%s)';

	return $interface;
}

?>
