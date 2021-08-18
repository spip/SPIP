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

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Fabrique d'iterateur
 * permet de charger n'importe quel iterateur IterateurXXX
 * fourni dans le fichier iterateurs/xxx.php
 *
 */
class IterFactory {
	public static function create($iterateur, $command, $info = null) {

		// cas des SI {si expression} analises tres tot
		// pour eviter le chargement de tout iterateur
		if (isset($command['si'])) {
			foreach ($command['si'] as $si) {
				if (!$si) {
					// $command pour boucle SQL peut generer des erreurs de compilation
					// s'il est transmis alors qu'on est dans un iterateur vide
					return new IterDecorator(new EmptyIterator(), array(), $info);
				}
			}
		}

		// chercher un iterateur PHP existant (par exemple dans SPL)
		// (il faudrait passer l'argument ->sql_serveur
		// pour etre certain qu'on est sur un "php:")
		if (class_exists($iterateur)) {
			$a = isset($command['args']) ? $command['args'] : array();

			// permettre de passer un Iterateur directement {args #ITERATEUR} :
			// si on recoit deja un iterateur en argument, on l'utilise
			if (count($a) == 1 and is_object($a[0]) and is_subclass_of($a[0], 'Iterator')) {
				$iter = $a[0];

				// sinon, on cree un iterateur du type donne
			} else {
				// arguments de creation de l'iterateur...
				// (pas glop)
				try {
					switch (count($a)) {
						case 0:
							$iter = new $iterateur();
							break;
						case 1:
							$iter = new $iterateur($a[0]);
							break;
						case 2:
							$iter = new $iterateur($a[0], $a[1]);
							break;
						case 3:
							$iter = new $iterateur($a[0], $a[1], $a[2]);
							break;
						case 4:
							$iter = new $iterateur($a[0], $a[1], $a[2], $a[3]);
							break;
					}
				} catch (Exception $e) {
					spip_log("Erreur de chargement de l'iterateur $iterateur");
					spip_log($e->getMessage());
					$iter = new EmptyIterator();
				}
			}
		} else {
			// chercher la classe d'iterateur
			// IterateurXXX
			// definie dans le fichier iterateurs/xxx.php
			$class = 'Iterateur' . $iterateur;
			if (!class_exists($class)) {
				if (!include_spip('iterateur/' . strtolower($iterateur))
					or !class_exists($class)
				) {
					die("Iterateur $iterateur non trouv&#233;");
					// si l'iterateur n'existe pas, on se rabat sur le generique
					# $iter = new EmptyIterator();
				}
			}
			$iter = new $class($command, $info);
		}

		return new IterDecorator($iter, $command, $info);
	}
}


class IterDecorator extends FilterIterator {
	private $iter;

	/**
	 * Conditions de filtrage
	 * ie criteres de selection
	 *
	 * @var array
	 */
	protected $filtre = array();

	/**
	 * Fonction de filtrage compilee a partir des criteres de filtre
	 *
	 * @var string
	 */
	protected $func_filtre = null;

	/**
	 * Critere {offset, limit}
	 *
	 * @var int
	 * @var int
	 */
	protected $offset = null;
	protected $limit = null;

	/**
	 * nombre d'elements recuperes depuis la position 0,
	 * en tenant compte des filtres
	 *
	 * @var int
	 */
	protected $fetched = 0;

	/**
	 * Y a t'il une erreur ?
	 *
	 * @var bool
	 **/
	protected $err = false;

	/**
	 * Drapeau a activer en cas d'echec
	 * (select SQL errone, non chargement des DATA, etc)
	 */
	public function err() {
		if (method_exists($this->iter, 'err')) {
			return $this->iter->err();
		}
		if (property_exists($this->iter, 'err')) {
			return $this->iter->err;
		}

		return false;
	}

	public function __construct(Iterator $iter, $command, $info) {
		parent::__construct($iter);
		parent::rewind(); // remettre a la premiere position (bug? connu de FilterIterator)

		// recuperer l'iterateur transmis
		$this->iter = $this->getInnerIterator();
		$this->command = $command;
		$this->info = $info;
		$this->pos = 0;
		$this->fetched = 0;

		// chercher la liste des champs a retourner par
		// fetch si l'objet ne les calcule pas tout seul
		if (!method_exists($this->iter, 'fetch')) {
			$this->calculer_select();
			$this->calculer_filtres();
		}

		// emptyIterator critere {si} faux n'a pas d'erreur !
		if (isset($this->iter->err)) {
			$this->err = $this->iter->err;
		}

		// pas d'init a priori, le calcul ne sera fait qu'en cas de besoin (provoque une double requete souvent inutile en sqlite)
		//$this->total = $this->count();
	}


	// calcule les elements a retournes par fetch()
	// enleve les elements inutiles du select()
	//
	private function calculer_select() {
		if ($select = &$this->command['select']) {
			foreach ($select as $s) {
				// /!\ $s = '.nom'
				if ($s[0] == '.') {
					$s = substr($s, 1);
				}
				$this->select[] = $s;
			}
		}
	}

	// recuperer la valeur d'une balise #X
	// en fonction des methodes
	// et proprietes disponibles
	public function get_select($nom) {
		if (is_object($this->iter)
			and method_exists($this->iter, $nom)
		) {
			try {
				return $this->iter->$nom();
			} catch (Exception $e) {
				// #GETCHILDREN sur un fichier de DirectoryIterator ...
				spip_log("Methode $nom en echec sur " . get_class($this->iter));
				spip_log("Cela peut être normal : retour d'une ligne de resultat ne pouvant pas calculer cette methode");

				return '';
			}
		}
		/*
		if (property_exists($this->iter, $nom)) {
			return $this->iter->$nom;
		}*/
		// cle et valeur par defaut
		// ICI PLANTAGE SI ON NE CONTROLE PAS $nom
		if (in_array($nom, array('cle', 'valeur'))
			and method_exists($this, $nom)
		) {
			return $this->$nom();
		}

		// Par defaut chercher en xpath dans la valeur()
		return table_valeur($this->valeur(), $nom, null);
	}


	private function calculer_filtres() {

		// Issu de calculer_select() de public/composer L.519
		// TODO: externaliser...
		//
		// retirer les criteres vides:
		// {X ?} avec X absent de l'URL
		// {par #ENV{X}} avec X absent de l'URL
		// IN sur collection vide (ce dernier devrait pouvoir etre fait a la compil)
		if ($where = &$this->command['where']) {
			foreach ($where as $k => $v) {
				if (is_array($v)) {
					if ((count($v) >= 2) && ($v[0] == 'REGEXP') && ($v[2] == "'.*'")) {
						$op = false;
					} elseif ((count($v) >= 2) && ($v[0] == 'LIKE') && ($v[2] == "'%'")) {
						$op = false;
					} else {
						$op = $v[0] ? $v[0] : $v;
					}
				} else {
					$op = $v;
				}
				if ((!$op) or ($op == 1) or ($op == '0=0')) {
					unset($where[$k]);
				}
				// traiter {cle IN a,b} ou {valeur !IN a,b}
				if (preg_match(',^\(([\w/]+)(\s+NOT)?\s+IN\s+(\(.*\))\)$,', $op, $regs)) {
					$this->ajouter_filtre($regs[1], 'IN', $regs[3], $regs[2]);
					unset($op, $where[$k]);
				}
			}
			foreach ($where as $k => $v) {
				// 3 possibilites : count($v) =
				// * 1 : {x y} ; on recoit $v[0] = y
				// * 2 : {x !op y} ; on recoit $v[0] = 'NOT', $v[1] = array() // array du type {x op y}
				// * 3 : {x op y} ; on recoit $v[0] = 'op', $v[1] = x, $v[2] = y

				// 1 : forcement traite par un critere, on passe
				if (!$v or count($v) == 1) {
					continue;
				}
				if (count($v) == 2 and is_array($v[1])) {
					$this->ajouter_filtre($v[1][1], $v[1][0], $v[1][2], 'NOT');
				}
				if (count($v) == 3) {
					$this->ajouter_filtre($v[1], $v[0], $v[2]);
				}
			}
		}

		// critere {2,7}
		if (isset($this->command['limit']) and $this->command['limit']) {
			$limit = explode(',', $this->command['limit']);
			$this->offset = $limit[0];
			$this->limit = $limit[1];
		}

		// Creer la fonction de filtrage sur $this
		if ($this->filtre) {
			$filtres = 'return (' . join(') AND (', $this->filtre) . ');';
			$this->func_filtre = function () use ($filtres) {
				return eval($filtres);
			};
		}
	}


	protected function ajouter_filtre($cle, $op, $valeur, $not = false) {
		if (method_exists($this->iter, 'exception_des_criteres')) {
			if (in_array($cle, $this->iter->exception_des_criteres())) {
				return;
			}
		}
		// TODO: analyser le filtre pour refuser ce qu'on ne sait pas traiter ?
		# mais c'est normalement deja opere par calculer_critere_infixe()
		# qui regarde la description 'desc' (en casse reelle d'ailleurs : {isDir=1}
		# ne sera pas vu si l'on a defini desc['field']['isdir'] pour que #ISDIR soit present.
		# il faudrait peut etre definir les 2 champs isDir et isdir... a reflechir...

		# if (!in_array($cle, array('cle', 'valeur')))
		#	return;

		$a = '$this->get_select(\'' . $cle . '\')';

		$filtre = '';

		if ($op == 'REGEXP') {
			$filtre = 'filtrer("match", ' . $a . ', ' . str_replace('\"', '"', $valeur) . ')';
			$op = '';
		} else {
			if ($op == 'LIKE') {
				$valeur = str_replace(array('\"', '_', '%'), array('"', '.', '.*'), preg_quote($valeur));
				$filtre = 'filtrer("match", ' . $a . ', ' . $valeur . ')';
				$op = '';
			} else {
				if ($op == '=') {
					$op = '==';
				} else {
					if ($op == 'IN') {
						$filtre = 'in_array(' . $a . ', array' . $valeur . ')';
						$op = '';
					} else {
						if (!in_array($op, array('<', '<=', '>', '>='))) {
							spip_log('operateur non reconnu ' . $op); // [todo] mettre une erreur de squelette
							$op = '';
						}
					}
				}
			}
		}

		if ($op) {
			$filtre = $a . $op . str_replace('\"', '"', $valeur);
		}

		if ($not) {
			$filtre = "!($filtre)";
		}

		if ($filtre) {
			$this->filtre[] = $filtre;
		}
	}


	public function next() {
		$this->pos++;
		parent::next();
	}

	/**
	 * revient au depart
	 *
	 * @return void
	 */
	public function rewind() {
		$this->pos = 0;
		$this->fetched = 0;
		parent::rewind();
	}


	# Extension SPIP des iterateurs PHP
	/**
	 * type de l'iterateur
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * parametres de l'iterateur
	 *
	 * @var array
	 */
	protected $command;

	/**
	 * infos de compilateur
	 *
	 * @var array
	 */
	protected $info;

	/**
	 * position courante de l'iterateur
	 *
	 * @var int
	 */
	protected $pos = null;

	/**
	 * nombre total resultats dans l'iterateur
	 *
	 * @var int
	 */
	protected $total = null;

	/**
	 * nombre maximal de recherche pour $total
	 * si l'iterateur n'implemente pas de fonction specifique
	 */
	protected $max = 100000;


	/**
	 * Liste des champs a inserer dans les $row
	 * retournes par ->fetch()
	 */
	protected $select = array();


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
	public function seek($n = 0, $continue = null) {
		if ($this->func_filtre or !method_exists($this->iter, 'seek') or !$this->iter->seek($n)) {
			$this->seek_loop($n);
		}
		$this->pos = $n;
		$this->fetched = $n;

		return true;
	}

	/*
	 * aller a la position $n en parcourant
	 * un par un tous les elements
	 */
	private function seek_loop($n) {
		if ($this->pos > $n) {
			$this->rewind();
		}

		while ($this->pos < $n and $this->valid()) {
			$this->next();
		}

		return true;
	}

	/**
	 * Avancer de $saut pas
	 *
	 * @param  $saut
	 * @param  $max
	 * @return int
	 */
	public function skip($saut, $max = null) {
		// pas de saut en arriere autorise pour cette fonction
		if (($saut = intval($saut)) <= 0) {
			return $this->pos;
		}
		$seek = $this->pos + $saut;
		// si le saut fait depasser le maxi, on libere la resource
		// et on sort
		if (is_null($max)) {
			$max = $this->count();
		}

		if ($seek >= $max or $seek >= $this->count()) {
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
	 * Appliquer aussi le critere {offset,limit}
	 *
	 * @return array|bool
	 */
	public function fetch() {
		if (method_exists($this->iter, 'fetch')) {
			return $this->iter->fetch();
		} else {
			while ($this->valid()
				and (
					!$this->accept()
					or (isset($this->offset) and $this->fetched++ < $this->offset)
				)) {
				$this->next();
			}

			if (!$this->valid()) {
				return false;
			}

			if (isset($this->limit)
				and $this->fetched > $this->offset + $this->limit
			) {
				return false;
			}

			$r = array();
			foreach ($this->select as $nom) {
				$r[$nom] = $this->get_select($nom);
			}
			$this->next();

			return $r;
		}
	}

	// retourner la cle pour #CLE
	public function cle() {
		return $this->key();
	}

	// retourner la valeur pour #VALEUR
	public function valeur() {
		return $this->current();
	}

	/**
	 * Accepte-t-on l'entree courante lue ?
	 * On execute les filtres pour le savoir.
	 **/
	public function accept() {
		if ($f = $this->func_filtre) {
			return $f();
		}

		return true;
	}

	/**
	 * liberer la ressource
	 *
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
	 *
	 * @return int
	 */
	public function count() {
		if (is_null($this->total)) {
			if (method_exists($this->iter, 'count')
				and !$this->func_filtre
			) {
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
