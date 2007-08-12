<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/filtres');
include_spip('base/abstract_sql');

// http://doc.spip.org/@action_editer_message_dist
function action_editer_message_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

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
	spip_query("DELETE FROM spip_messages WHERE id_message="._q($id_message));
	spip_query("DELETE FROM spip_auteurs_messages WHERE id_message="._q($id_message));
	spip_query("DELETE FROM spip_forum WHERE id_message="._q($id_message));
}

// http://doc.spip.org/@action_editer_message_post_vu
function action_editer_message_post_vu($id_message, $id_auteur) {
	spip_log("efface $id_message $id_auteur");
	spip_query("UPDATE spip_auteurs_messages SET vu='oui' WHERE id_message=$id_message AND id_auteur=$id_auteur");

}

// http://doc.spip.org/@action_editer_message_post_retirer
function action_editer_message_post_retirer($id_message, $id_auteur) {
	spip_query("DELETE FROM spip_auteurs_messages WHERE id_message=$id_message AND id_auteur=$id_auteur");
}

// http://doc.spip.org/@action_editer_message_post_ajouter
function action_editer_message_post_ajouter($id_message, $id_auteur) {
	spip_query("DELETE FROM spip_auteurs_messages WHERE id_auteur=$id_auteur AND id_message=$id_message");
	sql_insert('spip_auteurs_messages',
		"(id_auteur,id_message,vu)",
		"($id_auteur,$id_message,'non')");

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
		$id_auteur = $GLOBALS['auteur_session']['id_auteur'];
		$cherche_auteur= _request('cherche_auteur');
		$query = spip_query("SELECT id_auteur, nom FROM spip_auteurs WHERE messagerie<>'non' AND id_auteur<>'$connect_id_auteur' AND pass<>'' AND login<>''");
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
					    'cherche_auteur', _request('chercher_auteur'), '&'));
	}
}


// http://doc.spip.org/@action_editer_message_post_envoyer
function action_editer_message_post_envoyer($id_message, $statut) {

	spip_query("UPDATE spip_messages SET statut=" . _q($statut) . " WHERE id_message=$id_message");
	spip_query("UPDATE spip_messages SET date_heure=NOW() WHERE id_message=$id_message AND rv<>'oui'");
}

// http://doc.spip.org/@action_editer_message_post_nouveau
function action_editer_message_post_nouveau($type, $dest='', $rv='')
{

	$id_auteur = $GLOBALS['auteur_session']['id_auteur'];

	$mydate = date("YmdHis", time() - 2 * 24 * 3600);
	spip_query("DELETE FROM spip_messages WHERE (statut = 'redac') AND (date_heure < $mydate)");

	if ($type == 'pb') $statut = 'publie';
	else $statut = 'redac';

	$titre = filtrer_entites(_T('texte_nouveau_message'));

	$id_message = sql_insert("spip_messages", "(titre, date_heure, statut, type, id_auteur)", "(" . _q($titre) . ", NOW(), '$statut', '$type', $id_auteur)");
	
	if ($rv) {
		spip_query("UPDATE spip_messages SET rv='oui', date_heure=" . _q($rv . ' 12:00:00') . ", date_fin= " . _q($rv . ' 13:00:00') . " WHERE id_message = $id_message");
	}

	if ($type != "affich"){
		sql_insert('spip_auteurs_messages',
			"(id_auteur,id_message,vu)",
			"('$id_auteur','$id_message','oui')");
		if ($dest) {
			sql_insert('spip_auteurs_messages',
				"(id_auteur,id_message,vu)",
				"('$dest','$id_message','non')");
		}
	}
	redirige_par_entete(generer_url_ecrire('message_edit', "id_message=$id_message&new=oui&dest=$dest",true));
}

// http://doc.spip.org/@action_editer_message_post_vieux
function action_editer_message_post_vieux($id_message)
{
	spip_query("UPDATE spip_messages SET titre=" . _q(_request('titre')) . ", texte=" . _q(_request('texte')) . " WHERE id_message=$id_message");

	spip_query("UPDATE spip_messages SET rv=" . _q(_request('rv')) . " WHERE id_message=$id_message");

	if (_request('jour'))
		change_date_message($id_message, _request('heures'),_request('minutes'),_request('mois'), _request('jour'), _request('annee'), _request('heures_fin'),_request('minutes_fin'),_request('mois_fin'), _request('jour_fin'), _request('annee_fin'));
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

	spip_query("UPDATE spip_messages SET date_heure='$annee-$mois-$jour $heures:$minutes:00',  date_fin='$annee_fin-$mois_fin-$jour_fin $heures_fin:$minutes_fin:00' WHERE id_message=$id_message");
}

?>
