<?php

if (file_exists("inc_connect.php3")) {
	include ("inc_version.php3");

	include_local ("inc_connect.php3");
	include_local ("inc_auth.php3");
	if ($connect_statut != '0minirezo') exit;
}

phpinfo();




?>
