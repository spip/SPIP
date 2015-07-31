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

include_spip('inc/presentation');

// http://doc.spip.org/@exec_puce_statut_article_dist
function exec_meme_rubrique_dist()
{
	$id = intval(_request('id'));
	$type = _request('type');
	$date = _request('date');

        if (($GLOBALS['auteur_session']['statut'] != '0minirezo')
        OR (!acces_rubrique($id))
	OR (!preg_match('/^[\w_-]+$/',$date))
        OR (!preg_match('/^[\w_-]+$/',$type))) {
                include_spip('inc/minipres');
                echo minipres();
                exit;
        }

	// on connait pas le vrai 2e arg mais c'est pas dramatique
	$res = meme_rubrique($id, 0, $type, $date, 30, true);
	ajax_retour($res);
}
?>