<?php


// Ce fichier ne sera execute qu'une fois
if (defined("_INC_CALCUL")) return;
define("_INC_CALCUL", "1");

//
// Ce fichier calcule une page en executant un squelette.
//

include_ecrire("inc_index.php3");
include_ecrire("inc_texte.php3");
include_ecrire("inc_filtres.php3");
include_ecrire("inc_lang.php3");
include_ecrire("inc_documents.php3");
include_local("inc-calcul_mysql3.php");
include_local("inc-calcul_html4.php");


// Ce fichier peut contenir une affectation de $dossier_squelettes  indiquant
// le repertoire du source des squelettes (les pseudo-html avec <BOUCLE...)

if (file_exists("mes_fonctions.php3")) 
    include_local ("mes_fonctions.php3");


// Gestionnaire d'URLs
if (@file_exists("inc-urls.php3")) { include_local ("inc-urls.php3"); }
else { include_local ("inc-urls-dist.php3"); }


// Le squelette compile est-il trop vieux ?
function squelette_obsolete($skel) {
	return (
		($GLOBALS['recalcul'] == 'oui')
		OR !@file_exists($skel)
		OR (@filemtime('mes_fonctions.php3') > @filemtime($skel))
		OR (@filemtime('ecrire/mes_options.php3') > @filemtime($skel))
	);
}


// Charge un squelette (en demande au besoin la compilation)
function charger_squelette ($squelette) {
	$ext = $GLOBALS['extension_squelette'];
	$nom = $ext . '_' . md5($squelette);
	$sourcefile = $squelette . ".$ext";

	if (function_exists($nom)) {
		#spip_log("Squelette $squelette:\t($nom) deja en memoire");
		return $nom;
	}
	else {
		$phpfile = 'CACHE/skel_' . $nom . '.php';

		// le squelette est-il deja compile, lisible, etc ?
		if (!squelette_obsolete($sourcefile)
		AND lire_fichier ($phpfile, $contenu,
		array('critique' => 'oui', 'phpcheck' => 'oui'))) {
			eval('?'.'>'.$contenu);
			if (function_exists($nom))
				return $nom;
		}

		// sinon le compiler
		include_local("inc-calcul-squel.php3");
		if (!lire_fichier ($sourcefile, $skel)) { 
			// erreur webmaster : $fond ne correspond a rien
			include_ecrire ("inc_presentation.php3");
			install_debut_html(_T('info_erreur_squelette'));
			echo "<P>"._T('info_erreur_squelette2',
			array('fichier'=>$GLOBALS['fond']))."</P>";
			install_fin_html();
			spip_log ("ERREUR: aucun squelette $squelette n'est disponible...");
			exit;
		}

		$skel_compile = "<"."?php\n"
		. calculer_squelette($skel, $nom, $ext)."\n?".">";
		eval('?'.'>'.$skel_compile);

		if (function_exists($nom)) {
			ecrire_fichier ($phpfile, $skel_compile);
			return $nom;
		} else {
			echo ("<h1>Horreur, squelette pas compile !</h1>");
			echo $skel_compile;
			exit;
		}
	}
}


# Provoque la recherche du squelette $fond d'une $lang donnee,
# et l'applique sur un $contexte pour un certain $cache.
# Retourne un tableau de 3 elements:
# 'texte' => la page calculee
# 'process_ins' => 'html' ou 'php' si presence d'un '< ?php'
# 'invalideurs' => les invalideurs (cf inc-calcul-squel)

# En cas d'erreur process_ins est absent et texte est un tableau de 2 chaines

# La recherche est assuree par la fonction cherche_squelette
# definie dans inc-chercher, fichier non charge s'il existe un fichier
# mon-chercher dans $dossier_squelettes ou dans le rep principal de Spip,
# pour charger une autre definition de cette fonction.

# L'execution est precedee du chargement eventuel d'un fichier homonyme
# de celui du squelette mais d'extension .php  pouvant contenir:
# - des filtres
# - des fonctions de traduction de balise (cf inc-index-squel)

function cherche_page ($cache, $contexte, $fond, $id_rubrique, $lang='')  {
	global $dossier_squelettes;

	/* Bonne idee mais plus tard ?
	$dir = "$dossier_squelettes/mon-chercher.php3";
	if (file_exists($dir)) {
		include($dir);
	} else { */
		include_local("inc-chercher.php3");
	/* }
	*/

	// Choisir entre $fond-dist.html, $fond=7.html, etc?
	$skel = chercher_squelette($fond,
		$id_rubrique,
		$dossier_squelettes ? "$dossier_squelettes/" :'',
		$lang
	);

	/*  Idem
	$dir = "$skel" . '_fonctions.php3';
	if (file_exists($dir)) include($dir);
	*/

	// Charger le squelette demande et recuperer sa fonction main()
	// (on va le compiler si besoin est)
	$fonc = charger_squelette($skel);

	// Calculer la page a partir du main() du skel compile
	$page =  $fonc(array('cache' =>$cache),
		array($contexte),
		array(
			'articles' => '0',
			'rubriques' => '0',
			'breves' => '0',
			'auteurs' => '0',
			'forums' => '0',
			'signatures' => '0',
			'mots' => '0',
			'groupes_mots' => '0',
			'syndication' => '0',
			'documents' => '0'
		)
	);

	// Nettoyer le resultat si on est fou de XML
	if ($GLOBALS['xhtml']) {
		include_ecrire("inc_tidy.php");
		$page['texte'] = xhtml($page['texte']);
	}

	// Entrer les invalideurs dans la base
	include_ecrire('inc_invalideur.php3');
	maj_invalideurs($cache, $page['invalideurs']);

	// Retourner la structure de la page
	return $page;
}

// Etablit le contexte initial a partir des globales
function calculer_contexte() {
	foreach($GLOBALS['HTTP_GET_VARS'] as $var => $val) {
		if (!eregi("^(recalcul|submit|var_.*)$", $var))
			$contexte[$var] = $val;
	}
	foreach($GLOBALS['HTTP_POST_VARS'] as $var => $val) {
		if (!eregi("^(recalcul|submit|var_.*)$", $var))
			$contexte[$var] = $val;
	}

	if ($GLOBALS['date'])
		$contexte['date'] = $contexte['date_redac'] = date($GLOBALS['date']);
	else
		$contexte['date'] = $contexte['date_redac'] = date("Y-m-d H:i:s");

	return $contexte;
}

function calculer_page_globale($cache, $contexte_local, $fond, $var_recherche) {
	global $spip_lang;

	// Gestion des URLs personnalises - sale mais historique
	if (function_exists("recuperer_parametres_url")) {
		global $contexte;
		$contexte = $contexte_local;
		recuperer_parametres_url($fond, nettoyer_uri());
		if (is_array($contexte))
			foreach ($contexte as $var=>$val)
				$GLOBALS[$var] = $val;
		$contexte_local = $contexte;
	}

	$id_rubrique_fond = 0;

	// Si inc-urls veut fixer la langue, se baser ici
	$lang = $contexte_local['lang'];

	// Chercher le fond qui va servir de squelette
	if ($r = sql_rubrique_fond($contexte_local,
	$lang ? $lang : lire_meta('langue_site')))
		list($id_rubrique_fond, $lang) = $r;

	if (!$GLOBALS['forcer_lang'])
		lang_select($lang);

	// Go to work !
	$page = cherche_page($cache, $contexte_local, $fond, $id_rubrique_fond, $spip_lang);

	// Surligne
	if ($var_recherche) {
		include_ecrire("inc_surligne.php3");
		$page['texte'] = surligner_mots($page['texte'], $var_recherche);
	} 

	$signal = array();
	foreach(array('id_parent', 'id_rubrique', 'id_article', 'id_auteur',
	'id_breve', 'id_forum', 'id_secteur', 'id_syndic', 'id_syndic_article',
	'id_mot', 'id_groupe', 'id_document') as $val) {
		if ($contexte_local[$val])
			$signal['contexte'][$val] = intval($contexte_local[$val]);
	}
	$signal['process_ins'] = $page['process_ins'];

# ne marchera qu'avec les inclusions 'html' (versus 'php')
#	$signal['fraicheur'] = $page['fraicheur'];

	$signal = "<!-- ".str_replace("\n", " ", serialize($signal))." -->\n";

	$page['texte'] = $signal.$page['texte'];

	return $page;
}


// Cf ramener_page +cherche_page_incluante+ cherche_page_incluse chez ESJ
function calculer_page($chemin_cache, $elements, $delais, $inclusion=false) {
	include_local('inc-calcul.php3');

	// Inclusion
	if ($inclusion) {
		$contexte_inclus = $elements['contexte'];
		$page = cherche_page($chemin_cache,
			$contexte_inclus,
			$elements['fond'], 
			$contexte_inclus['id_rubrique']
		);
	}
	else {

		// Page globale
		// si le champ chapo commence par '=' c'est une redirection.
		if ($id_article = intval($GLOBALS['id_article'])) {
			$page = sql_chapo($id_article);
			if ($page) {
				$page = $page['chapo'];
				if (substr($page, 0, 1) == '=') {
					include_ecrire('inc_texte.php3');
					list(,$page) = extraire_lien(array('','','',
					substr($page, 1)));
					if ($page) { // sinon les navigateurs pataugent
						$page = addslashes($page);
						return array('texte' =>
						("<". "?php header(\"Location: $page\"); ?" . ">"),
						'process_ins' => 'php');
					}
				}
			}
		}
		$page = calculer_page_globale($chemin_cache,
			$elements['contexte'],
			$elements['fond'],
			$elements['var_recherche']);
	}

	// Enregistrer le fichier cache
	if ($delais>0)
		ecrire_fichier($chemin_cache, $page['texte']);

	return $page;
}



# Fonctions appelees par les squelettes (insertion dans le code trop lourde)

tester_variable('espace_logos',3);  // HSPACE=xxx VSPACE=xxx pour les logos (#LOGO_ARTICLE)
tester_variable('espace_images',3);  // HSPACE=xxx VSPACE=xxx pour les images integrees

//
// Retrouver le logo d'un objet (et son survol)
//

function cherche_image($id_objet, $type_objet, $flag_fichier) {
	// cherche l'image liee a l'objet
	$on = cherche_image_nommee($type_objet.'on'.$id_objet);

	// cherche un survol
	$off =(!$on ? '' :
	cherche_image_nommee($type_objet.'off'.$id_objet));

	return (!$flag_fichier ? 
		array($on, $off) :
		array(ereg_replace("^[^\/]+/", '', $on),
		      ereg_replace("^[^\/]+/", '', $off)));
}

function image_article($id_article, $dossier){
	return cherche_image($id_article,'art', $dossier);
}

function image_auteur($id_auteur, $dossier){
	return cherche_image($id_auteur,'aut', $dossier);
}

function image_breve($id_breve, $dossier){
	return cherche_image($id_breve,'breve', $dossier);
}

function image_site($id_syndic, $dossier){
	return cherche_image($id_syndic,'site', $dossier);
}

function image_mot($id_mot, $dossier){
	return cherche_image($id_mot,'mot', $dossier);
}

function image_rubrique($id_rubrique, $dossier) {
	// Recherche recursive vers les rubriques parentes (y compris racine)
	while ($id_rubrique) {
		$image = cherche_image($id_rubrique, 'rub', $dossier);
		if ($image[0]) return $image;
		$id_rubrique = sql_parent($id_rubrique);
	}
	return '';
}
?>
