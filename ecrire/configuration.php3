<?php

include ("inc.php3");


function mySel($varaut,$variable){
		$retour= " VALUE=\"$varaut\"";

	if ($variable==$varaut){
		$retour.= " SELECTED";
	}

	return $retour;
}


debut_page("Configuration du site");
debut_gauche();

debut_droite();

if ($connect_statut != '0minirezo' OR !$connect_toutes_rubriques) {
	echo "Vous n'avez pas acc&egrave;s &agrave; cette page.";
	fin_page();
	exit;
}

if ($changer_config == 'oui') {

	// purger les squelettes si un changement de meta les affecte
	if (($post_dates AND ($post_dates != lire_meta("post_dates")))
		OR ($forums_publics AND ($forums_publics != lire_meta("forums_publics"))))
		$purger_skel = true;

	// appliquer les changements de moderation forum
	// forums_publics_appliquer : futur, saufnon, tous
	$requete_appliquer = '';
	$accepter_forum = substr($forums_publics,0,3);
	if ($forums_publics_appliquer == 'saufnon') {
		$requete_appliquer = "UPDATE spip_articles SET accepter_forum='$accepter_forum' WHERE NOT (accepter_forum='non')";
	} else if ($forums_publics_appliquer == 'tous') {
		$requete_appliquer = "UPDATE spip_articles SET accepter_forum='$accepter_forum'";
	}
	if ($requete_appliquer) mysql_query($requete_appliquer);


	$adresse_site = ereg_replace("/$", "", $adresse_site);

	ecrire_meta("nom_site", $nom_site);
	ecrire_meta("adresse_site", $adresse_site);
	ecrire_meta("activer_moteur", $activer_moteur);
	ecrire_meta("activer_breves", $activer_breves);
	ecrire_meta("config_precise_groupes", $config_precise_groupes);
	ecrire_meta("mots_cles_forums", $mots_cles_forums);
	ecrire_meta("activer_syndic", $activer_syndic);
	ecrire_meta("visiter_sites", $visiter_sites);
	ecrire_meta("taille_index", $taille_index);
	ecrire_meta("proposer_sites", $proposer_sites);
	ecrire_meta("articles_surtitre", $articles_surtitre);
	ecrire_meta("articles_soustitre", $articles_soustitre);
	ecrire_meta("articles_descriptif", $articles_descriptif);
	ecrire_meta("articles_chapeau", $articles_chapeau);
	ecrire_meta("articles_ps", $articles_ps);
	ecrire_meta("articles_redac", $articles_redac);
	ecrire_meta("articles_mots", $articles_mots);
	ecrire_meta("activer_statistiques", $activer_statistiques);
	ecrire_meta("prevenir_auteurs", $prevenir_auteurs);
	ecrire_meta("post_dates", $post_dates);
	ecrire_meta("activer_messagerie", $activer_messagerie);
	ecrire_meta("activer_imessage", $activer_imessage);
	ecrire_meta("accepter_inscriptions", $accepter_inscriptions);
	ecrire_meta("forums_publics","$forums_publics");
	ecrire_meta("creer_preview", $creer_preview);
	ecrire_meta("taille_preview", $taille_preview);

	ecrire_meta("suivi_edito", $suivi_edito);
	if ($adresse_suivi) ecrire_meta("adresse_suivi", $adresse_suivi);

	ecrire_meta("quoi_de_neuf", $quoi_de_neuf);
	if ($adresse_neuf) ecrire_meta("adresse_neuf", $adresse_neuf);
	if ($jours_neuf) ecrire_meta("jours_neuf", $jours_neuf);

	ecrire_metas();	

	if ($purger_skel) {
		$hash = calculer_action_auteur("purger_squelettes");
		$redirect = rawurlencode("configuration.php3");
		@header ("Location: ../spip_cache.php3?purger_squelettes=oui&id_auteur=$connect_id_auteur&hash=$hash&redirect=$redirect");
	}
}
else {
	$forums_publics = lire_meta("forums_publics");
	if (!$forums_publics) {
		ecrire_meta("forums_publics", "posteriori");
		ecrire_metas();
	}
}

lire_metas();


echo "<form action='configuration.php3' method='post'>";
echo "<input type='hidden' name='changer_config' value='oui'>";
debut_cadre_relief();

	$nom_site = htmlspecialchars(lire_meta("nom_site"));
	$adresse_site = htmlspecialchars(lire_meta("adresse_site"));

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='IMG2/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Nom de votre site</FONT></B> ".aide ("confnom")."</TD></TR>";

	echo "<TR><TD BACKGROUND='IMG2/rien.gif'>";
	echo "<input type='text' name='nom_site' value=\"$nom_site\" size='40' CLASS='forml'>";
	echo "</TD></TR>";

	echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='IMG2/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#000000'>Adresse (URL) racine de votre site</FONT></B></TD></TR>";

	echo "<TR><TD BACKGROUND='IMG2/rien.gif'>";
	echo "<input type='text' name='adresse_site' value=\"$adresse_site\" size='40' CLASS='forml'>";
	echo "</TD></TR>";


	echo "<TR><TD ALIGN='right'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
	echo "</TD></TR>";
	echo "</TABLE>";

fin_cadre_relief();



debut_boite_info();

?>
<FONT FACE='Georgia,Garamond,Times,serif' SIZE=3>
<P align="center"><FONT COLOR='red'><B>ATTENTION !</B></FONT>

<P align="justify">Les modifications effectu&eacute;es ci-dessous influent notablement sur le
fonctionnement de votre site. Nous vous recommandons de ne pas y intervenir tant que vous n'&ecirc;tes pas parfaitement
familier du fonctionnement du syst&egrave;me SPIP. <P align="justify"><B>Plus g&eacute;n&eacute;ralement, il est <I>fortement conseill&eacute;</I>
de laisser la charge de cette page au webmestre principal de votre site.</B>
</FONT>

<?php

fin_boite_info();
echo "<P>";




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
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='IMG2/rien.gif' COLSPAN=2><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Contenu des articles</FONT></B>".aide ("confart")."</TD></TR>";

	echo "<TR><TD BACKGROUND='IMG2/rien.gif' COLSPAN=2>";
	echo "<img src='IMG2/article.gif' alt='' width='31' height=31 border=0 align=left>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>Selon la maquette adopt&eacute;e pour votre site, vous pouvez d&eacute;cider que certains &eacute;l&eacute;ments des articles ne sont pas utilis&eacute;s. Utilisez la liste ci-dessous pour indiquer quels &eacute;l&eacute;ments sont disponibles.</FONT>";
	echo "</TD></TR>";

	echo "<TR>";
	echo "<TD BACKGROUND='IMG2/rien.gif' ALIGN='left'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
	echo "Surtitre :";
	echo "</FONT></TD>";
	echo "<TD BACKGROUND='IMG2/rien.gif' ALIGN='left'>";
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
	echo "<TD BACKGROUND='IMG2/rien.gif' ALIGN='left'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
	echo "Soustitre :";
	echo "</FONT></TD>";
	echo "<TD BACKGROUND='IMG2/rien.gif' ALIGN='left'>";
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
	echo "<TD BACKGROUND='IMG2/rien.gif' ALIGN='left'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
	echo "Descriptif :";
	echo "</FONT></TD>";
	echo "<TD BACKGROUND='IMG2/rien.gif' ALIGN='left'>";
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
	echo "<TD BACKGROUND='IMG2/rien.gif' ALIGN='left'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
	echo "Chapeau :";
	echo "</FONT></TD>";
	echo "<TD BACKGROUND='IMG2/rien.gif' ALIGN='left'>";
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
	echo "<TD BACKGROUND='IMG2/rien.gif' ALIGN='left'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
	echo "Post-scriptum :";
	echo "</FONT></TD>";
	echo "<TD BACKGROUND='IMG2/rien.gif' ALIGN='left'>";
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
	echo "<TD BACKGROUND='IMG2/rien.gif' ALIGN='left'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
	echo "Date de publication ant&eacute;rieure :";
	echo "</FONT></TD>";
	echo "<TD BACKGROUND='IMG2/rien.gif' ALIGN='left'>";
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





//// Fonctionnement de la messagerie interne
debut_cadre_relief();

	$activer_messagerie=lire_meta("activer_messagerie");
	$activer_imessage=lire_meta("activer_imessage");


	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='IMG2/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Messagerie interne</FONT></B> ".aide ("confmessagerie")." </TD></TR>";
	echo "<TR><TD BACKGROUND='IMG2/rien.gif'>";
	echo "<img src='IMG2/m_sans.gif' alt='' width='32' height='32' border='0' align='left'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2>SPIP permet l'&eacute;change de messages et la constitution de forums de discussion priv&eacute;s entre les participants du site. Vous pouvez activer ou d&eacute;sactiver cette fonctionnalit&eacute;.</FONT>";
	echo "</TD></TR>";



	// Activer/d&eacute;sactiver l'int&eacute;gralit&eacute; de la messagerie
	echo "<TR><TD>&nbsp;</TD></TR>";

	echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='IMG2/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>Messagerie interne</FONT></B></TD></TR>";

	echo "<TR><TD BACKGROUND='IMG2/rien.gif'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>Vous pouvez activer ou d&eacute;sactiver l'int&eacute;gralit&eacute; du syst&egrave;me de messagerie.</FONT>";
	echo "</TD></TR>";



	echo "<TR><TD BACKGROUND='IMG2/rien.gif' ALIGN='left'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
	if ($activer_messagerie=="non"){
		echo "<INPUT TYPE='radio' NAME='activer_messagerie' VALUE='oui' id='activer_messagerie_on'>";
		echo " <label for='activer_messagerie_on'>Activer la messagerie interne</label> ";
		echo "<BR><INPUT TYPE='radio' NAME='activer_messagerie' VALUE='non' CHECKED id='activer_messagerie_off'>";
		echo " <B><label for='activer_messagerie_off'>D&eacute;sactiver la messagerie</label></B> ";
	}else{
		echo "<INPUT TYPE='radio' NAME='activer_messagerie' VALUE='oui' id='activer_messagerie_on' CHECKED>";
		echo " <B><label for='activer_messagerie_on'>Activer la messagerie interne</label></B> ";
		echo "<BR><INPUT TYPE='radio' NAME='activer_messagerie' VALUE='non' id='activer_messagerie_off'>";
		echo " <label for='activer_messagerie_off'>D&eacute;sactiver la messagerie</label> ";
	}

	echo "</FONT>";
	echo "</TD></TR>\n";


	if ($activer_messagerie!="non"){
		/// Liste des redacteurs connectes
			
		echo "<TR><TD>&nbsp;</TD></TR>";
		echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='IMG2/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>Liste des r&eacute;dacteurs connect&eacute;s</FONT></B></TD></TR>";

		echo "<TR><TD BACKGROUND='IMG2/rien.gif'>";
		echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>Lorsque le syst&egrave;me de messagerie est activ&eacute;, SPIP peut vous indiquer en permanence la liste des r&eacute;dacteurs connect&eacute;s, ce qui vous permet d'&eacute;changer des messages en direct (lorsque la messagerie est d&eacute;sactiv&eacute;e ci-dessus, la liste des r&eacute;dacteurs est elle-m&ecirc;me d&eacute;sactiv&eacute;e). Cette fonctionnalit&eacute;, qui favorise l'apparition de <i>chats</i> entre r&eacute;dacteurs, peut &ecirc;tre lourde &agrave; supporter par certains serveurs. Vous pouvez donc la d&eacute;sactiver. </FONT>";
		echo "</TD></TR>";



		echo "<TR><TD BACKGROUND='IMG2/rien.gif' ALIGN='left'>";
		echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
		if ($activer_imessage=="non"){
			echo "<INPUT TYPE='radio' NAME='activer_imessage' VALUE='oui' id='activer_imessage_on'>";
			echo " <label for='activer_imessage_on'>Afficher la liste des r&eacute;dacteurs connect&eacute;s</label> ";
			echo "<BR><INPUT TYPE='radio' NAME='activer_imessage' VALUE='non' CHECKED id='activer_imessage_off'>";
			echo " <B><label for='activer_imessage_off'>Ne pas afficher la liste des r&eacute;dacteurs</label></B> ";
		}else{
			echo "<INPUT TYPE='radio' NAME='activer_imessage' VALUE='oui' id='activer_imessage_on' CHECKED>";
			echo " <B><label for='activer_imessage_on'>Afficher la liste des r&eacute;dacteurs connect&eacute;s</label></B> ";

			echo "<BR><INPUT TYPE='radio' NAME='activer_imessage' VALUE='non' id='activer_imessage_off'>";
			echo " <label for='activer_imessage_off'>Ne pas afficher la liste des r&eacute;dacteurs</label> ";
		}

		echo "</FONT>";
		echo "</TD></TR>\n";
	}

	echo "<TR><TD ALIGN='right'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
	echo "</TD></TR>";
	echo "</TABLE>\n";

fin_cadre_relief();







//// Mode de fonctionnement des forums publics
debut_cadre_relief();

	$forums_publics=lire_meta("forums_publics");


	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='IMG2/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Mode de fonctionnement par d&eacute;faut des forums publics</FONT></B> ".aide ("confforums")."</TD></TR>";


/*	echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='IMG2/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#000000'>D&eacute;sactiver les forums publics</FONT></B></TD></TR>"; */

	echo "<TR><TD BACKGROUND='IMG2/rien.gif' ALIGN='left'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
	if ($forums_publics=="non") {
		$checked = ' CHECKED';
		$gras = '<b>'; $fingras = '</b>';
	} else {
		$checked = '';
		$gras = ''; $fingras = '';
	};
	echo "<INPUT$checked TYPE='radio' NAME='forums_publics' VALUE='non' id='forums_non'>";
	echo " $gras<label for='forums_non'>D&eacute;sactiver l'utilisation des forums publics. Les forums publics pourront &ecirc;tre autoris&eacute;s au cas par cas sur les articles ; ils seront interdits sur les rubriques, br&egrave;ves, etc. </label>$fingras ";
	echo "</TD></TR>";

	echo "<TR><TD BACKGROUND='IMG2/rien.gif'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'><i>Pour activer les forums publics, veuillez choisir leur mode de mod&eacute;ration par d&eacute;faut :</i></FONT>";
	echo "</TD></TR>";

	echo "<TR><TD BACKGROUND='IMG2/rien.gif' ALIGN='left'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";


	if ($forums_publics=="posteriori") {
		$checked = ' CHECKED';
		$gras = '<b>'; $fingras = '</b>';
	} else {
		$checked = '';
		$gras = ''; $fingras = '';
	};
	echo "<INPUT TYPE='radio'$checked NAME='forums_publics' VALUE='posteriori' id='forums_posteriori'>";
	echo " $gras<label for='forums_posteriori'>Mod&eacute;ration &agrave; post&eacute;riori (les contributions s'affichent imm&eacute;diatement en ligne, les administrateurs peuvent les supprimer ensuite).</label>$fingras\n<br>";

	if ($forums_publics=="priori") {
		$checked = ' CHECKED';
		$gras = '<b>'; $fingras = '</b>';
	} else {
		$checked = '';
		$gras = ''; $fingras = '';
	};
	echo "<INPUT TYPE='radio'$checked NAME='forums_publics' VALUE='priori' id='forums_priori'>";
	echo " $gras<label for='forums_priori'>Mod&eacute;ration &agrave; priori (les contributions ne s'affichent publiquement qu'apr&egrave;s validation par les administrateurs).</label>$fingras ";
		
	if (tester_mail()){
		echo "\n<BR>";
		if ($forums_publics=="abonnement") {
			$checked = ' CHECKED';
			$gras = '<b>'; $fingras = '</b>';
		} else {
			$checked = '';
			$gras = ''; $fingras = '';
		};
		echo "<INPUT TYPE='radio'$checked NAME='forums_publics' VALUE='abonnement' id='forums_abonnement'>";
		echo " $gras<label for='forums_abonnement'>Sur abonnement (les utilisateurs doivent fournir leur adresse email avant de pouvoir poster des contributions).</label>$fingras ";
	}

	echo "</FONT>";
	echo "</TD></TR>\n";

	echo "<TR><TD BACKGROUND='IMG2/rien.gif' ALIGN='left'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";

	echo "<center><table width='100%' cellpadding='2' border='1' class='hauteur'>\n";
	echo "<tr><td width='100%' bgcolor='#FFCC66'>\n";
	echo "<font face='Verdana,Arial,Helvetica,sans-serif' size='2' color='#333333'><b>\n";
	echo bouton_block_invisible("forumappliquer");
	echo "OPTIONS AVANC&Eacute;ES";
	echo "</b></font></td></tr>";
	echo debut_block_invisible("forumappliquer");
	echo "<tr><td><font face='Verdana,Arial,Helvetica,sans-serif' size='2'>";
	echo "Appliquer ce choix de mod&eacute;ration :<br>";

	echo "<INPUT TYPE='radio' CHECKED NAME='forums_publics_appliquer' VALUE='futur' id='forums_appliquer_futur'>";
	echo " <b><label for='forums_appliquer_futur'>aux articles futurs uniquement (pas d'action sur la base de donn&eacute;es).</label></b><br>";
	echo "<INPUT TYPE='radio' NAME='forums_publics_appliquer' VALUE='saufnon' id='forums_appliquer_saufnon'>";
	echo " <label for='forums_appliquer_saufnon'>&agrave; tous les articles, sauf ceux dont le forum est d&eacute;sactiv&eacute;.</label><br>";
	echo "<INPUT TYPE='radio' NAME='forums_publics_appliquer' VALUE='tous' id='forums_appliquer_tous'>";
	echo " <label for='forums_appliquer_tous'>&agrave; tous les articles sans exception.</label><br>";
	echo "</FONT>";
	echo "</TD></TR>\n";
	echo fin_block("forumappliquer");
	echo "</table></center>";

	echo "<TR><TD ALIGN='right'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
	echo "</TD></TR>";
	echo "</TABLE>\n";

fin_cadre_relief();


//// Gestion des mots-cles
debut_cadre_relief();

	$accepter_inscriptions=lire_meta("accepter_inscriptions");
	$config_precise_groupes=lire_meta("config_precise_groupes");
	$mots_cles_forums=lire_meta("mots_cles_forums");


	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='IMG2/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Les mots-cl&eacute;s</FONT></B> </TD></TR>";

	echo "<TR><TD BACKGROUND='IMG2/rien.gif'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>Souhaitez-vous utiliser les mots-cl&eacute;s sur votre site&nbsp;?</font></FONT>";
	echo "</TD></TR>";

	echo "<TR>";
	echo "<TD BACKGROUND='IMG2/rien.gif' ALIGN='left'>";
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
		echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='IMG2/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#000000'>Configuration des groupes de mots-cl&eacute;s</FONT></B></TD></TR>";

		echo "<TR><TD BACKGROUND='IMG2/rien.gif'>";
		echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>Souhaitez-vous configurer pr&eacute;cis&eacute;ment les mots-cl&eacute;s, en indiquant par exemple qu'on ne peut s&eacute;lectionner un unique mot-unique par groupe, qu'un groupe est important...&nbsp?</font></FONT>";
		echo "</TD></TR>";

		echo "<TR>";
		echo "<TD BACKGROUND='IMG2/rien.gif' ALIGN='left'>";
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
			echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='IMG2/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#000000'>Mots-cl&eacute;s dans les forums du site public</FONT></B></TD></TR>";

			echo "<TR><TD BACKGROUND='IMG2/rien.gif'>";
			echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>Souhaitez-vous permettre d'utilisation des mots-cl&eacute;s, s&eacute;lectionnables par les visiteurs, dans les forums du site public&nbsp;? (Attention&nbsp;: cette option est relativement complexe &agrave; utiliser correctement sur son site.)</font></FONT>";
			echo "</TD></TR>";

			echo "<TR>";
			echo "<TD BACKGROUND='IMG2/rien.gif' ALIGN='left'>";
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






//// Accepter les inscriptions de redacteurs depuis le site public
debut_cadre_relief();

	$accepter_inscriptions=lire_meta("accepter_inscriptions");


	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='IMG2/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Inscription automatique de nouveaux r&eacute;dacteurs</FONT></B> </TD></TR>";

	echo "<TR><TD BACKGROUND='IMG2/rien.gif'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>Acceptez-vous les inscriptions de nouveaux r&eacute;dacteurs &agrave; partir du site public&nbsp;? Si vous acceptez, les visiteurs pourront s'inscrire automatiquement, et acc&eacute;deront alors &agrave; l'espace priv&eacute; pour proposer leurs propres articles. <font color='red'>Lors de la phase d'inscription, les utilisateurs re&ccedil;oivent un courrier &eacute;lectronique automatique leur fournissant leurs codes d'acc&egrave;s au site priv&eacute;. Certains h&eacute;bergeurs d&eacute;sactivent l'envoi de mails depuis leurs serveurs&nbsp;: dans ce cas, l'inscription automatique est impossible.</font></FONT>";
	echo "</TD></TR>";



	echo "<TR><TD BACKGROUND='IMG2/rien.gif' ALIGN='center'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
	if ($accepter_inscriptions!="oui"){
		echo "<INPUT TYPE='radio' NAME='accepter_inscriptions' VALUE='oui' id='inscriptions_on'>";
		echo " <label for='inscriptions_on'>Accepter les inscriptions</label> ";
		echo " &nbsp; <INPUT TYPE='radio' NAME='accepter_inscriptions' VALUE='non' CHECKED id='inscriptions_off'>";
		echo " <B><label for='inscriptions_off'>Ne pas accepter les inscriptions</label></B> ";
	}else{
		echo "<INPUT TYPE='radio' NAME='accepter_inscriptions' VALUE='oui' id='inscriptions_on' CHECKED>";
		echo " <B><label for='inscriptions_on'>Accepter les inscriptions</label></B> ";
		echo " &nbsp; <INPUT TYPE='radio' NAME='accepter_inscriptions' VALUE='non' id='inscriptions_off'>";
		echo " <label for='inscriptions_off'>Ne pas accepter les inscriptions</label> ";
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
	debut_cadre_relief();

	$creer_preview=lire_meta("creer_preview");
	$taille_preview=lire_meta("taille_preview");
	if ($taille_preview < 15) $taille_preview = 120;


	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='IMG2/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Cr&eacute;ation automatique de vignettes de pr&eacute;visualisation</FONT></B></TD></TR>";
	echo "<TR><TD BACKGROUND='IMG2/rien.gif'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2>Lorsque vous installez des images au format JPEG en tant que document joint, SPIP peut cr&eacute;er pour vous, automatiquement, des vignettes de pr&eacute;visualisation. Cette option facilite, par exemple, la cr&eacute;ation d'un portfolio.</FONT>";
	echo "</TD></TR>";

	echo "<TR><TD BACKGROUND='IMG2/rien.gif' ALIGN='left'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
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


//// Articles post-dates
debut_cadre_relief();

	$post_dates=lire_meta("post_dates");


	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='IMG2/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Publication des articles post-dat&eacute;s</FONT></B> ".aide ("confdates")."</TD></TR>";

	echo "<TR><TD BACKGROUND='IMG2/rien.gif'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>Quel comportement SPIP doit-il adopter face aux articles dont la date de publication a &eacute;t&eacute; fix&eacute;e &agrave; une &eacute;ch&eacute;ance future&nbsp;?</FONT>";
	echo "</TD></TR>";



	echo "<TR><TD BACKGROUND='IMG2/rien.gif' ALIGN='left'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
	if ($post_dates!="oui"){
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




//// Actives/desactiver systeme de breves
debut_cadre_relief();

	$activer_breves=lire_meta("activer_breves");


	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='IMG2/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Syst&egrave;me de br&egrave;ves</FONT></B> ".aide ("confbreves")."</TD></TR>";

	echo "<TR><TD BACKGROUND='IMG2/rien.gif'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>Votre site utilise-t-il le syst&egrave;me de br&egrave;ves&nbsp;?</FONT>";
	echo "</TD></TR>";



	echo "<TR><TD BACKGROUND='IMG2/rien.gif' ALIGN='center'>";
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



//// Actives/desactiver systeme de syndication
debut_cadre_relief();

	$activer_syndic=lire_meta("activer_syndic");
	$proposer_sites=lire_meta("proposer_sites");
	$visiter_sites=lire_meta("visiter_sites");

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='IMG2/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Listes de sites r&eacute;f&eacute;renc&eacute;s et syndication</FONT></B> ".aide ("rubsyn")."</TD></TR>";

	echo "<TR><TD BACKGROUND='IMG2/rien.gif'>";
	echo "<img src=\"IMG2/sites.gif\" alt=\"\" width=\"28\" height=\"27\" hspace=\"5\" border=\"0\" align=\"left\">";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>SPIP vous permet de cr&eacute;er des listes de sites r&eacute;f&eacute;renc&eacute;s (annuaire de liens).";


	echo "<p>Qui peut proposer des sites r&eacute;f&eacute;renc&eacute;s&nbsp;?";
		echo "<center><SELECT NAME='proposer_sites' CLASS='fondo' SIZE=1>\n";
			echo "<OPTION".mySel('0',$proposer_sites).">les administrateurs\n";
			echo "<OPTION".mySel('1',$proposer_sites).">les r&eacute;dacteurs\n";
			echo "<OPTION".mySel('2',$proposer_sites).">les visiteurs du site public\n";
		echo "</SELECT></center><P>\n";



	echo "</FONT>";
	echo "</TD></TR>";


	echo "<TR><TD BGCOLOR='EEEECC' BACKGROUND='IMG2/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#000000'>Syndication de sites</FONT></B> ".aide ("rubsyn")."</TD></TR>";

	echo "<TR><TD BACKGROUND='IMG2/rien.gif'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>Il est possible de r&eacute;cup&eacute;rer, pour chaque site r&eacute;f&eacute;renc&eacute; (lorsque ce site le permet), la liste de ses derni&egrave;res publications. Pour cela, vous devez activer la syndication de SPIP. <font color='red'>Certains h&eacute;bergeurs interdisent la consultation de sites externes depuis leurs machines&nbsp;; dans ce cas, vous ne pourrez pas utiliser la syndication de contenu depuis votre site.</font> <p>Votre site utilise-t-il le syst&egrave;me de syndication de sites&nbsp;?</FONT>";
	echo "</TD></TR>";


	echo "<TR><TD BACKGROUND='IMG2/rien.gif' ALIGN='left'>";
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


	echo "<TR><TD ALIGN='right'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
	echo "</TD></TR>";
	echo "</TABLE>\n";

fin_cadre_relief();





//// Actives/desactiver les statistiques
debut_cadre_relief();

	$activer_statistiques=lire_meta("activer_statistiques");


	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='IMG2/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Statistiques des visites</FONT></B> ".aide ("confstat")."</TD></TR>";

	echo "<TR><TD BACKGROUND='IMG2/rien.gif'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>Votre site doit-il g&eacute;rer les statistiques des visites&nbsp;?</FONT>";
	echo "</TD></TR>";



	echo "<TR><TD BACKGROUND='IMG2/rien.gif' ALIGN='center'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
	if ($activer_statistiques=="non"){
		echo "<INPUT TYPE='radio' NAME='activer_statistiques' VALUE='oui' id='statistiques_on'>";
		echo " <label for='statistiques_on'>G&eacute;rer les statistiques</label> ";
		echo " &nbsp; <INPUT TYPE='radio' NAME='activer_statistiques' VALUE='non' id='statistiques_off' CHECKED>";
		echo " <B><label for='statistiques_off'>Ne pas g&eacute;rer les statistiques</label></B> ";
	}else{
		echo "<INPUT TYPE='radio' NAME='activer_statistiques' VALUE='oui' id='statistiques_on' CHECKED>";
		echo " <B><label for='statistiques_on'>G&eacute;rer les statistiques</label></B> ";
		echo " &nbsp; <INPUT TYPE='radio' NAME='activer_statistiques' VALUE='non' id='statistiques_off'>";
		echo " <label for='statistiques_off'>Ne pas g&eacute;rer les statistiques</label> ";
	}

	echo "</FONT>";
	echo "</TD></TR>\n";




	echo "<TR><TD ALIGN='right'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
	echo "</TD></TR>";
	echo "</TABLE>\n";

fin_cadre_relief();



if (tester_mail()) {
//// Actives/desactiver mails automatiques
	debut_cadre_relief();

	$prevenir_auteurs=lire_meta("prevenir_auteurs");


	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='IMG2/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Envoi automatique de mails</FONT></B> ".aide ("confmails")."</TD></TR>";
	echo "<TR><TD BACKGROUND='IMG2/rien.gif'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='red'>Certains h&eacute;bergeurs d&eacute;sactivent l'envoi automatique de mails depuis leurs serveurs. Dans ce cas, les fonctionnalit&eacute;s suivantes de SPIP ne fonctionneront pas.</FONT>";
	echo "</TD></TR>";


	echo "<TR><TD>&nbsp;</TD></TR>";

	echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='IMG2/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>Envoi des forums aux auteurs des articles</FONT></B></TD></TR>";

	echo "<TR><TD BACKGROUND='IMG2/rien.gif'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>Lorsqu'un visiteur du site poste un message dans les forums associ&eacute;s &agrave; un article, le texte de ce message peut &ecirc;tre envoy&eacute; par mail &agrave; l'auteur de l'article. Souhaitez-vous utiliser cette option&nbsp;?</FONT>";
	echo "</TD></TR>";



	echo "<TR><TD BACKGROUND='IMG2/rien.gif' ALIGN='left'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
	if ($prevenir_auteurs!="oui"){
		echo "<INPUT TYPE='radio' NAME='prevenir_auteurs' VALUE='oui' id='prevenir_auteurs_on'>";
		echo " <label for='prevenir_auteurs_on'>Faire suivre les messages des forums aux auteurs des articles</label> ";
		echo "<BR><INPUT TYPE='radio' NAME='prevenir_auteurs' VALUE='non' CHECKED id='prevenir_auteurs_off'>";
		echo " <B><label for='prevenir_auteurs_off'>Ne pas faire suivre les messages des forums</label></B> ";
	}else{
		echo "<INPUT TYPE='radio' NAME='prevenir_auteurs' VALUE='oui' id='prevenir_auteurs_on' CHECKED>";
		echo " <B><label for='prevenir_auteurs_on'>Faire suivre les messages des forums aux auteurs des articles</label></B> ";
		echo "<BR><INPUT TYPE='radio' NAME='prevenir_auteurs' VALUE='non' id='prevenir_auteurs_off'>";
		echo " <label for='prevenir_auteurs_off'>Ne pas faire suivre les messages des forums</label> ";
	}

	echo "</FONT>";
	echo "</TD></TR>\n";


	///**** Suivi editorial (articles prop/publies)
	
	$suivi_edito=lire_meta("suivi_edito");
	$adresse_suivi=lire_meta("adresse_suivi");
	
	echo "<TR><TD>&nbsp;</TD></TR>";
	echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='IMG2/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>Suivi de l'activit&eacute; &eacute;ditoriale</FONT></B></TD></TR>";

	echo "<TR><TD BACKGROUND='IMG2/rien.gif'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>Afin de faciliter le suivi de l'activit&eacute; &eacute;ditoriale du site, SPIP peut faire parvenir par mail, par exemple &agrave; une mailing-list des r&eacute;dacteurs, l'annonce des demandes de publication et des validations d'articles.</FONT>";
	echo "</TD></TR>";



	echo "<TR><TD BACKGROUND='IMG2/rien.gif' ALIGN='left'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
	if ($suivi_edito!="oui"){
		echo "<INPUT TYPE='radio' NAME='suivi_edito' VALUE='oui' id='suivi_edito_on'>";
		echo " <label for='suivi_edito_on'>Envoyer les annonces &eacute;ditoriales</label> ";
		echo "<BR><INPUT TYPE='radio' NAME='suivi_edito' VALUE='non' CHECKED id='suivi_edito_off'>";
		echo " <B><label for='suivi_edito_off'>Ne pas envoyer d'annonces</label></B> ";
	}else{
		echo "<INPUT TYPE='radio' NAME='suivi_edito' VALUE='oui' id='suivi_edito_on' CHECKED>";
		echo " <B><label for='suivi_edito_on'>Envoyer les annonces &agrave; l'adresse :</label></B> ";

		echo "<input type='text' name='adresse_suivi' value='$adresse_suivi' size='30' CLASS='fondl'>";

		echo "<BR><INPUT TYPE='radio' NAME='suivi_edito' VALUE='non' id='suivi_edito_off'>";
		echo " <label for='suivi_edito_off'>Ne pas envoyer d'annonces &eacute;ditoriales </label> ";
	}

	echo "</FONT>";
	echo "</TD></TR>\n";



	///**** Annonce des nouveautes
	
	$quoi_de_neuf=lire_meta("quoi_de_neuf");
	$adresse_neuf=lire_meta("adresse_neuf");
	$jours_neuf=lire_meta("jours_neuf");
	
	echo "<TR><TD>&nbsp;</TD></TR>";
	echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='IMG2/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>Annonce des nouveaut&eacute;s</FONT></B></TD></TR>";

	echo "<TR><TD BACKGROUND='IMG2/rien.gif'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>SPIP peut envoyer, r&eacute;guli&egrave;rement, l'annonce des derni&egrave;res nouveaut&eacute;s du site.</FONT>";
	echo "</TD></TR>";

	echo "<TR><TD BACKGROUND='IMG2/rien.gif' ALIGN='left'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
	if ($quoi_de_neuf!="oui"){
		echo "<INPUT TYPE='radio' NAME='quoi_de_neuf' VALUE='oui' id='quoi_de_neuf_on'>";
		echo " <label for='quoi_de_neuf_on'>Envoyer la liste des nouveaut&eacute;s</label> ";
		echo "<BR><INPUT TYPE='radio' NAME='quoi_de_neuf' VALUE='non' CHECKED id='quoi_de_neuf_off'>";
		echo " <B><label for='quoi_de_neuf_off'>Ne pas envoyer  la liste des nouveaut&eacute;s</label></B> ";
	}else{
		echo "<INPUT TYPE='radio' NAME='quoi_de_neuf' VALUE='oui' id='quoi_de_neuf_on' CHECKED>";
		echo " <B><label for='quoi_de_neuf_on'>Envoyer la liste des nouveaut&eacute;s</label></B> ";

		echo "<UL>";
		echo "<LI>&agrave; l'adresse : <input type='text' name='adresse_neuf' value='$adresse_neuf' size='30' CLASS='fondl'>";
		echo "<LI>tous les : <input type='text' name='jours_neuf' value='$jours_neuf' size='4' CLASS='fondl'> jours";
		echo " &nbsp;  &nbsp;  &nbsp; <INPUT TYPE='submit' NAME='envoi_now' VALUE='Envoyer maintenant' CLASS='fondl'>";
		echo "</UL>";
		echo "<BR><INPUT TYPE='radio' NAME='quoi_de_neuf' VALUE='non' id='quoi_de_neuf_off'>";
		echo " <label for='quoi_de_neuf_off'>Ne pas envoyer  la liste des nouveaut&eacute;s</label> ";
	}

	echo "</FONT>";
	echo "</TD></TR>\n";


	echo "<TR><TD ALIGN='right'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
	echo "</TD></TR>";
	echo "</TABLE>\n";

	fin_cadre_relief();
}

//// Indexation pour moteur de recherche
debut_cadre_relief();

	$activer_moteur=lire_meta("activer_moteur");


	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='IMG2/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Moteur de recherche int&eacute;gr&eacute;</FONT></B> ".aide ("confmoteur")."</TD></TR>";

	echo "<TR><TD BACKGROUND='IMG2/rien.gif'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>Souhaitez-vous utiliser le moteur de recherche int&eacute;gr&eacute; &agrave; SPIP?
	(Le d&eacute;sactiver acc&eacute;l&egrave;re le fonctionnement du syst&egrave;me.)
</FONT>";
	echo "</TD></TR>";

	echo "<TR><TD BACKGROUND='IMG2/rien.gif' ALIGN='center'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
	if ($activer_moteur=="oui"){
		echo "<INPUT TYPE='radio' NAME='activer_moteur' VALUE='oui' id='moteur_on' CHECKED>";
		echo " <B><label for='moteur_on'>Utiliser le moteur</label></B> ";
		echo " &nbsp; <INPUT TYPE='radio' NAME='activer_moteur' VALUE='non' id='moteur_off'>";
		echo " <label for='moteur_off'>Ne pas utiliser le moteur</label> ";
	}else{
		echo "<INPUT TYPE='radio' NAME='activer_moteur' VALUE='oui' id='moteur_on'>";
		echo " <label for='moteur_on'>Utiliser le moteur</label> ";
		echo " &nbsp; <INPUT TYPE='radio' NAME='activer_moteur' VALUE='non' CHECKED id='moteur_off'>";
		echo " <B><label for='moteur_off'>Ne pas utiliser le moteur</label></B> ";
	}

	echo "</FONT>";
	echo "</TD></TR>";


	echo "<TR><TD ALIGN='right'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
	echo "</TD></TR>";
	echo "</TABLE>";

fin_cadre_relief();


echo "</form>";


fin_page();

?>
