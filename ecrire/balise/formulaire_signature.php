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

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

//
// Formulaire de signature d'une petition
//

include_spip('base/abstract_sql');
spip_connect();

// Contexte necessaire lors de la compilation

// Il *faut* demander petition, meme si on ne s'en sert pas dans l'affichage,
// car on doit obtenir la jointure avec sql_petitions pour verifier si
// une petition est attachee a l'article

// http://doc.spip.org/@balise_FORMULAIRE_SIGNATURE
function balise_FORMULAIRE_SIGNATURE ($p) {
	return calculer_balise_dynamique($p,'FORMULAIRE_SIGNATURE', array('id_article', 'petition'));
}

// Verification des arguments (contexte + filtres)
// http://doc.spip.org/@balise_FORMULAIRE_SIGNATURE_stat
function balise_FORMULAIRE_SIGNATURE_stat($args, $filtres) {

	// pas d'id_article => erreur de squelette
	if (!$args[0])
		return erreur_squelette(
			_T('zbug_champ_hors_motif',
				array ('champ' => '#FORMULAIRE_SIGNATURE',
					'motif' => 'ARTICLES')), '');

	// article sans petition => pas de balise
	else if (!$args[1])
		return '';

	else {
		// aller chercher dans la base la petition associee
		if ($r = sql_fetsel("texte, site_obli, message", 'spip_petitions', "id_article = ".intval($args[0]))) {
			$args[2] = $r['texte'];
			// le signataire doit-il donner un site ?
			$args[3] = ($r['site_obli'] == 'oui') ? '':' ';
			// le signataire peut-il proposer un commentaire
			$args[4] = ($r['message'] == 'oui') ? ' ':'';
		}
		return $args;
	}
}

// Executer la balise
// http://doc.spip.org/@balise_FORMULAIRE_SIGNATURE_dyn
function balise_FORMULAIRE_SIGNATURE_dyn($id_article, $petition, $texte, $site_obli, $message) {

	if (_request('var_confirm')) # _GET
		$reponse = reponse_confirmation();  # calculee plus tot: assembler.php

	else if (_request('nom_email') AND _request('adresse_email')){ # _POST 
		if (!spip_connect())
		  $reponse = _T('form_pet_probleme_technique');
		else {
			$controler_signature = charger_fonction('controler_signature', 'inc');
			$reponse = $controler_signature($id_article,
			_request('nom_email'), _request('adresse_email'),
			_request('message'), _request('signature_nom_site'),
			_request('signature_url_site'), _request('url_page'));
		}
	}

	return array('formulaires/signature', $GLOBALS['delais'],
	array(
		'id_article' => $id_article,
		'petition' => $petition,
		'texte' => $texte,
		'site_obli' => $site_obli,
		'message' => $message,
		'self' => $reponse ?'':parametre_url(self(),'debut_signatures','', '&'),
		'reponse' => $reponse
	));
}


// Retour a l'ecran du lien de confirmation d'une signature de petition.
// Si var_confirm est non vide, c'est l'appel dans public/assembler.php
// pour vider le cache au demarrage afin que la nouvelle signature apparaisse.
// Sinon, c'est l'execution du formulaire et on retourne le message 
// de confirmation ou d'erreur construit lors de l'appel par assembler.php
// Le controle d'unicite du mail ou du site (si requis) refait ici correspond
// au cas de mails de demande de confirmation laisses sans reponse

// http://doc.spip.org/@reponse_confirmation
function reponse_confirmation($var_confirm = '') {

	static $confirm = '';
	if (!$var_confirm) return $confirm;

	if ($var_confirm == 'publie' OR $var_confirm == 'poubelle')
		return '';

	if (!spip_connect()) {
		$confirm = _T('form_pet_probleme_technique');
		return '';
	}
	include_spip('inc/texte');
	include_spip('inc/filtres');

	// Suppression d'une signature par un moderateur ?
	// Cf. plugin notifications
	if (isset($_GET['refus'])) {
		// verifier validite de la cle de suppression
		// l'id_signature est dans var_confirm
		include_spip('inc/securiser_action');
		if ($id_signature = intval($var_confirm)
		    AND (
			$_GET['refus'] == _action_auteur("supprimer signature $id_signature", '', '', 'alea_ephemere')
				OR
			$_GET['refus'] == _action_auteur("supprimer signature $id_signature", '', '', 'alea_ephemere_ancien')
			)) {
			spip_query("UPDATE spip_signatures SET statut='poubelle' WHERE id_signature=$id_signature");
			$confirm = _T('info_signature_supprimee');
		} else $confirm = _T('info_signature_supprimee_erreur');
		return '';
	}

	$r = sql_select('*', 'spip_signatures', "statut=" . _q($var_confirm), '', "1");

	if (!spip_num_rows($r)) {
		$confirm = _T('form_pet_aucune_signature');
		return '';
	}

	$row = sql_fetch($r);
	$id_signature = $row['id_signature'];
	$id_article = $row['id_article'];
	$adresse_email = $row['ad_email'];
	$url_site = $row['url_site'];

	$r = sql_select('*', 'spip_petitions', "id_article=$id_article");
	$row = sql_fetch($r);

	$email_unique = $row['email_unique']  == "oui";
	$site_unique = $row['site_unique']  == "oui";
	$lock = false;

	if ($email_unique OR $site_unique) {

		$lock = "petition $id_article $adresse_email";
		if (!spip_get_lock($lock, 5))
			return  _T('form_pet_probleme_technique');
 
		if ($email_unique) {
			$r = sql_countsel('spip_signatures', "id_article=$id_article AND ad_email=" . _q($adresse_email) . " AND statut='publie'","","1");
			if ($r)  $confirm =  _T('form_pet_deja_signe');
		} 

		if ($site_unique) {
			$r = sql_countsel('spip_signatures', "id_article=$id_article AND url_site=" . _q($url_site) . " AND (statut='publie' OR statut='poubelle')",'','1');
			if ($r) $confirm = _T('form_pet_site_deja_enregistre');
		}
	}

	if (!$confirm) spip_query("UPDATE spip_signatures SET statut='publie', date_time=NOW() WHERE id_signature=$id_signature");
	if ($lock) spip_release_lock($lock);
	if (!$confirm) {
		$confirm = _T('form_pet_signature_validee');
		// invalider les pages ayant des boucles signatures
		include_spip('inc/invalideur');
		include_spip('inc/meta');
		suivre_invalideur("id='varia/pet$id_article'");
	}
}

//
// Recevabilite de la signature d'une petition
//

// http://doc.spip.org/@inc_controler_signature_dist
function inc_controler_signature_dist($id_article, $nom_email, $adresse_email, $message, $nom_site, $url_site, $url_page) {

	include_spip('inc/texte');
	include_spip('inc/filtres');

	$envoyer_mail = charger_fonction('envoyer_mail','inc');

	if (strlen($nom_email) < 2)
		return _T('form_indiquer_nom');
	elseif ($adresse_email == _T('info_mail_fournisseur'))
		return _T('form_indiquer_email');
	elseif (!email_valide($adresse_email)) 
		return _T('form_email_non_valide');
	elseif (strlen(_request('nobot'))
		OR (@preg_match_all(',\bhref=[\'"]?http,i', // bug PHP
				    $message 
				    # ,  PREG_PATTERN_ORDER
				   )
		    >2)) {
		#envoyer_mail('email_moderateur@example.tld', 'spam intercepte', var_export($_POST,1));
		return _T('form_pet_probleme_liens');
	}

	// tout le monde est la.

	$row = sql_fetch(sql_select('titre,lang', 'spip_articles', "id_article=$id_article"));
	$lang = lang_select($row['lang']);
	$titre = textebrut(typo($row['titre']));
	if ($lang) lang_select();

	$result_petition = sql_select('*', 'spip_petitions', "id_article=$id_article");

	if (!$row = sql_fetch($result_petition)) 
		return _T('form_pet_probleme_technique');

	if ($row['site_obli'] == "oui") {
		if (!strlen($nom_site)
		OR !vider_url($url_site)) {
			return  _T('form_indiquer_nom_site');
		}
		include_spip('inc/sites');
		if (!recuperer_page($url_site, false, true, 0))
			return _T('form_pet_url_invalide');
	}

	$email_unique = $row['email_unique']  == "oui";
	$site_unique = $row['site_unique']  == "oui";
	$lock = $msg = false;

	if ($email_unique OR $site_unique) {

		$lock = "petition $id_article $adresse_email";
		if (!spip_get_lock($lock, 5))
			return  _T('form_pet_probleme_technique');
 
		if ($email_unique) {
			$r = sql_countsel('spip_signatures', "id_article=$id_article AND ad_email=" . _q($adresse_email) . " AND statut='publie'","","1");
			if ($r)  $msg =  _T('form_pet_deja_signe');
		} 

		if ($site_unique) {
			$r = sql_countsel('spip_signatures', "id_article=$id_article AND url_site=" . _q($url_site) . " AND (statut='publie' OR statut='poubelle')",'','1');
			if ($r) $msg = _T('form_pet_site_deja_enregistre');
		}
	}
	
	$passw = test_pass();
	if (!$msg)
		$id_signature = sql_insert('spip_signatures', "(id_article, date_time, statut)", "($id_article, NOW(), '$passw')");
 
	if ($lock) spip_release_lock($lock);
	if ($msg) return $msg;
	if (!$id_signature) return _T('form_pet_probleme_technique');

	// preparer l'url de confirmation
	$url = parametre_url($url_page,	'var_confirm',$passw,'&');
	if ($lang != $GLOBALS['meta']['langue_site'])
		  $url = parametre_url($url, "lang", $row['lang'],'&');
	$url .= "#sp$id_article";

	$messagex = _T('form_pet_mail_confirmation', array('titre' => $titre, 'nom_email' => $nom_email, 'nom_site' => $nom_site, 'url_site' => $url_site, 'url' => $url, 'message' => $message));

	if (!$envoyer_mail($adresse_email, _T('form_pet_confirmation')." ".$titre, $messagex)) 
		return _T('form_pet_probleme_technique');

	include_spip('inc/modifier');
	revision_signature($id_signature, array(
				'nom_email' => $nom_email,
				'ad_email' => $adresse_email,
				'message' => $message,
				'nom_site' => $nom_site,
				'url_site' => $url_site
				));
	return _T('form_pet_envoi_mail_confirmation');
}


// http://doc.spip.org/@test_pass
function test_pass() {
	include_spip('inc/acces');
	for (;;) {
		$passw = creer_pass_aleatoire();
		if (!spip_num_rows(sql_select('statut', 'spip_signatures', "statut='$passw'")))
			return $passw;
	}

}
?>
