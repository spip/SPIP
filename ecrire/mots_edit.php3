<?php

include ("inc.php3");
include_ecrire ("inc_logos.php3");
include_ecrire ("inc_sites.php3");

function mySel($varaut, $variable) {
	$retour = " VALUE=\"$varaut\"";

	if ($variable==$varaut){
		$retour.= " SELECTED";
	}
	return $retour;
}


//
// modifications mot
//
if ($connect_statut == '0minirezo') {
	if ($supp_mot) {
		$query = "DELETE FROM spip_mots WHERE id_mot=$supp_mot";
		$result = spip_query($query);
		$query = "DELETE FROM spip_mots_articles WHERE id_mot=$supp_mot";
		$result = spip_query($query);
	}

	if ($titre_mot) {
		if ($new == 'oui' && $id_groupe) {
			spip_query("INSERT INTO spip_mots (id_groupe) VALUES ($id_groupe)");
			$id_mot = spip_insert_id();

			// ajouter le mot a l'article
			if (settype($ajouter_id_article, 'integer') AND ($ajouter_id_article>0))
				spip_query("INSERT INTO spip_mots_articles (id_mot, id_article) VALUES ($id_mot, $ajouter_id_article)");
		}

		$titre_mot = addslashes($titre_mot);
		$texte = addslashes($texte);
		$descriptif = addslashes($descriptif);
		$type = addslashes(corriger_caracteres($type));
		$result = spip_query("SELECT * FROM spip_groupes_mots WHERE id_groupe='$id_groupe'");
		if ($row = spip_fetch_array($result))
			$type = addslashes(corriger_caracteres($row['titre']));

		// recoller les champs du extra
		if ($champs_extra) {
			include_ecrire("inc_extra.php3");
			$add_extra = ", extra = '".addslashes(extra_recup_saisie("mots"))."'";
		} else
			$add_extra = '';

		$query = "UPDATE spip_mots SET titre=\"$titre_mot\", texte=\"$texte\", descriptif=\"$descriptif\", type=\"$type\", id_groupe=$id_groupe $add_extra WHERE id_mot=$id_mot";
		$result = spip_query($query);

		if (lire_meta('activer_moteur') == 'oui') {
			include_ecrire ("inc_index.php3");
			indexer_mot($id_mot);
		}
	}
	else if ($new == 'oui') {
		$titre_mot = filtrer_entites(_T('texte_nouveau_mot'));
	}
}

//
// redirection ou affichage
//
if ($redirect_ok == 'oui' && $redirect) {
	@header("Location: ".rawurldecode($redirect));
	exit;
}

//
// Recupere les donnees
//
$query = "SELECT * FROM spip_mots WHERE id_mot='$id_mot'";
$result = spip_query($query);

if ($row = spip_fetch_array($result)) {
	$id_mot = $row['id_mot'];
	$titre_mot = $row['titre'];
	$descriptif = $row['descriptif'];
	$texte = $row['texte'];
	$type = $row['type'];
	$extra = $row['extra'];
	$id_groupe = $row['id_groupe'];
}

debut_page("&laquo; $titre_mot &raquo;", "documents", "mots");
debut_gauche();


//////////////////////////////////////////////////////
// Boite "voir en ligne"
//

debut_boite_info();
echo "<CENTER>";
echo "<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=1><B>"._T('titre_gauche_mots_edit')."</B></FONT>";
echo "<BR><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=6><B>$id_mot</B></FONT>";
echo "</CENTER>";

	icone_horizontale(_T('icone_voir_en_ligne'), "../spip_redirect.php3?id_mot=$id_mot&recalcul=oui", "racine-24.gif", "rien.gif");


fin_boite_info();

echo "<p><center>";
if ($new == 'oui') {
	$adresse_retour = "mots_edit.php3?redirect=$redirect&redirect_ok=oui&supp_mot=$id_mot";
}else {
	$adresse_retour = "mots_edit.php3?redirect=$redirect&redirect_ok=oui";
}
echo "</center>";

//////////////////////////////////////////////////////
// Logos du mot-clef
//

$arton = "moton$id_mot";
$artoff = "motoff$id_mot";

if ($id_mot > 0 AND $connect_statut == '0minirezo')
	afficher_boite_logo($arton, $artoff, _T('logo_mot_cle').aide("breveslogo"), _T('logo_survol'));


//
// Afficher les boutons de creation d'article et de breve
//
debut_raccourcis();

icone_horizontale(_T('icone_voir_tous_mots_cles'), "mots_tous.php3", "mot-cle-24.gif", "rien.gif");

if ($connect_statut == '0minirezo')
		icone_horizontale(_T('icone_creation_mots_cles'), "mots_edit.php3?new=oui&redirect=mots_tous.php3&id_groupe=$id_groupe", "mot-cle-24.gif", "creer.gif");

fin_raccourcis();


debut_droite();

debut_cadre_relief("mot-cle-24.gif");


echo "\n<table cellpadding=0 cellspacing=0 border=0 width='100%'>";
echo "<tr width='100%'>";
echo "<td width='100%' valign='top'>";
gros_titre($titre_mot);


if ($descriptif) {
	echo "<p><div align='left' border: 1px dashed #aaaaaa;'>";
	echo "<font size=2 face='Verdana,Arial,Sans,sans-serif'>";
	echo "<b>"._T('info_descriptif')."</b> ";
	echo propre($descriptif);
	echo "&nbsp; ";
	echo "</font>";
	echo "</div>";
}
echo "</td>";
echo "</tr></table>\n";


if (strlen($texte)>0){
	echo "<FONT FACE='Verdana,Arial,Sans,sans-serif'>";
	echo "<P>".propre($texte);
	echo "</FONT>";
}



if ($id_mot) {
	echo "<P>";

	if ($connect_statut == "0minirezo")
		$aff_articles = "'prepa','prop','publie','refuse'";
	else
		$aff_articles = "'prop','publie'";

	afficher_rubriques(_T('info_rubriques_liees_mot'),
	"SELECT rubrique.* FROM spip_rubriques AS rubrique, spip_mots_rubriques AS lien WHERE lien.id_mot='$id_mot'
	AND lien.id_rubrique=rubrique.id_rubrique ORDER BY rubrique.titre");

	afficher_articles(_T('info_articles_lies_mot'),
	", spip_mots_articles AS lien WHERE lien.id_mot='$id_mot'
	AND lien.id_article=articles.id_article AND articles.statut IN ($aff_articles) ORDER BY articles.date DESC");

	afficher_breves(_T('info_breves_liees_mot'),
	"SELECT breves.* FROM spip_breves AS breves, spip_mots_breves AS lien WHERE lien.id_mot='$id_mot'
	AND lien.id_breve=breves.id_breve ORDER BY breves.date_heure DESC LIMIT 0,10");

	afficher_sites(_T('info_sites_lies_mot'),
	"SELECT syndic.* FROM spip_syndic AS syndic, spip_mots_syndic AS lien WHERE lien.id_mot='$id_mot'
	AND lien.id_syndic=syndic.id_syndic ORDER BY syndic.nom_site DESC LIMIT 0,10");
}

fin_cadre_relief();



if ($connect_statut =="0minirezo"){
	echo "<P>";
	debut_cadre_formulaire();

	echo "<FORM ACTION='mots_edit.php3' METHOD='post'>";
	echo "<FONT FACE='Georgia,Garamond,Times,serif' SIZE=3>";
	if ($id_mot)
		echo "<INPUT TYPE='Hidden' NAME='id_mot' VALUE='$id_mot'>\n";
	else if ($new=='oui')
		echo "<INPUT TYPE='Hidden' NAME='new' VALUE='oui'>\n";
	echo "<INPUT TYPE='Hidden' NAME='redirect' VALUE=\"$redirect\">\n";
	echo "<INPUT TYPE='Hidden' NAME='redirect_ok' VALUE='oui'>\n";
	echo "<INPUT TYPE='Hidden' NAME='ajouter_id_article' VALUE=\"$ajouter_id_article\">\n";

	$titre_mot = entites_html($titre_mot);
	$descriptif = entites_html($descriptif);
	$texte = entites_html($texte);

	echo "<B>"._T('info_titre_mot_cle')."</B> "._T('info_obligatoire_02');
	echo aide ("mots");

	echo "<BR><INPUT TYPE='text' NAME='titre_mot' CLASS='formo' VALUE=\"$titre_mot\" SIZE='40'>";

	// dans le groupe...
	$query_groupes = "SELECT * FROM spip_groupes_mots ORDER BY titre";
	$result = spip_query($query_groupes);
	if (spip_num_rows($result)>1) {
		debut_cadre_relief("groupe-mot-24.gif");
		echo  _T('info_dans_groupe')."</label>\n";
		echo aide ("motsgroupes");
		echo  " &nbsp; <SELECT NAME='id_groupe' class='fondl'>\n";
		while ($row_groupes = spip_fetch_array($result)){
			$groupe = $row_groupes['id_groupe'];
			$titre_groupe = entites_html($row_groupes['titre']);
			echo  "<OPTION".mySel($groupe, $id_groupe).">$titre_groupe</OPTION>\n";
		}			
		echo  "</SELECT>";
		fin_cadre_relief();
	} else {
		$row_groupes = spip_fetch_array($result);
		if (!$row_groupes) {
			// il faut creer un groupe de mots (cas d'un mot cree depuis articles.php3)
			spip_query("INSERT INTO spip_groupes_mots SET titre='".addslashes(_T('info_mot_sans_groupe'))."', unseul='non', obligatoire='non',
                articles='oui', breves='oui', rubriques='non', syndic='oui',
                0minirezo='oui', 1comite='non', 6forum='non'");
			$row_groupes['id_groupe'] = spip_insert_id();
		}
		echo "<input type='hidden' name='id_groupe' value='".$row_groupes['id_groupe']."'>";
	}

	if ($options == 'avancees' OR $descriptif) {
		echo "<B>"._T('texte_descriptif_rapide')."</B><BR>";
		echo "<TEXTAREA NAME='descriptif' CLASS='forml' ROWS='4' COLS='40' wrap=soft>";
		echo $descriptif;
		echo "</TEXTAREA><P>\n";
	}
	else
		echo "<INPUT TYPE='hidden' NAME='descriptif' VALUE=\"$descriptif\">";

	if ($options == 'avancees' OR $texte) {
		echo "<B>"._T('info_texte_explicatif')."</B><BR>";
		echo "<TEXTAREA NAME='texte' ROWS='8' CLASS='forml' COLS='40' wrap=soft>";
		echo $texte;
		echo "</TEXTAREA><P>\n";
	}
	else
		echo "<INPUT TYPE='hidden' NAME='texte' VALUE=\"$texte\">";

	if ($champs_extra) {
		include_ecrire("inc_extra.php3");
		extra_saisie($extra, 'mots', $id_groupe);
	}

	echo "<DIV align='right'><INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
	echo "</FORM>";

	fin_cadre_formulaire();
}


fin_page();

?>
