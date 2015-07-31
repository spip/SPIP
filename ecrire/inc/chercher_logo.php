<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/logos');

// http://doc.spip.org/@inc_chercher_logo_dist
function inc_chercher_logo_dist($id, $type, $mode='on') {
	global $formats_logos;
	# attention au cas $id = '0' pour LOGO_SITE_SPIP : utiliser intval()

	$type = $GLOBALS['table_logos'][$type];
	$nom = $type . $mode . intval($id);

	foreach ($formats_logos as $format) {
		if (@file_exists($d = (_DIR_LOGOS . $nom . '.' . $format)))
			return array($d, _DIR_LOGOS, $nom, $format);
	}
	# coherence de type pour servir comme filtre (formulaire_login)
	return array();
}

global $table_logos;

$table_logos = array( 
		     'id_article' => 'art', 
		     'id_auteur' => 'aut', 
		     'id_breve' => 'breve', 
		     'id_mot' => 'mot', 
		     'id_syndic'=> 'site',
		     'id_rubrique' => 'rub'
		     );

?>
