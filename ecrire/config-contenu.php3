<?php

include ("inc.php3");

include_ecrire ("inc_admin.php3");

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
barre_onglets("configuration", "contenu");


debut_gauche();

debut_droite();

if ($connect_statut != '0minirezo' OR !$connect_toutes_rubriques) {
	echo "Vous n'avez pas acc&egrave;s &agrave; cette page.";
	fin_page();
	exit;
}

if ($changer_config == 'oui') {

	// purger les squelettes si un changement de meta les affecte
	if ($post_dates AND ($post_dates != lire_meta("post_dates")))
		$purger_skel = true;

	$liste_meta = array(
		'activer_breves',
		'config_precise_groupes',
		'mots_cles_forums',
		'articles_surtitre',
		'articles_soustitre',
		'articles_descriptif',
		'articles_chapeau',
		'articles_ps',
		'articles_redac',
		'articles_mots',
		'post_dates',
		'creer_preview',
		'taille_preview',
		'activer_sites',
		'proposer_sites',
		'activer_syndic',
		'visiter_sites',
		'moderation_sites'
	);
	while (list(,$i) = each($liste_meta))
		if ($$i) ecrire_meta($i, $$i);
	ecrire_metas();	

	if ($purger_skel) {
		$hash = calculer_action_auteur("purger_squelettes");
		@header ("Location: ../spip_cache.php3?purger_squelettes=oui&id_auteur=$connect_id_auteur&hash=$hash&redirect=config-contenu.php3");
	}
}

lire_metas();


echo "<form action='config-contenu.php3' method='post'>";
echo "<input type='hidden' name='changer_config' value='oui'>";


debut_boite_info();

?>
<FONT FACE='Georgia,Garamond,Times,serif' SIZE=3>
<P align="center"><FONT COLOR='red'><B>ATTENTION !</B></FONT>

<P align="justify">
<img src="img_pack/warning.gif" alt="Avertissement" width="48" height="48" align="right">
Les modifications effectu&eacute;es ci-dessous influent notablement sur le
fonctionnement de votre site. Nous vous recommandons de ne pas y intervenir tant que vous n'&ecirc;tes pas parfaitement
familier du fonctionnement du syst&egrave;me SPIP. <P align="justify"><B>Plus g&eacute;n&eacute;ralement, il est <I>fortement conseill&eacute;</I>
de laisser la charge de cette page au webmestre principal de votre site.</B>
</FONT>

<?php

fin_boite_info();
echo "<P>";



debut_cadre_enfonce("article-24.gif");

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif' COLSPAN=2><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>LES ARTICLES</FONT></B></TD></TR>";
	echo "</table>";


//// Contenu des articles
debut_cadre_relief();

	$articles_surtitre = lire_meta("articles_surtitre");
	$articles_soustitre = lire_meta("articles_soustitre");
	$articles_descriptif = lire_meta("articles_descriptif");
	$articles_chapeau = lire_meta("articles_chapeau");
	$articles_ps = lire_meta("articles_ps");
	$articles_redac = lire_meta("articles_redac");
	$articles_mots = lire_meta("articles_mots");

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_claire' BACKGROUND='img_pack/rien.gif' COLSPAN=2><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='black'>Contenu des articles</FONT></B>".aide ("confart")."</TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' COLSPAN=2>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>Selon la maquette adopt&eacute;e pour votre site, vous pouvez d&eacute;cider que certains &eacute;l&eacute;ments des articles ne sont pas utilis&eacute;s. Utilisez la liste ci-dessous pour indiquer quels &eacute;l&eacute;ments sont disponibles.</FONT>";
	echo "</TD></TR>";

	echo "<TR>";
	echo "<TD BACKGROUND='img_pack/rien.gif' ALIGN='left'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
	echo "Surtitre :";
	echo "</FONT></TD>";
	echo "<TD BACKGROUND='img_pack/rien.gif' ALIGN='left'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
	if ($articles_surtitre == "non") {
		echo "<INPUT TYPE='radio' NAME='articles_surtitre' VALUE='oui' id='articles_surtitre_on'>";
		echo " <label for='articles_surtitre_on'>Oui</label> ";
		echo " &nbsp; <INPUT TYPE='radio' NAME='articles_surtitre' VALUE='non' CHECKED id='articles_surtitre_off'>";
		echo " <B><label for='articles_surtitre_off'>Non</label></B> ";
	}
	else {
		echo "<INPUT TYPE='radio' NAME='articles_surtitre' VALUE='oui' CHECKED id='articles_surtitre_on'>";
		echo " <B><label for='articles_surtitre_on'>Oui</label></B> ";
		echo " &nbsp; <INPUT TYPE='radio' NAME='articles_surtitre' VALUE='non' id='articles_surtitre_off'>";
		echo " <label for='articles_surtitre_off'>Non</label> ";
	}
	echo "</FONT>";
	echo "</TD></TR>";


	echo "<TR>";
	echo "<TD BACKGROUND='img_pack/rien.gif' ALIGN='left'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
	echo "Soustitre :";
	echo "</FONT></TD>";
	echo "<TD BACKGROUND='img_pack/rien.gif' ALIGN='left'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
	if ($articles_soustitre == "non") {
		echo "<INPUT TYPE='radio' NAME='articles_soustitre' VALUE='oui' id='articles_soustitre_on'>";
		echo " <label for='articles_soustitre_on'>Oui</label> ";
		echo " &nbsp; <INPUT TYPE='radio' NAME='articles_soustitre' VALUE='non' CHECKED id='articles_soustitre_off'>";
		echo " <B><label for='articles_soustitre_off'>Non</label></B> ";
	}
	else {
		echo "<INPUT TYPE='radio' NAME='articles_soustitre' VALUE='oui' CHECKED id='articles_soustitre_on'>";
		echo " <B><label for='articles_soustitre_on'>Oui</label></B> ";
		echo " &nbsp; <INPUT TYPE='radio' NAME='articles_soustitre' VALUE='non' id='articles_soustitre_off'>";
		echo " <label for='articles_soustitre_off'>Non</label> ";
	}
	echo "</FONT>";
	echo "</TD></TR>";

	echo "<TR>";
	echo "<TD BACKGROUND='img_pack/rien.gif' ALIGN='left'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
	echo "Descriptif :";
	echo "</FONT></TD>";
	echo "<TD BACKGROUND='img_pack/rien.gif' ALIGN='left'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
	if ($articles_descriptif == "non") {
		echo "<INPUT TYPE='radio' NAME='articles_descriptif' VALUE='oui' id='articles_descriptif_on'>";
		echo " <label for='articles_descriptif_on'>Oui</label> ";
		echo " &nbsp; <INPUT TYPE='radio' NAME='articles_descriptif' VALUE='non' CHECKED id='articles_descriptif_off'>";
		echo " <B><label for='articles_descriptif_off'>Non</label></B> ";
	}
	else {
		echo "<INPUT TYPE='radio' NAME='articles_descriptif' VALUE='oui' CHECKED id='articles_descriptif_on'>";
		echo " <B><label for='articles_descriptif_on'>Oui</label></B> ";
		echo " &nbsp; <INPUT TYPE='radio' NAME='articles_descriptif' VALUE='non' id='articles_descriptif_off'>";
		echo " <label for='articles_descriptif_off'>Non</label> ";
	}
	echo "</FONT>";
	echo "</TD></TR>";

	echo "<TR>";
	echo "<TD BACKGROUND='img_pack/rien.gif' ALIGN='left'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
	echo "Chapeau :";
	echo "</FONT></TD>";
	echo "<TD BACKGROUND='img_pack/rien.gif' ALIGN='left'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
	if ($articles_chapeau == "non") {
		echo "<INPUT TYPE='radio' NAME='articles_chapeau' VALUE='oui' id='articles_chapeau_on'>";
		echo " <label for='articles_chapeau_on'>Oui</label> ";
		echo " &nbsp; <INPUT TYPE='radio' NAME='articles_chapeau' VALUE='non' CHECKED id='articles_chapeau_off'>";
		echo " <B><label for='articles_chapeau_off'>Non</label></B> ";
	}
	else {
		echo "<INPUT TYPE='radio' NAME='articles_chapeau' VALUE='oui' CHECKED id='articles_chapeau_on'>";
		echo " <B><label for='articles_chapeau_on'>Oui</label></B> ";
		echo " &nbsp; <INPUT TYPE='radio' NAME='articles_chapeau' VALUE='non' id='articles_chapeau_off'>";
		echo " <label for='articles_chapeau_off'>Non</label> ";
	}
	echo "</FONT>";
	echo "</TD></TR>";


	echo "<TR>";
	echo "<TD BACKGROUND='img_pack/rien.gif' ALIGN='left'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
	echo "Post-scriptum :";
	echo "</FONT></TD>";
	echo "<TD BACKGROUND='img_pack/rien.gif' ALIGN='left'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
	if ($articles_ps == "non") {
		echo "<INPUT TYPE='radio' NAME='articles_ps' VALUE='oui' id='articles_ps_on'>";
		echo " <label for='articles_ps_on'>Oui</label> ";
		echo " &nbsp; <INPUT TYPE='radio' NAME='articles_ps' VALUE='non' CHECKED id='articles_ps_off'>";
		echo " <B><label for='articles_ps_off'>Non</label></B> ";
	}
	else {
		echo "<INPUT TYPE='radio' NAME='articles_ps' VALUE='oui' CHECKED id='articles_ps_on'>";
		echo " <B><label for='articles_ps_on'>Oui</label></B> ";
		echo " &nbsp; <INPUT TYPE='radio' NAME='articles_ps' VALUE='non' id='articles_ps_off'>";
		echo " <label for='articles_ps_off'>Non</label> ";
	}
	echo "</FONT>";
	echo "</TD></TR>";



	echo "<TR>";
	echo "<TD BACKGROUND='img_pack/rien.gif' ALIGN='left'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
	echo "Date de publication ant&eacute;rieure :";
	echo "</FONT></TD>";
	echo "<TD BACKGROUND='img_pack/rien.gif' ALIGN='left'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
	if ($articles_redac == "non") {
		echo "<INPUT TYPE='radio' NAME='articles_redac' VALUE='oui' id='articles_redac_on'>";
		echo " <label for='articles_redac_on'>Oui</label> ";
		echo " &nbsp; <INPUT TYPE='radio' NAME='articles_redac' VALUE='non' CHECKED id='articles_redac_off'>";
		echo " <B><label for='articles_redac_off'>Non</label></B> ";
	}
	else {
		echo "<INPUT TYPE='radio' NAME='articles_redac' VALUE='oui' CHECKED id='articles_redac_on'>";
		echo " <B><label for='articles_redac_on'>Oui</label></B> ";
		echo " &nbsp; <INPUT TYPE='radio' NAME='articles_redac' VALUE='non' id='articles_redac_off'>";
		echo " <label for='articles_redac_off'>Non</label> ";
	}
	echo "</FONT>";
	echo "</TD></TR>";

	echo "<TR><TD ALIGN='right' COLSPAN=2>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
	echo "</TD></TR>";
	echo "</TABLE>";

fin_cadre_relief();

//// Articles post-dates
debut_cadre_relief();

	$post_dates=lire_meta("post_dates");


	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_claire' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='black'>Publication des articles post-dat&eacute;s</FONT></B> ".aide ("confdates")."</TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>Quel comportement SPIP doit-il adopter face aux articles dont la date de publication a &eacute;t&eacute; fix&eacute;e &agrave; une &eacute;ch&eacute;ance future&nbsp;?</FONT>";
	echo "</TD></TR>";



	echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='left'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
	if ($post_dates == "non"){
		echo "<INPUT TYPE='radio' NAME='post_dates' VALUE='oui' id='post_dates_on'>";
		echo " <label for='post_dates_on'>Publier les articles, quelle que soit leur date de publication.</label> ";
		echo "<BR><INPUT TYPE='radio' NAME='post_dates' VALUE='non' CHECKED id='post_dates_off'>";
		echo " <B><label for='post_dates_off'>Ne pas publier les articles avant la date de publication fix&eacute;e.</label></B> ";
	}else{
		echo "<INPUT TYPE='radio' NAME='post_dates' VALUE='oui' id='post_dates_on' CHECKED>";
		echo " <B><label for='post_dates_on'>Publier les articles, quelle que soit leur date de publication.</label></B> ";
		echo "<BR><INPUT TYPE='radio' NAME='post_dates' VALUE='non' id='post_dates_off'>";
		echo " <label for='post_dates_off'>Ne pas publier les articles avant la date de publication fix&eacute;e.</label> ";
	}

	echo "</FONT>";
	echo "</TD></TR>\n";




	echo "<TR><TD ALIGN='right'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
	echo "</TD></TR>";
	echo "</TABLE>\n";

fin_cadre_relief();


if (function_exists("imagejpeg")){
//// Activer/desactiver creation automatique de vignettes
	debut_cadre_relief("image-24.gif");

	$gd_formats=lire_meta("gd_formats");

	$creer_preview=lire_meta("creer_preview");
	$taille_preview=lire_meta("taille_preview");
	if ($taille_preview < 15) $taille_preview = 120;


	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_claire' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='black'>Cr&eacute;ation automatique de vignettes de pr&eacute;visualisation</FONT></B></TD></TR>";
	echo "<TR><TD BACKGROUND='img_pack/rien.gif'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2>Lorsque vous installez des images en tant que document joint, SPIP peut cr&eacute;er pour vous, automatiquement, des vignettes de pr&eacute;visualisation. Cette option facilite, par exemple, la cr&eacute;ation d'un portfolio.</FONT>";
	echo "</TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='left'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
	if (strlen($gd_formats)>0){
		if ($creer_preview!="oui"){
			echo "<INPUT TYPE='radio' NAME='creer_preview' VALUE='oui' id='creer_preview_on'>";
			echo " <label for='creer_preview_on'>Cr&eacute;er automatiquement les vignettes de pr&eacute;visualisation.</label> ";
				echo "<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Taille maximale des vignettes&nbsp;:";
				echo " &nbsp;&nbsp;<INPUT TYPE='text' NAME='taille_preview' VALUE='$taille_preview' class='fondl' size=5>";
				echo " pixels";
			
			echo "<BR><INPUT TYPE='radio' NAME='creer_preview' VALUE='non' CHECKED id='creer_preview_off'>";
			echo " <B><label for='creer_preview_off'>Ne pas cr&eacute;er  de vignettes de pr&eacute;visualisation.</label></B> ";
		}else{
			echo "<INPUT TYPE='radio' NAME='creer_preview' VALUE='oui' CHECKED id='creer_preview_on'>";
			echo " <b><label for='creer_preview_on'>Cr&eacute;er automatiquement les vignettes de pr&eacute;visualisation.</label></b> ";
				echo "<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Taille maximale des vignettes&nbsp;:";
				echo " &nbsp;&nbsp;<INPUT TYPE='text' NAME='taille_preview' VALUE='$taille_preview' class='fondl' size=5>";
				echo " pixels";
			echo "<BR><INPUT TYPE='radio' NAME='creer_preview' VALUE='non' id='creer_preview_off'>";
			echo " <label for='creer_preview_off'>Ne pas cr&eacute;er  de vignettes de pr&eacute;visualisation.</label> ";
		}
		
	echo "<p>Formats d'images pouvant &ecirc;tre utilis&eacute;es pour cr&eacute;er des vignettes&nbsp;: $gd_formats.<p>";	
	}

	

		//Tester les formats acceptes par GD
		echo "<a href='../spip_image.php3?test_formats=oui&redirect=config-contenu.php3'>Tester les formats d'image que ce site peut utiliser pour cr&eacute;er des vignettes</a>";
		
	echo "</FONT>";
	echo "</TD></TR>\n";
	echo "<TR><TD ALIGN='right' COLSPAN=2>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
	echo "</TD></TR>";

	echo "</table>";

	fin_cadre_relief();
}
else {
	echo "<INPUT TYPE='hidden' NAME='creer_preview' VALUE='non'>";
	
}
fin_cadre_enfonce();




//// Actives/desactiver systeme de breves
debut_cadre_relief("breve-24.gif");

	$activer_breves=lire_meta("activer_breves");


	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Syst&egrave;me de br&egrave;ves</FONT></B> ".aide ("confbreves")."</TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>Votre site utilise-t-il le syst&egrave;me de br&egrave;ves&nbsp;?</FONT>";
	echo "</TD></TR>";



	echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='center'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
	if ($activer_breves=="non"){
		echo "<INPUT TYPE='radio' NAME='activer_breves' VALUE='oui' id='breves_on'>";
		echo " <label for='breves_on'>Utiliser les br&egrave;ves</label> ";
		echo " &nbsp; <INPUT TYPE='radio' NAME='activer_breves' VALUE='non' CHECKED id='breves_off'>";
		echo " <B><label for='breves_off'>Ne pas utiliser les br&egrave;ves</label></B> ";
	}else{
		echo "<INPUT TYPE='radio' NAME='activer_breves' VALUE='oui' id='breves_on' CHECKED>";
		echo " <B><label for='breves_on'>Utiliser les br&egrave;ves</label></B> ";
		echo " &nbsp; <INPUT TYPE='radio' NAME='activer_breves' VALUE='non' id='breves_off'>";
		echo " <label for='breves_off'>Ne pas utiliser les br&egrave;ves</label> ";
	}

	echo "</FONT>";
	echo "</TD></TR>\n";




	echo "<TR><TD ALIGN='right'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
	echo "</TD></TR>";
	echo "</TABLE>\n";

fin_cadre_relief();


//// Gestion des mots-cles
debut_cadre_relief("mot-cle-24.gif");

	$config_precise_groupes=lire_meta("config_precise_groupes");
	$mots_cles_forums=lire_meta("mots_cles_forums");


	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Les mots-cl&eacute;s</FONT></B> </TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>Souhaitez-vous utiliser les mots-cl&eacute;s sur votre site&nbsp;?</font></FONT>";
	echo "</TD></TR>";

	echo "<TR>";
	echo "<TD BACKGROUND='img_pack/rien.gif' ALIGN='left'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
	if ($articles_mots == "non") {
		echo "<INPUT TYPE='radio' NAME='articles_mots' VALUE='oui' id='articles_mots_on'>";
		echo " <label for='articles_mots_on'>Utiliser les mots-cl&eacute;s</label> ";
		echo "<br><INPUT TYPE='radio' NAME='articles_mots' VALUE='non' CHECKED id='articles_mots_off'>";
		echo " <B><label for='articles_mots_off'>Ne pas utiliser les mots-cl&eacute;s</label></B> ";
	}
	else {
		echo "<INPUT TYPE='radio' NAME='articles_mots' VALUE='oui' CHECKED id='articles_mots_on'>";
		echo " <B><label for='articles_mots_on'>Utiliser les mots-cl&eacute;s</label></B> ";
		echo "<br><INPUT TYPE='radio' NAME='articles_mots' VALUE='non' id='articles_mots_off'>";
		echo " <label for='articles_mots_off'>Ne pas utiliser les mots-cl&eacute;s</label> ";
	}
	echo "</FONT>";
	echo "</TD></TR>";


	if ($articles_mots != "non"){

		echo "<TR><TD>&nbsp;</TD></TR>";
		echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#000000'>Configuration des groupes de mots-cl&eacute;s</FONT></B></TD></TR>";

		echo "<TR><TD BACKGROUND='img_pack/rien.gif'>";
		echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>Souhaitez-vous configurer pr&eacute;cis&eacute;ment les mots-cl&eacute;s, en indiquant par exemple qu'on ne peut s&eacute;lectionner un unique mot-unique par groupe, qu'un groupe est important...&nbsp?</font></FONT>";
		echo "</TD></TR>";

		echo "<TR>";
		echo "<TD BACKGROUND='img_pack/rien.gif' ALIGN='left'>";
		echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
		if ($config_precise_groupes != "oui") {
			echo "<INPUT TYPE='radio' NAME='config_precise_groupes' VALUE='oui' id='config_precise_groupes_on'>";
			echo " <label for='config_precise_groupes_on'>Configurer pr&eacute;cis&eacute;ment</label> ";
			echo "<br><INPUT TYPE='radio' NAME='config_precise_groupes' VALUE='non' CHECKED id='config_precise_groupes_off'>";
			echo " <B><label for='config_precise_groupes_off'>Ne pas configurer pr&eacute;cis&eacute;ment</label></B> ";
		}
		else {
			echo "<INPUT TYPE='radio' NAME='config_precise_groupes' VALUE='oui' CHECKED id='config_precise_groupes_on'>";
			echo " <B><label for='config_precise_groupes_on'>Configurer pr&eacute;cis&eacute;ment</label></B> ";
			echo "<br><INPUT TYPE='radio' NAME='config_precise_groupes' VALUE='non' id='config_precise_groupes_off'>";
			echo " <label for='config_precise_groupes_off'>Ne pas configurer pr&eacute;cis&eacute;ment</label> ";
		}
		echo "</FONT>";
		echo "</TD></TR>";


		if ($forums_publics != "non"){
			echo "<TR><TD>&nbsp;</TD></TR>";
			echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#000000'>Mots-cl&eacute;s dans les forums du site public</FONT></B></TD></TR>";

			echo "<TR><TD BACKGROUND='img_pack/rien.gif'>";
			echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>Souhaitez-vous permettre d'utilisation des mots-cl&eacute;s, s&eacute;lectionnables par les visiteurs, dans les forums du site public&nbsp;? (Attention&nbsp;: cette option est relativement complexe &agrave; utiliser correctement sur son site.)</font></FONT>";
			echo "</TD></TR>";

			echo "<TR>";
			echo "<TD BACKGROUND='img_pack/rien.gif' ALIGN='left'>";
			echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
			if ($mots_cles_forums != "oui") {
				echo "<INPUT TYPE='radio' NAME='mots_cles_forums' VALUE='oui' id='mots_cles_forums_on'>";
				echo " <label for='mots_cles_forums_on'>Permettre l'utilisation des mots-cl&eacute;s dans les forums publics</label> ";
				echo "<br><INPUT TYPE='radio' NAME='mots_cles_forums' VALUE='non' CHECKED id='mots_cles_forums_off'>";
				echo " <B><label for='mots_cles_forums_off'>Interdire l'utilisation des mots-cl&eacute;s dans les forums publics</label></B> ";
			}
			else {
				echo "<INPUT TYPE='radio' NAME='mots_cles_forums' VALUE='oui' CHECKED id='mots_cles_forums_on'>";
				echo " <B><label for='mots_cles_forums_on'>Permettre l'utilisation des mots-cl&eacute;s dans les forums publics</label></B> ";
				echo "<br><INPUT TYPE='radio' NAME='mots_cles_forums' VALUE='non' id='mots_cles_forums_off'>";
				echo " <label for='mots_cles_forums_off'>Interdire l'utilisation des mots-cl&eacute;s dans les forums publics</label> ";
			}
			echo "</FONT>";
			echo "</TD></TR>";
		}

	}




	echo "<TR><TD ALIGN='right'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
	echo "</TD></TR>";
	echo "</TABLE>\n";

fin_cadre_relief();


//// Actives/desactiver systeme de syndication
debut_cadre_relief("site-24.gif");

	$activer_syndic=lire_meta("activer_syndic");
	$proposer_sites=lire_meta("proposer_sites");
	$visiter_sites=lire_meta("visiter_sites");

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Listes de sites r&eacute;f&eacute;renc&eacute;s et syndication</FONT></B> ".aide ("reference")."</TD></TR>";

	$activer_sites = lire_meta('activer_sites');

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='left'>";

	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>SPIP vous permet de cr&eacute;er des listes de sites r&eacute;f&eacute;renc&eacute;s (annuaires de liens).<p>";
	if ($activer_sites=="non"){
		echo "<INPUT TYPE='radio' NAME='activer_sites' VALUE='oui' id='sites_on'>";
		echo " <label for='sites_on'>G&eacute;rer un annuaire de sites</label> ";
		echo " <br><INPUT TYPE='radio' NAME='activer_sites' VALUE='non' CHECKED id='sites_off'>";
		echo " <B><label for='sites_off'>D&eacute;sactiver l'annuaire de sites</label></B> ";
	}else{
		echo "<INPUT TYPE='radio' NAME='activer_sites' VALUE='oui' id='sites_on' CHECKED>";
		echo " <B><label for='sites_on'>G&eacute;rer un annuaire de sites</label></B> ";
		echo " <br><INPUT TYPE='radio' NAME='activer_sites' VALUE='non' id='sites_off'>";
		echo " <label for='sites_off'>D&eacute;sactiver l'annuaire de sites</label> ";
	}

	echo "</FONT>";
	echo "</TD></TR>\n";

	if ($activer_sites <> 'non') {
		echo "<TR><TD BACKGROUND='img_pack/rien.gif'>";
		echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
		echo "<hr><p>Qui peut proposer des sites r&eacute;f&eacute;renc&eacute;s&nbsp;?";
			echo "<center><SELECT NAME='proposer_sites' CLASS='fondo' SIZE=1>\n";
				echo "<OPTION".mySel('0',$proposer_sites).">les administrateurs\n";
				echo "<OPTION".mySel('1',$proposer_sites).">les r&eacute;dacteurs\n";
				echo "<OPTION".mySel('2',$proposer_sites).">les visiteurs du site public\n";
			echo "</SELECT></center><P>\n";
		echo "</FONT>";
		echo "</TD></TR>";

		echo "<TR><TD BGCOLOR='EEEECC' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#000000'>Syndication de sites</FONT></B> ".aide ("rubsyn")."</TD></TR>";

		echo "<TR><TD BACKGROUND='img_pack/rien.gif'>";
		echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>Il est possible de r&eacute;cup&eacute;rer, pour chaque site r&eacute;f&eacute;renc&eacute; (lorsque ce site le permet), la liste de ses derni&egrave;res publications. Pour cela, vous devez activer la syndication de SPIP. <font color='red'>Certains h&eacute;bergeurs interdisent la consultation de sites externes depuis leurs machines&nbsp;; dans ce cas, vous ne pourrez pas utiliser la syndication de contenu depuis votre site.</font> <p>Votre site utilise-t-il le syst&egrave;me de syndication de sites&nbsp;?</FONT>";
		echo "</TD></TR>";

		echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='left'>";
		echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
		if ($activer_syndic == "non") {
			echo "<p align='center'><INPUT TYPE='radio' NAME='activer_syndic' VALUE='oui' id='syndic_on'>";
			echo " <label for='syndic_on'>Utiliser la syndication</label> ";
			echo " &nbsp; <INPUT TYPE='radio' NAME='activer_syndic' VALUE='non' CHECKED id='syndic_off'>";
			echo " <B><label for='syndic_off'>Ne pas utiliser la syndication</label></B> ";
		}
		else {
			echo "<p align='center'><INPUT TYPE='radio' NAME='activer_syndic' VALUE='oui' id='syndic_on' CHECKED>";
			echo " <B><label for='syndic_on'>Utiliser la syndication</label></B> ";
			echo " &nbsp; <INPUT TYPE='radio' NAME='activer_syndic' VALUE='non' id='syndic_off'>";
			echo " <label for='syndic_off'>Ne pas utiliser la syndication</label> ";

			// Moderation par defaut des sites syndiques
			echo "<p><hr><p align='left'>";
			echo propre("Les liens issus des sites syndiqu&eacute;s peuvent
				&ecirc;tre bloqu&eacute;s a priori ; le r&eacute;glage
				ci-dessous indique le r&eacute;glage par d&eacute;faut des
				sites syndiqu&eacute;s apr&egrave;s leur cr&eacute;ation. Il
				est ensuite possible, de toutes fa&ccedil;ons, de
				d&eacute;bloquer chaque lien individuellement, ou de
				choisir, site par site, de bloquer les liens &agrave; venir
				de tel ou tel site.");
			if (lire_meta("moderation_sites") == 'oui') {
				echo "<p align='center'><INPUT TYPE='radio' NAME='moderation_sites' VALUE='oui' id='mod_syndic_on' CHECKED>";
				echo " <B><label for='mod_syndic_on'>Bloquer les liens a priori</label></B> ";
				echo " &nbsp; <INPUT TYPE='radio' NAME='moderation_sites' VALUE='non' id='mod_syndic_off'>";
				echo " <label for='mod_syndic_off'>Ne pas bloquer</label> ";
			} else {
				echo "<p align='center'><INPUT TYPE='radio' NAME='moderation_sites' VALUE='oui' id='mod_syndic_on'>";
				echo " <label for='mod_syndic_on'>Bloquer les liens a priori</label> ";
				echo " &nbsp; <INPUT TYPE='radio' NAME='moderation_sites' VALUE='non' id='mod_syndic_off' CHECKED>";
				echo " <B><label for='mod_syndic_off'>Ne pas bloquer</label></B> ";
			}

			// Si indexation, activer/desactiver pages recuperees

			$activer_moteur = lire_meta("activer_moteur");
			if ($activer_moteur == "oui") {
				echo "<p><hr><p align='left'>"; 
				echo "Lorsque vous utilisez le moteur de recherche int&eacute;gr&eacute; &agrave; SPIP, vous pouvez effectuer les recherches ".
					"sur les sites et les articles syndiqu&eacute;s de deux mani&egrave;res diff&eacute;rentes. <br><img src='puce.gif'> La plus ".
					"simple consiste &agrave; rechercher uniquement dans les titres et les descriptifs des articles. <br><img src='puce.gif'> Une seconde ".
					"m&eacute;thode, beaucoup plus puissante, permet &agrave; SPIP de rechercher &eacute;galement dans le texte des sites r&eacute;f&eacute;renc&eacute;s&nbsp;. ".
					"Si vous r&eacute;f&eacute;rencez un site, SPIP va alors effectuer la recherche dans le texte du site lui-m&ecirc;me. ";
				echo "<font color='red'>Cette m&eacute;thode oblige SPIP &agrave; visiter r&eacute;guli&egrave;rement les sites r&eacute;f&eacute;renc&eacute;s,
					ce qui peut provoquer un l&eacute;ger ralentissement de votre propre site.</font>";

				if ($visiter_sites == "oui") {
					echo "<p><INPUT TYPE='radio' NAME='visiter_sites' VALUE='non' id='visiter_off'>";
					echo " <label for='visiter_off'>Recherche limit&eacute;e aux informations de votre site</label> ";
					echo "<br><INPUT TYPE='radio' NAME='visiter_sites' VALUE='oui' id='visiter_on' CHECKED>";
					echo " <B><label for='visiter_on'>Recherche en utilisant le contenu des sites r&eacute;f&eacute;renc&eacute;s</label></B> ";
				}
				else {
					echo "<p><INPUT TYPE='radio' NAME='visiter_sites' VALUE='non' id='visiter_off' CHECKED>";
					echo " <b><label for='visiter_off'>Recherche limit&eacute;e aux informations de votre site</label></b> ";
					echo "<br><INPUT TYPE='radio' NAME='visiter_sites' VALUE='oui' id='visiter_on'>";
					echo " <label for='visiter_on'>Recherche en utilisant le contenu des sites r&eacute;f&eacute;renc&eacute;s</label> ";
				}
			}
			else {
				echo "<INPUT TYPE='hidden' NAME='visiter_sites' VALUE='$visiter_sites'>";
			}
		}

		echo "</FONT>";
		echo "</TD></TR>\n";
	}

	echo "<TR><TD ALIGN='right'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
	echo "</TD></TR>";
	echo "</TABLE>\n";

fin_cadre_relief();

echo "</form>";

fin_page();

?>
