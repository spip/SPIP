<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

/**
 * Gestion des emails et de leur envoi
 *
 * @package SPIP\Core\Mail
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}



/**
 * Vérifier la conformité d'une ou plusieurs adresses email (suivant RFC 822)
 *
 * @param string $adresses
 *      Adresse ou liste d'adresse (separees pas des virgules)
 * @return bool|string
 *      - false si une des adresses n'est pas conforme,
 *      - la normalisation de la dernière adresse donnée sinon
 **/
function inc_email_valide_dist($adresses) {
	// eviter d'injecter n'importe quoi dans preg_match
	if (!is_string($adresses)) {
		return false;
	}

	// Si c'est un spammeur autant arreter tout de suite
	if (preg_match(",[\n\r].*(MIME|multipart|Content-),i", $adresses)) {
		spip_log("Tentative d'injection de mail : $adresses");

		return false;
	}

	foreach (explode(',', $adresses) as $v) {
		// nettoyer certains formats
		// "Marie Toto <Marie@toto.com>"
		$adresse = trim(preg_replace(',^[^<>"]*<([^<>"]+)>$,i', "\\1", $v));
		// RFC 822
		if (!preg_match('#^[^()<>@,;:\\"/[:space:]]+(@([-_0-9a-z]+\.)*[-_0-9a-z]+)$#i', $adresse)) {
			return false;
		}
	}

	return $adresse;
}
