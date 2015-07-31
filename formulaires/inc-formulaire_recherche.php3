<?php

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

// Pas besoin de contexte de compilation
global $balise_FORMULAIRE_RECHERCHE_collecte;
$balise_FORMULAIRE_RECHERCHE_collecte = array();

function balise_FORMULAIRE_RECHERCHE_stat($args, $filtres) {
	// Si le moteur n'est pas active, pas de balise
	if ($GLOBALS['meta']["activer_moteur"] != "oui")
		return '';

	// Seul un lien [(#FORMULAIRE_RECHERCHE|xxx.php3)] nous interesse
	else
	  return array($filtres[0], $args[0]);
}
 
function balise_FORMULAIRE_RECHERCHE_dyn($lien, $rech) {
	include_ecrire('inc_filtres.php3');
	if (!$recherche_securisee = entites_html(_request('recherche'))) {
	  if (!$recherche_securisee = entites_html($rech)) {
		$recherche_securisee = _T('info_rechercher');
	  }
	}
	if (!$lien)
		$lien = 'recherche.php3';	# par defaut

	return array('formulaire_recherche', 3600, 
		array(
			'lien' => $lien,
			'recherche_securisee' => $recherche_securisee
		));
}

?>
