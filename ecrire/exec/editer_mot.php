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

// http://doc.spip.org/@exec_editer_mot_dist
function exec_editer_mot_dist()
{
	include_spip('inc/actions');
	include_spip('inc/mots');
	include_spip('inc/presentation');

	$objet = _request('objet');
	$id_objet = intval(_request('id_objet'));

	if ($GLOBALS['connect_toutes_rubriques']) // pour eviter SQL
		$droit = true;
	elseif ($objet == 'article')
		$droit = acces_article($id);
	elseif ($objet == 'rubrique')
		$droit = acces_rubrique($id);
	else {
		if ($objet == 'breve')
			$droit = spip_query("SELECT id_rubrique FROM spip_breves WHERE id_breve='$id_objet'");
		else $droit = spip_query("SELECT id_rubrique FROM spip_syndic WHERE id_syndic=$id_objet");
		$droit = acces_rubrique($droit['id_rubrique']);
	}

	if (!$droit) {
		spip_log("Tentative d'intrusion de " . $GLOBALS['auteur_session']['nom'] . " dans " . $GLOBALS['exec']);
		include_spip('inc/minipres');
		minipres(_T('info_acces_interdit'));
	}
	spip_log(" $objet $id_objet ");
	return formulaire_mots($objet, $id_objet, _request('cherche_mot'),
			      _request('select_groupe'),
			      'ajax'
			      ); 
}

?>
