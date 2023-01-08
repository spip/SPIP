<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function inc_log_dist($message, $logname = null, $logdir = null, $logsuf = null) {
	static $test_repertoire = [];
	static $compteur = [];
	static $debugverb = ''; // pour ne pas le recalculer au reappel

	if (is_null($logname) or !is_string($logname)) {
		$logname = defined('_FILE_LOG') ? _FILE_LOG : 'spip';
	}
	if (!isset($compteur[$logname])) {
		$compteur[$logname] = 0;
	}
	if (
		$logname != 'maj'
		and defined('_MAX_LOG')
		and (
			$compteur[$logname]++ > _MAX_LOG
			or !$GLOBALS['nombre_de_logs']
			or !$GLOBALS['taille_des_logs']
		)
	) {
		return;
	}

	// si spip_log() est appelé dans mes_options, toutes les constantes n'ont pas été définies
	$logfile =
		($logdir ?? (defined('_DIR_LOG') ? _DIR_LOG : _DIR_RACINE . _NOM_TEMPORAIRES_INACCESSIBLES))
		. $logname
		. ($logsuf ?? (defined('_FILE_LOG_SUFFIX') ? _FILE_LOG_SUFFIX : '.log'));

	if (!isset($test_repertoire[$d = dirname($logfile)])) {
		$test_repertoire[$d] = false; // eviter une recursivite en cas d'erreur de sous_repertoire
		$test_repertoire[$d] = (@is_dir($d) ? true : (function_exists('sous_repertoire') ? sous_repertoire(
			$d,
			'',
			false,
			true
		) : false));
	}

	// Si le repertoire défini n'existe pas, poser dans tmp/
	if (!$test_repertoire[$d]) {
		$logfile = _DIR_RACINE . _NOM_TEMPORAIRES_INACCESSIBLES . $logname . '.log';
	}

	$rotate = 0;
	$pid = '(pid ' . @getmypid() . ')';

	// accepter spip_log( Array )
	if (!is_string($message)) {
		$message = var_export($message, true);
	}

	if (!$debugverb and defined('_LOG_FILELINE') and _LOG_FILELINE) {
		$debug = debug_backtrace();
		$l = $debug[1]['line'];
		$fi = $debug[1]['file'];
		if (strncmp($fi, _ROOT_RACINE, strlen(_ROOT_RACINE)) == 0) {
			$fi = substr($fi, strlen(_ROOT_RACINE));
		}
		$fu = $debug[2]['function'] ?? '';
		$debugverb = "$fi:L$l:$fu" . '():';
	}

	$m = date('Y-m-d H:i:s') . ' ' . ($GLOBALS['ip'] ?? '') . ' ' . $pid . ' '
		//distinguer les logs prives et publics dans les grep
		. $debugverb
		. (test_espace_prive() ? ':Pri:' : ':Pub:')
		. preg_replace("/\n*$/", "\n", $message);


	if (
		@is_readable($logfile)
		and (!$s = @filesize($logfile) or $s > $GLOBALS['taille_des_logs'] * 1024)
	) {
		$rotate = $GLOBALS['nombre_de_logs'];
		$m .= "[-- rotate --]\n";
	}

	$f = @fopen($logfile, 'ab');
	if ($f) {
		fputs($f, (defined('_LOG_BRUT') and _LOG_BRUT) ? $m : str_replace('<', '&lt;', $m));
		fclose($f);
	}

	if (
		$rotate-- > 0
		and function_exists('spip_unlink')
	) {
		spip_unlink($logfile . '.' . $rotate);
		while ($rotate--) {
			@rename($logfile . ($rotate ? '.' . $rotate : ''), $logfile . '.' . ($rotate + 1));
		}
	}

	// Dupliquer les erreurs specifiques dans le log general
	if (
		defined('_FILE_LOG')
		and ($logname !== _FILE_LOG)
	) {
		inc_log_dist($logname == 'maj' ? 'cf maj.log' : $message);
	}
	$debugverb = '';
}
