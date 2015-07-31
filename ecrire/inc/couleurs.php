<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// Appelee sans argument, cette fonction retourne un menu de couleurs
// Avec un argument numerique, elle retourne les parametres d'URL 
// pour les feuilles de style calculees (cf commencer_page et svg)
// Avec un argument de type tableau, elle remplace le tableau par defaut
// par celui donne en argument

// http://doc.spip.org/@inc_couleurs_dist
function inc_couleurs_dist($choix=NULL)
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
);

	if (is_numeric($choix)) {
		// Compatibilite ascendente (plug-ins notamment)
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
	  if (is_array($choix)) return $couleurs_spip = $choix;
		$res = '';
		$evt = '
onmouseover="changestyle(\'bandeauinterface\');"
onfocus="changestyle(\'bandeauinterface\');"
onblur="changestyle(\'bandeauinterface\');"';

		foreach ($couleurs_spip as $key => $val) {
			$res .= "<a href=\""
			. parametre_url(self(), 'set_couleur', $key)
			. "\"$evt>"
			. http_img_pack("rien.gif",
					_T('choix_couleur_interface') . $key,
					"width='8' height='8' style='margin: 1px; background-color: "	. $val['couleur_claire'] . ";'")
			. "</a>";
		}
	return $res;
	}
}

?>