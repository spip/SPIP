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

# afficher un mini-navigateur de rubriques

function inc_ajax_selectionner_dist()
{
	global $id, $exclus, $rac;
	$id = intval($id);
	$exclus = intval($exclus);

	include_spip('inc/texte');
	include_spip('inc/mini_nav');
	return mini_nav ($id, "choix_parent", "this.form.id_rubrique.value=::sel::;this.form.titreparent.value='::sel2::';findObj('selection_rubrique').style.display='none';", $exclus, $rac);

}
?>
