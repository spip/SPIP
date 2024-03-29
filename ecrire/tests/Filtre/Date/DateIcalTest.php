<?php

declare(strict_types=1);

/**
 * Test unitaire de la fonction date_ical du fichier inc/filtres.php
 */

namespace Spip\Test\Filtre\Date;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DateIcalTest extends TestCase
{
	public static function setUpBeforeClass(): void {
		find_in_path('inc/filtres.php', '', true);
	}

	protected function setUp(): void {
		date_default_timezone_set('UTC');
	}

	#[DataProvider('providerFiltresDateIcal')]
	public function testFiltresDateIcal($expected, ...$args): void {
		$actual = date_ical(...$args);
		$this->assertSame($expected, $actual);
	}

	public static function providerFiltresDateIcal(): array {
		return [
			0 => [
				0 => '20010101T123344Z',
				1 => '2001-00-00 12:33:44',
			],
			1 => [
				0 => '20010301T091257Z',
				1 => '2001-03-00 09:12:57',
			],
			2 => [
				0 => '20010301T141233Z',
				1 => '2001-02-29 14:12:33',
			],
			3 => [
				0 => '20000101T000000Z',
				1 => '0000-00-00',
			],
			4 => [
				0 => '20010101T000000Z',
				1 => '0001-01-01',
			],
			5 => [
				0 => '19700101T000000Z',
				1 => '1970-01-01',
			],
			6 => [
				0 => '20010705T182524Z',
				1 => '2001-07-05 18:25:24',
			],
			7 => [
				0 => '20010101T000000Z',
				1 => '2001-01-01 00:00:00',
			],
			8 => [
				0 => '20011231T235959Z',
				1 => '2001-12-31 23:59:59',
			],
			9 => [
				0 => '20010301T141233Z',
				1 => '2001-03-01 14:12:33',
			],
			10 => [
				0 => '20040229T141233Z',
				1 => '2004-02-29 14:12:33',
			],
			11 => [
				0 => '20120320T120000Z',
				1 => '2012-03-20 12:00:00',
			],
			12 => [
				0 => '20120621T120000Z',
				1 => '2012-06-21 12:00:00',
			],
			13 => [
				0 => '20121222T120000Z',
				1 => '2012-12-22 12:00:00',
			],
			14 => [
				0 => '20010705T000000Z',
				1 => '2001-07-05',
			],
			15 => [
				0 => '20010101T000000Z',
				1 => '2001-01-01',
			],
			16 => [
				0 => '20011231T000000Z',
				1 => '2001-12-31',
			],
			17 => [
				0 => '20120620T000000Z',
				1 => '2012-06-20',
			],
			18 => [
				0 => '20050701T000000Z',
				1 => '2001/07/05',
			],
			19 => [
				0 => '20010101T000000Z',
				1 => '2001/01/01',
			],
			20 => [
				0 => '20311201T000000Z',
				1 => '2001/12/31',
			],
			21 => [
				0 => '20010301T000000Z',
				1 => '2001/03/01',
			],
			22 => [
				0 => '20290204T000000Z',
				1 => '2004/02/29',
			],
			23 => [
				0 => '20121222T000000Z',
				1 => '22/12/2012',
			],
		];
	}
}
