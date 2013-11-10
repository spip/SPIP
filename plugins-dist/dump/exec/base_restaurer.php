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

/**
 * Finir une restauration interrompue par logout
 */
function exec_base_restaurer_dist(){

	include_spip('base/dump');
	$status_file = base_dump_meta_name(0)."_restauration";
	$restaurer = charger_fonction("restaurer","action");
	$restaurer($status_file);

}

?>