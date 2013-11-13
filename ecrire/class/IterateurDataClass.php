<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2012                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

if (!defined('_DATA_SOURCE_MAX_SIZE')) define('_DATA_SOURCE_MAX_SIZE',2*1048576);

/**
 * IterateurDATA pour iterer sur des donnees
 */
class IterateurDATA implements Iterator
{
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
	public function __construct($command, $info=array())
	{
		$this->type='DATA';
		$this->command = $command;
		$this->info = $info;

		$this->select($command);
	}

	/**
	 * Revenir au depart
	 * @return void
	 */
	public function rewind()
	{
		reset($this->tableau);
		list($this->cle, $this->valeur) = each($this->tableau);
	}

	/**
	 * Declarer les criteres exceptions
	 * @return array
	 */
	public function exception_des_criteres()
	{
		return array('tableau');
	}

	/**
	 * Recuperer depuis le cache si possible
	 * @param  $cle
	 * @return
	 */
	protected function cache_get($cle)
	{
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
	protected function cache_set($cle, $ttl, $valeur = null)
	{
		if (!$cle) return;
		if (is_null($valeur)) {
			$valeur = $this->tableau;
		}
		# utiliser memoization si dispo
		include_spip('inc/memoization');
		if (!function_exists('cache_set')) return;
		return cache_set($cle,
			array(
				'data' => $valeur,
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
	protected function select($command)
	{

		// l'iterateur DATA peut etre appele en passant (data:type)
		// le type se retrouve dans la commande 'from'
		// dans ce cas la le critere {source}, si present, n'a pas besoin du 1er argument
		if (isset($this->command['from'][0])) {
			if (isset($this->command['source']) and is_array($this->command['source'])) {
				array_unshift($this->command['source'], $this->command['sourcemode']);
			}
			$this->command['sourcemode'] = $this->command['from'][0];
		}

		// cherchons defferents moyens de creer le tableau de donnees
		// les commandes connues pour l'iterateur DATA
		// sont : {tableau #ARRAY} ; {cle=...} ; {valeur=...}
		
		// {source format, [URL], [arg2]...}
		if (isset($this->command['source'])
		AND isset($this->command['sourcemode'])) {
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
			spip_log("erreur datasource ".var_export($command,true));
		}

		// {datapath query.results}
		// extraire le chemin "query.results" du tableau de donnees
		if (!$this->err
		AND isset($this->command['datapath'])
		AND is_array($this->command['datapath'])) {
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
	protected function select_source()
	{
		# un peu crado : avant de charger le cache il faut charger
		# les class indispensables, sinon PHP ne saura pas gerer
		# l'objet en cache ; cf plugins/icalendar
		# perf : pas de fonction table_to_array ! (table est deja un array)
		if (isset($this->command['sourcemode'])
		AND !in_array($this->command['sourcemode'],array('table', 'array', 'tableau')))
			charger_fonction($this->command['sourcemode'] . '_to_array', 'inc', true);

		# le premier argument peut etre un array, une URL etc.
		$src = $this->command['source'][0];

		# avons-nous un cache dispo ?
		$cle = null;
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
			else {
				if (preg_match(',^https?://,', $src)) {
					include_spip('inc/distant');
					$u = recuperer_page($src, false, false, _DATA_SOURCE_MAX_SIZE);
					if (!$u)
						throw new Exception("404");
					if (!isset($ttl)) $ttl = 24*3600;
				} else if (@is_dir($src)) {
					$u = $src;
					if (!isset($ttl)) $ttl = 10;
				} else if (@is_readable($src) && @is_file($src)) {
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
			}

			if (!is_array($this->tableau))
				$this->err = true;

			if (!$this->err AND isset($ttl) and $ttl>0)
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


	/**
	 * Retourne un tableau donne depuis un critere liste
	 * Critere {liste X1, X2, X3}
	 * 
	**/
	protected function select_liste()
	{
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
	protected function select_enum()
	{
		# s'il n'y a qu'une valeur dans la liste, sans doute une #BALISE
		if (!isset($this->command['enum'][1])) {
			if (!is_array($this->command['enum'][0])) {
				$this->command['enum'] = explode(',', $this->command['enum'][0]);
			} else {
				$this->command['enum'] = $this->command['enum'][0];
			}
		}
		if (count($this->command['enum'])>=3)
			$enum = range(array_shift($this->command['enum']),array_shift($this->command['enum']),array_shift($this->command['enum']));
		else
			$enum = range(array_shift($this->command['enum']),array_shift($this->command['enum']));
		$this->tableau = $enum;
	}


	/**
	 * extraire le chemin "query.results" du tableau de donnees
	 * {datapath query.results}
	 * 
	**/
	protected function select_datapath()
	{
		list(,$base) = each($this->command['datapath']);
		if (strlen($base = ltrim(trim($base),"/"))) {
			$this->tableau = table_valeur($this->tableau, $base);
			if (!is_array($this->tableau)) {
				$this->tableau = array();
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
	protected function select_orderby()
	{
		$sortfunc = '';
		$aleas = 0;
		foreach($this->command['orderby'] as $tri) {
			// virer le / initial pour les criteres de la forme {par /xx}
			if (preg_match(',^\.?([/\w]+)( DESC)?$,iS', ltrim($tri, '/'), $r)) {
				// tri par cle
				if ($r[1] == 'cle') {
					if (isset($r2) and $r[2])
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


	/**
	 * Grouper les resurltats
	 * {fusion /x/y/z}
	 * 
	**/
	protected function select_groupby()
	{
		// virer le / initial pour les criteres de la forme {fusion /xx}
		if (strlen($fusion = ltrim($this->command['groupby'][0], '/'))) {
			$vu = array();
			foreach($this->tableau as $k => $v) {
				$val = table_valeur($v, $fusion);
				if (isset($vu[$val]))
					unset($this->tableau[$k]);
				else
					$vu[$val] = true;
			}
		}
	}
	
	
	/**
	 * L'iterateur est-il encore valide ?
	 * @return bool
	 */
	public function valid()
	{
		return !is_null($this->cle);
	}

	/**
	 * Retourner la valeur
	 * @return null
	 */
	public function current()
	{
		return $this->valeur;
	}

	/**
	 * Retourner la cle
	 * @return null
	 */
	public function key()
	{
		return $this->cle;
	}

	/**
	 * Passer a la valeur suivante
	 * @return void
	 */
	public function next()
	{
		if ($this->valid())
			list($this->cle, $this->valeur) = each($this->tableau);
	}

	/**
	 * Compter le nombre total de resultats
	 * @return int
	 */
	public function count()
	{
		if (is_null($this->total))
			$this->total = count($this->tableau);
	  return $this->total;
	}
}
