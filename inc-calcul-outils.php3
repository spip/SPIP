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

function calcule_logo($type, $onoff, $id, $id_rubrique, $lien, $align, $ff){
  $table_logos = array (
	'ARTICLE' => 'art',
	'AUTEUR' =>  'aut',
	'BREVE' =>  'breve',
	'MOT' => 'mot',
	'RUBRIQUE' => 'rub',
	'SITE' => 'site'
);
  $type = $table_logos[$type];
  while ($id) {
    $on = cherche_image_nommee($type . $onoff . $id);
    if ($on) 
      return ($ff ? calcule_fichier_logo($on) :
	      affiche_logos($on, 
			    (($onoff == 'off') ? '' : 
			     cherche_image_nommee($type . 'off' . $id)),
			    $lien,
			    $align));
    else if ($id_rubrique)
      {$type = 'rub'; $id = $id_rubrique; $id_rubrique = 0;}
    else if ($type = 'rub') $id = sql_parent($id);
  }
  return '';
}

// Renvoie le code html pour afficher le logo, avec ou sans survol, avec ou sans lien, etc.
function affiche_logos($arton, $artoff, $lien, $align) {
	global $num_survol;
	global $espace_logos;

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
			if ($x = table_from_primary($element)) {
				list($table,$hierarchie) = $x;
				$exposer[$element][$id_element] = true;
				if ($hierarchie) {
					list ($id_rubrique) = spip_abstract_fetsel(
array('id_rubrique'), 
array($table),
array("$element=$id_element"));
				$hierarchie = substr(calculer_hierarchie($id_rubrique), 2);
				foreach (split(',',$hierarchie) as $id_rubrique)
					$exposer['id_rubrique'][$id_rubrique] = true;
				}
			}
		}
	}

	// And the winner is...
	return $exposer[$type][$id];
}

function table_from_primary($id)
{
	global $tables_principales;
	include_ecrire('inc_serialbase.php3');
	foreach ($tables_principales as $k => $v)
	  { if ($v['key']['PRIMARY KEY'] == $id) 
	      return array($k, array_key_exists('id_rubrique', $v['field']));
	  }
	return '';
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
  $row = spip_fetch_array(spip_query("SELECT * FROM spip_documents WHERE id_document = $id_document"));
  if (!$row) return '';
  if ($id_vignette = $row['id_vignette']) {
		if ($row_vignette = @spip_fetch_array(spip_query("SELECT fichier FROM spip_documents WHERE id_document = $id_vignette"))) {
			if (@file_exists($row_vignette['fichier']))
				return generer_url_document($id_vignette);
			}
  } else if ($row['mode'] == 'vignette') {
	return generer_url_document($id_document);
  }
// si pas de vignette, utiliser la vignette par defaut
// ou essayer de creer une preview (images)
  list($extension) = @spip_fetch_array(spip_query("SELECT extension FROM spip_types_documents WHERE id_type = " .$row['id_type']));
  if (!ereg(",$extension,", ','.lire_meta('formats_graphiques').',')) {
	list($url, $w, $h) = vignette_par_defaut($extension);
	return $url;
  } else {
	return 'spip_image.php3?vignette='.rawurlencode(str_replace('../', '', $row['fichier']));
  }
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