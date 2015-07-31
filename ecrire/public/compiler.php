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


//
// Fichier principal du compilateur de squelettes
//

if (!defined("_ECRIRE_INC_VERSION")) return;

// reperer un code ne calculant rien, meme avec commentaire
define('CODE_MONOTONE', "^(\n//[^\n]*\n)?\(?'([^'])*'\)?$");

// Definition de la structure $p, et fonctions de recherche et de reservation
// dans l'arborescence des boucles
include_spip('public/references');

// definition des boucles
include_spip('public/boucles');

// definition des criteres
include_spip('public/criteres');

// definition des balises
include_spip('public/balises');

// definition de l'API
include_spip('public/interfaces');

# definition des tables
include_spip('base/serial');

//
// Calculer un <INCLURE()>
//
function calculer_inclure($struct, $descr, &$boucles, $id_boucle) {
	$fichier = $struct->texte;

	# raccourci <INCLURE{fond=xxx}> sans fichier .php
	if (!strlen($fichier))
		$path = _DIR_RESTREINT.'public.php';

	# sinon chercher le fichier, eventuellement en changeant.php3 => .php
	# et en gardant la compatibilite <INCLURE(page.php3)>
	else if (!($path = find_in_path($fichier))
	AND !(
		preg_match(',^(.*[.]php)3$,', $fichier, $r)
		AND (
			($path = find_in_path($r[1]))
			OR ($path = ($r[1] == 'page.php') ? _DIR_RESTREINT.'public.php':'')
		)
	)) {
		spip_log("ERREUR: <INCLURE($fichier)> impossible");
		erreur_squelette(_T('zbug_info_erreur_squelette'),
				 "&lt;INCLURE($fichier)&gt; - "
				 ._T('fichier_introuvable', array('fichier' => $fichier)));
		return "'<!-- Erreur INCLURE(".texte_script($fichier).") -->'";
	}

	$l = array();
	foreach($struct->param as $val) {
		$var = array_shift($val);
		$l[] = "\'$var\' => ' .  argumenter_squelette(" . 
		  ($val ? calculer_liste($val[0], $descr, $boucles, $id_boucle) :(($var =='lang') ? '$GLOBALS["spip_lang"]' : index_pile($id_boucle, $var, $boucles)))
		  . ") . '";
	}

	return "\n'<".
		"?php\n\t\$contexte_inclus = array(" .
		join(",\n\t",$l) .
		");" .
		"\n\tinclude(\\'$path\\');" .
		"\n?'." . "'>'";
 }

//
// calculer_boucle() produit le corps PHP d'une boucle Spip. 
// ce corps remplit une variable $t0 retournee en valeur.
// Ici on distingue boucles recursives et boucle a requete SQL
// et on insere le code d'envoi au debusqueur du resultat de la fonction.

function calculer_boucle($id_boucle, &$boucles) {
 
  if ($boucles[$id_boucle]->type_requete == 'boucle')  {
    $corps = ( "\n	\$t0 = " . $boucles[$id_boucle]->return . ";");
    $req = "";
    } else {
      $corps = calculer_boucle_nonrec($id_boucle, $boucles);
      // attention, ne calculer la requete que maintenant
      // car la fonction precedente appelle index_pile qui influe dessus
      $req =	(($init = $boucles[$id_boucle]->doublons) ?
			("\n\t$init = array();") : '') .
		calculer_requete_sql($boucles[$id_boucle]);
    }
  return $req . $corps 
	. (($GLOBALS['var_mode_affiche'] != 'resultat') ? "" : "
		boucle_debug_resultat('$id_boucle', 'resultat', \$t0);")
	.  "\n	return \$t0;";
}

// compil d'un boucle non recursive. 
// c'est un "while (fetch_sql)" dans le cas général,
// qu'on essaye d'optimiser un max.

function calculer_boucle_nonrec($id_boucle, &$boucles) {

	$boucle = &$boucles[$id_boucle];
	$return = $boucle->return;
	$type_boucle = $boucle->type_requete;
	$primary = $boucle->primary;
	$constant = ereg(CODE_MONOTONE,$return);

	// Cas {1/3} {1,4} {n-2,1}...

	$flag_cpt = $boucle->mode_partie ||$boucle->cptrows;

	//
	// Creer le debut du corps de la boucle :
	//
	$corps = !$flag_cpt ? '' : "\n		\$Numrows['$id_boucle']['compteur_boucle']++;";

	if ($boucle->mode_partie)
		$corps .= "
		if (\$Numrows['$id_boucle']['compteur_boucle']-1 >= \$debut_boucle
		AND \$Numrows['$id_boucle']['compteur_boucle']-1 <= \$fin_boucle) {";
	
	// Calculer les invalideurs si c'est une boucle non constante et si on
	// souhaite invalider ces elements
	if (!$constant AND $primary AND ($primary == 'id_forum'
	OR in_array($primary, explode(',', $GLOBALS['invalider_caches']))))
		$corps .= "\n\t\t\$Cache['$primary'][intval(" .
		  (($primary != 'id_forum')  ? 
		   index_pile($id_boucle, $primary, $boucles) :
		   ("calcul_index_forum(" . 
		// Retournera 4 [$SP] mais force la demande du champ a MySQL
		    index_pile($id_boucle, 'id_article', $boucles) . ',' .
		    index_pile($id_boucle, 'id_breve', $boucles) .  ',' .
		    index_pile($id_boucle, 'id_rubrique', $boucles) .',' .
		    index_pile($id_boucle, 'id_syndic', $boucles) .
		    ")")) .
		  ")] = 1; // invalideurs\n";

	// faudrait expanser le foreach a la compil, car y en a souvent qu'un 
	// et puis faire un [] plutot qu'un "','."
	if ($boucle->doublons)
		$corps .= "		foreach(" . $boucle->doublons . ' as $k) $doublons[$k] .= "," . ' .
		index_pile($id_boucle, $primary, $boucles)
		. "; // doublons\n";


	if (count($boucle->separateur))
	  $code_sep = ("'" . ereg_replace("'","\'",join('',$boucle->separateur)) . "'"); 

	// La boucle doit-elle selectionner la langue ?
	// -. par defaut, les boucles suivantes le font
	// "peut-etre", c'est-a-dire si forcer_lang == false.
	// - . a moins d'une demande explicite
	if (!$constant && $boucle->lang_select != 'non' &&
	    (($boucle->lang_select == 'oui')  ||
		    (
			$type_boucle == 'articles'
			OR $type_boucle == 'rubriques'
			OR $type_boucle == 'hierarchie'
			OR $type_boucle == 'breves'
			)))
	  {
	      $corps .= 
		  (($boucle->lang_select != 'oui') ? 
			"\t\tif (!\$GLOBALS['forcer_lang'])\n\t " : '')
		  . "\t\t\$GLOBALS['spip_lang'] = (\$x = "
		  . index_pile($id_boucle, 'lang', $boucles)
		  . ') ? $x : $old_lang;';
		// Memoriser la langue avant la boucle pour la restituer apres
	      $init = "\n	\$old_lang = \$GLOBALS['spip_lang'];";
	      $fin = "\n	\$GLOBALS['spip_lang'] = \$old_lang;";

	  }
	else {
		$init = '';
		$fin = '';
	}

	// gestion optimale des separateurs et des boucles constantes
	$corps .= 
		((!$boucle->separateur) ? 
			(($constant && !$corps) ? $return :
			 	("\n\t\t" . '$t0 .= ' . $return . ";")) :
		 ("\n\t\t\$t1 " .
			((strpos($return, '$t1.') === 0) ? 
			 (".=" . substr($return,4)) :
			 ('= ' . $return)) .
		  ";\n\t\t" .
		  '$t0 .= (($t1 && $t0) ? ' . $code_sep . " : '') . \$t1;"));
     
	// Fin de parties
	if ($boucle->mode_partie) $corps .= "\n		}\n";


	// si le corps est une constante, ne pas appeler le serveur N fois!
	if (ereg(CODE_MONOTONE,$corps, $r)) {
		if (!$r[2]) {
			if (!$boucle->numrows)
				return 'return "";';
			else
				$corps = "";
		} else {
			$boucle->numrows = true;
			$corps = "\n	".'for($x=$Numrows["'.$id_boucle.'"]["total"];$x>0;$x--)
			$t0 .= ' . $corps .';';
		}
	} else {

		$corps = $init . '

	// RESULTATS
	while ($Pile[$SP] = @spip_abstract_fetch($result,"' .
		  $boucle->sql_serveur .
		  '")) {' . 
		  "\n$corps\n	}\n" .
		  $fin ;
	}

	return '
	$t0 = "";
	$SP++;'
		. (!$flag_cpt  ? "" :
			"\n	\$Numrows['$id_boucle']['compteur_boucle'] = 0;")
		. ($boucle->mode_partie ? 
		   calculer_parties($boucles, $id_boucle) :
		   (!$boucle->numrows ? '' :
		    ( "\n	\$Numrows['" .
			$id_boucle .
			"']['total'] = @spip_abstract_count(\$result,'" .
			$boucle->sql_serveur .
		      "');"))) .
		$corps .
		"\n	@spip_abstract_free(\$result,'" .
		$boucle->sql_serveur . "');";
}


function calculer_requete_sql(&$boucle)
{
	if (!$order = $boucle->order
	AND !$order = $boucle->default_order)
		$order = array();

	return   ($boucle->hierarchie ? "\n\t$boucle->hierarchie" : '')
		. $boucle->in 
		. $boucle->hash . 
		"\n\n	// REQUETE
	\$result = spip_optim_select(\n\t\tarray(\"" . 
		# En absence de champ c'est un decompte : 
	  	# prendre une constante pour avoir qqch
		(!$boucle->select ? 1 :
		 join("\",\n\t\t\"", $boucle->select)) .
		'"), # SELECT
		' . calculer_from($boucle) .
		', # FROM
		' . calculer_dump_array($boucle->where) .
		', # WHERE
		' . calculer_dump_join($boucle->join)
		. ', # WHERE pour jointure
		' . (!$boucle->group ? "''" : 
		     ('"' . join(", ", $boucle->group)) . '"') .
		', # GROUP
		array(' .
			join(', ', $order) .
		"), # ORDER
		" . (strpos($boucle->limit, 'intval') === false ?
			"'".$boucle->limit."'" :
			$boucle->limit). ", # LIMIT
		'".$boucle->sous_requete."', # sous
		'" . (!$boucle->having ? "" : "(COUNT(*)> $boucle->having)")."', # HAVING
		'".$boucle->id_table."', # table
		'".$boucle->id_boucle."', # boucle
		'".$boucle->sql_serveur."'); # serveur";
}


function calculer_dump_array($a)
{
  if (!is_array($a)) return $a ;
  $res = "";
  if ($a[0] == "'?'") 
    return ("(" . calculer_dump_array($a[1]) .
	    " ? " . calculer_dump_array($a[2]) .
	    " : " . calculer_dump_array($a[3]) .
	    ")");
  else {
    foreach($a as $k => $v) $res .= ", " . calculer_dump_array($v);
    return "\n\t\t\tarray(" . substr($res,2) . ')';
  }
}

function calculer_dump_join($a)
{
  $res = "";
  foreach($a as $k => $v) $res .= ", $k => array('$v[0]', '$v[1]')";
  return 'array(' . substr($res,2) . ')';
}

function calculer_from(&$boucle)
{
  $res = "";
  foreach($boucle->from as $k => $v) $res .= ",'$k' => '$v'";
  return 'array(' . substr($res,1) . ')';
}

//
// fonction traitant les criteres {1,n} (analyses dans inc-criteres)
//
## a deplacer dans inc-criteres ??
function calculer_parties($boucles, $id_boucle) {

	$boucle = &$boucles[$id_boucle];
	$partie = $boucle->partie;
	$mode_partie = $boucle->mode_partie;
	$total_parties = $boucle->total_parties;

	// Notes :
	// $debut_boucle et $fin_boucle sont les indices SQL du premier
	// et du dernier demandes dans la boucle : 0 pour le premier,
	// n-1 pour le dernier ; donc total_boucle = 1 + debut - fin

	// nombre total avant partition
	$retour = "\n\n	// Partition\n	" .
		'$nombre_boucle = @spip_abstract_count($result,"' .
		$boucle->sql_serveur .
		'");';

	ereg("([+-/])([+-/])?", $mode_partie, $regs);
	list(,$op1,$op2) = $regs;

	// {1/3}
	if ($op1 == '/') {
		$pmoins1 = is_numeric($partie) ? ($partie-1) : "($partie-1)";
		$totpos = is_numeric($total_parties) ? ($total_parties) :
		  "($total_parties ? $total_parties : 1)";
		$retour .= "\n	"
		  .'$debut_boucle = ceil(($nombre_boucle * '
		  . $pmoins1 . ')/' . $totpos . ");";
		$fin = 'ceil (($nombre_boucle * '
			. $partie . ')/' . $totpos . ") - 1";
	}

	// {1,x}
	elseif ($op1 == '+') {
		$retour .= "\n	"
			. '$debut_boucle = ' . $partie . ';';
	}
	// {n-1,x}
	elseif ($op1 == '-') {
		$retour .= "\n	"
			. '$debut_boucle = $nombre_boucle - ' . $partie . ';';
	}
	// {x,1}
	if ($op2 == '+') {
		$fin = '$debut_boucle'
		  . (is_numeric($total_parties) ?
		     (($total_parties==1) ? "" :(' + ' . ($total_parties-1))):
		     ('+' . $total_parties . ' - 1'));
	}
	// {x,n-1}
	elseif ($op2 == '-') {
		$fin = '$debut_boucle + $nombre_boucle - '
		  . (is_numeric($total_parties) ? ($total_parties+1) :
		     ($total_parties . ' - 1'));
	}

	// Rabattre $fin_boucle sur le maximum
	$retour .= "\n	"
		.'$fin_boucle = min(' . $fin . ', $nombre_boucle - 1);';

	// calcul du total boucle final
	$retour .= "\n	"
		.'$Numrows[\''.$id_boucle.'\']["total"] = max(0,$fin_boucle - $debut_boucle + 1);';

	return $retour;
}

// Production du code PHP a partir de la sequence livree par le phraseur
// $boucles est passe par reference pour affectation par index_pile.
// Retourne une expression PHP,
// (qui sera argument d'un Return ou la partie droite d'une affectation).

function calculer_liste($tableau, $descr, &$boucles, $id_boucle='') {
	if (!$tableau) return "''";
	$codes = compile_cas($tableau, $descr, $boucles, $id_boucle);
	$n = count($codes);
	if (!$n) return "''";
	if ($GLOBALS['var_mode_affiche'] != 'validation')
	  return
		(($n==1) ? $codes[0] : 
			 "(" . join (" .\n$tab", $codes) . ")");
	else return "debug_sequence('$id_boucle', '" .
	  ($descr['nom']) .
	  "', " .
	  intval($descr['niv']) .
	  ",  array(" .
	  join(" ,\n$tab", $codes) . "))";
}

function compile_cas($tableau, $descr, &$boucles, $id_boucle) {
        $codes = array();
	// cas de la boucle recursive
	if (is_array($id_boucle)) 
	  $id_boucle = $id_boucle[0];
	$type = $boucles[$id_boucle]->type_requete;
	$descr['niv']++;
	for ($i=0; $i<=$descr['niv']; $i++) $tab .= "\t";

	// chaque commentaire introduit dans le code doit commencer
	// par un caractere distinguant le cas, pour exploitation par debug.
	foreach ($tableau as $p) {

		switch($p->type) {
		// texte seul
		case 'texte':
			$code = "'".ereg_replace("([\\\\'])", "\\\\1", $p->texte)."'";

			$commentaire= strlen($p->texte) . " signes";
			$avant='';
			$apres='';
			$altern = "''";
			break;

		case 'polyglotte':
			$code = "";
			foreach($p->traductions as $k => $v) {
			  $code .= ",'" .
			    ereg_replace("([\\\\'])", "\\\\1", $k) .
			    "' => '" .
			    ereg_replace("([\\\\'])", "\\\\1", $v) .
			    "'";
			}
			$code = "multi_trad(array(" .
 			  substr($code,1) .
			  "))";
			$commentaire= '&';
			$avant='';
			$apres='';
			$altern = "''";
			break;

		// inclure
		case 'include':
			$code = calculer_inclure($p, $descr, $boucles, $id_boucle);
			
			$commentaire = '<INCLURE ' . str_replace("\n", ' ', $p->code) . '>';
			$avant='';
			$apres='';
			$altern = "''";
			break;

		// boucle
		case 'boucle':
			$nom = $p->id_boucle;
			$newdescr = $descr;
			$newdescr['id_mere'] = $nom;
			$newdescr['niv']++;
			$code = 'BOUCLE' .
			  ereg_replace("-","_", $nom) . $descr['nom'] .
			  '($Cache, $Pile, $doublons, $Numrows, $SP)';
			$commentaire= "?$nom";
			$avant = calculer_liste($p->avant,
				$newdescr, $boucles, $id_boucle);
			$apres = calculer_liste($p->apres,
				$newdescr, $boucles, $id_boucle);
			$newdescr['niv']--;
			$altern = calculer_liste($p->altern,
				$newdescr, $boucles, $id_boucle);
			break;

		case 'idiome':
			$p->code = "_T('" . $p->module . ":" .$p->nom_champ . "')";
			$p->id_boucle = $id_boucle;
			$p->boucles = &$boucles;
			$p->interdire_scripts = false;
			$commentaire = ":";
			$code = applique_filtres($p);
			$avant='';
			$apres='';
			$altern = "''";
			break;

		case 'champ':

			// cette structure pourrait etre completee des le phrase' (a faire)
			$p->id_boucle = $id_boucle;
			$p->boucles = &$boucles;
			$p->descr = $descr;
			#$p->interdire_scripts = true;
			$p->type_requete = $type;

			$code = calculer_champ($p);
			$commentaire = '#' . $p->nom_champ . $p->etoile;
			$avant = calculer_liste($p->avant,
				$descr, $boucles, $id_boucle);
			$apres = calculer_liste($p->apres,
				$descr, $boucles, $id_boucle);
			$altern = "''";
			break;

		default: 
		  erreur_squelette(_T('zbug_info_erreur_squelette'));
		} // switch

		if ($code != "''") {
			if ($avant == "''")
				$avant = '';
			if ($apres == "''")
				$apres = '';
			if ($avant||$apres||($altern!="''")) {
				$t = '$t' . $descr['niv'];
				$res = (!$avant ? "" : "$avant . ") . 
					$t .
					(!$apres ? "" : " . $apres");
				$code = "((strval($t = $code)!='')"
					." ?\n\t$tab($res) :\n\t$tab($altern))";
			}
		}
		if ($code != "''")
			$codes[]= (($GLOBALS['var_mode_affiche'] == 'validation') ?
				"array(" . $p->ligne . ", '$commentaire', $code)"
				: (($GLOBALS['var_mode_affiche'] == 'code') ?
				"\n// $commentaire\n$code" :
				$code));
	} // foreach
	return $codes;
}

// affichage du code produit

function code_boucle(&$boucles, $id, $nom)
{
	$boucle = &$boucles[$id];

	// Indiquer la boucle en commentaire
	$pretty = '';

	if ($boucle->type_requete != 'boucle')
	  {
	    // Resynthetiser les criteres
	    foreach ($boucle->param as $param) {
	      $s = "";
	      $sep = "";
	      foreach ($param as $t) {
		if (is_array($t)) { // toujours vrai normalement
		  $s .= $sep;
		  $c = $t[0];
		  if ($c->apres)
		    $s .= ($c->apres . $c->texte . $c->apres);
		  else {
		// faudrait decompiler aussi les balises...
		    foreach ($t as $c)
		      $s .=  ($c->type == 'texte') ? $c->texte : '#...';
		  }
		  $sep = ", ";
		}
	      }
	      $pretty .= ' {' . $s . '}';
	    }
	  }

	$pretty = "BOUCLE$id(".strtoupper($boucle->type_requete) . ")" .
		ereg_replace("[\r\n]", " ", $pretty);

	return $pretty;
}


// Prend en argument le texte d'un squelette (et son fichier d'origine)
// sa grammaire et un nom.
// Retourne une fonction PHP/SQL portant ce nom et calculant une page.
// Pour appeler la fonction produite, lui fournir 2 tableaux de 1 e'le'ment:
// - 1er: element 'cache' => nom (du fichier ou` mettre la page)
// - 2e: element 0 contenant un environnement ('id_article => $id_article, etc)
// Elle retourne alors un tableau de 5 e'le'ments:
// - 'texte' => page HTML, application du squelette a` l'environnement;
// - 'squelette' => le nom du squelette
// - 'process_ins' => 'html' ou 'php' selon la pre'sence de PHP dynamique
// - 'invalideurs' =>  de'pendances de cette page, pour invalider son cache.
// - 'entetes' => tableau des entetes http
// En cas d'erreur, elle retourne un tableau des 2 premiers elements seulement

function public_compiler_dist($squelette, $nom, $gram, $sourcefile) {
  global  $table_des_tables, $tables_des_serveurs_sql, $tables_principales,
    $tables_jointures;

	// Pre-traitement : reperer le charset du squelette, et le convertir
	// Bonus : supprime le BOM
	include_spip('inc/charsets');
	$squelette = transcoder_page($squelette);

	// Phraser le squelette, selon sa grammaire
	// pour le moment: "html" seul connu (HTML+balises BOUCLE)
	$boucles = array();
	spip_timer('calcul_skel');

	$f = charger_fonction('phraser_'.$gram, 'public');

	$racine = $f($squelette, '',$boucles, $nom);

	// tableau des informations sur le squelette
	$descr = array('nom' => $nom, 'documents' => false, 'sourcefile' => $sourcefile);

	// une boucle documents est conditionnee par tout le reste!
	foreach($boucles as $idb => $boucle) {
		if (($boucle->type_requete == 'documents') && $boucle->doublons)
			{ $descr['documents'] = true; break; }
		$boucles[$idb]->descr = &$descr;
	}
	// Commencer par reperer les boucles appelees explicitement 
	// car elles indexent les arguments de maniere derogatoire
	foreach($boucles as $id => $boucle) { 
		if ($boucle->type_requete == 'boucle') {
			$rec = &$boucles[$boucle->param[0]];
			if (!$rec) {
				return array(_T('zbug_info_erreur_squelette'),
						($boucle->param[0]
						. ' '. _T('zbug_boucle_recursive_undef')));
			} else {
				$rec->externe = $id;
				$descr['id_mere'] = $id;
				$boucles[$id]->return =
						calculer_liste(array($rec),
							 $descr,
							 $boucles,
							 $boucle->param);
			}
		}
	}
	foreach($boucles as $id => $boucle) { 
		$type = $boucle->type_requete;
		if ($type != 'boucle') {
		  if ($x = $table_des_tables[$type]) {
		    $boucles[$id]->id_table = $x;
		    $boucles[$id]->primary = $tables_principales["spip_$x"]['key']["PRIMARY KEY"];
		    if ((!$boucles[$id]->jointures)
			AND (is_array($x = $tables_jointures['spip_' . $x])))
		      $boucles[$id]->jointures = $x;
		  } else {
			// table non Spip.
		    $boucles[$id]->id_table = $type;
		    $serveur = $boucle->sql_serveur;
		    $x = $tables_des_serveurs_sql[$serveur ? $serveur : 'localhost'][$type]['key'];		
		    $boucles[$id]->primary = ($x["PRIMARY KEY"] ? $x["PRIMARY KEY"] : $x["KEY"]);
		  }
		  if ($boucle->param) {
				$res = calculer_criteres($id, $boucles);
				if (is_array($res)) return $res; # erreur
			}
		  $descr['id_mere'] = $id;
		  $boucles[$id]->return =
			  calculer_liste($boucle->milieu,
					 $descr,
					 $boucles,
					 $id);
		}
	}

	// idem pour la racine
	$descr['id_mere'] = '';
	$corps = calculer_liste($racine, $descr, $boucles);

	// Calcul du corps de toutes les fonctions PHP,
	// en particulier les requetes SQL et TOTAL_BOUCLE
	// de'terminables seulement maintenant

	foreach($boucles as $id => $boucle) {
		// appeler la fonction de definition de la boucle
		$f = 'boucle_'.strtoupper($boucle->type_requete);
		// si pas de definition perso, definition spip
		if (!function_exists($f)) $f = $f.'_dist';
		// laquelle a une definition par defaut
		if (!function_exists($f)) $f = 'boucle_DEFAUT';
		$boucles[$id]->return = 
			"function BOUCLE" . ereg_replace("-","_",$id) . $nom .
			'(&$Cache, &$Pile, &$doublons, &$Numrows, $SP) {' .
			$f($id, $boucles) .
			"\n}\n\n";
		if ($GLOBALS['var_mode'] == 'debug')
		  boucle_debug_compile ($id, $nom, $boucles[$id]->return);

	}

	$code = "";
	foreach($boucles as $id => $boucle) {
		$code .= "\n//\n// <BOUCLE " .
#		  code_boucle($boucles, $id, $nom). # pas au point
		  $boucle->type_requete .
		  ">\n//\n" .
		  $boucle->return;
	}

	$secondes = spip_timer('calcul_skel');
	spip_log("COMPIL ($secondes) ["
		.preg_replace(',\.html$,', '', $sourcefile)
		."] ".creer_repertoire(_DIR_CACHE, 'skel')."$nom.php");

	$code = "<"."?php
/*
 * Squelette : $sourcefile
 * Date :      ".http_gmoddate(@filemtime($sourcefile))." GMT
 * Compile :   ".http_gmoddate(time())." GMT ($secondes)
 * " . (!$boucles ?  "Pas de boucle" :
	("Boucles :   " . join (', ', array_keys($boucles)))) ."
 */ " .
	  $code . '

//
// Fonction principale du squelette ' . $sourcefile ."
//
function " . $nom . '($Cache, $Pile, $doublons=array(), $Numrows=array(), $SP=0) {
	$page = ' .
	// ATTENTION, le calcul du l'expression $corps affectera 
	// c'est pourquoi on l'affecte a cette variable auxiliaire
	// avant de referencer $Cache
	$corps . ";
	return analyse_resultat_skel('$nom', \$Cache, \$page);
}

?".">";

	if ($GLOBALS['var_mode'] == 'debug')
		squelette_debug_compile($nom, $sourcefile, $code, $squelette);
	return $code;

}

?>
