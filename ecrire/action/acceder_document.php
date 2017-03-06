<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2016                                                *
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

function retrouver_document($f, $id) {
	include_spip('inc/documents');

	$file = get_spip_doc($f);

	if (strpos($f,'../') !== false
	OR preg_match(',^\w+://,', $f)) {
		return 403;
	}
	else if (!file_exists($file) OR !is_readable($file)) {
		return 404;
	} else {
		$path = set_spip_doc($file);
		$path2 = generer_acceder_document($f, $id);
		$path3 = (strpos($path2, 'https') === 0) ? 
            preg_replace('/^https/', 'http', $path2) : 
            preg_replace('/^http/', 'https', $path2);
		$where = "(documents.fichier=".sql_quote($path)
		  . ' OR documents.fichier=' . sql_quote($path2)
		  . ' OR documents.fichier=' . sql_quote($path3) . ')'
		  . ($id ? (" AND documents.id_document=$id") : '');

		$doc = sql_fetsel("documents.id_document, documents.titre, types.mime_type, types.inclus, documents.extension", "spip_documents AS documents LEFT JOIN spip_types_documents AS types ON documents.extension=types.extension",$where);
		if (!$doc) {
			return 404;
		} else {

            // ETag pour gerer le status 304
            $doc['ETag'] = md5($file . ': '. filemtime($file));

            // Si le fichier a un titre avec extension,
            // ou si c'est un nom bien connu d'Unix, le prendre
            // sinon l'ignorer car certains navigateurs pataugent
            if (isset($doc['titre'])
            AND (preg_match('/^\w+[.]\w+$/', $doc['titre']) OR $doc['titre'] == 'Makefile'))
				$f = $doc['titre'];
            else $f = basename($file);

            // Pour les document affichables par les navigateurs,
            // envoyer "Content-Disposition: inline".
            // Mais la propriete "affichable" n'est pas toujours devinable,
            // il faut quand meme donner un nom au fichier a creer sinon.
            // Celui-ci est malheureusement souvent ignore, cf
            // http://greenbytes.de/tech/tc2231/

            $doc['Disposition'] = (($doc['inclus']!=='non')  ? 'inline' : 'attachment') . '; filename="' . $f . '"';
		}

        $doc['fichier'] = $file;
        $doc['Cache-Control'] = 'max-age=0, must-revalidate';
        return $doc;
	}
}

// http://doc.spip.org/@action_acceder_document_dist
function action_acceder_document_dist() {

	// $file exige pour eviter le scan id_document par id_document
    $file = rawurldecode(_request('file'));
    // id_document eventuellement absent
    $id = intval(rawurldecode(_request('arg')));
    $doc = retrouver_document($file, $id);

    if ($doc == 403) {
		include_spip('inc/minipres');
		echo minipres();
    } elseif ($doc ==  404) {
		http_status(404);
		include_spip('inc/minipres');
		echo minipres(_T('erreur').' 404',
			_T('info_document_indisponible'));
    } else {
        if (isset($_SERVER['HTTP_IF_NONE_MATCH'])
        AND $_SERVER['HTTP_IF_NONE_MATCH'] == $doc['ETag']) {
            http_status(304); // Not modified
        } else {
            //
            // Verifier les droits de lecture du document
            // en controlant la cle passee en argument
            //
            include_spip('inc/securiser_action');
            $cle = _request('cle');
            if (!verifier_cle_action($doc['id_document'].','.$file, $cle)) {
                include_spip('inc/minipres');
                echo minipres();
            } else {
                header("Content-Type: ". $doc['mime_type']);
                header("Cache-Control: ". $doc['Cache-Control']); 
                header("Content-Disposition: " . $doc['Disposition']); 
                header("Content-Transfer-Encoding: binary");
                header('ETag: '. $doc['ETag']);
                $file = $doc['fichier'];
                if ($cl = @filesize($file))
                    header("Content-Length: ". $cl);
                readfile($file);
            }
        }
    }
}

?>
