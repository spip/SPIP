<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2012                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/headers');

// acces aux documents joints securises
// verifie que le document est publie, c'est-a-dire
// joint a au moins 1 article, breve ou rubrique publie

// http://doc.spip.org/@action_acceder_document_dist
function action_acceder_document_dist() {
	include_spip('inc/documents');

	// $file exige pour eviter le scan id_document par id_document
	$f = rawurldecode(_request('file'));
	$file = get_spip_doc($f);
	$arg = rawurldecode(_request('arg'));

	$status = $dcc = false;
	if (strpos($f,'../') !== false
	OR preg_match(',^\w+://,', $f)) {
		$status = 403;
	}
	else if (!file_exists($file) OR !is_readable($file)) {
		$status = 404;
	} else {
		$path = set_spip_doc($file);
		$path2 = generer_acceder_document($f, $arg);
		$where = "(documents.fichier=".sql_quote($path)
		  . ' OR documents.fichier=' . sql_quote($path2) . ')'
		  . ($arg ? (" AND documents.id_document=".intval($arg)) : '');

		$doc = sql_fetsel("documents.id_document, documents.titre, documents.fichier, types.mime_type, types.inclus, documents.extension", "spip_documents AS documents LEFT JOIN spip_types_documents AS types ON documents.extension=types.extension",$where);
		if (!$doc) {
			$status = 404;
		} else {

			// ETag pour gerer le status 304
			$ETag = md5($file . ': '. filemtime($file));
			if (isset($_SERVER['HTTP_IF_NONE_MATCH'])
			AND $_SERVER['HTTP_IF_NONE_MATCH'] == $ETag) {
				http_status(304); // Not modified
				exit;
			} else {
				header('ETag: '.$ETag);
			}

			//
			// Verifier les droits de lecture du document
			// en controlant la cle passee en argument
			//
			include_spip('inc/securiser_action');
			$cle = _request('cle');
			if (!verifier_cle_action($doc['id_document'].','.$f, $cle)) {
				spip_log("acces interdit $cle erronee");
				$status = 403;
			}
		}
	}

	switch($status) {

	case 403:
		include_spip('inc/minipres');
		echo minipres();
		break;

	case 404:
		http_status(404);
		include_spip('inc/minipres');
		echo minipres(_T('erreur').' 404',
			_T('info_document_indisponible'));
		break;

	default:
		header("Content-Type: ". $doc['mime_type']);

		// Si le fichier a un titre avec extension,
		// ou si c'est un nom bien connu d'Unix, le prendre
		// sinon l'ignorer car certains navigateurs pataugent

		$f = basename($file);
		if (isset($doc['titre'])
		AND (preg_match('/^\w+[.]\w+$/', $doc['titre']) OR $doc['titre'] == 'Makefile'))
				$f = $doc['titre'];

		$f = "filename=\"$f\"";

		// Pour les document affichables par les navigateurs,
		// ne pas envoyer "Content-Disposition: attachment" sinon 
		// le navigateur cree un fichier au lieu de l'afficher.
		// Mais la propriete "affichable" n'est pas toujours devinable,
		// il faut quand meme donner un nom au fichier eventuel. 
		// Celui-ci est malheureusement souvent ignore, cf
		// http://greenbytes.de/tech/tc2231/

		if ($doc['inclus']!=='non') {
			header("Content-Disposition: inline; $f");
		} else {

			header("Content-Disposition: attachment; $f;");

			// ce content-type est necessaire
			// pour eviter des corruptions de zip dans ie6
			header('Content-Type: application/octet-stream');
			header("Content-Transfer-Encoding: binary");

			// fix for IE catching or PHP bug issue
			header("Pragma: public");
			header("Expires: 0"); // set expiration time
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

		}

		if ($cl = filesize($file))
			header("Content-Length: ". $cl);

		readfile($file);
		break;
	}

}

?>
