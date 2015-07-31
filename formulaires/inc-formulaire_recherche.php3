<?php

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

global $balise_FORMULAIRE_RECHERCHE_collecte;
$balise_FORMULAIRE_RECHERCHE_collecte = array();

function balise_FORMULAIRE_RECHERCHE_stat($args, $filtres)
{
  return (lire_meta("activer_moteur") != "oui") ? '' : array($filtres[0]);
}
 
function balise_FORMULAIRE_RECHERCHE_dyn($lien) {
	include_ecrire('inc_filtres.php3');
	if (!$recherche_securisee = entites_html($GLOBALS['recherche'])) {
		$recherche_securisee = _T('info_rechercher');
	}

	return array('formulaire_recherche', 3600, 
		array(
			'lien' => ($lien ? $lien : 'recherche.php3'),
			'recherche_securisee' => $recherche_securisee
		));
}

?>