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

//
// Formulaires de gestion de forums : les balises sont definies
// dans le fichier inc-forum.php3 qui centralise toute la gestion des forums
//
// Formulaire de reponse a un forum
#	function balise_FORMULAIRE_FORUM_dist($p) {}
// Parametres de reponse a un forum
#	function balise_PARAMETRES_FORUM_dist($p) {}
include_local('inc-forum.php3');


//
// Boutons d'administration: 
//
function balise_FORMULAIRE_ADMIN_dist($p) {
	$p->code = "'<!-- @@formulaire_admin@@45609871@@ -->'";
	$p->type = "php";
	return $p;
}

?>
