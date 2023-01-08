<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

/**
 * Gestion de l'action purger pour nettoyer le cache
 *
 * @package SPIP\Core\Cache
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Action de purge du cache
 *
 * L'argument peut être :
 *
 * - inhibe_cache : inhibe le cache pendant 24h
 * - reactive_cache : enlève l'inhibition du cache
 * - cache : nettoie tous les caches (sauf celui des vignettes)
 * - squelettes : nettoie le cache de compilation des squelettes
 * - vignettes : nettoie le cache des vignettes (et compressions css/js)
 *
 * @pipeline_appel trig_purger
 * @uses supprime_invalideurs()
 * @uses purger_repertoire()
 *
 * @param string|null $arg
 *     Argument attendu. En absence utilise l'argument
 *     de l'action sécurisée.
 */
function action_purger_dist($arg = null) {
	if (is_null($arg)) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}

	include_spip('inc/invalideur');

	spip_log("purger $arg");

	switch ($arg) {
		case 'inhibe_cache':
			// inhiber le cache pendant 24h
			ecrire_meta('cache_inhib', $_SERVER['REQUEST_TIME'] + 24 * 3600);
			break;
		case 'reactive_cache':
			effacer_meta('cache_inhib');
			break;

		case 'cache':
			supprime_invalideurs();
			@spip_unlink(_CACHE_RUBRIQUES);
			@spip_unlink(_CACHE_CHEMIN);
			@spip_unlink(_DIR_TMP . 'plugin_xml_cache.gz');
			// on ne supprime que _CACHE_PLUGINS_OPT qui declenche la reconstruction des 3
			// _CACHE_PIPELINES _CACHE_PLUGINS_PATH et _CACHE_PLUGINS_FCT
			// pour eviter des problemes de concurence
			// cf https://core.spip.net/issues/2989
			//@spip_unlink(_CACHE_PIPELINES);
			//@spip_unlink(_CACHE_PLUGINS_PATH);
			//@spip_unlink(_CACHE_PLUGINS_FCT);
			@spip_unlink(_CACHE_PLUGINS_OPT);
			purger_repertoire(_DIR_CACHE, ['subdir' => true]);
			purger_repertoire(_DIR_AIDE);
			purger_repertoire(_DIR_VAR . 'cache-css');
			purger_repertoire(_DIR_VAR . 'cache-js');
			break;

		case 'squelettes':
			purger_repertoire(_DIR_SKELS);
			break;

		case 'vignettes':
			purger_repertoire(_DIR_VAR . 'cache-gd2', ['subdir' => true]);
			purger_repertoire(_DIR_VAR . 'cache-texte', ['subdir' => true]);
			purger_repertoire(_DIR_VAR . 'cache-vignettes', ['subdir' => true]);
			purger_repertoire(_DIR_VAR . 'cache-TeX', ['subdir' => true]);
			supprime_invalideurs();
			purger_repertoire(_DIR_CACHE, ['subdir' => true]);
			break;
	}

	// le faire savoir aux plugins
	pipeline('trig_purger', $arg);
}
