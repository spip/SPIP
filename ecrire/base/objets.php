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
 * @return array
 */
function lister_table_objets_sql($table_sql=null, $desc=array()){
	static $infos_tables = null;
	if (is_null($infos_tables)){
		$infos_tables = pipeline('declarer_table_objets_sql',array(
			'spip_articles'=> array(
				'texte_retour' => 'icone_retour_article',
				'texte_modifier' => 'icone_modifier_article',
				'info_aucun_objet'=> 'info_aucun_article',
				'info_1_objet' => 'info_1_article',
				'info_nb_objets' => 'info_nb_articles',
				'champs_versionnes' => array('id_rubrique', 'surtitre', 'titre', 'soustitre', 'j_mots', 'descriptif', 'nom_site', 'url_site', 'chapo', 'texte', 'ps')
			),
			'spip_auteurs' => array(
				'texte_retour' => 'icone_retour',
				'texte_modifier' => 'admin_modifier_auteur',
				'info_aucun_objet'=> 'info_aucun_auteur',
				'info_1_objet' => 'info_1_auteur',
				'info_nb_objets' => 'info_nb_auteurs',
				'champs_versionnes' => array('nom', 'bio', 'email', 'nom_site', 'url_site', 'login')
			),
			'spip_rubriques' => array(
				'url_voir' => 'naviguer',
				'url_edit' => 'rubriques_edit',
				'texte_retour' => 'icone_retour',
				'texte_modifier' => 'icone_modifier_rubrique',
				'info_aucun_objet'=> 'info_aucun_rubrique',
				'info_1_objet' => 'info_1_rubrique',
				'info_nb_objets' => 'info_nb_rubriques',
				'champs_versionnes' => array('titre', 'descriptif', 'texte')
			)
		));
		// completer les informations manquantes ou implicites
		foreach($infos_tables as $t=>$infos)
			$infos_tables[$t] = renseigner_table_objet_sql($t,$infos);
	}
	if ($table_sql AND !isset($infos_tables[$table_sql])){
		$infos_tables[$table_sql] = renseigner_table_objet_sql($table_sql,$desc);
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
 *
 * info_aucun_objet
 * info_1_objet
 * info_nb_objets
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
	if (!isset($infos['texte_modifier']))
		$infos['texte_modifier'] = $infos['type'].':'.'icone_modifier_'.$infos['type'];

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
	if (!isset($infos['champs_versionnes']))
		$infos['champs_versionnes'] = array();

	return $infos;
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
		$infos_tables = lister_table_objets_sql();
		foreach($infos_tables as $t=>$infos){
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
		$infos_tables = lister_table_objets_sql();
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
	return @$desc['key']["PRIMARY KEY"];
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