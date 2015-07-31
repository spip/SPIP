<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

// http://doc.spip.org/@inc_install_
function install_etape__dist()
{
	global $spip_lang_right;

	$menu_langues = menu_langues('var_lang_ecrire');
	if (!$menu_langues)
		redirige_par_entete(generer_url_action('test_dirs'));
	else {
		install_debut_html();
		echo "<p align='center'><img alt='SPIP' src='" . _DIR_IMG_PACK . "logo-spip.gif' /></p>\n",
			"<p style='text-align: center; font-family: Verdana,Arial,Sans,sans-serif; font-size: 10px;'>",
			info_copyright(),
			"</p>\n",
			"<p>" . _T('install_select_langue'),
			"</p><div align='center'>",
			$menu_langues,
			"</div>\n",
			"<form action='", generer_url_action('test_dirs'),
			"'>",
			'<input type="hidden" name="action" value="test_dirs" />',
			bouton_suivant(),
			"</form>";
		install_fin_html();
	}
}

?>
