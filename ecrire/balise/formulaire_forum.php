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

include_spip('inc/acces');
include_spip('inc/texte');
include_spip('inc/forum');

/*******************************/
/* GESTION DU FORMULAIRE FORUM */
/*******************************/

// Contexte du formulaire
// Mots-cles dans les forums :
// Si la variable de personnalisation $afficher_groupe[] est definie
// dans le fichier d'appel, et si la table de reference est OK, proposer
// la liste des mots-cles

// http://doc.spip.org/@balise_FORMULAIRE_FORUM
function balise_FORMULAIRE_FORUM ($p) {

	$p = calculer_balise_dynamique($p,'FORMULAIRE_FORUM', array('id_rubrique', 'id_forum', 'id_article', 'id_breve', 'id_syndic', 'ajouter_mot', 'ajouter_groupe', 'afficher_texte'));

	// Ajouter le code d'invalideur specifique aux forums
	include_spip('inc/invalideur');
	if (function_exists($i = 'code_invalideur_forums'))
		$p->code = $i($p, $p->code);

	return $p;
}

//
// Chercher le titre et la configuration d'un forum
// valeurs possibles : 'pos'teriori, 'pri'ori, 'abo'nnement
// Donner aussi la table de reference pour afficher_groupes[]

// http://doc.spip.org/@balise_FORMULAIRE_FORUM_stat
function balise_FORMULAIRE_FORUM_stat($args, $context_compil) {

	// le denier arg peut contenir l'url sur lequel faire le retour
	// exemple dans un squelette article.html : [(#FORMULAIRE_FORUM{#SELF})]

	// recuperer les donnees du forum auquel on repond.
	list ($idr, $idf, $ida, $idb, $ids, $am, $ag, $af, $url) = $args;
	$idr = intval($idr);
	$idf = intval($idf);
	$ida = intval($ida);
	$idb = intval($idb);
	$ids = intval($ids);

	$type = substr($GLOBALS['meta']["forums_publics"],0,3);

	if ($ida) {
		$titre = sql_fetsel('accepter_forum AS type, titre', 'spip_articles', "statut = 'publie' AND id_article = $ida");
		if ($titre) {
			if ($titre['type']) $type = $titre['type'];
			$table = "articles";
		}
		if ($type == 'non') return false;
	} else {
		if ($type == 'non') return false;
		if ($idb) {
			$titre = sql_fetsel('titre', 'spip_breves', "statut = 'publie' AND id_breve = $idb");
			$table = "breves";
		} else if ($ids) {
			$titre = sql_fetsel('nom_site AS titre', 'spip_syndic', "statut = 'publie' AND id_syndic = $ids");
			$table = "syndic";
		} else if ($idr) {
			$titre = sql_fetsel('titre', 'spip_rubriques', "statut = 'publie' AND id_rubrique = $idr");
			$table = "rubriques";
		}
	}

	if (!$titre) return false; // inexistant ou non public

	if ($idf>0) {
		$titre_m = sql_fetsel('titre', 'spip_forum', "id_forum = $idf");
		if (!$titre_m) return false; // URL fabriquee
		$titre = $titre_m;
	}

	if ($GLOBALS['meta']["mots_cles_forums"] != "oui")
		$table = '';

	$titre = supprimer_numero($titre['titre']);

	// Sur quelle adresse va-t-on "boucler" pour la previsualisation ?
	// si vide : self()
	$script = '';

	return
		array($titre, $table, $type, $script,
		$idr, $idf, $ida, $idb, $ids, $am, $ag, $af, $url);
}

?>
