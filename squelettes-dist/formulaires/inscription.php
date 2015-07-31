<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2012                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

function formulaires_inscription_charger_dist($mode='', $id=0) {
	global $visiteur_session;
	
	// fournir le mode de la config ou tester si l'argument du formulaire est un mode accepte par celle-ci 
	include_spip('inc/filtres');
	$mode=tester_config($id, $mode);
	
	// pas de formulaire si le mode est interdit
	if (!$mode)
		return false;

	// pas de formulaire si on a déjà une session avec un statut égal ou meilleur au mode
	if(isset($visiteur_session['statut']) && ($visiteur_session['statut'] <= $mode))
		return false;
	
	$valeurs = array('nom_inscription'=>'','mail_inscription'=>'', 'id'=>$id, '_mode'=>$mode);
	
	return $valeurs;
}

// Si inscriptions pas autorisees, retourner une chaine d'avertissement
function formulaires_inscription_verifier_dist($mode='', $id=0) {
	
	include_spip('inc/filtres');
	$mode=tester_config($id, $mode);
	
	$erreurs = array();

	if (!$mode OR (strlen(_request('nobot'))>0))
		$erreurs['message_erreur'] = _T('pass_rien_a_faire_ici');

	if (!$nom = _request('nom_inscription'))
		$erreurs['nom_inscription'] = _T("info_obligatoire");
	if (!$mail = strval(_request('mail_inscription')))
		$erreurs['mail_inscription'] = _T("info_obligatoire");
	
	// compatibilite avec anciennes fonction surchargeables
	// plus de definition par defaut
	if (!count($erreurs)){
		include_spip('action/inscrire_auteur');
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
				else if (($row['statut'] != 'nouveau') AND !$declaration['pass'])
					// deja inscrit
					$erreurs['message_erreur'] = _T('form_forum_email_deja_enregistre');
				spip_log($row['id_auteur'] . " veut se resinscrire");
			}
		}
	}
	return $erreurs;
}

function formulaires_inscription_traiter_dist($mode='', $id=0) {
	
	include_spip('inc/filtres');
	$mode=tester_config($id, $mode);
		
	$nom = _request('nom_inscription');
	$mail_complet = _request('mail_inscription');

	$inscrire_auteur = charger_fonction('inscrire_auteur','action');
	$desc = $inscrire_auteur($mode, $mail_complet, $nom, array('id'=>$id));

	return array('message_ok'=>is_string($desc) ? $desc : _T('form_forum_identifiant_mail'));
}


?>
