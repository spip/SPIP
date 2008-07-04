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

function formulaires_editer_breve_charger_dist($id_breve='new', $id_rubrique=0, $retour='', $lier_trad=0, $config_fonc='breves_edit_config', $row=array(), $hidden=''){
	return formulaires_editer_objet_charger('breve',$id_breve,$id_rubrique,$lier_trad,$retour,$config_fonc,$row,$hidden);
}

// Choix par defaut des options de presentation
function breves_edit_config($row)
{
	global $spip_ecran, $spip_lang, $spip_display;

	$config = $GLOBALS['meta'];
	$config['lignes'] = ($spip_ecran == "large")? 8 : 5;
	$config['afficher_barre'] = $spip_display != 4;
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