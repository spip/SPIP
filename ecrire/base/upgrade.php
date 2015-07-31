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

/**
 * Programme de mise a jour des tables SQL lors d'un chgt de version.
 * L'entree dans cette fonction est reservee au maj de SPIP coeur
 *
 * Marche aussi pour les plugins en appelant directement la fonction maj_plugin
 * Pour que ceux-ci profitent aussi de la reprise sur interruption,
 * ils doivent simplement indiquer leur numero de version installee dans une meta
 * et fournir le tableau maj a la fonction maj_plugin.
 * La reprise sur timeout se fait alors par la page admin_plugin et jamais par ici
 *
 * http://doc.spip.org/@base_upgrade_dist
 *
 * @param string $titre
 * @param string $reprise
 * @return
 */
function base_upgrade_dist($titre='', $reprise='')
{
	if (!$titre) return; // anti-testeur automatique
	if ($GLOBALS['spip_version_base']!=$GLOBALS['meta']['version_installee']) {
		if (!is_numeric(_request('reinstall'))) {
			include_spip('base/create');
			spip_log("recree les tables eventuellement disparues","maj."._LOG_INFO_IMPORTANTE);
			creer_base();
		}
		
		// quand on rentre par ici, c'est toujours une mise a jour de SPIP
		// lancement de l'upgrade SPIP
		$res = maj_base();

		if ($res) {
			// on arrete tout ici !
			exit;
		}
	}
	spip_log("Fin de mise a jour SQL. Debut m-a-j acces et config","maj."._LOG_INFO_IMPORTANTE);
	
	// supprimer quelques fichiers temporaires qui peuvent se retrouver invalides
	@spip_unlink(_CACHE_RUBRIQUES);
	@spip_unlink(_CACHE_PIPELINES);
	@spip_unlink(_CACHE_PLUGINS_PATH);
	@spip_unlink(_CACHE_PLUGINS_OPT);
	@spip_unlink(_CACHE_PLUGINS_FCT);
	@spip_unlink(_CACHE_CHEMIN);
	@spip_unlink(_DIR_TMP."plugin_xml_cache.gz");

	include_spip('inc/auth');
	auth_synchroniser_distant();
	$config = charger_fonction('config', 'inc');
	$config();
}

/**
 * MAJ de base de SPIP
 *
 * http://doc.spip.org/@maj_base
 *
 * @param int $version_cible
 * @param string $redirect
 * @return array|bool
 */
function maj_base($version_cible = 0, $redirect = '') {
	global $spip_version_base;

	$version_installee = @$GLOBALS['meta']['version_installee'];
	//
	// Si version nulle ou inexistante, c'est une nouvelle installation
	//   => ne pas passer par le processus de mise a jour.
	// De meme en cas de version superieure: ca devait etre un test,
	// il y a eu le message d'avertissement il doit savoir ce qu'il fait
	//
	// version_installee = 1.702; quand on a besoin de forcer une MAJ
	
	spip_log("Version anterieure: $version_installee. Courante: $spip_version_base","maj."._LOG_INFO_IMPORTANTE);
	if (!$version_installee OR ($spip_version_base < $version_installee)) {
		sql_replace('spip_meta', 
		      array('nom' => 'version_installee',
			    'valeur' => $spip_version_base,
			    'impt' => 'non'));
		return false;
	}
	if (!upgrade_test()) return true;
	
	$cible = ($version_cible ? $version_cible : $spip_version_base);

	if ($version_installee <= 1.926) {
		$n = floor($version_installee * 10);
		while ($n < 19) {
			$nom  = sprintf("v%03d",$n);
			$f = charger_fonction($nom, 'maj', true);
			if ($f) {
				spip_log( "$f repercute les modifications de la version " . ($n/10),"maj."._LOG_INFO_IMPORTANTE);
				$f($version_installee, $spip_version_base);
			} else spip_log( "pas de fonction pour la maj $n $nom","maj."._LOG_INFO_IMPORTANTE);
			$n++;
		}
		include_spip('maj/v019_pre193');
		v019_pre193($version_installee, $version_cible);
	}
	if ($version_installee < 2000) {
		if ($version_installee < 2)
			$version_installee = $version_installee*1000;
		include_spip('maj/v019');
	}
	if ($cible < 2)
		$cible = $cible*1000;

	include_spip('maj/svn10000');
	ksort($GLOBALS['maj']);
	$res = maj_while($version_installee, $cible, $GLOBALS['maj'], 'version_installee','meta', $redirect, true);
	if ($res) {
		if (!is_array($res))
			spip_log("Pb d'acces SQL a la mise a jour","maj."._LOG_INFO_ERREUR);
		else {
			echo _T('avis_operation_echec') . ' ' . join(' ', $res);
			echo install_fin_html();
		}
	}
	return $res;
}

/**
 * Mise à jour d'un plugin de SPIP
 * 
 * Fonction appelée par la fonction de maj d'un plugin.
 * On lui fournit un tableau de fonctions élementaires
 * dont l'indice est la version
 * 
 * @param string $nom_meta_base_version
 *     Nom de la meta informant de la version du schéma de données du plugin installé dans SPIP
 * @param string $version_cible
 *     Version du schéma de données dans le plugin (déclaré dans paquet.xml)
 * @param array $maj
 *     Tableau d'actions à faire à l'installation (clé 'create') et pour chaque
 *     version intermédiaire entre la version actuelle du schéma du plugin dans SPIP
 *     et la version du schéma déclaré dans le plugin (ex. clé '1.1.0').
 *     
 *     Chaque valeur est un tableau contenant une liste de fonctions à exécuter,
 *     cette liste étant elle-même un tableau avec premier paramètre le nom de la fonction
 *     et les suivant les paramètres à lui passer
 *     @example
 *        array(
 *            'create' => array(
 *                array('maj_tables', array('spip_rubriques', 'spip_articles')),
 *                array('creer_base)),
 *            '1.1.0' => array(
 *                array('sql_alter', 'TABLE spip_articles ADD INDEX truc (truc)'))
 *        )
 * @param string $table_meta
 *     Nom de la table meta (sans le prefixe spip_) dans laquelle trouver la meta $nom_meta_base_version
 * @return void
 */
function maj_plugin($nom_meta_base_version, $version_cible, $maj, $table_meta='meta'){

	if ($table_meta!=='meta')
		lire_metas($table_meta);
	if ( (!isset($GLOBALS[$table_meta][$nom_meta_base_version]) )
			|| (!spip_version_compare($current_version = $GLOBALS[$table_meta][$nom_meta_base_version],$version_cible,'='))){

		// $maj['create'] contient les directives propres a la premiere creation de base
		// c'est une operation derogatoire qui fait aboutir directement dans la version_cible
		if (isset($maj['create'])){
			if (!isset($GLOBALS[$table_meta][$nom_meta_base_version])){
				// installation : on ne fait que l'operation create
				$maj = array("init"=>$maj['create']);
				// et on lui ajoute un appel a inc/config
				// pour creer les metas par defaut
				$config = charger_fonction('config','inc');
				$maj[$version_cible] = array(array($config));
			}
			// dans tous les cas enlever cet index du tableau
			unset($maj['create']);
		}
		// si init, deja dans le bon ordre
		if (!isset($maj['init'])){
			include_spip('inc/plugin'); // pour spip_version_compare
			uksort($maj,'spip_version_compare');
		}

		// la redirection se fait par defaut sur la page d'administration des plugins
		// sauf lorsque nous sommes sur l'installation de SPIP
		// ou define _REDIRECT_MAJ_PLUGIN
		$redirect = (defined('_REDIRECT_MAJ_PLUGIN')?_REDIRECT_MAJ_PLUGIN:generer_url_ecrire('admin_plugin'));
		if (defined('_ECRIRE_INSTALL')) {
			$redirect = parametre_url(generer_url_ecrire('install'),'etape', _request('etape'));
		}
		
		$res = maj_while($current_version, $version_cible, $maj, $nom_meta_base_version, $table_meta, $redirect);
		if ($res) {
			if (!is_array($res))
				spip_log("Pb d'acces SQL a la mise a jour","maj."._LOG_INFO_ERREUR);
			else {
				echo "<p>"._T('avis_operation_echec') . ' ' . join(' ', $res)."</p>";
			}
		}
	}
}

/**
 * Relancer le hit de maj avant timeout
 * si pas de redirect fourni, on redirige vers exec=upgrade pour finir
 * ce qui doit etre une maj SPIP
 * 
 * @param string $meta
 * @param string $table
 * @param string $redirect
 * @return void
 */
function relance_maj($meta,$table,$redirect=''){
	include_spip('inc/headers');
	if (!$redirect){
		// recuperer la valeur installee en cours
		// on la tronque numeriquement, elle ne sert pas reellement
		// sauf pour verifier que ce n'est pas oui ou non
		// sinon is_numeric va echouer sur un numero de version 1.2.3
		$installee = intval($GLOBALS[$table][$meta]);
		$redirect = generer_url_ecrire('upgrade',"reinstall=$installee&meta=$meta&table=$table",true);
	}
	echo redirige_formulaire($redirect);
	exit();
}

/**
 * Initialiser la page pour l'affichage des progres de l'upgrade
 * uniquement si la page n'a pas deja ete initilalisee
 * 
 * @param string $installee
 * @param string $meta
 * @param string $table
 * @return
 */
function maj_debut_page($installee,$meta,$table){
	static $done = false;
	if ($done) return;
	include_spip('inc/minipres');
	@ini_set("zlib.output_compression","0"); // pour permettre l'affichage au fur et a mesure
	$timeout = _UPGRADE_TIME_OUT*2;
	$titre = _T('titre_page_upgrade');
	$balise_img = charger_filtre('balise_img');
	$titre .= $balise_img(chemin_image('searching.gif'));
	echo ( install_debut_html($titre));
	// script de rechargement auto sur timeout
	$redirect = generer_url_ecrire('upgrade',"reinstall=$installee&meta=$meta&table=$table",true);
	echo http_script("window.setTimeout('location.href=\"".$redirect."\";',".($timeout*1000).")");
	echo "<div style='text-align: left'>\n";
	ob_flush();flush();
	$done = true;
}

define('_UPGRADE_TIME_OUT', 20);

/**
 * A partir des > 1.926 (i.e SPIP > 1.9.2), cette fonction gere les MAJ.
 * Se relancer soi-meme pour eviter l'interruption pendant une operation SQL
 * (qu'on espere pas trop longue chacune)
 * evidemment en ecrivant dans la meta a quel numero on en est.
 *
 * Cette fonction peut servir aux plugins qui doivent donner comme arguments:
 * 1. le numero de version courant (numero de version 1.2.3 ou entier)
 * 2. le numero de version a atteindre (numero de version 1.2.3 ou entier)
 * 3. le tableau des instructions de mise a jour a executer
 * Pour profiter du mecanisme de reprise sur interruption il faut de plus
 * 4. le nom de la meta permettant de retrouver tout ca
 * 5. la table des meta ou elle se trouve ($table_prefix . '_meta' par defaut)
 * (cf debut de fichier)
 * en cas d'echec, cette fonction retourne un tableau (etape,sous-etape)
 * sinon elle retourne un tableau vide
 *
 * les fonctions sql_xx appelees lors des maj sont supposees atomiques et ne sont pas relancees
 * en cas de timeout
 * mais les fonctions specifiques sont relancees jusqu'a ce qu'elles finissent
 * elles doivent donc s'assurer de progresser a chaque reprise
 *
 * http://doc.spip.org/@maj_while
 *
 * @param  $installee
 * @param  $cible
 * @param  $maj
 * @param string $meta
 * @param string $table
 * @param string $redirect
 * @param bool $debut_page
 * @return array
 */
function maj_while($installee, $cible, $maj, $meta='', $table='meta', $redirect='', $debut_page = false)
{
	# inclusions pour que les procedures d'upgrade disposent des fonctions de base
	include_spip('base/create');
	include_spip('base/abstract_sql');
	$trouver_table = charger_fonction('trouver_table','base');
	include_spip('inc/plugin'); // pour spip_version_compare
	$n = 0;
	$time = time();
	// definir le timeout qui peut etre utilise dans les fonctions
	// de maj qui durent trop longtemps
	define('_TIME_OUT',$time+_UPGRADE_TIME_OUT);

	reset($maj);
	while (list($v,)=each($maj)) {
		// si une maj pour cette version
		if ($v=='init' OR
			(spip_version_compare($v,$installee,'>')
			AND spip_version_compare($v,$cible,'<='))) {
			if ($debut_page)
				maj_debut_page($v,$meta,$table);
			echo "MAJ $v";
			$etape = serie_alter($v, $maj[$v], $meta, $table, $redirect);
			$trouver_table(''); // vider le cache des descriptions de table
			# echec sur une etape en cours ?
			# on sort
			if ($etape) return array($v, $etape);
			$n = time() - $time;
			spip_log( "$table $meta: $v en $n secondes",'maj.'._LOG_INFO_IMPORTANTE);
			if ($meta) ecrire_meta($meta, $installee=$v,'oui', $table);
			echo "<br />";
		}
		if (time() >= _TIME_OUT) {
			relance_maj($meta,$table,$redirect);
		}
	}
	$trouver_table(''); // vider le cache des descriptions de table
	// indispensable pour les chgt de versions qui n'ecrivent pas en base
	// tant pis pour la redondance eventuelle avec ci-dessus
	if ($meta) ecrire_meta($meta, $cible,'oui',$table);
	spip_log( "MAJ terminee. $meta: $installee",'maj.'._LOG_INFO_IMPORTANTE);
	return array();
}

/**
 * Appliquer une serie de chgt qui risquent de partir en timeout
 * (Alter cree une copie temporaire d'une table, c'est lourd)
 *
 * http://doc.spip.org/@serie_alter
 *
 * @param string $serie
 *   numero de version upgrade
 * @param array $q
 *   tableau des operations pour cette version
 * @param string $meta
 *   nom de la meta qui contient le numero de version
 * @param string $table
 *   nom de la table meta
 * @param string $redirect
 *   url de redirection en cas d'interruption
 * @return int
 */
function serie_alter($serie, $q = array(), $meta='', $table='meta', $redirect='') {
	$meta2 = $meta . '_maj_' . $serie;
	$etape = intval(@$GLOBALS[$table][$meta2]);
	foreach ($q as $i => $r) {
		if ($i >= $etape) {
			$msg = "maj $table $meta2 etape $i";
			if (is_array($r)
			  AND function_exists($f = array_shift($r))) {
				spip_log( "$msg: $f " . join(',',$r),'maj.'._LOG_INFO_IMPORTANTE);
				// pour les fonctions atomiques sql_xx
				// on enregistre le meta avant de lancer la fonction,
				// de maniere a eviter de boucler sur timeout
				// mais pour les fonctions complexes,
				// il faut les rejouer jusqu'a achevement.
				// C'est a elle d'assurer qu'elles progressent a chaque rappel
				if (strncmp($f,"sql_",4)==0)
					ecrire_meta($meta2, $i+1, 'non', $table);
				echo " <span title='$i'>.</span>";
				call_user_func_array($f, $r);
				// si temps imparti depasse, on relance sans ecrire en meta
				// car on est peut etre sorti sur timeout si c'est une fonction longue
				if (time() >= _TIME_OUT) {
					relance_maj($meta,$table,$redirect);
				}
				ecrire_meta($meta2, $i+1, 'non', $table);
				spip_log( "$meta2: ok", 'maj.'._LOG_INFO_IMPORTANTE);
			}
			else {
				if (!is_array($r))
					spip_log("maj $i format incorrect","maj."._LOG_ERREUR);
				else
					spip_log("maj $i fonction $f non definie","maj."._LOG_ERREUR);
				// en cas d'erreur serieuse, on s'arrete
				// mais on permet de passer par dessus en rechargeant la page.
				return $i+1;
			}
		}
	}
	effacer_meta($meta2, $table);
	return 0;
}



// La fonction a appeler dans le tableau global $maj 
// quand on rajoute des types MIME. cf par exemple la 1.953

// http://doc.spip.org/@upgrade_types_documents
function upgrade_types_documents() {
	if (include_spip('base/medias')
	AND function_exists('creer_base_types_doc'))
		creer_base_types_doc();
}

// http://doc.spip.org/@upgrade_test
function upgrade_test() {
	sql_drop_table("spip_test", true);
	sql_create("spip_test", array('a' => 'int'));
	sql_alter("TABLE spip_test ADD b INT");
	sql_insertq('spip_test', array('b' => 1), array('field'=>array('b' => 'int')));
	$result = sql_select('b', "spip_test");
	// ne pas garder le resultat de la requete sinon sqlite3 
	// ne peut pas supprimer la table spip_test lors du sql_alter qui suit
	// car cette table serait alors 'verouillee'
	$result = $result?true:false; 
	sql_alter("TABLE spip_test DROP b");
	return $result;
}

// pour versions <= 1.926
// http://doc.spip.org/@maj_version
function maj_version ($version, $test = true) {
	if ($test) {
		if ($version>=1.922)
			ecrire_meta('version_installee', $version, 'oui');
		else {
			// on le fait manuellement, car ecrire_meta utilise le champs impt qui est absent sur les vieilles versions
			$GLOBALS['meta']['version_installee'] = $version;
			sql_updateq('spip_meta',  array('valeur' => $version), "nom=" . sql_quote('version_installee') );
		}
		spip_log( "mise a jour de la base en $version","maj."._LOG_INFO_IMPORTANTE);
	} else {
		echo _T('alerte_maj_impossible', array('version' => $version));
		exit;
	}
}

// pour versions <= 1.926
// http://doc.spip.org/@upgrade_vers
function upgrade_vers($version, $version_installee, $version_cible = 0){
	return ($version_installee<$version
		AND (($version_cible>=$version) OR ($version_cible==0))
	);
}
?>
