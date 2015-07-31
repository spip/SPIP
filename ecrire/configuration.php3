<?php

include ("inc.php3");


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
barre_onglets("configuration", "config");


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
	if ($requete_appliquer) spip_query($requete_appliquer);


	$adresse_site = ereg_replace("/$", "", $adresse_site);

	ecrire_meta("nom_site", $nom_site);
	ecrire_meta("adresse_site", $adresse_site);
	ecrire_meta("accepter_inscriptions", $accepter_inscriptions);
	ecrire_meta("forums_publics","$forums_publics");

/*	A REACTIVER QUAND ON SAURA QUOI EN FAIRE (cf infra)
	if ($email_webmaster=='' OR email_valide($email_webmaster))
		ecrire_meta("email_webmaster", $email_webmaster);
*/

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
debut_cadre_relief("racine-24.gif");

	$nom_site = entites_html(lire_meta("nom_site"));
	$adresse_site = entites_html(lire_meta("adresse_site"));
	$email_webmaster = entites_html(lire_meta("email_webmaster"));

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Nom de votre site</FONT></B> ".aide ("confnom")."</TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif'>";
	echo "<input type='text' name='nom_site' value=\"$nom_site\" size='40' CLASS='forml'>";
	echo "</TD></TR>";

	echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#000000'>Adresse (URL) racine de votre site</FONT></B></TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif'>";
	echo "<input type='text' name='adresse_site' value=\"$adresse_site\" size='40' CLASS='forml'>";
	echo "</TD></TR>";

/*	A REACTIVER QUAND ON SAURA QUOI EN FAIRE (cf supra)
	echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#000000'>Email du webmaster du site</FONT></B></TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif'>";
	echo "<input type='text' name='email_webmaster' value=\"$email_webmaster\" size='40' CLASS='forml'>";
	echo "</TD></TR>";
*/

	echo "<TR><TD ALIGN='right'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
	echo "</TD></TR>";
	echo "</TABLE>";

fin_cadre_relief();



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



//// Accepter les inscriptions de redacteurs depuis le site public
debut_cadre_relief("redacteurs-24.gif");

	$accepter_inscriptions=lire_meta("accepter_inscriptions");


	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Inscription automatique de nouveaux r&eacute;dacteurs</FONT></B> </TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>Acceptez-vous les inscriptions de nouveaux r&eacute;dacteurs &agrave; partir du site public&nbsp;? Si vous acceptez, les visiteurs pourront s'inscrire automatiquement, et acc&eacute;deront alors &agrave; l'espace priv&eacute; pour proposer leurs propres articles. <font color='red'>Lors de la phase d'inscription, les utilisateurs re&ccedil;oivent un courrier &eacute;lectronique automatique leur fournissant leurs codes d'acc&egrave;s au site priv&eacute;. Certains h&eacute;bergeurs d&eacute;sactivent l'envoi de mails depuis leurs serveurs&nbsp;: dans ce cas, l'inscription automatique est impossible.</font></FONT>";
	echo "</TD></TR>";



	echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='center'>";
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



//// Mode de fonctionnement des forums publics
debut_cadre_relief("forum-interne-24.gif");

	$forums_publics=lire_meta("forums_publics");


	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Mode de fonctionnement par d&eacute;faut des forums publics</FONT></B> ".aide ("confforums")."</TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='left'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
	if ($forums_publics=="non") {
		$checked = ' CHECKED';
		$gras = '<b>'; $fingras = '</b>';
	} else {
		$checked = '';
		$gras = ''; $fingras = '';
	}
	echo "<INPUT$checked TYPE='radio' NAME='forums_publics' VALUE='non' id='forums_non'>";
	echo " $gras<label for='forums_non'>D&eacute;sactiver l'utilisation des forums publics. Les forums publics pourront &ecirc;tre autoris&eacute;s au cas par cas sur les articles ; ils seront interdits sur les rubriques, br&egrave;ves, etc. </label>$fingras ";
	echo "</TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'><i>Pour activer les forums publics, veuillez choisir leur mode de mod&eacute;ration par d&eacute;faut :</i></FONT>";
	echo "</TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='left'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";


	if ($forums_publics=="posteriori") {
		$checked = ' CHECKED';
		$gras = '<b>'; $fingras = '</b>';
	} else {
		$checked = '';
		$gras = ''; $fingras = '';
	}
	echo "<INPUT TYPE='radio'$checked NAME='forums_publics' VALUE='posteriori' id='forums_posteriori'>";
	echo " $gras<label for='forums_posteriori'>Mod&eacute;ration &agrave; post&eacute;riori (les contributions s'affichent imm&eacute;diatement en ligne, les administrateurs peuvent les supprimer ensuite).</label>$fingras\n<br>";

	if ($forums_publics=="priori") {
		$checked = ' CHECKED';
		$gras = '<b>'; $fingras = '</b>';
	} else {
		$checked = '';
		$gras = ''; $fingras = '';
	}
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
		}
		echo "<INPUT TYPE='radio'$checked NAME='forums_publics' VALUE='abonnement' id='forums_abonnement'>";
		echo " $gras<label for='forums_abonnement'>Sur abonnement (les utilisateurs doivent fournir leur adresse email avant de pouvoir poster des contributions).</label>$fingras ";
	}

	echo "</FONT>";
	echo "</TD></TR>\n";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='left'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";

	echo "<ul><table width='100%' cellpadding='2' border='0' class='hauteur'>\n";
	echo "<tr><td width='100%' bgcolor='#FFCC66'>\n";
	echo "<font face='Verdana,Arial,Helvetica,sans-serif' size='2' color='#333333'><b>\n";
	echo bouton_block_invisible('forum');
	echo "OPTIONS AVANC&Eacute;ES";
	echo "</b></font></td></tr></table>";
	echo debut_block_invisible('forum');
	echo "<table width='100%' cellpadding='2' border='0' class='hauteur'>\n";
	echo "<tr><td><font face='Verdana,Arial,Helvetica,sans-serif' size='2'>";
	echo "Appliquer ce choix de mod&eacute;ration :<br>";

	echo "<INPUT TYPE='radio' CHECKED NAME='forums_publics_appliquer' VALUE='futur' id='forums_appliquer_futur'>";
	echo " <b><label for='forums_appliquer_futur'>aux articles futurs uniquement (pas d'action sur la base de donn&eacute;es).</label></b><br>";
	echo "<INPUT TYPE='radio' NAME='forums_publics_appliquer' VALUE='saufnon' id='forums_appliquer_saufnon'>";
	echo " <label for='forums_appliquer_saufnon'>&agrave; tous les articles, sauf ceux dont le forum est d&eacute;sactiv&eacute;.</label><br>";
	echo "<INPUT TYPE='radio' NAME='forums_publics_appliquer' VALUE='tous' id='forums_appliquer_tous'>";
	echo " <label for='forums_appliquer_tous'>&agrave; tous les articles sans exception.</label><br>";
	echo "</FONT>";
	echo "</TD></TR></table>\n";
	echo fin_block();
	echo "</ul>";

	echo "<TR><TD ALIGN='right'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
	echo "</TD></TR>";
	echo "</TABLE>\n";

fin_cadre_relief();


echo "</form>";


fin_page();

?>
