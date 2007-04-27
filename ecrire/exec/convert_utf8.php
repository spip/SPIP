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

// En cas d'erreur, une page admin normale avec bouton de retour

function convert_utf8_non($action, $message) {

	echo minipres($action, ('<p>'.$message. "</p>\n<p style='text-align: right'><a href='" . generer_url_ecrire("config_lang"). "'> &gt;&gt; "._T('icone_retour')."</a>"));
	exit;
}

// http://doc.spip.org/@exec_convert_utf8_dist
function exec_convert_utf8_dist() {
	include_spip('inc/meta');
	include_spip('inc/charsets');
	lire_metas();

	// Definir le titre de la page (et le nom du fichier admin)
	$action = _T('utf8_convertir_votre_site');

	// si meta deja la, c'est une reprise apres timeout.
	if ($GLOBALS['meta']['convert_utf8']) {
		$base = charger_fonction('convert_utf8', 'base');
		$base($action, true);
	} else {
		$charset_orig =	$GLOBALS['meta']['charset'];
		// tester si le charset d'origine est connu de spip
		if (!load_charset($charset_orig))
			convert_utf8_non($action,
					  _T('utf8_convert_erreur_orig', array('charset' => "<b>".$charset_orig."</b>")));

		// ne pas convertir si deja utf8
		else if ($charset_orig == 'utf-8')
			convert_utf8_non($action,
					  _T('utf8_convert_erreur_deja',
					     array('charset' => $charset_orig)));

		$commentaire = _T('utf8_convert_avertissement',
			array('orig' => $charset_orig,'charset' => 'utf-8'));
		$commentaire .=  "<p><small>"
		. http_img_pack('warning.gif', _T('info_avertissement'), "style='width: 48px; height: 48px; float: right;margin: 10px;'");
		$commentaire .= _T('utf8_convert_backup', array('charset' => 'utf-8'))
		."</small>";
		$commentaire .= '<p>'._T('utf8_convert_timeout');
		$commentaire .= "<hr />\n";

		$admin = charger_fonction('admin', 'inc');
		$admin('convert_utf8', $action, $commentaire);
	}
}
?>
