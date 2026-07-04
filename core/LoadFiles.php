<?php

namespace nunas_download;

class LoadFiles
{
    static function init()
    {
        $core_dir = Config::$plugin_dir . '/core';
        require_once "$core_dir/Plugin.php";
        require_once "$core_dir/WordPress.php";
        require_once "$core_dir/Options.php";
        require_once "$core_dir/Template.php";
        require_once "$core_dir/Tools.php";
        require_once "$core_dir/Ajax.php";

        global $nunas_download_set;
        $nunas_download_set['set'] = Options::getOptions();

        Plugin::init();
        Ajax::init();

        \add_action('admin_enqueue_scripts', [static::class, '_loadFileOnAdmin']);
        \add_action('wp_enqueue_scripts', [static::class, '_LoadFileOnSite']);
    }

    static function _LoadFileOnSite()
    {
        if (\is_single()) {
            WordPress::loadCss('nunas_download_front', 'front.css');
            WordPress::loadJS('nunas_download_home', 'home.js', true, [], true);
        }
    }

    static function _loadFileOnAdmin($hook)
    {
        WordPress::loadCss('nunas-download-admin', 'admin.css');
        \wp_localize_script('jquery', 'nunas_download', Options::getAdminSet());
        if ($hook == 'toplevel_page_nunas_download_set') {
            WordPress::echoJson('nunas_download_set', Options::getAdminSet());
            WordPress::loadCss('nunas-download-admin-set', 'admin-set.css');
            WordPress::loadJS('nunas_download_admin_set', 'admin-set.js', true, [], true);
            \wp_enqueue_media();
        }
    }
}
