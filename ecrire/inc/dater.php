<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/presentation');
include_spip('inc/texte');
include_spip('inc/actions');
include_spip('inc/date');

// http://doc.spip.org/@inc_dater_dist
function inc_dater_dist($id, $flag, $statut, $type, $script, $date, $date_redac='')
{
  return
		recuperer_fond("prive/editer/dater",array('objet'=>$type,'id_objet'=>$id));
}

?>
