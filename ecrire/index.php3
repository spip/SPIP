<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

include ("inc.php3");

// prendre $var_* comme variables pour eviter les conflits avec les http_vars

$var_nom = "accueil";
$var_f = find_in_path('inc_' . $var_nom . '.php');

if ($var_f) 
  include($var_f);
else
  include_ecrire (_DIR_INCLUDE . 'inc_' . $var_nom . '.php');

$var_nom = 'affiche_' . $var_nom;

if (function_exists($var_nom))
  $var_nom($critere);
elseif (function_exists($var_f = $var_nom . "_dist"))
  $var_f($critere);
else
   spip_log("fonction $var_nom indisponible");

//
// Si necessaire, recalculer les rubriques
//

if (lire_meta('calculer_rubriques') == 'oui') {
	calculer_rubriques();
	effacer_meta('calculer_rubriques');
	ecrire_metas();
}

//
// Renouvellement de l'alea utilise pour valider certaines operations
// (ajouter une image, etc.)
//
if (abs(time() -  lire_meta('alea_ephemere_date')) > 2 * 24*3600) {
	spip_log("renouvellement de l'alea_ephemere");
	include_ecrire("inc_session.php3");
	$alea = md5(creer_uniqid());
	ecrire_meta('alea_ephemere_ancien', lire_meta('alea_ephemere'));
	ecrire_meta('alea_ephemere', $alea);
	ecrire_meta('alea_ephemere_date', time());
	ecrire_metas();
}

?>
