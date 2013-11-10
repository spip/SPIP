<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/actions');
include_spip('inc/editer');

function formulaires_regler_moderation_site_charger_dist($id_syndic,$retour=''){
	$valeurs = formulaires_editer_objet_charger('site',$id_syndic,0,0,$retour,'');
	# pour recuperer le logo issu d'analyse auto
	foreach(array('moderation','miroir','oubli','resume') as $k)
		if (!$valeurs[$k])
			$valeurs[$k] = 'non';

	return $valeurs;
}

/**
 * Identifier le formulaire en faisant abstraction des parametres qui
 * ne representent pas l'objet edite
 */
function formulaires_regler_moderation_site_identifier_dist($id_syndic,$retour=''){
	return serialize(array($id_syndic));
}


function formulaires_regler_moderation_site_verifier_dist($id_syndic,$retour=''){
	$erreurs = array();
	
	foreach(array('moderation','miroir','oubli','resume') as $k){
		if (!_request($k) OR !in_array(_request($k),array('oui','non')))
			set_request($k,'non');
	}

	return $erreurs;
}

function formulaires_regler_moderation_site_traiter_dist($id_syndic,$retour=''){
	$res = formulaires_editer_objet_traiter('site',$id_syndic,0,0,$retour,'');
  $res['editable'] = true;
  if (!isset($res['message_erreur']))
	  $res['message_ok'] = _T('config_info_enregistree');
  return $res;
}


?>
