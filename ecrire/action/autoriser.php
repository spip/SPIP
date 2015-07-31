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

include_spip('inc/charsets');	# pour le nom de fichier
include_spip('base/abstract_sql');

//  acces aux documents joints securise
//  est appelee avec arg comme parametre CGI
//  mais peu aussi etre appele avec le parametre file directement 
//  il verifie soit que le demandeur est authentifie
// soit que le fichier est joint a au moins 1 article, breve ou rubrique publie

function action_autoriser_dist()
{
  global $file, $arg, $toujours;

  $file = rawurldecode($file);

  $refus = false;
  if (strpos($file,'../') !== false)
    $refus = 1;
  else
  {
    if (!$arg) {
      $arg = spip_fetch_array(spip_query("SELECT id_document FROM spip_documents AS documents WHERE documents.fichier=" . spip_abstract_quote($file)));

      if (!$arg) $refus = 2;
      $arg = $arg['id_document'];
    } else {
      $arg = intval($arg);
      $file = spip_fetch_array(spip_query("SELECT fichier FROM spip_documents AS documents WHERE id_document='". $arg ."'"));

      if (!$file) $refus = 3;
      $file = $file['fichier'];
    }
  }
  spip_log("arg $arg $auth_login");
if (!$auth_login && !$refus) { 
  $n = spip_num_rows(spip_query("SELECT articles.id_article FROM spip_documents_articles AS rel_articles, spip_articles AS articles WHERE rel_articles.id_article = articles.id_article AND articles.statut = 'publie' AND rel_articles.id_document = $arg  LIMIT 1"));
  if (!$n) {
    $n = spip_num_rows(spip_query("SELECT rubriques.id_rubrique FROM spip_documents_rubriques AS rel_rubriques, spip_rubriques AS rubriques WHERE rel_rubriques.id_rubrique = rubriques.id_rubrique AND rubriques.statut = 'publie' AND rel_rubriques.id_document =  $arg  LIMIT 1"));
    if (!$n) {
      $n =spip_num_rows(spip_query("SELECT breves.id_breve FROM spip_documents_breves AS rel_breves, spip_breves AS breves WHERE rel_breves.id_breve = breves.id_breve AND breves.statut = 'publie' AND rel_breves.id_document =  $arg  LIMIT 1"));
      if (!$n)
	$refus = 4; } } }

  if (is_int($refus)) {
    spip_log("Acces refuse ($refus) au document " . $arg . ': ' . $file);
    global $fond;
    $fond = 404;
    include _DIR_INCLUDE.'public.php';
  }
  else
    {
      if (!function_exists('mime_content_type')) {
	function mime_content_type($f) {preg_match("/\.(\w+)/",$f,$r); return $r[1];}
 }
      spip_log("envoi $file");
      $ct = mime_content_type($file);
      $cl = filesize($file);
      $filename = basename($file);
      header("Content-Type: ". $ct);
      header("Content-Disposition: attachment; filename=\"". $filename ."\";");
      if ($file) header("Content-Description: " . $dcc);
      if ($cl) header("Content-Length: ". $cl);

      header("Content-Transfer-Encoding: binary");
      readfile($file);
    }
}

?>
