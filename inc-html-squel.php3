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
define('NOM_DE_CHAMP', "#((" . NOM_DE_BOUCLE . "):)?([A-Z_]+)(\*?)");
define('CHAMP_ETENDU', '\[([^]\[]*)\(' . NOM_DE_CHAMP . '([^[)]*)\)([^]\[]*)\]');
define('PARAM_DE_BOUCLE','[[:space:]]*\{[[:space:]]*([^{}]*(\{[^\}]*\}[^\}]*)*)[[:space:]]*\}');
define('TYPE_DE_BOUCLE', "[^)]*");
define('BALISE_DE_BOUCLE',
	"^<BOUCLE(" .
	NOM_DE_BOUCLE .
	')[[:space:]]*\((' .
	TYPE_DE_BOUCLE .
	')\)((' .
	PARAM_DE_BOUCLE .
	')*)[[:space:]]*>');
define('PARAM_INCLURE','[[:space:]]*\{[[:space:]]*([_0-9a-zA-Z]+)[[:space:]]*(=[[:space:]]*([^\{\}]*(\{[^\}]*\}[^\} ]*)?))?[[:space:]]*\}');
define('BALISE_INCLURE',"<INCLU[DR]E[[:space:]]*\(" .
       '([-_0-9a-zA-Z./ ]+)' .
	'\)((' .
	PARAM_INCLURE .
	')*)[[:space:]]*>');
define('DEBUT_DE_BOUCLE','/<B('.NOM_DE_BOUCLE.')>.*?<BOUCLE\1[^-_.a-zA-Z0-9]|<BOUCLE('.NOM_DE_BOUCLE.')/ms');	# preg


function phraser_inclure($texte, $result) {
	while (($p=strpos($texte, '<INCLU')) !== false) {
		$fin = substr($texte, $p);

		if (!ereg('^' . BALISE_INCLURE, $fin, $match)) break;
		$s = $match[0];
		$debut = substr($texte, 0, $p);
		$texte = substr($fin, strlen($s));

		if ($debut) $result = phraser_idiomes($debut, $result);

		$champ = new Inclure;
		$champ->fichier = $match[1];
		$champ->params = array();
		$p = trim($match[2]);
		if ($p) {
			while (ereg('^' . PARAM_INCLURE . '(.*)$', $p, $m)) {
				$champ->params[$m[1]] = $m[3];
				$p = $m[5];
			}
			
			if ($p)
				erreur_squelette(_T('zbug_parametres_inclus_incorrects'),
				$s);
		}
		$result[] = $champ;
	}

	return (!$texte ? $result : phraser_idiomes($texte, $result));
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
		while (preg_match("/^[[:space:]]*([^[{]*)[[:space:]]*[\{\[]([a-z_]+)[\}\]](.*)$/si", $bloc, $regs)) {
		  $trad = $regs[1];
		  if ($trad OR $lang) 
			$champ->traductions[$lang] = $trad;
		  $lang = $regs[2];
		  $bloc = $regs[3];
		}
		$champ->traductions[$lang] = $bloc;
		$result[] = $champ;
	}
	if ($texte) {
			$champ = new Texte;
			$champ->texte = $texte;
			$result[] = $champ;
	}
	return $result;
}


function phraser_idiomes($texte,$result) {
	// Reperer les balises de traduction <:toto:>
	while (eregi("<:(([a-z0-9_]+):)?([a-z0-9_]+)(\|[^>]*)?:>", $texte, $match)) {
		$p = strpos($texte, $match[0]);
		if ($p) $result = phraser_champs(substr($texte, 0, $p),$result);
		$champ = new Idiome;
		$champ->chaine = strtolower($match[3]);
		$champ->module = $match[2] ? $match[2] : 'public/spip/ecrire';
		$champ->fonctions = phraser_filtres(substr($match[4],1));
		$texte = substr($texte,$p+strlen($match[0]));
		$result[] = $champ;
	}
	if ($texte)  $result = phraser_champs($texte,$result);
	return $result;
}

function phraser_champs($texte,$result) {
	while (ereg(NOM_DE_CHAMP . '(.*)$', $texte, $regs)) {
	  $p = strpos($texte, $regs[0]);

	  if ($regs[4] || (strpos($regs[5][0], "[0-9]") === false)) {
		if ($p)
			$result = phraser_polyglotte(substr($texte, 0, $p), $result);
		$champ = new Champ;
		$champ->nom_boucle = $regs[2];
		$champ->nom_champ = $regs[3];
		$champ->etoile = $regs[4];
		$texte = $regs[5];
		$result[] = $champ;
	  } else {
	    // faux champ
	    $result = phraser_polyglotte (substr($texte, 0, $p+1), $result);
	    $texte = (substr($texte, $p+1));
	  }
	}
	if ($texte) $result = phraser_polyglotte($texte, $result);
	return $result;
}

// Gestion des imbrications:
// on cherche les [..] les plus internes et on les remplace par une chaine
// %###N@ ou N indexe un tableau comportant le resultat de leur phrase
// on recommence tant qu'il y a des [...] en substituant a l'appel suivant

function phraser_champs_etendus($texte, $result) {
	if (!$texte) return $result;
	$sep = '##';
	while (strpos($texte,$sep)!== false)
		$sep .= '#';
	return array_merge($result, phraser_champs_interieurs($texte, $sep, array()));
}

function phraser_filtres($fonctions) {
  $r = array();
  if ($fonctions) {
    $fonctions = explode('|', ereg_replace("^\|", "", $fonctions));
    foreach($fonctions as $f) {
      ereg("^([^{]*)(.*)$",$f,$m);
      $arg = $m[2];
      $fonc = $m[1];
      $x = ereg("^\{(.*)\} *$",$arg,$m);
      $r[]= array($fonc, ($x ? $m[1] : $arg));
    }
  }
  return $r;
}

function phraser_champs_exterieurs($debut, $sep, $nested) {
	$res = array();
	while (($p=strpos($debut, "%$sep"))!==false) {
	    if ($p) $res = phraser_inclure(substr($debut,0,$p), $res);
	    ereg("^%$sep([0-9]+)@(.*)$", substr($debut,$p),$m);
	    $res[]= $nested[$m[1]];
	    $debut = $m[2];
	}
	return (!$debut ?  $res : phraser_inclure($debut, $res));
}

function phraser_champs_interieurs($texte, $sep, $result) {
	$i = 1;
	while (true) {	  $j=$i;
	  while (ereg(CHAMP_ETENDU . '(.*)$', $texte, $regs)) {

		$champ = new Champ;
		$champ->nom_boucle = $regs[3];
		$champ->nom_champ = $regs[4];
		$champ->etoile = $regs[5];
		$champ->cond_avant = phraser_champs_exterieurs($regs[1],$sep,$result);
		$champ->cond_apres = phraser_champs_exterieurs($regs[7],$sep,$result);
		$champ->fonctions = phraser_filtres($regs[6]);

		$p = strpos($texte, $regs[0]);
		if ($p) {$result[$i] = substr($texte,0,$p);$i++; }
		$result[$i] = $champ;
		$i++;
		$texte = $regs[8];
	  }
	  if ($texte) {$result[$i] = $texte; $i++;}
	  $x ='';

	  while($j < $i) 
	    { $z= $result[$j]; 
	      if (is_object($z)) $x .= "%$sep$j@" ; else $x.=$z ;
	      $j++;}
	  if (ereg(CHAMP_ETENDU, $x)) $texte = $x;
	  else return phraser_champs_exterieurs($x, $sep, $result);}
}


function phraser_param($params, &$result) {
	$params2 = array();
	$type = $result->type_requete;
	while (ereg('^' . PARAM_DE_BOUCLE . '[[:space:]]*(.*)$', trim($params), $m)) {
		$params = $m[3];
		$param = trim($m[1]);
		// cas d'un critere avec {...}
		if ($m[2]) {
			$params2[] = $param;
		}
	      // traiter qq lexemes particuliers pour faciliter la suite
		else if (strlen($param) < 2)
			$params2[] = $param;
		else if (($param == 'tout') OR ($param == 'tous'))
			$result->tout = true;
		else if ($param == 'plat') 
			$result->plat = true;
		else if ($param == 'unique')
			$params2[] = 'doublons';
		else {
			if ($type == 'hierarchie') {
	// Boucle hierarchie, analyser le critere id_article - id_rubrique
	// - id_syndic, afin, dans les cas autres que {id_rubrique}, de
	// forcer {tout} pour avoir la rubrique mere...
				if ($param == 'id_article' OR $param == 'id_syndic') {
					$result->tout = true;
					$param = "";
				} else if ($param == 'id_rubrique')
					$param = "";
			}
			// les separateurs (specs CSS3 aN+b a finaliser)
			if (ereg('^"([^"}]*)"( *, *(\-?[0-9]*)n)?(\+?([0-9]+))?)?$', $param, $args))
			  $result->separateur[] = $args[1];
			else {
			  if ($param) $params2[] = $param;
			}
		}
	}
	$result->param = $params2;
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
			$result->param = substr($match[2], 6);
		} else {
			$result->type_requete = $type;
			phraser_param($match[3], $result);
		}

		//
		// Recuperer la partie conditionnelle avant
		//
		$s = "<B$id_boucle>";
		$p = strpos($debut, $s);
		if ($p !== false) {
			$result->cond_avant = substr($debut, $p + strlen($s));
			$debut = substr($debut, 0, $p);
		}
		$milieu = substr($milieu, strlen($match[0]));
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
			$result->cond_fin = substr($texte, 0, $p);
			$texte = substr($texte, $p + strlen($s));
		}

		//
		// 2. Recuperer la partie alternative
		//
		$s = "<//B$id_boucle>";
		$p = strpos($texte, $s);
		if ($p !== false) {
			$result->cond_altern = substr($texte, 0, $p);
			$texte = substr($texte, $p + strlen($s));
		}

		$result->cond_avant = phraser($result->cond_avant, $id_parent,$boucles, $nom);
		$result->cond_apres = phraser($result->cond_fin, $id_parent,$boucles, $nom);
		$result->cond_altern = phraser($result->cond_altern,$id_parent,$boucles, $nom);
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
