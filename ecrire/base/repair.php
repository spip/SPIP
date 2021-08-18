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
 * Réparation de la base de données
 *
 * @package SPIP\Core\SQL\Reparation
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Action de réparation de la base de données
 *
 * Tente de réparer les tables, recalcule les héritages et secteurs
 * de rubriques. Affiche les erreurs s'il y en a eu.
 *
 * @pipeline_appel base_admin_repair
 * @uses admin_repair_tables()
 * @uses calculer_rubriques()
 * @uses propager_les_secteurs()
 *
 * @param string $titre Inutilisé
 * @param string $reprise Inutilisé
 **/
function base_repair_dist($titre = '', $reprise = '') {

	$res = admin_repair_tables();
	if (!$res) {
		$res = "<div class='error'>" . _T('avis_erreur_mysql') . ' ' . sql_errno() . ': ' . sql_error() . "</div>\n";
	} else {
		include_spip('inc/rubriques');
		calculer_rubriques();
		propager_les_secteurs();
	}
	include_spip('inc/minipres');
	$res .= pipeline('base_admin_repair', $res);
	echo minipres(
		_T('texte_tentative_recuperation'),
		$res . generer_form_ecrire('accueil', '', '', _T('public:accueil_site'))
	);
}

/**
 * Exécute une réparation de la base de données
 *
 * Crée les tables et les champs manquants.
 * Applique sur les tables un REPAIR en SQL (si le serveur SQL l'accepte).
 *
 * @return string
 *     Code HTML expliquant les actions réalisées
 **/
function admin_repair_tables() {

	$repair = sql_repair('repair', null, 'continue');

	// recreer les tables manquantes eventuelles
	include_spip('base/create');
	creer_base();
	$tables = sql_alltable();

	$res = '';
	foreach ($tables as $tab) {
		$class = '';
		$m = "<strong>$tab</strong> ";
		spip_log("Repare $tab", _LOG_INFO_IMPORTANTE);
		// supprimer la meta avant de lancer la reparation
		// car le repair peut etre long ; on ne veut pas boucler
		effacer_meta('admin_repair');
		if ($repair) {
			$result_repair = sql_repair($tab);
			if (!$result_repair) {
				return false;
			}
		}

		// essayer de maj la table (creation de champs manquants)
		maj_tables($tab);

		$count = sql_countsel($tab);

		if ($count > 1) {
			$m .= '(' . _T('texte_compte_elements', array('count' => $count)) . ")\n";
		} else {
			if ($count == 1) {
				$m .= '(' . _T('texte_compte_element', array('count' => $count)) . ")\n";
			} else {
				$m .= '(' . _T('texte_vide') . ")\n";
			}
		}

		if ($result_repair
			and $msg = join(
				' ',
				(is_resource($result_repair) or is_object($result_repair)) ? sql_fetch($result_repair) : $result_repair
			) . ' '
			and strpos($msg, ' OK ') === false
		) {
			$class = " class='notice'";
			$m .= '<br /><tt>' . spip_htmlentities($msg) . "</tt>\n";
		} else {
			$m .= ' ' . _T('texte_table_ok');
		}

		$res .= "<div$class>$m</div>";
	}

	return $res;
}
