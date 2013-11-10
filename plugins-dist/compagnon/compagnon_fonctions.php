<?php

if (!defined('_ECRIRE_INC_VERSION')) return;


/**
 * Retourne un court texte de comprehension
 * aleatoirement parmi une liste.
 *
 * [(#VAL|ok_aleatoire)]
 * 
 *
 * @return string Texte.
**/
function filtre_ok_aleatoire_dist() {
	$alea = array(
		'compagnon:ok',
		'compagnon:ok_jai_compris',
		'compagnon:ok_bien',
		'compagnon:ok_merci',
		'compagnon:ok_parfait',
	);
	
	return _T($alea[array_rand($alea)]);
}
?>
