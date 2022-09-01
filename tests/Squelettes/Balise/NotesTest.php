<?php
namespace Spip\Core\Tests\Squelettes\Balise;

use Spip\Core\Testing\SquelettesTestCase;

class NotesTest extends SquelettesTestCase {

	private function viderNotes(): void {
		// attention a cette globale qui pourrait changer dans le temps
		$notes = charger_fonction('notes', 'inc');
		$notes('', 'reset_all');
	}

	public function testNotesEnVrac(): void {
		$this->assertOkSquelette(__DIR__ . '/data/notes.html');
		$this->viderNotes();
	}

	/**
	 * Ce bloc est en premier, et contient des notes separees par un MODELE;
	 * il ne doit pas "sauter" de compteur_notes (nb2-2)
	 */
	public function testNoteNonSupprimeeSiBaliseModele(): void {
		$this->assertOkCode(
			"[(#VAL{\[\[note1\]\]<img1>\[\[note2\]\]'}
				|propre
				|match{'nb.-1'}
				|?{#VAL{'Le compteur_notes a change a cause du modele. Résultat: '#NOTES}, OK})]"
		);
		$this->viderNotes();
	}

	/**
	 * Ce bloc teste le bug introduit en
	 * http://trac.rezo.net/trac/spip/changeset/8847
	 * et corrige en
	 * http://trac.rezo.net/trac/spip/changeset/8872
	 */
	public function testNoteNonSupprimeeSiInclureInline(): void {
		$dir = $this->relativePath(__DIR__);
		$this->assertOkCode("
			[(#VAL{'\[\[Ma note\]\]'}|propre|?)]
			[(#INCLURE{fond=$dir/data/inclure_vide})]
			[(#NOTES|match{Ma note}|?{'OK','Une note mangee par INCLURE'})]
		");
		$this->viderNotes();
	}
}
