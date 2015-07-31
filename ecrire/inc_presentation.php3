<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_PRESENTATION")) return;
define("_ECRIRE_INC_PRESENTATION", "1");

include_ecrire ("inc_lang.php3");
utiliser_langue_visiteur();


//
// Aide
//
function aide($aide='') {
	global $couleur_foncee, $spip_lang_rtl, $dir_ecrire;

	if (!$aide) return;

	return "&nbsp;&nbsp;<a class='aide' href=\"".$dir_ecrire."aide_index.php3?aide=$aide\" target=\"spip_aide\" ".
		"onclick=\"javascript:window.open(this.href, 'spip_aide', 'scrollbars=yes, ".
		"resizable=yes, width=740, height=580'); return false;\"><img ".
		"src=\"img_pack/aide.gif\" alt=\""._T('info_image_aide')."\" ".
		"title=\""._T('titre_image_aide')."\" width=\"12\" height=\"12\" border=\"0\" ".
		"align=\"middle\"></a>";
}


//
// affiche un bouton imessage
//
function bouton_imessage($destinataire, $row = '') {
	// si on passe "force" au lieu de $row, on affiche l'icone sans verification
	global $connect_id_auteur;
	global $spip_lang_rtl;

	$url = new Link("message_edit.php3");

	// verifier que ce n'est pas un auto-message
	if ($destinataire == $connect_id_auteur)
		return;
	// verifier que le destinataire a un login

	if ($row != "force") {
		$login_req = "select login, messagerie from spip_auteurs where id_auteur=$destinataire AND en_ligne>DATE_SUB(NOW(),INTERVAL 15 DAY)";
		$row = spip_fetch_array(spip_query($login_req));

		if (($row['login'] == "") OR ($row['messagerie'] == "non")) {
			return;
		}
	}
	$url->addVar('dest',$destinataire);
	$url->addVar('new','oui');
	$url->addVar('type','normal');

	if ($destinataire) $title = _T('info_envoyer_message_prive');
	else $title = _T('info_ecire_message_prive');

	$texte_bouton = "<img src='img_pack/m_envoi$spip_lang_rtl.gif' width='14' height='7' border='0'>";
	return "<a href='". $url->getUrl() ."' title=\"$title\">$texte_bouton</a>";
}

//
// Cadres
//

function debut_cadre($style, $icone, $fonction) {
	global $spip_display;
	static $accesskey = 97; // a

	if ($GLOBALS['spip_lang_rtl']) {
		$g = 'd';
		$d = 'g';
		$bgright = 'background-position: right; ';
	} else {
		$g = 'g';
		$d = 'd';
	}

	// accesskey pour accessibilite espace prive
	$accesskey_c = chr($accesskey++);
	$retour_aff .= "<a name='access-$accesskey_c' href='#access-$accesskey_c' accesskey='$accesskey_c'></a>";

	if ($spip_display != 1){
		if (strlen($icone)<3) $icone = "rien.gif";
		$retour_aff .= "\n<table class='cadre' cellspacing='0' cellpadding='0'><tr>";
		$retour_aff .= "\n<td class='$style-h$g'></td>";
		$retour_aff .= "\n<td class='$style-h'>";
		if ($fonction) {
			$retour_aff .= "<div style='$bgright"."background: url(img_pack/$icone) no-repeat; padding: 0px; margin: 0px;'>";
			$retour_aff .= "<img src='img_pack/$fonction'>";
			$retour_aff .= "</div>";
		}
		else $retour_aff .= "<img src='img_pack/$icone'>";
		$retour_aff .= "</td>";
		$retour_aff .= "\n<td class='$style-h$d'></td></tr>";
		$retour_aff .= "\n<tr><td class='$style-$g'></td>";
		$retour_aff .= "\n<td class='$style-c'>";
	}
	return $retour_aff;
}

function fin_cadre($style) {
	global $spip_display;

	if ($GLOBALS['spip_lang_rtl']) {
		$g = 'd';
		$d = 'g';
	} else {
		$g = 'g';
		$d = 'd';
	}

	if ($spip_display != 1){
		$retour_aff .= "\n</td>";
		$retour_aff .= "\n<td class='$style-$d'></td></tr>";
		$retour_aff .= "\n<tr><td class='$style-b$g'></td>";
		$retour_aff .= "\n<td class='$style-b'></td>";
		$retour_aff .= "\n<td class='$style-b$d'></td></tr>";
		$retour_aff .= "\n<tr><td><img src='img_pack/rien.gif' alt='' width='1' height='5'></td></tr>";
		$retour_aff .= "\n</table>\n";
	}
	return $retour_aff;
}

function debut_cadre_relief($icone='', $return = false, $fonction=''){
	global $spip_display;
	if ($spip_display != 1){
		$retour_aff = debut_cadre('r', $icone, $fonction);
	}
	else {
		$retour_aff = "<p><div style='border-right: 1px solid #cccccc; border-bottom: 1px solid #cccccc; -moz-border-radius: 6px;'><div style='border: 1px solid #666666; padding: 5px; -moz-border-radius: 6px; background-color: white;'>";
	}

	if ($return) return $retour_aff;
	else echo $retour_aff;
}

function fin_cadre_relief($return = false){
	global $spip_display;
	if ($spip_display != 1){
		$retour_aff = fin_cadre('r');
	}
	else {
		$retour_aff = "</div></div></p>\n";
	}

	if ($return) return $retour_aff;
	else echo $retour_aff;
}


function debut_cadre_enfonce($icone='', $return = false, $fonction=''){
	global $spip_display;

	if ($spip_display != 1){
		$retour_aff = debut_cadre('e', $icone, $fonction);
	}
	else {
		$retour_aff = "<p><div style=\"border: 1px solid #333333; -moz-border-radius: 6px; background-color: #e0e0e0;\"><div style=\"padding: 5px; border-left: 1px solid #999999; border-top: 1px solid #999999; -moz-border-radius: 6px;\">";
	}

	if ($return) return $retour_aff;
	else echo $retour_aff;
}

function fin_cadre_enfonce($return = false){
	global $spip_display;

	if ($spip_display != 1) {
		$retour_aff = fin_cadre('e');
	}
	else {
		$retour_aff = "</div></div></p>\n";
	}

	if ($return) return $retour_aff;
	else echo $retour_aff;
}



//
// une boite alerte
//
function debut_boite_alerte() {
	echo "<p><table cellpadding='6' border='0'><tr><td width='100%' bgcolor='red'>";
	echo "<table width='100%' cellpadding='12' border='0'><tr><td width='100%' bgcolor='white'>";
}

function fin_boite_alerte() {
	echo "</td></tr></table>";
	echo "</td></tr></table>";
}


//
// une boite info
//
function debut_boite_info() {
	global $couleur_claire,  $couleur_foncee;
	echo "&nbsp;<p><div style='border: 1px dashed #666666;'><table cellpadding='5' cellspacing='0' border='0' width='100%' style='border-left: 1px solid $couleur_foncee; border-top: 1px solid $couleur_foncee; border-bottom: 1px solid white; border-bottom: 1px solid white' background=''>";
	echo "<tr><td bgcolor='$couleur_claire' width='100%'>";
	echo "<font face='Verdana,Arial,Sans,sans-serif' size='2' color='#333333'>";
}

function fin_boite_info() {
	echo "</font></td></tr></table></div>\n\n";
}

//
// une autre boite
//
function bandeau_titre_boite($titre, $afficher_auteurs, $boite_importante = true) {
	global $couleur_foncee;
	if ($boite_importante) {
		$couleur_fond = $couleur_foncee;
		$couleur_texte = '#FFFFFF';
	}
	else {
		$couleur_fond = '#EEEECC';
		$couleur_texte = '#000000';
	}
	echo "<tr bgcolor='$couleur_fond'><td width=\"100%\"><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=3 COLOR='$couleur_texte'>";
	echo "<B>$titre</B></FONT></TD>";
	if ($afficher_auteurs){
		echo "<TD WIDTH='100'>";
		echo "<img src='img_pack/rien.gif' alt='' width='100' height='12' border='0'>";
		echo "</TD>";
	}
	echo "<TD WIDTH='90'>";
	echo "<img src='img_pack/rien.gif' alt='' width='90' height='12' border='0'>";
	echo "</TD>";
	echo "</TR>";
}


//
// La boite raccourcis
//

function debut_raccourcis() {
	creer_colonne_droite();

	debut_cadre_enfonce();
	echo "<font face='Verdana,Arial,Sans,sans-serif' size=1>";
	echo "<b>"._T('titre_cadre_raccourcis')."</b><p>";
}

function fin_raccourcis() {
	echo "</font>";
	fin_cadre_enfonce();
}

//
// Fonctions d'affichage
//

function afficher_liste($largeurs, $table, $styles = '') {
	global $couleur_claire;

	if (!is_array($table)) return;
	reset($table);
	echo "\n";
	while (list(, $t) = each($table)) {
		$couleur_fond = ($ifond ^= 1) ? '#FFFFFF' : $couleur_claire;
		echo "<tr bgcolor=\"$couleur_fond\">";
		reset($largeurs);
		if ($styles) reset($styles);
		while (list(, $texte) = each($t)) {
			$style = $largeur = "";
			list(, $largeur) = each($largeurs);
			if ($styles) list(, $style) = each($styles);
			if (!trim($texte)) $texte .= "&nbsp;";
			echo "<td";
			if ($largeur) echo " width=\"$largeur\"";
			if ($style) echo " class=\"$style\"";
			echo ">$texte</td>";
		}
		echo "</tr>\n";
	}
	echo "\n";
}

function afficher_tranches_requete(&$query, $colspan) {
	static $ancre = 0;

	$query = trim($query);
	$query_count = eregi_replace('^(SELECT)[[:space:]].*[[:space:]](FROM)[[:space:]]', '\\1 COUNT(*) \\2 ', $query);

	list($num_rows) = spip_fetch_row(spip_query($query_count));
	if (!$num_rows) return;

	$nb_aff = 10;
	// Ne pas couper pour trop peu
	if ($num_rows <= 1.5 * $nb_aff) $nb_aff = $num_rows;
	if (ereg('LIMIT .*,([0-9]+)', $query, $regs)) {
		if ($num_rows > $regs[1]) $num_rows = $regs[1];
	}

	$texte = "\n";

	if ($num_rows > $nb_aff) {
		$tmp_var = $query;
		$deb_aff = intval(getTmpVar($tmp_var));
		$ancre++;

		$texte .= "<a name='a$ancre'></a>";
		$texte .= "<tr><td background=\"\" class=\"arial2\" colspan=\"".($colspan - 1)."\">";

		for ($i = 0; $i < $num_rows; $i += $nb_aff){
			$deb = $i + 1;
			$fin = $i + $nb_aff;
			if ($fin > $num_rows) $fin = $num_rows;
			if ($deb > 1) $texte .= " | ";
			if ($deb_aff + 1 >= $deb AND $deb_aff + 1 <= $fin) {
				$texte .= "<B>$deb</B>";
			}
			else {
				$link = new Link;
				$link->addTmpVar($tmp_var, strval($deb - 1));
				$texte .= "<A HREF=\"".$link->getUrl()."#a$ancre\">$deb</A>";
			}
		}
		$texte .= "</td>\n";
		$texte .= "<td background=\"\" class=\"arial2\" colspan=\"1\" align=\"right\" valign=\"top\">";
		if ($deb_aff == -1) {
			$texte .= "<B>"._T('info_tout_afficher')."</B>";
		} else {
			$link = new Link;
			$link->addTmpVar($tmp_var, -1);
			$texte .= "<A HREF=\"".$link->getUrl()."#a$ancre\">"._T('lien_tout_afficher')."</A>";
		}

		$texte .= "</td>\n";
		$texte .= "</tr>\n";


		if ($deb_aff != -1) {
			$query = eregi_replace('LIMIT[[:space:]].*$', '', $query);
			$query .= " LIMIT $deb_aff, $nb_aff";
		}
	}

	return $texte;
}


//
// Afficher tableau d'articles
//
function afficher_articles($titre_table, $requete, $afficher_visites = false, $afficher_auteurs = true,
		$toujours_afficher = false, $afficher_cadre = true, $afficher_descriptif = true) {

	global $connect_id_auteur, $connect_statut, $dir_lang;

	$activer_messagerie = lire_meta("activer_messagerie");
	$activer_statistiques = lire_meta("activer_statistiques");
	$activer_statistiques_ref = lire_meta("activer_statistiques_ref");
	$afficher_visites = ($afficher_visites AND $connect_statut == "0minirezo" AND $activer_statistiques != "non");

	if (!ereg("^SELECT", $requete)) {
		$select = "SELECT articles.id_article, articles.titre, articles.id_rubrique, articles.statut, articles.date";

		if ((lire_meta('multi_rubriques') == 'oui' AND $GLOBALS['coll'] == 0) OR lire_meta('multi_articles') == 'oui') {
			$afficher_langue = true;
			if ($GLOBALS['langue_rubrique']) $langue_defaut = $GLOBALS['langue_rubrique'];
			else $langue_defaut = lire_meta('langue_site');
			$select .= ", articles.lang";
		}
		if ($afficher_visites)
			$select .= ", articles.visites, articles.popularite";
		if ($afficher_descriptif)
			$select .= ", articles.descriptif";
		$select .= ", petitions.id_article AS petition ";
		$requete = $select . "FROM spip_articles AS articles " . $requete;
	}

	$tranches = afficher_tranches_requete($requete, $afficher_auteurs ? 3 : 2);

	$requete = str_replace("FROM spip_articles AS articles ", "FROM spip_articles AS articles LEFT JOIN spip_petitions AS petitions USING (id_article)", $requete);

	if (strlen($tranches) OR $toujours_afficher) {
	 	$result = spip_query($requete);

		if ($afficher_cadre) debut_cadre_relief("article-24.gif");
		echo "<table width=100% cellpadding=0 cellspacing=0 border=0><tr><td width=100% background=''>";
		echo "<table width=100% cellpadding=3 cellspacing=0 border=0>";

		bandeau_titre_boite($titre_table, $afficher_auteurs);

		echo $tranches;

		while ($row = spip_fetch_array($result)) {
			$vals = '';

			$id_article = $row['id_article'];
			$tous_id[] = $id_article;
			$titre = $row['titre'];
			$id_rubrique = $row['id_rubrique'];
			$date = $row['date'];
			$statut = $row['statut'];
			$visites = $row['visites'];
			if ($lang = $row['lang']) changer_typo($lang);
			$popularite = ceil(min(100,100 * $row['popularite'] / max(1, 0 + lire_meta('popularite_max'))));
			$descriptif = $row['descriptif'];
			if ($descriptif) $descriptif = ' title="'.attribut_html(typo($descriptif)).'"';
			$petition = $row['petition'];

			if ($afficher_auteurs) {
				$les_auteurs = "";
			 	$query2 = "SELECT auteurs.id_auteur, nom, messagerie, login, en_ligne ".
			 		"FROM spip_auteurs AS auteurs, spip_auteurs_articles AS lien ".
			 		"WHERE lien.id_article=$id_article AND auteurs.id_auteur=lien.id_auteur";
				$result_auteurs = spip_query($query2);

				while ($row = spip_fetch_array($result_auteurs)) {
					$id_auteur = $row['id_auteur'];
					$nom_auteur = typo($row['nom']);
					$auteur_messagerie = $row['messagerie'];

					$les_auteurs .= ", $nom_auteur";
					if ($id_auteur != $connect_id_auteur AND $auteur_messagerie != "non" AND $activer_messagerie != "non") {
						$les_auteurs .= "&nbsp;".bouton_imessage($id_auteur, $row);
					}
				}
				$les_auteurs = substr($les_auteurs, 2);
			}

			switch ($statut) {
			case 'publie':
				$puce = 'verte';
				$title = _T('info_article_publie');
				break;
			case 'prepa':
				$puce = 'blanche';
				$title = _T('info_article_redaction');
				break;
			case 'prop':
				$puce = 'orange';
				$title = _T('info_article_propose');
				break;
			case 'refuse':
				$puce = 'rouge';
				$title = _T('info_article_refuse');
				break;
			case 'poubelle':
				$puce = 'poubelle';
				$title = _T('info_article_supprime');
				break;
			}
			$s = "<a href=\"articles.php3?id_article=$id_article\" title=\"$title\">";
			$puce = "puce-$puce.gif";

			$s .= "<img src=\"img_pack/$puce\" alt='' width=\"13\" height=\"14\" border=\"0\"></a>&nbsp;&nbsp;";
			if (acces_restreint_rubrique($id_rubrique))
				$s .= "<img src='img_pack/admin-12.gif' alt='' width='12' height='12' title='"._T('titre_image_admin_article')."'>&nbsp;";
			$s .= "<a href=\"articles.php3?id_article=$id_article\"$descriptif$dir_lang>".typo($titre)."</a>";
			if ($afficher_langue AND $lang != $langue_defaut)
				$s .= " <font size='1' color='#666666'$dir_lang>(".traduire_nom_langue($lang).")</font>";
			if ($petition) $s .= " <Font size=1 color='red'>"._T('lien_petitions')."</font>";

			$vals[] = $s;

			if ($afficher_auteurs) $vals[] = $les_auteurs;

			$s = affdate_court($date);
			
			if ($afficher_visites AND $visites > 0) {
				$s .= "<br><font size=\"1\"><a href='statistiques_visites.php3?id_article=$id_article'>"._T('lien_visites', array('visites' => $visites))."</a></font>";
				if ($popularite > 0) $s .= "<br><font size=\"1\"><a href='statistiques_visites.php3?id_article=$id_article'>"._T('lien_popularite', array('popularite' => $popularite))."</a></font>";
			}
			$vals[] = $s;

			$table[] = $vals;
		}
		spip_free_result($result);

		if ($afficher_auteurs) {
			$largeurs = array('', 100, 90);
			$styles = array('arial2', 'arial1', 'arial1');
		}
		else {
			$largeurs = array('', 90);
			$styles = array('arial2', 'arial1');
		}
		afficher_liste($largeurs, $table, $styles);

		echo "</table></td></tr></table>";
		if ($afficher_cadre) fin_cadre_relief();

	}
	return $tous_id;
}


//
// Afficher tableau de breves
//

function afficher_breves($titre_table, $requete, $affrub=false) {
	global $connect_id_auteur, $spip_lang_right, $dir_lang;

	if ((lire_meta('multi_rubriques') == 'oui' AND $GLOBALS['coll'] == 0) OR lire_meta('multi_articles') == 'oui') {
		$afficher_langue = true;
		$requete = ereg_replace(" FROM", ", lang FROM", $requete);
		if ($GLOBALS['langue_rubrique']) $langue_defaut = $GLOBALS['langue_rubrique'];
		else $langue_defaut = lire_meta('langue_site');
	}

	$tranches = afficher_tranches_requete($requete, 2);

	if (strlen($tranches)) {

		debut_cadre_relief("breve-24.gif");

		if ($titre_table) {
			echo "<p><table width=100% cellpadding=0 cellspacing=0 border=0 background=''>";
			echo "<tr><td width=100% background=''>";
			echo "<table width=100% cellpadding=3 cellspacing=0 border=0>";
			echo "<tr bgcolor='#EEEECC'><td width=100% colspan=2><font face='Verdana,Arial,Sans,sans-serif' size=3 color='#000000'>";
			echo "<b>$titre_table</b></font></td></tr>";
		}
		else {
			echo "<p><table width=100% cellpadding=3 cellspacing=0 border=0 background=''>";
		}

		echo $tranches;

		$result = spip_query($requete);

		$table = '';
		while ($row = spip_fetch_array($result)) {
			$vals = '';

			$id_breve = $row['id_breve'];
			$tous_id[] = $id_breve;
			$date_heure = $row['date_heure'];
			$titre = $row['titre'];
			$statut = $row['statut'];
			if ($lang = $row['lang']) changer_typo($lang);
			$id_rubrique = $row['id_rubrique'];
			switch ($statut) {
			case 'prop':
				$puce = "puce-blanche-breve";
				$title = _T('titre_breve_proposee');
				break;
			case 'publie':
				$puce = "puce-verte-breve";
				$title = _T('titre_breve_publiee');
				break;
			case 'refuse':
				$puce = "puce-rouge-breve";
				$title = _T('titre_breve_refusee');
				break;
			}

			$s = "<a href='breves_voir.php3?id_breve=$id_breve' title=\"$title\">";
			$s .= "<img src='img_pack/$puce.gif' alt='' width='8' height='9' border='0'></a>&nbsp;&nbsp;";
			$s .= "<a href='breves_voir.php3?id_breve=$id_breve'$dir_lang>";
			$s .= typo($titre);
			$s .= "</a>";
			if ($afficher_langue AND $lang != $langue_defaut)
				$s .= " <font size='1' color='#666666'$dir_lang>(".traduire_nom_langue($lang).")</font>";

			$vals[] = $s;

			$s = "<div align='$spip_lang_right'>";
			if ($affrub) {
				$rub = spip_fetch_array(spip_query("SELECT titre FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
				$s .= typo($rub['titre']);
			} else if ($statut != "prop")
				$s = affdate_court($date_heure);
			else
				$s .= _T('info_a_valider');
			$s .= "</div>";
			$vals[] = $s;
			$table[] = $vals;
		}
		spip_free_result($result);

		$largeurs = array('', '');
		$styles = array('arial2', 'arial1');
		afficher_liste($largeurs, $table, $styles);

		if ($titre_table) echo "</TABLE></TD></TR>";
		echo "</TABLE>";
		fin_cadre_relief();
	}
	return $tous_id;
}


//
// Afficher tableau de rubriques
//

function afficher_rubriques($titre_table, $requete) {
	global $connect_id_auteur;
	global $spip_lang_rtl;

	$tranches = afficher_tranches_requete($requete, 2);

	if (strlen($tranches)) {

		debut_cadre_relief("rubrique-24.gif");

		if ($titre_table) {
			echo "<p><table width=100% cellpadding=0 cellspacing=0 border=0 background=''>";
			echo "<tr><td width=100% background=''>";
			echo "<table width=100% cellpadding=3 cellspacing=0 border=0>";
			echo "<tr bgcolor='#333333'><td width=100% colspan=2><font face='Verdana,Arial,Sans,sans-serif' size=3 color='#FFFFFF'>";
			echo "<b>$titre_table</b></font></td></tr>";
		}
		else {
			echo "<p><table width=100% cellpadding=3 cellspacing=0 border=0 background=''>";
		}

		echo $tranches;

		$result = spip_query($requete);

		$table = '';
		while ($row = spip_fetch_array($result)) {
			$vals = '';

			$id_rubrique = $row['id_rubrique'];
			$tous_id[] = $id_rubrique;
			$titre = $row['titre'];

			$s = "<b><a href=\"naviguer.php3?coll=$id_rubrique\">";
			$puce = "puce$spip_lang_rtl.gif";
			$s .= "<img src=\"$puce\" alt=\"- \" border=\"0\"> ";
			$s .= typo($titre);
			$s .= "</A></b>";
			$vals[] = $s;

			$s = "<div align=\"right\">";
			$s .= "</div>";
			$vals[] = $s;
			$table[] = $vals;
		}
		spip_free_result($result);

		$largeurs = array('', '');
		$styles = array('arial2', 'arial2');
		afficher_liste($largeurs, $table, $styles);

		if ($titre_table) echo "</TABLE></TD></TR>";
		echo "</TABLE>";
		fin_cadre_relief();
	}
	return $tous_id;
}


//
// Afficher des auteurs sur requete SQL
//
function bonhomme_statut($row) {
	global $connect_statut;

	switch($row['statut']) {
		case "0minirezo":
			$image = "<img src='img_pack/admin-12.gif' alt='' title='"._T('titre_image_administrateur')."' border='0'>";
			break;
		case "1comite":
			if ($connect_statut == '0minirezo' AND ($row['source'] == 'spip' AND !($row['pass'] AND $row['login'])))
				$image = "<img src='img_pack/visit-12.gif' alt='' title='"._T('titre_image_redacteur')."' border='0'>";
			else
				$image = "<img src='img_pack/redac-12.gif' alt='' title='"._T('titre_image_redacteur_02')."' border='0'>";
			break;
		case "5poubelle":
			$image = "<img src='img_pack/poubelle.gif' alt='' title='"._T('titre_image_auteur_supprime')."' border='0'>";
			break;
		case "6forum":
			$image = "<img src='img_pack/visit-12.gif' alt='' title='"._T('titre_image_visiteur')."' border='0'>";
			break;
		case "nouveau":
		default:
			$image = '';
			break;
	}

	return $image;
}

function afficher_auteurs ($titre_table, $requete) {
	$tranches = afficher_tranches_requete($requete, 2);

	if (strlen($tranches)) {

		debut_cadre_relief("redacteurs-24.gif");

		if ($titre_table) {
			echo "<p><table width=100% cellpadding=0 cellspacing=0 border=0 background=''>";
			echo "<tr><td width=100% background=''>";
			echo "<table width=100% cellpadding=3 cellspacing=0 border=0>";
			echo "<tr bgcolor='#333333'><td width=100% colspan=2><font face='Verdana,Arial,Sans,sans-serif' size=3 color='#FFFFFF'>";
			echo "<b>$titre_table</b></font></td></tr>";
		}
		else {
			echo "<p><table width=100% cellpadding=3 cellspacing=0 border=0 background=''>";
		}

		echo $tranches;

		$result = spip_query($requete);

		$table = '';
		while ($row = spip_fetch_array($result)) {
			$vals = '';

			$id_auteur = $row['id_auteur'];
			$tous_id[] = $id_auteur;
			$nom = $row['nom'];

			$s = bonhomme_statut($row);
			$s .= "<a href=\"auteurs_edit.php3?id_auteur=$id_auteur\">";
			$s .= typo($nom);
			$s .= "</a>";
			$vals[] = $s;
			$table[] = $vals;
		}
		spip_free_result($result);

		$largeurs = array('');
		$styles = array('arial2');
		afficher_liste($largeurs, $table, $styles);

		if ($titre_table) echo "</TABLE></TD></TR>";
		echo "</TABLE>";
		fin_cadre_relief();
	}
	return $tous_id;
}

//
// Afficher les forums
//

function afficher_forum($request, $adresse_retour, $controle = "non", $recurrence = "oui") {
	global $debut;
	static $compteur_forum;
	static $nb_forum;
	static $i;
	global $couleur_foncee;
	global $connect_id_auteur, $connect_activer_messagerie;
	global $mots_cles_forums;
	global $spip_lang_rtl;

	$activer_messagerie = lire_meta("activer_messagerie");

	$compteur_forum++;

	$nb_forum[$compteur_forum] = spip_num_rows($request);
	$i[$compteur_forum] = 1;
 	while($row = spip_fetch_array($request)) {
		$id_forum=$row['id_forum'];
		$id_parent=$row['id_parent'];
		$id_rubrique=$row['id_rubrique'];
		$id_article=$row['id_article'];
		$id_breve=$row['id_breve'];
		$id_message=$row['id_message'];
		$id_syndic=$row['id_syndic'];
		$date_heure=$row['date_heure'];
		$titre=$row['titre'];
		$texte=$row['texte'];
		$auteur=$row['auteur'];
		$email_auteur=$row['email_auteur'];
		$nom_site=$row['nom_site'];
		$url_site=$row['url_site'];
		$statut=$row['statut'];
		$ip=$row["ip"];
		$id_auteur=$row["id_auteur"];

		if ($compteur_forum==1){echo "<BR><BR>\n";}

		$afficher = ($controle=="oui") ? ($statut!="perso") :
			(($statut=="prive" OR $statut=="privrac" OR $statut=="privadm" OR $statut=="perso")
			OR ($statut=="publie" AND $id_parent > 0));

		if ($afficher) {
			echo "<table width=100% cellpadding=0 cellspacing=0 border=0><tr>";
			for ($count=2;$count<=$compteur_forum AND $count<20;$count++){
				$fond[$count]='img_pack/rien.gif';
				if ($i[$count]!=$nb_forum[$count]){
					$fond[$count]='img_pack/forum-vert.gif';
				}
				$fleche='img_pack/rien.gif';
				if ($count==$compteur_forum){
					$fleche="img_pack/forum-droite$spip_lang_rtl.gif";
				}
				echo "<td width=10 valign='top' background=$fond[$count]><img src='$fleche' alt='' width=10 height=13 border=0></td>\n";
			}

			echo "\n<td width=100% valign='top'>";

			// Si refuse, cadre rouge
			if ($statut=="off") {
				echo "<table width=100% cellpadding=2 cellspacing=0 border=0><tr><td>";
			}
			// Si propose, cadre jaune
			else if ($statut=="prop") {
				echo "<table width=100% cellpadding=2 cellspacing=0 border=0><tr><td>";
			}

			if ($compteur_forum == 1) echo debut_cadre_relief("forum-interne-24.gif");
			echo "<table width=100% cellpadding=3 cellspacing=0><tr><td bgcolor='$couleur_foncee'><font face='Verdana,Arial,Sans,sans-serif' size=2 color='#FFFFFF'><b>".typo($titre)."</b></font></td></tr>";
			echo "<tr><td bgcolor='#EEEEEE'>";
			echo "<font size=2 face='Georgia,Garamond,Times,serif'>";
			echo "<font face='arial,helvetica'>$date_heure</font>";

			if ($email_auteur) {
				echo " <a href=\"mailto:$email_auteur?subject=".rawurlencode($titre)."\">$auteur</a>";
			}
			else {
				echo " $auteur";
			}

			if ($id_auteur AND $activer_messagerie != "non" AND $connect_activer_messagerie != "non") {
				$bouton = bouton_imessage($id_auteur,$row_auteur);
				if ($bouton) echo "&nbsp;".$bouton;
			}

			if ($controle == "oui") {
				if ($statut != "off") {
					icone (_T('icone_supprimer_message'), "articles_forum.php3?id_article=$id_article&supp_forum=$id_forum&debut=$debut", "forum-interne-24.gif", "supprimer.gif", "right");
				}
				else {
					echo "<br><font color='red'><b>"._T('info_message_supprime')." $ip</b></font>";
					if ($id_auteur) {
						echo " - <a href='auteurs_edit.php3?id_auteur=$id_auteur'>"._T('lien_voir_auteur')."</A>";
					}
				}
				if ($statut == "prop" OR $statut == "off") {
					icone (_T('icone_valider_message'), "articles_forum.php3?id_article=$id_article&valid_forum=$id_forum&debut=$debut", "forum-interne-24.gif", "creer.gif", "right");
				}
			}
			echo justifier(propre($texte));

			if (strlen($url_site) > 10 AND $nom_site) {
				echo "<p align='left'><font face='Verdana,Arial,Sans,sans-serif'><b><a href='$url_site'>$nom_site</a></b></font>";
			}

			if ($controle != "oui") {
				echo "<p align='right'><font face='Verdana,Arial,Sans,sans-serif' size=1>";
				$url = "forum_envoi.php3?id_parent=$id_forum&adresse_retour=".rawurlencode($adresse_retour)
					."&titre_message=".rawurlencode($titre);
				echo "<b><a href=\"$url\">"._T('lien_repondre_message')."</a></b></font>";
			}

			if ($mots_cles_forums == "oui"){

				$query_mots = "SELECT * FROM spip_mots AS mots, spip_mots_forum AS lien WHERE lien.id_forum = '$id_forum' AND lien.id_mot = mots.id_mot";
				$result_mots = spip_query($query_mots);

				while ($row_mots = spip_fetch_array($result_mots)) {
					$id_mot = $row_mots['id_mot'];
					$titre_mot = propre($row_mots['titre']);
					$type_mot = propre($row_mots['type']);
					echo "<li> <b>$type_mot :</b> $titre_mot";
				}

			}

			echo "</font>";
			echo "</td></tr></table>";
			if ($compteur_forum == 1) echo fin_cadre_relief();
			if ($statut == "off" OR $statut == "prop") {
				echo "</td></tr></table>";
			}
			echo "</td></tr></table>\n";

			if ($recurrence == "oui") forum($id_forum,$adresse_retour,$controle);
		}
		$i[$compteur_forum]++;
	}
	spip_free_result($request);
	$compteur_forum--;
}

function forum($le_forum, $adresse_retour, $controle = "non") {
	global $id_breve;
      	echo "<font size=2 face='Georgia,Garamond,Times,serif'>";
	
	if ($controle == "oui") {
		$query_forum2 = "SELECT * FROM spip_forum WHERE id_parent='$le_forum' ORDER BY date_heure";
	}
	else {
		$query_forum2 = "SELECT * FROM spip_forum WHERE id_parent='$le_forum' AND statut<>'off' ORDER BY date_heure";
	}
 	$result_forum2 = spip_query($query_forum2);
	afficher_forum($result_forum2, $adresse_retour, $controle);
}

//
// un bouton (en POST) a partir d'un URL en format GET
//
function bouton($titre,$lien) {
	$lapage=substr($lien,0,strpos($lien,"?"));
	$lesvars=substr($lien,strpos($lien,"?")+1,strlen($lien));

	echo "\n<form action='$lapage' method='get'>\n";
	$lesvars=explode("&",$lesvars);
	
	for($i=0;$i<count($lesvars);$i++){
		$var_loc=explode("=",$lesvars[$i]);
		echo "<input type='Hidden' name='$var_loc[0]' value=\"$var_loc[1]\">\n";
	}
	echo "<input type='submit' name='Submit' class='fondo' value=\"$titre\">\n";
	echo "</form>";
}


//
// Presentation de l'interface privee, debut du HTML
//

function debut_html($titre = "") {
	global $couleur_foncee, $couleur_claire, $couleur_lien, $couleur_lien_off;
	global $flag_ecrire;
	global $spip_lang_rtl;

	$nom_site_spip = entites_html(lire_meta("nom_site"));
	$titre = textebrut(typo($titre));

	if (!$nom_site_spip) $nom_site_spip="SPIP";
	if (!$charset = lire_meta('charset')) $charset = 'utf-8';

	@Header("Expires: 0");
	@Header("Cache-Control: no-cache,no-store");
	@Header("Pragma: no-cache");
	@Header("Content-Type: text/html; charset=$charset");

	echo "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN' 'http://www.w3.org/TR/html4/loose.dtd'>\n<head>\n<html>\n<title>[$nom_site_spip] $titre</title>\n";
	echo '<meta http-equiv="Content-Type" content="text/html; charset='.$charset.'">';
	echo '<link rel="stylesheet" type="text/css" href="';
	if (!$flag_ecrire) echo 'ecrire/';
	$link = new Link('spip_style.php3');
	$link->addVar('couleur_claire', $couleur_claire);
	$link->addVar('couleur_foncee', $couleur_foncee);
	$link->addVar('left', $GLOBALS['spip_lang_left']);
	$link->addVar('right', $GLOBALS['spip_lang_right']);
	echo $link->getUrl()."\">\n";

	afficher_script_layer();
?>
<script type='text/javascript'><!--
function changeclass(objet, myClass)
{
		objet.className = myClass;
}
function changesurvol(iddiv, myClass)
{
		document.getElementById(iddiv).className = myClass;
}
//--></script>
</head>
<?php
	echo "<body text='#000000' bgcolor='#e4e4e4' background='img_pack/degrade.jpg' link='$couleur_lien' vlink='$couleur_lien_off' alink='$couleur_lien_off' topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'";
	if ($spip_lang_rtl)
		echo " dir='rtl'";
	echo ">";
}

// Fonctions onglets

function onglet_relief_inter(){
	global $spip_display;
	if ($spip_display != 1) {
		echo "<td background='img_pack/barre-noir.gif'><img src='img_pack/rien.gif' alt='' width='1' height='40'></td>";
	}
}

function debut_onglet(){
	global $spip_display;
	if ($spip_display == 1) {
		echo "\n";
		echo "<p><table cellpadding=0 cellspacing=3 border=0>";
		echo "<tr>";
	}
	else {
		echo "\n";
		echo "<p><table cellpadding=0 cellspacing=0 border=0>";
		echo "<tr><td>";
		if ($GLOBALS['spip_lang_rtl'])
			echo "<img src='img_pack/barre-d.gif' alt='' width='16' height='40'>";
		else
			echo "<img src='img_pack/barre-g.gif' alt='' width='16' height='40'>";
		echo "</td>";
	}
}

function fin_onglet(){
	global $spip_display;
	onglet_relief_inter();
	if ($spip_display == 1) {
		echo "</tr>";
		echo "</table>";
	} else {
		echo "<td>";
		if ($GLOBALS['spip_lang_rtl'])
			echo "<img src='img_pack/barre-g.gif' alt='' width='16' height='40'>";
		else
			echo "<img src='img_pack/barre-d.gif' alt='' width='16' height='40'>";
		echo "</td></tr>";
		echo "</table>";
	}
}

function onglet($texte, $lien, $onglet_ref, $onglet, $icone=""){
	global $spip_display ;
	if ($spip_display == 1) {
		if ($onglet_ref == $onglet){
			echo "\n<td  class='iconeon' valign='middle'>";
			echo "<font face='Verdana,Arial,Sans,sans-serif' size='2' color='black'><b>$texte</b></font>";
			echo "</td>";
		}
		else {
			echo "\n<td class='iconeoff' onMouseOver=\"changeclass(this,'iconeon');\" onMouseOut=\"changeclass(this,'iconeoff');\" onClick=\"document.location='$lien'\" valign='middle'>";
			echo "<a href='$lien' class='icone'><font face='Verdana,Arial,Sans,sans-serif' size='2' color='#666666'><b>$texte</b></font></a>";
			echo "</td>";
		}
	}
	else {
		if ($onglet_ref == $onglet){
			onglet_relief_inter();
			if (strlen($icone)>3){
				echo "\n<td background='img_pack/barre-noir.gif' height=40 valign='top'>";
				echo "&nbsp; <img src='img_pack/$icone' border=0>";
				echo "</td>";
			}
			echo "\n<td background='img_pack/barre-noir.gif' height=40 valign='middle'>";
			echo "&nbsp; <font face='Verdana,Arial,Sans,sans-serif' size='2' color='black'><b>$texte</b></font> &nbsp;";
			echo "</td>";
		}
		else {
			onglet_relief_inter();
			echo "\n<td class='reliefblanc' onMouseOver=\"changeclass(this,'reliefgris');\" onMouseOut=\"changeclass(this,'reliefblanc');\" height='40' valign='middle'>\n";
			echo "<table border='0' cellspacing='0' cellpadding='0'><tr>\n";

			if (strlen($icone)>3){
				echo "\n<td height=40 valign='middle'>";
				echo "&nbsp; <a href='$lien' class='icone'><img src='img_pack/$icone' border=0></a>";
				echo "</td>";
			}
			echo "\n<td height=40 valign='middle'>";
			echo "<a href='$lien' class='icone'>&nbsp; <font face='Verdana,Arial,Sans,sans-serif' size='2' color='#666666'><b>$texte</b></font></a> &nbsp;";
			echo "</td>";

			echo "\n</tr></table>";
			echo "\n</td>\n";
		}
	}
}


function barre_onglets($rubrique, $onglet){
	global $id_auteur, $connect_id_auteur, $connect_statut, $statut_auteur, $options;

	debut_onglet();

	if ($rubrique == "statistiques") {
		onglet(_T('onglet_evolution_visite_mod'), "statistiques_visites.php3", "evolution", $onglet, "statistiques-24.gif");
		$activer_statistiques_ref = lire_meta("activer_statistiques_ref");
		if ($activer_statistiques_ref != "non")
			onglet(_T('titre_liens_entrants'), "statistiques_referers.php3", "referers", $onglet, "referers-24.gif");
	}
	if ($rubrique == "repartition") {
		onglet(_T('onglet_repartition_rubrique'), "statistiques.php3", "rubriques", $onglet, "rubrique-24.gif");
		if (lire_meta('multi_articles') == 'oui' OR lire_meta('multi_rubriques') == 'oui')
			onglet(_T('onglet_repartition_lang'), "statistiques_lang.php3", "langues", $onglet, "langues-24.gif");
	}

	if ($rubrique == "traductions") {
		onglet(_T('onglet_detail_traductions'), "plan_trad.php3", "detail", $onglet, "langues-24.gif");
		onglet(_T('onglet_bilan_traductions'), "statistiques_trad.php3", "bilan", $onglet, "statistiques-24.gif");
	}

	if ($rubrique == "administration"){
		onglet(_T('onglet_save_restaur_base'), "admin_tech.php3", "sauver", $onglet, "base-24.gif");
//		onglet(_T('onglet_vider_cache'), "admin_vider.php3", "vider", $onglet, "cache-24.gif");
		onglet(_T('onglet_affacer_base'), "admin_effacer.php3", "effacer", $onglet, "supprimer.gif");
	}

	if ($rubrique == "auteur"){
		$activer_messagerie = lire_meta("activer_messagerie");
		$activer_imessage = lire_meta("activer_imessage");

		onglet(_T('onglet_auteur'), "auteurs_edit.php3?id_auteur=$id_auteur", "auteur", $onglet, "redacteurs-24.gif");
		onglet(_T('onglet_informations_personnelles'), "auteur_infos.php3?id_auteur=$id_auteur", "infos", $onglet, "fiche-perso-24.gif");
		if ($activer_messagerie!="non" AND $connect_id_auteur == $id_auteur)
			onglet(_T('onglet_messagerie'), "auteur_messagerie.php3?id_auteur=$id_auteur", "messagerie", $onglet, "messagerie-24.gif");
	}

	if ($rubrique == "configuration"){
		onglet(_T('onglet_contenu_site'), "configuration.php3", "contenu", $onglet, "racine-site-24.gif");
		onglet(_T('onglet_interactivite'), "config-contenu.php3", "interactivite", $onglet, "forum-interne-24.gif");
		onglet(_T('onglet_fonctions_avances'), "config-fonctions.php3", "fonctions", $onglet, "image-24.gif");
		//onglet(_T('onglet_langues'), "config-lang.php3", "langues", $onglet, "langues-24.gif");
	}

	if ($rubrique == "suivi_forum"){
		onglet(_T('onglet_messages_publics'), "controle_forum.php3?page=public", "public", $onglet, "racine-site-24.gif");
		onglet(_T('onglet_messages_internes'), "controle_forum.php3?page=interne", "interne", $onglet, "forum-interne-24.gif");

		$query_forum = "SELECT * FROM spip_forum WHERE statut='publie' AND texte='' LIMIT 0,1";
		$result_forum = spip_query($query_forum);
		if ($row = spip_fetch_array($result_forum)) {
			onglet(_T('onglet_messages_vide'), "controle_forum.php3?page=vide", "sans", $onglet);
		}
	}

	fin_onglet();
}


function icone_bandeau_principal($texte, $lien, $fond, $rubrique_icone = "vide", $rubrique = "", $lien_noscript = ""){
	global $spip_display, $spip_ecran ;
	global $menu_accesskey, $compteur_survol;

	if ($spip_display == 1){
		//$hauteur = 20;
		$largeur = 80;
	}
	else if ($spip_display == 3){
		//$hauteur = 50;
		$largeur = 60;
		$title = " title=\"$texte\"";
		$alt = " alt=\"$texte\"";
	}
	else {
		//$hauteur = 80;
		if (count(explode(" ", $texte)) > 1) $largeur = 84;
		else $largeur = 80;
		$alt = " alt=\" \"";
	}

	if (!$menu_accesskey) $menu_accesskey = 1;
	if ($menu_accesskey < 10) {
		$accesskey = " accesskey='$menu_accesskey'";
		$menu_accesskey++;
	}
	else if ($menu_accesskey == 10) {
		$accesskey = " accesskey='0'";
		$menu_accesskey++;
	}

	if ($rubrique_icone == $rubrique) $class_select = " class='selection'";

	if (eregi("^javascript:",$lien)) {
		$a_href = "<a$accesskey onClick=\"$lien; return false;\" href='$lien_noscript' target='spip_aide'$class_select>";
	}
	else {
		$a_href = "<a$accesskey href=\"$lien\"$class_select>";
	}

	$compteur_survol ++;

	if ($spip_display != 1) {
		echo "<td class='cellule48' width='$largeur'>$a_href<img src='img_pack/$fond'$alt$title>";
		if ($spip_display != 3) {
			echo "<span>$texte</span>";
		}
	}
	else echo "<td class='cellule-texte' width='$largeur'>$a_href".$texte;
	echo "</a></td>\n";
}




function icone_bandeau_secondaire($texte, $lien, $fond, $rubrique_icone = "vide", $rubrique, $aide=""){
	global $spip_display;
	global $menu_accesskey, $compteur_survol;

	if ($spip_display == 1) {
		//$hauteur = 20;
		$largeur = 80;
	}
	else if ($spip_display == 3){
		//$hauteur = 26;
		$largeur = 40;
		$title = " title=\"$texte\"";
		$alt = " alt=\"$texte\"";
	}
	else {
		//$hauteur = 68;
		if (count(explode(" ", $texte)) > 1) $largeur = 80;
		else $largeur = 70;
		$alt = " alt=\" \"";
	}
	if ($aide AND $spip_display != 3) {
		$largeur += 50;
		//$texte .= aide($aide);
	}
	if ($spip_display != 3 AND strlen($texte)>16) $largeur += 20;
	
	if ($largeur) $width = "width='$largeur'";

	if (!$menu_accesskey) $menu_accesskey = 1;
	if ($menu_accesskey < 10) {
		$accesskey = " accesskey='$menu_accesskey'";
		$menu_accesskey++;
	}
	else if ($menu_accesskey == 10) {
		$accesskey = " accesskey='0'";
		$menu_accesskey++;
	}
	if ($spip_display == 3) $accesskey_icone = $accesskey;

	if ($rubrique_icone == $rubrique) $class_select = " class='selection'";
	$compteur_survol ++;

	$a_href = "<a$accesskey href=\"$lien\"$class_select>";

	if ($spip_display != 1) {
		echo "<td class='cellule36' width='$largeur'>$a_href<img src='img_pack/$fond'$alt$title>";
		if ($aide AND $spip_display != 3) echo aide($aide)." ";
		if ($spip_display != 3) {
			echo "<span>$texte</span>";
		}
	}
	else echo "<td class='cellule-texte' width='$largeur'>$a_href".$texte;
	echo "</a>";	
	echo "</td>\n";
}



function icone($texte, $lien, $fond, $fonction="", $align="", $afficher='oui'){
	global $spip_display, $couleur_claire, $couleur_foncee, $compteur_survol;

	if (strlen($fonction) < 3) $fonction = "rien.gif";
	if (strlen($align) > 2) $aligner = " ALIGN='$align' ";

	if ($spip_display == 1){
		$hauteur = 20;
		$largeur = 80;
		$alt = " alt=\"\"";
	}
	else if ($spip_display == 3){
		$hauteur = 30;
		$largeur = 30;
		$title = " title=\"$texte\"";
		$alt = " alt=\"$texte\"";
	}
	else {
		$hauteur = 70;
		$largeur = 80;
	}

	if ($fonction == "supprimer.gif") {
		$style = '-danger';
	} else {
		$style = '';
	}

	$compteur_survol ++;
	$icone .= "\n<table cellpadding='0' class='pointeur' cellspacing='0' border='0' $aligner width='$largeur'>";
		$icone .= "<tr><td class='icone36$style' style='text-align:center;'><a href='$lien'>";
	if ($spip_display != 1){
		if ($fonction != "rien.gif"){
			$icone .= "<img src='img_pack/$fonction'$alt$title style='background: url(img_pack/$fond) no-repeat center center;' width='24' height='24' border='0'>";
		}
		else {
			$icone .= "<img src='img_pack/$fond'$alt$title width='24' height='24' border='0'>";
		}
	}
	if ($spip_display != 3){
		$icone .= "<span>$texte</span>";
	}
	$icone .= "</a></td></tr>";
	$icone .= "</table>";

	if ($afficher == 'oui')
		echo $icone;
	else
		return $icone;
}

function icone_horizontale($texte, $lien, $fond = "", $fonction = "") {
	global $spip_display, $couleur_claire, $couleur_foncee, $compteur_survol;

	if (!$fonction) $fonction = "rien.gif";
	$danger = ($fonction == "supprimer.gif");

	if ($danger) echo "<div class='danger'>";
	if ($spip_display != 1) {
		echo "<a href='$lien' class='cellule-h'><table cellpadding='0' valign='middle'><tr>\n";
		echo "<td><a href='$lien'><div class='cell-i'><img style='background: url(\"img_pack/$fond\"); background-repeat: no-repeat; background-position: center center;' src='img_pack/$fonction' alt=''></div></a></td>\n";
		echo "<td class='cellule-h-lien'><a href='$lien' class='cellule-h'>$texte</a></td>\n";
		echo "</tr></table></a>\n";
	}
	else {
		echo "<a href='$lien' class='cellule-h-texte'><div>$texte</div></a>\n";
	}
	if ($danger) echo "</div>";
}


function bandeau_barre_verticale(){
	echo "<td class='separateur'></td>\n";
}


// lien changement de couleur
function lien_change_var($lien, $set, $couleur, $coords, $titre) {
	$lien->addVar($set, $couleur);
	return "\n<area shape='rect' href='". $lien->getUrl() ."' coords='$coords' title=\"$titre\">";
}

//
// Debut du corps de la page
//

function debut_page($titre = "", $rubrique = "asuivre", $sous_rubrique = "asuivre") {
	global $couleur_foncee;
	global $couleur_claire;
	global $adresse_site;
	global $connect_id_auteur;
	global $connect_statut;
	global $connect_activer_messagerie;
	global $connect_toutes_rubriques;
	global $auth_can_disconnect, $connect_login;
	global $options, $spip_display, $spip_ecran;
	global $spip_lang_rtl;
	$activer_messagerie = lire_meta("activer_messagerie");
	global $clean_link;

	if ($spip_ecran == "large") $largeur = 974;
	else $largeur = 750;

	// nettoyer le lien global
	$clean_link->delVar('var_lang');
	$clean_link->delVar('set_options');
	$clean_link->delVar('set_couleur');
	$clean_link->delVar('set_disp');
	$clean_link->delVar('set_ecran');

	if (strlen($adresse_site)<10) $adresse_site="../";

	debut_html($titre);

	$ctitre = _T('titre_changer_couleur_interface');
	echo "\n<map name='map_couleur'>";
	echo lien_change_var ($clean_link, 'set_couleur', 6, '0,0,10,10', $ctitre);
	echo lien_change_var ($clean_link, 'set_couleur', 1, '12,0,22,10', $ctitre);
	echo lien_change_var ($clean_link, 'set_couleur', 2, '24,0,34,10', $ctitre);
	echo lien_change_var ($clean_link, 'set_couleur', 3, '36,0,46,10', $ctitre);
	echo lien_change_var ($clean_link, 'set_couleur', 4, '48,0,58,10', $ctitre);
	echo lien_change_var ($clean_link, 'set_couleur', 5, '60,0,70,10', $ctitre);
	echo lien_change_var ($clean_link, 'set_couleur', 7, '0,11,10,21', $ctitre);
	echo lien_change_var ($clean_link, 'set_couleur', 8, '12,11,22,21', $ctitre);
	echo lien_change_var ($clean_link, 'set_couleur', 9, '24,11,34,21', $ctitre);
	echo lien_change_var ($clean_link, 'set_couleur', 10, '36,11,46,21', $ctitre);
	echo lien_change_var ($clean_link, 'set_couleur', 11, '48,11,58,21', $ctitre);
	echo lien_change_var ($clean_link, 'set_couleur', 12, '60,11,70,21', $ctitre);
	echo "\n</map>";

	echo "\n<map name='map_layout'>";
	echo lien_change_var ($clean_link, 'set_disp', 1, '1,0,18,15', _T('lien_afficher_texte_seul'));
	echo lien_change_var ($clean_link, 'set_disp', 2, '19,0,40,15', _T('lien_afficher_texte_icones'));
	echo lien_change_var ($clean_link, 'set_disp', 3, '41,0,59,15', _T('lien_afficher_icones_seuls'));
	echo "\n</map>";

	// Icones principales

	echo "<div class='bandeau-principal' align='center'>\n";
	echo "<div class='bandeau-icones' style='width: ".$largeur."px'>\n";
	echo "<table class='gauche'><tr>\n";

	icone_bandeau_principal (_T('icone_a_suivre'), "index.php3", "asuivre-48.gif", "asuivre", $rubrique);
	icone_bandeau_principal (_T('icone_edition_site'), "naviguer.php3", "documents-48$spip_lang_rtl.gif", "documents", $rubrique);
	icone_bandeau_principal (_T('icone_discussions'), "forum.php3", "messagerie-48.gif", "redacteurs", $rubrique);
	if ($connect_statut == "0minirezo") {
		bandeau_barre_verticale();
		if ($connect_toutes_rubriques) 
			icone_bandeau_principal (_T('icone_suivi_actualite'), "controle_forum.php3", "suivi-48.gif", "suivi", $rubrique);
		else if (lire_meta("activer_statistiques") != 'non') 
			icone_bandeau_principal (_T('icone_statistiques'), "statistiques_visites.php3", "statistiques-48.gif", "suivi", $rubrique);
	}
	if ($connect_statut == '0minirezo' and $connect_toutes_rubriques) {
		icone_bandeau_principal (_T('icone_admin_site'), "configuration.php3", "administration-48.gif", "administration", $rubrique);
	}

	echo "</tr></table>\n";
	echo "<table class='droite'><tr>\n";

		icone_bandeau_principal (_T('icone_aide_ligne'), "javascript:window.open('aide_index.php3', 'aide_spip', 'scrollbars=yes,resizable=yes,width=740,height=580');", "aide-48$spip_lang_rtl.gif", "vide", "", "aide_index.php3");
		icone_bandeau_principal (_T('icone_visiter_site'), "$adresse_site", "visiter-48$spip_lang_rtl.gif");

	echo "</tr></table>\n";

	// Merci le W3C pour l'alignement vertical / Thank you W3C idiots for vertical alignment
	if ($spip_display == 1) $h = 8;
	else if ($spip_display == 3) $h = 20;
	else $h = 34;
	echo "<div class='milieu' style='margin-top: ".$h."px'>";
	echo "<img src='img_pack/choix-layout$spip_lang_rtl.gif' alt='abc' vspace=3 border=0 usemap='#map_layout'>";
	echo "</div>\n";

	echo "<div class='fin'></div>\n";

	echo "</div>\n";
	echo "</div>\n";


	// Icones secondaires
	$activer_messagerie = lire_meta("activer_messagerie");
	$connect_activer_messagerie = $GLOBALS["connect_activer_messagerie"];

	echo "<div class='bandeau-secondaire' align='center'>\n";
	echo "<div class='bandeau-icones' style='width: ".$largeur."px'>\n";
	echo "<table class='gauche'><tr>\n";

	if ($rubrique == "asuivre"){
		icone_bandeau_secondaire (_T('icone_a_suivre'), "index.php3", "asuivre-24.gif", "asuivre", $sous_rubrique);
		icone_bandeau_secondaire (_T('icone_informations_personnelles'), "auteurs_edit.php3?id_auteur=$connect_id_auteur", "fiche-perso-24.gif", "perso", $sous_rubrique);
		icone_bandeau_secondaire (_T('icone_site_entier'), "articles_tous.php3", "tout-site-24.gif", "tout-site", $sous_rubrique);
		if ((lire_meta('multi_rubriques') == 'oui' OR lire_meta('multi_articles') == 'oui') AND lire_meta('gerer_trad') == 'oui' AND $options == 'avancees') {
			icone_bandeau_secondaire (_T('icone_etat_traductions'), "plan_trad.php3", "langues-24.gif", "plan-trad", $sous_rubrique);
		}
	}
	else if ($rubrique == "documents"){
		icone_bandeau_secondaire (_T('icone_rubriques'), "naviguer.php3", "rubrique-24.gif", "rubriques", $sous_rubrique);

		$nombre_articles = spip_num_rows(spip_query("SELECT art.id_article FROM spip_articles AS art, spip_auteurs_articles AS lien WHERE lien.id_auteur = '$connect_id_auteur' AND art.id_article = lien.id_article LIMIT 0,1"));
		if ($nombre_articles > 0) {
			icone_bandeau_secondaire (_T('icone_articles'), "articles_page.php3", "article-24.gif", "articles", $sous_rubrique);
		}

		$activer_breves=lire_meta("activer_breves");
		if ($activer_breves != "non"){
			icone_bandeau_secondaire (_T('icone_breves'), "breves.php3", "breve-24.gif", "breves", $sous_rubrique);
		}

		if ($options == "avancees"){
			$articles_mots = lire_meta('articles_mots');
			if ($articles_mots != "non") {
				icone_bandeau_secondaire (_T('icone_mots_cles'), "mots_tous.php3", "mot-cle-24.gif", "mots", $sous_rubrique);
			}

			$activer_sites = lire_meta('activer_sites');
			if ($activer_sites<>'non')
				icone_bandeau_secondaire (_T('icone_sites_references'), "sites_tous.php3", "site-24.gif", "sites", $sous_rubrique);

			if (@spip_num_rows(spip_query("SELECT * FROM spip_documents_rubriques LIMIT 0,1")) > 0) {
				icone_bandeau_secondaire (_T('icone_doc_rubrique'), "documents_liste.php3", "doc-24.gif", "documents", $sous_rubrique);
			}
		}
	}
	else if ($rubrique == "redacteurs") {
		icone_bandeau_secondaire (_T('titre_forum'), "forum.php3", "forum-interne-24.gif", "forum-interne", $sous_rubrique);
		if ($connect_statut == "0minirezo" AND lire_meta('forum_prive_admin') == 'oui')
			icone_bandeau_secondaire (_T('icone_forum_administrateur'), "forum_admin.php3", "forum-admin-24.gif", "forum-admin", $sous_rubrique);
		bandeau_barre_verticale();
		icone_bandeau_secondaire (_T('icone_tous_auteur'), "auteurs.php3", "redacteurs-24.gif", "redacteurs", $sous_rubrique);
		
		if ($activer_messagerie == "oui" AND $connect_activer_messagerie != "non") {
			bandeau_barre_verticale();
			icone_bandeau_secondaire (_T('icone_agenda'), "calendrier_jour.php3", "agenda-24.gif", "calendrier", $sous_rubrique);
			icone_bandeau_secondaire (_T('icone_messagerie_personnelle'), "messagerie.php3", "messagerie-24.gif", "messagerie", $sous_rubrique);
		}
	}
	else if ($rubrique == "suivi") {
		if ($connect_toutes_rubriques) {
			icone_bandeau_secondaire (_T('icone_suivi_forums'), "controle_forum.php3", "suivi-forum-24.gif", "forum-controle", $sous_rubrique);
			icone_bandeau_secondaire (_T('icone_suivi_pettions'), "controle_petition.php3", "petition-24.gif", "suivi-petition", $sous_rubrique);
		}
		if (lire_meta("activer_statistiques") != 'non') {
			if ($connect_toutes_rubriques) bandeau_barre_verticale();
			icone_bandeau_secondaire (_T('icone_statistiques_visites'), "statistiques_visites.php3", "statistiques-24.gif", "statistiques", $sous_rubrique);
			icone_bandeau_secondaire (_T('icone_repartition_visites'), "statistiques.php3", "rubrique-24.gif", "repartition", $sous_rubrique);
		}
	}
	else if ($rubrique == "administration") {
		icone_bandeau_secondaire (_T('icone_configuration_site'), "configuration.php3", "administration-24.gif", "configuration", $sous_rubrique);
		icone_bandeau_secondaire (_T('icone_gestion_langues'), "config-lang.php3", "langues-24.gif", "langues", $sous_rubrique);
		bandeau_barre_verticale();
		if ($options == "avancees") {
			icone_bandeau_secondaire (_T('icone_maintenance_site'), "admin_tech.php3", "base-24.gif", "base", $sous_rubrique);
			icone_bandeau_secondaire (_T('onglet_vider_cache'), "admin_vider.php3", "cache-24.gif", "cache", $sous_rubrique);
		}
		else {
			icone_bandeau_secondaire (_T('icone_sauver_site'), "admin_tech.php3", "base-24.gif", "base", $sous_rubrique);
		}
	}

	if ($options == "avancees") {
		global $recherche;
		if ($recherche == '' AND $spip_display != 2)
			$recherche_aff = _T('info_rechercher');
		else
			$recherche_aff = $recherche;
		bandeau_barre_verticale();
		echo "<td>";
		echo "<form method='get' style='margin: 0px;' action='recherche.php3'>";
		if ($spip_display == "2")
			echo "<font face='Verdana,Arial,Sans,sans-serif' size=1 color='#505050'><b>"._T('info_rechercher_02')."</b></font><br>";
		echo '<input type="text" size="10" value="'.$recherche_aff.'" name="recherche" class="spip_recherche" style="width: 70px" accesskey="r">';
		echo "</form>";
		echo "</td>";
	}

	echo "</tr></table>\n";

	if ($auth_can_disconnect) {
		echo "<table class='droite'><tr>\n";
		icone_bandeau_secondaire (_T('icone_deconnecter'), "../spip_cookie.php3?logout=$connect_login", "deconnecter-24$spip_lang_rtl.gif", "", $sous_rubrique, "deconnect");
		echo "</tr></table>\n";
	}

	echo "<div class='fin'></div>\n";

	echo "</div>\n";
	echo "</div>\n";


	// Bandeau
	echo "\n<table cellpadding='0' bgcolor='$couleur_foncee' style='border-bottom: solid 1px white; border-top: solid 1px #666666;' width='100%'><tr width='100%'><td width='100%'>";
	echo "<table align='center' cellpadding='0' background='' width='$largeur'><tr width='$largeur'><td>";

		global $id_rubrique;
		if ($id_rubrique > 0) echo "<a href='brouteur.php3?id_rubrique=$id_rubrique'><img src='img_pack/naviguer-site.gif' alt='nav' width='26' height='20' border='0'></a>";
		else echo "<a href='brouteur.php3'><img src='img_pack/naviguer-site.gif' alt='nav' width='26' height='20' border='0'></a>";
		
		if ($activer_messagerie != 'non' AND $connect_activer_messagerie != 'non') {
			echo " &nbsp; <font face='arial,helvetica,sans-serif' size=1><b>";
			$result_messages = spip_query("SELECT * FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE lien.id_auteur=$connect_id_auteur AND vu='non' AND statut='publie' AND type='normal' AND lien.id_message=messages.id_message");
			$total_messages = @spip_num_rows($result_messages);
			if ($total_messages == 1) {
				while($row = @spip_fetch_array($result_messages)) {
					$ze_message=$row['id_message'];
					echo "<a href='message.php3?id_message=$ze_message'><font color='$couleur_claire'><b>"._T('info_nouveau_message')."</b></font></a>";
				}
			}
			if ($total_messages > 1) echo "<a href='messagerie.php3'><font color='$couleur_claire'>"._T('info_nouveaux_messages', array('total_messages' => $total_messages))."</font></a>";
			echo "</b></font> &nbsp; ";
		}

	if ($activer_messagerie == "oui" AND $connect_activer_messagerie != "non") echo "<a href='calendrier.php3' title='"._T('icone_agenda')."'><img src='img_pack/cal-mois.gif' alt='jour' width='26' height='20' border='0'></a>";
	echo "</td>";
	echo "<td>   </td>";
	echo "<td>";
	echo "<font size=1 face='Verdana,Arial,Sans,sans-serif'>";
		if ($options == "avancees") {
			$lien = $clean_link;
			$lien->addVar('set_options', 'basiques');
			echo "<span class='fondgris'
				onMouseOver=\"changeclass(this,'fondgrison2')\"
				onMouseOut=\"changeclass(this,'fondgris')\"><a
				href='". $lien->getUrl() ."' class='icone'><font color='black'>"._T('icone_interface_simple')."</font></a></span>";
			echo " <span class = 'fondo'><b>"._T('info_interface_complete')."</b></span>";

		}
		else {
			$lien = $clean_link;
			$lien->addVar('set_options', 'avancees');
			echo "<span class='fondgrison2'><b>"._T('info_interface_simple')."</b></span> <span class='fondgris'
				onMouseOver=\"changeclass(this,'fondgrison2')\"
				onMouseOut=\"changeclass(this,'fondgris')\"><a
				href='". $lien->getUrl() ."' class='icone'><font color='black'>"._T('icone_interface_complet')."</font></a></span>";
		}

	echo "</font>";
	echo "</td>";

	// grand ecran
	echo "<td style:'text-align:center;'>";
	$lien = $clean_link;

	if ($spip_ecran == "large") {
		$lien->addVar('set_ecran', 'etroit');
		echo "<a href='". $lien->getUrl() ."'><img src='img_pack/set-ecran.gif' title='"._T('info_petit_ecran')."' alt='"._T('info_petit_ecran')."' width='23' height='19' border='0'></a>";
	}
	else {
		$lien->addVar('set_ecran', 'large');
		echo "<a href='". $lien->getUrl() ."'><img src='img_pack/set-ecran.gif' title='"._T('info_grand_ecran')."' alt='"._T('info_grand_ecran')."' width='23' height='19' border='0'></a>";
	}
	echo "</td>";

	//
	// choix de la langue
	//
	if ($GLOBALS['all_langs']) {
		echo "<td>   </td>";

		echo "<td align='center'>";
		echo menu_langues();
		echo "</td>";

	}

	// choix de la couleur
	echo "<td style='text-align:center;'>";
	echo "<img src='img_pack/barre-couleurs.gif' alt=\"".entites_html(_T('titre_changer_couleur_interface'))."\" width='70' height='21' border='0' usemap='#map_couleur'>";
	echo "</td>";
	echo "</tr></table>";
	echo "</td></tr></table>";

	echo "<center>";
}


function gros_titre($titre, $ze_logo=''){
	global $couleur_foncee;
	
	echo "<div>";
	if (strlen($ze_logo) > 3) echo "<img src='img_pack/$ze_logo' alt='' border=0 align='middle'> &nbsp; ";
	echo "<span style='border-bottom: 1px dashed $couleur_foncee;'><font size=5 face='Verdana,Arial,Sans,sans-serif' color='$couleur_foncee' ".$GLOBALS['dir_lang']."><b>";
	echo typo($titre);
	echo "</b></font></span></div>\n";
}


//
// Cadre centre (haut de page)
//

function debut_grand_cadre(){
	global $spip_ecran;
	
	if ($spip_ecran == "large") $largeur = 974;
	else $largeur = 750;
	echo "\n<br><br><table width=$largeur cellpadding=0 cellspacing=0 border=0>";
	echo "\n<tr>";
	echo "<td width=$largeur>";
	echo "<font face='Georgia,Garamond,Times,serif' size=3>";

}

function fin_grand_cadre(){
	echo "\n</font></td></tr></table>";
}

// Cadre formulaires

function debut_cadre_formulaire(){
	echo "\n<div style='width: 100%; border-top: 1px solid #aaaaaa; border-left: 1px solid #aaaaaa; border-right: 1px solid white; border-bottom: 1px solid white; margin: 0px; padding: 0px;'>";
	echo "\n<div style='border: 1px dashed #666666; margin: 0px; padding: 10px; background-color:#e4e4e4;'>";
}

function fin_cadre_formulaire(){
	echo "</div>";
	echo "</div>\n";
}



//
// Debut de la colonne de gauche
//

function debut_gauche($rubrique = "asuivre") {
	global $connect_statut, $cookie_admin;
	global $options;
	global $connect_id_auteur;
	global $spip_ecran;
	global $flag_3_colonnes, $flag_centre_large;
	global $spip_lang_rtl;

	$flag_3_colonnes = false;
	$largeur = 200;

	// Ecran panoramique ?
	if ($spip_ecran == "large") {
		$largeur_ecran = 974;
		
		// Si edition de texte, formulaires larges
		if (ereg('((articles|breves|rubriques)_edit|forum_envoi)\.php3', $GLOBALS['REQUEST_URI'])) {
			$flag_centre_large = true;
		}
		
		$flag_3_colonnes = true;
		$rspan = " rowspan=2";

	}
	else {
		$largeur_ecran = 750;
	}

	echo "<br><table width='$largeur_ecran' cellpadding=0 cellspacing=0 border=0>
		<tr><td width='$largeur' valign='top' $rspan><font face='Georgia,Garamond,Times,serif' size=2>\n";
	

	// Afficher les auteurs recemment connectes
	
	global $changer_config;
	global $activer_messagerie;
	global $activer_imessage;
	global $connect_activer_messagerie;
	global $connect_activer_imessage;

	if (!$flag_3_colonnes) {
		if ($changer_config!="oui"){
			$activer_messagerie=lire_meta("activer_messagerie");
			$activer_imessage=lire_meta("activer_imessage");
		}
	
		if ($activer_messagerie!="non" AND $connect_activer_messagerie!="non"){
			if ($activer_imessage != "non" AND ($connect_activer_imessage != "non" OR $connect_statut == "0minirezo")) {
				$query2 = "SELECT id_auteur, nom FROM spip_auteurs WHERE id_auteur!=$connect_id_auteur AND imessage!='non' AND messagerie!='non' AND en_ligne>DATE_SUB(NOW(),INTERVAL 5 MINUTE)";
				$result_auteurs = spip_query($query2);
				$nb_connectes = spip_num_rows($result_auteurs);
			}
	
			$flag_cadre = (($nb_connectes > 0) OR $rubrique == "messagerie");
			if ($flag_cadre) debut_cadre_relief("messagerie-24.gif");
			if ($rubrique == "messagerie") {
				echo "<a href='message_edit.php3?new=oui&type=normal'><img src='img_pack/m_envoi$spip_lang_rtl.gif' alt='' width='14' height='7' border='0'>";
				echo "<font color='#169249' face='Verdana,Arial,Sans,sans-serif' size=1><b>&nbsp;"._T('lien_nouveau_message')."</b></font></a>";
				echo "\n<br><a href='message_edit.php3?new=oui&type=pb'><img src='img_pack/m_envoi_bleu$spip_lang_rtl.gif' alt='' width='14' height='7' border='0'>";
				echo "<font color='#044476' face='Verdana,Arial,Sans,sans-serif' size=1><b>&nbsp;"._T('lien_nouvea_pense_bete')."</b></font></a>";
				if ($connect_statut == "0minirezo") {
					echo "\n<br><a href='message_edit.php3?new=oui&type=affich'><img src='img_pack/m_envoi_jaune$spip_lang_rtl.gif' alt='' width='14' height='7' border='0'>";
					echo "<font color='#ff9900' face='Verdana,Arial,Sans,sans-serif' size=1><b>&nbsp;"._T('lien_nouvelle_annonce')."</b></font></a>";
				}
			}
			
			if ($flag_cadre) {
				echo "<font face='Verdana,Arial,Sans,sans-serif' size=2>";
				if ($nb_connectes > 0) {
					if ($options == "avancees" AND $rubrique == "messagerie") echo "<p>";
					echo "<b>"._T('info_en_ligne')."</b>";
					while ($row = spip_fetch_array($result_auteurs)) {
						$id_auteur = $row["id_auteur"];
						$nom_auteur = typo($row["nom"]);
						if ($options == "avancees") echo "<br>".bouton_imessage($id_auteur,$row)." $nom_auteur";
						else  echo "<br> $nom_auteur";
					}
				}
				echo "</font>";
			}
			if ($flag_cadre) fin_cadre_relief();
		}
	}	
}


//
// Presentation de l''interface privee, marge de droite
//

function creer_colonne_droite($rubrique=""){
	global $deja_colonne_droite;
	global $changer_config;
	global $activer_messagerie;
	global $activer_imessage;
	global $connect_activer_messagerie;
	global $connect_activer_imessage;
	global $connect_statut, $cookie_admin;
	global $options;
	global $connect_id_auteur, $spip_ecran;
	global $flag_3_colonnes, $flag_centre_large;
	global $spip_lang_rtl, $lang_left;

	if ($flag_3_colonnes AND !$deja_colonne_droite) {
		$deja_colonne_droite = true;

		if ($flag_centre_large) {
			$espacement = 17;
			$largeur = 140;
		}
		else {
			$espacement = 37;
			$largeur = 200;
		}


		echo "<td width=$espacement rowspan=2>&nbsp;</td>";
		echo "<td rowspan=1></td>";
		echo "<td width=$espacement rowspan=2>&nbsp;</td>";
		echo "<td width=$largeur rowspan=2 align='$lang_left' valign='top'><p />";

		if ($changer_config!="oui") {
			$activer_messagerie=lire_meta("activer_messagerie");
			$activer_imessage=lire_meta("activer_imessage");
		}

		if ($activer_messagerie!="non" AND $connect_activer_messagerie!="non") {
			if ($activer_imessage != "non" AND ($connect_activer_imessage != "non" OR $connect_statut == "0minirezo")) {
				$query2 = "SELECT id_auteur, nom FROM spip_auteurs WHERE id_auteur!=$connect_id_auteur AND imessage!='non' AND messagerie!='non' AND en_ligne>DATE_SUB(NOW(),INTERVAL 5 MINUTE)";
				$result_auteurs = spip_query($query2);
				$nb_connectes = spip_num_rows($result_auteurs);
			}

			$flag_cadre = ($nb_connectes > 0);
			if ($flag_cadre) debut_cadre_relief("messagerie-24.gif");

			if ($flag_cadre) {
				echo "<font face='Verdana,Arial,Sans,sans-serif' size=2>";
				if ($nb_connectes > 0) {
					if ($options == "avancees" AND $rubrique == "messagerie") echo "<p>";
					echo "<b>"._T('info_nombre_en_ligne')."</b>";
					while ($row = spip_fetch_array($result_auteurs)) {
						$id_auteur = $row["id_auteur"];
						$nom_auteur = typo($row["nom"]);
						if ($options == "avancees") echo "<br>".bouton_imessage($id_auteur,$row)." $nom_auteur";
						else  echo "<br> $nom_auteur";
					}
				}
				echo "</font>";
			}
			if ($flag_cadre) fin_cadre_relief();
		}
	}

}

function debut_droite($rubrique="") {
	global $options, $spip_ecran, $deja_colonne_droite;
	global $connect_id_auteur, $connect_statut, $connect_toutes_rubriques, $clean_link;
	global $flag_3_colonnes, $flag_centre_large, $couleur_foncee, $couleur_claire;
	global $lang_left;

	if ($options == "avancees") {
		// liste des articles bloques
		if (lire_meta("articles_modif") != "non") {
			$query = "SELECT id_article, titre FROM spip_articles WHERE auteur_modif = '$connect_id_auteur' AND date_modif > DATE_SUB(NOW(), INTERVAL 1 HOUR) ORDER BY date_modif DESC";
			$result = spip_query($query);
			$num_articles_ouverts = spip_num_rows($result);
			if ($num_articles_ouverts) {
				echo "<p>";
				debut_cadre_enfonce('article-24.gif');
				//echo "<font face='Verdana,Arial,Sans,sans-serif' size='2'>";
				echo "<div class='verdana2' style='padding: 2px; background-color:$couleur_foncee; color: white; font-weight: bold;'>";
					echo _T('info_cours_edition')."&nbsp;:".aide('artmodif');
				echo "</div>";
				while ($row = @spip_fetch_array($result)) {
					$ze_article = $row['id_article'];
					$ze_titre = typo($row['titre']);


					if ($ifond == 1) {
						$couleur = $couleur_claire;
						$ifond = 0;
					} else {
						$couleur = "#eeeeee";
						$ifond = 1;
					}
					
					echo "<div style='padding: 3px; background-color: $couleur;'>";
					echo "<div class='verdana1'><b><a href='articles.php3?id_article=$ze_article'>$ze_titre</a></div></b>";
					
					// ne pas proposer de debloquer si c'est l'article en cours d'edition
					if ($ze_article != $GLOBALS['id_article_bloque']) {
						$nb_liberer ++;
						$lien = $clean_link;
						$lien->addVar('debloquer_article', $ze_article);
						echo "<div class='arial1' style='text-align:right;'><a href='". $lien->getUrl() ."' title='"._T('lien_liberer')."'>"._T('lien_liberer')."&nbsp;<img src='img_pack/croix-rouge.gif' alt='X' width='7' height='7' border='0' align='middle'></a></div>";
					}
				
					echo "</div>";
				}
				if ($nb_liberer >= 4) {
					$lien = $clean_link;
					$lien->addVar('debloquer_article', 'tous');
					echo "<div class='arial2' style='text-align:right; padding:2px; border-top: 1px solid $couleur_foncee;'><a href='". $lien->getUrl() ."'>"._T('lien_liberer_tous')."&nbsp;<img src='img_pack/croix-rouge.gif' alt='' width='7' height='7' border='0' align='middle'></a></div>";
				}
				//echo "</font>";
				fin_cadre_enfonce();
			}
		}
		
		if (!$deja_colonne_droite) creer_colonne_droite($rubrique);
	}

	echo "<br></font>&nbsp;</td>";

	if (!$flag_3_colonnes) {
		echo "<td width=50>&nbsp;</td>";
	}
	else {
		if (!$deja_colonne_droite) {
			creer_colonne_droite($rubrique);
		}
		echo "</td></tr><tr>";
	}

	if ($spip_ecran == 'large' AND $flag_centre_large)
		$largeur = 600;
	else
		$largeur = 500;

	echo '<td width="'.$largeur.'" valign="top" align="'.$lang_left.'" rowspan=1><font face="Georgia,Garamond,Times,serif" size=3>';

	// touche d'acces rapide au debut du contenu
	echo "\n<a name='saut' href='#saut' accesskey='s'></a>\n";
}


//
// Presentation de l'interface privee, fin de page et flush()
//

function fin_html() {

	echo "</font>";

	// rejouer le cookie de session si l'IP a change
	if ($GLOBALS['spip_session'] && $GLOBALS['auteur_session']['ip_change']) {
		echo "<img name='img_session' src='img_pack/rien.gif' width='0' height='0'>\n";
		echo "<script type='text/javascript'><!-- \n";
		echo "document.img_session.src='../spip_cookie.php3?change_session=oui';\n";
		echo "// --></script>\n";
	}

	echo "</body></html>\n";
	flush();
}


function fin_page($credits='') {
	global $spip_version_affichee;
	global $connect_id_auteur;


	echo "</td></tr></table>";

	debut_grand_cadre();

	echo "<div align='right'><font face='Verdana,Arial,Sans,sans-serif' size='2'>";
	echo "<b>SPIP $spip_version_affichee</b> ";
	echo _T('info_copyright');

	echo "<br>"._T('info_copyright_doc');

	if (ereg("jimmac", $credits))
		echo "<br>"._T('lien_icones_interface');

	echo "<p></font></div>";

	fin_grand_cadre();
	echo "</center>";

	fin_html();
}


//
// Afficher la hierarchie des rubriques
//
function afficher_parents($id_rubrique) {
	global $parents, $couleur_foncee, $lang_dir;

	$parents = ereg_replace("(~+)","\\1~",$parents);
	if ($id_rubrique) {
		$query = "SELECT id_rubrique, id_parent, titre, lang FROM spip_rubriques WHERE id_rubrique=$id_rubrique";
		$result = spip_query($query);

		while ($row = spip_fetch_array($result)) {
			$id_rubrique = $row['id_rubrique'];
			$id_parent = $row['id_parent'];
			$titre = $row['titre'];
			changer_typo($row['lang']);

			$parents = " <FONT SIZE=3 FACE='Verdana,Arial,Sans,sans-serif'><a href='naviguer.php3?coll=$id_rubrique'><font color='$couleur_foncee'><span dir='$lang_dir'>".typo($titre)."</span></font></a></FONT><BR>\n".$parents;
			if (acces_restreint_rubrique($id_rubrique))
				$parents = " <img src='img_pack/admin-12.gif' alt='' width='12' height='12' title='"._T('info_administrer_rubriques')."'> ".$parents;
			if (!$id_parent)
				$parents = "~ <IMG SRC='img_pack/secteur-24.gif' alt='' WIDTH=24 HEIGHT=24 BORDER=0 align='middle'> ".$parents;
			else
				$parents = "~ <IMG SRC='img_pack/rubrique-24.gif' alt='' WIDTH=24 HEIGHT=24 BORDER=0 align='middle'> ".$parents;
		}
		afficher_parents($id_parent);
	}
}




//
// Presentation des pages d'installation et d'erreurs
//

function install_debut_html($titre='AUTO', $onload='') {
	global $spip_lang_rtl;

	if ($titre=='AUTO')
		$titre=_T('info_installation_systeme_publication');

	if (!$charset = lire_meta('charset')) $charset = 'utf-8';
	@Header("Content-Type: text/html; charset=$charset");

	echo "<html><head>
	<title>$titre</title>
	<meta http-equiv='Expires' content='0'>
	<meta http-equiv='cache-control' content='no-cache,no-store'>
	<meta http-equiv='pragma' content='no-cache'>
	<meta http-equiv='Content-Type' content='text/html; charset=$charset'>
	<style>
	<!--
	a {text-decoration: none; }
	A:Hover {color:#FF9900; text-decoration: underline;}
	.forml {width: 100%; background-color: #FFCC66; background-position: center bottom; float: none; color: #000000}
	.formo {width: 100%; background-color: #FFF0E0; background-position: center bottom; weight: bold; float: none; color: #000000}
	.fondl {background-color: #FFCC66; background-position: center bottom; float: none; color: #000000}
	.fondo {background-color: #FFF0E0; background-position: center bottom; float: none; color: #000000}
	.fondf {background-color: #FFFFFF; border-style: solid ; border-width: 1; border-color: #E86519; color: #E86519}
	-->
	</style>
	</head>
	<body bgcolor='#FFFFFF' text='#000000' link='#E86519' vlink='#6E003A' alink='#FF9900' topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'";

	if ($onload) echo ' onLoad="$onload"';
	if ($spip_lang_rtl) echo " dir='rtl'";

	echo "><br><br><br>
	<center>
	<table width='450'>
	<tr><td width='450'>
	<font face='Verdana,Arial,Sans,sans-serif' size='4' color='#970038'><B>$titre</b></font>
	<font face='Georgia,Garamond,Times,serif' size='3'>";
}

function install_fin_html() {

	echo '
	</font>
	</td></tr></table>
	</center>
	</body>
	</html>
	';
}


?>
