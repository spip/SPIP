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

// Fonction appelee dans une boucle, calculer les invariants au premier appel.

function inc_formater_article($id_article, $row, $afficher_auteurs, $afficher_langue, $langue_defaut)
{
	global $dir_lang, $options, $spip_lang_right, $spip_display;
	static $pret = false;
	static $chercher_logo, $img_admin, $formater_auteur, $nb;

	if (!$pret) {
		$chercher_logo = ($spip_display != 1 AND $spip_display != 4 AND $GLOBALS['meta']['image_process'] != "non");
		if ($chercher_logo) 
			$chercher_logo = charger_fonction('chercher_logo', 'inc');
		$formater_auteur = charger_fonction('formater_auteur', 'inc');
		$img_admin = http_img_pack("admin-12.gif", "", "width='12' height='12'", _T('titre_image_admin_article'));
		$nb = ($options != "avancees")
		  ? ''
		  : _T('info_numero_abbreviation');
		$pret = true;
	}

	if ($chercher_logo) {
		if ($logo = $chercher_logo($id_article, 'id_article', 'on')) {
			list($fid, $dir, $nom, $format) = $logo;
			$logo = ratio_image($fid, $nom, $format, 26, 20, "alt=''");
		}
	} else $logo ='';

	$vals = array();

	$titre = sinon($row['titre'], _T('ecrire:info_sans_titre'));
	$id_rubrique = $row['id_rubrique'];
	$date = $row['date'];
	$statut = $row['statut'];
	$descriptif = $row['descriptif'];
	if ($lang = $row['lang']) changer_typo($lang);

	$vals[]= puce_statut_article($id_article, $statut, $id_rubrique);

	$vals[]= "<div>"
	. (acces_restreint_rubrique($id_rubrique) ? $img_admin : '')
	. "<a href='"
	. generer_url_ecrire("articles","id_article=$id_article")
	. "'"
	. (!$descriptif ? '' : 
	     (' title="'.attribut_html(typo($descriptif)).'"'))
	. $dir_lang
	. " style=\"display:block;\">"
	. (!$logo ? '' :
	   ("<div style='float: $spip_lang_right; margin-top: -2px; margin-bottom: -2px;'>" . $logo . "</div>"))
	. typo($titre)
	. (!($afficher_langue AND $lang != $langue_defaut) ? '' :
	   (" <font size='1' color='#666666'$dir_lang>(".traduire_nom_langue($lang).")</font>"))
	. (!$row['petition'] ? '' : (" <font size=1 color='red'>"._T('lien_petitions')."</font>"))
	. "</a>"
	. "</div>";
	
	if ($afficher_auteurs) {

		$result = auteurs_article($id_article);
		$les_auteurs = "";
		while ($r = spip_fetch_array($result)) {
			list($s, $mail, $nom, $w, $p) = $formater_auteur($r['id_auteur']);
			$les_auteurs .= "$mail&nbsp;$nom, ";
		}
		$vals[] = substr($les_auteurs, 0, -2);
	} else $vals[]= '&nbsp;';

	$s = affdate_jourcourt($date);
	$vals[] = $s ? $s : '&nbsp;';

	if  ($nb) $vals[]= "<b>" . $nb . $id_article . '</b>';

	return $vals;
}

?>
