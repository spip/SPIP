<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/meta');

//
// Appliquer les valeurs par defaut pour les options non initialisees
// (pour les langues c'est fait)
//

// http://doc.spip.org/@inc_config_dist
function inc_config_dist() {
	actualise_metas(liste_metas());
}

// http://doc.spip.org/@liste_metas
function liste_metas()
{
	return array(
		'nom_site' => _T('info_mon_site_spip'),
		'adresse_site' => preg_replace(",/$,", "", url_de_base()),
		'descriptif_site' => '',
		'activer_breves' => 'non',
		'activer_logos' => 'oui',
		'activer_logos_survol' => 'non',
		'config_precise_groupes' => 'non',
		'mots_cles_forums' =>  'non',
		'articles_surtitre' => 'non',
		'articles_soustitre' => 'non',
		'articles_descriptif' => 'non',
		'articles_chapeau' => 'non',
		'articles_texte' => 'oui',
		'articles_ps' => 'non',
		'articles_redac' => 'non',
		'articles_mots' => 'non',
		'post_dates' => 'non',
		'articles_urlref' => 'non',
		'articles_redirection' => 'non',
		'creer_preview' => 'non',
		'taille_preview' => 150,
		'articles_modif' => 'non',

		'rubriques_descriptif' => 'non',
		'rubriques_texte' => 'oui',

		'activer_sites' => 'non',
		'proposer_sites' => 0,
		'activer_syndic' => 'oui',
		'visiter_sites' => 'non',
		'moderation_sites' => 'non',

		'forums_publics' => 'posteriori',
		'accepter_inscriptions' => 'non',
		'accepter_visiteurs' => 'non',
		'prevenir_auteurs' => 'non',
		'suivi_edito' => 'non',
		'adresse_suivi' =>'',
		'adresse_suivi_inscription' =>'',
		'adresse_neuf' => '',
		'jours_neuf' => '',
		'quoi_de_neuf' => 'non',
		'forum_prive_admin' => 'non',

		'activer_moteur' => 'non',
		'articles_versions' => 'non',
		'preview' => 'non',
		'activer_statistiques' => 'non',

		'documents_article' => 'non',
		'documents_rubrique' => 'non',
		'charset' => _DEFAULT_CHARSET,
		'syndication_integrale' => 'oui',

		'multi_articles' => 'non',
		'multi_rubriques' => 'non',
		'multi_secteurs' => 'non',
		'gerer_trad' => 'non',
		'langues_multilingue' => ''
	);
}

// mets les meta a des valeurs conventionnelles quand elles sont vides
// et recalcule les langues

// http://doc.spip.org/@actualise_metas
function actualise_metas($liste_meta)
{
	while (list($nom, $valeur) = each($liste_meta)) {
		if (!$GLOBALS['meta'][$nom]) {
			ecrire_meta($nom, $valeur);
		}
	}

	include_spip('inc/rubriques');
	$langues = calculer_langues_utilisees();
	ecrire_meta('langues_utilisees', $langues);
	ecrire_metas();
}


// http://doc.spip.org/@avertissement_config
function avertissement_config() {
	global $spip_lang_right, $spip_lang_left;

	return debut_boite_info(true)
	. "\n<div class='verdana2' style='text-align: justify'>
	<p style='text-align: center'><b>"._T('avis_attention')."</b></p>"
	. http_img_pack("warning.gif", (_T('avis_attention')),
		"width='48' height='48' style='float: $spip_lang_right; padding-$spip_lang_left: 10px;'")
	. _T('texte_inc_config')
	. "</div>"
	. fin_boite_info(true)
	. "<p>&nbsp;</p>\n";
}


// http://doc.spip.org/@bouton_radio
function bouton_radio($nom, $valeur, $titre, $actif = false, $onClick="") {
	static $id_label = 0;
	
	if (strlen($onClick) > 0) $onClick = " onclick=\"$onClick\"";
	$texte = "<input type='radio' name='$nom' value='$valeur' id='label_${nom}_${id_label}'$onClick";
	if ($actif) {
		$texte .= ' checked="checked"';
		$titre = '<b>'.$titre.'</b>';
	}
	$texte .= " /> <label for='label_${nom}_${id_label}'>$titre</label>\n";
	$id_label++;
	return $texte;
}


// http://doc.spip.org/@afficher_choix
function afficher_choix($nom, $valeur_actuelle, $valeurs, $sep = "<br />") {
	while (list($valeur, $titre) = each($valeurs)) {
		$choix[] = bouton_radio($nom, $valeur, $titre, $valeur == $valeur_actuelle);
	}
	return "\n".join($sep, $choix);
}


//
// Gestion des modifs
//

// http://doc.spip.org/@appliquer_modifs_config
function appliquer_modifs_config() {
	global $email_webmaster, $descriptif_site, $email_envoi, $post_dates;
	global $forums_publics, $forums_publics_appliquer;
	global $charset, $charset_custom, $langues_auth;
	global $envoi_now, $activer_moteur;

	if (_request('adresse_site'))
		$_POST['adresse_site'] = preg_replace(",/?\s*$,", "", _request('adresse_site'));

	// provoquer l'envoi des nouveautes en supprimant le fichier lock
	if ($envoi_now) {
		@unlink(_DIR_TMP . 'mail.lock');
	}
	// Purger les squelettes si un changement de meta les affecte
	if ($post_dates AND ($post_dates != $GLOBALS['meta']["post_dates"]))
		$purger_skel = true;
	if ($forums_publics AND ($forums_publics != $GLOBALS['meta']["forums_publics"]))
		$purger_skel = true;

	// Appliquer les changements de moderation forum
	// forums_publics_appliquer : futur, saufnon, tous
	$accepter_forum = substr($forums_publics,0,3);
	if ($forums_publics_appliquer == 'saufnon')
	spip_query("UPDATE spip_articles SET accepter_forum='$accepter_forum'	WHERE accepter_forum != 'non'");
	else if ($forums_publics_appliquer == 'tous')
		spip_query("UPDATE spip_articles SET accepter_forum='$accepter_forum'");

	if ($accepter_forum == 'abo')
		ecrire_meta('accepter_visiteurs', 'oui');

	// Test du proxy : $tester_proxy est le bouton "submit"
	include_spip('configuration/relayeur');
	configuration_relayeur_post(_request('http_proxy'), _request('http_noproxy'), _request('test_proxy'), _request('tester_proxy'));

	// Activer le moteur : dresser la liste des choses a indexer
	if ($activer_moteur == 'oui' AND ($activer_moteur != $GLOBALS['meta']["activer_moteur"])) {
		include_spip('inc/indexation');
		creer_liste_indexation();
	}

	if ($langues_auth) {
		set_request('langues_multilingue', join($langues_auth, ","));
	}

	if (isset($email_webmaster))
		ecrire_meta("email_webmaster", $email_webmaster);
	if (isset($email_envoi))
		ecrire_meta("email_envoi", $email_envoi);
	if ($charset == 'custom') $charset = $charset_custom;

	$liste_meta = array_keys(liste_metas());

	// Modification du reglage accepter_inscriptions => vider le cache
	// (pour repercuter la modif sur le panneau de login)
	if (isset($GLOBALS['accepter_inscriptions'])
	AND ($GLOBALS['accepter_inscriptions']
	!= $GLOBALS['meta']['accepter_inscriptions'])) {
		include_spip('inc/invalideur');
		suivre_invalideur("1"); # tout effacer
	}

	foreach($liste_meta as $i)
		if (!(_request($i)===NULL))
			ecrire_meta($i, _request($i));

	// langue_site : la globale est mangee par inc_version
	if ($lang = $GLOBALS['changer_langue_site']) {
		include_spip('inc/lang');
		if (changer_langue($lang)) {
			ecrire_meta('langue_site', $lang);
			changer_langue($lang2);
		}
		utiliser_langue_visiteur(); 
	}

	ecrire_metas();

	if ($purger_skel) {
		include_spip('inc/invalideur');
		purger_repertoire(_DIR_SKELS);
	}
}

// Function glue_url : le pendant de parse_url 
// http://doc.spip.org/@glue_url
function glue_url ($url){
	if (!is_array($url)){
		return false;
	}
	// scheme
	$uri = (!empty($url['scheme'])) ? $url['scheme'].'://' : '';
	// user & pass
	if (!empty($url['user'])){
		$uri .= $url['user'].':'.$url['pass'].'@';
	}
	// host
	$uri .= $url['host'];
	// port
	$port = (!empty($url['port'])) ? ':'.$url['port'] : '';
	$uri .= $port;
	// path
	$uri .= $url['path'];
// fragment or query
	if (isset($url['fragment'])){
		$uri .= '#'.$url['fragment'];
	} elseif (isset($url['query'])){
		$uri .= '?'.$url['query'];
	}
	return $uri;
}

?>
