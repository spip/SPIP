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

// http://doc.spip.org/@inc_install_5
function install_etape_5_dist()
{
	global $email, $ldap_present, $login, $nom, $pass, $spip_lang_right;

	install_debut_html();

	if (@file_exists(_FILE_CONNECT_INS . _FILE_TMP . '.php'))
		include(_FILE_CONNECT_INS . _FILE_TMP . '.php');
	else
		redirige_par_entete(generer_url_ecrire('install'));

	echo "<BR />\n<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3>"._T('info_informations_personnelles')."</FONT>\n";

	echo "<b>"._T('texte_informations_personnelles_1')."</b>";
	echo aide ("install5");
	echo "<p>\n"._T('texte_informations_personnelles_2')." ";
	echo _T('info_laisser_champs_vides');

	echo generer_url_post_ecrire('install');

	echo "<INPUT TYPE='hidden' NAME='etape' VALUE='6' />";

	echo "<fieldset><label><B>"._T('info_identification_publique')."</B><BR />\n</label>";
	echo "<B>"._T('entree_signature')."</B><BR />\n";
	echo _T('entree_nom_pseudo_1')."<BR />\n";
	echo "<INPUT TYPE='text' NAME='nom' CLASS='formo' VALUE=\"$nom\" SIZE='40' /><P>\n";

	echo "<B>"._T('entree_adresse_email')."</B><BR />\n";
	echo "<INPUT TYPE='text' NAME='email' CLASS='formo' VALUE=\"$email\" SIZE='40' /></fieldset>\n";

	echo "<fieldset><label><B>"._T('entree_identifiants_connexion')."</B><BR />\n</label>";
	echo "<B>"._T('entree_login')."</B><BR />\n";
	echo _T('info_plus_trois_car')."<BR />\n";
	echo "<INPUT TYPE='text' NAME='login' CLASS='formo' VALUE=\"$login\" SIZE='40' />\n";

	echo "<B>"._T('entree_mot_passe')."</B> <BR />\n";
	echo _T('info_plus_cinq_car_2')."<BR />\n";
	echo "<INPUT TYPE='password' NAME='pass' CLASS='formo' VALUE=\"$pass\" SIZE='40' /></fieldset>\n";

	echo bouton_suivant();
	echo "</FORM>\n";

	if (function_exists('ldap_connect') AND !$ldap_present) {
		echo "<div style='border: 1px solid #404040; padding: 10px; text-align: left;'>";
		echo "<b>"._T('info_authentification_externe')."</b>";
		echo "<p>\n"._T('texte_annuaire_ldap_1');
		echo generer_url_post_ecrire('install');
		echo "<INPUT TYPE='hidden' NAME='etape' VALUE='ldap1' />";
		echo "<DIV align='$spip_lang_right'><INPUT TYPE='submit' CLASS='fondl'  VALUE=\""._T('bouton_acces_ldap')."\" /></div>";
		echo "</FORM>
		</div>";
	}

	install_fin_html();
}

?>
