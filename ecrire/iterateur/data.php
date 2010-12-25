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

include_spip('iterateur/iterateur');


//
// creer une boucle sur un iterateur DATA
// annonce au compilo les "champs" disponibles
//
function iterateur_DATA_dist($b) {
	$b->iterateur = 'DATA'; # designe la classe d'iterateur
	$b->show = array(
		'field' => array(
			'cle' => 'STRING',
			'valeur' => 'STRING',
			'*' => 'ALL'
		)
	);
	$b->select[] = '.valeur';
	return $b;
}


/**
 * IterateurDATA pour iterer sur des donnees
 */
class IterateurDATA implements Iterator {
	/**
	 * tableau de donnees
	 * @var array
	 */
	protected $tableau = array();

	/**
	 * Conditions de filtrage
	 * ie criteres de selection
	 * @var array
	 */
	protected $filtre = array();


	/**
	 * Cle courante
	 * @var null
	 */
	protected $cle = null;

	/**
	 * Valeur courante
	 * @var null
	 */
	protected $valeur = null;

	/**
	 * Constructeur
	 *
	 * @param  $command
	 * @param array $info
	 */
	public function __construct($command, $info=array()) {
		$this->type='DATA';
		$this->command = $command;
		$this->info = $info;

		$this->select($command);
	}

	/**
	 * Revenir au depart
	 * @return void
	 */
	public function rewind() {
		reset($this->tableau);
		list($this->cle, $this->valeur) = each($this->tableau);
	}

	public function exception_des_criteres() {
		return array('tableau');
	}

	protected function cache_get($cle) {
		# utiliser memoization si dispo
		include_spip('inc/memoization');
		if (!function_exists('cache_get')) return;
		return cache_get($cle);
	}

	protected function cache_set($cle, $ttl) {
		# utiliser memoization si dispo
		include_spip('inc/memoization');
		if (!function_exists('cache_set')) return;
		return cache_set($cle,
			array(
				'data' => $this->tableau,
				'time' => time(),
				'ttl' => $ttl
			),
			3600 + $ttl);
			# conserver le cache 1h deplus que la validite demandee,
			# pour le cas ou le serveur distant ne repond plus
	}

	protected function select($command) {
		// les commandes connues pour l'iterateur POUR
		// sont : tableau=#ARRAY ; cle=...; valeur=...
		// source URL
		if (isset($this->command['source'])
		AND isset($this->command['sourcemode'])) {

			# un peu crado : avant de charger le cache il faut charger
			# les class indispensables, sinon PHP ne saura pas gerer
			# l'objet en cache ; cf plugins/icalendar
			if (isset($this->command['sourcemode']))
				charger_fonction($this->command['sourcemode'] . '_to_array', 'inc', true);

			$cle = 'datasource_'.md5($this->command['sourcemode'].':'.$this->command['source']);
			# avons-nous un cache dispo ?
			$cache = $this->cache_get($cle);
			if (isset($this->command['datacache']))
				$ttl = intval($this->command['datacache']);
			if ($cache
			AND ($cache['time'] + (isset($ttl) ? $ttl : $cache['ttl'])
				> time())
			AND !(_request('var_mode') === 'recalcul'
				AND include_spip('inc/autoriser')
				AND autoriser('recalcul')
			)) {
				$this->tableau = $cache['data'];
			}
			else {
				# dommage que ca ne soit pas une option de yql_to_array...
				if ($this->command['sourcemode'] == 'yql')
					if (!isset($ttl)) $ttl = 3600;

				if (isset($this->command['sourcemode'])
				AND in_array($this->command['sourcemode'],
					array('table', 'array', 'tableau'))
				) {
					if (is_array($a = $this->command['source'])
					OR is_array($a = unserialize($this->command['source'])))
						$this->tableau = $a;
				}
				else if (preg_match(',^https?://,', $this->command['source'])) {
					include_spip('inc/distant');
					$u = recuperer_page($this->command['source']);
					if (!isset($ttl)) $ttl = 24*3600;
				} else if (@is_readable($this->command['source'])) {
					$u = spip_file_get_contents($this->command['source']);
					if (!isset($ttl)) $ttl = 10;
				} else {
					$u = $this->command['source'];
					if (!isset($ttl)) $ttl = 10;
				}

				if ($u) {
					if ($g = charger_fonction($this->command['sourcemode'] . '_to_array', 'inc', true)) {
						if (is_array($a = $g($u))) {
							$this->tableau = $a;
						} else {
							$this->err = true;
							spip_log("erreur sur $g(): $u");
						}
					}

					if (!$this->err AND $ttl>0)
						$this->cache_set($cle, $ttl);
				}
				# en cas d'erreur http, utiliser le cache si encore dispo
				else if ($cache)
					$this->tableau = $cache['data'];
			}
		}

		// recuperer le critere {tableau=xxx} pour compat ascendante
		// boucle POUR ; methode pas tres propre, depreciee par {table XX}
		if (is_array($this->command['where'])) {
			foreach ($this->command['where'] as $k => $com) {
				if ($com[1] === 'tableau') {
					if ($com[0] !== '=') {
						// erreur
					}
					# sql_quote a l'envers : pas propre...
					eval ('$x = '.str_replace('\"', '"', $com[2]).';');
					if (is_array($x) OR is_array($x = @unserialize($x)))
						$this->tableau = $x;
					else
						$this->err = true;
				}
			}
		}

		// Critere {liste X1, X2, X3}
		if (isset($this->command['liste'])) {
			$this->tableau = $this->command['liste'];
		}

		// Si a ce stade on n'a pas de table, il y a un bug
		if (!is_array($this->tableau)) {
			$this->err = true;
			spip_log("erreur datasource ".$this->command['source']);
		}


		// {datapath query.results}
		// extraire le chemin "query.results" du tableau de donnees
		if (!$this->err
		AND is_array($this->command['datapath'])) {
			list(,$base) = each($this->command['datapath']);
			if (strlen($base = trim($base))) {
				$this->tableau = table_valeur($this->tableau, $base);
				if (!is_array($this->tableau)) {
					$this->tableau = array();
					$this->err = true;
					spip_log("datapath '$base' absent");
				}
			}
		}

		// tri {par x}
		if ($this->command['orderby']) {
			$sortfunc = '';
			foreach($this->command['orderby'] as $tri) {
				if (preg_match(',^\.?([/\w]+)( DESC)?$,iS', $tri, $r)) {
					if ($r[1] == 'valeur')
						$tv = '%s';
					else if ($r[1] == 'alea') # {par hasard}
						$tv = 'rand(0,1)';
					else
						$tv = 'table_valeur(%s, '.var_export($r[1],true).')';
					$sortfunc .= '
					$a = '.sprintf($tv,'$aa').';
					$b = '.sprintf($tv,'$bb').';
					if ($a <> $b)
						return ($a ' . ($r[2] ? '>' : '<').' $b) ? -1 : 1;';
				}
			}

			if ($sortfunc) {
				uasort($this->tableau, create_function('$aa,$bb',
					$sortfunc.'
					return 0;'
				));
			}
		}

		// grouper les resultats {fusion /x/y/z} ;
		if ($this->command['groupby']
		AND strlen($fusion = $this->command['groupby'][0][1])) {
			$vu = array();
			foreach($this->tableau as $k => $v) {
				$val = table_valeur($v, $fusion);
				if (isset($vu[$val]))
					unset($this->tableau[$k]);
				else
					$vu[$val] = true;
			}
		}

		$this->rewind();
		#var_dump($this->tableau);
	}


	/**
	 * L'iterateur est-il encore valide ?
	 * @return bool
	 */
	public function valid(){
		return !is_null($this->cle);
	}

	/**
	 * Retourner la valeur
	 * @return null
	 */
	public function current() {
		return $this->valeur;
	}

	/**
	 * Retourner la cle
	 * @return null
	 */
	public function key() {
		return $this->cle;
	}

	/**
	 * Passer a la valeur suivante
	 * @return void
	 */
	public function next(){
		if ($this->valid())
			list($this->cle, $this->valeur) = each($this->tableau);
	}

	/**
	 * Compter le nombre total de resultats
	 * @return int
	 */
	public function count() {
		if (is_null($this->total))
			$this->total = count($this->tableau);
	  return $this->total;
	}
}


function inc_file_to_array_dist($u) {
	return preg_split('/\r?\n/', $u);
}
function inc_plugins_to_array_dist($u) {
	include_spip('inc/plugin');
	return liste_chemin_plugin_actifs();
}
function inc_xml_to_array_dist($u) {
	return ObjectToArray(new SimpleXmlIterator($u));
}
function inc_yql_to_array_dist($u) {
	define('_YQL_ENDPOINT', 'http://query.yahooapis.com/v1/public/yql?&env=store%3A%2F%2Fdatatables.org%2Falltableswithkeys&q=');
	$v = recuperer_page($url = _YQL_ENDPOINT.urlencode($u).'&format=json');
	$w = json_decode($v);
	if (!$w) {
		spip_log("erreur yql: $url");
		return false;
	}
	return (array) $w;
}
function inc_sql_to_array_dist($u) {
	if ($s = sql_query($u)) {
		$r = array();
		while ($t = sql_fetch($s))
			$r[] = $t;
		return $r;
	}
	return false;
}
function inc_json_to_array_dist($u) {
	if (is_array($json = json_decode($u))
	OR is_object($json))
		return (array) $json;
}
function inc_csv_to_array_dist($u) {
	# decodage csv a passer en inc/csv
	# cf. http://www.php.net/manual/en/function.str-getcsv.php#100579 et suiv.
	if (function_exists('str_getcsv')) # PHP 5.3.0
		$tableau = str_getcsv($u);
	else
	foreach(array_filter(preg_split('/\r?\n/',$u)) as $ligne)
		$tableau[] = explode(',', $ligne);

	return $tableau;
}
function inc_yaml_to_array_dist($u) {
	include_spip('inc/yaml');
	if (is_array($yaml = yaml_decode($u)))
		$tableau = $yaml;
	else if (is_object($yaml))
		$tableau = (array) $yaml;

	return $tableau;
}
function inc_rss_to_array_dist($u) {
	include_spip('inc/syndic');
	if (is_array($rss = analyser_backend($u)))
		$tableau = $rss;
	return $tableau;
}
// atom, alias de rss
function inc_atom_to_array_dist($u) {
	$g = charger_fonction('rss_to_array', 'inc');
	return $g($u);
}
// glob : lister des fichiers selon un masque, pour la syntaxe cf php.net/glob
function inc_glob_to_array_dist($u) {
	return glob($u,
		GLOB_MARK | GLOB_NOSORT | GLOB_BRACE
	);
}

function XmlToArray($xml_file){
  $object = new SimpleXmlIterator($xml_file, null, true);
  return ObjectToArray($object);
}
function ObjectToArray($object){
  $xml_array = array();
  for( $object->rewind(); $object->valid(); $object->next() ) {
    if(!array_key_exists($object->key(), $xml_array)){
      $xml_array[$object->key()] = array();
    }
    if($object->hasChildren()){
      $xml_array[$object->key()][] = ObjectToArray(
         $object->current());
    }
    else{
      $xml_array[$object->key()][] = strval($object->current());
    }
  }
  return $xml_array;
}
?>
