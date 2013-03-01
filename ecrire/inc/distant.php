<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Ce fichier gère l'obtention de données distantes
 * 
 * @package SPIP\Core\Distant
**/
if (!defined('_ECRIRE_INC_VERSION')) return;

if (!defined('_INC_DISTANT_VERSION_HTTP')) define('_INC_DISTANT_VERSION_HTTP', "HTTP/1.0");
if (!defined('_INC_DISTANT_CONTENT_ENCODING')) define('_INC_DISTANT_CONTENT_ENCODING', "gzip");
if (!defined('_INC_DISTANT_USER_AGENT')) define('_INC_DISTANT_USER_AGENT', 'SPIP-' . $GLOBALS['spip_version_affichee'] . " (" . $GLOBALS['home_server'] . ")");

define('_REGEXP_COPIE_LOCALE', ',' . 
       preg_replace('@^https?:@', 'https?:', $GLOBALS['meta']['adresse_site'])
       . "/?spip.php[?]action=acceder_document.*file=(.*)$,");

//@define('_COPIE_LOCALE_MAX_SIZE',2097152); // poids (inc/utils l'a fait)

/**
 * Crée au besoin la copie locale d'un fichier distant
 *
 * Prend en argument un chemin relatif au rep racine, ou une URL
 * Renvoie un chemin relatif au rep racine, ou false
 *
 * @link http://www.spip.net/4155
 * @pipeline_appel post_edition
 *
 * @param string $source
 * @param string $mode
 *   - 'test' - ne faire que tester
 *   - 'auto' - charger au besoin
 *   - 'modif' - Si deja present, ne charger que si If-Modified-Since
 *   - 'force' - charger toujours (mettre a jour)
 * @param string $local
 *   permet de specifier le nom du fichier local (stockage d'un cache par exemple, et non document IMG)
 * @return bool|string
 */
function copie_locale($source, $mode='auto', $local = null) {

	// si c'est la protection de soi-meme, retourner le path
	if ($mode !== 'force' AND preg_match(_REGEXP_COPIE_LOCALE, $source, $match)) {
		$source = substr(_DIR_IMG,strlen(_DIR_RACINE)) . urldecode($match[1]);
		return @file_exists($source) ? $source : false;
	}

	if (is_null($local))
		$local = fichier_copie_locale($source);
	else {
		if (_DIR_RACINE AND strncmp(_DIR_RACINE, $local, strlen(_DIR_RACINE))==0) {
			$local = substr($local, strlen(_DIR_RACINE));
		}
	}

	// si $local = '' c'est un fichier refuse par fichier_copie_locale(),
	// par exemple un fichier qui ne figure pas dans nos documents ;
	// dans ce cas on n'essaie pas de le telecharger pour ensuite echouer
	if (!$local) return false;

	$localrac = _DIR_RACINE.$local;
	$t = ($mode=='force') ? false  : @file_exists($localrac);

	// test d'existence du fichier
	if ($mode=='test') return $t ? $local : '';

	// sinon voir si on doit/peut le telecharger
	if ($local==$source OR !preg_match(',^\w+://,', $source))
		return $local;

	if ($mode=='modif' OR !$t){
		// passer par un fichier temporaire unique pour gerer les echecs en cours de recuperation
		// et des eventuelles recuperations concurantes
		include_spip("inc/acces");
		$res = recuperer_url($source, array('file'=>$localrac, 'taille_max'=>_COPIE_LOCALE_MAX_SIZE, 'if_modified_since' => $t ? filemtime($localrac) : ''));
		if (!$res OR (!$res["length"] AND !$res["status"]!==304)){
			spip_log("copie_locale : Echec recuperation $source sur $localrac status : ". $res["status"],_LOG_INFO_IMPORTANTE);
		}
		if (!$res['length']){
			// si $t c'est sans doute juste un not-modified-since
			return $t ? $local : false;
		}
		spip_log("copie_locale : recuperation $source sur $localrac taille ".$res['length']." OK");

		// pour une eventuelle indexation
		pipeline('post_edition',
			array(
				'args' => array(
					'operation' => 'copie_locale',
					'source' => $source,
					'fichier' => $local,
					'http_res' => $res['length'],
				),
				'data' => null
			)
		);
	}

	return $local;
}

/**
 * Preparer les donnes pour un POST
 * si $donnees est une chaine
 *  - charge a l'envoyeur de la boundariser, de gerer le Content-Type etc...
 *  - on traite les retour ligne pour les mettre au bon format
 *  - on decoupe en entete/corps (separes par ligne vide)
 * si $donnees est un tableau
 *  - structuration en chaine avec boundary si necessaire ou fournie et bon Content-Type
 *
 * @param string|array $donnees
 * @param string $boundary
 * @return array
 *   entete,corps
 */
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
		$p = strpos($donnees, "\n\n");
		if ($p!==FALSE){
			$entete = str_replace("\n", "\r\n", substr($donnees, 0, $p+1));
			$donnees = substr($donnees, $p+2);
		}
		$chaine = str_replace("\n", "\r\n", $donnees);
	}
	else {
		/* boundary automatique */
		// Si on a plus de 500 octects de donnees, on "boundarise"
		if ($boundary===''){
			$taille = 0;
			foreach ($donnees as $cle => $valeur){
				if (is_array($valeur)){
					foreach ($valeur as $val2){
						$taille += strlen($val2);
					}
				} else {
					// faut-il utiliser spip_strlen() dans inc/charsets ?
					$taille += strlen($valeur);
				}
			}
			if ($taille>500){
				$boundary = substr(md5(rand() . 'spip'), 0, 8);
			}
		}

		if (is_string($boundary) and strlen($boundary)){
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

/**
 * Récupère le contenu d'une URL
 * au besoin encode son contenu dans le charset local
 *
 * @uses init_http()
 * @uses recuperer_entetes()
 * @uses recuperer_body()
 * @uses transcoder_page()
 *
 * @param string $url
 * @param array $options
 *   bool transcoder : true si on veut transcoder la page dans le charset du site
 *   string methode : Type de requête HTTP à faire (HEAD, GET ou POST)
 *   int taille_max : Arrêter le contenu au-delà (0 = seulement les entetes ==> requête HEAD). Par defaut taille_max = 1Mo ou 16Mo si copie dans un fichier
 *   string|array datas : Pour faire un POST de données (force la methode POST si non vide)
 *   string boundary : boundary pour formater les datas au format array
 *   bool refuser_gz : Pour forcer le refus de la compression (cas des serveurs orthographiques)
 *   int if_modified_since : Un timestamp unix pour arrêter la récuperation si la page distante n'a pas été modifiée depuis une date donnée
 *   string uri_referer : Pour préciser un référer différent
 *   string file : nom du fichier dans lequel copier le contenu
 *   int follow_location : nombre de redirections a suivre (0 pour ne rien suivre)
 *   string version_http : version du protocole HTTP a utiliser (par defaut defini par la constante _INC_DISTANT_VERSION_HTTP)
 * @return array|bool
 *   false si echec
 *   array sinon :
 *     int status : le status de la page
 *     string headers : les entetes de la page
 *     string page : le contenu de la page (vide si copie dans un fichier)
 *     int last_modified : timestamp de derniere modification
 *     string location : url de redirection envoyee par la page
 *     string url : url reelle de la page recuperee
 *     int length : taille du contenu ou du fichier
 *
 *     string file : nom du fichier si enregistre dans un fichier
 */
function recuperer_url($url, $options = array()){
	$default = array(
		'transcoder' => false,
		'methode' => 'GET',
		'taille_max' => null,
		'datas' => '',
		'boundary' => '',
		'refuser_gz' => false,
		'if_modified_since' => '',
		'uri_referer' => '',
		'file' => '',
		'follow_location' => 10,
		'version_http' => _INC_DISTANT_VERSION_HTTP,
	);
	$options = array_merge($default,$options);
	// copier directement dans un fichier ?
	$copy = $options['file'];

	if ($options['methode']=="HEAD")
		$options['taille_max'] = 0;
	if (is_null($options['taille_max']))
		$options['taille_max'] = $copy ? _COPIE_LOCALE_MAX_SIZE : 1048576;

	if (!empty($options['datas'])) {
		$options['methode'] = 'POST';
		list($head, $postdata) = prepare_donnees_post($options['datas'], $options['boundary']);
		if (stripos($head,"Content-Length:")===false)
			$head .= 'Content-Length: ' . strlen($postdata);
		$options['datas'] = $head . "\r\n\r\n" . $postdata;
	}

	// Accepter les URLs au format feed:// ou qui ont oublie le http://
	$url = preg_replace(',^feed://,i', 'http://', $url);
	if (!preg_match(',^[a-z]+://,i', $url)) $url = 'http://' . $url;

	$result = array('status'=>0,'headers'=>'','page'=>'','length'=>0,'last_modified'=>'','location'=>'','url'=>$url);

	// si on ecrit directement dans un fichier, pour ne pas manipuler en memoire refuser gz
	$refuser_gz = (($options['refuser_gz'] OR $copy)?true:false);

	// ouvrir la connexion et envoyer la requete et ses en-tetes
	list($handle, $fopen) = init_http($options['methode'], $url, $refuser_gz, $options['uri_referer'], $options['datas'], $options['version_http'], $options['if_modified_since']);
	if (!$handle){
		spip_log("ECHEC init_http $url");
		return false;
	}

	// Sauf en fopen, envoyer le flux d'entree
	// et recuperer les en-tetes de reponses
	if (!$fopen) {
		$res = recuperer_entetes_complets($handle, $options['if_modified_since']);
		if(!$res) {
			// Chinoisierie inexplicable pour contrer
			// les actions liberticides de l'empire du milieu
			if ($res = @file_get_contents($url)){
				$result['status'] = 200;
				$result['page'] = $res;
				return $result;
			}
		}
		elseif ($res['location'] AND $options['follow_location']){
			$options['follow_location']--;
			fclose($handle);
			include_spip('inc/filtres');
			$url = suivre_lien($url, $res['location']);
			spip_log("recuperer_url recommence sur $url");
			return recuperer_url($url,$options);
		}
		elseif($res['status']!==200){
			spip_log("HTTP status ".$res['status']." pour $url");
		}
		$result['status'] = $res['status'];
		$result['headers'] = $res['headers'];
		$result['last_modified'] = $res['last_modified'];
		$result['location'] = $res['location'];
	}

	// on ne veut que les entetes
	if (!$options['taille_max'] OR $options['methode']=='HEAD' OR $result['status']=="304")
		return $result;


	// s'il faut deballer, le faire via un fichier temporaire
	// sinon la memoire explose pour les gros flux

	$gz = false;
	if (preg_match(",\bContent-Encoding: .*gzip,is", $result['headers']))
		$gz = (_DIR_TMP . md5(uniqid(mt_rand())) . '.tmp.gz');

	$res = recuperer_body($handle, $options['taille_max'], $gz ? $gz : $copy);
	fclose($handle);
	if ($copy){
		$result['length'] = $res;
		$result['file'] = $copy;
	}
	elseif($res) {
		$result['page'] = &$res;
		$result['length'] = strlen($result['page']);

		// Decompresser au besoin
		if ($gz){
			$result['page'] = implode('', gzfile($gz));
			supprimer_fichier($gz);
		}

		// Faut-il l'importer dans notre charset local ?
		if ($options['transcoder']){
			include_spip('inc/charsets');
			$result['page'] = transcoder_page($result['page'], $result['headers']);
		}
	}

	return $result;
}

/**
 * Recuperer une URL si on l'a pas deja dans un cache fichier
 * le delai de cache est fourni par l'option delai_cache
 * les autres options et le format de retour sont identiques a recuperer_url_cache
 * @uses recuperer_url()
 *
 * @param string $url
 * @param array $options
 *   int delai_cache : anciennete acceptable pour le contenu (en seconde)
 * @return array|bool|mixed
 */
function recuperer_url_cache($url, $options = array()){
	if (!defined('_DELAI_RECUPERER_URL_CACHE')) define('_DELAI_RECUPERER_URL_CACHE',3600);
	$default = array(
		'transcoder' => false,
		'methode' => 'GET',
		'taille_max' => null,
		'datas' => '',
		'boundary' => '',
		'refuser_gz' => false,
		'if_modified_since' => '',
		'uri_referer' => '',
		'file' => '',
		'follow_location' => 10,
		'version_http' => _INC_DISTANT_VERSION_HTTP,
		'delai_cache' => _DELAI_RECUPERER_URL_CACHE,
	);
	$options = array_merge($default,$options);

	// cas ou il n'est pas possible de cacher
	if (!empty($options['data']) OR $options['methode']=='POST')
		return recuperer_url($url,$options);

	// ne pas tenter plusieurs fois la meme url en erreur (non cachee donc)
	static $errors = array();
	if (isset($errors[$url]))
		return $errors[$url];

	$sig = $options;
	unset($sig['if_modified_since']);
	unset($sig['delai_cache']);

  $dir = sous_repertoire(_DIR_CACHE,'curl');
  $cache = md5(serialize($sig))."-".substr(preg_replace(",\W+,","_",$url),80);
  $sub = sous_repertoire($dir,substr($cache,0,2));
	$cache = "$sub$cache";

	$res = false;
	$is_cached = file_exists($cache);
  if ($is_cached
    AND (filemtime($cache)>$_SERVER['REQUEST_TIME']-$options['delai_cache'])){
		lire_fichier($cache,$res);
		if ($res = unserialize($res)){
			// mettre le last_modified et le status=304 ?
		}
  }
	if (!$res){
    $res = recuperer_url($url, $options);
	  // ne pas recharger cette url non cachee dans le meme hit puisque non disponible
	  if(!$res){
		  if ($is_cached){
			  // on a pas reussi a recuperer mais on avait un cache : l'utiliser
			  lire_fichier($cache,$res);
			  $res = unserialize($res);
		  }
		  return $errors[$url] = $res;
	  }
	  ecrire_fichier($cache,serialize($res));
  }

  return $res;
}

/**
 * Obosolete : Récupère une page sur le net et au besoin l'encode dans le charset local
 *
 * Gère les redirections de page (301) sur l'URL demandée (maximum 10 redirections)
 *
 * @deprecated
 * @uses recuperer_url()
 *
 * @param string $url
 *     URL de la page à récupérer
 * @param bool|string $trans
 *     - chaîne longue : c'est un nom de fichier (nom pour sa copie locale)
 *     - true : demande d'encodage/charset
 *     - null : ne retourner que les headers
 * @param bool $get_headers
 *     Si on veut récupérer les entêtes
 * @param int|null $taille_max
 *     Arrêter le contenu au-delà (0 = seulement les entetes ==> requête HEAD).
 *     Par defaut taille_max = 1Mo.
 * @param string|array $datas
 *     Pour faire un POST de données
 * @param string $boundary
 *     Pour forcer l'envoi par cette méthode
 * @param bool $refuser_gz
 *     Pour forcer le refus de la compression (cas des serveurs orthographiques)
 * @param string $date_verif
 *     Un timestamp unix pour arrêter la récuperation si la page distante
 *     n'a pas été modifiée depuis une date donnée
 * @param string $uri_referer
 *     Pour préciser un référer différent
 * @return string|bool
 *     - Code de la page obtenue (avec ou sans entête)
 *     - false si la page n'a pu être récupérée (status different de 200)
**/
function recuperer_page($url, $trans = false, $get_headers = false,
                        $taille_max = null, $datas = '', $boundary = '', $refuser_gz = false,
                        $date_verif = '', $uri_referer = ''){
	// $copy = copier le fichier ?
	$copy = (is_string($trans) AND strlen($trans)>5); // eviter "false" :-)

	if ($taille_max==0)
		$get = 'HEAD';
	else
		$get = 'GET';

	$options = array(
		'transcoder' => $trans===true,
		'methode' => $get,
		'datas' => $datas,
		'boundary' => $boundary,
		'refuser_gz' => $refuser_gz,
		'if_modified_since' => $date_verif,
		'uri_referer' => $uri_referer,
		'file' => $copy?$trans:'',
		'follow_location' => 10,
	);
	if (!is_null($taille_max))
		$options['taille_max']=$taille_max;
	// dix tentatives maximum en cas d'entetes 301...
	$res = recuperer_url($url,$options);
	if (!$res) return false;
	if ($res['status']!==200) return false;
	if ($get_headers) return $res['headers']."\n".$res['page'];
	return $res['page'];
}


/**
 * Obsolete Récupère une page sur le net et au besoin l'encode dans le charset local
 *
 * @deprecated
 *
 * @uses recuperer_url()
 *
 * @param string $url
 *     URL de la page à récupérer
 * @param bool|null|string $trans
 *     - chaîne longue : c'est un nom de fichier (nom pour sa copie locale)
 *     - true : demande d'encodage/charset
 *     - null : ne retourner que les headers
 * @param string $get
 *     Type de requête HTTP à faire (HEAD, GET ou POST)
 * @param int|bool $taille_max
 *     Arrêter le contenu au-delà (0 = seulement les entetes ==> requête HEAD).
 *     Par defaut taille_max = 1Mo.
 * @param string|array $datas
 *     Pour faire un POST de données
 * @param bool $refuser_gz
 *     Pour forcer le refus de la compression (cas des serveurs orthographiques)
 * @param string $date_verif
 *     Un timestamp unix pour arrêter la récuperation si la page distante
 *     n'a pas été modifiée depuis une date donnée
 * @param string $uri_referer
 *     Pour préciser un référer différent
 * @return string|array|bool
 *     - Retourne l'URL en cas de 301,
 *     - Un tableau (entête, corps) si ok,
 *     - false sinon
**/
function recuperer_lapage($url, $trans = false, $get = 'GET', $taille_max = 1048576, $datas = '', $refuser_gz = false, $date_verif = '', $uri_referer = ''){
	// $copy = copier le fichier ?
	$copy = (is_string($trans) AND strlen($trans)>5); // eviter "false" :-)

	// si on ecrit directement dans un fichier, pour ne pas manipuler
	// en memoire refuser gz
	if ($copy)
		$refuser_gz = true;

	$options = array(
		'transcoder' => $trans===true,
		'methode' => $get,
		'datas' => $datas,
		'refuser_gz' => $refuser_gz,
		'if_modified_since' => $date_verif,
		'uri_referer' => $uri_referer,
		'file' => $copy?$trans:'',
		'follow_location' => false,
	);
	if (!is_null($taille_max))
		$options['taille_max']=$taille_max;
	// dix tentatives maximum en cas d'entetes 301...
	$res = recuperer_url($url,$options);

	if ($res)
		return false;
	if ($res['status']!==200) return false;
	return array($res['headers'], $res['result']);
}

/**
 * Recuperer le contenu sur lequel pointe la resource passee en argument
 * $taille_max permet de tronquer
 * de l'url dont on a deja recupere les en-tetes
 *
 * @param resource $handle
 * @param int $taille_max
 * @param string $fichier
 *   fichier dans lequel copier le contenu de la resource
 * @return bool|int|string
 *   bool false si echec
 *   int taille du fichier si argument fichier fourni
 *   string contenu de la resource
 */
function recuperer_body($handle, $taille_max = 1048576, $fichier = ''){
	$taille = 0;
	$result = '';
	$fp = false;
	if ($fichier){
		include_spip("inc/acces");
		$tmpfile = "$fichier.".creer_uniqid().".tmp";
		$fp = spip_fopen_lock($tmpfile, 'w', LOCK_EX);
		if (!$fp AND file_exists($fichier)){
			return filesize($fichier);
		}
		if (!$fp)
			return false;
		$result = 0; // on renvoie la taille du fichier
	}
	while (!feof($handle) AND $taille<$taille_max){
		$res = fread($handle, 16384);
		$taille += strlen($res);
		if ($fp){
			fwrite($fp, $res);
			$result = $taille;
		}
		else
			$result .= $res;
	}
	if ($fp){
		spip_fclose_unlock($fp);
		spip_unlink($fichier);
		@rename($tmpfile, $fichier);
		if (!file_exists($fichier))
			return false;
	}
	return $result;
}

/**
 * Lit les entetes de reponse HTTP sur la socket $handle
 * et retourne
 * false en cas d'echec,
 * un tableau associatif en cas de succes, contenant :
 * - le status
 * - le tableau complet des headers
 * - la date de derniere modif si connue
 * - l'url de redirection si specifiee
 *
 * @param resource $handle
 * @param int|bool $if_modified_since
 * @return bool|array
 *   int status
 *   string headers
 *   int last_modified
 *   string location
 */
function recuperer_entetes_complets($handle, $if_modified_since = false){
	$result = array('status'=>0,'headers'=>array(),'last_modified'=>0,'location'=>'');

	$s = @trim(fgets($handle, 16384));
	if (!preg_match(',^HTTP/[0-9]+\.[0-9]+ ([0-9]+),', $s, $r)){
		return false;
	}
	$result['status'] = intval($r[1]);
	while ($s = trim(fgets($handle, 16384))){
		$result['headers'][] = $s . "\n";
		preg_match(',^([^:]*): *(.*)$,i', $s, $r);
		list(, $d, $v) = $r;
		if (strtolower(trim($d))=='location' AND $result['status']>=300 AND $result['status']<400){
			$result['location'] = $v;
		}
		elseif ($d=='Last-Modified') {
			$result['last_modified'] = strtotime($v);
		}
	}
	if ($if_modified_since
	  AND $result['last_modified']
	  AND $if_modified_since>$result['last_modified']
		AND $result['status']==200)
		$result['status'] = 304;

	$result['headers'] = implode('', $result['headers']);

	return $result;
}

/**
 * Obsolete : version simplifiee de recuperer_entetes_complets
 * Retourne les informations d'entête HTTP d'un socket
 *
 * Lit les entêtes de reponse HTTP sur la socket $f
 *
 * @uses recuperer_entetes_complets()
 * @deprecated
 *
 * @param resource $f
 *     Socket d'un fichier (issu de fopen)
 * @param int|string $date_verif
 *     Pour tester une date de dernière modification
 * @return string|int|array
 *     - la valeur (chaîne) de l'en-tete Location si on l'a trouvée
 *     - la valeur (numerique) du statut si different de 200, notamment Not-Modified
 *     - le tableau des entetes dans tous les autres cas
**/
function recuperer_entetes($f, $date_verif = ''){
	//Cas ou la page distante n'a pas bouge depuis
	//la derniere visite
	$res = recuperer_entetes_complets($f, $date_verif);
	if (!$res) return false;
	if ($res['location']) return $res['location'];
	if ($res['status']!=200) return $res['status'];
	return explode("\n",$res['headers']);
}

/**
 * Calcule le nom canonique d'une copie local d'un fichier distant
 *
 * Si on doit conserver une copie locale des fichiers distants, autant que ca
 * soit à un endroit canonique
 *
 * @note
 *   Si ca peut être bijectif c'est encore mieux,
 *   mais là tout de suite je ne trouve pas l'idee, étant donné les limitations
 *   des filesystems
 *
 * @param string $source
 *     URL de la source
 * @param string $extension
 *     Extension du fichier
 * @return string
 *     Nom du fichier pour copie locale
**/
function nom_fichier_copie_locale($source, $extension){
	include_spip('inc/documents');

	$d = creer_repertoire_documents('distant'); # IMG/distant/
	$d = sous_repertoire($d, $extension); # IMG/distant/pdf/

	// on se place tout le temps comme si on etait a la racine
	if (_DIR_RACINE)
		$d = preg_replace(',^' . preg_quote(_DIR_RACINE) . ',', '', $d);

	$m = md5($source);

	return $d
		. substr(preg_replace(',[^\w-],', '', basename($source)) . '-' . $m, 0, 12)
		. substr($m, 0, 4)
		. ".$extension";
}

/**
 * Donne le nom de la copie locale de la source
 *
 * Soit obtient l'extension du fichier directement de l'URL de la source,
 * soit tente de le calculer.
 *
 * @uses nom_fichier_copie_locale()
 * @uses recuperer_infos_distantes()
 *
 * @param string $source
 *      URL de la source distante
 * @return string
 *      Nom du fichier calculé
**/
function fichier_copie_locale($source){
	// Si c'est deja local pas de souci
	if (!preg_match(',^\w+://,', $source)){
		if (_DIR_RACINE)
			$source = preg_replace(',^' . preg_quote(_DIR_RACINE) . ',', '', $source);
		return $source;
	}

	// optimisation : on regarde si on peut deviner l'extension dans l'url et si le fichier
	// a deja ete copie en local avec cette extension
	// dans ce cas elle est fiable, pas la peine de requeter en base
	$path_parts = pathinfo($source);
	$ext = $path_parts ? $path_parts['extension'] : '';
	if ($ext
	AND preg_match(',^\w+$,', $ext) // pas de php?truc=1&...
	AND $f = nom_fichier_copie_locale($source, $ext)
	AND file_exists(_DIR_RACINE . $f)
	)
		return $f;


	// Si c'est deja dans la table des documents,
	// ramener le nom de sa copie potentielle

	$ext = sql_getfetsel("extension", "spip_documents", "fichier=" . sql_quote($source) . " AND distant='oui' AND extension <> ''");


	if ($ext) return nom_fichier_copie_locale($source, $ext);

	// voir si l'extension indiquee dans le nom du fichier est ok
	// et si il n'aurait pas deja ete rapatrie

	$ext = $path_parts ? $path_parts['extension'] : '';

	if ($ext AND sql_getfetsel("extension", "spip_types_documents", "extension=" . sql_quote($ext))){
		$f = nom_fichier_copie_locale($source, $ext);
		if (file_exists(_DIR_RACINE . $f))
			return $f;
	}

	// Ping  pour voir si son extension est connue et autorisee
	// avec mise en cache du resultat du ping

	$cache = sous_repertoire(_DIR_CACHE, 'rid') . md5($source);
	if (!@file_exists($cache)
		OR !$path_parts = @unserialize(spip_file_get_contents($cache))
		OR _request('var_mode')=='recalcul'
	){
		$path_parts = recuperer_infos_distantes($source, 0, false);
		ecrire_fichier($cache, serialize($path_parts));
	}
	$ext = $path_parts ? $path_parts['extension'] : '';
	if ($ext AND sql_getfetsel("extension", "spip_types_documents", "extension=" . sql_quote($ext))){
		return nom_fichier_copie_locale($source, $ext);
	}
	spip_log("pas de copie locale pour $source");
}


/**
 * Récupérer les infos d'un document distant, sans trop le télécharger
 *
 * @param string $source
 *     URL de la source
 * @param int $max
 *     Taille maximum du fichier à télécharger
 * @param bool $charger_si_petite_image
 *     Pour télécharger le document s'il est petit
 * @return array
 *     Couples des informations obtenues parmis :
 *
 *     - 'body' = chaine
 *     - 'type_image' = booleen
 *     - 'titre' = chaine
 *     - 'largeur' = intval
 *     - 'hauteur' = intval
 *     - 'taille' = intval
 *     - 'extension' = chaine
 *     - 'fichier' = chaine
**/
function recuperer_infos_distantes($source, $max = 0, $charger_si_petite_image = true){

	# charger les alias des types mime
	include_spip('base/typedoc');
	global $mime_alias;

	$a = array();
	$mime_type = '';
	// On va directement charger le debut des images et des fichiers html,
	// de maniere a attrapper le maximum d'infos (titre, taille, etc). Si
	// ca echoue l'utilisateur devra les entrer...
	if ($headers = recuperer_page($source, false, true, $max, '', '', true)){
		list($headers, $a['body']) = preg_split(',\n\n,', $headers, 2);

		if (preg_match(",\nContent-Type: *([^[:space:];]*),i", "\n$headers", $regs))
			$mime_type = (trim($regs[1]));
		else
			$mime_type = ''; // inconnu

		// Appliquer les alias
		while (isset($mime_alias[$mime_type]))
			$mime_type = $mime_alias[$mime_type];

		// Si on a un mime-type insignifiant
		// text/plain,application/octet-stream ou vide
		// c'est peut-etre que le serveur ne sait pas
		// ce qu'il sert ; on va tenter de detecter via l'extension de l'url
		// ou le Content-Disposition: attachment; filename=...
		$t = null;
		if (in_array($mime_type, array('text/plain', '', 'application/octet-stream'))){
			if (!$t
				AND preg_match(',\.([a-z0-9]+)(\?.*)?$,i', $source, $rext)
			){
				$t = sql_fetsel("extension", "spip_types_documents", "extension=" . sql_quote($rext[1],'','text'));
			}
			if (!$t
				AND preg_match(",^Content-Disposition:\s*attachment;\s*filename=(.*)$,Uims", $headers, $m)
				AND preg_match(',\.([a-z0-9]+)(\?.*)?$,i', $m[1], $rext)
			){
				$t = sql_fetsel("extension", "spip_types_documents", "extension=" . sql_quote($rext[1],'','text'));
			}
		}

		// Autre mime/type (ou text/plain avec fichier d'extension inconnue)
		if (!$t)
			$t = sql_fetsel("extension", "spip_types_documents", "mime_type=" . sql_quote($mime_type));

		// Toujours rien ? (ex: audio/x-ogg au lieu de application/ogg)
		// On essaie de nouveau avec l'extension
		if (!$t
			AND $mime_type!='text/plain'
			AND preg_match(',\.([a-z0-9]+)(\?.*)?$,i', $source, $rext)
		){
			$t = sql_fetsel("extension", "spip_types_documents", "extension=" . sql_quote($rext[1],'','text')); # eviter xxx.3 => 3gp (> SPIP 3)
		}


		if ($t){
			spip_log("mime-type $mime_type ok, extension " . $t['extension']);
			$a['extension'] = $t['extension'];
		} else {
			# par defaut on retombe sur '.bin' si c'est autorise
			spip_log("mime-type $mime_type inconnu");
			$t = sql_fetsel("extension", "spip_types_documents", "extension='bin'");
			if (!$t) return false;
			$a['extension'] = $t['extension'];
		}

		if (preg_match(",\nContent-Length: *([^[:space:]]*),i",
			"\n$headers", $regs)
		)
			$a['taille'] = intval($regs[1]);
	}

	// Echec avec HEAD, on tente avec GET
	if (!$a AND !$max){
		spip_log("tenter GET $source");
		$a = recuperer_infos_distantes($source, 1024*1024);
	}

	// S'il s'agit d'une image pas trop grosse ou d'un fichier html, on va aller
	// recharger le document en GET et recuperer des donnees supplementaires...
	if (preg_match(',^image/(jpeg|gif|png|swf),', $mime_type)){
		if ($max==0
			AND $a['taille']<1024*1024
				AND (strpos($GLOBALS['meta']['formats_graphiques'], $a['extension'])!==false)
					AND $charger_si_petite_image
		){
			$a = recuperer_infos_distantes($source, 1024*1024);
		}
		else if ($a['body']){
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
	if ($a AND $a['extension']=='swf'
		AND !$a['largeur']
	){
		$a['largeur'] = 425;
		$a['hauteur'] = 350;
	}

	if ($mime_type=='text/html'){
		include_spip('inc/filtres');
		$page = recuperer_page($source, true, false, 1024*1024);
		if (preg_match(',<title>(.*?)</title>,ims', $page, $regs))
			$a['titre'] = corriger_caracteres(trim($regs[1]));
		if (!$a['taille']) $a['taille'] = strlen($page); # a peu pres
	}

	return $a;
}


/**
 * Tester si un host peut etre recuperer directement ou doit passer par un proxy
 * on peut passer en parametre le proxy et la liste des host exclus,
 * pour les besoins des tests, lors de la configuration
 *
 * @param string $host
 * @param string $http_proxy
 * @param string $http_noproxy
 * @return string
 */
function need_proxy($host, $http_proxy = null, $http_noproxy = null){
	if (is_null($http_proxy))
		$http_proxy = @$GLOBALS['meta']["http_proxy"];
	if (is_null($http_noproxy))
		$http_noproxy = @$GLOBALS['meta']["http_noproxy"];

	$domain = substr($host, strpos($host, '.'));

	return ($http_proxy
		AND (strpos(" $http_noproxy ", " $host ")===false
		AND (strpos(" $http_noproxy ", " $domain ")===false)))
		? $http_proxy : '';
}


/**
 * Initialise une requete HTTP avec entetes
 * decompose l'url en son schema+host+path+port
 * et lance la requete
 * retourne le descripteur sur lequel lire la reponse
 *
 * @uses lance_requete()
 *
 * @param string $method
 *   HEAD, GET, POST
 * @param string $url
 * @param bool $refuse_gz
 * @param string $referer
 * @param string $datas
 * @param string $vers
 * @param string $date
 * @return array
 */
function init_http($method, $url, $refuse_gz = false, $referer = '', $datas = "", $vers = "HTTP/1.0", $date = ''){
	$user = $via_proxy = $proxy_user = '';
	$fopen = false;

	$t = @parse_url($url);
	$host = $t['host'];
	if ($t['scheme']=='http'){
		$scheme = 'http';
		$noproxy = '';
	} elseif ($t['scheme']=='https') {
		$scheme = 'ssl';
		$noproxy = 'ssl://';
		if (!isset($t['port']) || !($port = $t['port'])) $t['port'] = 443;
	}
	else {
		$scheme = $t['scheme'];
		$noproxy = $scheme . '://';
	}
	if (isset($t['user']))
		$user = array($t['user'], $t['pass']);

	if (!isset($t['port']) || !($port = $t['port'])) $port = 80;
	if (!isset($t['path']) || !($path = $t['path'])) $path = "/";
	if (@$t['query']) $path .= "?" . $t['query'];

	$f = lance_requete($method, $scheme, $user, $host, $path, $port, $noproxy, $refuse_gz, $referer, $datas, $vers, $date);
	if (!$f){
		// fallback : fopen
		if (!_request('tester_proxy')){
			$f = @fopen($url, "rb");
			spip_log("connexion vers $url par simple fopen");
			$fopen = true;
		}
		else
			$f = false;
		// echec total
	}

	return array($f, $fopen);
}

/**
 * Lancer la requete proprement dite
 * @param string $method
 *   type de la requete (GET, HEAD, POST...)
 * @param string $scheme
 *   protocole (http, ssl, ftp...)
 * @param array $user
 *   couple (utilisateur, mot de passe) en cas d'authentification http
 * @param string $host
 *   nom de domaine
 * @param string $path
 *   chemin de la page cherchee
 * @param string $port
 *   port utilise pour la connexion
 * @param bool $noproxy
 *   protocole utilise si requete sans proxy
 * @param bool $refuse_gz
 *   refuser la compression GZ
 * @param string $referer
 *   referer
 * @param string $datas
 *   donnees postees
 * @param string $vers
 *   version HTTP
 * @param int|string $date
 *   timestamp pour entente If-Modified-Since
 * @return bool|resource
 *   false si echec
 *   resource socket vers l'url demandee
 */
function lance_requete($method, $scheme, $user, $host, $path, $port, $noproxy, $refuse_gz = false, $referer = '', $datas = "", $vers = "HTTP/1.0", $date = ''){

	$proxy_user = '';
	$http_proxy = need_proxy($host);
	if ($user) $user = urlencode($user[0]) . ":" . urlencode($user[1]);

	if ($http_proxy){
		$path = (($scheme=='ssl') ? 'https://' : "$scheme://")
			. (!$user ? '' : "$user@")
			. "$host" . (($port!=80) ? ":$port" : "") . $path;
		$t2 = @parse_url($http_proxy);
		$first_host = $t2['host'];
		if (!($port = $t2['port'])) $port = 80;
		if ($t2['user'])
			$proxy_user = base64_encode($t2['user'] . ":" . $t2['pass']);
	}
	else
		$first_host = $noproxy . $host;

	$f = @fsockopen($first_host, $port);
	spip_log("Recuperer $path sur $first_host:$port par $f");
	if (!$f) return false;

	$site = $GLOBALS['meta']["adresse_site"];

	$req = "$method $path $vers\r\n"
		. "Host: $host\r\n"
		. "User-Agent: " . _INC_DISTANT_USER_AGENT . "\r\n"
		. ($refuse_gz ? '' : ("Accept-Encoding: " . _INC_DISTANT_CONTENT_ENCODING . "\r\n"))
		. (!$site ? '' : "Referer: $site/$referer\r\n")
		. (!$date ? '' : "If-Modified-Since: " . (gmdate("D, d M Y H:i:s", $date) . " GMT\r\n"))
		. (!$user ? '' : ("Authorization: Basic " . base64_encode($user) . "\r\n"))
		. (!$proxy_user ? '' : "Proxy-Authorization: Basic $proxy_user\r\n")
		. (!strpos($vers, '1.1') ? '' : "Keep-Alive: 300\r\nConnection: keep-alive\r\n");

#	spip_log("Requete\n$req");
	fputs($f, $req);
	fputs($f, $datas ? $datas : "\r\n");
	return $f;
}

?>
