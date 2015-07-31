<?php

include ("ecrire/inc_version.php3");

include_ecrire("inc_connect.php3");
include_ecrire("inc_meta.php3");
include_ecrire("inc_admin.php3");
include_local("inc-cache.php3");

if ($HTTP_POST_VARS) $vars = $HTTP_POST_VARS;
else $vars = $HTTP_GET_VARS;
$redirect_url = "./ecrire/" . $vars["redirect"];
$first = true;
reset($vars);
while (list ($key, $val) = each ($vars)) {
	if (!ereg("^(redirect|image.*|hash.*)$", $key)) {
		if ($first) $redirect_url .= "?";
		else $redirect_url .= "&";
		$first = false;
		$val = rawurlencode($val);
		$redirect_url .= "$key=$val";
	}
}


//
// Deplacer un fichier uploade
//

function deplacer_fichier_upload($source, $dest) {
	// Securite
	if (strstr($dest, "..")) {
		@unlink($source);
		exit;
	}

	$ok = @copy($source, $dest);
	if ($ok) @unlink($source);
	else $ok = @move_uploaded_file($source, $dest);
	@chmod($loc, 0666);
	return $ok;
}


//
// Ajouter une image (logo)
//

function ajout_image($source, $dest) {
	global $redirect_url, $hash_id_auteur, $hash, $num_img;

	// Securite
	if (!verifier_action_auteur("ajout_image $dest", $hash, $hash_id_auteur)) {
		@unlink($source);
		exit;
	}

	$loc = "IMG/$dest";
	if (!deplacer_fichier_upload($source, $loc)) return;

	$size = getimagesize($loc);
	$type = $size[2];

	if ($type=="1") $type="gif";
	else if ($type=="2") $type="jpg";
	else if ($type=="3") $type="png";
	else $type = "";

	if ($type) {
		rename($loc, "$loc.$type");
		$dest = "$dest.$type";
		$loc = "$loc.$type";
	}
	else {
		unlink($loc);
	}
}


//
// Ajouter un document
//

function ajout_doc($orig, $source, $dest, $mode, $id_document) {
	global $hash_id_auteur, $hash, $id_article;

	//
	// Securite
	//
	if (!verifier_action_auteur("ajout_doc", $hash, $hash_id_auteur)) {
		@unlink($source);
		exit;
	}

	if (ereg("\.([^.]+)$", $orig, $match)) {
		$ext = strtolower($match[1]);
	}
	$query = "SELECT * FROM spip_types_documents WHERE extension='$ext'";
	$result = mysql_query($query);
	if ($row = @mysql_fetch_array($result)) {
		$id_type = $row['id_type'];
		$type_inclus = $row['inclus'];
		$type_upload = $row['upload'];
	}

	// Ne pas accepter les types non autorises
	if ($type_upload != 'oui') return;
	if ($type_inclus == 'non' AND $mode == 'vignette') return;

	//
	// Preparation
	//

	if ($mode == 'vignette') {
		$id_document_lie = $id_document;
		$id_document = 0;
	}
	if (!$id_document) {
		$query = "INSERT spip_documents (id_type, titre) VALUES ($id_type, 'sans titre')";
		mysql_query($query);
		$id_document = mysql_insert_id();
		$nouveau = true;
		if ($id_article) {
			$query = "INSERT spip_documents_articles (id_document, id_article) VALUES ($id_document, $id_article)";
			mysql_query($query);
		}
	}
	if (!$dest) {
		if ($id_document_lie)
			$dest = "doc-$id_document_lie-prv";
		else
			$dest = "doc-$id_document";
	}
	$dest = ereg_replace("\.([^.]+)$", "", $dest) . ".$ext";

	if (creer_repertoire("IMG", $ext))
		$dest_path = "IMG/$ext/$dest";
	else
		$dest_path = "IMG/$dest";

	if (!deplacer_fichier_upload($source, $dest_path)) return;

	//
	// Recopier le fichier
	//

	$size_image = getimagesize($dest_path);
	$type_image = $size_image[2];
	if ($type_image) {
		$largeur = $size_image[0];
		$hauteur = $size_image[1];
	}
	$taille = filesize($dest_path);

	if ($nouveau) {
		if (!$mode) $mode = $type_image ? 'vignette' : 'document';
		$update = "mode='$mode', ";
		if ($largeur && $hauteur) $update .= "titre='image $largeur x $hauteur', ";
	}

	$query = "UPDATE spip_documents SET $update taille='$taille', largeur='$largeur', hauteur='$hauteur', fichier='$dest_path' ".
		"WHERE id_document=$id_document";
	mysql_query($query);
	if ($id_document_lie) {
		$query = "UPDATE spip_documents SET id_vignette=$id_document WHERE id_document=$id_document_lie";
		mysql_query($query);
	}
}


if ($ajout_doc == 'oui') {
	ajout_doc($image_name, $image, $fichier, $mode, $id_document);
}

if ($ajout_logo == "oui") {
	ajout_image($image, $logo);
}

if ($image_supp) {
	// Securite
	if (strstr($image_supp, "..")) {
		exit;
	}
	if (!verifier_action_auteur("supp_image $image_supp", $hash, $hash_id_auteur)) {
		exit;
	}
	@unlink("IMG/$image_supp");
}

if ($doc_supp) {
	// Securite
	if (!verifier_action_auteur("supp_doc $doc_supp", $hash, $hash_id_auteur)) {
		exit;
	}
	$query = "SELECT fichier FROM spip_documents WHERE id_document=$doc_supp";
	$result = mysql_query($query);
	if ($row = mysql_fetch_array($result)) {
		$fichier = $row['fichier'];
		mysql_query("DELETE FROM spip_documents WHERE id_document=$doc_supp");
		mysql_query("UPDATE spip_documents SET id_vignette=0 WHERE id_vignette=$doc_supp");
		unlink($fichier);
	}
}


@header ("Location: $redirect_url");

exit;
?>