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

// Fonction appelee dans une boucle, calculer les invariants au premier appel.

// http://doc.spip.org/@inc_formater_article_dist
function inc_formater_article_dist($row)
{
	global $options, $spip_lang_right, $spip_display;
	static $pret = false;
	static $chercher_logo, $img_admin, $formater_auteur, $nb, $langue_defaut, $afficher_langue;

	if (!$pret) {
		$chercher_logo = ($spip_display != 1 AND $spip_display != 4 AND $GLOBALS['meta']['image_process'] != "non");
		if ($chercher_logo) 
			$chercher_logo = charger_fonction('chercher_logo', 'inc');
		$formater_auteur = charger_fonction('formater_auteur', 'inc');
		$img_admin = http_img_pack("admin-12.gif", "", " width='12' height='12'", _T('titre_image_admin_article'));

		if (($GLOBALS['meta']['multi_rubriques'] == 'oui' AND (!isset($GLOBALS['id_rubrique']))) OR $GLOBALS['meta']['multi_articles'] == 'oui') {
			$afficher_langue = true;
			$langue_defaut = !isset($GLOBALS['langue_rubrique'])
			  ? $GLOBALS['meta']['langue_site']
			  : $GLOBALS['langue_rubrique'];
		}
		$pret = true;
	}

	$id_article = $row['id_article'];

	if ($chercher_logo) {
		if ($logo = $chercher_logo($id_article, 'id_article', 'on')) {
			list($fid, $dir, $nom, $format) = $logo;
			include_spip('inc/filtres_images');
			$logo = image_reduire("<img src='$fid' alt='' />", 26, 20);
		}
	} else $logo ='';

	$vals = array();

	$titre = sinon($row['titre'], _T('ecrire:info_sans_titre'));
	$id_rubrique = $row['id_rubrique'];
	$date = $row['date'];
	$statut = $row['statut'];
	$descriptif = $row['descriptif'];
	$lang_dir = lang_dir(($lang = $row['lang']) ? changer_typo($lang):'');

	$vals[]= puce_statut_article($id_article, $statut, $id_rubrique);

	$vals[]= "<div>"
	. "<a href='"
	. generer_url_ecrire("articles","id_article=$id_article")
	. "'"
	. (!$descriptif ? '' : 
	     (' title="'.attribut_html(typo($descriptif)).'"'))
	. " dir='$lang_dir'"
	. " style=\"display:block;\">"
	. (!$logo ? '' :
	   ("<span style='float: $spip_lang_right; margin-top: -2px; margin-bottom: -2px;'>" . $logo . "</span>"))
	. (acces_restreint_rubrique($id_rubrique) ? $img_admin : '')
	. typo($titre)
	. (!($afficher_langue AND $lang != $GLOBALS['meta']['langue_site']) ? '' :
	   (" <span class='spip_xx-small' style='color: #666666' dir='$lang_dir'>(".traduire_nom_langue($lang).")</span>"))
	. (!$row['petition'] ? '' : (" <span class='spip_xx-small' style='color: red'>"._T('lien_petitions')."</span>"))
	. "</a>"
	. "</div>";
	
	$result = auteurs_article($id_article);
	$les_auteurs = array();
	while ($r = spip_fetch_array($result)) {
		list($s, $mail, $nom, $w, $p) = $formater_auteur($r['id_auteur']);
		$les_auteurs[]= "$mail&nbsp;$nom";
	}
	$vals[] = join('<br />', $les_auteurs);

	$s = affdate_jourcourt($date);
	$vals[] = $s ? $s : '&nbsp;';

	$vals[]= afficher_numero_edit($id_article, 'id_article', 'article');

	// Afficher le numero (JMB)
	  $largeurs = array(11, '', 80, 100, 50);
	  $styles = array('', 'arial2', 'arial1', 'arial1', 'arial1');

	return ($spip_display != 4)
	? afficher_liste_display_neq4($largeurs, $vals, $styles)
	: afficher_liste_display_eq4($largeurs, $vals, $styles);
}

?>
