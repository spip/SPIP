<?php

//
// Des fonctions diverses utilisees lors du calcul d'une page ; ces fonctions
// bien pratiques n'ont guere de logique organisationnelle ; elles sont
// appelees par certaines balises au moment du calcul des pages. (Peut-on
// trouver un modele de donnees qui les associe physiquement au fichier
// definissant leur balise ???
//

// ON TROUVERA EN QUEUE DE FICHIER LES FONCTIONS FAISANT DES APPELS SQL


// Ce fichier ne sera execute qu'une fois
if (defined("_INC_CALCUL_OUTILS")) return;
define("_INC_CALCUL_OUTILS", "1");


#
# AFFREUX !!  Passer tout ca en CSS au plus vite !
#
tester_variable('espace_logos',3);
// HSPACE=xxx VSPACE=xxx pour les logos (#LOGO_ARTICLE)
tester_variable('espace_images',3);
// HSPACE=xxx VSPACE=xxx pour les images integrees

//
// Retrouver le logo d'un objet (et son survol)
//


function cherche_image($id_objet, $type_objet) {
	// cherche l'image liee a l'objet
	$on = cherche_image_nommee($type_objet.'on'.$id_objet);

	// cherche un survol
	$off =(!$on ? '' :
	cherche_image_nommee($type_objet.'off'.$id_objet));

	if (!$on)
		return false;

	return array($on, $off);
}

function cherche_logo_objet ($type, $id_objet, $on = false, $off = false, $flag_fichier=false) {

	# spip_log("cherche logo $type $id_objet $on $off $flag_fichier");
	switch($type) {
		case 'ARTICLE':
			$logo = cherche_image($id_objet, 'art');
			break;
		case 'AUTEUR':
			$logo = cherche_image($id_objet, 'aut');
			break;
		case 'BREVE':
			$logo = cherche_image($id_objet, 'breve');
			break;
		case 'SITE':
			$logo = cherche_image($id_objet, 'site');
			break;
		case 'MOT':
			$logo = cherche_image($id_objet, 'mot');
			break;
		// recursivite
		case 'RUBRIQUE':
			if (!($logo = cherche_image ($id_objet, 'rub'))
			AND $id_objet > 0)
				$logo = cherche_logo_objet('RUBRIQUE',
				sql_parent($id_objet), true, true);
			break;
		default:
			spip_log("cherche_logo_objet: type '$type' inconnu");
	}

	// Quelles images sont demandees ?
	if (!$on) unset($logo[0]);
	if (!$off) unset($logo[1]);

	if ($logo[0] OR $logo[1])
		return $logo;
}

// Renvoie le code html pour afficher le logo, avec ou sans survol, avec ou sans lien, etc.
function affiche_logos($logo, $lien, $align, $flag_fichier) {
	global $num_survol;
	global $espace_logos;

	list($arton,$artoff) = $logo;

	// Pour les documents comme pour les logos, le filtre |fichier donne
	// le chemin du fichier apres 'IMG/' ;  peut-etre pas d'une purete
	// remarquable, mais a conserver pour compatibilite ascendante.
	// -> http://www.spip.net/fr_article901.html
	if ($flag_fichier) {
		$on = ereg_replace("^IMG/","",$arton);
		$off = ereg_replace("^IMG/","",$artoff);
		return $on ? $on : $off;
	}

	$num_survol++;
	if ($arton) {
		//$imgsize = @getimagesize("$arton");
		//$taille_image = ereg_replace("\"","'",$imgsize[3]);
		if ($align) $align="align='$align' ";

		$milieu = "<img src='$arton' $align".
			" name='image$num_survol' ".$taille_image." border='0' alt=''".
			" hspace='$espace_logos' vspace='$espace_logos' class='spip_logos' />";

		if ($artoff) {
			if ($lien) {
				$afflien = "<a href='$lien'";
				$afflien2 = "a>";
			}
			else {
				$afflien = "<div";
				$afflien2 = "div>";
			}
			$milieu = "$afflien onMouseOver=\"image$num_survol.src=".
				"'$artoff'\" onMouseOut=\"image$num_survol.src=".
				"'$arton'\">$milieu</$afflien2";
		}
		else if ($lien) {
			$milieu = "<a href='$lien'>$milieu</a>";
		}
	} else {
		$milieu="";
	}
	return $milieu;
}

//
// fonction standard de calcul de la balise #INTRODUCTION
// on peut la surcharger en definissant dans mes_fonctions.php3 :
// function introduction($type,$texte,$descriptif) {...}
//
function calcul_introduction ($type, $texte, $chapo='', $descriptif='') {
	if (function_exists("introduction"))
		return introduction ($type, $texte, $chapo, $descriptif);

	switch ($type) {
		case 'articles':
			if ($descriptif)
				return propre($descriptif);
			else if (substr($chapo, 0, 1) == '=')	// article virtuel
				return '';
			else
				return PtoBR(propre(supprimer_tags(couper_intro($chapo."\n\n\n".$texte, 500))));
			break;
		case 'breves':
			return PtoBR(propre(supprimer_tags(couper_intro($texte, 300))));
			break;
		case 'forums':
			return PtoBR(propre(supprimer_tags(couper_intro($texte, 600))));
			break;
		case 'rubriques':
			if ($descriptif)
				return propre($descriptif);
			else
				return PtoBR(propre(supprimer_tags(couper_intro($texte, 600))));
			break;
	}
}


//
// FONCTIONS FAISANT DES APPELS SQL
//

# NB : a l'exception des fonctions de forum regroupees dans inc-forum.

function calcul_exposer ($id, $type, $reference) {
	static $exposer;
	static $ref_precedente;

	// Que faut-il exposer ? Tous les elements de $reference
	// ainsi que leur hierarchie ; on ne fait donc ce calcul
	// qu'une fois (par squelette) et on conserve le resultat
	// en static.
	if ($reference<>$ref_precedente) {
		$ref_precedente = $reference;

		$exposer = array();
		foreach ($reference as $element=>$id_element) {
			if ($element == 'id_secteur') $element = 'id_rubrique';
			if (ereg("id_(article|breve|rubrique|syndic)", $element, $regs)) {
				$exposer[$element][$id_element] = true;
				list ($id_rubrique) = spip_abstract_fetsel(
array('id_rubrique'), 
array(table_objet($regs[1])),
array("$element=$id_element"));
				$hierarchie = substr(calculer_hierarchie($id_rubrique), 2);
				foreach (split(',',$hierarchie) as $id_rubrique)
					$exposer['id_rubrique'][$id_rubrique] = true;
			}
		}
	}

	// And the winner is...
	return $exposer[$type][$id];
}

function calcul_generation ($generation) {
	$lesfils = array();
	$result = spip_abstract_select(array('id_rubrique'),
				       array('rubriques AS rubriques'),
				       array(calcul_mysql_in('id_parent', 
							     $generation,
							     '')),
				       '','','','','','','');
	while ($row = spip_abstract_fetch($result))
	  $lesfils[] = $row['id_rubrique'];
	return join(",",$lesfils);
}

function calcul_branche ($generation) {
	if (!$generation) 
	  return '0';
	else {
		$branche[] = $generation;
		while ($generation = calcul_generation ($generation))
			$branche[] = $generation;
		return join(",",$branche);
	}
}

function calcule_document($id_document, $doubdoc, &$doublons){
  if ($doubdoc && $id_document) $doublons["documents"] .= ', ' . $id_document;
  return (array(integre_image($id_document, '', 'fichier_vignette'), ''));
}


# fonction appelée par la balise #EMBED

function calcule_embed_document($id_document, $filtres, &$doublons, $doubdoc){
  if ($doubdoc && $id_document) $doublons["documents"] .= ', ' . $id_document;
  return embed_document($id_document, $filtres, false);
}

# fonction appelée par la balise #NOTES

function calculer_notes()
{
  $r = $GLOBALS["les_notes"];
  $GLOBALS["les_notes"] = "";
  $GLOBALS["compt_note"] = 0;
  $GLOBALS["marqueur_notes"] ++;
  return $r;
}

# retourne la profondeur d'une rubrique

function sql_profondeur($id)
{
	$n = 0;
	while ($id) {
		$n++;
		$id = sql_parent($id);
	}
	return $n;
}


function sql_parent($id_rubrique)
{
  $row = spip_abstract_fetsel(array(id_parent), 
			      array('rubriques'), 
			      array("id_rubrique='$id_rubrique'"));
  return $row['id_parent'];
}

function sql_rubrique($id_article)
{
  $row = spip_abstract_fetsel(array('id_rubrique'),
			      array('articles'),
			      array("id_article='$id_article'"));
  return $row['id_rubrique'];
}

function sql_auteurs($id_article, $table, $id_boucle, $serveur='')
{
  $auteurs = "";
  if ($id_article)
    {
      $result_auteurs = spip_abstract_select(array('auteurs.nom', 'auteurs.email'),
					     array('auteurs AS auteurs',
						   'auteurs_articles AS lien'), 
					     array("lien.id_article=$id_article",
						   "auteurs.id_auteur=lien.id_auteur"),
					     '','','','',1, 
					     $table, $id_boucle, $serveur);

      while($row_auteur = spip_abstract_fetch($result_auteurs, $serveur)) {
	$nom_auteur = typo($row_auteur["nom"]);
	$email_auteur = $row_auteur["email"];
	if ($email_auteur) {
	  $auteurs[] = "<a href=\"mailto:$email_auteur\">$nom_auteur</a>";
	}
	else {
	  $auteurs[] = "$nom_auteur";
	}
      }
    }
  return (!$auteurs) ? "" : join($auteurs, ", ");
}

function sql_petitions($id_article, $table, $id_boucle, $serveur='') {
  return spip_abstract_fetsel(array('id_article', 'email_unique', 'site_obli', 'site_unique', 'message', 'texte'),
			      array('petitions'),
			      array("id_article=".intval($id_article)),
			      '','','','',1, 
			      $table, $id_boucle, $serveur);
}

# retourne le chapeau d'un article, et seulement s'il est publie

function sql_chapo($id_article)
{
  return spip_abstract_fetsel(array('chapo'),
			      array('articles'),
			      array("id_article='$id_article'",
				    "statut='publie'"));
}

// Calcul de la rubrique associee a la requete
// (selection de squelette specifique par id_rubrique & lang)

function sql_rubrique_fond($contexte, $lang) {

	if ($id = intval($contexte['id_rubrique'])) {
	  $row = spip_abstract_fetsel(array('lang'),
				      array('rubriques'),
				      array("id_rubrique='$id'"));
		if ($row['lang'])
			$lang = $row['lang'];
		return array ($id, $lang);
	}

	if ($id  = intval($contexte['id_breve'])) {
	  $row = spip_abstract_fetsel(array('id_rubrique', 'lang'),
				      array('breves'), 
				      array("id_breve='$id'"));
		$id_rubrique_fond = $row['id_rubrique'];
		if ($row['lang'])
			$lang = $row['lang'];
		return array($id_rubrique_fond, $lang);
	}

	if ($id = intval($contexte['id_syndic'])) {
	  $row = spip_abstract_fetsel(array('id_rubrique'),
				      array('syndic'),
				      array("id_syndic='$id'"));
		$id_rubrique_fond = $row['id_rubrique'];
		$row = spip_abstract_fetsel(array('lang'),
					    array('rubriques'),
					    array("id_rubrique='$id_rubrique_fond'"));
		if ($row['lang'])
			$lang = $row['lang'];
		return array($id_rubrique_fond, $lang);
	}

	if ($id = intval($contexte['id_article'])) {
	  $row = spip_abstract_fetsel(array('id_rubrique', 'lang'),
				      array('articles'),
				      array("id_article='$id'"));
		$id_rubrique_fond = $row['id_rubrique'];
		if ($row['lang'])
			$lang = $row['lang'];
		return array($id_rubrique_fond, $lang);
	}
}

?>