<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

include_spip('inc/acces');
include_spip('inc/texte');
include_spip('inc/forum');

/*******************************/
/* GESTION DU FORMULAIRE FORUM */
/*******************************/

/**
 * Contexte du formulaire
 * Mots-cles dans les forums :
 * Si la variable de personnalisation $afficher_groupe[] est definie
 * dans le fichier d'appel, et si la table de reference est OK, proposer
 * la liste des mots-cles
 * #FORMULAIRE_FORUM_PRIVE seul calcule (objet, id_objet) depuis la boucle parente
 * #FORMULAIRE_FORUM_PRIVE{#SELF} pour forcer l'url de retour
 * #FORMULAIRE_FORUM_PRIVE{#SELF, article, 3} pour forcer l'objet et son identifiant
 * http://doc.spip.org/@balise_FORMULAIRE_FORUM_PRIVE
 *
 * @param Object $p
 * @return Object
 */
function balise_FORMULAIRE_FORUM_PRIVE ($p) {

	/**
	 * On recupere $objet et $id_objet depuis une boucle englobante si possible
	 * Sinon, on essaie aussi de recuperer des id_xx dans l'URL qui pourraient indiquer
	 * sur quoi le formulaire porte.
	 * Enfin, on pourra aussi forcer objet et id_objet depuis l'appel du formulaire
	 */
	$i_boucle  = $p->nom_boucle ? $p->nom_boucle : $p->id_boucle;
	if (isset($p->boucles[$i_boucle])) {
		$_id_objet = $p->boucles[$i_boucle]->primary;
		$_type     = $p->boucles[$i_boucle]->id_table;
	} else {
		$_id_objet = $_type = '';
	}

	/**
	 * On essaye de trouver les forums en fonction de l'environnement
	 * pour cela, on recupere l'ensemble des id_xxx possibles dans l'env
	 */
	$ids = forum_get_objets_depuis_env();
	$ids = array_values($ids);

	$obtenir = array(
		$_id_objet,
		'id_forum',
		'afficher_texte',
		'statut',
	);


	if ($ids) {
		$obtenir = array_merge($obtenir, $ids);
	}

	$p = calculer_balise_dynamique($p,'FORMULAIRE_FORUM_PRIVE', $obtenir,
		array("'$_type'", count($ids))
	);

	return $p;
}

/**
 * Chercher l'objet/id_objet et la configuration du forum
 *
 * http://doc.spip.org/@balise_FORMULAIRE_FORUM_PRIVE_stat
 *
 * @param array $args
 * @param array $context_compil
 * @return array|bool
 */
function balise_FORMULAIRE_FORUM_PRIVE_stat($args, $context_compil) {
	// un arg peut contenir l'url sur lequel faire le retour
	// exemple dans un squelette article.html : [(#FORMULAIRE_FORUM_PRIVE{#SELF})]
	// recuperer les donnees du forum auquel on repond.
	// deux autres a la suite pour forcer objet et id_objet
	// [(#FORMULAIRE_FORUM_PRIVE{#SELF, article, 8})]
	//

	// $args = (obtenir) + (ids) + (url, objet, id_objet)
	$ido = array_shift($args);
	$id_forum = intval(array_shift($args));
	$afficher_texte = array_shift($args);
	$statut = array_shift($args);

	include_spip('balise/formulaire_forum');
	// si statut privrac ou privadm, pas besoin d'objet !
	$r = balise_forum_retrouve_objet($ido,$id_forum,$args,$context_compil,!in_array($statut,array('privrac','privadm')));
	if (!$r)
		return false;

	list($objet, $id_objet, $retour) = $r;

	return
		array($objet, $id_objet, $id_forum, $afficher_texte, $statut, $retour);
}

?>
