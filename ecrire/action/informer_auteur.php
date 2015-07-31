<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// http://doc.spip.org/@action_informer_auteur_dist
function action_informer_auteur_dist() {
	include_spip('base/abstract_sql');
	include_spip('inc/json');

	$row = array();
	if ($login=_request('var_login')) {
		$row =  sql_fetsel('id_auteur,login,alea_actuel,alea_futur,prefs', 'spip_auteurs', "login=" . sql_quote($login));
		// Retrouver ceux qui signent de leur nom ou email
		if (!$row AND !spip_connect_ldap()) {
			$row = sql_fetsel('id_auteur,login,alea_actuel,alea_futur,prefs', 'spip_auteurs', "(nom = " . sql_quote($login) . " OR email = " . sql_quote($login) . ") AND login<>'' AND statut<>'5poubelle'");
		}
		if ($row) {
			$prefs = unserialize($row['prefs']);
			$row['cnx'] = $prefs['cnx'] == 'perma' ? '1' : '0';
			unset($row['prefs']);
			
			$page = evaluer_fond('formulaires/logo_auteur', array('id_auteur'=>$row['id_auteur']));
			$row['logo'] = $page['texte'];
		}
		unset($row['id_auteur']);

		echo json_export($row);
	}
}

?>
