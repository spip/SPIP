<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

// Appelee sans argument, cette fonction retourne un menu de couleurs
// Avec un argument numerique, elle retourne les parametres d'URL 
// pour les feuilles de style calculees (cf commencer_page et svg)
// Avec un argument de type tableau, soit elle remplace le tableau par defaut
// par celui donne en argument, soit, avec le second parametre
// a true, elle complete le tableau par celui donne en premier argument.

// http://doc.spip.org/@inc_couleurs_dist
function inc_couleurs_dist($choix=NULL, $ajouter=false)
{
	static $couleurs_spip = array(
// Vert
1 => array (
		"couleur_foncee" => "#9DBA00",
		"couleur_claire" => "#C5E41C",
		"couleur_lien" => "#657701",
		"couleur_lien_off" => "#A6C113"
		),
// Violet clair
2 => array (
		"couleur_foncee" => "#eb68b3",
		"couleur_claire" => "#ffa9e6",
		"couleur_lien" => "#8F004D",
		"couleur_lien_off" => "#BE6B97"
		),
// Orange
3 => array (
		"couleur_foncee" => "#fa9a00",
		"couleur_claire" => "#ffc000",
		"couleur_lien" => "#FF5B00",
		"couleur_lien_off" => "#B49280"
		),
// Saumon
4 => array (
		"couleur_foncee" => "#CDA261",
		"couleur_claire" => "#FFDDAA",
		"couleur_lien" => "#AA6A09",
		"couleur_lien_off" => "#B79562"
		),
//  Bleu pastel
5 => array (
		"couleur_foncee" => "#5da7c5",
		"couleur_claire" => "#97d2e1",
		"couleur_lien" => "#116587",
		"couleur_lien_off" => "#81B7CD"
		),
//  Gris
6 => array (
		"couleur_foncee" => "#85909A",
		"couleur_claire" => "#C0CAD4",
		"couleur_lien" => "#3B5063",
		"couleur_lien_off" => "#6D8499"
		),
// Vert de gris
7 => array (
		"couleur_foncee" => "#999966",
		"couleur_claire" => "#CCCC99",
		"couleur_lien" => "#666633",
		"couleur_lien_off" => "#999966"
		),
// Rose vieux
8 => array (
		"couleur_foncee" => "#EB68B3",
		"couleur_claire" => "#E4A7C5",
		"couleur_lien" => "#8F004D",
		"couleur_lien_off" => "#BE6B97"
		),
// Violet
9 => array (
		"couleur_foncee" => "#8F8FBD",
		"couleur_claire" => "#C4C4DD",
		"couleur_lien" => "#6071A5",
		"couleur_lien_off" => "#5C5C8C"
		),
//  Gris
10 => array (
		"couleur_foncee" => "#909090",
		"couleur_claire" => "#D3D3D3",
		"couleur_lien" => "#808080",
		"couleur_lien_off" => "#909090"
		),
);

	if (is_numeric($choix)) {
		// Compatibilite ascendante (plug-ins notamment)
		$GLOBALS["couleur_claire"] = $couleurs_spip[$choix]['couleur_claire'];
		$GLOBALS["couleur_foncee"] = $couleurs_spip[$choix]['couleur_foncee'];
		$GLOBALS["couleur_lien"] = $couleurs_spip[$choix]['couleur_lien'];
		$GLOBALS["couleur_lien_off"] = $couleurs_spip[$choix]['couleur_lien_off'];
		
	  return
	    "couleur_claire=" .
	    substr($couleurs_spip[$choix]['couleur_claire'],1).
	    '&couleur_foncee=' .
	    substr($couleurs_spip[$choix]['couleur_foncee'],1);
	} else {
		if (is_array($choix)) {
			if ($ajouter) {
				foreach($choix as $c)
					$couleurs_spip[] = $c;
				return $couleurs_spip;
			} else {
				return $couleurs_spip = $choix;
			}
		}

	}
	return $couleurs_spip;
}

?>
