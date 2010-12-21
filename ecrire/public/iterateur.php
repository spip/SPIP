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
 * Fabrique d'iterateur
 * permet de charger n'importe quel iterateur IterateurXXX
 * fourni dans le fichier iterateurs/xxx.php
 * 
 */
class IterFactory{
	public static function create($iterateur, $command, $info=null){

		// chercher un iterateur PHP existant (par exemple dans SPL)
		// (il faudrait passer l'argument ->serveur
		// pour etre certain qu'on est sur un iter:)
		if (class_exists($iterateur)) {
			
			// arguments de creation de l'iterateur...
			// (pas glop)
			$a = isset($command['args']) ? $command['args'] : array() ;
			switch (count($a)) {
				case 0:    $iter = new $iterateur();  break;
				case 1:    $iter = new $iterateur($a[0]);  break;
				case 2:    $iter = new $iterateur($a[0], $a[1]);  break;
				case 3:    $iter = new $iterateur($a[0], $a[1], $a[2]);  break;
				case 4:    $iter = new $iterateur($a[0], $a[1], $a[2], $a[3]);  break;
			}
		} else {
			// chercher la classe d'iterateur
			// IterateurXXX
			// definie dans le fichier iterateurs/xxx.php
			$class = "Iterateur".$iterateur;
			if (!include_spip("iterateur/" . strtolower($iterateur))
			  OR !class_exists($class)) {
				die("Iterateur $iterateur non trouv&#233;");
				// si l'iterateur n'existe pas, on se rabat sur le generique
				$iter = new Iterator();
			} else {
				$iter = new $class($command, $info);
			}
		}
		return new IterDecorator($iter, $command, $info);
	}
}


class IterDecorator implements Iterator {
	private $iter;

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

	public function __construct(Iterator $iter, $command, $info){
		$this->iter = $iter;
		$this->command = $command;
		$this->info = $info;
		$this->pos = 0;


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


		// Appliquer les filtres sur (valeur)
		if ($this->filtre) {
			$this->func_filtre = create_function('$cle,$valeur', $b = 'return ('.join(') AND (', $this->filtre).');');
		}

		$this->total = $this->count();
	}
 
	public function next(){
		$this->pos++;
		$this->iter->next();

		while ($this->valid() AND !$this->accept()) {
			$this->iter->next();
		}
	}

	/**
	 * revient au depart
	 * @return void
	 */
	public function rewind() {
		$this->pos = 0;
		$this->iter->rewind();
	}


	/**
	 * avons-nous un element
	 * @return void
	 */
	public function valid() {
		return $this->iter->valid();
	}

	/**
	 * Valeur courante
	 * @return void
	 */
	public function current() {
		return $this->iter->current();
	}


	/**
	 * Cle courante
	 * @return void
	 */
	public function key() {
		return $this->iter->key();
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
	 * nombre maximal de recherche pour $total
	 * si l'iterateur n'implemente pas de fonction specifique
	 */
	 protected $max=100000;
	 
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
	public function seek($n=0, $continue=null) {
		if (!method_exists($this->iter, 'seek') OR !$this->iter->seek($n)) {
			$this->seek_loop($n);
		}
		$this->pos = $n;
		return true;
	}

	/*
	 * aller a la position $n en parcourant
	 * un par un tous les elements
	 */
	private function seek_loop($n) {
		if ($this->pos>$n)
			$this->rewind();

		while($this->pos<$n AND $this->valid()) {
			$this->next();	
		}
		
		return true;
	}

	/**
	 * Avancer de $saut pas
	 * @param  $saut
	 * @param  $max
	 * @return int
	 */
	public function skip($saut, $max=null){
		// pas de saut en arriere autorise pour cette fonction
		if (($saut=intval($saut))<=0) return $this->pos;
		$seek = $this->pos + $saut;
		// si le saut fait depasser le maxi, on libere la resource
		// et on sort
		if (is_null($max))
			$max = $this->count();

		if ($seek>=$max OR $seek>=$this->count()) {
			// sortie plus rapide que de faire next() jusqu'a la fin !
			$this->free();
		  return $max;
		}

	  $this->seek($seek);
	  return $this->pos;
	}

	/**
	 * Renvoyer un tableau des donnees correspondantes
	 * a la position courante de l'iterateur
	 * en controlant si on respecte le filtre
	 *
	 * @return array|bool
	 */
	public function fetch() {
		if (method_exists($this->iter, 'fetch')) {
			return $this->iter->fetch();
		} else {

			while ($this->valid() AND !$this->accept()) {
				$this->next();
			}
			
			if (!$this->valid()) {
				return false;
			}
			
			$r = array(
				'cle' => $this->key(),
				'valeur' => is_object($this->current()) ?
					clone $this->current() :
					$this->current()
			);
			$this->next(); // $r['valeur'] avance aussi d'un cran avec DirectoryIterator ! fichtre !
			return $r;
		}
	}

	public function accept() {
		if ($f = $this->func_filtre) {
			return $f($this->key(), $this->current());
		}
		return true;
	}

	/**
	 * liberer la ressource
	 * @return bool
	 */
	public function free() {
		if (method_exists($this->iter, 'free')) {
			$this->iter->free();
		}
		$this->pos = $this->total = 0;
		return true;
	}

	/**
	 * Compter le nombre total de resultats
	 * pour #TOTAL_BOUCLE
	 * @return int
	 */
	public function count() {
		if (is_null($this->total)) {
			if (method_exists($this->iter, 'count')
			AND !$this->func_filtre) {
				return $this->total = $this->iter->count();
			} else {
				// compter les lignes et rembobiner
				$total = 0;
				$pos = $this->pos; // sauver la position
				$this->rewind();
				while ($this->fetch() and $total < $this->max) {
					$total++;
				}
				$this->seek($pos);
				$this->total = $total;
			}
		}

		return $this->total;
	}
	
}


?>
