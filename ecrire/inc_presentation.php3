<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_PRESENTATION")) return;
define("_ECRIRE_INC_PRESENTATION", "1");

include_ecrire("inc_lang.php3");
utiliser_langue_visiteur();

//
// Aide
//
function aide ($aide='') {
	global $couleur_foncee;

	if (!$aide) return;

	if (!ereg("/ecrire/", $GLOBALS['REQUEST_URI']))
		$dir_ecrire = 'ecrire/';

	return "&nbsp;&nbsp;<script><!--\n".
	'document.write("<a href=\"javascript:window.open(\''.$dir_ecrire.'aide_index.php3?aide='.
	$aide.
	"', 'aide_spip', 'scrollbars=yes,resizable=yes,width=740,height=580'); ".
	'void(0);\">");'.
	"\n// --></script><noscript>".
	'<a href="'.$dir_ecrire.'aide_index.php3?aide='.
	$aide.
	'" target="_blank"></noscript><img src="'.$dir_ecrire.'img_pack/aide.gif" alt="'._T('info_image_aide').'" title="'._T('titre_image_aide').'" width="12" height="12" border="0" align="middle"></a>'; // "
}


//
// affiche un bouton imessage
//
function bouton_imessage($destinataire, $row = '') {
	// si on passe "force" au lieu de $row, on affiche l'icone sans verification
	global $connect_id_auteur;

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

	$texte_bouton = "<img src='img_pack/m_envoi.gif' width='14' height='7' border='0'>";
	return "<a href='". $url->getUrl() ."' title=\"$title\">$texte_bouton</a>";
}

//
// Cadres
//

function debut_cadre($style, $icone, $fonction) {
	global $spip_display;
	if ($spip_display != 1){
		if (strlen($icone)<3) $icone = "rien.gif";
		$retour_aff .= "\n<table class='cadre' cellspacing='0' cellpadding='0'><tr>";
		$retour_aff .= "\n<td class='$style-hg'></td>";
		$retour_aff .= "\n<td class='$style-h'>";
		if ($fonction) {
			$retour_aff .= "<div style='background: url(img_pack/$icone) no-repeat; padding: 0px; margin: 0px;'>";
			$retour_aff .= "<img src='img_pack/$fonction'>";
			$retour_aff .= "</div>";
		}
		else $retour_aff .= "<img src='img_pack/$icone'>";
		$retour_aff .= "</td>";
		$retour_aff .= "\n<td class='$style-hd'></td></tr>";
		$retour_aff .= "\n<tr><td class='$style-g'></td>";
		$retour_aff .= "\n<td class='$style-c'>";
	}
	return $retour_aff;
}

function fin_cadre($style) {
	global $spip_display;
	if ($spip_display != 1){
		$retour_aff .= "\n</td>";
		$retour_aff .= "\n<td class='$style-d'></td></tr>";
		$retour_aff .= "\n<tr><td class='$style-bg'></td>";
		$retour_aff .= "\n<td class='$style-b'></td>";
		$retour_aff .= "\n<td class='$style-bd'></td></tr>";
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
		$retour_aff = "<p><div style='border-right: 1px solid #cccccc; border-bottom: 1px solid #cccccc;'><div style='border: 1px solid #666666; padding: 5px; background-color: white;'>";
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
		$retour_aff = "<p><div style=\"border: 1px solid #333333; background-color: #e0e0e0;\"><div style=\"padding: 5px; left-right: 1px solid #999999; border-top: 1px solid #999999;\">";
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
	echo "<font face='Verdana,Arial,Helvetica,sans-serif' size='2' color='#333333'>";
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
	echo "<tr bgcolor='$couleur_fond'><td width=\"100%\"><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='$couleur_texte'>";
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
	echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=1>";
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
				$texte .= "<A HREF=\"".$link->getUrl()."\">$deb</A>";
			}
		}
		$texte .= "</td>\n";
		$texte .= "<td background=\"\" class=\"arial2\" colspan=\"1\" align=\"right\" valign=\"top\">";
		if ($deb_aff == -1) {
			$texte .= "<B>"._T('info_tout_afficher')."</B>";
		} else {
			$link = new Link;
			$link->addTmpVar($tmp_var, -1);
			$texte .= "<A HREF=\"".$link->getUrl()."\">"._T('lien_tout_afficher')."</A>";
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
function afficher_articles($titre_table, $requete, $afficher_visites = false, $afficher_auteurs = true, $toujours_afficher = false, $afficher_cadre = true) {
	global $connect_id_auteur, $connect_statut;

	$activer_messagerie = lire_meta("activer_messagerie");
	$activer_statistiques = lire_meta("activer_statistiques");
	$activer_statistiques_ref = lire_meta("activer_statistiques_ref");

	$tranches = afficher_tranches_requete($requete, $afficher_auteurs ? 3 : 2);

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
			$popularite = ceil(min(100,100 * $row['popularite'] / max(1, 0 + lire_meta('popularite_max'))));
			$descriptif = $row['descriptif'];
			if ($descriptif) $descriptif = ' title="'.attribut_html(typo($descriptif)).'"';

			$query_petition = "SELECT id_article FROM spip_petitions WHERE id_article=$id_article";
			$result_petition = spip_query($query_petition);
			$petition = (@spip_num_rows($result_petition) > 0);

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
			$s .= "<a href=\"articles.php3?id_article=$id_article\"$descriptif>".typo($titre)."</a>";
			if ($petition) $s .= " <Font size=1 color='red'>"._T('lien_petitions')."</font>";

			$vals[] = $s;

			if ($afficher_auteurs) $vals[] = $les_auteurs;

			$s = affdate($date);
			if ($connect_statut == "0minirezo" AND $activer_statistiques != "non" AND $afficher_visites AND $visites > 0) {
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
	global $connect_id_auteur;

	$tranches = afficher_tranches_requete($requete, 2);

	if (strlen($tranches)) {

		debut_cadre_relief("breve-24.gif");

		if ($titre_table) {
			echo "<p><table width=100% cellpadding=0 cellspacing=0 border=0 background=''>";
			echo "<tr><td width=100% background=''>";
			echo "<table width=100% cellpadding=3 cellspacing=0 border=0>";
			echo "<tr bgcolor='#EEEECC'><td width=100% colspan=2><font face='Verdana,Arial,Helvetica,sans-serif' size=3 color='#000000'>";
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
			$id_rubrique = $row['id_rubrique'];
			switch ($statut) {
			case 'prop':
				$puce = "puce-blanche";
				$title = _T('titre_breve_proposee');
				break;
			case 'publie':
				$puce = "puce-verte";
				$title = _T('titre_breve_publiee');
				break;
			case 'refuse':
				$puce = "puce-rouge";
				$title = _T('titre_breve_refusee');
				break;
			}

			$s = "<a href='breves_voir.php3?id_breve=$id_breve' title=\"$title\">";
			$s .= "<img src='img_pack/$puce.gif' alt='' width='8' height='9' border='0'></a>&nbsp;&nbsp;";
			$s .= "<a href='breves_voir.php3?id_breve=$id_breve'>";
			$s .= typo($titre);
			$s .= "</a>";
			$vals[] = $s;

			$s = "<div align=\"right\">";
			if ($affrub) {
				$rub = spip_fetch_array(spip_query("SELECT titre FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
				$s .= typo($rub['titre']);
			} else if ($statut != "prop")
				$s .= affdate($date_heure);
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

	$tranches = afficher_tranches_requete($requete, 2);

	if (strlen($tranches)) {

		debut_cadre_relief("rubrique-24.gif");

		if ($titre_table) {
			echo "<p><table width=100% cellpadding=0 cellspacing=0 border=0 background=''>";
			echo "<tr><td width=100% background=''>";
			echo "<table width=100% cellpadding=3 cellspacing=0 border=0>";
			echo "<tr bgcolor='#333333'><td width=100% colspan=2><font face='Verdana,Arial,Helvetica,sans-serif' size=3 color='#FFFFFF'>";
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
			$puce = "puce.gif";
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
			echo "<tr bgcolor='#333333'><td width=100% colspan=2><font face='Verdana,Arial,Helvetica,sans-serif' size=3 color='#FFFFFF'>";
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
					$fleche='img_pack/forum-droite.gif';
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
			echo "<table width=100% cellpadding=3 cellspacing=0><tr><td bgcolor='$couleur_foncee'><font face='Verdana,Arial,Helvetica,sans-serif' size=2 color='#FFFFFF'><b>".typo($titre)."</b></font></td></tr>";
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
				echo "<p align='left'><font face='Verdana,Arial,Helvetica,sans-serif'><b><a href='$url_site'>$nom_site</a></b></font>";
			}

			if ($controle != "oui") {
				echo "<p align='right'><font face='Verdana,Arial,Helvetica,sans-serif' size=1>";
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
	global $spip_lang;

	$nom_site_spip = entites_html(lire_meta("nom_site"));
	$titre = textebrut(typo($titre));

	if (!$nom_site_spip) $nom_site_spip="SPIP";
	$charset = lire_meta('charset');

	@Header("Expires: 0");
	@Header("Cache-Control: no-cache,no-store");
	@Header("Pragma: no-cache");
	@Header("Content-Type: text/html; charset=$charset");
	
	echo "<html>\n<head>\n<title>[$nom_site_spip] $titre</title>\n";
	echo '<link rel="stylesheet" type="text/css" href="';
	if (!$flag_ecrire) echo 'ecrire/';
	echo "spip_style.php3?couleur_claire=".urlencode($couleur_claire)."&couleur_foncee=" . urlencode($couleur_foncee) ."\">\n";

	afficher_script_layer();
?>
<script language="JavaScript"><!--
function changeclass(objet, myClass)
{ 
  objet.className = myClass;
}
//--></script>
</head>
<body text="#000000" bgcolor="#e4e4e4" background="img_pack/degrade.jpg" link="<?php echo $couleur_lien; ?>" vlink="<?php echo $couleur_lien_off; ?>" alink="<?php echo $couleur_lien_off ?>"  topmargin="0" leftmargin="0" marginwidth="0" marginheight="0"<?php

	if ($spip_lang == 'ar')
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
			echo "<font face='Verdana,Arial,Helvetica,sans-serif' size='2' color='black'><b>$texte</b></font>";
			echo "</td>";
		}
		else {
			echo "\n<td class='iconeoff' onMouseOver=\"changeclass(this,'iconeon');\" onMouseOut=\"changeclass(this,'iconeoff');\" onClick=\"document.location='$lien'\" valign='middle'>";
			echo "<a href='$lien' class='icone'><font face='Verdana,Arial,Helvetica,sans-serif' size='2' color='#666666'><b>$texte</b></font></a>";
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
			echo "&nbsp; <font face='Verdana,Arial,Helvetica,sans-serif' size='2' color='black'><b>$texte</b></font> &nbsp;";
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
			echo "<a href='$lien' class='icone'>&nbsp; <font face='Verdana,Arial,Helvetica,sans-serif' size='2' color='#666666'><b>$texte</b></font></a> &nbsp;";
			echo "</td>";

			echo "\n</tr></table>";
			echo "\n</td>\n";
		}
	}
}


function barre_onglets($rubrique, $onglet){
	global $id_auteur, $connect_id_auteur, $connect_statut, $statut_auteur, $options;

	debut_onglet();

	if ($rubrique == "statistiques"){
		onglet(_T('onglet_evolution_visite'), "statistiques_visites.php3", "evolution", $onglet, "statistiques-24.gif");
		onglet(_T('onglet_repartition_rubrique'), "statistiques.php3", "repartition", $onglet, "rubrique-24.gif");
		$activer_statistiques_ref = lire_meta("activer_statistiques_ref");
		if ($activer_statistiques_ref != "non")	onglet(_T('onglet_origine_visites'), "statistiques_referers.php3", "referers", $onglet, "referers-24.gif");
	}

	if ($rubrique == "administration"){
		onglet(_T('onglet_save_restaur_base'), "admin_tech.php3", "sauver", $onglet, "base-24.gif");
		onglet(_T('onglet_vider_cache'), "admin_vider.php3", "vider", $onglet, "cache-24.gif");
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
		//onglet(_T('onglet_langue'), "config-lang.php3", "lang", $onglet, "langues-24.gif");
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

	if ($spip_display == 1){
		$hauteur = 20;
		$largeur = 80;
	}
	else if ($spip_display == 3){
		$hauteur = 50;
		$largeur = 52;
		$title = " title=\"$texte\" ";
		$alt = " alt=\"$texte\" ";
	}
	else {
		$hauteur = 80;
		$largeur = 80;
		$alt = " alt=\"\" ";
	}

	if (eregi("^javascript:",$lien)){
		$java_lien = substr($lien, 11, strlen($lien));
		$onClick = "";
		$a_href = '<script language="JavaScript"><!--' . "\n"
			. 'document.write("<a href=\\"javascript:'.addslashes($java_lien).'\\"");'."\n".'//--></script>'
			. "<noscript><a href='$lien_noscript' target='_blank'></noscript>\n";
		$a_href_icone = '<script language="JavaScript"><!--' . "\n"
			. 'document.write("<a href=\\"javascript:'.addslashes($java_lien).'\\" class=\\"icone\\"");'."\n".'//--></script>'
			. "<noscript><a href='$lien_noscript' target='_blank'></noscript>\n";
	}
	else {
		$onClick = "";
		$a_href = "<a href=\"$lien\">";
		$a_href_icone = "<a href=\"$lien\" class='icone'>";
	}

	if ($rubrique_icone == $rubrique){
		echo "\n<td background='' align='center' width='$largeur' class=\"fondgrison\" $onClick>";
		echo "\n<table cellpadding=0 cellspacing=0 border=0 width=$largeur>";
		echo "<tr><td background=''>";
		echo "<img src='img_pack/rien.gif' width=$largeur height=1>";
		echo "</td></tr>";
		echo "<tr><td background='' align='center' width='$largeur' height='$hauteur'>";
		if ($spip_display != 1) {
			echo "$a_href<img src='img_pack/$fond'$alt$title border='0'></a><br>";
		}
		if ($spip_display != 3) {
			echo "$a_href_icone<font face='Verdana,Arial,Helvetica,sans-serif' size='2' color='black'><b>$texte</b></font></a>";
		}
		echo "</td></tr></table>";
		echo "</td>\n";
	} 
	else {
		echo "\n<td background='' align='center' width='$largeur' class=\"fondgris\" onMouseOver=\"changeclass(this,'fondgrison2');\" onMouseOut=\"changeclass(this,'fondgris');\" $onClick>";
		echo "\n<table cellpadding=0 cellspacing=0 border=0 width=$largeur>";
		echo "<tr><td background=''>";
		echo "<img src='img_pack/rien.gif' width=$largeur height=1>";
		echo "</td></tr>";
		echo "<tr><td background='' align='center' width='$largeur' height='$hauteur'>";
		if ($spip_display != 1) {
			echo "$a_href<img src='img_pack/$fond'$alt$title border='0'></a><br>";
		}
		if ($spip_display != 3) {
			echo "$a_href_icone<font face='Verdana,Arial,Helvetica,sans-serif' size='2' color='black'><b>$texte</b></font></a>";
		}
		echo "</td></tr></table>";
		echo "</td>\n";
	}
	
	if ($spip_ecran == "large") {
		echo "<td width=10><img src='img_pack/rien.gif' border=0 width=10 height=1></td>";
	}
	
}


function icone_bandeau_secondaire($texte, $lien, $fond, $rubrique_icone = "vide", $rubrique, $aide=""){
	global $spip_display;

	if ($spip_display == 1){
		$hauteur = 20;
		$largeur = 80;
	}
	else if ($spip_display == 3){
		$hauteur = 26;
		$largeur = 28;
		$title = " title=\"$texte\"";
		$alt = " alt=\"$texte\"";
	}
	else {
		$hauteur = 70;
		$largeur = 80;
		$alt = " alt=\"\"";
	}

	if ($rubrique_icone == $rubrique){
		echo "\n<td background='' align='center' width='$largeur' class=\"fondgrison\">";
		echo "\n<table cellpadding=0 cellspacing=0 border=0>";
		if ($spip_display != 1){	
			echo "<tr><td background='' align='center'>";
			echo "<a href='$lien'><img src='img_pack/$fond'$alt$title width='24' height='24' border='0' align='middle'></a>";
			if (strlen($aide)>0) echo aide($aide);
			echo "</td></tr>";
		}
		echo "<tr><td background=''>";
		echo "<img src='img_pack/rien.gif' width=$largeur height=1>";
		echo "</td></tr>";
		echo "</table>";
		if ($spip_display != 3){
			echo "<a href='$lien' class='icone'><font face='Verdana,Arial,Helvetica,sans-serif' size='1' color='black'><b>$texte</b></font></a>";
		}
		echo "</td>";
	}
	else {
		echo "\n<td background='' align='center' width='$largeur' class=\"fondgris\" onMouseOver=\"changeclass(this,'fondgrison2');\" onMouseOut=\"changeclass(this,'fondgris');\">";
		echo "\n<table cellpadding=0 cellspacing=0 border=0>";
		if ($spip_display != 1){
			echo "<tr><td background='' align='center'>";
			echo "<a href='$lien'><img src='img_pack/$fond'$alt$title width='24' height='24' border='0' align='middle'></a>";
			if (strlen($aide)>0) echo aide($aide);
			echo "</td></tr>";
		}
		echo "<tr><td background=''>";
		echo "<img src='img_pack/rien.gif' width=$largeur height=1>";
		echo "</td></tr>";
		echo "</table>";
		if ($spip_display != 3){
			echo "<a href='$lien' class='icone'><font face='Verdana,Arial,Helvetica,sans-serif' size='1' color='black'><b>$texte</b></font></a>";
		}
		echo "</td>";
	}	
}



function icone($texte, $lien, $fond, $fonction="", $align="", $afficher='oui'){
	global $spip_display, $couleur_claire, $couleur_foncee;
	
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
		$largeur = 70;
	}

	$icone .= "\n<table cellpadding=0 cellspacing=0 border=0 $aligner width=$largeur class=\"iconeoff\" onMouseOver=\"changeclass(this,'iconeon');\" onMouseOut=\"changeclass(this,'iconeoff');\" onClick=\"document.location='$lien'\">";
	$icone .= "<tr><td background='' align='center' valign='middle' width=$largeur height=$hauteur>";
	$icone .= "\n<table cellpadding=0 cellspacing=0 border=0>";
	if ($spip_display != 1){	
		$icone .= "<tr><td background='' align='center'>";
		if ($fonction != "rien.gif"){
			$icone .= "\n<table cellpadding=0 cellspacing=0 border=0><tr><td background='img_pack/$fond'>";
			$icone .= "<a href='$lien'><img src='img_pack/$fonction'$alt$title width='24' height='24' border='0'></a>";
			$icone .= "</td></tr></table>\n";
		}
		else {
			$icone .= "\n<table cellpadding=0 cellspacing=0 border=0><tr><td background=''>";
			$icone .= "<a href='$lien'><img src='img_pack/$fond'$alt$title width='24' height='24' border='0'></a>";
			$icone .= "</td></tr></table>\n";
		}
		$icone .= "</td></tr>";
	}
	$icone .= "<tr><td background=''>";
	$icone .= "<img src='img_pack/rien.gif' width=$largeur height=1>";
	$icone .= "</td></tr>";
	if ($spip_display != 3){
		$icone .= "<tr><td background='' align='center'>";
		$icone .= "<a href='$lien' class='icone'><font face='Verdana,Arial,Helvetica,sans-serif' size='1' color='black'><b>$texte</b></font></a>";
		$icone .= "</td></tr>";
	}
	$icone .= "</table>";
	$icone .= "</td></tr>";
	$icone .= "</table>";

	if ($afficher == 'oui')
		echo $icone;
	else
		return $icone;
}

function icone_horizontale($texte, $lien, $fond = "", $fonction = "") {
	global $spip_display, $couleur_claire, $couleur_foncee;

	if (strlen($fonction) < 3) $fonction = "rien.gif";

	$hauteur = 30;
	$largeur = "100%";

	echo "\n<table class=\"icone-h\" onMouseOver=\"changeclass(this,'icone-h-on');\" onMouseOut=\"changeclass(this,'icone-h');\" onClick=\"document.location='$lien'\">";
	echo "<tr>";
	
	if ($spip_display != 1 AND $fond != "") {
		echo "<td class='image' style='background-image: url(\"img_pack/$fond\")'>";
		echo "<a href='$lien'>";
		echo "<img src='img_pack/$fonction' alt=''>";
		echo "</a>";
		echo "</td>";
	}

	echo "<td valign='middle'>";
	echo "<a href='$lien'>";
	echo "$texte";
	echo "</a>";
	echo "</td></tr>";

	echo "</table>\n";
}


function bandeau_barre_verticale(){
	global $spip_ecran;
	if ($spip_ecran == "large")
		$decalage = "<td width=10><img src='img_pack/rien.gif' border=0 width=10 height=1></td>";
	echo $decalage;
	echo "<td background='img_pack/tirets-separation.gif' width='2'>";
	echo "<img src='img_pack/rien.gif' alt='' width=2 height=2>";
	echo "</td>";
	echo $decalage;
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
	$activer_messagerie = lire_meta("activer_messagerie");
	global $clean_link;
	
	if ($spip_ecran == "large") $largeur = 974;
	else $largeur = 750;
	
	// nettoyer le lien global
	$clean_link->delVar('set_lang');
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
	echo "<table cellpadding='0' style='background-image: url(img_pack/rayures-fines.gif);' width='100%'><tr width='100%'><td width='100%' align='center'>";
	echo "<table cellpadding='0' background='' width='$largeur'><tr width='$largeur'>";
		icone_bandeau_principal (_T('icone_a_suivre'), "index.php3", "asuivre-48.gif", "asuivre", $rubrique);
		icone_bandeau_principal (_T('icone_edition_site'), "naviguer.php3", "documents-48.gif", "documents", $rubrique);
		if ($options == "avancees") {
			icone_bandeau_principal (_T('icone_auteurs'), "auteurs.php3", "redacteurs-48.gif", "redacteurs", $rubrique);
		} else {
			icone_bandeau_principal (_T('icone_informations_personnelles'), "auteurs_edit.php3?id_auteur=$connect_id_auteur", "fiche-perso-48.gif", "redacteurs", $rubrique);
		}
		if ($options == "avancees") {
			if ($connect_statut == "0minirezo")
				icone_bandeau_principal (_T('icone_forums_petitions'), "forum.php3", "messagerie-48.gif", "messagerie", $rubrique);
			else
				icone_bandeau_principal (_T('icone_forum_interne'), "forum.php3", "messagerie-48.gif", "messagerie", $rubrique);
		}
	if ($connect_statut == '0minirezo' and $connect_toutes_rubriques){
	bandeau_barre_verticale();
		icone_bandeau_principal (_T('icone_admin_site'), "configuration.php3", "administration-48.gif", "administration", $rubrique);
	}
	else if ($connect_statut == '0minirezo' and !$connect_toutes_rubriques and lire_meta("activer_statistiques") != 'non'){
	bandeau_barre_verticale();
		icone_bandeau_principal (_T('icone_statistiques'), "statistiques_visites.php3", "administration-48.gif", "administration", $rubrique);
	}
	echo "<td background='' width='100%'>   </td>";
	echo "<td align='center'><font size=1>";
		echo "<img src='img_pack/choix-layout.gif' alt='' vspace=3 border=0 usemap='#map_layout'>";
	echo "</font></td>";
		icone_bandeau_principal (_T('icone_aide_ligne'), "javascript:window.open('aide_index.php3', 'aide_spip', 'scrollbars=yes,resizable=yes,width=740,height=580'); void(0);", "aide-48.gif", "vide", "", "aide_index.php3");
		icone_bandeau_principal (_T('icone_visiter_site'), "$adresse_site", "visiter-48.gif");
	echo "</tr></table>";
	echo "</td></tr></table>";


	// Icones secondaires
	echo "<table cellpadding='0' bgcolor='white' style='border-bottom: solid 1px black; border-top: solid 1px #333333;' width='100%'><tr width='100%'><td width='100%' align='center'>";

	echo "<table cellpadding='0' background='' width='$largeur'><tr width='$largeur'>";

	if ($rubrique == "asuivre"){
		icone_bandeau_secondaire (_T('icone_a_suivre'), "index.php3", "asuivre-24.gif", "asuivre", $sous_rubrique);
		icone_bandeau_secondaire (_T('icone_site_entier'), "articles_tous.php3", "tout-site-24.gif", "tout-site", $sous_rubrique);
		if ($options == "avancees") {
			bandeau_barre_verticale();
			icone_bandeau_secondaire (_T('icone_calendrier'), "calendrier.php3", "calendrier-24.gif", "calendrier", $sous_rubrique);
		}
	}
	else if ($rubrique == "documents"){
		icone_bandeau_secondaire (_T('icone_rubriques'), "naviguer.php3", "rubrique-24.gif", "rubriques", $sous_rubrique);

		$nombre_articles = spip_num_rows(spip_query("SELECT art.id_article FROM spip_articles AS art, spip_auteurs_articles AS lien WHERE lien.id_auteur = '$connect_id_auteur' AND art.id_article = lien.id_article"));
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
	else if ($rubrique == "redacteurs"){
		if ($options == "avancees" OR $connect_statut == "0minirezo")
			icone_bandeau_secondaire (_T('icone_tous_auteur'), "auteurs.php3", "redacteurs-24.gif", "redacteurs", $sous_rubrique);

		icone_bandeau_secondaire (_T('icone_informations_personnelles'), "auteurs_edit.php3?id_auteur=$connect_id_auteur", "fiche-perso-24.gif", "perso", $sous_rubrique);
	}
	else if ($rubrique == "messagerie"){
		icone_bandeau_secondaire (_T('titre_forum'), "forum.php3", "forum-interne-24.gif", "forum-interne", $sous_rubrique);

		if ($connect_statut == "0minirezo"){
			if (lire_meta('forum_prive_admin') == 'oui')
				icone_bandeau_secondaire (_T('icone_forum_administrateur'), "forum_admin.php3", "forum-admin-24.gif", "forum-admin", $sous_rubrique);
			bandeau_barre_verticale();
			icone_bandeau_secondaire (_T('icone_suivi_forums'), "controle_forum.php3", "suivi-forum-24.gif", "forum-controle", $sous_rubrique);
			icone_bandeau_secondaire (_T('icone_suivi_pettions'), "controle_petition.php3", "petition-24.gif", "suivi-petition", $sous_rubrique);
		}

			bandeau_barre_verticale();
		if ($activer_messagerie != 'non' AND $connect_activer_messagerie != 'non')
			icone_bandeau_secondaire (_T('icone_messagerie_personnelle'), "messagerie.php3", "messagerie-24.gif", "messagerie", $sous_rubrique);
	}
	else if ($rubrique == "administration"){
		if ($connect_toutes_rubriques) {
			icone_bandeau_secondaire (_T('icone_configuration_site'), "configuration.php3", "administration-24.gif", "configuration", $sous_rubrique);
		}
		if (lire_meta("activer_statistiques") != 'non')
			icone_bandeau_secondaire (_T('icone_statistiques_visites'), "statistiques_visites.php3", "statistiques-24.gif", "statistiques", $sous_rubrique);
		if ($connect_toutes_rubriques) {
			if ($options == "avancees") {
				icone_bandeau_secondaire (_T('icone_maintenance_site'), "admin_tech.php3", "base-24.gif", "base", $sous_rubrique);
			}
			else {
				icone_bandeau_secondaire (_T('icone_sauver_site'), "admin_tech.php3", "base-24.gif", "base", $sous_rubrique);
			}
		}
	}

	if ($options == "avancees") {
		global $recherche;
		if ($recherche == '' AND $spip_display != 2)
			$recherche_aff = _T('info_rechercher');
		else
			$recherche_aff = $recherche;
		bandeau_barre_verticale();
		echo "<td width='5'><img src='img_pack/rien.gif' width=5></td>";
		echo "<td>";
		echo "<form method='get' style='margin: 0px;' action='recherche.php3'>";
		if ($spip_display == "2")
			echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=1><b>"._T('info_rechercher_02')."</b></font><br>";
		echo '<input type="text" size="18" value="'.$recherche_aff.'" name="recherche" class="spip_recherche">';
		echo "</form>";
		echo "</td>";
	}


	echo "<td width='100%'>   </td>";

	if ($auth_can_disconnect) {
		echo "<td width='5'>&nbsp;</td>";
		icone_bandeau_secondaire (_T('icone_deconnecter'), "../spip_cookie.php3?logout=$connect_login", "deconnecter-24.gif", "", $sous_rubrique, "deconnect");
	}

	echo "</tr></table>";
	echo "</td></tr></table>";


	// Bandeau
	echo "\n<table cellpadding='0' bgcolor='$couleur_foncee' style='border-bottom: solid 1px white; border-top: solid 1px #666666;' width='100%'><tr width='100%'><td width='100%' align='center'>";
	echo "<table cellpadding='0' background='' width='$largeur'><tr width='$largeur'><td>";
		if ($activer_messagerie != 'non' AND $connect_activer_messagerie != 'non') {
			echo "<font face='arial,helvetica,sans-serif' size=1><b>";
			$result_messages = spip_query("SELECT * FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE lien.id_auteur=$connect_id_auteur AND vu='non' AND statut='publie' AND type='normal' AND lien.id_message=messages.id_message");
			$total_messages = @spip_num_rows($result_messages);
			if ($total_messages == 1) {
				while($row = @spip_fetch_array($result_messages)) {
					$ze_message=$row['id_message'];
					echo "<a href='message.php3?id_message=$ze_message'><font color='$couleur_claire'><b>"._T('info_nouveau_message')."</b></font></a>";
				}
			}
			if ($total_messages > 1) echo "<a href='messagerie.php3'><font color='$couleur_claire'>"._T('info_nouveaux_messages', array('total_messages' => $total_messages))."</font></a>";
			$result_messages = spip_query("SELECT messages.* FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE lien.id_auteur='$connect_id_auteur' AND messages.statut='publie' AND lien.id_message=messages.id_message AND messages.rv='oui' AND messages.date_heure>DATE_SUB(NOW(),INTERVAL 1 DAY) GROUP BY messages.id_message");
			$total_messages = @spip_num_rows($result_messages);

			if ($total_messages == 1) {
				while ($row = @spip_fetch_array($result_messages)) {
					$ze_message = $row['id_message'];
					echo " | <a href='message.php3?id_message=$ze_message'><font color='white'>"._T('lien_rendez_vous')."</font></a> ";
				}
			}
			if ($total_messages > 1) echo " | <a href='calendrier.php3'><font color='white'>"._T('lien_rendez_vous_02', array('total_messages' => $total_messages))."</font></a> ";
			echo "</b></font>";
		}

	echo "</td>";
	echo "<td>   </td>";
	echo "<td>";
	echo "<font size=1 face='Verdana,Arial,Helvetica,sans-serif'>";
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
	echo "<td align='center'>";
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
		$lien = $clean_link;
		$lien->addVar('changer_var', 'oui'); // Bidon, pour forcer point d'interrogation

		echo "<form action='".$lien->getUrl()."' method='get' style='margin:0px; padding:0px;'>";
		echo "\n<select name='set_lang' class='verdana1' style='background-color: $couleur_foncee; color: white;' onChange=\"document.location.href='". $lien->getUrl() ."&set_lang='+this.options[this.selectedIndex].value\">\n";
		$langues = explode(',', $GLOBALS['all_langs']);
		while (list(,$l) = each ($langues)) {
			if ($l == $GLOBALS['spip_lang']) $selected = " selected";
			else $selected = "";

			echo "<option value='$l'$selected>".traduire_nom_langue($l)."</option>\n";
		}
		echo "</select>\n";
		echo "<noscript><INPUT TYPE='submit' NAME='Valider' VALUE='>>' class='verdana1' style='background-color: $couleur_foncee; color: white; height: 19px;'></noscript>";
		echo "</form>";
		echo "</td>";

	}

	// choix de la couleur
	echo "<td align='right'>";
	echo "<img src='img_pack/barre-couleurs.gif' alt='couleurs' width='70' height='21' border='0' usemap='#map_couleur'>";
	echo "</td>";
	echo "</tr></table>";
	echo "</td></tr></table>";

	echo "<center>";
}


function gros_titre($titre, $ze_logo=''){
	global $couleur_foncee;
	
	echo "<div>";
	if (strlen($ze_logo) > 3) echo "<img src='img_pack/$ze_logo' alt='' border=0 align='middle'> &nbsp; ";
	echo "<span style='border-bottom: 1px dashed $couleur_foncee;'><font size=5 face='Verdana,Arial,Helvetica,sans-serif' color='$couleur_foncee'><b>";
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
				echo "<a href='message_edit.php3?new=oui&type=normal'><img src='img_pack/m_envoi.gif' alt='' width='14' height='7' border='0'>";
				echo "<font color='#169249' face='Verdana,Arial,Helvetica,sans-serif' size=1><b>&nbsp;"._T('lien_nouveau_message')."</b></font></a>";
				echo "\n<br><a href='message_edit.php3?new=oui&type=pb'><img src='img_pack/m_envoi_bleu.gif' alt='' width='14' height='7' border='0'>";
				echo "<font color='#044476' face='Verdana,Arial,Helvetica,sans-serif' size=1><b>&nbsp;"._T('lien_nouvea_pense_bete')."</b></font></a>";
				if ($connect_statut == "0minirezo") {
					echo "\n<br><a href='message_edit.php3?new=oui&type=affich'><img src='img_pack/m_envoi_jaune.gif' alt='' width='14' height='7' border='0'>";
					echo "<font color='#ff9900' face='Verdana,Arial,Helvetica,sans-serif' size=1><b>&nbsp;"._T('lien_nouvelle_annonce')."</b></font></a>";
				}
			}
			
			if ($flag_cadre) {
				echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=2>";
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
		echo "<td width=$largeur rowspan=2 valign='top'><p />";

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

			$flag_cadre = ($nb_connectes > 0 OR $rubrique == "messagerie");
			if ($flag_cadre) debut_cadre_relief("messagerie-24.gif");
			if ($rubrique == "messagerie") {
				echo "<a href='message_edit.php3?new=oui&type=normal'><img src='img_pack/m_envoi.gif' alt='' width='14' height='7' border='0'>";
				echo "<font color='#169249' face='Verdana,Arial,Helvetica,sans-serif' size=1><b>&nbsp;"._T('lien_nouveau_message')."</b></font></a>";
				echo "\n<br><a href='message_edit.php3?new=oui&type=pb'><img src='img_pack/m_envoi_bleu.gif' alt='' width='14' height='7' border='0'>";
				echo "<font color='#044476' face='Verdana,Arial,Helvetica,sans-serif' size=1><b>&nbsp;"._T('lien_nouvea_pense_bete')."</b></font></a>";
				if ($connect_statut == "0minirezo") {
					echo "\n<br><a href='message_edit.php3?new=oui&type=affich'><img src='img_pack/m_envoi_jaune.gif' alt='' width='14' height='7' border='0'>";
					echo "<font color='#ff9900' face='Verdana,Arial,Helvetica,sans-serif' size=1><b>&nbsp;"._T('lien_nouvelle_annonce')."</b></font></a>";
				}
			}

			if ($flag_cadre) {
				echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=2>";
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
	global $flag_3_colonnes, $flag_centre_large;

	if ($options == "avancees") {
		// liste des articles bloques
		if (lire_meta("articles_modif") != "non") {
			$query = "SELECT id_article, titre FROM spip_articles WHERE auteur_modif = '$connect_id_auteur' AND id_rubrique > 0 AND date_modif > DATE_SUB(NOW(), INTERVAL 1 HOUR) ORDER BY date_modif DESC";
			$result = spip_query($query);
			$num_articles_ouverts = spip_num_rows($result);
			if ($num_articles_ouverts) {
				echo "<p>";
				debut_cadre_formulaire('racine-24.gif');
				echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=2>";

				echo _T('info_cours_edition')."&nbsp;:".aide('artmodif')."<br>";
				while ($row = @spip_fetch_array($result)) {
					$ze_article = $row['id_article'];
					$ze_titre = typo($row['titre']);
					echo "<br><a href='articles.php3?id_article=$ze_article'>$ze_titre</a>";
					// ne pas proposer de debloquer si c'est l'article en cours d'edition
					if ($ze_article != $GLOBALS['id_article_bloque']) {
						$lien = $clean_link;
						$lien->addVar('debloquer_article', $ze_article);
						echo " [<a href='". $lien->getUrl() ."'>"._T('lien_liberer')."</a>]";
					}
				}
				echo "</font>";
				fin_cadre_formulaire();
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

	echo '<td width="'.$largeur.'" valign="top" rowspan=1><font face="Georgia,Garamond,Times,serif" size=3>';

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

	?>

</td></tr></table>

<?php
debut_grand_cadre();

echo "<div align='right'><font face='Verdana,Arial,Helvetica,sans-serif' size='2'>";
echo "<a href='http://www.uzine.net/spip'>SPIP $spip_version_affichee</a> ";
echo _T('info_copyright');


if (ereg("jimmac", $credits))
	echo "<br>"._T('lien_icones_interface');
?>
<p>
</font></div>
<?php
fin_grand_cadre();
?>
</center>

	<?php

	fin_html();
}


//
// Afficher la hierarchie des rubriques
//
function afficher_parents($id_rubrique) {
	global $parents, $couleur_foncee;
	$parents = ereg_replace("(~+)","\\1~",$parents);
	if ($id_rubrique) {
		$query = "SELECT id_rubrique, id_parent, titre FROM spip_rubriques WHERE id_rubrique=$id_rubrique";
		$result = spip_query($query);

		while ($row = spip_fetch_array($result)) {
			$id_rubrique = $row['id_rubrique'];
			$id_parent = $row['id_parent'];
			$titre = $row['titre'];

			$parents = " <FONT SIZE=3 FACE='Verdana,Arial,Helvetica,sans-serif'><a href='naviguer.php3?coll=$id_rubrique'><font color='$couleur_foncee'>$titre</font></a></FONT><BR>\n".$parents;
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

	if ($titre=='AUTO')
		$titre=_T('info_installation_systeme_publication');
	?>
<html>
<head>
<title><?php echo $titre; ?></title>
<meta http-equiv="Expires" content="0">
<meta http-equiv="cache-control" content="no-cache,no-store">
<meta http-equiv="pragma" content="no-cache">
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">

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

<body bgcolor="#FFFFFF" text="#000000" link="#E86519" vlink="#6E003A" alink="#FF9900" topmargin="0" leftmargin="0" marginwidth="0" marginheight="0"<?php if($onload) echo " onLoad=\"$onload\""; ?>>

<br><br><br>
<center>
<table width="450">
<tr><td width="450">
<font face="Verdana,Arial,Helvetica,sans-serif" size="4" color="#970038"><B><?php 
	echo $titre; 
?></b></font>
<font face="Georgia,Garamond,Times,serif" size="3">
	<?php
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
