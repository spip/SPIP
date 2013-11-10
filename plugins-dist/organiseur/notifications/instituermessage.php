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


/**
 * Envoyer les notifications consecutives a l'envoi d'un message
 * (ie passage en statut=publie)
 *
 * @param string $quoi
 * @param int $id_message
 * @param array $options
 */
function notifications_instituermessage_dist($quoi, $id_message, $options=array()){
	
	// ne devrait jamais se produire
	if ($options['statut'] == $options['statut_ancien']) {
		spip_log("statut inchange",'notifications');
		return;
	}

	if ($options['statut']=='publie'){
		include_spip('inc/messages');
		$type = Sql::getfetsel('type','spip_messages','id_message='.intval($id_message));
		$vue = "notifications/message_{$type}_publie";
		if (trouver_fond($vue)){
			$envoyer_mail = charger_fonction('envoyer_mail','inc'); // pour nettoyer_titre_email
			$texte = recuperer_fond($vue,array('id_message'=>$id_message));

			// recuperer tous les emails des auteurs qui ont recu le message dans leur boite
			// si c'est une annonce generale, on envoie a tout le monde
			include_spip('inc/messages');
			$where = array(
				"email!=''",
				"statut!='5poubelle'",
				Sql::in('statut',messagerie_statuts_destinataires_possibles())
			);
			// pour une annonce : tous ceux qui recoivent des messages
			if ($type!=='affich'){
				$ids = Sql::allfetsel('id_auteur','spip_auteurs_liens',"objet='message' AND id_objet=".intval($id_message));
				$ids = array_map('reset',$ids);
				$where[] = Sql::in('id_auteur',$ids);
			}
			$emails = Sql::allfetsel('email',"spip_auteurs",$where);
			$emails = array_map('reset',$emails);

			include_spip('inc/notifications');
			notifications_envoyer_mails($emails,$texte);
		}
	}
}