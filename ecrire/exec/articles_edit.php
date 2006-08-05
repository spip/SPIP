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
include_spip('inc/article_select');
include_spip('inc/rubriques');
include_spip('inc/documents');
include_spip('inc/barre');

//
// Gestion des textes trop longs (limitation brouteurs)
//

function coupe_trop_long($texte){	// utile pour les textes > 32ko
	if (strlen($texte) > 28*1024) {
		$texte = str_replace("\r\n","\n",$texte);
		$pos = strpos($texte, "\n\n", 28*1024);	// coupe para > 28 ko
		if ($pos > 0 and $pos < 32 * 1024) {
			$debut = substr($texte, 0, $pos)."\n\n<!--SPIP-->\n";
			$suite = substr($texte, $pos + 2);
		} else {
			$pos = strpos($texte, " ", 28*1024);	// sinon coupe espace
			if (!($pos > 0 and $pos < 32 * 1024)) {
				$pos = 28*1024;	// au pire (pas d'espace trouv'e)
				$decalage = 0; // si y'a pas d'espace, il ne faut pas perdre le caract`ere
			} else {
				$decalage = 1;
			}
			$debut = substr($texte,0,$pos + $decalage); // Il faut conserver l'espace s'il y en a un
			$suite = substr($texte,$pos + $decalage);
		}
		return (array($debut,$suite));
	}
	else
		return (array($texte,''));
}

function articles_edit_recolle($texte, $att_text)
{
	$textes_supplement = "<br /><font color='red'>"._T('info_texte_long')."</font>\n";
	$nombre = 0;

	while (strlen($texte)>29*1024) {
		$nombre ++;
		list($texte1,$texte) = coupe_trop_long($texte);

		$textes_supplement .= "<br />" .
			afficher_barre('document.formulaire.texte'.$nombre)  .
			"<textarea id='texte$nombre' name='texte_plus[$nombre]'$att_text>$texte1</textarea><p>\n";
		}
	return array($texte,$textes_supplement);
}

function chapo_articles_edit($chapo, $articles_chapeau)
{
	global $spip_ecran;

	if (substr($chapo, 0, 1) == '=') {
		$virtuel = substr($chapo, 1);
		$chapo = "";
	}

	if ($virtuel) {
		return "<p><div style='border: 1px dashed #666666; background-color: #f0f0f0; padding: 5px;'>" .
			"<table width=100% cellspacing=0 cellpadding=0 border=0>" .
			"<tr><td valign='top'>" .
			"<font face='Verdana,Arial,Sans,sans-serif' size=2>" .
			"<B><label for='confirme-virtuel'>"._T('info_redirection')."&nbsp;:</label></B>" .
			aide ("artvirt") .
			"</font>" .
			"</td>" .
			"<td width=10>&nbsp;</td>" .
			"<td valign='top' width='50%'>" .
			"<INPUT TYPE='text' NAME='virtuel' CLASS='forml'
		style='font-size:9px;' VALUE=\"$virtuel\" SIZE='40'>" .
			"<input type='hidden' name='changer_virtuel' value='oui'>" .
			"</td></tr></table>\n" .
			"<font face='Verdana,Arial,Sans,sans-serif' size=2>" .
			_T('texte_article_virtuel_reference') .
			"</font>" .
			"</div><p>\n";
	}

	else {

		if (($articles_chapeau) OR strlen($chapo)) {
			if ($spip_ecran == "large") $rows = 8;
			else $rows = 5;
			return "<br /><B>"._T('info_chapeau')."</B>" .
				aide ("artchap") .
				"<BR>"._T('texte_introductif_article')."<BR>" .
				"<textarea name='chapo' class='forml' rows='$rows' COLS='40' wrap=soft>" .
				$chapo .
				"</textarea><P>\n";
		}
		else {
			return "<br /><INPUT TYPE='hidden' NAME='chapo' VALUE=\"$chapo\">";
		}
	}
}

function formulaire_articles_edit($row, $lier_trad, $new, $champs_article) {

	global $champs_extra, $spip_lang, $options, $spip_ecran, $spip_display;

	$articles_surtitre = $champs_article['articles_surtitre'] != 'non';
	$articles_soustitre = $champs_article['articles_soustitre'] != "non";
	$articles_descriptif = $champs_article['articles_descriptif'] != "non";
	$articles_urlref = $champs_article['articles_urlref'] != "non";
	$articles_chapeau = $champs_article['articles_chapeau'] != "non";
	$articles_ps = $champs_article['articles_ps']  != "non";

	$id_trad = $row['id_article'];
	$gros_titre = $row['titre'];
	// Gaffe: sans ceci, on ecrase systematiquement l'article d'origine
	// (et donc: pas de lien de traduction)
	$id_article = $lier_trad ? '' : $id_trad;

	$titre = entites_html($row['titre']);
	$soustitre = entites_html($row['soustitre']);
	$surtitre = entites_html($row['surtitre']);
	$descriptif = entites_html($row['descriptif']);
	$nom_site = entites_html($row['nom_site']);
	$url_site = entites_html($row['url_site']);
	$chapo = entites_html($row['chapo']);
	$texte = entites_html($row['texte']);
	$ps = entites_html($row['ps']);

	$id_rubrique = $row['id_rubrique'];
	$id_secteur = $row['id_secteur'];
	$date = $row['date'];
	$extra = $row['extra'];
	$onfocus = $row['onfocus'];
	$statut = $row['statut'];
	
	if ($id_rubrique == 0) $logo = "racine-site-24.gif";
	elseif ($id_secteur == $id_rubrique) $logo = "secteur-24.gif";
	else $logo = "rubrique-24.gif";

	if ($spip_ecran == "large") $rows = 28;	else $rows = 20;
	$att_text = " class='formo' ".$GLOBALS['browser_caret']." rows='$rows' COLS='40' wrap='soft'";
	if (strlen($texte)>29*1024) { // texte > 32 ko -> decouper en morceaux
	  list($texte, $sup) = articles_edit_recolle($texte, $att_text);
	} else $sup='';

	if ($champs_extra) include_spip('inc/extra');

	$selecteur_rubrique = charger_fonction('chercher_rubrique', 'inc');
	return
		"\n<table cellpadding='0' cellspacing='0' border='0' width='100%'>" .
		"<tr width='100%'>" .
		"<td>" .
		($lier_trad ?
		 icone(_T('icone_retour'), generer_url_ecrire("articles","id_article=$lier_trad"), "article-24.gif", "rien.gif", '',false) :
		 icone(_T('icone_retour'), generer_url_ecrire("articles","id_article=$id_trad"), "article-24.gif", "rien.gif",'',false)) .
		"</td>\n<td>" .
		http_img_pack('rien.gif', " ", "width='10'") .
		"</td>\n" .
		"<td width='100%'>" .
	 	_T('texte_modifier_article') .
		gros_titre($gros_titre,'',false) . 
		"</td></tr></table><p><hr /><p>" .

		generer_url_post_ecrire("articles", ($id_article ? "id_article=$id_article" : ""),'formulaire','',' onchange="disable_other_forms(this);"') .
		(!$new ? '' : "<input type='hidden' name='new' value='oui' />") .
		(!$lier_trad ? '' :
		 ("<input type='hidden' name='lier_trad' value='" .
		  $lier_trad .
		  "' />" .
		  "<input type='hidden' name='changer_lang' value='" .
		  $spip_lang .
		  "' />")) .

		(!(($options == "avancees" AND $articles_surtitre) OR $surtitre)?
			("<input type='hidden' name='surtitre' value=\"$surtitre\" />") :
			( "<b>" .
			  _T('texte_sur_titre') .
			  "</b>" .
			  aide ("arttitre") .
			  "<br /><input type='text' name='surtitre' class='forml' value=\"" .
			  $surtitre .
			  "\" size='40'" .
// Pour faire fonctionner le onchange sur Safari il faudrait modifier
// chaque input. Conclusion : c'est la mauvaise methode.
// ' onchange="disable_other_forms(this.parentNode);"'.
			  " /><P>")) .
		_T('texte_titre_obligatoire') .
		aide ("arttitre") .
		"\n<br /><input type='text' name='titre' style='font-weight: bold; font-size: 13px;' CLASS='formo' VALUE=\"" .
		$titre .
		"\" size='40' " .
		$onfocus .
		" />\n<P>" .

		(($articles_soustitre OR $soustitre) ?
		 ("<b>" .
		  _T('texte_sous_titre') .
		  "</b>" .
		  aide ("arttitre") .
		  "<br /><input type='text' name='soustitre' class='forml' value=\"" .
		  $soustitre .
		  "\" size='40' /><br /><br />") :
		 ("<input type='hidden' name='soustitre' value=\"$soustitre\" />")) .

		debut_cadre_couleur($logo, true, "", _T('titre_cadre_interieur_rubrique'). aide("artrub")) .

		$selecteur_rubrique($id_rubrique, 'article', ($statut == 'publie')) .

		fin_cadre_couleur(true) .
	
		($new ? '' : "<input type='hidden' name='id_rubrique_old' value='$id_rubrique'>") .

		((($options == "avancees" AND $articles_descriptif) OR $descriptif)?
		 ("<P><B>" ._T('texte_descriptif_rapide') ."</B>" .
		  aide ("artdesc") .
		  "</p><br />" ._T('texte_contenu_article') ."<br />" .
		  "<textarea name='descriptif' class='forml' rows='2' cols='40' wrap=soft>" .
		  $descriptif .
		  "</textarea>\n") :
		 ("<INPUT TYPE='hidden' NAME='descriptif' VALUE=\"$descriptif\" />")) .

		((($options == "avancees" AND $articles_urlref) OR $nom_site OR $url_site) ?
		 (_T('entree_liens_sites') ."<br />\n" .
		  _T('info_titre') ." " .
		  "<input type='text' name='nom_site' class='forml' width='40' value=\"$nom_site\"/><br />\n" .
		  _T('info_url') ." " .
		  "<input type='text' name='url_site' class='forml' width='40' value=\"$url_site\"/>") : '') .

		chapo_articles_edit($chapo, $articles_chapeau) .

		"<b>" ._T('info_texte') ."</b>" . 
		aide ("arttexte") . "<br />" .
		_T('texte_enrichir_mise_a_jour') .
		aide("raccourcis") .
		$sup .
		($spip_display==4 ? '' : afficher_barre('document.formulaire.texte')) .
		"<textarea id='text_area' name='texte'$att_text>$texte</textarea>\n" .

		((($articles_ps AND $options == "avancees") OR $ps) ?
		 ("<p><b>" . _T('info_post_scriptum') ."</b><br />" . "<textarea name='ps' class='forml' rows='5' cols='40' wrap=soft>" . $ps . "</textarea></p><p>\n") :
		 ("<input type='hidden' name='ps' value=\"" . $ps . "\">")) .

		(!$champs_extra ? '': extra_saisie($extra, 'articles', $id_secteur, false)) .

		(!$date ? '' : ("<input type='hidden' name='date' value=\"$date\" size='40'><P>")) .

		(!$new ? '' : ("<input type='hidden' name='statut_nouv' value=\"prepa\" SIZE='40' /><p>")) .

		"<div align='right'><input class='fondo' type='submit' value='" . _T('bouton_enregistrer') . "'></div></form>";
}

function exec_articles_edit_dist()
{
	$id_article =_request('id_article');
	$id_rubrique = _request('id_rubrique');
	$lier_trad = intval(_request('lier_trad'));
	$new = _request('new');

	pipeline('exec_init',array('args'=>array('exec'=>'articles_edit','id_article'=>$id_article),'data'=>''));
	
	$row = article_select($id_article, $id_rubrique, $lier_trad, $new);
	if (!$row) die ("<h3>"._T('info_acces_interdit')."</h3>");

	$id_article = $row['id_article'];

	// si une ancienne revision est demandee, la charger
	// en lieu et place de l'actuelle ; attention les champs
	// qui etaient vides ne sont pas vide's. Ca permet de conserver
	// des complements ajoutes "orthogonalement", et ca fait un code
	// plus generique.
	if ($id_version = intval(_request('id_version'))) {
		include_spip('inc/revisions');
		if ($textes = recuperer_version($id_article, $id_version)) {
			foreach ($textes as $champ => $contenu)
				$row[$champ] = $contenu;
		}
	}

	$id_rubrique = $row['id_rubrique'];
	$titre = $row['titre'];

	if ($id_version) $titre.= ' ('._T('version')." $id_version)";

	debut_page(_T('titre_page_articles_edit', array('titre' => $titre)),
			"documents", "articles", "hauteurTextarea();", 
			"",
			$id_rubrique);

	debut_grand_cadre();
	afficher_hierarchie($id_rubrique);
	fin_grand_cadre();

	debut_gauche();

	// Pave "documents associes a l'article"

	if (!$new){

	# affichage sur le cote des pieces jointes, en reperant les inserees
		if (isset($row['descriptif'])) document_a_voir($row['descriptif']);
		if (isset($row['chapo'])) document_a_voir($row['chapo']);
		if (isset($row['texte'])) document_a_voir($row['texte']);
		afficher_documents_colonne($id_article, 'article', true);
	}
	$GLOBALS['id_article_bloque'] = $id_article;	// globale dans debut_droite
	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'articles_edit','id_article'=>$id_article),'data'=>''));
	creer_colonne_droite();
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'articles_edit','id_article'=>$id_article),'data'=>''));
	debut_droite();
	
	debut_cadre_formulaire();
	echo formulaire_articles_edit($row, $lier_trad, $new, $GLOBALS['meta']);
	fin_cadre_formulaire();

	fin_page();
}
?>
