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


debut_page(_T('titre_page_config_contenu'), "administration", "configuration");

echo "<br><br><br>";
gros_titre(_T('titre_page_config_contenu'));
barre_onglets("configuration", "interactivite");


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
echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>"._T('info_mode_fonctionnement_defaut_forum_public')."</FONT></B> ".aide ("confforums")."</TD></TR>";

echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='left' class='verdana2'>";
if ($forums_publics=="non") {
	$checked = ' CHECKED';
	$gras = '<b>'; $fingras = '</b>';
} else {
	$checked = '';
	$gras = ''; $fingras = '';
}
echo "<INPUT$checked TYPE='radio' NAME='forums_publics' VALUE='non' id='forums_non'>";
echo $gras."<label for='forums_non'>"._T('info_desactiver_forum_public')."
	</label>.".$fingras;
echo "</TD></TR>";

echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='verdana2'>";
echo _T('info_activer_forum_public');
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
echo " $gras<label for='forums_posteriori'>"._T('bouton_radio_publication_immediate')."</label>$fingras\n<br>";

if ($forums_publics=="priori") {
	$checked = ' CHECKED';
	$gras = '<b>'; $fingras = '</b>';
} else {
	$checked = '';
	$gras = ''; $fingras = '';
}
echo "<INPUT TYPE='radio'$checked NAME='forums_publics' VALUE='priori'
id='forums_priori'>";
echo " $gras<label for='forums_priori'>"._T('bouton_radio_moderation_priori')."</label>$fingras ";

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
	echo " $gras<label for='forums_abonnement'>"._T('bouton_radio_enregistrement_obligatoire')."</label>$fingras ";
}

echo "</TD></TR>\n";

echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='left' class='verdana2'>";

if ($options == 'avancees') {
	echo "<ul><table width='100%' cellpadding='2' border='0' class='hauteur'>\n";
	echo "<tr><td width='100%' bgcolor='#FFCC66'>\n";
	echo "<font face='Verdana,Arial,Helvetica,sans-serif' size='2' color='#333333'><b>\n";
	echo bouton_block_invisible('forum');
	echo _T('info_options_avancees');
	echo "</b></font></td></tr></table>";
	echo debut_block_invisible('forum');
	echo "<table width='100%' cellpadding='2' border='0' class='hauteur'>\n";
	echo "<tr><td class='verdana2'>";
	echo _T('info_appliquer_choix_moderation')."<br>";

	echo "<INPUT TYPE='radio' CHECKED NAME='forums_publics_appliquer' VALUE='futur' id='forums_appliquer_futur'>";
	echo " <b><label for='forums_appliquer_futur'>"._T('bouton_radio_articles_futurs')."</label></b><br>";
	echo "<INPUT TYPE='radio' NAME='forums_publics_appliquer' VALUE='saufnon' id='forums_appliquer_saufnon'>";
	echo " <label for='forums_appliquer_saufnon'>"._T('bouton_radio_articles_tous_sauf_forum_desactive')."</label><br>";
	echo "<INPUT TYPE='radio' NAME='forums_publics_appliquer' VALUE='tous' id='forums_appliquer_tous'>";
	echo " <label for='forums_appliquer_tous'>"._T('bouton_radio_articles_tous')."</label><br>";
	echo "</TD></TR></table>\n";
	echo fin_block();
	echo "</ul>";
}
else {
	echo "<input type='hidden' name='forums_publics_appliquer' value='tous'>";
}


echo "<TR><TD ALIGN='right'>";
echo "<INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
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
	echo _T('info_messagerie_interne')."</FONT></B> ".aide ("confmessagerie")." </TD></TR>";
	echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='verdana2'>";
	echo _T('info_echange_message');
	echo "</TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='verdana2'>";
	afficher_choix('activer_messagerie', $activer_messagerie,
		array('oui' => _T('bouton_radio_activer_messagerie_interne'),
			'non' => _T('bouton_radio_desactiver_messagerie_interne')));
	echo "</TD></TR>\n";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='verdana2'>";
	echo "<hr>\n";
	echo _T('info_forum_ouvert');
	echo "</TD></TR>";
	echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='verdana2'>";
	afficher_choix('forum_prive_admin', lire_meta('forum_prive_admin'),
		array('oui' => _T('item_activer_forum_administrateur'),
			'non' => _T('item_desactiver_forum_administrateur')));
	echo "</TD></TR>\n";


	echo "<TR><TD ALIGN='right'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
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
	echo _T('info_inscription_automatique')."</FONT></B> </TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='verdana2'>";
	echo _T('info_question_inscription_nouveaux_redacteurs')."</i></blockquote>";
	echo "</TD></TR>";


	echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='center' class='verdana2'>";
	afficher_choix('accepter_inscriptions', $accepter_inscriptions,
		array('oui' => _T('item_accepter_inscriptions'),
			'non' => _T('item_non_accepter_inscriptions')), " &nbsp; ");
	echo "</TD></TR>\n";

	echo "<TR><TD ALIGN='right'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
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
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>"._T('info_envoi_email_automatique')."</FONT></B> ".aide ("confmails")."</TD></TR>";
	echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='verdana2'>";
	echo "<blockquote><i>"._T('info_hebergeur_desactiver_envoi_email')."</i></blockquote>";
	echo "</TD></TR>";

	echo "<TR><TD>&nbsp;</TD></TR>";

	echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>";
	echo _T('info_envoi_forum')."</FONT></B></TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='verdana2'>";
	echo _T('info_option_email');
	echo "</TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='left' class='verdana2'>";
	afficher_choix('prevenir_auteurs', $prevenir_auteurs,
		array('oui' => _T('info_option_faire_suivre'),
			'non' => _T('info_option_ne_pas_faire_suivre')));
	echo "</TD></TR>\n";

	//
	// Suivi editorial (articles proposes & publies)
	//

	$suivi_edito=lire_meta("suivi_edito");
	$adresse_suivi=lire_meta("adresse_suivi");

	echo "<TR><TD>&nbsp;</TD></TR>";
	echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3>";
	echo _T('info_suivi_activite')."</FONT></B></TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='verdana2'>";
	echo _T('info_facilite_suivi_activite')."</FONT>";
	echo "</TD></TR>";


	echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='left' class='verdana2'>";
	if ($suivi_edito!="oui"){
		echo "<INPUT TYPE='radio' NAME='suivi_edito' VALUE='oui' id='suivi_edito_on'>";
		echo " <label for='suivi_edito_on'>"._T('bouton_radio_envoi_annonces')."</label> ";
		echo "<BR><INPUT TYPE='radio' NAME='suivi_edito' VALUE='non' CHECKED id='suivi_edito_off'>";
		echo " <B><label for='suivi_edito_off'>"._T('bouton_radio_non_envoi_annonces')."</label></B>";
	}else{
		echo "<INPUT TYPE='radio' NAME='suivi_edito' VALUE='oui' id='suivi_edito_on' CHECKED>";
		echo " <B><label for='suivi_edito_on'>"._T('bouton_radio_envoi_annonces_adresse')."</label></B> ";
		echo "<input type='text' name='adresse_suivi' value='$adresse_suivi' size='30' CLASS='fondl'>";
		echo "<BR><INPUT TYPE='radio' NAME='suivi_edito' VALUE='non' id='suivi_edito_off'>";
		echo " <label for='suivi_edito_off'>"._T('bouton_radio_non_envoi_annonces_editoriales')."</label> ";
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
	echo _T('info_annonce_nouveautes')."</FONT></B></TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='verdana2'>";
	echo _T('info_non_envoi_annonce_dernieres_nouveautes');
	echo "</TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='left' class='verdana2'>";
	if ($quoi_de_neuf != "oui") {
		echo "<INPUT TYPE='radio' NAME='quoi_de_neuf' VALUE='oui' id='quoi_de_neuf_on'>";
		echo " <label for='quoi_de_neuf_on'>"._T('bouton_radio_envoi_liste_nouveautes')."</label> ";
		echo "<BR><INPUT TYPE='radio' NAME='quoi_de_neuf' VALUE='non' CHECKED id='quoi_de_neuf_off'>";
		echo " <B><label for='quoi_de_neuf_off'>"._T('bouton_radio_non_envoi_liste_nouveautes')."</label></B> ";
	}
	else {
		echo "<INPUT TYPE='radio' NAME='quoi_de_neuf' VALUE='oui' id='quoi_de_neuf_on' CHECKED>";
		echo " <B><label for='quoi_de_neuf_on'>"._T('bouton_radio_envoi_liste_nouveautes')."</label></B> ";

		echo "<UL>";
		echo "<LI>"._T('info_adresse')." <input type='text' name='adresse_neuf' value='$adresse_neuf' size='30' CLASS='fondl'>";
		echo "<LI>"._T('info_tous_les')." <input type='text' name='jours_neuf' value='$jours_neuf' size='4' CLASS='fondl'> "._T('info_jours');
		echo " &nbsp;  &nbsp;  &nbsp; <INPUT TYPE='submit' NAME='envoi_now' VALUE='"._T('info_envoyer_maintenant')."' CLASS='fondl'>";
		echo "</UL>";
		echo "<BR><INPUT TYPE='radio' NAME='quoi_de_neuf' VALUE='non' id='quoi_de_neuf_off'>";
		echo " <label for='quoi_de_neuf_off'>"._T('info_non_envoi_liste_nouveautes')."</label> ";
	}
	echo "</TD></TR>\n";

	echo "<TR><TD ALIGN='right'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
	echo "</TD></TR>";
	echo "</TABLE>\n";

	fin_cadre_relief();
}


echo "</form>";

fin_page();

?>
