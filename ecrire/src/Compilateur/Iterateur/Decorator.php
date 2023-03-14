<?php

namespace Spip\Compilateur\Iterateur;

use Exception;
use FilterIterator;
use Iterator;

class Decorator extends FilterIterator
{
	/**
	 * Conditions de filtrage
	 * ie criteres de selection.
	 *
	 * @var array
	 */
	protected $filtre = [];

	/**
	 * Fonction de filtrage compilee a partir des criteres de filtre.
	 *
	 * @var string
	 */
	protected $func_filtre;

	/**
	 * Critere {offset, limit}.
	 *
	 * @var int
	 * @var int
	 */
	protected $offset;
	protected $limit;

	/**
	 * nombre d'elements recuperes depuis la position 0,
	 * en tenant compte des filtres.
	 *
	 * @var int
	 */
	protected $fetched = 0;

	/**
	 * Y a t'il une erreur ?
	 *
	 * @var bool
	 */
	protected $err = false;

	// Extension SPIP des iterateurs PHP
	/**
	 * type de l'iterateur.
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * position courante de l'iterateur.
	 *
	 * @var int
	 */
	protected $pos = 0;

	/**
	 * nombre total resultats dans l'iterateur.
	 *
	 * @var int
	 */
	protected $total;

	/**
	 * nombre maximal de recherche pour $total
	 * si l'iterateur n'implemente pas de fonction specifique.
	 */
	protected $max = 100000;

	/**
	 * Liste des champs a inserer dans les $row
	 * retournes par ->fetch().
	 */
	protected $select = [];
	private Iterator $iter;

	public function __construct(
		Iterator $iter,
		/** Parametres de l'iterateur */
		protected array $command,
		/** Infos du compilateur */
		protected array $info
	) {
		parent::__construct($iter);
		parent::rewind(); // remettre a la premiere position (bug? connu de FilterIterator)

		// recuperer l'iterateur transmis
		$this->iter = $this->getInnerIterator();

		// chercher la liste des champs a retourner par
		// fetch si l'objet ne les calcule pas tout seul
		if (!method_exists($this->iter, 'fetch')) {
			$this->calculer_select();
			$this->calculer_filtres();
		}

		// emptyIterator critere {si} faux n'a pas d'erreur !
		if (property_exists($this->iter, 'err') && $this->iter->err !== null) {
			$this->err = $this->iter->err;
		}

		// pas d'init a priori, le calcul ne sera fait qu'en cas de besoin (provoque une double requete souvent inutile en sqlite)
		//$this->total = $this->count();
	}

	/**
	 * Drapeau a activer en cas d'echec
	 * (select SQL errone, non chargement des DATA, etc).
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

	// recuperer la valeur d'une balise #X
	// en fonction des methodes
	// et proprietes disponibles
	public function get_select($nom) {
		if (is_object($this->iter) && method_exists($this->iter, $nom)) {
			try {
				return $this->iter->{$nom}();
			} catch (Exception) {
				// #GETCHILDREN sur un fichier de DirectoryIterator ...
				spip_log("Methode {$nom} en echec sur " . $this->iter::class);
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
		if (
			in_array($nom, ['cle', 'valeur'])
			&& method_exists($this, $nom)
		) {
			return $this->{$nom}();
		}

		// Par defaut chercher en xpath dans la valeur()
		return table_valeur($this->valeur(), $nom, null);
	}

	public function next(): void {
		++$this->pos;
		parent::next();
	}

	/**
	 * revient au depart.
	 */
	public function rewind(): void {
		$this->pos = 0;
		$this->fetched = 0;
		parent::rewind();
	}

	/**
	 * aller a la position absolue n,
	 * comptee depuis le debut.
	 *
	 * @param int    $n
	 *                         absolute pos
	 * @param string $continue
	 *                         param for sql_ api
	 *
	 * @return bool
	 *              success or fail if not implemented
	 */
	public function seek($n = 0, $continue = null) {
		if ($this->func_filtre || !method_exists($this->iter, 'seek') || !$this->iter->seek($n)) {
			$this->seek_loop($n);
		}
		$this->pos = $n;
		$this->fetched = $n;

		return true;
	}

	/**
	 * Avancer de $saut pas.
	 *
	 * @param $saut
	 * @param $max
	 *
	 * @return int
	 */
	public function skip($saut, $max = null) {
		// pas de saut en arriere autorise pour cette fonction
		if (($saut = (int) $saut) <= 0) {
			return $this->pos;
		}
		$seek = $this->pos + $saut;
		// si le saut fait depasser le maxi, on libere la resource
		// et on sort
		if (is_null($max)) {
			$max = $this->count();
		}

		if ($seek >= $max || $seek >= $this->count()) {
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
	 * Appliquer aussi le critere {offset,limit}.
	 *
	 * @return array|bool
	 */
	public function fetch() {
		if (method_exists($this->iter, 'fetch')) {
			return $this->iter->fetch();
		}
		while (
				$this->valid()
				&& (!$this->accept() || $this->offset !== null && $this->fetched++ < $this->offset)
		) {
			$this->next();
		}

		if (!$this->valid()) {
			return false;
		}

		if (
				$this->limit !== null
				&& $this->fetched > $this->offset + $this->limit
		) {
			return false;
		}

		$r = [];
		foreach ($this->select as $nom) {
			$r[$nom] = $this->get_select($nom);
		}
		$this->next();

		return $r;
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
	 */
	public function accept(): bool {
		if ($f = $this->func_filtre) {
			return $f();
		}

		return true;
	}

	/**
	 * liberer la ressource.
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
	 * pour #TOTAL_BOUCLE.
	 *
	 * @return int
	 */
	public function count() {
		if (is_null($this->total)) {
			if (
				method_exists($this->iter, 'count')
				&& !$this->func_filtre
			) {
				return $this->total = $this->iter->count();
			}
			// compter les lignes et rembobiner
			$total = 0;
			$pos = $this->pos; // sauver la position
			$this->rewind();
			while ($this->fetch() && $total < $this->max) {
				++$total;
			}
			$this->seek($pos);
			$this->total = $total;
		}

		return $this->total;
	}

	/**
	 * Assembler le tableau de filtres traduits depuis les conditions SQL
	 * les filtres vides ou null sont ignores.
	 *
	 * @param $filtres
	 * @param string $operateur
	 *
	 * @return null|string
	 */
	protected function assembler_filtres($filtres, $operateur = 'AND') {
		$filtres_string = [];
		foreach ($filtres as $k => $v) {
			// si c'est un tableau de OR/AND + 2 sous-filtres, on recurse pour transformer en chaine
			if (is_array($v) && in_array(reset($v), ['OR', 'AND'])) {
				$op = array_shift($v);
				$v = $this->assembler_filtres($v, $op);
			}
			if (is_null($v) || !is_string($v) || empty($v)) {
				continue;
			}
			$filtres_string[] = $v;
		}

		if ($filtres_string === []) {
			return null;
		}

		return '(' . implode(") {$operateur} (", $filtres_string) . ')';
	}

	/**
	 * Traduire un element du tableau where SQL en un filtre.
	 *
	 * @param $v
	 *
	 * @return null|array|string
	 */
	protected function traduire_condition_sql_en_filtre($v) {
		if (is_array($v)) {
			if ((count($v) >= 2) && ('REGEXP' == $v[0]) && ("'.*'" == $v[2])) {
				return 'true';
			}
			if ((count($v) >= 2) && ('LIKE' == $v[0]) && ("'%'" == $v[2])) {
				return 'true';
			}
			$op = $v[0] ?: $v;
		} else {
			$op = $v;
		}
		if (!$op || 1 == $op || '0=0' == $op) {
			return 'true';
		}
		if ('0=1' === $op) {
			return 'false';
		}
		// traiter {cle IN a,b} ou {valeur !IN a,b}
		if (preg_match(',^\(([\w/]+)(\s+NOT)?\s+IN\s+(\(.*\))\)$,', $op, $regs)) {
			return $this->composer_filtre($regs[1], 'IN', $regs[3], $regs[2]);
		}

		// 3 possibilites : count($v) =
		// * 1 : {x y} ; on recoit $v[0] = y
		// * 2 : {x !op y} ; on recoit $v[0] = 'NOT', $v[1] = array() // array du type {x op y}
		// * 3 : {x op y} ; on recoit $v[0] = 'op', $v[1] = x, $v[2] = y

		// 1 : forcement traite par un critere, on passe
		if (!$v || !is_array($v) || 1 == count($v)) {
			return null; // sera ignore
		}
		if (2 == count($v) && is_array($v[1])) {
			return $this->composer_filtre($v[1][1], $v[1][0], $v[1][2], 'NOT');
		}
		if (3 == count($v)) {
			// traiter le OR/AND suivi de 2 valeurs
			if (in_array($op, ['OR', 'AND'])) {
				array_shift($v);
				foreach (array_keys($v) as $k) {
					$v[$k] = $this->traduire_condition_sql_en_filtre($v[$k]);
					if (null === $v[$k]) {
						unset($v[$k]);
					} elseif ('true' === $v[$k]) {
						if ('OR' === $op) {
							return 'true';
						}
						if ('AND' === $op) {
							unset($v[$k]);
						}
					} elseif ('false' === $v[$k]) {
						if ('OR' === $op) {
							unset($v[$k]);
						}
						if ('AND' === $op) {
							return 'false';
						}
					}
				}
				if ($v === []) {
					return null;
				}
				if (1 === count($v)) {
					return reset($v);
				}
				array_unshift($v, $op);

				return $v;
			}

			return $this->composer_filtre($v[1], $v[0], $v[2]);
		}

		return null;  // sera ignore
	}

	/**
	 * Calculer un filtre sur un champ du tableau.
	 *
	 * @param $cle
	 * @param $op
	 * @param $valeur
	 * @param false $not
	 *
	 * @return null|string
	 */
	protected function composer_filtre($cle, $op, $valeur, $not = false) {
		if (
			method_exists($this->iter, 'exception_des_criteres')
			&& in_array($cle, $this->iter->exception_des_criteres())
		) {
			return null;
		}
		// TODO: analyser le filtre pour refuser ce qu'on ne sait pas traiter ?
		// mais c'est normalement deja opere par calculer_critere_infixe()
		// qui regarde la description 'desc' (en casse reelle d'ailleurs : {isDir=1}
		// ne sera pas vu si l'on a defini desc['field']['isdir'] pour que #ISDIR soit present.
		// il faudrait peut etre definir les 2 champs isDir et isdir... a reflechir...

		// if (!in_array($cle, array('cle', 'valeur')))
		//	return;

		$a = '$this->get_select(\'' . $cle . '\')';

		$filtre = '';

		if ('REGEXP' == $op) {
			$filtre = 'filtrer("match", ' . $a . ', ' . str_replace('\"', '"', $valeur) . ')';
			$op = '';
		} else {
			if ('LIKE' == $op) {
				$valeur = str_replace(['\"', '_', '%'], ['"', '.', '.*'], preg_quote($valeur));
				$filtre = 'filtrer("match", ' . $a . ', ' . $valeur . ')';
				$op = '';
			} else {
				if ('=' == $op) {
					$op = '==';
				} else {
					if ('IN' == $op) {
						$filtre = 'in_array(' . $a . ', array' . $valeur . ')';
						$op = '';
					} else {
						if (!in_array($op, ['<', '<=', '>', '>='])) {
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
			$filtre = "!({$filtre})";
		}

		return $filtre;
	}

	// calcule les elements a retournes par fetch()
	// enleve les elements inutiles du select()
	//
	private function calculer_select() {
		if ($select = &$this->command['select']) {
			foreach ($select as $s) {
				// /!\ $s = '.nom'
				if ('.' == $s[0]) {
					$s = substr($s, 1);
				}
				$this->select[] = $s;
			}
		}
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
				$this->filtre[] = $this->traduire_condition_sql_en_filtre($v);
			}
		}

		// critere {2,7}
		if (isset($this->command['limit']) && $this->command['limit']) {
			$limit = explode(',', $this->command['limit']);
			$this->offset = $limit[0];
			$this->limit = $limit[1];
		}

		// Creer la fonction de filtrage sur $this
		if ($this->filtre) {
			if ($filtres = $this->assembler_filtres($this->filtre)) {
				$filtres = 'return ' . $filtres . ';';
				$this->func_filtre = fn () => eval($filtres);
			} else {
				$this->func_filtre = null;
			}
		}
	}

	/*
	 * aller a la position $n en parcourant
	 * un par un tous les elements
	 */
	private function seek_loop($n) {
		if ($this->pos > $n) {
			$this->rewind();
		}

		while ($this->pos < $n && $this->valid()) {
			$this->next();
		}

		return true;
	}
}
