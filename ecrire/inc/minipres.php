<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
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
include_spip('inc/minipublic');


/**
 * Retourne le début d'une page HTML minimale (de type installation ou erreur)
 *
 * Le contenu de CSS minimales (reset.css, clear.css, minipres.css) est inséré
 * dans une balise script inline (compactée si possible)
 *
 * @uses utiliser_langue_visiteur()
 * @uses http_no_cache()
 * @uses html_lang_attributes()
 * @uses minifier() si le plugin compresseur est présent
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

	if ($onLoad) {
		$onLoad = extraire_attribut("<body $onLoad>", "onload");
	}
	if ($titre == 'AUTO') {
		$titre = _T('info_installation_systeme_publication');
	}

	$options = [
		'all_inline' => $all_inline,
		'onload' => $onLoad,
		'page_title' => $titre,
	];
	$options['couleur_fond'] = '#aaa';
	$options['css_files'] = [
		find_in_theme('minipres.css')
	];


	$header = "<header>\n";

	if ($titre){
		$header .= "<h2>".interdire_scripts($titre)."</h2>";
	}
	$header .= "</header>";

	return minipublic_install_debut_html($options)
		. $header
		. "<div class='corps'>\n";
}

/**
 * Retourne la fin d'une page HTML minimale (de type installation ou erreur)
 *
 * @return string Code HTML
 */
function install_fin_html() {
	return "</div>" . minipublic_install_fin_html();
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


	$footer = '';

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
			$footer = bouton_action(_T('public:accueil_site'), generer_url_ecrire('accueil'));
		}
		elseif (!empty($_COOKIE['spip_admin'])) {
			$footer = bouton_action(_T('public:lien_connecter'), generer_url_public('login'));
		}
		else {
			$footer = bouton_action(_T('public:accueil_site'), $GLOBALS['meta']['adresse_site']);
		}

		$corps = "";
		spip_log($nom . " $titre " . $_SERVER['REQUEST_URI']);
	}

	$options['footer'] = $footer;
	$options['page_title'] = $titre;
	$options['titre'] = $titre;
	$options['couleur_fond'] = '#aaa';
	$options['css_files'] = [
		find_in_theme('minipres.css')
	];

	if (!_AJAX) {
		return minipublic($corps, $options);
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
