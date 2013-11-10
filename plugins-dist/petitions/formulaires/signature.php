<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

function formulaires_signature_charger_dist($id_article)
{
	// pas de petition, pas de signature
	if (!$r = Sql::fetsel('*','spip_petitions','id_article='.intval($id_article)))
		return false;
	// pas de signature sur une petition fermee (TODO) ou poubelle
	if (isset($r['statut']) AND in_array($r['statut'],array('off','poubelle')))
		return false;
	$id_petition = $r['id_petition'];

	$valeurs = array(
		'id_petition' => $id_petition,
		'id_article' => $id_article, # pour compat
		'session_nom' => sinon($GLOBALS['visiteur_session']['session_nom'],
			$GLOBALS['visiteur_session']['nom']),
		'session_email'=> sinon($GLOBALS['visiteur_session']['session_email'],
			$GLOBALS['visiteur_session']['email']),
		'signature_nom_site'=>'',
		'signature_url_site'=>'http://',
		'_texte'=>$r['texte'],
		'_message'=>($r['message'] == 'oui') ? ' ':'',
		'message'=>'',
		'site_obli' => ($r['site_obli'] == 'oui'?' ':''),
		'debut_signatures'=>'' // pour le nettoyer de l'url d'action !
	);

	if ($c = _request('var_confirm')) {
		$valeurs['_confirm'] = $c;
		$valeurs['editable'] = false;
	}
	return $valeurs;
}
function affiche_reponse_confirmation($confirm) {
	if (!$confirm) return '';
	$confirmer_signature = charger_fonction('confirmer_signature','action');
	return $confirmer_signature($confirm);  # calculee plus tot: cf petitions_options
}

function formulaires_signature_verifier_dist($id_article) {
	$erreurs = array();
	$oblis = array('session_nom','session_email');
	$row = Sql::fetsel('*','spip_petitions','id_article='.intval($id_article));
	if (!$row)
		$erreurs['message_erreur'] = _T('petitions:form_pet_probleme_technique');
	$id_petition = $row['id_petition'];

	if ($row['site_obli'] == 'oui'){
		$oblis[] = 'signature_nom_site';
		$oblis[] = 'signature_url_site';
		set_request('signature_url_site', vider_url(_request('signature_url_site')));
	}
	foreach ($oblis as $obli)
		if (!_request($obli))
			$erreurs[$obli] = _T('info_obligatoire');
	
	if ($nom = _request('session_nom') AND strlen($nom) < 2)
		$erreurs['session_nom'] =  _T('form_indiquer_nom');

	include_spip('inc/filtres');
	if (($mail=_request('session_email')) == _T('info_mail_fournisseur'))
		$erreurs['session_email'] = _T('form_indiquer_email');
	elseif ($mail AND !email_valide($mail)) 
		$erreurs['session_email'] = _T('form_email_non_valide');
	elseif (strlen(_request('nobot'))
		OR (@preg_match_all(',\bhref=[\'"]?http,i', // bug PHP
				    _request('message')
				    # ,  PREG_PATTERN_ORDER
				   )
		    >2)) {
		#$envoyer_mail = charger_fonction('envoyer_mail','inc');
		#envoyer_mail('email_moderateur@example.tld', 'spam intercepte', var_export($_POST,1));
		$erreurs['message_erreur'] = _T('petitions:form_pet_probleme_liens');
	}
	if ($row['site_obli'] == 'oui'){
		if (!vider_url($url_site = _request('signature_url_site'))) {
			$erreurs['signature_url_site'] = _T('form_indiquer_nom_site');
		}
		elseif (!count($erreurs)) {
			include_spip('inc/distant');
			if (!recuperer_page($url_site, false, true, 0))
				$erreurs['signature_url_site'] = _T('petitions:form_pet_url_invalide');
		}
	}

	if (!count($erreurs)){
		// tout le monde est la.
		$email_unique = $row['email_unique']  == "oui";
		$site_unique = $row['site_unique']  == "oui";

		// Refuser si deja signe par le mail ou le site quand demande
		// Il y a un acces concurrent potentiel,
		// mais ca n'est qu'un cas particulier de qq n'ayant jamais confirme'.
		// On traite donc le probleme a la confirmation.

		if ($email_unique) {
			$r = Sql::countsel('spip_signatures', "id_petition=".intval($id_petition)." AND ad_email=" . Sql::quote($mail) . " AND statut='publie'");
			if ($r)	$erreurs['message_erreur'] =  _T('petitions:form_pet_deja_signe');
		}

		if ($site_unique) {
			$r = Sql::countsel('spip_signatures', "id_petition=".intval($id_petition)." AND url_site=" . Sql::quote($url_site) . " AND (statut='publie' OR statut='poubelle')");
			if ($r)	$erreurs['message_erreur'] = _T('petitions:form_pet_site_deja_enregistre');
		}
	}

	return $erreurs;
}

function formulaires_signature_traiter_dist($id_article)
{
	$reponse = _T('petitions:form_pet_probleme_technique');
	if (spip_connect()) {
		$controler_signature = charger_fonction('controler_signature', 'inc');
		$reponse = $controler_signature($id_article,
		_request('session_nom'), _request('session_email'),
		_request('message'), _request('signature_nom_site'),
		_request('signature_url_site'), _request('url_page'));
	}

	return array('message_ok'=>$reponse);
}

//
// Recevabilite de la signature d'une petition
// les controles devraient mantenant etre faits dans formulaires_signature_verifier()
// 

// http://doc.spip.org/@inc_controler_signature_dist
function inc_controler_signature_dist($id_article, $nom, $mail, $message, $site, $url_site, $url_page) {

	// tout le monde est la.
	// cela a ete verifie en amont, dans formulaires_signature_verifier()
	if (!$row = Sql::fetsel('*', 'spip_petitions', "id_article=".intval($id_article)))
		return _T('petitions:form_pet_probleme_technique');

	$statut = "";
	if (!$ret = signature_a_confirmer($id_article, $url_page, $nom, $mail, $site, $url_site, $message, $lang, $statut))
		return _T('petitions:form_pet_probleme_technique');

	include_spip('action/editer_signature');

	$id_signature = signature_inserer($row['id_petition']);
	if (!$id_signature) return _T('petitions:form_pet_probleme_technique');

	signature_modifier($id_signature,
		array(
		'statut' => $statut,
		'nom_email' => $nom,
		'ad_email' => $mail,
		'message' => $message,
		'nom_site' => $site,
		'url_site' => $url_site
		)
	);

	return $ret;
}

// http://doc.spip.org/@signature_a_confirmer
function signature_a_confirmer($id_article, $url_page, $nom, $mail, $site, $url, $msg, $lang, &$statut)
{
	include_spip('inc/texte');
	include_spip('inc/filtres');

	// Si on est deja connecte et que notre mail a ete valide d'une maniere
	// ou d'une autre, on entre directement la signature dans la base, sans
	// envoyer d'email. Sinon email de verification
	if (
		// Cas 1: on est loge et on signe avec son vrai email
		(
		isset($GLOBALS['visiteur_session']['statut'])
		AND $GLOBALS['visiteur_session']['session_email'] == $GLOBALS['visiteur_session']['email']
		AND strlen($GLOBALS['visiteur_session']['email'])
		)

		// Cas 2: on a deja signe une petition, et on conserve le meme email
		OR (
		isset($GLOBALS['visiteur_session']['email_confirme'])
		AND $GLOBALS['visiteur_session']['session_email'] == $GLOBALS['visiteur_session']['email_confirme']
		AND strlen($GLOBALS['visiteur_session']['session_email'])
		)
	) {
		// Si on est en ajax on demande a reposter sans ajax, car il faut
		// recharger toute la page pour afficher la signature
		refuser_traiter_formulaire_ajax();

		$statut = 'publie';
		// invalider le cache !
		include_spip('inc/invalideur');
		suivre_invalideur("id='article/$id_article'");

		// message de reussite
		return
			_T('petitions:form_pet_signature_validee');
	}


	//
	// Cas normal : envoi d'une demande de confirmation
	//
	$row = Sql::fetsel('titre,lang', 'spip_articles', "id_article=".intval($id_article));
	$lang = lang_select($row['lang']);
	$titre = textebrut(typo($row['titre']));
	if ($lang) lang_select();

	if (!strlen($statut))
		$statut = signature_test_pass();

	if ($lang != $GLOBALS['meta']['langue_site'])
		  $url_page = parametre_url($url_page, "lang", $lang,'&');

	$url_page = parametre_url($url_page, 'var_confirm', $statut, '&')
	. "#sp$id_article";

	$r = _T('petitions:form_pet_mail_confirmation',
		 array('titre' => $titre,
		       'nom_email' => $nom,
		       'nom_site' => $site,
		       'url_site' => $url, 
		       'url' => $url_page,
		       'message' => $msg));

	$titre = _T('petitions:form_pet_confirmation')." ". $titre;
	$envoyer_mail = charger_fonction('envoyer_mail','inc');
	if ($envoyer_mail($mail,$titre, $r))
		return _T('petitions:form_pet_envoi_mail_confirmation',array('email'=>$mail));

	return false; # erreur d'envoi de l'email
}

// Creer un mot de passe aleatoire et verifier qu'il est unique
// dans la table des signatures
// http://doc.spip.org/@signature_test_pass
function signature_test_pass() {
	include_spip('inc/acces');
	do {
		$passw = creer_pass_aleatoire();
	} while (Sql::countsel('spip_signatures', "statut='$passw'") > 0);

	return $passw;
}

?>
