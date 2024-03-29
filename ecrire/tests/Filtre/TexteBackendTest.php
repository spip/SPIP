<?php

declare(strict_types=1);

/**
 * Test unitaire de la fonction texte_backend du fichier inc/filtres.php
 */

namespace Spip\Test\Filtre;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TexteBackendTest extends TestCase
{
	public static function setUpBeforeClass(): void {
		find_in_path('inc/filtres.php', '', true);
	}

	#[DataProvider('providerFiltresTexteBackend')]
	public function testFiltresTexteBackend($expected, ...$args): void {
		$actual = texte_backend(...$args);
		$this->assertSame($expected, $actual);
	}

	public static function providerFiltresTexteBackend(): array {
		return [
			0 => [
				0 => '',
				1 => '',
			],
			1 => [
				0 => '0',
				1 => '0',
			],
			2 => [
				0 => 'Un texte avec des &lt;a href=&#034;http://spip.net&#034;&gt;liens&lt;/a&gt; [Article 1-&gt;art1] [spip-&gt;http://www.spip.net] http://www.spip.net',
				1 => 'Un texte avec des <a href="http://spip.net">liens</a> [Article 1->art1] [spip->http://www.spip.net] http://www.spip.net',
			],
			3 => [
				0 => 'Un texte avec des entit&#233;s &amp;&lt;&gt;&#034;',
				1 => 'Un texte avec des entit&eacute;s &amp;&lt;&gt;&quot;',
			],
			4 => [
				0 => "Un texte sans entites &amp;&lt;&gt;&#034;'",
				1 => 'Un texte sans entites &<>"\'',
			],
			5 => [
				0 => '{{{Des raccourcis}}} {italique} {{gras}} &lt;code&gt;du code&lt;/code&gt;',
				1 => '{{{Des raccourcis}}} {italique} {{gras}} <code>du code</code>',
			],
			6 => [
				0 => 'Un modele &lt;modeleinexistant|lien=[-&gt;http://www.spip.net]&gt;',
				1 => 'Un modele <modeleinexistant|lien=[->http://www.spip.net]>',
			],
		];
	}
}
