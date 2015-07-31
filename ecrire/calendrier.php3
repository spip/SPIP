<?php

include ("inc.php3");


// transformer "1er" en "01"
function chiffrespar2($in)
{
	return substr('00'.ereg_replace('[^0-9]', '', $in), -2);
}


function numero_jour_semaine($lejour){
	switch($lejour){
	
		case "Sun": $retour=0; break;
		case "Mon": $retour= 1; break;
		case "Tue": $retour= 2; break;
		case "Wed": $retour= 3; break;
		case "Thu": $retour= 4; break;
		case "Fri": $retour= 5; break;
		case "Sat": $retour= 6; break;
	
	}	
	return $retour;
}


function afficher_mois($jour_today,$mois_today,$annee_today,$nom_mois){
	global $connect_id_auteur, $connect_statut;
	global $les_articles;
	global $les_breves;
	
	// calculer de nouveau la date du jour pour affichage en blanc
	$ce_jour=date("Y-m-d");

	$nom = mktime(1,1,1,$mois_today,1,$annee_today);
	$jour_semaine = numero_jour_semaine(date("D",$nom));
	
	
	if ($jour_semaine==0) $jour_semaine=7;
	
	echo "<TABLE border=0 CELLSPACING=1 CELLPADDING=3 WIDTH=700>";

		$mois_suiv=$mois_today+1;
		$annee_suiv=$annee_today;
		$mois_prec=$mois_today-1;
		$annee_prec=$annee_today;

	if ($mois_today==1){
		$mois_prec=12;
		$annee_prec=$annee_today-1;
	}
	if ($mois_today==12){
		$mois_suiv=1;
		$annee_suiv=$annee_today+1;
	}

	// articles du jour
	$query="SELECT * FROM spip_articles WHERE statut='publie' AND date >='$annee_today-$mois_today-0' AND date < DATE_ADD('$annee_today-$mois_today-1', INTERVAL 1 MONTH) ORDER BY date";
	$result=mysql_query($query);
	while($row=mysql_fetch_array($result)){
		$id_article=$row[0];
		$titre=typo($row['titre']);
		$lejour=jour($row['date']);
		$lemois = mois($row['date']);		

		$lejour=ereg_replace("1er","1",$lejour);
		if ($lemois == $mois_today) $les_articles["$lejour"].="<BR><A HREF='articles.php3?id_article=$id_article'><img src='IMG2/puce-verte.gif' width='7' height='7' border='0'> $titre</A>";
	}

	// breves du jour
	$query="SELECT * FROM spip_breves WHERE statut='publie' AND date_heure >='$annee_today-$mois_today-0' AND date_heure < DATE_ADD('$annee_today-$mois_today-1', INTERVAL 1 MONTH) ORDER BY date_heure";
	$result=mysql_query($query);
	while($row=mysql_fetch_array($result)){
		$id_breve=$row[0];
		$titre=typo($row['titre']);
		$lejour=jour($row['date_heure']);
		$lemois = mois($row['date_heure']);		
		$lejour=ereg_replace("1er","1",$lejour);
		if ($lemois == $mois_today) $les_breves["$lejour"].="<BR><A HREF='breves_voir.php3?id_breve=$id_breve'><img src='IMG2/puce-blanche.gif' width='7' height='7' border='0'> <i>$titre</i></A>";
	}

	// rendez-vous personnels ou annonces
	$result_messages=mysql_query("SELECT messages.* FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE ((lien.id_auteur='$connect_id_auteur' AND lien.id_message=messages.id_message) OR messages.type='affich') AND messages.rv='oui' AND messages.date_heure >='$annee_today-$mois_today-1' AND messages.date_heure <= DATE_ADD('$annee_today-$mois_today-1', INTERVAL 1 MONTH) AND messages.statut='publie' GROUP BY messages.id_message ORDER BY messages.date_heure");
	while($row=mysql_fetch_array($result_messages)){
		$id_message=$row[0];
		$date_heure=$row["date_heure"];
		$titre=typo($row["titre"]);
		$type=$row["type"];
		$lejour=jour($row['date_heure']);
		$lejour=ereg_replace("1er","1",$lejour);

		if ($type=="normal") $la_couleur="red";
		elseif ($type=="pb") $la_couleur="blue";
		elseif ($type=="affich") $la_couleur="yellow";
		else $la_couleur="black";

		$les_rv["$lejour"].="<br><font color='$la_couleur'><b>".heures($date_heure).":".minutes($date_heure)."</b></font> <a href='message.php3?id_message=$id_message'>$titre</a>";
	}


echo "<TR><TD><A HREF='calendrier.php3?mois=$mois_prec&annee=$annee_prec'><img src='IMG2/agauche.gif' width='13' height='14' border='0'></A></TD>";
echo "<TD ALIGN='center' COLSPAN=5><FONT FACE='arial,helvetica,sans-serif' SIZE=3><B>$nom_mois $annee_today ".aide ("messcalen")."</B></FONT></TD>";
echo "<TD ALIGN=right><A HREF='calendrier.php3?mois=$mois_suiv&annee=$annee_suiv'><img src='IMG2/adroite.gif' width='13' height='14' border='0'></A></TD></TR>";

	echo "<TR>";
	echo "<TD ALIGN='center' BGCOLOR='#044476'><FONT FACE='arial,helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'><B>lundi</B></TD>";
	echo "<TD ALIGN='center' BGCOLOR='#044476'><FONT FACE='arial,helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'><B>mardi</B></TD>";
	echo "<TD ALIGN='center' BGCOLOR='#044476'><FONT FACE='arial,helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'><B>mercredi</B></TD>";
	echo "<TD ALIGN='center' BGCOLOR='#044476'><FONT FACE='arial,helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'><B>jeudi</B></TD>";
	echo "<TD ALIGN='center' BGCOLOR='#044476'><FONT FACE='arial,helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'><B>vendredi</B></TD>";
	echo "<TD ALIGN='center' BGCOLOR='#044476'><FONT FACE='arial,helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'><B>samedi</B></TD>";
	echo "<TD ALIGN='center' BGCOLOR='#044476'><FONT FACE='arial,helvetica,sans-serif' SIZE=3 COLOR='#FFFFFF'><B>dimanche</B></TD>";

	echo "</TR><TR>";
	
	for ($i=1;$i<$jour_semaine;$i++){
	
		echo "<TD></TD>";
	
	}

	for ($jour=1; $jour<32;$jour++){

		//$jdc=GregorianToJD($mois_today,$jour,$annee_today);
		
		//$jour_semaine=jddayofweek($jdc,0);

		$nom = mktime(1,1,1,$mois_today,$jour,$annee_today);
		$jour_semaine = numero_jour_semaine(date("D",$nom));




		if (checkdate($mois_today,$jour,$annee_today)){
			if ("$annee_today-$mois_today-".chiffrespar2($jour)==$ce_jour){
				echo "<TD width=100 HEIGHT=80 BGCOLOR='#FFFFFF' VALIGN='top'><FONT FACE='arial,helvetica,sans-serif' SIZE=3 COLOR='red'><B>$jour</B></FONT>";
			}else{		
				echo "<TD width=100 HEIGHT=80 BGCOLOR='#E4E4E4' VALIGN='top'><FONT FACE='arial,helvetica,sans-serif' SIZE=3><B>$jour</B></FONT>";
			}

			$activer_messagerie = lire_meta("activer_messagerie");
			$connect_activer_messagerie = $GLOBALS["connect_activer_messagerie"];
			if ($activer_messagerie == "oui" AND $connect_activer_messagerie != "non"){
				echo " <a href='message_edit.php3?rv=$annee_today-$mois_today-$jour&new=oui&type=pb'><IMG SRC='IMG2/m_envoi_bleu.gif' WIDTH='14' HEIGHT='7' BORDER='0'></a>\n";
				echo " <a href='message_edit.php3?rv=$annee_today-$mois_today-$jour&new=oui&type=normal'><IMG SRC='IMG2/m_envoi.gif' WIDTH='14' HEIGHT='7' BORDER='0'></a>\n";
			}
			if ($connect_statut == "0minirezo")
				echo " <a href='message_edit.php3?rv=$annee_today-$mois_today-$jour&new=oui&type=affich'><IMG SRC='IMG2/m_envoi_affich.gif' WIDTH='14' HEIGHT='7' BORDER='0'></a>\n";
			echo "<FONT FACE='arial,helvetica,sans-serif' SIZE=1>";
			
			if (strlen($les_rv["$jour"])>0){
				echo $les_rv["$jour"];
				echo "<hr noshade size=1>";
			}

			echo $les_articles["$jour"];

			echo $les_breves["$jour"];
			
			echo "</FONT></TD>";
			
			if ($jour_semaine==0) echo "</TR><TR>";
		}

	}

	echo "</TR></TABLE>";

}


// date du jour
$today=getdate(time());
$jour=$today["mday"];

// sans arguments => mois courant
if (!$mois){
	$mois=$today["mon"];
	$annee=$today["year"];
}

$nom_mois = nom_mois('2000-'.chiffrespar2($mois).'-01');

debut_page("Calendrier $nom_mois $annee");

echo "<BR><BR><BR>";


// marges et pied de page supprimes pour prendre toute la largeur
// debut_gauche();
// debut_droite();

afficher_mois($jour,chiffrespar2($mois),$annee,$nom_mois);

	if (strlen($les_breves["0"]) > 0 OR $les_articles["0"] > 0){
			echo "<table width=200 background=''><tr width=200><td><FONT FACE='arial,helvetica,sans-serif' SIZE=1>";
			echo "<b>Dans le courant du mois :</b>";
			echo $les_breves["0"];
			echo $les_articles["0"];
			
			echo "</font></td></tr></table>";
	}
	
	$activer_messagerie = lire_meta("activer_messagerie");
	$connect_activer_messagerie = $GLOBALS["connect_activer_messagerie"];
	if ($activer_messagerie == "oui" AND $connect_activer_messagerie != "non"){
		echo "<br><br><br><table width='700' background=''><tr width='700'><td><FONT FACE='arial,helvetica,sans-serif' SIZE=2>";
		echo "<b>AIDE :</b>";
	
		echo "<br><IMG SRC='IMG2/m_envoi_bleu.gif' WIDTH='14' HEIGHT='7' BORDER='0'> Ce bouton vous permet de cr&eacute;er un nouveau pense-b&ecirc;te personnel.\n";
		echo "<br><IMG SRC='IMG2/m_envoi.gif' WIDTH='14' HEIGHT='7' BORDER='0'> Ce bouton vous permet de donner un rendez-vous &agrave; un autre participant.\n";
		
		echo "</font></td></tr></table>";
	
	}

	


// fin_page();

?>
