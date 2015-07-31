<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

// http://doc.spip.org/@action_purger_dist
function action_purger_dist($arg=null)
{
	if (is_null($arg)) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}

	include_spip('inc/invalideur');

	spip_log("purger $arg");

	switch ($arg) {
		case 'inhibe_cache':
			// inhiber le cache pendant 24h
			ecrire_meta('cache_inhib',$_SERVER['REQUEST_TIME']+24*3600);
			break;
		case 'reactive_cache':
			effacer_meta('cache_inhib');
			break;

		case 'cache':
			supprime_invalideurs();
			@spip_unlink(_CACHE_RUBRIQUES);
			@spip_unlink(_CACHE_CHEMIN);
			@spip_unlink(_DIR_TMP."plugin_xml_cache.gz");
			// on ne supprime que _CACHE_PLUGINS_OPT qui declenche la reconstruction des 3
			// _CACHE_PIPELINES _CACHE_PLUGINS_PATH et _CACHE_PLUGINS_FCT
			// pour eviter des problemes de concurence
			// cf http://core.spip.org/issues/2989
			//@spip_unlink(_CACHE_PIPELINES);
			//@spip_unlink(_CACHE_PLUGINS_PATH);
			//@spip_unlink(_CACHE_PLUGINS_FCT);
			@spip_unlink(_CACHE_PLUGINS_OPT);
			purger_repertoire(_DIR_CACHE,array('subdir'=>true));
			purger_repertoire(_DIR_AIDE);
			purger_repertoire(_DIR_VAR.'cache-css');
			purger_repertoire(_DIR_VAR.'cache-js');
			break;

		case 'squelettes':
			purger_repertoire(_DIR_SKELS);
			break;

		case 'vignettes':
			purger_repertoire(_DIR_VAR,array('subdir'=>true));
			supprime_invalideurs();
			purger_repertoire(_DIR_CACHE);
			break;
	}

	// le faire savoir aux plugins
	pipeline('trig_purger',$arg);
}

?>
