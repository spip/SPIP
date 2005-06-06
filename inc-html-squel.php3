<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


// Ce fichier ne sera execute qu'une fois
if (defined("_INC_HTML_SQUEL")) return;
define("_INC_HTML_SQUEL", "1");

# Ce fichier doit IMPERATIVEMENT contenir la fonction "phraser"
# qui transforme un squelette en un tableau d'objets de classe Boucle
# il est charge par un include calcule dans inc-calcul-squel
# pour permettre differentes syntaxes en entree

define('NOM_DE_BOUCLE', "[0-9]+|[-_][-_.a-zA-Z0-9]*");
# ecriture alambiquee pour rester compatible avec les hexadecimaux des vieux squelettes
define('NOM_DE_CHAMP', "#((" . NOM_DE_BOUCLE . "):)?(([A-F]*[G-Z_][A-Z_0-9]*)|[A-Z_]+)(\*?)");
define('CHAMP_ETENDU', '\[([^]\[]*)\(' . NOM_DE_CHAMP . '([^[)]*\)[^]\[]*)\]');
define('PARAM_DE_BOUCLE','[[:space:]]*[{][[:space:]]*([^{}]*([{][^[}]]*[}][^[}]]*)*)[[:space:]]*[}]');
define('TYPE_DE_BOUCLE', "[^)]*");
define('BALISE_DE_BOUCLE',
	"^<BOUCLE(" .
	NOM_DE_BOUCLE .
	')[[:space:]]*\((' .
	TYPE_DE_BOUCLE .
	')\)');
define('PARAM_INCLURE','^[[:space:]]*[{][[:space:]]*([_0-9a-zA-Z]+)[[:space:]]*(=)?');
define('BALISE_INCLURE','<INCLU[DR]E[[:space:]]*\(([^)]*)\)');
define('DEBUT_DE_BOUCLE','/<B('.NOM_DE_BOUCLE.')>.*?<BOUCLE\1[^-_.a-zA-Z0-9]|<BOUCLE('.NOM_DE_BOUCLE.')/ms');	# preg

function phraser_inclure($texte, $result) {
	while (ereg(BALISE_INCLURE, $texte, $match)) {
		$p = strpos($texte,$match[0]);
		if ($p) $result = phraser_idiomes(substr($texte, 0, $p), $result);

		$champ = new Inclure;
		$champ->texte = $match[1];
		$texte = substr($texte, $p+strlen($match[0]));
		// on assimile {var=val} a une liste de un argument sans fonction
		phraser_args($texte,">","",$result,$champ);
		foreach ($champ->param as $k => $v) {
		  $var = $v[1][0];
		  if ($var->type != 'texte')
			erreur_squelette(_T('zbug_parametres_inclus_incorrects'),
					 $match[0]);
		  else {
		    ereg("^([^=]*)(=)?(.*)$", $var->texte,$m);
		    if ($m[2]) {
		      $champ->param[$k][0] = $m[1];
		      $val = $m[3];
		      if (ereg('^[\'"](.*)[\'"]$', $val, $m)) $val = $m[1];
		      $champ->param[$k][1][0]->texte = $val;
		    }
		    else
		      $champ->param[$k] = array($m[1]);
		  }
		}
		$texte = substr($champ->apres,1);
		$champ->apres = "";
		$result[] = $champ;
	}
	return (($texte==="") ? $result : phraser_idiomes($texte, $result));
}

function phraser_polyglotte($texte,$result) {
	while (eregi('<multi>([^<]*)</multi>', $texte, $match)) {
		$p = strpos($texte, $match[0]);
		if ($p) {
			$champ = new Texte;
			$champ->texte = (substr($texte, 0, $p));
			$result[] = $champ;
		}

		$champ = new Polyglotte;
		$lang = '';
		$bloc = $match[1];
		$texte = substr($texte,$p+strlen($match[0]));
		while (preg_match("/^[[:space:]]*([^[{]*)[[:space:]]*[[{]([a-z_]+)[]}](.*)$/si", $bloc, $regs)) {
		  $trad = $regs[1];
		  if ($trad OR $lang) 
			$champ->traductions[$lang] = $trad;
		  $lang = $regs[2];
		  $bloc = $regs[3];
		}
		$champ->traductions[$lang] = $bloc;
		$result[] = $champ;
	}
	if ($texte!=="") {
			$champ = new Texte;
			$champ->texte = $texte;
			$result[] = $champ;
	}
	return $result;
}


function phraser_idiomes($texte,$result) {
	// Reperer les balises de traduction <:toto:>
	while (eregi("<:(([a-z0-9_]+):)?([a-z0-9_]+)((\|[^:>]*)?:>)", $texte, $match)) {
		$p = strpos($texte, $match[0]);
		if ($p) $result = phraser_champs(substr($texte, 0, $p),$result);
		$texte = substr($texte,$p+strlen($match[0]));
		$champ = new Idiome;
		$champ->nom_champ = strtolower($match[3]);
		$champ->module = $match[2] ? $match[2] : 'public/spip/ecrire';
		// pas d'imbrication pour les filtres sur langue
		phraser_args($match[5], ":", '', array(), $champ);
		$result[] = $champ;
	}
	if ($texte!=="")  $result = phraser_champs($texte,$result);
	return $result;
}

function phraser_champs($texte,$result) {
	while (ereg(NOM_DE_CHAMP . '(.*)$', $texte, $regs)) {
	  $p = strpos($texte, $regs[0]);

	  if ($regs[5] || (strpos($regs[6][0], "[0-9]") === false)) {
		if ($p)
			$result = phraser_polyglotte(substr($texte, 0, $p), $result);
		$champ = new Champ;
		$champ->nom_boucle = $regs[2];
		$champ->nom_champ = $regs[3];
		$champ->etoile = $regs[5];
		$texte = $regs[6];
		$result[] = $champ;
	  } else {
	    // faux champ
	    $result = phraser_polyglotte (substr($texte, 0, $p+1), $result);
	    $texte = (substr($texte, $p+1));
	  }
	}
	if ($texte!=="") $result = phraser_polyglotte($texte, $result);
	return $result;
}

// Gestion des imbrications:
// on cherche les [..] les plus internes et on les remplace par une chaine
// %###N@ ou N indexe un tableau comportant le resultat de leur analyse
// on recommence tant qu'il y a des [...] en substituant a l'appel suivant

function phraser_champs_etendus($texte, $result) {
	if ($texte==="") return $result;
	$sep = '##';
	while (strpos($texte,$sep)!== false)
		$sep .= '#';
	return array_merge($result, phraser_champs_interieurs($texte, $sep, array()));
}

//  Analyse les filtres d'un champ etendu et affecte le resultat
// renvoie la liste des lexemes d'origine augmentee
// de ceux trouves dans les arguments des filtres (rare)
// sert aussi aux arguments des includes et aux criteres de boucles
// Tres chevelu

function phraser_args($texte, $fin, $sep, $result, &$pointeur_champ) {
  $texte = ltrim($texte);
  while (($texte!=="") && strpos($fin, $texte[0]) === false) {
      preg_match(",^(\|?[^{)|]*)(.*)$,ms", $texte, $match);
      $suite = ltrim($match[2]);
      $fonc = $match[1];
      if ($fonc[0] == "|") $fonc = substr($fonc,1);
      $res = array(trim($fonc));
      $args = $suite;
      if ($suite[0] != '{')
	{ if (!$match[1]) {
	    erreur_squelette(_T('zbug_info_erreur_squelette'), $texte);
	    break;
	  }
	} else {
	$args = ltrim(substr($suite,1)); 
	$collecte = array();
	while ($args && $args[0] != '}') {

		if ($args[0] == '"')
			preg_match ('/^(")([^"]*)(")(.*)$/ms', $args, $regs);
		else if ($args[0] == "'")
			preg_match ("/^(')([^']*)(')(.*)$/ms", $args, $regs);
		else {
		  preg_match("/^([[:space:]]*)([^$fin,([{}]*([(\[{][^])}]*[])}])?[^$fin,}]*)([,}$fin].*)$/ms", $args, $regs);
		  if (!strlen($regs[2]))
		    {
		      erreur_squelette(_T('zbug_info_erreur_squelette'), $args);
		      $args = '';
		      exit;
		      }   
		}

		$arg = $regs[2];
		if (trim($regs[1])) {
			$champ = new Texte;
			$champ->texte = $arg;
			$champ->apres = $champ->avant = $regs[1];
			$result[] = $champ;
			$collecte[] = $champ;
			$args = ltrim($regs[count($regs)-1]);
		} else {
		  if (!ereg("^(.*)" . NOM_DE_CHAMP ."[{|]", $arg, $r)) {
		    $arg = phraser_champs_exterieurs($arg, $sep, $result);
		    $args = ltrim($regs[count($regs)-1]);
		    $collecte = array_merge($collecte, $arg);
		    $result = array_merge($result, $arg);
		  }
		  else {
		    $pred = $r[1];
		    $par = ',}';
		    if (ereg('(.*)\($', $pred, $m))
		      {$pred = $m[1]; $par =')';}
		    if ($pred) {
			$champ = new Texte;
			$champ->texte = $pred;
			$champ->apres = $champ->avant = "";
			$result[] = $champ;
			$collecte[] = $champ;
		    }
		    $rec = substr($args, strpos($r[0],$args)+strlen($r[0])-1);
		    $champ = new Champ;
		    $champ->nom_boucle = $r[3];
		    $champ->nom_champ = $r[4];
		    $champ->etoile = $r[6];
		    phraser_args($rec, $par, $sep, array(), $champ);
		    $args = $champ->apres ;
		    $champ->apres = '';
		    if ($par==')') $args = substr($args,1);
		    $collecte[] = $champ;
		    $result[] = $champ;
		  }
		}
		if ($args[0] == ',') {
		  $args = ltrim(substr($args,1));
		  if ($collecte)
		    {$res[] = $collecte; $collecte = array();}
		}

	}
	if ($collecte) {$res[] = $collecte; $collecte = array();}
	$args = substr($args,1);
      }
      $n = strlen($suite) - strlen($args);
      $pointeur_champ->param[] = $res;
      // pour les balises avec faux filtres qui boudent ce dur larbeur
      $pointeur_champ->fonctions[] = array($fonc, substr($suite, 0, $n));
      $texte = ltrim($args);
  }
  # laisser l'appelant virer le caractere fermant
  $pointeur_champ->apres = $texte;
  return $result;
}

function phraser_champs_exterieurs($debut, $sep, $nested) {
	$res = array();
	while (($p=strpos($debut, "%$sep"))!==false) {
	    if ($p) $res = phraser_inclure(substr($debut,0,$p), $res);
	    ereg("^%$sep([0-9]+)@(.*)$", substr($debut,$p),$m);
	    $res[]= $nested[$m[1]];
	    $debut = $m[2];
	}
	return (($debut==="") ?  $res : phraser_inclure($debut, $res));
}

function phraser_champs_interieurs($texte, $sep, $result) {
  $i = 0; // en fait count($result)
	while (true) {	  $j=$i;
	  while (ereg(CHAMP_ETENDU . '(.*)$', $texte, $regs)) {
		$champ = new Champ;
		$champ->nom_boucle = $regs[3];
		$champ->nom_champ = $regs[4];
		$champ->etoile = $regs[6];
		// phraser_args indiquera ou commence apres
		$result = phraser_args($regs[7], ")", $sep, $result, $champ);
		$champ->avant = phraser_champs_exterieurs($regs[1],$sep,$result);
		$champ->apres = phraser_champs_exterieurs(substr($champ->apres,1),$sep,$result);

		$p = strpos($texte, $regs[0]);
		if ($p) {$result[$i] = substr($texte,0,$p);$i++; }
		$result[$i] = $champ;
		$i++;
		$texte = $regs[8];
	  }
	  if ($texte!=="") {$result[$i] = $texte; $i++;}
	  $x ='';

	  while($j < $i) 
	    { $z= $result[$j]; 
	      if (is_object($z)) $x .= "%$sep$j@" ; else $x.=$z ;
	      $j++;}
	  if (ereg(CHAMP_ETENDU, $x)) $texte = $x;
	  else return phraser_champs_exterieurs($x, $sep, $result);}
}

// analyse des criteres de boucle, 

function phraser_criteres($params, &$result) {

	$args = array();
	$type = $result->type_requete;
	foreach($params as $v) {
		$var = $v[1][0];
		$param = ($var->type != 'texte') ? "" : $var->texte;
		if ((count($v) > 2) && (!eregi("[^A-Za-z]IN[^A-Za-z]",$param)))
		  {
// plus d'un argument:
// c'est soit le critere LIMIT debut,fin si ça se termine par un chiffre
// soit le critere PAR soit un critere perso
		       
			if (($var->type != 'texte') ||
			    (strpos("0123456789", $param[strlen($param)-1])
			     !== false))
			  $op = ',';
			else {
			  preg_match("/^([a-zA-Z][a-zA-Z0-9]*)[[:space:]]*(.*)$/ms", $param, $m);
			  $op = $m[1];
			  $v[1][0]->texte = $m[2];
			}
			array_shift($v);
			$crit = new Critere;
			$crit->op = $op;
			$crit->not = "";
			$crit->param = $v;
			$args[] = $crit;
		  } else {

		  if ($var->type != 'texte')
			  erreur_squelette('criteres','');
		  else {
	// traiter qq lexemes particuliers pour faciliter la suite

	// les separateurs
			if ($var->apres)
				$result->separateur[] = $param;
			elseif (($param == 'tout') OR ($param == 'tous'))
				$result->tout = true;
			elseif ($param == 'plat') 
				$result->plat = true;

	// Boucle hierarchie, analyser le critere id_article - id_rubrique
	// - id_syndic, afin, dans les cas autres que {id_rubrique}, de
	// forcer {tout} pour avoir la rubrique mere...

			elseif (($type == 'hierarchie') &&
				($param == 'id_article' OR $param == 'id_syndic'))
				$result->tout = true;
			elseif (($type == 'hierarchie') && ($param == 'id_rubrique'))
				{;}
			else { 
			  // pas d'emplacement statique, faut un dynamique
			  /// mais il y a 2 cas qui ont les 2 !
			  if (($param == 'unique') || ($param == 'doublons'))
			    {
			      // sera remplace ensuite par la bonne valeur
			      // mais il faut l'indiquer tout de suite
			      $result->doublons = true;
			      $param = 'doublons';
			    }
			  elseif ($param == 'recherche')
			    // meme chose (a cause de #nom_de_boucle:URL_*)
			      $result->hash = true;
			  if (ereg('^([0-9-]+)(/)([0-9-]+)$', $param, $m)) {
			    $crit = phraser_critere_infixe($m[1], $m[3],$v, '/', '', '');
			  } elseif (ereg('^(`?[A-Za-z_][A-Za-z_0-9]*\(?[A-Za-z_]*\)?`?)[[:space:]]*(\??)(!?)(<=?|>=?|==?|IN)[[:space:]]*"?([^<>=!"]*)"?$', $param, $m)) {
			    $crit = phraser_critere_infixe($m[1], $m[5],$v,
							   (($m[1] == 'lang_select') ? $m[1] : trim($m[4])),
							   $m[3], $m[2]);
		  } elseif (preg_match("/^([!]?)[[:space:]]*([A-Za-z_][A-Za-z_0-9]*)[[:space:]]*(\??)(.*)$/ism", $param, $m)) {
		  // contient aussi les comparaisons implicites !
			    array_shift($v);
			    if ($m[4])
			      $v[0][0]->texte = $m[4];
			    else {
			      array_shift($v[0]);
			      if (!$v[0]) array_shift($v);
			    }
			    $crit = new Critere;
			    $crit->op = $m[2];
			    $crit->param = $v;
			    $crit->not = $m[1];
			    $crit->cond = $m[3];
			  }
			  else
			    erreur_squelette(_T('zbug_critere_inconnu',
						array('critere' => $param)));
			  $args[] = $crit;
			}
		  }
		}
	}

	$result->criteres = $args;
}

function phraser_critere_infixe($arg1, $arg2, $args, $op, $not, $cond)
{
	$args[0] = new Texte;
	$args[0]->texte = $arg1;
	$args[0] = array($args[0]);
	$args[1][0]->texte = $arg2;
	$crit = new Critere;
	$crit->op = $op;
	$crit->not = $not;
	$crit->cond = $cond;
	$crit->param = $args;
	return $crit;
}

function phraser($texte, $id_parent, &$boucles, $nom) {

	$all_res = array();
	while (preg_match(DEBUT_DE_BOUCLE, $texte, $regs)) {
		$nom_boucle = $regs[1].$regs[2];
		$p = strpos($texte, '<BOUCLE'.$nom_boucle);

		// envoyer la boucle au debugueur
		if ($GLOBALS['var_mode']== 'debug') {
			$preg = "@<B($nom_boucle|OUCLE${nom_boucle}[^-_.a-zA-Z0-9][^>]*)>"
				. ".*</(BOUCLE|/?B)$nom_boucle>@ms";
			preg_match($preg, $texte, $match);
			boucle_debug ($nom_boucle, $nom, $match[0]);
		}

		//
		// Recuperer la partie principale de la boucle
		//
		$debut = substr($texte, 0, $p);
		$milieu = substr($texte, $p);
		if (!ereg(BALISE_DE_BOUCLE, $milieu, $match)) {
			erreur_squelette((_T('zbug_erreur_boucle_syntaxe')), $milieu);
		}
		$milieu = substr($milieu, strlen($match[0]));
		$id_boucle = $match[1];
		$result = new Boucle;
		$result->id_parent = $id_parent;
		$result->id_boucle = $id_boucle;

		$type = $match[2];
		if ($p =strpos($type, ':'))
		  {
		    $result->sql_serveur = substr($type,0,$p);
		    $type = substr($type,$p+1);
		  }
		$type = strtolower($type);
		if ($type == 'sites') $type = 'syndication'; # alias

		//
		// Recuperer les criteres de la boucle (sauf boucle recursive)
		//
		if (substr($type, 0, 6) == 'boucle') {
			$result->type_requete = 'boucle';
			$result->param[0] = substr($match[2], 6);
			$milieu = substr($milieu, strpos($milieu, '>'));
		} else {
			$result->type_requete = $type;
			phraser_args($milieu,">","",$all_res,$result);
			phraser_criteres($result->param, $result);
			$milieu = substr($result->apres,1);
			$result->apres = "";
		}
		//
		// Recuperer la partie conditionnelle avant
		//
		$s = "<B$id_boucle>";
		$p = strpos($debut, $s);
		if ($p !== false) {
			$result->avant = substr($debut, $p + strlen($s));
			$debut = substr($debut, 0, $p);
		}

		if (strpos($milieu, $s)) {
			erreur_squelette(_T('zbug_erreur_boucle_syntaxe'),
				$id_boucle . 
				_T('zbug_balise_b_aval'));
		}

		//
		// Recuperer la fin :
		//
		$s = "</BOUCLE$id_boucle>";
		$p = strpos($milieu, $s);
		if ($p === false) {
			erreur_squelette(_T('zbug_erreur_boucle_syntaxe'),
					 _T('zbug_erreur_boucle_fermant',
						array('id'=>$id_boucle)));
		}
		$texte = substr($milieu, $p + strlen($s));
		$milieu = substr($milieu, 0, $p);
		//
		// 1. Recuperer la partie conditionnelle apres
		//
		$s = "</B$id_boucle>";
		$p = strpos($texte, $s);
		if ($p !== false) {
			$result->apres = substr($texte, 0, $p);
			$texte = substr($texte, $p + strlen($s));
		}

		//
		// 2. Recuperer la partie alternative
		//
		$s = "<//B$id_boucle>";
		$p = strpos($texte, $s);
		if ($p !== false) {
			$result->altern = substr($texte, 0, $p);
			$texte = substr($texte, $p + strlen($s));
		}
		$result->avant = phraser($result->avant, $id_parent,$boucles, $nom);
		$result->apres = phraser($result->apres, $id_parent,$boucles, $nom);
		$result->altern = phraser($result->altern,$id_parent,$boucles, $nom);
		$result->milieu = phraser($milieu, $id_boucle,$boucles, $nom);

		$all_res = phraser_champs_etendus($debut, $all_res);
		$all_res[] = $result;
		if ($boucles[$id_boucle]) {
			erreur_squelette(_T('zbug_erreur_boucle_syntaxe'),
					 _T('zbug_erreur_boucle_double',
					 	array('id'=>$id_boucle)));
		} else
			$boucles[$id_boucle] = $result;
	}
	return phraser_champs_etendus($texte, $all_res);
}
?>
