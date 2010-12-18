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

// http://doc.spip.org/@exec_editer_auteurs_dist
function exec_editer_auteurs_dist()
{
	$type = _request('type');
	if (!preg_match(',^[a-z]+$,',$type)) // securite et a defaut on assure le fonctionnement pour articles
		$type = 'article';

	$id = intval(_request("id_$type"));

	if (! autoriser('modifier',$type,$id)) {
		include_spip('inc/minipres');
		echo minipres();
	} else {
	$script = _request('script');
	$titre = ($titre=_request('titre'))?urldecode($titre):$titre;

	$editer_auteurs = charger_fonction('editer_auteurs', 'inc');
	include_spip('inc/actions');
	ajax_retour($editer_auteurs($type, $id, 'ajax', _request('cherche_auteur'), _request('ids'),$titre,$script));
	}
}
?>
