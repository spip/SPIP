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

	public function Iter() {
		$this->type = '??';
	}

	/*
	 * array command: les commandes d'initialisation
	 * array info: les infos sur le squelette
	 */
	public function init($command, $info=array()) {
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
	public function init($command, $info=array()) {
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
	public function init($command, $info=array()) {
		$this->type='POUR';
		$this->command = $command;
		$this->info = $info;

		// les commandes connues pour l'iterateur POUR
		// sont : tableau=#ARRAY ; cle=...; valeur=...
		if (is_array($this->command['where']))
		foreach ($this->command['where'] as $k => $com) {
			switch($com[1]) {
				case 'tableau':
					if ($com[0] !== '=') {
						// erreur
					}
					# sql_quote a l'envers : pas propre...
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
