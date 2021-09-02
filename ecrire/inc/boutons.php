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

/**
 * Gestion des boutons de l'interface privée
 *
 * @package SPIP\Core\Boutons
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Classe définissant un bouton dans la barre du haut de l'interface
 * privée ou dans un de ses sous menus
 */
class Bouton {
	/** @var string L'icone à mettre dans le bouton */
	public $icone;

	/** @var string Le nom de l'entrée d'i18n associé */
	public $libelle;

	/** @var null|string L'URL de la page (null => ?exec=nom) */
	public $url = null;

	/** @var null|string|array Arguments supplementaires de l'URL */
	public $urlArg = null;

	/** @var null|string URL du javascript */
	public $url2 = null;

	/** @var null|string Pour ouvrir dans une fenetre a part */
	public $target = null;

	/** @var null|mixed Sous-barre de boutons / onglets */
	public $sousmenu = null;

	/**
	 * Définit un bouton
	 *
	 * @param string $icone
	 *    L'icone à mettre dans le bouton
	 * @param string $libelle
	 *    Le nom de l'entrée i18n associé
	 * @param null|string $url
	 *    L'URL de la page
	 * @param null|string|array $urlArg
	 *    Arguments supplémentaires de l'URL
	 * @param null|string $url2
	 *    URL du javascript
	 * @param null|mixed $target
	 *    Pour ouvrir une fenêtre à part
	 */
	public function __construct($icone, $libelle, $url = null, $urlArg = null, $url2 = null, $target = null) {
		$this->icone = $icone;
		$this->libelle = $libelle;
		$this->url = $url;
		$this->urlArg = $urlArg;
		$this->url2 = $url2;
		$this->target = $target;
	}
}


/**
 * Définir la liste des onglets dans une page de l'interface privée.
 *
 * On passe la main au pipeline "ajouter_onglets".
 *
 * @see plugin_ongletbouton() qui crée la fonction `onglets_plugins()`
 * @pipeline_appel ajouter_onglets
 *
 * @param string $script
 * @return array
 */
function definir_barre_onglets($script) {

	$onglets = [];
	$liste_onglets = [];

	// ajouter les onglets issus des plugin via paquet.xml
	if (function_exists('onglets_plugins')) {
		$liste_onglets = onglets_plugins();
	}


	foreach ($liste_onglets as $id => $infos) {
		if (
			($parent = $infos['parent'])
			&& $parent == $script
			&& autoriser('onglet', "_$id")
		) {
			$onglets[$id] = new Bouton(
				isset($infos['icone']) ? find_in_theme($infos['icone']) : '',  // icone
				$infos['titre'],  // titre
				(isset($infos['action']) and $infos['action'])
					? generer_url_ecrire(
						$infos['action'],
						(isset($infos['parametres']) and $infos['parametres']) ? $infos['parametres'] : ''
					)
					: null
			);
		}
	}

	return pipeline('ajouter_onglets', ['data' => $onglets, 'args' => $script]);
}


/**
 *
 * Création de la barre d'onglets
 *
 * @uses definir_barre_onglets()
 * @uses onglet()
 * @uses debut_onglet()
 * @uses fin_onglet()
 *
 * @param string $rubrique
 * @param string $ongletCourant
 * @param string $class
 * @return string
 */
function barre_onglets($rubrique, $ongletCourant, $class = 'barre_onglet') {
	include_spip('inc/presentation');

	$res = '';

	foreach (definir_barre_onglets($rubrique) as $exec => $onglet) {
		$url = $onglet->url ? $onglet->url : generer_url_ecrire($exec);
		$res .= onglet(_T($onglet->libelle), $url, $exec, $ongletCourant, $onglet->icone);
	}

	return !$res ? '' : (debut_onglet($class) . $res . fin_onglet());
}
