<?php

include_ecrire('inc_meta.php3');
include_ecrire('inc_forum.php3');
include_ecrire("inc_abstract_sql.php3");


// Ce fichier inclus par inc-public a un comportement special
// Voir commentaires dans celui-ci et dans inc-formulaire_forum

function prevenir_auteurs($auteur, $email_auteur, $id_article, $texte, $titre) {
	include_ecrire('inc_texte.php3');
	include_ecrire('inc_filtres.php3');
	include_ecrire('inc_mail.php3');
	// Gestionnaire d'URLs
	if (@file_exists("inc-urls.php3"))
	  include_local("inc-urls.php3");
	else
	  include_local("inc-urls-".$GLOBALS['type_urls'].".php3");
	$url = ereg_replace('^/', '', generer_url_article($id_article));
	$adresse_site = lire_meta("adresse_site");
	$nom_site_spip = lire_meta("nom_site");
	$url = "$adresse_site/$url";
	$parauteur = (strlen($auteur) <= 2) ? '' :
	  (" "
	   ._T('forum_par_auteur',
	       array('auteur' => $auteur)) .
	   (!$email_auteur ? '' : (' <' . $email_auteur . '>')));
	$courr =  _T('form_forum_message_auto')."\n\n"
		. _T('forum_poste_par',
		     array('parauteur' => $parauteur))."\n"
		. _T('forum_ne_repondez_pas')."\n"
		. $url
		. "\n\n\n".$titre."\n\n".textebrut(propre($texte))
		. "\n\n$nom_site_forum\n$url_site\n";
	$sujet = "[$nom_site_spip] ["._T('forum_forum')."] $titre";
	$result = spip_query("SELECT auteurs.email FROM spip_auteurs AS auteurs,
				spip_auteurs_articles AS lien
				WHERE lien.id_article='$id_article'
				AND auteurs.id_auteur=lien.id_auteur");

	while (list($email) = spip_fetch_array($result)) {
		$email = trim($email);
		if (strlen($email) < 3) continue;
		envoyer_mail($email, $sujet, $courr);
	}
}


function controler_forum($id_article, $retour)
{
	global $auteur_session;
	if ($id_article) {
		$r = spip_query("SELECT accepter_forum FROM spip_articles WHERE id_article=$id_article");
		$r = spip_fetch_array($r);
		if ($r)
			$forums_publics = $r['accepter_forum'];
		else
			$forums_publics = lire_meta("forums_publics");
	} else {
		$forums_publics = substr(lire_meta("forums_publics"),0,3);
	}

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

function enregistre_forum()
{
	global $REMOTE_ADDR, $auteur_session,
	  $afficher_texte, $ajouter_mot, $alea, $hash,
	  $auteur, $confirmer_forum, $email_auteur,
	  $id_article, $id_auteur, $id_breve, $id_forum, $id_rubrique, $id_syndic,
	  $nom_site_forum, $retour_forum, $texte, $titre, $url_site;

	$retour_forum = rawurldecode($retour_forum);
	$id_article = intval($id_article);
	$id_rubrique = intval($id_rubrique);
	$id_forum = intval($id_forum);
	$id_breve = intval($id_breve);
	$id_syndic = intval($id_syndic);

// initialisation de l'eventuel visiteur connecte
	if (!$id_auteur)
	$id_auteur = intval($auteur_session['id_auteur']);

	$statut == controler_forum($id_article, $retour_forum);

// Ne pas autoriser de changement de nom si forum sur abonnement

	if ($statut == 'abo') {
		$auteur = $auteur_session['nom'];
		$email_auteur = $auteur_session['email'];
	}

// trop court ?
	if ((strlen($texte) + strlen($titre) + strlen($nom_site_forum) + strlen($url_site) + strlen($auteur) + strlen($email_auteur)) > 20 * 1024) {
		ask_php_auth(_T('forum_message_trop_long'),
			     _T('forum_cliquer_retour',
				array('retour_forum' => $retour_forum)));
		exit;
	}

	// Verifier hash securite
	include_ecrire("inc_admin.php3");
	if (!verifier_action_auteur("ajout_forum $id_rubrique".
	" $id_forum $id_article $id_breve".
	" $id_syndic $alea", $hash))
		exit; 	# echec silencieux du POST

	// verifier fichier lock
	$alea = preg_replace('/[^0-9]/', '', $alea);
	if (!file_exists($hash = _DIR_SESSIONS."forum_$alea.lck"))
		exit; # echec silencieux du POST
	unlink($hash);

	// Entrer le message dans la base
	$id_message = spip_abstract_insert('spip_forum', '(date_heure)', '(NOW())');

	if ($id_forum)
		list($id_thread) = spip_fetch_array(spip_query(
		"SELECT id_thread FROM spip_forum WHERE id_forum = $id_forum"));
	else
		$id_thread = $id_message; # id_thread oblige INSERT puis UPDATE.

	$statut = ($statut == 'non') ? 'off' : (($statut == 'pri') ? 'prop' :
						'publie');

	spip_query("UPDATE spip_forum SET id_parent = $id_forum,
	id_rubrique = $id_rubrique,
	id_article = $id_article,
	id_breve = $id_breve,
	id_syndic = $id_syndic,
	id_auteur = $id_auteur,
	id_thread = $id_thread,
	date_heure = NOW(),
	titre = '".addslashes($titre)."',
	texte = '".addslashes($texte)."',
	nom_site = '".addslashes($nom_site_forum)."',
	url_site = '".addslashes($url_site)."',
	auteur = '".addslashes($auteur)."',
	email_auteur = '".addslashes($email_auteur)."',
	ip = '$REMOTE_ADDR',
	statut = '$statut'
	WHERE id_forum = $id_message
	");

	// calculer_threads();

	// Entrer les mots-cles associes
	if (is_array($ajouter_mot)) mots_du_forum($ajouter_mot, $id_message);

	// Prevenir les auteurs de l'article
	if (lire_meta("prevenir_auteurs") == "oui" AND ($afficher_texte != "non"))
		prevenir_auteurs($auteur, $email_auteur, $id_article, $texte, $titre);

	// Poser un cookie pour ne pas retaper le nom / email

	spip_setcookie('spip_forum_user',
		       serialize(array('nom' => $auteur, 'email' => $email_auteur)));

	if ($statut == 'publie') {
	//
	// INVALIDATION DES CACHES LIES AUX FORUMS
	//
		include_ecrire('inc_invalideur.php3');
		suivre_invalideur ("id='id_forum/" .
			calcul_index_forum($id_article,
				$id_breve,
				$id_rubrique,
				$id_syndic) . "'");
	}

	return $retour_forum;
}

?>
