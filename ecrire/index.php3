<?php

include ("inc.php3");

debut_page(_T('titre_page_index'), "asuivre", "asuivre");

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
			$les_enfants.= "<img src='img_pack/admin-12.gif' alt='' width='12' height='12' title='"._T('info_administrer_rubriques')."'> ";
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

if ($HTTP_REFERER && !strpos($HTTP_REFERER, '/ecrire/')) $bonjour = 'oui';

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
		echo "<br>"._T('info_utilisation_messagerie_interne')." ";
		if ($connect_activer_imessage != "non")
			echo _T('info_nom_utilisateurs_connectes');
		else
			echo _T('info_nom_non_utilisateurs_connectes');
	} else
		echo "<br>"._T('info_non_utilisation_messagerie');
}

icone_horizontale(_T('icone_modifier_informations_personnelles'), "auteurs_edit.php3?id_auteur=$connect_id_auteur&redirect=index.php3", "fiche-perso-24.gif","rien.gif");

//
// Supprimer le cookie, se deconnecter...
//

if ($connect_statut == "0minirezo" AND $cookie_admin) {
	icone_horizontale(_T('icone_supprimer_cookie') . aide("cookie"), "../spip_cookie.php3?cookie_admin=non&url=".rawurlencode("ecrire/index.php3"), "cookie-24.gif", "");
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
	echo "<b>"._T('info_annonces_generales')."</b>";
	echo "</div>";
	while ($row = spip_fetch_object($result)) {
		if (ereg("^=([^[:space:]]+)$",$row->texte,$match))
			$url = $match[1];
		else
			$url = "message.php3?id_message=".$row->id_message;
		$titre = typo($row->titre);
		echo "<div style='padding-top: 2px;'><img src='img_pack/m_envoi_jaune$spip_lang_rtl.gif' border=0> <a href='$url'>$titre</a></div>\n";
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
	icone_horizontale(_T('icone_ecrire_article'), "articles_edit.php3?new=oui", "article-24.gif","creer.gif");

	$activer_breves = lire_meta("activer_breves");
	if ($activer_breves != "non") {
		icone_horizontale(_T('icone_nouvelle_breve'), "breves_edit.php3?new=oui", "breve-24.gif","creer.gif");
	}
}
else {
	if ($connect_statut == '0minirezo') {
		echo "<font size='2'>"._T('info_ecrire_article')."</font><p>";
	}
}
if ($connect_statut == '0minirezo' and $connect_toutes_rubriques) {
	icone_horizontale(_T('icone_creer_rubrique_2'), "rubriques_edit.php3?new=oui", "rubrique-24.gif","creer.gif");
}



if ($options == "avancees") {
	echo "<p>";
	if (lire_meta("activer_messagerie") == 'oui' AND $connect_activer_messagerie != 'non') {
		icone_horizontale(_T('icone_messagerie_personnelle'), "messagerie.php3", "messagerie-24.gif");
	}

	icone_horizontale(_T('titre_forum'), "forum.php3", "forum-interne-24.gif","rien.gif");

	if ($connect_statut == "0minirezo") {
		if (lire_meta('forum_prive_admin') == 'oui') {
			icone_horizontale(_T('titre_page_forum'), "forum_admin.php3", "forum-admin-24.gif");
		}
			echo "<p>";
		if (lire_meta("activer_statistiques") == 'oui')
			icone_horizontale(_T('icone_statistiques'), "statistiques_visites.php3", "statistiques-24.gif");
		icone_horizontale(_T('titre_page_forum_suivi'), "controle_forum.php3", "suivi-forum-24.gif");
		if ($connect_toutes_rubriques)
			icone_horizontale(_T('texte_vider_cache'), "admin_vider.php3", "cache-24.gif");
	}
}
else if ($connect_statut == '0minirezo' and $connect_toutes_rubriques) {
	echo "<p>";
	icone_horizontale(_T('icone_configurer_site'), "configuration.php3", "administration-24.gif");
}

fin_raccourcis();


debut_droite();

if ($options != 'avancees') {
	debut_boite_info();
	echo "<div class='verdana2'>";
	echo "<p><center><b>&laquo;&nbsp;"._T('info_a_suivre')."</b></center>";
	echo "<p>"._T('texte_actualite_site_1')."<a href='index.php3?&set_options=avancees'>"._T('texte_actualite_site_2')."</a>"._T('texte_actualite_site_3');
	echo "</div>";
	fin_boite_info();
}




//
// Restauration d'une archive
//

if ($meta["debut_restauration"]) {
	@ignore_user_abort(1);
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
		$texte_boite = _T('info_base_restauration')."<p>
		<form name='progression'><center><input type='text' size=10 style='text-align:center;' name='taille' value='$taille'><br>
		<input type='text' class='forml' name='recharge' value='"._T('info_recharger_page')."'></center></form>";
	}
	else {
		$texte_boite = _T('info_erreur_restauration');
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
		echo _T('info_activer_cookie');
		echo aide ("cookie");
		echo "</td>";
		echo "<td width=10><img src='img_pack/rien.gif' width=10>";
		echo "</td>";
		echo "<td width='250'>";
		icone_horizontale(_T('icone_activer_cookie'), "../spip_cookie.php3?cookie_admin=".rawurlencode("@$connect_login")."&url=".rawurlencode("ecrire/index.php3"), "cookie-24.gif", "");
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
	afficher_articles(_T('info_article_a_paraitre'),
		"SELECT id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, id_rubrique, statut ".
		"FROM spip_articles WHERE statut='publie' AND date>NOW() ORDER BY date");
}


//
// Vos articles en cours de redaction
//

echo "<P align=left>";
$vos_articles = afficher_articles(_T('info_en_cours_validation'),
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
	echo "<font color='$couleur_foncee' face='arial,helvetica,sans-serif'><b>"._T('texte_en_cours_validation')."</b></font><p>";

	//
	// Les articles a valider
	//
	afficher_articles(_T('info_articles_proposes'),
		"SELECT id_article, surtitre, titre, soustitre, descriptif, chapo, date, visites, id_rubrique, statut ".
		"FROM spip_articles WHERE statut='prop'$vos_articles ORDER BY date DESC");

	//
	// Les breves a valider
	//
	$query = "SELECT * FROM spip_breves WHERE statut='prepa' OR statut='prop' ORDER BY date_heure DESC";
	afficher_breves(_T('info_breves_valider'), $query, true);

	//
	// Les sites references a valider
	//
	if (lire_meta('activer_syndic') != 'non') {
		include_ecrire("inc_sites.php3");
		afficher_sites(_T('info_site_valider'), "SELECT * FROM spip_syndic WHERE statut='prop' ORDER BY nom_site");
	}

	//
	// Les sites a probleme
	//
	if (lire_meta('activer_syndic') != 'non' AND $connect_statut == '0minirezo' AND $connect_toutes_rubriques) {
		include_ecrire("inc_sites.php3");
		afficher_sites(_T('avis_sites_syndiques_probleme'),
			"SELECT * FROM spip_syndic WHERE syndication='off' AND statut='publie' ORDER BY nom_site");
	}

	// Les articles syndiques en attente de validation
	if ($connect_statut == '0minirezo' AND $connect_toutes_rubriques) {
		$result = spip_query ("SELECT COUNT(*) AS compte FROM spip_syndic_articles WHERE statut='dispo'");
		if (($row = spip_fetch_array($result)) AND $row['compte'])
			echo "<br><small><a href='sites_tous.php3'>".$row['compte']." "._T('info_liens_syndiques_1')."</a> "._T('info_liens_syndiques_2')."</small>";
	}

	// Les forums en attente de moderation 
	if ($connect_statut == '0minirezo' AND $connect_toutes_rubriques) {
		$result = spip_query ("SELECT COUNT(*) AS compte FROM spip_forum WHERE statut='prop'");
		if (($row = spip_fetch_array($result)) AND $row['compte']) {
			echo "<br><small> <a href='controle_forum.php3'>".$row['compte']; 
			if ($row['compte']>1)
				echo " "._T('info_liens_syndiques_3')."</a> "._T('info_liens_syndiques_4');  
			else
				echo " "._T('info_liens_syndiques_5')."</a> "._T('info_liens_syndiques_6');
			echo " "._T('info_liens_syndiques_7')."</small>.";
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
	afficher_articles(_T('info_derniers_articles_publies'),
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
if (abs($t_jour - date('d')) > 2) {
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
