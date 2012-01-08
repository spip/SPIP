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

include_spip('base/abstract_sql');

# faut-il tracer les autorisations dans tmp/spip.log ?
define ('_DEBUG_AUTORISER', false);

// Constantes surchargeables, cf. plugin autorite
// false pour ignorer la notion d'admin restreint # todo: une option a activer
define('_ADMINS_RESTREINTS', true);
// statut par defaut a la creation
define('_STATUT_AUTEUR_CREATION', '1comite');
// statuts associables a des rubriques (separes par des virgules)
define('_STATUT_AUTEUR_RUBRIQUE', _ADMINS_RESTREINTS ? '0minirezo' : '');

// mes_fonctions peut aussi declarer des autorisations, donc il faut donc le charger
if ($f = find_in_path('mes_fonctions.php')) {
	global $dossier_squelettes;
	include_once(_ROOT_CWD . $f);
}


// surcharge possible de autoriser(), sinon autoriser_dist()
if (!function_exists('autoriser')) {
// http://doc.spip.org/@autoriser
	function autoriser() {
		// Charger les fonctions d'autorisation supplementaires
		static $pipe;
		if (!isset($pipe)) { $pipe = 1; pipeline('autoriser'); }

		$args = func_get_args();
		return call_user_func_array('autoriser_dist', $args);
	}
}


// API pour une fonction generique d'autorisation :
// $qui est : vide (on prend alors visiteur_session)
//            un id_auteur (on regarde dans la base)
//            un tableau auteur complet, y compris [restreint]
// $faire est une action ('modifier', 'publier'...)
// $type est un type d'objet ou nom de table ('article')
// $id est l'id de l'objet sur lequel on veut agir
// $opt (inutilise pour le moment) = options sous forme de tableau associatif
// (par exemple pour preciser si l'autorisation concerne tel ou tel champ)
//
// Seul le premier argument est obligatoire
//
// http://doc.spip.org/@autoriser_dist
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
	if (_ADMINS_RESTREINTS AND is_array($qui))
		$qui['restreint'] = liste_rubriques_auteur($qui['id_auteur']);

	if (_DEBUG_AUTORISER) spip_log("autoriser $faire $type $id ($qui[nom]) ?");

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

	if (_DEBUG_AUTORISER) spip_log("$f($faire,$type,$id,$qui[nom]): ".($a?'OK':'niet'));

	return $a;
}

// une globale pour aller au plus vite dans la fonction generique ci dessus
$GLOBALS['autoriser_exception']=array();
// http://doc.spip.org/@autoriser_exception
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

// Autorisation par defaut : les admins complets OK, les autres non
// http://doc.spip.org/@autoriser_defaut_dist
function autoriser_defaut_dist($faire, $type, $id, $qui, $opt) {
	return
		$qui['statut'] == '0minirezo'
		AND !$qui['restreint'];
}

// A-t-on acces a l'espace prive ?
// http://doc.spip.org/@autoriser_ecrire_dist
function autoriser_ecrire_dist($faire, $type, $id, $qui, $opt) {
	return in_array($qui['statut'], array('0minirezo', '1comite'));
}

// http://doc.spip.org/@autoriser_previsualiser_dist
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


function autoriser_changerlangue_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('modifier',$type,$id,$qui,$opt);
}

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

function autoriser_instituer_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('modifier',$type,$id,$qui,$opt);
}

// Autoriser a publier dans la rubrique $id
// http://doc.spip.org/@autoriser_rubrique_publierdans_dist
function autoriser_rubrique_publierdans_dist($faire, $type, $id, $qui, $opt) {
	return
		($qui['statut'] == '0minirezo')
		AND (
			!$qui['restreint'] OR !$id
			OR in_array($id, $qui['restreint'])
		);
}

// Autoriser a creer une rubrique dans la rubrique $id
// http://doc.spip.org/@autoriser_rubrique_creerrubriquedans_dist
function autoriser_rubrique_creerrubriquedans_dist($faire, $type, $id, $qui, $opt) {
	return
		($id OR ($qui['statut'] == '0minirezo' AND !$qui['restreint']))
		AND autoriser('voir','rubrique',$id)
		AND autoriser('publierdans','rubrique',$id);
}

// Autoriser a creer un article dans la rubrique $id
// http://doc.spip.org/@autoriser_rubrique_creerarticledans_dist
function autoriser_rubrique_creerarticledans_dist($faire, $type, $id, $qui, $opt) {
	return
		$id
		AND autoriser('voir','rubrique',$id);
}


// Autoriser a modifier la rubrique $id
// = publierdans rubrique $id
// http://doc.spip.org/@autoriser_rubrique_modifier_dist
function autoriser_rubrique_modifier_dist($faire, $type, $id, $qui, $opt) {
	return
		autoriser('publierdans', 'rubrique', $id, $qui, $opt);
}

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


// Autoriser a modifier l'article $id
// = publierdans rubrique parente
// = ou statut 'prop,prepa' et $qui est auteur
// http://doc.spip.org/@autoriser_article_modifier_dist
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
 * Autoriser a creer un article :
 * Il faut qu'une rubrique existe et qu'on est le statut necessaire pour creer
 * 
 * @return bool
 */
function autoriser_article_creer_dist($faire, $type, $id, $qui, $opt) {
	return (sql_countsel('spip_rubriques')>0 AND in_array($qui['statut'], array('0minirezo', '1comite')));
}

/**
 * Autoriser a voir un article :
 * peut etre appelee sans id, mais avec un $opt['statut'] pour tester
 * la liste des status autorises en fonction de $qui['statut']
 * 
 * @return bool
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

// Voir un objet
// http://doc.spip.org/@autoriser_voir_dist
function autoriser_voir_dist($faire, $type, $id, $qui, $opt) {
	# securite, mais on aurait pas du arriver ici !
	if (function_exists($f='autoriser_'.$type.'_voir') OR function_exists($f='autoriser_'.$type.'_voir_dist')){
		return $f($faire, $type, $id, $qui, $opt);
	}

	if ($qui['statut'] == '0minirezo') return true;
	// seuls les admin peuvent voir un auteur
	if ($type == 'auteur')
		return false;
	// sinon par defaut tout est visible
	// sauf cas particuliers traites separemment (ie article)
	return true;
}

// Est-on webmestre ? Signifie qu'on n'a meme pas besoin de passer par ftp
// pour modifier les fichiers, cf. notamment inc/admin
// = rien ni personne sauf definition de
// a l'avenir peut-etre autoriser "admin numero 1" ou une interface de selection
// http://doc.spip.org/@autoriser_webmestre_dist
function autoriser_webmestre_dist($faire, $type, $id, $qui, $opt) {
	return
		(defined('_ID_WEBMESTRES')?
			in_array($qui['id_auteur'], explode(':', _ID_WEBMESTRES))
			:$qui['webmestre']=='oui')
		AND $qui['statut'] == '0minirezo'
		AND !$qui['restreint']
		;
}

// Configurer le site => idem autorisation par defaut
// http://doc.spip.org/@autoriser_configurer_dist
function autoriser_configurer_dist($faire, $type, $id, $qui, $opt) {
	return
		$qui['statut'] == '0minirezo'
		AND !$qui['restreint']
		;
}

// Effectuer un backup ?
// admins y compris restreints
// http://doc.spip.org/@autoriser_sauvegarder_dist
function autoriser_sauvegarder_dist($faire, $type, $id, $qui, $opt) {
	return
		$qui['statut'] == '0minirezo'
		;
}

// Effacer la base de donnees ?
// webmestres seulement
// http://doc.spip.org/@autoriser_detruire_dist
function autoriser_detruire_dist($faire, $type, $id, $qui, $opt) {
	return
		autoriser('webmestre', null, null, $qui, $opt);
}

//
// http://doc.spip.org/@autoriser_auteur_previsualiser_dist
function autoriser_auteur_previsualiser_dist($faire, $type, $id, $qui, $opt) {
	// les admins peuvent "previsualiser" une page auteur
	if ($qui['statut'] == '0minirezo'
		AND !$qui['restreint']) return true;
	// "Voir en ligne" si l'auteur a un article publie
	$n = sql_fetsel('A.id_article', 'spip_auteurs_liens AS L LEFT JOIN spip_articles AS A ON (L.objet=\'article\' AND L.id_objet=A.id_article)', "A.statut='publie' AND L.id_auteur=".sql_quote($id));
	return $n ? true : false;
}

// Modifier un auteur ?
// Attention tout depend de ce qu'on veut modifier
// http://doc.spip.org/@autoriser_auteur_modifier_dist
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
	elseif ($opt['webmestre'] AND (defined('_ID_WEBMESTRES') OR !autoriser('webmestre')))
		return false;
	// et toucher au statut d'un webmestre si il ne l'est pas lui meme
	elseif ($opt['statut'] AND autoriser('webmestre','',0,$id) AND !autoriser('webmestre'))
		return false;
	else
		return true;
}


//
// Peut-on faire de l'upload ftp ?
// par defaut, les administrateurs
//
// http://doc.spip.org/@autoriser_chargerftp_dist
function autoriser_chargerftp_dist($faire, $type, $id, $qui, $opt) {
	return $qui['statut'] == '0minirezo';
}



// Qui peut activer le debugueur ?
// http://doc.spip.org/@autoriser_debug_dist
function autoriser_debug_dist($faire, $type, $id, $qui, $opt) {
	return $qui['statut'] == '0minirezo';
}

// Renvoie la liste des rubriques liees a cet auteur, independamment de son
// statut (pour les admins restreints, il faut donc aussi verifier statut)
// Memorise le resultat dans un tableau statique indexe par les id_auteur.
// On peut reinitialiser un element en passant un 2e argument non vide
// http://doc.spip.org/@liste_rubriques_auteur
function liste_rubriques_auteur($id_auteur, $raz=false) {
	static $restreint = array();

	if (!$id_auteur = intval($id_auteur)) return array();
	if ($raz) unset($restreint[$id_auteur]);
	elseif (isset($restreint[$id_auteur])) return $restreint[$id_auteur];

	$rubriques = array();
	if ($GLOBALS['meta']['version_installee']>16428
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

// Autoriser a modifier l'URL d'un objet (cf. action=redirect)
// http://doc.spip.org/@autoriser_modifierurl_dist
function autoriser_modifierurl_dist($faire, $quoi, $id, $qui, $opt) {
	return autoriser('modifier', $quoi, $id, $qui, $opt);
}

function autoriser_rubrique_previsualiser_dist($faire,$quoi,$id,$qui,$opts){
	return autoriser('previsualiser');
}

// http://doc.spip.org/@autoriser_rubrique_iconifier_dist
function autoriser_rubrique_iconifier_dist($faire,$quoi,$id,$qui,$opts){
	return autoriser('publierdans', 'rubrique', $id, $qui, $opts);
}
// http://doc.spip.org/@autoriser_auteur_iconifier_dist
function autoriser_auteur_iconifier_dist($faire,$quoi,$id,$qui,$opts){
 return (($id == $qui['id_auteur']) OR
 		(($qui['statut'] == '0minirezo') AND !$qui['restreint']));
}

// http://doc.spip.org/@autoriser_article_iconifier_dist
function autoriser_iconifier_dist($faire,$quoi,$id,$qui,$opts){
	// par defaut, on a le droit d'iconifier si on a le droit de modifier
	return autoriser('modifier', $quoi, $id, $qui, $opts);
}

// Deux fonctions sans surprise pour permettre les tests
// Dire toujours OK
// http://doc.spip.org/@autoriser_ok_dist
function autoriser_ok_dist($faire, $type, $id, $qui, $opt) { return true; }
// Dire toujours niet
// http://doc.spip.org/@autoriser_niet_dist
function autoriser_niet_dist($faire, $type, $id, $qui, $opt) { return false; }


function autoriser_base_reparer_dist($faire, $type, $id, $qui, $opts) {
	if (!autoriser('detruire') OR _request('reinstall'))
		return false;

	return true;
}

// Autorisations pour les onglets de la page auteur
function autoriser_infosperso_onglet_dist($faire,$quoi,$id,$qui,$opts) {
	return true;
}
function autoriser_configurerlangage_onglet_dist($faire,$quoi,$id,$qui,$opts) {
	return true;
}
function autoriser_configurerpreference_onglet_dist($faire,$quoi,$id,$qui,$opts) {
	return true;
}
function autoriser_auteurs_menu_dist($faire, $type, $id, $qui, $opts){return true;}
function autoriser_articles_menu_dist($faire, $type, $id, $qui, $opts){return true;}
function autoriser_rubriques_menu_dist($faire, $type, $id, $qui, $opts){return true;}

function autoriser_articlecreer_menu_dist($faire, $type, $id, $qui, $opts){
	return sql_countsel('spip_rubriques')>0;
}

function autoriser_suiviedito_menu_dist($faire, $type, $id, $qui, $opts){
	return $qui['statut']=='0minirezo';
}
function autoriser_synchro_menu_dist($faire, $type, $id, $qui, $opts){
	return $qui['statut']=='0minirezo';
}

/**
 * Autoriser la purge de la queue : il faut etre webmestre
 * @return mixed
 */
function autoriser_queue_purger_dist(){
	return autoriser('webmestre');
}

/*
 * Autoriser l'echaffaudage de squelettes en Z
 * dans l'espace prive : oui si on est identifie
 * sinon il faut etre webmestre (pas de fuite d'informations publiees)
 */
function autoriser_echaffauder_dist($faire, $type, $id, $qui, $opts){
	if (test_espace_prive())
		return intval($qui['id_auteur'])?true:false;
	else
		return autoriser('webmestre','',$id,$qui,$opts);
}


/**
 * Lister les auteurs d'un article
 * fonction generique utilisee par plusieurs autorisations
 *
 * http://doc.spip.org/@auteurs_article
 *
 * @param int $id_article
 * @param string $cond
 * @return array|bool
 */
function auteurs_article($id_article, $cond='')
{
	return sql_allfetsel("id_auteur", "spip_auteurs_liens", "objet='article' AND id_objet=$id_article". ($cond ? " AND $cond" : ''));
}


/**
 * Tester si on est admin restreint sur une rubrique donnee
 * fonction generique utilisee dans des autorisations ou assimilee
 *
 * http://doc.spip.org/@acces_restreint_rubrique
 *
 * @param  $id_rubrique
 * @return bool
 */
function acces_restreint_rubrique($id_rubrique) {
	global $connect_id_rubrique;

	return (isset($connect_id_rubrique[$id_rubrique]));
}
?>
