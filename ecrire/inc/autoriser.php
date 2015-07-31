<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

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
// id du ou des webmestres, '1:5:90' a regler dans mes_options
if (!defined ('_ID_WEBMESTRES')) define ('_ID_WEBMESTRES', '1');

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
	if ($qui === NULL)
	  $qui = $GLOBALS['visiteur_session'] ? $GLOBALS['visiteur_session'] : array('statut' => '', 'id_auteur' =>0);
	elseif (is_numeric($qui)) {
		$qui = sql_fetsel("*", "spip_auteurs", "id_auteur=".$qui);
	}

	// Admins restreints, on construit ici (pas generique mais...)
	// le tableau de toutes leurs rubriques (y compris les sous-rubriques)
	if (_ADMINS_RESTREINTS AND is_array($qui))
		$qui['restreint'] = liste_rubriques_auteur($qui['id_auteur']);

	if (_DEBUG_AUTORISER) spip_log("autoriser $faire $type $id ($qui[nom]) ?");

	// Aliases pour les types pas generiques (a etendre et ameliorer)
	if ($type == 'groupes_mot') $type = 'groupemots';
	#if ($type == 'syndic_article') $type = 'syndicarticle';

	// Si une exception a ete decretee plus haut dans le code, l'appliquer
	if (isset($GLOBALS['autoriser_exception'][$faire][$type][$id])
	AND autoriser_exception($faire,$type,$id,'verifier'))
		return true;
	
	// Chercher une fonction d'autorisation
	// Dans l'ordre on va chercher autoriser_type_faire, autoriser_type,
	// autoriser_faire, autoriser_defaut ; puis les memes avec _dist
	$fonctions = $type
		? array (
			'autoriser_'.$type.'_'.$faire,
			'autoriser_'.$type,
			'autoriser_'.$faire,
			'autoriser_defaut',
			'autoriser_'.$type.'_'.$faire.'_dist',
			'autoriser_'.$type.'_dist',
			'autoriser_'.$faire.'_dist',
			'autoriser_defaut_dist'
		)
		: array (
			'autoriser_'.$faire,
			'autoriser_defaut',
			'autoriser_'.$faire.'_dist',
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
	return strpos($GLOBALS['meta']['preview'], ",". $qui['statut'] .",")
		!==false;
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

// Autoriser a creer une breve dans la rubrique $id
// http://doc.spip.org/@autoriser_rubrique_creerbrevedans_dist
function autoriser_rubrique_creerbrevedans_dist($faire, $type, $id, $qui, $opt) {
	$r = sql_fetsel("id_parent", "spip_rubriques", "id_rubrique=".sql_quote($id));
	return
		$id
		AND ($r['id_parent']==0)
		AND ($GLOBALS['meta']["activer_breves"]!="non")
		AND autoriser('voir','rubrique',$id);
}

// Autoriser a creer un site dans la rubrique $id
// http://doc.spip.org/@autoriser_rubrique_creersitedans_dist
function autoriser_rubrique_creersitedans_dist($faire, $type, $id, $qui, $opt) {
	return
		$id
		AND autoriser('voir','rubrique',$id)
		AND $GLOBALS['meta']['activer_sites'] != 'non'
		AND (
			$qui['statut']=='0minirezo'
			OR ($GLOBALS['meta']["proposer_sites"] >= 
			    ($qui['statut']=='1comite' ? 1 : 2)));
}

// Autoriser a modifier un site
// http://doc.spip.org/@autoriser_site_modifier_dist
function autoriser_site_modifier_dist($faire, $type, $id, $qui, $opt) {
	if ($qui['statut'] == '0minirezo' AND !$qui['restreint'])
		return true;

	$t = sql_fetsel("id_rubrique,statut", "spip_syndic", "id_syndic=".sql_quote($id));
	return ($t
		AND autoriser('voir','rubrique',$t['id_rubrique'])
		AND ($t['statut'] == 'prop'
			OR autoriser('modifier', 'rubrique', $t['id_rubrique'])
		)
	);
}
// Autoriser a voir un site $id_syndic
// http://doc.spip.org/@autoriser_site_voir_dist
function autoriser_site_voir_dist($faire, $type, $id, $qui, $opt) {
	return autoriser_site_modifier_dist($faire, $type, $id, $qui, $opt);
}

// Autoriser a modifier la rubrique $id
// = publierdans rubrique $id
// http://doc.spip.org/@autoriser_rubrique_modifier_dist
function autoriser_rubrique_modifier_dist($faire, $type, $id, $qui, $opt) {
	return
		autoriser('publierdans', 'rubrique', $id, $qui, $opt);
}

// On ne peut joindre un document qu'a un article qu'on a le droit d'editer
// mais il faut prevoir le cas d'une *creation* par un redacteur, qui correspond
// au hack id_article = 0-id_auteur
// http://doc.spip.org/@autoriser_joindredocument_dist
function autoriser_joindredocument_dist($faire, $type, $id, $qui, $opt){
	return
		autoriser('modifier', $type, $id, $qui, $opt)
		OR (
			$type == 'article'
			AND $id<0
			AND abs($id) == $qui['id_auteur']
			AND autoriser('ecrire', $type, $id, $qui, $opt)
		);
}

// On ne peut modifier un document que s'il est lie a un objet qu'on a le droit
// d'editer *et* qu'il n'est pas lie a un objet qu'on n'a pas le droit d'editer
// http://doc.spip.org/@autoriser_document_modifier_dist
function autoriser_document_modifier_dist($faire, $type, $id, $qui, $opt){
	static $m = array();

	if ($qui['statut'] == '0minirezo'
	AND !$qui['restreint'])
		return true;

	if (!isset($m[$id])) {
		$interdit = false;
		$vu = true;
		include_spip('public/interfaces');
		$jointures = array_diff(
			$GLOBALS['tables_jointures']['spip_documents'],
			array('types_documents', 'mots')
		);
		foreach($jointures as $j) {
			$type = preg_replace(',s?_?documents?_?|s$,', '', $j);
			$id_table = id_table_objet($type);
			$s = sql_select("$id_table", "spip_$j", "id_document=".sql_quote($id));
			while ($t = sql_fetch($s)) {
				spip_log($t);
				if (autoriser('modifier', $type, $t[$id_table], $qui, $opt)) {
					$vu = true;
				}
				else {
					$interdit = true;
					break 2;
				}
			}
		}
		$m[$id] = ($vu && !$interdit);
	}

	return $m[$id];
}


// Autoriser a modifier la breve $id
// = admins & redac si la breve n'est pas publiee
// = admins de rubrique parente si publiee
// http://doc.spip.org/@autoriser_breve_modifier_dist
function autoriser_breve_modifier_dist($faire, $type, $id, $qui, $opt) {
	$r = sql_fetsel("id_rubrique,statut", "spip_breves", "id_breve=".sql_quote($id));
	return
		($r['statut'] == 'publie')
			? autoriser('publierdans', 'rubrique', $r['id_rubrique'], $qui, $opt)
			: in_array($qui['statut'], array('0minirezo', '1comite'));
}

// Autoriser a modifier l'article $id
// = publierdans rubrique parente
// = ou statut 'prop,prepa' et $qui est auteur
// http://doc.spip.org/@autoriser_article_modifier_dist
function autoriser_article_modifier_dist($faire, $type, $id, $qui, $opt) {
	$r = sql_fetsel("id_rubrique,statut", "spip_articles", "id_article=".sql_quote($id));

	include_spip('inc/auth'); // pour auteurs_article si espace public

	return
		autoriser('publierdans', 'rubrique', $r['id_rubrique'], $qui, $opt)
		OR (
			in_array($qui['statut'], array('0minirezo', '1comite'))
			AND in_array($r['statut'], array('prop','prepa', 'poubelle'))
			AND sql_count(auteurs_article($id, "id_auteur=".$qui['id_auteur']))
		);
}


// Autoriser a creer un groupe de mots
// http://doc.spip.org/@autoriser_groupemots_creer_dist
function autoriser_groupemots_creer_dist($faire, $type, $id, $qui, $opt) {
	return
		$qui['statut'] == '0minirezo'
		AND !$qui['restreint'];
}

// Autoriser a modifier un groupe de mots $id
// y compris en ajoutant/modifiant les mots lui appartenant
// http://doc.spip.org/@autoriser_groupemots_modifier_dist
function autoriser_groupemots_modifier_dist($faire, $type, $id, $qui, $opt) {
	return
		$qui['statut'] == '0minirezo'
		AND !$qui['restreint'];
}

// Autoriser a modifier un mot $id ; note : si on passe l'id_groupe
// dans les options, on gagne du CPU (c'est ce que fait l'espace prive)
// http://doc.spip.org/@autoriser_mot_modifier_dist
function autoriser_mot_modifier_dist($faire, $type, $id, $qui, $opt) {
	return
	isset($opt['id_groupe'])
		? autoriser('modifier', 'groupemots', $opt['id_groupe'], $qui, $opt)
		: (
			$t = sql_getfetsel("id_groupe", "spip_mots", "id_mot=".sql_quote($id))
			AND autoriser('modifier', 'groupemots', $t, $qui, $opt)
		);
}

// Lire les stats ?
// = tous les admins
// http://doc.spip.org/@autoriser_voirstats_dist
function autoriser_voirstats_dist($faire, $type, $id, $qui, $opt) {
	return
		$qui['statut'] == '0minirezo';
}


// Voir un objet
// http://doc.spip.org/@autoriser_voir_dist
function autoriser_voir_dist($faire, $type, $id, $qui, $opt) {
	if ($type == 'document')
		return autoriser_document_voir_dist($faire, $type, $id, $qui, $opt);
	if ($qui['statut'] == '0minirezo') return true;
	if ($type == 'auteur') return false;
	if ($type != 'article') return true;
	if (!$id) return false;

	// un article 'prepa' ou 'poubelle' dont on n'est pas auteur : interdit
	$r = sql_getfetsel("statut", "spip_articles", "id_article=".sql_quote($id));
	include_spip('inc/auth'); // pour auteurs_article si espace public
	return
		in_array($r, array('prop', 'publie'))
		OR sql_count(auteurs_article($id, "id_auteur=".$qui['id_auteur']));
}

// Voir les revisions ?
// = voir l'objet
// http://doc.spip.org/@autoriser_voirrevisions_dist
function autoriser_voirrevisions_dist($faire, $type, $id, $qui, $opt) {
	return
		autoriser('voir', $type, $id, $qui, $opt);
}

// Moderer le forum ?
// = modifier l'objet correspondant (si forum attache a un objet)
// = droits par defaut sinon (admin complet pour moderation complete)
// http://doc.spip.org/@autoriser_modererforum_dist
function autoriser_modererforum_dist($faire, $type, $id, $qui, $opt) {
	return
		autoriser('modifier', $type, $id, $qui, $opt);
}

// Modifier un forum ?
// = jamais !
// http://doc.spip.org/@autoriser_forum_modifier_dist
function autoriser_forum_modifier_dist($faire, $type, $id, $qui, $opt) {
	return
		false;
}

// Modifier une signature ?
// = jamais !
// http://doc.spip.org/@autoriser_signature_modifier_dist
function autoriser_signature_modifier_dist($faire, $type, $id, $qui, $opt) {
	return
		false;
}

// Moderer la petition ?
// = modifier l'article correspondant
// = droits par defaut sinon (admin complet pour moderation de tout)
// http://doc.spip.org/@autoriser_modererpetition_dist
function autoriser_modererpetition_dist($faire, $type, $id, $qui, $opt) {
	return
		autoriser('modifier', $type, $id, $qui, $opt);
}

// Est-on webmestre ? Signifie qu'on n'a meme pas besoin de passer par ftp
// pour modifier les fichiers, cf. notamment inc/admin
// = rien ni personne sauf definition de 
// a l'avenir peut-etre autoriser "admin numero 1" ou une interface de selection
// http://doc.spip.org/@autoriser_webmestre_dist
function autoriser_webmestre_dist($faire, $type, $id, $qui, $opt) {
	return
		defined('_ID_WEBMESTRES')
		AND in_array($qui['id_auteur'], explode(':', _ID_WEBMESTRES))
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
		AND !$qui['restreint']
		;
}

// Effacer la base de donnees ?
// admins seulement (+auth ftp)
// a transformer en webmestre quand la notion sera fixee
// http://doc.spip.org/@autoriser_detruire_dist
function autoriser_detruire_dist($faire, $type, $id, $qui, $opt) {
	return
		$qui['statut'] == '0minirezo'
		AND !$qui['restreint']
		;
}

//
// http://doc.spip.org/@autoriser_auteur_previsualiser_dist
function autoriser_auteur_previsualiser_dist($faire, $type, $id, $qui, $opt) {
	// les admins peuvent "previsualiser" une page auteur
	if ($qui['statut'] == '0minirezo'
		AND !$qui['restreint']) return true;
	// "Voir en ligne" si l'auteur a un article publie
	$n = sql_countsel('spip_auteurs_articles AS lien, spip_articles AS articles', "lien.id_auteur=".sql_quote($id)." AND lien.id_article=articles.id_article AND articles.statut='publie'");
	if ($n) return true;
	return false;		
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
		if ($opt['statut'] OR $opt['restreintes'] OR $opt['email'])
			return false;
		else if ($id == $qui['id_auteur'])
			return true;
		else
			return false;
	}

	// Un admin restreint peut modifier/creer un auteur non-admin mais il
	// n'a le droit ni de le promouvoir admin, ni de changer les rubriques
	if ($qui['restreint']) {
		if ($opt['statut'] == '0minirezo'
		OR $opt['restreintes']) {
			return false;
		} else {
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
	return
		true;
}


//
// Peut-on faire de l'upload ftp ?
// par defaut, les administrateurs
//
// http://doc.spip.org/@autoriser_chargerftp_dist
function autoriser_chargerftp_dist($faire, $type, $id, $qui, $opt) {
	return $qui['statut'] == '0minirezo';
}


//
// Peut-on voir un document dans _DIR_IMG ?
// Tout le monde (y compris les visiteurs non enregistres), puisque par
// defaut ce repertoire n'est pas protege ; si une extension comme
// acces_restreint a positionne creer_htaccess, on regarde
// si le document est lie a un element publie
// (TODO: a revoir car c'est dommage de sortir de l'API true/false)
//
// http://doc.spip.org/@autoriser_document_voir_dist
function autoriser_document_voir_dist($faire, $type, $id, $qui, $opt) {

	if (!isset($GLOBALS['meta']["creer_htaccess"])
	OR $GLOBALS['meta']["creer_htaccess"] != 'oui')
		return true;

	if ((!is_numeric($id)) OR $id < 0) return false;

	if (in_array($qui['statut'], array('0minirezo', '1comite')))
		return 'htaccess';

	if (sql_countsel('spip_documents_liens AS rel_articles, spip_articles AS articles', "rel_articles.id_article = articles.id_article AND articles.statut = 'publie' AND rel_articles.id_document = $id") > 0
	OR sql_countsel('spip_documents_liens AS rel_rubriques, spip_rubriques AS rubriques', "rel_rubriques.id_rubrique = rubriques.id_rubrique AND rubriques.statut = 'publie' AND rel_rubriques.id_document = $id") > 0
	OR sql_countsel('spip_documents_liens AS rel_breves, spip_breves AS breves', "rel_breves.id_breve = breves.id_breve AND breves.statut = 'publie' AND rel_breves.id_document = $id") > 0)
		return 'htaccess';
	else return false;
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

	$where = "id_auteur=$id_auteur AND id_rubrique!=0";
	$table =  "spip_auteurs_rubriques";
	// Recurrence sur les sous-rubriques
	$rubriques = array();
	while (true) {
		$q = sql_select("id_rubrique", $table, $where);
		$r = array();
		while ($row = sql_fetch($q)) {
			$id_rubrique = $row['id_rubrique'];
			$r[]= $rubriques[$id_rubrique] = $id_rubrique;
		}

		// Fin de la recurrence : $rubriques est complet
		if (!$r) break;
		$table = 'spip_rubriques';
		$where = sql_in('id_parent', $r) . ' AND ' . 
		  sql_in('id_rubrique', $r, 'NOT');
	}

	// Affecter l'auteur session le cas echeant
	if ($GLOBALS['visiteur_session']['id_auteur'] == $id_auteur)
		$GLOBALS['visiteur_session']['restreint'] = $rubriques;
			

	return $restreint[$id_auteur] = $rubriques;
}

// Autoriser a modifier l'URL d'un objet (cf. action=redirect)
// http://doc.spip.org/@autoriser_modifierurl_dist
function autoriser_modifierurl_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('modifier', $type, $id, $qui, $opt);
}


// Deux fonctions sans surprise pour permettre les tests
// Dire toujours OK
// http://doc.spip.org/@autoriser_ok_dist
function autoriser_ok_dist($faire, $type, $id, $qui, $opt) { return true; }
// Dire toujours niet
// http://doc.spip.org/@autoriser_niet_dist
function autoriser_niet_dist($faire, $type, $id, $qui, $opt) { return false; }

?>
