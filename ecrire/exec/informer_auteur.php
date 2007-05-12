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

include_spip('inc/actions');

# Les informations d'une rubrique selectionnee dans le mini navigateur

// http://doc.spip.org/@exec_informer_auteur_dist
function exec_informer_auteur_dist()
{
	$id = intval(_request('id'));

	$informer_auteur = charger_fonction('informer_auteur', 'inc');
	ajax_retour($informer_auteur($id));
}

?>
