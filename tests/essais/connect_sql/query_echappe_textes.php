<?php
/**
 * Test unitaire de la fonction query_echappe_textes
 * du fichier base/connect_sql.php
 *
 */
namespace Spip\Core\Tests;

find_in_path("base/connect_sql.php",'',true);

function pretest_connect_sql_query_echappe_textes() {
	query_echappe_textes('', 'uniqid');
}

/**
 * La fonction appelee pour chaque jeu de test
 * Nommage conventionnel : test_[[dossier1_][[dossier2_]...]]fichier
 * @param ...$args
 * @return mixed
 */
function test_connect_sql_query_echappe_textes(...$args) {
	return query_echappe_textes(...$args);
}


/**
 * La fonction qui fournit les jeux de test
 * Nommage conventionnel : essais_[[dossier1_][[dossier2_]...]]fichier
 * @return array
 *  [ output, input1, input2, input3...]
 */
function essais_connect_sql_query_echappe_textes(){
	  $md5 = substr(md5('uniqid'), 0, 4);
		$essais =  [
   [
    0 => ['%1$s',  ["'guillemets simples'"]],
    1 => "'guillemets simples'",
  ],
   [
    0 => ['%1$s',  ["\"guillemets doubles\""]],
    1 => "\"guillemets doubles\"",
  ],
   [
    0 => ['%1$s,%2$s',  ["'guillemets simples 1/2'", "'guillemets simples 2/2'"]],
    1 => "'guillemets simples 1/2','guillemets simples 2/2'",
  ],
   [
    0 => ['%1$s,%2$s',  ["\"guillemets doubles 1/2\"", "\"guillemets doubles 2/2\""]],
    1 => "\"guillemets doubles 1/2\",\"guillemets doubles 2/2\"",
  ],
   [
    0 => ['%1$s',  ["'guillemets simples \x2@#{$md5}#@\x2 avec un echappement'"]],
    1 => "'guillemets simples \' avec un echappement'",
  ],
   [
    0 => ['%1$s',  ["\"guillemets doubles \x3@#{$md5}#@\x3 avec un echappement\""]],
    1 => "\"guillemets doubles \\\" avec un echappement\"",
  ],
   [
    0 => ['%1$s',  ["'guillemets simples \x2@#{$md5}#@\x2\x3@#{$md5}#@\x3 avec deux echappements'"]],
    1 => "'guillemets simples \'\\\" avec deux echappements'",
  ],
   [
    0 => ['%1$s',  ["\"guillemets doubles \x2@#{$md5}#@\x2\x3@#{$md5}#@\x3 avec deux echappements\""]],
    1 => "\"guillemets doubles \'\\\" avec deux echappements\"",
  ],
   [
    0 => ['%1$s',  ["'guillemet double \" dans guillemets simples'"]],
    1 => "'guillemet double \" dans guillemets simples'",
  ],
   [
    0 => ['%1$s',  ["\"guillemet simple ' dans guillemets doubles\""]],
    1 => "\"guillemet simple ' dans guillemets doubles\"",
  ],

  // sortie de sqlitemanager firefox
  // (description de table suite a import d'une table au format xml/phpmyadmin v5)
   [
    0 => ['%1$s INTEGER,%2$s VARCHAR',  ["\"id_objet\"","\"objet\""]],
    1 => "\"id_objet\" INTEGER,\"objet\" VARCHAR",
  ],

	[
		0 => ['UPDATE spip_truc SET html=%1$s WHERE id_truc=1', ["'''0'' style=''margin: 0;padding: 0;width: 100\x4@#{$md5}#@\x4;border: 0;height: auto;lin'"]],
		1 => "UPDATE spip_truc SET html='''0'' style=''margin: 0;padding: 0;width: 100%;border: 0;height: auto;lin' WHERE id_truc=1",
	],
	[
		0 => ['UPDATE spip_truc SET html=%1$s, texte=%2$s WHERE id_truc=1', ["'''0'' style=''margin: 0;padding: 0;width: 100\x4@#{$md5}#@\x4;border: 0;height: auto;lin'", "'toto'"]],
		1 => "UPDATE spip_truc SET html='''0'' style=''margin: 0;padding: 0;width: 100%;border: 0;height: auto;lin', texte='toto' WHERE id_truc=1",
	],
	[
		0 => ['UPDATE spip_truc SET texte=%1$s, html=%2$s WHERE id_truc=1', ["''", "'''0'' style=''margin: 0;padding: 0;width: 100\x4@#{$md5}#@\x4;border: 0;height: auto;lin'"]],
		1 => "UPDATE spip_truc SET texte='', html='''0'' style=''margin: 0;padding: 0;width: 100%;border: 0;height: auto;lin' WHERE id_truc=1",
	],
];
		return $essais;
	}


