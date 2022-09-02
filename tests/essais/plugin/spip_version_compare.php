<?php
/**
 * Test unitaire de la fonction spip_version_compare
 * du fichier ./inc/plugin.php
 *
 */
namespace Spip\Core\Tests;

find_in_path("./inc/plugin.php",'',true);

/**
 * La fonction appelee pour chaque jeu de test
 * Nommage conventionnel : test_[[dossier1_][[dossier2_]...]]fichier
 * @param ...$args
 * @return mixed
 */
function test_plugin_spip_version_compare(...$args) {
	return spip_version_compare(...$args);
}


/**
 * La fonction qui fournit les jeux de test
 * Nommage conventionnel : essais_[[dossier1_][[dossier2_]...]]fichier
 * @return array
 *  [ output, input1, input2, input3...]
 */
function essais_plugin_spip_version_compare(){
		$essais =  [
  0 => 
   [
    0 => false,
    1 => '2',
    2 => '2',
    3 => '>',
  ],
  1 => 
   [
    0 => false,
    1 => '2',
    2 => '2.0',
    3 => '>',
  ],
  2 => 
   [
    0 => false,
    1 => '2',
    2 => '2.0.0',
    3 => '>',
  ],
  3 => 
   [
    0 => true,
    1 => '2',
    2 => '2.0.0dev',
    3 => '>',
  ],
  4 => 
   [
    0 => true,
    1 => '2',
    2 => '2.0.0alpha',
    3 => '>',
  ],
  5 => 
   [
    0 => true,
    1 => '2',
    2 => '2.0.0beta',
    3 => '>',
  ],
  6 => 
   [
    0 => true,
    1 => '2',
    2 => '2.0.0rc',
    3 => '>',
  ],
  8 => 
   [
    0 => false,
    1 => '2',
    2 => '2.0.0pl',
    3 => '>',
  ],
  9 => 
   [
    0 => false,
    1 => '2',
    2 => '2.0.1',
    3 => '>',
  ],
  10 => 
   [
    0 => false,
    1 => '2.0',
    2 => '2',
    3 => '>',
  ],
  11 => 
   [
    0 => false,
    1 => '2.0',
    2 => '2.0',
    3 => '>',
  ],
  12 => 
   [
    0 => false,
    1 => '2.0',
    2 => '2.0.0',
    3 => '>',
  ],
  13 => 
   [
    0 => true,
    1 => '2.0',
    2 => '2.0.0dev',
    3 => '>',
  ],
  14 => 
   [
    0 => true,
    1 => '2.0',
    2 => '2.0.0alpha',
    3 => '>',
  ],
  15 => 
   [
    0 => true,
    1 => '2.0',
    2 => '2.0.0beta',
    3 => '>',
  ],
  16 => 
   [
    0 => true,
    1 => '2.0',
    2 => '2.0.0rc',
    3 => '>',
  ],
  18 => 
   [
    0 => false,
    1 => '2.0',
    2 => '2.0.0pl',
    3 => '>',
  ],
  19 => 
   [
    0 => false,
    1 => '2.0',
    2 => '2.0.1',
    3 => '>',
  ],
  20 => 
   [
    0 => false,
    1 => '2.0.0',
    2 => '2',
    3 => '>',
  ],
  21 => 
   [
    0 => false,
    1 => '2.0.0',
    2 => '2.0',
    3 => '>',
  ],
  22 => 
   [
    0 => false,
    1 => '2.0.0',
    2 => '2.0.0',
    3 => '>',
  ],
  23 => 
   [
    0 => true,
    1 => '2.0.0',
    2 => '2.0.0dev',
    3 => '>',
  ],
  24 => 
   [
    0 => true,
    1 => '2.0.0',
    2 => '2.0.0alpha',
    3 => '>',
  ],
  25 => 
   [
    0 => true,
    1 => '2.0.0',
    2 => '2.0.0beta',
    3 => '>',
  ],
  26 => 
   [
    0 => true,
    1 => '2.0.0',
    2 => '2.0.0rc',
    3 => '>',
  ],
  28 => 
   [
    0 => false,
    1 => '2.0.0',
    2 => '2.0.0pl',
    3 => '>',
  ],
  29 => 
   [
    0 => false,
    1 => '2.0.0',
    2 => '2.0.1',
    3 => '>',
  ],
  30 => 
   [
    0 => false,
    1 => '2.0.0dev',
    2 => '2',
    3 => '>',
  ],
  31 => 
   [
    0 => false,
    1 => '2.0.0dev',
    2 => '2.0',
    3 => '>',
  ],
  32 => 
   [
    0 => false,
    1 => '2.0.0dev',
    2 => '2.0.0',
    3 => '>',
  ],
  33 => 
   [
    0 => false,
    1 => '2.0.0dev',
    2 => '2.0.0dev',
    3 => '>',
  ],
  34 => 
   [
    0 => false,
    1 => '2.0.0dev',
    2 => '2.0.0alpha',
    3 => '>',
  ],
  35 => 
   [
    0 => false,
    1 => '2.0.0dev',
    2 => '2.0.0beta',
    3 => '>',
  ],
  36 => 
   [
    0 => false,
    1 => '2.0.0dev',
    2 => '2.0.0rc',
    3 => '>',
  ],
  38 => 
   [
    0 => false,
    1 => '2.0.0dev',
    2 => '2.0.0pl',
    3 => '>',
  ],
  39 => 
   [
    0 => false,
    1 => '2.0.0dev',
    2 => '2.0.1',
    3 => '>',
  ],
  40 => 
   [
    0 => false,
    1 => '2.0.0alpha',
    2 => '2',
    3 => '>',
  ],
  41 => 
   [
    0 => false,
    1 => '2.0.0alpha',
    2 => '2.0',
    3 => '>',
  ],
  42 => 
   [
    0 => false,
    1 => '2.0.0alpha',
    2 => '2.0.0',
    3 => '>',
  ],
  43 => 
   [
    0 => true,
    1 => '2.0.0alpha',
    2 => '2.0.0dev',
    3 => '>',
  ],
  44 => 
   [
    0 => false,
    1 => '2.0.0alpha',
    2 => '2.0.0alpha',
    3 => '>',
  ],
  45 => 
   [
    0 => false,
    1 => '2.0.0alpha',
    2 => '2.0.0beta',
    3 => '>',
  ],
  46 => 
   [
    0 => false,
    1 => '2.0.0alpha',
    2 => '2.0.0rc',
    3 => '>',
  ],
  48 => 
   [
    0 => false,
    1 => '2.0.0alpha',
    2 => '2.0.0pl',
    3 => '>',
  ],
  49 => 
   [
    0 => false,
    1 => '2.0.0alpha',
    2 => '2.0.1',
    3 => '>',
  ],
  50 => 
   [
    0 => false,
    1 => '2.0.0beta',
    2 => '2',
    3 => '>',
  ],
  51 => 
   [
    0 => false,
    1 => '2.0.0beta',
    2 => '2.0',
    3 => '>',
  ],
  52 => 
   [
    0 => false,
    1 => '2.0.0beta',
    2 => '2.0.0',
    3 => '>',
  ],
  53 => 
   [
    0 => true,
    1 => '2.0.0beta',
    2 => '2.0.0dev',
    3 => '>',
  ],
  54 => 
   [
    0 => true,
    1 => '2.0.0beta',
    2 => '2.0.0alpha',
    3 => '>',
  ],
  55 => 
   [
    0 => false,
    1 => '2.0.0beta',
    2 => '2.0.0beta',
    3 => '>',
  ],
  56 => 
   [
    0 => false,
    1 => '2.0.0beta',
    2 => '2.0.0rc',
    3 => '>',
  ],
  58 => 
   [
    0 => false,
    1 => '2.0.0beta',
    2 => '2.0.0pl',
    3 => '>',
  ],
  59 => 
   [
    0 => false,
    1 => '2.0.0beta',
    2 => '2.0.1',
    3 => '>',
  ],
  60 => 
   [
    0 => false,
    1 => '2.0.0rc',
    2 => '2',
    3 => '>',
  ],
  61 => 
   [
    0 => false,
    1 => '2.0.0rc',
    2 => '2.0',
    3 => '>',
  ],
  62 => 
   [
    0 => false,
    1 => '2.0.0rc',
    2 => '2.0.0',
    3 => '>',
  ],
  63 => 
   [
    0 => true,
    1 => '2.0.0rc',
    2 => '2.0.0dev',
    3 => '>',
  ],
  64 => 
   [
    0 => true,
    1 => '2.0.0rc',
    2 => '2.0.0alpha',
    3 => '>',
  ],
  65 => 
   [
    0 => true,
    1 => '2.0.0rc',
    2 => '2.0.0beta',
    3 => '>',
  ],
  66 => 
   [
    0 => false,
    1 => '2.0.0rc',
    2 => '2.0.0rc',
    3 => '>',
  ],
  68 => 
   [
    0 => false,
    1 => '2.0.0rc',
    2 => '2.0.0pl',
    3 => '>',
  ],
  69 => 
   [
    0 => false,
    1 => '2.0.0rc',
    2 => '2.0.1',
    3 => '>',
  ],
  80 => 
   [
    0 => true,
    1 => '2.0.0pl',
    2 => '2',
    3 => '>',
  ],
  81 => 
   [
    0 => true,
    1 => '2.0.0pl',
    2 => '2.0',
    3 => '>',
  ],
  82 => 
   [
    0 => true,
    1 => '2.0.0pl',
    2 => '2.0.0',
    3 => '>',
  ],
  83 => 
   [
    0 => true,
    1 => '2.0.0pl',
    2 => '2.0.0dev',
    3 => '>',
  ],
  84 => 
   [
    0 => true,
    1 => '2.0.0pl',
    2 => '2.0.0alpha',
    3 => '>',
  ],
  85 => 
   [
    0 => true,
    1 => '2.0.0pl',
    2 => '2.0.0beta',
    3 => '>',
  ],
  86 => 
   [
    0 => true,
    1 => '2.0.0pl',
    2 => '2.0.0rc',
    3 => '>',
  ],
  88 => 
   [
    0 => false,
    1 => '2.0.0pl',
    2 => '2.0.0pl',
    3 => '>',
  ],
  89 => 
   [
    0 => false,
    1 => '2.0.0pl',
    2 => '2.0.1',
    3 => '>',
  ],
  90 => 
   [
    0 => true,
    1 => '2.0.1',
    2 => '2',
    3 => '>',
  ],
  91 => 
   [
    0 => true,
    1 => '2.0.1',
    2 => '2.0',
    3 => '>',
  ],
  92 => 
   [
    0 => true,
    1 => '2.0.1',
    2 => '2.0.0',
    3 => '>',
  ],
  93 => 
   [
    0 => true,
    1 => '2.0.1',
    2 => '2.0.0dev',
    3 => '>',
  ],
  94 => 
   [
    0 => true,
    1 => '2.0.1',
    2 => '2.0.0alpha',
    3 => '>',
  ],
  95 => 
   [
    0 => true,
    1 => '2.0.1',
    2 => '2.0.0beta',
    3 => '>',
  ],
  96 => 
   [
    0 => true,
    1 => '2.0.1',
    2 => '2.0.0rc',
    3 => '>',
  ],
  98 => 
   [
    0 => true,
    1 => '2.0.1',
    2 => '2.0.0pl',
    3 => '>',
  ],
  99 => 
   [
    0 => false,
    1 => '2.0.1',
    2 => '2.0.1',
    3 => '>',
  ],
  100 => 
   [
    0 => true,
    1 => '2',
    2 => '2.0',
    3 => '=',
  ],
  101 => 
   [
    0 => true,
    1 => '2.0',
    2 => '2.0.0',
    3 => '=',
  ],
  102 => 
   [
    0 => true,
    1 => '2.0.0alpha',
    2 => '2.0.0 alpha',
    3 => '=',
  ],
  103 => 
   [
    0 => true,
    1 => '2.0.0alpha',
    2 => '2.0.0-alpha',
    3 => '=',
  ],
  104 => 
   [
    0 => true,
    1 => '2.0.0alpha',
    2 => '2.0.0a',
    3 => '=',
  ],
  105 => 
   [
    0 => true,
    1 => '2.0.0beta',
    2 => '2.0.0b',
    3 => '=',
  ],
  106 => 
   [
    0 => true,
    1 => '2.0.0pl',
    2 => '2.0.0p',
    3 => '=',
  ],
  107 => 
   [
    0 => true,
    1 => '2.0.0-rc',
    2 => '2.0.0RC',
    3 => '=',
  ],
];
		return $essais;
	}
