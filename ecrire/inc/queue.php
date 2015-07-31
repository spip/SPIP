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

/**
 * Gestion des queues de travaux
 *
 * @package SPIP\Queue
**/
if (!defined("_ECRIRE_INC_VERSION")) return;

define('_JQ_SCHEDULED',1);
define('_JQ_PENDING',0);
#define('_JQ_MAX_JOBS_EXECUTE',200); // pour personaliser le nombre de jobs traitables a chaque hit
#define('_JQ_MAX_JOBS_TIME_TO_EXECUTE',15); // pour personaliser le temps d'excution dispo a chaque hit
#define('_JQ_NB_JOBS_OVERFLOW',10000); // nombre de jobs a partir duquel on force le traitement en fin de hit pour purger

/**
 * Ajouter une tâche à la file
 * 
 * Les tâches sont ensuites exécutées par date programmée croissant/priorité décroissante
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
	include_spip('base/abstract_sql');

	// cas pourri de ecrire/action/editer_site avec l'option reload=oui
	if (defined('_GENIE_SYNDIC_NOW'))
		$arguments['id_syndic'] = _GENIE_SYNDIC_NOW;

	// serialiser les arguments
	$arguments = serialize($arguments);
	$md5args = md5($arguments);

	// si pas de date programee, des que possible
	$duplicate_where = 'status='.intval(_JQ_SCHEDULED).' AND ';
	if (!$time){
		$time = time();
		$duplicate_where = ""; // ne pas dupliquer si deja le meme job en cours d'execution
	}
	$date = date('Y-m-d H:i:s',$time);

	$set_job = array(
		'fonction'=>$function,
		'descriptif'=>$description,
		'args'=>$arguments,
		'md5args'=>$md5args,
		'inclure'=>$file,
		'priorite'=>max(-10,min(10,intval($priority))),
		'date'=>$date,
		'status'=>_JQ_SCHEDULED,
	);
	// si option ne pas dupliquer, regarder si la fonction existe deja
	// avec les memes args et file
	if (
			$no_duplicate
		AND
			$id_job = sql_getfetsel('id_job','spip_jobs',
				$duplicate_where =
					$duplicate_where . 'fonction='.sql_quote($function)
				.(($no_duplicate==='function_only')?'':
				 ' AND md5args='.sql_quote($md5args).' AND inclure='.sql_quote($file)))
		)
		return $id_job;

	$id_job = sql_insertq('spip_jobs',$set_job);
	// en cas de concurrence, deux process peuvent arriver jusqu'ici en parallele
	// avec le meme job unique a inserer. Dans ce cas, celui qui a eu l'id le plus grand
	// doit s'effacer
	if (
			$no_duplicate
		AND
			$id_prev = sql_getfetsel('id_job','spip_jobs',"id_job<".intval($id_job)." AND $duplicate_where")){
		sql_delete('spip_jobs','id_job='.intval($id_job));
		return $id_prev;
	}

	// verifier la non duplication qui peut etre problematique en cas de concurence
	// il faut dans ce cas que seul le dernier ajoute se supprime !

	// une option de debug pour verifier que les arguments en base sont bons
	// ie cas d'un char non acceptables sur certains type de champs
	// qui coupe la valeur
	if (defined('_JQ_INSERT_CHECK_ARGS') AND $id_job) {
		$args = sql_getfetsel('args', 'spip_jobs', 'id_job='.intval($id_job));
		if ($args!==$arguments) {
			spip_log('arguments job errones / longueur '.strlen($args)." vs ".strlen($arguments).' / valeur : '.var_export($arguments,true),'queue');
		}
	}

	if ($id_job){
		queue_update_next_job_time($time);
	}
	// si la mise en file d'attente du job echoue,
	// il ne faut pas perdre l'execution de la fonction
	// on la lance immediatement, c'est un fallback
	// sauf en cas d'upgrade necessaire (table spip_jobs inexistante)
	elseif($GLOBALS['meta']['version_installee']==$GLOBALS['spip_version_base']) {
		$set_job['id_job'] = 0;
		queue_start_job($set_job);
	}

	return $id_job;
}

/**
 * Purger la file de tâche et reprgrammer les tâches périodiques
 * 
 * @return void
 */
function queue_purger(){
	include_spip('base/abstract_sql');
	sql_delete('spip_jobs');
  sql_delete("spip_jobs_liens","id_job NOT IN (".sql_get_select("id_job","spip_jobs").")");
  include_spip('inc/genie');
  genie_queue_watch_dist();
}

/**
 * Retirer une tache de la file d'attente
 * @param int $id_job
 *  id de la tache a retirer
 * @return bool
 */
function queue_remove_job($id_job){
	include_spip('base/abstract_sql');

	if ($row = sql_fetsel('fonction,inclure,date','spip_jobs','id_job='.intval($id_job))
	 AND $res = sql_delete('spip_jobs','id_job='.intval($id_job))){
		queue_unlink_job($id_job);
		// est-ce une tache cron qu'il faut relancer ?
		if ($periode = queue_is_cron_job($row['fonction'],$row['inclure'])){
			// relancer avec les nouveaux arguments de temps
			include_spip('inc/genie');
			// relancer avec la periode prevue
			queue_genie_replan_job($row['fonction'],$periode,strtotime($row['date']));
		}
		queue_update_next_job_time();
	}
	return $res;
}

/**
 * Associer une tache avec un objet
 *
 * @param int $id_job
 *	id of job to link
 * @param array $objets
 *  can be a simple array('objet'=>'article','id_objet'=>23)
 *  or an array of simple array to link multiples objet in one time
 */
function queue_link_job($id_job,$objets){
	include_spip('base/abstract_sql');

	if (is_array($objets) AND count($objets)){
		if (is_array(reset($objets))){
			foreach($objets as $k=>$o){
				$objets[$k]['id_job'] = $id_job;
			}
			sql_insertq_multi('spip_jobs_liens',$objets);
		}
		else
			sql_insertq('spip_jobs_liens',array_merge(array('id_job'=>$id_job),$objets));
	}
}

/**
 * Dissocier une tache d'un objet
 *
 * @param int $id_job
 *	id of job to unlink ibject with
 * @return int/bool
 *	result of sql_delete
 */
function queue_unlink_job($id_job){
	return sql_delete("spip_jobs_liens","id_job=".intval($id_job));
}

/**
 * Lancer une tache decrite par sa ligne SQL
 * @param array $row
 *	describe the job, with field of table spip_jobs
 * @return mixed
 *	return the result of job
 */
function queue_start_job($row){

	// deserialiser les arguments
	$args = unserialize($row['args']);
	if ($args===false){
		spip_log('arguments job errones '.var_export($row,true),'queue');
		$args = array();
	}

	$fonction = $row['fonction'];
	if (strlen($inclure = trim($row['inclure']))){
		if (substr($inclure,-1)=='/'){ // c'est un chemin pour charger_fonction
			$f = charger_fonction($fonction,rtrim($inclure,'/'),false);
			if ($f)
				$fonction = $f;
		}
		else
			include_spip($inclure);
	}

	if (!function_exists($fonction)){
		spip_log("fonction $fonction ($inclure) inexistante ".var_export($row,true),'queue');
		return false;
	}

	spip_log("queue [".$row['id_job']."]: $fonction() start", 'queue');
	switch (count($args)) {
		case 0:	$res = $fonction(); break;
		case 1:	$res = $fonction($args[0]); break;
		case 2:	$res = $fonction($args[0],$args[1]); break;
		case 3:	$res = $fonction($args[0],$args[1], $args[2]); break;
		case 4:	$res = $fonction($args[0],$args[1], $args[2], $args[3]); break;
		case 5:	$res = $fonction($args[0],$args[1], $args[2], $args[3], $args[4]); break;
		case 6:	$res = $fonction($args[0],$args[1], $args[2], $args[3], $args[4], $args[5]); break;
		case 7:	$res = $fonction($args[0],$args[1], $args[2], $args[3], $args[4], $args[5], $args[6]); break;
		case 8:	$res = $fonction($args[0],$args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7]); break;
		case 9:	$res = $fonction($args[0],$args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7], $args[8]); break;
		case 10:$res = $fonction($args[0],$args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7], $args[8], $args[9]); break;
		default:
			# plus lent mais completement generique
			$res = call_user_func_array($fonction, $args);
	}
	spip_log("queue [".$row['id_job']."]: $fonction() end", 'queue');
	return $res;

}

/**
 * Scheduler :
 * Prend une par une les taches en attente
 * et les lance, dans la limite d'un temps disponible total
 * et d'un nombre maxi de taches
 *
 * La date de la prochaine tache a executer est mise a jour
 * apres chaque chaque tache finie
 * afin de relancer le scheduler uniquement quand c'est necessaire
 *
 * @param array $force_jobs
 *   list of id_job to execute when provided
 * @return null|false
 */
function queue_schedule($force_jobs = null){
	$time = time();
	if (defined('_DEBUG_BLOCK_QUEUE')) {
		spip_log("_DEBUG_BLOCK_QUEUE : schedule stop",'jq'._LOG_DEBUG);
		return;
	}

	// rien a faire si le prochain job est encore dans le futur
	if (queue_sleep_time_to_next_job() AND (!$force_jobs OR !count($force_jobs))){
		spip_log("queue_sleep_time_to_next_job",'jq'._LOG_DEBUG);
		return;
	}

	include_spip('base/abstract_sql');
	// on ne peut rien faire si pas de connexion SQL
	if (!spip_connect())  return false;

	if (!defined('_JQ_MAX_JOBS_TIME_TO_EXECUTE')){
		$max_time = ini_get('max_execution_time')/2;
		// valeur conservatrice si on a pas reussi a lire le max_execution_time
		if (!$max_time) $max_time=5;
		define('_JQ_MAX_JOBS_TIME_TO_EXECUTE',min($max_time,15)); // une valeur maxi en temps.
	}
	$end_time = $time + _JQ_MAX_JOBS_TIME_TO_EXECUTE;

	spip_log("JQ schedule $time / $end_time",'jq'._LOG_DEBUG);

	if (!defined('_JQ_MAX_JOBS_EXECUTE'))
		define('_JQ_MAX_JOBS_EXECUTE',200);
	$nbj=0;
	// attraper les jobs
	// dont la date est passee (echus en attente),
	// par odre :
	//	- de priorite
	//	- de date
	// lorsqu'un job cron n'a pas fini, sa priorite est descendue
	// pour qu'il ne bloque pas les autres jobs en attente
	if (is_array($force_jobs) AND count($force_jobs))
		$cond = "status=".intval(_JQ_SCHEDULED)." AND ".sql_in("id_job", $force_jobs);
	else {
		$now = date('Y-m-d H:i:s',$time);
		$cond = "status=".intval(_JQ_SCHEDULED)." AND date<=".sql_quote($now);
	}

	register_shutdown_function('queue_error_handler'); // recuperer les erreurs auant que possible
	$res = sql_allfetsel('*','spip_jobs',$cond,'','priorite DESC,date','0,'.(_JQ_MAX_JOBS_EXECUTE+1));
	do {
		if ($row = array_shift($res)){
			$nbj++;
			// il faut un verrou, a base de sql_delete
			if (sql_delete('spip_jobs',"id_job=".intval($row['id_job'])." AND status=".intval(_JQ_SCHEDULED))){
				#spip_log("JQ schedule job ".$nbj." OK",'jq');
				// on reinsert dans la base aussitot avec un status=_JQ_PENDING
				$row['status'] = _JQ_PENDING;
				$row['date'] = date('Y-m-d H:i:s',$time);
				sql_insertq('spip_jobs', $row);

				// on a la main sur le job :
				// l'executer
				$result = queue_start_job($row);

				$time = time();
				queue_close_job($row, $time, $result);
			}
		}
		spip_log("JQ schedule job end time ".$time,'jq'._LOG_DEBUG);
	} while ($nbj<_JQ_MAX_JOBS_EXECUTE AND $row AND $time<$end_time);
	spip_log("JQ schedule end time ".time(),'jq'._LOG_DEBUG);

	if ($row = array_shift($res)){
		queue_update_next_job_time(0); // on sait qu'il y a encore des jobs a lancer ASAP
		spip_log("JQ encore !",'jq'._LOG_DEBUG);
	}
	else
		queue_update_next_job_time();

	return true;
}

/**
 * Terminer un job au status _JQ_PENDING :
 *  - le reprogrammer si c'est un cron
 *  - supprimer ses liens
 *  - le detruire en dernier
 *
 * @param array $row
 * @param int $time
 * @param int $result
 */
function queue_close_job(&$row,$time,$result=0){
	// est-ce une tache cron qu'il faut relancer ?
	if ($periode = queue_is_cron_job($row['fonction'],$row['inclure'])){
		// relancer avec les nouveaux arguments de temps
		include_spip('inc/genie');
		if ($result<0)
			// relancer tout de suite, mais en baissant la priorite
			queue_genie_replan_job($row['fonction'],$periode,0-$result,null,$row['priorite']-1);
		else
			// relancer avec la periode prevue
			queue_genie_replan_job($row['fonction'],$periode,$time);
	}
	// purger ses liens eventuels avec des objets
	sql_delete("spip_jobs_liens","id_job=".intval($row['id_job']));
	// supprimer le job fini
	sql_delete('spip_jobs','id_job='.intval($row['id_job']));
}

/**
 * Recuperer des erreurs auant que possible
 * en terminant la gestion de la queue
 */
function queue_error_handler(){
	// se remettre dans le bon dossier, car Apache le change parfois (toujours?)
	chdir(_ROOT_CWD);

	queue_update_next_job_time();
}


/**
 * Tester si une tache etait une tache periodique a reprogrammer
 *
 * @param <type> $function
 * @param <type> $inclure
 * @return <type>
 */
function queue_is_cron_job($function,$inclure){
	static $taches = null;
	if (strncmp($inclure,'genie/',6)==0){
		if (is_null($taches)){
			include_spip('inc/genie');
			$taches = taches_generales();
		}
		if (isset($taches[$function]))
			return $taches[$function];
	}
	return false;
}

/**
 * Mettre a jour la date du prochain job a lancer
 * Si une date est fournie (au format time unix)
 * on fait simplement un min entre la date deja connue et celle fournie
 * (cas de l'ajout simple
 * ou cas $next_time=0 car l'on sait qu'il faut revenir ASAP)
 *
 * @param int $next_time
 *	temps de la tache ajoutee ou 0 pour ASAP
 */
function queue_update_next_job_time($next_time=null){
	static $nb_jobs_scheduled = null;
	static $deja_la = false;
	// prendre le min des $next_time que l'on voit passer ici, en cas de reentrance
	static $next = null;
	// queue_close_job peut etre reentrant ici
	if ($deja_la) return;
	$deja_la = true;

	include_spip('base/abstract_sql');
	$time = time();

	// traiter les jobs morts au combat (_JQ_PENDING depuis plus de 180s)
	// pour cause de timeout ou autre erreur fatale
	$res = sql_allfetsel("*","spip_jobs","status=".intval(_JQ_PENDING)." AND date<".sql_quote(date('Y-m-d H:i:s',$time-180)));
	if (is_array($res)) {
		foreach ($res as $row)
			queue_close_job($row,$time);
	}

	// chercher la date du prochain job si pas connu
	if (is_null($next) OR is_null(queue_sleep_time_to_next_job())){
		$date = sql_getfetsel('date','spip_jobs',"status=".intval(_JQ_SCHEDULED),'','date','0,1');
		$next = strtotime($date);
	}
	if (!is_null($next_time)){
		if (is_null($next) OR $next>$next_time)
			$next = $next_time;
	}

		if ($next){
			if (is_null($nb_jobs_scheduled))
				$nb_jobs_scheduled = sql_countsel('spip_jobs',"status=".intval(_JQ_SCHEDULED)." AND date<".sql_quote(date('Y-m-d H:i:s',$time)));
			elseif ($next<=$time)
				$nb_jobs_scheduled++;
			// si trop de jobs en attente, on force la purge en fin de hit
			// pour assurer le coup
			if ($nb_jobs_scheduled>defined('_JQ_NB_JOBS_OVERFLOW')?_JQ_NB_JOBS_OVERFLOW:10000)
				define('_DIRECT_CRON_FORCE',true);
		}

	queue_set_next_job_time($next);
	$deja_la = false;
}


/**
 * Mettre a jour la date de prochain job
 * @param int $next
 */
function queue_set_next_job_time($next) {

	// utiliser le temps courant reel plutot que temps de la requete ici
	$time = time();

	// toujours relire la valeur pour comparer, pour tenir compte des maj concourrantes
	// et ne mettre a jour que si il y a un interet a le faire
	// permet ausis d'initialiser le nom de fichier a coup sur
	$curr_next = $_SERVER['REQUEST_TIME'] + queue_sleep_time_to_next_job(true);
	if (
			($curr_next<=$time AND $next>$time) // le prochain job est dans le futur mais pas la date planifiee actuelle
			OR $curr_next>$next // le prochain job est plus tot que la date planifiee actuelle
		) {
		if (include_spip('inc/memoization') AND defined('_MEMOIZE_MEMORY') AND _MEMOIZE_MEMORY) {
			cache_set(_JQ_NEXT_JOB_TIME_FILENAME,intval($next));
		}
		else {
			ecrire_fichier(_JQ_NEXT_JOB_TIME_FILENAME,intval($next));
		}
		queue_sleep_time_to_next_job($next);
	}

	return queue_sleep_time_to_next_job();
}

/**
 * Déclenche le cron en asynchrone ou retourne le code HTML pour le déclencher
 * 
 * Retourne le HTML à ajouter à la page pour declencher le cron
 * ou rien si on a réussi à le lancer en asynchrone.
 * 
 * @return string
 */
function queue_affichage_cron(){
	$texte = "";

	// rien a faire si le prochain job est encore dans le futur
	if (queue_sleep_time_to_next_job() OR defined('_DEBUG_BLOCK_QUEUE'))
		return $texte;

	// ne pas relancer si on vient de lancer dans la meme seconde par un hit concurent
	if (file_exists($lock=_DIR_TMP."cron.lock") AND !(@filemtime($lock)<$_SERVER['REQUEST_TIME']))
		return $texte;
	@touch($lock);

	// il y a des taches en attentes

	$url_cron = generer_url_action('cron','',false,true);

	if (!defined('_HTML_BG_CRON_FORCE') OR !_HTML_BG_CRON_FORCE){

		// methode la plus rapide :
		// Si fsockopen est possible, on lance le cron via un socket en asynchrone
		// si fsockopen echoue (disponibilite serveur, firewall) on essaye pas cURL
		// car on a toutes les chances d'echouer pareil mais sans moyen de le savoir
		// on passe direct a la methode background-image
		if(function_exists('fsockopen')){
			$parts=parse_url($url_cron);

			switch ($parts['scheme']) {
				case 'https':
					$scheme = 'ssl://';
					$port = 443;
					break;
				case 'http':
				default:
					$scheme = '';
					$port = 80;
			}

			$fp = @fsockopen($scheme.$parts['host'],
		        isset($parts['port'])?$parts['port']:$port,
		        $errno, $errstr, 1);

			if ($fp) {
				$query = $parts['path'].($parts['query']?"?".$parts['query']:"");
				$out = "GET ".$query." HTTP/1.1\r\n";
				$out.= "Host: ".$parts['host']."\r\n";
				$out.= "Connection: Close\r\n\r\n";
				fwrite($fp, $out);
				fclose($fp);
				return $texte;
			}
		}
		// si fsockopen n'est pas dispo on essaye cURL :
		// lancer le cron par un cURL asynchrone si cURL est present
		elseif (function_exists("curl_init")){
			//setting the curl parameters.
			$ch = curl_init($url_cron);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			// cf bug : http://www.php.net/manual/en/function.curl-setopt.php#104597
			curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
			// valeur mini pour que la requete soit lancee
			curl_setopt($ch, CURLOPT_TIMEOUT_MS, 100);
			// lancer
			curl_exec($ch);
			// fermer
			curl_close($ch);
			return $texte;
		}
	}

	// si deja force, on retourne sans rien
	if (defined('_DIRECT_CRON_FORCE'))
		return $texte;

	// si c'est un bot
	// inutile de faire un appel par image background,
	// on force un appel direct en fin de hit
	if ((defined('_IS_BOT') AND _IS_BOT)){
		define('_DIRECT_CRON_FORCE',true);
		return $texte;
	}

	// en derniere solution, on insere une image background dans la page
	$texte = '<!-- SPIP-CRON --><div style="background-image: url(\'' .
		generer_url_action('cron') .
		'\');"></div>';

	return $texte;
}
?>
