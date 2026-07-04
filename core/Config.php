<?php

namespace nunas_download;

class Config
{
    static $plugin_dir;
    static $plugin_url;
    static $plugin_name;
    static $plugin_version;
    static $plugin_version_name;
    static $static_url;
    static $js_url;
    static $css_url;
    static $img_url;
    static $lib_url;
    static $set_name;
    static $img_dir;

    static function init()
    {
        self::$plugin_name = 'nunas_download';
        self::$plugin_version = filemtime(__FILE__);
        self::$plugin_version_name = '1.1.1';
        self::$set_name = 'nunas_download_set';

        self::$plugin_dir = dirname(__DIR__);
        self::$plugin_url = \plugins_url('', self::$plugin_dir . '/index.php');
        if (\is_ssl()) {
            self::$plugin_url = str_replace('http://', 'https://', self::$plugin_url);
        }
        self::$static_url = self::$plugin_url . '/static';
        self::$js_url = self::$static_url . '/js';
        self::$css_url = self::$static_url . '/css';
        self::$lib_url = self::$static_url . '/lib';
        self::$img_url = self::$static_url . '/img';
        self::$img_dir = self::$plugin_dir . '/static/img';

        require_once 'LoadFiles.php';
        LoadFiles::init();
    }
}

Config::init();
