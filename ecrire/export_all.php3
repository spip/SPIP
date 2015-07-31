<?php

include ("inc_version.php3");

include_local ("inc_connect.php3");
include_local ("inc_auth.php3");
include_local ("inc_export.php3");
include_local ("inc_admin.php3");
include_local ("inc_presentation.php3");

if (!$archive) {
	if ($gz) $archive = "dump.xml.gz";
	else $archive = "dump.xml";
}

$action = "exportation de la base vers $archive";

debut_admin($action);

$debug_limit = '';
//$debug_limit = ' LIMIT 0,100';

$f = ($gz) ? @gzopen("data/$archive", "wb") : fopen("data/$archive", "wb");
$_fputs = ($gz) ? gzputs : fputs;

$_fputs($f, "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?".">\n<SPIP version=\"$spip_version_affichee\" version_base=\"$spip_version\" version_archive=\"$version_archive\">\n\n");

$query = "SELECT * FROM spip_rubriques";
export_objets(mysql_query($query), "rubrique", $f, $gz);

$query = "SELECT * FROM spip_auteurs";
export_objets(mysql_query($query), "auteur", $f, $gz);

$query = "SELECT * FROM spip_articles".$debug_limit;
export_objets(mysql_query($query), "article", $f, $gz);

$query = "SELECT * FROM spip_types_documents";
export_objets(mysql_query($query), "type_document", $f, $gz);

$query = "SELECT * FROM spip_documents";
export_objets(mysql_query($query), "document", $f, $gz);

$query = "SELECT * FROM spip_mots";
export_objets(mysql_query($query), "mot", $f, $gz);

$query = "SELECT * FROM spip_groupes_mots";
export_objets(mysql_query($query), "groupe_mots", $f, $gz);

$query = "SELECT * FROM spip_breves".$debug_limit;
export_objets(mysql_query($query), "breve", $f, $gz);

$query = "SELECT * FROM spip_messages";
export_objets(mysql_query($query), "message", $f, $gz);

$query = "SELECT * FROM spip_forum".$debug_limit;
export_objets(mysql_query($query), "forum", $f, $gz);

$query = "SELECT * FROM spip_petitions";
export_objets(mysql_query($query), "petition", $f, $gz);

$query = "SELECT * FROM spip_signatures".$debug_limit;
export_objets(mysql_query($query), "signature", $f, $gz);

$query = "SELECT * FROM spip_syndic";
export_objets(mysql_query($query), "syndic", $f, $gz);

$query = "SELECT * FROM spip_syndic_articles".$debug_limit;
export_objets(mysql_query($query), "syndic_article", $f, $gz);


$_fputs ($f, build_end_tag("SPIP")."\n");

if ($gz) gzclose($f);
else fclose($f);


fin_admin($action);

install_debut_html("Sauvegarde");
echo "<p>La base a &eacute;t&eacute; sauvegard&eacute;e dans <b>ecrire/data/$archive</b>.\n";
install_fin_html();

exit;

?>