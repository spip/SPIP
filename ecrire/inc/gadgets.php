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

	$GLOBALS['db_art_cache'] = array();
	if (spip_num_rows($res) > 0) { 
		while ($row = spip_fetch_array($res)) {
			$parent = $row['id_parent'];
			$id = $row['id_rubrique'];
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
	global $max_lignes;

	gen_liste_rubriques(); 
	$arr_low = extraire_article(0);

	$total_lignes = $i = sizeof($arr_low);

	$nb_col = min(10,max(1,ceil($total_lignes / 10)));
	$max_lignes = ceil($total_lignes / $nb_col);

	$count_lignes = 0;

	if ($i > 0) {
		$ret = "<div>&nbsp;</div>";
		$ret .= "<div class='bandeau_rubriques' style='z-index: 1;'>";
		foreach( $arr_low as $id_rubrique => $titre_rubrique) {

			if ($count_lignes == $max_lignes) {
				$count_lignes = 0;
				$ret .= "</div></td><td valign='top' width='200'><div>&nbsp;</div><div class='bandeau_rubriques' style='z-index: 1;'>";
			}
			$count_lignes ++;

			$ret .= bandeau_rubrique($id_rubrique, $titre_rubrique, $i);
			$i = $i - 1;
		}
		$ret .= "</div>";
	}
	unset($GLOBALS['db_art_cache']); // On libere la memoire

	$ret = "<table><tr><td valign='top' width='200'>\n"
		. $ret
		. "\n</td></tr></table>\n";

	return $ret;
}


// http://doc.spip.org/@bandeau_rubrique
function bandeau_rubrique($id_rubrique, $titre_rubrique, $z = 1) {
	global $zdecal;
	global $max_lignes;
	global $spip_ecran, $spip_display;
	global $spip_lang, $spip_lang_rtl, $spip_lang_left, $spip_lang_right;

	$titre_rubrique = preg_replace(',[\x00-\x1f]+,', ' ', $titre_rubrique);
	$count_ligne = 0;
	$zdecal = $zdecal + 1;
	// Limiter volontairement le nombre de sous-menus 
	$zmax = 6;

	if ($zdecal == 1) $image = "secteur-12.gif";
	//else $image = "rubrique-12.gif";
	else $image = '';
	
	if (strlen($image) > 1)
		$image = " style='background-image:url(" . http_wrapper($image) .");'";

	$arr_rub = extraire_article($id_rubrique);

	$i = sizeof($arr_rub);
	if ($i > 0 AND $zdecal < $zmax) {
		$ret = "<div class='pos_r' style='z-index: $z;' onMouseOver=\"montrer('b_$id_rubrique');\" onMouseOut=\"cacher('b_$id_rubrique');\">";
		$ret .= '<div class="brt"><a href="' . generer_url_ecrire('naviguer', 'id_rubrique='.$id_rubrique)
		  . '" class="bandeau_rub"'.$image.'>'.supprimer_tags($titre_rubrique)."</a></div>\n"
		  . '<div class="bandeau_rub" style="z-index: '.($z+1).';" id="b_'.$id_rubrique.'">';
		
		$ret .= '<table cellspacing="0" cellpadding="0"><tr><td valign="top">';
		$ret .= "<div style='width: 200px;'>\n";
		
		if ($nb_rub = count($arr_rub))
			$ret_ligne =  ceil($nb_rub / ceil($nb_rub / $max_lignes)) + 1;
				
		foreach( $arr_rub as $id_rub => $titre_rub) {
			$count_ligne ++;
			
			if ($count_ligne == $ret_ligne) {
				$count_ligne = 0;
				$ret .= "</div>";
				$ret .= "</td>";
				$ret .= '<td valign="top" style="border-left: 1px solid #cccccc;">';
				$ret .= "<div style='width: 200px;'>";

			}
		
			$titre_rub = supprimer_numero(typo($titre_rub));
			$ret .= bandeau_rubrique($id_rub, $titre_rub, ($z+$i));
			$i = $i - 1;
		}
		
		$ret .= "</div></td></tr></table>\n";
		
		$ret .= "</div></div>\n";
	} else {
		$ret = '<div><a href="' . generer_url_ecrire('naviguer', 'id_rubrique='.$id_rubrique)
		  . '" class="bandeau_rub"'.$image.'>'.supprimer_tags($titre_rubrique)."</a></div>\n";
	}
	$zdecal = $zdecal - 1;
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
				$gadget .= "<a class='$statut' style='font-size: 10px;' href='" . generer_url_ecrire("articles","id_article=$id_article") . "'>$titre</a>\n";
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
	
				$gadget .= "<a class='$statut' style='font-size: 10px;' href='" . generer_url_ecrire("articles","id_article=$id_article") . "'>$titre</a>";
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
	
				$gadget .= "<a class='$statut' style='font-size: 10px;' href='" . generer_url_ecrire("breves_voir","id_breve=$id_breve") . "'>$titre</a>";
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
	"\n<table width='$largeur' cellpadding='0' cellspacing='0' align='center'><tr><td>\n<div style='position: relative; z-index: 1000;'>"

	// GADGET Menu rubriques
	. "\n<div id='bandeautoutsite' class='bandeau bandeau_couleur_sous' style='$spip_lang_left: 0px;'>"
	. "<a href='"
	. generer_url_ecrire("articles_tous")
	. "' class='lien_sous'" 
/* retire par la 7033 car bugge. a reintroduire ? 
onmouseover=\"findObj_forcer('bandeautoutsite').style.visibility='visible'; charger_id_url_si_vide('" . generer_url_ecrire('rubriquer',"&var_ajax=1&id=$id_rubrique") . "','nav-recherche');\" */
	. ">"
	._T('icone_site_entier')
	. "</a>"
	. "<div id='nav-recherche'></div>"
	. "<div id='gadget-rubriques'></div>"
	. "</div>";
	// FIN GADGET Menu rubriques


	// GADGET Navigation rapide
	$bandeau .= "<div id='bandeaunavrapide' class='bandeau bandeau_couleur_sous' style='$spip_lang_left: 30px;'>"
	. "<a href='" . generer_url_ecrire("brouteur", ($id_rubrique ? "id_rubrique=$id_rubrique" : '')) . "' class='lien_sous'>" . _T('icone_brouteur') . "</a>"
	. "<div id='gadget-navigation'></div>\n"
	. "</div>\n";
	// FIN GADGET Navigation rapide

	// GADGET Recherche
	// attribut non conforme ==> le generer dynamiquement
	$js = 'this.setAttribute(\'autocomplete\', \'off\')';
	$bandeau .= "<div id='bandeaurecherche' class='bandeau bandeau_couleur_sous' style='$spip_lang_left: 60px;'>"
	. "<form method='get' style='margin: 0px; position: relative;' action='"
	 . generer_url_ecrire("recherche")
	. "'>"
	. "<input type='hidden' name='exec' value='recherche' />"
	. "<input type=\"text\" id=\"form_recherche\" style=\"width: 140px;\" size=\"10\" value=\""
	. _T('info_rechercher')
	. "\" name=\"recherche\" onkeypress=\"$js;t=window.setTimeout('lancer_recherche(\'form_recherche\',\'resultats_recherche\')', 200);\" class=\"formo\" accesskey=\"r\" />"
	. "</form>"
	. "</div>";
	// FIN GADGET recherche

	// GADGET Agenda
	$bandeau .= "<div id='bandeauagenda' class='bandeau bandeau_couleur_sous' style='$spip_lang_left: 100px;'>"
	. "<a href='" . generer_url_ecrire("calendrier","type=semaine") . "' class='lien_sous'>"
	. _T('icone_agenda')
	. "</a>"
	
	. "<div id='gadget-agenda'></div>\n"
	. "</div>\n";
	// FIN GADGET Agenda

	// GADGET Messagerie
	$gadget = '';
	$gadget .= "<div id='bandeaumessagerie' class='bandeau bandeau_couleur_sous' style='$spip_lang_left: 130px;'>";
	$gadget .= "<a href='" . generer_url_ecrire("messagerie") . "' class='lien_sous'>";
	$gadget .= _T('icone_messagerie_personnelle');
	$gadget .= "</a>";
	$gadget .= "<div id='gadget-messagerie'></div>\n";
	$gadget .= "</div>";

	$bandeau .= $gadget;

	// FIN GADGET Messagerie


	// Suivi activite
	$bandeau .= "<div id='bandeausynchro' class='bandeau bandeau_couleur_sous' style='$spip_lang_left: 160px;'>";
	$bandeau .= "<a href='" . generer_url_ecrire("synchro") . "' class='lien_sous'>";
	$bandeau .= _T('icone_suivi_activite');
	$bandeau .= "</a>";
	$bandeau .= "<div id='gadget-suivi'></div>\n";
	$bandeau .= "</div>";
	
		// Infos perso
	$bandeau .= "<div id='bandeauinfoperso' class='bandeau bandeau_couleur_sous' style='$spip_lang_left: 200px;'>";
	$bandeau .= "<a href='" . generer_url_ecrire("auteurs_edit","id_auteur=$connect_id_auteur") . "' class='lien_sous'>";
	$bandeau .= _T('icone_informations_personnelles');
	$bandeau .= "</a>";
	$bandeau .= "</div>";

		
		//
		// -------- Affichage de droite ----------
	
		// Deconnection
	$bandeau .= "<div class='bandeau bandeau_couleur_sous' id='bandeaudeconnecter' style='$spip_lang_right: 0px;'>";
	$bandeau .= "<a href='" . generer_url_action("logout","logout=prive") . "' class='lien_sous'>"._T('icone_deconnecter')."</a>".aide("deconnect");
	$bandeau .= "</div>";
	
	$decal = 0;
	$decal = $decal + 150;

	$bandeau .= "<div id='bandeauinterface' class='bandeau bandeau_couleur_sous' style='$spip_lang_right: ".$decal."px; text-align: $spip_lang_right;'>";
	$bandeau .= _T('titre_changer_couleur_interface');
	$bandeau .= "</div>";
		
	$decal = $decal + 70;
		
	$bandeau .= "<div id='bandeauecran' class='bandeau bandeau_couleur_sous' style='$spip_lang_right: ".$decal."px; text-align: $spip_lang_right;'>";
	if ($spip_ecran == "large") 
			$bandeau .= "<div><a href='".parametre_url(self(),'set_ecran', 'etroit')."' class='lien_sous'>"._T('info_petit_ecran')."</a>/<b>"._T('info_grand_ecran')."</b></div>";
	else
			$bandeau .= "<div><b>"._T('info_petit_ecran')."</b>/<a href='".parametre_url(self(),'set_ecran', 'large')."' class='lien_sous'>"._T('info_grand_ecran')."</a></div>";
	$bandeau .= "</div>";
		
	$decal = $decal + 110;
		
	// En interface simplifiee, afficher en permanence l'indication de l'interface
	if ($options != "avancees") {
			$bandeau .= "<div id='displayfond' class='bandeau bandeau_couleur_sous' style='$spip_lang_right: ".$decal."px; text-align: $spip_lang_right; visibility: visible; background-color: white; color: $couleur_foncee; z-index: -1000; border: 1px solid $couleur_claire; border-top: 0px;'>";
			$bandeau .= "<b>"._T('icone_interface_simple')."</b>";
			$bandeau .= "</div>";
	}
	$bandeau .= "<div id='bandeaudisplay' class='bandeau bandeau_couleur_sous' style='$spip_lang_right: ".$decal."px; text-align: $spip_lang_right;'>";

	if ($options != 'avancees') {
		$bandeau .= "<b>"._T('icone_interface_simple')."</b>/<a href='".parametre_url(self(),'set_options', 'avancees')."' class='lien_sous'>"._T('icone_interface_complet')."</a>";
	} else {
		$bandeau .= "<a href='".parametre_url(self(),'set_options', 'basiques')."' class='lien_sous'>"._T('icone_interface_simple')."</a>/<b>"._T('icone_interface_complet')."</b>";
	}

	if ($options != "avancees") {
		$bandeau .= "<div>&nbsp;</div><div style='width: 250px; text-align: $spip_lang_left;'>"._T('texte_actualite_site_1')."<a href='./?set_options=avancees'>"._T('texte_actualite_site_2')."</a>"._T('texte_actualite_site_3')."</div>";
	}

	$bandeau .= "</div>";
	$bandeau .= "</div>";
	$bandeau .= "</td></tr></table>";


	$bandeau .= '</div>';
	
	return $bandeau;
}

// http://doc.spip.org/@gadget_agenda
function gadget_agenda() {
	global $connect_id_auteur;

	$gadget = '';
	$today = getdate(time());
	$jour_today = $today["mday"];
	$mois_today = $today["mon"];
	$annee_today = $today["year"];
	$date = date("Y-m-d", mktime(0,0,0,$mois_today, 1, $annee_today));
	$mois = mois($date);
	$annee = annee($date);
	$jour = jour($date);
	$gadget .= "<table><tr>";
	$gadget .= "<td valign='top' width='200'>";
	$gadget .= "<div>";
	$gadget .= http_calendrier_agenda($annee_today, $mois_today, $jour_today, $mois_today, $annee_today, false, generer_url_ecrire('calendrier'));
	$gadget .= "</div>";
	$gadget .= "</td>";

	$n = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM spip_messages AS messages WHERE id_auteur=$connect_id_auteur AND statut='publie' AND type='pb' AND rv!='oui' LIMIT 1"));
	if (!$n['n'])
		$n = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE ((lien.id_auteur='$connect_id_auteur' AND lien.id_message=messages.id_message) OR messages.type='affich') AND messages.rv='oui' AND messages.date_heure > DATE_SUB(NOW(), INTERVAL 1 DAY) AND messages.date_heure < DATE_ADD(NOW(), INTERVAL 1 MONTH) AND messages.statut='publie' GROUP BY messages.id_message ORDER BY messages.date_heure LIMIT 1"));
	if ($n['n']) {
		$gadget .= "<td valign='top' width='10'> &nbsp; </td>";
		$gadget .= "<td valign='top' width='200'>";
		$gadget .= "<div>&nbsp;</div>";
		$gadget .= "<div style='color: black;'>";
		$gadget .= http_calendrier_rv(sql_calendrier_taches_annonces(),"annonces");
		$gadget .=  http_calendrier_rv(sql_calendrier_taches_pb(),"pb");
		$gadget .=  http_calendrier_rv(sql_calendrier_taches_rv(), "rv");
		$gadget .= "</div>";
		$gadget .= "</td>";
	}
	$gadget .= "</tr></table>";

	return $gadget;
}

// http://doc.spip.org/@gadget_messagerie
function gadget_messagerie() {
	global $connect_statut;

	$gadget = "<div>&nbsp;</div>";
	$gadget .= icone_horizontale(_T('lien_nouvea_pense_bete'),generer_url_ecrire("message_edit","new=oui&type=pb"), "pense-bete.gif", '', false);
	$gadget .= icone_horizontale(_T('lien_nouveau_message'),generer_url_ecrire("message_edit","new=oui&type=normal"), "message.gif", '', false);
	if ($connect_statut == "0minirezo") {
		  $gadget .= icone_horizontale(_T('lien_nouvelle_annonce'),generer_url_ecrire("message_edit","new=oui&type=affich"), "annonce.gif", '', false);
		}
	return $gadget;
}

// http://doc.spip.org/@repercuter_gadgets
function repercuter_gadgets($id_rubrique) {

	if (_SPIP_AJAX === -1) return '';

	$rub = $id_rubrique ? "\\x26id_rubrique=$id_rubrique" : '';

	return
	 "
	$('#gadget-rubriques')
	.load('./?exec=gadgets\\x26gadget=rubriques');" #pas de $rub
	."
	$('#gadget-navigation')
	.load('./?exec=gadgets\\x26gadget=navigation$rub');"
	."
	$('#gadget-agenda')
	.load('./?exec=gadgets\\x26gadget=agenda$rub');"
	."
	$('#gadget-messagerie')
	.load('./?exec=gadgets\\x26gadget=messagerie$rub');";
}

?>
