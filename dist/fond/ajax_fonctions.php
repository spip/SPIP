<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined("_ECRIRE_INC_VERSION")) return;

/* filtre necessaire pour que la pagination ajax ne soit pas plantee
 * si on charge la page &debut_x=1 : car alors en cliquant sur l'item 0,
 * le debut_x=0 n'existe pas, et on resterait sur 1
 */
function supprimer_debuts($env) {
	$env = unserialize($env);
	foreach ($env as $k => $v)
		if (strpos($k,'debut_') === 0)
			unset($env[$k]);
	return serialize($env);
}

?>