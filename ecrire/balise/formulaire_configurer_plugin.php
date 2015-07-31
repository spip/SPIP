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

// Comme l'emplacement du squelette est calculable
// (nom du plugin en premier argument, nom eventuel du skel en 2e)
// on ne peut rien dire sur l'existence du squelette lors de la compil

function balise_FORMULAIRE_CONFIGURER_PLUGIN_dist($p) {

	return calculer_balise_dynamique($p, $p->nom_champ, array());
}

// A l'execution on dispose des arguments, en particulier le premier
// le nom du repertoire ou doit se trouver le squelette.
// Si 2e arg n'est pas la pour donner son nom, c'est "configurer_$prefixe".
// Pour le calcul du contexte, c'est comme la balise #FORMULAIRE_.

function balise_FORMULAIRE_CONFIGURER_PLUGIN_dyn($plugin='', $form='') {

	$get_infos = charger_fonction('get_infos','plugins');
	$infos = !$plugin ? array() : $get_infos($plugin);
	if (!isset($infos['prefix'])) return ''; // plugin in{connu|actif}
	if (!$form) $form = 'configurer_' . $infos['prefix'];
	include_spip("balise/formulaire_");
	return array('formulaires/' . $form,
		     3600, 
		     balise_FORMULAIRE__contexte("configurer_plugin", func_get_args()));
}

?>
