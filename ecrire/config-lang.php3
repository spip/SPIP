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


debut_page(_T('titre_page_config_contenu'), "administration", "langues");

echo "<br><br><br>";
gros_titre(_T('info_langues'));


barre_onglets("config_lang", "langues");




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
echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3 COLOR='#FFFFFF'>"._T('info_langue_principale')."</FONT></B> ".aide ()."</TD></TR>";

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

echo "<TR><td style='text-align:$spip_lang_right;'>";
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
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3 COLOR='#FFFFFF'>"._T('info_jeu_caractere')."</FONT></B></TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='verdana2'>";
	echo _T('texte_jeu_caractere')."<p>";
	echo "<blockquote class='spip'>"._T('texte_jeu_caractere_2')."</blockquote>";

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

	echo "<TR><td style='text-align:$spip_lang_right;'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
	echo "</TD></TR>";

	echo "</TABLE>";

	fin_cadre_relief();

}


echo "</form>";

fin_page();

?>
