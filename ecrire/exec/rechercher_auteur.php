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

# gerer un charset minimaliste en convertissant tout en unicode &#xxx;

// http://doc.spip.org/@exec_rechercher_auteur_dist
function exec_rechercher_auteur_dist()
{
	exec_rechercher_auteur_args(_request('idom'));
}

// http://doc.spip.org/@exec_rechercher_auteur_args
function exec_rechercher_auteur_args($idom)
{
	if (!preg_match('/\w+/',$idom))
	      {
		include_spip('inc/minipres');
		echo minipres();
	      } else {
		include_spip('inc/actions');
		$where = preg_split(",\s+,", _request('nom'));
		if ($where) {
		  foreach ($where as $k => $v) 
			$where[$k] = "'%" . substr(str_replace("%","\%", sql_quote($v)),1,-1) . "%'";
		  $where= ("(nom LIKE " . join(" AND nom LIKE ", $where) . ")");
		}
		include_spip('inc/selectionner_auteur');
		ajax_retour(selectionner_auteur_boucle($where, $idom));
	}
}
?>
