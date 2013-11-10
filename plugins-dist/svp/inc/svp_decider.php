<?php

/**
 * Gestion du décideur : il calcule en fonction d'une demande d'action
 * sur les plugins tous les enchainements que cela implique sur les
 * dépendances.
 *
 * @plugin SVP pour SPIP
 * @license GPL
 * @package SPIP\SVP\Decideur
 */
 
if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('plugins/installer'); // pour spip_version_compare()
include_spip('inc/svp_rechercher'); // svp_verifier_compatibilite_spip()
# include_spip('inc/plugin'); // plugin_version_compatible() [inclu dans svp_rechercher]

/**
 * Le décideur calcule les actions qui doivent être faites en fonction
 * de ce qui est demandé et des différentes dépendances des plugins.
 *
 * @package SPIP\SVP\Actionner
**/
class Decideur {

	/**
	 * Plugins actifs en cours avant toute modification
	 * @var array
	 *     Index 'i' : plugins triés par identifiant en base [i][32] = tableau de description
	 *     Index 'p' : plugins triés par prefixe de plugin [p][MOTS] = tableau de description
	 */
	var $start = array(
		'i' => array(),
		'p' => array(),
	);

	/**
	 * Plugins actifs à la fin des modifications effectuées
	 * @var array
	 *     Index 'i' : plugins triés par identifiant en base [i][32] = tableau de description
	 *     Index 'p' : plugins triés par prefixe de plugin [p][MOTS] = tableau de description
	 */
	var $end = array(
		'i' => array(),
		'p' => array(),
	);

	/**
	 * Plugins procure par SPIP
	 * @var array
	 *     Tableau ('PREFIXE' => numéro de version)
	 */
	var $procure = array();

	/**
	 * Toutes les actions à faire demandées
	 * (ce que l'on demande à l'origine)
	 * @var array
	 *     Tableau ('identifiant' => tableau de description)
	 */
	var $ask = array();

	/**
	 * Toutes les actions à faire demandées et consécutives aux dépendances
	 * 
	 * @var array
	 *     Tableau ('identifiant' => tableau de description)
	 */
	var $todo = array();

	/**
	 * Toutes les actions à faire consécutives aux dépendances
	 *
	 * C'est à dire les actions à faire en plus de celles demandées.
	 * 
	 * @var array
	 *     Tableau ('identifiant' => tableau de description)
	 */
	var $changes = array();

	/**
	 * Tous les plugins à arrêter (désactiver ou désinstaller)
	 * 
	 * @var array
	 *     Tableau ('PREFIXE' => tableau de description)
	 */
	var $off = array();

	/**
	 * Tous les plugins invalidés (suite a des dependances introuvables, mauvaise version de SPIP...)
	 * 
	 * @var array
	 *     Tableau ('PREFIXE' => tableau de description)
	 */
	var $invalides = array();

	/**
	 * Liste des erreurs
	 * 
	 * @var array
	 *     Tableau ('identifiant' => liste des erreurs)
	 */
	var $err = array();

	/**
	 * État de santé (absence d'erreur)
	 * 
	 * Le résultat true permettra d'effectuer toutes les actions.
	 * Passe à false dès qu'une erreur est présente !
	 * 
	 * @var bool */
	var $ok = true;

	/**
	 * Loguer les différents éléments
	 * 
	 * Sa valeur sera initialisée par la configuration 'mode_log_verbeux' de SVP
	 * 
	 * @var bool */
	var $log = false;

	/**
	 * Générer une erreur si on demande une mise à jour d'un plugin
	 * alors qu'on ne la connait pas.
	 * @var bool */
	var $erreur_sur_maj_introuvable = true;

	/**
	 * Constructeur
	 * 
	 * Initialise la propriété $log en fonction de la configuration
	 */
	function Decideur () {
		include_spip('inc/config');
		$this->log = (lire_config('svp/mode_log_verbeux') == 'oui');
	}


	/**
	 * Liste des plugins déjà actifs
	 *
	 * @var array
	 *     Index 'i' : plugins triés par identifiant en base [i][32] = tableau de description
	 *     Index 'p' : plugins triés par prefixe de plugin [p][MOTS] = tableau de description
	 */
	function liste_plugins_actifs() {
		return $this->infos_courtes(array('pa.actif='.Sql::quote('oui'),  'pa.attente=' . Sql::quote('non')));
	}

	/**
	 * Teste si un paquet (via son identifiant) est en attente
	 *
	 * Les plugins en attente ont un statut spécial : à la fois dans la
	 * liste des plugins actifs, mais désactivés. Un plugin passe 'en attente'
	 * lorsqu'il est actif mais perd accidentellement une dépendance,
	 * par exemple si une dépendance est supprimée par FTP.
	 * Dès que sa dépendance revient, le plugin se réactive.
	 *
	 * L'interface de gestion des plugins de SVP, elle, permet pour ces plugins
	 * de les désactiver ou réactiver (retéléchargeant alors la dépendance si possible).
	 *
	 * @param int $id
	 *     Identifiant du plugin
	 * @return bool
	 *     Le plugin est-il en attente ?
	 */
	function est_attente_id($id) {
		static $attente = null;
		if (is_null($attente)) {
			$attente = $this->infos_courtes('pa.attente=' . Sql::quote('oui'));
		}
		return isset($attente['i'][$id]) ? $attente['i'][$id] : false;
	}

	/**
	 * Liste des plugins procurés par SPIP
	 *
	 * Calcule la liste des plugins que le core de SPIP déclare procurer.
	 *
	 * @return array
	 *     Tableau ('PREFIXE' => version)
	 */
	function liste_plugins_procure() {
		$procure = array();
		$get_infos = charger_fonction('get_infos','plugins');
		$infos['_DIR_RESTREINT'][''] = $get_infos('./',false,_DIR_RESTREINT);

		foreach($infos['_DIR_RESTREINT']['']['procure'] as $_procure) {
			$prefixe = strtoupper($_procure['nom']);
			$procure[$prefixe] = $_procure['version'];
		}

		return $procure;
	}

	/**
	 * Écrit un log
	 *
	 * Écrit un log si la propriété $log l'autorise.
	 * 
	 * @param mixed $quoi
	 *     La chose à logguer (souvent un texte)
	**/
	function log($quoi) {
		if ($this->log) {
			spip_log($quoi,'decideur');
		}
	}

	/**
	 * Retourne le tableau de description d'un paquet (via son identifiant) 
	 *
	 * @note
	 *     Attention, retourne un tableau complexe.
	 *     La description sera dans : ['i'][$id]
	 * @param int $id
	 *     Identifiant du paquet
	 * @return array
	 *     Index 'i' : plugins triés par identifiant en base [i][32] = tableau de description
	 *     Index 'p' : plugins triés par prefixe de plugin [p][MOTS] = tableau de description
	**/
	function infos_courtes_id($id) {
		// on cache ceux la
		static $plug = array();
		if (!isset($plug[$id])) {
			$plug[$id] = $this->infos_courtes('pa.id_paquet=' . Sql::quote($id));
		}
		return $plug[$id];
	}

	/**
	 * Récupérer les infos utiles des paquet
	 *
	 * Crée un tableau de description pour chaque paquet dans une
	 * écriture courte comme index ('i' pour identifiant) tel que :
	 * - i = identifiant
	 * - p = prefixe (en majuscule)
	 * - n = nom du plugin
	 * - v = version
	 * - e = etat
	 * - a = actif
	 * - du = dépendances utilise
	 * - dn = dépendances nécessite
	 * - dl = dépendances librairie
	 * - maj = mise à jour
	 * 
	 * 
	 * On passe un where ($condition) et on crée deux tableaux, l'un des paquets
	 * triés par identifiant, l'autre par prefixe.
	 *
	 * @param array|string $condition
	 *     Condition where
	 * @param bool $multiple
	 *     Si multiple, le tableau par préfixe est un sous-tableau (il peut alors
	 *     y avoir plusieurs paquets pour un même prefixe, classés par états décroissants)
	 * @return array
	 *     Index 'i' : plugins triés par identifiant en base [i][32] = tableau de description
	 *     Index 'p' : plugins triés par prefixe de plugin [p][MOTS] = tableau de description
	 *                 ou, avec $multiple=true : [p][MOTS][] = tableau de description
	 */
	function infos_courtes($condition, $multiple=false) {
		$plugs = array(
			'i'=>array(),
			'p'=>array()
		);

		$from = array('spip_paquets AS pa', 'spip_plugins AS pl');
		$orderby = $multiple ? 'pa.etatnum DESC' : '';
		$where = array('pa.id_plugin = pl.id_plugin');
		if (is_array($condition))
			$where = array_merge($where, $condition);
		else
			$where[] = $condition;

		include_spip('inc/filtres'); // extraire_multi()
		$res = Sql::allfetsel(array(
			'pa.id_paquet AS i',
			'pl.nom AS n',
			'pl.prefixe AS p',
			'pa.version AS v',
			'pa.etatnum AS e',
			'pa.compatibilite_spip',
			'pa.dependances',
			'pa.id_depot',
			'pa.maj_version AS maj',
			'pa.actif AS a'), $from, $where, '', $orderby);
		foreach ($res as $r) {
			$r['p'] = strtoupper( $r['p'] ); // on s'assure du prefixe en majuscule.
			
			// savoir si un paquet est en local ou non...
			$r['local'] = ($r['id_depot']) == 0 ? true : false;
			unset($r['id_depot']);
			
			$d = unserialize($r['dependances']);
			// voir pour enregistrer en bdd simplement 'n' et 'u' (pas la peine d'encombrer)...
			$deps = array('necessite'=>array(array()), 'utilise'=>array(array()), 'librairie'=>array(array()));
			if (!$d) $d = $deps;
			
			unset($r['dependances']);

			/*
			 * On extrait les multi sur le nom du plugin
			 */
			$r['n'] = extraire_multi($r['n']);

			$plugs['i'][$r['i']] = $r;

			
			// pour chaque type de dependences... (necessite, utilise, librairie)
			// on cree un tableau unique [$dependence] = array()
			// au lieu de plusieurs tableaux par version de spip
			// en ne mettant dans 0 que ce qui concerne notre spip local
			foreach($deps as $cle => $defaut) {
				if (!isset($d[$cle])) {
					$d[$cle] = $defaut;
				}
				
				// gerer les dependences autres que dans 0 (communs ou local) !!!!
				// il peut exister des cles info[dn]["[version_spip_min;version_spip_max]"] de dependences
				if (!isset($d[$cle][0]) OR count($d[$cle]) > 1) {
					$dep = array();
					$dep[0] = isset($d[$cle][0]) ? $d[$cle][0] : array();
					unset($d[$cle][0]);
					foreach ($d[$cle] as $version => $dependences) {
						if (svp_verifier_compatibilite_spip($version)) {
							$dep = array_merge($dep[0], $dependences);
						}
					}
					$d[$cle] = $dep;
				}
			}
			// passer les prefixes en majuscule
			foreach($d['necessite'][0] as $i=>$n) {
				$d['necessite'][0][$i]['nom'] = strtoupper($n['nom']);
			}
			$plugs['i'][$r['i']]['dn'] = $d['necessite'][0];
			$plugs['i'][$r['i']]['du'] = $d['utilise'][0];
			$plugs['i'][$r['i']]['dl'] = $d['librairie'][0];


			if ($multiple) {
				$plugs['p'][$r['p']][] = &$plugs['i'][$r['i']]; // alias
			} else {
				$plugs['p'][$r['p']] = &$plugs['i'][$r['i']]; // alias
			}
			
		}
		return $plugs;
	}


	/**
	 * Ajoute une erreur sur un paquet
	 *
	 * Passe le flag OK à false : on ne pourra pas faire les actions demandées.
	 * 
	 * @param int $id
	 *     Identifiant de paquet
	 * @param string $texte
	 *     Texte de l'erreur
	 */
	function erreur($id, $texte = '') {
		$this->log("erreur: $id -> $texte");
		if (!isset($this->err[$id]) OR !is_array($this->err[$id])) {
			$this->err[$id] = array();
		}
		$this->err[$id][] = $texte;
		$this->ok = false;
	}

	/**
	 * Teste si une erreur est présente sur un paquet (via son identifiant)
	 *
	 * @param int $id
	 *     Identifiant de paquet
	 * @return bool|array
	 *     false si pas d'erreur, tableau des erreurs sinon.
	 */
	function en_erreur($id) {
		return isset($this->err[$id]) ? $this->err[$id] : false;
	}


	/**
	 * Vérifie qu'un plugin plus récent existe pour un préfixe et une version donnée
	 *
	 * @param string $prefixe
	 *     Préfixe du plugin
	 * @param string $version
	 *     Compatibilité à comparer, exemple '[1.0;]'
	 * @return bool|array
	 *     false si pas de plugin plus récent trouvé
	 *     tableau de description du paquet le plus récent sinon
	 */
	function chercher_plugin_recent($prefixe, $version) {
		$news = $this->infos_courtes(array('pl.prefixe=' . Sql::quote($prefixe), 'pa.obsolete=' . Sql::quote('non'), 'pa.id_depot > '.Sql::quote(0)), true);
		$res = false;
		if ($news and count($news['p'][$prefixe]) > 0) {
			foreach ($news['p'][$prefixe] as $new) {
				if (spip_version_compare($new['v'],$version,'>')) {
					if (!$res or version_compare($new['v'],$res['v'],'>')) {
						$res = $new;
					}
				}
			}
		}
		return $res;
	}

	/**
	 * Vérifie qu'un plugin existe pour un préfixe et une version donnée
	 * 
	 * @param string $prefixe
	 *     Préfixe du plugin
	 * @param string $version
	 *     Compatibilité à comparer, exemple '[1.0;]'
	 * @return bool|array
	 *     false si pas de plugin plus récent trouvé
	 *     tableau de description du paquet le plus récent sinon
	 */
	function chercher_plugin_compatible($prefixe, $version) {
		
		// on choisit en priorite dans les paquets locaux !
		$locaux = $this->infos_courtes(array(
			'pl.prefixe=' . Sql::quote($prefixe),
			'pa.obsolete=' . Sql::quote('non'),
			'pa.id_depot='.Sql::quote(0)), true);
		if ($locaux and isset($locaux['p'][$prefixe]) and count($locaux['p'][$prefixe]) > 0) {
			foreach ($locaux['p'][$prefixe] as $new) {
				if (plugin_version_compatible($version, $new['v'])
				and svp_verifier_compatibilite_spip($new['compatibilite_spip']) ){
					return $new;
				}
			}
		}
		
		// sinon dans les paquets distants
		$distants = $this->infos_courtes(array(
			'pl.prefixe=' . Sql::quote($prefixe),
			'pa.obsolete=' . Sql::quote('non'),
			'pa.id_depot>'.Sql::quote(0)), true);
		if ($distants and isset($distants['p'][$prefixe]) and count($distants['p'][$prefixe]) > 0) {
			foreach ($distants['p'][$prefixe] as $new) {
				if (plugin_version_compatible($version, $new['v'])
				and svp_verifier_compatibilite_spip($new['compatibilite_spip']) ){
					return $new;
				}
			}
		}
		
		return false;
	}


	/**
	 * Indique qu'un paquet passe à on (on l'active)
	 *
	 * @param array $info
	 *     Description du paquet
	**/
	function add($info) {
		$this->end['i'][$info['i']] = $info;
		$this->end['p'][$info['p']] = &$this->end['i'][$info['i']];
	}

	/**
	 * Indique qu'un paquet passe à off (on le désactive ou désinstalle)
	 *
	 * @param array $info
	 *     Description du paquet
	 * @param bool $recur
	 *     Passer à off les plugins qui en dépendent, de façon récursive ?
	**/
	function off($info, $recur = false) {
		$this->log('- stopper ' . $info['p']);
		$this->remove($info);
		$this->off[$info['p']] = $info;

		// si recursif, on stoppe aussi les plugins dependants
		if ($recur) {
			foreach ($this->end['i'] as $id => $plug) {
				if (is_array($plug['dn']) and $plug['dn']) {
					foreach ($plug['dn'] as $n) {
						if ($info['p'] == $n['nom']) {
							$this->change($plug, 'off');
							$this->off($plug, true);
						}
					}
				}
			}
		}
	}

	/**
	 * Teste qu'un paquet (via son préfixe) sera passé off (désactivé ou désinstallé)
	 *
	 * @param string $prefixe
	 *     Prefixe du paquet
	 * @return bool
	 *     Le paquet sera t'il off ?
	**/
	function sera_off($prefixe) {
		return isset($this->off[$prefixe]) ? $this->off[$prefixe] : false;
	}

	/**
	 * Teste qu'un paquet (via son identifiant) sera passé off (désactivé ou désinstallé)
	 *
	 * @param int $id
	 *     Identifiant du paquet
	 * @return bool
	 *     Le paquet sera t'il off ?
	**/
	function sera_off_id($id) {
		foreach ($this->off as $info) {
			if ($info['i'] == $id) {
				return $info;
			}
		}
		return false;
	}

	/**
	 * Teste qu'un paquet (via son préfixe) sera actif
	 *
	 * @param string $prefixe
	 *     Préfixe du paquet
	 * @return bool
	 *     Le paquet sera t'il actif ?
	**/
	function sera_actif($prefixe) {
		return isset($this->end['p'][$prefixe]) ? $this->end['p'][$prefixe] : false;
	}

	/**
	 * Teste qu'un paquet (via son identifiant) sera actif
	 *
	 * @param int $id
	 *     Identifiant du paquet
	 * @return bool
	 *     Le paquet sera t'il actif ?
	**/
	function sera_actif_id($id) {
		return isset($this->end['i'][$id]) ? $this->end['i'][$id] : false;
	}

	/**
	 * Ajouter une action/paquet à la liste des demandées
	 *
	 * L'ajoute aussi à la liste de toutes les actions !
	 * 
	 * @param array $info
	 *     Description du paquet concerné
	 * @param string $quoi
	 *     Type d'action (on, off, kill, upon...)
	 */
	function ask($info, $quoi) {
		$this->ask[$info['i']] = $info;
		$this->ask[$info['i']]['todo'] = $quoi;
		$this->todo($info, $quoi);
	}

	/**
	 * Ajouter une action/paquet à la liste des changements en plus
	 * par rapport à la demande initiale
	 *
	 * L'ajoute aussi à la liste de toutes les actions !
	 * 
	 * @param array $info
	 *     Description du paquet concerné
	 * @param string $quoi
	 *     Type d'action (on, off, kill, upon...)
	 */
	function change($info, $quoi) {
		$this->changes[$info['i']] = $info;
		$this->changes[$info['i']]['todo'] = $quoi;
		$this->todo($info, $quoi);
	}


	/**
	 * Annule une action (automatique) qui finalement était réellement demandée.
	 * 
	 * Par exemple, une mise à 'off' de paquet entraîne d'autres mises à
	 * 'off' des paquets qui en dépendent. Si une action sur un des paquets
	 * dépendants était aussi demandée, il faut annuler l'action automatique.
	 *
	 * @param array $info
	 *     Description du paquet concerné
	 */
	function annule_change($info) {
		unset($this->changes[$info['i']]);
	}

	/**
	 * Ajouter une action/paquet à la liste de toutes les actions à faire
	 *
	 * @param array $info
	 *     Description du paquet concerné
	 * @param string $quoi
	 *     Type d'action (on, off, kill, upon...)
	 */
	function todo($info, $quoi) {
		$this->todo[$info['i']] = $info;
		$this->todo[$info['i']]['todo'] = $quoi;
	}

	/**
	 * Retire un paquet de la liste des paquets à activer
	 *
	 * @param array $info
	 *     Description du paquet concerné
	 */
	function remove($info) {
		// aucazou ce ne soit pas les memes ids entre la demande et la bdd,
		// on efface aussi avec l'id donne par le prefixe.
		// Lorsqu'on desactive un plugin en "attente", il n'est pas actif !
		// on teste tout de meme donc qu'il est la ce prefixe !
		$i = false;
		if (isset($this->end['p'][$info['p']])) {
			$i = $this->end['p'][$info['p']];
		}
		// on enleve les cles par id indique et par prefixe
		unset($this->end['i'][$info['i']], $this->end['p'][$info['p']]);
		// ainsi que l'id aucazou du prefixe
		if ($i) {
			unset($this->end['i'][$i['i']]);
		}

	}


	/**
	 * Invalide un plugin (il est introuvable, ne correspond pas à notre version de SPIP...)
	 *
	 * @param array $info
	 *     Description du paquet concerné
	 */
	function invalider($info) {
		$this->log("-> invalider $info[p]");
		$this->remove($info); // suffisant ?
		$this->invalides[$info['p']] = $info;
		$this->annule_change($info);
		unset($this->todo[$info['i']]);
	}

	/**
	 * Teste qu'un paquet (via son préfixe) est déclaré invalide
	 *
	 * @param string $p
	 *     Prefixe du paquet
	 * @return bool
	 *     Le paquet est t'il invalide ?
	**/
	function sera_invalide($p) {
		return isset($this->invalides[$p]) ? $this->invalides[$p] : false;
	}

	/**
	 * Teste qu'une librairie (via son nom) est déjà présente
	 *
	 * @param string $lib
	 *     Nom de la librairie
	 * @return bool
	 *     La librairie est-elle présente ?
	**/
	function est_presente_lib($lib) {
		static $libs = false;
		if ($libs === false) {
			include_spip('inc/svp_outiller');
			$libs = svp_lister_librairies();
		}
		return isset($libs[$lib]) ? $libs[$lib] : false;
	}


	/**
	 * Ajoute les actions demandées au décideur
	 *
	 * Chaque action est analysée et elles sont redispatchées dans différents
	 * tableaux via les méthodes :
	 * - ask  : ce qui est demandé (ils y vont tous)
	 * - todo : ce qui est à faire (ils y vont tous aussi)
	 * - add  : les plugins activés,
	 * - off  : les plugins désactivés
	 *
	 * La fonction peut lever des erreurs sur les actions tel que :
	 * - Paquet demandé inconnu
	 * - Mise à jour introuvable
	 * - Paquet à désactiver mais qui n'est pas actif
	 *
	 * @param array $todo
	 *     Ce qui est demandé de faire
	 *     Tableau identifiant du paquet => type d'action (on, off, up...)
	 * @return bool
	 *     False en cas d'erreur, true sinon
	 */
	function actionner($todo = null) {
		if (is_array($todo)) {
			foreach ($todo as $id => $t) {
				// plusieurs choses nous interessent... Sauf... le simple telechargement
				// et la suppression des fichiers (qui ne peuvent etre fait
				// que si le plugin n'est pas actif)
				$this->log("-- todo: $id/$t");

				switch ($t) {
					case 'getlib':
						break;
					case 'on':
					case 'geton':
						// ajouter ce plugin dans la liste
						if (!$this->sera_actif_id($id)) {
							$i = $this->infos_courtes_id($id);
							if ($i = $i['i'][$id]) {
								$this->log("--> $t : " . $i['p'] . ' en version : ' . $i['v'] );

								// se mefier : on peut tenter d'activer
								// un plugin de meme prefixe qu'un autre deja actif
								// mais qui n'est pas de meme version ou de meme etat
								// par exemple un plugin obsolete ou un plugin au contraire plus a jour.
								// dans ce cas, on desactive l'ancien (sans desactiver les dependences)
								// et on active le nouveau.
								// Si une dependance ne suit pas, une erreur se produira du coup.
								if (isset($this->end['p'][ $i['p'] ])) {
									$old = $this->end['p'][ $i['p'] ];
									$this->log("-->> off : " . $old['p'] . ' en version : ' . $old['v'] );
									$this->ask($old, 'off');
									$this->todo($old, 'off');
									// désactive l'ancien plugin, mais pas les dépendances qui en dépendent
									// car normalement, ça devrait suivre...
									$this->off($old, false); 

								}
								
								// pas de prefixe equivalent actif...
								$this->add($i);
								$this->ask($i, $i['local'] ? 'on' : 'geton' );
								
							} else {
								// la c'est vraiment pas normal... Erreur plugin inexistant...
								// concurrence entre administrateurs ?
								$this->erreur($id, _T('svp:message_nok_plugin_inexistant',array('plugin' => $id)));
							}
						}
						break;
					case 'up':
					case 'upon':
						// le plugin peut etre actif !
						// ajouter ce plugin dans la liste et retirer l'ancien
						$i = $this->infos_courtes_id($id);
						if ($i = $i['i'][$id]) {
							$this->log("--> $t : " . $i['p'] . ' en version : ' . $i['v'] );

							// new : plugin a installer
							if ($new = $this->chercher_plugin_recent($i['p'], $i['v'])) {
								$this->log("--> maj : " . $new['p'] . ' en version : ' . $new['v'] );
								// ajouter seulement si on l'active !
								// ou si le plugin est actuellement actif
								if ($t == 'upon' or $this->sera_actif_id($id)) {
									$this->remove($i);
									$this->add($new);
								}
								$this->ask($i, $t);
							} else {
								if ($this->erreur_sur_maj_introuvable) {
									// on n'a pas trouve la nouveaute !!!
									$this->erreur($id, _T('svp:message_nok_maj_introuvable',array('plugin' => $i['n'],'id'=>$id)));
								}
							}
						} else {
							// mauvais identifiant ?
							// on n'a pas trouve le plugin !!!
							$this->erreur($id, _T('svp:message_erreur_maj_inconnu',array('id'=>$id)));
						}
						break;
					case 'off':
					case 'stop':
						// retirer ce plugin
						// (il l'est peut etre deja)
						if ($info = $this->sera_actif_id($id)
						or  $info_off = $this->sera_off_id($id)
						// un plugin en attente (desactive parce sa dependance a disparu certainement par ftp)
						// peut etre desactive
						or $info = $this->est_attente_id($id)) {
							// annuler le signalement en "proposition" (due a une mise a 'off' recursive)
							// de cet arret de plugin, vu qu'on le demande reellement
							if (!$info) {
								$info = $info_off;
								$this->annule_change($info);
							}
							$this->log("--> $t : " . $info['p'] . ' en version : ' . denormaliser_version($info['v']) );
							$this->ask($info, $t);
							$this->todo($info, $t);
							// désactive tous les plugins qui en dépendent aussi.
							$this->off($info, true);

						} else {
							// pas normal... plugin deja inactif...
							// concurrence entre administrateurs ?
							$this->erreur($id, _T('svp:message_erreur_plugin_non_actif'));
						}
						break;
					case 'null':
					case 'get':
					case 'kill':
						if ($info = $this->infos_courtes_id($id)) {
							$this->log("--> $t : " . $info['i'][$id]['p'] . ' en version : ' . $info['i'][$id]['v'] );
							$this->ask($info['i'][$id], $t);
						} else {
							// pas normal... plugin inconnu... concurrence entre administrateurs ?
							$this->erreur($id, _T('svp:message_erreur_plugin_introuvable',array('plugin'=>$id,'action'=>$t)));
						}
						break;
				}
			}
		}
		return $this->ok;
	}


	/**
	 * Initialise les listes de plugins pour le calcul de dépendances
	 *
	 * Les propriété $start et $end reçoivent la liste des plugins actifs
	 * $procure celle des plugins procurés par le Core
	 */
	function start() {
		$this->start = $this->end = $this->liste_plugins_actifs();
		$this->procure = $this->liste_plugins_procure();
	}

	/**
	 * Vérifier (et activer) les dépendances
	 *
	 * Pour chaque plugin qui sera actif, vérifie qu'il respecte
	 * ses dépendances.
	 *
	 * Si ce n'est pas le cas, le plugin n'est pas activé et le calcul
	 * de dépendances se refait sans lui. À un moment on a normalement
	 * rapidement une liste de plugins cohérents (au pire on ne boucle
	 * que 100 fois maximum - ce qui ne devrait jamais se produire).
	 *
	 * Des erreurs sont levées lorsqu'un plugin ne peut honorer son activation
	 * à cause d'un problème de dépendance. On peut les récupérer dans la
	 * propriété $err.
	 *
	 * @api
	 * @param array $todo
	 *     Ce qui est demandé de faire
	 *     Tableau identifiant du paquet => type d'action (on, off, up...)
	 * @return bool
	 *     False en cas d'erreur, true sinon
	 */
	function verifier_dependances($todo = null) {

		$this->start();

		// ajouter les actions
		if (!$this->actionner($todo)) {
			$this->log("! Todo en echec !");
			$this->log($decideur->err);
			return false;
		}

		// doit on reverifier les dependances ?
		// oui des qu'on modifie quelque chose...
		// attention a ne pas boucler infiniment !

		$supersticieux = 0;
		do {
			$try_again = 0;
			$supersticieux++;

			// verifier chaque dependance de chaque plugin a activer
			foreach ($this->end['i'] as $info) {
				if (!$this->verifier_dependances_plugin($info)) {
					$try_again = true;
				}
			}
			unset($id, $info);
			$this->log("--------> try_again: $try_again, supersticieux: $supersticieux");
		} while ($try_again > 0 and $supersticieux < 100); # and !count($this->err)

		$this->log("Fin !");
		$this->log("Ok: " . $this->ok);
		# $this->log($this->todo);

		return $this->ok;
	}


	/**
	 * Pour une description de paquet donnée, vérifie sa validité.
	 *
	 * Teste la version de SPIP, les librairies nécessitées, ses dépendances
	 * (et tente de les trouver et ajouter si elles ne sont pas là)
	 *
	 * Lorsqu'une dépendance est activée, on entre en récursion
	 * dans cette fonction avec la description de la dépendance
	 * 
	 * @param array $info
	 *     Description du paquet
	 * @param int $prof
	 *     Profondeur de récursion
	 * @return bool
	 *     false si erreur (dépendance non résolue, incompatibilité...), true sinon
	**/
	function verifier_dependances_plugin($info, $prof=0) {
		$this->log("- [$prof] verifier dependances " . $info['p']);
		$id = $info['i'];
		$err = false; // variable receptionnant parfois des erreurs
		$cache = array(); // cache des actions realisees dans ce tour

		// 1
		// tester la version de SPIP de notre paquet
		// si on ne valide pas, on retourne une erreur !
		// mais normalement, on ne devrait vraiment pas pouvoir tomber sur ce cas
		if (!svp_verifier_compatibilite_spip($info['compatibilite_spip'])) {
			$this->invalider($info);
			$this->erreur($id, _T('svp:message_incompatibilite_spip',array('plugin'=>$info['n'])));
			return false;
		}


		// 2
		// ajouter les librairies necessaires a notre paquet
		if (is_array($info['dl']) and count($info['dl'])) {
			foreach ($info['dl'] as $l) {
				// $l = array('nom' => 'x', 'lien' => 'url')
				$lib = $l['nom'];
				$this->log("## Necessite la librairie : " . $lib );

				// on verifie sa presence OU le fait qu'on pourra la telecharger
				if ($lib and !$this->est_presente_lib($lib)) {
					// peut on ecrire ?
					if (!is_writable(_DIR_LIB)) {
						$this->invalider($info);
						$this->erreur($id, _T('svp:message_erreur_ecriture_lib', array('plugin'=>$info['n'], 'lib_url'=>$l['lien'], 'lib'=>$lib)));
						$err = true;
					}
					// ajout, pour info
					// de la librairie dans la todo list
					else {
						$this->change(array(
							'i' => md5(serialize($l)),
							'p' => $lib,
							'n' => $lib,
							'v' => $l['lien'],
						), 'getlib');
						$this->log("- La librairie $lib sera a télécharger");
					}
				}
			}
			if ($err) {
				return false;
			}
		}

		// 3
		// Trouver les dependences aux necessites
		// et les activer au besoin
		if (is_array($info['dn']) and count($info['dn'])) {
			foreach ($info['dn'] as $n) {

				$p = $n['nom'];
				$v = $n['compatibilite'];

				if ($p == 'SPIP') {
					// c'est pas la que ça se fait !
					// ca ne devrait plus apparaitre comme dependence a un plugin.
				}

				// le core procure le paquet que l'on demande !
				elseif ((array_key_exists($p, $this->procure))
				  and (plugin_version_compatible($v, $this->procure[$p], 'spip'))) {
					// rien a faire...
					$this->log("-- est procure par le core ($p)");

				}

				// pas d'autre alternative qu'un vrai paquet a activer
				else {
					$this->log("-- verifier : $p");
					// nous sommes face a une dependance de plugin
					// on regarde s'il est present et a la bonne version
					// sinon on le cherche et on l'ajoute
					if ($ninfo = $this->sera_actif($p)
					and !$err = $this->en_erreur($ninfo['i'])
					and plugin_version_compatible($v, $ninfo['v'])) {
						// il est deja actif ou a activer, et tout est ok
						$this->log('-- dep OK pour '.$info['p'].' : '.$p);
					}
					// il faut le trouver et demander a l'activer
					else {
					
						// absent ou erreur ou pas compatible
						$etat = $err ? 'erreur' : ($ninfo ? 'conflit' : 'absent');
						// conflit signifie qu'il existe le prefixe actif, mais pas a la version demandee
						$this->log("Dedendance " . $p . " a resoudre ! ($etat)");

						switch ($etat) {
							// commencons par le plus simple :
							// en cas d'absence, on cherche ou est ce plugin !
							case 'absent':
								// on choisit par defaut le meilleur etat de plugin.
								// de preference dans les plugins locaux, sinon en distant.
								if (!$this->sera_off($p)
								and $new = $this->chercher_plugin_compatible($p, $v)
								and $this->verifier_dependances_plugin($new, ++$prof)) {
									// si le plugin existe localement et possede maj_version,
									// c'est que c'est peut etre une mise a jour + activation a faire
									// si le plugin
									// nouveau est local   => non
									// nouveau est distant => oui peut etre
									$cache[] = $new;
									$i = array();
									if (!$new['local']) {
										$i = $this->infos_courtes(array(
												'pl.prefixe=' . Sql::quote($new['p']),
												'pa.maj_version=' . Sql::quote($new['v'])
											), true);
									}
									if ($i and isset($i['p'][$new['p']]) and count($i['p'][$new['p']])) {
										// c'est une mise a jour
										$vieux = $i['p'][$new['p']][0];
										$this->change($vieux, 'upon');
										$this->log("-- update+active : $p");
									} else {
										// tout nouveau tout beau
										$this->change($new, $new['local'] ? 'on' : 'geton');
										if ($new['local']) {
											$this->log("-- nouveau present : $p");
										} else {
											$this->log("-- nouveau distant : $p");
										}
									}
									$this->add($new);
								} else {
									$this->log("-- !erreur : $p");
									// on ne trouve pas la dependance !
									$this->invalider($info);
									$this->erreur($id, $v ? _T('svp:message_dependance_plugin_version',array('plugin'=>$info['n'],'dependance'=>$p,'version'=>denormaliser_version($v))) : _T('svp:message_dependance_plugin',array('plugin'=>$info['n'],'dependance'=>$p)));
								}
								unset($new, $vieux);
								break;

							case 'erreur':
								break;

							// present, mais conflit de version
							// de deux choses l'une :
							// soit on trouve un paquet meilleur...
							// soit pas :)
							case 'conflit':
								$this->log("  conflit -> demande $v, present : " . $ninfo['v']);
								if (!$this->sera_off($p)
								and $new = $this->chercher_plugin_compatible($p, $v)
								and $this->verifier_dependances_plugin($new, ++$prof)) {
									// on connait le nouveau...
									$cache[] = $new;
									$this->remove($ninfo);
									$this->add($new);
									$this->change($ninfo,'up');
									$this->log("-- update : $p");
								} else {
									$this->log("-- !erreur : $p");
									// on ne trouve pas la dependance !
									$this->invalider($info);
									$this->erreur($id, $v ? _T('svp:message_dependance_plugin_version',array('plugin'=>$info['n'],'dependance'=>$p,'version'=>denormaliser_version($v))) : _T('svp:message_dependance_plugin',array('plugin'=>$info['n'],'dependance'=>$p)));
								}
								break;
						}

					} 
				}

				if ($this->sera_invalide($info['p'])) {
					break;
				}
			}
			unset($n, $v, $p, $ninfo, $present, $conflit, $erreur, $err);

			// si le plugin est devenu invalide...
			// on invalide toutes les actions qu'on vient de faire !
			if ($this->sera_invalide($info['p'])) {
				$this->log("> Purge du cache");
				foreach ($cache as $i) {
					$this->invalider($i);
				}
				return false;
			}
		}
		return true;
	}

	/**
	 * Retourne un tableau des différentes actions qui seront faites 
	 *
	 * @param string $quoi
	 *     Type de demande
	 *     - ask : les actions demandées
	 *     - changes : les actions en plus par rapport à ce qui était demandé
	 *     - todo : toutes les actions 
	 * @return array
	 *     Liste des actions (joliement traduites et expliquées)
	**/
	function presenter_actions($quoi) {
		$res = array();
		foreach ($this->$quoi as $id=>$info) {
			$res[] = _T('svp:message_action_'.$info['todo'], array(
				'plugin'=>$info['n'],
				'version'=>denormaliser_version($info['v']),
				'version_maj'=>denormaliser_version($info['maj'])));
		}
		return $res;
	}
}


/**
 * Gère la partie vérifier des formulaires utilisant le Décideur 
 *
 * @param array $a_actionner
 *     Tableau des actions par paquet (id_paquet => action)
 * @param array $erreurs
 *     Tableau d'erreurs de verifier (CVT)
 * @return bool
 *     true si tout va bien, false sinon (erreur pour trouver les dépendances, ...)
**/
function svp_decider_verifier_actions_demandees($a_actionner, &$erreurs) {
	$decideur = new Decideur;
	$decideur->erreur_sur_maj_introuvable = false;
	$decideur->verifier_dependances($a_actionner);

	if (!$decideur->ok) {
		$erreurs['decideur_erreurs'] = array();
		foreach ($decideur->err as $id=>$errs) {
			foreach($errs as $err) {
				$erreurs['decideur_erreurs'][] = $err;
			}
		}
		return false;
	}

	$erreurs['decideur_propositions'] = $decideur->presenter_actions('changes');
	$erreurs['decideur_demandes']     = $decideur->presenter_actions('ask');
	$erreurs['decideur_actions']      = $decideur->presenter_actions('todo');

	// On construit la liste des actions pour la passer au formulaire en hidden
	$todo = array();
	foreach ($decideur->todo as $_todo) {
		$todo[$_todo['i']] = $_todo['todo'];
	}
	set_request('_todo', serialize($todo));
	return true;
}
?>
