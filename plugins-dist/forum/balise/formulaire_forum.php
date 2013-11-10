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
 * #FORMULAIRE_FORUM seul calcule (objet, id_objet) depuis la boucle parente
 * #FORMULAIRE_FORUM{#SELF} pour forcer l'url de retour
 * #FORMULAIRE_FORUM{#SELF, article, 3} pour forcer l'objet et son identifiant
 * http://doc.spip.org/@balise_FORMULAIRE_FORUM
 *
 * @param Object $p
 * @return Object
 */
function balise_FORMULAIRE_FORUM ($p) {
	/**
	 * On recupere $objet et $id_objet depuis une boucle englobante si possible
	 * Sinon, on essaie aussi de recuperer des id_xx dans l'URL qui pourraient indiquer
	 * sur quoi le formulaire porte.
	 * Enfin, on pourra aussi forcer objet et id_objet depuis l'appel du formulaire
	 */

	$i_boucle  = $p->nom_boucle ? $p->nom_boucle : $p->id_boucle;
	$_id_objet = $p->boucles[$i_boucle]->primary;
	$_type     = $p->boucles[$i_boucle]->id_table;

	/**
	 * On essaye de trouver les forums en fonction de l'environnement
	 * pour cela, on recupere l'ensemble des id_xxx possibles dans l'env
	 */
	$ids = forum_get_objets_depuis_env();
	$ids = array_values($ids);

	$obtenir = array(
		$_id_objet,
		'id_forum',
		'ajouter_mot',
		'ajouter_groupe',
		'afficher_texte'
	);
	
	if ($ids) {
		$obtenir = array_merge($obtenir, $ids);
	}

	$p = calculer_balise_dynamique($p,'FORMULAIRE_FORUM', $obtenir,
		array("'$_type'", count($ids))
	);

	// Ajouter le code d'invalideur specifique aux forums
	include_spip('inc/invalideur');
	if ($i = charger_fonction('code_invalideur_forums', '', true)) {
		$p->code = $i($p, $p->code);
	}

	return $p;
}

/**
 * Chercher l'objet/id_objet et la configuration du forum
 *
 * http://doc.spip.org/@balise_FORMULAIRE_FORUM_stat
 *
 * @param array $args
 * @param array $context_compil
 * @return array|bool
 */
function balise_FORMULAIRE_FORUM_stat($args, $context_compil) {
	

	// un arg peut contenir l'url sur lequel faire le retour
	// exemple dans un squelette article.html : [(#FORMULAIRE_FORUM{#SELF})]
	// recuperer les donnees du forum auquel on repond.
	// deux autres a la suite pour forcer objet et id_objet
	// [(#FORMULAIRE_FORUM{#SELF, article, 8})]
	//
	// $args = (obtenir) + (ids) + (url, objet, id_objet)
	$ido = array_shift($args);
	$id_forum = intval(array_shift($args));
	$ajouter_mot = array_shift($args);
	$ajouter_groupe = array_shift($args);
	$afficher_texte = array_shift($args);

	$r = balise_forum_retrouve_objet($ido,$id_forum,$args,$context_compil);
	if (!$r)
		return false;

	list($objet, $id_objet, $retour) = $r;

	// on verifie ici si on a le droit de poster sur ce forum
	// doublonne le test dans le formulaire, mais permet d'utiliser la balise
	// pour conditionner l'affichage d'un titre le precedant
	// (ie compatibilite)
	$accepter_forum = controler_forum($objet, $id_objet);
	if ($accepter_forum == 'non')
		return false;

	return
		array($objet,
		$id_objet, $id_forum, $ajouter_mot, $ajouter_groupe, $afficher_texte, $retour);
}

/**
 * @param int $ido
 * @param int $id_forum
 * @param array $args
 * @param array $context_compil
 * @param bool $objet_obligatoire
 * @return array|bool
 */
function balise_forum_retrouve_objet($ido,$id_forum,$args,$context_compil, $objet_obligatoire = true){
	$_objet     = $context_compil[5]; // type le la boucle deja calcule
	$nb_ids_env = $context_compil[6]; // nombre d'elements id_xx recuperes
	$nb         = $nb_ids_env;
	$url        = isset($args[$nb]) ? $args[$nb] : '';
	$objet      = isset($args[++$nb]) ? $args[$nb] : '';
	$id_objet   = isset($args[++$nb]) ? $args[$nb] : 0;

	// pas d'objet force ? on prend le type de boucle calcule
	if (!$objet) {
		$objet = $_objet;
		$id_objet = intval($ido);
	} else {
		$id_objet = intval($id_objet);
	}
	unset($_objet, $ido);

	$objet = objet_type($objet);

	// on tente de prendre l'objet issu de l'environnement si un n'a pas pu etre calcule
	if (!$objet) {
		$objets = forum_get_objets_depuis_env();
		$ids = array(); $i = 0;
		foreach ($objets as $o => $ido) {
			if ($id = $args[$i]) {
				$ids[$o] = $id;
			}
			$i++;
		}
		if (count($ids)>1) {
			if (isset($ids['rubrique'])) {
				unset($ids['rubrique']);
			}
		}
		if (count($ids) == 1) {
			$objet = key($ids);
			$id_objet = array_shift($ids);
		}
	}
	unset($i);

	// et si on n'a toujours pas ce qu'on souhaite, on tente de le trouver dans un forum existant...
	if (($objet=='forum' OR !$id_objet) and $id_forum){
		if ($objet = Sql::fetsel(array('id_objet', 'objet'), 'spip_forum', 'id_forum=' . intval($id_forum))) {
			$id_objet = $objet['id_objet'];
			$objet = $objet['objet'];
		} else {
			if ($objet_obligatoire)
				return false;
		}
	}
	// vraiment la... faut pas exagerer !
	if ($objet_obligatoire AND !$id_objet) {
		return false;
	}

	return array($objet,$id_objet,$url);
}
?>
