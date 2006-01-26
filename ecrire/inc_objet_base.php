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


class _Abstract {
	function abstract_error($str) {
		die ("<h4>".$str."<br>"._T('info_contact_developpeur')."</h4>");
	}
	function abstract_func() {
		$this->abstract_error(_T('avis_erreur_fonction_contexte'));
	}
	function _Abstract() { $this->abstract_func(); }
}


class ObjectCacheInstance extends _Abstract {
	// Variable values (array)
	var $fast_vars;
	var $slow_vars;

	// Variable status
	var $fast_vars_loaded = false;
	var $slow_vars_loaded = false;

	// Is modified ?
	var $dirty = false;

	function ObjectCacheInstance()  {
		$this->fast_vars = array();
		$this->slow_vars = array();
	}
}


class _ObjectFactory extends _Abstract {
	// Factory ID
	var $id_factory;

	// Object class name (for instantiation)
	var $object_class;

	// SQL table name/pattern
	var $sql_table;
	var $sql_id;

	// Plain array
	var $fast_vars_list, $nb_fast_vars;
	var $slow_vars_list, $nb_slow_vars;

	// Associative array
	var $fast_vars_array;
	var $slow_vars_array;

	// SQL field names
	var $fast_vars_sql;
	var $slow_vars_sql;

	// Object cache
	var $cache;

	// ---------------------------------------------------------

	//
	// Init factory helper variables and constants
	//
	function init_factory($id_factory) {
		$this->id_factory = $id_factory;

		// Store different representations of fast vars
		if (is_array($this->fast_vars_list)) {
			reset($this->fast_vars_list);
			while (list($key, $val) = each($this->fast_vars_list)) {
				$this->fast_vars_array[$val] = $val;
				$this->fast_vars_sql[] = $this->sql_table.'.'.$val;
			}
			$this->fast_vars_sql = join(', ', $this->fast_vars_sql);
			$this->nb_fast_vars = count($this->fast_vars_list);
		}
		else $this->nb_fast_vars = 0;

		// Store different representations of slow vars
		if (is_array($this->slow_vars_list)) {
			reset($this->slow_vars_list);
			while (list($key, $val) = each($this->slow_vars_list)) {
				$this->slow_vars_array[$val] = $val;
				$this->slow_vars_sql[] = $this->sql_table.'.'.$val;
			}
			$this->slow_vars_sql = join(', ', $this->slow_vars_sql);
			$this->nb_slow_vars = count($this->slow_vars_list);
		}
		else $this->nb_slow_vars = 0;

		// Default value for object id in database
		if (!$this->sql_id) {
			$this->sql_id = 'id_'.strtolower($this->object_class);
		}
	}


	//
	// Object management methods
	//

	function new_object($id) { $this->abstract(); }

	function create_object_cache_instance($id) {
		if (!($g = $this->cache[$id])) {
			$g = '_'.$this->object_class.'_'.$id;
			$GLOBALS[$g] = new ObjectCacheInstance;
			$this->cache[$id] = $g;
		}
		return $g;
	}

	// Create a new alias for an object
	// (aliases are the only way by which user code sees an object)
	function create_object_alias($id) {
		$class = $this->object_class;
		$alias = new $class;
		$alias->init_object($this->id_factory, $id);
		return $alias;
	}

	// Get field of an object (by ID)
	function get_object_field($id, $name) {
		$g = $this->cache[$id];
		if ($v = $this->fast_vars_array[$name]) {
			if (!$GLOBALS[$g]->fast_vars_loaded)
				$this->load_object_id($id, true);
			return $GLOBALS[$g]->fast_vars[$v];
		}
		else if ($v = $this->slow_vars_array[$name]) {
			if (!$GLOBALS[$g]->slow_vars_loaded)
				$this->load_object_id($id, false);
			return $GLOBALS[$g]->slow_vars[$v];
		}
		else {
			$this->abstract_error(_T('avis_champ_incorrect_type_objet', array('name' => $name, 'type' => $this->object_class)));
		}
	}

	// Set field of an object (by ID)
	function set_object_field($id, $name, $value) {
		$g = $this->cache[$id];
		if ($v = $this->fast_vars_array[$name]) {
			if (!$GLOBALS[$g]->fast_vars_loaded)
				$this->load_object_id($id, true);
			$GLOBALS[$g]->fast_vars[$v] = $value;
			$GLOBALS[$g]->dirty = true;
		}
		else if ($v = $this->slow_vars_array[$name]) {
			if (!$GLOBALS[$g]->slow_vars_loaded)
				$this->load_object_id($id, false);
			$GLOBALS[$g]->slow_vars[$v] = $value;
			$GLOBALS[$g]->dirty = true;
		}
		else {
			$this->abstract_error(_T('avis_champ_incorrect_type_objet', array('name' => $name)).$this->object_class);
		}
	}


	//
	// Load object by SQL query
	//
	function load_object_sql($query, $fast, $multiple = false) {
		$cols = $this->fast_vars_sql;
		if (!$fast && $this->slow_vars_sql) {
			if ($cols) $cols .= ', ';
			$cols .= $this->slow_vars_sql;
		}
		// Replace generic names by actual ones
		$query = ereg_replace('#cols', $cols, $query);
		$query = ereg_replace('#table', $this->sql_table, $query);
		$query = ereg_replace('#id', $this->sql_table.'.'.$this->sql_id, $query);
		$result = spip_query($query);
		// If multiple results expected, create a result array
		if ($multiple) $r = array();
		if ($result) while ($row = spip_fetch_array($result)) {
			$id = $row[$this->sql_id];
			$g = $this->create_object_cache_instance($id);
			// Read fast vars
			for ($i = 0; $i < $this->nb_fast_vars; $i++) {
				$var = $this->fast_vars_list[$i];
				$GLOBALS[$g]->fast_vars[$var] = $row[$var];
			}
			$GLOBALS[$g]->fast_vars_loaded = true;
			if (!$fast) {
				// Read slow vars
				for ($i = 0; $i < $this->nb_slow_vars; $i++) {
					$var = $this->slow_vars_list[$i];
					$GLOBALS[$g]->slow_vars[$var] = $row[$var];
				}
				$GLOBALS[$g]->slow_vars_loaded = true;
			}
			if ($multiple) $r[$id] = $id;
			else break;
		}
		if ($multiple) return $r;
	}

	//
	// Load object by ID
	//
	function load_object_id($id, $fast = true) {
		$query = "SELECT #cols FROM #table WHERE #id=$id";
		$this->load_object_sql($query, $fast);
	}

	//
	// Fetch object only if not in cache
	//
	function fetch_object_id($id, $fast = true) {
		if ($g = $this->cache[$id]) {
			if (!$GLOBALS[$g]->dirty) return;
		}
		else {
			$g = $this->create_object_cache_instance($id);
		}
		$this->load_object_id($id, $fast);
	}

	//
	// Create new object
	//
	function create_object() {
		static $new_id = 0;
		$id = 'new_'.(++$new_id);
		$g = $this->create_object_cache_instance($id);
		$GLOBALS[$g]->dirty = true;
		$GLOBALS[$g]->fast_vars_loaded = true;
		$GLOBALS[$g]->slow_vars_loaded = true;
		$this->new_object($id);
		return $id;
	}

	//
	// Main load function : fetch object by generic criterium
	//
	function fetch_object($critere, $fast = true) {
		if ($critere == 'new') {
			// create object
			$id = $this->create_object();
		}
		else if ($critere > 0) {
			// get object by id
			$id = intval($critere);
			$this->fetch_object_id($id, $fast);
		}
		else {
			// get object list by sql
			return $this->load_object_sql($critere, $fast, true);
		}
		return $this->create_object_alias($id);
	}

	//
	// Main save function : update object by ID
	//
	function update_object($id) {
		$g = $this->cache[$id];
		if ($GLOBALS[$g]->dirty) {
			// generate INSERT query (penser au addslashes)
		}
	}
}


class _Object extends _Abstract {
	// Factory ID
	var $id_factory;

	// Object ID
	var $id = 0;

	function init_object($id_factory, $id = 0) {
		$this->id_factory = $id_factory;
		if ($id) $this->id = $id;
	}

	function get($var) {
		return $GLOBALS[$this->id_factory]->get_object_field($this->id, $var);
	}

	function set($var, $value) {
		return $GLOBALS[$this->id_factory]->set_object_field($this->id, $var, $value);
	}

	function commit() {
		return $GLOBALS[$this->id_factory]->update_object($this->id);
	}
}


//
// Create a factory of a given type, and register it
//

function add_factory($type) {
	global $factories;
	$class = ucfirst($type).'Factory';
	$id_factory = $type.'_factory';
	$GLOBALS[$id_factory] = new $class;
	$GLOBALS[$id_factory]->init_factory($id_factory);
	return $id_factory;
}



?>
