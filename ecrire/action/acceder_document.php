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
include_spip('inc/headers');
include_spip('base/abstract_sql');

//  acces aux documents joints securise
//  est appelee avec arg comme parametre CGI
//  mais peu aussi etre appele avec le parametre file directement 
//  il verifie soit que le demandeur est authentifie
//  soit que le document est publie, c'est-a-dire
//  joint a au moins 1 article, breve ou rubrique publie
// TODO : a refaire avec l'API inc/autoriser
// http://doc.spip.org/@action_acceder_document_dist
function action_acceder_document_dist() {
	include_spip('inc/documents');
	global $auteur_session; // positionne par verifier_visiteur dans inc_version
	if ($auteur_session['statut'] == '0minirezo' 
	OR $auteur_session['statut'] == '1comite') 
		$auth_login = $auteur_session['login'];
	else
		$auth_login = "";

	$file = rawurldecode(_request('file'));
	$arg = rawurldecode(_request('arg'));

	$status = $dcc = false;
	if (strpos($file,'../') !== false) {
		$status = 403;
	} else if (!$arg AND $file) {
		$where = "documents.fichier="._q(set_spip_doc($file));
	} else {
		$where = "documents.id_document=".intval($arg);
	}

	if (!$status) {

		$s = spip_query("SELECT documents.id_document, documents.titre, documents.descriptif, documents.distant, documents.fichier, types.mime_type FROM spip_documents AS documents LEFT JOIN spip_types_documents AS types ON documents.id_type=types.id_type WHERE ".$where);
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

			$dcc = $doc['titre']. ($doc['descriptif']
				? ' - '.$doc['descriptif']
				: '');
			$file = get_spip_doc($doc['fichier']);

			// Un document distant ne peut etre protege
			// toutefois si on arrive ici c'est qu'on a hacke
			if ($doc['distant'] == 'oui') {
				http_status(403);
				include_spip('inc/minipres');
				echo minipres(_L('Status').' 403',
					_T('ecrire:avis_acces_interdit'));
				exit;
			}

			// Si le visiteur n'est pas redacteur, chercher un objet publie
			// referencant notre document (old school)
			$id_document = $doc['id_document'];
			if (!$auth_login) {
				$n = spip_num_rows(spip_query("SELECT articles.id_article FROM spip_documents_articles AS rel_articles, spip_articles AS articles WHERE rel_articles.id_article = articles.id_article AND articles.statut = 'publie' AND rel_articles.id_document = $id_document  LIMIT 1"));
				if (!$n) {
					$n = spip_num_rows(spip_query("SELECT rubriques.id_rubrique FROM spip_documents_rubriques AS rel_rubriques, spip_rubriques AS rubriques WHERE rel_rubriques.id_rubrique = rubriques.id_rubrique AND rubriques.statut = 'publie' AND rel_rubriques.id_document = $id_document LIMIT 1"));
					if (!$n) {
						$n =spip_num_rows(spip_query("SELECT breves.id_breve FROM spip_documents_breves AS rel_breves, spip_breves AS breves WHERE rel_breves.id_breve = breves.id_breve AND breves.statut = 'publie' AND rel_breves.id_document = $id_document  LIMIT 1"));
						if (!$n)
							$status = 403;
					}
				}
			}
		}

#		mettre une cle dans l'url (sympa mais ne resoud pas le cas des
#		documents appeles dans les forums ...)
#		if (_request('cle') == md5(...);

		if ($status == 403) {
			spip_log("403: acces refuse (erreur $refus) au document " . $arg . ': ' . $file);
			http_status(403);
			include_spip('inc/minipres');
			echo minipres(_L('Status').' 403',
				_T('ecrire:avis_acces_interdit'));
		} else if ($status == 404
		OR !file_exists($file)
		OR !is_readable($file)) {
			spip_log("404: Le document $file n'existe pas");
			http_status(404);
			include_spip('inc/minipres');
			echo minipres(_L('Erreur').' 404',
				_L('Ce document n\'est pas disponible sur le site.'));
		}
		else {
			header("Content-Type: ". $doc['mime_type']);

			header("Content-Disposition: attachment; filename=\""
				. basename($file) ."\";");

			if ($dcc) {
				include_spip('inc/texte');
				$dcc = preg_replace(',\s+,',' ', couper(textebrut(typo($dcc)),60, '...'));
				spip_log('description: '.$dcc);
				header("Content-Description: " . $dcc);
			}

			if ($cl = filesize($file))
				header("Content-Length: ". $cl);

			header("Content-Transfer-Encoding: binary");

			readfile($file);
		}
	}
}

?>
