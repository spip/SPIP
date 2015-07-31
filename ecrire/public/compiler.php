<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
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
define('CODE_MONOTONE', ",^(\n//[^\n]*\n)?\(?'([^'])*'\)?$,");
// s'il faut commenter le code produit
define('CODE_COMMENTE', true);

// definition des structures de donnees
include_spip('public/interfaces');

// Definition de la structure $p, et fonctions de recherche et de reservation
// dans l'arborescence des boucles
include_spip('public/references');

// definition des boucles
include_spip('public/boucles');

// definition des criteres
include_spip('public/criteres');

// definition des balises
include_spip('public/balises');

// Gestion des jointures
include_spip('public/jointures');

// Les 2 ecritures INCLURE{A1,A2,A3...} et INCLURE(A1){A2}{A3}... sont admises
// Preferer la premiere.
// Les Ai sont de la forme Vi=Ei ou bien Vi qui veut alors dire Vi=Vi
// Le resultat est un tableau indexe par les Vi
// Toutefois, si le premier argument n'est pas de la forme Vi=Ei
// il est conventionnellement la valeur de l'index 1.
// pour la balise #INCLURE
// mais pas pour <INCLURE> dont le fond est defini explicitement.


// http://doc.spip.org/@argumenter_inclure
function argumenter_inclure($params, $rejet_filtres, $p, &$boucles, $id_boucle, $echap=true, $lang = '', $fond1=false){
	$l = array();

	foreach($params as $k => $couple) {
	// la liste d'arguments d'inclusion peut se terminer par un filtre
		$filtre = array_shift($couple);
		if ($filtre) break;
		foreach($couple as $n => $val) {
			$var = $val[0];
			if ($var->type != 'texte') {
			  if ($n OR $k) {
				$msg = array('zbug_parametres_inclus_incorrects',
					 array('param' => $var->nom_champ));
				erreur_squelette($msg, $p);
			  } 
			  $l[1] = calculer_liste($val, $p->descr, $boucles, $id_boucle);
			  break;
			} else {
				preg_match(",^([^=]*)(=?)(.*)$,", $var->texte,$m);
				$var = $m[1];
				$auto = false;;
				if ($m[2]) {
				  $v = $m[3];
				  if (preg_match(',^[\'"](.*)[\'"]$,', $v, $m)) $v = $m[1];
				  $val[0] = new Texte;
				  $val[0]->texte = $v;
				} elseif ($k OR $n OR $fond1) {
				  $auto = true;
				} else $var = 1;

				if ($var == 'lang') {
				  $lang = !$auto 
				    ? calculer_liste($val, $p->descr, $boucles, $id_boucle)
				    : '$GLOBALS["spip_lang"]';
				} else {
				  $val = $auto
				    ? index_pile($id_boucle, $var, $boucles)
				    : calculer_liste($val, $p->descr, $boucles, $id_boucle);

				  if ($var !== 1)
				    $val = ($echap?"\'$var\' => ' . argumenter_squelette(":"'$var' => ")
				    . $val . ($echap? ") . '":" ");
				  else $val = $echap ? "'.$val.'" : $val;
				  $l[$var] = $val;
				}
			}
		}
	}

	// Cas particulier de la langue : si {lang=xx} est definie, on
	// la passe, sinon on passe la langue courante au moment du calcul
	// sauf si on n'en veut pas 
	if ($lang === false) return $l;
	if (!$lang) $lang = '$GLOBALS["spip_lang"]';
	$l['lang'] = ($echap?"\'lang\' => ' . argumenter_squelette(":"'lang' => ")  . $lang . ($echap?") . '":" ");

	return $l;
}

//
// Calculer un <INCLURE()>
// La constante ci-dessous donne le code general quand il s'agit d'un script.
// Pour un squelette, c'est plus simple.

define('CODE_INCLURE_SCRIPT', 'if (is_readable($path = %s))
	include $path;
else { include_spip("public/compiler");
	erreur_squelette(array("fichier_introuvable", array("fichier" => "%s")), reconstruire_contexte_compil(array(%s)));}'
);

// http://doc.spip.org/@calculer_inclure
function calculer_inclure($p, &$boucles, $id_boucle) {

	$_contexte = argumenter_inclure($p->param, false, $p, $boucles, $id_boucle, true, '', true);
	if (is_string($p->texte)) {
		$fichier = $p->texte;
		$code = "'$fichier'";
	} else {
		$code = calculer_liste($p->texte, $p->descr, $boucles, $id_boucle);
		if (preg_match("/^'([^']*)'/s", $code, $r))
			$fichier = $r[1];
		else $fichier = '';
	}

	// s'il y a une extension .php, ce n'est pas un squelette
	if (preg_match('/^.+[.]php$/s', $fichier)) {
		// si inexistant, on essaiera a l'execution
		if ($path = find_in_path($fichier))
			$path = "\"$path\"";
		else $path = "find_in_path(\"$fichier\")";

		$code = str_replace(array("\\","'"),
				    array("\\\\","\\'"), 
				    memoriser_contexte_compil($p));
		$code = sprintf(CODE_INCLURE_SCRIPT, $path, $fichier, $code);
	} else 	{
		$_contexte['fond'] = "\'fond\' => ' . argumenter_squelette(" . $code  . ") . '";
		$code = 'include _DIR_RESTREINT . "public.php";';
	}

	// Critere d'inclusion {env} (et {self} pour compatibilite ascendante)
	if ($env = (isset($_contexte['env'])|| isset($_contexte['self']))) {
		unset($_contexte['env']);
	}

	// noter les doublons dans l'appel a public.php
	if (isset($_contexte['doublons'])) {
		$_contexte['doublons'] = "\\'doublons\\' => '.var_export(\$doublons,true).'";
	}

	if ($ajax = isset($_contexte['ajax']))
		unset($_contexte['ajax']);

	$contexte = 'array(' . join(",\n\t", $_contexte) .')';
	if ($env) {
		$contexte = "array_merge('.var_export(\$Pile[0],1).',$contexte)";
	}

	// Gerer ajax
	if ($ajax) {
		$code = '	echo "<div class=\\\'ajaxbloc env-\'
			. eval(\'return encoder_contexte_ajax('.$contexte.');\')
			. \'\\\'>\\n";'
			."\n"
			.$code
			."\n"
			.'	echo "</div><!-- ajaxbloc -->\\n";';
	}

	$code = "\n'<".
		"?php\n".'$contexte_inclus = '.$contexte.";\n"
		. $code
		. "\n?'." . "'>'";

	return $code;
}

//
// calculer_boucle() produit le corps PHP d'une boucle Spip. 
// ce corps remplit une variable $t0 retournee en valeur.
// Ici on distingue boucles recursives et boucle a requete SQL
// et on insere le code d'envoi au debusqueur du resultat de la fonction.

// http://doc.spip.org/@calculer_boucle
function calculer_boucle($id_boucle, &$boucles) {

	$boucles[$id_boucle] = pipeline('post_boucle', $boucles[$id_boucle]);

	// en mode debug memoriser les premiers passages dans la boucle,
	// mais pas tous, sinon ca pete.
	if  (_request('var_mode_affiche') != 'resultat') 
		$trace = '';
	else {
		$trace = $boucles[$id_boucle]->descr['nom'] . $id_boucle;
		$trace = "if (count(@\$GLOBALS['debug_objets']['resultat']['$trace'])<3)
	    \$GLOBALS['debug_objets']['resultat']['$trace'][] = \$t0;";
	}
	return ($boucles[$id_boucle]->type_requete == 'boucle')
	? calculer_boucle_rec($id_boucle, $boucles, $trace) 
	: calculer_boucle_nonrec($id_boucle, $boucles, $trace);
}

// compil d'une boucle recursive. 
// il suffit (ET IL FAUT) sauvegarder les valeurs des arguments passes par
// reference, car par definition un tel passage ne les sauvegarde pas

// http://doc.spip.org/@calculer_boucle_rec
function calculer_boucle_rec($id_boucle, &$boucles, $trace) {
	$nom = $boucles[$id_boucle]->param[0];
	return "\n\t\$save_numrows = (\$Numrows['$nom']);"
	. "\n\t\$t0 = " . $boucles[$id_boucle]->return . ";"
	. "\n\t\$Numrows['$nom'] = (\$save_numrows);"
	. $trace
	. "\n\treturn \$t0;";
}

// Compilation d'une boucle non recursive. 
// Ci-dessous la constante donnant le cadre systematique du code:
// %s1: initialisation des arguments de calculer_select
// %s2: appel de calculer_select en donnant un contexte pour les cas d'erreur
// %s3: initialisation du sous-tableau Numrows[id_boucle]
// %s4: sauvegarde de la langue et calcul des invariants de boucle sur elle
// %s5: boucle while sql_fetch ou str_repeat si corps monotone
// %s6: restauration de la langue
// %s7: liberation de la ressource, en tenant compte du serveur SQL 
// %s8: code de trace eventuel avant le retour

define('CODE_CORPS_BOUCLE', '%s
	$t0 = "";
	// REQUETE
	$result = calculer_select($select, $from, $type, $where, $join, $groupby, $orderby, $limit, $having, $table, $id, $connect,
		 array(%s));
	if ($result) {
	%s%s$SP++;
	// RESULTATS
	%s
	%s@sql_free($result%s);
	}%s
	return $t0;'
);

// http://doc.spip.org/@calculer_boucle_nonrec
function calculer_boucle_nonrec($id_boucle, &$boucles, $trace) {

	$boucle = &$boucles[$id_boucle];
	$return = $boucle->return;
	$type_boucle = $boucle->type_requete;
	$primary = $boucle->primary;
	$constant = preg_match(CODE_MONOTONE, str_replace("\\'",'', $return));
	$corps = '';

	// faudrait expanser le foreach a la compil, car y en a souvent qu'un 
	// et puis faire un [] plutot qu'un "','."
	if ($boucle->doublons)
		$corps .= "\n\t\t\tforeach(" . $boucle->doublons . ' as $k) $doublons[$k] .= "," . ' .
		index_pile($id_boucle, $primary, $boucles)
		. "; // doublons\n";

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
		// Memoriser la langue avant la boucle et la restituer apres
	        // afin que le corps de boucle affecte la globale directement
		$�nit_lang = "lang_select(\$GLOBALS['spip_lang']);\n\t";
		$fin_lang = "lang_select();\n\t";

		$corps .= 
		  (($boucle->lang_select != 'oui') ? 
			"\t\tif (!(isset(\$GLOBALS['forcer_lang']) AND \$GLOBALS['forcer_lang']))\n\t " : '')
		  . "\t\tif (\$x = "
		  . index_pile($id_boucle, 'lang', $boucles)
		  . ') $GLOBALS["spip_lang"] = $x;';
	  }
	else {
		$�nit_lang = '';
		$fin_lang = '';
		// sortir les appels au traducteur (invariants de boucle)
		if (strpos($return, '?php') === false
		AND preg_match_all("/\W(_T[(]'[^']*'[)])/", $return, $r)) {
			$i = 1;
			foreach($r[1] as $t) {
				$�nit_lang .= "\n\t\$l$i = $t;";
				$return = str_replace($t, "\$l$i", $return);
				$i++;
			}
		}
	}

	// gestion optimale des separateurs et des boucles constantes
	if (count($boucle->separateur))
	  $code_sep = ("'" . str_replace("'","\'",join('',$boucle->separateur)) . "'");

	$corps .= 
		((!$boucle->separateur) ? 
			(($constant && !$corps) ? $return :
			 (($return==="''") ? '' :
			  ("\n\t\t" . '$t0 .= ' . $return . ";"))) :
		 ("\n\t\t\$t1 " .
			((strpos($return, '$t1.') === 0) ? 
			 (".=" . substr($return,4)) :
			 ('= ' . $return)) .
		  ";\n\t\t" .
		  '$t0 .= (($t1 && $t0) ? ' . $code_sep . " : '') . \$t1;"));
     
	// Calculer les invalideurs si c'est une boucle non constante et si on
	// souhaite invalider ces elements
	if (!$constant AND $primary) {
		include_spip('inc/invalideur');
		if (function_exists($i = 'calcul_invalideurs'))
			$corps = $i($corps, $primary, $boucles, $id_boucle);
	}

	// gerer le compteur de boucle 
	// avec ou sans son utilisation par les criteres {1/3} {1,4} {n-2,1}...

	if ($boucle->mode_partie)
		$corps = 
		"\n\t\t\$Numrows['$id_boucle']['compteur_boucle']++;
		if (\$Numrows['$id_boucle']['compteur_boucle'] > \$debut_boucle) {
		if (\$Numrows['$id_boucle']['compteur_boucle']-1 > \$fin_boucle) break;\n$corps\n		}\n";

	elseif ($boucle->cptrows)

		$corps = "\n\t\t\$Numrows['$id_boucle']['compteur_boucle']++;$corps";

	$serveur = !$boucle->sql_serveur ? ''
		: (', ' . _q($boucle->sql_serveur));

	// si le corps est une constante, ne pas appeler le serveur N fois!

	if (preg_match(CODE_MONOTONE,str_replace("\\'",'',$corps), $r)) {
		if (!isset($r[2]) OR (!$r[2])) {
			if (!$boucle->numrows)
				return "\n\t\$t0 = '';";
			else
				$corps = "";
		} else {
			$boucle->numrows = true;
			$corps = "\n\t\$t0 = str_repeat($corps, \$Numrows['$id_boucle']['total']);";
		}
	} else $corps = "while (\$Pile[\$SP] = @sql_fetch(\$result$serveur)) {\n$corps\n	}"; 

	$count = '';
	if (!$boucle->select) {
		if (!$boucle->numrows OR $boucle->limit OR $boucle_mode_partie OR $boucle->group)
			$count = '1';
		else $count = 'count(*)';
		$boucles[$id_boucle]->select[]= $count; 
	}

	if ($boucle->numrows OR $boucle->mode_partie) {
		if ($count == 'count(*)')
			$count = "array_shift(sql_fetch(\$result$serveur))";
		else $count = "sql_count(\$result$serveur)";
		$count = (!$boucle->mode_partie
		  ? "\$Numrows['$id_boucle']['total'] = @intval($count);"
		  : calculer_parties($boucles[$id_boucle], $id_boucle, $count))
		. "\n\t";
	} else $count = '';
	
	if ($boucle->mode_partie || $boucle->cptrows)
		$count .= "\$Numrows['$id_boucle']['compteur_boucle'] = 0;\n\t";

	if ($boucle->mode_partie)
		$count .= "if (isset(\$debut_boucle) AND \$debut_boucle>0 AND sql_seek(\$result,\$debut_boucle,"._q($boucle->sql_serveur).",'continue'))\n\t\t\$Numrows['$id_boucle']['compteur_boucle']=\$debut_boucle;\n\t";

	// Ne calculer la requete que maintenant
	// car ce qui precede appelle index_pile qui influe dessus

	$init = (($init = $boucles[$id_boucle]->doublons)
			 ? ("\n\t$init = array();") : '')
	. calculer_requete_sql($boucles[$id_boucle]);

	$contexte = memoriser_contexte_compil($boucle);

	return sprintf(CODE_CORPS_BOUCLE, $init, $contexte, $count, $�nit_lang, $corps, $fin_lang, $serveur, $trace);
}


// http://doc.spip.org/@calculer_requete_sql
function calculer_requete_sql($boucle)
{
	return ($boucle->hierarchie ? "\n\t$boucle->hierarchie" : '')
	  . $boucle->in 
	  . $boucle->hash 
	  . calculer_dec('$table',  "'" . $boucle->id_table ."'")
	  . calculer_dec('$id', "'" . $boucle->id_boucle ."'")
		# En absence de champ c'est un decompte : 
	  . calculer_dec('$from',  calculer_from($boucle))
	  . calculer_dec('$type', calculer_from_type($boucle))
	  . calculer_dec('$groupby', 'array(' . (($g=join("\",\n\t\t\"",$boucle->group))?'"'.$g.'"':'') . ")")
	  . calculer_dec('$select', 'array("' . join("\",\n\t\t\"", $boucle->select).  "\")")
	  . calculer_dec('$orderby', 'array(' . calculer_order($boucle) .	")")
	  . calculer_dec('$where', calculer_dump_array($boucle->where))
	  . calculer_dec('$join', calculer_dump_join($boucle->join))
	  . calculer_dec('$limit', (strpos($boucle->limit, 'intval') === false ?
				    "'".$boucle->limit."'" :
				    $boucle->limit))
	  . calculer_dec('$having', calculer_dump_array($boucle->having));
}

function memoriser_contexte_compil($p) {
	return join(',', array(
		_q($p->descr['sourcefile']),
		_q($p->descr['nom']),
		_q($p->id_boucle),
		intval($p->ligne),
		_q($GLOBALS['spip_lang'])));
}

function reconstruire_contexte_compil($context_compil)
{
	if (!is_array($context_compil)) return $context_compil;
	include_spip('public/interfaces');
	$p = new Contexte;
	$p->descr = array('sourcefile' => $context_compil[0],
				  'nom' => $context_compil[1]);
	$p->id_boucle = $context_compil[2];
	$p->ligne = $context_compil[3];
	$p->lang = $context_compil[4];
	return $p;
}

// http://doc.spip.org/@calculer_dec
function calculer_dec($nom, $val)
{
	$static = "static ";
  if (
    strpos($val, '$') !== false 
    OR strpos($val, 'sql_') !== false
    OR (
    	$test = str_replace(array("array(",'\"',"\'"),array("","",""),$val) // supprimer les array( et les echappements de guillemets
    	AND strpos($test,"(")!==FALSE // si pas de parenthese ouvrante, pas de fonction, on peut sortir
    	AND $test = preg_replace(",'[^']*',UimsS","",$test) // supprimer les chaines qui peuvent contenir des fonctions SQL qui ne genent pas
    	AND preg_match(",\w+\s*\(,UimsS",$test,$regs) // tester la presence de fonctions restantes
    )
    ){
    $static = "";
  }
  return "\n\t" . $static . $nom . ' = ' . $val . ';';
}

// http://doc.spip.org/@calculer_dump_array
function calculer_dump_array($a)
{
  if (!is_array($a)) return $a ;
  $res = "";
  if ($a AND $a[0] == "'?'") 
    return ("(" . calculer_dump_array($a[1]) .
	    " ? " . calculer_dump_array($a[2]) .
	    " : " . calculer_dump_array($a[3]) .
	    ")");
  else {
    foreach($a as $v) $res .= ", " . calculer_dump_array($v);
    return "\n\t\t\tarray(" . substr($res,2) . ')';
  }
}

// http://doc.spip.org/@calculer_dump_join
function calculer_dump_join($a)
{
  $res = "";
  foreach($a as $k => $v) 
		$res .= ", '$k' => array(".implode(',',$v).")";
  return 'array(' . substr($res,2) . ')';
}

// http://doc.spip.org/@calculer_from
function calculer_from(&$boucle)
{
  $res = "";
  foreach($boucle->from as $k => $v) $res .= ",'$k' => '$v'";
  return 'array(' . substr($res,1) . ')';
}

// http://doc.spip.org/@calculer_from_type
function calculer_from_type(&$boucle)
{
  $res = "";
  foreach($boucle->from_type as $k => $v) $res .= ",'$k' => '$v'";
  return 'array(' . substr($res,1) . ')';
}

// http://doc.spip.org/@calculer_order
function calculer_order(&$boucle)
{
	if (!$order = $boucle->order
	AND !$order = $boucle->default_order)
		$order = array();

	/*if (isset($boucle->modificateur['collate'])){
		$col = "." . $boucle->modificateur['collate'];
		foreach($order as $k=>$o)
			if (strpos($order[$k],'COLLATE')===false)
				$order[$k].= $col;
	}*/
	return join(', ', $order);
}

//
// Code specifique aux criteres {1,n} {n/m} etc
//
// http://doc.spip.org/@calculer_parties
function calculer_parties($boucle, $id_boucle, $count) {

	$partie = $boucle->partie;
	$mode_partie = $boucle->mode_partie;
	$total_parties = $boucle->total_parties;

	// Notes :
	// $debut_boucle et $fin_boucle sont les indices SQL du premier
	// et du dernier demandes dans la boucle : 0 pour le premier,
	// n-1 pour le dernier ; donc total_boucle = 1 + debut - fin

	// nombre total avant partition
	$retour = "\n\n	// PARTITION\n\t" . '$nombre_boucle = @' . $count .';';

	preg_match(",([+-/p])([+-/])?,", $mode_partie, $regs);
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
	// {pagination}
	elseif ($op1 == 'p') {
		$retour .= "\n	"
			. '$debut_boucle = ' . $partie . ';';
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
		.'$Numrows[\''.$id_boucle.'\']["grand_total"] = $nombre_boucle;'
		. "\n	"
		.'$Numrows[\''.$id_boucle.'\']["total"] = max(0,$fin_boucle - $debut_boucle + 1);';

	return $retour;
}

// Production du code PHP a partir de la sequence livree par le phraseur
// $boucles est passe par reference pour affectation par index_pile.
// Retourne une expression PHP,
// (qui sera argument d'un Return ou la partie droite d'une affectation).

// http://doc.spip.org/@calculer_liste
function calculer_liste($tableau, $descr, &$boucles, $id_boucle='') {
	if (!$tableau) return "''";
	if (!isset($descr['niv'])) $descr['niv'] = 0;
	$codes = compile_cas($tableau, $descr, $boucles, $id_boucle);
	$n = count($codes);
	if (!$n) return "''";
	$tab = str_repeat("\t", $descr['niv']);
	if (_request('var_mode_affiche') != 'validation') {
		if ($n==1) 
			return $codes[0];
		else {
			$res = '';
			foreach($codes as $code) {
				if (!preg_match("/^'[^']*'$/", $code)
				OR substr($res,-1,1)!=="'")
				  $res .=  " .\n$tab$code";
				else {
				  $res = substr($res,0,-1) . substr($code,1);
				}
			}
			return '(' . substr($res,2+$descr['niv']) . ')';
		}
	} else {
	  $nom = $descr['nom'] . $id_boucle .  ($descr['niv']?$descr['niv']:'');
	  return "join('', array_map('array_shift', \$GLOBALS['debug_objets']['sequence']['$nom'] = array(" .  join(" ,\n$tab", $codes) . ")))";
	}
}

define('_REGEXP_COND_VIDE_NONVIDE',"/^[(](.*)[?]\s*''\s*:\s*('[^']+')\s*[)]$/");
define('_REGEXP_COND_NONVIDE_VIDE',"/^[(](.*)[?]\s*('[^']+')\s*:\s*''\s*[)]$/");
define('_REGEXP_CONCAT_NON_VIDE', "/^(.*)[.]\s*'[^']+'\s*$/");

// http://doc.spip.org/@compile_cas
function compile_cas($tableau, $descr, &$boucles, $id_boucle) {
        $codes = array();
	// cas de la boucle recursive
	if (is_array($id_boucle)) 
	  $id_boucle = $id_boucle[0];
	$type = !$id_boucle ? '' : $boucles[$id_boucle]->type_requete;
	$tab = str_repeat("\t", ++$descr['niv']);
	$mode = _request('var_mode_affiche');
	// chaque commentaire introduit dans le code doit commencer
	// par un caractere distinguant le cas, pour exploitation par debug.
	foreach ($tableau as $p) {

		switch($p->type) {
		// texte seul
		case 'texte':
			$code = "'".str_replace(array("\\","'"),array("\\\\","\\'"), $p->texte)."'";

			$commentaire= strlen($p->texte) . " signes";
			$avant='';
			$apres='';
			$altern = "''";
			break;

		case 'polyglotte':
			$code = "";
			foreach($p->traductions as $k => $v) {
			  $code .= ",'" .
			    str_replace(array("\\","'"),array("\\\\","\\'"), $k) .
			    "' => '" .
			    str_replace(array("\\","'"),array("\\\\","\\'"), $v) .
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
			$p->descr = $descr;
			$code = calculer_inclure($p, $boucles, $id_boucle);
			
			$commentaire = '<INCLURE ' . addslashes(str_replace("\n", ' ', $code)) . '>';
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
			  str_replace("-","_", $nom) . $descr['nom'] .
			  '($Cache, $Pile, $doublons, $Numrows, $SP)';
			$commentaire= "?$nom";
			$avant = calculer_liste($p->avant,
				$newdescr, $boucles, $id_boucle);
			$apres = calculer_liste($p->apres,
				$newdescr, $boucles, $id_boucle);
			$newdescr['niv']--;
			$altern = calculer_liste($p->altern,
				$newdescr, $boucles, $id_boucle);
			if (!$boucles[$nom]->milieu
			AND $boucles[$nom]->type_requete <> 'boucle') {
				if ($altern != "''") $code .= "\n. $altern";
				if ($avant<>"''" OR $apres<>"''")
					spip_log("boucle $nom toujours vide, code superflu dans $id");
				$avant = $apres = $altern = "''";
			} else if ($altern != "''") $altern = "($altern)";

			break;

		case 'idiome':
			$l = array();
			foreach ($p->arg as $k => $v) {
				if ($k) $l[]=$k.' => '.calculer_liste($v,$p->descr,$boucles,$id_boucle);
			}
			$l = !$l ? '' : (",array(".implode(', ',$l).")");
			$code = "_T('" . $p->module . ":" .$p->nom_champ . "'$l)";
			if ($p->param) {
				$p->id_boucle = $id_boucle;
				$p->boucles = &$boucles;
				$code = compose_filtres($p, $code);
			}
			$commentaire = ":";
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
			// Si la valeur est destinee a une comparaison a ''
			// forcer la conversion en une chaine par strval
			// si ca peut etre autre chose qu'une chaine
			if (($avant != "''" OR $apres != "''")
			AND $code[0]!= "'"
#			AND (strpos($code,'interdire_scripts') !== 0)
			AND !preg_match(_REGEXP_COND_VIDE_NONVIDE, $code)
			AND !preg_match(_REGEXP_COND_NONVIDE_VIDE, $code)
			AND !preg_match(_REGEXP_CONCAT_NON_VIDE, $code)) 
				$code = "strval($code)";
			break;

		default: 
		  // Erreur de construction de l'arbre de syntaxe abstraite
			$p->descr = $descr;
			erreur_squelette(_T('zbug_info_erreur_squelette'), $p);
		} // switch

		if ($code != "''") {
			$code = compile_retour($code, $avant, $apres, $altern, $tab, $descr['niv']);
			$codes[]= (($mode == 'validation') ?
				"array($code, '$commentaire', " . $p->ligne . ")"
				: (($mode == 'code') ?
				"\n// $commentaire\n$code" :
				$code));
		}
	} // foreach
	return $codes;
}

// production d'une expression conditionnelle ((v=EXP) ? (p . v .s) : a)
// mais si EXP est de la forme (t ? 'C' : '') on produit (t ? (p . C . s) : a)
// de meme si EXP est de la forme (t ? '' : 'C')

// http://doc.spip.org/@compile_retour
function compile_retour($code, $avant, $apres, $altern, $tab, $n)
{
	if ($avant == "''") $avant = '';
	if ($apres == "''") $apres = '';
	if (!$avant AND !$apres AND ($altern==="''")) return $code;

	if (preg_match(_REGEXP_CONCAT_NON_VIDE, $code)) {
		$t = $code;
		$cond = '';
	} elseif (preg_match(_REGEXP_COND_VIDE_NONVIDE,$code, $r)) {
		$t = $r[2];
		$cond =  '!' . $r[1];
	} else if  (preg_match(_REGEXP_COND_NONVIDE_VIDE,$code, $r)) {
		$t = $r[2];
		$cond = $r[1];
	} else {
		$t = '$t' . $n;
		$cond = "($t = $code)!==''";
	}

	$res = (!$avant ? "" : "$avant . ") . 
		$t .
		(!$apres ? "" : " . $apres");

	if ($res !== $t) $res = "($res)";
	return !$cond ? $res : "($cond ?\n\t$tab$res :\n\t$tab$altern)";
}


function compile_inclure_doublons($lexemes)
{
	foreach($lexemes as $v)
	  if($v->type === 'include') 
	    foreach($v->param as $r) 
	      if (trim($r[0]) === 'doublons') 
		return true;
	return false;
}

// Prend en argument le texte d'un squelette, le nom de son fichier d'origine,
// sa grammaire et un nom. Retourne False en cas d'erreur,
// sinon retourne un tableau de fonctions PHP compilees a evaluer,
// notamment une fonction portant ce nom et calculant une page.
// Pour appeler la fonction produite, lui fournir 2 tableaux de 1 e'le'ment:
// - 1er: element 'cache' => nom (du fichier ou` mettre la page)
// - 2e: element 0 contenant un environnement ('id_article => $id_article, etc)
// Elle retournera alors un tableau de 5 e'le'ments:
// - 'texte' => page HTML, application du squelette a` l'environnement;
// - 'squelette' => le nom du squelette
// - 'process_ins' => 'html' ou 'php' selon la pre'sence de PHP dynamique
// - 'invalideurs' =>  de'pendances de cette page, pour invalider son cache.
// - 'entetes' => tableau des entetes http
// En cas d'erreur, elle retournera un tableau des 2 premiers elements seulement

// http://doc.spip.org/@public_compiler_dist
function public_compiler_dist($squelette, $nom, $gram, $sourcefile, $connect=''){
	// Pre-traitement : reperer le charset du squelette, et le convertir
	// Bonus : supprime le BOM
	include_spip('inc/charsets');
	$squelette = transcoder_page($squelette);

	$descr = array('nom' => $nom,
			'gram' => $gram,
			'sourcefile' => $sourcefile,
			'squelette' => $squelette);

	// Phraser le squelette, selon sa grammaire

	$boucles = array();
	$f = charger_fonction('phraser_' . $gram, 'public');

	$squelette = $f($squelette, '', $boucles, $descr);

	return compiler_squelette($squelette, $boucles, $nom, $descr, $sourcefile, $connect);
}

// Point d'entree pour arbre de syntaxe abstraite fourni en premier argument
// Autres specifications comme ci-dessus

function compiler_squelette($squelette, $boucles, $nom, $descr, $sourcefile, $connect=''){
	global $tables_jointures;
	static $trouver_table;
	spip_timer('calcul_skel');

	if (isset($GLOBALS['var_mode']) AND $GLOBALS['var_mode'] == 'debug') {
		$GLOBALS['debug_objets']['squelette'][$nom] = $descr['squelette'];
		$GLOBALS['debug_objets']['sourcefile'][$nom] = $sourcefile;

		if (!isset($GLOBALS['debug_objets']['principal']))
			$GLOBALS['debug_objets']['principal'] = $nom;
	}
	foreach ($boucles as $id => $boucle) {
		$GLOBALS['debug_objets']['boucle'][$nom.$id] = $boucle;
	}
	$descr['documents'] = compile_inclure_doublons($squelette);

	// Demander la description des tables une fois pour toutes
	// et reperer si les doublons sont demandes
	// pour un inclure ou une boucle document
	// c'est utile a la fonction champs_traitements
	if (!$trouver_table)
		$trouver_table = charger_fonction('trouver_table', 'base');

	foreach($boucles as $id => $boucle) {
		if (!($type = $boucle->type_requete)) continue;
		if (!$descr['documents'] AND (
			(($type == 'documents') AND $boucle->doublons) OR
				compile_inclure_doublons($boucle->avant) OR
				compile_inclure_doublons($boucle->apres) OR
				compile_inclure_doublons($boucle->milieu) OR
				compile_inclure_doublons($boucle->altern)))
			$descr['documents'] = true;  
		if ($type != 'boucle') {
			if (!$boucles[$id]->sql_serveur AND $connect)
				$boucles[$id]->sql_serveur = $connect;
			$show = $trouver_table($type, $boucles[$id]->sql_serveur);
			// si la table n'existe pas avec le connecteur par defaut, 
			// c'est peut etre une table qui necessite son connecteur dedie fourni
			// permet une ecriture allegee (GEO) -> (geo:GEO)
			if (!$show AND $show=$trouver_table($type, $type))
				$boucles[$id]->sql_serveur = $type;
			if ($show) {
				$boucles[$id]->show = $show;
				// recopie les infos les plus importantes
				$boucles[$id]->primary = $show['key']["PRIMARY KEY"];
				$boucles[$id]->id_table = $x = $show['id_table'];
				$boucles[$id]->from[$x] = $nom_table = $show['table'];

				$boucles[$id]->descr = &$descr;
				if ((!$boucles[$id]->jointures)
				AND (isset($tables_jointures[$nom_table])) 
				AND is_array($x = $tables_jointures[$nom_table]))
					$boucles[$id]->jointures = $x;
			} else {
				// Pas une erreur si la table est optionnelle
				if ($boucles[$id]->table_optionnelle)
					$boucles[$id]->type_requete = '';
				else  {
					$boucles[$id]->type_requete = false;
					$boucle = $boucles[$id];
					$x = (!$boucle->sql_serveur ? '' :
					      ($boucle->sql_serveur . ":")) .
					  $type;
					$msg = array('zbug_table_inconnue',
							array('table' => $x));
					erreur_squelette($msg, $boucle);
				}
			}
		}
	}

	// Commencer par reperer les boucles appelees explicitement 
	// car elles indexent les arguments de maniere derogatoire
	foreach($boucles as $id => $boucle) { 
		if ($boucle->type_requete == 'boucle') {
			$boucles[$id]->descr = &$descr;
			$rec = &$boucles[$boucle->param[0]];
			if (!$rec) {
				$msg = array('zbug_boucle_recursive_undef',
					array('nom' => $boucle->param[0]));
				erreur_squelette($msg, $boucle);
				$boucles[$id]->type_requete = false;
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
		$id = strval($id); // attention au type dans index_pile
		$type = $boucle->type_requete;
		if ($type AND $type != 'boucle') {
			if ($boucle->param) {
				$res = calculer_criteres($id, $boucles);
			}
			$descr['id_mere'] = $id;
			$boucles[$id]->return =
			  calculer_liste($boucle->milieu,
					 $descr,
					 $boucles,
					 $id);
			// Si les criteres se sont mal compiles
			// laisser tomber la suite
			if (is_array($res))
				$boucles[$id]->type_requete = false;
		}
	}

	// idem pour la racine
	$descr['id_mere'] = '';
	$corps = calculer_liste($squelette, $descr, $boucles);
	$debug = (isset($GLOBALS['var_mode']) AND $GLOBALS['var_mode']=='debug');

	if ($debug) {
		include_spip('public/decompiler');
		include_spip('public/format_' . _EXTENSION_SQUELETTES);
	}
	// Calcul du corps de toutes les fonctions PHP,
	// en particulier les requetes SQL et TOTAL_BOUCLE
	// de'terminables seulement maintenant

	foreach($boucles as $id => $boucle) {
		$boucle = $boucles[$id] = pipeline('pre_boucle', $boucle);

		// appeler la fonction de definition de la boucle
		$req = $boucle->type_requete;
		if ($req) {
			$f = 'boucle_'.strtoupper($req);
		// si pas de definition perso, definition spip
			if (!function_exists($f)) $f = $f.'_dist';
			// laquelle a une definition par defaut
			if (!function_exists($f)) $f = 'boucle_DEFAUT';
			if (!function_exists($f)) $f = 'boucle_DEFAUT_dist';
			$req = $f($id, $boucles);
		} else $req = "\n\treturn '';";
		$boucles[$id]->return = 
			"function BOUCLE" . strtr($id,"-","_") . $nom .
			'(&$Cache, &$Pile, &$doublons, &$Numrows, $SP) {' .
			"\n\n\tstatic \$connect = " .
			_q($boucles[$id]->sql_serveur) .
			";" .
			$req .
			"\n}\n\n";

		if ($debug)
			$GLOBALS['debug_objets']['code'][$nom.$id] = $boucles[$id]->return;
	}

	// Au final, si un critere au moins s'est mal compile
	// retourner False, sinon inserer leur decompilation
	
	foreach($boucles as $id => $boucle) {
		if ($boucle->type_requete === false) return false;
		$boucle->return = "\n\n/* BOUCLE " .
			$boucle->type_requete .
			" " .
			(!$debug ? '' : 
			str_replace('*/', '* /', 
				decompiler_criteres($boucle->param, 
						$boucle->criteres))) .
			" */\n\n " .
			$boucle->return;
	}

	$secondes = spip_timer('calcul_skel');
	spip_log("COMPIL ($secondes) [$sourcefile] $nom.php");

	// Assimiler la fct principale a une boucle anonyme, c'est plus simple
	$code = new Boucle;
	$code->descr = $descr;
	$code->return = '
//
// Fonction principale du squelette ' . 
	$sourcefile . 
	($connect ? " pour $connect" : '') . 
	(!CODE_COMMENTE ? '' : "\n// Temps de compilation total: $secondes") .
	"\n//" .
	(!$debug ? '' : ("\n/*\n" . 
			str_replace('*/', '* /', public_decompiler($squelette)) 
				      . "\n*/")) . "

function " . $nom . '($Cache, $Pile, $doublons=array(), $Numrows=array(), $SP=0) {

'
	// reporter de maniere securisee les doublons inclus
.'
	if (isset($Pile[0]["doublons"]) AND is_array($Pile[0]["doublons"]))
		$doublons = nettoyer_env_doublons($Pile[0]["doublons"]);

	$connect = ' .
	_q($connect) . ';
	$page = ' .
	// ATTENTION, le calcul de l'expression $corps affectera $Cache
	// c'est pourquoi on l'affecte a la variable auxiliaire $page. 
	// avant de referencer $Cache
	$corps . ";

	return analyse_resultat_skel(".var_export($nom,true)
		.", \$Cache, \$page, ".var_export($sourcefile,true).");
}";

	$boucles[''] = $code;
	return $boucles;
}

?>
