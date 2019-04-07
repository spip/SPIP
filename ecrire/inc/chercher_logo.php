<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2019                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
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
 *     - Liste (chemin complet du fichier, répertoire de logos, nom du logo, extension du logo, date de modification)
 *     - array vide aucun logo trouvé.
 **/
function inc_chercher_logo_dist($id, $_id_objet, $mode = 'on') {
	# attention au cas $id = '0' pour LOGO_SITE_SPIP : utiliser intval()
	
	$type = type_du_logo($_id_objet);
	$nom = $type . $mode . intval($id);
	
	foreach ($GLOBALS['formats_logos'] as $format) {
		if (@file_exists($d = (_DIR_LOGOS . $nom . '.' . $format))) {
			return array($d, _DIR_LOGOS, $nom, $format, @filemtime($d));
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
