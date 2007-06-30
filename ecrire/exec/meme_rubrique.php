<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// http://doc.spip.org/@exec_meme_rubrique_dist
function exec_meme_rubrique_dist()
{
	$id = intval(_request('id'));
	$type = _request('type');
	$order = _request('order');

        if ((!autoriser('publierdans','rubrique',$id))
	OR (!preg_match('/^[\w_-]+$/',$order))
	OR (!preg_match('/^[\w_-]+$/',$type))) {
		include_spip('inc/minipres');
                echo minipres();
                exit;
        }
	include_spip('inc/presentation');
	// on connait pas le vrai 2e arg mais c'est pas dramatique
	$res = meme_rubrique($id, 0, $type, $order, NULL, true);
	ajax_retour($res);
}
?>
