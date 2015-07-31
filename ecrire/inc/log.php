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

function inc_log_dist($message, $logname=NULL, $logdir=NULL, $logsuf=NULL) {
	static $test_repertoire = array();
	static $compteur = array();
	static $debugverb = ""; // pour ne pas le recalculer au reappel
	global $nombre_de_logs, $taille_des_logs;

	if (is_null($logname) OR !is_string($logname))
		$logname = defined('_FILE_LOG') ? _FILE_LOG : 'spip';
	if (!isset($compteur[$logname])) $compteur[$logname] = 0;
	if ($logname != 'maj'
	AND defined('_MAX_LOG')
	AND (
		$compteur[$logname]++ > _MAX_LOG
		OR !$nombre_de_logs
		OR !$taille_des_logs
	))
		return;

	$logfile = ($logdir===NULL ? _DIR_LOG : $logdir)
	  . ($logname)
	  . ($logsuf===NULL ? _FILE_LOG_SUFFIX : $logsuf);

	if (!isset($test_repertoire[$d = dirname($logfile)])) {
		$test_repertoire[$d] = false; // eviter une recursivite en cas d'erreur de sous_repertoire
		$test_repertoire[$d] = (@is_dir($d)?true:(function_exists('sous_repertoire')?sous_repertoire($d, '', false, true):false));
	}

	// si spip_log() dans mes_options, ou repertoire log/ non present, poser dans tmp/
	if (!defined('_DIR_LOG') OR !$test_repertoire[$d])
		$logfile = _DIR_RACINE._NOM_TEMPORAIRES_INACCESSIBLES.$logname.'.log';

	$rotate = 0;
	$pid = '(pid '.@getmypid().')';

	// accepter spip_log( Array )
	if (!is_string($message)) $message = var_export($message, true);

	if (!$debugverb AND defined('_LOG_FILELINE') AND _LOG_FILELINE){
		$debug = debug_backtrace();
		$l = $debug[1]['line'];
		$fi = $debug[1]['file'];
		if (strncmp($fi,_ROOT_RACINE,strlen(_ROOT_RACINE))==0)
			$fi = substr($fi,strlen(_ROOT_RACINE));
		$fu = isset($debug[2]['function']) ? $debug[2]['function'] : '';
		$debugverb = "$fi:L$l:$fu"."():";
	}

	$m = date("M d H:i:s").' '.$GLOBALS['ip'].' '.$pid.' '
	  //distinguer les logs prives et publics dans les grep
		. $debugverb
		. (test_espace_prive()?':Pri:':':Pub:')
		.preg_replace("/\n*$/", "\n", $message);


	if (@is_readable($logfile)
	AND (!$s = @filesize($logfile) OR $s > $taille_des_logs * 1024)) {
		$rotate = $nombre_de_logs;
		$m .= "[-- rotate --]\n";
	}

	$f = @fopen($logfile, "ab");
	if ($f) {
		fputs($f, (defined('_LOG_BRUT') AND _LOG_BRUT) ? $m : str_replace('<','&lt;',$m));
		fclose($f);
	}

	if ($rotate-- > 0
	AND function_exists('spip_unlink')) {
		spip_unlink($logfile . '.' . $rotate);
		while ($rotate--) {
			@rename($logfile . ($rotate ? '.' . $rotate : ''), $logfile . '.' . ($rotate + 1));
		}
	}

	// Dupliquer les erreurs specifiques dans le log general
	if ($logname !== _FILE_LOG
	  AND defined('_FILE_LOG'))
		inc_log_dist($logname=='maj' ? 'cf maj.log' : $message);
	$debugverb = "";
}

?>
