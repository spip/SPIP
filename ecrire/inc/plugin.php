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

// librairie pour parametrage plugin
//
define('_FILE_PLUGIN_CONFIG', "plugin.xml");
# l'adresse du repertoire de telechargement et de decompactage des plugins
define('_DIR_PLUGINS_AUTO', _DIR_PLUGINS.'auto/');

// besoin de inc_meta
include_spip('inc/texte');

function spip_version_compare($v1,$v2,$op){
	$v1 = strtolower(preg_replace(',([0-9])[\s-.]?(dev|alpha|a|beta|b|rc|pl|p),i','\\1.\\2',$v1));
	$v2 = strtolower(preg_replace(',([0-9])[\s-.]?(dev|alpha|a|beta|b|rc|pl|p),i','\\1.\\2',$v2));
	$v1 = str_replace('rc','RC',$v1); // certaines versions de PHP ne comprennent RC qu'en majuscule
	$v2 = str_replace('rc','RC',$v2); // certaines versions de PHP ne comprennent RC qu'en majuscule
	$v1 = explode('.',$v1);
	$v2 = explode('.',$v2);
	while (count($v1)<count($v2))
		$v1[] = '0';
	while (count($v2)<count($v1))
		$v2[] = '0';
	$v1 = implode('.',$v1);
	$v2 = implode('.',$v2);

	return version_compare($v1, $v2,$op);
}

// lecture des sous repertoire plugin existants
// $dir_plugins pour forcer un repertoire (ex: _DIR_EXTENSIONS)
// _DIR_PLUGINS_SUPPL pour aller en chercher ailleurs (separes par des ":")
// http://doc.spip.org/@liste_plugin_files
function liste_plugin_files($dir_plugins = null){
	static $plugin_files=array();

	if (is_null($dir_plugins)) {
		$dir_plugins = _DIR_PLUGINS;
		if (defined('_DIR_PLUGINS_SUPPL'))
			$dir_plugins_suppl = array_filter(explode(':',_DIR_PLUGINS_SUPPL));
	}

	if (!isset($plugin_files[$dir_plugins])
	OR count($plugin_files[$dir_plugins]) == 0){
		$plugin_files[$dir_plugins] = array();
		foreach (preg_files($dir_plugins, '/plugin[.]xml$') as $plugin) {
			$plugin_files[$dir_plugins][] = substr(dirname($plugin),strlen($dir_plugins));
		}
		sort($plugin_files[$dir_plugins]);

		// hack affreux pour avoir le bon chemin pour les repertoires
		// supplementaires ; chemin calcule par rapport a _DIR_PLUGINS.
		if (isset($dir_plugins_suppl)) {
			foreach($dir_plugins_suppl as $suppl) {
				foreach (preg_files(_DIR_RACINE.$suppl, 'plugin[.]xml$') as $plugin) {
					$plugin_files[$dir_plugins][] = (_DIR_RACINE? '':'../').dirname($plugin);
				}
			}
		}
	}

	return $plugin_files[$dir_plugins];
}

// http://doc.spip.org/@plugin_version_compatible
function plugin_version_compatible($intervalle,$version){
	if (!strlen($intervalle)) return true;
	if (!preg_match(',^[\[\(]([0-9.a-zRC\s\-]*)[;]([0-9.a-zRC\s\-]*)[\]\)]$,',$intervalle,$regs)) return false;
	#var_dump("$version::$intervalle");
	$minimum = $regs[1];
	$maximum = $regs[2];
	$minimum_inc = $intervalle{0}=="[";
	$maximum_inc = substr($intervalle,-1)=="]";
	#var_dump("$version::$minimum_inc::$minimum::$maximum::$maximum_inc");
	#var_dump(spip_version_compare($version,$minimum,'<'));
	if (strlen($minimum)){
		if ($minimum_inc AND spip_version_compare($version,$minimum,'<')) return false;
		if (!$minimum_inc AND spip_version_compare($version,$minimum,'<=')) return false;
	}
	if (strlen($maximum)){
		if ($maximum_inc AND spip_version_compare($version,$maximum,'>')) return false;
		if (!$maximum_inc AND spip_version_compare($version,$maximum,'>=')) return false;
	}
	return true;
}



/**
 * Faire la liste des librairies disponibles
 * retourne un array ( nom de la lib => repertoire , ... )
 *
 * @return array
 */
// http://doc.spip.org/@liste_librairies
function plugins_liste_librairies() {
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

// Prend comme argument le tableau des <necessite> et retourne un tableau vide
// si tout est bon, et un tableau avec une entree par erreur sinon
// http://doc.spip.org/@erreur_necessite
function erreur_necessite($n, $liste) {
	if (!is_array($n) OR !count($n)) {
		return array();
	}

	$msg = array();
	foreach($n as $need){
		$id = strtoupper($need['id']);

		// Necessite SPIP version x ?
		if ($id=='SPIP') {
			if (!plugin_version_compatible($need['version'],
			$GLOBALS['spip_version_branche'])) {
				$msg[] = _T('plugin_necessite_spip', array(
					'version' => $need['version']
				));
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
				$msg[] = _T('plugin_necessite_lib', array('lib'=>$lib)) . $lien_download;
			}
		}

		// Necessite un autre plugin version x ?
		else if (!isset($liste[$id])
		OR !plugin_version_compatible($need['version'],$liste[$id]['version'])
		) {
			$msg[] =  _T('plugin_necessite_plugin', array(
				'plugin' => $id,
				'version' => $need['version']));
		}
	}

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

	$get_infos = charger_fonction('get_infos','plugins');

	foreach($listes as $dir_type=>$l){
		foreach($l as $k=>$plug) {
			// renseigner ce plugin
			$infos[$dir_type][$plug] = $get_infos($plug,$force,constant($dir_type));
			// si il n'y a pas d'erreur
			if ($infos[$dir_type][$plug] AND !isset($infos[$dir_type][$plug]['erreur'])) {
				// regarder si on a pas deja selectionne le meme plugin dans une autre version
				$version = isset($infos[$dir_type][$plug]['version'])?$infos[$dir_type][$plug]['version']:NULL;
				if (isset($liste_non_classee[$p=strtoupper($infos[$dir_type][$plug]['prefix'])])){
					// prendre le plus recent
					if (spip_version_compare($version,$liste_non_classee[$p]['version'],'>'))
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
		// pour tester utilise, il faut connaitre tous les plugins 
		// qui seront forcement pas la a la fin,
		// car absent de la liste des plugins actifs
		$toute_la_liste = $liste_non_classee;
		
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
				// tous les plugins "utilise" absents
				$nb_utilise_absents_toleres = count(erreur_necessite($infos[$dir_type][$plug]['utilise'], $toute_la_liste));
				// tous les plugins "utilise" absents de la liste deja trie
				$nb_utilise_absents_tries = count(erreur_necessite($infos[$dir_type][$plug]['utilise'], $liste));
				if (abs($nb_utilise_absents_tries - $nb_utilise_absents_toleres) > 0) {
					$utilise_ok = false;
				} 				

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
					) . "<ul><li>" . implode("</li><li>", $n) . "</li></ul></li>";
				}
				else {
					// dependance circulaire, ou utilise qu'on peut ignorer ?
					// dans le doute on fait une erreur quand meme
					// plutot que d'inserer silencieusement et de risquer un bug sournois latent
					$necessite = erreur_necessite($infos[$dir_type][$plug]['utilise'], $liste);
					$erreurs .= "<li>" . _T('plugin_impossible_activer',
						array('plugin' => constant($dir_type). $plug)
					) . "<ul><li>" . implode("</li><li>", $necessite) . "</li></ul></li>";
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
			spip_log("MAJ meta plugin vieille version : $meta_plugin","plugin");
  		$t = explode(",",$meta_plugin);
  		list($liste,,) = liste_plugin_valides($t);
			include_spip('inc/meta');
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
		// compat au moment d'une migration depuis version anterieure
		// si pas de dir_type, alors c'est _DIR_PLUGINS
		if (!isset($infos['dir_type']))
			$infos['dir_type'] = "_DIR_PLUGINS";
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

	// creer le repertoire cache/ si necessaire ! (installation notamment)
	sous_repertoire(_DIR_CACHE, '', false,true);

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
	effacer_meta('message_crash_plugins'); // baisser ce flag !
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
	if (defined('_DIR_PLUGINS_SUPPL'))
		$dir_plugins_suppl = implode(array_filter(explode(':',_DIR_PLUGINS_SUPPL)),'|');

	foreach(array('chemins'=>_CACHE_PLUGINS_PATH,'options'=>_CACHE_PLUGINS_OPT,'fonctions'=>_CACHE_PLUGINS_FCT) as $charge=>$fileconf){
		$s = "";
		$splugs = "";
		$chemins = array();
		if (is_array($infos)){
			foreach($ordre as $p){
				$dir_type = $plugin_valides[$p]['dir_type'];
				$plug = $plugin_valides[$p]['dir'];
				$info = $infos[$dir_type][$plug];
				if($dir_plugins_suppl && preg_match(',('.$dir_plugins_suppl.'),',$plugin_valides[$p]['dir'])){
					//$plugin_valides[$p]['dir_type'] = '_DIR_RACINE';
					$dir_type = '_DIR_RACINE';
					//if(!test_espace_prive())
						$plug = str_replace('../','',$plug);
				}
				$root_dir_type = str_replace('_DIR_','_ROOT_',$dir_type);
				$dir = $dir_type.".'" . $plug ."/'";
				// definir le plugin, donc le path avant l'include du fichier options
				// permet de faire des include_spip pour attraper un inc_ du plugin
				if ($charge=='chemins'){
					$prefix = strtoupper(preg_replace(',\W,','_',$info['prefix']));
					$splugs .= "define('_DIR_PLUGIN_$prefix',$dir);\n";
					foreach($info['path'] as $chemin){
						if (!isset($chemin['version']) OR plugin_version_compatible($chemin['version'],$GLOBALS['spip_version_branche'])){
							$dir = $chemin['dir'];
							if (strlen($dir) AND $dir{0}=="/") $dir = substr($dir,1);
							if (!isset($chemin['type']) OR $chemin['type']=='public')
								$chemins['public'][]="_DIR_PLUGIN_$prefix".(strlen($dir)?".'$dir'":"");
							if (!isset($chemin['type']) OR $chemin['type']=='prive')
								$chemins['prive'][]="_DIR_PLUGIN_$prefix".(strlen($dir)?".'$dir'":"");
							#$splugs .= "if (".(($chemin['type']=='public')?"":"!")."_DIR_RESTREINT) ";
							#$splugs .= "_chemin(_DIR_PLUGIN_$prefix".(strlen($dir)?".'$dir'":"").");\n";
						}
					}
				}
				// concerne uniquement options et fonctions
				if (isset($info[$charge])){
					foreach($info[$charge] as $file){
						// on genere un if file_exists devant chaque include pour pouvoir garder le meme niveau d'erreur general
						$file = trim($file);

						if (strlen(constant($dir_type)) && (strpos($plug, constant($dir_type)) === 0)) {
							$dir = str_replace("'".constant($dir_type), $root_dir_type.".'", "'$plug/'");
						}
						if($root_dir_type == '_ROOT_RACINE'){
							$plug = str_replace('../','',$plug);
						}
						else
							$dir = $root_dir_type.".'$plug/'";
						$s .= "if (file_exists(\$f=$dir.'".trim($file)."')){ include_once \$f;}\n";
						$liste_fichier_verif[] = "$root_dir_type:$plug/".trim($file);
					}
				}
			}
		}
		if ($charge=='chemins'){
			if (count($chemins)){
				$splugs .= "if (_DIR_RESTREINT) _chemin(implode(':',array(".implode(',',array_reverse($chemins['public'])).")));\n";
				$splugs .= "else _chemin(implode(':',array(".implode(',',array_reverse($chemins['prive'])).")));\n";
			}
		}
		if ($charge=='options'){
			$s .= "if (!function_exists('boutons_plugins')){function boutons_plugins(){return unserialize('".str_replace("'","\'",serialize($liste_boutons))."');}}\n";
			$s .= "if (!function_exists('onglets_plugins')){function onglets_plugins(){return unserialize('".str_replace("'","\'",serialize($liste_onglets))."');}}\n";
		}
		ecrire_fichier($fileconf, $start_file . $splugs . $s . $end_file);
	}

	if (is_array($infos)){
		// construire tableaux de pipelines et matrices et boutons
		// $GLOBALS['spip_pipeline']
		// $GLOBALS['spip_matrice']
		$liste_boutons = array();
		foreach($ordre as $p){
			$dir_type = $plugin_valides[$p]['dir_type'];
			$root_dir_type = str_replace('_DIR_','_ROOT_',$dir_type);
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
							"$root_dir_type:$plug/".$pipe['inclure'];
					}
				}
			}
		}
	}
	
	// on charge les fichiers d'options qui peuvent completer 
	// la globale spip_pipeline egalement
	if (@is_readable(_CACHE_PLUGINS_PATH))
		include_once(_CACHE_PLUGINS_PATH); // securite : a priori n'a pu etre fait plus tot 
	if (@is_readable(_CACHE_PLUGINS_OPT)) {
		include_once(_CACHE_PLUGINS_OPT);
	} else {
		spip_log("pipelines desactives: impossible de produire " . _CACHE_PLUGINS_OPT);
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
		if (preg_match(",(_(DIR|ROOT)_[A-Z_]+):,Ums",$f,$regs))
			$f = str_replace($regs[0],$regs[2]=="ROOT"?constant($regs[1]):(_DIR_RACINE?"":"../").constant($regs[1]),$f);
		$liste_fichier_verif[$k] = $f;
	}
	ecrire_fichier(_CACHE_PLUGINS_VERIF,
		serialize($liste_fichier_verif));
	clear_path_cache();
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
				if (preg_match(",(_(DIR|ROOT)_[A-Z_]+):,Ums",$file,$regs)){
					$dir = $regs[1];
					$root_dir = str_replace('_DIR_','_ROOT_',$dir);
					if (defined($root_dir))
						$dir = $root_dir;
					$file = str_replace($regs[0],"'.".$dir.".'",$file);
					$file = str_replace("''.","",$file);
					$file = str_replace(constant($dir), '', $file);
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
	ecrire_fichier(_CACHE_PIPELINES, $start_file . $content . $end_file);
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
function actualise_plugins_actifs($pipe_recherche = false){
	if (!spip_connect()) return false;
	$plugin_actifs = liste_chemin_plugin_actifs();
	$plugin_liste = liste_plugin_files();
	$plugin_new = array_intersect($plugin_actifs,$plugin_liste);
	$actifs_avant = $GLOBALS['meta']['plugin'];
	ecrire_plugin_actifs($plugin_new,$pipe_recherche);
	// retourner -1 si la liste des plugins actifs a change
	return (strcmp($GLOBALS['meta']['plugin'],$actifs_avant)==0) ? 1 : -1;
}

// http://doc.spip.org/@spip_plugin_install
function spip_plugin_install($action, $infos){
	$prefix = $infos['prefix'];
	$version_cible = $infos['version_base'];
	if (isset($infos['meta']) AND (($table = $infos['meta']) !== 'meta'))
		$nom_meta = "base_version";
	else {  
		$nom_meta = $prefix."_base_version";
		$table = 'meta';
	}
	switch ($action){
		case 'test':
			return (isset($GLOBALS[$table])
			AND isset($GLOBALS[$table][$nom_meta]) 
			AND spip_version_compare($GLOBALS[$table][$nom_meta],$version_cible,'>='));
			break;
		case 'install':
			if (function_exists($upgrade = $prefix."_upgrade"))
				$upgrade($nom_meta, $version_cible, $table);
			break;
		case 'uninstall':
			if (function_exists($vider_tables = $prefix."_vider_tables"))
				$vider_tables($nom_meta, $table);
			break;
	}
}

// http://doc.spip.org/@desinstalle_un_plugin
function desinstalle_un_plugin($plug,$infos){
	// faire les include qui vont bien
	charge_instal_plugin($plug, $infos);
	$version_cible = isset($infos['version_base'])?$infos['version_base']:'';
	$prefix_install = $infos['prefix']."_install";
	if (function_exists($prefix_install)){
		$prefix_install('uninstall',$infos['prefix'],$version_cible);
		$ok = $prefix_install('test',$infos['prefix'],$version_cible);
		return $ok;
	}
	if (isset($infos['version_base'])){
		spip_plugin_install('uninstall',$infos);
		$ok = spip_plugin_install('test',$infos);
		return $ok;
	}

	return false;
}

function charge_instal_plugin($plug,$infos,$dir_plugins = '_DIR_PLUGINS'){
	// passer en chemin absolu si possible
	$dir = str_replace('_DIR_','_ROOT_',$dir_plugins);
	if (!defined($dir))
		$dir = $dir_plugins;
	
	// faire les include qui vont bien
	foreach($infos['install'] as $file){
		$file = trim($file);
		if (file_exists($f=constant($dir)."$plug/$file")){
			include_once($f);
		}
	}
}

function installe_un_plugin($plug,$infos,$dir_plugins = '_DIR_PLUGINS'){

	charge_instal_plugin($plug, $infos, $dir_plugins);

	$version_cible = isset($infos['version_base'])?$infos['version_base']:'';
	$prefix_install = $infos['prefix']."_install";
	// cas de la fonction install fournie par le plugin
	if (function_exists($prefix_install)){
		// voir si on a besoin de faire l'install
		$ok = $prefix_install('test',$infos['prefix'],$version_cible);
		if (!$ok) {
			echo "<div class='install-plugins'>";
			echo _T('plugin_titre_installation',array('plugin'=>typo($infos['nom'])))."<br />";
			$prefix_install('install',$infos['prefix'],$version_cible);
			$ok = $prefix_install('test',$infos['prefix'],$version_cible);
			// vider le cache des definitions des tables
			$trouver_table = charger_fonction('trouver_table','base');
			$trouver_table(false);
			echo "<span class='".($ok?'ok':'erreur')."'>".($ok ? _L("OK"):_L("Echec"))."</span>";
			echo "</div>";
		}
		return $ok; // le plugin est deja installe et ok
	}
	// pas de fonction instal fournie, mais une version_base dans le plugin
	// on utilise la fonction par defaut
	if (isset($infos['version_base'])){
		$ok = spip_plugin_install('test',$infos);
		if (!$ok) {
			echo "<div class='install-plugins'>";
			echo _T('plugin_titre_installation',array('plugin'=>typo($infos['nom'])))."<br />";
			spip_plugin_install('install',$infos);
			$ok = spip_plugin_install('test',$infos);
			// vider le cache des definitions des tables
			$trouver_table = charger_fonction('trouver_table','base');
			$trouver_table(false);
			echo "<span class='".($ok?'ok':'erreur')."'>".($ok ? _L("OK"):_L("Echec"))."</span>";
			echo "</div>";
		}
		return $ok; // le plugin est deja installe et ok
	}
	return false;
}

// http://doc.spip.org/@installe_plugins
function installe_plugins($test = false){
	$meta_plug_installes = array();

	// vider le cache des descriptions de tables avant installation
	$trouver_table = charger_fonction('trouver_table', 'base');
	$trouver_table('');

	$liste = liste_plugin_actifs();
	$get_infos = charger_fonction('get_infos','plugins');

	foreach ($liste as $prefix=>$resume) {
		$plug = $resume['dir'];
		$dir_type = $resume['dir_type'];		
		$infos = $get_infos($plug,false,constant($dir_type));
		if ($infos AND isset($infos['install'])){
			if ($test) return true; // il y a des installations a faire
			$ok = installe_un_plugin($plug,$infos,$dir_type);
			// on peut enregistrer le chemin ici car il est mis a jour juste avant l'affichage
			// du panneau -> cela suivra si le plugin demenage
			if ($ok)
				$meta_plug_installes[] = $plug;
			// vider le cache des descriptions de tables apres chaque installation
			$trouver_table('');
		}
	}
	ecrire_meta('plugin_installes',serialize($meta_plug_installes),'non');
	if ($test) return false; // il n'y a pas d'installations a faire
	return true; // succes
}

// http://doc.spip.org/@plugin_est_installe
function plugin_est_installe($plug_path){
	$plugin_installes = isset($GLOBALS['meta']['plugin_installes'])?unserialize($GLOBALS['meta']['plugin_installes']):array();
	if (!$plugin_installes) return false;
	return in_array($plug_path,$plugin_installes);
}

// http://doc.spip.org/@verifie_include_plugins
function verifie_include_plugins() {
	include_spip('inc/meta');
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
		actualise_plugins_actifs();
		spip_log("desactivation des plugins suite a suppression du repertoire");
	}
*/
}


// http://doc.spip.org/@message_crash_plugins
function message_crash_plugins() {
	if (autoriser('configurer')
	AND lire_fichier(_CACHE_PLUGINS_VERIF,$l)
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



?>
