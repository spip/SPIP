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

// http://doc.spip.org/@inc_editer_mot_dist
function formulaires_editer_mot_charger_dist($id_mot='new', $id_groupe=0, $retour='', $ajouter_id_article=0, $table='', $table_id=0, $config_fonc='mots_edit_config', $row=array(), $hidden=''){
	$valeurs = formulaires_editer_objet_charger('mot',$id_mot,$id_groupe,'',$retour,$config_fonc,$row,$hidden);
	$valeurs['table'] = $table;
	return $valeurs;
}

// Choix par defaut des options de presentation
// http://doc.spip.org/@articles_edit_config
function mots_edit_config($row)
{
	global $spip_ecran, $spip_lang, $spip_display;

	$config = $GLOBALS['meta'];
	$config['lignes'] = ($spip_ecran == "large")? 8 : 5;
	$config['afficher_barre'] = $spip_display != 4;
	$config['langue'] = $spip_lang;

	$config['restreint'] = ($row['statut'] == 'publie');
	return $config;
}

function formulaires_editer_mot_verifier_dist($id_mot='new', $id_groupe=0, $retour='', $ajouter_id_article=0, $table='', $table_id=0, $config_fonc='mots_edit_config', $row=array(), $hidden=''){

	$erreurs = formulaires_editer_objet_verifier('mot',$id_mot,array('titre'));
	return $erreurs;
}

// http://doc.spip.org/@inc_editer_mot_dist
function formulaires_editer_mot_traiter_dist($id_mot='new', $id_groupe=0, $retour='', $ajouter_id_article=0, $table='', $table_id=0, $config_fonc='mots_edit_config', $row=array(), $hidden=''){
	$message = '';
	set_request('redirect','');
	$action_editer = charger_fonction("editer_mot",'action');
	list($id_mot,$err) = $action_editer();
	if ($err){
		$message .= $err;
	}
	else {
		if ($ajouter_id_article){
			$id_groupe = intval(_request('id_groupe'));
			ajouter_nouveau_mot($id_groupe, $table, $table_id, $id_mot, $ajouter_id_article);
		}
		if ($retour) {
			include_spip('inc/headers');
			$message .= redirige_formulaire(parametre_url($retour,'id_mot',$id_mot));
		}
	}
	return $message;
}


?>