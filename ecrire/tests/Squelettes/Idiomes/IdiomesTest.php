<?php

declare(strict_types=1);

namespace Spip\Test\Squelettes\Idiomes;

use Spip\Test\SquelettesTestCase;

class IdiomesTest extends SquelettesTestCase {

	public static function setUpBeforeClass(): void {
		include_spip('inc/filtres');
		lang_select('fr'); // ce test est en fr
	}

	public static function tearDownAfterClass(): void {
		lang_select(); // retablir la langue d'origine
	}

	/**
	 * @dataProvider providerSyntaxeIdiomes
	 */
	public function testSyntaxeIdiomes($expected, $code): void {
		$this->assertEqualsCode($expected, $code);
	}

	public static function providerSyntaxeIdiomes(): array {
		include_spip('inc/filtres');

		$tests = [
			'chaine' => [
				_T('ecrire:avis_acces_interdit_prive'),
				'<:avis_acces_interdit_prive:>',
			],
			'chaine|filtre' => [
				attribut_html(_T('lien_trier_statut')),
				"<:lien_trier_statut|attribut_html:>",
			],
			'module:chaine' => [
				_T('ecrire:avis_acces_interdit_prive'),
				'<:ecrire:avis_acces_interdit_prive:>',
			],
			'module:chaine|filtre' => [
				attribut_html(_T('ecrire:avis_acces_interdit_prive')),
				'<:ecrire:avis_acces_interdit_prive|attribut_html:>',
			],
			'module:chaine{arg}' => [
				_T('ecrire:avis_acces_interdit_prive', ['exec' => 'chose']),
				'<:ecrire:avis_acces_interdit_prive{exec=chose}:>',
			],
			'module:chaine{arg_dyn}' => [
				_T('ecrire:avis_acces_interdit_prive', ['exec' => 'chose']),
				'<:ecrire:avis_acces_interdit_prive{exec=#VAL{chose}}:>',
			],
			'module:chaine{arg_etendu1}' => [
				_T('ecrire:avis_acces_interdit_prive', ['exec' => 'chose']),
				"<:ecrire:avis_acces_interdit_prive{exec=[(#VAL{chose})]}:>",
			],
			'module:chaine{arg_etendu2}' => [
				_T('ecrire:avis_acces_interdit_prive', ['exec' => 'chose  ']),
				"<:ecrire:avis_acces_interdit_prive{exec=[(#VAL{chose})  ]}:>",
			],
			'module:chaine{arg_etendu}|filtre1' => [
				trim(_T('ecrire:avis_acces_interdit_prive', ['exec' => 'chose  ']), ' '),
				"<:ecrire:avis_acces_interdit_prive{exec=[(#VAL{chose  })]}|trim{' '}:>",
			],
			'module:chaine{arg_etendu}|filtre2' => [
				trim(_T('ecrire:avis_acces_interdit_prive', ['exec' => 'chose  ']), '.'),
				"<:ecrire:avis_acces_interdit_prive{exec=[(#VAL{chose  })]}|trim{'.'}:>",
			],
			'module:{chaine_dynamique_arg_etendu}' => [
				_T('ecrire:avis_acces_interdit_prive', ['exec' => 'chose']),
				"<:ecrire:{=#VAL{avis_acces_interdit_prive},exec=[(#VAL{chose})]}:>",
			],
			'{module:chaine_dynamique_arg_etendu}|filtre' => [
				trim(_T('ecrire:avis_acces_interdit_prive', ['exec' => 'chose'])),
				"<:{=#VAL{ecrire:avis_acces_interdit_prive},exec=[(#VAL{chose})]}|trim:>",
			],
			'balise_trad_args_inclure' => [
				_T('ecrire:avis_acces_interdit_prive', ['exec' => 'chose']),
				"[(#TRAD{ecrire:avis_acces_interdit_prive,exec=[(#VAL{chose})]})]",
			],
			'balise_trad_args_array' => [
				_T('ecrire:avis_acces_interdit_prive', ['exec' => 'chose']),
				"[(#TRAD{ecrire:avis_acces_interdit_prive,#ARRAY{exec,#VAL{chose}}})]",
			],
			'balise_trad_args_array_options1' => [
				_T('ecrire:avis_acces_interdit_prive', ['exec' => 'chose'], ['sanitize' => false]),
				"[(#TRAD{ecrire:avis_acces_interdit_prive,#ARRAY{exec,#VAL{chose}},#ARRAY{sanitize,0}})]",
			],
			'balise_trad_args_array_options2' => [
				_T('ecrire:avis_acces_interdit_prive', ['exec' => '<chose>'], ['sanitize' => false]),
				"[(#TRAD{ecrire:avis_acces_interdit_prive,#ARRAY{exec,#VAL{<chose>}},#ARRAY{sanitize,0}})]",
			],
		];


		return $tests;
	}

	public function testCompilationIdiomes() {
		$code = <<<skel
			[(#ENV{home,'non'}|=={oui}|?{
	<h1 class="spip_logo_site">[(#LOGO_SITE_SPIP|image_reduire{224,96})]#NOM_SITE_SPIP</h1>
	,
	<strong class="h1 spip_logo_site"><a rel="start home" href="#URL_SITE_SPIP/" title="<:accueil_site:>">[(#LOGO_SITE_SPIP
            |image_reduire{224,96})]#NOM_SITE_SPIP</a></strong>
	}|vide)]OK
skel;
		$this->assertOkCode($code);
	}
}
