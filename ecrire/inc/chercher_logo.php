<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
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
 * @global formats_logos Extensions possibles des logos
 * @uses type_du_logo()
 *
 * @param int $id
 *     Identifiant de l'objet
 * @param string $_id_objet
 *     Nom de la clé primaire de l'objet
 * @param string $mode
 *     Mode de survol du logo désiré (on ou off)
 * @return array
 *     - Liste (chemin complet du fichier, répertoire de logos, nom du logo, extension du logo, date de modification[, doc])
 *     - array vide aucun logo trouvé.
 **/
function inc_chercher_logo_dist($id, $_id_objet, $mode = 'on', $compat_old_logos = true) {

	$mode = preg_replace(',\W,', '', $mode);
	if ($mode) {
		// chercher dans la base
		$mode_document = 'logo' . $mode;
		$objet = objet_type($_id_objet);
		$doc = sql_fetsel('D.*', 'spip_documents AS D JOIN spip_documents_liens AS L ON L.id_document=D.id_document', 'D.mode='.sql_quote($mode_document).' AND L.objet='.sql_quote($objet).' AND id_objet='.intval($id));
		if ($doc) {
			include_spip('inc/documents');
			$d = get_spip_doc($doc['fichier']);
			return array($d, _DIR_IMG, basename($d), $doc['extension'], @filemtime($d), $doc);
		}

		# deprecated TODO remove
		if ($compat_old_logos) {
			# attention au cas $id = '0' pour LOGO_SITE_SPIP : utiliser intval()
			$type = type_du_logo($_id_objet);
			$nom = $type . $mode . intval($id);

			foreach ($GLOBALS['formats_logos'] as $format) {
				if (@file_exists($d = (_DIR_LOGOS . $nom . '.' . $format))) {
					return array($d, _DIR_LOGOS, $nom, $format, @filemtime($d));
				}
			}
		}
	}

	# coherence de type pour servir comme filtre (formulaire_login)
	return array();
}

/**
 * Retourne le type de logo tel que `art` depuis le nom de clé primaire
 * de l'objet
 *
 * C'est par défaut le type d'objet, mais il existe des exceptions historiques
 * déclarées par la globale `$table_logos`
 *
 * @global table_logos Exceptions des types de logo
 *
 * @param string $_id_objet
 *     Nom de la clé primaire de l'objet
 * @return string
 *     Type du logo
 * @deprecated 4.0 MAIS NE PAS SUPPRIMER CAR SERT POUR L'UPGRADE des logos et leur mise en base
 **/
function type_du_logo($_id_objet) {
	return isset($GLOBALS['table_logos'][$_id_objet])
		? $GLOBALS['table_logos'][$_id_objet]
		: objet_type(preg_replace(',^id_,', '', $_id_objet));
}

// Exceptions standards (historique)
$GLOBALS['table_logos'] = array(
	'id_article' => 'art',
	'id_auteur' => 'aut',
	'id_rubrique' => 'rub',
	'id_groupe' => 'groupe',
);
