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

include_spip('base/abstract_sql');

// Balise independante du contexte


function balise_FORMULAIRE_INSCRIPTION ($p) {

	return calculer_balise_dynamique($p, 'FORMULAIRE_INSCRIPTION', array());
}

// args[0] indique le focus eventuel
// args[1] indique la rubrique eventuelle de proposition
// [(#FORMULAIRE_INSCRIPTION{nom_inscription, #ID_RUBRIQUE})]
function balise_FORMULAIRE_INSCRIPTION_stat($args, $filtres) {
	if ($GLOBALS['meta']['accepter_inscriptions'] != 'oui')
		return '';
	else
	  return array('redac', $args[0], $args[1]);
}

// Si inscriptions pas autorisees, retourner une chaine d'avertissement
// Sinon inclusion du squelette
// Si pas de mon ou pas de mail valide, premier appel rien d'autre a faire
// Autrement 2e appel, envoyer un mail et le squelette ne produira pas de
// formulaire.


function balise_FORMULAIRE_INSCRIPTION_dyn($mode, $focus, $id_rubrique=0) {

	if (!(($mode == 'redac' AND $GLOBALS['meta']['accepter_inscriptions'] == 'oui')
	OR ($mode == 'forum' AND (
		$GLOBALS['meta']['accepter_visiteurs'] == 'oui'
		OR $GLOBALS['meta']['forums_publics'] == 'abo'
		)
	    )))
		return _T('pass_rien_a_faire_ici');

	$nom = _request('nom_inscription');
	$mail = _request('mail_inscription');
	if (!$mail)
		$message = '';
	else {
		include_spip('inc/filtres'); // pour email_valide
		$message = message_inscription($mail, $nom, $mode, $id_rubrique);
		if (is_array($message)) {
			if (function_exists('envoyer_inscription'))
				$f = 'envoyer_inscription';
			else 
				$f = 'envoyer_inscription_dist';
			$message = $f($message, $nom, $mode, $id_rubrique);
		}
	}

	// #ENV*{message} est le message d'erreur
	// #ENV*{commentaire} explique si on s'inscrit a l'espce public ou prive
	// il disparait s'il y a un message d'erreur (pour faire moins verbeux)
	$commentaire = '';
	if (!$message) {
		if ($mode=='redac') $commentaire = _T('pass_espace_prive_bla');
		if ($mode=='forum') $commentaire = _T('pass_forum_bla');
	}

	return array("formulaire_inscription", $GLOBALS['delais'],
			array('focus' => $focus,
				'message' => $message,
				'mode' => $mode,
				'commentaire' => $commentaire,
				'nom_inscription' => _request('nom_inscription'),
				'mail_inscription' => _request('mail_inscription'),
				'self' => str_replace('&amp;','&',(self()))));
}

// fonction qu'on peut redefinir pour filtrer les adresses mail et les noms,
// et donner des infos supplémentaires
// Std: controler que le nom (qui sert a calculer le login) est assez long
// et que l'adresse est valide (et on la normalise)
// Retour: une chaine message d'erreur 
// ou un tableau avec au minimum email, nom, mode (redac / forum)

function test_inscription_dist($mode, $mail, $nom, $id_rubrique=0) {

	include_spip('inc/filtres');
	$nom = trim(corriger_caracteres($nom));
	if (!$nom || strlen($nom) > 64)
	    return _T('ecrire:info_login_trop_court');
	if (!$r = email_valide($mail)) return _T('info_email_invalide');
	return array('email' => $r, 'nom' => $nom, 'bio' => $mode);
}

// cree un nouvel utilisateur et renvoie un message d'impossibilite ou la
// ligne SQL le decrivant.

function message_inscription($mail, $nom, $mode, $id_rubrique=0) {

	if (function_exists('test_inscription'))
		$f = 'test_inscription';
	else 
		$f = 'test_inscription_dist';
	$declaration = $f($mode, $mail, $nom, $id_rubrique);

	if (is_string($declaration))
		return  $declaration;

	$row = spip_query("SELECT statut, id_auteur, login, email FROM spip_auteurs WHERE email='". addslashes($declaration['email']) . "'");
	$row = spip_fetch_array($row);

	if (!$row) 
		// il n'existe pas, creer les identifiants  
		return inscription_nouveau($declaration);
	if (($row['statut'] == '5poubelle') AND !$declaration['pass'])
		// irrecuperable
		return _T('form_forum_access_refuse');

	if (($row['statut'] != 'nouveau') AND !$declaration['pass'])
		// deja inscrit
		return _T('form_forum_email_deja_enregistre');

	// existant mais encore muet, ou ressucite: renvoyer les infos
	$row['pass'] = creer_pass_pour_auteur($row['id_auteur']);
	return $row;
}

// On enregistre le demandeur comme 'nouveau', en memorisant le statut final
// provisoirement dans le champ Bio, afin de ne pas visualiser les inactifs
// A sa premiere connexion il obtiendra son statut final (auth->activer())

function inscription_nouveau($declaration)
{
	if (!isset($declaration['login']))
		$declaration['login'] = test_login($declaration['nom'], $declaration['email']);

	$declaration['statut'] = 'nouveau';

	$n = spip_abstract_insert('spip_auteurs', ('(' .join(',',array_keys($declaration)).')'), ("('" .join("','",array_map('addslashes', $declaration)) ."')"));

	$declaration['id_auteur'] = $n;

	$declaration['pass'] = creer_pass_pour_auteur($declaration['id_auteur']);
	return $declaration;
}

// envoyer identifiants par mail
// fonction redefinissable

function envoyer_inscription_dist($ids, $nom, $mode, $id_rubrique) {
	$nom_site_spip = $GLOBALS['meta']["nom_site"];
	$adresse_site = $GLOBALS['meta']["adresse_site"];
	
	$message = _T('form_forum_message_auto')."\n\n"
	  . _T('form_forum_bonjour', array('nom'=>$nom))."\n\n"
	  . _T((($mode == 'forum')  ?
		'form_forum_voici1' :
		'form_forum_voici2'),
	       array('nom_site_spip' => $nom_site_spip,
		     'adresse_site' => $adresse_site . '/',
		     'adresse_login' => $adresse_site .'/'. _DIR_RESTREINT_ABS))
	  . "\n\n- "._T('form_forum_login')." " . $ids['login']
	  . "\n- ".  _T('form_forum_pass'). " " . $ids['pass'] . "\n\n";

	include_spip('inc/mail');
	if (envoyer_mail($ids['email'],
			 "[$nom_site_spip] "._T('form_forum_identifiants'),
			 $message))
		return _T('form_forum_identifiant_mail');
	else
		return _T('form_forum_probleme_mail');
}

function test_login($nom, $mail) {
	include_spip('inc/charsets');
	$nom = strtolower(translitteration($nom));
	$login_base = ereg_replace("[^a-zA-Z0-9_]", "_", $nom);

	// il faut eviter que le login soit vraiment trop court
	if (strlen($login_base) < 3) {
		$mail = strtolower(translitteration(preg_replace('/@.*/', '', $mail)));
		$login_base = ereg_replace("[^a-zA-Z0-9]", "_", $nom);
	}
	if (strlen($login_base) < 3)
		$login_base = 'user';

	// eviter aussi qu'il soit trop long (essayer d'attraper le prenom)
	if (strlen($login_base) > 10) {
		$login_base = preg_replace("/^(.{4,}(_.{1,7})?)_.*/",
			'\1', $login_base);
		$login_base = substr($login_base, 0,13);
	}

	$login = $login_base;

	for ($i = 1; ; $i++) {
		$n = spip_num_rows(spip_query("SELECT id_auteur FROM spip_auteurs WHERE login='$login' LIMIT 1"));
		if (!$n) return $login;
		$login = $login_base.$i;
	}
}

function creer_pass_pour_auteur($id_auteur) {
	include_spip('inc/acces');
	$pass = creer_pass_aleatoire(8, $id_auteur);
	$mdpass = md5($pass);
	$htpass = generer_htpass($pass);
	spip_query("UPDATE spip_auteurs	SET pass='$mdpass', htpass='$htpass' WHERE id_auteur = ".intval($id_auteur));
	ecrire_acces();
	
	return $pass;
}

?>
