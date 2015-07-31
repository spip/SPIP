<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


include ("ecrire/inc_version.php3");
include_ecrire("inc_session.php3"); # pour creer_uniq_id
include_ecrire("inc_mail.php3"); # pour envoyer_mail
include_ecrire("inc_acces.php3"); # pour generer_htpass
include_local("inc-public-global.php3"); # pour calculer la page
include_ecrire("inc_lang.php3");
include_ecrire("inc_filtres.php3"); # pour email_valide()
include_ecrire('inc_headers.php');

utiliser_langue_site();
utiliser_langue_visiteur();

// Ce fichier est celui d'une balise dynamique qui s'ignore.

function formulaire_oubli_dyn($p, $oubli)
{

$message = '';

// au 3e appel la variable P est positionnee par le script lui-meme
// et oubli = mot passe. Le choix du nom P est impose par pass_mail_passcookie
if ($p = addslashes($p)) {
	$res = spip_query("SELECT * FROM spip_auteurs WHERE cookie_oubli='$p' AND statut<>'5poubelle' AND pass<>''");
	if (!$row = spip_fetch_array($res)) 
		$message = _T('pass_erreur_code_inconnu');
	else {
		if ($oubli) {
			$mdpass = md5($oubli);
			$htpass = generer_htpass($oubli);
			spip_query("UPDATE spip_auteurs SET htpass='$htpass', pass='$mdpass', alea_actuel='',
				cookie_oubli='' WHERE cookie_oubli='$p'");

			$login = $row['login'];
			$message = "<b>" . _T('pass_nouveau_enregistre') . "</b>".
			"<p>" . _T('pass_rappel_login', array('login' => $login));
		}
	}
 } else { 
  // si p absent, oubli vaut alors le mail au 2e appel, vide au 1e
  if ($oubli) {
	if ( email_valide($oubli) ) {
		$email = addslashes($oubli);
		$res = spip_query("SELECT * FROM spip_auteurs WHERE email ='$email'");
		if ($row = spip_fetch_array($res)) {
			if ($row['statut'] == '5poubelle' OR $row['pass'] == '')
				$message = _T('pass_erreur_acces_refuse');
			else {
				$cookie = creer_uniqid();
				spip_query("UPDATE spip_auteurs SET cookie_oubli = '$cookie' WHERE email ='$email'");

				if ( envoyer_mail($email,
						 "[" . $GLOBALS['meta']["nom_site"] .'] ' .  _T('pass_oubli_mot'),
						 _T('pass_mail_passcookie',
						    array('nom_site_spip' => $GLOBALS['meta']["nom_site"],
							  'adresse_site' => $GLOBALS['meta']["adresse_site"], 
							  'cookie' => $cookie))))
					$message = _T('pass_recevoir_mail');
				else
					$message = _T('pass_erreur_probleme_technique');
			}
		}
		else
			$message = _T('pass_erreur_non_enregistre', array('email_oubli' => htmlspecialchars($oubli)));
	} else {
		$message = _T('pass_erreur_non_valide', array('email_oubli' => htmlspecialchars($oubli)));
	}
  }
 }
 return array('formulaire_oubli', 0, array('p' => $p, 'message' => $message));
}

http_no_cache();
echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="', 
  $GLOBALS['spip_lang'],
  '" dir="',
  ($GLOBALS['spip_lang_rtl'] ? 'rtl' : 'ltr'),
  '">
<head><title>',
  _T('pass_mot_oublie'),
  '</title>
<link rel="stylesheet" type="text/css" href="spip_style.css" />
</head><body>';
inclure_balise_dynamique(formulaire_oubli_dyn($p, $oubli));
echo "</body></html>";
?>
