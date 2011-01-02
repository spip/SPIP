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
	global $spip_lang_right, $connect_statut, $les_notes;

	$autoriser_editer = $editer = false;
	if ($new=='oui')
		$autoriser_editer = true;
	if (($new=='oui') OR $autoriser_editer)
		$editer = true;
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
		$row = sql_countsel('spip_groupes_mots', 
			($table ? "tables_liees REGEXP '(^|,)$table($|,)'" : '')
			//($table ? "$table='oui'" : '')
				    );

		if (!$new OR !autoriser('modifier', 'mot', $id_mot, null, array('id_groupe' => $id_groupe)) OR (!$row AND !$table)) {
			include_spip('inc/minipres');
			echo minipres(_T('info_mot_sans_groupe'));
		} else {
			$id_mot = 0;
			$descriptif = $texte = '';
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
		$out = $commencer_page("&laquo; ".sinon($titre_mot,_T('texte_nouveau_mot'))." &raquo;", "naviguer", "mots") . debut_gauche('',true);


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
			$out .= $iconifier('id_mot', $id_mot, 'mots_edit', false, $flag_editable);
		} else $flag_editable = false;

		//
		// Afficher les boutons de creation 
		//

		$res ='';
		
		if ($id_groupe AND autoriser('modifier','groupemots',$id_groupe)) {
			$res = icone_horizontale(_T('icone_modif_groupe_mots'), generer_url_ecrire("mots_type","id_groupe=$id_groupe"), "groupe-mot-24.gif", "edit.gif", false)
			  . icone_horizontale(_T('icone_creation_mots_cles'), generer_url_ecrire("mots_edit", "new=oui&id_groupe=$id_groupe&redirect=" . generer_url_retour('mots_tous')),  "mot-cle-24.gif",  "creer.gif", false);
		}

	$out .= pipeline('affiche_gauche',array('args'=>array('exec'=>'mots_edit','id_mot'=>$id_mot),'data'=>''))
	.  bloc_des_raccourcis($res . icone_horizontale(_T('icone_voir_tous_mots_cles'), generer_url_ecrire("mots_tous",""), "mot-cle-24.gif", "rien.gif", false))
	.  creer_colonne_droite('',true)
	.  pipeline('affiche_droite',array('args'=>array('exec'=>'mots_edit','id_mot'=>$id_mot),'data'=>''))
	.  debut_droite('',true);

	
	// --- Voir le mot ----
	
	$out .= debut_cadre_relief("mot-cle-24.gif",true,'','','mot-voir',$editer?'none':'');
	if ($flag_editable)
		$out .= icone_inline(_T('icone_modifier_mot'), generer_url_ecrire('mots_edit',"id_mot=$id_mot&edit=oui"), "mot-cle-24.gif", "rien.gif",$spip_lang_right,false," onclick=\"$('#mot-editer').show();$('#mot-voir').hide();return false;\"");
	$out .= gros_titre(sinon($titre_mot,_T('texte_nouveau_mot')),'',false);
	$out .= "<div class='nettoyeur'></div>";
	
	$contenu_mot = "";

	if ($descriptif) {
		$contenu_mot .= "<div style='border: 1px dashed #aaaaaa; ' class='verdana1 spip_small'>"
		. "<b>" . _T('info_descriptif') . "</b> "
		. propre($descriptif)
		. "&nbsp; "
		. "</div>";
	}

	if (strlen($texte)>0){
		$contenu_mot .= "<p class='verdana1 spip_small'>"
		. propre($texte)
		. "</p>";
	}

	if ($les_notes) {
		$contenu_mot .= debut_cadre_relief('',true)
		. "<div dir='" . lang_dir() ."' class='arial11'>"
		. justifier("<b>"._T('info_notes')."&nbsp;:</b> ".$les_notes)
		. "</div>"
		. fin_cadre_relief(true);
	}
	
	$contexte = array('id'=>$id_mot);
	// permettre aux plugin de faire des modifs ou des ajouts
	$contenu_mot = pipeline('afficher_contenu_objet',
		array(
			'args'=>array(
				'type'=>'mot',
				'id_objet'=>$id_mot,
				'contexte'=>$contexte
			),
			'data'=> $contenu_mot
		)
	);
	$out .= $contenu_mot;

	if ($id_mot) {

		if ($connect_statut == "0minirezo")
			$aff_articles = "'prepa','prop','publie','refuse'";
		else
			$aff_articles = "'prop','publie'";

		$out .= afficher_objets('rubrique','<b>' . _T('info_rubriques_liees_mot') . '</b>', array("FROM" => 'spip_rubriques AS rubrique LEFT JOIN spip_mots_rubriques AS lien ON lien.id_rubrique=rubrique.id_rubrique', 'WHERE' => "lien.id_mot=$id_mot", 'ORDER BY' => "rubrique.titre"));

		$out .= afficher_objets('article',_T('info_articles_lies_mot'), array('FROM' => "spip_articles AS articles LEFT JOIN spip_mots_articles AS lien ON lien.id_article=articles.id_article", 'WHERE' => "lien.id_mot=$id_mot AND articles.statut IN ($aff_articles)", 'ORDER BY' => "articles.date DESC"));

		$out .= afficher_objets('breve','<b>' . _T('info_breves_liees_mot') . '</b>', array("FROM" => 'spip_breves AS breves LEFT JOIN spip_mots_breves AS lien ON lien.id_breve=breves.id_breve', 'WHERE' => "lien.id_mot=$id_mot", 'ORDER BY' => "breves.date_heure DESC"));

		$out .= afficher_objets('site','<b>' . _T('info_sites_lies_mot') . '</b>', array("FROM" => 'spip_syndic AS syndic LEFT JOIN spip_mots_syndic AS lien ON lien.id_syndic=syndic.id_syndic', 'WHERE' => "lien.id_mot=$id_mot", 'ORDER BY' => "syndic.nom_site DESC"));
		
	}

	$out .= pipeline('affiche_milieu',array('args'=>array('exec'=>'mots_edit','id_mot'=>$id_mot),'data'=>''))
	. fin_cadre_relief(true);

	// --- Editer le mot ----

	if ($autoriser_editer){
		$out .= "<div id='mot-editer'".($editer?"":" class='none'").'>';
		$contexte = array(
			'icone_retour'=>icone_inline(_T('icone_retour'),($editer AND $redirect)?rawurldecode($redirect): generer_url_ecrire('mots_edit','id_mot='.$id_mot,false,true), "mot-cle-24.gif", "rien.gif",$GLOBALS['spip_lang_left'],false,($editer AND $redirect)?"":" onclick=\"$('#mot-editer').hide();$('#mot-voir').show();return false;\""),
			'redirect'=>$redirect?rawurldecode($redirect):generer_url_ecrire('mots_edit','id_mot='.$id_mot,'&',true),
			'titre'=>sinon($titre_mot,$titre),
			'new'=>$new == "oui"?$new:$id_mot,
			'id_groupe'=>$id_groupe,
			'config_fonc'=>'mots_edit_config',
			'ajouter_id_article' => $ajouter_id_article,
			'table'=>$table,
			'table_id'=>$table_id
		);

		$out .= recuperer_fond("prive/editer/mot", $contexte);
		$out .= '</div>';

	}

	echo $out, fin_gauche(), fin_page();
	}
}

?>
