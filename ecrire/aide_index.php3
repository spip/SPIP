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

define('_ECRIRE_AIDE', 1);
include ("inc_version.php3");

$nom = "aide_index";
$f = find_in_path('inc_' . $nom . '.php');
if ($f) 
  include($f);
 else include_ecrire(_DIR_INCLUDE . 'inc_' . $nom . '.php');
  
if (function_exists($nom))
  $nom($img, $frame, $aide, $var_lang, $lang);

?>
