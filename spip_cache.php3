<?

include ("ecrire/inc_version.php3");

include_local("ecrire/inc_connect.php3");
include_local("ecrire/inc_meta.php3");
include_local("ecrire/inc_admin.php3");

include_local("inc-cache.php3");


if ($purger_cache == "oui") {
	if (verifier_action_auteur("purger_cache", $hash, $id_auteur)) {
		purger_repertoire("CACHE", 0);
		mysql_query("DELETE FROM spip_forum_cache");
	}
}

if ($purger_squelettes == "oui") {
	if (verifier_action_auteur("purger_squelettes", $hash, $id_auteur)) {
		purger_repertoire("CACHE", 0, '^skel_');
	}
}

$url = "./ecrire/" . $redirect;
@header ("Location: $url");

?>