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
 * Présentation des pages simplifiées
 **/
abstract class AbstractPage {
	public const TYPE = '';

	public function __construct() {
		include_fichiers_fonctions();
		include_spip('inc/headers');
		include_spip('inc/texte'); //inclue inc/lang et inc/filtres
		include_spip('inc/filtres_images_mini');
	}

	/**
	 * Retourne le début d'une page HTML minimale
	 *
	 * Le contenu de CSS minimales (reset.css, clear.css, minipage.css) est inséré
	 * dans une balise script inline (compactée si possible)
	 *
	 * @param array $options
	 *   string $lang : forcer la langue utilisateur
	 *   string $page_title : titre éventuel de la page (nom du site par défaut)
	 *   string $couleur_fond : pour la couleur dominante de la page (par défaut on reprend le réglage de la page de login)
	 *   bool $all_inline : inliner les CSS pour envoyer toute la page en 1 hit
	 *   string $doctype
	 *   string $charset
	 *   string $onload
	 *   array $css_files : ajouter des fichiers css
	 *   string $css : ajouter du CSS inline
	 *   string $head : contenu à ajouter à la fin <head> (pour inclusion de JS ou JS inline...)
	 * @return string
	 *    Code HTML
	 *
	 * @uses html_lang_attributes()
	 * @uses minifier() si le plugin compresseur est présent
	 * @uses url_absolue_css()
	 *
	 * @uses utiliser_langue_visiteur()
	 * @uses http_no_cache()
	 */
	protected function ouvreBody($options = []) {
		$h = null;
		$s = null;
		$l = null;
		if (empty($options['lang'])) {
			// on se limite sur une langue de $GLOBALS['meta']['langues_multilingue'] car on est dans le public
			utiliser_langue_visiteur($GLOBALS['meta']['langues_multilingue'] ?? null);
		} else {
			changer_langue($options['lang']);
		}
		http_no_cache();

		$page_title = ($options['page_title'] ?? $GLOBALS['meta']['nom_site']);
		$doctype = ($options['doctype'] ?? '<!DOCTYPE html>');
		$doctype = trim((string) $doctype) . "\n";
		$charset = ($options['charset'] ?? 'utf-8');
		$all_inline = ($options['all_inline'] ?? true);
		$onLoad = ($options['onLoad'] ?? '');
		if ($onLoad) {
			$onLoad = ' onload="' . attribut_html($onLoad) . '"';
		}

		# envoyer le charset
		if (!headers_sent()) {
			header('Content-Type: text/html; charset=' . $charset);
		}

		$css = '';

		if (function_exists('couleur_hex_to_hsl')) {
			$couleur_fond = empty($options['couleur_fond'])
				? lire_config('couleur_login', '#db1762')
				: $options['couleur_fond'];
			$h = couleur_hex_to_hsl($couleur_fond, 'h');
			$s = couleur_hex_to_hsl($couleur_fond, 's');
			$l = couleur_hex_to_hsl($couleur_fond, 'l');
		}

		$inline = ':root {'
			. "--minipage-color-theme--h: $h;"
			. "--minipage-color-theme--s: $s;"
			. "--minipage-color-theme--l: $l;}";
		$vars = file_get_contents(find_in_theme('minipage.vars.css'));
		$inline .= "\n" . trim($vars);
		if (function_exists('minifier')) {
			$inline = minifier($inline, 'css');
		}
		$files = [
			find_in_theme('reset.css'),
			find_in_theme('clear.css'),
			find_in_theme('minipage.css'),
		];
		if (!empty($options['css_files'])) {
			foreach ($options['css_files'] as $css_file) {
				$files[] = $css_file;
			}
		}
		if ($all_inline) {
			// inliner les CSS (optimisation de la page minipage qui passe en un seul hit a la demande)
			foreach ($files as $name) {
				$file = direction_css($name);
				if (function_exists('minifier')) {
					$file = minifier($file);
				} else {
					$file = url_absolue_css($file); // precaution
				}
				$css .= file_get_contents($file);
			}
			$css = "$inline\n$css";
			if (!empty($options['css'])) {
				$css .= "\n" . $options['css'];
			}
			$css = "<style type='text/css'>$css</style>";
		} else {
			$css = "<style type='text/css'>$inline</style>";
			foreach ($files as $name) {
				$file = timestamp(direction_css($name));
				$css .= "<link rel='stylesheet' href='" . attribut_html($file) . "' type='text/css' />\n";
			}
			if (!empty($options['css'])) {
				$css .= "<style type='text/css'>" . $options['css'] . '</style>';
			}
		}

		return $doctype .
			html_lang_attributes() .
			"<head>\n" .
			'<title>' .
			textebrut($page_title) .
			"</title>\n" .
			"<meta name=\"viewport\" content=\"width=device-width\" />\n" .
			$css .
			(empty($options['head']) ? '' : $options['head']) .
			"</head>\n" .
			"<body{$onLoad} class=\"minipage" . ($this::TYPE ? ' minipage--' . $this::TYPE : '') . "\">\n" .
			"\t<div class=\"minipage-bloc\">\n";
	}

	/**
	 * Ouvre le corps : affiche le header avec un éventuel titre + ouvre le div corps
	 * @param $options
	 * @return string
	 */
	protected function ouvreCorps($options = []) {
		$url_site = url_de_base();
		$header = "<header>\n" .
			'<h1><a href="' . attribut_html($url_site) . '">' . interdire_scripts($GLOBALS['meta']['nom_site'] ?? '') . "</a></h1>\n";

		$titre = ($options['titre'] ?? '');
		if ($titre) {
			$header .= '<h2>' . interdire_scripts($titre) . '</h2>';
		}
		$header .= '</header>';

		return $header . "<div class='corps'>\n";
	}

	/**
	 * Ferme le corps : affiche le footer par défaut ou custom et ferme le div corps
	 * @param $options
	 * @return string
	 */
	protected function fermeCorps($options = []) {
		$url_site = url_de_base();

		if (isset($options['footer'])) {
			$footer = $options['footer'];
		} else {
			$footer = '<a href="' . attribut_html($url_site) . '">' . _T('retour') . "</a>\n";
		}
		if (!empty($footer)) {
			$footer = "<footer>\n{$footer}</footer>";
		}

		return "</div>\n" . $footer;
	}


	/**
	 * Retourne la fin d'une page HTML minimale
	 *
	 * @return string Code HTML
	 */
	protected function fermeBody() {
		return "\n\t</div>\n</body>\n</html>";
	}


	/**
	 * Retourne une page HTML contenant, dans une présentation minimale,
	 * le contenu transmis dans `$corps`.
	 *
	 * Appelée pour afficher un message ou une demande de confirmation simple et rapide
	 *
	 * @param string $corps
	 *   Corps de la page
	 * @param array $options
	 * @return string
	 *   HTML de la page
	 * @see  ouvreBody()
	 * @see  ouvreCorps()
	 *   string $titre : Titre à l'affichage (différent de $page_title)
	 *   int $status : status de la page
	 *   string $footer : pied de la box en remplacement du bouton retour par défaut
	 * @uses ouvreBody()
	 * @uses ouvreCorps()
	 * @uses fermeCorps()
	 * @uses fermeBody()
	 *
	 */
	public function page($corps, $options = []) {

		// par securite
		if (!defined('_AJAX')) {
			define('_AJAX', false);
		}

		$status = ((int) ($options['status'] ?? 200)) ?: 200;

		http_response_code($status);

		$html = $this->ouvreBody($options)
			. $this->ouvreCorps($options)
			. $corps
			. $this->fermeCorps($options)
			. $this->fermeBody();

		if (
			$GLOBALS['profondeur_url'] >= (_DIR_RESTREINT ? 1 : 2)
			&& empty($options['all_inline'])
		) {
			define('_SET_HTML_BASE', true);
			include_spip('public/assembler');
			$GLOBALS['html'] = true;
			page_base_href($html);
		}
		return $html;
	}

	/**
	 * Fonction helper pour les erreurs
	 * @param ?string $message_erreur
	 * @param array $options
	 * @see page()
	 * @return string
	 *
	 */
	public function pageErreur($message_erreur = null, $options = []) {

		if (empty($message_erreur)) {
			if (empty($options['lang'])) {
				utiliser_langue_visiteur();
			} else {
				changer_langue($options['lang']);
			}
			$message_erreur = _T('info_acces_interdit');
		}
		$corps = "<div class='msg-alert error'>"
			. $message_erreur
			. '</div>';
		if (empty($options['status'])) {
			$options['status'] = 403;
		}
		return $this->page($corps, $options);
	}
}
