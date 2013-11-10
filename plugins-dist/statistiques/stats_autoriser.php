<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

function stats_autoriser(){}


function autoriser_statistiques_menu_dist($faire, $type='', $id=0, $qui = NULL, $opt = NULL){
	return autoriser('voirstats', $type, $id, $qui, $opt);
}
function autoriser_referers_menu_dist($faire, $type='', $id=0, $qui = NULL, $opt = NULL){
	return autoriser('voirstats', $type, $id, $qui, $opt);
}


// Lire les stats ?
// = tous les admins
// http://doc.spip.org/@autoriser_voirstats_dist
function autoriser_voirstats_dist($faire, $type, $id, $qui, $opt) {
	return (($GLOBALS['meta']["activer_statistiques"] != 'non')
			AND ($qui['statut'] == '0minirezo'));
}

// autorisation des boutons et onglets
function autoriser_statsvisites_onglet_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('voirstats', $type, $id, $qui, $opt);
}

function autoriser_statsrepartition_onglet_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('voirstats', $type, $id, $qui, $opt);
}

function autoriser_statslang_onglet_dist($faire, $type, $id, $qui, $opt) {
	$objets = explode(',', $GLOBALS['meta']['multi_objets']);
	return (in_array('spip_articles',  $objets)
	     OR in_array('spip_rubriques', $objets))
			AND autoriser('voirstats', $type, $id, $qui, $opt);
}

function autoriser_statsreferers_onglet_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('voirstats', $type, $id, $qui, $opt);
}

?>
