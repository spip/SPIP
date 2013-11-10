<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://trad.spip.net/tradlang_module/dump?lang_cible=ar
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// A
	'aucune_donnee' => 'فارغ',
	'avis_probleme_ecriture_fichier' => 'مشكلة في كتابة الملف @fichier@',

	// B
	'bouton_restaurer_base' => 'إسترجاع القاعدة',

	// C
	'confirmer_ecraser_base' => 'نعم، أريد استبدال القاعدة بهذه النسخة الاحتياطية',
	'confirmer_ecraser_tables_selection' => 'نعم أريد استبدال الجداول المحددة بهذه النسخة الاحتياطية',

	// D
	'details_sauvegarde' => 'نفاصيل النسخة الاحتياطية:',

	// E
	'erreur_aucune_donnee_restauree' => 'لم يتم استرجاع اي بيانات',
	'erreur_connect_dump' => 'يوجد خادم اسمه « @dump@ ». أعد تسميته.',
	'erreur_creation_base_sqlite' => 'لا يمكن انشاء قاعدة SQLite للنسخة الاحتياطية',
	'erreur_nom_fichier' => 'اسم ملف غير مسموح به',
	'erreur_restaurer_verifiez' => 'يجب تصحيح الخطأ للتمكن من الاسترجاع.',
	'erreur_sauvegarde_deja_en_cours' => 'يوجد حالياً نسخ احتياطي قيد التنفيذ',
	'erreur_sqlite_indisponible' => 'إستضافة موقعك لا تسمح بتنفيذ نسخ احتياطي SQLite ',
	'erreur_table_absente' => 'جدول @table@ غير موجود',
	'erreur_table_donnees_manquantes' => 'الجدول @table@، بيانات ناقصة',
	'erreur_taille_sauvegarde' => 'يبدو ان النسخ الاحتياطي فشل. فالملف @fichier@ فارغ او غير موجود. ',

	// I
	'info_aucune_sauvegarde_trouvee' => 'لم يتم العثور على اي نسخة احتياطية',
	'info_restauration_finie' => 'انتهى! تم استرجاع النسخة الاحتياطية @archive@ في الموقع؟ يمكنك',
	'info_restauration_sauvegarde' => 'إسترجاع النسخة الاحتياطية @archive@',
	'info_sauvegarde' => 'نسخة إحتياطية',
	'info_sauvegarde_reussi_02' => 'تم حفظ قاعدة البيانات في @archive@. يمكنك',
	'info_sauvegarde_reussi_03' => 'العودة الى إدارة',
	'info_sauvegarde_reussi_04' => 'موقعك.',
	'info_selection_sauvegarde' => 'اخترت استرجاع النسخة الاحتياطية @fichier@. لا يمكن التراجع عن هذه العملية.',

	// L
	'label_nom_fichier_restaurer' => 'أو تحديد اسم الملف المطلوب استرجاعه',
	'label_nom_fichier_sauvegarde' => 'اسم ملف النسخة الاحتياطية',
	'label_selectionnez_fichier' => 'تحديد ملف في القائمة',

	// N
	'nb_donnees' => '@nb@ تسجيل',

	// R
	'restauration_en_cours' => 'استرجاع قيد التنفيذ',

	// S
	'sauvegarde_en_cours' => 'نسخ احتياطي قيد التنفيذ',
	'sauvegardes_existantes' => 'النسخ الاحتياطية الموجودة',
	'selectionnez_table_a_restaurer' => 'تحديد جداول مطلوب استرجاعها',

	// T
	'texte_admin_tech_01' => 'يتيح لك هذا الخيار حفظ محتوى قاعدة البيانات في ملف يتم تخزينه في دليل @dossier@. لا تنسى أيضاً أن تسترد كامل دليل @img@، الذي يحتوي على الصور والمستندات المستخدمة في المقالات والأقسام.',
	'texte_admin_tech_02' => 'تحذير: لا يمكن إسترجاع هذه النسخة الاحتياطية إلا من خلال موقع تم تثبيته بالإصدار نفسه من SPIP . بالاخص لا يجب «تفريغ القاعدة» مع الامل بإعادة تثبيتها بعد التحديث... لمزيد من المعلومات راجع <a href="@spipnet@">دليل SPIP</a>.',
	'texte_restaurer_base' => 'إسترجاع محتوى النسخة الاحتياطية من القاعدة',
	'texte_restaurer_sauvegarde' => 'يتيح لك هذا الخيار إسترجاع نسخة إحتياطية
سابقة من القاعدة. لتنفيذ ذلك، يجب على ملف النسخة الاحتياطية
ان يكون قد حفظ في دليل @dossier@.
توخى الحذر مع هذه الوظيفة: &lt;b&gt;اي تعديل أو فقدان في البيانات نهائي ولا يمكن
التراجع عنه&lt;/b&gt;.',
	'texte_sauvegarde' => 'نسخ إحتياطي لمحتوى القاعدة',
	'texte_sauvegarde_base' => 'إنشاء نسخة إحتياطية من القاعدة',
	'tout_restaurer' => 'استرجاع كل الجداول',
	'tout_sauvegarder' => 'حفظ كل الجداول',

	// U
	'une_donnee' => 'تسجيل واحد'
);

?>
