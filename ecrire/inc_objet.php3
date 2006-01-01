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


//
if (!defined("_ECRIRE_INC_VERSION")) return;

include_ecrire('inc_objet_base');


//
// --- Creation de classe concrete ----
//
// Il faut creer deux classes :
//   - une pour la factory
//   - une pour le descripteur d'objet
//
// Le descripteur d'objet doit declarer le constructeur, qui peut
// etre laisse vide.
//
// La factory doit declarer 1) le constructeur, qui regle normalement
// le nom des champs, de la table sql, et du descripteur d'objet ;
// ainsi que 2) la methode new_object, qui prend le $id d'un objet en
// argument et initialise les champs du nouvel objet selon la
// semantique choisie pour icelui.
//
// Ensuite il faut instancier et enregistrer la factory et fournir
// une fonction globale d'appel d'objet. Le plus simple est de
// recopier la fonction fournie et de changer les noms.
//

// --------------------------------------------------------------------

//
// Factory d'articles
//

class ArticleFactory extends _ObjectFactory {
	// Initialiser les variables de classe
	function ArticleFactory() {
		$this->fast_vars_list = array('id_article', 'id_rubrique', 'id_secteur', 'titre', 'surtitre',
			'soustitre', 'descriptif', 'date', 'date_redac', 'visites', 'referers', 'statut');
		$this->slow_vars_list = array('chapo', 'texte', 'ps');
		$this->sql_table = 'spip_articles';
		$this->object_class = 'Article';
	}

	// Initialiser les variables d'objet
	function new_object($id) {
		$this->set_object_field($id, 'titre', 'Nouvel article');
		$this->set_object_field($id, 'statut', 'poubelle');
	}
}

class Article extends _Object {
	// Ne rien faire
	function Article() {
	}
}

add_factory('article'); // Retourne 'article_factory'

function fetch_article($critere, $fast = true) {
	return $GLOBALS['article_factory']->fetch_object($critere, $fast);
}


// --------------------------------------------------------------------

//
// Factory de breves
//

class BreveFactory extends _ObjectFactory {
	// Initialiser les variables de classe
	function BreveFactory() {
		$this->fast_vars_list = array('id_breve', 'id_rubrique', 'titre', 
			'lien_titre', 'lien_url', 'date_heure', 'statut');
		$this->slow_vars_list = array('texte');
		$this->sql_table = 'spip_breves';
		$this->object_class = 'Breve';
	}

	// Initialiser les variables d'objet
	function new_object($id) {
		$this->set_object_field($id, 'titre', 'Nouvelle breve');
		$this->set_object_field($id, 'statut', 'refuse');
	}
}

class Breve extends _Object {
	// Ne rien faire
	function Breve() {
	}
}

add_factory('breve'); // Retourne 'article_factory'

function fetch_breve($critere, $fast = true) {
	return $GLOBALS['breve_factory']->fetch_object($critere, $fast);
}


// --------------------------------------------------------------------

//
// Factory de documents
//

class DocumentFactory extends _ObjectFactory {
	// Initialiser les variables de classe
	function DocumentFactory() {
		$this->fast_vars_list = array('id_document', 'id_vignette', 'id_type', 
			'titre', 'descriptif', 'fichier', 'largeur', 'hauteur', 'taille', 'mode', 'date');
		$this->slow_vars_list = '';
		$this->sql_table = 'spip_documents';
		$this->object_class = 'Document';
	}

	// Initialiser les variables d'objet
	function new_object($id) {
		$this->set_object_field($id, 'titre', 'nouveau document');
	}
}

class Document extends _Object {
	// Ne rien faire
	function Document() {
	}
}

add_factory('document'); // Retourne 'article_factory'

function fetch_document($critere, $fast = true) {
	return $GLOBALS['document_factory']->fetch_object($critere, $fast);
}


?>
