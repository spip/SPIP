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


if (isset($_GET['type']))
{
	if ($_GET['type'] == 'semaine')
		{ include ("calendrier_semaine.php3");exit;}
	else if ($_GET['type'] == 'jour')
		{ include ("calendrier_jour.php3");exit;}
}
include ("inc.php3");
include_ecrire ("inc_calendrier.php");

$today=getdate(time());

// sans arguments => mois courant
if (!$mois){$annee=$today["year"];$mois=$today["mon"]; }
$periode = $annee . '-' . sprintf("%02d", $mois) . '-01';

$afficher_bandeau_calendrier = true;

debut_page(_T('titre_page_calendrier',
	      array('nom_mois' => nom_mois($periode), 'annee' => $annee)), 
	   "redacteurs", 
	   "calendrier");

echo http_calendrier_tout($mois,$annee, '01', '31');
?>
