<?php

include ("inc_version.php3");

include_ecrire ("inc_connect.php3");
include_ecrire ("inc_auth.php3");
include_ecrire ("inc_admin.php3");

$action = "suppression totale et irr&eacute;versible";

debut_admin($action);

spip_query("DROP TABLE spip_articles");
spip_query("DROP TABLE spip_auteurs");
spip_query("DROP TABLE spip_auteurs_articles");
spip_query("DROP TABLE spip_breves");
spip_query("DROP TABLE spip_forum");
spip_query("DROP TABLE spip_forum_cache");
spip_query("DROP TABLE spip_index_articles");
spip_query("DROP TABLE spip_index_auteurs");
spip_query("DROP TABLE spip_index_breves");
spip_query("DROP TABLE spip_index_mots");
spip_query("DROP TABLE spip_index_rubriques");
spip_query("DROP TABLE spip_meta");
spip_query("DROP TABLE spip_mots");
spip_query("DROP TABLE spip_mots_articles");
spip_query("DROP TABLE spip_petitions");
spip_query("DROP TABLE spip_rubriques");
spip_query("DROP TABLE spip_signatures");
spip_query("DROP TABLE spip_syndic");
spip_query("DROP TABLE spip_syndic_articles");
spip_query("DROP TABLE spip_auteurs_messages");
spip_query("DROP TABLE spip_auteurs_rubriques");
spip_query("DROP TABLE spip_documents");
spip_query("DROP TABLE spip_documents");
spip_query("DROP TABLE spip_documents_articles");
spip_query("DROP TABLE spip_groupes_mots");
spip_query("DROP TABLE spip_index_dico");
spip_query("DROP TABLE spip_index_syndic");
spip_query("DROP TABLE spip_messages");
spip_query("DROP TABLE spip_mots_breves");
spip_query("DROP TABLE spip_mots_forum");
spip_query("DROP TABLE spip_mots_rubriques");
spip_query("DROP TABLE spip_mots_syndic");
spip_query("DROP TABLE spip_types_documents");

@unlink(".htaccess");
@unlink("inc_connect.php3");

@header("Location: ./");

fin_admin($action);

?>
