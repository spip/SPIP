<?

include ("inc.php3");


debut_page();
debut_gauche();

debut_droite();


function TesterPage($laPage,$leChemin,$laRecherche,$laMethode){
	$fp = @fsockopen($laPage, 80, $errno, $errstr);
	if($fp){
		fputs($fp,"$laMethode $leChemin?$laRecherche HTTP/1.0\nHost: $laPage\n\n");
		while(!feof($fp)) {
			$leRetour=fgets($fp,128);
			$resultat .= $leRetour;
		}
		fclose($fp);
		return $resultat;
	}
}



	echo "<A HREF='naviguer.php3?coll=$id_rubrique' onMouseOver=\"retour.src='IMG2/retour-on.gif'\" onMouseOut=\"retour.src='IMG2/retour-off.gif'\"><img src='IMG2/retour-off.gif' alt='Retour &agrave; la rubrique' width='49' height='46' border='0' name='retour' align='left'><B>Retour &agrave; la rubrique<BR> (annuler la syndication)</A></B>";

echo aide ("rubsyn");

echo "<P>";
echo "&nbsp;";
echo "<P>";

$la_query=$url;

//	$texte=@file($la_query);
if($texte){
	$texte=join($texte,"");
}else{
	$la_query=parse_url($la_query);

	$le_retour=TesterPage($la_query[host],$la_query[path],$la_query[query],"GET");

	$texte=$le_retour;
	
}


if (strlen($texte)>10){
	
	$i=0;
	
	while(strpos($texte,"<item")>0){
		$debut_item=strpos($texte,"<item");
		$fin_item=strpos($texte,"</item>")+strlen("</item>");
		
		$item[$i]=substr($texte,$debut_item,$fin_item-$debut_item);
		
		$debut_texte=substr($texte,"0",$debut_item);
		$fin_texte=substr($texte,$fin_item,strlen($texte));
		$texte=$debut_texte.$fin_texte;
		
		$i++;
		
	}
	
	
	$debut_img=strpos($texte,"<image>");
	$fin_img=strpos($texte,"</image>")+strlen("</image>");
	
	$img=substr($texte,$debut_img,$fin_img-$debut_img);
	
	$debut_texte=substr($texte,"0",$debut_img);
	$fin_texte=substr($texte,$fin_img,strlen($texte));
	$texte=$debut_texte.$fin_texte;

	/////// LE SITE
	ereg("<title>(.*)</title>",$texte,$match);
	$titre_site=$match[1];
	$match="";
	ereg("<link>(.*)</link>",$texte,$match);
	$url_site=$match[1];
	$match="";
	ereg("<description>(.*)</description>",$texte,$match);
	$description_site=$match[1];
	$match="";

	if (count($item)>1) {

		echo "<TABLE WIDTH=100% CELLPADDING=1 CELLSPACING=0><TR><TD BGCOLOR='#970038'><TABLE CELLPADDING=4 WIDTH=\"100%\"><TR><TD BGCOLOR='white' WIDTH='100%'>";
		
			echo "<TABLE WIDTH=100% CELLPADDING=2 BORDER=1><TR><TD WIDTH=100% ALIGN='center' BGCOLOR='#FFCC66'><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2><B>";
			echo "ELEMENTS FOURNIS PAR CE SITE...";
			echo "</B></FONT></TD></TR></TABLE>";	

		echo "<P><B><FONT SIZE=4><A HREF='$url_site'>$titre_site</A></FONT></B>";
		echo "<BR>$description_site";
		
		//////
		echo "<UL><FONT SIZE=2>";
		for($i=0;$i<count($item);$i++){
		
			ereg("<title>(.*)</title>",$item[$i],$match);
			$le_titre=$match[1];
			$match="";

			ereg("<link>(.*)</link>",$item[$i],$match);
			$le_lien=$match[1];
			$match="";

			ereg("<date>(.*)</date>",$item[$i],$match);
			$la_date=$match[1];
			$match="";
			ereg("<auteurs>(.*)</auteurs>",$item[$i],$match);
			$les_auteurs=$match[1];
			$match="";

			if (strlen($la_date)<4)$la_date=date("Y-m-j H:i:00");

			echo "<LI><A HREF='$le_lien'>$le_titre</A>";

		
		}
		echo "</FONT></UL>";
		
		echo "</TD></TR></TABLE></TD></TR></TABLE>";

		echo "<P><FORM ACTION='naviguer.php3' METHOD='POST' ENCTYPE='multipart/form-data'>";

		echo "<TABLE WIDTH=100% CELLPADDING=1 CELLSPACING=0><TR><TD BGCOLOR='#970038'><TABLE CELLPADDING=4 WIDTH=\"100%\"><TR><TD BGCOLOR='white' WIDTH='100%'>";
	
		echo "<TABLE WIDTH=100% CELLPADDING=2 BORDER=1><TR><TD WIDTH=100% ALIGN='center' BGCOLOR='#FFCC66'><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2><B>";
		echo "VALIDATION DES ELEMENTS...";
		echo "</B></FONT></TD></TR></TABLE>";	

		echo "<P>";
		
		echo "<B>Si les &eacute;l&eacute;ments indiqu&eacute;s ci-dessus vous semblent coh&eacute;rents, vous pouvez maintenant confirmer la syndication de ce site. </B>";
		
		echo "<INPUT NAME='coll' TYPE=Hidden VALUE='$id_rubrique'>";

		echo "<P>Nom du site :<BR>";
		echo "<INPUT NAME='titre_site' TYPE='Text' SIZE=20 CLASS='forml' VALUE=\"$titre_site\">";
		echo "<P>Adresse (URL) du site :<BR>";
		echo "<INPUT NAME='url_site' TYPE='Text' SIZE=20 CLASS='forml' VALUE='$url_site'>";
		echo "<P>Description du site (optionnel) :<BR>";
		echo "<INPUT NAME='description' TYPE='Text' SIZE=20 CLASS='forml' VALUE=\"$description_site\">";
		echo "<INPUT NAME='url_syndic' TYPE='Hidden' VALUE='$url'>";
		echo "<INPUT NAME='add_syndic' TYPE='Hidden' VALUE='$id_rubrique'>";
		
		echo "<P><DIV align='right'>  <INPUT NAME='ok' TYPE=Submit VALUE='Confirmer la syndication' CLASS='fondo'>";
	
		echo "</TD></TR></TABLE></TD></TR></TABLE>";
		echo "</FORM>";
	}
}


fin_page();

?>

