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

// http://doc.spip.org/@exec_meme_rubrique_dist
function exec_meme_rubrique_dist()
{
	exec_meme_rubrique_args(intval(_request('id')), _request('type'), _request('order'));
}

// http://doc.spip.org/@exec_meme_rubrique_args
function exec_meme_rubrique_args($id, $type, $order)
{
        if ((!autoriser('publierdans','rubrique',$id))
	OR (!preg_match('/^[\w_-]*$/',$order))) {
		include_spip('inc/minipres');
                echo minipres();
        } else {
		$meme_rubrique = charger_fonction('meme_rubrique', 'inc');
	// on connait pas le vrai 2e arg mais c'est pas dramatique
		$res = $meme_rubrique($id, 0, $type, $order, NULL, true);
		include_spip('inc/actions');
		ajax_retour($res);
	}
}
?>
