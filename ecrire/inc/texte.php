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

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/texte_mini');
include_spip('inc/lien');

// init du tableau principal des raccourcis

global $spip_raccourcis_typo, $class_spip_plus, $ligne_horizontale, $debut_intertitre, $fin_intertitre, $debut_gras, $fin_gras, $debut_italique, $fin_italique, $ouvre_ref, $ferme_ref, $ouvre_note, $ferme_note;


if (is_null($ligne_horizontale)) $ligne_horizontale =  "\n<hr$class_spip_plus />\n";
if (is_null($debut_intertitre)) $debut_intertitre =  "\n<h3$class_spip_plus>";
if (is_null($fin_intertitre)) $fin_intertitre =  "</h3>\n";
if (is_null($debut_gras)) $debut_gras =  "<strong$class_spip>";
if (is_null($fin_gras)) $fin_gras =  '</strong>';
if (is_null($debut_italique)) $debut_italique =  "<i$class_spip>";
if (is_null($fin_italique)) $fin_italique =  '</i>';
if (is_null($ouvre_ref)) $ouvre_ref =  '&nbsp;[';
if (is_null($ferme_ref)) $ferme_ref =  ']';
if (is_null($ouvre_note)) $ouvre_note =  '[';
if (is_null($ferme_note)) $ferme_note =  '] ';


$spip_raccourcis_typo = array(
			      array(
		/* 4 */		"/(^|[^{])[{][{][{]/S",
		/* 5 */		"/[}][}][}]($|[^}])/S",
		/* 6 */ 	"/(( *)\n){2,}(<br\s*\/?".">)?/S",
		/* 7 */ 	"/[{][{]/S",
		/* 8 */ 	"/[}][}]/S",
		/* 9 */ 	"/[{]/S",
		/* 10 */	"/[}]/S",
		/* 11 */	"/(?:<br\s*\/?".">){2,}/S",
		/* 12 */	"/<p>\n*(?:<br\s*\/?".">\n*)*/S",
		/* 13 */	"/<quote>/S",
		/* 14 */	"/<\/quote>/S",
		/* 15 */	"/<\/?intro>/S"
				),
			      array(
		/* 4 */ 	"\$1\n\n" . $debut_intertitre,
		/* 5 */ 	$fin_intertitre ."\n\n\$1",
		/* 6 */ 	"<p>",
		/* 7 */ 	$debut_gras,
		/* 8 */ 	$fin_gras,
		/* 9 */ 	$debut_italique,
		/* 10 */	$fin_italique,
		/* 11 */	"<p>",
		/* 12 */	"<p>",
		/* 13 */	"<blockquote$class_spip_plus><p>",
		/* 14 */	"</blockquote><p>",
		/* 15 */	""
				)
);

// Raccourcis dependant du sens de la langue

function definir_raccourcis_alineas()
{
	global $ligne_horizontale;
	static $alineas = array();
	$x = _DIR_RESTREINT ? lang_dir() : lang_dir($GLOBALS['spip_lang']);
	if (!isset($alineas[$x])) {
        
		$alineas[$x] = array(
		array(
		/* 0 */ 	"/\n(----+|____+)/S",
		/* 1 */ 	"/\n-- */S",
		/* 2 */ 	"/\n- */S", /* DOIT rester a cette position */
		/* 3 */ 	"/\n_ +/S"
				),
		array(
		/* 0 */ 	"\n\n" . $ligne_horizontale . "\n\n",
		/* 1 */ 	"\n<br />&mdash;&nbsp;",
		/* 2 */ 	"\n<br />".definir_puce()."&nbsp;",
		/* 3 */ 	"\n<br />"
				)
		);
	}
	return $alineas[$x];
}


//
// Les elements de propre()
//

// afficher joliment les <script>
// http://doc.spip.org/@echappe_js
function echappe_js($t,$class=' class="echappe-js"') {
	if (preg_match_all(',<script.*?($|</script.),isS', $t, $r, PREG_SET_ORDER))
	foreach ($r as $regs)
		$t = str_replace($regs[0],
			"<code$class>".nl2br(htmlspecialchars($regs[0])).'</code>',
			$t);
	return $t;
}


// Securite : empecher l'execution de code PHP, en le transformant en joli code
// dans l'espace prive, cette fonction est aussi appelee par propre et typo
// si elles sont appelees en direct
// il ne faut pas desactiver globalement la fonction dans l'espace prive car elle protege
// aussi les balises des squelettes qui ne passent pas forcement par propre ou typo apres
// http://doc.spip.org/@interdire_scripts
function interdire_scripts($arg) {
	// on memorise le resultat sur les arguments non triviaux
	static $dejavu = array();

	// Attention, si ce n'est pas une chaine, laisser intact
	if (!$arg OR !is_string($arg) OR !strstr($arg, '<')) return $arg; 

	if (isset($dejavu[$GLOBALS['filtrer_javascript']][$arg])) return $dejavu[$GLOBALS['filtrer_javascript']][$arg];

	// echapper les tags asp/php
	$t = str_replace('<'.'%', '&lt;%', $arg);

	// echapper le php
	$t = str_replace('<'.'?', '&lt;?', $t);

	// echapper le < script language=php >
	$t = preg_replace(',<(script\b[^>]+\blanguage\b[^\w>]+php\b),UimsS', '&lt;\1', $t);

	// Pour le js, trois modes : parano (-1), prive (0), ok (1)
	switch($GLOBALS['filtrer_javascript']) {
		case 0:
			if (!_DIR_RESTREINT)
				$t = echappe_js($t);
			break;
		case -1:
			$t = echappe_js($t);
			break;
	}

	// pas de <base href /> svp !
	$t = preg_replace(',<(base\b),iS', '&lt;\1', $t);

	// Reinserer les echappements des modeles
	if (defined('_PROTEGE_JS_MODELES'))
		$t = echappe_retour($t,"javascript"._PROTEGE_JS_MODELES);
	if (defined('_PROTEGE_PHP_MODELES'))
		$t = echappe_retour($t,"php"._PROTEGE_PHP_MODELES);

	return $dejavu[$GLOBALS['filtrer_javascript']][$arg] = $t;
}

// Typographie generale
// avec protection prealable des balises HTML et SPIP


// http://doc.spip.org/@typo
function typo($letexte, $echapper=true, $connect=null) {
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

	// Echapper les codes <html> etc
	if ($echapper)
		$letexte = echappe_html($letexte, 'TYPO');

	//
	// Installer les modeles, notamment images et documents ;
	//
	// NOTE : propre() ne passe pas par ici mais directement par corriger_typo
	// cf. inc/lien

	$letexte = traiter_modeles($mem = $letexte, false, $echapper ? 'TYPO' : '', $connect);
	if ($letexte != $mem) $echapper = true;
	unset($mem);

	$letexte = corriger_typo($letexte);
	$letexte = echapper_faux_tags($letexte);

	// reintegrer les echappements
	if ($echapper)
		$letexte = echappe_retour($letexte, 'TYPO');

	// Dans les appels directs hors squelette, securiser ici aussi
	if ($interdire_script)
		$letexte = interdire_scripts($letexte);

	return $letexte;
}

// Correcteur typographique
define('_TYPO_PROTEGER', "!':;?~%-");
define('_TYPO_PROTECTEUR', "\x1\x2\x3\x4\x5\x6\x7\x8");

define('_TYPO_BALISE', ",</?[a-z!][^<>]*[".preg_quote(_TYPO_PROTEGER)."][^<>]*>,imsS");

// http://doc.spip.org/@corriger_typo
function corriger_typo($letexte, $lang='') {

	// Plus vite !
	if (!$letexte) return $letexte;

	$letexte = pipeline('pre_typo', $letexte);

	// Caracteres de controle "illegaux"
	$letexte = corriger_caracteres($letexte);

	// Proteger les caracteres typographiques a l'interieur des tags html
	if (preg_match_all(_TYPO_BALISE, $letexte, $regs, PREG_SET_ORDER)) {
		foreach ($regs as $reg) {
			$insert = $reg[0];
			// hack: on transforme les caracteres a proteger en les remplacant
			// par des caracteres "illegaux". (cf corriger_caracteres())
			$insert = strtr($insert, _TYPO_PROTEGER, _TYPO_PROTECTEUR);
			$letexte = str_replace($reg[0], $insert, $letexte);
		}
	}

	// trouver les blocs multi et les traiter a part
	$letexte = extraire_multi($e = $letexte, $lang, true);
	$e = ($e === $letexte);

	// Charger & appliquer les fonctions de typographie
	$typographie = charger_fonction(lang_typo($lang), 'typographie');
	$letexte = $typographie($letexte);

	// Les citations en une autre langue, s'il y a lieu
	if (!$e) $letexte = echappe_retour($letexte, 'multi');

	// Retablir les caracteres proteges
	$letexte = strtr($letexte, _TYPO_PROTECTEUR, _TYPO_PROTEGER);

	// pipeline
	$letexte = pipeline('post_typo', $letexte);

	# un message pour abs_url - on est passe en mode texte
	$GLOBALS['mode_abs_url'] = 'texte';

	return $letexte;
}


//
// Tableaux
//

define('_RACCOURCI_TH_SPAN', '\s*(:?{{[^{}]+}}\s*)?|<');

// http://doc.spip.org/@traiter_tableau
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
					  $ligne= "<th id='id{$tabid}_c$c' $attr>$cols[$c]</th>$ligne";
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
			if (strpos($ligne,"\n-*")!==false OR strpos($ligne,"\n-#")!==false)
				$ligne = traiter_listes($ligne);

			// Pas de paragraphes dans les cellules
			$ligne = preg_replace("/\n{2,}/", "<br /><br />\n", $ligne);

			// tout mettre dans un tableau 2d
			preg_match_all('/\|([^|]*)/S', $ligne, $cols);
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
			if ($align AND preg_match('/^\d+([.,]?)\d*$/', trim($lignes[$j][$i]), $r)){
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
		$class = alterner($l+1, 'even', 'odd');
		$html = "<tr class='row_$class'>$ligne</tr>\n$html";
	}
	return "\n\n<table".$GLOBALS['class_spip_plus'].$summary.">\n"
		. $debut_table
		. "<tbody>\n"
		. $html
		. "</tbody>\n"
		. "</table>\n\n";
}


//
// Traitement des listes (merci a Michael Parienti)
//
// http://doc.spip.org/@traiter_listes
function traiter_listes ($texte) {
	global $class_spip, $class_spip_plus;
	$parags = preg_split(",\n[[:space:]]*\n,S", $texte);
	$texte ='';

	// chaque paragraphe est traite a part
	while (list(,$para) = each($parags)) {
		$niveau = 0;
		$pile_li = $pile_type = array();
		$lignes = explode("\n-", "\n" . $para);

		// ne pas toucher a la premiere ligne
		list(,$debut) = each($lignes);
		$texte .= $debut;

		// chaque item a sa profondeur = nb d'etoiles
		$type ='';
		while (list(,$item) = each($lignes)) {
			preg_match(",^([*]*|[#]*)([^*#].*)$,sS", $item, $regs);
			$profond = strlen($regs[1]);

			if ($profond > 0) {
				$ajout='';

				// changement de type de liste au meme niveau : il faut
				// descendre un niveau plus bas, fermer ce niveau, et
				// remonter
				$nouv_type = (substr($item,0,1) == '*') ? 'ul' : 'ol';
				$change_type = ($type AND ($type <> $nouv_type) AND ($profond == $niveau)) ? 1 : 0;
				$type = $nouv_type;

				// d'abord traiter les descentes
				while ($niveau > $profond - $change_type) {
					$ajout .= $pile_li[$niveau];
					$ajout .= $pile_type[$niveau];
					if (!$change_type)
						unset ($pile_li[$niveau]);
					$niveau --;
				}

				// puis les identites (y compris en fin de descente)
				if ($niveau == $profond && !$change_type) {
					$ajout .= $pile_li[$niveau];
				}

				// puis les montees (y compris apres une descente un cran trop bas)
				while ($niveau < $profond) {
					if ($niveau == 0) $ajout .= "\n\n";
					elseif (!isset($pile_li[$niveau])) {
						$ajout .= "<li$class_spip>";
						$pile_li[$niveau] = "</li>";
					}
					$niveau ++;
					$ajout .= "<$type$class_spip_plus>";
					$pile_type[$niveau] = "</$type>";
				}

				$ajout .= "<li$class_spip>";
				$pile_li[$profond] = "</li>";
			}
			else {
				$ajout = "\n-";	// puce normale ou <hr>
			}

			$texte .= $ajout . $regs[2];
		}

		// retour sur terre
		$ajout = '';
		while ($niveau > 0) {
			$ajout .= $pile_li[$niveau];
			$ajout .= $pile_type[$niveau];
			$niveau --;
		}
		$texte .= $ajout;

		// paragraphe
		$texte .= "\n\n";
	}

	// sucrer les deux derniers \n
	return substr($texte, 0, -2);
}



//
// Une fonction pour fermer les paragraphes ; on essaie de preserver
// des paragraphes indiques a la main dans le texte
// (par ex: on ne modifie pas un <p align='center'>)
//
// deuxieme argument : forcer les <p> meme pour un seul paragraphe
//
// http://doc.spip.org/@paragrapher
function paragrapher($letexte, $forcer=true) {
	global $class_spip;

	$letexte = trim($letexte);
	if (!strlen($letexte))
		return '';

	if ($forcer OR (
	strstr($letexte,'<') AND preg_match(',<p\b,iS',$letexte)
	)) {
		// toujours ouvrir un parapgraphe derriere une balise bloc fermante
		// Fermer les paragraphes (y compris sur "STOP P")
		$letexte = preg_replace(',</('._BALISES_BLOCS.')[^>]*>\s*?,UimsS',"\\0<p>", $letexte);

		// Ajouter un espace aux <p> et un "STOP P"
		// transformer aussi les </p> existants en <p>, nettoyes ensuite
		$letexte = preg_replace(',</?p\b\s?(.*?)>,iS', '<STOP P><p \1>',
			'<p>'.$letexte.'<STOP P>');

		// Fermer les paragraphes (y compris sur "STOP P")
		$letexte = preg_replace(',(<p\s.*)(</?(STOP P|'._BALISES_BLOCS.')[>[:space:]]),UimsS',
			"\\1</p>\n\\2", $letexte);

		// Supprimer les marqueurs "STOP P"
		$letexte = str_replace('<STOP P>', '', $letexte);

		// Reduire les blancs dans les <p>
		$u = @$GLOBALS['meta']['pcre_u'];
		$letexte = preg_replace(',(<p\b.*>)\s*,UiS'.$u, '\1',$letexte);
		$letexte = preg_replace(',\s*(</p\b.*>),UiS'.$u, '\1',$letexte);

		// Supprimer les <p xx></p> vides
		$letexte = preg_replace(',<p\b[^<>]*></p>\s*,iS'.$u, '',
			$letexte);

		// Renommer les paragraphes normaux
		$letexte = str_replace('<p >', "\n<p$class_spip>",
			$letexte);
	}

	return $letexte;
}

// http://doc.spip.org/@traiter_poesie
function traiter_poesie($letexte)
{
	if (preg_match_all(",<(poesie|poetry)>(.*)<\/(poesie|poetry)>,UimsS",
	$letexte, $regs, PREG_SET_ORDER)) {
		$u = "/\n[\s]*\n/S" . $GLOBALS['meta']['pcre_u'];
		foreach ($regs as $reg) {
			$lecode = preg_replace(",\r\n?,S", "\n", $reg[2]);
			$lecode = preg_replace($u, "\n&nbsp;\n",$lecode);
			$lecode = "<blockquote class=\"spip_poesie\">\n<div>"
				.preg_replace("/\n+/", "</div>\n<div>", trim($lecode))
				."</div>\n</blockquote>\n\n";
			$letexte = str_replace($reg[0], $lecode, $letexte);
		}
	}
	return $letexte;
}

// Harmonise les retours chariots et mange les paragraphes html
// http://doc.spip.org/@traiter_retours_chariots
function traiter_retours_chariots($letexte) {
	$letexte = preg_replace(",\r\n?,S", "\n", $letexte);
	$letexte = preg_replace(",<p[>[:space:]],iS", "\n\n\\0", $letexte);
	$letexte = preg_replace(",</p[>[:space:]],iS", "\\0\n\n", $letexte);
	return $letexte;
}

// Ces deux constantes permettent de proteger certains caracteres
// en les remplacanat par des caracteres "illegaux". (cf corriger_caracteres)

define('_RACCOURCI_PROTEGER', "{}_-");
define('_RACCOURCI_PROTECTEUR', "\x1\x2\x3\x4");

define('_RACCOURCI_BALISE', ",</?[a-z!][^<>]*[".preg_quote(_RACCOURCI_PROTEGER)."][^<>]*>,imsS");

// Nettoie un texte, traite les raccourcis autre qu'URL, la typo, etc.
// http://doc.spip.org/@traiter_raccourcis
function traiter_raccourcis($letexte) {

	// Appeler les fonctions de pre_traitement
	$letexte = pipeline('pre_propre', $letexte);

	// Gerer les notes (ne passe pas dans le pipeline)
	$notes = charger_fonction('notes', 'inc');
	list($letexte, $mes_notes) = $notes($letexte);

	//
	// Tableaux
	//

	// ne pas oublier les tableaux au debut ou a la fin du texte
	$letexte = preg_replace(",^\n?[|],S", "\n\n|", $letexte);
	$letexte = preg_replace(",\n\n+[|],S", "\n\n\n\n|", $letexte);
	$letexte = preg_replace(",[|](\n\n+|\n?$),S", "|\n\n\n\n", $letexte);

	if (preg_match_all(',[^|](\n[|].*[|]\n)[^|],UmsS', $letexte,
	$regs, PREG_SET_ORDER))
	foreach ($regs as $t) {
		$letexte = str_replace($t[1], traiter_tableau($t[1]), $letexte);
	}

	$letexte = "\n".trim($letexte);

	// les listes
	if (strpos($letexte,"\n-*")!==false OR strpos($letexte,"\n-#")!==false)
		$letexte = traiter_listes($letexte);

	// Proteger les caracteres actifs a l'interieur des tags html

	if (preg_match_all(_RACCOURCI_BALISE, $letexte, $regs, PREG_SET_ORDER)) {
		foreach ($regs as $reg) {
			$insert = strtr($reg[0], _RACCOURCI_PROTEGER, _RACCOURCI_PROTECTEUR);
			$letexte = str_replace($reg[0], $insert, $letexte);
		}
	}

	// Traitement des alineas
	list($a,$b) = definir_raccourcis_alineas();
	$letexte = preg_replace($a, $b,	$letexte);
	//  Introduction des attributs class_spip* et autres raccourcis
	list($a,$b) = $GLOBALS['spip_raccourcis_typo'];
	$letexte = preg_replace($a, $b,	$letexte);
	$letexte = preg_replace('@^\n<br />@S', '', $letexte);

	// Retablir les caracteres proteges
	$letexte = strtr($letexte, _RACCOURCI_PROTECTEUR, _RACCOURCI_PROTEGER);

	// Fermer les paragraphes ; mais ne pas forcement en creer si un seul
	$letexte = paragrapher($letexte, $GLOBALS['toujours_paragrapher']);

	// Appeler les fonctions de post-traitement
	$letexte = pipeline('post_propre', $letexte);

	if ($mes_notes) $notes($mes_notes);

	return $letexte;
}



// Filtre a appliquer aux champs du type #TEXTE*
// http://doc.spip.org/@propre
function propre($t, $connect=null) {
	// les appels directs a cette fonction depuis le php de l'espace
	// prive etant historiquement ecrits sans argment $connect
	// on utilise la presence de celui-ci pour distinguer les cas
	// ou il faut passer interdire_script explicitement
	// les appels dans les squelettes (de l'espace prive) fournissant un $connect
	// ne seront pas perturbes
	$interdire_script = false;
	if (is_null($connect)){
		$connect = '';
		$interdire_script = true;
	}

	return !$t ? strval($t) :
		echappe_retour_modeles(
			traiter_raccourcis(
				expanser_liens(echappe_html($t),$connect)),$interdire_script);
}
?>
