<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Présentation des pages d'installation et d'erreurs
 *
 * @package SPIP\Core\Minipres 
**/
if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/headers');
include_spip('inc/texte'); //inclue inc/lang et inc/filtres


/**
 * Retourne le début d'une page HTML minimale (de type installation ou erreur)
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
	global $spip_lang_right,$spip_lang_left;
	
	utiliser_langue_visiteur();

	http_no_cache();

	if ($titre=='AUTO')
		$titre=_T('info_installation_systeme_publication');

	# le charset est en utf-8, pour recuperer le nom comme il faut
	# lors de l'installation
	if (!headers_sent())
		header('Content-Type: text/html; charset=utf-8');

	$css = "";
	$files = array('reset.css','clear.css','minipres.css');
	if ($all_inline){
		// inliner les CSS (optimisation de la page minipres qui passe en un seul hit a la demande)
		foreach ($files as $name){
			$file = direction_css(find_in_theme($name));
			if (function_exists("compacte"))
				$file = compacte($file);
			else
				$file = url_absolue_css($file); // precaution
			lire_fichier($file,$c);
			$css .= $c;
		}
		$css = "<style type='text/css'>".$css."</style>";
	}
	else{
		foreach ($files as $name){
			$file = direction_css(find_in_theme($name));
			$css .= "<link rel='stylesheet' href='$file' type='text/css' />\n";
		}
	}

	// au cas ou minipres() est appele avant spip_initialisation_suite()
	if (!defined('_DOCTYPE_ECRIRE')) define('_DOCTYPE_ECRIRE', '');
	return  _DOCTYPE_ECRIRE.
		html_lang_attributes().
		"<head>\n".
		"<title>".
		textebrut($titre).
		"</title>\n".
		"<meta name='viewport' content='width=device-width' />\n".
		$css .
"</head>
<body".$onLoad." class='minipres'>
	<div id='minipres'>
	<h1>".
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
 * @param string $onload
 *   Attribut onload de `<body>`
 * @param bool $all_inline
 *   Inliner les css et js dans la page (limiter le nombre de hits)
 * @return string
 *   HTML de la page
 */
function minipres($titre='', $corps="", $onload='', $all_inline = false)
{
	if (!defined('_AJAX')) define('_AJAX', false); // par securite
	if (!$titre) {
		if (!_AJAX)
			http_status(403);
		if (!$titre = _request('action')
		AND !$titre = _request('exec')
		AND !$titre = _request('page'))
			$titre = '?';

		$titre = htmlspecialchars($titre);

		$titre = ($titre == 'install')
		  ?  _T('avis_espace_interdit')
		  : $titre . '&nbsp;: '. _T('info_acces_interdit');
		$corps = generer_form_ecrire('accueil', '','',
						$GLOBALS['visiteur_session']['statut']?_T('public:accueil_site'):_T('public:lien_connecter')
		);
		spip_log($GLOBALS['visiteur_session']['nom'] . " $titre " . $_SERVER['REQUEST_URI']);
	}

	if (!_AJAX)
		return install_debut_html($titre, $onload, $all_inline)
		. $corps
		. install_fin_html();
	else {
		include_spip('inc/headers');
		include_spip('inc/actions');
		$url = self('&',true);
		foreach ($_POST as $v => $c)
			$url = parametre_url($url, $v, $c, '&');
		ajax_retour("<div>".$titre . redirige_formulaire($url)."</div>",false);
	}
}
?>
