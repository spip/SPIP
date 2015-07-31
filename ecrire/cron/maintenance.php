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

// Diverses taches de maintenance
// http://doc.spip.org/@cron_maintenance
function cron_maintenance_dist ($t) {

	// (re)mettre .htaccess avec deny from all
	// dans les deux repertoires dits inaccessibles par http
	include_spip('inc/acces');
	verifier_htaccess(_DIR_ETC);
	verifier_htaccess(_DIR_TMP);

	// Verifier qu'aucune table n'est crashee
	verifier_crash_tables();

	return 1;
}


// http://doc.spip.org/@verifier_crash_tables
function verifier_crash_tables() {
	if (spip_connect()) {
		include_spip('base/serial');
		include_spip('base/auxiliaires');
		$crash = array();
		foreach (array('tables_principales', 'tables_auxiliaires') as $com) {
			foreach ($GLOBALS[$com] as $table => $desc) {
				if (!spip_query("SELECT * FROM $table LIMIT 1")
				AND !defined('spip_interdire_cache')) # cas "LOST CONNECTION"
					$crash[] = $table;
			}
		}
		#$crash[] = 'test';
		if ($crash) {
			ecrire_meta('message_crash_tables', serialize($crash));
			spip_log('crash des tables', 'err');
			spip_log($crash, 'err');
		} else {
			effacer_meta('message_crash_tables');
		}
		ecrire_metas();

		return $crash;
	}

	return false;
}

// http://doc.spip.org/@message_crash_tables
function message_crash_tables() {
	if ($crash = verifier_crash_tables()) {
		return 
		'<strong>' . _T('texte_recuperer_base') . '</strong><br />'
		. ' <tt>'.join(', ', $crash).'</tt><br />'
		. generer_form_ecrire('admin_repair',
			_T('texte_crash_base'), '',
			_T('bouton_tenter_recuperation'))
		;
	}
}

?>
