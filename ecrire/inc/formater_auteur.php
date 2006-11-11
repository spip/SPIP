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
// Construit un tableau des 5 informations principales sur un auteur,
// avec des liens vers les scripts associes:
// 1. l'icone du statut, avec lien vers la page de tous ceux ayant ce statut
// 2. l'icone du mail avec un lien mailto ou a defaut la messagerie de Spip
// 3. le nom, avec lien vers la page complete des informations
// 4. le mot "site" avec le lien vers le site Web personnelle
// 5. le nombre d'articles publies
//

// http://doc.spip.org/@inc_formater_auteur_dist
function inc_formater_auteur_dist($id_auteur) {

  global $connect_id_auteur, $spip_lang_rtl, $connect_statut;

	$id_auteur = intval($id_auteur);

	$row = spip_fetch_array(spip_query("SELECT *, (en_ligne<DATE_SUB(NOW(),INTERVAL 15 DAY)) AS parti FROM spip_auteurs where id_auteur=$id_auteur"));

	$vals = array();

	$href = generer_url_ecrire("auteurs","statut=" . $row['statut']);
	$vals[] = "<a href='$href'>" . bonhomme_statut($row) . '</a>';

	if (($id_auteur == $connect_id_auteur) OR $row['parti'])
		$vals[]= '&nbsp;';
	else	$vals[]= formater_auteur_mail($row['email']);

	if ($bio_auteur = attribut_html(propre(couper($row["bio"], 100))))
		$bio_auteur = " title=\"$bio_auteur\"";

	if (!$nom = typo($row['nom']))
		$nom = "<span style='color: red'>" . _T('texte_vide') . '</span>';

	$vals[] = "<a href='"
	. generer_url_ecrire('auteur_infos', "id_auteur=$id_auteur&initial=-1")
	. "' $bio_auteur>$nom</a>";

	if ($url_site_auteur = $row["url_site"]) $vals[] =  "<a href='$url_site_auteur'>"._T('info_site_min')."</a>";
	else $vals[] =  "&nbsp;";

	$cpt = spip_fetch_array(spip_query("SELECT COUNT(articles.id_article) AS n FROM spip_auteurs_articles AS lien, spip_articles AS articles WHERE lien.id_auteur=$id_auteur AND articles.id_article=lien.id_article AND articles.statut IN " . ($connect_statut == "0minirezo" ? "('prepa', 'prop', 'publie', 'refuse')" : "('prop', 'publie')") . " GROUP BY lien.id_auteur"));

	$nombre_articles = intval($cpt['n']);

	if ($nombre_articles > 1) $vals[] =  $nombre_articles.' '._T('info_article_2');
	elseif ($nombre_articles == 1) $vals[] =  _T('info_1_article');
	else $vals[] =  "&nbsp;";

	return $vals;
}

// http://doc.spip.org/@formater_auteur_mail
function formater_auteur_mail($email)
{
	global $spip_lang_rtl;

	if ($email) $href='mailto:' . $email;
	else $href = generer_url_ecrire("message_edit","new=oui&dest=$id_auteur&type=normal");

	return "<a href='$href' title=\""
		  . _T('email')
		  . '">'
		. http_img_pack("m_envoi$spip_lang_rtl.gif", "m&gt;", " width='14' height='7'", _T('info_envoyer_message_prive'))
		  . '</a>';
}
?>
