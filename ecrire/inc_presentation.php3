<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_PRESENTATION")) return;
define("_ECRIRE_INC_PRESENTATION", "1");


//
// Aide
//
function aide ($aide) {
	global $couleur_foncee;

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
	'" target="_blank"></noscript><img src="'.$dir_ecrire.'img_pack/aide.gif" alt="AIDE" title="De l\'aide sur cet &eacute;l&eacute;ment" width="12" height="12" border="0" align="middle"></a>';
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
		$row = mysql_fetch_array(spip_query($login_req));
		
		if (($row['login'] == "") OR ($row['messagerie'] == "non")) {
			return;
		}
	}
	$url->addVar('dest',$destinataire);
	$url->addVar('new','oui');
	$url->addVar('type','normal');

	if ($destinataire) $title = "Envoyer un message priv&eacute; &agrave; cet auteur";
	else $title = "Ecrire un message priv&eacute;";

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
		$retour_aff .= "\n<table class='cadre' cellspacing='0'><tr>";
		$retour_aff .= "\n<td class='$style-hg'></td>";
		$retour_aff .= "\n<td class='$style-h'><img src='img_pack/$icone'></td>";
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
		$retour_aff .= "\n<td class='$style-bg'></td>";
		$retour_aff .= "\n<td class='$style-b'></td>";
		$retour_aff .= "\n<td class='$style-bd'></td></tr>";
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
	echo "<b>RACCOURCIS :</b><p>";
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

	list($num_rows) = mysql_fetch_row(spip_query($query_count));
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
			$texte .= "<B>Tout afficher</B>";
		} else {
			$link = new Link;
			$link->addTmpVar($tmp_var, -1);
			$texte .= "<A HREF=\"".$link->getUrl()."\">Tout afficher</A>";
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

		while ($row = mysql_fetch_array($result)) {
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
			$petition = (@mysql_num_rows($result_petition) > 0);

			if ($afficher_auteurs) {
				$les_auteurs = "";
			 	$query2 = "SELECT auteurs.id_auteur, nom, messagerie, login, en_ligne ".
			 		"FROM spip_auteurs AS auteurs, spip_auteurs_articles AS lien ".
			 		"WHERE lien.id_article=$id_article AND auteurs.id_auteur=lien.id_auteur";
				$result_auteurs = spip_query($query2);

				while ($row = mysql_fetch_array($result_auteurs)) {
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

			$s = "<a href=\"articles.php3?id_article=$id_article\">";
			if ($statut=='publie') $puce = 'verte';
			else if ($statut == 'prepa') $puce = 'blanche';
			else if ($statut == 'prop') $puce = 'orange';
			else if ($statut == 'refuse') $puce = 'rouge';
			else if ($statut == 'poubelle') $puce = 'poubelle';
			if (acces_restreint_rubrique($id_rubrique))
				$puce = "puce-$puce-anim.gif";
			else
				$puce = "puce-$puce.gif";

			$s .= "<img src=\"img_pack/$puce\" alt='' width=\"13\" height=\"14\" border=\"0\"></a>&nbsp;&nbsp;";
			$s .= "<a href=\"articles.php3?id_article=$id_article\"$descriptif>".typo($titre)."</a>";
			if ($petition) $s .= " <Font size=1 color='red'>P&Eacute;TITION</font>";

			$vals[] = $s;
		
			if ($afficher_auteurs) $vals[] = $les_auteurs;

			$s = affdate($date);
			if ($connect_statut == "0minirezo" AND $activer_statistiques != "non" AND $afficher_visites AND $visites > 0) {
				$s .= "<br><font size=\"1\"><a href='statistiques_visites.php3?id_article=$id_article'>$visites&nbsp;visites</a></font>";
				if ($popularite > 0) $s .= "<br><font size=\"1\"><a href='statistiques_visites.php3?id_article=$id_article'>popularit&eacute;&nbsp;: $popularite%</a></font>";
			}
			$vals[] = $s;

			$table[] = $vals;
		}
		mysql_free_result($result);

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

function afficher_breves($titre_table, $requete) {
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
		while ($row = mysql_fetch_array($result)) {
			$vals = '';

			$id_breve = $row['id_breve'];
			$tous_id[] = $id_breve;
			$date_heure = $row['date_heure'];
			$titre = $row['titre'];
			$statut = $row['statut'];
			if ($statut == 'prop') $puce = "puce-blanche";
			else if ($statut == 'publie') $puce = "puce-verte";
			else if ($statut == 'refuse') $puce = "puce-rouge";

			$s = "<a href='breves_voir.php3?id_breve=$id_breve'>";
			$s .= "<img src='img_pack/$puce.gif' alt='' width='8' height='9' border='0'></a>&nbsp;&nbsp;";
			$s .= "<a href='breves_voir.php3?id_breve=$id_breve'>";
			$s .= typo($titre);
			$s .= "</a>";
			$vals[] = $s;

			$s = "<div align=\"right\">";
			if ($statut == "prop") $s .= "[<font color=\"red\">&agrave; valider</font>]";
			else $s .= affdate($date_heure);
			$s .= "</div>";
			$vals[] = $s;
			$table[] = $vals;
		}
		mysql_free_result($result);

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
		while ($row = mysql_fetch_array($result)) {
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
		mysql_free_result($result);

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

	switch($row['statut']){
		case "0minirezo":
			$image = "<img src='img_pack/bonhomme-noir.gif' alt='Admin' border='0'>";
			break;
		case "1comite":
			if ($connect_statut == '0minirezo' AND !($row['pass'] AND $row['login']))
				$image = "<img src='img_pack/bonhomme-rouge.gif' alt='Sans acc&egrave;s' border='0'>";
			else
				$image = "<img src='img_pack/bonhomme-bleu.gif' alt='R&eacute;dacteur' border='0'>";
			break;
		case "5poubelle":
			$image = "<img src='img_pack/supprimer.gif' alt='Effac&eacute;' border='0'>";
			break;
		case "nouveau":
		default:
			$image = '';
			break;
	}
	if ($image && $connect_statut=="0minirezo")
		$image = "<A HREF='auteurs_edit.php3?id_auteur=".$row['id_auteur']."'>$image</a>";

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
		while ($row = mysql_fetch_array($result)) {
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
		mysql_free_result($result);

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
	global $connect_id_auteur;
	global $connect_activer_messagerie;	
	global $mots_cles_forums;


	$activer_messagerie = lire_meta("activer_messagerie");
	
	$compteur_forum++; 

	$nb_forum[$compteur_forum] = mysql_num_rows($request);
	$i[$compteur_forum] = 1;
 	while($row = mysql_fetch_array($request)) {
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
					icone ("Supprimer ce message", "articles_forum.php3?id_article=$id_article&supp_forum=$id_forum&debut=$debut", "forum-interne-24.gif", "supprimer.gif", "right");
				}
				else {
					echo "<br><font color='red'><b>MESSAGE SUPPRIM&Eacute; $ip</b></font>";
					if ($id_auteur) {
						echo " - <a href='auteurs_edit.php3?id_auteur=$id_auteur'>Voir cet auteur</A>";
					}
				}
				if ($statut == "prop" OR $statut == "off") {
					icone ("Valider ce message", "articles_forum.php3?id_article=$id_article&valid_forum=$id_forum&debut=$debut", "forum-interne-24.gif", "creer.gif", "right");
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
				echo "<b><a href=\"$url\">R&eacute;pondre &agrave; ce message</a></b></font>";
			}

			if ($mots_cles_forums == "oui"){
			
				$query_mots = "SELECT * FROM spip_mots AS mots, spip_mots_forum AS lien WHERE lien.id_forum = '$id_forum' AND lien.id_mot = mots.id_mot";
				$result_mots = spip_query($query_mots);
				
				while ($row_mots = mysql_fetch_array($result_mots)) {
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
	mysql_free_result($request);
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

	$nom_site_spip = entites_html(lire_meta("nom_site"));
	$titre = textebrut(typo($titre));

	if (!$nom_site_spip) $nom_site_spip="SPIP";
	if (!$charset = lire_meta('charset')) $charset = 'iso-8859-1';

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
<body text="#000000" bgcolor="#e4e4e4" background="img_pack/degrade.jpg" link="<?php echo $couleur_lien; ?>" vlink="<?php echo $couleur_lien_off; ?>" alink="<?php echo $couleur_lien_off ?>"  topmargin="0" leftmargin="0" marginwidth="0" marginheight="0">
<?php
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
	global $id_auteur, $connect_id_auteur;

	debut_onglet();
	
	if ($rubrique == "statistiques"){
		onglet("&Eacute;volution des visites", "statistiques_visites.php3", "evolution", $onglet, "statistiques-24.gif");
		onglet("R&eacute;partition par rubriques", "statistiques.php3", "repartition", $onglet, "rubrique-24.gif");
//		onglet("Par articles", "statistiques_articles.php3", "recents", $onglet, "article-24.gif");
		$activer_statistiques_ref = lire_meta("activer_statistiques_ref");
		if ($activer_statistiques_ref != "non")	onglet("Origine des visites", "statistiques_referers.php3", "referers", $onglet, "referers-24.gif");
	}
	
	if ($rubrique == "administration"){
		onglet("Sauvegarder/restaurer la base", "admin_tech.php3", "sauver", $onglet, "base-24.gif");
		onglet("Vider le cache", "admin_vider.php3", "vider", $onglet, "cache-24.gif");
		onglet("Effacer la base", "admin_effacer.php3", "effacer", $onglet, "supprimer.gif");
	}
	
	if ($rubrique == "auteur"){
		$activer_messagerie = lire_meta("activer_messagerie");
		$activer_imessage = lire_meta("activer_imessage");
		
		onglet("L'auteur", "auteurs_edit.php3?id_auteur=$id_auteur", "auteur", $onglet, "redacteurs-24.gif");
		onglet("Informations personnelles", "auteur_infos.php3?id_auteur=$id_auteur", "infos", $onglet, "fiche-perso-24.gif");
		if ($activer_messagerie!="non" AND $connect_id_auteur == $id_auteur){
			onglet("Messagerie", "auteur_messagerie.php3?id_auteur=$id_auteur", "messagerie", $onglet, "messagerie-24.gif");
		}
		//onglet("Donn&eacute;es de connexion", "auteur_connexion.php3?id_auteur=$id_auteur", "connexion", $onglet, "base-24.gif");
	}

	if ($rubrique == "configuration"){
		onglet("Caract&eacute;ristiques principales", "configuration.php3", "config", $onglet, "racine-site-24.gif");
		onglet("Contenu de votre site", "config-contenu.php3", "contenu", $onglet, "secteur-24.gif");
		onglet("Fonctionnalit&eacute;s de SPIP", "config-fonctions.php3", "fonctions", $onglet, "statistiques-24.gif");
	}
	
	if ($rubrique == "suivi_forum"){
		onglet("Tous les messages", "controle_forum.php3", "tous", $onglet);
		onglet("Messages sans texte", "controle_forum.php3?controle_sans=oui", "sans", $onglet);
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



function icone($texte, $lien, $fond, $fonction="", $align=""){
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

	echo "\n<table cellpadding=0 cellspacing=0 border=0 $aligner width=$largeur class=\"iconeoff\" onMouseOver=\"changeclass(this,'iconeon');\" onMouseOut=\"changeclass(this,'iconeoff');\" onClick=\"document.location='$lien'\">";
	echo "<tr><td background='' align='center' valign='middle' width=$largeur height=$hauteur>";
	echo "\n<table cellpadding=0 cellspacing=0 border=0>";
	if ($spip_display != 1){	
		echo "<tr><td background='' align='center'>";
		if ($fonction != "rien.gif"){
			echo "\n<table cellpadding=0 cellspacing=0 border=0><tr><td background='img_pack/$fond'>";
			echo "<a href='$lien'><img src='img_pack/$fonction'$alt$title width='24' height='24' border='0'></a>";
			echo "</td></tr></table>\n";
		}
		else {
			echo "\n<table cellpadding=0 cellspacing=0 border=0><tr><td background=''>";
			echo "<a href='$lien'><img src='img_pack/$fond'$alt$title width='24' height='24' border='0'></a>";
			echo "</td></tr></table>\n";
		}
		echo "</td></tr>";
	}
	echo "<tr><td background=''>";
	echo "<img src='img_pack/rien.gif' width=$largeur height=1>";
	echo "</td></tr>";
	if ($spip_display != 3){
		echo "<tr><td background='' align='center'>";
		echo "<a href='$lien' class='icone'><font face='Verdana,Arial,Helvetica,sans-serif' size='1' color='black'><b>$texte</b></font></a>";
		echo "</td></tr>";
	}
	echo "</table>";
	echo "</td></tr>";
	echo "</table>";
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
	echo "<td background='img_pack/tirets-separation.gif' width='2'>";
	echo "<img src='img_pack/rien.gif' alt='' width=2 height=2>";
	echo "</td>";
	if ($spip_ecran == "large") {
		echo "<td width=10><img src='img_pack/rien.gif' border=0 width=10 height=1></td>";
	}
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
	$clean_link->delVar('set_options');
	$clean_link->delVar('set_couleur');
	$clean_link->delVar('set_disp');
	$clean_link->delVar('set_ecran');
	
	if (strlen($adresse_site)<10) $adresse_site="../";

	debut_html($titre);

	$ctitre = "Changer la couleur de l'interface";
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
	echo lien_change_var ($clean_link, 'set_disp', 1, '1,0,18,15', "Afficher uniquement le texte");
	echo lien_change_var ($clean_link, 'set_disp', 2, '19,0,40,15', "Afficher les icones et le texte");
	echo lien_change_var ($clean_link, 'set_disp', 3, '41,0,59,15', "Afficher uniquement les icones");
	echo "\n</map>";
	
	// Icones principales
	echo "<table cellpadding='0' style='background-image: url(img_pack/rayures-fines.gif);' width='100%'><tr width='100%'><td width='100%' align='center'>";
	echo "<table cellpadding='0' background='' width='$largeur'><tr width='$largeur'>";
	echo "<tr width='$largeur'>";
		icone_bandeau_principal ("&Agrave; suivre", "index.php3", "asuivre-48.gif", "asuivre", $rubrique);
		icone_bandeau_principal ("&Eacute;dition du site", "naviguer.php3", "documents-48.gif", "documents", $rubrique);
		if ($options == "avancees") {
			icone_bandeau_principal ("Auteurs", "auteurs.php3", "redacteurs-48.gif", "redacteurs", $rubrique);
		} else {
			icone_bandeau_principal ("Informations personnelles", "auteurs_edit.php3?id_auteur=$connect_id_auteur", "fiche-perso-48.gif", "redacteurs", $rubrique);
		}
		if ($options == "avancees") {
			if ($connect_statut == "0minirezo") 
				icone_bandeau_principal ("Forums et p&eacute;titions", "forum.php3", "messagerie-48.gif", "messagerie", $rubrique);
			else
				icone_bandeau_principal ("Forum interne", "forum.php3", "messagerie-48.gif", "messagerie", $rubrique);
		}
	if ($connect_statut == '0minirezo' and $connect_toutes_rubriques){
	bandeau_barre_verticale();
		icone_bandeau_principal ("Administration du site", "configuration.php3", "administration-48.gif", "administration", $rubrique);
	}
	else if ($connect_statut == '0minirezo' and !$connect_toutes_rubriques and lire_meta("activer_statistiques") != 'non'){
	bandeau_barre_verticale();
		icone_bandeau_principal ("Statistiques du site", "statistiques_visites.php3", "administration-48.gif", "administration", $rubrique);
	}
	echo "<td background='' width='100%'>   </td>";
	echo "<td align='center'><font size=1>";
		echo "<img src='img_pack/choix-layout.gif' alt='' vspace=3 border=0 usemap='#map_layout'>";
	echo "</font></td>";
		icone_bandeau_principal ("Aide en ligne", "javascript:window.open('aide_index.php3', 'aide_spip', 'scrollbars=yes,resizable=yes,width=740,height=580'); void(0);", "aide-48.gif", "vide", "", "aide_index.php3");
		icone_bandeau_principal ("Visiter le site", "$adresse_site", "visiter-48.gif");
	echo "</tr></table>";
	echo "</td></tr></table>";


	// Icones secondaires
	echo "<table cellpadding='0' bgcolor='white' style='border-bottom: solid 1px black; border-top: solid 1px #333333;' width='100%'><tr width='100%'><td width='100%' align='center'>";

	echo "<table cellpadding='0' background='' width='$largeur'><tr width='$largeur'>";

	if ($rubrique == "asuivre"){
		icone_bandeau_secondaire ("&Agrave; suivre", "index.php3", "asuivre-24.gif", "asuivre", $sous_rubrique);
		icone_bandeau_secondaire ("Tout le site", "articles_tous.php3", "tout-site-24.gif", "tout-site", $sous_rubrique);
		if ($options == "avancees") {
			bandeau_barre_verticale();
			if ($activer_messagerie != 'non' AND $connect_activer_messagerie != 'non')
				icone_bandeau_secondaire ("Messagerie personnelle", "messagerie.php3", "messagerie-24.gif", "messagerie", $sous_rubrique);
			icone_bandeau_secondaire ("Calendrier", "calendrier.php3", "calendrier-24.gif", "calendrier", $sous_rubrique);
		}
	}
	else if ($rubrique == "documents"){
		icone_bandeau_secondaire ("Rubriques", "naviguer.php3", "rubrique-24.gif", "rubriques", $sous_rubrique);
		
		$nombre_articles = mysql_num_rows(spip_query("SELECT art.id_article FROM spip_articles AS art, spip_auteurs_articles AS lien WHERE lien.id_auteur = '$connect_id_auteur' AND art.id_article = lien.id_article"));
		if ($nombre_articles > 0) {
			icone_bandeau_secondaire ("Articles", "articles_page.php3", "article-24.gif", "articles", $sous_rubrique);
		}

		$activer_breves=lire_meta("activer_breves");
		if ($activer_breves != "non"){
			icone_bandeau_secondaire ("Br&egrave;ves", "breves.php3", "breve-24.gif", "breves", $sous_rubrique);
		}

		if ($options == "avancees"){
			$articles_mots = lire_meta('articles_mots');
			if ($articles_mots != "non") {
				icone_bandeau_secondaire ("Mots-cl&eacute;s", "mots_tous.php3", "mot-cle-24.gif", "mots", $sous_rubrique);
			}

			$activer_sites = lire_meta('activer_sites');
			if ($activer_sites<>'non')
				icone_bandeau_secondaire ("Sites r&eacute;f&eacute;renc&eacute;s", "sites_tous.php3", "site-24.gif", "sites", $sous_rubrique);

			if (@mysql_num_rows(spip_query("SELECT * FROM spip_documents_rubriques LIMIT 0,1")) > 0) {
				icone_bandeau_secondaire ("Documents", "documents_liste.php3", "doc-24.gif", "documents", $sous_rubrique);
			}
		}
	}
	else if ($rubrique == "redacteurs"){
		if ($options == "avancees")
			icone_bandeau_secondaire ("Les auteurs", "auteurs.php3", "redacteurs-24.gif", "redacteurs", $sous_rubrique);

		icone_bandeau_secondaire ("Informations personnelles", "auteurs_edit.php3?id_auteur=$connect_id_auteur", "fiche-perso-24.gif", "perso", $sous_rubrique);
	}
	else if ($rubrique == "messagerie"){
		icone_bandeau_secondaire ("Forum interne", "forum.php3", "forum-interne-24.gif", "forum-interne", $sous_rubrique);
		if ($connect_statut == "0minirezo"){
			icone_bandeau_secondaire ("Forum des administrateurs", "forum_admin.php3", "forum-admin-24.gif", "forum-admin", $sous_rubrique);
			bandeau_barre_verticale();
			icone_bandeau_secondaire ("Suivre/g&eacute;rer les forums", "controle_forum.php3", "suivi-forum-24.gif", "forum-controle", $sous_rubrique);
			icone_bandeau_secondaire ("Suivre/g&eacute;rer les p&eacute;titions", "controle_petition.php3", "petition-24.gif", "suivi-petition", $sous_rubrique);
		}
	}
	else if ($rubrique == "administration"){
		if ($connect_toutes_rubriques) {
			icone_bandeau_secondaire ("Configuration du site", "configuration.php3", "administration-24.gif", "configuration", $sous_rubrique);
		}
		if (lire_meta("activer_statistiques") != 'non')
			icone_bandeau_secondaire ("Statistiques des visites", "statistiques_visites.php3", "statistiques-24.gif", "statistiques", $sous_rubrique);
		if ($connect_toutes_rubriques && $options == "avancees") {
			icone_bandeau_secondaire ("Maintenance du site", "admin_tech.php3", "base-24.gif", "base", $sous_rubrique);
		}
	}

	if ($options == "avancees") {
		global $recherche;
		if ($recherche == '' AND $spip_display != 2)
			$recherche_aff = 'Rechercher';
		else
			$recherche_aff = $recherche;
		bandeau_barre_verticale();
		echo "<td width='5'><img src='img_pack/rien.gif' width=5></td>";
		echo "<td>";
		echo "<form method='get' style='margin: 0px;' action='recherche.php3'>";
		if ($spip_display == "2")
			echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=1><b>Rechercher&nbsp;:</b></font><br>";
		echo '<input type="text" size="18" value="'.$recherche_aff.'" name="recherche" class="spip_recherche">';
		echo "</form>";
		echo "</td>";
	}


	echo "<td width='100%'>   </td>";

	if ($auth_can_disconnect) {
		echo "<td width='5'>&nbsp;</td>";
		icone_bandeau_secondaire ("Se d&eacute;connecter", "../spip_cookie.php3?logout=$connect_login", "deconnecter-24.gif", "", $sous_rubrique, "deconnect");
	}

	echo "</tr></table>";
	echo "</td></tr></table>";

		
	// Bandeau
	echo "\n<table cellpadding='0' bgcolor='$couleur_foncee' style='border-bottom: solid 1px white; border-top: solid 1px #666666;' width='100%'><tr width='100%'><td width='100%' align='center'>";
	echo "<table cellpadding='0' background='' width='$largeur'><tr width='$largeur'><td>";
		if ($activer_messagerie != 'non' AND $connect_activer_messagerie != 'non') {
			echo "<font face='arial,helvetica,sans-serif' size=1><b>";
			$result_messages = spip_query("SELECT * FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE lien.id_auteur=$connect_id_auteur AND vu='non' AND statut='publie' AND type='normal' AND lien.id_message=messages.id_message");
			$total_messages = @mysql_num_rows($result_messages);
			if ($total_messages == 1) {
				while($row = @mysql_fetch_array($result_messages)) {
					$ze_message=$row['id_message'];
					echo "<a href='message.php3?id_message=$ze_message'><font color='$couleur_claire'><b>VOUS AVEZ UN NOUVEAU MESSAGE</b></font></a>";
				}
			}
			if ($total_messages > 1) echo "<a href='messagerie.php3'><font color='$couleur_claire'>VOUS AVEZ $total_messages NOUVEAUX MESSAGES</font></a>";
			$result_messages = spip_query("SELECT messages.* FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE lien.id_auteur='$connect_id_auteur' AND messages.statut='publie' AND lien.id_message=messages.id_message AND messages.rv='oui' AND messages.date_heure>DATE_SUB(NOW(),INTERVAL 1 DAY) GROUP BY messages.id_message");
			$total_messages = @mysql_num_rows($result_messages);
			
			if ($total_messages == 1) {
				while ($row = @mysql_fetch_array($result_messages)) {
					$ze_message = $row['id_message'];
					echo " | <a href='message.php3?id_message=$ze_message'><font color='white'>UN RENDEZ-VOUS</font></a> ";
				}
			}
			if ($total_messages > 1) echo " | <a href='calendrier.php3'><font color='white'>$total_messages RENDEZ-VOUS</font></a> ";
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
				href='". $lien->getUrl() ."' class='icone'><font color='black'>Interface
				simplifi&eacute;e</font></a></span>";
			echo " <span class = 'fondo'><b>interface compl&egrave;te</b></span>";

		}
		else {
			$lien = $clean_link;
			$lien->addVar('set_options', 'avancees');
			echo "<span class='fondgrison2'><b>Interface
				simplifi&eacute;e</b></span> <span class='fondgris'
				onMouseOver=\"changeclass(this,'fondgrison2')\"
				onMouseOut=\"changeclass(this,'fondgris')\"><a
				href='". $lien->getUrl() ."' class='icone'><font color='black'>interface
				compl&egrave;te</font></a></span>";
		}

	echo "</font>";
	echo "</td>";
	echo "<td align='center' align='right'>";
	$lien = $clean_link;
			
	if ($spip_ecran == "large") {
		$lien->addVar('set_ecran', 'etroit');
		echo "<a href='". $lien->getUrl() ."'><img src='img_pack/set-ecran.gif' title='Petit &eacute;cran' alt='Petit &eacute;cran' width='23' height='19' border='0'></a>";
	}
	else {
		$lien->addVar('set_ecran', 'large');
		echo "<a href='". $lien->getUrl() ."'><img src='img_pack/set-ecran.gif' title='Grand &eacute;cran' alt='Grand &eacute;cran' width='23' height='19' border='0'></a>";
	}
	echo "</td>";

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
	echo "\n<div style='width: 100%; border-top: 1px solid #aaaaaa; border-left: 1px solid #aaaaaa; border-right: 1px solid white; border-bottom: 1px solid white; padding: 0px;'>";
	echo "\n<div style='width: 100%; border: 1px dashed #666666; padding: 10px; background-color:#e4e4e4;'>";
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


	// zap sessions si bonjour
	if ($GLOBALS['bonjour'] == "oui" || $GLOBALS['secu'] == 'oui') {
		$securite = $GLOBALS['prefs']['securite'];
		$zappees = zap_sessions($GLOBALS['connect_id_auteur'], $securite == 'strict');

		if ($zappees && $GLOBALS['bonjour'] == "oui" && ($securite == 'strict' || $options == 'avancees')) {
			debut_cadre_enfonce("warning-24.gif");
			echo "<font size=2 face='verdana,arial,helvetica,sans-serif'>";
			echo propre("<b>Avertissement de s&eacute;curit&eacute;</b>");

			echo "<p>";
			echo propre("<img align='right' src='img_pack/deconnecter-24.gif'>" .
				"Lorsque vous aurez fini de travailler dans l'espace priv&eacute;, " .
				"pensez &agrave; vous d&eacute;connecter en cliquant sur le bouton ".
				"ci-dessus.");
			echo "\n<p>";

			echo propre("Pour plus d'informations, vous pouvez afficher les informations de s&eacute;curit&eacute;.");
			echo "<p>";


			if ($securite == 'strict') $niveau = "(s&eacute;curit&eacute; stricte)";
			else $niveau = " (s&eacute;curit&eacute; normale)";
			icone_horizontale("Afficher les r&eacute;glages de s&eacute;curit&eacute; $niveau", "index.php3?secu=oui", "base-24.gif", "");
			fin_cadre_enfonce();
		}
	}


	if (!$flag_3_colonnes) {
		if ($changer_config!="oui"){
			$activer_messagerie=lire_meta("activer_messagerie");
			$activer_imessage=lire_meta("activer_imessage");
		}
	
		if ($activer_messagerie!="non" AND $connect_activer_messagerie!="non"){
			if ($activer_imessage != "non" AND ($connect_activer_imessage != "non" OR $connect_statut == "0minirezo")) {
				$query2 = "SELECT id_auteur, nom FROM spip_auteurs WHERE id_auteur!=$connect_id_auteur AND imessage!='non' AND messagerie!='non' AND en_ligne>DATE_SUB(NOW(),INTERVAL 5 MINUTE)";
				$result_auteurs = spip_query($query2);
				$nb_connectes = mysql_num_rows($result_auteurs);
			}
	
			$flag_cadre = (($nb_connectes > 0) OR $rubrique == "messagerie");
			if ($flag_cadre) debut_cadre_relief("messagerie-24.gif");
			if ($rubrique == "messagerie") {
				echo "<a href='message_edit.php3?new=oui&type=normal'><img src='img_pack/m_envoi.gif' alt='' width='14' height='7' border='0'>";
				echo "<font color='#169249' face='Verdana,Arial,Helvetica,sans-serif' size=1><b>&nbsp;NOUVEAU MESSAGE</b></font></a>";
				echo "\n<br><a href='message_edit.php3?new=oui&type=pb'><img src='img_pack/m_envoi_bleu.gif' alt='' width='14' height='7' border='0'>";
				echo "<font color='#044476' face='Verdana,Arial,Helvetica,sans-serif' size=1><b>&nbsp;NOUVEAU PENSE-B&Ecirc;TE</b></font></a>";
				if ($connect_statut == "0minirezo") {
					echo "\n<br><a href='message_edit.php3?new=oui&type=affich'><img src='img_pack/m_envoi_jaune.gif' alt='' width='14' height='7' border='0'>";
					echo "<font color='#ff9900' face='Verdana,Arial,Helvetica,sans-serif' size=1><b>&nbsp;NOUVELLE ANNONCE</b></font></a>";
				}
			}
			
			if ($flag_cadre) {
				echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=2>";
				if ($nb_connectes > 0) {
					if ($options == "avancees" AND $rubrique == "messagerie") echo "<p>";
					echo "<b>Actuellement en ligne&nbsp;:</b>";
					while ($row = mysql_fetch_array($result_auteurs)) {
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
// Presentation de l'interface privee, marge de droite
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
				$nb_connectes = mysql_num_rows($result_auteurs);
			}

			$flag_cadre = ($nb_connectes > 0 OR $rubrique == "messagerie");
			if ($flag_cadre) debut_cadre_relief("messagerie-24.gif");
			if ($rubrique == "messagerie") {
				echo "<a href='message_edit.php3?new=oui&type=normal'><img src='img_pack/m_envoi.gif' alt='' width='14' height='7' border='0'>";
				echo "<font color='#169249' face='Verdana,Arial,Helvetica,sans-serif' size=1><b>&nbsp;NOUVEAU MESSAGE</b></font></a>";
				echo "\n<br><a href='message_edit.php3?new=oui&type=pb'><img src='img_pack/m_envoi_bleu.gif' alt='' width='14' height='7' border='0'>";
				echo "<font color='#044476' face='Verdana,Arial,Helvetica,sans-serif' size=1><b>&nbsp;NOUVEAU PENSE-B&Ecirc;TE</b></font></a>";
				if ($connect_statut == "0minirezo") {
					echo "\n<br><a href='message_edit.php3?new=oui&type=affich'><img src='img_pack/m_envoi_jaune.gif' alt='' width='14' height='7' border='0'>";
					echo "<font color='#ff9900' face='Verdana,Arial,Helvetica,sans-serif' size=1><b>&nbsp;NOUVELLE ANNONCE</b></font></a>";
				}
			}
		
			if ($flag_cadre) {
				echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=2>";
				if ($nb_connectes > 0) {
					if ($options == "avancees" AND $rubrique == "messagerie") echo "<p>";
					echo "<b>Actuellement en ligne&nbsp;:</b>";
					while ($row = mysql_fetch_array($result_auteurs)) {
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
	global $connect_id_auteur, $clean_link;
	global $flag_3_colonnes, $flag_centre_large;

	if ($options == "avancees") {
		if (!$deja_colonne_droite) creer_colonne_droite($rubrique);
		// liste des articles bloques
		$query = "SELECT id_article, titre FROM spip_articles WHERE auteur_modif = '$connect_id_auteur' AND id_rubrique > 0 AND date_modif > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
		$result = spip_query($query);
		$num_articles_ouverts = mysql_num_rows($result);
		if ($num_articles_ouverts) {
			echo "<p>";
			debut_cadre_enfonce('warning-24.gif');
			echo "<font face='Verdana,Arial,Helvetica,sans-serif' size=2>";
	
			if ($num_articles_ouverts == 1)
				echo typo("Vous avez r&eacute;cemment ouvert cet article; les autres r&eacute;dacteurs sont invit&eacute;s &agrave; ne pas le modifier ");
			else
				echo typo("Vous avez r&eacute;cemment ouvert les articles suivants; les autres r&eacute;dacteurs sont invit&eacute;s &agrave; ne pas les modifier ");
			echo typo("avant une heure.").aide("artmodif");
			while ($row = @mysql_fetch_array($result)) {
				$ze_article = $row['id_article'];
				$ze_titre = typo($row['titre']);
				echo "<div><b><a href='articles.php3?id_article=$ze_article'>$ze_titre</a></b>";
				// ne pas proposer de debloquer si c'est l'article en cours d'edition
				if ($ze_article != $GLOBALS['id_article_bloque']) {
					$lien = $clean_link;
					$lien->addVar('debloquer_article', $ze_article);
					echo " <font size=1>[<a href='". $lien->getUrl() ."'>lib&eacute;rer</a>]</font>";
				}
				echo "</div>";
			}
	
			fin_cadre_enfonce();
		}
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

	// zap sessions si bonjour
	if ($GLOBALS['bonjour'] == "oui" || $GLOBALS['secu'] == 'oui') {
		$securite = $GLOBALS['prefs']['securite'];
		$zappees = zap_sessions($GLOBALS['connect_id_auteur'], $securite == 'strict');

		if ($zappees && $GLOBALS['bonjour'] == "oui" && ($securite == 'strict' || $options == 'avancees')) {
		}
		else if ($GLOBALS['secu'] == 'oui') {
			debut_cadre_enfonce();
			if ($securite == 'strict')
				gros_titre("Type de connexion&nbsp;: s&eacute;curit&eacute; stricte");
			else
				gros_titre("Type de connexion&nbsp;: s&eacute;curit&eacute; normale");

			echo "<p>";

			$link = $GLOBALS['clean_link'];
			$link->addVar('secu', 'oui');

			if ($securite == 'strict') {
				$link = $GLOBALS['clean_link'];
				$link->addVar('securite', 'normal');

				echo propre("Vous &ecirc;tes en mode de s&eacute;curit&eacute; &laquo;stricte&raquo;. ".
					"Les connexions multiples y sont interdites. ".
					"Cela veut dire que {{vous ne pouvez pas faire les choses suivantes}}:\n".
					"- {vous connecter avec plusieurs navigateurs diff&eacute;rents en m&ecirc;me temps} (ou depuis plusieurs machines diff&eacute;rentes);\n".
					"- {utiliser le m&ecirc;me identifiant pour plusieurs personnes diff&eacute;rentes} ".
					"(utile en cas d'auteur collectif).\n\n\n".
					"Pour utiliser les possibilit&eacute;s &eacute;voqu&eacute;es ci-dessus, ".
					"vous devez repasser en mode de s&eacute;curit&eacute; normal.".
					"\n\nSi vos connexions sont fr&eacute;quemment interrompues sans raison apparente, repassez en mode normal.");
				echo "<p>Pour plus de pr&eacute;cisions n'h&eacute;sitez pas &agrave; consulter l'aide en ligne.".aide('deconnect');

				echo "<p><div align='right'>";
				echo $link->getForm('POST');
				echo "<input type='submit' class='fondo' name='submit' value='Passer en s&eacute;curit&eacute; normale'>\n";
				echo "</form></div>\n";
			}
			else {
				$link = $GLOBALS['clean_link'];
				$link->addVar('securite', 'strict');

				echo propre("Vous &ecirc;tes en mode de s&eacute;curit&eacute; &laquo;normale&raquo;. ".
					"{{Vous pouvez faire les choses suivantes :}}\n".
					"- {vous connecter avec plusieurs navigateurs diff&eacute;rents en m&ecirc;me temps} (ou depuis plusieurs machines diff&eacute;rentes);\n".
					"- {utiliser le m&ecirc;me identifiant pour plusieurs personnes diff&eacute;rentes} ".
					"(utile en cas d'auteur collectif).\n\n\n".
					"En contrepartie, {{la s&eacute;curit&eacute; n'est pas maximale.}} ".
					"Si vous n'utilisez pas les possibilit&eacute;s mentionn&eacute;es ci-dessus et ".
					"que vous &ecirc;tes soucieux de votre s&eacute;curit&eacute;, ".
					"vous pouvez passer &agrave; un mode de connexion plus strict.\n\n\n\n");

				echo "<p>Pour plus de pr&eacute;cisions n'h&eacute;sitez pas &agrave; consulter l'aide en ligne ".aide('deconnect');
				echo "<p><div align='right'>";
				echo $link->getForm('POST');
				echo "<input type='submit' class='fondo' name='submit' value='Passer en s&eacute;curit&eacute; stricte'>\n";
				echo "</form></div>\n";
			}
			fin_cadre_enfonce();
		}
	}
}


//
// Presentation de l'interface privee, fin de page et flush()
//

function fin_html() {

	echo "</font>";

	// rejouer le cookie de session en mode parano
	if ($GLOBALS['spip_session'] && $GLOBALS['prefs']['securite'] == 'strict') {
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
?>
<div align='right'><font face="Verdana,Arial,Helvetica,sans-serif" size='2'>
<a href='http://www.uzine.net/spip'>SPIP <?php echo $spip_version_affichee; ?></a>
est un logiciel libre distribu&eacute; <a href='copyright_fr.html'>sous licence GPL.</a>

<?php
if (ereg("jimmac", $credits))
	echo "<br>Les icones de l'interface sont de <a href='http://jimmac.musichall.cz/'>Jakub 'Jimmac' Steiner</a>.";
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
function afficher_parents($collection){
	global $parents, $couleur_foncee;
	global $coll;
	$parents=ereg_replace("(~+)","\\1~",$parents);
	if ($collection!=0){	
		$query2="SELECT * FROM spip_rubriques WHERE id_rubrique=\"$collection\"";
		$result2=spip_query($query2);

		while($row=mysql_fetch_array($result2)){
			$id_rubrique = $row['id_rubrique'];
			$id_parent = $row['id_parent'];
			$titre = $row['titre'];
			
			if (acces_restreint_rubrique($id_rubrique)) {
				$parents="~ <IMG SRC='img_pack/triangle-anim.gif' WIDTH=16 HEIGHT=14 BORDER=0> <FONT SIZE=3 FACE='Verdana,Arial,Helvetica,sans-serif'><a href='naviguer.php3?coll=$id_rubrique'><font color='$couleur_foncee'>$titre</font></a></FONT><BR>\n$parents";
			}
			else {
				if ($id_parent == "0"){
					$parents="~ <IMG SRC='img_pack/secteur-24.gif' alt='' WIDTH=24 HEIGHT=24 BORDER=0 align='middle'> <FONT SIZE=3 FACE='Verdana,Arial,Helvetica,sans-serif'><a href='naviguer.php3?coll=$id_rubrique'><font color='$couleur_foncee'>$titre</font></a></FONT><BR>\n$parents";
				} else {
					$parents="~ <IMG SRC='img_pack/rubrique-24.gif' alt='' WIDTH=24 HEIGHT=24 BORDER=0 align='middle'> <FONT SIZE=3 FACE='Verdana,Arial,Helvetica,sans-serif'><a href='naviguer.php3?coll=$id_rubrique'><font color='$couleur_foncee'>$titre</font></a></FONT><BR>\n$parents";
				}
			}
		}
	afficher_parents($id_parent);
	}
}




//
// Presentation des pages d'installation et d'erreurs
//

function install_debut_html($titre="Installation du syst&egrave;me de publication...", $onload='') {
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
