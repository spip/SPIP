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
include_local("inc-forum.php3");
include_local("inc-calcul-outils.php3");

#include_local("inc-calcul_html");	# anciens noms des fichiers
#include_local("inc-calcul_mysql");


// Ce fichier peut contenir une affectation de $dossier_squelettes  indiquant
// le repertoire du source des squelettes (les pseudo-html avec <BOUCLE...)

if (@file_exists("mes_fonctions.php3")) 
    include_local ("mes_fonctions.php3");


// Gestionnaire d'URLs
if (@file_exists("inc-urls.php3")) { include_local ("inc-urls.php3"); }
else { include_local ("inc-urls-dist.php3"); }


// Le squelette compile est-il trop vieux ?
function squelette_obsolete($skel, $squelette) {
	return (
		($GLOBALS['recalcul'] == 'oui')
		OR !@file_exists($skel)
		OR (@filemtime($squelette) > ($date = @filemtime($skel)))
		OR (@filemtime('mes_fonctions.php3') > $date)
		OR (@filemtime('ecrire/mes_options.php3') > $date)
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
		if (!squelette_obsolete($phpfile, $sourcefile)
		AND lire_fichier ($phpfile, $contenu,
		array('critique' => 'oui', 'phpcheck' => 'oui'))) {
			eval('?'.'>'.$contenu);
			if (function_exists($nom))
				return $nom;
		}

		// sinon le compiler
		include_local("inc-compilo.php3");
		if (!lire_fichier ($sourcefile, $skel)) { 
			// erreur webmaster : $fond ne correspond a rien
			include_ecrire ("inc_presentation.php3");
			install_debut_html(_T('info_erreur_squelette'));
			echo "<P>"._T('info_erreur_squelette2',
			array('fichier'=>$squelette))."</P>";
			spip_log ("ERREUR: aucun squelette '$squelette' n'est disponible...");
			install_fin_html();
			exit;
		}

		$skel_compile = "<"."?php\n"
		. calculer_squelette($skel, $nom, $ext, $sourcefile)."\n?".">";

		// Envoyer le debugguer
		afficher_page_si_demande_admin ('skel', $skel_compile, _L('Fond : ').$sourcefile." ; fichier produit : ".$phpfile);
		// Evaluer le squelette
		eval('?'.'>'.$skel_compile);

		if (function_exists($nom)) {
			ecrire_fichier ($phpfile, $skel_compile);
			return $nom;
		}
		else {
			// en cas d'erreur afficher les boutons de debug
			echo "<hr /><h2>".
			_L("Erreur dans la compilation du squelette")." $sourcefile</h2>";
			$GLOBALS['bouton_admin_debug'] = true;
			$GLOBALS['var_afficher_debug'] = 'skel';
			afficher_page_si_demande_admin ('skel', $skel_compile, _L('Fond : ').$sourcefile." ; fichier produit : ".$phpfile);
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
	global $dossier_squelettes, $delais;

	/* Bonne idee mais plus tard ?
	$dir = "$dossier_squelettes/mon-chercher.php3";
	if (file_exists($dir)) {
		include($dir);
	} else { */
		include_local("inc-chercher.php3"); # a renommer
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
	$page =  $fonc(array('cache' => $cache), array($contexte));

	// Memoriser le nom du squelette utilise (pour le debuggueur)
	$page['squelette'] = $skel;

	// Nettoyer le resultat si on est fou de XML
	if ($GLOBALS['xhtml']) {
		include_ecrire("inc_tidy.php");
		$page['texte'] = xhtml($page['texte']);
	}

	// Entrer les invalideurs dans la base
	if ($delais>0) {
		include_ecrire('inc_invalideur.php3');
		maj_invalideurs($cache, $page['invalideurs'], $delais);
	}

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
		$contexte['date'] = $contexte['date_redac'] = normaliser_date($GLOBALS['date']);
	else
		$contexte['date'] = $contexte['date_redac'] = date("Y-m-d H:i:s");

	return $contexte;
}

function calculer_page_globale($cache, $contexte_local, $fond) {
	global $spip_lang;

	// Gestion des URLs personnalises - sale mais historique
	if (function_exists("recuperer_parametres_url")) {
		global $contexte;
		$contexte = $contexte_local;
		recuperer_parametres_url($fond, nettoyer_uri());

		// remettre les globales pour le bouton "Modifier cet article"
		if (is_array($contexte))
			foreach ($contexte as $var=>$val)
				if (substr($var,0,3) == 'id_')
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

	$signal = array();
	foreach(array('id_parent', 'id_rubrique', 'id_article', 'id_auteur',
	'id_breve', 'id_forum', 'id_secteur', 'id_syndic', 'id_syndic_article',
	'id_mot', 'id_groupe', 'id_document') as $val) {
		if ($contexte_local[$val])
			$signal['contexte'][$val] = intval($contexte_local[$val]);
	}

	$page['signal'] = $signal;

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
			$elements['fond']);
	}

	$page['signal']['process_ins'] = $page['process_ins'];
	$signal = "<!-- ".str_replace("\n", " ",
	serialize($page['signal']))." -->\n";

	// Enregistrer le fichier cache
	if ($delais > 0 AND empty($GLOBALS['HTTP_POST_VARS']))
		ecrire_fichier($chemin_cache, $signal.$page['texte']);

	return $page;
}





// Fonction appelee par le skel pour assembler les balises
function _f($push = false, $texte='') {
	static $pile_f = array();

	// push et teste si nul
	if ($push) {
		array_push($pile_f, $texte);
		if ($texte)
			return true;
	}
	// ou pop
	// Attention "return array_pop($pile_f)" provoquerait
	// l'affichage du chiffre zero pour, par exemple, #TOTAL_BOUCLE
	else
		if ($texte = array_pop($pile_f))
			return $texte;
}

### A passer peut-etre dans inc_db_mysql
// Cette fonction est systematiquement appelee par les squelettes
// pour constuire une requete SQL de type "lecture" (SELECT) a partir
// de chaque boucle.
// Elle construit et exe'cute une reque^te SQL correspondant a` une balise
// Boucle ; elle notifie une erreur SQL dans le flux de sortie et termine
// le processus.
// Sinon, retourne la ressource interrogeable par fetch_row ou fetch_array.
// Elle peut etre re'de'finie pour s'interfacer avec d'autres serveurs SQL
// Recoit en argument:
// - le tableau des champs a` ramener
// - le tableau des tables a` consulter
// - le tableau des conditions a` remplir
// - le crite`re de regroupement
// - le crite`re de classement
// - le crite`re de limite
// - une sous-requete e'ventuelle (MySQL > 4.1)
// - un compteur de sous-requete
// - le nom de la table
// - le nom de la boucle (pour le message d'erreur e'ventuel)


function spip_abstract_select (
	$select = array(), $from = array(), $where = '',
	$groupby = '', $orderby = '', $limit = '',
	$sousrequete = '', $cpt = '',
	$table = '', $id = '') {

	$DB = 'spip_';
	$q = " FROM $DB" . join(", $DB", $from)
	. (is_array($where) ? ' WHERE ' . join(' AND ', $where) : '')
	. ($groupby ? " GROUP BY $groupby" : '')
	. ($orderby ? "\nORDER BY $orderby" : '')
	. ($limit ? "\nLIMIT $limit" : '');

	if (!$sousrequete)
		$q = " SELECT ". join(", ", $select) . $q;
	else
		$q = " SELECT S_" . join(", S_", $select)
		. " FROM (" . join(", ", $select)
		. ", COUNT(".$sousrequete.") AS compteur " . $q
		.") AS S_$table WHERE compteur=" . $cpt;

	//
	// Erreur ? C'est du debug, ou une erreur du serveur
	//
	if (!($result = @spip_query($q))) {
		include_local('inc-admin.php3');
		echo erreur_requete_boucle($q, $id, $table);
	}

	#  spip_log(spip_num_rows($result));
	return $result;
}

?>
