<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

if (!defined('_INC_DISTANT_VERSION_HTTP')) define('_INC_DISTANT_VERSION_HTTP', "HTTP/1.0");
if (!defined('_INC_DISTANT_CONTENT_ENCODING')) define('_INC_DISTANT_CONTENT_ENCODING', "gzip");
if (!defined('_INC_DISTANT_USER_AGENT')) define('_INC_DISTANT_USER_AGENT', 'SPIP-' .$GLOBALS['spip_version_affichee']. " (" .$GLOBALS['home_server']. ")");

//@define('_COPIE_LOCALE_MAX_SIZE',2097152); // poids (inc/utils l'a fait)
//
// Cree au besoin la copie locale d'un fichier distant
// mode = 'test' - ne faire que tester
// mode = 'auto' - charger au besoin
// mode = 'modif' - Si deja present, ne charger que si If-Modified-Since
// mode = 'force' - charger toujours (mettre a jour)
//
// Prend en argument un chemin relatif au rep racine, ou une URL
// Renvoie un chemin relatif au rep racine, ou false
//
// http://doc.spip.org/@copie_locale
function copie_locale($source, $mode='auto') {

	// si c'est la protection de soi-meme
	$reg = ',' . $GLOBALS['meta']['adresse_site']
	  . "/?spip.php[?]action=acceder_document.*file=(.*)$,";

	if (preg_match($reg, $source, $local)) return substr(_DIR_IMG,strlen(_DIR_RACINE)) . urldecode($local[1]);

	$local = fichier_copie_locale($source);
	$localrac = _DIR_RACINE.$local;
	$t = ($mode=='force') ? false  : @file_exists($localrac);

	// test d'existence du fichier
	if ($mode == 'test') return $t ? $local : '';

	// si $local = '' c'est un fichier refuse par fichier_copie_locale(),
	// par exemple un fichier qui ne figure pas dans nos documents ;
	// dans ce cas on n'essaie pas de le telecharger pour ensuite echouer
	if (!$local) return false;

	// sinon voir si on doit/peut le telecharger
	if ($local == $source OR !preg_match(',^\w+://,', $source)) 
		return $local;

	if ($mode=='modif' OR !$t) {
		$res = recuperer_page($source, $localrac,false,_COPIE_LOCALE_MAX_SIZE, '','',false, $t ? filemtime($localrac) : '');
		if (!$res) return $t ? $local : false;
#		spip_log ('ecrire copie locale '.$localrac." taille $res");
			
		// pour une eventuelle indexation
		pipeline('post_edition',
				array(
					'args' => array(
						'operation' => 'copie_locale',
						'source' => $source,
						'fichier' => $local
					),
					'data' => null
				)
			);
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
	  if($boundary === '') {
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

		if(is_string($boundary) and strlen($boundary)) {
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
// taille_max : arreter le contenu au-dela (0 = seulement les entetes ==>HEAD)
// Par defaut taille_max = 1Mo.
// datas, une chaine ou un tableau pour faire un POST de donnees
// boundary, pour forcer l'envoi par cette methode
// et refuser_gz pour forcer le refus de la compression (cas des serveurs orthographiques)
// date_verif, un timestamp unix pour arreter la recuperation si la page distante n'a pas ete modifiee depuis une date donnee
// uri_referer, preciser un referer different
// Le second argument ($trans) :
// * si c'est une chaine longue, alors c'est un nom de fichier
//   dans lequel on ecrit directement la page
// * si c'est true/null ca correspond a une demande d'encodage/charset
// http://doc.spip.org/@recuperer_page
function recuperer_page($url, $trans=false, $get_headers=false,
	$taille_max = null, $datas='', $boundary='', $refuser_gz = false,
	$date_verif = '', $uri_referer = '') {
	$gz = false;

	// $copy = copier le fichier ?
	$copy = (is_string($trans) AND strlen($trans) > 5); // eviter "false" :-)

	if (is_null($taille_max))
		$taille_max = $copy ? _COPIE_LOCALE_MAX_SIZE : 1048576;

	// Accepter les URLs au format feed:// ou qui ont oublie le http://
	$url = preg_replace(',^feed://,i', 'http://', $url);
	if (!preg_match(',^[a-z]+://,i', $url)) $url = 'http://'.$url;

	if ($taille_max == 0)
		$get = 'HEAD';
	else
		$get = 'GET';

	if (!empty($datas)) {
		$get = 'POST';
		list($type, $postdata) = prepare_donnees_post($datas, $boundary);
		$datas = $type . 'Content-Length: '.strlen($postdata)."\r\n\r\n".$postdata;
	}

	// dix tentatives maximum en cas d'entetes 301...
	for ($i=0;$i<10;$i++) {
		$url = recuperer_lapage($url, $trans, $get, $taille_max, $datas, $refuser_gz, $date_verif, $uri_referer);
		if (!$url) return false;
		if (is_array($url)) {
			list($headers, $result) = $url;
			return ($get_headers ? $headers."\n" : '').$result;
		} else spip_log("recuperer page recommence sur $url");
	}
}

// args comme ci-dessus (presque)
// retourne l'URL en cas de 301, un tableau (entete, corps) si ok, false sinon
// si $trans est null -> on ne veut que les headers
// si $trans est une chaine, c'est un nom de fichier pour ecrire directement dedans
// http://doc.spip.org/@recuperer_lapage
function recuperer_lapage($url, $trans=false, $get='GET', $taille_max = 1048576, $datas='', $refuser_gz = false, $date_verif = '', $uri_referer = '')
{
	// $copy = copier le fichier ?
	$copy = (is_string($trans) AND strlen($trans) > 5); // eviter "false" :-)

	// si on ecrit directement dans un fichier, pour ne pas manipuler
	// en memoire refuser gz
	if ($copy)
		$refuser_gz = true;

	// ouvrir la connexion et envoyer la requete et ses en-tetes
	list($f, $fopen) = init_http($get, $url, $refuser_gz, $uri_referer, $datas, _INC_DISTANT_VERSION_HTTP, $date_verif);
	if (!$f) {
		spip_log("ECHEC init_http $url");
		return false;
	}

	// Sauf en fopen, envoyer le flux d'entree
	// et recuperer les en-tetes de reponses
	if ($fopen)
		$headers = '';
	else {
		$headers = recuperer_entetes($f, $date_verif);
		if (is_numeric($headers)) {
			fclose($f);
			// Chinoisierie inexplicable pour contrer 
			// les actions liberticides de l'empire du milieu
			if ($headers) {
				spip_log("HTTP status $headers pour $url");
				return false;
			} elseif ($result = @file_get_contents($url))
			    return array('', $result);
			else return false;
		}
		if (!is_array($headers)) { // cas Location
			fclose($f);
			include_spip('inc/filtres');
			return suivre_lien($url, $headers);
		}
		$headers = join('', $headers);
	}

	if ($trans === NULL) return array($headers, '');

	// s'il faut deballer, le faire via un fichier temporaire
	// sinon la memoire explose pour les gros flux

	$gz = preg_match(",\bContent-Encoding: .*gzip,is", $headers) ?
		(_DIR_TMP.md5(uniqid(mt_rand())).'.tmp.gz') : '';
	  
#	spip_log("entete ($trans $copy $gz)\n$headers"); 
	$result = recuperer_body($f, $taille_max, $gz ? $gz : ($copy ? $trans : ''));
	fclose($f);
	if (!$result) return array($headers, $result);

	// Decompresser au besoin
	if ($gz) {
		$result = join('', gzfile($gz));
		supprimer_fichier($gz);
	}
	// Faut-il l'importer dans notre charset local ?
	if ($trans === true) {
		include_spip('inc/charsets');
		$result = transcoder_page ($result, $headers);
	}

	return array($headers, $result);
}

// http://doc.spip.org/@recuperer_body
function recuperer_body($f, $taille_max=1048576, $fichier='')
{
	$taille = 0;
	$result = '';
	if ($fichier){
		$fp = spip_fopen_lock($fichier, 'w',LOCK_EX);
		if (!$fp) return false;
		$result = 0; // on renvoie la taille du fichier
	}
	while (!feof($f) AND $taille<$taille_max){
		$res = fread($f, 16384);
		$taille += strlen($res);
		if ($fp){
			fwrite($fp,$res);
			$result = $taille;
		}
		else
			$result .= $res;
	}
	if ($fp)
		spip_fclose_unlock($fp);
	return $result;
}

// Lit les entetes de reponse HTTP sur la socket $f et retourne:
// la valeur (chaine) de l'en-tete Location si on l'a trouvee
// la valeur (numerique) du statut si different de 200, notamment Not-Modified
// le tableau des entetes dans tous les autres cas

// http://doc.spip.org/@recuperer_entetes
function recuperer_entetes($f, $date_verif='')
{
	$s = @trim(fgets($f, 16384));

	if (!preg_match(',^HTTP/[0-9]+\.[0-9]+ ([0-9]+),', $s, $r)) {
		return 0;
	}
	$status = intval($r[1]);
	$headers = array();
	$not_modif = $location = false;
	while ($s = trim(fgets($f, 16384))) {
		$headers[]= $s."\n";
		preg_match(',^([^:]*): *(.*)$,i', $s, $r);
		list(,$d, $v) = $r;
		if (strtolower(trim($d)) == 'location' AND $status >= 300 AND $status < 400) {
			$location = $v;
		}
		elseif ($date_verif AND ($d == 'Last-Modified')) {
			if ($date_verif>=strtotime($v)) {
				//Cas ou la page distante n'a pas bouge depuis
				//la derniere visite
				$not_modif = true;
			}
		}
	}

	if ($location) return $location;
	if ($status != 200 or $not_modif) return $status;
	return $headers;
}

// Si on doit conserver une copie locale des fichiers distants, autant que ca
// soit a un endroit canonique -- si ca peut etre bijectif c'est encore mieux,
// mais la tout de suite je ne trouve pas l'idee, etant donne les limitations
// des filesystems
// http://doc.spip.org/@nom_fichier_copie_locale
function nom_fichier_copie_locale($source, $extension) {

	include_spip('inc/getdocument');
	$d = creer_repertoire_documents('distant'); # IMG/distant/
	$d = sous_repertoire($d, $extension); # IMG/distant/pdf/

	// on se place tout le temps comme si on etait a la racine
	if (_DIR_RACINE)
		$d = preg_replace(',^'.preg_quote(_DIR_RACINE).',', '', $d);

	$m = md5($source);

	return $d
	. substr(preg_replace(',[^\w-],', '', basename($source)).'-'.$m,0,12)
	. substr($m,0,4)
	. ".$extension";
}

//
// Donne le nom de la copie locale de la source
//
// http://doc.spip.org/@fichier_copie_locale
function fichier_copie_locale($source) {
	// Si c'est deja local pas de souci
	if (!preg_match(',^\w+://,', $source)) {
		if (_DIR_RACINE)
			$source = preg_replace(',^'.preg_quote(_DIR_RACINE).',', '', $source);
		return $source;
	}
	
	// optimisation : on regarde si on peut deviner l'extension dans l'url et si le fichier
	// a deja ete copie en local avec cette extension
	// dans ce cas elle est fiable, pas la peine de requeter en base
	$path_parts = pathinfo($source);
	$ext = $path_parts ? $path_parts['extension'] : '';
	if ($ext
		AND preg_match(',^\w+$,',$ext) // pas de php?truc=1&...
	  AND $f=nom_fichier_copie_locale($source, $ext)
	  AND file_exists(_DIR_RACINE . $f))
	  return $f;
	

	// Si c'est deja dans la table des documents,
	// ramener le nom de sa copie potentielle

	$ext = sql_getfetsel("extension", "spip_documents", "fichier=" . sql_quote($source) . " AND distant='oui' AND extension <> ''");


	if ($ext) return nom_fichier_copie_locale($source, $ext);

	// voir si l'extension indiquee dans le nom du fichier est ok
	// et si il n'aurait pas deja ete rapatrie

	$ext = $path_parts ? $path_parts['extension'] : '';

	if ($ext AND sql_getfetsel("extension", "spip_types_documents", "extension=".sql_quote($ext))) {
		$f = nom_fichier_copie_locale($source, $ext);
		if (file_exists(_DIR_RACINE  . $f))
		  return $f;
	}
	// Ping  pour voir si son extension est connue et autorisee
	$path_parts = recuperer_infos_distantes($source,0,false) ;
	$ext = $path_parts ? $path_parts['extension'] : '';
	if ($ext AND sql_getfetsel("extension", "spip_types_documents", "extension=".sql_quote($ext))) {
		return nom_fichier_copie_locale($source, $ext);
	}
	spip_log("pas de copie locale pour $source");
}


// Recuperer les infos d'un document distant, sans trop le telecharger
#$a['body'] = chaine
#$a['type_image'] = booleen
#$a['titre'] = chaine
#$a['largeur'] = intval
#$a['hauteur'] = intval
#$a['taille'] = intval
#$a['extension'] = chaine
#$a['fichier'] = chaine

// http://doc.spip.org/@recuperer_infos_distantes
function recuperer_infos_distantes($source, $max=0, $charger_si_petite_image = true) {

	# charger les alias des types mime
	include_spip('base/typedoc');
	global $mime_alias;

	$a = array();
	$mime_type = '';
	// On va directement charger le debut des images et des fichiers html,
	// de maniere a attrapper le maximum d'infos (titre, taille, etc). Si
	// ca echoue l'utilisateur devra les entrer...
	if ($headers = recuperer_page($source, false, true, $max, '', '', true)) {
		list($headers, $a['body']) = preg_split(',\n\n,', $headers, 2);

		if (preg_match(",\nContent-Type: *([^[:space:];]*),i", "\n$headers", $regs))
			$mime_type = (trim($regs[1]));
		else
			$mime_type = ''; // inconnu

		// Appliquer les alias
		while (isset($mime_alias[$mime_type]))
			$mime_type = $mime_alias[$mime_type];

		// Si on a text/plain, c'est peut-etre que le serveur ne sait pas
		// ce qu'il sert ; on va tenter de detecter via l'extension de l'url
		$t = null;
		if (($mime_type == 'text/plain' OR $mime_type == '')
		AND preg_match(',\.([a-z0-9]+)(\?.*)?$,', $source, $rext)) {
			$t = sql_fetsel("extension", "spip_types_documents", "extension=" . sql_quote($rext[1]));
		}

		// Autre mime/type (ou text/plain avec fichier d'extension inconnue)
		if (!$t)
			$t = sql_fetsel("extension", "spip_types_documents", "mime_type=" . sql_quote($mime_type));

		// Toujours rien ? (ex: audio/x-ogg au lieu de application/ogg)
		// On essaie de nouveau avec l'extension
		if (!$t
		AND $mime_type != 'text/plain'
		AND preg_match(',\.([a-z0-9]+)(\?.*)?$,', $source, $rext)) {
			$t = sql_fetsel("extension", "spip_types_documents", "extension=" . sql_quote($rext[1]));
		}


		if ($t) {
			spip_log("mime-type $mime_type ok, extension ".$t['extension']);
			$a['extension'] = $t['extension'];
		} else {
			# par defaut on retombe sur '.bin' si c'est autorise
			spip_log("mime-type $mime_type inconnu");
			$t = sql_fetsel("extension", "spip_types_documents", "extension='bin'");
			if (!$t) return false;
			$a['extension'] = $t['extension'];
		}

		if (preg_match(",\nContent-Length: *([^[:space:]]*),i",
			"\n$headers", $regs))
			$a['taille'] = intval($regs[1]);
	}

	// Echec avec HEAD, on tente avec GET
	if (!$a AND !$max) {
		spip_log("tenter GET $source");
		$a = recuperer_infos_distantes($source, 1024*1024);
	}

	// S'il s'agit d'une image pas trop grosse ou d'un fichier html, on va aller
	// recharger le document en GET et recuperer des donnees supplementaires...
	if (preg_match(',^image/(jpeg|gif|png|swf),', $mime_type)) {
		if ($max == 0
		AND $a['taille'] < 1024*1024
		AND (strpos($GLOBALS['meta']['formats_graphiques'],$a['extension'])!==false)
		AND $charger_si_petite_image) {
			$a = recuperer_infos_distantes($source, 1024*1024);
		}
		else if ($a['body']) {
			$a['fichier'] = _DIR_RACINE . nom_fichier_copie_locale($source, $a['extension']);
			ecrire_fichier($a['fichier'], $a['body']);
			$size_image = @getimagesize($a['fichier']);
			$a['largeur'] = intval($size_image[0]);
			$a['hauteur'] = intval($size_image[1]);
			$a['type_image'] = true;
		}
	}

	// Fichier swf, si on n'a pas la taille, on va mettre 425x350 par defaut
	// ce sera mieux que 0x0
	if ($a['extension'] == 'swf'
	AND !$a['largeur']) {
		$a['largeur'] = 425;
		$a['hauteur'] = 350;
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


// http://doc.spip.org/@need_proxy
function need_proxy($host)
{
	$http_proxy = @$GLOBALS['meta']["http_proxy"];
	$http_noproxy = @$GLOBALS['meta']["http_noproxy"];

	$domain = substr($host,strpos($host,'.'));

	return ($http_proxy
	AND (strpos(" $http_noproxy ", " $host ") === false
	     AND (strpos(" $http_noproxy ", " $domain ") === false)))
	? $http_proxy : '';
}

//
// Lance une requete HTTP avec entetes
// retourne le descripteur sur lequel lire la reponse
//
// http://doc.spip.org/@init_http
function init_http($method, $url, $refuse_gz=false, $referer = '', $datas="", $vers="HTTP/1.0", $date='') {
	$user = $via_proxy = $proxy_user = ''; 
	$fopen = false;

	$t = @parse_url($url);
	$host = $t['host'];
	if ($t['scheme'] == 'http') {
		$scheme = 'http'; $noproxy = '';
	} elseif ($t['scheme'] == 'https') {
		$scheme = 'ssl'; $noproxy = 'ssl://';
		if (!isset($t['port']) || !($port = $t['port'])) $t['port'] = 443;
	}
	else {
		$scheme = $t['scheme']; $noproxy = $scheme.'://';
	}
	if (isset($t['user']))
		$user = array($t['user'], $t['pass']);

	if (!isset($t['port']) || !($port = $t['port'])) $port = 80;
	if (!isset($t['path']) || !($path = $t['path'])) $path = "/";
	if (@$t['query']) $path .= "?" .$t['query'];

	$f = lance_requete($method, $scheme, $user, $host, $path, $port, $noproxy, $refuse_gz, $referer, $datas, $vers, $date);
	if (!$f) {
	  // fallback : fopen
		if (!_request('tester_proxy')) {
			$f = @fopen($url, "rb");
			spip_log("connexion vers $url par simple fopen");
			$fopen = true;
		} else $f = false;// echec total
	}

	return array($f, $fopen);
}

// http://doc.spip.org/@lance_requete
function lance_requete($method, $scheme, $user, $host, $path, $port, $noproxy, $refuse_gz=false, $referer = '', $datas="", $vers="HTTP/1.0", $date='') {

	$proxy_user = '';
	$http_proxy = need_proxy($host);
	if ($user) $user = urlencode($user[0]).":".urlencode($user[1]);

	if ($http_proxy) {
		$path = "$scheme://"
			. (!$user ? '' : "$user@")
			. "$host" . (($port != 80) ? ":$port" : "") . $path;
		$t2 = @parse_url($http_proxy);
		$first_host = $t2['host'];
		if (!($port = $t2['port'])) $port = 80;
		if ($t2['user'])
			$proxy_user = base64_encode($t2['user'] . ":" . $t2['pass']);
	} else $first_host = $noproxy.$host;

	$f = @fsockopen($first_host, $port);
	spip_log("Recuperer $path sur $first_host:$port par $f");
	if (!$f) return false;

	$site = $GLOBALS['meta']["adresse_site"];

	$req = "$method $path $vers\r\n"
	. "Host: $host\r\n"
	. "User-Agent: " . _INC_DISTANT_USER_AGENT . "\r\n"
	. ($refuse_gz ? '' : ("Accept-Encoding: " . _INC_DISTANT_CONTENT_ENCODING . "\r\n"))
	. (!$site ? '' : "Referer: $site/$referer\r\n")
	. (!$date ? '' : "If-Modified-Since: " . (gmdate("D, d M Y H:i:s", $date)  ." GMT\r\n"))
	. (!$user ? '' : ("Authorization: Basic " . base64_encode($user) ."\r\n"))
	. (!$proxy_user ? '' : "Proxy-Authorization: Basic $proxy_user\r\n")
	. (!strpos($vers, '1.1') ? '' : "Keep-Alive: 300\r\nConnection: keep-alive\r\n");

#	spip_log("Requete\n$req");
	fputs($f, $req);
	fputs($f, $datas ? $datas : "\r\n");
	return $f;
}

?>
