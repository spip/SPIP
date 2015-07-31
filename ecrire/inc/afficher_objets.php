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

$GLOBALS['my_sites']=array();

// http://doc.spip.org/@icone_table
function icone_table($type){
	$derog = array('document'=> 'doc-24.gif', 'mot'=>'mot-cle-24.gif','syndic_article'=>'site-24.gif', 'message' => 'messagerie-24.gif', 'groupes_mot'=>'mot-cle-24.gif');
	if (isset($derog[$type]))
		return $derog[$type];
	return "$type-24.gif";
}

// http://doc.spip.org/@lien_editer_objet
function lien_editer_objet($type,$key,$id){
	return $type == 'document' ? '' : generer_url_ecrire($type . "s_edit","$key=$id");
}

// http://doc.spip.org/@lien_voir_objet
function lien_voir_objet($type,$key,$id){
	if ($type == 'document') return generer_url_entite($id, 'document');
	$exec = array('article'=>'articles','breve'=>'breves_voir','rubrique'=>'naviguer','mot'=>'mots_edit', 'signature'=>'controle_petition');
	$exec = isset($exec[$type])?$exec[$type]:$type . "s";
	return generer_url_ecrire($exec,"$key=$id");
}

// http://doc.spip.org/@afficher_numero_edit
function afficher_numero_edit($id, $key, $type,$row=NULL) {
	global $spip_lang_right, $spip_lang_left,$my_sites;
	static $numero , $style='' ;
	if ($type=='syndic_article') {
		$redirect = _request('id_syndic') ? 'id_syndic='._request('id_syndic') : '';
		if (autoriser('modifier',$type,$id)) {
			if ($row['statut'] == "publie"){
			  $s =  "[<a href='". redirige_action_auteur('instituer_syndic',"$id-refuse", _request('exec'), $redirect) . "'><span style='color: black'>"._T('info_bloquer_lien')."</span></a>]";

			}
			else if ($row['statut'] == "refuse"){
			  $s =  "[<a href='". redirige_action_auteur('instituer_syndic',"$id-publie", _request('exec'), $redirect) . "'>"._T('info_retablir_lien')."</a>]";
			}
			else if ($row['statut'] == "off"
			AND isset($my_sites[$id]['miroir']) AND $my_sites[$id]['miroir'] == 'oui') {
				$s = '('._T('syndic_lien_obsolete').')';
			}
			else /* 'dispo' ou 'off' (dans le cas ancien site 'miroir') */
			{
			  $s = "[<a href='". redirige_action_auteur('instituer_syndic',"$id-publie", _request('exec'), $redirect) . "'>"._T('info_valider_lien')."</a>]";
			}
			return $s;
		}
	}

	if (!$style) {
		$style = " class='spip_xx-small' style='float: $spip_lang_right; padding-$spip_lang_left: 4px; color: black; '";

		$numero = _T('info_numero_abbreviation');
	}

	if (!autoriser('modifier',$type,$id) OR
	    !$href = lien_editer_objet($type,$key,$id)) {
		$bal ='span';
	} else {
		$bal = 'a';
		$href = "\nhref='"
		. $href
		. "' title='"
		. _T('bouton_modifier')
		. "'";
	}
	return "<$bal$style$href><b>"
	. $numero
	. $id
	. "</b></$bal>";
}

// libelle du titre de l'objet :
// la partie du titre a afficher dans un lien
// puis la partie hors lien
// http://doc.spip.org/@afficher_titre_objet
function afficher_titre_objet($type,$row){
	if (function_exists($f = "afficher_titre_$type"))
		return $f($row);

	$titre = isset($row['titre'])?sinon($row['titre'], "("._T('info_sans_titre_2').")"):
	(isset($row['nom'])?sinon($row['nom'], "("._T('info_sans_titre_2').")"):
	 (isset($row['nom_email'])?sinon($row['nom_email'], "("._T('info_sans_titre_2').")"):
	  ""));
	return array(typo(supprime_img($titre,'')),'');
}
// http://doc.spip.org/@afficher_titre_site
function afficher_titre_site($row){
	$nom = $row['nom_site'];

	$nom = $nom?(strlen($nom)>1?typo($nom):_T('info_sans_titre_2')):("("._T('info_sans_titre_2').")");

	$s2 = "&nbsp;&nbsp; <span class='spip_xx-small'>[<a href='"
	.$row['url_site']."'>"._T('lien_visite_site')."</a>]</span>";

	return array($nom,$s2);
}
// http://doc.spip.org/@afficher_titre_auteur
function afficher_titre_auteur($row){
	return array($row['nom'],
		((isset($row['restreint']) AND $row['restreint'])
		   ? (" &nbsp;<small>"._T('statut_admin_restreint')."</small>")
		   : ''));
}

// http://doc.spip.org/@afficher_titre_syndic_article
function afficher_titre_syndic_article($row){
	return array('', recuperer_fond(
		'prive/contenu/syndic_article',
		array('id' => $row['id_syndic_article'])
	));
}

// http://doc.spip.org/@afficher_complement_objet
function afficher_complement_objet($type,$row){
	if (function_exists($f = "afficher_complement_$type"))
		return $f($row);
	 return "";
}

// http://doc.spip.org/@afficher_complement_site
function afficher_complement_site($row){
	$syndication = $row['syndication'];
	$s = "";
	if ($syndication == 'off' OR $syndication == 'sus') {
		$s .= "<div style='color: red;'>"
			. http_img_pack('puce-orange-anim.gif', $syndication, "class='puce'",_T('info_panne_site_syndique'))
			. " "._T('info_probleme_grave')." </div>";
	}
	if ($syndication == "oui" or $syndication == "off" OR $syndication == 'sus'){
		$s .= "<div style='color: red;'>"._T('info_syndication')."</div>";
	}
	if ($syndication == "oui" OR $syndication == "off" OR $syndication == "sus") {
		$id_syndic = $row['id_syndic'];
		$total_art = sql_countsel("spip_syndic_articles", "id_syndic=$id_syndic");
		$s .= " " . $total_art . " " . _T('info_syndication_articles');
	} else {
			$s .= "&nbsp;";
	}
	return $s;
}
// http://doc.spip.org/@afficher_complement_syndic_article
function afficher_complement_syndic_article($row){
	global $my_sites;
	if ($GLOBALS['exec'] != 'sites') {
		$id_syndic = $row['id_syndic'];
		// $my_sites cache les resultats des requetes sur les sites
		if (!isset($my_sites[$id_syndic]))
			$my_sites[$id_syndic] = sql_fetsel("nom_site, moderation, miroir", "spip_syndic", "id_syndic=$id_syndic");

		$aff = $my_sites[$id_syndic]['nom_site'];
		if ($my_sites[$id_syndic]['moderation'] == 'oui')
			$aff = "<i>$aff</i>";

		$s = "<a href='" . generer_url_ecrire("sites","id_syndic=$id_syndic") . "'>$aff</a>";

		return $s;
	}
	return "";
}

// affichage des liste d'objets
// Cas generique, utilise pour tout sauf article
// http://doc.spip.org/@inc_afficher_objets_dist
function inc_afficher_objets_dist($type, $titre,$requete,$formater='', $force=false){
	if ($afficher = charger_fonction("afficher_{$type}s",'inc',true)){
		return $afficher($titre,$requete,$formater);
	}

	if (($GLOBALS['meta']['multi_rubriques'] == 'oui'
	     AND (!isset($GLOBALS['id_rubrique'])))
	OR $GLOBALS['meta']['multi_articles'] == 'oui') {
		$afficher_langue = true;

		if (isset($GLOBALS['langue_rubrique'])) $langue_defaut = $GLOBALS['langue_rubrique'];
		else $langue_defaut = $GLOBALS['meta']['langue_site'];
	} else $afficher_langue = $langue_defaut = '';

	$arg = array($afficher_langue, false, $langue_defaut, $formater, $type,id_table_objet($type));
	if (!function_exists($skel = "afficher_{$type}s_boucle")){
		$skel = "afficher_objet_boucle";
	}

	$presenter_liste = charger_fonction('presenter_liste', 'inc');
	$tmp_var = 't_' . substr(md5(join('', $requete)), 0, 4);
	$styles = array(array('arial11', 7), array('arial11'), array('arial1'), array('arial1'), array('arial1 centered', 100), array('arial1', 38));

	$tableau = array(); // ne sert pas ici
	return $presenter_liste($requete, $skel, $tableau, $arg, $force, $styles, $tmp_var, $titre, icone_table($type));
}

// http://doc.spip.org/@charger_fonction_logo_if
function charger_fonction_logo_if()
{
	global $spip_display;

	if ($spip_display == 1 OR $spip_display == 4 OR !isset($GLOBALS['meta']['image_process']))
	  return false;
	if ($GLOBALS['meta']['image_process'] == "non") return false;
	return charger_fonction('chercher_logo', 'inc');
}

// http://doc.spip.org/@afficher_objet_boucle
function afficher_objet_boucle($row, $own)
{
	global $connect_statut, $spip_lang_right;
	static $chercher_logo = true;

	list($afficher_langue, $affrub, $langue_defaut, $formater,$type,$primary) = $own;
	$vals = array();
	$id_objet = $row[$primary];
	if (autoriser('voir',$type,$id_objet)){

		$date_heure = isset($row['date'])?$row['date']:(isset($row['date_heure'])?$row['date_heure']:"");

		$statut = isset($row['statut'])?$row['statut']:"";
		if (isset($row['lang']))
		  changer_typo($lang = $row['lang']);
		else $lang = $langue_defaut;
		$lang_dir = lang_dir($lang);
		$id_rubrique = isset($row['id_rubrique'])?$row['id_rubrique']:0;

		$puce_statut = charger_fonction('puce_statut', 'inc');
		$vals[] = $puce_statut($id_objet, $statut, $id_rubrique, $type);

		list($titre,$suite) = afficher_titre_objet($type,$row);
		$flogo = '';
		if ($chercher_logo) {
			if ($chercher_logo !== true
			    OR $chercher_logo = charger_fonction_logo_if())
			  if ($logo = $chercher_logo($id_objet, $primary, 'on')) {
				list($fid, $dir, $nom, $format) = $logo;
				include_spip('inc/filtres_images_mini');
				$logo = image_reduire("<img src='$fid' alt='' />", 26, 20);
				if ($logo)
					$flogo = "\n<span style='float: $spip_lang_right; margin-top: -2px; margin-bottom: -2px;'>$logo</span>";
			  }
		}
		if ($titre) {
			$titre = "<a href='"
			.  lien_voir_objet($type,$primary,$id_objet)
			.  "'>"
			. $titre
			. "</a>";
		}
		$vals[] = "\n<div>$flogo$titre$suite</div>";

		$s = "";
		if ($afficher_langue){
			if (isset($row['langue_choisie'])){
				$s .= " <span class='spip_xx-small' style='color: #666666' dir='$lang_dir'>";
				if ($row['langue_choisie'] == "oui") $s .= "<b>".traduire_nom_langue($lang)."</b>";
				else $s .= "(".traduire_nom_langue($lang).")";
				$s .= "</span>";
			}
			elseif ($lang != $langue_defaut)
				$s .= " <span class='spip_xx-small' style='color: #666666' dir='$lang_dir'>".
					($lang
						? "(".traduire_nom_langue($lang).")"
						: ''
					)
				."</span>";
		}
		$vals[] = $s;

		$vals[] = afficher_complement_objet($type,$row);

		$s = "";
		if ($affrub && $id_rubrique) {
			$rub = sql_fetsel("id_rubrique, titre", "spip_rubriques", "id_rubrique=$id_rubrique");
			$id_rubrique = $rub['id_rubrique'];
			$s .= "<a href='" . generer_url_ecrire("naviguer","id_rubrique=$id_rubrique") . "' style=\"display:block;\">".typo($rub['titre'])."</a>";
		} else
		if ($statut){
			if ($statut != "prop")
					$s = affdate_jourcourt($date_heure);
				else
					$s .= _T('info_a_valider');
		}
		$vals[] = $s;

		$vals[] = afficher_numero_edit($id_objet, $primary, $type, $row);
	}
	return $vals;
}

// Cas particuliers -----------------------------------------------------------------

//
// Afficher tableau d'articles
//
// http://doc.spip.org/@inc_afficher_articles_dist
function inc_afficher_articles_dist($titre, $requete, $formater='') {

	if (!isset($requete['FROM'])) $requete['FROM'] = 'spip_articles AS articles';

	if (!isset($requete['SELECT'])) {
		$requete['SELECT'] = "articles.id_article, articles.titre, articles.id_rubrique, articles.statut, articles.date, articles.lang, articles.id_trad, articles.descriptif";
	}

	if (!isset($requete['GROUP BY'])) $requete['GROUP BY'] = '';

	$cpt = sql_countsel($requete['FROM'], $requete['WHERE'], $requete['GROUP BY']);

	if (!$cpt) return '' ;

	$requete['FROM'] = preg_replace("/(spip_articles(\s+AS\s+\w+)?)/i", "\\1 LEFT JOIN spip_petitions AS petitions ON articles.id_article=petitions.id_article", $requete['FROM']);

	$requete['SELECT'] .= ", petitions.id_article AS petition ";

	// memorisation des arguments pour gerer l'affichage par tranche
	// et/ou par langues.


	$hash = sauver_requete($titre, $requete, $formater);

	if (isset($requete['LIMIT'])) $cpt = min($requete['LIMIT'], $cpt);
	return afficher_articles_trad($titre, $requete, $formater, $hash, $cpt);
}

//
// Stocke la fonction ajax dans le fichier temp pour exec=memoriser
//

// http://doc.spip.org/@sauver_requete
function sauver_requete($titre, $requete, $formater)
{
	$r = $requete;
	unset($r['ORDER BY']);
	$hash = substr(md5(serialize($r) . $GLOBALS['meta']['gerer_trad'] . $titre), 0, 31);

	// on lit l'existant
	lire_fichier(_DIR_SESSIONS.'ajax_fonctions.txt', $ajax_fonctions);
	$ajax_fonctions = @unserialize($ajax_fonctions);

	// on ajoute notre fonction
	$v = array(time(), $titre, $requete, $formater);
	$ajax_fonctions[$hash] = $v;

	// supprime les fonctions trop vieilles
	foreach ($ajax_fonctions as $h => $fonc)
		if (time() - $fonc[0] > 48*3600)
			unset($ajax_fonctions[$h]);

	// enregistre
	ecrire_fichier(_DIR_SESSIONS.'ajax_fonctions.txt',
		serialize($ajax_fonctions));

	return $hash;

}
// http://doc.spip.org/@afficher_articles_trad
function afficher_articles_trad($titre_table, $requete, $formater, $hash, $cpt, $trad=0) {

	global $spip_lang_right;

	$tmp_var = 't' . substr($hash, 0, 7);

	if ($trad) {
		$formater = 'afficher_articles_trad_boucle';
		$icone = "langues-off-12.gif";
		$alt = _T('masquer_trad');
	} else {
		if (!$formater)
			$formater = charger_fonction('formater_article', 'inc');
		$icone = 'langues-12.gif';
		$alt = _T('afficher_trad');
	}

	$texte =  '<b>' . $titre_table  . '</b>';

	// Le parametre o sert a empecher le navigateur de reutiliser
	// un cache de tranche issu d'un autre tri

	$arg = "hash=$hash&o=" . $requete['ORDER BY'];

/*
	// DESACTIVE CAR AJOUTE UNE COMPLEXITE INUTILE -- A REVOIR
	// le micro "afficher les traductions"
	if (($GLOBALS['meta']['gerer_trad'] == "oui")) {
		$url = generer_url_ecrire('memoriser',"$arg&trad=" . (1-$trad));
		$texte =
		"\n<span style='float: $spip_lang_right;'><a href=\"#\""
		  . generer_onclic_ajax($url, $tmp_var, 0)
		  . "><img\nsrc='". chemin_image($icone) ."' alt='$alt' title='$alt' /></a></span>" . $texte;
	}
*/

/*
	// DESACTIVE CAR AJOUTE UNE COMPLEXITE INUTILE -- A REVOIR
	$url_t = generer_url_ecrire('memoriser',"hash=$hash&by=0%2Btitre,titre");
	$url_t = afficher_boutons_tri($url_t, $tmp_var);

	$url_d = generer_url_ecrire('memoriser',"hash=$hash&by=date");
	$url_d = afficher_boutons_tri($url_d, $tmp_var);
*/
	$url_t = $url_d = '';
	$presenter_liste = charger_fonction('presenter_liste', 'inc');
	$styles = array(array('', 11), array('verdana12','', $url_t), array('arial1', 80), array('arial1', 100, $url_d), array('arial1', 50));
	$tableau = array();
	$url = generer_url_ecrire('memoriser', "$arg&trad=$trad");
	$res = $presenter_liste($requete, $formater, $tableau, array(), false, $styles, $tmp_var, $texte, "article-24.gif", $url, $cpt);

	return ajax_action_greffe($tmp_var, '', $res);
}

// http://doc.spip.org/@afficher_boutons_tri
function afficher_boutons_tri($url, $tmp_var)
{
	static $monter = '';
	static $descendre = '';

	if (!$monter) {
		$monter = http_img_pack('monter-16.png', '<');
		$descendre = http_img_pack('descendre-16.png', '>');
	}

	$url_d = generer_onclic_ajax($url ."&amp;order=desc", $tmp_var, 0);
	$url_a = generer_onclic_ajax($url ."&amp;order=asc", $tmp_var, 0);
	
	return "<a href='$url'$url_d>$monter</a><a href='$url'$url_a>$descendre</a>";
}

// http://doc.spip.org/@afficher_articles_trad_boucle
function afficher_articles_trad_boucle($row, $own='')
{
  	global $spip_lang_right, $spip_display;

	$id_article = $row['id_article'];
	if (!autoriser('voir','article',$id_article)) return '';

	$titre = $row['titre'];
	$id_rubrique = $row['id_rubrique'];
	$statut = $row['statut'];
	$id_trad = $row['id_trad'];
	$lang = $row['lang'];

	$lang_dir = lang_dir($GLOBALS['lang_objet']);
	$dates_art = $langues_art = array();
	$ligne = "";

	$res_trad = sql_select("id_article, lang, date_modif", "spip_articles", "id_trad = $id_trad AND id_trad > 0");

	while ($row_trad = sql_fetch($res_trad)) {
		$id_article_trad = $row_trad["id_article"];
		$lang_trad = $row_trad["lang"];
		$date = $row_trad['date_modif'];
		$dates_art[$lang_trad] = $date;
		$langues_art[$lang_trad] = $id_article_trad;
		if ($id_article_trad == $id_trad) $date_ref = $date;
	}

	// faudrait sortir ces invariants de boucle

	if (($GLOBALS['meta']['multi_rubriques'] == 'oui' AND (!isset($GLOBALS['id_rubrique']))) OR $GLOBALS['meta']['multi_articles'] == 'oui') {
			$langue_defaut = isset($GLOBALS['langue_rubrique'])
			  ? $GLOBALS['meta']['langue_site']
			  : $GLOBALS['langue_rubrique'];
			if ($lang != $langue_defaut)
				$afficher_langue = " <span class='spip_xx-small' style='color: #666666'  dir='$lang_dir'>(".traduire_nom_langue($lang).")</span>";
	} else $afficher_langue = '';

	foreach(explode(',', $GLOBALS['meta']['langues_multilingue']) as $k){
		if (isset($langues_art[$k]) AND $langues_art[$k]<> $id_trad){
			$h = generer_url_ecrire("articles", "id_article=".$langues_art[$k]);
			$style = strtotime($dates_art[$k]) < strtotime($date_ref);
			$style = $style ? 'claire' : 'foncee';
			$ligne .= "<a href='$h' class='$style'>$k</a>";
		}
	}

	if (acces_restreint_rubrique($id_rubrique))
		$img = http_img_pack("admin-12.gif", _T('titre_image_administrateur'), "width='12' height='12'", _T('titre_image_admin_article'));
	else $img = '';

	if (!$titre) $titre =  _T('ecrire:info_sans_titre');
	if ($id_article == $id_trad) $titre = "<b>$titre</b>";

	$h = generer_url_ecrire("articles", "id_article=$id_article");

	$titre = "\n<div>"
	  . $img
	  . "<a href='$h' dir='$lang_dir' style=\"display:block;\">"
	  . typo(supprime_img($titre,''))
	  . "</a></div>";

	if ($spip_display == 4) return array($ligne);

	$ligne .= "<a href='$h'><span class='lang_base'>$lang</span></a>";

	// La petite puce de changement de statut
	$puce_statut = charger_fonction('puce_statut', 'inc');
	$puce = $puce_statut($id_article, $statut, $id_rubrique,'article');

	return array($puce,
		      $titre,
		      $afficher_langue,
		      "<div style='float: $spip_lang_right; margin-right: -10px;'>"
		      . $ligne
		      . "</div>");
}

// http://doc.spip.org/@afficher_auteurs_boucle
function afficher_auteurs_boucle($row, $own){
	$vals = array();
	list($afficher_langue, $affrub, $langue_defaut, $formater,$type,$primary) = $own;
	$formater_auteur = $formater ? $formater : charger_fonction('formater_auteur', 'inc');
	if ($row['statut'] == '0minirezo')
		$row['restreint'] = sql_countsel('spip_auteurs_rubriques', "id_auteur=".intval($row['id_auteur']));

	list($s, $mail, $nom, $w, $p) = $formater_auteur($row['id_auteur'],$row);
	if ($w) {
	  if (preg_match(',^([^>]*>)[^<]*(.*)$,', $w,$r)) {
	    $w = $r[1] . substr($row['site'],0,20) . $r[2];
	  }
	}
	$vals[] = $s;
	$vals[] = $mail;
	$vals[] = $nom
		. ((isset($row['restreint']) AND $row['restreint'])
		   ? (" &nbsp;<small>"._T('statut_admin_restreint')."</small>")
		   : '');
	$vals[] = $w;
	$vals[] = $p;
	return $vals;
}
?>
