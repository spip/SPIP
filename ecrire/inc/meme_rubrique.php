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
// http://doc.spip.org/@inc_meme_rubrique_dist
function inc_meme_rubrique_dist($id_rubrique, $id, $type, $order=''){

	$table = table_objet($type);
	$primary = id_table_objet($type);

	$lister_objets = charger_fonction('lister_objets','inc');
	$contexte = array('id_rubrique'=>$id_rubrique,'where'=>"$primary!=".intval($id));

	if ($GLOBALS['visiteur_session']['statut'] !== '0minirezo')
		$contexte['statut'] = array('publie','prop');

	if ($order)
		$contexte['par'] = $order;
	elseif ($type=='article' AND defined('_TRI_ARTICLES_RUBRIQUE'))
		$contexte['par'] = _TRI_ARTICLES_RUBRIQUE;

	$contexte['titre'] = _T('info_meme_rubrique');
	return $lister_objets($table,$contexte);

}
?>
