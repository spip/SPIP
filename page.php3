<?php

$fond = $_GET["fond"];

if (ereg("\/", $fond)) die ("Ben voyons");
if (strpos("\.\.", $fond) > 0) die ("Faut pas se gener");

$delais = 24 * 3600;

include ("inc-public.php3");

?>
