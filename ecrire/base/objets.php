<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

function array_set_merge(&$table,$index,$valeur){
	if (!isset($table[$index]))
		$table[$index] = $valeur;
	else
		$table[$index] = array_merge($table[$index],$valeur);
}

/**
 * Lister les infos de toutes les tables sql declarees
 * si un argument est fourni, on ne renvoie que les infos de cette table
 * elle est auto-declaree si inconnue jusqu'alors.
 *
 * @param string $table_sql
 *   table_sql demandee explicitement
 * @param array $desc
 *   description connue de la table sql demandee
 * @return array|bool
 */
function lister_tables_objets_sql($table_sql=null, $desc=array()){
	static $deja_la = false;
	static $infos_tables = null;
	// prealablement recuperer les tables_principales
	if (is_null($infos_tables)){
		// pas de reentrance (cas base/serial)
		if ($deja_la) return array();
		$deja_la = true;
		# recuperer les interfaces (table_titre, table_date)
		include_spip('public/interfaces');
		# recuperer les tables_principales si besoin
		include_spip('base/serial');
		# recuperer les tables_auxiliaires si besoin
		include_spip('base/auxiliaires');
		// recuperer les declarations explicites ancienne mode
		// qui servent a completer declarer_tables_objets_sql
		base_serial($GLOBALS['tables_principales']);
		base_auxiliaires($GLOBALS['tables_auxiliaires']);
		$infos_tables = pipeline('declarer_tables_objets_sql',array(
			'spip_articles'=> array(
				'page'=>'article',
				'texte_retour' => 'icone_retour_article',
				'texte_modifier' => 'icone_modifier_article',
				'texte_creer' => 'icone_ecrire_article',
				'texte_objets' => 'public:articles',
				'texte_objet' => 'public:article',
				'texte_signale_edition' => 'texte_travail_article',
				'info_aucun_objet'=> 'info_aucun_article',
				'info_1_objet' => 'info_1_article',
				'info_nb_objets' => 'info_nb_articles',
				'texte_logo_objet' => 'logo_article',
				'titre' => 'titre, lang',
				'date' => 'date',
				'champs_editables' => array('surtitre', 'titre', 'soustitre', 'descriptif','nom_site', 'url_site', 'chapo', 'texte', 'ps','virtuel'),
				'champs_versionnes' => array('id_rubrique', 'surtitre', 'titre', 'soustitre', 'jointure_auteurs', 'descriptif', 'nom_site', 'url_site', 'chapo', 'texte', 'ps'),
				'field' => array(
					"id_article"	=> "bigint(21) NOT NULL",
					"surtitre"	=> "text DEFAULT '' NOT NULL",
					"titre"	=> "text DEFAULT '' NOT NULL",
					"soustitre"	=> "text DEFAULT '' NOT NULL",
					"id_rubrique"	=> "bigint(21) DEFAULT '0' NOT NULL",
					"descriptif"	=> "text DEFAULT '' NOT NULL",
					"chapo"	=> "mediumtext DEFAULT '' NOT NULL",
					"texte"	=> "longtext DEFAULT '' NOT NULL",
					"ps"	=> "mediumtext DEFAULT '' NOT NULL",
					"date"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
					"statut"	=> "varchar(10) DEFAULT '0' NOT NULL",
					"id_secteur"	=> "bigint(21) DEFAULT '0' NOT NULL",
					"maj"	=> "TIMESTAMP",
					"export"	=> "VARCHAR(10) DEFAULT 'oui'",
					"date_redac"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
					"visites"	=> "integer DEFAULT '0' NOT NULL",
					"referers"	=> "integer DEFAULT '0' NOT NULL",
					"popularite"	=> "DOUBLE DEFAULT '0' NOT NULL",
					"accepter_forum"	=> "CHAR(3) DEFAULT '' NOT NULL",
					"date_modif"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
					"lang"		=> "VARCHAR(10) DEFAULT '' NOT NULL",
					"langue_choisie"	=> "VARCHAR(3) DEFAULT 'non'",
					"id_trad"	=> "bigint(21) DEFAULT '0' NOT NULL",
					"nom_site"	=> "tinytext DEFAULT '' NOT NULL",
					"url_site"	=> "VARCHAR(255) DEFAULT '' NOT NULL",
					"virtuel"	=> "VARCHAR(255) DEFAULT '' NOT NULL",
				),
				'key' => array(
					"PRIMARY KEY"		=> "id_article",
					"KEY id_rubrique"	=> "id_rubrique",
					"KEY id_secteur"	=> "id_secteur",
					"KEY id_trad"		=> "id_trad",
					"KEY lang"		=> "lang",
					"KEY statut"		=> "statut, date",
				),
				'join' => array(
					"id_article"=>"id_article",
					"id_rubrique"=>"id_rubrique"
				),
				'rechercher_champs' => array(
					'surtitre' => 5, 'titre' => 8, 'soustitre' => 5, 'chapo' => 3,
					'texte' => 1, 'ps' => 1, 'nom_site' => 1, 'url_site' => 1,
					'descriptif' => 4
				),
				'rechercher_jointures' => array(
					'auteur' => array('nom' => 10),
				),
				'statut'=> array(
					array(
						'champ' => 'statut',
						'publie' => 'publie',
						'previsu' => 'publie,prop,prepa',
						'post_date' => 'date',
						'exception' => 'statut'
					)
				),
				'statut_titres' => array(
					'prepa'=>'info_article_redaction',
					'prop'=>'info_article_propose',
					'publie'=>'info_article_publie',
					'refuse'=>'info_article_refuse',
					'poubelle'=>'info_article_supprime'
				),
				'statut_textes_instituer' => 	array(
					'prepa' => 'texte_statut_en_cours_redaction',
					'prop' => 'texte_statut_propose_evaluation',
					'publie' => 'texte_statut_publie',
					'refuse' => 'texte_statut_refuse',
					'poubelle' => 'texte_statut_poubelle',
				),
				'tables_jointures' => array('id_auteur' => 'auteurs_liens'),
			),
			'spip_auteurs' => array(
				'page'=>'auteur',
				'texte_retour' => 'icone_retour',
				'texte_modifier' => 'admin_modifier_auteur',
				'texte_objets' => 'icone_auteurs',
				'texte_objet' => 'public:auteur',
				'info_aucun_objet'=> 'info_aucun_auteur',
				'info_1_objet' => 'info_1_auteur',
				'info_nb_objets' => 'info_nb_auteurs',
				'texte_logo_objet' => 'logo_auteur',
				'titre' => "nom AS titre, '' AS lang",
				'date' => 'date',
				'champs_editables' => array('nom','email','bio','nom_site','url_site','imessage','pgp'),
				'champs_versionnes' => array('nom', 'bio', 'email', 'nom_site', 'url_site', 'login'),
				'field' => array(
					"id_auteur"	=> "bigint(21) NOT NULL",
					"nom"	=> "text DEFAULT '' NOT NULL",
					"bio"	=> "text DEFAULT '' NOT NULL",
					"email"	=> "tinytext DEFAULT '' NOT NULL",
					"nom_site"	=> "tinytext DEFAULT '' NOT NULL",
					"url_site"	=> "text DEFAULT '' NOT NULL",
					"login"	=> "VARCHAR(255) BINARY",
					"pass"	=> "tinytext DEFAULT '' NOT NULL",
					"low_sec"	=> "tinytext DEFAULT '' NOT NULL",
					"statut"	=> "varchar(255)  DEFAULT '0' NOT NULL",
					"webmestre"	=> "varchar(3)  DEFAULT 'non' NOT NULL",
					"maj"	=> "TIMESTAMP",
					"pgp"	=> "TEXT DEFAULT '' NOT NULL",
					"htpass"	=> "tinytext DEFAULT '' NOT NULL",
					"en_ligne"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
					"alea_actuel"	=> "tinytext",
					"alea_futur"	=> "tinytext",
					"prefs"	=> "tinytext",
					"cookie_oubli"	=> "tinytext",
					"source"	=> "VARCHAR(10) DEFAULT 'spip' NOT NULL",
					"lang"	=> "VARCHAR(10) DEFAULT '' NOT NULL"
				),
				'key' => array(
					"PRIMARY KEY"	=> "id_auteur",
					"KEY login"	=> "login",
					"KEY statut"	=> "statut",
					"KEY en_ligne"	=> "en_ligne",
				),
				'join' => array(
					"id_auteur"=>"id_auteur",
					"login"=>"login"
				),
				'rechercher_champs' => array(
					'nom' => 5, 'bio' => 1, 'email' => 1, 'nom_site' => 1, 'url_site' => 1, 'login' => 1
				),
				// 2 conditions pour les auteurs : statut!=poubelle,
				// et avoir des articles publies
				'statut'=> array(
					array(
						'champ' => 'statut',
						'publie' => '!5poubelle',
						'previsu' => '!5poubelle',
						'exception' => 'statut'
					),
					array(
						'champ' => array(
							array('spip_auteurs_liens', 'id_auteur'),
							array(
								'spip_articles',
								array('id_objet','id_article','objet','article')
							),
							'statut'
						),
						'publie' => 'publie',
						'previsu' => '!',
						'post_date' => 'date',
						'exception' => array('statut','lien','tout')
					),
				),
				'statut_images' => array(
					'auteur-6forum-16.png',
					'0minirezo'=>'auteur-0minirezo-16.png',
					'1comite'=>'auteur-1comite-16.png',
					'6forum'=>'auteur-6forum-16.png',
					'5poubelle'=>'auteur-5poubelle-16.png',
					'nouveau'=>''
				),
				'statut_titres' => array(
					'titre_image_visiteur',
					'0minirezo'=>'titre_image_administrateur',
					'1comite'=>'titre_image_redacteur_02',
					'6forum'=>'titre_image_visiteur',
					'5poubelle'=>'titre_image_auteur_supprime',
				),
				'tables_jointures' => array('auteurs_liens'),
			),
			'spip_rubriques' => array(
				'page'=>'rubrique',
				'url_voir' => 'rubrique',
				'url_edit' => 'rubrique_edit',
				'texte_retour' => 'icone_retour',
				'texte_objets' => 'public:rubriques',
				'texte_objet' => 'public:rubrique',
				'texte_modifier' => 'icone_modifier_rubrique',
				'texte_creer' => 'icone_creer_rubrique',
				'info_aucun_objet'=> 'info_aucun_rubrique',
				'info_1_objet' => 'info_1_rubrique',
				'info_nb_objets' => 'info_nb_rubriques',
				'texte_logo_objet' => 'logo_rubrique',
				'titre'=>'titre, lang',
				'date' => 'date',
				'champs_editables' => array('titre', 'texte', 'descriptif', 'extra'),
				'champs_versionnes' => array('titre', 'descriptif', 'texte'),
				'field' => array(
					"id_rubrique"	=> "bigint(21) NOT NULL",
					"id_parent"	=> "bigint(21) DEFAULT '0' NOT NULL",
					"titre"	=> "text DEFAULT '' NOT NULL",
					"descriptif"	=> "text DEFAULT '' NOT NULL",
					"texte"	=> "longtext DEFAULT '' NOT NULL",
					"id_secteur"	=> "bigint(21) DEFAULT '0' NOT NULL",
					"maj"	=> "TIMESTAMP",
					"export"	=> "VARCHAR(10) DEFAULT 'oui'",
					"id_import"	=> "bigint DEFAULT '0'",
					"statut"	=> "varchar(10) DEFAULT '0' NOT NULL",
					"date"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
					"lang"	=> "VARCHAR(10) DEFAULT '' NOT NULL",
					"langue_choisie"	=> "VARCHAR(3) DEFAULT 'non'",
					"statut_tmp"	=> "varchar(10) DEFAULT '0' NOT NULL",
						"date_tmp"	=> "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL"
				),
				'key' => array(
					"PRIMARY KEY"	=> "id_rubrique",
					"KEY lang"	=> "lang",
					"KEY id_parent"	=> "id_parent",
				),
				'rechercher_champs' => array(
					'titre' => 8, 'descriptif' => 5, 'texte' => 1
				),
				'statut' => array(
					array(
						'champ' => 'statut',
						'publie' => 'publie',
						'previsu' => '!',
						'exception' => array('statut','tout')
					),
				),
				'tables_jointures' => array('id_auteur' => 'auteurs_liens'),
			)
		));
		// completer les informations manquantes ou implicites
		$all = array();
		foreach($infos_tables as $t=>$infos) {
			// les cles numeriques servent a declarer
			// les proprietes applicables a tous les objets
			// on les mets de cote
			if (is_numeric($t)) {
				$all = array_merge_recursive($all,$infos);
				unset($infos_tables[$t]);
			}
			else
				$infos_tables[$t] = renseigner_table_objet_sql($t,$infos);
		}
		// repercuter les proprietes generales communes a tous les objets
		foreach($infos_tables as $t=>$infos){
			$infos_tables[$t] = array_merge_recursive($infos_tables[$t],$all);
		}

		// completer les tables principales et auxiliaires
		// avec celles declarees uniquement dans declarer_table_objets_sql
		// pour assurer la compat en transition
		foreach($infos_tables as $table=>$infos) {
			$principale_ou_auxiliaire = ($infos['principale']?'tables_principales':'tables_auxiliaires');
			if (!isset($GLOBALS[$principale_ou_auxiliaire][$table])){
				// l'ajouter au tableau
				$GLOBALS[$principale_ou_auxiliaire][$table] = array();
				if (isset($infos['field']) AND isset($infos['key']))
					$GLOBALS[$principale_ou_auxiliaire][$table] = &$infos_tables[$table];
				else {
					// lire sa definition en base
					$trouver_table = charger_fonction('trouver_table','base');
					$GLOBALS[$principale_ou_auxiliaire][$table] = $trouver_table($table);
				}
			}
		}
		$deja_la = false;
	}
	if ($table_sql AND !isset($infos_tables[$table_sql])){
	#	$infos_tables[$table_sql] = renseigner_table_objet_sql($table_sql,$desc);
		return renseigner_table_objet_sql($table_sql,$desc);
	}
	if ($table_sql)
		return isset($infos_tables[$table_sql])?$infos_tables[$table_sql]:array();

	return $infos_tables;
}


/**
 * Auto remplissage des informations non explicites
 * sur un objet d'une table sql
 *
 * table_objet
 * table_objet_surnoms
 * type
 * type_surnoms
 * url_voir
 * url_edit
 * icone_objet
 *
 * texte_retour
 * texte_modifier
 * texte_creer
 * texte_objets
 * texte_objet
 *
 * info_aucun_objet
 * info_1_objet
 * info_nb_objets
 *
 * principale
 * champs_contenu : utlise pour generer l'affichage par defaut du contenu
 * editable
 * champs_editables : utilise pour prendre en compte le post lors de l'edition
 * 
 * titre
 * date
 * champs_versionnes
 *
 * statut
 * statut_images
 * statut_titres
 * statut_textes_instituer
 * 
 * les infos non renseignees sont auto deduites par conventions
 * ou laissees vides
 *
 * @param string $table_sql
 * @param array $infos
 * @return array
 */
function renseigner_table_objet_sql($table_sql,$infos){
	if (!isset($infos['type'])){
		// si on arrive de base/trouver_table, on a la cle primaire :
		// s'en servir pour extrapoler le type
		if (isset($desc['key']["PRIMARY KEY"])){
			$primary = $desc['key']["PRIMARY KEY"];
			$primary = explode(',',$primary);
			$primary = reset($primary);
			$infos['type'] = preg_replace(',^spip_|^id_|s$,', '', $primary);
		}
		else
			$infos['type'] = preg_replace(',^spip_|s$,', '', $table_sql);
	}
	if (!isset($infos['type_surnoms']))
		$infos['type_surnoms'] = array();

	if (!isset($infos['table_objet']))
		$infos['table_objet'] = preg_replace(',^spip_,', '', $table_sql);
	if (!isset($infos['table_objet_surnoms']))
		$infos['table_objet_surnoms'] = array();

	if (!isset($infos['principale']))
		$infos['principale'] = (isset($GLOBALS['tables_principales'][$table_sql])?'oui':false);

	// normaliser pour pouvoir tester en php $infos['principale']?
	// et dans une boucle {principale=oui}
	$infos['principale'] = (($infos['principale'] AND $infos['principale']!='non')?'oui':false);

	// declarer et normaliser pour pouvoir tester en php $infos['editable']?
	// et dans une boucle {editable=oui}
	if (!isset($infos['editable'])) $infos['editable'] = 'oui';
	$infos['editable'] = (($infos['editable'] AND $infos['editable']!='non')?'oui':false);

	// les urls publiques sont par defaut page=type pour les tables principales, et rien pour les autres
	// seules les exceptions sont donc a declarer
	if (!isset($infos['page']))
		$infos['page'] = ($infos['principale']?$infos['type']:'');

	if (!isset($infos['url_voir']))
		$infos['url_voir'] = $infos['type'];
	if (!isset($infos['url_edit']))
		$infos['url_edit'] = $infos['url_voir'].($infos['editable']?"_edit":'');
	if (!isset($infos['icone_objet']))
		$infos['icone_objet'] = $infos['type'];

	// chaines de langue
	// par defaut : objet:icone_xxx_objet
	if (!isset($infos['texte_retour']))
		$infos['texte_retour'] = 'icone_retour';
	if (!isset($infos['texte_modifier']))
		$infos['texte_modifier'] = $infos['type'].':'.'icone_modifier_'.$infos['type'];
	if (!isset($infos['texte_creer']))
		$infos['texte_creer'] = $infos['type'].':'.'icone_creer_'.$infos['type'];
	if (!isset($infos['texte_objets']))
		$infos['texte_objets'] = $infos['type'].':'.'titre_'.$infos['table_objet'];
	if (!isset($infos['texte_objet']))
		$infos['texte_objet'] = $infos['type'].':'.'titre_'.$infos['type'];
	if (!isset($infos['texte_logo_objet']))  // objet:titre_logo_objet "Logo de ce X"
		$infos['texte_logo_objet'] = $infos['type'].':'.'titre_logo_'.$infos['type'];
		
	// objet:info_aucun_objet
	if (!isset($infos['info_aucun_objet']))
		$infos['info_aucun_objet'] = $infos['type'].':'.'info_aucun_'.$infos['type'];
	// objet:info_1_objet
	if (!isset($infos['info_1_objet']))
		$infos['info_1_objet'] = $infos['type'].':'.'info_1_'.$infos['type'];
	// objet:info_nb_objets
	if (!isset($infos['info_nb_objets']))
		$infos['info_nb_objets'] = $infos['type'].':'.'info_nb_'.$infos['table_objet'];


	if (!isset($infos['titre']))
		$infos['titre'] = isset($GLOBALS['table_titre'][$infos['table_objet']]) ? $GLOBALS['table_titre'][$infos['table_objet']] : '';
	if (!isset($infos['date']))
		$infos['date'] = isset($GLOBALS['table_date'][$infos['table_objet']]) ? $GLOBALS['table_date'][$infos['table_objet']] : '';
	if (!isset($infos['statut']))
		$infos['statut'] = isset($GLOBALS['table_statut'][$table_sql]) ? $GLOBALS['table_statut'][$table_sql] : '';
	if (!isset($infos['tables_jointures']))
		$infos['tables_jointures'] = array();
	if (isset($GLOBALS['tables_jointures'][$table_sql]))
		$infos['tables_jointures'] = array_merge($infos['tables_jointures'],$GLOBALS['tables_jointures'][$table_sql]);
	

	if (!isset($infos['champs_versionnes']))
		$infos['champs_versionnes'] = array();
	if (!isset($infos['rechercher_champs']))
		$infos['rechercher_champs'] = array();
	if (!isset($infos['rechercher_jointures']))
		$infos['rechercher_jointures'] = array();

	return $infos;
}

function lister_tables_principales(){
	if (!count($GLOBALS['tables_principales'])){
		lister_tables_objets_sql();
	}
	return $GLOBALS['tables_principales'];
}

function lister_tables_auxiliaires(){
	if (!count($GLOBALS['tables_auxiliaires'])){
		lister_tables_objets_sql();
	}
	return $GLOBALS['tables_auxiliaires'];
}

/**
 * Recenser les surnoms de table_objet
 * @return array
 */
function lister_tables_objets_surnoms(){
	static $surnoms = null;
	if (!$surnoms){
		// passer dans un pipeline qui permet aux plugins de declarer leurs exceptions
		// pour compatibilite, car il faut dorenavent utiliser
		// declarer_table_objets_sql
		$surnoms = pipeline('declarer_tables_objets_surnoms',
			array(
				# pour les modeles
				# a enlever ?
				'doc' => 'documents',
				'img' => 'documents',
				'emb' => 'documents',
			));
		$infos_tables = lister_tables_objets_sql();
		foreach($infos_tables as $t=>$infos){
			// cas de base type=>table
			// et preg_replace(',^spip_|^id_|s$,',table)=>table
			$surnoms[$infos['type']] = $infos['table_objet'];
			$surnoms[preg_replace(',^spip_|^id_|s$,', '', $infos['table_objet'])] = $infos['table_objet'];
			if (is_array($infos['table_objet_surnoms']) AND count($infos['table_objet_surnoms']))
				foreach($infos['table_objet_surnoms'] as $surnom)
					$surnoms[$surnom] = $infos['table_objet'];
		}
	}
	return $surnoms;
}

/**
 * Recenser les surnoms de table_objet
 * @return array
 */
function lister_types_surnoms(){
	static $surnoms = null;
	if (!$surnoms){
		// passer dans un pipeline qui permet aux plugins de declarer leurs exceptions
		// pour compatibilite, car il faut dorenavent utiliser
		// declarer_table_objets_sql
		$surnoms = pipeline('declarer_type_surnoms', array('racine-site'=>'site'));
		$infos_tables = lister_tables_objets_sql();
		foreach($infos_tables as $t=>$infos){
			if (is_array($infos['type_surnoms']) AND count($infos['type_surnoms']))
				foreach($infos['type_surnoms'] as $surnom)
					$surnoms[$surnom] = $infos['type'];
		}
	}
	return $surnoms;
}

// Nommage bizarre des tables d'objets
// http://doc.spip.org/@table_objet
function table_objet($type,$serveur='') {
	$surnoms = lister_tables_objets_surnoms();
	$type = preg_replace(',^spip_|^id_|s$,', '', $type);
	if (!$type) return;
	if (isset($surnoms[$type]))
		return $surnoms[$type];

	if ($serveur!==false){
		$trouver_table = charger_fonction('trouver_table', 'base');
		if ($desc = $trouver_table(rtrim($type,'s')."s",$serveur))
			return $desc['id_table'];
		elseif ($desc = $trouver_table($type,$serveur))
			return $desc['id_table'];

		spip_log( 'table_objet('.$type.') calculee sans verification', _LOG_AVERTISSEMENT);
	}

	return rtrim($type,'s')."s"; # cas historique ne devant plus servir, sauf si $serveur=false
}

// http://doc.spip.org/@table_objet_sql
function table_objet_sql($type,$serveur='') {
	global $table_des_tables;
	$nom = table_objet($type, $serveur);
	include_spip('public/interfaces');
	if (isset($table_des_tables[$nom])) {
		$nom = $table_des_tables[$nom];
		$nom = "spip_$nom";
	}
	else {
		$infos_tables = lister_tables_objets_sql();
		if (isset($infos_tables["spip_$nom"]))
			$nom = "spip_$nom";
	}

	return $nom ;
}

// http://doc.spip.org/@id_table_objet
function id_table_objet($type,$serveur='') {
	$type = objet_type($type,$serveur);
	if (!$type) return;
	$t = table_objet($type);
	$trouver_table = charger_fonction('trouver_table', 'base');
	$desc = $trouver_table($t,$serveur);
	if (isset($desc['key']['PRIMARY KEY']))
		return $desc['key']['PRIMARY KEY'];
	if (!$desc OR isset($desc['field']["id_$type"]))
		return "id_$type";
	// sinon renvoyer le premier champ de la table...
	return array_shift(array_keys($desc['field']));
}

// http://doc.spip.org/@objet_type
function objet_type($table_objet, $serveur=''){
	if (!$table_objet) return;
	$surnoms = lister_types_surnoms();

	// scenario de base
	// le type est decline a partir du nom de la table en enlevant le prefixe eventuel
	// et la marque du pluriel
	// on accepte id_xx en entree aussi
	$type = preg_replace(',^spip_|^id_|s$,', '', $table_objet);
	if (isset($surnoms[$type]))
		return $surnoms[$type];

	// securite : eliminer les caracteres non \w
	$type = preg_replace(',[^\w-],','',$type);

	// si le type redonne bien la table c'est bon
	// oui si table_objet ressemblait deja a un type
	if ( $type==$table_objet
		OR (table_objet($type,$serveur)==$table_objet)
	  OR (table_objet_sql($type,$serveur)==$table_objet))
	  return $type;

	// si on ne veut pas chercher en base
	if ($serveur===false)
		return $type;

	// sinon on passe par la cle primaire id_xx pour trouver le type
	// car le s a la fin est incertain
	// notamment en cas de pluriel derogatoire
	// id_jeu/spip_jeux id_journal/spip_journaux qui necessitent tout deux
	// une declaration jeu => jeux, journal => journaux
	// dans le pipeline declarer_tables_objets_surnoms
	$trouver_table = charger_fonction('trouver_table', 'base');
	if ($desc = $trouver_table($table_objet)
		 OR $desc = $trouver_table(table_objet($type),$serveur)){
		// si le type est declare : bingo !
		if (isset($desc['type']))
			return $desc['type'];
	}

	// on a fait ce qu'on a pu
	return $type;
}

/**
 * Determininer si un objet est publie ou non
 * on se base pour cela sur sa declaration de statut
 * pour des cas particuliers non declarables, on permet de fournir une fonction
 * base_xxxx_test_si_publie qui sera appele par la fonction
 *
 * @param string $objet
 * @param int $id_objet
 * @param string $serveur
 * @return bool
 */
function objet_test_si_publie($objet,$id_objet, $serveur=''){
	$id_table = $table_objet = table_objet($objet);
	$id_table_objet = id_table_objet($objet, $serveur);
	$trouver_table = charger_fonction('trouver_table', 'base');
	if ($desc = $trouver_table($table_objet, $serveur)
		AND isset($desc['statut'])
	  AND $desc['statut']){
		$boucle = new Boucle();
		$boucle->show = $desc;
		$boucle->nom = 'objet_test_si_publie';
		$boucle->id_boucle = $id_table;
		$boucle->id_table = $id_table;
		$boucle->serveur = $serveur;
		$boucle->select[] = $id_table_objet;
		$boucle->from[$table_objet] = table_objet_sql($objet, $serveur);
		$boucle->where[] = $id_table.".".$id_table_objet.'='.intval($id_objet);

		include_spip('public/compiler');
		include_spip('public/composer');
		instituer_boucle($boucle, false);
		$res = calculer_select($boucle->select,$boucle->from,$boucle->from_type,$boucle->where,$boucle->join,$boucle->group,$boucle->order,$boucle->limit,$boucle->having,$table_objet,$id_table,$serveur);
		if (sql_fetch($res))
			return true;
		return false;
	}
	// voir si une fonction est definie pour faire le boulot
	if ($f = charger_fonction($objet."_test_si_publie","base",true))
		return $f($objet,$id_objet, $serveur);

	// si pas d'info statut ni de fonction : l'objet est publie
	return true;
}
