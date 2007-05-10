<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;
include_spip('inc/presentation');

// http://doc.spip.org/@exec_editer_mot_dist
function exec_editer_mot_dist()
{
	$objet = _request('objet');
	$id_objet = intval(_request('id_objet'));

	if ($GLOBALS['connect_toutes_rubriques']) // pour eviter SQL
		$droit = true;
	elseif ($objet == 'article')
		$droit = autoriser('modifier','article',$id_objet);
	elseif ($objet == 'rubrique')
		$droit = autoriser('publierdans','rubrique',$id_objet);
	else {
		if ($objet == 'breve')
			$droit = spip_query("SELECT id_rubrique FROM spip_breves WHERE id_breve="._q($id_objet));
		else
			$droit = spip_query("SELECT id_rubrique FROM spip_syndic WHERE id_syndic="._q($id_objet));
		$droit = autoriser('publierdans','rubrique',$droit['id_rubrique']);
	}

	if (!$droit) {
		echo minipres();
		exit;
	}

	$ch = _request('cherche_mot');
	$id_groupe = _request('select_groupe');
	$editer_mot = charger_fonction('editer_mot', 'inc');
	ajax_retour($editer_mot($objet, $id_objet, $ch, $id_groupe, 'ajax')); 
}
?>
