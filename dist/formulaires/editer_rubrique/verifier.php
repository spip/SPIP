<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/actions');
include_spip('inc/editer');

function formulaires_editer_rubrique_verifier_dist($id_rubrique='new', $id_parent=0, $lier_trad=0, $retour='', $config_fonc='rubriques_edit_config', $row=array(), $hidden=''){

	$erreurs = formulaires_editer_objet_verifier('rubrique',$id_rubrique,array('titre'));
	return $erreurs;
}

?>