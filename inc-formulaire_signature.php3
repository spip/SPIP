<?php

// Formulaire de signature d'une petition
//

global $balise_FORMULAIRE_SIGNATURE_collecte;
$balise_FORMULAIRE_SIGNATURE_collecte = array('petition', 'nom_email', 'adresse_email', 'message', 'signature_nom_site', 'signature_url_site', 'url_page', 'val_confirm');

function balise_FORMULAIRE_SIGNATURE_stat($args, $filtres)
{
  return ($args[0] ? $args : '');
}

function balise_FORMULAIRE_SIGNATURE_dyn($id_article, $nom_email, $adresse_email, $message, $nom_site, $url_site, $url_page, $val_confirm) {

	include_local(_FILE_CONNECT);
	if ($val_confirm)
		return reponse_confirmation($id_article);
	else if ($nom_email AND $adresse_email)
		return  reponse_signature($id_article, $nom_email, $adresse_email, $message, $nom_site, $url_site, $url_page, $val_confirm);
	else {
		$row = spip_fetch_array(spip_query("SELECT * FROM spip_petitions WHERE id_article='$id_article'"));
		return !$row ? '': array('formulaire_signature', 0, $row);
	}
}


//
// Retour a l'ecran du lien de confirmation d'une signature de petition.
// Si val_confirm est non vide, c'est l'appel en debut de inc-public
// pour vider le cache au demarrage afin que la nouvelle signature apparaisse.
// Sinon, c'est l'execution du formulaire et on retourne le message 
// de confirmation ou d'erreur construit lors de l'appel par inc-public.

function reponse_confirmation($id_article, $val_confirm = '') {
	static $confirm = '';

	if (!$val_confirm) return $confirm;
	include_local(_FILE_CONNECT);
	if ($GLOBALS['db_ok']) {
		include_ecrire("inc_texte.php3");
		include_ecrire("inc_filtres.php3");

		// Eviter les doublons
		$lock = "petition $id_article $val_confirm";
		if (!spip_get_lock($lock, 5)) {
			$confirm= _T('form_pet_probleme_technique');
		}
		else {
			$query_sign = "SELECT * FROM spip_signatures WHERE statut='".addslashes($val_confirm)."'";
			$result_sign = spip_query($query_sign);
			if (spip_num_rows($result_sign) > 0) {
				while($row = spip_fetch_array($result_sign)) {
					$id_signature = $row['id_signature'];
					$id_article = $row['id_article'];
					$date_time = $row['date_time'];
					$nom_email = $row['nom_email'];
					$adresse_email = $row['ad_email'];
					$nom_site = $row['nom_site'];
					$url_site = $row['url_site'];
					$message = $row['message'];
					$statut = $row['statut'];
				}
	
				$query_petition = "SELECT * FROM spip_petitions WHERE id_article=$id_article";
				$result_petition = spip_query($query_petition);
	
				while ($row = spip_fetch_array($result_petition)) {
					$id_article = $row['id_article'];
					$email_unique = $row['email_unique'];
					$site_obli = $row['site_obli'];
					$site_unique = $row['site_unique'];
					$message_petition = $row['message'];
					$texte_petition = $row['texte'];
				}
	
				if ($email_unique == "oui") {
					$email = addslashes($adresse_email);
					$query = "SELECT * FROM spip_signatures WHERE id_article=$id_article AND ad_email='$email' AND statut='publie'";
					$result = spip_query($query);
					if (spip_num_rows($result) > 0) {
						$confirm= (_T('form_pet_deja_signe'));
						$refus = "oui";
					}
				}
	
				if ($site_unique == "oui") {
					$site = addslashes($url_site);
					$query = "SELECT * FROM spip_signatures WHERE id_article=$id_article AND url_site='$site' AND statut='publie'";
					$result = spip_query($query);
					if (spip_num_rows($result) > 0) {
						$confirm= (_T('form_pet_deja_enregistre'));
						$refus = "oui";
					}
				}
	
				if ($refus == "oui") {
					$confirm= (_T('form_deja_inscrit'));
				}
				else {
					$query = "UPDATE spip_signatures SET statut='publie' WHERE id_signature='$id_signature'";
					$result = spip_query($query);
					// invalider les pages ayant des boucles signatures
					include_ecrire('inc_invalideur.php3');
					include_ecrire('inc_meta.php3');
					suivre_invalideur("id='petition/petition'");
	
					$confirm= (_T('form_pet_signature_validee'));
				}
			}
			else {
				$confirm= (_T('form_pet_aucune_signature'));
			}
			spip_release_lock($lock);
		}
	}
	else {
		$confirm= _T('form_pet_probleme_technique');
	}
}

//
// Retour a l'ecran de la signature d'une petition
//

function reponse_signature($id_article, $nom_email, $adresse_email, $message, $nom_site, $url_site, $url_page, $val_confirm) {

	if ($GLOBALS['db_ok']) {
		include_ecrire("inc_texte.php3");
		include_ecrire("inc_filtres.php3");
		include_ecrire("inc_mail.php3");

		// Eviter les doublons
		$lock = "petition $id_article $adresse_email";
		if (!spip_get_lock($lock, 5)) {
			return _T('form_pet_probleme_technique');
		} else {
			$query_petition = "SELECT * FROM spip_petitions WHERE id_article=$id_article";
			$result_petition = spip_query($query_petition);
	
			while ($row = spip_fetch_array($result_petition)) {
				$id_article = $row['id_article'];
				$email_unique = $row['email_unique'];
				$site_obli = $row['site_obli'];
				$site_unique = $row['site_unique'];
				$message_petition = $row['message'];
				$texte_petition = $row['texte'];
			}
	
			if (strlen($nom_email) < 2) {
				return (_T('form_indiquer_nom'));
			}
	
			if ($adresse_email == _T('info_mail_fournisseur')) {
				return (_T('form_indiquer_email'));
			}
	
			if ($email_unique == "oui") {
				$email = addslashes($adresse_email);
				$query = "SELECT * FROM spip_signatures WHERE id_article=$id_article AND ad_email='$email' AND statut='publie'";
				$result = spip_query($query);
				if (spip_num_rows($result) > 0) {
					return (_T('form_pet_deja_signe'));
				}
			}
	
			if (!email_valide($adresse_email)) {
				return (_T('form_email_non_valide'));
			}
	
			if ($site_obli == "oui") {
				if (!$nom_site) {
					return (_T('form_indiquer_nom_site'));
				}
				include_ecrire("inc_sites.php3");
	
				if (!recuperer_page($url_site)) {
					return (_T('form_pet_url_invalide'));
				}
			}
			if ($site_unique == "oui") {
				$site = addslashes($url_site);
				$query = "SELECT * FROM spip_signatures WHERE id_article=$id_article AND url_site='$site' AND (statut='publie' OR statut='poubelle')";
				$result = spip_query($query);
				if (spip_num_rows($result) > 0) {
					return (_T('form_pet_site_deja_enregistre'));
				}
			}
	
			$passw = test_pass();
	
			if ($refus == "oui") {
				return _T('form_pet_signature_pasprise');
			}
			else {
				$query_site = "SELECT titre FROM spip_articles WHERE id_article=$id_article";
				$result_site = spip_query($query_site);
				while ($row = spip_fetch_array($result_site)) {
					$titre = $row['titre'];
				}

				$link = new Link($url_page);
				$link->addVar('val_confirm', $passw);
				$url = $link->getUrl("sp$id_article");
	
				$messagex = _T('form_pet_mail_confirmation', array('titre' => $titre, 'nom_email' => $nom_email, 'nom_site' => $nom_site, 'url_site' => $url_site, 'url' => $url));

				if (envoyer_mail($adresse_email, _T('form_pet_confirmation')." ".$titre, $messagex)) {

					$nom_email = addslashes($nom_email);
					$adresse_email = addslashes($adresse_email);
					$nom_site = addslashes($nom_site);
					$url_site = addslashes($url_site);
					$message = addslashes($message);
	
					spip_query("INSERT INTO spip_signatures (id_article, date_time, nom_email, ad_email, nom_site, url_site, message, statut) VALUES ('$id_article', NOW(), '$nom_email', '$adresse_email', '$nom_site', '$url_site', '$message', '$passw')");
					return _T('form_pet_envoi_mail_confirmation');
				}
				else {
					return _T('form_pet_probleme_technique');
				}
			}
			spip_release_lock($lock);
		}
	}
	else {
		return _T('form_pet_probleme_technique');
	}

}


function test_pass() {
	include_ecrire("inc_acces.php3");
	for (;;) {
		$passw = creer_pass_aleatoire();
		$query = "SELECT statut FROM spip_signatures WHERE statut='$passw'";
		$result = spip_query($query);
		if (!spip_num_rows($result)) break;
	}
	return $passw;
}

?>
