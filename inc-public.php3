<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


// Distinguer une inclusion d'un appel initial
if (defined("_INC_PUBLIC")) {
	$page = inclure_page($fond, $contexte_inclus);
	if ($page['process_ins'] == 'html')
		echo $page['texte'];
	else
		eval('?' . '>' . $page['texte']);

	if ($page['lang_select'] === true)
		lang_dselect();

} else {
	define ("_INC_PUBLIC", 1);
	if (!function_exists('include_local')) { # cas de page.php3
		include ("ecrire/inc_version.php3");
	}
	include_local('inc-public-global');

	// Calculer la page en envoyant seulement les en-tetes, pas la page
	$tableau_des_erreurs = array();
	$page = calcule_header_et_page ($fond);

	// est-on admin ?
	if ($affiche_boutons_admin = (
	(!$flag_preserver AND $GLOBALS['_COOKIE']['spip_admin'])
	OR $var_mode == 'debug'))
	  include_local(find_in_path('inc-formulaire_admin.php3'));
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
				include_ecrire('inc_debug_sql');
				erreur_squelette(_T('zbug_erreur_execution_page'));
			}
		}
	}

	// Passer la main au debuggueur le cas echeant 
	if ($var_mode == 'debug') {
		include_ecrire("inc_debug_sql");
		debug_dumpfile($var_mode_affiche== 'validation' ? $page :"",
			       $var_mode_objet,$var_mode_affiche);
	} 
	if (count($tableau_des_erreurs) > 0 AND $affiche_boutons_admin)
		$page = affiche_erreurs_page($tableau_des_erreurs) . $page;

	// Traiter var_recherche pour surligner les mots
	if ($var_recherche) {
		include_ecrire("inc_surligne");
		$page = surligner_mots($page, $var_recherche);
	}

	// Valider/indenter a la demande. garder la compatibilite tidy
	if (trim($page) AND $xhtml AND !$flag_preserver AND !headers_sent()) {
		charger_analyseur_xhtml($xhtml);
		if (function_exists($xhtml))
			$page = $xhtml($page);
		else if (function_exists('xhtml'))
			$page = xhtml($page);
	}

	// Inserer au besoin les boutons admins
	if ($affiche_boutons_admin) {
		include_local("inc-admin");
		$page = affiche_boutons_admin($page);
	}

	// Affichage final s'il en reste
	echo $page;

	// Gestion des statistiques du site public
	if ($GLOBALS['meta']["activer_statistiques"] != "non") {
		include_local ("inc-stats");
		ecrire_stats();
	}

	// Effectuer une tache de fond ?
	cron();

}

?>
