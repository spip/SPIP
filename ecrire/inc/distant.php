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

//
// Cree au besoin la copie locale d'un fichier distant
// mode = 'test' - ne faire que tester
// mode = 'auto' - charger au besoin
// mode = 'force' - charger toujours (mettre a jour)
//
// Prend en argument un chemin relatif au rep racine, ou une URL
// Renvoie un chemin relatif au rep racine, ou false
//
// http://doc.spip.org/@copie_locale
function copie_locale($source, $mode='auto') {
	$local = fichier_copie_locale($source);

	// test d'existence du fichier
	if ($mode == 'test')
		return @file_exists(_DIR_RACINE.$local) ? $local : '';

	// si $local = '' c'est un fichier refuse par fichier_copie_locale(),
	// par exemple un fichier qui ne figure pas dans nos documents ;
	// dans ce cas on n'essaie pas de le telecharger pour ensuite echouer
	if (!$local) return false;

	// sinon voir si on doit le telecharger
	if ($local != $source
	AND preg_match(',^\w+://,', $source)) {
		if (($mode=='auto' AND !@file_exists(_DIR_RACINE.$local))
		OR $mode=='force') {
			$contenu = recuperer_page($source);
			if ($contenu) {
				ecrire_fichier(_DIR_RACINE.$local, $contenu);

				// signaler au moteur de recherche qu'il peut reindexer ce doc
				$id_document = spip_fetch_array(spip_query("SELECT id_document FROM spip_documents WHERE fichier=" . _q($source)));
				$id_document = $id_document['id_document'];
				if ($id_document) {
					include_spip('inc/indexation');
					marquer_indexer('spip_documents', $id_document);
				}
			}
			else
				return false;
		}
	}

	return $local;
}

// http://doc.spip.org/@prepare_donnees_post
function prepare_donnees_post($donnees, $boundary = '') {

	// permettre a la fonction qui a demande le post de formater elle meme ses donnees
	// pour un appel soap par exemple
	// l'entete est separe des donnees par un double retour a la ligne
	// on s'occupe ici de passer tous les retours lignes (\r\n, \r ou \n) en \r\n
	if (is_string($donnees) && strlen($donnees)){
		$entete = "";
		// on repasse tous les \r\n et \r en simples \n
		$donnees = str_replace("\r\n","\n",$donnees);
		$donnees = str_replace("\r","\n",$donnees);
		// un double retour a la ligne signifie la fin de l'entete et le debut des donnees
		$p = strpos($donnees,"\n\n");
  	if ($p!==FALSE){
  		$entete = str_replace("\n","\r\n",substr($donnees,0,$p+1));
  		$donnees = substr($donnees,$p+2);
  	}
		$chaine = str_replace("\n","\r\n",$donnees);
  }
  else {
	  /* boundary automatique */
	  // Si on a plus de 500 octects de donnees, on "boundarise"
	  if($boundary == '') {
	    $taille = 0;
	    foreach ($donnees as $cle => $valeur) {
	  		if (is_array($valeur)) {
	  			foreach ($valeur as $val2) {
	          $taille += strlen($val2);
	        }
	      } else {
	        // faut-il utiliser spip_strlen() dans inc/charsets ?
	        $taille += strlen($valeur);
	      }
	    }
	    if($taille>500) {
	      $boundary = substr(md5(rand().'spip'), 0, 8);
	    }
	  }
	
		if($boundary) {
			// fabrique une chaine HTTP pour un POST avec boundary
			$entete = "Content-Type: multipart/form-data; boundary=$boundary\r\n";
			$chaine = '';
			if (is_array($donnees)) {
				foreach ($donnees as $cle => $valeur) {
					$chaine .= "\r\n--$boundary\r\n";
					$chaine .= "Content-Disposition: form-data; name=\"$cle\"\r\n";
					$chaine .= "\r\n";
					$chaine .= $valeur;
				}
				$chaine .= "\r\n--$boundary\r\n";
			}
		} else {
			// fabrique une chaine HTTP simple pour un POST
			$entete = 'Content-Type: application/x-www-form-urlencoded'."\r\n";
			$chaine = array();
			if (is_array($donnees)) {
				foreach ($donnees as $cle => $valeur) {
					if (is_array($valeur)) {
						foreach ($valeur as $val2) {
							$chaine[] = rawurlencode($cle).'='.rawurlencode($val2);
						}
					} else {
						$chaine[] = rawurlencode($cle).'='.rawurlencode($valeur);
					}
				}
				$chaine = implode('&', $chaine);
			} else {
				$chaine = $donnees;
			}
		}
  }
	return array($entete, $chaine);
}

//
// Recupere une page sur le net
// et au besoin l'encode dans le charset local
//
// options : get_headers si on veut recuperer les entetes
// taille_max : arreter le contenu au-dela (0 = seulement les entetes)
// Par defaut taille_max = 1Mo.
// datas, une chaine ou un tableau pour faire un POST de donnees
// boundary, pour forcer l'envoi par cette methode
// et refuser_gz pour forcer le refus de la compression (cas des serveurs orthographiques)
// date_verif, un timestamp unix pour arreter la recuperation si la page distante n'a pas ete modifiee depuis une date donnee
// uri_referer, preciser un referer different 
// http://doc.spip.org/@recuperer_page
function recuperer_page($url, $munge_charset=false, $get_headers=false,
	$taille_max = 1048576, $datas='', $boundary='', $refuser_gz = false,
	$date_verif = '', $uri_referer = '') {
  	$gz = false;

	// Accepter les URLs au format feed:// ou qui ont oublie le http://
	$url = preg_replace(',^feed://,i', 'http://', $url);
	if (!preg_match(',^[a-z]+://,i', $url)) $url = 'http://'.$url;

	if ($taille_max == 0)
		$get = 'HEAD';
	else
		$get = 'GET';

	if (!empty($datas)) {
		$get = 'POST';
		list($content_type, $postdata) = prepare_donnees_post($datas);
	}

	for ($i=0;$i<10;$i++) {	// dix tentatives maximum en cas d'entetes 301...
		list($f, $fopen) = init_http($get, $url, $refuser_gz, $uri_referer);

		// si on a utilise fopen() - passer a la suite
		if ($fopen) {
			spip_log('connexion via fopen');
			break;
		} else {
			// Fin des entetes envoyees par SPIP
			if($get == 'POST') {
				fputs($f, $content_type);
				fputs($f, 'Content-Length: '.strlen($postdata)."\r\n");
				fputs($f, "\r\n".$postdata);
			} else {
				fputs($f,"\r\n");
			}

			// Reponse du serveur distant
			$s = trim(fgets($f, 16384));
			if (preg_match(',^HTTP/[0-9]+\.[0-9]+ ([0-9]+),', $s, $r)) {
				$status = $r[1];
			}
			else return;

			// Entetes HTTP de la page
			$headers = '';
			while ($s = trim(fgets($f, 16384))) {
				$headers .= $s."\n";
				if (preg_match(',^Location: (.*),i', $s, $r)) {
					include_spip('inc/filtres');
					$location = suivre_lien($url, $r[1]);
					spip_log("Location: $location");
				}
				if ($date_verif AND preg_match(',^Last-Modified: (.*),', $s, $r)) {
					if(strtotime($date_verif)>=strtotime($r[1])) {
						//Cas ou la page distante n'a pas bouge depuis
						//la derniere visite
						return $status;
					}
				}
				if (preg_match(",^Content-Encoding: .*gzip,i", $s))
					$gz = true;
			}
			if ($status >= 300 AND $status < 400 AND $location)
				$url = $location;
			else if ($status != 200)
				return false;
			else
				break; # ici on est content
			fclose($f);
			$f = false;
		}
	}

	// Contenu de la page
	if (!$f) {
		spip_log("ECHEC chargement $url");
		return false;
	}

	$result = '';
	while (!feof($f) AND strlen($result)<$taille_max)
		$result .= fread($f, 16384);
	fclose($f);

	// Decompresser le flux
	if ($gz AND $result)
		$result = gzinflate(substr($result,10));

	// Faut-il l'importer dans notre charset local ?
	if ($munge_charset) {
		include_spip('inc/charsets');
		$result = transcoder_page ($result, $headers);
	}

	return ($get_headers ? $headers."\n" : '').$result;
}


// Si on doit conserver une copie locale des fichiers distants, autant que ca
// soit a un endroit canonique -- si ca peut etre bijectif c'est encore mieux,
// mais la tout de suite je ne trouve pas l'idee, etant donne les limitations
// des filesystems
// http://doc.spip.org/@nom_fichier_copie_locale
function nom_fichier_copie_locale($source, $extension) {
	$dir = sous_repertoire(_DIR_IMG, 'distant'); # IMG/distant/
	$dir2 = sous_repertoire($dir, $extension); 		# IMG/distant/pdf/
	$chemin = $dir2 . substr(preg_replace(',[^\w-],', '', basename($source)).'-'.md5($source),0,12).
		substr(md5($source),0,4).'.'.$extension;

	// on se place tout le temps comme si on etait a la racine
	if (_DIR_RACINE)
		$chemin = preg_replace(',^'.preg_quote(_DIR_RACINE).',', '', $chemin);

	return $chemin;
}

//
// Donne le nom de la copie locale de la source
//
// http://doc.spip.org/@fichier_copie_locale
function fichier_copie_locale($source) {
	// Si c'est une image locale pas de souci
	if (!preg_match(',^\w+://,', $source)) {
		if (_DIR_RACINE)
			$source = preg_replace(',^'.preg_quote(_DIR_RACINE).',', '', $source);
		return $source;
	}

	$extension = "";
	// Chercher d'abord le doc dans la table des documents, pour se baser sur son type reel
	$t = spip_fetch_array(spip_query("SELECT id_type FROM spip_documents WHERE fichier=" . _q($source) . " AND distant='oui'"));
	if ($t) {
		$t = spip_fetch_array(spip_query("SELECT extension FROM spip_types_documents WHERE id_type=".$t['id_type']));
		if ($t)
			$extension = $t['extension'];
	}
	
	// si la source n'est pas dans la table des documents, on regarde si son extension est connue et autorisee
	if (!strlen($extension)) {
		$path_parts = pathinfo($source);
		if (isset($path_parts['extension']) && strlen($path_parts['extension'])){
			// verifier que c'est un type autorise
			$t = spip_fetch_array(spip_query("SELECT extension FROM spip_types_documents WHERE extension="._q($path_parts['extension'])));
			if ($t)
				$extension = $t['extension'];
		}
	}
	
	if (strlen($extension))
		return nom_fichier_copie_locale($source, $extension);
}


// Recuperer les infos d'un document distant, sans trop le telecharger
// http://doc.spip.org/@recuperer_infos_distantes
function recuperer_infos_distantes($source, $max=0) {

	$a = array();
	$mime_type = '';
	// On va directement charger le debut des images et des fichiers html,
	// de maniere a attrapper le maximum d'infos (titre, taille, etc). Si
	// ca echoue l'utilisateur devra les entrer...
	if ($headers = recuperer_page($source, false, true, $max)) {
		list($headers, $a['body']) = split("\n\n", $headers, 2);
		$t = preg_match(",\nContent-Type: *([^[:space:];]*),i",
				"\n$headers", $regs);
		if ($t) {
		  $mime_type = (trim($regs[1]));
		  $t = spip_fetch_array(spip_query("SELECT id_type,extension FROM spip_types_documents WHERE mime_type=" . _q($mime_type)));
		}
		if ($t) {
			spip_log("mime-type $mime_type ok");
			$a['id_type'] = $t['id_type'];
			$a['extension'] = $t['extension'];
		} else {
			# par defaut on retombe sur '.bin' si c'est autorise
			spip_log("mime-type $mime_type inconnu");
			$t = spip_fetch_array(spip_query("SELECT id_type,extension FROM spip_types_documents WHERE extension='bin'"));
			if (!$t) return false;
			$a['id_type'] = $t['id_type'];
			$a['extension'] = $t['extension'];
		}

		if (preg_match(",\nContent-Length: *([^[:space:]]*),i",
			"\n$headers", $regs))
			$a['taille'] = intval($regs[1]);
	}

	// Echec avec HEAD, on tente avec GET
	if (!$a AND !$max) {
	spip_log("tente $source");
		$a = recuperer_infos_distantes($source, 1024*1024);
	}

	// S'il s'agit d'une image pas trop grosse ou d'un fichier html, on va aller
	// recharger le document en GET et recuperer des donnees supplementaires...
	if (preg_match(',^image/(jpeg|gif|png|swf),', $mime_type)) {
		if ($max == 0
		    AND $a['taille'] < 1024*1024
		    AND (strpos($GLOBALS['meta']['formats_graphiques'],$a['extension'])!==false) ){
			$a = recuperer_infos_distantes($source, 1024*1024);
		}
		else if ($a['body']) {
			$a['fichier'] = nom_fichier_copie_locale($source, $a['extension']);
			ecrire_fichier($a['fichier'], $a['body']);
			$size_image = @getimagesize($a['fichier']);
			$a['largeur'] = intval($size_image[0]);
			$a['hauteur'] = intval($size_image[1]);
			$a['type_image'] = true;
		}
	}
	
	if ($mime_type == 'text/html') {
		include_spip('inc/filtres');
		$page = recuperer_page($source, true, false, 1024*1024);
		if(preg_match(',<title>(.*?)</title>,ims', $page, $regs)) 
			$a['titre'] = corriger_caracteres(trim($regs[1]));
			if (!$a['taille']) $a['taille'] = strlen($page); # a peu pres
	}

	return $a;
}


//
// Demarre une transaction HTTP (s'arrete a la fin des entetes)
// retourne un descripteur de fichier
//
// http://doc.spip.org/@init_http
function init_http($get, $url, $refuse_gz=false, $uri_referer = '') {
	$via_proxy = ''; $proxy_user = ''; $fopen = false;
	$http_proxy = $GLOBALS['meta']["http_proxy"];
	if (strncmp("http://", $http_proxy,7)!=0)
		$http_proxy = '';
	else
		$via_proxy = " (proxy $http_proxy)";

	spip_log("http $get $url$via_proxy");

	$t = @parse_url($url);
	$host = $t['host'];
	if ($t['scheme'] == 'http') {
		$scheme = 'http'; $scheme_fsock='';
	} else {
		$scheme = $t['scheme']; $scheme_fsock=$scheme.'://';
	}
	if (!isset($t['port']) || !($port = $t['port'])) $port = 80;
	$query = $t['query'];
	if (!isset($t['path']) || !($path = $t['path'])) $path = "/";

	if ($http_proxy) {
		$t2 = @parse_url($http_proxy);
		$proxy_host = $t2['host'];
		$proxy_user = $t2['user'];
		$proxy_pass = $t2['pass'];
		if (!($proxy_port = $t2['port'])) $proxy_port = 80;
		$f = @fsockopen($proxy_host, $proxy_port);
	} else
		$f = @fsockopen($scheme_fsock.$host, $port);

	if ($f) {
		if ($http_proxy)
			fputs($f, "$get $scheme://$host" . (($port != 80) ? ":$port" : "") . $path . ($query ? "?$query" : "") . " HTTP/1.0\r\n");
		else
			fputs($f, "$get $path" . ($query ? "?$query" : "") . " HTTP/1.0\r\n");

		fputs($f, "Host: $host\r\n");
		fputs($f, "User-Agent: SPIP-".$GLOBALS['spip_version_affichee']." (http://www.spip.net/)\r\n");

		// Proxy authentifiant
		if ($proxy_user) {
			fputs($f, "Proxy-Authorization: Basic "
			. base64_encode($proxy_user . ":" . $proxy_pass) . "\r\n");
		}
		// Referer = c'est nous !
		if ($referer = $GLOBALS['meta']["adresse_site"]) {
			$referer .= '/'.$uri_referer;
			fputs($f, "Referer: $referer\r\n");
		}

		// On sait lire du gzip
		if ($GLOBALS['flag_gz'] AND !$refuse_gz)
			fputs($f, "Accept-Encoding: gzip\r\n");

	}
	// fallback : fopen
	else if (!$GLOBALS['tester_proxy']) {
		$f = @fopen($url, "rb");
		$fopen = true;
	}
	// echec total
	else {
		$f = false;
	}

	return array($f, $fopen);
}

?>
