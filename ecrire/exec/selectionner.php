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

function exec_selectionner_dist()
{
	$id = intval(_request('id'));
	$exclus = intval(_request('exclus'));
	$type = _request('type');
	$rac = _request('racine');

	include_spip('inc/texte');
	$selectionner = charger_fonction('selectionner', 'inc');
	ajax_retour($selectionner($id, "choix_parent", "this.form.id_rubrique.value=::sel::;this.form.titreparent.value='::sel2::';findObj_forcer('selection_rubrique').style.display='none';", $exclus, $rac, $type!='breve'));

}
?>
