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

include_spip('iterateurs/iterateur');

/**
 * Iterateur SQL
 */
class IterateurSQL extends IterateurSPIP {

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
			// si la position courante est apres la position visee
			// il faut relancer la requete
			if ($this->pos>$n){
				$this->free();
				$this->select();
			}
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
		if (!$this->sqlresult) return true;
		$a = sql_free($this->sqlresult, $this->command['connect']);
	  $this->sqlresult = null;
	  return $a;
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

?>
