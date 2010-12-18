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

// Gestion ou simulation du register_globals a 'On' (PHP < 4.1.x)
// Code a l'agonie, heureusement.
// NB: c'est une fonction de maniere a ne pas pourrir $GLOBALS
// http://doc.spip.org/@spip_register_globals
function spip_register_globals($type='') {

	spip_log("spip_register_globals($type)");

	// Liste des variables dont on refuse qu'elles puissent provenir du client
	$refuse_gpc = array (
		# inc-public
		'fond', 'delais' /*,

		# ecrire/inc_auth (ceux-ci sont bien verifies dans $_SERVER)
		'REMOTE_USER',
		'PHP_AUTH_USER', 'PHP_AUTH_PW'
		*/
	);

	// Liste des variables (contexte) dont on refuse qu'elles soient cookie
	// (histoire que personne ne vienne fausser le cache)
	$refuse_c = array (
		# inc-calcul
		'id_parent', 'id_rubrique', 'id_article',
		'id_auteur', 'id_breve', 'id_forum', 'id_secteur',
		'id_syndic', 'id_syndic_article', 'id_mot', 'id_groupe',
		'id_document', 'date', 'lang',
	// et celles relatives aux raccourcis (cf inc/texte)
		'class_spip', 'class_spip_plus',
		'toujours_paragrapher',	'ligne_horizontale',
		'spip_raccourcis_typo',
		'puce', 'puce_prive', 'puce_rtl', 'puce_prive_rtl',
		'debut_intertitre', 'fin_intertitre',
		'debut_gras', 'fin_gras', 'debut_italique', 'fin_italique',
		'ouvre_note', 'ferme_note', 'les_notes', 'compt_note',
		'ouvre_ref', 'ferme_ref'
	);

	// Si les variables sont passees en global par le serveur, il faut
	// faire quelques verifications de base
	if ($type) {
		foreach ($refuse_gpc as $var) {
			if (isset($GLOBALS[$var])) {
				if (
				// demande par le client
				$_REQUEST[$var] !== NULL
				// et pas modifie par les fichiers d'appel
				AND $GLOBALS[$var] == $_REQUEST[$var]
				) // Alors on ne sait pas si c'est un hack
					die ("register_globals: $var interdite");
			}
		}
		foreach ($refuse_c as $var) {
			if (isset($GLOBALS[$var])) {
				if (
				isset ($_COOKIE[$var])
				AND $_COOKIE[$var] == $GLOBALS[$var]
				)
					define ('spip_interdire_cache', true);
			}
		}
	}

	// sinon il faut les passer nous-memes, a l'exception des interdites.
	// (A changer en une liste des variables admissibles...)
	else {
		foreach (array('_SERVER', '_COOKIE', '_POST', '_GET') as $_table) {
			foreach ($GLOBALS[$_table] as $var => $val) {
				if (!isset($GLOBALS[$var]) # indispensable securite
				AND isset($GLOBALS[$_table][$var])
				AND ($_table == '_SERVER' OR !in_array($var, $refuse_gpc))
				AND ($_table <> '_COOKIE' OR !in_array($var, $refuse_c)))
					$GLOBALS[$var] = $val;
			}
		}
	}

}
?>
