<?php


/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


/**
 * Iterateurs
 * http://php.net/manual/fr/class.iterator.php
 *
 * IterateurSPIP
 * implementation de base
 */
class IterateurSPIP implements Iterator {
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
		return $this->pos < $this->total;
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
	 * aller a la position absolue n,
	 * comptee depuis le debut
	 *
	 * @param int $n
	 *   absolute pos
	 * @param string $continue
	 *   param for sql_ api
	 * @return bool
	 *   success or fail if not implemented
	 */
	#public function seek($n=0, $continue=null) {}

	/**
	 * Renvoyer un tableau des donnees correspondantes
	 * a la position courante de l'iterateur
	 *
	 * @return array|bool
	 */
	#public function fetch() {}

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
	#public function count() {}
}

?>
