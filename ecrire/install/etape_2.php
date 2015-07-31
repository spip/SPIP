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

// http://doc.spip.org/@inc_install_2
function install_etape_2_dist()
{
	global $adresse_db, $login_db, $pass_db, $spip_lang_right,$chmod;

	install_debut_html();

	echo info_etape(_T('info_connexion_base'));

	echo "<!--";
	$link = mysql_connect("$adresse_db","$login_db","$pass_db");
	$db_connect = mysql_errno();
	echo "-->";

	if (($db_connect=="0") && $link){
		echo "<p><b>"._T('info_connexion_ok')."</b></p><p> "._T('info_etape_suivante_2')."</p>";

		echo generer_url_post_ecrire('install');
		echo "<INPUT TYPE='hidden' NAME='etape' VALUE='3' />";
		echo "<INPUT TYPE='hidden' NAME='chmod' VALUE='$chmod' />";
		echo "<INPUT TYPE='hidden' NAME='adresse_db'  VALUE=\"$adresse_db\" />";
		echo "<INPUT TYPE='hidden' NAME='login_db' VALUE=\"$login_db\" />";
		echo "<INPUT TYPE='hidden' NAME='pass_db' VALUE=\"$pass_db\" />";

		echo bouton_suivant();
		echo "</FORM>";
	}
	else {
		echo "<p><b>"._T('avis_connexion_echec_1')."</b></p>";
		echo "<p>"._T('avis_connexion_echec_2')."</p>";
		echo "<p style='font-size: small;'>"._T('avis_connexion_echec_3')."</p>";
	}

	install_fin_html();
}

?>
