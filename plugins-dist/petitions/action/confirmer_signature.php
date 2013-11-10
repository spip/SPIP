<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;



/**
 * Confirmer une signature
 * Retour a l'ecran du lien de confirmation d'une signature de petition.
 * var_confirm contient le hash de la signature.
 * Au premier appel on traite et on publie
 * Au second appel on retourne le resultat a afficher
 *
 * @staticvar string $confirm
 * @param <type> $var_confirm
 * @return string 
 */
function action_confirmer_signature_dist($var_confirm=null) {
	static $confirm = null;

	// reponse mise en cache dans la session ?
	$code_message = 'signature_message_'.strval($var_confirm);
	if (isset($GLOBALS['visiteur_session'][$code_message]))
		return $GLOBALS['visiteur_session'][$code_message];

	// reponse deja calculee depuis public/assembler.php
	if (isset($confirm))
		return $confirm;

	if (is_null($var_confirm)) {
		$securiser_action = charger_fonction('securiser_action','inc');
		$var_confirm = $securiser_action();
	}

	if (!$var_confirm OR $var_confirm == 'publie' OR $var_confirm == 'poubelle')
		return '';

	if (!spip_connect()) {
		$confirm = _T('petitions:form_pet_probleme_technique');
		return '';
	}
	include_spip('inc/texte');
	include_spip('inc/filtres');

	// Suppression d'une signature par un moderateur ?
	// Cf. plugin notifications
	if (isset($_GET['refus'])) {
		// verifier validite de la cle de suppression
		// l'id_signature est dans var_confirm
		include_spip('inc/securiser_action');
		if ($id_signature = intval($var_confirm)
		    AND (
			$_GET['refus'] == _action_auteur("supprimer signature $id_signature", '', '', 'alea_ephemere')
				OR
			$_GET['refus'] == _action_auteur("supprimer signature $id_signature", '', '', 'alea_ephemere_ancien')
			)) {
			include_spip('action/editer_signature');
			signature_modifier($id_signature,array("statut" => 'poubelle'));
			$confirm = _T('petitions:info_signature_supprimee');
		} else $confirm = _T('petitions:info_signature_supprimee_erreur');
		return '';
	}

	$row = Sql::fetsel('*', 'spip_signatures', "statut=" . Sql::quote($var_confirm), '', "1");

	if (!$row) {
		$confirm = _T('petitions:form_pet_aucune_signature');
		return '';
	}

	$id_signature = $row['id_signature'];
	$id_petition = $row['id_petition'];
	$adresse_email = $row['ad_email'];
	$url_site = $row['url_site'];

	$row = Sql::fetsel('email_unique, site_unique, id_article', 'spip_petitions', "id_petition=".intval($id_petition));

	$email_unique = $row['email_unique']  == "oui";
	$site_unique = $row['site_unique']  == "oui";
	$id_article = $row['id_article'];

	include_spip('action/editer_signature');
	signature_modifier($id_signature,array('statut' => 'publie'));

	if ($email_unique) {
		$r = "id_petition=".intval($id_petition)." AND ad_email=" . Sql::quote($adresse_email);
		if (signature_entrop($r))
			  $confirm =  _T('petitions:form_pet_deja_signe');
	}

	if ($site_unique) {
		$r = "id_petition=".intval($id_petition)." AND url_site=" . Sql::quote($url_site);
		if (signature_entrop($r))
			$confirm = _T('petitions:form_pet_site_deja_enregistre');
	}

	include_spip('inc/session');

	if (!$confirm) {
		$confirm = _T('petitions:form_pet_signature_validee');

		// noter dans la session que l'email est valide
		// de facon a permettre de signer les prochaines
		// petitions sans refaire un tour d'email
		session_set('email_confirme', $adresse_email);

		// invalider les pages ayant des boucles signatures
		include_spip('inc/invalideur');
		suivre_invalideur("id='signature/$id_signature'");
		suivre_invalideur("id='article/$id_article'");
	}

	// Conserver la reponse dans la session du visiteur
	if ($confirm)
		session_set($code_message, $confirm);
}

?>