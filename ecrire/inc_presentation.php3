<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_PRESENTATION")) return;
define("_ECRIRE_INC_PRESENTATION", "1");

//
// initialisations globales de presentation (beurk!)
//


//
// Aide
//
function aide ($aide) {
	return " <font size='1'>[<b><a href=\"javascript:window.open('aide_index.php3?aide=$aide', 'aide_spip', 'scrollbars=yes,resizable=yes,width=700'); void(0);\">AIDE</a></b>]</font>";
}



//
// affiche un bouton imessage
//
function bouton_imessage($destinataire, $row = '') {
	// si on passe "force" au lieu de $row, on affiche l'icone sans verification
	global $connect_id_auteur;

	$url = "message_edit.php3?";

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
	$url .= "dest=$destinataire&";
	$url .= "new=oui&type=normal";
	
	$texte_bouton = "<IMG SRC='img_pack/m_envoi.gif' WIDTH='14' HEIGHT='7' BORDER='0' alt='-'>";
	return "<a href='$url'>$texte_bouton</a>";
}

//
// un cadre en relief
//
function debut_cadre_relief($icone='', $return = false, $fonction=''){
	global $spip_display;
	if ($spip_display != 1){	

		if (strlen($icone)<3) $icone = "rien.gif";
			$retour_aff.= "<TABLE CELLPADDING=0 CELLSPACING=0 BORDER=0 WIDTH=\"100%\">";
			$retour_aff.= "<tr>";
			$retour_aff.= "<td width='5'><img src='img_pack/rond-hg-24.gif' alt='/' width='5' height='24'></td>";
			$retour_aff.= "<td background='img_pack/rond-h-24.gif'>";
			if (strlen($fonction)>3) {
				$retour_aff.= "<table cellpadding=0 cellspacing=0 border=0><tr><td background='img_pack/$icone'><img src='img_pack/$fonction' alt='\' width='24' height='24'></td></tr></table>";
			}
			else {
				$retour_aff.= "<img src='img_pack/$icone' alt='\' width='24' height='24'>";
			}
			$retour_aff.= "</td>";
			$retour_aff.= "<td width='5'><img src='img_pack/rond-hd-24.gif' alt='/' width='5' height='24'></td>";
			$retour_aff.= "</tr>";
	
		$retour_aff.= "<TR>";
		$retour_aff.= "<td background='img_pack/rond-g.gif' width='5'><img src='img_pack/rien.gif' alt='/' width='5' height='5'></td>";
		$retour_aff.= "<TD WIDTH=\"100%\">";
		$retour_aff.= "<TABLE CELLPADDING=3 CELLSPACING=0 BORDER=0 WIDTH=\"100%\"><TR><TD BGCOLOR='#ffffff' WIDTH=\"100%\">";
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

		$retour_aff.= "</TD></TR></TABLE>";
		$retour_aff.= "</TD>";
		$retour_aff.= "<td background='img_pack/rond-d.gif' width='5'><img src='img_pack/rien.gif' alt='\' width='5' height='5'></td>";
		$retour_aff.= "<tr>";
		$retour_aff.= "<td width='5'><img src='img_pack/rond-bg.gif' alt='\' width='5' height='5'></td>";
		$retour_aff.= "<td background='img_pack/rond-b.gif'><img src='img_pack/rien.gif' alt='-' width='5' height='5'></td>";
		$retour_aff.= "<td width='5'><img src='img_pack/rond-bd.gif' alt='/' width='5' height='5'></td>";
		$retour_aff.= "</tr>";
		$retour_aff.= "<tr><td></td><td bgcolor='#bbbbbb'><img src='img_pack/rien.gif' alt='\' width='5' height='1'></td></tr>";
		$retour_aff.= "<tr><td><img src='img_pack/rien.gif' alt='' width='5' height='4'></td></tr>";
		$retour_aff.= "</table>";

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
		if (strlen($icone)<3) $icone = "rien.gif";
			$retour_aff.= "<TABLE CELLPADDING=0 CELLSPACING=0 BORDER=0 WIDTH=\"100%\">";
			$retour_aff.= "<tr>";
			$retour_aff.= "<td width='5'><img src='img_pack/cadre-hg.gif' alt='/' width='5' height='24'></td>";
			$retour_aff.= "<td background='img_pack/cadre-h.gif'>";
			if (strlen($fonction)>3) {
				$retour_aff.= "<table cellpadding=0 cellspacing=0 border=0><tr><td background='img_pack/$icone'><img src='img_pack/$fonction' alt='' width='24' height='24'></td></tr></table>";
			}
			else {
				$retour_aff.= "<img src='img_pack/$icone' alt='' width='24' height='24'>";
			}
			$retour_aff.= "</td>";
			$retour_aff.= "<td width='5'><img src='img_pack/cadre-hd.gif' alt='\' width='5' height='24'></td>";
			$retour_aff.= "</tr>";
	
		$retour_aff.= "<TR>";
		$retour_aff.= "<td background='img_pack/cadre-g.gif' width='5'><img src='img_pack/rien.gif' alt='' width='5' height='5'></td>";
		$retour_aff.= "<TD WIDTH=\"100%\" bgcolor='#e0e0e0' background=''>";
		$retour_aff.= "<TABLE CELLPADDING=3 CELLSPACING=0 BORDER=0 WIDTH=\"100%\"><TR><TD WIDTH=\"100%\">";
	}
	else {
		$retour_aff = "<p><div style=\"border: 1px solid #333333; background-color: #e0e0e0;\"><div style=\"padding: 5px; left-right: 1px solid #999999; border-top: 1px solid #999999;\">";
	}
	
	if ($return) return $retour_aff;
	else echo $retour_aff;
}

function fin_cadre_enfonce($return = false){
	global $spip_display;
	if ($spip_display != 1){	
		$retour_aff.= "</TD></TR></TABLE>";
		$retour_aff.= "</TD>";
		$retour_aff.= "<td background='img_pack/cadre-d.gif' width='5'><img src='img_pack/rien.gif' alt='' width='5' height='5'></td>";
		$retour_aff.= "<tr>";
		$retour_aff.= "<td width='5'><img src='img_pack/cadre-bg.gif' alt='\' width='5' height='5'></td>";
		$retour_aff.= "<td background='img_pack/cadre-b.gif'><img src='img_pack/rien.gif' alt='' width='5' height='5'></td>";
		$retour_aff.= "<td width='5'><img src='img_pack/cadre-bd.gif' alt='/' width='5' height='5'></td>";
		$retour_aff.= "</tr>";
		$retour_aff.= "<tr><td><img src='img_pack/rien.gif' alt='' width='5' height='4'></td></tr>";
		$retour_aff.= "</table>";
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
	echo "<p><table cellpadding='5' cellspacing='0' border='1' width='100%' style='border-left: 1px solid $couleur_foncee; border-top: 1px solid $couleur_foncee; border-bottom: 1px solid white; border-bottom: 1px solid white' background=''>";
	echo "<tr><td bgcolor='$couleur_claire' width='100%'>";
	echo "<font face='Verdana,Arial,Helvetica,sans-serif' size='2' color='#333333'>";
}

function fin_boite_info() {
	echo "</font></td></tr></table>";
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
// Une icone avec lien et legende (texte en-dessous)
//

function afficher_icone_texte($url, $texte, $image, $largeur, $hauteur, $align = "") {
	echo "<table";
	if ($align) echo " align='$align'";
	echo " cellspacing='0' cellpadding='10'>";
	echo "<tr><td width='".floor($largeur * 2.0)."' align='center'>\n";
	echo "\t<a class='icone' href=\"$url\"><font face='Verdana, Arial, Helvetica, sans-serif' size='1'>\n";
	echo "\t<img src='$image' border='0' alt='o' width='$largeur' height='$hauteur'><br>\n";
	echo "\t<b>$texte</b></font></a>\n";
	echo "\t</td></tr></table>\n";
}


//
// Une icone avec lien et info-bulle (pas de texte en-dessous)
//

function afficher_icone($url, $texte, $image, $largeur, $hauteur, $align = "") {
	echo "<a class='icone' href=\"$url\">\n";
	$texte = attribut_html($texte);
	echo "\t<img src='$image' border='0' width='$largeur' height='$hauteur' alt=\"$texte\" title=\"$texte\"";
	if ($align) echo " align='$align'";
	echo "></a>\n";
}




//
// Fonctions d'affichage
//

function tableau($texte,$lien,$image){
	echo "<td width=15>&nbsp;</td>\n";
	echo "<td width=80 valign='top' align='center'><a href='$lien'><img src='$image' border='0' alt='o'></a><br><font size=1 face='arial,helvetica' color='#e86519'><b>$texte</b></font></td>";
}

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
			if ($deb_aff + 1 >= $deb AND $deb_aff + 1 <= $fin) {
				$texte .= "[<B>$deb-$fin</B>] ";
			}
			else {
				$link = new Link;
				$link->addTmpVar($tmp_var, strval($deb - 1));
				$texte .= "[<A HREF=\"".$link->getUrl()."\">$deb-$fin</A>] ";
			}
		}
		$texte .= "</td>\n";
		$texte .= "<td background=\"\" class=\"arial2\" colspan=\"1\" align=\"right\" valign=\"top\">";
		if ($deb_aff == -1) {
			$texte .= "[<B>Tout afficher</B>]";
		} else {
			$link = new Link;
			$link->addTmpVar($tmp_var, -1);
			$texte .= "[<A HREF=\"".$link->getUrl()."\">Tout afficher</A>]";
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
	global $connect_id_auteur;

	$activer_messagerie = lire_meta("activer_messagerie");
	$activer_statistiques = lire_meta("activer_statistiques");

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

			$query_petition = "SELECT COUNT(*) AS cnt FROM spip_petitions WHERE id_article=$id_article";
			$row_petition = mysql_fetch_array(spip_query($query_petition));
			$petition = ($row_petition['cnt'] > 0);

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

			$s = "<A HREF=\"articles.php3?id_article=$id_article\">";
			if ($statut=='publie') $puce = 'verte';
			else if ($statut == 'prepa') $puce = 'blanche';
			else if ($statut == 'prop') $puce = 'orange';
			else if ($statut == 'refuse') $puce = 'rouge';
			else if ($statut == 'poubelle') $puce = 'poubelle';
			if (acces_restreint_rubrique($id_rubrique))
				$puce = "puce-$puce-anim.gif";
			else
				$puce = "puce-$puce.gif";

			$s .= "<img src=\"img_pack/$puce\" alt='-' width=\"13\" height=\"14\" border=\"0\">";
			$s .= "&nbsp;&nbsp;".typo($titre)."</A>";
			if ($petition) $s .= " <Font size=1 color='red'>P&Eacute;TITION</font>";

			$vals[] = $s;
		
			if ($afficher_auteurs) $vals[] = $les_auteurs;

			$s = affdate($date);
			if ($activer_statistiques != "non" AND $afficher_visites AND $visites > 0) {
				$s .= "<br><font size=\"1\">($visites&nbsp;visites)</font>";
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

			$s = "<a href=\"breves_voir.php3?id_breve=$id_breve\">";
			$s .= "<img src='img_pack/$puce.gif' alt='o' width=\"8\" height=\"9\" border=\"0\"> ";
			$s .= typo($titre);
			$s .= "</A>";
			$vals[] = $s;

			$s = "<div align=\"right\"><font size='2'>";
			if ($statut == "prop") $s .= "[<font color=\"red\">&agrave; valider</font>]";
			else $s .= affdate($date_heure);
			$s .= "</font></div>";
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
			$s .= "<img src=\"$puce\" alt=\">\" border=\"0\"> ";
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
			for ($count=2;$count<=$compteur_forum AND $count<11;$count++){
				$fond[$count]='img_pack/rien.gif';
				if ($i[$count]!=$nb_forum[$count]){
					$fond[$count]='img_pack/forum-vert.gif';
				}
				$fleche='img_pack/rien.gif';
				if ($count==$compteur_forum){		
					$fleche='img_pack/forum-droite.gif';
				}
				echo "<td width=10 valign='top' background=$fond[$count]><img src=$fleche alt='o' width=10 height=13 border=0></td>\n";
			}

			echo "\n<td width=100% bgcolor='#eeeeee' valign='top'>";

			// Si refuse, cadre rouge
			if ($statut=="off") {
				echo "<table width=100% cellpadding=2 cellspacing=0 border=0><tr><td bgcolor='#FF0000'>";
			}
			// Si propose, cadre jaune
			else if ($statut=="prop") {
				echo "<table width=100% cellpadding=2 cellspacing=0 border=0><tr><td bgcolor='#FFFF00'>";
			}

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

			echo "</font>";
			echo "</td></tr></table>";
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
	global $couleur_foncee;
	global $couleur_claire;
	$nom_site_spip = htmlspecialchars(lire_meta("nom_site"));
	$titre = textebrut(typo($titre));

	if ($nom_site_spip == "") $nom_site_spip="SPIP";
	
	?>
	<html>
	<head>
	<title>[<?php echo $nom_site_spip; ?>] <?php echo $titre; ?></TITLE>
	<meta http-equiv="Expires" content="0">
	<meta http-equiv="cache-control" content="no-cache,no-store">
	<meta http-equiv="pragma" content="no-cache">
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<style><!--
	.forml {width: 100%; background-color: #E4E4E4; background-position: center bottom; float: none; color: #000000}
	.formo {width: 100%; background-color: <?php echo $couleur_claire; ?>; background-position: center bottom; float: none;}
		.sanscadre {padding: 4px; margin: 0px; }
		.aveccadre {cursor: pointer; padding: 3px; margin: 0px;  border-left: solid 1px <?php echo $couleur_claire; ?>; border-top: solid 1px <?php echo $couleur_claire; ?>; border-right: solid 1px #000000; border-bottom: solid 1px #000000;}
		.fondgris {padding: 4px; margin: 1px;}
		.fondgrison {cursor: pointer; padding: 3px; margin: 1px; border: 1px dashed #999999; background-color: #e4e4e4;}
		.fondgrison2 {cursor: pointer; padding: 3px; margin: 1px; border: 1px dashed #999999; background-color: white;}
	.fondl {background-color: <?php echo $couleur_claire; ?>; background-position: center bottom; float: none; color: #000000}
	.fondo {background-color: <?php echo $couleur_foncee; ?>; background-position: center bottom; float: none; color: #FFFFFF}
	.fondf {background-color: #FFFFFF; border-style: solid ; border-width: 1; border-color: #E86519; color: #E86519}
	.profondeur {border-right-color:white; border-top-color:#666666; border-left-color:#666666; border-bottom-color:white; border-style:solid}
	.hauteur {border-right-color:#666666; border-top-color:white; border-left-color:white; border-bottom-color:#666666; border-style:solid}
	label {cursor: pointer;}
	.arial1 { font-family: Arial, Helvetica, sans-serif; font-size: 10px; }
	.arial2 { font-family: Arial, Helvetica, sans-serif; font-size: 12px; }


	.reliefblanc {background-image: url(img_pack/barre-blanc.gif)}
	.reliefgris {background-image: url(img_pack/barre-noir.gif)}
	.iconeoff {padding: 3px; margin: 1px; border: 1px dashed #aaaaaa; background-color: #f0f0f0}
	.iconeimpoff {padding: 3px; margin: 1px; border: 1px dashed <? echo $couleur_foncee; ?>; background-color: #e4e4e4}
	.iconeon {cursor: pointer; padding: 3px; margin: 1px;  border-right: solid 1px white; border-bottom: solid 1px white; border-left: solid 1px #666666; border-top: solid 1px #666666; background-color: #eeeeee;}

	a { text-decoration: none; }
	a:hover { color:#FF9900; text-decoration: underline; }
	a.icone { text-decoration: none; }
	a.icone:hover { text-decoration: none; }

	a.spip_in  {background-color:#eeeeee;}
	a.spip_out {}
	a.spip_note {}
	.spip_recherche {width : 100px; font-size: 9px;}
	.spip_cadre { 
		width : 100%;
		background-color: #FFFFFF; 
		padding: 5px; 
	}
	
	.boutonlien {
		font-family: Verdana,Arial,Helvetica,sans-serif;
		font-weight: bold;
		font-size: 9px;
	}
	a.boutonlien:hover {color:#454545; text-decoration: none;}
	a.boutonlien {color:#808080; text-decoration: none;}

	h3.spip {
		font-family: Verdana,Arial,Helvetica,sans-serif;
		font-weight: bold;
		font-size: 115%;
		text-align: center;
	}
	.spip_documents{
		font-family: Verdana,Arial,Helvetica,sans-serif;
		font-size : 70%;
	}
	table.spip {
	}
	table.spip tr.row_first {
		background-color: #FCF4D0;
	}
	table.spip tr.row_odd {
		background-color: #C0C0C0;
	}
	table.spip tr.row_even {
		background-color: #F0F0F0;
	}
	table.spip td {
		padding: 1px;
		text-align: left;
		vertical-align: center;
	}

--></style>
<?php
afficher_script_layer();
?>
<script language="JavaScript">
<!--
	function MM_preloadImages() { //v3.0
		var d=document; if(d.images){ if(!d.MM_p) d.MM_p=new Array();
		var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)
		if (a[i].indexOf("#")!=0){ d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}
	}

	function changeclass(objet, myClass)
	{ 
	  objet.className = myClass;
	}
//-->
</script>
</head>
<body bgcolor="#E4E4E4" background="img_pack/degrade.jpg" text="#000000" link="#E86519" vlink="#6E003A" alink="#FF9900"  topmargin="0" leftmargin="0" marginwidth="0" marginheight="0">

<?php

}


// Fonctions onglets

function onglet_relief_inter(){
	echo "<td background='img_pack/barre-noir.gif'><img src='img_pack/rien.gif' alt='o' width='1' height='40'></td>";
}

function debut_onglet(){
	echo "\n";
	echo "<p><table cellpadding=0 cellspacing=0 border=0>";
	echo "<tr><td>";
	echo "<img src='img_pack/barre-g.gif' alt='<' width='16' height='40'>";
	echo "</td>";
}

function fin_onglet(){
	onglet_relief_inter();
	echo "<td>";
	echo "<img src='img_pack/barre-d.gif' alt='>' width='16' height='40'>";
	echo "</td></tr>";
	echo "</table>";
}

function onglet($texte, $lien, $onglet_ref, $onglet, $icone=""){

	if ($onglet_ref == $onglet){
		onglet_relief_inter();
		if (strlen($icone)>3){
		echo "\n<td background='img_pack/barre-noir.gif' height=40 valign='top'>";
			echo "&nbsp; <img src='img_pack/$icone' border=0>";
		echo "</td>";
		}
		echo "\n<td background='img_pack/barre-noir.gif' height=40 valign='middle'>";
		echo "&nbsp; <font face='verdana,arial,helvetica,sans-serif' size=2 color='black'><b>$texte</b></font> &nbsp;";
		echo "</td>";
	}
	else {
		onglet_relief_inter();
		echo "\n<td class='reliefblanc' onMouseOver=\"changeclass(this,'reliefgris');\" onMouseOut=\"changeclass(this,'reliefblanc');\" onClick=\"document.location='$lien'\" height=40 valign='middle'>";
		if (strlen($icone)>3){
			echo "&nbsp; <img src='img_pack/$icone'  alt='o' border=0 align='middle'>";
		}
		echo "&nbsp; <a href='$lien' class='icone'><font face='verdana,arial,helvetica,sans-serif' size=2 color='#666666'><b>$texte</b></font></a> &nbsp;";
		echo "</td>";
	}
}


function barre_onglets($rubrique, $onglet){
	global $id_auteur, $connect_id_auteur;

	debut_onglet();
	
	if ($rubrique == "statistiques"){
		onglet("R&eacute;partition des entr&eacute;es", "statistiques.php3", "repartition", $onglet, "statistiques-24.gif");
		onglet("Articles r&eacute;cents", "statistiques_recents.php3", "recents", $onglet, "article-24.gif");
		onglet("Tous les articles", "statistiques_tous.php3", "tous", $onglet, "article-24.gif");
	}
	
	if ($rubrique == "administration"){
		onglet("Sauvegarder/restaurer la base", "admin_tech.php3", "sauver", $onglet, "base-24.gif");
		onglet("Vider le cache", "admin_vider.php3", "vider", $onglet, "cache-24.gif");
		onglet("Effacer la base", "admin_effacer.php3", "effacer", $onglet, "supprimer.gif");
	}
	
	if ($rubrique == "auteur"){
		$activer_messagerie=lire_meta("activer_messagerie");
		$activer_imessage=lire_meta("activer_imessage");
		
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
		onglet("Messages sans texte", "controle_forum_sans.php3", "sans", $onglet);
	}

	fin_onglet();
}


function icone_bandeau_principal($texte, $lien, $fond, $rubrique_icone = "vide", $rubrique = ""){
	global $spip_display;
	
	if ($spip_display == 1){
		$hauteur = 20;
		$largeur = 80;
	}
	else if ($spip_display == 3){
		$hauteur = 50;
		$largeur = 52;
		$title = " title = \"$texte\" ";
	}
	else {
		$hauteur = 70;
		$largeur = 80;
	}

	if (eregi("^javascript:",$lien)){
		$java_lien = substr($lien, 11, strlen($lien));
		$onClick = " onClick=\"$java_lien\"";
	}
	else {
		$onClick = " onClick=\"document.location='$lien'\"";
	}

	if ($rubrique_icone == $rubrique){
		echo "\n<table cellpadding=0 cellspacing=0 border=0 class=\"fondgrison\" $onClick>";
		echo "<tr><td background=''>";
		echo "<img src='img_pack/rien.gif' alt='o' width=$largeur height=1>";
		echo "</td></tr>";
		echo "<tr><td background='' align='center' width='$largeur' height='$hauteur'>";
		if ($spip_display != 1){	
			echo "<a href=\"$lien\"><img src='img_pack/$fond' $title border='0' alt=' '></a><br>";
		}
		if ($spip_display != 3){
			echo "<a href=\"$lien\" class='icone'><font face='verdana,arial,helvetica,sans-serif' size='2' color='black'><b>$texte</b></font></a>";
		}
		echo "</td></tr></table>";
	} 
	else {
		echo "\n<table cellpadding=0 cellspacing=0 border=0 class=\"fondgris\" onMouseOver=\"changeclass(this,'fondgrison2');\" onMouseOut=\"changeclass(this,'fondgris');\" $onClick>";
		echo "<tr><td background=''>";
		echo "<img src='img_pack/rien.gif' alt='o' width=$largeur height=1>";
		echo "</td></tr>";
		echo "<tr><td background='' align='center' width='$largeur' height='$hauteur'>";
		if ($spip_display != 1){	
			echo "<a href=\"$lien\"><img src='img_pack/$fond' $title border='0' alt=' '></a><br>";
		}
		if ($spip_display != 3){
			echo "<a href=\"$lien\" class='icone'><font face='verdana,arial,helvetica,sans-serif' size='2' color='black'><b>$texte</b></font></a>";
		}
		echo "</td></tr></table>";
	}
}


function icone_bandeau_secondaire($texte, $lien, $fond, $rubrique_icone = "vide", $rubrique){
	global $spip_display;

	if ($spip_display == 1){
		$hauteur = 20;
		$largeur = 80;
	}
	else if ($spip_display == 3){
		$hauteur = 26;
		$largeur = 28;
		$title = " title = \"$texte\" ";
	}
	else {
		$hauteur = 70;
		$largeur = 80;
	}

	if ($rubrique_icone == $rubrique){
		echo "\n<td background='' align='center' width='$largeur' class=\"fondgrison\" onClick=\"document.location='$lien'\">";
		echo "\n<table cellpadding=0 cellspacing=0 border=0>";
		if ($spip_display != 1){	
			echo "<tr><td background='' align='center'>";
			echo "<a href='$lien'><img src='img_pack/$fond' alt='o' $title width='24' height='24' border='0'></a>";
			echo "</td></tr>";
		}
		echo "<tr><td background=''>";
		echo "<img src='img_pack/rien.gif' alt='o' width=$largeur height=1>";
		echo "</td></tr>";
		echo "</table>";
		if ($spip_display != 3){
			echo "<a href='$lien' class='icone'><font face='verdana,arial,helvetica,sans-serif' size='1' color='black'><b>$texte</b></font></a>";
		}
		echo "</td>";
	}
	else {
		echo "\n<td background='' align='center' width='$largeur' class=\"fondgris\" onMouseOver=\"changeclass(this,'fondgrison2');\" onMouseOut=\"changeclass(this,'fondgris');\" onClick=\"document.location='$lien'\">";
		echo "\n<table cellpadding=0 cellspacing=0 border=0>";
		if ($spip_display != 1){	
			echo "<tr><td background='' align='center'>";
			echo "<a href='$lien'><img src='img_pack/$fond' alt='o' $title width='24' height='24' border='0'></a>";
			echo "</td></tr>";
		}
		echo "<tr><td background=''>";
		echo "<img src='img_pack/rien.gif' alt='o' width=$largeur height=1>";
		echo "</td></tr>";
		echo "</table>";
		if ($spip_display != 3){
			echo "<a href='$lien' class='icone'><font face='verdana,arial,helvetica,sans-serif' size='1' color='black'><b>$texte</b></font></a>";
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
	}
	else if ($spip_display == 3){
		$hauteur = 30;
		$largeur = 30;
		$title = " title = \"$texte\" ";
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
			echo "<a href='$lien'><img src='img_pack/$fonction' alt='o' $title width='24' height='24' border='0'></a>";
			echo "</td></tr></table>\n";
		}
		else {
			echo "\n<table cellpadding=0 cellspacing=0 border=0><tr><td background=''>";
			echo "<a href='$lien'><img src='img_pack/$fond' alt='o' $title width='24' height='24' border='0'></a>";
			echo "</td></tr></table>\n";
		}
		echo "</td></tr>";
	}
	echo "<tr><td background=''>";
	echo "<img src='img_pack/rien.gif' alt='o' width=$largeur height=1>";
	echo "</td></tr>";
	if ($spip_display != 3){
		echo "<tr><td background='' align='center'>";
		echo "<a href='$lien' class='icone'><font face='verdana,arial,helvetica,sans-serif' size='1' color='black'><b>$texte</b></font></a>";
		echo "</td></tr>";
	}
	echo "</table>";
	echo "</td></tr>";
	echo "</table>";
}

function icone_horizontale($texte, $lien, $fond, $fonction="", $important=false){
	global $spip_display, $couleur_claire, $couleur_foncee;
		
	if (strlen($fonction) < 3) $fonction = "rien.gif";

	$hauteur = 30;
	$largeur = "100%";

	if ($important)
		echo "\n<table cellpadding=0 cellspacing=0 border=0 width=$largeur class=\"iconeimpoff\" onMouseOver=\"changeclass(this,'iconeon');\" onMouseOut=\"changeclass(this,'iconeimpoff');\" onClick=\"document.location='$lien'\">";
	else
		echo "\n<table cellpadding=0 cellspacing=0 border=0 width=$largeur class=\"iconeoff\" onMouseOver=\"changeclass(this,'iconeon');\" onMouseOut=\"changeclass(this,'iconeoff');\" onClick=\"document.location='$lien'\">";

	echo "<tr>";
	
	echo "<td background='' align='left' valign='middle' width=$largeur height=$hauteur>";
	echo "\n<table cellpadding=0 cellspacing=0 border=0>";
	echo "<tr>";
		
	if ($spip_display != 1){	
		echo "<td background='' align='center'>";
		if ($fonction != "rien.gif"){
			echo "\n<table cellpadding=0 cellspacing=0 border=0><tr><td background='img_pack/$fond'>";
			echo "<a href='$lien'><img src='img_pack/$fonction' alt='o' $title width='24' height='24' border='0'></a>";
			echo "</td></tr></table>\n";
		}
		else {
			echo "\n<table cellpadding=0 cellspacing=0 border=0><tr><td background=''>";
			echo "<a href='$lien'><img src='img_pack/$fond' alt='o' $title width='24' height='24' border='0'></a>";
			echo "</td></tr></table>\n";
		}
		echo "</td>";
	}

		echo "<td background=''>";
		echo "<img src='img_pack/rien.gif' alt='o' width=5 height=1>";
		echo "</td>";

	echo "<td background='' align='left'>";
	echo "<a href='$lien' class='icone'><font face='verdana,arial,helvetica,sans-serif' size='1' color='#666666'><b>$texte</b></font></a>";
	echo "</td></tr>";

	echo "</table>";
	echo "</td></tr>";
	echo "</table>";
}


function bandeau_barre_verticale(){
	echo "<td background='img_pack/tirets-separation.gif' width='2'>";
	echo "<img src='img_pack/rien.gif' alt='o' width=2 height=2>";
	echo "</td>";
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
	global $REQUEST_URI;
	global $requete_fichier;
	$activer_messagerie = lire_meta("activer_messagerie");
	
	if (!$requete_fichier) {
		$requete_fichier = substr($REQUEST_URI, strrpos($REQUEST_URI, '/') + 1);
	}
	$lien = ereg_replace("\&set_options=(basiques|avancees)", "", $requete_fichier);
	$lien = ereg_replace("\&set_couleur=[0-9]", "", $lien);
	$lien = ereg_replace("\&set_disp=[0-9]", "", $lien);
	if (!ereg('\?', $lien)) $lien .= '?';

	
	if (strlen($adresse_site)<10) $adresse_site="../";

	debut_html($titre);
	
		echo "\n<map name='map_couleur'>";
		echo "\n<area shape='rect' href='$lien&set_couleur=6' alt='bleu' coords='0,0,10,10' title=\"Changer la couleur de l'interface\">";
		echo "\n<area shape='rect' href='$lien&set_couleur=1' alt='bleu' coords='12,0,22,10' title=\"Changer la couleur de l'interface\">";
		echo "\n<area shape='rect' href='$lien&set_couleur=2' alt='bleu' coords='24,0,34,10' title=\"Changer la couleur de l'interface\">";
		echo "\n<area shape='rect' href='$lien&set_couleur=3' alt='bleu' coords='36,0,46,10' title=\"Changer la couleur de l'interface\">";
		echo "\n<area shape='rect' href='$lien&set_couleur=4' alt='bleu' coords='48,0,58,10' title=\"Changer la couleur de l'interface\">";
		echo "\n<area shape='rect' href='$lien&set_couleur=5' alt='bleu' coords='60,0,70,10' title=\"Changer la couleur de l'interface\">";
		echo "\n<area shape='rect' href='$lien&set_couleur=7' alt='bleu' coords='0,11,10,21' title=\"Changer la couleur de l'interface\">";
		echo "\n<area shape='rect' href='$lien&set_couleur=8' alt='bleu' coords='12,11,22,21' title=\"Changer la couleur de l'interface\">";
		echo "\n<area shape='rect' href='$lien&set_couleur=9' alt='bleu' coords='24,11,34,21' title=\"Changer la couleur de l'interface\">";
		echo "\n<area shape='rect' href='$lien&set_couleur=10' alt='bleu' coords='36,11,46,21' title=\"Changer la couleur de l'interface\">";
		echo "\n<area shape='rect' href='$lien&set_couleur=11' alt='bleu' coords='48,11,58,21' title=\"Changer la couleur de l'interface\">";
		echo "\n<area shape='rect' href='$lien&set_couleur=12' alt='bleu' coords='60,11,70,21' title=\"Changer la couleur de l'interface\">";
		echo "\n</map>";

		echo "\n<map name='map_layout'>";
		echo "\n<area shape='rect' href='$lien&set_disp=1' alt='o' coords='0,0,20,15' title=\"Afficher uniquement le texte\">";
		echo "\n<area shape='rect' href='$lien&set_disp=2' alt='o' coords='19,0,40,15' title=\"Afficher les icones et le texte\">";
		echo "\n<area shape='rect' href='$lien&set_disp=3' alt='o' coords='41,0,59,15' title=\"Afficher uniquement les icones\">";
		echo "\n</map>";
	
	// Icones principales
	echo "<table cellpadding='0' style='background-image: url(img_pack/rayures-fines.gif) ; border-top: solid 1px white;' width='100%'><tr width='100%'><td width='100%' align='center'>";
	echo "<table cellpadding='0' background='' width='750'><tr width='750'>";
	echo "<td background=''>";
		icone_bandeau_principal ("&Agrave; suivre", "index.php3", "asuivre-48.gif", "asuivre", $rubrique);
	echo "</td>";
	echo "<td background=''>";
		icone_bandeau_principal ("Edition du site", "naviguer.php3", "documents-48.gif", "documents", $rubrique);
	echo "</td>";
	echo "<td background=''>";
	echo "</td>";
	echo "<td background=''>";
		icone_bandeau_principal ("Les r&eacute;dacteurs", "auteurs.php3?aff_art[]=1comite", "redacteurs-48.gif", "redacteurs", $rubrique);
	echo "</td>";
	echo "<td background=''>";
		icone_bandeau_principal ("Forums et messagerie", "forum.php3", "messagerie-48.gif", "messagerie", $rubrique);
	echo "</td>";
	if ($connect_statut == '0minirezo'){
	bandeau_barre_verticale();
		echo "<td background=''>";
			icone_bandeau_principal ("Administration du site", "statistiques.php3", "administration-48.gif", "administration", $rubrique);
		echo "</td>";
	}
	echo "<td background='' width='100%'>   </td>";
	echo "<td align='center'><font size=1>";
		echo "<img src='img_pack/choix-layout.gif' alt='o' vspace=3 border=0 usemap='#map_layout'>";
		//echo "<br><a href='$lien&set_disp=1'>Texte</a> | <a href='$lien&set_disp=2'>Icones+texte</a> | <a href='$lien&set_disp=3'>Icones</a>";
	echo "</font></td>";
	//bandeau_barre_verticale();
	echo "<td background=''>";
		icone_bandeau_principal ("Aide en ligne", "javascript:window.open('aide_index.php3', 'aide_spip', 'scrollbars=yes,resizable=yes,width=700'); void(0);", "aide-48.gif");
		//echo "<table cellpadding=0 cellspacing=0 border=0 class='fondgris' onMouseOver=\"changeclass(this,'fondgrison');\" onMouseOut=\"changeclass(this,'fondgris');\" onClick=\"window.open('aide_index.php3', 'aide_spip', 'scrollbars=yes,resizable=yes,width=700'); void(0);\"><tr><td background=''><img src='img_pack/rien.gif' width=52 height=1></td></tr><tr><td background='' align='center' width='52' height='50'><a href=\"javascript:window.open('aide_index.php3', 'aide_spip', 'scrollbars=yes,resizable=yes,width=700'); void(0);\"><img src='img_pack/aide-48.gif' alt='Aide en ligne'  title =\"Aide en ligne\"  border='0'></a><br></td></tr></table>";


	echo "</td>";
	echo "<td background=''>";
		icone_bandeau_principal ("Visiter le site", "$adresse_site", "visiter-48.gif");
	echo "</td>";
	echo "</tr></table>";
	echo "</td></tr></table>";


	// Icones secondaires
	echo "<table cellpadding='0' bgcolor='white' style='border-bottom: solid 1px black; border-top: solid 1px #333333;' width='100%'><tr width='100%'><td width='100%' align='center'>";

	echo "<table cellpadding='0' background='' width='750'><tr width='750'>";
		if ($rubrique == "asuivre"){
			icone_bandeau_secondaire ("&Agrave; suivre", "index.php3", "asuivre-24.gif", "asuivre", $sous_rubrique);
			icone_bandeau_secondaire ("Tout le site", "articles_tous.php3", "tout-site-24.gif", "tout-site", $sous_rubrique);
			icone_bandeau_secondaire ("Calendrier", "calendrier.php3", "calendrier-24.gif", "calendrier", $sous_rubrique);
		}
		else if ($rubrique == "documents"){
			icone_bandeau_secondaire ("Rubriques", "naviguer.php3", "rubrique-24.gif", "rubriques", $sous_rubrique);
			icone_bandeau_secondaire ("Articles", "articles_page.php3", "article-24.gif", "articles", $sous_rubrique);
			$activer_breves=lire_meta("activer_breves");
			if ($activer_breves != "non"){
				icone_bandeau_secondaire ("Br&egrave;ves", "breves.php3", "breve-24.gif", "breves", $sous_rubrique);
			}
			$articles_mots = lire_meta('articles_mots');
			if ($articles_mots != "non") {
				icone_bandeau_secondaire ("Mots-cl&eacute;s", "mots_tous.php3", "mot-cle-24.gif", "mots", $sous_rubrique);
			}
			icone_bandeau_secondaire ("Sites r&eacute;f&eacute;renc&eacute;s", "sites_tous.php3", "site-24.gif", "sites", $sous_rubrique);
		}
		else if ($rubrique == "redacteurs"){
			icone_bandeau_secondaire ("R&eacute;dacteurs", "auteurs.php3?aff_art[]=1comite", "redacteurs-24.gif", "redacteurs", $sous_rubrique);
			icone_bandeau_secondaire ("Administrateurs", "auteurs.php3?aff_art[]=0minirezo", "redacteurs-admin-24.gif", "administrateurs", $sous_rubrique);
			if ($connect_statut == "0minirezo"){
				bandeau_barre_verticale();
				icone_bandeau_secondaire ("&Agrave; la poubelle", "auteurs.php3?aff_art[]=5poubelle", "redacteurs-poubelle-24.gif", "redac-poubelle", $sous_rubrique);
			}
			bandeau_barre_verticale();
			icone_bandeau_secondaire ("Informations personnelles", "auteurs_edit.php3?id_auteur=$connect_id_auteur", "fiche-perso-24.gif", "perso", $sous_rubrique);
		}
		else if ($rubrique == "messagerie"){
			icone_bandeau_secondaire ("Forum interne", "forum.php3", "forum-interne-24.gif", "forum-interne", $sous_rubrique);
			if ($connect_statut == "0minirezo"){
				icone_bandeau_secondaire ("Forum des administrateurs", "forum_admin.php3", "forum-admin-24.gif", "forum-admin", $sous_rubrique);
				bandeau_barre_verticale();
				icone_bandeau_secondaire ("Suivre/g&eacute;rer les forums", "controle_forum.php3", "suivi-forum-24.gif", "forum-controle", $sous_rubrique);
				icone_bandeau_secondaire ("Suivre/g&eacute;rer les p&eacute;titions", "controle_petition.php3", "suivi-forum-24.gif", "suivi-petition", $sous_rubrique);
			}
			if ($activer_messagerie != 'non' AND $connect_activer_messagerie != 'non') {
				bandeau_barre_verticale();
				icone_bandeau_secondaire ("Messagerie interne", "messagerie.php3", "messagerie-24.gif", "messagerie", $sous_rubrique);
			}
		}
		else if ($rubrique == "administration"){
			icone_bandeau_secondaire ("Statistiques des visites", "statistiques.php3", "statistiques-24.gif", "statistiques", $sous_rubrique);
			if ($connect_toutes_rubriques) {
				icone_bandeau_secondaire ("Configuration du site", "configuration.php3", "administration-24.gif", "configuration", $sous_rubrique);
				icone_bandeau_secondaire ("Gestion de la base", "admin_tech.php3", "base-24.gif", "base", $sous_rubrique);
			}
		}
	echo "<td width='100%'>   </td>";
	echo "<td>";
	echo "<form method='get' style='margin: 0px;' action='recherche.php3'>";
	echo '<input type="text" size="18" value="Chercher" name="recherche" class="spip_recherche">';
	echo "</form>";
	echo "</td>";
	echo "</tr></table>";
	echo "</td></tr></table>";


		
	// Bandeau
	echo "\n<table cellpadding='0' bgcolor='$couleur_foncee' style='border-bottom: solid 1px white; border-top: solid 1px #666666;' width='100%'><tr width='100%'><td width='100%' align='center'>";
	echo "<table cellpadding='0' background='' width='750'><tr width='750'><td>";
		if ($activer_messagerie != 'non' AND $connect_activer_messagerie != 'non') {
			echo "<font face='arial,helvetica,sans-serif' size=1><b>";
			$result_messages = spip_query("SELECT * FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE lien.id_auteur=$connect_id_auteur AND vu='non' AND statut='publie' AND type='normal' AND lien.id_message=messages.id_message");
			$total_messages = @mysql_num_rows($result_messages);
			if ($total_messages == 1) {
				while($row = @mysql_fetch_array($result_messages)) {
					$ze_message=$row['id_message'];
					echo "<a href='message.php3?id_message=$ze_message'><font color='red'>VOUS AVEZ UN NOUVEAU MESSAGE</font></a>";
				}
			}
			if ($total_messages > 1) echo "<a href='messagerie.php3'><font color='white'>VOUS AVEZ $total_messages NOUVEAUX MESSAGES</font></a>";
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
	echo "<font size=1 face='verdana,arial,helvetica,sans-serif'>";
		global $options;
		if ($options == "avancees") echo "<span class='fondgris' onMouseOver=\"changeclass(this,'fondgrison2')\" onMouseOut=\"changeclass(this,'fondgris')\"><a href='$lien&set_options=basiques'><font color='black'>Interface simplifi&eacute;e</font></a></span> <font color='white'><b>interface compl&egrave;te</b></font>";
		else  echo "<b><font color='white'>Interface simplifi&eacute;e</font></b> <span class='fondgris' onMouseOver=\"changeclass(this,'fondgrison2')\" onMouseOut=\"changeclass(this,'fondgris')\"><a href='$lien&set_options=avancees'><font color='black'>interface compl&egrave;te</font></a></span>";
	echo "</font>";
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
	if (strlen($ze_logo) > 3) echo "<img src='img_pack/$ze_logo' alt='o' border=0 align='middle'> &nbsp; ";
	echo "<span style='border-bottom: 1px dashed $couleur_foncee;'><font size=5 face='verdana,arial,helvetica,sans-serif' color='$couleur_foncee'><b>";
	echo propre("$titre");
	echo "</b></font></span></div>\n";
}


//
// Cadre centre (haut de page)
//

function debut_grand_cadre(){
	echo "\n<br><br><table width=750 cellpadding=0 cellspacing=0 border=0>";
	echo "\n<tr>";
	echo "<td width=750>";
	echo "<font face='Georgia,Garamond,Times,serif' size=3>";

}

function fin_grand_cadre(){
	echo "\n</font></td></tr></table>";
}

// Cadre formulaires

function debut_cadre_formulaire(){
	echo "\n<div style='border-top: 1px solid #aaaaaa; border-left: 1px solid #aaaaaa; border-right: 1px solid white; border-bottom: 1px solid white; padding: 0px;'>";
	echo "\n<div style='border: 1px dashed #666666; padding: 10px; background-color:#e4e4e4;'>";
}

function fin_cadre_formulaire(){
	echo "</div>";
	echo "</div>\n";
}



//
// Debut de la colonne de gauche
//

function debut_gauche() {
	global $connect_statut, $cookie_admin;
	global $REQUEST_URI;
	global $options;
	global $requete_fichier;
	global $connect_id_auteur;

	if (!$requete_fichier) {
		$requete_fichier = substr($REQUEST_URI, strrpos($REQUEST_URI, '/') + 1);
	}
	$lien = $requete_fichier;
	if (!ereg('\?', $lien)) $lien .= '?';

	$lapage=$lien;
	if ($lapage=="?") $lapage="index.php3?";
	if (ereg("&",$lapage)) $lapage=substr($lapage,0,strpos($lapage,"&"));
	
	?>
	<br>

	<table width=750 cellpadding=0 cellspacing=0 border=0>

	<tr>
	<td width=200 valign="top">
	<font face='Georgia,Garamond,Times,serif' size=2>
	<?php
	
	
	// Afficher les auteurs recemment connectes
	
	global $changer_config;
	global $activer_messagerie;
	global $activer_imessage;
	global $connect_activer_messagerie;
	global $connect_activer_imessage;
	
	if ($changer_config!="oui"){
		$activer_messagerie=lire_meta("activer_messagerie");
		$activer_imessage=lire_meta("activer_imessage");
	}

	if ($activer_messagerie!="non" AND $connect_activer_messagerie!="non"){
	
		debut_cadre_relief("messagerie-24.gif");

		echo "<a href='message_edit.php3?new=oui&type=normal'><img src='img_pack/m_envoi.gif' alt='M>' width='14' height='7' border='0'>";
		echo "<font color='#169249' face='verdana,arial,helvetica,sans-serif' size=1><b>&nbsp;NOUVEAU MESSAGE</b></font></a>";
		echo "\n<br><a href='message_edit.php3?new=oui&type=pb'><img src='img_pack/m_envoi_bleu.gif' alt='M>' width='14' height='7' border='0'>";
		echo "<font color='#044476' face='verdana,arial,helvetica,sans-serif' size=1><b>&nbsp;NOUVEAU PENSE-B&Ecirc;TE</b></font></a>";

		if ($activer_imessage != "non" AND ($connect_activer_imessage != "non" OR $connect_statut == "0minirezo")) {
		 	$query2 = "SELECT * FROM spip_auteurs WHERE id_auteur!=$connect_id_auteur AND imessage!='non' AND messagerie!='non' AND en_ligne>DATE_SUB(NOW(),INTERVAL 5 MINUTE)";
			$result_auteurs = spip_query($query2);

			if (mysql_num_rows($result_auteurs) > 0) {
				echo "<font face='verdana,arial,helvetica,sans-serif' size=2>";
				echo "<p><b>Actuellement en ligne&nbsp;:</b>";
			
				while($row = mysql_fetch_array($result_auteurs)){
					$id_auteur = $row["id_auteur"];
					$nom_auteur = typo($row["nom"]);
					echo "<br>".bouton_imessage($id_auteur,$row)." $nom_auteur";
				}	
				echo "</font>";
			}
		}
		fin_cadre_relief();
	}	
}


//
// Presentation de l'interface privee, marge de droite
//

function debut_droite() {
	
	
	/*
	//
	// Boite de recherche
	//

	echo '<p><form method="get" action="recherche.php3">';
	debut_cadre_relief();
	echo '<font face="verdana,arial,helvetica,sans-serif" size="2">';
	echo 'Recherche sur les titres des articles et des br&egrave;ves&nbsp;:<br>';
	echo '<input type="text" size="18" name="recherche" class="spip_recherche">';
	echo "</font>\n";
	fin_cadre_relief();
	echo "</form>";
	*/
	
	?>
	<br></font>
	&nbsp;
	</td>
	<td width=50 rowspan=1>&nbsp;</td>
	<td width=500 valign="top" rowspan=2>
	<font face="Georgia,Garamond,Times,serif" size=3>
	<?php
}


//
// Presentation de l'interface privee, fin de page et flush()
//

function fin_html() {
	global $spip_version_affichee;
?>
<p align='right'><font face="Verdana, Arial, Helvetica, sans-serif" size='2'>
<a href='http://www.uzine.net/spip'>SPIP <?php echo $spip_version_affichee; ?></a>
est distribu&eacute; <a href='gpl.txt'>sous licence GPL</a>.</p>
</body></html>
<?php
	flush();
}


function fin_page() {

?>
</td></tr>
</table></center>
<?php
	fin_html();
}


//
// Afficher la hierarchie des rubriques
//
function afficher_parents($collection){
	global $parents;
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
				$parents="~ <IMG SRC='img_pack/triangle-anim.gif' WIDTH=16 HEIGHT=14 BORDER=0> <FONT SIZE=3 FACE='Verdana,Arial,Helvetica,sans-serif'><a href='naviguer.php3?coll=$id_rubrique'>$titre</a></FONT><BR>\n$parents";
			}
			else {
				if ($id_parent == "0"){
					$parents="~ <IMG SRC='img_pack/secteur-24.gif' alt='o' WIDTH=24 HEIGHT=24 BORDER=0 align='middle'> <FONT SIZE=3 FACE='Verdana,Arial,Helvetica,sans-serif'><a href='naviguer.php3?coll=$id_rubrique'>$titre</a></FONT><BR>\n$parents";
				} else {
					$parents="~ <IMG SRC='img_pack/rubrique-24.gif' alt='o' WIDTH=24 HEIGHT=24 BORDER=0 align='middle'> <FONT SIZE=3 FACE='Verdana,Arial,Helvetica,sans-serif'><a href='naviguer.php3?coll=$id_rubrique'>$titre</a></FONT><BR>\n$parents";
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
