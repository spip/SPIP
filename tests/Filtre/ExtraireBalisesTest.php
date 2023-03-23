<?php

declare(strict_types=1);

/**
 * Test unitaire de la fonction extraire_multi du fichier ./inc/filtres.php
 */

namespace Spip\Core\Tests\Filtre;

use PHPUnit\Framework\TestCase;

class ExtraireBalisesTest extends TestCase
{
	public static function setUpBeforeClass(): void
	{
		find_in_path('./inc/filtres.php', '', true);
		find_in_path('./inc/lang.php', '', true);
	}

	public function testFiltresExtraireBalisesMediaRss(): void
	{

		$rss = file_get_contents(dirname(__DIR__) . '/Fixtures/data/dailymotion.rss');
		if (empty($rss)) {
			$this->markTestSkipped();
		}

		$balises_media = extraire_balises($rss, 'media:content');
		$this->assertIsArray($balises_media);
		$this->assertEquals(count($balises_media), 40);
	}


	/**
	 * @dataProvider providerFiltresExtraireBalises
	 */
	public function testFiltresExtraireBalises($expected, ...$args): void
	{
		$actual = extraire_balises(...$args);
		$this->assertSame($expected, $actual);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @dataProvider providerFiltresExtraireBalises
	 */
	public function testFiltresExtraireBalise($expected, ...$args): void
	{
		// extraire_balise doit renvoyer le premier résultat de extraire_balises
		// sauf si on fournit un tableau de chaine en entree, ce doit être alors le premier résultat de chaque sous-tableau
		$first_result = reset($expected);
		if (is_array($first_result)) {
			$first_result = [];
			foreach ($expected as $e) {
				$first_result[] = (empty($e) ? null : reset($e));
			}
			$expected = $first_result;
		} else {
			$expected = (empty($expected) ? null : $first_result);
		}
		$actual = extraire_balise(...$args);
		$this->assertSame($expected, $actual);
		$this->assertEquals($expected, $actual);
	}

	public function providerFiltresExtraireBalises(): array
	{

		return [
			[
				['<a href="truc">chose</a>'],
				'allo <a href="truc">chose</a>'
			],
			[
				['<a href="truc" />'],
				'allo <a href="truc" />'
			],
			[
				["<a\nhref='truc' />"],
				'allo' . "\n" . " <a\nhref='truc' />"
			],
			[
				[['<a href="1">'], ['<a href="2">']],
				['allo <a href="1">', 'allo <a href="2">']
			],
			[
				['<a href="truc">chose</a>'],
				'bonjour <a href="truc">chose</a> machin'
			],
			[
				['<a href="truc">chose</a>', '<A href="truc">machin</a>'],
				'bonjour <a href="truc">chose</a> machin <A href="truc">machin</a>',
			],
			[
				['<a href="truc">'],
				'bonjour <a href="truc">chose'
			],
			[
				['<a href="truc"/>'],
				'<a href="truc"/>chose</a>'
			],
			[
				['<a>chose</a>'],
				'<a>chose</a>'
			],
			[
				['<a href="truc">chose</a>'],
				'allo <a href="truc">chose</a>',
				'a'
			],
			[
				['<a href="truc" />'],
				'allo <a href="truc" />',
				'a'
			],
			[
				["<a\nhref='truc' />"],
				'allo' . "\n" . " <a\nhref='truc' />",
				'a'
			],
			[
				[['<a href="1">'], ['<a href="2">']],
				['allo <a href="1">', 'allo <a href="2">'],
				'a'
			],
			[
				['<a href="truc">chose</a>'],
				'bonjour <a href="truc">chose</a> machin',
				'a'
			],
			[
				['<a href="truc">chose</a>', '<A href="truc">machin</a>'],
				'bonjour <a href="truc">chose</a> machin <A href="truc">machin</a>',
				'a'
			],
			[
				['<a href="truc">'],
				'bonjour <a href="truc">chose',
				'a'
			],
			[
				['<a href="truc"/>'],
				'<a href="truc"/>chose</a>',
				'a'
			],
			[
				['<a>chose</a>'],
				'<a>chose</a>',
				'a'
			],
			[
				[],
				'allo <a href="truc">chose</a>',
				'b'
			],
			[
				[],
				'allo <a href="truc" />',
				'b'
			],
			[
				[],
				'allo' . "\n" . " <a\nhref='truc' />",
				'b'
			],
			[
				[[], []],
				['allo <a href="1">', 'allo <a href="2">'],
				'b'
			],
			[
				[],
				'bonjour <a href="truc">chose</a> machin',
				'b'
			],
			[
				[],
				'bonjour <a href="truc">chose</a> machin <A href="truc">machin</a>',
				'b'
			],
			[
				[],
				'bonjour <a href="truc">chose',
				'b'
			],
			[
				[],
				'<a href="truc"/>chose</a>',
				'b'
			],
			[
				[],
				'<a>chose</a>',
				'b'
			]
		];
	}
}
