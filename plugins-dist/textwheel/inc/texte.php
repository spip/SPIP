<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/texte_mini');
include_spip('inc/lien');

include_spip('inc/textwheel');


defined('_AUTOBR')||define('_AUTOBR', "<br class='autobr' />");
define('_AUTOBR_IGNORER', _AUTOBR?"<!-- ig br -->":"");

// Avec cette surcharge, cette globale n'est plus définie, et du coup ça plante dans les plugins qui font un foreach dessus comme ZPIP
$GLOBALS['spip_raccourcis_typo'] = array();
if (!isset($GLOBALS['toujours_paragrapher']))
	$GLOBALS['toujours_paragrapher'] = true;

// class_spip : savoir si on veut class="spip" sur p i strong & li
// class_spip_plus : class="spip" sur les ul ol h3 hr quote table...
// la difference c'est que des css specifiques existent pour les seconds
//
if (!isset($GLOBALS['class_spip']))
	$GLOBALS['class_spip'] = '';
if (!isset($GLOBALS['class_spip_plus']))
	$GLOBALS['class_spip_plus'] = ' class="spip"';


/**
 * echapper les < script ...
 *
 * @param string $t
 * @return string
 */
function echappe_js($t) {
	static $wheel = null;

	if (!isset($wheel))
		$wheel = new TextWheel(
			SPIPTextWheelRuleset::loader($GLOBALS['spip_wheels']['echappe_js'])
		);

	return $wheel->text($t);
}

/**
 * paragrapher seulement
 *
 * @param string $t
 * @param null $toujours_paragrapher
 * @return string
 */
function paragrapher($t, $toujours_paragrapher = null) {
	static $wheel = array();
	if (is_null($toujours_paragrapher))
		$toujours_paragrapher = $GLOBALS['toujours_paragrapher'];

	if (!isset($wheel[$toujours_paragrapher])) {
		$ruleset = SPIPTextWheelRuleset::loader($GLOBALS['spip_wheels']['paragrapher']);
		if (!$toujours_paragrapher
		  AND $rule=$ruleset->getRule('toujours-paragrapher')) {
			$rule->disabled = true;
			$ruleset->addRules(array('toujours-paragrapher'=>$rule));
		}
		$wheel[$toujours_paragrapher] = new TextWheel($ruleset);
	}

	return $wheel[$toujours_paragrapher]->text($t);
}


/**
 * Securite : empecher l'execution de code PHP, en le transformant en joli code
 * dans l'espace prive, cette fonction est aussi appelee par propre et typo
 * si elles sont appelees en direct
 * il ne faut pas desactiver globalement la fonction dans l'espace prive car elle protege
 * aussi les balises des squelettes qui ne passent pas forcement par propre ou typo apres
 *
 * http://doc.spip.org/@interdire_scripts
 *
 * @param string $arg
 * @return string
 */
function interdire_scripts($arg) {
	// on memorise le resultat sur les arguments non triviaux
	static $dejavu = array();
	static $wheel = array();

	// Attention, si ce n'est pas une chaine, laisser intact
	if (!$arg OR !is_string($arg) OR !strstr($arg, '<')) return $arg;
	if (isset($dejavu[$GLOBALS['filtrer_javascript']][$arg])) return $dejavu[$GLOBALS['filtrer_javascript']][$arg];

	if (!isset($wheel[$GLOBALS['filtrer_javascript']])){
		$ruleset = SPIPTextWheelRuleset::loader(
			$GLOBALS['spip_wheels']['interdire_scripts']
		);
		// Pour le js, trois modes : parano (-1), prive (0), ok (1)
		// desactiver la regle echappe-js si besoin
		if ($GLOBALS['filtrer_javascript']==1
			OR ($GLOBALS['filtrer_javascript']==0 AND !test_espace_prive()))
			$ruleset->addRules (array('securite-js'=>array('disabled'=>true)));
		$wheel[$GLOBALS['filtrer_javascript']] = new TextWheel($ruleset);
	}

	$t = $wheel[$GLOBALS['filtrer_javascript']]->text($arg);

	// Reinserer les echappements des modeles
	if (defined('_PROTEGE_JS_MODELES'))
		$t = echappe_retour($t,"javascript"._PROTEGE_JS_MODELES);
	if (defined('_PROTEGE_PHP_MODELES'))
		$t = echappe_retour($t,"php"._PROTEGE_PHP_MODELES);

	return $dejavu[$GLOBALS['filtrer_javascript']][$arg] = $t;
}


/**
 * Typographie generale
 * avec protection prealable des balises HTML et SPIP
 *
 * http://doc.spip.org/@typo
 *
 * @param string $letexte
 * @param bool $echapper
 * @param null $connect
 * @param array $env
 * @return string
 */
function typo($letexte, $echapper=true, $connect=null, $env=array()) {
	// Plus vite !
	if (!$letexte) return $letexte;

	// les appels directs a cette fonction depuis le php de l'espace
	// prive etant historiquement ecrit sans argment $connect
	// on utilise la presence de celui-ci pour distinguer les cas
	// ou il faut passer interdire_script explicitement
	// les appels dans les squelettes (de l'espace prive) fournissant un $connect
	// ne seront pas perturbes
	$interdire_script = false;
	if (is_null($connect)){
		$connect = '';
		$interdire_script = true;
	}

	$echapper = ($echapper?'TYPO':false);
	// Echapper les codes <html> etc
	if ($echapper)
		$letexte = echappe_html($letexte, $echapper);

	//
	// Installer les modeles, notamment images et documents ;
	//
	// NOTE : propre() ne passe pas par ici mais directement par corriger_typo
	// cf. inc/lien

	$letexte = traiter_modeles($mem = $letexte, false, $echapper ? $echapper : '', $connect, null, $env);
	if (!$echapper AND $letexte != $mem) $echapper = '';
	unset($mem);

	$letexte = corriger_typo($letexte);
	$letexte = echapper_faux_tags($letexte);

	// reintegrer les echappements
	if ($echapper!==false)
		$letexte = echappe_retour($letexte, $echapper);

	// Dans les appels directs hors squelette, securiser ici aussi
	if ($interdire_script)
		$letexte = interdire_scripts($letexte);

	return $letexte;
}

// Correcteur typographique

define('_TYPO_PROTEGER', "!':;?~%-");
define('_TYPO_PROTECTEUR', "\x1\x2\x3\x4\x5\x6\x7\x8");

define('_TYPO_BALISE', ",</?[a-z!][^<>]*[".preg_quote(_TYPO_PROTEGER)."][^<>]*>,imsS");

/**
 * http://doc.spip.org/@corriger_typo
 *
 * @param string $t
 * @param string $lang
 * @return string
 */
function corriger_typo($t, $lang='') {
	static $typographie = array();
	// Plus vite !
	if (!$t) return $t;

	$t = pipeline('pre_typo', $t);

	// Caracteres de controle "illegaux"
	$t = corriger_caracteres($t);

	// Proteger les caracteres typographiques a l'interieur des tags html
	if (preg_match_all(_TYPO_BALISE, $t, $regs, PREG_SET_ORDER)) {
		foreach ($regs as $reg) {
			$insert = $reg[0];
			// hack: on transforme les caracteres a proteger en les remplacant
			// par des caracteres "illegaux". (cf corriger_caracteres())
			$insert = strtr($insert, _TYPO_PROTEGER, _TYPO_PROTECTEUR);
			$t = str_replace($reg[0], $insert, $t);
		}
	}

	// trouver les blocs multi et les traiter a part
	$t = extraire_multi($e = $t, $lang, true);
	$e = ($e === $t);

	// Charger & appliquer les fonctions de typographie
	$idxl = "$lang:" . (isset($GLOBALS['lang_objet'])? $GLOBALS['lang_objet']: $GLOBALS['spip_lang']);
	if (!isset($typographie[$idxl]))
		$typographie[$idxl] = charger_fonction(lang_typo($lang), 'typographie');
	$t = $typographie[$idxl]($t);

	// Les citations en une autre langue, s'il y a lieu
	if (!$e) $t = echappe_retour($t, 'multi');

	// Retablir les caracteres proteges
	$t = strtr($t, _TYPO_PROTECTEUR, _TYPO_PROTEGER);

	// pipeline
	$t = pipeline('post_typo', $t);

	# un message pour abs_url - on est passe en mode texte
	$GLOBALS['mode_abs_url'] = 'texte';

	return $t;
}


//
// Tableaux
//

define('_RACCOURCI_TH_SPAN', '\s*(:?{{[^{}]+}}\s*)?|<');

/**
 * http://doc.spip.org/@traiter_tableau
 *
 * @param sring $bloc
 * @return string
 */
function traiter_tableau($bloc) {
	// id "unique" pour les id du tableau
	$tabid = substr(md5($bloc),0,4);

	// Decouper le tableau en lignes
	preg_match_all(',([|].*)[|]\n,UmsS', $bloc, $regs, PREG_PATTERN_ORDER);
	$lignes = array();
	$debut_table = $summary = '';
	$l = 0;
	$numeric = true;

	// Traiter chaque ligne
	$reg_line1 = ',^(\|(' . _RACCOURCI_TH_SPAN . '))+$,sS';
	$reg_line_all = ',^('  . _RACCOURCI_TH_SPAN . ')$,sS';
	$hc = $hl = array();
	foreach ($regs[1] as $ligne) {
		$l ++;

		// Gestion de la premiere ligne :
		if ($l == 1) {
		// - <caption> et summary dans la premiere ligne :
		//   || caption | summary || (|summary est optionnel)
			if (preg_match(',^\|\|([^|]*)(\|(.*))?$,sS', rtrim($ligne,'|'), $cap)) {
				$l = 0;
				if ($caption = trim($cap[1]))
					$debut_table .= "<caption>".$caption."</caption>\n";
				$summary = ' summary="'.entites_html(trim($cap[3])).'"';
			}
		// - <thead> sous la forme |{{titre}}|{{titre}}|
		//   Attention thead oblige a avoir tbody
			else if (preg_match($reg_line1,	$ligne, $thead)) {
			  	preg_match_all('/\|([^|]*)/S', $ligne, $cols);
				$ligne='';$cols= $cols[1];
				$colspan=1;
				for($c=count($cols)-1; $c>=0; $c--) {
					$attr='';
					if($cols[$c]=='<') {
					  $colspan++;
					} else {
					  if($colspan>1) {
						$attr= " colspan='$colspan'";
						$colspan=1;
					  }
					  // inutile de garder le strong qui n'a servi que de marqueur 
					  $cols[$c] = str_replace(array('{','}'), '', $cols[$c]);
					  $ligne= "<th id='id{$tabid}_c$c'$attr>$cols[$c]</th>$ligne";
						$hc[$c] = "id{$tabid}_c$c"; // pour mettre dans les headers des td
					}
				}

				$debut_table .= "<thead><tr class='row_first'>".
					$ligne."</tr></thead>\n";
				$l = 0;
			}
		}

		// Sinon ligne normale
		if ($l) {
			// Gerer les listes a puce dans les cellules
			// on declenche simplement sur \n- car il y a les
			// -* -# -? -! (qui produisent des -&nbsp;!)
			if (strpos($ligne,"\n-")!==false)
				$ligne = traiter_listes($ligne);

			// tout mettre dans un tableau 2d
			preg_match_all('/\|([^|]*)/S', $ligne, $cols);

			// Pas de paragraphes dans les cellules
			foreach ($cols[1] as &$col) {
				if (strlen($col = trim($col))) {
					$col = preg_replace("/\n{2,}/S", "<br /> <br />", $col);
					if (_AUTOBR)
						$col = str_replace("\n", _AUTOBR."\n", $col);
				}
			}

			// assembler le tableau
			$lignes[]= $cols[1];
		}
	}

	// maintenant qu'on a toutes les cellules
	// on prepare une liste de rowspan par defaut, a partir
	// du nombre de colonnes dans la premiere ligne.
	// Reperer egalement les colonnes numeriques pour les cadrer a droite
	$rowspans = $numeric = array();
	$n = count($lignes[0]);
	$k = count($lignes);
	// distinguer les colonnes numeriques a point ou a virgule,
	// pour les alignements eventuels sur "," ou "."
	$numeric_class = array('.'=>'point',','=>'virgule');
	for($i=0;$i<$n;$i++) {
	  $align = true;
	  for ($j=0;$j<$k;$j++) {
		  $rowspans[$j][$i] = 1;
			if ($align AND preg_match('/^[+-]?(?:\s|\d)*([.,]?)\d*$/', trim($lignes[$j][$i]), $r)){
				if ($r[1])
					$align = $r[1];
			}
			else
				$align = '';
	  }
	  $numeric[$i] = $align ? (" class='numeric ".$numeric_class[$align]."'") : '';
	}
	for ($j=0;$j<$k;$j++) {
		if (preg_match($reg_line_all, $lignes[$j][0])) {
			$hl[$j] = "id{$tabid}_l$j"; // pour mettre dans les headers des td
		}
		else
			unset($hl[0]);
	}
	if (!isset($hl[0]))
		$hl = array(); // toute la colonne ou rien

	// et on parcourt le tableau a l'envers pour ramasser les
	// colspan et rowspan en passant
	$html = '';

	for($l=count($lignes)-1; $l>=0; $l--) {
		$cols= $lignes[$l];
		$colspan=1;
		$ligne='';

		for($c=count($cols)-1; $c>=0; $c--) {
			$attr= $numeric[$c];
			$cell = trim($cols[$c]);
			if($cell=='<') {
			  $colspan++;

			} elseif($cell=='^') {
			  $rowspans[$l-1][$c]+=$rowspans[$l][$c];

			} else {
			  if($colspan>1) {
				$attr .= " colspan='$colspan'";
				$colspan=1;
			  }
			  if(($x=$rowspans[$l][$c])>1) {
				$attr.= " rowspan='$x'";
			  }
			  $b = ($c==0 AND isset($hl[$l]))?'th':'td';
				$h = (isset($hc[$c])?$hc[$c]:'').' '.(($b=='td' AND isset($hl[$l]))?$hl[$l]:'');
				if ($h=trim($h))
					$attr.=" headers='$h'";
				// inutile de garder le strong qui n'a servi que de marqueur
				if ($b=='th') {
					$attr.=" id='".$hl[$l]."'";
					$cols[$c] = str_replace(array('{','}'), '', $cols[$c]);
				}
			  $ligne= "\n<$b".$attr.'>'.$cols[$c]."</$b>".$ligne;
			}
		}

		// ligne complete
		$class = alterner($l+1, 'odd', 'even');
		$html = "<tr class='row_$class $class'>$ligne</tr>\n$html";
	}
	return "\n\n<table".$GLOBALS['class_spip_plus'].$summary.">\n"
		. $debut_table
		. "<tbody>\n"
		. $html
		. "</tbody>\n"
		. "</table>\n\n";
}


/**
 * Traitement des listes
 * on utilise la wheel correspondante
 *
 * http://doc.spip.org/@traiter_listes
 *
 * @param string $t
 * @return string
 */
function traiter_listes ($t) {
	static $wheel = null;

	if (!isset($wheel))
		$wheel = new TextWheel(
			SPIPTextWheelRuleset::loader($GLOBALS['spip_wheels']['listes'])
		);

	return $wheel->text($t);
}


// Ces deux constantes permettent de proteger certains caracteres
// en les remplacanat par des caracteres "illegaux". (cf corriger_caracteres)

define('_RACCOURCI_PROTEGER', "{}_-");
define('_RACCOURCI_PROTECTEUR', "\x1\x2\x3\x4");

define('_RACCOURCI_BALISE', ",</?[a-z!][^<>]*[".preg_quote(_RACCOURCI_PROTEGER)."][^<>]*>,imsS");

/**
 * mais d'abord, une callback de reconfiguration des raccourcis
 * a partir de globales (est-ce old-style ? on conserve quand meme
 * par souci de compat ascendante)
 *
 * @param $ruleset
 */
function personnaliser_raccourcis(&$ruleset){
	if (isset($GLOBALS['debut_intertitre']) AND $rule=$ruleset->getRule('intertitres')){
		$rule->replace[0] = preg_replace(',<[^>]*>,Uims',$GLOBALS['debut_intertitre'],$rule->replace[0]);
		$rule->replace[1] = preg_replace(',<[^>]*>,Uims',$GLOBALS['fin_intertitre'],$rule->replace[1]);
		$ruleset->addRules(array('intertitres'=>$rule));
	}
	if (isset($GLOBALS['debut_gras']) AND $rule=$ruleset->getRule('gras')){
		$rule->replace[0] = preg_replace(',<[^>]*>,Uims',$GLOBALS['debut_gras'],$rule->replace[0]);
		$rule->replace[1] = preg_replace(',<[^>]*>,Uims',$GLOBALS['fin_gras'],$rule->replace[1]);
		$ruleset->addRules(array('gras'=>$rule));
	}
	if (isset($GLOBALS['debut_italique']) AND $rule=$ruleset->getRule('italiques')){
		$rule->replace[0] = preg_replace(',<[^>]*>,Uims',$GLOBALS['debut_italique'],$rule->replace[0]);
		$rule->replace[1] = preg_replace(',<[^>]*>,Uims',$GLOBALS['fin_italique'],$rule->replace[1]);
		$ruleset->addRules(array('italiques'=>$rule));
	}
	if (isset($GLOBALS['ligne_horizontale']) AND $rule=$ruleset->getRule('ligne-horizontale')){
		$rule->replace = preg_replace(',<[^>]*>,Uims',$GLOBALS['ligne_horizontale'],$rule->replace);
		$ruleset->addRules(array('ligne-horizontale'=>$rule));
	}
	if (isset($GLOBALS['toujours_paragrapher']) AND !$GLOBALS['toujours_paragrapher']
	  AND $rule=$ruleset->getRule('toujours-paragrapher')) {
		$rule->disabled = true;
		$ruleset->addRules(array('toujours-paragrapher'=>$rule));
	}
}

/**
 * Nettoie un texte, traite les raccourcis autre qu'URL, la typo, etc.
 *
 * http://doc.spip.org/@traiter_raccourcis
 *
 * @param string $t
 * @param bool $show_autobr
 * @return string
 */
function traiter_raccourcis($t, $show_autobr = false) {
	static $wheel, $notes;
	static $img_br_auto,$img_br_manuel,$img_br_no;

	// hack1: respecter le tag ignore br
	if (_AUTOBR_IGNORER
		AND strncmp($t, _AUTOBR_IGNORER, strlen(_AUTOBR_IGNORER))==0) {
		$ignorer_autobr = true;
		$t = substr($t, strlen(_AUTOBR_IGNORER));
	} else
		$ignorer_autobr = false;

	// Appeler les fonctions de pre_traitement
	$t = pipeline('pre_propre', $t);

	if (!isset($wheel)) {
		$ruleset = SPIPTextWheelRuleset::loader(
			$GLOBALS['spip_wheels']['raccourcis'],'personnaliser_raccourcis'
		);
		$wheel = new TextWheel($ruleset);

		if (_request('var_mode') == 'wheel'
		AND autoriser('debug')) {
			$f = $wheel->compile();
			echo "<pre>\n".htmlspecialchars($f)."</pre>\n";
			exit;
		}
		$notes = charger_fonction('notes', 'inc');
	}

	// Gerer les notes (ne passe pas dans le pipeline)
	list($t, $mes_notes) = $notes($t);

	$t = $wheel->text($t);

	// Appeler les fonctions de post-traitement
	$t = pipeline('post_propre', $t);

	if ($mes_notes)
		$notes($mes_notes,'traiter',$ignorer_autobr);

	// hack2: wrap des autobr dans l'espace prive, pour affichage css
	// car en css on ne sait pas styler l'element BR
	if ($ignorer_autobr AND _AUTOBR) {
		if (is_null($img_br_no))
			$img_br_no = ($show_autobr?http_img_pack("br-no-10.png",_T("tw:retour_ligne_ignore"),"class='br-no'",_T("tw:retour_ligne_ignore")):"");
		$t = str_replace(_AUTOBR, $img_br_no, $t);
	}
	if ($show_autobr AND _AUTOBR) {
		if (is_null($img_br_manuel))
			$img_br_manuel = http_img_pack("br-manuel-10.png",_T("tw:retour_ligne_manuel"),"class='br-manuel'",_T("tw:retour_ligne_manuel"));
		if (is_null($img_br_auto))
			$img_br_auto = http_img_pack("br-auto-10.png",_T("tw:retour_ligne_auto"),"class='br-auto'",_T("tw:retour_ligne_auto"));
		if (false !== strpos(strtolower($t), '<br')) {
			$t = preg_replace("/<br\b.*>/UiS", "$img_br_manuel\\0", $t);
			$t = str_replace($img_br_manuel._AUTOBR, $img_br_auto._AUTOBR, $t);
		}
	}

	return $t;
}


/**
 * Filtre a appliquer aux champs du type #TEXTE*
 * http://doc.spip.org/@propre
 *
 * @param string $t
 * @param string $connect
 * @param array $env
 * @return string
 */
function propre($t, $connect=null, $env=array()) {
	// les appels directs a cette fonction depuis le php de l'espace
	// prive etant historiquement ecrits sans argment $connect
	// on utilise la presence de celui-ci pour distinguer les cas
	// ou il faut passer interdire_script explicitement
	// les appels dans les squelettes (de l'espace prive) fournissant un $connect
	// ne seront pas perturbes
	$interdire_script = false;
	if (is_null($connect) AND test_espace_prive()){
		$connect = '';
		$interdire_script = true;
	}

	if (!$t) return strval($t);

	$t = echappe_html($t);
	$t = expanser_liens($t,$connect, $env);
	
	$t = traiter_raccourcis($t, (isset($env['wysiwyg']) AND $env['wysiwyg'])?true:false);
	$t = echappe_retour_modeles($t, $interdire_script);

	return $t;
}
?>
