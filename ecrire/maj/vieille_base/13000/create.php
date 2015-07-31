<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;
include_spip('maj/vieille_base/13000/serial');
include_spip('maj/vieille_base/13000/auxiliaires');
include_spip('maj/vieille_base/13000/typedoc');
include_spip('base/create');

// http://doc.spip.org/@creer_base
function maj_vieille_base_13000_create($serveur='') {
	global $tables_principales, $tables_auxiliaires;

	// Note: les mises a jour reexecutent ce code pour s'assurer
	// de la conformite de la base
	// pas de panique sur  "already exists" et "duplicate entry" donc.

	foreach($tables_principales as $k => $v)
		creer_ou_upgrader_table($k,$v,true,false,$serveur);

	foreach($tables_auxiliaires as $k => $v)
		creer_ou_upgrader_table($k,$v,false,false,$serveur);
}


?>
