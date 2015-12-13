<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2015                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Déclaration de filtres pour les squelettes.
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
    return;
}

include_spip('inc/charsets');
include_spip('inc/filtres_mini');
include_spip('inc/filtres_selecteur_generique');
include_spip('base/objets');
include_spip('public/parametrer'); // charger les fichiers fonctions

/**
 * Charger un filtre depuis le php.
 *
 * - on inclue tous les fichiers fonctions des plugins et du skel
 * - on appelle chercher_filtre
 *
 * Pour éviter de perdre le texte si le filtre demandé est introuvable,
 * on transmet `filtre_identite_dist` en filtre par défaut.
 *
 * @uses filtre_identite_dist() Comme fonction par défaut
 *
 * @param string $fonc    Nom du filtre
 * @param string $default Filtre par défaut
 *
 * @return string Fonction PHP correspondante du filtre
 */
function charger_filtre($fonc, $default = 'filtre_identite_dist')
{
    include_spip('public/parametrer'); // inclure les fichiers fonctions
    return chercher_filtre($fonc, $default);
}

/**
 * Retourne le texte tel quel.
 *
 * @param string $texte Texte
 *
 * @return string Texte
 **/
function filtre_identite_dist($texte)
{
    return $texte;
}

/**
 * Cherche un filtre.
 *
 * Pour une filtre `F` retourne la première fonction trouvée parmis :
 *
 * - filtre_F
 * - filtre_F_dist
 * - F
 *
 * Peut gérer des appels par des fonctions statiques de classes tel que `Foo::Bar`
 *
 * En absence de fonction trouvée, retourne la fonction par défaut indiquée.
 *
 * @param string $fonc
 *                        Nom du filtre
 * @param null   $default
 *                        Nom du filtre appliqué par défaut si celui demandé n'est pas trouvé
 *
 * @return string
 *                Fonction PHP correspondante du filtre demandé
 */
function chercher_filtre($fonc, $default = null)
{
    if (!$fonc) {
        return $default;
    }
    // Cas des types mime, sans confondre avec les appels de fonction de classe
    // Foo::Bar
    // qui peuvent etre avec un namespace : space\Foo::Bar
    if (preg_match(',^[\w]+/,', $fonc)) {
        $nom = preg_replace(',\W,', '_', $fonc);
        $f = chercher_filtre($nom);
        // cas du sous-type MIME sans filtre associe, passer au type:
        // si filtre_text_plain pas defini, passe a filtre_text
        if (!$f and $nom !== $fonc) {
            $f = chercher_filtre(preg_replace(',\W.*$,', '', $fonc));
        }

        return $f;
    }
    foreach (
        array('filtre_'.$fonc, 'filtre_'.$fonc.'_dist', $fonc) as $f) {
        if (isset($GLOBALS['spip_matrice'][$f]) and is_string($g = $GLOBALS['spip_matrice'][$f])) {
            find_in_path($g, '', true);
        }
        if (function_exists($f)
            or (preg_match("/^(\w*)::(\w*)$/", $f, $regs)
                and is_callable(array($regs[1], $regs[2]))
            )
        ) {
            return $f;
        }
    }

    return $default;
}

/**
 * Applique un filtre.
 *
 * Fonction générique qui prend en argument l’objet (texte, etc) à modifier
 * et le nom du filtre. Retrouve les arguments du filtre demandé dans les arguments
 * transmis à cette fonction, via func_get_args().
 *
 * @see filtrer() Assez proche
 *
 * @param string $arg
 *                       Texte sur lequel appliquer le filtre
 * @param string $filtre
 *                       Nom du filtre a appliquer
 * @param string $force
 *                       La fonction doit-elle retourner le texte ou rien ?
 *
 * @return string
 *                Texte avec le filtre appliqué s'il a été trouvé,
 *                Texte sans le filtre appliqué s'il n'a pas été trouvé et que $force n'a
 *                pas été fourni,
 *                Chaîne vide si le filtre n'a pas été trouvé et que $force a été fourni.
 **/
function appliquer_filtre($arg, $filtre, $force = null)
{
    $f = chercher_filtre($filtre);
    if (!$f) {
        if (!$force) {
            return '';
        } else {
            return $arg;
        }
    }

    $args = func_get_args();
    array_shift($args); // enlever $arg
    array_shift($args); // enlever $filtre
    array_unshift($args, $arg); // remettre $arg
    return call_user_func_array($f, $args);
}

/**
 * Retourne la version de SPIP.
 *
 * Si l'on retrouve un numéro de révision SVN, il est ajouté entre crochets.
 * Si effectivement le SPIP est installé par SVN, 'SVN' est ajouté avant sa révision.
 *
 * @global spip_version_affichee Contient la version de SPIP
 *
 * @uses version_svn_courante() Pour trouver le numéro de révision SVN
 *
 * @return string
 *                Version de SPIP
 **/
function spip_version()
{
    $version = $GLOBALS['spip_version_affichee'];
    if ($svn_revision = version_svn_courante(_DIR_RACINE)) {
        $version .= ($svn_revision < 0 ? ' SVN' : '').' ['.abs($svn_revision).']';
    }

    return $version;
}

/**
 * Retrouve un numéro de révision SVN d'un répertoire.
 *
 * Mention de la révision SVN courante d'un répertoire
 * Retourne un nombre négatif si on est sur .svn, et positif si on utilise svn.revision
 *
 * @param string $dir Chemin du répertoire
 *
 * @return int
 *
 *     - 0 si aucune info trouvée
 *     - NN (entier) si info trouvée par svn.revision (créé par le générateur de paquet Zip)
 *     - -NN (entier) si info trouvée par .svn/entries
 **/
function version_svn_courante($dir)
{
    if (!$dir) {
        $dir = '.';
    }

    // version installee par paquet ZIP
    if (lire_fichier($dir.'/svn.revision', $c)
        and preg_match(',Revision: (\d+),', $c, $d)
    ) {
        return intval($d[1]);
    }

    // version installee par SVN
    if (lire_fichier($dir.'/.svn/entries', $c)
        and (
            (preg_match_all(
                    ',committed-rev="([0-9]+)",', $c, $r1, PREG_PATTERN_ORDER)
                and $v = max($r1[1])
            )
            or
            (preg_match(',^\d.*dir[\r\n]+(\d+),ms', $c, $r1) # svn >= 1.4
                and $v = $r1[1]
            ))
    ) {
        return -$v;
    }

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

/**
 * Charge et exécute un filtre (graphique ou non).
 *
 * Recherche la fonction prévue pour un filtre (qui peut être un filtre graphique `image_*`)
 * et l'exécute avec les arguments transmis à la fonction, obtenus avec `func_get_args()`
 *
 * @api
 *
 * @uses image_filtrer() Pour un filtre image
 * @uses chercher_filtre() Pour un autre filtre
 *
 * @param string $filtre
 *                       Nom du filtre à appliquer
 *
 * @return string
 *                Code HTML retourné par le filtre
 **/
function filtrer($filtre)
{
    if (isset($GLOBALS['spip_matrice'][$filtre]) and is_string($f = $GLOBALS['spip_matrice'][$filtre])) {
        find_in_path($f, '', true);
        $GLOBALS['spip_matrice'][$filtre] = true;
    }
    $tous = func_get_args();
    if (substr($filtre, 0, 6) == 'image_' && $GLOBALS['spip_matrice'][$filtre]) {
        return image_filtrer($tous);
    } elseif ($f = chercher_filtre($filtre)) {
        array_shift($tous);

        return call_user_func_array($f, $tous);
    } else {
        // le filtre n'existe pas, on provoque une erreur
        $msg = array('zbug_erreur_filtre', array('filtre' => texte_script($filtre)));
        erreur_squelette($msg);

        return '';
    }
}

/**
 * Filtre `set` qui sauve la valeur en entrée dans une variable.
 *
 * La valeur pourra être retrouvée avec `#GET{variable}`.
 *
 * @example
 *     `[(#CALCUL|set{toto})]` enregistre le résultat de `#CALCUL`
 *     dans la variable `toto` et renvoie vide.
 *     C'est équivalent à `[(#SET{toto, #CALCUL})]` dans ce cas.
 *     `#GET{toto}` retourne la valeur sauvegardée.
 * @example
 *     `[(#CALCUL|set{toto,1})]` enregistre le résultat de `#CALCUL`
 *      dans la variable toto et renvoie la valeur. Cela permet d'utiliser
 *      d'autres filtres ensuite. `#GET{toto}` retourne la valeur.
 *
 * @filtre
 *
 * @param array  $Pile     Pile de données
 * @param mixed  $val      Valeur à sauver
 * @param string $key      Clé d'enregistrement
 * @param bool   $continue True pour retourner la valeur
 *
 * @return mixed
 */
function filtre_set(&$Pile, $val, $key, $continue = null)
{
    $Pile['vars'][$key] = $val;

    return $continue ? $val : '';
}

/**
 * Filtre `debug` qui affiche un debug de la valeur en entrée.
 *
 * Log la valeur dans `debug.log` et l'affiche si on est webmestre.
 *
 * @example
 *     `[(#TRUC|debug)]` affiche et log la valeur de `#TRUC`
 * @example
 *     `[(#TRUC|debug{avant}|calcul|debug{apres}|etc)]`
 *     affiche la valeur de `#TRUC` avant et après le calcul,
 *     en précisant "avant" et "apres".
 *
 * @filtre
 *
 * @link http://www.spip.net/5695
 *
 * @param mixed      $val La valeur à debugguer
 * @param mixed|null $key Clé pour s'y retrouver
 *
 * @return mixed Retourne la valeur (sans la modifier).
 */
function filtre_debug($val, $key = null)
{
    $debug = (
        is_null($key) ? '' : (var_export($key, true).' = ')
        ).var_export($val, true);

    include_spip('inc/autoriser');
    if (autoriser('webmestre')) {
        echo "<div class='spip_debug'>\n", $debug, "</div>\n";
    }

    spip_log($debug, 'debug');

    return $val;
}

/**
 * Exécute un filtre image.
 *
 * Fonction générique d'entrée des filtres images.
 * Accepte en entrée :
 *
 * - un texte complet,
 * - un img-log (produit par #LOGO_XX),
 * - un tag `<img ...>` complet,
 * - un nom de fichier *local* (passer le filtre `|copie_locale` si on veut
 *   l'appliquer à un document distant).
 *
 * Applique le filtre demande à chacune des occurrences
 *
 * @param array $args
 *                    Liste des arguments :
 *
 *     - le premier est le nom du filtre image à appliquer
 *     - le second est le texte sur lequel on applique le filtre
 *     - les suivants sont les arguments du filtre image souhaité.
 *
 * @return string
 *                Texte qui a reçu les filtres
 **/
function image_filtrer($args)
{
    $filtre = array_shift($args); # enlever $filtre
    $texte = array_shift($args);
    if (!strlen($texte)) {
        return;
    }
    find_in_path('filtres_images_mini.php', 'inc/', true);
    statut_effacer_images_temporaires(true); // activer la suppression des images temporaires car le compilo finit la chaine par un image_graver
    // Cas du nom de fichier local
    if (strpos(substr($texte, strlen(_DIR_RACINE)), '..') === false
        and !preg_match(',^/|[<>]|\s,S', $texte)
        and (
            file_exists(preg_replace(',[?].*$,', '', $texte))
            or tester_url_absolue($texte)
        )
    ) {
        array_unshift($args, "<img src='$texte' />");
        $res = call_user_func_array($filtre, $args);
        statut_effacer_images_temporaires(false); // desactiver pour les appels hors compilo
        return $res;
    }

    // Cas general : trier toutes les images, avec eventuellement leur <span>
    if (preg_match_all(
        ',(<([a-z]+) [^<>]*spip_documents[^<>]*>)?\s*(<img\s.*>),UimsS',
        $texte, $tags, PREG_SET_ORDER)) {
        foreach ($tags as $tag) {
            $class = extraire_attribut($tag[3], 'class');
            if (!$class or
                (strpos($class, 'filtre_inactif') == false
                    // compat historique a virer en 3.2
                    and strpos($class, 'no_image_filtrer') === false)
            ) {
                array_unshift($args, $tag[3]);
                if ($reduit = call_user_func_array($filtre, $args)) {
                    // En cas de span spip_documents, modifier le style=...width:
                    if ($tag[1]) {
                        $w = extraire_attribut($reduit, 'width');
                        if (!$w and preg_match(",width:\s*(\d+)px,S", extraire_attribut($reduit, 'style'), $regs)) {
                            $w = $regs[1];
                        }
                        if ($w and ($style = extraire_attribut($tag[1], 'style'))) {
                            $style = preg_replace(",width:\s*\d+px,S", "width:${w}px", $style);
                            $replace = inserer_attribut($tag[1], 'style', $style);
                            $texte = str_replace($tag[1], $replace, $texte);
                        }
                    }
                    // traiter aussi un eventuel mouseover
                    if ($mouseover = extraire_attribut($reduit, 'onmouseover')) {
                        if (preg_match(",this[.]src=['\"]([^'\"]+)['\"],ims", $mouseover, $match)) {
                            $srcover = $match[1];
                            array_shift($args);
                            array_unshift($args, "<img src='".$match[1]."' />");
                            $srcover_filter = call_user_func_array($filtre, $args);
                            $srcover_filter = extraire_attribut($srcover_filter, 'src');
                            $reduit = str_replace($srcover, $srcover_filter, $reduit);
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

/**
 * Retourne les tailles d'une image.
 *
 * Pour les filtres `largeur` et `hauteur`
 *
 * @param string $img
 *                    Balise HTML `<img ... />` ou chemin de l'image (qui peut être une URL distante).
 *
 * @return array
 *               Liste (hauteur, largeur) en pixels
 **/
function taille_image($img)
{
    static $largeur_img = array(), $hauteur_img = array();
    $srcWidth = 0;
    $srcHeight = 0;

    $logo = extraire_attribut($img, 'src');

    if (!$logo) {
        $logo = $img;
    } else {
        $srcWidth = extraire_attribut($img, 'width');
        $srcHeight = extraire_attribut($img, 'height');
    }

    // ne jamais operer directement sur une image distante pour des raisons de perfo
    // la copie locale a toutes les chances d'etre la ou de resservir
    if (tester_url_absolue($logo)) {
        include_spip('inc/distant');
        $fichier = copie_locale($logo);
        $logo = $fichier ? _DIR_RACINE.$fichier : $logo;
    }
    if (($p = strpos($logo, '?')) !== false) {
        $logo = substr($logo, 0, $p);
    }

    $srcsize = false;
    if (isset($largeur_img[$logo])) {
        $srcWidth = $largeur_img[$logo];
    }
    if (isset($hauteur_img[$logo])) {
        $srcHeight = $hauteur_img[$logo];
    }
    if (!$srcWidth or !$srcHeight) {
        if (file_exists($logo)
            and $srcsize = @getimagesize($logo)
        ) {
            if (!$srcWidth) {
                $largeur_img[$logo] = $srcWidth = $srcsize[0];
            }
            if (!$srcHeight) {
                $hauteur_img[$logo] = $srcHeight = $srcsize[1];
            }
        }
        // $logo peut etre une reference a une image temporaire dont a n'a que le log .src
        // on s'y refere, l'image sera reconstruite en temps utile si necessaire
        elseif (@file_exists($f = "$logo.src")
            and lire_fichier($f, $valeurs)
            and $valeurs = unserialize($valeurs)
        ) {
            if (!$srcWidth) {
                $largeur_img[$logo] = $srcWidth = $valeurs['largeur_dest'];
            }
            if (!$srcHeight) {
                $hauteur_img[$logo] = $srcHeight = $valeurs['hauteur_dest'];
            }
        }
    }

    return array($srcHeight, $srcWidth);
}

/**
 * Retourne la largeur d'une image.
 *
 * @filtre
 *
 * @link http://www.spip.net/4296
 *
 * @uses taille_image()
 *
 * @see  hauteur()
 *
 * @param string $img
 *                    Balise HTML `<img ... />` ou chemin de l'image (qui peut être une URL distante).
 *
 * @return int|null
 *                  Largeur en pixels, NULL ou 0 si aucune image.
 **/
function largeur($img)
{
    if (!$img) {
        return;
    }
    list($h, $l) = taille_image($img);

    return $l;
}

/**
 * Retourne la hauteur d'une image.
 *
 * @filtre
 *
 * @link http://www.spip.net/4291
 *
 * @uses taille_image()
 *
 * @see  largeur()
 *
 * @param string $img
 *                    Balise HTML `<img ... />` ou chemin de l'image (qui peut être une URL distante).
 *
 * @return int|null
 *                  Hauteur en pixels, NULL ou 0 si aucune image.
 **/
function hauteur($img)
{
    if (!$img) {
        return;
    }
    list($h, $l) = taille_image($img);

    return $h;
}

/**
 * Échappement des entités HTML avec correction des entités « brutes ».
 *
 * Ces entités peuvent être générées par les butineurs lorsqu'on rentre des
 * caractères n'appartenant pas au charset de la page [iso-8859-1 par défaut]
 *
 * Attention on limite cette correction aux caracteres « hauts » (en fait > 99
 * pour aller plus vite que le > 127 qui serait logique), de manière à
 * préserver des eéhappements de caractères « bas » (par exemple `[` ou `"`)
 * et au cas particulier de `&amp;` qui devient `&amp;amp;` dans les URL
 *
 * @see corriger_toutes_entites_html()
 *
 * @param string $texte
 *
 * @return string
 **/
function corriger_entites_html($texte)
{
    if (strpos($texte, '&amp;') === false) {
        return $texte;
    }

    return preg_replace(',&amp;(#[0-9][0-9][0-9]+;|amp;),iS', '&\1', $texte);
}

/**
 * Échappement des entités HTML avec correction des entités « brutes » ainsi
 * que les `&amp;eacute;` en `&eacute;`.
 *
 * Identique à `corriger_entites_html()` en corrigeant aussi les
 * `&amp;eacute;` en `&eacute;`
 *
 * @see corriger_entites_html()
 *
 * @param string $texte
 *
 * @return string
 **/
function corriger_toutes_entites_html($texte)
{
    if (strpos($texte, '&amp;') === false) {
        return $texte;
    }

    return preg_replace(',&amp;(#?[a-z0-9]+;),iS', '&\1', $texte);
}

/**
 * Échappe les `&` en `&amp;`.
 *
 * @param string $texte
 *
 * @return string
 **/
function proteger_amp($texte)
{
    return str_replace('&', '&amp;', $texte);
}

/**
 * Échappe en entités HTML certains caractères d'un texte.
 *
 * Traduira un code HTML en transformant en entités HTML les caractères
 * en dehors du charset de la page ainsi que les `"`, `<` et `>`.
 *
 * Ceci permet d’insérer le texte d’une balise dans un `<textarea> </textarea>`
 * sans dommages.
 *
 * @filtre
 *
 * @link http://www.spip.net/4280
 *
 * @uses echappe_html()
 * @uses echappe_retour()
 * @uses proteger_amp()
 * @uses corriger_entites_html()
 * @uses corriger_toutes_entites_html()
 *
 * @param string $texte
 *                      chaine a echapper
 * @param bool   $tout
 *                      corriger toutes les `&amp;xx;` en `&xx;`
 * @param bool   $quote
 *                      Échapper aussi les simples quotes en `&#039;`
 *
 * @return mixed|string
 */
function entites_html($texte, $tout = false, $quote = true)
{
    if (!is_string($texte) or !$texte
        or strpbrk($texte, "&\"'<>") == false
    ) {
        return $texte;
    }
    include_spip('inc/texte');
    $flags = ($quote ? ENT_QUOTES : ENT_NOQUOTES);
    if (defined('ENT_HTML401')) {
        $flags |= ENT_HTML401;
    }
    $texte = spip_htmlspecialchars(echappe_retour(echappe_html($texte, '', true), '', 'proteger_amp'), $flags);
    if ($tout) {
        return corriger_toutes_entites_html($texte);
    } else {
        return corriger_entites_html($texte);
    }
}

/**
 * Convertit les caractères spéciaux HTML dans le charset du site.
 *
 * @exemple
 *     Si le charset de votre site est `utf-8`, `&eacute;` ou `&#233;`
 *     sera transformé en `é`
 *
 * @filtre
 *
 * @link http://www.spip.net/5513
 *
 * @param string $texte
 *                      Texte à convertir
 *
 * @return string
 *                Texte converti
 **/
function filtrer_entites($texte)
{
    if (strpos($texte, '&') === false) {
        return $texte;
    }
    // filtrer
    $texte = html2unicode($texte);
    // remettre le tout dans le charset cible
    $texte = unicode2charset($texte);
    // cas particulier des " et ' qu'il faut filtrer aussi
    // (on le faisait deja avec un &quot;)
    if (strpos($texte, '&#') !== false) {
        $texte = str_replace(array('&#039;', '&#39;', '&#034;', '&#34;'), array("'", "'", '"', '"'), $texte);
    }

    return $texte;
}

/**
 * Supprime des caractères illégaux.
 *
 * Remplace les caractères de controle par le caractère `-`
 *
 * @link http://www.w3.org/TR/REC-xml/#charsets
 *
 * @param string|array $texte
 *
 * @return string|array
 **/
function supprimer_caracteres_illegaux($texte)
{
    static $from = "\x0\x1\x2\x3\x4\x5\x6\x7\x8\xB\xC\xE\xF\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F";
    static $to = null;

    if (is_array($texte)) {
        return array_map('supprimer_caracteres_illegaux', $texte);
    }

    if (!$to) {
        $to = str_repeat('-', strlen($from));
    }

    return strtr($texte, $from, $to);
}

/**
 * Correction de caractères.
 *
 * Supprimer les caracteres windows non conformes et les caracteres de controle illégaux
 *
 * @param string|array $texte
 *
 * @return string|array
 **/
function corriger_caracteres($texte)
{
    $texte = corriger_caracteres_windows($texte);
    $texte = supprimer_caracteres_illegaux($texte);

    return $texte;
}

/**
 * Encode du HTML pour transmission XML notamment dans les flux RSS.
 *
 * Ce filtre transforme les liens en liens absolus, importe les entitées html et échappe les tags html.
 *
 * @filtre
 *
 * @link http://www.spip.net/4287
 *
 * @param string $texte
 *                      Texte à transformer
 *
 * @return string
 *                Texte encodé pour XML
 */
function texte_backend($texte)
{
    static $apostrophe = array('&#8217;', "'"); # n'allouer qu'une fois

    // si on a des liens ou des images, les passer en absolu
    $texte = liens_absolus($texte);

    // echapper les tags &gt; &lt;
    $texte = preg_replace(',&(gt|lt);,S', '&amp;\1;', $texte);

    // importer les &eacute;
    $texte = filtrer_entites($texte);

    // " -> &quot; et tout ce genre de choses
    $u = $GLOBALS['meta']['pcre_u'];
    $texte = str_replace('&nbsp;', ' ', $texte);
    $texte = preg_replace('/\s{2,}/S'.$u, ' ', $texte);
    // ne pas echapper les sinqle quotes car certains outils de syndication gerent mal
    $texte = entites_html($texte, false, false);
    // mais bien echapper les double quotes !
    $texte = str_replace('"', '&#034;', $texte);

    // verifier le charset
    $texte = charset2unicode($texte);

    // Caracteres problematiques en iso-latin 1
    if (isset($GLOBALS['meta']['charset']) and $GLOBALS['meta']['charset'] == 'iso-8859-1') {
        $texte = str_replace(chr(156), '&#156;', $texte);
        $texte = str_replace(chr(140), '&#140;', $texte);
        $texte = str_replace(chr(159), '&#159;', $texte);
    }

    // l'apostrophe curly pose probleme a certains lecteure de RSS
    // et le caractere apostrophe alourdit les squelettes avec PHP
    // ==> on les remplace par l'entite HTML
    return str_replace($apostrophe, "'", $texte);
}

/**
 * Encode et quote du HTML pour transmission XML notamment dans les flux RSS.
 *
 * Comme texte_backend(), mais avec addslashes final pour squelettes avec PHP (rss)
 *
 * @uses texte_backend()
 * @filtre
 *
 * @param string $texte
 *                      Texte à transformer
 *
 * @return string
 *                Texte encodé et quote pour XML
 */
function texte_backendq($texte)
{
    return addslashes(texte_backend($texte));
}

/**
 * Enlève un numéro préfixant un texte.
 *
 * Supprime `10. ` dans la chaine `10. Titre`
 *
 * @filtre
 *
 * @link http://www.spip.net/4314
 * @see recuperer_numero() Pour obtenir le numéro
 *
 * @example
 *     ```
 *     [<h1>(#TITRE|supprimer_numero)</h1>]
 *     ```
 *
 * @param string $texte
 *                      Texte
 *
 * @return int|string
 *                    Numéro de titre, sinon chaîne vide
 **/
function supprimer_numero($texte)
{
    return preg_replace(
        ',^[[:space:]]*([0-9]+)([.)]|'.chr(194).'?'.chr(176).')[[:space:]]+,S',
        '', $texte);
}

/**
 * Récupère un numéro préfixant un texte.
 *
 * Récupère le numéro `10` dans la chaine `10. Titre`
 *
 * @filtre
 *
 * @link http://www.spip.net/5514
 * @see supprimer_numero() Pour supprimer le numéro
 * @see balise_RANG_dist() Pour obtenir un numéro de titre
 *
 * @example
 *     ```
 *     [(#TITRE|recuperer_numero)]
 *     ```
 *
 * @param string $texte
 *                      Texte
 *
 * @return int|string
 *                    Numéro de titre, sinon chaîne vide
 **/
function recuperer_numero($texte)
{
    if (preg_match(
        ',^[[:space:]]*([0-9]+)([.)]|'.chr(194).'?'.chr(176).')[[:space:]]+,S',
        $texte, $regs)) {
        return strval($regs[1]);
    } else {
        return '';
    }
}

/**
 * Suppression basique et brutale de tous les tags.
 *
 * Supprime tous les tags `<...>`.
 * Utilisé fréquemment pour écrire des RSS.
 *
 * @filtre
 *
 * @link http://www.spip.net/4315
 *
 * @example
 *     ```
 *     <title>[(#TITRE|supprimer_tags|texte_backend)]</title>
 *     ```
 *
 * @note
 *     Ce filtre supprime aussi les signes inférieurs `<` rencontrés.
 *
 * @param string $texte
 *                      Texte à échapper
 * @param string $rempl
 *                      Inutilisé.
 *
 * @return string
 *                Texte converti
 **/
function supprimer_tags($texte, $rempl = '')
{
    $texte = preg_replace(",<(!--|\w|/)[^>]*>,US", $rempl, $texte);
    // ne pas oublier un < final non ferme car coupe
    $texte = preg_replace(",<(!--|\w|/).*$,US", $rempl, $texte);
    // mais qui peut aussi etre un simple signe plus petit que
    $texte = str_replace('<', '&lt;', $texte);

    return $texte;
}

/**
 * Convertit les chevrons de tag en version lisible en HTML.
 *
 * Transforme les chevrons de tag `<...>` en entité HTML.
 *
 * @filtre
 *
 * @link http://www.spip.net/5515
 *
 * @example
 *     ```
 *     <pre>[(#TEXTE|echapper_tags)]</pre>
 *     ```
 *
 * @param string $texte
 *                      Texte à échapper
 * @param string $rempl
 *                      Inutilisé.
 *
 * @return string
 *                Texte converti
 **/
function echapper_tags($texte, $rempl = '')
{
    $texte = preg_replace('/<([^>]*)>/', '&lt;\\1&gt;', $texte);

    return $texte;
}

/**
 * Convertit un texte HTML en texte brut.
 *
 * Enlève les tags d'un code HTML, élimine les doubles espaces.
 *
 * @filtre
 *
 * @link http://www.spip.net/4317
 *
 * @example
 *     ```
 *     <title>[(#TITRE|textebrut) - ][(#NOM_SITE_SPIP|textebrut)]</title>
 *     ```
 *
 * @param string $texte
 *                      Texte à convertir
 *
 * @return string
 *                Texte converti
 **/
function textebrut($texte)
{
    $u = $GLOBALS['meta']['pcre_u'];
    $texte = preg_replace('/\s+/S'.$u, ' ', $texte);
    $texte = preg_replace('/<(p|br)( [^>]*)?'.'>/iS', "\n\n", $texte);
    $texte = preg_replace("/^\n+/", '', $texte);
    $texte = preg_replace("/\n+$/", '', $texte);
    $texte = preg_replace("/\n +/", "\n", $texte);
    $texte = supprimer_tags($texte);
    $texte = preg_replace('/(&nbsp;| )+/S', ' ', $texte);
    // nettoyer l'apostrophe curly qui pose probleme a certains rss-readers, lecteurs de mail...
    $texte = str_replace('&#8217;', "'", $texte);

    return $texte;
}

/**
 * Remplace les liens SPIP en liens ouvrant dans une nouvelle fenetre (target=blank).
 *
 * @filtre
 *
 * @link http://www.spip.net/4297
 *
 * @param string $texte
 *                      Texte avec des liens
 *
 * @return string
 *                Texte avec liens ouvrants
 **/
function liens_ouvrants($texte)
{
    return preg_replace(",<a\s+([^>]*https?://[^>]*class=[\"']spip_(out|url)\b[^>]+)>,",
        '<a \\1 target="_blank">', $texte);
}

/**
 * Ajouter un attribut rel="nofollow" sur tous les liens d'un texte.
 *
 * @param string $texte
 *
 * @return string
 */
function liens_nofollow($texte)
{
    if (stripos($texte, '<a') === false) {
        return $texte;
    }

    if (preg_match_all(",<a\b[^>]*>,UimsS", $texte, $regs, PREG_PATTERN_ORDER)) {
        foreach ($regs[0] as $a) {
            $rel = extraire_attribut($a, 'rel');
            if (strpos($rel, 'nofollow') === false) {
                $rel = 'nofollow'.($rel ? " $rel" : '');
                $anofollow = inserer_attribut($a, 'rel', $rel);
                $texte = str_replace($a, $anofollow, $texte);
            }
        }
    }

    return $texte;
}

/**
 * Transforme les sauts de paragraphe HTML `p` en simples passages à la ligne `br`.
 *
 * @filtre
 *
 * @link http://www.spip.net/4308
 *
 * @example
 *     ```
 *     [<div>(#DESCRIPTIF|PtoBR)[(#NOTES|PtoBR)]</div>]
 *     ```
 *
 * @param string $texte
 *                      Texte à transformer
 *
 * @return string
 *                Texte sans paraghaphes
 **/
function PtoBR($texte)
{
    $u = $GLOBALS['meta']['pcre_u'];
    $texte = preg_replace('@</p>@iS', "\n", $texte);
    $texte = preg_replace("@<p\b.*>@UiS", '<br />', $texte);
    $texte = preg_replace("@^\s*<br />@S".$u, '', $texte);

    return $texte;
}

/**
 * Assure qu'un texte ne vas pas déborder d'un bloc
 * par la faute d'un mot trop long (souvent des URLs).
 *
 * Ne devrait plus être utilisé et fait directement en CSS par un style
 * `word-wrap:break-word;`
 *
 * @note
 *   Pour assurer la compatibilité du filtre, on encapsule le contenu par
 *   un `div` ou `span` portant ce style CSS inline.
 *
 * @filtre
 *
 * @link http://www.spip.net/4298
 * @link http://www.alsacreations.com/tuto/lire/1038-gerer-debordement-contenu-css.html
 * @deprecated Utiliser le style CSS `word-wrap:break-word;`
 *
 * @param string $texte Texte
 *
 * @return string Texte encadré du style CSS
 */
function lignes_longues($texte)
{
    if (!strlen(trim($texte))) {
        return $texte;
    }
    include_spip('inc/texte');
    $tag = preg_match(',</?('._BALISES_BLOCS.')[>[:space:]],iS', $texte) ?
        'div' : 'span';

    return "<$tag style='word-wrap:break-word;'>$texte</$tag>";
}

/**
 * Passe un texte en majuscules, y compris les accents, en HTML.
 *
 * Encadre le texte du style CSS `text-transform: uppercase;`.
 * Le cas spécifique du i turc est géré.
 *
 * @filtre
 *
 * @example
 *     ```
 *     [(#EXTENSION|majuscules)]
 *     ```
 *
 * @param string $texte Texte
 *
 * @return string Texte en majuscule
 */
function majuscules($texte)
{
    if (!strlen($texte)) {
        return '';
    }

    // Cas du turc
    if ($GLOBALS['spip_lang'] == 'tr') {
        # remplacer hors des tags et des entites
        if (preg_match_all(',<[^<>]+>|&[^;]+;,S', $texte, $regs, PREG_SET_ORDER)) {
            foreach ($regs as $n => $match) {
                $texte = str_replace($match[0], "@@SPIP_TURC$n@@", $texte);
            }
        }

        $texte = str_replace('i', '&#304;', $texte);

        if ($regs) {
            foreach ($regs as $n => $match) {
                $texte = str_replace("@@SPIP_TURC$n@@", $match[0], $texte);
            }
        }
    }

    // Cas general
    return "<span style='text-transform: uppercase;'>$texte</span>";
}

/**
 * Retourne une taille en octets humainement lisible.
 *
 * Tel que "127.4 ko" ou "3.1 Mo"
 *
 * @example
 *     - `[(#TAILLE|taille_en_octets)]`
 *     - `[(#VAL{123456789}|taille_en_octets)]` affiche `117.7 Mo`
 *
 * @filtre
 *
 * @link http://www.spip.net/4316
 *
 * @param int $taille
 *
 * @return string
 **/
function taille_en_octets($taille)
{
    if (!defined('_KILOBYTE')) {
        /*
         * Définit le nombre d'octets dans un Kilobyte
         *
         * @var int
         **/
        define('_KILOBYTE', 1024);
    }

    if ($taille < 1) {
        return '';
    }
    if ($taille < _KILOBYTE) {
        $taille = _T('taille_octets', array('taille' => $taille));
    } elseif ($taille < _KILOBYTE * _KILOBYTE) {
        $taille = _T('taille_ko', array('taille' => round($taille / _KILOBYTE, 1)));
    } elseif ($taille < _KILOBYTE * _KILOBYTE * _KILOBYTE) {
        $taille = _T('taille_mo', array('taille' => round($taille / _KILOBYTE / _KILOBYTE, 1)));
    } else {
        $taille = _T('taille_go', array('taille' => round($taille / _KILOBYTE / _KILOBYTE / _KILOBYTE, 2)));
    }

    return $taille;
}

/**
 * Rend une chaine utilisable sans dommage comme attribut HTML.
 *
 * @example `<a href="#URL_ARTICLE" title="[(#TITRE|attribut_html)]">#TITRE</a>`
 *
 * @filtre
 *
 * @link http://www.spip.net/4282
 *
 * @uses textebrut()
 * @uses texte_backend()
 *
 * @param string $texte
 *                          Texte à mettre en attribut
 * @param bool   $textebrut
 *                          Passe le texte en texte brut (enlève les balises html) ?
 *
 * @return string
 *                Texte prêt pour être utilisé en attribut HTML
 **/
function attribut_html($texte, $textebrut = true)
{
    $u = $GLOBALS['meta']['pcre_u'];
    if ($textebrut) {
        $texte = preg_replace(array(",\n,", ",\s(?=\s),msS".$u), array(' ', ''), textebrut($texte));
    }
    $texte = texte_backend($texte);
    $texte = str_replace(array("'", '"'), array('&#039;', '&#034;'), $texte);

    return preg_replace(array('/&(amp;|#38;)/', "/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,5};)/"), array('&', '&#38;'),
        $texte);
}

/**
 * Vider les URL nulles.
 *
 * - Vide les URL vides comme `http://` ou `mailto:` (sans rien d'autre)
 * - échappe les entités et gère les `&amp;`
 *
 * @uses entites_html()
 *
 * @param string $url
 *                        URL à vérifier et échapper
 * @param bool   $entites
 *                        `true` pour échapper les entités HTML.
 *
 * @return string
 *                URL ou chaîne vide
 **/
function vider_url($url, $entites = true)
{
    # un message pour abs_url
    $GLOBALS['mode_abs_url'] = 'url';
    $url = trim($url);
    $r = ',^(?:'._PROTOCOLES_STD.'):?/?/?$,iS';

    return preg_match($r, $url) ? '' : ($entites ? entites_html($url) : $url);
}

/**
 * Extrait une date d'un texte et renvoie le résultat au format de date SQL.
 *
 * L'année et le mois doivent être numériques.
 * Le séparateur entre l'année et le mois peut être un `-`, un `:` ou un texte
 * quelconque ne contenant pas de chiffres.
 *
 * Les jours ne sont pas pris en compte et le résultat est toujours le 1er du mois.
 *
 * @link http://www.spip.net/5516
 *
 * @param string $texte
 *                      Texte contenant une date tel que `2008-04`
 *
 * @return string
 *                Date au format SQL tel que `2008-04-01`
 **/
function extraire_date($texte)
{
    // format = 2001-08
    if (preg_match(',([1-2][0-9]{3})[^0-9]*(1[0-2]|0?[1-9]),', $texte, $regs)) {
        return $regs[1].'-'.sprintf('%02d', $regs[2]).'-01';
    }
}

/**
 * Maquiller une adresse e-mail.
 *
 * Remplace `@` par 3 caractères aléatoires.
 *
 * @uses creer_pass_aleatoire()
 *
 * @param string $texte Adresse email
 *
 * @return string Adresse email maquillée
 **/
function antispam($texte)
{
    include_spip('inc/acces');
    $masque = creer_pass_aleatoire(3);

    return preg_replace('/@/', " $masque ", $texte);
}

/**
 * Vérifie un accès à faible sécurité.
 *
 * Vérifie qu'un visiteur peut accéder à la page demandée,
 * qui est protégée par une clé, calculée à partir du low_sec de l'auteur,
 * et des paramètres le composant l'appel (op, args)
 *
 * @example
 *     `[(#ID_AUTEUR|securiser_acces{#ENV{cle}, rss, #ENV{op}, #ENV{args}}|sinon_interdire_acces)]`
 *
 * @see  bouton_spip_rss() pour générer un lien de faible sécurité pour les RSS privés
 * @see  afficher_low_sec() pour calculer une clé valide
 *
 * @uses verifier_low_sec()
 *
 * @filtre
 *
 * @param int    $id_auteur
 *                          L'auteur qui demande la page
 * @param string $cle
 *                          La clé à tester
 * @param string $dir
 *                          Un type d'accès (nom du répertoire dans lequel sont rangés les squelettes demandés, tel que 'rss')
 * @param string $op
 *                          Nom de l'opération éventuelle
 * @param string $args
 *                          Nom de l'argument calculé
 *
 * @return bool
 *              True si on a le droit d'accès, false sinon.
 **/
function securiser_acces($id_auteur, $cle, $dir, $op = '', $args = '')
{
    include_spip('inc/acces');
    if ($op) {
        $dir .= " $op $args";
    }

    return verifier_low_sec($id_auteur, $cle, $dir);
}

/**
 * Retourne le second paramètre lorsque
 * le premier est considere vide, sinon retourne le premier paramètre.
 *
 * En php `sinon($a, 'rien')` retourne `$a`, ou `'rien'` si `$a` est vide.
 * En filtre SPIP `|sinon{#TEXTE, rien}` : affiche `#TEXTE` ou `rien` si `#TEXTE` est vide,
 *
 * @filtre
 *
 * @see filtre_logique() pour la compilation du filtre dans un squelette
 * @link http://www.spip.net/4313
 * @note
 *     L'utilisation de `|sinon` en tant que filtre de squelette
 *     est directement compilé dans `public/references` par la fonction `filtre_logique()`
 *
 * @param mixed $texte
 *                     Contenu de reference a tester
 * @param mixed $sinon
 *                     Contenu a retourner si le contenu de reference est vide
 *
 * @return mixed
 *               Retourne $texte, sinon $sinon.
 **/
function sinon($texte, $sinon = '')
{
    if ($texte or (!is_array($texte) and strlen($texte))) {
        return $texte;
    } else {
        return $sinon;
    }
}

/**
 * Filtre `|choixsivide{vide, pas vide}` alias de `|?{si oui, si non}` avec les arguments inversés.
 *
 * @example
 *     `[(#TEXTE|choixsivide{vide, plein})]` affiche vide si le `#TEXTE`
 *     est considéré vide par PHP (chaîne vide, false, 0, tableau vide, etc…).
 *     C'est l'équivalent de `[(#TEXTE|?{plein, vide})]`
 *
 * @filtre
 *
 * @see choixsiegal()
 * @link http://www.spip.net/4189
 *
 * @param mixed $a
 *                       La valeur à tester
 * @param mixed $vide
 *                       Ce qui est retourné si `$a` est considéré vide
 * @param mixed $pasvide
 *                       Ce qui est retourné sinon
 *
 * @return mixed
 **/
function choixsivide($a, $vide, $pasvide)
{
    return $a ? $pasvide : $vide;
}

/**
 * Filtre `|choixsiegal{valeur, sioui, sinon}`.
 *
 * @example
 *     `#LANG_DIR|choixsiegal{ltr,left,right}` retourne `left` si
 *      `#LANG_DIR` vaut `ltr` et `right` sinon.
 *
 * @filtre
 *
 * @link http://www.spip.net/4148
 *
 * @param mixed $a1
 *                  La valeur à tester
 * @param mixed $a2
 *                  La valeur de comparaison
 * @param mixed $v
 *                  Ce qui est retourné si la comparaison est vraie
 * @param mixed $f
 *                  Ce qui est retourné sinon
 *
 * @return mixed
 **/
function choixsiegal($a1, $a2, $v, $f)
{
    return ($a1 == $a2) ? $v : $f;
}

//
// Date, heure, saisons
//

/**
 * Normaliser une date vers le format datetime (Y-m-d H:i:s).
 *
 * @note
 *     Si elle vient du contexte (public/parametrer.php), on force le jour
 *
 * @filtre
 *
 * @link http://www.spip.net/5518
 *
 * @uses vider_date()
 *
 * @param string $date
 *                            La date à normaliser
 * @param bool   $forcer_jour
 *                            true pour forcer à indiquer un jour et mois (01) s'il n'y en a pas.
 *
 * @return string
 *                - une date au format datetime
 *                - une chaîne vide si la date est considérée nulle
 **/
function normaliser_date($date, $forcer_jour = false)
{
    $date = vider_date($date);
    if ($date) {
        if (preg_match('/^[0-9]{8,10}$/', $date)) {
            $date = date('Y-m-d H:i:s', $date);
        }
        if (preg_match('#^([12][0-9]{3})([-/]00)?( [-0-9:]+)?$#', $date, $regs)) {
            $regs = array_pad($regs, 4, null); // eviter notice php
            $date = $regs[1].'-00-00'.$regs[3];
        } else {
            if (preg_match('#^([12][0-9]{3}[-/][01]?[0-9])([-/]00)?( [-0-9:]+)?$#', $date, $regs)) {
                $regs = array_pad($regs, 4, null); // eviter notice php
                $date = preg_replace('@/@', '-', $regs[1]).'-00'.$regs[3];
            } else {
                $date = date('Y-m-d H:i:s', strtotime($date));
            }
        }

        if ($forcer_jour) {
            $date = str_replace('-00', '-01', $date);
        }
    }

    return $date;
}

/**
 * Enlève une date considérée comme vide.
 *
 * @param string $letexte
 *
 * @return string
 *                - La date entrée (si elle n'est pas considérée comme nulle)
 *                - Une chaine vide
 **/
function vider_date($letexte)
{
    if (strncmp('0000-00-00', $letexte, 10) == 0) {
        return '';
    }
    if (strncmp('0001-01-01', $letexte, 10) == 0) {
        return '';
    }
    if (strncmp('1970-01-01', $letexte, 10) == 0) {
        return '';
    }  // eviter le bug GMT-1
    return $letexte;
}

/**
 * Retrouve à partir d'une chaîne les valeurs heures, minutes, secondes.
 *
 * Retrouve une horaire au format `11:29:55`
 *
 * @param string $date
 *                     Chaîne de date contenant éventuellement une horaire
 *
 * @return array
 *               - [heures, minutes, secondes] si horaire trouvée
 *               - [0, 0, 0] sinon
 **/
function recup_heure($date)
{
    static $d = array(0, 0, 0);
    if (!preg_match('#([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})#', $date, $r)) {
        return $d;
    }

    array_shift($r);

    return $r;
}

/**
 * Retourne l'heure d'une date.
 *
 * @filtre
 *
 * @link http://www.spip.net/4293
 *
 * @uses recup_heure()
 *
 * @param string $numdate La date à extraire
 *
 * @return int heures, sinon 0
 **/
function heures($numdate)
{
    $date_array = recup_heure($numdate);
    if ($date_array) {
        list($heures, $minutes, $secondes) = $date_array;
    }

    return $heures;
}

/**
 * Retourne les minutes d'une date.
 *
 * @filtre
 *
 * @link http://www.spip.net/4300
 *
 * @uses recup_heure()
 *
 * @param string $numdate La date à extraire
 *
 * @return int minutes, sinon 0
 **/
function minutes($numdate)
{
    $date_array = recup_heure($numdate);
    if ($date_array) {
        list($heures, $minutes, $secondes) = $date_array;
    }

    return $minutes;
}

/**
 * Retourne les secondes d'une date.
 *
 * @filtre
 *
 * @link http://www.spip.net/4312
 *
 * @uses recup_heure()
 *
 * @param string $numdate La date à extraire
 *
 * @return int secondes, sinon 0
 **/
function secondes($numdate)
{
    $date_array = recup_heure($numdate);
    if ($date_array) {
        list($heures, $minutes, $secondes) = $date_array;
    }

    return $secondes;
}

/**
 * Retourne l'horaire (avec minutes) d'une date, tel que `12h36min`.
 *
 * @note
 *     Le format de retour varie selon la langue utilisée.
 *
 * @filtre
 *
 * @link http://www.spip.net/5519
 *
 * @param string $numdate La date à extraire
 *
 * @return string L'heure formatée dans la langue en cours.
 **/
function heures_minutes($numdate)
{
    return _T('date_fmt_heures_minutes', array('h' => heures($numdate), 'm' => minutes($numdate)));
}

/**
 * Retrouve à partir d'une date les valeurs année, mois, jour, heures, minutes, secondes.
 *
 * Annee, mois, jour sont retrouvés si la date contient par exemple :
 * - '03/11/2015', '3/11/15'
 * - '2015-11-04', '2015-11-4'
 * - '2015-11'
 *
 * Dans ces cas, les heures, minutes, secondes sont retrouvés avec `recup_heure()`
 *
 * Annee, mois, jour, heures, minutes, secondes sont retrouvés si la date contient par exemple :
 * - '20151104111420'
 *
 * @uses recup_heure()
 *
 * @param string $numdate     La date à extraire
 * @param bool   $forcer_jour
 *                            True pour tout le temps renseigner un jour ou un mois (le 1) s'il
 *                            ne sont pas indiqués dans la date.
 *
 * @return array [année, mois, jour, heures, minutes, secondes]
 **/
function recup_date($numdate, $forcer_jour = true)
{
    if (!$numdate) {
        return '';
    }
    $heures = $minutes = $secondes = 0;
    if (preg_match('#([0-9]{1,2})/([0-9]{1,2})/([0-9]{4}|[0-9]{1,2})#', $numdate, $regs)) {
        $jour = $regs[1];
        $mois = $regs[2];
        $annee = $regs[3];
        if ($annee < 90) {
            $annee = 2000 + $annee;
        } elseif ($annee < 100) {
            $annee = 1900 + $annee;
        }
        list($heures, $minutes, $secondes) = recup_heure($numdate);
    } elseif (preg_match('#([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})#', $numdate, $regs)) {
        $annee = $regs[1];
        $mois = $regs[2];
        $jour = $regs[3];
        list($heures, $minutes, $secondes) = recup_heure($numdate);
    } elseif (preg_match('#([0-9]{4})-([0-9]{2})#', $numdate, $regs)) {
        $annee = $regs[1];
        $mois = $regs[2];
        $jour = '';
        list($heures, $minutes, $secondes) = recup_heure($numdate);
    } elseif (preg_match('#^([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})$#', $numdate, $regs)) {
        $annee = $regs[1];
        $mois = $regs[2];
        $jour = $regs[3];
        $heures = $regs[4];
        $minutes = $regs[5];
        $secondes = $regs[6];
    } else {
        $annee = $mois = $jour = '';
    }
    if ($annee > 4000) {
        $annee -= 9000;
    }
    if (substr($jour, 0, 1) == '0') {
        $jour = substr($jour, 1);
    }

    if ($forcer_jour and $jour == '0') {
        $jour = '1';
    }
    if ($forcer_jour and $mois == '0') {
        $mois = '1';
    }
    if ($annee or $mois or $jour or $heures or $minutes or $secondes) {
        return array($annee, $mois, $jour, $heures, $minutes, $secondes);
    }
}

/**
 * Retourne une date relative si elle est récente, sinon une date complète.
 *
 * En fonction de la date transmise, peut retourner par exemple :
 * - «il y a 3 minutes»,
 * - «il y a 11 heures»,
 * - «10 mai 2015 à 10h23min»
 *
 * @example `[(#DATE|date_interface)]`
 *
 * @filtre
 *
 * @link http://www.spip.net/5520
 *
 * @uses date_relative()
 * @uses affdate_heure() utilisé si le décalage est trop grand
 *
 * @param string $date
 *                              La date fournie
 * @param int    $decalage_maxi
 *                              Durée écoulée, en secondes, à partir de laquelle on bascule sur une date complète.
 *                              Par défaut +/- 12h.
 *
 * @return string
 *                La date relative ou complète
 **/
function date_interface($date, $decalage_maxi = 43200 /* 12*3600 */)
{
    return sinon(
        date_relative($date, $decalage_maxi),
        affdate_heure($date)
    );
}

/**
 * Retourne une date relative (passée ou à venir).
 *
 * En fonction de la date transmise ainsi que de la date de référence
 * (par défaut la date actuelle), peut retourner par exemple :
 * - «il y a 3 minutes»,
 * - «il y a 2 semmaines»,
 * - «dans 1 semaine»
 *
 * @example
 *     - `[(#DATE|date_relative)]`
 *     - `[(#DATE|date_relative{43200})]`
 *     - `[(#DATE|date_relative{0, #AUTRE_DATE})]` Calcul relatif à une date spécifique
 *
 * @filtre
 *
 * @link http://www.spip.net/4277
 *
 * @param string $date
 *                              La date fournie
 * @param int    $decalage_maxi
 *                              Durée écoulée, en secondes, au delà de laquelle on ne retourne pas de date relative
 *                              Indiquer `0` (par défaut) pour ignorer.
 * @param string $ref_date
 *                              La date de référence pour le calcul relatif, par défaut la date actuelle
 *
 * @return string
 *                - La date relative
 *                - "" si un dépasse le décalage maximum est indiqué et dépassé.
 **/
function date_relative($date, $decalage_maxi = 0, $ref_date = null)
{
    if (is_null($ref_date)) {
        $ref_time = time();
    } else {
        $ref_time = strtotime($ref_date);
    }

    if (!$date) {
        return;
    }
    $decal = date('U', $ref_time) - date('U', strtotime($date));

    if ($decalage_maxi and ($decal > $decalage_maxi or $decal < 0)) {
        return '';
    }

    if ($decal < 0) {
        $il_y_a = 'date_dans';
        $decal = -1 * $decal;
    } else {
        $il_y_a = 'date_il_y_a';
    }

    if ($decal > 3600 * 24 * 30 * 6) {
        return affdate_court($date);
    }

    if ($decal > 3600 * 24 * 30) {
        $mois = floor($decal / (3600 * 24 * 30));
        if ($mois < 2) {
            $delai = "$mois "._T('date_un_mois');
        } else {
            $delai = "$mois "._T('date_mois');
        }
    } else {
        if ($decal > 3600 * 24 * 7) {
            $semaines = floor($decal / (3600 * 24 * 7));
            if ($semaines < 2) {
                $delai = "$semaines "._T('date_une_semaine');
            } else {
                $delai = "$semaines "._T('date_semaines');
            }
        } else {
            if ($decal > 3600 * 24) {
                $jours = floor($decal / (3600 * 24));
                if ($jours < 2) {
                    return $il_y_a == 'date_dans' ? _T('date_demain') : _T('date_hier');
                } else {
                    $delai = "$jours "._T('date_jours');
                }
            } else {
                if ($decal >= 3600) {
                    $heures = floor($decal / 3600);
                    if ($heures < 2) {
                        $delai = "$heures "._T('date_une_heure');
                    } else {
                        $delai = "$heures "._T('date_heures');
                    }
                } else {
                    if ($decal >= 60) {
                        $minutes = floor($decal / 60);
                        if ($minutes < 2) {
                            $delai = "$minutes "._T('date_une_minute');
                        } else {
                            $delai = "$minutes "._T('date_minutes');
                        }
                    } else {
                        $secondes = ceil($decal);
                        if ($secondes < 2) {
                            $delai = "$secondes "._T('date_une_seconde');
                        } else {
                            $delai = "$secondes "._T('date_secondes');
                        }
                    }
                }
            }
        }
    }

    return _T($il_y_a, array('delai' => $delai));
}

/**
 * Retourne une date relative courte (passée ou à venir).
 *
 * Retourne «hier», «aujourd'hui» ou «demain» si la date correspond, sinon
 * utilise `date_relative()`
 *
 * @example `[(#DATE|date_relativecourt)]`
 *
 * @filtre
 *
 * @uses date_relative()
 *
 * @param string $date
 *                              La date fournie
 * @param int    $decalage_maxi
 *                              Durée écoulée, en secondes, au delà de laquelle on ne retourne pas de date relative
 *                              Indiquer `0` (par défaut) pour ignorer.
 *
 * @return string
 *                - La date relative
 *                - "" si un dépasse le décalage maximum est indiqué et dépassé.
 **/
function date_relativecourt($date, $decalage_maxi = 0)
{
    if (!$date) {
        return;
    }
    $decal = date('U', strtotime(date('Y-m-d')) - strtotime(date('Y-m-d', strtotime($date))));

    if ($decalage_maxi and ($decal > $decalage_maxi or $decal < 0)) {
        return '';
    }

    if ($decal < -24 * 3600) {
        $retour = date_relative($date, $decalage_maxi);
    } elseif ($decal < 0) {
        $retour = _T('date_demain');
    } else {
        if ($decal < (3600 * 24)) {
            $retour = _T('date_aujourdhui');
        } else {
            if ($decal < (3600 * 24 * 2)) {
                $retour = _T('date_hier');
            } else {
                $retour = date_relative($date, $decalage_maxi);
            }
        }
    }

    return $retour;
}

/**
 * Formatage humain de la date `$numdate` selon le format `$vue`.
 *
 * @param string $numdate
 *                        Une écriture de date
 * @param string $vue
 *                        Type de format souhaité ou expression pour `strtotime()` tel que `Y-m-d h:i:s`
 * @param array  $options {
 *
 * @var string $param
 *             'abbr' ou 'initiale' permet d'afficher les jours au format court ou initiale
 * @var int    $annee_courante
 *             Permet de definir l'annee de reference pour l'affichage des dates courtes
 *             }
 *
 * @return mixed|string
 */
function affdate_base($numdate, $vue, $options = array())
{
    if (is_string($options)) {
        $options = array('param' => $options);
    }
    $date_array = recup_date($numdate, false);
    if (!$date_array) {
        return;
    }
    list($annee, $mois, $jour, $heures, $minutes, $secondes) = $date_array;

    // 1er, 21st, etc.
    $journum = $jour;

    if ($jour == 0) {
        $jour = '';
        $njour = 0;
    } else {
        $njour = intval($jour);
        if ($jourth = _T('date_jnum'.$jour)) {
            $jour = $jourth;
        }
    }

    $mois = intval($mois);
    if ($mois > 0 and $mois < 13) {
        $nommois = _T('date_mois_'.$mois);
        if ($jour) {
            $jourmois = _T('date_de_mois_'.$mois, array('j' => $jour, 'nommois' => $nommois));
        } else {
            $jourmois = $nommois;
        }
    } else {
        $nommois = '';
        $jourmois = '';
    }

    if ($annee < 0) {
        $annee = -$annee.' '._T('date_avant_jc');
        $avjc = true;
    } else {
        $avjc = false;
    }

    switch ($vue) {
        case 'saison':
        case 'saison_annee':
            $saison = '';
            if ($mois > 0) {
                $saison = ($options['param'] == 'sud') ? 3 : 1;
                if (($mois == 3 and $jour >= 21) or $mois > 3) {
                    $saison = ($options['param'] == 'sud') ? 4 : 2;
                }
                if (($mois == 6 and $jour >= 21) or $mois > 6) {
                    $saison = ($options['param'] == 'sud') ? 1 : 3;
                }
                if (($mois == 9 and $jour >= 21) or $mois > 9) {
                    $saison = ($options['param'] == 'sud') ? 2 : 4;
                }
                if (($mois == 12 and $jour >= 21) or $mois > 12) {
                    $saison = ($options['param'] == 'sud') ? 3 : 1;
                }
            }
            if ($vue == 'saison') {
                return $saison ? _T('date_saison_'.$saison) : '';
            } else {
                return $saison ? trim(_T('date_fmt_saison_annee',
                    array('saison' => _T('date_saison_'.$saison), 'annee' => $annee))) : '';
            }

        case 'court':
            if ($avjc) {
                return $annee;
            }
            $a = ((isset($options['annee_courante']) and $options['annee_courante']) ? $options['annee_courante'] : date('Y'));
            if ($annee < ($a - 100) or $annee > ($a + 100)) {
                return $annee;
            }
            if ($annee != $a) {
                return _T('date_fmt_mois_annee',
                    array('mois' => $mois, 'nommois' => spip_ucfirst($nommois), 'annee' => $annee));
            }

            return _T('date_fmt_jour_mois',
                array('jourmois' => $jourmois, 'jour' => $jour, 'mois' => $mois, 'nommois' => $nommois, 'annee' => $annee));

        case 'jourcourt':
            if ($avjc) {
                return $annee;
            }
            $a = ((isset($options['annee_courante']) and $options['annee_courante']) ? $options['annee_courante'] : date('Y'));
            if ($annee < ($a - 100) or $annee > ($a + 100)) {
                return $annee;
            }
            if ($annee != $a) {
                return _T('date_fmt_jour_mois_annee',
                    array('jourmois' => $jourmois, 'jour' => $jour, 'mois' => $mois, 'nommois' => $nommois, 'annee' => $annee));
            }

            return _T('date_fmt_jour_mois',
                array('jourmois' => $jourmois, 'jour' => $jour, 'mois' => $mois, 'nommois' => $nommois, 'annee' => $annee));

        case 'entier':
            if ($avjc) {
                return $annee;
            }
            if ($jour) {
                return _T('date_fmt_jour_mois_annee',
                    array('jourmois' => $jourmois, 'jour' => $jour, 'mois' => $mois, 'nommois' => $nommois, 'annee' => $annee));
            } elseif ($mois) {
                return trim(_T('date_fmt_mois_annee', array('mois' => $mois, 'nommois' => $nommois, 'annee' => $annee)));
            } else {
                return $annee;
            }

        case 'nom_mois':
            $param = ((isset($options['param']) and $options['param']) ? '_'.$options['param'] : '');
            if ($param and $mois) {
                return _T('date_mois_'.$mois.$param);
            }

            return $nommois;

        case 'mois':
            return sprintf('%02s', $mois);

        case 'jour':
            return $jour;

        case 'journum':
            return $journum;

        case 'nom_jour':
            if (!$mois or !$njour) {
                return '';
            }
            $nom = mktime(1, 1, 1, $mois, $njour, $annee);
            $nom = 1 + date('w', $nom);
            $param = ((isset($options['param']) and $options['param']) ? '_'.$options['param'] : '');

            return _T('date_jour_'.$nom.$param);

        case 'mois_annee':
            if ($avjc) {
                return $annee;
            }

            return trim(_T('date_fmt_mois_annee', array('mois' => $mois, 'nommois' => $nommois, 'annee' => $annee)));

        case 'annee':
            return $annee;

        // Cas d'une vue non definie : retomber sur le format
        // de date propose par http://www.php.net/date
        default:
            return date($vue, strtotime($numdate));
    }
}

/**
 * Affiche le nom du jour pour une date donnée.
 *
 * @example
 *     - `[(#DATE|nom_jour)]` lundi
 *     - `[(#DATE|nom_jour{abbr})]` lun.
 *     - `[(#DATE|nom_jour{initiale})]` l.
 *
 * @filtre
 *
 * @link http://www.spip.net/4305
 *
 * @uses affdate_base()
 *
 * @param string $numdate
 *                        Une écriture de date
 * @param string $forme
 *                        Forme spécifique de retour :
 *                        - initiale : l'initiale du jour
 *                        - abbr : abbréviation du jour
 *                        - '' : le nom complet (par défaut)
 *
 * @return string
 *                Nom du jour
 **/
function nom_jour($numdate, $forme = '')
{
    if (!($forme == 'abbr' or $forme == 'initiale')) {
        $forme = '';
    }

    return affdate_base($numdate, 'nom_jour', $forme);
}

/**
 * Affiche le numéro du jour (1er à 31) pour une date donnée.
 *
 * Utilise une abbréviation (exemple "1er") pour certains jours,
 * en fonction de la langue utilisée.
 *
 * @example `[(#DATE|jour)]`
 *
 * @filtre
 *
 * @link http://www.spip.net/4295
 *
 * @uses affdate_base()
 *
 * @see  journum()
 *
 * @param string $numdate
 *                        Une écriture de date
 *
 * @return int
 *             Numéro du jour
 **/
function jour($numdate)
{
    return affdate_base($numdate, 'jour');
}

/**
 * Affiche le numéro du jour (1 à 31) pour une date donnée.
 *
 * @example `[(#DATE|journum)]`
 *
 * @filtre
 *
 * @uses affdate_base()
 *
 * @see  jour()
 *
 * @param string $numdate
 *                        Une écriture de date
 *
 * @return int
 *             Numéro du jour
 **/
function journum($numdate)
{
    return affdate_base($numdate, 'journum');
}

/**
 * Affiche le numéro du mois (01 à 12) pour une date donnée.
 *
 * @example `[(#DATE|mois)]`
 *
 * @filtre
 *
 * @link http://www.spip.net/4303
 *
 * @uses affdate_base()
 *
 * @param string $numdate
 *                        Une écriture de date
 *
 * @return string
 *                Numéro du mois (sur 2 chiffres)
 **/
function mois($numdate)
{
    return affdate_base($numdate, 'mois');
}

/**
 * Affiche le nom du mois pour une date donnée.
 *
 * @example
 *     - `[(#DATE|nom_mois)]` novembre
 *     - `[(#DATE|nom_mois{abbr})]` nov.
 *
 * @filtre
 *
 * @link http://www.spip.net/4306
 *
 * @uses affdate_base()
 *
 * @param string $numdate
 *                        Une écriture de date
 * @param string $forme
 *                        Forme spécifique de retour :
 *                        - abbr : abbréviation du mois
 *                        - '' : le nom complet (par défaut)
 *
 * @return string
 *                Nom du mois
 **/
function nom_mois($numdate, $forme = '')
{
    if (!($forme == 'abbr')) {
        $forme = '';
    }

    return affdate_base($numdate, 'nom_mois', $forme);
}

/**
 * Affiche l'année sur 4 chiffres d'une date donnée.
 *
 * @example `[(#DATE|annee)]`
 *
 * @filtre
 *
 * @link http://www.spip.net/4146
 *
 * @uses affdate_base()
 *
 * @param string $numdate
 *                        Une écriture de date
 *
 * @return int
 *             Année (sur 4 chiffres)
 **/
function annee($numdate)
{
    return affdate_base($numdate, 'annee');
}

/**
 * Affiche le nom boréal ou austral de la saison.
 *
 * @filtre
 *
 * @link http://www.spip.net/4311
 *
 * @uses affdate_base()
 *
 * @example
 *     En PHP
 *     ```
 *     saison("2008-10-11 14:08:45") affiche "automne"
 *     saison("2008-10-11 14:08:45", "sud") affiche "printemps"
 *     ```
 *     En squelettes
 *     ```
 *     [(#DATE|saison)]
 *     [(#DATE|saison{sud})]
 *     ```
 *
 * @param string $numdate
 *                           Une écriture de date
 * @param string $hemisphere
 *                           Nom optionnel de l'hémisphère (sud ou nord) ; par défaut nord
 *
 * @return string
 *                La date formatée
 **/
function saison($numdate, $hemisphere = 'nord')
{
    if ($hemisphere != 'sud') {
        $hemisphere = 'nord';
    }

    return affdate_base($numdate, 'saison', $hemisphere);
}

/**
 * Affiche le nom boréal ou austral de la saison suivi de l'année en cours.
 *
 * @filtre
 *
 * @uses affdate_base()
 *
 * @example
 *     En PHP
 *     ```
 *     saison_annee("2008-10-11 14:08:45") affiche "automne 2008"
 *     saison_annee("2008-10-11 14:08:45", "sud") affiche "printemps 2008"
 *     ```
 *     En squelettes
 *     ```
 *     [(#DATE|saison_annee)]
 *     [(#DATE|saison_annee{sud})]
 *     ```
 *
 * @param string $numdate
 *                           Une écriture de date
 * @param string $hemisphere
 *                           Nom optionnel de l'hémisphère (sud ou nord) ; par défaut nord
 *
 * @return string
 *                La date formatée
 **/
function saison_annee($numdate, $hemisphere = 'nord')
{
    if ($hemisphere != 'sud') {
        $hemisphere = 'nord';
    }

    return affdate_base($numdate, 'saison_annee', $hemisphere);
}

/**
 * Formate une date.
 *
 * @example
 *     En PHP`affdate("2008-10-11 14:08:45")` affiche "11 octobre 2008"
 * @example
 *     En squelettes
 *     - `[(#DATE|affdate)]`
 *     - `[(#DATE|affdate{Y-m-d})]`
 *
 * @filtre
 *
 * @link http://www.spip.net/4129
 *
 * @uses affdate_base()
 *
 * @see  affdate_court()
 * @see  affdate_jourcourt()
 *
 * @param string $numdate
 *                        Une écriture de date
 * @param string $format
 *                        Type de format souhaité ou expression pour `strtotime()` tel que `Y-m-d h:i:s`
 *
 * @return string
 *                La date formatée
 **/
function affdate($numdate, $format = 'entier')
{
    return affdate_base($numdate, $format);
}

/**
 * Formate une date, omet l'année si année courante, sinon omet le jour.
 *
 * Si l'année actuelle (ou indiquée dans `$annee_courante`) est 2015,
 * retournera "21 juin" si la date en entrée est le 21 juin 2015,
 * mais retournera "juin 2013" si la date en entrée est le 21 juin 2013.
 *
 * @example `[(#DATE|affdate_court)]`
 *
 * @filtre
 *
 * @link http://www.spip.net/4130
 *
 * @uses affdate_base()
 *
 * @see  affdate()
 * @see  affdate_jourcourt()
 *
 * @param string   $numdate
 *                                 Une écriture de date
 * @param int|null $annee_courante
 *                                 L'année de comparaison, utilisera l'année en cours si omis.
 *
 * @return string
 *                La date formatée
 **/
function affdate_court($numdate, $annee_courante = null)
{
    return affdate_base($numdate, 'court', array('annee_courante' => $annee_courante));
}

/**
 * Formate une date, omet l'année si année courante.
 *
 * Si l'année actuelle (ou indiquée dans `$annee_courante`) est 2015,
 * retournera "21 juin" si la date en entrée est le 21 juin 2015,
 * mais retournera "21 juin 2013" si la date en entrée est le 21 juin 2013.
 *
 * @example `[(#DATE|affdate_jourcourt)]`
 *
 * @filtre
 *
 * @link http://www.spip.net/4131
 *
 * @uses affdate_base()
 *
 * @see  affdate()
 * @see  affdate_court()
 *
 * @param string   $numdate
 *                                 Une écriture de date
 * @param int|null $annee_courante
 *                                 L'année de comparaison, utilisera l'année en cours si omis.
 *
 * @return string
 *                La date formatée
 **/
function affdate_jourcourt($numdate, $annee_courante = null)
{
    return affdate_base($numdate, 'jourcourt', array('annee_courante' => $annee_courante));
}

/**
 * Retourne le mois en toute lettre et l’année d'une date.
 *
 * Ne retourne pas le jour donc.
 *
 * @filtre
 *
 * @link http://www.spip.net/4132
 *
 * @uses affdate_base()
 *
 * @param string $numdate
 *                        Une écriture de date
 *
 * @return string
 *                La date formatée
 **/
function affdate_mois_annee($numdate)
{
    return affdate_base($numdate, 'mois_annee');
}

/**
 * Retourne la date suivie de l'heure.
 *
 * @example `[(#DATE|affdate_heure)]` peut donner "11 novembre 2015 à 11h10min"
 *
 * @filtre
 *
 * @uses recup_date()
 * @uses affdate()
 *
 * @param string $numdate
 *                        Une écriture de date
 *
 * @return string
 *                La date formatée
 **/
function affdate_heure($numdate)
{
    $date_array = recup_date($numdate);
    if (!$date_array) {
        return;
    }
    list($annee, $mois, $jour, $heures, $minutes, $sec) = $date_array;

    return _T('date_fmt_jour_heure', array(
        'jour' => affdate($numdate),
        'heure' => _T('date_fmt_heures_minutes', array('h' => $heures, 'm' => $minutes)),
    ));
}

/**
 * Afficher de facon textuelle les dates de début et fin en fonction des cas.
 *
 * - Lundi 20 fevrier a 18h
 * - Le 20 fevrier de 18h a 20h
 * - Du 20 au 23 fevrier
 * - Du 20 fevrier au 30 mars
 * - Du 20 fevrier 2007 au 30 mars 2008
 *
 * `$horaire='oui'` ou `true` permet d'afficher l'horaire,
 * toute autre valeur n'indique que le jour
 * `$forme` peut contenir une ou plusieurs valeurs parmi
 *  - `abbr` (afficher le nom des jours en abrege)
 *  - `hcal` (generer une date au format hcal)
 *  - `jour` (forcer l'affichage des jours)
 *  - `annee` (forcer l'affichage de l'annee)
 *
 * @param string $date_debut
 * @param string $date_fin
 * @param string $horaire
 * @param string $forme
 *                           - `abbr` pour afficher le nom du jour en abrege (Dim. au lieu de Dimanche)
 *                           - `annee` pour forcer l'affichage de l'annee courante
 *                           - `jour` pour forcer l'affichage du nom du jour
 *                           - `hcal` pour avoir un markup microformat abbr
 *
 * @return string
 *                Texte de la date
 */
function affdate_debut_fin($date_debut, $date_fin, $horaire = 'oui', $forme = '')
{
    $abbr = $jour = '';
    $affdate = 'affdate_jourcourt';
    if (strpos($forme, 'abbr') !== false) {
        $abbr = 'abbr';
    }
    if (strpos($forme, 'annee') !== false) {
        $affdate = 'affdate';
    }
    if (strpos($forme, 'jour') !== false) {
        $jour = 'jour';
    }

    $dtstart = $dtend = $dtabbr = '';
    if (strpos($forme, 'hcal') !== false) {
        $dtstart = "<abbr class='dtstart' title='".date_iso($date_debut)."'>";
        $dtend = "<abbr class='dtend' title='".date_iso($date_fin)."'>";
        $dtabbr = '</abbr>';
    }

    $date_debut = strtotime($date_debut);
    $date_fin = strtotime($date_fin);
    $d = date('Y-m-d', $date_debut);
    $f = date('Y-m-d', $date_fin);
    $h = ($horaire === 'oui' or $horaire === true);
    $hd = _T('date_fmt_heures_minutes_court', array('h' => date('H', $date_debut), 'm' => date('i', $date_debut)));
    $hf = _T('date_fmt_heures_minutes_court', array('h' => date('H', $date_fin), 'm' => date('i', $date_fin)));

    if ($d == $f) { // meme jour
        $nomjour = nom_jour($d, $abbr);
        $s = $affdate($d);
        $s = _T('date_fmt_jour', array('nomjour' => $nomjour, 'jour' => $s));
        if ($h) {
            if ($hd == $hf) {
                // Lundi 20 fevrier a 18h25
                $s = spip_ucfirst(_T('date_fmt_jour_heure', array('jour' => $s, 'heure' => $hd)));
                $s = "$dtstart$s$dtabbr";
            } else {
                // Le <abbr...>lundi 20 fevrier de 18h00</abbr> a <abbr...>20h00</abbr>
                if ($dtabbr && $dtstart && $dtend) {
                    $s = _T('date_fmt_jour_heure_debut_fin_abbr', array(
                        'jour' => spip_ucfirst($s),
                        'heure_debut' => $hd,
                        'heure_fin' => $hf,
                        'dtstart' => $dtstart,
                        'dtend' => $dtend,
                        'dtabbr' => $dtabbr,
                    ));
                } // Le lundi 20 fevrier de 18h00 a 20h00
                else {
                    $s = spip_ucfirst(_T('date_fmt_jour_heure_debut_fin',
                        array('jour' => $s, 'heure_debut' => $hd, 'heure_fin' => $hf)));
                }
            }
        } else {
            if ($dtabbr && $dtstart) {
                $s = $dtstart.spip_ucfirst($s).$dtabbr;
            } else {
                $s = spip_ucfirst($s);
            }
        }
    } else {
        if ((date('Y-m', $date_debut)) == date('Y-m', $date_fin)) { // meme annee et mois, jours differents
            if (!$h) {
                $date_debut = jour($d);
            } else {
                $date_debut = affdate_jourcourt($d, date('Y', $date_fin));
            }
            $date_fin = $affdate($f);
            if ($jour) {
                $nomjour_debut = nom_jour($d, $abbr);
                $date_debut = _T('date_fmt_jour', array('nomjour' => $nomjour_debut, 'jour' => $date_debut));
                $nomjour_fin = nom_jour($f, $abbr);
                $date_fin = _T('date_fmt_jour', array('nomjour' => $nomjour_fin, 'jour' => $date_fin));
            }
            if ($h) {
                $date_debut = _T('date_fmt_jour_heure', array('jour' => $date_debut, 'heure' => $hd));
                $date_fin = _T('date_fmt_jour_heure', array('jour' => $date_fin, 'heure' => $hf));
            }
            $date_debut = $dtstart.$date_debut.$dtabbr;
            $date_fin = $dtend.$date_fin.$dtabbr;

            $s = _T('date_fmt_periode', array('date_debut' => $date_debut, 'date_fin' => $date_fin));
        } else {
            $date_debut = affdate_jourcourt($d, date('Y', $date_fin));
            $date_fin = $affdate($f);
            if ($jour) {
                $nomjour_debut = nom_jour($d, $abbr);
                $date_debut = _T('date_fmt_jour', array('nomjour' => $nomjour_debut, 'jour' => $date_debut));
                $nomjour_fin = nom_jour($f, $abbr);
                $date_fin = _T('date_fmt_jour', array('nomjour' => $nomjour_fin, 'jour' => $date_fin));
            }
            if ($h) {
                $date_debut = _T('date_fmt_jour_heure', array('jour' => $date_debut, 'heure' => $hd));
                $date_fin = _T('date_fmt_jour_heure', array('jour' => $date_fin, 'heure' => $hf));
            }

            $date_debut = $dtstart.$date_debut.$dtabbr;
            $date_fin = $dtend.$date_fin.$dtabbr;
            $s = _T('date_fmt_periode', array('date_debut' => $date_debut, 'date_fin' => $date_fin));
        }
    }

    return $s;
}

/**
 * Alignements en HTML (Old-style, préférer CSS).
 *
 * Cette fonction ne crée pas de paragraphe
 *
 * @deprecated Utiliser CSS
 *
 * @param string $letexte
 * @param string $justif
 *
 * @return string
 */
function aligner($letexte, $justif = '')
{
    $letexte = trim($letexte);
    if (!strlen($letexte)) {
        return '';
    }

    // Paragrapher rapidement
    $letexte = "<div style='text-align:$justif'>"
        .$letexte
        .'</div>';

    return $letexte;
}

/**
 * Justifie en HTML (Old-style, préférer CSS).
 *
 * @deprecated Utiliser CSS
 *
 * @uses aligner()
 *
 * @param string $letexte
 *
 * @return string
 */
function justifier($letexte)
{
    return aligner($letexte, 'justify');
}

/**
 * Aligne à droite en HTML (Old-style, préférer CSS).
 *
 * @deprecated Utiliser CSS
 *
 * @uses aligner()
 *
 * @param string $letexte
 *
 * @return string
 */
function aligner_droite($letexte)
{
    return aligner($letexte, 'right');
}

/**
 * Aligne à gauche en HTML (Old-style, préférer CSS).
 *
 * @deprecated Utiliser CSS
 *
 * @uses aligner()
 *
 * @param string $letexte
 *
 * @return string
 */
function aligner_gauche($letexte)
{
    return aligner($letexte, 'left');
}

/**
 * Centre en HTML (Old-style, préférer CSS).
 *
 * @deprecated Utiliser CSS
 *
 * @uses aligner()
 *
 * @param string $letexte
 *
 * @return string
 */
function centrer($letexte)
{
    return aligner($letexte, 'center');
}

/**
 * Retourne un texte de style CSS aligné sur la langue en cours.
 *
 * @deprecated
 *
 * @param mixed $bof Inutilisé
 *
 * @return string Style CSS
 **/
function style_align($bof)
{
    return 'text-align: '.$GLOBALS['spip_lang_left'];
}

//
// Export iCal
//

/**
 * Adapte un texte pour être inséré dans une valeur d'un export ICAL.
 *
 * Passe le texte en utf8, enlève les sauts de lignes et échappe les virgules.
 *
 * @example `SUMMARY:[(#TITRE|filtrer_ical)]`
 * @filtre
 *
 * @param string $texte
 *
 * @return string
 **/
function filtrer_ical($texte)
{
    #include_spip('inc/charsets');
    $texte = html2unicode($texte);
    $texte = unicode2charset(charset2unicode($texte, $GLOBALS['meta']['charset'], 1), 'utf-8');
    $texte = preg_replace("/\n/", ' ', $texte);
    $texte = preg_replace('/,/', "\,", $texte);

    return $texte;
}

/**
 * Adapte une date pour être insérée dans une valeur de date d'un export ICAL.
 *
 * Retourne une date au format `Ymd\THis`, tel que '20150428T163254'
 *
 * @example `DTSTAMP:[(#DATE|date_ical)]`
 * @filtre
 *
 * @uses recup_heure()
 * @uses recup_date()
 *
 * @param string $date
 *                           La date
 * @param int    $addminutes
 *                           Ajouter autant de minutes à la date
 *
 * @return string
 *                Date au format ical
 **/
function date_ical($date, $addminutes = 0)
{
    list($heures, $minutes, $secondes) = recup_heure($date);
    list($annee, $mois, $jour) = recup_date($date);

    return date("Ymd\THis", mktime($heures, $minutes + $addminutes, $secondes, $mois, $jour, $annee));
}

/**
 * Retourne une date formattée au format "RFC 3339" ou "ISO 8601".
 *
 * @example `[(#DATE|date_iso)]` peut donner "2015-11-11T10:13:45Z"
 *
 * @filtre
 *
 * @link http://www.spip.net/5641
 * @link https://fr.wikipedia.org/wiki/ISO_8601
 * @link http://www.ietf.org/rfc/rfc3339.txt
 * @link http://php.net/manual/fr/class.datetime.php
 *
 * @uses recup_date()
 * @uses recup_heure()
 *
 * @param string $date_heure
 *                           Une écriture de date
 *
 * @return string
 *                La date formatée
 **/
function date_iso($date_heure)
{
    list($annee, $mois, $jour) = recup_date($date_heure);
    list($heures, $minutes, $secondes) = recup_heure($date_heure);
    $time = @mktime($heures, $minutes, $secondes, $mois, $jour, $annee);

    return gmdate('Y-m-d\TH:i:s\Z', $time);
}

/**
 * Retourne une date formattée au format "RFC 822".
 *
 * Utilisé pour `<pubdate>` dans certains flux RSS
 *
 * @example `[(#DATE|date_822)]` peut donner "Wed, 11 Nov 2015 11:13:45 +0100"
 *
 * @filtre
 *
 * @link http://www.spip.net/4276
 * @link http://php.net/manual/fr/class.datetime.php
 *
 * @uses recup_date()
 * @uses recup_heure()
 *
 * @param string $date_heure
 *                           Une écriture de date
 *
 * @return string
 *                La date formatée
 **/
function date_822($date_heure)
{
    list($annee, $mois, $jour) = recup_date($date_heure);
    list($heures, $minutes, $secondes) = recup_heure($date_heure);
    $time = mktime($heures, $minutes, $secondes, $mois, $jour, $annee);

    return date('r', $time);
}

/**
 * Pour une date commençant par `Y-m-d`, retourne `Ymd`.
 *
 * @example `date_anneemoisjour('2015-10-11 11:27:03')` retourne `20151011`
 *
 * @see date_anneemois()
 *
 * @param string $d
 *                  Une écriture de date commençant par un format `Y-m-d` (comme date ou datetime SQL).
 *                  Si vide, utilise la date actuelle.
 *
 * @return string
 *                Date au format `Ymd`
 **/
function date_anneemoisjour($d)
{
    if (!$d) {
        $d = date('Y-m-d');
    }

    return substr($d, 0, 4).substr($d, 5, 2).substr($d, 8, 2);
}

/**
 * Pour une date commençant par `Y-m`, retourne `Ym`.
 *
 * @example `date_anneemoisjour('2015-10-11 11:27:03')` retourne `201510`
 *
 * @see date_anneemoisjour()
 *
 * @param string $d
 *                  Une écriture de date commençant par un format `Y-m` (comme date ou datetime SQL).
 *                  Si vide, utilise la date actuelle.
 *
 * @return string
 *                Date au format `Ym`
 **/
function date_anneemois($d)
{
    if (!$d) {
        $d = date('Y-m-d');
    }

    return substr($d, 0, 4).substr($d, 5, 2);
}

/**
 * Retourne le premier jour (lundi) de la même semaine au format `Ymd`.
 *
 * @example `date_debut_semaine(2015, 11, 11)` retourne `20151109`
 *
 * @see date_fin_semaine()
 *
 * @param int $annee
 * @param int $mois
 * @param int $jour
 *
 * @return string
 *                Date au lundi de la même semaine au format `Ymd`
 **/
function date_debut_semaine($annee, $mois, $jour)
{
    $w_day = date('w', mktime(0, 0, 0, $mois, $jour, $annee));
    if ($w_day == 0) {
        $w_day = 7;
    } // Gaffe: le dimanche est zero
    $debut = $jour - $w_day + 1;

    return date('Ymd', mktime(0, 0, 0, $mois, $debut, $annee));
}

/**
 * Retourne le dernier jour (dimanche) de la même semaine au format `Ymd`.
 *
 * @example `date_debut_semaine(2015, 11, 11)` retourne `20151115`
 *
 * @see date_fin_semaine()
 *
 * @param int $annee
 * @param int $mois
 * @param int $jour
 *
 * @return string
 *                Date au dimanche de la même semaine au format `Ymd`
 **/
function date_fin_semaine($annee, $mois, $jour)
{
    $w_day = date('w', mktime(0, 0, 0, $mois, $jour, $annee));
    if ($w_day == 0) {
        $w_day = 7;
    } // Gaffe: le dimanche est zero
    $debut = $jour - $w_day + 1;

    return date('Ymd', mktime(0, 0, 0, $mois, $debut + 6, $annee));
}

/**
 * Transforme les sauts de ligne simples en sauts forcés avec `_ `.
 *
 * Ne modifie pas les sauts de paragraphe (2 sauts consécutifs au moins),
 * ou les retours à l'intérieur de modèles ou de certaines balises html.
 *
 * @note
 *     Cette fonction pouvait être utilisée pour forcer les alinéas,
 *     (retours à la ligne sans saut de paragraphe), mais ce traitement
 *     est maintenant automatique.
 *     Cf. plugin Textwheel et la constante _AUTOBR
 *
 * @uses echappe_html()
 * @uses echappe_retour()
 *
 * @param string $texte
 * @param string $delim
 *                      Ce par quoi sont remplacés les sauts
 *
 * @return string
 **/
function post_autobr($texte, $delim = "\n_ ")
{
    if (!function_exists('echappe_html')) {
        include_spip('inc/texte_mini');
    }
    $texte = str_replace("\r\n", "\r", $texte);
    $texte = str_replace("\r", "\n", $texte);

    if (preg_match(",\n+$,", $texte, $fin)) {
        $texte = substr($texte, 0, -strlen($fin = $fin[0]));
    } else {
        $fin = '';
    }

    $texte = echappe_html($texte, '', true);

    // echapper les modeles
    if (strpos($texte, '<') !== false) {
        include_spip('inc/lien');
        if (defined('_PREG_MODELE')) {
            $preg_modeles = '@'._PREG_MODELE.'@imsS';
            $texte = echappe_html($texte, '', true, $preg_modeles);
        }
    }

    $debut = '';
    $suite = $texte;
    while ($t = strpos('-'.$suite, "\n", 1)) {
        $debut .= substr($suite, 0, $t - 1);
        $suite = substr($suite, $t);
        $car = substr($suite, 0, 1);
        if (($car != '-') and ($car != '_') and ($car != "\n") and ($car != '|') and ($car != '}')
            and !preg_match(',^\s*(\n|</?(quote|div|dl|dt|dd)|$),S', ($suite))
            and !preg_match(',</?(quote|div|dl|dt|dd)> *$,iS', $debut)
        ) {
            $debut .= $delim;
        } else {
            $debut .= "\n";
        }
        if (preg_match(",^\n+,", $suite, $regs)) {
            $debut .= $regs[0];
            $suite = substr($suite, strlen($regs[0]));
        }
    }
    $texte = $debut.$suite;

    $texte = echappe_retour($texte);

    return $texte.$fin;
}

/*
 * Expression régulière pour obtenir le contenu des extraits polyglottes `<multi>`
 *
 * @var string
 */
define('_EXTRAIRE_MULTI', '@<multi>(.*?)</multi>@sS');

/**
 * Extrait une langue des extraits polyglottes (`<multi>`).
 *
 * Retrouve les balises `<multi>` d'un texte et remplace son contenu
 * par l'extrait correspondant à la langue demandée.
 *
 * Si la langue demandée n'est pas trouvée dans le multi, ni une langue
 * approchante (exemple `fr` si on demande `fr_TU`), on retourne l'extrait
 * correspondant à la langue par défaut (option 'lang_defaut'), qui est
 * par défaut la langue du site. Et si l'extrait n'existe toujours pas
 * dans cette langue, ça utilisera la première langue utilisée
 * dans la balise `<multi>`.
 *
 * Ne pas mettre de span@lang=fr si on est déjà en fr.
 *
 * @filtre
 *
 * @link http://www.spip.net/5332
 *
 * @uses extraire_trads()
 * @uses approcher_langue()
 * @uses lang_typo()
 * @uses code_echappement()
 * @uses echappe_retour()
 *
 * @param string $letexte
 * @param string $lang
 *                        Langue à retrouver (si vide, utilise la langue en cours).
 * @param array  $options Options {
 *
 * @var bool   $echappe_span
 *             True pour échapper les balises span (false par défaut)
 * @var string $lang_defaut
 *             Code de langue : permet de définir la langue utilisée par défaut,
 *             en cas d'absence de traduction dans la langue demandée.
 *             Par défaut la langue du site.
 *             Indiquer 'aucune' pour ne pas retourner de texte si la langue
 *             exacte n'a pas été trouvée.
 *             }
 *
 * @return string
 **/
function extraire_multi($letexte, $lang = null, $options = array())
{
    if ($letexte
        and preg_match_all(_EXTRAIRE_MULTI, $letexte, $regs, PREG_SET_ORDER)
    ) {
        if (!$lang) {
            $lang = $GLOBALS['spip_lang'];
        }

        // Compatibilité avec le prototype de fonction précédente qui utilisait un boolean
        if (is_bool($options)) {
            $options = array('echappe_span' => $options, 'lang_defaut' => _LANGUE_PAR_DEFAUT);
        }
        if (!isset($options['echappe_span'])) {
            $options = array_merge($options, array('echappe_span' => false));
        }
        if (!isset($options['lang_defaut'])) {
            $options = array_merge($options, array('lang_defaut' => _LANGUE_PAR_DEFAUT));
        }

        include_spip('inc/lang');
        foreach ($regs as $reg) {
            // chercher la version de la langue courante
            $trads = extraire_trads($reg[1]);
            if ($l = approcher_langue($trads, $lang)) {
                $trad = $trads[$l];
            } else {
                if ($options['lang_defaut'] == 'aucune') {
                    $trad = '';
                } else {
                    // langue absente, prendre le fr ou une langue précisée (meme comportement que inc/traduire.php)
                    // ou la premiere dispo
                    // mais typographier le texte selon les regles de celle-ci
                    // Attention aux blocs multi sur plusieurs lignes
                    if (!$l = approcher_langue($trads, $options['lang_defaut'])) {
                        $l = key($trads);
                    }
                    $trad = $trads[$l];
                    $typographie = charger_fonction(lang_typo($l), 'typographie');
                    $trad = $typographie($trad);
                    // Tester si on echappe en span ou en div
                    // il ne faut pas echapper en div si propre produit un seul paragraphe
                    include_spip('inc/texte');
                    $trad_propre = preg_replace(',(^<p[^>]*>|</p>$),Uims', '', propre($trad));
                    $mode = preg_match(',</?('._BALISES_BLOCS.')[>[:space:]],iS', $trad_propre) ? 'div' : 'span';
                    $trad = code_echappement($trad, 'multi', false, $mode);
                    $trad = str_replace("'", '"', inserer_attribut($trad, 'lang', $l));
                    if (lang_dir($l) !== lang_dir($lang)) {
                        $trad = str_replace("'", '"', inserer_attribut($trad, 'dir', lang_dir($l)));
                    }
                    if (!$options['echappe_span']) {
                        $trad = echappe_retour($trad, 'multi');
                    }
                }
            }
            $letexte = str_replace($reg[0], $trad, $letexte);
        }
    }

    return $letexte;
}

/**
 * Convertit le contenu d'une balise `<multi>` en un tableau.
 *
 * Exemple de blocs.
 * - `texte par défaut [fr] en français [en] en anglais`
 * - `[fr] en français [en] en anglais`
 *
 * @param string $bloc
 *                     Le contenu intérieur d'un bloc multi
 *
 * @return array [code de langue => texte]
 *               Peut retourner un code de langue vide, lorsqu'un texte par défaut est indiqué.
 **/
function extraire_trads($bloc)
{
    $lang = '';
// ce reg fait planter l'analyse multi s'il y a de l'{italique} dans le champ
//	while (preg_match("/^(.*?)[{\[]([a-z_]+)[}\]]/siS", $bloc, $regs)) {
    while (preg_match("/^(.*?)[\[]([a-z_]+)[\]]/siS", $bloc, $regs)) {
        $texte = trim($regs[1]);
        if ($texte or $lang) {
            $trads[$lang] = $texte;
        }
        $bloc = substr($bloc, strlen($regs[0]));
        $lang = $regs[2];
    }
    $trads[$lang] = $bloc;

    return $trads;
}

/**
 * Calculer l'initiale d'un nom.
 *
 * @param string $nom
 *
 * @return string L'initiale en majuscule
 */
function filtre_initiale($nom)
{
    return spip_substr(trim(strtoupper(extraire_multi($nom))), 0, 1);
}

/**
 * Retourne la donnée si c'est la première fois qu'il la voit.
 *
 * Il est possible de gérer différentes "familles" de données avec
 * le second paramètre.
 *
 * @filtre
 *
 * @link http://www.spip.net/4320
 *
 * @example
 *     ```
 *     [(#ID_SECTEUR|unique)]
 *     [(#ID_SECTEUR|unique{tete})] n'a pas d'incidence sur
 *     [(#ID_SECTEUR|unique{pied})]
 *     [(#ID_SECTEUR|unique{pied,1})] affiche le nombre d'éléments.
 *     Préférer totefois #TOTAL_UNIQUE{pied}
 *     ```
 *
 * @todo
 *    Ameliorations possibles :
 *
 *    1) si la donnée est grosse, mettre son md5 comme clé
 *    2) purger $mem quand on change de squelette (sinon bug inclusions)
 *
 * @param string $donnee
 *                        Donnée que l'on souhaite unique
 * @param string $famille
 *                        Famille de stockage (1 unique donnée par famille)
 *
 *      - _spip_raz_ : (interne) Vide la pile de mémoire et la retourne
 *      - _spip_set_ : (interne) Affecte la pile de mémoire avec la donnée
 * @param bool $cpt
 *                  True pour obtenir le nombre d'éléments différents stockés
 *
 * @return string|int|array|null|void
 *
 *      - string : Donnée si c'est la première fois qu'elle est vue
 *      - void : si la donnée a déjà été vue
 *      - int : si l'on demande le nombre d'éléments
 *      - array (interne) : si on dépile
 *      - null (interne) : si on empile
 **/
function unique($donnee, $famille = '', $cpt = false)
{
    static $mem = array();
    // permettre de vider la pile et de la restaurer
    // pour le calcul de introduction...
    if ($famille == '_spip_raz_') {
        $tmp = $mem;
        $mem = array();

        return $tmp;
    } elseif ($famille == '_spip_set_') {
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

/**
 * Filtre qui alterne des valeurs en fonction d'un compteur.
 *
 * Affiche à tour de rôle et dans l'ordre, un des arguments transmis
 * à chaque incrément du compteur.
 *
 * S'il n'y a qu'un seul argument, et que c'est un tableau,
 * l'alternance se fait sur les valeurs du tableau.
 *
 * Souvent appliqué à l'intérieur d'une boucle, avec le compteur `#COMPTEUR_BOUCLE`
 *
 * @example
 *     - `[(#COMPTEUR_BOUCLE|alterner{bleu,vert,rouge})]`
 *     - `[(#COMPTEUR_BOUCLE|alterner{#LISTE{bleu,vert,rouge}})]`
 *
 * @filtre
 *
 * @link http://www.spip.net/4145
 *
 * @param int $i
 *               Le compteur
 *
 * @return mixed
 *               Une des valeurs en fonction du compteur.
 **/
function alterner($i)
{
    // recuperer les arguments (attention fonctions un peu space)
    $num = func_num_args();
    $args = func_get_args();

    if ($num == 2 && is_array($args[1])) {
        $args = $args[1];
        array_unshift($args, '');
        $num = count($args);
    }

    // renvoyer le i-ieme argument, modulo le nombre d'arguments
    return $args[(intval($i) - 1) % ($num - 1) + 1];
}

/**
 * Récupérer un attribut d'une balise HTML.
 *
 * la regexp est mortelle : cf. `tests/unit/filtres/extraire_attribut.php`
 * Si on a passé un tableau de balises, renvoyer un tableau de résultats
 * (dans ce cas l'option `$complet` n'est pas disponible)
 *
 * @param string|array $balise
 *                               Texte ou liste de textes dont on veut extraire des balises
 * @param string       $attribut
 *                               Nom de l'attribut désiré
 * @param bool         $complet
 *                               True pour retourner un tableau avec
 *                               - le texte de la balise
 *                               - l'ensemble des résultats de la regexp ($r)
 *
 * @return string|array
 *                      - Texte de l'attribut retourné, ou tableau des texte d'attributs
 *                      (si 1er argument tableau)
 *                      - Tableau complet (si 2e argument)
 **/
function extraire_attribut($balise, $attribut, $complet = false)
{
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
        .'(?:=\s*("[^"]*"|\'[^\']*\'|[^\'"]\S*))?)()((?:[\s/][^>]*)?>.*),isS',

        $balise, $r)) {
        if (isset($r[3][0]) and ($r[3][0] == '"' || $r[3][0] == "'")) {
            $r[4] = substr($r[3], 1, -1);
            $r[3] = $r[3][0];
        } elseif ($r[3] !== '') {
            $r[4] = $r[3];
            $r[3] = '';
        } else {
            $r[4] = trim($r[2]);
        }
        $att = $r[4];
        if (strpos($att, '&#') !== false) {
            $att = str_replace(array('&#039;', '&#39;', '&#034;', '&#34;'), array("'", "'", '"', '"'), $att);
        }
        $att = filtrer_entites($att);
    } else {
        $att = null;
    }

    if ($complet) {
        return array($att, $r);
    } else {
        return $att;
    }
}

/**
 * Insérer (ou modifier) un attribut html dans une balise.
 *
 * @example
 *     - `[(#LOGO_ARTICLE|inserer_attribut{class, logo article})]`
 *     - `[(#LOGO_ARTICLE|inserer_attribut{alt, #TTTRE|attribut_html|couper{60}})]`
 *     - `[(#FICHIER|image_reduire{40}|inserer_attribut{data-description, #DESCRIPTIF})]`
 *       Laissera les balises HTML de la valeur (ici `#DESCRIPTIF`) si on n'applique pas le
 *       filtre `attribut_html` dessus.
 *
 * @filtre
 *
 * @link http://www.spip.net/4294
 *
 * @uses attribut_html()
 * @uses extraire_attribut()
 *
 * @param string $balise
 *                         Code html de la balise (ou contenant une balise)
 * @param string $attribut
 *                         Nom de l'attribut html à modifier
 * @param string $val
 *                         Valeur de l'attribut à appliquer
 * @param bool   $proteger
 *                         Prépare la valeur en tant qu'attribut de balise (mais conserve les balises html).
 * @param bool   $vider
 *                         True pour vider l'attribut. Une chaîne vide pour `$val` fera pareil.
 *
 * @return string
 *                Code html modifié
 **/
function inserer_attribut($balise, $attribut, $val, $proteger = true, $vider = false)
{
    // preparer l'attribut
    // supprimer les &nbsp; etc mais pas les balises html
    // qui ont un sens dans un attribut value d'un input
    if ($proteger) {
        $val = attribut_html($val, false);
    }

    // echapper les ' pour eviter tout bug
    $val = str_replace("'", '&#039;', $val);
    if ($vider and strlen($val) == 0) {
        $insert = '';
    } else {
        $insert = " $attribut='$val'";
    }

    list($old, $r) = extraire_attribut($balise, $attribut, true);

    if ($old !== null) {
        // Remplacer l'ancien attribut du meme nom
        $balise = $r[1].$insert.$r[5];
    } else {
        // preferer une balise " />" (comme <img />)
        if (preg_match(',/>,', $balise)) {
            $balise = preg_replace(",\s?/>,S", $insert.' />', $balise, 1);
        } // sinon une balise <a ...> ... </a>
        else {
            $balise = preg_replace(",\s?>,S", $insert.'>', $balise, 1);
        }
    }

    return $balise;
}

/**
 * Supprime un attribut HTML.
 *
 * @example `[(#LOGO_ARTICLE|vider_attribut{class})]`
 *
 * @filtre
 *
 * @link http://www.spip.net/4142
 *
 * @uses inserer_attribut()
 *
 * @see  extraire_attribut()
 *
 * @param string $balise   Code HTML de l'élément
 * @param string $attribut Nom de l'attribut à enlever
 *
 * @return string Code HTML sans l'attribut
 **/
function vider_attribut($balise, $attribut)
{
    return inserer_attribut($balise, $attribut, '', false, true);
}

/**
 * Un filtre pour déterminer le nom du statut des inscrits.
 *
 * @param void|int $id
 * @param string   $mode
 *
 * @return string
 */
function tester_config($id, $mode = '')
{
    include_spip('action/inscrire_auteur');

    return tester_statut_inscription($mode, $id);
}

//
// Quelques fonctions de calcul arithmetique
//

/**
 * Additionne 2 nombres.
 *
 * @filtre
 *
 * @link http://www.spip.net/4307
 * @see moins()
 *
 * @example
 *     ```
 *     [(#VAL{28}|plus{14})]
 *     ```
 *
 * @param int $a
 * @param int $b
 *
 * @return int $a+$b
 **/
function plus($a, $b)
{
    return $a + $b;
}

/**
 * Soustrait 2 nombres.
 *
 * @filtre
 *
 * @link http://www.spip.net/4302
 * @see plus()
 *
 * @example
 *     ```
 *     [(#VAL{28}|moins{14})]
 *     ```
 *
 * @param int $a
 * @param int $b
 *
 * @return int $a-$b
 **/
function moins($a, $b)
{
    return $a - $b;
}

/**
 * Multiplie 2 nombres.
 *
 * @filtre
 *
 * @link http://www.spip.net/4304
 * @see div()
 * @see modulo()
 *
 * @example
 *     ```
 *     [(#VAL{28}|mult{14})]
 *     ```
 *
 * @param int $a
 * @param int $b
 *
 * @return int $a*$b
 **/
function mult($a, $b)
{
    return $a * $b;
}

/**
 * Divise 2 nombres.
 *
 * @filtre
 *
 * @link http://www.spip.net/4279
 * @see mult()
 * @see modulo()
 *
 * @example
 *     ```
 *     [(#VAL{28}|div{14})]
 *     ```
 *
 * @param int $a
 * @param int $b
 *
 * @return int $a/$b (ou 0 si $b est nul)
 **/
function div($a, $b)
{
    return $b ? $a / $b : 0;
}

/**
 * Retourne le modulo 2 nombres.
 *
 * @filtre
 *
 * @link http://www.spip.net/4301
 * @see mult()
 * @see div()
 *
 * @example
 *     ```
 *     [(#VAL{28}|modulo{14})]
 *     ```
 *
 * @param int $nb
 * @param int $mod
 * @param int $add
 *
 * @return int ($nb % $mod) + $add
 **/
function modulo($nb, $mod, $add = 0)
{
    return ($mod ? $nb % $mod : 0) + $add;
}

/**
 * Vérifie qu'un nom (d'auteur) ne comporte pas d'autres tags que <multi>
 * et ceux volontairement spécifiés dans la constante.
 *
 * @param string $nom
 *                    Nom (signature) proposé
 *
 * @return bool
 *              - false si pas conforme,
 *              - true sinon
 **/
function nom_acceptable($nom)
{
    if (!is_string($nom)) {
        return false;
    }
    if (!defined('_TAGS_NOM_AUTEUR')) {
        define('_TAGS_NOM_AUTEUR', '');
    }
    $tags_acceptes = array_unique(explode(',', 'multi,'._TAGS_NOM_AUTEUR));
    foreach ($tags_acceptes as $tag) {
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

/**
 * Vérifier la conformité d'une ou plusieurs adresses email (suivant RFC 822).
 *
 * @param string $adresses
 *                         Adresse ou liste d'adresse
 *
 * @return bool|string
 *                     - false si pas conforme,
 *                     - la normalisation de la dernière adresse donnée sinon
 **/
function email_valide($adresses)
{
    // eviter d'injecter n'importe quoi dans preg_match
    if (!is_string($adresses)) {
        return false;
    }

    // Si c'est un spammeur autant arreter tout de suite
    if (preg_match(",[\n\r].*(MIME|multipart|Content-),i", $adresses)) {
        spip_log("Tentative d'injection de mail : $adresses");

        return false;
    }

    foreach (explode(',', $adresses) as $v) {
        // nettoyer certains formats
        // "Marie Toto <Marie@toto.com>"
        $adresse = trim(preg_replace(',^[^<>"]*<([^<>"]+)>$,i', '\\1', $v));
        // RFC 822
        if (!preg_match('#^[^()<>@,;:\\"/[:space:]]+(@([-_0-9a-z]+\.)*[-_0-9a-z]+)$#i', $adresse)) {
            return false;
        }
    }

    return $adresse;
}

/**
 * Permet d'afficher un symbole à côté des liens pointant vers les
 * documents attachés d'un article (liens ayant `rel=enclosure`).
 *
 * @filtre
 *
 * @link http://www.spip.net/4134
 *
 * @param string $tags Texte
 *
 * @return string Texte
 **/
function afficher_enclosures($tags)
{
    $s = array();
    foreach (extraire_balises($tags, 'a') as $tag) {
        if (extraire_attribut($tag, 'rel') == 'enclosure'
            and $t = extraire_attribut($tag, 'href')
        ) {
            $s[] = preg_replace(',>[^<]+</a>,S',
                '>'
                .http_img_pack('attachment-16.png', $t,
                    'title="'.attribut_html($t).'"')
                .'</a>', $tag);
        }
    }

    return implode('&nbsp;', $s);
}

/**
 * Filtre des liens HTML `<a>` selon la valeur de leur attribut `rel`
 * et ne retourne que ceux là.
 *
 * @filtre
 *
 * @link http://www.spip.net/4187
 *
 * @param string $tags Texte
 * @param string $rels Attribut `rel` à capturer (ou plusieurs séparés par des virgules)
 *
 * @return string Liens trouvés
 **/
function afficher_tags($tags, $rels = 'tag, directory')
{
    $s = array();
    foreach (extraire_balises($tags, 'a') as $tag) {
        $rel = extraire_attribut($tag, 'rel');
        if (strstr(",$rels,", ",$rel,")) {
            $s[] = $tag;
        }
    }

    return implode(', ', $s);
}

/**
 * Convertir les médias fournis par un flux RSS (podcasts)
 * en liens conformes aux microformats.
 *
 * Passe un `<enclosure url="fichier" length="5588242" type="audio/mpeg"/>`
 * au format microformat `<a rel="enclosure" href="fichier" ...>fichier</a>`.
 *
 * Peut recevoir un `<link` ou un `<media:content` parfois.
 *
 * Attention : `length="zz"` devient `title="zz"`, pour rester conforme.
 *
 * @filtre
 *
 * @see microformat2enclosure() Pour l'inverse
 *
 * @param string $e Tag RSS `<enclosure>`
 *
 * @return string Tag HTML `<a>` avec microformat.
 **/
function enclosure2microformat($e)
{
    if (!$url = filtrer_entites(extraire_attribut($e, 'url'))) {
        $url = filtrer_entites(extraire_attribut($e, 'href'));
    }
    $type = extraire_attribut($e, 'type');
    if (!$length = extraire_attribut($e, 'length')) {
        # <media:content : longeur dans fileSize. On tente.
        $length = extraire_attribut($e, 'fileSize');
    }
    $fichier = basename($url);

    return '<a rel="enclosure"'
    .($url ? ' href="'.spip_htmlspecialchars($url).'"' : '')
    .($type ? ' type="'.spip_htmlspecialchars($type).'"' : '')
    .($length ? ' title="'.spip_htmlspecialchars($length).'"' : '')
    .'>'.$fichier.'</a>';
}

/**
 * Convertir les liens conformes aux microformats en médias pour flux RSS,
 * par exemple pour les podcasts.
 *
 * Passe un texte ayant des liens avec microformat
 * `<a rel="enclosure" href="fichier" ...>fichier</a>`
 * au format RSS `<enclosure url="fichier" ... />`.
 *
 * @filtre
 *
 * @see enclosure2microformat() Pour l'inverse
 *
 * @param string $tags Texte HTML ayant des tag `<a>` avec microformat
 *
 * @return string Tags RSS `<enclosure>`.
 **/
function microformat2enclosure($tags)
{
    $enclosures = array();
    foreach (extraire_balises($tags, 'a') as $e) {
        if (extraire_attribut($e, 'rel') == 'enclosure') {
            $url = filtrer_entites(extraire_attribut($e, 'href'));
            $type = extraire_attribut($e, 'type');
            if (!$length = intval(extraire_attribut($e, 'title'))) {
                $length = intval(extraire_attribut($e, 'length'));
            } # vieux data
            $fichier = basename($url);
            $enclosures[] = '<enclosure'
                .($url ? ' url="'.spip_htmlspecialchars($url).'"' : '')
                .($type ? ' type="'.spip_htmlspecialchars($type).'"' : '')
                .($length ? ' length="'.$length.'"' : '')
                .' />';
        }
    }

    return implode("\n", $enclosures);
}

/**
 * Créer les éléments ATOM `<dc:subject>` à partir des tags.
 *
 * Convertit les liens avec attribut `rel="tag"`
 * en balise `<dc:subject></dc:subject>` pour les flux RSS au format Atom.
 *
 * @filtre
 *
 * @param string $tags Texte
 *
 * @return string Tags RSS Atom `<dc:subject>`.
 **/
function tags2dcsubject($tags)
{
    $subjects = '';
    foreach (extraire_balises($tags, 'a') as $e) {
        if (extraire_attribut($e, rel) == 'tag') {
            $subjects .= '<dc:subject>'
                .texte_backend(textebrut($e))
                .'</dc:subject>'."\n";
        }
    }

    return $subjects;
}

/**
 * Retourne la premiere balise html du type demandé.
 *
 * Retourne le contenu d'une balise jusqu'à la première fermeture rencontrée
 * du même type.
 * Si on a passe un tableau de textes, retourne un tableau de resultats.
 *
 * @example `[(#DESCRIPTIF|extraire_balise{img})]`
 *
 * @filtre
 *
 * @link http://www.spip.net/4289
 * @see extraire_balises()
 * @note
 *     Attention : les résultats peuvent être incohérents sur des balises imbricables,
 *     tel que demander à extraire `div` dans le texte `<div> un <div> mot </div> absent </div>`,
 *     ce qui retournerait `<div> un <div> mot </div>` donc.
 *
 * @param string|array $texte
 *                            Texte(s) dont on souhaite extraire une balise html
 * @param string       $tag
 *                            Nom de la balise html à extraire
 *
 * @return void|string|array
 *                           - Code html de la balise, sinon rien
 *                           - Tableau de résultats, si tableau en entrée.
 **/
function extraire_balise($texte, $tag = 'a')
{
    if (is_array($texte)) {
        array_walk(
            $texte,
            create_function('&$a,$key,$t', '$a = extraire_balise($a,$t);'),
            $tag
        );

        return $texte;
    }

    if (preg_match(
        ",<$tag\b[^>]*(/>|>.*</$tag\b[^>]*>|>),UimsS",
        $texte, $regs)) {
        return $regs[0];
    }
}

/**
 * Extrait toutes les balises html du type demandé.
 *
 * Retourne dans un tableau le contenu de chaque balise jusqu'à la première
 * fermeture rencontrée du même type.
 * Si on a passe un tableau de textes, retourne un tableau de resultats.
 *
 * @example `[(#TEXTE|extraire_balises{img}|implode{" - "})]`
 *
 * @filtre
 *
 * @link http://www.spip.net/5618
 * @see extraire_balise()
 * @note
 *     Attention : les résultats peuvent être incohérents sur des balises imbricables,
 *     tel que demander à extraire `div` dans un texte.
 *
 * @param string|array $texte
 *                            Texte(s) dont on souhaite extraire une balise html
 * @param string       $tag
 *                            Nom de la balise html à extraire
 *
 * @return array
 *               - Liste des codes html des occurrences de la balise, sinon tableau vide
 *               - Tableau de résultats, si tableau en entrée.
 **/
function extraire_balises($texte, $tag = 'a')
{
    if (is_array($texte)) {
        array_walk(
            $texte,
            create_function('&$a,$key,$t', '$a = extraire_balises($a,$t);'),
            $tag
        );

        return $texte;
    }

    if (preg_match_all(
        ",<${tag}\b[^>]*(/>|>.*</${tag}\b[^>]*>|>),UimsS",
        $texte, $regs, PREG_PATTERN_ORDER)) {
        return $regs[0];
    } else {
        return array();
    }
}

/**
 * Indique si le premier argument est contenu dans le second.
 *
 * Cette fonction est proche de `in_array()` en PHP avec comme principale
 * différence qu'elle ne crée pas d'erreur si le second argument n'est pas
 * un tableau (dans ce cas elle tentera de le désérialiser, et sinon retournera
 * la valeur par défaut transmise).
 *
 * @example `[(#VAL{deux}|in_any{#LISTE{un,deux,trois}}|oui) ... ]`
 *
 * @filtre
 *
 * @see filtre_find() Assez proche, avec les arguments valeur et tableau inversés.
 *
 * @param string       $val
 *                           Valeur à chercher dans le tableau
 * @param array|string $vals
 *                           Tableau des valeurs. S'il ce n'est pas un tableau qui est transmis,
 *                           la fonction tente de la désérialiser.
 * @param string       $def
 *                           Valeur par défaut retournée si `$vals` n'est pas un tableau.
 *
 * @return string
 *                - ' ' si la valeur cherchée est dans le tableau
 *                - '' si la valeur n'est pas dans le tableau
 *                - `$def` si on n'a pas transmis de tableau
 **/
function in_any($val, $vals, $def = '')
{
    if (!is_array($vals) and $v = unserialize($vals)) {
        $vals = $v;
    }

    return !is_array($vals) ? $def : (in_array($val, $vals) ? ' ' : '');
}

/**
 * Retourne le résultat d'une expression mathématique simple.
 *
 * N'accepte que les *, + et - (à ameliorer si on l'utilise vraiment).
 *
 * @filtre
 *
 * @example
 *      ```
 *      valeur_numerique("3*2") retourne 6
 *      ```
 *
 * @param string $expr
 *                     Expression mathématique `nombre operateur nombre` comme `3*2`
 *
 * @return int
 *             Résultat du calcul
 **/
function valeur_numerique($expr)
{
    $a = 0;
    if (preg_match(',^[0-9]+(\s*[+*-]\s*[0-9]+)*$,S', trim($expr))) {
        eval("\$a = $expr;");
    }

    return intval($a);
}

/**
 * Retourne un calcul de règle de trois.
 *
 * @filtre
 *
 * @example
 *     ```
 *     [(#VAL{6}|regledetrois{4,3})] retourne 8
 *     ```
 *
 * @param int $a
 * @param int $b
 * @param int $c
 *
 * @return int
 *             Retourne `$a*$b/$c`
 **/
function regledetrois($a, $b, $c)
{
    return round($a * $b / $c);
}

/**
 * Crée des tags HTML input hidden pour chaque paramètre et valeur d'une URL.
 *
 * Fournit la suite de Input-Hidden correspondant aux paramètres de
 * l'URL donnée en argument, compatible avec les types_urls
 *
 * @filtre
 *
 * @link http://www.spip.net/4286
 * @see balise_ACTION_FORMULAIRE()
 *     Également pour transmettre les actions à un formulaire
 *
 * @example
 *     ```
 *     [(#ENV{action}|form_hidden)] dans un formulaire
 *     ```
 *
 * @param string $action URL
 *
 * @return string Suite de champs input hidden
 **/
function form_hidden($action)
{
    $contexte = array();
    include_spip('inc/urls');
    if ($p = urls_decoder_url($action, '')
        and reset($p)
    ) {
        $fond = array_shift($p);
        if ($fond != '404') {
            $contexte = array_shift($p);
            $contexte['page'] = $fond;
            $action = preg_replace('/([?]'.preg_quote($fond).'[^&=]*[0-9]+)(&|$)/', '?&', $action);
        }
    }
    // defaire ce qu'a injecte urls_decoder_url : a revoir en modifiant la signature de urls_decoder_url
    if (defined('_DEFINIR_CONTEXTE_TYPE') and _DEFINIR_CONTEXTE_TYPE) {
        unset($contexte['type']);
    }
    if (defined('_DEFINIR_CONTEXTE_TYPE_PAGE') and _DEFINIR_CONTEXTE_TYPE_PAGE) {
        unset($contexte['type-page']);
    }

    // on va remplir un tableau de valeurs en prenant bien soin de ne pas
    // ecraser les elements de la forme mots[]=1&mots[]=2
    $values = array();

    // d'abord avec celles de l'url
    if (false !== ($p = strpos($action, '?'))) {
        foreach (preg_split('/&(amp;)?/S', substr($action, $p + 1)) as $c) {
            $c = explode('=', $c, 2);
            $var = array_shift($c);
            $val = array_shift($c);
            if ($var) {
                $val = rawurldecode($val);
                $var = rawurldecode($var); // decoder les [] eventuels
                if (preg_match(',\[\]$,S', $var)) {
                    $values[] = array($var, $val);
                } else {
                    if (!isset($values[$var])) {
                        $values[$var] = array($var, $val);
                    }
                }
            }
        }
    }

    // ensuite avec celles du contexte, sans doublonner !
    foreach ($contexte as $var => $val) {
        if (preg_match(',\[\]$,S', $var)) {
            $values[] = array($var, $val);
        } else {
            if (!isset($values[$var])) {
                $values[$var] = array($var, $val);
            }
        }
    }

    // puis on rassemble le tout
    $hidden = array();
    foreach ($values as $value) {
        list($var, $val) = $value;
        $hidden[] = '<input name="'
            .entites_html($var)
            .'"'
            .(is_null($val)
                ? ''
                : ' value="'.entites_html($val).'"'
            )
            .' type="hidden"'."\n/>";
    }

    return implode('', $hidden);
}

/**
 * Calcule les bornes d'une pagination.
 *
 * @filtre
 *
 * @param int $courante
 *                      Page courante
 * @param int $nombre
 *                      Nombre de pages
 * @param int $max
 *                      Nombre d'éléments par page
 *
 * @return int[]
 *               Liste (première page, dernière page).
 **/
function filtre_bornes_pagination_dist($courante, $nombre, $max = 10)
{
    if ($max <= 0 or $max >= $nombre) {
        return array(1, $nombre);
    }

    $premiere = max(1, $courante - floor(($max - 1) / 2));
    $derniere = min($nombre, $premiere + $max - 2);
    $premiere = $derniere == $nombre ? $derniere - $max + 1 : $premiere;

    return array($premiere, $derniere);
}

/**
 * Retourne la première valeur d'un tableau.
 *
 * Plus précisément déplace le pointeur du tableau sur la première valeur et la retourne.
 *
 * @example `[(#LISTE{un,deux,trois}|reset)]` retourne 'un'
 *
 * @filtre
 *
 * @link http://php.net/manual/fr/function.reset.php
 * @see filtre_end()
 *
 * @param array $array
 *
 * @return mixed|null|false
 *                          - null si $array n'est pas un tableau,
 *                          - false si le tableau est vide
 *                          - la première valeur du tableau sinon.
 **/
function filtre_reset($array)
{
    return !is_array($array) ? null : reset($array);
}

/**
 * Retourne la dernière valeur d'un tableau.
 *
 * Plus précisément déplace le pointeur du tableau sur la dernière valeur et la retourne.
 *
 * @example `[(#LISTE{un,deux,trois}|end)]` retourne 'trois'
 *
 * @filtre
 *
 * @link http://php.net/manual/fr/function.end.php
 * @see filtre_reset()
 *
 * @param array $array
 *
 * @return mixed|null|false
 *                          - null si $array n'est pas un tableau,
 *                          - false si le tableau est vide
 *                          - la dernière valeur du tableau sinon.
 **/
function filtre_end($array)
{
    return !is_array($array) ? null : end($array);
}

/**
 * Empile une valeur à la fin d'un tableau.
 *
 * @example `[(#LISTE{un,deux,trois}|push{quatre}|print)]`
 *
 * @filtre
 *
 * @link http://www.spip.net/4571
 * @link http://php.net/manual/fr/function.array-push.php
 *
 * @param array $array
 * @param mixed $val
 *
 * @return array|string
 *                      - '' si $array n'est pas un tableau ou si echec.
 *                      - le tableau complété de la valeur sinon.
 **/
function filtre_push($array, $val)
{
    if (!is_array($array) or !array_push($array, $val)) {
        return '';
    }

    return $array;
}

/**
 * Indique si une valeur est contenue dans un tableau.
 *
 * @example `[(#LISTE{un,deux,trois}|find{quatre}|oui) ... ]`
 *
 * @filtre
 *
 * @link http://www.spip.net/4575
 * @see in_any() Assez proche, avec les paramètres tableau et valeur inversés.
 *
 * @param array $array
 * @param mixed $val
 *
 * @return bool
 *              - `false` si `$array` n'est pas un tableau
 *              - `true` si la valeur existe dans le tableau, `false` sinon.
 **/
function filtre_find($array, $val)
{
    return is_array($array) and in_array($val, $array);
}

/**
 * Filtre calculant une pagination, utilisé par la balise `#PAGINATION`.
 *
 * Le filtre cherche le modèle `pagination.html` par défaut, mais peut
 * chercher un modèle de pagination particulier avec l'argument `$modele`.
 * S'il `$modele='prive'`, le filtre cherchera le modèle `pagination_prive.html`.
 *
 * @filtre
 *
 * @see balise_PAGINATION_dist()
 *
 * @param int    $total
 *                         Nombre total d'éléments
 * @param string $nom
 *                         Nom identifiant la pagination
 * @param int    $position
 *                         Page à afficher (tel que la 3è page)
 * @param int    $pas
 *                         Nombre d'éléments par page
 * @param bool   $liste
 *                         - True pour afficher toute la liste des éléments,
 *                         - False pour n'afficher que l'ancre
 * @param string $modele
 *                         Nom spécifique du modèle de pagination
 * @param string $connect
 *                         Nom du connecteur à la base de données
 * @param array  $env
 *                         Environnement à transmettre au modèle
 *
 * @return string
 *                Code HTML de la pagination
 **/
function filtre_pagination_dist(
    $total,
    $nom,
    $position,
    $pas,
    $liste = true,
    $modele = '',
    $connect = '',
    $env = array()
) {
    static $ancres = array();
    if ($pas < 1) {
        return '';
    }
    $ancre = 'pagination'.$nom; // #pagination_articles
    $debut = 'debut'.$nom; // 'debut_articles'

    // n'afficher l'ancre qu'une fois
    if (!isset($ancres[$ancre])) {
        $bloc_ancre = $ancres[$ancre] = "<a name='".$ancre."' id='".$ancre."'></a>";
    } else {
        $bloc_ancre = '';
    }
    // liste = false : on ne veut que l'ancre
    if (!$liste) {
        return $ancres[$ancre];
    }

    $pagination = array(
        'debut' => $debut,
        'url' => parametre_url(self(), 'fragment', ''), // nettoyer l'id ahah eventuel
        'total' => $total,
        'position' => intval($position),
        'pas' => $pas,
        'nombre_pages' => floor(($total - 1) / $pas) + 1,
        'page_courante' => floor(intval($position) / $pas) + 1,
        'ancre' => $ancre,
        'bloc_ancre' => $bloc_ancre,
    );
    if (is_array($env)) {
        $pagination = array_merge($env, $pagination);
    }

    // Pas de pagination
    if ($pagination['nombre_pages'] <= 1) {
        return '';
    }

    if ($modele) {
        $modele = '_'.$modele;
    }

    return recuperer_fond("modeles/pagination$modele", $pagination, array('trim' => true), $connect);
}

/**
 * Passer les url relatives à la css d'origine en url absolues.
 *
 * @uses suivre_lien()
 *
 * @param string $contenu
 *                        Contenu du fichier CSS
 * @param string $source
 *                        Chemin du fichier CSS
 *
 * @return string
 *                Contenu avec urls en absolus
 **/
function urls_absolues_css($contenu, $source)
{
    $path = suivre_lien(url_absolue($source), './');

    return preg_replace_callback(
        ",url\s*\(\s*['\"]?([^'\"/#\s][^:]*)['\"]?\s*\),Uims",
        create_function('$x',
            'return "url(\"".suivre_lien("'.$path.'",$x[1])."\")";'
        ), $contenu);
}

/**
 * Inverse le code CSS (left <--> right) d'une feuille de style CSS.
 *
 * Récupère le chemin d'une CSS existante et :
 *
 * 1. regarde si une CSS inversée droite-gauche existe dans le meme répertoire
 * 2. sinon la crée (ou la recrée) dans `_DIR_VAR/cache_css/`
 *
 * Si on lui donne à manger une feuille nommée `*_rtl.css` il va faire l'inverse.
 *
 * @filtre
 *
 * @example
 *     ```
 *     [<link rel="stylesheet" href="(#CHEMIN{css/perso.css}|direction_css)" type="text/css" />]
 *     ```
 *
 * @param string $css
 *                       Chemin vers le fichier CSS
 * @param string $voulue
 *                       Permet de forcer le sens voulu (en indiquant `ltr`, `rtl` ou un
 *                       code de langue). En absence, prend le sens de la langue en cours.
 *
 * @return string
 *                Chemin du fichier CSS inversé
 **/
function direction_css($css, $voulue = '')
{
    if (!preg_match(',(_rtl)?\.css$,i', $css, $r)) {
        return $css;
    }

    // si on a precise le sens voulu en argument, le prendre en compte
    if ($voulue = strtolower($voulue)) {
        if ($voulue != 'rtl' and $voulue != 'ltr') {
            $voulue = lang_dir($voulue);
        }
    } else {
        $voulue = lang_dir();
    }

    $r = count($r) > 1;
    $right = $r ? 'left' : 'right'; // 'right' de la css lue en entree
    $dir = $r ? 'rtl' : 'ltr';
    $ndir = $r ? 'ltr' : 'rtl';

    if ($voulue == $dir) {
        return $css;
    }

    if (
        // url absolue
        preg_match(',^http:,i', $css)
        // ou qui contient un ?
        or (($p = strpos($css, '?')) !== false)
    ) {
        $distant = true;
        $cssf = parse_url($css);
        $cssf = $cssf['path'].($cssf['query'] ? '?'.$cssf['query'] : '');
        $cssf = preg_replace(',[?:&=],', '_', $cssf);
    } else {
        $distant = false;
        $cssf = $css;
        // 1. regarder d'abord si un fichier avec la bonne direction n'est pas aussi
        //propose (rien a faire dans ce cas)
        $f = preg_replace(',(_rtl)?\.css$,i', '_'.$ndir.'.css', $css);
        if (@file_exists($f)) {
            return $f;
        }
    }

    // 2.
    $dir_var = sous_repertoire(_DIR_VAR, 'cache-css');
    $f = $dir_var
        .preg_replace(',.*/(.*?)(_rtl)?\.css,', '\1', $cssf)
        .'.'.substr(md5($cssf), 0, 4).'_'.$ndir.'.css';

    // la css peut etre distante (url absolue !)
    if ($distant) {
        include_spip('inc/distant');
        $contenu = recuperer_page($css);
        if (!$contenu) {
            return $css;
        }
    } else {
        if ((@filemtime($f) > @filemtime($css))
            and (_VAR_MODE != 'recalcul')
        ) {
            return $f;
        }
        if (!lire_fichier($css, $contenu)) {
            return $css;
        }
    }

    $contenu = str_replace(
        array('right', 'left', '@@@@L E F T@@@@'),
        array('@@@@L E F T@@@@', 'right', 'left'),
        $contenu);

    // reperer les @import auxquels il faut propager le direction_css
    preg_match_all(",\@import\s*url\s*\(\s*['\"]?([^'\"/][^:]*)['\"]?\s*\),Uims", $contenu, $regs);
    $src = array();
    $src_direction_css = array();
    $src_faux_abs = array();
    $d = dirname($css);
    foreach ($regs[1] as $k => $import_css) {
        $css_direction = direction_css("$d/$import_css", $voulue);
        // si la css_direction est dans le meme path que la css d'origine, on tronque le path, elle sera passee en absolue
        if (substr($css_direction, 0, strlen($d) + 1) == "$d/") {
            $css_direction = substr($css_direction, strlen($d) + 1);
        } // si la css_direction commence par $dir_var on la fait passer pour une absolue
        elseif (substr($css_direction, 0, strlen($dir_var)) == $dir_var) {
            $css_direction = substr($css_direction, strlen($dir_var));
            $src_faux_abs['/@@@@@@/'.$css_direction] = $css_direction;
            $css_direction = '/@@@@@@/'.$css_direction;
        }
        $src[] = $regs[0][$k];
        $src_direction_css[] = str_replace($import_css, $css_direction, $regs[0][$k]);
    }
    $contenu = str_replace($src, $src_direction_css, $contenu);

    $contenu = urls_absolues_css($contenu, $css);

    // virer les fausses url absolues que l'on a mis dans les import
    if (count($src_faux_abs)) {
        $contenu = str_replace(array_keys($src_faux_abs), $src_faux_abs, $contenu);
    }

    if (!ecrire_fichier($f, $contenu)) {
        return $css;
    }

    return $f;
}

/**
 * Transforme les urls relatives d'un fichier CSS en absolues.
 *
 * Récupère le chemin d'une css existante et crée (ou recrée) dans `_DIR_VAR/cache_css/`
 * une css dont les url relatives sont passées en url absolues
 *
 * Le calcul n'est pas refait si le fichier cache existe déjà et que
 * la source n'a pas été modifiée depuis.
 *
 * @uses recuperer_page() si l'URL source n'est pas sur le même site
 * @uses urls_absolues_css()
 *
 * @param string $css
 *                    Chemin ou URL du fichier CSS source
 *
 * @return string
 *                - Chemin du fichier CSS transformé (si source lisible et mise en cache réussie)
 *                - Chemin ou URL du fichier CSS source sinon.
 **/
function url_absolue_css($css)
{
    if (!preg_match(',\.css$,i', $css, $r)) {
        return $css;
    }

    $url_absolue_css = url_absolue($css);

    $f = basename($css, '.css');
    $f = sous_repertoire(_DIR_VAR, 'cache-css')
        .preg_replace(',(.*?)(_rtl|_ltr)?$,', '\\1-urlabs-'.substr(md5("$css-urlabs"), 0, 4).'\\2', $f)
        .'.css';

    if ((@filemtime($f) > @filemtime($css)) and (_VAR_MODE != 'recalcul')) {
        return $f;
    }

    if ($url_absolue_css == $css) {
        if (strncmp($GLOBALS['meta']['adresse_site'], $css, $l = strlen($GLOBALS['meta']['adresse_site'])) != 0
            or !lire_fichier(_DIR_RACINE.substr($css, $l), $contenu)
        ) {
            include_spip('inc/distant');
            if (!$contenu = recuperer_page($css)) {
                return $css;
            }
        }
    } elseif (!lire_fichier($css, $contenu)) {
        return $css;
    }

    // passer les url relatives a la css d'origine en url absolues
    $contenu = urls_absolues_css($contenu, $css);

    // ecrire la css
    if (!ecrire_fichier($f, $contenu)) {
        return $css;
    }

    return $f;
}

/**
 * Récupère la valeur d'une clé donnée
 * dans un tableau (ou un objet).
 *
 * @filtre
 *
 * @link http://www.spip.net/4572
 *
 * @example
 *     ```
 *     [(#VALEUR|table_valeur{cle/sous/element})]
 *     ```
 *
 * @param mixed  $table
 *                       Tableau ou objet PHP
 *                       (ou chaîne serialisée de tableau, ce qui permet d'enchaîner le filtre)
 * @param string $cle
 *                       Clé du tableau (ou paramètre public de l'objet)
 *                       Cette clé peut contenir des caractères / pour sélectionner
 *                       des sous éléments dans le tableau, tel que `sous/element/ici`
 *                       pour obtenir la valeur de `$tableau['sous']['element']['ici']`
 * @param mixed  $defaut
 *                       Valeur par defaut retournée si la clé demandée n'existe pas
 *
 * @return mixed
 *               Valeur trouvée ou valeur par défaut.
 **/
function table_valeur($table, $cle, $defaut = '')
{
    foreach (explode('/', $cle) as $k) {
        $table = is_string($table) ? @unserialize($table) : $table;

        if (is_object($table)) {
            $table = (($k !== '') and isset($table->$k)) ? $table->$k : $defaut;
        } elseif (is_array($table)) {
            $table = isset($table[$k]) ? $table[$k] : $defaut;
        } else {
            $table = $defaut;
        }
    }

    return $table;
}

/**
 * Retrouve un motif dans un texte à partir d'une expression régulière.
 *
 * S'appuie sur la fonction `preg_match()` en PHP
 *
 * @example
 *    - `[(#TITRE|match{toto})]`
 *    - `[(#TEXTE|match{^ceci$,Uims})]`
 *    - `[(#TEXTE|match{truc(...)$, UimsS, 1})]` Capture de la parenthèse indiquée
 *    - `[(#TEXTE|match{truc(...)$, 1})]` Équivalent, sans indiquer les modificateurs
 *
 * @filtre
 *
 * @link http://www.spip.net/4299
 * @link http://php.net/manual/fr/function.preg-match.php Pour des infos sur `preg_match()`
 *
 * @param string     $texte
 *                               Texte dans lequel chercher
 * @param string|int $expression
 *                               Expression régulière de recherche, sans le délimiteur
 * @param string     $modif
 *                               - string : Modificateurs de l'expression régulière
 *                               - int : Numéro de parenthèse capturante
 * @param int        $capte
 *                               Numéro de parenthèse capturante
 *
 * @return bool|string
 *                     - false : l'expression n'a pas été trouvée
 *                     - true : expression trouvée, mais pas la parenthèse capturante
 *                     - string : expression trouvée.
 **/
function match($texte, $expression, $modif = 'UimsS', $capte = 0)
{
    if (intval($modif) and $capte == 0) {
        $capte = $modif;
        $modif = 'UimsS';
    }
    $expression = str_replace("\/", '/', $expression);
    $expression = str_replace('/', "\/", $expression);

    if (preg_match('/'.$expression.'/'.$modif, $texte, $r)) {
        if (isset($r[$capte])) {
            return $r[$capte];
        } else {
            return true;
        }
    }

    return false;
}

/**
 * Remplacement de texte à base d'expression régulière.
 *
 * @filtre
 *
 * @link http://www.spip.net/4309
 * @see match()
 *
 * @example
 *     ```
 *     [(#TEXTE|replace{^ceci$,cela,UimsS})]
 *     ```
 *
 * @param string $texte
 *                           Texte
 * @param string $expression
 *                           Expression régulière
 * @param string $replace
 *                           Texte de substitution des éléments trouvés
 * @param string $modif
 *                           Modificateurs pour l'expression régulière.
 *
 * @return string
 *                Texte
 **/
function replace($texte, $expression, $replace = '', $modif = 'UimsS')
{
    $expression = str_replace("\/", '/', $expression);
    $expression = str_replace('/', "\/", $expression);

    return preg_replace('/'.$expression.'/'.$modif, $replace, $texte);
}

/**
 * Cherche les documents numerotés dans un texte traite par `propre()`.
 *
 * Affecte la liste des doublons['documents']
 *
 * @param array  $doublons
 *                         Liste des doublons
 * @param string $letexte
 *                         Le texte
 *
 * @return string
 *                Le texte
 **/
function traiter_doublons_documents(&$doublons, $letexte)
{

    // Verifier dans le texte & les notes (pas beau, helas)
    $t = $letexte.$GLOBALS['les_notes'];

    if (strstr($t, 'spip_document_') // evite le preg_match_all si inutile
        and preg_match_all(
            ',<[^>]+\sclass=["\']spip_document_([0-9]+)[\s"\'],imsS',
            $t, $matches, PREG_PATTERN_ORDER)
    ) {
        if (!isset($doublons['documents'])) {
            $doublons['documents'] = '';
        }
        $doublons['documents'] .= ','.implode(',', $matches[1]);
    }

    return $letexte;
}

/**
 * Filtre vide qui ne renvoie rien.
 *
 * @example
 *     `[(#CALCUL|vide)]` n'affichera pas le résultat du calcul
 * @filtre
 *
 * @param mixed $texte
 *
 * @return string Chaîne vide
 **/
function vide($texte)
{
    return '';
}

//
// Filtres pour le modele/emb (embed document)
//

/**
 * Écrit des balises HTML `<param...>` à partir d'un tableau de données tel que `#ENV`.
 *
 * Permet d'écrire les balises `<param>` à indiquer dans un `<object>`
 * en prenant toutes les valeurs du tableau transmis.
 *
 * Certaines clés spécifiques à SPIP et aux modèles embed sont omises :
 * id, lang, id_document, date, date_redac, align, fond, recurs, emb, dir_racine
 *
 * @example `[(#ENV*|env_to_params)]`
 *
 * @filtre
 *
 * @link http://www.spip.net/4005
 *
 * @param array|string $env
 *                                    Tableau cle => valeur des paramètres à écrire, ou chaine sérialisée de ce tableau
 * @param array        $ignore_params
 *                                    Permet de compléter les clés ignorées du tableau.
 *
 * @return string
 *                Code HTML résultant
 **/
function env_to_params($env, $ignore_params = array())
{
    $ignore_params = array_merge(
        array('id', 'lang', 'id_document', 'date', 'date_redac', 'align', 'fond', '', 'recurs', 'emb', 'dir_racine'),
        $ignore_params
    );
    if (!is_array($env)) {
        $env = unserialize($env);
    }
    $texte = '';
    if ($env) {
        foreach ($env as $i => $j) {
            if (is_string($j) and !in_array($i, $ignore_params)) {
                $texte .= "<param name='".$i."'\n\tvalue='".$j."' />";
            }
        }
    }

    return $texte;
}

/**
 * Écrit des attributs HTML à partir d'un tableau de données tel que `#ENV`.
 *
 * Permet d'écrire des attributs d'une balise HTML en utilisant les données du tableau transmis.
 * Chaque clé deviendra le nom de l'attribut (et la valeur, sa valeur)
 *
 * Certaines clés spécifiques à SPIP et aux modèles embed sont omises :
 * id, lang, id_document, date, date_redac, align, fond, recurs, emb, dir_racine
 *
 * @example `<embed src='#URL_DOCUMENT' [(#ENV*|env_to_attributs)] width='#GET{largeur}' height='#GET{hauteur}'></embed>`
 * @filtre
 *
 * @param array|string $env
 *                                    Tableau cle => valeur des attributs à écrire, ou chaine sérialisée de ce tableau
 * @param array        $ignore_params
 *                                    Permet de compléter les clés ignorées du tableau.
 *
 * @return string
 *                Code HTML résultant
 **/
function env_to_attributs($env, $ignore_params = array())
{
    $ignore_params = array_merge(
        array('id', 'lang', 'id_document', 'date', 'date_redac', 'align', 'fond', '', 'recurs', 'emb', 'dir_racine'),
        $ignore_params
    );
    if (!is_array($env)) {
        $env = unserialize($env);
    }
    $texte = '';
    if ($env) {
        foreach ($env as $i => $j) {
            if (is_string($j) and !in_array($i, $ignore_params)) {
                $texte .= $i."='".$j."' ";
            }
        }
    }

    return $texte;
}

/**
 * Concatène des chaînes.
 *
 * @filtre
 *
 * @link http://www.spip.net/4150
 *
 * @example
 *     ```
 *     #TEXTE|concat{texte1,texte2,...}
 *     ```
 *
 * @return string Chaînes concaténés
 **/
function concat()
{
    $args = func_get_args();

    return implode('', $args);
}

/**
 * Retourne le contenu d'un ou plusieurs fichiers.
 *
 * Les chemins sont cherchés dans le path de SPIP
 *
 * @see balise_INCLURE_dist() La balise `#INCLURE` peut appeler cette fonction
 *
 * @param array|string $files
 *                             - array : Liste de fichiers
 *                             - string : fichier ou fichiers séparés par `|`
 * @param bool         $script
 *                             - si true, considère que c'est un fichier js à chercher `javascript/`
 *
 * @return string
 *                Contenu du ou des fichiers, concaténé
 **/
function charge_scripts($files, $script = true)
{
    $flux = '';
    foreach (is_array($files) ? $files : explode('|', $files) as $file) {
        if (!is_string($file)) {
            continue;
        }
        if ($script) {
            $file = preg_match(",^\w+$,", $file) ? "javascript/$file.js" : '';
        }
        if ($file) {
            $path = find_in_path($file);
        }
        if ($path) {
            $flux .= spip_file_get_contents($path);
        }
    }

    return $flux;
}

/**
 * Produit une balise img avec un champ alt d'office si vide.
 *
 * Attention le htmlentities et la traduction doivent être appliqués avant.
 *
 * @param string $img
 * @param string $alt
 * @param string $atts
 * @param string $title
 * @param array  $options
 *                        chemin_image : utiliser chemin_image sur $img fourni, ou non (oui par dafaut)
 *                        utiliser_suffixe_size : utiliser ou non le suffixe de taille dans le nom de fichier de l'image
 *                        sous forme -xx.png (pour les icones essentiellement) (oui par defaut)
 *
 * @return string
 */
function http_img_pack($img, $alt, $atts = '', $title = '', $options = array())
{
    if (!isset($options['chemin_image']) or $options['chemin_image'] == true) {
        $img = chemin_image($img);
    }
    if (stripos($atts, 'width') === false) {
        // utiliser directement l'info de taille presente dans le nom
        if ((!isset($options['utiliser_suffixe_size']) or $options['utiliser_suffixe_size'] == true)
            and preg_match(',-([0-9]+)[.](png|gif)$,', $img, $regs)
        ) {
            $largeur = $hauteur = intval($regs[1]);
        } else {
            $taille = taille_image($img);
            list($hauteur, $largeur) = $taille;
            if (!$hauteur or !$largeur) {
                return '';
            }
        }
        $atts .= " width='".$largeur."' height='".$hauteur."'";
    }

    return "<img src='$img' alt='".attribut_html($alt ? $alt : $title)."'"
    .($title ? ' title="'.attribut_html($title).'"' : '')
    .' '.ltrim($atts)
    .' />';
}

/**
 * Générer une directive `style='background:url()'` à partir d'un fichier image.
 *
 * @param string $img
 * @param string $att
 *
 * @return string
 */
function http_style_background($img, $att = '')
{
    return " style='background".($att ? '' : '-image').': url("'.chemin_image($img).'")'.($att ? (' '.$att) : '').";'";
}

/**
 * Générer une balise HTML `img` à partir d'un nom de fichier.
 *
 * @uses http_img_pack()
 *
 * @param string $img
 * @param string $alt
 * @param string $class
 *
 * @return string
 *                Code HTML de la balise IMG
 */
function filtre_balise_img_dist($img, $alt = '', $class = '')
{
    return http_img_pack($img, $alt, $class ? " class='".attribut_html($class)."'" : '', '',
        array('chemin_image' => false, 'utiliser_suffixe_size' => false));
}

/**
 * Affiche chaque valeur d'un tableau associatif en utilisant un modèle.
 *
 * @example
 *     - `[(#ENV*|unserialize|foreach)]`
 *     - `[(#ARRAY{a,un,b,deux}|foreach)]`
 *
 * @filtre
 *
 * @link http://www.spip.net/4248
 *
 * @param array  $tableau
 *                        Tableau de données à afficher
 * @param string $modele
 *                        Nom du modèle à utiliser
 *
 * @return string
 *                Code HTML résultant
 **/
function filtre_foreach_dist($tableau, $modele = 'foreach')
{
    $texte = '';
    if (is_array($tableau)) {
        foreach ($tableau as $k => $v) {
            $res = recuperer_fond('modeles/'.$modele,
                array_merge(array('cle' => $k), (is_array($v) ? $v : array('valeur' => $v)))
            );
            $texte .= $res;
        }
    }

    return $texte;
}

/**
 * Obtient des informations sur les plugins actifs.
 *
 * @filtre
 *
 * @uses liste_plugin_actifs() pour connaître les informations affichables
 *
 * @param string $plugin
 *                          Préfixe du plugin ou chaîne vide
 * @param string $type_info
 *                          Type d'info demandée
 * @param bool   $reload
 *                          true (à éviter) pour forcer le recalcul du cache des informations des plugins.
 *
 * @return array|string|bool
 *
 *     - Liste sérialisée des préfixe de plugins actifs (si $plugin = '')
 *     - Suivant $type_info, avec $plugin un préfixe
 *         - est_actif : renvoie true s'il est actif, false sinon
 *         - x : retourne l'information x du plugin si présente (et plugin actif)
 *         - tout : retourne toutes les informations du plugin actif
 **/
function filtre_info_plugin_dist($plugin, $type_info, $reload = false)
{
    include_spip('inc/plugin');
    $plugin = strtoupper($plugin);
    $plugins_actifs = liste_plugin_actifs();

    if (!$plugin) {
        return serialize(array_keys($plugins_actifs));
    } elseif (empty($plugins_actifs[$plugin])) {
        return '';
    } elseif ($type_info == 'est_actif') {
        return $plugins_actifs[$plugin] ? 1 : 0;
    } elseif (isset($plugins_actifs[$plugin][$type_info])) {
        return $plugins_actifs[$plugin][$type_info];
    } else {
        $get_infos = charger_fonction('get_infos', 'plugins');
        // On prend en compte les extensions
        if (!is_dir($plugins_actifs[$plugin]['dir_type'])) {
            $dir_plugins = constant($plugins_actifs[$plugin]['dir_type']);
        } else {
            $dir_plugins = $plugins_actifs[$plugin]['dir_type'];
        }
        if (!$infos = $get_infos($plugins_actifs[$plugin]['dir'], $reload, $dir_plugins)) {
            return '';
        }
        if ($type_info == 'tout') {
            return $infos;
        } else {
            return strval($infos[$type_info]);
        }
    }
}

/**
 * Affiche la puce statut d'un objet, avec un menu rapide pour changer
 * de statut si possibilité de l'avoir.
 *
 * @see inc_puce_statut_dist()
 *
 * @filtre
 *
 * @param int    $id_objet
 *                            Identifiant de l'objet
 * @param string $statut
 *                            Statut actuel de l'objet
 * @param int    $id_rubrique
 *                            Identifiant du parent
 * @param string $type
 *                            Type d'objet
 * @param bool   $ajax
 *                            Indique s'il ne faut renvoyer que le coeur du menu car on est
 *                            dans une requete ajax suite à un post de changement rapide
 *
 * @return string
 *                Code HTML de l'image de puce de statut à insérer (et du menu de changement si présent)
 */
function puce_changement_statut($id_objet, $statut, $id_rubrique, $type, $ajax = false)
{
    $puce_statut = charger_fonction('puce_statut', 'inc');

    return $puce_statut($id_objet, $statut, $id_rubrique, $type, $ajax);
}

/**
 * Affiche la puce statut d'un objet, avec un menu rapide pour changer
 * de statut si possibilité de l'avoir.
 *
 * Utilisable sur tout objet qui a declaré ses statuts
 *
 * @example
 *     [(#STATUT|puce_statut{article})] affiche une puce passive
 *     [(#STATUT|puce_statut{article,#ID_ARTICLE,#ID_RUBRIQUE})] affiche une puce avec changement rapide
 *
 * @see inc_puce_statut_dist()
 *
 * @filtre
 *
 * @param string $statut
 *                          Statut actuel de l'objet
 * @param string $objet
 *                          Type d'objet
 * @param int    $id_objet
 *                          Identifiant de l'objet
 * @param int    $id_parent
 *                          Identifiant du parent
 *
 * @return string
 *                Code HTML de l'image de puce de statut à insérer (et du menu de changement si présent)
 */
function filtre_puce_statut_dist($statut, $objet, $id_objet = 0, $id_parent = 0)
{
    static $puce_statut = null;
    if (!$puce_statut) {
        $puce_statut = charger_fonction('puce_statut', 'inc');
    }

    return $puce_statut($id_objet, $statut, $id_parent, $objet, false,
        objet_info($objet, 'editable') ? _ACTIVER_PUCE_RAPIDE : false);
}

/**
 * Encoder un contexte pour l'ajax.
 *
 * Encoder le contexte, le signer avec une clé, le crypter
 * avec le secret du site, le gziper si possible.
 *
 * L'entrée peut-être sérialisée (le `#ENV**` des fonds ajax et ajax_stat)
 *
 * @see  decoder_contexte_ajax()
 *
 * @uses calculer_cle_action()
 *
 * @param string|array $c
 *                              contexte, peut etre un tableau serialize
 * @param string       $form
 *                              nom du formulaire eventuel
 * @param string       $emboite
 *                              contenu a emboiter dans le conteneur ajax
 * @param string       $ajaxid
 *                              ajaxid pour cibler le bloc et forcer sa mise a jour
 *
 * @return string
 *                hash du contexte
 */
function encoder_contexte_ajax($c, $form = '', $emboite = null, $ajaxid = '')
{
    if (is_string($c)
        and @unserialize($c) !== false
    ) {
        $c = unserialize($c);
    }

    // supprimer les parametres debut_x
    // pour que la pagination ajax ne soit pas plantee
    // si on charge la page &debut_x=1 : car alors en cliquant sur l'item 0,
    // le debut_x=0 n'existe pas, et on resterait sur 1
    if (is_array($c)) {
        foreach ($c as $k => $v) {
            if (strpos($k, 'debut_') === 0) {
                unset($c[$k]);
            }
        }
    }

    if (!function_exists('calculer_cle_action')) {
        include_spip('inc/securiser_action');
    }
    $cle = calculer_cle_action($form.(is_array($c) ? serialize($c) : $c));
    $c = serialize(array($c, $cle));

    // on ne stocke pas les contextes dans des fichiers caches
    // par defaut, sauf si cette configuration a ete forcee
    // OU que la longueur de l''argument generee est plus long
    // que ce que telere Suhosin.
    $cache_contextes_ajax = (defined('_CACHE_CONTEXTES_AJAX') and _CACHE_CONTEXTES_AJAX);
    if (!$cache_contextes_ajax) {
        $env = $c;
        if (function_exists('gzdeflate') && function_exists('gzinflate')) {
            $env = gzdeflate($env);
            // http://core.spip.net/issues/2667 | https://bugs.php.net/bug.php?id=61287
            if (substr(phpversion(), 0, 5) == '5.4.0' and !@gzinflate($env)) {
                $cache_contextes_ajax = true;
                spip_log('Contextes AJAX forces en fichiers ! Erreur PHP 5.4.0', _LOG_AVERTISSEMENT);
            }
        }
        $env = _xor($env);
        $env = base64_encode($env);
        // tester Suhosin et la valeur maximale des variables en GET...
        if ($max_len = @ini_get('suhosin.get.max_value_length')
            and $max_len < ($len = strlen($env))
        ) {
            $cache_contextes_ajax = true;
            spip_log('Contextes AJAX forces en fichiers !'
                .' Cela arrive lorsque la valeur du contexte'
                .' depasse la longueur maximale autorisee par Suhosin'
                ." ($max_len) dans 'suhosin.get.max_value_length'. Ici : $len."
                .' Vous devriez modifier les parametres de Suhosin'
                .' pour accepter au moins 1024 caracteres.', _LOG_AVERTISSEMENT);
        }
    }

    if ($cache_contextes_ajax) {
        $dir = sous_repertoire(_DIR_CACHE, 'contextes');
        // stocker les contextes sur disque et ne passer qu'un hash dans l'url
        $md5 = md5($c);
        ecrire_fichier("$dir/c$md5", $c);
        $env = $md5;
    }

    if ($emboite === null) {
        return $env;
    }
    if (!trim($emboite)) {
        return '';
    }
    // toujours encoder l'url source dans le bloc ajax
    $r = self();
    $r = ' data-origin="'.$r.'"';
    $class = 'ajaxbloc';
    if ($ajaxid and is_string($ajaxid)) {
        $class .= ' ajax-id-'.$ajaxid;
    }

    return "<div class='$class' "."data-ajax-env='$env'$r>\n$emboite</div><!--ajaxbloc-->\n";
}

/**
 * Décoder un hash de contexte pour l'ajax.
 *
 * Précude inverse de `encoder_contexte_ajax()`
 *
 * @see  encoder_contexte_ajax()
 *
 * @uses calculer_cle_action()
 *
 * @param string $c
 *                     hash du contexte
 * @param string $form
 *                     nom du formulaire eventuel
 *
 * @return array|string|bool
 *                           - array|string : contexte d'environnement, possiblement sérialisé
 *                           - false : erreur de décodage
 */
function decoder_contexte_ajax($c, $form = '')
{
    if (!function_exists('calculer_cle_action')) {
        include_spip('inc/securiser_action');
    }
    if (((defined('_CACHE_CONTEXTES_AJAX') and _CACHE_CONTEXTES_AJAX) or strlen($c) == 32)
        and $dir = sous_repertoire(_DIR_CACHE, 'contextes')
        and lire_fichier("$dir/c$c", $contexte)
    ) {
        $c = $contexte;
    } else {
        $c = @base64_decode($c);
        $c = _xor($c);
        if (function_exists('gzdeflate') && function_exists('gzinflate')) {
            $c = @gzinflate($c);
        }
    }
    list($env, $cle) = @unserialize($c);

    if ($cle == calculer_cle_action($form.(is_array($env) ? serialize($env) : $env))) {
        return $env;
    }

    return false;
}

/**
 * Encrypte ou décrypte un message.
 *
 * @link http://www.php.net/manual/fr/language.operators.bitwise.php#81358
 *
 * @param string      $message
 *                             Message à encrypter ou décrypter
 * @param null|string $key
 *                             Clé de cryptage / décryptage.
 *                             Une clé sera calculée si non transmise
 *
 * @return string
 *                Message décrypté ou encrypté
 **/
function _xor($message, $key = null)
{
    if (is_null($key)) {
        if (!function_exists('calculer_cle_action')) {
            include_spip('inc/securiser_action');
        }
        $key = pack('H*', calculer_cle_action('_xor'));
    }

    $keylen = strlen($key);
    $messagelen = strlen($message);
    for ($i = 0; $i < $messagelen; ++$i) {
        $message[$i] = ~($message[$i] ^ $key[$i % $keylen]);
    }

    return $message;
}

/**
 * Retourne une URL de réponse de forum (aucune action ici).
 *
 * @see filtre_url_reponse_forum() du plugin forum (prioritaire)
 * @note
 *   La vraie fonction est dans le plugin forum,
 *   mais on évite ici une erreur du compilateur en absence du plugin
 *
 * @param string $texte
 *
 * @return string
 */
function url_reponse_forum($texte)
{
    return $texte;
}

/**
 * retourne une URL de suivi rss d'un forum (aucune action ici).
 *
 * @see filtre_url_rss_forum() du plugin forum (prioritaire)
 * @note
 *   La vraie fonction est dans le plugin forum,
 *   mais on évite ici une erreur du compilateur en absence du plugin
 *
 * @param string $texte
 *
 * @return string
 */
function url_rss_forum($texte)
{
    return $texte;
}

/**
 * Génère des menus avec liens ou `<strong class='on'>` non clicable lorsque
 * l'item est sélectionné.
 *
 * @filtre
 *
 * @link http://www.spip.net/4004
 *
 * @example
 *   ```
 *   [(#URL_RUBRIQUE|lien_ou_expose{#TITRE, #ENV{test}|=={en_cours}})]
 *   ```
 *
 * @param string $url
 *                        URL du lien
 * @param string $libelle
 *                        Texte du lien
 * @param bool   $on
 *                        État exposé (génère un strong) ou non (génère un lien)
 * @param string $class
 *                        Classes CSS ajoutées au lien
 * @param string $title
 *                        Title ajouté au lien
 * @param string $rel
 *                        Attribut `rel` ajouté au lien
 * @param string $evt
 *                        Complement à la balise `a` pour gérer un événement javascript,
 *                        de la forme ` onclick='...'`
 *
 * @return string
 *                Code HTML
 */
function lien_ou_expose($url, $libelle = null, $on = false, $class = '', $title = '', $rel = '', $evt = '')
{
    if ($on) {
        $bal = 'strong';
        $att = "class='on'";
    } else {
        $bal = 'a';
        $att = "href='$url'"
            .($title ? " title='".attribut_html($title)."'" : '')
            .($class ? " class='".attribut_html($class)."'" : '')
            .($rel ? " rel='".attribut_html($rel)."'" : '')
            .$evt;
    }
    if ($libelle === null) {
        $libelle = $url;
    }

    return "<$bal $att>$libelle</$bal>";
}

/**
 * Afficher un message "un truc"/"N trucs"
 * Les items sont à indiquer comme pour la fonction _T() sous la forme :
 * "module:chaine".
 *
 * @param int    $nb               : le nombre
 * @param string $chaine_un        : l'item de langue si $nb vaut un
 * @param string $chaine_plusieurs : l'item de lanque si $nb > 1
 * @param string $var              : La variable à remplacer par $nb dans l'item de langue (facultatif, défaut "nb")
 * @param array  $vars             : Les autres variables nécessaires aux chaines de langues (facultatif)
 *
 * @return string : la chaine de langue finale en utilisant la fonction _T()
 */
function singulier_ou_pluriel($nb, $chaine_un, $chaine_plusieurs, $var = 'nb', $vars = array())
{
    if (!$nb = intval($nb)) {
        return '';
    }
    if (!is_array($vars)) {
        return '';
    }
    $vars[$var] = $nb;
    if ($nb > 1) {
        return _T($chaine_plusieurs, $vars);
    } else {
        return _T($chaine_un, $vars);
    }
}

/**
 * Fonction de base pour une icone dans un squelette
 * structure html : `<span><a><img><b>texte</b></span>`.
 *
 * @param string $type
 *                           'lien' ou 'bouton'
 * @param string $lien
 *                           url
 * @param string $texte
 *                           texte du lien / alt de l'image
 * @param string $fond
 *                           objet avec ou sans son extension et sa taille (article, article-24, article-24.png)
 * @param string $fonction
 *                           new/del/edit
 * @param string $class
 *                           classe supplementaire (horizontale, verticale, ajax ...)
 * @param string $javascript
 *                           "onclick='...'" par exemple
 *
 * @return string
 */
function prepare_icone_base($type, $lien, $texte, $fond, $fonction = '', $class = '', $javascript = '')
{
    if (in_array($fonction, array('del', 'supprimer.gif'))) {
        $class .= ' danger';
    } elseif ($fonction == 'rien.gif') {
        $fonction = '';
    } elseif ($fonction == 'delsafe') {
        $fonction = 'del';
    }

    // remappage des icone : article-24.png+new => article-new-24.png
    if ($icone_renommer = charger_fonction('icone_renommer', 'inc', true)) {
        list($fond, $fonction) = $icone_renommer($fond, $fonction);
    }

    // ajouter le type d'objet dans la class de l'icone
    $class .= ' '.substr(basename($fond), 0, -4);

    $alt = attribut_html($texte);
    $title = " title=\"$alt\""; // est-ce pertinent de doubler le alt par un title ?

    $ajax = '';
    if (strpos($class, 'ajax') !== false) {
        $ajax = 'ajax';
        if (strpos($class, 'preload') !== false) {
            $ajax .= ' preload';
        }
        if (strpos($class, 'nocache') !== false) {
            $ajax .= ' nocache';
        }
        $ajax = " class='$ajax'";
    }

    $size = 24;
    if (preg_match('/-([0-9]{1,3})[.](gif|png)$/i', $fond, $match)) {
        $size = $match[1];
    }

    if ($fonction) {
        // 2 images pour composer l'icone : le fond (article) en background,
        // la fonction (new) en image
        $icone = http_img_pack($fonction, $alt, "width='$size' height='$size'\n".
            http_style_background($fond));
    } else {
        $icone = http_img_pack($fond, $alt, "width='$size' height='$size'");
    }

    if ($type == 'lien') {
        return "<span class='icone s$size $class'>"
        ."<a href='$lien'$title$ajax$javascript>"
        .$icone
        ."<b>$texte</b>"
        ."</a></span>\n";
    } else {
        return bouton_action("$icone<b>$texte</b>", $lien, "icone s$size $class", $javascript, $alt);
    }
}

/**
 * Crée un lien ayant une icone.
 *
 * @uses prepare_icone_base()
 *
 * @param string $lien
 *                           URL du lien
 * @param string $texte
 *                           Texte du lien
 * @param string $fond
 *                           Objet avec ou sans son extension et sa taille (article, article-24, article-24.png)
 * @param string $fonction
 *                           Fonction du lien (`edit`, `new`, `del`)
 * @param string $class
 *                           Classe CSS, tel que `left`, `right` pour définir un alignement
 * @param string $javascript
 *                           Javascript ajouté sur le lien
 *
 * @return string
 *                Code HTML du lien
 **/
function icone_base($lien, $texte, $fond, $fonction = '', $class = '', $javascript = '')
{
    return prepare_icone_base('lien', $lien, $texte, $fond, $fonction, $class, $javascript);
}

/**
 * Crée un lien précédé d'une icone au dessus du texte.
 *
 * @uses icone_base()
 *
 * @see  icone_verticale() Pour un usage dans un code PHP.
 *
 * @filtre
 *
 * @example
 *     ```
 *     [(#AUTORISER{voir,groupemots,#ID_GROUPE})
 *         [(#URL_ECRIRE{groupe_mots,id_groupe=#ID_GROUPE}
 *            |icone_verticale{<:mots:icone_voir_groupe_mots:>,groupe_mots-24.png,'',left})]
 *    ]
 *     ```
 *
 * @param string $lien
 *                           URL du lien
 * @param string $texte
 *                           Texte du lien
 * @param string $fond
 *                           Objet avec ou sans son extension et sa taille (article, article-24, article-24.png)
 * @param string $fonction
 *                           Fonction du lien (`edit`, `new`, `del`)
 * @param string $class
 *                           Classe CSS à ajouter, tel que `left`, `right`, `center` pour définir un alignement.
 *                           Il peut y en avoir plusieurs : `left ajax`
 * @param string $javascript
 *                           Javascript ajouté sur le lien
 *
 * @return string
 *                Code HTML du lien
 **/
function filtre_icone_verticale_dist($lien, $texte, $fond, $fonction = '', $class = '', $javascript = '')
{
    return icone_base($lien, $texte, $fond, $fonction, "verticale $class", $javascript);
}

/**
 * Crée un lien précédé d'une icone horizontale.
 *
 * @uses icone_base()
 *
 * @see  icone_horizontale() Pour un usage dans un code PHP.
 *
 * @filtre
 *
 * @example
 *     En tant que filtre dans un squelettes :
 *     ```
 *     [(#URL_ECRIRE{sites}|icone_horizontale{<:sites:icone_voir_sites_references:>,site-24.png})]
 *
 *     [(#AUTORISER{supprimer,groupemots,#ID_GROUPE}|oui)
 *         [(#URL_ACTION_AUTEUR{supprimer_groupe_mots,#ID_GROUPE,#URL_ECRIRE{mots}}
 *             |icone_horizontale{<:mots:icone_supprimer_groupe_mots:>,groupe_mots,del})]
 *     ]
 *     ```
 *
 *     En tant que filtre dans un code php :
 *     ```
 *     $icone_horizontale=chercher_filtre('icone_horizontale');
 *     $icone = $icone_horizontale(generer_url_ecrire("stats_visites","id_article=$id_article"),
 *         _T('statistiques:icone_evolution_visites', array('visites' => $visites)),
 *         "statistique-24.png");
 *     ```
 *
 * @param string $lien
 *                           URL du lien
 * @param string $texte
 *                           Texte du lien
 * @param string $fond
 *                           Objet avec ou sans son extension et sa taille (article, article-24, article-24.png)
 * @param string $fonction
 *                           Fonction du lien (`edit`, `new`, `del`)
 * @param string $class
 *                           Classe CSS à ajouter
 * @param string $javascript
 *                           Javascript ajouté sur le lien
 *
 * @return string
 *                Code HTML du lien
 **/
function filtre_icone_horizontale_dist($lien, $texte, $fond, $fonction = '', $class = '', $javascript = '')
{
    return icone_base($lien, $texte, $fond, $fonction, "horizontale $class", $javascript);
}

/**
 * Crée un bouton d'action intégrant une icone horizontale.
 *
 * @uses prepare_icone_base()
 *
 * @filtre
 *
 * @example
 *     ```
 *     [(#URL_ACTION_AUTEUR{supprimer_mot, #ID_MOT, #URL_ECRIRE{groupe_mots,id_groupe=#ID_GROUPE}}
 *         |bouton_action_horizontal{<:mots:info_supprimer_mot:>,mot-24.png,del})]
 *     ```
 *
 * @param string $lien
 *                         URL de l'action
 * @param string $texte
 *                         Texte du bouton
 * @param string $fond
 *                         Objet avec ou sans son extension et sa taille (article, article-24, article-24.png)
 * @param string $fonction
 *                         Fonction du bouton (`edit`, `new`, `del`)
 * @param string $class
 *                         Classe CSS à ajouter
 * @param string $confirm
 *                         Message de confirmation à ajouter en javascript sur le bouton
 *
 * @return string
 *                Code HTML du lien
 **/
function filtre_bouton_action_horizontal_dist($lien, $texte, $fond, $fonction = '', $class = '', $confirm = '')
{
    return prepare_icone_base('bouton', $lien, $texte, $fond, $fonction, "horizontale $class", $confirm);
}

/**
 * Filtre `icone` pour compatibilité mappé sur `icone_base`.
 *
 * @uses icone_base()
 *
 * @see  filtre_icone_verticale_dist()
 *
 * @filtre
 *
 * @deprecated Utiliser le filtre `icone_verticale`
 *
 * @param string $lien
 *                           URL du lien
 * @param string $texte
 *                           Texte du lien
 * @param string $fond
 *                           Nom de l'image utilisée
 * @param string $align
 *                           Classe CSS d'alignement (`left`, `right`, `center`)
 * @param string $fonction
 *                           Fonction du lien (`edit`, `new`, `del`)
 * @param string $class
 *                           Classe CSS à ajouter
 * @param string $javascript
 *                           Javascript ajouté sur le lien
 *
 * @return string
 *                Code HTML du lien
 */
function filtre_icone_dist($lien, $texte, $fond, $align = '', $fonction = '', $class = '', $javascript = '')
{
    return icone_base($lien, $texte, $fond, $fonction, "verticale $align $class", $javascript);
}

/**
 * Explose un texte en tableau suivant un séparateur.
 *
 * @note
 *     Inverse l'écriture de la fonction PHP de même nom
 *     pour que le filtre soit plus pratique dans les squelettes
 *
 * @filtre
 *
 * @example
 *     ```
 *     [(#GET{truc}|explode{-})]
 *     ```
 *
 * @param string $a Texte
 * @param string $b Séparateur
 *
 * @return array Liste des éléments
 */
function filtre_explode_dist($a, $b)
{
    return explode($b, $a);
}

/**
 * Implose un tableau en chaine en liant avec un séparateur.
 *
 * @note
 *     Inverse l'écriture de la fonction PHP de même nom
 *     pour que le filtre soit plus pratique dans les squelettes
 *
 * @filtre
 *
 * @example
 *     ```
 *     [(#GET{truc}|implode{-})]
 *     ```
 *
 * @param array  $a Tableau
 * @param string $b Séparateur
 *
 * @return string Texte
 */
function filtre_implode_dist($a, $b)
{
    return is_array($a) ? implode($b, $a) : $a;
}

/**
 * Produire les styles privés qui associent item de menu avec icone en background.
 *
 * @return string Code CSS
 */
function bando_images_background()
{
    include_spip('inc/bandeau');
    // recuperer tous les boutons et leurs images
    $boutons = definir_barre_boutons(definir_barre_contexte(), true, false);

    $res = '';
    foreach ($boutons as $page => $detail) {
        if ($detail->icone and strlen(trim($detail->icone))) {
            $res .= "\n.navigation_avec_icones #bando1_$page {background-image:url(".$detail->icone.');}';
        }
        $selecteur = (in_array($page, array('outils_rapides', 'outils_collaboratifs')) ? '' : '.navigation_avec_icones ');
        if (is_array($detail->sousmenu)) {
            foreach ($detail->sousmenu as $souspage => $sousdetail) {
                if ($sousdetail->icone and strlen(trim($sousdetail->icone))) {
                    $res .= "\n$selecteur.bando2_$souspage {background-image:url(".$sousdetail->icone.');}';
                }
            }
        }
    }

    return $res;
}

/**
 * Generer un bouton_action
 * utilise par #BOUTON_ACTION.
 *
 * @param string $libelle
 * @param string $url
 * @param string $class
 * @param string $confirm
 *                         message de confirmation oui/non avant l'action
 * @param string $title
 * @param string $callback
 *                         callback js a appeler lors de l'evenement action (apres confirmation eventuelle si $confirm est non vide)
 *                         et avant execution de l'action. Si la callback renvoie false, elle annule le declenchement de l'action
 *
 * @return string
 */
function bouton_action($libelle, $url, $class = '', $confirm = '', $title = '', $callback = '')
{
    if ($confirm) {
        $confirm = 'confirm("'.attribut_html($confirm).'")';
        if ($callback) {
            $callback = "$confirm?($callback):false";
        } else {
            $callback = $confirm;
        }
    }
    $onclick = $callback ? " onclick='return ".addcslashes($callback, "'")."'" : '';
    $title = $title ? " title='$title'" : '';

    return "<form class='bouton_action_post $class' method='post' action='$url'><div>".form_hidden($url)
    ."<button type='submit' class='submit'$title$onclick>$libelle</button></div></form>";
}

/**
 * Proteger les champs passes dans l'url et utiliser dans {tri ...}
 * preserver l'espace pour interpreter ensuite num xxx et multi xxx.
 *
 * @param string $t
 *
 * @return string
 */
function tri_protege_champ($t)
{
    return preg_replace(',[^\s\w.+],', '', $t);
}

/**
 * Interpreter les multi xxx et num xxx utilise comme tri
 * pour la clause order
 * 'multi xxx' devient simplement 'multi' qui est calcule dans le select.
 *
 * @param string $t
 * @param array  $from
 *
 * @return string
 */
function tri_champ_order($t, $from = null)
{
    if (strncmp($t, 'multi ', 6) == 0) {
        return 'multi';
    }

    $champ = $t;

    if (strncmp($t, 'num ', 4) == 0) {
        $champ = substr($t, 4);
    }
    // enlever les autres espaces non evacues par tri_protege_champ
    $champ = preg_replace(',\s,', '', $champ);

    if (is_array($from)) {
        $trouver_table = charger_fonction('trouver_table', 'base');
        foreach ($from as $idt => $table_sql) {
            if ($desc = $trouver_table($table_sql)
                and isset($desc['field'][$champ])
            ) {
                $champ = "$idt.$champ";
                break;
            }
        }
    }
    if (strncmp($t, 'num ', 4) == 0) {
        return "0+$champ";
    } else {
        return $champ;
    }
}

/**
 * Interpreter les multi xxx et num xxx utilise comme tri
 * pour la clause select
 * 'multi xxx' devient select "...." as multi
 * les autres cas ne produisent qu'une chaine vide '' en select
 * 'hasard' devient 'rand() AS hasard' dans le select.
 *
 * @param string $t
 *
 * @return string
 */
function tri_champ_select($t)
{
    if (strncmp($t, 'multi ', 6) == 0) {
        $t = substr($t, 6);
        $t = preg_replace(',\s,', '', $t);
        $t = sql_multi($t, $GLOBALS['spip_lang']);

        return $t;
    }
    if (trim($t) == 'hasard') {
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
 * @param int    $id_objet
 * @param string $type_objet
 * @param string $info
 * @param string $etoile
 *
 * @return string
 */
function generer_info_entite($id_objet, $type_objet, $info, $etoile = '')
{
    static $trouver_table = null;
    static $objets;

    // On verifie qu'on a tout ce qu'il faut
    $id_objet = intval($id_objet);
    if (!($id_objet and $type_objet and $info)) {
        return '';
    }

    // si on a deja note que l'objet n'existe pas, ne pas aller plus loin
    if (isset($objets[$type_objet]) and $objets[$type_objet] === false) {
        return '';
    }

    // Si on demande l'url, on retourne direct la fonction
    if ($info == 'url') {
        return generer_url_entite($id_objet, $type_objet);
    }

    // Sinon on va tout chercher dans la table et on garde en memoire
    $demande_titre = ($info == 'titre');

    // On ne fait la requete que si on a pas deja l'objet ou si on demande le titre mais qu'on ne l'a pas encore
    if (!isset($objets[$type_objet][$id_objet])
        or
        ($demande_titre and !isset($objets[$type_objet][$id_objet]['titre']))
    ) {
        if (!$trouver_table) {
            $trouver_table = charger_fonction('trouver_table', 'base');
        }
        $desc = $trouver_table(table_objet_sql($type_objet));
        if (!$desc) {
            return $objets[$type_objet] = false;
        }

        // Si on demande le titre, on le gere en interne
        $champ_titre = '';
        if ($demande_titre) {
            // si pas de titre declare mais champ titre, il sera peuple par le select *
            $champ_titre = (!empty($desc['titre'])) ? ', '.$desc['titre'] : '';
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
    if ($generer = charger_fonction("generer_${info}_${type_objet}", '', true)) {
        $info_generee = $generer($id_objet, $objets[$type_objet][$id_objet]);
    } // Si la fonction generer_TRUC_entite existe, on l'utilise pour formater $info_generee
    else {
        if ($generer = charger_fonction("generer_${info}_entite", '', true)) {
            $info_generee = $generer($id_objet, $type_objet, $objets[$type_objet][$id_objet]);
        } // Sinon on prend directement le champ SQL tel quel
        else {
            $info_generee = (isset($objets[$type_objet][$id_objet][$info]) ? $objets[$type_objet][$id_objet][$info] : '');
        }
    }

    // On va ensuite appliquer les traitements automatiques si besoin
    if (!$etoile) {
        // FIXME: on fournit un ENV minimum avec id et type et connect=''
        // mais ce fonctionnement est a ameliorer !
        $info_generee = appliquer_traitement_champ($info_generee, $info, table_objet($type_objet),
            array('id_objet' => $id_objet, 'objet' => $type_objet, ''));
    }

    return $info_generee;
}

/**
 * Appliquer a un champ SQL le traitement qui est configure pour la balise homonyme dans les squelettes.
 *
 * @param string $texte
 * @param string $champ
 * @param string $table_objet
 * @param array  $env
 * @param string $connect
 *
 * @return string
 */
function appliquer_traitement_champ($texte, $champ, $table_objet = '', $env = array(), $connect = '')
{
    if (!$champ) {
        return $texte;
    }

    $champ = strtoupper($champ);
    $traitements = isset($GLOBALS['table_des_traitements'][$champ]) ? $GLOBALS['table_des_traitements'][$champ] : false;
    if (!$traitements or !is_array($traitements)) {
        return $texte;
    }

    $traitement = '';
    if ($table_objet and (!isset($traitements[0]) or count($traitements) > 1)) {
        // necessaire pour prendre en charge les vieux appels avec un table_objet_sql en 3e arg
        $table_objet = table_objet($table_objet);
        if (isset($traitements[$table_objet])) {
            $traitement = $traitements[$table_objet];
        }
    }
    if (!$traitement and isset($traitements[0])) {
        $traitement = $traitements[0];
    }
    // (sinon prendre le premier de la liste par defaut ?)

    if (!$traitement) {
        return $texte;
    }

    $traitement = str_replace('%s', "'".texte_script($texte)."'", $traitement);

    // Fournir $connect et $Pile[0] au traitement si besoin
    $Pile = array(0 => $env);
    eval("\$texte = $traitement;");

    return $texte;
}

/**
 * Generer un lien (titre clicable vers url) vers un objet.
 *
 * @param int $id_objet
 * @param $objet
 * @param int         $longueur
 * @param null|string $connect
 *
 * @return string
 */
function generer_lien_entite($id_objet, $objet, $longueur = 80, $connect = null)
{
    include_spip('inc/liens');
    $titre = traiter_raccourci_titre($id_objet, $objet, $connect);
    // lorsque l'objet n'est plus declare (plugin desactive par exemple)
    // le raccourcis n'est plus valide
    $titre = isset($titre['titre']) ? typo($titre['titre']) : '';
    // on essaye avec generer_info_entite ?
    if (!strlen($titre) and !$connect) {
        $titre = generer_info_entite($id_objet, $objet, 'titre');
    }
    if (!strlen($titre)) {
        $titre = _T('info_sans_titre');
    }
    $url = generer_url_entite($id_objet, $objet, '', '', $connect);

    return "<a href='$url' class='$objet'>".couper($titre, $longueur).'</a>';
}

/**
 * Englobe (Wrap) un texte avec des balises.
 *
 * @example `wrap('mot','<b>')` donne `<b>mot</b>'`
 *
 * @filtre
 *
 * @uses extraire_balises()
 *
 * @param string $texte
 * @param string $wrap
 *
 * @return string
 */
function wrap($texte, $wrap)
{
    $balises = extraire_balises($wrap);
    if (preg_match_all(",<([a-z]\w*)\b[^>]*>,UimsS", $wrap, $regs, PREG_PATTERN_ORDER)) {
        $texte = $wrap.$texte;
        $regs = array_reverse($regs[1]);
        $wrap = '</'.implode('></', $regs).'>';
        $texte = $texte.$wrap;
    }

    return $texte;
}

/**
 * afficher proprement n'importe quoi
 * On affiche in fine un pseudo-yaml qui premet de lire humainement les tableaux et de s'y reperer.
 *
 * Les textes sont retournes avec simplement mise en forme typo
 *
 * le $join sert a separer les items d'un tableau, c'est en general un \n ou <br /> selon si on fait du html ou du texte
 * les tableaux-listes (qui n'ont que des cles numeriques), sont affiches sous forme de liste separee par des virgules :
 * c'est VOULU !
 *
 * @param $u
 * @param string $join
 * @param int    $indent
 *
 * @return array|mixed|string
 */
function filtre_print_dist($u, $join = '<br />', $indent = 0)
{
    if (is_string($u)) {
        $u = typo($u);

        return $u;
    }

    // caster $u en array si besoin
    if (is_object($u)) {
        $u = (array) $u;
    }

    if (is_array($u)) {
        $out = '';
        // toutes les cles sont numeriques ?
        // et aucun enfant n'est un tableau
        // liste simple separee par des virgules
        $numeric_keys = array_map('is_numeric', array_keys($u));
        $array_values = array_map('is_array', $u);
        $object_values = array_map('is_object', $u);
        if (array_sum($numeric_keys) == count($numeric_keys)
            and !array_sum($array_values)
            and !array_sum($object_values)
        ) {
            return implode(', ', array_map('filtre_print_dist', $u));
        }

        // sinon on passe a la ligne et on indente
        $i_str = str_pad('', $indent, ' ');
        foreach ($u as $k => $v) {
            $out .= $join.$i_str."$k: ".filtre_print_dist($v, $join, $indent + 2);
        }

        return $out;
    }

    // on sait pas quoi faire...
    return $u;
}

/**
 * Renvoyer l'info d'un objet
 * telles que definies dans declarer_tables_objets_sql.
 *
 * @param string $objet
 * @param string $info
 *
 * @return string
 */
function objet_info($objet, $info)
{
    $table = table_objet_sql($objet);
    $infos = lister_tables_objets_sql($table);

    return isset($infos[$info]) ? $infos[$info] : '';
}

/**
 * Filtre pour afficher 'Aucun truc' ou '1 truc' ou 'N trucs'
 * avec la bonne chaîne de langue en fonction de l'objet utilisé.
 *
 * @param int    $nb
 *                      Nombre d'éléments
 * @param string $objet
 *                      Objet
 *
 * @return mixed|string
 *                      Texte traduit du comptage, tel que '3 articles'
 */
function objet_afficher_nb($nb, $objet)
{
    if (!$nb) {
        return _T(objet_info($objet, 'info_aucun_objet'));
    } else {
        return _T(objet_info($objet, $nb == 1 ? 'info_1_objet' : 'info_nb_objets'), array('nb' => $nb));
    }
}

/**
 * Filtre pour afficher l'img icone d'un objet.
 *
 * @param string $objet
 * @param int    $taille
 *
 * @return string
 */
function objet_icone($objet, $taille = 24)
{
    $icone = objet_info($objet, 'icone_objet').'-'.$taille.'.png';
    $icone = chemin_image($icone);
    $balise_img = charger_filtre('balise_img');

    return $icone ? $balise_img($icone, _T(objet_info($objet, 'texte_objet'))) : '';
}

/**
 * Fonction de secours pour inserer le head_css de facon conditionnelle.
 *
 * Appelée en filtre sur le squelette qui contient #INSERT_HEAD,
 * elle vérifie l'absence éventuelle de #INSERT_HEAD_CSS et y suplée si besoin
 * pour assurer la compat avec les squelettes qui n'utilisent pas.
 *
 * @param string $flux Code HTML
 *
 * @return string Code HTML
 */
function insert_head_css_conditionnel($flux)
{
    if (strpos($flux, '<!-- insert_head_css -->') === false
        and $p = strpos($flux, '<!-- insert_head -->')
    ) {
        // plutot avant le premier js externe (jquery) pour etre non bloquant
        if ($p1 = stripos($flux, '<script src=') and $p1 < $p) {
            $p = $p1;
        }
        $flux = substr_replace($flux, pipeline('insert_head_css', '<!-- insert_head_css -->'), $p, 0);
    }

    return $flux;
}

/**
 * Produire un fichier statique à partir d'un squelette dynamique.
 *
 * Permet ensuite à Apache de le servir en statique sans repasser
 * par spip.php à chaque hit sur le fichier.
 *
 * Si le format (css ou js) est passe dans `contexte['format']`, on l'utilise
 * sinon on regarde si le fond finit par .css ou .js, sinon on utilie "html"
 *
 * @uses urls_absolues_css()
 *
 * @param string $fond
 * @param array  $contexte
 * @param array  $options
 * @param string $connect
 *
 * @return string
 */
function produire_fond_statique($fond, $contexte = array(), $options = array(), $connect = '')
{
    if (isset($contexte['format'])) {
        $extension = $contexte['format'];
        unset($contexte['format']);
    } else {
        $extension = 'html';
        if (preg_match(',[.](css|js|json)$,', $fond, $m)) {
            $extension = $m[1];
        }
    }
    // recuperer le contenu produit par le squelette
    $options['raw'] = true;
    $cache = recuperer_fond($fond, $contexte, $options, $connect);

    // calculer le nom de la css
    $dir_var = sous_repertoire(_DIR_VAR, 'cache-'.$extension);
    $nom_safe = preg_replace(",\W,", '_', str_replace('.', '_', $fond));
    $filename = $dir_var.$extension."dyn-$nom_safe-".substr(md5($fond.serialize($contexte).$connect), 0,
            8).".$extension";

    // mettre a jour le fichier si il n'existe pas
    // ou trop ancien
    // le dernier fichier produit est toujours suffixe par .last
    // et recopie sur le fichier cible uniquement si il change
    if (!file_exists($filename)
        or !file_exists($filename.'.last')
        or (isset($cache['lastmodified']) and $cache['lastmodified'] and filemtime($filename.'.last') < $cache['lastmodified'])
        or (defined('_VAR_MODE') and _VAR_MODE == 'recalcul')
    ) {
        $contenu = $cache['texte'];
        // passer les urls en absolu si c'est une css
        if ($extension == 'css') {
            $contenu = urls_absolues_css($contenu,
                test_espace_prive() ? generer_url_ecrire('accueil') : generer_url_public($fond));
        }

        // ne pas insérer de commentaire si c'est du json
        if ($extension != 'json') {
            $comment = "/* #PRODUIRE{fond=$fond";
            foreach ($contexte as $k => $v) {
                $comment .= ",$k=$v";
            }
            // pas de date dans le commentaire car sinon ca invalide le md5 et force la maj
            // mais on peut mettre un md5 du contenu, ce qui donne un aperu rapide si la feuille a change ou non
            $comment .= "}\n   md5:".md5($contenu)." */\n";
        }
        // et ecrire le fichier
        ecrire_fichier($filename.'.last', $comment.$contenu);
        // regarder si on recopie
        if (!file_exists($filename)
            or md5_file($filename) !== md5_file($filename.'.last')
        ) {
            @copy($filename.'.last', $filename);
            spip_clearstatcache(true, $filename); // eviter que PHP ne reserve le vieux timestamp
        }
    }

    return $filename;
}

/**
 * Ajouter un timestamp a une url de fichier
 * [(#CHEMIN{monfichier}|timestamp)].
 *
 * @param string $fichier
 *                        Le chemin du fichier sur lequel on souhaite ajouter le timestamp
 *
 * @return string
 *                $fichier auquel on a ajouté le timestamp
 */
function timestamp($fichier)
{
    if (!$fichier
        or !file_exists($fichier)
        or !$m = filemtime($fichier)
    ) {
        return $fichier;
    }

    return "$fichier?$m";
}

/**
 * Supprimer le timestamp d'une url.
 *
 * @param string $url
 *
 * @return string
 */
function supprimer_timestamp($url)
{
    if (strpos($url, '?') === false) {
        return $url;
    }

    return preg_replace(",\?[[:digit:]]+$,", '', $url);
}

/**
 * Nettoyer le titre d'un email.
 *
 * Éviter une erreur lorsqu'on utilise `|nettoyer_titre_email` dans un squelette de mail
 *
 * @filtre
 *
 * @uses nettoyer_titre_email()
 *
 * @param string $titre
 *
 * @return string
 */
function filtre_nettoyer_titre_email_dist($titre)
{
    include_spip('inc/envoyer_mail');

    return nettoyer_titre_email($titre);
}

/**
 * Afficher le sélecteur de rubrique.
 *
 * Il permet de placer un objet dans la hiérarchie des rubriques de SPIP
 *
 * @uses chercher_rubrique()
 *
 * @param string $titre
 * @param int    $id_objet
 * @param int    $id_parent
 * @param string $objet
 * @param int    $id_secteur
 * @param bool   $restreint
 * @param bool   $actionable
 *                                  true : fournit le selecteur dans un form directement postable
 * @param bool   $retour_sans_cadre
 *
 * @return string
 */
function filtre_chercher_rubrique_dist(
    $titre,
    $id_objet,
    $id_parent,
    $objet,
    $id_secteur,
    $restreint,
    $actionable = false,
    $retour_sans_cadre = false
) {
    include_spip('inc/filtres_ecrire');

    return chercher_rubrique($titre, $id_objet, $id_parent, $objet, $id_secteur, $restreint, $actionable,
        $retour_sans_cadre);
}

/**
 * Rediriger une page suivant une autorisation,
 * et ce, n'importe où dans un squelette, même dans les inclusions.
 *
 * En l'absence de redirection indiquée, la fonction redirige par défaut
 * sur une 403 dans l'espace privé et 404 dans l'espace public.
 *
 * @example
 *     ```
 *     [(#AUTORISER{non}|sinon_interdire_acces)]
 *     [(#AUTORISER{non}|sinon_interdire_acces{#URL_PAGE{login}, 401})]
 *     ```
 *
 * @filtre
 *
 * @param bool   $ok
 *                        Indique si l'on doit rediriger ou pas
 * @param string $url
 *                        Adresse eventuelle vers laquelle rediriger
 * @param int    $statut
 *                        Statut HTML avec lequel on redirigera
 * @param string $message
 *                        message d'erreur
 *
 * @return string|void
 *                     Chaîne vide si l'accès est autorisé
 */
function sinon_interdire_acces($ok = false, $url = '', $statut = 0, $message = null)
{
    if ($ok) {
        return '';
    }

    // Vider tous les tampons
    $level = @ob_get_level();
    while ($level--) {
        @ob_end_clean();
    }

    include_spip('inc/headers');

    // S'il y a une URL, on redirige (si pas de statut, la fonction mettra 302 par défaut)
    if ($url) {
        redirige_par_entete($url, '', $statut);
    }

    // ecriture simplifiee avec message en 3eme argument (= statut 403)
    if (!is_numeric($statut) and is_null($message)) {
        $message = $statut;
        $statut = 0;
    }
    if (!$message) {
        $message = '';
    }
    $statut = intval($statut);

    // Si on est dans l'espace privé, on génère du 403 Forbidden par defaut ou du 404
    if (test_espace_prive()) {
        if (!$statut or !in_array($statut, array(404, 403))) {
            $statut = 403;
        }
        http_status(403);
        $echec = charger_fonction('403', 'exec');
        $echec($message);
    } else {
        // Sinon dans l'espace public on redirige vers une 404 par défaut, car elle toujours présente normalement
        if (!$statut) {
            $statut = 404;
        }
        // Dans tous les cas on modifie l'entité avec ce qui est demandé
        http_status($statut);
        // Si le statut est une erreur et qu'il n'y a pas de redirection on va chercher le squelette du même nom
        if ($statut >= 400) {
            echo recuperer_fond("$statut", array('erreur' => $message));
        }
    }

    exit;
}

/**
 * Assurer le fonctionnement de |compacte meme sans l'extension compresseur.
 *
 * @param string      $source
 * @param null|string $format
 *
 * @return string
 */
function filtre_compacte_dist($source, $format = null)
{
    if (function_exists('compacte')) {
        return compacte($source, $format);
    }

    return $source;
}
