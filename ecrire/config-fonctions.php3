<?php

include ("inc.php3");

include_ecrire("inc_mail.php3");

function mySel($varaut,$variable){
		$retour= " VALUE=\"$varaut\"";

	if ($variable==$varaut){
		$retour.= " SELECTED";
	}

	return $retour;
}



if ($changer_config == 'oui') {

	// test du proxy : $tester_proxy est le bouton "submit"
	if ($tester_proxy) {
		if (!$test_proxy) {
			echo "Vous n'avez pas indiqu&eacute; d'adresse &agrave; tester !";
			exit;
		} else {
			include_ecrire("inc_sites.php3");
			$page = recuperer_page($test_proxy);
			if ($page)
				echo "<pre>".entites_html($page)."</pre>";
			else
				echo propre("{{Erreur !}} Impossible de lire la page <tt><html>$test_proxy</html></tt> &agrave; travers le proxy <tt><html>$http_proxy</html></tt>.") . aide('confhttpproxy');
			exit;
		}
	}

	// activer le moteur : dresser la liste des choses a indexer
	if ($activer_moteur == 'oui') {
		include_ecrire('inc_index.php3');
		creer_liste_indexation();
	}

	ecrire_meta("http_proxy", $http_proxy);
	ecrire_meta("activer_moteur", $activer_moteur);
	ecrire_meta("prevenir_auteurs", $prevenir_auteurs);
	ecrire_meta("activer_messagerie", $activer_messagerie);
	ecrire_meta("activer_imessage", $activer_imessage);
	ecrire_meta("activer_statistiques", $activer_statistiques);
	ecrire_meta("activer_statistiques_ref", $activer_statistiques_ref);

	ecrire_meta("suivi_edito", $suivi_edito);
	if ($adresse_suivi) ecrire_meta("adresse_suivi", $adresse_suivi);

	ecrire_meta("quoi_de_neuf", $quoi_de_neuf);
	if ($adresse_neuf) ecrire_meta("adresse_neuf", $adresse_neuf);
	if ($jours_neuf) ecrire_meta("jours_neuf", $jours_neuf);

	ecrire_metas();	
}

lire_metas();




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



echo "<form action='config-fonctions.php3' method='post'>";
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


//// Fonctionnement de la messagerie interne
debut_cadre_relief("messagerie-24.gif");

	$activer_messagerie=lire_meta("activer_messagerie");
	$activer_imessage=lire_meta("activer_imessage");


	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Messagerie interne</FONT></B> ".aide ("confmessagerie")." </TD></TR>";
	echo "<TR><TD BACKGROUND='img_pack/rien.gif'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2>SPIP permet l'&eacute;change de messages et la constitution de forums de discussion priv&eacute;s entre les participants du site. Vous pouvez activer ou d&eacute;sactiver cette fonctionnalit&eacute;.</FONT>";
	echo "</TD></TR>";



	// Activer/d&eacute;sactiver l'int&eacute;gralit&eacute; de la messagerie
	echo "<TR><TD>&nbsp;</TD></TR>";

	echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>Messagerie interne</FONT></B></TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>Vous pouvez activer ou d&eacute;sactiver l'int&eacute;gralit&eacute; du syst&egrave;me de messagerie.</FONT>";
	echo "</TD></TR>";



	echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='left'>";
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
		echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>Liste des r&eacute;dacteurs connect&eacute;s</FONT></B></TD></TR>";

		echo "<TR><TD BACKGROUND='img_pack/rien.gif'>";
		echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>Lorsque le syst&egrave;me de messagerie est activ&eacute;, SPIP peut vous indiquer en permanence la liste des r&eacute;dacteurs connect&eacute;s, ce qui vous permet d'&eacute;changer des messages en direct (lorsque la messagerie est d&eacute;sactiv&eacute;e ci-dessus, la liste des r&eacute;dacteurs est elle-m&ecirc;me d&eacute;sactiv&eacute;e). Cette fonctionnalit&eacute;, qui favorise l'apparition de <i>chats</i> entre r&eacute;dacteurs, peut &ecirc;tre lourde &agrave; supporter par certains serveurs. Vous pouvez donc la d&eacute;sactiver. </FONT>";
		echo "</TD></TR>";



		echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='left'>";
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


//// Actives/desactiver les statistiques
debut_cadre_relief("statistiques-24.gif");

	$activer_statistiques=lire_meta("activer_statistiques");
	$activer_statistiques_ref=lire_meta("activer_statistiques_ref");


	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Statistiques des visites</FONT></B> ".aide ("confstat")."</TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>Votre site doit-il g&eacute;rer les statistiques des visites&nbsp;?</FONT>";
	echo "</TD></TR>";



	echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='center'>";
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




	if ($activer_statistiques != "non") {
		echo "<TR><TD BACKGROUND='img_pack/rien.gif'>";
		echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>Votre site doit-il conserver les <i>referers</i>&nbsp;?</FONT>";
		echo "</TD></TR>";

	
		echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='center'>";
		echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>";
		if ($activer_statistiques_ref!="oui"){
			echo "<INPUT TYPE='radio' NAME='activer_statistiques_ref' VALUE='oui' id='statistiques_ref_on'>";
			echo " <label for='statistiques_ref_on'>G&eacute;rer les referers</label> ";
			echo " &nbsp; <INPUT TYPE='radio' NAME='activer_statistiques_ref' VALUE='non' id='statistiques_ref_off' CHECKED>";
			echo " <B><label for='statistiques_ref_off'>Ne pas g&eacute;rer les referers</label></B> ";
		}else{
			echo "<INPUT TYPE='radio' NAME='activer_statistiques_ref' VALUE='oui' id='statistiques_ref_on' CHECKED>";
			echo " <B><label for='statistiques_ref_on'>G&eacute;rer les referers</label></B> ";
			echo " &nbsp; <INPUT TYPE='radio' NAME='activer_statistiques_ref' VALUE='non' id='statistiques_ref_off'>";
			echo " <label for='statistiques_ref_off'>Ne pas g&eacute;rer les referers</label> ";
		}
		echo "</FONT>";
		echo "</TD></TR>\n";
	}


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
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Envoi automatique de mails</FONT></B> ".aide ("confmails")."</TD></TR>";
	echo "<TR><TD BACKGROUND='img_pack/rien.gif'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='red'>Certains h&eacute;bergeurs d&eacute;sactivent l'envoi automatique de mails depuis leurs serveurs. Dans ce cas, les fonctionnalit&eacute;s suivantes de SPIP ne fonctionneront pas.</FONT>";
	echo "</TD></TR>";


	echo "<TR><TD>&nbsp;</TD></TR>";

	echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>Envoi des forums aux auteurs des articles</FONT></B></TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>Lorsqu'un visiteur du site poste un message dans les forums associ&eacute;s &agrave; un article, le texte de ce message peut &ecirc;tre envoy&eacute; par mail &agrave; l'auteur de l'article. Souhaitez-vous utiliser cette option&nbsp;?</FONT>";
	echo "</TD></TR>";



	echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='left'>";
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
	echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>Suivi de l'activit&eacute; &eacute;ditoriale</FONT></B></TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>Afin de faciliter le suivi de l'activit&eacute; &eacute;ditoriale du site, SPIP peut faire parvenir par mail, par exemple &agrave; une mailing-list des r&eacute;dacteurs, l'annonce des demandes de publication et des validations d'articles.</FONT>";
	echo "</TD></TR>";



	echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='left'>";
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

	if ($envoi_now) {
		effacer_meta('majnouv');
		ecrire_metas();
	}
	
	echo "<TR><TD>&nbsp;</TD></TR>";
	echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>Annonce des nouveaut&eacute;s</FONT></B></TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>SPIP peut envoyer, r&eacute;guli&egrave;rement, l'annonce des derni&egrave;res nouveaut&eacute;s du site.</FONT>";
	echo "</TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='left'>";
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
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Moteur de recherche int&eacute;gr&eacute;</FONT></B> ".aide ("confmoteur")."</TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>Souhaitez-vous utiliser le moteur de recherche int&eacute;gr&eacute; &agrave; SPIP?
	(Le d&eacute;sactiver acc&eacute;l&egrave;re le fonctionnement du syst&egrave;me.)
</FONT>";
	echo "</TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='center'>";
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


//// Utilisation d'un proxy pour aller lire les sites syndiques
debut_cadre_relief();

	$http_proxy=entites_html(lire_meta("http_proxy"));

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Utiliser un proxy</FONT></B> ".aide ("confhttpproxy")."</TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>"
		. propre("Dans certains cas (intranet, r&eacute;seaux prot&eacute;g&eacute;s...), il peut &ecirc;tre n&eacute;cessaire
		d'utiliser un {proxy HTTP} pour atteindre les sites syndiqu&eacute;s. Le cas &eacute;ch&eacute;ant,
		indiquez ci-dessous son adresse, sous la forme <tt><html>http://proxy:8080</html></tt>. En g&eacute;n&eacute;ral,
		vous laisserez cette case vide.") . "</FONT>";
	echo "</TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='center'>";
	echo "<INPUT TYPE='text' NAME='http_proxy' VALUE='$http_proxy' size='40' class='forml'>";
	echo "</TD></TR>";

	echo "<TR><TD ALIGN='right'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
	if ($http_proxy) {
		echo "<p align='left'><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#000000'>"
			. propre("Pour faire un essai de ce proxy, indiquez ici l'adresse d'un {backend}
			que vous souhaitez syndiquer -~par exemple celui du site {uZine}~-, et v&eacute;rifiez que vous y avez
			acc&egrave;s.");
		echo "</TD></TR>";

		echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='center'>";
		echo "<INPUT TYPE='text' NAME='test_proxy' VALUE='http://www.uzine.net/backend.php3' size='40' class='forml'>";
		echo "</TD></TR>";

		echo "<TR><TD ALIGN='right'>";

		echo "</font><div align='right'><INPUT TYPE='submit' NAME='tester_proxy'
		VALUE='Essayer le proxy' CLASS='fondo'></div>";
		
	}
	echo "</TD></TR>";


	echo "</TABLE>";

fin_cadre_relief();


echo "</form>";


fin_page();

?>
