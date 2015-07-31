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

include_ecrire('inc_abstract_sql');

// Balise independante du contexte
global $balise_FORMULAIRE_INSCRIPTION_collecte ;
$balise_FORMULAIRE_INSCRIPTION_collecte = array();

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
	if (!$nom)
		$message = '';
	else {
		$message = message_inscription($mail, $nom, false, $mode);
		if (is_array($message)) {
			if (function_exists('envoyer_inscription'))
				$f = 'envoyer_inscription';
			else 
				$f = 'envoyer_inscription_dist';
			$message = $f($message, $nom, $mode, $id_rubrique);
		}
	}
	return array("formulaire_inscription", $GLOBALS['delais'],
			array('focus' => $focus,
				'message' => $message,
				'mode' => $mode,
				'self' => $GLOBALS["clean_link"]->getUrl()
				));
}

// fonction qu'on peut redefinir pour filtrer les adresses mail
// cas general: controler juste que l'adresse n'est pas vide et est valide

function test_inscription_dist($mode, $mail) {
	include_ecrire('inc_filtres');
	return email_valide($mail);
}

// cree un nouvel utilisateur et renvoie un message d'impossibilite ou la
// ligne SQL le decrivant.
// On enregistre le demandeur comme 'nouveau' 
// et on lui envoie ses codes par email ; lors de
// sa premiere connexion il obtiendra son statut final (auth->activer())

function message_inscription($mail_inscription, $nom_inscription, $force, $mode) {

	if (function_exists('test_inscription'))
	    $f = 'test_inscription';
	else 
	  $f  = 'test_inscription_dist';
	if (!($mail_inscription = $f($mode, $mail_inscription)))
		return  _T('info_email_invalide');

	$mail = addslashes($mail_inscription);
	$s = spip_query("SELECT statut, id_auteur, login, email
		FROM spip_auteurs WHERE email='". $mail ."'");
	$row = spip_fetch_array($s);

	if (!$row) {
	// il n'existe pas, creer les identifiants 
		$row['email'] = $mail_inscription;
		$row['login'] = test_login($nom_inscription, $mail_inscription);
		$row['id_auteur'] = spip_abstract_insert('spip_auteurs', 
							 '(nom, email, login, statut)',
							 "('".
							 addslashes($nom_inscription) .
							 "', '".
							 $mail .
							 "', '" .
							 $row['login'] .
							 "', 'nouveau')");
		$row['pass'] = creer_pass_pour_auteur($row['id_auteur']);
		return $row;
	} else {
		// existant mais encore muet, ou ressucite: renvoyer les infos
		if ((($row['statut'] == 'nouveau') && !$force) ||
			(($row['statut'] == '5poubelle') && $force)) {
			// recreer le pass
			$row['pass'] = creer_pass_pour_auteur($row['id_auteur']);
			return $row;
		} else {
			// irrecuperable
			if ($row['statut'] == '5poubelle')
				return _T('form_forum_access_refuse');
			else
				// deja inscrit
				return _T('form_forum_email_deja_enregistre');
		}
	}
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

	include_ecrire("inc_mail");
	if (envoyer_mail($ids['email'],
			 "[$nom_site_spip] "._T('form_forum_identifiants'),
			 $message))
		return _T('form_forum_identifiant_mail');
	else
		return _T('form_forum_probleme_mail');
}

function test_login($nom, $mail) {
	include_ecrire('inc_charsets');
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
	  if (!spip_num_rows(spip_query("SELECT id_auteur FROM spip_auteurs WHERE login='$login' LIMIT 1")))
			return $login;
		$login = $login_base.$i;
	}
}

function creer_pass_pour_auteur($id_auteur) {
	include_ecrire("inc_acces");
	$pass = creer_pass_aleatoire(8, $id_auteur);
	$mdpass = md5($pass);
	$htpass = generer_htpass($pass);
	spip_query("UPDATE spip_auteurs
		SET pass='$mdpass', htpass='$htpass'
		WHERE id_auteur = ".intval($id_auteur));
	ecrire_acces();
	
	return $pass;
}

?>
