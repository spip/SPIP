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

include_spip('inc/filtres');

// http://doc.spip.org/@action_editer_message_dist
function action_editer_message_dist($arg=null) {

	if (is_null($arg)){
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}

	if (preg_match(',^(\d+)$,', $arg, $r))
		action_editer_message_post_vieux($arg); 
	elseif (preg_match(',^-(\d+)$,', $arg, $r))
		action_editer_message_post_supprimer($r[1]);
	elseif (preg_match(',^(\d+)\W$,', $arg, $r))
		action_editer_message_post_choisir($r[1]);	  
	elseif (preg_match(',^(\d+)\W@(\d+)$,', $arg, $r))
		action_editer_message_post_ajouter($r[1], $r[2]);	  
	elseif (preg_match(',^(\d+)\W:(\d+)$,', $arg, $r))
		action_editer_message_post_vu($r[1], $r[2]);	  
	elseif (preg_match(',^(\d+)\W-(\d+)$,', $arg, $r))
		action_editer_message_post_retirer($r[1], $r[2]);	  
	elseif (preg_match(',^(\d+)\W(\w+)$,', $arg, $r))
		action_editer_message_post_envoyer($r[1], $r[2]);	  
	elseif (preg_match(',^(\w+)$,', $arg, $r))
		action_editer_message_post_nouveau($arg);
	elseif (preg_match(',^(\w+)\W(\d+)$,', $arg, $r))
		action_editer_message_post_nouveau($r[1], $r[2]);
	elseif (preg_match(',^(\w+)\W(\d+-\d+-\d+)$,', $arg, $r))
		action_editer_message_post_nouveau($r[1], '', $r[2]);
	else 	spip_log("action_editer_message_dist $arg pas compris");
}

// http://doc.spip.org/@action_editer_message_post_supprimer
function action_editer_message_post_supprimer($id_message) {
	sql_delete("spip_messages", "id_message=".sql_quote($id_message));
	sql_delete("spip_auteurs_messages", "id_message=".sql_quote($id_message));
	sql_delete("spip_forum", "id_message=".sql_quote($id_message));
	pipeline('trig_supprimer_objets_lies',array(
		array('type'=>'message','id'=>$id_message)
	));
}

// http://doc.spip.org/@action_editer_message_post_vu
function action_editer_message_post_vu($id_message, $id_auteur) {
	sql_updateq("spip_auteurs_messages", array("vu" => 'oui'), "id_message=$id_message AND id_auteur=$id_auteur");

}

// http://doc.spip.org/@action_editer_message_post_retirer
function action_editer_message_post_retirer($id_message, $id_auteur) {
	sql_delete("spip_auteurs_messages", "id_message=$id_message AND id_auteur=$id_auteur");
}

// http://doc.spip.org/@action_editer_message_post_ajouter
function action_editer_message_post_ajouter($id_message, $id_auteur) {

	sql_delete("spip_auteurs_messages", "id_auteur=$id_auteur AND id_message=$id_message");
	sql_insertq('spip_auteurs_messages',
		   array('id_auteur' => $id_auteur,
			 'id_message' => $id_message,
			 'vu' =>'non'));

	// Ne pas notifier ici, car si on se trompe d'auteur, on veut avoir le temps
	// de supprimer celui qu'on vient d'ajouter... c'est fait en cron
}

// http://doc.spip.org/@action_editer_message_post_choisir
function action_editer_message_post_choisir($id_message) {

	if ($id_auteur = _request('nouv_auteur'))
		action_editer_message_post_ajouter($id_message, $id_auteur);
	else {
		include_spip('inc/mots');
		include_spip('inc/charsets'); // pour tranlitteration
		$id_auteur = $GLOBALS['visiteur_session']['id_auteur'];
		$cherche_auteur= _request('cherche_auteur');
		$query = sql_select("id_auteur, nom", "spip_auteurs", "messagerie<>'non' AND pass<>'' AND login<>'' AND id_auteur<>" . sql_quote($id_auteur));
		$table_auteurs = array();
		$table_ids = array();
		while ($row = sql_fetch($query)) {
			$table_auteurs[] = $row['nom'];
			$table_ids[] = $row['id_auteur'];
		}
		$res =  mots_ressemblants($cherche_auteur, $table_auteurs, $table_ids);
		$n = count($res);

		if ($n == 1)
			# Bingo
			action_editer_message_post_ajouter($id_message, $res[0]);
		# renvoyer la valeur ==> formulaire de choix si n !=1
		# notification que $res[0] a ete rajoute sinon
		redirige_par_entete(parametre_url(urldecode(_request('redirect')),
					    'cherche_auteur', $cherche_auteur, '&'));
	}
}


// http://doc.spip.org/@action_editer_message_post_envoyer
function action_editer_message_post_envoyer($id_message, $statut) {

	sql_updateq("spip_messages", array("statut" => $statut), "id_message=$id_message");
	sql_updateq("spip_messages", array("date_heure" => date('Y-m-d H:i:s')), "id_message=$id_message AND rv<>'oui'");
}

// http://doc.spip.org/@action_editer_message_post_nouveau
function action_editer_message_post_nouveau($type, $dest='', $rv='')
{

	$id_auteur = $GLOBALS['visiteur_session']['id_auteur'];

	$mydate = date("YmdHis", time() - 2 * 24 * 3600);
	sql_delete("spip_messages", "(statut = 'redac') AND (date_heure < $mydate)");

	if ($type == 'pb') $statut = 'publie';
	else $statut = 'redac';

	$titre = filtrer_entites(_T('texte_nouveau_message'));

	$vals = array('titre' => $titre,
		      'statut' => $statut,
		      'type' => $type,
		      'id_auteur' => $id_auteur);

	if (!$rv)
		$vals['date_heure'] = date('Y-m-d H:i:s');
	else {
		$vals['date_heure'] = "$rv 12:00:00";
		$vals['date_fin'] = "$rv 13:00:00";
		$vals['rv'] = 'oui';
	}

	$id_message = sql_insertq("spip_messages", $vals);

	if ($type != "affich"){
		sql_insertq('spip_auteurs_messages',
		   array('id_auteur' => $id_auteur,
			 'id_message' => $id_message,
			 'vu' =>'oui'));
		if ($dest) {
			sql_insertq('spip_auteurs_messages',
				    array('id_auteur' => $dest,
					  'id_message' => $id_message,
					  'vu' =>'non'));
		}
	}

	redirige_url_ecrire('message_edit', "id_message=$id_message&new=oui&dest=$dest");
}

// http://doc.spip.org/@action_editer_message_post_vieux
function action_editer_message_post_vieux($id_message)
{
	sql_updateq('spip_messages', array('titre'=>_request('titre'), 'texte' => _request('texte')), "id_message=$id_message");

	sql_updateq('spip_messages', array('rv' => _request('rv')), "id_message=$id_message");

	if (_request('jour'))
		change_date_message($id_message, _request('heures'),_request('minutes'),_request('mois'), _request('jour'), _request('annee'), _request('heures_fin'),_request('minutes_fin'),_request('mois_fin'), _request('jour_fin'), _request('annee_fin'));
	action_editer_message_post_choisir($id_message);
}


// Convertir dates a calendrier correct
// (exemple: 31 fevrier devient debut mars, 24h12 devient 00h12 du lendemain)

// http://doc.spip.org/@change_date_message
function change_date_message($id_message, $heures,$minutes,$mois, $jour, $annee, $heures_fin,$minutes_fin,$mois_fin, $jour_fin, $annee_fin)
{
	$date = date("Y-m-d H:i:s", mktime($heures,$minutes,0,$mois, $jour, $annee));
	
	$jour = journum($date);
	$mois = mois($date);
	$annee = annee($date);
	$heures = heures($date);
	$minutes = minutes($date);
	
	// Verifier que la date de fin est bien posterieure au debut
	$unix_debut = date("U", mktime($heures,$minutes,0,$mois, $jour, $annee));
	$unix_fin = date("U", mktime($heures_fin,$minutes_fin,0,$mois_fin, $jour_fin, $annee_fin));
	if ($unix_fin <= $unix_debut) {
		$jour_fin = $jour;
		$mois_fin = $mois;
		$annee_fin = $annee;
		$heures_fin = $heures + 1;
		$minutes_fin = $minutes;
	}		

	$date_fin = date("Y-m-d H:i:s", mktime($heures_fin,$minutes_fin,0,$mois_fin, $jour_fin, $annee_fin));
	
	$jour_fin = journum($date_fin);
	$mois_fin = mois($date_fin);
	$annee_fin = annee($date_fin);
	$heures_fin = heures($date_fin);
	$minutes_fin = minutes($date_fin);

	sql_updateq('spip_messages', array('date_heure'=>"$annee-$mois-$jour $heures:$minutes:00",  'date_fin'=>"$annee_fin-$mois_fin-$jour_fin $heures_fin:$minutes_fin:00"), "id_message=$id_message");
}

?>
