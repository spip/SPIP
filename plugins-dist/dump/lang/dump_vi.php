<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://www.spip.net/trad-lang/
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// A
	'aucune_donnee' => 'vide', # NEW
	'avis_probleme_ecriture_fichier' => 'Problème d\'écriture du fichier @fichier@', # NEW

	// B
	'bouton_restaurer_base' => 'Phục hồi database',

	// C
	'confirmer_ecraser_base' => 'Oui, je veux écraser ma base avec cette sauvegarde', # NEW
	'confirmer_ecraser_tables_selection' => 'Oui, je veux écraser les tables sélectionnées avec cette sauvegarde', # NEW

	// D
	'details_sauvegarde' => 'Détails de la sauvegarde :', # NEW

	// E
	'erreur_aucune_donnee_restauree' => 'Aucune donnée restaurée', # NEW
	'erreur_connect_dump' => 'Un serveur nommé « @dump@ » existe déjà. Renommez-le.', # NEW
	'erreur_creation_base_sqlite' => 'Impossible de créer une base SQLite pour la sauvegarde', # NEW
	'erreur_nom_fichier' => 'Ce nom de fichier n\'est pas autorisé', # NEW
	'erreur_restaurer_verifiez' => 'Corrigez l\'erreur pour pouvoir restaurer.', # NEW
	'erreur_sauvegarde_deja_en_cours' => 'Vous avez déjà une sauvegarde en cours', # NEW
	'erreur_sqlite_indisponible' => 'Impossible de faire une sauvegarde SQLite sur votre hébergement', # NEW
	'erreur_table_absente' => 'Table @table@ absente', # NEW
	'erreur_table_donnees_manquantes' => 'Table @table@, données manquantes', # NEW
	'erreur_taille_sauvegarde' => 'La sauvegarde semble avoir échoué. Le fichier @fichier@ est vide ou absent.', # NEW

	// I
	'info_aucune_sauvegarde_trouvee' => 'Aucune sauvegarde trouvée', # NEW
	'info_restauration_finie' => 'C\'est fini !. La sauvegarde @archive@ a été restaurée dans votre site. Vous pouvez', # NEW
	'info_restauration_sauvegarde' => 'Phục hồi hồ sơ dự trữ @archive@', # MODIF
	'info_sauvegarde' => 'Dự trữ',
	'info_sauvegarde_reussi_02' => 'Database được lưu trữ trong @archive@. Bạn có thể ', # MODIF
	'info_sauvegarde_reussi_03' => 'trở lại việc quản trị',
	'info_sauvegarde_reussi_04' => 'website của bạn.',
	'info_selection_sauvegarde' => 'Vous avez choisi de restaurer la sauvegarde @fichier@. Cette opération est irréversible.', # NEW

	// L
	'label_nom_fichier_restaurer' => 'Ou indiquez le nom du fichier à restaurer', # NEW
	'label_nom_fichier_sauvegarde' => 'Nom du fichier pour la sauvegarde', # NEW
	'label_selectionnez_fichier' => 'Sélectionnez un fichier dans la liste', # NEW

	// N
	'nb_donnees' => '@nb@ enregistrements', # NEW

	// R
	'restauration_en_cours' => 'Restauration en cours', # NEW

	// S
	'sauvegarde_en_cours' => 'Sauvegarde en cours', # NEW
	'sauvegardes_existantes' => 'Sauvegardes existantes', # NEW
	'selectionnez_table_a_restaurer' => 'Sélectionnez les tables à restaurer', # NEW

	// T
	'texte_admin_tech_01' => 'Chọn lựa này cho phép bạn giữ lại nội dung của database vào trong một hồ sơ đựng trong ngăn @dossier@. Và cũng đừng quên cất giữ lại lại toàn bộ ngăn <i>IMG/</i>, chứa đựng các hình ảnh dùng trong các bài vở và đề mục.', # MODIF
	'texte_admin_tech_02' => '<b>Cảnh báo</b>: CHỈ CÓ THỂ phục hồi bản dự trữ này trong một website có cùng ấn bản SPIP.
 Lỗi lầm thường hay vấp phải là thực hiện việc dự trữ database trước khi nâng cấp SPIP...
 Để biết thêm, xin đọc [tài liệu SPIP->http://www.spip.net/fr_article1489.html].', # MODIF
	'texte_restaurer_base' => 'Phục hồi nội dung của kho dự trữ database',
	'texte_restaurer_sauvegarde' => 'Chọn lựa này cho phép bạn phục hồi một hồ sơ dự trữ trước đó của database. Để thực hiện điều này, hồ sơ chứa phần dự trữ phải được nên giữ ở thư mục @dossier@. Cẩn thận với đặc điểm này: <b>Bất cứ sửa đổi hay thất thoát nào xảy ra là không làm ngược lại được.</b>', # MODIF
	'texte_sauvegarde' => 'Dự trữ nội dung của database',
	'texte_sauvegarde_base' => 'Dự trữ database',
	'tout_restaurer' => 'Restaurer toutes les tables', # NEW
	'tout_sauvegarder' => 'Sauvegarder toutes les tables', # NEW

	// U
	'une_donnee' => '1 enregistrement' # NEW
);

?>
