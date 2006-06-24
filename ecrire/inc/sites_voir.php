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

function afficher_sites($titre_table, $requete)
{
	global $couleur_claire, $connect_id_auteur, $spip_display ;

	$tmp_var = substr(md5(join(' ',$requete)), 0, 4);
	$deb_aff = intval(_request('t_' .$tmp_var));

	return affiche_tranche_bandeau($requete, "site-24.gif", 3, $couleur_claire, "black", $tmp_var, $deb_aff, $titre_table, false,  array('','',''), array('arial11', 'arial1', 'arial1'), 'afficher_sites_boucle');
}

function afficher_sites_boucle($row, &$tous_id, $voir_logo, $bof)
{
  global $spip_lang_right;
	$vals = '';
	$id_syndic=$row["id_syndic"];
	$id_rubrique=$row["id_rubrique"];
	$nom_site=sinon(typo($row["nom_site"]), _T('info_sans_titre'));
	$url_site=$row["url_site"];
	$url_syndic=$row["url_syndic"];
	$syndication=$row["syndication"];
	$statut=$row["statut"];
	$date=$row["date"];
	$moderation=$row['moderation'];
			
	$tous_id[] = $id_syndic;

	switch ($statut) {
		case 'publie':
				if (acces_restreint_rubrique($id_rubrique))
					$puce = 'puce-verte-anim.gif';
				else
					$puce='puce-verte-breve.gif';
				$title = _T('info_site_reference');
				break;
			case 'prop':
				if (acces_restreint_rubrique($id_rubrique))
					$puce = 'puce-orange-anim.gif';
				else
					$puce='puce-orange-breve.gif';
				$title = _T('info_site_attente');
				break;
			case 'refuse':
				if (acces_restreint_rubrique($id_rubrique))
					$puce = 'puce-poubelle-anim.gif';
				else
					$puce='puce-poubelle-breve.gif';
				$title = _T('info_site_refuse');
				break;
	}
	if ($syndication == 'off' OR $syndication == 'sus') {
				$puce = 'puce-orange-anim.gif';
				$title = _T('info_panne_site_syndique');
			}

	$s = "<a href=\"".generer_url_ecrire("sites","id_syndic=$id_syndic")."\" title=\"$title\">";

	if ($voir_logo) {

		include_spip('inc/logos');
		$logo = decrire_logo("id_syndic", 'on', $id_syndic, 26, 20);
		if ($logo)
			$s .= "<div style='float: $spip_lang_right; margin-top: -2px; margin-bottom: -2px;'>$logo</div>";
	}

	$s .= http_img_pack($puce, $statut, "width='7' height='7'") ."&nbsp;&nbsp;";
			
	$s .= typo($nom_site);
	
	$s .= "</a> &nbsp;&nbsp; <font size='1'>[<a href='$url_site'>"._T('lien_visite_site')."</a>]</font>";
	$vals[] = $s;
			
	$s = "";

	if ($syndication == 'off' OR $syndication == 'sus') {
				$s .= "<font color='red'>"._T('info_probleme_grave')." </font>";
	}
	if ($syndication == "oui" or $syndication == "off" OR $syndication == 'sus'){
			$s .= "<font color='red'>"._T('info_syndication')."</font>";
	}
	$vals[] = $s;

	if ($syndication == "oui" OR $syndication == "off" OR $syndication == "sus") {
		$total_art = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM spip_syndic_articles WHERE id_syndic='$id_syndic'"));
		$s = " " . $total_art['n'] . " " . _T('info_syndication_articles');
	} else {
			$s = "&nbsp;";
	}
	$vals[] = $s;

	return $vals;
}

function afficher_syndic_articles($titre_table, $requete, $id = 0) {
	global $connect_statut, $spip_lang_right;

	$col = (($connect_statut == '0minirezo') ? 3 :  2) + ($id==0);
	$tmp_var = substr(md5(join(' ',$requete)), 0, 4);
	$deb_aff = intval(_request('t_' .$tmp_var));
	$redirect = generer_url_ecrire($GLOBALS['exec'], ('t_' .$tmp_var . '=' . $deb_aff) . (!$id ? '' : "&id_syndic=$id"), true);
	if (!$requete['FROM']) $requete['FROM']= 'spip_syndic_articles';

	if (!$id) {
			$largeurs = array(7, '', '100');
			$styles = array('','arial11', 'arial1');
	} else {
			$largeurs = array(7, '');
			$styles = array('','arial11');
	}
	if ($connect_statut == '0minirezo') {
			$largeurs[] = '80';
			$styles[] = 'arial1';
	}

	return affiche_tranche_bandeau($requete, "site-24.gif", $col, "#999999", "white", $tmp_var, $deb_aff, $titre_table, $obligatoire, $largeurs, $styles, 'afficher_syndic_articles_boucle', $redirect);
}

function afficher_syndic_articles_boucle($row, &$my_sites, $bof, $redirect)
{
	global  $connect_statut, $spip_lang_right;

	$vals = '';

	$id_syndic_article=$row["id_syndic_article"];
	$id_syndic=$row["id_syndic"];
	$titre=safehtml($row["titre"]);
	$url=$row["url"];
	$date=$row["date"];
	$lesauteurs=typo($row["lesauteurs"]);
	$statut=$row["statut"];
	$descriptif=safehtml($row["descriptif"]);

	if ($statut=='publie') {
			$puce='puce-verte.gif';
	}
	else if ($statut == "refuse") {
			$puce = 'puce-poubelle.gif';
	}

	else if ($statut == "dispo") { // moderation : a valider
			$puce = 'puce-rouge.gif';
	}

	else if ($statut == "off") { // feed d'un site en mode "miroir"
			$puce = 'puce-rouge-anim.gif';
	}

	$vals[] = http_img_pack($puce, $statut, "width='7' height='7'");

	$s = "<a href='$url'>$titre</a>";

	$date = affdate_court($date);
	if (strlen($lesauteurs) > 0) $date = $lesauteurs.', '.$date;
	$s.= " ($date)";

	// Tags : d'un cote les enclosures, de l'autre les liens
	if($e = afficher_enclosures($row['tags']))
		$s .= ' '.$e;

	// descriptif
	if (strlen($descriptif) > 0)
		$s .= "<div class='arial1'>".safehtml($descriptif)."</div>";

	// tags
	if ($tags = afficher_tags($row['tags']))
		$s .= "<div style='float:$spip_lang_right;'>&nbsp;<em>"
			. $tags . '</em></div>';

	// source
	if (strlen($row['url_source']))
		$s .= "<div style='float:$spip_lang_right;'>"
		. propre("[".$row['source']."->".$row['url_source']."]")
		. "</div>";
	else if (strlen($row['source']))
		$s .= "<div style='float:$spip_lang_right;'>"
		. typo($row['source'])
		. "</div>";

	$vals[] = $s;

	// on n'affiche pas la colonne 'site' lorsqu'on regarde un site precis
	if ($GLOBALS['exec'] != 'sites') {
		// $my_sites cache les resultats des requetes sur les sites
		if (!$my_sites[$id_syndic])
			$my_sites[$id_syndic] = spip_fetch_array(spip_query("SELECT nom_site, moderation, miroir FROM spip_syndic WHERE id_syndic=$id_syndic"));

		$aff = $my_sites[$id_syndic]['nom_site'];
		if ($my_sites[$id_syndic]['moderation'] == 'oui')
			$aff = "<i>$aff</i>";
			
		$s = "<a href='" . generer_url_ecrire("sites","id_syndic=$id_syndic") . "'>$aff</a>";

		$vals[] = $s;
	}
				
	if ($connect_statut == '0minirezo'){
		if ($statut == "publie"){
		  $s =  "[<a href='". generer_action_auteur("instituer", "syndic_article-$id_syndic_article-refuse", $redirect) . "'><font color='black'>"._T('info_bloquer_lien')."</font></a>]";
		
		}
		else if ($statut == "refuse"){
			$s =  "[<a href='". generer_action_auteur("instituer", "syndic_article-$id_syndic_article-publie", $redirect) . "'>"._T('info_retablir_lien')."</a>]";
		}
		else if ($statut == "off"
		AND $my_sites[$id_syndic]['miroir'] == 'oui') {
			$s = '('._T('syndic_lien_obsolete').')';
		}
		else /* 'dispo' ou 'off' (dans le cas ancien site 'miroir') */
		{
			$s = "[<a href='". generer_action_auteur("instituer", "syndic_article-$id_syndic_article-publie", $redirect) . "'>"._T('info_valider_lien')."</a>]";
		}
		$vals[] = $s;
	}
			
	return $vals;
}
?>
