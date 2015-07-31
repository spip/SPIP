<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

function formulaires_site_charger_dist($id_rubrique) {

	return array('nom_site'=>'','url_site'=>'http://','description_site'=>'');
}

function formulaires_site_verifier_dist($id_rubrique){

	$erreurs = array();
	if (!$nom = _request('nom_site'))
		$erreurs['nom_site'] = _T("info_obligatoire");
	else {
		if((strlen ($nom) < 2) OR (strlen(_request('nobot'))>0))
			$erreurs['email_message_auteur'] = _T('form_prop_indiquer_nom_site');
	}
	if (!$url = _request('url_site'))
		$erreurs['url_site'] = _T("info_obligatoire");

	if (!count($erreurs)) {
		// Tester l'URL du site
		include_spip('inc/distant');
		if (!recuperer_page($url))
			$erreurs['url_site'] = _T('form_pet_url_invalide');
	}
	return $erreurs;
}

function formulaires_site_traiter_dist($id_rubrique){
	$res = array('message_erreur'=>_T('titre_probleme_technique'));

	$nom = _request('nom_site');
	$url = _request('url_site');
	$desc = _request('description_site');

	include_spip('base/abstract_sql');
	if ($id_syndic = sql_insertq('spip_syndic', array(
		'nom_site' => $nom,
		'url_site' => $url,
		'id_rubrique' => $id_rubrique,
		'id_secteur' => sql_getfetsel('id_secteur','spip_rubriques','id_rubrique='.sql_quote($id_rubrique)),
		'descriptif' => $desc,
		'date' => date('Y-m-d H:i:s'),
		'date_syndic' => date('Y-m-d H:i:s'),
		'statut' => 'prop',
		'syndication' => 'non')))
			  $res = array('message_ok' => _T('form_prop_enregistre'), 'id_syndic'=>$id_syndic);

	return $res;
}

?>
