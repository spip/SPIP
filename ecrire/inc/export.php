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

define('_EXPORT_TRANCHES_LIMITE', 200);
define('_EXTENSION_PARTIES', '.gz');

// http://doc.spip.org/@exec_export_all_args
function inc_export_dist($meta)
{
	if (!isset($GLOBALS['meta'][$meta])) {
		include_spip('inc/minipres');
		echo minipres();
	}
	else {
		$start = false;
		list($gz, $archive, $rub, $tables_for_dump, $etape_actuelle, $sous_etape) = 
			unserialize($GLOBALS['meta'][$meta]);

		// determine upload va aussi initialiser l'index "restreint"
		$maindir = determine_upload();
		if (!$GLOBALS['visiteur_session']['restreint'])
			$maindir = _DIR_DUMP;
		$dir = sous_repertoire($maindir, $meta);
		$file = $dir . $archive;
		$metatable = $meta . '_tables';

		// Reperer une situation anormale (echec reprise sur interruption)
		if (!$etape_actuelle AND !$sous_etape) {
			$l = preg_files($file .  ".part_[0-9]+_[0-9]+");
			if ($l) {
				spip_log("menage d'une sauvegarde inachevee: " . join(',', $l));
				foreach($l as $dummy) spip_unlink($dummy);
			}
			$start = true; //  utilise pour faire un premier hit moitie moins long
			$tables_sauvegardees = array();
		} else 	$tables_sauvegardees = isset($GLOBALS['meta'][$metatable])?unserialize($GLOBALS['meta'][$metatable]):array();

		// concatenation des fichiers crees a l'appel precedent
		ramasse_parties($dir, $archive);
		$all = count($tables_for_dump);
		if ($etape_actuelle > $all OR !$all){
			return "end,$gz,$archive,$rub"; // c'est fini !
		}

		include_spip('inc/minipres');
		@ini_set("zlib.output_compression","0"); // pour permettre l'affichage au fur et a mesure

		echo ( install_debut_html(_T('info_sauvegarde') . " ($all)"));

		if (!($timeout = ini_get('max_execution_time')*1000));
		$timeout = 30000; // parions sur une valeur tellement courante ...
	// le premier hit est moitie moins long car seulement une phase d'ecriture de morceaux
	// sans ramassage
	// sinon grosse ecriture au 1er hit, puis gros rammassage au deuxieme avec petite ecriture,... ca oscille
		if ($start) $timeout = round($timeout/2);

	// Les sauvegardes partielles prennent le temps d'indiquer les logos
	// Instancier une fois pour toutes, car on va boucler un max.
	// On complete jusqu'au secteur pour resituer dans l'arborescence)
		if ($rub) {
			$GLOBALS['chercher_logo'] = charger_fonction('chercher_logo', 'inc',true);
			$les_rubriques = complete_fils(array($rub));
			$les_meres  = complete_secteurs(array($rub));
		} else {
			$GLOBALS['chercher_logo'] = false;
			$les_rubriques = $les_meres = '';
		}

	// script de rechargement auto sur timeout
		$redirect = generer_url_ecrire("export_all");
		echo http_script("window.setTimeout('location.href=\"".$redirect."\";',$timeout)");

		echo "<div style='text-align: left'>\n";
		$etape = 1;
		foreach($tables_for_dump as $table){
			if ($etape_actuelle > $etape) {
				 // sauter les deja faits, mais rappeler qu'ils sont fait
				echo ( "\n<br /><strong>".$etape. '. '."</strong>". $tables_sauvegardees[$table]);
			}
			else {
				echo ( "\n<br /><strong>".$etape. '. '. $table."</strong> ");
			  $r = sql_countsel($table);
			  flush();
			  if (!$r) $r = ( _T('texte_vide'));
			  else {
			    $f = $dir . $archive . '.part_' . sprintf('%03d',$etape);
			    $r = export_objets($table, $sous_etape, $r, $f, $les_rubriques, $les_meres, $meta);
			    $r += $sous_etape*_EXPORT_TRANCHES_LIMITE;
			    // info pas fiable si interruption+partiel
			    if ($rub AND $etape_actuelle > 1) $r = ">= $r";
			  }
			  echo " $r";
			  flush();
			  $sous_etape = 0;
			  // on utilise l'index comme ca c'est pas grave si on ecrit plusieurs fois la meme
			  $tables_sauvegardees[$table] = "$table ($r)";
			  ecrire_meta($metatable, serialize($tables_sauvegardees),'non');
			}
			$etape++;
			$v = serialize(array($gz, $archive, $rub, $tables_for_dump, $etape,$sous_etape));
			ecrire_meta($meta, $v,'non');
		}
		echo ( "</div>\n");
		// si Javascript est dispo, anticiper le Time-out
		echo  ("<script language=\"JavaScript\" type=\"text/javascript\">window.setTimeout('location.href=\"$redirect\";',0);</script>\n");
		echo (install_fin_html());
		flush();
	}
}


// http://doc.spip.org/@complete_secteurs
function complete_secteurs($les_rubriques)
{
	$res = array();
	foreach($les_rubriques as $r) {
		do {
			$r = sql_getfetsel("id_parent", "spip_rubriques", "id_rubrique=$r");
			if ($r) {
				if ((isset($les_rubriques[$r])) OR isset($res[$r]))
					$r = false;
				else  $res[$r] = $r;
			}
		} while ($r);
	}
	return $res;
}

// http://doc.spip.org/@complete_fils
function complete_fils($rubriques)
{
	$r = $rubriques;
	do {
		$q = sql_select("id_rubrique", "spip_rubriques", "id_parent IN (".join(',',$r).")");
		$r = array();
		while ($row = sql_fetch($q)) {
			$r[]= $rubriques[] = $row['id_rubrique'];
		}
	} while ($r);


	return $rubriques;
}

// Concatenation des tranches
// Il faudrait ouvrir une seule fois le fichier, et d'abord sous un autre nom
// et sans detruire les tranches: au final renommage+destruction massive pour
// prevenir autant que possible un Time-out.

// http://doc.spip.org/@ramasse_parties
function ramasse_parties($dir, $archive)
{
	$files = preg_files($dir . $archive . ".part_[0-9]+_[0-9]+[.gz]?");

	$ok = true;
	$files_o = array();
	$but = $dir . $archive;
	foreach($files as $f) {
	  $contenu = "";
	  if (lire_fichier ($f, $contenu)) {
	    if (!ecrire_fichier($but,$contenu,false,false))
	      { $ok = false; break;}
	  }
	  spip_unlink($f);
	  $files_o[]=$f;
	}
	return $ok ? $files_o : false;
}

//
// Exportation de table SQL au format xml
// La constante ci-dessus determine la taille des tranches,
// chaque tranche etant copiee immediatement dans un fichier 
// et son numero memorisee dans le serveur SQL.
// En cas d'abandon sur Time-out, le travail pourra ainsi avancer.
// Au final, on regroupera les tranches en un seul fichier
// et on memorise dans le serveur qu'on va passer a la table suivante.
// on prefere ne pas faire le ramassage ici de peur d'etre interrompu 
// par le timeout au mauvais moment
// le ramassage aura lieu en debut de hit suivant, 
// et ne sera normalement pas interrompu car le temps pour ramasser
// est plus court que le temps pour creer les parties

// http://doc.spip.org/@export_objets
function export_objets($table, $cpt, $total, $filetable, $les_rubriques, $les_meres, $meta) {
	global $tables_principales;

	$temp = $filetable . '.temp' . _EXTENSION_PARTIES;
	$prim = isset($tables_principales[$table])
	  ? $tables_principales[$table]['key']["PRIMARY KEY"]
	  : '';
	$debut = $cpt * _EXPORT_TRANCHES_LIMITE;
	$effectifs = 0;

	while (1){ // on ne connait pas le nb de paquets d'avance

		$cpt++;
		$tranche = build_while($debut, $table, $prim, $les_rubriques, $les_meres);
		// attention: vide ne suffit pas a sortir
		// car les sauvegardes partielles peuvent parcourir
		// une table dont la portion qui les concerne sera vide..
		if ($tranche) { 
		// on ecrit dans un fichier generique
		// puis on le renomme pour avoir une operation atomique 
			ecrire_fichier ($temp, join('', $tranche));
			$f = $filetable . sprintf('_%04d',$cpt) . _EXTENSION_PARTIES;
	// le fichier destination peut deja exister
	// si on sort d'un timeout entre le rename et le ecrire_meta
			if (file_exists($f)) spip_unlink($f);
			rename($temp, $f);
			$effectifs += count($tranche);
		}
		// incrementer le numero de sous-etape 
		// au cas ou une interruption interviendrait
		$v = unserialize($GLOBALS['meta'][$meta]);
		$v[5]++;
		ecrire_meta($meta, serialize($v));
		$debut +=  _EXPORT_TRANCHES_LIMITE;
		if ($debut >= $total) {break;}
		/* pour tester la robustesse de la reprise sur interruption
		 decommenter ce qui suit.
		if ($cpt && 1) {
		  spip_log("force interrup $s");
		  include_spip('inc/headers');
		  redirige_par_entete("./?exec=export_all&rub=$rub&x=$s");
		  } /* */
		echo(". ");
		flush();
	}

	return $effectifs;
}


// Construit la version xml  des champs d'une table

// http://doc.spip.org/@build_while
function build_while($debut, $table, $prim, $les_rubriques, $les_meres) {
	global  $chercher_logo ;

	// sauver par ordre croissant les tables avec cles primaires simples
	// sinon les sequences PG seront pertubees a la restauration
	// (a ameliorer)
	$result = sql_select('*', $table, '', '', $prim, "$debut," . _EXPORT_TRANCHES_LIMITE);

	$res = array();
	while ($row = sql_fetch($result)) {
		if (export_select($row, $les_rubriques, $les_meres)) {
			$attributs = "";
			if ($chercher_logo) {
				if ($logo = $chercher_logo($row[$prim], $prim, 'on'))
					$attributs .= ' on="' . $logo[3] . '"';
				if ($logo = $chercher_logo($row[$prim], $prim, 'off'))
					$attributs .= ' off="' . $logo[3] . '"';
			}

			$string = "<$table$attributs>\n";
			foreach ($row as $k => $v) {
				$string .= "<$k>" . text_to_xml($v) . "</$k>\n";
			}
			$string .= "</$table>\n\n";
			$res[]= $string;
		}
	}
	sql_free($result);
	return $res;
}

// dit si Row est exportable, 
// en particulier quand on se restreint a certaines rubriques
// Attention, la table articles doit etre au debut 
// et la table document_articles avant la table documents
// (faudrait blinder, c'est un bug potentiel)

// http://doc.spip.org/@export_select
function export_select($row, $les_rubriques, $les_meres) {
	static $articles = array();
	static $documents = array();

	if (isset($row['impt']) AND $row['impt'] !='oui') return false;
	if (!$les_rubriques) return true;

	// numero de rubrique non determinant pour les forums (0 � 99%)
	if (isset($row['id_rubrique']) AND $row['id_rubrique']) {
		if (in_array($row['id_rubrique'], $les_rubriques)) {
			if (isset($row['id_article']))
				$articles[] = $row['id_article'];
			if (isset($row['id_document']))
				$documents[]=$row['id_document'];
			return true;
		}
		if (!in_array($row['id_rubrique'], $les_meres))
			return false;
		// la rubrique, mais rien d'autre
		return (!isset($row['id_article'])
			AND !isset($row['id_mot'])
			AND !isset($row['id_document'])
			AND !isset($row['id_breve']));
	}
	//  dependances d'articles (mots, petitions, signatures et documents)
	if (isset($row['id_article']) AND $row['id_article']) {
		if (in_array($row['id_article'], $articles)) {
			if (isset($row['id_document']))
				$documents[]= $row['id_document'];
			return true;
		}
		return false;
	}
	if (isset($row['id_objet']) AND isset($row['objet'])) {
		if ($row['objet'] == 'article') {
			if (in_array($row['id_objet'], $articles)) {
				if (isset($row['id_document']))
					$documents[]= $row['id_document'];
				return true;
			}
			return false;
		}
		if ($row['objet'] == 'rubrique') {
			if (in_array($row['id_objet'], $les_rubriques)) {
				if (isset($row['id_document']))
					$documents[]=$row['id_document'];
				return true;
			}
			return false;
		}
	}

	if (isset($row['id_document']) AND $row['id_document']) {
		return array_search($row['id_document'], $documents);
	}
	// a la louche pour le reste, mais c'est a peu pres ca.
	return (isset($row['id_groupe']) OR isset($row['id_mot']) OR isset($row['mime_type']));
}

// Conversion texte -> xml (ajout d'entites)
// http://doc.spip.org/@text_to_xml
function text_to_xml($string) {
	return str_replace(array('&','<','>'), array('&amp;','&lt;','&gt;'), $string);
}

?>
