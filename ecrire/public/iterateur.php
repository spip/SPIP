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



class IterDecorator extends FilterIterator {
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
		parent::__construct($iter);
		parent::rewind(); // remettre a la premiere position (bug? connu de FilterIterator)
		
		// recuperer l'iterateur transmis
		$this->iter = $this->getInnerIterator();
		$this->command = $command;
		$this->info = $info;
		$this->pos = 0;

		// chercher la liste des champs a retournes par
		// fetch si l'objet ne les calcule pas tout seul
		if (!method_exists($this->iter, 'fetch')) {
			$this->calculer_select();
			$this->calculer_filtres();
		}

		$this->total = $this->count();
	}


	// calcule les elements a retournes par fetch()
	// enleve les elements inutiles du select()
	// 
	private function calculer_select() {
		$select = &$this->command['select'];
		foreach($select as $s) {
			// /!\ $s = '.nom'
			if ($s[0] == '.') {
				$s = substr($s, 1);
			}
			$this->select[] = $s;
		}
	}

	// recuperer la valeur d'une balise #X
	// en fonction des methodes 
	// et proprietes disponibles
	public function get_select($nom) {
		if (method_exists($this->iter, $nom)) {
			return $this->iter->$nom();
		}
		/*
		if (property_exists($this->iter, $nom)) {
			return $this->iter->$nom;
		}*/
		// cle et valeur par defaut
		if (method_exists($this, $nom)) {
			return $this->$nom();
		}
		// big erreur ...
		return '';
	}

	
	private function calculer_filtres() {
		
		// Issu de calculer_select() de public/composer L.519
		// [todo] externaliser...
		//
		// retirer les criteres vides:
		// {X ?} avec X absent de l'URL
		// {par #ENV{X}} avec X absent de l'URL
		// IN sur collection vide (ce dernier devrait pouvoir etre fait a la compil)
		$where = &$this->command['where'];
		$menage = false;
		foreach($where as $k => $v) { 
			if (is_array($v)){
				if ((count($v)>=2) && ($v[0]=='REGEXP') && ($v[2]=="'.*'")) $op= false;
				elseif ((count($v)>=2) && ($v[0]=='LIKE') && ($v[2]=="'%'")) $op= false;
				else $op = $v[0] ? $v[0] : $v;
			} else $op = $v;
			if ((!$op) OR ($op==1) OR ($op=='0=0')) {
				unset($where[$k]);
				$menage = true;
			}
		}
		foreach($where as $k => $v) {
			// 3 possibilites : count($v) =
			// * 1 : {x y} ; on recoit $v[0] = y
			// * 2 : {x !op y} ; on recoit $v[0] = 'NOT', $v[1] = array() // array du type {x op y}
			// * 3 : {x op y} ; on recoit $v[0] = 'op', $v[1] = x, $v[2] = y

			// 1 : forcement traite par un critere, on passe
			if (count($v) == 1) {
				continue;
			}
			if (count($v) == 2) {
				$this->ajouter_filtre($v[1][1], $v[1][0], $v[1][2], 'NOT');
			}
			if (count($v) == 3) {
				$this->ajouter_filtre($v[1], $v[0], $v[2]);
			}
		}

		// Appliquer les filtres sur (valeur)
		if ($this->filtre) {
			$this->func_filtre = create_function('$me', $b = 'return ('.join(') AND (', $this->filtre).');');
		}
	}



	protected function ajouter_filtre($cle, $op, $valeur, $not=false) {
		if (method_exists($this->iter, 'exception_des_criteres')) {
			if (in_array($cle, $this->iter->exception_des_criteres())) {
				return;
			}
		}
		# [todo ?] analyser le filtre pour refuser ce qu'on ne sait pas traiter ?
		# mais c'est normalement deja opere par calculer_critere_infixe()
		# qui regarde la description 'desc' (en casse reelle d'ailleurs : {isDir=1}
		# ne sera pas vu si l'on a defini desc['field']['isdir'] pour que #ISDIR soit present.
		# il faudrait peut etre definir les 2 champs isDir et isdir... a reflechir...
		
		# if (!in_array($cle, array('cle', 'valeur')))
		#	return;
		
		$filtre = '';
		
		if ($op == 'REGEXP')
			$filtre = '@preg_match("/". '.str_replace('\"', '"', $valeur).'."/", $'.$cle.')';
		else if ($op == '=')
			$op = '==';
		else if (!in_array($op, array('<','<=', '>', '>='))) {
			spip_log('operateur non reconnu ' . $op); // [todo] mettre une erreur de squelette
			$op = '';
		}
	
		if ($op)
			$filtre = '$me->get_select(\''.$cle.'\')'.$op.str_replace('\"', '"', $valeur);

		if ($not)
			$filtre = "!($filtre)";
			
		if ($filtre) {
			$this->filtre[] = $filtre;
		}
	}

	
	public function next(){
		$this->pos++;
		parent::next();
	}

	/**
	 * revient au depart
	 * @return void
	 */
	public function rewind() {
		$this->pos = 0;
		parent::rewind();
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
	 * Liste des champs a inserer dans les $row
	 * retournes par ->fetch()
	 */
	 protected $select=array();

	 
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
		if ($this->func_filtre OR !method_exists($this->iter, 'seek') OR !$this->iter->seek($n)) {
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

			$r = array();
			foreach ($this->select as $nom) {
				$r[$nom] = $this->get_select($nom);
			}

			$this->next(); // $r['valeur'] avance aussi d'un cran avec DirectoryIterator ! fichtre !
			return $r;
		}
	}

	// retourner la cle pour #CLE
	public function cle() {
		return $this->key();
	}
	
	// retourner la valeur pour #VALEUR
	public function valeur() {
		# attention PHP est mechant avec les objets, parfois il ne les
		# clone pas proprement (directoryiterator sous php 5.2.2)
		# on se rabat sur la version __toString()
		if (is_object($v = $this->current())) {
			if (method_exists($v, '__toString'))
				$v = $v->__toString();
			else
				$v = (array) $v;
		}
		return $v;
	}

	/**
	 * Accepte t'on l'entree courante lue ?
	 * On execute les filtres pour le savoir. 
	**/
	public function accept() {
		if ($f = $this->func_filtre) {
			return $f($this);
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
