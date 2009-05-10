<?php
if (!defined("_ECRIRE_INC_VERSION")) return;

// fonction pour le pipeline, n'a rien a effectuer
function mes_fichiers_autoriser(){}

// declarations d'autorisations
function autoriser_mes_fichiers_onglet_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('sauvegarder', 'mes_fichiers', $id, $qui, $opt);
}
function autoriser_mes_fichiers_sauvegarder_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('sauvegarder', $type, $id, $qui, $opt);
}
function autoriser_mes_fichiers_telecharger_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('sauvegarder', $type, $id, $qui, $opt);
}
?>
