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


// pour le support des vieux dump
function inc_import_1_2_dist($f, $gz=false) {
	global $import_ok, $pos, $abs_pos;

	// detruire les tables a restaurer
	if (!$abs_pos) import_init_tables();

	static $tables;
	if (!$tables) $tables = array(
		'article' => 'spip_articles',
		'auteur' => 'spip_auteurs',
		'breve' => 'spip_breves',
		'document' => 'spip_documents',
		'forum' => 'spip_forum',
		'groupe_mots' => 'spip_groupes_mots',
		'message' => 'spip_messages',
		'mot' => 'spip_mots',
		'petition' => 'spip_petitions',
		'rubrique' => 'spip_rubriques',
		'signature' => 'spip_signatures',
		'syndic' => 'spip_syndic',
		'syndic_article' => 'spip_syndic_articles',
		'type_document' => 'spip_types_documents'
	);

	$import_ok = false;
	$b = '';
	// Lire le type d'objet
	if (!($type = xml_fetch_tag($f, $b, $gz))) return false;
	if ($type == '/SPIP') return !($import_ok = true);
	$id = "id_$type";
	$id_objet = 0;

	// Lire les champs de l'objet
	for (;;) {
		$b = '';
		if (!($col = xml_fetch_tag($f, $b, $gz))) return false;
		if ($col == '/'.$type) break;
		$value = '';
		if (!xml_fetch_tag($f, $value, $gz)) return false;
		if (substr($col, 0, 5) == 'lien:') {
			$type_lien = substr($col, 5);
			$liens[$type_lien][] = '('.$id_objet.','.$value.')';
		}
		else if ($col != 'maj') {
			// tentative de restauration d'une base sauvegardee avec le champ 'images' ; d'experience, ca arrive...
			// mieux vaut accepter que canner silencieusement...
			if (($type == 'article') && ($col == 'images'))
			{
				if ($value) {		// ne pas afficher de message si on a un champ suppl mais vide
					echo "--><br><font color='red'><b>"._T('avis_erreur_sauvegarde', array('type' => $type, 'id_objet' => $id_objet))."</b></font>\n<font color='black'>"._T('avis_colonne_inexistante', array('col' => $col));
					if ($col == 'images') echo _T('info_verifier_image');
					echo "</font>\n<!--";
					$GLOBALS['erreur_restauration'] = true;
				}
			}
			else {
				$cols[] = $col;
				$values[] = spip_abstract_quote($value);
				if ($col == $id) $id_objet = $value;
			}
		}
	}

	$table = isset($tables[$type]) ? $tables[$type] : $type;
	
	if (!spip_query("REPLACE $table (" . join(',', $cols) . ') VALUES (' . join(',', $values) . ')')) {
		echo "--><br><font color='red'><b>"._T('avis_erreur_mysql')."</b></font>\n<font color='black'><tt>".spip_sql_error()."</tt></font>\n<!--";
		$GLOBALS['erreur_restauration'] = true;
	}

	if ($type == 'article') {
		spip_query("DELETE FROM spip_auteurs_articles WHERE id_article=$id_objet");
		spip_query("DELETE FROM spip_documents_articles WHERE id_article=$id_objet");
	}
	else if ($type == 'rubrique') {
		spip_query("DELETE FROM spip_auteurs_rubriques WHERE id_rubrique=$id_objet");
		spip_query("DELETE FROM spip_documents_rubriques WHERE id_rubrique=$id_objet");
	}
	else if ($type == 'breve') {
		spip_query("DELETE FROM spip_documents_breves WHERE id_breve=$id_objet");
	}
	else if ($type == 'mot') {
		spip_query("DELETE FROM spip_mots_articles WHERE id_mot=$id_objet");
		spip_query("DELETE FROM spip_mots_breves WHERE id_mot=$id_objet");
		spip_query("DELETE FROM spip_mots_forum WHERE id_mot=$id_objet");
		spip_query("DELETE FROM spip_mots_rubriques WHERE id_mot=$id_objet");
		spip_query("DELETE FROM spip_mots_syndic WHERE id_mot=$id_objet");
	}
	else if ($type == 'auteur') {
		spip_query("DELETE FROM spip_auteurs_rubriques WHERE id_auteur=$id_objet");
	}
	else if ($type == 'message') {
		spip_query("DELETE FROM spip_auteurs_messages WHERE id_message=$id_objet");
	}
	if ($liens) {
		reset($liens);
		while (list($type_lien, $t) = each($liens)) {
			if ($type == 'auteur' OR $type == 'mot' OR $type == 'document')
				if ($type_lien == 'syndic' OR $type_lien == 'forum') $table_lien = 'spip_'.$type.'s_'.$type_lien;
				else $table_lien = 'spip_'.$type.'s_'.$type_lien.'s';
			else
				$table_lien = 'spip_'.$type_lien.'s_'.$type.'s';
			spip_abstract_insert($table_lien, "($id, id_$type_lien)", join(',', $t));
		}
	}

	return $import_ok = "    ";
}

?>
