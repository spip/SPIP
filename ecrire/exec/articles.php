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
include_spip('inc/texte');
include_spip('inc/actions');
include_spip('inc/autoriser');

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
		. "<br /><br />\n<div align='center'>"
		  . icone(_T('icone_poster_message'), generer_url_ecrire("forum_envoi", "statut=prive&id=$id_article&script=articles") ."#formulaire", "forum-interne-24.gif", "creer.gif", '', false)
		. "</div><br />"
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
	global $spip_display, $spip_lang_left, $spip_lang_right, $dir_lang;
	global $connect_id_auteur, $connect_statut, $options;

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
	
	$virtuel =  (substr($chapo, 0, 1) == '=')  ? substr($chapo, 1) : '';


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
		$icone = $iconifier('id_article', $id_article,'articles','iconifier');
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
	changer_typo('','article'.$id_article);

	return $res
	. debut_cadre_relief('', true)
	. titres_articles($titre, $statut_article,$surtitre, $soustitre, $descriptif, $url_site, $nom_site, $flag_editable, $id_article, $id_rubrique, $modif)

	. "\n<div>&nbsp;</div>"
	. "\n<div class='serif' align='$spip_lang_left'>"

	. $dater($id_article, $flag_editable, $statut_article, 'article', 'articles', $date, $date_redac)

	. $editer_auteurs($id_article, $flag_editable, $cherche_auteur, $ids)

	. (!$editer_mot ? '' : $editer_mot('article', $id_article, $cherche_mot, $select_groupe, $flag_editable))

	. (!$referencer_traduction ? '' : $referencer_traduction($id_article, $flag_editable, $id_rubrique, $id_trad, $trad_err))

	. pipeline('affiche_milieu',array('args'=>array('exec'=>'articles','id_article'=>$id_article),'data'=>''))

	. (!$statut_rubrique ? ''
	 : (debut_cadre_relief('', true)
		. $instituer_article($id_article, $statut_article)
		. fin_cadre_relief(true)))

	. "\n<div align='justify' style='padding: 10px;'>"
	. afficher_corps_articles($virtuel, $chapo, $texte, $ps, $extra)

	. (!$flag_editable ? ''
	: ("\n<div align='$spip_lang_right'><br />"
	.  bouton_modifier_articles($id_article, $id_rubrique, $modif,_T('texte_travail_article', $modif), "warning-24.gif", "")
	   . "</div>"))
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
	global $spip_lang_left;
	
	$multi_js = "";
	if  ($GLOBALS['meta']["documents_$type"]!='non' AND $flag_editable) {

		$joindre = charger_fonction('joindre', 'inc');

		$res = debut_cadre_relief("image-24.gif", true, "", _T('titre_joindre_document'))
		. $joindre('articles', "id_article=$id", $id, _T('info_telecharger_ordinateur'), 'document', 'article','',0,generer_url_ecrire("documenter","id_article=$id&type=$type",true))
		. fin_cadre_relief(true);

	// eviter le formulaire upload qui se promene sur la page
	// a cause des position:relative incompris de MSIE

    $align = "";
		if ($GLOBALS['browser_name']!='MSIE') {
			$res = "\n<table width='50%' cellpadding='0' cellspacing='0' border='0'>\n<tr><td style='text-align: $spip_lang_left;'>\n$res</td></tr></table>";
			$align = " align='right'";
		}
		$res = "\n<div$align>$res</div>";
    $res .= "<script src='"._DIR_JAVASCRIPT."async_upload.js' type='text/javascript'></script>\n";
    $res .= <<<EOF
    <script type='text/javascript'>
    $("form.form_upload").async_upload(async_upload_portfolio_documents);
    </script>
EOF;
		if($GLOBALS['meta']['multi_rubriques']=="oui" || $GLOBALS['meta']['multi_articles']=="oui" || $GLOBALS['meta']['multi_secteurs']=="oui") {
			$active_langs = "'".str_replace(",","','",$GLOBALS['meta']['langues_multilingue'])."'";
			$multi_js = "<script type='text/javascript' src='"._DIR_JAVASCRIPT."multilang.js'></script>\n".
			"<script type='text/javascript'>\n".
			"var multilang_def_lang='".$GLOBALS["spip_lang"]."';var multilang_avail_langs=[$active_langs];\n".
			"$(function(){\n".
			"multilang_init_lang({'root':'#portfolio,#documents','forms':'form:not(.form_upload)','fields':'input,textarea'});\n".
			"onAjaxLoad(function(){forms_init_multi({'target':this})});\n".
			"});\n".
			"</script>\n";
		}		
	} else $res = '';
	
	$documenter = charger_fonction('documenter', 'inc');

	return $multi_js."<div id='portfolio'>" . $documenter($id, 'article', 'portfolio', $flag_editable) . "</div>"
	. "<div id='documents'>" . $documenter($id, 'article', 'documents', $flag_editable) . "</div>"
	. $res;
}

// http://doc.spip.org/@boite_info_articles
function boite_info_articles($id_article, $statut_article, $visites, $id_version)
{
	global $connect_statut, $options, $flag_revisions;

	$res = "\n<div style='font-famuily: Verdana,Arial,Sans,sans-serif; font-size: 10px; font-weight: bold; text-align: center'>" 
	. _T('info_numero_article')
	.  "<br /><span style='font-size: 32px;'>"
	.  $id_article
	.  '</span></div>'
	. voir_en_ligne('article', $id_article, $statut_article, 'racine-24.gif', false);

	if ($statut_article == 'publie'
	AND $visites > 0
	AND $GLOBALS['meta']["activer_statistiques"] != "non"
	AND $options == "avancees"
	AND autoriser('voirstats', 'article', $id_article)) {
		$res .= icone_horizontale(_T('icone_evolution_visites', array('visites' => $visites)), generer_url_ecrire("statistiques_visites","id_article=$id_article"), "statistiques-24.gif","rien.gif", false);
	}

	if ((($GLOBALS['meta']["articles_versions"]=='oui') && $flag_revisions)
	AND $id_version>1 AND $options == "avancees"
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
		$regler = $regler_moderation($id_article,"articles","id_article=$id_article");
	}

	if (autoriser('moderer_petition', 'article', $id_article)) {
		$petitionner = charger_fonction('petitionner', 'inc');
		$petition = $petitionner($id_article,"articles","id_article=$id_article");
	}

	$masque = $regler . $petition;

	if (!$masque)
		return '';

	$invite = "<span class='verdana1'><b>"
	. _T('bouton_forum_petition')
	. aide('confforums')
	. "</b></span>";

	return debut_cadre_relief("forum-interne-24.gif", true)
	. block_parfois_visible('forumpetition',
		$invite,
		$masque,
		'text-align: center;',
		$visible = strstr($masque, '<!-- visible -->')
	)
	. fin_cadre_relief(true);
}

// http://doc.spip.org/@boite_article_virtuel
function boite_article_virtuel($id_article, $virtuel, $flag)
{

	$virtualiser = charger_fonction('virtualiser', 'inc');

	$masque = $virtualiser($id_article, $flag, $virtuel, "articles", "id_article=$id_article");

	if (!$masque) return '';

	$invite = "<span class='verdana1'>"
	. '<b>'
	._T('bouton_redirection')
	. '</b>'
	. aide ("artvirt")
	. "</span>";

	$f = block_parfois_visible('redirection', $invite, $masque, 'text-align: center;', $virtuel);

	return debut_cadre_relief("site-24.gif", true) . $f . fin_cadre_relief(true);
}

// http://doc.spip.org/@bouton_modifier_articles
function bouton_modifier_articles($id_article, $id_rubrique, $flag_modif, $mode, $ip, $im, $align='')
{
	if ($flag_modif) {
		return icone(_T('icone_modifier_article'), generer_url_ecrire("articles_edit","id_article=$id_article"), $ip, $im, $align, false)
		. "<span style='font-family: arial,helvetica,sans-serif; font-size: 14px;'>$mode</span>"
		. aide("artmodif");
	}
	else return icone(_T('icone_modifier_article'), generer_url_ecrire("articles_edit","id_article=$id_article"), "article-24.gif", "edit.gif", $align, false);
}

// http://doc.spip.org/@titres_articles
function titres_articles($titre, $statut_article,$surtitre, $soustitre, $descriptif, $url_site, $nom_site, $flag_editable, $id_article, $id_rubrique, $modif)
{
	global  $dir_lang, $spip_lang_left, $spip_lang_right;

	$res = '';

	if ($surtitre) {
		$res .= "<span $dir_lang><span style='font-family: arial,helvetica; font-size: 16px;'><b>" . typo($surtitre) . "</b></span></span>\n";
	}
	 
	$res .= gros_titre($titre, "puce-".puce_statut($statut_article).".gif", false);
	
	if ($soustitre) {
		$res .= "<span $dir_lang><span style='font-family: arial,helvetica; font-size: 16px;'><b>" . typo($soustitre) . "</b></span></span>\n";
	}
	
	if ($descriptif OR $url_site OR $nom_site) {

		$texte_case = ($descriptif) ? "{{"._T('info_descriptif')."}} $descriptif\n\n" : '';

		$texte_case .=  ($nom_site.$url_site) ? "{{"._T('info_urlref')."}} [".$nom_site."->".$url_site."]" : '';

		$res .= "<br />\n<div align='$spip_lang_left' $dir_lang style='padding: 5px; border: 1px dashed #aaaaaa; background-color: #e4e4e4; font-size: 11px;  font-face: Verdana,Arial,Sans,sans-serif'>"
		. propre($texte_case)
		. "</div>";
	}
	
	if ($statut_article == 'prop')
		$res .= "<p style='font-family: Verdana,Arial,Sans,sans-serif; font-size: 14px; color: red'><b>"._T('text_article_propose_publication')."</b></p>";
	
	$res = "\n<td valign='top'>$res</td>";

	if ($flag_editable) {
		$res .= "\n<td style='width: 130px;' valign='top' align='$spip_lang_right'>"
		. bouton_modifier_articles($id_article, $id_rubrique, $modif, _T('avis_article_modifie', $modif), "article-24.gif", "edit.gif")
		. "</td>\n";
	}

	return "\n<table cellpadding='0' cellspacing='0' border='0' width='100%'>"
	. "\n<tr>"
	. $res
	. "</tr></table>\n";
}

// http://doc.spip.org/@afficher_corps_articles
function afficher_corps_articles($virtuel, $chapo, $texte, $ps,  $extra)
{
  global $revision_nbsp, $activer_revision_nbsp, $champs_extra, $les_notes, $dir_lang;

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
		$revision_nbsp = $activer_revision_nbsp;

		if (strlen($chapo) > 0) {
			$res .= "\n<div $dir_lang><b>"
			. propre($chapo)
			. "</b></div>\n";
		}

		$res .= "\n<div $dir_lang>"
		.  propre($texte)
		.  "<br clear='all' />"
		.  "</div>";

		if ($ps) {
			$res .= debut_cadre_enfonce('',true)
			. "\n<div $dir_lang style='font-family:Verdana,Arial,Sans,sans-serif; font-size: small;'>"
			. justifier("<b>"._T('info_ps')."</b> ".propre($ps))
			. "</div>"
			. fin_cadre_enfonce(true);
		}
		$revision_nbsp = false;

		if ($les_notes) {
			$res .= debut_cadre_relief('',true)
			. "\n<div $dir_lang class='arial11'>"
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
