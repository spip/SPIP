<?php

global $recherche_array;
$recherche_array = array();

function recherche_stat($args, $filtres)
{
  if (lire_meta("activer_moteur") != "oui")
    return '';
  else {
    if (!($lien = $filtre[0])) $lien = 'recherche.php3';
    return "<form action='$lien' method='get' class='formrecherche'><input type='text' id='formulaire_recherche' size='20' class='formrecherche' name='recherche' value='" . _T('info_rechercher') . "' /></form>";
  }
}

?>