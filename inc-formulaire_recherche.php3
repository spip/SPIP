<?php

global $balise_FORMULAIRE_RECHERCHE_collecte;
$balise_FORMULAIRE_RECHERCHE_collecte = array();

function balise_FORMULAIRE_RECHERCHE_stat($args, $filtres)
{
  return (lire_meta("activer_moteur") != "oui") ? '' : array($filtres[0]);
}
 
function balise_FORMULAIRE_RECHERCHE_dyn($lien) {
	return array('formulaire_recherche', 3600, 
		     array('lien' => ($lien ? $lien : 'recherche.php3'),
			   'recherche' => ($GLOBALS['recherche'] ? $GLOBALS['recherche'] : _T('info_rechercher'))));
}

?>