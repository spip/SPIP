<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

if (!defined('_LOGIN_TROP_COURT')) define('_LOGIN_TROP_COURT', 4);

function formulaires_inscription_charger_dist($mode, $focus, $id=0) {
	$valeurs = array('nom_inscription'=>'','mail_inscription'=>'');
	if ($mode=='1comite')
		$valeurs['_commentaire'] = _T('pass_espace_prive_bla');
	else 
		$valeurs['_commentaire'] = _T('pass_forum_bla');

	if (!tester_config($id, $mode))
		return array(false,$valeurs);

	return $valeurs;
}

// Si inscriptions pas autorisees, retourner une chaine d'avertissement
function formulaires_inscription_verifier_dist($mode, $focus, $id=0) {
	$erreurs = array();
	include_spip('inc/filtres');	
	if (!tester_config($id, $mode))
		$erreurs['message_erreur'] = _T('rien_a_faire_ici');

	if (!$nom = _request('nom_inscription'))
		$erreurs['nom_inscription'] = _T("info_obligatoire");
	else {
		$nom = trim(corriger_caracteres($nom));
		if((strlen ($nom) < _LOGIN_TROP_COURT) OR (strlen($nom) > 64) OR (strlen(_request('nobot'))>0))
			$erreurs['nom_inscription'] = _T('info_login_trop_court');
	}
	
	if (!$mail = _request('mail_inscription'))
		$erreurs['mail_inscription'] = _T("info_obligatoire");
	elseif(!email_valide($mail)){
		$erreurs['mail_inscription'] = _T("form_prop_indiquer_email");
	}
	
	// compatibilite avec anciennes fonction surchargeables
	// plus de definition par defaut
	if (!count($erreurs)){
		if (function_exists('test_inscription'))
			$f = 'test_inscription';
		else 
			$f = 'test_inscription_dist';
		$declaration = $f($mode, $mail, $nom, $id);
		if (is_string($declaration))
			$erreurs['mail_inscription'] = $declaration;
		else {
			include_spip('base/abstract_sql');
			
			if ($row = sql_fetsel("statut, id_auteur, login, email", "spip_auteurs", "email=" . sql_quote($declaration['email']))){
				if (($row['statut'] == '5poubelle') AND !$declaration['pass'])
					// irrecuperable
					$erreurs['message_erreur'] = _T('form_forum_access_refuse');	
				if (($row['statut'] != 'nouveau') AND !$declaration['pass'])
					// deja inscrit
					$erreurs['message_erreur'] = _T('form_forum_email_deja_enregistre');
			}
		}
	}
	return $erreurs;
}

function formulaires_inscription_traiter_dist($mode, $focus, $id=0) {
	$message = _T('form_forum_identifiant_mail');

	$nom = _request('nom_inscription');
	$mail = _request('mail_inscription');
	
	if ($mail) {
		$erreur = message_inscription($mail, $nom, $mode, $id);
		if (is_array($erreur)) {
			if (function_exists('envoyer_inscription'))
				$f = 'envoyer_inscription';
			else 
				$f = 'envoyer_inscription_dist';
			$erreur = $f($erreur, $nom, $mode, $id);
		}
	}

	if ($erreur)
		$message= $erreur;
		
	return $message;
}


// fonction qu'on peut redefinir pour filtrer les adresses mail et les noms,
// et donner des infos supplementaires
// Std: controler que le nom (qui sert a calculer le login) est plausible
// et que l'adresse est valide (et on la normalise)
// Retour: une chaine message d'erreur 
// ou un tableau avec au minimum email, nom, mode (redac / forum)

// http://doc.spip.org/@test_inscription_dist
function test_inscription_dist($mode, $mail, $nom, $id=0) {

	include_spip('inc/filtres');
	$nom = trim(corriger_caracteres($nom));
	if (!$nom || strlen($nom) > 64)
	    return _T('ecrire:info_login_trop_court');
	if (!$r = email_valide($mail)) return _T('info_email_invalide');
	return array('email' => $r, 'nom' => $nom, 'bio' => $mode);
}

// cree un nouvel utilisateur et renvoie un message d'impossibilite 
// ou le tableau representant la ligne SQL le decrivant.
// $mode = 'forum' ou 'redac' selon ce a quoi on s'inscrit
// $id = une id_rubrique eventuelle (?)
// http://doc.spip.org/@message_inscription
function message_inscription($mail, $nom, $mode, $id=0) {

	if (function_exists('test_inscription'))
		$f = 'test_inscription';
	else 
		$f = 'test_inscription_dist';
	$declaration = $f($mode, $mail, $nom, $id);

	if (is_string($declaration))
		return $declaration;
	
	include_spip('base/abstract_sql');
	$row = sql_fetsel("statut, id_auteur, login, email", "spip_auteurs", "email=" . sql_quote($declaration['email']));

	if (!$row)
		// il n'existe pas, creer les identifiants  
		return inscription_nouveau($declaration);
		
	// les cas "auteur existant" ont ete testes dans verifier()

	// existant mais encore muet, ou ressucite: renvoyer les infos
	$row['pass'] = creer_pass_pour_auteur($row['id_auteur']);
	return $row;
}

// On enregistre le demandeur comme 'nouveau', en memorisant le statut final
// provisoirement dans le champ Bio, afin de ne pas visualiser les inactifs
// A sa premiere connexion il obtiendra son statut final (auth->activer())

// http://doc.spip.org/@inscription_nouveau
function inscription_nouveau($declaration)
{
	if (!isset($declaration['login']))
		$declaration['login'] = test_login($declaration['nom'], $declaration['email']);

	$declaration['statut'] = 'nouveau';

	$n = sql_insertq('spip_auteurs', $declaration);

	$declaration['id_auteur'] = $n;

	$declaration['pass'] = creer_pass_pour_auteur($declaration['id_auteur']);
	return $declaration;
}

// envoyer identifiants par mail
// fonction redefinissable qui doit retourner false si tout est ok
// ou une chaine non vide expliquant pourquoi le mail n'a pas ete envoye

// http://doc.spip.org/@envoyer_inscription_dist
function envoyer_inscription_dist($ids, $nom, $mode, $id) {

	$envoyer_mail = charger_fonction('envoyer_mail','inc');
	$nom_site_spip = nettoyer_titre_email($GLOBALS['meta']["nom_site"]);
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


	if ($envoyer_mail($ids['email'],
			 "[$nom_site_spip] "._T('form_forum_identifiants'),
			 $message))
		return false;
	else
		return _T('form_forum_probleme_mail');
}

// http://doc.spip.org/@test_login
function test_login($nom, $mail) {
	include_spip('inc/charsets');
	$nom = strtolower(translitteration($nom));
	$login_base = preg_replace("/[^\w\d_]/", "_", $nom);

	// il faut eviter que le login soit vraiment trop court
	if (strlen($login_base) < 3) {
		$mail = strtolower(translitteration(preg_replace('/@.*/', '', $mail)));
		$login_base = preg_replace("/[^\w\d]/", "_", $nom);
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
		if (!sql_countsel('spip_auteurs', "login='$login'"))
			return $login;
		$login = $login_base.$i;
	}
}

// http://doc.spip.org/@creer_pass_pour_auteur
function creer_pass_pour_auteur($id_auteur) {
	include_spip('inc/acces');
	$pass = creer_pass_aleatoire(8, $id_auteur);
	$mdpass = md5($pass);
	$htpass = generer_htpass($pass);
	sql_updateq('spip_auteurs', array('pass'=>$mdpass, 'htpass'=>$htpass),"id_auteur = ".intval($id_auteur));
	ecrire_acces();
	
	return $pass;
}

?>