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

class Iter {
	var $ok = false;
	var $type;
	var $command;
	var $info;

	private $result = false;

	/*
	 * array command: les commandes d'initialisation
	 * array info: les infos sur le squelette
	 */
	public function Iter($command, $info=array()) {
		$this->type = '??';
		$this->command = $command;
		$this->info = $info;
	}
	public function seek($n=0, $continue=null) {}
	public function next() {}
	public function free() {}
	public function count() {}
}

class IterSQL extends Iter {
	var $ok = false;
	var $type;
	var $command;
	var $info;

	private $result = false;

	private function select() {
		$v = &$this->command;
		$this->result = calculer_select($v['select'], $v['from'], $v['type'], $v['where'], $v['join'], $v['groupby'], $v['orderby'], $v['limit'], $v['having'], $v['table'], $v['id'], $v['connect'], $this->info);
		$this->ok = !!$this->result;
	}

	/*
	 * array command: les commandes d'initialisation
	 * array info: les infos sur le squelette
	 */
	public function IterSQL($command, $info=array()) {
		$this->type='SQL';
		$this->command = $command;
		$this->info = $info;
		$this->select();
	}
	public function seek($n=0, $continue=null) {
		# SQLite ne sait pas seek(), il faut relancer la query
		if (!$a = sql_seek($this->result, $this->command['connect'], $n, $continue)) {
			$this->free();
			$this->select();
			return true; # ??
		}
		return $a;
	}
	public function next(){
		return sql_fetch($this->result, $this->command['connect']);
	}
	public function free(){
		return sql_free($this->result, $this->command['connect']);
	}
	public function count() {
		return sql_count($this->result, $this->command['connect']);
	}
}

class IterENUM extends Iter {
	var $ok = true;
	var $type;
	var $command;
	var $info;

	var $n = 0;
	var $max = 1000000;

	var $filtre = array();

	private $result = false;

	/*
	 * array command: les commandes d'initialisation
	 * array info: les infos sur le squelette
	 */
	public function IterENUM($command, $info=array()) {
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
			$this->n = $limit[0];
			$this->max = $limit[0]+$limit[1]-1;
		}


		// Appliquer les filtres sur (valeur)
		if ($this->filtre) {
			$this->filtre = create_function('$valeur', $b = 'return ('.join(') AND (', $this->filtre).');');
		}

	}
	public function seek($n=0, $continue=null) {
		$this->n = $n;
		return true;
	}
	public function next() {
		if ($f = $this->filtre) {
			while (
			$this->n < $this->max
			AND !$f($a = $this->n++)){};
		} else
			$a = $this->n++;

		if ($this->n <= 1+$this->max)
			return array('valeur' => $a);
	}
	public function free(){
	}
	public function count() {
		return $this->max;
	}
}


class IterPOUR extends Iter {
	var $ok = false;
	var $type;
	var $command;
	var $info;

	var $tableau = array();
	var $filtre = array();

	private $result = false;

	/*
	 * array command: les commandes d'initialisation
	 * array info: les infos sur le squelette
	 */
	public function IterPOUR($command, $info=array()) {
		$this->type='POUR';
		$this->command = $command;
		$this->info = $info;

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

			// tout ce bloc devrait marcher par charger_fonction('xxx_to_array')
			// si c'est du RSS
			if (isset($this->command['sourcemode'])) {
				switch ($this->command['sourcemode']) {
					case 'rss':
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
						foreach(explode("\n",$u) as $ligne)
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


		reset($this->tableau);
		#var_dump($this->tableau);
	}
	public function seek($n=0, $continue=null) {
		reset($this->tableau);
		while($n-->0 AND list($cle, $valeur) = each($this->tableau)){};
		return true;
	}
	public function next(){
		if (list($cle, $valeur) = each($this->tableau)) {
			return array('cle' => $cle, 'valeur' => $valeur);
		}
	}
	public function free(){
	}
	public function count() {
		return count($this->tableau);
	}
}



?>
