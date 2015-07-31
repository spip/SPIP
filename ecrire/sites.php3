<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


include ("inc.php3");
include_ecrire("inc_presentation.php3");
include_ecrire("inc_texte.php3");
include_ecrire("inc_urls.php3");
include_ecrire("inc_rubriques.php3");
include_ecrire ("inc_logos.php3");
include_ecrire ("inc_mots.php3");
include_ecrire ("inc_sites.php3");
include_ecrire ("inc_date.php3");
include_ecrire ("inc_abstract_sql.php3");
include_ecrire ("inc_config.php3");

$proposer_sites = $GLOBALS['meta']["proposer_sites"];

$id_rubrique = intval($id_parent);


function calculer_droits() {
	global $connect_statut, $statut, $id_rubrique, $id_rubrique_depart, $proposer_sites, $new;
	global $flag_editable, $flag_administrable;
	$flag_administrable = ($connect_statut == '0minirezo' AND acces_rubrique($id_rubrique));
	if ($id_rubrique_depart > 0)
		 $flag_administrable &= acces_rubrique($id_rubrique_depart);
	$flag_editable = ($flag_administrable OR ($proposer_sites > 0 AND ($statut == 'prop' OR $new == 'oui')));
}


//
// Creation d'un site
//

if ($new == 'oui') {
	calculer_droits();

	if ($flag_editable) {
		$id_rubrique = intval($id_rubrique);
	
		$mydate = date("YmdHis", time() - 12 * 3600);
		$query = "DELETE FROM spip_syndic WHERE (statut = 'refuse') && (maj < $mydate)";
		$result = spip_query($query);
	
		$moderation = ($GLOBALS['meta']["moderation_sites"] == "oui")? 'oui' : 'non';
	
		$id_syndic = spip_abstract_insert("spip_syndic",
					 "(nom_site, id_rubrique, id_secteur, date, date_syndic, statut, syndication, moderation)",
					 "('"._T('avis_site_introuvable')."', $id_rubrique, $id_rubrique, NOW(), NOW(), 'refuse', 'non', '$moderation')");
	}
} else $id_syndic = intval($id_syndic);

$result = spip_query("SELECT statut, id_rubrique FROM spip_syndic
	WHERE id_syndic=$id_syndic");

if ($row = spip_fetch_array($result)) {
	$statut = $row["statut"];
	$id_rubrique_depart = $row["id_rubrique"];
	if (!$id_rubrique) $id_rubrique = $id_rubrique_depart;
}
if ($new == 'oui') $statut = 'prop';

calculer_droits();


//
// Analyse automatique d'une URL
//

if ($analyser_site == 'oui' AND $flag_editable) {

	$v = analyser_site($url);

	if ($v) {
		$nom_site = addslashes($v['nom_site']);
		$url_site = addslashes($v['url_site']);
		if (!$nom_site) $nom_site = $url_site;
		$url_syndic = addslashes($v['url_syndic']);
		$descriptif = addslashes($v['descriptif']);
		$syndication = $v[syndic] ? 'oui' : 'non';
		$result = spip_query("UPDATE spip_syndic ".
			"SET nom_site='$nom_site', url_site='$url_site',
			url_syndic='$url_syndic', descriptif='$descriptif',
			syndication='$syndication', statut='$statut'
			WHERE id_syndic=$id_syndic");
		if ($syndication == 'oui') syndic_a_jour($id_syndic);
		$link = new Link('sites.php3');
		$link->addVar('id_syndic');
		$link->addVar('redirect');
		$redirect = $link->getUrl();
		$redirect_ok = 'oui';
	}
}


//
// Ajout et suppression syndication
//

if ($nouveau_statut AND $flag_administrable) {
	$statut = $nouveau_statut;
	$result = spip_query("UPDATE spip_syndic SET statut='$statut'
	WHERE id_syndic=$id_syndic");
	if ($statut == 'publie')
		spip_query("UPDATE spip_syndic SET date=NOW() WHERE
		id_syndic=$id_syndic");

	calculer_rubriques();
	if ($statut == 'publie') {
		if ($GLOBALS['meta']['activer_moteur'] == 'oui') {
			include_ecrire ("inc_index.php3");
			marquer_indexer('syndic', $id_syndic);
		}
	}
}

if (strval($nom_site)!='' AND $modifier_site == 'oui' AND $flag_editable) {
	$nom_site = addslashes($nom_site);
	$url_site = addslashes($url_site);
	$descriptif = addslashes($descriptif);
	if (strlen($url_syndic) < 8) $syndication = "non";
	$url_syndic = addslashes($url_syndic);
	
	// recoller les champs du extra
	if ($champs_extra) {
		include_ecrire("inc_extra.php3");
		$add_extra = ", extra = '".addslashes(extra_recup_saisie("sites", $id_secteur))."'";
	} else
		$add_extra = '';
	
	
	
	spip_query("UPDATE spip_syndic SET id_rubrique='$id_rubrique',
	nom_site='$nom_site', url_site='$url_site', url_syndic='$url_syndic',
	descriptif='$descriptif', syndication='$syndication', statut='$statut'
	$add_extra WHERE id_syndic=$id_syndic");

	propager_les_secteurs();

	if ($syndication_old != $syndication
	OR $url_syndic != $old_syndic)
		$reload = "oui";

	if ($syndication_old != $syndication AND $syndication == "non")
		spip_query("DELETE FROM spip_syndic_articles
		WHERE id_syndic=$id_syndic");

	calculer_rubriques();

	// invalider et reindexer
	if ($statut == 'publie') {
		if ($invalider_caches) {
			include_ecrire ("inc_invalideur.php3");
			suivre_invalideur("id='id_syndic/$id_syndic'");
		}
		if ($GLOBALS['meta']['activer_moteur'] == 'oui') {
			include_ecrire ("inc_index.php3");
			marquer_indexer('syndic', $id_syndic);
		}
	}
	$link = new Link('sites.php3');
	$link->addVar('id_syndic');
	$link->addVar('redirect');
	$link->addVar('reload', $reload);
	$redirect = $link->getUrl();
	$redirect_ok = 'oui';
}


if ($jour AND $flag_administrable) {
	if ($annee == "0000") $mois = "00";
	if ($mois == "00") $jour = "00";
	spip_query("UPDATE spip_syndic SET date='$annee-$mois-$jour'
	WHERE id_syndic=$id_syndic");
	calculer_rubriques();
}



if ($redirect AND $redirect_ok == 'oui') {
	redirige_par_entete($redirect);
}

// Appliquer le choix resume/fulltexte (necessite un reload)
if ($flag_editable AND ($resume == 'oui' OR $resume == 'non')) {
	list($old_resume) = spip_fetch_array(spip_query(
		"SELECT resume FROM spip_syndic WHERE id_syndic=$id_syndic"));
	if ($old_resume <> $resume) $reload = 'oui';
	spip_query("UPDATE spip_syndic SET resume='$resume'
		WHERE id_syndic=$id_syndic");
}


//
// reload
//
if ($reload) {
	$result = spip_query ("SELECT * FROM spip_syndic WHERE id_syndic=$id_syndic
	AND syndication IN ('oui', 'sus', 'off')");
	if ($result AND spip_num_rows($result)>0)
		$erreur_syndic = syndic_a_jour ($id_syndic);
}


//
// Afficher la page
//

calculer_droits();

$result = spip_query("SELECT * FROM spip_syndic WHERE id_syndic=$id_syndic");

if ($row = spip_fetch_array($result)) {
	$id_syndic = $row["id_syndic"];
	$id_rubrique = $row["id_rubrique"];
	$nom_site = $row["nom_site"];
	$url_site = $row["url_site"];
	$url_syndic = $row["url_syndic"];
	$descriptif = $row["descriptif"];
	$syndication = $row["syndication"];
	$statut = $row["statut"];
	$date_heure = $row["date"];
	$date_syndic = $row['date_syndic'];
	$mod = $row['moderation'];

	$extra=$row["extra"];
	
	}

if ($nom_site)
	$titre_page = "&laquo; $nom_site &raquo;";
else
	$titre_page = _T('info_site');



debut_page("$titre_page","documents","sites");


//////// parents


debut_grand_cadre();

afficher_hierarchie($id_rubrique);

fin_grand_cadre();



debut_gauche();

debut_boite_info();
	echo "<center>";
	echo "<font face='Verdana,Arial,Sans,sans-serif' size=1><b>"._T('titre_site_numero')."</b></font>";
	echo "<br><font face='Verdana,Arial,Sans,sans-serif' size=6><b>$id_syndic</b></font>\n";

	voir_en_ligne ('site', $id_syndic, $statut);


	echo "</center>";
fin_boite_info();


echo "<p><center>";
	icone (_T('icone_voir_sites_references'), "sites_tous.php3", "site-24.gif","rien.gif");
echo "</center>";

if ($id_syndic AND $flag_administrable)
	afficher_boite_logo('site', 'id_syndic', $id_syndic, _T('logo_site')." ".aide ("rublogo"), _T('logo_survol'), 'site');


debut_droite();



debut_cadre_relief("site-24.gif");
echo "<center>";

if ($syndication == 'off' OR $syndication == 'sus') {
	$logo_statut = "puce-orange-anim.gif";
} 
else if ($statut == 'publie') {
	$logo_statut = "puce-verte.gif";
}
else if ($statut == 'prop') {
	$logo_statut = "puce-blanche.gif";
}
else if ($statut == 'refuse') {
	$logo_statut = "puce-rouge.gif";
}

echo "\n<table cellpadding=0 cellspacing=0 border=0 width='100%'>";
echo "<tr width='100%'><td width='100%' valign='top'>";
	gros_titre($nom_site, $logo_statut);

$url_affichee = $url_site;

if (strlen($url_affichee) > 40) $url_affichee = substr($url_affichee, 0, 30)."...";
echo "<a href='$url_site'><b>$url_affichee</b></a>";

if (strlen($descriptif) > 1) {
	echo "<p><div align='left' style='padding: 5px; border: 1px dashed #aaaaaa; background-color: #e4e4e4;'>";
	echo "<font size=2 face='Verdana,Arial,Sans,sans-serif'>";
	echo "<b>"._T('info_descriptif')."</b> ";
	echo propre($descriptif);
	echo "&nbsp; ";
	echo "</font>";
	echo "</div>";
}
echo "</td>";

if ($flag_editable) {
	$link = new Link('sites_edit.php3');
	$link->addVar('id_syndic');
	$link->addVar('target', $clean_link->getUrl());
	echo "<td>". http_img_pack('rien.gif', " ", "width='5'") . "</td>\n";
	echo "<td  align='right'>";
	icone(_T('icone_modifier_site'), $link->getUrl(), "site-24.gif", "edit.gif");
	echo "</td>";
}
echo "</tr></table>\n";



if ($flag_editable AND ($options == 'avancees' OR $statut == 'publie')) {
	if ($statut == 'publie') {
		echo "<p>";

		if (ereg("([0-9]{4})-([0-9]{2})-([0-9]{2})", $date_heure, $regs)) {
		        $mois = $regs[2];
		        $jour = $regs[3];
		        $annee = $regs[1];
		}


		debut_cadre_enfonce();
		echo afficher_formulaire_date("sites.php3?id_syndic=$id_syndic&options=$options", _T('info_date_referencement'), $jour, $mois, $annee);
		fin_cadre_enfonce();	
	}
	else {
		echo "<BR><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3>"._T('info_site_propose')." <B>".affdate($date_heure)."&nbsp;</B></FONT><P>";
	}
}

if ($flag_editable AND $options == 'avancees') {
	formulaire_mots('syndic', $id_syndic, $nouv_mot, $supp_mot, $cherche_mot, $flag_editable);
}

if ($flag_administrable) {
	$link = $GLOBALS['clean_link'];
	$link->delVar('new');
	echo $link->getForm('GET');
	debut_cadre_relief("racine-site-24.gif");
	echo "\n<center>";

	echo "<b>"._T('info_statut_site_1')."</b> &nbsp;&nbsp; \n";

	echo "<select name='nouveau_statut' size=1 class='fondl'>\n";

	echo my_sel("prop",_T('info_statut_site_3'),$statut);
	echo my_sel("publie",_T('info_statut_site_2'),$statut);
	echo my_sel("refuse",_T('info_statut_site_4'),$statut);

	echo "</select>\n";

	echo " &nbsp;&nbsp;&nbsp; <input type='submit' name='Valider' value='"._T('bouton_valider')."' class='fondo'>\n</center>\n";
	fin_cadre_relief();
	echo "</form>\n";
}

if ($syndication == "oui" OR $syndication == "off" OR $syndication == "sus") {
	echo "<p><font size=3 face='Verdana,Arial,Sans,sans-serif'><b>"._T('info_site_syndique')."</b></font>";

	if ($erreur_syndic)
		echo "<p><font color=red><b>$erreur_syndic</b></font>";

	if ($syndication == "off" OR $syndication=="sus") {
		debut_boite_info();
		echo _T('avis_site_syndique_probleme', array('url_syndic' => $url_syndic));
		echo "<center><b>";
		echo "<a href='sites.php3?id_syndic=$id_syndic&reload=oui'>";
		echo _T('lien_nouvelle_recuperation')."</a></b></center>\n";
		fin_boite_info();
	}
	afficher_syndic_articles(_T('titre_articles_syndiques'),
		"SELECT * FROM spip_syndic_articles WHERE id_syndic=$id_syndic
		ORDER BY date DESC");


	echo "<font face='verdana,arial,helvetica' size=2>";
	// afficher la date de dernier acces a la syndication
	if ($date_syndic)
		echo "<p><div align='left'>"._T('info_derniere_syndication').' '.affdate_heure($date_syndic)
		.".</div>\n";
		
		echo "<div align='right'>\n"
		. "<form method='post' action='sites.php3?id_syndic=$id_syndic'>"
		. "<input type='submit' name='reload' value=\""
		. attribut_html(_T('lien_mise_a_jour_syndication'))
		. "\" class='fondo' style='font-size:9px;' /></form></div>\n";

	// Options
	if ($flag_administrable && $options=='avancees') {

		debut_cadre_relief();
		echo "<u>"._T('syndic_options')."</u>"
			. aide('artsyn')."\n"
			. "<form method='POST' action='sites.php3?id_syndic=$id_syndic' class='verdana2'>\n";

		// modifier la moderation
		if ($moderation == 'oui' OR $moderation == 'non')
			spip_query("UPDATE spip_syndic SET moderation='$moderation'
			WHERE id_syndic=$id_syndic");
		else
			$moderation = $mod;
		if ($moderation != 'oui') $moderation='non';

		echo "<p><div align='$spip_lang_left'>"
			. _T('syndic_choix_moderation') . "<br />\n";
		afficher_choix('moderation', $moderation,
			array(
			'non' => _T('info_publier')
				.' ('._T('bouton_radio_modere_posteriori').')',
			'oui' => _T('info_bloquer')
				.' ('._T('bouton_radio_modere_priori').')'
			));

		// Oublier les vieux liens ?
		// Depublier les liens qui ne figurent plus ?
		# appliquer les choix
		if ($miroir == 'oui' OR $miroir == 'non')
			spip_query("UPDATE spip_syndic SET miroir='$miroir'
			WHERE id_syndic=$id_syndic");
		if ($oubli == 'oui' OR $oubli == 'non')
			spip_query("UPDATE spip_syndic SET oubli='$oubli'
			WHERE id_syndic=$id_syndic");

		echo "<p><div align='left'>"._T('syndic_choix_oublier');

		echo "<ul>\n";

		# miroir
		if (!$miroir AND !$miroir = $row['miroir']) $miroir = 'non';
		echo "<li>"._T('syndic_option_miroir').' ';
		afficher_choix('miroir', $miroir,
			array('oui' => _T('item_oui'), 'non' => _T('item_non')),
			" &nbsp; ");
		echo "</li>\n";

		# oubli
		if (!$oubli AND !$oubli = $row['oubli']) $oubli = 'non';
		echo "<li>"._T('syndic_option_oubli', array('mois' => 2)).' ';
		afficher_choix('oubli', $oubli,
			array('oui' => _T('item_oui'), 'non' => _T('item_non')),
			" &nbsp; ");
		echo "</li>\n";

		echo "</ul>\n";


		// Prendre les resumes ou le texte integral ?
		# appliquer les choix
		if ($resume == 'oui' OR $resume == 'non')
			spip_query("UPDATE spip_syndic SET resume='$resume'
			WHERE id_syndic=$id_syndic");
		if (!$resume AND !$resume = $row['resume']) $resume = 'oui';
		echo "<p><div align='$spip_lang_left'>"
			. _T('syndic_choix_resume') . "<br />\n";
		afficher_choix('resume', $resume,
			array(
				'oui' => _T('syndic_option_resume_oui'),
				'non' => _T('syndic_option_resume_non')
			));
		echo "</li>\n";


		// Bouton "Valider"
		echo "<div style='text-align:$spip_lang_right'><INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'></div>";


		fin_cadre_relief();
	}
	echo "</font>";
}
// Cas d'un site ayant un feedfinder detecte
else if (preg_match(',^select: (.*),', $url_syndic, $regs)) {
	echo "<br /><br />\n";
	echo "<form method='post' action='sites.php3?id_syndic=$id_syndic'>";
	foreach (
		array('id_rubrique', 'nom_site', 'url_site', 'descriptif', 'statut')
	as $var) {
		echo "<input type='hidden' name='$var' value=\"".entites_html($$var)."\" />";
	}
	echo debut_cadre_relief();
	echo "<div align='$spip_lang_left'>\n";
	echo "<INPUT TYPE='radio' NAME='syndication' VALUE='non' id='syndication_non' CHECKED>";
	echo " <b><label for='syndication_non'>"._T('bouton_radio_non_syndication')."</label></b><p>";
	echo "<INPUT TYPE='radio' NAME='syndication' VALUE='oui' id='syndication_oui'>";
	echo " <b><label for='syndication_oui'>"._T('bouton_radio_syndication')."</label></b> &nbsp;";

	$feeds = explode(' ',$regs[1]);
	echo "<select name='url_syndic'>\n";
	foreach ($feeds as $feed) {
		echo '<option value="'.entites_html($feed).'">'.$feed."</option>\n";
	}
	echo "</select>\n";
	echo aide("rubsyn");
	echo '<input type="hidden" name="modifier_site" value="oui" />';
	echo '<input type="hidden" name="reload" value="oui" />';
	echo "<div align='$spip_lang_right'><input type='submit' name='Valider' value='"._T('bouton_valider')."' class='fondo'></div>\n";
	echo fin_cadre_relief();
	echo "</div></form>\n";
}


if ($champs_extra AND $extra) {
		include_ecrire("inc_extra.php3");
		extra_affichage($extra, "sites");
	}

fin_cadre_relief();



//////////////////////////////////////////////////////
// Forums
//

echo "<br><br>\n";

$forum_retour = "sites.php3?id_syndic=$id_syndic";

$link = new Link('forum_envoi.php3');
$link->addVar('statut', 'prive');
$link->addVar('adresse_retour', $forum_retour);
$link->addVar('id_syndic');
$link->addVar('titre_message', $nom_site);


echo "<div align='center'>";
icone (_T('icone_poster_message'), $link->getUrl(), "forum-interne-24.gif", "creer.gif");
echo "</div>";

echo "<p align='left'>\n";

$result_forum = spip_query("SELECT * FROM spip_forum WHERE statut='prive'
AND id_syndic=$id_syndic AND id_parent=0 ORDER BY date_heure DESC LIMIT 20");
afficher_forum($result_forum, $forum_retour);


fin_page();

?>
