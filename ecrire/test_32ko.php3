<?php
	include "inc_32ko_browsers.php3";
	$toto = "Vous savez parler le 32ko ; moi non, mais je vous en remercie...\n";
	$long = strlen($toto);
	while ($taille < 38*1024){
		$taille += $long;
		$texte .= $toto;
	}
	if ($control == "vu") {
		echo "<H1>R&eacute;sultat du test</H1>";
		print "texte initial : ".strlen($texte)." car. ; champ renvoy&eacute; : ".strlen($champ)." car.<BR>";

		$champ = ereg_replace("[\r\n]+","\n",$champ);
		$texte = ereg_replace("[\r\n]+","\n",$texte);

		if (trim($champ) == trim($texte)){
			echo "<B>Votre navigateur est <font color=green>OK</font> :</B><BR>
					$HTTP_USER_AGENT\n";
			$ok = true;
		} else {
			echo "<B>Votre navigateur <font color='red'>coupe les textes trop longs</font> :</B><BR>
					$HTTP_USER_AGENT\n";
			$ok = false;
		}

		// connu ou pas ?
		if ($connu_ok[$HTTP_USER_AGENT]){
			if (! $ok){
				print "Contre-ordre!<BR>".
				"<P>Merci d'envoyer ce r&eacute;sultat &agrave; &lt;spip-dev@rezo.net&gt;.";
			}
		} elseif ($connu_coupe[$HTTP_USER_AGENT]){
			if ($ok){
				print "Contre-ordre!<BR>".
				"<P>Merci d'envoyer ce r&eacute;sultat &agrave; &lt;spip-dev@rezo.net&gt;.";
			}

		} else
			echo "<P>Merci d'envoyer ce r&eacute;sultat &agrave; &lt;spip-dev@rezo.net&gt;.";
	} else {
		echo "<H1>Test navigateur</H1>";
		echo "<font color=red><b>Certains navigateurs sont capables de traiter
			des TEXTAREA de plus de 32ko. Ici vous pouvez tester le v&ocirc;tre...</b></font><BR>";

		if ($connu_ok[$HTTP_USER_AGENT]){
			echo "<B><font color=blue>A priori votre navigateur est OK.</font></B><BR>";
		} elseif ($connu_coupe[$HTTP_USER_AGENT]){
			echo "<B><font color=blue>A priori votre navigateur coupe les textes.</font></B><BR>";
		} else {
			echo "<B><font color=blue>Votre navigateur n'est pas encore test&eacute;</font></B><BR>";
		}

		echo "<FORM action='test_32ko.php3' METHOD='post'>";
		echo "<TEXTAREA NAME='champ' ROWS='20' COLS='40'>";
		print $texte;
		echo "</TEXTAREA>";
		echo "<input type='hidden' name='control' value='vu'><BR>"; 
		echo "<INPUT TYPE='submit' value='Tester votre navigateur'></FORM>";
	}
?>