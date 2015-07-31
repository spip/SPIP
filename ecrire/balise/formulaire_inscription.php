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

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('base/abstract_sql');
include_spip('inc/filtres');

// Balise independante du contexte

// http://doc.spip.org/@balise_FORMULAIRE_INSCRIPTION
function balise_FORMULAIRE_INSCRIPTION ($p) {

	return calculer_balise_dynamique($p, 'FORMULAIRE_INSCRIPTION', array());
}

// args[0] un statut d'auteur (redacteur par defaut)
// args[1] indique le focus eventuel
// args[2] indique la rubrique eventuelle de proposition
// [(#FORMULAIRE_INSCRIPTION{nom_inscription, #ID_RUBRIQUE})]

// http://doc.spip.org/@balise_FORMULAIRE_INSCRIPTION_stat
function balise_FORMULAIRE_INSCRIPTION_stat($args, $context_compil) {
	list($mode, $focus, $id) = $args;

	include_spip('inc/autoriser');
	if ($mode)
		return autoriser('inscrireauteur', $mode, $id) ? array($mode, $focus, $id) : '';
	elseif (
	     autoriser('inscrireauteur', $mode = "1comite", $id)
	  OR autoriser('inscrireauteur', $mode = "6forum", $id))
		return array($mode, $focus, $id);

	return '';
}

?>
