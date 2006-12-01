<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// http://doc.spip.org/@insere_2_init
function insere_2_init($request) {

	// l'insertion porte sur les tables principales ...
	$t = array_keys($GLOBALS['tables_principales']);
	// ... mais pas cette table car elle n'est pas extensible ..
	// (si on essaye ==> duplication sur la cle secondaire)
	unset($t[array_search('spip_types_documents', $t)]);
	// .. ni celle-ci a cause de la duplication des login 
	unset($t[array_search('spip_auteurs', $t)]);
	// et les tables auxiliaires sur les mots car on sait les identifier
	$t[]= 'spip_mots_articles';
	$t[]= 'spip_mots_breves';
	$t[]= 'spip_mots_rubriques';
	$t[]= 'spip_mots_syndic';
	$t[]= 'spip_mots_forum';
	$t[]= 'spip_mots_documents';

	return $t;
}

// http://doc.spip.org/@insere_1_init
function insere_1_init($request) {

  //  preparation de la table des translations
	$spip_translate = array(
		"type"		=> "VARCHAR(16) NOT NULL",
		"ajout"		=> "ENUM('0', '1')",
		"titre"		=> "text NOT NULL",
                "id_old"	=> "BIGINT (21) DEFAULT '0' NOT NULL",
                "id_new"	=> "BIGINT (21) DEFAULT '0' NOT NULL");

	$spip_translate_key = array(
                "PRIMARY KEY"	=> "id_old, id_new, type",
                "KEY id_old"	=> "id_old");

	include_spip('base/create');
	spip_create_table('spip_translate', $spip_translate, $spip_translate_key, true);
	// au cas ou la derniere fois ce serait terminee anormalement
	spip_query("DELETE FROM spip_translate");
	return insere_2_init($request);
}

//   construire le tableau PHP de la table spip_translate
// (mis en table pour pouvoir reprendre apres interruption)

// http://doc.spip.org/@translate_init
function translate_init($request) {

	include_spip('inc/texte.php'); // pour les Regexp des raccourcis

	$q = spip_query("SELECT * FROM spip_translate");
	$trans = array();
	while ($r = spip_fetch_array($q)) {
		$trans[$r['type']][$r['id_old']] = array($r['id_new'], $r['titre'], intval($r['ajout']));
	}
	return $trans;
}


// http://doc.spip.org/@import_insere
function import_insere($values, $table, $desc, $request) {

	$type_id = $desc['key']["PRIMARY KEY"];

	// reserver une place dans les tables principales si nouveau
	$ajout = 0;
	if ((!function_exists($f = 'import_identifie_' . $type_id))
	OR (!$n = $f($values, $table, $desc, $request))) {
		$n = spip_abstract_insert($table, '', '()');
		if (!$n) {
			$GLOBALS['erreur_restauration'] = spip_sql_error();
			return;
		}
		$ajout = 1;
	}
	// et memoriser la correspondance dans la table auxiliaire
	// si different et pas de recherche dessus plus tard
	if (($n != $values[$type_id]) OR $type_id == 'id_groupe') {
		if (is_array($n))
		  list($id, $titre) = $n; 
		else {$id = $n; $titre = "";}
		spip_abstract_insert('spip_translate',
				"(id_old, id_new, titre, type, ajout)",
				     "(". $values[$type_id] .",$id, " . _q($titre) . ", '$type_id', '$ajout')");
	}
}

// Renumerotation des entites collectees
// Le tableau de correspondance est passe en reference pour que le nouveau
// numero d'une entite soit calcule une seule fois, a sa premiere occurrence.
// Si une allocation est finalement necessaire, la renumerotation
// est repercutee sur la table SQL temporaire pour qu'en cas de reprise
// sur Time-Out il n'y ait pas reallocation.
// Autrement on evite cet acces SQL, quitte a recalculer le nouveau numero
// si une autre occurrence est rencontree a la reprise. Pas dramatique.

// http://doc.spip.org/@import_translate
function import_translate($values, $table, $desc, $request) {
	global $trans;
	$vals = '';

	foreach ($values as $k => $v) {
		if ($k=='id_parent' OR $k=='id_secteur') $k = 'id_rubrique';

		$v = importe_translate_maj($k, $v);

		if (preg_match_all(_RACCOURCI_LIEN, $v, $m, PREG_SET_ORDER)) {
			foreach ($m as $regs) {
				$v = importe_raccourci($regs, $k, $v);
			}
		}

		$vals .= "," . _q($v);
	}
	// pas la peine d'ecraser avec les memes valeurs si rien de neuf
	$p = $desc['key']["PRIMARY KEY"];
	$v = $values[$p];
	if (!isset($trans[$p]) OR !isset($trans[$p][$v]) OR $trans[$p][$v][2])
		spip_query($q = "REPLACE $table (" . join(',',array_keys($values)) . ') VALUES (' .substr($vals,1) . ')');
}

function importe_translate_maj($k, $v)
{
  global $trans;
	if (!(isset($trans[$k]) AND isset($trans[$k][$v]))) return $v;

	list($g, $titre, $ajout) = $trans[$k][$v];
	if ($g < 0) {
		$f = 'import_identifie_parent_' . $k;
		$g = $f($g, $titre, $v);
		if ($g > 0)
			  // memoriser qu'on insere
			$trans[$k][$v][2]=1;
		else $g = (0-$g);
		$trans[$k][$v][0] = $g;
	}
	return $g;
}

function importe_raccourci($regs, $k, $v)
{
	$lien = vider_url($regs[3]); # supprimer 'http://' ou 'mailto:'

	if (!$match = typer_raccourci($lien)) return $v;

	list($f,$objet,$id,$params,$ancre) = $match;

	$k = 'id_' . $f;
	$g = importe_translate_maj($k, $id);

	if ($g == $id) return $v;

	$rac = '[' . $regs[1] . '->' . $reg[2] . $objet . $g . $params . $ancre .']';
	return str_replace($regs[0], $rac, $v);
}

// deux groupes de mots ne peuvent avoir le meme titre ==> identification
// http://doc.spip.org/@import_identifie_id_groupe
function import_identifie_id_groupe($values, $table, $desc, $request)  {
	$r = spip_fetch_array(spip_query($q = "SELECT id_groupe, titre FROM spip_groupes_mots WHERE titre=" . _q($values['titre'])), SPIP_NUM);
	return $r;
}

// pour un mot le titre est insuffisant, il faut aussi l'identite du groupe.
// Memoriser ces 2 infos et le signaler a import_translate grace a 1 negatif
// http://doc.spip.org/@import_identifie_id_mot
function import_identifie_id_mot($values, $table, $desc, $request) {
	return array((0 - $values['id_groupe']), $values['titre']);
}

// mot de meme titre et de meme groupe ==> identification
// http://doc.spip.org/@import_identifie_parent_id_mot
function import_identifie_parent_id_mot($id_groupe, $titre, $v)
{
  global $trans;
	$titre = _q($titre);
	$id_groupe = 0-$id_groupe;
	if (isset($trans['id_groupe'])
	AND isset($trans['id_groupe'][$id_groupe])) {
		$new = $trans['id_groupe'][$id_groupe][0];
		$r = spip_fetch_array(spip_query("SELECT id_mot FROM spip_mots WHERE titre=$titre AND id_groupe=$new" ));
		if ($r) return  (0 - $r['id_mot']);
	}
	$r = spip_abstract_insert('spip_mots', '', '()');
	spip_query("REPLACE spip_translate (id_old, id_new, titre, type, ajout) VALUES ($v,$r,$titre,'id_mot',1)");
	return $r;

}

// pour une rubrique le titre est insuffisant, il faut l'identite du parent
// Memoriser ces 2 infos et le signaler a import_translate grace a 1 negatif
function import_identifie_id_rubrique($values, $table, $desc, $request) {
	return array((0 - $values['id_parent']), $values['titre']);
}

// renumerotation en cascade. 
// rubrique de meme titre et de meme parent ==> identification
function import_identifie_parent_id_rubrique($id_parent, $titre, $v)
{
	global $trans;
	if (isset($trans['id_rubrique'])) {
		if ($id_parent < 0) {
			$id_parent = (0 - $id_parent);
			$gparent = $trans['id_rubrique'][$id_parent][0];
			// parent deja renumerote depuis le debut la passe 2
			if ($gparent > 0)
			  $id_parent = $gparent;
			else {
			  // premiere occurrence du parent
				$pitre = $trans['id_rubrique'][$id_parent][1];
				$n = import_identifie_parent_id_rubrique($gparent, $pitre, $id_parent);
				$trans['id_rubrique'][$id_parent][0] = ($n>0) ? $n: (0-$n);
				// parent tout neuf,
				// pas la peine de chercher un titre homonyme
				if ($n > 0) {
					$trans['id_rubrique'][$id_parent][2]=1; // nouvelle rub.
					return import_alloue_id_rubrique($n, $titre, $v);
				} else $id_parent = (0 - $n);
			}
		}

		$r = spip_fetch_array(spip_query("SELECT id_rubrique FROM spip_rubriques WHERE titre=" . _q($titre) . " AND id_parent=" . intval($id_parent)));
		if ($r)  {
		  return (0 - $r['id_rubrique']);
		}
		return import_alloue_id_rubrique($id_parent, $titre, $v);
	}
}

// reserver la place en mettant titre et parent tout de suite
// pour que le SELECT ci-dessus fonctionne a la prochaine occurrence

function import_alloue_id_rubrique($id_parent, $titre, $v) {
	$titre = _q($titre);
	$r = spip_abstract_insert('spip_rubriques', '(titre, id_parent)', "($titre,$id_parent)");
	spip_query("REPLACE spip_translate (id_old, id_new, titre, type, ajout) VALUES ($v,$r,$titre,'id_rubrique',1)");
	return $r;
}

?>
