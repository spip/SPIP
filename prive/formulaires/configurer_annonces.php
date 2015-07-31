<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

function formulaires_configurer_annonces_charger_dist(){
	foreach(array(
		"suivi_edito",
		"adresse_suivi",
		"adresse_suivi_inscription",
		"quoi_de_neuf",
		"adresse_neuf",
		"jours_neuf",
		"email_envoi",
		) as $m)
		$valeurs[$m] = $GLOBALS['meta'][$m];

	return $valeurs;
}

function formulaires_configurer_annonces_verifier_dist(){
	$erreurs = array();
	if (_request('suivi_edito')=='oui') {
		if (!$email=_request('adresse_suivi'))
			$erreurs['adresse_suivi'] = _T('info_obligatoire');
		else {
			include_spip('inc/filtres');
			if (!email_valide($email))
				$erreurs['adresse_suivi'] = _T('form_prop_indiquer_email');
		}
	}
	if (_request('quoi_de_neuf')=='oui') {
		if (!$email=_request('adresse_neuf'))
			$erreurs['adresse_neuf'] = _T('info_obligatoire');
		else {
			include_spip('inc/filtres');
			if (!email_valide($email))
				$erreurs['adresse_neuf'] = _T('form_prop_indiquer_email');
		}
		if (!$email=_request('jours_neuf'))
			$erreurs['jours_neuf'] = _T('info_obligatoire');
	}
	return $erreurs;
}

function formulaires_configurer_annonces_traiter_dist(){
	$res = array('editable'=>true);
	foreach(array(
		"suivi_edito",
		"quoi_de_neuf",
		) as $m)
		if (!is_null($v=_request($m)))
			ecrire_meta($m, $v=='oui'?'oui':'non');

	foreach(array(
		"adresse_suivi",
		"adresse_suivi_inscription",
		"adresse_neuf",
		"jours_neuf",
		"email_envoi",
		) as $m)
		if (!is_null($v=_request($m)))
			ecrire_meta($m, $v);

	$res['message_ok'] = _T('config_info_enregistree');
	// provoquer l'envoi des nouveautes en supprimant le fichier lock
	if (_request('envoi_now')) {
		effacer_meta('dernier_envoi_neuf');
		$id_job = job_queue_add("mail","Test Envoi des nouveautes",array(0),"genie/");
		include_spip('inc/queue');
		queue_schedule(array($id_job));
		$res['message_ok'] .= "<br />"._T("info_liste_nouveautes_envoyee");
	}

	return $res;
}

