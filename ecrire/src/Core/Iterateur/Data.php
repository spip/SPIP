<?php

namespace Spip\Core\Iterateur;

use Exception;
use Iterator;

/**
 * Itérateur DATA.
 *
 * Pour itérer sur des données quelconques (transformables en tableau)
 */
class Data extends AbstractIterateur implements Iterator
{
	/**
	 * Calcul du total des elements.
	 *
	 * @var null|int
	 */
	public $total;

	public $type = 'DATA';

	/**
	 * tableau de donnees.
	 *
	 * @var array
	 */
	protected $tableau = [];

	/**
	 * Conditions de filtrage
	 * ie criteres de selection.
	 *
	 * @var array
	 */
	protected $filtre = [];

	/**
	 * Cle courante.
	 *
	 * @var null
	 */
	protected $cle;

	/**
	 * Valeur courante.
	 *
	 * @var null
	 */
	protected $valeur;

	/**
	 * Constructeur.
	 *
	 * @param       $command
	 * @param array $info
	 */
	public function __construct($command, $info = []) {
		parent::__construct($command, $info);

		$this->select($command);
	}

	/**
	 * Revenir au depart.
	 */
	public function rewind(): void {
		reset($this->tableau);
		$this->cle = array_key_first($this->tableau);
		$this->valeur = current($this->tableau);
		next($this->tableau);
	}

	/**
	 * Déclarer les critères exceptions.
	 *
	 * @return array
	 */
	public function exception_des_criteres() {
		return ['tableau'];
	}

	/**
	 * L'iterateur est-il encore valide ?
	 */
	public function valid(): bool {
		return !is_null($this->cle);
	}

	/**
	 * Retourner la valeur.
	 *
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function current() {
		return $this->valeur;
	}

	/**
	 * Retourner la cle.
	 *
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function key() {
		return $this->cle;
	}

	/**
	 * Passer a la valeur suivante.
	 */
	public function next(): void {
		if ($this->valid()) {
			$this->cle = key($this->tableau);
			$this->valeur = current($this->tableau);
			next($this->tableau);
		}
	}

	/**
	 * Compter le nombre total de resultats.
	 *
	 * @return int
	 */
	public function count() {
		if (is_null($this->total)) {
			$this->total = count($this->tableau);
		}

		return $this->total;
	}

	/**
	 * Récupérer depuis le cache si possible.
	 *
	 * @param string $cle
	 *
	 * @return mixed
	 */
	protected function cache_get($cle) {
		if (!$cle) {
			return;
		}
		// utiliser memoization si dispo
		if (!function_exists('cache_get')) {
			return;
		}

		return cache_get($cle);
	}

	/**
	 * Stocker en cache si possible.
	 *
	 * @param string     $cle
	 * @param int        $ttl
	 * @param null|mixed $valeur
	 *
	 * @return bool
	 */
	protected function cache_set($cle, $ttl, $valeur = null) {
		if (!$cle) {
			return;
		}
		if (is_null($valeur)) {
			$valeur = $this->tableau;
		}
		// utiliser memoization si dispo
		if (!function_exists('cache_set')) {
			return;
		}

		return cache_set(
			$cle,
			[
				'data' => $valeur,
				'time' => time(),
				'ttl' => $ttl,
			],
			3600 + $ttl
		);
		// conserver le cache 1h de plus que la validite demandee,
		// pour le cas ou le serveur distant ne reponde plus
	}

	/**
	 * Aller chercher les données de la boucle DATA.
	 *
	 * @param array $command
	 *
	 * @throws Exception
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
		//var_dump($this->tableau);
	}

	/**
	 * Aller chercher les donnees de la boucle DATA
	 * depuis une source
	 * {source format, [URL], [arg2]...}.
	 */
	protected function select_source() {
		// un peu crado : avant de charger le cache il faut charger
		// les class indispensables, sinon PHP ne saura pas gerer
		// l'objet en cache ; cf plugins/icalendar
		// perf : pas de fonction table_to_array ! (table est deja un array)
		if (
			isset($this->command['sourcemode'])
			and !in_array($this->command['sourcemode'], ['table', 'array', 'tableau'])
		) {
			charger_fonction($this->command['sourcemode'] . '_to_array', 'inc', true);
		}

		// le premier argument peut etre un array, une URL etc.
		$src = $this->command['source'][0];

		// avons-nous un cache dispo ?
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
			and ($cache['time'] + ($ttl ?? $cache['ttl'])
				> time())
			and !(
				'recalcul' === _request('var_mode')
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
							and $a = str_replace('&quot;', '"', $a) // fragile!
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
					"[%s, %s] {$e}",
					$src,
					$this->command['sourcemode']
				);
				erreur_squelette([$err, []]);
				$this->err = true;
			}
		}

		// en cas d'erreur, utiliser le cache si encore dispo
		if (
			$this->err
			and $cache
		) {
			$this->tableau = $cache['data'];
			$this->err = false;
		}
	}

	/**
	 * Retourne un tableau donne depuis un critère liste.
	 *
	 * Critère `{liste X1, X2, X3}`
	 *
	 * @see critere_DATA_liste_dist()
	 */
	protected function select_liste() {
		// s'il n'y a qu'une valeur dans la liste, sans doute une #BALISE
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
	 * Critere {enum Xmin, Xmax}.
	 */
	protected function select_enum() {
		// s'il n'y a qu'une valeur dans la liste, sans doute une #BALISE
		if (!isset($this->command['enum'][1])) {
			if (!is_array($this->command['enum'][0])) {
				$this->command['enum'] = explode(',', $this->command['enum'][0]);
			} else {
				$this->command['enum'] = $this->command['enum'][0];
			}
		}
		if ((is_countable($this->command['enum']) ? count($this->command['enum']) : 0) >= 3) {
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
	 * {datapath query.results}.
	 */
	protected function select_datapath() {
		$base = reset($this->command['datapath']);
		if (strlen($base = ltrim(trim($base), '/'))) {
			$this->tableau = table_valeur($this->tableau, $base);
			if (!is_array($this->tableau)) {
				$this->tableau = [];
				$this->err = true;
				spip_log("datapath '{$base}' absent");
			}
		}
	}

	/**
	 * Ordonner les resultats
	 * {par x}.
	 */
	protected function select_orderby() {
		$sortfunc = '';
		$aleas = 0;
		foreach ($this->command['orderby'] as $tri) {
			// virer le / initial pour les criteres de la forme {par /xx}
			if (preg_match(',^\.?([/\w:_-]+)( DESC)?$,iS', ltrim($tri, '/'), $r)) {
				$r = array_pad($r, 3, null);

				// tri par cle
				if ('cle' == $r[1]) {
					if (isset($r[2]) and $r[2]) {
						krsort($this->tableau);
					} else {
						ksort($this->tableau);
					}
				} // {par hasard}
				else {
					if ('hasard' == $r[1]) {
						$k = array_keys($this->tableau);
						shuffle($k);
						$v = [];
						foreach ($k as $cle) {
							$v[$cle] = $this->tableau[$cle];
						}
						$this->tableau = $v;
					} else {
						// {par valeur}
						if ('valeur' == $r[1]) {
							$tv = '%s';
						} // {par valeur/xx/yy} ??
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
			uasort($this->tableau, fn ($aa, $bb) => eval($sortfunc));
		}
	}

	/**
	 * Grouper les resultats
	 * {fusion /x/y/z}.
	 */
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
}
