<?php

include ("inc.php3");
include_ecrire ("inc_logos.php3");
include_ecrire ("inc_mots.php3");
include_ecrire ("inc_documents.php3");


// date du jour
$today=getdate(time());
	$jour_today = $today["mday"];
	$mois_today = $today["mon"];
	$annee_today = $today["year"];

// sans arguments => mois courant
if (!$mois){
	$jour=$today["mday"];
	$mois=$today["mon"];
	$annee=$today["year"];
}




///// debut de la page
debut_page(nom_jour("$annee-$mois-$jour")." ".affdate("$annee-$mois-$jour"),  "asuivre", "calendrier");


//////// parents


barre_onglets("calendrier", "jour");


gros_titre(nom_jour("$annee-$mois-$jour")." ".affdate("$annee-$mois-$jour"));


echo "<p /><div align='center'>";
	echo "<font size='1'>";
	echo " <a href='message_edit.php3?rv=$annee-$mois-$jour&new=oui&type=pb' style='color: blue;'><IMG SRC='img_pack/m_envoi_bleu$spip_lang_rtl.gif' WIDTH='14' HEIGHT='7' BORDER='0'> "._T("lien_nouvea_pense_bete")."</a>";
	echo " &nbsp; <a href='message_edit.php3?rv=$annee-$mois-$jour&new=oui&type=normal' style='color: green;'><IMG SRC='img_pack/m_envoi$spip_lang_rtl.gif' WIDTH='14' HEIGHT='7' BORDER='0'> "._T("lien_nouveau_message")."</a>";

	if ($connect_statut == "0minirezo")
		echo " &nbsp; <a href='message_edit.php3?rv=$annee-$mois-$jour&new=oui&type=affich' style='color: #ff9900;'><IMG SRC='img_pack/m_envoi_jaune$spip_lang_rtl.gif' WIDTH='14' HEIGHT='7' BORDER='0'> "._T("lien_nouvelle_annonce")."</a>\n";
	echo "</font>";
echo "</div>\n";	


debut_gauche();

if ($jour != $jour_today OR $mois != $mois_today OR $annee != $annee_today) {
			icone(_T("info_aujourdhui")."<br>".affdate("$annee_today-$mois_today-$jour_today"), "calendrier_jour.php3", "calendrier-24.gif", "", "center");
			echo "<p />";
}

agenda ($mois-1, $annee, $jour, $mois, $annee);
agenda ($mois, $annee, $jour, $mois, $annee);
agenda ($mois+1, $annee, $jour, $mois, $annee);

	// afficher en reduction le tableau du jour suivant
	creer_colonne_droite();	
	calendrier_jour($jour+1,$mois,$annee, false);

debut_droite();

calendrier_jour($jour,$mois,$annee, true);

	// articles
	echo "<p />";
	$query_articles="SELECT * FROM spip_articles WHERE statut='publie' AND date >='$annee-$mois-$jour' AND date < DATE_ADD('$annee-$mois$jour', INTERVAL 1 DAY) ORDER BY titre";
	afficher_articles(_T('icone_articles'),$query_articles);

	// breves
	echo "<p />";
	$query_breves="SELECT * FROM spip_breves WHERE statut='publie' AND date_heure >='$annee-$mois-$jour' AND date_heure < DATE_ADD('$annee-$mois$jour', INTERVAL 1 DAY) ORDER BY titre";
	afficher_breves(_T('icone_breves'),$query_breves);


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
	debut_cadre_enfonce();
	echo "<font color='$couleur_foncee' face='arial,helvetica,sans-serif'><b>"._T('texte_en_cours_validation')."</b></font><p>";

	//
	// Les articles a valider
	//
	afficher_articles(_T('info_articles_proposes'),
		"WHERE statut='prop'$vos_articles ORDER BY date DESC");

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





fin_page();

?>
