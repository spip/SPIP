<?php

include ("inc_version.php3");

include_local ("inc_connect.php3");
include_local ("inc_auth.php3");
include_local ("inc_admin.php3");

$action = "suppression totale et irr&eacute;versible";

debut_admin($action);

mysql_query("DROP TABLE spip_articles");
mysql_query("DROP TABLE spip_auteurs");
mysql_query("DROP TABLE spip_auteurs_articles");
mysql_query("DROP TABLE spip_breves");
mysql_query("DROP TABLE spip_forum");
mysql_query("DROP TABLE spip_forum_cache");
mysql_query("DROP TABLE spip_index_articles");
mysql_query("DROP TABLE spip_index_auteurs");
mysql_query("DROP TABLE spip_index_breves");
mysql_query("DROP TABLE spip_index_mots");
mysql_query("DROP TABLE spip_index_rubriques");
mysql_query("DROP TABLE spip_meta");
mysql_query("DROP TABLE spip_mots");
mysql_query("DROP TABLE spip_mots_articles");
mysql_query("DROP TABLE spip_petitions");
mysql_query("DROP TABLE spip_rubriques");
mysql_query("DROP TABLE spip_signatures");
mysql_query("DROP TABLE spip_syndic");
mysql_query("DROP TABLE spip_syndic_articles");
mysql_query("DROP TABLE spip_auteurs_messages");
mysql_query("DROP TABLE spip_auteurs_rubriques");
mysql_query("DROP TABLE spip_documents");
mysql_query("DROP TABLE spip_documents");
mysql_query("DROP TABLE spip_documents_articles");
mysql_query("DROP TABLE spip_groupes_mots");
mysql_query("DROP TABLE spip_index_dico");
mysql_query("DROP TABLE spip_index_syndic");
mysql_query("DROP TABLE spip_messages");
mysql_query("DROP TABLE spip_mots_breves");
mysql_query("DROP TABLE spip_mots_forum");
mysql_query("DROP TABLE spip_mots_rubriques");
mysql_query("DROP TABLE spip_mots_syndic");
mysql_query("DROP TABLE spip_types_documents");

@unlink(".htaccess");
@unlink("inc_connect.php3");

@header("Location: ./");

fin_admin($action);

?>
