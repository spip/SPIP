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
		if ($deja_la) return;
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
				'texte_objets' => 'public:articles',
				'texte_objet' => 'public:article',
				'texte_signale_edition' => 'texte_travail_article',
				'info_aucun_objet'=> 'info_aucun_article',
				'info_1_objet' => 'info_1_article',
				'info_nb_objets' => 'info_nb_articles',
				'titre' => 'titre, lang',
				'date' => 'date',
				'champs_versionnes' => array('id_rubrique', 'surtitre', 'titre', 'soustitre', 'j_mots', 'descriptif', 'nom_site', 'url_site', 'chapo', 'texte', 'ps'),
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
						'previsu' => 'publie,prop',
						'post_date' => 'date',
						'exception' => 'statut'
					)
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
				'titre' => "nom AS titre, '' AS lang",
				'date' => 'date',
				'champs_versionnes' => array('nom', 'bio', 'email', 'nom_site', 'url_site', 'login'),
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
				'tables_jointures' => array('auteurs_liens'),
			),
			'spip_rubriques' => array(
				'page'=>'rubrique',
				'url_voir' => 'naviguer',
				'url_edit' => 'rubriques_edit',
				'texte_retour' => 'icone_retour',
				'texte_objets' => 'public:rubriques',
				'texte_objet' => 'public:rubrique',
				'texte_modifier' => 'icone_modifier_rubrique',
				'info_aucun_objet'=> 'info_aucun_rubrique',
				'info_1_objet' => 'info_1_rubrique',
				'info_nb_objets' => 'info_nb_rubriques',
				'titre'=>'titre, lang',
				'date' => 'date',
				'champs_versionnes' => array('titre', 'descriptif', 'texte'),
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
		foreach($infos_tables as $t=>$infos)
			$infos_tables[$t] = renseigner_table_objet_sql($t,$infos);

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
		return $infos_tables[$table_sql];

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
 * texte_objets
 *
 * info_aucun_objet
 * info_1_objet
 * info_nb_objets
 *
 * principale
 * editable
 * 
 * titre
 * date
 * champs_versionnes
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
		$infos['url_edit'] = $infos['url_voir']."_edit";
	if (!isset($infos['icone_objet']))
		$infos['icone_objet'] = $infos['type'];

	// chaines de langue
	// par defaut : objet:icone_xxx_objet
	if (!isset($infos['texte_retour']))
		$infos['texte_retour'] = $infos['type'].':'.'icone_retour_'.$infos['type'];
	if (!isset($infos['texte_modifier']))
		$infos['texte_modifier'] = $infos['type'].':'.'icone_modifier_'.$infos['type'];
	if (!isset($infos['texte_objets']))
		$infos['texte_objets'] = $infos['type'].':'.'titre_'.$infos['table_objet'];
	if (!isset($infos['texte_objet']))
		$infos['texte_objet'] = $infos['type'].':'.'titre_'.$infos['type'];

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

	$trouver_table = charger_fonction('trouver_table', 'base');
	if ($desc = $trouver_table(rtrim($type,'s')."s",$serveur))
		return $desc['id_table'];
	elseif ($desc = $trouver_table($type,$serveur))
		return $desc['id_table'];

	spip_log( 'table_objet('.$type.') calculee sans verification', _LOG_AVERTISSEMENT);
	return rtrim($type,'s')."s"; # cas historique ne devant plus servir
}

// http://doc.spip.org/@table_objet_sql
function table_objet_sql($type,$serveur='') {
	global $table_des_tables;
	$nom = table_objet($type, $serveur);
	include_spip('public/interfaces');
	if (isset($table_des_tables[$nom])) {
		$t = $table_des_tables[$nom];
		$nom = 'spip_' . $t;
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
	return isset($desc['key']["PRIMARY KEY"])?$desc['key']["PRIMARY KEY"]:"id_$type";
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
		OR (table_objet($type)==$table_objet)
	  OR (table_objet_sql($type)==$table_objet))
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
