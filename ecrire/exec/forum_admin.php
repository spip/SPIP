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

// http://doc.spip.org/@exec_forum_admin_dist
function exec_forum_admin_dist() {

  if (!autoriser('forum_admin')) {
	include_spip('inc/minipres');
	echo minipres();
  } else {
	include_spip('exec/forum');
	forum_affiche(intval(_request('debut')), true);
  }
}
?>
