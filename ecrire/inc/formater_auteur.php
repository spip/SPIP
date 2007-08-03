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

//
// Construit un tableau des 5 informations principales sur un auteur,
// avec des liens vers les scripts associes:
// 1. l'icone du statut, avec lien vers la page de tous ceux ayant ce statut
// 2. l'icone du mail avec un lien mailto ou a defaut la messagerie de Spip
// 3. le nom, avec lien vers la page complete des informations
// 4. le mot "site" avec le lien vers le site Web personnelle
// 5. le nombre d'objets publies
//

// Un auteur sans autorisation de modification de soi  est un visiteur;
// il n'a pas de messagerie interne, et n'a publie que des messages de forum

// http://doc.spip.org/@inc_formater_auteur_dist
function inc_formater_auteur_dist($id_auteur, $row=NULL) {

  global $connect_id_auteur, $connect_statut;

	$id_auteur = intval($id_auteur);

	if ($row===NULL)
		$row = spip_fetch_array(spip_query("SELECT *, (en_ligne<DATE_SUB(NOW(),INTERVAL 15 DAY)) AS parti FROM spip_auteurs where id_auteur=$id_auteur"));

	$vals = array();

	$href = generer_url_ecrire("auteurs","statut=" . $row['statut']);
	$vals[] = "<a href='$href'>" . bonhomme_statut($row) . '</a>';

	if (($id_auteur == $connect_id_auteur) OR $row['parti'])
		$vals[]= '&nbsp;';
	else	$vals[]= formater_auteur_mail($row, $id_auteur);

	if ($bio_auteur = attribut_html(propre(couper($row["bio"], 100))))
		$bio_auteur = " title=\"$bio_auteur\"";

	if (!$nom = typo($row['nom']))
		$nom = "<span style='color: red'>" . _T('texte_vide') . '</span>';

	$vals[] = "<a href='"
	. generer_url_ecrire('auteur_infos', "id_auteur=$id_auteur")
	. "' $bio_auteur>$nom</a>";

	if ($url_site_auteur = $row["url_site"]) $vals[] =  "<a href='$url_site_auteur'>"._T('info_site_min')."</a>";
	else $vals[] =  "&nbsp;";

	if (autoriser('modifier', 'auteur', $id_auteur, $row)) {
	  $cpt = spip_abstract_countsel("spip_auteurs_articles AS lien, spip_articles AS articles", "lien.id_auteur=$id_auteur AND articles.id_article=lien.id_article AND articles.statut IN " . ($connect_statut == "0minirezo" ? "('prepa', 'prop', 'publie', 'refuse')" : "('prop', 'publie')"), "lien.id_auteur");
	  $t = _T('info_article_2');
	  $t1 = _T('info_1_article'); 
	} else {
	  $cpt = spip_abstract_countsel("spip_forum AS F", "F.id_auteur=$id_auteur");
	  $t = _T('public:messages_forum');
	  $t1 = '1 ' . _T('public:message');
	}

	if ($cpt > 1) $vals[] =  $cpt.' '.$t;
	// manque "1 message de forum"
	elseif ($cpt == 1) $vals[] =  $t1;
	else $vals[] =  "&nbsp;";

	return $vals;
}

// http://doc.spip.org/@formater_auteur_mail
function formater_auteur_mail($row, $id_auteur)
{
	global $spip_lang_rtl;

	if (!autoriser('modifier', 'auteur', $id_auteur, $row))
		$href= 'mailto:' . $row['email'];
	else $href = generer_action_auteur("editer_message","normal/$id_auteur");
	return "<a href='$href' title=\""
	  .  _T('info_envoyer_message_prive')
	  . "\" class='message'>&nbsp;</a>";
}
?>
