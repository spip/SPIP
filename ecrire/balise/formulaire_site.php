<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

// Le contexte indique dans quelle rubrique le visiteur peut proposer le site


function balise_FORMULAIRE_SITE ($p) {
  return calculer_balise_dynamique($p,'FORMULAIRE_SITE', array('id_rubrique'));
}

function balise_FORMULAIRE_SITE_stat($args, $filtres) {

	// Pas d'id_rubrique ? Erreur de squelette
	if (!$args[0])
		return erreur_squelette(
			_T('zbug_champ_hors_motif',
				array ('champ' => '#FORMULAIRE_SITE',
					'motif' => 'RUBRIQUES')), '');

	// Verifier que les visisteurs sont autorises a proposer un site

	return (($GLOBALS['meta']["proposer_sites"] != 2) ? '' : $args);
}

function balise_FORMULAIRE_SITE_dyn($id_rubrique) {

	$nom = _request('nom_site');
	if (!$nom)
		return array('formulaire_site', $GLOBALS['delais'],
			array('self' => str_replace('&amp;', '&', self())
		));

	// Tester le nom du site
	if (strlen ($nom) < 2){
		return _T('form_prop_indiquer_nom_site');
	}

	// Tester l'URL du site
	include_spip('inc/sites');
	$url = _request('url_site');
	if (!recuperer_page($url))
		return _T('form_pet_url_invalide');

	// Integrer a la base de donnees

	$desc = _request('description_site');
	spip_abstract_insert('spip_syndic', "(nom_site, url_site, id_rubrique, descriptif, date, date_syndic, statut, syndication)", "('" . addslashes($nom) . "', '" . addslashes($url). "', " . intval($id_rubrique) .", '" . addslashes($desc) . "', NOW(), NOW(), 'prop', 'non')");

	return  _T('form_prop_enregistre');
}
?>
