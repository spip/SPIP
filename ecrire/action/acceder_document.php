<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
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

// http://doc.spip.org/@action_acceder_document_dist
function action_acceder_document_dist()
{
  global $auteur_session; // positionne par verifier_visiteur dans inc_version
  if ($auteur_session['statut'] == '0minirezo' 
      OR $auteur_session['statut'] == '1comite') 
	      $auth_login = $auteur_session['login'];
  else $auth_login = "";

    $file = rawurldecode(_request('file'));
    $arg = rawurldecode(_request('arg'));

    $refus = $dcc = false;
    if (strpos($file,'../') !== false)
      $refus = 1;
    else
  {
    if (!$arg) {
      $arg =spip_query("SELECT id_document, descriptif FROM spip_documents AS documents WHERE documents.fichier=" . _q(set_spip_doc($file)));
      $arg = spip_fetch_array($arg);
      if (!$arg) $refus = 2;
      $dcc = $arg['descriptif'];
      $arg = $arg['id_document'];
    } else {
      $arg = intval($arg);
      $file = spip_query("SELECT fichier, descriptif FROM spip_documents AS documents WHERE id_document='". $arg ."'");
      $file = spip_fetch_array($file);
      if (!$file) $refus = 3;
      $dcc = $file['descriptif'];
      $file = get_spip_doc($file['fichier']);
    }
  }

    // Si le document existe et que le visiteur n'est pas redacteur
    // chercher un objet publié le referencant
if (!$refus AND !$auth_login) { 
  $n = spip_num_rows(spip_query("SELECT articles.id_article FROM spip_documents_articles AS rel_articles, spip_articles AS articles WHERE rel_articles.id_article = articles.id_article AND articles.statut = 'publie' AND rel_articles.id_document = $arg  LIMIT 1"));
  if (!$n) {
    $n = spip_num_rows(spip_query("SELECT rubriques.id_rubrique FROM spip_documents_rubriques AS rel_rubriques, spip_rubriques AS rubriques WHERE rel_rubriques.id_rubrique = rubriques.id_rubrique AND rubriques.statut = 'publie' AND rel_rubriques.id_document =  $arg  LIMIT 1"));
    if (!$n) {
      $n =spip_num_rows(spip_query("SELECT breves.id_breve FROM spip_documents_breves AS rel_breves, spip_breves AS breves WHERE rel_breves.id_breve = breves.id_breve AND breves.statut = 'publie' AND rel_breves.id_document =  $arg  LIMIT 1"));
      if (!$n)
	$refus = 4; } } }

  if (is_int($refus)) {
    spip_log("Acces refuse (erreur $refus) au document " . $arg . ': ' . $file);
    redirige_par_entete('./?page=404');
  }
  else
    {
      if (!function_exists('mime_content_type')) {
// http://doc.spip.org/@mime_content_type
	function mime_content_type($f) {preg_match("/\.(\w+)/",$f,$r); return $r[1];}
 }
      $ct = mime_content_type($file);
      $cl = filesize($file);
      $filename = basename($file);
      header("Content-Type: ". $ct);
      header("Content-Disposition: attachment; filename=\"". $filename ."\";");
      if ($dcc) header("Content-Description: " . $dcc);
      if ($cl) header("Content-Length: ". $cl);

      header("Content-Transfer-Encoding: binary");
      readfile($file);
    }
}

?>
