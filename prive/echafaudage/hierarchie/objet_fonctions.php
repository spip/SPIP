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

/**
 * Tester le deplacement restreint ou non
 * de l'objet en fonction de son statut
 *
 * @param string $objet
 * @param string $statut
 * @return bool
 */
function deplacement_restreint($objet,$statut) {

	switch ($objet) {
		case 'rubrique':
			return (!$GLOBALS['connect_toutes_rubriques']);
			break;
		case 'article':
		case 'site':
		case 'syndic':
		case 'breve':
			return ($statut=='publie');
			break;
		default :
			return ($statut?$statut=='publie':false);
			break;
	}
	return false;
}

?>
