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

// pour le support des vieux dump
// http://doc.spip.org/@inc_import_1_2_dist
function inc_import_1_2_dist($f, $request, $gz='fread') {
  global $import_ok;
	static $field_desc = array ();

	static $tables, $a_importer;
	if (!$tables) {
		$init = $request['init'];
		$a_importer = $init($request);
		$tables = array(
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
	}
	$import_ok = false;
	$b = false;
	// Lire le type d'objet
	if (!($type = xml_fetch_tag($f, $b, $gz))) return false;
	if ($type == '/SPIP') return !($import_ok = true);
	$id = "id_$type";
	$id_objet = 0;

	$table = isset($tables[$type]) ? $tables[$type] : $type;
	if (in_array($table, $a_importer) AND !isset($field_desc[$table])) {
		// recuperer la description de la table pour connaitre ses champs valides
		$desc = description_table($table);

		if (isset($desc['field']))
			$field_desc[$table] = $desc['field'];
		else
			$field_desc[$table] = NULL;
	}
	$fields = $field_desc[$table];

	$char = $GLOBALS['meta']['charset_insertion'];
	if ($char == $GLOBALS['meta']['charset_restauration']) $char = '';

	$values = array();
	// Lire les champs de l'objet
	for (;;) {
		$b = false;
		if (!($col = xml_fetch_tag($f, $b, $gz))) return false;
		if ($col == '/'.$type) break;
		$value = true;
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
					echo "--><br /><span style='color: red; font-weight: bold;'>"._T('avis_erreur_sauvegarde', array('type' => $type, 'id_objet' => $id_objet))."</span>\n<span style='color: black'>"._T('avis_colonne_inexistante', array('col' => $col));
					if ($col == 'images') echo _T('info_verifier_image');
					echo "</span>\n<!--";
					$GLOBALS['erreur_restauration'] = true;
				}
			}
			else if ($fields==NULL or isset($fields[$col])) {
				if ($char) 
					$value = importer_charset($value, $char);
				$values[$col] = $value;
				if ($col == $id) $id_objet = $value;
			}
		}
	}
   if ($values) {
	if (!sql_replace($table, $values, $desc)) {
		echo "--><br /><span style='color: red; font-weight: bold;'>"._T('avis_erreur_mysql')."</span>\n<span style='color: black'><tt>".sql_error()."</tt></span>\n<!--";
		$GLOBALS['erreur_restauration'] = true;
	}

	if ($type == 'article') {
		sql_delete("spip_auteurs_articles", "id_article=$id_objet");
		sql_delete("spip_documents_articles", "id_article=$id_objet");
	}
	else if ($type == 'rubrique') {
		sql_delete("spip_auteurs_rubriques", "id_rubrique=$id_objet");
		sql_delete("spip_documents_rubriques", "id_rubrique=$id_objet");
	}
	else if ($type == 'breve') {
		sql_delete("spip_documents_breves", "id_breve=$id_objet");
	}
	else if ($type == 'mot') {
		sql_delete("spip_mots_articles", "id_mot=$id_objet");
		sql_delete("spip_mots_breves", "id_mot=$id_objet");
		sql_delete("spip_mots_forum", "id_mot=$id_objet");
		sql_delete("spip_mots_rubriques", "id_mot=$id_objet");
		sql_delete("spip_mots_syndic", "id_mot=$id_objet");
	}
	else if ($type == 'auteur') {
		sql_delete("spip_auteurs_rubriques", "id_auteur=$id_objet");
	}
	else if ($type == 'message') {
		sql_delete("spip_auteurs_messages", "id_message=$id_objet");
	}
	if ($liens) {
		reset($liens);
		while (list($type_lien, $t) = each($liens)) {
			if ($type == 'auteur' OR $type == 'mot' OR $type == 'document')
				if ($type_lien == 'syndic' OR $type_lien == 'forum') $table_lien = 'spip_'.$type.'s_'.$type_lien;
				else $table_lien = 'spip_'.$type.'s_'.$type_lien.'s';
			else
				$table_lien = 'spip_'.$type_lien.'s_'.$type.'s';
			sql_insert($table_lien, "($id, id_$type_lien)", join(',', $t));
		}
	}
   }
   return $import_ok = "    ";
}

?>
