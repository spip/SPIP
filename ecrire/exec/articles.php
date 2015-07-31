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
include_spip('inc/actions');

// http://doc.spip.org/@exec_articles_dist
function exec_articles_dist()
{
	exec_articles_args(intval(_request('id_article')));
}

// http://doc.spip.org/@exec_articles_args
function exec_articles_args($id_article)
{
	pipeline('exec_init',array('args'=>array('exec'=>'articles','id_article'=>$id_article),'data'=>''));

	$row = sql_fetsel("*", "spip_articles", "id_article=$id_article");

	if (!$row
	OR !autoriser('voir', 'article', $id_article)) {
		include_spip('inc/minipres');
		echo minipres(_T('public:aucun_article'));
	} else {
		$row['titre'] = sinon($row["titre"],_T('info_sans_titre'));

		$res = debut_gauche('accueil',true)
		  .  articles_affiche($id_article, $row, _request('cherche_auteur'), _request('ids'), _request('cherche_mot'), _request('select_groupe'), _request('trad_err'), _request('debut'))
		  . "<br /><br /><div class='centered'>"
		. "</div>"
		. fin_gauche();

		$commencer_page = charger_fonction('commencer_page', 'inc');
		echo $commencer_page("&laquo; ". $row['titre'] ." &raquo;", "naviguer", "articles", $row['id_rubrique']);

		echo debut_grand_cadre(true),
			afficher_hierarchie($row['id_rubrique'],_T('titre_cadre_interieur_rubrique'),$id_article,'article',$row['id_secteur'],($row['statut'] == 'publie')),
			fin_grand_cadre(true),
			$res,
			fin_page();
	}
}

// http://doc.spip.org/@articles_affiche
function articles_affiche($id_article, $row, $cherche_auteur, $ids, $cherche_mot,  $select_groupe, $trad_err, $debut_forum=0, $statut_forum='prive')
{
	global $spip_lang_right, $dir_lang;

	$id_rubrique = $row['id_rubrique'];
	$id_secteur = $row['id_secteur'];
	$statut_article = $row['statut'];
	$titre = $row["titre"];
	$surtitre = $row["surtitre"];
	$soustitre = $row["soustitre"];
	$descriptif = $row["descriptif"];
	$nom_site = $row["nom_site"];
	$url_site = $row["url_site"];
	$texte = $row["texte"];
	$ps = $row["ps"];
	$date = $row["date"];
	$date_redac = $row["date_redac"];
	$extra = $row["extra"];
	$id_trad = $row["id_trad"];

	$virtuel = (strncmp($row["chapo"],'=',1)!==0) ? '' :
		chapo_redirige(substr($row["chapo"], 1));

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
	$editer_mots = charger_fonction('editer_mots', 'inc');
	$editer_auteurs = charger_fonction('editer_auteurs', 'inc');
	$referencer_traduction = charger_fonction('referencer_traduction', 'inc');
	$discuter = charger_fonction('discuter', 'inc');

	$meme_rubrique = charger_fonction('meme_rubrique', 'inc');
	$iconifier = charger_fonction('iconifier', 'inc');
	$icone = $iconifier('id_article', $id_article,'articles', false, $flag_editable);

	$boite = pipeline ('boite_infos', array('data' => '',
		'args' => array(
			'type'=>'article',
			'id' => $id_article,
			'row' => $row
		)
	));

	$navigation =
	  debut_boite_info(true). $boite . fin_boite_info(true)
	  . $icone
		. (_INTERFACE_ONGLETS?"":boites_de_config_articles($id_article))
	  . ($flag_editable ? boite_article_virtuel($id_article, $virtuel):'')
	  . pipeline('affiche_gauche',array('args'=>array('exec'=>'articles','id_article'=>$id_article),'data'=>''));

	$extra = creer_colonne_droite('', true)
		. $meme_rubrique($id_rubrique, $id_article, 'article')
	  . pipeline('affiche_droite',array('args'=>array('exec'=>'articles','id_article'=>$id_article),'data'=>''))
	  . debut_droite('',true);

	// affecter les globales dictant les regles de typographie de la langue
	changer_typo($row['lang']);

	$actions =
	  ($flag_editable ? bouton_modifier_articles($id_article, $id_rubrique, $modif, _T('avis_article_modifie', $modif), "article-24.gif", "edit.gif",$spip_lang_right) : "");

	$haut =
		"<div class='bandeau_actions'>$actions</div>".
		(_INTERFACE_ONGLETS?"":"<span $dir_lang class='arial1 spip_medium'><b>" . typo($surtitre) . "</b></span>\n")
		. gros_titre($titre, '' , false)
		. (_INTERFACE_ONGLETS?"":"<span $dir_lang class='arial1 spip_medium'><b>" . typo($soustitre) . "</b></span>\n");

	$onglet_contenu =
	  afficher_corps_articles($id_article,$virtuel,$row)
		.		"<div class='bandeau_actions'>$actions</div>";

	$onglet_proprietes = ((!_INTERFACE_ONGLETS) ? "" :"")
	  . $dater($id_article, $flag_editable, $statut_article, 'article', 'articles', $date, $date_redac)
	  . $editer_auteurs('article', $id_article, $flag_editable, $cherche_auteur, $ids)
	  . (!$editer_mots ? '' : $editer_mots('article', $id_article, $cherche_mot, $select_groupe, $flag_editable, false, 'articles'))
	  . (!$referencer_traduction ? '' : $referencer_traduction($id_article, $flag_editable, $id_rubrique, $id_trad, $trad_err))
	  . pipeline('affiche_milieu',array('args'=>array('exec'=>'articles','id_article'=>$id_article),'data'=>''))
		. bouton_proposer_article($id_article,$statut_article)
	  ;

	$documenter_objet = charger_fonction('documenter_objet','inc');
	$onglet_documents = $documenter_objet($id_article,'article','articles',$flag_editable);
	$onglet_interactivite = (_INTERFACE_ONGLETS?boites_de_config_articles($id_article):"");

	$onglet_discuter = !$statut_forum ? '' : ($discuter($id_article, 'articles', 'id_article', $statut_forum, $debut_forum));


	return
	  $navigation
	  . $extra
	  . pipeline('afficher_fiche_objet',array('args'=>array('type'=>'article','id'=>$id_article),'data'=>
	   "<div class='fiche_objet'>"
	  . $haut
	  . afficher_onglets_pages(
	  	array(
	  	'voir' => _T('onglet_contenu'),
	  	'props' => _T('onglet_proprietes'),
	  	'docs' => _T('onglet_documents'),
	  	'interactivite' => _T('onglet_interactivite'),
	  	'discuter' => _T('onglet_discuter')),
	  	array(
	    'props'=>$onglet_proprietes,
	    'voir'=>$onglet_contenu,
	    'docs'=>$onglet_documents,
	    'interactivite'=>$onglet_interactivite,
	    'discuter'=>_INTERFACE_ONGLETS?$onglet_discuter:""))
	  . "</div>"
	  . (_INTERFACE_ONGLETS?"":$onglet_discuter)
			));
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

	$petitionner = charger_fonction('petitionner', 'inc');
	$petition = $petitionner($id_article,"articles","id_article=$id_article");

	$masque = $regler . $petition;
  $masque = pipeline('afficher_config_objet',array('args'=>array('type'=>'article','id'=>$id_article),'data'=>$masque));

	if (!$masque) return '';

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
	$res = '';
	if ($row['statut'] == 'prop') {
		$res .= "<p class='article_prop'>"._T('text_article_propose_publication');

		if ($GLOBALS['meta']['forum_prive_objets'] != 'non')
			$res .= ' '._T('text_article_propose_publication_forum');

		$res.= "</p>";
	}

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
		$type = 'article';
		$contexte = array(
			'id'=>$id_article,
			'id_rubrique'=>$row['id_rubrique'],
			'id_secteur' => $row['id_secteur']
		);
		$fond = recuperer_fond("prive/contenu/$type",$contexte);
		// permettre aux plugin de faire des modifs ou des ajouts
		$fond = pipeline('afficher_contenu_objet',
			array(
			'args'=>array(
				'type'=>$type,
				'id_objet'=>$id_article,
				'contexte'=>$contexte),
			'data'=> ($fond)));
	
		$res .= "<div id='wysiwyg'>$fond</div>";
	}
	return $res;
}

function bouton_proposer_article($id_article,$statut_article){
	$ret = "";

	if ($statut_article=='prepa'
		AND $id_auteur = $GLOBALS["visiteur_session"]["id_auteur"]
		AND $GLOBALS["visiteur_session"]["statut"] == "1comite"
		AND autoriser('modifier', 'article', $id_article)
		AND sql_fetsel("id_article", "spip_auteurs_articles", "id_article=".intval($id_article)." AND id_auteur=".intval($id_auteur))) {
			$ret .= debut_cadre_relief("", true);
			$ret .= "<div class='verdana3' style='text-align: center;'>";
			$ret .= "<div>"._T("texte_proposer_publication")."</div>";

			$ret .= bouton_action(_T("bouton_demande_publication"),
							generer_action_auteur('instituer_article', "$id_article-prop", self()), '', _T('confirm_changer_statut'));

			$ret .= "</div>";
			$ret .= fin_cadre_relief(true);

	}
	return $ret;
}
?>
