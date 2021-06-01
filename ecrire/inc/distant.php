<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Ce fichier gère l'obtention de données distantes
 *
 * @package SPIP\Core\Distant
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

if (!defined('_INC_DISTANT_VERSION_HTTP')) {
	define('_INC_DISTANT_VERSION_HTTP', 'HTTP/1.0');
}
if (!defined('_INC_DISTANT_CONTENT_ENCODING')) {
	define('_INC_DISTANT_CONTENT_ENCODING', 'gzip');
}
if (!defined('_INC_DISTANT_USER_AGENT')) {
	define('_INC_DISTANT_USER_AGENT', 'SPIP-' . $GLOBALS['spip_version_affichee'] . ' (' . $GLOBALS['home_server'] . ')');
}
if (!defined('_INC_DISTANT_MAX_SIZE')) {
	define('_INC_DISTANT_MAX_SIZE', 2097152);
}
if (!defined('_INC_DISTANT_CONNECT_TIMEOUT')) {
	define('_INC_DISTANT_CONNECT_TIMEOUT', 10);
}

define('_REGEXP_COPIE_LOCALE', ',' 	.
	preg_replace(
		'@^https?:@',
		'https?:',
		(isset($GLOBALS['meta']['adresse_site']) ? $GLOBALS['meta']['adresse_site'] : '')
	)
	. '/?spip.php[?]action=acceder_document.*file=(.*)$,');

//@define('_COPIE_LOCALE_MAX_SIZE',2097152); // poids (inc/utils l'a fait)

/**
 * Crée au besoin la copie locale d'un fichier distant
 *
 * Prend en argument un chemin relatif au rep racine, ou une URL
 * Renvoie un chemin relatif au rep racine, ou false
 *
 * @link https://www.spip.net/4155
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
 * @param int $taille_max
 *   taille maxi de la copie local, par defaut _COPIE_LOCALE_MAX_SIZE
 * @return bool|string
 */
function copie_locale($source, $mode = 'auto', $local = null, $taille_max = null) {

	// si c'est la protection de soi-meme, retourner le path
	if ($mode !== 'force' and preg_match(_REGEXP_COPIE_LOCALE, $source, $match)) {
		$source = substr(_DIR_IMG, strlen(_DIR_RACINE)) . urldecode($match[1]);

		return @file_exists($source) ? $source : false;
	}

	if (is_null($local)) {
		$local = fichier_copie_locale($source);
	} else {
		if (_DIR_RACINE and strncmp(_DIR_RACINE, $local, strlen(_DIR_RACINE)) == 0) {
			$local = substr($local, strlen(_DIR_RACINE));
		}
	}

	// si $local = '' c'est un fichier refuse par fichier_copie_locale(),
	// par exemple un fichier qui ne figure pas dans nos documents ;
	// dans ce cas on n'essaie pas de le telecharger pour ensuite echouer
	if (!$local) {
		return false;
	}

	$localrac = _DIR_RACINE . $local;
	$t = ($mode == 'force') ? false : @file_exists($localrac);

	// test d'existence du fichier
	if ($mode == 'test') {
		return $t ? $local : '';
	}

	// sinon voir si on doit/peut le telecharger
	if ($local == $source or !tester_url_absolue($source)) {
		return $local;
	}

	if ($mode == 'modif' or !$t) {
		// passer par un fichier temporaire unique pour gerer les echecs en cours de recuperation
		// et des eventuelles recuperations concurantes
		include_spip('inc/acces');
		if (!$taille_max) {
			$taille_max = _COPIE_LOCALE_MAX_SIZE;
		}
		$res = recuperer_url(
			$source,
			array('file' => $localrac, 'taille_max' => $taille_max, 'if_modified_since' => $t ? filemtime($localrac) : '')
		);
		if (!$res or (!$res['length'] and $res['status'] != 304)) {
			spip_log("copie_locale : Echec recuperation $source sur $localrac status : " . $res['status'], 'distant' . _LOG_INFO_IMPORTANTE);
		}
		if (!$res['length']) {
			// si $t c'est sans doute juste un not-modified-since
			return $t ? $local : false;
		}
		spip_log("copie_locale : recuperation $source sur $localrac taille " . $res['length'] . ' OK', 'distant');

		// pour une eventuelle indexation
		pipeline(
			'post_edition',
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
 * Valider qu'une URL d'un document distant est bien distante
 * et pas une url localhost qui permet d'avoir des infos sur le serveur
 * inspiree de https://core.trac.wordpress.org/browser/trunk/src/wp-includes/http.php?rev=36435#L500
 * 
 * @param string $url
 * @param array $known_hosts
 *   url/hosts externes connus et acceptes
 * @return false|string 
 *   url ou false en cas d'echec
 */
function valider_url_distante($url, $known_hosts = array()) {
	if (!function_exists('protocole_verifier')){
		include_spip('inc/filtres_mini');
	}

	if (!protocole_verifier($url, array('http', 'https'))) {
		return false;
	}
	
	$parsed_url = parse_url($url);
	if (!$parsed_url or empty($parsed_url['host']) ) {
		return false;
	}

	if (isset($parsed_url['user']) or isset($parsed_url['pass'])) {
		return false;
	}

	if (false !== strpbrk($parsed_url['host'], ':#?[]')) {
		return false;
	}

	if (!is_array($known_hosts)) {
		$known_hosts = array($known_hosts);
	}
	$known_hosts[] = $GLOBALS['meta']['adresse_site'];
	$known_hosts[] = url_de_base();
	$known_hosts = pipeline('declarer_hosts_distants', $known_hosts);

	$is_known_host = false;
	foreach ($known_hosts as $known_host) {
		$parse_known = parse_url($known_host);
		if ($parse_known
		  and strtolower($parse_known['host']) === strtolower($parsed_url['host'])) {
			$is_known_host = true;
			break;
		}
	}

	if (!$is_known_host) {
		$host = trim($parsed_url['host'], '.');
		if (preg_match('#^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$#', $host)) {
			$ip = $host;
		} else {
			$ip = gethostbyname($host);
			if ($ip === $host) {
				// Error condition for gethostbyname()
				$ip = false;
			}
		}
		if ($ip) {
			$parts = array_map('intval', explode( '.', $ip ));
			if (127 === $parts[0] or 10 === $parts[0] or 0 === $parts[0]
			  or ( 172 === $parts[0] and 16 <= $parts[1] and 31 >= $parts[1] )
			  or ( 192 === $parts[0] && 168 === $parts[1] )
			) {
				return false;
			}
		}
	}

	if (empty($parsed_url['port'])) {
		return $url;
	}

	$port = $parsed_url['port'];
	if ($port === 80  or $port === 443  or $port === 8080) {
		return $url;
	}

	if ($is_known_host) {
		foreach ($known_hosts as $known_host) {
			$parse_known = parse_url($known_host);
			if ($parse_known
				and !empty($parse_known['port'])
			  and strtolower($parse_known['host']) === strtolower($parsed_url['host'])
			  and $parse_known['port'] == $port) {
				return $url;
			}
		}
	}

	return false;
}

/**
 * Preparer les donnes pour un POST
 * si $donnees est une chaine
 *  - charge a l'envoyeur de la boundariser, de gerer le Content-Type,
 *    de séparer les entetes des données par une ligne vide etc...
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
	if (is_string($donnees) && strlen($donnees)) {
		$entete = '';
		// on repasse tous les \r\n et \r en simples \n
		$donnees = str_replace("\r\n", "\n", $donnees);
		$donnees = str_replace("\r", "\n", $donnees);
		// un double retour a la ligne signifie la fin de l'entete et le debut des donnees
		$p = strpos($donnees, "\n\n");
		if ($p !== false) {
			$entete = str_replace("\n", "\r\n", substr($donnees, 0, $p + 1));
			$donnees = substr($donnees, $p + 2);
		}
		$chaine = str_replace("\n", "\r\n", $donnees);
	} else {
		/* boundary automatique */
		// Si on a plus de 500 octects de donnees, on "boundarise"
		if ($boundary === '') {
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
			if ($taille > 500) {
				$boundary = substr(md5(rand() . 'spip'), 0, 8);
			}
		}

		if (is_string($boundary) and strlen($boundary)) {
			// fabrique une chaine HTTP pour un POST avec boundary
			$entete = "Content-Type: multipart/form-data; boundary=$boundary\r\n";
			$chaine = '';
			if (is_array($donnees)) {
				foreach ($donnees as $cle => $valeur) {
					if (is_array($valeur)) {
						foreach ($valeur as $val2) {
							$chaine .= "\r\n--$boundary\r\n";
							$chaine .= "Content-Disposition: form-data; name=\"{$cle}[]\"\r\n";
							$chaine .= "\r\n";
							$chaine .= $val2;
						}
					} else {
						$chaine .= "\r\n--$boundary\r\n";
						$chaine .= "Content-Disposition: form-data; name=\"$cle\"\r\n";
						$chaine .= "\r\n";
						$chaine .= $valeur;
					}
				}
				$chaine .= "\r\n--$boundary\r\n";
			}
		} else {
			// fabrique une chaine HTTP simple pour un POST
			$entete = 'Content-Type: application/x-www-form-urlencoded' . "\r\n";
			$chaine = array();
			if (is_array($donnees)) {
				foreach ($donnees as $cle => $valeur) {
					if (is_array($valeur)) {
						foreach ($valeur as $val2) {
							$chaine[] = rawurlencode($cle) . '[]=' . rawurlencode($val2);
						}
					} else {
						$chaine[] = rawurlencode($cle) . '=' . rawurlencode($valeur);
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
 * Convertir une URL dont le host est en utf8 en ascii
 * Utilise la librairie https://github.com/phlylabs/idna-convert/tree/v0.9.1
 * dans sa derniere version compatible toutes version PHP 5
 * La fonction PHP idn_to_ascii depend d'un package php5-intl et est rarement disponible
 *
 * @param string $url_idn
 * @return array|string
 */
function url_to_ascii($url_idn) {

	if ($parts = parse_url($url_idn)) {
		$host = $parts['host'];
		if (!preg_match(',^[a-z0-9_\.\-]+$,i', $host)) {
			include_spip('inc/idna_convert.class');
			$IDN = new idna_convert();
			$host_ascii = $IDN->encode($host);
			$url_idn = explode($host, $url_idn, 2);
			$url_idn = implode($host_ascii, $url_idn);
		}
		// et on urlencode les char utf si besoin dans le path
		$url_idn = preg_replace_callback('/[^\x20-\x7f]/', function($match) { return urlencode($match[0]); }, $url_idn);
	}

	return $url_idn;
}

/**
 * Récupère le contenu d'une URL
 * au besoin encode son contenu dans le charset local
 *
 * @uses init_http()
 * @uses recuperer_entetes_complets()
 * @uses recuperer_body()
 * @uses transcoder_page()
 * @uses prepare_donnees_post()
 *
 * @param string $url
 * @param array $options
 *   bool transcoder : true si on veut transcoder la page dans le charset du site
 *   string methode : Type de requête HTTP à faire (HEAD, GET, POST, PUT, DELETE)
 *   int taille_max : Arrêter le contenu au-delà (0 = seulement les entetes ==> requête HEAD). Par defaut taille_max = 1Mo ou 16Mo si copie dans un fichier
 *   array headers : tableau associatif d'entetes https a envoyer
 *   string|array datas : Pour envoyer des donnees (array) et/ou entetes au complet, avec saut de ligne entre headers et donnees ( string @see prepare_donnees_post()) (force la methode POST si donnees non vide)
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
function recuperer_url($url, $options = array()) {
	// Conserve la mémoire de la méthode fournit éventuellement
	$methode_demandee = $options['methode'] ?? '';
	$default = array(
		'transcoder' => false,
		'methode' => 'GET',
		'taille_max' => null,
		'headers' => [],
		'datas' => '',
		'boundary' => '',
		'refuser_gz' => false,
		'if_modified_since' => '',
		'uri_referer' => '',
		'file' => '',
		'follow_location' => 10,
		'version_http' => _INC_DISTANT_VERSION_HTTP,
	);
	$options = array_merge($default, $options);
	// copier directement dans un fichier ?
	$copy = $options['file'];

	if ($options['methode'] == 'HEAD') {
		$options['taille_max'] = 0;
	}
	if (is_null($options['taille_max'])) {
		$options['taille_max'] = $copy ? _COPIE_LOCALE_MAX_SIZE : _INC_DISTANT_MAX_SIZE;
	}


	// Ajout des en-têtes spécifiques si besoin
	$head_add = '';
	if (!empty($options['headers'])) {
		foreach ($options['headers'] as $champ => $valeur) {
			$head_add .= $champ . ': ' . $valeur . "\r\n";
		}
		// ne pas le repasser a recuperer_url si on follow un location, car ils seront dans datas
		unset($options['entetes']);
	}

	if (!empty($options['datas'])) {
		list($head, $postdata) = prepare_donnees_post($options['datas'], $options['boundary']);
		$head .= $head_add;
		if (stripos($head, 'Content-Length:') === false) {
			$head .= 'Content-Length: ' . strlen($postdata) . "\r\n";
		}
		$options['datas'] = $head . "\r\n" . $postdata;
		if (
			strlen($postdata)
			and !$methode_demandee
		) {
			$options['methode'] = 'POST';
		}
	} elseif ($head_add) {
		$options['datas'] = $head_add . "\r\n";
	}

	// Accepter les URLs au format feed:// ou qui ont oublie le http:// ou les urls relatives au protocole
	$url = preg_replace(',^feed://,i', 'http://', $url);
	if (!tester_url_absolue($url)) {
		$url = 'http://' . $url;
	} elseif (strncmp($url, '//', 2) == 0) {
		$url = 'http:' . $url;
	}

	$url = url_to_ascii($url);

	$result = array(
		'status' => 0,
		'headers' => '',
		'page' => '',
		'length' => 0,
		'last_modified' => '',
		'location' => '',
		'url' => $url
	);

	// si on ecrit directement dans un fichier, pour ne pas manipuler en memoire refuser gz
	$refuser_gz = (($options['refuser_gz'] or $copy) ? true : false);

	// ouvrir la connexion et envoyer la requete et ses en-tetes
	list($handle, $fopen) = init_http(
		$options['methode'],
		$url,
		$refuser_gz,
		$options['uri_referer'],
		$options['datas'],
		$options['version_http'],
		$options['if_modified_since']
	);
	if (!$handle) {
		spip_log("ECHEC init_http $url", 'distant' . _LOG_ERREUR);

		return false;
	}

	// Sauf en fopen, envoyer le flux d'entree
	// et recuperer les en-tetes de reponses
	if (!$fopen) {
		$res = recuperer_entetes_complets($handle, $options['if_modified_since']);
		if (!$res) {
			fclose($handle);
			$t = @parse_url($url);
			$host = $t['host'];
			// Chinoisierie inexplicable pour contrer
			// les actions liberticides de l'empire du milieu
			if (!need_proxy($host)
				and $res = @file_get_contents($url)
			) {
				$result['length'] = strlen($res);
				if ($copy) {
					ecrire_fichier($copy, $res);
					$result['file'] = $copy;
				} else {
					$result['page'] = $res;
				}
				$res = array(
					'status' => 200,
				);
			} else {
				spip_log("ECHEC chinoiserie $url", 'distant' . _LOG_ERREUR);
				return false;
			}
		} elseif ($res['location'] and $options['follow_location']) {
			$options['follow_location']--;
			fclose($handle);
			include_spip('inc/filtres');
			$url = suivre_lien($url, $res['location']);
			spip_log("recuperer_url recommence sur $url", 'distant');

			return recuperer_url($url, $options);
		} elseif ($res['status'] !== 200) {
			spip_log('HTTP status ' . $res['status'] . " pour $url", 'distant');
		}
		$result['status'] = $res['status'];
		if (isset($res['headers'])) {
			$result['headers'] = $res['headers'];
		}
		if (isset($res['last_modified'])) {
			$result['last_modified'] = $res['last_modified'];
		}
		if (isset($res['location'])) {
			$result['location'] = $res['location'];
		}
	}

	// on ne veut que les entetes
	if (!$options['taille_max'] or $options['methode'] == 'HEAD' or $result['status'] == '304') {
		return $result;
	}


	// s'il faut deballer, le faire via un fichier temporaire
	// sinon la memoire explose pour les gros flux

	$gz = false;
	if (preg_match(",\bContent-Encoding: .*gzip,is", $result['headers'])) {
		$gz = (_DIR_TMP . md5(uniqid(mt_rand())) . '.tmp.gz');
	}

	// si on a pas deja recuperer le contenu par une methode detournee
	if (!$result['length']) {
		$res = recuperer_body($handle, $options['taille_max'], $gz ? $gz : $copy);
		fclose($handle);
		if ($copy) {
			$result['length'] = $res;
			$result['file'] = $copy;
		} elseif ($res) {
			$result['page'] = &$res;
			$result['length'] = strlen($result['page']);
		}
		if (!$result['status']) {
			$result['status'] = 200; // on a reussi, donc !
		}
	}
	if (!$result['page']) {
		return $result;
	}

	// Decompresser au besoin
	if ($gz) {
		$result['page'] = implode('', gzfile($gz));
		supprimer_fichier($gz);
	}

	// Faut-il l'importer dans notre charset local ?
	if ($options['transcoder']) {
		include_spip('inc/charsets');
		$result['page'] = transcoder_page($result['page'], $result['headers']);
	}

	return $result;
}

/**
 * Récuperer une URL si on l'a pas déjà dans un cache fichier
 *
 * Le délai de cache est fourni par l'option `delai_cache`
 * Les autres options et le format de retour sont identiques à la fonction `recuperer_url`
 * @uses recuperer_url()
 *
 * @param string $url
 * @param array $options
 *   int delai_cache : anciennete acceptable pour le contenu (en seconde)
 * @return array|bool|mixed
 */
function recuperer_url_cache($url, $options = array()) {
	if (!defined('_DELAI_RECUPERER_URL_CACHE')) {
		define('_DELAI_RECUPERER_URL_CACHE', 3600);
	}
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
		'delai_cache' => in_array(_VAR_MODE, ['preview', 'recalcul']) ? 0 : _DELAI_RECUPERER_URL_CACHE,
	);
	$options = array_merge($default, $options);

	// cas ou il n'est pas possible de cacher
	if (!empty($options['data']) or $options['methode'] == 'POST') {
		return recuperer_url($url, $options);
	}

	// ne pas tenter plusieurs fois la meme url en erreur (non cachee donc)
	static $errors = array();
	if (isset($errors[$url])) {
		return $errors[$url];
	}

	$sig = $options;
	unset($sig['if_modified_since']);
	unset($sig['delai_cache']);
	$sig['url'] = $url;

	$dir = sous_repertoire(_DIR_CACHE, 'curl');
	$cache = md5(serialize($sig)) . '-' . substr(preg_replace(',\W+,', '_', $url), 0, 80);
	$sub = sous_repertoire($dir, substr($cache, 0, 2));
	$cache = "$sub$cache";

	$res = false;
	$is_cached = file_exists($cache);
	if ($is_cached
		and (filemtime($cache) > $_SERVER['REQUEST_TIME'] - $options['delai_cache'])
	) {
		lire_fichier($cache, $res);
		if ($res = unserialize($res)) {
			// mettre le last_modified et le status=304 ?
		}
	}
	if (!$res) {
		$res = recuperer_url($url, $options);
		// ne pas recharger cette url non cachee dans le meme hit puisque non disponible
		if (!$res) {
			if ($is_cached) {
				// on a pas reussi a recuperer mais on avait un cache : l'utiliser
				lire_fichier($cache, $res);
				$res = unserialize($res);
			}

			return $errors[$url] = $res;
		}
		ecrire_fichier($cache, serialize($res));
	}

	return $res;
}

/**
 * Obsolète : Récupère une page sur le net et au besoin l'encode dans le charset local
 *
 * Gère les redirections de page (301) sur l'URL demandée (maximum 10 redirections)
 *
 * @deprecated 3.1
 * @see recuperer_url()
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
function recuperer_page(
	$url,
	$trans = false,
	$get_headers = false,
	$taille_max = null,
	$datas = '',
	$boundary = '',
	$refuser_gz = false,
	$date_verif = '',
	$uri_referer = ''
) {
	// $copy = copier le fichier ?
	$copy = (is_string($trans) and strlen($trans) > 5); // eviter "false" :-)

	if (!is_null($taille_max) and ($taille_max == 0)) {
		$get = 'HEAD';
	} else {
		$get = 'GET';
	}

	$options = array(
		'transcoder' => $trans === true,
		'methode' => $get,
		'datas' => $datas,
		'boundary' => $boundary,
		'refuser_gz' => $refuser_gz,
		'if_modified_since' => $date_verif,
		'uri_referer' => $uri_referer,
		'file' => $copy ? $trans : '',
		'follow_location' => 10,
	);
	if (!is_null($taille_max)) {
		$options['taille_max'] = $taille_max;
	}
	// dix tentatives maximum en cas d'entetes 301...
	$res = recuperer_url($url, $options);
	if (!$res) {
		return false;
	}
	if ($res['status'] !== 200) {
		return false;
	}
	if ($get_headers) {
		return $res['headers'] . "\n" . $res['page'];
	}

	return $res['page'];
}


/**
 * Obsolete Récupère une page sur le net et au besoin l'encode dans le charset local
 *
 * @deprecated 3.1
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
function recuperer_lapage(
	$url,
	$trans = false,
	$get = 'GET',
	$taille_max = 1048576,
	$datas = '',
	$refuser_gz = false,
	$date_verif = '',
	$uri_referer = ''
) {
	// $copy = copier le fichier ?
	$copy = (is_string($trans) and strlen($trans) > 5); // eviter "false" :-)

	// si on ecrit directement dans un fichier, pour ne pas manipuler
	// en memoire refuser gz
	if ($copy) {
		$refuser_gz = true;
	}

	$options = array(
		'transcoder' => $trans === true,
		'methode' => $get,
		'datas' => $datas,
		'refuser_gz' => $refuser_gz,
		'if_modified_since' => $date_verif,
		'uri_referer' => $uri_referer,
		'file' => $copy ? $trans : '',
		'follow_location' => false,
	);
	if (!is_null($taille_max)) {
		$options['taille_max'] = $taille_max;
	}
	// dix tentatives maximum en cas d'entetes 301...
	$res = recuperer_url($url, $options);

	if (!$res) {
		return false;
	}
	if ($res['status'] !== 200) {
		return false;
	}

	return array($res['headers'], $res['page']);
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
function recuperer_body($handle, $taille_max = _INC_DISTANT_MAX_SIZE, $fichier = '') {
	$taille = 0;
	$result = '';
	$fp = false;
	if ($fichier) {
		include_spip('inc/acces');
		$tmpfile = "$fichier." . creer_uniqid() . '.tmp';
		$fp = spip_fopen_lock($tmpfile, 'w', LOCK_EX);
		if (!$fp and file_exists($fichier)) {
			return filesize($fichier);
		}
		if (!$fp) {
			return false;
		}
		$result = 0; // on renvoie la taille du fichier
	}
	while (!feof($handle) and $taille < $taille_max) {
		$res = fread($handle, 16384);
		$taille += strlen($res);
		if ($fp) {
			fwrite($fp, $res);
			$result = $taille;
		} else {
			$result .= $res;
		}
	}
	if ($fp) {
		spip_fclose_unlock($fp);
		spip_unlink($fichier);
		@rename($tmpfile, $fichier);
		if (!file_exists($fichier)) {
			return false;
		}
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
function recuperer_entetes_complets($handle, $if_modified_since = false) {
	$result = array('status' => 0, 'headers' => array(), 'last_modified' => 0, 'location' => '');

	$s = @trim(fgets($handle, 16384));
	if (!preg_match(',^HTTP/[0-9]+\.[0-9]+ ([0-9]+),', $s, $r)) {
		return false;
	}
	$result['status'] = intval($r[1]);
	while ($s = trim(fgets($handle, 16384))) {
		$result['headers'][] = $s . "\n";
		preg_match(',^([^:]*): *(.*)$,i', $s, $r);
		list(, $d, $v) = $r;
		if (strtolower(trim($d)) == 'location' and $result['status'] >= 300 and $result['status'] < 400) {
			$result['location'] = $v;
		} elseif ($d == 'Last-Modified') {
			$result['last_modified'] = strtotime($v);
		}
	}
	if ($if_modified_since
		and $result['last_modified']
		and $if_modified_since > $result['last_modified']
		and $result['status'] == 200
	) {
		$result['status'] = 304;
	}

	$result['headers'] = implode('', $result['headers']);

	return $result;
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
function nom_fichier_copie_locale($source, $extension) {
	include_spip('inc/documents');

	$d = creer_repertoire_documents('distant'); # IMG/distant/
	$d = sous_repertoire($d, $extension); # IMG/distant/pdf/

	// on se place tout le temps comme si on etait a la racine
	if (_DIR_RACINE) {
		$d = preg_replace(',^' . preg_quote(_DIR_RACINE) . ',', '', $d);
	}

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
function fichier_copie_locale($source) {
	// Si c'est deja local pas de souci
	if (!tester_url_absolue($source)) {
		if (_DIR_RACINE) {
			$source = preg_replace(',^' . preg_quote(_DIR_RACINE) . ',', '', $source);
		}

		return $source;
	}

	// optimisation : on regarde si on peut deviner l'extension dans l'url et si le fichier
	// a deja ete copie en local avec cette extension
	// dans ce cas elle est fiable, pas la peine de requeter en base
	$path_parts = pathinfo($source);
	if (!isset($path_parts['extension'])) {
		$path_parts['extension'] = '';
	}
	$ext = $path_parts ? $path_parts['extension'] : '';
	if ($ext
		and preg_match(',^\w+$,', $ext) // pas de php?truc=1&...
		and $f = nom_fichier_copie_locale($source, $ext)
		and file_exists(_DIR_RACINE . $f)
	) {
		return $f;
	}


	// Si c'est deja dans la table des documents,
	// ramener le nom de sa copie potentielle
	$ext = sql_getfetsel('extension', 'spip_documents', 'fichier=' . sql_quote($source) . " AND distant='oui' AND extension <> ''");

	if ($ext) {
		return nom_fichier_copie_locale($source, $ext);
	}

	// voir si l'extension indiquee dans le nom du fichier est ok
	// et si il n'aurait pas deja ete rapatrie

	$ext = $path_parts ? $path_parts['extension'] : '';

	if ($ext and sql_getfetsel('extension', 'spip_types_documents', 'extension=' . sql_quote($ext))) {
		$f = nom_fichier_copie_locale($source, $ext);
		if (file_exists(_DIR_RACINE . $f)) {
			return $f;
		}
	}

	// Ping  pour voir si son extension est connue et autorisee
	// avec mise en cache du resultat du ping

	$cache = sous_repertoire(_DIR_CACHE, 'rid') . md5($source);
	if (!@file_exists($cache)
		or !$path_parts = @unserialize(spip_file_get_contents($cache))
		or _request('var_mode') == 'recalcul'
	) {
		$path_parts = recuperer_infos_distantes($source, 0, false);
		ecrire_fichier($cache, serialize($path_parts));
	}
	$ext = !empty($path_parts['extension']) ? $path_parts['extension'] : '';
	if ($ext and sql_getfetsel('extension', 'spip_types_documents', 'extension=' . sql_quote($ext))) {
		return nom_fichier_copie_locale($source, $ext);
	}
	spip_log("pas de copie locale pour $source", 'distant' . _LOG_ERREUR);
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
 *     - 'mime_type' = chaine
 **/
function recuperer_infos_distantes($source, $max = 0, $charger_si_petite_image = true) {

	// pas la peine de perdre son temps
	if (!tester_url_absolue($source)) {
		return false;
	}

	# charger les alias des types mime
	include_spip('base/typedoc');

	$a = array();
	$mime_type = '';
	// On va directement charger le debut des images et des fichiers html,
	// de maniere a attrapper le maximum d'infos (titre, taille, etc). Si
	// ca echoue l'utilisateur devra les entrer...
	$reponse = recuperer_url($source, ['taille_max' => $max, 'refuser_gz' => true]);
	$headers = $reponse['headers'] ?? '';
	$a['body'] = $reponse['page'] ?? '';
	if ($headers) {
		if (preg_match(",\nContent-Type: *([^[:space:];]*),i", "\n$headers", $regs)) {
			$mime_type = (trim($regs[1]));
		} else {
			$mime_type = '';
		} // inconnu

		// Appliquer les alias
		while (isset($GLOBALS['mime_alias'][$mime_type])) {
			$mime_type = $GLOBALS['mime_alias'][$mime_type];
		}

		// Si on a un mime-type insignifiant
		// text/plain,application/octet-stream ou vide
		// c'est peut-etre que le serveur ne sait pas
		// ce qu'il sert ; on va tenter de detecter via l'extension de l'url
		// ou le Content-Disposition: attachment; filename=...
		$t = null;
		if (in_array($mime_type, array('text/plain', '', 'application/octet-stream'))) {
			if (!$t
				and preg_match(',\.([a-z0-9]+)(\?.*)?$,i', $source, $rext)
			) {
				$t = sql_fetsel('extension', 'spip_types_documents', 'extension=' . sql_quote($rext[1], '', 'text'));
			}
			if (!$t
				and preg_match(',^Content-Disposition:\s*attachment;\s*filename=(.*)$,Uims', $headers, $m)
				and preg_match(',\.([a-z0-9]+)(\?.*)?$,i', $m[1], $rext)
			) {
				$t = sql_fetsel('extension', 'spip_types_documents', 'extension=' . sql_quote($rext[1], '', 'text'));
			}
		}

		// Autre mime/type (ou text/plain avec fichier d'extension inconnue)
		if (!$t) {
			$t = sql_fetsel('extension', 'spip_types_documents', 'mime_type=' . sql_quote($mime_type));
		}

		// Toujours rien ? (ex: audio/x-ogg au lieu de application/ogg)
		// On essaie de nouveau avec l'extension
		if (!$t
			and $mime_type != 'text/plain'
			and preg_match(',\.([a-z0-9]+)(\?.*)?$,i', $source, $rext)
		) {
			# eviter xxx.3 => 3gp (> SPIP 3)
			$t = sql_fetsel('extension', 'spip_types_documents', 'extension=' . sql_quote($rext[1], '', 'text'));
		}

		if ($t) {
			spip_log("mime-type $mime_type ok, extension " . $t['extension'], 'distant');
			$a['extension'] = $t['extension'];
		} else {
			# par defaut on retombe sur '.bin' si c'est autorise
			spip_log("mime-type $mime_type inconnu", 'distant');
			$t = sql_fetsel('extension', 'spip_types_documents', "extension='bin'");
			if (!$t) {
				return false;
			}
			$a['extension'] = $t['extension'];
		}

		if (preg_match(",\nContent-Length: *([^[:space:]]*),i", "\n$headers", $regs)) {
			$a['taille'] = intval($regs[1]);
		}
	}

	// Echec avec HEAD, on tente avec GET
	if (!$a and !$max) {
		spip_log("tenter GET $source", 'distant');
		$a = recuperer_infos_distantes($source, _INC_DISTANT_MAX_SIZE);
	}

	// si on a rien trouve pas la peine d'insister
	if (!$a) {
		return false;
	}

	// S'il s'agit d'une image pas trop grosse ou d'un fichier html, on va aller
	// recharger le document en GET et recuperer des donnees supplementaires...
	include_spip('inc/filtres_images_lib_mini');
	if (strpos($mime_type, "image/") === 0
	  and $extension = _image_trouver_extension_depuis_mime($mime_type)) {
		if ($max == 0
			and (empty($a['taille']) or $a['taille'] < _INC_DISTANT_MAX_SIZE)
			and in_array($extension, formats_image_acceptables())
			and $charger_si_petite_image
		) {
			$a = recuperer_infos_distantes($source, _INC_DISTANT_MAX_SIZE);
		} else {
			if ($a['body']) {
				$a['extension'] = $extension;
				$a['fichier'] = _DIR_RACINE . nom_fichier_copie_locale($source, $extension);
				ecrire_fichier($a['fichier'], $a['body']);
				$size_image = @spip_getimagesize($a['fichier']);
				$a['largeur'] = intval($size_image[0]);
				$a['hauteur'] = intval($size_image[1]);
				$a['type_image'] = true;
			}
		}
	}

	// Fichier swf, si on n'a pas la taille, on va mettre 425x350 par defaut
	// ce sera mieux que 0x0
	// Flash is dead!
	if ($a and isset($a['extension']) and $a['extension'] == 'swf'
		and empty($a['largeur'])
	) {
		$a['largeur'] = 425;
		$a['hauteur'] = 350;
	}

	if ($mime_type == 'text/html') {
		include_spip('inc/filtres');
		$page = recuperer_url($source, ['transcoder' => true, 'taille_max' => _INC_DISTANT_MAX_SIZE]);
		$page = $page['page'] ?? '';
		if (preg_match(',<title>(.*?)</title>,ims', $page, $regs)) {
			$a['titre'] = corriger_caracteres(trim($regs[1]));
		}
		if (!isset($a['taille']) or !$a['taille']) {
			$a['taille'] = strlen($page); # a peu pres
		}
	}
	$a['mime_type'] = $mime_type;

	return $a;
}


/**
 * Tester si un host peut etre recuperer directement ou doit passer par un proxy
 *
 * On peut passer en parametre le proxy et la liste des host exclus,
 * pour les besoins des tests, lors de la configuration
 *
 * @param string $host
 * @param string $http_proxy
 * @param string $http_noproxy
 * @return string
 */
function need_proxy($host, $http_proxy = null, $http_noproxy = null) {
	if (is_null($http_proxy)) {
		$http_proxy = isset($GLOBALS['meta']['http_proxy']) ? $GLOBALS['meta']['http_proxy'] : null;
	}
	// rien a faire si pas de proxy :)
	if (is_null($http_proxy) or !$http_proxy = trim($http_proxy)) {
		return '';
	}

	if (is_null($http_noproxy)) {
		$http_noproxy = isset($GLOBALS['meta']['http_noproxy']) ? $GLOBALS['meta']['http_noproxy'] : null;
	}
	// si pas d'exception, on retourne le proxy
	if (is_null($http_noproxy) or !$http_noproxy = trim($http_noproxy)) {
		return $http_proxy;
	}

	// si le host ou l'un des domaines parents est dans $http_noproxy on fait exception
	// $http_noproxy peut contenir plusieurs domaines separes par des espaces ou retour ligne
	$http_noproxy = str_replace("\n", " ", $http_noproxy);
	$http_noproxy = str_replace("\r", " ", $http_noproxy);
	$http_noproxy = " $http_noproxy ";
	$domain = $host;
	// si le domaine exact www.example.org est dans les exceptions
	if (strpos($http_noproxy, " $domain ") !== false)
		return '';

	while (strpos($domain, '.') !== false) {
		$domain = explode('.', $domain);
		array_shift($domain);
		$domain = implode('.', $domain);

		// ou si un domaine parent commencant par un . est dans les exceptions (indiquant qu'il couvre tous les sous-domaines)
		if (strpos($http_noproxy, " .$domain ") !== false) {
			return '';
		}
	}

	// ok c'est pas une exception
	return $http_proxy;
}


/**
 * Initialise une requete HTTP avec entetes
 *
 * Décompose l'url en son schema+host+path+port et lance la requete.
 * Retourne le descripteur sur lequel lire la réponse.
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
function init_http($method, $url, $refuse_gz = false, $referer = '', $datas = '', $vers = 'HTTP/1.0', $date = '') {
	$user = $via_proxy = $proxy_user = '';
	$fopen = false;

	$t = @parse_url($url);
	$host = $t['host'];
	if ($t['scheme'] == 'http') {
		$scheme = 'http';
		$noproxy = '';
	} elseif ($t['scheme'] == 'https') {
		$scheme = 'ssl';
		$noproxy = 'ssl://';
		if (!isset($t['port']) || !($port = $t['port'])) {
			$t['port'] = 443;
		}
	} else {
		$scheme = $t['scheme'];
		$noproxy = $scheme . '://';
	}
	if (isset($t['user'])) {
		$user = array($t['user'], $t['pass']);
	}

	if (!isset($t['port']) || !($port = $t['port'])) {
		$port = 80;
	}
	if (!isset($t['path']) || !($path = $t['path'])) {
		$path = '/';
	}

	if (!empty($t['query'])) {
		$path .= '?' . $t['query'];
	}

	$f = lance_requete($method, $scheme, $user, $host, $path, $port, $noproxy, $refuse_gz, $referer, $datas, $vers, $date);
	if (!$f or !is_resource($f)) {
		// fallback : fopen si on a pas fait timeout dans lance_requete
		// ce qui correspond a $f===110
		if ($f !== 110
			and !need_proxy($host)
			and !_request('tester_proxy')
			and (!isset($GLOBALS['inc_distant_allow_fopen']) or $GLOBALS['inc_distant_allow_fopen'])
		) {
			$f = @fopen($url, 'rb');
			spip_log("connexion vers $url par simple fopen", 'distant');
			$fopen = true;
		} else {
			// echec total
			$f = false;
		}
	}

	return array($f, $fopen);
}

/**
 * Lancer la requete proprement dite
 *
 * @param string $method
 *   type de la requete (GET, HEAD, POST...)
 * @param string $scheme
 *   protocole (http, tls, ftp...)
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
 *   false|int si echec
 *   resource socket vers l'url demandee
 */
function lance_requete(
	$method,
	$scheme,
	$user,
	$host,
	$path,
	$port,
	$noproxy,
	$refuse_gz = false,
	$referer = '',
	$datas = '',
	$vers = 'HTTP/1.0',
	$date = ''
) {

	$proxy_user = '';
	$http_proxy = need_proxy($host);
	if ($user) {
		$user = urlencode($user[0]) . ':' . urlencode($user[1]);
	}

	$connect = '';
	if ($http_proxy) {
		if (!defined('_PROXY_HTTPS_NOT_VIA_CONNECT') and in_array($scheme , array('tls','ssl'))) {
			$path_host = (!$user ? '' : "$user@") . $host . (($port != 80) ? ":$port" : '');
			$connect = 'CONNECT ' . $path_host . " $vers\r\n"
				. "Host: $path_host\r\n"
				. "Proxy-Connection: Keep-Alive\r\n";
		} else {
			$path = (in_array($scheme , array('tls','ssl')) ? 'https://' : "$scheme://")
				. (!$user ? '' : "$user@")
				. "$host" . (($port != 80) ? ":$port" : '') . $path;
		}
		$t2 = @parse_url($http_proxy);
		$first_host = $t2['host'];
		if (!($port = $t2['port'])) {
			$port = 80;
		}
		if ($t2['user']) {
			$proxy_user = base64_encode($t2['user'] . ':' . $t2['pass']);
		}
	} else {
		$first_host = $noproxy . $host;
	}

	if ($connect) {
		$streamContext = stream_context_create(array(
			'ssl' => array(
				'verify_peer' => false,
				'allow_self_signed' => true,
				'SNI_enabled' => true,
				'peer_name' => $host,
			)
		));
		$f = @stream_socket_client(
			"tcp://$first_host:$port",
			$errno,
			$errstr,
			_INC_DISTANT_CONNECT_TIMEOUT,
			STREAM_CLIENT_CONNECT,
			$streamContext
		);
		spip_log("Recuperer $path sur $first_host:$port par $f (via CONNECT)", 'connect');
		if (!$f) {
			spip_log("Erreur connexion $errno $errstr", 'distant' . _LOG_ERREUR);
			return $errno;
		}
		stream_set_timeout($f, _INC_DISTANT_CONNECT_TIMEOUT);

		fputs($f, $connect);
		fputs($f, "\r\n");
		$res = fread($f, 1024);
		if (!$res
			or !count($res = explode(' ', $res))
			or $res[1] !== '200'
		) {
			spip_log("Echec CONNECT sur $first_host:$port", 'connect' . _LOG_INFO_IMPORTANTE);
			fclose($f);

			return false;
		}
		// important, car sinon on lit trop vite et les donnees ne sont pas encore dispo
		stream_set_blocking($f, true);
		// envoyer le handshake
		stream_socket_enable_crypto($f, true, STREAM_CRYPTO_METHOD_SSLv23_CLIENT);
		spip_log("OK CONNECT sur $first_host:$port", 'connect');
	} else {
		$ntry = 3;
		do {
			$f = @fsockopen($first_host, $port, $errno, $errstr, _INC_DISTANT_CONNECT_TIMEOUT);
		} while (!$f and $ntry-- and $errno !== 110 and sleep(1));
		spip_log("Recuperer $path sur $first_host:$port par $f");
		if (!$f) {
			spip_log("Erreur connexion $errno $errstr", 'distant' . _LOG_ERREUR);

			return $errno;
		}
		stream_set_timeout($f, _INC_DISTANT_CONNECT_TIMEOUT);
	}

	$site = isset($GLOBALS['meta']['adresse_site']) ? $GLOBALS['meta']['adresse_site'] : '';

	$host_port = $host;
	if ($port != (in_array($scheme , array('tls','ssl')) ? 443 : 80)) {
		$host_port .= ":$port";
	}
	$req = "$method $path $vers\r\n"
		. "Host: $host_port\r\n"
		. 'User-Agent: ' . _INC_DISTANT_USER_AGENT . "\r\n"
		. ($refuse_gz ? '' : ('Accept-Encoding: ' . _INC_DISTANT_CONTENT_ENCODING . "\r\n"))
		. (!$site ? '' : "Referer: $site/$referer\r\n")
		. (!$date ? '' : 'If-Modified-Since: ' . (gmdate('D, d M Y H:i:s', $date) . " GMT\r\n"))
		. (!$user ? '' : ('Authorization: Basic ' . base64_encode($user) . "\r\n"))
		. (!$proxy_user ? '' : "Proxy-Authorization: Basic $proxy_user\r\n")
		. (!strpos($vers, '1.1') ? '' : "Keep-Alive: 300\r\nConnection: keep-alive\r\n");

#	spip_log("Requete\n$req", 'distant');
	fputs($f, $req);
	fputs($f, $datas ? $datas : "\r\n");

	return $f;
}
