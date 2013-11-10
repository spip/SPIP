<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://trad.spip.net/tradlang_module/dump?lang_cible=es
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// A
	'aucune_donnee' => 'vacío',
	'avis_probleme_ecriture_fichier' => 'Problema de escritura del archivo @fichier@',

	// B
	'bouton_restaurer_base' => 'Restaurar la base',

	// C
	'confirmer_ecraser_base' => 'Sí, quiero destruir mi base con la restauración de este respaldo. ',
	'confirmer_ecraser_tables_selection' => 'Sí, quiero destruir las tablas seleccionadas con la restauración de este respaldo',

	// D
	'details_sauvegarde' => 'Detalles del respaldo:',

	// E
	'erreur_aucune_donnee_restauree' => 'Ningún dato restaurado',
	'erreur_connect_dump' => 'Un servidor denominado « @dump@ » ya existe. Cámbiale el nombre.',
	'erreur_creation_base_sqlite' => 'Impossible crear una base SQLite para el respaldo',
	'erreur_nom_fichier' => 'Este nombre de archivo no está autorizado',
	'erreur_restaurer_verifiez' => 'Corrige el error para poder restaurar.',
	'erreur_sauvegarde_deja_en_cours' => 'Ya tienes un respaldo en curso.',
	'erreur_sqlite_indisponible' => 'Imposible hacer un respaldo SQLite en su espacio de hospedaje.',
	'erreur_table_absente' => 'Tabla @table@ ausente',
	'erreur_table_donnees_manquantes' => 'Tabla @table@, datos faltantes',
	'erreur_taille_sauvegarde' => 'El respaldo parece haber fracasado. El archivo @fichier@ está vacío o ausente',

	// I
	'info_aucune_sauvegarde_trouvee' => 'Ningún respaldo encontrado',
	'info_restauration_finie' => '¡Ya está! El respaldo @archive@ fue restaurado en tu sitio. Puedes ',
	'info_restauration_sauvegarde' => 'Restauración de la copia de respaldo @archive@',
	'info_sauvegarde' => 'Copia de respaldo',
	'info_sauvegarde_reussi_02' => 'El respaldo de la base está en @archive@. Puedes',
	'info_sauvegarde_reussi_03' => 'volver a la gestión',
	'info_sauvegarde_reussi_04' => 'del sitio.',
	'info_selection_sauvegarde' => 'Elegiste restaurar el respaldo  @fichier@. Esta operación es irreversible.',

	// L
	'label_nom_fichier_restaurer' => 'O indica el nombre del archivo por restaurar',
	'label_nom_fichier_sauvegarde' => 'Nombre del archivo para el respaldo',
	'label_selectionnez_fichier' => 'Selecciona un archivo en la lista',

	// N
	'nb_donnees' => '@nb@ registros',

	// R
	'restauration_en_cours' => 'Restauración en courso',

	// S
	'sauvegarde_en_cours' => 'Respaldo en curso',
	'sauvegardes_existantes' => 'Respaldos existentes',
	'selectionnez_table_a_restaurer' => 'Selecciona las tablas por restaurar',

	// T
	'texte_admin_tech_01' => 'Esta opción permite respaldar el contenido de la base en un archivo que se guardará en la carpeta @dossier@. No olvides recuperar además la totalidad de la carpeta @img@, que contiene las imágenes y los documentos utilizadas en los artículos y las secciones.',
	'texte_admin_tech_02' => '¡Atención! Esta copia de respaldo SÓLO PODRÁ ser restaurada en un sitio que utiliza LA MISMA versión de SPIP. Por ningún motivo se deberá «vaciar la base» imaginando volver a instalar el respaldo después de una actualización. Consultar la <a href="@spipnet@">documentación de SPIP</a>.',
	'texte_restaurer_base' => 'Restaurar el contenido de una copia de respaldo',
	'texte_restaurer_sauvegarde' => 'Esta opción te permite restaurar una copia de respaldo de la base efectuada anteriormente. A tal efecto, el archivo que contiene la copia de respaldo debe estar en la carpeta @dossier@.',
	'texte_sauvegarde' => 'Crear una copia de respaldo de la base',
	'texte_sauvegarde_base' => 'Crear una copia de respaldo de la base',
	'tout_restaurer' => 'Restaurar todas las tablas',
	'tout_sauvegarder' => 'Restaurar todas las tablas',

	// U
	'une_donnee' => '1 registro'
);

?>
