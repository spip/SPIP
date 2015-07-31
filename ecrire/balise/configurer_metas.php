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

// Une balise qui prend en argument un squelette suppose contenir un FORM
// et gere ses saises automatiquement dans une table SQL a 2 colonnes
// nom / valeur

// Comme l'emplacement du squelette est calcule (par l'argument de la balise)
// on ne peut rien dire sur l'existence du squelette lors de la compil
// On pourrait toutefois traiter le cas de l'argument qui est une constante.

function balise_CONFIGURER_METAS_dist($p) {

	return calculer_balise_dynamique($p, $p->nom_champ, array());
}

// A l'execution on dispose du nom du squelette, on verifie qu'il existe.
// Pour le calcul du contexte, c'est comme la balise #FORMULAIRE_.
// y compris le controle au retour pour faire apparaitre le message d'erreur.

function balise_CONFIGURER_METAS_dyn($form) {

	include_spip('balise/formulaire_');
	if (!existe_formulaire($form)) return '';
	$args = func_get_args();
	$contexte = balise_FORMULAIRE__contexte('configurer_metas', $args);
	if (!is_array($contexte)) return $contexte;
	return array('formulaires/' . $form, 3600, $contexte);
}

?>
