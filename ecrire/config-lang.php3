<?php

include ("inc.php3");

include_ecrire ("inc_config.php3");

if ($connect_statut != '0minirezo' OR !$connect_toutes_rubriques) {
	echo _T('avis_non_acces_page');
	exit;
}

init_config();
if ($changer_config == 'oui') {
	appliquer_modifs_config();
}

debut_page(_T('titre_page_config_fonctions'), "administration", "configuration");

echo "<br><br><br>";
gros_titre(_T('titre_config_fonctions'));
barre_onglets("configuration", "lang");

debut_gauche();
debut_droite();

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
echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>"._T('info_langues')."</FONT></B> ".aide ()."</TD></TR>";

echo "<TR><TD class='verdana2'>";
echo _T('texte_choix_langue_defaut');
echo "</TD></TR>";


// langue par defaut
echo "<TR><TD ALIGN='left' class='verdana2'>";
echo _T('info_langue_defaut');
echo "\n<select name='langue_site' class='fondl'>\n";
echo "<option value='$langue_site' style='background-image: url(lang/drap_$langue_site.gif); background-repeat: no-repeat; background-position: 3px 3px; padding-left: 20px;' selected>"._T("langue_".$langue_site)."</option>\n";
reset ($langues_prop);
while (list(,$l) = each ($langues_prop)) {
	if ($l <> $langue_site)
		echo "<option value='$l' style='background-image: url(lang/drap_$l.gif); background-repeat: no-repeat; background-position: 3px 3px; padding-left: 20px;'>"._T("langue_".$l)."</option>\n";
}
echo "</select><br>\n";
echo "</TD></TR>";


// langues proposees
echo "<TR><TD ALIGN='left' class='verdana2'>";
echo _T('info_langues_proposees');
$langues_toutes = split(',',$all_langs);
$langues_proposees = lire_meta('langues_proposees');
$test = (ereg(",$langue_site,", ",$langues_tests,")) ? _T('info_en_test_1') : "";
echo "<input type='checkbox' name='langues_prop[]' value='$langue_site' checked id='lang_$langue_site'><label for='lang_$langue_site'>&nbsp;<b>"._T("langue_".$langue_site).'</b>'.$test."</label>\n";
while (list(,$l) = each ($langues_toutes)) {
	if ($l AND ($l <> $langue_site)) {
		$test = (ereg(",$l,", ",$langues_tests,")) ? _T('info_en_test_2') : "";
		if (ereg(",$l,", ",$langues_proposees,"))
			echo "<br>&nbsp; <input type='checkbox' name='langues_prop[]' value='$l' checked id='lang_$l'><label for='lang_$l'>&nbsp;<b>"._T("langue_".$l).'</b>'.$test.'</label>';
		else
			echo "<br>&nbsp; <input type='checkbox' name='langues_prop[]' value='$l' id='lang_$l'> <label for='lang_$l'>"._T("langue_".$l).$test.'</label>';
	}
}
echo "</TD></TR>";


echo "<TR><TD ALIGN='right'>";
echo "<INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
echo "</TD></TR>";
echo "</TABLE>\n";

fin_cadre_relief();

echo "<p>";




echo "</form>";

fin_page();

?>
