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

// librairie pour parametrage plugin
//
define('_FILE_PLUGIN_CONFIG', "plugin.xml");
# l'adresse du repertoire de telechargement et de decompactage des plugins
define('_DIR_PLUGINS_AUTO', _DIR_PLUGINS.'auto/');

// besoin de inc_meta
include_spip('inc/meta');
include_spip('inc/texte');

// lecture des sous repertoire plugin existants
// http://doc.spip.org/@liste_plugin_files
function liste_plugin_files(){
	static $plugin_files=array();
	if (!count($plugin_files)){
		foreach (preg_files(_DIR_PLUGINS, '/plugin[.]xml$') as $plugin) {
			$plugin_files[]=substr(dirname($plugin), strlen(_DIR_PLUGINS));
		}
		sort($plugin_files);
	}
	return $plugin_files;
}

// http://doc.spip.org/@plugin_version_compatible
function plugin_version_compatible($intervalle,$version){
	if (!strlen($intervalle)) return true;
	if (!preg_match(',^[\[\(]([0-9.]*)[;]([0-9]*)[\]\)]$,',$intervalle,$regs)) return false;
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
			$GLOBALS['spip_version_code'])) {
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
					include_ecrire('inc/charger_plugin');
					$lien_download = '<br />'
						.bouton_telechargement_plugin($url, strtolower($r[1]));
				}
				$msg .= "<li>"
				._L('Ce plugin n&#233;cessite la librairie '.$lib)
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
function liste_plugin_valides($liste_plug,&$infos, $force = false){
	$liste = array();
	$infos = array();

	foreach($liste_plug as $plug)
		$infos[$plug] = plugin_get_infos($plug,$force);

	if (is_array($liste_plug)){
		// construire une liste ordonnee des plugins
		$count = 0;
		while ($c=count($liste_plug) AND $c!=$count){ // tant qu'il reste des plugins a classer, et qu'on ne stagne pas
			#echo "tour::";var_dump($liste_plug);
			$count = $c;
			foreach($liste_plug as $k=>$plug) {
				if (isset($infos[$plug]['erreur'])) {
					unset($liste_plug[$k]);
				} else {
					$version = isset($infos[$plug]['version'])?$infos[$plug]['version']:NULL;
					if (isset($liste[$p=strtoupper($infos[$plug]['prefix'])])){
						// prendre le plus recent
						if (version_compare($version,$liste[$p]['version'],'>'))
							unset($liste[$p]);
						else{
							unset($liste_plug[$k]);
							continue;
						}
					}
					if (!erreur_necessite($infos[$plug]['necessite'], $liste)) {
						$liste[$p] = array(
							'nom' => $infos[$plug]['nom'],
							'etat' => $infos[$plug]['etat'],
							'dir'=>$plug,
							'version'=>isset($infos[$plug]['version'])?$infos[$plug]['version']:NULL
						);
						unset($liste_plug[$k]);
					}
				}
			}
		}
		if (count($liste_plug)) {
			include_spip('inc/lang');
			utiliser_langue_visiteur();
			$erreurs = "";
			foreach($liste_plug as $plug){
				$necessite = erreur_necessite($infos[$plug]['necessite'], $liste);
				$erreurs .= "<li>" . _T('plugin_impossible_activer',
					array('plugin' => $plug)
				)."</li>";
			}
			ecrire_meta('plugin_erreur_activation',
				"<ul>$erreurs</ul>$necessite");
		}
	}
	return $liste;
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
  		$liste = liste_plugin_valides($t,$infos);
			ecrire_meta('plugin',serialize($liste));
			ecrire_metas();
			return $liste;
  	}
  }
	else
		return array();
}
// http://doc.spip.org/@liste_chemin_plugin_actifs
function liste_chemin_plugin_actifs(){
	$liste = liste_plugin_actifs();
	foreach ($liste as $prefix=>$infos) {
		$liste[$prefix] = $infos['dir'];
	}
	return $liste;
}

// http://doc.spip.org/@ecrire_plugin_actifs
function ecrire_plugin_actifs($plugin,$pipe_recherche=false,$operation='raz') {
	static $liste_pipe_manquants=array();

	include_spip('inc/meta');

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
	$plugin_valides = liste_plugin_valides($plugin,$infos,true);

	ecrire_meta('plugin',serialize($plugin_valides));
	$plugin_header_info = array();
	foreach($plugin_valides as $p=>$info){
		$plugin_header_info[]= $p.($info['version']?"(".$info['version'].")":"");
	}
	ecrire_meta('plugin_header',substr(strtolower(implode(",",$plugin_header_info)),0,900));

	$start_file = "<"."?php\nif (defined('_ECRIRE_INC_VERSION')) {\n";
	$end_file = "}\n?".">";

	if (is_array($infos)){
		// construire tableaux de boutons et onglets
		$liste_boutons = array();
		$liste_onglets = array();
		foreach($infos as $plug=>$info){
			if (isset($info['bouton'])){
				foreach($info['bouton'] as $id=>$conf){
					$conf['icone'] = "$plug/" . $conf['icone'];
					$info['bouton'][$id] = $conf;
				}
				$liste_boutons = array_merge($liste_boutons,$info['bouton']);
			}
			if (isset($info['onglet'])){
				foreach($info['onglet'] as $id=>$conf){
					$conf['icone'] = "$plug/" . $conf['icone'];
					$info['onglet'][$id] = $conf;
				}
				$liste_onglets = array_merge($liste_onglets,$info['onglet']);
			}
		}
	}

	// generer les fichier 
	// charger_plugins_options.php
	// charger_plugins_fonctions.php
	foreach(array('options','fonctions') as $charge){
		$s = "error_reporting(SPIP_ERREUR_REPORT_INCLUDE_PLUGINS);\n";
		$splugs = "";
		if (is_array($infos)){
			foreach($infos as $plug=>$info){
				// definir le plugin, donc le path avant l'include du fichier options
				// permet de faire des include_ecrire pour attraper un inc_ du plugin
				if ($charge=='options'){
					$prefix = strtoupper(preg_replace(',\W,','_',$info['prefix']));
					$splugs .= "define('_DIR_PLUGIN_$prefix',_DIR_PLUGINS.'$plug/');\n";
					foreach($info['path'] as $chemin){
						if (!isset($chemin['version']) OR plugin_version_compatible($chemin['version'],$GLOBALS['spip_version_code'])){
							if (isset($chemin['type']))
								$splugs .= "if (".(($chemin['type']=='public')?"":"!")."_DIR_RESTREINT) ";
							$dir = $chemin['dir'];
							if (strlen($dir) AND $dir{0}=="/") $dir = substr($dir,1);
							$splugs .= "_chemin(_DIR_PLUGIN_$prefix".(strlen($dir)?".'$dir'":"").");\n";
						}
					}
					//$splugs .= '$GLOBALS[\'plugins\'][]=\''.$plug.'\';';
					//$splugs .= "\n";
				}
				if (isset($info[$charge])){
					foreach($info[$charge] as $file){
						$s .= "include_once _DIR_PLUGINS.'$plug/".trim($file)."';\n";
						$liste_fichier_verif[] = "_DIR_PLUGINS.'$plug/".trim($file)."'";
					}
				}
			}
		}
		$s .= "error_reporting(SPIP_ERREUR_REPORT);\n";
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
		foreach($infos as $plug=>$info){
			$prefix = "";
			$prefix = $info['prefix']."_";
			if (isset($info['pipeline']) AND is_array($info['pipeline'])){
				foreach($info['pipeline'] as $pipe){
					$nom = $pipe['nom'];
					if (isset($pipe['action']))
						$action = $pipe['action'];
					else
						$action = $nom;
					if (!isset($GLOBALS['spip_pipeline'][$nom])) // creer le pipeline eventuel
						$GLOBALS['spip_pipeline'][$nom]="";
					if (strpos($GLOBALS['spip_pipeline'][$nom],"|$prefix$action")===FALSE)
						$GLOBALS['spip_pipeline'][$nom] .= "|$prefix$action";
					if (isset($pipe['inclure'])){
						$GLOBALS['spip_matrice']["$prefix$action"] = 
							"_DIR_PLUGINS$plug/".$pipe['inclure'];
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
	foreach ($liste_fichier_verif as $k => $f)
		$liste_fichier_verif[$k] = _DIR_PLUGINS.preg_replace(",(_DIR_PLUGINS\.)?',", "", $f);
	ecrire_fichier(_DIR_TMP.'verifier_plugins.txt',
		serialize($liste_fichier_verif));

	ecrire_metas();
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
			$s_call .= '$val = minipipe(\''.$fonc.'\', $val);'."\n";
			if (isset($spip_matrice[$fonc])){
				$file = $spip_matrice[$fonc];
				$s_inc .= 'include_once(';
				// si _DIR_PLUGINS est dans la chaine, on extrait la constante
				if (($p = strpos($file,'_DIR_PLUGINS'))!==FALSE){
					$f = "";
					if ($p)
						$f .= "'".substr($file,0,$p)."'.";
					$f .= "_DIR_PLUGINS.";
					$f .= "'".substr($file,$p+12)."'";
					$s_inc .= $f;
					$liste_fichier_verif[] = $f;
				}
				else{
					$s_inc .= "'$file'";
					$liste_fichier_verif[] = "'$file'";
				}
				$s_inc .= ');'."\n";
			}
		}
		$content .= "// Pipeline $action \n";
		$content .= "function execute_pipeline_$action(\$val){\n";
		$content .= $s_inc?"error_reporting(SPIP_ERREUR_REPORT_INCLUDE_PLUGINS);\n":"";
		$content .= $s_inc;
		$content .= $s_inc?"error_reporting(SPIP_ERREUR_REPORT);\n":"";
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
// penser a faire une maj du cache =>  ecrire_meta()
// en principe cela doit aussi initialiser la valeur a vide si elle n'esite pas
// risque de pb en php5 a cause du typage ou de null (verifier dans la doc php)
// http://doc.spip.org/@verif_plugin
function verif_plugin($pipe_recherche = false){
	$plugin_actifs = liste_chemin_plugin_actifs();
	$plugin_liste = liste_plugin_files();
	$plugin_new = array_intersect($plugin_actifs,$plugin_liste);
	ecrire_plugin_actifs($plugin_new,$pipe_recherche);
}

// http://doc.spip.org/@ordonne_plugin
function ordonne_plugin(){
	$liste = liste_chemin_plugin_actifs();
	$liste_triee = array();
	$i=2;
	foreach($liste as $plug){
		$index = $i;
		$i = $i+2;
		if (rawurldecode($_GET['monter'])==$plug) $index = $index-3;
		if (rawurldecode($_GET['descendre'])==$plug) $index = $index+3;
		$liste_triee[$index] = $plug;
	}
	ksort($liste_triee);
	ecrire_plugin_actifs($liste_triee);
}

// http://doc.spip.org/@spip_plugin_install
function spip_plugin_install($action,$prefix,$version_cible){
	$nom_meta_base_version = $prefix."_base_version";
	switch ($action){
		case 'test':
			return (isset($GLOBALS['meta'][$nom_meta_base_version]) AND ($GLOBALS['meta'][$nom_meta_base_version]>=$version_cible));
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
		@include_once(_DIR_PLUGINS."$plug/$file");
	}
	$prefix_install = $infos['prefix']."_install";
	if (function_exists($prefix_install)){
		$prefix_install('uninstall');
		$ok = $prefix_install('test');
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
function installe_un_plugin($plug,$infos){
	// faire les include qui vont bien
	foreach($infos['install'] as $file){
		$file = trim($file);
		@include_once(_DIR_PLUGINS."$plug/$file");
	}
	$prefix_install = $infos['prefix']."_install";
	// cas de la fonction install fournie par le plugin
	if (function_exists($prefix_install)){
		// voir si on a besoin de faire l'install
		$ok = $prefix_install('test');
		if (!$ok) {
			$prefix_install('install');
			$ok = $prefix_install('test');
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
	$liste = liste_chemin_plugin_actifs();
	foreach($liste as $plug){
		$infos = plugin_get_infos($plug);
		if (isset($infos['install'])){
			$ok = installe_un_plugin($plug,$infos);
			// on peut enregistrer le chemin ici car il est mis a jour juste avant l'affichage
			// du panneau -> cela suivra si le plugin demenage
			if ($ok)
				$meta_plug_installes[] = $plug;
		}
	}
	ecrire_meta('plugin_installes',serialize($meta_plug_installes),'non');
	ecrire_metas();
}
// http://doc.spip.org/@plugin_est_installe
function plugin_est_installe($plug_path){
	$plugin_installes = isset($GLOBALS['meta']['plugin_installes'])?unserialize($GLOBALS['meta']['plugin_installes']):array();
	if (!$plugin_installes) return false;
	return in_array($plug_path,$plugin_installes);
}

// lecture du fichier de configuration d'un plugin
// http://doc.spip.org/@plugin_get_infos
function plugin_get_infos($plug, $force_reload=false){
	include_spip('inc/xml');
	static $infos=array();
	static $plugin_xml_cache=NULL;
	if (!isset($infos[$plug]) OR $force_reload){
		if ($plugin_xml_cache==NULL){
			$plugin_xml_cache = array();
			if (is_file($f=_DIR_TMP."plugin_xml.cache")){
				lire_fichier($f,$contenu);
				$plugin_xml_cache = unserialize($contenu);
				if (!is_array($plugin_xml_cache)) $plugin_xml_cache = array();
			}
		}
		$ret = array();
		if (isset($plugin_xml_cache[$plug])){
			$info = $plugin_xml_cache[$plug];
			if (!$force_reload
				AND isset($info['filemtime'])
				AND @file_exists($f = _DIR_PLUGINS."$plug/plugin.xml")
				AND (@filemtime($f)<=$info['filemtime']))
				$ret = $info;
		}
		if (!count($ret)){
		  if ((@file_exists(_DIR_PLUGINS))&&(is_dir(_DIR_PLUGINS))){
				if (@file_exists($f = _DIR_PLUGINS."$plug/plugin.xml")) {
					$arbre = spip_xml_load($f);
					if (!$arbre OR !isset($arbre['plugin']) OR !is_array($arbre['plugin']))
						$arbre = array('erreur' => array(_T('erreur_plugin_fichier_def_incorrect')." : $plug/plugin.xml"));
				}
				else {
					// pour arriver ici on l'a vraiment cherche...
					$arbre = array('erreur' => array(_T('erreur_plugin_fichier_def_absent')." : $plug/plugin.xml"));
				}
				plugin_verifie_conformite($plug,$arbre);
				$ret['nom'] = spip_xml_aplatit($arbre['nom']);
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
				
				// recuperer les boutons et onglets si necessaire
				spip_xml_match_nodes(",^(bouton|onglet)\s,",$arbre,$les_boutons);
				if (is_array($les_boutons) && count($les_boutons)){
					$ret['bouton'] = array();
					$ret['onglet'] = array();
					foreach($les_boutons as $bouton => $val) {
						$bouton = spip_xml_decompose_tag($bouton);
						$type = reset($bouton);
						$bouton = end($bouton);
						if (isset($bouton['id'])){
							$id = $bouton['id'];
							$val = reset($val);
							if(is_array($val)){
								$ret[$type][$id]['parent'] = isset($bouton['parent'])?$bouton['parent']:'';
								$ret[$type][$id]['titre'] = isset($val['titre'])?trim(end($val['titre'])):'';
								$ret[$type][$id]['icone'] = isset($val['icone'])?trim(end($val['icone'])):'';
								$ret[$type][$id]['url'] = isset($val['url'])?trim(end($val['url'])):'';
								$ret[$type][$id]['args'] = isset($val['args'])?trim(end($val['args'])):'';
							}
						}
					}
				}
				
				if ($t=@filemtime($f)){
					$ret['filemtime'] = $t;
					$plugin_xml_cache[$plug]=$ret;
					ecrire_fichier(_DIR_TMP."plugin_xml.cache",serialize($plugin_xml_cache));
				}
			}
		}
		$infos[$plug] = $ret;
	}
	return $infos[$plug];
}

// http://doc.spip.org/@plugin_verifie_conformite
function plugin_verifie_conformite($plug,&$arbre){
	$silence = false;
	if (isset($arbre['plugin'])&&is_array($arbre['plugin']))
		$arbre = end($arbre['plugin']); // derniere def plugin
	else{
		$arbre = array('erreur' => array(_T('erreur_plugin_tag_plugin_absent')." : $plug/plugin.xml"));
		$silence = true;
	}
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
				if (!@is_readable(_DIR_PLUGINS."$plug/$optfile"))
  				if (!$silence)
						$arbre['erreur'][] = _T('erreur_plugin_fichier_absent')." : $optfile";
			}
		}
		if (isset($arbre['fonctions'])){
			foreach($arbre['fonctions'] as $optfile){
				$optfile = trim($optfile);
				if (!@is_readable(_DIR_PLUGINS."$plug/$optfile"))
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
				$inclure = _DIR_PLUGINS."$plug/".$pipe['inclure'];
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
	include_spip('inc/meta');
	ecrire_meta('message_crash_plugins', 1);

/*	if (_request('exec')!="admin_plugin"
	AND $_SERVER['X-Requested-With'] != 'XMLHttpRequest'){
		if (@is_readable(_DIR_PLUGINS)) {
			include_spip('inc/headers');
			redirige_par_entete(generer_url_ecrire("admin_plugin"));
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
				._L("Erreur dans les plugins : @plugins@",
					array('plugins' => join(', ', $err)))
				.'</a>';
		}
	}
}


// http://doc.spip.org/@affiche_bloc_plugin
function affiche_bloc_plugin($plug_file, $info) {
	global $spip_lang_right;

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

	if (isset($info['icon']))
		$s .= "<img src='". _DIR_PLUGINS.$plug_file.'/'.trim($info['icon'])."' style='float:$spip_lang_right;' alt=' ' />\n";

	// TODO: le traiter_multi ici n'est pas beau
	// cf. description du plugin/_stable_/ortho/plugin.xml
	if (isset($info['description']))
		$s .= plugin_propre($info['description']) . "<br/>";

	if (isset($info['auteur']))
		$s .= "<hr/>" . _T('auteur') .' '. plugin_propre($info['auteur']) . "<br/>";

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
	if ($svn_revision = version_svn_courante(_DIR_PLUGINS.$plug_file))
		$version .= ($svn_revision<0 ? ' SVN':'').' ['.abs($svn_revision).']';

	$infotech[] = $version;
	$infotech[] = "<strong>$titre_etat</strong>";

	// Version SVN

	// bouton de desinstallation
	if (plugin_est_installe($plug_file)){
		$action = generer_action_auteur('desinstaller_plugin',$plug_file,generer_url_ecrire('admin_plugin'));
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
	$source = (lire_fichier(_DIR_PLUGINS.$plug_file.'/install.log', $log)
	AND preg_match(',^source:(.*)$,m', $log, $r))
		? '<br />'._L('source:').' '.trim($r[1])
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
