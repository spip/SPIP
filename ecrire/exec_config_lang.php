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

function config_lang_dist()
{ 
global $connect_statut, $connect_toutes_rubriques, $options, $spip_lang_right, $all_langs, $changer_config;

debut_page(_T('titre_page_config_contenu'), "administration", "langues");

echo "<br><br><br>";
gros_titre(_T('info_langues'));

if ($connect_statut != '0minirezo' OR !$connect_toutes_rubriques) {
		echo _T('avis_non_acces_page');
		exit;

	}

barre_onglets("config_lang", "langues");


debut_gauche();

debut_droite();


init_config();
if ($changer_config == 'oui') {
	appliquer_modifs_config();
	calculer_langues_rubriques();
}

lire_metas();


 echo generer_url_post_ecrire('config_lang');
 echo "<input type='hidden' name='changer_config' value='oui'>";


//
// Configuration i18n
//

debut_cadre_couleur("langues-24.gif", false, "", _T('info_langue_principale'));

$langues_prop = split(",",$all_langs);
$langue_site = $GLOBALS['meta']['langue_site'];

echo _T('texte_selection_langue_principale');


// langue du site
echo _T('info_langue_principale')." : ";
echo "\n<select name='changer_langue_site' class='fondl' align='middle'>\n";
echo "<option value='$langue_site' selected>".traduire_nom_langue($langue_site)."</option>\n";
reset ($langues_prop);
while (list(,$l) = each ($langues_prop)) {
	if ($l <> $langue_site)
		echo "<option value='$l'>".traduire_nom_langue($l)."</option>\n";
}
echo "</select>\n";
echo "<INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";


fin_cadre_couleur();

echo "<p>";


//
// Configuration du charset
//

#if ($options == 'avancees') {
	debut_cadre_relief("breve-24.gif", false, "", _T('info_jeu_caractere'));

	$charset = $GLOBALS['meta']["charset"];

	echo _T('texte_jeu_caractere')."<p>";
	echo "<blockquote class='spip'><p>"._T('texte_jeu_caractere_2')."</p></blockquote>";


	echo bouton_radio('charset', 'utf-8',
		_T('bouton_radio_universel'), $charset == 'utf-8');
	echo "<br>";

	if ($charset != 'utf-8'
	AND load_charset($charset)) {

		echo generer_url_post_ecrire('convert_utf8');
		echo "\n<div align='center'><input class='fondo' type='submit' VALUE='". _L("Convertir votre site en utf-8") ."'></div></form>";
		echo "<br>";
	}

	echo bouton_radio('charset', 'iso-8859-1',
		_T('bouton_radio_occidental'), $charset == 'iso-8859-1');
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

	echo "<div style='text-align: $spip_lang_right;'><INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'></div>";

	fin_cadre_relief();

#} # /avancees


echo "</form>";

fin_page();
}
?>
