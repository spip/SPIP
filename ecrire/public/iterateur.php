<?php


/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


/**
 * Iterateurs
 * http://php.net/manual/fr/class.iterator.php
 */
class Iter implements Iterator {
	/**
	 * Constructeur & initialise
	 */
	public function __construct() {
		$this->pos = 0;
		$this->total = $this->total();
	}

	/**
	 * revient au depart
	 * @return void
	 */
	public function rewind() {
		$this->pos = 0;
	}

	/**
	 * avons-nous un element
	 * @return void
	 */
	public function valid() {
		return $this->pos<$this->total;
	}

	/**
	 * Valeur courante
	 * @return void
	 */
	public function current() {}

	/**
	 * Cle courante
	 * @return void
	 */
	public function key() {}

	/**
	 * avancer d'un cran
	 * @return void
	 */
	public function next() {
		$this->pos++;
	}

	# Extension SPIP des iterateurs PHP
	/**
	 * type de l'iterateur
	 * @var string
	 */
	protected $type;

	/**
	 * parametres de l'iterateur
	 * @var array
	 */
	protected $command;

	/**
	 * infos de compilateur
	 * @var array
	 */
	protected $info;

	/**
	 * position courante de l'iterateur
	 * @var int
	 */
	protected $pos=null;

	/**
	 * nombre total resultats dans l'iterateur
	 * @var int
	 */
	protected $total=null;

	/**
	 * avancer en position n,
	 * comptee en absolu depuis le debut
	 *
	 * @param int $n
	 *   absolute pos
	 * @param string $continue
	 *   param for sql_ api
	 * @return bool
	 *   success or fail if not implemented
	 */
	public function seek($n=0, $continue=null) {
		if ($this->pos>$n)
			$this->rewind();
		
		while($this->pos<$n AND $this->valid())
			$this->next();
		return true;
	}

	/**
	 * Renvoyer un tableau des donnees correspondantes
	 * a la position courante de l'iterateur
	 *
	 * @return array|bool
	 */
	public function fetch() {
		if ($this->valid()) {
			$r = array('cle' => $this->key(), 'valeur' => $this->current());
			$this->next();
		} else
			$r = false;
		return $r;
	}

	/**
	 * liberer la ressource
	 * @return bool
	 */
	public function free() {
		$this->pos = $this->total = 0;
	  return true;
	}

	/**
	 * Compter le nombre total de resultats
	 * pour #TOTAL_BOUCLE
	 * @return int
	 */
	public function total() {
		if (is_null($this->total))
			$this->total = 0;
		return $this->total;
	}
}

/**
 * Iterateur SQL
 */
class IterSQL extends Iter {

	/**
	 * ressource sql
	 * @var resource|bool
	 */
	protected $sqlresult = false;

	/**
	 * row sql courante
	 * @var array|null
	 */
	protected $row = null;

	/**
	 * selectionner les donnees, ie faire la requete SQL
	 * @return void
	 */
	protected function select() {
		$this->row = null;
		$v = &$this->command;
		$this->sqlresult = calculer_select($v['select'], $v['from'], $v['type'], $v['where'], $v['join'], $v['groupby'], $v['orderby'], $v['limit'], $v['having'], $v['table'], $v['id'], $v['connect'], $this->info);
		$ok = !!$this->sqlresult;
		if ($ok)
			$this->row = sql_fetch($this->sqlresult, $this->command['connect']);
	  $this->pos = 0;
	  $this->total = $this->total();
	}

	/*
	 * array command: les commandes d'initialisation
	 * array info: les infos sur le squelette
	 */
	public function __construct($command, $info=array()) {
		$this->type='SQL';
		$this->command = $command;
		$this->info = $info;
		$this->select();
	}

	/**
	 * Rembobiner
	 * @return bool
	 */
	public function rewind() {
		parent::rewind();
		return $this->seek(0);
	}

	/**
	 * Verifier l'etat de l'iterateur
	 * @return bool
	 */
	public function valid() {
		return $this->sqlresult AND is_array($this->row);
	}

	/**
	 * Valeurs sur la position courante
	 * @return array
	 */
	public function current() {
		return $this->row;
	}
	
	/**
	 * Sauter a une position absolue
	 * @param int $n
	 * @param null|string $continue
	 * @return bool
	 */
	public function seek($n=0, $continue=null) {
		if (!sql_seek($this->sqlresult, $n, $this->command['connect'], $continue)) {
			// SQLite ne sait pas seek(), il faut relancer la query
			$this->free();
			$this->select();
			// et utiliser la methode par defaut pour se deplacer au bon endroit
			parent::seek($n);
			return true;
		}
		$this->row = sql_fetch($this->sqlresult, $this->command['connect']);
		$this->pos = min($n,$this->total());
		return true;
	}

	/**
	 * Avancer d'un cran
	 * @return void
	 */
	public function next(){
		$this->row = sql_fetch($this->sqlresult, $this->command['connect']);
		parent::next();
	}

	/**
	 * Avancer et retourner les donnees pour le nouvel element
	 * @return array|bool|null
	 */
	public function fetch(){
		if ($this->valid()) {
			$r = $this->current();
			$this->next();
		} else
			$r = false;
		return $r;
	}

	/**
	 * liberer les ressources
	 * @return bool
	 */
	public function free(){
		parent::free();
		return sql_free($this->sqlresult, $this->command['connect']);
	}
	
	/**
	 * Compter le nombre de resultats
	 * @return int
	 */
	public function total() {
		if (is_null($this->total))
			if (!$this->sqlresult)
				$this->total = 0;
			else
				$this->total = sql_count($this->sqlresult, $this->command['connect']);
	  return $this->total;
	}
}

/**
 * IterENUM pour iterer sur un intervalle de nombre
 * repondant eventuellement a des conditions de filtrage
 */
class IterENUM extends Iter {

	/**
	 * Valeur entiere de l'iterateur, ce qui est renvoye
	 * @var int
	 */
	protected $n = 0;

	/**
	 * Valeur de depart de l'iteration, zero
	 * @var int
	 */
	protected $start = 0;

	/**
	 * Maximum d'iteration, valeur de securite
	 * @var int
	 */
	protected $max = 1000000;

	/**
	 * Offset dans les resultats
	 * @var int
	 */
	protected $offset = 0;

	/**
	 * Conditions de filtrage
	 * ie criteres de selection
	 * @var array
	 */
	protected $filtre = array();

	/**
	 * Fonction de filtrage compilee a partir des criteres de filtre
	 * @var string
	 */
	protected $func_filtre = null;

	public function __construct($command=array(), $info=array()) {
		$this->type='ENUM';
		$this->command = $command;
		$this->info = $info;

		if (is_array($this->command['where']))
		foreach ($this->command['where'] as $k => $com) {
			switch($com[1]) {
				case 'valeur':
					unset($op);
					if ($com[0] == 'REGEXP')
						$this->filtre[] = 'preg_match("/". '.str_replace('\"', '"', $com[2]).'."/", $'.$com[1].')';
					else if ($com[0] == '=')
						$op = '==';
					else if (in_array($com[0], array('<','<=', '>', '>=')))
						$op = $com[0];

					if ($op)
						$this->filtre[] = '$'.$com[1].$op.str_replace('\"', '"', $com[2]);

					break;
			}

		}

		$this->pos = 0;
	  $this->total = $this->max;
	  
		// critere {2,7}
		if ($this->command['limit']) {
			$limit = explode(',',$this->command['limit']);
			$this->offset = $limit[0];
			$this->total = $limit[1];
		}

		// Appliquer les filtres sur (valeur)
		if ($this->filtre) {
			$this->func_filtre = create_function('$cle,$valeur', $b = 'return ('.join(') AND (', $this->filtre).');');
		}

	  $this->rewind();
	}

	/**
	 * Rembobiner
	 * On part de n=0 et on next() tant qu'on a pas satisfait les filtres,
	 * en bloquant pos=0
	 * @return void
	 */
	public function rewind() {
		$this->n = $this->start-1;
		for ($i=0; $i<=$this->offset; $i++) {
			$this->pos = -1; # forcer la position courante
			$this->next(); # pour filtrage par func_filtre
		}
	}

	/**
	 * L'iterateur est il encore en cours ?
	 * @return bool
	 */
	public function valid(){
		return
			$this->n <= $this->max
			AND $this->pos < $this->total;
	}

	/**
	 * Valeur courante de la valeur
	 * @return int
	 */
	public function current() {
		return $this->n;
	}

	/**
	 * Valeur courante du compteur
	 * @return int
	 */
	public function key() {
		return $this->pos;
	}

	/**
	 * Avancer d'un pas
	 * Ici c'est un peu tordu :
	 * - on incremente pos d'une unite,
	 * car c'est next(), donc on va juste a la position d'apres
	 * - on incremente n jusqu'a ce que les conditions de filtrage
	 * soient satisfaites pour trouver le "prochain" resultat
	 * 
	 * @return void
	 */
	public function next() {
		$this->pos++;
		$this->n++;
		if ($f = $this->func_filtre) {
			while ($this->valid()
			  AND !$f($this->pos,$this->n)) {
				$this->n++;
			}
		}
	}

	/**
	 * Total
	 * @return int
	 */
	public function total() {
		return $this->total;
	}
}


/**
 * IterDATA pour iterer sur des donnees
 */
class IterDATA extends Iter {
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
		parent::rewind();
		reset($this->tableau);
		list($this->cle, $this->valeur) = each($this->tableau);
	}

	protected function select($command) {
		// les commandes connues pour l'iterateur POUR
		// sont : tableau=#ARRAY ; cle=...; valeur=...
		// source URL
		if (isset($this->command['source'])) {
			if (isset($this->command['sourcemode'])
			AND in_array($this->command['sourcemode'],
				array('table', 'array', 'tableau'))
			) {
				if (is_array($a = $this->command['source'])
				OR is_array($a = unserialize($this->command['source']))) {
					$this->tableau = $a;
					$this->ok = true;
				}
			}
			else if (preg_match(',^http://,', $this->command['source'])) {
				include_spip('inc/distant');
				$u = recuperer_page($this->command['source']);
			} else if (@is_readable($this->command['source']))
				$u = spip_file_get_contents($this->command['source']);
			else
				$u = $this->command['source'];

			// tout ce bloc devrait marcher par charger_fonction('xxx_to_array')
			// si c'est du RSS
			if (isset($this->command['sourcemode'])) {
				if ($g = charger_fonction($this->command['sourcemode'] . '_to_array', 'inc', true)) {
					if (is_array($a = $g($u))) {
						$this->tableau = $a;
						$this->ok = true;
					}
				}
				else
				switch ($this->command['sourcemode']) {
					case 'rss':
					case 'atom':
						include_spip('inc/syndic');
						if (is_array($rss = analyser_backend($u))) {
							$this->tableau = $rss;
							$this->ok = true;
						}
						break;
					case 'json':
						if (is_array($json = json_decode($u))) {
							$this->tableau = $json;
							$this->ok = true;
						}
						break;
					case 'yaml':
						include_spip('inc/yaml');
						if (is_array($yaml = yaml_decode($u))) {
							$this->tableau = $yaml;
							$this->ok = true;
						}
						break;
					case 'csv':
						# decodage csv a passer en inc/csv
						# cf. http://www.php.net/manual/en/function.str-getcsv.php#100579 et suiv.
						if (function_exists('str_getcsv')) # PHP 5.3.0
							$this->tableau = str_getcsv($u);
						else
						foreach(preg_split('/\r?\n/',$u) as $ligne)
							$this->tableau[] = explode(',', $ligne);
						$this->ok = true;
				}
			}
		}

		if (is_array($this->command['where']))
		foreach ($this->command['where'] as $k => $com) {
			switch($com[1]) {
				case 'tableau':
					if ($com[0] !== '=') {
						// erreur
					}
					# sql_quote a l'envers : pas propre...
					# c'est pour la compat ascendante avec le critere
					# {tableau=#ENV...} de la boucle POUR de SPIP-Bonux-2
					$x = null;
					eval ('$x = '.str_replace('\"', '"', $com[2]).';');
					if (is_array($x) OR is_array($x = @unserialize($x))) {
						$this->tableau = $x;
						$this->ok = true;
					}
					else
						{
							// erreur
						}
					break;
				case 'cle':
				case 'valeur':
					unset($op);
					if ($com[0] == 'REGEXP')
						$this->filtre[] = 'preg_match("/". '.str_replace('\"', '"', $com[2]).'."/", $'.$com[1].')';
					else if ($com[0] == '=')
						$op = '==';
					else if (in_array($com[0], array('<','<=', '>', '>=')))
						$op = $com[0];

					if ($op)
						$this->filtre[] = '$'.$com[1].$op.str_replace('\"', '"', $com[2]);

					break;
			}

		}

		// Appliquer les filtres sur (cle,valeur)
		if ($this->filtre) {
			$func_filtre = create_function('$cle,$valeur', $b = 'return ('.join(') AND (', $this->filtre).');');
			#var_dump($b);
			foreach($this->tableau as $cle=>$valeur) {
				if (!$func_filtre($cle,$valeur))
					unset($this->tableau[$cle]);
			}
		}

		// critere {2,7}
		if ($this->command['limit']) {
			$limit = explode(',',$this->command['limit']);
			$this->tableau = array_slice($this->tableau,
				$limit[0],$limit[1],true);
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
		parent::next();
		if ($this->valid())
			list($this->cle, $this->valeur) = each($this->tableau);
	}

	/**
	 * Compter le nombre total de resultats
	 * @return int
	 */
	public function total() {
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

?>
