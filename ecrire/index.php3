<?php

include ("inc.php3");
include_ecrire ("inc_calendrier.php");

if ($HTTP_REFERER && !strpos($HTTP_REFERER, '/ecrire/')) $bonjour = 'oui';

debut_page(_T('titre_page_index'), "asuivre", "asuivre");

debut_gauche();


if ($spip_display != 4) {
	
	//
	// Infos personnelles : nom, utilisation de la messagerie
	//
	
	
	echo "<p>";
	
	$titre_cadre = afficher_plus("auteurs_edit.php3?id_auteur=$connect_id_auteur");
	$titre_cadre .= majuscules(typo($connect_nom));
	
	debut_cadre_couleur_foncee("fiche-perso-24.gif", false, '', '');
	echo "<center><b>".$titre_cadre."</b></center>";
	fin_cadre_couleur_foncee();
	

	//
	// Supprimer le cookie, se deconnecter...
	//
	
	if ($connect_statut == "0minirezo" AND $cookie_admin) {
		$texte = _T('icone_supprimer_cookie');
		if ($spip_display != 1) $texte .= aide("cookie");
		icone_horizontale( $texte , "../spip_cookie.php3?cookie_admin=non&url=".rawurlencode("ecrire/index.php3"), "cookie-24.gif", "");
	}



	$nom_site_spip = propre(lire_meta("nom_site"));
	if (!$nom_site_spip) $nom_site_spip="SPIP";
	
	
	echo "<div>&nbsp;</div>";
	
	echo debut_cadre_relief("racine-site-24.gif", false, "", $nom_site_spip);


	if ($spip_display != 1) {
		include_ecrire("inc_logos.php3");
	
		$logo = decrire_logo("rubon0");
		if ($logo) {
			echo "<div style='text-align:center; margin-bottom: 5px;'><a href='naviguer.php3'>";
			echo $logo[2];
			echo "</a></div>";
		}
	}
	echo "<div class='verdana1'>";
	$query = "SELECT count(*) AS cnt FROM spip_articles where statut='publie'";
	$result = spip_fetch_array(spip_query($query));
	$nb_art_publie = $result['cnt'];
	$query = "SELECT count(*) AS cnt FROM spip_articles where statut='prop'";
	$result = spip_fetch_array(spip_query($query));
	$nb_art_prop = $result['cnt'];
	$query = "SELECT count(*) AS cnt FROM spip_articles where statut='prepa'";
	$result = spip_fetch_array(spip_query($query));
	$nb_art_redac= $result['cnt'];
	
	
	if ($nb_art_redac OR $nb_art_prop OR $nb_art_publie) 
	{
		echo afficher_plus("articles_page.php3")."<b>"._T('info_articles')."</b>";
		echo "<ul style='margin:0px; padding-$spip_lang_left: 20px; margin-bottom: 5px;'>";
		if ($nb_art_redac) echo "<li>"._T("texte_statut_en_cours_redaction").": ".$nb_art_redac;
		if ($nb_art_prop) echo "<li>"._T("texte_statut_attente_validation").": ".$nb_art_prop;
		if ($nb_art_publie) echo "<li><b>"._T("texte_statut_publies").": ".$nb_art_publie."</b>";
		echo "</ul>";
	}

	$query = "SELECT count(*) AS cnt FROM spip_breves where statut='publie'";
	$result = spip_fetch_array(spip_query($query));
	$nb_bre_publie = $result['cnt'];
	$query = "SELECT count(*) AS cnt FROM spip_breves where statut='prop'";
	$result = spip_fetch_array(spip_query($query));
	$nb_bre_prop = $result['cnt'];

	if ($nb_bre_prop OR $nb_bre_publie) 
	{
		echo afficher_plus("breves.php3")."<b>"._T('info_breves_02')."</b>";
		echo "<ul style='margin:0px; padding-$spip_lang_left: 20px; margin-bottom: 5px;'>";
		if ($nb_bre_prop) echo "<li>"._T("texte_statut_attente_validation").": ".$nb_bre_prop;
		if ($nb_bre_publie) echo "<li><b>"._T("texte_statut_publies").": ".$nb_bre_publie."</b>";
		echo "</ul>";
	}

	$query = "SELECT count(*) AS cnt FROM spip_forum where statut='publie'";
	$result = spip_fetch_array(spip_query($query));
	$nb_forum = $result['cnt'];

	if ($nb_forum) {
		if ($connect_statut == "0minirezo") echo afficher_plus("controle_forum.php3");
		echo "<b>"._T('onglet_messages_publics')."</b>";
		echo "<ul style='margin:0px; padding-$spip_lang_left: 20px; margin-bottom: 5px;'>";
		echo "<li><b>".$nb_forum."</b>";
		echo "</ul>";
	}

	$query = "SELECT count(*) AS cnt FROM spip_auteurs where statut='0minirezo'";
	$result = spip_fetch_array(spip_query($query));
	$nb_admin = $result['cnt'];

	$query = "SELECT count(*) AS cnt FROM spip_auteurs where statut='1comite'";
	$result = spip_fetch_array(spip_query($query));
	$nb_redac = $result['cnt'];

	$query = "SELECT count(*) AS cnt FROM spip_auteurs where statut='6forum'";
	$result = spip_fetch_array(spip_query($query));
	$nb_abonn = $result['cnt'];

	if ($nb_admin OR $nb_redac OR $nb_abonn) 
	{
		echo afficher_plus("auteurs.php3")."<b>"._T('icone_auteurs')."</b>";
		echo "<ul style='margin:0px; padding-$spip_lang_left: 20px; margin-bottom: 5px;'>";
		if ($nb_admin) echo "<li>"._T("info_administrateurs").": ".$nb_admin;
		if ($nb_redac) echo "<li>"._T("info_redacteurs").": ".$nb_redac;
		if ($nb_abonn) echo "<li>"._T("info_visiteurs").": ".$nb_abonn;
		echo "</ul>";
	}


	echo "</div>";

	
	echo fin_cadre_relief();


//
// Afficher les raccourcis : boutons de creation d'article et de breve, etc.
//


//
// Afficher les boutons de creation d'article et de breve
//

/*
debut_raccourcis();

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
		echo "<div class='verdana11'>"._T('info_ecrire_article')."</div>";
	}
}
if ($connect_statut == '0minirezo' and $connect_toutes_rubriques) {
	icone_horizontale(_T('icone_creer_rubrique_2'), "rubriques_edit.php3?new=oui", "rubrique-24.gif","creer.gif");
}


// La nouvelle interface rend ces raccourcis inutiles
if ($options == "avancees") {
	echo "<p>";
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
*/
	creer_colonne_droite();
	echo "<div>&nbsp;</div>";	


	
	//
	// Annonces
	//
	echo    http_calendrier_rv(sql_calendrier_taches_annonces(),"annonces");
	echo    http_calendrier_rv(sql_calendrier_taches_pb(),"pb") ;
	echo    http_calendrier_rv(sql_calendrier_taches_rv(), "rv");

	
	//
	// Afficher le calendrier du mois s'il y a des rendez-vous
	//
	
		$today = getdate(time());
		$jour_today = $today["mday"];
		$mois_today = $today["mon"];
		$annee_today = $today["year"];
		$date = date("Y-m-d", mktime(0,0,0,$mois_today, 1, $annee_today));
		$mois = mois($date);
		$annee = annee($date);
		$jour = jour($date);
	
		// rendez-vous personnels dans le mois
		$result_messages = spip_query("SELECT messages.id_message FROM spip_messages AS messages, spip_auteurs_messages AS lien ".
				"WHERE ((lien.id_auteur='$connect_id_auteur' AND lien.id_message=messages.id_message) OR messages.type='affich') ".
				"AND messages.rv='oui' AND messages.date_heure >='$annee-$mois-1' AND date_heure < DATE_ADD('$annee-$mois-1', INTERVAL 1 MONTH) ".
				"AND messages.statut='publie' LIMIT 0,1");
		if (spip_num_rows($result_messages)) {
			echo "<p />";
			echo http_calendrier_agenda ($mois_today, $annee_today, $jour_today, $mois_today, $annee_today);
		}
		// rendez-vous personnels dans le mois
		$result_messages = spip_query("SELECT messages.id_message FROM spip_messages AS messages, spip_auteurs_messages AS lien ".
				"WHERE ((lien.id_auteur='$connect_id_auteur' AND lien.id_message=messages.id_message) OR messages.type='affich') ".
				"AND messages.rv='oui' AND messages.date_heure >='$annee_today-$mois_today-$jour_today' AND messages.date_heure < DATE_ADD('$annee_today-$mois_today-$jour_today', INTERVAL 1 DAY) ".
				"AND messages.statut='publie' LIMIT 0,1");
		if (spip_num_rows($result_messages)) {
			echo "<p />";
		echo http_calendrier_jour($jour_today,$mois_today,$annee_today, "col");
		}
}


debut_droite();



//
// Restauration d'une archive
//

if ($meta["debut_restauration"]) {
	@ignore_user_abort(1);
	include ("inc_import.php3");

	$archive = $meta["fichier_restauration"];
	$my_pos = $meta["status_restauration"];
	$ok = @file_exists($archive);

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
	echo "<font FACE='Verdana,Arial,Sans,sans-serif' SIZE=4 color='black'><B>$texte_boite</B></font>";
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
// Articles post-dates en attente de publication
//

$post_dates = lire_meta("post_dates");

if ($post_dates == "non" AND $connect_statut == '0minirezo' AND $options == 'avancees') {
	echo "<p>";
	afficher_articles(_T('info_article_a_paraitre'),
		"WHERE statut='publie' AND date>NOW() ORDER BY date");
}



//
// Vos articles en cours de redaction
//

echo "<p>";
$vos_articles = afficher_articles(afficher_plus('articles_page.php3')._T('info_en_cours_validation'),
	", spip_auteurs_articles AS lien WHERE articles.id_article=lien.id_article ".
	"AND lien.id_auteur=$connect_id_auteur AND articles.statut='prepa' ORDER BY articles.date DESC");

if ($vos_articles) $vos_articles = ' AND articles.id_article NOT IN ('.join($vos_articles,',').')';

echo "<div>&nbsp;</div>";
echo debut_cadre_trait_couleur();

//
// Raccourcis pour malvoyants
//
if ($spip_display == 4) {
	debut_raccourcis();
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
			echo "<div class='verdana11'>"._T('info_ecrire_article')."</div>";
		}
	}
	if ($connect_statut == '0minirezo' and $connect_toutes_rubriques) {
		icone_horizontale(_T('icone_creer_rubrique_2'), "rubriques_edit.php3?new=oui", "rubrique-24.gif","creer.gif");
	}
	fin_raccourcis();
} else {

		$query = "SELECT id_rubrique FROM spip_rubriques LIMIT 0,1";
		$result = spip_query($query);
		$gadget = "";
		
		if (spip_num_rows($result) > 0) {
			$id_rubrique = $GLOBALS['id_rubrique'];
			if ($id_rubrique > 0) {
				$dans_rub = "&id_rubrique=$id_rubrique";
				$dans_parent = "&id_parent=$id_rubrique";
			}
			$gadget = "<table width='95%'><tr>";
			if ($connect_statut == "0minirezo") {
				$gadget .= "<td>";
				$gadget .= icone_horizontale(_T('icone_creer_rubrique'), "rubriques_edit.php3?new=oui", "rubrique-24.gif", "creer.gif", false);
				$gadget .= "</td>";
			}
			
			$gadget .= "<td>";
			$gadget .= icone_horizontale(_T('icone_ecrire_article'), "articles_edit.php3?new=oui$dans_rub", "article-24.gif","creer.gif", false);
			$gadget .= "</td>";
			
			$activer_breves = lire_meta("activer_breves");
			if ($activer_breves != "non") {
				$gadget .= "<td>";
				$gadget .= icone_horizontale(_T('icone_nouvelle_breve'), "breves_edit.php3?new=oui$dans_rub", "breve-24.gif","creer.gif", false);
				$gadget .= "</td>";
			}
			
			if (lire_meta("activer_sites") == 'oui') {
				if ($connect_statut == '0minirezo' OR lire_meta("proposer_sites") > 0) {
					$gadget .= "<td>";
					$gadget .= icone_horizontale(_T('info_sites_referencer'), "sites_edit.php3?new=oui$dans_rub", "site-24.gif","creer.gif", false);
					$gadget .= "</td>";
				}
			}
			
		}

		$gadget .= "</tr></table>";

	echo $gadget;
}

//
// Modification du cookie
//

if ($connect_statut == "0minirezo" AND $spip_display != 4) {
	if (!$cookie_admin) {
		echo "<div>&nbsp;</div>";
		echo "<table width=95%><tr>";
		echo "<td width=100%>";
		echo _T('info_activer_cookie');
		echo aide ("cookie");
		echo "</td>";
		echo "<td width=10><img src='img_pack/rien.gif' width=10 alt='' />";
		echo "</td>";
		echo "<td width='250'>";
		icone_horizontale(_T('icone_activer_cookie'), "../spip_cookie.php3?cookie_admin=".rawurlencode("@$connect_login")."&url=".rawurlencode("ecrire/index.php3"), "cookie-24.gif", "");
		echo "</td></tr></table>";
	}
}
		echo fin_cadre_trait_couleur();



//
// Verifier les boucles a mettre en relief
//

$relief = false;

if (!$relief) {
	$query = "SELECT id_article FROM spip_articles AS articles WHERE statut='prop'$vos_articles LIMIT 0,1";
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
	debut_cadre_couleur_foncee("",false, "", _T('texte_en_cours_validation'));
	
	//echo "<div class='verdana2' style='color: black;'><b>"._T('texte_en_cours_validation')."</b></div><p>";

	//
	// Les articles a valider
	//
	afficher_articles(_T('info_articles_proposes'),
		"WHERE statut='prop'$vos_articles ORDER BY date DESC");

	//
	// Les breves a valider
	//
	$query = "SELECT * FROM spip_breves WHERE statut='prepa' OR statut='prop' ORDER BY date_heure DESC";
	afficher_breves(afficher_plus('breves.php3')._T('info_breves_valider'), $query, true);

	//
	// Les sites references a valider
	//
	if (afficher_plus('sites_tous.php3').lire_meta('activer_syndic') != 'non') {
		include_ecrire("inc_sites.php3");
		afficher_sites(afficher_plus('sites_tous.php3')._T('info_site_valider'), "SELECT * FROM spip_syndic WHERE statut='prop' ORDER BY nom_site");
	}

	//
	// Les sites a probleme
	//
	if (lire_meta('activer_syndic') != 'non' AND $connect_statut == '0minirezo' AND $connect_toutes_rubriques) {
		include_ecrire("inc_sites.php3");
		afficher_sites(afficher_plus('sites_tous.php3')._T('avis_sites_syndiques_probleme'),
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

	fin_cadre_couleur_foncee();
}


if ($options == 'avancees') {

	/* Ne plus afficher: il y a la page "Tous vos articles" pour cela
	// Vos articles publies
	echo "<p>";
	afficher_articles(afficher_plus('articles_page.php3')._T('info_derniers_articles_publies'),
		", spip_auteurs_articles AS lien ".
		"WHERE articles.id_article=lien.id_article AND lien.id_auteur=\"$connect_id_auteur\" AND articles.statut=\"publie\" ORDER BY articles.date DESC", true);
	*/

	// Dernieres modifications d'articles
	include_ecrire("inc_suivi_revisions.php");
	afficher_suivi_versions (0, 0, false, "", true);
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
if (!$bonjour) {
	if ($optimiser == 'oui' || (time() - lire_meta('date_optimisation')) > 24 * 3600) {
		if (timeout('optimisation')) {
			ecrire_meta("date_optimisation", time());
			ecrire_metas();
			include ("optimiser.php3");
		}
	}
}

?>
