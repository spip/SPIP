<?php

/*
 * API Job Queue
 *
 * Ce fichier ne gere pas de file d'attente, sa fonction queue_add_job() execute
 * immediatement la tache demandee. Mais l'API est celle du plugin job_queue qui
 * lui sait gerer une file d'attente etc.
 *
 * (c) 2009 Cedric&Fil
 * Distribue sous licence GPL
 *
 */


/**
 * Add a job to the queue. The function added will be called in the order it
 * was added during cron.
 *
 * @param $function
 *   The function name to call.
 * @param $description
 *   A human-readable description of the queued job.
 * @param $arguments
 *   Optional array of arguments to pass to the function.
 * @param $file
 *   Optional file path which needs to be included for $fucntion.
 * @param $no_duplicate
 *   If TRUE, do not add the job to the queue if one with the same function and
 *   arguments already exists.
 *	 If 'function_only' test of existence is only on function name (for cron job)
 * @param $time
 *		time for starting the job. If 0, job will start as soon as possible
 * @param $priority
 *		-10 (low priority) to +10 (high priority), 0 is the default
 * @return int
 *	id of job
 */
function queue_add_job($function, $description, $arguments = array(), $file = '', $no_duplicate = false, $time=0, $priority=0){

	// include $file?
	if (strlen($inclure = trim($file))){
		if (substr($inclure,-1)=='/'){ // c'est un chemin pour charger_fonction
			$f = charger_fonction($function,rtrim($inclure,'/'),false);
			if ($f)
				$function = $f;
		}
		else
			include_spip($inclure);
	}

	// check that $function exists
	if (!function_exists($function)){
		spip_log("fonction $function ($inclure) inexistante ".var_export($row,true),'queue');
		return false;
	}

	// execute immediately
	return call_user_func_array($function, $arguments);

}

?>
