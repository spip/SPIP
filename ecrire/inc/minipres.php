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
 * Présentation des pages d'installation et d'erreurs
 *
 * @package SPIP\Core\Minipres
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/headers');
include_spip('inc/texte'); //inclue inc/lang et inc/filtres


/**
 * Retourne le début d'une page HTML minimale (de type installation ou erreur)
 *
 * Le contenu de CSS minimales (reset.css, clear.css, minipres.css) est inséré
 * dans une balise script inline (compactée si possible)
 *
 * @uses utiliser_langue_visiteur()
 * @uses http_no_cache()
 * @uses html_lang_attributes()
 * @uses compacte() si le plugin compresseur est présent
 * @uses url_absolue_css()
 *
 * @param string $titre
 *    Titre. `AUTO`, indique que l'on est dans le processus d'installation de SPIP
 * @param string $onLoad
 *    Attributs pour la balise `<body>`
 * @param bool $all_inline
 *    Inliner les css et js dans la page (limiter le nombre de hits)
 * @return string
 *    Code HTML
 */
function install_debut_html($titre = 'AUTO', $onLoad = '', $all_inline = false) {

	utiliser_langue_visiteur();

	http_no_cache();

	if ($titre == 'AUTO') {
		$titre = _T('info_installation_systeme_publication');
	}

	# le charset est en utf-8, pour recuperer le nom comme il faut
	# lors de l'installation
	if (!headers_sent()) {
		header('Content-Type: text/html; charset=utf-8');
	}

	$css = '';
	$files = ['reset.css', 'clear.css', 'minipres.css'];
	if ($all_inline) {
		// inliner les CSS (optimisation de la page minipres qui passe en un seul hit a la demande)
		foreach ($files as $name) {
			$file = direction_css(find_in_theme($name));
			if (function_exists('compacte')) {
				$file = compacte($file);
			} else {
				$file = url_absolue_css($file); // precaution
			}
			lire_fichier($file, $c);
			$css .= $c;
		}
		$css = "<style type='text/css'>" . $css . '</style>';
	} else {
		foreach ($files as $name) {
			$file = direction_css(find_in_theme($name));
			$css .= "<link rel='stylesheet' href='$file' type='text/css' />\n";
		}
	}

	// au cas ou minipres() est appele avant spip_initialisation_suite()
	if (!defined('_DOCTYPE_ECRIRE')) {
		define('_DOCTYPE_ECRIRE', '');
	}

	return _DOCTYPE_ECRIRE .
	html_lang_attributes() .
	"<head>\n" .
	'<title>' .
	textebrut($titre) .
	"</title>\n" .
	"<meta name='viewport' content='width=device-width' />\n" .
	$css .
	'</head>
<body' . $onLoad . " class='minipres'>
	<div id='minipres'>
	<h1>" .
	$titre .
	"</h1>
	<div>\n";
}

/**
 * Retourne la fin d'une page HTML minimale (de type installation ou erreur)
 *
 * @return string Code HTML
 */
function install_fin_html() {
	return "\n\t</div>\n\t</div>\n</body>\n</html>";
}


/**
 * Retourne une page HTML contenant, dans une présentation minimale,
 * le contenu transmis dans `$titre` et `$corps`.
 *
 * Appelée pour afficher un message d’erreur (l’utilisateur n’a pas
 * accès à cette page par exemple).
 *
 * Lorsqu’aucun argument n’est transmis, un header 403 est renvoyé,
 * ainsi qu’un message indiquant une interdiction d’accès.
 *
 * @example
 *   ```
 *   include_spip('inc/minipres');
 *   if (!autoriser('configurer')) {
 *      echo minipres();
 *      exit;
 *   }
 *   ```
 * @uses install_debut_html()
 * @uses install_fin_html()
 *
 * @param string $titre
 *   Titre de la page
 * @param string $corps
 *   Corps de la page
 * @param array $options
 *   string onload : Attribut onload de `<body>`
 *   bool all_inline : Inliner les css et js dans la page (limiter le nombre de hits)
 *   int status : status de la page
 * @return string
 *   HTML de la page
 */
function minipres($titre = '', $corps = '', $options = []) {

	// compat signature old
	// minipres($titre='', $corps="", $onload='', $all_inline = false)
	$args = func_get_args();
	if (isset($args[2]) and is_string($args[2])) {
		$options = ['onload' => $args[2]];
	}
	if (isset($args[3])) {
		$options['all_inline'] = $args[3];
	}

	$options = array_merge([
		'onload' => '',
		'all_inline' => false,
	], $options);

	if (!defined('_AJAX')) {
		define('_AJAX', false);
	} // par securite
	if (!$titre) {
		if (!isset($options['status'])) {
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
			$corps = bouton_action(_T('public:accueil_site'), generer_url_ecrire('accueil'));
		}
		elseif (!empty($_COOKIE['spip_admin'])) {
			$corps = bouton_action(_T('public:lien_connecter'), generer_url_public('login'));
		}
		else {
			$corps = bouton_action(_T('public:accueil_site'), $GLOBALS['meta']['adresse_site']);
		}
		$corps = "<div class='boutons'>$corps</div>";
		spip_log($nom . " $titre " . $_SERVER['REQUEST_URI']);
	}

	if (!_AJAX) {
		if (isset($options['status'])) {
			http_response_code($options['status']);
		}

		$html = install_debut_html($titre, $options['onload'], $options['all_inline'])
				. $corps
				. install_fin_html();

		if ($GLOBALS['profondeur_url'] >= (_DIR_RESTREINT ? 1 : 2)
		  and empty($options['all_inline'])) {
			define('_SET_HTML_BASE', true);
			include_spip('public/assembler');
			$GLOBALS['html'] = true;
			page_base_href($html);
		}
		return $html;
	} else {
		include_spip('inc/headers');
		include_spip('inc/actions');
		$url = self('&', true);
		foreach ($_POST as $v => $c) {
			$url = parametre_url($url, $v, $c, '&');
		}
		ajax_retour('<div>' . $titre . redirige_formulaire($url) . '</div>', false);
	}
}
