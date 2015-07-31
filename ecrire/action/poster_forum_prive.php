<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// http://doc.spip.org/@action_poster_forum_prive_dist
function action_poster_forum_prive_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	// arg = l'eventuel mot a supprimer pour d'eventuelles Row SQL
	if (!preg_match(',^(\d+)\D(\d+)\D(\w+)\W(\w+)\W(\w+)$,', $arg, $r)) 
		spip_log("action poster_forum_prive: $arg pas compris");
	else action_poster_forum_prive_post($r);
}

// http://doc.spip.org/@action_poster_forum_prive_post
function action_poster_forum_prive_post($r)
{
	list(,$id, $id_parent, $statut, $script, $objet) = $r;

	if (_request('valider_forum') AND ($statut!='')) {
		include_spip('inc/texte');
		include_spip('inc/forum');

		$titre_message = corriger_caracteres(_request('titre_message'));
		$texte = corriger_caracteres(_request('texte'));

		$id_forum = sql_insertq('spip_forum', array(
			$objet => $id,
			'titre' => $titre_message,
			'texte' => $texte,
			'date_heure' => date('Y-m-d H:i:s'),
			'nom_site' => _request('nom_site'),
			'url_site' => _request('url_site'),
			'statut' => $statut,
			'id_auteur' =>$GLOBALS['visiteur_session']['id_auteur'],
			'auteur' => $GLOBALS['visiteur_session']['nom'],
			'email_auteur' => $GLOBALS['visiteur_session']['email'],
			'id_parent' => $id_parent));

		calculer_threads();

		if ($objet == 'id_message') {
			sql_updateq("spip_auteurs_messages", array("vu" => 'non'), "id_message=$id");

		}

		// Notification
		if ($notifications = charger_fonction('notifications', 'inc')) {
			$notifications('forumprive', $id_forum);
		}

		$retour = urldecode(_request('redirect'));
		$retour = parametre_url($retour, 'modif_forum', 'fin', '&');
		$retour = parametre_url($retour, 'texte', $objet, '&');
#		$retour = parametre_url($retour, 'script', $script, '&');
		redirige_par_entete($retour ."#id".$id_forum);
	} else {
	   // previsualisation : on ne fait que passer .... 
	   // et si les clients HTTP respectaient le RFC HTTP selon lequel
	   // une redirection d'un POST doit etre en POST et pas en GET
	   // on n'aurait pas a faire l'horreur ci-dessous.
		  
	   set_request('action', '');
	   set_request('exec', 'poster_forum_prive');
	   set_request('id', $id);
	   set_request('id_parent', $id_parent);
	   set_request('statut', $statut);
	   set_request('script', $script);

	   include(_DIR_RESTREINT.'index.php');
	   exit;
	}
}
?>
