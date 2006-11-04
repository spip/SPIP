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

//
// Presentation de l'interface privee, debut du HTML
//

// http://doc.spip.org/@inc_commencer_page_dist
function inc_commencer_page_dist($titre = "", $rubrique = "accueil", $sous_rubrique = "accueil", $onLoad = "", $id_rubrique = "") {

	include_spip('inc/headers');

	http_no_cache();

	return init_entete($titre, $id_rubrique)
	. init_body($rubrique, $sous_rubrique, $onLoad, $id_rubrique)
	. "<center onmouseover='recherche_desesperement()'>" // ????
	. avertissement_messagerie()
	  . ((($rubrique == "messagerie") OR (_request('changer_config')!="oui"))
	     ? auteurs_recemment_connectes() : '');
}

// envoi du doctype et du <head><title>...</head> 
// http://doc.spip.org/@init_entete
function init_entete($titre='', $id_rubrique=0) {
	include_spip('inc/gadgets');

	if (!$nom_site_spip = textebrut(typo($GLOBALS['meta']["nom_site"])))
		$nom_site_spip=  _T('info_mon_site_spip');

	$head = "<title>["
		. $nom_site_spip
		. "] " . textebrut(typo($titre)) . "</title>\n"
		. "<meta http-equiv='Content-Type' content='text/html"
		. (($c = $GLOBALS['meta']['charset']) ?
			"; charset=$c" : '')
		. "' />\n"
		. envoi_link($nom_site_spip);

	// anciennement verifForm
	$head .= '
	<script type="text/javascript"><!--
	$(document).ready(function(){
		verifForm();
	'
	.
	repercuter_gadgets($id_rubrique)
	.'
	});
	// --></script>
	';

	return _DOCTYPE_ECRIRE
	. html_lang_attributes()
	. "<head>\n"
	. pipeline('header_prive', $head)
	. "</head>\n";
}

// fonction envoyant la double serie d'icones de redac
// http://doc.spip.org/@init_body
function init_body($rubrique='accueil', $sous_rubrique='accueil', $load='', $id_rubrique='') {
	global $couleur_foncee, $couleur_claire;
	global $connect_id_auteur, $connect_toutes_rubriques;
	global $auth_can_disconnect;
	global $options, $spip_display, $spip_ecran;
	global $spip_lang, $spip_lang_rtl, $spip_lang_left, $spip_lang_right;

	if ($spip_ecran == "large") $largeur = 974; else $largeur = 750;

	if ($load) $load = " onload=\"$load\"";

	$res = pipeline('body_prive',"<body ". _ATTRIBUTES_BODY	.$load . '>')
	.  "\n<map id='map_layout'>"
	. lien_change_var (self(), 'set_disp', 1, '1,0,18,15', _T('lien_afficher_texte_seul'), "onmouseover=\"changestyle('bandeauvide','visibility', 'visible');\"")
	. lien_change_var (self(), 'set_disp', 2, '19,0,40,15', _T('lien_afficher_texte_icones'), "onmouseover=\"changestyle('bandeauvide','visibility', 'visible');\"")
	. lien_change_var (self(), 'set_disp', 3, '41,0,59,15', _T('lien_afficher_icones_seuls'), "onmouseover=\"changestyle('bandeauvide','visibility', 'visible');\"")
	. "\n</map>";

	if ($spip_display == "4") {
		$res .= "<ul>"
		. "<li><a href='./'>"._T('icone_a_suivre')."</a></li>"
		. "<li><a href='" . generer_url_ecrire("naviguer") . "'>"._T('icone_edition_site')."</a></li>"
		. "<li><a href='" . generer_url_ecrire("forum"). "'>"._T('titre_forum')."</a></li>"
		. "<li><a href='" . generer_url_ecrire("auteurs") . "'>"._T('icone_auteurs')."</a></li>"
		. "<li><a href=\"".url_de_base()."\">"._T('icone_visiter_site')."</a></li>"
		. "</ul>";

		return $res;
	}

	$res .= bandeau_double_rangee($rubrique, $sous_rubrique, $largeur);

	if (true /*$bandeau_colore*/) {
		if ($rubrique == "administration") {
			$style = "background: url(" . _DIR_IMG_PACK . "rayures-danger.png); background-color: $couleur_foncee";
			$res .= "<style>a.icone26 { color: white; }</style>";
		} else  $style = "background-color: $couleur_claire";

		$res .= "\n<div style=\"max-height: 40px; width: 100%; border-bottom: solid 1px white;$style\">"
	. "<table align='center' cellpadding='0' style='background: none;' width='$largeur'><tr>"
		. "<td valign='middle' class='bandeau_couleur' style='text-align: $spip_lang_left;'>"
		. "<a href='" . generer_url_ecrire("articles_tous") . "' class='icone26' onmouseover=\"changestyle('bandeautoutsite','visibility','visible');\">"
		. http_img_pack("tout-site.png", "", "width='26' height='20'") . "</a>";
		if ($id_rubrique > 0)
			$res .= "<a href='" . generer_url_ecrire("brouteur","id_rubrique=$id_rubrique") . "' class='icone26' onmouseover=\"changestyle('bandeaunavrapide','visibility','visible');\">" .
			  http_img_pack("naviguer-site.png", "", "width='26' height='20'") ."</a>";
		else $res .= "<a href='" . generer_url_ecrire("brouteur") . "' class='icone26' onmouseover=\"changestyle('bandeaunavrapide','visibility','visible');\" >" .
		  http_img_pack("naviguer-site.png", "", "width='26' height='20'") . "</a>";

		$res .= "<a href='" . generer_url_ecrire("recherche") . "' class='icone26' onmouseover=\"changestyle('bandeaurecherche','visibility','visible'); findObj('form_recherche').focus();\" >" .
		  http_img_pack("loupe.png", "", "width='26' height='20'") ."</a>";

		$res .= http_img_pack("rien.gif", " ", "width='10'");

		$res .= "<a href='" . generer_url_ecrire("calendrier","type=semaine") . "' class='icone26' onmouseover=\"changestyle('bandeauagenda','visibility','visible');\">" .
		  http_img_pack("cal-rv.png", "", "width='26' height='20'") ."</a>"
		. "<a href='" . generer_url_ecrire("messagerie") . "' class='icone26' onmouseover=\"changestyle('bandeaumessagerie','visibility','visible');\">" .
		  http_img_pack("cal-messagerie.png", "", "width='26' height='20'") ."</a>"
		. "<a href='" . generer_url_ecrire("synchro") . "' class='icone26' onmouseover=\"changestyle('bandeausynchro','visibility','visible');\">" .
		  http_img_pack("cal-suivi.png", "", "width='26' height='20'") . "</a>";
		

		if (!($connect_toutes_rubriques)) {
			$res .= http_img_pack("rien.gif", " ", "width='10'");
			$res .= "<a href='" . generer_url_ecrire("auteur_infos","id_auteur=$connect_id_auteur&initial=-1") . "' class='icone26' onmouseover=\"changestyle('bandeauinfoperso','visibility','visible');\">" .
			  http_img_pack("fiche-perso.png", "", "onmouseover=\"changestyle('bandeauvide','visibility', 'visible');\"");
			$res .= "</a>";
		}
		
	$res .= "</td>"
	. "<td valign='middle' class='bandeau_couleur' style='text-align: $spip_lang_left;'>";
		// overflow pour masquer les noms tres longs (et eviter debords, notamment en ecran etroit)
		if ($spip_ecran == "large") $largeur_nom = 300;
		else $largeur_nom= 110;
		$res .= "<div style='width: ".$largeur_nom."px; height: 14px; overflow: hidden;'>";
		// Redacteur connecte
		$res .= typo($GLOBALS['auteur_session']['nom'])
		. "</div>";
	
	$res .= "</td>"
	. "<td> &nbsp; </td>"
	. "<td class='bandeau_couleur' style='text-align: $spip_lang_right;' valign='middle'>";

			// Choix display
		//	$res .="<img src=_DIR_IMG_PACK . 'rien.gif' width='10' />";
	if ($options != "avancees") {
				$lien = parametre_url(self(), 'set_options', 'avancees');
				$icone = "interface-display-comp.png";
	} else {
				$lien = parametre_url(self(), 'set_options', 'basiques');
				$icone = "interface-display.png";
	}
	$res .= "<a href='$lien' class='icone26' onmouseover=\"changestyle('bandeaudisplay','visibility', 'visible');\">"
	. http_img_pack("$icone", "", "width='26' height='20'")."</a>"
	. http_img_pack("rien.gif", " ", "width='10' height='1'")
	. http_img_pack("choix-layout$spip_lang_rtl".($spip_lang=='he'?'_he':'').".gif", "abc", "class='format_png' style='vertical-align: middle' width='59' height='15' usemap='#map_layout'")
	. http_img_pack("rien.gif", " ", "width='10' height='1'");
			// grand ecran
	if ($spip_ecran == "large") {
				$i = _T('info_petit_ecran');
				$res .= "<a href='". parametre_url(self(),'set_ecran', 'etroit') ."' class='icone26' onmouseover=\"changestyle('bandeauecran','visibility', 'visible');\" title=\"$i\">" .
				  http_img_pack("set-ecran-etroit.png", $i, "width='26' height='20'") . "</a>";
				$ecran = "<div><a href='".parametre_url(self(),'set_ecran', 'etroit')."' class='lien_sous'>"._T('info_petit_ecran')."</a>/<b>"._T('info_grand_ecran')."</b></div>";
	} else {
				$i = _T('info_grand_ecran');
				$res .= "<a href='".parametre_url(self(),'set_ecran', 'large')."' class='icone26' onmouseover=\"changestyle('bandeauecran','visibility', 'visible');\" title=\"$i\">" .
				  http_img_pack("set-ecran.png", $i, "width='26' height='20'") ."</a>";
				$ecran = "<div><b>"._T('info_petit_ecran')."</b>/<a href='".parametre_url(self(),'set_ecran', 'large')."' class='lien_sous'>"._T('info_grand_ecran')."</a></div>";
			}

	$res .= "</td>"
	. "<td class='bandeau_couleur' style='width: 60px; text-align:$spip_lang_left;' valign='middle'>"
	. choix_couleur()
	. "</td>";
	//
	// choix de la langue
	//
	if ($GLOBALS['all_langs']) {
		$res .= "<td class='bandeau_couleur' style='width: 100px; text-align: $spip_lang_right;' valign='middle'>"
		. menu_langues('var_lang_ecrire')
		. "</td>";
	}

	$res .= "<td class='bandeau_couleur' style='text-align: $spip_lang_right; width: 28px;' valign='middle'>";

	if ($auth_can_disconnect) {
			$res .= "<a href='".
			  generer_url_action("logout","logout=prive") .
			  "' class='icone26' onmouseover=\"changestyle('bandeaudeconnecter','visibility', 'visible');\">" .
			  http_img_pack("deconnecter-24.gif", "", "") .
			  "</a>";
			}
	$res .= "</td>"
	. "</tr></table>";

} // fin bandeau colore

	// <div> pour la barre des gadgets
	// (elements invisibles qui s'ouvrent sous la barre precedente)

	$res .= bandeau_gadgets($largeur, $options, $id_rubrique)
	. "</div>"
	. "</div>";

	if ($options != "avancees") $res .= "<div style='height: 18px;'>&nbsp;</div>";
	return $res;
}


// http://doc.spip.org/@avertissement_messagerie
function avertissement_messagerie() {
	global $couleur_foncee;
	global $connect_id_auteur;

	$result_messages = spip_query("SELECT lien.id_message FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE lien.id_auteur=$connect_id_auteur AND vu='non' AND statut='publie' AND type='normal' AND lien.id_message=messages.id_message");
	$total_messages = @spip_num_rows($result_messages);
	if ($total_messages == 1) {
		$row = @spip_fetch_array($result_messages);
		$ze_message=$row['id_message'];
		return "<div class='messages'><a href='" . generer_url_ecrire("message","id_message=$ze_message") . "'><font color='$couleur_foncee'>"._T('info_nouveau_message')."</font></a></div>";
	} elseif ($total_messages > 1)
		return "<div class='messages'><a href='" . generer_url_ecrire("messagerie") . "'><font color='$couleur_foncee'>"._T('info_nouveaux_messages', array('total_messages' => $total_messages))."</font></a></div>";
	else return '';
}


// http://doc.spip.org/@auteurs_recemment_connectes
function auteurs_recemment_connectes()
{	
	global $connect_id_auteur;
	$res = '';
	$result_auteurs = spip_query("SELECT id_auteur FROM spip_auteurs WHERE id_auteur!=$connect_id_auteur AND en_ligne>DATE_SUB(NOW(),INTERVAL 15 MINUTE) AND statut IN ('0minirezo','1comite')");

	if (spip_num_rows($result_auteurs)) {
		$formater_auteur = charger_fonction('formater_auteur', 'inc');
		$res = "<b>"._T('info_en_ligne'). "&nbsp;</b>";
		while ($row = spip_fetch_array($result_auteurs)) {
			list($s, $mail, $nom, $w, $p) = $formater_auteur($row['id_auteur']);
			$res .= "$mail&nbsp;$nom, ";
		}
		$res = substr($res,0,-2);
	}

	return "<div class='messages' style='color: #666666;'>$res</div>";
}
?>
