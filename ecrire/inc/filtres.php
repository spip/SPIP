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

include_spip('inc/charsets');
include_spip('inc/filtres_mini');

/**
 * Charger un filtre depuis le php :
 * - on inclue tous les fichiers fonctions des plugins et du skel
 * - on appelle chercher_filtre
 */
function charger_filtre($fonc, $default=NULL) {
	include_spip('public/parametrer'); // inclure les fichiers fonctions
	return chercher_filtre($fonc, $default);
}

// http://adoc.spip.org/@chercher_filtre
function chercher_filtre($fonc, $default=NULL) {
	// Cas MIME = type/subtype
	// sans confondre avec les appels de fonction de classe Foo::Bar
	// qui peuvent etre avec un namespace : space\Foo::Bar
	if (strpos($fonc, '/')){
		$f = preg_replace(',\W,','_', $fonc);
		if ($f = chercher_filtre($f)) return $f;
		// cas du sous-type MIME sans filtre associe, passer au type:
		// si filtre_text_plain pas defini, passe a filtre_text
		$fonc = preg_replace(',\W.*$,','', $fonc);
	}
	if (!$fonc) return $default;
	foreach (
	array('filtre_'.$fonc, 'filtre_'.$fonc.'_dist', $fonc) as $f){
		if (is_string($g = $GLOBALS['spip_matrice'][$f]))
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

// http://doc.spip.org/@appliquer_filtre
function appliquer_filtre($arg, $filtre) {
	$f = chercher_filtre($filtre);
	if (!$f)
		return ''; // ou faut-il retourner $arg inchange == filtre_identite?

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
$GLOBALS['spip_matrice']['image_graver'] = 'inc/filtres_images_mini.php';
$GLOBALS['spip_matrice']['image_select'] = 'inc/filtres_images_mini.php';
$GLOBALS['spip_matrice']['image_reduire'] = 'inc/filtres_images_mini.php';
$GLOBALS['spip_matrice']['image_reduire_par'] = 'inc/filtres_images_mini.php';
$GLOBALS['spip_matrice']['image_passe_partout'] = 'inc/filtres_images_mini.php';

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
	include_spip('public/parametrer'); // charger les fichiers fonctions
	if (is_string($f = $GLOBALS['spip_matrice'][$filtre]))
		find_in_path($f,'', true);
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

// pour les feuilles de style CSS du prive
function filtre_background_image_dist ($img, $couleur, $pos="") {
	if (!function_exists("imagecreatetruecolor")
	  OR !include_spip('filtres/images_transforme')
	  OR !function_exists('image_sepia')
	  OR !function_exists('image_aplatir')
	  )
		return "background-color: #$couleur;";
	return "background: url(".url_absolue(extraire_attribut(image_aplatir(image_sepia($img, $couleur),"gif","cccccc", 64, true), "src")).") $pos;";
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
		if ($srcsize = @getimagesize($logo)){
			if (!$srcWidth)	$largeur_img[$logo] = $srcWidth = $srcsize[0];
			if (!$srcHeight)	$hauteur_img[$logo] = $srcHeight = $srcsize[1];
		}
		// $logo peut etre une reference a une image temporaire dont a n'a que le log .src
		// on s'y refere, l'image sera reconstruite en temps utile si necessaire
		elseif(@file_exists($f = "$logo.src")
		  AND lire_fichier($f,$valeurs)
		  AND $valeurs=unserialize($valeurs)) {
			if (!$srcWidth)	$largeur_img[$mem] = $srcWidth = $valeurs["largeur_dest"];
			if (!$srcHeight)	$hauteur_img[$mem] = $srcHeight = $valeurs["hauteur_dest"];
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
// http://doc.spip.org/@entites_html
function entites_html($texte, $tout=false) {
	if (!is_string($texte) OR !$texte
	OR !preg_match(",[&\"'<>],S", $texte) # strpbrk($texte, "&\"'<>")!==false
	) return $texte;
	include_spip('inc/texte');
	$texte = htmlspecialchars(echappe_retour(echappe_html($texte,'',true),'','proteger_amp'));
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
	return unicode2charset($texte);
}

// caracteres de controle - http://www.w3.org/TR/REC-xml/#charsets
// http://doc.spip.org/@supprimer_caracteres_illegaux
function supprimer_caracteres_illegaux($texte) {
	static $from = "\x0\x1\x2\x3\x4\x5\x6\x7\x8\xB\xC\xE\xF\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F";
	static $to = null;
	
	if (is_array($texte)) {
		return array_map('supprimer_caracteres_illegaux', $texte);
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

// Encode du HTML pour transmission XML
// http://doc.spip.org/@texte_backend
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
	$texte = entites_html($texte);

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
		return intval($regs[1]);
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
	return preg_replace(",<a ([^>]*https?://[^>]*class=[\"']spip_(out|url)\b[^>]+)>,",
		"<a \\1 target=\"_blank\">", $texte);
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

// Couper les "mots" de plus de $l caracteres (souvent des URLs)
// http://doc.spip.org/@lignes_longues
function lignes_longues($texte, $l = 70) {
	if ($l<1) return $texte;
	if (!preg_match("/[\w,\/.]{".$l."}/UmsS", $texte))
		return $texte;
	// Passer en utf-8 pour ne pas avoir de coupes trop courtes avec les &#xxxx;
	// qui prennent 7 caracteres
	#include_spip('inc/charsets');
	$texte = str_replace("&nbsp;","<&nbsp>",$texte);
	$texte = html2unicode($texte, true);
	$texte = str_replace("<&nbsp>","&nbsp;",$texte);
	$texte = unicode_to_utf_8(charset2unicode(
		$texte, $GLOBALS['meta']['charset'], true));

	// echapper les tags (on ne veut pas casser les a href=...)
	$tags = array();
	if (preg_match_all('/<.+>|&(?:amp;)?#x?[0-9]+;|&(?:amp;)?[a-zA-Z1-4]{2,6};/UumsS', $texte, $t, PREG_SET_ORDER)) {
		foreach ($t as $n => $tag) {
			$tags[$n] = $tag[0];
			$texte = str_replace($tag[0], "<---$n--->", $texte);
		}
	}
	// casser les mots longs qui restent
	// note : on pourrait preferer couper sur les / , etc.
	if (preg_match_all("/[\w,\/.]{".$l."}/UmsS", $texte, $longs, PREG_SET_ORDER)) {
		foreach ($longs as $long) {
			$texte = str_replace($long[0], $long[0].' ', $texte);
		}
	}

	// retablir les tags
	if (preg_match_all('/<---[\s0-9]+--->/UumsS', $texte, $t, PREG_SET_ORDER)) {
		foreach ($t as $tag) {
			$n = intval(preg_replace(',[^0-9]+,U','',$tag[0]));
			$texte = str_replace($tag[0], $tags[$n], $texte);
		}
	}

	return importer_charset($texte, 'utf-8');
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
	if ($taille < 1024) {$taille = _T('taille_octets', array('taille' => $taille));}
	else if ($taille < 1024*1024) {
		$taille = _T('taille_ko', array('taille' => ((floor($taille / 102.4))/10)));
	} else {
		$taille = _T('taille_mo', array('taille' => ((floor(($taille / 1024) / 102.4))/10)));
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
	$texte = str_replace(array("'",'"'),array('&#39;', '&#34;'), $texte);

	return preg_replace(array("/&(amp;|#38;)/","/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,5};)/"),array("&","&#38;") , $texte);
}

// Vider les url nulles comme 'http://' ou 'mailto:'
// et leur appliquer un htmlspecialchars() + gerer les &amp;
// http://doc.spip.org/@vider_url
function vider_url($url, $entites = true) {
	# un message pour abs_url
	$GLOBALS['mode_abs_url'] = 'url';

	$url = trim($url);
	if (preg_match(",^(http:?/?/?|mailto:?)$,iS", $url))
		return '';

	if ($entites) $url = entites_html($url);

	return $url;
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

// sinon{texte, rien} : affiche "rien" si la chaine est vide,
// affiche la chaine si non vide ;
// attention c'est compile directement dans inc/references
// http://doc.spip.org/@sinon
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

// http://doc.spip.org/@affdate_base
function affdate_base($numdate, $vue, $param = '') {
	global $spip_lang;
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
		if ($mois > 0){
			$saison = 1;
			if (($mois == 3 AND $jour >= 21) OR $mois > 3) $saison = 2;
			if (($mois == 6 AND $jour >= 21) OR $mois > 6) $saison = 3;
			if (($mois == 9 AND $jour >= 21) OR $mois > 9) $saison = 4;
			if (($mois == 12 AND $jour >= 21) OR $mois > 12) $saison = 1;
		}
		return _T('date_saison_'.$saison);

	case 'court':
		if ($avjc) return $annee;
		$a = date('Y');
		if ($annee < ($a - 100) OR $annee > ($a + 100)) return $annee;
		if ($annee != $a) return _T('date_fmt_mois_annee', array ('mois'=>$mois, 'nommois'=>ucfirst($nommois), 'annee'=>$annee));
		return _T('date_fmt_jour_mois', array ('jourmois'=>$jourmois, 'jour'=>$jour, 'mois'=>$mois, 'nommois'=>$nommois, 'annee'=>$annee));

	case 'jourcourt':
		if ($avjc) return $annee;
		$a = date('Y');
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
		$param = $param ? '_'.$param : '';
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
function nom_mois($numdate) {
	return affdate_base($numdate, 'nom_mois');
}

// http://doc.spip.org/@annee
function annee($numdate) {
	return affdate_base($numdate, 'annee');
}

// http://doc.spip.org/@saison
function saison($numdate) {
	return affdate_base($numdate, 'saison');
}

// http://doc.spip.org/@affdate
function affdate($numdate, $format='entier') {
	return affdate_base($numdate, $format);
}

// http://doc.spip.org/@affdate_court
function affdate_court($numdate) {
	return affdate_base($numdate, 'court');
}

// http://doc.spip.org/@affdate_jourcourt
function affdate_jourcourt($numdate) {
	return affdate_base($numdate, 'jourcourt');
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


//
// Alignements en HTML (Old-style, preferer CSS)
//

// Cette fonction cree le paragraphe s'il n'existe pas (texte sur un seul para)
// http://doc.spip.org/@aligner
function aligner($letexte, $justif='') {
	$letexte = trim($letexte);
	if (!strlen($letexte)) return '';

	// Paragrapher proprement
	$letexte = paragrapher($letexte, true);

	// Inserer les alignements
	if ($justif)
		$letexte = str_replace(
		'<p class="spip">', '<p class="spip" align="'.$justif.'">',
		$letexte);

	return $letexte;
}

// http://doc.spip.org/@justifier
function justifier($letexte) {
	return aligner($letexte,'justify');
}

// http://doc.spip.org/@aligner_droite
function aligner_droite($letexte) {
	return aligner($letexte,'right');
}

// http://doc.spip.org/@aligner_gauche
function aligner_gauche($letexte) {
	return aligner($letexte,'left');
}

// http://doc.spip.org/@centrer
function centrer($letexte) {
	return aligner($letexte,'center');
}

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

// http://doc.spip.org/@agenda_connu
function agenda_connu($type)
{
  return in_array($type, array('jour','mois','semaine','periode')) ? ' ' : '';
}


// Cette fonction memorise dans un tableau indexe par son 5e arg
// un evenement decrit par les 4 autres (date, descriptif, titre, URL).
// Appellee avec une date nulle, elle renvoie le tableau construit.
// l'indexation par le 5e arg autorise plusieurs calendriers dans une page

// http://doc.spip.org/@agenda_memo
function agenda_memo($date=0 , $descriptif='', $titre='', $url='', $cal='')
{
  static $agenda = array();
  if (!$date) return $agenda;
  $idate = date_ical($date);
  $cal = trim($cal); // func_get_args (filtre alterner) rajoute \n !!!!
  $agenda[$cal][(date_anneemoisjour($date))][] =  array(
			'CATEGORIES' => $cal,
			'DTSTART' => $idate,
			'DTEND' => $idate,
                        'DESCRIPTION' => texte_script($descriptif),
                        'SUMMARY' => texte_script($titre),
                        'URL' => $url);
  // toujours retourner vide pour qu'il ne se passe rien
  return "";
}

// Cette fonction recoit:
// - un nombre d'evenements,
// - une chaine a afficher si ce nombre est nul,
// - un type de calendrier
// -- et une suite de noms N.
// Elle demande a la fonction precedente son tableau
// et affiche selon le type les elements indexes par N dans ce tableau.
// Si le suite de noms est vide, tout le tableau est pris
// Ces noms N sont aussi des classes CSS utilisees par http_calendrier_init
// Cette fonction recupere aussi par _request les parametres
// jour, mois, annee, echelle, partie_cal (a ameliorer)

// http://doc.spip.org/@agenda_affiche
function agenda_affiche($i)
{
	$args = func_get_args();
	// date (ou nombre d'evenements qu'on pourrait alors afficher)
	$nb = array_shift($args);
	$evt = array_shift($args);
	$type = array_shift($args);
	if ($nb) {
		$agenda = agenda_memo(0);
		$evt = array();
		foreach (($args ? $args : array_keys($agenda)) as $k) {
			if (is_array($agenda[$k]))
				foreach($agenda[$k] as $d => $v) {
					$evt[$d] = $evt[$d] ? (array_merge($evt[$d], $v)) : $v;
				}
		}
	}
	return agenda_periode($type, $nb, $evt);
}

function agenda_periode($type, $nb, $avec, $sans='')
{
	include_spip('inc/agenda');
	$start = agenda_controle();
	if ($start<0) $start = time();
	$mindate = date("Ymd", $start);

	if ($type != 'periode')
		$evt = array($sans, $avec);
	else {
		$min = substr($mindate,6,2);
		$max = !(is_array($avec) AND $avec) ? time() : strtotime(max(array_keys($avec)));
		$max = $min + (($max - $start) / (3600 * 24));
		if ($max < 31) $max = 0;
		$evt = array($sans, $avec, $min, $max);
		$type = 'mois';
	}
	return http_calendrier_init($start, $type,  _request('echelle'), _request('partie_cal'), self('&'), $evt);
}


// Controle la coherence des 3 nombres d'une date et retourne le temps Unix,
// sinon retourne un code d'erreur, toujours negatif
function agenda_controle($date='date', $jour='jour', $mois='mois', $annee='annee')
{
	$jour = _request($jour);
	$mois = _request($mois);
	$annee = _request($annee);
	
	if (!($jour||$mois||$anne)) {
		if ($date = recup_date(_request($date))) {
			list($annee, $mois, $jour ) = $date;
		} else  return -1;
	}
	if (!$d = mktime(0,0,0, $mois, $jour, $annee)) return -2;
	if ($jour != date("d", $d)) return -3;
	if ($mois != date("m", $d)) return -4;
	if ($annee != date("Y", $d)) return -5;
	return $d;
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
	$texte = str_replace("\r\n", "\r", $texte);
	$texte = str_replace("\r", "\n", $texte);

	if (preg_match(",\n+$,", $texte, $fin))
		$texte = substr($texte, 0, -strlen($fin = $fin[0]));
	else
		$fin = '';

	$texte = echappe_html($texte, '', true);


	$debut = '';
	$suite = $texte;
	while ($t = strpos('-'.$suite, "\n", 1)) {
		$debut .= substr($suite, 0, $t-1);
		$suite = substr($suite, $t);
		$car = substr($suite, 0, 1);
		if (($car<>'-') AND ($car<>'_') AND ($car<>"\n") AND ($car<>"|") AND ($car<>"}")
		AND !preg_match(',^\s*(\n|</?(quote|div)|$),S',($suite))
		AND !preg_match(',</?(quote|div)> *$,iS', $debut)) {
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


//
// Gestion des blocs multilingues
//

define('_EXTRAIRE_MULTI', "@<multi>(.*?)</multi>@sS");
// Extraire et transformer les blocs multi ; on indique la langue courante
// pour ne pas mettre de span@lang=fr si on est deja en fr
// http://doc.spip.org/@extraire_multi
function extraire_multi($letexte, $lang=null, $echappe_span=false) {
	if (strpos($letexte, '<multi>') === false) return $letexte; // perf
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
				$trad = traiter_retours_chariots($typographie($trad));
				$trad = explode("\n", $trad);
				foreach($trad as $i => $ligne) {
					if (strlen($ligne)) {
						$ligne = code_echappement($ligne, 'multi');
						$ligne = str_replace("'", '"', inserer_attribut($ligne, 'lang', $l));
						if (lang_dir($l) !== lang_dir($lang))
							$ligne = str_replace("'", '"', inserer_attribut($ligne, 'dir', lang_dir($l)));
						$trad[$i] = $ligne;
					}
				}
				$trad = join("\n", $trad);
				if (!$echappe_span)
					$trad = echappe_retour($trad, 'multi');
			}
			$letexte = str_replace($reg[0], $trad, $letexte);
		}
	}

	return $letexte;
}



//
// Selection dans un tableau dont les index sont des noms de langues
// de la valeur associee a la langue en cours
// si absente, retourne le premier
// remarque : on pourrait aussi appeler un service de traduction externe
// ou permettre de choisir une langue "plus proche",
// par exemple le francais pour l'espagnol, l'anglais pour l'allemand, etc.


function multi_trad ($trads, $lang='') {
	$k = multi_trads($trads, $lang);
	return $k ? $trads[$k] : array_shift($trads);
}

// idem, mais retourne l'index

function multi_trads ($trads, $lang='') {

		if (!$lang) $lang = $GLOBALS['spip_lang'];

	if (isset($trads[$lang])) {
		return $lang;

	}	// cas des langues xx_yy
	else if (preg_match(',^([a-z]+)_,', $lang, $regs) AND isset($trads[$regs[1]])) {
		return $regs[1];
					}
	else  return '';
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
	static $mem;
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

	if ($cpt)
		return count($mem[$famille]);
	if (!($mem[$famille][$donnee]++))
		return $donnee;
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
		$att = filtrer_entites(str_replace("&#39;", "'", $r[4]));
	}
	else
		$att = NULL;

	if ($complet)
		return array($att, $r);
	else
		return $att;
}

// modifier (ou inserer) un attribut html dans une balise
// http://doc.spip.org/@inserer_attribut
function inserer_attribut($balise, $attribut, $val, $proteger=true, $vider=false) {
	// preparer l'attribut
	// supprimer les &nbsp; etc mais pas les balises html
	// qui ont un sens dans un attribut value d'un input
	if ($proteger) $val = attribut_html($val,false);

	// echapper les ' pour eviter tout bug
	$val = str_replace("'", "&#39;", $val);
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


// Un filtre pour determiner le nom du mode des librement inscrits,
// a l'aide de la liste globale des statuts (tableau mode => nom du mode)
// Utile pour le formulaire d'inscription.
// Si un mode est fourni, verifier que la configuration l'accepte.
// Si mode inconnu laisser faire, c'est une extension non std
// mais verifier que la syntaxe est compatible avec SQL

// http://doc.spip.org/@tester_config
function tester_config($id, $mode='') {

	$s = array_search($mode, $GLOBALS['liste_des_statuts']);
	switch ($s) {

	case 'info_redacteurs' :
	  return (($GLOBALS['meta']['accepter_inscriptions'] == 'oui') ? $mode : '');

	case 'info_visiteurs' :
	  return (($GLOBALS['meta']['accepter_visiteurs'] == 'oui' OR $GLOBALS['meta']['forums_publics'] == 'abo') ? $mode : '');

	default:
	  if ($mode AND $mode == addslashes($mode))
	    return $mode;
	  if ($GLOBALS['meta']["accepter_inscriptions"] == "oui")
	    return $GLOBALS['liste_des_statuts']['info_redacteurs'];
	  if ($GLOBALS['meta']["accepter_visiteurs"] == "oui")
	    return $GLOBALS['liste_des_statuts']['info_visiteurs'];
	  return '';
	}
}

//
// Un filtre qui, etant donne un #PARAMETRES_FORUM, retourne un URL de suivi rss
// dudit forum
// Attention applique a un #PARAMETRES_FORUM complexe (id_article=x&id_forum=y)
// ca retourne un url de suivi du thread y (que le thread existe ou non)
// http://doc.spip.org/@url_rss_forum
function url_rss_forum($param) {
	if (!preg_match(',.*(id_(\w*?))=([0-9]+),S', $param, $regs)) return '';
	list(,$k,$t,$v) = $regs;
	if ($t == 'forum') $k = 'id_' . ($t = 'thread');
	return generer_url_public("rss_forum_$t", array($k => $v));
}

//
// Un filtre applique a #PARAMETRES_FORUM, qui donne l'adresse de la page
// de reponse
//
// http://doc.spip.org/@url_reponse_forum
function url_reponse_forum($parametres) {
	if (!$parametres) return '';
	return generer_url_public('forum', $parametres);
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


// Verifier la conformite d'une ou plusieurs adresses email
//  retourne false ou la  normalisation de la derniere adresse donnee
// http://doc.spip.org/@email_valide
function email_valide($adresses) {
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
				.http_img_pack('attachment.gif', $t,
					'height="15" width="15" title="'.attribut_html($t).'"')
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
	$length = extraire_attribut($e, 'length');
	$fichier = basename($url);
	return '<a rel="enclosure"'
		. ($url? ' href="'.htmlspecialchars($url).'"' : '')
		. ($type? ' type="'.htmlspecialchars($type).'"' : '')
		. ($length? ' title="'.htmlspecialchars($length).'"' : '')
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
			. ($url? ' url="'.htmlspecialchars($url).'"' : '')
			. ($type? ' type="'.htmlspecialchars($type).'"' : '')
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
// fabrique un bouton de type $t de Name $n, de Value $v et autres attributs $a
// http://doc.spip.org/@boutonne
function boutonne($t, $n, $v, $a='') {
	return "\n<input type='$t'"
	. (!$n ? '' : " name='$n'")
	. " value=\"$v\" $a />";
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
  return (!is_array($vals) ? $def : (in_array($val, $vals) ? ' ' : ''));
}

// valeur_numerique("3*2") => 6
// n'accepte que les *, + et - (a ameliorer si on l'utilise vraiment)
// http://doc.spip.org/@valeur_numerique
function valeur_numerique($expr) {
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

	$fond = ''; // inutilise mais necessaire
	$contexte = array();
	if (
			($renommer = generer_url_entite() OR $renommer = charger_fonction('page','urls'))
	AND $p = $renommer($action, $fond, $contexte)
	AND $p[3]) {
		$contexte = $p[0];
		$contexte['page'] = $p[3];
		$action = preg_replace('/([?]'.$p[3].'[^&=]*[0-9]+)(&|$)/', '?&', $action);
	}

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
				: ' value="'.str_replace("'","&#39;",entites_html($val)).'"'
				)
			. ' type="hidden" />';
	}
	return join("\n", $hidden);
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
		$bloc_ancre = $ancres[$ancre] = "<a name='$ancre' id='$ancre'></a>";
	else $bloc_ancre = '';
	// liste = false : on ne veut que l'ancre
	if (!$liste)
		return $ancres[$ancre];

	$position = min($position,$total);
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

	$contenu = preg_replace_callback(
		",url\s*\(\s*['\"]?([^'\"/][^:]*)['\"]?\s*\),Uims",
		create_function('$x',
			'return "url(\"".suivre_lien("'.$path.'",$x[1])."\")";'
		), $contenu);

  // les directives filter ... progid:DXImageTransform.Microsoft.AlphaImageLoader(src=...,..)
	// de IEx prennent des urls relatives a la page, et non a la css
	// ne pas y toucher.
	/*
	$contenu = preg_replace_callback(
		";\(\s*src=['\"]?([^'\"/][^:]*)['\"]?\s*(,|\));Uims",
		create_function('$x',
			'return "(src=\"".suivre_lien("'.$path.'",$x[1])."\"".$x[2];'
		), $contenu);
	 */
	return $contenu;
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
		$cssf = preg_replace(',[\W],', "_", $cssf);
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
			AND ($GLOBALS['var_mode'] != 'recalcul'))
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
	AND ($GLOBALS['var_mode'] != 'recalcul'))
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


// filtre table_valeur
// permet de recuperer la valeur d'un tableau pour une cle donnee
// prend en entree un tableau serialise ou non (ce qui permet d'enchainer le filtre)
// http://doc.spip.org/@table_valeur
function table_valeur($table,$cle,$defaut=''){
	$table= is_string($table)?unserialize($table):$table;
	$table= is_array($table)?$table:array();
	return isset($table[$cle])?$table[$cle]:$defaut;
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

// Inserer jQuery
// et au passage verifier qu'on ne doublonne pas #INSERT_HEAD
// http://doc.spip.org/@f_jQuery
function f_jQuery ($texte) {
	static $doublon=0;
	if ($doublon++) {
		erreur_squelette(array('double_occurrence', array('balise' => "INSERT_HEAD")));
	} else {
		$x = '';
		foreach (array_unique(pipeline('jquery_plugins',
		array(
			'javascript/jquery.js',
			'javascript/jquery.form.js',
			'javascript/ajaxCallback.js',
			'javascript/jquery.cookie.js'
		))) as $script)
			if ($script = find_in_path($script))
				$x .= "\n<script src=\"$script\" type=\"text/javascript\"></script>\n";
		$texte = $x.$texte;
	}
	return $texte;
}

/**
 * fonction appelee par #INSERT_HEAD_CSS et #INSERT_HEAD pour la compatibilite
 * @staticvar <type> $done
 * @return <type>
 */
function insert_head_css(){
	static $done = false;
	if ($done) return '';
	$done = true;
	return pipeline('insert_head_css','');
}


// Concatener des chaines
// #TEXTE|concat{texte1,texte2,...}
// http://doc.spip.org/@concat
function concat(){
	$args = func_get_args();
	return join('', $args);
}


// http://doc.spip.org/@charge_scripts
function charge_scripts($scripts) {
  $flux = "";
  $args = is_array($scripts)?$scripts:explode("|",$scripts);
  foreach($args as $script) {
    if(preg_match(",^\w+$,",$script)) {
      $path = find_in_path("javascript/$script.js");
      if($path) $flux .= spip_file_get_contents($path);
    }
  }
  return $flux;
}




// produit une balise img avec un champ alt d'office si vide
// attention le htmlentities et la traduction doivent etre appliques avant.

// http://doc.spip.org/@http_wrapper
function http_wrapper($img){
	if (strpos($img,'/')===FALSE) // on ne prefixe par _NOM_IMG_PACK que si c'est un nom de fichier sans chemin
		$f = chemin_image($img);
	else { // sinon, le path a ete fourni
		$f = $img;
	}
	return $f;
}

// http://doc.spip.org/@http_img_pack
function http_img_pack($img, $alt, $atts='', $title='') {

	$img = http_wrapper($img);
	if (strpos($atts, 'width')===FALSE){
		// utiliser directement l'info de taille presente dans le nom
		if (preg_match(',-([0-9]+)[.](png|gif)$,',$img,$regs)){
				$size = array(intval($regs[1]),intval($regs[1]));
		}
		else
			$size = @getimagesize($img);
		$atts.=" width='".$size[0]."' height='".$size[1]."'";
	}
	return  "<img src='" . $img
	  . ("'\nalt=\"" .
	     str_replace('"','', textebrut($alt ? $alt : ($title ? $title : '')))
	     . '" ')
	  . ($title ? "title=\"$title\" " : '')
	  . $atts
	  . " />";
}

// http://doc.spip.org/@http_style_background
function http_style_background($img, $att='')
{
  return " style='background: url(\"".http_wrapper($img)."\")" .
	    ($att ? (' ' . $att) : '') . ";'";
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
		if (!$infos = $get_infos($plugins_actifs[$plugin]['dir']))
			return '';
		if ($type_info == 'tout')
			return $infos;
		else
			return strval($infos[$type_info]);
	}
}


// http://doc.spip.org/@chercher_rubrique
function chercher_rubrique($msg,$id, $id_parent, $type, $id_secteur, $restreint,$actionable = false, $retour_sans_cadre=false){
	global $spip_lang_right;
	include_spip('inc/autoriser');
	if (intval($id) && !autoriser('modifier', $type, $id))
		return "";
	if (!sql_countsel('spip_rubriques'))
		return "";
	$chercher_rubrique = charger_fonction('chercher_rubrique', 'inc');
	$form = $chercher_rubrique($id_parent, $type, $restreint, ($type=='rubrique')?$id:0);

	if ($id_parent == 0) $logo = "racine-site-24.gif";
	elseif ($id_secteur == $id_parent) $logo = "secteur-24.gif";
	else $logo = "rubrique-24.gif";

	$confirm = "";
	if ($type=='rubrique') {
		// si c'est une rubrique-secteur contenant des breves, demander la
		// confirmation du deplacement
		$contient_breves = sql_countsel('spip_breves', "id_rubrique=$id");

		if ($contient_breves > 0) {
			$scb = ($contient_breves>1? 's':'');
			$scb = _T('avis_deplacement_rubrique',
				array('contient_breves' => $contient_breves,
				      'scb' => $scb));
			$confirm .= "\n<div class='confirmer_deplacement verdana2'><div class='choix'><input type='checkbox' name='confirme_deplace' value='oui' id='confirme-deplace' /><label for='confirme-deplace'>" . $scb . "</label></div></div>\n";
		} else
			$confirm .= "<input type='hidden' name='confirme_deplace' value='oui' />\n";
	}
	$form .= $confirm;
	if ($actionable){
		if (strpos($form,'<select')!==false) {
			$form .= "<div style='text-align: $spip_lang_right;'>"
				. '<input type="submit" value="'._T('bouton_choisir').'"/>'
				. "</div>";
		}
		$form = "<input type='hidden' name='editer_$type' value='oui' />\n" . $form;
		$form = generer_action_auteur("editer_$type", $id, self(), $form, " method='post' class='submit_plongeur'");
	}

	if ($retour_sans_cadre)
		return $form;

	include_spip('inc/presentation');
	return debut_cadre_couleur($logo, true, "", $msg) . $form .fin_cadre_couleur(true);

}


// http://doc.spip.org/@puce_changement_statut
function puce_changement_statut($id_objet, $statut, $id_rubrique, $type, $ajax=false){
	$puce_statut = charger_fonction('puce_statut','inc');
	return $puce_statut($id_objet, $statut, $id_rubrique, $type, $ajax=false);
}

// Encoder un contexte pour l'ajax, le signer avec une cle, le crypter
// avec le secret du site, le gziper si possible...
// l'entree peut etre serialisee (le #ENV** des fonds ajax et ajax_stat)
// http://doc.spip.org/@encoder_contexte_ajax
function encoder_contexte_ajax($c,$form='', $emboite=NULL) {
	if (is_string($c)
	AND !is_null(@unserialize($c)))
		$c = unserialize($c);

	// supprimer les parametres debut_x
	// pour que la pagination ajax ne soit pas plantee
	// si on charge la page &debut_x=1 : car alors en cliquant sur l'item 0,
	// le debut_x=0 n'existe pas, et on resterait sur 1
	foreach ($c as $k => $v)
		if (strpos($k,'debut_') === 0)
			unset($c[$k]);

	include_spip("inc/securiser_action");
	$cle = calculer_cle_action($form.(is_array($c)?serialize($c):$c));
	$c = serialize(array($c,$cle));

	if ((defined('_CACHE_CONTEXTES_AJAX') AND _CACHE_CONTEXTES_AJAX)
		AND $dir = sous_repertoire(_DIR_CACHE, 'contextes')) {
		// stocker les contextes sur disque et ne passer qu'un hash dans l'url
		$md5 = md5($c);
		ecrire_fichier("$dir/c$md5",$c);
		$c = $md5;
	} else {
		if (function_exists('gzdeflate') && function_exists('gzinflate'))
			$c = gzdeflate($c);
		$c = _xor($c);
		$c = base64_encode($c);
	}
	
	if ($emboite === NULL) return $c;
	return !trim($emboite) ? '' :
	"<div class='ajaxbloc env-$c'>\n$emboite</div><!-- ajaxbloc -->\n";
}

// la procedure inverse de encoder_contexte_ajax()
// http://doc.spip.org/@decoder_contexte_ajax
function decoder_contexte_ajax($c,$form='') {
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
		include_spip("inc/securiser_action");
		$key = pack("H*", calculer_cle_action('_xor'));
	}

	$keylen = strlen($key);
	$messagelen = strlen($message);
	for($i=0; $i<$messagelen; $i++)
		$message[$i] = ~($message[$i]^$key[$i%$keylen]);

	return $message;
}



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
function lien_ou_expose($url,$libelle,$on=false,$class="",$title="",$rel="", $evt=''){
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

	return "<$bal $att>$libelle</$bal>";
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
	$taille = taille_image($img);
	list($hauteur,$largeur) = $taille;
	if (!$hauteur OR !$largeur)
		return "";
	return
	"<img src='$img' width='$largeur' height='$hauteur'"
	  ." alt='".attribut_html($alt)."'"
	  .($class?" class='".attribut_html($class)."'":'')
	  .' />';
}


/**
 * Afficher un message "un truc"/"N trucs"
 *
 * @param int $nb
 * @return string
 */
function singulier_ou_pluriel($nb,$chaine_un,$chaine_plusieurs,$var='nb'){
	if (!$nb=intval($nb)) return "";
	if ($nb>1) return _T($chaine_plusieurs, array($var => $nb));
	else return _T($chaine_un);
}


/**
 * un filtre icone mappe sur icone_inline, qui cree une icone a gauche par defaut
 * le code de icone_inline est grandement reproduit ici car les liens ajax portent simplement une class ajax
 * lorsque les interfaces sont en squelette, alors que l'implementation d'ajax de des scripts php
 * est plus complexe
 *
 * @param string $lien
 * @param string $texte
 * @param string $fond
 * @param string $align
 * @param string $fonction
 * @return string
 */
function filtre_icone_dist($lien, $texte, $fond, $align="", $fonction="", $class="",$javascript=""){
	if ($icone_renommer = charger_fonction('icone_renommer','inc',true))
		list($fond,$fonction) = $icone_renommer($fond,$fonction);

	$align = $align?$align:$GLOBALS['spip_lang_left'];
	global $spip_display;

	if ($fonction == "del") {
		$style = 'icone36 danger';
	} else {
		$style = 'icone36';
		if (strlen($fonction) < 3) $fonction = "rien.gif";
	}
	$style .= " " . substr(basename($fond),0,-4);

	if ($spip_display == 1){
		$hauteur = 20;
		$largeur = 100;
		$title = $alt = "";
	}
	else if ($spip_display == 3){
		$hauteur = 30;
		$largeur = 30;
		$title = "\ntitle=\"$texte\"";
		$alt = $texte;
	}
	else {
		$hauteur = 70;
		$largeur = 100;
		$title = '';
		$alt = $texte;
	}

	$size = 24;
	if (preg_match("/-([0-9]{1,3})[.](gif|png)$/i",$fond,$match))
		$size = $match[1];
	if ($spip_display != 1 AND $spip_display != 4){
		if ($fonction != "rien.gif"){
		  $icone = http_img_pack($fonction, $alt, "$title width='$size' height='$size'\n" .
					  http_style_background($fond, "no-repeat center center"));
		}
		else {
			$icone = http_img_pack($fond, $alt, "$title width='$size' height='$size'");
		}
	} else $icone = '';

	// cas d'ajax_action_auteur: faut defaire le boulot
	// (il faudrait fusionner avec le cas $javascript)
	if (preg_match(",^<a\shref='([^']*)'([^>]*)>(.*)</a>$,i",$lien,$r))
		list($x,$lien,$atts,$texte)= $r;
	else $atts = '';

	if ($align && $align!='center') $align = "float: $align; ";

	$icone = "<a style='$align' class='$style $class'"
	. $atts
	. $javascript
	. "\nhref='"
	. $lien
	. "'>"
	. $icone
	. (($spip_display == 3)	? '' : "<span>$texte</span>")
	  . "</a>\n";

	if ($align <> 'center') return $icone;
	$style = " style='text-align:center;'";
	return "<div$style>$icone</div>";
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

/*
 * Deux verrues pour que le pipeline de revisions soit correct
 * elles vont sauter quand ca passera en plugin
 */
function premiere_revision($x) {
	include_spip('inc/revisions');
	return enregistrer_premiere_revision($x);
	}
function nouvelle_revision($x) {
	include_spip('inc/revisions');
	return enregistrer_nouvelle_revision($x);
}

/**
 * Generer un bouton_action
 * utilise par #BOUTON_ACTION
 *
 * @param string $libelle
 * @param string $url
 * @param string $class
 * @param string $confirm
 * @param string $title
 * @return string
 */
function bouton_action($libelle, $url, $class="", $confirm="", $title=""){
	$onclick = $confirm?" onclick='return confirm(\"" . attribut_html($confirm) . "\");'":"";
	$title = $title ? " title='$title'" : "";

	return "<form class='bouton_action_post $class' method='post' action='$url'><div>".form_hidden($url)
		."<button type='submit' class='submit'$title$onclick>$libelle</button></div></form>";
}


?>
