<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

// http://doc.spip.org/@inc_chercher_logo_dist
function inc_chercher_logo_dist($id, $_id_objet, $mode='on') {
	global $formats_logos;
	# attention au cas $id = '0' pour LOGO_SITE_SPIP : utiliser intval()

	$type = type_du_logo($_id_objet);
	$nom = $type . $mode . intval($id);

	foreach ($formats_logos as $format) {
		if (@file_exists($d = (_DIR_LOGOS . $nom . '.' . $format))) {
			return array($d, _DIR_LOGOS, $nom, $format, @filemtime($d));
		}
	}
	# coherence de type pour servir comme filtre (formulaire_login)
	return array();
}

// http://doc.spip.org/@type_du_logo
function type_du_logo($_id_objet) {
	return isset($GLOBALS['table_logos'][$_id_objet])
		? $GLOBALS['table_logos'][$_id_objet]
		: objet_type(preg_replace(',^id_,','',$_id_objet));
}

// Exceptions standards (historique)
global $table_logos;
$table_logos = array( 
	'id_article' => 'art', 
	'id_auteur' => 'aut', 
	'id_rubrique' => 'rub',
	'id_groupe' => 'groupe',
);

?>
