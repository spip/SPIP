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

function afficher_barre($champ, $forum=false) {
	global $barre_typo;
	tester_variable("barre_typo", "spip");
	if($barre_typo != '' AND file_exists(_DIR_RESTREINT."inc_barre_$barre_typo.php" )) {
		include_ecrire("inc_barre_$barre_typo");
		$f = "afficher_barre_$barre_typo";
		if(function_exists($f)) {
			return $f($champ, $forum);
		}
	}
	return "";
}

?>
