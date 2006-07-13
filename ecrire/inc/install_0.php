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

function inc_install_()
{
	global $spip_lang_right;

	$menu_langues = menu_langues('var_lang_ecrire');
	if (!$menu_langues)
		redirige_par_entete(generer_url_action('test_dirs'));
	else {
		install_debut_html();
	
		echo "<p align='center'><img src='" . _DIR_IMG_PACK . "logo-spip.gif'></p>",
		  "<p style='text-align: center; font-family: Verdana,Arial,Sans,sans-serif; font-size: 10px;'>",
		 info_copyright(),
		  "</p>",
		  "<p>" . _T('install_select_langue'),
		  "<p><div align='center'>",
		  $menu_langues,
		  "</div>",
		  "<p><form action='", generer_url_action('test_dirs'),
		  "'>",
		  '<input type="hidden" name="action" value="test_dirs" />',
		  "<div align='$spip_lang_right'><input type='submit' class='fondl'  VALUE='",
		  _T('bouton_suivant'),
		  " >>'>",
		  "</form>";
		install_fin_html();
	}
}

?>
