<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2012                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Gestion de l'API autoriser et fonctions d'autorisations de SPIP
 *
 * @package SPIP\Core\Autorisations
**/
if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('base/abstract_sql');

/**
 * Tracer les autorisations dans tmp/spip.log pour débug ?
 */
defined('_DEBUG_AUTORISER') || define('_DEBUG_AUTORISER', false);

// Constantes surchargeables, cf. plugin autorite

/**
 * Gérer les admins restreints ?
 * 
 * @todo une option à activer
 */
defined('_ADMINS_RESTREINTS') || define('_ADMINS_RESTREINTS', true);

/** Statut par defaut à la creation */
defined('_STATUT_AUTEUR_CREATION') || define('_STATUT_AUTEUR_CREATION', '1comite');

/** statuts associables a des rubriques (separes par des virgules) */
defined('_STATUT_AUTEUR_RUBRIQUE') || define('_STATUT_AUTEUR_RUBRIQUE', _ADMINS_RESTREINTS ? '0minirezo' : '');

// mes_fonctions peut aussi declarer des autorisations, donc il faut donc le charger
if ($f = find_in_path('mes_fonctions.php')) {
	global $dossier_squelettes;
	include_once(_ROOT_CWD . $f);
}


if (!function_exists('autoriser')) {
/**
 * Autoriser une action
 * 
 * Teste si une personne (par défaut le visiteur en cours) peut effectuer
 * une certaine action. Cette fonction est le point d'entrée de toutes
 * les autorisations.
 *
 * La fonction se charge d'appeler des fonctions d'autorisations spécifiques
 * aux actions demandées si elles existent. Elle cherche donc les fonctions
 * dans cet ordre :
 * - autoriser_{type}_{faire}, sinon avec _dist
 * - autoriser_{type}, sinon avec _dist
 * - autoriser_{faire}, sinon avec _dist
 * - autoriser_{defaut}, sinon avec _dist
 * 
 * Seul le premier argument est obligatoire
 *
 * @api
 * @see autoriser_dist()
 * 
 * @param string $faire
 *   une action ('modifier', 'publier'...)
 * @param string $type
 *   type d'objet ou nom de table ('article')
 * @param int $id
 *   id de l'objet sur lequel on veut agir
 * @param null|int|array $qui
 *   - si null on prend alors visiteur_session
 *   - un id_auteur (on regarde dans la base)
 *   - un tableau auteur complet, y compris [restreint]
 * @param null|array $opt
 *   options sous forme de tableau associatif
 * @return bool
 *   true si la personne peut effectuer l'action
 */
	function autoriser($faire, $type='', $id=0, $qui = NULL, $opt = NULL) {
		// Charger les fonctions d'autorisation supplementaires
		static $pipe;
		if (!isset($pipe)) { $pipe = 1; pipeline('autoriser'); }

		$args = func_get_args();
		return call_user_func_array('autoriser_dist', $args);
	}
}


/**
 * Autoriser une action
 * 
 * Voir autoriser() pour une description complète
 * 
 * @see autoriser()
 * 
 * @param string $faire
 *   une action ('modifier', 'publier'...)
 * @param string $type
 *   type d'objet ou nom de table ('article')
 * @param int $id
 *   id de l'objet sur lequel on veut agir
 * @param null|int|array $qui
 *   si null on prend alors visiteur_session
 *   un id_auteur (on regarde dans la base)
 *   un tableau auteur complet, y compris [restreint]
 * @param null|array $opt
 *   options sous forme de tableau associatif
 * @return bool
 *   true si la personne peut effectuer l'action
 */
function autoriser_dist($faire, $type='', $id=0, $qui = NULL, $opt = NULL) {

	// Qui ? visiteur_session ?
	// si null ou '' (appel depuis #AUTORISER) on prend l'auteur loge
	if ($qui === NULL OR $qui==='')
	  $qui = $GLOBALS['visiteur_session'] ? $GLOBALS['visiteur_session'] : array('statut' => '', 'id_auteur' =>0, 'webmestre' => 'non');
	elseif (is_numeric($qui)) {
		$qui = sql_fetsel("*", "spip_auteurs", "id_auteur=".$qui);
	}

	// Admins restreints, on construit ici (pas generique mais...)
	// le tableau de toutes leurs rubriques (y compris les sous-rubriques)
	if (_ADMINS_RESTREINTS AND is_array($qui)) {
		$qui['restreint'] = isset($qui['id_auteur']) ? liste_rubriques_auteur($qui['id_auteur']) : array();
	}

	if (_DEBUG_AUTORISER) spip_log("autoriser $faire $type $id (" . (isset($qui['nom']) ? $qui['nom'] : '') . ") ?");

	// passer par objet_type pour avoir les alias
	// et supprimer les _
	$type = str_replace('_','',  strncmp($type,"_",1)==0?$type:objet_type($type,false));

	// Si une exception a ete decretee plus haut dans le code, l'appliquer
	if (isset($GLOBALS['autoriser_exception'][$faire][$type][$id])
	AND autoriser_exception($faire,$type,$id,'verifier'))
		return true;

	// Chercher une fonction d'autorisation
	// Dans l'ordre on va chercher autoriser_type_faire[_dist], autoriser_type[_dist],
	// autoriser_faire[_dist], autoriser_defaut[_dist]
	$fonctions = $type
		? array (
			'autoriser_'.$type.'_'.$faire,
			'autoriser_'.$type.'_'.$faire.'_dist',
			'autoriser_'.$type,
			'autoriser_'.$type.'_dist',
			'autoriser_'.$faire,
			'autoriser_'.$faire.'_dist',
			'autoriser_defaut',
			'autoriser_defaut_dist'
		)
		: array (
			'autoriser_'.$faire,
			'autoriser_'.$faire.'_dist',
			'autoriser_defaut',
			'autoriser_defaut_dist'
		);

	foreach ($fonctions as $f) {
		if (function_exists($f)) {
			$a = $f($faire,$type,$id,$qui,$opt);
			break;
		}
	}

	if (_DEBUG_AUTORISER) spip_log("$f($faire,$type,$id," . (isset($qui['nom']) ? $qui['nom'] : '') . "): ".($a?'OK':'niet'));

	return $a;
}

// une globale pour aller au plus vite dans la fonction generique ci dessus
$GLOBALS['autoriser_exception']=array();

/**
 * Accorder une autorisation exceptionnel pour le hit en cours, ou la revoquer
 *
 * http://doc.spip.org/@autoriser_exception
 *
 * @param string $faire   Action demandée
 * @param string $type    Type d'objet sur lequel appliquer l'action
 * @param int    $id      Identifiant de l'objet
 * @param bool $autoriser accorder (true) ou revoquer (false)
 * @return bool
 */
function autoriser_exception($faire,$type,$id,$autoriser=true){
	// une static innaccessible par url pour verifier que la globale est positionnee a bon escient
	static $autorisation;
	if ($autoriser==='verifier')
			return isset($autorisation[$faire][$type][$id]);
	if ($autoriser===true)
		$GLOBALS['autoriser_exception'][$faire][$type][$id] = $autorisation[$faire][$type][$id] = true;
	if ($autoriser===false) {
		unset($GLOBALS['autoriser_exception'][$faire][$type][$id]);
		unset($autorisation[$faire][$type][$id]);
	}
	return false;
}

 
/**
 * Autorisation par defaut
 *
 * Les admins complets OK, les autres non
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_defaut_dist($faire, $type, $id, $qui, $opt) {
	return
		$qui['statut'] == '0minirezo'
		AND !$qui['restreint'];
}


/**
 * Autorisation d'accès è l'espace privé ?
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_ecrire_dist($faire, $type, $id, $qui, $opt) {
	return in_array($qui['statut'], array('0minirezo', '1comite'));
}

/**
 * Autorisation de créer un contenu
 *
 * Accordée par defaut ceux qui accèdent à l'espace privé,
 * peut-être surchargée au cas par cas
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_creer_dist($faire, $type, $id, $qui, $opt) {
	return in_array($qui['statut'], array('0minirezo', '1comite'));
}

/**
 * Autorisation de prévisualiser un contenu
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_previsualiser_dist($faire, $type, $id, $qui, $opt) {
	// si auteur pas autorise, NIET
	if (strpos($GLOBALS['meta']['preview'], ",". $qui['statut'] .",")===false)
		return false;
	// si pas de type et statut fourni, c'est une autorisation generale => OK
	if (!$type)
		return true;

	include_spip('base/objets');
	$infos = lister_tables_objets_sql(table_objet_sql($type));
	if (isset($infos['statut']))
		foreach($infos['statut'] as $c){
			if (isset($c['publie'])){
				if (!isset($c['previsu'])) return false; // pas de previsu definie => NIET
				$champ = $c['champ'];
				if (!isset($opt[$champ])) return false; // pas de champ passe a la demande => NIET
				$previsu = explode(',',$c['previsu']);
				if (!in_array($opt[$champ],$previsu)) // le statut n'est pas dans ceux definis par la previsu => NIET
					return false;
			}
		}
	return true;
}

/**
 * Autorisation de changer de langue un contenu
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_changerlangue_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('modifier',$type,$id,$qui,$opt);
}

/**
 * Autorisation de changer la date d'un contenu
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_dater_dist($faire, $type, $id, $qui, $opt) {
	if (!isset($opt['statut'])){
		$table = table_objet($type);
		$trouver_table = charger_fonction('trouver_table','base');
		$desc = $trouver_table($table);
		if (!$desc)
			return false;
		if (isset($desc['field']['statut'])){
			$statut = sql_getfetsel("statut", $desc['table'], id_table_objet($type)."=".intval($id));
		}
		else
			$statut = 'publie'; // pas de statut => publie
	}
	else
		$statut = $opt['statut'];

	if ($statut == 'publie'
	 OR ($statut == 'prop' AND $type=='article' AND $GLOBALS['meta']["post_dates"] == "non"))
		return autoriser('modifier', $type, $id);
	return false;
}

/**
 * Autorisation d'instituer un contenu
 *
 * C'est à dire de changer son statut ou son parent.
 * Par défaut, il faut l'autorisation de modifier le contenu
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_instituer_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('modifier',$type,$id,$qui,$opt);
}

/**
 * Autorisation de publier dans une rubrique $id
 *
 * Il faut être administrateur ou administrateur restreint de la rubrique
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_rubrique_publierdans_dist($faire, $type, $id, $qui, $opt) {
	return
		($qui['statut'] == '0minirezo')
		AND (
			!$qui['restreint'] OR !$id
			OR in_array($id, $qui['restreint'])
		);
}

/**
 * Autorisation de créer une sous rubrique dans une rubrique $id
 *
 * Il faut être administrateur et pouvoir publier dans la rubrique
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_rubrique_creerrubriquedans_dist($faire, $type, $id, $qui, $opt) {
	return
		($id OR ($qui['statut'] == '0minirezo' AND !$qui['restreint']))
		AND autoriser('voir','rubrique',$id)
		AND autoriser('publierdans','rubrique',$id);
}

/**
 * Autorisation de créer un article dans une rubrique $id
 *
 * Il faut pouvoir voir la rubrique
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_rubrique_creerarticledans_dist($faire, $type, $id, $qui, $opt) {
	return
		$id
		AND autoriser('voir','rubrique',$id);
}


/**
 * Autorisation de modifier une rubrique $id
 *
 * Il faut pouvoir publier dans cette rubrique
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_rubrique_modifier_dist($faire, $type, $id, $qui, $opt) {
	return
		autoriser('publierdans', 'rubrique', $id, $qui, $opt);
}

/**
 * Autorisation de supprimer une rubrique $id
 *
 * Il faut quelle soit vide (pas d'enfant) et qu'on ait le droit de la modifier
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_rubrique_supprimer_dist($faire, $type, $id, $qui, $opt) {
	if (!$id = intval($id))
		return false;

	if (sql_countsel('spip_rubriques', "id_parent=".intval($id)))
		return false;

	if (sql_countsel('spip_articles', "id_rubrique=".intval($id)." AND (statut<>'poubelle')"))
		return false;

	$compte = pipeline('objet_compte_enfants',array('args'=>array('objet'=>'rubrique','id_objet'=>$id),'data'=>array()));
	foreach($compte as $objet => $n)
		if ($n)
			return false;

	return autoriser('modifier','rubrique',$id);
}


/**
 * Autorisation de modifier un article $id
 *
 * Il faut pouvoir publier dans le parent
 * ou, si on change le statut en proposé ou préparation être auteur de l'article
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_article_modifier_dist($faire, $type, $id, $qui, $opt) {
	$r = sql_fetsel("id_rubrique,statut", "spip_articles", "id_article=".sql_quote($id));

	if (!function_exists('auteurs_article'))
		include_spip('inc/auth'); // pour auteurs_article si espace public

	return
		$r
		AND
		(
			autoriser('publierdans', 'rubrique', $r['id_rubrique'], $qui, $opt)
			OR (
				(!isset($opt['statut']) OR $opt['statut']!=='publie')
				AND in_array($qui['statut'], array('0minirezo', '1comite'))
				AND in_array($r['statut'], array('prop','prepa', 'poubelle'))
				AND auteurs_article($id, "id_auteur=".$qui['id_auteur'])
			)
		);
}

 /**
 * Autorisation de créer un article
 *
 * Il faut qu'une rubrique existe et être au moins rédacteur
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_article_creer_dist($faire, $type, $id, $qui, $opt) {
	return (sql_countsel('spip_rubriques')>0 AND in_array($qui['statut'], array('0minirezo', '1comite')));
}

/**
 * Autorisation de voir un article
 *
 * Il faut être admin ou auteur de l'article, sinon il faut que l'article
 * soit publié ou proposé.
 * 
 * Peut-être appelée sans $id, mais avec un $opt['statut'] pour tester
 * la liste des status autorisés en fonction de $qui['statut']
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
 */
function autoriser_article_voir_dist($faire, $type, $id, $qui, $opt){
	if ($qui['statut'] == '0minirezo') return true;
	// cas des articles : depend du statut de l'article et de l'auteur
	if (isset($opt['statut']))
		$statut = $opt['statut'];
	else {
		if (!$id) return false;
		$statut = sql_getfetsel("statut", "spip_articles", "id_article=".intval($id));
	}

	return
		// si on est pas auteur de l'article,
		// seuls les propose et publies sont visibles
		in_array($statut, array('prop', 'publie'))
		// sinon si on est auteur, on a le droit de le voir, evidemment !
		OR
		($id AND $qui['id_auteur']
		     AND (function_exists('auteurs_article') OR include_spip('inc/auth'))
		     AND auteurs_article($id, "id_auteur=".$qui['id_auteur']));
}


/**
 * Autorisation de voir un objet
 *
 * Tout est visible par défaut, sauf les auteurs où il faut au moins être rédacteur.
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_voir_dist($faire, $type, $id, $qui, $opt) {
	# securite, mais on aurait pas du arriver ici !
	if (function_exists($f='autoriser_'.$type.'_voir') OR function_exists($f='autoriser_'.$type.'_voir_dist')){
		return $f($faire, $type, $id, $qui, $opt);
	}

	if ($qui['statut'] == '0minirezo') return true;
	// admins et redacteurs peuvent voir un auteur
	if ($type == 'auteur')
		return in_array($qui['statut'], array('0minirezo', '1comite'));
	// sinon par defaut tout est visible
	// sauf cas particuliers traites separemment (ie article)
	return true;
}


/**
 * Autorisation de webmestre
 *
 * Est-on webmestre ? Signifie qu'on n'a même pas besoin de passer par ftp
 * pour modifier les fichiers, cf. notamment inc/admin
 * 
 * Soit la liste des webmestres est définie via une constante _ID_WEBMESTRES,
 * soit on regarde l'état "webmestre" de l'auteur
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_webmestre_dist($faire, $type, $id, $qui, $opt) {
	return
		(defined('_ID_WEBMESTRES')?
			in_array($qui['id_auteur'], explode(':', _ID_WEBMESTRES))
			:$qui['webmestre']=='oui')
		AND $qui['statut'] == '0minirezo'
		AND !$qui['restreint']
		;
}

/**
 * Autorisation Configurer le site
 *
 * Il faut être administrateur complet
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_configurer_dist($faire, $type, $id, $qui, $opt) {
	return
		$qui['statut'] == '0minirezo'
		AND !$qui['restreint']
		;
}

/**
 * Autorisation de sauvegarder la base de données
 *
 * Il faut être administrateur (y compris restreint)
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_sauvegarder_dist($faire, $type, $id, $qui, $opt) {
	return
		$qui['statut'] == '0minirezo'
		;
}

/**
 * Autorisation d'effacer la base de données
 *
 * Il faut être webmestre
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_detruire_dist($faire, $type, $id, $qui, $opt) {
	return
		autoriser('webmestre', null, null, $qui, $opt);
}

/**
 * Autorisation de prévisialiser un auteur
 *
 * Il faut être administrateur ou que l'auteur à prévisualiser
 * ait au moins publié un article
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_auteur_previsualiser_dist($faire, $type, $id, $qui, $opt) {
	// les admins peuvent "previsualiser" une page auteur
	if ($qui['statut'] == '0minirezo'
		AND !$qui['restreint']) return true;
	// "Voir en ligne" si l'auteur a un article publie
	$n = sql_fetsel('A.id_article', 'spip_auteurs_liens AS L LEFT JOIN spip_articles AS A ON (L.objet=\'article\' AND L.id_objet=A.id_article)', "A.statut='publie' AND L.id_auteur=".sql_quote($id));
	return $n ? true : false;
}


/**
 * Autorisation de modifier un auteur
 *
 * Attention tout depend de ce qu'on veut modifier. Il faut être au moins
 * rédacteur, mais on ne peut pas promouvoir (changer le statut) un auteur
 * avec des droits supérieurs au sien.
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_auteur_modifier_dist($faire, $type, $id, $qui, $opt) {

	// Ni admin ni redacteur => non
	if (!in_array($qui['statut'], array('0minirezo', '1comite')))
		return false;

	// Un redacteur peut modifier ses propres donnees mais ni son login/email
	// ni son statut (qui sont le cas echeant passes comme option)
	if ($qui['statut'] == '1comite') {
		if ($opt['webmestre'])
			return false;
		elseif ($opt['statut'] OR $opt['restreintes'] OR $opt['email'])
			return false;
		elseif ($id == $qui['id_auteur'])
			return true;
		else
			return false;
	}

	// Un admin restreint peut modifier/creer un auteur non-admin mais il
	// n'a le droit ni de le promouvoir admin, ni de changer les rubriques
	if ($qui['restreint']) {
		if ($opt['webmestre'])
			return false;
		elseif ($opt['statut'] == '0minirezo' OR $opt['restreintes'])
			return false;
		else {
			if ($id == $qui['id_auteur']) {
				if ($opt['statut'])
					return false;
				else
					return true;
			}
			else if ($id_auteur = intval($id)) {
				$t = sql_fetsel("statut", "spip_auteurs", "id_auteur=$id_auteur");
				if ($t AND $t['statut'] != '0minirezo')
					return true;
				else
					return false;
			}
			// id = 0 => creation
			else
				return true;
		}
	}

	// Un admin complet fait ce qu'elle veut
	// sauf se degrader
	if ($id == $qui['id_auteur'] && $opt['statut'])
		return false;
	// et toucher au statut webmestre si il ne l'est pas lui meme
	// ou si les webmestres sont fixes par constante (securite)
	elseif (isset($opt['webmestre']) AND $opt['webmestre'] AND (defined('_ID_WEBMESTRES') OR !autoriser('webmestre')))
		return false;
	// et modifier un webmestre si il ne l'est pas lui meme
	elseif (intval($id) AND autoriser('webmestre','',0,$id) AND !autoriser('webmestre'))
		return false;
	else
		return true;
}


/**
 * Autorisation d'associer un auteur sur un objet
 *
 * Il faut pouvoir modifier l'objet en question
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_associerauteurs_dist($faire, $type, $id, $qui, $opt){
	return autoriser('modifier', $type, $id, $qui, $opt);
}


/**
 * Autorisation d'upload FTP
 *
 * Il faut être administrateur.
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_chargerftp_dist($faire, $type, $id, $qui, $opt) {
	return $qui['statut'] == '0minirezo';
}

/**
 * Autorisation d'activer le mode debug
 *
 * Il faut être administrateur.
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_debug_dist($faire, $type, $id, $qui, $opt) {
	return $qui['statut'] == '0minirezo';
}

/**
 * Liste les rubriques d'un auteur
 *
 * Renvoie la liste des rubriques liées à cet auteur, independamment de son
 * statut (pour les admins restreints, il faut donc aussi vérifier statut)
 *
 * Mémorise le resultat dans un tableau statique indéxé par les id_auteur.
 * On peut reinitialiser un élément en passant un 2e argument non vide
 * 
 * @param int  $id_auteur Identifiant de l'auteur
 * @param bool $raz       Recalculer le résultat connu pour cet auteur
 * @return array          Liste des rubriques
**/
function liste_rubriques_auteur($id_auteur, $raz=false) {
	static $restreint = array();

	if (!$id_auteur = intval($id_auteur)) return array();
	if ($raz) unset($restreint[$id_auteur]);
	elseif (isset($restreint[$id_auteur])) return $restreint[$id_auteur];

	$rubriques = array();
	if (
		(!isset($GLOBALS['meta']['version_installee']) OR $GLOBALS['meta']['version_installee']>16428)
	  AND $r = sql_allfetsel('id_objet', 'spip_auteurs_liens', "id_auteur=".intval($id_auteur)." AND objet='rubrique' AND id_objet!=0")
	  AND count($r)) {
		$r = array_map('reset',$r);

		// recuperer toute la branche, au format chaine enumeration
		include_spip('inc/rubriques');
		$r = calcul_branche_in($r);
		$r = explode(',',$r);

		// passer les rubriques en index, elimine les doublons
		$r = array_flip($r);
		// recuperer les index seuls
		$r = array_keys($r);
		// combiner pour avoir un tableau id_rubrique=>id_rubrique
		// est-ce vraiment utile ? (on preserve la forme donnee par le code precedent)
		$rubriques = array_combine($r,$r);
	}

	// Affecter l'auteur session le cas echeant
	if ($GLOBALS['visiteur_session']['id_auteur'] == $id_auteur)
		$GLOBALS['visiteur_session']['restreint'] = $rubriques;


	return $restreint[$id_auteur] = $rubriques;
}

/**
 * Autorisation de modifier l'URL d'un objet
 *
 * Il faut pouvoir modifier l'objet.
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_modifierurl_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('modifier', $type, $id, $qui, $opt);
}

/**
 * Autorisation de prévisualiser une rubrique
 *
 * Il faut pouvoir prévisualiser.
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_rubrique_previsualiser_dist($faire,$type,$id,$qui,$opt){
	return autoriser('previsualiser');
}

/**
 * Autorisation d'iconifier une rubrique (mettre un logo)
 *
 * Il faut pouvoir publier dans la rubrique.
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_rubrique_iconifier_dist($faire,$type,$id,$qui,$opt){
	return autoriser('publierdans', 'rubrique', $id, $qui, $opt);
}

/**
 * Autorisation d'iconifier un auteur (mettre un logo)
 *
 * Il faut un administrateur ou que l'auteur soit celui qui demande l'autorisation
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_auteur_iconifier_dist($faire,$type,$id,$qui,$opt){
 return (($id == $qui['id_auteur']) OR
 		(($qui['statut'] == '0minirezo') AND !$qui['restreint']));
}

/**
 * Autorisation d'iconifier un objet (mettre un logo)
 *
 * Il faut pouvoir modifier l'objet
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_iconifier_dist($faire,$type,$id,$qui,$opt){
	// par defaut, on a le droit d'iconifier si on a le droit de modifier
	return autoriser('modifier', $type, $id, $qui, $opt);
}


/**
 * Autorisation OK
 *
 * Autorise toujours !
 * Fonction sans surprise pour permettre les tests.
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true
**/
function autoriser_ok_dist($faire, $type, $id, $qui, $opt) { return true; }

/**
 * Autorisation NIET
 *
 * Refuse toujours !
 * Fonction sans surprise pour permettre les tests.
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          false
**/
function autoriser_niet_dist($faire, $type, $id, $qui, $opt) { return false; }

/**
 * Autorisation de réparer la base de données
 *
 * Il faut pouvoir la détruire (et ne pas être en cours de réinstallation)
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          false
**/
function autoriser_base_reparer_dist($faire, $type, $id, $qui, $opt) {
	if (!autoriser('detruire') OR _request('reinstall'))
		return false;

	return true;
}

/**
 * Autorisation de voir l'onglet infosperso
 *
 * Toujours OK
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_infosperso_onglet_dist($faire,$type,$id,$qui,$opt) {
	return true;
}

/**
 * Autorisation de voir l'onglet configurerlangage
 *
 * Toujours OK
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_configurerlangage_onglet_dist($faire,$type,$id,$qui,$opt) {
	return true;
}

/**
 * Autorisation de voir l'onglet configurerpreferences
 *
 * Toujours OK
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_configurerpreferences_onglet_dist($faire,$type,$id,$qui,$opt) {
	return true;
}

/**
 * Autorisation de voir le menu auteurs
 *
 * Toujours OK
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_auteurs_menu_dist($faire, $type, $id, $qui, $opt){return true;}

/**
 * Autorisation de voir le menu articles
 *
 * Toujours OK
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_articles_menu_dist($faire, $type, $id, $qui, $opt){return true;}

/**
 * Autorisation de voir le menu rubriques
 *
 * Toujours OK
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_rubriques_menu_dist($faire, $type, $id, $qui, $opt){return true;}

/**
 * Autorisation de voir le menu articlecreer 
 *
 * Il faut au moins une rubrique présente.
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_articlecreer_menu_dist($faire, $type, $id, $qui, $opt){
	return verifier_table_non_vide();
}

/**
 * Autorisation de voir le menu suiviedito
 *
 * Il faut être administrateur (y compris restreint).
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_suiviedito_menu_dist($faire, $type, $id, $qui, $opt){
	return $qui['statut']=='0minirezo';
}

/**
 * Autorisation de voir le menu synchro
 *
 * Il faut être administrateur (y compris restreint).
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_synchro_menu_dist($faire, $type, $id, $qui, $opt){
	return $qui['statut']=='0minirezo';
}

/**
 * Autorisation de purger la queue de travaux
 *
 * Il faut être webmestre.
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_queue_purger_dist($faire, $type, $id, $qui, $opt){
	return autoriser('webmestre');
}


/**
 * Autorisation l'échafaudage de squelettes en Z
 *
 * Il faut être dans l'espace privé (et authentifié),
 * sinon il faut être webmestre (pas de fuite d'informations publiées)
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_echafauder_dist($faire, $type, $id, $qui, $opt){
	if (test_espace_prive())
		return intval($qui['id_auteur'])?true:false;
	else
		return autoriser('webmestre','',$id,$qui,$opt);
}


/**
 * Lister les auteurs d'un article
 * 
 * Fonction générique utilisée par plusieurs autorisations
 *
 * @param int $id_article Identifiant de l'article
 * @param string $cond    Condition en plus dans le where de la requête
 * @return array|bool
 *     - array : liste des id_auteur trouvés
 *     - false : serveur SQL indisponible
 */
function auteurs_article($id_article, $cond='')
{
	return sql_allfetsel("id_auteur", "spip_auteurs_liens", "objet='article' AND id_objet=$id_article". ($cond ? " AND $cond" : ''));
}


/**
 * Tester si on est admin restreint sur une rubrique donnée
 * 
 * Fonction générique utilisee dans des autorisations ou assimilée
 *
 * @param int $id_rubrique  Identifiant de la rubrique
 * @return bool             true si administrateur de cette rubrique, false sinon.
 */
function acces_restreint_rubrique($id_rubrique) {
	global $connect_id_rubrique;

	return (isset($connect_id_rubrique[$id_rubrique]));
}


/**
 * Verifier qu'il existe au moins un parent
 * 
 * Fonction utilisee dans des autorisations des boutons / menus du prive des objets enfants (articles, breves, sites)
 *
 * @param string $table  	la table a verifier
 * @return bool             true si un parent existe
 */
function verifier_table_non_vide($table='spip_rubriques') {
	static $done = array();
	if (!isset($done[$table]))
		 $done[$table] = sql_countsel($table)>0;
	return $done[$table];
}
?>
