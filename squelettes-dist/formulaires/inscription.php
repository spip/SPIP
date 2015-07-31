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

function formulaires_inscription_charger_dist($mode, $focus, $id=0) {
	$valeurs = array('nom_inscription'=>'','mail_inscription'=>'', 'id'=>$id);
	if ($mode=='1comite')
		$valeurs['_commentaire'] = _T('pass_espace_prive_bla');
	else 
		$valeurs['_commentaire'] = _T('pass_forum_bla');

	if (!tester_config($id, $mode))
		$valeurs['editable'] = false;

	return $valeurs;
}

// Si inscriptions pas autorisees, retourner une chaine d'avertissement
function formulaires_inscription_verifier_dist($mode, $focus, $id=0) {

	$erreurs = array();
	include_spip('inc/filtres');	
	if (!tester_config($id, $mode) OR (strlen(_request('nobot'))>0))
		$erreurs['message_erreur'] = _T('rien_a_faire_ici');

	if (!$nom = _request('nom_inscription'))
		$erreurs['nom_inscription'] = _T("info_obligatoire");
	if (!$mail = _request('mail_inscription'))
		$erreurs['mail_inscription'] = _T("info_obligatoire");
	
	// compatibilite avec anciennes fonction surchargeables
	// plus de definition par defaut
	if (!count($erreurs)){
		if (function_exists('test_inscription'))
			$f = 'test_inscription';
		else 
			$f = 'test_inscription_dist';
		$declaration = $f($mode, $mail, $nom, $id);
		if (is_string($declaration)) {
			$k = (strpos($declaration, 'mail')  !== false) ?
			  'mail_inscription' : 'nom_inscription';
			$erreurs[$k] = _T($declaration);
		} else {
			include_spip('base/abstract_sql');
			
			if ($row = sql_fetsel("statut, id_auteur, login, email", "spip_auteurs", "email=" . sql_quote($declaration['email']))){
				if (($row['statut'] == '5poubelle') AND !$declaration['pass'])
					// irrecuperable
					$erreurs['message_erreur'] = _T('form_forum_access_refuse');	
				elseif (($row['statut'] != 'nouveau') AND !$declaration['pass'])
					// deja inscrit
					$erreurs['message_erreur'] = _T('form_forum_email_deja_enregistre');
				spip_log($row['id_auteur'] . " veut se resinscrire");
			}
		}
	}
	return $erreurs;
}

function formulaires_inscription_traiter_dist($mode, $focus, $id=0) {

	$nom = _request('nom_inscription');
	$mail_complet = _request('mail_inscription');
	
	if (function_exists('test_inscription'))
		$f = 'test_inscription';
	else 	$f = 'test_inscription_dist';
	$desc = $f($mode, $mail_complet, $nom, $id);

	if (!is_array($desc)) {
		$desc = _T($desc);
	} else {
		include_spip('base/abstract_sql');
		$res = sql_select("statut, id_auteur, login, email", "spip_auteurs", "email=" . sql_quote($desc['email']));
		if (!$res) 
			$desc = _T('titre_probleme_technique');
		else {
			$row = sql_fetch($res);
			// s'il n'existe pas deja, creer les identifiants  
			$desc = $row ? $row : inscription_nouveau($desc);
		}
	}

	if (is_array($desc)) {
	// generer le mot de passe (ou le refaire si compte inutilise)
		$desc['pass'] = creer_pass_pour_auteur($desc['id_auteur']);
		// charger de suite cette fonction, pour ses utilitaires
		$envoyer_mail = charger_fonction('envoyer_mail','inc');
		if (function_exists('envoyer_inscription'))
			$f = 'envoyer_inscription';
		else 	$f = 'envoyer_inscription_dist';
		list($sujet,$msg,$from,$head) = $f($desc, $nom, $mode, $id);
		if (!$envoyer_mail($mail_complet, $sujet, $msg, $from, $head))
			$desc = _T('form_forum_probleme_mail');
		// Notifications
		if ($notifications = charger_fonction('notifications', 'inc')) {
			$notifications('inscription', $desc['id_auteur'],
				array('nom' => $desc['nom'], 'email' => $desc['email'])
			);
		}
	}

	return array('message_ok'=>is_string($desc) ? $desc : _T('form_forum_identifiant_mail'));
}

// fonction qu'on peut redefinir pour filtrer les adresses mail et les noms,
// et donner des infos supplementaires
// Std: controler que le nom (qui sert a calculer le login) est plausible
// et que l'adresse est valide. On les normalise au passage (trim etc).
// Retour: 
// - si ok un tableau avec au minimum email, nom, mode (redac / forum)
// - si ko une chaine de langue servant d'argument a  _T expliquant le refus

// http://doc.spip.org/@test_inscription_dist
function test_inscription_dist($mode, $mail, $nom, $id=0) {

	include_spip('inc/filtres');
	$nom = trim(corriger_caracteres($nom));
	if((strlen ($nom) < _LOGIN_TROP_COURT) OR (strlen($nom) > 64))
	    return 'ecrire:info_login_trop_court';
	if (!$r = email_valide($mail)) return 'info_email_invalide';
	return array('email' => $r, 'nom' => $nom, 'bio' => $mode);
}

// On enregistre le demandeur comme 'nouveau', en memorisant le statut final
// provisoirement dans le champ Bio, afin de ne pas visualiser les inactifs
// A sa premiere connexion il obtiendra son statut final.

// http://doc.spip.org/@inscription_nouveau
function inscription_nouveau($desc)
{
	if (!isset($desc['login']))
		$desc['login'] = test_login($desc['nom'], $desc['email']);

	$desc['statut'] = 'nouveau';

	$n = sql_insertq('spip_auteurs', $desc);

	if (!$n) return _T('titre_probleme_technique');

	$desc['id_auteur'] = $n;

	return $desc;
}

// construction du mail envoyant les identifiants 
// fonction redefinissable qui doit retourner un tableau
// dont les elements seront les arguments de inc_envoyer_mail

// http://doc.spip.org/@envoyer_inscription_dist
function envoyer_inscription_dist($desc, $nom, $mode, $id) {

	$nom_site_spip = nettoyer_titre_email($GLOBALS['meta']["nom_site"]);
	$adresse_site = $GLOBALS['meta']["adresse_site"];
	if ($mode == '6forum') {
		$adresse_login = generer_url_public('login'); 
		$msg = 'form_forum_voici1';
	} else {
		$adresse_login = $adresse_site .'/'. _DIR_RESTREINT_ABS;
		$msg = 'form_forum_voici2';
	}

	$msg = _T('form_forum_message_auto')."\n\n"
		. _T('form_forum_bonjour', array('nom'=>$nom))."\n\n"
		. _T($msg, array('nom_site_spip' => $nom_site_spip,
			'adresse_site' => $adresse_site . '/',
			'adresse_login' => $adresse_login)) . "\n\n- "
		. _T('form_forum_login')." " . $desc['login'] . "\n- "
		. _T('form_forum_pass'). " " . $desc['pass'] . "\n\n";

	return array("[$nom_site_spip] "._T('form_forum_identifiants'), $msg);
}

// http://doc.spip.org/@test_login
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

// http://doc.spip.org/@creer_pass_pour_auteur
function creer_pass_pour_auteur($id_auteur) {
	include_spip('inc/acces');
	$pass = creer_pass_aleatoire(8, $id_auteur);
	include_spip('action/editer_auteur');
	instituer_auteur($id_auteur, array('pass'=>$pass));
	return $pass;
}

?>
