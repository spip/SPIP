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
include_spip('inc/acces'); // pour low_sec (iCal)

// http://doc.spip.org/@afficher_liens_calendrier
function afficher_liens_calendrier($lien, $icone, $texte) {

	global $spip_display;
	$charge = icone_horizontale_display(_T('ical_methode_http'), $lien, "calendrier-24.gif","", false);
	$abonne = icone_horizontale_display (_T('ical_methode_webcal'), preg_replace("@https?://@", "webcal://", $lien), "calendrier-24.gif", "", false);

	echo debut_cadre_enfonce($icone, true);
	echo $texte;
	if ($spip_display == 4)
	  echo $charge,$abonne;
	else {
	echo "<table style='width: 100%;'><tr><td style='width: 200px;'>";
	echo $charge;
	echo "</td>";
	echo "<td> &nbsp; </td>";
	echo "<td style='width: 200px;'>";
	echo $abonne;
	echo "</td></tr></table>";
	}
	echo fin_cadre_enfonce(true);
}

// http://doc.spip.org/@exec_synchro_dist
function exec_synchro_dist()
{
	///// debut de la page
	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T("icone_suivi_activite"),  "accueil", "synchro");

	echo "<br /><br />";
	echo gros_titre(_T("icone_suivi_activite"),'', false);

	echo debut_gauche('', true);

	echo debut_boite_info(true);

	echo "<div class='verdana2'>";

	echo _T('ical_info1').'<br /><br />';

	echo _T('ical_info2', array('spipnet' => $GLOBALS['home_server']
					. '/' .  $GLOBALS['spip_lang']
					. '_suivi'));

	echo "</div>";

	echo fin_boite_info(true);

	$adresse_suivi_inscription=$GLOBALS['meta']["adresse_suivi_inscription"];

	echo debut_droite('', true);


///
/// Suivi par mailing-list
///

	if ($GLOBALS['meta']["suivi_edito"] == "oui" AND strlen($GLOBALS['meta']["adresse_suivi"]) > 3 AND strlen($adresse_suivi_inscription) > 3) {
		echo debut_cadre_enfonce("racine-site-24.gif", true, "", _T('ical_titre_mailing'));
		echo _T('info_config_suivi_explication'), 
		propre("<b style='text-align: center'>[->$adresse_suivi_inscription]</b>");
		echo fin_cadre_enfonce(true);
	}


///
/// Suivi par agenda iCal (taches + rendez-vous)
///

	echo debut_cadre_relief("agenda-24.gif", true, "", _T('icone_calendrier'));

	echo _T('calendrier_synchro');
	echo '<p>'._T('ical_info_calendrier').'</p>';

	$id_auteur = $GLOBALS['visiteur_session']['id_auteur'];

	afficher_liens_calendrier(generer_url_public('ical'),'', _T('ical_texte_public'));

	afficher_liens_calendrier(generer_url_public("ical_prive", "id_auteur=$id_auteur&arg=".afficher_low_sec($id_auteur,'ical')),'cadenas-24.gif',  _T('ical_texte_prive'));

	echo fin_cadre_relief(true);

///
/// Suivi par RSS
///

	echo debut_cadre_relief("site-24.gif", true, "", _T('ical_titre_rss'));

	echo _T('ical_texte_rss');

	echo "<p>"._T("ical_texte_rss_articles")."</p>";

	echo propre("<cadre>" . generer_url_public('backend') . "</cadre>");

	echo "<p>"._T("ical_texte_rss_articles2")."</p>";

	$bouton = http_img_pack( 'feed.png', 'RSS', '');

	$result = sql_allfetsel("id_rubrique, titre", "spip_rubriques", 'id_parent=0','', '0+titre,titre');

	$res = '';
	foreach($result as $row){
		$h = generer_url_public('backend', "id_rubrique=" . $row['id_rubrique']);
		$titre_rubrique = typo($row['titre']);
		$titre = htmlspecialchars($titre_rubrique);
			
		$res .= "\n<li><a href='$h' title=\"$titre\">$bouton&nbsp; $titre_rubrique</a></li>";
	}
	
	if ($res) echo "\n<ul>", $res, "\n</ul>";
	
	if ($GLOBALS['meta']['activer_breves'] == "oui") {
		
		echo "<p>"._T("ical_texte_rss_breves")."</p>";
		echo "<ul><li><a href='",
		  generer_url_public('backend-breves', ""),
		  "' title=\"",
		  _T('ical_lien_rss_breves'),
		  "\">",
		  $bouton,
		  '&nbsp; ' . _T('ical_lien_rss_breves'), 
		  "</a></li></ul>";
		
	}

	echo fin_cadre_relief(true);

///
/// Suivi par Javascript
///

	  echo debut_cadre_relief("doc-24.gif", true, "", _T('ical_titre_js'));

	  echo _T('ical_texte_js').'<br />';

	  echo propre('<code>
<script
    type="text/javascript"
    src="'.generer_url_public('distrib').'">
</script>
</code>');

	  echo fin_cadre_relief(true);


	  echo fin_gauche(), fin_page();
}
?>
