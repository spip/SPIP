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

function exec_referencer_traduction_dist()
{
	include_spip('inc/actions');

	$id_article = intval(_request('id_article'));

	if ($GLOBALS['connect_toutes_rubriques']) // pour eviter SQL
		$droit = true;
	else	$droit = acces_article($id_article);

	if (!$droit) {
		spip_log("Tentative d'intrusion de " . $GLOBALS['auteur_session']['nom'] . " dans " . $GLOBALS['exec']);
		include_spip('inc/minipres');
		minipres(_T('info_acces_interdit'));
	}

	$row = spip_fetch_array(spip_query("SELECT id_trad, id_rubrique FROM spip_articles WHERE id_article=$id_article"));

	return formulaire_referencer_traduction($id_article, $row['id_rubrique'], $row['id_trad'], 'ajax'); 
}

function formulaire_referencer_traduction($id_article, $id_rubrique, $id_trad, $flag_editable, $trad_err='')
{
	global $connect_statut, $couleur_claire, $options, $connect_toutes_rubriques, $spip_lang_right, $dir_lang;

	$langue_article = spip_fetch_array(spip_query("SELECT lang FROM spip_articles WHERE id_article=$id_article"));

	$langue_article = $langue_article['lang'];

	$reponse = '';
	// Choix langue article
	if ($GLOBALS['meta']['multi_articles'] == 'oui' AND $flag_editable) {

		$row = spip_fetch_array(spip_query("SELECT lang FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
		$langue_parent = $row['lang'];

		if (!$langue_parent)
			$langue_parent = $GLOBALS['meta']['langue_site'];
		if (!$langue_article)
			$langue_article = $langue_parent;

		$menu = menu_langues('changer_lang', $langue_article, _T('info_multi_cet_article').' ', $langue_parent, 'ajax');

		$menu = ajax_action_auteur('referencer_traduction', "$id_article,$id_rubrique","articles","id_article=$id_article", $menu);

		$reponse .= debut_block_invisible('languesarticle')
		. debut_cadre_couleur('',true)
		. "\n<div style='text-align: center;'>"
		. $menu
		. "</div>\n"
		. fin_cadre_couleur(true)
		. fin_block();
	}

	if ($trad_err)
		$reponse .= "<div><font color='red' size='2' face='verdana,arial,helvetica,sans-serif'>"._T('trad_deja_traduit'). "</font></div>";

		// Afficher la liste des traductions
	$table = !$id_trad ? array() : articles_traduction($id_article, $id_trad);

		// bloc traductions
	if (count($table) > 0) {

		$largeurs = array(7, 12, '', 100);
		$styles = array('', '', 'arial2', 'arial2');

		$reponse .= "\n<div class='liste'>"
		. bandeau_titre_boite2(_T('trad_article_traduction'),'', 'white', 'black', false)
		. "<table width='100%' cellspacing='0' border='0' cellpadding='2'>"
		. afficher_liste ($largeurs, $table, $styles)
		. "</table>"
		. "</div>";
	}

	// changer les globales $dir_lang etc
	changer_typo($langue_article);

	$form = "<table width='100%'><tr>";

	if ($flag_editable AND $options == "avancees" AND !$table) {
			// Formulaire pour lier a un article
		$form .= "<td class='arial2' width='60%'>"
		. ajax_action_auteur("referencer_traduction",
				$id_article,
				'articles',
				"id_article=$id_article",
				(_T('trad_lier') .
				 "\n<input type='text' class='fondl' name='lier_trad' size='5' />\n<input type='submit' value='"._T('bouton_valider')."' class='fondl' />"))
		. "</td>\n"
		. "<td background='' width='10'> &nbsp; </td>"
		. "<td background='" . _DIR_IMG_PACK . "tirets-separation.gif' width='2'>". http_img_pack('rien.gif', " ", "width='2' height='2'") . "</td>"
		. "<td background='' width='10'> &nbsp; </td>";
	}

	$form .= "<td>"
	. icone_horizontale(_T('trad_new'), generer_url_ecrire("articles_edit","new=oui&lier_trad=$id_article&id_rubrique=$id_rubrique"), "traductions-24.gif", "creer.gif", false)
	. "</td>";

	if ($flag_editable AND $options == "avancees" AND $table) {
		$clic = _T('trad_delier');
		$form .= "<td background='' width='10'> &nbsp; </td>"
		. "<td background='" . _DIR_IMG_PACK . "tirets-separation.gif' width='2'>". http_img_pack('rien.gif', " ", "width='2' height='2'") . "</td>"
		. "<td background='' width='10'> &nbsp; </td>"
		. "<td>"
		  // la 1ere occurrence de clic ne sert pas en Ajax
		. icone_horizontale($clic, ajax_action_auteur("referencer_traduction","$id_article,-$id_trad",'articles', "id_article=$id_article",array($clic)), "traductions-24.gif", "supprimer.gif", false)
		. "</td>\n";
	}

	$form .= "</tr></table>";

	if ($GLOBALS['meta']['gerer_trad'] == 'oui')
		$bouton = _T('titre_langue_trad_article');
	else
		$bouton = _T('titre_langue_article');

	if ($langue_article)
		$bouton .= "&nbsp; (".traduire_nom_langue($langue_article).")";
	$bouton = bouton_block_invisible('lier_traductions').$bouton;

	$res = "\n<div>&nbsp;</div>\n" // place pour l'animation pendant Ajax
	. debut_cadre_enfonce('langues-24.gif', true, "", $bouton)
	. $reponse
	. debut_block_invisible('lier_traductions')
	. $form
	. fin_block()
	. fin_cadre_enfonce(true);

	return ($flag_editable === 'ajax')
	? $res
	: "\n<div id='referencer_traduction-$id_article'>$res</div>";
}


// http://doc.spip.org/@articles_traduction
function articles_traduction($id_article, $id_trad)
{
	global $connect_toutes_rubriques, $dir_lang;

	$result_trad = spip_query("SELECT id_article, id_rubrique, titre, lang, statut FROM spip_articles WHERE id_trad = $id_trad");
	
	$table= array();

	while ($row = spip_fetch_array($result_trad)) {
		$vals = array();
		$id_article_trad = $row["id_article"];
		$id_rubrique_trad = $row["id_rubrique"];
		$titre_trad = $row["titre"];
		$lang_trad = $row["lang"];
		$statut_trad = $row["statut"];

		changer_typo($lang_trad);
		$titre_trad = "<span $dir_lang>$titre_trad</span>";

		$vals[] = http_img_pack("puce-".puce_statut($statut_trad).'.gif', "", "width='7' height='7' border='0' NAME='statut'");
		
		if ($id_article_trad == $id_trad) {
			$vals[] = http_img_pack('langues-12.gif', "", "width='12' height='12' border='0'");
			$titre_trad = "<b>$titre_trad</b>";
		} else {
		  if (!$connect_toutes_rubriques)
			$vals[] = http_img_pack('langues-off-12.gif', "", "width='12' height='12' border='0'");
		  else 
		    $vals[] = ajax_action_auteur("referencer_traduction", "$id_article,$id_trad,$id_article_trad", 'articles', "id_article=$id_article", array(http_img_pack('langues-off-12.gif', _T('trad_reference'), "width='12' height='12' border='0'"), ' title="' . _T('trad_reference') . '"'));
		}

		$s = typo($titre_trad);
		if ($id_article_trad != $id_article) 
			$s = "<a href='" . generer_url_ecrire("articles","id_article=$id_article_trad") . "'>$s</a>";
		if ($id_article_trad == $id_trad)
			$s .= " "._T('trad_reference');

		$vals[] = $s;
		$vals[] = traduire_nom_langue($lang_trad);
		$table[] = $vals;
	}

	return $table;
}
?>
