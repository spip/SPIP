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

//
// Le bandeau des gadgets s'affiche en deux temps :
// 1. On affiche un minimum de <div> permettant aux boutons de jouer
//    du on/off au survol
//    -> fonction bandeau_gadgets()
// 2. En fin de page on envoie le vrai contenu (bien lourd) via innerHTML
//    -> fonction dessiner_gadgets()
//


// http://doc.spip.org/@inc_gadgets_dist
function inc_gadgets_dist($id_rubrique, $gadget)
{
	$gadget = 'gadget_' . $gadget;
	return  (function_exists($gadget)) ? $gadget($id_rubrique) : '';
}
//
// GADGET DES RUBRIQUES
//
// http://doc.spip.org/@extraire_article
function extraire_article($id_p) {
	if (array_key_exists($id_p, $GLOBALS['db_art_cache'])) {
		return $GLOBALS['db_art_cache'][$id_p];
	} else {
		return array();
	}
}

// http://doc.spip.org/@gen_liste_rubriques
function gen_liste_rubriques() {

	// ici, un petit fichier cache ne fait pas de mal
	if (lire_fichier(_DIR_TMP.'cache-menu-rubriques.txt', $cache)
	AND list($date,$GLOBALS['db_art_cache']) = @unserialize($cache)
	AND $date == $GLOBALS['meta']["date_calcul_rubriques"])
		return; // c'etait en cache :-)

	// se restreindre aux rubriques utilisees recemment +secteurs
	$liste="0";
	$s = spip_query("SELECT id_rubrique FROM spip_rubriques ORDER BY id_parent=0 DESC, date DESC LIMIT 500");
	while ($t = spip_fetch_array($s))
		$liste .=",".$t['id_rubrique']; 
	 
	$res = spip_query("SELECT id_rubrique, id_parent, titre FROM spip_rubriques WHERE id_rubrique IN ($liste) ORDER BY id_parent,0+titre,titre");

	// il ne faut pas filtrer le autoriser voir ici car on met le resultat en cache, commun a tout le monde
	$GLOBALS['db_art_cache'] = array();
	if (spip_num_rows($res) > 0) { 
		while ($row = spip_fetch_array($res)) {
			$id = $row['id_rubrique'];
			$parent = $row['id_parent'];
			$GLOBALS['db_art_cache'][$parent][$id] = 
					supprimer_numero(typo(sinon($row['titre'], _T('ecrire:info_sans_titre'))));
		}
	}

	// ecrire dans le cache
	ecrire_fichier(_DIR_TMP.'cache-menu-rubriques.txt',
		serialize(array(
			$GLOBALS['meta']["date_calcul_rubriques"],
			$GLOBALS['db_art_cache']
		))
	);
}


// http://doc.spip.org/@gadget_rubriques
function gadget_rubriques() {
	global $spip_ecran;
        
	$largeur_t = ($spip_ecran == "large") ? 900 : 650;
	gen_liste_rubriques(); 
	$arr_low = extraire_article(0);

	$total_lignes = $i = sizeof($arr_low);
	$ret = '';

	if ($i > 0) {
		$nb_col = min(8,ceil($total_lignes / 30));
		if ($nb_col <= 1) $nb_col =  ceil($total_lignes / 10);
		$max_lignes = ceil($total_lignes / $nb_col);
		$largeur = min(200, ceil($largeur_t / $nb_col)); 
		$count_lignes = 0;
		$style = " style='z-index: 0; vertical-align: top;'";

		foreach( $arr_low as $id_rubrique => $titre_rubrique) {
			if ($count_lignes == $max_lignes) {
				$count_lignes = 0;
				$ret .= "</div></td>\n<td$style><div class='bandeau_rubriques'>";
			}
			$count_lignes ++;
			if (autoriser('voir','rubrique',$id_rubrique)){
			  $ret .= bandeau_rubrique($id_rubrique, $titre_rubrique, 1, $largeur);
			}
		}

		$ret = "<table><tr>\n<td$style><div class='bandeau_rubriques'>"
		. $ret
		. "\n</div></td></tr></table>\n";
	}
	unset($GLOBALS['db_art_cache']); // On libere la memoire

	return "<div>&nbsp;</div>" . $ret;
}


// http://doc.spip.org/@bandeau_rubrique
function bandeau_rubrique($id_rubrique, $titre_rubrique, $zdecal, $largeur) {
	global $spip_ecran, $spip_display;
	global $spip_lang, $spip_lang_rtl, $spip_lang_left, $spip_lang_right;

	$titre_rubrique = preg_replace(',[\x00-\x1f]+,', ' ', $titre_rubrique);
	// Limiter volontairement le nombre de sous-menus 
	$zmax = 6;

	if ($zdecal == 1) $image = "secteur-12.gif";
	//else $image = "rubrique-12.gif";
	else $image = '';

	if ($image)
		$image = " background-image: url(" . http_wrapper($image) .");";

	$nav = "<a href='"
	. generer_url_ecrire('naviguer', 'id_rubrique='.$id_rubrique)
	. "'\nclass='bandeau_rub' style='width: "
	. $largeur
	. "px;"
	. $image
	. "'>\n&nbsp;"
	. supprimer_tags($titre_rubrique)
	. "</a>\n";

	if ($zdecal >= $zmax) return "\n<div>$nav</div>";

	$arr_rub = extraire_article($id_rubrique);
	$i = sizeof($arr_rub);
	if (!$i) return "\n<div>$nav</div>";

	$pxdecal = max(15, ceil($largeur/5)) . 'px';
	$idom = 'b_' . $id_rubrique;

	$ret = "<div class='pos_r'
onmouseover=\"montrer('$idom');\"
onmouseout=\"cacher('$idom'); \">"
	. '<div class="brt">'
	. $nav
	. "</div>\n<div class='bandeau_rub' style='top: 14px; left: "
	. $pxdecal
	. "; z-index: "
	. $zdecal
	. ";' id='"
	. $idom
	. "'><table cellspacing='0' cellpadding='0'><tr><td valign='top'>";

	if ($nb_rub = count($arr_rub)) {
		  $nb_col = min(10,max(1,ceil($nb_rub / 10)));
		  $ret_ligne = max(4,ceil($nb_rub / $nb_col));
	}
	$count_ligne = 0;
	foreach( $arr_rub as $id_rub => $titre_rub) {
			$count_ligne ++;
			
			if ($count_ligne > $ret_ligne) {
				$count_ligne = 0;
				$ret .= "</td>";
				$ret .= '<td valign="top" style="border-left: 1px solid #cccccc;">';

			}
			if (autoriser('voir','rubrique',$id_rub)){
				$titre = supprimer_numero(typo($titre_rub));
				$ret .= bandeau_rubrique($id_rub, $titre, $zdecal+1, $largeur);
			}
		}
	$ret .= "</td></tr></table>\n";
	$ret .= "</div></div>\n";
	return $ret;
}

// FIN GADGET DES RUBRIQUES


//
// GADGET DE NAVIGATION RAPIDE
//
// http://doc.spip.org/@gadget_navigation
function gadget_navigation($id_rubrique) {
	global $connect_id_auteur, $connect_login, $connect_statut, $couleur_claire,$couleur_foncee, $spip_lang_left, $spip_lang_right, $spip_ecran;

	$gadget = '<div style="width: 300px;">';

	$vos_articles = spip_query("SELECT articles.id_article, articles.id_rubrique, articles.titre, articles.statut FROM spip_articles AS articles, spip_auteurs_articles AS lien WHERE articles.id_article=lien.id_article AND lien.id_auteur=$connect_id_auteur AND articles.statut='prepa' ORDER BY articles.date DESC LIMIT 5");
	if (spip_num_rows($vos_articles) > 0) {
			$gadget .= "<div>&nbsp;</div>";
			$gadget .= "<div class='bandeau_rubriques' style='z-index: 1;'>";
			$gadget .= bandeau_titre_boite2(afficher_plus(generer_url_ecrire("articles_page","")) . '<b>' ._T('info_en_cours_validation')  . '</b>', "article-24.gif", $couleur_foncee, 'white', false);
			$gadget .= "\n<div class='plan-articles'>\n";
			while($row = spip_fetch_array($vos_articles)) {
				$id_article = $row['id_article'];
				$titre = typo(sinon($row['titre'], _T('ecrire:info_sans_titre')));
				$statut = $row['statut'];
				$gadget .= "<a class='$statut spip_xx-small' href='" . generer_url_ecrire("articles","id_article=$id_article") . "'>$titre</a>\n";
			}
			$gadget .= "</div>";
			$gadget .= "</div>";
	}
	
	$vos_articles = spip_query("SELECT id_article, id_rubrique, titre, statut FROM spip_articles WHERE statut='prop' ORDER BY date DESC LIMIT 5");
	if (spip_num_rows($vos_articles) > 0) {
			$gadget .= "<div>&nbsp;</div>";
			$gadget .= "<div class='bandeau_rubriques' style='z-index: 1;'>";
			$gadget .= bandeau_titre_boite2(afficher_plus('./') . '<b>' . _T('info_articles_proposes') . '</b>', "article-24.gif", $couleur_foncee, 'white', false);
			$gadget .= "<div class='plan-articles'>";
			while($row = spip_fetch_array($vos_articles)) {
				$id_article = $row['id_article'];
				$titre = sinon($row['titre'], _T('ecrire:info_sans_titre'));
				$statut = $row['statut'];
	
				$gadget .= "<a class='$statut spip_xx-small' href='" . generer_url_ecrire("articles","id_article=$id_article") . "'>$titre</a>";
			}
			$gadget .= "</div>";
			$gadget .= "</div>";
	}

	$vos_articles = spip_query("SELECT * FROM spip_breves WHERE statut='prop' ORDER BY date_heure DESC LIMIT 5");
	if (spip_num_rows($vos_articles) > 0) {
			$gadget .= "<div>&nbsp;</div>";
			$gadget .= "<div class='bandeau_rubriques' style='z-index: 1;'>";
			$gadget .= bandeau_titre_boite2(afficher_plus(generer_url_ecrire("breves")).'<b>' . _T('info_breves_valider') . '</b>', "breve-24.gif", "$couleur_foncee", "white", false);
			$gadget .= "<div class='plan-articles'>";
			while($row = spip_fetch_array($vos_articles)) {
				$id_breve = $row['id_breve'];
				$titre = typo(sinon($row['titre'], _T('ecrire:info_sans_titre')));
				$statut = $row['statut'];
	
				$gadget .= "<a class='$statut spip_xx-small' href='" . generer_url_ecrire("breves_voir","id_breve=$id_breve") . "'>$titre</a>";
			}
			$gadget .= "</div>";
			$gadget .= "</div>";
	}

	$result = spip_query("SELECT id_rubrique FROM spip_rubriques LIMIT 1");
		
	if (spip_num_rows($result) > 0) {
			$gadget .= "<div>&nbsp;</div>";
			if ($id_rubrique > 0) {
				$dans_rub = "&id_rubrique=$id_rubrique";
				$dans_parent = "&id_parent=$id_rubrique";
			} else $dans_rub = $dans_parent = '';
			if ($connect_statut == "0minirezo") {	
				$gadget .= "<div style='width: 140px; float: $spip_lang_left;'>";
				if ($id_rubrique > 0)
					$gadget .= icone_horizontale(_T('icone_creer_sous_rubrique'), generer_url_ecrire("rubriques_edit","new=oui$dans_parent"), "rubrique-24.gif", "creer.gif", false);
				else 
					$gadget .= icone_horizontale(_T('icone_creer_rubrique'), generer_url_ecrire("rubriques_edit","new=oui"), "rubrique-24.gif", "creer.gif", false);
				$gadget .= "</div>";
			}		
			$gadget .= "<div style='width: 140px; float: $spip_lang_left;'>";
			$gadget .= icone_horizontale(_T('icone_ecrire_article'), generer_url_ecrire("articles_edit","new=oui$dans_rub"), "article-24.gif","creer.gif", false);
			$gadget .= "</div>";
			
			if ($GLOBALS['meta']["activer_breves"] != "non") {
				$gadget .= "<div style='width: 140px;  float: $spip_lang_left;'>";
				$gadget .= icone_horizontale(_T('icone_nouvelle_breve'), generer_url_ecrire("breves_edit","new=oui$dans_rub"), "breve-24.gif","creer.gif", false);
				$gadget .= "</div>";
			}
			
			if ($GLOBALS['meta']["activer_sites"] == 'oui') {
				if ($connect_statut == '0minirezo' OR $GLOBALS['meta']["proposer_sites"] > 0) {
					$gadget .= "<div style='width: 140px; float: $spip_lang_left;'>";
					$gadget .= icone_horizontale(_T('info_sites_referencer'), generer_url_ecrire("sites_edit","new=oui$dans_parent"), "site-24.gif","creer.gif", false);
					$gadget .= "</div>";
				}
			}
			
	}

	$gadget .="</div>";

	return $gadget;
}


// http://doc.spip.org/@bandeau_gadgets
function bandeau_gadgets($largeur, $options, $id_rubrique) {
	global $connect_id_auteur, $connect_login, $connect_statut, $couleur_claire,$couleur_foncee, $spip_lang_left, $spip_lang_right, $spip_ecran;

	$bandeau = "<div id='bandeau-gadgets'>".
	"\n<table width='$largeur' cellpadding='0' cellspacing='0'><tr><td>\n<div style='position: relative; z-index: 1000;'>"

	// GADGET Menu rubriques
	. "\n<div id='bandeautoutsite' class='bandeau bandeau_couleur_sous' style='$spip_lang_left: 0px;'>"
	. "<a href='"
	. generer_url_ecrire("articles_tous")
	. "' class='lien_sous'" 
	. ">"
	._T('icone_site_entier')
	. "</a>"
	. "\n<div id='gadget-rubriques'></div>"
	. "</div>";
	// FIN GADGET Menu rubriques


	// GADGET Navigation rapide
	$bandeau .= "<div id='bandeaunavrapide' class='bandeau bandeau_couleur_sous' style='$spip_lang_left: 30px;'>"
	. "<a href='" . generer_url_ecrire("brouteur", ($id_rubrique ? "id_rubrique=$id_rubrique" : '')) . "' class='lien_sous'>" . _T('icone_brouteur') . "</a>"
	. "\n<div id='gadget-navigation'></div>\n"
	. "</div>\n";
	// FIN GADGET Navigation rapide

	// GADGET Recherche
	// attribut non conforme ==> le generer dynamiquement
	$js = 'this.setAttribute(\'autocomplete\', \'off\')';
	$bandeau .= "\n<div id='bandeaurecherche' class='bandeau bandeau_couleur_sous' style='$spip_lang_left: 60px;'>"
	. "<form method='get' style='margin: 0px; position: relative;' action='"
	 . generer_url_ecrire("recherche")
	. "'><div>"
	. "<input type='hidden' name='exec' value='recherche' />"
	. "<input type=\"text\" id=\"form_recherche\" style=\"width: 140px;\" size=\"10\" value=\""
	. _T('info_rechercher')
	. "\" name=\"recherche\" onkeypress=\"$js;t=window.setTimeout('lancer_recherche(\'form_recherche\',\'resultats_recherche\')', 200);\" class=\"formo\" accesskey=\"r\" />"
	. "</div></form>"
	. "</div>";
	// FIN GADGET recherche

	// GADGET Agenda
	$bandeau .= "<div id='bandeauagenda' class='bandeau bandeau_couleur_sous' style='$spip_lang_left: 100px;'>"
	. "<a href='" . generer_url_ecrire("calendrier","type=semaine") . "' class='lien_sous'>"
	. _T('icone_agenda')
	. "</a>"
	
	. "\n<div id='gadget-agenda'></div>\n"
	. "</div>\n";
	// FIN GADGET Agenda

	// GADGET Messagerie
	$gadget = '';
	$gadget .= "<div id='bandeaumessagerie' class='bandeau bandeau_couleur_sous' style='$spip_lang_left: 130px;'>";
	$gadget .= "<a href='" . generer_url_ecrire("messagerie") . "' class='lien_sous'>";
	$gadget .= _T('icone_messagerie_personnelle');
	$gadget .= "</a>";
	$gadget .= "\n<div id='gadget-messagerie'></div>\n";
	$gadget .= "</div>";

	$bandeau .= $gadget;

	// FIN GADGET Messagerie

	// Suivi activite
	$bandeau .= "<div id='bandeausynchro' class='bandeau bandeau_couleur_sous' style='$spip_lang_left: 160px;'>"
	. "<a href='" . generer_url_ecrire("synchro") . "' class='lien_sous'>"
	. _T('icone_suivi_activite')
	. "</a>"
//	. "\n<div id='gadget-suivi'><div>&nbsp;</div>"
//	. icone_horizontale(_T('analyse_xml'), parametre_url(self(),'transformer_xml', 'valider_xml'), 'racine-24.gif', '', false)
//	. "</div>".
	. "</div>\n";
	
		// Infos perso
	$bandeau .= "\n<div id='bandeauinfoperso' class='bandeau bandeau_couleur_sous' style='$spip_lang_left: 200px;'>"
	. "<a href='" . generer_url_ecrire("auteurs_edit","id_auteur=$connect_id_auteur") . "' class='lien_sous'>"
	. _T('icone_informations_personnelles')
	. "</a>"
	. "</div>";

		
		//
		// -------- Affichage de droite ----------
	
		// Deconnection
	$bandeau .= "\n<div class='bandeau bandeau_couleur_sous' id='bandeaudeconnecter' style='$spip_lang_right: 0px;'>";
	$bandeau .= "<a href='" . generer_url_action("logout","logout=prive") . "' class='lien_sous'>"._T('icone_deconnecter')."</a>".aide("deconnect");
	$bandeau .= "</div>";
	
	$decal = 0;
	$decal = $decal + 150;

	$bandeau .= "\n<div id='bandeauinterface' class='bandeau bandeau_couleur_sous' style='$spip_lang_right: ".$decal."px; text-align: $spip_lang_right;'>";
	$bandeau .= _T('titre_changer_couleur_interface');
	$bandeau .= "</div>";
		
	$decal = $decal + 70;
		
	$bandeau .= "\n<div id='bandeauecran' class='bandeau bandeau_couleur_sous' style='$spip_lang_right: ".$decal."px; text-align: $spip_lang_right;'>";
	if ($spip_ecran == "large") 
			$bandeau .= "<div><a href='".parametre_url(self(),'set_ecran', 'etroit')."' class='lien_sous'>"._T('info_petit_ecran')."</a>/<b>"._T('info_grand_ecran')."</b></div>";
	else
			$bandeau .= "<div><b>"._T('info_petit_ecran')."</b>/<a href='".parametre_url(self(),'set_ecran', 'large')."' class='lien_sous'>"._T('info_grand_ecran')."</a></div>";
	$bandeau .= "</div>";
		
	$decal = $decal + 110;
		
	// En interface simplifiee, afficher en permanence l'indication de l'interface
	if ($options != "avancees") {
		$bandeau .= "\n<div id='displayfond' class='bandeau bandeau_couleur_sous' style='$spip_lang_right: ".$decal."px; text-align: $spip_lang_right; visibility: visible; background-color: white; color: $couleur_foncee; z-index: -1000; border: 1px solid $couleur_claire; border-top: 0px;'>"
		. "<b>" . _T('icone_interface_simple')."</b>"
		. "</div>\n";
	}
	$bandeau .= "\n<div id='bandeaudisplay' class='bandeau bandeau_couleur_sous' style='$spip_lang_right: ".$decal."px; text-align: $spip_lang_right;'>";

	if ($options != 'avancees') {
		$bandeau .= "<b>"._T('icone_interface_simple')."</b>/<a href='".parametre_url(self(),'set_options', 'avancees')."' class='lien_sous'>"._T('icone_interface_complet')."</a>";
	} else {
		$bandeau .= "<a href='".parametre_url(self(),'set_options', 'basiques')."' class='lien_sous'>"._T('icone_interface_simple')."</a>/<b>"._T('icone_interface_complet')."</b>";
	}

	if ($options != "avancees") {
		$bandeau .= "<div>&nbsp;</div><div style='width: 250px; text-align: $spip_lang_left;'>"._T('texte_actualite_site_1')."<a href='./?set_options=avancees'>"._T('texte_actualite_site_2')."</a>"._T('texte_actualite_site_3')."</div>\n";
	}

	$bandeau .= "</div>";
	$bandeau .= "</div>";
	$bandeau .= "</td></tr></table>\n";


	$bandeau .= '</div>';
	
	return $bandeau;
}

// http://doc.spip.org/@gadget_agenda
function gadget_agenda() {
	global $connect_id_auteur;

	list($evtm, $evtt, $evtr) = http_calendrier_messages(date("Y"), date("m"), date("d"));

	return "<table><tr>"
	. "<td style='width: 200px; vertical-align: top;' >"
	. "<div>"
	. $evtm
	. "</div>"
	. "</td>"
	.  (!$evtt ? '' :
	      ( "<td style='width: 10px; vertical-align: top'> &nbsp; </td>"
		. "<td style='width: 200px; color: black; vertical-align: top'>"
		. "<div>&nbsp;</div>$evtt</td>"))
	. "</tr></table>";
}

// http://doc.spip.org/@gadget_messagerie
function gadget_messagerie() {
	global $connect_statut;

	return "<div>&nbsp;</div>"
	. icone_horizontale(_T('lien_nouvea_pense_bete'),generer_action_auteur("editer_message","pb"), "pense-bete.gif",'',false)
	.  icone_horizontale(_T('lien_nouveau_message'),generer_action_auteur("editer_message","normal"), "message.gif",'',false)
	  . (($connect_statut != "0minirezo") ? '' :
	     icone_horizontale(_T('lien_nouvelle_annonce'),generer_action_auteur("editer_message","affich"), "annonce.gif",'',false));
}

// http://doc.spip.org/@repercuter_gadgets
function repercuter_gadgets($id_rubrique) {

	if (!_SPIP_AJAX) return '';

	// ne sert ici qu'a caracteriser l'asynchronisme de ces scripts,
	// afin de les neutraliser lors d'une restauration
	$ajax = "\\x26var_ajaxcharset=utf8" ;

	$rub = $ajax . ($id_rubrique ? "\\x26id_rubrique=$id_rubrique" : '');

	// comme on cache fortement ce menu, son url change en fonction de sa date de modif
	$date = $GLOBALS['meta']['date_calcul_rubriques'];

	return

	// Seul le gadget des rubriques (potentiellement lourd) est charge en ajax
	 "
	jQuery('#boutonbandeautoutsite')
	.parent()
	.one('mouseover',function(){
		changestyle('bandeautoutsite');
		jQuery('#gadget-rubriques')
		.load('./?exec=gadgets\\x26gadget=rubriques$ajax\\x26date=$date');
	})
	.one('focus', function(){jQuery(this).mouseover();});"

	// les autres sont remplis ici
	."
	jQuery('#gadget-navigation')
	.html('".addslashes(strtr(gadget_navigation($id_rubrique),"\n\r","  "))."');
	"
	."
	jQuery('#gadget-agenda')
	.html('".addslashes(strtr(gadget_agenda($id_rubrique),"\n\r","  "))."');
	"
	."
	jQuery('#gadget-messagerie')
	.html('".addslashes(strtr(gadget_messagerie($id_rubrique),"\n\r","  "))."');
	"

	// la case de recherche s'efface la premiere fois qu'on la clique
	."
	jQuery('#form_recherche')
	.one('click',function(){this.value='';});
	";

}

?>
