<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;
include_spip('inc/presentation');
include_spip('inc/date');
include_spip('inc/config');

// http://doc.spip.org/@exec_sites_dist
function exec_sites_dist()
{
	exec_sites_args(intval(_request('id_syndic')));
}

// http://doc.spip.org/@exec_sites_args
function exec_sites_args($id_syndic)
{
	if (!autoriser('voir','site',$id_syndic)
	OR (!$row = sql_fetsel("*", "spip_syndic", "id_syndic=$id_syndic"))) {
		include_spip('inc/minipres');
		echo minipres(_T('public:aucun_site'));
	} else {

		$id_rubrique = $row["id_rubrique"];
		$nom_site = $row["nom_site"];
		$titre_page = "&laquo; $nom_site &raquo;";
		pipeline('exec_init',array('args'=>array('exec'=>'sites','id_syndic'=>$id_syndic),'data'=>''));

		$commencer_page = charger_fonction('commencer_page', 'inc');
		echo $commencer_page("$titre_page","naviguer","sites", $id_rubrique);
		afficher_site($id_syndic, $id_rubrique, $nom_site, $row);
		echo fin_gauche();
		echo fin_page();
	}
}


// http://doc.spip.org/@afficher_site
function afficher_site($id_syndic, $id_rubrique, $nom_site, $row){

	global $spip_lang_left,  
$spip_lang_right;

	$cherche_mot = _request('cherche_mot');
	$select_groupe = _request('select_groupe');
	$id_secteur = $row["id_secteur"];
	$url_site = $row["url_site"];
	$url_syndic = $row["url_syndic"];
	$descriptif = $row["descriptif"];
	$syndication = $row["syndication"];
	$statut = $row["statut"];
	$date_heure = $row["date"];
	$date_syndic = $row['date_syndic'];
	$mod = $row['moderation'];
	$extra=$row["extra"];

	$flag_administrable = autoriser('modifier','site',$id_syndic);
	$flag_editable = ($flag_administrable OR ($GLOBALS['meta']["proposer_sites"] > 0 AND ($statut == 'prop')));

	$meme_rubrique = charger_fonction('meme_rubrique', 'inc');
	$iconifier = charger_fonction('iconifier', 'inc');
	if ($flag_editable AND ($statut == 'publie'))
		$dater = charger_fonction('dater', 'inc');
	$editer_mots = charger_fonction('editer_mots', 'inc');
	if ($flag_administrable)
		$instituer_site = charger_fonction('instituer_site','inc');

	echo debut_grand_cadre(true);
	echo afficher_hierarchie($id_rubrique);
	echo fin_grand_cadre(true);

	echo debut_gauche('', true);
	echo debut_boite_info(true);
	echo pipeline ('boite_infos', array('data' => '',
		'args' => array(
			'type'=>'site',
			'id' => $id_syndic,
			'row' => $row
			)
	));
	echo fin_boite_info(true);
	echo $iconifier('id_syndic', $id_syndic, 'sites', false, $flag_administrable);

	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'sites','id_syndic'=>$id_syndic),'data'=>''));

	echo creer_colonne_droite('', true);
	echo $meme_rubrique($id_rubrique, $id_syndic, 'syndic');
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'sites','id_syndic'=>$id_syndic),'data'=>''));

	echo bloc_des_raccourcis(
		icone_horizontale(_T('icone_voir_sites_references'), generer_url_ecrire("sites_tous",""), "site-24.gif","rien.gif", false)
	);


	echo debut_droite('', true);

	if ($syndication == 'off' OR $syndication == 'sus')
	  $droit = $id_rubrique;
	else $droit = 0;

	$url_affichee = $url_site;
	if (strlen($url_affichee) > 40) $url_affichee = substr($url_affichee, 0, 30)."...";

	$actions =
	 ($flag_editable ? icone_inline(_T('icone_modifier_site'), generer_url_ecrire('sites_edit',"id_syndic=$id_syndic"), "site-24.gif", "edit.gif",$spip_lang_right) : "");

	$haut =
		"<div class='bandeau_actions'>$actions</div>".
		gros_titre($nom_site?$nom_site:"("._T('info_sans_titre_2').")", '' , false)
	  . "<a href='$url_site' class='url_site'>$url_affichee</a>";

	$type = 'site';
	$contexte = array('id'=>$id_syndic,'id_rubrique'=>$id_rubrique);
	$fond = recuperer_fond("prive/contenu/$type",$contexte);
	// permettre aux plugin de faire des modifs ou des ajouts
	$fond = pipeline('afficher_contenu_objet',
			array(
			'args'=>array(
				'type'=>$type,
				'id_objet'=>$id_syndic,
				'contexte'=>$contexte),
			'data'=> $fond));
	
	$fond = "<div id='wysiwyg'>$fond</div>";

	$onglet_contenu =
		(_INTERFACE_ONGLETS?
		($statut == 'prop' ? "<p class='site_prop'>"._T('info_site_propose')." <b>".affdate($date_heure)."&nbsp;</b></p>" : "")
		 . $fond:"")

		. (($syndication == "oui" OR $syndication == "off" OR $syndication == "sus") ?
		  "<p class='site_syndique'><a href='".htmlspecialchars($url_syndic)."'>"
		  .	http_img_pack('feed.png', 'RSS').	'</a>'._T('info_site_syndique').'</p>'

			. (($syndication == "off" OR $syndication=="sus") ?
			  "<div class='site_syndique_probleme'>" . _T('avis_site_syndique_probleme', array('url_syndic' => quote_amp($url_syndic)))
			  . redirige_action_auteur('editer_site', $id_syndic, 'sites', '',
			    "<input type='hidden' name='reload' value='oui' />
			    <input type='submit' value=\""
				  . attribut_html(_T('lien_nouvelle_recuperation'))
				  . "\" class='spip_xx-small' />")
				. "</div>"
			  : "")

			. afficher_objets('syndic_article',_T('titre_articles_syndiques'), array('FROM' => 'spip_syndic_articles', 'WHERE' => "id_syndic=$id_syndic", 'ORDER BY' => "date DESC"), $id_syndic)

			. ($date_syndic ? "<div class='date_syndic'>" . _T('info_derniere_syndication').' '.affdate_heure($date_syndic) .".</div>" : "")
			. "<div class='mise_a_jour_syndic'>"
			. redirige_action_post('editer_site', $id_syndic, 'sites', "id_syndic=$id_syndic",
				"<input type='hidden' name='reload' value='oui' />
				<input type='submit' value=\""
				. attribut_html(_T('lien_mise_a_jour_syndication'))
				. "\" class='spip_xx-small' />")
			. "</div>"

			: choix_feed($id_syndic, $id_rubrique, $nom_site, $row))
		. (_INTERFACE_ONGLETS?"":($flag_administrable ? options_moderation($row) : ""))

	  ;

	$onglet_proprietes =
		(_INTERFACE_ONGLETS?"":
		$fond
		. ($statut == 'prop' ? "<p class='site_prop'>"._T('info_site_propose')." <b>".affdate($date_heure)."&nbsp;</b></p>" : "")
		)
		. afficher_site_rubrique($id_syndic, $id_rubrique, $id_secteur)
		. ($dater ? $dater($id_syndic, $flag_editable, $statut, 'syndic', 'sites', $date_heure) : "")
	  . $editer_mots('syndic', $id_syndic,  $cherche_mot,  $select_groupe, $flag_editable, true, 'sites')
	  . (_INTERFACE_ONGLETS?($flag_administrable ? options_moderation($row) : ""):"")
	  . pipeline('affiche_milieu',array('args'=>array('exec'=>'sites','id_syndic'=>$id_syndic),'data'=>''))
	  ;

	$discuter = charger_fonction('discuter', 'inc');
	$onglet_discuter = $discuter($id_syndic, 'sites', 'id_syndic');
	$onglet_documents = "" ;
	$onglet_interactivite = "";

	echo
	  "<div class='fiche_objet'>"
	  . $haut
	  . afficher_onglets_pages(array(
	  	'voir' => _T('onglet_contenu'),
	  	'props' => _T('onglet_proprietes'),
	  	'docs' => _T('onglet_documents'),
	  	'interactivite' => _T('onglet_interactivite'),
	  	'discuter' => _T('onglet_discuter')),
	  _INTERFACE_ONGLETS?
	  array(
	    'voir'=>$onglet_contenu,
	    'props'=>$onglet_proprietes,
	    'docs'=>$onglet_documents,
	    'interactivite'=>$onglet_interactivite,
	    'discuter'=>$onglet_discuter)
	  :array(
	    'props'=>$onglet_proprietes,
	    'voir'=>$onglet_contenu	    )
	   )
	  . "</div>"
	  . (_INTERFACE_ONGLETS?"":$onglet_discuter);
}

// http://doc.spip.org/@options_moderation
function options_moderation($row) {

	global $spip_lang_left;

	if ($row['syndication'] == 'non') return '';

	$id_syndic = $row['id_syndic'];
	$moderation = $row['moderation'];
	if ($moderation != 'oui') $moderation='non';

	$res = '';
	$res .= "<div style='text-align: ".$GLOBALS['spip_lang_left']."'>".
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
		. "\n<div style='text-align:".$GLOBALS['spip_lang_left']."'>"._T('syndic_choix_oublier'). '</div>'
		. "\n<ul style='text-align:".$GLOBALS['spip_lang_left']."'>\n";

	$on = array('oui' => _T('item_oui'), 'non' => _T('item_non'));
	if (!$miroir = $row['miroir'])
		$miroir = 'non';

	$res .= "\n<li>"._T('syndic_option_miroir').' '
	  . afficher_choix('miroir', $miroir, $on, " &nbsp; ")
	  . "</li>\n";

	if (!$oubli = $row['oubli'])
		$oubli = 'non';
	$res .= "\n<li>"
	  . _T('syndic_option_oubli', array('mois' => 2)).' '
	  . afficher_choix('oubli', $oubli, $on," &nbsp; ")
	  . "</li>\n"
	  . "</ul>\n";

	// Prendre les resumes ou le texte integral ?
	if (!$resume = $row['resume'])
		$resume = 'oui';

	$res .= "\n<div style='text-align: $spip_lang_left'>"
	  .  _T('syndic_choix_resume')
	  . "\n<div style='padding-$spip_lang_left: 40px;'>"
	  . afficher_choix('resume', $resume,
	    array(	'oui' => _T('syndic_option_resume_oui'),
	      'non' => _T('syndic_option_resume_non')	))
	  . "</div></div>\n";

	// Bouton "Valider"
	$res .= "\n<div style='text-align:".$GLOBALS['spip_lang_right'].";'><input type='submit' value='"._T('bouton_valider')."' /></div>\n";

	return
	  debut_cadre_relief('feed.png', true, "", _T('syndic_options').aide('artsyn'))
	  . redirige_action_post('editer_site', "options/$id_syndic", 'sites', '', $res)
	 .  fin_cadre_relief(true);
}

// Site pour lesquels feedfinder a un ou plusieurs flux,
// et l'on propose de choisir

// http://doc.spip.org/@choix_feed
function choix_feed($id_syndic, $id_rubrique, $nom_site, $row) {

	global $spip_lang_left, $spip_lang_right;

	if (!preg_match(',^\s*select: (.*),', $row['url_syndic'], $regs))
		return '';

	$url_site = $row["url_site"];
	$descriptif = $row["descriptif"];
	$statut = $row["statut"];

	$date_heure = $row["date"];
	$date_syndic = $row['date_syndic'];
	$mod = $row['moderation'];
	$extra=$row["extra"];

	$res = "";

	foreach (array('id_rubrique', 'nom_site', 'url_site', 'descriptif', 'statut')	as $var) {
			$res .= "<input type='hidden' name='$var' value=\"".entites_html($$var)."\" />\n";
	}
	$res .= "<div style='text-align: $spip_lang_left'>\n";
	$res .= "<div><input type='radio' name='syndication' value='non' id='syndication_non' checked='checked' />";
	$res .= " <b><label for='syndication_non'>"._T('bouton_radio_non_syndication')."</label></b></div>\n";
	$res .= "<div><input type='radio' name='syndication' value='oui' id='syndication_oui' />";
	$res .= " <label for='syndication_oui'>"._T('bouton_radio_syndication')."</label></div>\n";

	$res .= "<select name='url_syndic' id='url_syndic'>\n";
	foreach (explode(' ',$regs[1]) as $feed) {
			$res .= '<option value="'.entites_html($feed).'">'.$feed."</option>\n";
	}
	$res .= "</select>\n";
	$res .= aide("rubsyn");
	$res .= "<div style='text-align: $spip_lang_right'><input type='submit' value='"._T('bouton_valider')."' /></div>\n";
	$res .= "</div>\n";

	$res = redirige_action_post('editer_site', $id_syndic, 'sites','', $res);
		
	return debut_cadre_relief('', true) . $res . fin_cadre_relief(true);
}

// http://doc.spip.org/@afficher_site_rubrique
function afficher_site_rubrique($id_syndic, $id_rubrique, $id_secteur)
{
	global $spip_lang_right;

	if (!_INTERFACE_ONGLETS) return "";

	$chercher_rubrique = charger_fonction('chercher_rubrique', 'inc');

	$form = $chercher_rubrique($id_rubrique, 'site', false);
	if (strpos($form,'<select')!==false) {
		$form .= "<div style='text-align: $spip_lang_right;'>"
			. '<input type="submit" value="'._T('bouton_choisir').'"/>'
			. "</div>";
	}

	$msg = _T('titre_cadre_interieur_rubrique');

	$form = "<input type='hidden' name='editer_article' value='oui' />\n" . $form;
	$form = redirige_action_post("editer_site", $id_syndic, 'sites', $form, " class='submit_plongeur'");

	if ($id_rubrique == 0) $logo = "racine-site-24.gif";
	elseif ($id_secteur == $id_rubrique) $logo = "secteur-24.gif";
	else $logo = "rubrique-24.gif";

	return debut_cadre_couleur($logo, true, "", $msg) . $form .fin_cadre_couleur(true);
}
?>
