<?php

namespace Spip;

/**
 * Gestion des erreurs PHP
 * @internal
 */
final class ErrorHandler {

	static bool $done = false;

	public static function setup(?int $error_level = null): void {
		if (!self::$done) {
			self::$done = true;
			error_reporting($error_level);
			set_error_handler(self::user_deprecated(...), E_USER_DEPRECATED);
		}
	}

	/** Loger les `trigger_deprecated()` */
	private static function user_deprecated(int $errno, string $errstr, string $errfile, int $errline): bool {
		if (!(\E_USER_DEPRECATED & $errno)) {
			return false;
		}

		$backtrace = debug_backtrace();
		array_shift($backtrace);
		do {
			$t = array_shift($backtrace);
			$fqdn = $t['class'] . $t['type'] . $t['function'];
		} while (in_array($fqdn, ['trigger_error', 'trigger_deprecation']));

		$errfile = $t['file'];
		$errline = $t['line'];

		spip_log(sprintf('%s in %s on line %s', $errstr, $errfile, $errline), 'deprecated.' . _LOG_INFO);
		return false;
	}
}
