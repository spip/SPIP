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


//
// Iterateur SQL
//

class Iter implements Iterator {
	# http://php.net/manual/fr/class.iterator.php
	public function __construct() {} // initialise
	public function rewind() {} // revient au depart
	public function valid() {} // avons-nous un element
	public function current() {} // quel est sa valeur
	public function key() {} // quelle est sa cle
	public function next() {} // avancer d'un cran

	# Iter SPIP
	var $type; # type de l'iterateur
	var $command; # parametres de l'iterateur
	var $info; # infos de compilateur

	// avancer en position n
	public function seek($n=0, $continue=null) {
		$this->rewind();
		while($n-->0 AND $this->valid()) $this->next();
		return true;
	}
	public function fetch() {
		if ($this->valid()) {
			$r = array('cle' => $this->key(), 'valeur' => $this->current());
			$this->next();
		} else
			$r = false;
		return $r;
	}
	public function free() {} // liberer la ressource
	public function total() {} // #TOTAL_BOUCLE
}

class IterSQL extends Iter {

	private $sqlresult = false; # ressource sql
	private $row = null; # row sql courante

	private function select() {
		$v = &$this->command;
		$this->sqlresult = calculer_select($v['select'], $v['from'], $v['type'], $v['where'], $v['join'], $v['groupby'], $v['orderby'], $v['limit'], $v['having'], $v['table'], $v['id'], $v['connect'], $this->info);
		$this->ok = !!$this->sqlresult;
		if ($this->ok)
			$this->row = sql_fetch($this->sqlresult, $this->command['connect']);
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
	public function rewind() {
		return $this->seek(0);
	}
	public function valid() {
		return is_array($this->row);
	}
	public function current() {
		return $this->row;
	}
	public function seek($n=0, $continue=null) {
		# SQLite ne sait pas seek(), il faut relancer la query
		if (!$a = sql_seek($this->sqlresult, $this->command['connect'], $n, $continue)) {
			$this->free();
			$this->select();
			return true;
		}
		return $a;
	}
	public function next(){
		$this->row = sql_fetch($this->sqlresult, $this->command['connect']);
	}
	public function fetch(){
		if ($this->valid()) {
			$r = $this->current();
			$this->next();
		} else
			$r = false;
		return $r;
	}
	public function free(){
		return sql_free($this->sqlresult, $this->command['connect']);
	}
	public function total() {
		return sql_count($this->sqlresult, $this->command['connect']);
	}
}

class IterENUM extends Iter {
	private $n = 0;
	private $pos = 0;
	private $start = 0;
	private $offset = 0;
	private $total = 1000000;
	private $max = 1000000;
	private $filtre = array();

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

		// critere {2,7}
		if ($this->command['limit']) {
			$limit = explode(',',$this->command['limit']);
			$this->offset = $limit[0];
			$this->total = $limit[1];
		}


		// Appliquer les filtres sur (valeur)
		if ($this->filtre) {
			$this->filtre = create_function('$cle,$valeur', $b = 'return ('.join(') AND (', $this->filtre).');');
		}

	}
	public function rewind() {
		$this->n = $this->start-1;
		$this->pos = -1;
		for ($i=0; $i<=$this->offset; $i++) {
			$this->next(); # pour filtre
			$this->pos=0;
		}
	}
	public function valid(){
		return
			$this->n <= $this->max
			AND $this->pos < $this->total;
	}
	public function current() {
		return $this->n;
	}
	public function key() {
		return $this->pos;
	}
	public function next() {
		$this->pos++;
		$this->n++;
		if ($f = $this->filtre) {
			while (
			$this->n <= $this->max
			AND !$f($this->pos,$this->n)) {
				$this->n++;
			}
		}
	}

	public function seek($n=0, $continue=null) {
		$this->n = $this->start-1;
		$this->pos = -1;
		for ($i=0; $i<=$n; $i++) {
			$this->next(); # pour filtre
		}
		return true;
	}
	public function total() {
		return $this->total;
	}
}


class IterDATA extends Iter {
	private $tableau = array();
	private $filtre = array();
	private $cle = null;
	private $valeur = null;

	public function __construct($command, $info=array()) {
		$this->type='DATA';
		$this->command = $command;
		$this->info = $info;

		$this->select($command);
	}

	public function rewind() {
		reset($this->tableau);
		list($this->cle, $this->valeur) = each($this->tableau);
	}

	private function select($command) {
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
			$filtre = create_function('$cle,$valeur', $b = 'return ('.join(') AND (', $this->filtre).');');
			#var_dump($b);
			foreach($this->tableau as $cle=>$valeur) {
				if (!$filtre($cle,$valeur))
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
	public function seek($n=0, $continue=null) {
		$this->rewind();
		while($n-->0
		AND list($this->cle, $this->valeur) = each($this->tableau)){};
		return true;
	}
	public function valid(){
		return !is_null($this->cle);
	}
	public function current() {
		return $this->valeur;
	}
	public function key() {
		return $this->cle;
	}
	public function next(){
		if ($this->valid())
			list($this->cle, $this->valeur) = each($this->tableau);
	}
	public function total() {
		return count($this->tableau);
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
