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
 * Gestion de l'itérateur SQL
 *
 * @package SPIP\Core\Iterateur\SQL
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Itérateur SQL
 *
 * Permet d'itérer sur des données en base de données
 */
class IterateurSQL implements Iterator {

	/**
	 * ressource sql
	 *
	 * @var resource|bool
	 */
	protected $sqlresult = false;

	/**
	 * row sql courante
	 *
	 * @var array|null
	 */
	protected $row = null;

	protected $firstseek = false;

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
	 * selectionner les donnees, ie faire la requete SQL
	 *
	 * @return void
	 */
	protected function select() {
		$this->row = null;
		$v = &$this->command;
		$this->sqlresult = calculer_select(
			$v['select'],
			$v['from'],
			$v['type'],
			$v['where'],
			$v['join'],
			$v['groupby'],
			$v['orderby'],
			$v['limit'],
			$v['having'],
			$v['table'],
			$v['id'],
			$v['connect'],
			$this->info
		);
		$this->err = !$this->sqlresult;
		$this->firstseek = false;
		$this->pos = -1;

		// pas d'init a priori, le calcul ne sera fait qu'en cas de besoin (provoque une double requete souvent inutile en sqlite)
		//$this->total = $this->count();
	}

	/*
	 * array command: les commandes d'initialisation
	 * array info: les infos sur le squelette
	 */
	public function __construct($command, $info = []) {
		$this->type = 'SQL';
		$this->command = $command;
		$this->info = $info;
		$this->select();
	}

	/**
	 * Rembobiner
	 *
	 * @return bool
	 */
	public function rewind() {
		return ($this->pos > 0)
			? $this->seek(0)
			: true;
	}

	/**
	 * Verifier l'etat de l'iterateur
	 *
	 * @return bool
	 */
	public function valid() {
		if ($this->err) {
			return false;
		}
		if (!$this->firstseek) {
			$this->next();
		}

		return is_array($this->row);
	}

	/**
	 * Valeurs sur la position courante
	 *
	 * @return array
	 */
	public function current() {
		return $this->row;
	}

	public function key() {
		return $this->pos;
	}

	/**
	 * Sauter a une position absolue
	 *
	 * @param int $n
	 * @param null|string $continue
	 * @return bool
	 */
	public function seek($n = 0, $continue = null) {
		if (!sql_seek($this->sqlresult, $n, $this->command['connect'], $continue)) {
			// SQLite ne sait pas seek(), il faut relancer la query
			// si la position courante est apres la position visee
			// il faut relancer la requete
			if ($this->pos > $n) {
				$this->free();
				$this->select();
				$this->valid();
			}
			// et utiliser la methode par defaut pour se deplacer au bon endroit
			// (sera fait en cas d'echec de cette fonction)
			return false;
		}
		$this->row = sql_fetch($this->sqlresult, $this->command['connect']);
		$this->pos = min($n, $this->count());

		return true;
	}

	/**
	 * Avancer d'un cran
	 *
	 * @return void
	 */
	public function next() {
		$this->row = sql_fetch($this->sqlresult, $this->command['connect']);
		$this->pos++;
		$this->firstseek |= true;
	}

	/**
	 * Avancer et retourner les donnees pour le nouvel element
	 *
	 * @return array|bool|null
	 */
	public function fetch() {
		if ($this->valid()) {
			$r = $this->current();
			$this->next();
		} else {
			$r = false;
		}

		return $r;
	}

	/**
	 * liberer les ressources
	 *
	 * @return bool
	 */
	public function free() {
		if (!$this->sqlresult) {
			return true;
		}
		$a = sql_free($this->sqlresult, $this->command['connect']);
		$this->sqlresult = null;

		return $a;
	}

	/**
	 * Compter le nombre de resultats
	 *
	 * @return int
	 */
	public function count() {
		if (is_null($this->total)) {
			if (!$this->sqlresult) {
				$this->total = 0;
			} else {
				# cas count(*)
				if (in_array('count(*)', $this->command['select'])) {
					$this->valid();
					$s = $this->current();
					$this->total = $s['count(*)'];
				} else {
					$this->total = sql_count($this->sqlresult, $this->command['connect']);
				}
			}
		}

		return $this->total;
	}
}
