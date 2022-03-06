<?php

namespace Spip\Admin;

/**
 * Classe définissant un bouton dans la barre du haut de l'interface
 * privée ou dans un de ses sous menus
 */
class Bouton {
	/** L'icone à mettre dans le bouton */
	public string $icone;

	/** Le nom de l'entrée d'i18n associé */
	public string $libelle;

	/** @var null|string L'URL de la page (null => ?exec=nom) */
	public $url = null;

	/** @var null|string|array Arguments supplementaires de l'URL */
	public $urlArg = null;

	/** @var null|string URL du javascript */
	public $url2 = null;

	/** @var null|string Pour ouvrir dans une fenetre a part */
	public $target = null;

	/** Sous-barre de boutons / onglets */
	public array $sousmenu = [];

	/** Position dans le menu */
	public int $position = 0;

	/** Entrée favorite (sa position dans les favoris) ? */
	public int $favori = 0;


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

