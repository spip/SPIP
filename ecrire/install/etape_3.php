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
	echo "<input type='hidden' name='etape' value='4' />";
	echo "<input type='hidden' name='chmod' value='$chmod' />";
	echo "<input type='hidden' name='adresse_db'  value=\"$adresse_db\" />";
	echo "<input type='hidden' name='login_db' value=\"$login_db\" />";
	echo "<input type='hidden' name='pass_db' value=\"$pass_db\" />\n";

	$link = mysql_connect("$adresse_db","$login_db","$pass_db");
	$result = @mysql_list_dbs();

	echo "<fieldset><legend>"._T('texte_choix_base_1')."</legend>\n";

	$checked = '';
	if ($result AND (($n = @mysql_num_rows($result)) > 0)) {
		echo "<label for='choix_db'><b>"._T('texte_choix_base_2')."</b><br />"._T('texte_choix_base_3')."</label>";
		echo "<ul>";
		$bases = "";
		for ($i = 0; $i < $n; $i++) {
			$table_nom = mysql_dbname($result, $i);
			$base = "<li><input name=\"choix_db\" value=\"".$table_nom."\" type='radio' id='tab$i'";
			$base_fin = " /><label for='tab$i'>".$table_nom."</label></li>\n\n";
			if ($table_nom == $login_db) {
				$bases = "$base checked='checked'$base_fin".$bases;
				$checked = true;
			}
			else {
				$bases .= "$base$base_fin\n";
			}
		}
		echo $bases."</ul>";
		echo "<p>"._T('info_ou')." ";
	}
	else {
		echo "<b>"._T('avis_lecture_noms_bases_1')."</b>
		"._T('avis_lecture_noms_bases_2')."<p>";
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
				echo "<li><input name=\"choix_db\" value=\"".$test_base."\" type='radio' id='stand' checked='checked' />";
				echo "<label for='stand'>".$test_base."</label></li>\n";
				echo "</ul>";
				echo "<p>"._T('info_ou')." ";
				$checked = true;
			}
		}
	}
	echo "<input name=\"choix_db\" value=\"new_spip\" type='radio' id='nou'";
	if (!$checked) echo " checked='checked'";
	echo " /> <label for='nou'>"._T('info_creer_base')."</label></p><p>";
	echo "<input type='text' name='table_new' class='fondl' value=\"spip\" size='20' /></p></fieldset>";

	echo bouton_suivant();
	echo "</form>";

	install_fin_html();
}

?>
