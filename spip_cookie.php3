<?

include ("ecrire/inc_version.php3");
include_local ("ecrire/inc_cookie.php3");

if ($ajout_cookie == "oui") {
	ajout_cookie_admin();
}

if ($supp_cookie == "oui") {
	supp_cookie_admin();
}


$url = "./ecrire/" . $redirect;
@header ("Location: $url");


?>