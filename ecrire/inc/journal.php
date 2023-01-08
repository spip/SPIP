<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/*
 * Consigner une phrase dans le journal de bord du site
 * Cette API travaille a minima, mais un plugin pourra stocker
 * ces journaux en base et fournir des outils d'affichage, de selection etc
 *
 * @param string $journal
 * @param array $opt
 */
function inc_journal_dist($phrase, $opt = []) {
	if (!strlen($phrase)) {
		return;
	}
	if ($opt) {
		$phrase .= ' :: ' . str_replace("\n", ' ', join(', ', $opt));
	}
	spip_log($phrase, 'journal');
}
