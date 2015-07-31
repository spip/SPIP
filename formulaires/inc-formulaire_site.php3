<?php

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

global $balise_FORMULAIRE_SITE_collecte;
$balise_FORMULAIRE_SITE_collecte = array('id_rubrique', 'nom_site', 'url_site', 'description_site');

function balise_FORMULAIRE_SITE_stat($args, $filtres)
{
  return ((lire_meta("proposer_sites") != 2) ? '' : $args);
}

function balise_FORMULAIRE_SITE_dyn($id_rubrique, $nom_site, $url_site, $description_site) {

	if (!$nom_site) return array('formulaire_site', 0);

	// Tester le nom du site
	if (strlen ($nom_site) < 2){
		return _T('form_prop_indiquer_nom_site');
	}

	// Tester l'URL du site
	include_ecrire("inc_sites.php3");
	if (!recuperer_page($url_site)) {
		return _T('form_pet_url_invalide');
	}

	// Integrer a la base de donnees
		
	$nom_site = addslashes($nom_site);
	$url_site = addslashes($url_site);
	$description_site = addslashes($description_site);
			
	spip_query("INSERT INTO spip_syndic (nom_site, url_site, id_rubrique, descriptif, date, date_syndic, statut, syndication) VALUES ('$nom_site', '$url_site', $id_rubrique, '$description_site', NOW(), NOW(), 'prop', 'non')");
	return  _T('form_prop_enregistre');
}
?>
