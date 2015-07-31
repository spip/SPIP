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
$f = find_in_path("inc_breves.php");
include($f ? $f : (_DIR_INCLUDE . "inc_breves.php"));

if ($statut AND $connect_statut == "0minirezo") {
  changer_statut_breves($id_breve, $statut);
  redirige_par_entete("breves.php3");
}

debut_page(_T('titre_page_breves'), "documents", "breves");
debut_gauche();
debut_droite();
enfant(0);
fin_page();

?>

