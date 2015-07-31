<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/presentation');
include_spip('inc/rubriques');

function exec_rubriques_edit_dist()
{
  global
    $champs_extra,
    $connect_statut,
    $id_parent,
    $id_rubrique,
    $new,
    $options;

if ($new == "oui") {
	if (($connect_statut=='0minirezo') AND acces_rubrique($id_parent)) {
		$id_parent = intval($id_parent);
		$id_rubrique = 0;
		$titre = filtrer_entites(_T('titre_nouvelle_rubrique'));
		$onfocus = " onfocus=\"if(!antifocus){this.value='';antifocus=true;}\"";
		$descriptif = "";
		$texte = "";
	}
	else {
		echo _T('avis_acces_interdit');
		exit;
	}
}
else {
	$id_rubrique = intval($id_rubrique);
	$result = spip_query("SELECT * FROM spip_rubriques WHERE id_rubrique='$id_rubrique'");
	while ($row = spip_fetch_array($result)) {
		$id_parent = $row['id_parent'];
		$titre = $row['titre'];
		$descriptif = $row['descriptif'];
		$texte = $row['texte'];
		$id_secteur = $row['id_secteur'];
		$extra = $row["extra"];
	}
}

 pipeline('exec_init',array('args'=>array('exec'=>'rubriques_edit','id_rubrique'=>$id_rubrique),'data'=>''));
 debut_page(_T('info_modifier_titre', array('titre' => $titre)), "naviguer", "rubriques", '', '', $id_rubrique);

if ($id_parent == 0) $ze_logo = "secteur-24.gif";
else $ze_logo = "rubrique-24.gif";

if ($id_parent == 0) $logo_parent = "racine-site-24.gif";
else {
	$id_secteur = spip_fetch_array(spip_query("SELECT id_secteur FROM spip_rubriques WHERE id_rubrique='$id_parent'"));
	$id_secteur = $id_secteur['id_secteur'];
	if ($id_parent_== $id_secteur)
		$logo_parent = "secteur-24.gif";
	else	$logo_parent = "rubrique-24.gif";
}


debut_grand_cadre();

afficher_hierarchie($id_parent);

fin_grand_cadre();

debut_gauche();
//////// parents



echo pipeline('affiche_gauche',array('args'=>array('exec'=>'rubriques_edit','id_rubrique'=>$id_rubrique),'data'=>''));
creer_colonne_droite();
echo pipeline('affiche_droite',array('args'=>array('exec'=>'rubriques_edit','id_rubrique'=>$id_rubrique),'data'=>''));	  
debut_droite();

debut_cadre_formulaire();

echo "\n<table cellpadding=0 cellspacing=0 border=0 width='100%'>";
echo "<tr width='100%'>";
echo "<td>";

if ($id_rubrique) icone(_T('icone_retour'), generer_url_ecrire("naviguer","id_rubrique=$id_rubrique"), $ze_logo, "rien.gif");
else icone(_T('icone_retour'), generer_url_ecrire("naviguer","id_rubrique=$id_parent"), $ze_logo, "rien.gif");

echo "</td>";
echo "<td>". http_img_pack('rien.gif', " ", "width='10'") . "</td>\n";
echo "<td width='100%'>";
echo _T('info_modifier_rubrique');
gros_titre($titre);
echo "</td></tr></table>";
echo "<p>";

echo  generer_url_post_ecrire("naviguer",($id_rubrique ? "id_rubrique=$id_rubrique" : ""));

$titre = entites_html($titre);

echo _T('entree_titre_obligatoire');
echo "<INPUT TYPE='text' CLASS='formo' NAME='titre' VALUE=\"$titre\" SIZE='40' $onfocus><P>";


debut_cadre_couleur("$logo_parent", false, '', _T('entree_interieur_rubrique').aide ("rubrub"));

// selecteur de rubriques
$selecteur_rubrique = charger_fonction('chercher_rubrique', 'inc');
$restreint = ($GLOBALS['statut'] == 'publie');
echo $selecteur_rubrique($id_parent, 'rubrique', $restreint, $id_rubrique);


// si c'est une rubrique-secteur contenant des breves, demander la
// confirmation du deplacement
 $contient_breves = spip_fetch_array(spip_query("SELECT COUNT(*) AS cnt FROM spip_breves WHERE id_rubrique='$id_rubrique' LIMIT 1"));
 $contient_breves = $contient_breves['cnt'];

if ($contient_breves > 0) {
	$scb = ($contient_breves>1? 's':'');
	echo "<div><font size='2'><input type='checkbox' name='confirme_deplace'
	value='oui' id='confirme-deplace'
	><label for='confirme-deplace'>&nbsp;"
	._T('avis_deplacement_rubrique',
		array('contient_breves' => $contient_breves,
			'scb' => $scb))
	."</font></label></div>\n";
} else
	echo "<input type='hidden' name='confirme_deplace' value='oui' />\n";


fin_cadre_couleur();

echo "<P>";


if ($options == "avancees" OR $descriptif) {
	echo "<B>"._T('texte_descriptif_rapide')."</B><BR>";
	echo _T('entree_contenu_rubrique')."<BR>";
	echo "<TEXTAREA NAME='descriptif' CLASS='forml' ROWS='4' COLS='40' wrap=soft>";
	echo entites_html($descriptif);
	echo "</TEXTAREA><P>\n";
}
else {
	echo "<INPUT TYPE='Hidden' NAME='descriptif' VALUE=\"".entites_html($descriptif)."\" />";
}

echo "<B>"._T('info_texte_explicatif')."</B>";
echo aide ("raccourcis");
echo "<BR><TEXTAREA NAME='texte' ROWS='15' CLASS='formo' COLS='40' wrap=soft>";
echo entites_html($texte);
echo "</TEXTAREA>\n";

	if ($champs_extra) {
		include_spip('inc/extra');
		extra_saisie($extra, 'rubriques', $id_secteur);
	}

echo "<input type='hidden' name='new' value='",
	  (($new == "oui") ? 'oui' : 'non'),
	  "' />";

echo "\n<p align='right'><input type='submit' value='"._T('bouton_enregistrer')."' CLASS='fondo' />\n</p></form>";

fin_cadre_formulaire();

fin_page();
}
?>
