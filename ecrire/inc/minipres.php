<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/texte'); //inclue inc/lang et inc/filtres
include_spip('inc/headers');

//
// Presentation des pages d'installation et d'erreurs
//

// http://doc.spip.org/@install_debut_html
function install_debut_html($titre = 'AUTO', $onLoad = '') {
	global $spip_lang_right,$spip_lang_left;
	
	utiliser_langue_visiteur();

	http_no_cache();

	if ($titre=='AUTO')
		$titre=_T('info_installation_systeme_publication');

	# le charset est en utf-8, pour recuperer le nom comme il faut
	# lors de l'installation
	if (!headers_sent())
		header('Content-Type: text/html; charset=utf-8');
	$dir_img_pack = _DIR_IMG_PACK;
	
	return  _DOCTYPE_ECRIRE.
		html_lang_attributes().
		"<head>\n".
		"<title>".
		textebrut($titre).
		"</title>
		<link rel='stylesheet' href='".find_in_path('minipres.css')."' type='text/css' media='all' />
		<script type='text/javascript' src='" . _DIR_JAVASCRIPT . "spip_barre.js'></script>\n". // cet appel permet d'assurer un copier-coller du nom du repertoire a creer dans tmp (esj)
#	"<script type='text/javascript' src='" . _DIR_JAVASCRIPT . "jquery.js'></script>".
"</head>
<body".$onLoad.">
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

// http://doc.spip.org/@minipres
function minipres($titre='', $corps="", $onload='')
{
	if (!$titre) {
		http_status(403);
		$titre = _request(_DIR_RESTREINT ? 'action' : 'exec');
		$titre = ($titre == 'install')
		  ?  _T('avis_espace_interdit')
		  : $titre . '&nbsp;: '. _T('info_acces_interdit');
		$corps = generer_form_ecrire('accueil', '','',_T('ecrire:accueil_site'));
		spip_log($GLOBALS['auteur_session']['nom'] . " $titre " . $_SERVER['REQUEST_URI']);
	}

	return install_debut_html($titre, $onload)
	. $corps
	. install_fin_html();
}
?>
