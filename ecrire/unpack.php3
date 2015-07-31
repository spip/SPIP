<?php

include ("inc_version.php3");


include_ecrire ("inc_auth.php3");
include_ecrire ("inc_meta.php3");
include_ecrire ("inc_admin.php3");

$action = "t&eacute;l&eacute;chargement de la derni&egrave;re version";

debut_admin($action);

$hash = calculer_action_auteur("unpack");

fin_admin($action);

if (@file_exists("../spip_loader.php3"))
	@header("Location: ../spip_loader.php3?hash=$hash&id_auteur=$connect_id_auteur");
else if (@file_exists("../spip_unpack.php3"))
	@header("Location: ../spip_unpack.php3?hash=$hash&id_auteur=$connect_id_auteur");
else
	@header("Location: ../spip_loader.php3?hash=$hash&id_auteur=$connect_id_auteur");

?>
