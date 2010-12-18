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

// http://doc.spip.org/@exec_articles_tous_dist
function exec_articles_tous_dist()
{
	exec_articles_tous_args(intval(_request('id_rubrique')),
				_request('aff_art'),
				_request('sel_lang'));
}

// http://doc.spip.org/@exec_articles_tous_args
function exec_articles_tous_args($id_rubrique, $aff_art, $sel_lang)
{
	global $browser_layer,$spip_lang_right,$spip_lang_left;

	changer_typo(); // pour definir la direction de la langue
	if (!is_array($aff_art)) $aff_art = array('prop','publie');
	$enfant = arbo_articles_tous();

	$flag_trad = (($GLOBALS['meta']['multi_rubriques'] == 'oui' 
		OR $GLOBALS['meta']['multi_articles'] == 'oui') 
		AND $GLOBALS['meta']['gerer_trad'] == 'oui');

	list($article,$text_article,$aff_statut) = texte_articles_tous($sel_lang, $flag_trad, $aff_art, lang_dir());
	if (_AJAX AND $id_rubrique) {
		include_spip('inc/actions');
		ajax_retour(afficher_contenu_rubrique($article, $enfant, $text_article, $id_rubrique, $flag_trad, 2));
	}
	else {

		pipeline('exec_init',array('args'=>array('exec'=>'articles_tous'),'data'=>''));
		$commencer_page = charger_fonction('commencer_page', 'inc');
		echo $commencer_page(_T('titre_page_articles_tous'), "accueil", "tout-site");

		echo http_script('var img_deplierhaut = "'. chemin_image('noeud_plus.gif') .'";
var img_deplierbas = "'. chemin_image('noeud_moins.gif') . '";');

		echo http_script('', 'jquery-ui-1.8-drag-drop.min.js');
		echo http_script('', 'articles_tous_edite.js');
		echo http_script('', 'pause.js');
	
		echo debut_gauche('', true);
		echo formulaire_affiche_tous($aff_art, $aff_statut, $sel_lang);

		echo pipeline('affiche_gauche',array('args'=>array('exec'=>'articles_tous'),'data'=>''));
		echo creer_colonne_droite('', true);
		echo pipeline('affiche_droite',array('args'=>array('exec'=>'articles_tous'),'data'=>''));
		echo debut_droite('', true);

		if ($enfant AND $browser_layer)
		  echo couche_formulaire_tous();

		$out = "<textarea cols='1' rows='1' id='deplacements' style='display:none;' name='deplacements'></textarea>"
		  . "\n<div id='apply' style='display:none;text-align:$spip_lang_right'><input type='submit' value='"._T('bouton_changer')."' /></div>";

		echo "\n<div id='cancel' class='verdana2' style='display:none;text-align:$spip_lang_left;float:$spip_lang_left'>",
		  "<a href='javascript:annuler_deplacement();'>",
		  _T('bouton_annuler'),
		  "</a></div>",
		  redirige_action_post("reorganiser","",'articles_tous', '', $out),
		  "<ul id='articles_tous'><li id='rubrique-0' class='treeItem racine verdana2'>",
		  "<span class='holder icone'>&nbsp;</span>",
		  _T('info_racine_site'),
		  "\n<ul class=''>\n",
		  afficher_contenu_rubrique($article, $enfant, $text_article, 0, $flag_trad, 2),
		  "</ul></li></ul>\n",
		  fin_gauche(), fin_page();
	}
}

// http://doc.spip.org/@arbo_articles_tous
function arbo_articles_tous()
{

	$enfant = array();
	$result = sql_select("id_rubrique, titre, id_parent", "spip_rubriques", '','', '0+titre,titre');
	while ($row = sql_fetch($result)) {
		$id_rubrique = $row['id_rubrique'];
		if (autoriser('voir','rubrique',$id_rubrique)){
			$id_parent = $row['id_parent'];
			$enfant[$id_parent][$id_rubrique] = typo($row['titre']);
		}
	}
	return $enfant;
}

// http://doc.spip.org/@texte_articles_tous
function texte_articles_tous(&$sel_lang, $flag_trad, $aff_art,$spip_lang_dir){
	global $spip_lang;
	
	if ($flag_trad)
		$langues = explode(',', $GLOBALS['meta']['langues_multilingue']);
	else	$langues = array();

	$sel_lang[$spip_lang] = $spip_lang;

	if (autoriser('publierdans', 'rubrique', 0))
		$result = sql_select("id_article, titre, statut, id_rubrique, lang, id_trad, date_modif", "spip_articles", "", "", "date DESC");
	else 
		$result = sql_select("articles.id_article, articles.titre, articles.statut, articles.id_rubrique, articles.lang, articles.id_trad, articles.date_modif", "spip_articles AS articles LEFT JOIN spip_auteurs_articles AS lien ON articles.id_article=lien.id_article", "articles.statut = 'publie' OR articles.statut =	'prop' OR (articles.statut = 'prepa'  AND lien.id_auteur=" . sql_quote($GLOBALS['visiteur_session']['id_auteur']) . ")", "id_article", "articles.date DESC");

	while($row = sql_fetch($result)) {
		$id_rubrique=$row['id_rubrique'];
		$id_article = $row['id_article'];
		if (autoriser('voir','article',$id_article)){
			$titre = typo($row['titre']);
			$statut = $row['statut'];
			$lang = $row['lang'];
			$id_trad = $row['id_trad'];
			$date_modif = $row['date_modif'];
			
			$aff_statut[$statut] = true; // signale qu'il existe de tels articles
			$text_article[$id_article]["titre"] = strlen($titre)?$titre:_T('ecrire:info_sans_titre');
			$text_article[$id_article]["statut"] = $statut;
			$text_article[$id_article]["lang"] = $lang;
			$text_article[$id_article]["id_trad"] = $id_trad;
			$text_article[$id_article]["date_modif"] = $date_modif;
			$GLOBALS['langues_utilisees'][$lang] = true;
			
			if (count($langues) > 1) {
				while (list(, $l) = each ($langues)) {
					if (in_array($l, $sel_lang)) $text_article[$id_article]["trad"]["$l"] =  "<span class='creer'>$l</span>";
				}
			}
			
			if ($id_trad == $id_article OR $id_trad == 0) {
				$text_article[$id_article]["trad"]["$lang"] = "<span class='lang_base' dir='$spip_lang_dir'>$lang</span>";
			}
			
			if (in_array($statut, $aff_art))
				$article[$id_rubrique][] = $id_article;
		}
	}

	if ($text_article)
		foreach ($text_article as $id_article => $v) {
			$id_trad = $v["id_trad"];
			$lang = $v['lang'];
				
			
			if ($id_trad > 0 AND $id_trad != $id_article AND in_array($lang, $sel_lang)) {
				if ($text_article[$id_trad]["date_modif"] < $v["date_modif"]) 
					$c = 'foncee';
				else
					$c = 'claire';
				$text_article[$id_trad]["trad"][$lang] =
					"<a class='$c' href='" . generer_url_ecrire("articles","id_article=$id_article") . "'>$lang</a>";
			}
		}
	return array($article,$text_article,$aff_statut);
}

//  checkbox avec image

// http://doc.spip.org/@http_label_img
function http_label_img($statut, $etat, $var, $img, $texte) {
	return  
		"<label for='$statut'>" .
		boutonne('checkbox',
			$var . '[]',
			$statut,
			(($etat !== false) ? ' checked="checked" ' : '') .
			"id='$statut'") .
		"&nbsp;" . 
		http_img_pack($img, $texte, "style='width: 8px; height: 9px; border: 0px;'", $texte) .
		" " .
		$texte .
		"</label>" .
		"<br />\n";
}

// http://doc.spip.org/@formulaire_affiche_tous
function formulaire_affiche_tous($aff_art, $aff_statut,$sel_lang)
{
	global $spip_lang_left, $spip_lang_right, $spip_lang;
	
	$out = "\n<input type='hidden' name='aff_art[]' value='x' />\n"
	. "<b>"._T('titre_cadre_afficher_article')."&nbsp;:</b><br />\n";
	
	if (isset($aff_statut['prepa']) && $aff_statut['prepa'])
		$out .= http_label_img('prepa',
				    in_array('prepa', $aff_art),
				    'aff_art',
				    'puce-blanche-breve.gif',
				    _T('texte_statut_en_cours_redaction'));
	
	if (isset($aff_statut['prop']) && $aff_statut['prop'])
		$out .= http_label_img('prop',
				    in_array('prop', $aff_art),
				    'aff_art',
				    'puce-orange-breve.gif',
				    _T('texte_statut_attente_validation'));
		
	if (isset($aff_statut['publie']) && $aff_statut['publie'])
		$out .= http_label_img('publie',
				    in_array('publie', $aff_art),
				    'aff_art',
				    'puce-verte-breve.gif',
				    _T('texte_statut_publies'));
	
	if (isset($aff_statut['refuse']) && $aff_statut['refuse'])
		$out .= http_label_img('refuse',
				    in_array('refuse', $aff_art),
				    'aff_art',
				    'puce-rouge-breve.gif',
				    _T('texte_statut_refuses'));
	
	if (isset($aff_statut['poubelle']) && $aff_statut['poubelle'])
		$out .= http_label_img('poubelle',
				    in_array('poubelle', $aff_art),
				    'aff_art',
				    'puce-poubelle-breve.gif',
				    _T('texte_statut_poubelle'));
	
	$out .= "\n<div style='text-align: $spip_lang_right'><input type='submit' value='"._T('bouton_changer')."' /></div>";
	
	
	// GERER LE MULTILINGUISME
	if (($GLOBALS['meta']['multi_rubriques'] == 'oui' OR $GLOBALS['meta']['multi_articles'] == 'oui') AND $GLOBALS['meta']['gerer_trad'] == 'oui') {

		// bloc legende
		$lf = $GLOBALS['meta']['langue_site'];
		$out .= "<hr />\n<div class='verdana2'>";
		$out .= _T('info_tout_site6');
		$out .= "\n<div><span class='lang_base'>$lf</span> ". _T('info_tout_site5') ." </div>";
		$out .= "\n<div><span class='creer'>$lf</span> ". _T('info_tout_site2') ." </div>";
		$out .= "\n<div><a class='claire'>$lf</a> ". _T('info_tout_site3'). " </div>";
		$out .= "\n<div><a class='foncee'>$lf</a> ". _T('info_tout_site4'). " </div>";
		$out .= "</div>\n";
	
		// bloc choix de langue
		$langues = explode(',', $GLOBALS['meta']['langues_multilingue']);
		if (count($langues) > 1) {
			sort($langues);
			$out .= "\n<br />\n<div class='verdana2'><b><label for='sel_lang'>"._T('titre_cadre_afficher_traductions')."</label></b>\n<br />";
			$out .= "<select style='width:100%' name='sel_lang[]' id='sel_lang' size='".count($langues)."' multiple='multiple'>";
			while (list(, $l) = each ($langues)) {
			  $out .= "<option value='$l'" .
			    (in_array($l,$sel_lang) ? " selected='selected'" : "") .
			    ">" .
			    traduire_nom_langue($l) .
			    "</option>\n"; 
			}
			$out .= "</select></div>\n";
	
			$out .= "\n<div style='text-align: $spip_lang_right'><input type='submit' value='"._T('bouton_changer')."' /></div>";
		}
	}

	$out = debut_boite_info(true) . $out  . fin_boite_info(true);

	return generer_form_ecrire('articles_tous', $out);
}

// http://doc.spip.org/@couche_formulaire_tous
function couche_formulaire_tous()
{
	return "<div>&nbsp;</div>"
	. "<b class='verdana3'>"
	. "<a href=\"javascript:deplie_arbre()\">"
	. _T('lien_tout_deplier')
	. "</a>"
	. "</b>"
	. " | "
	. "<b class='verdana3'>"
	. "<a href=\"javascript:plie_arbre()\">"
	. _T('lien_tout_replier')
	. "</a>"
	. "</b>"
	. "<div>&nbsp;</div>";
}

// http://doc.spip.org/@afficher_contenu_rubrique
function afficher_contenu_rubrique(&$article, &$enfant, &$text_article, $id_rubrique, $flag_trad, $profondeur){
	static $ajax_args=NULL;
	$out = "";
	if ($profondeur!=0){
		if (isset($article[$id_rubrique]))
			$out .= afficher_article_tous_rubrique($text_article, $article[$id_rubrique], $id_rubrique, $flag_trad);
		if (isset($enfant[$id_rubrique]))
			$out .= afficher_rubriques_filles($article, $enfant, $text_article, $id_rubrique, $flag_trad, $profondeur);
	}
	else{
		if (isset($article[$id_rubrique]) || isset($enfant[$id_rubrique])){
			if ($ajax_args==NULL){
				$ajax_args = "";
				if (is_array($aff_art = _request('aff_art')))
					foreach($aff_art as $aff)
						$ajax_args.="&aff_art[]=$aff";
				if (is_array($sel_lang = _request('sel_lang')))
					foreach($sel_lang as $sel)
						$ajax_args.="&sel_lang[]=$sel";
			}
			$out = "<li><a href='".generer_url_ecrire('articles_tous',"id_rubrique=$id_rubrique&$ajax_args")."' class='ajax' rel='ul$id_rubrique'>"._T('info_tout_site')."</a></li>";
		}
	}
	return $out;
}
// http://doc.spip.org/@afficher_rubriques_filles
function afficher_rubriques_filles(&$article, &$enfant, &$text_article, $id_parent, $flag_trad, $profondeur=-1) {
	$out = "";

	if (!$enfant[$id_parent]) return;
	$profondeur--;

	while (list($id_rubrique, $titre) = each($enfant[$id_parent]) ) {
		$out .= "<li id='rubrique-$id_rubrique' class='treeItem " .
			(($id_parent==0)?"sec":"rub") .
			"'>" .
			//$lesenfants?'<img src="'. chemin_image('deplierhaut.gif') .'" class="expandImage" />':'' .
		  "<span class='holder icone'> </span><a href='" .
		   generer_url_ecrire("naviguer","id_rubrique=$id_rubrique") .
		   "' class='titre'>$titre</a>";
		
		$lesenfants = afficher_contenu_rubrique($article, $enfant, $text_article, $id_rubrique, $flag_trad, $profondeur);
		if ($lesenfants)
			$out .= "\n<ul id='ul$id_rubrique'>\n$lesenfants</ul>\n";
		$out .= "</li>\n";
	}
	return $out;
}

// http://doc.spip.org/@afficher_article_tous_rubrique
function afficher_article_tous_rubrique(&$text_article, $tous, $id_rubrique, $flag_trad) 
{
	$res = '';
	$puce_statut = charger_fonction('puce_statut', 'inc');
	while(list(,$zarticle) = each($tous) ) {
		$attarticle = &$text_article[$zarticle];
		$zelang = $attarticle["lang"];
		unset ($attarticle["trad"][$zelang]);
		if ($attarticle["id_trad"] == 0
		OR $attarticle["id_trad"] == $zarticle) {
			$auteurs = trouve_auteurs_articles($zarticle);

			$res .= "\n<li id='article-$zarticle' class='treeItem art tr_liste'>";
			if (count($attarticle["trad"]) > 0) {
				ksort($attarticle["trad"]);
				$res .= "\n<span class='trad_float'>" 
				.  join('',$attarticle["trad"])
				.  "</span>";
			}
			$res .= "\n"
				. "<span class='icone'> </span>"
			  . "<div class='puce_statut'>".$puce_statut($zarticle, $attarticle["statut"], $id_rubrique,'article')."</div>"
			  . "<span><a"
			  . ($auteurs ? (' title="' . entites_html($auteurs). '"') :'')
			  . "\nhref='"
			  . generer_url_ecrire("articles","id_article=$zarticle")
			  . "' class='titre'>"
			  . ($flag_trad ? "<span class='lang_base'>$zelang</span> " : '')
			  . "<span>"
			  . $attarticle["titre"]
			  . "</span></a></span>"
			  . "</li>";
		}
	}
	return (!$res ? '' : $res);
}

// http://doc.spip.org/@trouve_auteurs_articles
function trouve_auteurs_articles($id_article)
{
	return corriger_typo(join(", ", array_map('array_shift', sql_allfetsel("nom", "spip_auteurs AS A LEFT JOIN spip_auteurs_articles AS L ON A.id_auteur=L.id_auteur", "L.id_article=$id_article", "", "nom"))));
}
?>
