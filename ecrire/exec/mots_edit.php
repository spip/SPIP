<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/presentation');
include_spip('base/abstract_sql');

// http://doc.spip.org/@exec_mots_edit_dist
function exec_mots_edit_dist()
{
	exec_mots_edit_args(intval(_request('id_mot')),
		       intval(_request('id_groupe')),
		       _request('new'),
 // Secu un peu superfetatoire car seuls les admin generaux les verront;
 // mais si un jour on relache les droits, vaut mieux blinder.
		       preg_replace('/\W/','',_request('table')),
		       preg_replace('/\W/','', _request('table_id')),
		       _request('titre'),
		       _request('redirect'),
		       intval(_request('ajouter_id_article')));
}

// attention, ajouter_id_article n'est pas forcement un id d'article

// http://doc.spip.org/@exec_mots_edit_args
function exec_mots_edit_args($id_mot, $id_groupe, $new, $table='', $table_id='', $titre='', $redirect='', $ajouter_id_article='')
{
	global $spip_lang_right, $connect_statut, $spip_display, $les_notes;

	$autoriser_editer = ($new=='oui');
	$editer = ($new=='oui') OR $autoriser_editer;
	$ok = false;
	
	$row = sql_fetsel("*", "spip_mots", "id_mot=$id_mot");
	if ($row) {
		$id_mot = $row['id_mot'];
		$titre_mot = $row['titre'];
		$descriptif = $row['descriptif'];
		$texte = $row['texte'];
		$extra = $row['extra'];
		$id_groupe = $row['id_groupe'];
		$onfocus ='';
		$autoriser_editer = autoriser('modifier', 'mot', $id_mot, null, array('id_groupe' => $id_groupe));
		if (!_request('edit'))
			$editer = false;
		else
			$editer = $autoriser_editer;
		$ok = true;
	}
	else {
		if (!$new OR !autoriser('modifier', 'mot', $id_mot, null, array('id_groupe' => $id_groupe))) {
			include_spip('inc/minipres');
			echo minipres(_T('info_mot_sans_groupe'));
		} else {
			$id_mot = 0;
			$descriptif = $texte = '';
			if (!$titre_mot = $titre) {
				$titre_mot = filtrer_entites(_T('texte_nouveau_mot'));
				$onfocus = " onfocus=\"if(!antifocus){this.value='';antifocus=true;}\"";
			}
			$row = sql_countsel('spip_groupes_mots', ($table ? "$table='oui'" : ''));
			if (!$row) {
		  // cas pathologique: 
		  // creation d'un mot sans groupe de mots cree auparavant
		  // (ne devrait arriver qu'en cas d'appel explicite ou
		  // destruction concomittante des groupes de mots idoines)
				if ($redirect)
					$redirect = '&redirect=' . $redirect;
				if ($titre)
					$titre = "&titre=".rawurlencode($titre);
				include_spip('inc/headers');
				redirige_par_entete(redirige_action_auteur('instituer_groupe_mots', $table, 'mots_edit', "new=$new&table=$table&table_id=$table_id&ajouter_id_article=$ajouter_id_article$titre$redirect", true));
			}
			$ok = true;
		}
	}
	if ($ok) {
		pipeline('exec_init',array('args'=>array('exec'=>'mots_edit','id_mot'=>$id_mot),'data'=>''));
		
		$commencer_page = charger_fonction('commencer_page', 'inc');
		$out = $commencer_page("&laquo; $titre_mot &raquo;", "naviguer", "mots") . debut_gauche('',true);


		//////////////////////////////////////////////////////
		// Boite "voir en ligne"
		//

		if ($id_mot) {
			$out .= debut_boite_info(true);
			$out .= "\n<div style='font-weight: bold; text-align: center' class='verdana1 spip_xx-small'>" 
			.  _T('titre_gauche_mots_edit')
			.  "<br /><span class='spip_xx-large'>"
			.  $id_mot
			.  '</span></div>';
			$out .= voir_en_ligne ('mot', $id_mot, false, 'racine-24.gif', false, false);
			$out .= fin_boite_info(true);
			
			// Logos du mot-clef
			$flag_editable = autoriser('modifier', 'mot', $id_mot, null, array('id_groupe' => $id_groupe));
			$iconifier = charger_fonction('iconifier', 'inc');
			$out .= $iconifier('id_mot', $id_mot, 'mots_edit', $flag_editable);
		}

		//
		// Afficher les boutons de creation 
		//

		$res ='';
		
		if ($id_groupe AND autoriser('modifier','groupemots',$id_groupe)) {
			$res = icone_horizontale(_T('icone_modif_groupe_mots'), generer_url_ecrire("mots_type","id_groupe=$id_groupe"), "groupe-mot-24.gif", "edit.gif", false)
			  . icone_horizontale(_T('icone_creation_mots_cles'), generer_url_ecrire("mots_edit", "new=oui&id_groupe=$id_groupe&redirect=" . generer_url_retour('mots_tous')),  "mot-cle-24.gif",  "creer.gif", false);
		}

	$out .= pipeline('affiche_gauche',array('args'=>array('exec'=>'mots_edit','id_mot'=>$id_mot),'data'=>''));
	$out .= bloc_des_raccourcis($res . icone_horizontale(_T('icone_voir_tous_mots_cles'), generer_url_ecrire("mots_tous",""), "mot-cle-24.gif", "rien.gif", false));
	$out .= creer_colonne_droite('',true);
	$out .= pipeline('affiche_droite',array('args'=>array('exec'=>'mots_edit','id_mot'=>$id_mot),'data'=>''));
	$out .= debut_droite('',true);

	
	// --- Voir le mot ----
	
	$out .= debut_cadre_relief("mot-cle-24.gif",true,'','','mot-voir',$editer?'none':'');
	$out .= icone_inline(_T('icone_modifier_mot'), generer_url_ecrire('mots_edit',"id_mot=$id_mot&edit=oui"), "mot-cle-24.gif", "rien.gif",$spip_lang_right,false," onclick=\"$('#mot-editer').show();$('#mot-voir').hide();return false;\"");
	$out .= gros_titre($titre_mot,'',false);
	$out .= "<div class='nettoyeur'></div>";

	if ($descriptif) {
		$out .= "<div style='border: 1px dashed #aaaaaa; ' class='verdana1 spip_small'>";
		$out .= "<b>" . _T('info_descriptif') . "</b> ";
		$out .= propre($descriptif);
		$out .= "&nbsp; ";
		$out .= "</div>";
	}

	if (strlen($texte)>0){
		$out .= "<p class='verdana1 spip_small'>";
		$out .= propre($texte);
		$out .= "</p>";
	}

	if ($les_notes) {
		$out .= debut_cadre_relief('',true);
		$out .= "<div dir='" . lang_dir() ."' class='arial11'>";
		$out .= justifier("<b>"._T('info_notes')."&nbsp;:</b> ".$les_notes);
		$out .= "</div>";
		$out .= fin_cadre_relief(true);
	}

	if ($id_mot) {

		if ($connect_statut == "0minirezo")
			$aff_articles = "'prepa','prop','publie','refuse'";
		else
			$aff_articles = "'prop','publie'";

		$out .= afficher_objets('rubrique','<b>' . _T('info_rubriques_liees_mot') . '</b>', array("FROM" => 'spip_rubriques AS rubrique, spip_mots_rubriques AS lien', 'WHERE' => "lien.id_mot=$id_mot AND lien.id_rubrique=rubrique.id_rubrique", 'ORDER BY' => "rubrique.titre"));

		$out .= afficher_objets('article',_T('info_articles_lies_mot'),	array('FROM' => "spip_articles AS articles, spip_mots_articles AS lien", 'WHERE' => "lien.id_mot=$id_mot AND lien.id_article=articles.id_article AND articles.statut IN ($aff_articles)", 'ORDER BY' => "articles.date DESC"));

		$out .= afficher_objets('breve','<b>' . _T('info_breves_liees_mot') . '</b>', array("FROM" => 'spip_breves AS breves, spip_mots_breves AS lien', 'WHERE' => "lien.id_mot=$id_mot AND lien.id_breve=breves.id_breve", 'ORDER BY' => "breves.date_heure DESC"));

		$out .= afficher_objets('site','<b>' . _T('info_sites_lies_mot') . '</b>', array("FROM" => 'spip_syndic AS syndic, spip_mots_syndic AS lien', 'WHERE' => "lien.id_mot=$id_mot AND lien.id_syndic=syndic.id_syndic", 'ORDER BY' => "syndic.nom_site DESC"));
	}

	$out .= fin_cadre_relief(true);

	$out .= pipeline('affiche_milieu',array('args'=>array('exec'=>'mots_edit','id_mot'=>$id_mot),'data'=>''));

	// --- Editer le mot ----
	if ($autoriser_editer){
		$out .= "<div id='mot-editer'".($editer?"":" class='none'").'>';
		$out .= debut_cadre_formulaire('',true);
		$contexte = array(
			'icone_retour'=>icone_inline(_T('icone_retour'),($editer&$redirect)?rawurldecode($redirect): generer_url_ecrire('mots_edit','id_mot='.$id_mot, '&',true), "mot-cle-24.gif", "rien.gif",$GLOBALS['spip_lang_right'],false,($editer&$redirect)?"":" onclick=\"$('#mot-editer').hide();$('#mot-voir').show();return false;\""),
			'redirect'=>$redirect?rawurldecode($redirect):generer_url_ecrire('mots_edit','id_mot='.$id_mot, '&',true),
			'titre'=>$titre_mot,
			'new'=>$new == "oui"?$new:$id_mot,
			'id_groupe'=>$id_groupe,
			'config_fonc'=>'mots_edit_config',
			'ajouter_id_article' => $ajouter_id_article,
			'table'=>$table,
			'table_id'=>$table_id
		);
		$page = evaluer_fond("prive/editer/mot", $contexte, $connect);
		$out .= $page['texte'];
		$out .= fin_cadre_formulaire(true);
		$out .= '</div>';
		

		/*$res = "<div class='serif'>";

		$titre_mot = entites_html($titre_mot);
		$descriptif = entites_html($descriptif);
		$texte = entites_html($texte);
		
		$res .= "<ol class='formfx'>";
		
		
		$res .= "<li class='gauche obligatoire'>";
		$res .= "<label for='titre'>"._T('info_titre_mot_cle');
		$res .= aide ("mots")."</label>";

		$res .= "<input type='text' name='titre' id='titre' class='formo' value=\"$titre_mot\" size='40' $onfocus /></li>";

		$res .= "<li class='gauche'>".determine_groupe_mots($table, $id_groupe)."</li>";


		$res .= "<li class='gauche'>";
		$res .= "<label for='descriptif'>"._T('texte_descriptif_rapide')."</label>";
		$res .= "<textarea name='descriptif' id='descriptif' class='forml' rows='4' cols='40'>";
		$res .= $descriptif;
		$res .= "</textarea>\n";
		$res .= "</li>";

		$res .= "<li class='gauche'>";
		$res .= "<label for='texte'>"._T('info_texte_explicatif')."</label>";
		$res .= "<textarea name='texte' id='texte' rows='12' class='forml' cols='40'>";
		$res .= $texte;
		$res .= "</textarea>";
		$res .= "</li>";

		if ($GLOBALS['champs_extra']) {
			include_spip('inc/extra');
			$res .= extra_saisie($extra, 'mots', $id_groupe);
		}

		// Ajouter le controles md5
		if (intval($id_mot)) {
			include_spip('inc/editer');
			$res .= controles_md5($row);
		}
		

		$res .= "<div style='text-align: right'><input type='submit' value='"._T('bouton_enregistrer')."' class='fondo' /></div>";
		
		$res .= "</ol>";
	
	
		$res .= "</div>";

		if (!$redirect)
			$redirect = generer_url_ecrire('mots_edit','id_mot='.$id_mot, '&',true);
		else
			$redirect = rawurldecode($redirect);
		$arg = !$table ? $id_mot : "$id_mot,$ajouter_id_article,$table,$table_id";

		$out .= debut_cadre_formulaire('',true)
			. generer_action_auteur("instituer_mot", $arg, _DIR_RESTREINT_ABS . $redirect, $res, " method='post'")
			. fin_cadre_formulaire(true);*/
	}

	echo $out, fin_gauche(), fin_page();
	}
}


// http://doc.spip.org/@determine_groupe_mots
/*function determine_groupe_mots($table, $id_groupe) {

	$q = sql_select('id_groupe, titre', 'spip_groupes_mots', ($table ? "$table='oui'" : ''),'', "titre");

	if (sql_count($q)>1) {

		$res = " &nbsp; <select name='id_groupe' id='id_groupe' class='fondl'>\n";
		while ($row = sql_fetch($q)){
			$groupe = $row['id_groupe'];
			$titre_groupe = texte_backend(supprimer_tags(typo($row['titre'])));
			$res .=  "<option".mySel($groupe, $id_groupe).">$titre_groupe</option>\n";
		}			
		$res .=  "</select>";
	} else {
	  // pas de menu si un seul groupe 
	  // (et on est sur qu'il y en a un grace au redirect preventif)
		$row = sql_fetch($q);
		$res = $row['titre']
		. "<br /><input type='hidden' name='id_groupe' id='id_groupe' value='".$row['id_groupe']."' />";
	}

	return _T('info_dans_groupe')
	. aide("motsgroupes")
	. debut_cadre_relief("groupe-mot-24.gif", true)
	. $res
	. fin_cadre_relief(true);
}*/
?>
