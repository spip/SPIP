<?php

/**
 * Fichier permettant de phraser les XML des fichiers paquet.xml et plugin.xml
 * ainsi que des fichiers décrivant le contenu d'un dépot de paquets.
 * 
 * @plugin SVP pour SPIP
 * @license GPL
 * @package SPIP\SVP\Plugins
**/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/xml');
include_spip('inc/config');

if (!defined('_SVP_MODE_RUNTIME')) {
/**
 * Mode d'utilisation de SVP runtime ou pas :
 * - En mode runtime (true), on ne charge que les plugins compatibles avec la version courante
 * - En mode non runtime (false) on charge tous les plugins : cas du site Plugins SPIP
 * Runtime est le mode par defaut
 * @var bool */
	define('_SVP_MODE_RUNTIME', (lire_config('svp/mode_runtime', 'oui') == 'oui' ? true : false));
}


// Type parseur XML à appliquer pour récupérer les infos du plugin
/** @var string  Phraseur à utiliser pour un XML de plugin.xml */
define('_SVP_DTD_PLUGIN', 'plugin'); 
/** @var string  Phraseur à utiliser pour un XML de paquet.xml */
define('_SVP_DTD_PAQUET', 'paquet'); 

// Regexp de recherche des balises principales de archives.xml
define('_SVP_REGEXP_BALISE_DEPOT', '#<depot[^>]*>(.*)</depot>#Uims');
define('_SVP_REGEXP_BALISE_ARCHIVES', '#<archives[^>]*>(.*)</archives>#Uims');
define('_SVP_REGEXP_BALISE_ARCHIVE', '#<archive[^s][^>]*>(.*)</archive>#Uims');
define('_SVP_REGEXP_BALISE_ZIP', '#<zip[^>]*>(.*)</zip>#Uims');
define('_SVP_REGEXP_BALISE_TRADUCTIONS', '#<traductions[^>]*>(.*)</traductions>#Uims');
define('_SVP_REGEXP_BALISE_PLUGIN', '#<plugin[^>]*>(.*)</plugin>#Uims');
define('_SVP_REGEXP_BALISE_PAQUET', '#<paquet[^>]*>(.*)</paquet>#Uims');
define('_SVP_REGEXP_BALISE_MULTIS', '#<multis[^>]*>(.*)</multis>#Uims');


// Liste des categories de plugin
# define('_CATEGORIES_PLUGIN', serialize($categories_plugin));
$GLOBALS['categories_plugin'] = array(
	'communication',
	'edition',
	'multimedia',
	'navigation',
	'date',
	'divers',
	'auteur',
	'statistique',
	'performance',
	'maintenance',
	'outil',
	'theme',
	'squelette',
	'aucune'
);

/** Liste des balises techniques autorisées dans la balise <spip> */
$GLOBALS['balises_techniques'] = array(
	'menu', 'chemin', 'lib', 'necessite', 'onglet', 'procure', 'pipeline', 'utilise',
	'options', 'fonctions', 'install');
# define('_BALISES_TECHNIQUES', serialize($balises_techniques));
	
/** Liste des balises autorisant une traduction */
$GLOBALS['balises_multis'] = array(
	'nom', 'slogan', 'description');
# define('_BALISES_MULTIS', serialize($balises_multis));


/**
 * Phrase un fichier décrivant un dépot, dont le chemin local est donné
 * 
 * Le fichier est au format XML et contient deux balises principales :
 * - <depot>...</depot> : informations de description du depot (facultatif)
 * - <archives>...</archives> : liste des informations sur chaque archive (obligatoire)
 *
 * La fonction met en cache le résultat du phrasage de chaque archive et ne
 * rephrase que les archives ayant changées.
 * 
 * @param string $fichier_xml
 *     Chemin local du fichier XML de description du dépot
 * @return array|bool
 *     false si erreur,
 *     Tableau de 2 index sinon :
 *     - depot : description du dépot
 *     - paquets : 
 */
function svp_phraser_depot($fichier_xml) {

	// le fichier xml fournit sous forme de fichier
	lire_fichier($fichier_xml,$xml);

	// Initialisation du tableau des informations
	// -- Si aucun bloc depot n'est trouve le titre et le type prennent une valeur par defaut
	$infos = array(
				'depot' => array(
							'titre' => _T('svp:titre_nouveau_depot'), 
							'type' => 'manuel'),
				'paquets' => array());


	// Extraction et phrasage du bloc depot si il existe
	// -- Si le bloc <depot> n'est pas renseigne on ne considere pas cela comme une erreur
	$balises_depot = array('titre', 'descriptif', 'type', 'url_serveur', 'url_brouteur', 'url_archives', 'url_commits');
	if (preg_match(_SVP_REGEXP_BALISE_DEPOT, $xml, $matches)) {
		if (is_array($arbre_depot = spip_xml_parse($matches[1]))) {
			$infos['depot'] = svp_aplatir_balises($balises_depot, $arbre_depot, 'nonvide', $infos['depot']);
		}
	}

	// Extraction et phrasage du bloc des archives si il existe
	// -- Le bloc <archives> peut etre une chaine de grande taille et provoquer une erreur 
	// sur une recherche de regexp. On ne teste donc pas l'existence de cette balise
	// -- Si aucun bloc <archive> c'est aussi une erreur
	if (!preg_match_all(_SVP_REGEXP_BALISE_ARCHIVE, $xml, $matches))
		return false;

	// lire le cache des md5 pour ne parser que ce qui a change
	$fichier_xml_md5 = $fichier_xml . ".md5.txt";
	lire_fichier($fichier_xml_md5,$cache_md5);
	if (!$cache_md5
	  OR !$cache_md5 = unserialize($cache_md5))
		$cache_md5 = array();

	$infos['paquets'] = svp_phraser_archives($matches[0], $cache_md5);
	ecrire_fichier($fichier_xml_md5,serialize($cache_md5));

	// -- Si aucun paquet extrait c'est aussi une erreur
	if (!$infos['paquets'])
		return false;

	return $infos;
}


/**
 * Phrase la liste des balises <archive>
 *
 * Chaque bloc XML est constitue de 3 sous-blocs principaux :
 * - <zip> : contient les balises d'information sur le zip (obligatoire)
 * - <traductions> : contient la compilation des informations de traduction (facultatif)
 * - <plugin> ou <paquet> suivant la DTD : le contenu du fichier plugin.xml ou paquet.xml (facultatif)
 *
 * @param array $archives
 *     Tableau de la liste des archives trouvées dans la description d'un dépot
 * @param array $md5_cache
 *     Tableau des descriptions d'archives déjà connues : on supprime
 *     à la fin celles qui ne font plus parties du dépot.
 * @return array
 *     Tableau décrivant chaque archive, avec en index l'url de l'archive.
 *     Tableau (url => Tableau de description de l'archive)
 */
function svp_phraser_archives($archives,&$md5_cache=array()) {
	include_spip('inc/plugin');
	$seen = array();

	$paquets = array();
	$version_spip = $GLOBALS['spip_version_branche'].".".$GLOBALS['spip_version_code'];

	// On verifie qu'il existe au moins une archive
	if (!$archives)
		return $paquets;

	// On phrase chacune des archives
	// Seul le bloc <zip> est obligatoire
	foreach ($archives as $_cle => $_archive){
		// quand version spip ou mode runtime changent,
		// il faut mettre le xml a jour pour voir les plugins compatibles ou non
		$md5 = md5($_archive.":$version_spip:"._SVP_MODE_RUNTIME);
		if (isset($md5_cache[$md5])){
			if (is_array($p=$md5_cache[$md5]))
				$paquets[$p['file']] = $p; // ce paquet est connu
			$seen[] = $md5;
		}
		elseif (preg_match(_SVP_REGEXP_BALISE_ZIP, $_archive, $matches)) {

			// Extraction de la balise <zip>
			$zip = svp_phraser_zip($matches[1]);

			if ($zip) {
				
				// Extraction de la balise traductions
				$traductions = array();
				if (preg_match(_SVP_REGEXP_BALISE_TRADUCTIONS, $_archive, $matches))
					$traductions = svp_phraser_traductions($matches[1]);

				
				// La balise <archive> peut posseder un attribut qui precise la DTD utilisee pour les plugins (plugin ou paquet)
				// Sinon, c'est la DTD plugin qui est utilisee
				list($tag, $attributs) = spip_xml_decompose_tag($_archive);
				// -- On stocke la DTD d'extraction des infos du plugin
				$dtd = (isset($attributs['dtd']) AND $attributs['dtd']) ? $attributs['dtd'] : _SVP_DTD_PLUGIN;

				// Extraction *des balises* plugin ou *de la balise* paquet suivant la DTD et la version SPIP
				// -- DTD : si on utilise plugin.xml on extrait la balise <plugin> sinon la balise <paquet>
				$xml = svp_phraser_plugin($dtd, $_archive);
			
				// Si on est en mode runtime, on est seulement interesse par les plugins compatibles avec
				// la version courant de SPIP. On ne stocke donc pas les autres plugins.
				// Si on est pas en mode runtime on prend tout !
				if (!_SVP_MODE_RUNTIME
				OR (_SVP_MODE_RUNTIME AND isset($xml['compatibilite']) AND plugin_version_compatible($xml['compatibilite'], $version_spip, 'spip'))) {
					$paquets[$zip['file']] = $zip;
					$paquets[$zip['file']]['traductions'] = $traductions;
					$paquets[$zip['file']]['dtd'] = $dtd;
					$paquets[$zip['file']]['plugin'] = $xml;
					$paquets[$zip['file']]['md5'] = $md5;
					$md5_cache[$md5] = $paquets[$zip['file']];
					$seen[] = $md5;
				}
				else{
					$md5_cache[$md5] = $zip['file'];
					$seen[] = $md5;
				}
			}
		}
	}

	// supprimer du cache les zip qui ne sont pas dans le nouveau $archives
	$oldies = array_diff(array_keys($md5_cache),$seen);
	foreach ($oldies as $old_md5){
		unset($md5_cache[$old_md5]);
	}

	return $paquets;
}



/**
 * Phrase le contenu du XML décrivant une archive suivant une DTD
 * de plugin.xml ou de paquet.xml donnée
 *
 * La fonction peut-être appelée via archives.xml ou via un xml de plugin.
 * Elle phrase la balise <multi> dans le cas d'une DTD paquet qui contient
 * les traductions du nom, slogan et description
 *
 * @global $balises_multis
 * 
 * @param string $dtd
 *     Nom du type de dtd : plugin ou paquet (pour phraser un plugin.xml ou un paquet.xml)
 * @param string $contenu
 *     Contenu XML à phraser
 * @return array
 *     Description du plugin
**/
function svp_phraser_plugin($dtd, $contenu) {
	global $balises_multis;
	static $informer = array();
	
	$plugin = array();
	
	// On initialise les informations du plugin avec le contenu du plugin.xml ou paquet.xml
	$regexp = ($dtd == 'plugin') ? _SVP_REGEXP_BALISE_PLUGIN : _SVP_REGEXP_BALISE_PAQUET;
	if ($nb_balises = preg_match_all($regexp, $contenu, $matches)) {
		$plugins = array();
		// Pour chacune des occurences de la balise on extrait les infos
		foreach ($matches[0] as $_balise_plugin) {
			// Extraction des informations du plugin suivant le standard SPIP
			if (!isset($informer[$dtd])) {
				$informer[$dtd] = charger_fonction('infos_' . $dtd, 'plugins');
			}
			$plugins[] = $informer[$dtd]($_balise_plugin);
		}

		// On appelle systematiquement une fonction de mise a jour de la structure de donnees du plugin :
		// -- Si DTD plugin et que le nombre de balises plugin > 1 ou si DTD paquet avec une presence de balise spip
		//    alors on fusionne donc les informations recoltees
		// -- sinon on arrange la structure pour deplacer le contenu des balises dites techniques dans un sous tableau
		//    d'index 0 par similitude avec la structure fusionnee
		$fusionner = charger_fonction('fusion_' . $dtd, 'plugins');
		if ($dtd == 'plugin')
			$plugin = $fusionner($plugins);
		else
			$plugin = $fusionner($plugins[0]);

		// Pour la DTD paquet, les traductions du nom, slogan et description sont compilees dans une balise
		// du fichier archives.xml. Il faut donc completer les informations precedentes avec cette balise
		if (($dtd == _SVP_DTD_PAQUET) AND (preg_match(_SVP_REGEXP_BALISE_MULTIS, $contenu, $matches))) {
			$multis = array();
			if (is_array($arbre = spip_xml_parse($matches[1])))
				$multis = svp_aplatir_balises($balises_multis, $arbre);
			// Le nom peut etre traduit ou pas, il faut donc le tester
			if ($multis['nom'])
				$plugin['nom'] = $multis['nom'];
			// Slogan et description sont forcement des items de langue 
			$plugin['slogan'] = $multis['slogan'];
			$plugin['description'] = $multis['description'];
		}
	}

	return $plugin;
}


/**
 * Phrase le contenu de la balise <zip>
 *
 * Extrait du XML les informations du zip
 * 
 * @param string $contenu
 *     Description XML de l'archive
 * @return array
 *     Description du zip.
 *     - Index 'file' : nom du zip
 *     - Index 'size' : taille
 *     - Index 'date' : date de création
 *     - Index 'last_commit' : date du dernier commit
 *     - Index 'source' : arborescence relative des sources
 */
function svp_phraser_zip($contenu) {
	static $balises_zip = array('file', 'size', 'date', 'source', 'last_commit');
	
	$zip = array();
	if (is_array($arbre = spip_xml_parse($contenu)))
		$zip = svp_aplatir_balises($balises_zip, $arbre);

	return $zip;
}


/**
 * Phrase le contenu d'une balise <traductions> en un tableau plus
 * facilement utilisable
 *
 * @param string $contenu
 *     Contenu XML de la balise <traductions>
 * @return array
 *     Tableau complexe avec pour index les noms des modules de langue et pour
 *     valeur leur description. Chaque description contient dedans 3 index :
 *     - reference : la langue de référence
 *     - gestionnaire : quel logiciel à servi à gérer les traductions
 *     - langues : tableau classé par langue puis par traducteurs, qui indique
 *       l'ensemble des traducteurs pour chacune des langues présentes
 */
function svp_phraser_traductions($contenu) {
	
	$traductions = array();
	if (is_array($arbre = spip_xml_parse($contenu))) {
		foreach ($arbre as $_tag => $_langues) {
			// On commence par les balises <traduction> et leurs attributs
			list($tag, $attributs_traduction) = spip_xml_decompose_tag($_tag);
			$traductions[$attributs_traduction['module']]['reference'] = $attributs_traduction['reference'];
			$traductions[$attributs_traduction['module']]['gestionnaire'] = isset($attributs_traduction['gestionnaire']) ? $attributs_traduction['gestionnaire'] : '' ;
	
			// On continue par les balises <langue> qui donnent le code en attribut
			// et les balises <traducteur> qui donnent uniquement le nom en attribut
			if (is_array($_langues[0])) {
				foreach ($_langues[0] as $_tag => $_traducteurs) {
					list($tag, $attributs_langue) = spip_xml_decompose_tag($_tag);
					$traducteurs = array();
					if (is_array($_traducteurs[0])) {
						foreach ($_traducteurs[0] as $_tag => $_vide) {
							list($tag, $attributs_traducteur) = spip_xml_decompose_tag($_tag);
							$traducteurs[] = $attributs_traducteur;
						}
					}
					$traductions[$attributs_traduction['module']]['langues'][$attributs_langue['code']] = $traducteurs;
				}
			}
		}
	}

	return $traductions;
}


/**
 * Aplatit plusieurs clés d'un arbre xml dans un tableau
 * 
 * Effectue un trim() de la valeur trouvée dans l'arbre
 *
 * @param array $balises
 *     Liste de noms de balises XML.
 *     Peut aussi être un tableau indiquant un renommage d'une balise
 *     au passage tel que 'x' => 'y' qui cherchera x dans l'arbre XML et
 *     l'applatira dans y.
 * @param array $arbre_xml
 *     Un arbre issu de spip_xml_parse()
 * @param string $mode
 *     Mode d'affectation des valeurs trouvées
 *     - 'vide_et_nonvide' : Affecte une chaine vide si la balise n'est
 *       pas trouvée dans l'arbre et affecte la valeur de la balise sinon.
 *     - 'nonvide' : Si la balise n'est pas trouvée dans l'arbre ou si son
 *       contenu est vide, affecte la valeur du tableau initial concernant
 *       cette balise si elle est connue.
 * @param array
 *     Tableau initial pouvant contenir des valeurs par défaut à affecter
 *     à chaque balise avec 'x' => 'valeur'
 */
function svp_aplatir_balises($balises, $arbre_xml, $mode='vide_et_nonvide', $tableau_initial=array()) {
	$tableau_aplati = array();

	if (!$balises)
		return $tableau_initial;

	foreach ($balises as $_cle => $_valeur){
		$tag = (is_string($_cle)) ? $_cle : $_valeur;
		$valeur_aplatie = '';
		if (isset($arbre_xml[$tag])) {
			$valeur_aplatie = trim(spip_xml_aplatit($arbre_xml[$tag]));
		}
		if (($mode == 'vide_et_nonvide')
		OR (($mode == 'nonvide') AND $valeur_aplatie))
			$tableau_aplati[$_valeur] = $valeur_aplatie;
		else
			$tableau_aplati[$_valeur] = isset($tableau_initial[$_valeur]) ? $tableau_initial[$_valeur] : '';
	}

	return $tableau_aplati;
}

?>
