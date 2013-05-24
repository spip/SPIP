<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2012                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('base/abstract_sql');
include_spip('inc/filtres');

// Balise independante du contexte

/**
 * http://doc.spip.org/@balise_FORMULAIRE_INSCRIPTION
 *
 * @param object $p
 * @return mixed
 */
function balise_FORMULAIRE_INSCRIPTION ($p) {

	return calculer_balise_dynamique($p, 'FORMULAIRE_INSCRIPTION', array());
}

/**
 * http://doc.spip.org/@balise_FORMULAIRE_INSCRIPTION_stat
 *
 * [(#FORMULAIRE_INSCRIPTION{nom_inscription, #ID_RUBRIQUE})]
 *
 * @param array $args
 *   args[0] un statut d'auteur (redacteur par defaut)
 *   args[1] indique la rubrique eventuelle de proposition
 * @param array $context_compil
 * @return array|string
 */
function balise_FORMULAIRE_INSCRIPTION_stat($args, $context_compil) {
	list($mode, $id) = $args;

	if (!$mode){
		if ($GLOBALS['meta']["accepter_inscriptions"] == "oui")
			$mode = "1comite";
		elseif (($GLOBALS['meta']['accepter_visiteurs'] == 'oui' OR $GLOBALS['meta']['forums_publics'] == 'abo')){
			$mode = "6forum";
		}
	}

	include_spip('inc/autoriser');
	return autoriser('inscrireauteur', $mode, $id) ? array($mode, $id) : '';
}

?>
