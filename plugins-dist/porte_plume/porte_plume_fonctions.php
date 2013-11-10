<?php
/**
 * Fonctions utiles pour le Porte Plume
 * 
 * @plugin Porte Plume pour SPIP
 * @license GPL
 * @package SPIP\PortePlume\BarreOutils
 */

if (!defined("_ECRIRE_INC_VERSION")) return;

/**
 * Objet contenant les différents paramètres definissant une barre d'outils
 * Markitup et permettant d'agir dessus
 *
 * @example
 *     $barre = new Barre_Outil($description);
 * 
 * @package SPIP\PortePlume\BarreOutils
 */
class Barre_outils{
	/**
	 * Identifiant HTML de la barre
	 * @todo À supprimer car non utilisé !
	 * @var string */
	public $id = "";

	/**
	 * Nom de la barre d'outil
	 * @var string */
	public $nameSpace = "";

	/**
	 * Langue
	 * @todo À supprimer car non utilisé !
	 * @var string */
	public $lang = "";

	/**
	 * Option de markitup : rafraîchir la prévisu ?
	 * @todo À supprimer car non utilisé !
	 * @var bool */
	public $previewAutoRefresh = false;

	/**
	 * Option de markitup : nom de la fonction de prévisu
	 * @todo À supprimer car on le redéfini dans l'appel javascript !
	 * @var bool */
	public $previewParserPath = "";

	/**
	 * Option de markitup : que faire sur l'appuie de Entrée ?
	 * @var array */
	public $onEnter = array();
	

	/**
	 * Option de markitup : que faire sur l'appuie de Shift+Entrée ?
	 * @example array('keepDefault'=>false, 'replaceWith'=>"\n_ ")
	 * @var array */
	public $onShiftEnter = array();

	/**
	 * Option de markitup : que faire sur l'appuie de Control+Entrée ?
	 * @var array */
	public $onCtrlEnter = array();

	/**
	 * Option de markitup : que faire sur l'appuie d'une tabulation ?
	 * @var array */
	public $onTab = array();

	/**
	 * Option de markitup : Code JS à exécuter avant une insertion
	 * @var string */
	public $beforeInsert = "";

	/**
	 * Option de markitup : Code JS à exécuter après une insertion
	 * @var string */
	public $afterInsert = "";

	/**
	 * Description des outils/boutons et leurs sous boutons éventuels
	 * @var array */
	public $markupSet = array();

	/**
	 * Fonctions JS supplémentaires à écrire après la déclaration JSON
	 * des outils. Ces fonctions peuvent servir aux boutons.
	 * @var string */
	public $functions = "";

	/**
	 * Liste des paramètres valides pour une description d'outils (markupSet)
	 * @var array */
	private $_liste_params_autorises = array(
	
		'replaceWith',
		'openWith',
		'closeWith',
		'openBlockWith', // sur multiline, avant les lignes selectionnees
		'closeBlockWith', // sur multiline, apres les lignes selectionnees
		'placeHolder', // remplace par ce texte lorsqu'il n'y a pas de selection
		
		'beforeInsert', // avant l'insertion
		'afterInsert', // apres l'insertion
		'beforeMultiInsert',
		'afterMultiInsert',

		'dropMenu', // appelle un sous menu
		
		'name', // nom affiche au survol
		'key', // raccourcis clavier
		'className', // classe css utilisee
		'lang', // langues dont le bouton doit apparaitre - array
		'lang_not', // langues dont le bouton ne doit pas apparaitre - array
		'selectionType', // '','word','line' : type de selection (normale, aux mots les plus proches, a la ligne la plus proche) 
		'multiline', // open/close sur chaque ligne (mais replace est applique sur l'ensemble de la selection)
		'forceMultiline', // pour faire comme si on faisait systematiquement un control+shift (et replace est applique sur chaque ligne de la selection)
		
		'separator',
		
		'call',
		'keepDefault',
		
		// cacher ou afficher facilement des boutons
		'display',
		// donner un identifiant unique au bouton (pour le php)
		'id',
	);

	/**
	 * Constructeur
	 * 
	 * Initialise la barre avec les paramètres transmis
	 * en n'adressant que les paramètres effectivement valides
	 *
	 * @api
	 * @param array $params Paramètres de la barre d'outil
	 * @return void
	 */
	function Barre_outils($params=array()){
		foreach ($params as $p=>$v) {
			if (isset($this->$p)) {
				// si tableau, on verifie les entrees
				if (is_array($v)) {
					$v = $this->verif_params($p,$v);
				}
				$this->$p = $v;
			}
		}
	}


	/**
	 * Vérifie que les paramètres d'une clé existent
	 * et retourne un tableau des paramètres valides
	 * 
	 * @param string $nom
	 *     Clé à vérifier (ex: 'markupSet')
	 * @param array $params
	 *     Paramètres de cette clé (description des boutons ou sous boutons)
	 * @return array
	 *     Paramètres, soustrait de ceux qui ne sont pas valides
	 */
	function verif_params($nom, $params=array()) {
		// si markupset, on boucle sur les items
		if (stripos($nom, 'markupSet')!==false) {
			foreach ($params as $i=>$v) {
				$params[$i] = $this->verif_params($i, $v);
			}
		} 
		// sinon on teste la validite
		else {
			foreach ($params as $p=>$v) {
				if (!in_array($p, $this->_liste_params_autorises)) {
					unset($params[$p]);
				}
			}
		}
		return $params;
	}

	/**
	 * Permet d'affecter des paramètres à un élément de la barre
	 * 
	 * La fonction retourne les paramètres, de sorte qu'on peut s'en servir
	 * pour simplement récupérer ceux-ci.
	 * 
	 * Il est possible d'affecter des paramètres avant/après l'élément trouvé
	 * en definisant une valeur différente pour le $lieu : 'dedans','avant','apres'
	 * par defaut 'dedans' (modifie l'élément trouvé).
	 * 
	 * Lorsqu'on demande d'insérer avant ou après, la fonction retourne
	 * les paramètres inserés
	 * 
	 * @param array $tableau
	 *     Tableau ou chercher les elements (sert pour la recursion)
	 * @param string $identifiant
	 *     Identifiant du bouton a afficher
	 * @param array $params
	 *     Paramètres à affecter à la trouvaille (ou avant ou après).
	 *     Peut être un tableau clé/valeur ou un tableau de tableaux
	 *     clé/valeur (sauf pour $lieu = dedans)
	 * @param string $lieu
	 *     Lieu d'affectation des paramètres (dedans, avant, apres)
	 * @param bool $plusieurs
	 *     Définit si $params est une forme simple (tableau cle/valeur)
	 *     ou comporte plusieurs boutons (tableau de tableaux cle/valeur).
	 * @return array|bool
	 *     Paramètres de l'élément modifié ou paramètres ajoutés
	 *     False si l'identifiant cherché n'est pas trouvé
	 */
	function affecter(&$tableau, $identifiant, $params=array(), $lieu='dedans', $plusieurs=false){
		static $cle_de_recherche = 'id'; // ou className ?
		
		if ($tableau === null) // utile ?
			$tableau = &$this->markupSet;

		if (!in_array($lieu, array('dedans','avant','apres'))) 
			$lieu = 'dedans';

		// present en premiere ligne ?
		$trouve = false;
		foreach ($tableau as $i=>$v){
			if (isset($v[$cle_de_recherche]) and ($v[$cle_de_recherche] == $identifiant)) {
				$trouve = $i;
				break;
			}
		}
		// si trouve, affectations
		if (($trouve !== false)) {
			if ($params) {
				// verifier que les insertions sont correctes
				$les_params = ($plusieurs ? $params : array($params));
				foreach ($les_params as $i=>$un_params) {
					$les_params[$i] = $this->verif_params($identifiant, $un_params);
				}
	
				// dedans on merge ($params uniquement tableau cle/valeur)
				if ($lieu == 'dedans' && !$plusieurs) {
					return $tableau[$trouve] = array_merge($tableau[$trouve], $les_params[0]);
				}
				// avant ou apres, on insere ($params peut etre tableau cle/valeur ou tableau de tableaux cle/valeur)
				elseif ($lieu == 'avant') {
					array_splice($tableau, $trouve, 0, $les_params);
					return $params;
				}
				elseif ($lieu == 'apres') {
					array_splice($tableau, $trouve+1, 0, $les_params);
					return $params;
				}
			}
			return $tableau[$trouve];
		}

		// recursivons sinon !
		foreach ($tableau as $i=>$v){
			if (is_array($v)) {
				foreach ($v as $m=>$n) {
					if (is_array($n) AND ($r = $this->affecter($tableau[$i][$m], $identifiant, $params, $lieu, $plusieurs))) 
						return $r;
				}
			}
		}
		return false;
	}

	
	/**
	 * Permet d'affecter des paramètres à tous les éléments de la barre
	 * ou à une liste d'identifiants d'éléments indiqués.
	 * 
	 * @param array $tableau
	 *     Tableau où chercher les éléments
	 * @param array $params
	 *     Paramètres à affecter aux éléments
	 * @param array $ids
	 *     Tableau d'identifiants particuliers à qui on affecte les paramètres.
	 *     Si vide, tous les identifiants seront modifiés
	 * @return bool
	 *     false si aucun paramètre à affecter, true sinon.
	 */
	function affecter_a_tous(&$tableau, $params=array(), $ids=array()){
		if (!$params)
			return false;
		
		if ($tableau === null)
			$tableau = &$this->markupSet;

		$params = $this->verif_params('divers', $params);

		// merge de premiere ligne
		foreach ($tableau as $i=>$v){
			if (!$ids OR in_array($v['id'], $ids)) {
				$tableau[$i] = array_merge($tableau[$i], $params);
			}
			// recursion si sous-menu
			if (isset($tableau[$i]['dropMenu'])) {
				$this->affecter_a_tous($tableau[$i]['dropMenu'], $params, $ids);
			}
		}
		return true;
	}
	
	
	/**
	 * Affecte les valeurs des paramètres indiqués au bouton demandé
	 * et retourne l'ensemble des paramètres du bouton (sinon false)
	 *
	 * @api
	 * @param string|array $identifiant
	 *     Identifiant du ou des boutons.
	 * @param array $params
	 *     Paramètres de l'ajout (tableau paramètre=>valeur)
	 * @return bool|array
	 *     false si l'identifiant n'a pas été trouvé
	 *     true si plusieurs identifiants,
	 *     array sinon : description de l'identifiant cherché.
	 */
	function set($identifiant, $params=array()) {
		// prudence tout de meme a pas tout modifier involontairement (si array)
		if (!$identifiant) return false;
	
		if (is_string($identifiant)) {
			return $this->affecter($this->markupSet, $identifiant, $params);
		}
		elseif (is_array($identifiant)) {
			return $this->affecter_a_tous($this->markupSet, $params, $identifiant);
		}
		return false;
	}
	
	/**
	 * Retourne les parametres du bouton demande
	 *
	 * @api
	 * @param string|array $identifiant
	 *     Identifiant du ou des boutons.
	 * @return bool|array
	 *     false si l'identifiant n'est pas trouvé
	 *     array sinon : Description de l'identifiant cherché.
	 */
	function get($identifiant) {
		if ($a = $this->affecter($this->markupSet, $identifiant)) {
			return $a;
		}
		return false;
	}
	
	
	/**
	 * Affiche le ou les boutons demandés
	 *
	 * @api
	 * @param string|array $identifiant
	 *     Identifiant du ou des boutons
	 * @return bool|array
	 *     false si l'identifiant n'a pas été trouvé
	 *     true si plusieurs identifiants,
	 *     array sinon : description de l'identifiant cherché.
	 */
	function afficher($identifiant){
		return $this->set($identifiant,array('display'=>true));
	}
	
	
	/**
	 * Cache le ou les boutons demandés
	 *
	 * @api
	 * @param string|array $identifiant
	 *     Identifiant du ou des boutons
	 * @return bool|array
	 *     false si l'identifiant n'a pas été trouvé
	 *     true si plusieurs identifiants,
	 *     array sinon : description de l'identifiant cherché.
	 */
	function cacher($identifiant){
		return $this->set($identifiant, array('display'=>false));
	}
	
	
	/**
	 * Affiche tous les boutons
	 *
	 * @api
	 * @return bool
	 *     false si aucun paramètre à affecter, true sinon.
	 */
	function afficherTout(){
		return $this->affecter_a_tous($this->markupSet, array('display'=>true));
	}
	
	/**
	 * Cache tous les boutons
	 *
	 * @api
	 * @return bool
	 *     false si aucun paramètre à affecter, true sinon.
	 */
	function cacherTout(){
		return $this->affecter_a_tous($this->markupSet, array('display'=>false));
	}
	

	/**
	 * Ajoute un bouton ou quelque chose, avant un autre déjà présent
	 *
	 * @api
	 * @param string $identifiant
	 *     Identifiant du bouton où l'on doit se situer
	 * @param array $params
	 *     Paramètres de l'ajout.
	 *     Description d'un bouton (tableau clé/valeurs).
	 * @return array|bool
	 *     Paramètres ajoutés avant
	 *     False si l'identifiant cherché n'est pas trouvé
	 */
	function ajouterAvant($identifiant, $params){
		return $this->affecter($this->markupSet, $identifiant, $params, 'avant');
	}

	/**
	 * Ajoute plusieurs boutons, avant un autre déjà présent
	 *
	 * @api
	 * @param string $identifiant
	 *     Identifiant du bouton où l'on doit se situer
	 * @param array $tableau_params
	 *     Paramètres de l'ajout.
	 *     Description de plusieurs boutons (tableau de tableaux clé/valeurs).
	 * @return array|bool
	 *     Paramètres ajoutés avant
	 *     False si l'identifiant cherché n'est pas trouvé
	 */
	function ajouterPlusieursAvant($identifiant, $tableau_params){
		return $this->affecter($this->markupSet, $identifiant, $tableau_params, 'avant', true);
	}

	/**
	 * Ajoute un bouton ou quelque chose, après un autre déjà présent
	 *
	 * @api
	 * @param string $identifiant
	 *     Identifiant du bouton où l'on doit se situer
	 * @param array $params
	 *     Paramètres de l'ajout.
	 *     Description d'un bouton (tableau clé/valeurs).
	 * @return array|bool
	 *     Paramètres ajoutés après
	 *     False si l'identifiant cherché n'est pas trouvé
	 */
	function ajouterApres($identifiant, $params){
		return $this->affecter($this->markupSet, $identifiant, $params, 'apres');
	}

	/**
	 * Ajoute plusieurs boutons, après un autre déjà présent
	 *
	 * @api
	 * @param string $identifiant
	 *     Identifiant du bouton où l'on doit se situer
	 * @param array $tableau_params
	 *     Paramètres de l'ajout.
	 *     Description de plusieurs boutons (tableau de tableaux clé/valeurs).
	 * @return array|bool
	 *     Paramètres ajoutés après
	 *     False si l'identifiant cherché n'est pas trouvé
	 */
	function ajouterPlusieursApres($identifiant, $tableau_params){
		return $this->affecter($this->markupSet, $identifiant, $tableau_params, 'apres', true);
	}
	
	/**
	 * Ajoute une fonction JS qui pourra être utilisée par les boutons
	 *
	 * @api
	 * @param string $fonction Code de la fonction JS
	 * @return void
	 */
	function ajouterFonction($fonction){
		if (false === strpos($this->functions, $fonction)){
			$this->functions .= "\n" . $fonction . "\n";
		}
	}
	
	/**
	 * Supprimer les éléments non affichés (display:false)
	 * Et les séparateurs (li vides) selon la configuration
	 * 
	 * @param array $tableau Tableau de description des outils
	 * @return void
	 */
	function enlever_elements_non_affiches(&$tableau){
		if ($tableau === null) // utile ?
			$tableau = &$this->markupSet;
		
		foreach ($tableau as $p=>$v) {

			if (isset($v['display']) AND !$v['display']) {
				unset($tableau[$p]);
				$tableau = array_values($tableau); // remettre les cles automatiques sinon json les affiche et ça plante.
			}
			// sinon, on lance une recursion sur les sous-menus
			else {
				if (isset($v['dropMenu']) and is_array($v['dropMenu'])) {
					$this->enlever_elements_non_affiches($tableau[$p]['dropMenu']);
					// si le sous-menu est vide
					// on enleve le sous menu.
					// mais pas le parent ($tableau[$p]), qui peut effectuer une action.
					if (!$tableau[$p]['dropMenu']) {
						unset($tableau[$p]['dropMenu']);
					}
				}
			}
		}
	}

	/**
	 * Enlève les séparateurs pour améliorer l'accessibilité
	 * au détriment du stylage possible de ces séparateurs.
	 *
	 * Le bouton précédent le séparateur reçoit une classe CSS 'separateur_avant'
	 * Celui apres 'separateur_apres'
	 * 
	 * @param array Tableau de description des outils 
	 * @return void
	**/
	function enlever_separateurs(&$tableau) {
		if ($tableau === null) // utile ?
			$tableau = &$this->markupSet;

		foreach ($tableau as $p=>$v) {
			if (isset($v['separator']) and $v['separator']) {
				if (isset($tableau[$p-1])) {
					$tableau[$p-1]['className'] .= " separateur_avant";
				}
				if (isset($tableau[$p+1])) {
					$tableau[$p+1]['className'] .= " separateur separateur_apres $v[id]";
				}
				unset($tableau[$p]);
				$tableau = array_values($tableau); // remettre les cles automatiques sinon json les affiche et ça plante.
			}
			// sinon, on lance une recursion sur les sous-menus
			else {
				if (isset($v['dropMenu']) and is_array($v['dropMenu'])) {
					#$this->enlever_separateurs($tableau[$p]['dropMenu']);
				}
			}
		}
	}

	/**
	 * Supprime les éléments vides (uniquement à la racine de l'objet)
	 * et uniquement si chaîne ou tableau.
	 * 
	 * Supprime les paramètres privés
	 * Supprime les paramètres inutiles a markitup/json dans les paramètres markupSet
	 * (id, display, icone)
	 */
	function enlever_parametres_inutiles() {
		foreach($this as $p=>$v){
			if (!$v) {
				if (is_array($v) or is_string($v)) {
					unset($this->$p);
				}
			} elseif ($p == 'functions') {
				unset($this->$p);
			}
		}
		foreach($this->markupSet as $p=>$v) {
			foreach ($v as $n=>$m) {
				if (in_array($n, array('id', 'display'))) {
					unset($this->markupSet[$p][$n]);
				}
			}
		}
		unset ($this->_liste_params_autorises);
	}
	
	
	/**
	 * Crée la sortie json pour le javascript des paramètres de la barre
	 * 
	 * @return string Déclaration json de la barre
	 */
	function creer_json(){
		$barre = $this;
		$type = $barre->nameSpace;
		$fonctions = $barre->functions;

		$barre->enlever_elements_non_affiches($this->markupSet);
		$barre->enlever_separateurs($this->markupSet);
		$barre->enlever_parametres_inutiles();
		
		$json = Barre_outils::json_export($barre);

		// on lance la transformation des &chose; en veritables caracteres
		// sinon markitup restitue &laquo; au lieu de « directement
		// lorsqu'on clique sur l'icone
		include_spip('inc/charsets');
		$json = unicode2charset(html2unicode($json));
		return "\n\nbarre_outils_$type = ".$json . "\n\n $fonctions";
	}

	/**
	 * Transforme une variable PHP dans un équivalent javascript (json)
	 * 
	 * Copié depuis ecrire/inc/json, mais modifié pour que les fonctions
	 * JavaScript ne soient pas encapsulées dans une chaîne (string)
	 * 
	 * @access private
	 * @param mixed the variable
	 * @return string js script | boolean false if error
	 */
	function json_export($var) {
		$asso = false;
		switch (true) {
			case is_null($var) :
				return 'null';
			case is_string($var) :
				if (strtolower(substr(ltrim($var),0,8))=='function')
					return $var;
				return '"' . addcslashes($var, "\"\\\n\r") . '"';
			case is_bool($var) :
				return $var ? 'true' : 'false';
			case is_scalar($var) :
				return $var;
			case is_object( $var) :
				$var = get_object_vars($var);
				$asso = true;
			case is_array($var) :
				$keys = array_keys($var);
				$ikey = count($keys);
				while (!$asso && $ikey--) {
					$asso = $ikey !== $keys[$ikey];
				}
				$sep = '';
				if ($asso) {
					$ret = '{';
					foreach ($var as $key => $elt) {
						$ret .= $sep . '"' . $key . '":' . Barre_outils::json_export($elt);
						$sep = ',';
					}
					return $ret ."}\n";
				} else {
					$ret = '[';
					foreach ($var as $elt) {
						$ret .= $sep . Barre_outils::json_export($elt);
						$sep = ',';
					}
					return $ret ."]\n";
				}
		}
		return false;
	}
	
}


/**
 * Crée le code CSS pour les images des icones des barres d'outils
 * 
 * S'appuie sur la description des jeux de barres disponibles et cherche
 * une fonction barre_outils_($barre)_icones pour chaque barre et
 * l'exécute si existe, attendant alors en retour un tableau de couples :
 * nom de l'outil => nom de l'image
 *
 * @pipeline_appel porte_plume_lien_classe_vers_icone
 * 
 * @return string Déclaration CSS des icones
 */
function barre_outils_css_icones(){
	// recuperer la liste, extraire les icones
	$css = "";
	
	// liste des barres
	if (!$barres = barre_outils_liste()) 
		return null;
		
	// liste des classes css et leur correspondance avec une icone
	$classe2icone = array();
	foreach ($barres as $barre) {
		include_spip('barre_outils/' . $barre);
		if ($f = charger_fonction($barre . '_icones', 'barre_outils', true)) {
			if (is_array($icones = $f())) {
				$classe2icone = array_merge($classe2icone, $icones);
			}
		}
	}
	
	/**
	 * Permettre aux plugins d'étendre les icones connues du porte plume
	 *
	 * On passe la liste des icones connues au pipeline pour ceux qui
	 * ajoutent de simples icones à des barres existantes
	 *
	 * @pipeline_appel porte_plume_lien_classe_vers_icone
	 * @var array $classe2icone
	 *     Couples identifiant de bouton => nom de l'image (ou tableau)
	 *     Dans le cas d'un tableau, cela indique une sprite : (nom de l'image , position haut, position bas)
	 *     Exemple : 'outil_header1' => array('spt-v1.png','-10px -226px')
	 */
	$classe2icone = pipeline('porte_plume_lien_classe_vers_icone', $classe2icone);
	
	// passage en css
	foreach ($classe2icone as $n=>$i) {
		$pos="";
		if (is_array($i)){
			$pos = "background-position:".end($i);
		  $i = reset($i);
		}
		if (file_exists($i))
			$file = $i;
		else
			$file = find_in_path("icones_barre/$i");
		if ($file)
			$css .= "\n.markItUp .$n>a>em {background-image:url(".protocole_implicite(url_absolue($file)).");$pos}";
	}

	return $css;
}


/**
 * Retourne une instance de Barre_outils
 * crée à partir du type de barre demandé
 *
 * Une fonction barre_outils_{type}_dist() retournant la barre doit
 * donc exister.
 * 
 * @param string $set
 *     Type de barre (ex: 'edition')
 * @return Barre_Outils|bool
 *     La barre d'outil si la fonction a été trouvée, false sinon
 */
function barre_outils_initialiser($set){
	if ($f = charger_fonction($set, 'barre_outils')) {
		// retourne une instance de l'objet Barre_outils
		return $f();
	}
	return false;
}

/**
 * Retourne la liste des barres d'outils connues
 *
 * @return array|bool
 *     Tableau des noms de barres d'outils trouvées
 *     False si on ne trouve aucune barre.
 */
function barre_outils_liste(){
	static $sets = -1;
	if ($sets !== -1) 
		return $sets;

	// on recupere l'ensemble des barres d'outils connues
	if (!$sets = find_all_in_path('barre_outils/','.*[.]php')
	or !is_array($sets)) {
		spip_log("[Scandale] Porte Plume ne trouve pas de barre d'outils !");
		$sets = false;
		return $sets;
	}

	foreach($sets as $fichier=>$adresse) {
		$sets[$fichier] = substr($fichier,0,-4); // juste le nom
	}
	return $sets;
}

/**
 * Filtre appliquant les traitements SPIP d'un champ
 *
 * Applique les filtres prévus sur un champ (et eventuellement un type d'objet)
 * sur un texte donné. Sécurise aussi le texte en appliquant safehtml().
 *
 * Ce mécanisme est à préférer au traditionnel #TEXTE*|propre
 * 
 * traitements_previsu() consulte la globale $table_des_traitements et
 * applique le traitement adequat. Si aucun traitement n'est trouvé,
 * alors propre() est appliqué.
 *
 * @package SPIP\PortePlume\Fonctions
 * @see champs_traitements() dans public/references.php
 * @global table_des_traitements
 * 
 * @param string $texte
 *     Texte source
 * @param string $nom_champ
 *     Nom du champ (nom de la balise, en majuscules)
 * @param string $type_objet
 *     L'objet a qui appartient le champ (en minuscules)
 * @param string $connect
 *     Nom du connecteur de base de données
 * @return string
 *     Texte traité avec les filtres déclarés pour le champ.
 */
function traitements_previsu($texte, $nom_champ='', $type_objet='', $connect=null)
{
	include_spip('public/interfaces'); // charger les traitements

	global $table_des_traitements;
	if (!strlen($nom_champ) || !isset($table_des_traitements[$nom_champ])) {
		$texte = propre($texte, $connect);
	}
	else {
		$table = table_objet($type_objet);
		$ps = $table_des_traitements[$nom_champ];
		if (is_array($ps)) {
			$ps = $ps[(strlen($table) && isset($ps[$table])) ? $table : 0];
		}
		if (!$ps) {
			$texte = propre($texte, $connect);
		} else {
			// [FIXME] Éviter une notice sur le eval suivant qui ne connait
			// pas la Pile ici. C'est pas tres joli...
			$Pile = array( 0 => array() );
			// remplacer le placeholder %s par le texte fourni
			eval('$texte=' . str_replace('%s', '$texte', $ps) . ';');
		}
	}
	// il faut toujours securiser le texte prévisualisé car il peut contenir n'importe quoi
	// et servir de support a une attaque xss ou vol de cookie admin
	// on ne peut donc se fier au statut de l'auteur connecté car le contenu ne vient pas
	// forcément de lui
	return safehtml($texte);
}
?>
