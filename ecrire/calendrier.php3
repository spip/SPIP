<?php

if (isset($HTTP_GET_VARS['type']))
{
	if ($HTTP_GET_VARS['type'] == 'semaine')
		{ include ("calendrier_semaine.php3");exit;}
	else if ($HTTP_GET_VARS['type'] == 'jour')
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
