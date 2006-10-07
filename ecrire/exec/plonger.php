<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;


# afficher les sous-rubriques d'une rubrique (composant du mini-navigateur)

function exec_plonger_dist()
{
	global $id, $exclus, $col, $rac;
	$id = intval($id);
	$exclus = intval($exclus);
	$col = intval($col);

	include_spip('inc/texte');
	include_spip('inc/mini_nav');
	ajax_retour(mini_afficher_rubrique($id, htmlentities($rac), array(), $col, $exclus));
}

?>
