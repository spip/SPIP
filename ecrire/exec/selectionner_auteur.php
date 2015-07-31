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

# afficher un mini-navigateur de rubriques

// http://doc.spip.org/@exec_selectionner_auteur_dist
function exec_selectionner_auteur_dist()
{
  	$id = intval(_request('id_article'));

	$selectionner_auteur = charger_fonction('selectionner_auteur', 'inc');
	ajax_retour($selectionner_auteur($id));
}
?>
