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

include_spip('inc/presentation');

// http://doc.spip.org/@repercuter_gadgets
function repercuter_gadgets($id_rubrique) {

	if (!_SPIP_AJAX) return '';

	// comme on cache fortement ce menu,
	// son url change en fonction de sa date de modif
	$toutsite = "./?exec=menu_rubriques\\x26date=" .  $GLOBALS['meta']['date_calcul_rubriques'];

	return
	
	 "\ninit_gadgets('$toutsite','','','');\n";

}

?>
