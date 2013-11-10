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
 * Lister les tables a ne pas inclure dans un export de BDD
 * ici se ramener a tester l'admin restreint est un abus
 * car cela presume qu'un admin restreint ne peut pas faire de sauvegarde
 * complete, alors que l'intention est d'exclure les messages
 * des sauvegardes partielles que peuvent realiser les admin restreint
 *
 * *a revoir*
 *
 * @param array $EXPORT_tables_noexport
 * @return array
 */
function organiseur_lister_tables_noexport($EXPORT_tables_noexport){
	if (!$GLOBALS['connect_toutes_rubriques']){
		$EXPORT_tables_noexport[]='spip_messages';
		#$EXPORT_tables_noexport[]='spip_auteurs_liens'; // where objet='message'
	}
	return $EXPORT_tables_noexport;
}

/**
 * Optimiser les liens morts dans la base de donnees
 *
 * @param array $flux
 * @return array
 */
function organiseur_optimiser_base_disparus($flux){
	//
	// Messages prives
	//

	# supprimer les messages lies a un auteur disparu
	$res = Sql::select("M.id_message AS id",
		      "spip_messages AS M
		        LEFT JOIN spip_auteurs AS A
		          ON A.id_auteur=M.id_auteur",
			"A.id_auteur IS NULL");

	$flux['data'] += optimiser_sansref('spip_messages', 'id_message', $res);

	return $flux;
}

/**
 * Generer les alertes message recu a destination de l'auteur
 * concerne par l'appel
 *
 * @param array $flux
 * @return array
 */
function organiseur_alertes_auteur($flux) {

	$id_auteur = $flux['args']['id_auteur'];

	$result_messages = Sql::allfetsel("M.id_message", "spip_messages AS M LEFT JOIN spip_auteurs_liens AS L ON (L.objet='message' AND L.id_objet=M.id_message)", "L.id_auteur=".intval($id_auteur)." AND vu='non' AND statut='publie' AND type='normal'");
	$total_messages = count($result_messages);
	if ($total_messages == 1) {
		$row = reset($result_messages);
		$id_message=$row['id_message'];
		$flux['data'][] = "<a href='" . generer_url_ecrire("message","id_message=$id_message") . "'>"._T('organiseur:info_1_message_nonlu')."</a>";
	}
	elseif ($total_messages > 1)
		$flux['data'][] = "<a href='" . generer_url_ecrire("messages") . "'>"._T('organiseur:info_nb_messages_nonlus', array('nb' => $total_messages))."</a>";

	return $flux;
}

/**
 * Afficher les interventions et objets en lien
 * avec un auteur (sur sa page)
 *
 * @param array $flux
 * @return array
 */
function organiseur_affiche_auteurs_interventions($flux){

	if ($id_auteur = intval($flux['args']['id_auteur'])){
		include_spip('inc/message_select');
		// Messages de l'auteur et discussions en cours
		if ($GLOBALS['meta']['messagerie_agenda'] != 'non'
		AND $id_auteur != $GLOBALS['visiteur_session']['id_auteur']
		AND autoriser('ecrire', '', '', $flux['args']['auteur'])
		) {
		  $flux['data'] .= recuperer_fond('prive/squelettes/inclure/organiseur-interventions',array('id_auteur'=>$id_auteur));
		}
	}
  return $flux;
}

/**
 * Declarer les metas de configuration de l'agenda/messagerie
 * @param array $metas
 * @return array
 */
function organiseur_configurer_liste_metas($metas){
	$metas['messagerie_agenda'] = 'oui';
	return $metas;
}

/**
 * Inserer la css de l'agenda dans l'espace prive (hum)
 * @param string $head
 * @return string
 */
function organiseur_header_prive($head){
	// CSS calendrier
	if ($GLOBALS['meta']['messagerie_agenda'] != 'non')
		$head .= '<link rel="stylesheet" type="text/css" href="'
		  . url_absolue(find_in_path('calendrier.css')) .'" />' . "\n";

  return $head;
}

/**
 * Afficher agenda, messages et annonces sur la page d'accueil
 *
 * @param array $flux
 * @return array
 */
function organiseur_affiche_droite($flux){
	if ($flux['args']['exec']=='accueil'){
		$flux['data'] .= recuperer_fond(
			'prive/squelettes/inclure/organiseur-rappels',
			array(
				'id_auteur'=>$GLOBALS['visiteur_session']['id_auteur'],
				'last' => $GLOBALS['visiteur_session']['quand'],
			)
		);
	}
  return $flux;
}

/**
 * Afficher le formulaire de configuration sur la page concernee
 *
 * @param array $flux
 * @return array
 */
function organiseur_affiche_milieu($flux){
	if ($flux['args']['exec']=='configurer_interactions'){
		$c = recuperer_fond('prive/squelettes/inclure/configurer_messagerie',array());
	  if ($p = strpos($flux['data'],'<!--contenu_prive-->'))
		  $flux['data'] = substr_replace($flux['data'],$c,$p,0);
	  else
		  $flux['data'] .= $c;
	}
  return $flux;
}

/**
 * Diffuser un message qui passe en publie (== a envoyer)
 *
 * @param array $flux
 * @return array
 */
function organiseur_post_edition($flux){

	if ($flux['args']['table']=='spip_messages'
	  AND $flux['args']['action']=='instituer'
		AND $flux['data']['statut']=='publie'
		AND $flux['args']['statut_ancien']!='publie'
	){
		$id_message = $flux['args']['id_objet'];
		$row = Sql::fetsel('destinataires,id_auteur,titre,texte','spip_messages','id_message='.intval($id_message));
		if ($row){
			include_spip('inc/messages');
			list($auteurs_dest,$email_dests) = messagerie_destiner(explode(',',$row['destinataires']));

			// diffuser le message en interne
			messagerie_diffuser_message($id_message, $auteurs_dest);
			// diffuser le message en externe
			messagerie_mailer_message($id_message, $email_dests);
		}
	}
	return $flux;
}