<?php

include ("inc.php3");

include_ecrire ("inc_config.php3");

function mySel($varaut,$variable){
		$retour= " VALUE=\"$varaut\"";

	if ($variable==$varaut){
		$retour.= " SELECTED";
	}

	return $retour;
}


debut_page(_T('titre_page_config_contenu'), "administration", "configuration");

echo "<br><br><br>";
gros_titre(_T('info_langues'));
barre_onglets("configuration", "langues");


debut_gauche();

debut_droite();

if ($connect_statut != '0minirezo' OR !$connect_toutes_rubriques) {
	echo _T('avis_non_acces_page');
	fin_page();
	exit;
}

init_config();
if ($changer_config == 'oui') {
	appliquer_modifs_config();
	calculer_langues_rubriques();
}

lire_metas();


echo "<form action='config-lang.php3' method='post'>";
echo "<input type='hidden' name='changer_config' value='oui'>";


//
// Configuration i18n
//

debut_cadre_relief("langues-24.gif");

$langues_prop = split(",",lire_meta("langues_proposees"));
$langue_site = lire_meta('langue_site');

echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>"._T('info_langue_principale')."</FONT></B> ".aide ()."</TD></TR>";

echo "<TR><TD class='verdana2'>";
echo _T('texte_selection_langue_principale');
echo "</TD></TR>";


// langue du site
echo "<TR><TD ALIGN='$spip_lang_left' class='verdana2'>";
echo _T('info_langue_principale')." : ";
echo "\n<select name='changer_langue_site' class='fondl' align='middle'>\n";
echo "<option value='$langue_site' selected>".traduire_nom_langue($langue_site)."</option>\n";
reset ($langues_prop);
while (list(,$l) = each ($langues_prop)) {
	if ($l <> $langue_site)
		echo "<option value='$l'>".traduire_nom_langue($l)."</option>\n";
}
echo "</select><br>\n";
echo "</TD></TR>";

echo "<TR><TD ALIGN='$spip_lang_right'>";
echo "<INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
echo "</TD></TR>";
echo "</TABLE>\n";


fin_cadre_relief();

echo "<p>";


//
// Configuration du charset
//

if ($options == 'avancees') {
	debut_cadre_relief("breve-24.gif");

	$charset = lire_meta("charset");

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>"._T('info_jeu_caractere')."</FONT></B></TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='verdana2'>";
	echo _T('texte_jeu_caractere')."<p>";
	echo "<blockquote><i>"._T('texte_jeu_caractere_2')."</i></blockquote>";

	echo "</FONT>";
	echo "</TD></TR>";

	echo "<TR><TD ALIGN='$spip_lang_left' class='verdana2'>";
	echo bouton_radio('charset', 'iso-8859-1',
		_T('bouton_radio_occidental'), $charset == 'iso-8859-1');
	echo "<br>";
	echo bouton_radio('charset', 'utf-8',
		_T('bouton_radio_universel'), $charset == 'utf-8');
	echo "<br>";
	echo bouton_radio('charset', 'custom',
		_T('bouton_radio_personnalise'), $charset != 'utf-8' && $charset != 'iso-8859-1');
	echo "<br>";
	if ($charset != 'utf-8' && $charset != 'iso-8859-1') {
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"._T('info_entrer_code_alphabet')."&nbsp;";
		echo "<input type='text' name='charset_custom' class='fondl' value='$charset' size='15'>";
	}
	else
		echo "<input type='hidden' name='charset_custom' value=''>";
	echo "</TD></TR>";

	echo "<TR><TD ALIGN='$spip_lang_right'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
	echo "</TD></TR>";

	echo "</TABLE>";

	fin_cadre_relief();

}


debut_cadre_enfonce();
	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='img_pack/rien.gif' COLSPAN=2><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='black'>"._T('info_multilinguisme')."</FONT></B>" /* .aide ("confart") */ ."</TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' COLSPAN='2' class='verdana2'>";
	echo _T('texte_multilinguisme');
	echo "</TD></TR>";

	echo "<TR>";
	echo "<TD ALIGN='$spip_lang_left' class='verdana2'>";
	echo _T('info_multi_articles');
	echo "</TD>";
	echo "<TD ALIGN='$spip_lang_left' class='verdana2'>";
	afficher_choix('multi_articles', lire_meta('multi_articles'),
		array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ");
	echo "</TD></TR>\n";

	echo "<TR>";
	echo "<TD ALIGN='$spip_lang_left' class='verdana2'>";
	echo _T('info_multi_rubriques');
	echo "</TD>";
	echo "<TD ALIGN='$spip_lang_left' class='verdana2'>";
	afficher_choix('multi_rubriques', lire_meta('multi_rubriques'),
		array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ");
	echo "</TD></TR>\n";

	if  (lire_meta('multi_rubriques') == 'oui') {
		echo "<TR>";
		echo "<TD ALIGN='$spip_lang_left' class='verdana2'>";
		echo _T('info_multi_secteurs');
		echo "</TD>";
		echo "<TD ALIGN='$spip_lang_left' class='verdana2'>";
		afficher_choix('multi_secteurs', lire_meta('multi_secteurs'),
			array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ");
		echo "</TD></TR>\n";
	} else
		echo "<input type='hidden' name='multi_secteurs' value='".lire_meta('multi_secteurs')."'>";

	if ((lire_meta('multi_rubriques') == 'oui') OR (lire_meta('multi_articles') == 'oui')) {
		echo "<TR><TD BACKGROUND='img_pack/rien.gif' COLSPAN='2' class='verdana2'><hr>";
		echo _T('texte_multilinguisme_trad');
		echo "</TD></TR>";

		echo "<TR>";
		echo "<TD ALIGN='$spip_lang_left' class='verdana2'>";
		echo _T('info_gerer_trad');
		echo "</TD>";
		echo "<TD ALIGN='$spip_lang_left' class='verdana2'>";
		afficher_choix('gerer_trad', lire_meta('gerer_trad'),
			array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ");
		echo "</TD></TR>\n";
	} else
		echo "<input type='hidden' name='gerer_trad' value='".lire_meta('gerer_trad')."'>";


	echo "<TR><TD ALIGN='$spip_lang_right' COLSPAN=2>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
	echo "</TD></TR>";
	echo "</TABLE>";


	if (lire_meta('multi_articles') == "oui" OR lire_meta('multi_rubriques') == "oui") {
		echo "<p>";
		debut_cadre_relief("langues-24.gif");
		echo "<p class='verdana2'>";
		echo _T('info_multi_langues_choisies');
		echo '</p>';

		$langues = $GLOBALS['codes_langues'];
		$cesure = floor((count($langues) + 1) / 2);

		$langues_installees = explode(',', $GLOBALS['all_langs']);
		$langues_autorisees = explode(',', lire_meta('langues_multilingue'));

		while (list(,$l) = each ($langues_installees)) {
			$langues_trad[$l] = true;
		}

		while (list(,$l) = each ($langues_autorisees)) {
			$langues_auth[$l] = true;
		}

		$langues_bloquees[lire_meta('langue_site')] = 1;
		$query = "SELECT DISTINCT lang FROM spip_articles WHERE statut='publie'";
		$result = spip_query($query);
		while ($row = spip_fetch_array($result)) {
			$langues_bloquees[$row['lang']] = 1;
		}
		$query = "SELECT DISTINCT lang FROM spip_breves WHERE statut='publie'";
		$result = spip_query($query);
		while ($row = spip_fetch_array($result)) {
			$langues_bloquees[$row['lang']] = 1;
		}
		$query = "SELECT DISTINCT lang FROM spip_rubriques WHERE statut='publie'";
		$result = spip_query($query);
		while ($row = spip_fetch_array($result)) {
			$langues_bloquees[$row['lang']] = 1;
		}

		echo "<table width = '100%' cellspacing='10'><tr><td width='50%' align='top'><font size='2' face='Verdana,Arial,Helvetica,sans-serif'>";

		ksort($langues_bloquees);
		while (list($code_langue, ) = each($langues_bloquees)) {
			$i++;
			echo "<div>";
			$nom_langue = $langues[$code_langue];
			$nom_langue = "<b><font color='$couleur_foncee'>$nom_langue</font></b>";
			echo "<input type='hidden' name='langues_auth[]' value='$code_langue' id='langue_auth_$code_langue'>";
			echo "<input type='checkbox' checked disabled>";
			echo  " $nom_langue &nbsp; &nbsp;<font color='#777777'>[$code_langue]</font>";
			echo "</div>\n";

			if ($i == $cesure) echo "</font></td><td width='50%' align='top'><font size='2' face='Verdana,Arial,Helvetica,sans-serif'>";
		}

		echo "<div>&nbsp;</div>";

		while (list($code_langue, $nom_langue) = each($langues)) {
			if ($langues_bloquees[$code_langue]) continue;
			$i++;
			echo "<div>";

			if ($langues_auth[$code_langue]) {
				echo "<input type='checkbox' name='langues_auth[]' value='$code_langue' id='langue_auth_$code_langue' checked>";
				$nom_langue = "<b>$nom_langue</b>";
			}
			else {
				echo "<input type='checkbox' name='langues_auth[]' value='$code_langue' id='langue_auth_$code_langue'>";
			}
			echo  " <label for='langue_auth_$code_langue'>$nom_langue</label> &nbsp; &nbsp;<font color='#777777'>[$code_langue]</font>";

			echo "</div>\n";

			if ($i == $cesure) echo "</font></td><td width='50%' align='top'><font size='2' face='Verdana,Arial,Helvetica,sans-serif'>";
		}

		echo "</font></td></tr>";
		echo "<tr><td ALIGN='$spip_lang_right' COLSPAN=2>";
		echo "<INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
		echo "</td></tr></table>";

		fin_cadre_relief();
	}
fin_cadre_enfonce();



echo "</form>";

fin_page();

?>
