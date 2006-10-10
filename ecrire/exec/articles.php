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
include_spip('inc/texte');
include_spip('inc/actions');

// http://doc.spip.org/@exec_articles_dist
function exec_articles_dist()
{
	$id_article= intval(_request('id_article'));

	pipeline('exec_init',array('args'=>array('exec'=>'articles','id_article'=>$id_article),'data'=>''));

	$row = spip_fetch_array(spip_query("SELECT * FROM spip_articles WHERE id_article=$id_article"));

	if (!$row) {
		$res = $row['titre'] = _T('public:aucun_article');
		$row['id_rubrique'] = 0;
	} else {
		$discuter = charger_fonction('discuter', 'inc');
		$row['titre'] = sinon($row["titre"],_T('info_sans_titre'));

		$res = articles_affiche($id_article, $row, _request('cherche_auteur'), _request('ids'), _request('cherche_mot'), _request('select_groupe'), _request('trad_err'))
		. "<br /><br />\n<div align='center'>"
		. icone(_T('icone_poster_message'), generer_url_ecrire("forum_envoi","statut=prive&id_article=$id_article&titre_message=" .rawurlencode($row['titre']) . "&url=" . generer_url_retour("articles","id_article=$id_article")), "forum-interne-24.gif", "creer.gif", '', false)
		. "</div><br />"
		. $discuter($id_article, false,  _request('debut'));
	}

	debut_page("&laquo; ". $row['titre'] ." &raquo;", "naviguer", "articles", "", $row['id_rubrique']);

	echo debut_grand_cadre(true),
		afficher_hierarchie($id_rubrique),
		fin_grand_cadre(true),
		$res,
		fin_page();
}

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

	$statut_rubrique = acces_rubrique($id_rubrique);

	$flag_auteur = spip_num_rows(spip_query("SELECT id_auteur FROM spip_auteurs_articles WHERE id_article=$id_article AND id_auteur=$connect_id_auteur LIMIT 1"));

	$flag_editable = ($statut_rubrique OR ($flag_auteur AND ($statut_article == 'prepa' OR $statut_article == 'prop' OR $statut_article == 'poubelle')));

	// Est-ce que quelqu'un a deja ouvert l'article en edition ?
	$modif = array();
	if ($GLOBALS['meta']['articles_modif'] != 'non') {
		include_spip('inc/drapeau_edition');
		$modif = qui_edite($id_article, 'article');
		if ($modif['id_auteur_modif'] == $connect_id_auteur)
			$modif = array();
	}

 // chargement prealable des fonctions produisant des formulaires

	$dater = charger_fonction('dater', 'inc');
	$editer_auteurs = charger_fonction('editer_auteurs', 'inc');

	if ($flag_editable AND ($spip_display != 4)) 
		$iconifier = charger_fonction('iconifier', 'inc');
	else $iconifier = '';

	if ($flag_editable)
		$instituer_article = charger_fonction('instituer_article', 'inc');
	else $instituer_article ='';

	if ($options == 'avancees' AND $GLOBALS['meta']["articles_mots"] != 'non')
		$editer_mot = charger_fonction('editer_mot', 'inc');
	else $editer_mot = '';

	if (($GLOBALS['meta']['multi_articles'] == 'oui')
	OR (($GLOBALS['meta']['multi_rubriques'] == 'oui') 
	AND ($GLOBALS['meta']['gerer_trad'] == 'oui'))) 
		$traduction = charger_fonction('referencer_traduction', 'inc');
	else $traduction ='';

	$res = debut_gauche('accueil',true)

	.	boite_info_articles($id_article, $statut_article, $visites, $id_version)

	.	(!$iconifier ? '' : $iconifier('id_article', $id_article,'articles'))

	.	(!($options == "avancees" && $connect_statut=='0minirezo' && $flag_editable) ? '' : ( boites_de_config_articles($id_article) . boite_article_virtuel($id_article, $virtuel))) 

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

	. $dater($id_article, $flag_editable, $statut_article, $date, $date_redac)

	. $editer_auteurs($id_article, $flag_editable, $cherche_auteur, $ids)

	. (!$editer_mot ? '' : $editer_mot('article', $id_article, $cherche_mot, $select_groupe, $flag_editable))

	. (!$traduction ? '' : $traduction($id_article, $flag_editable, $id_rubrique, $id_trad, $trad_err))

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

	. (($flag_auteur AND  $statut_article == 'prepa' AND !$statut_rubrique) 
	 ? $instituer_article($id_article)
	 : '')
	. "</div></div>"
	. fin_cadre_relief(true);
}

function articles_documents($flag_editable, $type, $id)
{
	global $spip_lang_left;
	if  ($GLOBALS['meta']["documents_$type"]!='non' AND $flag_editable) {

		$f = charger_fonction('joindre', 'inc');

		$res = debut_cadre_relief("image-24.gif", true, "", _T('titre_joindre_document'))
		. $f('articles', "id_article=$id", $id, _T('info_telecharger_ordinateur'), 'document', 'article')
		. fin_cadre_relief(true);

	// eviter le formulaire upload qui se promene sur la page
	// a cause des position:relative incompris de MSIE

		if ($align = ($GLOBALS['browser_name'] !== "MSIE")) {
			$res = "\n<table width='50%' cellpadding='0' cellspacing='0' border='0'>\n<tr><td style='text-align: $spip_lang_left;'>\n$res</td></tr></table>";
			$align = " align='right'";
		}
		$res = "\n<div$align>$res</div>";
	} else $res ='';

	$f = charger_fonction('documenter', 'inc');

	return $f($id, 'article', 'portfolio', $flag_editable)
	. $f($id, 'article', 'documents', $flag_editable)
	. $res;
}

// http://doc.spip.org/@boite_info_articles
function boite_info_articles($id_article, $statut_article, $visites, $id_version)
{
	global $connect_statut, $options, $flag_revisions;

	$res ="\n<div align='center'>\n"
	. "<font face='Verdana,Arial,Sans,sans-serif' size='1'><b>"
	. _T('info_numero_article')."</b></font>\n"
	. "<br /><font face='Verdana,Arial,Sans,sans-serif' size='6'><b>$id_article</b></font>\n"
	. "</div>\n"
	  . voir_en_ligne('article', $id_article, $statut_article, 'racine-24.gif', false);

	if ($connect_statut == "0minirezo" AND $statut_article == 'publie' AND $visites > 0 AND $GLOBALS['meta']["activer_statistiques"] != "non" AND $options == "avancees"){
		$res .= icone_horizontale(_T('icone_evolution_visites', array('visites' => $visites)), generer_url_ecrire("statistiques_visites","id_article=$id_article"), "statistiques-24.gif","rien.gif", false);
	}

	if ((($GLOBALS['meta']["articles_versions"]=='oui') && $flag_revisions)
	AND $id_version>1 AND $options == "avancees") 
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
	$nb_forums = spip_fetch_array(spip_query("SELECT COUNT(*) AS count FROM spip_forum WHERE id_article=$id_article 	AND statut IN ('publie', 'off', 'prop')"));

	$nb_signatures = spip_fetch_array(spip_query("SELECT COUNT(*) AS count FROM spip_signatures WHERE id_article=$id_article AND statut IN ('publie', 'poubelle')"));

	$nb_forums = $nb_forums['count'];
	$nb_signatures = $nb_signatures['count'];
	$visible = $nb_forums || $nb_signatures;

	$invite = "<span class='verdana1'<b>"
	. _T('bouton_forum_petition')
	. aide('confforums')
	. "</b></span>";

	$f = charger_fonction('poster', 'inc');
	$g = charger_fonction('petitionner', 'inc');

	if ($nb_forums) {
		$masque = icone_horizontale(_T('icone_suivi_forum', array('nb_forums' => $nb_forums)), generer_url_ecrire("articles_forum","id_article=$id_article"), "suivi-forum-24.gif", "", false);
	} else 	$masque = '';

	$masque .= $f($id_article,"articles","id_article=$id_article")
	. $g($id_article,"articles","id_article=$id_article");

	return debut_cadre_relief("forum-interne-24.gif", true)
	. block_parfois_visible('forumpetition', $invite, $masque, 'text-align: center;', $visible)
	. fin_cadre_relief(true);
}

// http://doc.spip.org/@boite_article_virtuel
function boite_article_virtuel($id_article, $virtuel)
{

	$f = charger_fonction('virtualiser', 'inc');

	$masque = $f($id_article, false, $virtuel, "articles", "id_article=$id_article");

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
function bouton_modifier_articles($id_article, $id_rubrique, $flag_modif, $mode, $ip, $im)
{
	if ($flag_modif) {
		return icone(_T('icone_modifier_article'), generer_url_ecrire("articles_edit","id_article=$id_article"), $ip, $im, '', false)
		. "<font face='arial,helvetica,sans-serif' size='2'>$mode</font>"
		. aide("artmodif");
	}
	else return icone(_T('icone_modifier_article'), generer_url_ecrire("articles_edit","id_article=$id_article"), "article-24.gif", "edit.gif", '', false);
}

// http://doc.spip.org/@titres_articles
function titres_articles($titre, $statut_article,$surtitre, $soustitre, $descriptif, $url_site, $nom_site, $flag_editable, $id_article, $id_rubrique, $modif)
{
	global  $dir_lang, $spip_lang_left, $spip_lang_right;

	$res = '';

	if ($surtitre) {
		$res .= "<span $dir_lang><font face='arial,helvetica' size='3'><b>";
		$res .= typo($surtitre);
		$res .= "</b></font></span>\n";
	}
	 
	$res .= gros_titre($titre, "puce-".puce_statut($statut_article).".gif", false);
	
	if ($soustitre) {
		$res .= "<span $dir_lang><font face='arial,helvetica' size='3'><b>";
		$res .= typo($soustitre);
		$res .= "</b></font></span>\n";
	}
	
	if ($descriptif OR $url_site OR $nom_site) {

		$texte_case = ($descriptif) ? "{{"._T('info_descriptif')."}} $descriptif\n\n" : '';

		$texte_case .=  ($nom_site.$url_site) ? "{{"._T('info_urlref')."}} [".$nom_site."->".$url_site."]" : '';

		$res .= "<p>\n<div align='$spip_lang_left' style='padding: 5px; border: 1px dashed #aaaaaa; background-color: #e4e4e4;' $dir_lang>"
		. "<font size='2' face='Verdana,Arial,Sans,sans-serif'>"
		. propre($texte_case)
		. "</font>"
		. "</div></p>";
	}
	
	if ($statut_article == 'prop')
		$res .= "<p><font face='Verdana,Arial,Sans,sans-serif' size='2' color='red'><b>"._T('text_article_propose_publication')."</b></font></p>";
	
	$res = "<td valign='top'>$res</td>";

	if ($flag_editable) {
		$res .= "<td valign='top' align='$spip_lang_right'>"
		. bouton_modifier_articles($id_article, $id_rubrique, $modif, _T('avis_article_modifie', $modif), "article-24.gif", "edit.gif")
		. "</td>\n";
	}

	return "\n<table cellpadding=0 cellspacing=0 border=0 width='100%'>"
	. "\n<tr width='100%'>"
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
			. "\n<div $dir_lang><font style='font-family:Verdana,Arial,Sans,sans-serif; font-size: small;'>"
			. justifier("<b>"._T('info_ps')."</b> ".propre($ps))
			. "</font></div>"
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
