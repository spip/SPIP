<?php

include ("inc_version.php3");

include_ecrire ("inc_connect.php3");
include_ecrire ("inc_auth.php3");
include_ecrire ("inc_export.php3");
include_ecrire ("inc_admin.php3");
include_ecrire ("inc_presentation.php3");

if (!$archive) {
	if ($gz) $archive = "dump.xml.gz";
	else $archive = "dump.xml";
}

$action = "exportation de la base vers $archive";

debut_admin($action);

$debug_limit = '';
//$debug_limit = ' LIMIT 0,100';
if (!$debut_limit) $debut_limit = 0;

install_debut_html("Sauvegarde");

if (!$etape) echo "<p><font size=2>Si la sauvegarde a &eacute;chou&eacute; (&laquo;Maximum execution time exceeded&raquo;), <a href='export_all.php3?etape=1&gz=$gz'>proc&eacute;dez &eacute;tape par &eacute;tape</a>.</font><p>";


if ($etape < 2){
	$f = ($gz) ? @gzopen("data/$archive", "wb") : fopen("data/$archive", "wb");
}
else {
	$f = ($gz) ? @gzopen("data/$archive", "ab") : fopen("data/$archive", "ab");
}
$_fputs = ($gz) ? gzputs : fputs;

if ($etape < 2) $_fputs($f, "<"."?xml version=\"1.0\" encoding=\"ISO-8859-1\"?".">\n<SPIP version=\"$spip_version_affichee\" version_base=\"$spip_version\" version_archive=\"$version_archive\">\n\n");

$query = "SELECT * FROM spip_rubriques";
export_objets($query, "rubrique", $f, $gz, $etape, 1, "Sauvegarder les rubriques");

$query = "SELECT * FROM spip_auteurs";
export_objets($query, "auteur", $f, $gz, $etape, 2, "Sauvegarder les auteurs");

$query = "SELECT * FROM spip_articles";
export_objets($query, "article", $f, $gz, $etape, 3, "Sauvegarder les articles");

$query = "SELECT * FROM spip_types_documents";
export_objets($query, "type_document", $f, $gz, $etape, 4, "Sauvegarder les types de documents");

$query = "SELECT * FROM spip_documents";
export_objets($query, "document", $f, $gz, $etape, 5, "Sauvegarder les documents");

$query = "SELECT * FROM spip_mots";
export_objets($query, "mot", $f, $gz, $etape, 6, "Sauvegarder les mots-cl&eacute;s");

$query = "SELECT * FROM spip_groupes_mots";
export_objets($query, "groupe_mots", $f, $gz, $etape, 7, "Sauvegarder les groupes de mots");

$query = "SELECT * FROM spip_breves".$debug_limit;
export_objets($query, "breve", $f, $gz, $etape, 8, "Sauvegarder les br&egrave;ves");

//$query = "SELECT * FROM spip_messages";
//export_objets($query, "message", $f, $gz, $etape, 9, "Sauvegarder les messages");

$query = "SELECT * FROM spip_forum WHERE statut='publie'".$debug_limit;
export_objets($query, "forum", $f, $gz, $etape, 9, "Sauvegarder les forums");

$query = "SELECT * FROM spip_petitions";
export_objets($query, "petition", $f, $gz, $etape, 10, "Sauvegarder les p&eacute;titions");

$query = "SELECT * FROM spip_signatures".$debug_limit;
export_objets($query, "signature", $f, $gz, $etape, 11, "Sauvegarder les signatures de p&eacute;titions");

$query = "SELECT * FROM spip_syndic";
export_objets($query, "syndic", $f, $gz, $etape, 12, "Sauvegarder les sites r&eacute;f&eacute;renc&eacute;s");

$query = "SELECT * FROM spip_syndic_articles".$debug_limit;
export_objets($query, "syndic_article", $f, $gz, $etape, 13, "Sauvegarder les articles des sites r&eacute;f&eacute;renc&eacute;s");

/*$query = "SELECT * FROM spip_visites".$debug_limit;
export_objets($query, "spip_visite", $f, $gz, $etape, 14, "Sauvegarder les visites");

$query = "SELECT * FROM spip_referers".$debug_limit;
export_objets($query, "spip_referers", $f, $gz, $etape, 15, "Sauvegarder les referers");
*/

if (!$etape OR $etape == 13){
	$_fputs ($f, build_end_tag("SPIP")."\n");



	echo "<p><b>Sauvegarde r&eacute;ussie.</b> La base a &eacute;t&eacute; sauvegard&eacute;e dans <b>ecrire/data/$archive</b>. Vous pouvez <a href='index.php3'>retourner &agrave; la gestion</a> de votre site.\n";
}
else {
	$etape_suivante = $etape + 1;
	if ($debut_limit > 1) echo "<p align='right'> <a href='export_all.php3?etape=$etape&debut_limit=$debut_limit&gz=$gz'>>>>> Passer &agrave; l'&eacute;tape suivante</a>";
	else  echo "<p align='right'> <a href='export_all.php3?etape=$etape_suivante&gz=$gz'>>>>> Passer &agrave; l'&eacute;tape suivante</a>";
}
install_fin_html();
	if ($gz) gzclose($f);
	else fclose($f);


if (!$etape OR $etape == 14) fin_admin($action);


exit;

?>