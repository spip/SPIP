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

// acces aux documents joints securise
// verifie soit que le demandeur est authentifie
// soit que le document est publie, c'est-a-dire
// joint a au moins 1 article, breve ou rubrique publie

// Cette variable de configuration peut etre posee par un plugin
// par exemple acces_restreint

// Definir une fonction d'autorisation specifique
// sauf si on a deja eu cette idee
// TODO: ne devrait pas figurer dans ce fichier
if ($GLOBALS['meta']["creer_htaccess"] == 'oui'
AND !function_exists('autoriser_document_voir')) {

// http://doc.spip.org/@autoriser_document_voir
function autoriser_document_voir($faire, $type, $id, $qui, $opt) {
	if (autoriser('ecrire'))
		return true;

	return
		spip_num_rows(spip_query("SELECT articles.id_article FROM spip_documents_articles AS rel_articles, spip_articles AS articles WHERE rel_articles.id_article = articles.id_article AND articles.statut = 'publie' AND rel_articles.id_document = $id  LIMIT 1")) > 0
	OR
		spip_num_rows(spip_query("SELECT rubriques.id_rubrique FROM spip_documents_rubriques AS rel_rubriques, spip_rubriques AS rubriques WHERE rel_rubriques.id_rubrique = rubriques.id_rubrique AND rubriques.statut = 'publie' AND rel_rubriques.id_document = $id LIMIT 1")) > 0
	OR
		spip_num_rows(spip_query("SELECT breves.id_breve FROM spip_documents_breves AS rel_breves, spip_breves AS breves WHERE rel_breves.id_breve = breves.id_breve AND breves.statut = 'publie' AND rel_breves.id_document = $id_document  LIMIT 1")) > 0
	;
}
}

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
		if (!$doc = spip_fetch_array($s)) {
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
		echo minipres(_L('Erreur').' 404',
			_L('Ce document n\'est pas disponible sur le site.'));
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
