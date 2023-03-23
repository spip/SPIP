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
 * Recherche de logo
 *
 * @package SPIP\Core\Logos
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Cherche le logo d'un élément d'objet
 *
 * @param int $id
 *     Identifiant de l'objet
 * @param string $_id_objet
 *     Nom de la clé primaire de l'objet
 * @param string $mode
 *     Mode de survol du logo désiré (on ou off)
 * @param bool $compat_old_logos (unused) @deprecated 5.0
 * @return array
 *     - Liste (chemin complet du fichier, répertoire de logos, nom du logo, extension du logo, date de modification[, doc])
 *     - array vide aucun logo trouvé.
 **/
function inc_chercher_logo_dist(int $id, string $_id_objet, string $mode = 'on', bool $compat_old_logos = false): array {

	$mode = preg_replace(',\W,', '', $mode);
	if ($mode) {
		// chercher dans la base
		$mode_document = 'logo' . $mode;
		$objet = objet_type($_id_objet);
		$doc = sql_fetsel(
			'D.*',
			'spip_documents AS D JOIN spip_documents_liens AS L ON L.id_document=D.id_document',
			'D.mode=' . sql_quote($mode_document) . ' AND L.objet=' . sql_quote($objet) . ' AND id_objet=' . $id
		);
		if ($doc) {
			include_spip('inc/documents');
			$d = get_spip_doc($doc['fichier']);
			return [$d, _DIR_IMG, basename($d), $doc['extension'], @filemtime($d), $doc];
		}
	}

	# coherence de type pour servir comme filtre (formulaire_login)
	return [];
}
