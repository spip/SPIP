<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function formulaires_configurer_annonces_charger_dist() {
	$valeurs = [];
	foreach (
		[
		'suivi_edito',
		'adresse_suivi',
		'adresse_suivi_inscription',
		'quoi_de_neuf',
		'adresse_neuf',
		'jours_neuf',
		'email_envoi',
		] as $m
	) {
		$valeurs[$m] = $GLOBALS['meta'][$m];
	}

	return $valeurs;
}

function formulaires_configurer_annonces_verifier_dist() {
	$erreurs = [];
	if (_request('suivi_edito') == 'oui') {
		if (!$email = _request('adresse_suivi')) {
			$erreurs['adresse_suivi'] = _T('info_obligatoire');
		} else {
			include_spip('inc/filtres');
			if (!email_valide($email)) {
				$erreurs['adresse_suivi'] = _T('form_prop_indiquer_email');
			}
		}
	}
	if (_request('quoi_de_neuf') == 'oui') {
		if (!$email = _request('adresse_neuf')) {
			$erreurs['adresse_neuf'] = _T('info_obligatoire');
		} else {
			include_spip('inc/filtres');
			if (!email_valide($email)) {
				$erreurs['adresse_neuf'] = _T('form_prop_indiquer_email');
			}
		}
		if (!$email = _request('jours_neuf')) {
			$erreurs['jours_neuf'] = _T('info_obligatoire');
		}
	}

	return $erreurs;
}

function formulaires_configurer_annonces_traiter_dist() {
	$res = ['editable' => true];
	foreach (
		[
		'suivi_edito',
		'quoi_de_neuf',
		] as $m
	) {
		if (!is_null($v = _request($m))) {
			ecrire_meta($m, $v == 'oui' ? 'oui' : 'non');
		}
	}

	foreach (
		[
		'adresse_suivi',
		'adresse_suivi_inscription',
		'adresse_neuf',
		'jours_neuf',
		'email_envoi',
		] as $m
	) {
		if (!is_null($v = _request($m))) {
			ecrire_meta($m, $v);
		}
	}

	$res['message_ok'] = _T('config_info_enregistree');
	// provoquer l'envoi des nouveautes en supprimant le fichier lock
	if (_request('envoi_now')) {
		effacer_meta('dernier_envoi_neuf');
		$id_job = job_queue_add('mail', 'Test Envoi des nouveautes', [0], 'genie/');
		include_spip('inc/queue');
		queue_schedule([$id_job]);
		$res['message_ok'] .= '<br />' . _T('info_liste_nouveautes_envoyee');
	}

	return $res;
}
