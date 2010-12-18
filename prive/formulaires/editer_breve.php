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

include_spip('inc/actions');
include_spip('inc/editer');

function formulaires_editer_breve_charger_dist($id_breve='new', $id_rubrique=0, $retour='', $lier_trad=0, $config_fonc='breves_edit_config', $row=array(), $hidden=''){
	$valeurs = formulaires_editer_objet_charger('breve',$id_breve,$id_rubrique,$lier_trad,$retour,$config_fonc,$row,$hidden);
	// un bug a permis a un moment que des breves soient dans des sous rubriques
	// lorsque ce cas se presente, il faut relocaliser la breve dans son secteur, plutot que n'importe ou
	if ($valeurs['id_rubrique'])
		$valeurs['id_parent'] = sql_getfetsel('id_secteur','spip_rubriques','id_rubrique='.intval($valeurs['id_rubrique']));
	// et on enleve id_rubrique des valeurs saisies (c'est id_parent)
	unset($valeurs['id_rubrique']);
	return $valeurs;
}

/**
 * Identifier le formulaire en faisant abstraction des parametres qui
 * ne representent pas l'objet edite
 */
function formulaires_editer_breve_identifier_dist($id_breve='new', $id_rubrique=0, $retour='', $lier_trad=0, $config_fonc='breves_edit_config', $row=array(), $hidden=''){
	return serialize(array($id_breve,$lier_trad,$row));
}


// Choix par defaut des options de presentation
function breves_edit_config($row)
{
	global $spip_ecran, $spip_lang, $spip_display;

	$config = $GLOBALS['meta'];
	$config['lignes'] = ($spip_ecran == "large")? 8 : 5;
	$config['langue'] = $spip_lang;

	$config['restreint'] = ($row['statut'] == 'publie');
	return $config;
}

function formulaires_editer_breve_verifier_dist($id_breve='new', $id_rubrique=0, $retour='', $lier_trad=0, $config_fonc='breves_edit_config', $row=array(), $hidden=''){
	
	$erreurs = formulaires_editer_objet_verifier('breve',$id_breve,array('titre'));
	return $erreurs;
}

// http://doc.spip.org/@inc_editer_article_dist
function formulaires_editer_breve_traiter_dist($id_breve='new', $id_rubrique=0, $retour='', $lier_trad=0, $config_fonc='breves_edit_config', $row=array(), $hidden=''){
	return formulaires_editer_objet_traiter('breve',$id_breve,$id_rubrique,$lier_trad,$retour,$config_fonc,$row,$hidden);
}

?>
