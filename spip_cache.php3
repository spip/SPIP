<?php

include ("ecrire/inc_version.php3");

include_ecrire("inc_meta.php3");
include_ecrire("inc_admin.php3");
include_local("inc-cache.php3");

if ($purger_cache == "oui") {
	if (verifier_action_auteur("purger_cache", $hash, $id_auteur))
		purger_cache();
}

if ($purger_squelettes == "oui") {
	if (verifier_action_auteur("purger_squelettes", $hash, $id_auteur))
	  purger_squelettes();
}
redirige_par_entete($redirect);

?>
