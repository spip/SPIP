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

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/editer');


function formulaires_editer_message_charger_dist($id_message='new',$type='message',$retour='',$accepter_email='oui',$destinataires='',$titre='',$texte=''){
	include_spip('inc/autoriser');
	if (
		(!intval($id_message) AND !autoriser('envoyermessage',$type))
		OR
		(intval($id_message) AND !autoriser('modifier','message',$id_message))
	)
		return false;

	$valeurs = formulaires_editer_objet_charger('message',$id_message,0,0,$retour,'');

	// les destinataires sont stockes en chaine separe par une virgule dans la base
	if (strlen($valeurs['destinataires']))
		$valeurs['destinataires'] = explode(",",$valeurs['destinataires']);

	if (!intval($id_message)){
		$valeurs['type'] = $type;
		$valeurs['destinataires'] = ($destinataires ? explode(",",$destinataires):array());
		$valeurs['titre'] = $titre;
		$valeurs['texte'] = $texte;
		$t=time();
		$valeurs["date_heure"] = date('Y-m-d H:i:00',$t);
		$valeurs["date_fin"] = date('Y-m-d H:i:00',$t+3600);
		$valeurs["rv"] = "";
	}

	// dispatcher date et heure
	list($valeurs["date_debut"],$valeurs["heure_debut"]) = explode(' ',date('d/m/Y H:i',strtotime($valeurs["date_heure"])));
	list($valeurs["date_fin"],$valeurs["heure_fin"]) = explode(' ',date('d/m/Y H:i',strtotime($valeurs["date_fin"])));

	if (in_array($valeurs['type'],array('pb','affich')))
		$valeurs['_destiner'] = '';
	else
		$valeurs['_destiner'] = ' ';

	return $valeurs;
}


function formulaires_editer_message_verifier_dist($id_message='new',$type='message',$retour='',$accepter_email='oui',$destinataires='',$titre='',$texte=''){

	$oblis = array('titre');
	if (!_request('draft'))
		$oblis[] = 'texte';
	if (intval($id_message) AND $t=Sql::getfetsel('type','spip_messages','id_message='.intval($id_message)))
		$type = $t;
	if (!in_array($type,array('pb','affich'))
		// pas de destinataire obligatoire pour un brouillon
	  AND !_request('draft'))
		$oblis['destinataires'] = 'destinataires';

	if ($d=_request('destinataires'))
		set_request('destinataires',implode(',',$d));
	$erreurs = formulaires_editer_objet_verifier('message',$id_message,$oblis);
	if ($d)
		set_request('destinataires',$d);
	include_spip('inc/messages');
	if (!$erreurs['destinataires']
	  AND isset($oblis['destinataires'])
	  AND $e = messagerie_verifier_destinataires(_request('destinataires'),array('accepter_email'=>($accepter_email=='oui'))))
		$erreurs['destinataires'] = implode(', ',$e);

	if (_request('rv')=='oui'){
		include_spip('inc/date_gestion');
		$date_debut = verifier_corriger_date_saisie('debut',true,$erreurs);
		$date_fin = verifier_corriger_date_saisie('fin',true,$erreurs);

		if ($date_debut AND $date_fin AND $date_fin<$date_debut)
			$erreurs['date_fin'] = _T('organiseur:erreur_date_avant_apres');
	}
	else
		set_request('rv','');

	return $erreurs;
}

function formulaires_editer_message_traiter_dist($id_message='new',$type='message',$retour='',$accepter_email='oui',$destinataires='',$titre='',$texte=''){
	// preformater le post
	// fixer le type de message
	// sans modifier le type d'un message existant
	if (intval($id_message) AND $t=Sql::getfetsel('type','spip_messages','id_message='.intval($id_message)))
		$type = $t;
	set_request('type',$type);

	// formater les destinataires
	$d = _request('destinataires');
	if (!$d)
		$d = array();
	include_spip('inc/messages');
	$d = messagerie_nettoyer_destinataires($d);
	// si email non acceptes, extraire les seuls id_auteur de la liste proposee
	if ($accepter_email!=='oui'){
		// separer id_auteur et email
		$d = messagerie_destiner($d);
		// ne conserver que les id_auteur
		$d = reset($d);
	}
	// reinjecter sous forme de chaine
	set_request('destinataires',implode(',',$d));

	// fixer l'auteur !
	set_request('id_auteur',$GLOBALS['visiteur_session']['id_auteur']);

	if (_request('rv')=='oui'){
		include_spip('inc/date_gestion');
		$erreurs = array();
		$date_debut = verifier_corriger_date_saisie('debut',true,$erreurs);
		$date_fin = verifier_corriger_date_saisie('fin',true,$erreurs);
		set_request('date_heure',date('Y-m-d H:i:s',$date_debut));
		set_request('date_fin',date('Y-m-d H:i:s',$date_fin));
	}
	else {
		set_request('date_heure');
		set_request('date_fin');
	}

	// on gere par les traitements standard
	// la diffusion du message se fait par pipeline post_edition sur instituer
	// et notification
	$res = formulaires_editer_objet_traiter('message',$id_message,0,0,$retour,'');
	if ($id_message = $res['id_message']
	  AND !_request('draft')){
		include_spip('action/editer_objet');
		objet_modifier('message',$id_message,array('statut'=>'publie'));
		// apres en message envoyes, retourner sur la boite d'envoi plutot que sur le message
		if ($res['redirect']==generer_url_ecrire('message','id_message='.$id_message))
			$res['redirect'] = generer_url_ecrire('messages','quoi=envoi');
	}

	set_request('destinataires',explode(',',_request('destinataires')));
	return $res;
}

