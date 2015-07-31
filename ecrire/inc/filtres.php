<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/charsets');
include_spip('inc/filtres_mini');
include_spip('base/objets');
include_spip('public/parametrer'); // charger les fichiers fonctions

/**
 * Charger un filtre depuis le php :
 * - on inclue tous les fichiers fonctions des plugins et du skel
 * - on appelle chercher_filtre
 *
 * @param string $fonc
 * @param string $default
 * @return string
 */
function charger_filtre($fonc, $default='filtre_identite_dist') {
	include_spip('public/parametrer'); // inclure les fichiers fonctions
	return chercher_filtre($fonc, $default);
}

function filtre_identite_dist($texte){return $texte;}

/**
 * http://doc.spip.org/@chercher_filtre
 *
 * @param string $fonc
 * @param null $default
 * @return string
 */
function chercher_filtre($fonc, $default=NULL) {
	if (!$fonc) return $default;
	// Cas des types mime, sans confondre avec les appels de fonction de classe
	// Foo::Bar
	// qui peuvent etre avec un namespace : space\Foo::Bar
	if (preg_match(',^[\w]+/,',$fonc)){
		$nom = preg_replace(',\W,','_', $fonc);
		$f = chercher_filtre($nom);
		// cas du sous-type MIME sans filtre associe, passer au type:
		// si filtre_text_plain pas defini, passe a filtre_text
		if (!$f AND $nom!==$fonc)
			$f = chercher_filtre(preg_replace(',\W.*$,','', $fonc));
		return $f;
	}
	foreach (
	array('filtre_'.$fonc, 'filtre_'.$fonc.'_dist', $fonc) as $f){
		if (isset( $GLOBALS['spip_matrice'][$f]) AND is_string($g = $GLOBALS['spip_matrice'][$f]))
			find_in_path($g,'', true);
		if (function_exists($f)
		OR (preg_match("/^(\w*)::(\w*)$/", $f, $regs)
			AND is_callable(array($regs[1], $regs[2]))
		)) {
			return $f;
		}
	}
	return $default;
}

/**
 * Applique un filtre
 * 
 * Fonction générique qui prend en argument l’objet (texte, etc) à modifier
 * et le nom du filtre. Retrouve les arguments du filtre demandé dans les arguments
 * transmis à cette fonction, via func_get_args().
 *
 * @see filtrer() Assez proche
 * 
 * @param string $arg
 *     Texte sur lequel appliquer le filtre
 * @param string $filtre
 *     Nom du filtre a appliquer
 * @param string $force
 *     La fonction doit-elle retourner le texte ou rien ?
 * @return string
 *     Texte avec le filtre appliqué s'il a été trouvé,
 *     Texte sans le filtre appliqué s'il n'a pas été trouvé et que $force n'a
 *       pas été fourni,
 *     Chaîne vide si le filtre n'a pas été trouvé et que $force a été fourni.
**/
function appliquer_filtre($arg, $filtre, $force=NULL) {
	$f = chercher_filtre($filtre);
	if (!$f) {
		if (!$force) return '';
		else return $arg;
	}

	$args = func_get_args();
	array_shift($args); // enlever $arg
	array_shift($args); // enlever $filtre
	array_unshift($args, $arg); // remettre $arg
	return call_user_func_array($f,$args);
}

// http://doc.spip.org/@spip_version
function spip_version() {
	$version = $GLOBALS['spip_version_affichee'];
	if ($svn_revision = version_svn_courante(_DIR_RACINE))
		$version .= ($svn_revision<0 ? ' SVN':'').' ['.abs($svn_revision).']';
	return $version;
}


//
// Mention de la revision SVN courante de l'espace restreint standard
// (numero non garanti pour l'espace public et en cas de mutualisation)
// on est negatif si on est sur .svn, et positif si on utilise svn.revision
// http://doc.spip.org/@version_svn_courante
function version_svn_courante($dir) {
	if (!$dir) $dir = '.';

	// version installee par paquet ZIP
	if (lire_fichier($dir.'/svn.revision', $c)
	AND preg_match(',Revision: (\d+),', $c, $d))
		return intval($d[1]);

	// version installee par SVN
	if (lire_fichier($dir . '/.svn/entries', $c)
	AND (
	(preg_match_all(
	',committed-rev="([0-9]+)",', $c, $r1, PREG_PATTERN_ORDER)
	AND $v = max($r1[1])
	)
	OR
	(preg_match(',^\d.*dir[\r\n]+(\d+),ms', $c, $r1) # svn >= 1.4
	AND $v = $r1[1]
	)))
		return -$v;

	// Bug ou paquet fait main
	return 0;
}

// La matrice est necessaire pour ne filtrer _que_ des fonctions definies dans filtres_images
// et laisser passer les fonctions personnelles baptisees image_...
$GLOBALS['spip_matrice']['image_graver'] = true;//'inc/filtres_images_mini.php';
$GLOBALS['spip_matrice']['image_select'] = true;//'inc/filtres_images_mini.php';
$GLOBALS['spip_matrice']['image_reduire'] = true;//'inc/filtres_images_mini.php';
$GLOBALS['spip_matrice']['image_reduire_par'] = true;//'inc/filtres_images_mini.php';
$GLOBALS['spip_matrice']['image_passe_partout'] = true;//'inc/filtres_images_mini.php';

$GLOBALS['spip_matrice']['couleur_html_to_hex'] = 'inc/filtres_images_mini.php';
$GLOBALS['spip_matrice']['couleur_foncer'] = 'inc/filtres_images_mini.php';
$GLOBALS['spip_matrice']['couleur_eclaircir'] = 'inc/filtres_images_mini.php';

// ou pour inclure un script au moment ou l'on cherche le filtre
$GLOBALS['spip_matrice']['filtre_image_dist'] = 'inc/filtres_mime.php';
$GLOBALS['spip_matrice']['filtre_audio_dist'] = 'inc/filtres_mime.php';
$GLOBALS['spip_matrice']['filtre_video_dist'] = 'inc/filtres_mime.php';
$GLOBALS['spip_matrice']['filtre_application_dist'] = 'inc/filtres_mime.php';
$GLOBALS['spip_matrice']['filtre_message_dist'] = 'inc/filtres_mime.php';
$GLOBALS['spip_matrice']['filtre_multipart_dist'] = 'inc/filtres_mime.php';
$GLOBALS['spip_matrice']['filtre_text_dist'] = 'inc/filtres_mime.php';
$GLOBALS['spip_matrice']['filtre_text_csv_dist'] = 'inc/filtres_mime.php';
$GLOBALS['spip_matrice']['filtre_text_html_dist'] = 'inc/filtres_mime.php';
$GLOBALS['spip_matrice']['filtre_audio_x_pn_realaudio'] = 'inc/filtres_mime.php';


// charge les fonctions graphiques et applique celle demandee
// http://doc.spip.org/@filtrer
function filtrer($filtre) {
	if (isset($GLOBALS['spip_matrice'][$filtre]) and is_string($f = $GLOBALS['spip_matrice'][$filtre])){
		find_in_path($f,'', true);
		$GLOBALS['spip_matrice'][$filtre] = true;
	}
	$tous = func_get_args();
	if (substr($filtre,0,6)=='image_' && $GLOBALS['spip_matrice'][$filtre])
		return image_filtrer($tous);
	elseif($f = chercher_filtre($filtre)) {
		array_shift($tous);
		return call_user_func_array($f, $tous);
	}
	else {
		// le filtre n'existe pas, on provoque une erreur
		$msg = array('zbug_erreur_filtre', array('filtre'=>texte_script($filtre)));
		erreur_squelette($msg);
		return '';
	}
}

/*
 *
 * [(#CALCUL|set{toto})] enregistre le résultat de #CALCUL
 *           dans la variable toto et renvoie vide
 *
 * [(#CALCUL|set{toto,1})] enregistre le résultat de #CALCUL
 *           dans la variable toto et renvoie la valeur
 *
 */
function filtre_set(&$Pile, $val, $key, $continue = null) {
	$Pile['vars'][$key] = $val;
	return $continue ? $val : '';
}

/*
 * [(#TRUC|debug{avant}|calcul|debug{apres}|etc)] affiche
 *   la valeur de #TRUC avant et après le calcul
 */
function filtre_debug($val, $key=null) {
	$debug = (
		is_null($key) ? '' :  (var_export($key,true)." = ")
	) . var_export($val, true);

	include_spip('inc/autoriser');
	if (autoriser('webmestre'))
		echo "<div class='spip_debug'>\n",$debug,"</div>\n";

	spip_log($debug, 'debug');

	return $val;
}


// fonction generique d'entree des filtres images
// accepte en entree un texte complet, un img-log (produit par #LOGO_XX),
// un tag <img ...> complet, ou encore un nom de fichier *local* (passer
// le filtre |copie_locale si on veut l'appliquer a un document)
// applique le filtre demande a chacune des occurrences

// http://doc.spip.org/@image_filtrer
function image_filtrer($args){
	$filtre = array_shift($args); # enlever $filtre
	$texte = array_shift($args);
	if (!strlen($texte)) return;
	find_in_path('filtres_images_mini.php','inc/', true);
	statut_effacer_images_temporaires(true); // activer la suppression des images temporaires car le compilo finit la chaine par un image_graver
	// Cas du nom de fichier local
	if ( strpos(substr($texte,strlen(_DIR_RACINE)),'..')===FALSE
	AND !preg_match(',^/|[<>]|\s,S', $texte)
	AND (
		file_exists(preg_replace(',[?].*$,','',$texte))
		OR preg_match(';^(\w{3,7}://);', $texte) 
		)) {
		array_unshift($args,"<img src='$texte' />");
		$res = call_user_func_array($filtre, $args);
		statut_effacer_images_temporaires(false); // desactiver pour les appels hors compilo
		return $res;
	}

	// Cas general : trier toutes les images, avec eventuellement leur <span>
	if (preg_match_all(
		',(<([a-z]+) [^<>]*spip_documents[^<>]*>)?\s*(<img\s.*>),UimsS',
		$texte, $tags, PREG_SET_ORDER)) {
		foreach ($tags as $tag) {
			$class = extraire_attribut($tag[3],'class');
			if (!$class || (strpos($class,'no_image_filtrer')===FALSE)){
				array_unshift($args,$tag[3]);
				if ($reduit = call_user_func_array($filtre, $args)) {
					// En cas de span spip_documents, modifier le style=...width:
					if($tag[1]){
						$w = extraire_attribut($reduit, 'width');
						if (!$w AND preg_match(",width:\s*(\d+)px,S",extraire_attribut($reduit,'style'),$regs))
							$w = $regs[1];
						if ($w AND ($style = extraire_attribut($tag[1], 'style'))){
							$style = preg_replace(",width:\s*\d+px,S", "width:${w}px", $style);
							$replace = inserer_attribut($tag[1], 'style', $style);
							$texte = str_replace($tag[1], $replace, $texte);
						}
					}
					// traiter aussi un eventuel mouseover
					if ($mouseover = extraire_attribut($reduit,'onmouseover')){
						if (preg_match(",this[.]src=['\"]([^'\"]+)['\"],ims", $mouseover, $match)){
							$srcover = $match[1];
							array_shift($args);
							array_unshift($args,"<img src='".$match[1]."' />");
							$srcover_filter = call_user_func_array($filtre, $args);
							$srcover_filter = extraire_attribut($srcover_filter,'src');
							$reduit = str_replace($srcover,$srcover_filter,$reduit);
						}
					}
					$texte = str_replace($tag[3], $reduit, $texte);
				}
				array_shift($args);
			}
		}
	}
	statut_effacer_images_temporaires(false); // desactiver pour les appels hors compilo
	return $texte;
}

//
// Retourner taille d'une image
// pour les filtres |largeur et |hauteur
//
// http://doc.spip.org/@taille_image
function taille_image($img) {

	static $largeur_img =array(), $hauteur_img= array();
	$srcWidth = 0;
	$srcHeight = 0;

	$logo = extraire_attribut($img,'src');

	if (!$logo) $logo = $img;
	else {
		$srcWidth = extraire_attribut($img,'width');
		$srcHeight = extraire_attribut($img,'height');
	}

	// ne jamais operer directement sur une image distante pour des raisons de perfo
	// la copie locale a toutes les chances d'etre la ou de resservir
	if (preg_match(';^(\w{3,7}://);', $logo)){
		include_spip('inc/distant');
		$fichier = copie_locale($logo);
		$logo = $fichier ? _DIR_RACINE . $fichier : $logo;
	}
	if (($p=strpos($logo,'?'))!==FALSE)
		$logo=substr($logo,0,$p);

	$srcsize = false;
	if (isset($largeur_img[$logo]))
		$srcWidth = $largeur_img[$logo];
	if (isset($hauteur_img[$logo]))
		$srcHeight = $hauteur_img[$logo];
	if (!$srcWidth OR !$srcHeight){
		if (file_exists($logo)
			AND $srcsize = @getimagesize($logo)){
			if (!$srcWidth)	$largeur_img[$logo] = $srcWidth = $srcsize[0];
			if (!$srcHeight)	$hauteur_img[$logo] = $srcHeight = $srcsize[1];
		}
		// $logo peut etre une reference a une image temporaire dont a n'a que le log .src
		// on s'y refere, l'image sera reconstruite en temps utile si necessaire
		elseif(@file_exists($f = "$logo.src")
		  AND lire_fichier($f,$valeurs)
		  AND $valeurs=unserialize($valeurs)) {
			if (!$srcWidth)	$largeur_img[$logo] = $srcWidth = $valeurs["largeur_dest"];
			if (!$srcHeight)	$hauteur_img[$logo] = $srcHeight = $valeurs["hauteur_dest"];
	  }
	}
	return array($srcHeight, $srcWidth);
}
// http://doc.spip.org/@largeur
function largeur($img) {
	if (!$img) return;
	list ($h,$l) = taille_image($img);
	return $l;
}
// http://doc.spip.org/@hauteur
function hauteur($img) {
	if (!$img) return;
	list ($h,$l) = taille_image($img);
	return $h;
}


// Echappement des entites HTML avec correction des entites "brutes"
// (generees par les butineurs lorsqu'on rentre des caracteres n'appartenant
// pas au charset de la page [iso-8859-1 par defaut])
//
// Attention on limite cette correction aux caracteres "hauts" (en fait > 99
// pour aller plus vite que le > 127 qui serait logique), de maniere a
// preserver des echappements de caracteres "bas" (par exemple [ ou ")
// et au cas particulier de &amp; qui devient &amp;amp; dans les url
// http://doc.spip.org/@corriger_entites_html
function corriger_entites_html($texte) {
	if (strpos($texte,'&amp;') === false) return $texte;
	return preg_replace(',&amp;(#[0-9][0-9][0-9]+;|amp;),iS', '&\1', $texte);
}
// idem mais corriger aussi les &amp;eacute; en &eacute;
// http://doc.spip.org/@corriger_toutes_entites_html
function corriger_toutes_entites_html($texte) {
	if (strpos($texte,'&amp;') === false) return $texte;
	return preg_replace(',&amp;(#?[a-z0-9]+;),iS', '&\1', $texte);
}

// http://doc.spip.org/@proteger_amp
function proteger_amp($texte){
	return str_replace('&','&amp;',$texte);
}

//
/**
 * http://doc.spip.org/@entites_html
 *
 * @param string $texte
 *   chaine a echapper
 * @param bool $tout
 *   corriger toutes les &amp;xx; en &xx;
 * @param bool $quote
 *   echapper aussi les simples quotes en &#039;
 * @return mixed|string
 */
function entites_html($texte, $tout=false, $quote=true) {
	if (!is_string($texte) OR !$texte
	OR strpbrk($texte, "&\"'<>")==false
	) return $texte;
	include_spip('inc/texte');
	$flags = !defined('PHP_VERSION_ID') OR PHP_VERSION_ID < 50400 ? ENT_COMPAT : ENT_COMPAT|ENT_HTML401;
	$texte = spip_htmlspecialchars(echappe_retour(echappe_html($texte, '', true), '', 'proteger_amp'), $quote?ENT_QUOTES:$flags);
	if ($tout)
		return corriger_toutes_entites_html($texte);
	else
		return corriger_entites_html($texte);
}

// Transformer les &eacute; dans le charset local
// http://doc.spip.org/@filtrer_entites
function filtrer_entites($texte) {
	if (strpos($texte,'&') === false) return $texte;
	// filtrer
	$texte = html2unicode($texte);
	// remettre le tout dans le charset cible
	$texte = unicode2charset($texte);
	// cas particulier des " et ' qu'il faut filtrer aussi
	// (on le faisait deja avec un &quot;)
	if (strpos($texte,"&#")!==false)
		$texte = str_replace(array("&#039;","&#39;","&#034;","&#34;"), array("'","'",'"','"'), $texte);
	return $texte;
}

// caracteres de controle - http://www.w3.org/TR/REC-xml/#charsets
// http://doc.spip.org/@supprimer_caracteres_illegaux
function supprimer_caracteres_illegaux($texte) {
	static $from = "\x0\x1\x2\x3\x4\x5\x6\x7\x8\xB\xC\xE\xF\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F";
	static $to = null;
	
	if (is_array($texte)) {
		return array_map('corriger_caracteres_windows', $texte);
	}
	
	if (!$to) $to = str_repeat('-', strlen($from));
	return strtr($texte, $from, $to);
}

// Supprimer caracteres windows et les caracteres de controle ILLEGAUX
// http://doc.spip.org/@corriger_caracteres
function corriger_caracteres ($texte) {
	$texte = corriger_caracteres_windows($texte);
	$texte = supprimer_caracteres_illegaux($texte);
	return $texte;
}

/**
 * Encode du HTML pour transmission XML
 * notamment dans les flux RSS
 *
 * http://doc.spip.org/@texte_backend
 *
 * @param $texte
 * @return mixed
 */
function texte_backend($texte) {

	static $apostrophe = array("&#8217;", "'"); # n'allouer qu'une fois

	// si on a des liens ou des images, les passer en absolu
	$texte = liens_absolus($texte);

	// echapper les tags &gt; &lt;
	$texte = preg_replace(',&(gt|lt);,S', '&amp;\1;', $texte);

	// importer les &eacute;
	$texte = filtrer_entites($texte);

	// " -> &quot; et tout ce genre de choses
	$u = $GLOBALS['meta']['pcre_u'];
	$texte = str_replace("&nbsp;", " ", $texte);
	$texte = preg_replace('/\s{2,}/S'.$u, " ", $texte);
	// ne pas echapper les sinqle quotes car certains outils de syndication gerent mal
	$texte = entites_html($texte, false, false);
	// mais bien echapper les double quotes !
	$texte = str_replace('"','&#034;',$texte);

	// verifier le charset
	$texte = charset2unicode($texte);

	// Caracteres problematiques en iso-latin 1
	if ($GLOBALS['meta']['charset'] == 'iso-8859-1') {
		$texte = str_replace(chr(156), '&#156;', $texte);
		$texte = str_replace(chr(140), '&#140;', $texte);
		$texte = str_replace(chr(159), '&#159;', $texte);
	}

	// l'apostrophe curly pose probleme a certains lecteure de RSS
	// et le caractere apostrophe alourdit les squelettes avec PHP
	// ==> on les remplace par l'entite HTML
	return str_replace($apostrophe, "'", $texte);
}

// Comme ci-dessus, mais avec addslashes final pour squelettes avec PHP (rss)

function texte_backendq($texte) {
	return addslashes(texte_backend($texte));
}

// Enleve le numero des titres numerotes ("1. Titre" -> "Titre")
// http://doc.spip.org/@supprimer_numero
function supprimer_numero($texte) {
	return preg_replace(
	",^[[:space:]]*([0-9]+)([.)]|".chr(194).'?'.chr(176).")[[:space:]]+,S",
	"", $texte);
}

// et la fonction inverse
// http://doc.spip.org/@recuperer_numero
function recuperer_numero($texte) {
	if (preg_match(
	",^[[:space:]]*([0-9]+)([.)]|".chr(194).'?'.chr(176).")[[:space:]]+,S",
	$texte, $regs))
		return strval($regs[1]);
	else
		return '';
}

// Suppression basique et brutale de tous les <...>
// http://doc.spip.org/@supprimer_tags
function supprimer_tags($texte, $rempl = "") {
	$texte = preg_replace(",<[^>]*>,US", $rempl, $texte);
	// ne pas oublier un < final non ferme
	// mais qui peut aussi etre un simple signe plus petit que
	$texte = str_replace('<', ' ', $texte);
	return $texte;
}

// Convertit les <...> en la version lisible en HTML
// http://doc.spip.org/@echapper_tags
function echapper_tags($texte, $rempl = "") {
	$texte = preg_replace("/<([^>]*)>/", "&lt;\\1&gt;", $texte);
	return $texte;
}

// Convertit un texte HTML en texte brut
// http://doc.spip.org/@textebrut
function textebrut($texte) {
	$u = $GLOBALS['meta']['pcre_u'];
	$texte = preg_replace('/\s+/S'.$u, " ", $texte);
	$texte = preg_replace("/<(p|br)( [^>]*)?".">/iS", "\n\n", $texte);
	$texte = preg_replace("/^\n+/", "", $texte);
	$texte = preg_replace("/\n+$/", "", $texte);
	$texte = preg_replace("/\n +/", "\n", $texte);
	$texte = supprimer_tags($texte);
	$texte = preg_replace("/(&nbsp;| )+/S", " ", $texte);
	// nettoyer l'apostrophe curly qui pose probleme a certains rss-readers, lecteurs de mail...
	$texte = str_replace("&#8217;","'",$texte);
	return $texte;
}

// Remplace les liens SPIP en liens ouvrant dans une nouvelle fenetre (target=blank)
// http://doc.spip.org/@liens_ouvrants
function liens_ouvrants ($texte) {
	return preg_replace(",<a\s+([^>]*https?://[^>]*class=[\"']spip_(out|url)\b[^>]+)>,",
		"<a \\1 target=\"_blank\">", $texte);
}

/**
 * Ajouter un attribut rel="nofollow" sur tous les liens d'un texte
 * @param string $texte
 * @return string
 */
function liens_nofollow($texte) {
	if (stripos($texte,"<a")===false)
		return $texte;

	if (preg_match_all(",<a\b[^>]*>,UimsS",$texte, $regs, PREG_PATTERN_ORDER)){
		foreach($regs[0] as $a){
			$rel = extraire_attribut($a,"rel");
			if (strpos($rel,"nofollow")===false){
				$rel = "nofollow" . ($rel?" $rel":"");
				$anofollow = inserer_attribut($a,"rel",$rel);
				$texte = str_replace($a,$anofollow,$texte);
			}
		}
	}

	return $texte;
}

// Transformer les sauts de paragraphe en simples passages a la ligne
// http://doc.spip.org/@PtoBR
function PtoBR($texte){
	$u = $GLOBALS['meta']['pcre_u'];
	$texte = preg_replace("@</p>@iS", "\n", $texte);
	$texte = preg_replace("@<p\b.*>@UiS", "<br />", $texte);
	$texte = preg_replace("@^\s*<br />@S".$u, "", $texte);
	return $texte;
}


/**
 * lignes_longues assure qu'un texte ne vas pas deborder d'un bloc
 * par la faute d'un mot trop long (souvent des URLs)
 * Ne devrait plus etre utilise et fait directement en CSS par un style
 * word-wrap:break-word;
 * cf http://www.alsacreations.com/tuto/lire/1038-gerer-debordement-contenu-css.html
 *
 * Pour assurer la compatibilite du filtre, on encapsule le contenu par
 * un div ou span portant ce style inline.
 *
 * http://doc.spip.org/@lignes_longues
 *
 * @param string $texte
 * @return string
 */
function lignes_longues($texte) {
	if (!strlen(trim($texte))) return $texte;
	include_spip('inc/texte');
	$tag = preg_match(',</?('._BALISES_BLOCS.')[>[:space:]],iS', $texte) ?
		'div' : 'span';

	return "<$tag style='word-wrap:break-word;'>$texte</$tag>";
}

// Majuscules y compris accents, en HTML
// http://doc.spip.org/@majuscules
function majuscules($texte) {
	if (!strlen($texte)) return '';

	// Cas du turc
	if ($GLOBALS['spip_lang'] == 'tr') {
		# remplacer hors des tags et des entites
		if (preg_match_all(',<[^<>]+>|&[^;]+;,S', $texte, $regs, PREG_SET_ORDER))
			foreach ($regs as $n => $match)
				$texte = str_replace($match[0], "@@SPIP_TURC$n@@", $texte);

		$texte = str_replace('i', '&#304;', $texte);

		if ($regs)
			foreach ($regs as $n => $match)
				$texte = str_replace("@@SPIP_TURC$n@@", $match[0], $texte);
	}

	// Cas general
	return "<span style='text-transform: uppercase;'>$texte</span>";
}

// "127.4 ko" ou "3.1 Mo"
// http://doc.spip.org/@taille_en_octets
function taille_en_octets ($taille) {
	if ($taille < 1) return '';
	if ($taille < 1024) {$taille = _T('taille_octets', array('taille' => $taille));}
	else if ($taille < 1024*1024) {
		$taille = _T('taille_ko', array('taille' => round($taille/1024, 1)));
	} else if ($taille < 1024*1024*1024) {
		$taille = _T('taille_mo', array('taille' => round($taille/1024/1024, 1)));
	} else {
		$taille = _T('taille_go', array('taille' => round($taille/1024/1024/1024, 2)));
	}
	return $taille;
}


// Rend une chaine utilisable sans dommage comme attribut HTML
// http://doc.spip.org/@attribut_html
function attribut_html($texte,$textebrut = true) {
	$u = $GLOBALS['meta']['pcre_u'];
	if ($textebrut)
		$texte = preg_replace(array(",\n,",",\s(?=\s),msS".$u),array(" ",""),textebrut($texte));
	$texte = texte_backend($texte);
	$texte = str_replace(array("'",'"'),array('&#039;', '&#034;'), $texte);
	
	return preg_replace(array("/&(amp;|#38;)/","/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,5};)/"),array("&","&#38;") , $texte);
}

// Vider les url nulles comme 'http://' ou 'mailto:'
// et leur appliquer un htmlspecialchars() + gerer les &amp;
// http://doc.spip.org/@vider_url
function vider_url($url, $entites = true) {
	# un message pour abs_url
	$GLOBALS['mode_abs_url'] = 'url';
	$url = trim($url);
	$r = ",^(?:" . _PROTOCOLES_STD . '):?/?/?$,iS';
	return preg_match($r, $url) ? '': ($entites ? entites_html($url) : $url);
}

// Extraire une date de n'importe quel champ (a completer...)
// http://doc.spip.org/@extraire_date
function extraire_date($texte) {
	// format = 2001-08
	if (preg_match(",([1-2][0-9]{3})[^0-9]*(1[0-2]|0?[1-9]),",$texte,$regs))
		return $regs[1]."-".sprintf("%02d", $regs[2])."-01";
}

// Maquiller une adresse e-mail
// http://doc.spip.org/@antispam
function antispam($texte) {
	include_spip('inc/acces');
	$masque = creer_pass_aleatoire(3);
	return preg_replace("/@/", " $masque ", $texte);
}

// http://doc.spip.org/@securiser_acces
function securiser_acces($id_auteur, $cle, $dir, $op='', $args='')
{
	include_spip('inc/acces');
	if ($op) $dir .= " $op $args";
	return verifier_low_sec($id_auteur, $cle, $dir);
}

/**
 * La fonction sinon retourne le second parametre lorsque
 * le premier est considere vide, sinon retourne le premier parametre.
 *
 * En php sinon($a, 'rien') retourne $a ou 'rien' si $a est vide.
 * En filtre spip |sinon{#TEXTE, rien} : affiche #TEXTE ou "rien" si #TEXTE est vide,
 *
 * Note : l'utilisation de |sinon en tant que filtre de squelette
 * est directement compile dans public/references par la fonction filtre_logique()
 * 
 * @param mixed $texte
 * 		Contenu de reference a tester
 * @param mixed $sinon
 * 		Contenu a retourner si le contenu de reference est vide
 * @return mixed
 * 		Retourne $texte, sinon $sinon.
**/
function sinon ($texte, $sinon='') {
	if ($texte OR (!is_array($texte) AND strlen($texte)))
		return $texte;
	else
		return $sinon;
}

// |choixsivide{vide,pasvide} affiche pasvide si la chaine n'est pas vide...
// http://doc.spip.org/@choixsivide
function choixsivide($a, $vide, $pasvide) {
	return $a ? $pasvide : $vide;
}

// |choixsiegal{aquoi,oui,non} affiche oui si la chaine est egal a aquoi ...
// http://doc.spip.org/@choixsiegal
function choixsiegal($a1,$a2,$v,$f) {
	return ($a1 == $a2) ? $v : $f;
}


//
// Date, heure, saisons
//

// on normalise la date, si elle vient du contexte (public/parametrer.php), on force le jour
// http://doc.spip.org/@normaliser_date
function normaliser_date($date, $forcer_jour = false) {
	$date = vider_date($date);
	if ($date) {
		if (preg_match("/^[0-9]{8,10}$/", $date))
			$date = date("Y-m-d H:i:s", $date);
		if (preg_match("#^([12][0-9]{3})([-/]00)?( [-0-9:]+)?$#", $date, $regs))
			$date = $regs[1]."-00-00".$regs[3];
		else if (preg_match("#^([12][0-9]{3}[-/][01]?[0-9])([-/]00)?( [-0-9:]+)?$#", $date, $regs))
			$date = preg_replace("@/@","-",$regs[1])."-00".$regs[3];
		else
			$date = date("Y-m-d H:i:s", strtotime($date));

		if ($forcer_jour)
			$date = str_replace('-00', '-01', $date);
	}
	return $date;
}

// http://doc.spip.org/@vider_date
function vider_date($letexte) {
	if (strncmp("0000-00-00", $letexte,10)==0) return '';
	if (strncmp("0001-01-01", $letexte,10)==0) return '';
	if (strncmp("1970-01-01", $letexte,10)==0) return '';	// eviter le bug GMT-1
	return $letexte;
}

// http://doc.spip.org/@recup_heure
function recup_heure($date){

	static $d = array(0,0,0);
	if (!preg_match('#([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})#', $date, $r)) 
		return $d;
	
	array_shift($r);
	return $r;
}

// http://doc.spip.org/@heures
function heures($numdate) {
	$date_array = recup_heure($numdate);
	if ($date_array)
		list($heures, $minutes, $secondes) = $date_array;
	return $heures;
}

// http://doc.spip.org/@minutes
function minutes($numdate) {
	$date_array = recup_heure($numdate);
	if ($date_array)
		list($heures, $minutes, $secondes) = $date_array;
	return $minutes;
}

// http://doc.spip.org/@secondes
function secondes($numdate) {
	$date_array = recup_heure($numdate);
	if ($date_array)
		list($heures,$minutes,$secondes) = $date_array;
	return $secondes;
}

// http://doc.spip.org/@heures_minutes
function heures_minutes($numdate) {
	return _T('date_fmt_heures_minutes', array('h'=> heures($numdate), 'm'=> minutes($numdate)));
}

// http://doc.spip.org/@recup_date
function recup_date($numdate, $forcer_jour = true){
	if (!$numdate) return '';
	$heures = $minutes = $secondes = 0;
	if (preg_match('#([0-9]{1,2})/([0-9]{1,2})/([0-9]{4}|[0-9]{1,2})#', $numdate, $regs)) {
		$jour = $regs[1];
		$mois = $regs[2];
		$annee = $regs[3];
		if ($annee < 90){
			$annee = 2000 + $annee;
		} elseif ($annee<100) {
			$annee = 1900 + $annee ;
		}
		list($heures, $minutes, $secondes) = recup_heure($numdate);

	}
	elseif (preg_match('#([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})#',$numdate, $regs)) {
		$annee = $regs[1];
		$mois = $regs[2];
		$jour = $regs[3];
		list($heures, $minutes, $secondes) = recup_heure($numdate);
	}
	elseif (preg_match('#([0-9]{4})-([0-9]{2})#', $numdate, $regs)){
		$annee = $regs[1];
		$mois = $regs[2];
		$jour ='';
		list($heures, $minutes, $secondes) = recup_heure($numdate);
	}
	elseif (preg_match('#^([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})$#', $numdate, $regs)){
		$annee = $regs[1];
		$mois = $regs[2];
		$jour = $regs[3];
		$heures = $regs[4];
		$minutes = $regs[5];
		$secondes = $regs[6];
	} else $annee = $mois =  $jour =''; 
	if ($annee > 4000) $annee -= 9000;
	if (substr($jour, 0, 1) == '0') $jour = substr($jour, 1);

	if ($forcer_jour AND $jour == '0') $jour = '1';
	if ($forcer_jour AND $mois == '0') $mois = '1';
	if ($annee OR $mois OR $jour OR $heures OR $minutes OR $secondes)
		return array($annee, $mois, $jour, $heures, $minutes, $secondes);
}

// une date pour l'interface : utilise date_relative si le decalage
// avec time() est de moins de douze heures, sinon la date complete
// http://doc.spip.org/@date_interface
function date_interface($date, $decalage_maxi = 43200/* 12*3600 */) {
	return sinon(
		date_relative($date, $decalage_maxi),
		affdate_heure($date)
	);
}

// http://doc.spip.org/@date_relative
function date_relative($date, $decalage_maxi=0,$ref_date=null) {
	
	if (is_null($ref_date))
		$ref_time = time();
	else
		$ref_time = strtotime($ref_date);
	
	if (!$date) return;
	$decal = date("U",$ref_time) - date("U", strtotime($date));

	if ($decalage_maxi AND ($decal > $decalage_maxi OR $decal < 0))
		return '';

	if ($decal < 0) {
		$il_y_a = "date_dans";
		$decal = -1 * $decal;
	} else {
		$il_y_a = "date_il_y_a";
	}

	if ($decal > 3600 * 24 * 30 * 6)
		return affdate_court($date);

	if ($decal > 3600 * 24 * 30) {
		$mois = floor ($decal / (3600 * 24 * 30));
		if ($mois < 2)
			$delai = "$mois "._T("date_un_mois");
		else
			$delai = "$mois "._T("date_mois");
	}
	else if ($decal > 3600 * 24 * 7) {
		$semaines = floor ($decal / (3600 * 24 * 7));
		if ($semaines < 2)
			$delai = "$semaines "._T("date_une_semaine");
		else
			$delai = "$semaines "._T("date_semaines");
	}
	else if ($decal > 3600 * 24) {
		$jours = floor ($decal / (3600 * 24));
		if ($jours < 2)
			return $il_y_a=="date_dans"?_T("date_demain"):_T("date_hier");
		else
			$delai = "$jours "._T("date_jours");
	}
	else if ($decal >= 3600) {
		$heures = floor ($decal / 3600);
		if ($heures < 2)
			$delai = "$heures "._T("date_une_heure");
		else
			$delai = "$heures "._T("date_heures");
	}
	else if ($decal >= 60) {
		$minutes = floor($decal / 60);
		if ($minutes < 2)
			$delai = "$minutes "._T("date_une_minute");
		else
			$delai = "$minutes "._T("date_minutes");
	} else {
		$secondes = ceil($decal);
		if ($secondes < 2)
			$delai = "$secondes "._T("date_une_seconde");
		else
			$delai = "$secondes "._T("date_secondes");
	}

	return _T($il_y_a, array("delai"=> $delai));
}


// http://doc.spip.org/@date_relativecourt
function date_relativecourt($date, $decalage_maxi=0) {
	
	if (!$date) return;
	$decal = date("U",strtotime(date('Y-m-d'))-strtotime(date('Y-m-d',strtotime($date))));

	if ($decalage_maxi AND ($decal > $decalage_maxi OR $decal < 0))
		return '';

	if ($decal < -24*3600) {
		$retour = date_relative($date, $decalage_maxi);
	}
	elseif ($decal < 0) {
		$retour = _T("date_demain");
	}
	else if ($decal < (3600 * 24) ) {
		$retour = _T("date_aujourdhui");
	}
	else if ($decal < (3600 * 24 *2) ) {
		$retour = _T("date_hier");
	}
	else {
		$retour = date_relative($date, $decalage_maxi);
	}



	return $retour;
}

/**
 * Formatage humain de la date $numdate selon le format $vue
 * http://doc.spip.org/@affdate_base
 *
 * @param $numdate
 * @param $vue
 * @param array $options
 *   param : 'abbr' ou 'initiale' permet d'afficher les jours au format court ou initiale
 *   annee_courante : permet de definir l'annee de reference pour l'affichage des dates courtes
 * @return mixed|string
 */
function affdate_base($numdate, $vue, $options = array()) {
	if (is_string($options))
		$options = array('param'=>$options);
	$date_array = recup_date($numdate, false);
	if (!$date_array) return;
	list($annee, $mois, $jour, $heures, $minutes, $secondes)= $date_array;

	// 1er, 21st, etc.
	$journum = $jour;

	if ($jour == 0) {
		$jour = '';
	} else {
		$njour = intval($jour);
		if ($jourth = _T('date_jnum'.$jour))
			$jour = $jourth;
	}

	$mois = intval($mois);
	if ($mois > 0 AND $mois < 13) {
		$nommois = _T('date_mois_'.$mois);
		if ($jour)
			$jourmois = _T('date_de_mois_'.$mois, array('j'=>$jour, 'nommois'=>$nommois));
		else
			$jourmois = $nommois;
	} else $nommois = '';

	if ($annee < 0) {
		$annee = -$annee." "._T('date_avant_jc');
		$avjc = true;
	}
	else $avjc = false;

	switch ($vue) {
	case 'saison':
	case 'saison_annee':
		$saison = '';
		if ($mois > 0){
			$saison = 1;
			if (($mois == 3 AND $jour >= 21) OR $mois > 3) $saison = 2;
			if (($mois == 6 AND $jour >= 21) OR $mois > 6) $saison = 3;
			if (($mois == 9 AND $jour >= 21) OR $mois > 9) $saison = 4;
			if (($mois == 12 AND $jour >= 21) OR $mois > 12) $saison = 1;
		}
		if($vue == 'saison')
			return $saison?_T('date_saison_'.$saison):'';
		else
			return $saison?trim(_T('date_fmt_saison_annee', array('saison'=>_T('date_saison_'.$saison), 'annee'=>$annee))) :'';

	case 'court':
		if ($avjc) return $annee;
		$a = ((isset($options['annee_courante']) AND $options['annee_courante'])?$options['annee_courante']:date('Y'));
		if ($annee < ($a - 100) OR $annee > ($a + 100)) return $annee;
		if ($annee != $a) return _T('date_fmt_mois_annee', array ('mois'=>$mois, 'nommois'=>spip_ucfirst($nommois), 'annee'=>$annee));
		return _T('date_fmt_jour_mois', array ('jourmois'=>$jourmois, 'jour'=>$jour, 'mois'=>$mois, 'nommois'=>$nommois, 'annee'=>$annee));

	case 'jourcourt':
		if ($avjc) return $annee;
		$a = ((isset($options['annee_courante']) AND $options['annee_courante'])?$options['annee_courante']:date('Y'));
		if ($annee < ($a - 100) OR $annee > ($a + 100)) return $annee;
		if ($annee != $a) return _T('date_fmt_jour_mois_annee', array ('jourmois'=>$jourmois, 'jour'=>$jour, 'mois'=>$mois, 'nommois'=>$nommois, 'annee'=>$annee));
		return _T('date_fmt_jour_mois', array ('jourmois'=>$jourmois, 'jour'=>$jour, 'mois'=>$mois, 'nommois'=>$nommois, 'annee'=>$annee));

	case 'entier':
		if ($avjc) return $annee;
		if ($jour)
			return _T('date_fmt_jour_mois_annee', array ('jourmois'=>$jourmois, 'jour'=>$jour, 'mois'=>$mois, 'nommois'=>$nommois, 'annee'=>$annee));
		elseif ($mois)
			return trim(_T('date_fmt_mois_annee', array ('mois'=>$mois, 'nommois'=>$nommois, 'annee'=>$annee)));
		else
			return $annee;

	case 'nom_mois':
		$param = ((isset($options['param']) AND $options['param']) ? '_'.$options['param'] : '');
		if ($param and $mois) {
			return _T('date_mois_'.$mois.$param);
		}
		return $nommois;

	case 'mois':
		return sprintf("%02s",$mois);

	case 'jour':
		return $jour;

	case 'journum':
		return $journum;

	case 'nom_jour':
		if (!$mois OR !$njour)
			return '';
		$nom = mktime(1,1,1,$mois,$njour,$annee);
		$nom = 1+date('w',$nom);
		$param = ((isset($options['param']) AND $options['param']) ? '_'.$options['param'] : '');
		return _T('date_jour_'.$nom.$param);

	case 'mois_annee':
		if ($avjc) return $annee;
		return trim(_T('date_fmt_mois_annee', array('mois'=>$mois, 'nommois'=>$nommois, 'annee'=>$annee)));

	case 'annee':
		return $annee;

	// Cas d'une vue non definie : retomber sur le format
	// de date propose par http://www.php.net/date
	default:
		return date($vue, strtotime($numdate));
	}
}

// http://doc.spip.org/@nom_jour
function nom_jour($numdate, $forme = '') {
	if(!($forme == 'abbr' OR $forme == 'initiale')) $forme = '';
	return affdate_base($numdate, 'nom_jour', $forme);
}

// http://doc.spip.org/@jour
function jour($numdate) {
	return affdate_base($numdate, 'jour');
}

// http://doc.spip.org/@journum
function journum($numdate) {
	return affdate_base($numdate, 'journum');
}

// http://doc.spip.org/@mois
function mois($numdate) {
	return affdate_base($numdate, 'mois');
}

// http://doc.spip.org/@nom_mois
function nom_mois($numdate, $forme='') {
	if(!($forme == 'abbr')) $forme = '';
	return affdate_base($numdate, 'nom_mois', $forme);
}

// http://doc.spip.org/@annee
function annee($numdate) {
	return affdate_base($numdate, 'annee');
}

// http://doc.spip.org/@saison
function saison($numdate) {
	return affdate_base($numdate, 'saison');
}

// http://doc.spip.org/@saison_annee
function saison_annee($numdate) {
	return affdate_base($numdate, 'saison_annee');
}

// http://doc.spip.org/@affdate
function affdate($numdate, $format='entier') {
	return affdate_base($numdate, $format);
}

// http://doc.spip.org/@affdate_court
function affdate_court($numdate, $annee_courante=null) {
	return affdate_base($numdate, 'court', array('annee_courante'=>$annee_courante));
}

// http://doc.spip.org/@affdate_jourcourt
function affdate_jourcourt($numdate, $annee_courante=null) {
	return affdate_base($numdate, 'jourcourt', array('annee_courante'=>$annee_courante));
}

// http://doc.spip.org/@affdate_mois_annee
function affdate_mois_annee($numdate) {
	return affdate_base($numdate, 'mois_annee');
}

// http://doc.spip.org/@affdate_heure
function affdate_heure($numdate) {
	$date_array = recup_date($numdate);
	if (!$date_array) return;
	list($annee, $mois, $jour, $heures, $minutes, $sec)= $date_array;
	return _T('date_fmt_jour_heure', array('jour' => affdate($numdate), 'heure' =>  _T('date_fmt_heures_minutes', array('h'=> $heures, 'm'=> $minutes))));
}

/**
 * Afficher de facon textuelle les dates de debut et fin en fonction des cas
 * - Lundi 20 fevrier a 18h
 * - Le 20 fevrier de 18h a 20h
 * - Du 20 au 23 fevrier
 * - Du 20 fevrier au 30 mars
 * - Du 20 fevrier 2007 au 30 mars 2008
 * $horaire='oui' ou true permet d'afficher l'horaire, toute autre valeur n'indique que le jour
 * $forme peut contenir une ou plusieurs valeurs parmi
 *  - abbr (afficher le nom des jours en abbrege)
 *  - hcal (generer une date au format hcal)
 *  - jour (forcer l'affichage des jours)
 *  - annee (forcer l'affichage de l'annee)
 *
 * @param string $date_debut
 * @param string $date_fin
 * @param string $horaire
 * @param string $forme
 *   abbr pour afficher le nom du jour en abbrege (Dim. au lieu de Dimanche)
 *   annee pour forcer l'affichage de l'annee courante
 *   jour pour forcer l'affichage du nom du jour
 *   hcal pour pour avoir un markup microformat abbr
 * @return string
 */
function affdate_debut_fin($date_debut, $date_fin, $horaire = 'oui', $forme=''){
	$abbr = $jour = '';
	$affdate = "affdate_jourcourt";
	if (strpos($forme,'abbr') !==false) $abbr = 'abbr';
	if (strpos($forme,'annee')!==false) $affdate = 'affdate';
	if (strpos($forme,'jour') !==false) $jour = 'jour';
	
	$dtstart = $dtend = $dtabbr = "";
	if (strpos($forme,'hcal')!==false) {
		$dtstart = "<abbr class='dtstart' title='".date_iso($date_debut)."'>";
		$dtend = "<abbr class='dtend' title='".date_iso($date_fin)."'>";
		$dtabbr = "</abbr>";
	}

	$date_debut = strtotime($date_debut);
	$date_fin = strtotime($date_fin);
	$d = date("Y-m-d", $date_debut);
	$f = date("Y-m-d", $date_fin);
	$h = ($horaire==='oui' OR $horaire===true);
	$hd = _T('date_fmt_heures_minutes_court', array('h'=> date("H",$date_debut), 'm'=> date("i",$date_debut)));
	$hf = _T('date_fmt_heures_minutes_court', array('h'=> date("H",$date_fin), 'm'=> date("i",$date_fin)));

	if ($d==$f)
	{ // meme jour
		$nomjour = nom_jour($d,$abbr);
		$s = $affdate($d);
		$s = _T('date_fmt_jour',array('nomjour'=>$nomjour,'jour' => $s));
		if ($h){
			if ($hd==$hf){
				// Lundi 20 fevrier a 18h25
				$s = spip_ucfirst(_T('date_fmt_jour_heure',array('jour'=>$s,'heure'=>$hd)));
				$s = "$dtstart$s$dtabbr";
			}else{
				// Le <abbr...>lundi 20 fevrier de 18h00</abbr> a <abbr...>20h00</abbr>
				if($dtabbr && $dtstart && $dtend)
					$s = _T('date_fmt_jour_heure_debut_fin_abbr',array('jour'=>spip_ucfirst($s),'heure_debut'=>$hd,'heure_fin'=>$hf,'dtstart'=>$dtstart,'dtend'=>$dtend,'dtabbr'=>$dtabbr));
				// Le lundi 20 fevrier de 18h00 a 20h00
				else
					$s = spip_ucfirst(_T('date_fmt_jour_heure_debut_fin',array('jour'=>$s,'heure_debut'=>$hd,'heure_fin'=>$hf)));
			}
		}else{
			if($dtabbr && $dtstart)
				$s = $dtstart.spip_ucfirst($s).$dtabbr;
			else
				$s = spip_ucfirst($s);
		}
	}
	else if ((date("Y-m",$date_debut))==date("Y-m",$date_fin))
	{ // meme annee et mois, jours differents
		if(!$h)
			$date_debut = jour($d);
		else
			$date_debut = affdate_jourcourt($d,date("Y",$date_fin));
		$date_fin = $affdate($f);
		if($jour){
			$nomjour_debut = nom_jour($d,$abbr);
			$date_debut = _T('date_fmt_jour',array('nomjour'=>$nomjour_debut,'jour' => $date_debut));
			$nomjour_fin = nom_jour($f,$abbr);
			$date_fin = _T('date_fmt_jour',array('nomjour'=>$nomjour_fin,'jour' => $date_fin));
		}
		if ($h){
			$date_debut = _T('date_fmt_jour_heure',array('jour'=>$date_debut,'heure'=>$hd));
			$date_fin = _T('date_fmt_jour_heure',array('jour'=>$date_fin,'heure'=>$hf));
		}
		$date_debut = $dtstart.$date_debut.$dtabbr;
		$date_fin = $dtend.$date_fin.$dtabbr;
		
		$s = _T('date_fmt_periode',array('date_debut' => $date_debut,'date_fin'=>$date_fin));
	}
	else {
		$date_debut = affdate_jourcourt($d,date("Y",$date_fin));
		$date_fin = $affdate($f);
		if($jour){
			$nomjour_debut = nom_jour($d,$abbr);
			$date_debut = _T('date_fmt_jour',array('nomjour'=>$nomjour_debut,'jour' => $date_debut));
			$nomjour_fin = nom_jour($f,$abbr);
			$date_fin = _T('date_fmt_jour',array('nomjour'=>$nomjour_fin,'jour' => $date_fin));
		}
		if ($h){
			$date_debut = _T('date_fmt_jour_heure',array('jour'=>$date_debut,'heure'=>$hd)); 
			$date_fin = _T('date_fmt_jour_heure',array('jour'=>$date_fin,'heure'=>$hf));
		}
		
		$date_debut = $dtstart.$date_debut.$dtabbr;
		$date_fin=$dtend.$date_fin.$dtabbr;
		$s = _T('date_fmt_periode',array('date_debut' => $date_debut,'date_fin'=>$date_fin));
		
	}
	return $s;
}

/**
 * Alignements en HTML (Old-style, preferer CSS)
 * Cette fonction ne cree pas de paragraphe
 *
 * http://doc.spip.org/@aligner
 *
 * @param  $letexte
 * @param string $justif
 * @return string
 */
function aligner($letexte, $justif='') {
	$letexte = trim($letexte);
	if (!strlen($letexte)) return '';

	// Paragrapher rapidement
	$letexte = "<div style='text-align:$justif'>"
		. $letexte
	  ."</div>";

	return $letexte;
}
// http://doc.spip.org/@justifier
function justifier($letexte) { return aligner($letexte,'justify');}
// http://doc.spip.org/@aligner_droite
function aligner_droite($letexte) { return aligner($letexte,'right');}
// http://doc.spip.org/@aligner_gauche
function aligner_gauche($letexte) {return aligner($letexte,'left');}
// http://doc.spip.org/@centrer
function centrer($letexte) {return aligner($letexte,'center');}

// http://doc.spip.org/@style_align
function style_align($bof) {
	global $spip_lang_left;
	return "text-align: $spip_lang_left";
}

//
// Export iCal
//

// http://doc.spip.org/@filtrer_ical
function filtrer_ical($texte) {
	#include_spip('inc/charsets');
	$texte = html2unicode($texte);
	$texte = unicode2charset(charset2unicode($texte, $GLOBALS['meta']['charset'], 1), 'utf-8');
	$texte = preg_replace("/\n/", " ", $texte);
	$texte = preg_replace("/,/", "\,", $texte);

	return $texte;
}

// http://doc.spip.org/@date_ical
function date_ical($date, $addminutes = 0) {
	list($heures, $minutes, $secondes) = recup_heure($date);
	list($annee, $mois, $jour) = recup_date($date);
	return date("Ymd\THis", 
		    mktime($heures, $minutes+$addminutes,$secondes,$mois,$jour,$annee));
}

// date_iso retourne la date au format "RFC 3339" / "ISO 8601"
// voir http://www.php.net/manual/fr/ref.datetime.php#datetime.constants
// http://doc.spip.org/@date_iso
function date_iso($date_heure) {
	list($annee, $mois, $jour) = recup_date($date_heure);
	list($heures, $minutes, $secondes) = recup_heure($date_heure);
	$time = @mktime($heures, $minutes, $secondes, $mois, $jour, $annee);
	return gmdate('Y-m-d\TH:i:s\Z', $time);
}

// date_822 retourne la date au format "RFC 822"
// utilise pour <pubdate> dans certains feeds RSS
// http://doc.spip.org/@date_822
function date_822($date_heure) {
	list($annee, $mois, $jour) = recup_date($date_heure);
	list($heures, $minutes, $secondes) = recup_heure($date_heure);
	$time = mktime($heures, $minutes, $secondes, $mois, $jour, $annee);
	return date('r', $time);
}

// http://doc.spip.org/@date_anneemoisjour
function date_anneemoisjour($d)  {
	if (!$d) $d = date("Y-m-d");
	return  substr($d, 0, 4) . substr($d, 5, 2) .substr($d, 8, 2);
}

// http://doc.spip.org/@date_anneemois
function date_anneemois($d)  {
	if (!$d) $d = date("Y-m-d");
	return  substr($d, 0, 4) . substr($d, 5, 2);
}

// http://doc.spip.org/@date_debut_semaine
function date_debut_semaine($annee, $mois, $jour) {
  $w_day = date("w", mktime(0,0,0,$mois, $jour, $annee));
  if ($w_day == 0) $w_day = 7; // Gaffe: le dimanche est zero
  $debut = $jour-$w_day+1;
  return date("Ymd", mktime(0,0,0,$mois,$debut,$annee));
}

// http://doc.spip.org/@date_fin_semaine
function date_fin_semaine($annee, $mois, $jour) {
  $w_day = date("w", mktime(0,0,0,$mois, $jour, $annee));
  if ($w_day == 0) $w_day = 7; // Gaffe: le dimanche est zero
  $debut = $jour-$w_day+1;
  return date("Ymd", mktime(0,0,0,$mois,$debut+6,$annee));
}


//
// Recuperation de donnees dans le champ extra
// Ce filtre n'a de sens qu'avec la balise #EXTRA
//
// http://doc.spip.org/@extra
function extra($letexte, $champ) {
	$champs = unserialize($letexte);
	return $champs[$champ];
}

// postautobr : transforme les sauts de ligne en _
// http://doc.spip.org/@post_autobr
function post_autobr($texte, $delim="\n_ ") {
	if (!function_exists('echappe_html'))
		include_spip('inc/texte_mini');
	$texte = str_replace("\r\n", "\r", $texte);
	$texte = str_replace("\r", "\n", $texte);

	if (preg_match(",\n+$,", $texte, $fin))
		$texte = substr($texte, 0, -strlen($fin = $fin[0]));
	else
		$fin = '';

	$texte = echappe_html($texte, '', true);

	// echapper les modeles
	if (strpos($texte,"<")!==false){
		include_spip('inc/lien');
		if (defined('_PREG_MODELE')){
			$preg_modeles = "@"._PREG_MODELE."@imsS";
			$texte = echappe_html($texte, '', true, $preg_modeles);
		}
	}

	$debut = '';
	$suite = $texte;
	while ($t = strpos('-'.$suite, "\n", 1)) {
		$debut .= substr($suite, 0, $t-1);
		$suite = substr($suite, $t);
		$car = substr($suite, 0, 1);
		if (($car<>'-') AND ($car<>'_') AND ($car<>"\n") AND ($car<>"|") AND ($car<>"}")
		AND !preg_match(',^\s*(\n|</?(quote|div|dl|dt|dd)|$),S',($suite))
		AND !preg_match(',</?(quote|div|dl|dt|dd)> *$,iS', $debut)) {
			$debut .= $delim;
		} else
			$debut .= "\n";
		if (preg_match(",^\n+,", $suite, $regs)) {
			$debut.=$regs[0];
			$suite = substr($suite, strlen($regs[0]));
		}
	}
	$texte = $debut.$suite;

	$texte = echappe_retour($texte);
	return $texte.$fin;
}


define('_EXTRAIRE_MULTI', "@<multi>(.*?)</multi>@sS");

// Extraire et transformer les blocs multi ; on indique la langue courante
// pour ne pas mettre de span@lang=fr si on est deja en fr
// http://doc.spip.org/@extraire_multi
function extraire_multi($letexte, $lang=null, $echappe_span=false) {
	if (preg_match_all(_EXTRAIRE_MULTI, $letexte, $regs, PREG_SET_ORDER)) {
		if (!$lang) $lang = $GLOBALS['spip_lang'];

		foreach ($regs as $reg) {
			// chercher la version de la langue courante
			$trads = extraire_trads($reg[1]);
			if ($l = approcher_langue($trads, $lang)) {
				$trad = $trads[$l];
			} else {
				include_spip('inc/texte');
				// langue absente, prendre la premiere dispo
				// mais typographier le texte selon les regles de celle-ci
				// Attention aux blocs multi sur plusieurs lignes
				$l = key($trads);
				$trad = $trads[$l];
				$typographie = charger_fonction(lang_typo($l), 'typographie');
				$trad = $typographie($trad);
				include_spip('inc/texte');
				// Tester si on echappe en span ou en div
				// il ne faut pas echapper en div si propre produit un seul paragraphe
				$trad_propre = preg_replace(",(^<p[^>]*>|</p>$),Uims","",propre($trad));
				$mode = preg_match(',</?('._BALISES_BLOCS.')[>[:space:]],iS', $trad_propre) ? 'div' : 'span';
				$trad = code_echappement($trad, 'multi', false, $mode);
				$trad = str_replace("'", '"', inserer_attribut($trad, 'lang', $l));
				if (lang_dir($l) !== lang_dir($lang))
					$trad = str_replace("'", '"', inserer_attribut($trad, 'dir', lang_dir($l)));
				if (!$echappe_span)
					$trad = echappe_retour($trad, 'multi');
			}
			$letexte = str_replace($reg[0], $trad, $letexte);
		}
	}

	return $letexte;
}

// convertit le contenu d'une balise multi en un tableau
// http://doc.spip.org/@extraire_trad
function extraire_trads($bloc) {
	$lang = '';
// ce reg fait planter l'analyse multi s'il y a de l'{italique} dans le champ
//	while (preg_match("/^(.*?)[{\[]([a-z_]+)[}\]]/siS", $bloc, $regs)) {
	while (preg_match("/^(.*?)[\[]([a-z_]+)[\]]/siS", $bloc, $regs)) {
		$texte = trim($regs[1]);
		if ($texte OR $lang)
			$trads[$lang] = $texte;
		$bloc = substr($bloc, strlen($regs[0]));
		$lang = $regs[2];
	}
	$trads[$lang] = $bloc;

	return $trads;
}

// Calculer l'initiale d'un nom
function initiale($nom){
	return spip_substr(trim(strtoupper(extraire_multi($nom))),0,1);
}

//
// Ce filtre retourne la donnee si c'est la premiere fois qu'il la voit ;
// possibilite de gerer differentes "familles" de donnees |unique{famille}
# |unique{famille,1} affiche le nombre d'elements affiches (preferer toutefois #TOTAL_UNIQUE)
# ameliorations possibles :
# 1) si la donnee est grosse, mettre son md5 comme cle
# 2) purger $mem quand on change de squelette (sinon bug inclusions)
//
// http://www.spip.net/@unique
// http://doc.spip.org/@unique
function unique($donnee, $famille='', $cpt = false) {
	static $mem = array();
	// permettre de vider la pile et de la restaurer
	// pour le calcul de introduction...
	if ($famille=='_spip_raz_'){
		$tmp = $mem;
		$mem = array();
		return $tmp;
	} elseif ($famille=='_spip_set_'){
		$mem = $donnee;
		return;
	}
	// eviter une notice
	if (!isset($mem[$famille])) {
		$mem[$famille] = array();
	}
	if ($cpt) {
		return count($mem[$famille]);
	}
	// eviter une notice
	if (!isset($mem[$famille][$donnee])) {
		$mem[$famille][$donnee] = 0;
	}
	if (!($mem[$famille][$donnee]++)) {
		return $donnee;
	}
}

//
// Filtre |alterner
//
// Exemple [(#COMPTEUR_BOUCLE|alterner{'bleu','vert','rouge'})]
//
// http://doc.spip.org/@alterner
function alterner($i) {
	// recuperer les arguments (attention fonctions un peu space)
	$num = func_num_args();
	$args = func_get_args();

	if($num == 2 && is_array($args[1])) {
    $args = $args[1];
    array_unshift($args,'');
    $num = count($args);
  }

	// renvoyer le i-ieme argument, modulo le nombre d'arguments
	return $args[(intval($i)-1)%($num-1)+1];
}

// recuperer un attribut d'une balise html
// ($complet demande de retourner $r)
// la regexp est mortelle : cf. tests/filtres/extraire_attribut.php
// Si on a passe un tableau de balises, renvoyer un tableau de resultats
// (dans ce cas l'option $complet n'est pas disponible)
// http://doc.spip.org/@extraire_attribut
function extraire_attribut($balise, $attribut, $complet = false) {
	if (is_array($balise)) {
		array_walk($balise,
			create_function('&$a,$key,$t',
				'$a = extraire_attribut($a,$t);'
			),
			$attribut);
		return $balise;
	}
	if (preg_match(
	',(^.*?<(?:(?>\s*)(?>[\w:.-]+)(?>(?:=(?:"[^"]*"|\'[^\']*\'|[^\'"]\S*))?))*?)(\s+'
	.$attribut
	.'(?:=\s*("[^"]*"|\'[^\']*\'|[^\'"]\S*))?)()([^>]*>.*),isS',

	$balise, $r)) {
		if ($r[3][0] == '"' || $r[3][0] == "'") {
			$r[4] = substr($r[3], 1, -1);
			$r[3] = $r[3][0];
		} elseif ($r[3]!=='') {
			$r[4] = $r[3]; 
			$r[3] = '';
		} else {
			$r[4] = trim($r[2]); 
		}
		$att = $r[4];
		if (strpos($att,"&#")!==false)
			$att = str_replace(array("&#039;","&#39;","&#034;","&#34;"), array("'","'",'"','"'), $att);
		$att = filtrer_entites($att);
	}
	else
		$att = NULL;

	if ($complet)
		return array($att, $r);
	else
		return $att;
}

/**
 * modifier (ou inserer) un attribut html dans une balise
 *
 * http://doc.spip.org/@inserer_attribut
 *
 * @param string $balise
 * @param string $attribut
 * @param string $val
 * @param bool $proteger
 * @param bool $vider
 * @return string
 */
function inserer_attribut($balise, $attribut, $val, $proteger=true, $vider=false) {
	// preparer l'attribut
	// supprimer les &nbsp; etc mais pas les balises html
	// qui ont un sens dans un attribut value d'un input
	if ($proteger) $val = attribut_html($val,false);

	// echapper les ' pour eviter tout bug
	$val = str_replace("'", "&#039;", $val);
	if ($vider AND strlen($val)==0)
		$insert = '';
	else
		$insert = " $attribut='$val'";

	list($old, $r) = extraire_attribut($balise, $attribut, true);

	if ($old !== NULL) {
		// Remplacer l'ancien attribut du meme nom
		$balise = $r[1].$insert.$r[5];
	}
	else {
		// preferer une balise " />" (comme <img />)
		if (preg_match(',/>,', $balise))
			$balise = preg_replace(",\s?/>,S", $insert." />", $balise, 1);
		// sinon une balise <a ...> ... </a>
		else
			$balise = preg_replace(",\s?>,S", $insert.">", $balise, 1);
	}

	return $balise;
}

// http://doc.spip.org/@vider_attribut
function vider_attribut ($balise, $attribut) {
	return inserer_attribut($balise, $attribut, '', false, true);
}


/**
 * Un filtre pour determiner le nom du satut des inscrits
 *
 * @param void|int $id
 * @param string $mode
 * @return string
 */
function tester_config($id, $mode='') {
	include_spip('action/inscrire_auteur');
	return tester_statut_inscription($mode, $id);
}

//
// Quelques fonctions de calcul arithmetique
//
// http://doc.spip.org/@plus
function plus($a,$b) {
	return $a+$b;
}
// http://doc.spip.org/@moins
function moins($a,$b) {
	return $a-$b;
}
// http://doc.spip.org/@mult
function mult($a,$b) {
	return $a*$b;
}
// http://doc.spip.org/@div
function div($a,$b) {
	return $b?$a/$b:0;
}
// http://doc.spip.org/@modulo
function modulo($nb, $mod, $add=0) {
	return ($mod?$nb%$mod:0)+$add;
}


/**
 * Vérifie qu'un nom (d'auteur) ne comporte pas d'autres tags que <multi>
 * et ceux volontairement spécifiés dans la constante
 *
 * @param string $nom
 *      Nom (signature) proposé
 * @return bool
 *      - false si pas conforme,
 *      - true sinon
**/
function nom_acceptable($nom) {
	if (!is_string($nom)) {
		return false;
	}
	if (!defined('_TAGS_NOM_AUTEUR')) define('_TAGS_NOM_AUTEUR','');
	$tags_acceptes = array_unique(explode(',', 'multi,' . _TAGS_NOM_AUTEUR));
	foreach($tags_acceptes as $tag) {
		if (strlen($tag)) {
			$remp1[] = '<'.trim($tag).'>';
			$remp1[] = '</'.trim($tag).'>';
			$remp2[] = '\x60'.trim($tag).'\x61';
			$remp2[] = '\x60/'.trim($tag).'\x61';
		}
	}	
	$v_nom = str_replace($remp2, $remp1, supprimer_tags(str_replace($remp1, $remp2, $nom)));
	return str_replace('&lt;', '<', $v_nom) == $nom;
}

// Verifier la conformite d'une ou plusieurs adresses email
//  retourne false ou la  normalisation de la derniere adresse donnee
// http://doc.spip.org/@email_valide
function email_valide($adresses) {
	// eviter d'injecter n'importe quoi dans preg_match
	if (!is_string($adresses))
		return false;

	// Si c'est un spammeur autant arreter tout de suite
	if (preg_match(",[\n\r].*(MIME|multipart|Content-),i", $adresses)) {
		spip_log("Tentative d'injection de mail : $adresses");
		return false;
	}

	foreach (explode(',', $adresses) as $v) {
		// nettoyer certains formats
		// "Marie Toto <Marie@toto.com>"
		$adresse = trim(preg_replace(",^[^<>\"]*<([^<>\"]+)>$,i", "\\1", $v));
		// RFC 822
		if (!preg_match('#^[^()<>@,;:\\"/[:space:]]+(@([-_0-9a-z]+\.)*[-_0-9a-z]+)$#i', $adresse))
			return false;
	}
	return $adresse;
}

// http://doc.spip.org/@afficher_enclosures
function afficher_enclosures($tags) {
	$s = array();
	foreach (extraire_balises($tags, 'a') as $tag) {
		if (extraire_attribut($tag, 'rel') == 'enclosure'
		AND $t = extraire_attribut($tag, 'href')) {
			$s[] = preg_replace(',>[^<]+</a>,S', 
				'>'
				.http_img_pack('attachment-16.png', $t,
					'title="'.attribut_html($t).'"')
				.'</a>', $tag);
		}
	}
	return join('&nbsp;', $s);
}
// http://doc.spip.org/@afficher_tags
function afficher_tags($tags, $rels='tag,directory') {
	$s = array();
	foreach (extraire_balises($tags, 'a') as $tag) {
		$rel = extraire_attribut($tag, 'rel');
		if (strstr(",$rels,", ",$rel,"))
			$s[] = $tag;
	}
	return join(', ', $s);
}

// Passe un <enclosure url="fichier" length="5588242" type="audio/mpeg"/>
// au format microformat <a rel="enclosure" href="fichier" ...>fichier</a>
// attention length="zz" devient title="zz", pour rester conforme
// http://doc.spip.org/@enclosure2microformat
function enclosure2microformat($e) {
	if (!$url = filtrer_entites(extraire_attribut($e, 'url')))
		$url = filtrer_entites(extraire_attribut($e, 'href'));
	$type = extraire_attribut($e, 'type');
	if (!$length = extraire_attribut($e, 'length')) {
		# <media:content : longeur dans fileSize. On tente.
		$length = extraire_attribut($e, 'fileSize');
	}
	$fichier = basename($url);
	return '<a rel="enclosure"'
		. ($url? ' href="'.spip_htmlspecialchars($url).'"' : '')
		. ($type? ' type="'.spip_htmlspecialchars($type).'"' : '')
		. ($length? ' title="'.spip_htmlspecialchars($length).'"' : '')
		. '>'.$fichier.'</a>';
}
// La fonction inverse
// http://doc.spip.org/@microformat2enclosure
function microformat2enclosure($tags) {
	$enclosures = array();
	foreach (extraire_balises($tags, 'a') as $e)
	if (extraire_attribut($e, 'rel') == 'enclosure') {
		$url = filtrer_entites(extraire_attribut($e, 'href'));
		$type = extraire_attribut($e, 'type');
		if (!$length = intval(extraire_attribut($e, 'title')))
			$length = intval(extraire_attribut($e, 'length')); # vieux data
		$fichier = basename($url);
		$enclosures[] = '<enclosure'
			. ($url? ' url="'.spip_htmlspecialchars($url).'"' : '')
			. ($type? ' type="'.spip_htmlspecialchars($type).'"' : '')
			. ($length? ' length="'.$length.'"' : '')
			. ' />';
	}
	return join("\n", $enclosures);
}
// Creer les elements ATOM <dc:subject> a partir des tags
// http://doc.spip.org/@tags2dcsubject
function tags2dcsubject($tags) {
	$subjects = '';
	foreach (extraire_balises($tags, 'a') as $e) {
		if (extraire_attribut($e, rel) == 'tag') {
			$subjects .= '<dc:subject>'
				. texte_backend(textebrut($e))
				. '</dc:subject>'."\n";
		}
	}
	return $subjects;
}

// retourne la premiere balise du type demande
// ex: [(#DESCRIPTIF|extraire_balise{img})]
// Si on a passe un tableau de textes, renvoyer un tableau de resultats
// http://doc.spip.org/@extraire_balise
function extraire_balise($texte, $tag='a') {
	if (is_array($texte)) {
		array_walk($texte,
			create_function('&$a,$key,$t', '$a = extraire_balise($a,$t);'),
			$tag);
		return $texte;
	}

	if (preg_match(
	",<$tag\b[^>]*(/>|>.*</$tag\b[^>]*>|>),UimsS",
	$texte, $regs))
		return $regs[0];
}

// extraire toutes les balises du type demande, sous forme de tableau
// Si on a passe un tableau de textes, renvoyer un tableau de resultats
// http://doc.spip.org/@extraire_balises
function extraire_balises($texte, $tag='a') {
	if (is_array($texte)) {
		array_walk($texte,
			create_function('&$a,$key,$t', '$a = extraire_balises($a,$t);'),
			$tag);
		return $texte;
	}

	if (preg_match_all(
	",<${tag}\b[^>]*(/>|>.*</${tag}\b[^>]*>|>),UimsS",
	$texte, $regs, PREG_PATTERN_ORDER))
		return $regs[0];
	else
		return array();
}

// comme in_array mais renvoie son 3e arg si le 2er arg n'est pas un tableau
// prend ' ' comme representant de vrai et '' de faux

// http://doc.spip.org/@in_any
function in_any($val, $vals, $def='') {
	if (!is_array($vals) AND $v=unserialize($vals)) $vals = $v;
  return (!is_array($vals) ? $def : (in_array($val, $vals) ? ' ' : ''));
}

// valeur_numerique("3*2") => 6
// n'accepte que les *, + et - (a ameliorer si on l'utilise vraiment)
// http://doc.spip.org/@valeur_numerique
function valeur_numerique($expr) {
	$a = 0;
	if (preg_match(',^[0-9]+(\s*[+*-]\s*[0-9]+)*$,S', trim($expr)))
		eval("\$a = $expr;");
	return intval($a);
}

// http://doc.spip.org/@regledetrois
function regledetrois($a,$b,$c)
{
  return round($a*$b/$c);
}

// Fournit la suite de Input-Hidden correspondant aux parametres de
// l'URL donnee en argument, compatible avec les types_urls depuis [14447].
// cf. tests/filtres/form_hidden.html
// http://doc.spip.org/@form_hidden
function form_hidden($action) {

	$contexte = array();
	include_spip('inc/urls');
	if ($p = urls_decoder_url($action, '')
		AND reset($p)) {
		$fond = array_shift($p);
		if ($fond!='404'){
			$contexte = array_shift($p);
			$contexte['page'] = $fond;
			$action = preg_replace('/([?]'.preg_quote($fond).'[^&=]*[0-9]+)(&|$)/', '?&', $action);
		}
	}
	// defaire ce qu'a injecte urls_decoder_url : a revoir en modifiant la signature de urls_decoder_url
	if (defined('_DEFINIR_CONTEXTE_TYPE') AND _DEFINIR_CONTEXTE_TYPE)
		unset($contexte['type']);
	if (defined('_DEFINIR_CONTEXTE_TYPE_PAGE') AND _DEFINIR_CONTEXTE_TYPE_PAGE)
		unset($contexte['type-page']);

	// on va remplir un tableau de valeurs en prenant bien soin de ne pas
	// ecraser les elements de la forme mots[]=1&mots[]=2
	$values = array();

	// d'abord avec celles de l'url
	if (false !== ($p = strpos($action, '?'))) {
		foreach(preg_split('/&(amp;)?/S',substr($action,$p+1)) as $c){
			list($var,$val) = explode('=', $c, 2);
			if ($var) {
				$val =  rawurldecode($val);
				$var =  rawurldecode($var); // decoder les [] eventuels
				if (preg_match(',\[\]$,S', $var))
					$values[] = array($var, $val);
				else if (!isset($values[$var]))
					$values[$var] = array($var, $val);
			}
		}
	}

	// ensuite avec celles du contexte, sans doublonner !
	foreach($contexte as $var=>$val)
		if (preg_match(',\[\]$,S', $var))
			$values[] = array($var, $val);
		else if (!isset($values[$var]))
			$values[$var] = array($var, $val);

	// puis on rassemble le tout
	$hidden = array();
	foreach($values as $value) {
		list($var,$val) = $value;
		$hidden[] = '<input name="'
			. entites_html($var)
			.'"'
			. (is_null($val)
				? ''
				: ' value="'.entites_html($val).'"'
				)
			. ' type="hidden"'."\n/>";
	}
	return join("", $hidden);
}

// http://doc.spip.org/@filtre_bornes_pagination_dist
function filtre_bornes_pagination_dist($courante, $nombre, $max = 10) {
	if($max<=0 OR $max>=$nombre)
		return array(1, $nombre);

	$premiere = max(1, $courante-floor(($max-1)/2));
	$derniere = min($nombre, $premiere+$max-2);
	$premiere = $derniere == $nombre ? $derniere-$max+1 : $premiere;
	return array($premiere, $derniere);
}


// Ces trois fonctions permettent de simuler les filtres |reset et |end
// pour extraire la premiere ou la derniere valeur d'un tableau ; utile
// pour la pagination (mais peut-etre a refaire plus simplement)
// http://doc.spip.org/@filtre_valeur_tableau
function filtre_valeur_tableau($array, $index) {
	if (!is_array($array)
	OR !isset($array[$index]))
		return null;
	return $array[$index];
}
// http://doc.spip.org/@filtre_reset
function filtre_reset($array) {
	return !is_array($array) ? null : reset($array);
}
// http://doc.spip.org/@filtre_end
function filtre_end($array) {
	return !is_array($array) ? null : end($array);
}

// http://doc.spip.org/@filtre_push
function filtre_push($array, $val) {
	if($array == '' OR !array_push($array, $val)) return '';
	return $array;
}

// http://doc.spip.org/@filtre_find
function filtre_find($array, $val) {
	return (is_array($array) AND in_array($val, $array));
}


//
// fonction standard de calcul de la balise #PAGINATION
// on peut la surcharger en definissant filtre_pagination dans mes_fonctions
//

// http://doc.spip.org/@filtre_pagination_dist
function filtre_pagination_dist($total, $nom, $position, $pas, $liste = true, $modele='', $connect='', $env=array()) {
	static $ancres = array();
	if ($pas<1) return '';
	$ancre = 'pagination'.$nom; // #pagination_articles
	$debut = 'debut'.$nom; // 'debut_articles'

	// n'afficher l'ancre qu'une fois
	if (!isset($ancres[$ancre]))
		$bloc_ancre = $ancres[$ancre] = "<a name='".$ancre."' id='".$ancre."'></a>";
	else $bloc_ancre = '';
	// liste = false : on ne veut que l'ancre
	if (!$liste)
		return $ancres[$ancre];

	$pagination = array(
		'debut' => $debut,
		'url' => parametre_url(self(),'fragment',''), // nettoyer l'id ahah eventuel
		'total' => $total,
		'position' => intval($position),
		'pas' => $pas,
		'nombre_pages' => floor(($total-1)/$pas)+1,
		'page_courante' => floor(intval($position)/$pas)+1,
		'ancre' => $ancre,
		'bloc_ancre' => $bloc_ancre
	);
	if (is_array($env))
		$pagination = array_merge($env,$pagination);

	// Pas de pagination
	if ($pagination['nombre_pages']<=1)
		return '';

	if ($modele) $modele = '_'.$modele;

	return recuperer_fond("modeles/pagination$modele", $pagination, array('trim'=>true), $connect);
}

// passer les url relatives a la css d'origine en url absolues
// http://doc.spip.org/@urls_absolues_css
function urls_absolues_css($contenu, $source) {
	$path = suivre_lien(url_absolue($source),'./');

	return preg_replace_callback(
		",url\s*\(\s*['\"]?([^'\"/][^:]*)['\"]?\s*\),Uims",
		create_function('$x',
			'return "url(\"".suivre_lien("'.$path.'",$x[1])."\")";'
		), $contenu);
}

// recuperere le chemin d'une css existante et :
// 1. regarde si une css inversee droite-gauche existe dans le meme repertoire
// 2. sinon la cree (ou la recree) dans _DIR_VAR/cache_css/
// SI on lui donne a manger une feuille nommee _rtl.css il va faire l'inverse
// http://doc.spip.org/@direction_css
function direction_css ($css, $voulue='') {
	if (!preg_match(',(_rtl)?\.css$,i', $css, $r)) return $css;

	// si on a precise le sens voulu en argument, le prendre en compte
	if ($voulue = strtolower($voulue)) {
		if ($voulue != 'rtl' AND $voulue != 'ltr')
			$voulue = lang_dir($voulue);
	}
	else
		$voulue =  lang_dir();

	$r = count($r) > 1;
	$right = $r ? 'left' : 'right'; // 'right' de la css lue en entree
	$dir = $r ? 'rtl' : 'ltr';
	$ndir = $r ? 'ltr' : 'rtl';

	if ($voulue == $dir)
		return $css;

	if (
		// url absolue
		preg_match(",^http:,i",$css)
		// ou qui contient un ?
		OR (($p=strpos($css,'?'))!==FALSE)) {
		$distant = true;
		$cssf = parse_url($css);
		$cssf = $cssf['path'].($cssf['query']?"?".$cssf['query']:"");
		$cssf = preg_replace(',[?:&=],', "_", $cssf);
	}
	else {
		$distant = false;
		$cssf = $css;
		// 1. regarder d'abord si un fichier avec la bonne direction n'est pas aussi
		//propose (rien a faire dans ce cas)
		$f = preg_replace(',(_rtl)?\.css$,i', '_'.$ndir.'.css', $css);
		if (@file_exists($f))
			return $f;
	}

	// 2.
	$dir_var = sous_repertoire (_DIR_VAR, 'cache-css');
	$f = $dir_var
		. preg_replace(',.*/(.*?)(_rtl)?\.css,', '\1', $cssf)
		. '.' . substr(md5($cssf), 0,4) . '_' . $ndir . '.css';

	// la css peut etre distante (url absolue !)
	if ($distant){
		include_spip('inc/distant');
		$contenu = recuperer_page($css);
		if (!$contenu) return $css;
	}
	else {
		if ((@filemtime($f) > @filemtime($css))
			AND (_VAR_MODE != 'recalcul'))
			return $f;
		if (!lire_fichier($css, $contenu))
			return $css;
	}

	$contenu = str_replace(
		array('right', 'left', '@@@@L E F T@@@@'),
		array('@@@@L E F T@@@@', 'right', 'left'),
		$contenu);
	
	// reperer les @import auxquels il faut propager le direction_css
	preg_match_all(",\@import\s*url\s*\(\s*['\"]?([^'\"/][^:]*)['\"]?\s*\),Uims",$contenu,$regs);
	$src = array();$src_direction_css = array();$src_faux_abs=array();
	$d = dirname($css);
	foreach($regs[1] as $k=>$import_css){
		$css_direction = direction_css("$d/$import_css",$voulue);
		// si la css_direction est dans le meme path que la css d'origine, on tronque le path, elle sera passee en absolue
		if (substr($css_direction,0,strlen($d)+1)=="$d/") $css_direction = substr($css_direction,strlen($d)+1);
		// si la css_direction commence par $dir_var on la fait passer pour une absolue
		elseif (substr($css_direction,0,strlen($dir_var))==$dir_var) {
			$css_direction = substr($css_direction,strlen($dir_var));
			$src_faux_abs["/@@@@@@/".$css_direction] = $css_direction;
			$css_direction = "/@@@@@@/".$css_direction;
		}
		$src[] = $regs[0][$k];
		$src_direction_css[] = str_replace($import_css,$css_direction,$regs[0][$k]);
	}
	$contenu = str_replace($src,$src_direction_css,$contenu);

	$contenu = urls_absolues_css($contenu, $css);

	// virer les fausses url absolues que l'on a mis dans les import
	if (count($src_faux_abs))
		$contenu = str_replace(array_keys($src_faux_abs),$src_faux_abs,$contenu);

	if (!ecrire_fichier($f, $contenu))
		return $css;

	return $f;
}

// recuperere le chemin d'une css existante et :
// cree (ou recree) dans _DIR_VAR/cache_css/ une css dont les url relatives sont passees en url absolues
// http://doc.spip.org/@url_absolue_css
function url_absolue_css ($css) {
	if (!preg_match(',\.css$,i', $css, $r)) return $css;

	$url_absolue_css = url_absolue($css);

	$f = basename($css,'.css');
	$f = sous_repertoire (_DIR_VAR, 'cache-css') 
		. preg_replace(",(.*?)(_rtl|_ltr)?$,","\\1-urlabs-" . substr(md5("$css-urlabs"), 0,4) . "\\2",$f) 
		. '.css';

	if ((@filemtime($f) > @filemtime($css))
	AND (_VAR_MODE != 'recalcul'))
		return $f;

	if ($url_absolue_css==$css){
		if (strncmp($GLOBALS['meta']['adresse_site'],$css,$l=strlen($GLOBALS['meta']['adresse_site']))!=0
		 OR !lire_fichier(_DIR_RACINE . substr($css,$l), $contenu)){
		 		include_spip('inc/distant');
		 		if (!$contenu = recuperer_page($css))
					return $css;
		}
	}
	elseif (!lire_fichier($css, $contenu))
		return $css;

	// passer les url relatives a la css d'origine en url absolues
	$contenu = urls_absolues_css($contenu, $css);

	// ecrire la css
	if (!ecrire_fichier($f, $contenu))
		return $css;

	return $f;
}



/**
 * Le filtre table_valeur
 * permet de recuperer la valeur d'une cle donnee
 * dans un tableau (ou un objet).
 * 
 * @param mixed $table
 * 		Tableau ou objet
 * 		(ou chaine serialisee de tableau, ce qui permet d'enchainer le filtre)
 * 		
 * @param string $cle
 * 		Cle du tableau (ou parametre public de l'objet)
 * 		Cette cle peut contenir des caracteres / pour selectionner
 * 		des sous elements dans le tableau, tel que "sous/element/ici"
 * 		pour obtenir la valeur de $tableau['sous']['element']['ici']
 *
 * @param mixed $defaut
 * 		Valeur par defaut retournee si la cle demandee n'existe pas
 * 
 * @return mixed Valeur trouvee ou valeur par defaut.
**/
function table_valeur($table, $cle, $defaut='') {
	foreach (explode('/', $cle) as $k) {

		$table = is_string($table) ? @unserialize($table) : $table;

		if (is_object($table)) {
			$table =  (($k !== "") and isset($table->$k)) ? $table->$k : $defaut;
		} elseif (is_array($table)) {
			$table = isset($table[$k]) ? $table[$k] : $defaut;
		} else {
			$table = $defaut;
		}
	}
	return $table;
}

// filtre match pour faire des tests avec expression reguliere
// [(#TEXTE|match{^ceci$,Uims})]
// retourne le fragment de chaine qui "matche"
// il est possible de passer en 3eme argument optionnel le numero de parenthese capturante
// accepte egalement la syntaxe #TRUC|match{truc(...)$,1} ou le modificateur n'est pas passe en second argument
// http://doc.spip.org/@match
function match($texte, $expression, $modif="UimsS",$capte=0) {
	if (intval($modif) AND $capte==0){
		$capte = $modif;
		$modif = "UimsS";
	}
	$expression=str_replace("\/","/",$expression);
	$expression=str_replace("/","\/",$expression);

	if (preg_match('/' . $expression . '/' . $modif,$texte, $r)) {
		if (isset($r[$capte]))
			return $r[$capte];
		else
			return true;
	}
	return false;
}

// filtre replace pour faire des operations avec expression reguliere
// [(#TEXTE|replace{^ceci$,cela,UimsS})]
// http://doc.spip.org/@replace
function replace($texte, $expression, $replace='', $modif="UimsS") {
	$expression=str_replace("\/","/", $expression);
	$expression=str_replace("/","\/",$expression);
	return preg_replace('/' . $expression . '/' . $modif, $replace, $texte);
}


// cherche les documents numerotes dans un texte traite par propre()
// et affecte les doublons['documents']
// http://doc.spip.org/@traiter_doublons_documents
// http://doc.spip.org/@traiter_doublons_documents
function traiter_doublons_documents(&$doublons, $letexte) {

	// Verifier dans le texte & les notes (pas beau, helas)
	$t = $letexte.$GLOBALS['les_notes'];

	if (strstr($t, 'spip_document_') // evite le preg_match_all si inutile
	AND preg_match_all(
	',<[^>]+\sclass=["\']spip_document_([0-9]+)[\s"\'],imsS',
	$t, $matches, PREG_PATTERN_ORDER))
		$doublons['documents'] .= "," . join(',', $matches[1]);

	return $letexte;
}

// filtre vide qui ne renvoie rien
// http://doc.spip.org/@vide
function vide($texte){
	return "";
}

//
// Filtres pour le modele/emb (embed document)
//

// A partir d'un #ENV, retourne des <param ...>
// http://doc.spip.org/@env_to_params
function env_to_params ($texte, $ignore_params=array()) {
	$ignore_params = array_merge (
		array('id', 'lang', 'id_document', 'date', 'date_redac', 'align', 'fond', '', 'recurs', 'emb', 'dir_racine'),
	$ignore_params);
	$tableau = unserialize($texte);
	$texte = "";
	foreach ($tableau as $i => $j)
		if (is_string($j) AND !in_array($i,$ignore_params))
			$texte .= "<param name='".$i."'\n\tvalue='".$j."' />";
	return $texte;
}
// A partir d'un #ENV, retourne des attributs
// http://doc.spip.org/@env_to_attributs
function env_to_attributs ($texte, $ignore_params=array()) {
	$ignore_params = array_merge (
		array('id', 'lang', 'id_document', 'date', 'date_redac', 'align', 'fond', '', 'recurs', 'emb', 'dir_racine'),
	$ignore_params);
	$tableau = unserialize($texte);
	$texte = "";
	foreach ($tableau as $i => $j)
		if (is_string($j) AND !in_array($i,$ignore_params))
			$texte .= $i."='".$j."' ";
	return $texte;
}

// Concatener des chaines
// #TEXTE|concat{texte1,texte2,...}
// http://doc.spip.org/@concat
function concat(){
	$args = func_get_args();
	return join('', $args);
}


// http://doc.spip.org/@charge_scripts
// http://doc.spip.org/@charge_scripts
function charge_scripts($files, $script = true) {
	$flux = "";
	foreach(is_array($files)?$files:explode("|",$files) as $file) {
		if (!is_string($file)) continue;
		if ($script)
			$file = preg_match(",^\w+$,",$file) ? "javascript/$file.js" : '';
		if ($file) $path = find_in_path($file);
		if ($path) $flux .= spip_file_get_contents($path);
	}
	return $flux;
}



/**
 * produit une balise img avec un champ alt d'office si vide
 * attention le htmlentities et la traduction doivent etre appliques avant.
 *
 * http://doc.spip.org/@http_img_pack
 *
 * @param $img
 * @param $alt
 * @param string $atts
 * @param string $title
 * @param array $options
 *   chemin_image : utiliser chemin_image sur $img fourni, ou non (oui par dafaut)
 *   utiliser_suffixe_size : utiliser ou non le suffixe de taille dans le nom de fichier de l'image
 *   sous forme -xx.png (pour les icones essentiellement) (oui par defaut)
 * @return string
 */
function http_img_pack($img, $alt, $atts='', $title='', $options = array()) {
	if (!isset($options['chemin_image']) OR $options['chemin_image']==true)
		$img = chemin_image($img);
	if (stripos($atts, 'width')===false){
		// utiliser directement l'info de taille presente dans le nom
		if ((!isset($options['utiliser_suffixe_size']) OR $options['utiliser_suffixe_size']==true)
		    AND preg_match(',-([0-9]+)[.](png|gif)$,',$img,$regs)){
			$largeur = $hauteur = intval($regs[1]);
		}
		else{
			$taille = taille_image($img);
			list($hauteur,$largeur) = $taille;
			if (!$hauteur OR !$largeur)
				return "";
		}
		$atts.=" width='".$largeur."' height='".$hauteur."'";
	}
	return  "<img src='$img' alt='" . attribut_html($alt ? $alt : $title) . "'"
	  . ($title ? ' title="'.attribut_html($title).'"' : '')
	  . " ".ltrim($atts)
	  . " />";
}

/**
 * generer une directive style='background:url()' a partir d'un fichier image
 * 
 * http://doc.spip.org/@http_style_background
 *
 * @param string $img
 * @param string $att
 * @return string
 */
function http_style_background($img, $att=''){
  return " style='background".($att?"":"-image").": url(\"".chemin_image($img)."\")" . ($att ? (' ' . $att) : '') . ";'";
}

/**
 * une fonction pour generer une balise img a partir d'un nom de fichier
 *
 * @param string $img
 * @param string $alt
 * @param string $class
 * @return string
 */
function filtre_balise_img_dist($img,$alt="",$class=""){
	return http_img_pack($img, $alt, $class?" class='".attribut_html($class)."'":'', '', array('chemin_image'=>false,'utiliser_suffixe_size'=>false));
}


//[(#ENV*|unserialize|foreach)]
// http://doc.spip.org/@filtre_foreach_dist
function filtre_foreach_dist($balise_deserializee, $modele = 'foreach') {
	$texte = '';
	if(is_array($balise_deserializee))
		foreach($balise_deserializee as $k => $v) {
			$res = recuperer_fond('modeles/'.$modele,
				array_merge(array('cle' => $k), (is_array($v) ? $v : array('valeur' => $v)))
			);
			$texte .= $res;
		}
	return $texte;
}

// renvoie la liste des plugins actifs du site
// si le premier parametre est un prefix de cette liste, renvoie vrai, faux sinon
// la valeur du second parametre si celui-ci renvoie a une information connue
// cf liste_plugin_actifs() pour connaitre les informations affichables
// appelee par la balise #PLUGIN
// http://doc.spip.org/@filtre_info_plugin_dist
function filtre_info_plugin_dist($plugin, $type_info) {
	include_spip('inc/plugin');
	$plugin = strtoupper($plugin);
	$plugins_actifs = liste_plugin_actifs();

	if (!$plugin)
		return serialize(array_keys($plugins_actifs));
	elseif (empty($plugins_actifs[$plugin]))
		return '';
	elseif ($type_info == 'est_actif')
		return $plugins_actifs[$plugin] ? 1 : 0;
	elseif (isset($plugins_actifs[$plugin][$type_info]))
		return $plugins_actifs[$plugin][$type_info];
	else {
		$get_infos = charger_fonction('get_infos','plugins');
		// On prend en compte les extensions
		if (!is_dir($plugins_actifs[$plugin]['dir_type']))
			$dir_plugins = constant($plugins_actifs[$plugin]['dir_type']);
		else
			$dir_plugins = $plugins_actifs[$plugin]['dir_type'];
		if (!$infos = $get_infos($plugins_actifs[$plugin]['dir'], false, $dir_plugins))
			return '';
		if ($type_info == 'tout')
			return $infos;
		else
			return strval($infos[$type_info]);
	}
}


// http://doc.spip.org/@puce_changement_statut
function puce_changement_statut($id_objet, $statut, $id_rubrique, $type, $ajax=false){
	$puce_statut = charger_fonction('puce_statut','inc');
	return $puce_statut($id_objet, $statut, $id_rubrique, $type, $ajax);
}

/**
 * [(#STATUT|puce_statut{article})] affiche une puce passive
 * [(#STATUT|puce_statut{article,#ID_ARTICLE,#ID_RUBRIQUE})] affiche une puce avec changement rapide
 *
 * utilisable sur tout objet qui a declare
 * @param string $statut
 * @param string $objet
 * @param int $id_objet
 * @param int $id_parent
 * @return string
 */
function filtre_puce_statut_dist($statut,$objet,$id_objet=0,$id_parent=0){
	static $puce_statut = null;
	if (!$puce_statut)
		$puce_statut = charger_fonction('puce_statut','inc');
	return $puce_statut($id_objet, $statut, $id_parent, $objet, false, objet_info($objet,'editable')?_ACTIVER_PUCE_RAPIDE:false);
}


/**
 * Encoder un contexte pour l'ajax, le signer avec une cle, le crypter
 * avec le secret du site, le gziper si possible...
 * l'entree peut etre serialisee (le #ENV** des fonds ajax et ajax_stat)
 *
 * http://doc.spip.org/@encoder_contexte_ajax
 *
 * @param string|array $c
 *   contexte, peut etre un tableau serialize
 * @param string $form
 *   nom du formulaire eventuel
 * @param string $emboite
 *   contenu a emboiter dans le conteneur ajax
 * @param string $ajaxid
 *   ajaxid pour cibler le bloc et forcer sa mise a jour
 * @return string
 */
function encoder_contexte_ajax($c,$form='', $emboite=NULL, $ajaxid='') {
	if (is_string($c)
	AND !is_null(@unserialize($c))) {
		$c = unserialize($c);
	}

	// supprimer les parametres debut_x
	// pour que la pagination ajax ne soit pas plantee
	// si on charge la page &debut_x=1 : car alors en cliquant sur l'item 0,
	// le debut_x=0 n'existe pas, et on resterait sur 1
	foreach ($c as $k => $v) {
		if (strpos($k,'debut_') === 0) {
			unset($c[$k]);
		}
	}
	
	if (!function_exists('calculer_cle_action'))
		include_spip("inc/securiser_action");
	$cle = calculer_cle_action($form.(is_array($c)?serialize($c):$c));
	$c = serialize(array($c,$cle));

	// on ne stocke pas les contextes dans des fichiers caches
	// par defaut, sauf si cette configuration a ete forcee
	// OU que la longueur de l''argument generee est plus long
	// que ce que telere Suhosin.
	$cache_contextes_ajax = (defined('_CACHE_CONTEXTES_AJAX') AND _CACHE_CONTEXTES_AJAX);
	if (!$cache_contextes_ajax) {
		$env = $c;
		if (function_exists('gzdeflate') && function_exists('gzinflate')) {
			$env = gzdeflate($env);
			// http://core.spip.org/issues/2667 | https://bugs.php.net/bug.php?id=61287
			if (substr(phpversion(),0,5) == '5.4.0' AND !@gzinflate($env)) {
				$cache_contextes_ajax = true;
				spip_log("Contextes AJAX forces en fichiers ! Erreur PHP 5.4.0", _LOG_AVERTISSEMENT);
			}
		}
		$env = _xor($env);
		$env = base64_encode($env);
		// tester Suhosin et la valeur maximale des variables en GET...
		if ($max_len = @ini_get('suhosin.get.max_value_length')
		and $max_len < ($len = strlen($env))) {
			$cache_contextes_ajax = true;
			spip_log("Contextes AJAX forces en fichiers !"
				. " Cela arrive lorsque la valeur du contexte"
				. " depasse la longueur maximale autorisee par Suhosin"
				. " ($max_len) dans 'suhosin.get.max_value_length'. Ici : $len."
				. " Vous devriez modifier les parametres de Suhosin"
				. " pour accepter au moins 1024 caracteres.", _LOG_AVERTISSEMENT);
		}
	}
	
	if ($cache_contextes_ajax) {
		$dir = sous_repertoire(_DIR_CACHE, 'contextes');
		// stocker les contextes sur disque et ne passer qu'un hash dans l'url
		$md5 = md5($c);
		ecrire_fichier("$dir/c$md5",$c);
		$env = $md5;
	} 
	
	if ($emboite === NULL) return $env;
	if (!trim($emboite)) return "";
	// toujours encoder l'url source dans le bloc ajax
	$r = self();
	$r = ' data-origin="'.$r.'"';
	$class = 'ajaxbloc';
	if ($ajaxid AND is_string($ajaxid)){
		$class .= ' ajax-id-'.$ajaxid;
	}
	$compl = "aria-live='polite' aria-atomic='true' ";
	return "<div class='$class' ".$compl."data-ajax-env='$env'$r>\n$emboite</div><!--ajaxbloc-->\n";
}

// la procedure inverse de encoder_contexte_ajax()
// http://doc.spip.org/@decoder_contexte_ajax
function decoder_contexte_ajax($c,$form='') {
	if (!function_exists('calculer_cle_action'))
		include_spip("inc/securiser_action");
	if (( (defined('_CACHE_CONTEXTES_AJAX') AND _CACHE_CONTEXTES_AJAX) OR strlen($c)==32)
		AND $dir = sous_repertoire(_DIR_CACHE, 'contextes')
		AND lire_fichier("$dir/c$c",$contexte)) {
			$c = $contexte;
	} else {
		$c = @base64_decode($c);
		$c = _xor($c);
		if (function_exists('gzdeflate') && function_exists('gzinflate'))
			$c = @gzinflate($c);
	}
	list($env, $cle) = @unserialize($c);

	if ($cle == calculer_cle_action($form.(is_array($env)?serialize($env):$env)))
		return $env;
	return false;
}

// encrypter/decrypter un message
// http://www.php.net/manual/fr/language.operators.bitwise.php#81358
// http://doc.spip.org/@_xor
function _xor($message, $key=null){
	if (is_null($key)) {
		if (!function_exists('calculer_cle_action'))
			include_spip("inc/securiser_action");
		$key = pack("H*", calculer_cle_action('_xor'));
	}

	$keylen = strlen($key);
	$messagelen = strlen($message);
	for($i=0; $i<$messagelen; $i++)
		$message[$i] = ~($message[$i]^$key[$i%$keylen]);

	return $message;
}

// Les vrai fonctions sont dans le plugin forum, mais on evite ici une erreur du compilateur
// en absence du plugin
function url_reponse_forum($texte){return $texte;}
function url_rss_forum($texte){return $texte;}


/**
 * une fonction pour generer des menus avec liens
 * ou un <strong class='on'> non clicable lorsque l'item est selectionne
 *
 * @param string $url
 * @param string $libelle
 *   le texte du lien
 * @param bool $on
 *   etat expose (genere un strong) ou non (genere un lien)
 * @param string $class
 * @param string $title
 * @param string $rel
 * @param string $evt
 *   complement a la balise a pour gerer un evenement javascript, de la forme " onclick='...'"
 * @return string
 */
function lien_ou_expose($url,$libelle=NULL,$on=false,$class="",$title="",$rel="", $evt=''){
	if ($on) {
		$bal = "strong";
		$att = "class='on'";
	} else {
		$bal = 'a';
		$att = "href='$url'"
	  	.($title?" title='".attribut_html($title)."'":'')
	  	.($class?" class='".attribut_html($class)."'":'')
	  	.($rel?" rel='".attribut_html($rel)."'":'')
		.$evt;
	}
	if ($libelle === NULL)
		$libelle = $url;
	return "<$bal $att>$libelle</$bal>";
}


/**
 * Afficher un message "un truc"/"N trucs"
 * Les items sont à indiquer comme pour la fonction _T() sous la forme :
 * "module:chaine"
 *
 * @param int $nb : le nombre
 * @param string $chaine_un : l'item de langue si $nb vaut un 
 * @param string $chaine_plusieurs : l'item de lanque si $nb > 1 
 * @param string $var : La variable à remplacer par $nb dans l'item de langue (facultatif, défaut "nb")
 * @param array $vars : Les autres variables nécessaires aux chaines de langues (facultatif)
 * @return string : la chaine de langue finale en utilisant la fonction _T()
 */
function singulier_ou_pluriel($nb,$chaine_un,$chaine_plusieurs,$var='nb',$vars=array()){
	if (!$nb=intval($nb)) return "";
	if (!is_array($vars)) return "";
	$vars[$var] = $nb;
	if ($nb>1) return _T($chaine_plusieurs, $vars);
	else return _T($chaine_un,$vars);
}


/**
 * Fonction de base pour une icone dans un squelette
 * structure html : <span><a><img><b>texte</b></span>
 *
 * @param string $type
 *  'lien' ou 'bouton'
 * @param string $lien
 *  url
 * @param string $texte
 *  texte du lien / alt de l'image
 * @param string $fond
 *  objet avec ou sans son extension et sa taille (article, article-24, article-24.png)
 * @param string $fonction
 *  new/del/edit
 * @param string $class
 *  classe supplementaire (horizontale, verticale, ajax ...)
 * @param string $javascript
 *  "onclick='...'" par exemple
 * @return string 
 */
function prepare_icone_base($type, $lien, $texte, $fond, $fonction="", $class="",$javascript=""){
	if (in_array($fonction,array("del","supprimer.gif")))
		$class .= ' danger';
	elseif ($fonction == "rien.gif")
		$fonction = "";
	elseif ($fonction == "delsafe")
		$fonction = "del";

	// remappage des icone : article-24.png+new => article-new-24.png
	if ($icone_renommer = charger_fonction('icone_renommer','inc',true))
		list($fond,$fonction) = $icone_renommer($fond,$fonction);

	// ajouter le type d'objet dans la class de l'icone
	$class .= " " . substr(basename($fond),0,-4);

	$alt = attribut_html($texte);
	$title = " title=\"$alt\""; // est-ce pertinent de doubler le alt par un title ?

	$ajax = "";
	if (strpos($class,"ajax")!==false) {
			$ajax="ajax";
		if (strpos($class,"preload")!==false)
			$ajax.=" preload";
		if (strpos($class,"nocache")!==false)
			$ajax.=" nocache";
		$ajax=" class='$ajax'";
	}

	$size = 24;
	if (preg_match("/-([0-9]{1,3})[.](gif|png)$/i",$fond,$match))
		$size = $match[1];

	if ($fonction){
		// 2 images pour composer l'icone : le fond (article) en background,
		// la fonction (new) en image
		$icone = http_img_pack($fonction, $alt, "width='$size' height='$size'\n" .
					http_style_background($fond));
	}
	else {
		$icone = http_img_pack($fond, $alt, "width='$size' height='$size'");
	}

	if ($type=='lien')
		return "<span class='icone s$size $class'>"
		. "<a href='$lien'$title$ajax$javascript>"
		. $icone
		. "<b>$texte</b>"
		. "</a></span>\n";

	else
		return bouton_action("$icone<b>$texte</b>",$lien,"icone s$size $class",$javascript,$alt);
}

function icone_base($lien, $texte, $fond, $fonction="", $class="",$javascript=""){
	return prepare_icone_base('lien', $lien, $texte, $fond, $fonction, $class, $javascript);
}
function filtre_icone_verticale_dist($lien, $texte, $fond, $fonction="", $class="",$javascript=""){
	return icone_base($lien,$texte,$fond,$fonction,"verticale $class",$javascript);
}
function filtre_icone_horizontale_dist($lien, $texte, $fond, $fonction="", $class="",$javascript=""){
	return icone_base($lien,$texte,$fond,$fonction,"horizontale $class",$javascript);
}

function filtre_bouton_action_horizontal_dist($lien, $texte, $fond, $fonction="", $class="",$confirm=""){
	return prepare_icone_base('bouton', $lien, $texte, $fond, $fonction, "horizontale $class", $confirm);
}
/*
 * Filtre icone pour compatibilite
 * mappe sur icone_base
 */
function filtre_icone_dist($lien, $texte, $fond, $align="", $fonction="", $class="",$javascript=""){
	return icone_base($lien,$texte,$fond,$fonction,"verticale $align $class",$javascript);
}


/**
 * filtre explode pour les squelettes permettant d'ecrire
 * #GET{truc}|explode{-}
 *
 * @param strong $a
 * @param string $b
 * @return array
 */
function filtre_explode_dist($a,$b){return explode($b,$a);}

/**
 * filtre implode pour les squelettes permettant d'ecrire
 * #GET{truc}|implode{-}
 *
 * @param array $a
 * @param string $b
 * @return string
 */
function filtre_implode_dist($a,$b){return is_array($a)?implode($b,$a):$a;}

/**
 * Produire les styles prives qui associent item de menu avec icone en background
 * @return string
 */
function bando_images_background(){
	include_spip('inc/bandeau');
	// recuperer tous les boutons et leurs images
	$boutons = definir_barre_boutons(definir_barre_contexte(),true,false);

	$res = "";
	foreach($boutons as $page => $detail){
		if ($detail->icone AND strlen(trim($detail->icone)))
			$res .="\n.navigation_avec_icones #bando1_$page {background-image:url(".$detail->icone.");}";
		$selecteur = (in_array($page,array('outils_rapides','outils_collaboratifs'))?"":".navigation_avec_icones ");
		if (is_array($detail->sousmenu))
			foreach($detail->sousmenu as $souspage=>$sousdetail)
				if ($sousdetail->icone AND strlen(trim($sousdetail->icone)))
					$res .="\n$selecteur.bando2_$souspage {background-image:url(".$sousdetail->icone.");}";
	}
	return $res;
}

/**
 * Generer un bouton_action
 * utilise par #BOUTON_ACTION
 *
 * @param string $libelle
 * @param string $url
 * @param string $class
 * @param string $confirm
 *   message de confirmation oui/non avant l'action
 * @param string $title
 * @param string $callback
 *   callback js a appeler lors de l'evenement action (apres confirmation eventuelle si $confirm est non vide)
 *   et avant execution de l'action. Si la callback renvoie false, elle annule le declenchement de l'action
 * @return string
 */
function bouton_action($libelle, $url, $class="", $confirm="", $title="", $callback=""){
	if ($confirm) {
		$confirm = "confirm(\"" . attribut_html($confirm) . "\")";
	  if ($callback)
		  $callback = "$confirm?($callback):false";
	  else
		  $callback = $confirm;
	}
	$onclick = $callback?" onclick='return ".addcslashes($callback,"'")."'":"";
	$title = $title ? " title='$title'" : "";
	return "<form class='bouton_action_post $class' method='post' action='$url'><div>".form_hidden($url)
		."<button type='submit' class='submit'$title$onclick>$libelle</button></div></form>";
}


/**
 * Proteger les champs passes dans l'url et utiliser dans {tri ...}
 * preserver l'espace pour interpreter ensuite num xxx et multi xxx
 * @param string $t
 * @return string
 */
function tri_protege_champ($t){
	return preg_replace(',[^\s\w.+],','',$t);
}

/**
 * Interpreter les multi xxx et num xxx utilise comme tri
 * pour la clause order
 * 'multi xxx' devient simplement 'multi' qui est calcule dans le select
 * @param string $t
 * @param array $from
 * @return string
 */
function tri_champ_order($t, $from=null){
	if(strncmp($t,'multi ',6)==0){
		return "multi";
	}

	$champ = $t;

	if (strncmp($t,'num ',4)==0)
		$champ = substr($t,4);
	// enlever les autres espaces non evacues par tri_protege_champ
	$champ = preg_replace(',\s,','',$champ);

	if (is_array($from)){
		$trouver_table = charger_fonction('trouver_table','base');
		foreach($from as $idt=>$table_sql){
			if ($desc = $trouver_table($table_sql)
				AND isset($desc['field'][$champ])){
				$champ = "$idt.$champ";
				break;
			}
		}
	}
	if (strncmp($t,'num ',4)==0)
		return "0+$champ";
	else
		return $champ;
}

/**
 * Interpreter les multi xxx et num xxx utilise comme tri
 * pour la clause select
 * 'multi xxx' devient select "...." as multi
 * les autres cas ne produisent qu'une chaine vide '' en select
 * 'hasard' devient 'rand() AS hasard' dans le select
 *
 * @param string $t
 * @return string
 */
function tri_champ_select($t){
	if(strncmp($t,'multi ',6)==0){
		$t = substr($t,6);
		$t = preg_replace(',\s,','',$t);
		$t = sql_multi($t,$GLOBALS['spip_lang']);
		return $t;
	}
	if(trim($t)=='hasard'){
		return 'rand() AS hasard';
	}
	return "''";
}


/**
 * Donner n'importe quelle information sur un objet de maniere generique.
 *
 * La fonction va gerer en interne deux cas particuliers les plus utilises :
 * l'URL et le titre (qui n'est pas forcemment le champ SQL "titre").
 *
 * On peut ensuite personnaliser les autres infos en creant une fonction
 * generer_<nom_info>_entite($id_objet, $type_objet, $ligne).
 * $ligne correspond a la ligne SQL de tous les champs de l'objet, les fonctions
 * de personnalisation n'ont donc pas a refaire de requete.
 *
 * @param int $id_objet
 * @param string $type_objet
 * @param string $info
 * @param string $etoile
 * @return string
 */
function generer_info_entite($id_objet, $type_objet, $info, $etoile=""){
	global $table_des_traitements;
	static $trouver_table=null;
	static $objets;

	// On verifie qu'on a tout ce qu'il faut
	$id_objet = intval($id_objet);
	if (!($id_objet and $type_objet and $info))
		return '';

	// si on a deja note que l'objet n'existe pas, ne pas aller plus loin
	if (isset($objets[$type_objet]) AND $objets[$type_objet]===false)
		return '';

	// Si on demande l'url, on retourne direct la fonction
	if ($info == 'url')
		return generer_url_entite($id_objet, $type_objet);

	// Sinon on va tout chercher dans la table et on garde en memoire
	$demande_titre = ($info == 'titre');

	// On ne fait la requete que si on a pas deja l'objet ou si on demande le titre mais qu'on ne l'a pas encore
	if (!isset($objets[$type_objet][$id_objet])
	  OR
	  ($demande_titre AND !isset($objets[$type_objet][$id_objet]['titre']))
	  ){
		if (!$trouver_table)
			$trouver_table = charger_fonction('trouver_table','base');
		$desc = $trouver_table(table_objet_sql($type_objet));
		if (!$desc)
			return $objets[$type_objet] = false;

		// Si on demande le titre, on le gere en interne
		$champ_titre = "";
		if ($demande_titre){
			// si pas de titre declare mais champ titre, il sera peuple par le select *
			$champ_titre = (!empty($desc['titre'])) ? ', ' . $desc['titre']:'';
		}
		include_spip('base/abstract_sql');
		include_spip('base/connect_sql');
		$objets[$type_objet][$id_objet] = sql_fetsel(
			'*'.$champ_titre,
			$desc['table_sql'],
			id_table_objet($type_objet).' = '.intval($id_objet)
		);
	}

	// Si la fonction generer_TRUC_TYPE existe, on l'utilise pour formater $info_generee
	if ($generer = charger_fonction("generer_${info}_${type_objet}", '', true))
		$info_generee = $generer($id_objet, $objets[$type_objet][$id_objet]);
	// Si la fonction generer_TRUC_entite existe, on l'utilise pour formater $info_generee
	else if ($generer = charger_fonction("generer_${info}_entite", '', true))
		$info_generee = $generer($id_objet, $type_objet, $objets[$type_objet][$id_objet]);
	// Sinon on prend directement le champ SQL tel quel
	else
		$info_generee = (isset($objets[$type_objet][$id_objet][$info])?$objets[$type_objet][$id_objet][$info]:'');

	// On va ensuite chercher les traitements automatiques a faire
	$champ = strtoupper($info);
	$traitement = isset($table_des_traitements[$champ]) ? $table_des_traitements[$champ] : false;
	$table_sql = table_objet_sql($type_objet);

	if (!$etoile
		AND is_array($traitement)
	  AND (isset($traitement[$table_sql]) OR isset($traitement[0]))){
	  	include_spip('inc/texte');
		$traitement = $traitement[isset($traitement[$table_sql]) ? $table_sql : 0];
		$traitement = str_replace('%s', "'".texte_script($info_generee)."'", $traitement);
		// FIXME: $connect et $Pile[0] font souvent partie des traitements.
		// on les definit pour eviter des notices, mais ce fonctionnement est a ameliorer !
		$connect = ""; $Pile = array(0 => array('id_objet'=>$id_objet,'objet'=>$type_objet));
		eval("\$info_generee = $traitement;");
	}

	return $info_generee;
}

/**
 * Wrap un texte avec des balises
 * wrap('mot','<b>') => '<b>mot</b>'
 * @param string $texte
 * @param string $wrap
 * @return string
 */
function wrap($texte,$wrap) {
	$balises = extraire_balises($wrap);
	if (preg_match_all(",<([a-z]\w*)\b[^>]*>,UimsS",$wrap, $regs, PREG_PATTERN_ORDER)) {
		$texte = $wrap . $texte;
		$regs = array_reverse($regs[1]);
		$wrap = "</".implode("></",$regs).">";
		$texte = $texte . $wrap;
	}
	return $texte;
}


/**
 * afficher proprement n'importe quoi
 * On affiche in fine un pseudo-yaml qui premet de lire humainement les tableaux et de s'y reperer
 *
 * Les textes sont retournes avec simplement mise en forme typo
 *
 * le $join sert a separer les items d'un tableau, c'est en general un \n ou <br /> selon si on fait du html ou du texte
 * les tableaux-listes (qui n'ont que des cles numeriques), sont affiches sous forme de liste separee par des virgules :
 * c'est VOULU !
 *
 * @param $u
 * @param string $join
 * @param int $indent
 * @return array|mixed|string
 */
function filtre_print_dist($u, $join="<br />", $indent=0) {
	if (is_string($u)){
		$u = typo($u);
		return $u;
	}

	// caster $u en array si besoin
	if (is_object($u))
		$u = (array) $u;

	if (is_array($u)){
		$out = "";
		// toutes les cles sont numeriques ?
		// et aucun enfant n'est un tableau
		// liste simple separee par des virgules
		$numeric_keys = array_map('is_numeric',array_keys($u));
		$array_values = array_map('is_array',$u);
		$object_values = array_map('is_object',$u);
		if (array_sum($numeric_keys)==count($numeric_keys)
		  AND !array_sum($array_values)
		  AND !array_sum($object_values)){
			return join(", ", array_map('filtre_print_dist', $u));
		}

		// sinon on passe a la ligne et on indente
		$i_str = str_pad("",$indent," ");
		foreach($u as $k => $v){
			$out .= $join . $i_str . "$k: " . filtre_print_dist($v,$join,$indent+2);
		}
		return $out;
	}

	// on sait pas quoi faire...
	return $u;
}


/**
 * Renvoyer l'info d'un objet
 * telles que definies dans declarer_tables_objets_sql
 *
 * @param string $objet
 * @param string $info
 * @return string
 */
function objet_info($objet,$info){
	$table = table_objet_sql($objet);
	$infos = lister_tables_objets_sql($table);
	return (isset($infos[$info])?$infos[$info]:'');
}

/**
 * Filtre pour afficher 'Aucun truc' ou '1 truc' ou 'N trucs'
 * avec la bonne chaine de langue en fonction de l'objet utilise
 * @param  $nb
 * @param  $objet
 * @return mixed|string
 */
function objet_afficher_nb($nb, $objet){
	if (!$nb)
		return _T(objet_info($objet,'info_aucun_objet'));
	else
		return _T(objet_info($objet,$nb==1?'info_1_objet':'info_nb_objets'),array('nb'=>$nb));
}

/**
 * Filtre pour afficher l'img icone d'un objet
 *
 * @param string $objet
 * @param int $taille
 * @return string
 */
function objet_icone($objet,$taille=24){
	$icone = objet_info($objet,'icone_objet')."-".$taille.".png";
	$icone = chemin_image($icone);
	$balise_img = charger_filtre('balise_img');
	return $icone?$balise_img($icone,_T(objet_info($objet,'texte_objet'))):'';
}

/**
 * Fonction de secours pour inserer le head_css de facon conditionnelle
 * 
 * Appelée en filtre sur le squelette qui contient #INSERT_HEAD,
 * elle vérifie l'absence éventuelle de #INSERT_HEAD_CSS et y suplée si besoin
 * pour assurer la compat avec les squelettes qui n'utilisent pas.
 * 
 * @param string $flux Code HTML
 * @return string      Code HTML
 */
function insert_head_css_conditionnel($flux){
	if (strpos($flux,'<!-- insert_head_css -->')===false
		AND $p=strpos($flux,'<!-- insert_head -->')){
		// plutot avant le premier js externe (jquery) pour etre non bloquant
		if ($p1 = stripos($flux,'<script src=') AND $p1<$p)
			$p = $p1;
		$flux = substr_replace($flux,pipeline('insert_head_css','<!-- insert_head_css -->'),$p,0);
	}
	return $flux;
}

/**
 * Produire un fichier statique a partir d'un squelette dynamique
 * Permet ensuite a apache de le servir en statique sans repasser
 * par spip.php a chaque hit sur le fichier
 * si le format (css ou js) est passe dans contexte['format'], on l'utilise
 * sinon on regarde si le fond finit par .css ou .js
 * sinon on utilie "html"
 *
 * @param string $fond
 * @param array $contexte
 * @param array $options
 * @param string $connect
 * @return string
 */
function produire_fond_statique($fond, $contexte=array(), $options = array(), $connect=''){
	if (isset($contexte['format'])){
		$extension = $contexte['format'];
		unset($contexte['format']);
	}
	else {
		$extension = "html";
		if (preg_match(',[.](css|js|json)$,',$fond,$m))
			$extension = $m[1];
	}
	// recuperer le contenu produit par le squelette
	$options['raw'] = true;
	$cache = recuperer_fond($fond,$contexte,$options,$connect);
	
	// calculer le nom de la css
	$dir_var = sous_repertoire (_DIR_VAR, 'cache-'.$extension);
	$nom_safe = preg_replace(",\W,",'_',str_replace('.','_',$fond));
	$filename = $dir_var . $extension."dyn-$nom_safe-".substr(md5($fond.serialize($contexte).$connect),0,8) .".$extension";

	// mettre a jour le fichier si il n'existe pas
	// ou trop ancien
	// le dernier fichier produit est toujours suffixe par .last
	// et recopie sur le fichier cible uniquement si il change
	if (!file_exists($filename)
		OR !file_exists($filename.".last")
		OR (isset($cache['lastmodified']) AND $cache['lastmodified'] AND filemtime($filename.".last")<$cache['lastmodified'])
		OR (defined('_VAR_MODE') AND _VAR_MODE=='recalcul')) {
		$contenu = $cache['texte'];
		// passer les urls en absolu si c'est une css
		if ($extension=="css")
			$contenu = urls_absolues_css($contenu, test_espace_prive()?generer_url_ecrire('accueil'):generer_url_public($fond));
		
		// ne pas insérer de commentaire si c'est du json
		if ($extension!="json") {
			$comment = "/* #PRODUIRE{fond=$fond";
			foreach($contexte as $k=>$v)
				$comment .= ",$k=$v";
			// pas de date dans le commentaire car sinon ca invalide le md5 et force la maj
			// mais on peut mettre un md5 du contenu, ce qui donne un aperu rapide si la feuille a change ou non
			$comment .="}\n   md5:".md5($contenu)." */\n";
		}
		// et ecrire le fichier
		ecrire_fichier($filename.".last",$comment.$contenu);
		// regarder si on recopie
		if (!file_exists($filename)
		  OR md5_file($filename)!==md5_file($filename.".last")){
			@copy($filename.".last",$filename);
			spip_clearstatcache(true,$filename); // eviter que PHP ne reserve le vieux timestamp
		}
	}
	
	return $filename;
}

/**
 * Ajouter un timestamp a une url de fichier
 * [(#CHEMIN{monfichier}|timestamp)]
 *
 * @param string $fichier
 * @return string
 */
function timestamp($fichier){
	if (!$fichier OR !file_exists($fichier)) return $fichier;
	$m = filemtime($fichier);
	return "$fichier?$m";
}

/**
 * Nettoyer le titre d'un email
 * eviter une erreur lorsqu'on utilise |nettoyer_titre_email dans un squelette de mail
 * @param  $titre
 * @return mixed
 */
function filtre_nettoyer_titre_email_dist($titre){
	include_spip('inc/envoyer_mail');
	return nettoyer_titre_email($titre);
}

/**
 * Afficher le sélecteur de rubrique
 *
 * Il permet de placer un objet dans la hiérarchie des rubriques de SPIP
 *
 * @param string $titre
 * @param int $id_objet
 * @param int $id_parent
 * @param string $objet
 * @param int $id_secteur
 * @param bool $restreint
 * @param bool $actionable
 *   true : fournit le selecteur dans un form directement postable
 * @param bool $retour_sans_cadre
 * @return string
 */
function filtre_chercher_rubrique_dist($titre,$id_objet, $id_parent, $objet, $id_secteur, $restreint,$actionable = false, $retour_sans_cadre=false){
	include_spip('inc/filtres_ecrire');
	return chercher_rubrique($titre,$id_objet, $id_parent, $objet, $id_secteur, $restreint,$actionable, $retour_sans_cadre);
}

/**
 * Rediriger une page suivant une autorisation,
 * et ce, n'importe où dans un squelette, même dans les inclusions.
 * Exemple :
 * [(#AUTORISER{non}|sinon_interdire_acces)]
 * [(#AUTORISER{non}|sinon_interdire_acces{#URL_PAGE{login}, 401})]
 *
 * @param bool $ok Indique si l'on doit rediriger ou pas
 * @param string $url Adresse vers laquelle rediriger
 * @param int $statut Statut HTML avec lequel on redirigera
 * @return string
 */
function sinon_interdire_acces($ok=false, $url='', $statut=0){
	if ($ok) return '';
	
	// Vider tous les tampons
	$level = @ob_get_level();
	while ($level--)
		@ob_end_clean();
	
	include_spip('inc/headers');
	$statut = intval($statut);
	
	// Si aucun argument on essaye de deviner quoi faire par défaut
	if (!$url and !$statut){
		// Si on est dans l'espace privé, on génère du 403 Forbidden
		if (test_espace_prive()){
			http_status(403);
			$echec = charger_fonction('403','exec');
			$echec();
		}
		// Sinon dans l'espace public on redirige vers une 404 par défaut, car elle toujours présente normalement
		else{
			$statut = 404;
		}
	}
	
	// On suit les directives indiquées dans les deux arguments
	
	// S'il y a un statut
	if ($statut){
		// Dans tous les cas on modifie l'entité avec ce qui est demandé
		http_status($statut);
		// Si le statut est une erreur et qu'il n'y a pas de redirection on va chercher le squelette du même nom
		if ($statut >= 400 and !$url)
			echo recuperer_fond("$statut");
	}
	
	// S'il y a une URL, on redirige (si pas de statut, la fonction mettra 302 par défaut)
	if ($url) redirige_par_entete($url, '', $statut);
	
	exit;
}

/**
 * Assurer le fonctionnement de |compacte meme sans l'extension compresseur
 * @param string $source
 * @param null|string $format
 * @return string
 */
function filtre_compacte_dist($source, $format = null){
	if (function_exists('compacte'))
		return compacte($source, $format);
	return $source;
}

?>
