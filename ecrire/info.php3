<?php

include ("inc_version.php3");
if (_FILE_CONNECT) {
	include_ecrire ("inc_auth.php3");
	if ($connect_statut != '0minirezo') exit;
}

phpinfo();




?>
