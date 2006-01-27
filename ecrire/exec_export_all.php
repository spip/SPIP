<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_ecrire ("inc_export");
include_ecrire('inc_admin');

function export_all_dist()
{
  global $archive, $debut_limit, $etape, $gz, $spip_version, $spip_version_affichee, $version_archive;

if (!$archive) {
	if ($gz) $archive = "dump.xml.gz";
	else $archive = "dump.xml";
}

$action = _T('info_exportation_base', array('archive' => $archive));

debut_admin(generer_url_post_ecrire("export_all","archive=$archive"), $action);

$debug_limit = '';

 $debut_limit = intval($debut_limit);

install_debut_html(_T('info_sauvegarde'));

if (!$etape) echo "<p><blockquote><font size=2>"._T('info_sauvegarde_echouee')." <a href='" . generer_url_ecrire("export_all","reinstall=non&etape=1&gz=$gz") . "'>"._T('info_procedez_par_etape')."</a></font></blockquote><p>";

if ($etape < 2)
	$f = ($gz) ? gzopen(_DIR_SESSIONS . $archive, "wb") : fopen(_DIR_SESSIONS . $archive, "wb");
else
	$f = ($gz) ? gzopen(_DIR_SESSIONS . $archive, "ab") : fopen(_DIR_SESSIONS . $archive, "ab");

if (!$f) {
	echo _T('avis_erreur_sauvegarde', array('type'=>'.', 'id_objet'=>'. .'));
	exit;
}

$_fputs = ($gz) ? gzputs : fputs;

if ($etape < 2)
	$_fputs($f, "<"."?xml version=\"1.0\" encoding=\"".$GLOBALS['meta']['charset']."\"?".">\n<SPIP version=\"$spip_version_affichee\" version_base=\"$spip_version\" version_archive=\"$version_archive\">\n\n");

$query = "SELECT * FROM spip_rubriques";
export_objets($query, "rubrique", $f, $gz, $etape, 1, _T('info_sauvegarde_rubriques'));

$query = "SELECT * FROM spip_auteurs";
export_objets($query, "auteur", $f, $gz, $etape, 2, _T('info_sauvegarde_auteurs'));

$query = "SELECT * FROM spip_articles";
export_objets($query, "article", $f, $gz, $etape, 3, _T('info_sauvegarde_articles'));

$query = "SELECT * FROM spip_types_documents";
export_objets($query, "type_document", $f, $gz, $etape, 4, _T('info_sauvegarde_type_documents'));

$query = "SELECT * FROM spip_documents";
export_objets($query, "document", $f, $gz, $etape, 5, _T('info_sauvegarde_documents'));

$query = "SELECT * FROM spip_mots";
export_objets($query, "mot", $f, $gz, $etape, 6, _T('info_sauvegarde_mots_cles'));

$query = "SELECT * FROM spip_groupes_mots";
export_objets($query, "groupe_mots", $f, $gz, $etape, 7, _T('info_sauvegarde_groupe_mots'));

$query = "SELECT * FROM spip_breves".$debug_limit;
export_objets($query, "breve", $f, $gz, $etape, 8, _T('info_sauvegarde_breves'));

//$query = "SELECT * FROM spip_messages";
//export_objets($query, "message", $f, $gz, $etape, 9, _T('info_sauvegarde_messages'));

$query = "SELECT * FROM spip_forum".$debug_limit;
export_objets($query, "forum", $f, $gz, $etape, 9, _T('info_sauvegarde_forums'));

$query = "SELECT * FROM spip_petitions";
export_objets($query, "petition", $f, $gz, $etape, 10, _T('info_sauvegarde_petitions'));

$query = "SELECT * FROM spip_signatures".$debug_limit;
export_objets($query, "signature", $f, $gz, $etape, 11, _T('info_sauvegarde_signatures'));

$query = "SELECT * FROM spip_syndic";
export_objets($query, "syndic", $f, $gz, $etape, 12, _T('info_sauvegarde_sites_references'));

$query = "SELECT * FROM spip_syndic_articles".$debug_limit;
export_objets($query, "syndic_article", $f, $gz, $etape, 13, _T('info_sauvegarde_articles_sites_ref'));

/*$query = "SELECT * FROM spip_visites".$debug_limit;
export_objets($query, "spip_visite", $f, $gz, $etape, 14, _T('info_sauvegarde_visites'));

$query = "SELECT * FROM spip_referers".$debug_limit;
export_objets($query, "spip_referers", $f, $gz, $etape, 15, _T('info_sauvegarde_refers'));
*/

if (!$etape OR $etape == 13){
	$_fputs ($f, build_end_tag("SPIP")."\n");
	echo "<p>"._T('info_sauvegarde_reussi_01')."</b><p>"._T('info_sauvegarde_reussi_02', array('archive' => $archive))." <a href='./'>"._T('info_sauvegarde_reussi_03')."</a> "._T('info_sauvegarde_reussi_04')."\n";
}
else {
	$etape_suivante = $etape + 1;
	if ($debut_limit > 1) echo "<p align='right'> <a href='" . generer_url_ecrire("export_all","reinstall=non&etape=$etape&debut_limit=$debut_limit&gz=$gz") . "'>>>>> "._T('info_etape_suivante')."</a>";
	else echo "<p align='right'> <a href='" . generer_url_ecrire("export_all","reinstall=non&etape=$etape_suivante&gz=$gz") . "'>>>>> "._T('info_etape_suivante')."</a>";
}
install_fin_html();

if ($gz) gzclose($f);
else fclose($f);

if (!$etape OR $etape == 14) fin_admin($action);
}

?>
