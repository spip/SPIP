<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// librairie pour parametrage plugin
//
define('_FILE_PLUGIN_CONFIG', "plugin.xml");
# l'adresse du repertoire de telechargement et de decompactage des plugins
define('_DIR_PLUGINS_AUTO', _DIR_PLUGINS.'auto/');

// besoin de inc_meta
include_spip('inc/texte');

// lecture des sous repertoire plugin existants
// $dir_plugins pour forcer un repertoire (ex: _DIR_EXTENSIONS)
// _DIR_PLUGINS_SUPPL pour aller en chercher ailleurs (separes par des ":")
// http://doc.spip.org/@liste_plugin_files
function liste_plugin_files($dir_plugins = null){
	static $plugin_files=array();

	if (is_null($dir_plugins)) {
		if (defined('_DIR_PLUGINS_SUPPL')) {
			$dir_plugins = _DIR_PLUGINS_SUPPL.':'._DIR_PLUGINS;
		} else
			$dir_plugins = _DIR_PLUGINS;
	}

	if (!isset($plugin_files[$dir_plugins])
	OR count($plugin_files[$dir_plugins]) == 0){
		$plugin_files[$dir_plugins] = array();
		foreach(array_filter(explode(':', $dir_plugins)) as $dir)
			foreach (preg_files($dir, '/plugin[.]xml$') as $plugin) {
				$plugin_files[$dir_plugins][]= str_replace(_DIR_PLUGINS,'',dirname($plugin));
		}
		sort($plugin_files[$dir_plugins]);
	}
	return $plugin_files[$dir_plugins];
}

// http://doc.spip.org/@plugin_version_compatible
function plugin_version_compatible($intervalle,$version){
	if (!strlen($intervalle)) return true;
	if (!preg_match(',^[\[\(]([0-9.a-zRC\s]*)[;]([0-9.a-zRC\s]*)[\]\)]$,',$intervalle,$regs)) return false;
	$mineure = $regs[1];
	$majeure = $regs[2];
	$mineure_inc = $intervalle{0}=="[";
	$majeure_inc = substr($intervalle,-1)=="]";
	#var_dump("$mineure_inc-$mineure-$majeure-$majeure_inc");
	if (strlen($mineure)){
		if ($mineure_inc AND version_compare($version,$mineure,'<')) return false;
		if (!$mineure_inc AND version_compare($version,$mineure,'<=')) return false;
	}
	if (strlen($majeure)){
		if ($majeure_inc AND version_compare($version,$majeure,'>')) return false;
		if (!$majeure_inc AND version_compare($version,$majeure,'>=')) return false;
	}
	return true;
}


// Faire la liste des librairies disponibles
// retourne un array ( nom de la lib => repertoire , ... )

// http://doc.spip.org/@liste_librairies
function liste_librairies() {
	$libs = array();
	foreach (array_reverse(creer_chemin()) as $d) {
		if (is_dir($dir = $d.'lib/')
		AND $t = @opendir($dir)) {
			while (($f = readdir($t)) !== false) {
				if ($f[0] != '.'
				AND is_dir("$dir/$f"))
					$libs[$f] = $dir;
			}
		}
	}
	return $libs;
}

// Prend comme argument le tableau des <necessite> et retourne false si
// tout est bon, et un message d'erreur sinon
// http://doc.spip.org/@erreur_necessite
function erreur_necessite($n, $liste) {
	if (!is_array($n) OR !count($n))
		return false;

	$msg = "";
	foreach($n as $need){
		$id = strtoupper($need['id']);

		// Necessite SPIP version x ?
		if ($id=='SPIP') {
			if (!plugin_version_compatible($need['version'],
			$GLOBALS['spip_version_branche'].".".$GLOBALS['spip_version_code'])) {
				$msg .= "<li>"
				._T('plugin_necessite_spip',
				array('version' => $need['version'])
				)."</li>";
			}
		}

		// Necessite une librairie ?
		else if (preg_match(',^(lib):(.*),i', $need['id'], $r)) {
			$lib = trim($r[2]);
			if (!find_in_path('lib/'.$lib)) {
				$lien_download = '';
				if (isset($need['src'])) {
					$url = $need['src'];
					include_spip('inc/charger_plugin');
					$lien_download = '<br />'
						.bouton_telechargement_plugin($url, strtolower($r[1]));
				}
				$msg .= "<li>"
				  ._T('plugin_necessite_lib',array('lib'=>$lib))
				. $lien_download
				."</li>";
			}
		}

		// Necessite un autre plugin version x ?
		else if (!isset($liste[$id])
		OR !plugin_version_compatible($need['version'],$liste[$id]['version'])
		) {
			$msg .= "<li>"
			._T('plugin_necessite_plugin',
			array('plugin' => $id,
				'version' => $need['version'])
			)."</li>";
		}
	}
	if (strlen($msg))
		$msg="<ul>$msg</ul>";
	return $msg;
}


// http://doc.spip.org/@liste_plugin_valides
function liste_plugin_valides($liste_plug, $force = false){
	$liste = array();
	$ordre = array();
	$infos = array();
	
	// lister les extensions qui sont automatiquement actives
	$liste_extensions = liste_plugin_files(_DIR_EXTENSIONS);

	$listes = array(
		'_DIR_EXTENSIONS'=>$liste_extensions,
		'_DIR_PLUGINS'=>$liste_plug
	);

	// creer une premiere liste non ordonnee mais qui ne retient
	// que les plugins valides, et dans leur derniere version en cas de doublon
	$liste_non_classee = array();
	foreach($listes as $dir_type=>$l){
		foreach($l as $k=>$plug) {
			// renseigner ce plugin
			$infos[$dir_type][$plug] = plugin_get_infos($plug,$force,constant($dir_type));
			// si il n'y a pas d'erreur
			if (!isset($infos[$dir_type][$plug]['erreur'])) {
				// regarder si on a pas deja selectionne le meme plugin dans une autre version
				$version = isset($infos[$dir_type][$plug]['version'])?$infos[$dir_type][$plug]['version']:NULL;
				if (isset($liste_non_classee[$p=strtoupper($infos[$dir_type][$plug]['prefix'])])){
					// prendre le plus recent
					if (version_compare($version,$liste_non_classee[$p]['version'],'>'))
						unset($liste_non_classee[$p]);
					else{
						continue;
					}
				}
				// ok, le memoriser
				$liste_non_classee[$p] = array(
					'nom' => $infos[$dir_type][$plug]['nom'],
					'etat' => $infos[$dir_type][$plug]['etat'],
					'dir_type' => $dir_type, // extensions ou plugins
					'dir'=>$plug,
					'version'=>isset($infos[$dir_type][$plug]['version'])?$infos[$dir_type][$plug]['version']:NULL
				);
			}
		}
	}

	// il y a des plugins a trier
	if (is_array($liste_non_classee)){
		// construire une liste ordonnee des plugins
		$count = 0;
		while ($c=count($liste_non_classee) AND $c!=$count){ // tant qu'il reste des plugins a classer, et qu'on ne stagne pas
			#echo "tour::";var_dump($liste_non_classee);
			$count = $c;
			foreach($liste_non_classee as $p=>$resume) {
				$plug = $resume['dir'];
				$dir_type = $resume['dir_type'];
				// si des plugins sont necessaire, on ne peut inserer qu'apres eux
				$necessite_ok = !erreur_necessite($infos[$dir_type][$plug]['necessite'], $liste);
				// si des plugins sont utiles, on ne peut inserer qu'apres eux, 
				// sauf si ils sont de toute facon absents de la liste
				$utilise_ok = true;
				if (!erreur_necessite($infos[$dir_type][$plug]['utilise'], $liste_non_classee))
					$utilise_ok = !erreur_necessite($infos[$dir_type][$plug]['utilise'], $liste);
				if ($necessite_ok AND $utilise_ok){
					$liste[$p] = $liste_non_classee[$p];
					$ordre[] = $p;
					unset($liste_non_classee[$p]);
				}
			}
		}
		if (count($liste_non_classee)) {
			include_spip('inc/lang');
			utiliser_langue_visiteur();
			$erreurs = "";
			foreach($liste_non_classee as $p=>$resume){
				$plug = $resume['dir'];
				$dir_type = $resume['dir_type'];
				if ($n = erreur_necessite($infos[$dir_type][$plug]['necessite'], $liste)){
					$erreurs .= "<li>" . _T('plugin_impossible_activer',
						array('plugin' => constant($dir_type). $plug)
					)."$n</li>";
				}
				else {
					// dependance circulaire, ou utilise qu'on peut ignorer ?
					// dans le doute on fait une erreur quand meme
					// plutot que d'inserer silencieusement et de risquer un bug sournois latent
					$necessite = erreur_necessite($infos[$plug]['utilise'], $liste);
					$erreurs .= "<li>" . _T('plugin_impossible_activer',
						array('plugin' => constant($dir_type). $plug)
					)."$necessite</li>";
				}
			}
			ecrire_meta('plugin_erreur_activation',
				"<ul>$erreurs</ul>");
		}
	}
	return array($liste,$ordre,$infos);
}

//  A utiliser pour initialiser ma variable globale $plugin
// http://doc.spip.org/@liste_plugin_actifs
function liste_plugin_actifs(){
  $meta_plugin = isset($GLOBALS['meta']['plugin'])?$GLOBALS['meta']['plugin']:'';
  if (strlen($meta_plugin)>0){
  	if (is_array($t=unserialize($meta_plugin)))
  		return $t;
  	else{ // compatibilite pre 1.9.2, mettre a jour la meta
  		$t = explode(",",$meta_plugin);
  		list($liste,,) = liste_plugin_valides($t);
			ecrire_meta('plugin',serialize($liste));
			return $liste;
  	}
  }
	else
		return array();
}

/**
 * Lister les chemins vers les plugins actifs d'un dossier plugins/
 *
 * @return unknown
 */
// http://doc.spip.org/@liste_chemin_plugin_actifs
function liste_chemin_plugin_actifs($dir_plugins=_DIR_PLUGINS){
	$liste = liste_plugin_actifs();
	foreach ($liste as $prefix=>$infos) {
		if (defined($infos['dir_type']) 
		AND constant($infos['dir_type'])==$dir_plugins)
			$liste[$prefix] = $infos['dir'];
		else 
			unset($liste[$prefix]);
	}
	return $liste;
}

// http://doc.spip.org/@ecrire_plugin_actifs
function ecrire_plugin_actifs($plugin,$pipe_recherche=false,$operation='raz') {
	static $liste_pipe_manquants=array();

	$liste_fichier_verif = array();
	if (($pipe_recherche)&&(!in_array($pipe_recherche,$liste_pipe_manquants)))
		$liste_pipe_manquants[]=$pipe_recherche;

	if ($operation!='raz'){
		$plugin_actifs = liste_chemin_plugin_actifs();
		$plugin_liste = liste_plugin_files();
		$plugin_valides = array_intersect($plugin_actifs,$plugin_liste);
		if ($operation=='ajoute')
			$plugin = array_merge($plugin_valides,$plugin);
		if ($operation=='enleve')
			$plugin = array_diff($plugin_valides,$plugin);
	}

	// recharger le xml des plugins a activer
	list($plugin_valides,$ordre,$infos) = liste_plugin_valides($plugin,true);

	ecrire_meta('plugin',serialize($plugin_valides));
	$plugin_header_info = array();
	foreach($plugin_valides as $p=>$resume){
		$plugin_header_info[]= $p.($resume['version']?"(".$resume['version'].")":"");
	}
	ecrire_meta('plugin_header',substr(strtolower(implode(",",$plugin_header_info)),0,900));

	$start_file = "<"."?php\nif (defined('_ECRIRE_INC_VERSION')) {\n";
	$end_file = "}\n?".">";

	if (is_array($infos)){
		// construire tableaux de boutons et onglets
		$liste_boutons = array();
		$liste_onglets = array();
		foreach($ordre as $p){
			$dir_type = $plugin_valides[$p]['dir_type'];
			$plug = $plugin_valides[$p]['dir'];
			$info = $infos[$dir_type][$plug];
			if (isset($info['bouton'])){
				$liste_boutons = array_merge($liste_boutons,$info['bouton']);
			}
			if (isset($info['onglet'])){
				$liste_onglets = array_merge($liste_onglets,$info['onglet']);
			}
		}
	}

	// generer les fichier
	// charger_plugins_options.php
	// charger_plugins_fonctions.php
	foreach(array('options','fonctions') as $charge){
		$s = "";
		$splugs = "";
		if (is_array($infos)){
			foreach($ordre as $p){
				$dir_type = $plugin_valides[$p]['dir_type'];
				$plug = $plugin_valides[$p]['dir'];
				$info = $infos[$dir_type][$plug];
				// definir le plugin, donc le path avant l'include du fichier options
				// permet de faire des include_spip pour attraper un inc_ du plugin
				if ($charge=='options'){
					$prefix = strtoupper(preg_replace(',\W,','_',$info['prefix']));
					$splugs .= "define('_DIR_PLUGIN_$prefix',$dir_type.'$plug/'); ";
					foreach($info['path'] as $chemin){
						if (!isset($chemin['version']) OR plugin_version_compatible($chemin['version'],$GLOBALS['spip_version_branche'].".".$GLOBALS['spip_version_code'])){
							if (isset($chemin['type']))
								$splugs .= "if (".(($chemin['type']=='public')?"":"!")."_DIR_RESTREINT) ";
							$dir = $chemin['dir'];
							if (strlen($dir) AND $dir{0}=="/") $dir = substr($dir,1);
							$splugs .= "_chemin(_DIR_PLUGIN_$prefix".(strlen($dir)?".'$dir'":"").");\n";
						}
					}
				}
				if (isset($info[$charge])){
					foreach($info[$charge] as $file){
						// on genere un if file_exists devant chaque include pour pouvoir garder le meme niveau d'erreur general
						$s .= "if (file_exists(\$f=$dir_type.'$plug/".trim($file)."')){ include_once \$f;}\n";
						$liste_fichier_verif[] = "$dir_type:$plug/".trim($file);
					}
				}
			}
		}
		if ($charge=='options'){
			$s .= "function boutons_plugins(){return unserialize('".str_replace("'","\'",serialize($liste_boutons))."');}\n";
			$s .= "function onglets_plugins(){return unserialize('".str_replace("'","\'",serialize($liste_onglets))."');}\n";
		}
		ecrire_fichier(_DIR_TMP."charger_plugins_$charge.php",
			$start_file . $splugs . $s . $end_file);
	}

	if (is_array($infos)){
		// construire tableaux de pipelines et matrices et boutons
		// $GLOBALS['spip_pipeline']
		// $GLOBALS['spip_matrice']
		$liste_boutons = array();
		foreach($ordre as $p){
			$dir_type = $plugin_valides[$p]['dir_type'];
			$plug = $plugin_valides[$p]['dir'];
			$info = $infos[$dir_type][$plug];
			$prefix = "";
			$prefix = $info['prefix']."_";
			if (isset($info['pipeline']) AND is_array($info['pipeline'])){
				foreach($info['pipeline'] as $pipe){
					$nom = $pipe['nom'];
					if (isset($pipe['action']))
						$action = $pipe['action'];
					else
						$action = $nom;
					$nomlower = strtolower($nom);
					if ($nomlower!=$nom 
					  AND isset($GLOBALS['spip_pipeline'][$nom]) 
					  AND !isset($GLOBALS['spip_pipeline'][$nomlower])){
						$GLOBALS['spip_pipeline'][$nomlower] = $GLOBALS['spip_pipeline'][$nom];
						unset($GLOBALS['spip_pipeline'][$nom]);
					}
					$nom = $nomlower;
					if (!isset($GLOBALS['spip_pipeline'][$nom])) // creer le pipeline eventuel
						$GLOBALS['spip_pipeline'][$nom]="";
					if (strpos($GLOBALS['spip_pipeline'][$nom],"|$prefix$action")===FALSE)
						$GLOBALS['spip_pipeline'][$nom] = preg_replace(",(\|\||$),","|$prefix$action\\1",$GLOBALS['spip_pipeline'][$nom],1);
					if (isset($pipe['inclure'])){
						$GLOBALS['spip_matrice']["$prefix$action"] =
							"$dir_type:$plug/".$pipe['inclure'];
					}
				}
			}
		}
	}

	// on ajoute les pipe qui ont ete recenses manquants
	foreach($liste_pipe_manquants as $add_pipe)
		if (!isset($GLOBALS['spip_pipeline'][$add_pipe]))
			$GLOBALS['spip_pipeline'][$add_pipe]= '';

	$liste_fichier_verif2 = pipeline_precompile();
	$liste_fichier_verif = array_merge($liste_fichier_verif,$liste_fichier_verif2);

	// on note dans tmp la liste des fichiers qui doivent etre presents,
	// pour les verifier "souvent"
	// ils ne sont verifies que depuis l'espace prive, mais peuvent etre reconstruit depuis l'espace public
	// dans le cas d'un plugin non declare, spip etant mis devant le fait accompli
	// hackons donc avec un "../" en dur dans ce cas, qui ne manquera pas de nous embeter un jour...
	foreach ($liste_fichier_verif as $k => $f){
		// si un _DIR_XXX: est dans la chaine, on extrait la constante
		if (preg_match(",(_DIR_[A-Z_]+):,Ums",$f,$regs))
			$f = str_replace($regs[0],(_DIR_RACINE?"":"../").constant($regs[1]),$f);
		$liste_fichier_verif[$k] = $f;
	}
	ecrire_fichier(_DIR_TMP.'verifier_plugins.txt',
		serialize($liste_fichier_verif));
}

// precompilation des pipelines
// http://doc.spip.org/@pipeline_precompile
function pipeline_precompile(){
	global $spip_pipeline, $spip_matrice;
	$liste_fichier_verif = array();

	$start_file = "<"."?php\nif (defined('_ECRIRE_INC_VERSION')) {\n";
	$end_file = "}\n?".">";
	$content = "";
	foreach($spip_pipeline as $action=>$pipeline){
		$s_inc = "";
		$s_call = "";
		$pipe = array_filter(explode('|',$pipeline));
		// Eclater le pipeline en filtres et appliquer chaque filtre
		foreach ($pipe as $fonc) {
			$fonc = trim($fonc);
			$s_call .= '$val = minipipe(\''.$fonc.'\', $val);'."\n";
			if (isset($spip_matrice[$fonc])){
				$file = $spip_matrice[$fonc];
				$liste_fichier_verif[] = $file;
				$s_inc .= 'if (file_exists($f=';
				$file = "'$file'";
				// si un _DIR_XXX: est dans la chaine, on extrait la constante
				if (preg_match(",(_DIR_[A-Z_]+):,Ums",$file,$regs)){
					$file = str_replace($regs[0],"'.".$regs[1].".'",$file);
					$file = str_replace("''.","",$file);
				}
				$s_inc .= $file . ')){include_once($f);}'."\n";
			}
		}
		$content .= "// Pipeline $action \n";
		$content .= "function execute_pipeline_$action(&\$val){\n";
		$content .= $s_inc;
		$content .= $s_call;
		$content .= "return \$val;\n}\n\n";
	}
	ecrire_fichier(_DIR_TMP."charger_pipelines.php",
		$start_file . $content . $end_file);
	return $liste_fichier_verif;
}

// pas sur que ca serve...
// http://doc.spip.org/@liste_plugin_inactifs
function liste_plugin_inactifs(){
	return array_diff (liste_plugin_files(),liste_chemin_plugin_actifs());
}

// mise a jour du meta en fonction de l'etat du repertoire
// Les  ecrire_meta() doivent en principe aussi initialiser la valeur a vide
// si elle n'existe pas
// risque de pb en php5 a cause du typage ou de null (verifier dans la doc php)
// http://doc.spip.org/@verif_plugin
function verif_plugin($pipe_recherche = false){
	if (!spip_connect()) return false;
	$plugin_actifs = liste_chemin_plugin_actifs();
	$plugin_liste = liste_plugin_files();
	$plugin_new = array_intersect($plugin_actifs,$plugin_liste);
	ecrire_plugin_actifs($plugin_new,$pipe_recherche);
	return true;
}

// http://doc.spip.org/@spip_plugin_install
function spip_plugin_install($action,$prefix,$version_cible){
	$nom_meta_base_version = $prefix."_base_version";
	switch ($action){
		case 'test':
			return (isset($GLOBALS['meta'][$nom_meta_base_version]) 
			  AND version_compare($GLOBALS['meta'][$nom_meta_base_version],$version_cible,'>='));
			break;
		case 'install':
			if (function_exists($upgrade = $prefix."_upgrade"))
				$upgrade($nom_meta_base_version,$version_cible);
			break;
		case 'uninstall':
			if (function_exists($vider_tables = $prefix."_vider_tables"))
				$vider_tables($nom_meta_base_version);
			break;
	}
}

// http://doc.spip.org/@desinstalle_un_plugin
function desinstalle_un_plugin($plug,$infos){
	// faire les include qui vont bien
	foreach($infos['install'] as $file){
		$file = trim($file);
		if (file_exists(_DIR_PLUGINS."$plug/$file")){
			include_once(_DIR_PLUGINS."$plug/$file");
		}
	}
	$version_cible = isset($infos['version_base'])?$infos['version_base']:'';
	$prefix_install = $infos['prefix']."_install";
	if (function_exists($prefix_install)){
		$prefix_install('uninstall',$infos['prefix'],$version_cible);
		$ok = $prefix_install('test',$infos['prefix'],$version_cible);
		return $ok;
	}
	if (isset($infos['version_base'])){
		spip_plugin_install('uninstall',$infos['prefix'],$infos['version_base']);
		$ok = spip_plugin_install('test',$infos['prefix'],$infos['version_base']);
		return $ok;
	}

	return false;
}

// http://doc.spip.org/@installe_un_plugin
function installe_un_plugin($plug,$infos,$dir_plugins = _DIR_PLUGINS){
	// faire les include qui vont bien
	foreach($infos['install'] as $file){
		$file = trim($file);
		if (file_exists($f=$dir_plugins."$plug/$file")){
			include_once($f);
		}
	}
	$version_cible = isset($infos['version_base'])?$infos['version_base']:'';
	$prefix_install = $infos['prefix']."_install";
	// cas de la fonction install fournie par le plugin
	if (function_exists($prefix_install)){
		// voir si on a besoin de faire l'install
		$ok = $prefix_install('test',$infos['prefix'],$version_cible);
		if (!$ok) {
			$prefix_install('install',$infos['prefix'],$version_cible);
			$ok = $prefix_install('test',$infos['prefix'],$version_cible);
		}
		return $ok; // le plugin est deja installe et ok
	}
	// pas de fonction instal fournie, mais une version_base dans le plugin
	// on utilise la fonction par defaut
	if (isset($infos['version_base'])){
		$ok = spip_plugin_install('test',$infos['prefix'],$infos['version_base']);
		if (!$ok) {
			spip_plugin_install('install',$infos['prefix'],$infos['version_base']);
			$ok = spip_plugin_install('test',$infos['prefix'],$infos['version_base']);
		}
		return $ok; // le plugin est deja installe et ok
	}
	return false;
}

// http://doc.spip.org/@installe_plugins
function installe_plugins(){
	$meta_plug_installes = array();
	$liste = liste_plugin_actifs();
	foreach ($liste as $prefix=>$resume) {
		$plug = $resume['dir'];
		$dir_type = $resume['dir_type'];		
		$infos = plugin_get_infos($plug,false,constant($dir_type));
		if (isset($infos['install'])){
			$ok = installe_un_plugin($plug,$infos,constant($dir_type));
			// on peut enregistrer le chemin ici car il est mis a jour juste avant l'affichage
			// du panneau -> cela suivra si le plugin demenage
			if ($ok)
				$meta_plug_installes[] = $plug;
		}
	}
	ecrire_meta('plugin_installes',serialize($meta_plug_installes),'non');
}

// http://doc.spip.org/@plugin_est_installe
function plugin_est_installe($plug_path){
	$plugin_installes = isset($GLOBALS['meta']['plugin_installes'])?unserialize($GLOBALS['meta']['plugin_installes']):array();
	if (!$plugin_installes) return false;
	return in_array($plug_path,$plugin_installes);
}

// lecture du fichier de configuration d'un plugin
// http://doc.spip.org/@plugin_get_infos
function plugin_get_infos($plug, $force_reload=false, $dir_plugins = _DIR_PLUGINS){
	include_spip('inc/xml');
	static $infos=array();
	static $plugin_xml_cache=NULL;
	if (!isset($infos[$dir_plugins][$plug]) OR $force_reload){
		if ($plugin_xml_cache==NULL){
			$plugin_xml_cache = array();
			if (is_file($f=_DIR_TMP."plugin_xml.cache")){
				lire_fichier($f,$contenu);
				$plugin_xml_cache = unserialize($contenu);
				if (!is_array($plugin_xml_cache)) $plugin_xml_cache = array();
			}
		}
		$ret = array();
		if (isset($plugin_xml_cache[$dir_plugins][$plug])){
			$info = $plugin_xml_cache[$dir_plugins][$plug];
			if (!$force_reload
				AND isset($info['filemtime'])
				AND @file_exists($f = $dir_plugins."$plug/plugin.xml")
				AND (@filemtime($f)<=$info['filemtime']))
				$ret = $info;
		}
		if (!count($ret)){
		  if ((@file_exists($dir_plugins))&&(is_dir($dir_plugins))){
				if (@file_exists($f = $dir_plugins."$plug/plugin.xml")) {
					$arbre = spip_xml_load($f);
					if (!$arbre OR !isset($arbre['plugin']) OR !is_array($arbre['plugin']))
						$arbre = array('erreur' => array(_T('erreur_plugin_fichier_def_incorrect')." : $plug/plugin.xml"));
				}
				else {
					// pour arriver ici on l'a vraiment cherche...
					$arbre = array('erreur' => array(_T('erreur_plugin_fichier_def_absent')." : $plug/plugin.xml"));
				}
				plugin_verifie_conformite($plug,$arbre,$dir_plugins);
				
				$ret['nom'] = charset2unicode(spip_xml_aplatit($arbre['nom']));
				$ret['version'] = trim(end($arbre['version']));
				if (isset($arbre['auteur']))
					$ret['auteur'] = spip_xml_aplatit($arbre['auteur']);
				if (isset($arbre['icon']))
					$ret['icon'] = spip_xml_aplatit($arbre['icon']);
				if (isset($arbre['description']))
					$ret['description'] = spip_xml_aplatit($arbre['description']);
				if (isset($arbre['lien']))
					$ret['lien'] = join(' ',$arbre['lien']);
				if (isset($arbre['etat']))
					$ret['etat'] = trim(end($arbre['etat']));
				if (isset($arbre['options']))
					$ret['options'] = $arbre['options'];
				if (isset($arbre['licence']))
					$ret['licence'] = spip_xml_aplatit($arbre['licence']);
				if (isset($arbre['install']))
					$ret['install'] = $arbre['install'];
				if (isset($arbre['fonctions']))
					$ret['fonctions'] = $arbre['fonctions'];
				$ret['prefix'] = trim(array_pop($arbre['prefix']));
				if (isset($arbre['pipeline']))
					$ret['pipeline'] = $arbre['pipeline'];
				if (isset($arbre['erreur']))
					$ret['erreur'] = $arbre['erreur'];
				if (isset($arbre['version_base']))
					$ret['version_base'] = trim(end($arbre['version_base']));
				$ret['necessite'] = $arbre['necessite'];
				$ret['utilise'] = $arbre['utilise'];
				$ret['path'] = $arbre['path'];
				if (isset($arbre['noisette']))
					$ret['noisette'] = $arbre['noisette'];

				include_spip('inc/bandeau');
				$les_boutons = boutons_parse($arbre);
				$ret['bouton'] = $les_boutons['bouton'];
				$ret['onglet'] = $les_boutons['onglet'];

				if ($t=@filemtime($f)){
					$ret['filemtime'] = $t;
					$plugin_xml_cache[$plug]=$ret;
					ecrire_fichier(_DIR_TMP."plugin_xml.cache",serialize($plugin_xml_cache));
				}
			}
		}
		$infos[$dir_plugins][$plug] = $ret;
	}
	return $infos[$dir_plugins][$plug];
}

// http://doc.spip.org/@plugin_verifie_conformite
function plugin_verifie_conformite($plug, &$arbre, $dir_plugins = _DIR_PLUGINS){
	$silence = false;
	if (isset($arbre['plugin']) AND is_array($arbre['plugin']))
		$arbre = end($arbre['plugin']); // derniere def plugin
	else{
		$arbre = array('erreur' => array(_T('erreur_plugin_tag_plugin_absent')." : $plug/plugin.xml"));
		$silence = true;
	}
	if (!is_array($arbre)) $arbre = array();
  // verification de la conformite du plugin avec quelques
  // precautions elementaires
  if (!isset($arbre['nom'])){
  	if (!$silence)
			$arbre['erreur'][] = _T('erreur_plugin_nom_manquant');
		$arbre['nom'] = array("");
	}
  if (!isset($arbre['version'])){
  	if (!$silence)
			$arbre['erreur'][] = _T('erreur_plugin_version_manquant');
		$arbre['version'] = array("");
	}
  if (!isset($arbre['prefix'])){
  	if (!$silence)
			$arbre['erreur'][] = _T('erreur_plugin_prefix_manquant');
		$arbre['prefix'] = array("");
	}
	else{
		$prefix = "";
		$prefix = trim(end($arbre['prefix']));
		if (strtoupper($prefix)=='SPIP'){
			$arbre['erreur'][] = _T('erreur_plugin_prefix_interdit');
		}
		if (isset($arbre['etat'])){
			$etat = trim(end($arbre['etat']));
			if (!in_array($etat,array('dev','experimental','test','stable')))
				$arbre['erreur'][] = _T('erreur_plugin_etat_inconnu')." : $etat";
		}
		if (isset($arbre['options'])){
			foreach($arbre['options'] as $optfile){
				$optfile = trim($optfile);
				if (!@is_readable($dir_plugins."$plug/$optfile"))
  				if (!$silence)
						$arbre['erreur'][] = _T('erreur_plugin_fichier_absent')." : $optfile";
			}
		}
		if (isset($arbre['fonctions'])){
			foreach($arbre['fonctions'] as $optfile){
				$optfile = trim($optfile);
				if (!@is_readable($dir_plugins."$plug/$optfile"))
  				if (!$silence)
						$arbre['erreur'][] = _T('erreur_plugin_fichier_absent')." : $optfile";
			}
		}
		$fonctions = array();
		if (isset($arbre['fonctions']))
			$fonctions = $arbre['fonctions'];
	  $liste_methodes_reservees = array('__construct','__destruct','plugin','install','uninstall',strtolower($prefix));
	  plugin_pipeline_props($arbre);
		foreach($arbre['pipeline'] as $pipe){
			if (!isset($pipe['nom']))
				if (!$silence)
					$arbre['erreur'][] = _T("erreur_plugin_nom_pipeline_non_defini");
			if (isset($pipe['action'])) $action = $pipe['action'];
			else $action = $pipe['nom'];
			// verif que la methode a un nom autorise
			if (in_array(strtolower($action),$liste_methodes_reservees)){
				if (!$silence)
					$arbre['erreur'][] = _T("erreur_plugin_nom_fonction_interdit")." : $action";
			}
			if (isset($pipe['inclure'])) {
				$inclure = $dir_plugins."$plug/".$pipe['inclure'];
				if (!@is_readable($inclure))
	  			if (!$silence)
						$arbre['erreur'][] = _T('erreur_plugin_fichier_absent')." : $inclure";
			}
		}
		$necessite = array();
		if (spip_xml_match_nodes(',^necessite,',$arbre,$needs)){
			foreach(array_keys($needs) as $tag){
				list($tag,$att) = spip_xml_decompose_tag($tag);
				$necessite[] = $att;
			}
		}
		$arbre['necessite'] = $necessite;
		$utilise = array();
		if (spip_xml_match_nodes(',^utilise,',$arbre,$uses)){
			foreach(array_keys($uses) as $tag){
				list($tag,$att) = spip_xml_decompose_tag($tag);
				$utilise[] = $att;
			}
		}
		$arbre['utilise'] = $utilise;
		$path = array();
		if (spip_xml_match_nodes(',^chemin,',$arbre,$paths)){
			foreach(array_keys($paths) as $tag){
				list($tag,$att) = spip_xml_decompose_tag($tag);
				$path[] = $att;
			}
		}
		else
			$path = array(array('dir'=>'')); // initialiser par defaut
		$arbre['path'] = $path;
		// exposer les noisettes
		if (isset($arbre['noisette'])){
			foreach($arbre['noisette'] as $k=>$nut){
				$nut = preg_replace(',[.]html$,uims','',trim($nut));
				$arbre['noisette'][$k] = $nut;
				if (!@is_readable($dir_plugins."$plug/$nut.html"))
  				if (!$silence)
						$arbre['erreur'][] = _T('erreur_plugin_fichier_absent')." : $nut";
			}
		}
	}
}
// http://doc.spip.org/@plugin_pipeline_props
function plugin_pipeline_props(&$arbre){
	$pipeline = array();
	if (spip_xml_match_nodes(',^pipeline,',$arbre,$pipes)){
		foreach($pipes as $tag=>$p){
			if (!is_array($p[0])){
				list($tag,$att) = spip_xml_decompose_tag($tag);
				$pipeline[] = $att;
			}
			else foreach($p as $pipe){
				$att = array();
				if (is_array($pipe))
					foreach($pipe as $k=>$t)
						$att[$k] = trim(end($t));
				$pipeline[] = $att;
			}
		}
		unset($arbre[$tag]);
	}
	$arbre['pipeline'] = $pipeline;
}

// http://doc.spip.org/@verifie_include_plugins
function verifie_include_plugins() {
	ecrire_meta('message_crash_plugins', 1);

/*	if (_request('exec')!="admin_plugin"
	AND $_SERVER['X-Requested-With'] != 'XMLHttpRequest'){
		if (@is_readable(_DIR_PLUGINS)) {
			include_spip('inc/headers');
			redirige_url_ecrire("admin_plugin");
		}
		// plus de repertoire plugin existant, le menu n'existe plus
		// on fait une mise a jour silencieuse
		// generer les fichiers php precompiles
		// de chargement des plugins et des pipelines
		verif_plugin();
		spip_log("desactivation des plugins suite a suppression du repertoire");
	}
*/
}


// http://doc.spip.org/@message_crash_plugins
function message_crash_plugins() {
	if (autoriser('configurer')
	AND lire_fichier(_DIR_TMP.'verifier_plugins.txt',$l)
	AND $l = @unserialize($l)) {
		$err = array();
		foreach ($l as $fichier) {
			if (!@is_readable($fichier)) {
				spip_log("Verification plugin: echec sur $fichier !");
				$err[] = $fichier;
			}
		}

		if ($err) {
			$err = array_map('joli_repertoire', array_unique($err));
			return "<a href='".generer_url_ecrire('admin_plugin')."'>"
				._T('plugins_erreur',
					array('plugins' => join(', ', $err)))
				.'</a>';
		}
	}
}


// http://doc.spip.org/@affiche_bloc_plugin
function affiche_bloc_plugin($plug_file, $info, $dir_plugins=null) {
	global $spip_lang_right;
	if (!$dir_plugins)
		$dir_plugins = _DIR_PLUGINS;

	// puce d'etat du plugin
	// <etat>dev|experimental|test|stable</etat>
	$etat = 'dev';
	if (isset($info['etat']))
		$etat = $info['etat'];
	switch ($etat) {
		case 'experimental':
			$puce = 'puce-rouge.gif';
			$titre_etat = _T('plugin_etat_experimental');
			break;
		case 'test':
			$puce = 'puce-orange.gif';
			$titre_etat = _T('plugin_etat_test');
			break;
		case 'stable':
			$puce = 'puce-verte.gif';
			$titre_etat = _T('plugin_etat_stable');
			break;
		default:
			$puce = 'puce-poubelle.gif';
			$titre_etat = _T('plugin_etat_developpement');
			break;
	}

	$s .= "<div class='detailplugin verdana2'>";

	if (isset($info['icon'])) {
		include_spip("inc/filtres_images_mini");
		$s.= "<div  style='float:$spip_lang_right;'>".image_reduire($dir_plugins.$plug_file.'/'.trim($info['icon']), 64)."</div>";

	}
	// TODO: le traiter_multi ici n'est pas beau
	// cf. description du plugin/_stable_/ortho/plugin.xml
	if (isset($info['description']))
		$s .= plugin_propre($info['description']) . "<br/>";

	if (isset($info['auteur']))
		$s .= "<hr/>" . _T('auteur') .' '. plugin_propre($info['auteur']) . "<br/>";
	if (isset($info['licence']))
		$s .= "<hr/>" . _T('intitule_licence') .' '. plugin_propre($info['licence']) . "<br/>";

	if (trim($info['lien'])) {
		if (preg_match(',^https?://,iS', $info['lien']))
			$s .= "<hr/>" . _T('info_url') .' '. plugin_propre("[->".$info['lien']."]") . "<br/>";
		else
			$s .= "<hr/>" . _T('info_url') .' '. plugin_propre($info['lien']) . "<br/>";
	}

	$s .= "</div>";

	//
	// Ajouter les infos techniques
	//
	$infotech = array();

	$version = _T('version') .' '.  $info['version'];
	if ($svn_revision = version_svn_courante($dir_plugins.$plug_file))
		$version .= ($svn_revision<0 ? ' SVN':'').' ['.abs($svn_revision).']';

	$infotech[] = $version;
	$infotech[] = "<strong>$titre_etat</strong>";

	// Version SVN

	// bouton de desinstallation
	if (plugin_est_installe($plug_file)){
		$action = redirige_action_auteur('desinstaller_plugin',$plug_file,'admin_plugin');
		$infotech[] = "<a href='$action'
		onclick='return confirm(\""._T('bouton_desinstaller')
		." ".basename($plug_file)." ?\\n"._T('info_desinstaller_plugin')."\")'
		title=\""._T('info_desinstaller_plugin')."\">"
		. http_img_pack('spip-pack-24.png','spip-pack','',
			'')
		."</a>"
		;
	}

	// source zip le cas echeant
	$source = (lire_fichier($dir_plugins.$plug_file.'/install.log', $log)
	AND preg_match(',^source:(.*)$,m', $log, $r))
		? '<br />'._T('plugin_source').' '.trim($r[1])
		:'';

	$s .= "<div style='text-align:$spip_lang_right' class='spip_pack'>"
		. join(' &mdash; ', $infotech) .
		 '<br />' . _T('repertoire_plugins') .' '. $plug_file
		. $source
		."</div>";


	return $s;
}

// http://doc.spip.org/@plugin_propre
function plugin_propre($texte) {
	$mem = $GLOBALS['toujours_paragrapher'];
	$GLOBALS['toujours_paragrapher'] = false;
	$regexp = "|\[:([^>]*):\]|";
	if (preg_match_all($regexp, $texte, $matches, PREG_SET_ORDER))
	foreach ($matches as $regs)
		$texte = str_replace($regs[0],
		_T('spip/ecrire/public:'.$regs[1]), $texte);
	$texte = propre($texte);
	$GLOBALS['toujours_paragrapher'] = $mem;
	return $texte;
}

?>
