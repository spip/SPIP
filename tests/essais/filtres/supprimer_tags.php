<?php
/**
 * Test unitaire de la fonction supprimer_tags
 * du fichier inc/filtres.php
 *
 */
namespace Spip\Core\Tests;

find_in_path("inc/filtres.php",'',true);

/**
 * La fonction appelee pour chaque jeu de test
 * Nommage conventionnel : test_[[dossier1_][[dossier2_]...]]fichier
 * @param ...$args
 * @return mixed
 */
function test_filtres_supprimer_tags(...$args) {
	return supprimer_tags(...$args);
}


/**
 * La fonction qui fournit les jeux de test
 * Nommage conventionnel : essais_[[dossier1_][[dossier2_]...]]fichier
 * @return array
 *  [ output, input1, input2, input3...]
 */
function essais_filtres_supprimer_tags(){
		$essais =  [
  0 => 
   [
    0 => '',
    1 => '',
  ],
  1 => 
   [
    0 => '0',
    1 => '0',
  ],
  2 => 
   [
    0 => 'Un texte avec des liens [Article 1->art1] [spip->http://www.spip.net] http://www.spip.net',
    1 => 'Un texte avec des <a href="http://spip.net">liens</a> [Article 1->art1] [spip->http://www.spip.net] http://www.spip.net',
  ],
  3 => 
   [
    0 => 'Un texte avec des entit&eacute;s &amp;&lt;&gt;&quot;',
    1 => 'Un texte avec des entit&eacute;s &amp;&lt;&gt;&quot;',
  ],
  4 => 
   [
    0 => 'Un texte sans entites &&lt;>"\'',
    1 => 'Un texte sans entites &<>"\'',
  ],
  5 => 
   [
    0 => '{{{Des raccourcis}}} {italique} {{gras}} du code',
    1 => '{{{Des raccourcis}}} {italique} {{gras}} <code>du code</code>',
  ],
  6 => 
   [
    0 => 'Un modele http://www.spip.net]>',
    1 => 'Un modele <modeleinexistant|lien=[->http://www.spip.net]>',
  ],
];
		return $essais;
	}
