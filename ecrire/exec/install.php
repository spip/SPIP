<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/minipres');
include_spip('inc/install');
include_spip('inc/autoriser');

define("_ECRIRE_INSTALL", "1");
define('_FILE_TMP', '_install');

// http://doc.spip.org/@exec_install_dist
function exec_install_dist()
{
	$etape = _request('etape');
	if ((_FILE_CONNECT AND (!in_array($etape, array('chmod', 'sup1', 'sup2')))) OR !autoriser('configurer')) {
  // L'etape chmod peut etre reexecutee n'importe quand apres l'install,
  // pour verification des chmod. Sinon, install deja faite => refus.
		echo minipres();
	} else {
		include_spip('base/create');
		$fonc = charger_fonction("etape_$etape", 'install');
		$fonc();
	}
}

?>
