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
 * Gestion des différentes tâches de maintenance
 *
 * @package SPIP\Core\Genie\Maintenance
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Diverses tâches de maintenance
 *
 * - (re)mettre .htaccess avec 'Deny from all'
 *   dans les deux répertoires dits inaccessibles par http
 * - Vérifier qu'aucune table ne s'est crashée
 *
 * @uses verifier_htaccess()
 * @uses verifier_crash_tables()
 *
 * @param object $t
 * @return bool Toujours à true.
 */
function genie_maintenance_dist($t) {

	// (re)mettre .htaccess avec deny from all
	// dans les deux repertoires dits inaccessibles par http
	include_spip('inc/acces');
	verifier_htaccess(_DIR_ETC);
	verifier_htaccess(_DIR_TMP);

	// Verifier qu'aucune table n'est crashee
	if (!_request('reinstall')) {
		verifier_crash_tables();
	}

	return 1;
}


/**
 * Vérifier si une table a crashé
 *
 * Pour cela, on vérifie si on peut se connecter à la base de données.
 *
 * @see message_crash_tables()
 *
 * @return bool|array
 *     Si pas de table de crashée, on retourne `false`.
 *     Sinon,  retourne un tableau contenant tous les noms
 *     des tables qui ont crashé.
 */
function verifier_crash_tables() {
	if (spip_connect()) {
		include_spip('base/serial');
		include_spip('base/auxiliaires');
		$crash = array();
		foreach (array('tables_principales', 'tables_auxiliaires') as $com) {
			foreach ($GLOBALS[$com] as $table => $desc) {
				if (!sql_select('*', $table, '', '', '', 1)
					and !defined('spip_interdire_cache')
				) { # cas "LOST CONNECTION"
				$crash[] = $table;
				}
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

		return $crash;
	}

	return false;
}

/**
 * Vérifier si une table a crashé et crée un message en conséquence.
 *
 * S'il y a un crash, on affiche un message avec le nom
 * de la ou des tables qui ont crashé.
 * On génère un lien vers la page permettant la
 * réparation de la base de données.
 *
 * @uses verifier_crash_tables()
 *
 * @return string
 */
function message_crash_tables() {
	if ($crash = verifier_crash_tables()) {
		return
			'<strong>' . _T('texte_recuperer_base') . '</strong><br />'
			. ' <tt>' . join(', ', $crash) . '</tt><br />'
			. generer_form_ecrire(
				'base_repair',
				_T('texte_crash_base'),
				'',
				_T('bouton_tenter_recuperation')
			);
	}
}
