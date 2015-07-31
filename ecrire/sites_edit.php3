<?

include ("inc.php3");

debut_page("Site r&eacute;f&eacute;r&eacute;nc&eacute;");
debut_gauche();
debut_droite();

$proposer_sites = lire_meta("proposer_sites");

function mySel($varaut,$variable) {
	$retour= " VALUE=\"$varaut\"";

	if ($variable==$varaut){
		$retour.= " SELECTED";
	}

	return $retour;
}

function premiere_rubrique(){
 	$query="SELECT * FROM spip_rubriques WHERE id_parent='0' ORDER BY titre LIMIT 0,1";
 	$result=mysql_query($query);

	while($row=mysql_fetch_array($result)){
		$my_rubrique=$row['id_rubrique'];
	}
	return $my_rubrique;

}

function enfant($leparent) {
	global $id_parent;
	global $id_rubrique;
	global $i;
	global $statut;
	
	$i++;
 	$query="SELECT * FROM spip_rubriques WHERE id_parent='$leparent' ORDER BY titre";
 	$result=mysql_query($query);

	while($row=mysql_fetch_array($result)){
		$my_rubrique=$row['id_rubrique'];
		$titre=typo($row['titre']);

		// si l'article est publie il faut etre admin pour avoir le menu
		// sinon le menu est present en entier (proposer un article)
		if ($statut != "publie" OR acces_rubrique($my_rubrique)) {
			$rubrique_acceptable = true;
		} else {
			$rubrique_acceptable = false;
		}

		$espace="";
		for ($count=0;$count<$i;$count++){$espace.="&nbsp;&nbsp;";}
		$espace .= "|";
		if ($i==1)
			$espace = "*";

		if ($rubrique_acceptable) {
			echo "<OPTION".mySel($my_rubrique,$id_rubrique).">$espace $titre\n";
		}
		enfant($my_rubrique);
	}
	$i=$i-1;
}

$proposer_sites = lire_meta("proposer_sites");

$query = "SELECT * FROM spip_syndic WHERE id_syndic='$id_syndic'";
$result = mysql_query($query);




if ($row = mysql_fetch_array($result)) {
	$id_syndic = $row["id_syndic"];
	$id_rubrique = $row["id_rubrique"];
	$nom_site = stripslashes($row["nom_site"]);
	$url_site = stripslashes($row["url_site"]);
	$url_syndic = stripslashes($row["url_syndic"]);
	$descriptif = stripslashes($row["descriptif"]);
	$syndication = $row["syndication"];
}
else {
	$syndication = 'non';
	$new = 'oui';
}
if (!$id_rubrique > 0) $id_rubrique = premiere_rubrique();


echo "<font size=4 face='verdana,arial,helvetica'>r&eacute;f&eacute;rencer le site : <b>$nom_site</b></font>".aide("reference");



if ($new == 'oui'){

	$proposer_sites = lire_meta("proposer_sites");
	if ($connect_statut == '0minirezo' OR $proposer_sites > 0) {
		echo "<table cellpadding=1 cellspacing=0 border=0 width='100%'><tr><td bgcolor='#FFFFFF'>";
		echo "<table cellpadding=5 cellspacing=0 border=0 width='100%'><tr bgcolor='#E4E4E4'><td bgcolor='#E4E4E4'>";

		$link = new Link('sites.php3');
		$link->addVar('id_rubrique', $id_rubrique);
		$link->addVar('new', 'oui');
		$link->addVar('redirect', $this_link->getUrl());
		$link->addVar('analyser_site', 'oui');
		echo $link->getForm();
		
		echo "<img src='IMG2/sites.gif' alt='' width='28' height='27' hspace='10' vspace='0' border='0' align='left'>";
		
		echo "<font face='arial,helvetica' size=2><b>R&eacute;f&eacute;rencement automatis&eacute; d'un site</b><br>Vous pouvez r&eacute;f&eacute;rencer rapidement un site Web en indiquant ci-dessous l'adresse URL d&eacute;sir&eacute;e, ou l'adresse de son fichier backend. SPIP va r&eacute;cup&eacute;rer automatiquement les informations concernant ce site (titre, description...).</font>";
		echo "<div align='right'><input type=\"text\" name=\"url\" value=\"http://\">";
		echo "<input type=\"submit\" name=\"submit\" value=\"Ajouter\" class='fondo'>";
		
		echo "</td></tr></table>";
		echo "</td></tr></table>";
		echo "</form>";
		
		echo "<p><b>Vous pouvez pr&eacute;f&eacute;rer ne pas utiliser cette fonction automatique, et indiquer vous-m&ecirc;me les &eacute;l&eacute;ments concernant ce site...</b>";
		
	}

}


$link = new Link($target);
$link->addVar('new');
$link->addVar('modifier_site', 'oui');
$link->addVar('syndication_old', $syndication);
echo $link->getForm('POST');

$nom_site = htmlspecialchars($nom_site);
$url_site = htmlspecialchars($url_site);
$url_syndic = htmlspecialchars($url_syndic);

echo "<b>Nom du site</b> [Obligatoire]<br>";
echo "<input type='text' class='formo' name='nom_site' value=\"$nom_site\" size='40'><p>";
if (strlen($url_site)<8) $url_site="http://";
echo "<b>Adresse du site</b> [Obligatoire]<br>";
echo "<input type='text' class='formo' name='url_site' value=\"$url_site\" size='40'><p>";

echo "<b>&Agrave; l'int&eacute;rieur de la rubrique&nbsp;:</b><br>\n";

echo "<select name='id_rubrique' class='forml' size=1>\n";
enfant(0);
echo "</select><p>\n";

echo "<b>Description du site</b><br>";
echo "<textarea name='descriptif' rows='8' class='formo' cols='40' wrap=soft>";
echo $descriptif;
echo "</textarea>\n";

$activer_syndic = lire_meta("activer_syndic");

echo "<input type='hidden' name='syndication_old' value=\"$syndication\">";

if ($activer_syndic != "non") {
	debut_boite_info();
	if ($syndication == "non") {
		echo "<INPUT TYPE='radio' NAME='syndication' VALUE='non' id='syndication_non' CHECKED>";
	}
	else {
		echo "<INPUT TYPE='radio' NAME='syndication' VALUE='non' id='syndication_non'>";
	}
	echo " <b><label for='syndication_non'>Pas de syndication</label></b><p>";

	if ($syndication == "non") {
		echo "<INPUT TYPE='radio' NAME='syndication' VALUE='oui' id='syndication_oui'>";
	}
	else {
		echo "<INPUT TYPE='radio' NAME='syndication' VALUE='oui' id='syndication_oui' CHECKED>";
	}
	echo " <b><label for='syndication_oui'>Syndication :</label></b>";
	echo aide("rubsyn");


	echo "<table cellpadding=0 cellspacing=0 border=0 width=100%><tr><td width=50>&nbsp;</td><td>";

	if (strlen($url_syndic) < 8) $url_syndic = "http://";
	echo "Adresse du fichier &laquo;&nbsp;backend&nbsp;&raquo; pour la syndication&nbsp;:";
	echo "<br>";
	echo "<INPUT TYPE='text' CLASS='formo' NAME='url_syndic' VALUE=\"$url_syndic\" SIZE='40'><P>";
	echo "<INPUT TYPE='hidden' NAME='old_syndic' VALUE=\"$url_syndic\"";
	echo "</td></tr></table>";

	fin_boite_info();
} 
else {
	echo "<INPUT TYPE='Hidden' NAME='syndication' VALUE=\"$syndication\">";
	echo "<INPUT TYPE='hidden' NAME='url_syndic' VALUE=\"$url_syndic\"";
}

echo "<P ALIGN='right'><INPUT TYPE='submit' NAME='Valider' VALUE='Valider' CLASS='fondo'  >";
echo "</FORM>";


fin_page();

?>