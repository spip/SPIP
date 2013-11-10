<?php

/**
 * Gestion de l'actionneur : il effectue les actions sur les plugins 
 *
 * @plugin SVP pour SPIP
 * @license GPL
 * @package SPIP\SVP\Actionneur
 */
 
if (!defined("_ECRIRE_INC_VERSION")) return;


/**
 * L'actionneur calcule l'ordre des actions, permet de les stocker
 * dans un fichier cache et de les effectuer.
 *
 * @package SPIP\SVP\Actionner
**/
class Actionneur {

	/**
	 * Instance du décideur
	 * @var Decideur */
	var $decideur;

	/**
	 * Loguer les différents éléments
	 * 
	 * Sa valeur sera initialisée par la configuration 'mode_log_verbeux' de SVP
	 * 
	 * @var bool */
	var $log = false;

	/**
	 * Liste des actions à faire
	 * @var array
	 *     Tableau identifiant du paquet => type d'action
	 */
	var $start = array();

	/**
	 * Actions en cours d'analyse
	 *
	 * Lorsqu'on ajoute les actions à faire, elles sont réordonnées
	 * et classées dans ces quatre sous-tableaux
	 *
	 * Chaque sous-tableau est composé d'une description courte du paquet
	 * auquel est ajouté dans l'index 'todo' le type d'action à faire.
	 * 
	 * @var array
	 *     Index 'off' : les paquets à désactiver (ordre inverse des dépendances)
	 *     Index 'lib' : les librairies à installer
	 *     Index 'on' : les paquets à activer (ordre des dépendances)
	 *     Index 'neutre' : autres actions dont l'ordre a peu d'importance.
	 */
	var $middle = array(
		'off' => array(),
		'lib' => array(),
		'on' => array(),
		'neutre' => array(),
	);

	// actions à la fin (apres analyse, et dans l'ordre)

	/**
	 * Liste des actions à faire
	 * 
	 * Liste de description courtes des paquets + index 'todo' indiquant l'action
	 * @var array */
	var $end = array();

	/**
	 * Liste des actions faites
	 * Liste de description courtes des paquets + index 'todo' indiquant l'action
	 * @var array */
	var $done = array(); // faites

	/**
	 * Actions en cours
	 * Description courte du paquet + index 'todo' indiquant l'action
	 * @var array */
	var $work = array();

	/**
	 * Liste des erreurs
	 * 
	 * @var array Liste des erreurs */
	var $err = array();

	/**
	 * Verrou.
	 * Le verrou est posé au moment de passer à l'action.
	 * @var array
	 *     Index 'id_auteur' : Identifiant de l'auteur ayant déclenché des actions
	 *     Indix 'time' : timestamp de l'heure de déclenchement de l'action */
	var $lock = array('id_auteur'=>0, 'time'=>'');

	/**
	 * SVP (ce plugin) est-il à désactiver dans une des actions ?
	 *
	 * Dans ce cas, on tente de le désactiver après d'autres plugins à désactiver
	 * sinon l'ensemble des actions suivantes échoueraient.
	 * 
	 * @var bool
	 *     false si SVP n'est pas à désactiver, true sinon */
	var $svp_off = false;

	/**
	 * Constructeur
	 *
	 * Détermine si les logs sont activés et instancie un décideur.
	 */
	function Actionneur(){
		include_spip('inc/config');
		$this->log = (lire_config('svp/mode_log_verbeux') == 'oui');

		include_spip('inc/svp_decider');
		$this->decideur = new Decideur();
		#$this->decideur->start();

		// pour denormaliser_version()
		include_spip('svp_fonctions');
	}


	/**
	 * Ajoute un log
	 *
	 * Ajoute un log si la propriété $log l'autorise;
	 *
	 * @param mixed $quoi
	 *     La chose à logguer (souvent un texte)
	**/
	function log($quoi) {
		if ($this->log) {
			spip_log($quoi,'actionneur');
		}
	}

	/**
	 * Ajoute une erreur
	 * 
	 * Ajoute une erreur à la liste des erreurs présentées au moment
	 * de traiter les actions.
	 *
	 * @param string $erreur
	 *     Le texte de l'erreur
	**/
	function err($erreur) {
		if ($erreur) {
			$this->err[] = $erreur;
		}
	}

	/**
	 * Remet à zéro les tableaux d'actions
	 */
	function clear() {
		$this->middle = array(
			'off' => array(),
			'lib' => array(),
			'on' => array(),
			'neutre' => array(),
		);
		$this->end = array();
		$this->done = array();
		$this->work = array();
	}

	/**
	 * Ajoute les actions à faire dans l'actionneur 
	 *
	 * @param array $todo
	 *     Tableau des actions à faire (identifiant de paquet => type d'action)
	**/
	function ajouter_actions($todo) {
		foreach ($todo as $id => $action) {
			$this->start[$id] = $action;
		}
		$this->ordonner_actions();
	}


	/**
	 * Ajoute une librairie à installer
	 *
	 * Ajoute l'action de télécharger une librairie, si la libraire
	 * n'est pas déjà présente et si le répertoire de librairie est
	 * écrivable.
	 *
	 * @param string $nom Nom de la librairie
	 * @param string $source URL pour obtenir la librairie
	 */
	function add_lib($nom, $source) {
		if (!$this->decideur->est_presente_lib($nom)) {
			if (is_writable(_DIR_LIB)) {
				$this->middle['lib'][$nom] = array(
					'todo'=>'getlib',
					'n'=>$nom,
					'p'=>$nom,
					'v'=>$source,
					's'=>$source,
				);
			} else {
				// erreur : impossible d'ecrire dans _DIR_LIB !
				// TODO : message et retour d'erreur a gerer...
				return false;
			}
		}
		return true;
	}

	/**
	 * Ordonne les actions demandées
	 *
	 * La fonction définie quelles sont les actions graduellement réalisables.
	 * Si un plugin A dépend de B qui dépend de C
	 * - pour tout ce qui est à installer : ordre des dependances (d'abord C, puis B, puis A)
	 * - pour tout ce qui est à désinstaller : ordre inverse des dependances. (d'abord A, puis B, puis C)
	 *
	 * On commence donc par séparer
	 * - ce qui est à désinstaller,
	 * - ce qui est à installer,
	 * - les actions neutres (get, up sur non actif, kill)
	 *
	 * Dans les traitements, on commencera par faire
	 * - ce qui est à désinstaller (il est possible que certains plugins
	 * nécessitent la désinstallation d'autres présents - tel que : 1 seul
	 * service d'envoi de mail)
	 * - puis ce qui est a installer (à commencer par les librairies, puis paquets),
	 * - puis les actions neutres
	 */
	function ordonner_actions() {
		// nettoyer le terrain
		$this->clear();

		foreach ($this->start as $id=>$action) {
			$i = $this->decideur->infos_courtes_id($id);
			$i = $i['i'][$id];
			switch ($action) {
				case 'getlib':
					// le plugin en ayant besoin le fera
					// comme un grand...
					break;
				case 'geton':
				case 'on':
					$this->on($i, $action);
					break;
				case 'up':
					// si le plugin est actif
					if ($i['a'] == 'oui') { 
						$this->on($i, $action);
					} else {
						$this->neutre($i, $action);
					}
					break;
				case 'upon':
					$this->on($i, $action);
					break;
				case 'off':
				case 'stop':
					$this->off($i, $action);
					break;
				case 'get':
				case 'kill':
					$this->neutre($i, $action);
					break;
			}
		}

		// c'est termine, on passe tout dans la fin...
		foreach ($this->middle as $acts) {
			$this->end = array_merge($this->end, $acts);
		}

		// si on a vu une desactivation de SVP
		// on le met comme derniere action...
		// sinon on ne pourrait pas faire les suivantes !
		if ($this->svp_off) {
			$this->log("SVP a desactiver a la fin.");
			foreach ($this->end as $c => $info) {
				if ($info['p'] == 'SVP') {
					unset($this->end[$c]);
					$this->end[] = $info;
					break;
				}
			}
		}
	
		$this->log("------------");
		#$this->log("Fin du tri :");
		#$this->log($this->end);
	}


	/**
	 * Ajoute un paquet à activer 
	 *
	 * À chaque fois qu'un nouveau paquet arrive ici, on le compare
	 * avec ceux déjà présents pour savoir si on doit le traiter avant
	 * ou après un des paquets à activer déjà présent.
	 *
	 * Si le paquet est une dépendance d'un autre plugin, il faut le mettre
	 * avant (pour l'activer avant celui qui en dépend).
	 *
	 * Si le paquet demande une librairie, celle-ci est ajoutée (les
	 * librairies seront téléchargées avant l'activation des plugins,
	 * le plugin aura donc sa librairie lorsqu'il sera activé)
	 *
	 * 
	 * @param array $info
	 *     Description du paquet
	 * @param string $action
	 *     Action à réaliser (on, upon)
	 * @return void
	**/
	function on($info, $action) {
		$info['todo'] = $action;
		$p = $info['p'];
		$this->log("ON: $p $action");

		// si dependance, il faut le mettre avant !
		$in = $out = $deps = $deps_all = array();
		// raz des cles pour avoir les memes que $out (utile reellement ?)
		$this->middle['on'] = array_values($this->middle['on']);
		// ajout des dependance
		foreach ($info['dn'] as $dep) {
			$in[]  = $dep['nom'];
		}
		// ajout des librairies
		foreach ($info['dl'] as $lib) {
			// il faudrait gerer un retour d'erreur eventuel !
			$this->add_lib($lib['nom'], $lib['lien']);
		}

		// on recupere : tous les prefix de plugin a activer (out)
		// ie. ce plugin peut dependre d'un de ceux la
		//
		// ainsi que les dependences de ces plugins (deps)
		// ie. ces plugins peuvent dependre de ce nouveau a activer.
		foreach ($this->middle['on'] as $inf) {
			$out[] = $inf['p'];
			foreach ($inf['dn'] as $dep) {
				$deps[$inf['p']][] = $dep['nom'];
				$deps_all[] = $dep['nom'];
			}
		}


		if (!$in) {

			// pas de dependance, on le met en premier !
			$this->log("- placer $p tout en haut");
			array_unshift($this->middle['on'], $info);

		} else {

			// intersection = dependance presente aussi
			// on place notre action juste apres la derniere dependance
			if ($diff = array_intersect($in, $out)) {
				$key = array();
				foreach($diff as $d) {$key[] = array_search($d, $out);}
				$key = max($key);
				$this->log("- placer $p apres " . $this->middle['on'][$key]['p']);
				if ($key == count($this->middle['on'])) {
					$this->middle['on'][] = $info;
				} else {
					array_splice($this->middle['on'], $key+1, 0, array($info));
				}

			// intersection = plugin dependant de celui-ci
			// on place notre plugin juste avant la premiere dependance a lui trouvee
			} elseif (in_array($p, $deps_all)) {
				foreach ($deps as $prefix=>$dep) {
					if (in_array($p, $dep)) {
						$key = array_search($prefix, $out);
						$this->log("- placer $p avant $prefix qui en depend ($key)");
						if ($key == 0) {
							array_unshift($this->middle['on'], $info);
						} else {
							array_splice($this->middle['on'], $key, 0, array($info));
						}
						break;
					}
				}

			// rien de particulier, il a des dependances mais les plugins
			// ne sont pas encore la ou les dependances sont deja actives
			// donc on le place tout en bas
			} else {
				$this->log("- placer $p tout en bas");
				$this->middle['on'][] = $info;
			}
		}
		unset($diff, $in, $out);
	}


	/**
	 * Ajoute un paquet avec une action neutre 
	 *
	 * Ces actions seront traitées en dernier, et peu importe leur
	 * ordre car elles n'entrent pas en conflit avec des dépendances.
	 * 
	 * @param array $info
	 *     Description du paquet
	 * @param string $action
	 *     Action à réaliser (kill, get, up (sur plugin inactif))
	 * @return void
	**/
	function neutre($info, $action) {
		$info['todo'] = $action;
		$this->log("NEUTRE:  $info[p] $action");
		$this->middle['neutre'][] = $info;
	}

	/**
	 * Ajoute un paquet à désactiver
	 *
	 * Ces actions seront traitées en premier.
	 *
	 * À chaque fois qu'un nouveau paquet arrive ici, on le compare
	 * avec ceux déjà présents pour savoir si on doit le traiter avant
	 * ou après un des paquets à désactiver déjà présent.
	 *
	 * Si le paquet est une dépendance d'un autre plugin, il faut le mettre
	 * après (pour désactiver avant celui qui en dépend).
	 * 
	 * @param array $info
	 *     Description du paquet
	 * @param string $action
	 *     Action à réaliser (kill, get, up (sur plugin inactif))
	 * @return void
	**/
	function off($info, $action) {
		$info['todo'] = $action;
		$p = $info['p'];
		$this->log("OFF: $p $action");

		// signaler la desactivation de SVP
		if ($p == 'SVP') {
			$this->svp_off = true;
		}
		
		// si dependance, il faut le mettre avant !
		$in = $out = array();
		// raz des cles pour avoir les memes que $out (utile reellement ?)
		$this->middle['off'] = array_values($this->middle['off']);
		foreach ($info['dn'] as $dep) {
			$in[]  = $dep['nom'];
		}
		foreach ($this->middle['off'] as $inf) 	{
			$out[] = $inf['p'];
		}

		if (!$in) {
			// ce plugin n'a pas de dependance, on le met en dernier !
			$this->log("- placer $p tout en bas");
			$this->middle['off'][] = $info;
		} else {
			// ce plugin a des dependances,
			// on le desactive juste avant elles.
			
			// intersection = dependance presente aussi
			// on place notre action juste avant la premiere dependance
			if ($diff = array_intersect($in, $out)) {
				$key = array();
				foreach($diff as $d) {$key[] = array_search($d, $out);}
				$key = min($key);
				$this->log("- placer $p avant " . $this->middle['off'][$key]['p']);
				array_splice($this->middle['off'], $key, 0, array($info));
			} else {
				// aucune des dependances n'est a desactiver
				// (du moins à ce tour ci),
				// on le met en premier !
				$this->log("- placer $p tout en haut");
				array_unshift($this->middle['off'], $info); // etait ->middle['on'] ?? ...
			}
		}
		unset($diff, $in, $out);
	}


	/**
	 * Retourne un bilan, texte HTML, des actions qui ont été faites
	 *
	 * Si c'est un affichage du bilan de fin, et qu'il reste des actions
	 * à faire, un lien est proposé pour faire supprimer ces actions restantes
	 * et le verrou qui va avec.
	 * 
	 * @param bool $fin
	 *     Est-ce un affichage intermédiaire (false) ou le tout dernier (true).
	 * @return string
	 *     Bilan des actions au format HTML
	**/
	function presenter_actions($fin = false) {
		$affiche = "";

		include_spip('inc/filtres_boites');
		
		if (count($this->err)) {
			$erreurs = "<ul>";
			foreach ($this->err as $i) {
				$erreurs .= "\t<li class='erreur'>" . $i . "</li>\n";
			}
			$erreurs .= "</ul>"; 
			$affiche .= boite_ouvrir(_T('svp:actions_en_erreur'), 'error') . $erreurs . boite_fermer();
		}

		if (count($this->done)) {
			$oks = true;
			$done = "<ul>";
			foreach ($this->done as $i) {
				$ok = ($i['done'] ? true : false);
				$oks = &$ok;
				$ok_texte = $ok ? 'ok' : 'fail';
				$cle_t = 'svp:message_action_finale_' . $i['todo'] . '_' . $ok_texte;
				$texte = _T($cle_t, array(
					'plugin' => $i['n'],
					'version' => denormaliser_version($i['v']),
					'version_maj' => denormaliser_version($i['maj'])));
				if (is_string($i['done'])) {
					$texte .= " <span class='$ok_texte'>$i[done]</span>";
				}
				$done .= "\t<li class='$ok_texte'>$texte</li>\n";
			}
			$done .= "</ul>";
			$affiche .= boite_ouvrir(_T('svp:actions_realises'), ($oks ? 'success' : 'notice')) . $done . boite_fermer();
		}

		if (count($this->end)) {
			$todo = "<ul>";
			foreach ($this->end as $i) {
				$todo .= "\t<li>"._T('svp:message_action_'.$i['todo'],array(
					'plugin'=>$i['n'],
					'version'=>denormaliser_version($i['v']),
					'version_maj'=>denormaliser_version($i['maj'])))."</li>\n";
			}
			$todo .= "</ul>\n";
			$titre = ($fin ? _T('svp:actions_non_traitees') : _T('svp:actions_a_faire'));

			// s'il reste des actions à faire alors que c'est la fin qui est affichée,
			// on met un lien pour vider. C'est un cas anormal qui peut surgir :
			// - en cas d'erreur sur une des actions bloquant l'espace privé
			// - en cas d'appel d'admin_plugins concurrent par le même admin ou 2 admins...
			if ($fin) {
				include_spip('inc/filtres');
				if ($this->lock['time']) {
					$time = $this->lock['time'];
				} else {
					$time = time();
				}
				$date = date('Y-m-d H:i:s', $time);
				$todo .= "<br />\n";
				$todo .= "<p class='error'>" . _T('svp:erreur_actions_non_traitees', array(
					'auteur' => Sql::getfetsel('nom', 'spip_auteurs', 'id_auteur=' . Sql::quote($this->lock['id_auteur'])),
					'date' => affdate_heure($date)
				)) . "</p>\n";
				$todo .= "<a href='" . parametre_url(self(), 'nettoyer_actions', '1'). "'>" . _T('svp:nettoyer_actions') . "</a>\n";
			}
			$affiche .= boite_ouvrir($titre, 'notice') . $todo . boite_fermer();
		}

		if ($affiche) {
			include_spip('inc/filtres');
			$affiche = wrap($affiche, "<div class='svp_retour'>");
		}
		
		return $affiche;
	}

	/**
	 * Teste l'existance d'un verrou par un auteur ?
	 *
	 * Si un id_auteur est transmis, teste que c'est cet auteur
	 * précis qui a posé le verrou.
	 *
	 * @see Actionneur::verouiller()
	 * 
	 * @param int|string $id_auteur
	 *     Identifiant de l'auteur, ou vide
	 * @return bool
	 *     true si un verrou est là, false sinon
	**/
	function est_verrouille($id_auteur = '') {
		if ($id_auteur == '') {
			return ($this->lock['id_auteur'] ? true : false);
		}
		return ($this->lock['id_auteur'] == $id_auteur);
	}

	/**
	 * Pose un verrou
	 *
	 * Un verrou permet de garentir qu'une seule exécution d'actions
	 * est lancé à la fois, ce qui évite que deux administrateurs
	 * puissent demander en même temps des actions qui pourraient
	 * s'entrechoquer.
	 *
	 * Le verrou est signé par l'id_auteur de l'auteur actuellement identifié.
	 *
	 * Le verrou sera sauvegardé en fichier avec la liste des actions
	 * 
	 * @see Actionneur::sauver_actions()
	**/
	function verrouiller() {
		$this->lock = array(
			'id_auteur' => $GLOBALS['visiteur_session']['id_auteur'],
			'time' => time(),
		);
	}

	/**
	 * Enlève le verrou
	**/
	function deverrouiller() {
		$this->lock = array(
			'id_auteur' => 0,
			'time' => '',
		);
	}

	/**
	 * Sauvegarde en fichier cache la liste des actions et le verrou
	 *
	 * Crée un tableau contenant les informations principales qui permettront
	 * de retrouver ce qui est à faire comme action, ce qui a été fait,
	 * les erreurs générées, et le verrouillage.
	 *
	 * Le cache peut être lu avec la méthode get_actions()
	 * 
	 * @see Actionneur::get_actions()
	**/
	function sauver_actions() {
		$contenu = serialize(array(
			'todo' => $this->end,
			'done' => $this->done,
			'work' => $this->work,
			'err'  => $this->err,
			'lock' => $this->lock,
		));
		ecrire_fichier(_DIR_TMP . 'stp_actions.txt', $contenu);
	}

	/**
	 * Lit le fichier cache de la liste des actions et verrou
	 *
	 * Restaure les informations contenues dans le fichier de cache
	 * et écrites avec la méthode sauver_actions().
	 * 
	 * @see Actionneur::sauver_actions()
	**/
	function get_actions() {
		lire_fichier(_DIR_TMP . 'stp_actions.txt', $contenu);
		$infos = unserialize($contenu);
		$this->end  = $infos['todo'];
		$this->work = $infos['work'];
		$this->done = $infos['done'];
		$this->err  = $infos['err'];
		$this->lock = $infos['lock'];
	}

	/**
	 * Nettoyage des actions et verrou
	 *
	 * Remet tout à zéro pour pouvoir repartir d'un bon pied.
	**/
	function nettoyer_actions() {
		$this->todo = array();
		$this->done = array();
		$this->work = array();
		$this->err  = array();
		$this->deverrouiller();
		$this->sauver_actions();
	}

	/**
	 * Effectue une des actions qui reste à faire.
	 *
	 * Dépile une des actions à faire s'il n'y en a pas en cours
	 * au moment de l'appel et traite cette action
	 *
	 * @see Actionneur::do_action()
	 * @return bool|array
	 *     False si aucune action à faire,
	 *     sinon tableau de description courte du paquet + index 'todo' indiquant l'action
	**/
	function one_action() {
		// s'il reste des actions, on en prend une, et on la fait
		// de meme si une action est en cours mais pas terminee (timeout)
		// on tente de la refaire...
		if (count($this->end) OR $this->work) {
			// on verrouille avec l'auteur en cours pour
			// que seul lui puisse effectuer des actions a ce moment la
			if (!$this->est_verrouille()) {
				$this->verrouiller();
			}
			// si ce n'est pas verrouille par l'auteur en cours...
			// ce n'est pas normal, donc on quitte sans rien faire.
			elseif (!$this->est_verrouille($GLOBALS['visiteur_session']['id_auteur'])) {
				return false;
			}

			// si pas d'action en cours
			if (!$this->work) {
				// on prend une des actions en attente
				$this->work = array_shift($this->end);
			}
			$action = $this->work;
			$this->sauver_actions();
			// effectue l'action dans work
			$this->do_action();

			// si la liste des actions en attente est maintenant vide
			// on deverrouille aussitot.
			if (!count($this->end)) {
				$this->deverrouiller();
				$this->sauver_actions();
			}
			return $action;
		} else {
			// on ne devrait normalement plus tomber sur un cas de verrouillage ici
			// mais sait-on jamais. Tester ne couter rien :)
			if ($this->est_verrouille()) {
				$this->deverrouiller();
				$this->sauver_actions();
			}
		}
		return false;
	}

	/**
	 * Effectue l'action en attente.
	 *
	 * Appelle une methode do_{todo} de l'Actionneur où todo
	 * est le type d'action à faire.
	 *
	 * Place dans la clé 'done' de description courte du paquet
	 * le résultat de l'action (un booléen indiquant si elle s'est bien
	 * déroulée).
	**/
	function do_action() {
		if ($do = $this->work) {
			$todo = 'do_' . $do['todo'];
			lire_metas(); // avoir les metas a jour
			$this->log("Faire $todo avec $do[n]");
			$do['done'] = $this->$todo($do);
			$this->done[] = $do;
			$this->work = array();
			$this->sauver_actions();
		}
	}


	/**
	 * Attraper et activer un paquet
	 *
	 * @param array $info
	 *     Description courte du paquet
	 * @return bool
	 *     false si erreur, true sinon.
	 */
	function do_geton($info) {
		if (!$this->tester_repertoire_plugins_auto()) {
			return false;
		}
		$i = Sql::fetsel('*','spip_paquets','id_paquet='.Sql::quote($info['i']));
		if ($dirs = $this->get_paquet_id($i)) {
			$this->activer_plugin_dossier($dirs['dossier'], $i);
			return true;
		}
		
		$this->log("GetOn : Erreur de chargement du paquet " .$info['n']);
		return false;
	}

	/**
	 * Activer un paquet
	 *
	 * Soit il est là... soit il est à télécharger...
	 *
	 * @param array $info
	 *     Description courte du paquet
	 * @return bool
	 *     false si erreur, true sinon.
	 */
	function do_on($info) {
		$i = Sql::fetsel('*','spip_paquets','id_paquet='.Sql::quote($info['i']));
		// à télécharger ?
		if ($i['id_zone'] > 0) {
			return $this->do_geton($info);
		}
		
		// a activer uniquement
		// il faudra prendre en compte les autres _DIR_xx
		if (in_array($i['constante'], array('_DIR_PLUGINS','_DIR_PLUGINS_SUPPL'))) {
			$dossier = rtrim($i['src_archive'], '/');
			$this->activer_plugin_dossier($dossier, $i, $i['constante']);
			return true;
		}
		
		return false;
	}


	/**
	 * Mettre à jour un paquet
	 *
	 * @param array $info
	 *     Description courte du paquet
	 * @return bool|array
	 *     false si erreur,
	 *     description courte du nouveau plugin sinon.
	 */
	function do_up($info) {
		// ecriture du nouveau
		// suppression de l'ancien (si dans auto, et pas au meme endroit)
		// OU suppression des anciens fichiers
		if (!$this->tester_repertoire_plugins_auto()) {
			return false;
		}

		// $i est le paquet a mettre à jour (donc present)
		// $maj est le paquet a telecharger qui est a jour (donc distant)
		
		$i = Sql::fetsel('*','spip_paquets','id_paquet='.Sql::quote($info['i']));

		// on cherche la mise a jour...
		// c'est a dire le paquet source que l'on met a jour.
		if ($maj = Sql::fetsel('pa.*',
			array('spip_paquets AS pa', 'spip_plugins AS pl'),
			array(
			'pl.prefixe='.Sql::quote($info['p']),
			'pa.version='.Sql::quote($info['maj']),
			'pa.id_plugin = pl.id_plugin',
			'pa.id_depot>'.Sql::quote(0)),
			'', 'pa.etatnum DESC', '0,1')) {

			if ($dirs = $this->get_paquet_id($maj)) {
				// Si le plugin a jour n'est pas dans le meme dossier que l'ancien...
				// il faut :
				// - activer le plugin sur son nouvel emplacement (uniquement si l'ancien est actif)...
				// - supprimer l'ancien (si faisable)
				if (($dirs['dossier'] . '/') != $i['src_archive']) {
					if ($i['actif'] == 'oui') {
						$this->activer_plugin_dossier($dirs['dossier'], $maj);
					}

					// l'ancien repertoire a supprimer pouvait etre auto/X
					// alors que le nouveau est auto/X/Y ...
					// il faut prendre en compte ce cas particulier et ne pas ecraser auto/X !
					if (substr($i['src_archive'], 0, 5) == 'auto/' and (false === strpos($dirs['dossier'], $i['src_archive']))) {
						if (supprimer_repertoire( constant($i['constante']) . $i['src_archive']) ) {
							Sql::delete('spip_paquets', 'id_paquet=' . Sql::quote($info['i']));
						}
					}
				}

				$this->ajouter_plugin_interessants_meta($dirs['dossier']);
				return $dirs;
			}
		}
		return false;
	}


	/**
	 * Mettre à jour et activer un paquet
	 *
	 * @param array $info
	 *     Description courte du paquet
	 * @return bool
	 *     false si erreur, true sinon
	 */
	function do_upon($info) {
		$i = Sql::fetsel('*', 'spip_paquets', 'id_paquet='.Sql::quote($info['i']));
		if ($dirs = $this->do_up($info)) {
			$this->activer_plugin_dossier($dirs['dossier'], $i, $i['constante']);
			return true;
		}
		return false;
	}


	/**
	 * Désactiver un paquet
	 *
	 * @param array $info
	 *     Description courte du paquet
	 * @return bool
	 *     false si erreur, true sinon
	 */
	function do_off($info) {
		$i = Sql::fetsel('*','spip_paquets','id_paquet='.Sql::quote($info['i']));
		// il faudra prendre en compte les autres _DIR_xx
		if (in_array($i['constante'], array('_DIR_PLUGINS','_DIR_PLUGINS_SUPPL'))) {
			include_spip('inc/plugin');
			$dossier = rtrim($i['src_archive'], '/');
			ecrire_plugin_actifs(array(rtrim($dossier,'/')), false, 'enleve');
			Sql::updateq('spip_paquets', array('actif'=>'non', 'installe'=>'non'), 'id_paquet='.Sql::quote($info['i']));
			$this->actualiser_plugin_interessants();
			// ce retour est un rien faux...
			// il faudrait que la fonction ecrire_plugin_actifs()
			// retourne au moins d'eventuels message d'erreur !
			return true;
		}
		return false;
	}


	/**
	 * Désinstaller un paquet
	 *
	 * @param array $info
	 *     Description courte du paquet
	 * @return bool
	 *     false si erreur, true sinon
	 */
	function do_stop($info) {
		$i = Sql::fetsel('*','spip_paquets','id_paquet=' . Sql::quote($info['i']));
		// il faudra prendre en compte les autres _DIR_xx
		if (in_array($i['constante'], array('_DIR_PLUGINS','_DIR_PLUGINS_SUPPL'))) {
			include_spip('inc/plugin');
			$dossier = rtrim($i['src_archive'],'/');

			$installer_plugins = charger_fonction('installer', 'plugins');
			// retourne :
			// - false : pas de procedure d'install/desinstalle
			// - true : operation deja faite
			// - tableau : operation faite ce tour ci.
			$infos = $installer_plugins($dossier, 'uninstall');
			if (is_bool($infos) OR !$infos['install_test'][0]) {
				include_spip('inc/plugin');
				ecrire_plugin_actifs(array($dossier), false, 'enleve');
				Sql::updateq('spip_paquets', array('actif'=>'non', 'installe'=>'non'), 'id_paquet='.SQl::quote($info['i']));
				return true;
			} else {
				// echec
				$this->log("Échec de la désinstallation de " . $i['src_archive']);
			}
		}
		$this->actualiser_plugin_interessants();
		return false;
	}


	/**
	 * Effacer les fichiers d'un paquet
	 *
	 * @param array $info
	 *     Description courte du paquet
	 * @return bool
	 *     false si erreur, true sinon
	 */
	function do_kill($info) {
		// on reverifie que c'est bien un plugin auto !
		// il faudrait aussi faire tres attention sur un site mutualise
		// cette option est encore plus delicate que les autres...
		$i = Sql::fetsel('*','spip_paquets','id_paquet='.Sql::quote($info['i']));

		if (in_array($i['constante'], array('_DIR_PLUGINS','_DIR_PLUGINS_SUPPL'))
		and substr($i['src_archive'], 0, 5) == 'auto/') {
			
			$dir = constant($i['constante']) . $i['src_archive'];
			if (supprimer_repertoire($dir)) {
				$id_plugin = Sql:getfetsel('id_plugin', 'spip_paquets', 'id_paquet=' . Sql::quote($info['i']));

				// on supprime le paquet
				Sql::delete('spip_paquets', 'id_paquet=' . Sql::quote($info['i']));

				// ainsi que le plugin s'il n'est plus utilise
				$utilise = Sql::allfetsel(
					'pl.id_plugin',
					array('spip_paquets AS pa', 'spip_plugins AS pl'),
					array('pa.id_plugin = pl.id_plugin', 'pa.id_plugin=' . Sql::quote($id_plugin)));
				if (!$utilise) {
					Sql::delete('spip_plugins', 'id_plugin=' . Sql::quote($id_plugin));
				} else {
					// on met a jour d'eventuels obsoletes qui ne le sont plus maintenant
					// ie si on supprime une version superieure à une autre qui existe en local...
					include_spip('inc/svp_depoter_local');
					svp_corriger_obsolete_paquets(array($id_plugin));
				}

				// on tente un nettoyage jusqu'a la racine de auto/
				// si la suppression concerne une profondeur d'au moins 2
				// et que les repertoires sont vides
				$chemins = explode('/', $i['src_archive']); // auto / prefixe / version
				// le premier c'est auto
				array_shift($chemins);
				// le dernier est deja fait...
				array_pop($chemins);
				// entre les deux...
				while (count($chemins)) {
					$vide = true;
					$dir = constant($i['constante']) . 'auto/' . implode('/', $chemins);
					$fichiers = scandir($dir);
					if ($fichiers) {
						foreach ($fichiers as $f) {
							if ($f[0] != '.') {
								$vide = false;
								break;
							}
						}
					}
					// on tente de supprimer si c'est effectivement vide.
					if ($vide and !supprimer_repertoire($dir)) {
						break;
					}
					array_pop($chemins);
				}
				return true;
			}
		}

		return false;
	}


	/**
	 * Installer une librairie
	 *
	 * @param array $info
	 *     Description courte du paquet (une librairie ici)
	 * @return bool
	 *     false si erreur, true sinon
	 */
	function do_getlib($info) {
		if (!defined('_DIR_LIB') or !_DIR_LIB) {
			$this->err(_T('svp:erreur_dir_dib_indefini'));
			$this->log("/!\ Pas de _DIR_LIB defini !");
			return false;
		}
		if (!is_writable(_DIR_LIB)) {
			$this->err(_T('svp:erreur_dir_dib_ecriture', array('dir' => _DIR_LIB )));
			$this->log("/!\ Ne peut pas écrire dans _DIR_LIB !");
			return false;
		}
		if(!autoriser('plugins_ajouter')){
			$this->err(_T('svp:erreur_auth_plugins_ajouter_lib'));
			$this->log("/!\ Pas autorisé à ajouter des libs !");
			return false;
		}

		$this->log("Recuperer la librairie : " . $info['n'] );

		// on recupere la mise a jour...
		include_spip('action/teleporter');
		$teleporter_composant = charger_fonction('teleporter_composant', 'action');
		$ok = $teleporter_composant('http', $info['v'], _DIR_LIB . $info['n']);
		if ($ok === true) {
			return true;
		}
		
		$this->err($ok);
		$this->log("Téléporteur en erreur : " . $ok);
		return false;
	}


	/**
	 * Télécharger un paquet
	 *
	 * @param array $info
	 *     Description courte du paquet
	 * @return bool
	 *     false si erreur, true sinon
	 */
	function do_get($info) {
		if (!$this->tester_repertoire_plugins_auto()) {
			return false;
		}

		$i = Sql::fetsel('*', 'spip_paquets', 'id_paquet=' . Sql::quote($info['i']));
	
		if ($dirs = $this->get_paquet_id($info['i'])) {
			$this->ajouter_plugin_interessants_meta($dirs['dossier']);
			return true;
		}

		return false;
	}


	/**
	 * Lancer l'installation d'un paquet
	 *
	 * @param array $info
	 *     Description courte du paquet
	 * @return bool
	 *     false si erreur, true sinon
	 */
	function do_install($info) {
		return $this->installer_plugin($info);
	}


	/**
	 * Activer un plugin 
	 *
	 * @param string $dossier
	 *     Chemin du répertoire du plugin
	 * @param array $i
	 *     Description en BDD du paquet - row SQL (tableau clé => valeur)
	 * @param string $constante
	 *     Constante indiquant le chemin de base du plugin (_DIR_PLUGINS, _DIR_PLUGINS_SUPPL, _DIR_PLUGINS_DIST)
	 * @return void
	**/
	function activer_plugin_dossier($dossier, $i, $constante='_DIR_PLUGINS') {
		include_spip('inc/plugin');
		$this->log("Demande d'activation de : " . $dossier);
		
		//il faut absolument que tous les fichiers de cache
		// soient inclus avant modification, sinon un appel ulterieur risquerait
		// de charger des fichiers deja charges par un autre !
		// C'est surtout le ficher de fonction le probleme (options et pipelines
		// sont normalement deja charges).
		if (@is_readable(_CACHE_PLUGINS_OPT)) {include_once(_CACHE_PLUGINS_OPT);}
		if (@is_readable(_CACHE_PLUGINS_FCT)) {include_once(_CACHE_PLUGINS_FCT);}
		if (@is_readable(_CACHE_PIPELINES))   {include_once(_CACHE_PIPELINES);}

		include_spip('inc/plugin');
		ecrire_plugin_actifs(array($dossier), false, 'ajoute');
		$installe = $i['version_base'] ? 'oui' : 'non';
		if ($installe == 'oui') {
			if(!$i['constante'])
				$i['constante'] = '_DIR_PLUGINS';
			// installer le plugin au prochain tour
			$new_action = array_merge($this->work, array(
				'todo'=>'install',
				'dossier'=>rtrim($dossier,'/'),
				'constante'=>$i['constante'],
				'v'=>$i['version'], // pas forcement la meme version qu'avant lors d'une mise a jour.
			));
			array_unshift($this->end, $new_action);
			$this->log("Demande d'installation de $dossier");
			#$this->installer_plugin($dossier);
		}

		$this->ajouter_plugin_interessants_meta($dossier);
		$this->actualiser_plugin_interessants();
	}


	/**
	 * Actualiser les plugins intéressants
	 *
	 * Décrémente chaque score de plugin présent dans la méta
	 * 'plugins_interessants' et signifiant que ces plugins
	 * ont été utilisés récemment.
	 * 
	 * Les plugins atteignant un score de zéro sont évacués ce la liste.
	 */
	function actualiser_plugin_interessants() {
		// Chaque fois que l'on valide des plugins,
		// on memorise la liste de ces plugins comme etant "interessants",
		// avec un score initial, qui sera decremente a chaque tour :
		// ainsi un plugin active pourra reter visible a l'ecran,
		// jusqu'a ce qu'il tombe dans l'oubli.
		$plugins_interessants = @unserialize($GLOBALS['meta']['plugins_interessants']);
		if (!is_array($plugins_interessants)) {
			$plugins_interessants = array();
		}
		
		$dossiers = array();
		$dossiers_old = array();
		foreach($plugins_interessants as $p => $score) {
			if (--$score > 0) {
				$plugins_interessants[$p] = $score;
				$dossiers[$p.'/'] = true;
			} else {
				unset($plugins_interessants[$p]);
				$dossiers_old[$p.'/'] = true;
			}
		}

		// enlever les anciens
		if ($dossiers_old) {
			// ATTENTION, il faudra prendre en compte les _DIR_xx
			Sql::updateq('spip_paquets', array('recent'=>0), Sql::in('src_archive', array_keys($dossiers_old)));
		}

		$plugs = Sql::allfetsel('src_archive','spip_paquets', 'actif='.Sql::quote('oui'));
		$plugs = array_map('array_shift', $plugs);
		foreach ($plugs as $dossier) {
			$dossiers[$dossier] = true;
			$plugins_interessants[ rtrim($dossier, '/') ] = 30; // score initial
		}

		$plugs = Sql::updateq('spip_paquets', array('recent'=>1), Sql::in('src_archive', array_keys($dossiers)));
		ecrire_meta('plugins_interessants', serialize($plugins_interessants));
	}


	/**
	 * Ajoute un plugin dans les plugins intéressants
	 *
	 * Initialise à 30 le score du plugin indiqué par le chemin transmis,
	 * dans la liste des plugins intéressants.
	 *
	 * @param string $dir
	 *     Chemin du répertoire du plugin
	 */
	function ajouter_plugin_interessants_meta($dir) {
		$plugins_interessants = @unserialize($GLOBALS['meta']['plugins_interessants']);
		if (!is_array($plugins_interessants)) {
			$plugins_interessants = array();
		}
		$plugins_interessants[$dir] = 30;
		ecrire_meta('plugins_interessants', serialize($plugins_interessants));
	}

	/**
	 * Lancer l'installation d'un plugin
	 *
	 * @param array $info
	 *     Description courte du paquet
	 * @return bool
	 *     false si erreur, true sinon
	 */
	function installer_plugin($info){
		// il faut info['dossier'] et info['constante'] pour installer
		if ($plug = $info['dossier']) {
			$installer_plugins = charger_fonction('installer', 'plugins');
			$infos = $installer_plugins($plug, 'install', $info['constante']);
			if ($infos) {
				// en absence d'erreur, on met a jour la liste des plugins installes...
				if (!is_array($infos) OR $infos['install_test'][0]) {
					$meta_plug_installes = @unserialize($GLOBALS['meta']['plugin_installes']);
					if (!$meta_plug_installes) {
						$meta_plug_installes=array();
					}
					$meta_plug_installes[] = $plug;
					ecrire_meta('plugin_installes',serialize($meta_plug_installes),'non');
				}

				if (!is_array($infos)) {
					// l'installation avait deja ete faite un autre jour
					return true; 
				} else {
					// l'installation est neuve
					list($ok, $trace) = $infos['install_test'];
					if ($ok) {
						return true;
					}
					// l'installation est en erreur
					$this->err(_T('svp:message_action_finale_install_fail',
						array('plugin' => $info['n'], 'version'=>denormaliser_version($info['v']))) . "<br />" . $trace);
				}
			}
		}
		return false;
	}


	/**
	 * Télécharge un paquet
	 * 
	 * Supprime les fichiers obsolètes (si présents)
	 *
	 * @param int|array $id_or_row
	 *     Identifiant du paquet ou description ligne SQL du paquet
	 * @return bool|array
	 *     False si erreur.
	 *     Tableau de 2 index sinon :
	 *     - dir : Chemin du paquet téléchargé depuis la racine
	 *     - dossier : Chemin du paquet téléchargé, depuis _DIR_PLUGINS
	 */
	function get_paquet_id($id_or_row) {
		// on peut passer direct le row sql...
		if (!is_array($id_or_row)) {
			$i = Sql::fetsel('*','spip_paquets','id_paquet='.Sql::quote($id_or_row));
		} else {
			$i = $id_or_row;
		}
		unset($id_or_row);

		if ($i['nom_archive'] and $i['id_depot']) {
			$this->log("Recuperer l'archive : " . $i['nom_archive'] );
			if ($adresse = Sql::getfetsel('url_archives', 'spip_depots', 'id_depot='.Sql::quote($i['id_depot']))) {
				$zip = $adresse . '/' . $i['nom_archive'];

				// destination : auto/prefixe/version (sinon auto/nom_archive/version)
				$prefixe = Sql::getfetsel('pl.prefixe',
					array('spip_paquets AS pa', 'spip_plugins AS pl'),
					array('pa.id_plugin = pl.id_plugin', 'pa.id_paquet=' . Sql::quote($i['id_paquet'])));

				// prefixe
				$base =  ($prefixe ? strtolower($prefixe) : substr($i['nom_archive'], 0, -4) ); // enlever .zip ...

				// prefixe/version
				$dest = $base . '/v' . denormaliser_version($i['version']);
				
				// si on tombe sur un auto/X ayant des fichiers (et pas uniquement des dossiers)
				// ou un dossier qui ne commence pas par 'v'
				// c'est que auto/X n'était pas chargé avec SVP
				// ce qui peut arriver lorsqu'on migre de SPIP 2.1 à 3.0
				// dans ce cas, on supprime auto X pour mettre notre nouveau paquet.
				$ecraser_base = false;
				if (is_dir(_DIR_PLUGINS_AUTO . $base)) {
					$base_files = scandir(_DIR_PLUGINS_AUTO . $base);
					if (is_array($base_files)) {
						$base_files = array_diff($base_files, array('.', '..'));
						foreach ($base_files as $f) {
							if (($f[0] != '.' and $f[0] != 'v') // commence pas par v
							OR ($f[0] != '.' and !is_dir(_DIR_PLUGINS_AUTO . $base . '/' . $f))) { // commence par v mais pas repertoire
								$ecraser_base = true;
								break;
							}
						}
					}
				}
				if ($ecraser_base) {
					supprimer_repertoire(_DIR_PLUGINS_AUTO . $base);
				}


				// on recupere la mise a jour...
				include_spip('action/teleporter');
				$teleporter_composant = charger_fonction('teleporter_composant', 'action');
				$ok = $teleporter_composant('http', $zip, _DIR_PLUGINS_AUTO . $dest);
				if ($ok === true) {
					return array(
						'dir'=> _DIR_PLUGINS_AUTO . $dest,
						'dossier' => 'auto/' . $dest, // c'est depuis _DIR_PLUGINS ... pas bien en dur...
					);
				}
				$this->err($ok);
				$this->log("Téléporteur en erreur : " . $ok);
			} else {
				$this->log("Aucune adresse pour le dépot " . $i['id_depot'] );
			}
		}
		return false;
	}


	/**
	 * Teste que le répertoire plugins auto existe et
	 * que l'on peut ecrire dedans !
	 *
	 * @return bool
	 *     True si on peut écrire dedans, false sinon
	**/
	function tester_repertoire_plugins_auto() {
		include_spip('inc/plugin'); // pour _DIR_PLUGINS_AUTO
		if (!defined('_DIR_PLUGINS_AUTO') or !_DIR_PLUGINS_AUTO) {
			$this->err(_T('svp:erreur_dir_plugins_auto_indefini'));
			$this->log("/!\ Pas de _DIR_PLUGINS_AUTO defini !");
			return false;
		}
		if (!is_writable(_DIR_PLUGINS_AUTO)) {
			$this->err(_T('svp:erreur_dir_plugins_auto_ecriture', array('dir'=>_DIR_PLUGINS_AUTO)));
			$this->log("/!\ Ne peut pas écrire dans _DIR_PLUGINS_AUTO !");
			return false;
		}
		return true;
	}


	/**
	 * Teste si le plugin SVP (celui-ci donc) a
	 * été désinstallé / désactivé dans les actions réalisées 
	 *
	 * @note
	 *     On ne peut tester sa désactivation que dans le hit où la désinstallation
	 *     est réalisée, puisque après, s'il a été désactivé, au prochain hit
	 *     on ne connaîtra plus ce fichier !
	 * 
	 * @return bool
	 *     true si SVP a été désactivé, false sinon
	**/
	function tester_si_svp_desactive() {
		foreach ($this->done as $d) {
			if ($d['p'] == 'SVP'
			AND $d['done'] == true
			AND in_array($d['todo'], array('off', 'stop'))) {
				return true;
			}
		}
		return false;
	}
	
}


/**
 * Gère le traitement des actions des formulaires utilisant l'Actionneur
 *
 * @param array $actions
 *     Liste des actions a faire (id_paquet => action)
 * @param array $retour
 *     Tableau de retour du CVT dans la partie traiter
 * @param string $redirect
 *     URL de retour
 * @return void
**/
function svp_actionner_traiter_actions_demandees($actions, &$retour,$redirect=null) {
		$actionneur = new Actionneur();
		$actionneur->ajouter_actions($actions);
		$actionneur->verrouiller();
		$actionneur->sauver_actions();
		
		$redirect = $redirect ? $redirect : generer_url_ecrire('admin_plugin');
		$retour['redirect'] = generer_url_action('actionner', 'redirect='.urlencode($redirect));
		set_request('_todo', '');
		$retour['message_ok'] = _T("svp:action_patienter");
}
?>
