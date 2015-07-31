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

$var_f = find_in_path("inc_config-fonctions.php");
if ($var_f)
  include($var_f);
 else
   include_ecrire(_DIR_INCLUDE . "inc_config-fonctions.php"));

if ($connect_statut != '0minirezo' OR !$connect_toutes_rubriques) {
	echo _T('avis_non_acces_page');
	exit;
}

init_config();
if ($changer_config == 'oui') appliquer_modifs_config();

if (function_exists('affiche_config_fonctions'))
  $var_nom = 'affiche_config_fonctions';
 else
   $var_nom = 'affiche_config_fonctions_dist';

$var_nom();

?>
