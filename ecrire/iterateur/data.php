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


/**
 * creer une boucle sur un iterateur DATA
 * annonce au compilo les "champs" disponibles
 *
 * @param  $b
 * @return
 */
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

	/**
	 * Declarer les criteres exceptions
	 * @return array
	 */
	public function exception_des_criteres() {
		return array('tableau');
	}

	/**
	 * Recuperer depuis le cache si possible
	 * @param  $cle
	 * @return
	 */
	protected function cache_get($cle) {
		if (!$cle) return;
		# utiliser memoization si dispo
		include_spip('inc/memoization');
		if (!function_exists('cache_get')) return;
		return cache_get($cle);
	}

	/**
	 * Srtocker en cache si possible
	 * @param  $cle
	 * @param  $ttl
	 * @return
	 */
	protected function cache_set($cle, $ttl) {
		if (!$cle) return;
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

	/**
	 * Aller chercher les donnees de la boucle DATA
	 *
	 * @throws Exception
	 * @param  $command
	 * @return void
	 */
	protected function select($command) {

		// l'iterateur DATA peut etre appele en passant (data:type)
		// le type se retrouve dans la commande 'from'
		// dans ce cas la le critere {source} n'a pas besoin du 1er argument
		if (isset($this->command['from'][0])) {
			array_unshift($this->command['source'], $this->command['sourcemode']);
			$this->command['sourcemode'] = $this->command['from'][0];
		}
		
		// les commandes connues pour l'iterateur DATA
		// sont : {tableau #ARRAY} ; {cle=...} ; {valeur=...}
		// {source format, [URL], [arg2]...}
		
		if (isset($this->command['source'])
		AND isset($this->command['sourcemode'])) {

			# un peu crado : avant de charger le cache il faut charger
			# les class indispensables, sinon PHP ne saura pas gerer
			# l'objet en cache ; cf plugins/icalendar
			if (isset($this->command['sourcemode']))
				charger_fonction($this->command['sourcemode'] . '_to_array', 'inc', true);

			# le premier argument peut etre un array, une URL etc.
			$src = $this->command['source'][0];

			# avons-nous un cache dispo ?
			if (is_string($src))
				$cle = 'datasource_'.md5($this->command['sourcemode'].':'.var_export($this->command['source'],true));

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
			else try {
				# dommage que ca ne soit pas une option de yql_to_array...
				if ($this->command['sourcemode'] == 'yql')
					if (!isset($ttl)) $ttl = 3600;

				if (isset($this->command['sourcemode'])
				AND in_array($this->command['sourcemode'],
					array('table', 'array', 'tableau'))
				) {
					if (is_array($a = $src)
					OR (is_string($a)
					AND $a = str_replace('&quot;', '"', $a) # fragile!
					AND is_array($a = @unserialize($a)))
					)
						$this->tableau = $a;
				}
				else if (preg_match(',^https?://,', $src)) {
					include_spip('inc/distant');
					$u = recuperer_page($src);
					if (!$u)
						throw new Exception("404");
					if (!isset($ttl)) $ttl = 24*3600;
				} else if (@is_readable($src)) {
					$u = spip_file_get_contents($src);
					if (!isset($ttl)) $ttl = 10;
				} else {
					$u = $src;
					if (!isset($ttl)) $ttl = 10;
				}

				if (!$this->err
				AND $g = charger_fonction($this->command['sourcemode'] . '_to_array', 'inc', true)) {
					$args = $this->command['source'];
					$args[0] = $u;
					if (is_array($a = call_user_func_array($g,$args))) {
						$this->tableau = $a;
					}
				}

				if (!is_array($this->tableau))
					$this->err = true;

				if (!$this->err AND $ttl>0)
					$this->cache_set($cle, $ttl);

			}
			catch (Exception $e) {
				$e = $e->getMessage();
				$err = sprintf("[%s, %s] $e",
					$src,
					$this->command['sourcemode']);
				erreur_squelette(array($err, array()));
				$this->err = true;
			}

			# en cas d'erreur, utiliser le cache si encore dispo
			if ($this->err
			AND $cache) {
				$this->tableau = $cache['data'];
				$this->err = false;
			}

		}

		// Critere {liste X1, X2, X3}
		if (isset($this->command['liste'])) {
			$this->tableau = $this->command['liste'];
		}

		// Si a ce stade on n'a pas de table, il y a un bug
		if (!is_array($this->tableau)) {
			$this->err = true;
			spip_log("erreur datasource ".$src);
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
			$aleas = 0;
			foreach($this->command['orderby'] as $tri) {
				if (preg_match(',^\.?([/\w]+)( DESC)?$,iS', $tri, $r)) {
					// tri par cle
					if ($r[1] == 'cle'){
						if ($r[2])
							krsort($this->tableau);
						else
							ksort($this->tableau);
					}
					# {par hasard}
					else if ($r[1] == 'alea') {
						$k = array_keys($this->tableau);
						shuffle($k);
						$v = array();
						foreach($k as $cle)
							$v[$cle] = $this->tableau[$cle];
						$this->tableau = $v;
					}
					else {
						# {par valeur}
						if ($r[1] == 'valeur')
							$tv = '%s';
						# {par valeur/xx/yy} ??
						else
							$tv = 'table_valeur(%s, '.var_export($r[1],true).')';
						$sortfunc .= '
						$a = '.sprintf($tv,'$aa').';
						$b = '.sprintf($tv,'$bb').';
						if ($a <> $b)
							return ($a ' . ($r[2] ? '>' : '<').' $b) ? -1 : 1;';
					}
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
		AND strlen($fusion = $this->command['groupby'][0])) {
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

/*
 * Fonctions de transformation donnee => tableau
 */

/**
 * file -> tableau
 *
 * @param  string $u
 * @return array
 */
function inc_file_to_array_dist($u) {
	return preg_split('/\r?\n/', $u);
}

/**
 * plugins -> tableau
 * @return unknown
 */
function inc_plugins_to_array_dist() {
	include_spip('inc/plugin');
	return liste_chemin_plugin_actifs();
}

/**
 * xml -> tableau
 * @param  string $u
 * @return array
 */
function inc_xml_to_array_dist($u) {
	return @ObjectToArray(new SimpleXmlIterator($u));
}

/**
 * yql -> tableau
 * @throws Exception
 * @param  string $u
 * @return array|bool
 */
function inc_yql_to_array_dist($u) {
	define('_YQL_ENDPOINT', 'http://query.yahooapis.com/v1/public/yql?&env=store%3A%2F%2Fdatatables.org%2Falltableswithkeys&q=');
	$v = recuperer_page($url = _YQL_ENDPOINT.urlencode($u).'&format=json');
	$w = json_decode($v);
	if (!$w) {
		throw new Exception('YQL: r&#233;ponse vide ou mal form&#233;e');
		return false;
	}
	return (array) $w;
}

/**
 * sql -> tableau
 * @param string $u
 * @return array|bool
 */
function inc_sql_to_array_dist($u) {
	# sortir le connecteur de $u
	preg_match(',^(?:(\w+):)?(.*)$,S', $u, $v);
	$serveur = (string) $v[1];
	$req = trim($v[2]);
	if ($s = sql_query($req, $serveur)) {
		$r = array();
		while ($t = sql_fetch($s))
			$r[] = $t;
		return $r;
	}
	return false;
}

/**
 * json -> tableau
 * @param string $u
 * @return array|bool
 */
function inc_json_to_array_dist($u) {
	if (is_array($json = json_decode($u))
	OR is_object($json))
		return (array) $json;
}

/**
 * csv -> tableau
 * @param string $u
 * @return array|bool
 */
function inc_csv_to_array_dist($u) {
	include_spip('inc/csv');
	list($entete,$csv) = analyse_csv($u);
	array_unshift($csv,$entete);

	include_spip('inc/charsets');
	foreach ($entete as $k => $v) {
		$v = strtolower(preg_replace(',\W+,', '_', translitteration($v)));
		foreach ($csv as &$item)
			$item[$v] = &$item[$k];
	}
	return $csv;
}

/**
 * RSS -> tableau
 * @param string $u
 * @return array|bool
 */
function inc_rss_to_array_dist($u) {
	include_spip('inc/syndic');
	if (is_array($rss = analyser_backend($u)))
		$tableau = $rss;
	return $tableau;
}

/**
 * atom, alias de rss -> tableau
 * @param string $u
 * @return array|bool
 */
function inc_atom_to_array_dist($u) {
	$g = charger_fonction('rss_to_array', 'inc');
	return $g($u);
}

/**
 * glob -> tableau
 * lister des fichiers selon un masque, pour la syntaxe cf php.net/glob
 * @param string $u
 * @return array|bool
 */
function inc_glob_to_array_dist($u) {
	return (array) glob($u,
		GLOB_MARK | GLOB_NOSORT | GLOB_BRACE
	);
}

/**
 * ls -> tableau
 * ls : lister des fichiers selon un masque glob
 * et renvoyer aussi leurs donnees php.net/stat
 * @param string $u
 * @return array|bool
 */
function inc_ls_to_array_dist($u) {
	$glob = charger_fonction('glob_to_array', 'inc');
	$a = $glob($u);
	foreach ($a as &$v) {
		$b = (array) @stat($v);
		foreach ($b as $k => $ignore)
			if (is_numeric($k)) unset($b[$k]);
		$b['file'] = basename($v);
		$v = array_merge(
			pathinfo($v),
			$b
		);
	}
	return $a;
}

/**
 * Object -> tableau
 * @param Object $object
 * @return array|bool
 */
function ObjectToArray($object){
	$xml_array = array();
	for( $object->rewind(); $object->valid(); $object->next() ) {
		if(!array_key_exists($object->key(), $xml_array)){
			$xml_array[$object->key()] = array();
		}
		$vars = get_object_vars($object->current());
		if (isset($vars['@attributes']))
			foreach($vars['@attributes'] as $k => $v)
			$xml_array[$object->key()][$k] = $v;
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
