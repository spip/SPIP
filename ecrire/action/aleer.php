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

/**
    * Transform a variable into its javascript equivalent (recursive)
    * @access private
    * @param mixed the variable
    * @return string js script | boolean false if error
    */
function var2js($var) {
    $asso = false;
    switch (true) {
        case is_null($var) :
            return 'null';
        case is_string($var) :
            return '"' . str_replace('&', '\x26', addcslashes($var, "\"\\\n\r")) . '"';
        case is_bool($var) :
            return $var ? 'true' : 'false';
        case is_scalar($var) :
            return $var;
        case is_object( $var) :
            $var = get_object_vars($var);
            $asso = true;
        case is_array($var) :
            $keys = array_keys($var);
            $ikey = count($keys);
            while (!$asso && $ikey--) {
                $asso = $ikey !== $keys[$ikey];
            }
            $sep = '';
            if ($asso) {
                $ret = '{';
                foreach ($var as $key => $elt) {
                    $ret .= $sep . '"' . $key . '":' . var2js($elt);
                    $sep = ',';
                }
                return $ret ."}\n";
            } else {
                $ret = '[';
                foreach ($var as $elt) {
                    $ret .= $sep . var2js($elt);
                    $sep = ',';
                }
                return $ret ."]\n";
            }
    }
    return false;
}

function json_export($var) {
	$var = var2js($var);

	// flag indiquant qu'on est en iframe et qu'il faut proteger nos
	// donnees dans un <textarea> ; attention $_FILES a ete vide par array_pop
	if (defined('FILE_UPLOAD'))
		return "<textarea>".htmlspecialchars($var)."</textarea>";
	else
		return $var;
}

include_spip('base/abstract_sql');
// http://doc.spip.org/@action_configurer_dist
function action_aleer_dist() {
	$row = array();
	if ($login=_request('var_login')) {
		$row =  sql_fetsel('login,alea_actuel,alea_futur', 'spip_auteurs', "login=" . sql_quote($login));
		// Retrouver ceux qui signent de leur nom ou email
		if (!$row AND !spip_connect_ldap()) {
			$row = sql_fetsel('login,alea_actuel,alea_futur', 'spip_auteurs', "(nom = " . sql_quote($login) . " OR email = " . sql_quote($login) . ") AND login<>'' AND statut<>'5poubelle'");
		}
		echo json_export($row);
	}
}

?>