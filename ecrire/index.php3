<?php

include ("inc.php3");

debut_page("Votre espace priv&eacute;", "asuivre", "asuivre");

debut_gauche();



function enfant($collection){
	global $les_enfants, $couleur_foncee;
	$query2 = "SELECT * FROM spip_rubriques WHERE id_parent=\"$collection\" ORDER BY titre";
	$result2 = spip_query($query2);

	while($row=spip_fetch_array($result2)){
		$id_rubrique=$row['id_rubrique'];
		$id_parent=$row['id_parent'];
		$titre=$row['titre'];
		$descriptif=propre($row['descriptif']);
	
		$bouton_layer = bouton_block_invisible("enfants$id_rubrique");
		$les_sous_enfants = sous_enfant($id_rubrique);

		$les_enfants.= "<P>";
		if ($id_parent == "0") $les_enfants .= debut_cadre_relief("secteur-24.gif", true);
		else  $les_enfants .= debut_cadre_relief("rubrique-24.gif", true);
		$les_enfants.= "<FONT FACE=\"Verdana,Arial,Helvetica,sans-serif\">";

		if (strlen($les_sous_enfants) > 0){
			$les_enfants.= $bouton_layer;
		}
		if  (acces_restreint_rubrique($id_rubrique))
			$les_enfants.= "<img src='img_pack/admin-12.gif' alt='' width='12' height='12' title='Vous pouvez administrer cette rubrique et ses sous-rubriques'> ";
		$les_enfants.= "<B><A HREF='naviguer.php3?coll=$id_rubrique'><font color='$couleur_foncee'>".typo($titre)."</font></A></B>";
		if (strlen($descriptif)>1)
			$les_enfants.="<BR><FONT SIZE=1>$descriptif</FONT>";

		$les_enfants.= "</FONT>";

		$les_enfants.="<FONT FACE='arial, helvetica'>";
		$les_enfants .= $les_sous_enfants;
		$les_enfants .="</FONT>&nbsp;";
		$les_enfants .= fin_cadre_relief(true);
	}
}

function sous_enfant($collection2){
	$query3 = "SELECT * FROM spip_rubriques WHERE id_parent=\"$collection2\" ORDER BY titre";
	$result3 = spip_query($query3);

	if (spip_num_rows($result3) > 0){
		$retour = debut_block_invisible("enfants$collection2")."\n\n<FONT SIZE=1><ul style='list-style-image: url(img_pack/rubrique-12.gif)'>";
		while($row=spip_fetch_array($result3)){
			$id_rubrique2=$row['id_rubrique'];
			$id_parent2=$row['id_parent'];
			$titre2=$row['titre'];

			$retour.="<LI><A HREF='naviguer.php3?coll=$id_rubrique2'>$titre2</A>\n";
		}
		$retour .= "</FONT></ul>\n\n".fin_block()."\n\n";
	}

	return $retour;
}


//
// Infos personnelles : nom, utilisation de la messagerie
//

echo "<p align='left'>";
debut_cadre_relief("fiche-perso-24.gif");
echo "<font face='Verdana,Arial,Helvetica,sans-serif' size='2'>";
if ($bonjour == "oui" OR $spip_ecran == "large") echo bouton_block_visible("info_perso");
else echo bouton_block_invisible("info_perso");
echo "<font size='1' color='black'><b>".majuscules($connect_nom)."</b></font>";

if ($bonjour == "oui" OR $spip_ecran == "large") echo debut_block_visible("info_perso");
else echo debut_block_invisible("info_perso");

if (lire_meta('activer_messagerie') != 'non') {
	if ($connect_activer_messagerie != "non") {
		echo "<br>Vous utilisez la messagerie interne de ce site. ";
		if ($connect_activer_imessage != "non")
			echo "Votre nom appara&icirc;t dans la liste des utilisateurs connect&eacute;s.";
		else
			echo "Votre nom n'appara&icirc;t pas dans la liste des utilisateurs connect&eacute;s.";
	} else
		echo "<br>Vous n'utilisez pas la messagerie interne de ce site.";
}

icone_horizontale("Modifier vos informations personnelles", "auteurs_edit.php3?id_auteur=$connect_id_auteur&redirect=index.php3", "fiche-perso-24.gif","rien.gif");

//
// Supprimer le cookie, se deconnecter...
//

if ($connect_statut == "0minirezo" AND $cookie_admin) {
	icone_horizontale("Supprimer le cookie de correspondance" . aide("cookie"), "../spip_cookie.php3?cookie_admin=non&url=".rawurlencode("ecrire/index.php3"), "cookie-24.gif", "");
}

if ($options == "avancees") {
       icone_horizontale("Afficher les r&eacute;glages de s&eacute;curit&eacute;", "index.php3?secu=oui", "base-24.gif", "");
}

echo fin_block();
fin_cadre_relief();


//
// Annonces
//
$query = "SELECT * FROM spip_messages WHERE type = 'affich' AND statut = 'publie' ORDER BY date_heure DESC";
$result = spip_query($query);

if (spip_num_rows($result) > 0){
	debut_cadre_enfonce("messagerie-24.gif");
	echo "<font face='Verdana,Arial,Helvetica,sans-serif' size='1'>";
	echo "<div style='background-color: yellow; padding: 3px;'>";
	echo "<b>Annonces g&eacute;n&eacute;rales :</b>";
	echo "</div>";
	while ($row = spip_fetch_object($result)) {
		if (ereg("^=([^[:space:]]+)$",$row->texte,$match))
			$url = $match[1];
		else
			$url = "message.php3?id_message=".$row->id_message;
		$titre = typo($row->titre);
		echo "<div style='padding-top: 2px;'><img src='img_pack/m_envoi_jaune.gif' border=0> <a href='$url'>$titre</a></div>\n";
	}
	echo "</font>";
	fin_cadre_enfonce();
}



debut_raccourcis();


//
// Afficher les boutons de creation d'article et de breve
//

$query = "SELECT id_rubrique FROM spip_rubriques LIMIT 0,1";
$result = spip_query($query);

if (spip_num_rows($result) > 0) {
	icone_horizontale("&Eacute;crire un nouvel article", "articles_edit.php3?new=oui", "article-24.gif","creer.gif");

	$activer_breves = lire_meta("activer_breves");
	if ($activer_breves != "non") {
		icone_horizontale("&Eacute;crire une nouvelle br&egrave;ve", "breves_edit.php3?new=oui", "breve-24.gif","creer.gif");
	}
}
else {
	if ($connect_statut == '0minirezo') {
		echo "<font size='2'>Avant de pouvoir &eacute;crire des articles, ";
		echo "vous devez cr&eacute;er au moins une rubrique.</font><p>";
	}
}
if ($connect_statut == '0minirezo' and $connect_toutes_rubriques) {
	icone_horizontale("Cr&eacute;er une nouvelle rubrique", "rubriques_edit.php3?new=oui", "rubrique-24.gif","creer.gif");
}



if ($options == "avancees") {
	echo "<p>";
	if (lire_meta("activer_messagerie") == 'oui' AND $connect_activer_messagerie != 'non') {
		icone_horizontale("Messagerie personnelle", "messagerie.php3", "messagerie-24.gif");
	}

	icone_horizontale("Forum interne", "forum.php3", "forum-interne-24.gif","rien.gif");

	if ($connect_statut == "0minirezo") {
		if (lire_meta('forum_prive_admin') == 'oui') {
			icone_horizontale("Forum des administrateurs", "forum_admin.php3", "forum-admin-24.gif");
			echo "<p>";
		}
		if (lire_meta("activer_statistiques") == 'oui')
			icone_horizontale("Statistiques du site", "statistiques_visites.php3", "statistiques-24.gif");
		icone_horizontale("Suivi des forums", "controle_forum.php3", "suivi-forum-24.gif");
		if ($connect_toutes_rubriques)
			icone_horizontale("Vider le cache", "admin_vider.php3", "cache-24.gif");
	}
}
else if ($connect_statut == '0minirezo' and $connect_toutes_rubriques) {
	echo "<p>";
	icone_horizontale("Configurer votre site", "configuration.php3", "administration-24.gif");
}

fin_raccourcis();


debut_droite();

if ($options != 'avancees') {
	debut_boite_info();
	echo "<div class='verdana2'>";
	echo "<p><center><b>&laquo;&nbsp;&Agrave; SUIVRE&nbsp;&raquo;</b></center>";
	echo "<p>".propre("Cette page recense l'actualit&eacute; du site et vous permet de suivre vos contributions. ".
		"Vous y retrouverez vos articles en cours de r&eacute;daction, les articles et les br&egrave;ves ".
		"pour lesquelles vous &ecirc;tes invit&eacute; &agrave; donner votre avis, puis un rappel de vos ".
		"pr&eacute;c&eacute;dentes contributions.<p><hr><p>Quand vous serez familiaris&eacute;(e) avec ".
		"l'interface, vous pourrez cliquer sur &laquo;<a href='index.php3?&set_options=avancees'>interface compl&egrave;te</a>&raquo; ".
		"pour ouvrir plus de possibilit&eacute;s.");
	echo "</div>";
	fin_boite_info();
}


//
// Restauration d'une archive
//

if ($meta["debut_restauration"]) {

	if ($flag_ignore_user_abort) {
		@ignore_user_abort(1);
	}
	include ("inc_import.php3");

	$archive = $meta["fichier_restauration"];
	$my_pos = $meta["status_restauration"];
	$ok = file_exists($archive);

	if ($ok) {
		if (ereg("\.gz$", $archive)) {
			$affiche_progression_pourcent = false;
			$taille = taille_en_octets($my_pos);
		}
		else {
			$affiche_progression_pourcent = filesize($archive);
			$taille = floor(100 * $my_pos / $affiche_progression_pourcent)." %";
		}
		$texte_boite = "La base est en cours de restauration.<p>
		<form name='progression'><center><input type='text' size=10 style='text-align:center;' name='taille' value='$taille'><br>
		<input type='text' class='forml' name='recharge' value='Veuillez recharger cette page dans quelques instants.'></center></form>";
	}
	else {
		$texte_boite = "Erreur de restauration : fichier inexistant.";
	}
	
	debut_boite_alerte();
	echo "<font FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=4 color='black'><B>$texte_boite</B></font>";
	fin_boite_alerte();
	fin_page("jimmac");
	echo "</HTML><font color='white'>\n<!--";
	@flush();
	$gz = $flag_gz;
	$_fopen = ($gz) ? gzopen : fopen;

	if ($ok) {
		$f = $_fopen($archive, "rb");
		$pos = 0;
		$buf = "";
		if (!import_all($f, $gz)) import_abandon();
	}
	else {
		import_fin();
	}
	exit;
}


//
// Modification du cookie
//

if ($connect_statut == "0minirezo") {
	if (!$cookie_admin) {
		echo "<table width=100%><tr width=100%>";
		echo "<td width=100%>";
		echo "Vous pouvez activer un <b>cookie de correspondance</b>, ce qui vous permettra de passer facilement du site public au site priv&eacute;.";
		echo aide ("cookie");
		echo "</td>";
		echo "<td width=10><img src='img_pack/rien.gif' width=10>";
		echo "</td>";
		echo "<td width='250'>";
		icone_horizontale("Activer le cookie de correspondance", "../spip_cookie.php3?cookie_admin=".rawurlencode("@$connect_login")."&url=".rawurlencode("ecrire/index.php3"), "cookie-24.gif", "");
		echo "</td></tr></table>";
		echo "<p><hr><p>";
	}
}


//
// Articles post-dates en attente de publication
//

$post_dates = lire_meta("post_dates");

if ($post_dates == "non" AND $connect_statut == '0minirezo' AND $options == 'avancees') {
	echo "<P align=left>";
	afficher_articles("Les articles post-dat&eacute;s &agrave; para&icirc;tre",
		"SELECT id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, id_rubrique, statut ".
		"FROM spip_articles WHERE statut='publie' AND date>NOW() ORDER BY date");
}


//
// Vos articles en cours de redaction
//

echo "<P align=left>";
$vos_articles = afficher_articles("Vos articles en cours de r&eacute;daction",
	"SELECT articles.id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, id_rubrique, statut ".
	"FROM spip_articles AS articles, spip_auteurs_articles AS lien ".
	"WHERE articles.id_article=lien.id_article AND lien.id_auteur=$connect_id_auteur".
	" AND articles.statut='prepa' ORDER BY articles.date DESC");

if ($vos_articles) $vos_articles = ' AND id_article NOT IN ('.join($vos_articles,',').')';

//
// Verifier les boucles a mettre en relief
//

$relief = false;

if (!$relief) {
	$query = "SELECT id_article FROM spip_articles WHERE statut='prop'$vos_articles LIMIT 0,1";
	$result = spip_query($query);
	$relief = (spip_num_rows($result) > 0);
}

if (!$relief) {
	$query = "SELECT id_breve FROM spip_breves WHERE statut='prop' LIMIT 0,1";
	$result = spip_query($query);
	$relief = (spip_num_rows($result) > 0);
}

if (!$relief AND lire_meta('activer_syndic') != 'non') {
	$query = "SELECT id_syndic FROM spip_syndic WHERE statut='prop' LIMIT 0,1";
	$result = spip_query($query);
	$relief = (spip_num_rows($result) > 0);
}

if (!$relief AND lire_meta('activer_syndic') != 'non' AND $connect_statut == '0minirezo' AND $connect_toutes_rubriques) {
	$query = "SELECT id_syndic FROM spip_syndic WHERE syndication='off' LIMIT 0,1";
	$result = spip_query($query);
	$relief = (spip_num_rows($result) > 0);
}


if ($relief) {
	echo "<p>";
	debut_cadre_enfonce();
	echo "<font color='$couleur_foncee' face='arial,helvetica,sans-serif'><b>Les articles et br&egrave;ves ci-dessous sont propos&eacute;s &agrave; la publication. N'h&eacute;sitez pas &agrave; donner votre avis gr&acirc;ce aux forums qui leur sont attach&eacute;s.</b></font><p>";

	//
	// Les articles a valider
	//
	afficher_articles("Articles propos&eacute;s",
		"SELECT id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, id_rubrique, statut ".
		"FROM spip_articles WHERE statut='prop'$vos_articles ORDER BY date DESC");

	//
	// Les breves a valider
	//
	$query = "SELECT * FROM spip_breves WHERE statut='prepa' OR statut='prop' ORDER BY date_heure DESC";
	afficher_breves("Br&egrave;ves &agrave; valider", $query, true);

	//
	// Les sites references a valider
	//
	if (lire_meta('activer_syndic') != 'non') {
		include_ecrire("inc_sites.php3");
		afficher_sites("Sites &agrave; valider", "SELECT * FROM spip_syndic WHERE statut='prop' ORDER BY nom_site");
	}

	//
	// Les sites a probleme
	//
	if (lire_meta('activer_syndic') != 'non' AND $connect_statut == '0minirezo' AND $connect_toutes_rubriques) {
		include_ecrire("inc_sites.php3");
		afficher_sites("Ces sites syndiqu&eacute;s ont pos&eacute; un probl&egrave;me",
			"SELECT * FROM spip_syndic WHERE syndication='off' AND statut='publie' ORDER BY nom_site");
	}

	// Les articles syndiques en attente de validation
	if ($connect_statut == '0minirezo' AND $connect_toutes_rubriques) {
		$result = spip_query ("SELECT COUNT(*) AS compte FROM spip_syndic_articles WHERE statut='dispo'");
		if (($row = spip_fetch_array($result)) AND $row['compte'])
			echo "<br><small><a href='sites_tous.php3'>".$row['compte']." liens syndiqu&eacute;s</a> sont en attente de validation.</small>";
	}

	// Les forums en attente de moderation
	if ($connect_statut == '0minirezo' AND $connect_toutes_rubriques) {
		$result = spip_query ("SELECT COUNT(*) AS compte FROM spip_forum WHERE statut='prop'");
		if (($row = spip_fetch_array($result)) AND $row['compte']) {
			echo "<br><small><a href='controle_forum.php3'>".$row['compte']." forum";
			if ($row['compte']>1)
				echo "s</a> sont";
			else
				echo " </a> est";
			echo " en attente de validation</small>.";
		}
	}

	fin_cadre_enfonce();
}


if ($options == 'avancees') {
	enfant(0);

	$les_enfants2=substr($les_enfants,round(strlen($les_enfants)/2),strlen($les_enfants));
	if (strpos($les_enfants2,"<P>")){
		$les_enfants2=substr($les_enfants2,strpos($les_enfants2,"<P>"),strlen($les_enfants2));
		$les_enfants1=substr($les_enfants,0,strlen($les_enfants)-strlen($les_enfants2));
	}else{
		$les_enfants1=$les_enfants;
		$les_enfants2="";
	}

	// Afficher les sous-rubriques
	echo "<p><table cellpadding=0 cellspacing=0 border=0 width='100%'>";
	echo "<tr><td valign='top' width=50%>$les_enfants1</td>";
	echo "<td width=20><img src='img_pack/rien.gif' width=20></td>";
	echo "<td valign='top' width=50%>$les_enfants2 &nbsp;";
	if (strlen($les_enfants2) > 0) echo "<p>";
	echo "</td></tr>";
	echo "</table>";

	//
	// Vos articles publies
	//

	echo "<p>";
	afficher_articles("Vos derniers articles publi&eacute;s en ligne",
		"SELECT articles.id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, referers, id_rubrique, statut ".
		"FROM spip_articles AS articles, spip_auteurs_articles AS lien ".
		"WHERE articles.id_article=lien.id_article AND lien.id_auteur=\"$connect_id_auteur\" AND articles.statut=\"publie\" ORDER BY articles.date DESC", true);


}


fin_page("jimmac");


//
// Si necessaire, recalculer les rubriques
//

if (lire_meta('calculer_rubriques') == 'oui') {
	calculer_rubriques();
	effacer_meta('calculer_rubriques');
	ecrire_metas();
}


//
// Renouvellement de l'alea utilise pour valider certaines operations
// (ajouter une image, etc.)
//

$maj_alea = $meta_maj['alea_ephemere'];
$t_jour = substr($maj_alea, 6, 2);
if ($t_jour != date('d')) {
	include_ecrire("inc_session.php3");
	$alea = md5(creer_uniqid());
	ecrire_meta('alea_ephemere_ancien', lire_meta('alea_ephemere'));
	ecrire_meta('alea_ephemere', $alea);
	ecrire_metas();
}

//
// Optimisation periodique de la base de donnees
//

$date_opt = $meta['date_optimisation'];
$date = time();

if (!$bonjour) {
	if ($optimiser == 'oui' || ($date - $date_opt) > 24 * 3600) {
		ecrire_meta("date_optimisation", "$date");
		ecrire_metas();
		include ("optimiser.php3");
	}
}

?>
