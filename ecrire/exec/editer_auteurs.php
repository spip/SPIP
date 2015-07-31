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

function exec_editer_auteurs_dist()
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

	return formulaire_editer_auteurs(_request('cherche_auteur'), _request('ids'), $id_article, 'ajax'); 
}

function formulaire_editer_auteurs($cherche_auteur, $ids, $id_article, $flag_editable)
{
  global $spip_lang_left, $options;
  spip_log("formulaire_editer_auteurs($cherche_auteur, $ids, $id_article, $flag_editable");

//
// complement de action/editer_auteurs.php pour notifier la recherche d'auteur
//

 $bouton_creer_auteur =  $GLOBALS['connect_toutes_rubriques'];

 if ($cherche_auteur) {

	$res ="<p align='$spip_lang_left'>"
	. debut_boite_info(true)
	. rechercher_auteurs_articles($cherche_auteur, $ids,  $id_article);

	if ($bouton_creer_auteur) {

		$res .="<div style='width: 200px;'>"
		. icone_horizontale(_T('icone_creer_auteur'), generer_url_ecrire("auteur_infos","ajouter_id_article=$id_article&nom=" . rawurlencode($cherche_auteur). "&redirect=" . generer_url_retour("articles","id_article=$id_article")), "redacteurs-24.gif", "creer.gif", false)
		. "</div> ";

		$bouton_creer_auteur = false;
	}

	$res .= fin_boite_info(true)
	. '</p>';
 } else $res ='';

//
// Afficher les auteurs
//
	$les_auteurs = array();

	$result = spip_query("SELECT id_auteur FROM spip_auteurs_articles WHERE id_article=$id_article");

	while ($row = spip_fetch_array($result))
		$les_auteurs[]= $row['id_auteur'];

	if ($les_auteurs = join(',', $les_auteurs)) 
		$res .= afficher_auteurs_articles($id_article, $flag_editable, $les_auteurs);

//
// Ajouter un auteur
//

 if ($flag_editable AND $options == 'avancees') {
	$res .= debut_block_invisible("auteursarticle")
	. "<table width='100%'><tr>";

	if ($bouton_creer_auteur) {

		$res .= "<td width='200'>"
		. icone_horizontale(_T('icone_creer_auteur'), generer_url_ecrire("auteur_infos","ajouter_id_article=$id_article&redirect=" .generer_url_retour("articles","id_article=$id_article")), "redacteurs-24.gif", "creer.gif", false)
		. "</td><td width='20'>&nbsp;</td>";
	}

	$res .="<td>"
	. ajouter_auteurs_articles($id_article, $les_auteurs, $bouton_creer_auteur)
	. "</td></tr></table>"
	. fin_block();
 }
 
 if ($flag_editable === 'ajax') return $res;

 $bouton = (($flag_editable AND $options == 'avancees')
	    ? bouton_block_invisible("auteursarticle")
	    : '')
 . _T('texte_auteurs')
 . aide("artauteurs");

 return  debut_cadre_enfonce("auteur-24.gif", true, "", $bouton)
 . "\n<div id='editer_auteurs-$id_article'>$res</div>"
 . fin_cadre_enfonce(true);
}

// http://doc.spip.org/@rechercher_auteurs_articles
function rechercher_auteurs_articles($cherche_auteur, $ids, $id_article)
{
	if (!$ids) {
		return "<B>"._T('texte_aucun_resultat_auteur', array('cherche_auteur' => $cherche_auteur)).".</B><BR />";
	}
	elseif ($ids == -1) {
		return "<B>"._T('texte_trop_resultats_auteurs', array('cherche_auteur' => $cherche_auteur))."</B><BR />";
	}
	elseif (preg_match('/^\d+$/',$ids)) {

		$row = spip_fetch_array(spip_query("SELECT nom FROM spip_auteurs WHERE id_auteur=$ids"));
		return "<B>"._T('texte_ajout_auteur')."</B><BR /><UL><LI><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=2><B><FONT SIZE=3>".typo($row['nom'])."</FONT></B></UL>";
	}
	else {
		$ids = preg_replace('/[^0-9,]/','',$ids); // securite
		$result = spip_query("SELECT * FROM spip_auteurs WHERE id_auteur IN ($ids) ORDER BY nom");

		$res = "<B>"
		. _T('texte_plusieurs_articles', array('cherche_auteur' => $cherche_auteur))
		. "</B><BR />"
		.  "<UL class='verdana1'>";
		while ($row = spip_fetch_array($result)) {
				$id_auteur = $row['id_auteur'];
				$nom_auteur = $row['nom'];
				$email_auteur = $row['email'];
				$bio_auteur = $row['bio'];

				$res .= "<li><b>".typo($nom_auteur)."</b>";

				if ($email_auteur) $res .= " ($email_auteur)";

				$res .= " | "
				  .  ajax_action_auteur('editer_auteurs', "$id_article,$id_auteur","articles","id_article=$id_article", array(_T('lien_ajouter_auteur')));

				if (trim($bio_auteur)) {
					$res .= "<br />".couper(propre($bio_auteur), 100)."\n";
				}
				$res .= "</li>\n";
			}
		$res .= "</UL>";
		return $res;
	}
}

// http://doc.spip.org/@afficher_auteurs_articles
function afficher_auteurs_articles($id_article, $flag_editable, $les_auteurs)
{
	global $connect_statut, $options,$connect_id_auteur;

	$table = array();

	$result = spip_query("SELECT * FROM spip_auteurs AS A WHERE A.id_auteur IN ($les_auteurs) ORDER BY A.nom");

	while ($row = spip_fetch_array($result)) {
			$vals = array();
			$id_auteur = $row["id_auteur"];
			$nom_auteur = $row["nom"];
			$email_auteur = $row["email"];
			if ($bio_auteur = attribut_html(propre(couper($row["bio"], 100))))
			  $bio_auteur = " TITLE=\"$bio_auteur\"";
			$url_site_auteur = $row["url_site"];
			$statut_auteur = $row["statut"];
			if ($row['messagerie'] == 'non' OR $row['login'] == '') $messagerie = 'non';

			$vals[] = bonhomme_statut($row);

			$vals[] = "<a href='" . generer_url_ecrire('auteurs_edit', "id_auteur=$id_auteur") . "' $bio_auteur>".typo($nom_auteur)."</a>";

			$vals[] = bouton_imessage($id_auteur);
		
			if ($email_auteur) $vals[] =  "<A href='mailto:$email_auteur'>"._T('email')."</A>";
			else $vals[] =  "&nbsp;";

			if ($url_site_auteur) $vals[] =  "<A href='$url_site_auteur'>"._T('info_site_min')."</A>";
			else $vals[] =  "&nbsp;";

			$cpt = spip_fetch_array(spip_query("SELECT COUNT(articles.id_article) AS n FROM spip_auteurs_articles AS lien, spip_articles AS articles WHERE lien.id_auteur=$id_auteur AND articles.id_article=lien.id_article AND articles.statut IN " . ($connect_statut == "0minirezo" ? "('prepa', 'prop', 'publie', 'refuse')" : "('prop', 'publie')") . " GROUP BY lien.id_auteur"));

			$nombre_articles = intval($cpt['n']);

			if ($nombre_articles > 1) $vals[] =  $nombre_articles.' '._T('info_article_2');
			elseif ($nombre_articles == 1) $vals[] =  _T('info_1_article');
			else $vals[] =  "&nbsp;";

			if ($flag_editable AND ($connect_id_auteur != $id_auteur OR $connect_statut == '0minirezo') AND $options == 'avancees') {
				$vals[] =  ajax_action_auteur('editer_auteurs', "$id_article,-$id_auteur",'articles', "id_article=$id_article", array(_T('lien_retirer_auteur')."&nbsp;". http_img_pack('croix-rouge.gif', "X", "width='7' height='7' border='0' align='middle'")));
			} else {
			  $vals[] = "";
			}
		
			$table[] = $vals;
	}
	
	$largeurs = array('14', '', '', '', '', '', '');
	$styles = array('arial11', 'arial2', 'arial11', 'arial11', 'arial11', 'arial11', 'arial1');

	return "<div class='liste'><table width='100%' cellpadding='3' cellspacing='0' border='0' background=''>"
	. afficher_liste($largeurs, $table, $styles)
	. "</table></div>\n";
}


// http://doc.spip.org/@ajouter_auteurs_articles
function ajouter_auteurs_articles($id_article, $les_auteurs)
{
	$result = spip_query("SELECT * FROM spip_auteurs WHERE " . (!$les_auteurs ? '' : "id_auteur NOT IN ($les_auteurs) AND ") . "statut!='5poubelle' AND statut!='6forum' AND statut!='nouveau' ORDER BY statut, nom");

	if (!$num = spip_num_rows($result)) return '';

	$js = "\"findObj_forcer('valider_ajouter_auteur').style.visibility='visible';\"";

	return ajax_action_auteur('editer_auteurs', $id_article,'articles', "id_article=$id_article",
				      (
			"<span class='verdana1'><B>"._T('titre_cadre_ajouter_auteur')."&nbsp; </B></span>\n" .

			($num > 200 
			? ("<input type='text' name='cherche_auteur' onClick=$js CLASS='fondl' VALUE='' SIZE='20' />" .
			  "<span  class='visible_au_chargement' id='valider_ajouter_auteur'>\n<input type='submit' value='"._T('bouton_chercher')."' CLASS='fondo' /></span>")
			: ("<select name='nouv_auteur' size='1' style='width:150px;' CLASS='fondl' onChange=$js>" .
			   articles_auteur_select($result) .
			   "</select>" .
			   "<span  class='visible_au_chargement' id='valider_ajouter_auteur'>" .
			   " <input type='submit' value='"._T('bouton_ajouter')."' CLASS='fondo'>" .
			   "</span>"))));
}

// http://doc.spip.org/@articles_auteur_select
function articles_auteur_select($result)
{
	global $couleur_claire ;

	$statut_old = $premiere_old = $res = '';

	while ($row = spip_fetch_array($result)) {
		$id_auteur = $row["id_auteur"];
		$nom = $row["nom"];
		$email = $row["email"];
		$statut = $row["statut"];

		$statut=str_replace("0minirezo", _T('info_administrateurs'), $statut);
		$statut=str_replace("1comite", _T('info_redacteurs'), $statut);
		$statut=str_replace("6visiteur", _T('info_visiteurs'), $statut);
				
		$premiere = strtoupper(substr(trim($nom), 0, 1));

		if ($connect_statut != '0minirezo')
			if ($p = strpos($email, '@'))
				  $email = substr($email, 0, $p).'@...';
		if ($email)
			$email = " ($email)";

		if ($statut != $statut_old) {
			$res .= "\n<OPTION VALUE=\"x\">";
			$res .= "\n<OPTION VALUE=\"x\" style='background-color: $couleur_claire;'> $statut";
		}

		if ($premiere != $premiere_old AND ($statut != _T('info_administrateurs') OR !$premiere_old))
			$res .= "\n<OPTION VALUE=\"x\">";
				
		$res .= "\n<OPTION VALUE=\"$id_auteur\">&nbsp;&nbsp;&nbsp;&nbsp;" . supprimer_tags(couper(typo("$nom$email"), 40));
		$statut_old = $statut;
		$premiere_old = $premiere;
	}
	return $res;
}

?>
