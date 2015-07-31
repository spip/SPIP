<?php

include ("inc_version.php3");
include_local ("inc_connect.php3");
include_local ("inc_auth.php3");
include_local ("inc_import.php3");
include_local ("inc_admin.php3");
include_local ("inc_meta.php3");

if ($archive) $action = "restauration de la sauvegarde $archive";

debut_admin($action);


$archive = "data/$archive";

ecrire_meta("debut_restauration", "debut");
ecrire_meta("fichier_restauration", $archive);
ecrire_meta("status_restauration", "0");
ecrire_metas();

fin_admin($action);

@header("Location: index.php3");

exit;

?>
