<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_LISTES")) return;
define("_ECRIRE_INC_LISTES", "1");

function get_listes($statut_auteur) {
	static $res;
	if (!$res)
		$res = spip_query ("SELECT * FROM spip_listes WHERE statut='publie' AND FIND_IN_SET('$statut_auteur', droits)"); 

	return ($res);
}

?>
