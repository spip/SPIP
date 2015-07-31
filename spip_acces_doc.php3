<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

# script d'acces aux documents joints
# doit etre appele avec un de ces 2 parametres de GET:
# - id_document 
# - file 
# il verifie soit que le demandeur est authentifie
# soit que le fichier est joint à au moins 1 article, breve ou rubrique

$id_document = intval($_GET['id_document']);
$file = urldecode($_GET['file']);
if (strpos($file,'../') !== false)
  $refus = 1;
else
  {
    $refus = false;
    include ("ecrire/inc_version.php3");
    include_local(_FILE_CONNECT);
    include_ecrire("inc_meta.php3");
    include_ecrire("inc_session.php3");

    global $auteur_session;
    if ($cookie_session = $_COOKIE['spip_session']) 
      {
	if (verifier_session($cookie_session)) 
	  {
	    if ($auteur_session['statut'] == '0minirezo' 
		OR $auteur_session['statut'] == '1comite') 
	      $auth_login = $auteur_session['login'];
	  }
      }

    if (!$id_document) {
      $id_document = @spip_fetch_array(spip_query("select id_document from spip_documents as documents where documents.fichier='".addslashes($file)."'"));
      if (!$id_document) $refus = 2;
      $id_document = $id_document['id_document'];
    } else {
      $file = @spip_fetch_array(spip_query("select fichier from spip_documents as documents where id_document='". $id_document ."'"));
      if (!$file) $refus = 3;
      $file = $file['fichier'];
    }
  }

if (!$auth_login && !$refus) { 
    if (!spip_num_rows(spip_query("select articles.id_article
from spip_documents_articles as rel_articles, spip_articles as articles 
where rel_articles.id_article = articles.id_article AND
articles.statut = 'publie' AND rel_articles.id_document ='".
			       $id_document .
				"' LIMIT 1"))) {
      if (!spip_num_rows(spip_query("select rubriques.id_rubrique
from spip_documents_rubriques as rel_rubriques, spip_rubriques as rubriques 
where rel_rubriques.id_rubrique = rubriques.id_rubrique AND
rubriques.statut = 'publie' AND rel_rubriques.id_document ='".
			       $id_document .
				  "' LIMIT 1"))) {
	if (!spip_num_rows(spip_query("select breves.id_breve
from spip_documents_breves as rel_breves, spip_breves as breves 
where rel_breves.id_breve = breves.id_breve AND
breves.statut = 'publie' AND rel_breves.id_document ='".
			       $id_document .
				  "' LIMIT 1")))
	  $refus = 4; } } }

if (!$refus)
  {
     header("Content-Type: ". mime_content_type($file));
     header("Content-Length: ". filesize($file));
     header("Content-Disposition: attachment; filename=\"". basename($file) ."\";");
     header("Content-Transfer-Encoding: binary");
     readfile($file);
   }
 else
   spip_log("Acces refuse ($refus) au document " . ($_GET['id_document']) . ': ' .($_GET['file']));

?>