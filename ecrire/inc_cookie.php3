<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_COOKIE")) return;
define("_ECRIRE_INC_COOKIE", "1");


function ajout_cookie_admin()
{
	setcookie("spip_admin", "admin", time() + 3600 * 24 * 7);
}

function supp_cookie_admin()
{
	setcookie("spip_admin", "admin", time() - 3600 * 24);
}

?>