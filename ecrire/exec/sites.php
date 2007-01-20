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
include_spip('inc/sites_voir');
include_spip('inc/syndic');
include_spip('inc/mots');
include_spip('inc/date');
include_spip('inc/config');

// http://doc.spip.org/@exec_sites_dist
function exec_sites_dist()
{
	global $options,   $spip_lang_left,  $spip_lang_right, $spip_display;

	global $cherche_mot, $select_groupe, $id_syndic;

	$id_syndic = intval($id_syndic);

	$result = spip_query("SELECT * FROM spip_syndic WHERE id_syndic=$id_syndic");

	if ($row = spip_fetch_array($result)) {
		$id_syndic = $row["id_syndic"];
		$id_rubrique = $row["id_rubrique"];
		$nom_site = $row["nom_site"];
		$url_site = $row["url_site"];
		$url_syndic = $row["url_syndic"];
		$descriptif = $row["descriptif"];
		$syndication = $row["syndication"];
		$statut = $row["statut"];
		$date_heure = $row["date"];
		$date_syndic = $row['date_syndic'];
		$mod = $row['moderation'];
		$extra=$row["extra"];

		$flag_administrable = autoriser('publierdans','rubrique',$id_rubrique);

		$flag_editable = ($flag_administrable OR ($GLOBALS['meta']["proposer_sites"] > 0 AND ($statut == 'prop')));
	
	} else {$id_syndic = 0; $nom_site='';}

	if ($nom_site)
		$titre_page = "&laquo; $nom_site &raquo;";
	else
		$titre_page = _T('info_site');

	pipeline('exec_init',array('args'=>array('exec'=>'sites','id_syndic'=>$id_syndic),'data'=>''));


	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page("$titre_page","naviguer","sites", $id_rubrique);

	debut_grand_cadre();

	echo afficher_hierarchie($id_rubrique);

	fin_grand_cadre();

	if (!$id_syndic) {echo _T('public:aucun_site'); exit;}

	debut_gauche();

	debut_boite_info();

	$res = "\n<div style='font-weight: bold; text-align: center' class='verdana1 spip_xx-small'>"
		  .  _T('titre_site_numero')
		  . "<br /><span class='spip_xx-large'>"
		  . $id_syndic
		  . '</span></div>';
	echo $res;
	voir_en_ligne ('site', $id_syndic, $statut);
	fin_boite_info();


	echo "\n<br /><div align='center'>";
	icone (_T('icone_voir_sites_references'), generer_url_ecrire("sites_tous",""), "site-24.gif","rien.gif");
	echo "</div>";

	if ($id_syndic AND $flag_administrable AND ($spip_display != 4)) {
		$iconifier = charger_fonction('iconifier', 'inc');
		echo $iconifier('id_syndic', $id_syndic, 'sites');
	}
	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'sites','id_syndic'=>$id_syndic),'data'=>''));

	creer_colonne_droite();
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'sites','id_syndic'=>$id_syndic),'data'=>''));

	debut_droite();

	debut_cadre_relief("site-24.gif");

	if ($syndication == 'off' OR $syndication == 'sus') {
		$logo_statut = "puce-orange-anim.gif";
	} 
	else if ($statut == 'publie') {
		$logo_statut = "puce-verte.gif";
	}
	else if ($statut == 'prop') {
		$logo_statut = "puce-blanche.gif";
	}
	else if ($statut == 'refuse') {
		$logo_statut = "puce-rouge.gif";
	}

	$url_affichee = $url_site;

	if (strlen($url_affichee) > 40) $url_affichee = substr($url_affichee, 0, 30)."...";

	echo "<div style='text-align: center;'>";
	echo "\n<table cellpadding='0' cellspacing='0' border='0' width='100%'>";
	echo "<tr><td style='width: 100%' valign='top'>";
	gros_titre($nom_site, $logo_statut);
	echo "<a href='$url_site'><b>$url_affichee</b></a>";

	if (strlen($descriptif) > 1) {
		echo "<div align='left' style='padding: 5px; border: 1px dashed #aaaaaa; background-color: #e4e4e4; margin-top: 5px; ' class='verdana1 spip_small'>";
		echo "<b>"._T('info_descriptif')."</b> ";
		echo propre($descriptif);
		echo "&nbsp; ";
		echo "</div>";
	}
	echo "</td>";

	if ($flag_editable) {
		echo "<td>". http_img_pack('rien.gif', " ", "width='5'") . "</td>\n";
		echo "<td  align='right'>";
		icone(_T('icone_modifier_site'), generer_url_ecrire('sites_edit',"id_syndic=$id_syndic"), "site-24.gif", "edit.gif");
		echo "</td>";
	}
	echo "</tr></table><br />\n";

	if ($flag_editable AND ($options == 'avancees' OR $statut == 'publie')) {
		if ($statut == 'publie') {

			debut_cadre_enfonce();
			$dater = charger_fonction('dater', 'inc');
			echo $dater($id_syndic, $flag_editable, $statut, 'syndic', 'sites', $date_heure);
			fin_cadre_enfonce();	
		} else {
			echo "<br />\n<span class='verdana1 spip_medium'>"._T('info_site_propose')." <b>".affdate($date_heure)."&nbsp;</b></span>";
	}
}

	$editer_mot = charger_fonction('editer_mot', 'inc');
	echo $editer_mot('syndic', $id_syndic,  $cherche_mot,  $select_groupe, $flag_editable);

	echo pipeline('affiche_milieu',array('args'=>array('exec'=>'sites','id_syndic'=>$id_syndic),'data'=>''));

	if ($flag_administrable) {
		debut_cadre_relief("racine-site-24.gif");

		$corps = "\n<div style='text-align: center'><b>"
		. _T('info_statut_site_1')
		. "</b> &nbsp;&nbsp; \n"
		.  "<select name='nouveau_statut' size='1' class='fondl'>\n"
		.  my_sel("prop",_T('info_statut_site_3'),$statut)
		.  my_sel("publie",_T('info_statut_site_2'),$statut)
		.  my_sel("refuse",_T('info_statut_site_4'),$statut)
		. "</select>\n"
		. " &nbsp;&nbsp;&nbsp; "
		. "<input type='submit' value='"
		. _T('bouton_valider')
		.  "' class='fondo' />\n"
		.  "</div>\n";

		echo redirige_action_auteur('editer_site',
			$id_syndic,
			'sites',
			"id_syndic=$id_syndic&id_parent=$id_rubrique",
			$corps);
		fin_cadre_relief();
	}


	if ($syndication == "oui" OR $syndication == "off" OR $syndication == "sus") {
		echo "<p class='verdana1 spip_medium'><a href='".htmlspecialchars($url_syndic)."'>",	http_img_pack('feed.png', 'RSS', ''),	'</a> <b>'._T('info_site_syndique').'</b></p>';


		if ($syndication == "off" OR $syndication=="sus") {
			debut_boite_info();
			echo _T('avis_site_syndique_probleme', array('url_syndic' => quote_amp($url_syndic)));

			echo redirige_action_auteur('editer_site',
				$id_syndic,
				'sites',
				'',
				"<input type='hidden' name='reload' value='oui' />
			<input type='submit' value=\""
			. attribut_html(_T('lien_nouvelle_recuperation'))
			. "\" class='fondo spip_xx-small' />"
						    );
			fin_boite_info();
		}
		echo afficher_syndic_articles(_T('titre_articles_syndiques'), array('FROM' => 'spip_syndic_articles', 'WHERE' => "id_syndic=$id_syndic", 'ORDER BY' => "date DESC"), $id_syndic);


	// afficher la date de dernier acces a la syndication

		if ($date_syndic)
			echo "<div align='left'>".
			  "<span class='verdana1 spip_small'>",  _T('info_derniere_syndication').' '.affdate_heure($date_syndic)	.".</span></div>\n";

		echo "<div align='right'>\n";
		echo generer_action_auteur('editer_site',
			$id_syndic,
			generer_url_ecrire('sites'),
			"<input type='hidden' name='reload' value='oui' />
		<input type='submit' value=\""
			. attribut_html(_T('lien_mise_a_jour_syndication'))
			. "\" class='fondo spip_xx-small' />",
					   " method='post'"
					   );
		echo "</div>\n";

	// Options
		if ($flag_administrable && $options=='avancees') {

			$moderation = $mod;
			if ($moderation != 'oui') $moderation='non';

		$res .= "<div align='".$GLOBALS['spip_lang_left']."'>".
		  _T('syndic_choix_moderation')
		. "<div style='padding-$spip_lang_left: 40px;'>"
		. afficher_choix('moderation', $moderation,
			array(
			'non' => _T('info_publier') .' ('._T('bouton_radio_modere_posteriori').')',
			'oui' => _T('info_bloquer') .' ('._T('bouton_radio_modere_priori').')' ))
		. "</div></div>\n";
		
		// Oublier les vieux liens ?
		// Depublier les liens qui ne figurent plus ?

		$res .= "\n<div>&nbsp;</div>"
		. "\n<div align='".$GLOBALS['spip_lang_left']."'>"._T('syndic_choix_oublier'). '</div>'
		. "\n<ul align='".$GLOBALS['spip_lang_left']."'>\n";

		$on = array('oui' => _T('item_oui'), 'non' => _T('item_non'));
		if (!$miroir = $row['miroir']) $miroir = 'non';
		$res .= "\n<li>"._T('syndic_option_miroir').' '
		. afficher_choix('miroir', $miroir, $on, " &nbsp; ")
		. "</li>\n";

		if (!$oubli = $row['oubli']) $oubli = 'non';
		$res .= "\n<li>"
		. _T('syndic_option_oubli', array('mois' => 2)).' '
		. afficher_choix('oubli', $oubli, $on," &nbsp; ")
		. "</li>\n"
		. "</ul>\n";

		// Prendre les resumes ou le texte integral ?
		if (!$resume = $row['resume']) $resume = 'oui';
		$res .= "\n<div align='$spip_lang_left'>"
		.  _T('syndic_choix_resume') 
		. "\n<div style='padding-$spip_lang_left: 40px;'>"
		. afficher_choix('resume', $resume,
			array(	'oui' => _T('syndic_option_resume_oui'),
				'non' => _T('syndic_option_resume_non')	))
		. "</div></div>\n";

		// Bouton "Valider"
		$res .= "\n<div style='text-align:$spip_lang_right'><input type='submit' value='"._T('bouton_valider')."' class='fondo' /></div>\n";
		echo debut_cadre_relief('feed.png', false, "", _T('syndic_options').aide('artsyn')),	
		  redirige_action_auteur('editer_site',
					 "options/$id_syndic",
					 'sites',
					 '',
					 $res,
					 " method='post'"),
		  fin_cadre_relief();
	}
	}

// Cas d'un site pour lesquels feedfinder a un ou plusieurs flux,
// et l'on propose de choisir
else if (preg_match(',^\s*select: (.*),', $url_syndic, $regs)) {

	$res = "<br /><br />\n";
	foreach (
		array('id_rubrique', 'nom_site', 'url_site', 'descriptif', 'statut')
	as $var) {
		$res .= "<input type='hidden' name='$var' value=\"".entites_html($$var)."\" />\n";
	}

	$res .= "<div align='$spip_lang_left'>\n";
	$res .= "<div><input type='radio' name='syndication' value='non' id='syndication_non' checked='checked' />";
	$res .= " <b><label for='syndication_non'>"._T('bouton_radio_non_syndication')."</label></b></div>\n";
	$res .= "<div><input type='radio' name='syndication' value='oui' id='syndication_oui' />";
	$res .= " <label for='syndication_oui'>"._T('bouton_radio_syndication')."</label></div>\n";

	$res .= "<select name='url_syndic'>\n";
	foreach (explode(' ',$regs[1]) as $feed) {
		$res .= '<option value="'.entites_html($feed).'">'.$feed."</option>\n";
	}
	$res .= "</select>\n";
	$res .= aide("rubsyn");
	$res .= "<div align='$spip_lang_right'><input type='submit' value='"._T('bouton_valider')."' class='fondo' /></div>\n";
	$res .= "</div>\n";
	echo debut_cadre_relief();
	echo redirige_action_auteur('editer_site',
		$id_syndic,
		'sites',
		'',
		$res,
		" method='post'");
	echo fin_cadre_relief();

}


if ($GLOBALS['champs_extra'] AND $extra) {
		include_spip('inc/extra');
		echo extra_affichage($extra, "sites");
	}

 echo '</div>';
fin_cadre_relief();



//////////////////////////////////////////////////////
// Forums
//

 echo "<br /><br />\n<div align='center'>";

 icone (_T('icone_poster_message'), generer_url_ecrire('forum_envoi', "id_syndic=$id_syndic&statut=prive&script=sites") . '#formulaire', "forum-interne-24.gif", "creer.gif");

 echo "</div>\n";

 $result_forum = spip_query("SELECT * FROM spip_forum WHERE statut='prive' AND id_syndic=$id_syndic AND id_parent=0 ORDER BY date_heure DESC LIMIT 20");

 echo afficher_forum($result_forum, "sites","id_syndic=$id_syndic");

 echo fin_gauche(), fin_page();
}


?>
