<?php

include ("inc.php3");
include_ecrire ("Include/PHP4/calendrier_php4.php");
include_ecrire ("Include/MySQL3/calendrier_mysql3.php");
include_ecrire ("Include/HTML4/calendrier_html4.php");

// sans arguments => mois courant
if (!$mois){
  $today=getdate(time());
  $jour=$today["mday"];
  $mois=$today["mon"];
  $annee=$today["year"];
}

$date = date("Y-m-d", mktime(0,0,0,$mois, $jour, $annee));
$jour = journum($date);
$mois = mois($date);
$annee = annee($date);

debut_page(_T('titre_page_calendrier',
	      array('nom_mois' => nom_mois($date), 'annee' => $annee)),
	   "redacteurs", 
	   "calendrier");

echo http_calendrier_semaine($jour,$mois,$annee);

// fin_page();
?>
