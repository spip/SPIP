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

$GLOBALS['version_archive'] = '1.2';

include_spip('inc/admin');

function exec_export_all_dist()
{
  global $archive, $debut_limit, $etape, $gz, $spip_version, $spip_version_affichee, $version_archive;

if (!$archive) {
	if ($gz) $archive = "dump.xml.gz";
	else $archive = "dump.xml";
}

$action = _T('info_exportation_base', array('archive' => $archive));

debut_admin(generer_url_post_ecrire("export_all","archive=$archive&gz=$gz"), $action);

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


//
// Exportation generique d'objets (fichier ou retour de fonction)
//
function export_objets($query, $type, $file = 0, $gz = false, $etape_en_cours="", $etape_actuelle="", $nom_etape="") {
	global $debut_limit;
	if ($etape_en_cours < 1 OR $etape_en_cours == $etape_actuelle){
		if ($etape_en_cours > 0) {
			echo "<li><b>$nom_etape</b>";
		}
	
		$result = spip_query($query);

		if ($etape_en_cours > 0){
			if ($type == "forum"){
				$total = spip_num_rows($result);
				if ($total > 5000){
					$result = spip_query($query.
#" LIMIT  5000 OFFSET $debut_limit" # PG
" LIMIT  $debut_limit, 5000"
							     );
					$debut_limit = $debut_limit + 5000;
					if ($debut_limit > $total) {
						$debut_limit = 0;
						echo " "._T('info_tous_resultats_enregistres');
					}
					else {
						echo " "._T('info_premier_resultat', array('debut_limit' => $debut_limit, 'total' => $total));
					}
				} 
				else {
					$debut_limit = 0;
				}
			}
			if ($type == "article"){
				$total = spip_num_rows($result);
				if ($total > 500){
					$result = spip_query($query. 
#" LIMIT  500 OFFSET $debut_limit" # PG
" LIMIT  $debut_limit, 500"
							     );
					$debut_limit = $debut_limit + 500;
					if ($debut_limit > $total) {
						$debut_limit = 0;
						echo " "._T('info_tous_resultats_enregistres');
					}
					else {
						echo " "._T('info_premier_resultat_sur', array('debut_limit' => $debut_limit, 'total' => $total));
					}
				} 
				else {
					$debut_limit = 0;
				}
			}
		
		}
		
		$_fputs = ($gz) ? gzputs : fputs;
		$nfields = mysql_num_fields($result);
		// Recuperer les noms des champs
		for ($i = 0; $i < $nfields; ++$i) $fields[$i] = mysql_field_name($result, $i);
		while ($row = spip_fetch_array($result)) {
			$string .= build_begin_tag($type) . "\n";
			// Exporter les champs de la table
			for ($i = 0; $i < $nfields; ++$i) {
				$string .= '<'.$fields[$i].'>' . text_to_xml($row[$i]) . '</'.$fields[$i].'>' . "\n";
			}
			// Exporter les relations
			if ($type == 'article') {
				$query = 'SELECT id_auteur FROM spip_auteurs_articles WHERE id_article='.$row[0];
				$res2 = spip_query($query);
				while($row2 = spip_fetch_array($res2)) {
					$string .= '<lien:auteur>' . $row2['id_auteur'] . '</lien:auteur>' . "\n";
				}
				spip_free_result($res2);
				$query = 'SELECT id_document FROM spip_documents_articles WHERE id_article='.$row[0];
				$res2 = spip_query($query);
				while($row2 = spip_fetch_array($res2)) {
					$string .= '<lien:document>' . $row2['id_document'] . '</lien:document>' . "\n";
				}
				spip_free_result($res2);
			}
			else if ($type == 'message') {
				$query = 'SELECT id_auteur FROM spip_auteurs_messages WHERE id_message='.$row[0];
				$res2 = spip_query($query);
				while($row2 = spip_fetch_array($res2)) {
					$string .= '<lien:auteur>' . $row2['id_auteur'] . '</lien:auteur>' . "\n";
				}
				spip_free_result($res2);
			}
			else if ($type == 'breve') {
				$query = 'SELECT id_document FROM spip_documents_breves WHERE id_breve='.$row[0];
				$res2 = spip_query($query);
				while($row2 = spip_fetch_array($res2)) {
					$string .= '<lien:document>' . $row2['id_document'] . '</lien:document>' . "\n";
				}
				spip_free_result($res2);
			}
			else if ($type == 'rubrique') {
				$query = 'SELECT id_document FROM spip_documents_rubriques WHERE id_rubrique='.$row[0];
				$res2 = spip_query($query);
				while($row2 = spip_fetch_array($res2)) {
					$string .= '<lien:document>' . $row2['id_document'] . '</lien:document>' . "\n";
				}
				spip_free_result($res2);
				$query = 'SELECT id_auteur FROM spip_auteurs_rubriques WHERE id_rubrique='.$row[0];
				$res2 = spip_query($query);
				while($row2 = spip_fetch_array($res2)) {
					$string .= '<lien:auteur>' . $row2['id_auteur'] . '</lien:auteur>' . "\n";
				}
				spip_free_result($res2);
			}
			else if ($type == 'auteur') {
				$query = 'SELECT id_rubrique FROM spip_auteurs_rubriques WHERE id_auteur='.$row[0];
				$res2 = spip_query($query);
				while($row2 = spip_fetch_array($res2)) {
					$string .= '<lien:rubrique>' . $row2['id_rubrique'] . '</lien:rubrique>' . "\n";
				}
				spip_free_result($res2);
			}
			else if ($type == 'mot') {
				$query = 'SELECT id_article FROM spip_mots_articles WHERE id_mot='.$row[0];
				$res2 = spip_query($query);
				while($row2 = spip_fetch_array($res2)) {
					$string .= '<lien:article>' . $row2['id_article'] . '</lien:article>' . "\n";
				}
				spip_free_result($res2);
				$query = 'SELECT id_breve FROM spip_mots_breves WHERE id_mot='.$row[0];
				$res2 = spip_query($query);
				while($row2 = spip_fetch_array($res2)) {
					$string .= '<lien:breve>' . $row2['id_breve'] . '</lien:breve>' . "\n";
				}
				spip_free_result($res2);
				$query = 'SELECT id_forum FROM spip_mots_forum WHERE id_mot='.$row[0];
				$res3 = spip_query($query);
				while($row3 = spip_fetch_array($res3)) {
					$string .= '<lien:forum>' . $row3['id_forum'] . '</lien:forum>' . "\n";
				}
				spip_free_result($res3);
				$query = 'SELECT id_rubrique FROM spip_mots_rubriques WHERE id_mot='.$row[0];
				$res4 = spip_query($query);
				while($row4 = spip_fetch_array($res4)) {
					$string .= '<lien:rubrique>' . $row4['id_rubrique'] . '</lien:rubrique>' . "\n";
				}
				spip_free_result($res4);
				$query = 'SELECT id_syndic FROM spip_mots_syndic WHERE id_mot='.$row[0];
				$res4 = spip_query($query);
				while($row4 = spip_fetch_array($res4)) {
					$string .= '<lien:syndic>' . $row4['id_syndic'] . '</lien:syndic>' . "\n";
				}
				spip_free_result($res4);
			}
			$string .= build_end_tag($type) . "\n\n";
			if ($file) {
				$_fputs($file, $string);
				$string = '';
			}
		}
		spip_free_result($result);
		if (!$file) return $string;
	}
	else if ($etape_actuelle < $etape_en_cours) {
		echo "<li> $nom_etape";
	} else {
		echo "<li> <font color='#999999'>$nom_etape</font>";
	}
}

function build_begin_tag($tag) {
	return "<$tag>";
}

function build_end_tag($tag) {
	return "</$tag>";
}

// Conversion texte -> xml (ajout d'entites)
function text_to_xml($string) {
	return str_replace('<', '&lt;', str_replace('&', '&amp;', $string));
}

?>
