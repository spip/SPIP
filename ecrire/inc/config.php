<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;


/**
 * Appliquer les valeurs par defaut pour les options non initialisees
 * (pour les langues c'est fait)
 *
 * @return null
 */
// http://doc.spip.org/@inc_config_dist
function inc_config_dist() {
	actualise_metas(liste_metas());
}

/**
 * Expliquer une configuration :
 * analyser le cfg pour determiner la table, le casier et le sous-casier eventuel
 *
 * @param string $cfg
 * @return array
 */
function expliquer_config($cfg){
	// par defaut, sur la table des meta
	$table = 'meta';
	$casier = null;
	$sous_casier = array();
	$cfg = explode('/',$cfg);
	// si le premier argument est vide, c'est une syntaxe /table/ ou un appel vide ''
	if (!reset($cfg) AND count($cfg)>1) {
		array_shift($cfg);
		$table = array_shift($cfg);
		if (!isset($GLOBALS[$table]))
			lire_metas($table);
	}
	// si on a demande #CONFIG{/meta,'',0}
	if (count($cfg))
		$casier = array_shift($cfg);

	if (count($cfg))
		$sous_casier = $cfg;

	return array($table,$casier,$sous_casier);
}

/**
 * Lecture de la configuration
 *
 * lire_config() permet de recuperer une config depuis le php<br>
 * memes arguments que la balise (forcement)<br>
 * $cfg: la config, lire_config('montruc') est un tableau<br>
 * lire_config('/table/champ') lit le valeur de champ dans la table des meta 'spip_table'<br>
 * lire_config('montruc/sub') est l'element "sub" de cette config equivalent a lire_config('/meta/montruc/sub')<br>
 *
 * lire_config('methode::montruc/sub') delegue la lecture a methode_lire_config_dist via un charger_fonction
 * permet de brancher CFG ou autre outil externe qui etend les methodes de stockage de config
 *
 * $unserialize est mis par l'histoire
 *
 * @param  string  $cfg          la config
 * @param  mixed   $def          un défaut optionnel
 * @param  boolean $unserialize  n'affecte que le dépôt 'meta'
 * @return string
 */
function lire_config($cfg='', $def=null, $unserialize=true) {
	// lire le stockage sous la forme /table/valeur
	// ou valeur qui est en fait implicitement /meta/valeur
	// ou casier/valeur qui est en fait implicitement /meta/casier/valeur

	// traiter en priorite le cas simple et frequent
	// de lecture direct $GLOBALS['meta']['truc'], si $cfg ne contient ni / ni :
	if ($cfg AND strpbrk($cfg,'/:')===false){
		$r = isset($GLOBALS['meta'][$cfg])?
		  ((!$unserialize
			// ne pas essayer de deserialiser autre chose qu'une chaine
			OR !is_string($GLOBALS['meta'][$cfg])
			// ne pas essayer de deserialiser si ce n'est visiblement pas une chaine serializee
			OR strpos($GLOBALS['meta'][$cfg],':')===false
			OR ($t=unserialize($GLOBALS['meta'][$cfg]))===false)?$GLOBALS['meta'][$cfg]:$t)
		  :$def;
		return $r;
	}

	// Brancher sur methodes externes si besoin
	if ($cfg AND $p=strpos($cfg,'::')){
		$methode = substr($cfg,0,$p);
		$lire_config = charger_fonction('lire_config',$methode);
		return $lire_config(substr($cfg,$p+2),$def,$unserialize);
	}

	list($table,$casier,$sous_casier) = expliquer_config($cfg);

	if (!isset($GLOBALS[$table]))
			return $def;

	$r = $GLOBALS[$table];

	// si on a demande #CONFIG{/meta,'',0}
	if (!$casier)
		return $unserialize ? $r : serialize($r);

	// casier principal :
	// le deserializer si demande
	// ou si on a besoin
	// d'un sous casier
	$r = isset($r[$casier])?$r[$casier]:null;
	if (($unserialize OR count($sous_casier)) AND $r AND is_string($r))
		$r = (($t=unserialize($r))===false?$r:$t);

	// aller chercher le sous_casier
	while(!is_null($r) AND $casier = array_shift($sous_casier))
		$r = isset($r[$casier])?$r[$casier]:null;

	if (is_null($r)) return $def;
	return $r;
}

/**
 * metapack est inclue dans lire_config, mais on traite le cas ou il est explicite
 * metapack:: dans le $cfg de lire_config.
 * On renvoie simplement sur lire_config
 * 
 * @param string $cfg
 * @param mixed $def
 * @param bool $unserialize
 * @return mixed
 */
function metapack_lire_config_dist($cfg='', $def=null, $unserialize=true) {
	return lire_config($cfg, $def, $unserialize);
}


/**
 * Ecrire une configuration
 *
 * @param string $cfg
 * @param mixed $store
 * @return bool
 */
function ecrire_config($cfg,$store) {
	// Brancher sur methodes externes si besoin
	if ($cfg AND $p=strpos($cfg,'::')){
		$methode = substr($cfg,0,$p);
		$ecrire_config = charger_fonction('ecrire_config',$methode);
		return $ecrire_config(substr($cfg,$p+2),$store);
	}
	
	list($table,$casier,$sous_casier) = expliquer_config($cfg);
	// il faut au moins un casier pour ecrire
	if (!$casier) return false;

	// trouvons ou creons le pointeur sur le casier
	$st = isset($GLOBALS[$table][$casier])?$GLOBALS[$table][$casier]:null;
	if (!is_array($st) AND ($sous_casier OR is_array($store))) {
		$st = unserialize($st);
		if ($st===false) {
			// ne rien creer si c'est une demande d'effacement
			if (is_null($store))
				return false;
			$st=array();
		}
	}

	// si on a affaire a un sous caiser
	// il faut ecrire au bon endroit sans perdre les autres sous casier freres
	if ($c = $sous_casier) {
		$sc = &$st;
		$pointeurs = array();
		while (count($c) AND $cc=array_shift($c)) {
			// creer l'entree si elle n'existe pas
			if (!isset($sc[$cc])) {
				// si on essaye d'effacer une config qui n'existe pas
				// ne rien creer mais sortir
				if (is_null($store))
					return false;
				$sc[$cc] = array();
			}
			$pointeurs[$cc] = &$sc;
			$sc = &$sc[$cc];
		}

		// si c'est une demande d'effacement
		if (is_null($store)){
			$c = $sous_casier;
			$sous = array_pop($c);
			// effacer, et remonter pour effacer les parents vides
			do {
				unset($pointeurs[$sous][$sous]);
			} while ($sous = array_pop($c) AND !count($pointeurs[$sous][$sous]));
			
			// si on a vide tous les sous casiers,
			// et que le casier est vide
			// vider aussi la meta
			if (!$sous AND !count($st))
				$st = null;
		}
		// si ce casier est un tableau et qu'on veut stocker un tableau,
		// faire un merge
		elseif (is_array($store) AND is_array($sc) AND count($sc))
			$sc = array_merge($sc,$store);
		// dans tous les autres cas, on ecrase
		else
			$sc = $store;

		// Maintenant que $st est modifiee
		// reprenons la comme valeur a stocker dans le casier principal
		$store = $st;
	}

	if (is_null($store)) {
		if (is_null($st) AND !$sous_casier)
			return false; // la config n'existait deja pas !
		effacer_meta ($casier, $table);
		if (!count($GLOBALS[$table])
			OR count($GLOBALS[$table])==1 AND isset($GLOBALS[$table]['charset'])) {
			effacer_meta('charset', $table); // enlevons cette meta
			supprimer_table_meta($table); // supprimons la table (si elle est bien vide)
		}
	}
	// les meta ne peuvent etre que des chaines : il faut serializer le reste
	else {
		if (!isset($GLOBALS[$table]))
			installer_table_meta($table);
		// si ce n'est pas une chaine
		// il faut serializer
		if (!is_string($store))
			$store=serialize($store);
		ecrire_meta($casier, $store, null, $table);
	}
	// verifier que lire_config($cfg)==$store ?
	return true;
}


/**
 * metapack est inclue dans ecrire_config, mais on traite le cas ou il est explicite
 * metapack:: dans le $cfg de ecrire_config.
 * On renvoie simplement sur ecrire_config
 *
 * @param string $cfg
 * @param mixed $store
 * @return bool
 */
function metapack_ecrire_config($cfg,$store) {
	// cas particulier en metapack::
	// si on ecrit une chaine deja serializee, il faut la reserializer pour la rendre
	// intacte en sortie ...
	if (is_string($store) AND strpos($store,':') AND unserialize($store))
		$store = serialize($store);
	return ecrire_config($cfg, $store);
}

/**
 * Effacer une configuration : revient a ecrire une valeur null
 * @param string $cfg
 * @return bool
 */
function effacer_config($cfg){
	ecrire_config($cfg, null);
	return true;
}


function lister_configurer($exclure = array()){
	// lister les pages de config deja dans les menus
	$deja = array();
	foreach($exclure as $id=>$b) {
		if ($b['url']!==$id AND !isset($exclure[$b['url']])) {
			if(strncmp($b['url'],'configurer_',11)==0)
				$deja[$b['url']] = $b;
			elseif($b['url']=='configurer' AND preg_match(',cfg=([a-z0-9_]+),i',$b['args'],$match)) {
				$deja["configurer_".$match[1]] = $b;
			}
		}
	}
	$exclure = $exclure + $deja;

	$icone_defaut = "images/configuration-16.png";
	$liste = array();
	$skels = array();
	$forms = array();

	// trouver toutes les page-configurer_xxx de l'espace prive
	// et construire un tableau des entrees qui ne sont pas dans $deja
	$pages = find_all_in_path("prive/squelettes/contenu/", "page-configurer_.*[.]"._EXTENSION_SQUELETTES.'$');
	
	foreach($pages as $page) {
		$configurer = substr(basename($page,"."._EXTENSION_SQUELETTES),5);
		if (!isset($exclure[$configurer]))
			$liste[$configurer] = array(
					'parent' => 'bando_configuration',
					'url' => $configurer,
					'titre' => _T("configurer:{$configurer}_titre"),
					'icone' => find_in_theme($i="images/{$configurer}-16.png")?$i:$icone_defaut,
			);
		$skels[$configurer] = $page;
	}

	// analyser la liste des $skels pour voir les #FORMULAIRE_CONFIGURER_ inclus
	foreach($skels as $file) {
		lire_fichier($file, $skel);
		if (preg_match_all(",#FORMULAIRE_(CONFIGURER_[A-Z0-9_]*),", $skel, $matches,PREG_SET_ORDER)) {
			$matches = array_map('end',$matches);
			$matches = array_map('strtolower',$matches);
			$forms = array_merge($forms,$matches);
		}
	}
	$forms = array_flip($forms);

	// trouver tous les formulaires/configurer_
	// et construire un tableau des entrees
	$pages = find_all_in_path("formulaires/", "configurer_.*[.]"._EXTENSION_SQUELETTES.'$');
	foreach($pages as $page) {
		$configurer = basename($page,"."._EXTENSION_SQUELETTES);
		if (!isset($forms[$configurer])
		  AND !isset($liste[$configurer])
			AND !isset($exclure[$configurer]))
			$liste[$configurer] = array(
					'parent' => 'bando_configuration',
					'url' => 'configurer',
					'args' => 'cfg='.substr($configurer,11),
					'titre' => _T("configurer:{$configurer}_titre"),
					'icone' => find_in_theme($i="images/{$configurer}-16.png")?$i:$icone_defaut,
			);
	}


	return $liste;
}

// http://doc.spip.org/@liste_metas
function liste_metas()
{
	return pipeline('configurer_liste_metas', array(
		'nom_site' => _T('info_mon_site_spip'),
		'adresse_site' => preg_replace(",/$,", "", url_de_base()),
		'descriptif_site' => '',
		'activer_breves' => 'non',
		'activer_logos' => 'oui',
		'activer_logos_survol' => 'non',
		'articles_surtitre' => 'non',
		'articles_soustitre' => 'non',
		'articles_descriptif' => 'non',
		'articles_chapeau' => 'non',
		'articles_texte' => 'oui',
		'articles_ps' => 'non',
		'articles_redac' => 'non',
		'post_dates' => 'non',
		'articles_urlref' => 'non',
		'articles_redirection' => 'non',
		'creer_preview' => 'non',
		'taille_preview' => 150,
		'articles_modif' => 'non',

		'rubriques_descriptif' => 'non',
		'rubriques_texte' => 'oui',

		'accepter_inscriptions' => 'non',
		'accepter_visiteurs' => 'non',
		'prevenir_auteurs' => 'non',
		'suivi_edito' => 'non',
		'adresse_suivi' =>'',
		'adresse_suivi_inscription' =>'',
		'adresse_neuf' => '',
		'jours_neuf' => '',
		'quoi_de_neuf' => 'non',
		'messagerie_agenda' => 'oui',

		'documents_article' => 'non',
		'documents_rubrique' => 'non',
		'documents_date' => 'non',
		'syndication_integrale' => 'oui',
		'charset' => _DEFAULT_CHARSET,
		'dir_img' => substr(_DIR_IMG,strlen(_DIR_RACINE)),

		'multi_articles' => 'non',
		'multi_rubriques' => 'non',
		'multi_secteurs' => 'non',
		'gerer_trad' => 'non',
		'langues_multilingue' => '',

		'version_html_max' => 'html4',

		'type_urls' => 'page',

		'email_envoi' => '',
		'email_webmaster' => '',
		'auto_compress_http'=>'non',
	));
}

// mets les meta a des valeurs conventionnelles quand elles sont vides
// et recalcule les langues

// http://doc.spip.org/@actualise_metas
function actualise_metas($liste_meta)
{
	$meta_serveur =
		array('version_installee','adresse_site','alea_ephemere_ancien','alea_ephemere','alea_ephemere_date','langue_site','langues_proposees','date_calcul_rubriques','derniere_modif','optimiser_table','drapeau_edition','creer_preview','taille_preview','creer_htpasswd','creer_htaccess','gd_formats_read','gd_formats',
	'netpbm_formats','formats_graphiques','image_process','plugin_header','plugin');
	// verifier le impt=non
	sql_updateq('spip_meta',array('impt'=>'non'),sql_in('nom',$meta_serveur));

	while (list($nom, $valeur) = each($liste_meta)) {
		if (!$GLOBALS['meta'][$nom]) {
			ecrire_meta($nom, $valeur);
		}
	}

	include_spip('inc/rubriques');
	$langues = calculer_langues_utilisees();
	ecrire_meta('langues_utilisees', $langues);
}


// http://doc.spip.org/@avertissement_config
function avertissement_config() {
	global $spip_lang_right, $spip_lang_left;

	return debut_boite_info(true)
	. "\n<div class='verdana2' style='text-align: justify'>
	<p style='text-align: center'><b>"._T('avis_attention')."</b></p>"
	. http_img_pack(chemin_image("warning-48.png"), (_T('avis_attention')),
		" style='float: $spip_lang_right; padding-$spip_lang_left: 10px;'")
	. _T('texte_inc_config')
	. "</div>"
	. fin_boite_info(true)
	. "<p>&nbsp;</p>\n";
}


// http://doc.spip.org/@bouton_radio
function bouton_radio($nom, $valeur, $titre, $actif = false, $onClick="") {
	static $id_label = 0;

	if (strlen($onClick) > 0) $onClick = " onclick=\"$onClick\"";
	$texte = "<input type='radio' name='$nom' value='$valeur' id='label_${nom}_${id_label}'$onClick";
	if ($actif) {
		$texte .= ' checked="checked"';
		$titre = '<b>'.$titre.'</b>';
	}
	$texte .= " /> <label for='label_${nom}_${id_label}'>$titre</label>\n";
	$id_label++;
	return $texte;
}


// http://doc.spip.org/@afficher_choix
function afficher_choix($nom, $valeur_actuelle, $valeurs, $sep = "<br />") {
	$choix = array();
	while (list($valeur, $titre) = each($valeurs)) {
		$choix[] = bouton_radio($nom, $valeur, $titre, $valeur == $valeur_actuelle);
	}
	return "\n".join($sep, $choix);
}


//
// Gestion des modifs
//

// http://doc.spip.org/@appliquer_modifs_config
function appliquer_modifs_config($purger_skel=false) {

	if (($i = _request('adresse_site'))!==NULL){
		if (!strlen($i)) {$GLOBALS['profondeur_url']=_DIR_RESTREINT?0:1;$i = url_de_base();}
		$_POST['adresse_site'] = preg_replace(",/?\s*$,", "", $i);
	}

	foreach(liste_metas() as $i => $v) {
		if (($x =_request($i))!==NULL)
			ecrire_meta($i, $x);
		elseif  (!isset($GLOBALS['meta'][$i]))
			ecrire_meta($i, $v);
	}

	if ($purger_skel) {
		include_spip('inc/invalideur');
		purger_repertoire(_DIR_SKELS);
	}
}

?>
