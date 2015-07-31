<?php

//
// Traduction des champs "formulaire" et "parametres"
//


// Formulaire de recherche
function calculer_champ_FORMULAIRE_RECHERCHE($fonctions, $nom_champ, $id_boucle, &$boucles, $id_mere) {
	if ($fonctions) {
		list(, $lien) = each($fonctions);	// le premier est un url
		while (list(, $filtre) = each($fonctions))
			$filtres[] = $filtre;	// les suivants sont des filtres
		$fonctions = $filtres;
	}
	if (!$lien) $lien = 'recherche.php3';
	$code = "((lire_meta('activer_moteur') != 'oui') ? '' : calcul_form_rech('$lien'))";
	return applique_filtres($fonctions, $code, $id_boucle, $boucles, $id_mere, 'php');
}


// Formulaire d'inscription comme redacteur (dans inc-formulaires.php3)
function calculer_champ_FORMULAIRE_INSCRIPTION($fonctions, $nom_champ, $id_boucle, &$boucles, $id_mere) {
	$code = '(lire_meta("accepter_inscriptions") != "oui") ? "" :
		("<"."?php include(\'inc-formulaires.php3\'); lang_select(\"$spip_lang\"); formulaire_inscription(\"redac\"); lang_dselect(); ?".">")';
	list($c,$m) = applique_filtres($fonctions, $code, $id_boucle, $boucles, $id_mere, 'php');
	return array($c,$m);
}

// Formulaire ecrire auteur (OK)
function calculer_champ_FORMULAIRE_ECRIRE_AUTEUR($fonctions, $nom_champ, $id_boucle, &$boucles, $id_mere) {
	$_id_auteur = index_pile($id_boucle, 'id_auteur', $boucles);
	$_mail_auteur = index_pile($id_boucle, 'email', $boucles);
	$code = '!email_valide('.$_mail_auteur.') ? "" :
		("<'.'?php include(\'inc-formulaires.php3\');
		lang_select(\'$spip_lang\');
		formulaire_ecrire_auteur(".'.$_id_auteur.'.", \'".texte_script('.$_mail_auteur.')."\');
		lang_dselect(); ?'.'>")';
	list($c,$m) = applique_filtres($fonctions, $code, $id_boucle, $boucles, $id_mere, 'php');
	return array($c,$m);  
}

// Formulaire signature de petition
function calculer_champ_FORMULAIRE_SIGNATURE($fonctions, $nom_champ, $id_boucle, &$boucles, $id_mere) {
	$_id_article = index_pile($id_boucle, 'id_article', $boucles);
	$code = '!($petition = sql_petitions('.$_id_article.')) ? "" :
		("<"."?php include(\'inc-formulaires.php3\');
		lang_select(\'$spip_lang\');
		echo formulaire_signature(".'.$_id_article.'.",
			\'".texte_script(serialize($petition))."\');
		lang_dselect(); ?".">")';
	list($c,$m) = applique_filtres($fonctions, $code, $id_boucle, $boucles, $id_mere, 'php');
	return array($c,$m);
}

// Formulaire d'inscription de site dans l'annuaire
function calculer_champ_FORMULAIRE_SITE($fonctions, $nom_champ, $id_boucle, &$boucles, $id_mere) {
	$code = '(lire_meta("proposer_sites") != 2) ? "":
		"<"."?php include(\'inc-formulaires.php3\');
		lang_select(\'".$GLOBALS[\'spip_lang\']."\');
		formulaire_site(".'.index_pile($id_boucle, 'id_rubrique', $boucles).'.");
		lang_dselect(); ?".">"';
	list($c,$m) = applique_filtres($fonctions, $code, $id_boucle, $boucles, $id_mere, 'php');
	return array($c,$m);
}


// Formulaire de reponse a un forum
function calculer_champ_FORMULAIRE_FORUM($fonctions, $nom_champ, $id_boucle, &$boucles, $id_mere) {
	$type = $boucles[$id_boucle]->type_requete;
	switch ($type) {
		case 'breves':
			$code = "boutons_de_forum('', '', ''," .
				index_pile($id_boucle,  'id_breve', $boucles) .
				", '', " .
				index_pile($id_boucle,  'titre', $boucles) .
				", '$type', substr(lire_meta('forums_publics'),0,3)), \$Cache)";
		break;

		case 'rubriques':
			$code = 'boutons_de_forum(' .
			index_pile($id_boucle,  'id_rubrique', $boucles) .
			", '', '', '', ''," .
			index_pile($id_boucle,  'titre', $boucles) .
			", '$type', substr(lire_meta('forums_publics'),0,3)), \$Cache)";
			break;

		case 'syndication':
			$code = "boutons_de_forum('', '', '','', " .
			index_pile($id_boucle, 'id_rubrique', $boucles) . ", " .
			index_pile($id_boucle,  'nom_site', $boucles) .
			", '$type', substr(lire_meta('forums_publics'),0,3)), \$Cache)";
			break;
    
	case 'articles': 
		$code = "boutons_de_forum('', '', " .
		index_pile($id_boucle, 'id_article', $boucles) .
		", '','', " .
		index_pile($id_boucle,  'nom_site', $boucles) .
		"'$type', " .
		index_pile($id_boucle,  'accepter_forum', $boucles) .
		', $Cache)';
		break;

	case 'forums':
	default:
		$code = "boutons_de_forum(" .
		index_pile($id_boucle, 'id_rubrique', $boucles) . ', ' .
		index_pile($id_boucle, 'id_forum', $boucles) . ', ' .
		index_pile($id_boucle, 'id_article', $boucles) . ', ' .
		index_pile($id_boucle, 'id_breve', $boucles) . ', ' .
		index_pile($id_boucle, 'id_syndic', $boucles) . ', ' .
		index_pile($id_boucle, 'titre', $boucles) .
		", '$type', '', \$Cache)";
		break;
	}
	list($c,$m) = applique_filtres($fonctions, $code, $id_boucle, $boucles, $id_mere, 'php');
	return array($c,$m);
}

// Parametres de reponse a un forum
function calculer_champ_PARAMETRES_FORUM($fonctions, $nom_champ, $id_boucle, &$boucles, $id_mere) {
	$_accepter_forum = index_pile($id_boucle,  "accepter_forum", $boucles);
	$code = '
	// refus des forums ?
	('.$_accepter_forum.'=="non" OR
	(lire_meta("forums_publics") == "non" AND '.$_accepter_forum.'!="oui"))
	? "" : // sinon:
	';


	switch ($boucles[$id_boucle]->type_requete) {
		case 'articles':
			$c = '"id_article=".' . index_pile($id_boucle, 'id_article', $boucles);
			break;
		case 'breves':
			$c = '"id_breve=".' . index_pile($id_boucle, 'id_breve', $boucles);
			break;
		case 'rubriques':
			$c = '"id_rubrique=".' . index_pile($id_boucle, 'id_rubrique', $boucles);
			break;
		case 'syndication':
			$c = '"id_syndic=".' . index_pile($id_boucle, 'id_syndic', $boucles);
			break;
		case 'forums':
		default:
			$liste_champs = array ("id_article","id_breve","id_rubrique","id_syndic","id_forum");
			foreach ($liste_champs as $champ) {
				$x = index_pile($id_boucle,  $champ, $boucles);
				$c .= (($c) ? ".\n" : "") . "((!$x) ? '' : ('&$champ='.$x))";
			}
			$c = "substr($c,1)";
			break;
	}

	$c .= '.
	"&retour=".rawurlencode($lien=$GLOBALS["HTTP_GET_VARS"]["retour"] ? $lien : nettoyer_uri())';

	// Noter l'invalideur de la page contenant ces parametres,
	// en cas de premier post sur le forum (a mettre dans ecrire/inc_forum et
	// a repliquer sur les autres balises FORUM)
	$invalide = '
	// invalideur forums
	(!($Cache[\'id_forum\'][calcul_index_forum(' . 
				// Retournera 4 [$SP] mais force la demande du champ a MySQL
				index_pile($id_boucle, 'id_article', $boucles) . ',' .
				index_pile($id_boucle, 'id_breve', $boucles) .  ',' .
				index_pile($id_boucle, 'id_rubrique', $boucles) .',' .
				index_pile($id_boucle, 'id_syndic', $boucles) .  ")]=1)?'':\n";
	$code .= $invalide."(".$c."))";

	list($c,$m) = applique_filtres($fonctions, $code, $id_boucle, $boucles, $id_mere);
	return array($c,$m);
}

/*
# Boutons d'administration: 
*/
function calculer_champ_FORMULAIRE_ADMIN($fonctions, $nom_champ, $id_boucle, &$boucles, $id_mere) {
  return array("'<!-- @@formulaire_admin@@45609871@@ -->'",'');
}

?>
