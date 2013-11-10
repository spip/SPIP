<?php
/**
 * Ce fichier permet de lancer SPIP
 * pour obtenir ses fonctions depuis
 * les jeux de tests unitaires (simpletest)
 * des plugins
 * 
 * Il verifie aussi la presence du plugin simpleTest
 * 
 */
$version_lanceur = '1.0.0';

if (!defined('_ECRIRE_INC_VERSION')) {
	// recherche du loader SPIP.
	$deep = 2;
	$lanceur ='ecrire/inc_version.php';
	$include = '../../'.$lanceur;
	while (!defined('_ECRIRE_INC_VERSION') && $deep++ < 6) { 
		// attention a pas descendre trop loin tout de meme ! 
		// plugins/zone/stable/nom/version/tests/ maximum cherche
		$include = '../' . $include;
		if (file_exists($include)) {
			chdir(dirname(dirname($include)));
			require $lanceur;
		}
	}	
}
if (!defined('_ECRIRE_INC_VERSION')) {
	die("<strong>Echec :</strong> SPIP ne peut pas etre demarre automatiquement pour le test.<br />
		Vous utilisez certainement un lien symbolique dans votre repertoire plugins.");
}
include_spip('inc/tests');
if (!class_exists('SpipTestSuite')) {
	die("<strong>Echec :</strong> le plugin pour les tests unitaires avec SimpleTest ne semble pas actif.");
}
?>
