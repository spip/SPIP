<?php

global $recherche_array;
$recherche_array = array();

function recherche_stat($args, $filtres)
{
  return (lire_meta("activer_moteur") != "oui") ? '' : array($filtres[0]);
}
 
function recherche_dyn($lien) {
	return array('formulaire_recherche', 3600, 
		     array('lien' => ($lien ? $lien : 'recherche.php3'),
			   'recherche' => ($GLOBALS['recherche'] ? $GLOBALS['recherche'] : _T('info_rechercher'))));
}

?>