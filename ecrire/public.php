<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


// Distinguer une inclusion d'un appel initial
if (defined('_INC_PUBLIC')) {
	// $fond passe par INCLURE(){fond=...}
	if (isset($contexte_inclus['fond']))
		$fond = $contexte_inclus['fond'];
	$subpage = inclure_page($fond, $contexte_inclus);

	if ($subpage['process_ins'] == 'html')
		echo $subpage['texte'];
	else
		eval('?' . '>' . $subpage['texte']);

	if ($subpage['lang_select'] === true)
		lang_dselect();

} else {
	define ('_INC_PUBLIC', 1);

	//
	// Discriminer les appels
	//

	// Faut-il initialiser SPIP ? (oui dans le cas general)
	if (!defined('_DIR_RESTREINT_ABS'))
		if (defined('_DIR_RESTREINT')
		AND @file_exists(_DIR_RESTREINT.'inc_version.php')) {
			include_once _DIR_RESTREINT.'inc_version.php';
		}
		else
			die('stupid death...');


	// Est-ce une action ?
	if ($action = _request('action')) {
		$var_f = charger_fonction($action, 'action');
		$var_f();
		if ($redirect) redirige_par_entete($redirect);
		exit;
	}

	// cas normal, $fond defini dans le fichier d'appel
	// note : securise anti-injection par inc/utils.php
	else if (isset($fond)) { }

	// page=xxxx demandee par l'url
	else if (isset($_GET['page'])) {
		$fond = $_GET['page'];
		// Securite
		if (strstr($fond, '/'))
			die (_L("Faut pas se gener"));

	# par defaut
	} else
		$fond = 'sommaire';

	// Particularites de certains squelettes
	if ($fond == 'login')
		$forcer_lang = true;


	//
	// Aller chercher la page
	//

	$tableau_des_erreurs = array();
	$f = charger_fonction('assembler', 'public');
	$page = $f($fond);

	if (isset($page['status'])) {
		include_spip('inc/headers');
		http_status($page['status']);
	}

	$html = $page['entetes'];
	$html = (!isset($html['Content-Type']) ? '' : preg_match(',^\s*text/html,',$html['Content-Type']));

	if ($var_preview AND $html) {
		include_spip('inc/minipres');
		$page['texte'] .= afficher_bouton_preview();
	}

	// est-on admin ?
	if ($affiche_boutons_admin = (
	isset($_COOKIE['spip_admin']) 
	AND !$flag_preserver
	AND ($html OR ($var_mode == 'debug') OR count($tableau_des_erreurs))
	))
		include_spip('balise/formulaire_admin');

	// Execution de la page calculee

	// 1. Cas d'une page contenant uniquement du HTML :
	if ($page['process_ins'] == 'html') {
		foreach($page['entetes'] as $k => $v) @header("$k: $v");
	}

	// 2. Cas d'une page contenant du PHP :
	// Attention cette partie eval() doit imperativement
	// etre declenchee dans l'espace des globales (donc pas
	// dans une fonction).
	else {
		// Si la retention du flux de sortie est impossible
	  	// envoi des entetes
		if (!$flag_ob) {
			foreach($page['entetes'] as $k => $v) @header("$k: $v");
			eval('?' . '>' . $page['texte']);
			$page['texte'] = '';
		}

		// sinon, inclure_balise_dynamique nous enverra peut-etre
		// quelques en-tetes de plus (voire qq envoyes directement)
		else {
			ob_start(); 
			$res = eval('?' . '>' . $page['texte']);
			$page['texte'] = ob_get_contents(); 
			ob_end_clean();

			foreach($page['entetes'] as $k => $v) @header("$k: $v");
			// en cas d'erreur lors du eval,
			// la memoriser dans le tableau des erreurs
			// On ne revient pas ici si le nb d'erreurs > 4
			if ($res === false AND $affiche_boutons_admin
			AND $auteur_session['statut'] == '0minirezo') {
				include_spip('inc/debug');
				erreur_squelette(_T('zbug_erreur_execution_page'));
			}
		}
	}

	// Passer la main au debuggueur le cas echeant
	if ($var_mode == 'debug') {
		include_spip('inc/debug');
		debug_dumpfile($var_mode_affiche== 'validation' ? $page['texte'] :"",
			       $var_mode_objet,$var_mode_affiche);
	} 

	if (count($tableau_des_erreurs) AND $affiche_boutons_admin)
		$page['texte'] = affiche_erreurs_page($tableau_des_erreurs)
			. $page['texte'];

	// Post-traitements et affichage final
	// (c'est ici qu'on fait var_recherche, tidy, boutons d'admin,
	// cf. public/assembler.php)
	echo pipeline('affichage_final', $page['texte']);

	// Gestion des statistiques du site public
	if ($GLOBALS['meta']["activer_statistiques"] != "non") {
		$f = charger_fonction('stats', 'public');
		$f();
	}

	// Effectuer une tache de fond ?
	cron();

}

?>
