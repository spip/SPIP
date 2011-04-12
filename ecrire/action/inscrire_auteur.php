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


/**
 * Inscrire un nouvel auteur sur la base de son nom et son email
 * L'email est utilise pour reperer si il existe deja ou non
 * => identifiant par defaut
 *
 * @param string $mode
 * @param string $mail_complet
 * @param string $nom
 * @return array|string
 */
function action_inscrire_auteur_dist($statut, $mail_complet, $nom, $login=''){
	
	if (function_exists('test_inscription'))
		$f = 'test_inscription';
	else 	$f = 'test_inscription_dist';
	$desc = $f($statut, $mail_complet, $nom, $login);

	// erreur ?
	if (!is_array($desc))
		return _T($desc);

	include_spip('base/abstract_sql');
	$res = sql_select("statut, id_auteur, login, email", "spip_auteurs", "email=" . sql_quote($desc['email']));
	// erreur ?
	if (!$res)
		return _T('titre_probleme_technique');

	$row = sql_fetch($res);
	// s'il n'existe pas deja, creer les identifiants
	$desc = $row ? $row : inscription_nouveau($desc);

	// erreur ?
	if (!is_array($desc))
		return $desc;


	// generer le mot de passe (ou le refaire si compte inutilise)
	$desc['pass'] = creer_pass_pour_auteur($desc['id_auteur']);


	// charger de suite cette fonction, pour ses utilitaires
	$envoyer_inscription = charger_fonction("envoyer_inscription","");
	list($sujet,$msg,$from,$head) = $envoyer_inscription($desc, $nom, $statut);

	$notifications = charger_fonction('notifications', 'inc');
	notifications_envoyer_mails($mail_complet, $msg, $sujet, $from, $head);

	// Notifications
	$notifications('inscription', $desc['id_auteur'],
		array('nom' => $desc['nom'], 'email' => $desc['email'])
	);

	return $desc;
}


/**
 * fonction qu'on peut redefinir pour filtrer les adresses mail et les noms,
 * et donner des infos supplementaires
 * Std: controler que le nom (qui sert a calculer le login) est plausible
 * et que l'adresse est valide. On les normalise au passage (trim etc).
 * Retour:
 * - si ok un tableau avec au minimum email, nom, mode (redac / forum)
 * - si ko une chaine de langue servant d'argument a  _T expliquant le refus
 *
 * http://doc.spip.org/@test_inscription_dist
 *
 * @param string $statut
 * @param string $mail
 * @param string $nom
 * @return array|string
 */
function test_inscription_dist($statut, $mail, $nom, $login='') {
	include_spip('inc/filtres');
	$nom = trim(corriger_caracteres($nom));
	if((strlen ($nom) < _LOGIN_TROP_COURT) OR (strlen($nom) > 64))
	    return 'ecrire:info_login_trop_court';
	if (!$r = email_valide($mail)) return 'info_email_invalide';
	return array('email' => $r, 'nom' => $nom, 'bio' => $statut, 'login'=>$login);
}


/**
 * On enregistre le demandeur comme 'nouveau', en memorisant le statut final
 * provisoirement dans le champ Bio, afin de ne pas visualiser les inactifs
 * A sa premiere connexion il obtiendra son statut final.
 *
 * http://doc.spip.org/@inscription_nouveau
 *
 * @param array $desc
 * @return mixed|string
 */
function inscription_nouveau($desc)
{
	if (!isset($desc['login']) OR !strlen($desc['login']))
		$desc['login'] = test_login($desc['nom'], $desc['email']);

	$desc['statut'] = 'nouveau';
	include_spip('action/editer_auteur');
	$id_auteur = insert_auteur();

	if (!$id_auteur) return _T('titre_probleme_technique');

	include_spip('action/editer_auteur');
	auteurs_set($id_auteur, $desc);

	instituer_auteur($id_auteur, $desc);

	$desc['id_auteur'] = $id_auteur;

	return $desc;
}


/**
 * http://doc.spip.org/@test_login
 *
 * @param string $nom
 * @param string $mail
 * @return string
 */
function test_login($nom, $mail) {
	include_spip('inc/charsets');
	$nom = strtolower(translitteration($nom));
	$login_base = preg_replace("/[^\w\d_]/", "_", $nom);

	// il faut eviter que le login soit vraiment trop court
	if (strlen($login_base) < 3) {
		$mail = strtolower(translitteration(preg_replace('/@.*/', '', $mail)));
		$login_base = preg_replace("/[^\w\d]/", "_", $mail);
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


/**
 * construction du mail envoyant les identifiants
 * fonction redefinissable qui doit retourner un tableau
 * dont les elements seront les arguments de inc_envoyer_mail
 *
 * http://doc.spip.org/@envoyer_inscription_dist
 *
 * @param array $desc
 * @param string $nom
 * @param strong $mode
 * @param int $id
 * @return array
 */
function envoyer_inscription_dist($desc, $nom, $mode) {

	$contexte = $desc;
	$contexte['nom'] = $nom;
	$contexte['mode'] = $mode;

	$message = recuperer_fond('modeles/mail_inscription',$contexte);
	return array("", $message);
}


/**
 * Creer un mot de passe initial aleatoire
 * 
 * http://doc.spip.org/@creer_pass_pour_auteur
 *
 * @param int $id_auteur
 * @return string
 */
function creer_pass_pour_auteur($id_auteur) {
	include_spip('inc/acces');
	$pass = creer_pass_aleatoire(8, $id_auteur);
	include_spip('action/editer_auteur');
	instituer_auteur($id_auteur, array('pass'=>$pass));
	return $pass;
}