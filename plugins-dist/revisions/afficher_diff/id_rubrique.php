<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/diff');

/**
 * Afficher le diff d'un id_rubrique
 * on affiche en fait le deplacement si id_rubrique a change
 * le nom de la rubrique sinon
 *
 * @param string $champ
 * @param string $old
 * @param string $new
 * @param string $format
 * @return string
 */
function afficher_diff_id_rubrique_dist($champ,$old,$new,$format='diff'){
	// ne pas se compliquer la vie !
	if ($old==$new)
		$out = _T('info_dans_rubrique')
		       . " <b>&#171;&nbsp;".generer_info_entite($new,'rubrique','titre')."&nbsp;&#187;</b>";
	else
		$out = _T('revisions:version_deplace_rubrique',
							 array('from'=> generer_info_entite($old,'rubrique','titre')
									 ,'to'=>generer_info_entite($new,'rubrique','titre')
							 )
		         );
	return $out;
}