<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/presentation');
include_spip('inc/actions');

// L'ajout d'un auteur se fait par mini-navigateur dans la fourchette:
define('_SPIP_SELECT_MIN_AUTEURS', 30); // en dessous: balise Select
define('_SPIP_SELECT_MAX_AUTEURS', 30); // au-dessus: saisie + return

// http://doc.spip.org/@inc_editer_auteurs_dist
function inc_editer_auteurs_dist($type, $id, $flag, $cherche_auteur, $ids, $titre_boite = NULL, $script_edit_objet = NULL) {
	return
	recuperer_fond('prive/objets/editer/liens',array('table_source'=>'auteurs','objet'=>$type,'id_objet'=>$id));
}

?>
