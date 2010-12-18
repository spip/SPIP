<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

function formulaires_signature_charger_dist($id_article, $petition, $texte, $site_obli, $message) {
	$valeurs = array(
		'id_article' => $id_article,
		'session_nom' => sinon($GLOBALS['visiteur_session']['session_nom'],
			$GLOBALS['visiteur_session']['nom']),
		'session_email'=> sinon($GLOBALS['visiteur_session']['session_email'],
			$GLOBALS['visiteur_session']['email']),
		'signature_nom_site'=>'',
		'signature_url_site'=>'http://',
		'_texte'=>$petition,
		'_message'=>$message,
		'message'=>'',
		'site_obli' => $site_obli,
		'debut_signatures'=>'' // pour le nettoyer de l'url d'action !
		);

	if ($c = _request('var_confirm')) {
		$valeurs['_confirm'] = $c;
		$valeurs['editable'] = false;
	}
	return $valeurs;
}
function affiche_reponse_confirmation($confirm) {
	$reponse_confirmation = charger_fonction('reponse_confirmation','formulaires/signature/');
	return $reponse_confirmation($confirm);  # calculee plus tot: assembler.php
}

function formulaires_signature_verifier_dist($id_article, $petition, $texte, $site_obli, $message) {
	$erreurs = array();
	$oblis = array('session_email','session_email');

	if ($site_obli){
		$oblis[] = 'signature_nom_site';
		$oblis[] = 'signature_url_site';
		set_request('signature_url_site', vider_url(_request('signature_url_site')));
	}
	foreach ($oblis as $obli)
		if (!_request($obli))
			$erreurs[$obli] = _T('info_obligatoire');
	
	if ($nom = _request('session_nom') AND strlen($nom) < 2)
		$erreurs['nom_email'] =  _T('form_indiquer_nom');

	include_spip('inc/filtres');
	if (($mail=_request('session_email')) == _T('info_mail_fournisseur'))
		$erreurs['adresse_email'] = _T('form_indiquer');
	elseif ($mail AND !email_valide($mail)) 
		$erreurs['adresse_email'] = _T('form_email_non_valide');
	elseif (strlen(_request('nobot'))
		OR (@preg_match_all(',\bhref=[\'"]?http,i', // bug PHP
				    $message 
				    # ,  PREG_PATTERN_ORDER
				   )
		    >2)) {
		#$envoyer_mail = charger_fonction('envoyer_mail','inc');
		#envoyer_mail('email_moderateur@example.tld', 'spam intercepte', var_export($_POST,1));
		$erreurs['message_erreur'] = _T('form_pet_probleme_liens');
	}
	if ($site_obli){
		if (!vider_url($url_site = _request('signature_url_site'))) {
			$erreurs['signature_url_site'] = _T('form_indiquer_nom_site');
		}
		elseif (!count($erreurs)) {
			include_spip('inc/distant');
			if (!recuperer_page($url_site, false, true, 0))
				$erreurs['signature_url_site'] = _T('form_pet_url_invalide');
		}
	}
	
	if (!count($erreurs)){
		// tout le monde est la.
		include_spip('base/abstract_sql');
		$row = sql_fetsel('*', 'spip_petitions', "id_article=".intval($id_article));

		if (!$row) 
			$erreurs['message_erreur'] = _T('form_pet_probleme_technique');
		else {
			$email_unique = $row['email_unique']  == "oui";
			$site_unique = $row['site_unique']  == "oui";
		
			// Refuser si deja signe par le mail ou le site quand demande
			// Il y a un acces concurrent potentiel,
			// mais ca n'est qu'un cas particulier de qq n'ayant jamais confirme'.
			// On traite donc le probleme a la confirmation.
		
			if ($email_unique) {
				$r = sql_countsel('spip_signatures', "id_article=$id_article AND ad_email=" . sql_quote($mail) . " AND statut='publie'");
				if ($r)	$erreurs['message_erreur'] =  _T('form_pet_deja_signe');
			}
		
			if ($site_unique) {
				$r = sql_countsel('spip_signatures', "id_article=$id_article AND url_site=" . sql_quote($url_site) . " AND (statut='publie' OR statut='poubelle')");
				if ($r)	$erreurs['message_erreur'] = _T('form_pet_site_deja_enregistre');
			}
		}
	}

	return $erreurs;
}

function formulaires_signature_traiter_dist($id_article, $petition, $texte, $site_obli, $message) {
	$reponse = _T('form_pet_probleme_technique');
	include_spip('base/abstract_sql');
	if (spip_connect()) {
		$controler_signature = charger_fonction('controler_signature', 'inc');
		$reponse = $controler_signature($id_article,
		_request('session_nom'), _request('session_email'),
		_request('message'), _request('signature_nom_site'),
		_request('signature_url_site'), _request('url_page'));
	}

	return array('message_ok'=>$reponse);
}

// Retour a l'ecran du lien de confirmation d'une signature de petition.
// Si var_confirm est non vide, c'est l'appel dans public/assembler.php
// pour vider le cache au demarrage afin que la nouvelle signature apparaisse.
// Sinon, c'est l'execution du formulaire et on retourne le message 
// de confirmation ou d'erreur construit lors de l'appel par assembler.php
// Le controle d'unicite du mail ou du site (si requis) refait ici correspond
// au cas de mails de demande de confirmation laisses sans reponse

// http://doc.spip.org/@reponse_confirmation_dist
function formulaires_signature_reponse_confirmation_dist($var_confirm = '') {
	static $confirm = null;

	// reponse mise en cache dans la session ?
	$code_message = 'signature_message_'.strval($var_confirm);
	if (isset($GLOBALS['visiteur_session'][$code_message]))
		return $GLOBALS['visiteur_session'][$code_message];

	// reponse deja calculee depuis public/assembler.php
	if (isset($confirm))
		return $confirm;

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
			sql_updateq("spip_signatures", array("statut" => 'poubelle'), "id_signature=$id_signature");
			$confirm = _T('info_signature_supprimee');
		} else $confirm = _T('info_signature_supprimee_erreur');
		return '';
	}

	$row = sql_fetsel('*', 'spip_signatures', "statut=" . sql_quote($var_confirm), '', "1");

	if (!$row) {
		$confirm = _T('form_pet_aucune_signature');
		return '';
	}

	$id_signature = $row['id_signature'];
	$id_article = $row['id_article'];
	$adresse_email = $row['ad_email'];
	$url_site = $row['url_site'];

	$row = sql_fetsel('email_unique, site_unique', 'spip_petitions', "id_article=$id_article");

	$email_unique = $row['email_unique']  == "oui";
	$site_unique = $row['site_unique']  == "oui";

	sql_updateq('spip_signatures',
		    array('statut' => 'publie', 'date_time' => date('Y-m-d H:i:s')),
		    "id_signature=$id_signature");

	if ($email_unique) {

		$r = "id_article=$id_article AND ad_email=" . sql_quote($adresse_email);
		if (signature_entrop($r))
			  $confirm =  _T('form_pet_deja_signe');
	} 

	if ($site_unique) {
		$r = "id_article=$id_article AND url_site=" . sql_quote($url_site);
		if (signature_entrop($r))
			$confirm = _T('form_pet_site_deja_enregistre');
	}

	include_spip('inc/session');

	if (!$confirm) {
		$confirm = _T('form_pet_signature_validee');

		// noter dans la session que l'email est valide
		// de facon a permettre de signer les prochaines
		// petitions sans refaire un tour d'email
		session_set('email_confirme', $adresse_email);

		// invalider les pages ayant des boucles signatures
		include_spip('inc/invalideur');
		suivre_invalideur("id='varia/pet$id_article'");
	}

	// Conserver la reponse dans la session du visiteur
	if ($confirm)
		session_set($code_message, $confirm);
}

//
// Recevabilite de la signature d'une petition
// les controles devraient mantenant etre faits dans formulaires_signature_verifier()
// 

// http://doc.spip.org/@inc_controler_signature_dist
function inc_controler_signature_dist($id_article, $nom, $mail, $message, $site, $url_site, $url_page) {

	include_spip('inc/texte');
	include_spip('inc/filtres');

	// tout le monde est la.
	// cela a ete verifie en amont, dans formulaires_signature_verifier()
	if (!$row = sql_fetsel('*', 'spip_petitions', "id_article=$id_article"))
		return _T('form_pet_probleme_technique');

	$statut = "";
	if (!$ret = signature_a_confirmer($id_article, $url_page, $nom, $mail, $site, $url_site, $message, $lang, $statut))
		return _T('form_pet_probleme_technique');

	$id_signature = sql_insertq('spip_signatures', array(
		'id_article' => $id_article,
		'date_time' => date('Y-m-d H:i:s'),
		'statut' => $statut,
		'ad_email' => $mail,
		'url_site' => $url_site));

	if (!$id_signature) return _T('form_pet_probleme_technique');

	include_spip('inc/modifier');
	revision_signature($id_signature, array(
		'nom_email' => $nom,
		'ad_email' => $mail,
		'message' => $message,
		'nom_site' => $site,
		'url_site' => $url_site
	));

	return $ret;
}

// http://doc.spip.org/@signature_a_confirmer
function signature_a_confirmer($id_article, $url_page, $nom, $mail, $site, $url, $msg, $lang, &$statut)
{

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
		suivre_invalideur("id='varia/pet$id_article'");

		// message de reussite : en ajax, preciser qu'il faut recharger la page
		// pour voir le resultat
		return
			_T('form_pet_signature_validee');
	}


	//
	// Cas normal : envoi d'une demande de confirmation
	//
	$row = sql_fetsel('titre,lang', 'spip_articles', "id_article=$id_article");
	$lang = lang_select($row['lang']);
	$titre = textebrut(typo($row['titre']));
	if ($lang) lang_select();

	if (!strlen($statut))
		$statut = signature_test_pass();

	if ($lang != $GLOBALS['meta']['langue_site'])
		  $url_page = parametre_url($url_page, "lang", $lang,'&');

	$url_page = parametre_url($url_page, 'var_confirm', $statut, '&')
	. "#sp$id_article";

	$r = _T('form_pet_mail_confirmation',
		 array('titre' => $titre,
		       'nom_email' => $nom,
		       'nom_site' => $site,
		       'url_site' => $url, 
		       'url' => $url_page,
		       'message' => $msg));

	$titre = _T('form_pet_confirmation')." ". $titre;
	$envoyer_mail = charger_fonction('envoyer_mail','inc');
	if ($envoyer_mail($mail,$titre, $r))
		return _T('form_pet_envoi_mail_confirmation',array('email'=>$mail));

	return false; # erreur d'envoi de l'email
}

// Pour eviter le recours a un verrou (qui bloque l'acces a la base),
// on commence par inserer systematiquement la signature 
// puis on demande toutes celles ayant la propriete devant etre unique
// (mail ou site). S'il y en a plus qu'une on les retire sauf la premiere
// En cas d'acces concurrents il y aura des requetes de retraits d'elements
// deja detruits. Bizarre ?  C'est mieux que de bloquer!

// http://doc.spip.org/@signature_entrop
function signature_entrop($where)
{
	$where .= " AND statut='publie'";
	$query = sql_select('id_signature', 'spip_signatures', $where,'',"date_time desc");
	$n = sql_count($query);
	if ($n>1) {
		$entrop = array();
		for ($i=$n-1;$i;$i--) {
			$r = sql_fetch($query);
			$entrop[]=$r['id_signature'];
		}
		sql_free($query);
		$where .= " OR " . sql_in('id_signature', $entrop);
	
		sql_delete('spip_signatures', $where);
	}

	return $entrop;
}

// Creer un mot de passe aleatoire et verifier qu'il est unique
// dans la table des signatures
// http://doc.spip.org/@signature_test_pass
function signature_test_pass() {
	include_spip('inc/acces');
	do {
		$passw = creer_pass_aleatoire();
	} while (sql_countsel('spip_signatures', "statut='$passw'") > 0);

	return $passw;
}

?>
