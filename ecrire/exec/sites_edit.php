<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;
include_spip('inc/presentation');

// http://doc.spip.org/@exec_sites_edit_dist
function exec_sites_edit_dist()
{
	global $connect_statut, $descriptif, $id_rubrique, $id_secteur, $id_syndic, $new, $nom_site, $syndication, $url_site, $url_syndic, $connect_id_rubrique;

	$result = spip_query("SELECT * FROM spip_syndic WHERE id_syndic=" . intval($id_syndic));

	if ($row = spip_fetch_array($result)) {
		$id_syndic = $row["id_syndic"];
		$id_rubrique = $row["id_rubrique"];
		$nom_site = $row["nom_site"];
		$url_site = $row["url_site"];
		$url_syndic = $row["url_syndic"];
		$descriptif = $row["descriptif"];
		$syndication = $row["syndication"];
		$extra=$row["extra"];
	} else {
		$syndication = 'non';
		$new = 'oui';
		if (!intval($id_rubrique)) {
			$in = !$connect_id_rubrique ? ''
			  : (' WHERE id_rubrique IN (' . join(',', $connect_id_rubrique) . ')');
			$row = spip_fetch_array(spip_query("SELECT id_rubrique FROM spip_rubriques$in ORDER BY id_rubrique DESC LIMIT 1"));		
			$id_rubrique = $row['id_rubrique'];
		}
		if (!autoriser('creersitedans','rubrique',$id_rubrique )){
			// manque de chance, la rubrique n'est pas autorisee, on cherche un des secteurs autorises
			$res = spip_query("SELECT id_rubrique FROM spip_rubriques WHERE id_parent=0");
			while (!autoriser('creersitedans','rubrique',$id_rubrique ) && $row_rub = spip_fetch_array($res)){
				$id_rubrique = $row_rub['id_rubrique'];
			}
		}
	}
	$commencer_page = charger_fonction('commencer_page', 'inc');
	if ( ($new!='oui' AND (!autoriser('voir','site',$id_syndic) OR !autoriser('modifier','site',$id_syndic)))
	  OR ($new=='oui' AND !autoriser('creersitedans','rubrique',$id_rubrique)) ){
		echo $commencer_page(_T('info_site_reference_2'), "naviguer", "sites", $id_rubrique);
		echo "<strong>"._T('avis_acces_interdit')."</strong>";
		echo fin_page();
		exit;
	}

	pipeline('exec_init',array('args'=>array('exec'=>'sites_edit','id_syndic'=>$id_syndic),'data'=>''));

	echo $commencer_page(_T('info_site_reference_2'), "naviguer", "sites", $id_rubrique);

	debut_grand_cadre();

	echo afficher_hierarchie($id_rubrique);

	fin_grand_cadre();

	debut_gauche();
	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'sites_edit','id_syndic'=>$id_syndic),'data'=>''));
	creer_colonne_droite();
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'sites_edit','id_syndic'=>$id_syndic),'data'=>''));	  
	debut_droite();
	debut_cadre_formulaire();

	echo "\n<table cellpadding='0' cellspacing='0' border='0' width='100%'>\n<tr>";

	if ($new != 'oui') {
		echo "<td>";
		icone(_T('icone_retour'), generer_url_ecrire("sites","id_syndic=$id_syndic"), 'site-24.gif', "rien.gif");
		echo "</td>";
		echo "<td>". http_img_pack('rien.gif', " ", "width='10'") . "</td>\n";
	}
	echo "<td style='width: 100%'>";
	echo _T('titre_referencer_site');
	gros_titre($nom_site);
	echo "</td></tr></table><br />\n";

	if ($new == 'oui'
	AND ($connect_statut == '0minirezo' OR $GLOBALS['meta']["proposer_sites"] > 0)){
		$form_auto = "<span class='verdana1 spip_small'>"
		. _T('texte_referencement_automatique')
		. "</span>"
		. "\n<div align='right'><input type=\"text\" name=\"url\" class='fondl' size='40' value=\"http://\" />\n"
		. "\n<input type='hidden' name='id_parent' value='"
		. intval(_request('id_rubrique'))
		. "' />\n"
		. "<input type=\"submit\"  value=\""
		. _T('bouton_ajouter')
		. "\" class='fondo' />\n"
		. '</div>';

		$form_auto = generer_action_auteur('editer_site',
			'auto',
			generer_url_ecrire('sites'),
			$form_auto,
			" method='post' name='formulaireauto'"
						   );

		echo	debut_cadre_relief("site-24.gif", true)
		. $form_auto
		. fin_cadre_relief(true)
		. "\n<blockquote><b>"
		. _T('texte_non_fonction_referencement')
		. "</b>";

		$cadre_ouvert = true;
		$form = debut_cadre_enfonce("site-24.gif");
	} else $cadre_ouvert = $form = '';

	$url_syndic = entites_html($url_syndic);
	$nom_site = entites_html($nom_site);
	$url_site = entites_html($url_site);
	if (strlen($url_site)<8) $url_site="http://";

	if ($id_rubrique == 0) $logo = "racine-site-24.gif";
	else {
		$result=spip_query("SELECT id_parent FROM spip_rubriques WHERE id_rubrique='$id_rubrique'");

		while($row=spip_fetch_array($result)){
			$parent_parent=$row['id_parent'];
		}
		if ($parent_parent == 0) $logo = "secteur-24.gif";
		else $logo = "rubrique-24.gif";
	}

	// selecteur de rubriques
	$chercher_rubrique = charger_fonction('chercher_rubrique', 'inc');

	$form .= _T('info_nom_site_2')
	. "<br />\n<input type='text' class='formo' name='nom_site' value=\""
	. $nom_site
	. "\" size='40' />\n<br />"
	. _T('entree_adresse_site')
	. "<br />\n<input type='text' class='formo' name='url_site' value=\""
	. $url_site
	. "\" size='40' /><br />\n"
	. debut_cadre_couleur($logo, true, "", _T('entree_interieur_rubrique'))
	. $chercher_rubrique($id_rubrique, 'site', false)
	. fin_cadre_couleur(true)
	. "\n<br />"
	."<b>"
	. _T('entree_description_site')
	. "</b><br />\n"
	. "<textarea name='descriptif' rows='8' class='forml' cols='40' >"
	. entites_html($descriptif)
	. "</textarea>"
	. "\n<input type='hidden' name='syndication_old' value=\""
	. $syndication
	. "\" />";

	if ($GLOBALS['meta']["activer_syndic"]!= "non") {
		$form .= debut_cadre_enfonce('feed.png', true);
		if ($syndication == "non") {
			$form .= "\n<input type='radio' name='syndication' value='non' id='syndication_non' checked='checked' />";
		} else {
			$form .= "\n<input type='radio' name='syndication' value='non' id='syndication_non' />";
		}
		$form .= "\n<b><label for='syndication_non'>"
		. _T('bouton_radio_non_syndication')
		. "</label></b><br />\n";

		if ($syndication == "non") {
			$form .= "<input type='radio' name='syndication' value='oui' id='syndication_oui' />";
		} else {
			$form .= "<input type='radio' name='syndication' value='oui' id='syndication_oui' checked='checked' />";
		}
		$form .= "\n<b><label for='syndication_oui'>"
		. _T('bouton_radio_syndication')
		. "</label></b>"
		. aide("rubsyn")
		. "\n<table cellpadding='0' cellspacing='0' border='0' width='100%'>\n<tr><td style='width: 10px;'>&nbsp;</td>\n<td>"
		. _T('entree_adresse_fichier_syndication')
		. "<br />\n";

		if (strlen($url_syndic) < 8) $url_syndic = "http://";

	// cas d'une liste de flux detectee par feedfinder : menu
		if (preg_match(',^select: (.+),', $url_syndic, $regs)) {
			$feeds = explode(' ',$regs[1]);
			$form .= "<select name='url_syndic'>\n";
			foreach ($feeds as $feed) {
				$form .= '<option value="'.entites_html($feed).'">'.$feed."</option>\n";
			}
			$form .= "</select>\n";
		} else {
			$form .= "<input type='text' class='formo' name='url_syndic' value=\"$url_syndic\" size='40' />\n";
		}
		$form .= "</td></tr></table>";
		$form .= fin_cadre_enfonce(true);
	}

	if ($GLOBALS['champs_extra']) {
		include_spip('inc/extra');
		$form .= extra_saisie($extra, 'sites', intval($id_secteur));
	}

	$form .= "\n<div align='right'><input type='submit' value='"
	. _T('bouton_enregistrer')
	. "' class='fondo' /></div>";

	$form = generer_action_auteur('editer_site',
				      ($new == 'oui') ? $new : $id_syndic,
				      generer_url_ecrire('sites'),
				      $form,
				      " method='post' name='formulaire'"
				      );

	if ($cadre_ouvert) {
		$form .= fin_cadre_enfonce(true);
		$form .= "</blockquote>\n";
	}

	echo $form;
	
	echo fin_cadre_formulaire(true);
	
	echo pipeline('affiche_milieu',array('args'=>array('exec'=>'sites_edit','id_syndic'=>$id_syndic),'data'=>''));

	echo fin_gauche(), fin_page();
}
?>
