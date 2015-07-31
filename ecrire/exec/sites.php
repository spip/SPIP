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
include_spip('inc/date');
include_spip('inc/config');

// http://doc.spip.org/@exec_sites_dist
function exec_sites_dist()
{
	$id_syndic = intval(_request('id_syndic'));

	if (!autoriser('voir','site',$id_syndic)){
		include_spip('inc/minipres');
		echo minipres();
		exit;
	}

	$result = spip_query("SELECT * FROM spip_syndic WHERE id_syndic=$id_syndic");

	if ($row = sql_fetch($result)) {
		$id_rubrique = $row["id_rubrique"];
		$nom_site = $row["nom_site"];
		$titre_page = "&laquo; $nom_site &raquo;";
	} else {
		$id_syndic = $id_rubrique = $nom_site = '';
		$titre_page = _T('info_site');
	}

	pipeline('exec_init',array('args'=>array('exec'=>'sites','id_syndic'=>$id_syndic),'data'=>''));

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page("$titre_page","naviguer","sites", $id_rubrique);

	if (!$id_syndic)
	  echo _T('public:aucun_site'); 
	else 
		afficher_site($id_syndic, $id_rubrique, $nom_site, $row);
	echo fin_page();
}


// http://doc.spip.org/@afficher_site
function afficher_site($id_syndic, $id_rubrique, $nom_site, $row){

	global $spip_lang_left,  $spip_lang_right, $spip_display;

	$cherche_mot = _request('cherche_mot');
	$select_groupe = _request('select_groupe');
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

	if ($id_syndic AND $flag_administrable AND ($spip_display != 4))
		$iconifier = charger_fonction('iconifier', 'inc');
	if ($flag_editable AND ($statut == 'publie'))
		$dater = charger_fonction('dater', 'inc');
	$editer_mot = charger_fonction('editer_mot', 'inc');
	if ($flag_administrable)
		$instituer_site = charger_fonction('instituer_site','inc');
	if ($GLOBALS['champs_extra'] AND $extra)
		include_spip('inc/extra');

	$logo = '';
 	$chercher_logo = ($spip_display != 1 AND $spip_display != 4 AND $GLOBALS['meta']['image_process'] != "non");
	if ($chercher_logo) {
		$chercher_logo = charger_fonction('chercher_logo', 'inc');
		if ($logo = $chercher_logo($id_syndic, 'id_syndic', 'on')) {
			list($fid, $dir, $nom, $format) = $logo;
			include_spip('inc/filtres_images');
			$logo = image_reduire("<img src='$fid' alt='' />", 75, 60);
		}
	}

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

	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'sites','id_syndic'=>$id_syndic),'data'=>''));

	echo creer_colonne_droite('', true);
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'sites','id_syndic'=>$id_syndic),'data'=>''));

	echo debut_droite('', true);

	if ($syndication == 'off' OR $syndication == 'sus') 
	  $droit = $id_rubrique;
	else $droit = 0;

	$url_affichee = $url_site;
	if (strlen($url_affichee) > 40) $url_affichee = substr($url_affichee, 0, 30)."...";

	$actions = 
		voir_en_ligne('site', $id_syndic, $statut, 'racine-24.gif', false)
	 . ($flag_editable ? icone_inline(_T('icone_modifier_site'), generer_url_ecrire('sites_edit',"id_syndic=$id_syndic"), "site-24.gif", "edit.gif",$spip_lang_right) : "")
	 . icone_inline(_T('icone_voir_sites_references'), generer_url_ecrire("sites_tous",""), "site-24.gif","rien.gif", $spip_lang_left)
	 . icone_inline (_T('icone_poster_message'), generer_url_ecrire('forum_envoi', "id=$id_syndic&statut=prive&script=sites") . '#formulaire', "forum-interne-24.gif", "creer.gif", $spip_lang_left)
	 . "<div class='nettoyeur'></div>";

	$haut =
		($logo ? "<div class='logo_titre'>$logo</div>" : "")
		. gros_titre($nom_site, '' , false)
	  . "<a href='$url_site' class='url_site'>$url_affichee</a>"
		. "<div class='bandeau_actions'>$actions</div>";

	$onglet_contenu = array(_L('Contenu'),
		($statut == 'prop' ? "<p class='site_prop'>"._T('info_site_propose')." <b>".affdate($date_heure)."&nbsp;</b></p>" : "")

		. (strlen($descriptif) > 1 ? 
		  "<span class='label'>"._T('info_descriptif')."</span>"
		  . "<span  dir='$lang_dir' class='descriptif crayon rubrique-descriptif-$id_rubrique'>" . propre($descriptif) . "</span>\n" :"")

		. (($syndication == "oui" OR $syndication == "off" OR $syndication == "sus") ?
		  "<p class='site_syndique'><a href='".htmlspecialchars($url_syndic)."'>"
		  .	http_img_pack('feed.png', 'RSS').	'</a>'._T('info_site_syndique').'</p>' 

			. (($syndication == "off" OR $syndication=="sus") ?
			  "<div class='site_syndique_probleme'>" . _T('avis_site_syndique_probleme', array('url_syndic' => quote_amp($url_syndic)))
			  . redirige_action_auteur('editer_site',
					$id_syndic,
			    'sites',
			    '',
			    "<input type='hidden' name='reload' value='oui' />
			    <input type='submit' value=\""
				  . attribut_html(_T('lien_nouvelle_recuperation'))
				  . "\" class='fondo spip_xx-small' />")
				. "</div>"
			  : "")
		  
			. afficher_objets('syndic_article',_T('titre_articles_syndiques'), array('FROM' => 'spip_syndic_articles', 'WHERE' => "id_syndic=$id_syndic", 'ORDER BY' => "date DESC"), $id_syndic)
			
			. ($date_syndic ? "<div class='date_syndic'>" . _T('info_derniere_syndication').' '.affdate_heure($date_syndic) .".</div>" : "")
			. "<div class='mise_a_jour_syndic'>" 
			. generer_action_auteur('editer_site',
				$id_syndic,
				generer_url_ecrire('sites'),
				"<input type='hidden' name='reload' value='oui' />
				<input type='submit' value=\""
				. attribut_html(_T('lien_mise_a_jour_syndication'))
				. "\" class='fondo spip_xx-small' />", " method='post'")
			. "</div>"
			
			: choix_feed($id_syndic, $id_rubrique, $nom_site, $row))

		. (($GLOBALS['champs_extra'] AND $extra) ? extra_affichage($extra, "sites") : "")

	  );

	$onglet_proprietes = array(_L('Propri&eacute;t&eacute;s'),
		($dater ? $dater($id_syndic, $flag_editable, $statut, 'syndic', 'sites', $date_heure) : "")
	  . $editer_mot('syndic', $id_syndic,  $cherche_mot,  $select_groupe, $flag_editable)
	  . ($flag_administrable ? options_moderation($row) : "")
	  . pipeline('affiche_milieu',array('args'=>array('exec'=>'sites','id_syndic'=>$id_syndic),'data'=>''))
	  );

	$onglet_documents = array(_L('Documents'),
	  $iconifier ? $iconifier('id_syndic', $id_syndic, 'sites') :""
	  //. articles_documents('article', $id_article)
	  );
	
	$onglet_interactivite = array(_L('Interactivit&eacute;'),
		""
	);
		
	$onglet_discuter = array(_L('Discuter'),
    ($result_forum = spip_query("SELECT * FROM spip_forum WHERE statut='prive' AND id_syndic=$id_syndic AND id_parent=0 ORDER BY date_heure DESC LIMIT 20") ?
    	afficher_forum($result_forum, "sites","id_syndic=$id_syndic")
    	:"")
		);

	echo 
	  $haut 
	  . afficher_onglets_pages(array(
	    //'resume'=>$onglet_resume,
	    'voir'=>$onglet_contenu,
	    'props'=>$onglet_proprietes,
	    'docs'=>$onglet_documents,
	    'interactivite'=>$onglet_interactivite,	    
	    'discuter'=>$onglet_discuter));
}

function options_moderation($row) {
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
	$res .= "\n<div style='text-align:$spip_lang_right'><input type='submit' value='"._T('bouton_valider')."' class='fondo' /></div>\n";
	
	return
	  debut_cadre_relief('feed.png', true, "", _T('syndic_options').aide('artsyn'))
	  . redirige_action_auteur('editer_site',
					 "options/$id_syndic",
					 'sites',
					 '',
					 $res,
					 " method='post'")
	 .  fin_cadre_relief(true);
}

function choix_feed($id_syndic, $id_rubrique, $nom_site, $row) {
	$url_site = $row["url_site"];
	$url_syndic = $row["url_syndic"];
	$descriptif = $row["descriptif"];
	$statut = $row["statut"];
	
	$date_heure = $row["date"];
	$date_syndic = $row['date_syndic'];
	$mod = $row['moderation'];
	$extra=$row["extra"];

	$res = "";
	// Cas d'un site pour lesquels feedfinder a un ou plusieurs flux,
	// et l'on propose de choisir
	if (preg_match(',^\s*select: (.*),', $url_syndic, $regs)) {
			foreach (
				array('id_rubrique', 'nom_site', 'url_site', 'descriptif', 'statut')	as $var) {
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
		$res .= "<div style='text-align: $spip_lang_right'><input type='submit' value='"._T('bouton_valider')."' class='fondo' /></div>\n";
		$res .= "</div>\n";
		
		$res =
		  debut_cadre_relief('', true)
		  . redirige_action_auteur('editer_site',
			$id_syndic,
			'sites',
			'',
			$res,
			" method='post'")
			. fin_cadre_relief(true);
	}
	return $res;
}
?>
