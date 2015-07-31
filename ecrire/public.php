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

	$page = inclure_page($fond, $contexte_inclus);
	if ($page['process_ins'] == 'html')
		echo $page['texte'];
	else
		eval('?' . '>' . $page['texte']);

	if ($page['lang_select'] === true)
		lang_dselect();

} else {
	define ('_INC_PUBLIC', 1);

	//
	// Dispatcher les appels
	//

	// Faut-il initialiser SPIP ? (oui dans le cas general)
	if (defined('_DIR_RESTREINT_ABS') AND
	@file_exists(_DIR_RESTREINT_ABS.'inc_version.php')) {
		include _DIR_RESTREINT_ABS.'inc_version.php';
	}
	else
		die('stupid death...');


	// Est-ce une action ?
	if ($action = _request('action')) {
		$var_f = include_fonction($action, 'action');
		$var_f();
		if ($redirect) redirige_par_entete(urldecode($redirect));
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

	# par defaut, la globale
	} else
		tester_variable('fond', 'sommaire');

	// Particularites de certains squelettes
	if ($fond == 'login')
		$forcer_lang = true;


	//
	// Aller chercher la page
	//

	include_spip('public/global');
	$tableau_des_erreurs = array();
	$page = calcule_header_et_page($fond);

	if ($page['status']) {
		include_spip('inc/headers');
		http_status($page['status']);
	}

	foreach($page['entetes'] as $k => $v)
		@header("$k: $v");

	$html= preg_match(',^\s*text/html,',$page['entetes']['Content-Type']);

	if ($var_preview AND $html) {
		include_spip('inc/minipres');
		$page['texte'] .= afficher_bouton_preview();
	}

	// est-on admin ?
	if ($affiche_boutons_admin = (
	$_COOKIE['spip_admin']
	AND ($html OR ($var_mode == 'debug') OR count($tableau_des_erreurs))
	))
		include_spip('inc-formulaire_admin');

	// Execution de la page calculee

	// 1. Cas d'une page contenant uniquement du HTML :
	if ($page['process_ins'] == 'html') {
		$page = $page['texte'];
	}

	// 2. Cas d'une page contenant du PHP :
	// Attention cette partie eval() doit imperativement
	// etre declenchee dans l'espace des globales (donc pas
	// dans une fonction).
	else {
		// Une page "normale" va s'afficher ici
		if (! ($flag_ob 
			AND (($var_mode == 'debug')
				OR $var_recherche
				OR $affiche_boutons_admin
				OR $xhtml		))) {
			eval('?' . '>' . $page['texte']);
			$page = '';
		}

		// Certains cas demandent un ob_start() de plus
		else {
			ob_start(); 
			$res = eval('?' . '>' . $page['texte']);
			$page = ob_get_contents(); 
			ob_end_clean();

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
		debug_dumpfile($var_mode_affiche== 'validation' ? $page :"",
			       $var_mode_objet,$var_mode_affiche);
	} 

	if (count($tableau_des_erreurs) AND $affiche_boutons_admin)
		$page = affiche_erreurs_page($tableau_des_erreurs) . $page;

	// Traiter var_recherche pour surligner les mots
	if ($var_recherche) {
		include_spip('inc/surligne');
		$page = surligner_mots($page, $var_recherche);
	}

	// Valider/indenter a la demande.
	if (trim($page) AND $xhtml AND $html AND !headers_sent()) {
		# Compatibilite ascendante
		if ($xhtml === true) $xhtml ='tidy';
		else if ($xhtml == 'spip_sax') $xhtml = 'sax';

		if ($f = include_fonction($xhtml, 'inc'))
			$page = $f($page);
	}

	// Inserer au besoin les boutons admins
	if ($affiche_boutons_admin) {
		include_spip('public/admin');
		$page = affiche_boutons_admin($page);
	}

	// Affichage final s'il en reste
	echo $page;

	// Gestion des statistiques du site public
	if ($GLOBALS['meta']["activer_statistiques"] != "non") {
		include_spip ('public/stats');
		ecrire_stats();
	}

	// Effectuer une tache de fond ?
	cron();

}

?>
