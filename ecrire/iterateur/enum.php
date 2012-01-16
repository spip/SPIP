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

include_spip('iterateur/iterateur');

//
// creer une boucle sur un iterateur ENUM
// annonce au compilo les "champs" disponibles
//
function iterateur_ENUM_dist($b) {
	$b->iterateur = 'ENUM'; # designe la classe d'iterateur
	$b->show = array(
		'field' => array(
			'cle' => 'STRING',
			'valeur' => 'STRING',
		)
	);
	return $b;
}


/**
 * IterateurENUM pour iterer sur un intervalle de nombre
 * repondant eventuellement a des conditions de filtrage
 */
class IterateurENUM implements Iterator {

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

	public function __construct($command=array(), $info=array()) {
		$this->type='ENUM';
		$this->command = $command;
		$this->info = $info;

		$this->pos = 0;
		$this->total = $this->max;

		// critere {2,7}
		if ($this->command['limit']) {
			$limit = explode(',',$this->command['limit']);
			$this->offset = $limit[0];
			$this->total = $limit[1];
		}

		$this->rewind();
	}

	/**
	 * Rembobiner
	 * @return void
	 */
	public function rewind() {
		$this->pos = 0;
		$this->n = $this->start;
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
	}

	/**
	 * Total
	 * @return int
	 */
	public function count() {
		return $this->total;
	}
}


?>
