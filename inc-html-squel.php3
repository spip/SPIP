<?php

// Ce fichier ne sera execute qu'une fois
if (defined("_INC_HTML_SQUEL")) return;
define("_INC_HTML_SQUEL", "1");

# Ce fichier doit IMPERATIVEMENT contenir la fonction "parser"
# qui transforme un squelette en un tableau d'objets de classe Boucle
# il est charge par un include calcule dans inc-calcul-squel
# pour permettre differentes syntaxes en entree

define(NOM_DE_BOUCLE, "[0-9]+|[-_][-_.a-zA-Z0-9]*");
define(NOM_DE_CHAMP, "#((" . NOM_DE_BOUCLE . "):)?([A-Z_]+)(\*?)");
define(CHAMP_ETENDU, '\[([^]\[]*)\(' . NOM_DE_CHAMP . '([^]\[)]*)\)([^]\[]*)\]');
define(PARAM_DE_BOUCLE,'\{[^}]*\}');
define(TYPE_DE_BOUCLE, "[^)]*");
define(BALISE_DE_BOUCLE,
	"^<BOUCLE(" .
	NOM_DE_BOUCLE .
	')[[:space:]]*\((' .
	TYPE_DE_BOUCLE .
	')\)[[:space:]]*(([[:space:]]*' .
	PARAM_DE_BOUCLE .
	')*)[[:space:]]*>');
define(BALISE_INCLURE,"<INCLU[DR]E[[:space:]]*\(([-_0-9a-zA-Z./ ]+)\)([^>]*)>");

define(DEBUT_DE_BOUCLE,'/<B('.NOM_DE_BOUCLE.')>.*?<BOUCLE\1[^-_.a-zA-Z0-9]|<BOUCLE('.NOM_DE_BOUCLE.')/ms');	# preg


function parser_texte($texte) {
	while (ereg(BALISE_INCLURE, $texte, $match)) {
		$s = $match[0];
		$p = strpos($texte, $s);
		$debut = substr($texte, 0, $p);
		$texte = substr($texte, $p + strlen($s));

		if ($debut) {
			$champ = new Texte;
			$champ->texte = $debut;
			$result[] = $champ;
		}
		$champ = new Inclure;
		$champ->fichier = $match[1];

		$p = trim($match[2]);
		if (!$p)
			$champ->params = '';
		else {
			if (!(ereg('^\\{(.*)\\}$', $p, $params))) {
				include_local("inc-admin.php3");
				erreur_squelette(_L("Param&egrave;tres d'inclusion incorrects"), $s);
			}
			else
				$champ->params = split("\}[[:space:]]*\{", $params[1]);
		}
		$result[] = $champ;
	}

	if ($texte) {
		$champ = new Texte;
		$champ->texte = $texte;
		$result[] = $champ;
	}

	return $result;
}

function parser_champs($texte) {
	$result=Array();
	while (ereg(NOM_DE_CHAMP . '(.*)$', $texte, $regs)) {
		$p = strpos($texte, $regs[0]);
		if ($p) {
			$result = array_merge($result,
					      parser_texte(substr($texte, 0, $p)));
		}

		$champ = new Champ;
		$champ->nom_boucle = $regs[2];
		$champ->nom_champ = $regs[3];
		$champ->etoile = $regs[4];
		$texte = $regs[5];
		$result[] = $champ;
	}
	if (!$texte)
		return $result;
	else
		return array_merge($result, parser_texte($texte));
}

// Gestion des imbrications:
// on cherches les [..] les plus internes et on les remplace par une  chaine
// %###N@ où N indexe un tableau comportant le résultat de leur phrasé
// on recommence tant qu'il y a des [...] en substituant à l'appel suivant

function parser_champs_etendus($debut) {
	$sep = '##';
	while (strpos($debut,$sep)!== false)
		$sep .= '#';
	return parser_champs_interieurs($debut, $sep, array());
}


function parser_champs_exterieurs($debut, $sep, $nested) {
	$res = array();
	while (($p=strpos($debut, "%$sep"))!==false) {
	    if ($p) $res = array_merge($res, 
					parser_champs(substr($debut,0,$p)));
	    ereg("^%$sep([0-9]+)@(.*)$", substr($debut,$p),$m);
	    $res[]= $nested[$m[1]];
	    $debut = $m[2];
	}
	if ($debut) $res = array_merge($res,parser_champs($debut));
	return $res;
}

function parser_champs_interieurs($texte, $sep, $result) {
	if (!$texte)
		return $result;

	$i = 1;
	while (true) {	  $j=$i;
	  while (ereg(CHAMP_ETENDU . '(.*)$', $texte, $regs)) {
		$champ = new Champ;
		$champ->nom_boucle = $regs[3];
		$champ->nom_champ = $regs[4];
		$champ->etoile = $regs[5];
		$champ->cond_avant = parser_champs_exterieurs($regs[1],$sep,$result);
		$champ->cond_apres = parser_champs_exterieurs($regs[7],$sep,$result);
		$fonctions = $regs[6];

		if ($fonctions) {
			$fonctions = explode('|', ereg_replace("^\|", "", $fonctions));
			foreach($fonctions as $f) $champ->fonctions[]= $f;
		}

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
	  else return parser_champs_exterieurs($x, $sep, $result);}
}


function parser_param($params, &$result, $idb) {
	$params2 = Array();
	$i = 1;
	while (ereg('^[[:space:]]*\{[[:space:]]*([^ }])([^"}]*)(["}])(.*)$', $params, $args)) {
		if ($args[3] == "}") {
			$params = $args[4];
			ereg("^(.*[^ \t\n])[[:space:]]*$", $args[2], $m);
			$param = $args[1] . $m[1];
			if (($param == 'tout') OR ($param == 'tous')) {
				$result->tout = true;
			}
			else if ($param == 'plat') {
				$result->plat = true;
			}
			else
				$params2[] = $param;
		}
		else {
			if ($args[1] == '"') {
				if (!ereg("[[:space:]]*\}(.*)$", $params, $m))
					break;
				else {
					$params = $m[1];
					$result->separateur = 
					ereg_replace("'","\'",$args[2]);
				}
			}
			else {
				if (!ereg("([^\"]*\"[[:space:]]*)\}(.*)$", $args[4], $m))
					break;
				else {
					$params = $m[2];
					$params2[] = $args[1] . $args[2] . '"' . $m[1];
				}
			}
		}
		$i++;
	}

	if ($params) {
		include_local("inc-admin.php3");
		erreur_squelette(_L("Param&egrave;tre $i (ou suivants) incorrect"), $params);
	}

	$result->param = $params2;
}

function parser($texte, $id_parent, &$boucles, $nom) {

	$all_res = array();

	while (preg_match(DEBUT_DE_BOUCLE, $texte, $regs)) {
		$nom_boucle = $regs[1].$regs[2];
		$p = strpos($texte, '<BOUCLE'.$nom_boucle);

		// envoyer la boucle au debugueur
		if ($GLOBALS['var_debug']) {
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
			include_local("inc-admin.php3");
			erreur_squelette((_T('erreur_boucle_syntaxe')), $milieu);
		}
		$id_boucle = $match[1];

		$result = new Boucle;
		$result->id_parent = $id_parent;
		$result->id_boucle = $id_boucle;

		$type = strtolower($match[2]);
		if ($type == 'sites') $type = 'syndication'; # alias

		//
		// Recuperer les criteres de la boucle (sauf boucle recursive)
		//
		if (substr($type, 0, 6) == 'boucle') {
			$result->type_requete = 'boucle';
			$result->param = substr($match[2], 6);
		} else {
			$result->type_requete = $type;
			parser_param($match[3], $result, $id_boucle);
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
			include_local("inc-admin.php3");
			erreur_squelette(_T('erreur_boucle_syntaxe'),
				$id_boucle . 
				_L('&nbsp;: balise B en aval'));
		}

		//
		// Recuperer la fin :
		//
		$s = "</BOUCLE$id_boucle>";
		$p = strpos($milieu, $s);
		if ($p === false) {
			include_local("inc-admin.php3");
			erreur_squelette(_T('erreur_boucle_syntaxe'),
					 _T('erreur_boucle_fermant',
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

		$result->cond_avant = parser($result->cond_avant, $id_parent,$boucles, $nom);
		$result->cond_apres = parser($result->cond_fin, $id_parent,$boucles, $nom);
		$result->cond_altern = parser($result->cond_altern,$id_parent,$boucles, $nom);
		$result->milieu = parser($milieu, $id_boucle,$boucles, $nom);

		$all_res = array_merge($all_res, parser_champs_etendus($debut));
		$all_res[] = $result;
		if ($boucles[$id_boucle]) {
			include_local("inc-admin.php3");
			erreur_squelette(_T('erreur_boucle_syntaxe'),
					 _T('erreur_boucle_double',
					 	array('id'=>$id_boucle)));
		} else
			$boucles[$id_boucle] = $result;
	}

	return array_merge($all_res, parser_champs_etendus($texte));
}

?>
