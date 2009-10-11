<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

function formulaires_configurer_preferences_charger_dist(){
	// travailler sur des meta fraiches
	include_spip('inc/meta');
	lire_metas();

	$valeurs = array();
	foreach(array('display_navigation','display','couleur') as $k)
		$valeurs[$k] = isset($GLOBALS['visiteur_session']['prefs'][$k])?$GLOBALS['visiteur_session']['prefs'][$k]:'';
	$valeurs['spip_ecran'] = $GLOBALS['spip_ecran'];

	$couleurs = charger_fonction('couleurs','inc');
	$les_couleurs = $couleurs(array(),true);
	$i=1;
	foreach($les_couleurs as $k=>$c){
		$valeurs['couleurs_url'][$i] = generer_url_public('style_prive','ltr='
				. $GLOBALS['spip_lang_left'] . '&'
				. $couleurs($k));
		$valeurs['couleurs'][$i++] = $c['couleur_foncee'];
	}

	$valeurs['imessage'] = $GLOBALS['visiteur_session']['imessage'];
	return $valeurs;
}
/*
function formulaires_configurer_preferences_verifier_dist(){
	$erreurs = array();

	foreach(array('nom_site','adresse_site') as $obli)
		if (!_request($obli))
			$erreurs[$obli] = _T('info_obligatoire');

	if ($email = _request('email_webmaster') AND !email_valide($email))
		$erreurs['email_webmaster'] = _T('info_email_invalide');
	
	return $erreurs;
}
*/

function formulaires_configurer_preferences_traiter_dist(){

	if ($couleur = _request('couleur')) {
		$GLOBALS['visiteur_session']['prefs']['couleur'] = $couleur;
	}
	if ($display = _request('display')) {
		$GLOBALS['visiteur_session']['prefs']['display'] = $display;
	}
	if ($display_navigation = _request('display_navigation')) {
		$GLOBALS['visiteur_session']['prefs']['display_navigation'] = $display_navigation;
	}

	if (intval($GLOBALS['visiteur_session']['id_auteur'])){
		include_spip('inc/modifier');
		$c = array('prefs' => serialize($GLOBALS['visiteur_session']['prefs']));

		if (_request('imessage'))
			$c['imessage'] = _request('imessage');
		revision_auteur($GLOBALS['visiteur_session']['id_auteur'], $c);
	}

	if ($spip_ecran = _request('spip_ecran')) {
		// Poser un cookie,
		// car ce reglage depend plus du navigateur que de l'utilisateur
		$GLOBALS['spip_ecran'] = $spip_ecran;
		include_spip('inc/cookie');
		spip_setcookie('spip_ecran', $_COOKIE['spip_ecran'] = $spip_ecran, time() + 365 * 24 * 3600);
	}
	
	return array('message_ok'=>_T('config_info_enregistree'),'editable'=>true);
}

?>