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
barre_onglets("configuration", "contenu");


debut_gauche();

debut_droite();

if ($connect_statut != '0minirezo' OR !$connect_toutes_rubriques) {
	echo "Vous n'avez pas acc&egrave;s &agrave; cette page.";
	fin_page();
	exit;
}

//
// Modifications
//

init_config();
if ($changer_config == 'oui') {
	appliquer_modifs_config();
}
else {
	$forums_publics = lire_meta("forums_publics");
	if (!$forums_publics) {
		ecrire_meta("forums_publics", "posteriori");
		ecrire_metas();
	}
}

lire_metas();

avertissement_config();

//
// Afficher les options de config
//

echo "<form action='configuration.php3' method='post'>";
echo "<input type='hidden' name='changer_config' value='oui'>";
debut_cadre_relief("racine-24.gif");

	$nom_site = entites_html(lire_meta("nom_site"));
	$adresse_site = entites_html(lire_meta("adresse_site"));
	$email_webmaster = entites_html(lire_meta("email_webmaster"));

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>";
	echo "Nom de votre site</FONT></B> ".aide ("confnom")."</TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='verdana2'>";
	echo "<input type='text' name='nom_site' value=\"$nom_site\" size='40' CLASS='formo'>";
	echo "</TD></TR>";

	echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#000000'>";
	echo "Adresse (URL) du site public</FONT></B></TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='verdana2'>";
	echo "<input type='text' name='adresse_site' value=\"$adresse_site/\" size='40' CLASS='formo'><p>&nbsp;";
	echo "</TD></TR>";

	if ($options == "avancees") {
		echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#000000'>";
		echo "Adresse e-mail du webmestre (optionnel)</FONT></B></TD></TR>";

		echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='verdana2'>";
		echo "<input type='text' name='email_webmaster' value=\"$email_webmaster\" size='40' CLASS='forml'>";
		echo "</TD></TR>";
	}

	echo "<TR><TD ALIGN='right'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
	echo "</TD></TR>";
	echo "</TABLE>";

fin_cadre_relief();

echo "<p>&nbsp;<p>";


//
// Options des articles
//

if ($options == 'avancees') {
	debut_cadre_enfonce("article-24.gif");

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif' COLSPAN=2><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>LES ARTICLES</FONT></B></TD></TR>";
	echo "</table>";

	//
	// Champs optionnels des articles
	//

	debut_cadre_relief();

	$articles_surtitre = lire_meta("articles_surtitre");
	$articles_soustitre = lire_meta("articles_soustitre");
	$articles_descriptif = lire_meta("articles_descriptif");
	$articles_chapeau = lire_meta("articles_chapeau");
	$articles_ps = lire_meta("articles_ps");
	$articles_redac = lire_meta("articles_redac");

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='img_pack/rien.gif' COLSPAN=2><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='black'>Contenu des articles</FONT></B>".aide ("confart")."</TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' COLSPAN='2' class='verdana2'>";
	echo "Selon la maquette adopt&eacute;e pour votre site, vous pouvez d&eacute;cider
		que certains &eacute;l&eacute;ments des articles ne sont pas utilis&eacute;s.
		Utilisez la liste ci-dessous pour indiquer quels &eacute;l&eacute;ments sont disponibles.";
	echo "</TD></TR>";

	echo "<TR>";
	echo "<TD ALIGN='left' class='verdana2'>";
	echo "Surtitre :";
	echo "</TD>";
	echo "<TD ALIGN='left' class='verdana2'>";
	afficher_choix('articles_surtitre', $articles_surtitre,
		array('oui' => 'Oui', 'non' => 'Non'), " &nbsp; ");
	echo "</TD></TR>\n";

	echo "<TR>";
	echo "<TD ALIGN='left' class='verdana2'>";
	echo "Soustitre :";
	echo "</TD>";
	echo "<TD ALIGN='left' class='verdana2'>";
	afficher_choix('articles_soustitre', $articles_soustitre,
		array('oui' => 'Oui', 'non' => 'Non'), " &nbsp; ");
	echo "</TD></TR>\n";

	echo "<TR>";
	echo "<TD ALIGN='left' class='verdana2'>";
	echo "Descriptif :";
	echo "</TD>";
	echo "<TD ALIGN='left' class='verdana2'>";
	afficher_choix('articles_descriptif', $articles_descriptif,
		array('oui' => 'Oui', 'non' => 'Non'), " &nbsp; ");
	echo "</TD></TR>\n";

	echo "<TR>";
	echo "<TD ALIGN='left' class='verdana2'>";
	echo "Chapeau :";
	echo "</TD>";
	echo "<TD ALIGN='left' class='verdana2'>";
	afficher_choix('articles_chapeau', $articles_chapeau,
		array('oui' => 'Oui', 'non' => 'Non'), " &nbsp; ");
	echo "</TD></TR>\n";

	echo "<TR>";
	echo "<TD ALIGN='left' class='verdana2'>";
	echo "Post-scriptum :";
	echo "</TD>";
	echo "<TD ALIGN='left' class='verdana2'>";
	afficher_choix('articles_ps', $articles_ps,
		array('oui' => 'Oui', 'non' => 'Non'), " &nbsp; ");
	echo "</TD></TR>\n";

	echo "<TR>";
	echo "<TD ALIGN='left' class='verdana2'>";
	echo "Date de publication ant&eacute;rieure :";
	echo "</TD>";
	echo "<TD ALIGN='left' class='verdana2'>";
	afficher_choix('articles_redac', $articles_redac,
		array('oui' => 'Oui', 'non' => 'Non'), " &nbsp; ");
	echo "</TD></TR>\n";

	echo "<TR><TD ALIGN='right' COLSPAN=2>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
	echo "</TD></TR>";
	echo "</TABLE>";

	fin_cadre_relief();

	//
	// Articles post-dates
	//

	debut_cadre_relief();

	$post_dates = lire_meta("post_dates");

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='black'>Publication des articles post-dat&eacute;s</FONT></B> ".aide ("confdates")."</TD></TR>";

	echo "<TR><TD class='verdana2'>";
	echo "Quel comportement SPIP doit-il adopter face aux articles dont la
		date de publication a &eacute;t&eacute; fix&eacute;e &agrave; une
		&eacute;ch&eacute;ance future&nbsp;?";
	echo "</TD></TR>";

	echo "<TR><TD ALIGN='left' class='verdana2'>";
	afficher_choix('post_dates', $post_dates,
		array('oui' => 'Publier les articles, quelle que soit leur date de publication.',
			'non' => 'Ne pas publier les articles avant la date de publication fix&eacute;e.'));
	echo "</TD></TR>\n";

	echo "<TR><TD ALIGN='right'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
	echo "</TD></TR>";
	echo "</TABLE>\n";

	fin_cadre_relief();
}


if ($options == "avancees") fin_cadre_enfonce();

echo "<p>";


//
// Actives/desactiver les breves
//

debut_cadre_relief("breve-24.gif");

$activer_breves = lire_meta("activer_breves");

echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>";
echo "Les br&egrave;ves</FONT></B> ".aide ("confbreves")."</TD></TR>";

echo "<TR><TD class='verdana2'>";
echo "Les br&egrave;ves sont des textes courts et simples permettant de
	mettre en ligne rapidement des informations concises, de g&eacute;rer
	une revue de presse, un calendrier d'&eacute;v&eacute;nements...<p>";
echo "Votre site utilise-t-il le syst&egrave;me de br&egrave;ves&nbsp;?";
echo "</TD></TR>";

echo "<TR><TD align='center' class='verdana2'>";
afficher_choix('activer_breves', $activer_breves,
	array('oui' => 'Utiliser les br&egrave;ves',
		'non' => 'Ne pas utiliser les br&egrave;ves'), " &nbsp; ");
echo "</FONT>";
echo "</TD></TR>\n";

echo "<TR><TD ALIGN='right'>";
echo "<INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
echo "</TD></TR>";
echo "</TABLE>\n";

fin_cadre_relief();

echo "<p>";


//
// Gestion des mots-cles
//

if ($options == "avancees") {

	debut_cadre_relief("mot-cle-24.gif");

	$articles_mots = lire_meta("articles_mots");
	$config_precise_groupes = lire_meta("config_precise_groupes");
	$mots_cles_forums = lire_meta("mots_cles_forums");
	$forums_publics = lire_meta("forums_publics");

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Les mots-cl&eacute;s</FONT></B> </TD></TR>";

	echo "<TR><TD class='verdana2'>";
	echo "Les mots-cl&eacute;s permettent de cr&eacute;er des liens th&eacute;matiques entre vos articles
		ind&eacute;pendamment de leur placement dans des rubriques. Vous pouvez ainsi
		enrichir la navigation de votre site, voire utiliser ces propri&eacute;t&eacute;s
		pour personnaliser la pr&eacute;sentation des articles dans vos squelettes.<p>";
	echo "Souhaitez-vous utiliser les mots-cl&eacute;s sur votre site&nbsp;?";
	echo "</TD></TR>";

	echo "<TR>";
	echo "<TD align='center' class='verdana2'>";
	afficher_choix('articles_mots', $articles_mots,
		array('oui' => 'Utiliser les mots-cl&eacute;s',
			'non' => 'Ne pas utiliser les mots-cl&eacute;s'), " &nbsp; ");
	echo "</FONT>";
	echo "</TD></TR>";

	if ($articles_mots != "non") {

		echo "<TR><TD>&nbsp;</TD></TR>";
		echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#000000'>Configuration des groupes de mots-cl&eacute;s</FONT></B></TD></TR>";

		echo "<TR><TD class='verdana2'>";
		echo "Souhaitez-vous activer la configuration avanc&eacute;e des mots-cl&eacute;s,
			en indiquant par exemple qu'on peut s&eacute;lectionner un mot unique
			par groupe, qu'un groupe est important...&nbsp?";
		echo "</TD></TR>";

		echo "<TR>";
		echo "<TD ALIGN='left' class='verdana2'>";
		afficher_choix('config_precise_groupes', $config_precise_groupes,
			array('oui' => 'Utiliser la configuration avanc&eacute;e des groupes de mots-cl&eacute;s',
				'non' => 'Ne pas utiliser la configuration avanc&eacute;e des groupes de mots-cl&eacute;s'));
		echo "</TD></TR>";

		if ($forums_publics != "non"){
			echo "<TR><TD>&nbsp;</TD></TR>";
			echo "<TR><TD BGCOLOR='#EEEECC' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#000000'>Mots-cl&eacute;s dans les forums du site public</FONT></B></TD></TR>";

			echo "<TR><TD class='verdana2'>";
			echo "Souhaitez-vous permettre d'utilisation des mots-cl&eacute;s, s&eacute;lectionnables par les visiteurs, dans les forums du site public&nbsp;? (Attention&nbsp;: cette option est relativement complexe &agrave; utiliser correctement.)";
			echo "</TD></TR>";

			echo "<TR>";
			echo "<TD BACKGROUND='img_pack/rien.gif' ALIGN='left' class='verdana2'>";
			afficher_choix('mots_cles_forums', $mots_cles_forums,
				array('oui' => "Autoriser l'ajout de mots-cl&eacute;s aux forums",
					'non' => "Interdire l'utilisation des mots-cl&eacute;s dans les forums"));
			echo "</FONT>";
			echo "</TD></TR>";
		}
	}

	echo "<TR><TD ALIGN='right'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
	echo "</TD></TR>";
	echo "</TABLE>\n";

	fin_cadre_relief();

	echo "<p>";
}


//
// Actives/desactiver systeme de syndication
//

debut_cadre_enfonce("site-24.gif");

$activer_sites = lire_meta('activer_sites');
$activer_syndic = lire_meta("activer_syndic");
$proposer_sites = lire_meta("proposer_sites");
$visiter_sites = lire_meta("visiter_sites");
$moderation_sites = lire_meta("moderation_sites");

echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>R&eacute;f&eacute;rencement de sites et syndication</FONT></B>".aide ("reference")."</TD></TR>";


echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='left' class='verdana2'>";
afficher_choix('activer_sites', $activer_sites,
	array('oui' => 'G&eacute;rer un annuaire de sites Web',
	'non' => "D&eacute;sactiver l'annuaire de sites Web"));
echo "</TD></TR>\n";



if ($activer_sites != 'non') {
	//
	// Utilisateurs autorises a proposer des sites references
	//
	if ($options == "avancees") {
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
	}

	echo "</TABLE>\n";

	debut_cadre_relief();

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";

	echo "<TR><TD BGCOLOR='EEEECC' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#000000'>Syndication de sites</FONT></B> ".aide ("rubsyn")."</TD></TR>";

	//
	// Reglage de la syndication
	//
	echo "<TR><TD BACKGROUND='img_pack/rien.gif' class='verdana2'>";
	echo "Il est possible de r&eacute;cup&eacute;rer automatiquement, lorsqu'un site Web le permet, 
		la liste de ses nouveaut&eacute;s. Pour cela, vous devez activer la syndication. 
		<blockquote><i>Certains h&eacute;bergeurs d&eacute;sactivent cette fonctionnalit&eacute;&nbsp;; 
		dans ce cas, vous ne pourrez pas utiliser la syndication de contenu
		depuis votre site.</i></blockquote>";
	echo "</TD></TR>";

	echo "<TR><TD BACKGROUND='img_pack/rien.gif' ALIGN='left' class='verdana2'>";
	afficher_choix('activer_syndic', $activer_syndic,
		array('oui' => 'Utiliser la syndication automatique',
		'non' => "Ne pas utiliser la syndication automatique"));

	if ($activer_syndic != "non" AND $options == "avancees") {
		// Moderation par defaut des sites syndiques
		echo "<p><hr><p align='left'>";
		echo propre("Les liens issus des sites syndiqu&eacute;s peuvent
			&ecirc;tre bloqu&eacute;s a priori ; le r&eacute;glage
			ci-dessous indique le r&eacute;glage par d&eacute;faut des
			sites syndiqu&eacute;s apr&egrave;s leur cr&eacute;ation. Il
			est ensuite possible, de toutes fa&ccedil;ons, de
			d&eacute;bloquer chaque lien individuellement, ou de
			choisir, site par site, de bloquer les liens &agrave; venir
			de tel ou tel site.")."<p>";

		afficher_choix('moderation_sites', $moderation_sites,
			array('oui' => 'Bloquer les liens syndiqu&eacute;s pour validation',
			'non' => "Ne pas bloquer les liens issus de la syndication"));

		// Si indexation, activer/desactiver pages recuperees

		$activer_moteur = lire_meta("activer_moteur");
		if ($activer_moteur == "oui") {
			echo "<p><hr><p align='left'>";
			echo "Lorsque vous utilisez le moteur de recherche int&eacute;gr&eacute; 
				&agrave; SPIP, vous pouvez effectuer les recherches sur les sites et
				les articles syndiqu&eacute;s de deux mani&egrave;res
				diff&eacute;rentes. <br><img src='puce.gif'> La plus
				simple consiste &agrave; rechercher uniquement dans les
				titres et les descriptifs des articles. <br><img src='puce.gif'>
				Une seconde m&eacute;thode, beaucoup plus puissante, permet
				&agrave; SPIP de rechercher &eacute;galement dans le texte des
				sites r&eacute;f&eacute;renc&eacute;s&nbsp;. Si vous
				r&eacute;f&eacute;rencez un site, SPIP va alors effectuer la
				recherche dans le texte du site lui-m&ecirc;me. ";
			echo "<blockquote><i>Cette m&eacute;thode oblige SPIP &agrave; visiter
				r&eacute;guli&egrave;rement les sites r&eacute;f&eacute;renc&eacute;s,
				ce qui peut provoquer un l&eacute;ger ralentissement de votre propre
				site.</i></blockquote><p>";

			afficher_choix('visiter_sites', $visiter_sites,
				array('non' => 'Limiter la recherche aux informations contenues dans votre site',
					'oui' => "Etendre la recherche au contenu des sites r&eacute;f&eacute;renc&eacute;s"));
		}
	}
	echo "</TD></TR>\n";

	echo "</TABLE>\n";

	fin_cadre_relief();

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
}

echo "<TR><TD ALIGN='right'>";
echo "<INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
echo "</TD></TR>";
echo "</TABLE>\n";

fin_cadre_enfonce();

echo "<p>";


//
// Gestion des documents joints
//

debut_cadre_relief("doc-24.gif");

$documents_rubrique = lire_meta("documents_rubrique");
$documents_article = lire_meta("documents_article");

echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
echo "<TR><TD BGCOLOR='$couleur_foncee' BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Documents joints</FONT></B> </TD></TR>";

echo "<TR><TD class='verdana2'>";
echo "Vous pouvez autoriser l'ajout de documents (fichiers bureautiques, images,
	multim&eacute;dia, etc.) aux articles et/ou aux rubriques. Ces fichiers
	peuvent ensuite &ecirc;tre r&eacute;f&eacute;renc&eacute;s dans
	l'article, ou affich&eacute;s s&eacute;par&eacute;ment.<p>";
echo "Ce r&eacute;glage n'emp&ecirc;che pas l'insertion d'images directement dans les articles.";
echo "</TD></TR>";

echo "<TR>";
echo "<TD align='left' class='verdana2'>";
afficher_choix('documents_article', $documents_article,
	array('oui' => 'Autoriser les documents joints aux articles',
		'non' => 'Ne pas autoriser les documents dans les articles'), "<br>");
echo "<br><br>\n";
afficher_choix('documents_rubrique', $documents_rubrique,
	array('oui' => 'Autoriser les documents dans les rubriques',
		'non' => 'Ne pas autoriser les documents dans les rubriques'), "<br>");
echo "</FONT>";
echo "</TD></TR>";

echo "<TR><TD ALIGN='right'>";
echo "<INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
echo "</TD></TR>";
echo "</TABLE>\n";

fin_cadre_relief();

echo "<p>";




//
// Options des liens ouvrants
//

if ($options == "avancees") {
	debut_cadre_relief("doc-24.gif");

	$lien_ouvrant_in = lire_meta("lien_ouvrant_in");
	$lien_ouvrant_out = lire_meta("lien_ouvrant_out");
	$lien_ouvrant_doc = lire_meta("lien_ouvrant_doc");
	$lien_ouvrant_manuel = lire_meta("lien_ouvrant_manuel");

	echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3 WIDTH=\"100%\">";
	echo "<TR><TD BGCOLOR='$couleur_foncee'colspan=2 BACKGROUND='img_pack/rien.gif'><B><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>Liens hypertextes ouvrants</FONT></B> </TD></TR>";

	echo "<TR><TD class='verdana2' colspan=2>";
	echo "Vous pouvez d&eacute;cider de forcer l'ouverture d'une nouvelle fen&ecirc;tre &agrave; l'&eacute;cran lorsque les visiteurs de votre site suivent les liens hypertextes (ce qui correspond &agrave; effectuer syst&eacute;matiquement l'op&eacute;ration &laquo;Ouvrir dans une nouvelle fen&ecirc;tre...&raquo; pour tous les liens).<p>";
	echo "<blockquote><i>Une telle pratique est vivement d&eacute;conseill&eacute;e, car contraire &agrave; la n&eacute;tiquette&nbsp;: c'est aux visiteurs de d&eacute;cider quand et pourquoi ils veulent ouvrir des fen&ecirc;tres dans leur butineur, et non aux webmestres des sites. Nous recommandons donc de laisser les s&eacute;lections suivantes sur &laquo;Non&raquo;.</i></blockquote>";
	echo "Ouvrir une nouvelle fen&ecirc;tre&nbsp;:";
	echo "</TD></TR>";

	echo "<TR>";
	echo "<TD ALIGN='left' class='verdana2'>";
	echo "<li>pour les liens &agrave; l'int&eacute;rieur du site&nbsp;:";
	echo "</TD>";
	echo "<TD ALIGN='left' class='verdana2'>";
	afficher_choix('lien_ouvrant_in', $lien_ouvrant_in,
		array('oui' => 'Oui', 'non' => 'Non'), " &nbsp; ");
	echo "</TD></TR>\n";

	echo "<TR>";
	echo "<TD ALIGN='left' class='verdana2'>";
	echo "<li>pour les liens vers l'ext&eacute;rieur du site&nbsp;:";
	echo "</TD>";
	echo "<TD ALIGN='left' class='verdana2'>";
	afficher_choix('lien_ouvrant_out', $lien_ouvrant_out,
		array('oui' => 'Oui', 'non' => 'Non'), " &nbsp; ");
	echo "</TD></TR>\n";

	echo "<TR>";
	echo "<TD ALIGN='left' class='verdana2'>";
	echo "<li>pour l'ouverture des documents joints&nbsp;:";
	echo "</TD>";
	echo "<TD ALIGN='left' class='verdana2'>";
	afficher_choix('lien_ouvrant_doc', $lien_ouvrant_doc,
		array('oui' => 'Oui', 'non' => 'Non'), " &nbsp; ");
	echo "</TD></TR>\n";


	echo "<TR>";
	echo "<TD align='left' class='verdana2' colspan=2>";
	echo "<p><hr><p>";
	echo "Le raccourci de mise en page <tt>[...->>...]</tt> permet, s'il est activ&eacute;, de cr&eacute;er des liens hypertextes qui provoquent l'ouverture d'une nouvelle fen&ecirc;tre, selon le choix du r&eacute;dacteur. Vous pouvez accepter ou interdire l'utilisation de ce raccourci par les r&eacute;dacteurs du site.<p>";
	echo "<blockquote><i>Une telle pratique est d&eacute;conseill&eacute;e: vous devriez alors v&eacute;rifier le comportement de chaque lien hypertexte dans chaque article, sauf &agrave; perdre la coh&eacute;rence de votre interface de navigation. Si vous conservez l'option &laquo;Interdire la s&eacute;lection manuelle&raquo;, ce raccourci fonctionnera normalement, mais sans provoquer l'ouverture d'une fen&ecirc;tre.</i></blockquote>";

	afficher_choix('lien_ouvrant_manuel', $lien_ouvrant_manuel,
	array('oui' => 'Autoriser les liens ouvrants &laquo;&nbsp;manuels&nbsp;&raquo;',
		'non' => 'Interdire les liens ouvrants &laquo;&nbsp;manuels&nbsp;&raquo;'), "<br>");
	echo "</TD></TR>";

	echo "<TR><TD ALIGN='right'>";
	echo "<INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'>";
	echo "</TD></TR>";
	echo "</TABLE>\n";

	fin_cadre_relief();

	echo "<p>";
}




echo "</form>";


fin_page();

?>
