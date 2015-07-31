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
	global $adresse_db, $login_db, $pass_db, $spip_lang_right;

	install_debut_html();



	echo "<BR />\n<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3>"._T('info_connexion_base')."</FONT>";

	echo "<!--";
	$link = mysql_connect("$adresse_db","$login_db","$pass_db");
	$db_connect = mysql_errno();
	echo "-->";

	echo "<P>";

	if (($db_connect=="0") && $link){
		echo "<B>"._T('info_connexion_ok')."</B><P> "._T('info_etape_suivante_2');

		echo generer_url_post_ecrire('install');
		echo "<INPUT TYPE='hidden' NAME='etape' VALUE='3'>";
		echo "<INPUT TYPE='hidden' NAME='adresse_db'  VALUE=\"$adresse_db\" SIZE='40'>";
		echo "<INPUT TYPE='hidden' NAME='login_db' VALUE=\"$login_db\">";
		echo "<INPUT TYPE='hidden' NAME='pass_db' VALUE=\"$pass_db\"><P>";

		echo "<DIV align='$spip_lang_right'><INPUT TYPE='submit' CLASS='fondl'  VALUE='"._T('bouton_suivant')." >>'>";
		echo "</FORM>";
	}
	else {
		echo "<B>"._T('avis_connexion_echec_1')."</B>";
		echo "<P>"._T('avis_connexion_echec_2');
		echo "<P><FONT SIZE=2>"._T('avis_connexion_echec_3')."</FONT>";
	}

	install_fin_html();
}

?>
