<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/headers');
include_spip('inc/texte'); //inclue inc/lang et inc/filtres

//
// Presentation des pages d'installation et d'erreurs
//

/**
 * http://doc.spip.org/@install_debut_html
 *
 * @param string $titre
 * @param string $onLoad
 * @param bool $all_inline
 *   inliner les css et js dans la page (limiter le nombre de hits)
 * @return string
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

// http://doc.spip.org/@install_fin_html
function install_fin_html() {
	return "\n\t</div>\n\t</div>\n</body>\n</html>";
}


/**
 * http://doc.spip.org/@minipres
 *
 * @param string $titre
 *   titre de la page
 * @param string $corps
 *   corps de la page
 * @param string $onload
 *   attribut onload de <body>
 * @param bool $all_inline
 *   inliner les css et js dans la page (limiter le nombre de hits)
 * @return string
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

		$titre = spip_htmlspecialchars($titre);

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
