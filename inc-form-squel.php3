<?php

//
// Traduction des champs "formulaire" et "parametres"
//


// Formulaire de recherche
function balise_FORMULAIRE_RECHERCHE_dist($p) {
	if ($p->fonctions) {
		list(, $lien) = each($p->fonctions);	// le premier est un url
		while (list(, $filtre) = each($p->fonctions))
			$filtres[] = $filtre;	// les suivants sont des filtres
		$p->fonctions = $filtres;
	}
	if (!$lien) $lien = 'recherche.php3';

	$p->code = "((lire_meta('activer_moteur') != 'oui') ? '' : calcul_form_rech('$lien'))";

	$p->type = 'html';
	return $p;
}


// Formulaire d'inscription comme redacteur (dans inc-formulaires.php3)
function balise_FORMULAIRE_INSCRIPTION_dist($p) {

	$p->code = '(lire_meta("accepter_inscriptions") != "oui") ? "" :
		("<"."?php include(\'inc-formulaires.php3\'); lang_select(\"$spip_lang\"); formulaire_inscription(\"redac\"); lang_dselect(); ?".">")';

	$p->type = 'php';
	return $p;
}

// Formulaire ecrire auteur
function balise_FORMULAIRE_ECRIRE_AUTEUR_dist($p) {
	$_id_auteur = champ_sql('id_auteur', $p);
	$_mail_auteur = champ_sql('email', $p);

	$p->code = '!email_valide('.$_mail_auteur.') ? "" :
		("<'.'?php include(\'inc-formulaires.php3\');
		lang_select(\'$spip_lang\');
		formulaire_ecrire_auteur(".'.$_id_auteur.'.", \'".texte_script('.$_mail_auteur.')."\');
		lang_dselect(); ?'.'>")';

	$p->type = 'php';
	return $p;
}

// Formulaire signature de petition
function balise_FORMULAIRE_SIGNATURE_dist($p) {
	$_id_article = champ_sql('id_article', $p);

	$p->code = '!($petition = sql_petitions('.$_id_article.')) ? "" :
		("<"."?php include(\'inc-formulaires.php3\');
		lang_select(\'$spip_lang\');
		echo formulaire_signature(".'.$_id_article.'.",
			\'".texte_script(serialize($petition))."\');
		lang_dselect(); ?".">")';

	$p->type = 'php';
	return $p;
}

// Formulaire d'inscription de site dans l'annuaire
function balise_FORMULAIRE_SITE_dist($p) {
	$_id_rubrique = champ_sql('id_rubrique', $p);

	$p->code = '(lire_meta("proposer_sites") != 2) ? "":
		"<"."?php include(\'inc-formulaires.php3\');
		lang_select(\'".$GLOBALS[\'spip_lang\']."\');
		formulaire_site(".'.$_id_rubrique.'.");
		lang_dselect(); ?".">"';

	$p->type = 'php';
	return $p;
}


// Formulaire de reponse a un forum
function balise_FORMULAIRE_FORUM_dist($p) {
	$type = $p->type_requete;
	switch ($type) {
		case 'breves':
			$p->code = "boutons_de_forum('', '', ''," .
				champ_sql('id_breve', $p) .
				", '', " .
				champ_sql('titre', $p) .
				", '$type', substr(lire_meta('forums_publics'),0,3)), \$Cache)";
		break;

		case 'rubriques':
			$p->code = 'boutons_de_forum(' .
			champ_sql('id_rubrique', $p) .
			", '', '', '', ''," .
			champ_sql('titre', $p) .
			", '$type', substr(lire_meta('forums_publics'),0,3)), \$Cache)";
			break;

		case 'syndication':
			$p->code = "boutons_de_forum('', '', '','', " .
			champ_sql('id_rubrique', $p) . ", " .
			champ_sql('nom_site', $p) .
			", '$type', substr(lire_meta('forums_publics'),0,3)), \$Cache)";
			break;
    
		case 'articles': 
			$p->code = "boutons_de_forum('', '', " .
			champ_sql('id_article', $p) .
			", '','', " .
			champ_sql('nom_site', $p) .
			"'$type', " .
			champ_sql('accepter_forum', $p) .
			', $Cache)';
			break;

		case 'forums':
		default:
			$p->code = "boutons_de_forum(" .
			champ_sql('id_rubrique', $p) . ', ' .
			champ_sql('id_forum', $p) . ', ' .
			champ_sql('id_article', $p) . ', ' .
			champ_sql('id_breve', $p) . ', ' .
			champ_sql('id_syndic', $p) . ', ' .
			champ_sql('titre', $p) .
			", '$type', '', \$Cache)";
			break;
	}

	$p->type = 'php';
	return $p;
}

// Parametres de reponse a un forum
function balise_PARAMETRES_FORUM_dist($p) {
	$_accepter_forum = champ_sql('accepter_forum', $p);
	$p->code = '
	// refus des forums ?
	('.$_accepter_forum.'=="non" OR
	(lire_meta("forums_publics") == "non" AND '.$_accepter_forum.'!="oui"))
	? "" : // sinon:
	';


	switch ($p->type_requete) {
		case 'articles':
			$c = '"id_article=".' . champ_sql('id_article', $p);
			break;
		case 'breves':
			$c = '"id_breve=".' . champ_sql('id_breve', $p);
			break;
		case 'rubriques':
			$c = '"id_rubrique=".' . champ_sql('id_rubrique', $p);
			break;
		case 'syndication':
			$c = '"id_syndic=".' . champ_sql('id_syndic', $p);
			break;
		case 'forums':
		default:
			$liste_champs = array ("id_article","id_breve","id_rubrique","id_syndic","id_forum");
			foreach ($liste_champs as $champ) {
				$x = champ_sql( $champ, $p);
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
				champ_sql('id_article', $p) . ',' .
				champ_sql('id_breve', $p) .  ',' .
				champ_sql('id_rubrique', $p) .',' .
				champ_sql('id_syndic', $p) .  ")]=1)?'':\n";
	$p->code .= $invalide."(".$c."))";

	$p->type = 'html';
	return $p;
}

/*
# Boutons d'administration: 
*/
function balise_FORMULAIRE_ADMIN_dist($p) {
	$p->code = "'<!-- @@formulaire_admin@@45609871@@ -->'";
	$p->type = "php";
	return $p;
}

?>
