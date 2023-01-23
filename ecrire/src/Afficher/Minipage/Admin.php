<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 * \***************************************************************************/

namespace Spip\Afficher\Minipage;


/**
 * Présentation des pages simplifiées d’admin pour envoyer un message à un utilisateur
 **/
class Admin extends Page {
	public const TYPE = 'admin';
	protected function setOptions(array $options) {
		$options['couleur_fond'] = '#222';
		$options['css_files'] = [
			find_in_theme('minipres.css')
		];

		$options['page_title'] = ($options['titre'] ?? '');

		return $options;
	}


	/**
	 * Retourne le début d'une page HTML minimale (de type installation ou erreur)
	 *
	 * @param array $options
	 * @return string
	 *    Code HTML
	 */
	public function installDebutPage($options = []) {

		$options = $this->setOptions($options);
		return parent::ouvreBody($options)
			. parent::ouvreCorps($options);
	}

	/**
	 * Retourne le fin d'une page HTML minimale (de type installation ou erreur)
	 *
	 * @param array $options
	 * @return string
	 *    Code HTML
	 */
	public function installFinPage($options = []) {

		$options = $this->setOptions($options);
		return parent::fermeCorps($options)
			. parent::fermeBody();
	}


	/**
	 * Retourne une page HTML contenant, dans une présentation minimale,
	 * le contenu transmis dans `$corps`.
	 *
	 * Appelée pour afficher un message d’erreur (l’utilisateur n’a pas
	 * accès à cette page par exemple).
	 *
	 * Lorsqu’aucun argument n’est transmis, un header 403 est renvoyé,
	 * ainsi qu’un message indiquant une interdiction d’accès.
	 *
	 * @param string $corps
	 *   Corps de la page
	 * @param array $options
	 * @return string
	 *   HTML de la page
	 * @see  ouvreBody()
	 *   string $titre : Titre à l'affichage (différent de $page_title)
	 *   int $status : status de la page
	 *   string $footer : pied de la box en remplacement du bouton retour par défaut
	 * @uses ouvreBody()
	 * @uses fermeBody()
	 *
	 */
	public function page($corps = '', $options = []) {

		$footer = '';

		$titre = $options['titre'] ?? '';
		if (!$titre) {
			if (empty($corps) and !isset($options['status'])) {
				$options['status'] = 403;
			}

			if (
				!$titre = _request('action')
				and !$titre = _request('exec')
				and !$titre = _request('page')
			) {
				$titre = '?';
			}

			$titre = spip_htmlspecialchars($titre);

			$titre = ($titre == 'install')
				? _T('avis_espace_interdit')
				: $titre . '&nbsp;: ' . _T('info_acces_interdit');

			$statut = $GLOBALS['visiteur_session']['statut'] ?? '';
			$nom = $GLOBALS['visiteur_session']['nom'] ?? '';

			if ($statut != '0minirezo') {
				$titre = _T('info_acces_interdit');
			}

			if ($statut and test_espace_prive()) {
				$footer = bouton_action(_T('public:accueil_site'), generer_url_ecrire('accueil'));
			}
			elseif (!empty($_COOKIE['spip_admin'])) {
				$footer = bouton_action(_T('public:lien_connecter'), generer_url_public('login'));
			}
			else {
				$footer = bouton_action(_T('public:accueil_site'), $GLOBALS['meta']['adresse_site'] ?? '');
			}

			$corps = "";
			spip_log($nom . " $titre " . $_SERVER['REQUEST_URI'], 'minipres');
		}

		$options['footer'] = $footer;
		$options['page_title'] = $titre;
		$options['titre'] = $titre;

		$options = $this->setOptions($options);

		$html = parent::page($corps, $options);

		if (!_AJAX) {
			return $html;
		} else {
			include_spip('inc/headers');
			include_spip('inc/actions');
			$url = self('&', true);
			foreach ($_POST as $v => $c) {
				$url = parametre_url($url, $v, $c, '&');
			}
			ajax_retour('<div>' . $titre . redirige_formulaire($url) . '</div>', false);
			return '';
		}
	}

}
