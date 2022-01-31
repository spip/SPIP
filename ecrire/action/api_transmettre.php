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

/**
 * Gestion de l'action activer_plugins
 *
 * @package SPIP\Core\Action
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


function action_api_transmettre_dist($arg = null) {

	// Obtenir l'argument 'id_auteur/cle/format/fond'
	if (is_null($arg)) {
		$arg = _request('arg');
	}

	$args = explode('/', $arg);

	if (count($args) !== 4) {
		action_api_transmettre_fail($arg);
	}

	[$id_auteur, $cle, $format, $fond] = $args;
	$id_auteur = intval($id_auteur);

	if (preg_match(",[^\w\\.-],", $format)) {
		action_api_transmettre_fail("format $format");
	}
	if (preg_match(",[^\w\\.-],", $fond)) {
		action_api_transmettre_fail("fond $fond");
	}

	// verifier la cle
	//[(#ENV{id,0}|securiser_acces{#ENV{cle}, voirstats, #ENV{op}, #ENV{args}}|?{1,0})]
	//[(#ENV{id,0}|securiser_acces{#ENV{cle}, voirstats, #ENV{op}, #ENV{args}}|?{1,0})]

	$qs = $_SERVER['QUERY_STRING'];
	// retirer action et arg de la qs
	$contexte = [];
	parse_str($qs, $contexte);
	foreach ($contexte as $k => $v) {
		if (in_array($k, ['action', 'arg', 'var_mode'])) {
			unset($contexte[$k]);
		}
	}
	$qs = http_build_query($contexte);
	if (!securiser_acces_low_sec(intval($id_auteur), $cle, "transmettre/$format", $fond, $qs)) {
		var_dump([$id_auteur, $cle, "transmettre/$format", $fond, $qs]);
		action_api_transmettre_fail("QS $qs");
	}

	// et une autorisation en bonne et due forme
	include_spip('inc/autoriser');
	if (!autoriser('transmettre',"_{$format}_{$fond}", $id_auteur)) {
		action_api_transmettre_fail("autoriser");
	}

	$contexte['id_auteur'] = $id_auteur;

	$fond = "transmettre/$format/$fond";

	if (!trouver_fond($fond) and !test_espace_prive()) {
		$fond = "prive/$fond";
	}

	$res = recuperer_fond($fond, $contexte, ['raw' => true]);
	if (!empty($res['entetes'])) {
		foreach ($res['entetes'] as $h => $v) {
			header("$h: $v");
		}
	}
	echo ltrim($res['texte']);
	exit();
}

function action_api_transmettre_fail($arg) {
	include_spip('inc/minipres');
	echo minipres(_T('info_acces_interdit'), $arg);
	exit;
}