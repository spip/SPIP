<?php

declare(strict_types=1);

/**
 * Test unitaire de la classe \Spip\Utils\Serializer
 */

namespace Spip\Test\Utils;

use PHPUnit\Framework\TestCase;

class SerializerTest extends TestCase
{
	public static function setUpBeforeClass(): void
	{
	}

	/**
	 * Fonction utilitaire pour construire le jeu de test
	 * @param ...$args
	 * @return mixed
	 */
	public function sampleSerialize(...$args)
	{
		return \Spip\Utils\Serializer::serialize(...$args);
	}

	/**
	 * Fonction utilitaire pour construire le jeu de test
	 * @param ...$args
	 * @return mixed
	 */
	public function sampleUnserializeSerialize(...$args)
	{
		return \Spip\Utils\Serializer::unserialize(\Spip\Utils\Serializer::serialize(...$args));
	}

	/**
	 * @dataProvider providerSerialize
	 */
	public function testSerialize($expected, ...$args): void
	{
		$actual = \Spip\Utils\Serializer::serialize(...$args);
		$this->assertSame($expected, $actual);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * Test que sur le jeu de données providerSerialize si on inverse entrée et sortie et qu'on appelle unserialize au lieu de serialize
	 * ça marche aussi
	 * @dataProvider providerSerialize
	 */
	public function testUnserializeReverse($input, $expected): void
	{
		$actual = \Spip\Utils\Serializer::unserialize($input);
		var_dump($input, $actual);
		// pour ce reverse on ne traite pas le cas des objets
		if (!is_object($actual)) {
			$this->assertSame($expected, $actual);
			$this->assertEquals($expected, $actual);
		}
		elseif ($actual::class !== 'stdClass') {
			$this->assertEquals($expected, $actual);
		}
		else {
			$this->markTestSkipped();
		}
	}

	/**
	 * Test que un $v === unserialize(serialize($v))
	 *
	 * @dataProvider providerUnserializeSerialize
	 */
	public function testUnserializeSerialize($expected, ...$args): void
	{
		$serialized = \Spip\Utils\Serializer::serialize(...$args);
		$actual = \Spip\Utils\Serializer::unserialize($serialized);
		if (!is_object($expected)) {
			$this->assertSame($expected, $actual);
		}
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @dataProvider providerUnserializeWithAllowClasses
	 * @param $expected
	 * @param ...$args
	 * @return void
	 */
	public function testUnserializeWithAllowClasses($expected, ...$args): void
	{
		$actual = \Spip\Utils\Serializer::unserialize(...$args);
		$this->assertEquals($expected, $actual);
	}

	public static function providerSerialize(): array
	{
		// Phrases de test et éventuel texte de suite.
		$data =
			array (
			  'bool_1' =>
			  array (
			    0 => '[true]',
			    1 => true,
			  ),
			  'bool_2' =>
			  array (
			    0 => '[false]',
			    1 => false,
			  ),
			  'string_1' =>
			  array (
			    0 => '[""]',
			    1 => '',
			  ),
			  'string_2' =>
			  array (
			    0 => '["0"]',
			    1 => '0',
			  ),
			  'string_3' =>
			  array (
			    0 => '["Un texte avec des <a href=\\"http:\\/\\/spip.net\\">liens<\\/a> [Article 1->art1] [spip->https:\\/\\/www.spip.net] https:\\/\\/www.spip.net"]',
			    1 => 'Un texte avec des <a href="http://spip.net">liens</a> [Article 1->art1] [spip->https://www.spip.net] https://www.spip.net',
			  ),
			  'string_4' =>
			  array (
			    0 => '["Un texte avec des entit&eacute;s &amp;&lt;&gt;&quot;"]',
			    1 => 'Un texte avec des entit&eacute;s &amp;&lt;&gt;&quot;',
			  ),
			  'string_5' =>
			  array (
			    0 => '["Un texte avec des entit&amp;eacute;s echap&amp;eacute; &amp;amp;&amp;lt;&amp;gt;&amp;quot;"]',
			    1 => 'Un texte avec des entit&amp;eacute;s echap&amp;eacute; &amp;amp;&amp;lt;&amp;gt;&amp;quot;',
			  ),
			  'string_6' =>
			  array (
			    0 => '["Un texte avec des entit&#233;s num&#233;riques &#38;&#60;&#62;&quot;"]',
			    1 => 'Un texte avec des entit&#233;s num&#233;riques &#38;&#60;&#62;&quot;',
			  ),
			  'string_7' =>
			  array (
			    0 => '["Un texte avec des entit&amp;#233;s num&amp;#233;riques echap&amp;#233;es &amp;#38;&amp;#60;&amp;#62;&amp;quot;"]',
			    1 => 'Un texte avec des entit&amp;#233;s num&amp;#233;riques echap&amp;#233;es &amp;#38;&amp;#60;&amp;#62;&amp;quot;',
			  ),
			  'string_8' =>
			  array (
			    0 => '["Un texte sans entites &<>\\"\'"]',
			    1 => 'Un texte sans entites &<>"\'',
			  ),
			  'string_9' =>
			  array (
			    0 => '["{{{Des raccourcis}}} {italique} {{gras}} <code>du code<\\/code>"]',
			    1 => '{{{Des raccourcis}}} {italique} {{gras}} <code>du code</code>',
			  ),
			  'string_10' =>
			  array (
			    0 => '["Un modele <modeleinexistant|lien=[->https:\\/\\/www.spip.net]>"]',
			    1 => 'Un modele <modeleinexistant|lien=[->https://www.spip.net]>',
			  ),
			  'string_11' =>
			  array (
			    0 => '["Un texte avec des retour\\na la ligne et meme des\\n\\nparagraphes"]',
			    1 => 'Un texte avec des retour
a la ligne et meme des

paragraphes',
			  ),
			  'utf8-string_1' =>
			  array (
			    0 => '[""]',
			    1 => '',
			  ),
			  'utf8-string_2' =>
			  array (
			    0 => '["0"]',
			    1 => '0',
			  ),
			  'utf8-string_3' =>
			  array (
			    0 => '["Un texte avec des <a href=\\"http:\\/\\/spip.net\\">liens avec des accents UTF-8 aàâä eéèêë iîï oô uùü<\\/a> [Article 1 avec des accents UTF-8 aàâä eéèêë iîï oô uùü->art1] [spip avec des accents UTF-8 aàâä eéèêë iîï oô uùü->https:\\/\\/www.spip.net] https:\\/\\/www.spip.net"]',
			    1 => 'Un texte avec des <a href="http://spip.net">liens avec des accents UTF-8 aàâä eéèêë iîï oô uùü</a> [Article 1 avec des accents UTF-8 aàâä eéèêë iîï oô uùü->art1] [spip avec des accents UTF-8 aàâä eéèêë iîï oô uùü->https://www.spip.net] https://www.spip.net',
			  ),
			  'utf8-string_4' =>
			  array (
			    0 => '["Un texte avec des entit&eacute;s &amp;&lt;&gt;&quot; et avec des accents UTF-8 aàâä eéèêë iîï oô uùü"]',
			    1 => 'Un texte avec des entit&eacute;s &amp;&lt;&gt;&quot; et avec des accents UTF-8 aàâä eéèêë iîï oô uùü',
			  ),
			  'utf8-string_5' =>
			  array (
			    0 => '["Un texte avec des entit&amp;eacute;s echap&amp;eacute; &amp;amp;&amp;lt;&amp;gt;&amp;quot; et avec des accents UTF-8 aàâä eéèêë iîï oô uùü"]',
			    1 => 'Un texte avec des entit&amp;eacute;s echap&amp;eacute; &amp;amp;&amp;lt;&amp;gt;&amp;quot; et avec des accents UTF-8 aàâä eéèêë iîï oô uùü',
			  ),
			  'utf8-string_6' =>
			  array (
			    0 => '["Un texte avec des entit&#233;s num&#233;riques &#38;&#60;&#62;&quot; et avec des accents UTF-8 aàâä eéèêë iîï oô uùü"]',
			    1 => 'Un texte avec des entit&#233;s num&#233;riques &#38;&#60;&#62;&quot; et avec des accents UTF-8 aàâä eéèêë iîï oô uùü',
			  ),
			  'utf8-string_7' =>
			  array (
			    0 => '["Un texte avec des entit&amp;#233;s num&amp;#233;riques echap&amp;#233;es &amp;#38;&amp;#60;&amp;#62;&amp;quot; et avec des accents UTF-8 aàâä eéèêë iîï oô uùü"]',
			    1 => 'Un texte avec des entit&amp;#233;s num&amp;#233;riques echap&amp;#233;es &amp;#38;&amp;#60;&amp;#62;&amp;quot; et avec des accents UTF-8 aàâä eéèêë iîï oô uùü',
			  ),
			  'utf8-string_8' =>
			  array (
			    0 => '["Un texte sans entites &<>\\"\' et avec des accents UTF-8 aàâä eéèêë iîï oô uùü"]',
			    1 => 'Un texte sans entites &<>"\' et avec des accents UTF-8 aàâä eéèêë iîï oô uùü',
			  ),
			  'utf8-string_9' =>
			  array (
			    0 => '["{{{Des raccourcis avec des accents UTF-8 aàâä eéèêë iîï oô uùü}}} {italique avec des accents UTF-8 aàâä eéèêë iîï oô uùü} {{gras avec des accents UTF-8 aàâä eéèêë iîï oô uùü}} <code>du code avec des accents UTF-8 aàâä eéèêë iîï oô uùü<\\/code>"]',
			    1 => '{{{Des raccourcis avec des accents UTF-8 aàâä eéèêë iîï oô uùü}}} {italique avec des accents UTF-8 aàâä eéèêë iîï oô uùü} {{gras avec des accents UTF-8 aàâä eéèêë iîï oô uùü}} <code>du code avec des accents UTF-8 aàâä eéèêë iîï oô uùü</code>',
			  ),
			  'utf8-string_10' =>
			  array (
			    0 => '["Un modele avec des accents UTF-8 aàâä eéèêë iîï oô uùü <modeleinexistant|lien=[avec des accents UTF-8 aàâä eéèêë iîï oô uùü->https:\\/\\/www.spip.net]>"]',
			    1 => 'Un modele avec des accents UTF-8 aàâä eéèêë iîï oô uùü <modeleinexistant|lien=[avec des accents UTF-8 aàâä eéèêë iîï oô uùü->https://www.spip.net]>',
			  ),
			  'utf8-string_11' =>
			  array (
			    0 => '["Un texte avec des retour\\na la ligne et meme des\\n\\nparagraphes avec des accents UTF-8 aàâä eéèêë iîï oô uùü"]',
			    1 => 'Un texte avec des retour
a la ligne et meme des

paragraphes avec des accents UTF-8 aàâä eéèêë iîï oô uùü',
			  ),
			  'iso-string_1' =>
			  array (
			    0 => '[""]',
			    1 => '',
			  ),
			  'iso-string_2' =>
			  array (
			    0 => '["0"]',
			    1 => '0',
			  ),
			  'iso-string_3' =>
			  array (
				0 => '[{"@scalar":"Un texte avec des <a href=\\"http:\\/\\/spip.net\\">liens avec des accents UTF-8 aàâä eéèêë iîï oô uùü<\\/a> [Article 1 avec des accents UTF-8 aàâä eéèêë iîï oô uùü->art1] [spip avec des accents UTF-8 aàâä eéèêë iîï oô uùü->https:\\/\\/www.spip.net] https:\\/\\/www.spip.net","@utf8encoded":2}]',
			    1 => iconv('utf-8', 'iso8859-1', 'Un texte avec des <a href="http://spip.net">liens avec des accents UTF-8 aàâä eéèêë iîï oô uùü</a> [Article 1 avec des accents UTF-8 aàâä eéèêë iîï oô uùü->art1] [spip avec des accents UTF-8 aàâä eéèêë iîï oô uùü->https://www.spip.net] https://www.spip.net'),
			  ),
			  'iso-string_4' =>
			  array (
			    0 => '[{"@scalar":"Un texte avec des entit&eacute;s &amp;&lt;&gt;&quot; et avec des accents UTF-8 aàâä eéèêë iîï oô uùü","@utf8encoded":2}]',
			    1 => iconv('utf-8', 'iso8859-1','Un texte avec des entit&eacute;s &amp;&lt;&gt;&quot; et avec des accents UTF-8 aàâä eéèêë iîï oô uùü'),
			  ),
			  'iso-string_5' =>
			  array (
			    0 => '[{"@scalar":"Un texte avec des entit&amp;eacute;s echap&amp;eacute; &amp;amp;&amp;lt;&amp;gt;&amp;quot; et avec des accents UTF-8 aàâä eéèêë iîï oô uùü","@utf8encoded":2}]',
			    1 => iconv('utf-8', 'iso8859-1','Un texte avec des entit&amp;eacute;s echap&amp;eacute; &amp;amp;&amp;lt;&amp;gt;&amp;quot; et avec des accents UTF-8 aàâä eéèêë iîï oô uùü'),
			  ),
			  'iso-string_6' =>
			  array (
			    0 => '[{"@scalar":"Un texte avec des entit&#233;s num&#233;riques &#38;&#60;&#62;&quot; et avec des accents UTF-8 aàâä eéèêë iîï oô uùü","@utf8encoded":2}]',
			    1 => iconv('utf-8', 'iso8859-1','Un texte avec des entit&#233;s num&#233;riques &#38;&#60;&#62;&quot; et avec des accents UTF-8 aàâä eéèêë iîï oô uùü'),
			  ),
			  'iso-string_7' =>
			  array (
			    0 => '[{"@scalar":"Un texte avec des entit&amp;#233;s num&amp;#233;riques echap&amp;#233;es &amp;#38;&amp;#60;&amp;#62;&amp;quot; et avec des accents UTF-8 aàâä eéèêë iîï oô uùü","@utf8encoded":2}]',
			    1 => iconv('utf-8', 'iso8859-1','Un texte avec des entit&amp;#233;s num&amp;#233;riques echap&amp;#233;es &amp;#38;&amp;#60;&amp;#62;&amp;quot; et avec des accents UTF-8 aàâä eéèêë iîï oô uùü'),
			  ),
			  'iso-string_8' =>
			  array (
			    0 => '[{"@scalar":"Un texte sans entites &<>\\"\' et avec des accents UTF-8 aàâä eéèêë iîï oô uùü","@utf8encoded":2}]',
			    1 => iconv('utf-8', 'iso8859-1','Un texte sans entites &<>"\' et avec des accents UTF-8 aàâä eéèêë iîï oô uùü'),
			  ),
			  'iso-string_9' =>
			  array (
			    0 => '[{"@scalar":"{{{Des raccourcis avec des accents UTF-8 aàâä eéèêë iîï oô uùü}}} {italique avec des accents UTF-8 aàâä eéèêë iîï oô uùü} {{gras avec des accents UTF-8 aàâä eéèêë iîï oô uùü}} <code>du code avec des accents UTF-8 aàâä eéèêë iîï oô uùü<\\/code>","@utf8encoded":2}]',
			    1 => iconv('utf-8', 'iso8859-1','{{{Des raccourcis avec des accents UTF-8 aàâä eéèêë iîï oô uùü}}} {italique avec des accents UTF-8 aàâä eéèêë iîï oô uùü} {{gras avec des accents UTF-8 aàâä eéèêë iîï oô uùü}} <code>du code avec des accents UTF-8 aàâä eéèêë iîï oô uùü</code>'),
			  ),
			  'iso-string_10' =>
			  array (
			    0 => '[{"@scalar":"Un modele avec des accents UTF-8 aàâä eéèêë iîï oô uùü <modeleinexistant|lien=[avec des accents UTF-8 aàâä eéèêë iîï oô uùü->https:\\/\\/www.spip.net]>","@utf8encoded":2}]',
			    1 => iconv('utf-8', 'iso8859-1','Un modele avec des accents UTF-8 aàâä eéèêë iîï oô uùü <modeleinexistant|lien=[avec des accents UTF-8 aàâä eéèêë iîï oô uùü->https://www.spip.net]>'),
			  ),
			  'iso-string_11' =>
			  array (
			    0 => '[{"@scalar":"Un texte avec des retour\\na la ligne et meme des\\n\\nparagraphes avec des accents UTF-8 aàâä eéèêë iîï oô uùü","@utf8encoded":2}]',
			    1 => iconv('utf-8', 'iso8859-1','Un texte avec des retour
a la ligne et meme des

paragraphes avec des accents UTF-8 aàâä eéèêë iîï oô uùü'),
			  ),
			  'int_1' =>
			  array (
			    0 => '[0]',
			    1 => 0,
			  ),
			  'int_2' =>
			  array (
			    0 => '[-1]',
			    1 => -1,
			  ),
			  'int_3' =>
			  array (
			    0 => '[1]',
			    1 => 1,
			  ),
			  'int_4' =>
			  array (
			    0 => '[2]',
			    1 => 2,
			  ),
			  'int_5' =>
			  array (
			    0 => '[3]',
			    1 => 3,
			  ),
			  'int_6' =>
			  array (
			    0 => '[4]',
			    1 => 4,
			  ),
			  'int_7' =>
			  array (
			    0 => '[5]',
			    1 => 5,
			  ),
			  'int_8' =>
			  array (
			    0 => '[6]',
			    1 => 6,
			  ),
			  'int_9' =>
			  array (
			    0 => '[7]',
			    1 => 7,
			  ),
			  'int_10' =>
			  array (
			    0 => '[10]',
			    1 => 10,
			  ),
			  'int_11' =>
			  array (
			    0 => '[20]',
			    1 => 20,
			  ),
			  'int_12' =>
			  array (
			    0 => '[30]',
			    1 => 30,
			  ),
			  'int_13' =>
			  array (
			    0 => '[50]',
			    1 => 50,
			  ),
			  'int_14' =>
			  array (
			    0 => '[100]',
			    1 => 100,
			  ),
			  'int_15' =>
			  array (
			    0 => '[1000]',
			    1 => 1000,
			  ),
			  'int_16' =>
			  array (
			    0 => '[10000]',
			    1 => 10000,
			  ),
			  'float01_1' =>
			  array (
			    0 => '[0.0]',
			    1 => 0.0,
			  ),
			  'float01_2' =>
			  array (
			    0 => '[0.25]',
			    1 => 0.25,
			  ),
			  'float01_3' =>
			  array (
			    0 => '[0.5]',
			    1 => 0.5,
			  ),
			  'float01_4' =>
			  array (
			    0 => '[0.75]',
			    1 => 0.75,
			  ),
			  'float01_5' =>
			  array (
			    0 => '[1.0]',
			    1 => 1.0,
			  ),
			  'array_1' =>
			  array (
			    0 => '[[]]',
			    1 =>
			    array (
			    ),
			  ),
			  'array_2' =>
			  array (
			    0 => '[["","0","Un texte avec des <a href=\\"http:\\/\\/spip.net\\">liens<\\/a> [Article 1->art1] [spip->https:\\/\\/www.spip.net] https:\\/\\/www.spip.net","Un texte avec des entit&eacute;s &amp;&lt;&gt;&quot;","Un texte avec des entit&amp;eacute;s echap&amp;eacute; &amp;amp;&amp;lt;&amp;gt;&amp;quot;","Un texte avec des entit&#233;s num&#233;riques &#38;&#60;&#62;&quot;","Un texte avec des entit&amp;#233;s num&amp;#233;riques echap&amp;#233;es &amp;#38;&amp;#60;&amp;#62;&amp;quot;","Un texte sans entites &<>\\"\'","{{{Des raccourcis}}} {italique} {{gras}} <code>du code<\\/code>","Un modele <modeleinexistant|lien=[->https:\\/\\/www.spip.net]>","Un texte avec des retour\\na la ligne et meme des\\n\\nparagraphes"]]',
			    1 =>
			    array (
			      0 => '',
			      1 => '0',
			      2 => 'Un texte avec des <a href="http://spip.net">liens</a> [Article 1->art1] [spip->https://www.spip.net] https://www.spip.net',
			      3 => 'Un texte avec des entit&eacute;s &amp;&lt;&gt;&quot;',
			      4 => 'Un texte avec des entit&amp;eacute;s echap&amp;eacute; &amp;amp;&amp;lt;&amp;gt;&amp;quot;',
			      5 => 'Un texte avec des entit&#233;s num&#233;riques &#38;&#60;&#62;&quot;',
			      6 => 'Un texte avec des entit&amp;#233;s num&amp;#233;riques echap&amp;#233;es &amp;#38;&amp;#60;&amp;#62;&amp;quot;',
			      7 => 'Un texte sans entites &<>"\'',
			      8 => '{{{Des raccourcis}}} {italique} {{gras}} <code>du code</code>',
			      9 => 'Un modele <modeleinexistant|lien=[->https://www.spip.net]>',
			      10 => 'Un texte avec des retour
a la ligne et meme des

paragraphes',
			    ),
			  ),
			  'array_3' =>
			  array (
			    0 => '[[0,-1,1,2,3,4,5,6,7,10,20,30,50,100,1000,10000]]',
			    1 =>
			    array (
			      0 => 0,
			      1 => -1,
			      2 => 1,
			      3 => 2,
			      4 => 3,
			      5 => 4,
			      6 => 5,
			      7 => 6,
			      8 => 7,
			      9 => 10,
			      10 => 20,
			      11 => 30,
			      12 => 50,
			      13 => 100,
			      14 => 1000,
			      15 => 10000,
			    ),
			  ),
			  'array_4' =>
			  array (
			    0 => '[[true,false]]',
			    1 =>
			    array (
			      0 => true,
			      1 => false,
			    ),
			  ),
			  'array_5' =>
			  array (
			    0 => '[[[],["","0","Un texte avec des <a href=\\"http:\\/\\/spip.net\\">liens<\\/a> [Article 1->art1] [spip->https:\\/\\/www.spip.net] https:\\/\\/www.spip.net","Un texte avec des entit&eacute;s &amp;&lt;&gt;&quot;","Un texte avec des entit&amp;eacute;s echap&amp;eacute; &amp;amp;&amp;lt;&amp;gt;&amp;quot;","Un texte avec des entit&#233;s num&#233;riques &#38;&#60;&#62;&quot;","Un texte avec des entit&amp;#233;s num&amp;#233;riques echap&amp;#233;es &amp;#38;&amp;#60;&amp;#62;&amp;quot;","Un texte sans entites &<>\\"\'","{{{Des raccourcis}}} {italique} {{gras}} <code>du code<\\/code>","Un modele <modeleinexistant|lien=[->https:\\/\\/www.spip.net]>","Un texte avec des retour\\na la ligne et meme des\\n\\nparagraphes"],[0,-1,1,2,3,4,5,6,7,10,20,30,50,100,1000,10000],[true,false]]]',
			    1 =>
			    array (
			      0 =>
			      array (
			      ),
			      1 =>
			      array (
			        0 => '',
			        1 => '0',
			        2 => 'Un texte avec des <a href="http://spip.net">liens</a> [Article 1->art1] [spip->https://www.spip.net] https://www.spip.net',
			        3 => 'Un texte avec des entit&eacute;s &amp;&lt;&gt;&quot;',
			        4 => 'Un texte avec des entit&amp;eacute;s echap&amp;eacute; &amp;amp;&amp;lt;&amp;gt;&amp;quot;',
			        5 => 'Un texte avec des entit&#233;s num&#233;riques &#38;&#60;&#62;&quot;',
			        6 => 'Un texte avec des entit&amp;#233;s num&amp;#233;riques echap&amp;#233;es &amp;#38;&amp;#60;&amp;#62;&amp;quot;',
			        7 => 'Un texte sans entites &<>"\'',
			        8 => '{{{Des raccourcis}}} {italique} {{gras}} <code>du code</code>',
			        9 => 'Un modele <modeleinexistant|lien=[->https://www.spip.net]>',
			        10 => 'Un texte avec des retour
a la ligne et meme des

paragraphes',
			      ),
			      2 =>
			      array (
			        0 => 0,
			        1 => -1,
			        2 => 1,
			        3 => 2,
			        4 => 3,
			        5 => 4,
			        6 => 5,
			        7 => 6,
			        8 => 7,
			        9 => 10,
			        10 => 20,
			        11 => 30,
			        12 => 50,
			        13 => 100,
			        14 => 1000,
			        15 => 10000,
			      ),
			      3 =>
			      array (
			        0 => true,
			        1 => false,
			      ),
			    ),
			  ),
			  'array-assoc_1' =>
			  array (
			    0 => '[[]]',
			    1 =>
			    array (
			    ),
			  ),
			  'array-assoc_2' =>
			  array (
			    0 => '[["","0","Un texte avec des <a href=\\"http:\\/\\/spip.net\\">liens<\\/a> [Article 1->art1] [spip->https:\\/\\/www.spip.net] https:\\/\\/www.spip.net","Un texte avec des entit&eacute;s &amp;&lt;&gt;&quot;","Un texte avec des entit&amp;eacute;s echap&amp;eacute; &amp;amp;&amp;lt;&amp;gt;&amp;quot;","Un texte avec des entit&#233;s num&#233;riques &#38;&#60;&#62;&quot;","Un texte avec des entit&amp;#233;s num&amp;#233;riques echap&amp;#233;es &amp;#38;&amp;#60;&amp;#62;&amp;quot;","Un texte sans entites &<>\\"\'","{{{Des raccourcis}}} {italique} {{gras}} <code>du code<\\/code>","Un modele <modeleinexistant|lien=[->https:\\/\\/www.spip.net]>","Un texte avec des retour\\na la ligne et meme des\\n\\nparagraphes"]]',
			    1 =>
			    array (
			      0 => '',
			      1 => '0',
			      2 => 'Un texte avec des <a href="http://spip.net">liens</a> [Article 1->art1] [spip->https://www.spip.net] https://www.spip.net',
			      3 => 'Un texte avec des entit&eacute;s &amp;&lt;&gt;&quot;',
			      4 => 'Un texte avec des entit&amp;eacute;s echap&amp;eacute; &amp;amp;&amp;lt;&amp;gt;&amp;quot;',
			      5 => 'Un texte avec des entit&#233;s num&#233;riques &#38;&#60;&#62;&quot;',
			      6 => 'Un texte avec des entit&amp;#233;s num&amp;#233;riques echap&amp;#233;es &amp;#38;&amp;#60;&amp;#62;&amp;quot;',
			      7 => 'Un texte sans entites &<>"\'',
			      8 => '{{{Des raccourcis}}} {italique} {{gras}} <code>du code</code>',
			      9 => 'Un modele <modeleinexistant|lien=[->https://www.spip.net]>',
			      10 => 'Un texte avec des retour
a la ligne et meme des

paragraphes',
			    ),
			  ),
			  'array-assoc_3' =>
			  array (
			    0 => '[[0,-1,1,2,3,4,5,6,7,10,20,30,50,100,1000,10000]]',
			    1 =>
			    array (
			      0 => 0,
			      1 => -1,
			      2 => 1,
			      3 => 2,
			      4 => 3,
			      5 => 4,
			      6 => 5,
			      7 => 6,
			      8 => 7,
			      9 => 10,
			      10 => 20,
			      11 => 30,
			      12 => 50,
			      13 => 100,
			      14 => 1000,
			      15 => 10000,
			    ),
			  ),
			  'array-assoc_4' =>
			  array (
			    0 => '[[true,false]]',
			    1 =>
			    array (
			      0 => true,
			      1 => false,
			    ),
			  ),
			  'array-assoc_5' =>
			  array (
			    0 => '[{"vide":[],"string":["","0","Un texte avec des <a href=\\"http:\\/\\/spip.net\\">liens<\\/a> [Article 1->art1] [spip->https:\\/\\/www.spip.net] https:\\/\\/www.spip.net","Un texte avec des entit&eacute;s &amp;&lt;&gt;&quot;","Un texte avec des entit&amp;eacute;s echap&amp;eacute; &amp;amp;&amp;lt;&amp;gt;&amp;quot;","Un texte avec des entit&#233;s num&#233;riques &#38;&#60;&#62;&quot;","Un texte avec des entit&amp;#233;s num&amp;#233;riques echap&amp;#233;es &amp;#38;&amp;#60;&amp;#62;&amp;quot;","Un texte sans entites &<>\\"\'","{{{Des raccourcis}}} {italique} {{gras}} <code>du code<\\/code>","Un modele <modeleinexistant|lien=[->https:\\/\\/www.spip.net]>","Un texte avec des retour\\na la ligne et meme des\\n\\nparagraphes"],"int":[0,-1,1,2,3,4,5,6,7,10,20,30,50,100,1000,10000],"bool":[true,false]}]',
			    1 =>
			    array (
			      'vide' =>
			      array (
			      ),
			      'string' =>
			      array (
			        0 => '',
			        1 => '0',
			        2 => 'Un texte avec des <a href="http://spip.net">liens</a> [Article 1->art1] [spip->https://www.spip.net] https://www.spip.net',
			        3 => 'Un texte avec des entit&eacute;s &amp;&lt;&gt;&quot;',
			        4 => 'Un texte avec des entit&amp;eacute;s echap&amp;eacute; &amp;amp;&amp;lt;&amp;gt;&amp;quot;',
			        5 => 'Un texte avec des entit&#233;s num&#233;riques &#38;&#60;&#62;&quot;',
			        6 => 'Un texte avec des entit&amp;#233;s num&amp;#233;riques echap&amp;#233;es &amp;#38;&amp;#60;&amp;#62;&amp;quot;',
			        7 => 'Un texte sans entites &<>"\'',
			        8 => '{{{Des raccourcis}}} {italique} {{gras}} <code>du code</code>',
			        9 => 'Un modele <modeleinexistant|lien=[->https://www.spip.net]>',
			        10 => 'Un texte avec des retour
a la ligne et meme des

paragraphes',
			      ),
			      'int' =>
			      array (
			        0 => 0,
			        1 => -1,
			        2 => 1,
			        3 => 2,
			        4 => 3,
			        5 => 4,
			        6 => 5,
			        7 => 6,
			        8 => 7,
			        9 => 10,
			        10 => 20,
			        11 => 30,
			        12 => 50,
			        13 => 100,
			        14 => 1000,
			        15 => 10000,
			      ),
			      'bool' =>
			      array (
			        0 => true,
			        1 => false,
			      ),
			    ),
			  ),
			  'object_1' =>
			  array (
			    0 => '[{"@type":"Spip\\\\Test\\\\Fixtures\\\\A","publicValue":"public","protectedValue":"protected"}]',
			    1 =>
			    \Spip\Test\Fixtures\A::__set_state(array(
			       'publicValue' => 'public',
			       'protectedValue' => 'protected',
			    )),
			  ),
			  'object_2' =>
			  array (
			    0 => '[{"@type":"DateTime","date":"2023-03-09 12:13:14.000000","timezone_type":3,"timezone":"Europe\\/Paris"}]',
			    1 =>
			    \DateTime::__set_state(array(
			       'date' => '2023-03-09 12:13:14.000000',
			       'timezone_type' => 3,
			       'timezone' => 'Europe/Paris',
			    )),
			  ),
			);
		return $data;
	}


	public static function providerUnserializeSerialize(): array {
		$data =
			array (
			  'bool_1' =>
			  array (
			    0 => true,
			    1 => true,
			  ),
			  'bool_2' =>
			  array (
			    0 => false,
			    1 => false,
			  ),
			  'string_1' =>
			  array (
			    0 => '',
			    1 => '',
			  ),
			  'string_2' =>
			  array (
			    0 => '0',
			    1 => '0',
			  ),
			  'string_3' =>
			  array (
			    0 => 'Un texte avec des <a href="http://spip.net">liens</a> [Article 1->art1] [spip->https://www.spip.net] https://www.spip.net',
			    1 => 'Un texte avec des <a href="http://spip.net">liens</a> [Article 1->art1] [spip->https://www.spip.net] https://www.spip.net',
			  ),
			  'string_4' =>
			  array (
			    0 => 'Un texte avec des entit&eacute;s &amp;&lt;&gt;&quot;',
			    1 => 'Un texte avec des entit&eacute;s &amp;&lt;&gt;&quot;',
			  ),
			  'string_5' =>
			  array (
			    0 => 'Un texte avec des entit&amp;eacute;s echap&amp;eacute; &amp;amp;&amp;lt;&amp;gt;&amp;quot;',
			    1 => 'Un texte avec des entit&amp;eacute;s echap&amp;eacute; &amp;amp;&amp;lt;&amp;gt;&amp;quot;',
			  ),
			  'string_6' =>
			  array (
			    0 => 'Un texte avec des entit&#233;s num&#233;riques &#38;&#60;&#62;&quot;',
			    1 => 'Un texte avec des entit&#233;s num&#233;riques &#38;&#60;&#62;&quot;',
			  ),
			  'string_7' =>
			  array (
			    0 => 'Un texte avec des entit&amp;#233;s num&amp;#233;riques echap&amp;#233;es &amp;#38;&amp;#60;&amp;#62;&amp;quot;',
			    1 => 'Un texte avec des entit&amp;#233;s num&amp;#233;riques echap&amp;#233;es &amp;#38;&amp;#60;&amp;#62;&amp;quot;',
			  ),
			  'string_8' =>
			  array (
			    0 => 'Un texte sans entites &<>"\'',
			    1 => 'Un texte sans entites &<>"\'',
			  ),
			  'string_9' =>
			  array (
			    0 => '{{{Des raccourcis}}} {italique} {{gras}} <code>du code</code>',
			    1 => '{{{Des raccourcis}}} {italique} {{gras}} <code>du code</code>',
			  ),
			  'string_10' =>
			  array (
			    0 => 'Un modele <modeleinexistant|lien=[->https://www.spip.net]>',
			    1 => 'Un modele <modeleinexistant|lien=[->https://www.spip.net]>',
			  ),
			  'string_11' =>
			  array (
			    0 => 'Un texte avec des retour
			a la ligne et meme des

			paragraphes',
			    1 => 'Un texte avec des retour
			a la ligne et meme des

			paragraphes',
			  ),
			  'utf8-string_1' =>
			  array (
			    0 => '',
			    1 => '',
			  ),
			  'utf8-string_2' =>
			  array (
			    0 => '0',
			    1 => '0',
			  ),
			  'utf8-string_3' =>
			  array (
			    0 => 'Un texte avec des <a href="http://spip.net">liens avec des accents UTF-8 aàâä eéèêë iîï oô uùü</a> [Article 1 avec des accents UTF-8 aàâä eéèêë iîï oô uùü->art1] [spip avec des accents UTF-8 aàâä eéèêë iîï oô uùü->https://www.spip.net] https://www.spip.net',
			    1 => 'Un texte avec des <a href="http://spip.net">liens avec des accents UTF-8 aàâä eéèêë iîï oô uùü</a> [Article 1 avec des accents UTF-8 aàâä eéèêë iîï oô uùü->art1] [spip avec des accents UTF-8 aàâä eéèêë iîï oô uùü->https://www.spip.net] https://www.spip.net',
			  ),
			  'utf8-string_4' =>
			  array (
			    0 => 'Un texte avec des entit&eacute;s &amp;&lt;&gt;&quot; et avec des accents UTF-8 aàâä eéèêë iîï oô uùü',
			    1 => 'Un texte avec des entit&eacute;s &amp;&lt;&gt;&quot; et avec des accents UTF-8 aàâä eéèêë iîï oô uùü',
			  ),
			  'utf8-string_5' =>
			  array (
			    0 => 'Un texte avec des entit&amp;eacute;s echap&amp;eacute; &amp;amp;&amp;lt;&amp;gt;&amp;quot; et avec des accents UTF-8 aàâä eéèêë iîï oô uùü',
			    1 => 'Un texte avec des entit&amp;eacute;s echap&amp;eacute; &amp;amp;&amp;lt;&amp;gt;&amp;quot; et avec des accents UTF-8 aàâä eéèêë iîï oô uùü',
			  ),
			  'utf8-string_6' =>
			  array (
			    0 => 'Un texte avec des entit&#233;s num&#233;riques &#38;&#60;&#62;&quot; et avec des accents UTF-8 aàâä eéèêë iîï oô uùü',
			    1 => 'Un texte avec des entit&#233;s num&#233;riques &#38;&#60;&#62;&quot; et avec des accents UTF-8 aàâä eéèêë iîï oô uùü',
			  ),
			  'utf8-string_7' =>
			  array (
			    0 => 'Un texte avec des entit&amp;#233;s num&amp;#233;riques echap&amp;#233;es &amp;#38;&amp;#60;&amp;#62;&amp;quot; et avec des accents UTF-8 aàâä eéèêë iîï oô uùü',
			    1 => 'Un texte avec des entit&amp;#233;s num&amp;#233;riques echap&amp;#233;es &amp;#38;&amp;#60;&amp;#62;&amp;quot; et avec des accents UTF-8 aàâä eéèêë iîï oô uùü',
			  ),
			  'utf8-string_8' =>
			  array (
			    0 => 'Un texte sans entites &<>"\' et avec des accents UTF-8 aàâä eéèêë iîï oô uùü',
			    1 => 'Un texte sans entites &<>"\' et avec des accents UTF-8 aàâä eéèêë iîï oô uùü',
			  ),
			  'utf8-string_9' =>
			  array (
			    0 => '{{{Des raccourcis avec des accents UTF-8 aàâä eéèêë iîï oô uùü}}} {italique avec des accents UTF-8 aàâä eéèêë iîï oô uùü} {{gras avec des accents UTF-8 aàâä eéèêë iîï oô uùü}} <code>du code avec des accents UTF-8 aàâä eéèêë iîï oô uùü</code>',
			    1 => '{{{Des raccourcis avec des accents UTF-8 aàâä eéèêë iîï oô uùü}}} {italique avec des accents UTF-8 aàâä eéèêë iîï oô uùü} {{gras avec des accents UTF-8 aàâä eéèêë iîï oô uùü}} <code>du code avec des accents UTF-8 aàâä eéèêë iîï oô uùü</code>',
			  ),
			  'utf8-string_10' =>
			  array (
			    0 => 'Un modele avec des accents UTF-8 aàâä eéèêë iîï oô uùü <modeleinexistant|lien=[avec des accents UTF-8 aàâä eéèêë iîï oô uùü->https://www.spip.net]>',
			    1 => 'Un modele avec des accents UTF-8 aàâä eéèêë iîï oô uùü <modeleinexistant|lien=[avec des accents UTF-8 aàâä eéèêë iîï oô uùü->https://www.spip.net]>',
			  ),
			  'utf8-string_11' =>
			  array (
			    0 => 'Un texte avec des retour
			a la ligne et meme des

			paragraphes avec des accents UTF-8 aàâä eéèêë iîï oô uùü',
			    1 => 'Un texte avec des retour
			a la ligne et meme des

			paragraphes avec des accents UTF-8 aàâä eéèêë iîï oô uùü',
			  ),
			  'int_1' =>
			  array (
			    0 => 0,
			    1 => 0,
			  ),
			  'int_2' =>
			  array (
			    0 => -1,
			    1 => -1,
			  ),
			  'int_3' =>
			  array (
			    0 => 1,
			    1 => 1,
			  ),
			  'int_4' =>
			  array (
			    0 => 2,
			    1 => 2,
			  ),
			  'int_5' =>
			  array (
			    0 => 3,
			    1 => 3,
			  ),
			  'int_6' =>
			  array (
			    0 => 4,
			    1 => 4,
			  ),
			  'int_7' =>
			  array (
			    0 => 5,
			    1 => 5,
			  ),
			  'int_8' =>
			  array (
			    0 => 6,
			    1 => 6,
			  ),
			  'int_9' =>
			  array (
			    0 => 7,
			    1 => 7,
			  ),
			  'int_10' =>
			  array (
			    0 => 10,
			    1 => 10,
			  ),
			  'int_11' =>
			  array (
			    0 => 20,
			    1 => 20,
			  ),
			  'int_12' =>
			  array (
			    0 => 30,
			    1 => 30,
			  ),
			  'int_13' =>
			  array (
			    0 => 50,
			    1 => 50,
			  ),
			  'int_14' =>
			  array (
			    0 => 100,
			    1 => 100,
			  ),
			  'int_15' =>
			  array (
			    0 => 1000,
			    1 => 1000,
			  ),
			  'int_16' =>
			  array (
			    0 => 10000,
			    1 => 10000,
			  ),
			  'float01_1' =>
			  array (
			    0 => 0.0,
			    1 => 0.0,
			  ),
			  'float01_2' =>
			  array (
			    0 => 0.25,
			    1 => 0.25,
			  ),
			  'float01_3' =>
			  array (
			    0 => 0.5,
			    1 => 0.5,
			  ),
			  'float01_4' =>
			  array (
			    0 => 0.75,
			    1 => 0.75,
			  ),
			  'float01_5' =>
			  array (
			    0 => 1.0,
			    1 => 1.0,
			  ),
			  'array_1' =>
			  array (
			    0 =>
			    array (
			    ),
			    1 =>
			    array (
			    ),
			  ),
			  'array_2' =>
			  array (
			    0 =>
			    array (
			      0 => '',
			      1 => '0',
			      2 => 'Un texte avec des <a href="http://spip.net">liens</a> [Article 1->art1] [spip->https://www.spip.net] https://www.spip.net',
			      3 => 'Un texte avec des entit&eacute;s &amp;&lt;&gt;&quot;',
			      4 => 'Un texte avec des entit&amp;eacute;s echap&amp;eacute; &amp;amp;&amp;lt;&amp;gt;&amp;quot;',
			      5 => 'Un texte avec des entit&#233;s num&#233;riques &#38;&#60;&#62;&quot;',
			      6 => 'Un texte avec des entit&amp;#233;s num&amp;#233;riques echap&amp;#233;es &amp;#38;&amp;#60;&amp;#62;&amp;quot;',
			      7 => 'Un texte sans entites &<>"\'',
			      8 => '{{{Des raccourcis}}} {italique} {{gras}} <code>du code</code>',
			      9 => 'Un modele <modeleinexistant|lien=[->https://www.spip.net]>',
			      10 => 'Un texte avec des retour
			a la ligne et meme des

			paragraphes',
			    ),
			    1 =>
			    array (
			      0 => '',
			      1 => '0',
			      2 => 'Un texte avec des <a href="http://spip.net">liens</a> [Article 1->art1] [spip->https://www.spip.net] https://www.spip.net',
			      3 => 'Un texte avec des entit&eacute;s &amp;&lt;&gt;&quot;',
			      4 => 'Un texte avec des entit&amp;eacute;s echap&amp;eacute; &amp;amp;&amp;lt;&amp;gt;&amp;quot;',
			      5 => 'Un texte avec des entit&#233;s num&#233;riques &#38;&#60;&#62;&quot;',
			      6 => 'Un texte avec des entit&amp;#233;s num&amp;#233;riques echap&amp;#233;es &amp;#38;&amp;#60;&amp;#62;&amp;quot;',
			      7 => 'Un texte sans entites &<>"\'',
			      8 => '{{{Des raccourcis}}} {italique} {{gras}} <code>du code</code>',
			      9 => 'Un modele <modeleinexistant|lien=[->https://www.spip.net]>',
			      10 => 'Un texte avec des retour
			a la ligne et meme des

			paragraphes',
			    ),
			  ),
			  'array_3' =>
			  array (
			    0 =>
			    array (
			      0 => 0,
			      1 => -1,
			      2 => 1,
			      3 => 2,
			      4 => 3,
			      5 => 4,
			      6 => 5,
			      7 => 6,
			      8 => 7,
			      9 => 10,
			      10 => 20,
			      11 => 30,
			      12 => 50,
			      13 => 100,
			      14 => 1000,
			      15 => 10000,
			    ),
			    1 =>
			    array (
			      0 => 0,
			      1 => -1,
			      2 => 1,
			      3 => 2,
			      4 => 3,
			      5 => 4,
			      6 => 5,
			      7 => 6,
			      8 => 7,
			      9 => 10,
			      10 => 20,
			      11 => 30,
			      12 => 50,
			      13 => 100,
			      14 => 1000,
			      15 => 10000,
			    ),
			  ),
			  'array_4' =>
			  array (
			    0 =>
			    array (
			      0 => true,
			      1 => false,
			    ),
			    1 =>
			    array (
			      0 => true,
			      1 => false,
			    ),
			  ),
			  'array_5' =>
			  array (
			    0 =>
			    array (
			      0 =>
			      array (
			      ),
			      1 =>
			      array (
			        0 => '',
			        1 => '0',
			        2 => 'Un texte avec des <a href="http://spip.net">liens</a> [Article 1->art1] [spip->https://www.spip.net] https://www.spip.net',
			        3 => 'Un texte avec des entit&eacute;s &amp;&lt;&gt;&quot;',
			        4 => 'Un texte avec des entit&amp;eacute;s echap&amp;eacute; &amp;amp;&amp;lt;&amp;gt;&amp;quot;',
			        5 => 'Un texte avec des entit&#233;s num&#233;riques &#38;&#60;&#62;&quot;',
			        6 => 'Un texte avec des entit&amp;#233;s num&amp;#233;riques echap&amp;#233;es &amp;#38;&amp;#60;&amp;#62;&amp;quot;',
			        7 => 'Un texte sans entites &<>"\'',
			        8 => '{{{Des raccourcis}}} {italique} {{gras}} <code>du code</code>',
			        9 => 'Un modele <modeleinexistant|lien=[->https://www.spip.net]>',
			        10 => 'Un texte avec des retour
			a la ligne et meme des

			paragraphes',
			      ),
			      2 =>
			      array (
			        0 => 0,
			        1 => -1,
			        2 => 1,
			        3 => 2,
			        4 => 3,
			        5 => 4,
			        6 => 5,
			        7 => 6,
			        8 => 7,
			        9 => 10,
			        10 => 20,
			        11 => 30,
			        12 => 50,
			        13 => 100,
			        14 => 1000,
			        15 => 10000,
			      ),
			      3 =>
			      array (
			        0 => true,
			        1 => false,
			      ),
			    ),
			    1 =>
			    array (
			      0 =>
			      array (
			      ),
			      1 =>
			      array (
			        0 => '',
			        1 => '0',
			        2 => 'Un texte avec des <a href="http://spip.net">liens</a> [Article 1->art1] [spip->https://www.spip.net] https://www.spip.net',
			        3 => 'Un texte avec des entit&eacute;s &amp;&lt;&gt;&quot;',
			        4 => 'Un texte avec des entit&amp;eacute;s echap&amp;eacute; &amp;amp;&amp;lt;&amp;gt;&amp;quot;',
			        5 => 'Un texte avec des entit&#233;s num&#233;riques &#38;&#60;&#62;&quot;',
			        6 => 'Un texte avec des entit&amp;#233;s num&amp;#233;riques echap&amp;#233;es &amp;#38;&amp;#60;&amp;#62;&amp;quot;',
			        7 => 'Un texte sans entites &<>"\'',
			        8 => '{{{Des raccourcis}}} {italique} {{gras}} <code>du code</code>',
			        9 => 'Un modele <modeleinexistant|lien=[->https://www.spip.net]>',
			        10 => 'Un texte avec des retour
			a la ligne et meme des

			paragraphes',
			      ),
			      2 =>
			      array (
			        0 => 0,
			        1 => -1,
			        2 => 1,
			        3 => 2,
			        4 => 3,
			        5 => 4,
			        6 => 5,
			        7 => 6,
			        8 => 7,
			        9 => 10,
			        10 => 20,
			        11 => 30,
			        12 => 50,
			        13 => 100,
			        14 => 1000,
			        15 => 10000,
			      ),
			      3 =>
			      array (
			        0 => true,
			        1 => false,
			      ),
			    ),
			  ),
			  'array-assoc_1' =>
			  array (
			    0 =>
			    array (
			    ),
			    1 =>
			    array (
			    ),
			  ),
			  'array-assoc_2' =>
			  array (
			    0 =>
			    array (
			      0 => '',
			      1 => '0',
			      2 => 'Un texte avec des <a href="http://spip.net">liens</a> [Article 1->art1] [spip->https://www.spip.net] https://www.spip.net',
			      3 => 'Un texte avec des entit&eacute;s &amp;&lt;&gt;&quot;',
			      4 => 'Un texte avec des entit&amp;eacute;s echap&amp;eacute; &amp;amp;&amp;lt;&amp;gt;&amp;quot;',
			      5 => 'Un texte avec des entit&#233;s num&#233;riques &#38;&#60;&#62;&quot;',
			      6 => 'Un texte avec des entit&amp;#233;s num&amp;#233;riques echap&amp;#233;es &amp;#38;&amp;#60;&amp;#62;&amp;quot;',
			      7 => 'Un texte sans entites &<>"\'',
			      8 => '{{{Des raccourcis}}} {italique} {{gras}} <code>du code</code>',
			      9 => 'Un modele <modeleinexistant|lien=[->https://www.spip.net]>',
			      10 => 'Un texte avec des retour
			a la ligne et meme des

			paragraphes',
			    ),
			    1 =>
			    array (
			      0 => '',
			      1 => '0',
			      2 => 'Un texte avec des <a href="http://spip.net">liens</a> [Article 1->art1] [spip->https://www.spip.net] https://www.spip.net',
			      3 => 'Un texte avec des entit&eacute;s &amp;&lt;&gt;&quot;',
			      4 => 'Un texte avec des entit&amp;eacute;s echap&amp;eacute; &amp;amp;&amp;lt;&amp;gt;&amp;quot;',
			      5 => 'Un texte avec des entit&#233;s num&#233;riques &#38;&#60;&#62;&quot;',
			      6 => 'Un texte avec des entit&amp;#233;s num&amp;#233;riques echap&amp;#233;es &amp;#38;&amp;#60;&amp;#62;&amp;quot;',
			      7 => 'Un texte sans entites &<>"\'',
			      8 => '{{{Des raccourcis}}} {italique} {{gras}} <code>du code</code>',
			      9 => 'Un modele <modeleinexistant|lien=[->https://www.spip.net]>',
			      10 => 'Un texte avec des retour
			a la ligne et meme des

			paragraphes',
			    ),
			  ),
			  'array-assoc_3' =>
			  array (
			    0 =>
			    array (
			      0 => 0,
			      1 => -1,
			      2 => 1,
			      3 => 2,
			      4 => 3,
			      5 => 4,
			      6 => 5,
			      7 => 6,
			      8 => 7,
			      9 => 10,
			      10 => 20,
			      11 => 30,
			      12 => 50,
			      13 => 100,
			      14 => 1000,
			      15 => 10000,
			    ),
			    1 =>
			    array (
			      0 => 0,
			      1 => -1,
			      2 => 1,
			      3 => 2,
			      4 => 3,
			      5 => 4,
			      6 => 5,
			      7 => 6,
			      8 => 7,
			      9 => 10,
			      10 => 20,
			      11 => 30,
			      12 => 50,
			      13 => 100,
			      14 => 1000,
			      15 => 10000,
			    ),
			  ),
			  'array-assoc_4' =>
			  array (
			    0 =>
			    array (
			      0 => true,
			      1 => false,
			    ),
			    1 =>
			    array (
			      0 => true,
			      1 => false,
			    ),
			  ),
			  'array-assoc_5' =>
			  array (
			    0 =>
			    array (
			      'vide' =>
			      array (
			      ),
			      'string' =>
			      array (
			        0 => '',
			        1 => '0',
			        2 => 'Un texte avec des <a href="http://spip.net">liens</a> [Article 1->art1] [spip->https://www.spip.net] https://www.spip.net',
			        3 => 'Un texte avec des entit&eacute;s &amp;&lt;&gt;&quot;',
			        4 => 'Un texte avec des entit&amp;eacute;s echap&amp;eacute; &amp;amp;&amp;lt;&amp;gt;&amp;quot;',
			        5 => 'Un texte avec des entit&#233;s num&#233;riques &#38;&#60;&#62;&quot;',
			        6 => 'Un texte avec des entit&amp;#233;s num&amp;#233;riques echap&amp;#233;es &amp;#38;&amp;#60;&amp;#62;&amp;quot;',
			        7 => 'Un texte sans entites &<>"\'',
			        8 => '{{{Des raccourcis}}} {italique} {{gras}} <code>du code</code>',
			        9 => 'Un modele <modeleinexistant|lien=[->https://www.spip.net]>',
			        10 => 'Un texte avec des retour
			a la ligne et meme des

			paragraphes',
			      ),
			      'int' =>
			      array (
			        0 => 0,
			        1 => -1,
			        2 => 1,
			        3 => 2,
			        4 => 3,
			        5 => 4,
			        6 => 5,
			        7 => 6,
			        8 => 7,
			        9 => 10,
			        10 => 20,
			        11 => 30,
			        12 => 50,
			        13 => 100,
			        14 => 1000,
			        15 => 10000,
			      ),
			      'bool' =>
			      array (
			        0 => true,
			        1 => false,
			      ),
			    ),
			    1 =>
			    array (
			      'vide' =>
			      array (
			      ),
			      'string' =>
			      array (
			        0 => '',
			        1 => '0',
			        2 => 'Un texte avec des <a href="http://spip.net">liens</a> [Article 1->art1] [spip->https://www.spip.net] https://www.spip.net',
			        3 => 'Un texte avec des entit&eacute;s &amp;&lt;&gt;&quot;',
			        4 => 'Un texte avec des entit&amp;eacute;s echap&amp;eacute; &amp;amp;&amp;lt;&amp;gt;&amp;quot;',
			        5 => 'Un texte avec des entit&#233;s num&#233;riques &#38;&#60;&#62;&quot;',
			        6 => 'Un texte avec des entit&amp;#233;s num&amp;#233;riques echap&amp;#233;es &amp;#38;&amp;#60;&amp;#62;&amp;quot;',
			        7 => 'Un texte sans entites &<>"\'',
			        8 => '{{{Des raccourcis}}} {italique} {{gras}} <code>du code</code>',
			        9 => 'Un modele <modeleinexistant|lien=[->https://www.spip.net]>',
			        10 => 'Un texte avec des retour
			a la ligne et meme des

			paragraphes',
			      ),
			      'int' =>
			      array (
			        0 => 0,
			        1 => -1,
			        2 => 1,
			        3 => 2,
			        4 => 3,
			        5 => 4,
			        6 => 5,
			        7 => 6,
			        8 => 7,
			        9 => 10,
			        10 => 20,
			        11 => 30,
			        12 => 50,
			        13 => 100,
			        14 => 1000,
			        15 => 10000,
			      ),
			      'bool' =>
			      array (
			        0 => true,
			        1 => false,
			      ),
			    ),
			  ),
			  'object_1' =>
			  array (
			    0 =>
			    (object) array(
			       'publicValue' => 'public',
			       'protectedValue' => 'protected',
			       '__PHP_Incomplete_Class_Name' => 'Spip\\Test\\Fixtures\\A',
			    ),
			    1 =>
			    \Spip\Test\Fixtures\A::__set_state(array(
			       'publicValue' => 'public',
			       'protectedValue' => 'protected',
			    )),
			  ),
			  'object_2' =>
			  array (
			    0 =>
			    (object) array(
			       'publicValue' => 'public',
			       'protectedValue' => 'protected',
			       '__PHP_Incomplete_Class_Name' => 'Spip\\Test\\Fixtures\\Awithwakeup',
			    ),
			    1 =>
			    \Spip\Test\Fixtures\Awithwakeup::__set_state(array(
			       'publicValue' => 'public',
			       'protectedValue' => 'protected',
			    )),
			  ),
			  'object_3' =>
			  array (
			    0 =>
			    \DateTime::__set_state(array(
			       'date' => '2023-03-09 12:13:14.000000',
			       'timezone_type' => 3,
			       'timezone' => 'Europe/Paris',
			    )),
			    1 =>
			    \DateTime::__set_state(array(
			       'date' => '2023-03-09 12:13:14.000000',
			       'timezone_type' => 3,
			       'timezone' => 'Europe/Paris',
			    )),
			  ),
			);

		// générer les cas tests en iso-string
		foreach ($data as $k => $args) {
			if (strpos($k, 'utf8-string') === 0) {
				foreach ($args as &$arg) {
					$arg = iconv('utf-8', 'iso8859-1', $arg);
				}
				$k = str_replace('utf8-', 'iso-', $k);
				$data[$k] = $args;
			}
		}
		return $data;
	}

	public static function providerUnserializeWithAllowClasses() {

		$objetA = \Spip\Test\Fixtures\A::__set_state(array(
	       'publicValue' => 'public',
	       'protectedValue' => 'protected',
	    ));

		$objetAstd = (object) array(
	      'publicValue' => 'public',
	      'protectedValue' => 'protected',
	      '__PHP_Incomplete_Class_Name' => 'Spip\\Test\\Fixtures\\A',
	    );
		$objetB = \Spip\Test\Fixtures\B::__set_state(array(
	       'publicValue' => 'public',
	       'protectedValue' => 'protected',
	    ));
		$objetBstd = (object) array(
	       'publicValue' => 'public',
	       'protectedValue' => 'protected',
	       '__PHP_Incomplete_Class_Name' => 'Spip\\Test\\Fixtures\\B',
	    );

		$serialized = \Spip\Utils\Serializer::serialize([$objetA, $objetB]);

		$data = [];
		$data['allowed_classes_false'] = [
			[ $objetAstd, $objetBstd ],
			$serialized,
			['allowed_classes' => false]
		];
		$data['allowed_classes_A'] = [
			[ $objetA, $objetBstd ],
			$serialized,
			['allowed_classes' => ['Spip\\Test\\Fixtures\\A']]
		];
		$data['allowed_classes_B'] = [
			[ $objetAstd, $objetB ],
			$serialized,
			['allowed_classes' => ['Spip\\Test\\Fixtures\\B']]
		];
		$data['allowed_classes_true'] = [
			[ $objetA, $objetB ],
			$serialized,
			['allowed_classes' => true]
		];

		return $data;
	}

}
