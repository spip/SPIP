<?php

declare(strict_types=1);

/**
 * Test unitaire de la fonction journum du fichier inc/filtres.php
 */

namespace Spip\Test\Filtre\Date;

use PHPUnit\Framework\TestCase;

class JournumTest extends TestCase
{
	public static function setUpBeforeClass(): void {
		find_in_path('inc/filtres.php', '', true);
	}

	/**
	 * @dataProvider providerFiltresJournum
	 */
	public function testFiltresJournum($expected, ...$args): void {
		$actual = journum(...$args);
		$this->assertSame($expected, $actual);
	}

	public static function providerFiltresJournum(): array {
		return [
			0 => [
				0 => '0',
				1 => '2001-00-00 12:33:44',
			],
			1 => [
				0 => '0',
				1 => '2001-03-00 09:12:57',
			],
			2 => [
				0 => '29',
				1 => '2001-02-29 14:12:33',
			],
			3 => [
				0 => '0',
				1 => '0000-00-00',
			],
			4 => [
				0 => '1',
				1 => '0001-01-01',
			],
			5 => [
				0 => '1',
				1 => '1970-01-01',
			],
			6 => [
				0 => '5',
				1 => '2001-07-05 18:25:24',
			],
			7 => [
				0 => '1',
				1 => '2001-01-01 00:00:00',
			],
			8 => [
				0 => '31',
				1 => '2001-12-31 23:59:59',
			],
			9 => [
				0 => '1',
				1 => '2001-03-01 14:12:33',
			],
			10 => [
				0 => '29',
				1 => '2004-02-29 14:12:33',
			],
			11 => [
				0 => '20',
				1 => '2012-03-20 12:00:00',
			],
			12 => [
				0 => '22',
				1 => '2012-06-22 12:00:00',
			],
			13 => [
				0 => '22',
				1 => '2012-12-22 12:00:00',
			],
			14 => [
				0 => '5',
				1 => '2001-07-05',
			],
			15 => [
				0 => '1',
				1 => '2001-01-01',
			],
			16 => [
				0 => '31',
				1 => '2001-12-31',
			],
			17 => [
				0 => '1',
				1 => '2001-03-01',
			],
			18 => [
				0 => '29',
				1 => '2004-02-29',
			],
			19 => [
				0 => '20',
				1 => '2012-03-20',
			],
			20 => [
				0 => '22',
				1 => '2012-06-22',
			],
			21 => [
				0 => '22',
				1 => '2012-12-22',
			],
			22 => [
				0 => '1',
				1 => '2001/07/05',
			],
			23 => [
				0 => '1',
				1 => '2001/01/01',
			],
			24 => [
				0 => '1',
				1 => '2001/12/31',
			],
			25 => [
				0 => '1',
				1 => '2001/03/01',
			],
			26 => [
				0 => '4',
				1 => '2004/02/29',
			],
			27 => [
				0 => '12',
				1 => '2012/03/20',
			],
			28 => [
				0 => '12',
				1 => '2012/06/22',
			],
			29 => [
				0 => '12',
				1 => '2012/12/22',
			],
			30 => [
				0 => '5',
				1 => '05/07/2001',
			],
			31 => [
				0 => '1',
				1 => '01/01/2001',
			],
			32 => [
				0 => '31',
				1 => '31/12/2001',
			],
			33 => [
				0 => '1',
				1 => '01/03/2001',
			],
			34 => [
				0 => '29',
				1 => '29/02/2004',
			],
			35 => [
				0 => '20',
				1 => '20/03/2012',
			],
			36 => [
				0 => '22',
				1 => '22/06/2012',
			],
			37 => [
				0 => '22',
				1 => '22/12/2012',
			],
		];
	}
}
