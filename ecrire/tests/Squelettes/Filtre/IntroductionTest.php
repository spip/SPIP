<?php

declare(strict_types=1);

namespace Spip\Test\Squelettes\Filtre;

use Spip\Test\SquelettesTestCase;

class IntroductionTest extends SquelettesTestCase
{
	public static function setUpBeforeClass(): void {
		include_spip('inc/filtres');
		include_spip('public/composer');
	}

	public function testPresenceFiltre(): void {
		$introduction = $this->getFilterIntroduction();
		if ($introduction !== 'filtre_introduction_dist') {
			$this->markTestIncomplete(sprintf("Careful: filter %s is not 'filtre_introduction_dist'", $introduction));
		}

		$this->assertEquals('<p>ok</p>', propre('ok'));
		$this->assertEquals('<p>ok</p>', $introduction('ok', '', 100, ''));
	}

	/**
	 * la description seule ressort avec propre() sans passer par couper() or couper() enleve les balises <p> et consoeur,
	 * il faut en tenir compte dans la coupe du texte, meme si le texte est plus petit
	 */
	public function testDescriptifRetourneSiPresent(): void {
		$introduction = $this->getFilterIntroduction();
		$this->assertEquals(
			propre('description petite'),
			$introduction('description petite', 'description plus longue', 100, '')
		);
	}

	/**
	 * couper en plus...
	 */
	public function testTexteNonCoupeSiPetit(): void {
		$introduction = $this->getFilterIntroduction();
		$this->assertEquals(
			paragrapher(couper(propre('description plus longue'), 100), true),
			$introduction('', 'description plus longue', 100, '')
		);
	}

	public function testTexteCoupe(): void {
		$introduction = $this->getFilterIntroduction();
		$this->assertEquals(
			paragrapher(couper(propre('description plus longue'), 10), true),
			$introduction('', 'description plus longue', 10, '')
		);
		$this->assertNotEquals(
			paragrapher(couper(propre('description plus longue'), 20), true),
			$introduction('', 'description plus longue', 10, '')
		);
	}

	public function testTexteAvecBaliseIntro(): void {
		$introduction = $this->getFilterIntroduction();
		$this->assertEquals(
			paragrapher(couper(propre('plus'), 100), true),
			$introduction('', 'description <intro>plus</intro> longue', 100, '')
		);
	}

	private function getFilterIntroduction(): string {
		return chercher_filtre('introduction');
	}
}
