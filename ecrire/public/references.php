<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

// fonctions de recherche et de reservation
// dans l'arborescence des boucles

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Retrouver l'index de la boucle dans le cas ou une reference explicite est demandee
 * #MABALISE : l'index est celui de la premiere boucle englobante
 * #_autreboucle:MABALISE : l'index est celui de la boucle _autreboucle si elle est bien englobante
 * renvoi '' si une reference explicite incorrecte est envoyee
 *
 * Dans une balise dynamique :
 * $idb = index_boucle($p);
 *
 * @param Object $p
 * @return string
 */
function index_boucle($p){

	$idb = $p->id_boucle;
	$explicite = $p->nom_boucle;

	if (strlen($explicite)) {
		// Recherche d'un champ dans un etage superieur
	  while (($idb !== $explicite) && ($idb !=='')) {
			$idb = $p->boucles[$idb]->id_parent;
		}
	}

	return $idb;
}

/**
 * index_pile retourne la position dans la pile du champ SQL $nom_champ
 * en prenant la boucle la plus proche du sommet de pile (indique par $idb).
 * Si on ne trouve rien, on considere que ca doit provenir du contexte
 * (par l'URL ou l'include) qui a ete recopie dans Pile[0]
 * (un essai d'affinage a debouche sur un bug vicieux)
 * Si ca reference un champ SQL, on le memorise dans la structure $boucles
 * afin de construire un requete SQL minimale (plutot qu'un brutal 'SELECT *')
 *
 * http://doc.spip.org/@index_pile
 *
 * @param string $idb
 * @param string $nom_champ
 * @param Object $boucles
 * @param string $explicite
 *   indique que le nom de la boucle explicite dans la balise #_nomboucletruc:CHAMP
 * @param string $defaut
 *   code par defaut si champ pas trouve dans l'index. @$Pile[0][$nom_champ] si non fourni
 * @param bool $remonte_pile
 *   permettre de remonter la pile des boucles ou non (dans ce cas on ne cherche que ds la 1ere boucle englobante)
 * @return string
 */
function index_pile($idb, $nom_champ, &$boucles, $explicite='', $defaut=null, $remonte_pile=true) {
	if (!is_string($defaut))
		$defaut = '@$Pile[0][\''. strtolower($nom_champ) . '\']';

	$i = 0;
	if (strlen($explicite)) {
		// Recherche d'un champ dans un etage superieur
	  while (($idb !== $explicite) && ($idb !=='')) {
			#	spip_log("Cherchexpl: $nom_champ '$explicite' '$idb' '$i'");
			$i++;
			$idb = $boucles[$idb]->id_parent;
		}
	}

	#	spip_log("Cherche: $nom_champ a partir de '$idb'");
	$nom_champ = strtolower($nom_champ);
	$conditionnel = array();
	// attention: entre la boucle nommee 0, "" et le tableau vide,
	// il y a incoherences qu'il vaut mieux eviter
	while (isset($boucles[$idb])) {
		$joker = true;
		list ($t, $c) = index_tables_en_pile($idb, $nom_champ, $boucles, $joker);
		if ($t) {
			if (!in_array($t, $boucles[$idb]->select)) {
				$boucles[$idb]->select[] = $t;
			}
			$champ = '$Pile[$SP' . ($i ? "-$i" : "") . '][\'' . $c . '\']';
			if (!$joker)
				return index_compose($conditionnel,$champ);

			$conditionnel[] = "isset($champ)?$champ";
		}

		if ($remonte_pile){
			#	spip_log("On remonte vers $i");
			// Sinon on remonte d'un cran
			$idb = $boucles[$idb]->id_parent;
			$i++;
		}
		else
			$idb = null;
	}

	#	spip_log("Pas vu $nom_champ");
	// esperons qu'il y sera
	// on qu'on a fourni une valeur par "defaut" plus pertinent
	return index_compose($conditionnel,$defaut);
}

/**
 * Reconstuire la cascade de condition avec la valeur finale par defaut
 * pour les balises dont on ne saura qu'a l'execution si elles sont definies ou non
 * (boucle DATA)
 * 
 * @param array $conditionnel
 * @param string $defaut
 * @return string
 */
function index_compose($conditionnel,$defaut){
	while ($c = array_pop($conditionnel))
		// si on passe defaut = '', ne pas générer d'erreur de compilation.
		$defaut = "($c:(".($defaut?$defaut:"''")."))";
	return $defaut;
}

// http://doc.spip.org/@index_tables_en_pile
function index_tables_en_pile($idb, $nom_champ, &$boucles, &$joker) {
	global $exceptions_des_tables;

	$r = $boucles[$idb]->type_requete;

	if ($r == 'boucle') return array();
	if (!$r) {
		$joker = false; // indiquer a l'appelant
		# continuer pour chercher l'erreur suivante
		return  array("'#" . $r . ':' . $nom_champ . "'",'');
	}

	$desc = $boucles[$idb]->show;
	$excep = isset($exceptions_des_tables[$r]) ? $exceptions_des_tables[$r] : '';
	if ($excep)
		$excep = isset($excep[$nom_champ]) ? $excep[$nom_champ] : '';
	if ($excep) {
		$joker = false; // indiquer a l'appelant
	  return index_exception($boucles[$idb], $desc, $nom_champ, $excep);
	}
	else {
		if (isset($desc['field'][$nom_champ])) {
			$t = $boucles[$idb]->id_table;
			$joker = false; // indiquer a l'appelant
			return array("$t.$nom_champ", $nom_champ);
		}
		// Champ joker * des iterateurs DATA qui accepte tout
		elseif (/*$joker AND */isset($desc['field']['*'])) {
			$joker = true; // indiquer a l'appelant
			return array($nom_champ, $nom_champ);
		}
		else {
			$joker = false; // indiquer a l'appelant
		  if ($boucles[$idb]->jointures_explicites) {
		    $t = trouver_champ_exterieur($nom_champ, 
						 $boucles[$idb]->jointures,
						 $boucles[$idb]);
		    if ($t) 
					return index_exception($boucles[$idb],
					       $desc,
					       $nom_champ,
					       array($t[1]['id_table'], $nom_champ));
		  }
			return array('','');
		}
	}
}

// Reference a une entite SPIP alias d'un champ SQL
// Ca peut meme etre d'un champ dans une jointure
// qu'il faut provoquer si ce n'est fait

// http://doc.spip.org/@index_exception
function index_exception(&$boucle, $desc, $nom_champ, $excep)
{
	static $trouver_table;
	if (!$trouver_table)
		$trouver_table = charger_fonction('trouver_table', 'base');

	if (is_array($excep)) {
		// permettre aux plugins de gerer eux meme des jointures derogatoire ingerables
		$t = NULL;
		if (count($excep)==3){
			$index_exception_derogatoire = array_pop($excep);
			$t = $index_exception_derogatoire($boucle, $desc, $nom_champ, $excep);
		}
		if ($t == NULL) {
			list($e, $x) = $excep;	#PHP4 affecte de gauche a droite
			$excep = $x;		#PHP5 de droite a gauche !
			$j = $trouver_table($e, $boucle->sql_serveur);
			if (!$j) return array('','');
			$e = $j['table'];
			if (!$t = array_search($e, $boucle->from)) {
				$k = $j['key']['PRIMARY KEY'];
				if (strpos($k,',')) {
					$l = (preg_split('/\s*,\s*/', $k));
					$k = $desc['key']['PRIMARY KEY'];
					if (!in_array($k, $l)) {
						spip_log("jointure impossible $e " . join(',', $l));
						return array('','');
					}
				}
				$k = array($boucle->id_table, array($e), $k);
				fabrique_jointures($boucle, array($k));
				$t = array_search($e, $boucle->from);
			}
		}
	}
	else $t = $boucle->id_table;
	// demander a SQL de gerer le synonyme
	// ca permet que excep soit dynamique (Cedric, 2/3/06)
	if ($excep != $nom_champ) $excep .= ' AS '. $nom_champ;
	return array("$t.$excep", $nom_champ);
}

/**
 * cette fonction sert d'API pour demander le champ '$champ' dans la pile
 *
 * http://doc.spip.org/@champ_sql
 *
 * @param string $champ
 *   champ recherch�
 * @param object $p
 *   contexte de compilation
 * @param bool $defaut
 *   valeur par defaut si rien trouve dans la pile (si null, on prend le #ENV)
 * @param bool $remonte_pile
 *   permettre de remonter dans la pile des boucles pour trouver le champ
 * @return string
 */
function champ_sql($champ, $p, $defaut = null, $remonte_pile = true) {
	return index_pile($p->id_boucle, $champ, $p->boucles, $p->nom_boucle, $defaut, $remonte_pile);
}

// cette fonction sert d'API pour demander une balise Spip avec filtres

// http://doc.spip.org/@calculer_champ
function calculer_champ($p) {
	$p = calculer_balise($p->nom_champ, $p);
	return applique_filtres($p);
}

// Cette fonction sert d'API pour demander une balise SPIP sans filtres.
// Pour une balise nommmee NOM, elle demande a charger_fonction de chercher
// s'il existe une fonction balise_NOM ou balise_NOM_dist
// eventuellement en chargeant le fichier balise/NOM.php.
// Si la balise est de la forme PREFIXE_SUFFIXE (cf LOGO_* et URL_*)
// elle fait de meme avec juste le PREFIXE.
// Si pas de fonction, c'est une reference a une colonne de table SQL connue.
// Les surcharges des colonnes SQL via charger_fonction sont donc possibles.

// http://doc.spip.org/@calculer_balise
function calculer_balise($nom, $p) {

	// S'agit-t-il d'une balise_XXXX[_dist]() ?
	if ($f = charger_fonction($nom, 'balise', true)) {
		$p->balise_calculee = true;
		$res = $f($p);
		if ($res !== NULL)
			return $res;
	}

	// Certaines des balises comportant un _ sont generiques
	if ($f = strpos($nom, '_')
	AND $f = charger_fonction(substr($nom,0,$f+1), 'balise', true)) {
		$res = $f($p);
		if ($res !== NULL)
			return $res;
	}

	$f = charger_fonction('DEFAUT', 'calculer_balise');

	return $f($nom, $p);
}

function calculer_balise_DEFAUT_dist($nom, $p) {

	// ca pourrait etre un champ SQL homonyme,
	$p->code = index_pile($p->id_boucle, $nom, $p->boucles, $p->nom_boucle);

	// compatibilite: depuis qu'on accepte #BALISE{ses_args} sans [(...)] autour
	// il faut recracher {...} quand ce n'est finalement pas des args
	if ($p->fonctions AND (!$p->fonctions[0][0]) AND $p->fonctions[0][1]) {
		$code = addslashes($p->fonctions[0][1]);
		$p->code .= " . '$code'";
	}

	// ne pas passer le filtre securite sur les id_xxx
	if (strpos($nom, 'ID_') === 0)
		$p->interdire_scripts = false;

	// Compatibilite ascendante avec les couleurs html (#FEFEFE) :
	// SI le champ SQL n'est pas trouve
	// ET si la balise a une forme de couleur
	// ET s'il n'y a ni filtre ni etoile
	// ALORS retourner la couleur.
	// Ca permet si l'on veut vraiment de recuperer [(#ACCEDE*)]
	if (preg_match("/^[A-F]{1,6}$/i", $nom)
	AND !$p->etoile
	AND !$p->fonctions) {
		$p->code = "'#$nom'";
		$p->interdire_scripts = false;
	}

	return $p;
}


//
// Traduction des balises dynamiques, notamment les "formulaire_*"
// Inclusion du fichier associe a son nom, qui contient la fonction homonyme
// donnant les arguments a chercher dans la pile, et qui sont donc compiles.
// On leur adjoint les arguments explicites de la balise (cf #LOGIN{url})
// et d'eventuelles valeurs transmises d'autorite par la balise.
// (cf http://trac.rezo.net/trac/spip/ticket/1728)
// La fonction nommee ci-dessous recevra a l'execution la valeur de tout ca.

define('CODE_EXECUTER_BALISE', "executer_balise_dynamique('%s',
	array(%s%s),
	array(%s%s))");

// http://doc.spip.org/@calculer_balise_dynamique
function calculer_balise_dynamique($p, $nom, $l, $supp=array()) {

	if (!balise_distante_interdite($p)) {
		$p->code = "''";
		return $p;
	}
	// compatibilite: depuis qu'on accepte #BALISE{ses_args} sans [(...)] autour
	// il faut recracher {...} quand ce n'est finalement pas des args
	if ($p->fonctions AND (!$p->fonctions[0][0]) AND $p->fonctions[0][1]) {
		$p->fonctions = null;
	}

	if ($p->param AND ($c = $p->param[0])) {
		// liste d'arguments commence toujours par la chaine vide
		array_shift($c);
		// construire la liste d'arguments comme pour un filtre
		$param = compose_filtres_args($p, $c, ',');
	} else	$param = "";
	$collecte = collecter_balise_dynamique($l, $p, $nom);

	$p->code = sprintf(CODE_EXECUTER_BALISE, $nom,
		join(',', $collecte),
		($collecte ? $param : substr($param,1)), # virer la virgule
		memoriser_contexte_compil($p),
		(!$supp ? '' : (', ' . join(',', $supp))));

	$p->interdire_scripts = false;
	return $p;
}

// Construction du tableau des arguments d'une balise dynamique.
// Ces arguments peuvent etre eux-meme des balises (cf FORMULAIRE_SIGNATURE)
// mais gare au bouclage (on peut s'aider de $nom pour le reperer au besoin)
// En revanche ils n'ont pas de filtres, donc on appelle calculer_balise qui
// ne s'occupe pas de ce qu'il y a dans $p (mais qui va y ecrire le code)

// http://doc.spip.org/@collecter_balise_dynamique
function collecter_balise_dynamique($l, &$p, $nom) {
	$args = array();
	foreach($l as $c) { $x = calculer_balise($c, $p); $args[] = $x->code;}
	return $args;
}




/**
 * Récuperer le nom du serveur
 * 
 * Mais pas si c'est un serveur specifique derogatoire
 * 
 * @param Champ $p
 *     AST positionné sur la balise
 * @return string
 *     Nom de la connexion
**/
function trouver_nom_serveur_distant($p) {
	$nom = $p->id_boucle;
	if ($nom
		AND isset($p->boucles[$nom])) {
		$s = $p->boucles[$nom]->sql_serveur;
		if (strlen($s)
			AND strlen($serveur = strtolower($s))
			AND !in_array($serveur,$GLOBALS['exception_des_connect'])) {
				return $serveur;
		}
	}
	return "";
}


/**
 * Teste si une balise est appliquée sur une base distante
 *
 * La fonction loge une erreur si la balise est utilisée sur une
 * base distante et retourne false dans ce cas.
 * 
 * Note :
 * Il faudrait savoir traiter les formulaires en local
 * tout en appelant le serveur SQL distant.
 * En attendant, cette fonction permet de refuser une authentification
 * sur qqch qui n'a rien a voir.
 * 
 * @param Champ $p
 *     AST positionné sur la balise
 * @return bool
 *     - true : La balise est autorisée
 *     - false : La balise est interdite car le serveur est distant
**/
function balise_distante_interdite($p) {
	$nom = $p->id_boucle;

	if ($nom AND trouver_nom_serveur_distant($p)) {
		spip_log( $nom .':' . $p->nom_champ .' '._T('zbug_distant_interdit'));
		return false;
	}
	return true;
}


//
// Traitements standard de divers champs
// definis par $table_des_traitements, cf. ecrire/public/interfaces
//
// http://doc.spip.org/@champs_traitements
function champs_traitements ($p) {
	global $table_des_traitements;

	if (isset($table_des_traitements[$p->nom_champ]))
		$ps = $table_des_traitements[$p->nom_champ];
	else {
		// quand on utilise un traitement catch-all *
		// celui-ci ne s'applique pas sur les balises calculees qui peuvent gerer
		// leur propre securite
		if (!$p->balise_calculee)
			$ps = $table_des_traitements['*'];
		else
			$ps = false;
	}

	if (is_array($ps)) {
		// Recuperer le type de boucle (articles, DATA) et la table SQL sur laquelle elle porte
		$idb = index_boucle($p);
		// mais on peut aussi etre hors boucle. Se mefier.
		$type_requete = isset($p->boucles[$idb]->type_requete) ? $p->boucles[$idb]->type_requete : false;
		$table_sql = isset($p->boucles[$idb]->show['table_sql'])?$p->boucles[$idb]->show['table_sql']:false;

		// le traitement peut n'etre defini que pour une table en particulier "spip_articles"
		if ($table_sql AND isset($ps[$table_sql]))
			$ps = $ps[$table_sql];
		// ou pour une boucle en particulier "DATA","articles"
		elseif ($type_requete AND isset($ps[$type_requete]))
			$ps = $ps[$type_requete];
		// ou pour indiferrement quelle que soit la boucle
		elseif(isset($ps[0]))
			$ps = $ps[0];
		else
			$ps=false;
	}

	if (!$ps) return $p->code;

	// Si une boucle DOCUMENTS{doublons} est presente dans le squelette,
	// ou si in INCLURE contient {doublons}
	// on insere une fonction de remplissage du tableau des doublons 
	// dans les filtres propre() ou typo()
	// (qui traitent les raccourcis <docXX> referencant les docs)

	if (isset($p->descr['documents']) 
	AND 
	  $p->descr['documents']
	AND (
		(strpos($ps,'propre') !== false)
		OR
		(strpos($ps,'typo') !== false)
	))
		$ps = 'traiter_doublons_documents($doublons, '.$ps.')';

	// La protection des champs par |safehtml est assuree par les extensions
	// dans la declaration des traitements des champs sensibles

	// Remplacer enfin le placeholder %s par le vrai code de la balise
	return str_replace('%s', $p->code, $ps);
}


//
// Appliquer les filtres a un champ [(#CHAMP|filtre1|filtre2)]
// retourne un code php compile exprimant ce champ filtre et securise
//  - une etoile => pas de processeurs standards
//  - deux etoiles => pas de securite non plus !
//
// http://doc.spip.org/@applique_filtres
function applique_filtres($p) {

	// Traitements standards (cf. supra)
	if ($p->etoile == '')
		$code = champs_traitements($p);
	else
		$code = $p->code;

	// Appliquer les filtres perso
	if ($p->param)
		$code = compose_filtres($p, $code);

	// S'il y a un lien avec la session, ajouter un code qui levera
	// un drapeau dans la structure d'invalidation $Cache
	if (isset($p->descr['session']))
		$code = "invalideur_session(\$Cache, $code)";

	$code = sandbox_composer_interdire_scripts($code, $p);
	return $code;
}

// Cf. function pipeline dans ecrire/inc_utils.php
// http://doc.spip.org/@compose_filtres
function compose_filtres(&$p, $code) {

	$image_miette = false;
	foreach($p->param as $filtre) {
		$fonc = array_shift($filtre);
		if (!$fonc) continue; // normalement qu'au premier tour.
		$is_filtre_image = ((substr($fonc,0,6)=='image_') AND $fonc!='image_graver');
		if ($image_miette AND !$is_filtre_image){
	// il faut graver maintenant car apres le filtre en cours
	// on est pas sur d'avoir encore le nom du fichier dans le pipe
			$code = "filtrer('image_graver', $code)";
			$image_miette = false;
		}
		// recuperer les arguments du filtre, 
		// a separer par "," ou ":" dans le cas du filtre "?{a,b}"
		if ($fonc !== '?') {
			$sep = ',';
		} else {$sep = ':';
			// |?{a,b} *doit* avoir exactement 2 arguments ; on les force
			if (count($filtre) != 2)
				$filtre = array(isset($filtre[0])?$filtre[0]:"", isset($filtre[1])?$filtre[1]:"");
		}
		$arglist = compose_filtres_args($p, $filtre, $sep);
		$logique = filtre_logique($fonc, $code, substr($arglist,1));
		if ($logique)
			$code = $logique;
		else {
			$code = sandbox_composer_filtre($fonc,$code,$arglist,$p);
			if ($is_filtre_image) $image_miette = true;
		}
	}
	// ramasser les images intermediaires inutiles et graver l'image finale
	if ($image_miette)
		$code = "filtrer('image_graver',$code)";

	return $code;
}

// Filtres et,ou,oui,non,sinon,xou,xor,and,or,not,yes
// et comparateurs
function filtre_logique($fonc, $code, $arg)
{
	global $table_criteres_infixes;
	switch (true) {
		case in_array($fonc, $table_criteres_infixes):
			return "($code $fonc $arg)";
		case ($fonc == 'and') OR ($fonc == 'et'):
			return "((($code) AND ($arg)) ?' ' :'')";
		case ($fonc == 'or') OR ($fonc == 'ou'):
			return "((($code) OR ($arg)) ?' ' :'')";
		case ($fonc == 'xor') OR ($fonc == 'xou'):
			return "((($code) XOR ($arg)) ?' ' :'')";
		case ($fonc == 'sinon'):
			return "(((\$a = $code) OR (is_string(\$a) AND strlen(\$a))) ? \$a : $arg)";
		case ($fonc == 'not') OR ($fonc == 'non'):
			return "(($code) ?'' :' ')";
		case ($fonc == 'yes') OR ($fonc == 'oui'):
			return "(($code) ?' ' :'')";
	}
	return '';
}

// http://doc.spip.org/@compose_filtres_args
function compose_filtres_args($p, $args, $sep)
{
	$arglist = "";
	foreach ($args as $arg) {
		$arglist .= $sep . 
		  calculer_liste($arg, $p->descr, $p->boucles, $p->id_boucle);
	}
	return $arglist;
}

//
// Reserve les champs necessaires a la comparaison avec le contexte donne par
// la boucle parente ; attention en recursif il faut les reserver chez soi-meme
// ET chez sa maman
// 
// http://doc.spip.org/@calculer_argument_precedent
function calculer_argument_precedent($idb, $nom_champ, &$boucles, $defaut=null) {

	// si recursif, forcer l'extraction du champ SQL mais ignorer le code
	if ($boucles[$idb]->externe) {
		index_pile ($idb, $nom_champ, $boucles,'', $defaut);
		// retourner $Pile[$SP] et pas $Pile[0] si recursion en 1ere boucle
		// on ignore le defaut fourni dans ce cas
		$defaut = "@\$Pile[\$SP]['$nom_champ']";
	}

	return index_pile($boucles[$idb]->id_parent, $nom_champ, $boucles,'', $defaut);
}

//
// Rechercher dans la pile des boucles actives celle ayant un critere
// comportant un certain $motif, et construire alors une reference
// a l'environnement de cette boucle, qu'on indexe avec $champ.
// Sert a referencer une cellule non declaree dans la table et pourtant la.
// Par exemple pour la balise #POINTS on produit $Pile[$SP-n]['points']
// si la n-ieme boucle a un critere "recherche", car on sait qu'il a produit
// "SELECT XXXX AS points"
//

// http://doc.spip.org/@rindex_pile
function rindex_pile($p, $champ, $motif) 
{
	$n = 0;
	$b = $p->id_boucle;
	$p->code = '';
	while ($b != '') {
		foreach($p->boucles[$b]->criteres as $critere) {
			if ($critere->op == $motif) {
				$p->code = '$Pile[$SP' . (($n==0) ? "" : "-$n") .
					"]['$champ']";
				$b = '';
				break 2;
			}
		}
		$n++;
		$b = $p->boucles[$b]->id_parent;
	}

	// si on est hors d'une boucle de {recherche}, cette balise est vide
	if (!$p->code)
		$p->code = "''";

	$p->interdire_scripts = false;
	return $p;
}

?>
