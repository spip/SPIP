<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://trad.spip.net/tradlang_module/dump?lang_cible=ru
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// A
	'aucune_donnee' => 'пусто',
	'avis_probleme_ecriture_fichier' => 'Не удалось сохранить файл @fichier@',

	// B
	'bouton_restaurer_base' => 'Восстановить базу данных',

	// C
	'confirmer_ecraser_base' => 'Да, я хочу перезаписать всю текущую информацию данными из резервной копии',
	'confirmer_ecraser_tables_selection' => 'Да, я хочу заменить информацию в выбранных таблицах данными из резервной копии.',

	// D
	'details_sauvegarde' => 'Информация о резервной копии :',

	// E
	'erreur_aucune_donnee_restauree' => 'Не удалось восстановить информацию',
	'erreur_connect_dump' => 'Сервер « @dump@ » уже существует. Переименуйте его.',
	'erreur_creation_base_sqlite' => 'Не удалось создать SQLite базу для бэкапа',
	'erreur_nom_fichier' => 'Данное называние файла не разрешено',
	'erreur_restaurer_verifiez' => 'Исправьте ошибки для того, что бы продолжить восстановление.',
	'erreur_sauvegarde_deja_en_cours' => 'Резервная копия уже создается',
	'erreur_sqlite_indisponible' => 'Не получается сделать резервную копию SQLite на вашем хостинге',
	'erreur_table_absente' => 'Не хватает таблицы @table@ ',
	'erreur_table_donnees_manquantes' => 'В таблице @table@ нет информации',
	'erreur_taille_sauvegarde' => 'Проблемы с резервной копией. Похоже, что файл @fichier@ пустой или отсутствует.',

	// I
	'info_aucune_sauvegarde_trouvee' => 'Резервные копии не обнаружены',
	'info_restauration_finie' => 'Восстановление окончено! Резервная копия @archive@ загружена на сайт. Вы можете',
	'info_restauration_sauvegarde' => 'Восстановление резервной копии  @archive@',
	'info_sauvegarde' => 'Резервная копия',
	'info_sauvegarde_reussi_02' => 'База данных была сохранена в @archive@. Вы можете',
	'info_sauvegarde_reussi_03' => 'вернуться к настройкам',
	'info_sauvegarde_reussi_04' => 'сайта.',
	'info_selection_sauvegarde' => 'Вы решили восстановить информацию из резервной копии в файле @fichier@. Данная операция необратима.',

	// L
	'label_nom_fichier_restaurer' => '... или укажите название файла с резервной копией:',
	'label_nom_fichier_sauvegarde' => 'Название файла резервной копии',
	'label_selectionnez_fichier' => 'Выберите файл из списка:',

	// N
	'nb_donnees' => '@nb@ записей',

	// R
	'restauration_en_cours' => 'Идет восстановление',

	// S
	'sauvegarde_en_cours' => 'Идет сохранение данных',
	'sauvegardes_existantes' => 'Резервные копии',
	'selectionnez_table_a_restaurer' => 'Выбрать таблицы для восстановления',

	// T
	'texte_admin_tech_01' => 'Вы можете сделать резервную копию базы данных.Файл будет сохранен в каталоге @dossier@. Не забудьте скопировать папку @img@, в которой сохранены изображения и загруженные файлы. Все файлы необходимо скопировать на локальный компьютер или другой сервер.',
	'texte_admin_tech_02' => 'Внимание: вы можете восстановить резервную копию в той версии SPIP, в которой вы ее создали. Подробнее см. <a href="@spipnet@">документацию</a>.',
	'texte_restaurer_base' => 'Восстановление из резервной копии',
	'texte_restaurer_sauvegarde' => 'Вы можете восстановить сайт из резервной копии. Для этого поместите файл с копией в папку @dossier@.<br />


<b>Внимание:</b>  вся текущая информация будет заменена информацией с резервной копии. Эта операция необратима, если вы не уверены в том, что вы делаете - сделайте резервную копию перед началом восстановления. ',
	'texte_sauvegarde' => 'Сохранить базу данных',
	'texte_sauvegarde_base' => 'Сохранить базу данных',
	'tout_restaurer' => 'Восстановить все таблицы',
	'tout_sauvegarder' => 'Сохранить все таблицы',

	// U
	'une_donnee' => '1 запись'
);

?>
