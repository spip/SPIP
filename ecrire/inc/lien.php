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

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('base/abstract_sql');


//
// Raccourcis liens [xxx->url]
// Note : complique car c'est ici qu'on applique typo(),
// et en plus on veut pouvoir les passer en pipeline
//

// Regexp des raccouris, aussi utilisee pour la fusion de sauvegarde Spip
// Laisser passer des paires de crochets pour la balise multi
// mais refuser plus d'imbrications ou de mauvaises imbrications
// sinon les crochets ne peuvent plus servir qu'a ce type de raccourci
define('_RACCOURCI_LIEN', ",\[([^][]*?([[]\w*[]][^][]*)*)->(>?)([^]]*)\],msS");

// http://doc.spip.org/@expanser_liens
function expanser_liens($letexte, $connect='')
{
	$letexte = pipeline('pre_liens', $letexte);

	$inserts = array();
	if (preg_match_all(_RACCOURCI_LIEN, $letexte, $matches, PREG_SET_ORDER)) {
		$i = 0;
		foreach ($matches as $regs) {
			$n = count($regs);
			list($texte, $bulle, $hlang) = traiter_raccourci_lien_atts($regs[1]);
			list ($lien, $class, $texte, $lang) =
			  calculer_url($regs[$n-1], $texte, 'tout', $connect);
			$inserts[++$i] = traiter_raccourci_lien_lang($lien, $class, $texte, $hlang, $lang, $bulle, $connect);

			$letexte = str_replace($regs[0], "@@SPIP_ECHAPPE_LIEN_$i@@",
				$letexte);
		}
	}

	$letexte = corriger_typo(traiter_modeles($letexte, false, false, $connect));
	foreach ($inserts as $i => $insert) {
		$letexte = str_replace("@@SPIP_ECHAPPE_LIEN_$i@@", $insert, $letexte);
	}
	return $letexte;
}

// http://doc.spip.org/@traiter_raccourci_lien_lang
function traiter_raccourci_lien_lang($lien, $class, $texte, $hlang, $lang, $bulle, $connect='')
{		
	// Si l'objet n'est pas de la langue courante, on ajoute hreflang
	if (!$hlang AND $lang!=$GLOBALS['spip_lang'])
		$hlang = $lang;
	$lang = ($hlang ? ' hreflang="'.$hlang.'"' : '') . $bulle;

	# ceci s'execute heureusement avant les tableaux et leur "|".
	# Attention, le texte initial est deja echappe mais pas forcement
	# celui retourne par calculer_url.

	# Penser au cas [<imgXX|right>->URL], qui exige typo('<a>...</a>')
	return typo('<a href="'.$lien
		. ($class ? '" class="'.$class : '')
		. '"'.$lang.'>'
		. $texte.'</a>', true, $connect);
}

// Repere dans la partie texte d'un raccourci [texte->...]
// la langue et la bulle eventuelles

// http://doc.spip.org/@traiter_raccourci_lien_atts
function traiter_raccourci_lien_atts($texte) {

	$bulle = $hlang = '';
	// title et hreflang donnes par le raccourci ?
	if (preg_match(',^(.*?)([|]([^<>]*?))?([{]([a-z_]+)[}])?$,', $texte, $m)) {

		$n =count($m);
		// |infobulle ?
		if ($n > 2) {
			$bulle = ' title="'.texte_backend($m[3]).'"';
			// {hreflang} ?
			if ($n > 4) {
			// si c'est un code de langue connu, on met un hreflang
				if (traduire_nom_langue($m[5]) <> $m[5]) {
					$hlang = $m[5];
				}
			// sinon c'est un italique
				else {
					$m[1] .= $m[4];
				}
			
	// S'il n'y a pas de hreflang sous la forme {}, ce qui suit le |
	// est peut-etre une langue
			} else if (preg_match(',^[a-z_]+$,', $m[3])) {
			// si c'est un code de langue connu, on met un hreflang
			// mais on laisse le title (c'est arbitraire tout ca...)
				if (traduire_nom_langue($m[3]) <> $m[3]) {
				  $hlang = $m[3];
				}
			}
		}
		$texte = $m[1];
	}

	return array(trim($texte), $bulle, $hlang);
}

// callback pour la fonction traiter_raccourci_liens()
// http://doc.spip.org/@autoliens_callback
function autoliens_callback($r) {
	if (strlen($l = $r[1])) {
		// reperer le protocole
		$protocol = 'http';
		if (preg_match(',^((https?):/*),S', $l, $m)) {
			$l = substr($l, strlen($m[1]));
			$protocol = $m[2];
		}
		// valider le nom de domaine
		if (preg_match(
		'/^(?:[^\W_]((?:[^\W_]|-){0,61}[^\W_,])?\.)+[a-z]{2,6}\b/Si', $l)) {
			// supprimer les ponctuations a la fin d'une URL
			preg_match('/^(.*?)([,.;?]?)$/', $l, $k);
			$l = inserer_attribut(
				expanser_liens('[->'.$protocol.'://'.$k[1].']'),
			'rel', 'nofollow')
			.$k[2];
			// si le texte ne contenait pas le 'http:' on le supprime aussi
			if (!$m)
				$l = str_replace('>http://', '>', $l);
			return $l;
		}
	}
	return $r[0];
}

// extraire les liens ecrits en mode texte brut
// http://doc.spip.org/@traiter_raccourci_liens
function traiter_raccourci_liens($texte) {
	return preg_replace_callback(
	';\[[^\[\]]*(?:<-|->).*?\]|<a\b.*?</a\b|<.*?>|'
	.'((?:https?:/|www\.)[^"\'\s\[\]\}\)<>]*);imsS',
	'autoliens_callback', $texte);
	return $texte;
}

// http://doc.spip.org/@nettoyer_raccourcis_typo
function nettoyer_raccourcis_typo($texte, $connect=''){
	$texte = pipeline('nettoyer_raccourcis_typo',$texte);
	// remplacer les liens
	if (preg_match_all(',[[]([^][]*)->(>?)([^][]*)[]],S', $texte, $regs, PREG_SET_ORDER))
		foreach ($regs as $reg) {
			list ($titre,,)= traiter_raccourci_lien_atts($reg[1]);
			$titre = calculer_url($reg[3], $titre, 'titre', $connect);
			$titre = corriger_typo(supprimer_tags($titre));
			$texte = str_replace($reg[0], $titre, $texte);
		}

	// supprimer les notes
	$texte = preg_replace(",[[][[]([^]]|[]][^]])*[]][]],UimsS","",$texte);

	// supprimer les codes typos
	$texte = str_replace(array('}','{'), '', $texte);

	// supprimer les tableaux
	$texte = preg_replace(",(^|\r)\|.*\|\r,s", "\r", $texte);
	return $texte;
}

// Fonction pour les champs chapo commencant par =,  redirection qui peut etre:
// 1. un raccourci Spip habituel (premier If) [texte->TYPEnnn]
// 2. un ultra raccourci TYPEnnn voire nnn (article) (deuxieme If)
// 3. une URL std
// renvoie une tableau structure comme ci-dessus mais sans calcul d'URL
// (cf fusion de sauvegardes)

define('_RACCOURCI_CHAPO', ',^(\W*)(\W*)(\w*\d+([?#].*)?)$,');

// http://doc.spip.org/@chapo_redirige
function chapo_redirige($chapo, $url=false)
{
	if (!preg_match(_RACCOURCI_LIEN, $chapo, $m))
		if (!preg_match(_RACCOURCI_CHAPO, $chapo, $m))
			return $chapo;
	return !$url ? $m[3] : calculer_url($m[3]);
}

// Ne pas afficher le chapo si article virtuel
// http://doc.spip.org/@nettoyer_chapo
function nettoyer_chapo($chapo){
	return (substr($chapo,0,1) == "=") ? '' : $chapo;
}

// http://doc.spip.org/@chapo_redirigetil
function chapo_redirigetil($chapo) { return $chapo && $chapo[0] == '=';}

// Cherche un lien du type [->raccourci 123]
// associe a une fonction generer_url_raccourci() definie explicitement 
// ou implicitement par le jeu de type_urls courant.
//
// Valeur retournee selon le parametre $pour:
// 'tout' : tableau [U,C,T,L] (vise <a href="U" class='C' hreflang='L'>T</a>)
// 'titre': seulement T ci-dessus (i.e. le TITRE ci-dessus ou dans table SQL)
// 'url':   seulement U  (i.e. generer_url_RACCOURCI)

// http://doc.spip.org/@calculer_url
function calculer_url ($ref, $texte='', $pour='url', $connect='') {
	if ($match = typer_raccourci($ref)) {
		@list($type,,$id,,$args,,$ancre) = $match;
# attention dans le cas des sites le lien doit pointer non pas sur
# la page locale du site, mais directement sur le site lui-meme
		if ($type == 'site')
			$url = sql_getfetsel('url_site', 'spip_syndic', "id_syndic=$id",'','','','',$connect);
		else $url = generer_url_entite($id,$type,$args,$ancre,	$connect ? $connect : NULL);
		if ($url) 
			return ($pour === 'url')
			? $url
			: calculer_url_lien($type, $id, $url, $texte, $pour, $connect);
	}
	if (preg_match(",^\s*(http:?/?/?|mailto:?)\s*$,iS", $ref))
		return ($pour != 'tout') ? '' : array('','','','');

	$lien = entites_html(trim($ref));

	// Liens explicites
	if (!$texte) {
		$texte = str_replace('"', '', $lien);
		if (strlen($texte)>40)
				$texte = substr($texte,0,35).'...';
		$texte = "<html>$texte</html>";
		$class = "spip_url spip_out";
	} else 	$class = "spip_out";

	if ($pour == 'titre') return $texte;

	// petites corrections d'URL
	if (preg_match(",^www\.[^@]+$,S",$lien))
		$lien = "http://".$lien;
	else if (strpos($lien, "@") && email_valide($lien))
		$lien = "mailto:".$lien;
	
	if (preg_match(",^\s*mailto:,",$lien))
		$class = "spip_mailto";

	// class spip_ancre sur les ancres pures (internes a la page)
	if (substr($lien,0,1) == '#')
		$class = 'spip_ancre';

	return ($pour == 'url') ? $lien : array($lien, $class, $texte, '');
}

// analyse des raccourcis issus de [TITRE->RACCOURCInnn] et connexes

define('_RACCOURCI_URL', ',^\s*(\w*?)\s*(\d+)(\?(.*?))?(#([^\s]*))?\s*$,S');

// http://doc.spip.org/@typer_raccourci
function typer_raccourci ($lien) {
	if (!preg_match(_RACCOURCI_URL, $lien, $match)) return array();
	$f = $match[1];
	// valeur par defaut et alias historiques
	if (!$f) $f = 'article';
	else if ($f == 'art') $f = 'article';
	else if ($f == 'br') $f = 'breve';
	else if ($f == 'rub') $f = 'rubrique';
	else if ($f == 'aut') $f = 'auteur';
	else if ($f == 'doc' OR $f == 'im' OR $f == 'img' OR $f == 'image' OR $f == 'emb')
		$f = 'document';
	else if (preg_match(',^br..?ve$,S', $f)) $f = 'breve'; # accents :(
	$match[0] = $f;
	$match[2] = entites_html($match[2]);
	return $match;
}

function calculer_url_lien($type, $id, $url, $texte, $pour, $connect)
{
	$trouver_table = charger_fonction('trouver_table', 'base');
	$desc = $trouver_table(table_objet($type));
	$lang = '';
	if ($desc AND $s = $desc['titre']) {
		$_id = $desc['key']['PRIMARY KEY'];
		$t = $desc['table'];
		$r = sql_fetsel($s, $t, "$_id=$id", '','','','',$connect);
		$texte = trim($texte);
		if ($r AND !$texte) {
			$texte = supprimer_numero($r['titre']);
			if (!$texte) $texte = $r['surnom'];
			if (!$texte) $texte = $id;
			$lang = $r['lang'];
		}
		$style = 'spip_in';
	} else $style =  'spip_out';

	return ($pour=='titre') ? $texte : array($url, $style, $texte, $lang);
}

// traite les modeles (dans la fonction typo), en remplacant
// le raccourci <modeleN|parametres> par la page calculee a
// partir du squelette modeles/modele.html
// Le nom du modele doit faire au moins trois caracteres (evite <h2>)
// Si $doublons==true, on repere les documents sans calculer les modeles
// mais on renvoie les params (pour l'indexation par le moteur de recherche)
// http://doc.spip.org/@traiter_modeles

define('_RACCOURCI_MODELE', 
	 '(<([a-z_-]{3,})' # <modele
	.'\s*([0-9]*)\s*' # id
	.'([|](?:<[^<>]*>|[^>])*?)?' # |arguments (y compris des tags <...>)
	.'\s*/?'.'>)' # fin du modele >
	.'\s*(<\/a>)?' # eventuel </a>
       );

define('_RACCOURCI_MODELE_DEBUT', '@^' . _RACCOURCI_MODELE .'@is');

// http://doc.spip.org/@traiter_modeles
function traiter_modeles($texte, $doublons=false, $echap='', $connect='') {
	// detecter les modeles (rapide)
	if (preg_match_all('/<[a-z_-]{3,}\s*[0-9|]+/iS',
	$texte, $matches, PREG_SET_ORDER)) {
		include_spip('public/assembler');
		foreach ($matches as $match) {
			// Recuperer l'appel complet (y compris un eventuel lien)

			$a = strpos($texte,$match[0]);
			preg_match(_RACCOURCI_MODELE_DEBUT, substr($texte, $a), $regs);
			$regs[]=""; // s'assurer qu'il y a toujours un 5e arg, eventuellement vide
			list(,$mod, $type, $id, $params, $fin) = $regs;
			if ($fin AND preg_match(
			',<a\s[^<>]*>\s*$,i', substr($texte, 0, $a), $r)) {
				$lien = array(
					extraire_attribut($r[0],'href'),
					extraire_attribut($r[0],'class')
				);
				$n = strlen($r[0]);
				$a -= $n;
				$cherche = $n + strlen($regs[0]);
			} else {
				$lien = false;
				$cherche = strlen($mod);
			}

			// calculer le modele
			# hack articles_edit, breves_edit, indexation
			if ($doublons)
				$texte .= preg_replace(',[|][^|=]*,s',' ',$params);
			# version normale
			else {
				$modele = inclure_modele($type, $id, $params, $lien, $connect);

				// le remplacer dans le texte
				if ($modele !== false) {
					$modele = protege_js_modeles($modele);
					$rempl = code_echappement($modele, $echap);
					$texte = substr($texte, 0, $a)
						. $rempl
						. substr($texte, $a+$cherche);
				}
			}

			// hack pour tout l'espace prive
			if (((!_DIR_RESTREINT) OR ($doublons)) AND ($id) AND (in_array($type,array('doc','emb','img'))))
				$GLOBALS['doublons_documents_inclus'][] = $id;
		}
	}

	return $texte;
}

//
// Raccourcis ancre [#ancre<-]
//

define('_RACCOURCI_ANCRE', "|\[#?([^][]*)<-\]|S");

// http://doc.spip.org/@traiter_raccourci_ancre
function traiter_raccourci_ancre($letexte)
{
	if (preg_match_all(_RACCOURCI_ANCRE, $letexte, $m, PREG_SET_ORDER))
	foreach ($m as $regs)
		$letexte = str_replace($regs[0],
		'<a name="'.entites_html($regs[1]).'"></a>', $letexte);
	return $letexte;
}

//
// Raccourcis automatiques [?SPIP] vers un glossaire
// Wikipedia par defaut, avec ses contraintes techniques
// cf. http://fr.wikipedia.org/wiki/Wikip%C3%A9dia:Conventions_sur_les_titres

define('_RACCOURCI_GLOSSAIRE', "|\[\?+\s*([^][<>]+)\]|S");

// http://doc.spip.org/@traiter_raccourci_glossaire
function traiter_raccourci_glossaire($letexte)
{
	if (!preg_match_all(_RACCOURCI_GLOSSAIRE, $letexte, $m, PREG_SET_ORDER))
		return $letexte;

	include_spip('inc/charsets');

	foreach ($m as $regs) {
		// Eviter les cas particulier genre "[?!?]"
		// et isoler le lexeme a gloser de ses accessoires
		// (#:url du glossaire, | bulle d'aide, {} lang)

		if (preg_match(',^([^|#{]*\w[^|#{]*)([^#]*)(#([^|{}]*))?(.*)$,', $regs[1], $r)) {

			list($terme, $bulle, $hlang) = traiter_raccourci_lien_atts($r[1] . $r[2] . $r[5]);

			$terme = unicode2charset(charset2unicode($terme), 'utf-8');
			
			if ($r[4] AND function_exists($f = 'glossaire_' . $r[4]))
				$glose = $f($terme);
			else $glose  = glossaire_std($terme);
			$ref = traiter_raccourci_lien_lang($glose, 'spip_glossaire', $terme, $hlang, '', $bulle);
			$letexte = str_replace($regs[0], $ref, $letexte);
		}
	}
	return $letexte;
}

function glossaire_std($terme)
{
	global $url_glossaire_externe;
	static $pcre = NULL;

	if ($pcre === NULL) {
		$pcre = isset($GLOBALS['meta']['pcre_u']) 
		? $GLOBALS['meta']['pcre_u']
		  : '';
		if (strpos($url_glossaire_externe, "%s") === false)
			$url_glossaire_externe .= '%s';
	}

	$glosateur = str_replace("@lang@",
				$GLOBALS['spip_lang'],
				$GLOBALS['url_glossaire_externe']);

	$terme = rawurlencode(preg_replace(',\s+,'.$pcre, '_', $terme));
	
	return  str_replace("%s", $terme, $glosateur);
}

?>
