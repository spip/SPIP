<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;


define ('_DEBUG_AUTORISER', false);

// surcharge possible de autoriser(), sinon autoriser_dist()
// http://doc.spip.org/@autoriser
if (!function_exists('autoriser')) {
// http://doc.spip.org/@autoriser
	function autoriser() {
		$args = func_get_args(); 
		return call_user_func_array('autoriser_dist', $args);
	}
}


// API pour une fonction generique d'autorisation :
// $qui est : vide (on prend alors auteur_session)
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

	// Qui ? auteur_session ?
	if ($qui === NULL)
		$qui = $GLOBALS['auteur_session']; // "" si pas connecte
	elseif (is_numeric($qui)) {
		$s = spip_query("SELECT * FROM spip_auteurs WHERE id_auteur=".$qui);
		$qui = spip_fetch_array($s);
	}

	// Admins restreints, on construit ici (pas generique mais...)
	// le tableau de toutes leurs rubriques (y compris les sous-rubriques)
	if (is_array($qui))
		$qui['restreint'] = liste_rubriques_auteur($qui['id_auteur']);

	if (_DEBUG_AUTORISER) spip_log("autoriser $faire $type $id ($qui[nom]) ?");

	// Aliases pour les types pas generiques (a etendre et ameliorer)
	if ($type == 'groupes_mot') $type = 'groupemots';
	#if ($type == 'syndic_article') $type = 'syndicarticle';

	// Chercher une fonction d'autorisation explicite
	if (
	// 1. Sous la forme "autoriser_type_faire"
		(
		$type
		AND $f = 'autoriser_'.$type.'_'.$faire
		AND (function_exists($f) OR function_exists($f.='_dist'))
		)

	// 2. Sous la forme "autoriser_type"
	// ne pas tester si $type est vide
	OR (
		$type
		AND $f = 'autoriser_'.$type
		AND (function_exists($f) OR function_exists($f.='_dist'))
	)

	// 3. Sous la forme "autoriser_faire"
	OR (
		$f = 'autoriser_'.$faire
		AND (function_exists($f) OR function_exists($f.='_dist'))
	)

	// 4. Sinon autorisation generique
	OR (
		$f = 'autoriser_defaut'
		AND (function_exists($f) OR function_exists($f.='_dist'))
	)

	)
		$a = $f($faire,$type,intval($id),$qui,$opt);

	if (_DEBUG_AUTORISER) spip_log("$f($faire,$type,$id,$qui[nom]): ".($a?'OK':'niet'));

	return $a;
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

	return ($GLOBALS['meta']['preview'] == '1comite'
		OR ($GLOBALS['meta']['preview']== 'oui' AND
		    $qui['statut']=='0minirezo')); 
}

// Autoriser a publier dans la rubrique $id
// http://doc.spip.org/@autoriser_rubrique_publierdans_dist
function autoriser_rubrique_publierdans_dist($faire, $type, $id, $qui, $opt) {
	return
		($qui['statut'] == '0minirezo')
		AND (
			!$qui['restreint']
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
	$s = spip_query(
	"SELECT id_parent FROM spip_rubriques WHERE id_rubrique="._q($id));
	$r = spip_fetch_array($s);
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
	if ($qui['statut'] == '0minirezo')
		return true;

	$s = spip_query("SELECT id_rubrique,statut FROM spip_syndic WHERE id_syndic="._q($id));
	return ($t = spip_fetch_array($s)
		AND autoriser('voir','rubrique',$t['id_rubrique'])
		AND ($t['statut'] == 'prop')
	);
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
// Autoriser a modifier la breve $id
// = admins & redac si la breve n'est pas publiee
// = admins de rubrique parente si publiee
// http://doc.spip.org/@autoriser_breve_modifier_dist
function autoriser_breve_modifier_dist($faire, $type, $id, $qui, $opt) {
	$s = spip_query(
	"SELECT id_rubrique,statut FROM spip_breves WHERE id_breve="._q($id));
	$r = spip_fetch_array($s);
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
	$s = spip_query(
	"SELECT id_rubrique,statut FROM spip_articles WHERE id_article="._q($id));
	$r = spip_fetch_array($s);
	include_spip('inc/auth');
	return
		autoriser('publierdans', 'rubrique', $r['id_rubrique'], $qui, $opt)
		OR (
			in_array($qui['statut'], array('0minirezo', '1comite'))
			AND in_array($r['statut'], array('prop','prepa', 'poubelle'))
			AND spip_num_rows(auteurs_article($id, "id_auteur=".$qui['id_auteur']))
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
			$s = spip_query(
				"SELECT id_groupe FROM spip_mots WHERE id_mot="._q($id)
			)
			AND $t = spip_fetch_array($s)
			AND autoriser('modifier', 'groupemots', $t['id_groupe'], $qui, $opt)
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
	$s = spip_query("SELECT statut FROM spip_articles WHERE id_article="._q($id));
	$r = spip_fetch_array($s);
	return
		in_array($r['statut'], array('prop', 'publie'))
		OR spip_num_rows(auteurs_article($id, "id_auteur=".$qui['id_auteur']));
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
				$s = spip_query("SELECT statut FROM spip_auteurs WHERE id_auteur=$id_auteur");
				if ($t = spip_fetch_array($s)
				AND $t['statut'] != '0minirezo')
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
// Tout le monde (y compris les visiteurs non enregistres)
// sauf si une extension comme acces_restreint a positionne creer_htaccees
//
// http://doc.spip.org/@autoriser_document_voir_dist

function autoriser_document_voir_dist($faire, $type, $id, $qui, $opt) {
	if ($GLOBALS['meta']["creer_htaccess"] != 'oui')
		return true;

	if (in_array($qui['statut'], array('0minirezo', '1comite')))
		return true;

	return
		spip_num_rows(spip_query("SELECT articles.id_article FROM spip_documents_articles AS rel_articles, spip_articles AS articles WHERE rel_articles.id_article = articles.id_article AND articles.statut = 'publie' AND rel_articles.id_document = $id  LIMIT 1")) > 0
	OR
		spip_num_rows(spip_query("SELECT rubriques.id_rubrique FROM spip_documents_rubriques AS rel_rubriques, spip_rubriques AS rubriques WHERE rel_rubriques.id_rubrique = rubriques.id_rubrique AND rubriques.statut = 'publie' AND rel_rubriques.id_document = $id LIMIT 1")) > 0
	OR
		spip_num_rows(spip_query("SELECT breves.id_breve FROM spip_documents_breves AS rel_breves, spip_breves AS breves WHERE rel_breves.id_breve = breves.id_breve AND breves.statut = 'publie' AND rel_breves.id_document = $id_document  LIMIT 1")) > 0
	;
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

	$q = spip_query("SELECT id_rubrique FROM spip_auteurs_rubriques WHERE id_auteur=$id_auteur AND id_rubrique!=0");

	// Recurrence sur les sous-rubriques
	$rubriques = array();
	while ($q AND spip_num_rows($q)) {
		$r = array();
		while ($row = spip_fetch_array($q)) {
			$id_rubrique = $row['id_rubrique'];
			$r[]= $rubriques[$id_rubrique] = $id_rubrique;
		}

		// Fin de la recurrence : $rubriques est complet
		$q = count($r)
			? spip_query("SELECT id_rubrique FROM spip_rubriques WHERE id_parent IN (".join(',',$r).") AND id_rubrique NOT IN (".join(',',$r).")")
			: false;
	}

	// Affecter l'auteur session le cas echeant
	if ($GLOBALS['auteur_session']['id_auteur'] == $id_auteur)
		$GLOBALS['auteur_session']['restreint'] = $rubriques;
			

	return $restreint[$id_auteur] = $rubriques;
}

// Deux fonctions sans surprise pour permettre les tests
// Dire toujours OK
// http://doc.spip.org/@autoriser_ok_dist
function autoriser_ok_dist($faire, $type, $id, $qui, $opt) { return true; }
// Dire toujours niet
// http://doc.spip.org/@autoriser_niet_dist
function autoriser_niet_dist($faire, $type, $id, $qui, $opt) { return false; }

?>
