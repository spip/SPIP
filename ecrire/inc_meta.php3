<?

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_META")) return;
define("_ECRIRE_INC_META", "1");


function lire_metas() {
	global $meta, $meta_maj;

	$meta = '';
	$meta_maj = '';
	$query = 'SELECT * FROM spip_meta';
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) {
		$nom = $row['nom'];
		$meta[$nom] = $row['valeur'];
		$meta_maj[$nom] = $row['maj'];
	}
}

function lire_meta($nom) {
	global $meta;
	return $meta[$nom];
}

function lire_meta_maj($nom) {
	global $meta_maj;
	return $meta_maj[$nom];
}

function ecrire_meta($nom, $valeur) {
	$valeur = addslashes($valeur);
	mysql_query("REPLACE spip_meta (nom, valeur) VALUES ('$nom', '$valeur')");
}

function effacer_meta($nom) {
	mysql_query("DELETE FROM spip_meta WHERE nom='$nom'");
}

//
// Mettre a jour le fichier cache des metas
//
// Ne pas oublier d'appeler cette fonction apres ecrire_meta() et effacer_meta() !
//
function ecrire_metas() {
	global $meta, $meta_maj;

	lire_metas();

	$f = fopen("inc_meta_cache.php3", "w");
	$s = '<?

if (defined("_ECRIRE_INC_META")) return;
define("_ECRIRE_INC_META", "1");

function lire_meta($nom) {
	global $meta;
	return $meta[$nom];
}

function lire_meta_maj($nom) {
	global $meta_maj;
	return $meta_maj[$nom];
}

';

	fputs($f, $s);
	if ($meta) {
		reset($meta);
		while (list($key, $val) = each($meta)) {
			$key = addslashes($key);
			$val = addslashes($val);
			$s = "\$GLOBALS['meta']['$key'] = '$val';\n";
			fputs($f, $s);
		}
		fputs($f, "\n");
	}
	if ($meta_maj) {
		reset($meta_maj);
		while (list($key, $val) = each($meta_maj)) {
			$key = addslashes($key);
			$s = "\$GLOBALS['meta_maj']['$key'] = '$val';\n";
			fputs($f, $s);
		}
		fputs($f, "\n");
	}
	fputs($f, '?>');
	fclose($f);
}


if (!$meta) lire_metas();

?>