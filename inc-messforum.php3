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

if (!defined("_ECRIRE_INC_VERSION")) return;

include_ecrire('inc_meta');
include_ecrire('inc_forum');
include_ecrire('inc_filtres');
include_ecrire("inc_abstract_sql");
include_local(_FILE_CONNECT);

// Ce fichier inclus par inc-public a un comportement special
// Voir commentaires dans celui-ci et dans inc-formulaire_forum

function prevenir_auteurs($auteur, $email_auteur, $id_forum, $id_article, $texte, $titre, $statut) {
	global $nom_site_forum, $url_site;
	include_ecrire('inc_texte');
	include_ecrire('inc_filtres');
	include_ecrire('inc_mail');
	charger_generer_url();

	if ($statut == 'prop') # forum modere
	  $url = _DIR_RESTREINT_ABS .
	    generer_url_ecrire('controle_forum', "debut_id_forum=$id_forum");
	else if (function_exists('generer_url_forum'))
		$url = generer_url_forum($id_forum);
	else {
		spip_log('inc-urls personnalise : ajoutez generer_url_forum() !');
		$url = generer_url_article($id_article);
	}

	$adresse_site = $GLOBALS['meta']["adresse_site"];
	$url = $adresse_site .'/' .  ereg_replace('^/', '', $url);

	$sujet = "[" .
	  entites_html(textebrut(typo($GLOBALS['meta']["nom_site"]))) .
	  "] ["._T('forum_forum')."] $titre";

	$parauteur = (strlen($auteur) <= 2) ? '' :
	  (" " ._T('forum_par_auteur', array('auteur' => $auteur)) . 
	   ($email_auteur ? "" : (' <' . $email_auteur . '>')));

	$corps = _T('form_forum_message_auto') .
		"\n\n" .
		_T('forum_poste_par', array('parauteur' => $parauteur)).
		"\n"
	  	. _T('forum_ne_repondez_pas')
	  	. "\n"
		. $url
		. "\n\n\n".$titre."\n\n".textebrut(propre($texte))
		. "\n\n$nom_site_forum\n$url_site\n";

	$old_lang = $GLOBALS['spip_lang'];

	$result = spip_query("SELECT auteurs.email, auteurs.lang FROM spip_auteurs AS auteurs,
				spip_auteurs_articles AS lien
				WHERE lien.id_article='$id_article'
				AND auteurs.id_auteur=lien.id_auteur");
	while (list($email, $salangue) = spip_fetch_array($result)) {
		$email = trim($email);
		if (strlen($email) < 3) continue;
		$GLOBALS['spip_lang'] = ($salangue ? $salangue : $old_lang);
		envoyer_mail($email, $sujet, $corps) ;
	}
	$GLOBALS['spip_lang'] = $old_lang;	
}


function controler_forum($id_article, $retour) {
	global $auteur_session;

	// Reglage forums d'article
	if ($id_article) {
		$q = spip_query("SELECT accepter_forum FROM spip_articles
			WHERE id_article=$id_article");
		if ($r = spip_fetch_array($q))
			$forums_publics = $r['accepter_forum'];
	}

	// Valeur par defaut
	if (!$forums_publics)
		$forums_publics = substr($GLOBALS['meta']["forums_publics"],0,3);

	if ($forums_publics == "abo") {
		if ($auteur_session) {
			$statut = $auteur_session['statut'];
			if (!$statut OR $statut == '5poubelle') {
				ask_php_auth(_T('forum_acces_refuse'),
					     _T('forum_cliquer_retour',
						array('retour_forum' => $retour)));
				exit;
			}
		} else {
			ask_php_auth(_T('forum_non_inscrit'),
				     _T('forum_cliquer_retour',
					array('retour_forum' => $retour)));
			exit;
		}
	}

	return $forums_publics;
}

function mots_du_forum($ajouter_mot, $id_message)
{
	foreach ($ajouter_mot as $id_mot)
		if ($id_mot = intval($id_mot))
			spip_query("INSERT INTO spip_mots_forum (id_mot, id_forum)
				VALUES ($id_mot, $id_message)");
}

function enregistre_forum() {
	global $REMOTE_ADDR, $auteur_session,
		$afficher_texte, $ajouter_mot, $alea, $hash,
		$auteur, $confirmer_forum, $email_auteur, $id_auteur,
		$nom_site_forum, $retour_forum, $texte, $titre, $url_site,
		$id_rubrique, $id_forum, $id_article, $id_breve, $id_syndic;

	$retour_forum = rawurldecode($retour_forum);

	# retour a calculer (cf. inc-formulaire_forum)
	if ($retour_forum == '!') {
		$retour_forum = new Link();
		$retour_forum = $retour_forum->getUrl(); # en cas d'echec du post
		$calculer_retour = true;
	}

	// initialisation de l'eventuel visiteur connecte
	if (!$id_auteur)
	$id_auteur = intval($auteur_session['id_auteur']);

	$statut = controler_forum($id_article, $retour_forum);

	// Ne pas autoriser de changement de nom si forum sur abonnement
	if ($statut == 'abo') {
		$auteur = $auteur_session['nom'];
		$email_auteur = $auteur_session['email'];
	}

	// trop court ?
	if ((strlen($texte) + strlen($titre) + strlen($nom_site_forum) +
	strlen($url_site) + strlen($auteur) + strlen($email_auteur)) > 20 * 1024) {
		ask_php_auth(_T('forum_message_trop_long'),
			_T('forum_cliquer_retour',
				array('retour_forum' => $retour_forum)));
		exit;
	}

	// Verifier hash securite pour les forums avec previsu
	if ($GLOBALS['afficher_texte'] <> 'non') {
		include_ecrire("inc_session");
		if (!verifier_action_auteur("ajout_forum $id_rubrique".
		" $id_forum $id_article $id_breve".
		" $id_syndic $alea", $hash)) {
			spip_log('erreur hash forum');
			die (_T('forum_titre_erreur')); 	# echec du POST
		}

		// verifier fichier lock
		$alea = preg_replace('/[^0-9]/', '', $alea);
		if (!file_exists($hash = _DIR_SESSIONS."forum_$alea.lck"))
			return $retour_forum; # echec silencieux du POST
		unlink($hash);
	}

	// Entrer le message dans la base
	$id_message = spip_abstract_insert('spip_forum', '(date_heure)', '(NOW())');

	if ($id_forum)
		list($id_thread) = spip_fetch_array(spip_query(
		"SELECT id_thread FROM spip_forum WHERE id_forum = $id_forum"));
	else
		$id_thread = $id_message; # id_thread oblige INSERT puis UPDATE.

	$statut = ($statut == 'non') ? 'off' : (($statut == 'pri') ? 'prop' :
						'publie');

	spip_query("UPDATE spip_forum
	SET id_parent = $id_forum,
	id_rubrique = $id_rubrique,
	id_article = $id_article,
	id_breve = $id_breve,
	id_syndic = $id_syndic,
	id_auteur = $id_auteur,
	id_thread = $id_thread,
	date_heure = NOW(),
	titre = '".addslashes(corriger_caracteres($titre))."',
	texte = '".addslashes(corriger_caracteres($texte))."',
	nom_site = '".addslashes(corriger_caracteres($nom_site_forum))."',
	url_site = '".addslashes(corriger_caracteres($url_site))."',
	auteur = '".addslashes(corriger_caracteres($auteur))."',
	email_auteur = '".addslashes(corriger_caracteres($email_auteur))."',
	ip = '$REMOTE_ADDR',
	statut = '$statut'
	WHERE id_forum = $id_message
	");

	// Le cas echeant, calculer le retour
	if ($calculer_retour) {
		charger_generer_url();

		// le retour automatique envoie sur le thread, ce qui permet
		// de traiter elegamment le cas des forums moderes a priori.
		// Cela assure aussi qu'on retrouve son message dans le thread
		// dans le cas des forums moderes a posteriori, ce qui n'est
		// pas plus mal.
		$retour_forum = generer_url_forum($id_message, true);
	}

	// Entrer les mots-cles associes
	if (is_array($ajouter_mot)) mots_du_forum($ajouter_mot, $id_message);

	// Prevenir les auteurs de l'article
	if ($GLOBALS['meta']["prevenir_auteurs"] == "oui" AND ($afficher_texte != "non"))
		prevenir_auteurs($auteur, $email_auteur, $id_message, $id_article, $texte, $titre, $statut);

	// Poser un cookie pour ne pas retaper le nom / email
	include_ecrire('inc_cookie');
	spip_setcookie('spip_forum_user',
		       serialize(array('nom' => $auteur, 'email' => $email_auteur)));

	if ($statut == 'publie') {
	//
	// INVALIDATION DES CACHES LIES AUX FORUMS
	//
		include_ecrire('inc_invalideur');
		suivre_invalideur ("id='id_forum/" .
			calcul_index_forum($id_article,
				$id_breve,
				$id_rubrique,
				$id_syndic) . "'");
	}

	return $retour_forum;
}

?>
