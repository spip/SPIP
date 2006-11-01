<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;


// API pour une fonction generique d'autorisation :
// $qui est : vide (on prend alors auteur_session)
//            un id_auteur (on regarde dans la base)
//            un tableau auteur complet
// $faire est une action ('modifier', 'publier'...)
// $type est un type d'objet ou nom de table ('article')
// $id est l'id de l'objet sur lequel on veut agir
// $opt (inutilise pour le moment) = options sous forme de tableau associatif
// (par exemple pour preciser si l'autorisation concerne tel ou tel champ)
//
// Seul le premier argument est obligatoire
define ('_DEBUG_AUTORISER', false);
function autoriser($faire, $type='', $id=0, $qui = NULL, $opt = NULL) {
	static $restreint = array();

	// Qui ? auteur_session ?
	if (!is_array($qui)) {
		if (is_int($qui)) {
			$qui = spip_fetch_array(spip_query(
			"SELECT * FROM spip_auteurs WHERE id_auteur=".$qui));
		} else {
			$qui = $GLOBALS['auteur_session'];
		}
	}

	// Admins restreints, les verifier ici (pas generique mais...)
	// Par convention $restreint est un array des rubriques autorisees
	// (y compris leurs sous-rubriques), ou 0 si admin complet
	if (is_array($qui)
	AND $qui['statut'] == '0minirezo'
	AND !isset($qui['rubriques'])) {
		if (!isset($restreint[$qui['id_auteur']])) {
			include_spip('inc/auth'); # pour auth_rubrique
			$restreint[$qui['id_auteur']] = auth_rubrique($qui['id_auteur'], $qui['statut']);
		}
		$qui['restreint'] = $restreint[$qui['id_auteur']];
	}

	if (_DEBUG_AUTORISER) spip_log("autoriser $faire $type $id ?");

	// Chercher une fonction d'autorisation explicite
	if (
	// 1. Sous la forme "autoriser_type_faire"
	// si $type est vide, charge directement autoriser_faire
	   ($f = charger_fonction($faire,"autoriser/$type"))

	// 2. Sous la forme "autoriser_type"
	// ne pas tester si $type est vide
	OR ($type AND $f = charger_fonction($type,"autoriser"))

	// 3. Sous la forme "autoriser_faire"
	// ne pas tester si $type est vide, deja teste en 1.
	OR ($type AND $f = charger_fonction($faire,'autoriser'))

	// 4. Sinon autorisation generique
	OR ($f = charger_fonction('defaut','autoriser'))
	)
		$a = $f($faire,$type,intval($id),$qui,$opt);

	if (_DEBUG_AUTORISER) spip_log("$f($faire,$type,$id): ".($a?'OK':'niet'));

	return $a;
}

// Autorisation par defaut : les admins complets OK, les autres non
function autoriser_defaut_dist($faire, $type, $id, $qui, $opt) {
	return
		$qui['statut'] == '0minirezo'
		AND !$qui['restreint'];
}

// Autoriser a publier dans la rubrique $id
function autoriser_rubrique_publier_dans_dist($faire, $type, $id, $qui, $opt) {
	return
		($qui['statut'] == '0minirezo')
		AND (!$qui['restreint']
			? true
			: in_array($id, $qui['restreint'])
		);
}

// Autoriser a modifier la rubrique $id
// = publier_dans rubrique $id
function autoriser_rubrique_modifier_dist($faire, $type, $id, $qui, $opt) {
	return
		autoriser('publier_dans', 'rubrique', $id, $qui, $opt);
}

// Autoriser a modifier la breve $id
// = admins & redac si la breve n'est pas publiee
// = admins de rubrique parente si publiee
function autoriser_breve_modifier_dist($faire, $type, $id, $qui, $opt) {
	$s = spip_query(
	"SELECT id_rubrique,statut FROM spip_breves WHERE id_breve="._q($id));
	$r = spip_fetch_array($s);
	return
		($r['statut'] == 'publie')
			? autoriser('publier_dans', 'rubrique', $r['id_rubrique'], $qui, $opt)
			: in_array($qui['statut'], array('0minirezo', '1comite'));
}

// Autoriser a modifier l'article $id
// = publier_dans rubrique parente
// = ou statut 'prop,prepa' et $qui est auteur
function autoriser_article_modifier_dist($faire, $type, $id, $qui, $opt) {
	$s = spip_query(
	"SELECT id_rubrique,statut FROM spip_articles WHERE id_article="._q($id));
	$r = spip_fetch_array($s);
	return
		autoriser('publier_dans', 'rubrique', $r['id_rubrique'], $qui, $opt)
		OR (
			in_array($qui['statut'], array('0minirezo', '1comite'))
			AND in_array($r['statut'], array('prop','prepa', 'poubelle'))
			AND spip_num_rows(auteurs_article($id, "id_auteur=".$qui['id_auteur']))
		);
}

// Lire les stats ?
// = tous les admins
function autoriser_stats_voir_dist($faire, $type, $id, $qui, $opt) {
	return
		$qui['statut'] == '0minirezo';
}


// Voir un objet
function autoriser_voir_dist($faire, $type, $id, $qui, $opt) {
	if (
		($qui['statut'] == '0minirezo')
		OR ($type != 'article')
	)
		return true;

	// un article 'prepa' ou 'poubelle' dont on n'est pas auteur : interdit
	$s = spip_query(
	"SELECT statut FROM spip_articles WHERE id_article="._q($id));
	$r = spip_fetch_array($s);
		return in_array($r['statut'], array('prop', 'publie'));
}

// Voir les revisions ?
// = voir l'objet
function autoriser_revisions_voir_dist($faire, $type, $id, $qui, $opt) {
	return
		autoriser('voir', $type, $id, $qui, $opt);
}

// Moderer le forum ?
// = modifier l'objet correspondant (si forum attache a un objet)
// = droits par defaut sinon (admin complet pour moderation complete)
function autoriser_forum_moderer_dist($faire, $type, $id, $qui, $opt) {
	return
		autoriser('modifier', $type, $id, $qui, $opt);
}

// Moderer la petition ?
// = modifier l'article correspondant
// = droits par defaut sinon (admin complet pour moderation de tout)
function autoriser_petition_moderer_dist($faire, $type, $id, $qui, $opt) {
	return
		autoriser('modifier', $type, $id, $qui, $opt);
}


?>
