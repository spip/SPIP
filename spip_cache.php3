<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


include ("ecrire/inc_version.php3");

include_ecrire("inc_meta.php3");
include_ecrire("inc_session.php3");
include_local("inc-cache.php3");

if ($purger_cache == "oui"
AND verifier_action_auteur("purger_cache", $hash, $id_auteur)) {
	purger_cache();
}

if ($purger_squelettes == "oui"
AND verifier_action_auteur("purger_squelettes", $hash, $id_auteur)) {
	  purger_squelettes();
}


if ($afficher_cache_images == "oui"
AND verifier_action_auteur("afficher_cache_images", $hash, $id_auteur)) {
	include_ecrire('inc_lang.php3');
	lang_select($lang);
	calculer_cache_vignettes();
}

if ($purger_cache_images == "oui"
AND verifier_action_auteur("purger_cache_images", $hash, $id_auteur)) {
	purger_cache_images();
	purger_cache();
}


if ($redirect) redirige_par_entete($redirect);

?>
