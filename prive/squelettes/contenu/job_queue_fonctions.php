<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function job_queue_block_and_watch() {
	// bloquer la queue sur ce hit
	// pour avoir coherence entre l'affichage de la liste de jobs
	// et les jobs en base en fin de hit
	define('_DEBUG_BLOCK_QUEUE', true);
	include_spip('inc/genie');
	genie_queue_watch_dist();
}

/**
 * Prévisu d'un appel à une fonction avec ses arguments
 *
 * @param string $function
 * @param string $args
 * @return string
 */
function job_queue_display_call(string $function, string $args): string {
	$args = unserialize($args);
	$args = array_map(function($arg) {
		return is_scalar($arg) ? $arg : get_debug_type($arg);
	}, $args);

	return sprintf('%s(%s)', $function, implode(', ', $args));
}
