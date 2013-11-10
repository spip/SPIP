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

include_spip('inc/filtres');

if (!defined('_EMAIL_GENERAL'))
	define('_EMAIL_GENERAL','general'); // permet aux admin d'envoyer un email a tout le monde

/**
 * Lister les statuts des auteurs pouvant recevoir un message
 * c'est tous les auteurs au moins redacteur
 * 
 * @return array
 */
function messagerie_statuts_destinataires_possibles(){
	include_spip('inc/filtres_ecrire');
	return pipeline('messagerie_statuts_destinataires_possibles', auteurs_lister_statuts('redacteurs',false));
}

/**
 * Nettoyer une liste de destinataires
 * @param $destinataires
 * @return array
 */
function messagerie_nettoyer_destinataires($destinataires){
	foreach ($destinataires as $k=>$id){
		// il se peut que l'id recupere l'ancre qui suit avec certains ie ... :(
		if (preg_match(',^[0-9]+#[a-z_0-9]+,',$id))
			$destinataires[$k] = intval($id);
	}
	return $destinataires;
}

/**
 * Fonction generique de verification des destinataires
 * lors de l'envoi d'un message ou de recommander
 * un destinataire peut etre un id_auteur numerique
 * ou une adresse mail valide, si l'options accepter_email est true
 *
 * @param array $destinataires
 * @param array $options
 * @return array
 */
function messagerie_verifier_destinataires($destinataires,$options=array('accepter_email'=>true)){
	$erreurs = array();

	$destinataires = messagerie_nettoyer_destinataires($destinataires);
	foreach ($destinataires as $id){
		if (is_numeric($id)){
			if (!$id)
				$erreurs[] = _T('organiseur:erreur_destinataire_invalide',array('dest'=>$id));
		}
		else {
			if (!$options['accepter_email']
				OR !email_valide($id))
				$erreurs[] = _T('organiseur:erreur_destinataire_invalide',array('dest'=>$id));
		}
	}

	return $erreurs;
}

/**
 * Selectionner les destinataires en distinguant emails et id_auteur
 *
 * @param array $dests
 * @return array
 */
function messagerie_destiner($dests){
	// separer les destinataires auteur des destinataires email
	$auteurs_dest = array();
	$email_dests = array();

	$dests = messagerie_nettoyer_destinataires($dests);
	foreach ($dests as $id){
		if (is_numeric($id))
			$auteurs_dest[] = $id;
		elseif (defined('_MESSAGERIE_EMAIL_GENERAL') AND $id!=_MESSAGERIE_EMAIL_GENERAL)
			$email_dests[] = $id;
	}
	if (count($email_dests)) {
		// retrouver les id des emails pour ceux qui sont en base
		$res = Sql::select('id_auteur,email','spip_auteurs',Sql::in('email', $email_dests));
		$auteurs_dest_found = array();
		while ($row = Sql::fetch($res)){
			$auteurs_dest_found[] = $row['id_auteur'];
		}
		$auteurs_dest = array_merge($auteurs_dest,$auteurs_dest_found);
	}
	return array($auteurs_dest,$email_dests);
}

/**
 * Diffuser un message par la messagerie interne
 *
 * @param int $id_message
 * @param array $auteurs_dest
 * @return bool|int
 */
function messagerie_diffuser_message($id_message, $auteurs_dest=array()){
	$out = false;
	if ($id_message=intval($id_message)
	  AND count($auteurs_dest)){
		include_spip('action/editer_liens');
		$out = objet_associer(array('auteur'=>$auteurs_dest),array('message'=>$id_message),array('vu'=>'non'));
	}
	return $out;
}

/**
 * Envoyer un message par mail pour les destinataires externes
 *
 * @param int $id_message
 * @param array $emails_dest
 * @return bool
 */
function messagerie_mailer_message($id_message, $emails_dest=array()){
	if ($id_message=intval($id_message)
	  AND count($emails_dest)) {
		if ($row = Sql::fetsel('titre,texte,id_auteur','spip_messages','id_message='.intval($id_message))){
			$from = Sql::getfetsel('email','spip_auteurs','id_auteur='.$row['id_auteur']);
			foreach($emails_dest as $email)
				job_queue_add(
					'envoyer_mail',
					'messagerie mail',
				  array($email,$row['titre'],array('texte'=>$row['texte'],'from'=>$from)),
					'inc/'
				);
			return true;
		}
	}
	return false;
}

/**
 * Marquer un message dans l'etat indique par $vu
 *
 * @param int $id_auteur
 * @param array $liste
 * @param string $vu
 * @return void
 */
function messagerie_marquer_message($id_auteur,$liste,$vu){
	include_spip('action/editer_liens');
	if (!is_array($liste))
		$liste = array($liste);
	// completer les liens qui n'existent pas encore
	// ex : pour marquer lue une annonce, on ajoute le lien d'abord (n'existe pas)
	// puis on le marque 'oui'
	$liens = objet_trouver_liens(array('auteur'=>$id_auteur),array('message'=>$liste));
	$l = array();
	foreach($liens as $lien)
		$l[] = $lien['message'];
	objet_associer(array('auteur'=>$id_auteur),array('message'=>array_diff($liste,$l)),array('vu'=>$vu));
	// puis les marquer tous lus
	objet_qualifier_liens(array('auteur'=>$id_auteur),array('message'=>$liste),array('vu'=>$vu));
	include_spip('inc/invalideur');
	suivre_invalideur("message/".implode(',',$liste));
}

/**
 * Marquer un message comme lu
 *
 * @param int $id_auteur
 * @param array $liste_id_message
 */
function messagerie_marquer_lus($id_auteur,$liste_id_message){messagerie_marquer_message($id_auteur,$liste_id_message,'oui');}

/**
 * Marquer un message comme non lu
 *
 * @param int $id_auteur
 * @param array $liste_id_message
 */
function messagerie_marquer_non_lus($id_auteur,$liste_id_message){messagerie_marquer_message($id_auteur,$liste_id_message,'non');}

/**
 * Effacer un message recu
 *
 * @param int $id_auteur
 * @param array $liste_id_message
 */
function messagerie_effacer_message_recu($id_auteur,$liste_id_message){messagerie_marquer_message($id_auteur,$liste_id_message,'poub');}

?>
