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
include_ecrire ("inc_calendrier.php");

$today=getdate(time());
$jour_today = $today["mday"];
$mois_today = $today["mon"];
$annee_today = $today["year"];

// sans arguments => mois courant
if (!$mois){
  $jour=$jour_today;
  $mois=$mois_today;
  $annee=$annee_today;
}

$date = date("Y-m-d", mktime(0,0,0,$mois, $jour, $annee));
$jour = journum($date);
$mois = mois($date);
$annee = annee($date);


$afficher_bandeau_calendrier = true;

debut_page(nom_jour("$annee-$mois-$jour")." ". affdate_jourcourt("$annee-$mois-$jour"),  
	   "redacteurs",
	   "calendrier");

debut_gauche();

echo http_calendrier_journee($jour_today,$mois_today,$annee_today, 
		  date("Y-m-d", mktime(0,0,0,$mois, $jour, $annee)));

fin_page();

?>
