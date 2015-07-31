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
include_spip('inc/actions');

// http://doc.spip.org/@exec_articles_dist
function exec_articles_dist()
{
	$id_article= intval(_request('id_article'));

	pipeline('exec_init',array('args'=>array('exec'=>'articles','id_article'=>$id_article),'data'=>''));

	$row = sql_fetch(spip_query("SELECT * FROM spip_articles WHERE id_article=$id_article"));

	if (!$row
	OR !autoriser('voir', 'article', $id_article)) {
		$res = $row['titre'] = _T('public:aucun_article');
		$row['id_rubrique'] = 0;
	} else {
		$row['titre'] = sinon($row["titre"],_T('info_sans_titre'));

		$res = debut_gauche('accueil',true)
		.  articles_affiche($id_article, $row, _request('cherche_auteur'), _request('ids'), _request('cherche_mot'), _request('select_groupe'), _request('trad_err'))
		  . "<br /><br /><div class='centered'>"
		. "</div>"
		. fin_gauche()
;
	}

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page("&laquo; ". $row['titre'] ." &raquo;", "naviguer", "articles", $row['id_rubrique']);

	echo debut_grand_cadre(true),
		afficher_hierarchie($row['id_rubrique']),
		fin_grand_cadre(true),
		$res,
		fin_page();
}

// http://doc.spip.org/@articles_affiche
function articles_affiche($id_article, $row, $cherche_auteur, $ids, $cherche_mot,  $select_groupe, $trad_err)
{
	global $spip_display, $spip_lang_left, $spip_lang_right, $connect_id_auteur;

	$id_rubrique = $row['id_rubrique'];
	$statut_article = $row['statut'];
	$titre = $row["titre"];
	$surtitre = $row["surtitre"];
	$soustitre = $row["soustitre"];
	$descriptif = $row["descriptif"];
	$nom_site = $row["nom_site"];
	$url_site = $row["url_site"];
	$chapo = $row["chapo"];
	$texte = $row["texte"];
	$ps = $row["ps"];
	$date = $row["date"];
	$date_redac = $row["date_redac"];
	$extra = $row["extra"];
	$id_trad = $row["id_trad"];
	
	if (substr($chapo, 0, 1) != '=')
	  $virtuel ='';
	else {
	  $virtuel = chapo_redirige(substr($chapo, 1));
	  $virtuel = $virtuel[3];
	}

	$statut_rubrique = autoriser('publierdans', 'rubrique', $id_rubrique);
	$flag_editable = autoriser('modifier', 'article', $id_article);

	// Est-ce que quelqu'un a deja ouvert l'article en edition ?
	if ($flag_editable
	AND $GLOBALS['meta']['articles_modif'] != 'non') {
		include_spip('inc/drapeau_edition');
		$modif = mention_qui_edite($id_article, 'article');
	} else
		$modif = array();


 // chargement prealable des fonctions produisant des formulaires

	$dater = charger_fonction('dater', 'inc');
	$editer_mot = charger_fonction('editer_mot', 'inc');
	$editer_auteurs = charger_fonction('editer_auteurs', 'inc');
	$referencer_traduction = charger_fonction('referencer_traduction', 'inc');
	$discuter = charger_fonction('discuter', 'inc');

	$logo = '';
 	$chercher_logo = ($spip_display != 1 AND $spip_display != 4 AND $GLOBALS['meta']['image_process'] != "non");
	if ($chercher_logo) {
		$chercher_logo = charger_fonction('chercher_logo', 'inc');
		if ($logo = $chercher_logo($id_article, 'id_article', 'on')) {
			list($fid, $dir, $nom, $format) = $logo;
			include_spip('inc/filtres_images');
			$logo = image_reduire("<img src='$fid' alt='' />", 75, 60);
		}
	}

	if ($flag_editable AND ($spip_display != 4)) {
		$iconifier = charger_fonction('iconifier', 'inc');
		$icone = $iconifier('id_article', $id_article,'articles', true);
	} else $icone = '';

	$instituer_article = charger_fonction('instituer_article', 'inc');

	$boite = pipeline ('boite_infos', array('data' => '',
		'args' => array(
			'type'=>'article',
			'id' => $id_article,
			'row' => $row
		)
	));

	$navigation =
	  debut_boite_info(true). $boite . fin_boite_info(true)
	  . ($flag_editable ? boite_article_virtuel($id_article, $virtuel):'')
	  . meme_rubrique($id_rubrique, $id_article, 'article')
	  . pipeline('affiche_gauche',array('args'=>array('exec'=>'articles','id_article'=>$id_article),'data'=>''));
	
	$extra = creer_colonne_droite('', true)
	  . pipeline('affiche_droite',array('args'=>array('exec'=>'articles','id_article'=>$id_article),'data'=>''))
	  . debut_droite('',true);

	// affecter les globales dictant les regles de typographie de la langue
	changer_typo($row['lang']);
	
	$actions = 
		voir_en_ligne('article', $id_article, $statut_article, 'racine-24.gif', false)
	 . ($flag_editable ? bouton_modifier_articles($id_article, $id_rubrique, $modif, _T('avis_article_modifie', $modif), "article-24.gif", "edit.gif",$spip_lang_right) : "")
	 . icone_inline(_T('icone_poster_message'), generer_url_ecrire("forum_envoi", "statut=prive&id=$id_article&script=articles") ."#formulaire", "forum-interne-24.gif", "creer.gif", $spip_lang_left);
	 
	// revisions d'articles
	if (($GLOBALS['meta']["articles_versions"]=='oui')
		AND $row['id_version']>1
		AND autoriser('voirrevisions', 'article', $id_article))
			$actions .= icone_inline(_T('info_historique_lien'), generer_url_ecrire("articles_versions","id_article=$id_article"), "historique-24.gif", "rien.gif", $spip_lang_left);

	$actions .= "<div class='nettoyeur'></div>";
	
	$haut =
		($logo ? "<div class='logo_titre'>$logo</div>" : "")
		. gros_titre($titre, '' , false)
		. "<div class='bandeau_actions'>$actions</div>";

	$onglet_contenu = array(_L('Contenu'),
	  afficher_corps_articles($id_article,$virtuel,$row)
	  );

	$onglet_proprietes = array(_L('Propri&eacute;t&eacute;s'),
		afficher_article_rubrique($id_article, $id_rubrique, $id_secteur, $statut)
	  . $dater($id_article, $flag_editable, $statut_article, 'article', 'articles', $date, $date_redac)
	  . $editer_auteurs('article', $id_article, $flag_editable, $cherche_auteur, $ids)
	  . (!$editer_mot ? '' : $editer_mot('article', $id_article, $cherche_mot, $select_groupe, $flag_editable, true))
	  . (!$referencer_traduction ? '' : $referencer_traduction($id_article, $flag_editable, $id_rubrique, $id_trad, $trad_err))
	  . pipeline('affiche_milieu',array('args'=>array('exec'=>'articles','id_article'=>$id_article),'data'=>''))
	  );

	$onglet_documents = array(_L('Documents'),
	  $icone
	  . articles_documents('article', $id_article)
	  );
	
	$onglet_interactivite = array(_L('Interactivit&eacute;'),
		// statistiques
		(($row['statut'] == 'publie'
		AND $row['visites'] > 0
		AND $GLOBALS['meta']["activer_statistiques"] != "non"
		AND autoriser('voirstats', $type, $id)) ?
		  icone_horizontale(_T('icone_evolution_visites', array('visites' => $row['visites'])), generer_url_ecrire("statistiques_visites","id_article=$id"), "statistiques-24.gif","rien.gif", false)
		  : "")
	  . boites_de_config_articles($id_article)
		);
		
	$onglet_discuter = array(_L('Discuter'),
		$discuter($id_article, false,  _request('debut'))
		);


	return 
	  $navigation
	  . $extra 
	  . $haut 
	  . afficher_onglets_pages(array(
	    //'resume'=>$onglet_resume,
	    'voir'=>$onglet_contenu,
	    'props'=>$onglet_proprietes,
	    'docs'=>$onglet_documents,
	    'interactivite'=>$onglet_interactivite,	    
	    'discuter'=>$onglet_discuter));
}

// http://doc.spip.org/@articles_documents
function articles_documents($type, $id)
{
	global $spip_lang_left, $spip_lang_right;

	// Joindre ?
	if  ($GLOBALS['meta']["documents_$type"]=='non'
	OR !autoriser('joindredocument', $type, $id))
		$res = '';
	else {
		$joindre = charger_fonction('joindre', 'inc');

		$res = $joindre(array(
			'cadre' => 'relief',
			'icone' => 'image-24.gif',
			'fonction' => 'creer.gif',
			'titre' => _T('titre_joindre_document'),
			'script' => 'articles',
			'args' => "id_article=$id",
			'id' => $id,
			'intitule' => _T('info_telecharger_ordinateur'),
			'mode' => 'document',
			'type' => 'article',
			'ancre' => '',
			'id_document' => 0,
			'iframe_script' => generer_url_ecrire("documenter","id_article=$id&type=$type",true)
		));

		// eviter le formulaire upload qui se promene sur la page
		// a cause des position:relative incompris de MSIE
		if ($GLOBALS['browser_name']!='MSIE') {
			$res = "\n<table style='float: $spip_lang_right' width='50%' cellpadding='0' cellspacing='0' border='0'>\n<tr><td style='text-align: $spip_lang_left;'>\n$res</td></tr></table>";
		}

		$res .= "<script src='"._DIR_JAVASCRIPT."async_upload.js' type='text/javascript'></script>
<script type='text/javascript'>
$(\"form.form_upload\").async_upload(async_upload_portfolio_documents);
</script>";
	}

	$documenter = charger_fonction('documenter', 'inc');

	$flag_editable = autoriser('modifier', $type, $id);

	return "<div id='portfolio'>" . $documenter($id, $type, 'portfolio') . "</div><br />"
	. "<div id='documents'>" . $documenter($id, $type, 'documents') . "</div>"
	. $res;
}

//
// Boites de configuration avancee
//

// http://doc.spip.org/@boites_de_config_articles
function boites_de_config_articles($id_article)
{
	if (autoriser('modererforum', 'article', $id_article)) {
		$regler_moderation = charger_fonction('regler_moderation', 'inc');
		$regler = $regler_moderation($id_article,"articles","id_article=$id_article") . '<br />';
	}

	if (autoriser('modererpetition', 'article', $id_article)) {
		$petitionner = charger_fonction('petitionner', 'inc');
		$petition = $petitionner($id_article,"articles","id_article=$id_article");
	}

	$masque = $regler . $petition;

	if (!$masque)
		return '';

	$invite = "<b>"
	. _T('bouton_forum_petition')
	. aide('confforums')
	. "</b>";

	return 
		cadre_depliable("forum-interne-24.gif",
		  $invite,
		  true,//$visible = strstr($masque, '<!-- visible -->')
		  $masque,
		  'forumpetition');
}

// http://doc.spip.org/@boite_article_virtuel
function boite_article_virtuel($id_article, $virtuel)
{
	if (!$virtuel
	AND $GLOBALS['meta']['articles_redirection'] != 'oui')
		return '';

	$invite = '<b>'
	._T('bouton_redirection')
	. '</b>'
	. aide ("artvirt");

	$virtualiser = charger_fonction('virtualiser', 'inc');

	return cadre_depliable("site-24.gif",
		$invite,
		$virtuel,
		$virtualiser($id_article, $virtuel, "articles", "id_article=$id_article"),
		'redirection');
}

// http://doc.spip.org/@bouton_modifier_articles
function bouton_modifier_articles($id_article, $id_rubrique, $flag_modif, $mode, $ip, $im, $align='')
{
	if ($flag_modif) {
		return icone_inline(_T('icone_modifier_article'), generer_url_ecrire("articles_edit","id_article=$id_article"), $ip, $im, $align, false)
		. "<span class='arial1 spip_small'>$mode</span>"
		. aide("artmodif");
	}
	else return icone_inline(_T('icone_modifier_article'), generer_url_ecrire("articles_edit","id_article=$id_article"), "article-24.gif", "edit.gif", $align);
}

// http://doc.spip.org/@afficher_corps_articles
function afficher_corps_articles($id_article, $virtuel, $row)
{
  global $champs_extra, $les_notes, $lang_objet;

  //$lang_dir = lang_dir($lang_objet);

	$res = '';
	if ($row['statut'] == 'prop')
		$res .= "<p class='article_prop'>"._T('text_article_propose_publication')."</p>";

	if ($virtuel) {
		$res .= debut_boite_info(true)
		.  "\n<div style='text-align: center'>"
		. _T('info_renvoi_article')
		. " "
		.  propre("[->$virtuel]")
		. '</div>'
		.  fin_boite_info(true);
	}
	else {
		$afficher_contenu_objet = charger_fonction('afficher_contenu_objet', 'inc');
		$res .= $afficher_contenu_objet('article', $id_article,$row);
	}
	return $res;
}

// http://doc.spip.org/@afficher_article_rubrique
function afficher_article_rubrique($id_article, $id_rubrique, $id_secteur, $statut)
{
	global $spip_lang_right;
	$chercher_rubrique = charger_fonction('chercher_rubrique', 'inc');
	$aider = charger_fonction('aider', 'inc');

	$form = $chercher_rubrique($id_rubrique, 'article', $statut=='publie');
	if (strpos($form,'<select')!==false) {
		$form .= "<div style='text-align: $spip_lang_right;'>"
			. '<input class="fondo" type="submit" value="'._T('bouton_choisir').'"/>'
			. "</div>";
	}

	$msg = _T('titre_cadre_interieur_rubrique') .
	  ((preg_match('/^<input[^>]*hidden[^<]*$/', $form)) ? '' : $aider("artrub"));
	  
	$form = "<input type='hidden' name='editer_article' value='oui' />\n" . $form;
	$form = generer_action_auteur("editer_article", $id_article, generer_url_ecrire('articles'), $form, " method='post' name='formulaire' class='submit_plongeur'");

	if ($id_rubrique == 0) $logo = "racine-site-24.gif";
	elseif ($id_secteur == $id_rubrique) $logo = "secteur-24.gif";
	else $logo = "rubrique-24.gif";

	return debut_cadre_couleur($logo, true, "", $msg) . $form .fin_cadre_couleur(true);
}
?>
