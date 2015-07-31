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

	$row = spip_fetch_array(spip_query("SELECT * FROM spip_articles WHERE id_article=$id_article"));

	if (!$row
	OR !autoriser('voir', 'article', $id_article)) {
		$res = $row['titre'] = _T('public:aucun_article');
		$row['id_rubrique'] = 0;
	} else {
		$discuter = charger_fonction('discuter', 'inc');
		$row['titre'] = sinon($row["titre"],_T('info_sans_titre'));

		$res = debut_gauche('accueil',true)
		.  articles_affiche($id_article, $row, _request('cherche_auteur'), _request('ids'), _request('cherche_mot'), _request('select_groupe'), _request('trad_err'))
		  . "<br /><br /><div class='centered'>"
		  . icone_inline(_T('icone_poster_message'), generer_url_ecrire("forum_envoi", "statut=prive&id=$id_article&script=articles") ."#formulaire", "forum-interne-24.gif", "creer.gif")
		. "</div>"
		. $discuter($id_article, false,  _request('debut'))
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
	$visites = $row["visites"];
	$extra = $row["extra"];
	$id_trad = $row["id_trad"];
	$id_version = $row["id_version"];
	
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

	if ($flag_editable AND ($spip_display != 4)) {
		$iconifier = charger_fonction('iconifier', 'inc');
		$icone = $iconifier('id_article', $id_article,'articles');
	} else $icone = '';

	$instituer_article = charger_fonction('instituer_article', 'inc');

	$res =  boite_info_articles($id_article, $statut_article, $visites, $id_version)

	.	$icone

	.	boites_de_config_articles($id_article)
	.	boite_article_virtuel($id_article, $virtuel, $flag_editable)
	.	meme_rubrique($id_rubrique, $id_article, 'article')

	.	 pipeline('affiche_gauche',array('args'=>array('exec'=>'articles','id_article'=>$id_article),'data'=>''))

	.	creer_colonne_droite('', true)

	.	pipeline('affiche_droite',array('args'=>array('exec'=>'articles','id_article'=>$id_article),'data'=>''))

	.	debut_droite('',true);

// affecter les globales dictant les regles de typographie de la langue
	changer_typo($row['lang']);

	return $res
	. debut_cadre_relief('', true)
	. titres_articles($titre, $statut_article,$surtitre, $soustitre, $descriptif, $url_site, $nom_site, $flag_editable, $id_article, $id_rubrique, $modif)
	. "\n<div style='margin-top: 10px' class='serif'>"

	. $dater($id_article, $flag_editable, $statut_article, 'article', 'articles', $date, $date_redac)

	. $editer_auteurs('article', $id_article, $flag_editable, $cherche_auteur, $ids)

	. (!$editer_mot ? '' : $editer_mot('article', $id_article, $cherche_mot, $select_groupe, $flag_editable))

	. (!$referencer_traduction ? '' : $referencer_traduction($id_article, $flag_editable, $id_rubrique, $id_trad, $trad_err))

	. pipeline('affiche_milieu',array('args'=>array('exec'=>'articles','id_article'=>$id_article),'data'=>''))

	. (!$statut_rubrique ? ''
	 : (debut_cadre_relief('', true)
		. $instituer_article($id_article, $statut_article)
		. fin_cadre_relief(true)))

	. "\n<div style='text-align: justify; padding: 10px;'>"
	. afficher_corps_articles($virtuel, $chapo, $texte, $ps, $extra)

	. (!$flag_editable ? ''
	   :  (bouton_modifier_articles($id_article, $id_rubrique, $modif,_T('texte_travail_article', $modif), "warning-24.gif", '', 'right') . "<br class='nettoyeur' />"))
	. (($spip_display == 4) ? ''
	 : articles_documents($flag_editable, 'article', $id_article))

	. (($statut_article == 'prepa' AND !$statut_rubrique
	AND spip_num_rows(auteurs_article($id_article, " id_auteur=$connect_id_auteur")))
	 ? $instituer_article($id_article)
	 : '')
	. "</div></div>"
	. fin_cadre_relief(true);
}

// http://doc.spip.org/@articles_documents
function articles_documents($flag_editable, $type, $id)
{
	global $spip_lang_left, $spip_lang_right;
	
	if  ($GLOBALS['meta']["documents_$type"]=='non' OR !$flag_editable)

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

	return "<div id='portfolio'>" . $documenter($id, 'article', 'portfolio', $flag_editable) . "</div><br />"
	. "<div id='documents'>" . $documenter($id, 'article', 'documents', $flag_editable) . "</div>"
	. $res;
}

// http://doc.spip.org/@boite_info_articles
function boite_info_articles($id_article, $statut_article, $visites, $id_version)
{
	$res = "\n<div style='font-weight: bold; text-align: center' class='verdana1 spip_xx-small'>" 
	. _T('info_numero_article')
	.  "<br /><span class='spip_xx-large'>"
	.  $id_article
	.  '</span></div>'
	. voir_en_ligne('article', $id_article, $statut_article, 'racine-24.gif', false);

	if ($statut_article == 'publie'
	AND $visites > 0
	AND $GLOBALS['meta']["activer_statistiques"] != "non"
	AND autoriser('voirstats', 'article', $id_article)) {
		$res .= icone_horizontale(_T('icone_evolution_visites', array('visites' => $visites)), generer_url_ecrire("statistiques_visites","id_article=$id_article"), "statistiques-24.gif","rien.gif", false);
	}

	if (($GLOBALS['meta']["articles_versions"]=='oui')
	AND $id_version>1 
	AND autoriser('voirrevisions', 'article', $id_article))
		$res .= icone_horizontale(_T('info_historique_lien'), generer_url_ecrire("articles_versions","id_article=$id_article"), "historique-24.gif", "rien.gif", false);

	// Correction orthographique
	if ($GLOBALS['meta']['articles_ortho'] == 'oui') {
		$js_ortho = "onclick=\"window.open(this.href, 'spip_ortho', 'scrollbars=yes, resizable=yes, width=740, height=580'); return false;\"";
		$res .= icone_horizontale(_T('ortho_verifier'), generer_url_ecrire("articles_ortho", "id_article=$id_article"), "ortho-24.gif", "rien.gif", false, $js_ortho);
	}

	return  debut_boite_info(true). $res . fin_boite_info(true);
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
		  $visible = strstr($masque, '<!-- visible -->'),
		  $masque,
		  'forumpetition');
}

// http://doc.spip.org/@boite_article_virtuel
function boite_article_virtuel($id_article, $virtuel, $flag)
{
	if (!strlen($virtuel)
	AND $GLOBALS['meta']['articles_redirection'] != 'oui')
		return '';


	$virtualiser = charger_fonction('virtualiser', 'inc');
	$masque = $virtualiser($id_article, $flag, $virtuel, "articles", "id_article=$id_article");

	if (!$masque) return '';

	$invite = '<b>'
	._T('bouton_redirection')
	. '</b>'
	. aide ("artvirt");

	return
		cadre_depliable("site-24.gif",
		  $invite,
		  $virtuel,
		  $masque,
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

// http://doc.spip.org/@titres_articles
function titres_articles($titre, $statut_article,$surtitre, $soustitre, $descriptif, $url_site, $nom_site, $flag_editable, $id_article, $id_rubrique, $modif)
{
	global  $lang_objet, $spip_lang_left, $spip_lang_right;

	$lang_dir = lang_dir($lang_objet);

	$res = '';
	if ($flag_editable) {
		$res .= bouton_modifier_articles($id_article, $id_rubrique, $modif, _T('avis_article_modifie', $modif), "article-24.gif", "edit.gif",$spip_lang_right);
	}

	if ($surtitre) {
		$res .= "<span  dir='$lang_dir' class='arial1 spip_medium'><b>" . typo($surtitre) . "</b></span>\n";
	}
	 
	$res .= gros_titre($titre, puce_statut($statut_article, " style='vertical-align: bottom'") . " &nbsp; ", false);

	
	if ($soustitre) {
		$res .= "<span  dir='$lang_dir' class='arial1 spip_medium'><b>" . typo($soustitre) . "</b></span>\n";
	}
	$res .= "<div class='nettoyeur'></div>";
	if ($descriptif OR $url_site OR $nom_site) {

		$texte_case = ($descriptif) ? "{{"._T('info_descriptif')."}} $descriptif\n\n" : '';

		$texte_case .=  ($nom_site OR $url_site) ? "{{"._T('info_urlref')."}} [".$nom_site."->".$url_site."]" : '';

		$res .= "<br />\n<div  dir='$lang_dir' style='padding: 4px; border: 1px dashed #aaaaaa; background-color: #e4e4e4; text-align: $spip_lang_left;' class='Verdana1 spip_x-small'>"
		. propre($texte_case)
		. "</div>";
	}
	
	if ($statut_article == 'prop')
		$res .= "<p style='color: red' class='verdana1 spip_small'><b>"._T('text_article_propose_publication')."</b></p>";
	
	return $res;
}

// http://doc.spip.org/@afficher_corps_articles
function afficher_corps_articles($virtuel, $chapo, $texte, $ps,  $extra)
{
  global $champs_extra, $les_notes, $lang_objet;

  $lang_dir = lang_dir($lang_objet);
// HACK TEMPORAIRE POUR TESTER les crayons dans l'espace prive
global $id_article;

	$res = '';

	if ($virtuel) {
		$res .= debut_boite_info(true)
		.  "\n<div style='text-align: center'>"
		. _T('info_renvoi_article')
		. " "
		.  propre("[->$virtuel]")
		. '</div>'
		.  fin_boite_info(true);
	} else {

		if (strlen($chapo) > 0) {
			$res .= "\n<div  dir='$lang_dir' style='font-weight: bold;' class='spip_small crayon article-chapo-$id_article'>"
			. propre($chapo)
			. "</div>";
		}

		if (strlen($texte) > 0) {
			$res .= "\n<div  dir='$lang_dir' class='crayon article-texte-$id_article'>"
			.  propre($texte)
			.  "<div class='nettoyeur'></div>"
			.  "</div>";
		}

		if (strlen($ps)) {
			$res .= debut_cadre_enfonce('',true)
			. "\n<div  dir='$lang_dir' style='font-size: small;' class='verdana1 crayon article-ps-$id_article'>"
			. justifier("<b>"._T('info_ps')."</b> ".propre($ps))
			. "</div>"
			. fin_cadre_enfonce(true);
		}

		if ($les_notes) {
			$res .= debut_cadre_relief('',true)
			. "\n<div  dir='$lang_dir' class='arial11'>"
			. justifier("<b>"._T('info_notes')."&nbsp;:</b> ".$les_notes)
			. "</div>"
			. fin_cadre_relief(true);
		}
		
		if ($champs_extra AND $extra) {
			include_spip('inc/extra');
			$res .= extra_affichage($extra, "articles");
		}
	}
	return $res;
}
?>
