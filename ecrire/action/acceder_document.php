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

include_spip('inc/headers');
include_spip('inc/autoriser');

// acces aux documents joints securise
// verifie soit que le demandeur est authentifie
// soit que le document est publie, c'est-a-dire
// joint a au moins 1 article, breve ou rubrique publie

// http://doc.spip.org/@action_acceder_document_dist
function action_acceder_document_dist() {
	include_spip('inc/documents');

	// $file exige pour eviter le scan id_document par id_document
	$file = rawurldecode(_request('file'));
	$file = get_spip_doc($file);
	$arg = rawurldecode(_request('arg'));

	$status = $dcc = false;
	if (strpos($file,'../') !== false
	OR preg_match(',^\w+://,', $file)) {
		$status = 403;
	}
	else if (!file_exists($file) OR !is_readable($file)) {
		$status = 404;
	} else {
		$where = "documents.fichier="._q(set_spip_doc($file))
		. ($arg ? " AND documents.id_document=".intval($arg): '');

		$s = spip_query("SELECT documents.id_document, documents.titre, documents.descriptif, documents.distant, documents.fichier, types.mime_type FROM spip_documents AS documents LEFT JOIN spip_types_documents AS types ON documents.extension=types.extension WHERE ".$where);
		if (!$doc = spip_abstract_fetch($s)) {
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
			//
			if (!autoriser('voir', 'document', $doc['id_document']))
				$status = 403;
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
		// Content-Type ; pour les images ne pas passer en attachment
		// sinon, lorsqu'on pointe directement sur leur adresse,
		// le navigateur les downloade au lieu de les afficher
		header("Content-Type: ". $doc['mime_type']);

		if (!preg_match(',^image/,', $doc['mime_type'])) {
			header("Content-Disposition: attachment; filename=\""
				. basename($file) ."\";");
			header("Content-Transfer-Encoding: binary");
		}

		if ($cl = filesize($file))
			header("Content-Length: ". $cl);

		readfile($file);
		break;
	}

}

?>
