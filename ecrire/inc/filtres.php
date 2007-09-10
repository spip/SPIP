<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/charsets');
// on definit la matrice pour les filtres images : le compilateur fera passer l'appel par filtrer
// on ne definit pas de fichier a inclure : l'inclusion sera faite dans image_filtrer
// par un include_spip unique en cas d'appel multiple

$GLOBALS['spip_matrice']['image_valeurs_trans'] = '';
$GLOBALS['spip_matrice']['image_reduire'] = '';
$GLOBALS['spip_matrice']['image_reduire_par'] = '';
$GLOBALS['spip_matrice']['image_recadre'] = '';
$GLOBALS['spip_matrice']['image_alpha'] = '';
$GLOBALS['spip_matrice']['image_flip_vertical'] = '';
$GLOBALS['spip_matrice']['image_flip_horizontal'] = '';
$GLOBALS['spip_matrice']['image_masque'] = '';
$GLOBALS['spip_matrice']['image_nb'] = '';
$GLOBALS['spip_matrice']['image_flou'] = '';
$GLOBALS['spip_matrice']['image_RotateBicubic'] = '';
$GLOBALS['spip_matrice']['image_rotation'] = '';
$GLOBALS['spip_matrice']['image_distance_pixel'] = '';
$GLOBALS['spip_matrice']['image_decal_couleur'] = '';
$GLOBALS['spip_matrice']['image_gamma'] = '';
$GLOBALS['spip_matrice']['image_decal_couleur_127'] = '';
$GLOBALS['spip_matrice']['image_sepia'] = '';
$GLOBALS['spip_matrice']['image_aplatir'] = '';
$GLOBALS['spip_matrice']['image_couleur_extraire'] = '';
$GLOBALS['spip_matrice']['image_select'] = '';
$GLOBALS['spip_matrice']['image_renforcement'] = '';
$GLOBALS['spip_matrice']['image_imagick'] = '';
$GLOBALS['spip_matrice']['image_ramasse_miettes'] = '';
$GLOBALS['spip_matrice']['image_passe_partout'] = '';

$inc_filtres_images = _DIR_RESTREINT."inc/filtres_images.php"; # find_in_path('inc/filtres_images');
$GLOBALS['spip_matrice']['couleur_dec_to_hex'] = $inc_filtres_images;
$GLOBALS['spip_matrice']['couleur_hex_to_dec'] = $inc_filtres_images;
$GLOBALS['spip_matrice']['couleur_extreme'] = $inc_filtres_images;
$GLOBALS['spip_matrice']['couleur_inverser'] = $inc_filtres_images;
$GLOBALS['spip_matrice']['couleur_eclaircir'] = $inc_filtres_images;
$GLOBALS['spip_matrice']['couleur_foncer'] = $inc_filtres_images;
$GLOBALS['spip_matrice']['couleur_foncer_si_claire'] = $inc_filtres_images;
$GLOBALS['spip_matrice']['couleur_eclaircir_si_foncee'] = $inc_filtres_images;
$GLOBALS['spip_matrice']['couleur_web'] = $inc_filtres_images;
$GLOBALS['spip_matrice']['couleur_4096'] = $inc_filtres_images;

// http://doc.spip.org/@chercher_filtre
function chercher_filtre($fonc) {
		foreach (
		array('filtre_'.$fonc, 'filtre_'.$fonc.'_dist', $fonc) as $f)
			if (function_exists($f)
			OR (preg_match("/^(\w*)::(\w*)$/", $f, $regs)                            
				AND is_callable(array($regs[1], $regs[2]))
			)) {
				return $f;
			}
		return NULL;
}

// Appliquer un filtre (eventuellement defini dans la matrice) aux donnees
// et arguments
// http://doc.spip.org/@filtrer
function filtrer($filtre) {
	if (isset($GLOBALS['spip_matrice'][$filtre])
	AND $f = $GLOBALS['spip_matrice'][$filtre])
		include_once($f);

	$tous = func_get_args();
	if (substr($filtre,0,6)=='image_')
		return image_filtrer($tous);
	else{
		array_shift($tous); # enlever $filtre
		return call_user_func_array($filtre, $tous);
	}
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
	(preg_match(',^8.*dir[\r\n]+(\d+),ms', $c, $r1) # svn >= 1.4
	AND $v = $r1[1]
	)))
		return -$v;

	// Bug ou paquet fait main
	return 0;
}

//
// Fonctions graphiques
//

// fonction generique d'entree des filtres images
// accepte en entree un texte complet, un img-log (produit par #LOGO_XX),
// un tag <img ...> complet, ou encore un nom de fichier *local* (passer
// le filtre |copie_locale si on veut l'appliquer a un document)
// applique le filtre demande a chacune des occurences

// http://doc.spip.org/@image_filtrer
function image_filtrer($args){
	static $inclure = true;
	$filtre = array_shift($args); # enlever $filtre
	$texte = array_shift($args);
	if (!$texte) return;
	// Cas du nom de fichier local
	if (preg_match(',^('._DIR_IMG .'|'. _DIR_IMG_PACK .'|'. _DIR_VAR .'),', $texte)) {
		if ($inclure){
			include_spip('inc/filtres_images');
			$inclure = false;
		}
		array_unshift($args,"<img src='$texte' />");
		return call_user_func_array($filtre, $args);
	}

	// Cas general : trier toutes les images, avec eventuellement leur <span>
	if (preg_match_all(
		',(<([a-z]+) [^<>]*spip_documents[^<>]*>)?\s*(<img\s.*>),UimsS',
		$texte, $tags, PREG_SET_ORDER)) {
		if ($inclure){
			include_spip('inc/filtres_images');
			$inclure = false;
		}
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

	return $texte;
}

// Pour assurer la compatibilite avec les anciens nom des filtres image_xxx
// commencent par "image_"
// http://doc.spip.org/@reduire_image
function reduire_image($texte, $taille = -1, $taille_y = -1) {
	return filtrer('image_graver',
		filtrer('image_reduire',$texte, $taille, $taille_y)
	);
}
// http://doc.spip.org/@valeurs_image_trans
function valeurs_image_trans($img, $effet, $forcer_format = false) {
	include_spip('inc/filtres_images');
	return image_valeurs_trans($img, $effet, $forcer_format = false);
}
// http://doc.spip.org/@couleur_extraire
function couleur_extraire($img, $x=10, $y=6) {
	return filtrer('image_couleur_extraire',$img, $x, $y);
}
// http://doc.spip.org/@image_typo
function image_typo() {
	include_spip('inc/filtres_images');
	$tous = func_get_args();
	return call_user_func_array('produire_image_typo', $tous);
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
	
	// pour essayer de limiter les lectures disque
	// $meme remplace $logo, pour unifier certains fichiers dont on sait qu'ils ont la meme taille
	$mem = $logo;
	if (strrpos($mem,"/") > 0) $mem = substr($mem, strrpos($mem,"/")+1, strlen($mem));
	$mem = preg_replace(",\-flip\_v|\-flip\_h,", "", $mem);
	$mem = preg_replace(",\-nb\-[0-9]+(\.[0-9]+)?\-[0-9]+(\.[0-9]+)?\-[0-9]+(\.[0-9]+)?,", "", $mem);

	$srcsize = false;
	if (isset($largeur_img[$mem]))
		$srcWidth = $largeur_img[$mem];
	if (!$srcWidth AND $srcsize = @getimagesize($logo)) {
		$srcWidth = $srcsize[0];
	 	$largeur_img[$mem] = $srcWidth;
	}
	if (isset($hauteur_img[$mem]))
		$srcHeight = $hauteur_img[$mem];
	if (!$srcHeight AND ($srcsize OR ($srcsize = @getimagesize($logo)))) {
		$srcHeight = $srcsize[1];
		$hauteur_img[$mem] = $srcHeight;
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
	if (!$texte) return $texte;
	include_spip("inc/texte");
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
#	include_spip('inc/charsets');
	// filtrer
	$texte = html2unicode($texte);
	// remettre le tout dans le charset cible
	return unicode2charset($texte);
}

// caracteres de controle - http://www.w3.org/TR/REC-xml/#charsets
// http://doc.spip.org/@supprimer_caracteres_illegaux
function supprimer_caracteres_illegaux($texte) {
	$from = "\x0\x1\x2\x3\x4\x5\x6\x7\x8\xB\xC\xE\xF\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F";
	$to = str_repeat('-', strlen($from));
	return strtr($texte, $from, $to);
}

// Supprimer caracteres windows et les caracteres de controle ILLEGAUX
// http://doc.spip.org/@corriger_caracteres
function corriger_caracteres ($texte) {
	include_spip('inc/charsets');
	$texte = corriger_caracteres_windows($texte);
	$texte = supprimer_caracteres_illegaux($texte);
	return $texte;
}

// Encode du HTML pour transmission XML
// http://doc.spip.org/@texte_backend
function texte_backend($texte) {

	// si on a des liens ou des images, les passer en absolu
	$texte = liens_absolus($texte);

	// echapper les tags &gt; &lt;
	$texte = preg_replace(',&(gt|lt);,S', '&amp;\1;', $texte);

	// importer les &eacute;
	$texte = filtrer_entites($texte);

	// " -> &quot; et tout ce genre de choses
	// contourner bug windows ou char(160) fait partie de la regexp \s
	$u = ($GLOBALS['meta']['charset']=='utf-8') ? 'u':'';
	$texte = str_replace("&nbsp;", " ", $texte);
	$texte = preg_replace('/\s{2,}/'.$u.'S', " ", $texte);
	$texte = entites_html($texte);

	// verifier le charset
	$texte = charset2unicode($texte);

	// Caracteres problematiques en iso-latin 1
	if ($GLOBALS['meta']['charset'] == 'iso-8859-1') {
		$texte = str_replace(chr(156), '&#156;', $texte);
		$texte = str_replace(chr(140), '&#140;', $texte);
		$texte = str_replace(chr(159), '&#159;', $texte);
	}

	// nettoyer l'apostrophe curly qui semble poser probleme a certains rss-readers
	$texte = str_replace("&#8217;","'",$texte);

	return $texte;
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
	$u = (@$GLOBALS['meta']['charset']=='utf-8' && test_pcre_unicode()) ? 'u':'';
	$texte = preg_replace('/\s+/'.$u.'S', " ", $texte);
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
	return preg_replace(",<a ([^>]*https?://[^>]*class=\"spip_(out|url)\b[^>]+)>,",
		"<a \\1 target=\"_blank\">", $texte);
}

// Transformer les sauts de paragraphe en simples passages a la ligne
// http://doc.spip.org/@PtoBR
function PtoBR($texte){
	$texte = preg_replace("@</p>@i", "\n", $texte);
	$texte = preg_replace("@<p([\s][^>]*)?".">@i", "<br />", $texte);
	$texte = preg_replace("@^[\s]*<br />@", "", $texte);
	return $texte;
}

// Couper les "mots" de plus de $l caracteres (souvent des URLs)
// http://doc.spip.org/@lignes_longues
function lignes_longues($texte, $l = 70) {
	// Passer en utf-8 pour ne pas avoir de coupes trop courtes avec les &#xxxx;
	// qui prennent 7 caracteres
	#include_spip('inc/charsets');
	$texte = unicode_to_utf_8(charset2unicode(
		$texte, $GLOBALS['meta']['charset'], true));

	// echapper les tags (on ne veut pas casser les a href=...)
	$tags = array();
	if (preg_match_all('/<.*>/UumsS', $texte, $t, PREG_SET_ORDER)) {
		foreach ($t as $n => $tag) {
			$tags[$n] = $tag[0];
			$texte = str_replace($tag[0], " @@SPIPTAG$n@@ ", $texte);
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
	foreach ($tags as $n=>$tag) {
		$texte = str_replace(" @@SPIPTAG$n@@ ", $tag, $texte);
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
function attribut_html($texte) {
	$texte = texte_backend(preg_replace(array(",\n,",",\s(?=\s),ms"),array(" ",""),textebrut($texte)));
	$texte = str_replace(array("'",'"'),array('&#39;', '&#34;'), $texte);
	
	return preg_replace(array("/&(amp;|#38;)/","/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,5};)/"),array("&","&#38;") , $texte);
	return $texte;
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

// |sinon{rien} : affiche "rien" si la chaine est vide, affiche la chaine si non vide
// http://doc.spip.org/@sinon
function sinon ($texte, $sinon='') {
	if (strlen($texte))
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

// http://doc.spip.org/@normaliser_date
function normaliser_date($date) {
	if ($date) {
		$date = vider_date($date);
		if (preg_match("/^[0-9]{8,10}$/", $date))
			$date = date("Y-m-d H:i:s", $date);
		if (preg_match("#^([12][0-9]{3})([-/]00)?( [-0-9:]+)?$#", $date, $regs))
			$date = $regs[1]."-01-01".$regs[3];
		else if (preg_match("#^([12][0-9]{3}[-/][01]?[0-9])([-/]00)?( [-0-9:]+)?$#", $date, $regs))
			$date = preg_replace("@/@","-",$regs[1])."-01".$regs[3];
		else
			$date = date("Y-m-d H:i:s", strtotime($date));
	}
	return $date;
}

// http://doc.spip.org/@vider_date
function vider_date($letexte) {
	if (strncmp("0000-00-00", $letexte,10)==0) return;
	if (strncmp("1970-01-01", $letexte,10)==0) return;	// eviter le bug GMT-1
	return $letexte;
}

// http://doc.spip.org/@recup_heure
function recup_heure($numdate){
	if (!$numdate) return '';

	if (preg_match('#([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})#', $numdate, $regs)) {
		$heures = $regs[1];
		$minutes = $regs[2];
		$secondes = $regs[3];
	}
	return array($heures, $minutes, $secondes);
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
function recup_date($numdate){
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
	}
	elseif (preg_match('#([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})#',$numdate, $regs)) {
		$annee = $regs[1];
		$mois = $regs[2];
		$jour = $regs[3];
	}
	elseif (preg_match('#([0-9]{4})-([0-9]{2})#', $numdate, $regs)){
		$annee = $regs[1];
		$mois = $regs[2];
		$jour ='';
	}
	elseif (preg_match('#([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})#', $numdate, $regs)){
		$annee = $regs[1];
		$mois = $regs[2];
		$jour = $regs[3];
		$heures = $regs[4];
		$minutes = $regs[5];
		$secondes = $regs[6];
	} else $annee = $mois =  $jour =''; 
	if ($annee > 4000) $annee -= 9000;
	if (substr($jour, 0, 1) == '0') $jour = substr($jour, 1);

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
function date_relative($date, $decalage_maxi=0) {
	
	if (!$date) return;
	$decal = date("U") - date("U", strtotime($date));

	if ($decalage_maxi AND ($decal > $decalage_maxi OR $decal < 0))
		return '';

	if ($decal < 0) {
		$il_y_a = "date_dans";
		$decal = -1 * $decal;
	} else {
		$il_y_a = "date_il_y_a";
	}
	
	if ($decal < 3600) {
		$minutes = ceil($decal / 60);
		$retour = _T($il_y_a, array("delai"=>"$minutes "._T("date_minutes"))); 
	}
	else if ($decal < (3600 * 24) ) {
		$heures = ceil ($decal / 3600);
		$retour = _T($il_y_a, array("delai"=>"$heures "._T("date_heures"))); 
	}
	else if ($decal < (3600 * 24 * 7)) {
		$jours = ceil ($decal / (3600 * 24));
		$retour = _T($il_y_a, array("delai"=>"$jours "._T("date_jours"))); 
	}
	else if ($decal < (3600 * 24 * 7 * 4)) {
		$semaines = ceil ($decal / (3600 * 24 * 7));
		$retour = _T($il_y_a, array("delai"=>"$semaines "._T("date_semaines"))); 
	}
	else if ($decal < (3600 * 24 * 30 * 6)) {
		$mois = ceil ($decal / (3600 * 24 * 30));
		$retour = _T($il_y_a, array("delai"=>"$mois "._T("date_mois"))); 
	}
	else {
		$retour = affdate_court($date);
	}



	return $retour;
}


// http://doc.spip.org/@affdate_base
function affdate_base($numdate, $vue, $param = '') { 
	global $spip_lang;
	$date_array = recup_date($numdate);
	if (!$date_array) return;
	list($annee, $mois, $jour, $heures, $minutes, $secondes)= $date_array;

	// 1er, 21st, etc.
	$journum = $jour;

	if ($jour == 0)
		$jour = '';
	else if ($jourth = _T('date_jnum'.$jour))
			$jour = $jourth;

	$mois = intval($mois);
	if ($mois > 0 AND $mois < 13) {
		$nommois = _T('date_mois_'.$mois);
		if ($jour)
			$jourmois = _T('date_de_mois_'.$mois, array('j'=>$jour, 'nommois'=>$nommois));
		else
			$jourmois = $nommois;
	}

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
		else
			return trim(_T('date_fmt_mois_annee', array ('mois'=>$mois, 'nommois'=>$nommois, 'annee'=>$annee)));

	case 'nom_mois':
		return $nommois;

	case 'mois':
		return sprintf("%02s",$mois);

	case 'jour':
		return $jour;

	case 'journum':
		return $journum;

	case 'nom_jour':
		if (!$mois OR !$jour) return '';
		$nom = mktime(1,1,1,$mois,$jour,$annee);
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
	list($annee, $mois, $jour, $heures, $minutes, $secondes)= $date_array;
	return _T('date_fmt_jour_heure', array('jour' => affdate($numdate), 'heure' => heures_minutes("$heures:$minutes:$secondes")));
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
	$time = mktime($heures, $minutes, $secondes, $mois, $jour, $annee);
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

// http://doc.spip.org/@agenda_affiche
function agenda_affiche($i)
{
	include_spip('inc/agenda');
	$args = func_get_args();
	$nb = array_shift($args); // nombre d'evenements (on pourrait l'afficher)
	$sinon = array_shift($args);
	$type = array_shift($args);
	if (!$nb){ 
		return http_calendrier_init('', ($type != 'periode') ? $type : 'mois', '', '', self('&'), $sinon);
	}	
	$agenda = agenda_memo(0);
	$evt = array();
	foreach (($args ? $args : array_keys($agenda)) as $k) {  
		if (is_array($agenda[$k]))
		foreach($agenda[$k] as $d => $v) { 
			$evt[$d] = $evt[$d] ? (array_merge($evt[$d], $v)) : $v;
		}
	}
	$d = array_keys($evt);

	if (count($d)){
		$mindate = min($d);
		$start = strtotime($mindate);
	}
	else {  
		$mindate = ($j=_request('jour')) * ($m=_request('mois')) * ($a=_request('annee'));  
  		if ($mindate)
			$start = mktime(0,0,0, $m, $j, $a);
  		else $start = mktime(0,0,0);
	}
	if ($type != 'periode')
		$evt = array('', $evt);
	else {
		$min = substr($mindate,6,2);
		$max = $min + ((strtotime(max($d)) - $start) / (3600 * 24));
		if ($max < 31) $max = 0;
		$evt = array('', $evt, $min, $max);
		$type = 'mois';
	}
	return http_calendrier_init($start, $type, '', '', self('&'), $evt);
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
	$texte = echappe_html($texte, '', true);

	$debut = '';
	$suite = $texte;
	while ($t = strpos('-'.$suite, "\n", 1)) {
		$debut .= substr($suite, 0, $t-1);
		$suite = substr($suite, $t);
		$car = substr($suite, 0, 1);
		if (($car<>'-') AND ($car<>'_') AND ($car<>"\n") AND ($car<>"|"))
			$debut .= $delim;
		else
			$debut .= "\n";
		if (preg_match(",^\n+,", $suite, $regs)) {
			$debut.=$regs[0];
			$suite = substr($suite, strlen($regs[0]));
		}
	}
	$texte = $debut.$suite;

	$texte = echappe_retour($texte);
	return $texte;
}


//
// Gestion des blocs multilingues
//

//
// Selection dans un tableau dont les index sont des noms de langues
// de la valeur associee a la langue en cours
//

// http://doc.spip.org/@multi_trad
function multi_trad ($trads) {
	global  $spip_lang; 

	if (isset($trads[$spip_lang])) {
		return $trads[$spip_lang];

	}	// cas des langues xx_yy
	else if (preg_match(',^([a-z]+)_,', $spip_lang, $regs) AND isset($trads[$regs[1]])) {
		return $trads[$regs[1]];
	}	
	// sinon, renvoyer la premiere du tableau
	// remarque : on pourrait aussi appeler un service de traduction externe
	// ou permettre de choisir une langue "plus proche",
	// par exemple le francais pour l'espagnol, l'anglais pour l'allemand, etc.
	else  return array_shift($trads);
}

// analyse un bloc multi
// http://doc.spip.org/@extraire_trad
function extraire_trad ($bloc) {
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

	// faire la traduction avec ces donnees
	return multi_trad($trads);
}

// repere les blocs multi dans un texte et extrait le bon
// http://doc.spip.org/@extraire_multi
function extraire_multi ($letexte) {
	if (strpos($letexte, '<multi>') === false) return $letexte; // perf
	if (preg_match_all("@<multi>(.*?)</multi>@sS", $letexte, $regs, PREG_SET_ORDER))
		foreach ($regs as $reg)
			$letexte = str_replace($reg[0], extraire_trad($reg[1]), $letexte);
	return $letexte;
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
	',(^.*?<(?:(?>\s*)(?>[\w:]+)(?>(?:=(?:"[^"]*"|\'[^\']*\'|[^\'"]\S*))?))*?)(\s+'
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
function inserer_attribut($balise, $attribut, $val, $texte_backend=true, $vider=false) {
	// preparer l'attribut
	if ($texte_backend) $val = texte_backend($val); # supprimer les &nbsp; etc

	// echapper les ' pour eviter tout bug
	$val = str_replace("'", "&#39;", $val);
	if ($vider AND strlen($val)==0)
		$insert = '';
	else
		$insert = " $attribut='$val' ";

	list($old, $r) = extraire_attribut($balise, $attribut, true);

	if ($old !== NULL) {
		// Remplacer l'ancien attribut du meme nom
		$balise = $r[1].$insert.$r[5];
	}
	else {
		// preferer une balise " />" (comme <img />)
		if (preg_match(',[[:space:]]/>,S', $balise))
			$balise = preg_replace(",[[:space:]]/>,S", $insert."/>", $balise, 1);
		// sinon une balise <a ...> ... </a>
		else
			$balise = preg_replace(",>,", $insert.">", $balise, 1);
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
	if (preg_match(',.*(id_.*?)=([0-9]+),S', $param, $regs)) {
		include_spip('inc/acces');
		$regs[1] = str_replace('id_forum', 'id_thread', $regs[1]);
		$arg = $regs[1].'-'.$regs[2];
		$cle = afficher_low_sec(0, "rss forum $arg");
		return generer_url_action('rss', "op=forum&args=$arg&cle=$cle");
	}
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
// Filtres d'URLs
//

// Nettoyer une URL contenant des ../
//
// resolve_url('/.././/truc/chose/machin/./.././.././hopla/..');
// inspire (de loin) par PEAR:NetURL:resolvePath
//
// http://doc.spip.org/@resolve_path
function resolve_path($url) {
	while (preg_match(',/\.?/,', $url, $regs)		# supprime // et /./
	OR preg_match(',/[^/]*/\.\./,S', $url, $regs)	# supprime /toto/../
	OR preg_match(',^/\.\./,S', $url, $regs))		# supprime les /../ du haut
		$url = str_replace($regs[0], '/', $url);

	return '/'.preg_replace(',^/,S', '', $url);
}

// 
// Suivre un lien depuis une adresse donnee -> nouvelle adresse
//
// suivre_lien('http://rezo.net/sous/dir/../ect/ory/fi.html..s#toto',
// 'a/../../titi.coco.html/tata#titi');
// http://doc.spip.org/@suivre_lien
function suivre_lien($url, $lien) {
	# lien absolu ? ok
	if (preg_match(',^([a-z0-9]+://|mailto:|javascript:),iS', $lien))
		return $lien;

	# lien relatif, il faut verifier l'url de base
	if (preg_match(',^(.*?://[^/]+)(/.*?/?)?[^/]*$,S', $url, $regs)) {
		$debut = $regs[1];
		$dir = $regs[2];
	}
	if (substr($lien,0,1) == '/')
		return $debut . resolve_path($lien);
	else
		return $debut . resolve_path($dir.$lien);
}

// un filtre pour transformer les URLs relatives en URLs absolues ;
// ne s'applique qu'aux #URL_XXXX
// http://doc.spip.org/@url_absolue
function url_absolue($url, $base='') {
	if (strlen($url = trim($url)) == 0)
		return '';
	if (!$base)
		$base = url_de_base() . (_DIR_RACINE ? _DIR_RESTREINT_ABS : '');
	return suivre_lien($base, $url);
}

// un filtre pour transformer les URLs relatives en URLs absolues ;
// ne s'applique qu'aux textes contenant des liens
// http://doc.spip.org/@liens_absolus
function liens_absolus($texte, $base='') {
	if (preg_match_all(',(<(a|link)[[:space:]]+[^<>]*href=["\']?)([^"\' ><[:space:]]+)([^<>]*>),imsS', 
	$texte, $liens, PREG_SET_ORDER)) {
		foreach ($liens as $lien) {
			$abs = url_absolue($lien[3], $base);
			if ($abs <> $lien[3])
				$texte = str_replace($lien[0], $lien[1].$abs.$lien[4], $texte);
		}
	}
	if (preg_match_all(',(<(img|script)[[:space:]]+[^<>]*src=["\']?)([^"\' ><[:space:]]+)([^<>]*>),imsS', 
	$texte, $liens, PREG_SET_ORDER)) {
		foreach ($liens as $lien) {
			$abs = url_absolue($lien[3], $base);
			if ($abs <> $lien[3])
				$texte = str_replace($lien[0], $lien[1].$abs.$lien[4], $texte);
		}
	}
	return $texte;
}

//
// Ce filtre public va traiter les URL ou les <a href>
//
// http://doc.spip.org/@abs_url
function abs_url($texte, $base='') {
	if ($GLOBALS['mode_abs_url'] == 'url')
		return url_absolue($texte, $base);
	else
		return liens_absolus($texte, $base);
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
	return ($nb%$mod)+$add;
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
	",<$tag\b[^>]*(/>|>.*</$tag>|>),UimsS",
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
	",<${tag}\b[^>]*(/>|>.*</${tag}>|>),UimsS",
	$texte, $regs, PREG_PATTERN_ORDER))
		return $regs[0];
	else
		return array();
}


// construit une balise textarea avec la barre de raccourcis std de Spip.
// ATTENTION: cette barre injecte un script JS que le squelette doit accepter
// donc ce filtre doit IMPERATIVEMENT assurer la securite a sa place

// http://doc.spip.org/@barre_textarea
function barre_textarea($texte, $rows, $cols, $lang='') {
	static $num_textarea = 0;
	include_spip('inc/layer'); // definit browser_barre

	$texte = entites_html($texte);
	if (!$GLOBALS['browser_barre'])
		return "<textarea name='texte' rows='$rows' class='forml' cols='$cols'>$texte</textarea>";

	$num_textarea++;
	include_spip ('inc/barre');
	return afficher_barre("document.getElementById('textarea_$num_textarea')", true, $lang) .
	  "
<textarea name='texte' rows='$rows' class='forml' cols='$cols'
id='textarea_$num_textarea'
onselect='storeCaret(this);'
onclick='storeCaret(this);'
onkeyup='storeCaret(this);'
ondblclick='storeCaret(this);'>$texte</textarea>";
}

// comme in_array mais renvoie son 3e arg si le 2er arg n'est pas un tableau
// prend ' ' comme representant de vrai et '' de faux

// http://doc.spip.org/@in_any
function in_any($val, $vals, $def) {
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

// La fonction suivante ne sert plus aujourd'hui qu'aux squelettes des
// balises dynamiques, en attendant une nouvelle conception qui permettra
// d'eviter les redondances de calcul et d'ecriture qu'elle induit.
// Elle fournit la suite de Input-Hidden correspondant aux parametres de 
// l'URL donnee en argument. Ce besoin de ne devrait pas apparaitre,
// utiliser plutot generer_form_ecrire.
// http://doc.spip.org/@form_hidden
function form_hidden($action) {
	$hidden = '';
	if (false !== ($p = strpos($action, '?')))
		foreach(preg_split('/&(amp;)?/S',substr($action,$p+1)) as $c) {
			$hidden .= "\n<input name='" .
				entites_html(rawurldecode(str_replace('=', "' value='", $c))) .
				"' type='hidden' />";
	}
	return $hidden;
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
	return filtre_valeur_tableau($array,0);
}
// http://doc.spip.org/@filtre_end
function filtre_end($array) {
	return filtre_valeur_tableau($array,@count($array)-1);
}

// http://doc.spip.org/@filtre_push
function filtre_push($array, $val) {
	if($array == '' OR !array_push($array, $val)) return '';
	return $array;
}

// http://doc.spip.org/@filtre_find
function filtre_find($array, $val) {
	return ($array != '' AND in_array($val, $array));
}

//
// fonction standard de calcul de la balise #PAGINATION
// on peut la surcharger en definissant dans mes_fonctions :
// function pagination($total, $nom, $pas, $liste) {...}
//

// http://doc.spip.org/@filtre_pagination_dist
function filtre_pagination_dist($total, $nom, $position, $pas, $liste = true, $modele='', $connect='') {
	static $ancres = array();
	$bloc_ancre = "";
	
	if ($pas<1) return;

	$debut = 'debut'.$nom; // 'debut_articles'
	$ancre = 'pagination'.$nom; // #pagination_articles

	// Si le contexte ne contient pas de debut_xx, on regarde les globales
	// (de facon a permettre la pagination dans les modeles) ; c'est une
	// legere entorse au schema de base (squelette+contexte => page), mais
	// sinon il faut une usine a gaz pour passer debut_xx dans propre()...
	if ($position === NULL)
		$position = _request($debut);

	// n'afficher l'ancre qu'une fois
	if (!isset($ancres[$ancre]))
		$bloc_ancre = $ancres[$ancre] = "<a name='$ancre' id='$ancre'></a>";

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

	// Pas de pagination
	if ($pagination['nombre_pages']<=1)
		return '';

	// liste = false : on ne veut que l'ancre
	if (!$liste)
		return $bloc_ancre;

	if ($modele) $modele = '_'.$modele;
	return recuperer_fond("modeles/pagination$modele", $pagination, false, true, $connect);
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

	$path = dirname(url_absolue($css))."/"; // pour mettre sur les images

	// 1.
	$f = preg_replace(',(_rtl)?\.css$,i', '_'.$ndir.'.css', $css);
	if (@file_exists($f))
		return $f;

	// 2.
	$dir_var = sous_repertoire (_DIR_VAR, 'cache-css');
	$f = $dir_var
		. preg_replace(',.*/(.*?)(_rtl)?\.css,', '\1', $css)
		. '.' . substr(md5($css), 0,4) . '_' . $ndir . '.css';

	// la css peut etre distante (url absolue !)
	if (preg_match(",^http:,i",$css)){
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
	
	// passer les url relatives a la css d'origine en url absolues
	$contenu = preg_replace(",url\s*\(\s*['\"]?([^'\"/][^:]*)['\"]?\s*\),Uims","url($path\\1)",$contenu);
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

	$path = dirname(url_absolue($css))."/"; // pour mettre sur les images
	
	$f = basename($css,'.css');
	$f = sous_repertoire (_DIR_VAR, 'cache-css') 
		. preg_replace(",(.*?)(_rtl|_ltr)?$,","\\1-urlabs-" . substr(md5("$css-urlabs"), 0,4) . "\\2",$f) 
		. '.css';

	if ((@filemtime($f) > @filemtime($css))
	AND ($GLOBALS['var_mode'] != 'recalcul'))
		return $f;

	if (!lire_fichier($css, $contenu))
		return $css;

	// passer les url relatives a la css d'origine en url absolues
	$contenu = preg_replace(",url\s*\(\s*['\"]?([^'\"/][^:]*)['\"]?\s*\),Uims","url($path\\1)",$contenu);

	// ecrire la css
	if (!ecrire_fichier($f, $contenu))
		return $css;

	return $f;
}

// http://doc.spip.org/@compacte_css
function compacte_css ($contenu) {
	// nettoyer la css de tout ce qui sert pas
	$contenu = preg_replace(",/\*.*\*/,Ums","",$contenu); // pas de commentaires
	$contenu = preg_replace(",\s(?=\s),Ums","",$contenu); // pas d'espaces consecutifs
	$contenu = preg_replace("/\s?({|;|,|:)\s?/ms","$1",$contenu); // pas d'espaces dans les declarations css
	$contenu = preg_replace("/\s}/ms","}",$contenu); // pas d'espaces dans les declarations css
	$contenu = preg_replace(",#([0-9a-f])(\\1)([0-9a-f])(\\3)([0-9a-f])(\\5),i","#$1$3$5",$contenu); // passser les codes couleurs en 3 car si possible
	$contenu = preg_replace(",([^{}]*){},Ums"," ",$contenu); // supprimer les declarations vides
	$contenu = trim($contenu);

	return $contenu;
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
// http://doc.spip.org/@match
function match($texte, $expression, $modif="UimsS") {
	$expression=str_replace("\/","/",$expression);
	$expression=str_replace("/","\/",$expression);
	return preg_match('/' . $expression . '/' . $modif,$texte, $r)
		? ($r[0]?$r[0]:true) : false;
}

// filtre replace pour faire des operations avec expression reguliere
// [(#TEXTE|replace{^ceci$,cela,Uims})]
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
		if (!in_array($i,$ignore_params))
			$texte .= "<param name='".$i."' value='".$j."' />";
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
		if (!in_array($i,$ignore_params))
			$texte .= $i."='".$j."' ";
	return $texte;
}

// Inserer jQuery
// et au passage verifier qu'on ne doublonne pas #INSERT_HEAD
// http://doc.spip.org/@f_jQuery
function f_jQuery ($texte) {
	static $doublon=0;
	if ($doublon++) {
		include_spip('public/debug');
		$texte = affiche_erreurs_page(array(
			array("#INSERT_HEAD",_T('double_occurrence')))
		) . $texte;
	} else {
		$texte = "\n<script src=\"".generer_url_public('jquery.js')
			. "\" type=\"text/javascript\"></script>\n".$texte;
	}
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

// Compacte du javascript grace a Dean Edward's JavaScriptPacker
// utile pour dist/jquery.js par exemple
// http://doc.spip.org/@compacte_js
function compacte_js($flux) {
	if (!strlen($flux))
		return $flux;
	include_spip('lib/JavaScriptPacker/class.JavaScriptPacker');
	$packer = new JavaScriptPacker($flux, 0, true, false);

	// en cas d'echec (?) renvoyer l'original
	if (strlen($t = $packer->pack()))
		return $t;

	// erreur
	spip_log('erreur de compacte_js');
	return $flux;
}

// Si la source est un chemin, on retourne un chemin avec le contenu compacte
// dans _DIR_VAR/cache_$format/
// Si c'est un flux on le renvoit compacte
// Si on ne sait pas compacter, on renvoie ce qu'on a recu
// http://doc.spip.org/@compacte
function compacte($source, $format = null) {
	if (!$format AND preg_match(',\.(js|css)$,', $source, $r))
		$format = $r[1];
	if (!function_exists($compacte = 'compacte_'.$format))
		return $source;

	// Si on n'importe pas, est-ce un fichier ?
	if (!preg_match(',[\s{}],', $source)
	AND preg_match(',\.'.$format.'$,i', $source, $r)
	AND file_exists($source)) {
		$f = basename($source,'.'.$format);
		$f = sous_repertoire (_DIR_VAR, 'cache-'.$format) 
		. preg_replace(",(.*?)(_rtl|_ltr)?$,","\\1-compacte-"
		. substr(md5("$source-compacte"), 0,4) . "\\2", $f, 1)
		. '.' . $format;

		if ((@filemtime($f) > @filemtime($source))
		AND ($GLOBALS['var_mode'] != 'recalcul'))
			return $f;

		if (!lire_fichier($source, $contenu))
			return $source;

		// traiter le contenu
		$contenu = $compacte($contenu);

		// ecrire le fichier destination, en cas d'echec renvoyer la source
		if (ecrire_fichier($f, $contenu))
			return $f;
		else
			return $source;
	}

	// Sinon simple compactage de contenu
	return $compacte($source);
}


// clone de http://php.net/var_export compatible < 4.2.0 et sans ob_xx
// http://doc.spip.org/@spip_var_export
function spip_var_export($s) {
	if (is_array($s)) {
		foreach ($s as $k=>$v)
			$s[$k] = spip_var_export($k) . ' => ' . spip_var_export($v);
		return 'array(' . join(',',$s).')';
	}

	return is_null($s)
		? 'null'
		: (is_bool($s)
			? ($s ? 'true' : 'false')
			: (is_numeric($s)
				? "$s"
				: (is_string($s)
					? "'".addslashes($s)."'"
					: "'".gettype($s)."'"
				)
			)
		);
}

// produit une balise img avec un champ alt d'office si vide
// attention le htmlentities et la traduction doivent etre appliques avant.

// http://doc.spip.org/@http_wrapper
function http_wrapper($img){
	static $wrapper_state=NULL;
	static $wrapper_table = array();
	
	if (strpos($img,'/')===FALSE) // on ne prefixe par _DIR_IMG_PACK que si c'est un nom de fichier sans chemin
		$f = _DIR_IMG_PACK . $img;
	else { // sinon, le path a ete fourni
		$f = $img;
		// gerer quand meme le cas des hacks pre 1.9.2 ou l'on faisait un path relatif depuis img_pack
		if (substr($f,0,strlen("../"._DIR_PLUGINS))=="../"._DIR_PLUGINS)
			$f = substr($img,3); // on enleve le ../ qui ne faisait que ramener au rep courant
	}
	
	if ($wrapper_state==NULL){
		global $browser_name;
		if (!strlen($browser_name)){include_spip('inc/layer');}
		$wrapper_state = ($browser_name=="MSIE");
	}
	if ($wrapper_state){
		if (!isset($wrapper_table[$d=dirname($f)])) {
			$wrapper_table[$d] = false;
			if (file_exists("$d/wrapper.php"))
				$wrapper_table[$d] = "$d/wrapper.php?file=";
		}
		if ($wrapper_table[$d])
			$f = $wrapper_table[$d] . urlencode(basename($img));
	}
	return $f;
}
// http://doc.spip.org/@http_img_pack
function http_img_pack($img, $alt, $atts='', $title='') {

	return  "<img src='" . http_wrapper($img)
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
		foreach($balise_deserializee as $k => $v)
			$texte .= recuperer_fond(
				'modeles/'.$modele,
				array_merge(array('cle' => $k), (is_array($v) ? $v : array('valeur' => $v)))
			);
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

	if(!$plugin)
		return serialize(array_keys($plugins_actifs));
	if(!empty($plugins_actifs[$plugin]))
		if($type_info == 'est_actif')
			return $plugins_actifs[$plugin] ? 1 : 0;
		else
			return $plugins_actifs[$plugin][$type_info];
}

?>