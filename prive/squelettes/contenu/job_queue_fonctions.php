<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

	function job_queue_block_and_watch(){
		// bloquer la queue sur ce hit
		// pour avoir coherence entre l'affichage de la liste de jobs
		// et les jobs en base en fin de hit
		define('_DEBUG_BLOCK_QUEUE',true);
		include_spip('inc/genie');
		genie_queue_watch_dist();
	}

?>