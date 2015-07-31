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

$nom = "controle_forum";
$f = find_in_path('inc_' . $nom . '.php');
if ($f) 
  include($f);
else  include_ecrire('inc_' . $nom . '.php');
if (function_exists($nom))
  $nom($page, $debut, $debut_id_forum, $id_rubrique);

?>
