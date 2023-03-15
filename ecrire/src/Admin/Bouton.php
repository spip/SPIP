<?php

namespace Spip\Admin;

/**
 * Classe définissant un bouton dans la barre du haut de l'interface
 * privée ou dans un de ses sous menus
 */
class Bouton {
	/** Sous-barre de boutons / onglets */
	public array $sousmenu = [];

	/** Position dans le menu */
	public int $position = 0;

	/** Entrée favorite (sa position dans les favoris) ? */
	public int $favori = 0;


	/**
	 * Définit un bouton
	 */
	public function __construct(
		/** L'icone à mettre dans le bouton */
		public string $icone,
		/** Le nom de l'entrée d'i18n associé */
		public string $libelle,
		/** L'URL de la page (null => ?exec=nom) */
		public ?string $url = null,
		/** Arguments supplémentaires de l'URL */
		public string|array|null $urlArg = null,
		/** URL du javascript */
		public ?string $url2 = null,
		/** Pour ouvrir une fenêtre à part */
		public ?string $target = null
	) {
	}
}
