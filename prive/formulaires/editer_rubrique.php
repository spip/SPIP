<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/actions');
include_spip('inc/editer');

// http://doc.spip.org/@inc_editer_article_dist
function formulaires_editer_rubrique_charger_dist($id_rubrique='new', $id_parent=0, $retour='', $lier_trad=0, $config_fonc='rubriques_edit_config', $row=array(), $hidden=''){
	return formulaires_editer_objet_charger('rubrique',$id_rubrique,$id_parent,$lier_trad,$retour,$config_fonc,$row,$hidden);
}

function rubriques_edit_config($row)
{
	global $spip_lang;

	$config = $GLOBALS['meta'];
	$config['lignes'] = 8;
	$config['langue'] = $spip_lang;

	$config['restreint'] = (!$GLOBALS['connect_toutes_rubriques']);
	return $config;
}

/**
 * Identifier le formulaire en faisant abstraction des parametres qui
 * ne representent pas l'objet edite
 */
function formulaires_editer_rubrique_identifier_dist($id_rubrique='new', $id_parent=0, $retour='', $lier_trad=0, $config_fonc='rubriques_edit_config', $row=array(), $hidden=''){
	return serialize(array(intval($id_rubrique),$lier_trad));
}

function formulaires_editer_rubrique_verifier_dist($id_rubrique='new', $id_parent=0, $retour='', $lier_trad=0, $config_fonc='rubriques_edit_config', $row=array(), $hidden=''){
	// auto-renseigner le titre si il n'existe pas
	titre_automatique('titre',array('descriptif','texte'));
	// on ne demande pas le titre obligatoire : il sera rempli a la volee dans editer_rubrique si vide
	$erreurs = formulaires_editer_objet_verifier('rubrique',$id_rubrique,array());
	return $erreurs;
}

function formulaires_editer_rubrique_traiter_dist($id_rubrique='new', $id_parent=0, $retour='', $lier_trad=0, $config_fonc='rubriques_edit_config', $row=array(), $hidden=''){
	return formulaires_editer_objet_traiter('rubrique',$id_rubrique,$id_parent,$lier_trad,$retour,$config_fonc,$row,$hidden);
}

?>
