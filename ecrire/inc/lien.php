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

include_spip('base/abstract_sql');

//
// Production de la balise A+href a partir des raccourcis [xxx->url] etc.
// Note : complique car c'est ici qu'on applique typo(),
// et en plus on veut pouvoir les passer en pipeline
//

// http://doc.spip.org/@traiter_raccourci_lien_lang
function inc_lien_dist($lien, $texte='', $class='', $title='', $hlang='', $rel='', $connect='')
{
	// Si une langue est demandee sur un raccourci d'article, chercher
	// la traduction ;
	// - [{en}->art2] => traduction anglaise de l'article 2, sinon art 2
	// - [{}->art2] => traduction en langue courante de l'art 2, sinon art 2
	if ($hlang
	AND $match = typer_raccourci($lien)) { 
		@list($type,,$id,,$args,,$ancre) = $match; 
		if ($id_trad = sql_getfetsel('id_trad', 'spip_articles', "id_article=$id")
		AND $id_dest = sql_getfetsel('id_article', 'spip_articles',
			"id_trad=$id_trad  AND statut<>'refuse' AND lang=" . sql_quote($hlang))
		)
			$lien = "$type$id_dest";
		else
			$hlang = '';
	}

	$mode = ($texte AND $class) ? 'url' : 'tout';
	$lien = calculer_url($lien, $texte, $mode, $connect);
	if ($mode === 'tout') {
		$texte = $lien['titre'];
		if (!$class AND isset($lien['class'])) $class = $lien['class'];
		$lang = isset($lien['lang']) ?$lien['lang'] : '';
		$mime = isset($lien['mime']) ? " type='".$lien['mime']."'" : "";
		$lien = $lien['url'];
	}
	if (substr($lien,0,1) == '#')  # ancres pures (internes a la page)
		$class = 'spip_ancre';
	elseif (preg_match('/^\s*mailto:/',$lien)) # pseudo URL de mail
		$class = "spip_mail";
	elseif (preg_match('/^<html>/',$texte)) # cf traiter_lien_explicite
		$class = "spip_url spip_out";
	elseif (!$class) $class = "spip_out"; # si pas spip_in|spip_glossaire

	// Si l'objet n'est pas de la langue courante, on ajoute hreflang
	if (!$hlang AND $lang!==$GLOBALS['spip_lang'])
		$hlang = $lang;

	$lang = ($hlang ? " hreflang='$hlang'" : '');

	if ($title) $title = ' title="'.texte_backend($title).'"';

	// rel=external pour les liens externes
	if (preg_match(',^https?://,S', $lien)
	AND false === strpos("$lien/", url_de_base()))
		$rel = trim("$rel external");
	if ($rel) $rel = " rel='$rel'";

	$lien = "<a href=\"".str_replace('"', '&quot;', $lien)."\" class='$class'$lang$title$rel$mime>$texte</a>";

	# ceci s'execute heureusement avant les tableaux et leur "|".
	# Attention, le texte initial est deja echappe mais pas forcement
	# celui retourne par calculer_url.
	# Penser au cas [<imgXX|right>->URL], qui exige typo('<a>...</a>')
	return typo($lien, true, $connect);
}

// Regexp des raccourcis, aussi utilisee pour la fusion de sauvegarde Spip
// Laisser passer des paires de crochets pour la balise multi
// mais refuser plus d'imbrications ou de mauvaises imbrications
// sinon les crochets ne peuvent plus servir qu'a ce type de raccourci
define('_RACCOURCI_LIEN', "/\[([^][]*?([[]\w*[]][^][]*)*)->(>?)([^]]*)\]/msS");

// http://doc.spip.org/@expanser_liens
function expanser_liens($texte, $connect='')
{
	$texte = pipeline('pre_liens', $texte);
	$sources = $inserts = $regs = array();
	if (preg_match_all(_RACCOURCI_LIEN, $texte, $regs, PREG_SET_ORDER)) {
		$lien = charger_fonction('lien', 'inc');
		foreach ($regs as $k => $reg) {

			$inserts[$k] = '@@SPIP_ECHAPPE_LIEN_' . $k . '@@';
			$sources[$k] = $reg[0];
			$texte = str_replace($sources[$k], $inserts[$k], $texte);

			list($titre, $bulle, $hlang) = traiter_raccourci_lien_atts($reg[1]);
			$r = $reg[count($reg)-1];
			// la mise en lien automatique est passee par la a tort !
			// corrigeons pour eviter d'avoir un <a...> dans un href...
			if (strncmp($r,'<a',2)==0){
				$href = extraire_attribut($r, 'href');
				// remplacons dans la source qui peut etre reinjectee dans les arguments
				// d'un modele
				$sources[$k] = str_replace($r,$href,$sources[$k]);
				// et prenons le href comme la vraie url a linker
				$r = $href;
			}
			$regs[$k] = $lien($r, $titre, '', $bulle, $hlang, '', $connect);
		}
	}

	// on passe a traiter_modeles la liste des liens reperes pour lui permettre
	// de remettre le texte d'origine dans les parametres du modele
	$texte = traiter_modeles($texte, false, false, $connect, array($inserts, $sources));
 	$texte = corriger_typo($texte);
	$texte = str_replace($inserts, $regs, $texte);
	return $texte;
}

// Meme analyse mais pour eliminer les liens
// et ne laisser que leur titre, a expliciter si ce n'est fait
// http://doc.spip.org/@nettoyer_raccourcis_typo
function nettoyer_raccourcis_typo($texte, $connect='')
{
	$texte = pipeline('nettoyer_raccourcis_typo',$texte);

	if (preg_match_all(_RACCOURCI_LIEN, $texte, $regs, PREG_SET_ORDER))
		foreach ($regs as $reg) {
			list ($titre,,)= traiter_raccourci_lien_atts($reg[1]);
			if (!$titre) {
				$match = typer_raccourci($reg[count($reg)-1]);
				@list($type,,$id,,,,) = $match;
				if ($type) {
					$url = generer_url_entite($id,$type,'','',true);
					if (is_array($url)) list($type, $id) = $url;
					$titre = traiter_raccourci_titre($id, $type, $connect);
				}
				$titre = $titre ? $titre['titre'] : $match[0];
			}
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



// Repere dans la partie texte d'un raccourci [texte->...]
// la langue et la bulle eventuelles

define('_RACCOURCI_ATTRIBUTS', '/^(.*?)([|]([^<>]*?))?([{]([a-z_]*)[}])?$/');

// http://doc.spip.org/@traiter_raccourci_lien_atts
function traiter_raccourci_lien_atts($texte) {

	$bulle = $hlang = '';
	// title et hreflang donnes par le raccourci ?
	if (preg_match(_RACCOURCI_ATTRIBUTS, $texte, $m)) {

		$n =count($m);
		// |infobulle ?
		if ($n > 2) {
			$bulle = $m[3];
			// {hreflang} ?
			if ($n > 4) {
			// si c'est un code de langue connu, on met un hreflang
				if (traduire_nom_langue($m[5]) <> $m[5]) {
					$hlang = $m[5];
				} elseif (!$m[5]) {
					$hlang = test_espace_prive() ? 
					  $GLOBALS['lang_objet'] : $GLOBALS['spip_lang'];
				// sinon c'est un italique
				} else {
					$m[1] .= $m[4];
				}
			
	// S'il n'y a pas de hreflang sous la forme {}, ce qui suit le |
	// est peut-etre une langue
			} else if (preg_match('/^[a-z_]+$/', $m[3])) {
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

define('_EXTRAIRE_DOMAINE', '/^(?:[^\W_]((?:[^\W_]|-){0,61}[^\W_,])?\.)+[a-z]{2,6}\b/Si');

// callback pour la fonction traiter_raccourci_liens()
// http://doc.spip.org/@autoliens_callback
function traiter_autoliens($r) {
	if (count($r)<2) return reset($r);
	list($tout, $l) = $r;
	if (!$l) return $tout;
	// reperer le protocole
	if (preg_match(',^(https?):/*,S', $l, $m)) {
		$l = substr($l, strlen($m[0]));
		$protocol = $m[1];
	} else 	$protocol = 'http';
	// valider le nom de domaine
	if (!preg_match(_EXTRAIRE_DOMAINE, $l)) return $tout;
	// supprimer les ponctuations a la fin d'une URL
	preg_match('/^(.*?)([,.;?]?)$/', $l, $k);
	$url = $protocol.'://'.$k[1];
	$lien = charger_fonction('lien', 'inc');
	$r = $lien($url,'','','','','nofollow') . $k[2];
	// si l'original ne contenait pas le 'http:' on le supprime du clic
	return $m ? $r : str_replace('>http://', '>', $r);
}

define('_EXTRAIRE_LIENS', ',' . '\[[^\[\]]*(?:<-|->).*?\]' . '|<a\b.*?</a\b' . '|<\w.*?>' . '|((?:https?:/|www\.)[^"\'\s\[\]\}\)<>]*)' .',imsS');

// Les URLs brutes sont converties en <a href='url'>url</a>
// http://doc.spip.org/@traiter_raccourci_liens
function traiter_raccourci_liens($t) {
	return preg_replace_callback(_EXTRAIRE_LIENS, 'traiter_autoliens', $t);
}


define('_RACCOURCI_CHAPO', '/^(\W*)(\W*)(\w*\d+([?#].*)?)$/');
/**
 * Fonction pour les champs chapo commencant par =,  redirection qui peut etre:
 * 1. un raccourci Spip habituel (premier If) [texte->TYPEnnn]
 * 2. un ultra raccourci TYPEnnn voire nnn (article) (deuxieme If)
 * 3. une URL std
 *
 * renvoie l'url reelle de redirection si le $url=true,
 * l'url brute contenue dans le chapo sinon
 *
 * http://doc.spip.org/@chapo_redirige
 *
 * @param string $chapo
 * @param bool $url
 * @return string
 */
function chapo_redirige($chapo, $url=false)
{
	if (!preg_match(_RACCOURCI_LIEN, $chapo, $m))
		if (!preg_match(_RACCOURCI_CHAPO, $chapo, $m))
			return $chapo;

	return !$url ? $m[3] : traiter_lien_implicite($m[3]);
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
// 'tout' : tableau d'index url,class,titre,lang (vise <a href="U" class='C' hreflang='L'>T</a>)
// 'titre': seulement T ci-dessus (i.e. le TITRE ci-dessus ou dans table SQL)
// 'url':   seulement U  (i.e. generer_url_RACCOURCI)

// http://doc.spip.org/@calculer_url
function calculer_url ($ref, $texte='', $pour='url', $connect='') {
	$r = traiter_lien_implicite($ref, $texte, $pour, $connect);
	return $r ? $r : traiter_lien_explicite($ref, $texte, $pour, $connect);
}

define('_EXTRAIRE_LIEN', ",^\s*(http:?/?/?|mailto:?)\s*$,iS");

// http://doc.spip.org/@traiter_lien_explicite
function traiter_lien_explicite ($ref, $texte='', $pour='url', $connect='')
{
	if (preg_match(_EXTRAIRE_LIEN, $ref))
		return ($pour != 'tout') ? '' : array('','','','');

	$lien = entites_html(trim($ref));

	// Liens explicites
	if (!$texte) {
		$texte = str_replace('"', '', $lien);
		// evite l'affichage de trops longues urls.
		$lien_court = charger_fonction('lien_court', 'inc');
		$texte = $lien_court($texte);
		$texte = "<html>".quote_amp($texte)."</html>";
	}

	// petites corrections d'URL
	if (preg_match('/^www\.[^@]+$/S',$lien))
		$lien = "http://".$lien;
	else if (strpos($lien, "@") && email_valide($lien)) {
		if (!$texte) $texte = $lien;
		$lien = "mailto:".$lien;
	}
	
	if ($pour == 'url') return $lien;

	if ($pour == 'titre') return $texte;

	return array('url' => $lien, 'titre' => $texte);
}

// http://doc.spip.org/@traiter_lien_implicite
function traiter_lien_implicite ($ref, $texte='', $pour='url', $connect='')
{
	if (!($match = typer_raccourci($ref))) return false;
	@list($type,,$id,,$args,,$ancre) = $match;
# attention dans le cas des sites le lien doit pointer non pas sur
# la page locale du site, mais directement sur le site lui-meme
	if ($type == 'site')
		$url = sql_getfetsel('url_site', 'spip_syndic', "id_syndic=$id",'','','','',$connect);
	elseif ($type == 'glose') {
		if (function_exists($f = 'glossaire_' . $ancre)) 
		  $url = $f($texte, $id);
		else $url = glossaire_std($texte);
	} else $url = generer_url_entite($id,$type,$args,$ancre,$connect ? $connect : NULL);
	if (!$url) return false;
	if (is_array($url)) {
		@list($type,$id) = $url;
		$url = generer_url_entite($id,$type,$args,$ancre,$connect ? $connect : NULL);
	}
	if ($pour === 'url') return $url;
	$r = traiter_raccourci_titre($id, $type, $connect);
	if ($r) $r['class'] =  ($type == 'site')?'spip_out':'spip_in';
	if ($texte = trim($texte)) $r['titre'] = $texte;
	if (!@$r['titre']) $r['titre'] =  _T($type) . " $id";
	if ($pour=='titre') return $r['titre'];
	$r['url'] = $url;

	// dans le cas d'un lien vers un doc, ajouter le type='mime/type'
	if ($type == 'document'
	AND $mime = sql_getfetsel('mime_type', 'spip_types_documents',
			"extension IN (SELECT extension FROM spip_documents where id_document =".sql_quote($id).")",
			'','','','',$connect)
	)
		$r['mime'] = $mime;

	return $r;
}

// analyse des raccourcis issus de [TITRE->RACCOURCInnn] et connexes

define('_RACCOURCI_URL', '/^\s*(\w*?)\s*(\d+)(\?(.*?))?(#([^\s]*))?\s*$/S');

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
	else if (preg_match('/^br..?ve$/S', $f)) $f = 'breve'; # accents :(
	$match[0] = $f;
	return $match;
}

// Retourne le champ textuel associe a une cle primaire, et sa langue
function traiter_raccourci_titre($id, $type, $connect=NULL)
{
	$trouver_table = charger_fonction('trouver_table', 'base');
	$desc = $trouver_table(table_objet($type));
	if (!($desc AND $s = $desc['titre'])) return array();
	$_id = $desc['key']['PRIMARY KEY'];
	$r = sql_fetsel($s, $desc['table'], "$_id=$id", '','','','',$connect);
	if (!$r) return array();
	$r['titre'] = supprimer_numero($r['titre']);
	if (!$r['titre']) $r['titre'] = $r['surnom'];
	if (!isset($r['lang'])) $r['lang'] = '';
	return $r;
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

define('_RACCOURCI_MODELE_DEBUT', '@^' . _RACCOURCI_MODELE .'@isS');

// http://doc.spip.org/@traiter_modeles
function traiter_modeles($texte, $doublons=false, $echap='', $connect='', $liens = null) {
	// preserver la compatibilite : true = recherche des documents
	if ($doublons===true)
		$doublons = array('documents'=>array('doc','emb','img'));
	// detecter les modeles (rapide)
	if (strpos($texte,"<")!==false AND
	  preg_match_all('/<[a-z_-]{3,}\s*[0-9|]+/iS', $texte, $matches, PREG_SET_ORDER)) {
		include_spip('public/assembler');
		foreach ($matches as $match) {
			// Recuperer l'appel complet (y compris un eventuel lien)

			$a = strpos($texte,$match[0]);
			preg_match(_RACCOURCI_MODELE_DEBUT,
			substr($texte, $a), $regs);
			$regs[]=""; // s'assurer qu'il y a toujours un 5e arg, eventuellement vide
			list(,$mod, $type, $id, $params, $fin) = $regs;
			if ($fin AND
			preg_match('/<a\s[^<>]*>\s*$/i',
					substr($texte, 0, $a), $r)) {
				$lien = array(
					'href' => extraire_attribut($r[0],'href'),
					'class' => extraire_attribut($r[0],'class'),
					'mime' => extraire_attribut($r[0],'type')
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
				// si un tableau de liens a ete passe, reinjecter le contenu d'origine
				// dans les parametres, plutot que les liens echappes
				if (!is_null($liens))
					$params = str_replace($liens[0], $liens[1], $params);
			  $modele = inclure_modele($type, $id, $params, $lien, $connect);
				// en cas d'echec, 
				// si l'objet demande a une url, 
				// creer un petit encadre vers elle
				if ($modele === false) {
					if (!$lien)
						$lien = traiter_lien_implicite("$type$id", '', 'tout', $connect);
					if ($lien)
						$modele = '<a href="'
						  .$lien['url']
						  .'" class="spip_modele'
						  . '">'
						  .sinon($lien['titre'], _T('ecrire:info_sans_titre'))
						  ."</a>";
					else {
						$modele = "";
						if (test_espace_prive()) {
							$modele = entites_html(substr($texte,$a,$cherche));
							if (!is_null($liens))
								$modele = "<pre>".str_replace($liens[0], $liens[1], $modele)."</pre>";
						}
					}
				}
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
			if (((!_DIR_RESTREINT) OR ($doublons)) AND ($id)){
				foreach($doublons?$doublons:array('documents'=>array('doc','emb','img')) as $quoi=>$modeles)
					if (in_array($type,$modeles))
						$GLOBALS["doublons_{$quoi}_inclus"][] = $id;
			}
		}
	}

	return $texte;
}

//
// Raccourcis ancre [#ancre<-]
//

define('_RACCOURCI_ANCRE', "/\[#?([^][]*)<-\]/S");

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

define('_RACCOURCI_GLOSSAIRE', "/\[\?+\s*([^][<>]+)\]/S");
define('_RACCOURCI_GLOSES', '/^([^|#{]*\w[^|#{]*)([^#]*)(#([^|{}]*))?(.*)$/S');

// http://doc.spip.org/@traiter_raccourci_glossaire
function traiter_raccourci_glossaire($texte)
{
	if (!preg_match_all(_RACCOURCI_GLOSSAIRE,
	$texte, $matches, PREG_SET_ORDER))
		return $texte;

	include_spip('inc/charsets');
	$lien = charger_fonction('lien', 'inc');

	foreach ($matches as $regs) {
	// Eviter les cas particulier genre "[?!?]"
	// et isoler le lexeme a gloser de ses accessoires
	// (#:url du glossaire, | bulle d'aide, {} hreflang)
	// Transformation en pseudo-raccourci pour passer dans inc_lien
		if (preg_match(_RACCOURCI_GLOSES, $regs[1], $r)) {
			preg_match('/^(.*?)(\d*)$/', $r[4], $m);
			$_n = intval($m[2]);
			$gloss = $m[1] ? ('#' . $m[1]) : '';
			$t = $r[1] . $r[2] . $r[5];
			list($t, $bulle, $hlang) = traiter_raccourci_lien_atts($t);
			$t = unicode2charset(charset2unicode($t), 'utf-8');
			$ref = $lien("glose$_n$gloss", $t, 'spip_glossaire', $bulle, $hlang);
			$texte = str_replace($regs[0], $ref, $texte);
		}
	}
	return $texte;
}

// http://doc.spip.org/@glossaire_std
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
