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


/*
 * Consigner une phrase dans le journal de bord du site
 * Cette API travaille a minima, mais un plugin pourra stocker
 * ces journaux en base et fournir des outils d'affichage, de selection etc
 *
 * @param string $journal
 * @param array $opt
 */
function inc_journal_dist($phrase, $opt = array()) {
	if (!strlen($phrase))
		return;
	if ($opt)
		$phrase .= " :: ".str_replace("\n", ' ', join(', ',$opt));
	spip_log($phrase, 'journal');
}

?>
