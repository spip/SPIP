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
 * Gestion de l'itérateur DATA
 *
 * @package SPIP\Core\Iterateur\DATA
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

if (!defined('_DATA_SOURCE_MAX_SIZE')) {
	define('_DATA_SOURCE_MAX_SIZE', 2 * 1048576);
}


/**
 * Créer une boucle sur un itérateur DATA
 *
 * Annonce au compilateur les "champs" disponibles, c'est à dire
 * 'cle', 'valeur' et '*' (tout nom possible).
 *
 * On ne peut effectivement pas connaître à la compilation la structure
 * des données qui seront obtenues. On indique donc au compilateur que
 * toute balise utilisée dans la boucle est possiblement un champ
 * des données reçues.
 *
 * @param Boucle $b
 *     Description de la boucle
 * @return Boucle
 *     Description de la boucle complétée des champs
 */
function iterateur_DATA_dist($b) {
	$b->iterateur = 'DATA'; # designe la classe d'iterateur
	$b->show = [
		'field' => [
			'cle' => 'STRING',
			'valeur' => 'STRING',
			'*' => 'ALL' // Champ joker *
		]
	];
	$b->select[] = '.valeur';

	return $b;
}


/**
 * Itérateur DATA
 *
 * Pour itérer sur des données quelconques (transformables en tableau)
 */
class IterateurDATA implements Iterator {
	/**
	 * tableau de donnees
	 *
	 * @var array
	 */
	protected $tableau = [];

	/**
	 * Conditions de filtrage
	 * ie criteres de selection
	 *
	 * @var array
	 */
	protected $filtre = [];


	/**
	 * Cle courante
	 *
	 * @var null
	 */
	protected $cle = null;

	/**
	 * Valeur courante
	 *
	 * @var null
	 */
	protected $valeur = null;

	/**
	 * Erreur presente ?
	 *
	 * @var bool
	 **/
	public $err = false;

	/**
	 * Calcul du total des elements
	 *
	 * @var int|null
	 **/
	public $total = null;

	/**
	 * Constructeur
	 *
	 * @param  $command
	 * @param array $info
	 */
	public function __construct($command, $info = []) {
		$this->type = 'DATA';
		$this->command = $command;
		$this->info = $info;

		$this->select($command);
	}

	/**
	 * Revenir au depart
	 *
	 * @return void
	 */
	public function rewind() {
		reset($this->tableau);
		$this->cle = key($this->tableau);
		$this->valeur = current($this->tableau);
		next($this->tableau);
	}

	/**
	 * Déclarer les critères exceptions
	 *
	 * @return array
	 */
	public function exception_des_criteres() {
		return ['tableau'];
	}

	/**
	 * Récupérer depuis le cache si possible
	 *
	 * @param string $cle
	 * @return mixed
	 */
	protected function cache_get($cle) {
		if (!$cle) {
			return;
		}
		# utiliser memoization si dispo
		if (!function_exists('cache_get')) {
			return;
		}

		return cache_get($cle);
	}

	/**
	 * Stocker en cache si possible
	 *
	 * @param string $cle
	 * @param int $ttl
	 * @param null|mixed $valeur
	 * @return bool
	 */
	protected function cache_set($cle, $ttl, $valeur = null) {
		if (!$cle) {
			return;
		}
		if (is_null($valeur)) {
			$valeur = $this->tableau;
		}
		# utiliser memoization si dispo
		if (!function_exists('cache_set')) {
			return;
		}

		return cache_set(
			$cle,
			[
				'data' => $valeur,
				'time' => time(),
				'ttl' => $ttl
			],
			3600 + $ttl
		);
		# conserver le cache 1h de plus que la validite demandee,
		# pour le cas ou le serveur distant ne reponde plus
	}

	/**
	 * Aller chercher les données de la boucle DATA
	 *
	 * @throws Exception
	 * @param array $command
	 * @return void
	 */
	protected function select($command) {

		// l'iterateur DATA peut etre appele en passant (data:type)
		// le type se retrouve dans la commande 'from'
		// dans ce cas la le critere {source}, si present, n'a pas besoin du 1er argument
		if (isset($this->command['from'][0])) {
			if (isset($this->command['source']) and is_array($this->command['source'])) {
				array_unshift($this->command['source'], $this->command['sourcemode']);
			}
			$this->command['sourcemode'] = $this->command['from'][0];
		}

		// cherchons differents moyens de creer le tableau de donnees
		// les commandes connues pour l'iterateur DATA
		// sont : {tableau #ARRAY} ; {cle=...} ; {valeur=...}

		// {source format, [URL], [arg2]...}
		if (
			isset($this->command['source'])
			and isset($this->command['sourcemode'])
		) {
			$this->select_source();
		}

		// Critere {liste X1, X2, X3}
		if (isset($this->command['liste'])) {
			$this->select_liste();
		}
		if (isset($this->command['enum'])) {
			$this->select_enum();
		}

		// Si a ce stade on n'a pas de table, il y a un bug
		if (!is_array($this->tableau)) {
			$this->err = true;
			spip_log('erreur datasource ' . var_export($command, true));
		}

		// {datapath query.results}
		// extraire le chemin "query.results" du tableau de donnees
		if (
			!$this->err
			and isset($this->command['datapath'])
			and is_array($this->command['datapath'])
		) {
			$this->select_datapath();
		}

		// tri {par x}
		if ($this->command['orderby']) {
			$this->select_orderby();
		}

		// grouper les resultats {fusion /x/y/z} ;
		if ($this->command['groupby']) {
			$this->select_groupby();
		}

		$this->rewind();
		#var_dump($this->tableau);
	}


	/**
	 * Aller chercher les donnees de la boucle DATA
	 * depuis une source
	 * {source format, [URL], [arg2]...}
	 */
	protected function select_source() {
		# un peu crado : avant de charger le cache il faut charger
		# les class indispensables, sinon PHP ne saura pas gerer
		# l'objet en cache ; cf plugins/icalendar
		# perf : pas de fonction table_to_array ! (table est deja un array)
		if (
			isset($this->command['sourcemode'])
			and !in_array($this->command['sourcemode'], ['table', 'array', 'tableau'])
		) {
			charger_fonction($this->command['sourcemode'] . '_to_array', 'inc', true);
		}

		# le premier argument peut etre un array, une URL etc.
		$src = $this->command['source'][0];

		# avons-nous un cache dispo ?
		$cle = null;
		if (is_string($src)) {
			$cle = 'datasource_' . md5($this->command['sourcemode'] . ':' . var_export($this->command['source'], true));
		}

		$cache = $this->cache_get($cle);
		if (isset($this->command['datacache'])) {
			$ttl = intval($this->command['datacache']);
		}
		if (
			$cache
			and ($cache['time'] + (isset($ttl) ? $ttl : $cache['ttl'])
				> time())
			and !(_request('var_mode') === 'recalcul'
				and include_spip('inc/autoriser')
				and autoriser('recalcul')
			)
		) {
			$this->tableau = $cache['data'];
		} else {
			try {
				if (
					isset($this->command['sourcemode'])
					and in_array(
						$this->command['sourcemode'],
						['table', 'array', 'tableau']
					)
				) {
					if (
						is_array($a = $src)
						or (is_string($a)
							and $a = str_replace('&quot;', '"', $a) # fragile!
							and is_array($a = @unserialize($a)))
					) {
						$this->tableau = $a;
					}
				} else {
					$data = $src;
					if (is_string($src)) {
						if (tester_url_absolue($src)) {
							include_spip('inc/distant');
							$data = recuperer_url($src, ['taille_max' => _DATA_SOURCE_MAX_SIZE]);
							$data = $data['page'] ?? '';
							if (!$data) {
								throw new Exception('404');
							}
							if (!isset($ttl)) {
								$ttl = 24 * 3600;
							}
						} elseif (@is_dir($src)) {
							$data = $src;
						} elseif (@is_readable($src) && @is_file($src)) {
							$data = spip_file_get_contents($src);
						}
						if (!isset($ttl)) {
							$ttl = 10;
						}
					}

					if (
						!$this->err
						and $data_to_array = charger_fonction($this->command['sourcemode'] . '_to_array', 'inc', true)
					) {
						$args = $this->command['source'];
						$args[0] = $data;
						if (is_array($a = $data_to_array(...$args))) {
							$this->tableau = $a;
						}
					}
				}

				if (!is_array($this->tableau)) {
					$this->err = true;
				}

				if (!$this->err and isset($ttl) and $ttl > 0) {
					$this->cache_set($cle, $ttl);
				}
			} catch (Exception $e) {
				$e = $e->getMessage();
				$err = sprintf(
					"[%s, %s] $e",
					$src,
					$this->command['sourcemode']
				);
				erreur_squelette([$err, []]);
				$this->err = true;
			}
		}

		# en cas d'erreur, utiliser le cache si encore dispo
		if (
			$this->err
			and $cache
		) {
			$this->tableau = $cache['data'];
			$this->err = false;
		}
	}


	/**
	 * Retourne un tableau donne depuis un critère liste
	 *
	 * Critère `{liste X1, X2, X3}`
	 *
	 * @see critere_DATA_liste_dist()
	 *
	 **/
	protected function select_liste() {
		# s'il n'y a qu'une valeur dans la liste, sans doute une #BALISE
		if (!isset($this->command['liste'][1])) {
			if (!is_array($this->command['liste'][0])) {
				$this->command['liste'] = explode(',', $this->command['liste'][0]);
			} else {
				$this->command['liste'] = $this->command['liste'][0];
			}
		}
		$this->tableau = $this->command['liste'];
	}

	/**
	 * Retourne un tableau donne depuis un critere liste
	 * Critere {enum Xmin, Xmax}
	 *
	 **/
	protected function select_enum() {
		# s'il n'y a qu'une valeur dans la liste, sans doute une #BALISE
		if (!isset($this->command['enum'][1])) {
			if (!is_array($this->command['enum'][0])) {
				$this->command['enum'] = explode(',', $this->command['enum'][0]);
			} else {
				$this->command['enum'] = $this->command['enum'][0];
			}
		}
		if (count($this->command['enum']) >= 3) {
			$enum = range(
				array_shift($this->command['enum']),
				array_shift($this->command['enum']),
				array_shift($this->command['enum'])
			);
		} else {
			$enum = range(array_shift($this->command['enum']), array_shift($this->command['enum']));
		}
		$this->tableau = $enum;
	}


	/**
	 * extraire le chemin "query.results" du tableau de donnees
	 * {datapath query.results}
	 *
	 **/
	protected function select_datapath() {
		$base = reset($this->command['datapath']);
		if (strlen($base = ltrim(trim($base), '/'))) {
			$this->tableau = table_valeur($this->tableau, $base);
			if (!is_array($this->tableau)) {
				$this->tableau = [];
				$this->err = true;
				spip_log("datapath '$base' absent");
			}
		}
	}

	/**
	 * Ordonner les resultats
	 * {par x}
	 *
	 **/
	protected function select_orderby() {
		$sortfunc = '';
		$aleas = 0;
		foreach ($this->command['orderby'] as $tri) {
			// virer le / initial pour les criteres de la forme {par /xx}
			if (preg_match(',^\.?([/\w]+)( DESC)?$,iS', ltrim($tri, '/'), $r)) {
				$r = array_pad($r, 3, null);

				// tri par cle
				if ($r[1] == 'cle') {
					if (isset($r[2]) and $r[2]) {
						krsort($this->tableau);
					} else {
						ksort($this->tableau);
					}
				} # {par hasard}
				else {
					if ($r[1] == 'hasard') {
						$k = array_keys($this->tableau);
						shuffle($k);
						$v = [];
						foreach ($k as $cle) {
							$v[$cle] = $this->tableau[$cle];
						}
						$this->tableau = $v;
					} else {
						# {par valeur}
						if ($r[1] == 'valeur') {
							$tv = '%s';
						} # {par valeur/xx/yy} ??
						else {
							$tv = 'table_valeur(%s, ' . var_export($r[1], true) . ')';
						}
						$sortfunc .= '
					$a = ' . sprintf($tv, '$aa') . ';
					$b = ' . sprintf($tv, '$bb') . ';
					if ($a <> $b)
						return ($a ' . (!empty($r[2]) ? '>' : '<') . ' $b) ? -1 : 1;';
					}
				}
			}
		}

		if ($sortfunc) {
			$sortfunc .= "\n return 0;";
			uasort($this->tableau, function ($aa, $bb) use ($sortfunc) {
				return eval($sortfunc);
			});
		}
	}


	/**
	 * Grouper les resultats
	 * {fusion /x/y/z}
	 *
	 **/
	protected function select_groupby() {
		// virer le / initial pour les criteres de la forme {fusion /xx}
		if (strlen($fusion = ltrim($this->command['groupby'][0], '/'))) {
			$vu = [];
			foreach ($this->tableau as $k => $v) {
				$val = table_valeur($v, $fusion);
				if (isset($vu[$val])) {
					unset($this->tableau[$k]);
				} else {
					$vu[$val] = true;
				}
			}
		}
	}


	/**
	 * L'iterateur est-il encore valide ?
	 *
	 * @return bool
	 */
	public function valid() {
		return !is_null($this->cle);
	}

	/**
	 * Retourner la valeur
	 *
	 * @return null
	 */
	public function current() {
		return $this->valeur;
	}

	/**
	 * Retourner la cle
	 *
	 * @return null
	 */
	public function key() {
		return $this->cle;
	}

	/**
	 * Passer a la valeur suivante
	 *
	 * @return void
	 */
	public function next() {
		if ($this->valid()) {
			$this->cle = key($this->tableau);
			$this->valeur = current($this->tableau);
			next($this->tableau);
		}
	}

	/**
	 * Compter le nombre total de resultats
	 *
	 * @return int
	 */
	public function count() {
		if (is_null($this->total)) {
			$this->total = count($this->tableau);
		}

		return $this->total;
	}
}

/*
 * Fonctions de transformation donnee => tableau
 */

/**
 * file -> tableau
 *
 * @param string $data
 * @return array
 */
function inc_file_to_array_dist($data) {
	return preg_split('/\r?\n/', $data);
}

/**
 * plugins -> tableau
 *
 * @return array
 */
function inc_plugins_to_array_dist() {
	include_spip('inc/plugin');

	return liste_chemin_plugin_actifs();
}

/**
 * xml -> tableau
 *
 * @param string $data
 * @return array
 */
function inc_xml_to_array_dist($data) {
	return @XMLObjectToArray(new SimpleXmlIterator($data));
}

/**
 *
 * object -> tableau
 *
 * @param object $object The object to convert
 * @return array
 *
 */
function inc_object_to_array($object) {
	if (!is_object($object) && !is_array($object)) {
		return $object;
	}
	if (is_object($object)) {
		$object = get_object_vars($object);
	}

	return array_map('inc_object_to_array', $object);
}

/**
 * sql -> tableau
 *
 * @param string $data
 * @return array|bool
 */
function inc_sql_to_array_dist($data) {
	# sortir le connecteur de $data
	preg_match(',^(?:(\w+):)?(.*)$,Sm', $data, $v);
	$serveur = (string)$v[1];
	$req = trim($v[2]);
	if ($s = sql_query($req, $serveur)) {
		$r = [];
		while ($t = sql_fetch($s)) {
			$r[] = $t;
		}

		return $r;
	}

	return false;
}

/**
 * json -> tableau
 *
 * @param string $data
 * @return array|bool
 */
function inc_json_to_array_dist($data) {
	if (is_array($json = json_decode($data, true))) {
		return (array)$json;
	}
}

/**
 * csv -> tableau
 *
 * @param string $data
 * @return array|bool
 */
function inc_csv_to_array_dist($data) {
	include_spip('inc/csv');
	list($entete, $csv) = analyse_csv($data);
	array_unshift($csv, $entete);

	include_spip('inc/charsets');
	$i = 1;
	foreach ($entete as $k => $v) {
		if (trim($v) == '') {
			$v = 'col' . $i;
		} // reperer des eventuelles cases vides
		if (is_numeric($v) and $v < 0) {
			$v = '__' . $v;
		} // ne pas risquer d'ecraser une cle numerique
		if (is_numeric($v)) {
			$v = '_' . $v;
		} // ne pas risquer d'ecraser une cle numerique
		$v = strtolower(preg_replace(',\W+,', '_', translitteration($v)));
		foreach ($csv as &$item) {
			$item[$v] = &$item[$k];
		}
		$i++;
	}

	return $csv;
}

/**
 * RSS -> tableau
 *
 * @param string $data
 * @return array|bool
 */
function inc_rss_to_array_dist($data) {
	include_spip('inc/syndic');
	if (is_array($rss = analyser_backend($data))) {
		$tableau = $rss;
	}

	return $tableau;
}

/**
 * atom, alias de rss -> tableau
 *
 * @param string $data
 * @return array|bool
 */
function inc_atom_to_array_dist($data) {
	$rss_to_array = charger_fonction('rss_to_array', 'inc');

	return $rss_to_array($data);
}

/**
 * glob -> tableau
 * lister des fichiers selon un masque, pour la syntaxe cf php.net/glob
 *
 * @param string $data
 * @return array|bool
 */
function inc_glob_to_array_dist($data) {
	$a = glob(
		$data,
		GLOB_MARK | GLOB_NOSORT | GLOB_BRACE
	);

	return $a ? $a : [];
}

/**
 * YAML -> tableau
 *
 * @param string $data
 * @return bool|array
 * @throws Exception
 */
function inc_yaml_to_array_dist($data) {
	include_spip('inc/yaml-mini');
	if (!function_exists('yaml_decode')) {
		throw new Exception('YAML: impossible de trouver la fonction yaml_decode');

		return false;
	}

	return yaml_decode($data);
}


/**
 * pregfiles -> tableau
 * lister des fichiers a partir d'un dossier de base et selon une regexp.
 * pour la syntaxe cf la fonction spip preg_files
 *
 * @param string $dir
 * @param string $regexp
 * @param int $limit
 * @return array|bool
 */
function inc_pregfiles_to_array_dist($dir, $regexp = -1, $limit = 10000) {
	return (array)preg_files($dir, $regexp, $limit);
}

/**
 * ls -> tableau
 * ls : lister des fichiers selon un masque glob
 * et renvoyer aussi leurs donnees php.net/stat
 *
 * @param string $data
 * @return array|bool
 */
function inc_ls_to_array_dist($data) {
	$glob_to_array = charger_fonction('glob_to_array', 'inc');
	$a = $glob_to_array($data);
	foreach ($a as &$v) {
		$b = (array)@stat($v);
		foreach ($b as $k => $ignore) {
			if (is_numeric($k)) {
				unset($b[$k]);
			}
		}
		$b['file'] = preg_replace('`/$`', '', $v) ;
		$v = array_merge(
			pathinfo($v),
			$b
		);
	}

	return $a;
}

/**
 * Object -> tableau
 *
 * @param Object $object
 * @return array|bool
 */
function XMLObjectToArray($object) {
	$xml_array = [];
	for ($object->rewind(); $object->valid(); $object->next()) {
		if (array_key_exists($key = $object->key(), $xml_array)) {
			$key .= '-' . uniqid();
		}
		$vars = get_object_vars($object->current());
		if (isset($vars['@attributes'])) {
			foreach ($vars['@attributes'] as $k => $v) {
				$xml_array[$key][$k] = $v;
			}
		}
		if ($object->hasChildren()) {
			$xml_array[$key][] = XMLObjectToArray(
				$object->current()
			);
		} else {
			$xml_array[$key][] = strval($object->current());
		}
	}

	return $xml_array;
}
