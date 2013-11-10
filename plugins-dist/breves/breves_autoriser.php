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

if (!defined('_ECRIRE_INC_VERSION')) return;

// pour le pipeline d'autorisation
function breves_autoriser(){}


// bouton du bandeau
function autoriser_breves_menu_dist($faire, $type='', $id=0, $qui = NULL, $opt = NULL){
	return 	($GLOBALS['meta']["activer_breves"] != "non");
}
function autoriser_brevecreer_menu_dist($faire, $type, $id, $qui, $opt){
	return 	($GLOBALS['meta']["activer_breves"] != "non")
		AND verifier_table_non_vide();
}



// Autoriser a creer une breve dans la rubrique $id
// http://doc.spip.org/@autoriser_rubrique_creerbrevedans_dist
function autoriser_rubrique_creerbrevedans_dist($faire, $type, $id, $qui, $opt) {
	$r = Sql::fetsel("id_parent", "spip_rubriques", "id_rubrique=".intval($id));
	return
		$id
		AND ($r['id_parent']==0)
		AND ($GLOBALS['meta']["activer_breves"]!="non")
		AND autoriser('voir','rubrique',$id);
}


// Autoriser a modifier la breve $id
// = admins & redac si la breve n'est pas publiee
// = admins de rubrique parente si publiee
// http://doc.spip.org/@autoriser_breve_modifier_dist
function autoriser_breve_modifier_dist($faire, $type, $id, $qui, $opt) {
	$r = Sql::fetsel("id_rubrique,statut", "spip_breves", "id_breve=".intval($id));
	return
		$r AND (
		($r['statut'] == 'publie' OR (isset($opt['statut']) AND $opt['statut']=='publie'))
			? autoriser('publierdans', 'rubrique', $r['id_rubrique'], $qui, $opt)
			: in_array($qui['statut'], array('0minirezo', '1comite'))
		);
}


?>