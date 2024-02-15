<?php

use Monolog\Level;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Spip\Component\Filesystem\Filesystem;
use Spip\Component\Logger\Config;
use Spip\Component\Logger\Factory;
use Spip\Component\Logger\LineFormatter;

/**
 * Obtenir un logger compatible Psr\Log
 *
 * @api
 * @example
 * ```
 * spip_logger()->notice('mon message');
 *
 * $logger = spip_logger();
 * $logger->info('mon message');
 * $logger->debug('mon debug');
 *
 * $logger = spip_logger('mysql');
 * $logger->info('mon message sur le canal mysql');
 * $logger->debug('mon debug sur le canal mysql');
 * ```
 */
function spip_logger(?string $name = null): LoggerInterface {
	/* @var array<string,LoggerInterface> */
	static $loggers = [];
	/* @var ?Factory */
	static $loggerFactory = null;

	$name ??= 'spip';

	if ($loggerFactory === null) {
		$spipToMonologLevels = [
			Level::Emergency, // _LOG_HS
			Level::Alert,     // _LOG_ALERTE_ROUGE
			Level::Critical,  // _LOG_CRITIQUE
			Level::Error,     // _LOG_ERREUR
			Level::Warning,   // _LOG_AVERTISSEMENT
			Level::Notice,    // _LOG_INFO_IMPORTANTE
			Level::Info,      // _LOG_INFO
			Level::Debug,     // _LOG_DEBUG
		];

		$config = [
			'siteDir' => _ROOT_RACINE,
			'filesystem' => new Filesystem(),
			// max log par hit
			'max_log' => defined('_MAX_LOG') ? constant('_MAX_LOG') : null,
			// pour indiquer le chemin du fichier qui envoie le log
			'fileline' => defined('_LOG_FILELINE') ? constant('_LOG_FILELINE') : null,
			// échappement des log
			'brut' => defined('_LOG_BRUT') ? constant('_LOG_BRUT') : null,
			// à quel level on commence à logguer
			'max_level' => (function() use ($spipToMonologLevels): Level {
				if (!defined('_LOG_FILTRE_GRAVITE')) {
					return Level::Notice;
				}
				$level = constant('_LOG_FILTRE_GRAVITE');
				if ($level instanceof Level) {
					return $level;
				}
				if (isset($spipToMonologLevels[$level])) {
					return $spipToMonologLevels[$level];
				}
				return match($level) {
					LogLevel::EMERGENCY => Level::Emergency,
					LogLevel::ALERT => Level::Alert,
					LogLevel::CRITICAL => Level::Critical,
					LogLevel::ERROR => Level::Error,
					LogLevel::WARNING => Level::Warning,
					LogLevel::NOTICE => Level::Notice,
					LogLevel::INFO => Level::Info,
					LogLevel::DEBUG => Level::Debug,
					default => Level::Notice,
				};
			})(),
			// rotation: nombre de fichiers
			'max_files' => $GLOBALS['nombre_de_logs'] ??= 4,
			// rotation: taille max d’un fichier
			'max_size' => ($GLOBALS['taille_des_logs'] ??= 100) * 1024,
			// chemin du fichier de log
			'log_path' => (function() {
				$log_dir = defined('_DIR_LOG') ? str_replace(_DIR_RACINE, '', constant('_DIR_LOG')) : 'tmp/log/';
				$log_file = defined('_FILE_LOG') ? constant('_FILE_LOG') : 'spip';
				$log_suffix = defined('_FILE_LOG_SUFFIX') ? constant('_FILE_LOG_SUFFIX') : '.log';

				$log_file = str_replace('spip', '%s', $log_file);
				return sprintf('%s%s%s', $log_dir, $log_file, $log_suffix);
			})(),
		];
		$env = getenv('APP_ENV') ?? 'prod';
		if ($env === 'dev') {
			$config = [
				...$config,
				'fileline' => true,
				'max_level' => Level::Debug,
			];
		}
		$config = array_filter($config);

		$loggerFactory = new Factory(new Config(...$config), new LineFormatter());
		unset($args, $env, $spipToMonologLevels);
	}

    return $loggers[$name] ??= $loggerFactory->createFromFilename($name);
}


/**
 * Enregistrement des événements
 *
 * Signature : `spip_log(message, ?name)`
 *
 * @example
 *   ```
 *   # Les appels ci-dessous sont "deprecated" depuis 5.0
 *   spip_log($message)
 *   spip_log($message, 'recherche')
 *   spip_log($message, _LOG_DEBUG)
 *   spip_log($message, 'recherche.'._LOG_DEBUG)
 *   ```
 *
 * @api
 * @link https://programmer.spip.net/spip_log
 * @see spip_logger()
 * @deprecated 5.0 Utiliser spip_logger()
 *
 * @param mixed           $message Message à consigner
 * @param int|string|null $name    Nom du fichier de log, "spip" par défaut
 */
function spip_log($message, $name = null): void
{
    static $spipToMonologLevels = [
		Level::Emergency, // _LOG_HS
		Level::Alert,     // _LOG_ALERTE_ROUGE
		Level::Critical,  // _LOG_CRITIQUE
		Level::Error,     // _LOG_ERREUR
		Level::Warning,   // _LOG_AVERTISSEMENT
		Level::Notice,    // _LOG_INFO_IMPORTANTE
		Level::Info,      // _LOG_INFO
		Level::Debug,     // _LOG_DEBUG
    ];

	# Éviter de trop polluer les logs de dépréciation
	static $deprecated = [];

    preg_match('/^([a-z_]*)\.?(\d)?$/iS', (string) $name, $regs);
    $logFile = 'spip';
    if (isset($regs[1]) && strlen($regs[1])) {
        $logFile = $regs[1];
    }

	if (!isset($regs[2])) {
		$level = Level::Info;
	} else {
		$level = $spipToMonologLevels[intval($regs[2])] ?? Level::Info;
	}

	$logger = spip_logger($logFile);
	$logger->log($level, preg_replace(
        "/\n*$/",
        "\n",
        is_string($message) ? $message : print_r($message, true)
    ));


	if (!array_key_exists($logFile, $deprecated)) {
		$deprecated[$logFile] = true;
		if ($logFile === 'spip') {
			trigger_deprecation(
				'spip',
				'5.0',
				sprintf(
					'spip_log(\'message\') function is deprecated ; use spip_logger().'
					. ' Example: spip_logger()->info(\'message\').',
					$logFile,
					$logFile,
				)
			);
		} else {
			trigger_deprecation(
				'spip',
				'5.0',
				sprintf(
					'spip_log(\'message\', \'%s\') function is deprecated ; use spip_logger().'
					. ' Example: spip_logger(\'%s\')->info(\'message\').',
					$logFile,
					$logFile,
				)
			);
		}
	}
}


/**
 * Enregistrement des journaux
 *
 * @uses inc_journal_dist()
 * @param string $phrase texte du journal
 * @param array $opt Tableau d'options
 **/
function journal($phrase, $opt = []) {
	$journal = charger_fonction('journal', 'inc');
	$journal($phrase, $opt);
}
