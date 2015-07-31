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

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/precharger_objet');


// Recupere les donnees d'un article pour composer un formulaire d'edition
// id_article = numero d'article existant
// id_rubrique = ou veut-on l'installer (pas obligatoire)
// lier_trad = l'associer a l'article numero $lier_trad
// new=oui = article a creer si on valide le formulaire
// http://doc.spip.org/@inc_article_select_dist
function inc_precharger_article_dist($id_article, $id_rubrique=0, $lier_trad=0) {
	return precharger_objet('article', $id_article, $id_rubrique, $lier_trad, 'titre');
}


//
// Si un article est demande en creation (new=oui) avec un lien de trad,
// on initialise les donnees de maniere specifique
//
// (fonction facultative si pas de changement dans les traitements)
function inc_precharger_traduction_article_dist($id_article, $id_rubrique=0, $lier_trad=0) {
	return precharger_traduction_objet('article', $id_article, $id_rubrique, $lier_trad, 'titre');
}



?>
