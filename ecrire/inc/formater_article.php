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
include_spip('inc/afficher_objets');
// Fonction appelee dans une boucle, calculer les invariants au premier appel.

// http://doc.spip.org/@inc_formater_article_dist
function inc_formater_article_dist($row, $own='')
{
	global $spip_lang_right, $spip_display;
	static $pret = false;
	static $chercher_logo, $img_admin, $formater_auteur, $nb, $langue_defaut, $afficher_langue, $puce_statut;

	$id_article = $row['id_article'];

	if (!autoriser('voir','article',$id_article)) return '';

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
		$puce_statut = charger_fonction('puce_statut', 'inc');
		$pret = true;
	}

	if ($chercher_logo) {
		if ($logo = $chercher_logo($id_article, 'id_article', 'on')) {
			list($fid, $dir, $nom, $format) = $logo;
			include_spip('inc/filtres_images_mini');
			$logo = image_reduire("<img src='$fid' alt='' />", 26, 20);
		}
	} else $logo ='';

	$titre = sinon($row['titre'], _T('ecrire:info_sans_titre'));
	$id_rubrique = $row['id_rubrique'];
	$date = $row['date'];
	$statut = $row['statut'];
	$descriptif = $row['descriptif'];
	$lang_dir = lang_dir(($lang = $row['lang']) ? changer_typo($lang):'');

	$lien  = "<div>"
	. "<a href='"
	. generer_url_ecrire("articles","id_article=$id_article")
	. "'"
	. (!$descriptif ? '' : 
	     (' title="'.attribut_html(typo($descriptif)).'"'))
	. " dir='$lang_dir'>"
	. (!$logo ? '' :
	   ("<span style='float: $spip_lang_right; margin-top: -2px; margin-bottom: -2px;'>" . $logo . "</span>"))
	. (acces_restreint_rubrique($id_rubrique) ? $img_admin : '')
	  . typo(supprime_img($titre,''))
	. (!($afficher_langue AND $lang != $GLOBALS['meta']['langue_site'] AND strlen($lang)) ? '' :
	   (" <span class='spip_xx-small' style='color: #666666' dir='$lang_dir'>(".traduire_nom_langue($lang).")</span>"))
	  . (!$row['petition'] ? '' :
	     ("</a> <a href='" . generer_url_ecrire('controle_petition', "id_article=$id_article") . "' class='spip_xx-small' style='color: red'>"._T('lien_petitions')))
	. "</a>"
	. "</div>";
	
	if ($spip_display == 4) return array($lien);

	$puce = $puce_statut($id_article, $statut, $id_rubrique,'article');

	$auteurs = auteurs_article($id_article); 
	foreach ($auteurs as $k => $r) {
		list(, $mail, $nom,,) = $formater_auteur($r['id_auteur']);
		$auteurs[$k]= "$mail&nbsp;$nom";
	}

	$date = affdate_jourcourt($date);
	if (!$date) $date = '&nbsp;';

	$num = afficher_numero_edit($id_article, 'id_article', 'article');

	// Afficher le numero (JMB)

	return array($puce, $lien, join('<br />', $auteurs), $date, $num);
}

?>
