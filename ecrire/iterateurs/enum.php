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

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('iterateurs/iterateur');

//
// creer une boucle sur un iterateur ENUM
// annonce au compilo les "champs" disponibles
//
function iterateurs_ENUM_dist($b) {
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
class IterateurENUM extends IterateurSPIP {

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

		$op = '';
		if (is_array($this->command['where'])) {
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


?>
