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

// http://doc.spip.org/@exec_editer_mots_dist
function exec_editer_mots_dist()
{
	exec_editer_mots_args(intval(_request('id_objet')), _request('objet'));
}

// http://doc.spip.org/@exec_editer_mots_args
function exec_editer_mots_args($id_objet, $objet)
{
	$base="";
	if (!$id_objet OR !$objet)
		$droit = false;
	elseif ($GLOBALS['connect_toutes_rubriques']) // pour eviter SQL
		$droit = true;
	elseif ($objet == 'article')
		$droit = autoriser('modifier','article',$id_objet);
	elseif ($objet == 'rubrique')
		$droit = autoriser('publierdans','rubrique',$id_objet);
	else {
		if ($objet == 'breve')
			$droit = sql_select("id_rubrique", "spip_breves", "id_breve=".sql_quote($id_objet));
		else
			$droit = sql_select("id_rubrique", "spip_syndic", "id_syndic=".sql_quote($id_objet));
		$droit = autoriser('publierdans','rubrique',$droit['id_rubrique']);
	}
	$bases = array('article'=>'articles','breve'=>'breves_voir','rubrique'=>'naviguer','syndic'=>'sites');
	if (isset($bases[$objet]))
		$base = $bases[$objet];

	if (!$droit) {
		include_spip('inc/minipres');
		echo minipres();
	} else {

		$ch = _request('cherche_mot');
		$id_groupe = _request('select_groupe');
		$editer_mots = charger_fonction('editer_mots', 'inc');
		ajax_retour($editer_mots($objet, $id_objet, $ch, $id_groupe, 'ajax',false,$base)); 
	}
}
?>
