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
barre_onglets("configuration", "interactivite");


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


echo "<form action='config-contenu.php3' method='post'>";
echo "<input type='hidden' name='changer_config' value='oui'>";



//
// Mode de fonctionnement des forums publics
//
debut_cadre_relief("forum-interne-24.gif");

$forums_publics=lire_meta("forums_publics");

echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Mode de fonctionnement par d&eacute;faut des forums publics</FONT></B> ".aide ("confforums")."</TD></TR>";

echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='left' class='verdana2'>";
if ($forums_publics=="non") {
	$checked = ' CHECKED';
	$gras = '<b>'; $fingras = '</b>';
} else {
	$checked = '';
	$gras = ''; $fingras = '';
}
echo "<INPUT$checked TYPE='radio' NAME='forums_publics' VALUE='non' id='forums_non'>";
echo $gras."<label for='forums_non'>D&eacute;sactiver l'utilisation des forums
	publics. Les forums publics pourront &ecirc;tre autoris&eacute;s au cas par cas
	sur les articles ; ils seront interdits sur les rubriques, br&egrave;ves, etc.
	</label>.".$fingras;
echo "</TD></TR>";

echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='verdana2'>";
echo propre("{Pour activer les forums publics, veuillez choisir leur mode
	de mod&eacute;ration par d&eacute;faut:}");
echo "</TD></TR>";

echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='left' class='verdana2'>";

if ($forums_publics=="posteriori") {
	$checked = ' CHECKED';
	$gras = '<b>'; $fingras = '</b>';
} else {
	$checked = '';
	$gras = ''; $fingras = '';
}
echo "<INPUT TYPE='radio'$checked NAME='forums_publics' VALUE='posteriori' id='forums_posteriori'>";
echo " $gras<label for='forums_posteriori'>Publication imm&eacute;diate des messages
	(les contributions s'affichent d&egrave;s leur envoi, les administrateurs peuvent
	les supprimer ensuite).</label>$fingras\n<br>";

if ($forums_publics=="priori") {
	$checked = ' CHECKED';
	$gras = '<b>'; $fingras = '</b>';
} else {
	$checked = '';
	$gras = ''; $fingras = '';
}
echo "<INPUT TYPE='radio'$checked NAME='forums_publics' VALUE='priori'
id='forums_priori'>";
echo " $gras<label for='forums_priori'>Mod&eacute;ration &agrave; priori (les
	contributions ne s'affichent publiquement qu'apr&egrave;s validation par les
	administrateurs).</label>$fingras ";

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
	echo " $gras<label for='forums_abonnement'>Enregistrement obligatoire (les
		utilisateurs doivent s'abonner en fournissant leur adresse e-mail avant de
		pouvoir poster des contributions).</label>$fingras ";
}

echo "</TD></TR>\n";

echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='left' class='verdana2'>";

if ($options == 'avancees') {
	echo "<ul><table width='100%' cellpadding='2' border='0' class='hauteur'>\n";
	echo "<tr><td width='100%' bgcolor='#FFCC66'>\n";
	echo "<font face='Verdana,Arial,Helvetica,sans-serif' size='2' color='#333333'><b>\n";
	echo bouton_block_invisible('forum');
	echo "OPTIONS AVANC&Eacute;ES";
	echo "</b></font></td></tr></table>";
	echo debut_block_invisible('forum');
	echo "<table width='100%' cellpadding='2' border='0' class='hauteur'>\n";
	echo "<tr><td class='verdana2'>";
	echo "Appliquer ce choix de mod&eacute;ration :<br>";

	echo "<INPUT TYPE='radio' CHECKED NAME='forums_publics_appliquer' VALUE='futur' id='forums_appliquer_futur'>";
	echo " <b><label for='forums_appliquer_futur'>aux articles futurs uniquement (pas d'action sur la base de donn&eacute;es).</label></b><br>";
	echo "<INPUT TYPE='radio' NAME='forums_publics_appliquer' VALUE='saufnon' id='forums_appliquer_saufnon'>";
	echo " <label for='forums_appliquer_saufnon'>&agrave; tous les articles, sauf ceux dont le forum est d&eacute;sactiv&eacute;.</label><br>";
	echo "<INPUT TYPE='radio' NAME='forums_publics_appliquer' VALUE='tous' id='forums_appliquer_tous'>";
	echo " <label for='forums_appliquer_tous'>&agrave; tous les articles sans exception.</label><br>";
	echo "</TD></TR></table>\n";
	echo fin_block();
	echo "</ul>";
}
else {
	echo "<input type='hidden' name='forums_publics_appliquer' value='tous'>";
}


echo "<TR><TD ALIGN='right'>";
echo "<INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
echo "</TD></TR>";
echo "</TABLE>\n";

fin_cadre_relief();

echo "<p>";


//
// Fonctionnement de la messagerie interne
//

if ($options == "avancees") {
	debut_cadre_relief("messagerie-24.gif");

	$activer_messagerie = lire_meta("activer_messagerie");
	$activer_imessage = lire_meta("activer_imessage");

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>";
	echo "Messagerie interne</FONT></B> ".aide ("confmessagerie")." </TD></TR>";
	echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='verdana2'>";
	echo "SPIP permet l'&eacute;change de messages et la constitution de forums de discussion
		priv&eacute;s entre les participants du site. Vous pouvez activer ou
		d&eacute;sactiver cette fonctionnalit&eacute;.";
	echo "</TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='verdana2'>";
	afficher_choix('activer_messagerie', $activer_messagerie,
		array('oui' => 'Activer la messagerie interne',
			'non' => 'D&eacute;sactiver la messagerie interne'));
	echo "</TD></TR>\n";

	echo "<TR><TD ALIGN='right'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
	echo "</TD></TR>";
	echo "</TABLE>\n";

	fin_cadre_relief();
	echo "<p>";
}


//
// Accepter les inscriptions de redacteurs depuis le site public
//

if ($options == "avancees") {
	debut_cadre_relief("redacteurs-24.gif");

	$accepter_inscriptions=lire_meta("accepter_inscriptions");
	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";

	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>";
	echo "Inscription automatique de nouveaux r&eacute;dacteurs</FONT></B> </TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='verdana2'>";
	echo "Acceptez-vous les inscriptions de nouveaux r&eacute;dacteurs &agrave;
		partir du site public&nbsp;? Si vous acceptez, les visiteurs pourront s'inscrire
		depuis un formulaire automatis&eacute et acc&eacute;deront alors &agrave; l'espace priv&eacute; pour
		proposer leurs propres articles. <blockquote><i>Lors de la phase d'inscription,
		les utilisateurs re&ccedil;oivent un courrier &eacute;lectronique automatique
		leur fournissant leurs codes d'acc&egrave;s au site priv&eacute;. Certains
		h&eacute;bergeurs d&eacute;sactivent l'envoi de mails depuis leurs
		serveurs&nbsp;: dans ce cas, l'inscription automatique est
		impossible.</i></blockquote>";
	echo "</TD></TR>";


	echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='center' class='verdana2'>";
	afficher_choix('accepter_inscriptions', $accepter_inscriptions,
		array('oui' => 'Accepter les inscriptions',
			'non' => 'Ne pas accepter les inscriptions'), " &nbsp; ");
	echo "</TD></TR>\n";

	echo "<TR><TD ALIGN='right'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
	echo "</TD></TR>";
	echo "</TABLE>\n";

	fin_cadre_relief();
	echo "<p>";
}


//
// Activer/desactiver mails automatiques
//
if (tester_mail()) {
	debut_cadre_relief();

	$prevenir_auteurs=lire_meta("prevenir_auteurs");


	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Envoi de mails automatique</FONT></B> ".aide ("confmails")."</TD></TR>";
	echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='verdana2'>";
	echo "<blockquote><i>Certains h&eacute;bergeurs d&eacute;sactivent l'envoi automatique de
		mails depuis leurs serveurs. Dans ce cas, les fonctionnalit&eacute;s suivantes
		de SPIP ne fonctionneront pas.</i></blockquote>";
	echo "</TD></TR>";

	echo "<TR><TD>&nbsp;</TD></TR>";

	echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>";
	echo "Envoi des forums aux auteurs des articles</FONT></B></TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='verdana2'>";
	echo "Lorsqu'un visiteur du site poste un nouveau message dans le forum
		associ&eacute; &agrave; un article, les auteurs de l'article peuvent &ecirc;tre
		pr&eacute;venus de ce message par e-mail. Souhaitez-vous utiliser cette option&nbsp;?";
	echo "</TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='left' class='verdana2'>";
	afficher_choix('prevenir_auteurs', $prevenir_auteurs,
		array('oui' => 'Faire suivre les messages des forums aux auteurs des articles',
			'non' => 'Ne pas faire suivre les messages des forums'));
	echo "</TD></TR>\n";

	//
	// Suivi editorial (articles proposes & publies)
	//

	$suivi_edito=lire_meta("suivi_edito");
	$adresse_suivi=lire_meta("adresse_suivi");

	echo "<TR><TD>&nbsp;</TD></TR>";
	echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>";
	echo "Suivi de l'activit&eacute; &eacute;ditoriale</FONT></B></TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='verdana2'>";
	echo "Afin de faciliter le suivi de l'activit&eacute;
		&eacute;ditoriale du site, SPIP peut faire parvenir par mail, par exemple
		&agrave; une mailing-list des r&eacute;dacteurs, l'annonce des demandes de
		publication et des validations d'articles.</FONT>";
	echo "</TD></TR>";


	echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='left' class='verdana2'>";
	if ($suivi_edito!="oui"){
		echo "<INPUT TYPE='radio' NAME='suivi_edito' VALUE='oui' id='suivi_edito_on'>";
		echo " <label for='suivi_edito_on'>Envoyer les annonces &eacute;ditoriales</label> ";
		echo "<BR><INPUT TYPE='radio' NAME='suivi_edito' VALUE='non' CHECKED id='suivi_edito_off'>";
		echo " <B><label for='suivi_edito_off'>Ne pas envoyer d'annonces</label></B>";
	}else{
		echo "<INPUT TYPE='radio' NAME='suivi_edito' VALUE='oui' id='suivi_edito_on' CHECKED>";
		echo " <B><label for='suivi_edito_on'>Envoyer les annonces &agrave; l'adresse :</label></B> ";
		echo "<input type='text' name='adresse_suivi' value='$adresse_suivi' size='30' CLASS='fondl'>";
		echo "<BR><INPUT TYPE='radio' NAME='suivi_edito' VALUE='non' id='suivi_edito_off'>";
		echo " <label for='suivi_edito_off'>Ne pas envoyer d'annonces &eacute;ditoriales </label> ";
	}
	echo "</TD></TR>\n";

	//
	// Annonce des nouveautes
	//
	$quoi_de_neuf=lire_meta("quoi_de_neuf");
	$adresse_neuf=lire_meta("adresse_neuf");
	$jours_neuf=lire_meta("jours_neuf");

	if ($envoi_now) {
		effacer_meta('majnouv');
		ecrire_metas();
	}

	echo "<TR><TD>&nbsp;</TD></TR>";
	echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>";
	echo "Annonce des nouveaut&eacute;s</FONT></B></TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='verdana2'>";
	echo "SPIP peut envoyer, r&eacute;guli&egrave;rement, l'annonce des derni&egrave;res nouveaut&eacute;s du site
		(articles et br&egrave;ves r&eacute;cemment publi&eacute;s).";
	echo "</TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='left' class='verdana2'>";
	if ($quoi_de_neuf != "oui") {
		echo "<INPUT TYPE='radio' NAME='quoi_de_neuf' VALUE='oui' id='quoi_de_neuf_on'>";
		echo " <label for='quoi_de_neuf_on'>Envoyer la liste des nouveaut&eacute;s</label> ";
		echo "<BR><INPUT TYPE='radio' NAME='quoi_de_neuf' VALUE='non' CHECKED id='quoi_de_neuf_off'>";
		echo " <B><label for='quoi_de_neuf_off'>Ne pas envoyer  la liste des nouveaut&eacute;s</label></B> ";
	}
	else {
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
	echo "</TD></TR>\n";

	echo "<TR><TD ALIGN='right'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
	echo "</TD></TR>";
	echo "</TABLE>\n";

	fin_cadre_relief();
}


echo "</form>";

fin_page();

?>
