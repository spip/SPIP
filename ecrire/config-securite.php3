<?php

include ("inc.php3");

include_ecrire ("inc_config.php3");
if ($connect_statut != '0minirezo' OR !$connect_toutes_rubriques) {
	debut_page("Configuration du site", "administration", "configuration");
	echo "Vous n'avez pas acc&egrave;s &agrave; cette page.";
	fin_page();
	exit;
}

init_config();
if ($changer_config == 'oui') {
	appliquer_modifs_config();
}

lire_metas();

debut_page("Configuration du site", "administration", "configuration");

echo "<br><br><br>";
gros_titre("Configuration du site");
barre_onglets("configuration", "securite");

debut_gauche();

debut_droite();



echo "<form action='config-securite.php3' method='post'>";
echo "<input type='hidden' name='changer_config' value='oui'>";


//
// Connexions paralleles ?
//

debut_cadre_relief("base-24.gif");

$secu_avertissement = lire_meta("secu_avertissement");

echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Avertissement de connexion</FONT></B></TD></TR>";

echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='verdana2'>";
echo propre("{{Voulez-vous afficher les avertissements de s&eacute;curit&eacute; lors de la connexion?}}\n\nLorsqu'un r&eacute;dacteur se connecte &agrave; l'espace priv&eacute;, SPIP v&eacute;rifie qu'il s'est bien d&eacute;connect&eacute; lors de sa pr&eacute;c&eacute;dente session, et affiche au besoin un message attirant son attention sur la fonction de d&eacute;connexion.")."<p>".propre("Cette option peut s'av&eacute;rer utile si les r&eacute;dacteurs du site - notamment les administrateurs - se connectent depuis un cybercaf&eacute; et oublient de se d&eacute;connecter.").aide("deconnect");
echo "</TD></TR>";

echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='center' class='verdana2'>";
afficher_choix('secu_avertissement', $secu_avertissement,
	array('oui' => 'Afficher l\'avertissement',
		'non' => 'Pas d\'avertissement'), ' &nbsp; ');
echo "</TD></TR>";

echo "<TR><TD ALIGN='right'>";
echo "<INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
echo "</TD></TR>";
echo "</TABLE>";

fin_cadre_relief();

echo "<p>";


//
// Creer fichier .htpasswd ?
//

if (! @file_exists('.htaccess') AND ! $REMOTE_USER ) {
	include_ecrire ("inc_acces.php3");
	ecrire_acces();

	debut_cadre_relief("breve-24.gif");

	$creer_htpasswd = lire_meta("creer_htpasswd");

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Fichiers .htpasswd</FONT></B></TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='verdana2'>";
	echo propre("{{SPIP doit-il cr&eacute;er les fichiers <code>.htpasswd</code> et <code>.htpasswd-admin</code> dans le r&eacute;pertoire <code>ecrire/data/</code>?}}\n\nVotre installation de SPIP ne semble pas n&eacute;cessiter ces fichiers pour l'authentification des utilisateurs dans l'espace priv&eacute;. Mais il peuvent servir, en d'autres endroits de votre site (module externe de statistiques ou de connexion &agrave; la base, par exemple), &agrave; restreindre l'acc&egrave;s aux auteurs SPIP ou aux seuls administrateurs.\n\nSi vous n'en avez pas l'usage, ne les cr&eacute;ez pas; cela &eacute;liminera tout risque qu'ils soient &eacute;ventuellement &laquo;craqu&eacute;s&raquo; par un pirate qui aurait r&eacute;ussi &agrave; les r&eacute;cup&eacute;rer sur ce serveur (et pas forc&eacute;ment &agrave; travers le r&eacute;seau!).");
	echo "</TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='center' class='verdana2'>";
	afficher_choix('creer_htpasswd', $creer_htpasswd,
		array('oui' => 'Cr&eacute;er les fichiers .htpasswd',
		'non' => 'Ne pas cr&eacute;er ces fichiers'), ' &nbsp; ');
	echo "</TD></TR>";

	echo "<TR><TD ALIGN='right'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
	echo "</TD></TR>";
	echo "</TABLE>";

	fin_cadre_relief();

	echo "<p>";
}


echo "</form>";


fin_page();

?>
