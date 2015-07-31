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

if (!defined("_ECRIRE_INC_VERSION")) return;

include_ecrire("inc_presentation");
include_ecrire("inc_rubriques");
include_ecrire ("inc_config");

function config_multilang_dist()
{
  global $connect_statut, $connect_toutes_rubriques, $couleur_foncee, $spip_lang_right, $changer_config;

lire_metas();

debut_page(_T('titre_page_config_contenu'), "administration", "langues");

echo "<br><br><br>";
gros_titre(_T('info_langues'));


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


barre_onglets("config_lang", "multi");


debut_gauche();

	
	
debut_droite();

echo generer_url_post_ecrire('config-multilang');
echo "<input type='hidden' name='changer_config' value='oui'>";

debut_cadre_couleur("traductions-24.gif", false, "", _T('info_multilinguisme'));
	echo "<p>"._T('texte_multilinguisme')."</p>";

	echo "<div>";
	echo _T('info_multi_articles');
	echo "<div style='text-align: $spip_lang_right';>";
	afficher_choix('multi_articles', $GLOBALS['meta']['multi_articles'],
		array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ");
	echo "</div>";
	echo "</div>";

	echo "<div>";
	echo _T('info_multi_rubriques');
	echo "<div style='text-align: $spip_lang_right';>";
	afficher_choix('multi_rubriques', $GLOBALS['meta']['multi_rubriques'],
		array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ");
	echo "</div>";
	echo "</div>";

	if  ($GLOBALS['meta']['multi_rubriques'] == 'oui') {
		echo "<div>";
		echo _T('info_multi_secteurs');
		echo "<div style='text-align: $spip_lang_right';>";
		afficher_choix('multi_secteurs', $GLOBALS['meta']['multi_secteurs'],
			array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ");
		echo "</div>";
		echo "</div>";
	} else
		echo "<input type='hidden' name='multi_secteurs' value='".$GLOBALS['meta']['multi_secteurs']."'>";

	if (($GLOBALS['meta']['multi_rubriques'] == 'oui') OR ($GLOBALS['meta']['multi_articles'] == 'oui')) {
		echo "<hr>";
		echo "<p>"._T('texte_multilinguisme_trad')."</p>";

		echo _T('info_gerer_trad');
		echo "<div style='text-align: $spip_lang_right';>";
		afficher_choix('gerer_trad', $GLOBALS['meta']['gerer_trad'],
			array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ");
		echo "</div>";
	} else
		echo "<input type='hidden' name='gerer_trad' value='".$GLOBALS['meta']['gerer_trad']."'>";


	echo "<div style='text-align: $spip_lang_right;'><INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'></div>";

fin_cadre_couleur();

	if ($GLOBALS['meta']['multi_articles'] == "oui" OR $GLOBALS['meta']['multi_rubriques'] == "oui") {
		echo "<p>";
		debut_cadre_relief("langues-24.gif");
		echo "<p class='verdana2'>";
		echo _T('info_multi_langues_choisies');
		echo '</p>';

		include_ecrire('inc_lang_liste');
		$langues = $GLOBALS['codes_langues'];
		$cesure = floor((count($langues) + 1) / 2);

		$langues_installees = explode(',', $GLOBALS['all_langs']);
		$langues_autorisees = explode(',', $GLOBALS['meta']['langues_multilingue']);

		while (list(,$l) = each ($langues_installees)) {
			$langues_trad[$l] = true;
		}

		while (list(,$l) = each ($langues_autorisees)) {
			$langues_auth[$l] = true;
		}

		$langues_bloquees[$GLOBALS['meta']['langue_site']] = 1;
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

		echo "<table width = '100%' cellspacing='10'><tr><td width='50%' align='top' class='verdana1'>";

		ksort($langues_bloquees);
		while (list($code_langue, ) = each($langues_bloquees)) {
			$i++;
			echo "<div>";
			$nom_langue = $langues[$code_langue];
			if ($langues_trad[$code_langue]) $nom_langue = "<u>$nom_langue</u>";
			$nom_langue = "<b><font color='$couleur_foncee'>$nom_langue</font></b>";
			echo "<input type='hidden' name='langues_auth[]' value='$code_langue' id='langue_auth_$code_langue'>";
			echo "<input type='checkbox' checked disabled>";
			echo  " $nom_langue &nbsp; &nbsp;<font color='#777777'>[$code_langue]</font>";
			echo "</div>\n";

			if ($i == $cesure) echo "</td><td width='50%' align='top' class='verdana1'>";
		}

		echo "<div>&nbsp;</div>";

		while (list($code_langue, $nom_langue) = each($langues)) {
			if ($langues_bloquees[$code_langue]) continue;
			$i++;
			echo "<div>";
			if ($langues_trad[$code_langue]) $nom_langue = "<u>$nom_langue</u>";
	
			if ($langues_auth[$code_langue]) {
				echo "<input type='checkbox' name='langues_auth[]' value='$code_langue' id='langue_auth_$code_langue' checked>";
				$nom_langue = "<b>$nom_langue</b>";
			}
			else {
				echo "<input type='checkbox' name='langues_auth[]' value='$code_langue' id='langue_auth_$code_langue'>";
			}
			echo  " <label for='langue_auth_$code_langue'>$nom_langue</label> &nbsp; &nbsp;<font color='#777777'>[$code_langue]</font>";

			echo "</div>\n";

			if ($i == $cesure) echo "</font></td><td width='50%' align='top' class='verdana1'>";
		}

		echo "</td></tr>";
		echo "<tr><td style='text-align:$spip_lang_right;' COLSPAN=2>";
		echo "<INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
		echo "</td></tr></table>";
		
		
		echo "<div class='verdana1'>"._T("info_multi_langues_soulignees")."</div>";

		fin_cadre_relief();
	}



echo "</form>";

fin_page();
}
?>
