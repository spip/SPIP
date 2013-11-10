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
 * Fournir une liste d'"evenements" entre deux dates start et end
 * au format json
 * utilise pour l'affichage du calendrier prive et public
 * 
 * @return void
 */
function action_quete_calendrier_prive_dist(){
	$securiser_action = charger_fonction('securiser_action','inc');
	$securiser_action();

	$start = _request('start');
	$end = _request('end');
	$quoi = _request('quoi');

	include_spip('inc/quete_calendrier');

	$evt = array();

	// recuperer la liste des evenements au format ics
	$start = date('Y-m-d H:i:s',$start);
	$end = date('Y-m-d H:i:s',$end);
	$limites = array(Sql::quote($start),Sql::quote($end));

	// on fonction de quoi on récupère : tout (rv + publication) ou l'un ou l'autre.
	$entier = $duree = array();

	if (!$quoi OR $quoi=='rv') {
		$duree = quete_calendrier_interval_rv(reset($limites), end($limites));
		$evt = convert_fullcalendar_quete_calendrier_interval_rv($duree, $evt);
	}

	if (!$quoi OR $quoi=='publication') {
		list($entier,) = quete_calendrier_interval($limites);
		$evt = convert_fullcalendar_quete_calendrier_interval($entier, $evt);
	}


	// permettre aux plugins d'afficher leurs evenements dans ce calendrier
	$evt = pipeline('quete_calendrier_prive',
		array(
			'args' => array('start' => $start, 'end' => $end, 'quoi'=>$quoi),
			'data' => $evt,
		)
	);

	// format json
	include_spip('inc/json');
	echo json_encode($evt);
}

/**
 * Convertir une date au format ical renvoyee par quete_calendrier_interval
 * dans le format attendu par fullcalendar : yyyy-mm-dd H:i:s
 * @param $dateical
 * @return string
 */
function convert_dateical($dateical){
	$d = explode('T',$dateical);
	$amj = reset($d);
	$s = substr($amj,0,4).'-'.substr($amj,4,2).'-'.substr($amj,6,2);
	if (count($d)>1){
		$his = end($d);
		$s .= ' '.substr($his,0,2).":".substr($his,2,2).":".substr($his,4,2);
	}
	return $s;
}

/**
 * Convertir une sortie événement de quète calendrier_interval
 * dans le format attendu par fullcalendar
 *
 * @param array $messages
 *     Les événements / messages au format de la quete calendrier_interval
 * @param array $evt
 *     Les événements au format fullcalendar déjà présents
 * @return array
 *     Les événements au format fullcalendar
**/
function convert_fullcalendar_quete_calendrier_interval($messages, $evt = array()) {
	if (!$messages) return $evt;

	// la retransformer au format attendu par fullcalendar
	// facile : chaque evt n'est mentionne qu'une fois, a une date
	foreach($messages as $amj=>$l){
		$date = substr($amj,0,4).'-'.substr($amj,4,2).'-'.substr($amj,6,2);
		foreach($l as $e){
			$evt[] = array(
				'id' => 0,
				'title' => $e['SUMMARY'],
				'allDay' => true,
				'start' => $date,
				'end' => $date,
				'url' => str_replace('&amp;','&',$e['URL']),
				'className' => "calendrier-event ".$e['CATEGORIES'],
				'description' => $e['DESCRIPTION'],
			);
		}
	}

	return $evt;
}

/**
 * Convertir une sortie événement de quete calendrier_interval_rv
 * dans le format attendu par fullcalendar
 *
 * @param array $messages
 *     Les événements / messages au format issu de la quete calendrier_interval_rv
 * @param array $evt
 *     Les événements au format fullcalendar déjà présents
 * @return array
 *     Les événements au format fullcalendar
**/
function convert_fullcalendar_quete_calendrier_interval_rv($messages, $evt = array()) {
	if (!$messages) return $evt;

	// ici il faut faire attention : un evt apparait N fois
	// mais on a son id
	$seen = array();

	// toutes les messages déjà inscrits qu'on ne remet pas
	foreach ($evt as $e) {
		$seen[$e['url']] = true;
	}

	foreach($messages as $amj=>$l){
		foreach($l as $id=>$e){
			$url = str_replace('&amp;','&',$e['URL']);
			if (!isset($seen[$url])) {
				$evt[] = array(
					'id' => $id,
					'title' => $e['SUMMARY'],
					'allDay' => false,
					'start' => convert_dateical($e['DTSTART']), //Ymd\THis
					'end' => convert_dateical($e['DTEND']), // Ymd\THis
					'url' => $url,
					'className' => "calendrier-event ".$e['CATEGORIES'],
					'description' => $e['DESCRIPTION'],
				);
				$seen[$url] = true;
			}
		}
	}

	return $evt;
}
