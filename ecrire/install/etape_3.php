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

// http://doc.spip.org/@inc_install_3
function install_etape_3_dist()
{
	global $adresse_db, $login_db, $pass_db, $spip_lang_right, $chmod;

	install_debut_html();

	echo info_etape(_T('info_choix_base')." "._T('menu_aide_installation_choix_base').aide ("install2"));

	echo generer_url_post_ecrire('install');
	echo "<INPUT TYPE='hidden' NAME='etape' VALUE='4' />";
	echo "<INPUT TYPE='hidden' NAME='chmod' VALUE='$chmod' />";
	echo "<INPUT TYPE='hidden' NAME='adresse_db'  VALUE=\"$adresse_db\" />";
	echo "<INPUT TYPE='hidden' NAME='login_db' VALUE=\"$login_db\" />";
	echo "<INPUT TYPE='hidden' NAME='pass_db' VALUE=\"$pass_db\" />\n";

	$link = mysql_connect("$adresse_db","$login_db","$pass_db");
	$result = @mysql_list_dbs();

	echo "<fieldset><legend>"._T('texte_choix_base_1')."</legend>\n";

	if ($result AND (($n = @mysql_num_rows($result)) > 0)) {
		echo "<label for='choix_db'><b>"._T('texte_choix_base_2')."</b><br />"._T('texte_choix_base_3')."</label>";
		echo "<ul>";
		$bases = "";
		for ($i = 0; $i < $n; $i++) {
			$table_nom = mysql_dbname($result, $i);
			$base = "<li><INPUT NAME=\"choix_db\" VALUE=\"".$table_nom."\" TYPE=Radio id='tab$i'";
			$base_fin = " /><label for='tab$i'>".$table_nom."</label></li>\n\n";
			if ($table_nom == $login_db) {
				$bases = "$base CHECKED$base_fin".$bases;
				$checked = true;
			}
			else {
				$bases .= "$base$base_fin\n";
			}
		}
		echo $bases."</UL>";
		echo _T('info_ou')." ";
	}
	else {
		echo "<B>"._T('avis_lecture_noms_bases_1')."</B>
		"._T('avis_lecture_noms_bases_2')."<P>";
		if ($login_db) {
			// Si un login comporte un point, le nom de la base est plus
			// probablement le login sans le point -- testons pour savoir
			$test_base = $login_db;
			$ok = @mysql_select_db($test_base);
			$test_base2 = str_replace('.', '_', $test_base);
			if (@mysql_select_db($test_base2)) {
				$test_base = $test_base2;
				$ok = true;
			}
			
			if ($ok) {
				echo _T('avis_lecture_noms_bases_3');
				echo "<ul>";
				echo "<li><INPUT NAME=\"choix_db\" VALUE=\"".$test_base."\" TYPE=Radio id='stand' CHECKED>";
				echo "<label for='stand'>".$test_base."</label></li>\n";
				echo "</UL>";
				echo _T('info_ou')." ";
				$checked = true;
			}
		}
	}
	echo "<INPUT NAME=\"choix_db\" VALUE=\"new_spip\" TYPE=Radio id='nou'";
	if (!$checked) echo " CHECKED";
	echo " /> <label for='nou'>"._T('info_creer_base')."</label> ";
	echo "<INPUT TYPE='text' NAME='table_new' CLASS='fondl' VALUE=\"spip\" SIZE='20' /></fieldset>";

	echo bouton_suivant();
	echo "</FORM>";

	install_fin_html();
}

?>
