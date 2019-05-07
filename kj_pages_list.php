<?php

// not for directly open
if (! defined('IN_ADMIN'))
{
	exit;
}


#current case
$current_case = g('case');

#current template
$stylee = 'admin_kj_pages';

// template variables
$styleePath = dirname(__FILE__) . '/templates';

$pluginUrlPath = $config['siteurl'] . KLEEJA_PLUGINS_FOLDER . '/kj_pages';
$currentLang = explode('-', $lang['LANG_SMALL_NAME'])[0];
$currentLang = ! in_array($currentLang, ['ar', 'en']) ? 'en' : $currentLang;

$action = basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php');

$H_FORM_KEYS	= kleeja_add_form_key('adm_kj_pages');
$H_FORM_KEYS_GET	= kleeja_add_form_key_get('adm_kj_pages');

$current_case = ig('p') ? 'edit' : (ig('c') ? 'edit_category' : g('type', 'str', 'list'));

if(ip('submit'))
{
    if(!kleeja_check_form_key('adm_kj_pages', 3600))
    {
        kleeja_admin_err($lang['INVALID_FORM_KEY'], true, $lang['ERROR'], true, $action, 1);
    }
}


$ERRORS = false;

switch ($current_case)
{
    /**
     * show a list of current ftp accounts
     */
    default:
    case 'list':

        $query	= array(
            'SELECT'	=> 'k.*',
            'FROM'		=> "`{$dbprefix}kj_pages` k",
            'ORDER BY'	=> 'k.page_id ASC'
        );


        $result = $SQL->build($query);

        $result_number = $SQL->num_rows($result);

        $pages = array();

        if($result_number > 0)
        {
            while($row=$SQL->fetch_array($result))
            {
				$pages[] = $row;
            }
        }

        $SQL->free();


        break;


    /**
     * delete a page
     */
    case 'delete':

        $page_id = g('page');

        if (! kleeja_check_form_key_get('adm_kj_pages', 3600)) 
        {
            header('HTTP/1.1 405 Method Not Allowed');
            $adminAjaxContent = $lang['INVALID_FORM_KEY'];
        }
        else if (! $SQL->num_rows(
            $SQL->query("SELECT * FROM {$dbprefix}kj_pages WHERE page_id=" . $page_id)
        )) 
        {
            header('HTTP/1.1 500 Internal Server Error');
            $adminAjaxContent = $olang['KJ_PAGES_PAGE_NOT_FOUND'];
        }
        else
        {
            $SQL->query("DELETE FROM {$dbprefix}kj_pages WHERE page_id=" . $page_id);
            $SQL->query("DELETE FROM {$dbprefix}kj_pages_snapshots WHERE page_id=" . $page_id);

            $adminAjaxContent = $olang['KJ_PAGES_PAGE_DELETED'];
        }
        break;


    /**
     * add new page
     */
    case 'new':

    
        //is this enough ?
        $unique_name = uniqid();

        $insert_query	= array(
            'INSERT'	=> 'page_slug, page_title, page_created_time',
            'INTO'		=> "{$dbprefix}kj_pages",
            'VALUES'	=> "'new-page-".$unique_name."', '".$olang['KJ_PAGES_NEW_PAGE']."', " .time()
        );

        if ($SQL->build($insert_query)) {
            $last_page_id = $SQL->insert_id();

            kleeja_admin_info($olang['KJ_PAGES_PAGE_ADDED'], true, '', true, $action . '&p='.$last_page_id, 3);
        }

        break;


    case 'edit':

        $query = array(
            'SELECT' => 'k.*',
            'FROM' => "`{$dbprefix}kj_pages` k",
            'WHERE' => 'k.page_id=' . g('p', 'int')
        );

        $result = $SQL->build($query);

        if(! $SQL->num_rows($result))
        {
            kleeja_admin_err($olang['KJ_PAGES_PAGE_NOT_FOUND'], true, '', true, $action);
        }

        $page = $SQL->fetch_array($result);

        if(ig('recover'))
        {
            //delete current
            $query_del = array(
                'DELETE' => "{$dbprefix}kj_pages_snapshots",
                'WHERE' => 'page_id=' . $page['page_id'] . ' AND snap_current=1'
            );

            $SQL->build($query_del);
            redirect($action . '&p='. $page['page_id']);
        }

        //get current snapshot + show (recovered from current snapshot, go back to published page content?)
        $querySnap = array(
            'SELECT' => 's.*',
            'FROM' => "`{$dbprefix}kj_pages_snapshots` s",
            'WHERE' => 's.page_id=' . $page['page_id'] .' AND s.snap_current=1',
            'LIMIT' => '1'
        );

        $resultSnap = $SQL->build($querySnap);
        $recovered = sprintf($olang['KJ_PAGES_RECOVER_ORIGINAL'], $action . '&amp;p='. $page['page_id'].'&recover=1');

        if(! $SQL->num_rows($resultSnap))
        {
            $recovered = false;
            $querySnap['WHERE'] = 's.page_id=' . $page['page_id'] . ' AND s.snap_published=1';
            $resultSnap = $SQL->build($querySnap);
        }

        $snap = $SQL->fetch_array($resultSnap);

        //or insert a new current snapshot from published snapshot and return its data $snap[..]
        if(empty($snap['snap_current']) || $snap['snap_current'] == 0)
        {
            $snap['snap_content'] = $snap['snap_content'] ?? '';
            $snap['snap_time'] = $snap['snap_time'] ?? time();
            $snap['snap_current'] = $snap['snap_current'] ?? 1;

            $insert_snapshot_query = array(
                'INSERT' => 'page_id, snap_content, snap_time, snap_current, snap_parent',
                'INTO' => "{$dbprefix}kj_pages_snapshots",
                'VALUES' => $page['page_id'] . ",'" . $SQL->real_escape($snap['snap_content'] ?? '') . "', " . time() . ", 1," . intval($page['snap_id'])
            );
            $SQL->build($insert_snapshot_query);

            $snap['snap_id'] = $SQL->insert_id();
        }

        break;

        case 'save':

            if (! kleeja_check_form_key('adm_kj_pages', 3600)) 
            {
                header('HTTP/1.1 405 Method Not Allowed');
                $adminAjaxContent = $lang['INVALID_FORM_KEY'];
            }
            else if ($_SERVER['REQUEST_METHOD'] === 'POST') 
            {
                header("X-XSS-Protection: 0");

                #save, show info
                $page_id = p('page_id', 'int');
                $page['page_title'] = p('page_title');
                $page['page_slug'] = preg_replace('/[\s_.]|-{2,}/', '-', p('page_slug'));
                $page['snap_id'] = p('snap_id', 'int');

                $isAutoSave =  boolval(g('autosave', 'int'));
                $continue = true;

                $snapshot['snap_content'] = $SQL->real_escape(htmlspecialchars_decode(p('snap_content')));

                if(
                    $SQL->num_rows(
                        $SQL->query("SELECT * FROM {$dbprefix}kj_pages WHERE page_id<>" . $page_id . " AND page_slug='" . $SQL->escape($data['page_slug']) . "'")
                    )
                )
                {
                    header('HTTP/1.1 500 Internal Server Error');
                    $adminAjaxContent = $olang['KJ_PAGES_SLUG_CONFLICT'];
                    $continue = false;
                }

                if($continue)
                {
                    if (!$SQL->num_rows(
                        $SQL->query("SELECT * FROM {$dbprefix}kj_pages_snapshots WHERE snap_id=" . $page['snap_id'] . " AND page_id=" . $page_id)
                    )) {
                        header('HTTP/1.1 500 Internal Server Error');
                        $adminAjaxContent = $olang['KJ_PAGES_MISSING_SNAPSHOT'];
                        $continue = false;
                    }
                }

                if($continue)
                {
                    if(! $isAutoSave)
                    {
                        $SQL->query("UPDATE {$dbprefix}kj_pages_snapshots SET snap_published=0 WHERE snap_published=1 AND page_id=" . $page_id);

                        $snapshot['snap_current'] = 0;
                        $snapshot['snap_published'] = 1;
                    }

                    $updateSnapSet = '';
                    foreach ($snapshot as $n=>$v){
                        $updateSnapSet .= ($updateSnapSet == '' ? '' : ', '). "`$n`='" . $v .  "'";
                    }

                    $update_snap_query = array(
                        'UPDATE' => "{$dbprefix}kj_pages_snapshots",
                        'SET' => $updateSnapSet,
                        'WHERE' => "snap_id=" . $page['snap_id']
                    );

                    $SQL->build($update_snap_query);

                    if(! $isAutoSave)
                    {
                        $page['page_updated_time'] = time();

                        $updateSet = '';
                        foreach ($page as $n => $v) {
                            $updateSet .= ($updateSet == '' ? '' : ', ') . "`$n`='" . $v . "'";
                        }

                        $update_query = array(
                            'UPDATE'	=> "{$dbprefix}kj_pages",
                            'SET'		=> $updateSet,
                            'WHERE'		=> "page_id=". $page_id
                        );

                        $SQL->build($update_query);

                        if(! $SQL->affected())
                        {
                            //error affecting updating snapshot
                            header('HTTP/1.1 500 Internal Server Error');
                            $adminAjaxContent = $olang['KJ_PAGES_PAGE_ERROR_UPDATED'];
                            $continue = false;
                        }
                    }

                    if($continue)
                    {
                        $adminAjaxContent = $isAutoSave ? $olang['KJ_PAGES_PAGE_AUTO_UPDATED'] : $olang['KJ_PAGES_PAGE_UPDATED'];
                    }
                }
            }
            else
            {
                header('HTTP/1.1 405 Method Not Allowed');
                $adminAjaxContent = 'Reuqest type must be post.';
            }
        break;

    case 'categories':

        $query	= array(
            'SELECT'	=> 'c.*',
            'FROM'		=> "`{$dbprefix}kj_pages_categories` c",
            'ORDER BY'	=> 'c.cat_id ASC'
        );


        $result = $SQL->build($query);

        $result_number = $SQL->num_rows($result);

        $categories = array();

        if($result_number > 0)
        {
            while($row=$SQL->fetch_array($result))
            {
                $categories[] = $row;
            }
        }

        $SQL->free();


        break;

    /**
     * delete a page
     */
    case 'delete_category':

        $category_id = g('category');

        if (! kleeja_check_form_key_get('adm_kj_pages', 3600)) 
        {
            header('HTTP/1.1 405 Method Not Allowed');
            $adminAjaxContent = $lang['INVALID_FORM_KEY'];
        }
        else if (! $SQL->num_rows(
            $SQL->query("SELECT * FROM {$dbprefix}kj_pages_categories WHERE cat_id=" . $category_id)
        )) 
        {
            header('HTTP/1.1 500 Internal Server Error');
            $adminAjaxContent = $olang['KJ_PAGES_CATEGORY_NOT_FOUND'];
        }
        else
        {
            $SQL->query("UPDATE {$dbprefix}kj_pages SET page_category=NULL WHERE page_category=" . $category_id);
            $SQL->query("DELETE FROM {$dbprefix}kj_pages_categories WHERE cat_id=" . $category_id);

            $adminAjaxContent = $olang['KJ_PAGES_CATEGORY_DELETED'];
        }
        break;


    /**
     * add new category
     */
    case 'new_category':

    
        //is this enough ?
        $unique_name = uniqid();

        $insert_query	= array(
            'INSERT'	=> 'cat_name, cat_title',
            'INTO'		=> "{$dbprefix}kj_pages_categories",
            'VALUES'	=> "'new-category-".$unique_name."', '".$olang['KJ_PAGES_NEW_CATEGORY']."'"
        );

        if ($SQL->build($insert_query)) {
            $last_category_id = $SQL->insert_id();

            kleeja_admin_info($olang['KJ_PAGES_CATEGORY_ADDED'], true, '', true, $action . '&c='. $last_category_id, 3);
        }

        break;



    case 'edit_category':

        $category_id = g('c', 'int');
    
        $query = array(
            'SELECT' => 'c.*',
            'FROM' => "`{$dbprefix}kj_pages_categories` c",
            'WHERE' => 'c.cat_id=' . $category_id
        );

        $result = $SQL->build($query);

        if(! $SQL->num_rows($result))
        {
            kleeja_admin_err($olang['KJ_PAGES_CATEGORY_NOT_FOUND'], true, '', true, $action);
        }

        if(ip('submit'))
        {
            $category = array(
                'cat_name' => strtolower(p('cat_name')),
                'cat_title' => p('cat_title')
            );

            $updateSet = '';
            foreach ($category as $n => $v) {
                $updateSet .= ($updateSet == '' ? '' : ', ') . "`$n`='" . $v . "'";
            }
    
            $update_query = array(
                'UPDATE'	=> "{$dbprefix}kj_pages_categories",
                'SET'		=> $updateSet,
                'WHERE'		=> "cat_id=". $category_id
            );

            $SQL->build($update_query);

            kleeja_admin_info($olang['KJ_PAGES_CATEGORY_UPDATED'], true, '', true, $action . '&type=categories', 3);
        }
        else
        {
            $category = $SQL->fetch_array($result);
        }

        break;

    case 'settings_update':

        if (! kleeja_check_form_key_get('adm_kj_pages', 3600)) 
        {
            header('HTTP/1.1 405 Method Not Allowed');
            $adminAjaxContent = $lang['INVALID_FORM_KEY'];
        }
        else
        {
            $config_name = p('item');
            $config_value = p('value');
            $list = array('kj_pages_homepage', 'kj_pages_url_format', 'kj_pages_hide_menu_items');

            if(in_array('kj_pages_'. $config_name, $list))
            {
                $adminAjaxContent = update_config(
                    'kj_pages_' . $config_name, $config_value
                    ) ? 'done' : 'none';
            }
            $adminAjaxContent = 'none';
        }

        break;
}



