<?php
if (defined("_ECRIRE_INC_CONNECT")) return;
define("_ECRIRE_INC_CONNECT", "1");
$GLOBALS['spip_connect_version'] = 0.2;
include_ecrire('inc_db_mysql.php3');
spip_connect_db('localhost','','root','','spip');
?>