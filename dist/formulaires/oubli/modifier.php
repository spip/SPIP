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

// la saisie a ete validee, on peut agir
function formulaires_oubli_modifier_dist(){
echo 'plop';
	$message = "";
	if (
	    ($p = _request('p'))
	 && ($oubli = _request('oubli'))) {
		include_spip('inc/acces');
		$mdpass = md5($oubli);
		$htpass = generer_htpass($oubli);
		include_spip('base/abstract_sql');
		$res = sql_select("login", "spip_auteurs", "cookie_oubli=" . sql_quote($p) . " AND statut<>'5poubelle' AND pass<>''");
		$row = sql_fetch($res);
		sql_updateq('spip_auteurs', array('htpass' =>$htpass, 'pass'=>$mdpass, 'alea_actuel'=>'', 'cookie_oubli'=>''), "cookie_oubli=" . sql_quote($p));
		
	
		$login = $row['login'];
		$message = "<b>" . _T('pass_nouveau_enregistre') . "</b>".
		"<p>" . _T('pass_rappel_login', array('login' => $login));
	}
	return $message;
}

?>