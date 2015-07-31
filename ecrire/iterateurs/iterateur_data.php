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
 * IterateurDATA pour iterer sur des donnees
 */
class IterateurDATA extends IterateurSPIP {
	/**
	 * tableau de donnees
	 * @var array
	 */
	protected $tableau = array();

	/**
	 * Conditions de filtrage
	 * ie criteres de selection
	 * @var array
	 */
	protected $filtre = array();


	/**
	 * Cle courante
	 * @var null
	 */
	protected $cle = null;

	/**
	 * Valeur courante
	 * @var null
	 */
	protected $valeur = null;

	/**
	 * Constructeur
	 *
	 * @param  $command
	 * @param array $info
	 */
	public function __construct($command, $info=array()) {
		$this->type='DATA';
		$this->command = $command;
		$this->info = $info;

		$this->select($command);
	}

	/**
	 * Revenir au depart
	 * @return void
	 */
	public function rewind() {
		parent::rewind();
		reset($this->tableau);
		list($this->cle, $this->valeur) = each($this->tableau);
	}

	protected function select($command) {
		// les commandes connues pour l'iterateur POUR
		// sont : tableau=#ARRAY ; cle=...; valeur=...
		// source URL
		if (isset($this->command['source'])) {
			if (isset($this->command['sourcemode'])
			AND in_array($this->command['sourcemode'],
				array('table', 'array', 'tableau'))
			) {
				if (is_array($a = $this->command['source'])
				OR is_array($a = unserialize($this->command['source']))) {
					$this->tableau = $a;
					$this->ok = true;
				}
			}
			else if (preg_match(',^http://,', $this->command['source'])) {
				include_spip('inc/distant');
				$u = recuperer_page($this->command['source']);
			} else if (@is_readable($this->command['source']))
				$u = spip_file_get_contents($this->command['source']);
			else
				$u = $this->command['source'];

			// tout ce bloc devrait marcher par charger_fonction('xxx_to_array')
			// si c'est du RSS
			if (isset($this->command['sourcemode'])) {
				if ($g = charger_fonction($this->command['sourcemode'] . '_to_array', 'inc', true)) {
					if (is_array($a = $g($u))) {
						$this->tableau = $a;
						$this->ok = true;
					}
				}
				else
				switch ($this->command['sourcemode']) {
					case 'rss':
					case 'atom':
						include_spip('inc/syndic');
						if (is_array($rss = analyser_backend($u))) {
							$this->tableau = $rss;
							$this->ok = true;
						}
						break;
					case 'json':
						if (is_array($json = json_decode($u))
						OR is_object($json)) {
							$this->tableau = (array) $json;
							$this->ok = true;
						}
						break;
					case 'yaml':
						include_spip('inc/yaml');
						if (is_array($yaml = yaml_decode($u))) {
							$this->tableau = $yaml;
							$this->ok = true;
						}
						break;
					case 'csv':
						# decodage csv a passer en inc/csv
						# cf. http://www.php.net/manual/en/function.str-getcsv.php#100579 et suiv.
						if (function_exists('str_getcsv')) # PHP 5.3.0
							$this->tableau = str_getcsv($u);
						else
						foreach(preg_split('/\r?\n/',$u) as $ligne)
							$this->tableau[] = explode(',', $ligne);
						$this->ok = true;
				}
			}
		}

		if (is_array($this->command['where'])) {
			$op = '';
			foreach ($this->command['where'] as $k => $com) {
				switch($com[1]) {
					case 'tableau':
						if ($com[0] !== '=') {
							// erreur
						}
						# sql_quote a l'envers : pas propre...
						# c'est pour la compat ascendante avec le critere
						# {tableau=#ENV...} de la boucle POUR de SPIP-Bonux-2
						$x = null;
						eval ('$x = '.str_replace('\"', '"', $com[2]).';');
						if (is_array($x) OR is_array($x = @unserialize($x))) {
							$this->tableau = $x;
							$this->ok = true;
						}
						else
							{
								// erreur
							}
						break;
					case 'cle':
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

		// Appliquer les filtres sur (cle,valeur)
		if ($this->filtre) {
			$func_filtre = create_function('$cle,$valeur', $b = 'return ('.join(') AND (', $this->filtre).');');
			#var_dump($b);
			foreach($this->tableau as $cle=>$valeur) {
				if (!$func_filtre($cle,$valeur))
					unset($this->tableau[$cle]);
			}
		}

		// critere {2,7}
		if ($this->command['limit']) {
			$limit = explode(',',$this->command['limit']);
			$this->tableau = array_slice($this->tableau,
				$limit[0],$limit[1],true);
		}

		$this->rewind();
		#var_dump($this->tableau);
	}


	/**
	 * L'iterateur est-il encore valide ?
	 * @return bool
	 */
	public function valid(){
		return !is_null($this->cle);
	}

	/**
	 * Retourner la valeur
	 * @return null
	 */
	public function current() {
		return $this->valeur;
	}

	/**
	 * Retourner la cle
	 * @return null
	 */
	public function key() {
		return $this->cle;
	}

	/**
	 * Passer a la valeur suivante
	 * @return void
	 */
	public function next(){
		parent::next();
		if ($this->valid())
			list($this->cle, $this->valeur) = each($this->tableau);
	}

	/**
	 * Compter le nombre total de resultats
	 * @return int
	 */
	public function total() {
		if (is_null($this->total))
			$this->total = count($this->tableau);
	  return $this->total;
	}
}


function inc_file_to_array_dist($u) {
	return preg_split('/\r?\n/', $u);
}
function inc_plugins_to_array_dist($u) {
	include_spip('inc/plugin');
	return liste_chemin_plugin_actifs();
}

?>
