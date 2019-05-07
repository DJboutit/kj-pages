<?php
# kleeja plugin
# kj_pages
# version: 1.0
# developer: kleeja team


# prevent illegal run
if (!defined('IN_PLUGINS_SYSTEM')) {
    exit();
}

//todo:
// settings page [select a first page ? ] [ability to hide original menu items]
// snapshots history, [delete, backroll, delete all except current...]

# plugin basic information
$kleeja_plugin['kj_pages']['information'] = array(
    # the casucal name of this plugin, anything can a human being understands
    'plugin_title' => array(
        'en' => 'KJ Pages',
        'ar' => 'صفحات إضافية'
    ),
    # who wrote this plugin?
    'plugin_developer' => 'kleeja.com',
    # this plugin version
    'plugin_version' => '1.0',
    # explain what is this plugin, why should i use it?
    'plugin_description' => array(
        'en' => 'Adds extra pages to Kleeja',
        'ar' => 'إضافة صفحات إضافية لكليجا'
    ),
    # min version of kleeja that's required to run this plugin
    'plugin_kleeja_version_min' => '3.0',
    # max version of kleeja that support this plugin, use 0 for unlimited
    'plugin_kleeja_version_max' => '4.0',
    # should this plugin run before others?, 0 is normal, and higher number has high priority
    'plugin_priority' => 0
);

//after installation message, you can remove it, it's not requiered
$kleeja_plugin['kj_pages']['first_run']['ar'] = "
شكراً لاستخدامك إضافة الصفحات لكليجا، قم بمراسلتنا بالأخطاء عند ظهورها على البريد: <br>
info@kleeja.com
";

$kleeja_plugin['kj_pages']['first_run']['en'] = "
Thank you for using our plugin, if you encounter any bugs and errors, contact us: <br>
info@kleeja.com
";

# plugin installation function
$kleeja_plugin['kj_pages']['install'] = function ($plg_id) {
    global $dbprefix, $SQL;

    $sqlPagesTable = "CREATE TABLE `{$dbprefix}kj_pages` (
  `page_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `page_slug` varchar(250) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `page_title` varchar(250) COLLATE utf8_bin DEFAULT NULL,
  `page_created_time` int(11) DEFAULT NULL,
  `page_updated_time` int(11) DEFAULT NULL,
  `page_order` int(4) DEFAULT 0,
  `page_views` int(11) DEFAULT 0,
  `page_category` int(11) DEFAULT NULL,
  `snap_id` int(11) unsigned DEFAULT NULL,
  `page_menu_hide` tinyint(1) unsigned DEFAULT 0,
  `page_password` varchar(250) COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`page_id`),
  UNIQUE KEY `slug` (`page_slug`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;";

    $SQL->query($sqlPagesTable);

    $sqlSnapshotsTable = "CREATE TABLE `{$dbprefix}kj_pages_snapshots` (
  `snap_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` int(11) unsigned DEFAULT NULL,
  `snap_content` mediumtext CHARACTER SET utf8 COLLATE utf8_bin,
  `snap_time` int(11) unsigned DEFAULT NULL,
  `snap_parent` int(11) unsigned DEFAULT NULL,
  `snap_published` tinyint(1) unsigned DEFAULT 0,
  `snap_current` tinyint(1) unsigned DEFAULT 0,
  PRIMARY KEY (`snap_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;";

    $SQL->query($sqlSnapshotsTable);

    $sqlCategoriesTable = "CREATE TABLE `{$dbprefix}kj_pages_categories` (
  `cat_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `cat_name` varchar(250) COLLATE utf8_bin DEFAULT NULL,
  `cat_title` varchar(250) COLLATE utf8_bin DEFAULT NULL,
  `cat_hide` tinyint(1) DEFAULT '0',
  `cat_order` int(4) DEFAULT NULL,
  PRIMARY KEY (`cat_id`),
  UNIQUE KEY `cat_name` (`cat_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;";

    $SQL->query($sqlCategoriesTable);


    //new language variables
    add_olang(array(
        'R_KJ_PAGES_LIST' => 'صفحات إضافية',
        'KJ_PAGES_NO_PAGES_YET' => 'لايوجد أي صفحات حاليا!',
        'KJ_PAGES_NO_PAGES_YET_EXP' => 'للبدء بإضافة صفحات رائعة مثلك، قم بالضغط على زر «صفحة جديدة» بالأعلى ...',
        'KJ_PAGES_NEW_PAGE' => 'صفحة جديدة',
        'KJ_PAGES_NEW_PAGE_EXP' => 'عند الضغط على إنشاء سيتم إضافة صفحة جديدة ويمكنك حينها تعديل محتوياتها.',
        'KJ_PAGES_CREATE' => 'إنشاء',
        'KJ_PAGES_PAGE_ADDED' => 'تم إنشاء الصفحة بنجاح...',
        'KJ_PAGES_PAGE_DELETED' => 'تم حذف الصفحة بنجاح',
        'KJ_PAGES_PAGE_AUTO_UPDATED' => 'تم حفظ الصفحة بشكل تلقائي ...',
        'KJ_PAGES_PAGE_UPDATED' => 'تم حفظ تعديلاتك على الصفحة بشكل ناجح ...',
        'KJ_PAGES_PAGE_ERROR_UPDATED' => 'هناك خطأ يمنع حفظ النسخة الحالية ... ',
        'KJ_PAGES_PAGE_TITLE' => 'عنوان الصفحة',
        'KJ_PAGES_PAGE_SLUG' => 'الاسم اللطيف',
        'KJ_PAGES_PAGE_KEYWORDS' => 'كلمات الصفحة (سيو)',
        'KJ_PAGES_PAGE_DESC' => 'وصف الصفحة (سيو)',
        'KJ_PAGES_PAGE_HIDE_MENU' => 'إخفاء رابط الصفحة من قائمة العرض',
        'KJ_PAGES_PAGE_PASSWORD' => 'كلمة سر للصفحة',
        'KJ_PAGES_PAGE_PASSWORD_EXP' => 'إملأ الحقل هذا فقط عند الحاجة لحجب الصفحة من الوصول إلا بكلمة سر.',
        'KJ_PAGE_PAGE_SLUG_EXP' => 'يستخدم للروابط، وهي كلمات بدون مسافات وشرطة بين الكلمات ويفضل بالانجليزي، مثلاً : buraydah-city',
        'KJ_PAGES_PAGE_CONTENT' => 'محتوى الصفحة',
        'KJ_PAGES_PAGE_ORDER' => 'ترتيب عرض الصفحة (رقم)',
        'KJ_PAGES_SLUG_CONFLICT' => 'يبدو أن الاسم اللطيف قد تم استخدامه سابقاً، استخدم اسم آخر.',
        'KJ_PAGES_MISSING_SNAPSHOT' => 'يبدو أن نسخة المقالة المحفوظة مفقودة! قم بتحديث الصفحة او الرجوع لقائمة الصفحات.',
        'KJ_PAGES_PAGE_NOT_FOUND' => 'لم يتم  إيجاد صفحة بهذه المعلومات! قد تكون حُذفت أو حصل أمر خارق وأختفت من الوجود!.',
        'KJ_PAGES_RECOVER_ORIGINAL' => 'لقد تم إستعادة آخر التعديلات التي تم العمل عليها. للرجوع للنسخة الأصلية من الصفحة اضغط <a href="%s">هنا</a>.',
        'KJ_PAGES_CATEGORY_DELETED' => 'تم حذف القسم بنجاح...',
        'KJ_PAGES_CATEGORY_ADDED' => 'تم إضافة القسم بنجاح...',
        'KJ_PAGES_CATEGORY_UPDATED' => 'تم تحديث بيانات القسم بنجاح...',
        'KJ_PAGES_CATEGORY_NOT_FOUND' => 'لم يتم إيجاد القسم المطلوب .. قد يكون حُذف!',
        'KJ_PAGES_NEW_CATEGORY' => 'قسم جديد',
        'KJ_PAGES_NEW_CATEGORY_EXP' => 'عند الضغط على إنشاء سيتم إضافة قسم جديد ويمكنك حينها تعديل بياناته.',
        'KJ_PAGES_NO_CATEGORIES_YET' => 'لايوجد أي أقسام حالياً!',
        'KJ_PAGES_NO_CATEGORIES_YET_EXP' => 'للبدء بإضافة أقسام رائعة مثلك، قم بالضغط على زر «قسم جديد» بالأعلى ...',
        'KJ_PAGES_CATEGORY_TITLE' => 'عنوان القسم',
        'KJ_PAGES_CATEGORY_NAME' => 'اسم القسم',
        'KJ_PAGES_CATEGORY_NAME_EXP' => 'يستخدم للروابط، وهي كلمات بدون مسافات وشرطة بين الكلمات ويفضل بالانجليزي، مثلاً : saudi-arabia-attractions',

    ),
        'ar',
        $plg_id);

    add_olang(array(
        'R_KJ_PAGES_LIST' => 'Extra Pages',
        'KJ_PAGES_NO_PAGES_YET' => 'لايوجد أي صفحات حاليا!',
        'KJ_PAGES_NO_PAGES_YET_EXP' => 'للبدء بإضافة صفحات رائعة مثلك، قم بالضغط على زر «صفحة جديدة» بالأعلى ...',
        'KJ_PAGES_NEW_PAGE' => 'صفحة جديدة',
        'KJ_PAGES_NEW_PAGE_EXP' => 'عند الضغط على إنشاء سيتم إضافة صفحة جديدة ويمكنك حينها تعديل محتوياتها.',
        'KJ_PAGES_CREATE' => 'إنشاء',
        'KJ_PAGES_PAGE_ADDED' => 'تم إنشاء الصفحة بنجاح...',
        'KJ_PAGES_PAGE_DELETED' => 'تم حذف الصفحة بنجاح',
        'KJ_PAGES_PAGE_AUTO_UPDATED' => 'تم حفظ الصفحة بشكل تلقائي ...',
        'KJ_PAGES_PAGE_UPDATED' => 'تم حفظ تعديلاتك على الصفحة بشكل ناجح ...',
        'KJ_PAGES_PAGE_ERROR_UPDATED' => 'هناك خطأ يمنع حفظ النسخة الحالية ... ',
        'KJ_PAGES_PAGE_TITLE' => 'عنوان الصفحة',
        'KJ_PAGES_PAGE_SLUG' => 'الاسم اللطيف',
        'KJ_PAGES_PAGE_KEYWORDS' => 'كلمات الصفحة (سيو)',
        'KJ_PAGES_PAGE_DESC' => 'وصف الصفحة (سيو)',
        'KJ_PAGES_PAGE_HIDE_MENU' => 'إخفاء رابط الصفحة من قائمة العرض',
        'KJ_PAGES_PAGE_PASSWORD' => 'كلمة سر للصفحة',
        'KJ_PAGES_PAGE_PASSWORD_EXP' => 'إملأ الحقل هذا فقط عند الحاجة لحجب الصفحة من الوصول إلا بكلمة سر.',
        'KJ_PAGE_PAGE_SLUG_EXP' => 'يستخدم للروابط، وهي كلمات بدون مسافات وشرطة بين الكلمات ويفضل بالانجليزي، مثلاً : buraydah-city',
        'KJ_PAGES_PAGE_CONTENT' => 'محتوى الصفحة',
        'KJ_PAGES_PAGE_ORDER' => 'ترتيب عرض الصفحة (رقم)',
        'KJ_PAGES_SLUG_CONFLICT' => 'يبدو أن الاسم اللطيف قد تم استخدامه سابقاً، استخدم اسم آخر.',
        'KJ_PAGES_MISSING_SNAPSHOT' => 'يبدو أن نسخة المقالة المحفوظة مفقودة! قم بتحديث الصفحة او الرجوع لقائمة الصفحات.',
        'KJ_PAGES_PAGE_NOT_FOUND' => 'لم يتم  إيجاد صفحة بهذه المعلومات! قد تكون حُذفت أو حصل أمر خارق وأختفت من الوجود!.',
        'KJ_PAGES_RECOVER_ORIGINAL' => 'لقد تم إستعادة آخر التعديلات التي تم العمل عليها. للرجوع للنسخة الأصلية من الصفحة اضغط <a href="%s">هنا</a>.',
        'KJ_PAGES_CATEGORY_DELETED' => 'تم حذف القسم بنجاح...',
        'KJ_PAGES_CATEGORY_ADDED' => 'تم إضافة القسم بنجاح...',
        'KJ_PAGES_CATEGORY_UPDATED' => 'تم تحديث بيانات القسم بنجاح...',
        'KJ_PAGES_CATEGORY_NOT_FOUND' => 'لم يتم إيجاد القسم المطلوب .. قد يكون حُذف!',
        'KJ_PAGES_NEW_CATEGORY' => 'قسم جديد',
        'KJ_PAGES_NEW_CATEGORY_EXP' => 'عند الضغط على إنشاء سيتم إضافة قسم جديد ويمكنك حينها تعديل بياناته.',
        'KJ_PAGES_NO_CATEGORIES_YET' => 'لايوجد أي أقسام حالياً!',
        'KJ_PAGES_NO_CATEGORIES_YET_EXP' => 'للبدء بإضافة أقسام رائعة مثلك، قم بالضغط على زر «قسم جديد» بالأعلى ...',
        'KJ_PAGES_CATEGORY_TITLE' => 'عنوان القسم',
        'KJ_PAGES_CATEGORY_NAME' => 'اسم القسم',
        'KJ_PAGES_CATEGORY_NAME_EXP' => 'يستخدم للروابط، وهي كلمات بدون مسافات وشرطة بين الكلمات ويفضل بالانجليزي، مثلاً : saudi-arabia-attractions',
    ),
        'en',
        $plg_id);


    add_config_r(array(
        'kj_pages_homepage' =>
            array(
            'value' => '0',
            'plg_id' => $plg_id,
            'type' => 'kj_pages'
        ),
        'kj_pages_url_format' =>
            array(
                //1: /p/{page_slug}
                //2: /p/{page_id}
                //3: /{page_slug}
                //4: /{cat_name}/{page_slug}
                //5: /{day}/{month}/{year}/{page_slug}
                //6: /{month}/{year}/{page_slug}
            'value' => '1',
            'plg_id' => $plg_id,
            'type' => 'kj_pages'
        ),
        'kj_pages_hide_menu_items' =>
            array(
            'value' => '',
            'plg_id' => $plg_id,
            'type' => 'kj_pages'
        ),
    ));
};


//plugin update function, called if plugin is already installed but version is different than current
$kleeja_plugin['kj_pages']['update'] = function ($old_version, $new_version) {
    //
    // if(version_compare($old_version, '0.6', '<')){
    // 	//... update to 0.6
    // }

    //you could use update_config, update_olang
};


# plugin uninstalling, function to be called at uninstalling
$kleeja_plugin['kj_pages']['uninstall'] = function ($plg_id) {
    global $SQL, $dbprefix;

    foreach(array(
        "DROP TABLE `{$dbprefix}kj_pages`",
        "DROP TABLE `{$dbprefix}kj_pages_snapshots`",
        "DROP TABLE `{$dbprefix}kj_pages_categories`",
    ) as $sql)
    {
        $SQL->query($sql);
    }

    delete_olang(null, null, $plg_id);
    delete_config(array('kj_pages_homepage', 'kj_pages_hide_menu_items'));
};


# plugin functions
$kleeja_plugin['kj_pages']['functions'] = array(

    //add to admin menu
    'begin_admin_page' => function ($args)
    {
        $adm_extensions = $args['adm_extensions'];
        $ext_icons = $args['ext_icons'];

        $adm_extensions[] = 'kj_pages_list';
        $ext_icons['kj_pages_list'] = 'file';
        return compact('adm_extensions', 'ext_icons');
    },

    //add as admin page to reach when click on admin menu item we added.
    'not_exists_kj_pages_list' => function()
    {
        $include_alternative = dirname(__FILE__) . '/kj_pages_list.php';

        return compact('include_alternative');
    },

    //add links to kleeja style menu
    'Saaheader_links_func' => function($args) {
        $top_menu = $args['top_menu'];
        $top_menu[] = array('name' => 'xst', 'title' => $lang['STATS'].'x', 'url' => $config['mod_writer'] ? 'stats.html' : 'go.php?go=stats', 'show' => true);

        return compact('top_menu');
    },

    'default_go_page' => function($args) {

        if(ig('kj_pages'))
        {
            //... htaccess !
        }

        if(ig('p'))
        {
            global $tpl, $dbprefix, $SQL, $olang, $config;

            $no_request = $show_style = false;

            $pageId = g('p', 'int');
            $pageSlug = g('pslug');

            $query = array(
                'SELECT' => 'p.*, s.snap_content',
                'FROM' => "`{$dbprefix}kj_pages` p",
                'JOINS' => array(
                    array(
                        'LEFT JOIN' => "{$dbprefix}kj_pages_snapshots s",
                        'ON' => 's.snap_id=p.snap_id AND s.snap_published=1'
                    )
                ),
                'WHERE' => empty($pageId)
                        ? "p.page_slug='" . $SQL->escape($pageSlug) . "'"
                        : "p.page_id=" . $SQL->escape($pageId)
            );

            $result = $SQL->build($query);

            if(! $SQL->num_rows($result))
            {
                kleeja_info($olang['KJ_PAGES_PAGE_NOT_FOUND']);
            }

            $page = $SQL->fetch($result);

            if($tpl->template_exists('kj_pages_page'))
            {
                $stylee = 'kj_pages_page';
            }
            else
            {
                $stylee = $config['style'] == 'default' || $config['style_depend_on'] == 'default'
                        ? 'page_default'
                        : 'page_bootstrap';
            }


            Saaheader($page['page_title'], $extraHeader);
            $tpl->assign('page', $page);
            echo $tpl->display($stylee, dirname(__FILE__) . '/templates/');
            Saafooter();

            return compact('no_request', 'stylee', 'show_style');
        }
    },
);

//


/**
 * special functions
 */
