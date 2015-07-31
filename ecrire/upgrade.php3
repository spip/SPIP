<?php

include ("inc_version.php3");

include_local ("inc_connect.php3");
include_local ("inc_auth.php3");
include_local ("inc_admin.php3");
include_local ("inc_acces.php3");
include_local ("inc_meta.php3");

debut_admin("upgrade de la base");

include_local ("inc_base.php3");

creer_base();
maj_base();
ecrire_acces();

$hash = calculer_action_auteur("purger_squelettes");
$redirect = rawurlencode("index.php3");

fin_admin("upgrade de la base");

@header ("Location: ../spip_cache.php3?purger_squelettes=oui&id_auteur=$connect_id_auteur&hash=$hash&redirect=$redirect");

?>
