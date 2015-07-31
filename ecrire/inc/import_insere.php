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


// http://doc.spip.org/@insere_1_init
function insere_1_init($request) {

//  table des translations

	$res = insere_1bis_init($request);
	$desc = $GLOBALS['tables_principales'][_SPIP_TRANSLATE];
	$v = sql_create(_SPIP_TRANSLATE, $desc['field'], $desc['key'], true);
	if (!$v) {
		spip_log("echec de la creation de la table de fusion");
		return  false; 
	}
	// au cas ou la derniere fois ce serait terminee anormalement
	$d = sql_delete(_SPIP_TRANSLATE);
	spip_log("init " . _SPIP_TRANSLATE . " : $d");
	return $res;
}

// http://doc.spip.org/@insere_1bis_init
function insere_1bis_init($request='') {

	// l'insertion porte sur les tables principales ...
	$t = array_keys($GLOBALS['tables_principales']);
	// ... mais pas cette table a cause de la duplication des login 
	unset($t[array_search('spip_auteurs', $t)]);
	// ni celle-ci, les qui est liee implicitement a la precedente
	unset($t[array_search('spip_messages', $t)]);
	// et pour celles-ci restent a programmer les regles
	unset($t[array_search('spip_forum', $t)]);
	unset($t[array_search('spip_syndic', $t)]);
	unset($t[array_search('spip_signatures', $t)]);

	$field = array(
		"type"		=> "VARCHAR(16) NOT NULL",
		"ajout"		=> "integer NOT NULL", // en fait booleen
		"titre"		=> "text NOT NULL",
                "id_old"	=> "bigint (21) DEFAULT '0' NOT NULL",
                "id_new"	=> "bigint (21) DEFAULT '0' NOT NULL");

	$key = array(
                "PRIMARY KEY"	=> "id_old, id_new, type",
                "KEY id_old"	=> "id_old");

	define('_SPIP_TRANSLATE',
	       'spip_translate_' . $GLOBALS['visiteur_session']['id_auteur']);

	// Pour que trouver_base ne fasse pas des siennes
	// mais evidemment elle n'est pas a transferer!
	$GLOBALS['tables_principales'][_SPIP_TRANSLATE] = 
		array('field' => $field, 'key' => $key);

	return $t;
}

// En passe 2, relire les tables principales et les tables auxiliaires 
// sur les mots et les documents car on sait les identifier

// http://doc.spip.org/@insere_2_init
function insere_2_init($request) {
	$t = insere_1bis_init($request);

	// ne pas importer cette table, son homologue est prioritaire
	unset($t[array_search('spip_types_documents', $t)]);

	$t[]= 'spip_mots_articles';
	$t[]= 'spip_mots_breves';
	$t[]= 'spip_mots_rubriques';
	$t[]= 'spip_mots_syndic';
	$t[]= 'spip_mots_forum';
	$t[]= 'spip_mots_documents';
	$t[]= 'spip_documents_liens';

	return $t;
}

//   construire le tableau PHP de la table spip_translate
// (mis en table pour pouvoir reprendre apres interruption)

// http://doc.spip.org/@translate_init
function translate_init($request) {

	include_spip('inc/texte'); // pour les Regexp des raccourcis
	include_spip('inc/chercher_logo'); // pour les noms des logos
	include_spip('inc/distant'); // pour recuperer les logos

	$q = sql_select('*', _SPIP_TRANSLATE);
	$trans = array();
	while ($r = sql_fetch($q)) {
		$trans[$r['type']][$r['id_old']] = array($r['id_new'], $r['titre'], $r['ajout']);
	}
	$r = '';
	foreach ($trans as $k =>$v) $r .= " $k: " . count($v);
	spip_log( count($trans) . " types d'objets a transferer: $r");
	return $trans;
}


// http://doc.spip.org/@import_insere
function import_insere($values, $table, $desc, $request, $atts) {

	static $jesais = array();

	$type_id = $desc['key']["PRIMARY KEY"];
	// reserver une place dans les tables principales si nouveau
	$ajout = 0;

	if ((!($f = charger_fonction('identifie_' . $type_id, 'import', true)))
	OR (!($n = $f($values, $table, $desc, $request)))) {
          // pas d'importation de types_doc (a revoir)
		if ($table == 'spip_types_documents') return;
		$n = sql_insertq($table);
		$ajout=1;
	}

	if (is_array($n))
		list($id, $titre) = $n; 
	else {$id = $n; $titre = "";}
	sql_insertq(_SPIP_TRANSLATE, array(
		   'id_old' => $values[$type_id],
		   'id_new' => $id,
		   'titre' => $titre,
		   'type' => $type_id,
		   'ajout' => $ajout));
}

// Renumerotation des entites collectees
// Appelle la fonction specifique a la table, ou a defaut la std.
// Le tableau de correspondance est global, et permet qu'un numero
// d'une entite soit calcule une seule fois, a sa premiere occurrence.
// (Mais des requetes avec jointures eviteraient sa construction. A voir)

// http://doc.spip.org/@import_translate
function import_translate($values, $table, $desc, $request, $atts) {

	if (!($f = charger_fonction('translate_' . $table, 'import', true)))
		$f = charger_fonction('translate_std', 'import');
	$f($values, $table, $desc, $request, $atts);
}

// La fonction d'insertion apres renumerotation.
// Afin qu'inserer une 2e fois la meme sauvegarde ne change pas la base,
// chaque entree de la sauvegarde est ignoree s'il existe une entree
// de meme titre avec le meme contexte (parent etc) dans la base installee.
// Une synchronisation plus fine serait preferable, cf [8004]

// http://doc.spip.org/@import_inserer_translate
function import_inserer_translate($values, $table, $desc, $request, $atts) {
	global $trans;
	$p = $desc['key']["PRIMARY KEY"];
	$v = $values[$p];
	if (!isset($trans[$p]) OR !isset($trans[$p][$v]) OR $trans[$p][$v][2]){
		sql_replace($table, $values);
		$on = isset($atts['on']) ? ($atts['on']) : '';
		$off = isset($atts['off']) ? ($atts['off']) : '';
		if ($on OR $off) {
			$t = type_du_logo($p);		  
			$url = $request['url_site'];
			if (!$url) $url = $atts['adresse_site'];
			if (substr($url,-1) !='/') $url .='/';
			$url .= $atts['dir_logos'];
			$new = $trans[$p][$v][0];
			if ($on) {
			  if ($logo = recuperer_page($url . $t . "on$v." . $on))
			    ecrire_fichier(_DIR_LOGOS. $t . "on$new." . $on, $logo);
			}
			if ($off) {
			  if ($logo = recuperer_page($url . $t . "off$v." . $off))
			    ecrire_fichier(_DIR_LOGOS. $t . "off$new." . $off, $logo);
			}
		}
	}
}

// Insertion avec renumerotation, y compris des raccourcis.
// http://doc.spip.org/@import_translate_std
function import_translate_std_dist($values, $table, $desc, $request, $atts) {

	foreach ($values as $k => $v) {
	  switch($k) {
	  case 'id_secteur': $type = 'id_rubrique'; break;
	  case 'id_thread': $type = 'id_forum'; break;
	  case 'id_vignette': $type = 'id_document'; break;
	  case 'id_parent': $type =
	      isset($values['id_forum']) ? 'id_forum' : 'id_rubrique'; break;
	  default: $type = $k;
	  }
	  $values[$k]= importe_raccourci(importe_translate_maj($type, $v));
	}
	import_inserer_translate($values, $table, $desc, $request, $atts);
}

// http://doc.spip.org/@import_translate_spip_articles
function import_translate_spip_articles_dist($values, $table, $desc, $request, $atts) {
	$v = $values['chapo']; 
	if ($v[0]=='=' AND preg_match(_RACCOURCI_CHAPO, substr($v,1)))
		$values['chapo'] = '=[->' . substr($v,1) . ']';
	if ($request['statut'] == 'on' AND $values['statut'] == 'publie')
		$values['statut'] = 'prop';
	import_translate_std_dist($values, $table, $desc, $request, $atts);
}

// http://doc.spip.org/@import_translate_spip_breves
function import_translate_spip_breves_dist($values, $table, $desc, $request, $atts) {
	if ($request['statut'] == 'on' AND $values['statut'] == 'publie')
		$values['statut'] = 'prop';
	import_translate_std_dist($values, $table, $desc, $request, $atts);
}

// Les doc importes deviennent distants, a fortiori s'ils etaient deja
// Gerer les vieilles sauvegardes où le Path etait en dur
// http://doc.spip.org/@import_translate_spip_documents
function import_translate_spip_documents_dist($values, $table, $desc, $request, $atts) {

	if ($values['distant'] === 'oui') {
		$url = '';
	} else {
		$values['distant'] = 'oui';
		$url = $request['url_site'];
		if (!$url) $url = $atts['adresse_site'];
		if (substr($url,-1) !='/') $url .='/';
		// deja dans la BD avant cette epoque
		if ($atts['version_base'] >= '1.934')
			$url .= $atts['dir_img']; 
	}
	$url .= $values['fichier'];
	unset($values['fichier']);
	foreach ($values as $k => $v) {
		$values[$k]= importe_raccourci(importe_translate_maj($k, $v));

	}
	$values['fichier'] = $url;
	import_inserer_translate($values, $table, $desc, $request, $atts);
}

function import_translate_spip_documents_liens_dist($values, $table, $desc, $request, $atts) {

	$values['id_document']= (importe_translate_maj('id_document', $values['id_document']));
	$values['id_objet']= (importe_translate_maj('id_' .$values['objet'], $values['id_objet']));

	sql_replace($table, $values);
}

// Fonction de renumerotation, par delegation aux fonction specialisees
// Si une allocation est finalement necessaire, celles-ci doivent repercuter
// la renumerotation sur la table SQL temporaire pour qu'en cas de reprise
// sur Time-Out il n'y ait pas reallocation.
// En l'absence d'allocation, cet acces SQL peut etre omis, quitte a 
// recalculer le nouveau numero  si une autre occurrence est rencontree
// a la reprise. Pas dramatique.

// http://doc.spip.org/@importe_translate_maj
function importe_translate_maj($k, $v)
{
	global $trans;
	static $err = array();
	if (!(isset($trans[$k]) AND isset($trans[$k][$v]))) return $v;
	list($g, $titre, $ajout) = $trans[$k][$v];
	if ($g <= 0) {
	  if (($f = charger_fonction('identifie_parent_' . $k, 'import', true))){
			$g = $f($g, $titre, $v);
			if ($g > 0)
			  // memoriser qu'on insere
				$trans[$k][$v][2]=1;
			else $g = (0-$g);
			$trans[$k][$v][0] = $g;
	  } else {
	    if (!isset($err[$f])) spip_log("$f manquante");
	    $err[$f] = true;
	  }
	}
	return $g;
}

define('_RACCOURCI_MODELE_ALL', '@' . _RACCOURCI_MODELE .'@isS');

// http://doc.spip.org/@importe_raccourci
function importe_raccourci($v)
{
	if (preg_match_all(_RACCOURCI_LIEN, $v, $m, PREG_SET_ORDER)) {
		foreach ($m as $regs) {
		  // supprimer 'http://' ou 'mailto:'
		  	$lien = vider_url($regs[count($regs)-1]);
			if ($match = typer_raccourci($lien)) {
				list($f,$objet,$id,$params,$ancre) = $match;
				$k = 'id_' . $f;
				$g = importe_translate_maj($k, $id);
				if ($g != $id) {

				  $rac = '[' . $regs[1] . '->' . $regs[2] . $objet . $g . $params . $ancre .']';
				  $v = str_replace($regs[0], $rac, $v);
				}
			}
		}
	}

	if (preg_match_all(_RACCOURCI_MODELE_ALL, $v, $m, PREG_SET_ORDER)) {
		foreach ($m as $regs) {
			$g = importe_translate_maj('id_document', $regs[3]);
			if ($g != $regs[3]) {
				$rac = '<' . $regs[2] . $g . $regs[4] . '>' . $regs[5];
				$v = str_replace($regs[0], $rac, $v);
			}
		}
	}
	return $v;
}

// un document importe est considere comme identique a un document local
// s'ils ont meme taille et meme nom et que le present n'est pas detruit
// Et ne pas importer les incoherences (docs sans extension)
// http://doc.spip.org/@import_identifie_id_document
function import_identifie_id_document_dist($values, $table, $desc, $request) {
	if (!$values['extension']) return false;
	$t = $values['taille'];
	$f = $values['fichier'];
	$h = $request['url_site'] . $f;
	$r = sql_fetsel("id_document AS id, fichier AS titre, distant", "spip_documents", "taille=" . sql_quote($t) . " AND (fichier=" . sql_quote($f) . " OR fichier= " . sql_quote($h) . ')');
	if (!$r) return false;
	if (($r['distant'] != 'oui')
	AND !file_exists(_DIR_IMG . $r['titre']))
		return false; 
	return array($r['id'], $r['titre']);
}

// un type de document importe est considere comme identique a un type present
// s'ils ont meme extension et meme titre
// Sinon il ne sera PAS importe
// http://doc.spip.org/@import_identifie_id_type
function import_identifie_id_type_dist($values, $table, $desc, $request) {
	$e = $values['extension'];
	$t = $values['titre'];
	$r = sql_fetsel("id_type AS id, titre", "spip_types_documents", "extension=" . sql_quote($e) . " AND titre=" . sql_quote($t));
	return $r ? array($r['id'], $r['titre']) : false;
}

// deux groupes de mots ne peuvent avoir le meme titre ==> identification
// http://doc.spip.org/@import_identifie_id_groupe
function import_identifie_id_groupe_dist($values, $table, $desc, $request)  {
	$r = sql_fetsel("id_groupe AS id, titre", "spip_groupes_mots", "titre=" . sql_quote($values['titre']));
	return $r ? array($r['id'], $r['titre']) : false;
}

// pour un mot le titre est insuffisant, il faut aussi l'identite du groupe.
// Memoriser ces 2 infos et le signaler a import_translate grace a 1 negatif
// http://doc.spip.org/@import_identifie_id_mot
function import_identifie_id_mot_dist($values, $table, $desc, $request) {
	return array((0 - $values['id_groupe']), $values['titre']);
}

// Passe 2: mot de meme titre et de meme groupe ==> identification
// http://doc.spip.org/@import_identifie_parent_id_mot
function import_identifie_parent_id_mot_dist($id_groupe, $titre, $v)
{
	global $trans;
	$titre = sql_quote($titre);
	$id_groupe = 0-$id_groupe;
	if (isset($trans['id_groupe'])
	AND isset($trans['id_groupe'][$id_groupe])) {
		$new = $trans['id_groupe'][$id_groupe][0];
		$r = sql_fetsel("id_mot", "spip_mots", "titre=$titre AND id_groupe=$new" );
		if ($r) return  (0 - $r['id_mot']);
	}
	if ($r = sql_insertq('spip_mots'))
		sql_replace(_SPIP_TRANSLATE, array(
					    'id_old' => $v,
					    'id_new' => $r,
					    'titre' => $titre,
					    'type' => 'id_mot',
					    'ajout' => 1),
			    $GLOBALS['tables_principales'][_SPIP_TRANSLATE]);
	else spip_log("Impossible d'inserer dans spip_mots");
	return $r;
}

// idem pour les articles
// http://doc.spip.org/@import_identifie_id_article
function import_identifie_id_article_dist($values, $table, $desc, $request) {
	return array((0 - $values['id_rubrique']), $values['titre']);
}

// Passe 2 des articles comme pour les mots

// http://doc.spip.org/@import_identifie_parent_id_article
function import_identifie_parent_id_article_dist($id_parent, $titre, $v)
{
	$id_parent = importe_translate_maj('id_rubrique', (0 - $id_parent));

	$titre = sql_quote($titre);
	$r = sql_fetsel("id_article", "spip_articles", "titre=$titre AND id_rubrique=$id_parent AND statut<>'poubelle'" );
	if ($r) return (0 - $r['id_article']);

	if ($r = sql_insertq('spip_articles'))
		sql_replace(_SPIP_TRANSLATE, array(
					    'id_old' => $v,
					    'id_new' => $r,
					    'titre' => $titre,
					    'type' => 'id_article',
					    'ajout' => 1),
			$GLOBALS['tables_principales'][_SPIP_TRANSLATE]
		    );
	else spip_log("Impossible d'inserer dans spip_articles");
	return $r;
}

// idem pour les breves
// http://doc.spip.org/@import_identifie_id_breve
function import_identifie_id_breve_dist($values, $table, $desc, $request) {
	return array((0 - $values['id_rubrique']), $values['titre']);
}

// Passe 2 des breves comme pour les mots

// http://doc.spip.org/@import_identifie_parent_id_breve
function import_identifie_parent_id_breve_dist($id_parent, $titre, $v)
{
	$id_parent = importe_translate_maj('id_rubrique', (0 - $id_parent));

	$titre = sql_quote($titre);
	$r = sql_fetsel("id_breve", "spip_breves", "titre=$titre AND id_rubrique=$id_parent AND statut<>'refuse'" );
	if ($r) return (0 - $r['id_breve']);

	if ($r = sql_insertq('spip_breves'))
		sql_replace(_SPIP_TRANSLATE, array(
					    'id_old' => $v,
					    'id_new' => $r,
					    'titre' => $titre,
					    'type' => 'id_breve',
					    'ajout' => 1),
			$GLOBALS['tables_principales'][_SPIP_TRANSLATE]
		    );
	else spip_log("Impossible d'inserer dans spip_breves");
	return $r;
}


// pour une rubrique le titre est insuffisant, il faut l'identite du parent
// Memoriser ces 2 infos et le signaler a import_translate grace a 1 negatif
// http://doc.spip.org/@import_identifie_id_rubrique
function import_identifie_id_rubrique_dist($values, $table, $desc, $request) {
	return array((0 - $values['id_parent']), $values['titre']);
}

// Passe 2 des rubriques, renumerotation en cascade. 
// rubrique de meme titre et de meme parent ==> identification
// http://doc.spip.org/@import_identifie_parent_id_rubrique
function import_identifie_parent_id_rubrique_dist($id_parent, $titre, $v)
{
	global $trans;
	if (isset($trans['id_rubrique'])) {
		if ($id_parent < 0) {
			$id_parent = (0 - $id_parent);
			$gparent = $trans['id_rubrique'][$id_parent][0];
			// parent deja renumerote depuis le debut la passe 2
			if ($gparent >= 0)
			  $id_parent = $gparent;
			else {
			  // premiere occurrence du parent
				$pitre = $trans['id_rubrique'][$id_parent][1];
				$n = import_identifie_parent_id_rubrique_dist($gparent, $pitre, $id_parent);
				$trans['id_rubrique'][$id_parent][0] = ($n>0) ? $n: (0-$n);
				// parent tout neuf,
				// pas la peine de chercher un titre homonyme
				if ($n > 0) {
					$trans['id_rubrique'][$id_parent][2]=1; // nouvelle rub.
					return import_alloue_id_rubrique($n, $titre, $v);
				} else $id_parent = (0 - $n);
			}
		}

		$r = sql_fetsel("id_rubrique", "spip_rubriques", "titre=" . sql_quote($titre) . " AND id_parent=" . intval($id_parent));
		if ($r)  {
		  return (0 - $r['id_rubrique']);
		}
		return import_alloue_id_rubrique($id_parent, $titre, $v);
	}
}

// reserver la place en mettant titre et parent tout de suite
// pour que le SELECT ci-dessus fonctionne a la prochaine occurrence

// http://doc.spip.org/@import_alloue_id_rubrique
function import_alloue_id_rubrique($id_parent, $titre, $v) {
	if ($r = sql_insertq('spip_rubriques', array('titre' => $titre, 'id_parent' => $id_parent)))
		sql_replace(_SPIP_TRANSLATE, array(
		    'id_old' => $v,
		    'id_new' => $r,
		    'titre' => $titre,
		    'type' => 'id_rubrique',
		    'ajout' => 1),
	  $GLOBALS['tables_principales'][_SPIP_TRANSLATE]);
	else spip_log("Impossible d'inserer dans spip_rubriques");
	return $r;
}
?>
