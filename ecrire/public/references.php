<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

// fonctions de recherche et de reservation
// dans l'arborescence des boucles

if (!defined('_ECRIRE_INC_VERSION')) return;

// index_pile retourne la position dans la pile du champ SQL $nom_champ 
// en prenant la boucle la plus proche du sommet de pile (indique par $idb).
// Si on ne trouve rien, on considere que ca doit provenir du contexte 
// (par l'URL ou l'include) qui a ete recopie dans Pile[0]
// (un essai d'affinage a debouche sur un bug vicieux)
// Si ca reference un champ SQL, on le memorise dans la structure $boucles
// afin de construire un requete SQL minimale (plutot qu'un brutal 'SELECT *')

// http://doc.spip.org/@index_pile
function index_pile($idb, $nom_champ, &$boucles, $explicite='') {

	$i = 0;
	if (strlen($explicite)) {
	// Recherche d'un champ dans un etage superieur
	  while (($idb !== $explicite) && ($idb !=='')) {
#		spip_log("Cherchexpl: $nom_champ '$explicite' '$idb' '$i'");
			$i++;
			$idb = $boucles[$idb]->id_parent;
		}
	}

#	spip_log("Cherche: $nom_champ a partir de '$idb'");
	$nom_champ = strtolower($nom_champ);
	// attention: entre la boucle nommee 0, "" et le tableau vide,
	// il y a incoherences qu'il vaut mieux eviter
	while (isset($boucles[$idb])) {
		list ($t, $c) = index_tables_en_pile($idb, $nom_champ, $boucles);

		if ($t) {
		  if (!in_array($t, $boucles[$idb]->select)) {
		    $boucles[$idb]->select[] = $t;
		  }
		  return '$Pile[$SP' . ($i ? "-$i" : "") . '][\'' . $c . '\']';
		}
#		spip_log("On remonte vers $i");
		// Sinon on remonte d'un cran
		$idb = $boucles[$idb]->id_parent;
		$i++;
	}

#	spip_log("Pas vu $nom_champ");
	// esperons qu'il y sera
	return('@$Pile[0][\''. strtolower($nom_champ) . '\']');
}

// http://doc.spip.org/@index_tables_en_pile
function index_tables_en_pile($idb, $nom_champ, &$boucles) {
	global $exceptions_des_tables;

	$r = $boucles[$idb]->type_requete;

	if ($r == 'boucle') return array();
	if (!$r) {
		# continuer pour chercher l'erreur suivante
		return  array("'#" . $r . ':' . $nom_champ . "'",'');
	}

	$desc = $boucles[$idb]->show;
	$excep = isset($exceptions_des_tables[$r]) ? $exceptions_des_tables[$r] : '';
	if ($excep)
		$excep = isset($excep[$nom_champ]) ? $excep[$nom_champ] : '';
	if ($excep) {
	  return index_exception($boucles[$idb], $desc, $nom_champ, $excep);
	} else {
		if (isset($desc['field'][$nom_champ])) {
			$t = $boucles[$idb]->id_table;
			return array("$t.$nom_champ", $nom_champ);
		} else {
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


// cette fonction sert d'API pour demander le champ '$champ' dans la pile
// http://doc.spip.org/@champ_sql
function champ_sql($champ, $p) {
	return index_pile($p->id_boucle, $champ, $p->boucles, $p->nom_boucle);
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


// il faudrait savoir traiter les formulaires en local
// tout en appelant le serveur SQL distant.
// En attendant, cette fonction permet de refuser une authentification
// sur qqch qui n'a rien a voir.

// http://doc.spip.org/@balise_distante_interdite
function balise_distante_interdite($p) {
	$nom = $p->id_boucle;

	if ($nom 
		AND $p->boucles[$nom]->sql_serveur
		AND !in_array($p->boucles[$nom]->sql_serveur,$GLOBALS['exception_des_connect'])) {
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

	if (!isset($table_des_traitements[$p->nom_champ]))
		return $p->code;
	$ps = $table_des_traitements[$p->nom_champ];
	if (is_array($ps)) {
	  // new style

		if ($p->nom_boucle)
			$type = $p->boucles[$p->nom_boucle]->type_requete;
		else
			$type = $p->type_requete;
		// le traitement peut n'etre defini que pour une table en particulier
		if (isset($ps[$type]))
			$ps = $ps[$type];
		elseif(isset($ps[0]))
			$ps = $ps[0];
		else $ps=false;
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

	// Passer |safehtml sur les boucles "sensibles"
	// sauf sur les champs dont on est surs
	// ces exceptions doivent etre ventilees dans les plugins fonctionnels concernes
	// dans la globale table_des_traitements
	switch ($p->type_requete) {
		case 'signatures':
		case 'syndic_articles':
			$champs_surs = array(
			'date', 'date_heure', 'statut', 'ip', 'url_article', 'maj', 'idx'
			);
			if (!in_array(strtolower($p->nom_champ), $champs_surs)
			AND !preg_match(',^ID_,', $p->nom_champ))
				$ps = 'safehtml('.$ps.')';
			break;
		default:
			break;
	}

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

	// Securite
	if ($p->interdire_scripts
	AND $p->etoile != '**') {
		if (!preg_match("/^sinon[(](.*),'([^']*)'[)]$/", $code, $r))
			$code = "interdire_scripts($code)";
		else {
		  $code = interdire_scripts($r[2]);
		  $code = "sinon(interdire_scripts($r[1]),'$code')";
		}
	}
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
			if (isset($GLOBALS['spip_matrice'][$fonc])) {
				$code = "filtrer('$fonc',$code$arglist)";
				if ($is_filtre_image) $image_miette = true;
			}

			// le filtre est defini sous forme de fonction ou de methode
			// par ex. dans inc_texte, inc_filtres ou mes_fonctions
			elseif ($f = chercher_filtre($fonc)) {
				$code = "$f($code$arglist)";
			}
			// le filtre n'existe pas,
			// on le notifie
			else erreur_squelette(array('zbug_erreur_filtre', array('filtre'=>  texte_script($fonc))), $p);
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
			return "(((\$a = $code) OR (!is_array(\$a) AND strlen(\$a))) ? \$a : $arg)";
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
function calculer_argument_precedent($idb, $nom_champ, &$boucles) {

	// si recursif, forcer l'extraction du champ SQL mais ignorer le code
	if ($boucles[$idb]->externe) {
		index_pile ($idb, $nom_champ, $boucles); 
		$zero = '$SP';
	} else $zero = '0';
	// retourner $Pile[$SP] et pas $Pile[0] si recursion en 1ere boucle
	$prec = $boucles[$idb]->id_parent;
	return (($prec === '')
		? ('$Pile[' . $zero . "]['$nom_champ']") 
		: index_pile($prec, $nom_champ, $boucles));
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
