<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// Comme l'emplacement du squelette est calcule (par l'argument de la balise)
// on ne peut rien dire sur l'existence du squelette lors de la compil
// On pourrait toutefois traiter le cas de l'argument qui est une constante.

function balise_FORMULAIRE_CONFIGURER_PLUGIN_dist($p) {

	return calculer_balise_dynamique($p, $p->nom_champ, array());
}

// A l'execution on dispose du nom du squelette, on verifie qu'il existe.
// Pour le calcul du contexte, c'est comme la balise #FORMULAIRE_.

function balise_FORMULAIRE_CONFIGURER_PLUGIN_dyn($form) {

	include_spip("balise/formulaire_");
	if (!existe_formulaire($form)) return '';
	return array('formulaires/' . $form,
		     3600, 
		     balise_FORMULAIRE__contexte("configurer_plugin", func_get_args()));
}

?>
