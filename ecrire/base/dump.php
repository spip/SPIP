<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 *
 * Fonctions de base pour la sauvegarde
 * Boite a outil commune, sans prejuger de la methode de sauvegarde
 *
 */

if (!defined('_ECRIRE_INC_VERSION')) return;

define('_VERSION_ARCHIVE', '1.3');

include_spip('base/serial');
include_spip('base/auxiliaires');
include_spip('public/interfaces'); // pour table_jointures

// NB: Ce fichier peut ajouter des tables (old-style)
// donc il faut l'inclure "en globals"
if ($f = find_in_path('mes_fonctions.php')) {
	global $dossier_squelettes;
	@include_once (_ROOT_CWD . $f);
}

if (@is_readable(_CACHE_PLUGINS_FCT)){
	// chargement optimise precompile
	include_once(_CACHE_PLUGINS_FCT);
}

function base_dump_meta_name($rub){
	return $meta = "status_dump_{$rub}_"  . abs($GLOBALS['visiteur_session']['id_auteur']);
}
function base_dump_dir($meta){
	include_spip('inc/documents');
	// determine upload va aussi initialiser l'index "restreint"
	$maindir = determine_upload();
	if (!$GLOBALS['visiteur_session']['restreint'])
		$maindir = _DIR_DUMP;
	$dir = sous_repertoire($maindir, $meta);
	return $dir;
}

/**
 * Lister toutes les tables d'un serveur
 * en excluant eventuellement une liste fournie
 *
 * @param string $serveur
 * @param array $tables
 * @param array $exclude
 * @param bool $affiche_vrai_prefixe
 * @return array
 */
function base_lister_toutes_tables($serveur='', $tables=array(), $exclude = array(),$affiche_vrai_prefixe=false) {
	spip_connect($serveur);
	$connexion = $GLOBALS['connexions'][$serveur ? $serveur : 0];
	$prefixe = $connexion['prefixe'];

	$p = '/^' . $prefixe . '/';
	$res = $tables;
	foreach(sql_alltable(null,$serveur) as $t) {
		if (preg_match($p, $t)) {
			$t1 = preg_replace($p, 'spip', $t);
			if (!in_array($t1, $tables) AND !in_array($t1, $exclude))
				$res[]= ($affiche_vrai_prefixe?$t:$t1);
		}
	}
	sort($res);
	return $res;
}


/**
 * Fabrique la liste a cocher des tables a traiter (copie, delete, sauvegarde)
 *
 * @param string $name
 * @param array $tables
 * @param array $exclude
 * @param array|null $post
 * @param string $serveur
 * @return array
 */
function base_saisie_tables($name, $tables, $exclude = array(), $post=null, $serveur='') {
	include_spip('inc/filtres');
	$res = array();
	foreach ($tables as $k => $t) {
		// par defaut tout est coche sauf les tables dans $exclude
		if (is_null($post))
			$check = (in_array($t,$exclude)?false:true);
		// mais si on a poste une selection, la reprendre
		else
			$check = in_array($t,$post);

		$res[$k] = "<input type='checkbox' value='$t' name='$name"
			. "[]' id='$name$k'"
			. ($check ? " checked='checked'" : '')
			. "/>\n"
			. "<label for='$name$k'>".$t."</label>"
			. " ("
			. sinon(singulier_ou_pluriel(sql_countsel($t,'','','',$serveur), 'dump:une_donnee', 'dump:nb_donnees'),_T('dump:aucune_donnee'))
	  		. ")";
	}
	return $res;
}


/**
 * Lister les tables non exportables par defaut
 * (liste completable par le pipeline lister_tables_noexport
 *
 * @staticvar array $EXPORT_tables_noexport
 * @return array
 */
function lister_tables_noexport(){
	// par defaut tout est exporte sauf les tables ci-dessous
	static $EXPORT_tables_noexport = null;
	if (!is_null($EXPORT_tables_noexport))
		return $EXPORT_tables_noexport;

	$EXPORT_tables_noexport= array(
		'spip_caches', // plugin invalideur
		'spip_resultats', // resultats de recherche ... c'est un cache !
		'spip_test', // c'est un test !
		#'spip_referers',
		#'spip_referers_articles',
		#'spip_visites',
		#'spip_visites_articles',
		#'spip_versions',
		#'spip_versions_fragments'
		);

	$EXPORT_tables_noexport = pipeline('lister_tables_noexport',$EXPORT_tables_noexport);
	return $EXPORT_tables_noexport;
}

/**
 * Lister les tables non importables par defaut
 * (liste completable par le pipeline lister_tables_noimport
 *
 * @staticvar array $IMPORT_tables_noimport
 * @return array
 */
function lister_tables_noimport(){
	static $IMPORT_tables_noimport=null;
	if (!is_null($IMPORT_tables_noimport))
		return $IMPORT_tables_noimport;

	$IMPORT_tables_noimport = array();
	// par defaut tout est importe sauf les tables ci-dessous
	// possibiliter de definir cela tables via la meta
	// compatibilite
	if (isset($GLOBALS['meta']['IMPORT_tables_noimport'])){
		$IMPORT_tables_noimport = unserialize($GLOBALS['meta']['IMPORT_tables_noimport']);
		if (!is_array($IMPORT_tables_noimport)){
			include_spip('inc/meta');
			effacer_meta('IMPORT_tables_noimport');
		}
	}
	$IMPORT_tables_noimport = pipeline('lister_tables_noimport',$IMPORT_tables_noimport);
	return $IMPORT_tables_noimport;
}


/**
 * Lister les tables a ne pas effacer
 * (liste completable par le pipeline lister_tables_noerase
 *
 * @staticvar array $IMPORT_tables_noerase
 * @return array
 */
function lister_tables_noerase(){
	static $IMPORT_tables_noerase=null;
	if (!is_null($IMPORT_tables_noerase))
		return $IMPORT_tables_noerase;

	$IMPORT_tables_noerase = array(
		'spip_meta',
		// par defaut on ne vide pas les stats, car elles ne figurent pas dans les dump
		// et le cas echeant, un bouton dans l'admin permet de les vider a la main...
		'spip_referers',
		'spip_referers_articles',
		'spip_visites',
		'spip_visites_articles'
	);
	$IMPORT_tables_noerase = pipeline('lister_tables_noerase',$IMPORT_tables_noerase);
	return $IMPORT_tables_noerase;
}


/**
 * construction de la liste des tables pour le dump :
 * toutes les tables principales
 * + toutes les tables auxiliaires hors relations
 * + les tables relations dont les deux tables liees sont dans la liste
 *
 * @param array $exclude_tables
 * @return array
 */
function base_liste_table_for_dump($exclude_tables = array()){
	$tables_for_dump = array();
	$tables_pointees = array();
	$tables = array();
	$tables_principales = $GLOBALS['tables_principales'];
	$tables_auxiliaires = $GLOBALS['tables_auxiliaires'];
	$tables_jointures = $GLOBALS['tables_jointures'];

	if (include_spip('base/objets')
		AND function_exists('lister_tables_objets_sql')){
		$tables = lister_tables_objets_sql();
		foreach($tables as $t=>$infos){
			if ($infos['principale'] AND !isset($tables_principales[$t]))
				$tables_principales[$t] = true;
			if (!$infos['principale'] AND !isset($tables_auxiliaires[$t]))
				$tables_auxiliaires[$t] = true;
			if (count($infos['tables_jointures']))
				$tables_jointures[$t] = array_merge(isset($tables_jointures[$t])?$tables_jointures[$t]:array(),$infos['tables_jointures']);
		}
	}

	// on construit un index des tables de liens
	// pour les ajouter SI les deux tables qu'ils connectent sont sauvegardees
	$tables_for_link = array();
	foreach($tables_jointures as $table => $liste_relations)
		if (is_array($liste_relations))
		{
			$nom = $table;
			if (!isset($tables_auxiliaires[$nom])&&!isset($tables_principales[$nom]))
				$nom = "spip_$table";
			if (isset($tables_auxiliaires[$nom])||isset($tables_principales[$nom])){
				foreach($liste_relations as $link_table){
					if (isset($tables_auxiliaires[$link_table])/*||isset($tables_principales[$link_table])*/){
						$tables_for_link[$link_table][] = $nom;
					}
					else if (isset($tables_auxiliaires["spip_$link_table"])/*||isset($tables_principales["spip_$link_table"])*/){
						$tables_for_link["spip_$link_table"][] = $nom;
					}
				}
			}
		}

	$liste_tables = array_merge(array_keys($tables_principales),array_keys($tables_auxiliaires),array_keys($tables));
	foreach($liste_tables as $table){
	  //		$name = preg_replace("{^spip_}","",$table);
	  if (		!isset($tables_pointees[$table])
	  		&&	!in_array($table,$exclude_tables)
	  		&&	!isset($tables_for_link[$table])){
			$tables_for_dump[] = $table;
			$tables_pointees[$table] = 1;
		}
	}
	foreach ($tables_for_link as $link_table =>$liste){
		$connecte = true;
		foreach($liste as $connect_table)
			if (!in_array($connect_table,$tables_for_dump))
				$connecte = false;
		if ($connecte)
			# on ajoute les liaisons en premier
			# si une restauration est interrompue,
			# cela se verra mieux si il manque des objets
			# que des liens
			array_unshift($tables_for_dump,$link_table);
	}
	return array($tables_for_dump, $tables_for_link);
}

/**
 * Vider les tables de la base de destination
 * pour la copie dans une base
 *
 * peut etre utilise pour l'import depuis xml,
 * ou la copie de base a base (mysql<->sqlite par exemple)
 *
 * @param array $tables
 * @param array $exclure_tables
 * @param string $serveur
 */
function base_vider_tables_destination_copie($tables, $exclure_tables = array(), $serveur=''){
	$trouver_table = charger_fonction('trouver_table', 'base');

	spip_log('Vider '.count($tables) . " tables sur serveur '$serveur' : " . join(', ', $tables),'base.'._LOG_INFO_IMPORTANTE);
	foreach($tables as $table){
		if (!in_array($table,$exclure_tables)){
			// sur le serveur principal, il ne faut pas supprimer l'auteur loge !
			if (($table!='spip_auteurs') OR $serveur!=''){
				// regarder si il y a au moins un champ impt='non'
				$desc = $trouver_table($table,$serveur);
				if (isset($desc['field']['impt'])){
					sql_delete($table, "impt='oui'", $serveur);
				}
				else{
					sql_delete($table, "", $serveur);
				}
			}
		}
	}

	// sur le serveur principal, il ne faut pas supprimer l'auteur loge !
	// Bidouille pour garder l'acces admin actuel pendant toute la restauration
	if ($serveur==''
	  AND in_array('spip_auteurs',$tables)
	  AND !in_array('spip_auteurs',$exclure_tables)) {
		base_conserver_copieur(true, $serveur);
		sql_delete("spip_auteurs", "id_auteur>0",$serveur);
	}
}

/**
 * Conserver le copieur si besoin
 * @param bool $move
 * @param string $serveur
 * @return void
 */
function base_conserver_copieur($move = true,$serveur=''){
	// s'asurer qu'on a pas deja fait la manip !
	if ($GLOBALS['visiteur_session']['id_auteur']>0 AND sql_countsel("spip_auteurs", "id_auteur>0")) {
		spip_log('Conserver copieur '.$GLOBALS['visiteur_statut']['id_auteur'] . " dans id_auteur=".$GLOBALS['visiteur_statut']['id_auteur']." pour le serveur '$serveur'",'dump.'._LOG_INFO_IMPORTANTE);
		sql_delete("spip_auteurs", "id_auteur<0",$serveur);
		if ($move){
			sql_updateq('spip_auteurs', array('id_auteur'=>-$GLOBALS['visiteur_session']['id_auteur']), "id_auteur=".intval($GLOBALS['visiteur_session']['id_auteur']),array(),$serveur);
		}
		else {
			$row = sql_fetsel('*','spip_auteurs','id_auteur='.$GLOBALS['visiteur_session']['id_auteur'],'','','','',$serveur);
			$row['id_auteur'] = -$GLOBALS['visiteur_session']['id_auteur'];
			sql_insertq('spip_auteurs',$row,array(),$serveur);
		}
	}
}

/**
 * Effacement de la bidouille ci-dessus
 * Toutefois si la table des auteurs ne contient plus qu'elle
 * c'est que la copie etait incomplete et on restaure le compte
 * pour garder la connection au site
 *
 * (mais il doit pas etre bien beau
 * et ca ne marche que si l'id_auteur est sur moins de 3 chiffres)
 *
 * @param string $serveur
 */
function base_detruire_copieur_si_besoin($serveur='')
{
	// rien a faire si ce n'est pas le serveur principal !
	if ($serveur=='') {
		if (sql_countsel("spip_auteurs", "id_auteur>0")) {
			spip_log("Detruire copieur id_auteur<0 pour le serveur '$serveur'",'dump.'._LOG_INFO_IMPORTANTE);
			sql_delete("spip_auteurs", "id_auteur<0", $serveur);
		}
		else {
			spip_log( "Restaurer copieur id_auteur<0 pour le serveur '$serveur' (aucun autre auteur en base)",'dump.'._LOG_INFO_IMPORTANTE);
			sql_update('spip_auteurs', array('id_auteur'=>'-id_auteur'), "id_auteur<0");
		}
	}
	else
		spip_log("Pas de destruction copieur sur serveur '$serveur'",'dump.'._LOG_INFO_IMPORTANTE);
}

/**
 * Preparer la table dans la base de destination :
 * la droper si elle existe (sauf si auteurs ou meta sur le serveur principal)
 * la creer si necessaire, ou ajouter simplement les champs manquants
 *
 * @param string $table
 * @param array $desc
 * @param string $serveur_dest
 * @param bool $init
 * @return array
 */
function base_preparer_table_dest($table, $desc, $serveur_dest, $init=false) {
	$upgrade = false;
	// si la table existe et qu'on est a l'init, la dropper
	if ($desc_dest=sql_showtable($table,true,$serveur_dest) AND $init) {
		if ($serveur_dest=='' AND in_array($table,array('spip_meta','spip_auteurs'))) {
			// ne pas dropper auteurs et meta sur le serveur principal
			// faire un simple upgrade a la place
			// pour ajouter les champs manquants
			$upgrade = true;
			// coherence avec le drop sur les autres tables
			base_vider_tables_destination_copie(array($table),array(),$serveur_dest);
			if ($table=='spip_meta'){
				// virer les version base qui vont venir avec l'import
				sql_delete($table, "nom like '%_base_version'",$serveur_dest);
				// hum casse la base si pas version_installee a l'import ...
				sql_delete($table, "nom='version_installee'",$serveur_dest);
			}
		}
		else {
			sql_drop_table($table, '', $serveur_dest);
			spip_log( "drop table '$table' sur serveur '$serveur_dest'",'dump.'._LOG_INFO_IMPORTANTE);
		}
		$desc_dest = false;
	}
	// si la table n'existe pas dans la destination, la creer a l'identique !
	if (!$desc_dest) {
		spip_log( "creation '$table' sur serveur '$serveur_dest'",'dump.'._LOG_INFO_IMPORTANTE);
		include_spip('base/create');
		creer_ou_upgrader_table($table, $desc, 'auto', $upgrade,$serveur_dest);
		$desc_dest = sql_showtable($table,true,$serveur_dest);
	}
	if (!$desc_dest){
		spip_log( "Erreur creation '$table' sur serveur '$serveur_dest'".var_export($desc,1),'dump.'._LOG_ERREUR);
	}

	return $desc_dest;
}

/**
 * Copier de base a base
 *
 * @param string $status_file
 *   nom avec chemin complet du fichier ou est stocke le status courant
 * @param array $tables
 *   liste des tables a copier
 * @param string $serveur_source
 * @param string $serveur_dest
 * @param array $options
 *   parametres optionnels sous forme de tableau :
 *   param string $callback_progression
 *     fonction a appeler pour afficher la progression, avec les arguments (compteur,total,table)
 *   param int $max_time
 *     limite de temps au dela de laquelle sortir de la fonction proprement (de la forme time()+15)
 *   param bool $drop_source
 *     vider les tables sources apres copie
 *   param array $no_erase_dest
 *     liste des tables a ne pas vider systematiquement (ne seront videes que si existent dans la base source)
 *   param array $where
 *     liste optionnelle de condition where de selection des donnees pour chaque table
 *   param string $racine_fonctions_dest
 *     racine utilisee pour charger_fonction() des operations elementaires sur la base de destination.
 *     Permet de deleguer vers une autre voie de communication.
 *     Par defaut on utilise 'base', ce qui route vers les fonctions de ce fichier. Concerne :
 *     - vider_tables_destination_copie
 *     - preparer_table_dest
 *     - detruire_copieur_si_besoin
 *     - inserer_copie
 *   param array $fonction_base_inserer
 *     fonction d'insertion en base. Par defaut "inserer_copie" qui fait un insertq a l'identique.
 *     Attention, la fonction appelee est prefixee par $racine_fonctions_dest via un charger_fonction()
 *     Peut etre personalisee pour filtrer, renumeroter....
 *   param array $desc_tables_dest
 *     description des tables de destination a utiliser de preference a la description de la table source
 *   param int data_pool
 *     nombre de ko de donnees a envoyer d'un coup en insertion dans la table cible (par defaut 1)
 *     permet des envois groupes pour plus de rapidite, notamment si l'insertion est distante
 *
 * @return bool
 */
function base_copier_tables($status_file, $tables, $serveur_source, $serveur_dest, $options=array()){

	$callback_progression = isset($options['callback_progression'])?$options['callback_progression']:'';
	$max_time = isset($options['max_time'])?$options['max_time']:0;
	$drop_source = isset($options['drop_source'])?$options['drop_source']:false;
	$no_erase_dest = isset($options['no_erase_dest'])?$options['no_erase_dest']:array();
	$where = isset($options['where'])?$options['where']:array();
	$fonction_base_inserer = isset($options['fonction_base_inserer'])?$options['fonction_base_inserer']:'inserer_copie';
	$desc_tables_dest = isset($options['desc_tables_dest'])?$options['desc_tables_dest']:array();
	$racine_fonctions = (isset($options['racine_fonctions_dest'])?$options['racine_fonctions_dest']:'base');
	$data_pool = (isset($options['data_pool'])?$options['data_pool']:50*1024);

	spip_log( "Copier ".count($tables)." tables de '$serveur_source' vers '$serveur_dest'",'dump.'._LOG_INFO_IMPORTANTE);

	if (!$inserer_copie = charger_fonction($fonction_base_inserer,$racine_fonctions, true)) {
		spip_log( "Fonction '{$racine_fonctions}_$fonction_base_inserer' inconnue. Abandon",'dump.'._LOG_INFO_IMPORTANTE);
		return true; // echec mais on a fini, donc true
	}
	if (!$preparer_table_dest = charger_fonction('preparer_table_dest',$racine_fonctions, true)) {
		spip_log( "Fonction '{$racine_fonctions}_$preparer_table_dest' inconnue. Abandon",'dump.'._LOG_INFO_IMPORTANTE);
		return true; // echec mais on a fini, donc true
	}

	if (!lire_fichier($status_file, $status)
		OR !$status = unserialize($status))
		$status = array();
	$status['etape'] = 'basecopie';

	// puis relister les tables a importer
	// et les vider si besoin, au moment du premier passage ici
	$initialisation_copie = (!isset($status["dump_status_copie"])) ? 0 : $status["dump_status_copie"];

	// si init pas encore faite, vider les tables du serveur destination
	if (!$initialisation_copie) {
		if (!$vider_tables_destination_copie = charger_fonction('vider_tables_destination_copie',$racine_fonctions, true)) {
			spip_log( "Fonction '{$racine_fonctions}_vider_tables_destination_copie' inconnue. Abandon",'dump.'._LOG_INFO_IMPORTANTE);
			return true; // echec mais on a fini, donc true
		}
		$vider_tables_destination_copie($tables, $no_erase_dest, $serveur_dest);
		$status["dump_status_copie"]='ok';
		ecrire_fichier($status_file,serialize($status));
	}

	// les tables auteurs et meta doivent etre copiees en dernier !
	if (in_array('spip_auteurs',$tables)){
		$tables = array_diff($tables,array('spip_auteurs'));
		$tables[] = 'spip_auteurs';
	}
	if (in_array('spip_meta',$tables)){
		$tables = array_diff($tables,array('spip_meta'));
		$tables[] = 'spip_meta';
	}
	spip_log( "Tables a copier :".implode(", ",$tables),'dump.'._LOG_INFO);

	$trouver_table = charger_fonction('trouver_table','base');

	foreach ($tables as $table){
		// en principe, pas de spip_ dans le nom de table passe a trouver_table
		$desc_source = $trouver_table(preg_replace(",^spip_,","",$table), $serveur_source, false);
		if (!$desc_source)
			$desc_source = $trouver_table($table, $serveur_source, false);

		// verifier que la table est presente dans la base source
		if ($desc_source){
			// $status['tables_copiees'][$table] contient l'avancement
			// de la copie pour la $table : 0 a N et -N quand elle est finie (-1 si vide et finie...)
			if (!isset($status['tables_copiees'][$table]))
				$status['tables_copiees'][$table] = 0;

			if (is_numeric($status['tables_copiees'][$table])
				AND $status['tables_copiees'][$table]>=0
				AND $desc_dest = $preparer_table_dest(
								$table,
								isset($desc_tables_dest[$table])?$desc_tables_dest[$table]:$desc_source,
								$serveur_dest,
								$status['tables_copiees'][$table] == 0)){
				if ($callback_progression)
					$callback_progression($status['tables_copiees'][$table],0,$table);
				while (true) {
					$n = intval($status['tables_copiees'][$table]);
					// on copie par lot de 400
					$res = sql_select('*',$table,isset($where[$table])?$where[$table]:'','','',"$n,400",'',$serveur_source);
					while ($row = sql_fetch($res,$serveur_source)){
						$rows = array($row);
						// lire un groupe de donnees si demande en option
						// (permet un envoi par lot vers la destination)
						if ($data_pool>0){
							$s = strlen(serialize($row));
							while ($s<$data_pool AND $row = sql_fetch($res,$serveur_source)){
								$s += strlen(serialize($row));
								$rows[]= $row;
							}
						}
						// si l'enregistrement est deja en base, ca fera un echec ou un doublon
						// mais si ca renvoie false c'est une erreur fatale => abandon
						if ($inserer_copie($table,$rows,$desc_dest,$serveur_dest)===false) {
							// forcer la sortie, charge a l'appelant de gerer l'echec
							// copie finie
							return true;
						}
						$status['tables_copiees'][$table]+=count($rows);
						if ($max_time AND time()>$max_time)
							break;
					}
					if ($n == $status['tables_copiees'][$table])
						break;
					spip_log( "recopie $table ".$status['tables_copiees'][$table],'dump.'._LOG_INFO_IMPORTANTE);
					if ($callback_progression)
						$callback_progression($status['tables_copiees'][$table],0,$table);
					ecrire_fichier($status_file,serialize($status));
					if ($max_time AND time()>$max_time)
						return false; // on a pas fini, mais le temps imparti est ecoule
				}
				if ($drop_source) {
					sql_drop_table($table,'',$serveur_source);
					spip_log( "drop $table sur serveur source '$serveur_source'",'dump.'._LOG_INFO_IMPORTANTE);
				}
				$status['tables_copiees'][$table]=($status['tables_copiees'][$table]?-$status['tables_copiees'][$table]:"zero");
				ecrire_fichier($status_file,serialize($status));
				spip_log( "tables_recopiees ".implode(',',$status['tables_copiees']),'dump.'._LOG_INFO);
				if ($callback_progression)
					$callback_progression($status['tables_copiees'][$table],$status['tables_copiees'][$table],$table);
			}
			else {
				if ($status['tables_copiees'][$table]<0)
					spip_log("Table $table deja copiee : ".$status['tables_copiees'][$table],"dump."._LOG_INFO);
				if ($callback_progression)
					$callback_progression(0,$status['tables_copiees'][$table],"$table".((is_numeric($status['tables_copiees'][$table]) AND $status['tables_copiees'][$table]>=0)?"[Echec]":""));
			}
		}
		else {
			$status['errors'][] = "Impossible de lire la description de la table $table";
			ecrire_fichier($status_file,serialize($status));
			spip_log("Impossible de lire la description de la table $table","dump."._LOG_ERREUR);
		}
	}

	// si le nombre de tables envoyees n'est pas egal au nombre de tables demandees
	// abandonner
	if (count($status['tables_copiees'])<count($tables)){
		spip_log("Nombre de tables copiees incorrect : ".count($status['tables_copiees'])."/".count($tables),"dump."._LOG_ERREUR);
		$status['errors'][] = "Nombre de tables copiees incorrect : ".count($status['tables_copiees'])."/".count($tables);
		ecrire_fichier($status_file,serialize($status));
	}

	if ($detruire_copieur_si_besoin = charger_fonction('detruire_copieur_si_besoin',$racine_fonctions, true)) {
		$detruire_copieur_si_besoin($serveur_dest);
	}
	else {
		spip_log( "Fonction '{$racine_fonctions}_detruire_copieur_si_besoin' inconnue.",'dump.'._LOG_INFO_IMPORTANTE);
	}

	// OK, copie complete
	return true;
}

/**
 * fonction d'insertion en base lors de la copie de base a base
 *
 * @param string $table
 * @param array $rows
 * @param array $desc_dest
 * @param string $serveur_dest
 * @return int/bool
 */
function base_inserer_copie($table,$rows,$desc_dest,$serveur_dest){

	// verifier le nombre d'insertion
	$nb1 = sql_countsel($table);
	// si l'enregistrement est deja en base, ca fera un echec ou un doublon
	$r = sql_insertq_multi($table,$rows,$desc_dest,$serveur_dest);
	$nb = sql_countsel($table);
	if ($nb-$nb1<count($rows)){
		foreach($rows as $row){
			// si l'enregistrement est deja en base, ca fera un echec ou un doublon
			$r = sql_insertq($table,$row,$desc_dest,$serveur_dest);
		}
	}
	return $r;
}
?>
