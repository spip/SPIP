<?php

global $site_array;
$site_array = array('id_rubrique', 'nom_site', 'url_site', 'description_site');

function site_stat($args, $filtres)
{
  return ((lire_meta("proposer_sites") != 2) ? '' : $args);
}

function site_dyn($id_rubrique, $nom_site, $url_site, $description_site) {

	global $spip_lang_rtl;
	$puce_ligne = "<br /><img src='puce$spip_lang_rtl.gif' border='0' alt='-' /> ";

	if (!$nom_site) return array('formulaire_site', 0);

	$res = '';

	// Tester le nom du site
	if (strlen ($nom_site) < 2){
		$res = $puce_ligne . _T('form_prop_indiquer_nom_site');
		$refus = "oui";
	}

	// Tester l'URL du site
	include_ecrire("inc_sites.php3");
	if (!recuperer_page($url_site)) {
		$res = $puce_ligne . _T('form_pet_url_invalide');
		$refus = "oui";
	}

	// Integrer a la base de donnees
		
	if ($refus !="oui"){
		$nom_site = addslashes($nom_site);
		$url_site = addslashes($url_site);
		$description_site = addslashes($description_site);
			
		spip_query("INSERT INTO spip_syndic (nom_site, url_site, id_rubrique, descriptif, date, date_syndic, statut, syndication) VALUES ('$nom_site', '$url_site', $id_rubrique, '$description_site', NOW(), NOW(), 'prop', 'non')");
		$res =  _T('form_prop_enregistre');
	} else {
		$res .= _T('form_prop_non_enregistre');
	}
		
	return "<div class='reponse_formulaire'>$res</div>";
}
?>
