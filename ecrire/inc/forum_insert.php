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
include_spip('inc/meta');
include_spip('inc/forum');
include_spip('inc/filtres');
include_spip('base/abstract_sql');
include_spip('inc/actions');

spip_connect();

// Ce fichier est inclus lorsqu'on appelle un script de l'espace public
// avec une variable d'URL nommee confirmer_forum 
// Voir commentaires dans balise/formulaire_forum


// http://doc.spip.org/@controler_forum_abo
function controler_forum_abo($retour)
{
	global $auteur_session;
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

// http://doc.spip.org/@controler_forum
function controler_forum($id) {

	// Reglage forums d'article
	if ($id) {
		$q = spip_query("SELECT accepter_forum FROM spip_articles WHERE id_article=$id");
		if ($r = spip_fetch_array($q))
			$id = $r['accepter_forum'];
	}

	// Valeur par defaut
	return $id ? $id: substr($GLOBALS['meta']["forums_publics"],0,3);


}

// http://doc.spip.org/@mots_du_forum
function mots_du_forum($ajouter_mot, $id_message)
{
	foreach ($ajouter_mot as $id_mot)
		if ($id_mot = intval($id_mot))
		  spip_abstract_insert('spip_mots_forum', '(id_mot, id_forum)', "($id_mot, $id_message)");
}


// http://doc.spip.org/@reduce_strlen
function reduce_strlen($n, $c) 
{
  return $n - strlen($c);
}


// http://doc.spip.org/@tracer_erreur_forum
function tracer_erreur_forum($type='') {
	spip_log("erreur forum ($type): ".print_r($_POST, true));
	include_spip('inc/mail');
	envoyer_mail($GLOBALS['meta']['email_webmaster'], "erreur forum ($type)",
		"erreur sur le forum ($type) :\n\n".
		'$_POST = '.print_r($_POST, true)."\n\n".
		'$_SERVER = '.print_r($_SERVER, true));
}

// http://doc.spip.org/@inc_forum_insert_dist
function inc_forum_insert_dist() {

	// Ne pas se laisser polluer par les pollueurs de globales
	$id_article = intval(_request('id_article'));
	$id_breve = intval(_request('id_breve'));
	$id_forum = intval(_request('id_forum'));
	$id_rubrique = intval(_request('id_rubrique'));
	$id_syndic = intval(_request('id_syndic'));
	$afficher_texte = _request('afficher_texte');
	$ajouter_mot = _request('ajouter_mot');
	$retour_forum = _request('retour_forum');

	$retour_forum = rawurldecode($retour_forum);

	# retour a calculer (cf. inc-formulaire_forum)
	if ($retour_forum == '!') {
		// on calcule a priori l'adresse de retour {en cas d'echec du POST}
		charger_generer_url();
		if ($id_forum)
			$retour_forum = generer_url_forum($id_forum);
		elseif ($id_article)
			$retour_forum = generer_url_article($id_article);
		elseif ($id_breve)
			$retour_forum = generer_url_breve($id_breve);
		elseif ($id_syndic)
			$retour_forum = generer_url_syndic($id_syndic);
		elseif ($id_rubrique) # toujours en dernier
			$retour_forum = generer_url_rubrique($id_rubrique);
		$retour_forum = str_replace('&amp;','&',$retour_forum);

		// mais la veritable adresse de retour sera calculee apres insertion
		$calculer_retour = true;
	}

	if (array_reduce($_POST, 'reduce_strlen', (20 * 1024)) < 0) {
		ask_php_auth(_T('forum_message_trop_long'),
			_T('forum_cliquer_retour',
				array('retour_forum' => $retour_forum)));
		exit;
	}

	// Verifier hash securite pour les forums avec previsu
	if ($afficher_texte <> 'non') {

		// simuler une action venant de l'espace public
		// pour se conformer au a general.
		set_request('action', 'ajout_forum');
	        $securiser_action = charger_fonction('securiser_action', 'inc');
        	$securiser_action();

		$file = _DIR_TMP ."forum_" . preg_replace('/[^0-9]/', '', _request('arg')) .".lck";
		if (!file_exists($file)) {
			# ne pas tracer cette erreur, peut etre due a un double POST
			# tracer_erreur_forum('session absente');
			return $retour_forum; # echec silencieux du POST
		}
		unlink($file);

		// antispam : si le champ au nom aleatoire verif_$hash n'est pas 'ok'
		// on meurt
		if (_request('verif_'._request('hash')) != 'ok') {
			tracer_erreur_forum('champ verif manquant');
			return $retour_forum;
		}
	}

	// id_rubrique est parfois passee pour les articles, on n'en veut pas
	if ($id_rubrique > 0 AND ($id_article OR $id_breve OR $id_syndic))
		$id_rubrique = 0;

	$statut = controler_forum($id_article);

	// Ne pas autoriser de changement de nom si forum sur abonnement
	if ($statut == 'abo') {
		controler_forum_abo($retour_forum);
		set_request('auteur', $GLOBALS['auteur_session']['nom']);
		set_request('email_auteur', $GLOBALS['auteur_session']['email']);
	}

	$statut = ($statut == 'non') ? 'off' : (($statut == 'pri') ? 'prop' :
						'publie');

	// Antispam : si 'nobot' a ete renseigne, ca ne peut etre qu'un bot
	if (strlen(_request('nobot'))) {
		tracer_erreur_forum('champ interdit (nobot) rempli');
		return $retour_forum; # echec silencieux du POST
	}

	// Entrer le message dans la base
	$id_message = spip_abstract_insert('spip_forum', '(date_heure)', '(NOW())');

	if ($id_forum) {
		$id_thread = spip_fetch_array(spip_query("SELECT id_thread FROM spip_forum WHERE id_forum = $id_forum"));
		$id_thread = $id_thread['id_thread'];
	}
	else
		$id_thread = $id_message; # id_thread oblige INSERT puis UPDATE.

	spip_query("UPDATE spip_forum SET id_parent = $id_forum, id_rubrique = $id_rubrique, id_article = $id_article, id_breve = $id_breve, id_syndic = $id_syndic, id_thread = $id_thread, statut = '$statut' WHERE id_forum = $id_message");

	// Entrer les mots-cles associes
	if (is_array($ajouter_mot)) mots_du_forum($ajouter_mot, $id_message);

	//
	// Entree du contenu et invalidation des caches
	//
	include_spip('inc/modifier');
	revision_forum($id_message);

	// Notification
	if ($notifications = charger_fonction('notifications', 'inc'))
		$notifications('forumposte', $id_message);

	// En cas de retour sur (par exemple) {#SELF}, on ajoute quand
	// meme #forum12 a la fin de l'url, sauf si un #ancre est explicite
	if (!$calculer_retour)
		return strstr('#', $retour_forum) ?
			$retour_forum
			: $retour_forum.'#forum'.$id_message;

	// le retour automatique envoie sur le thread, ce qui permet
	// de traiter elegamment le cas des forums moderes a priori.
	// Cela assure aussi qu'on retrouve son message dans le thread
	// dans le cas des forums moderes a posteriori, ce qui n'est
	// pas plus mal.

	charger_generer_url();
	return generer_url_forum($id_message);
}
?>
