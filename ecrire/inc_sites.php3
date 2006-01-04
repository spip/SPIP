<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined("_ECRIRE_INC_VERSION")) return;
include_ecrire ("inc_sites_tous");
include_ecrire("inc_presentation");
include_ecrire("inc_rubriques");
include_ecrire ("inc_logos");
include_ecrire ("inc_mots");
include_ecrire ("inc_date");
include_ecrire ("inc_abstract_sql");
include_ecrire ("inc_config");

function sites_dist()
{
global 
  $analyser_site,
  $annee,
  $champs_extra,
  $cherche_mot,
  $clean_link,
  $connect_statut,
  $id_parent,
  $id_syndic,
  $invalider_caches,
  $jour,
  $miroir,
  $moderation,
  $modifier_site,
  $mois,
  $new,
  $nom_site,
  $nouv_mot,
  $nouveau_statut,
  $old_syndic,
  $options,
  $oubli,
  $redirect,
  $redirect_ok,
  $reload,
  $resume,
  $spip_lang_left,
  $spip_lang_right,
  $supp_mot,
  $syndication,
  $syndication_old,
  $url,
  $url_site,
  $url_syndic;

$id_rubrique = intval($id_parent);
$id_syndic = intval($id_syndic);

//
// Creation d'un site
//

$flag_administrable = ($connect_statut == '0minirezo' AND acces_rubrique($id_rubrique));


if ($flag_administrable && !_DIR_RESTREINT) {
		if ($supprimer_lien = intval($GLOBALS["supprimer_lien"]))
			spip_query("UPDATE spip_syndic_articles SET statut='refuse' WHERE id_syndic_article='$supprimer_lien'");
		if ($ajouter_lien = intval($GLOBALS["ajouter_lien"]))
			spip_query("UPDATE spip_syndic_articles SET statut='publie' WHERE id_syndic_article='$ajouter_lien'");
 }

if ($new == 'oui') {
	$flag_editable = ($flag_administrable OR ($GLOBALS['meta']["proposer_sites"] > 0));

	if ($flag_editable) {
	
		spip_query("DELETE FROM spip_syndic WHERE (statut = 'refuse') && (maj < " . date("YmdHis", time() - 12 * 3600) . ")");

	
		$moderation = ($GLOBALS['meta']["moderation_sites"] == "oui")? 'oui' : 'non';
	
		$id_syndic = spip_abstract_insert("spip_syndic",
					 "(nom_site, id_rubrique, id_secteur, date, date_syndic, statut, syndication, moderation)",
					 "('"._T('avis_site_introuvable')."', $id_rubrique, $id_rubrique, NOW(), NOW(), 'refuse', 'non', '$moderation')");
		$statut = 'prop';
	}
 } else {

  $result = spip_query("SELECT statut, id_rubrique FROM spip_syndic WHERE id_syndic=$id_syndic");

  if ($row = spip_fetch_array($result)) {
    $statut = $row["statut"];
    if (!$id_rubrique) {
      $id_rubrique = $row["id_rubrique"];
      $flag_administrable = ($connect_statut == '0minirezo' AND acces_rubrique($id_rubrique));}

    $flag_editable = ($flag_administrable OR ($GLOBALS['meta']["proposer_sites"] > 0 AND ($statut == 'prop')));
  }
 }

//
// Analyse automatique d'une URL
//

if ($analyser_site == 'oui' AND $flag_editable) {

	$v = analyser_site($url);

	if ($v) {
		$nom_site = addslashes($v['nom_site']);
		$url_site = addslashes($v['url_site']);
		if (!$nom_site) $nom_site = $url_site;
		$url_syndic = trim(addslashes($v['url_syndic']));
		$descriptif = addslashes($v['descriptif']);
		$syndication = $v[syndic] ? 'oui' : 'non';
		$result = spip_query("UPDATE spip_syndic ".
			"SET nom_site='$nom_site', url_site='$url_site',
			url_syndic='$url_syndic', descriptif='$descriptif',
			syndication='$syndication', statut='$statut'
			WHERE id_syndic=$id_syndic");
		if ($syndication == 'oui') syndic_a_jour($id_syndic);
		$link = new Link(generer_url_ecrire('sites'));
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
			include_ecrire ("inc_index");
			marquer_indexer('syndic', $id_syndic);
		}
	}
}

if (strval($nom_site)!='' AND $modifier_site == 'oui' AND $flag_editable) {
	$nom_site = addslashes($nom_site);
	$url_site = addslashes($url_site);
	$descriptif = addslashes($descriptif);
	if (strlen($url_syndic) < 8) $syndication = "non";
	$url_syndic = trim(addslashes($url_syndic));
	
	// recoller les champs du extra
	if ($champs_extra) {
		include_ecrire("inc_extra");
		$add_extra = ", extra = '".addslashes(extra_recup_saisie("sites"))."'";
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
			include_ecrire ("inc_invalideur");
			suivre_invalideur("id='id_syndic/$id_syndic'");
		}
		if ($GLOBALS['meta']['activer_moteur'] == 'oui') {
			include_ecrire ("inc_index");
			marquer_indexer('syndic', $id_syndic);
		}
	}
	$link = new Link(generer_url_ecrire('sites'));
	$link->addVar('id_syndic');
	$link->addVar('redirect');
	$link->addVar('reload', $reload);
	$redirect = $link->getUrl();
	$redirect_ok = 'oui';
}


if ($jour AND $flag_administrable) {
	if ($annee == "0000") $mois = "00";
	if ($mois == "00") $jour = "00";
	spip_query("UPDATE spip_syndic SET date='" . 
		   addslashes("$annee-$mois-$jour") . 
		   "' WHERE id_syndic=$id_syndic");
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
	$result = spip_query ("SELECT id_syndic FROM spip_syndic WHERE id_syndic=$id_syndic AND syndication IN ('oui', 'sus', 'off') LIMIT 1");
	if ($result AND spip_num_rows($result)>0)
		$erreur_syndic = syndic_a_jour ($id_syndic);
}


//
// Afficher la page
//

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
	icone (_T('icone_voir_sites_references'), generer_url_ecrire("sites_tous",""), "site-24.gif","rien.gif");
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
	$link = new Link(generer_url_ecrire('sites_edit'));
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
		echo afficher_formulaire_date(generer_url_ecrire("sites", "id_syndic=$id_syndic&options=$options"), _T('info_date_referencement'), $jour, $mois, $annee);
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
	debut_cadre_relief("racine-site-24.gif");

	echo "<form action='", generer_url_ecrire('sites'), "'>\n",
	  "<center><b>",
	  _T('info_statut_site_1'),
	  "</b> &nbsp;&nbsp; \n",
	  "<input type='hidden' name='id_parent' value='$id_rubrique' />\n",
	  "<input type='hidden' name='id_syndic' value='$id_syndic' />\n",
	  "<select name='nouveau_statut' size='1' class='fondl'>\n",
	  my_sel("prop",_T('info_statut_site_3'),$statut),
	  my_sel("publie",_T('info_statut_site_2'),$statut),
	  my_sel("refuse",_T('info_statut_site_4'),$statut),
	  "</select>\n",
	  " &nbsp;&nbsp;&nbsp; ",
	  "<input type='submit' value='",
	  _T('bouton_valider'),
	  "' class='fondo' />\n",
	  "</center>\n",
	  "</form>\n";
	fin_cadre_relief();
}

if ($syndication == "oui" OR $syndication == "off" OR $syndication == "sus") {
	echo "<p><font size=3 face='Verdana,Arial,Sans,sans-serif'><b>"._T('info_site_syndique')."</b></font>";

	if ($erreur_syndic)
		echo "<p><font color=red><b>$erreur_syndic</b></font>";

	if ($syndication == "off" OR $syndication=="sus") {
		debut_boite_info();
		echo _T('avis_site_syndique_probleme', array('url_syndic' => $url_syndic));
		echo "<center><b>";
		echo "<a href='" . generer_url_ecrire("sites","id_syndic=$id_syndic&reload=oui") . "'>";
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
		. "<form method='post' action='" . generer_url_ecrire("sites","id_syndic=$id_syndic") . "'>"
		. "<input type='submit' name='reload' value=\""
		. attribut_html(_T('lien_mise_a_jour_syndication'))
		. "\" class='fondo' style='font-size:9px;' /></form></div>\n";

	// Options
	if ($flag_administrable && $options=='avancees') {

		debut_cadre_relief();
		echo "<u>"._T('syndic_options')."</u>"
			. aide('artsyn')."\n"
			. "<form method='POST' action='" . generer_url_ecrire("sites","id_syndic=$id_syndic") . "' class='verdana2'>\n";

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
else if (preg_match(',^select: (.*),', trim($url_syndic), $regs)) {
	echo "<br /><br />\n";
	echo "<form method='post' action='" . generer_url_ecrire("sites","id_syndic=$id_syndic") . "'>";
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
		include_ecrire("inc_extra");
		extra_affichage($extra, "sites");
	}

fin_cadre_relief();



//////////////////////////////////////////////////////
// Forums
//

echo "<br><br>\n";

 $forum_retour = generer_url_ecrire("sites","id_syndic=$id_syndic");

$link = new Link(generer_url_ecrire('forum_envoi'));
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
}

// helas strtotime ne reconnait pas le format W3C
// http://www.w3.org/TR/NOTE-datetime
function my_strtotime($la_date) {

	if (preg_match(
	',^([0-9]+-[0-9]+-[0-9]+T[0-9]+:[0-9]+(:[0-9]+)?)(\.[0-9]+)?'
	.'(Z|([-+][0-9][0-9]):[0-9]+)?$,',
	$la_date, $match)) {
		$la_date = str_replace("T", " ", $match[1])." GMT";
		return strtotime($la_date) - intval($match[5]) * 3600;
	}

	$s = strtotime($la_date);
	if ($s > 0)
		return $s;

	// erreur
	spip_log("Impossible de lire le format de date '$la_date'");
	return false;
}


function analyser_site($url) {
	include_ecrire("inc_filtres"); # pour filtrer_entites()
	include_ecrire("inc_distant");

	// Accepter les URLs au format feed:// ou qui ont oublie le http://
	$url = preg_replace(',^feed://,i', 'http://', $url);
	if (!preg_match(',^[a-z]+://,i', $url)) $url = 'http://'.$url;

	$texte = recuperer_page($url, true);
	if (!$texte) return false;

	if (preg_match(',<(channel|feed)([:[:space:]][^>]*)?'
	.'>(.*)</\1>,ims', $texte, $regs)) {
		$result['syndic'] = true;
		$result['url_syndic'] = $url;
		$channel = $regs[3];

		list($header) = preg_split(
		',<(entry|item)([:[:space:]][^>]*)?'.'>,Uims', $channel,2);
		if (preg_match(',<title[^>]*>(.*)</title>,Uims', $header, $r))
			$result['nom_site'] = supprimer_tags(filtrer_entites($r[1]));
		if (preg_match(
		',<link[^>]*[[:space:]]rel=["\']?alternate[^>]*>(.*)</link>,Uims',
		$header, $regs))
			$result['url_site'] = filtrer_entites($regs[1]);
		else if (preg_match(',<link[^>]*[[:space:]]rel=.alternate[^>]*>,Uims',
		$header, $regs))
			$result['url_site'] = filtrer_entites(extraire_attribut($regs[0], 'href'));
		else if (preg_match(',<link[^>]*>(.*)</link>,Uims', $header, $regs))
			$result['url_site'] = filtrer_entites($regs[1]);
		else if (preg_match(',<link[^>]*>,Uims', $header, $regs))
			$result['url_site'] = filtrer_entites(extraire_attribut($regs[0], 'href'));
		$result['url_site'] = url_absolue($result['url_site'], $url);

		if (preg_match(',<(description|tagline)([[:space:]][^>]*)?'
		.'>(.*)</\1>,Uims', $header, $r))
			$result['descriptif'] = filtrer_entites($r[3]);
	}
	else {
		$result['syndic'] = false;
		$result['url_site'] = $url;
		if (eregi('<head>(.*)', $texte, $regs))
			$head = filtrer_entites(eregi_replace('</head>.*', '', $regs[1]));
		else
			$head = $texte;
		if (eregi('<title[^>]*>(.*)', $head, $regs))
			$result['nom_site'] = filtrer_entites(supprimer_tags(eregi_replace('</title>.*', '', $regs[1])));
		if (eregi('<meta[[:space:]]+(name|http\-equiv)[[:space:]]*=[[:space:]]*[\'"]?description[\'"]?[[:space:]]+(content|value)[[:space:]]*=[[:space:]]*[\'"]([^>]+)[\'"]>', $head, $regs))
			$result['descriptif'] = filtrer_entites(supprimer_tags($regs[3]));

		// Cherchons quand meme un backend
		include_ecrire('inc_distant');
		include_ecrire('feedfinder');
		$feeds = get_feed_from_url($url, $texte);
		if (count($feeds)>1) {
			spip_log("feedfinder.php :\n".join("\n", $feeds));
			$result['url_syndic'] = "select: ".join(' ',$feeds);
		} else
			$result['url_syndic'] = $feeds[0];
	}
	return $result;
}


// A partir d'un <dc:subject> ou autre essayer de recuperer
// le mot et son url ; on cree <a href="url" rel="tag">mot</a>
function creer_tag($mot,$type,$url) {
	if (!strlen($mot = trim($mot))) return '';
	$mot = "<a rel=\"tag\">$mot</a>";
	if ($url)
		$mot = inserer_attribut($mot, 'href', $url);
	if ($type)
		$mot = inserer_attribut($mot, 'rel', $type);
	return $mot;
}
function ajouter_tags($matches, $item) {
	include_ecrire('inc_filtres');
	$tags = array();
	foreach ($matches as $match) {
		$type = ($match[3] == 'category') ? 'category':'tag';
		$mot = supprimer_tags($match[0]);
		if (!strlen($mot)) break;
		// rechercher un url
		if ($url = extraire_attribut($match[0], 'domain')
		OR $url = extraire_attribut($match[0], 'resource')
		OR $url = extraire_attribut($match[0], 'url'))
			{}

		## cas particuliers
		else if (extraire_attribut($match[0], 'scheme') == 'urn:flickr:tags') {
			foreach(explode(' ', $mot) as $petit)
				if ($t = creer_tag($petit, $type,
				'http://www.flickr.com/photos/tags/'.urlencode($petit).'/'))
					$tags[] = $t;
			$mot = '';
		} else {
			# type del.icio.us
			foreach(explode(' ', $mot) as $petit)
				if (preg_match(',<rdf[^>]* resource=["\']([^>]*/'
				.preg_quote(urlencode($petit),',').')["\'],i',
				$item, $m)) {
					$mot = '';
					if ($t = creer_tag($petit, $type, $m[1]))
						$tags[] = $t;
				}
		}

		if ($t = creer_tag($mot, $type, $url))
			$tags[] = $t;
	}
	return $tags;
}


// Retablit le contenu des blocs [[CDATA]] dans un tableau
function cdata_echappe_retour(&$table, &$echappe_cdata) {
	foreach ($table as $var => $val) {
		$table[$var] = filtrer_entites($table[$var]);
		foreach ($echappe_cdata as $n => $e)
			$table[$var] = str_replace("@@@SPIP_CDATA$n@@@",
				$e, $table[$var]);
	}
}


// prend un fichier backend et retourne un tableau des items lus,
// et une chaine en cas d'erreur
function analyser_backend($rss, $url_syndic='') {
	include_ecrire("inc_texte"); # pour couper()

	// Echapper les CDATA
	$echappe_cdata = array();
	if (preg_match_all(',<!\[CDATA\[(.*)]]>,Uims', $rss,
	$regs, PREG_SET_ORDER)) {
		foreach ($regs as $n => $reg) {
			$echappe_cdata[$n] = $reg[1];
			$rss = str_replace($reg[0], "@@@SPIP_CDATA$n@@@", $rss);
		}
	}

	// supprimer les commentaires
	$rss = preg_replace(',<!--\s+.*\s-->,Ums', '', $rss);

	// simplifier le backend, en supprimant les espaces de nommage type "dc:"
	$rss = preg_replace(',<(/?)(dc):,i', '<\1', $rss);

	// chercher auteur/lang dans le fil au cas ou les items n'en auraient pas
	list($header) = preg_split(',<(item|entry)[:[:space:]>],', $rss, 2);
	if (preg_match_all(
	',<(author|creator)>(.*)</\1>,Uims',
	$header, $regs, PREG_SET_ORDER)) {
		$les_auteurs_du_site = array();
		foreach ($regs as $reg) {
			$nom = $reg[2];
			if (preg_match(',<name>(.*)</name>,Uims', $nom, $reg))
				$nom = $reg[1];
			$les_auteurs_du_site[] = trim(textebrut(filtrer_entites($nom)));
		}
		$les_auteurs_du_site = join(', ', array_unique($les_auteurs_du_site));
	} else
		$les_auteurs_du_site = '';

	if (preg_match(',<([^>]*xml:)?lang(uage)?'.'>([^<>]+)<,i',
	$header, $match))
		$langue_du_site = $match[3];

	$items = array();
	if (preg_match_all(',<(item|entry)([:[:space:]][^>]*)?'.
	'>(.*)</\1>,Uims',$rss,$r, PREG_PATTERN_ORDER))
		$items = $r[0];

	//
	// Analyser chaque <item>...</item> du backend et le transformer en tableau
	//

	if (!count($items)) return _T('avis_echec_syndication_01');
	foreach ($items as $item) {
		$data = array();

		// URL (semi-obligatoire, sert de cle)
		if (preg_match(
		',<link[^>]*[[:space:]]rel=["\']?alternate[^>]*>(.*)</link>,Uims',
		$item, $regs))
			$data['url'] = $regs[1];
		else if (preg_match(',<link[^>]*[[:space:]]rel=.alternate[^>]*>,Uims',
		$item, $regs))
			$data['url'] = extraire_attribut($regs[0], 'href');
		else if (preg_match(',<link[^>]*>(.*)</link>,Uims', $item, $regs))
			$data['url'] = $regs[1];
		else if (preg_match(',<link[^>]*>,Uims', $item, $regs))
			$data['url'] = extraire_attribut($regs[0], 'href');
		// guid n'est un URL que si marque de <guid permalink="true">
		else if (preg_match(',<guid.*>[[:space:]]*(https?:[^<]*)</guid>,Uims',
		$item, $regs))
			$data['url'] = $regs[1];
		else
			$data['url'] = '';

		$data['url'] = url_absolue(filtrer_entites($data['url']), $url_syndic);

		// Titre (semi-obligatoire)
		if (preg_match(",<title>(.*?)</title>,ims",$item,$match))
			$data['titre'] = $match[1];
			else if (preg_match(',<link[[:space:]][^>]*>,Uims',$item,$mat)
			AND $title = extraire_attribut($mat[0], 'title'))
				$data['titre'] = $title; 
		if (!$data['titre'] = trim($data['titre']))
			$data['titre'] = _T('ecrire:info_sans_titre');

		// Date
		$la_date = '';
		if (preg_match(',<(published|modified|issued)>([^<]*)<,Uims',
		$item,$match))
			$la_date = my_strtotime($match[2]);
		if (!$la_date AND
		preg_match(',<(pubdate)>([^<]*)<,Uims',$item, $match))
			$la_date = my_strtotime($match[2]);
		if (!$la_date AND
		preg_match(',<([a-z]+:date)>([^<]*)<,Uims',$item,$match))
			$la_date = my_strtotime($match[2]);
		if (!$la_date AND
		preg_match(',<date>([^<]*)<,Uims',$item,$match))
			$la_date = my_strtotime($match[1]);

		if ($la_date < time() - 365 * 24 * 3600
		OR $la_date > time() + 48 * 3600)
			$la_date = time();
		$data['date'] = $la_date;

		// Honorer le <lastbuilddate> en forcant la date
		if (preg_match(',<(lastbuilddate|modified)>([^<>]+)</\1>,i',
		$item, $regs)
		AND $lastbuilddate = my_strtotime(trim($regs[2]))
		// pas dans le futur
		AND $lastbuilddate < time())
			$data['lastbuilddate'] = $lastbuilddate;

		// Auteur(s)
		if (preg_match_all(
		',<(author|creator)>(.*)</\1>,Uims',
		$item, $regs, PREG_SET_ORDER)) {
			$auteurs = array();
			foreach ($regs as $reg) {
				$nom = $reg[2];
				if (preg_match(',<name>(.*)</name>,Uims', $nom, $reg))
					$nom = $reg[1];
				$auteurs[] = trim(textebrut(filtrer_entites($nom)));
			}
			$data['lesauteurs'] = join(', ', array_unique($auteurs));
		}
		else
			$data['lesauteurs'] = $les_auteurs_du_site;

		// Description
		if (preg_match(',<((description|summary)([:[:space:]][^>]*)?)'
		.'>(.*)</\2[:>[:space:]],Uims',$item,$match)) {
			$data['descriptif'] = $match[4];
		}
		if (preg_match(',<((content)([:[:space:]][^>]*)?)'
		.'>(.*)</\2[:>[:space:]],Uims',$item,$match)) {
			$data['content'] = $match[4];
		}

		// lang
		if (preg_match(',<([^>]*xml:)?lang(uage)?'.'>([^<>]+)<,i',
			$item, $match))
			$data['lang'] = trim($match[3]);
		else
			$data['lang'] = trim($langue_du_site);

		// source et url_source  (pas trouve d'exemple en ligne !!)
		# <source url="http://www.truc.net/music/uatsap.mp3" length="19917" />
		# <source url="http://www.truc.net/rss">Site source</source>
		if (preg_match(',(<source[^>]*>)(([^<>]+)</source>)?,i',
		$item, $match)) {
			$data['source'] = trim($match[3]);
			$data['url_source'] = str_replace('&amp;', '&',
				trim(extraire_attribut($match[1], 'url')));
		}

		// tags
		# a partir de "<dc:subject>", (del.icio.us)
		# ou <media:category> (flickr)
		# ou <itunes:category> (apple)
		# on cree nos tags microformat <a rel="category" href="url">titre</a>
		$tags = array();
		if (preg_match_all(
		',<(([a-z]+:)?(subject|category|keywords?|tags?|type))[^>]*>'
		.'(.*?)</\1>,ims',
		$item, $matches, PREG_SET_ORDER))
			$tags = ajouter_tags($matches, $item); # array()
		// Pieces jointes : s'il n'y a pas de microformat relEnclosure,
		// chercher <enclosure> au format RSS et les passer en microformat
		if (!afficher_enclosures(join(', ', $tags)))
			if (preg_match_all(',<enclosure[[:space:]][^<>]+>,i',
			$item, $matches, PREG_PATTERN_ORDER))
				$data['enclosures'] = join(', ',
					array_map('enclosure2microformat', $matches[0]));
		$data['item'] = $item;

		// Nettoyer les donnees et remettre les CDATA en place
		cdata_echappe_retour($data, $echappe_cdata);
		cdata_echappe_retour($tags, $echappe_cdata);

		// Trouver les microformats (ecrase les <category> et <dc:subject>)
		if (preg_match_all(
		',<a[[:space:]]([^>]+[[:space:]])?rel=[^>]+>.*</a>,Uims',
		$data['item'], $regs, PREG_PATTERN_ORDER)) {
			$tags = $regs[0];
		}
		// Cas particulier : tags Connotea sous la forme <a class="postedtag">
		if (preg_match_all(
		',<a[[:space:]][^>]+ class="postedtag"[^>]*>.*</a>,Uims',
		$data['item'], $regs, PREG_PATTERN_ORDER))
			$tags = preg_replace(', class="postedtag",i',
			' rel="tag"', $regs[0]);

		$data['tags'] = $tags;

		$articles[] = $data;
	}

	return $articles;
}

//
// Insere un article syndique (renvoie true si l'article est nouveau)
//
function inserer_article_syndique ($data, $now_id_syndic, $statut, $url_site, $url_syndic, $resume, $documents) {

	// Creer le lien s'il est nouveau - cle=(id_syndic,url)
	$le_lien = substr($data['url'], 0,255);
	if (spip_num_rows(spip_query(
		"SELECT * FROM spip_syndic_articles
		WHERE url='".addslashes($le_lien)."'
		AND id_syndic=$now_id_syndic"
	)) == 0 and !spip_sql_error()) {
		spip_query("INSERT INTO spip_syndic_articles
		(id_syndic, url, date, statut) VALUES
		('$now_id_syndic', '".addslashes($le_lien)."',
		FROM_UNIXTIME(".$data['date']."), '$statut')");
		$ajout = true;
	}

	// Descriptif, en mode resume ou mode 'full text'
	// on prend en priorite data['descriptif'] si on est en mode resume,
	// et data['content'] si on est en mode "full syndication"
	if ($resume != 'non') {
		// mode "resume"
		$desc = strlen($data['descriptif']) ?
			$data['descriptif'] : $data['content'];
		$desc = couper(trim(textebrut($desc)), 300);
	} else {
		// mode "full syndication"
		// choisir le contenu pertinent
		// & refaire les liens relatifs
		$desc = strlen($data['content']) ?
			$data['content'] : $data['descriptif'];
		$desc = liens_absolus($desc, $url_syndic);
	}

	// Mettre a jour la date si lastbuilddate
	$update_date = $data['lastbuilddate'] ?
		"date = FROM_UNIXTIME(".$data['lastbuilddate'].")," : '';

	// tags & enclosures (preparer spip_syndic_articles.tags)
	$tags = $data['enclosures'];
	# eviter les doublons (cle = url+titre) et passer d'un tableau a une chaine
	if ($data['tags']) {
		$vus = array();
		foreach ($data['tags'] as $tag) {
			$cle = supprimer_tags($tag).extraire_attribut($tag,'href');
			$vus[$cle] = $tag;
		}
		$tags .= ($tags ? ', ' : '') . join(', ', $vus);
	}

	// Mise a jour du contenu (titre,auteurs,description,date?,source...)
	spip_query ("UPDATE spip_syndic_articles SET
	titre='".addslashes($data['titre'])."',
	".$update_date."
	lesauteurs='".addslashes($data['lesauteurs'])."',
	descriptif='".addslashes($desc)."',
	lang='".addslashes(substr($data['lang'],0,10))."',
	source='".addslashes(substr($data['source'],0,255))."',
	url_source='".addslashes(substr($data['url_source'],0,255))."',
	tags='".addslashes($tags)."'
	WHERE id_syndic='$now_id_syndic' AND url='".addslashes($le_lien)."'");

	// Point d'entree post_syndication
	pipeline('post_syndication',
		array(
			$le_lien,
			$now_id_syndic,
			$data
		)
	);

	return $ajout;
}

//
// Mettre a jour le site
//
function syndic_a_jour($now_id_syndic, $statut = 'off') {
	include_ecrire("inc_texte");

	$query = "SELECT * FROM spip_syndic WHERE id_syndic='$now_id_syndic'";
	$result = spip_query($query);
	if (!$row = spip_fetch_array($result))
		return;

	$url_syndic = $row['url_syndic'];
	$url_site = $row['url_site'];

	if ($row['moderation'] == 'oui')
		$moderation = 'dispo';	// a valider
	else
		$moderation = 'publie';	// en ligne sans validation

	// Section critique : n'autoriser qu'une seule syndication
	// simultanee pour un site donne
	if (!spip_get_lock("syndication $url_syndic")) {
		spip_log("lock pour $url_syndic");
		return;
	}
	spip_query("UPDATE spip_syndic SET syndication='$statut',
		date_syndic=NOW() WHERE id_syndic='$now_id_syndic'");

	// Aller chercher les donnees du RSS et les analyser
	include_ecrire("inc_distant");
	$rss = recuperer_page($url_syndic, true);
	if (!$rss)
		$articles = _T('avis_echec_syndication_02');
	else
		$articles = analyser_backend($rss, $url_syndic);

	// Les enregistrer dans la base
	if (is_array($articles)) {
		$urls = array();
		foreach ($articles as $data) {
			inserer_article_syndique ($data, $now_id_syndic, $moderation, $url_site, $url_syndic, $row['resume'], $row['documents']);
			$urls[] = $data['url'];
		}

		// moderation automatique des liens qui sont sortis du feed
		if (count($urls) > 0
		AND $row['miroir'] == 'oui') {
			spip_query("UPDATE spip_syndic_articles
				SET statut='off', maj=maj
				WHERE id_syndic=$now_id_syndic
				AND NOT (url IN ('"
				. join("','", array_map('addslashes',$urls))
				. "'))");
		}

		// suppression apres 2 mois des liens qui sont sortis du feed
		if (count($urls) > 0
		AND $row['oubli'] == 'oui') {
			$time = date('U') - 61*24*3600; # deux mois
			spip_query("DELETE FROM spip_syndic_articles
				WHERE id_syndic=$now_id_syndic
				AND UNIX_TIMESTAMP(maj) < $time
				AND UNIX_TIMESTAMP(date) < $time
				AND NOT (url IN ('"
				. join("','", array_map('addslashes',$urls))
				. "'))");
		}


		// Noter que la syndication est OK
		spip_query("UPDATE spip_syndic SET syndication='oui'
		WHERE id_syndic='$now_id_syndic'");
	}

	// Ne pas oublier de liberer le verrou
	spip_release_lock($url_syndic);

	if ($liens_ajoutes) {
		spip_log("Syndication: $liens_ajoutes nouveau(x) lien(s)");
		include_ecrire('inc_rubriques');
		calculer_rubriques();
	}

	// Renvoyer l'erreur le cas echeant
	if (!is_array($articles))
		return $articles;
	else
		return false; # c'est bon
}



//
// Effectuer la syndication d'un unique site, retourne 0 si aucun a faire.
//

function executer_une_syndication() {
	$id_syndic = 0;

	## valeurs modifiables dans mes_options
	## attention il est tres mal vu de prendre une periode < 20 minutes
	define_once('_PERIODE_SYNDICATION', 2*60);
	define_once('_PERIODE_SYNDICATION_SUSPENDUE', 24*60);

	// On va tenter un site 'sus' ou 'off' de plus de 24h, et le passer en 'off'
	// s'il echoue
	$s = spip_query("SELECT * FROM spip_syndic
	WHERE syndication IN ('sus','off')
	AND statut='publie'
	AND date_syndic < DATE_SUB(NOW(), INTERVAL
	"._PERIODE_SYNDICATION_SUSPENDUE." MINUTE)
	ORDER BY date_syndic LIMIT 1");
	if ($row = spip_fetch_array($s)) {
		$id_syndic = $row["id_syndic"];
		syndic_a_jour($id_syndic, 'off');
	}

	// Et un site 'oui' de plus de 2 heures, qui passe en 'sus' s'il echoue
	$s = spip_query("SELECT * FROM spip_syndic
	WHERE syndication='oui'
	AND statut='publie'
	AND date_syndic < DATE_SUB(NOW(), INTERVAL "._PERIODE_SYNDICATION." MINUTE)
	ORDER BY date_syndic LIMIT 1");
	if ($row = spip_fetch_array($s)) {
		$id_syndic = $row["id_syndic"];
		syndic_a_jour($id_syndic, 'sus');
	}
	return $id_syndic;
}

?>
