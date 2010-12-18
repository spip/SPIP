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

// http://doc.spip.org/@exec_copier_local_dist
function exec_copier_local_dist()
{

// oui, ca parait bizarre d'appeler tourner, mais en fait la copie locale est
// etroitement liee a la mise en page des icones 'tourner'

	$var_f = charger_fonction('tourner');
	$var_f();
}

?>
