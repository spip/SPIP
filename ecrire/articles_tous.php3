<?php

include ("inc.php3");

if (count($aff_art) > 0) $aff_art = join(',', $aff_art);
else $aff_art = 'prop,publie';

debut_page("Tout le site", "asuivre", "tout-site");
debut_gauche();


echo "<form action='articles_tous.php3' method='get'>";
echo "<input type='hidden' name='liste_coll' value='$liste_coll'>";
echo "<input type='hidden' name='aff_art[]' value='x'>";

debut_boite_info();

echo "<FONT FACE='arial,helvetica,sans-serif'>";
echo "<B>Afficher les articles&nbsp;:</B><BR>";


if ($connect_statut == "0minirezo") {
	if (ereg('prepa', $aff_art)) {
		echo "<input type='checkbox' CHECKED name='aff_art[]' value='prepa' id='prepa'>";
	}
	else {
		echo "<input type='checkbox' name='aff_art[]' value='prepa' id='prepa'>";
	}
	echo " <label for='prepa'><img src='img_pack/puce-blanche.gif' alt='' width='9' height='9' border='0'>";
	echo "  en cours de r&eacute;daction</label><BR>";
}


if (ereg('prop', $aff_art)) {
	echo "<input type='checkbox' CHECKED name='aff_art[]' value='prop' id='prop'>";
}
else {
	echo "<input type='checkbox' name='aff_art[]' value='prop' id='prop'>";
}
echo " <label for='prop'><img src='img_pack/puce-orange.gif' alt='' width='9' height='9' border='0'>";
echo "  en attente de validation</label><BR>";

if (ereg('publie', $aff_art)) {
	echo "<input type='checkbox' CHECKED name='aff_art[]' value='publie' id='publie'>";
}
else {
	echo "<input type='checkbox' name='aff_art[]' value='publie' id='publie'>";
}
echo " <label for='publie'><img src='img_pack/puce-verte.gif' alt='' width='9' height='9' border='0'>";
echo "  publi&eacute;s en ligne</label><BR>";

if ($connect_statut == "0minirezo") {
	if (ereg("refuse",$aff_art)) {
		echo "<input type='checkbox' CHECKED name='aff_art[]' value='refuse' id='refuse'>";
	}
	else {
		echo "<input type='checkbox' name='aff_art[]' value='refuse' id='refuse'>";
	}
	echo " <label for='refuse'><img src='img_pack/puce-rouge.gif' alt='' width='9' height='9' border='0'>";
	echo "  refus&eacute;s</label><BR>";

	if (ereg('poubelle',$aff_art)) {
		echo "<input type='checkbox' CHECKED name='aff_art[]' value='poubelle' id='poubelle'>";
	}
	else {
		echo "<input type='checkbox' name='aff_art[]' value='poubelle' id='poubelle'>";
	}
	echo " <label for='poubelle'><img src='img_pack/puce-poubelle.gif' alt='' width='9' height='9' border='0'>";
	echo "  &agrave; la poubelle</label>";
}

echo "<div align='right'><INPUT TYPE='submit' NAME='Changer' CLASS='fondo' VALUE='Changer'></div>";
echo "</FONT>";
fin_boite_info();
echo "</form>";


debut_droite();


function enfants($id_parent, $decalage = 0) {
	global $deplier;
	global $liste_coll;
	global $coll_actives;
	global $aff_art;
	global $couleur_foncee, $couleur_claire;

	$query = "SELECT id_rubrique, titre, statut, date FROM spip_rubriques WHERE id_parent=$id_parent ORDER BY titre";
	$result = spip_query($query);

	while ($row = spip_fetch_array($result)) {
		$id_rubrique = $row['id_rubrique'];
		$titre = typo($row['titre']);
		$date = affdate($row['date']);
		$sucrer = '';
		$lien = '';
		
		

		//$flag_active = ereg("(^|,)$id_rubrique(\$|,)", $coll_actives);
		if (tester_rubrique_vide("$id_rubrique") ==  true) {
			$sucrer="[<A HREF='articles_tous.php3?liste_coll=$liste_coll&supp_rubrique=$id_rubrique'><font color='white'>supprimer</font></A>]";
		}
		$flag_liste = ereg("(^|,)$id_rubrique(\$|,)", $liste_coll);
		if ($flag_liste) {
			$lien = ereg_replace("(^|,)$id_rubrique(\$|,)", ',', $liste_coll);
			$lien = ereg_replace('^,+', '', $lien);
			$lien = ereg_replace(',+$', '', $lien);
		}
		else {
			if ($liste_coll) $lien = "$liste_coll,";
			$lien .= "$id_rubrique";
		}
		$lien = "articles_tous.php3?liste_coll=$lien";
		if ($deplier) $lien .= "&deplier=$deplier";
		$lien .= "&aff_art[]=$aff_art";
		if (($deplier == 'oui') ? !$flag_liste : $flag_liste) {
			if ($decalage) {
				echo "<TABLE BORDER=0 CELLPADDING=0 CELLSPACING=0><TR>";
				echo "<TD WIDTH=$decalage><IMG SRC='img_pack/rien.gif' BORDER=0 HEIGHT=1 WIDTH=$decalage></TD><TD WIDTH='100%'>";
			}
			$bandeau = "<A HREF='$lien'>";
			$bandeau .= "<img src='img_pack/triangle-bleu-bas.gif' alt='' width='14' height='14' border='0'></A>";
			$bandeau .= " <A HREF='naviguer.php3?coll=$id_rubrique'><FONT COLOR='white'>$titre</FONT></A> $sucrer";
			$requete = "SELECT id_article, titre, id_rubrique, statut, date FROM spip_articles WHERE id_rubrique=$id_rubrique AND FIND_IN_SET(statut,'$aff_art') ORDER BY date DESC";
			afficher_articles($bandeau, $requete, false, false, true, false);
			if ($decalage) {
				echo "</TD></TR></TABLE>";
			}
			enfants($id_rubrique, $decalage + 20);
		}
		else {
			if ($decalage) {
				echo "<TABLE BORDER=0 CELLPADDING=0 CELLSPACING=0><TR>";
				echo "<TD WIDTH=$decalage><IMG SRC='img_pack/rien.gif' BORDER=0 HEIGHT=1 WIDTH=$decalage></TD><TD WIDTH='100%'>";
			}
			echo "<TABLE CELLPADDING=3 CELLSPACING=1 BORDER=0 WIDTH=\"100%\">";
			echo "<TR><TD BGCOLOR='$couleur_foncee' WIDTH=\"100%\"><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'>";

			echo "<B><A HREF='$lien'><img src='img_pack/triangle-bleu.gif' alt='' width='14' height='14' border='0'></A> <A HREF='naviguer.php3?coll=$id_rubrique'><FONT COLOR='#FFFFFF'>$titre</FONT></A></B> $sucrer";
			echo "</FONT></TD></TR></TABLE>";
			if ($decalage) {
				echo "</TD></TR></TABLE>";
			}
		}
		if (!$id_parent) echo "<P>";
		echo "\n\n";
	}
	spip_free_result($result);
}


echo "<A HREF='articles_tous.php3?aff_art[]=$aff_art&deplier=oui'>Tout d&eacute;plier</A>";
echo " | <A HREF='articles_tous.php3?aff_art[]=$aff_art'>Tout replier</A>";
echo "<P>";

echo "<P>";

enfants(0);


fin_page();

?>

