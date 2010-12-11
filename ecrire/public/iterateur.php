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


	public function Iter($type) {
		$this->type = $type;
	}

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



?>
