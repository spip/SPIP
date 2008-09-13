<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
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

// http://doc.spip.org/@argumenter_inclure
function argumenter_inclure($struct, $descr, &$boucles, $id_boucle, $echap=true	, $lang = ''){
	$l = array();

	foreach($struct->param as $val) {
		$var = array_shift($val);
		if ($var == 'lang') {
			$lang = $val;
		} else
			$l[$var] = ($echap?"\'$var\' => ' . argumenter_squelette(":"'$var' => ")  .
			($val
				? calculer_liste($val[0], $descr, $boucles, $id_boucle)
				: index_pile($id_boucle, $var, $boucles)
			) . ($echap?") . '":" ");
	}
	// Cas particulier de la langue : si {lang=xx} est definie, on
	// la passe, sinon on passe la langue courante au moment du calcul
	// sauf si on n'en veut pas 
	if ($lang === false) return $l;

	$l['lang'] = ($echap?"\'lang\' => ' . argumenter_squelette(":"'lang' => ")  .
		($lang
			? calculer_liste($lang[0], $descr, $boucles, $id_boucle)
			: '$GLOBALS["spip_lang"]'
			) . ($echap?") . '":" ");
	return $l;
}

//
// Calculer un <INCLURE()>
//
// http://doc.spip.org/@calculer_inclure
function calculer_inclure($p, $descr, &$boucles, $id_boucle) {

	# Si pas raccourci <INCLURE{fond=xxx}> 
	# chercher le fichier, eventuellement en changeant.php3 => .php
	# et en gardant la compatibilite <INCLURE(page.php3)>
	if ($fichier = $p->texte) {
		if (preg_match(',^(.*[.]php)3$,', $fichier, $r)) {
			$fichier = $r[1];
		}
		if ($fichier == 'page.php') {
			$fichier = '';
		} else {
			$path = find_in_path($fichier);
			if (!$path) {
			spip_log("ERREUR: <INCLURE($fichier)> impossible");
			erreur_squelette(_T('zbug_info_erreur_squelette'),
				 "&lt;INCLURE($fichier)&gt; - "
				 ._T('fichier_introuvable', array('fichier' => $fichier)));
			return "'<!-- Erreur INCLURE(".texte_script($fichier).") -->'";
			}
		}
	}

	$_contexte = argumenter_inclure($p, $descr, $boucles, $id_boucle);

	// Critere d'inclusion {env} (et {self} pour compatibilite ascendante)
	if ($env = (isset($_contexte['env'])|| isset($_contexte['self']))) {
		unset($_contexte['env']);
	}

	// <INCLURE{doublons}>
	if (isset($_contexte['doublons'])) {
		// noter les doublons dans l'appel a public.php
		$_contexte['doublons'] = "\\'doublons\\' => '.var_export(\$doublons,true).'";
	}

	if ($ajax = isset($_contexte['ajax']))
		unset($_contexte['ajax']);

	$contexte = 'array(' . join(",\n\t", $_contexte) .')';
	if ($env) {
		$contexte = "array_merge('.var_export(\$Pile[0],1).',$contexte)";
	}

	$code = "\tinclude " .
		($fichier ? "\\'$path\\'" : ('_DIR_RESTREINT . "public.php"')).
		";";

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


	return "\n'<".
		"?php\n\t".'$contexte_inclus = '.$contexte.";\n"
		. $code
		. "\n?'." . "'>'";
}

//
// calculer_boucle() produit le corps PHP d'une boucle Spip. 
// ce corps remplit une variable $t0 retournee en valeur.
// Ici on distingue boucles recursives et boucle a requete SQL
// et on insere le code d'envoi au debusqueur du resultat de la fonction.

// http://doc.spip.org/@calculer_boucle
function calculer_boucle($id_boucle, &$boucles) {

	$boucles[$id_boucle] = pipeline('post_boucle', $boucles[$id_boucle]);

  if ($boucles[$id_boucle]->type_requete == 'boucle')  {
    $corps = calculer_boucle_rec($id_boucle, $boucles);
    $req = "";
    } else {
      $corps = calculer_boucle_nonrec($id_boucle, $boucles);
      // attention, ne calculer la requete que maintenant
      // car la fonction precedente appelle index_pile qui influe dessus
      $req =	(($init = $boucles[$id_boucle]->doublons) ?
			("\n\t$init = array();") : '') .
		calculer_requete_sql($boucles[$id_boucle]);
    }
  $notrace = (_request('var_mode_affiche') != 'resultat');
  return $req . $corps 
	. ($notrace ? "" : "
		boucle_debug_resultat('$id_boucle', 'resultat', \$t0);")
	.  "\n	return \$t0;";
}

// compil d'une boucle recursive. 
// il suffit (ET IL FAUT) sauvegarder les valeurs des arguments passes par
// reference, car par definition un tel passage ne les sauvegarde pas

// http://doc.spip.org/@calculer_boucle_rec
function calculer_boucle_rec($id_boucle, &$boucles) {
	$nom = $boucles[$id_boucle]->param[0];
	return "\n\t\$save_numrows = (\$Numrows['$nom']);"
	. "\n\t\$t0 = " . $boucles[$id_boucle]->return . ";"
	. "\n\t\$Numrows['$nom'] = (\$save_numrows);";
}

// compil d'une boucle non recursive. 
// c'est un "while (fetch_sql)" dans le cas general,
// qu'on essaye d'optimiser un max.

// http://doc.spip.org/@calculer_boucle_nonrec
function calculer_boucle_nonrec($id_boucle, &$boucles) {

	$boucle = &$boucles[$id_boucle];
	$return = $boucle->return;
	$type_boucle = $boucle->type_requete;
	$primary = $boucle->primary;
	$constant = preg_match(CODE_MONOTONE, str_replace("\\'",'', $return));

	// Cas {1/3} {1,4} {n-2,1}...

	$flag_cpt = $boucle->mode_partie ||$boucle->cptrows;

	//
	// Creer le debut du corps de la boucle :
	//
	$corps = !$flag_cpt ? '' : "\n		\$Numrows['$id_boucle']['compteur_boucle']++;";

	if ($boucle->mode_partie)
		$corps .= "
		if (\$Numrows['$id_boucle']['compteur_boucle'] > \$debut_boucle) {
		if (\$Numrows['$id_boucle']['compteur_boucle']-1 > \$fin_boucle) break;\n";

	// Calculer les invalideurs si c'est une boucle non constante et si on
	// souhaite invalider ces elements
	if (!$constant AND $primary) {
		include_spip('inc/invalideur');
		if (function_exists($i = 'calcul_invalideurs'))
			$corps = $i($corps, $primary, $boucles, $id_boucle);
	}

	// faudrait expanser le foreach a la compil, car y en a souvent qu'un 
	// et puis faire un [] plutot qu'un "','."
	if ($boucle->doublons)
		$corps .= "\n\t\t\tforeach(" . $boucle->doublons . ' as $k) $doublons[$k] .= "," . ' .
		index_pile($id_boucle, $primary, $boucles)
		. "; // doublons\n";


	if (count($boucle->separateur))
	  $code_sep = ("'" . str_replace("'","\'",join('',$boucle->separateur)) . "'");

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
		$init = "\n	lang_select(\$GLOBALS['spip_lang']);";
		$fin = "\n	lang_select();";

		$corps .= 
		  (($boucle->lang_select != 'oui') ? 
			"\t\tif (!\$GLOBALS['forcer_lang'])\n\t " : '')
		  . "\t\tif (\$x = "
		  . index_pile($id_boucle, 'lang', $boucles)
		  . ') $GLOBALS["spip_lang"] = $x;';
	  }
	else {
		$init = '';
		$fin = '';
		// sortir les appels au traducteur (invariants de boucle)
		if (strpos($return, '?php') === false
		AND preg_match_all("/\W(_T[(]'[^']*'[)])/", $return, $r)) {
			$i = 1;
			foreach($r[1] as $t) {
				$init .= "\n\t\$l$i = $t;";
				$return = str_replace($t, "\$l$i", $return);
				$i++;
			}
		}
	}

	// gestion optimale des separateurs et des boucles constantes
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
     
	// Fin de parties
	if ($boucle->mode_partie) $corps .= "\n		}\n";

	$sql_args = '$result, ' . _q($boucle->sql_serveur);

	// si le corps est une constante, ne pas appeler le serveur N fois!
	if (preg_match(CODE_MONOTONE,str_replace("\\'",'',$corps), $r)) {
		if (!isset($r[2]) OR (!$r[2])) {
			if (!$boucle->numrows)
				return "\n\t\$t0 = '';";
			else
				$corps = "";
		} else {
			$boucle->numrows = true;
			$corps = "\n	".'for($x=$Numrows["'.$id_boucle.'"]["total"];$x>0;$x--)
			$t0 .= ' . $corps .';';
		}
	} else {

		$corps = $init . '
	$SP++;

	// RESULTATS
	while ($Pile[$SP] = @sql_fetch(' .
		  $sql_args .
		  ")) {\n$corps\n	}\n" .
		  $fin ;
	}

	$count = '';
	if (!$boucle->select) {
		if (!$boucle->numrows OR $boucle->limit OR $boucle_mode_partie OR $boucle->group)
			$count = '1';
		else $count = 'count(*)';
		$boucles[$id_boucle]->select[]= $count; 
	}

	if ($boucle->numrows OR $boucle->mode_partie) {
		if ($count == 'count(*)')
			$count = "array_shift(sql_fetch($sql_args))";
		else $count = "sql_count($sql_args)";
		$count = !$boucle->mode_partie
		  ? "\n\t\$Numrows['$id_boucle']['total'] = @intval($count);"
		  : calculer_parties($boucles, $id_boucle, $count);
	} else $count = '';
	
	return  $count .
		(!$flag_cpt  ? "" :
			"\n\t\$Numrows['$id_boucle']['compteur_boucle'] = 0;")
		. '
	$t0 = "";' .
		$corps .
		"\n\t@sql_free($sql_args);";
}


// http://doc.spip.org/@calculer_requete_sql
function calculer_requete_sql(&$boucle)
{
	if (!$boucle->select) return ""; // l'optimiseur a fait fort
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
	  . calculer_dec('$having', calculer_dump_array($boucle->having))
	  . "\n\t// REQUETE\n\t"
	  . '$result = calculer_select($select, $from, $type, $where, $join, $groupby, $orderby, $limit, $having, $table, $id, $connect);';
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
  	if (count($v)==3)
	  	$res .= ", '$k' => array($v[0], $v[1], $v[2])";
  	else
  		$res .= ", '$k' => array($v[0], $v[1])";
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
function calculer_parties($boucles, $id_boucle, $count) {

	$boucle = &$boucles[$id_boucle];
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
	} else return "@debug_sequence('$id_boucle', '" .
	  ($descr['nom']) .
	  "', " .
	  $descr['niv'] .
	  ",  array(" .
	  join(" ,\n$tab", $codes) . "))";
}

define('_REGEXP_COND_VIDE_NONVIDE',"/^[(](.*)[?]\s*''\s*:\s*('[^']+')\s*[)]$/");
define('_REGEXP_COND_NONVIDE_VIDE',"/^[(](.*)[?]\s*('[^']+')\s*:\s*''\s*[)]$/");

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
			$code = calculer_inclure($p, $descr, $boucles, $id_boucle);
			
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
			$args=array();
			foreach ($p->arg as $k => $v) {
			  if ($k) $args[]=$k.' => '.calculer_liste($v,$p->descr,$p->boucles,$p->id_boucle);
			}
			$code = "_T('" . $p->module . ":" .$p->nom_champ . "',array(".implode(', ',$args)."))";
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
			AND (strpos($code,'interdire_scripts') !== 0)
			AND !preg_match(_REGEXP_COND_VIDE_NONVIDE, $code)
			AND !preg_match(_REGEXP_COND_NONVIDE_VIDE, $code))
				$code = "strval($code)";
			break;

		default: 
		  erreur_squelette(_T('zbug_info_erreur_squelette'));
		} // switch

		if ($code != "''") {
			$code = compile_retour($code, $avant, $apres, $altern, $tab, $descr['niv']);
			$codes[]= (($mode == 'validation') ?
				"array(" . $p->ligne . ", '$commentaire', $code)"
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

function compile_retour($code, $avant, $apres, $altern, $tab, $n)
{
	if ($avant == "''") $avant = '';
	if ($apres == "''") $apres = '';
	if (!$avant AND !$apres AND ($altern==="''")) return $code;

	if (preg_match(_REGEXP_COND_VIDE_NONVIDE,$code, $r)) {
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
	return "($cond ?\n\t$tab$res :\n\t$tab$altern)";
}


// affichage du code produit

// http://doc.spip.org/@code_boucle
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
		strtr($pretty,"\r\n", "  ");

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

// http://doc.spip.org/@public_compiler_dist
function public_compiler_dist($squelette, $nom, $gram, $sourcefile, $connect=''){
	global $tables_jointures;
	static $trouver_table;
	if (!$trouver_table)
		$trouver_table = charger_fonction('trouver_table', 'base');

	// Pre-traitement : reperer le charset du squelette, et le convertir
	// Bonus : supprime le BOM
	include_spip('inc/charsets');
	$squelette = transcoder_page($squelette);

	// Informations sur le squelette
	$descr = array('nom' => $nom, 'sourcefile' => $sourcefile,
		'squelette' => $squelette);
	unset($squelette);

	// Phraser le squelette, selon sa grammaire
	// pour le moment: "html" seul connu (HTML+balises BOUCLE)
	$boucles = array();
	spip_timer('calcul_skel');


	$f = charger_fonction('phraser_'.$gram, 'public');

	$racine = $f($descr['squelette'], '', $boucles, $nom);

	// Demander la description des tables une fois pour toutes
	foreach($boucles as $id => $boucle) {
		$type = $boucle->type_requete;
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
				AND (is_array($x = $tables_jointures[$nom_table])))
					$boucles[$id]->jointures = $x;
			} else {
				$boucles[$id]->type_requete = '';
				$x = $boucles[$id]->sql_serveur;
				$x = $x ? "$x:$type" : $type;
				// ne pas renvoyer d'erreur si la table est optionnelle
				// declare par ? avant ) dans <BOUCLE_A(table ?)>
				if (!$boucles[$id]->table_optionnelle) {
					erreur_squelette(_T('zbug_table_inconnue',
								array('table' => $x )),
							 $id);
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
		if ($type AND $type != 'boucle') {
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
		$boucle = pipeline('pre_boucle', $boucle);

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

		if (isset($GLOBALS['var_mode']) AND $GLOBALS['var_mode'] == 'debug')
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
		."] $nom.php");

	$code = "<"."?php
/*
 * Squelette : $sourcefile
 * Date :      ".gmdate("D, d M Y H:i:s", @filemtime($sourcefile))." GMT
 * Compile :   ".gmdate("D, d M Y H:i:s", time())." GMT ($secondes)
 * " . (!$boucles ?  "Pas de boucle" :
	("Boucles :   " . join (', ', array_keys($boucles)))) ."
 */ " .
	  $code . '

//
// Fonction principale du squelette ' . $sourcefile . 
	  ($connect ? " pour $connect" : '') . ".
//
function " . $nom . '($Cache, $Pile, $doublons=array(), $Numrows=array(), $SP=0) {

'
	// reporter de maniere securisee les doublons inclus
.'
	if (is_array($Pile[0]["doublons"]))
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
}

?".">";

	if (isset($GLOBALS['var_mode']) AND $GLOBALS['var_mode'] == 'debug')
		squelette_debug_compile($nom, $sourcefile, $code, $descr['squelette']);
	return $code;

}

?>
