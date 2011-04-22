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
 * @param array $options
 *   login : login precalcule
 *   id : id_rubrique fournit en second arg de #FORMULAIRE_INSCRIPTION
 * @return array|string
 */
function action_inscrire_auteur_dist($statut, $mail_complet, $nom, $options = array()){
	if (!is_array($options))
		$options = array('id'=>$options);

	if (function_exists('test_inscription'))
		$f = 'test_inscription';
	else 	$f = 'test_inscription_dist';
	$desc = $f($statut, $mail_complet, $nom, $options);

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

	// attribuer un jeton pour confirmation par clic sur un lien
	$desc['jeton'] = auteur_attribuer_jeton($desc['id_auteur']);

	// charger de suite cette fonction, pour ses utilitaires
	$envoyer_inscription = charger_fonction("envoyer_inscription","");
	list($sujet,$msg,$from,$head) = $envoyer_inscription($desc, $nom, $statut, $options);

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
 * @param string $options
 * @return array|string
 */
function test_inscription_dist($statut, $mail, $nom, $options) {
	include_spip('inc/filtres');
	$nom = trim(corriger_caracteres($nom));
	if((strlen ($nom) < _LOGIN_TROP_COURT) OR (strlen($nom) > 64))
	    return 'ecrire:info_login_trop_court';
	if (!$r = email_valide($mail)) return 'info_email_invalide';
	$res = array('email' => $r, 'nom' => $nom, 'prefs' => $statut);
	if (isset($options['login']))
		$res['login'] = $options['login'];
	return $res;
}


/**
 * On enregistre le demandeur comme 'nouveau', en memorisant le statut final
 * provisoirement dans le champ prefs, afin de ne pas visualiser les inactifs
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
 * @param string $mode
 * @param array $options
 * @return array
 */
function envoyer_inscription_dist($desc, $nom, $mode, $options=array()) {

	$contexte = array_merge($desc,$options);
	$contexte['nom'] = $nom;
	$contexte['mode'] = $mode;
	$contexte['url_confirm'] = generer_url_action('confirmer_inscription','',true,true);
	$contexte['url_confirm'] = parametre_url($contexte['url_confirm'],'email',$desc['email']);
	$contexte['url_confirm'] = parametre_url($contexte['url_confirm'],'jeton',$desc['jeton']);

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

/**
 * Un filtre pour determiner le nom du mode des librement inscrits,
 * a l'aide de la liste globale des statuts (tableau mode => nom du mode)
 * Utile pour le formulaire d'inscription.
 * Si un mode est fourni, verifier que la configuration l'accepte.
 * Si mode inconnu laisser faire, c'est une extension non std
 * mais verifier que la syntaxe est compatible avec SQL
 *
 * http://doc.spip.org/@tester_config
 *
 * @param string $statut_tmp
 * @return string
 */
function tester_statut_inscription($statut_tmp){
	$s = array_search($statut_tmp, $GLOBALS['liste_des_statuts']);
	switch ($s) {

	case 'info_redacteurs' :
	  return (($GLOBALS['meta']['accepter_inscriptions'] == 'oui') ? $statut_tmp : '');

	case 'info_visiteurs' :
	  return (($GLOBALS['meta']['accepter_visiteurs'] == 'oui' OR $GLOBALS['meta']['forums_publics'] == 'abo') ? $statut_tmp : '');

	default:
	  if ($statut_tmp AND $statut_tmp == addslashes($statut_tmp))
	    return $statut_tmp;
	  if ($GLOBALS['meta']["accepter_inscriptions"] == "oui")
	    return $GLOBALS['liste_des_statuts']['info_redacteurs'];
	  if ($GLOBALS['meta']["accepter_visiteurs"] == "oui")
	    return $GLOBALS['liste_des_statuts']['info_visiteurs'];
	  return '';
	}
}


/**
 * Un nouvel inscrit prend son statut definitif a la 1ere connexion.
 * Le statut a ete memorise dans prefs (cf test_inscription_dist).
 * On le verifie, car la config a peut-etre change depuis,
 * et pour compatibilite avec les anciennes versions qui n'utilisaient pas "prefs".
 *
 * http://doc.spip.org/@acces_statut
 *
 * @param array $auteur
 * @return array
 */
function confirmer_statut_inscription($auteur){
	// securite
	if ($auteur['statut'] != 'nouveau') return $auteur;

	if (!($s = tester_statut_inscription('', $auteur['prefs'])))
		return $auteur;

	include_spip('action/editer_auteur');
	// changer le statut
	auteur_modifier($auteur['id_auteur'],array('statut'=> $s));
	unset($_COOKIE['spip_session']); // forcer la maj de la session

	return $auteur;
}


/**
 * Attribuer un jeton temporaire pour un auteur
 * en assurant l'unicite du jeton
 * @param int $id_auteur
 * @return string
 */
function auteur_attribuer_jeton($id_auteur){
	include_spip('inc/acces');
	// s'assurer de l'unicite du jeton pour le couple (email,cookie)
	do {
		$jeton = creer_uniqid();
		sql_updateq("spip_auteurs", array("cookie_oubli" => $jeton), "id_auteur=" . intval($id_auteur));
	}
	while (sql_countsel("spip_auteurs","cookie_oubli=".sql_quote($jeton))>1);
	return $jeton;
}

/**
 * Retrouver l'auteur par son jeton et son email, uniques par construction
 * @param string $email
 * @param string $jeton
 * @return array|bool
 */
function auteur_verifier_jeton($jeton){
	// refuser un jeton corrompu
	if (preg_match(',[^0-9a-f.],i',$jeton))
		return false;

	$desc = sql_fetsel('*','spip_auteurs',"cookie_oubli=".sql_quote($jeton));
	return $desc;
}

/**
 * Effacer le jeton d'un auteur apres utilisation
 *
 * @param int $id_auteur
 * @return bool
 */
function auteur_effacer_jeton($id_auteur){
	return sql_updateq("spip_auteurs", array("cookie_oubli" => ''), "id_auteur=" . intval($id_auteur));
}