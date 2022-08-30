<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de https://trad.spip.net/tradlang_module/ecrire_?lang_cible=zh
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// A
	'aide_non_disponible' => '这部分在线帮助尚无中文版本.',
	'avis_acces_interdit' => '限制访问.',
	'avis_article_modifie' => '警告, @nom_auteur_modif@在@date_diff@分钟前修改过此文',
	'avis_aucun_resultat' => '没有结果.',
	'avis_chemin_invalide_1' => '您所选路径',
	'avis_chemin_invalide_2' => '无效. 请返回前页校验提供的信息.',
	'avis_connexion_echec_1' => '连接MYSQL服务器失败.', # MODIF
	'avis_connexion_echec_2' => '请返回前页校验提供的信息.',
	'avis_connexion_echec_3' => '<b>N.B.</b> 在许多服务器上运行时, 使用前您必须<b>请求</b>激活访问MYSQL数据库的权限.如果您无法连接, 请首先检验您是否有效激活该权限.', # MODIF
	'avis_connexion_ldap_echec_1' => '连接LDAP服务器失败.',
	'avis_connexion_ldap_echec_2' => '返回前页校验您所提供的信息.',
	'avis_connexion_ldap_echec_3' => '请勿使用LDAP支持导入用户.',
	'avis_deplacement_rubrique' => '注意! 该专栏包含 @contient_breves@ 简要@scb@: 如果您要移动它,请选择该确认框.',
	'avis_erreur_connexion_mysql' => 'SQL连接失败',
	'avis_espace_interdit' => '<b>禁止区</b><p>SPIP已安装.', # MODIF
	'avis_lecture_noms_bases_1' => '安装程序无法读取已安装的数据库的名称.',
	'avis_lecture_noms_bases_2' => '要么是数据库不可用,要么数据库的允许特性因安全原因被禁止
(这是多主机的的一个例子).',
	'avis_lecture_noms_bases_3' => '第二种情况为使用您的用户名登录后的数据库是可用的:',
	'avis_non_acces_page' => '您无权查看此页.',
	'avis_operation_echec' => '操作失败.',
	'avis_suppression_base' => '注意, 数据删除不可挽回',

	// B
	'bouton_acces_ldap' => '添加LDAP访问 >>', # MODIF
	'bouton_ajouter' => '添加',
	'bouton_demande_publication' => '请求发表文章',
	'bouton_effacer_tout' => '删除所有',
	'bouton_envoyer_message' => '最后消息:发送',
	'bouton_modifier' => '修改',
	'bouton_radio_afficher' => '显示',
	'bouton_radio_apparaitre_liste_redacteurs_connectes' => '显示在已连接的编辑者列表中',
	'bouton_radio_envoi_annonces_adresse' => '发送声明给下列地址:',
	'bouton_radio_envoi_liste_nouveautes' => '发送最近新闻列表',
	'bouton_radio_non_apparaitre_liste_redacteurs_connectes' => '不要出现在连接编辑者列表中',
	'bouton_radio_non_envoi_annonces_editoriales' => '不发送任何编辑的声明',
	'bouton_redirection' => '重定向',
	'bouton_relancer_installation' => '重新安装',
	'bouton_suivant' => '下一步',
	'bouton_tenter_recuperation' => '试图修复',
	'bouton_test_proxy' => '测试代理',
	'bouton_vider_cache' => '清空缓存',

	// C
	'cache_modifiable_webmestre' => '这些参数可以被管理员修改。', # MODIF
	'calendrier_synchro' => '如果您使用的日历软件与<b>iCal</b>兼容, 您可以同步站点信息.',

	// D
	'date_mot_heures' => '时',

	// E
	'email' => '电子邮件',
	'email_2' => '电子邮件:',
	'entree_adresse_annuaire' => '目录地址',
	'entree_adresse_email' => '您的邮件地址',
	'entree_base_donnee_1' => '数据库地址',
	'entree_base_donnee_2' => '(该地址经常对应您的站点地址,有时对应 «localhost», 有时可以留空.)',
	'entree_biographie' => '自我简介.',
	'entree_chemin_acces' => '<b>输入</b> 路径:', # MODIF
	'entree_cle_pgp' => '您的PGP钥匙',
	'entree_contenu_rubrique' => '(专栏内容简介.)',
	'entree_identifiants_connexion' => '您的连接标识符...',
	'entree_informations_connexion_ldap' => '请在表单中填入LDAP连接信息. 所有信息应该由系统或网络管理员提供.',
	'entree_infos_perso' => '您是谁?',
	'entree_interieur_rubrique' => '在专栏内部:',
	'entree_liens_sites' => '<b>超链接</b> (访问参考站点...)', # MODIF
	'entree_login' => '登录用户名',
	'entree_login_connexion_1' => '连接登录',
	'entree_login_connexion_2' => '(有时对应您的FTP登录用户名;有时留空)',
	'entree_mot_passe' => '密码',
	'entree_mot_passe_1' => '连接密码',
	'entree_mot_passe_2' => '(有时对应您的FTP登录用户名;有时留空)',
	'entree_nom_fichier' => '请输入文件名 @texte_compresse@:',
	'entree_nom_pseudo' => '您的名字或昵称',
	'entree_nom_pseudo_1' => '(您的名字或昵称)',
	'entree_nom_site' => '站点名',
	'entree_nouveau_passe' => '新密码',
	'entree_passe_ldap' => '密码',
	'entree_port_annuaire' => '目录端口号',
	'entree_signature' => '签名',
	'entree_titre_obligatoire' => '<b>标题</b> [必需的]<br />', # MODIF
	'entree_url' => '站点连接',

	// I
	'ical_info1' => '该页面提供了几种与本站点保持联系的方法.',
	'ical_info2' => '要得到更多的信息, 请访问 <a href="@spipnet@">SPIP 文档</a>.', # MODIF
	'ical_info_calendrier' => '在您的配置中有两个日历. 第一个是站点地图,它显示所有已发布的文章. 第二个包含了可编辑的声明,作为您最后的私有消息: 由于您可以随时通过更新密码来更改您的个人钥匙,它总是为您保留的.',
	'ical_methode_http' => '下载',
	'ical_methode_webcal' => '同步 (webcal://)', # MODIF
	'ical_texte_prive' => '该日历严格限于个人使用, 提醒您在该站点上的个人活动 (任务,个人约会,提交的文章和简要...).',
	'ical_texte_public' => '该日历允许您追踪站点的公共活动 (发布的文章和简要).',
	'ical_texte_rss' => '您可以用任何XML/RSS(Rich Site Summary)阅读器联合站点的最近新闻以便阅读. XML/RSS同样是允许从其它SPIP站点读取/交换最近新闻的格式.',
	'ical_titre_js' => 'Javascript',
	'ical_titre_mailing' => '邮件列表',
	'ical_titre_rss' => '«引用»文件', # MODIF
	'icone_activer_cookie' => '激活相应cookie',
	'icone_afficher_auteurs' => '显示作者',
	'icone_afficher_visiteurs' => '显示访问者',
	'icone_arret_discussion' => '停止参与该讨论',
	'icone_calendrier' => '日历',
	'icone_creer_auteur' => '新建一个作者并与该文章关联',
	'icone_creer_mot_cle' => '新建一个关键词并与该文章关联',
	'icone_creer_rubrique_2' => '新建专栏',
	'icone_modifier_article' => '修改文章',
	'icone_modifier_rubrique' => '修改此栏',
	'icone_retour' => '返回',
	'icone_retour_article' => '返回文章',
	'icone_supprimer_cookie' => '删除cookie',
	'icone_supprimer_rubrique' => '删除此栏',
	'icone_supprimer_signature' => '删除签名',
	'icone_valider_signature' => '使签名有效',
	'image_administrer_rubrique' => '您可以管理该栏',
	'impossible_modifier_login_auteur' => 'Impossible de modifier le login.', # MODIF
	'impossible_modifier_pass_auteur' => 'Impossible de modifier le mot de passe.', # MODIF
	'info_1_article' => '1篇文章',
	'info_activer_cookie' => '您可以激活<b>相应的cookie</b>,以便让您轻松转换公共站点为私私人站点.',
	'info_administrateur' => '管理员',
	'info_administrateur_1' => '管理员',
	'info_administrateur_2' => '站点 (<i>谨慎使用</i>)',
	'info_administrateur_site_01' => '如果您是站点管理员,请',
	'info_administrateur_site_02' => '点击链接',
	'info_administrateurs' => '管理员',
	'info_administrer_rubrique' => '您可以管理该栏',
	'info_adresse' => '给地址:',
	'info_adresse_url' => '您的公众站点URL地址',
	'info_aide_en_ligne' => 'SPIP在线帮助',
	'info_ajout_image' => '当您添加图像作为文章的附加文档,  SPIP 能根据插入的图片自动创建缩略图.
这将允许, 例如, 自动创建
  画廊或相册.',
	'info_ajouter_rubrique' => '加入其它专栏进行管理:',
	'info_annonce_nouveautes' => '最近的新闻声明',
	'info_article' => '文章',
	'info_article_2' => '文章',
	'info_article_a_paraitre' => '过期文章发表',
	'info_articles_02' => '文章',
	'info_articles_2' => '文章',
	'info_articles_auteur' => '该作者的文章',
	'info_articles_trouves' => '找到的文章',
	'info_attente_validation' => '您的文章正在等候确认中',
	'info_aujourdhui' => '今天:',
	'info_auteurs' => '作者',
	'info_auteurs_par_tri' => '作者 @partri@',
	'info_auteurs_trouves' => '找到的作者',
	'info_authentification_externe' => '外部验证',
	'info_avertissement' => '消息',
	'info_base_installee' => '您的数据库已经安装.',
	'info_chapeau' => '前言',
	'info_chapeau_2' => '前言:',
	'info_chemin_acces_1' => '选项: <b>目录的访问路径</b>', # MODIF
	'info_chemin_acces_2' => '从现在开始您必须配置目录的访问路径. 这是存在目录中的用户说明文件精要.',
	'info_chemin_acces_annuaire' => '选项: <b>目录的访问路径</b>', # MODIF
	'info_choix_base' => '第三步:',
	'info_classement_1' => '<sup>st</sup> 出了 @liste@',
	'info_classement_2' => '<sup>th</sup> 出了 @liste@',
	'info_code_acces' => '不要忘记你的访问码!',
	'info_config_suivi' => '如果地址对应邮件列表, 你可以简要说明以下地址(从这儿能注册参与). 地址可以是URL (例如通过页面注册), 或通过电子邮件给一个特殊的标题(例如: <tt>@adresse_suivi@?subject=subscribe</tt>):',
	'info_config_suivi_explication' => '你可以订阅站点的邮件列表. 随后你将接到自动邮件,关于文章和新闻的声明将提交发表.',
	'info_confirmer_passe' => '确认新密码:',
	'info_connexion_base' => '第二步: <b>试图连接到数据库</b>', # MODIF
	'info_connexion_ldap_ok' => '<b>你的 LDAP 连接成功.</b><p> 你可进行下一步操作.', # MODIF
	'info_connexion_mysql' => '第一步: <b>你的 SQL 连接</b>', # MODIF
	'info_connexion_ok' => '连接成功.',
	'info_contact' => '联系',
	'info_contenu_articles' => '文章内容',
	'info_creation_paragraphe' => '(新建段落, 只需空一行.)', # MODIF
	'info_creation_rubrique' => '在能够发表文章之前,<br /> 您必须创建至少一个专栏.<br />', # MODIF
	'info_creation_tables' => '第四步: <b>创建数据库表</b>', # MODIF
	'info_creer_base' => '<b>新建</b> 数据库:', # MODIF
	'info_dans_rubrique' => '所属专栏:',
	'info_date_publication_anterieure' => '更早出版的日期:', # MODIF
	'info_date_referencement' => '参考站点日期:',
	'info_derniere_etape' => '最后一步: <b>完成了!', # MODIF
	'info_descriptif' => '描述:',
	'info_discussion_cours' => '讨论进展中',
	'info_ecrire_article' => '在能够发表文章之前,您必须建立至少一个专栏.',
	'info_email_envoi' => '发送者电子邮件地址 (可选)',
	'info_email_envoi_txt' => '输入发送者电子邮件地址,发送电子邮件将用这个地址, 接收者的地址将做为发送者的地址 :',
	'info_email_webmestre' => 'Web站点管理员的电子邮件地址 (可选)', # MODIF
	'info_envoi_email_automatique' => '自动邮寄',
	'info_envoyer_maintenant' => '现在发送',
	'info_etape_suivante' => '到下一步',
	'info_etape_suivante_1' => '你可移动到下一步.',
	'info_etape_suivante_2' => '你可移动到下一步.',
	'info_exportation_base' => '导出数据库到 @archive@',
	'info_facilite_suivi_activite' => '为减轻站点编辑的跟踪;
  活动, SPIP 通过电子邮件发送给编辑的邮件列表作为实例,
  公共请求和文章
  确认的声明.', # MODIF
	'info_fichiers_authent' => '认证文件 ".htpasswd"',
	'info_forums_abo_invites' => '您的网站包含要求注册的公共论坛；所以公共网站的访客将被要求注册。',
	'info_gauche_admin_tech' => '<b>只有管理者才有权访问这页.</b><p> 它提供多种多种
维护任务. 它们有一些需更高的认证
(通过FTP访问站点).', # MODIF
	'info_gauche_admin_vider' => '<b>只有管理者才有权访问这页.</b><p> 它提供多种维护任务
. 它们有一些需更高的认证
(通过FTP访问站点).', # MODIF
	'info_gauche_auteurs' => '你将找到站点所有的作者.
 每一个的状态用路标的颜色标识(作者 = 绿色; 管理员 = 黄色).', # MODIF
	'info_gauche_auteurs_exterieurs' => '外部作者用蓝色图标标识, 不能访问站点; 通过垃圾箱删除作者.', # MODIF
	'info_gauche_messagerie' => '消息允许你在作者中交换消息, 为保护备忘录(给个人用的) 或在主页私有区上显示声明(如果你是管理者).',
	'info_gauche_statistiques_referers' => '页面显示 <i>引用</i>列表, 例如. 包含你站点的链接, 只有今天: 列表每24小时都要更新.', # MODIF
	'info_gauche_visiteurs_enregistres' => '在这儿你将找到在站点公共区
 注册的访问者(订阅论坛).',
	'info_generation_miniatures_images' => '产生像册',
	'info_hebergeur_desactiver_envoi_email' => '一些主机禁止自动邮件发送
 . 这种情况下SPIP的
  以下特性不能用.',
	'info_hier' => '昨天:',
	'info_identification_publique' => '你的公开标识...',
	'info_image_process' => '点击相关图片选取最佳的标志制作方法.',
	'info_image_process2' => '<b>注意</b> <i>如果没有任何图片显示，那么储存您的网站的服务器不支持该工具。如果您希望使用这些功能，请联系您的服务器的技术支持，请他们安装《GD》或者《Imagick》扩展。</i>', # MODIF
	'info_informations_personnelles' => '第五步: <b>个人信息</b>', # MODIF
	'info_inscription_automatique' => '新编辑自动注册系统',
	'info_jeu_caractere' => '站点的字符集',
	'info_jours' => '天',
	'info_laisser_champs_vides' => '文本框留空)',
	'info_langues' => '站点语言',
	'info_ldap_ok' => 'LDAP 验证已安装.',
	'info_lien_hypertexte' => '超链接:',
	'info_liste_redacteurs_connectes' => '列出连接的编辑者',
	'info_login_existant' => '这个登录名已经存在.',
	'info_login_trop_court' => '登录名太短.',
	'info_maximum' => '最大:',
	'info_meme_rubrique' => '在同一栏目',
	'info_message_en_redaction' => '你的进展中的消息',
	'info_message_technique' => '技术消息:',
	'info_messagerie_interne' => '内部消息',
	'info_mise_a_niveau_base' => 'SQL 数据库升级',
	'info_mise_a_niveau_base_2' => '{{警告!}} 你已经安装的SPIP的
  版本 {老于} 以前安装的
  : 你的数据库有丢失的危险
  并且再也不能正常工作.<br />{{重新安装
  SPIP 文件.}}', # MODIF
	'info_modifier_rubrique' => '修改专栏:',
	'info_modifier_titre' => '修改: @titre@',
	'info_mon_site_spip' => '我的 SPIP 站点',
	'info_moyenne' => '平均:',
	'info_multi_cet_article' => '文章的语言:',
	'info_multi_langues_choisies' => '请在站点中选择以下语言使它们对编辑者可用.
 你的站点已经用了如下语言(在顶端列表),它们不能设为未激活.',
	'info_multi_secteurs' => '... 只为站点根目录下的专栏?',
	'info_nom' => '名字',
	'info_nom_destinataire' => '接收者名字',
	'info_nom_site' => '你的站点名',
	'info_nombre_articles' => '@nb_articles@ 文章,',
	'info_nombre_rubriques' => '专栏@nb_rubriques@,',
	'info_nombre_sites' => '@nb_sites@ 站点,',
	'info_non_deplacer' => '不要移动...',
	'info_non_envoi_annonce_dernieres_nouveautes' => 'SPIP 能定期主动发送站点的最新新闻声明.
  (最新发表的文章和新闻).',
	'info_non_envoi_liste_nouveautes' => '不能发送新新闻列表',
	'info_non_modifiable' => '不能修改',
	'info_non_suppression_mot_cle' => '我不想删除关键词.',
	'info_notes' => '脚注',
	'info_nouvel_article' => '新文章',
	'info_nouvelle_traduction' => '新译文:',
	'info_numero_article' => '文章号:',
	'info_obligatoire_02' => '[必须的]', # MODIF
	'info_option_accepter_visiteurs' => '允许公共网站访问者注册。',
	'info_option_ne_pas_accepter_visiteurs' => '拒绝公共网站访问者注册。',
	'info_options_avancees' => '高级选项',
	'info_ou' => '或...',
	'info_page_interdite' => '禁止页',
	'info_par_nombre_article' => '(按文章数)', # MODIF
	'info_passe_trop_court' => '密码过短.',
	'info_passes_identiques' => '两个密码不一致.',
	'info_plus_cinq_car' => '多于5 字符',
	'info_plus_cinq_car_2' => '(多于 5 字符)',
	'info_plus_trois_car' => '(多于 3 字符)',
	'info_popularite' => '流行: @popularite@; 访问: @visites@',
	'info_post_scriptum' => '后记',
	'info_post_scriptum_2' => '后记:',
	'info_pour' => '为',
	'info_preview_texte' => '可以预览整个网站，就像所有的文章和短消息（至少有 « 建议发表 »资格）都被发表了一样。向管理员，编辑开放这一功能，还是不向任何人开放？', # MODIF
	'info_procedez_par_etape' => '请一步步进行下去',
	'info_procedure_maj_version' => '升级过程应该适应
 SPIP的新版本的数据库运行.',
	'info_ps' => 'P.S.', # MODIF
	'info_publies' => '你的文章在线出版',
	'info_question_accepter_visiteurs' => '如果您的网站骨架设定访问者可以从公共网站注册，而不用到私人空间，请激活如下功能:',
	'info_question_inscription_nouveaux_redacteurs' => '你允许新编辑从公共站点注册吗?
  如果你愿意, 访问将通过自动表单注册
  , 将能访问私有区维护文章
  . <blockquote><i>光注册过程中,
  用户使用自动电子邮件提供的访问码访问私有站点.
  . 一些主机使自动发送不可用,
  这样,
  自动注册将
  不生效.', # MODIF
	'info_racine_site' => '站点根',
	'info_recharger_page' => '请重新载入该页.',
	'info_recherche_auteur_zero' => '<b> "@cherche_auteur@"没有结果.', # MODIF
	'info_recommencer' => '请再试.',
	'info_redacteur_1' => 'Rédacteur',
	'info_redacteur_2' => '有权访问私有区 (<i>推荐</i>)',
	'info_redacteurs' => '编辑者',
	'info_redaction_en_cours' => '在编辑中',
	'info_redirection' => '重定向',
	'info_refuses' => '你的文章被拒',
	'info_reglage_ldap' => '选项: <b>调整 LDAP 导入</b>', # MODIF
	'info_renvoi_article' => '<b>重定向.</b> 引用该页的文章:', # MODIF
	'info_reserve_admin' => '只有管理能改这个地址.',
	'info_restreindre_rubrique' => '限制专栏管理:',
	'info_resultat_recherche' => '搜索结果:',
	'info_rubriques' => '专栏',
	'info_rubriques_02' => '专栏',
	'info_rubriques_trouvees' => '找到的专栏',
	'info_sans_titre' => '无标题',
	'info_selection_chemin_acces' => '从目录的访问路径<b>选择</b> :',
	'info_signatures' => '签名',
	'info_site' => '站点',
	'info_site_2' => '站点:',
	'info_site_min' => '站点',
	'info_site_reference_2' => '引用的站点',
	'info_site_web' => '站点:', # MODIF
	'info_sites' => '站点',
	'info_sites_lies_mot' => '与关键词关联的参考站点',
	'info_sites_proxy' => '使用代理',
	'info_sites_trouves' => '站点找到了',
	'info_sous_titre' => '子标题:',
	'info_statut_administrateur' => '管理者',
	'info_statut_auteur' => '作者状态:', # MODIF
	'info_statut_redacteur' => '编辑者',
	'info_statut_utilisateurs_1' => '导入用户的缺省状态',
	'info_statut_utilisateurs_2' => 'Choose the status that is attributed to the persons present in the LDAP directory when they connect for the first time. Later, you can modify this value for each author on a case by case basis.',
	'info_suivi_activite' => '继续使编辑可用',
	'info_surtitre' => '顶标题:',
	'info_taille_maximale_vignette' => '系统产生的小插图的最大尺寸:',
	'info_terminer_installation' => '现在你可以完成标准安装过程.',
	'info_texte' => '正文',
	'info_texte_explicatif' => '展开正文',
	'info_texte_long' => '(正文太长: 将分几部分显示,确认后能合并在一起.)',
	'info_texte_message' => '你的消息正文:', # MODIF
	'info_texte_message_02' => '消息正文',
	'info_titre' => '标题:',
	'info_total' => '所有:',
	'info_tous_articles_en_redaction' => '进展中的所有文章',
	'info_tous_articles_presents' => '该专栏中所有发表的文章',
	'info_tous_les' => '每一个:',
	'info_tout_site' => '整个站点',
	'info_tout_site2' => '该文章尚未译成中文.',
	'info_tout_site3' => '文章已经译为本语言,但由参考文章带来一些变动.译文应更新.',
	'info_tout_site4' => '该文章已经译为中文并更新.',
	'info_tout_site5' => '源文章.',
	'info_tout_site6' => '<b>注意 :</b> 这里只显示源文件.
各翻译版本已与源文件相关联,
并以不同的颜色标识当前状态 :',
	'info_travail_colaboratif' => '合力工作文章',
	'info_un_article' => '一个文章,',
	'info_un_site' => '一个站点,',
	'info_une_rubrique' => '一个专栏,',
	'info_une_rubrique_02' => '1个专栏',
	'info_url' => 'URL:', # MODIF
	'info_urlref' => '超链接:',
	'info_utilisation_spip' => 'SPIP 准备使用...',
	'info_visites_par_mois' => '每月显示:',
	'info_visiteur_1' => '访问者',
	'info_visiteur_2' => '公共站点',
	'info_visiteurs' => '访问者',
	'info_visiteurs_02' => '公众站点访问者',
	'install_select_langue' => '选择语言并单击 "下一步" 开始安装过程.',
	'intem_redacteur' => '编辑',
	'item_accepter_inscriptions' => '允许注册',
	'item_activer_messages_avertissement' => '激活警告消息',
	'item_administrateur_2' => '管理者',
	'item_afficher_calendrier' => '在日历中显示',
	'item_choix_administrateurs' => '管理者',
	'item_choix_generation_miniature' => '自动产生像片册.',
	'item_choix_non_generation_miniature' => '不产生像片册.',
	'item_choix_redacteurs' => '编辑者',
	'item_choix_visiteurs' => '公共站点的访问者',
	'item_creer_fichiers_authent' => '创建 .htpasswd 文件',
	'item_login' => '登录',
	'item_mots_cles_association_articles' => '文章',
	'item_mots_cles_association_rubriques' => '相关专栏',
	'item_mots_cles_association_sites' => '参与或联合的站点.',
	'item_non' => 'No',
	'item_non_accepter_inscriptions' => '不允许注册',
	'item_non_activer_messages_avertissement' => '没有警告信息',
	'item_non_afficher_calendrier' => '在日历中不显示',
	'item_non_creer_fichiers_authent' => '不创建这些文件',
	'item_non_publier_articles' => '不发表出版日期前的文章.',
	'item_nouvel_auteur' => '新作者',
	'item_nouvelle_rubrique' => '新专栏',
	'item_oui' => '是',
	'item_publier_articles' => '忽略出版日期出版文章.',
	'item_reponse_article' => '回复文章',
	'item_visiteur' => '访问者',

	// J
	'jour_non_connu_nc' => '不知道',

	// L
	'lien_ajouter_auteur' => '加作者',
	'lien_email' => '电子邮件',
	'lien_nom_site' => '站点名:',
	'lien_retirer_auteur' => '移去作者',
	'lien_site' => '站点',
	'lien_tout_deplier' => '展开所有',
	'lien_tout_replier' => '伸缩所有',
	'lien_trier_nom' => '按名字排序',
	'lien_trier_nombre_articles' => '按文章号排序',
	'lien_trier_statut' => '按标题排序',
	'lien_voir_en_ligne' => '在线预览:',
	'logo_article' => '文章图标', # MODIF
	'logo_auteur' => '作者图标', # MODIF
	'logo_rubrique' => '专栏图标', # MODIF
	'logo_site' => '站点图标', # MODIF
	'logo_standard_rubrique' => '专栏标准图标', # MODIF
	'logo_survol' => '盘旋图标', # MODIF

	// M
	'menu_aide_installation_choix_base' => '选择数据库',
	'module_fichier_langue' => '语言文件',
	'module_raccourci' => '快捷方式',
	'module_texte_affiche' => '显示文本',
	'module_texte_explicatif' => '你不能插入快捷方式到站点模板. 有一种语言他们将自动翻译为各种语言.',
	'module_texte_traduction' => '语言文件 « @module@ » 可用在:',
	'mois_non_connu' => '不知道',

	// O
	'onglet_repartition_actuelle' => '现在',

	// R
	'required' => '[必须的]', # MODIF

	// S
	'statut_admin_restreint' => '(受限制的管理)', # MODIF

	// T
	'taille_cache_infinie' => '本网站对 <code>CACHE/</code>目录的大小没有限制。', # MODIF
	'taille_cache_maxi' => '网络文章发布系统将尝试限制 <code>CACHE/</code> 目录的大小至大约 <b>@octets@</b> 数据.', # MODIF
	'taille_cache_octets' => '缓存目录当前的大小是 @octets@。', # MODIF
	'taille_cache_vide' => '缓存当前状态为空。',
	'taille_repertoire_cache' => '缓存目录的大小',
	'text_article_propose_publication' => '文章已提交发表. 不要犹豫通过论坛发表你的观点附在文章后 (在页底).', # MODIF
	'texte_acces_ldap_anonyme_1' => '一些 LDAP 服务器不允许任何匿名访问. 这样你必须标识初始连接,以后能搜索目录中信息. 无论如何, 大多数情况下以下区域可留空.',
	'texte_admin_effacer_01' => '命令删除数据库的<i>所有</i> 内容包括
<i>所有</i> 访问者和管理者的访问参数. 执行后, 为新建数据库和第一个管理员访问你应
重新安装 SPIP .',
	'texte_adresse_annuaire_1' => '( 如果你的目录安装到同样机器作为WEB站点, 可能 «localhost».)',
	'texte_ajout_auteur' => '以下作者加到文章:',
	'texte_annuaire_ldap_1' => '若你有权访问(LDAP) 目录, 你可用它在SPIP下自动导入用户.',
	'texte_article_statut' => '文章是:',
	'texte_article_virtuel' => '虚文章',
	'texte_article_virtuel_reference' => '<b>虚文章 :</b>在SPIP中引用文档, 但是重定向到其它的URL. 移去链接, 删除以下 URL.',
	'texte_aucun_resultat_auteur' => '"@cherche_auteur@"没有结果.',
	'texte_auteur_messagerie' => '站点能连续监控连接编辑列表, 它允许实时交换信息 (如果以上消息被禁, 连接编辑列表自身禁用). 你能决定不出现在列表中 (其他用户在列表中" 无法 "看到你）', # MODIF
	'texte_auteurs' => '作者',
	'texte_choix_base_1' => '选择你的数据库:',
	'texte_choix_base_2' => 'SQL 服务器包括几个数据库.',
	'texte_choix_base_3' => '<b>选择</b> 以下主机给你提供的这个:', # MODIF
	'texte_compte_element' => '@count@ 元素',
	'texte_compte_elements' => '@count@ 元素',
	'texte_connexion_mysql' => '根据你主机提到的信息: 它将给你, 如果你的主机支持 SQL,SQL 服务器的连接码.', # MODIF
	'texte_contenu_article' => '(简要说明文章的内容.)',
	'texte_contenu_articles' => '基于为你选择的站点的展开, 你能决定
  一些文章元素没有用.
  用以下列表选择哪一个元素将可用.',
	'texte_crash_base' => '如果数据库毁坏
   , 你可以自动修复
   它.',
	'texte_creer_rubrique' => '在写文章前,<br />您必须创建一个专栏.', # MODIF
	'texte_date_creation_article' => '创建文章日期:',
	'texte_date_publication_anterieure' => '更早的出版日期', # MODIF
	'texte_date_publication_anterieure_nonaffichee' => '隐藏更早的出版日期.', # MODIF
	'texte_date_publication_article' => '在线出版日期:', # MODIF
	'texte_descriptif_rapide' => '主要描述',
	'texte_effacer_base' => '删除SPIP 数据库',
	'texte_en_cours_validation' => '下列文章和新闻提交出版. 请不要犹豫通过论坛发表您的观点.', # MODIF
	'texte_enrichir_mise_a_jour' => '你可以丰富你的文本,通过«文字快捷方式».',
	'texte_fichier_authent' => '<b>让SPIP创建特殊的<tt>.htpasswd</tt>
  并且<tt>.htpasswd-admin</tt> 文件在目录@dossier@?</b><p>
  这些文件能用于严格限制访问作者和管理者
  在站点的不同部分
  (例如, 外部统计编程).<p>
  如果你没有用这样的文件, 留下该选项为它的缺省值
   (没有建
  文件).', # MODIF
	'texte_informations_personnelles_1' => '系统将提供给你提供定制访问.',
	'texte_informations_personnelles_2' => '(注意: 如果是重新安装, 你的访问正在工作, 你可以', # MODIF
	'texte_introductif_article' => '(文章介绍.)',
	'texte_jeu_caractere' => '如果你的站点显示的字符不同于罗马数字(就是 «western»)
 这个选项很有用.
 这种情况下, 为使用合适的字符集缺省设置必须改变
; 无论如何, 我们建议你试试不同的字符符集
 . 如果你修改参数, 不要忘记, 
 根据 (<tt>#CHARSET</tt> 标记)协调公共站点.', # MODIF
	'texte_login_ldap_1' => '(匿名访问留空或输入完整路径, 例如 «<tt>uid=smith, ou=users, dc=my-domain, dc=com</tt>».)',
	'texte_login_precaution' => '警告 ! 这是你正连接的登录.
 小心使用这个表单...',
	'texte_mise_a_niveau_base_1' => '你已更新 SPIP 文件.
 现在你必须更新站点
 数据库.',
	'texte_modifier_article' => '修改文章:',
	'texte_multilinguisme' => '如果您希望用复杂导航管理多语言文章, 您可以根据站点的组织, 在文章及/或专栏中添加语言选择菜单.', # MODIF
	'texte_multilinguisme_trad' => '同样,在不同的文章翻译中你可以激活连接管理系统.', # MODIF
	'texte_non_compresse' => '<i>未解压</i> (你的服务器不支持)',
	'texte_nouvelle_version_spip_1' => '您已经安装了新版SPIP.',
	'texte_nouvelle_version_spip_2' => '新版本需要比通常更彻底的更新. 如果你是站点管理员, 请删除目录中 <tt>ecrire</tt>文件 <tt>inc_connect.php3</tt>  并重新安装更新你的数据库连接参数. <p>(NB.: 如果你忘记了连接参数, 在删除前看看<tt>inc_connect.php3</tt> ...)', # MODIF
	'texte_operation_echec' => '返回前页,选择另一个数据库或新建一个. 确认你主机提供的信息.',
	'texte_plus_trois_car' => '多于 3 字符',
	'texte_plusieurs_articles' => '"@cherche_auteur@好几个作者找到了":',
	'texte_port_annuaire' => '(一般缺省值更合适.)',
	'texte_proposer_publication' => '当你的文章完成,<br /> 你可提交出版.', # MODIF
	'texte_proxy' => '一些情况下 (内部网, 受保护的网络...),
  有必要用 <i>代理HTTP</i> 到达联合站点.
  只要有一个代理就在以下输入一个地址, 因此
  <tt><html>http://proxy:8080</html></tt>. 一般地,
  你可以留空.', # MODIF
	'texte_publication_articles_post_dates' => 'SPIP将采纳提供的将来
  出版的文章
  什么行为?',
	'texte_rappel_selection_champs' => '[记住正确选择区域.]',
	'texte_recalcul_page' => '如果你只要刷新
这页, 最好在公共区做,使用按钮 « refresh ».',
	'texte_recuperer_base' => '修复数据库',
	'texte_reference_mais_redirige' => '你的SPIP参考的文章, 但是重定向到别的 URL.',
	'texte_requetes_echouent' => '<b>当一些 SQL 查询失败并且没有任何原因显示
  , 可能是数据库
  自动出错了
  .</b>
  <p>SQL 有修复表的配置
  当它们被偶然打断.
  在这里, 你可以执行修复;
  为避免失败, 你应保持显示的备份, 这将包含
  出错的线索...
  <p>如果问题仍然存在,请联系
  主机.', # MODIF
	'texte_selection_langue_principale' => '你可在下面选择"主要语言". 幸运地,选择不限制你的文章使用选中的语言,但允许确定

<ul><li> 公众站点的缺省日期格式</li>

<li> 文字引擎将用于SPIP自动翻译;</li>

<li> 公众站点上论坛的语言</li>

<li> 私有区显示缺省语言.</li></ul>',
	'texte_sous_titre' => '子标题',
	'texte_statistiques_visites' => '(黑线:  周日 / 夜晚 曲线: 平均进展)',
	'texte_statut_attente_validation' => '未确认',
	'texte_statut_publies' => '在线出版',
	'texte_statut_refuses' => '丢弃',
	'texte_suppression_fichiers' => '使用命令删除SPIP缓存中的文件
这允许你, 另外地, 以防你进入站点结构和图片重要修改后
强制你刷新所有的页面.',
	'texte_sur_titre' => '顶标题',
	'texte_table_ok' => ': 表好了.',
	'texte_tentative_recuperation' => '试图修复',
	'texte_tenter_reparation' => '试图修复数据库',
	'texte_test_proxy' => '若使用代理, 输入要测试的
      网站地址.',
	'texte_titre_02' => '主题:',
	'texte_titre_obligatoire' => '<b>标题</b> [必需]', # MODIF
	'texte_travail_article' => '@nom_auteur_modif@  @date_diff@ 分钟前正在修改这篇文章',
	'texte_travail_collaboratif' => '如果经常好几个作者编辑同一文章
  ,系统能显示最近的文章 
  «opened» 文章
  为避免同时修改.
  该选项为避免不合时宜的警告信息缺省
  设定为
  不可用.',
	'texte_vide' => '清空',
	'texte_vider_cache' => '清空缓存',
	'titre_admin_tech' => '技术维护',
	'titre_admin_vider' => '技术维护',
	'titre_cadre_afficher_article' => '显示文章:',
	'titre_cadre_afficher_traductions' => '显示语言的翻译状态.',
	'titre_cadre_ajouter_auteur' => '加作者:',
	'titre_cadre_interieur_rubrique' => '在专栏内部',
	'titre_cadre_numero_auteur' => '作者号',
	'titre_cadre_signature_obligatoire' => '<b>签名</b> [必需]<br />', # MODIF
	'titre_config_fonctions' => '站点配置',
	'titre_configuration' => '站点配置',
	'titre_connexion_ldap' => '选项: <b>你的 LDAP 连接</b>',
	'titre_groupe_mots' => '关键词组:',
	'titre_langue_article' => '文章语言', # MODIF
	'titre_langue_rubrique' => '专栏使用的语言', # MODIF
	'titre_langue_trad_article' => '文章语言和译文',
	'titre_les_articles' => '文章',
	'titre_naviguer_dans_le_site' => '浏览站点...',
	'titre_nouvelle_rubrique' => '新专栏',
	'titre_numero_rubrique' => '专栏编号:',
	'titre_page_articles_edit' => '修改: @titre@',
	'titre_page_articles_page' => '文章',
	'titre_page_articles_tous' => '整个站点',
	'titre_page_calendrier' => '日历 @nom_mois@ @annee@',
	'titre_page_config_contenu' => '站点配置',
	'titre_page_delete_all' => '所有和不能撤回的删除',
	'titre_page_recherche' => '搜索结果@recherche@',
	'titre_page_statistiques_referers' => '统计(引入链接)',
	'titre_page_upgrade' => 'SPIP升级 ',
	'titre_publication_articles_post_dates' => '发表日期文章的出版物',
	'titre_reparation' => '修复',
	'titre_suivi_petition' => '跟踪请求',
	'trad_article_traduction' => '这篇文章的所有版本:',
	'trad_delier' => '取消这篇文章到它的译文的链接', # MODIF
	'trad_lier' => '该篇文章译自文章No.',
	'trad_new' => '为该篇文章写一篇新译文' # MODIF
);
