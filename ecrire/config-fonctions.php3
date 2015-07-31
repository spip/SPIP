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


debut_page("Configuration du site", "administration", "configuration");

echo "<br><br><br>";
gros_titre("Configuration du site");
barre_onglets("configuration", "fonctions");

debut_gauche();

debut_droite();

if ($connect_statut != '0minirezo' OR !$connect_toutes_rubriques) {
	echo "Vous n'avez pas acc&egrave;s &agrave; cette page.";
	fin_page();
	exit;
}

init_config();
if ($changer_config == 'oui') {
	appliquer_modifs_config();
}

lire_metas();


echo "<form action='config-fonctions.php3' method='post'>";
echo "<input type='hidden' name='changer_config' value='oui'>";


//
// Activer/desactiver la creation automatique de vignettes
//
if ($flag_function_exists AND @function_exists("imagejpeg")) {
	debut_cadre_relief("image-24.gif");

	$gd_formats = lire_meta("gd_formats");
	$creer_preview = lire_meta("creer_preview");
	$taille_preview = lire_meta("taille_preview");

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee'>";
	echo "<B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='white'>G&eacute;n&eacute;ration de miniatures des images</FONT></B></TD></TR>";
	echo "<TR><TD class='verdana2'>";
	echo "Lorsque vous ajoutez des images en tant que documents joints &agrave; un article,
		SPIP peut cr&eacute;er pour vous, automatiquement, des vignettes (miniatures) des
		images ins&eacute;r&eacute;es. Cela permet par exemple de cr&eacute;er
		automatiquement une galerie ou un portfolio.";
	echo "</TD></TR>";

	echo "<TR><TD ALIGN='left'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
	if ($gd_formats) {
		afficher_choix('creer_preview', $creer_preview,
			array('oui' => 'G&eacute;n&eacute;rer automatiquement les miniatures des images.',
				'non' => 'Ne pas g&eacute;n&eacute;rer de miniatures des images.'));
		echo "<p>";
	}

	echo "<div style='border: 1px dashed #404040; margin: 6px; padding: 6px;'>";
	if ($gd_formats)
		echo "Formats d'images pouvant &ecirc;tre utilis&eacute;es pour cr&eacute;er des vignettes&nbsp;: $gd_formats.<p>";

	// Tester les formats acceptes par GD
	echo "<a href='../spip_image.php3?test_formats=oui&redirect=config-fonctions.php3'>Tester les formats d'image que ce site peut utiliser pour cr&eacute;er des vignettes</a>";
	echo "</div>";

	if ($creer_preview == "oui") {
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Taille maximale des vignettes g&eacute;n&eacute;r&eacute;es par le syst&egrave;me&nbsp;:";
		echo " &nbsp;&nbsp;<INPUT TYPE='text' NAME='taille_preview' VALUE='$taille_preview' class='fondl' size=5>";
		echo " pixels";
	}

	echo "</FONT>";
	echo "</TD></TR>\n";
	echo "<TR><TD ALIGN='right' COLSPAN=2>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
	echo "</TD></TR>";

	echo "</table>";

	fin_cadre_relief();
	echo "<p>";
}


//
// Indexation pour moteur de recherche
//

debut_cadre_relief("racine-site-24.gif");

$activer_moteur = lire_meta("activer_moteur");

echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Moteur de recherche int&eacute;gr&eacute;</FONT></B> ".aide ("confmoteur")."</TD></TR>";

echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='verdana2'>";
echo "Souhaitez-vous utiliser le moteur de recherche int&eacute;gr&eacute; &agrave; SPIP&nbsp;?
	(le d&eacute;sactiver acc&eacute;l&egrave;re le fonctionnement du syst&egrave;me.)";
echo "</TD></TR>";

echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='center' class='verdana2'>";
afficher_choix('activer_moteur', $activer_moteur,
	array('oui' => 'Utiliser le moteur de recherche',
		'non' => 'Ne pas utiliser le moteur'), ' &nbsp; ');
echo "</TD></TR>";

echo "<TR><TD ALIGN='right'>";
echo "<INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
echo "</TD></TR>";
echo "</TABLE>";

fin_cadre_relief();

echo "<p>";


//
// Activer les statistiques
//

debut_cadre_relief("statistiques-24.gif");

$activer_statistiques = lire_meta("activer_statistiques");
$activer_statistiques_ref = lire_meta("activer_statistiques_ref");

echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Statistiques des visites</FONT></B> ".aide ("confstat")."</TD></TR>";

echo "<TR><TD class='verdana2'>";
echo "Votre site doit-il g&eacute;rer les statistiques des visites&nbsp;?";
echo "</TD></TR>";

echo "<TR><TD ALIGN='center' class='verdana2'>";
afficher_choix('activer_statistiques', $activer_statistiques,
	array('oui' => 'G&eacute;rer les statistiques',
		'non' => 'Ne pas g&eacute;rer les statistiques'), ' &nbsp; ');
echo "</TD></TR>\n";

if ($activer_statistiques != "non" AND $options == "avancees") {
	echo "<TR><TD class='verdana2'>";
	echo "Votre site doit-il conserver les <i>referers</i>
		(adresses des liens externes menant &agrave; votre site)&nbsp;?";
	echo "</TD></TR>";

	echo "<TR><TD ALIGN='center' class='verdana2'>";
	afficher_choix('activer_statistiques_ref', $activer_statistiques_ref,
		array('oui' => 'G&eacute;rer les referers',
			'non' => 'Ne pas g&eacute;rer les referers'), ' &nbsp; ');
	echo "</TD></TR>\n";
}


echo "<TR><TD ALIGN='right'>";
echo "<INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
echo "</TD></TR>";
echo "</TABLE>\n";

fin_cadre_relief();


//
// Utilisation d'un proxy pour aller lire les sites syndiques
//

if ($options == 'avancees') {
	debut_cadre_relief();

	$http_proxy=entites_html(lire_meta("http_proxy"));

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Utiliser un proxy</FONT></B> ".aide ("confhttpproxy")."</TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>"
		. propre("Dans certains cas (intranet, r&eacute;seaux prot&eacute;g&eacute;s...),
			il peut &ecirc;tre n&eacute;cessaire d'utiliser un {proxy HTTP} pour atteindre les sites syndiqu&eacute;s.
			Le cas &eacute;ch&eacute;ant, indiquez ci-dessous son adresse, sous la forme
			<tt><html>http://proxy:8080</html></tt>. En g&eacute;n&eacute;ral,
			vous laisserez cette case vide.") . "</FONT>";
	echo "</TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='center'>";
	echo "<INPUT TYPE='text' NAME='http_proxy' VALUE='$http_proxy' size='40' class='forml'>";
	echo "</TD></TR>";

	echo "<TR><TD ALIGN='right'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
	if ($http_proxy) {
		echo "<p align='left'><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>"
			. propre("Pour faire un essai de ce proxy, indiquez ici l'adresse d'un site Web
				que vous souhaitez tester.");
		echo "</TD></TR>";

		echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='center'>";
		echo "<INPUT TYPE='text' NAME='test_proxy' VALUE='http://rezo.net/spip-dev/' size='40' class='forml'>";
		echo "</TD></TR>";

		echo "<TR><TD ALIGN='right'>";

		echo "</font><div align='right'><INPUT TYPE='submit' NAME='tester_proxy' VALUE='Essayer le proxy' CLASS='fondo'></div>";
	}
	echo "</TD></TR>";

	echo "</TABLE>";

	fin_cadre_relief();
}


//
// Configuration du charset
//

if ($options == 'avancees') {
	debut_cadre_relief();

	$charset=lire_meta("charset");

	// peut-on modifier cette valeur ?
	$nbart = spip_fetch_array(spip_query("SELECT COUNT(*) AS count FROM spip_articles WHERE statut='publie'"));
	$nbbre = spip_fetch_array(spip_query("SELECT COUNT(*) AS count FROM spip_breves WHERE statut='publie'"));
	$modif_charset = ($nbart['count'] <= 3 AND $nbbre['count'] <= 3);

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Jeu de caract&egrave;res du site</FONT></B></TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>"
		. propre("Cette option est utile pour construire un site dans un autre jeu de
		caract&egrave;res que l'<code>iso-8859-1</code> (&eacute;galement appel&eacute;
		<code>iso-latin-1</code> ou <code>occidental</code>). Exemples : un site en
		cyrillique utilisera <code>windows-1251</code>; un site en esp&eacute;ranto
		l'<code>UTF-8</code>; en cor&eacute;en, <code>EUC-KR</code>, etc.

		Attention: il faudra &eacute;galement vous assurer que MySQL accepte les textes
		entr&eacute;s dans le {charset} choisi.");

		if (!$modif_charset) {
			echo "<p>".propre("{{Vous avez maintenant publi&eacute; plus de trois articles
			ou br&egrave;ves, il ne vous est donc plus possible de modifier le jeu de
			caract&egrave;res de votre site sans risques.}}");

			echo debut_boite_info();
			echo propre("{{Jeu de caract&egrave;res de ce site: <code>$charset</code>}}");
			echo fin_boite_info();
		}
	
	echo "</FONT>";
	echo "</TD></TR>";

	if ($modif_charset) {
		echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='center'>";
		echo "<INPUT TYPE='text' NAME='charset' VALUE='".entites_html($charset)."' size='40' class='forml'>";
		echo "</TD></TR>";

		echo "<TR><TD ALIGN='right'>";
		echo "<INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
		echo "</TD></TR>";
	}

	echo "</TABLE>";

	fin_cadre_relief();
}



echo "</form>";


fin_page();

?>
