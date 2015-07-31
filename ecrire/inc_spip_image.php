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


//
// Fonctions invoquees par spip_image.php3 en fonction du parametre "action"
//  Les globales sont les variables CGI.

//
if (!defined("_ECRIRE_INC_VERSION")) return;

// faudrait ne charger qu'a bon escient

include_ecrire('inc_getdocument');	# diverses fonctions de ce fichier
include_ecrire("inc_charsets");	# pour le nom de fichier
include_ecrire("inc_meta");	# ne pas faire confiance au cache
					# (alea_ephemere a peut-etre change)
include_ecrire("inc_session");	# verifier_action_auteur
include_ecrire("inc_abstract_sql");# spip_insert / spip_fetch...
include_ecrire('inc_documents');	# fichiers_upload()


function spip_image_joindre_dist($doc)
{
  global 
    $sousaction1,
    $sousaction2,
    $sousaction3,
    $sousaction4,
    $sousaction5,
    $action, $hash, $hash_id_auteur,
    $url, $chemin, $ancre, $type, $id_article, $id_document,  $redirect,
    $_FILES,  $HTTP_POST_FILES;

  if (!verifier_action_auteur("$action $doc", $hash, $hash_id_auteur))
		die ($action . '!!!');

     // pas terrible, mais c'est le pb du bouton Submit qui retourne son texte,
     // et son transcodage est couteux et perilleux
     $action = 'spip_image_joindre' .
       ($sousaction1 ? 1 :
	($sousaction2 ? 2 :
	 ($sousaction3 ? 3 : 
	  ($sousaction4 ? 4 :
	   $sousaction5 ))));

     $arg = ($sousaction1 ? ($_FILES ? $_FILES : $HTTP_POST_FILES) :
	     ($sousaction2 ? $url : $chemin));

     $documents_actifs = array();

     if (function_exists($action))
       $action($arg, $doc, $type, $id_article, $id_document, 
	       $hash, $hash_id_auteur, $redirect, $documents_actifs);

     else spip_log("spip_image: action inconnue $action");

     $link = new Link(_DIR_RESTREINT_ABS . $redirect);
     if ($documents_actifs) {
	$link->addVar('show_docs',join('-',$documents_actifs));
     }
     redirige_par_entete($link->getUrl($ancre));
}


// Cas d'un document distant reference sur internet

function spip_image_joindre2($arg, $mode, $type, $id, $id_document,$hash, $hash_id_auteur, $redirect, &$actifs)
{
	examiner_les_fichiers(array(
				   array('name' => basename($arg),
					 'tmp_name' => $arg)
				   ), 'distant', $type, $id, $id_document,
			     $hash, $hash_id_auteur, $redirect, $actifs);
}

// Cas d'un fichier transmis

function spip_image_joindre1($arg, $mode, $type, $id, $id_document,$hash, $hash_id_auteur, $redirect, &$actifs)
{
	$files = array();
	if (is_array($arg))
	  foreach ($arg as $file) {
		if (!$file['error'] == 4 /* UPLOAD_ERR_NO_FILE */)
			$files[]=$file;
	}
	examiner_les_fichiers($files, $mode, $type, $id, $id_document,
			     $hash, $hash_id_auteur, $redirect, $actifs);
} 

// copie de tout ou partie du repertoire upload

function spip_image_joindre3($arg, $mode, $type, $id, $id_document,$hash, $hash_id_auteur, $redirect, &$actifs)
{
	if (!$arg || strstr($arg, '..')) return;
	    
	$upload = (_DIR_TRANSFERT .$arg);

	if (!is_dir($upload))
	  // seul un fichier est demande
	  $files = array(array ('name' => basename($upload),
				'tmp_name' => $upload)
			 );
	else {
	  $files = array();
	  foreach (fichiers_upload($upload) as $fichier) {
			$files[]= array (
					'name' => basename($fichier),
					'tmp_name' => $fichier
					);
	  }
	}

	examiner_les_fichiers($files, $mode, $type, $id, $id_document,
			     $hash, $hash_id_auteur, $redirect, $actifs);
}

//  identifie les repertoires de upload aux rubriques Spip

function spip_image_joindre4($arg, $mode, $type, $id, $id_document, $hash, $hash_id_auteur, $redirect, &$documents_actifs)
{
	if (!$arg || strstr($arg, '..')) return;
	$upload = (_DIR_TRANSFERT .$arg);
	identifie_repertoire_et_rubrique($upload, $id, $hash_id_auteur);
	include_ecrire("inc_rubriques");
	calculer_rubriques();
}

//  Zip avec confirmation "tel quel"

function spip_image_joindre5($arg, $mode, $type, $id, $id_document,$hash, $hash_id_auteur, $redirect, &$actifs)
{
  	ajouter_un_document($arg, basename($arg), $type, $id, $mode, $id_document, $actifs);
}

// cas du zip a deballer. On ressort la bibli 

function spip_image_joindre6($arg, $mode, $type, $id, $id_document,$hash, $hash_id_auteur, $redirect, &$actifs)
{
	    define('_tmp_dir', creer_repertoire_documents($hash));
	    if (_tmp_dir == _DIR_DOC) die(_L('Op&eacute;ration impossible'));
	    require_once(_DIR_RESTREINT . 'pclzip.lib.php');
	    $archive = new PclZip($arg);
	    $archive->extract(
			      PCLZIP_OPT_PATH, _tmp_dir,
			      PCLZIP_CB_PRE_EXTRACT, 'callback_deballe_fichier'
			      );
	    $contenu = verifier_compactes($archive);
	    //  on supprime la copie temporaire
	    @unlink($arg);
	    
	    foreach ($contenu as $fichier)
		ajouter_un_document(_tmp_dir.basename($fichier),
				    basename($fichier),
				    $type, $id, $mode, $id_document, $actifs);
	    effacer_repertoire_temporaire(_tmp_dir);
}

//
// Ajouter un logo
//

// $source = $_FILES[0]
// $dest = arton12.xxx
function spip_image_ajouter_dist($doc) {
	global $sousaction2, $source;
	global $action, $hash, $hash_id_auteur;

	if (!verifier_action_auteur("$action $doc", $hash, $hash_id_auteur))
		die ($action . '!!!');

	if (!$sousaction2) {
		if (!$_FILES) $_FILES = $HTTP_POST_FILES;
		$source = (is_array($_FILES) ? array_pop($_FILES) : "");
	}
	if ($source) {
		$f =_DIR_DOC . $doc . '.tmp';

		if (!is_array($source)) 
		// fichier dans upload/
	  		$source = @copy(_DIR_TRANSFERT . $source, $f);
		else {
		// Intercepter une erreur a l'envoi
			if (check_upload_error($source['error']))
				$source ="";
			else
		// analyse le type de l'image (on ne fait pas confiance au nom de
		// fichier envoye par le browser : pour les Macs c'est plus sur)

				$source = deplacer_fichier_upload($source['tmp_name'], $f);
		}
	}
	if (!$source)
		spip_log("pb de copie pour $f");
	else {

		$size = @getimagesize($f);
		$type = decoder_type_image($size[2], true);

		if ($type) {
			$poids = filesize($f);
			if (_LOGO_MAX_SIZE > 0
			AND $poids > _LOGO_MAX_SIZE*1024) {
				@unlink ($f);
				check_upload_error(6,
				_T('info_logo_max_poids',
					array('maxi' => taille_en_octets(_LOGO_MAX_SIZE*1024),
					'actuel' => taille_en_octets($poids))));
			}

			if (_LOGO_MAX_WIDTH * _LOGO_MAX_HEIGHT
			AND ($size[0] > _LOGO_MAX_WIDTH
			OR $size[1] > _LOGO_MAX_HEIGHT)) {
				@unlink ($f);
				check_upload_error(6, 
				_T('info_logo_max_taille',
					array(
					'maxi' =>
						_T('info_largeur_vignette',
							array('largeur_vignette' => _LOGO_MAX_WIDTH,
							'hauteur_vignette' => _LOGO_MAX_HEIGHT)),
					'actuel' =>
						_T('info_largeur_vignette',
							array('largeur_vignette' => $size[0],
							'hauteur_vignette' => $size[1]))
				)));
			}
			@rename ($f, _DIR_DOC . $doc . ".$type");
		}
		else {
			@unlink ($f);
			check_upload_error(6,
				_T('info_logo_format_interdit',
				array ('formats' => 'GIF, JPG, PNG'))
			);
		}
	
	}
	$link = new Link(_DIR_RESTREINT_ABS . $GLOBALS['redirect']);
	redirige_par_entete($link->getUrl($GLOBALS['ancre']));
}


function spip_image_effacer_dist($doc) {
	global $action, $hash, $hash_id_auteur;
	if (!verifier_action_auteur("$action $doc", $hash, $hash_id_auteur))
		die ($action . '!!!');

	if (!strstr($doc, ".."))
		@unlink(_DIR_IMG . $doc);
	$link = new Link(_DIR_RESTREINT_ABS . $GLOBALS['redirect']);
	redirige_par_entete($link->getUrl($GLOBALS['ancre']));
}

//
// Creation automatique de vignette
//

// Tester nos capacites
function spip_image_tester_dist($test_vignette) {
	global $pnmscale_command;

	// verifier les formats acceptes par GD
	if ($test_vignette == "gd1") {
		// Si GD est installe et php >= 4.0.2
		if (function_exists('imagetypes')) {

			if (imagetypes() & IMG_GIF) {
				$gd_formats[] = "gif";
			} else {
				# Attention GD sait lire le gif mais pas forcement l'ecrire
				if (function_exists('ImageCreateFromGIF')) {
					$srcImage = @ImageCreateFromGIF(_DIR_IMG . "test.gif");
					if ($srcImage) {
						$gd_formats_read_gif = ",gif";
						ImageDestroy( $srcImage );
					}
				}
			}

			if (imagetypes() & IMG_JPG)
				$gd_formats[] = "jpg";
			if (imagetypes() & IMG_PNG)
				$gd_formats[] = "png";
		}

		else {	# ancienne methode de detection des formats, qui en plus
				# est bugguee car elle teste les formats en lecture
				# alors que la valeur deduite sert a identifier
				# les formats disponibles en ecriture... (cf. inc_logos.php3)
		
			$gd_formats = Array();
			if (function_exists('ImageCreateFromJPEG')) {
				$srcImage = @ImageCreateFromJPEG(_DIR_IMG . "test.jpg");
				if ($srcImage) {
					$gd_formats[] = "jpg";
					ImageDestroy( $srcImage );
				}
			}
			if (function_exists('ImageCreateFromGIF')) {
				$srcImage = @ImageCreateFromGIF(_DIR_IMG . "test.gif");
				if ($srcImage) {
					$gd_formats[] = "gif";
					ImageDestroy( $srcImage );
				}
			}
			if (function_exists('ImageCreateFromPNG')) {
				$srcImage = @ImageCreateFromPNG(_DIR_IMG . "test.png");
				if ($srcImage) {
					$gd_formats[] = "png";
					ImageDestroy( $srcImage );
				}
			}
		}

		if ($gd_formats) $gd_formats = join(",", $gd_formats);
		ecrire_meta("gd_formats_read", $gd_formats.$gd_formats_read_gif);
		ecrire_meta("gd_formats", $gd_formats);
		ecrire_metas();
	}

	// verifier les formats netpbm
	else if ($test_vignette == "netpbm"
	AND $pnmscale_command) {
		$netpbm_formats= Array();

		$jpegtopnm_command = str_replace("pnmscale",
			"jpegtopnm", $pnmscale_command);
		$pnmtojpeg_command = str_replace("pnmscale",
			"pnmtojpeg", $pnmscale_command);

		$vignette = _DIR_IMG . "test.jpg";
		$dest = _DIR_IMG . "test-jpg.jpg";
		$commande = "$jpegtopnm_command $vignette | $pnmscale_command -width 10 | $pnmtojpeg_command > $dest";
		spip_log($commande);
		exec($commande);
		if ($taille = @getimagesize($dest)) {
			if ($taille[1] == 10) $netpbm_formats[] = "jpg";
		}
		$giftopnm_command = ereg_replace("pnmscale", "giftopnm", $pnmscale_command);
		$pnmtojpeg_command = ereg_replace("pnmscale", "pnmtojpeg", $pnmscale_command);
		$vignette = _DIR_IMG . "test.gif";
		$dest = _DIR_IMG . "test-gif.jpg";
		$commande = "$giftopnm_command $vignette | $pnmscale_command -width 10 | $pnmtojpeg_command > $dest";
		spip_log($commande);
		exec($commande);
		if ($taille = @getimagesize($dest)) {
			if ($taille[1] == 10) $netpbm_formats[] = "gif";
		}

		$pngtopnm_command = ereg_replace("pnmscale", "pngtopnm", $pnmscale_command);
		$vignette = _DIR_IMG . "test.png";
		$dest = _DIR_IMG . "test-gif.jpg";
		$commande = "$pngtopnm_command $vignette | $pnmscale_command -width 10 | $pnmtojpeg_command > $dest";
		spip_log($commande);
		exec($commande);
		if ($taille = @getimagesize($dest)) {
			if ($taille[1] == 10) $netpbm_formats[] = "png";
		}
		

		if ($netpbm_formats)
			$netpbm_formats = join(",", $netpbm_formats);
		else
			$netpbm_formats = '';
		ecrire_meta("netpbm_formats", $netpbm_formats);
		ecrire_metas();
	}

	// et maintenant envoyer la vignette de tests
	if (ereg("^(gd1|gd2|imagick|convert|netpbm)$", $test_vignette)) {
		include_ecrire('inc_logos');
		//$taille_preview = $GLOBALS['meta']["taille_preview"];
		if ($taille_preview < 10) $taille_preview = 150;
		if ($preview = creer_vignette(_DIR_IMG . 'test_image.jpg', $taille_preview, $taille_preview, 'jpg', '', "test_$test_vignette", $test_vignette, true))

			redirige_par_entete($preview['fichier']);
	}

	# image echec
	redirige_par_entete(_DIR_IMG_PACK . 'puce-rouge-anim.gif');
}


// Effacer un doc (et sa vignette)
function spip_image_supprimer_dist($doc) {

	global $action, $hash, $hash_id_auteur;
	if (!verifier_action_auteur("$action $doc", $hash, $hash_id_auteur))
		die ($action . '!!!');

	$result = spip_query("SELECT id_vignette, fichier
		FROM spip_documents
		WHERE id_document=$doc");
	if ($row = spip_fetch_array($result)) {
		$fichier = $row['fichier'];
		$id_vignette = $row['id_vignette'];
		spip_query("DELETE FROM spip_documents
			WHERE id_document=$doc");
		spip_query("UPDATE spip_documents SET id_vignette=0
			WHERE id_vignette=$doc");
		spip_query("DELETE FROM spip_documents_articles
			WHERE id_document=$doc");
		spip_query("DELETE FROM spip_documents_rubriques
			WHERE id_document=$doc");
		spip_query("DELETE FROM spip_documents_breves
			WHERE id_document=$doc");
		@unlink($fichier);

		if ($id_vignette > 0) {
			$query = "SELECT id_vignette, fichier FROM spip_documents
				WHERE id_document=$id_vignette";
			$result = spip_query($query);
			if ($row = spip_fetch_array($result)) {
				$fichier = $row['fichier'];
				@unlink($fichier);
			}
			spip_query("DELETE FROM spip_documents
				WHERE id_document=$id_vignette");
			spip_query("DELETE FROM spip_documents_articles
				WHERE id_document=$id_vignette");
			spip_query("DELETE FROM spip_documents_rubriques
				WHERE id_document=$id_vignette");
			spip_query("DELETE FROM spip_documents_breves
				WHERE id_document=$id_vignette");
		}
	}
	$link = new Link(_DIR_RESTREINT_ABS . $GLOBALS['redirect']);
	redirige_par_entete($link->getUrl($GLOBALS['ancre']));
}


function spip_image_tourner_dist($doc) {
	
	global $action, $hash, $hash_id_auteur;
	if (!verifier_action_auteur("$action $doc", $hash, $hash_id_auteur))
		die ($action . '!!!');

	global $var_rot, $convert_command;
	$var_rot = intval($var_rot);

	$query = "SELECT id_vignette, fichier FROM spip_documents WHERE id_document=$doc";
	$result = spip_query($query);
	if ($row = spip_fetch_array($result)) {
		$id_vignette = $row['id_vignette'];
		$image = $row['fichier'];

		$process = $GLOBALS['meta']['image_process'];

		 // imagick (php4-imagemagick)
		 if ($process == 'imagick') {
			$handle = imagick_readimage($image);
			imagick_rotate($handle, $var_rot);
			imagick_write($handle, $image);
			if (!@file_exists($image)) return;	// echec imagick
		}
		else if ($process == "gd2") { // theoriquement compatible gd1, mais trop forte degradation d'image
			if ($var_rot == 180) { // 180 = 90+90
				gdRotate ($image, 90);
				gdRotate ($image, 90);
			} else {
				gdRotate ($image, $var_rot);
			}
		}
		else if ($process = "convert") {
			$commande = "$convert_command -rotate $var_rot ./"
				. escapeshellcmd($image).' ./'.escapeshellcmd($image);
#			spip_log($commande);
			exec($commande);
		}

		$size_image = @getimagesize($image);
		$largeur = $size_image[0];
		$hauteur = $size_image[1];

/*
	A DESACTIVER PEUT-ETRE ? QUE SE PASSE--IL SI JE TOURNE UNE IMAGE AYANT UNE VGNETTE "MANUELLE" -> NE PAS CREER DE VIGNETTE TOURNEE -- EN VERITE IL NE FAUT PAS PERMETTRE DE TOURNER UNE IMAGE AYANT UNE VIGNETTE MANUELLE
		if ($id_vignette > 0) {
			creer_fichier_vignette($image);
		}
*/

		spip_query("UPDATE spip_documents SET largeur=$largeur, hauteur=$hauteur WHERE id_document=$doc");

	}
	$link = new Link(_DIR_RESTREINT_ABS . $GLOBALS['redirect']);
	redirige_par_entete($link->getUrl($GLOBALS['ancre']));
}


//  acces aux documents joints securise
//  est appelee avec id_document comme parametre CGI
//  mais peu aussi etre appele avec le parametre file directement 
//  il verifie soit que le demandeur est authentifie
// soit que le fichier est joint a au moins 1 article, breve ou rubrique publie

function spip_image_autoriser_dist($id_document)
{
  global $file;

  $file = urldecode($file);

  $refus = false;
  if (strpos($file,'../') !== false)
    $refus = 1;
  else
  {
    if ($cookie_session = $_COOKIE['spip_session']) 
      {
	include_ecrire("inc_session");
	global $auteur_session;

	if (verifier_session($cookie_session)) 
	  {

	    if ($auteur_session['statut'] == '0minirezo' 
		OR $auteur_session['statut'] == '1comite') 
	      $auth_login = $auteur_session['login'];
	  }
      }

    if (!$id_document) {
      $id_document = spip_query("select id_document from spip_documents as documents where documents.fichier='".$file."'");
	$id_document = spip_fetch_array($id_document);
      if (!$id_document) $refus = 2;
      $id_document = $id_document['id_document'];
    } else {
      $file = spip_query("select fichier from spip_documents as documents where id_document='". $id_document ."'");
      $file = spip_fetch_array($file);
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

  if (is_int($refus)) {
    spip_log("Acces refuse ($refus) au document " . $id_document . ': ' . $file);
  $fond = 404;
  include("inc-public.php3");
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

// pour envoyer un article proprement
// spip_image.php3?action=telecharger&doc=$id_article

function spip_image_telecharger_dist($id_article)
{
  $r = spip_query("
SELECT	texte, soustitre, titre, date
FROM	spip_articles
WHERE	id_article=" . $id_article
				 );
  $r = spip_fetch_array($r);
  if (!$r)
    return 0;
  else
    {
      $titre = $r['titre'];
      $text =ereg_replace("^<code>[[:space:]]*",'',
			  ereg_replace('</code>$','',$r['texte']));
      header("Content-Type: text/plain; charset='iso-8859-1'");
      if ($titre) header("Content-Description: $titre");
      header("Content-Disposition: attachment; filename=" .
            ($r['soustitre'] ? $r['soustitre'] : ($id_article . ".txt")) .
             ";" );
      header("Content-Length: ". strlen($text)+1);
      print $text;
    }
}
     
?>
