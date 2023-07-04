<?php

declare(strict_types=1);

/**
 * Test unitaire de la fonction interdire_script du fichier inc/texte.php
 */

namespace Spip\Test\Texte;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class InterdireScriptParanoTest extends TestCase
{
	protected static $save_filtrer_javascript;

	public static function setUpBeforeClass(): void {
		self::$save_filtrer_javascript = $GLOBALS['filtrer_javascript'];
		find_in_path('inc/texte.php', '', true);
	}

	public static function tearDownAfterClass(): void {
		$GLOBALS['filtrer_javascript'] = self::$save_filtrer_javascript;
	}

	protected function setUp(): void {
		$GLOBALS['filtrer_javascript'] = -1;
	}

	#[DataProvider('providerTexteInterdireScriptParano')]
	public function testTexteInterdireScriptParano($expected, ...$args): void {
		$actual = interdire_scripts(...$args);
		$this->assertSame($expected, $actual);
	}

	public static function providerTexteInterdireScriptParano(): array {
		return [
			[
				"<code class=\"echappe-js\">&lt;script src='toto.js'&gt;&lt;/script&gt;</code>", "<script src='toto.js'></script>",
			],
			[
				"<code class=\"echappe-js\">&lt;script src='spip.php?page=toto'&gt;&lt;/script&gt;</code>",
				"<script src='spip.php?page=toto'></script>",
			],
			[
				"<code class=\"echappe-js\">&lt;script&gt;var php=5;&lt;/script&gt;</code>",
				"<script>var php=5;</script>",
			],
			[
				"<code class=\"echappe-js\">&lt;script language='javascript' src='spip.php?page=toto'&gt;&lt;/script&gt;</code>",
				"<script language='javascript' src='spip.php?page=toto'></script>",
			],
			["&lt;script language='php'>die();</script>", "<script language='php'>die();</script>"],
			['&lt;script language=php>die();</script>', '<script language=php>die();</script>'],
			['&lt;script language = php >die();</script>', '<script language = php >die();</script>'],
		];
	}
}
